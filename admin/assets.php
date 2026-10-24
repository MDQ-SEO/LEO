<?php
require_once __DIR__ . '/includes/header.php';

$db = getDB();

// Handle File Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['upload_file'])) {
    // Validate Anti-CSRF Token
    validateCsrfToken();

    $file = $_FILES['upload_file'];
    if ($file['error'] === UPLOAD_ERR_OK) {
        $filename = basename($file['name']);
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        // STRICT EXTENSION BLOCKLIST (Prevent RCE)
        $blocked_extensions = ['php', 'phtml', 'php3', 'php4', 'php5', 'phps', 'phar', 'inc', 'pl', 'py', 'cgi', 'sh', 'bat', 'exe', 'htaccess'];
        if (in_array($ext, $blocked_extensions)) {
            setFlash('error', "Security Violation: Executable file upload blocked.");
            header("Location: assets.php");
            exit;
        }

        // STRICT EXTENSION WHITELIST
        $allowed_images = ['png', 'jpg', 'jpeg', 'gif', 'svg', 'ico', 'webp'];
        $allowed_docs   = ['pdf', 'doc', 'docx', 'zip'];
        $allowed_videos = ['mp4', 'webm', 'ogg'];

        if (in_array($ext, $allowed_images)) {
            $folder = 'images';
            $type = 'image';
        } elseif (in_array($ext, $allowed_docs)) {
            $folder = 'docs';
            $type = 'document';
        } elseif (in_array($ext, $allowed_videos)) {
            $folder = 'video';
            $type = 'video';
        } else {
            setFlash('error', "Invalid file type format extension '." . e($ext) . "'. Only images, PDFs, and MP4 videos allowed.");
            header("Location: assets.php");
            exit;
        }

        $target_dir = __DIR__ . '/../assets/' . $folder;
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        // Secure Random Hash Filename
        $hash_prefix = bin2hex(random_bytes(6));
        $clean_name = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($filename, PATHINFO_FILENAME));
        $safe_filename = $clean_name . '_' . $hash_prefix . '.' . $ext;

        $target_file = $target_dir . '/' . $safe_filename;
        $rel_path = 'assets/' . $folder . '/' . $safe_filename;

        if (move_uploaded_file($file['tmp_name'], $target_file)) {
            $filesize = filesize($target_file);
            $stmt = $db->prepare("INSERT INTO assets (filename, filepath, filetype, filesize) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE filesize = ?");
            $stmt->execute([$safe_filename, $rel_path, $type, $filesize, $filesize]);
            setFlash('success', "File '" . e($safe_filename) . "' uploaded securely!");
        } else {
            setFlash('error', "Failed to save uploaded file.");
        }
    } else {
        setFlash('error', "Upload error code: " . $file['error']);
    }
    header("Location: assets.php");
    exit;
}

// Handle Asset Deletion
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $assetId = (int)$_GET['id'];
    $stmt = $db->prepare("SELECT * FROM assets WHERE id = ?");
    $stmt->execute([$assetId]);
    $asset = $stmt->fetch();

    if ($asset) {
        $file_on_disk = __DIR__ . '/../' . $asset['filepath'];
        // Path traversal protection check
        if (strpos(realpath($file_on_disk), realpath(__DIR__ . '/../assets/')) === 0 && file_exists($file_on_disk)) {
            unlink($file_on_disk);
        }
        $del = $db->prepare("DELETE FROM assets WHERE id = ?");
        $del->execute([$assetId]);
        setFlash('success', "Asset deleted successfully.");
    }
    header("Location: assets.php");
    exit;
}

// Filter assets by type
$filter = $_GET['type'] ?? 'all';
if (!in_array($filter, ['all', 'image', 'document', 'video'])) {
    $filter = 'all';
}

if ($filter !== 'all') {
    $stmt = $db->prepare("SELECT * FROM assets WHERE filetype = ? ORDER BY uploaded_at DESC");
    $stmt->execute([$filter]);
} else {
    $stmt = $db->query("SELECT * FROM assets ORDER BY uploaded_at DESC");
}
$assets = $stmt->fetchAll();
?>

<div class="space-y-6">
    <!-- Header & Upload Form -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-gray-100 pb-5">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Asset & Media Manager</h1>
            <p class="text-sm text-gray-500 mt-1">Manage images, documents, whitepapers, and videos (Hardened & Protected)</p>
        </div>

        <!-- Upload Form with CSRF Protection -->
        <form method="POST" action="assets.php" enctype="multipart/form-data" class="flex items-center gap-3">
            <?= csrfField() ?>
            <input type="file" name="upload_file" required class="text-xs text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs px-4 py-2 rounded-lg shadow-sm transition flex items-center gap-1.5 shrink-0">
                <i class="fa-solid fa-cloud-arrow-up"></i> Upload Asset
            </button>
        </form>
    </div>

    <!-- Filter Tabs -->
    <div class="flex items-center gap-2 border-b border-gray-200 pb-2 text-sm font-medium">
        <a href="assets.php?type=all" class="px-3 py-1.5 rounded-lg transition <?= $filter === 'all' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-100' ?>">All Assets</a>
        <a href="assets.php?type=image" class="px-3 py-1.5 rounded-lg transition <?= $filter === 'image' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-100' ?>">Images</a>
        <a href="assets.php?type=document" class="px-3 py-1.5 rounded-lg transition <?= $filter === 'document' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-100' ?>">Documents (PDFs)</a>
        <a href="assets.php?type=video" class="px-3 py-1.5 rounded-lg transition <?= $filter === 'video' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-100' ?>">Videos</a>
    </div>

    <!-- Assets Grid -->
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
        <?php foreach ($assets as $a): ?>
            <div class="border border-gray-200 rounded-xl bg-white p-3 flex flex-col justify-between hover:shadow-md transition space-y-2">
                <!-- Preview Area -->
                <div class="h-28 bg-gray-50 rounded-lg flex items-center justify-center overflow-hidden border border-gray-100">
                    <?php if ($a['filetype'] === 'image'): ?>
                        <img src="../<?= e($a['filepath']) ?>" alt="" class="max-h-full max-w-full object-contain">
                    <?php elseif ($a['filetype'] === 'document'): ?>
                        <div class="text-center">
                            <i class="fa-solid fa-file-pdf text-red-500 text-3xl"></i>
                            <span class="text-[10px] text-gray-400 block mt-1 font-mono">PDF</span>
                        </div>
                    <?php elseif ($a['filetype'] === 'video'): ?>
                        <div class="text-center">
                            <i class="fa-solid fa-video text-blue-500 text-3xl"></i>
                            <span class="text-[10px] text-gray-400 block mt-1 font-mono">MP4</span>
                        </div>
                    <?php else: ?>
                        <i class="fa-solid fa-file text-gray-400 text-2xl"></i>
                    <?php endif; ?>
                </div>

                <!-- Asset Info -->
                <div>
                    <span class="text-xs font-semibold text-gray-800 block truncate" title="<?= e($a['filename']) ?>">
                        <?= e($a['filename']) ?>
                    </span>
                    <span class="text-[10px] text-gray-400 block font-mono">
                        <?= round($a['filesize'] / 1024, 1) ?> KB
                    </span>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-between pt-1 border-t border-gray-100">
                    <button onclick="navigator.clipboard.writeText('<?= e($a['filepath']) ?>'); alert('Copied path: <?= e($a['filepath']) ?>');" class="text-[11px] text-blue-600 hover:text-blue-800 font-medium flex items-center gap-1" title="Copy local path">
                        <i class="fa-regular fa-copy"></i> Path
                    </button>
                    <a href="assets.php?action=delete&id=<?= $a['id'] ?>" onclick="return confirm('Are you sure you want to delete this asset?');" class="text-[11px] text-rose-600 hover:text-rose-800 font-medium" title="Delete file">
                        <i class="fa-solid fa-trash"></i>
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
