<?php
require_once 'config/auth.php';
checkAdminLogin();

// Handle CRUD operations
if ($_POST) {
    $action = $_POST['action'];
    
    if ($action == 'create') {
        $id_ujian = $_POST['id_ujian'];
        $tipe_soal = $_POST['tipe_soal'];
        $teks_pertanyaan = trim($_POST['teks_pertanyaan']);
        $skor = $_POST['skor'];
        
        $stmt = $conn->prepare("INSERT INTO pertanyaan (id_ujian, tipe_soal, teks_pertanyaan, skor) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("issi", $id_ujian, $tipe_soal, $teks_pertanyaan, $skor);
        $stmt->execute();
        $success = "Pertanyaan berhasil ditambahkan!";
    }
    
    elseif ($action == 'update') {
        $id = $_POST['id'];
        $id_ujian = $_POST['id_ujian'];
        $tipe_soal = $_POST['tipe_soal'];
        $teks_pertanyaan = trim($_POST['teks_pertanyaan']);
        $skor = $_POST['skor'];
        
        $stmt = $conn->prepare("UPDATE pertanyaan SET id_ujian=?, tipe_soal=?, teks_pertanyaan=?, skor=? WHERE id=?");
        $stmt->bind_param("issii", $id_ujian, $tipe_soal, $teks_pertanyaan, $skor, $id);
        $stmt->execute();
        $success = "Pertanyaan berhasil diupdate!";
    }
    
    elseif ($action == 'delete') {
        $id = $_POST['id'];
        $stmt = $conn->prepare("DELETE FROM pertanyaan WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $success = "Pertanyaan berhasil dihapus!";
    }
}

// Get all pertanyaan with ujian info
$pertanyaans = $conn->query("
    SELECT p.*, u.judul as ujian_judul, u.kode_ujian, g.username as guru_username,
           COUNT(oj.id) as total_opsi,
           COUNT(js.id) as total_jawaban
    FROM pertanyaan p
    JOIN ujian u ON p.id_ujian = u.id
    JOIN guru g ON u.id_guru = g.id
    LEFT JOIN opsi_jawaban oj ON p.id = oj.id_pertanyaan
    LEFT JOIN jawaban_siswa js ON p.id = js.id_pertanyaan
    GROUP BY p.id
    ORDER BY p.id DESC
")->fetch_all(MYSQLI_ASSOC);

// Get all ujian for dropdown
$ujians = $conn->query("
    SELECT u.id, u.judul, u.kode_ujian, g.username as guru_username
    FROM ujian u
    JOIN guru g ON u.id_guru = g.id
    ORDER BY u.created_at DESC
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Pertanyaan - Ujian Sekolah</title>
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
                    <h1 class="h2">Manajemen Pertanyaan</h1>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                        <i class="bi bi-plus"></i> Tambah Pertanyaan
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
                                        <th>Ujian</th>
                                        <th>Tipe</th>
                                        <th>Pertanyaan</th>
                                        <th>Skor</th>
                                        <th>Opsi</th>
                                        <th>Jawaban</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pertanyaans as $pertanyaan): ?>
                                    <tr>
                                        <td><?= $pertanyaan['id'] ?></td>
                                        <td>
                                            <small class="text-muted"><?= htmlspecialchars($pertanyaan['guru_username']) ?></small><br>
                                            <?= htmlspecialchars($pertanyaan['ujian_judul']) ?>
                                            <span class="badge bg-primary"><?= $pertanyaan['kode_ujian'] ?></span>
                                        </td>
                                        <td>
                                            <?php
                                            $badge_class = match($pertanyaan['tipe_soal']) {
                                                'pilihan_ganda' => 'bg-success',
                                                'essay' => 'bg-warning',
                                                'pilihan_ganda_gambar' => 'bg-info'
                                            };
                                            ?>
                                            <span class="badge <?= $badge_class ?>">
                                                <?= str_replace('_', ' ', ucfirst($pertanyaan['tipe_soal'])) ?>
                                            </span>
                                        </td>
                                        <td style="max-width: 300px;">
                                            <?= htmlspecialchars(substr($pertanyaan['teks_pertanyaan'], 0, 100)) ?>
                                            <?= strlen($pertanyaan['teks_pertanyaan']) > 100 ? '...' : '' ?>
                                        </td>
                                        <td><span class="badge bg-secondary"><?= $pertanyaan['skor'] ?></span></td>
                                        <td><span class="badge bg-info"><?= $pertanyaan['total_opsi'] ?></span></td>
                                        <td><span class="badge bg-success"><?= $pertanyaan['total_jawaban'] ?></span></td>
                                        <td>
                                            <button class="btn btn-sm btn-warning" onclick="editPertanyaan(<?= htmlspecialchars(json_encode($pertanyaan)) ?>)">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger" onclick="deletePertanyaan(<?= $pertanyaan['id'] ?>)">
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
                        <h5 class="modal-title">Tambah Pertanyaan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create">
                        <div class="mb-3">
                            <label class="form-label">Ujian</label>
                            <select class="form-control" name="id_ujian" required>
                                <option value="">Pilih Ujian</option>
                                <?php foreach ($ujians as $ujian): ?>
                                <option value="<?= $ujian['id'] ?>">
                                    [<?= $ujian['kode_ujian'] ?>] <?= htmlspecialchars($ujian['judul']) ?> 
                                    - <?= htmlspecialchars($ujian['guru_username']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tipe Soal</label>
                            <select class="form-control" name="tipe_soal" required>
                                <option value="pilihan_ganda">Pilihan Ganda</option>
                                <option value="essay">Essay</option>
                                <option value="pilihan_ganda_gambar">Pilihan Ganda Gambar</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Teks Pertanyaan</label>
                            <textarea class="form-control" name="teks_pertanyaan" rows="4" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Skor</label>
                            <input type="number" class="form-control" name="skor" required min="1" value="10">
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
                        <h5 class="modal-title">Edit Pertanyaan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="mb-3">
                            <label class="form-label">Ujian</label>
                            <select class="form-control" name="id_ujian" id="edit_id_ujian" required>
                                <?php foreach ($ujians as $ujian): ?>
                                <option value="<?= $ujian['id'] ?>">
                                    [<?= $ujian['kode_ujian'] ?>] <?= htmlspecialchars($ujian['judul']) ?> 
                                    - <?= htmlspecialchars($ujian['guru_username']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tipe Soal</label>
                            <select class="form-control" name="tipe_soal" id="edit_tipe_soal" required>
                                <option value="pilihan_ganda">Pilihan Ganda</option>
                                <option value="essay">Essay</option>
                                <option value="pilihan_ganda_gambar">Pilihan Ganda Gambar</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Teks Pertanyaan</label>
                            <textarea class="form-control" name="teks_pertanyaan" id="edit_teks_pertanyaan" rows="4" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Skor</label>
                            <input type="number" class="form-control" name="skor" id="edit_skor" required min="1">
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
                        <h5 class="modal-title">Hapus Pertanyaan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" id="delete_id">
                        <p>Apakah Anda yakin ingin menghapus pertanyaan ini? Semua opsi jawaban dan jawaban siswa terkait akan ikut terhapus.</p>
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
        function editPertanyaan(pertanyaan) {
            document.getElementById('edit_id').value = pertanyaan.id;
            document.getElementById('edit_id_ujian').value = pertanyaan.id_ujian;
            document.getElementById('edit_tipe_soal').value = pertanyaan.tipe_soal;
            document.getElementById('edit_teks_pertanyaan').value = pertanyaan.teks_pertanyaan;
            document.getElementById('edit_skor').value = pertanyaan.skor;
            new bootstrap.Modal(document.getElementById('editModal')).show();
        }
        
        function deletePertanyaan(id) {
            document.getElementById('delete_id').value = id;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }
    </script>
</body>
</html>