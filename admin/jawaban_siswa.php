<?php
require_once 'config/auth.php';
checkAdminLogin();

// Handle CRUD operations
if ($_POST) {
    $action = $_POST['action'];
    
    if ($action == 'create') {
        $id_partisipasi = $_POST['id_partisipasi'];
        $id_pertanyaan = $_POST['id_pertanyaan'];
        $id_opsi_jawaban = $_POST['id_opsi_jawaban'] ?: null;
        $jawaban_essay = trim($_POST['jawaban_essay']) ?: null;
        
        $stmt = $conn->prepare("INSERT INTO jawaban_siswa (id_partisipasi, id_pertanyaan, id_opsi_jawaban, jawaban_essay) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiis", $id_partisipasi, $id_pertanyaan, $id_opsi_jawaban, $jawaban_essay);
        $stmt->execute();
        $success = "Jawaban siswa berhasil ditambahkan!";
    }
    
    elseif ($action == 'update') {
        $id = $_POST['id'];
        $id_partisipasi = $_POST['id_partisipasi'];
        $id_pertanyaan = $_POST['id_pertanyaan'];
        $id_opsi_jawaban = $_POST['id_opsi_jawaban'] ?: null;
        $jawaban_essay = trim($_POST['jawaban_essay']) ?: null;
        
        $stmt = $conn->prepare("UPDATE jawaban_siswa SET id_partisipasi=?, id_pertanyaan=?, id_opsi_jawaban=?, jawaban_essay=? WHERE id=?");
        $stmt->bind_param("iiisi", $id_partisipasi, $id_pertanyaan, $id_opsi_jawaban, $jawaban_essay, $id);
        $stmt->execute();
        $success = "Jawaban siswa berhasil diupdate!";
    }
    
    elseif ($action == 'delete') {
        $id = $_POST['id'];
        $stmt = $conn->prepare("DELETE FROM jawaban_siswa WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $success = "Jawaban siswa berhasil dihapus!";
    }
}

// Get all jawaban siswa with detailed info
$jawaban_siswas = $conn->query("
    SELECT js.*, 
           ps.nama_siswa, ps.kelas_siswa,
           p.teks_pertanyaan, p.tipe_soal,
           u.judul as ujian_judul, u.kode_ujian,
           g.username as guru_username,
           oj.teks_opsi, oj.adalah_jawaban_benar
    FROM jawaban_siswa js
    JOIN partisipasi_siswa ps ON js.id_partisipasi = ps.id
    JOIN pertanyaan p ON js.id_pertanyaan = p.id
    JOIN ujian u ON p.id_ujian = u.id
    JOIN guru g ON u.id_guru = g.id
    LEFT JOIN opsi_jawaban oj ON js.id_opsi_jawaban = oj.id
    ORDER BY js.id DESC
")->fetch_all(MYSQLI_ASSOC);

// Get partisipasi for dropdown
$partisipasis = $conn->query("
    SELECT ps.id, ps.nama_siswa, ps.kelas_siswa, u.judul as ujian_judul, u.kode_ujian
    FROM partisipasi_siswa ps
    JOIN ujian u ON ps.id_ujian = u.id
    ORDER BY ps.id DESC
")->fetch_all(MYSQLI_ASSOC);

// Get pertanyaan for dropdown
$pertanyaans = $conn->query("
    SELECT p.id, p.teks_pertanyaan, p.tipe_soal, u.judul as ujian_judul, u.kode_ujian
    FROM pertanyaan p
    JOIN ujian u ON p.id_ujian = u.id
    ORDER BY p.id DESC
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Jawaban Siswa - Ujian Sekolah</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container-fluid mt-5">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Manajemen Jawaban Siswa</h1>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                        <i class="bi bi-plus"></i> Tambah Jawaban
                    </button>
                </div>
                
                <?php if (isset($success)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= $success ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Siswa</th>
                                        <th>Ujian</th>
                                        <th>Pertanyaan</th>
                                        <th>Jawaban</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($jawaban_siswas as $jawaban): ?>
                                    <tr>
                                        <td><?= $jawaban['id'] ?></td>
                                        <td>
                                            <?= htmlspecialchars($jawaban['nama_siswa']) ?><br>
                                            <small class="text-muted"><?= htmlspecialchars($jawaban['kelas_siswa']) ?></small>
                                        </td>
                                        <td>
                                            <small class="text-muted"><?= htmlspecialchars($jawaban['guru_username']) ?></small><br>
                                            <?= htmlspecialchars($jawaban['ujian_judul']) ?>
                                            <span class="badge bg-primary"><?= $jawaban['kode_ujian'] ?></span>
                                        </td>
                                        <td style="max-width: 200px;">
                                            <span class="badge <?= $jawaban['tipe_soal'] == 'essay' ? 'bg-warning' : 'bg-success' ?>">
                                                <?= str_replace('_', ' ', ucfirst($jawaban['tipe_soal'])) ?>
                                            </span><br>
                                            <?= htmlspecialchars(substr($jawaban['teks_pertanyaan'], 0, 50)) ?>...
                                        </td>
                                        <td style="max-width: 250px;">
                                            <?php if ($jawaban['jawaban_essay']): ?>
                                                <strong>Essay:</strong> <?= htmlspecialchars(substr($jawaban['jawaban_essay'], 0, 50)) ?>...
                                            <?php elseif ($jawaban['teks_opsi']): ?>
                                                <strong>Pilihan:</strong> <?= htmlspecialchars($jawaban['teks_opsi']) ?>
                                            <?php else: ?>
                                                <span class="text-muted">Tidak dijawab</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($jawaban['tipe_soal'] == 'essay'): ?>
                                                <span class="badge bg-warning">Manual</span>
                                            <?php elseif ($jawaban['adalah_jawaban_benar']): ?>
                                                <span class="badge bg-success">Benar</span>
                                            <?php elseif ($jawaban['teks_opsi']): ?>
                                                <span class="badge bg-danger">Salah</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Kosong</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-warning" onclick="editJawaban(<?= htmlspecialchars(json_encode($jawaban)) ?>)">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger" onclick="deleteJawaban(<?= $jawaban['id'] ?>)">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <!-- Add Modal -->
    <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Tambah Jawaban Siswa</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create">
                        <div class="mb-3">
                            <label class="form-label">Partisipasi Siswa</label>
                            <select class="form-control" name="id_partisipasi" required>
                                <option value="">Pilih Siswa</option>
                                <?php foreach ($partisipasis as $partisipasi): ?>
                                <option value="<?= $partisipasi['id'] ?>">
                                    <?= htmlspecialchars($partisipasi['nama_siswa']) ?> (<?= $partisipasi['kelas_siswa'] ?>) 
                                    - [<?= $partisipasi['kode_ujian'] ?>] <?= htmlspecialchars($partisipasi['ujian_judul']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Pertanyaan</label>
                            <select class="form-control" name="id_pertanyaan" required>
                                <option value="">Pilih Pertanyaan</option>
                                <?php foreach ($pertanyaans as $pertanyaan): ?>
                                <option value="<?= $pertanyaan['id'] ?>">
                                    [<?= $pertanyaan['kode_ujian'] ?>] <?= htmlspecialchars(substr($pertanyaan['teks_pertanyaan'], 0, 50)) ?>... 
                                    (<?= $pertanyaan['tipe_soal'] ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Opsi Jawaban (untuk pilihan ganda)</label>
                            <select class="form-control" name="id_opsi_jawaban">
                                <option value="">Tidak ada / Essay</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Jawaban Essay</label>
                            <textarea class="form-control" name="jawaban_essay" rows="4" placeholder="Kosongkan jika pilihan ganda"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Jawaban Siswa</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="mb-3">
                            <label class="form-label">Partisipasi Siswa</label>
                            <select class="form-control" name="id_partisipasi" id="edit_id_partisipasi" required>
                                <?php foreach ($partisipasis as $partisipasi): ?>
                                <option value="<?= $partisipasi['id'] ?>">
                                    <?= htmlspecialchars($partisipasi['nama_siswa']) ?> (<?= $partisipasi['kelas_siswa'] ?>) 
                                    - [<?= $partisipasi['kode_ujian'] ?>] <?= htmlspecialchars($partisipasi['ujian_judul']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Pertanyaan</label>
                            <select class="form-control" name="id_pertanyaan" id="edit_id_pertanyaan" required>
                                <?php foreach ($pertanyaans as $pertanyaan): ?>
                                <option value="<?= $pertanyaan['id'] ?>">
                                    [<?= $pertanyaan['kode_ujian'] ?>] <?= htmlspecialchars(substr($pertanyaan['teks_pertanyaan'], 0, 50)) ?>... 
                                    (<?= $pertanyaan['tipe_soal'] ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Opsi Jawaban (untuk pilihan ganda)</label>
                            <select class="form-control" name="id_opsi_jawaban" id="edit_id_opsi_jawaban">
                                <option value="">Tidak ada / Essay</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Jawaban Essay</label>
                            <textarea class="form-control" name="jawaban_essay" id="edit_jawaban_essay" rows="4"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Delete Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Hapus Jawaban Siswa</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" id="delete_id">
                        <p>Apakah Anda yakin ingin menghapus jawaban siswa ini?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger">Hapus</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editJawaban(jawaban) {
            document.getElementById('edit_id').value = jawaban.id;
            document.getElementById('edit_id_partisipasi').value = jawaban.id_partisipasi;
            document.getElementById('edit_id_pertanyaan').value = jawaban.id_pertanyaan;
            document.getElementById('edit_id_opsi_jawaban').value = jawaban.id_opsi_jawaban || '';
            document.getElementById('edit_jawaban_essay').value = jawaban.jawaban_essay || '';
            new bootstrap.Modal(document.getElementById('editModal')).show();
        }
        
        function deleteJawaban(id) {
            document.getElementById('delete_id').value = id;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }
    </script>
</body>
</html>