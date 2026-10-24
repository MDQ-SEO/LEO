<?php
require_once __DIR__ . '/includes/header.php';

$db = getDB();
$id = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("SELECT * FROM pages WHERE id = ?");
$stmt->execute([$id]);
$page = $stmt->fetch();

if (!$page) {
    setFlash('error', "Page not found!");
    header("Location: pages.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate Anti-CSRF Token
    validateCsrfToken();

    $title = trim($_POST['title'] ?? '');
    $meta_description = trim($_POST['meta_description'] ?? '');
    $content_html = $_POST['content_html'] ?? '';
    $status = ($_POST['status'] === 'draft') ? 'draft' : 'published';

    // Save to Database via Prepared Statement
    $update = $db->prepare("UPDATE pages SET title = ?, meta_description = ?, content_html = ?, status = ? WHERE id = ?");
    $update->execute([$title, $meta_description, $content_html, $status, $id]);

    // Sanitize slug to prevent path traversal attack
    $safe_slug = sanitizeSlug($page['slug']);
    $filename = ($safe_slug === 'home') ? 'index.html' : $safe_slug . '.html';
    $filepath = realpath(__DIR__ . '/../') . DIRECTORY_SEPARATOR . $filename;
    
    // Ensure file stays within htdocs root
    if (strpos(realpath($filepath), realpath(__DIR__ . '/../')) === 0 || !file_exists($filepath)) {
        file_put_contents($filepath, $content_html);
    }

    setFlash('success', "Page '" . e($title) . "' updated successfully!");
    header("Location: edit_page.php?id=" . $id);
    exit;
}
?>

<div class="space-y-6 max-w-5xl mx-auto">
    <!-- Header -->
    <div class="flex items-center justify-between border-b border-gray-100 pb-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-gray-400 font-mono mb-1">
                <a href="pages.php" class="hover:text-blue-600">Pages</a> &rarr; <span><?= e($page['slug']) ?></span>
            </div>
            <h1 class="text-2xl font-bold text-gray-800">Edit Page: <?= e($page['title']) ?></h1>
        </div>
        <div class="flex items-center gap-3">
            <a href="pages.php" class="text-xs bg-gray-100 hover:bg-gray-200 text-gray-600 px-3.5 py-2 rounded-lg font-medium">Back to Pages</a>
            <a href="../<?= e($page['slug']) === 'home' ? 'index' : e($page['slug']) ?>.html" target="_blank" class="text-xs bg-blue-50 hover:bg-blue-100 text-blue-700 font-semibold px-3.5 py-2 rounded-lg border border-blue-200 flex items-center gap-1.5">
                <i class="fa-solid fa-external-link"></i> Preview Page
            </a>
        </div>
    </div>

    <!-- Edit Form -->
    <form method="POST" action="edit_page.php?id=<?= $id ?>" class="space-y-6">
        <?= csrfField() ?>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Page Title -->
            <div class="md:col-span-2">
                <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Page Title</label>
                <input type="text" name="title" required value="<?= e($page['title']) ?>" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none">
            </div>

            <!-- Status -->
            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Publishing Status</label>
                <select name="status" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none bg-white">
                    <option value="published" <?= $page['status'] === 'published' ? 'selected' : '' ?>>Published</option>
                    <option value="draft" <?= $page['status'] === 'draft' ? 'selected' : '' ?>>Draft / Hidden</option>
                </select>
            </div>
        </div>

        <!-- Meta Description -->
        <div>
            <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Meta Description (SEO)</label>
            <textarea name="meta_description" rows="2" class="w-full px-4 py-2 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none" placeholder="Enter meta description..."><?= e($page['meta_description']) ?></textarea>
        </div>

        <!-- HTML Content Editor -->
        <div>
            <div class="flex items-center justify-between mb-2">
                <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider">Page HTML Content</label>
                <span class="text-xs text-gray-400 font-mono">Synced live with MySQL & File System</span>
            </div>
            <textarea name="content_html" id="content_html" rows="24" class="w-full p-4 font-mono text-xs bg-slate-900 text-slate-100 border border-slate-700 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none leading-relaxed"><?= e($page['content_html']) ?></textarea>
        </div>

        <!-- Action Bar -->
        <div class="flex items-center justify-end gap-4 border-t border-gray-100 pt-5">
            <a href="pages.php" class="text-sm text-gray-500 hover:text-gray-700">Cancel</a>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm px-6 py-2.5 rounded-xl shadow-lg shadow-blue-600/30 transition flex items-center gap-2">
                <i class="fa-solid fa-floppy-disk"></i> Save Page Changes
            </button>
        </div>

    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
