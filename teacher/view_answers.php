<?php
require_once '../db.php';
include 'header.php';

// 1. Validasi Input & Keamanan
if (!isset($_GET['partisipasi_id']) || !is_numeric($_GET['partisipasi_id'])) {
    header("Location: dashboard.php"); exit;
}
$partisipasi_id = $_GET['partisipasi_id'];
$id_guru = $_SESSION['id_guru'];

// 2. Ambil data partisipasi, ujian, dan pastikan guru berhak mengakses
$stmt_partisipasi = $conn->prepare("
    SELECT ps.nama_siswa, ps.kelas_siswa, ps.skor, ps.waktu_selesai, u.judul AS judul_ujian, u.id AS id_ujian
    FROM partisipasi_siswa ps
    JOIN ujian u ON ps.id_ujian = u.id
    WHERE ps.id = ? AND u.id_guru = ?
");
$stmt_partisipasi->bind_param("ii", $partisipasi_id, $id_guru);
$stmt_partisipasi->execute();
$result_partisipasi = $stmt_partisipasi->get_result();

if ($result_partisipasi->num_rows == 0) {
    // Jika tidak ada hasil, berarti partisipasi ini tidak ada atau bukan milik guru yg login
    echo "<p class='text-center text-red-500'>Data tidak ditemukan atau Anda tidak memiliki hak akses.</p>";
    include 'footer.php';
    exit;
}
$partisipasi = $result_partisipasi->fetch_assoc();
$id_ujian = $partisipasi['id_ujian'];
$stmt_partisipasi->close();

// 3. Ambil semua pertanyaan dan jawaban siswa untuk ujian ini
$stmt_jawaban = $conn->prepare("
    SELECT
        p.id AS id_pertanyaan,
        p.teks_pertanyaan,
        p.tipe_soal,
        p.skor AS skor_pertanyaan,
        js.id_opsi_jawaban AS id_opsi_dipilih,
        js.jawaban_essay
    FROM pertanyaan p
    LEFT JOIN jawaban_siswa js ON p.id = js.id_pertanyaan AND js.id_partisipasi = ?
    WHERE p.id_ujian = ?
    ORDER BY p.id ASC
");
$stmt_jawaban->bind_param("ii", $partisipasi_id, $id_ujian);
$stmt_jawaban->execute();
$result_jawaban = $stmt_jawaban->get_result();
$jawaban_siswa = [];
while ($row = $result_jawaban->fetch_assoc()) {
    $jawaban_siswa[$row['id_pertanyaan']] = $row;
}
$stmt_jawaban->close();

// 4. Pre-fetch semua opsi jawaban untuk ujian ini agar lebih efisien
$stmt_opsi = $conn->prepare("
    SELECT o.id, o.id_pertanyaan, o.teks_opsi, o.adalah_jawaban_benar
    FROM opsi_jawaban o
    JOIN pertanyaan p ON o.id_pertanyaan = p.id
    WHERE p.id_ujian = ?
");
$stmt_opsi->bind_param("i", $id_ujian);
$stmt_opsi->execute();
$result_opsi = $stmt_opsi->get_result();
$semua_opsi = [];
while ($row = $result_opsi->fetch_assoc()) {
    $semua_opsi[$row['id_pertanyaan']][] = $row;
}
$stmt_opsi->close();

?>
<title>Detail Jawaban: <?php echo htmlspecialchars($partisipasi['nama_siswa']); ?></title>

<div class="mb-8">
    <a href="report.php?id=<?php echo $id_ujian; ?>" class="flex items-center gap-2 text-slate-600 hover:text-blue-600 font-medium transition-colors">
        <i data-lucide="arrow-left"></i>
        Kembali ke Laporan
    </a>
    <h2 class="text-3xl font-bold text-slate-900 mt-4">Detail Jawaban Siswa</h2>
    <div class="mt-2 text-slate-600 border-l-4 border-blue-500 pl-4 py-2 bg-blue-50 rounded-r-lg">
        <p><strong>Ujian:</strong> <?php echo htmlspecialchars($partisipasi['judul_ujian']); ?></p>
        <p><strong>Siswa:</strong> <?php echo htmlspecialchars($partisipasi['nama_siswa']); ?> (<?php echo htmlspecialchars($partisipasi['kelas_siswa']); ?>)</p>
        <p><strong>Skor Akhir:</strong> <span class="font-bold text-2xl text-blue-600"><?php echo number_format($partisipasi['skor'] ?? 0, 2); ?></span></p>
    </div>
</div>

<div class="space-y-6">
    <?php $no = 1; foreach ($jawaban_siswa as $id_pertanyaan => $detail_jawaban): ?>
        <div class="bg-white p-6 rounded-2xl shadow-lg border-l-4 <?php
            $is_correct = false;
            if ($detail_jawaban['tipe_soal'] == 'pilihan_ganda' || $detail_jawaban['tipe_soal'] == 'pilihan_ganda_gambar') {
                if (isset($semua_opsi[$id_pertanyaan])) {
                    foreach ($semua_opsi[$id_pertanyaan] as $opsi) {
                        if ($opsi['adalah_jawaban_benar'] && $opsi['id'] == $detail_jawaban['id_opsi_dipilih']) {
                            $is_correct = true;
                            break;
                        }
                    }
                }
            }
            // Untuk essay, kita anggap netral karena perlu dinilai manual
            echo $is_correct ? 'border-green-500' : 'border-red-500';
        ?>">
            <div class="flex justify-between items-start">
                <p class="text-lg font-semibold text-slate-800 mb-4">Pertanyaan #<?php echo $no++; ?></p>
                <span class="text-sm font-bold px-3 py-1 rounded-full <?php echo $is_correct ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                    <?php echo $is_correct ? 'Benar' : 'Salah'; ?>
                </span>
            </div>
            <div class="prose max-w-none text-slate-700">
                <?php echo nl2br(htmlspecialchars($detail_jawaban['teks_pertanyaan'])); ?>
            </div>

            <hr class="my-4">

            <div class="mt-4">
                <?php if ($detail_jawaban['tipe_soal'] == 'pilihan_ganda' || $detail_jawaban['tipe_soal'] == 'pilihan_ganda_gambar'): ?>
                    <h4 class="font-semibold text-slate-600 mb-2">Jawaban:</h4>
                    <ul class="space-y-2">
                        <?php if (isset($semua_opsi[$id_pertanyaan])): ?>
                            <?php foreach ($semua_opsi[$id_pertanyaan] as $opsi):
                                $is_student_choice = ($opsi['id'] == $detail_jawaban['id_opsi_dipilih']);
                                $is_correct_answer = $opsi['adalah_jawaban_benar'];
                                
                                $class = 'flex items-start gap-3 p-3 rounded-lg border-2 ';
                                $icon = '';

                                if ($is_correct_answer) {
                                    $class .= 'bg-green-50 border-green-500 text-green-900';
                                    $icon = '<i data-lucide="check-circle-2" class="w-5 h-5 text-green-600 flex-shrink-0"></i>';
                                } elseif ($is_student_choice && !$is_correct_answer) {
                                    $class .= 'bg-red-50 border-red-500 text-red-900';
                                    $icon = '<i data-lucide="x-circle" class="w-5 h-5 text-red-600 flex-shrink-0"></i>';
                                } else {
                                    $class .= 'bg-slate-50 border-slate-200';
                                }
                            ?>
                                <li class="<?php echo $class; ?>">
                                    <?php echo $icon; ?>
                                    <span><?php echo htmlspecialchars($opsi['teks_opsi']); ?></span>
                                    <?php if ($is_student_choice && !$is_correct_answer): ?>
                                        <span class="ml-auto text-xs font-bold text-red-700">(Pilihan Siswa)</span>
                                    <?php elseif ($is_correct_answer && !$is_student_choice): ?>
                                        <span class="ml-auto text-xs font-bold text-green-700">(Jawaban Benar)</span>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                <?php elseif ($detail_jawaban['tipe_soal'] == 'essay'): ?>
                    <h4 class="font-semibold text-slate-600 mb-2">Jawaban Essay Siswa:</h4>
                    <div class="bg-slate-50 p-4 rounded-lg border border-slate-200">
                        <p class="text-slate-800"><?php echo nl2br(htmlspecialchars($detail_jawaban['jawaban_essay'] ?: 'Siswa tidak menjawab.')); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php include 'footer.php'; ?>
