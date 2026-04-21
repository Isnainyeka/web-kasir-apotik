<?php
session_start();

// Koneksi ke database
$host = "localhost";
$dbname = "kasir_apotik";
$username_db = "root";
$password_db = "";

$conn = new mysqli($host, $username_db, $password_db, $dbname);

// Cek koneksi database
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Ambil ID transaksi dari URL
$id_transaksi = $_GET['id'] ?? null;
if (!$id_transaksi) {
    die("ID transaksi tidak ditemukan.");
}

// Ambil data transaksi + nama member (JOIN dengan tabel member)
$stmt = $conn->prepare("
    SELECT t.*, m.name AS nama_member 
    FROM transactions t 
    LEFT JOIN member m ON t.phone = m.phone 
    WHERE t.id_transaksi = ?
");
$stmt->bind_param("i", $id_transaksi);
$stmt->execute();
$result = $stmt->get_result();
$transactions = $result->fetch_assoc();

if (!$transactions) {
    die("Data transaksi tidak ditemukan.");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Invoice</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-green-200 to-green-50 min-h-screen">
  <div class="max-w-2xl mx-auto mt-6 bg-white rounded-lg shadow-lg overflow-hidden border border-green-200">

    <!-- HEADER -->
    <div class="bg-green-700 text-white p-6 flex items-center gap-4">
      <div>
        <h1 class="text-2xl font-bold">Invoice Transaksi</h1>
      </div>
    </div>

    <!-- TOTAL HARGA -->
    <div class="bg-green-50 p-6 text-center">
      <p class="text-sm text-green-700">Total Bayar</p>
      <h2 class="text-3xl font-bold text-green-700">
        Rp <?= number_format($transactions['total_harga'], 0, ',', '.') ?>
      </h2>
    </div>

    <!-- DETAIL TRANSAKSI -->
<div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-y-4 gap-x-6 text-gray-700">

  <!-- ID Transaksi -->
  <div>
    <p class="text-sm text-gray-500">ID Transaksi</p>
    <p class="font-semibold"><?= $transactions['id_transaksi'] ?></p>
  </div>

  <!-- Nama Produk -->
  <div>
    <p class="text-sm text-gray-500">Nama Produk</p>
    <p class="font-semibold">
      <?= htmlspecialchars($transactions['nama_produk'] ?? 'Produk tidak tersedia'); ?>
    </p>
  </div>

  <!-- Kasir -->
  <div>
    <p class="text-sm text-gray-500">Kasir</p>
    <p class="font-semibold"><?= htmlspecialchars($transactions['admin'] ?? 'Kasir tidak diketahui'); ?></p>
  </div>

  <!-- Harga -->
  <div>
    <p class="text-sm text-gray-500">Harga</p>
    <p class="font-semibold">Rp <?= number_format($transactions['harga'], 0, ',', '.') ?></p>
  </div>

  <!-- Tanggal -->
  <div>
    <p class="text-sm text-gray-500">Tanggal Pembelian</p>
    <p class="font-semibold"><?= date('d-m-Y', strtotime($transactions['tanggal_beli'])) ?></p>
  </div>

  <!-- Diskon -->
  <div>
    <p class="text-sm text-gray-500">Diskon</p>
    <p class="font-semibold text-red-500">
      <?php
          $total = $transactions['total_harga'] + $transactions['potongan'];
          $persen = ($total > 0) ? round(($transactions['potongan'] / $total) * 100) : 0;
      ?>
      <?= $persen ?>% (-Rp <?= number_format($transactions['potongan'], 0, ',', '.') ?>)
    </p>
  </div>

  <!-- Nama Member -->
  <div>
    <p class="text-sm text-gray-500">Nama Member</p>
    <p class="font-semibold">
      <?= htmlspecialchars($transactions['nama_member'] ?? 'Bukan Member'); ?>
    </p>
  </div>



  <!-- Uang Dibayar -->
  <div>
    <p class="text-sm text-gray-500">Uang Dibayar</p>
    <p class="font-semibold">Rp <?= number_format($transactions['uang_dibayar'], 0, ',', '.') ?></p>
  </div>

  <!-- 🔹 Uang Kembalian (ditaruh di tengah) -->
  <div class="sm:col-span-2 text-center bg-gray-50 rounded-lg p-3">
    <p class="text-sm text-gray-500">Uang Kembalian</p>
    <p class="font-bold text-green-600 text-lg">Rp <?= number_format($transactions['kembalian'], 0, ',', '.') ?></p>
  </div>
</div>


    <!-- BUTTON -->
    <div class="p-6 flex justify-center">
      <a href="struk.php?id=<?= $transactions['id_transaksi'] ?>"
         class="bg-green-700 hover:bg-green-800 text-white font-semibold py-2 px-6 rounded-lg shadow">
        Lihat Struk
      </a>
    </div>

  </div>
</body>
</html>
