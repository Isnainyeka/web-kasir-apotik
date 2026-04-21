<?php
header('Content-Type: application/json');

// Koneksi ke database
$host = "localhost";
$dbname = "kasir_apotik";
$username_db = "root";  
$password_db = "";

$conn = new mysqli($host, $username_db, $password_db, $dbname);

// Cek koneksi database
if ($conn->connect_error) {
    echo json_encode(['error' => 'Koneksi gagal: ' . $conn->connect_error]);
    exit;
}

// Ambil barcode dari parameter GET
$barcode = isset($_GET['barcode']) ? trim($_GET['barcode']) : '';

if (empty($barcode)) {
    echo json_encode(['error' => 'Barcode tidak boleh kosong']);
    exit;
}

// Cari produk berdasarkan barcode
$stmt = $conn->prepare("SELECT id, product_name, selling_price, qty FROM products WHERE barcode = ?");
$stmt->bind_param("s", $barcode);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    // Produk ditemukan
    echo json_encode([
        'success' => true,
        'id_produk' => $row['id'],
        'product_name' => $row['product_name'],
        'selling_price' => $row['selling_price'],
        'qty' => $row['qty'],
        'message' => 'Produk ditemukan'
    ]);
} else {
    // Produk tidak ditemukan
    echo json_encode([
        'success' => false,
        'message' => 'Produk dengan barcode ' . $barcode . ' tidak ditemukan'
    ]);zz
}

$stmt->close();
$conn->close();
?>