<?php
require_once __DIR__ . '/includes/header.php';

$db = getDB();

// Self-provision the media_gallery table (this repo creates tables on first use, no migration files)
$db->exec("CREATE TABLE IF NOT EXISTS media_gallery (
    id INT AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    filepath VARCHAR(500) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$media_dir_fs  = __DIR__ . '/../assets/images/media';
$media_dir_web = 'assets/images/media';

// One-time seed: if the table is empty, pick up whatever is already sitting in assets/images/media
$count = (int)$db->query("SELECT COUNT(*) FROM media_gallery")->fetchColumn();
if ($count === 0 && is_dir($media_dir_fs)) {
    $existing = array_values(array_filter(scandir($media_dir_fs), function ($f) use ($media_dir_fs) {
        return is_file($media_dir_fs . '/' . $f) && preg_match('/\.(png|jpe?g|gif|webp)$/i', $f);
    }));
    sort($existing);
    $order = 1;
    $ins = $db->prepare("INSERT INTO media_gallery (filename, filepath, sort_order) VALUES (?, ?, ?)");
    foreach ($existing as $f) {
        $ins->execute([$f, $media_dir_web . '/' . $f, $order]);
        $order++;
    }
}

// Handle AJAX reorder (drag & drop)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reorder') {
    validateCsrfToken();
    header('Content-Type: application/json');

    $order = json_decode($_POST['order'] ?? '[]', true);
    if (!is_array($order)) {
        echo json_encode(['success' => false, 'error' => 'Invalid order payload.']);
        exit;
    }

    $update = $db->prepare("UPDATE media_gallery SET sort_order = ? WHERE id = ?");
    $position = 1;
    foreach ($order as $id) {
        $update->execute([$position, (int)$id]);
        $position++;
    }
    echo json_encode(['success' => true]);
    exit;
}

// Handle Upload (Add)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['media_file'])) {
    validateCsrfToken();

    $file = $_FILES['media_file'];
    if ($file['error'] === UPLOAD_ERR_OK) {
        $filename = basename($file['name']);
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $allowed = ['png', 'jpg', 'jpeg', 'gif', 'webp'];

        if (!in_array($ext, $allowed)) {
            setFlash('error', "Only image files (png, jpg, jpeg, gif, webp) can be added to the media gallery.");
            header("Location: media.php");
            exit;
        }

        if (!file_exists($media_dir_fs)) {
            mkdir($media_dir_fs, 0777, true);
        }

        $hash_prefix = bin2hex(random_bytes(6));
        $clean_name = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($filename, PATHINFO_FILENAME));
        $safe_filename = $clean_name . '_' . $hash_prefix . '.' . $ext;

        $target_file = $media_dir_fs . '/' . $safe_filename;
        $rel_path = $media_dir_web . '/' . $safe_filename;

        if (move_uploaded_file($file['tmp_name'], $target_file)) {
            $next_order = (int)$db->query("SELECT COALESCE(MAX(sort_order), 0) FROM media_gallery")->fetchColumn() + 1;
            $stmt = $db->prepare("INSERT INTO media_gallery (filename, filepath, sort_order) VALUES (?, ?, ?)");
            $stmt->execute([$safe_filename, $rel_path, $next_order]);
            setFlash('success', "Image '" . e($safe_filename) . "' added to the media gallery.");
        } else {
            setFlash('error', "Failed to save uploaded image.");
        }
    } else {
        setFlash('error', "Upload error code: " . $file['error']);
    }
    header("Location: media.php");
    exit;
}

// Handle Delete (Remove)
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $itemId = (int)$_GET['id'];
    $stmt = $db->prepare("SELECT * FROM media_gallery WHERE id = ?");
    $stmt->execute([$itemId]);
    $item = $stmt->fetch();

    if ($item) {
        $file_on_disk = __DIR__ . '/../' . $item['filepath'];
        // Path traversal protection: resolved path must stay inside assets/images/media
        if (realpath($file_on_disk) !== false
            && strpos(realpath($file_on_disk), realpath($media_dir_fs)) === 0
            && file_exists($file_on_disk)) {
            unlink($file_on_disk);
        }
        $del = $db->prepare("DELETE FROM media_gallery WHERE id = ?");
        $del->execute([$itemId]);
        setFlash('success', "Image removed from the media gallery.");
    }
    header("Location: media.php");
    exit;
}

$items = $db->query("SELECT * FROM media_gallery ORDER BY sort_order ASC, id ASC")->fetchAll();
?>

<div class="space-y-6">
    <!-- Header & Upload Form -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-gray-100 pb-5">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Media Gallery</h1>
            <p class="text-sm text-gray-500 mt-1">Images shown on the /media.html card gallery. Drag cards to reorder.</p>
        </div>

        <form method="POST" action="media.php" enctype="multipart/form-data" class="flex items-center gap-3">
            <?= csrfField() ?>
            <input type="file" name="media_file" accept=".png,.jpg,.jpeg,.gif,.webp" required class="text-xs text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs px-4 py-2 rounded-lg shadow-sm transition flex items-center gap-1.5 shrink-0">
                <i class="fa-solid fa-cloud-arrow-up"></i> Add Image
            </button>
        </form>
    </div>

    <div id="reorder-status" class="text-xs font-medium text-emerald-600 hidden">
        <i class="fa-solid fa-check"></i> Order saved
    </div>

    <!-- Media Grid -->
    <?php if (empty($items)): ?>
        <div class="text-center text-gray-400 py-16 border border-dashed border-gray-200 rounded-xl">
            <i class="fa-solid fa-images text-3xl mb-3"></i>
            <p class="text-sm">No media images yet. Add one using the form above.</p>
        </div>
    <?php else: ?>
        <div id="media-grid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
            <?php foreach ($items as $item): ?>
                <div class="media-card border border-gray-200 rounded-xl bg-white p-3 flex flex-col justify-between hover:shadow-md transition space-y-2 cursor-grab active:cursor-grabbing" draggable="true" data-id="<?= (int)$item['id'] ?>">
                    <div class="h-28 bg-gray-50 rounded-lg flex items-center justify-center overflow-hidden border border-gray-100 pointer-events-none">
                        <img src="../<?= e($item['filepath']) ?>" alt="" class="max-h-full max-w-full object-contain">
                    </div>
                    <div class="pointer-events-none">
                        <span class="text-xs font-semibold text-gray-800 block truncate" title="<?= e($item['filename']) ?>">
                            <?= e($item['filename']) ?>
                        </span>
                        <span class="text-[10px] text-gray-400 block font-mono">
                            <i class="fa-solid fa-up-down-left-right"></i> Drag to reorder
                        </span>
                    </div>
                    <div class="flex items-center justify-end pt-1 border-t border-gray-100">
                        <a href="media.php?action=delete&id=<?= (int)$item['id'] ?>" onclick="return confirm('Remove this image from the media gallery?');" class="text-[11px] text-rose-600 hover:text-rose-800 font-medium" title="Remove image">
                            <i class="fa-solid fa-trash"></i> Remove
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
(function () {
    var grid = document.getElementById('media-grid');
    if (!grid) return;

    var csrfToken = <?= json_encode(generateCsrfToken()) ?>;
    var statusEl = document.getElementById('reorder-status');
    var dragged = null;

    grid.addEventListener('dragstart', function (e) {
        var card = e.target.closest('.media-card');
        if (!card) return;
        dragged = card;
        e.dataTransfer.effectAllowed = 'move';
        card.style.opacity = '0.4';
    });

    grid.addEventListener('dragend', function () {
        if (dragged) dragged.style.opacity = '';
        dragged = null;
    });

    grid.addEventListener('dragover', function (e) {
        e.preventDefault();
        var card = e.target.closest('.media-card');
        if (!card || card === dragged || !dragged) return;

        var rect = card.getBoundingClientRect();
        var before = (e.clientX - rect.left) < rect.width / 2;
        grid.insertBefore(dragged, before ? card : card.nextSibling);
    });

    grid.addEventListener('drop', function (e) {
        e.preventDefault();
        saveOrder();
    });

    function saveOrder() {
        var ids = Array.prototype.map.call(grid.querySelectorAll('.media-card'), function (c) {
            return c.getAttribute('data-id');
        });

        fetch('media.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'reorder',
                order: JSON.stringify(ids),
                csrf_token: csrfToken
            })
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data.success && statusEl) {
                    statusEl.classList.remove('hidden');
                    setTimeout(function () { statusEl.classList.add('hidden'); }, 1500);
                }
            });
    }
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
