<?php
// 1. Mulai session dan cek login SEBELUM output apapun
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION['id_guru'])) {
    header("location: ../login.php");
    exit;
}

// 2. Hubungkan ke DB
require_once '../db.php';
$id_guru = $_SESSION['id_guru'];

// 3. Logika pemrosesan form (hapus & update status)
// Logika ini sekarang dijalankan sebelum header.php dipanggil
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Logika untuk menghapus ujian
    if (isset($_POST['hapus_ujian'])) {
        $id_ujian_hapus = $_POST['id_ujian'];

        $stmt_check = $conn->prepare("SELECT id FROM ujian WHERE id = ? AND id_guru = ?");
        $stmt_check->bind_param("ii", $id_ujian_hapus, $id_guru);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            $conn->begin_transaction();
            try {
                $stmt_img = $conn->prepare("SELECT gp.nama_file FROM gambar_pertanyaan gp JOIN pertanyaan p ON gp.id_pertanyaan = p.id WHERE p.id_ujian = ?");
                $stmt_img->bind_param("i", $id_ujian_hapus);
                $stmt_img->execute();
                $result_img = $stmt_img->get_result();
                
                while ($img = $result_img->fetch_assoc()) {
                    $filepath = '../uploads/' . $img['nama_file'];
                    if (file_exists($filepath)) {
                        unlink($filepath);
                    }
                }
                $stmt_img->close();

                $stmt_delete = $conn->prepare("DELETE FROM ujian WHERE id = ?");
                $stmt_delete->bind_param("i", $id_ujian_hapus);
                $stmt_delete->execute();
                $stmt_delete->close();

                $conn->commit();
                header("Location: dashboard.php?status=exam_deleted");
                exit;

            } catch (Exception $e) {
                $conn->rollback();
                header("Location: dashboard.php?status=delete_failed");
                exit;
            }
        }
        $stmt_check->close();
    }

    // Logika untuk mengubah status ujian
    if (isset($_POST['update_status'])) {
        $id_ujian_update = $_POST['id_ujian'];
        $new_status = $_POST['status'];
        $allowed_statuses = ['menunggu', 'berlangsung', 'selesai'];

        if (in_array($new_status, $allowed_statuses)) {
            $stmt_update = $conn->prepare("UPDATE ujian SET status = ? WHERE id = ? AND id_guru = ?");
            $stmt_update->bind_param("sii", $new_status, $id_ujian_update, $id_guru);
            $stmt_update->execute();
            $stmt_update->close();
            header("Location: dashboard.php?status=status_updated");
            exit;
        }
    }
}

// 4. Ambil data untuk ditampilkan
$result = $conn->query("SELECT * FROM ujian WHERE id_guru = $id_guru ORDER BY created_at DESC");

// 5. Baru panggil header.php setelah semua logika selesai
include 'header.php';
?>

<title>Dasbor Guru - RMD</title>

<div class="flex justify-between items-center mb-8">
    <div>
        <h2 class="text-3xl font-bold text-slate-900">Daftar Ujian Anda</h2>
        <p class="text-slate-500 mt-1">Kelola semua ujian yang telah Anda buat di sini.</p>
    </div>
    <a href="create_exam.php" class="hidden sm:flex items-center space-x-2 bg-blue-600 text-white font-semibold px-5 py-3 rounded-lg hover:bg-blue-700 transition-all transform hover:scale-105 shadow-md hover:shadow-lg">
        <i data-lucide="plus-circle"></i>
        <span>Buat Ujian Baru</span>
    </a>
</div>

<?php
if (isset($_GET['status'])) {
    if ($_GET['status'] == 'exam_deleted') {
        echo '<div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-md" role="alert"><p>Ujian berhasil dihapus secara permanen.</p></div>';
    }
    if ($_GET['status'] == 'delete_failed') {
        echo '<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-md" role="alert"><p>Gagal menghapus ujian. Silakan coba lagi.</p></div>';
    }
    if ($_GET['status'] == 'status_updated') {
        echo '<div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 mb-6 rounded-md" role="alert"><p>Status ujian berhasil diperbarui.</p></div>';
    }
}
?>

<div class="bg-white p-6 rounded-2xl shadow-lg">
    <?php if ($result->num_rows > 0): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php while($row = $result->fetch_assoc()): ?>
                <div class="bg-slate-50 border border-slate-200 rounded-xl p-6 flex flex-col justify-between transition-all hover:shadow-xl hover:border-blue-500 hover:-translate-y-1">
                    <div>
                        <div class="flex justify-between items-start mb-4">
                            <h3 class="text-xl font-bold text-slate-800 w-2/3"><?php echo htmlspecialchars($row['judul']); ?></h3>
                            <?php
                                $status_class = '';
                                switch ($row['status']) {
                                    case 'berlangsung':
                                        $status_class = 'bg-green-100 text-green-800 border-green-200';
                                        break;
                                    case 'selesai':
                                        $status_class = 'bg-slate-100 text-slate-800 border-slate-200';
                                        break;
                                    case 'menunggu':
                                    default:
                                        $status_class = 'bg-yellow-100 text-yellow-800 border-yellow-200';
                                        break;
                                }
                            ?>
                            <form method="POST" action="dashboard.php" class="status-form">
                                <input type="hidden" name="id_ujian" value="<?php echo $row['id']; ?>">
                                <input type="hidden" name="update_status" value="1">
                                <select name="status" onchange="this.form.submit()" class="text-xs font-semibold px-2.5 py-1.5 rounded-full border appearance-none focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all <?php echo $status_class; ?>">
                                    <option value="menunggu" <?php if($row['status'] == 'menunggu') echo 'selected'; ?>>Menunggu</option>
                                    <option value="berlangsung" <?php if($row['status'] == 'berlangsung') echo 'selected'; ?>>Berlangsung</option>
                                    <option value="selesai" <?php if($row['status'] == 'selesai') echo 'selected'; ?>>Selesai</option>
                                </select>
                            </form>
                        </div>
                        <div class="space-y-3 text-sm text-slate-600">
                            <div class="flex items-center space-x-2">
                                <i data-lucide="key-round" class="text-slate-400 w-4 h-4"></i>
                                <span>Kode Ujian: <strong class="font-mono bg-slate-200 text-slate-800 px-2 py-0.5 rounded"><?php echo htmlspecialchars($row['kode_ujian']); ?></strong></span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <i data-lucide="clock" class="text-slate-400 w-4 h-4"></i>
                                <span>Durasi: <strong><?php echo $row['durasi']; ?> menit</strong></span>
                            </div>
                        </div>
                        <div class="mt-4 pt-4 border-t border-slate-200">
                            <p class="text-xs text-slate-500 mb-1">Link untuk Siswa:</p>
                            <?php $link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF'], 2) . "/join_exam.php?kode=" . $row['kode_ujian']; ?>
                            <a href="<?php echo $link; ?>" target="_blank" class="text-xs text-blue-600 hover:underline break-all"><?php echo $link; ?></a>
                        </div>
                    </div>
                    <div class="mt-6 flex items-center justify-end gap-2 flex-wrap">
                        <a href="manage_exam.php?id=<?php echo $row['id']; ?>" class="flex items-center justify-center space-x-2 bg-slate-800 text-white font-semibold py-2 px-3 rounded-lg hover:bg-slate-900 transition-colors text-sm">
                            <i data-lucide="settings-2" class="w-4 h-4"></i>
                            <span>Kelola</span>
                        </a>
                        <a href="report.php?id=<?php echo $row['id']; ?>" class="flex items-center justify-center space-x-2 bg-sky-600 text-white font-semibold py-2 px-3 rounded-lg hover:bg-sky-700 transition-colors text-sm">
                            <i data-lucide="file-text" class="w-4 h-4"></i>
                            <span>Laporan</span>
                        </a>
                        <form method="POST" action="dashboard.php" onsubmit="return confirm('Anda yakin ingin menghapus ujian ini? Semua data soal dan jawaban siswa akan hilang selamanya.')">
                            <input type="hidden" name="id_ujian" value="<?php echo $row['id']; ?>">
                            <button type="submit" name="hapus_ujian" class="flex items-center justify-center space-x-2 bg-red-600 text-white font-semibold py-2 px-3 rounded-lg hover:bg-red-700 transition-colors text-sm">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                <span>Hapus</span>
                            </button>
                        </form>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="text-center py-16 border-2 border-dashed border-slate-300 rounded-xl">
            <i data-lucide="file-x-2" class="mx-auto h-16 w-16 text-slate-400"></i>
            <h3 class="mt-4 text-xl font-semibold text-slate-800">Belum Ada Ujian</h3>
            <p class="mt-2 text-slate-500">Anda belum membuat ujian apapun. <br>Klik tombol di bawah untuk memulai.</p>
            <a href="create_exam.php" class="mt-6 inline-flex items-center space-x-2 bg-blue-600 text-white font-semibold px-5 py-3 rounded-lg hover:bg-blue-700 transition-all transform hover:scale-105">
                <i data-lucide="plus-circle"></i>
                <span>Buat Ujian Pertama Anda</span>
            </a>
        </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
