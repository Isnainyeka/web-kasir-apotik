<?php
session_start();
// Koneksi ke database
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "kasir_apotik";
$conn = new mysqli($servername, $username, $password, $dbname);

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $token = $_POST['token'] ?? null;
    $password = $_POST['new_password'] ?? null;
    $confirm_password = $_POST['confirm_password'] ?? null;

    if ($password !== $confirm_password) {
        die("Password tidak cocok!");
    }

    // Cek token dalam database
    $stmt = $conn->prepare("SELECT email FROM password_reset WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    $reset = $result->fetch_assoc();

    if (!$reset) {
        die("Token tidak valid!");
    }

    $email = $reset['email'];

    // Update password di tabel admin (TANPA HASH)
    $stmt = $conn->prepare("UPDATE admin SET password = ? WHERE email = ?");
    $stmt->bind_param("ss", $password, $email);
    $stmt->execute();

    // Hapus token dari database
    $stmt = $conn->prepare("DELETE FROM password_reset WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();

    $_SESSION['message'] = "Password berhasil diubah!";
    header("Location: login.php");
    exit();
}
?>
