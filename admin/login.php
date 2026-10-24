<?php
require_once __DIR__ . '/config.php';

$error = '';

if (isset($_SESSION['admin_user'])) {
    header("Location: index.php");
    exit;
}

// Rate Limiting Logic (Max 5 failed attempts per 15 mins per IP)
$ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
$time_window = time() - (15 * 60);

if (!isset($_SESSION['failed_logins'])) {
    $_SESSION['failed_logins'] = [];
}

// Filter attempts older than 15 mins
$_SESSION['failed_logins'] = array_filter($_SESSION['failed_logins'], function($t) use ($time_window) {
    return $t > $time_window;
});

if (count($_SESSION['failed_logins']) >= 5) {
    $error = "Too many failed login attempts. Please wait 15 minutes before trying again.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && count($_SESSION['failed_logins']) < 5) {
    // Validate Anti-CSRF Token
    validateCsrfToken();

    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username && $password) {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            // Prevent Session Fixation
            session_regenerate_id(true);

            $_SESSION['admin_user'] = $user['username'];
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_role'] = $user['role'];
            unset($_SESSION['failed_logins']);

            header("Location: index.php");
            exit;
        } else {
            $_SESSION['failed_logins'][] = time();
            $attempts_left = 5 - count($_SESSION['failed_logins']);
            $error = "Invalid username or password! ($attempts_left attempts remaining)";
        }
    } else {
        $error = "Please fill in all fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Leo Surface Coatings</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-slate-900 min-h-screen flex items-center justify-center p-4">

    <div class="max-w-md w-full bg-white rounded-2xl shadow-2xl p-8 border border-slate-700">
        <div class="text-center mb-8">
            <div class="w-16 h-16 bg-blue-600 rounded-2xl flex items-center justify-center mx-auto mb-4 text-white shadow-lg text-2xl">
                <i class="fa-solid fa-layer-group"></i>
            </div>
            <h1 class="text-2xl font-bold text-slate-800">Leo Admin Portal</h1>
            <p class="text-slate-500 text-sm mt-1">Sign in to manage site content & assets</p>
        </div>

        <?php if ($error): ?>
            <div class="mb-6 bg-red-50 border border-red-200 text-red-700 p-4 rounded-xl text-sm flex items-center gap-3">
                <i class="fa-solid fa-circle-exclamation text-red-500 text-base"></i>
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php" class="space-y-5">
            <?= csrfField() ?>
            <div>
                <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">Username</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400">
                        <i class="fa-solid fa-user"></i>
                    </span>
                    <input type="text" name="username" required value="" class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white text-sm outline-none transition" placeholder="Enter username">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">Password</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400">
                        <i class="fa-solid fa-lock"></i>
                    </span>
                    <input type="password" name="password" required value="" class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white text-sm outline-none transition" placeholder="Enter password">
                </div>
            </div>

            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3.5 px-4 rounded-xl shadow-lg shadow-blue-600/30 transition duration-200 flex items-center justify-center gap-2">
                <span>Sign In to Dashboard</span>
                <i class="fa-solid fa-arrow-right text-xs"></i>
            </button>
        </form>

    </div>

</body>
</html>
