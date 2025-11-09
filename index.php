<?php
// This is the new landing page.
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rotasi Masa Depan - Platform Ujian Online Generasi Baru</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="icon" type="image/png" href="logo.png">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .bg-grid { background-image: linear-gradient(to right, rgba(255, 255, 255, 0.05) 1px, transparent 1px), linear-gradient(to bottom, rgba(255, 255, 255, 0.05) 1px, transparent 1px); background-size: 2rem 2rem; }
        .glow-effect { box-shadow: 0 0 20px rgba(59, 130, 246, 0.5), 0 0 40px rgba(59, 130, 246, 0.3); }
    </style>
</head>
<body class="bg-slate-900 text-white antialiased">

    <!-- Header -->
    <header class="fixed top-0 left-0 right-0 z-50 bg-slate-900/80 backdrop-blur-lg border-b border-slate-700">
        <div class="container mx-auto px-6 py-4 flex justify-between items-center">
            <a href="#" class="flex items-center space-x-2">
                <div class=" p-2 rounded-lg">
                    <img src="logo.png" alt="" width="50">
                      </div>
                <h1 class="text-2xl font-bold text-white">Rotasi<span class="text-blue-500"> Masa Depan</span></h1>
            </a>
           
            <a href="login.php" class="hidden md:inline-block bg-slate-700 text-white font-semibold px-5 py-2 rounded-lg hover:bg-slate-600 transition-all">Login</a>
        </div>
    </header>

    <main>
        <!-- Hero Section -->
        <section class="relative min-h-screen flex items-center justify-center pt-20 overflow-hidden bg-grid">
            <div class="absolute inset-0 bg-gradient-to-b from-slate-900 via-slate-900/80 to-slate-900"></div>
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-96 h-96 bg-blue-700/50 rounded-full blur-3xl animate-pulse"></div>
            <div class="container mx-auto px-6 text-center relative z-10">
                <h2 class="text-5xl md:text-7xl font-extrabold tracking-tight bg-clip-text text-transparent bg-gradient-to-r from-blue-400 to-teal-300 leading-tight">
                    Ujian Online Generasi Baru.
                </h2>
                <p class="mt-6 max-w-2xl mx-auto text-lg md:text-xl text-slate-300">
                    Platform yang bikin ujian jadi lebih seru, interaktif, dan anti-ribet. Untuk guru yang modern dan siswa yang siap jadi juara.
                </p>
                <div class="mt-10 flex flex-col sm:flex-row justify-center items-center gap-4">
                    <a href="#mulai-ujian" class="w-full sm:w-auto bg-gradient-to-r from-blue-500 to-blue-600 text-white font-bold py-4 px-8 rounded-full text-lg hover:scale-105 transition-transform shadow-lg glow-effect">
                        Mulai Ujian (Siswa)
                    </a>
                    <a href="login.php" class="w-full sm:w-auto bg-slate-700/50 border border-slate-600 text-white font-semibold py-4 px-8 rounded-full text-lg hover:bg-slate-700 transition-colors">
                        Buat Ujian (Guru)
                    </a>
                </div>
            </div>
        </section>

        <!-- Mulai Ujian Section -->
        <section id="mulai-ujian" class="py-20 bg-slate-900">
            <div class="container mx-auto px-6">
                <div class="max-w-xl mx-auto text-center">
                    <h2 class="text-3xl md:text-4xl font-bold mb-4">Punya Kode Ujian?</h2>
                    <p class="text-slate-400 mb-8">Masukkan kode yang diberikan oleh gurumu untuk memulai ujian. Semoga berhasil!</p>
                    <form action="join_exam.php" method="GET" class="flex flex-col sm:flex-row gap-3">
                        <input type="text" name="kode" placeholder="Ketik kode ujian di sini..." required class="w-full flex-grow bg-slate-800 border border-slate-700 rounded-lg px-6 py-4 text-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                        <button type="submit" class="bg-blue-600 text-white font-bold py-4 px-8 rounded-lg hover:bg-blue-700 transition-colors">
                            Lanjut
                        </button>
                    </form>
                </div>
            </div>
        </section>

        <!-- Fitur Section -->
        <section id="fitur" class="py-20 bg-slate-800/50">
            <div class="container mx-auto px-6">
                <div class="text-center mb-12">
                    <h2 class="text-4xl font-bold">Kenapa<span class="text-blue-500"> Rotasi Masa Depan</span>?</h2>
                    <p class="mt-4 max-w-2xl mx-auto text-slate-400">Karena kami percaya ujian tidak harus membosankan. Ini yang bikin kami beda.</p>
                </div>
                <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
                    <div class="bg-slate-800 p-8 rounded-2xl border border-slate-700 hover:border-blue-500 hover:-translate-y-2 transition-all">
                        <i data-lucide="sparkles" class="w-12 h-12 text-blue-400 mb-4"></i>
                        <h3 class="text-xl font-bold mb-2">Desain Modern</h3>
                        <p class="text-slate-400">Tampilan yang fresh dan intuitif, bikin kamu betah lama-lama.</p>
                    </div>
                    <div class="bg-slate-800 p-8 rounded-2xl border border-slate-700 hover:border-blue-500 hover:-translate-y-2 transition-all">
                        <i data-lucide="shield-check" class="w-12 h-12 text-green-400 mb-4"></i>
                        <h3 class="text-xl font-bold mb-2">Aman & Teracak</h3>
                        <p class="text-slate-400">Soal diacak otomatis, meminimalisir kecurangan dan adil untuk semua.</p>
                    </div>
                    <div class="bg-slate-800 p-8 rounded-2xl border border-slate-700 hover:border-blue-500 hover:-translate-y-2 transition-all">
                        <i data-lucide="bar-chart-3" class="w-12 h-12 text-yellow-400 mb-4"></i>
                        <h3 class="text-xl font-bold mb-2">Hasil Real-time</h3>
                        <p class="text-slate-400">Nilai langsung keluar setelah ujian selesai. Gak perlu nunggu lama!</p>
                    </div>
                    <div class="bg-slate-800 p-8 rounded-2xl border border-slate-700 hover:border-blue-500 hover:-translate-y-2 transition-all">
                        <i data-lucide="smartphone" class="w-12 h-12 text-purple-400 mb-4"></i>
                        <h3 class="text-xl font-bold mb-2">Akses Fleksibel</h3>
                        <p class="text-slate-400">Kerjakan ujian kapan saja, di mana saja, lewat HP atau laptop.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="py-20">
            <div class="container mx-auto px-6">
                <div class="bg-gradient-to-r from-blue-600 to-purple-600 rounded-2xl p-10 md:p-16 text-center">
                    <h2 class="text-4xl font-extrabold mb-4">Siap Mengubah Cara Ujianmu?</h2>
                    <p class="text-blue-100 max-w-2xl mx-auto mb-8">Bergabunglah dengan ribuan guru dan siswa yang telah merasakan kemudahan ujian online bersama kami.</p>
                    <a href="login.php" class="bg-white text-blue-600 font-bold py-4 px-8 rounded-full text-lg hover:scale-105 transition-transform inline-block">
                        Mulai Sekarang, Gratis!
                    </a>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="bg-slate-900 border-t border-slate-800">
        <div class="container mx-auto px-6 py-8 text-center text-slate-400">
            <p>&copy; <?php echo date('Y'); ?> Rotasi Masa Depan. Platform ujian online masa kini.</p>
        </div>
    </footer>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
