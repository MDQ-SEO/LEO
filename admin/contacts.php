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
        $stmt = $db->prepare("SELECT attachment_path FROM contact_submissions WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if ($row && !empty($row['attachment_path'])) {
            $f = __DIR__ . '/../' . $row['attachment_path'];
            if (file_exists($f)) {
                @unlink($f);
            }
        }
        $del = $db->prepare("DELETE FROM contact_submissions WHERE id = ?");
        $del->execute([$id]);
        setFlash('success', 'Inquiry deleted successfully.');
        header("Location: contacts.php");
        exit;
    }
}

// Search
$search = trim($_GET['search'] ?? '');
$sql = "SELECT * FROM contact_submissions WHERE 1=1";
$params = [];

if ($search !== '') {
    $sql .= " AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR phone LIKE ? OR subject LIKE ? OR message LIKE ?)";
    $term = "%$search%";
    $params = [$term, $term, $term, $term, $term, $term];
}

$sql .= " ORDER BY created_at DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$inquiries = $stmt->fetchAll();

$totalCount = $db->query("SELECT COUNT(*) FROM contact_submissions")->fetchColumn();

// Include header (starts HTML output)
require_once __DIR__ . '/includes/header.php';
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-gray-100 pb-5">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                <i class="fa-solid fa-envelope-open-text text-blue-600"></i> Contact Form Submissions
            </h1>
            <p class="text-sm text-gray-500 mt-1">All inquiries received from the website contact form</p>
        </div>
        <div class="text-xs bg-slate-100 text-slate-700 px-3 py-1.5 rounded-lg font-semibold">
            Total Submissions: <span class="text-blue-600 font-bold"><?= number_format($totalCount) ?></span>
        </div>
    </div>

    <!-- Search Bar -->
    <div class="bg-gray-50 p-4 rounded-xl border border-gray-200 flex flex-wrap items-center justify-between gap-3">
        <form method="GET" action="contacts.php" class="flex items-center gap-2 w-full sm:w-auto">
            <div class="relative">
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search name, email, subject..." class="pl-9 pr-4 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none w-72 bg-white">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-2.5 text-gray-400 text-sm"></i>
            </div>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">
                Search
            </button>
            <?php if ($search): ?>
                <a href="contacts.php" class="text-xs text-gray-500 hover:text-gray-700 px-2 font-medium">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Submissions Table -->
    <div class="border border-gray-200 rounded-xl overflow-x-auto shadow-sm bg-white">
        <table class="w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-slate-50 text-slate-700 font-semibold text-xs uppercase tracking-wider text-left">
                <tr>
                    <th class="py-3 px-4">Sender Details</th>
                    <th class="py-3 px-4">Subject & Message</th>
                    <th class="py-3 px-3">Attachment</th>
                    <th class="py-3 px-3">Received On</th>
                    <th class="py-3 px-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if (empty($inquiries)): ?>
                    <tr>
                        <td colspan="5" class="py-12 text-center text-gray-400">
                            <i class="fa-regular fa-envelope-open text-4xl mb-3 block text-gray-300"></i>
                            No contact submissions found.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($inquiries as $inq): ?>
                        <tr class="hover:bg-slate-50/70 transition">
                            <!-- Sender -->
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <div class="font-semibold text-gray-900"><?= htmlspecialchars($inq['first_name'] . ' ' . $inq['last_name']) ?></div>
                                <div class="text-xs text-gray-500 flex items-center gap-1.5 mt-0.5">
                                    <i class="fa-regular fa-envelope text-slate-400"></i>
                                    <a href="mailto:<?= htmlspecialchars($inq['email']) ?>" class="hover:underline text-blue-600 font-medium"><?= htmlspecialchars($inq['email']) ?></a>
                                </div>
                                <div class="text-xs text-gray-500 flex items-center gap-1.5 mt-0.5">
                                    <i class="fa-solid fa-phone text-slate-400"></i> <?= htmlspecialchars($inq['phone']) ?>
                                </div>
                            </td>

                            <!-- Subject & Message Preview -->
                            <td class="py-3.5 px-4 max-w-sm">
                                <div class="font-semibold text-gray-800"><?= htmlspecialchars($inq['subject'] ?: 'No Subject') ?></div>
                                <div class="text-xs text-gray-600 line-clamp-2 mt-0.5"><?= htmlspecialchars($inq['message']) ?></div>
                            </td>

                            <!-- Attachment -->
                            <td class="py-3.5 px-3 text-xs whitespace-nowrap">
                                <?php if (!empty($inq['attachment_path'])): ?>
                                    <a href="../<?= htmlspecialchars($inq['attachment_path']) ?>" target="_blank" class="inline-flex items-center gap-1.5 px-2.5 py-1.5 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-lg font-medium transition" title="<?= htmlspecialchars($inq['attachment_name']) ?>">
                                        <i class="fa-solid fa-paperclip"></i>
                                        <span class="max-w-[120px] truncate"><?= htmlspecialchars($inq['attachment_name'] ?: 'File') ?></span>
                                    </a>
                                <?php else: ?>
                                    <span class="text-gray-400">—</span>
                                <?php endif; ?>
                            </td>

                            <!-- Date -->
                            <td class="py-3.5 px-3 text-xs text-gray-500 whitespace-nowrap">
                                <?= date('d M Y, h:i A', strtotime($inq['created_at'])) ?>
                            </td>

                            <!-- Actions -->
                            <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-2">
                                    <button onclick="viewModal(<?= htmlspecialchars(json_encode($inq)) ?>)" class="inline-flex items-center gap-1.5 text-xs bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded-lg font-semibold shadow-sm transition" title="View Full Details">
                                        <i class="fa-solid fa-eye"></i> View
                                    </button>
                                    <form method="POST" action="contacts.php" class="inline" onsubmit="return confirm('Are you sure you want to delete this submission?');">
                                        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $inq['id'] ?>">
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
            <h3 class="text-lg font-bold text-gray-900" id="m_subject">Inquiry Details</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
        </div>
        <div class="space-y-3 text-sm">
            <div class="grid grid-cols-2 gap-3 bg-slate-50 p-3 rounded-lg text-xs">
                <div><span class="text-gray-500 font-semibold">From:</span> <span id="m_name" class="font-bold text-gray-800"></span></div>
                <div><span class="text-gray-500 font-semibold">Date:</span> <span id="m_date" class="text-gray-700"></span></div>
                <div><span class="text-gray-500 font-semibold">Email:</span> <a id="m_email_link" href="#" class="text-blue-600 underline"></a></div>
                <div><span class="text-gray-500 font-semibold">Phone:</span> <span id="m_phone" class="text-gray-700"></span></div>
            </div>
            <div>
                <label class="text-xs font-bold text-gray-500 uppercase tracking-wider block mb-1">Message:</label>
                <div id="m_message" class="bg-gray-50 p-4 rounded-xl border border-gray-200 text-gray-700 whitespace-pre-wrap leading-relaxed max-h-60 overflow-y-auto"></div>
            </div>
            <div id="m_attachment_wrap" class="hidden">
                <label class="text-xs font-bold text-gray-500 uppercase tracking-wider block mb-1">Attachment:</label>
                <a id="m_attachment_link" href="#" target="_blank" class="inline-flex items-center gap-2 bg-blue-50 text-blue-700 hover:bg-blue-100 px-4 py-2 rounded-lg text-sm font-semibold transition">
                    <i class="fa-solid fa-download"></i> <span id="m_attachment_name">Download Attachment</span>
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
    document.getElementById('m_subject').textContent = data.subject || 'Inquiry Details';
    document.getElementById('m_name').textContent = (data.first_name + ' ' + (data.last_name || '')).trim();
    document.getElementById('m_email_link').textContent = data.email;
    document.getElementById('m_email_link').href = 'mailto:' + data.email;
    document.getElementById('m_phone').textContent = data.phone;
    document.getElementById('m_date').textContent = data.created_at;
    document.getElementById('m_message').textContent = data.message;
    
    var attachWrap = document.getElementById('m_attachment_wrap');
    if (data.attachment_path) {
        attachWrap.classList.remove('hidden');
        document.getElementById('m_attachment_link').href = '../' + data.attachment_path;
        document.getElementById('m_attachment_name').textContent = data.attachment_name || 'Download File';
    } else {
        attachWrap.classList.add('hidden');
    }
    document.getElementById('detailModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('detailModal').classList.add('hidden');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
