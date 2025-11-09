<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse" style="margin-top: 56px;">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php">
                    <i class="bi bi-house"></i> Dashboard
                </a>
            </li>
            
            <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                <span>Manajemen Data</span>
            </h6>
            
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'admin.php' ? 'active' : '' ?>" href="admin.php">
                    <i class="bi bi-shield-check"></i> Admin
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'guru.php' ? 'active' : '' ?>" href="guru.php">
                    <i class="bi bi-people"></i> Guru
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'ujian.php' ? 'active' : '' ?>" href="ujian.php">
                    <i class="bi bi-file-earmark-text"></i> Ujian
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'pertanyaan.php' ? 'active' : '' ?>" href="pertanyaan.php">
                    <i class="bi bi-question-circle"></i> Pertanyaan
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'opsi_jawaban.php' ? 'active' : '' ?>" href="opsi_jawaban.php">
                    <i class="bi bi-list-check"></i> Opsi Jawaban
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'partisipasi_siswa.php' ? 'active' : '' ?>" href="partisipasi_siswa.php">
                    <i class="bi bi-person-badge"></i> Partisipasi Siswa
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'jawaban_siswa.php' ? 'active' : '' ?>" href="jawaban_siswa.php">
                    <i class="bi bi-chat-square-text"></i> Jawaban Siswa
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'gambar_pertanyaan.php' ? 'active' : '' ?>" href="gambar_pertanyaan.php">
                    <i class="bi bi-image"></i> Gambar Pertanyaan
                </a>
            </li>
        </ul>
    </div>
</nav>