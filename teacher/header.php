<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="icon" type="image/png" href="logo.png">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .lucide {
            width: 20px;
            height: 20px;
            stroke-width: 2;
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-800">
    <header class="bg-white/80 backdrop-blur-lg shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-4">
                    <a href="dashboard.php" class="flex items-center space-x-2">
                        <div class=" p-2 rounded-lg">
                           <img src="../logo.png" width="50">
                        </div>
                        <h1 class="text-xl font-bold text-slate-800">Rotasi<span class="text-blue-600"> Masa Depan</span></h1>
                    </a>
                </div>
                <nav class="hidden md:flex items-center space-x-2">
                    <a href="dashboard.php" class="flex items-center space-x-2 text-slate-600 hover:text-blue-600 font-medium px-3 py-2 rounded-md transition-colors">
                        <i data-lucide="layout-dashboard"></i>
                        <span>Daftar Ujian</span>
                    </a>
                    <a href="create_exam.php" class="flex items-center space-x-2 text-slate-600 hover:text-blue-600 font-medium px-3 py-2 rounded-md transition-colors">
                        <i data-lucide="file-plus-2"></i>
                        <span>Buat Ujian Baru</span>
                    </a>
                </nav>
                <div class="flex items-center space-x-4">
                    <span class="hidden sm:inline font-medium text-slate-700">Halo, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
                    <a href="../logout.php" class="flex items-center justify-center bg-red-500 hover:bg-red-600 text-white font-bold w-10 h-10 rounded-full transition-transform transform hover:scale-110" title="Logout">
                        <i data-lucide="log-out"></i>
                    </a>
                </div>
            </div>
        </div>
    </header>
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
