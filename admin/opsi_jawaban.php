<?php
require_once 'config/auth.php';
checkAdminLogin();

// Handle CRUD operations
if ($_POST) {
    $action = $_POST['action'];
    
    if ($action == 'create') {
        $id_pertanyaan = $_POST['id_pertanyaan'];
        $teks_opsi = trim($_POST['teks_opsi']) ?: null;
        $gambar_opsi = $_POST['gambar_opsi'] ?: null;
        $adalah_jawaban_benar = isset($_POST['adalah_jawaban_benar']) ? 1 : 0;
        
        $stmt = $conn->prepare("INSERT INTO opsi_jawaban (id_pertanyaan, teks_opsi, gambar_opsi, adalah_jawaban_benar) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("issi", $id_pertanyaan, $teks_opsi, $gambar_opsi, $adalah_jawaban_benar);
        $stmt->execute();
        $success = "Opsi jawaban berhasil ditambahkan!";
    }
    
    elseif ($action == 'update') {
        $id = $_POST['id'];
        $id_pertanyaan = $_POST['id_pertanyaan'];
        $teks_opsi = trim($_POST['teks_opsi']) ?: null;
        $gambar_opsi = $_POST['gambar_opsi'] ?: null;
        $adalah_jawaban_benar = isset($_POST['adalah_jawaban_benar']) ? 1 : 0;
        
        $stmt = $conn->prepare("UPDATE opsi_jawaban SET id_pertanyaan=?, teks_opsi=?, gambar_opsi=?, adalah_jawaban_benar=? WHERE id=?");
        $stmt->bind_param("issii", $id_pertanyaan, $teks_opsi, $gambar_opsi, $adalah_jawaban_benar, $id);
        $stmt->execute();
        $success = "Opsi jawaban berhasil diupdate!";
    }
    
    elseif ($action == 'delete') {
        $id = $_POST['id'];
        $stmt = $conn->prepare("DELETE FROM opsi_jawaban WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $success = "Opsi jawaban berhasil dihapus!";
    }
}

// Get all opsi jawaban with pertanyaan info
$opsi_jawabans = $conn->query("
    SELECT oj.*, p.teks_pertanyaan, p.tipe_soal, u.judul as ujian_judul, u.kode_ujian, g.username as guru_username
    FROM opsi_jawaban oj
    JOIN pertanyaan p ON oj.id_pertanyaan = p.id
    JOIN ujian u ON p.id_ujian = u.id
    JOIN guru g ON u.id_guru = g.id
    ORDER BY oj.id DESC
")->fetch_all(MYSQLI_ASSOC);

// Get all pertanyaan for dropdown (only pilihan ganda)
$pertanyaans = $conn->query("
    SELECT p.id, p.teks_pertanyaan, p.tipe_soal, u.judul as ujian_judul, u.kode_ujian, g.username as guru_username
    FROM pertanyaan p
    JOIN ujian u ON p.id_ujian = u.id
    JOIN guru g ON u.id_guru = g.id
    WHERE p.tipe_soal IN ('pilihan_ganda', 'pilihan_ganda_gambar')
    ORDER BY p.id DESC
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Opsi Jawaban - Ujian Sekolah</title>
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
                    <h1 class="h2">Manajemen Opsi Jawaban</h1>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                        <i class="bi bi-plus"></i> Tambah Opsi
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
                                        <th>Pertanyaan</th>
                                        <th>Teks Opsi</th>
                                        <th>Gambar</th>
                                        <th>Jawaban Benar</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($opsi_jawabans as $opsi): ?>
                                    <tr>
                                        <td><?= $opsi['id'] ?></td>
                                        <td style="max-width: 250px;">
                                            <small class="text-muted">
                                                <?= htmlspecialchars($opsi['guru_username']) ?> - 
                                                [<?= $opsi['kode_ujian'] ?>] <?= htmlspecialchars($opsi['ujian_judul']) ?>
                                            </small><br>
                                            <?= htmlspecialchars(substr($opsi['teks_pertanyaan'], 0, 80)) ?>
                                            <?= strlen($opsi['teks_pertanyaan']) > 80 ? '...' : '' ?>
                                        </td>
                                        <td><?= $opsi['teks_opsi'] ? htmlspecialchars($opsi['teks_opsi']) : '<span class="text-muted">-</span>' ?></td>
                                        <td><?= $opsi['gambar_opsi'] ? '<span class="badge bg-info">Ada gambar</span>' : '<span class="text-muted">-</span>' ?></td>
                                        <td>
                                            <?= $opsi['adalah_jawaban_benar'] ? 
                                                '<span class="badge bg-success">Ya</span>' : 
                                                '<span class="badge bg-secondary">Tidak</span>' ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-warning" onclick="editOpsi(<?= htmlspecialchars(json_encode($opsi)) ?>)">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger" onclick="deleteOpsi(<?= $opsi['id'] ?>)">
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
                        <h5 class="modal-title">Tambah Opsi Jawaban</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create">
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
                            <label class="form-label">Teks Opsi</label>
                            <textarea class="form-control" name="teks_opsi" rows="3" placeholder="Kosongkan jika menggunakan gambar"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Gambar Opsi (nama file)</label>
                            <input type="text" class="form-control" name="gambar_opsi" placeholder="Kosongkan jika menggunakan teks">
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="adalah_jawaban_benar" value="1" id="adalah_jawaban_benar">
                                <label class="form-check-label" for="adalah_jawaban_benar">
                                    Ini adalah jawaban yang benar
                                </label>
                            </div>
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
                        <h5 class="modal-title">Edit Opsi Jawaban</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" id="edit_id">
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
                            <label class="form-label">Teks Opsi</label>
                            <textarea class="form-control" name="teks_opsi" id="edit_teks_opsi" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Gambar Opsi (nama file)</label>
                            <input type="text" class="form-control" name="gambar_opsi" id="edit_gambar_opsi">
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="adalah_jawaban_benar" value="1" id="edit_adalah_jawaban_benar">
                                <label class="form-check-label" for="edit_adalah_jawaban_benar">
                                    Ini adalah jawaban yang benar
                                </label>
                            </div>
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
                        <h5 class="modal-title">Hapus Opsi Jawaban</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" id="delete_id">
                        <p>Apakah Anda yakin ingin menghapus opsi jawaban ini?</p>
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
        function editOpsi(opsi) {
            document.getElementById('edit_id').value = opsi.id;
            document.getElementById('edit_id_pertanyaan').value = opsi.id_pertanyaan;
            document.getElementById('edit_teks_opsi').value = opsi.teks_opsi || '';
            document.getElementById('edit_gambar_opsi').value = opsi.gambar_opsi || '';
            document.getElementById('edit_adalah_jawaban_benar').checked = opsi.adalah_jawaban_benar == 1;
            new bootstrap.Modal(document.getElementById('editModal')).show();
        }
        
        function deleteOpsi(id) {
            document.getElementById('delete_id').value = id;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }
    </script>
</body>
</html>