<?php
require_once '../db.php';

$error = '';
$success = '';

// Logic to handle form submission is moved to the top
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $judul = trim($_POST['judul']);
    $durasi = intval($_POST['durasi']);
    
    // Make sure session contains id_guru before using it
    if (!isset($_SESSION['id_guru'])) {
        // Redirect to login or show an error if teacher ID is not in session
        header("location: ../login.php?error=session_expired");
        exit;
    }
    $id_guru = $_SESSION['id_guru'];
    
    // Generate unique exam code
    $kode_ujian = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 6);

    if (empty($judul) || empty($durasi)) {
        $error = "Judul dan durasi wajib diisi.";
    } elseif ($durasi <= 0) {
        $error = "Durasi harus lebih dari 0.";
    } else {
        $stmt = $conn->prepare("INSERT INTO ujian (id_guru, judul, kode_ujian, durasi) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("issi", $id_guru, $judul, $kode_ujian, $durasi);

        if ($stmt->execute()) {
            $id_ujian_baru = $stmt->insert_id;
            $stmt->close();
            $conn->close();
            // Redirect to manage_exam.php on success
            header("location: manage_exam.php?id=" . $id_ujian_baru . "&status=created");
            exit; // Stop script execution after redirect
        } else {
            $error = "Gagal membuat ujian. Silakan coba lagi. Error: " . $stmt->error;
        }
        $stmt->close();
    }
    $conn->close();
}

// Include header after processing logic
include 'header.php';
?>

<title>Buat Ujian Baru</title>

<div class="max-w-3xl mx-auto py-10">
    <div class="bg-white border border-slate-200 rounded-lg shadow-sm">
        <div class="h-3 bg-blue-600 rounded-t-lg"></div>
        <div class="p-8">
            <h2 class="text-3xl font-bold text-slate-800 mb-2">Buat Ujian Baru</h2>
            <p class="text-slate-600 mb-8">Langkah pertama untuk membuat kuis atau ujian online. Isi judul dan durasi, lalu Anda bisa mulai menambahkan soal.</p>
            
            <?php if(!empty($error)): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-md" role="alert">
                    <p class="font-bold">Oops!</p>
                    <p><?php echo $error; ?></p>
                </div>
            <?php endif; ?>

            <form action="create_exam.php" method="post" class="space-y-6">
                <div>
                    <label for="judul" class="block text-sm font-medium text-slate-700 mb-1">Judul Ujian</label>
                    <input type="text" name="judul" id="judul" class="block w-full px-4 py-3 bg-slate-50 border-slate-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 transition" placeholder="Contoh: Ujian Tengah Semester Fisika" required>
                </div>
                <div>
                    <label for="durasi" class="block text-sm font-medium text-slate-700 mb-1">Durasi Ujian (menit)</label>
                    <input type="number" name="durasi" id="durasi" class="block w-full px-4 py-3 bg-slate-50 border-slate-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 transition" placeholder="Contoh: 90" required min="1">
                </div>
                <div class="pt-4">
                    <button type="submit" class="w-full flex justify-center items-center py-3 px-4 border border-transparent rounded-md shadow-sm text-lg font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                        <i data-lucide="plus-circle" class="mr-2 h-5 w-5"></i>
                        Simpan dan Lanjut Tambah Soal
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
