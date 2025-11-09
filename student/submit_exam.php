<?php
require_once '../db.php';

if (!isset($_SESSION['id_partisipasi_ujian'])) {
    header("Location: ../index.php");
    exit;
}

$id_partisipasi = $_SESSION['id_partisipasi_ujian'];
$jawaban_siswa = $_SESSION['jawaban_siswa'] ?? [];

$conn->begin_transaction();
try {
    // Hapus jawaban lama untuk memastikan data bersih
    $stmt_delete = $conn->prepare("DELETE FROM jawaban_siswa WHERE id_partisipasi = ?");
    $stmt_delete->bind_param("i", $id_partisipasi);
    $stmt_delete->execute();
    $stmt_delete->close();

    // Ambil semua pertanyaan untuk ujian ini, beserta skor dan jawaban benarnya
    $stmt_pertanyaan = $conn->prepare(
        "SELECT p.id, p.tipe_soal, p.skor, o.id AS id_opsi_benar 
         FROM pertanyaan p 
         LEFT JOIN opsi_jawaban o ON p.id = o.id_pertanyaan AND o.adalah_jawaban_benar = 1 
         WHERE p.id_ujian = ?"
    );
    $stmt_pertanyaan->bind_param("i", $_SESSION['id_ujian_aktif']);
    $stmt_pertanyaan->execute();
    $result_pertanyaan = $stmt_pertanyaan->get_result();
    
    $semua_soal_map = [];
    while ($row = $result_pertanyaan->fetch_assoc()) {
        $semua_soal_map[$row['id']] = $row;
    }
    $stmt_pertanyaan->close();

    $total_skor_didapat = 0;
    $total_skor_maksimal_pg = 0;

    $stmt_insert = $conn->prepare("INSERT INTO jawaban_siswa (id_partisipasi, id_pertanyaan, id_opsi_jawaban, jawaban_essay) VALUES (?, ?, ?, ?)");

    foreach ($semua_soal_map as $id_pertanyaan => $soal) {
        $jawaban = $jawaban_siswa[$id_pertanyaan] ?? null;
        
        $id_opsi_jawaban = null;
        $jawaban_essay = null;

        // FIX: Handle both 'pilihan_ganda' and 'pilihan_ganda_gambar' for scoring
        if ($soal['tipe_soal'] == 'pilihan_ganda' || $soal['tipe_soal'] == 'pilihan_ganda_gambar') {
            $total_skor_maksimal_pg += $soal['skor']; // Tambahkan skor soal ke total skor maksimal
            if ($jawaban !== null && $jawaban !== '') {
                $id_opsi_jawaban = intval($jawaban);
                if ($id_opsi_jawaban == $soal['id_opsi_benar']) {
                    $total_skor_didapat += $soal['skor']; // Jika benar, tambahkan skor soal ke skor didapat
                }
            }
        } elseif ($soal['tipe_soal'] == 'essay') {
            if ($jawaban !== null) {
                $jawaban_essay = trim($jawaban);
            }
        }
        $stmt_insert->bind_param("iiis", $id_partisipasi, $id_pertanyaan, $id_opsi_jawaban, $jawaban_essay);
        $stmt_insert->execute();
    }
    $stmt_insert->close();

    // Hitung skor akhir (hanya dari soal PG) dalam skala 0-100
    $skor_akhir = ($total_skor_maksimal_pg > 0) ? ($total_skor_didapat / $total_skor_maksimal_pg) * 100 : 0;

    // Update data partisipasi dengan skor dan waktu selesai
    $stmt_update = $conn->prepare("UPDATE partisipasi_siswa SET waktu_selesai = NOW(), skor = ? WHERE id = ?");
    $stmt_update->bind_param("di", $skor_akhir, $id_partisipasi);
    $stmt_update->execute();
    $stmt_update->close();

    $conn->commit();

} catch (Exception $e) {
    $conn->rollback();
    // Sebaiknya log error ini untuk debugging
    error_log("Error submitting exam: " . $e->getMessage());
    die("Terjadi kesalahan saat menyimpan jawaban Anda. Silakan coba lagi atau hubungi administrator.");
}

// Hapus semua sesi terkait ujian
unset($_SESSION['id_partisipasi_ujian'], $_SESSION['id_ujian_aktif'], $_SESSION['waktu_mulai_ujian'], $_SESSION['durasi_ujian'], $_SESSION['soal_acak_ids'], $_SESSION['jawaban_siswa'], $_SESSION['soal_sekarang_idx']);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ujian Selesai - UjianKita</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="icon" type="image/png" href="../logo.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Inter', sans-serif; } </style>
</head>
<body class="bg-slate-100">
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="max-w-md w-full bg-white p-10 rounded-2xl shadow-lg text-center">
            <div class="w-24 h-24 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-6">
                <i data-lucide="check-circle-2" class="w-16 h-16"></i>
            </div>
            <h1 class="text-3xl font-bold text-slate-900 mb-3">Ujian Telah Selesai!</h1>
            <p class="text-slate-600 mb-8">Terima kasih telah menyelesaikan ujian. Jawaban Anda telah berhasil disimpan. Hasil akhir akan diumumkan oleh guru Anda setelah soal essay (jika ada) diperiksa.</p>
            <a href="../index.php" class="inline-flex items-center space-x-2 bg-blue-600 text-white font-bold py-3 px-6 rounded-lg hover:bg-blue-700 transition-transform transform hover:scale-105">
                <i data-lucide="home" class="w-5 h-5"></i>
                <span>Kembali ke Halaman Utama</span>
            </a>
        </div>
    </div>
    <script>lucide.createIcons();</script>
</body>
</html>
