<?php
require_once __DIR__ . '/config.php';
checkAdminLogin();

$db = getDB();

// Handle deletion (POST handled BEFORE header.php to allow redirect without warning)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrfToken();
    $action = $_POST['action'] ?? '';
    $id = intval($_POST['id'] ?? 0);

    if ($action === 'delete' && $id > 0) {
        $stmt = $db->prepare("SELECT cv_path, cover_letter_path FROM career_submissions WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if ($row) {
            if (!empty($row['cv_path'])) {
                $f1 = __DIR__ . '/../' . $row['cv_path'];
                if (file_exists($f1)) @unlink($f1);
            }
            if (!empty($row['cover_letter_path'])) {
                $f2 = __DIR__ . '/../' . $row['cover_letter_path'];
                if (file_exists($f2)) @unlink($f2);
            }
        }
        $del = $db->prepare("DELETE FROM career_submissions WHERE id = ?");
        $del->execute([$id]);
        setFlash('success', 'Application deleted successfully.');
        header("Location: careers.php");
        exit;
    }
}

// Search & Role Filter
$search = trim($_GET['search'] ?? '');
$filter_role = trim($_GET['role'] ?? '');

$sql = "SELECT * FROM career_submissions WHERE 1=1";
$params = [];

if ($search !== '') {
    $sql .= " AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR phone LIKE ? OR role_applied LIKE ? OR current_company LIKE ?)";
    $term = "%$search%";
    $params = [$term, $term, $term, $term, $term, $term];
}

if ($filter_role !== '') {
    $sql .= " AND role_applied LIKE ?";
    $params[] = "%$filter_role%";
}

$sql .= " ORDER BY created_at DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$applications = $stmt->fetchAll();

$totalCount = $db->query("SELECT COUNT(*) FROM career_submissions")->fetchColumn();

// Include header (starts HTML output)
require_once __DIR__ . '/includes/header.php';
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-gray-100 pb-5">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                <i class="fa-solid fa-user-tie text-blue-600"></i> Job Applications
            </h1>
            <p class="text-sm text-gray-500 mt-1">Review candidate resumes and applications submitted via Careers Form</p>
        </div>
        <div class="text-xs bg-slate-100 text-slate-700 px-3 py-1.5 rounded-lg font-semibold">
            Total Applications: <span class="text-blue-600 font-bold"><?= number_format($totalCount) ?></span>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="bg-gray-50 p-4 rounded-xl border border-gray-200 flex flex-wrap items-center justify-between gap-3">
        <form method="GET" action="careers.php" class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
            <div class="relative">
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search candidate, email, role..." class="pl-9 pr-4 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none w-64 bg-white">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-2.5 text-gray-400 text-sm"></i>
            </div>
            
            <select name="role" class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none bg-white">
                <option value="">All Roles</option>
                <option value="Senior Design" <?= strpos($filter_role, 'Senior') !== false ? 'selected' : '' ?>>Senior Design Engineer</option>
                <option value="Sales" <?= strpos($filter_role, 'Sales') !== false ? 'selected' : '' ?>>Sales & Marketing</option>
                <option value="Human Resources" <?= strpos($filter_role, 'Human') !== false ? 'selected' : '' ?>>Human Resources Manager</option>
                <option value="Design Engineer" <?= $filter_role === 'Design Engineer' ? 'selected' : '' ?>>Design Engineer</option>
                <option value="Project Engineer" <?= strpos($filter_role, 'Project') !== false ? 'selected' : '' ?>>Project Engineer</option>
                <option value="Site Engineer" <?= strpos($filter_role, 'Site') !== false ? 'selected' : '' ?>>Site Engineer</option>
                <option value="Proposal Engineer" <?= strpos($filter_role, 'Proposal') !== false ? 'selected' : '' ?>>Proposal Engineer</option>
            </select>

            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">
                Filter
            </button>
            <?php if ($search || $filter_role): ?>
                <a href="careers.php" class="text-xs text-gray-500 hover:text-gray-700 px-2 font-medium">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Table -->
    <div class="border border-gray-200 rounded-xl overflow-x-auto shadow-sm bg-white">
        <table class="w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-slate-50 text-slate-700 font-semibold text-xs uppercase tracking-wider text-left">
                <tr>
                    <th class="py-3 px-4">Candidate Details</th>
                    <th class="py-3 px-4">Applied Role</th>
                    <th class="py-3 px-4">Current Company</th>
                    <th class="py-3 px-3">Resume / CV</th>
                    <th class="py-3 px-3">Applied On</th>
                    <th class="py-3 px-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if (empty($applications)): ?>
                    <tr>
                        <td colspan="6" class="py-12 text-center text-gray-400">
                            <i class="fa-solid fa-user-graduate text-4xl mb-3 block text-gray-300"></i>
                            No career applications found.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($applications as $app): ?>
                        <tr class="hover:bg-slate-50/70 transition">
                            <!-- Candidate Details -->
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <div class="font-semibold text-gray-900"><?= htmlspecialchars($app['first_name'] . ' ' . $app['last_name']) ?></div>
                                <div class="text-xs text-gray-500 flex items-center gap-1.5 mt-0.5">
                                    <i class="fa-regular fa-envelope text-slate-400"></i>
                                    <a href="mailto:<?= htmlspecialchars($app['email']) ?>" class="hover:underline text-blue-600 font-medium"><?= htmlspecialchars($app['email']) ?></a>
                                </div>
                                <div class="text-xs text-gray-500 flex items-center gap-1.5 mt-0.5">
                                    <i class="fa-solid fa-phone text-slate-400"></i> <?= htmlspecialchars($app['phone']) ?>
                                </div>
                            </td>

                            <!-- Role -->
                            <td class="py-3.5 px-4">
                                <span class="inline-block px-2.5 py-1 bg-blue-50 text-blue-800 rounded-lg text-xs font-semibold">
                                    <?= htmlspecialchars($app['role_applied']) ?>
                                </span>
                            </td>

                            <!-- Current Company -->
                            <td class="py-3.5 px-4 text-xs text-gray-600">
                                <?= htmlspecialchars($app['current_company'] ?: 'Fresher / Not specified') ?>
                            </td>

                            <!-- Resume -->
                            <td class="py-3.5 px-3 text-xs whitespace-nowrap">
                                <?php if (!empty($app['cv_path'])): ?>
                                    <a href="../<?= htmlspecialchars($app['cv_path']) ?>" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 rounded-lg font-semibold transition" title="Download CV">
                                        <i class="fa-solid fa-file-pdf text-red-500"></i> Download CV
                                    </a>
                                <?php else: ?>
                                    <span class="text-gray-400">No CV</span>
                                <?php endif; ?>

                                <?php if (!empty($app['cover_letter_path'])): ?>
                                    <a href="../<?= htmlspecialchars($app['cover_letter_path']) ?>" target="_blank" class="inline-flex items-center gap-1 px-2 py-1 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md font-medium text-[11px] ml-1 transition" title="Cover Letter">
                                        <i class="fa-solid fa-file-lines"></i>
                                    </a>
                                <?php endif; ?>
                            </td>

                            <!-- Date -->
                            <td class="py-3.5 px-3 text-xs text-gray-500 whitespace-nowrap">
                                <?= date('d M Y', strtotime($app['created_at'])) ?>
                            </td>

                            <!-- Actions -->
                            <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-2">
                                    <button onclick="viewModal(<?= htmlspecialchars(json_encode($app)) ?>)" class="inline-flex items-center gap-1.5 text-xs bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded-lg font-semibold shadow-sm transition" title="View Full Details">
                                        <i class="fa-solid fa-eye"></i> View
                                    </button>
                                    <form method="POST" action="careers.php" class="inline" onsubmit="return confirm('Are you sure you want to delete this applicant?');">
                                        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $app['id'] ?>">
                                        <button type="submit" class="inline-flex items-center gap-1 text-xs bg-rose-50 hover:bg-rose-600 hover:text-white text-rose-600 px-2.5 py-1.5 rounded-lg font-semibold transition" title="Delete">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Details Modal -->
<div id="detailModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-xl w-full p-6 shadow-2xl relative space-y-4">
        <div class="flex items-center justify-between border-b pb-3">
            <h3 class="text-lg font-bold text-gray-900" id="m_role">Candidate Application</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
        </div>
        <div class="space-y-3 text-sm">
            <div class="grid grid-cols-2 gap-3 bg-slate-50 p-4 rounded-xl text-xs">
                <div><span class="text-gray-500 font-semibold">Candidate:</span> <span id="m_name" class="font-bold text-gray-800 block text-sm"></span></div>
                <div><span class="text-gray-500 font-semibold">Applied On:</span> <span id="m_date" class="text-gray-700 block"></span></div>
                <div><span class="text-gray-500 font-semibold">Email:</span> <a id="m_email_link" href="#" class="text-blue-600 underline block"></a></div>
                <div><span class="text-gray-500 font-semibold">Phone:</span> <span id="m_phone" class="text-gray-700 block"></span></div>
                <div class="col-span-2"><span class="text-gray-500 font-semibold">Current Company & Designation:</span> <span id="m_company" class="text-gray-800 block font-medium"></span></div>
            </div>
            
            <div class="pt-2 flex flex-wrap gap-3">
                <a id="m_cv_link" href="#" target="_blank" class="inline-flex items-center gap-2 bg-blue-600 text-white hover:bg-blue-700 px-4 py-2.5 rounded-xl text-sm font-semibold shadow-sm transition">
                    <i class="fa-solid fa-file-pdf"></i> Download Resume / CV
                </a>
                <a id="m_cl_link" href="#" target="_blank" class="inline-flex items-center gap-2 bg-slate-100 text-slate-700 hover:bg-slate-200 px-4 py-2.5 rounded-xl text-sm font-semibold transition hidden">
                    <i class="fa-solid fa-file-lines"></i> View Cover Letter
                </a>
            </div>
        </div>
        <div class="flex justify-end pt-3 border-t">
            <button onclick="closeModal()" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-5 py-2 rounded-xl text-sm font-semibold">Close</button>
        </div>
    </div>
</div>

<script>
function viewModal(data) {
    document.getElementById('m_role').textContent = data.role_applied || 'Candidate Application';
    document.getElementById('m_name').textContent = (data.first_name + ' ' + (data.last_name || '')).trim();
    document.getElementById('m_email_link').textContent = data.email;
    document.getElementById('m_email_link').href = 'mailto:' + data.email;
    document.getElementById('m_phone').textContent = data.phone;
    document.getElementById('m_date').textContent = data.created_at;
    document.getElementById('m_company').textContent = data.current_company || 'Not specified / Fresher';
    
    var cvLink = document.getElementById('m_cv_link');
    if (data.cv_path) {
        cvLink.href = '../' + data.cv_path;
        cvLink.classList.remove('hidden');
    } else {
        cvLink.classList.add('hidden');
    }

    var clLink = document.getElementById('m_cl_link');
    if (data.cover_letter_path) {
        clLink.href = '../' + data.cover_letter_path;
        clLink.classList.remove('hidden');
    } else {
        clLink.classList.add('hidden');
    }

    document.getElementById('detailModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('detailModal').classList.add('hidden');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
