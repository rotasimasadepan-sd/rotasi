<?php
// 1. Mulai session dan cek login SEBELUM output apapun
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION['id_guru'])) {
    header("location: ../login.php");
    exit;
}

// 2. Hubungkan ke DB dan validasi input
require_once '../db.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: dashboard.php");
    exit;
}
$id_ujian = $_GET['id'];
$id_guru = $_SESSION['id_guru'];

// 3. Logika pemrosesan form (tambah & hapus soal)
// Handle form tambah soal
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['tambah_soal'])) {
    $conn->begin_transaction();
    try {
        $teks_pertanyaan = trim($_POST['teks_pertanyaan']);
        $tipe_soal = $_POST['tipe_soal'];
        $skor = intval($_POST['skor']);
        $upload_dir = '../uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

        $stmt_soal = $conn->prepare("INSERT INTO pertanyaan (id_ujian, tipe_soal, teks_pertanyaan, skor) VALUES (?, ?, ?, ?)");
        $stmt_soal->bind_param("issi", $id_ujian, $tipe_soal, $teks_pertanyaan, $skor);
        $stmt_soal->execute();
        $id_pertanyaan_baru = $stmt_soal->insert_id;
        $stmt_soal->close();

        if ($tipe_soal == 'pilihan_ganda') {
            $opsi = $_POST['opsi'];
            $jawaban_benar_idx = $_POST['jawaban_benar'];
            $stmt_opsi = $conn->prepare("INSERT INTO opsi_jawaban (id_pertanyaan, teks_opsi, adalah_jawaban_benar) VALUES (?, ?, ?)");
            foreach ($opsi as $index => $teks_opsi) {
                if (!empty(trim($teks_opsi))) {
                    $is_correct = ($index == $jawaban_benar_idx);
                    $stmt_opsi->bind_param("isi", $id_pertanyaan_baru, $teks_opsi, $is_correct);
                    $stmt_opsi->execute();
                }
            }
            $stmt_opsi->close();
        } elseif ($tipe_soal == 'pilihan_ganda_gambar') {
            $jawaban_benar_idx = $_POST['jawaban_benar_gambar'];
            $stmt_opsi = $conn->prepare("INSERT INTO opsi_jawaban (id_pertanyaan, gambar_opsi, adalah_jawaban_benar) VALUES (?, ?, ?)");
            foreach ($_FILES['opsi_gambar']['name'] as $index => $name) {
                if ($_FILES['opsi_gambar']['error'][$index] == UPLOAD_ERR_OK) {
                    $tmp_name = $_FILES['opsi_gambar']['tmp_name'][$index];
                    $new_filename = uniqid('opt_', true) . '.' . strtolower(pathinfo($name, PATHINFO_EXTENSION));
                    if (move_uploaded_file($tmp_name, $upload_dir . $new_filename)) {
                        $is_correct = ($index == $jawaban_benar_idx);
                        $stmt_opsi->bind_param("isi", $id_pertanyaan_baru, $new_filename, $is_correct);
                        $stmt_opsi->execute();
                    }
                }
            }
            $stmt_opsi->close();
        }

        if (isset($_FILES['gambar_soal']) && !empty($_FILES['gambar_soal']['name'][0])) {
            $stmt_img = $conn->prepare("INSERT INTO gambar_pertanyaan (id_pertanyaan, nama_file) VALUES (?, ?)");
            foreach ($_FILES['gambar_soal']['name'] as $key => $name) {
                if ($_FILES['gambar_soal']['error'][$key] == UPLOAD_ERR_OK) {
                    $tmp_name = $_FILES['gambar_soal']['tmp_name'][$key];
                    $new_filename = uniqid('img_', true) . '.' . strtolower(pathinfo($name, PATHINFO_EXTENSION));
                    if (move_uploaded_file($tmp_name, $upload_dir . $new_filename)) {
                        $stmt_img->bind_param("is", $id_pertanyaan_baru, $new_filename);
                        $stmt_img->execute();
                    }
                }
            }
            $stmt_img->close();
        }

        $conn->commit();
        header("Location: manage_exam.php?id=$id_ujian&status=question_added");
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Gagal menambahkan soal: " . $e->getMessage();
    }
}

// Handle hapus soal
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['qid'])) {
    $id_pertanyaan_hapus = $_GET['qid'];
    $conn->begin_transaction();
    try {
        // Hapus gambar opsi
        $stmt_find_opsi_img = $conn->prepare("SELECT gambar_opsi FROM opsi_jawaban WHERE id_pertanyaan = ? AND gambar_opsi IS NOT NULL");
        $stmt_find_opsi_img->bind_param("i", $id_pertanyaan_hapus);
        $stmt_find_opsi_img->execute();
        $opsi_img_result = $stmt_find_opsi_img->get_result();
        while($img_row = $opsi_img_result->fetch_assoc()){
            @unlink('../uploads/' . $img_row['gambar_opsi']);
        }
        $stmt_find_opsi_img->close();

        // Hapus gambar pertanyaan
        $stmt_find_img = $conn->prepare("SELECT nama_file FROM gambar_pertanyaan WHERE id_pertanyaan = ?");
        $stmt_find_img->bind_param("i", $id_pertanyaan_hapus);
        $stmt_find_img->execute();
        $img_result = $stmt_find_img->get_result();
        while($img_row = $img_result->fetch_assoc()){
            @unlink('../uploads/' . $img_row['nama_file']);
        }
        $stmt_find_img->close();

        // Hapus soal (akan cascade ke opsi dan gambar pertanyaan)
        $stmt_delete = $conn->prepare("DELETE FROM pertanyaan WHERE id = ? AND id_ujian = ?");
        $stmt_delete->bind_param("ii", $id_pertanyaan_hapus, $id_ujian);
        $stmt_delete->execute();
        $stmt_delete->close();
        
        $conn->commit();
        header("Location: manage_exam.php?id=$id_ujian&status=question_deleted");
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Gagal menghapus soal: " . $e->getMessage();
    }
}

// 4. Ambil data untuk ditampilkan
// Ambil detail ujian
$stmt_ujian = $conn->prepare("SELECT * FROM ujian WHERE id = ? AND id_guru = ?");
$stmt_ujian->bind_param("ii", $id_ujian, $id_guru);
$stmt_ujian->execute();
$result_ujian = $stmt_ujian->get_result();
if ($result_ujian->num_rows == 0) {
    header("Location: dashboard.php");
    exit;
}
$ujian = $result_ujian->fetch_assoc();
$stmt_ujian->close();

// Ambil data soal dan gambar
$list_soal = $conn->query("SELECT * FROM pertanyaan WHERE id_ujian = $id_ujian ORDER BY id ASC");
$images_by_question = [];
$result_imgs = $conn->query("SELECT gp.id_pertanyaan, gp.nama_file FROM gambar_pertanyaan gp JOIN pertanyaan p ON gp.id_pertanyaan = p.id WHERE p.id_ujian = $id_ujian");
while($img = $result_imgs->fetch_assoc()){
    $images_by_question[$img['id_pertanyaan']][] = $img['nama_file'];
}

// 5. Baru panggil header.php setelah semua logika selesai
include 'header.php';
?>
<title>Kelola Ujian: <?php echo htmlspecialchars($ujian['judul']); ?></title>

<div class="flex justify-between items-center mb-8">
    <div>
        <h2 class="text-3xl font-bold text-slate-900">Kelola Ujian</h2>
        <p class="text-slate-500 mt-1">Ujian: <span class="font-semibold text-slate-700"><?php echo htmlspecialchars($ujian['judul']); ?></span></p>
    </div>
    <a href="dashboard.php" class="text-sm font-medium text-blue-600 hover:text-blue-800 flex items-center gap-1"><i data-lucide="arrow-left" class="w-4 h-4"></i> Kembali</a>
</div>

<!-- Form Tambah Soal -->
<div class="bg-white p-8 rounded-2xl shadow-lg mb-10" id="form-tambah-soal">
    <h3 class="text-2xl font-bold text-slate-800 mb-6">Tambah Soal Baru</h3>
    <?php if(isset($error)) echo "<div class='bg-red-100 text-red-700 p-4 rounded-md mb-4'>$error</div>"; ?>
    <form action="manage_exam.php?id=<?php echo $id_ujian; ?>" method="post" enctype="multipart/form-data" class="space-y-6">
        <div>
            <label for="teks_pertanyaan" class="block text-sm font-medium text-slate-700">Teks Pertanyaan</label>
            <textarea name="teks_pertanyaan" id="teks_pertanyaan" class="mt-1 block w-full border border-slate-300 rounded-md shadow-sm p-3 focus:ring-blue-500 focus:border-blue-500" rows="4" required></textarea>
        </div>
        <div>
            <label for="gambar_soal" class="block text-sm font-medium text-slate-700">Gambar Pendukung Soal (Opsional)</label>
            <input type="file" name="gambar_soal[]" id="gambar_soal" multiple class="mt-1 block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="tipe_soal" class="block text-sm font-medium text-slate-700">Tipe Soal</label>
                <select name="tipe_soal" id="tipe_soal" class="mt-1 block w-full border border-slate-300 rounded-md shadow-sm p-3 focus:ring-blue-500 focus:border-blue-500">
                    <option value="pilihan_ganda">Pilihan Ganda (Teks)</option>
                    <option value="pilihan_ganda_gambar">Pilihan Ganda (Gambar)</option>
                    <option value="essay">Essay</option>
                </select>
            </div>
            <div>
                <label for="skor" class="block text-sm font-medium text-slate-700">Skor Soal</label>
                <input type="number" name="skor" id="skor" class="mt-1 block w-full border border-slate-300 rounded-md shadow-sm p-3 focus:ring-blue-500 focus:border-blue-500" value="10" required min="1">
            </div>
        </div>
        
        <!-- Opsi Jawaban Teks -->
        <div id="pilihan-ganda-container" class="space-y-3 pt-2">
            <label class="block text-sm font-medium text-slate-700">Opsi Jawaban (Teks)</label>
            <div id="options-wrapper" class="space-y-2"></div>
            <button type="button" id="add-option-btn" class="text-sm font-medium text-blue-600 hover:text-blue-800 flex items-center gap-1 mt-2"><i data-lucide="plus-circle" class="w-4 h-4"></i> Tambah Opsi</button>
        </div>

        <!-- Opsi Jawaban Gambar -->
        <div id="pilihan-ganda-gambar-container" class="space-y-3 pt-2" style="display: none;">
            <label class="block text-sm font-medium text-slate-700">Opsi Jawaban (Gambar)</label>
            <div id="options-image-wrapper" class="space-y-3"></div>
            <button type="button" id="add-image-option-btn" class="text-sm font-medium text-blue-600 hover:text-blue-800 flex items-center gap-1 mt-2"><i data-lucide="plus-circle" class="w-4 h-4"></i> Tambah Opsi Gambar</button>
        </div>

        <div class="pt-4">
            <button type="submit" name="tambah_soal" class="w-full flex justify-center items-center py-3 px-4 border border-transparent rounded-md shadow-sm text-lg font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                <i data-lucide="plus" class="mr-2 h-5 w-5"></i> Tambahkan Soal
            </button>
        </div>
    </form>
</div>

<!-- Daftar Soal -->
<div class="bg-white p-8 rounded-2xl shadow-lg">
    <h3 class="text-2xl font-bold text-slate-800 mb-6">Daftar Soal (<?php echo $list_soal->num_rows; ?>)</h3>
    <div class="space-y-4">
        <?php if ($list_soal->num_rows > 0): ?>
            <?php $no = 1; while($soal = $list_soal->fetch_assoc()): ?>
            <div class="border border-slate-200 rounded-lg p-5">
                <div class="flex justify-between items-start">
                    <div class="prose prose-slate max-w-none flex-grow">
                        <p class="font-semibold text-slate-800"><?php echo $no++; ?>. <?php echo nl2br(htmlspecialchars($soal['teks_pertanyaan'])); ?></p>
                        
                        <?php if (isset($images_by_question[$soal['id']])): ?>
                            <div class="flex flex-wrap gap-2 mt-3">
                                <?php foreach ($images_by_question[$soal['id']] as $img_file): ?>
                                    <img src="../uploads/<?php echo htmlspecialchars($img_file); ?>" class="h-20 w-20 object-cover rounded-md border border-slate-200">
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <div class="mt-3 flex items-center gap-2 text-xs">
                            <span class="font-semibold uppercase px-2 py-1 rounded-full <?php 
                                switch($soal['tipe_soal']) {
                                    case 'pilihan_ganda': echo 'bg-sky-100 text-sky-800'; break;
                                    case 'pilihan_ganda_gambar': echo 'bg-violet-100 text-violet-800'; break;
                                    case 'essay': echo 'bg-amber-100 text-amber-800'; break;
                                }
                            ?>">
                                <?php echo str_replace('_', ' ', $soal['tipe_soal']); ?>
                            </span>
                            <span class="font-bold uppercase px-2 py-1 rounded-full bg-slate-100 text-slate-600">
                                SKOR: <?php echo htmlspecialchars($soal['skor']); ?>
                            </span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0 ml-4">
                        <a href="edit_question.php?id=<?php echo $soal['id']; ?>" class="p-2 text-slate-500 hover:text-blue-600 hover:bg-slate-100 rounded-md" title="Edit Soal"><i data-lucide="edit-3" class="w-4 h-4"></i></a>
                        <a href="manage_exam.php?id=<?php echo $id_ujian; ?>&action=delete&qid=<?php echo $soal['id']; ?>" onclick="return confirm('Anda yakin ingin menghapus soal ini? Semua data terkait (gambar, opsi, jawaban siswa) akan ikut terhapus.')" class="p-2 text-slate-500 hover:text-red-600 hover:bg-slate-100 rounded-md" title="Hapus Soal"><i data-lucide="trash-2" class="w-4 h-4"></i></a>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="text-center text-slate-500 py-10">Belum ada soal ditambahkan.</p>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tipeSoalSelect = document.getElementById('tipe_soal');
    const pgContainer = document.getElementById('pilihan-ganda-container');
    const pgImgContainer = document.getElementById('pilihan-ganda-gambar-container');
    const optionsWrapper = document.getElementById('options-wrapper');
    const optionsImgWrapper = document.getElementById('options-image-wrapper');
    const addOptionBtn = document.getElementById('add-option-btn');
    const addImgOptionBtn = document.getElementById('add-image-option-btn');

    function toggleContainers() {
        const type = tipeSoalSelect.value;
        pgContainer.style.display = type === 'pilihan_ganda' ? 'block' : 'none';
        pgImgContainer.style.display = type === 'pilihan_ganda_gambar' ? 'block' : 'none';
        
        pgContainer.querySelectorAll('input').forEach(i => i.required = type === 'pilihan_ganda');
        pgImgContainer.querySelectorAll('input').forEach(i => i.required = type === 'pilihan_ganda_gambar');
    }

    function createOptionInput(index, isChecked = false) {
        const div = document.createElement('div');
        div.className = 'flex items-center gap-2';
        div.innerHTML = `
            <input type="radio" name="jawaban_benar" value="${index}" class="h-4 w-4 text-blue-600 border-slate-300 focus:ring-blue-500" ${isChecked ? 'checked' : ''} required>
            <input type="text" name="opsi[]" class="flex-grow border border-slate-300 rounded-md shadow-sm p-2 text-sm" placeholder="Teks Opsi ${index + 1}" required>
            <button type="button" class="remove-option-btn text-slate-400 hover:text-red-600"><i data-lucide="x-circle" class="h-5 w-5"></i></button>
        `;
        optionsWrapper.appendChild(div);
        lucide.createIcons({ nodes: [div.querySelector('.remove-option-btn')] });
    }

    function createImageOptionInput(index, isChecked = false) {
        const div = document.createElement('div');
        div.className = 'flex items-center gap-3 p-2 border rounded-md';
        div.innerHTML = `
            <input type="radio" name="jawaban_benar_gambar" value="${index}" class="h-4 w-4 text-blue-600 border-slate-300 focus:ring-blue-500" ${isChecked ? 'checked' : ''} required>
            <input type="file" name="opsi_gambar[]" class="flex-grow text-sm text-slate-500 file:mr-4 file:py-1 file:px-3 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" required>
            <button type="button" class="remove-option-btn text-slate-400 hover:text-red-600"><i data-lucide="x-circle" class="h-5 w-5"></i></button>
        `;
        optionsImgWrapper.appendChild(div);
        lucide.createIcons({ nodes: [div.querySelector('.remove-option-btn')] });
    }

    function setupEventListeners(wrapper, addBtn, createFn, minItems) {
        addBtn.addEventListener('click', () => createFn(wrapper.children.length));
        wrapper.addEventListener('click', e => {
            const removeBtn = e.target.closest('.remove-option-btn');
            if (removeBtn) {
                if (wrapper.children.length > minItems) {
                    removeBtn.parentElement.remove();
                    // Update indices for radio buttons
                    wrapper.querySelectorAll('input[type="radio"]').forEach((radio, i) => radio.value = i);
                } else {
                    alert(`Minimal harus ada ${minItems} opsi.`);
                }
            }
        });
    }

    tipeSoalSelect.addEventListener('change', toggleContainers);
    
    setupEventListeners(optionsWrapper, addOptionBtn, createOptionInput, 2);
    setupEventListeners(optionsImgWrapper, addImgOptionBtn, createImageOptionInput, 2);

    // Initial state
    for(let i=0; i<4; i++) createOptionInput(i, i === 0);
    for(let i=0; i<4; i++) createImageOptionInput(i, i === 0);
    toggleContainers();
});
</script>

<?php include 'footer.php'; ?>
