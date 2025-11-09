<?php
require_once 'config/auth.php';
checkAdminLogin();

// Handle CRUD operations
if ($_POST) {
    $action = $_POST['action'];
    
    if ($action == 'create') {
        $username = trim($_POST['username']);
        $password = password_hash(trim($_POST['password']), PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("INSERT INTO guru (username, password) VALUES (?, ?)");
        $stmt->bind_param("ss", $username, $password);
        $stmt->execute();
        $success = "Guru berhasil ditambahkan!";
    }
    
    elseif ($action == 'update') {
        $id = $_POST['id'];
        $username = trim($_POST['username']);
        
        if (!empty($_POST['password'])) {
            $password = password_hash(trim($_POST['password']), PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE guru SET username=?, password=? WHERE id=?");
            $stmt->bind_param("ssi", $username, $password, $id);
        } else {
            $stmt = $conn->prepare("UPDATE guru SET username=? WHERE id=?");
            $stmt->bind_param("si", $username, $id);
        }
        $stmt->execute();
        $success = "Guru berhasil diupdate!";
    }
    
    elseif ($action == 'delete') {
        $id = $_POST['id'];
        $stmt = $conn->prepare("DELETE FROM guru WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $success = "Guru berhasil dihapus!";
    }
}

// Get all guru with statistics
$gurus = $conn->query("
    SELECT g.*, 
           COUNT(u.id) as total_ujian,
           COUNT(DISTINCT ps.id) as total_partisipasi
    FROM guru g
    LEFT JOIN ujian u ON g.id = u.id_guru
    LEFT JOIN partisipasi_siswa ps ON u.id = ps.id_ujian
    GROUP BY g.id
    ORDER BY g.created_at DESC
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Guru - Ujian Sekolah</title>
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
                    <h1 class="h2">Manajemen Guru</h1>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                        <i class="bi bi-plus"></i> Tambah Guru
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
                                        <th>Username</th>
                                        <th>Total Ujian</th>
                                        <th>Total Partisipasi</th>
                                        <th>Terdaftar</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($gurus as $guru): ?>
                                    <tr>
                                        <td><?= $guru['id'] ?></td>
                                        <td><?= htmlspecialchars($guru['username']) ?></td>
                                        <td><span class="badge bg-primary"><?= $guru['total_ujian'] ?></span></td>
                                        <td><span class="badge bg-success"><?= $guru['total_partisipasi'] ?></span></td>
                                        <td><?= date('d/m/Y H:i', strtotime($guru['created_at'])) ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-warning" onclick="editGuru(<?= htmlspecialchars(json_encode($guru)) ?>)">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger" onclick="deleteGuru(<?= $guru['id'] ?>)">
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
                        <h5 class="modal-title">Tambah Guru</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create">
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" class="form-control" name="password" required>
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
                        <h5 class="modal-title">Edit Guru</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" name="username" id="edit_username" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password (kosongkan jika tidak ingin mengubah)</label>
                            <input type="password" class="form-control" name="password">
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
                        <h5 class="modal-title">Hapus Guru</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" id="delete_id">
                        <p>Apakah Anda yakin ingin menghapus guru ini? Semua ujian yang terkait akan ikut terhapus.</p>
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
        function editGuru(guru) {
            document.getElementById('edit_id').value = guru.id;
            document.getElementById('edit_username').value = guru.username;
            new bootstrap.Modal(document.getElementById('editModal')).show();
        }
        
        function deleteGuru(id) {
            document.getElementById('delete_id').value = id;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }
    </script>
</body>
</html>