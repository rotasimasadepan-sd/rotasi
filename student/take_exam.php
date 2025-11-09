<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['id_partisipasi_ujian'])) {
    header("Location: ../index.php");
    exit;
}

// Handle form submission for navigation and answers
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_ujian = $_SESSION['id_ujian_aktif'];
    $soal_ids = $_SESSION['soal_acak_ids'];
    $current_idx = $_SESSION['soal_sekarang_idx'] ?? 0;

    // Simpan jawaban yang dikirim
    $jawaban = $_POST['jawaban'] ?? '';
    if (isset($soal_ids[$current_idx])) {
        $_SESSION['jawaban_siswa'][$soal_ids[$current_idx]] = $jawaban;
    }
    
    $navigation_action = $_POST['navigation'] ?? '';

    // Handle Selesaikan Ujian
    if ($navigation_action === 'finish') {
        header('Location: submit_exam.php');
        exit;
    }
    
    // Handle Navigasi (Sebelumnya / Selanjutnya)
    if ($navigation_action === 'prev') {
        if ($_SESSION['soal_sekarang_idx'] > 0) {
            $_SESSION['soal_sekarang_idx']--;
        }
    } elseif ($navigation_action === 'next') {
        if ($_SESSION['soal_sekarang_idx'] < (count($soal_ids) - 1) ) {
            $_SESSION['soal_sekarang_idx']++;
        }
    }
    
    // Redirect back to the exam page
    header('Location: take_exam.php');
    exit;
}

$id_ujian = $_SESSION['id_ujian_aktif'];
$soal_ids = $_SESSION['soal_acak_ids'];
$current_idx = $_SESSION['soal_sekarang_idx'] ?? 0;
$total_soal = count($soal_ids);

if ($current_idx >= $total_soal && $total_soal > 0) {
    header('Location: submit_exam.php');
    exit;
}

$id_soal_sekarang = $soal_ids[$current_idx];

// PERBAIKAN: Ambil data pertanyaan dengan gambar pendukung
$stmt_q = $conn->prepare("SELECT * FROM pertanyaan WHERE id = ?");
$stmt_q->bind_param("i", $id_soal_sekarang);
$stmt_q->execute();
$pertanyaan = $stmt_q->get_result()->fetch_assoc();
$stmt_q->close();

// PERBAIKAN: Ambil gambar pendukung soal (gambar_pertanyaan) - INI YANG DITAMPILKAN DI ATAS SOAL
$stmt_img = $conn->prepare("SELECT nama_file FROM gambar_pertanyaan WHERE id_pertanyaan = ?");
$stmt_img->bind_param("i", $id_soal_sekarang);
$stmt_img->execute();
$gambar_pertanyaan = $stmt_img->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_img->close();

// PERBAIKAN: Ambil SEMUA opsi jawaban untuk pilihan ganda gambar
$opsi_jawaban = [];
if ($pertanyaan['tipe_soal'] == 'pilihan_ganda' || $pertanyaan['tipe_soal'] == 'pilihan_ganda_gambar') {
    $stmt_o = $conn->prepare("SELECT * FROM opsi_jawaban WHERE id_pertanyaan = ? ORDER BY id ASC");
    $stmt_o->bind_param("i", $id_soal_sekarang);
    $stmt_o->execute();
    $opsi_jawaban = $stmt_o->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_o->close();
}

$jawaban_tersimpan = $_SESSION['jawaban_siswa'][$id_soal_sekarang] ?? null;

// Ambil data ujian untuk menampilkan gambar pendukung ujian (jika ada)
$stmt_ujian = $conn->prepare("SELECT judul, gambar_pendukung FROM ujian WHERE id = ?");
$stmt_ujian->bind_param("i", $id_ujian);
$stmt_ujian->execute();
$ujian_data = $stmt_ujian->get_result()->fetch_assoc();
$stmt_ujian->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ujian Berlangsung - <?php echo htmlspecialchars($ujian_data['judul']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Roboto+Mono:wght@500&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="icon" type="image/png" href="../logo.png">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .timer-font { font-family: 'Roboto Mono', monospace; }
        .option-radio:checked + label {
            border-color: #2563eb;
            background-color: #dbeafe;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.3);
        }
        .option-image-radio:checked + label {
            border-color: #2563eb;
            background-color: #dbeafe;
            transform: scale(1.02);
        }
    </style>
</head>
<body class="bg-slate-100">
    <div class="fixed top-0 left-0 w-full bg-white/90 backdrop-blur-lg shadow-md z-10 border-b border-slate-200">
        <div class="max-w-6xl mx-auto flex justify-between items-center p-4">
            <div class="flex items-center space-x-4">
                <?php if (!empty($ujian_data['gambar_pendukung'])): ?>
                    <img src="../uploads/exams/<?php echo htmlspecialchars($ujian_data['gambar_pendukung']); ?>" 
                         alt="Gambar Ujian" 
                         class="h-10 w-10 object-cover rounded-lg border border-slate-200">
                <?php endif; ?>
                <div>
                    <h2 class="text-lg font-bold text-slate-800"><?php echo htmlspecialchars($ujian_data['judul']); ?></h2>
                    <p class="text-sm text-slate-600">Ujian Berlangsung</p>
                </div>
            </div>
            <div id="timer" class="flex items-center space-x-2 bg-blue-600 text-white text-lg font-bold timer-font px-4 py-2 rounded-lg shadow-sm">
                <i data-lucide="clock" class="w-5 h-5"></i>
                <span id="timer-display">--:--</span>
            </div>
        </div>
    </div>

    <div class="max-w-4xl mx-auto pt-28 pb-12 px-4">
        <div class="bg-white p-8 rounded-2xl shadow-lg border border-slate-200">
            <div class="mb-6 pb-6 border-b border-slate-200">
                <div class="flex justify-between items-start mb-4">
                    <p class="text-sm font-semibold text-blue-600 bg-blue-50 px-3 py-1 rounded-full">Soal <?php echo ($current_idx + 1) . " dari " . $total_soal; ?></p>
                    <span class="text-sm font-medium text-slate-500 bg-slate-100 px-3 py-1 rounded-full">
                        <?php 
                        switch($pertanyaan['tipe_soal']) {
                            case 'pilihan_ganda': echo 'Pilihan Ganda'; break;
                            case 'pilihan_ganda_gambar': echo 'Pilihan Ganda Gambar'; break;
                            case 'essay': echo 'Essay'; break;
                        }
                        ?>
                    </span>
                </div>
                
                <!-- PERBAIKAN: Tampilkan GAMBAR PENDUKUNG SOAL jika ada -->
                <?php if (!empty($gambar_pertanyaan)): ?>
                    <div class="mb-6">
                        
                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                            <?php foreach ($gambar_pertanyaan as $gambar): ?>
                                <?php if (!empty($gambar['nama_file']) && file_exists('../uploads/' . $gambar['nama_file'])): ?>
                                    <a href="../uploads/<?php echo htmlspecialchars($gambar['nama_file']); ?>" target="_blank" class="group">
                                        <div class="relative overflow-hidden rounded-lg border border-slate-200 hover:shadow-md transition-shadow">
                                            <img src="../uploads/<?php echo htmlspecialchars($gambar['nama_file']); ?>" 
                                                 alt="Gambar Pendukung Soal" 
                                                 class="w-full h-32 object-cover group-hover:scale-105 transition-transform">
                                            <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-10 transition-all"></div>
                                        </div>
                                    </a>
                                <?php else: ?>
                                    <div class="border border-red-300 rounded-lg p-4 text-center bg-red-50">
                                        <i data-lucide="image-off" class="w-8 h-8 text-red-400 mx-auto mb-2"></i>
                                        <p class="text-xs text-red-600">Gambar tidak ditemukan</p>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="text-lg text-slate-800 leading-relaxed bg-slate-50 p-4 rounded-lg">
                    <?php echo nl2br(htmlspecialchars($pertanyaan['teks_pertanyaan'])); ?>
                </div>
            </div>
            
            <form action="take_exam.php" method="post" id="exam-form">
                <input type="hidden" name="navigation" id="navigation-action" value="">
                <input type="hidden" name="question_id" value="<?php echo $id_soal_sekarang; ?>">
                
                <?php if ($pertanyaan['tipe_soal'] == 'pilihan_ganda'): ?>
                    <div class="space-y-3">
                        <?php foreach ($opsi_jawaban as $index => $opsi): ?>
                            <div>
                                <input type="radio" name="jawaban" value="<?php echo $opsi['id']; ?>" 
                                       id="opsi_<?php echo $opsi['id']; ?>" 
                                       class="hidden option-radio" 
                                       <?php if ($jawaban_tersimpan == $opsi['id']) echo 'checked'; ?>>
                                <label for="opsi_<?php echo $opsi['id']; ?>" 
                                       class="flex items-center space-x-4 w-full p-4 border-2 border-slate-300 rounded-lg cursor-pointer transition-all duration-200 hover:border-blue-500 hover:bg-blue-50">
                                    <span class="flex-shrink-0 w-6 h-6 rounded-full border-2 border-slate-400 flex items-center justify-center text-sm font-medium">
                                        <?php echo chr(65 + $index); ?>
                                    </span>
                                    <span class="text-base text-slate-700 flex-grow"><?php echo htmlspecialchars($opsi['teks_opsi']); ?></span>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                <?php elseif ($pertanyaan['tipe_soal'] == 'pilihan_ganda_gambar'): ?>
                    <!-- PERBAIKAN: Grid yang fleksibel untuk gambar dan pastikan SEMUA opsi tampil -->
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                        <?php if (!empty($opsi_jawaban)): ?>
                            <?php foreach ($opsi_jawaban as $index => $opsi): ?>
                                <?php if (!empty($opsi['gambar_opsi']) && file_exists('../uploads/' . $opsi['gambar_opsi'])): ?>
                                    <div class="flex flex-col items-center">
                                        <input type="radio" name="jawaban" value="<?php echo $opsi['id']; ?>" 
                                               id="opsi_<?php echo $opsi['id']; ?>" 
                                               class="hidden option-image-radio" 
                                               <?php if ($jawaban_tersimpan == $opsi['id']) echo 'checked'; ?>>
                                        <label for="opsi_<?php echo $opsi['id']; ?>" 
                                               class="block w-full p-3 border-2 border-slate-300 rounded-lg cursor-pointer transition-all duration-200 hover:border-blue-500 hover:bg-blue-50 aspect-square flex flex-col items-center justify-center">
                                            <img src="../uploads/<?php echo htmlspecialchars($opsi['gambar_opsi']); ?>" 
                                                 alt="Opsi <?php echo $index + 1; ?>" 
                                                 class="max-w-full max-h-20 object-contain rounded-md mb-2">
                                            <span class="text-xs font-medium text-slate-600 text-center">
                                                Opsi <?php echo $index + 1; ?>
                                            </span>
                                        </label>
                                    </div>
                                <?php else: ?>
                                    <div class="flex flex-col items-center text-red-500">
                                        <span class="text-sm">Gambar tidak ditemukan</span>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="col-span-full text-center text-slate-500 py-8">
                                <i data-lucide="image-off" class="w-12 h-12 mx-auto mb-2"></i>
                                <p>Tidak ada opsi gambar tersedia</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                <?php elseif ($pertanyaan['tipe_soal'] == 'essay'): ?>
                    <div>
                        <label for="jawaban_essay" class="block text-sm font-medium text-slate-700 mb-2">Jawaban Anda:</label>
                        <textarea name="jawaban" id="jawaban_essay" rows="8" 
                                  class="w-full p-4 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition resize-vertical"
                                  placeholder="Ketik jawaban Anda di sini..."><?php echo htmlspecialchars($jawaban_tersimpan ?? ''); ?></textarea>
                        <p class="text-sm text-slate-500 mt-2">Skor: <?php echo $pertanyaan['skor']; ?> poin</p>
                    </div>
                <?php endif; ?>
                
                <div class="flex justify-between items-center mt-10 pt-6 border-t border-slate-200">
                    <div>
                        <?php if ($current_idx > 0): ?>
                            <button type="button" onclick="navigate('prev')" 
                                    class="flex items-center space-x-2 bg-slate-200 text-slate-700 font-semibold py-3 px-5 rounded-lg hover:bg-slate-300 transition-colors shadow-sm">
                                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                                <span>Sebelumnya</span>
                            </button>
                        <?php endif; ?>
                    </div>
                    <div class="flex items-center space-x-3">
                        <?php if ($current_idx < $total_soal - 1): ?>
                            <button type="button" onclick="navigate('next')" 
                                    class="flex items-center space-x-2 bg-blue-600 text-white font-semibold py-3 px-5 rounded-lg hover:bg-blue-700 transition-colors shadow-sm">
                                <span>Selanjutnya</span>
                                <i data-lucide="arrow-right" class="w-4 h-4"></i>
                            </button>
                        <?php else: ?>
                            <button type="button" onclick="confirmFinish()" 
                                    class="flex items-center space-x-2 bg-green-600 text-white font-semibold py-3 px-5 rounded-lg hover:bg-green-700 transition-colors shadow-sm">
                                <i data-lucide="check-circle" class="w-4 h-4"></i>
                                <span>Selesaikan Ujian</span>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        lucide.createIcons();
        
        let isNavigating = false;

        function navigate(action) {
            isNavigating = true;
            document.getElementById('navigation-action').value = action;
            document.getElementById('exam-form').submit();
        }

        function confirmFinish() {
            if (confirm('Apakah Anda yakin ingin menyelesaikan ujian? Pastikan semua jawaban sudah diperiksa.')) {
                navigate('finish');
            }
        }

        const WAKTU_SELESAI = <?php echo $_SESSION['waktu_mulai_ujian'] + ($_SESSION['durasi_ujian'] * 60); ?>;
        const timerDisplay = document.getElementById('timer-display');

        function submitExamOnTimeout() {
            isNavigating = true;
            const form = document.getElementById('exam-form');
            if (form && !form.dataset.submitted) {
                form.dataset.submitted = 'true';
                document.getElementById('navigation-action').value = 'finish';
                form.submit();
            }
        }

        // Timer Countdown
        function updateTimer() {
            const sisaDetik = WAKTU_SELESAI - Math.floor(Date.now() / 1000);
            if (sisaDetik <= 0) {
                timerDisplay.innerHTML = "00:00";
                clearInterval(timerInterval);
                alert("Waktu ujian telah habis. Jawaban Anda akan dikumpulkan secara otomatis.");
                submitExamOnTimeout();
                return;
            }
            const menit = Math.floor(sisaDetik / 60);
            const detik = sisaDetik % 60;
            timerDisplay.innerHTML = `${String(menit).padStart(2, '0')}:${String(detik).padStart(2, '0')}`;
            
            // Change color when less than 5 minutes
            if (sisaDetik < 300) {
                timerDisplay.parentElement.classList.remove('bg-blue-600');
                timerDisplay.parentElement.classList.add('bg-red-600');
            }
        }
        const timerInterval = setInterval(updateTimer, 1000);
        updateTimer();

        // Fitur deteksi pindah tab
        let tabWasHidden = false;
        document.addEventListener('visibilitychange', function() {
            if (!isNavigating) {
                if (document.visibilityState === 'hidden') {
                    tabWasHidden = true;
                    navigator.sendBeacon('reset_progress.php');
                }
                if (document.visibilityState === 'visible' && tabWasHidden) {
                    alert("Anda terdeteksi berpindah tab atau window. Sesuai aturan, kemajuan ujian Anda telah direset.");
                    window.location.reload();
                }
            }
        });
    </script>
</body>
</html>