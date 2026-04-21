<?php
session_start();

// Cek apakah admin sedang login
if (isset($_SESSION['id'])) {
    // Koneksi ke database
    $conn = new mysqli("localhost", "root", "", "kasir_apotik");
    
    if (!$conn->connect_error) {
        $id = $_SESSION['id'];
        // Update status admin jadi Tidak Aktif
        $conn->query("UPDATE admin SET status = 'Tidak Aktif' WHERE id = $id");
        $conn->close();
    }
}

// Hapus semua session
session_unset();
session_destroy();

// Redirect ke halaman login
header("Location: login.php");
exit();
?>
