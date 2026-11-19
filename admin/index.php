<?php
require_once __DIR__ . '/includes/header.php';

$db = getDB();

// Fetch metrics
$totalPages = $db->query("SELECT COUNT(*) FROM pages")->fetchColumn();
$totalAssets = $db->query("SELECT COUNT(*) FROM assets")->fetchColumn();

// Fetch submission metrics
$totalInquiries = 0;
$recentInquiries = [];
try {
    $totalInquiries = $db->query("SELECT COUNT(*) FROM contact_submissions")->fetchColumn();
    $recentInquiries = $db->query("SELECT * FROM contact_submissions ORDER BY created_at DESC LIMIT 5")->fetchAll();
} catch (Exception $e) {}

$totalCareers = 0;
$recentCareers = [];
try {
    $totalCareers = $db->query("SELECT COUNT(*) FROM career_submissions")->fetchColumn();
    $recentCareers = $db->query("SELECT * FROM career_submissions ORDER BY created_at DESC LIMIT 5")->fetchAll();
} catch (Exception $e) {}

// Fetch recent updated pages
$recentPages = $db->query("SELECT * FROM pages ORDER BY updated_at DESC LIMIT 5")->fetchAll();
?>

<div class="space-y-8">
    <!-- Header Banner -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-gray-100 pb-5">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Dashboard Overview</h1>
            <p class="text-sm text-gray-500 mt-1">Manage content, customer inquiries, and job applications for Leo Surface Coatings</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="contacts.php" class="bg-cyan-600 hover:bg-cyan-700 text-white text-xs font-semibold px-3.5 py-2 rounded-lg shadow-sm transition flex items-center gap-1.5">
                <i class="fa-solid fa-envelope-open-text"></i> Inquiries (<?= $totalInquiries ?>)
            </a>
            <a href="careers.php" class="bg-purple-600 hover:bg-purple-700 text-white text-xs font-semibold px-3.5 py-2 rounded-lg shadow-sm transition flex items-center gap-1.5">
                <i class="fa-solid fa-user-tie"></i> Applications (<?= $totalCareers ?>)
            </a>
        </div>
    </div>

    <!-- Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <!-- Contact Inquiries Card -->
        <a href="contacts.php" class="block bg-cyan-50/50 hover:bg-cyan-50 border border-cyan-100 rounded-xl p-5 transition group">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold text-cyan-700 uppercase tracking-wider">Contact Inquiries</span>
                    <div class="text-3xl font-extrabold text-slate-800 mt-1"><?= number_format($totalInquiries) ?></div>
                    <span class="text-xs text-cyan-600 font-semibold block mt-1">Form Submissions</span>
                </div>
                <div class="w-12 h-12 bg-cyan-600 text-white rounded-xl flex items-center justify-center text-xl shadow-md group-hover:scale-105 transition">
                    <i class="fa-solid fa-envelope-open-text"></i>
                </div>
            </div>
        </a>

        <!-- Career Applications Card -->
        <a href="careers.php" class="block bg-purple-50/50 hover:bg-purple-50 border border-purple-100 rounded-xl p-5 transition group">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold text-purple-700 uppercase tracking-wider">Job Applications</span>
                    <div class="text-3xl font-extrabold text-slate-800 mt-1"><?= number_format($totalCareers) ?></div>
                    <span class="text-xs text-purple-600 font-semibold block mt-1">Resumes & Candidates</span>
                </div>
                <div class="w-12 h-12 bg-purple-600 text-white rounded-xl flex items-center justify-center text-xl shadow-md group-hover:scale-105 transition">
                    <i class="fa-solid fa-user-tie"></i>
                </div>
            </div>
        </a>

        <!-- Total Pages Card -->
        <a href="pages.php" class="block bg-blue-50/50 hover:bg-blue-50 border border-blue-100 rounded-xl p-5 transition group">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold text-blue-600 uppercase tracking-wider">Total Pages</span>
                    <div class="text-3xl font-extrabold text-slate-800 mt-1"><?= number_format($totalPages) ?></div>
                    <span class="text-xs text-slate-500 block mt-1">Seeded & Editable</span>
                </div>
                <div class="w-12 h-12 bg-blue-600 text-white rounded-xl flex items-center justify-center text-xl shadow-md group-hover:scale-105 transition">
                    <i class="fa-solid fa-file-lines"></i>
                </div>
            </div>
        </a>

        <!-- Total Assets Card -->
        <a href="assets.php" class="block bg-emerald-50/50 hover:bg-emerald-50 border border-emerald-100 rounded-xl p-5 transition group">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold text-emerald-600 uppercase tracking-wider">Total Assets</span>
                    <div class="text-3xl font-extrabold text-slate-800 mt-1"><?= number_format($totalAssets) ?></div>
                    <span class="text-xs text-slate-500 block mt-1">Media Files in DB</span>
                </div>
                <div class="w-12 h-12 bg-emerald-600 text-white rounded-xl flex items-center justify-center text-xl shadow-md group-hover:scale-105 transition">
                    <i class="fa-solid fa-photo-film"></i>
                </div>
            </div>
        </a>
    </div>

    <!-- Recent Submissions Split Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        
        <!-- Latest Inquiries -->
        <div class="border border-gray-200 rounded-xl p-5 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-gray-100 pb-3 mb-4">
                <h2 class="font-bold text-gray-800 flex items-center gap-2">
                    <i class="fa-solid fa-envelope-open-text text-cyan-600"></i> Recent Inquiries
                </h2>
                <a href="contacts.php" class="text-xs font-semibold text-cyan-600 hover:text-cyan-800">View All (<?= $totalInquiries ?>) &rarr;</a>
            </div>
            <div class="divide-y divide-gray-100">
                <?php if (empty($recentInquiries)): ?>
                    <p class="text-xs text-gray-400 py-4 text-center">No inquiries received yet.</p>
                <?php else: ?>
                    <?php foreach ($recentInquiries as $inq): ?>
                        <div class="py-3 flex items-center justify-between">
                            <div class="max-w-[70%]">
                                <div class="font-semibold text-sm text-gray-800 truncate">
                                    <?= htmlspecialchars($inq['first_name'] . ' ' . $inq['last_name']) ?>
                                    <span class="text-xs font-normal text-gray-400">· <?= htmlspecialchars($inq['subject']) ?></span>
                                </div>
                                <span class="text-xs text-gray-500 block truncate"><?= htmlspecialchars($inq['email']) ?> · <?= htmlspecialchars($inq['phone']) ?></span>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="text-xs text-gray-400">
                                    <?= date('d M', strtotime($inq['created_at'])) ?>
                                </span>
                                <a href="contacts.php" class="text-xs bg-cyan-50 text-cyan-700 hover:bg-cyan-100 px-2.5 py-1 rounded-md font-semibold">View</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Latest Job Applications -->
        <div class="border border-gray-200 rounded-xl p-5 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-gray-100 pb-3 mb-4">
                <h2 class="font-bold text-gray-800 flex items-center gap-2">
                    <i class="fa-solid fa-user-tie text-purple-600"></i> Recent Applications
                </h2>
                <a href="careers.php" class="text-xs font-semibold text-purple-600 hover:text-purple-800">View All (<?= $totalCareers ?>) &rarr;</a>
            </div>
            <div class="divide-y divide-gray-100">
                <?php if (empty($recentCareers)): ?>
                    <p class="text-xs text-gray-400 py-4 text-center">No applications received yet.</p>
                <?php else: ?>
                    <?php foreach ($recentCareers as $c): ?>
                        <div class="py-3 flex items-center justify-between">
                            <div class="max-w-[70%]">
                                <div class="font-semibold text-sm text-gray-800 truncate">
                                    <?= htmlspecialchars($c['first_name'] . ' ' . $c['last_name']) ?>
                                </div>
                                <span class="text-xs text-purple-700 font-medium block truncate"><?= htmlspecialchars($c['role_applied']) ?></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <?php if (!empty($c['cv_path'])): ?>
                                    <a href="../<?= htmlspecialchars($c['cv_path']) ?>" target="_blank" class="text-xs bg-purple-50 text-purple-700 hover:bg-purple-100 px-2.5 py-1 rounded-md font-semibold" title="CV">
                                        <i class="fa-solid fa-download"></i> CV
                                    </a>
                                <?php endif; ?>
                                <a href="careers.php" class="text-xs text-blue-600 hover:text-blue-800 font-medium">View</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <!-- Content Split Grid (Pages & Assets) -->
    <div class="border border-gray-200 rounded-xl p-5 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-gray-100 pb-3 mb-4">
            <h2 class="font-bold text-gray-800 flex items-center gap-2">
                <i class="fa-solid fa-file-lines text-blue-600"></i> Quick Site Pages Access
            </h2>
            <a href="pages.php" class="text-xs font-semibold text-blue-600 hover:text-blue-800">Manage All Pages &rarr;</a>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            <?php foreach ($recentPages as $p): ?>
                <?php 
                    $cleanTitle = html_entity_decode($p['title'] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
                ?>
                <div class="p-3 border border-gray-100 rounded-lg hover:border-blue-200 hover:bg-blue-50/20 transition flex items-center justify-between gap-3 min-w-0 bg-slate-50/40">
                    <div class="min-w-0 flex-1">
                        <div class="font-semibold text-xs text-gray-800 truncate" title="<?= htmlspecialchars($cleanTitle) ?>"><?= htmlspecialchars($cleanTitle) ?></div>
                        <div class="text-[11px] text-gray-400 font-mono truncate">/<?= htmlspecialchars($p['slug']) ?>.html</div>
                    </div>
                    <a href="edit_page.php?id=<?= $p['id'] ?>" class="text-[11px] font-medium bg-white border border-gray-200 hover:bg-blue-600 hover:text-white hover:border-blue-600 text-slate-700 px-2.5 py-1 rounded transition shrink-0 shadow-sm">Edit</a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
