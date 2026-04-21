<?php
session_start();

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "kasir_apotik";

// Buat koneksi
$conn = new mysqli($servername, $username, $password, $dbname);

// Cek koneksi
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Koneksi gagal: " . $conn->connect_error]);
    exit();
}

// Validasi input ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "ID tidak valid!";
    exit();
}

$id = intval($_GET['id']); // Konversi aman ke integer

// Cek status member
$checkStatus = $conn->prepare("SELECT status FROM member WHERE id = ?");
$checkStatus->bind_param("i", $id);
$checkStatus->execute();
$result = $checkStatus->get_result();

if ($result->num_rows === 0) {
    echo "Member tidak ditemukan!";
    $checkStatus->close();
    $conn->close();
    exit();
}

$row = $result->fetch_assoc();

// Cegah hapus jika member aktif
if (strtolower($row['status']) === 'active') {
    echo "Member dengan status 'Active' tidak bisa dihapus!";
    $checkStatus->close();
    $conn->close();
    exit();
}

// Hapus member jika status non-active
$stmt = $conn->prepare("DELETE FROM member WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
echo "Member berhasil dihapus";
} else {
    echo "Gagal menghapus member";
}

$stmt->close();
$checkStatus->close();
$conn->close();
?>
