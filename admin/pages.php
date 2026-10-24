<?php
require_once __DIR__ . '/includes/header.php';

$db = getDB();

// Handle Status Toggle
if (isset($_GET['action']) && $_GET['action'] === 'toggle_status' && isset($_GET['id'])) {
    $pageId = (int)$_GET['id'];
    $stmt = $db->prepare("SELECT status FROM pages WHERE id = ?");
    $stmt->execute([$pageId]);
    $p = $stmt->fetch();
    if ($p) {
        $newStatus = ($p['status'] === 'published') ? 'draft' : 'published';
        $update = $db->prepare("UPDATE pages SET status = ? WHERE id = ?");
        $update->execute([$newStatus, $pageId]);
        setFlash('success', "Page status updated to " . ucfirst($newStatus));
    }
    header("Location: pages.php");
    exit;
}

// Fetch all pages
$search = trim($_GET['search'] ?? '');
if ($search) {
    $stmt = $db->prepare("SELECT * FROM pages WHERE title LIKE ? OR slug LIKE ? ORDER BY title ASC");
    $stmt->execute(["%$search%", "%$search%"]);
} else {
    $stmt = $db->query("SELECT * FROM pages ORDER BY title ASC");
}
$pages = $stmt->fetchAll();
?>

<div class="space-y-6">
    <!-- Title & Search Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-gray-100 pb-5">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Manage Website Pages</h1>
            <p class="text-sm text-gray-500 mt-1">Select any page to edit titles, meta tags, and HTML content</p>
        </div>

        <form method="GET" action="pages.php" class="flex items-center gap-2">
            <div class="relative">
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search pages..." class="pl-9 pr-4 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none w-64">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-2.5 text-gray-400 text-sm"></i>
            </div>
            <?php if ($search): ?>
                <a href="pages.php" class="text-xs bg-gray-100 hover:bg-gray-200 text-gray-600 px-3 py-2 rounded-lg">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Pages Table -->
    <div class="overflow-x-auto border border-gray-200 rounded-xl shadow-sm bg-white">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200 text-xs font-bold text-gray-500 uppercase tracking-wider">
                    <th class="py-3.5 px-4">Page Title</th>
                    <th class="py-3.5 px-4">URL Route</th>
                    <th class="py-3.5 px-4">Status</th>
                    <th class="py-3.5 px-4">Last Updated</th>
                    <th class="py-3.5 px-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 text-sm">
                <?php if (empty($pages)): ?>
                    <tr>
                        <td colspan="5" class="py-8 text-center text-gray-400">No pages found matching your search query.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($pages as $p): ?>
                        <tr class="hover:bg-blue-50/40 transition">
                            <td class="py-3.5 px-4 font-semibold text-gray-800">
                                <a href="edit_page.php?id=<?= $p['id'] ?>" class="hover:text-blue-600">
                                    <?= htmlspecialchars($p['title']) ?>
                                </a>
                            </td>
                            <td class="py-3.5 px-4 font-mono text-xs text-gray-500">
                                /<?= htmlspecialchars($p['slug']) === 'home' ? '' : htmlspecialchars($p['slug']) ?>.html
                            </td>
                            <td class="py-3.5 px-4">
                                <a href="pages.php?action=toggle_status&id=<?= $p['id'] ?>" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium <?= $p['status'] === 'published' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' ?>">
                                    <span class="w-1.5 h-1.5 rounded-full <?= $p['status'] === 'published' ? 'bg-emerald-500' : 'bg-amber-500' ?>"></span>
                                    <?= ucfirst($p['status']) ?>
                                </a>
                            </td>
                            <td class="py-3.5 px-4 text-xs text-gray-500">
                                <?= date('M d, Y H:i', strtotime($p['updated_at'])) ?>
                            </td>
                            <td class="py-3.5 px-4 text-right space-x-2">
                                <a href="edit_page.php?id=<?= $p['id'] ?>" class="inline-flex items-center gap-1 bg-blue-50 hover:bg-blue-100 text-blue-700 text-xs font-semibold px-3 py-1.5 rounded-lg border border-blue-200 transition">
                                    <i class="fa-solid fa-pen"></i> Edit
                                </a>
                                <a href="../<?= htmlspecialchars($p['slug']) === 'home' ? 'index' : htmlspecialchars($p['slug']) ?>.html" target="_blank" class="inline-flex items-center gap-1 bg-gray-50 hover:bg-gray-100 text-gray-600 text-xs font-medium px-2.5 py-1.5 rounded-lg border border-gray-200 transition">
                                    <i class="fa-solid fa-arrow-up-right-from-square"></i> Preview
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
