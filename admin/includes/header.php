<?php
require_once __DIR__ . '/../config.php';
checkAdminLogin();

$current_page = basename($_SERVER['PHP_SELF']);
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Leo Surface Coatings</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen text-gray-800 flex flex-col">

    <!-- Top Navigation Bar -->
    <header class="bg-slate-900 text-white shadow-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Brand -->
                <div class="flex items-center space-x-3">
                    <div class="bg-blue-600 p-2 rounded-lg text-white font-bold">
                        <i class="fa-solid fa-layer-group text-lg"></i>
                    </div>
                    <div>
                        <a href="index.php" class="font-bold text-lg tracking-wide hover:text-blue-400 transition">Leo Admin</a>
                        <span class="text-xs text-slate-400 block">Surface Coatings CMS</span>
                    </div>
                </div>

                <!-- User Actions -->
                <div class="flex items-center space-x-4">
                    <a href="../index.html" target="_blank" class="text-slate-300 hover:text-white px-3 py-1.5 rounded-md text-sm font-medium bg-slate-800 hover:bg-slate-700 transition flex items-center gap-2">
                        <i class="fa-solid fa-globe text-blue-400"></i> View Site
                    </a>
                    <div class="border-l border-slate-700 h-6"></div>
                    <div class="flex items-center space-x-2 text-sm">
                        <span class="text-slate-400">Hello,</span>
                        <span class="font-semibold text-slate-200"><?= htmlspecialchars($_SESSION['admin_user']) ?></span>
                    </div>
                    <a href="logout.php" class="text-red-400 hover:text-red-300 px-3 py-1.5 rounded-md text-sm font-medium bg-red-950/40 hover:bg-red-900/50 transition flex items-center gap-1">
                        <i class="fa-solid fa-right-from-bracket"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Container -->
    <div class="flex-1 flex max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6 gap-6">

        <!-- Sidebar Navigation -->
        <aside class="w-64 bg-white rounded-xl shadow-sm border border-gray-200 p-4 shrink-0 flex flex-col justify-between">
            <div class="space-y-1">
                <div class="px-3 py-2 text-xs font-bold text-gray-400 uppercase tracking-wider">Navigation</div>
                
                <a href="index.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition <?= $current_page == 'index.php' ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                    <i class="fa-solid fa-chart-line w-5 text-center text-blue-500"></i> Dashboard
                </a>
                
                <a href="pages.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition <?= in_array($current_page, ['pages.php', 'edit_page.php']) ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                    <i class="fa-solid fa-file-lines w-5 text-center text-indigo-500"></i> Manage Pages
                </a>
                
                <a href="assets.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition <?= $current_page == 'assets.php' ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                    <i class="fa-solid fa-photo-film w-5 text-center text-emerald-500"></i> Asset Manager
                </a>

                <a href="media.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition <?= $current_page == 'media.php' ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                    <i class="fa-solid fa-images w-5 text-center text-pink-500"></i> Media Gallery
                </a>

                <a href="settings.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition <?= $current_page == 'settings.php' ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                    <i class="fa-solid fa-sliders w-5 text-center text-amber-500"></i> Site Settings
                </a>
            </div>

            <div class="border-t border-gray-100 pt-4 px-3 text-xs text-gray-400">
                <div><strong class="text-gray-600">Database:</strong> leoantigra_db</div>
                <div class="mt-1"><strong class="text-gray-600">Server:</strong> XAMPP MySQL</div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <main class="flex-1 bg-white rounded-xl shadow-sm border border-gray-200 p-6 overflow-hidden">
            <?php if ($flash): ?>
                <div class="mb-6 p-4 rounded-lg flex items-center justify-between <?= $flash['type'] == 'success' ? 'bg-emerald-50 border border-emerald-200 text-emerald-800' : 'bg-rose-50 border border-rose-200 text-rose-800' ?>">
                    <div class="flex items-center gap-3 text-sm font-medium">
                        <i class="fa-solid <?= $flash['type'] == 'success' ? 'fa-circle-check text-emerald-600' : 'fa-circle-exclamation text-rose-600' ?> text-lg"></i>
                        <?= htmlspecialchars($flash['msg']) ?>
                    </div>
                </div>
            <?php endif; ?>
