<?php
session_start();
// Koneksi ke database
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "kasir_apotik";
$conn = new mysqli($servername, $username, $password, $dbname);

require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];

    // Cek apakah email terdaftar
    $stmt = $conn->prepare("SELECT email FROM admin WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();
$stmt->close(); // Tambahkan ini sebelum menjalankan query baru!

    if ($admin) {
        $token = bin2hex(random_bytes(50)); // Token unik
        $expires = date("Y-m-d H:i:s", strtotime("+1 hour")); // Berlaku 1 jam

        // Simpan token ke database
        $stmt = $conn->prepare("INSERT INTO password_reset (email, token, expires) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $email, $token, $expires);
$stmt->execute();
$stmt->close();

        // Kirim email dengan tautan reset
        $mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'ssnnaaoke@gmail.com'; // Ganti dengan email pengirim
    $mail->Password = 'y x e c z x d b g i k d v f n z'; // Ganti dengan password aplikasi email
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    $mail->setFrom('ssnnaaoke@gmail.com', 'Admin');
    $mail->addAddress($email);
    $mail->isHTML(true);
    $mail->Subject = 'Reset Password';
    $mail->Body = "Klik link berikut untuk mereset password Anda: <a href='http://localhost/kasir_apotik/resetpw.php?token=$token'>Reset Password</a>";

    $mail->SMTPDebug = 2; // Aktifkan debugging

    if (!$mail->send()) {
        echo "Gagal mengirim email! Error: " . $mail->ErrorInfo;
    } else {
        echo "Email berhasil dikirim!";
    }
    
    $_SESSION['message'] = "Email reset password telah dikirim!";
} catch (Exception $e) {
    $_SESSION['message'] = "Gagal mengirim email! Error: " . $e->getMessage();
}
    } else {
        $_SESSION['message'] = "Email tidak terdaftar!";
    }
}
header("Location: forgotpw.php");
exit();