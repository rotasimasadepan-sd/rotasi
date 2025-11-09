<?php
require_once 'config/auth.php';
checkAdminLogin();

// Handle CRUD operations
if ($_POST) {
    $action = $_POST['action'];
    
    if ($action == 'create') {
        $id_guru = $_POST['id_guru'];
        $judul = trim($_POST['judul']);
        $kode_ujian = strtoupper(substr(md5(uniqid()), 0, 6));
        $waktu_mulai = $_POST['waktu_mulai'] ?: null;
        $durasi = $_POST['durasi'];
        $status = $_POST['status'];
        
        $stmt = $conn->prepare("INSERT INTO ujian (id_guru, judul, kode_ujian, waktu_mulai, durasi, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssss", $id_guru, $judul, $kode_ujian, $waktu_mulai, $durasi, $status);
        $stmt->execute();
        $success = "Ujian berhasil ditambahkan dengan kode: $kode_ujian";
    }
    
    elseif ($action == 'update') {
        $id = $_POST['id'];
        $id_guru = $_POST['id_guru'];
        $judul = trim($_POST['judul']);
        $waktu_mulai = $_POST['waktu_mulai'] ?: null;
        $durasi = $_POST['durasi'];
        $status = $_POST['status'];
        
        $stmt = $conn->prepare("UPDATE ujian SET id_guru=?, judul=?, waktu_mulai=?, durasi=?, status=? WHERE id=?");
        $stmt->bind_param("issssi", $id_guru, $judul, $waktu_mulai, $durasi, $status, $id);
        $stmt->execute();
        $success = "Ujian berhasil diupdate!";
    }
    
    elseif ($action == 'delete') {
        $id = $_POST['id'];
        $stmt = $conn->prepare("DELETE FROM ujian WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $success = "Ujian berhasil dihapus!";
    }
}

// Get all ujian with guru info and statistics
$ujians = $conn->query("
    SELECT u.*, g.username as guru_username,
           COUNT(DISTINCT p.id) as total_pertanyaan,
           COUNT(DISTINCT ps.id) as total_partisipasi
    FROM ujian u
    JOIN guru g ON u.id_guru = g.id
    LEFT JOIN pertanyaan p ON u.id = p.id_ujian
    LEFT JOIN partisipasi_siswa ps ON u.id = ps.id_ujian
    GROUP BY u.id
    ORDER BY u.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

// Get all guru for dropdown
$gurus = $conn->query("SELECT id, username FROM guru ORDER BY username")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Ujian - Ujian Sekolah</title>
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
                    <h1 class="h2">Manajemen Ujian</h1>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                        <i class="bi bi-plus"></i> Tambah Ujian
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
                                        <th>Judul</th>
                                        <th>Kode</th>
                                        <th>Guru</th>
                                        <th>Pertanyaan</th>
                                        <th>Partisipasi</th>
                                        <th>Durasi</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($ujians as $ujian): ?>
                                    <tr>
                                        <td><?= $ujian['id'] ?></td>
                                        <td><?= htmlspecialchars($ujian['judul']) ?></td>
                                        <td><span class="badge bg-primary"><?= $ujian['kode_ujian'] ?></span></td>
                                        <td><?= htmlspecialchars($ujian['guru_username']) ?></td>
                                        <td><span class="badge bg-info"><?= $ujian['total_pertanyaan'] ?></span></td>
                                        <td><span class="badge bg-success"><?= $ujian['total_partisipasi'] ?></span></td>
                                        <td><?= $ujian['durasi'] ?> menit</td>
                                        <td>
                                            <?php
                                            $badge_class = match($ujian['status']) {
                                                'menunggu' => 'bg-warning',
                                                'berlangsung' => 'bg-success',
                                                'selesai' => 'bg-secondary'
                                            };
                                            ?>
                                            <span class="badge <?= $badge_class ?>"><?= ucfirst($ujian['status']) ?></span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-warning" onclick="editUjian(<?= htmlspecialchars(json_encode($ujian)) ?>)">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger" onclick="deleteUjian(<?= $ujian['id'] ?>)">
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
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Tambah Ujian</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create">
                        <div class="mb-3">
                            <label class="form-label">Guru</label>
                            <select class="form-control" name="id_guru" required>
                                <option value="">Pilih Guru</option>
                                <?php foreach ($gurus as $guru): ?>
                                <option value="<?= $guru['id'] ?>"><?= htmlspecialchars($guru['username']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Judul Ujian</label>
                            <input type="text" class="form-control" name="judul" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Waktu Mulai (opsional)</label>
                            <input type="datetime-local" class="form-control" name="waktu_mulai">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Durasi (menit)</label>
                            <input type="number" class="form-control" name="durasi" required min="1">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-control" name="status" required>
                                <option value="menunggu">Menunggu</option>
                                <option value="berlangsung">Berlangsung</option>
                                <option value="selesai">Selesai</option>
                            </select>
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
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Ujian</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="mb-3">
                            <label class="form-label">Guru</label>
                            <select class="form-control" name="id_guru" id="edit_id_guru" required>
                                <?php foreach ($gurus as $guru): ?>
                                <option value="<?= $guru['id'] ?>"><?= htmlspecialchars($guru['username']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Judul Ujian</label>
                            <input type="text" class="form-control" name="judul" id="edit_judul" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Waktu Mulai (opsional)</label>
                            <input type="datetime-local" class="form-control" name="waktu_mulai" id="edit_waktu_mulai">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Durasi (menit)</label>
                            <input type="number" class="form-control" name="durasi" id="edit_durasi" required min="1">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-control" name="status" id="edit_status" required>
                                <option value="menunggu">Menunggu</option>
                                <option value="berlangsung">Berlangsung</option>
                                <option value="selesai">Selesai</option>
                            </select>
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
                        <h5 class="modal-title">Hapus Ujian</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" id="delete_id">
                        <p>Apakah Anda yakin ingin menghapus ujian ini? Semua data terkait (pertanyaan, partisipasi, jawaban) akan ikut terhapus.</p>
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
        function editUjian(ujian) {
            document.getElementById('edit_id').value = ujian.id;
            document.getElementById('edit_id_guru').value = ujian.id_guru;
            document.getElementById('edit_judul').value = ujian.judul;
            document.getElementById('edit_waktu_mulai').value = ujian.waktu_mulai ? ujian.waktu_mulai.slice(0,16) : '';
            document.getElementById('edit_durasi').value = ujian.durasi;
            document.getElementById('edit_status').value = ujian.status;
            new bootstrap.Modal(document.getElementById('editModal')).show();
        }
        
        function deleteUjian(id) {
            document.getElementById('delete_id').value = id;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }
    </script>
</body>
</html>