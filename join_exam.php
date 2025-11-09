<?php
require_once 'db.php';

$error = '';
$kode_ujian = isset($_GET['kode']) ? trim($_GET['kode']) : '';

if (empty($kode_ujian)) {
    header("Location: index.php#mulai-ujian");
    exit;
}

// Ambil data ujian
$stmt = $conn->prepare("SELECT * FROM ujian WHERE kode_ujian = ?");
$stmt->bind_param("s", $kode_ujian);
$stmt->execute();
$ujian = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Fungsi untuk menampilkan halaman status
function show_status_page($title, $message, $icon) {
    echo <<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>$title - RDM</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="icon" type="image/png" href="logo.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Inter', sans-serif; } </style>
</head>
<body class="bg-slate-100 text-slate-800">
    <div class="min-h-screen flex flex-col items-center justify-center p-4 text-center">
        <div class="bg-white p-8 sm:p-12 rounded-2xl shadow-lg max-w-md w-full">
            <div class="w-20 h-20 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center mx-auto mb-6">
                <i data-lucide="$icon" class="w-10 h-10"></i>
            </div>
            <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 mb-3">$title</h1>
            <p class="text-slate-500 mb-8">$message</p>
            <a href="index.php" class="inline-flex items-center space-x-2 bg-blue-600 text-white font-bold py-3 px-6 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-transform transform hover:scale-105">
                <i data-lucide="arrow-left" class="w-5 h-5"></i>
                <span>Kembali ke Awal</span>
            </a>
        </div>
    </div>
    <script>lucide.createIcons();</script>
</body>
</html>
HTML;
    exit;
}

if (!$ujian) {
    show_status_page("Ujian Tidak Ditemukan", "Ujian dengan kode yang Anda masukkan tidak ada. Pastikan kode sudah benar.", "search-x");
}

if ($ujian['status'] == 'menunggu') {
    show_status_page("Ujian Belum Dimulai", "Ujian ini dijadwalkan tetapi belum dimulai oleh guru. Silakan kembali lagi nanti.", "timer");
}

if ($ujian['status'] == 'selesai') {
    show_status_page("Ujian Telah Selesai", "Ujian ini sudah berakhir dan tidak bisa diakses lagi.", "lock");
}

// Proses pendaftaran siswa untuk ujian
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama_siswa = trim($_POST['nama_siswa']);
    $kelas_siswa = trim($_POST['kelas_siswa']);

    if (empty($nama_siswa) || empty($kelas_siswa)) {
        $error = "Nama dan Kelas wajib diisi.";
    } else {
        // Cek apakah siswa sudah pernah mengerjakan dan menyelesaikan
        $stmt_check = $conn->prepare("SELECT id FROM partisipasi_siswa WHERE id_ujian = ? AND nama_siswa = ? AND kelas_siswa = ? AND waktu_selesai IS NOT NULL");
        $stmt_check->bind_param("iss", $ujian['id'], $nama_siswa, $kelas_siswa);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            $error = "Anda sudah pernah menyelesaikan ujian ini dengan nama dan kelas yang sama.";
        } else {
            // Daftarkan partisipasi siswa jika belum ada
            $stmt_find = $conn->prepare("SELECT id FROM partisipasi_siswa WHERE id_ujian = ? AND nama_siswa = ? AND kelas_siswa = ?");
            $stmt_find->bind_param("iss", $ujian['id'], $nama_siswa, $kelas_siswa);
            $stmt_find->execute();
            $result_find = $stmt_find->get_result();
            if ($result_find->num_rows > 0) {
                $partisipasi = $result_find->fetch_assoc();
                $id_partisipasi = $partisipasi['id'];
            } else {
                $stmt_insert = $conn->prepare("INSERT INTO partisipasi_siswa (id_ujian, nama_siswa, kelas_siswa) VALUES (?, ?, ?)");
                $stmt_insert->bind_param("iss", $ujian['id'], $nama_siswa, $kelas_siswa);
                $stmt_insert->execute();
                $id_partisipasi = $stmt_insert->insert_id;
                $stmt_insert->close();
            }
            $stmt_find->close();

            // Ambil semua ID pertanyaan dan acak urutannya
            $pertanyaan_ids = [];
            $result_q = $conn->query("SELECT id FROM pertanyaan WHERE id_ujian = {$ujian['id']}");
            while ($row_q = $result_q->fetch_assoc()) {
                $pertanyaan_ids[] = $row_q['id'];
            }
            shuffle($pertanyaan_ids);

            // Simpan data ke session dan mulai ujian
            $_SESSION['id_partisipasi_ujian'] = $id_partisipasi;
            $_SESSION['id_ujian_aktif'] = $ujian['id'];
            $_SESSION['waktu_mulai_ujian'] = time();
            $_SESSION['durasi_ujian'] = $ujian['durasi'];
            $_SESSION['soal_acak_ids'] = $pertanyaan_ids;
            $_SESSION['jawaban_siswa'] = [];
            $_SESSION['soal_sekarang_idx'] = 0;

            header("location: student/take_exam.php");
            exit;
        }
        $stmt_check->close();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mulai Ujian: <?php echo htmlspecialchars($ujian['judul']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-slate-100 text-slate-800">
    <div class="min-h-screen flex flex-col items-center justify-center p-4">
        <div class="w-full max-w-md">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-slate-900"><?php echo htmlspecialchars($ujian['judul']); ?></h1>
                <p class="text-slate-500 mt-2">Selamat datang! Isi data diri untuk memulai ujian.</p>
            </div>
            <div class="bg-white p-8 rounded-2xl shadow-lg">
                <h2 class="text-2xl font-semibold text-center mb-6">Konfirmasi Data Diri</h2>
                
                <?php if(!empty($error)): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-6" role="alert">
                        <span class="block sm:inline"><?php echo $error; ?></span>
                    </div>
                <?php endif; ?>

                <form action="join_exam.php?kode=<?php echo htmlspecialchars($kode_ujian); ?>" method="post" class="space-y-6">
                    <div>
                        <label for="nama_siswa" class="block text-sm font-medium text-slate-600 mb-2">Nama Lengkap</label>
                        <input type="text" name="nama_siswa" id="nama_siswa" required class="w-full px-4 py-3 bg-slate-50 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                    </div>
                    <div>
                        <label for="kelas_siswa" class="block text-sm font-medium text-slate-600 mb-2">Kelas</label>
                        <input type="text" name="kelas_siswa" id="kelas_siswa" required class="w-full px-4 py-3 bg-slate-50 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                    </div>
                    <div>
                        <button type="submit" class="w-full bg-blue-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-transform transform hover:scale-105">
                            Mulai Kerjakan
                        </button>
                    </div>
                </form>
                 <p class="text-center text-sm text-slate-500 mt-8">
                    Salah kode? <a href="index.php" class="font-medium text-blue-600 hover:text-blue-500">Kembali ke halaman utama</a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
