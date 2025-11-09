<?php
require_once '../db.php';
include 'header.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: dashboard.php"); exit;
}
$id_ujian = $_GET['id'];
$id_guru = $_SESSION['id_guru']; // Assuming id_guru is stored in session
$filter_kelas = $_GET['kelas'] ?? '';

// Handle update skor
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_skor'])) {
    $conn->begin_transaction();
    try {
        $stmt_update = $conn->prepare("UPDATE partisipasi_siswa SET skor = ? WHERE id = ? AND id_ujian = ?");
        foreach ($_POST['skor'] as $id_partisipasi => $skor) {
            $skor_val = !empty($skor) ? floatval($skor) : null;
            $stmt_update->bind_param("dii", $skor_val, $id_partisipasi, $id_ujian);
            $stmt_update->execute();
        }
        $stmt_update->close();
        $conn->commit();
        $success_msg = "Skor berhasil diperbarui!";
    } catch (Exception $e) {
        $conn->rollback();
        $error_msg = "Gagal memperbarui skor: " . $e->getMessage();
    }
}

// Handle delete partisipasi
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['partisipasi_id']) && is_numeric($_GET['partisipasi_id'])) {
    $id_partisipasi_to_delete = $_GET['partisipasi_id'];

    $conn->begin_transaction();
    try {
        // First, delete related answers
        $stmt_delete_answers = $conn->prepare("DELETE FROM jawaban_siswa WHERE id_partisipasi = ?");
        $stmt_delete_answers->bind_param("i", $id_partisipasi_to_delete);
        $stmt_delete_answers->execute();
        $stmt_delete_answers->close();

        // Then, delete the participation record
        $stmt_delete_partisipasi = $conn->prepare("DELETE FROM partisipasi_siswa WHERE id = ? AND id_ujian = ?");
        $stmt_delete_partisipasi->bind_param("ii", $id_partisipasi_to_delete, $id_ujian);
        $stmt_delete_partisipasi->execute();

        if ($stmt_delete_partisipasi->affected_rows > 0) {
            $conn->commit();
            $success_msg = "Data partisipasi siswa berhasil dihapus!";
            // Redirect to clean the URL after deletion
            header("Location: report.php?id=" . $id_ujian . (!empty($filter_kelas) ? '&kelas=' . urlencode($filter_kelas) : ''));
            exit;
        } else {
            $conn->rollback();
            $error_msg = "Gagal menghapus data partisipasi atau data tidak ditemukan.";
        }
        $stmt_delete_partisipasi->close();
    } catch (Exception $e) {
        $conn->rollback();
        $error_msg = "Gagal menghapus data partisipasi: " . $e->getMessage();
    }
}


// Ambil data ujian
$stmt_ujian = $conn->prepare("SELECT judul, kode_ujian FROM ujian WHERE id = ? AND id_guru = ?");
$stmt_ujian->bind_param("ii", $id_ujian, $id_guru);
$stmt_ujian->execute();
$result_ujian = $stmt_ujian->get_result();
if ($result_ujian->num_rows == 0) {
    header("Location: dashboard.php"); exit;
}
$ujian = $result_ujian->fetch_assoc();
$stmt_ujian->close();

// Ambil daftar kelas yang unik untuk filter
$stmt_kelas = $conn->prepare("SELECT DISTINCT kelas_siswa FROM partisipasi_siswa WHERE id_ujian = ? ORDER BY kelas_siswa ASC");
$stmt_kelas->bind_param("i", $id_ujian);
$stmt_kelas->execute();
$result_kelas = $stmt_kelas->get_result();
$daftar_kelas = [];
while ($row_kelas = $result_kelas->fetch_assoc()) {
    $daftar_kelas[] = $row_kelas['kelas_siswa'];
}
$stmt_kelas->close();

// Ambil data partisipasi siswa dengan filter
$sql_partisipasi = "SELECT id, nama_siswa, kelas_siswa, waktu_selesai, skor FROM partisipasi_siswa WHERE id_ujian = ?";
if (!empty($filter_kelas)) {
    $sql_partisipasi .= " AND kelas_siswa = ?";
}
$sql_partisipasi .= " ORDER BY skor DESC, nama_siswa ASC";

$stmt_partisipasi = $conn->prepare($sql_partisipasi);
if (!empty($filter_kelas)) {
    $stmt_partisipasi->bind_param("is", $id_ujian, $filter_kelas);
} else {
    $stmt_partisipasi->bind_param("i", $id_ujian);
}
$stmt_partisipasi->execute();
$result_partisipasi = $stmt_partisipasi->get_result();
?>
<title>Laporan Ujian: <?php echo htmlspecialchars($ujian['judul']); ?></title>
<script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>

<div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4 mb-6">
    <div>
        <h2 class="text-3xl font-bold text-slate-900">Laporan Hasil Ujian</h2>
        <p class="text-slate-500 mt-1">Ujian: <span class="font-semibold text-slate-700"><?php echo htmlspecialchars($ujian['judul']); ?></span></p>
    </div>
    <div class="flex flex-col sm:flex-row gap-2">
        <button id="exportButton" class="flex items-center justify-center space-x-2 bg-green-600 text-white font-semibold px-5 py-3 rounded-lg hover:bg-green-700 transition-all shadow-md w-full sm:w-auto"><i data-lucide="file-spreadsheet"></i><span>Export ke Excel</span></button>
    </div>
</div>

<?php if (isset($success_msg)): ?>
<div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-md" role="alert"><p><?php echo $success_msg; ?></p></div>
<?php endif; ?>
<?php if (isset($error_msg)): ?>
<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-md" role="alert"><p><?php echo $error_msg; ?></p></div>
<?php endif; ?>

<div class="bg-white p-4 rounded-2xl shadow-lg mb-6">
    <form action="report.php" method="get" class="flex flex-col sm:flex-row items-center gap-4">
        <input type="hidden" name="id" value="<?php echo $id_ujian; ?>">
        <div class="w-full sm:w-auto sm:flex-1">
            <label for="kelas" class="block text-sm font-medium text-slate-700 mb-1">Filter Berdasarkan Kelas</label>
            <select name="kelas" id="kelas" class="w-full p-2 border border-slate-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                <option value="">-- Tampilkan Semua Kelas --</option>
                <?php foreach ($daftar_kelas as $kelas): ?>
                    <option value="<?php echo htmlspecialchars($kelas); ?>" <?php echo ($filter_kelas == $kelas) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($kelas); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="w-full sm:w-auto bg-blue-600 text-white font-semibold px-5 py-2.5 rounded-lg hover:bg-blue-700 transition-all shadow-md mt-2 sm:mt-0 self-end"><i data-lucide="filter" class="inline-block mr-2 -mt-1 h-5 w-5"></i>Filter</button>
    </form>
</div>


<form action="report.php?id=<?php echo $id_ujian; ?><?php if(!empty($filter_kelas)) echo '&kelas='.urlencode($filter_kelas); ?>" method="post">
    <div class="bg-white p-2 sm:p-6 rounded-2xl shadow-lg overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200" id="reportTable">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">No</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Nama Siswa (Klik untuk Detail)</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Kelas</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Waktu Selesai</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider w-32">Skor</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Aksi</th> </tr>
            </thead>
            <tbody class="bg-white divide-y divide-slate-200">
                <?php if ($result_partisipasi->num_rows > 0): ?>
                    <?php $no = 1; while($row = $result_partisipasi->fetch_assoc()): ?>
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4 text-sm font-medium text-slate-900"><?php echo $no++; ?></td>
                            <td class="px-6 py-4 text-sm text-slate-700 font-semibold">
                                <a href="view_answers.php?partisipasi_id=<?php echo $row['id']; ?>" class="hover:text-blue-600 hover:underline" title="Lihat Detail Jawaban">
                                    <?php echo htmlspecialchars($row['nama_siswa']); ?>
                                </a>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-500"><?php echo htmlspecialchars($row['kelas_siswa']); ?></td>
                            <td class="px-6 py-4 text-sm text-slate-500"><?php echo $row['waktu_selesai'] ? date('d M Y, H:i', strtotime($row['waktu_selesai'])) : '<span class="text-yellow-600 font-semibold">Belum Selesai</span>'; ?></td>
                            <td class="px-6 py-4 text-sm">
                                <input type="number" name="skor[<?php echo $row['id']; ?>]" value="<?php echo $row['skor'] !== null ? number_format($row['skor'], 2, '.', '') : ''; ?>" step="0.01" min="0" max="100" class="w-28 p-2 border border-slate-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <button type="button" onclick="confirmDelete(<?php echo $row['id']; ?>)" class="text-red-600 hover:text-red-800 transition-colors">
                                    <i data-lucide="trash-2" class="w-5 h-5"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="text-center py-16 text-slate-500"><i data-lucide="users-2" class="mx-auto h-12 w-12 text-slate-400"></i><h4 class="mt-4 text-lg font-semibold">Belum Ada Peserta</h4><p class="text-sm mt-1">Tidak ada data untuk ditampilkan pada kelas yang dipilih.</p></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($result_partisipasi->num_rows > 0): ?>
    <div class="mt-6 text-right">
        <button type="submit" name="update_skor" class="bg-blue-600 text-white font-semibold px-6 py-3 rounded-lg hover:bg-blue-700 transition-all shadow-md"><i data-lucide="save" class="inline-block mr-2 -mt-1"></i> Simpan Perubahan Skor</button>
    </div>
    <?php endif; ?>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('exportButton')?.addEventListener('click', function() {
        const table = document.getElementById('reportTable');
        const tableClone = table.cloneNode(true);

        // Remove the 'Aksi' column (last column) from the cloned table
        tableClone.querySelectorAll('thead th:last-child, tbody td:last-child').forEach(cell => {
            cell.remove();
        });

        // Replace input with its value for export
        tableClone.querySelectorAll('input[type="number"]').forEach(input => {
            const cell = input.parentElement;
            cell.innerText = input.value;
        });

        // Change link to plain text
        tableClone.querySelectorAll('a').forEach(link => {
            const cell = link.parentElement;
            cell.innerText = link.innerText;
        });

        // Update header text
        const header = tableClone.querySelector('thead th:nth-child(2)');
        if(header) header.innerText = 'Nama Siswa';

        const wb = XLSX.utils.table_to_book(tableClone, {sheet: "Hasil Ujian"});
        const examTitle = "<?php echo preg_replace('/[^A-Za-z0-9\-\_ ]/', '', $ujian['judul']); ?>";
        XLSX.writeFile(wb, `Laporan Ujian - ${examTitle}.xlsx`);
    });
});

function confirmDelete(partisipasiId) {
    if (confirm('Apakah Anda yakin ingin menghapus data partisipasi siswa ini? Semua jawaban terkait juga akan dihapus.')) {
        window.location.href = 'report.php?id=<?php echo $id_ujian; ?>&action=delete&partisipasi_id=' + partisipasiId + '<?php if(!empty($filter_kelas)) echo '&kelas='.urlencode($filter_kelas); ?>';
    }
}
</script>

<?php include 'footer.php'; ?>