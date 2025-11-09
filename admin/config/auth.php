<?php
require_once '../backend/db.php';

// Function untuk cek login admin
function checkAdminLogin() {
    if (!isset($_SESSION['admin_id'])) {
        header('Location: login.php');
        exit();
    }
}

// Function untuk login admin
function loginAdmin($username, $password) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT id, username, password, nama_lengkap, email FROM admin WHERE username = ? AND status = 'aktif'");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $admin = $result->fetch_assoc();
        if (password_verify($password, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_nama'] = $admin['nama_lengkap'];
            $_SESSION['admin_email'] = $admin['email'];
            return true;
        }
    }
    return false;
}

// Function untuk logout admin
function logoutAdmin() {
    session_destroy();
    header('Location: index.php');
    exit();
}
?>