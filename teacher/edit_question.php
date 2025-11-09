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
    header("Location: dashboard.php"); exit;
}
$id_pertanyaan = $_GET['id'];
$id_guru = $_SESSION['id_guru'];

// 3. Verifikasi bahwa soal ini milik guru yang login
$stmt_verify = $conn->prepare("SELECT p.*, u.id_guru FROM pertanyaan p JOIN ujian u ON p.id_ujian = u.id WHERE p.id = ?");
$stmt_verify->bind_param("i", $id_pertanyaan);
$stmt_verify->execute();
$result_verify = $stmt_verify->get_result();
if ($result_verify->num_rows == 0) {
    header("Location: dashboard.php"); exit;
}
$soal = $result_verify->fetch_assoc();
if ($soal['id_guru'] != $id_guru) {
    header("Location: dashboard.php"); exit;
}
$id_ujian = $soal['id_ujian'];
$stmt_verify->close();

// 4. Logika pemrosesan form (update soal)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_soal'])) {
    $conn->begin_transaction();
    try {
        $teks_pertanyaan = trim($_POST['teks_pertanyaan']);
        $tipe_soal = $_POST['tipe_soal'];
        $skor = intval($_POST['skor']);
        $upload_dir = '../uploads/';

        // Update pertanyaan utama
        $stmt_update_soal = $conn->prepare("UPDATE pertanyaan SET teks_pertanyaan = ?, tipe_soal = ?, skor = ? WHERE id = ?");
        $stmt_update_soal->bind_param("ssii", $teks_pertanyaan, $tipe_soal, $skor, $id_pertanyaan);
        $stmt_update_soal->execute();
        $stmt_update_soal->close();

        // Hapus gambar pendukung lama jika dihapus
        if (isset($_POST['hapus_gambar'])) {
            foreach ($_POST['hapus_gambar'] as $gambar_hapus) {
                $stmt_hapus_gambar = $conn->prepare("DELETE FROM gambar_pertanyaan WHERE id_pertanyaan = ? AND nama_file = ?");
                $stmt_hapus_gambar->bind_param("is", $id_pertanyaan, $gambar_hapus);
                $stmt_hapus_gambar->execute();
                $stmt_hapus_gambar->close();
                @unlink($upload_dir . $gambar_hapus);
            }
        }

        // Upload gambar pendukung baru
        if (isset($_FILES['gambar_soal']) && !empty($_FILES['gambar_soal']['name'][0])) {
            $stmt_img = $conn->prepare("INSERT INTO gambar_pertanyaan (id_pertanyaan, nama_file) VALUES (?, ?)");
            foreach ($_FILES['gambar_soal']['name'] as $key => $name) {
                if ($_FILES['gambar_soal']['error'][$key] == UPLOAD_ERR_OK) {
                    $tmp_name = $_FILES['gambar_soal']['tmp_name'][$key];
                    $new_filename = uniqid('img_', true) . '.' . strtolower(pathinfo($name, PATHINFO_EXTENSION));
                    if (move_uploaded_file($tmp_name, $upload_dir . $new_filename)) {
                        $stmt_img->bind_param("is", $id_pertanyaan, $new_filename);
                        $stmt_img->execute();
                    }
                }
            }
            $stmt_img->close();
        }

        // Hapus opsi lama
        $stmt_del_opsi = $conn->prepare("DELETE FROM opsi_jawaban WHERE id_pertanyaan = ?");
        $stmt_del_opsi->bind_param("i", $id_pertanyaan);
        $stmt_del_opsi->execute();
        $stmt_del_opsi->close();

        if ($tipe_soal == 'pilihan_ganda') {
            $opsi = $_POST['opsi'];
            $jawaban_benar_idx = $_POST['jawaban_benar'];
            $stmt_opsi = $conn->prepare("INSERT INTO opsi_jawaban (id_pertanyaan, teks_opsi, adalah_jawaban_benar) VALUES (?, ?, ?)");
            foreach ($opsi as $index => $teks_opsi) {
                if (!empty(trim($teks_opsi))) {
                    $is_correct = ($index == $jawaban_benar_idx);
                    $stmt_opsi->bind_param("isi", $id_pertanyaan, $teks_opsi, $is_correct);
                    $stmt_opsi->execute();
                }
            }
            $stmt_opsi->close();
        } elseif ($tipe_soal == 'pilihan_ganda_gambar') {
            $jawaban_benar_idx = $_POST['jawaban_benar_gambar'];
            $existing_images = $_POST['existing_opsi_gambar'] ?? [];
            $stmt_opsi = $conn->prepare("INSERT INTO opsi_jawaban (id_pertanyaan, gambar_opsi, adalah_jawaban_benar) VALUES (?, ?, ?)");
            
            $current_idx = 0;

            // Process existing images first
            foreach ($existing_images as $index => $filename) {
                if (!empty($filename)) {
                    $is_correct = ($current_idx == $jawaban_benar_idx);
                    $stmt_opsi->bind_param("isi", $id_pertanyaan, $filename, $is_correct);
                    $stmt_opsi->execute();
                    $current_idx++;
                }
            }

            // Process new uploaded images
            if (isset($_FILES['opsi_gambar'])) {
                foreach ($_FILES['opsi_gambar']['name'] as $index => $name) {
                    if ($_FILES['opsi_gambar']['error'][$index] == UPLOAD_ERR_OK) {
                        $tmp_name = $_FILES['opsi_gambar']['tmp_name'][$index];
                        $new_filename = uniqid('opt_', true) . '.' . strtolower(pathinfo($name, PATHINFO_EXTENSION));
                        if (move_uploaded_file($tmp_name, $upload_dir . $new_filename)) {
                            $is_correct = ($current_idx == $jawaban_benar_idx);
                            $stmt_opsi->bind_param("isi", $id_pertanyaan, $new_filename, $is_correct);
                            $stmt_opsi->execute();
                            $current_idx++;
                        }
                    }
                }
            }
            $stmt_opsi->close();
        }

        $conn->commit();
        header("Location: manage_exam.php?id=$id_ujian&status=question_updated");
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Gagal memperbarui soal: " . $e->getMessage();
    }
}

// 5. Ambil data untuk form setelah semua logika pemrosesan
$opsi_jawaban = [];
$stmt_opsi = $conn->prepare("SELECT * FROM opsi_jawaban WHERE id_pertanyaan = ? ORDER BY id ASC");
$stmt_opsi->bind_param("i", $id_pertanyaan);
$stmt_opsi->execute();
$result_opsi = $stmt_opsi->get_result();
while($row = $result_opsi->fetch_assoc()) {
    $opsi_jawaban[] = $row;
}
$stmt_opsi->close();

// Ambil gambar pendukung
$gambar_pertanyaan = [];
$stmt_gambar = $conn->prepare("SELECT * FROM gambar_pertanyaan WHERE id_pertanyaan = ?");
$stmt_gambar->bind_param("i", $id_pertanyaan);
$stmt_gambar->execute();
$result_gambar = $stmt_gambar->get_result();
while($row = $result_gambar->fetch_assoc()) {
    $gambar_pertanyaan[] = $row;
}
$stmt_gambar->close();

// 6. Baru panggil header.php
include 'header.php';
?>
<title>Edit Soal</title>

<div class="flex justify-between items-center mb-8">
    <div>
        <h2 class="text-3xl font-bold text-slate-900">Edit Soal</h2>
        <p class="text-slate-500 mt-1">Mengubah detail pertanyaan untuk ujian.</p>
    </div>
    <a href="manage_exam.php?id=<?php echo $id_ujian; ?>" class="text-sm font-medium text-blue-600 hover:text-blue-800 flex items-center gap-1"><i data-lucide="arrow-left" class="w-4 h-4"></i> Kembali ke Kelola Ujian</a>
</div>

<div class="bg-white p-8 rounded-2xl shadow-lg mb-10">
    <?php if(isset($error)) echo "<div class='bg-red-100 text-red-700 p-4 rounded-md mb-4'>$error</div>"; ?>
    <form action="edit_question.php?id=<?php echo $id_pertanyaan; ?>" method="post" enctype="multipart/form-data" class="space-y-6">
        <input type="hidden" name="update_soal" value="1">
        <div>
            <label for="teks_pertanyaan" class="block text-sm font-medium text-slate-700">Teks Pertanyaan</label>
            <textarea name="teks_pertanyaan" id="teks_pertanyaan" class="mt-1 block w-full border border-slate-300 rounded-md shadow-sm p-3 focus:ring-blue-500 focus:border-blue-500" rows="4" required><?php echo htmlspecialchars($soal['teks_pertanyaan']); ?></textarea>
        </div>
        
        <!-- Gambar Pendukung Soal -->
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-2">Gambar Pendukung Soal</label>
            
            <!-- Gambar yang sudah ada -->
            <?php if (!empty($gambar_pertanyaan)): ?>
            <div class="mb-4">
                <p class="text-sm text-slate-600 mb-2">Gambar saat ini:</p>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    <?php foreach ($gambar_pertanyaan as $gambar): ?>
                    <div class="border rounded-lg p-2 relative group">
                        <img src="../uploads/<?php echo htmlspecialchars($gambar['nama_file']); ?>" alt="Gambar Soal" class="w-full h-24 object-cover rounded-md">
                        <div class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity rounded-md">
                            <a href="../uploads/<?php echo htmlspecialchars($gambar['nama_file']); ?>" target="_blank" class="text-white p-1 hover:text-blue-300" title="Lihat gambar">
                                <i data-lucide="eye" class="w-4 h-4"></i>
                            </a>
                            <label class="text-white p-1 hover:text-red-300 cursor-pointer" title="Hapus gambar">
                                <input type="checkbox" name="hapus_gambar[]" value="<?php echo htmlspecialchars($gambar['nama_file']); ?>" class="hidden">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </label>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <p class="text-xs text-slate-500 mt-1">Centang ikon sampah untuk menghapus gambar</p>
            </div>
            <?php endif; ?>
            
            <!-- Upload gambar baru -->
            <div class="mt-2">
                <input type="file" name="gambar_soal[]" id="gambar_soal" multiple class="file-input hidden" accept="image/*">
                <div class="upload-area border-2 border-dashed border-slate-300 rounded-md p-6 text-center cursor-pointer hover:border-blue-400 transition-colors">
                    <div class="upload-content">
                        <i data-lucide="upload" class="w-10 h-10 text-slate-400 mx-auto mb-3"></i>
                        <p class="text-sm text-slate-600 font-medium">Klik untuk menambah gambar pendukung</p>
                        <p class="text-xs text-slate-400 mt-1">Format: JPG, PNG, GIF (Maks. 2MB per file)</p>
                    </div>
                    <div class="upload-progress hidden">
                        <div class="w-full bg-slate-200 rounded-full h-2 mb-2">
                            <div class="upload-bar bg-blue-600 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                        </div>
                        <p class="text-xs text-slate-600">Mengupload... <span class="upload-percent">0%</span></p>
                    </div>
                    <div class="file-preview mt-3 hidden">
                        <p class="text-xs text-slate-600 mb-2">File terpilih:</p>
                        <div class="preview-list space-y-1"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="tipe_soal" class="block text-sm font-medium text-slate-700">Tipe Soal</label>
                <select name="tipe_soal" id="tipe_soal" class="mt-1 block w-full border border-slate-300 rounded-md shadow-sm p-3 focus:ring-blue-500 focus:border-blue-500">
                    <option value="pilihan_ganda" <?php if($soal['tipe_soal'] == 'pilihan_ganda') echo 'selected'; ?>>Pilihan Ganda (Teks)</option>
                    <option value="pilihan_ganda_gambar" <?php if($soal['tipe_soal'] == 'pilihan_ganda_gambar') echo 'selected'; ?>>Pilihan Ganda (Gambar)</option>
                    <option value="essay" <?php if($soal['tipe_soal'] == 'essay') echo 'selected'; ?>>Essay</option>
                </select>
            </div>
            <div>
                <label for="skor" class="block text-sm font-medium text-slate-700">Skor Soal</label>
                <input type="number" name="skor" id="skor" class="mt-1 block w-full border border-slate-300 rounded-md shadow-sm p-3" value="<?php echo htmlspecialchars($soal['skor']); ?>" required min="1">
            </div>
        </div>
        
        <!-- Opsi Jawaban Teks -->
        <div id="pilihan-ganda-container" class="space-y-3 pt-2">
            <label class="block text-sm font-medium text-slate-700">Opsi Jawaban (Teks)</label>
            <div id="options-wrapper" class="space-y-2">
                <?php if($soal['tipe_soal'] == 'pilihan_ganda'): foreach($opsi_jawaban as $index => $opsi): ?>
                <div class="flex items-center gap-2 option-item">
                    <input type="radio" name="jawaban_benar" value="<?php echo $index; ?>" class="h-4 w-4 text-blue-600" <?php if($opsi['adalah_jawaban_benar']) echo 'checked'; ?> required>
                    <input type="text" name="opsi[]" class="flex-grow border p-2 text-sm rounded-md" value="<?php echo htmlspecialchars($opsi['teks_opsi']); ?>" required>
                    <button type="button" class="remove-option-btn text-slate-400 hover:text-red-600"><i data-lucide="x-circle" class="h-5 w-5"></i></button>
                </div>
                <?php endforeach; endif; ?>
            </div>
            <button type="button" id="add-option-btn" class="text-sm font-medium text-blue-600 hover:text-blue-800 flex items-center gap-1 mt-2"><i data-lucide="plus-circle" class="w-4 h-4"></i> Tambah Opsi</button>
        </div>

        <!-- Opsi Jawaban Gambar -->
        <div id="pilihan-ganda-gambar-container" class="space-y-3 pt-2">
            <label class="block text-sm font-medium text-slate-700">Opsi Jawaban (Gambar)</label>
            <div id="options-image-wrapper" class="space-y-3">
                 <?php if($soal['tipe_soal'] == 'pilihan_ganda_gambar'): foreach($opsi_jawaban as $index => $opsi): ?>
                 <div class="flex items-center gap-3 p-2 border rounded-md option-item">
                    <input type="radio" name="jawaban_benar_gambar" value="<?php echo $index; ?>" class="h-4 w-4 text-blue-600" <?php if($opsi['adalah_jawaban_benar']) echo 'checked'; ?> required>
                    <img src="../uploads/<?php echo htmlspecialchars($opsi['gambar_opsi']); ?>" class="h-12 w-12 object-cover rounded-md">
                    <span class="text-xs text-slate-500 flex-grow"><?php echo htmlspecialchars($opsi['gambar_opsi']); ?></span>
                    <input type="hidden" name="existing_opsi_gambar[]" value="<?php echo htmlspecialchars($opsi['gambar_opsi']); ?>">
                    <button type="button" class="remove-option-btn text-slate-400 hover:text-red-600"><i data-lucide="x-circle" class="h-5 w-5"></i></button>
                </div>
                 <?php endforeach; endif; ?>
            </div>
            <button type="button" id="add-image-option-btn" class="text-sm font-medium text-blue-600 hover:text-blue-800 flex items-center gap-1 mt-2"><i data-lucide="plus-circle" class="w-4 h-4"></i> Tambah Opsi Gambar Baru</button>
        </div>

        <div class="pt-4">
            <button type="submit" class="w-full flex justify-center items-center py-3 px-4 rounded-md shadow-sm text-lg font-medium text-white bg-blue-600 hover:bg-blue-700">
                <i data-lucide="save" class="mr-2 h-5 w-5"></i> Simpan Perubahan
            </button>
        </div>
    </form>
</div>

<style>
.upload-area {
    transition: all 0.3s ease;
}
.upload-area.dragover {
    border-color: #3b82f6;
    background-color: #f8fafc;
}
.file-input {
    display: none;
}
.upload-bar {
    transition: width 0.3s ease;
}
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
.animate-spin {
    animation: spin 1s linear infinite;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tipeSoalSelect = document.getElementById('tipe_soal');
    const pgContainer = document.getElementById('pilihan-ganda-container');
    const pgImgContainer = document.getElementById('pilihan-ganda-gambar-container');
    
    function toggleContainers() {
        const type = tipeSoalSelect.value;
        pgContainer.style.display = type === 'pilihan_ganda' ? 'block' : 'none';
        pgImgContainer.style.display = type === 'pilihan_ganda_gambar' ? 'block' : 'none';
        
        pgContainer.querySelectorAll('input').forEach(i => i.required = type === 'pilihan_ganda');
        pgImgContainer.querySelectorAll('input[type="radio"]').forEach(i => i.required = type === 'pilihan_ganda_gambar');
    }

    tipeSoalSelect.addEventListener('change', toggleContainers);
    toggleContainers();

    // --- Logic to add/remove options dynamically ---
    const optionsWrapper = document.getElementById('options-wrapper');
    const addOptionBtn = document.getElementById('add-option-btn');
    
    addOptionBtn.addEventListener('click', () => {
        const index = optionsWrapper.children.length;
        const div = document.createElement('div');
        div.className = 'flex items-center gap-2 option-item';
        div.innerHTML = `
            <input type="radio" name="jawaban_benar" value="${index}" class="h-4 w-4 text-blue-600" required>
            <input type="text" name="opsi[]" class="flex-grow border p-2 text-sm rounded-md" placeholder="Teks Opsi Baru" required>
            <button type="button" class="remove-option-btn text-slate-400 hover:text-red-600"><i data-lucide="x-circle" class="h-5 w-5"></i></button>
        `;
        optionsWrapper.appendChild(div);
        lucide.createIcons({ nodes: [div.querySelector('.remove-option-btn')] });
    });

    optionsWrapper.addEventListener('click', e => {
        const removeBtn = e.target.closest('.remove-option-btn');
        if (removeBtn) {
            removeBtn.closest('.option-item').remove();
            // Re-index radio buttons
            optionsWrapper.querySelectorAll('input[type="radio"]').forEach((radio, i) => radio.value = i);
        }
    });

    // --- Logic for image options with upload indicator ---
    const optionsImgWrapper = document.getElementById('options-image-wrapper');
    const addImgOptionBtn = document.getElementById('add-image-option-btn');

    addImgOptionBtn.addEventListener('click', () => {
        const index = optionsImgWrapper.children.length;
        const div = document.createElement('div');
        div.className = 'flex items-center gap-3 p-2 border rounded-md option-item';
        div.innerHTML = `
            <input type="radio" name="jawaban_benar_gambar" value="${index}" class="h-4 w-4 text-blue-600" required>
            <div class="flex-grow">
                <input type="file" name="opsi_gambar[]" class="file-input hidden" required>
                <div class="upload-area border-2 border-dashed border-slate-300 rounded-md p-3 text-center cursor-pointer hover:border-blue-400 transition-colors">
                    <div class="upload-content">
                        <i data-lucide="upload" class="w-8 h-8 text-slate-400 mx-auto mb-2"></i>
                        <p class="text-sm text-slate-600">Klik untuk memilih gambar</p>
                        <p class="text-xs text-slate-400 mt-1">Format: JPG, PNG, GIF (Maks. 2MB)</p>
                    </div>
                    <div class="upload-progress hidden">
                        <div class="w-full bg-slate-200 rounded-full h-2 mb-2">
                            <div class="upload-bar bg-blue-600 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                        </div>
                        <p class="text-xs text-slate-600">Mengupload... <span class="upload-percent">0%</span></p>
                    </div>
                </div>
            </div>
            <button type="button" class="remove-option-btn text-slate-400 hover:text-red-600"><i data-lucide="x-circle" class="h-5 w-5"></i></button>
        `;
        optionsImgWrapper.appendChild(div);
        
        // Setup upload indicator for new file input
        const uploadArea = div.querySelector('.upload-area');
        const fileInput = div.querySelector('.file-input');
        const uploadContent = div.querySelector('.upload-content');
        const uploadProgress = div.querySelector('.upload-progress');
        const uploadBar = div.querySelector('.upload-bar');
        const uploadPercent = div.querySelector('.upload-percent');
        
        uploadArea.addEventListener('click', () => fileInput.click());
        
        fileInput.addEventListener('change', function(e) {
            if (this.files && this.files[0]) {
                const file = this.files[0];
                
                // Validate file size (2MB max)
                if (file.size > 2 * 1024 * 1024) {
                    alert('Ukuran file maksimal 2MB');
                    this.value = '';
                    return;
                }
                
                // Validate file type
                const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
                if (!validTypes.includes(file.type)) {
                    alert('Hanya format JPG, PNG, dan GIF yang diizinkan');
                    this.value = '';
                    return;
                }
                
                // Show preview and upload indicator
                const reader = new FileReader();
                reader.onload = function(e) {
                    uploadContent.innerHTML = `
                        <img src="${e.target.result}" class="h-12 w-12 object-cover rounded-md mx-auto mb-1">
                        <p class="text-xs text-slate-600 truncate">${file.name}</p>
                        <p class="text-xs text-slate-400">${(file.size / 1024).toFixed(1)} KB</p>
                    `;
                };
                reader.readAsDataURL(file);
                
                // Simulate upload progress (in real scenario, this would be actual upload)
                uploadContent.classList.add('hidden');
                uploadProgress.classList.remove('hidden');
                
                let progress = 0;
                const interval = setInterval(() => {
                    progress += Math.random() * 15;
                    if (progress >= 100) {
                        progress = 100;
                        clearInterval(interval);
                        setTimeout(() => {
                            uploadProgress.classList.add('hidden');
                            uploadContent.classList.remove('hidden');
                        }, 500);
                    }
                    uploadBar.style.width = progress + '%';
                    uploadPercent.textContent = Math.round(progress) + '%';
                }, 100);
            }
        });
        
        lucide.createIcons({ nodes: [div.querySelector('.remove-option-btn'), div.querySelector('.upload-content')] });
    });

    optionsImgWrapper.addEventListener('click', e => {
        const removeBtn = e.target.closest('.remove-option-btn');
        if (removeBtn) {
            removeBtn.closest('.option-item').remove();
            // Re-index radio buttons
            optionsImgWrapper.querySelectorAll('input[type="radio"]').forEach((radio, i) => radio.value = i);
        }
    });

    // Upload indicator for gambar pendukung soal
    const gambarSoalInput = document.getElementById('gambar_soal');
    const uploadAreaMain = document.querySelector('.upload-area');
    const uploadContentMain = document.querySelector('.upload-content');
    const uploadProgressMain = document.querySelector('.upload-progress');
    const uploadBarMain = document.querySelector('.upload-bar');
    const uploadPercentMain = document.querySelector('.upload-percent');
    const filePreview = document.querySelector('.file-preview');
    const previewList = document.querySelector('.preview-list');

    uploadAreaMain.addEventListener('click', () => gambarSoalInput.click());

    gambarSoalInput.addEventListener('change', function(e) {
        if (this.files && this.files.length > 0) {
            // Clear previous previews
            previewList.innerHTML = '';
            
            // Validate files
            const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
            let hasInvalidFile = false;
            
            Array.from(this.files).forEach(file => {
                if (file.size > 2 * 1024 * 1024) {
                    alert(`File ${file.name} melebihi ukuran maksimal 2MB`);
                    hasInvalidFile = true;
                } else if (!validTypes.includes(file.type)) {
                    alert(`File ${file.name} harus berupa gambar (JPG, PNG, GIF)`);
                    hasInvalidFile = true;
                }
            });
            
            if (hasInvalidFile) {
                this.value = '';
                return;
            }
            
            // Show upload progress
            uploadContentMain.classList.add('hidden');
            uploadProgressMain.classList.remove('hidden');
            
            let totalProgress = 0;
            const fileCount = this.files.length;
            
            Array.from(this.files).forEach((file, index) => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const previewItem = document.createElement('div');
                    previewItem.className = 'flex items-center gap-2 p-2 bg-slate-50 rounded-md';
                    previewItem.innerHTML = `
                        <img src="${e.target.result}" class="h-8 w-8 object-cover rounded">
                        <span class="text-xs text-slate-700 flex-grow">${file.name}</span>
                        <span class="text-xs text-slate-500">${(file.size / 1024).toFixed(1)} KB</span>
                    `;
                    previewList.appendChild(previewItem);
                    
                    // Update progress for each file
                    totalProgress += (100 / fileCount);
                    uploadBarMain.style.width = Math.min(totalProgress, 100) + '%';
                    uploadPercentMain.textContent = Math.round(Math.min(totalProgress, 100)) + '%';
                    
                    // When all files are processed
                    if (index === fileCount - 1) {
                        setTimeout(() => {
                            uploadProgressMain.classList.add('hidden');
                            filePreview.classList.remove('hidden');
                        }, 500);
                    }
                };
                reader.readAsDataURL(file);
            });
        }
    });

    // Global form submission indicator
    const form = document.querySelector('form');
    form.addEventListener('submit', function(e) {
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        
        submitBtn.disabled = true;
        submitBtn.innerHTML = `
            <div class="flex items-center justify-center">
                <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-white mr-2"></div>
                Menyimpan Perubahan...
            </div>
        `;
        
        // Check if there are any file uploads in progress
        const uploadInProgress = document.querySelector('.upload-progress:not(.hidden)');
        if (uploadInProgress) {
            e.preventDefault();
            alert('Tunggu hingga semua file selesai diupload');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    });
});
</script>
<?php include 'footer.php'; ?>