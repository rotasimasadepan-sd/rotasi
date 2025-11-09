<?php
require_once 'config/auth.php';
checkAdminLogin();

// Handle CRUD operations
if ($_POST) {
    $action = $_POST['action'];
    
    if ($action == 'create') {
        $id_ujian = $_POST['id_ujian'];
        $nama_siswa = trim($_POST['nama_siswa']);
        $kelas_siswa = trim($_POST['kelas_siswa']);
        $waktu_selesai = $_POST['waktu_selesai'] ?: null;
        $skor = $_POST['skor'] ?: null;
        
        $stmt = $conn->prepare("INSERT INTO partisipasi_siswa (id_ujian, nama_siswa, kelas_siswa, waktu_selesai, skor) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("isssd", $id_ujian, $nama_siswa, $kelas_siswa, $waktu_selesai, $skor);
        $stmt->execute();
        $success = "Partisipasi siswa berhasil ditambahkan!";
    }
    
    elseif ($action == 'update') {
        $id = $_POST['id'];
        $id_ujian = $_POST['id_ujian'];
        $nama_siswa = trim($_POST['nama_siswa']);
        $kelas_siswa = trim($_POST['kelas_siswa']);
        $waktu_selesai = $_POST['waktu_selesai'] ?: null;
        $skor = $_POST['skor'] ?: null;
        
        $stmt = $conn->prepare("UPDATE partisipasi_siswa SET id_ujian=?, nama_siswa=?, kelas_siswa=?, waktu_selesai=?, skor=? WHERE id=?");
        $stmt->bind_param("isssdi", $id_ujian, $nama_siswa, $kelas_siswa, $waktu_selesai, $skor, $id);
        $stmt->execute();
        $success = "Partisipasi siswa berhasil diupdate!";
    }
    
    elseif ($action == 'delete') {
        $id = $_POST['id'];
        $stmt = $conn->prepare("DELETE FROM partisipasi_siswa WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $success = "Partisipasi siswa berhasil dihapus!";
    }
}

// Get all partisipasi with ujian info
$partisipasis = $conn->query("
    SELECT ps.*, u.judul as ujian_judul, u.kode_ujian, g.username as guru_username,
           COUNT(js.id) as total_jawaban
    FROM partisipasi_siswa ps
    JOIN ujian u ON ps.id_ujian = u.id
    JOIN guru g ON u.id_guru = g.id
    LEFT JOIN jawaban_siswa js ON ps.id = js.id_partisipasi
    GROUP BY ps.id
    ORDER BY ps.id DESC
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
    <title>Manajemen Partisipasi Siswa - Ujian Sekolah</title>
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
                    <h1 class="h2">Manajemen Partisipasi Siswa</h1>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                        <i class="bi bi-plus"></i> Tambah Partisipasi
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
                                        <th>Nama Siswa</th>
                                        <th>Kelas</th>
                                        <th>Waktu Selesai</th>
                                        <th>Skor</th>
                                        <th>Jawaban</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($partisipasis as $partisipasi): ?>
                                    <tr>
                                        <td><?= $partisipasi['id'] ?></td>
                                        <td>
                                            <small class="text-muted"><?= htmlspecialchars($partisipasi['guru_username']) ?></small><br>
                                            <?= htmlspecialchars($partisipasi['ujian_judul']) ?>
                                            <span class="badge bg-primary"><?= $partisipasi['kode_ujian'] ?></span>
                                        </td>
                                        <td><?= htmlspecialchars($partisipasi['nama_siswa']) ?></td>
                                        <td><?= htmlspecialchars($partisipasi['kelas_siswa']) ?></td>
                                        <td>
                                            <?= $partisipasi['waktu_selesai'] ? 
                                                date('d/m/Y H:i', strtotime($partisipasi['waktu_selesai'])) : 
                                                '<span class="text-muted">Belum selesai</span>' ?>
                                        </td>
                                        <td>
                                            <?= $partisipasi['skor'] !== null ? 
                                                '<span class="badge bg-success">' . $partisipasi['skor'] . '</span>' : 
                                                '<span class="text-muted">Belum dinilai</span>' ?>
                                        </td>
                                        <td><span class="badge bg-info"><?= $partisipasi['total_jawaban'] ?></span></td>
                                        <td>
                                            <button class="btn btn-sm btn-warning" onclick="editPartisipasi(<?= htmlspecialchars(json_encode($partisipasi)) ?>)">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger" onclick="deletePartisipasi(<?= $partisipasi['id'] ?>)">
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
                        <h5 class="modal-title">Tambah Partisipasi Siswa</h5>
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
                            <label class="form-label">Nama Siswa</label>
                            <input type="text" class="form-control" name="nama_siswa" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Kelas Siswa</label>
                            <input type="text" class="form-control" name="kelas_siswa" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Waktu Selesai (opsional)</label>
                            <input type="datetime-local" class="form-control" name="waktu_selesai">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Skor (opsional)</label>
                            <input type="number" class="form-control" name="skor" step="0.01" min="0" max="100">
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
                        <h5 class="modal-title">Edit Partisipasi Siswa</h5>
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
                            <label class="form-label">Nama Siswa</label>
                            <input type="text" class="form-control" name="nama_siswa" id="edit_nama_siswa" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Kelas Siswa</label>
                            <input type="text" class="form-control" name="kelas_siswa" id="edit_kelas_siswa" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Waktu Selesai</label>
                            <input type="datetime-local" class="form-control" name="waktu_selesai" id="edit_waktu_selesai">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Skor</label>
                            <input type="number" class="form-control" name="skor" id="edit_skor" step="0.01" min="0" max="100">
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
                        <h5 class="modal-title">Hapus Partisipasi Siswa</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" id="delete_id">
                        <p>Apakah Anda yakin ingin menghapus partisipasi siswa ini? Semua jawaban siswa terkait akan ikut terhapus.</p>
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
        function editPartisipasi(partisipasi) {
            document.getElementById('edit_id').value = partisipasi.id;
            document.getElementById('edit_id_ujian').value = partisipasi.id_ujian;
            document.getElementById('edit_nama_siswa').value = partisipasi.nama_siswa;
            document.getElementById('edit_kelas_siswa').value = partisipasi.kelas_siswa;
            document.getElementById('edit_waktu_selesai').value = partisipasi.waktu_selesai ? partisipasi.waktu_selesai.slice(0,16) : '';
            document.getElementById('edit_skor').value = partisipasi.skor || '';
            new bootstrap.Modal(document.getElementById('editModal')).show();
        }
        
        function deletePartisipasi(id) {
            document.getElementById('delete_id').value = id;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }
    </script>
</body>
</html>