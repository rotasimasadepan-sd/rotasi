<?php
// Konfigurasi koneksi database
define('DB_HOST', 'localhost');
define('DB_USER', 'rotz3716_ujian_sekolah'); // Ganti dengan username database Anda
define('DB_PASS', 'Otongkecil6a'); // Ganti dengan password database Anda
define('DB_NAME', 'rotz3716_ujian_sekolah'); // Ganti dengan nama database Anda

// Membuat koneksi ke database
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Memeriksa koneksi
if ($conn->connect_error) {
    die("Koneksi Gagal: " . $conn->connect_error);
}

// Memulai session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
