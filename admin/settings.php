<?php
require_once __DIR__ . '/includes/header.php';

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate Anti-CSRF Token
    validateCsrfToken();

    $settings = $_POST['settings'] ?? [];
    foreach ($settings as $key => $val) {
        $safe_key = preg_replace('/[^a-z0-9_]/', '', strtolower($key));
        $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->execute([$safe_key, trim($val), trim($val)]);
    }
    setFlash('success', "Site settings saved successfully!");
    header("Location: settings.php");
    exit;
}

// Fetch all settings
$rows = $db->query("SELECT * FROM settings")->fetchAll();
$s = [];
foreach ($rows as $r) {
    $s[$r['setting_key']] = $r['setting_value'];
}
?>

<div class="space-y-6 max-w-4xl mx-auto">
    <!-- Header -->
    <div class="border-b border-gray-100 pb-4">
        <h1 class="text-2xl font-bold text-gray-800">Site Settings</h1>
        <p class="text-sm text-gray-500 mt-1">Configure global business contact details and social links</p>
    </div>

    <!-- Form -->
    <form method="POST" action="settings.php" class="space-y-6 bg-white border border-gray-200 rounded-xl p-6 shadow-sm">
        <?= csrfField() ?>

        <!-- Site Title -->
        <div>
            <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Company / Site Name</label>
            <input type="text" name="settings[site_name]" value="<?= e($s['site_name'] ?? 'Leo Surface Coatings') ?>" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <!-- Contact Phone 1 -->
            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Primary Phone</label>
                <input type="text" name="settings[contact_phone]" value="<?= e($s['contact_phone'] ?? '') ?>" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none">
            </div>

            <!-- Contact Phone 2 -->
            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Secondary Phone</label>
                <input type="text" name="settings[contact_phone_alt]" value="<?= e($s['contact_phone_alt'] ?? '') ?>" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <!-- Email 1 -->
            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Primary Contact Email</label>
                <input type="email" name="settings[contact_email]" value="<?= e($s['contact_email'] ?? '') ?>" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none">
            </div>

            <!-- Email 2 -->
            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Secondary Contact Email</label>
                <input type="email" name="settings[contact_email_alt]" value="<?= e($s['contact_email_alt'] ?? '') ?>" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none">
            </div>
        </div>

        <!-- Address -->
        <div>
            <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Registered Office Address</label>
            <textarea name="settings[contact_address]" rows="3" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none"><?= e($s['contact_address'] ?? '') ?></textarea>
        </div>

        <!-- LinkedIn URL -->
        <div>
            <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">LinkedIn Profile URL</label>
            <input type="url" name="settings[linkedin_url]" value="<?= e($s['linkedin_url'] ?? '') ?>" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none">
        </div>

        <!-- Submit Button -->
        <div class="pt-4 border-t border-gray-100 flex justify-end">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm px-6 py-2.5 rounded-xl shadow-lg shadow-blue-600/30 transition flex items-center gap-2">
                <i class="fa-solid fa-floppy-disk"></i> Save Settings
            </button>
        </div>

    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
