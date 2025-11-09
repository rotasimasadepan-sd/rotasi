<?php
require_once 'config/auth.php';
checkAdminLogin();

// Handle CRUD operations
if ($_POST) {
    $action = $_POST['action'];
    
    if ($action == 'create') {
        $id_pertanyaan = $_POST['id_pertanyaan'];
        $nama_file = trim($_POST['nama_file']);
        
        $stmt = $conn->prepare("INSERT INTO gambar_pertanyaan (id_pertanyaan, nama_file) VALUES (?, ?)");
        $stmt->bind_param("is", $id_pertanyaan, $nama_file);
        $stmt->execute();
        $success = "Gambar pertanyaan berhasil ditambahkan!";
    }
    
    elseif ($action == 'update') {
        $id = $_POST['id'];
        $id_pertanyaan = $_POST['id_pertanyaan'];
        $nama_file = trim($_POST['nama_file']);
        
        $stmt = $conn->prepare("UPDATE gambar_pertanyaan SET id_pertanyaan=?, nama_file=? WHERE id=?");
        $stmt->bind_param("isi", $id_pertanyaan, $nama_file, $id);
        $stmt->execute();
        $success = "Gambar pertanyaan berhasil diupdate!";
    }
    
    elseif ($action == 'delete') {
        $id = $_POST['id'];
        $stmt = $conn->prepare("DELETE FROM gambar_pertanyaan WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $success = "Gambar pertanyaan berhasil dihapus!";
    }
}

// Get all gambar pertanyaan with pertanyaan info
$gambar_pertanyaans = $conn->query("
    SELECT gp.*, p.teks_pertanyaan, p.tipe_soal, u.judul as ujian_judul, u.kode_ujian, g.username as guru_username
    FROM gambar_pertanyaan gp
    JOIN pertanyaan p ON gp.id_pertanyaan = p.id
    JOIN ujian u ON p.id_ujian = u.id
    JOIN guru g ON u.id_guru = g.id
    ORDER BY gp.id DESC
")->fetch_all(MYSQLI_ASSOC);

// Get all pertanyaan for dropdown (only essay and pilihan_ganda_gambar)
$pertanyaans = $conn->query("
    SELECT p.id, p.teks_pertanyaan, p.tipe_soal, u.judul as ujian_judul, u.kode_ujian, g.username as guru_username
    FROM pertanyaan p
    JOIN ujian u ON p.id_ujian = u.id
    JOIN guru g ON u.id_guru = g.id
    WHERE p.tipe_soal IN ('essay', 'pilihan_ganda_gambar')
    ORDER BY p.id DESC
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Gambar Pertanyaan - Ujian Sekolah</title>
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
                    <h1 class="h2">Manajemen Gambar Pertanyaan</h1>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                        <i class="bi bi-plus"></i> Tambah Gambar
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
                                        <th>Nama File</th>
                                        <th>Tipe</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($gambar_pertanyaans as $gambar): ?>
                                    <tr>
                                        <td><?= $gambar['id'] ?></td>
                                        <td style="max-width: 300px;">
                                            <small class="text-muted">
                                                <?= htmlspecialchars($gambar['guru_username']) ?> - 
                                                [<?= $gambar['kode_ujian'] ?>] <?= htmlspecialchars($gambar['ujian_judul']) ?>
                                            </small><br>
                                            <?= htmlspecialchars(substr($gambar['teks_pertanyaan'], 0, 60)) ?>
                                            <?= strlen($gambar['teks_pertanyaan']) > 60 ? '...' : '' ?>
                                        </td>
                                        <td>
                                            <code><?= htmlspecialchars($gambar['nama_file']) ?></code>
                                        </td>
                                        <td>
                                            <?php
                                            $badge_class = match($gambar['tipe_soal']) {
                                                'essay' => 'bg-warning',
                                                'pilihan_ganda_gambar' => 'bg-info'
                                            };
                                            ?>
                                            <span class="badge <?= $badge_class ?>">
                                                <?= str_replace('_', ' ', ucfirst($gambar['tipe_soal'])) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-warning" onclick="editGambar(<?= htmlspecialchars(json_encode($gambar)) ?>)">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger" onclick="deleteGambar(<?= $gambar['id'] ?>)">
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
                        <h5 class="modal-title">Tambah Gambar Pertanyaan</h5>
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
                            <label class="form-label">Nama File Gambar</label>
                            <input type="text" class="form-control" name="nama_file" required placeholder="contoh: gambar_soal_1.jpg">
                            <small class="form-text text-muted">Masukkan nama file gambar yang sudah diupload ke server</small>
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
                        <h5 class="modal-title">Edit Gambar Pertanyaan</h5>
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
                            <label class="form-label">Nama File Gambar</label>
                            <input type="text" class="form-control" name="nama_file" id="edit_nama_file" required>
                            <small class="form-text text-muted">Masukkan nama file gambar yang sudah diupload ke server</small>
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
                        <h5 class="modal-title">Hapus Gambar Pertanyaan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" id="delete_id">
                        <p>Apakah Anda yakin ingin menghapus gambar pertanyaan ini?</p>
                        <small class="text-muted">Catatan: File gambar di server tidak akan terhapus otomatis.</small>
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
        function editGambar(gambar) {
            document.getElementById('edit_id').value = gambar.id;
            document.getElementById('edit_id_pertanyaan').value = gambar.id_pertanyaan;
            document.getElementById('edit_nama_file').value = gambar.nama_file;
            new bootstrap.Modal(document.getElementById('editModal')).show();
        }
        
        function deleteGambar(id) {
            document.getElementById('delete_id').value = id;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }
    </script>
</body>
</html>