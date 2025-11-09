<?php
require_once 'config/auth.php';
checkAdminLogin();

// Statistik dashboard
$stats = [];

// Total guru
$result = $conn->query("SELECT COUNT(*) as total FROM guru");
$stats['guru'] = $result->fetch_assoc()['total'];

// Total ujian
$result = $conn->query("SELECT COUNT(*) as total FROM ujian");
$stats['ujian'] = $result->fetch_assoc()['total'];

// Total siswa (partisipasi unik)
$result = $conn->query("SELECT COUNT(DISTINCT CONCAT(nama_siswa, kelas_siswa)) as total FROM partisipasi_siswa");
$stats['siswa'] = $result->fetch_assoc()['total'];

// Total pertanyaan
$result = $conn->query("SELECT COUNT(*) as total FROM pertanyaan");
$stats['pertanyaan'] = $result->fetch_assoc()['total'];

// Ujian terbaru
$ujian_terbaru = $conn->query("
    SELECT u.judul, u.kode_ujian, u.status, u.created_at, g.username as guru
    FROM ujian u 
    JOIN guru g ON u.id_guru = g.id 
    ORDER BY u.created_at DESC LIMIT 5
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Ujian Sekolah</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Dashboard</h1>
                </div>
                
                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Guru</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['guru'] ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-people text-primary" style="font-size: 2rem;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-success shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Ujian</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['ujian'] ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-file-earmark-text text-success" style="font-size: 2rem;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-info shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Siswa</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['siswa'] ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-person-badge text-info" style="font-size: 2rem;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Total Pertanyaan</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['pertanyaan'] ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-question-circle text-warning" style="font-size: 2rem;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Exams -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Ujian Terbaru</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Judul Ujian</th>
                                        <th>Kode</th>
                                        <th>Guru</th>
                                        <th>Status</th>
                                        <th>Dibuat</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($ujian_terbaru as $ujian): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($ujian['judul']) ?></td>
                                        <td><span class="badge bg-primary"><?= $ujian['kode_ujian'] ?></span></td>
                                        <td><?= htmlspecialchars($ujian['guru']) ?></td>
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
                                        <td><?= date('d/m/Y H:i', strtotime($ujian['created_at'])) ?></td>
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
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>