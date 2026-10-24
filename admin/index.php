<?php
require_once __DIR__ . '/includes/header.php';

$db = getDB();

// Fetch metrics
$totalPages = $db->query("SELECT COUNT(*) FROM pages")->fetchColumn();
$totalAssets = $db->query("SELECT COUNT(*) FROM assets")->fetchColumn();
$totalImages = $db->query("SELECT COUNT(*) FROM assets WHERE filetype = 'image'")->fetchColumn();
$totalDocs = $db->query("SELECT COUNT(*) FROM assets WHERE filetype = 'document'")->fetchColumn();

// Fetch recent updated pages
$recentPages = $db->query("SELECT * FROM pages ORDER BY updated_at DESC LIMIT 5")->fetchAll();

// Fetch recent assets
$recentAssets = $db->query("SELECT * FROM assets ORDER BY uploaded_at DESC LIMIT 6")->fetchAll();
?>

<div class="space-y-8">
    <!-- Header Banner -->
    <div class="flex items-center justify-between border-b border-gray-100 pb-5">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Dashboard Overview</h1>
            <p class="text-sm text-gray-500 mt-1">Manage content, pages, and media assets for Leo Surface Coatings</p>
        </div>
        <a href="pages.php" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2.5 rounded-lg shadow-sm transition flex items-center gap-2">
            <i class="fa-solid fa-pen-to-square"></i> Edit Pages
        </a>
    </div>

    <!-- Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <div class="bg-blue-50/50 border border-blue-100 rounded-xl p-5 flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-blue-600 uppercase tracking-wider">Total Pages</span>
                <div class="text-3xl font-extrabold text-slate-800 mt-1"><?= number_format($totalPages) ?></div>
                <span class="text-xs text-slate-500 block mt-1">Seeded & Editable</span>
            </div>
            <div class="w-12 h-12 bg-blue-600 text-white rounded-xl flex items-center justify-center text-xl shadow-md">
                <i class="fa-solid fa-file-lines"></i>
            </div>
        </div>

        <div class="bg-emerald-50/50 border border-emerald-100 rounded-xl p-5 flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-emerald-600 uppercase tracking-wider">Total Assets</span>
                <div class="text-3xl font-extrabold text-slate-800 mt-1"><?= number_format($totalAssets) ?></div>
                <span class="text-xs text-slate-500 block mt-1">Files in DB</span>
            </div>
            <div class="w-12 h-12 bg-emerald-600 text-white rounded-xl flex items-center justify-center text-xl shadow-md">
                <i class="fa-solid fa-photo-film"></i>
            </div>
        </div>

        <div class="bg-amber-50/50 border border-amber-100 rounded-xl p-5 flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-amber-600 uppercase tracking-wider">Images & Graphics</span>
                <div class="text-3xl font-extrabold text-slate-800 mt-1"><?= number_format($totalImages) ?></div>
                <span class="text-xs text-slate-500 block mt-1">Photos & Logos</span>
            </div>
            <div class="w-12 h-12 bg-amber-600 text-white rounded-xl flex items-center justify-center text-xl shadow-md">
                <i class="fa-solid fa-image"></i>
            </div>
        </div>

        <div class="bg-purple-50/50 border border-purple-100 rounded-xl p-5 flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-purple-600 uppercase tracking-wider">PDF Whitepapers</span>
                <div class="text-3xl font-extrabold text-slate-800 mt-1"><?= number_format($totalDocs) ?></div>
                <span class="text-xs text-slate-500 block mt-1">Brochures & Manuals</span>
            </div>
            <div class="w-12 h-12 bg-purple-600 text-white rounded-xl flex items-center justify-center text-xl shadow-md">
                <i class="fa-solid fa-file-pdf"></i>
            </div>
        </div>
    </div>

    <!-- Content Split Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        
        <!-- Recently Updated Pages -->
        <div class="border border-gray-200 rounded-xl p-5 bg-white">
            <div class="flex items-center justify-between border-b border-gray-100 pb-3 mb-4">
                <h2 class="font-bold text-gray-800 flex items-center gap-2">
                    <i class="fa-solid fa-clock-rotate-left text-blue-600"></i> Recently Updated Pages
                </h2>
                <a href="pages.php" class="text-xs font-semibold text-blue-600 hover:text-blue-800">View All Pages &rarr;</a>
            </div>
            <div class="divide-y divide-gray-100">
                <?php foreach ($recentPages as $p): ?>
                    <div class="py-3 flex items-center justify-between">
                        <div>
                            <a href="edit_page.php?id=<?= $p['id'] ?>" class="font-semibold text-sm text-gray-800 hover:text-blue-600 transition">
                                <?= htmlspecialchars($p['title']) ?>
                            </a>
                            <span class="text-xs text-gray-400 block font-mono">/<?= htmlspecialchars($p['slug']) ?>.html</span>
                        </div>
                        <a href="edit_page.php?id=<?= $p['id'] ?>" class="text-xs bg-slate-100 hover:bg-blue-50 text-slate-700 hover:text-blue-600 font-medium px-3 py-1.5 rounded-lg border border-slate-200 transition">
                            Edit Content
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Quick Asset Overview -->
        <div class="border border-gray-200 rounded-xl p-5 bg-white">
            <div class="flex items-center justify-between border-b border-gray-100 pb-3 mb-4">
                <h2 class="font-bold text-gray-800 flex items-center gap-2">
                    <i class="fa-solid fa-images text-emerald-600"></i> Quick Asset Gallery
                </h2>
                <a href="assets.php" class="text-xs font-semibold text-emerald-600 hover:text-emerald-800">Asset Manager &rarr;</a>
            </div>
            <div class="grid grid-cols-3 gap-3">
                <?php foreach ($recentAssets as $a): ?>
                    <div class="border border-gray-100 rounded-lg p-2 text-center bg-gray-50 flex flex-col justify-between h-28 overflow-hidden">
                        <?php if ($a['filetype'] === 'image'): ?>
                            <div class="h-16 flex items-center justify-center overflow-hidden rounded">
                                <img src="../<?= htmlspecialchars($a['filepath']) ?>" alt="" class="max-h-full max-w-full object-contain">
                            </div>
                        <?php else: ?>
                            <div class="h-16 flex items-center justify-center text-gray-400 text-2xl">
                                <i class="fa-solid <?= $a['filetype'] === 'document' ? 'fa-file-pdf text-red-500' : ($a['filetype'] === 'video' ? 'fa-video text-blue-500' : 'fa-file-code text-gray-500') ?>"></i>
                            </div>
                        <?php endif; ?>
                        <span class="text-[10px] text-gray-600 truncate font-mono block px-1"><?= htmlspecialchars($a['filename']) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
