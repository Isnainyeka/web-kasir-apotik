<?php
session_start();
$conn = new mysqli("localhost", "root", "", "kasir_apotik");
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

$id_transaksi = $_GET['id'] ?? null;
if (!$id_transaksi) {
    die("ID transaksi tidak ditemukan.");
}

$stmt = $conn->prepare("SELECT * FROM transactions WHERE id_transaksi = ?");
$stmt->bind_param("i", $id_transaksi);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();
if (!$data) {
    die("Data transaksi tidak ditemukan.");
}

$tanggal_beli = '';
if (!empty($data['tanggal_beli'])) {
    $tanggal_beli = date('d M Y', strtotime($data['tanggal_beli']));
}

// Format nomor WA
$phone = trim($data['phone'] ?? '');
$nomor_wa = '';
if ($phone) {
    $nomor_wa = preg_replace('/^0/', '62', $phone); // Ubah awalan 0 jadi 62
}

// Siapkan pesan WhatsApp
$pesan_wa = "*Apotek Naacare Pharma Medica - Struk Pembayaran*\n";
$pesan_wa .= "Order ID: #" . $data['id_transaksi'] . "\n";
$pesan_wa .= "Tanggal: " . $tanggal_beli . "\n";
$pesan_wa .= "Kasir: " . $data['admin'] . "\n";
$pesan_wa .= "Member: " . $data['nama_member'] . "\n";
$pesan_wa .= "-------------------------\n";
$pesan_wa .= "Item:\n";

// pecah nama produk yang disimpan dalam satu field
$produkList = explode(",", $data['nama_produk']);

foreach ($produkList as $produk) {
    $pesan_wa .= "- " . trim($produk) . "\n";
}
$pesan_wa .= "-------------------------\n";
$pesan_wa .= "Harga: Rp" . number_format($data['harga'], 0, ',', '.') . "\n";
$totalSebelumDiskon = $data['total_harga'] + $data['potongan'];
$persenDiskon = ($totalSebelumDiskon > 0) ? round(($data['potongan'] / $totalSebelumDiskon) * 100) : 0;
$pesan_wa .= "Diskon: {$persenDiskon}% (-Rp" . number_format($data['potongan'], 0, ',', '.') . ")\n";
$pesan_wa .= "-------------------------\n";
$pesan_wa .= "Total Belanja: Rp" . number_format($data['total_harga'], 0, ',', '.') . "\n";
$pesan_wa .= "Uang Dibayar: Rp" . number_format($data['uang_dibayar'], 0, ',', '.') . "\n";
$pesan_wa .= "Uang Kembalian: Rp" . number_format($data['kembalian'], 0, ',', '.') . "\n";
$pesan_wa .= "-------------------------\n";
$pesan_wa .= "Terima kasih telah berbelanja di Apotek Naacare Pharma Medica";

$pesan_wa = rawurlencode($pesan_wa);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Struk Pembayaran - Apotek</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Poppins', sans-serif; }
  </style>
</head>
<body class="bg-gradient-to-br from-green-200 to-green-50 to-white min-h-screen flex items-center justify-center p-4">

<div class="receipt-wrapper">
  <!-- Struk -->
  <div class="receipt">
    <!-- Robekan Atas -->
    <div class="tear top"></div>

    <div class="receipt-content">
      <!-- Header -->
      <div class="text-center font-bold text-lg mb-1">Apotek Naacare Pharma Medica</div>
      <div class="text-center text-xs mb-2">Babelan, Bekasi</div>
      <hr class="border-dashed border-gray-400 mb-3">

      <!-- Info Transaksi -->
<div class="flex justify-between text-sm">
  <span>Order ID</span>
  <span>#<?= $data['id_transaksi'] ?></span>
</div>
<div class="flex justify-between text-sm">
  <span>Tanggal</span>
  <span><?= $tanggal_beli ?></span>
</div>
<div class="flex justify-between text-sm">
  <span>Kasir</span>
  <span><?= htmlspecialchars($data['admin']) ?></span>
</div>
<div class="flex justify-between text-sm mb-3">
  <span>Member</span>
  <span><?= htmlspecialchars($data['nama_member'] ?? 'Bukan Member') ?></span>
</div>

<!-- Produk -->
<div class="flex font-bold border-b border-gray-300 pb-1 mb-1">
  <span class="flex-1 text-left">Item</span>
  <span class="w-24 text-right">Harga</span>
</div>

<?php
$produkList = explode(",", $data['nama_produk']);
$first = true;

foreach ($produkList as $produk): 
  // hapus newline & spasi berlebih
  $produk = trim(preg_replace('/\s+/', ' ', $produk));
?>
  <div class="flex text-xs items-start">
  <span class="flex-1 text-left">
    <?= htmlspecialchars(trim(preg_replace('/\s+/', ' ', $produk))) ?>
  </span>
  <?php if ($first): ?>
    <span class="w-24 text-right">Rp<?= number_format($data['harga'], 0, ',', '.') ?></span>
    <?php $first = false; ?>
  <?php else: ?>
    <span class="w-24 text-right"></span>
  <?php endif; ?>
</div>
<?php endforeach; ?>

      <hr class="border-dashed border-gray-400 my-3">

      <!-- Ringkasan -->
      <div class="flex justify-between text-sm text-red-600">
        <span>Diskon</span>
        <span><?= $persenDiskon ?>% (-Rp<?= number_format($data['potongan'], 0, ',', '.') ?>)</span>
      </div>
      <div class="flex justify-between text-sm">
        <span>Total Belanja</span>
        <span>Rp<?= number_format($data['total_harga'], 0, ',', '.') ?></span>
      </div>
      <div class="flex justify-between text-sm">
        <span>Jumlah Uang</span>
        <span>Rp<?= number_format($data['uang_dibayar'], 0, ',', '.') ?></span>
      </div>
      <div class="flex justify-between text-sm font-bold text-green-700">
        <span>Kembalian</span>
        <span>Rp<?= number_format($data['kembalian'], 0, ',', '.') ?></span>
      </div>

      <hr class="border-gray-300 my-3">

      <!-- Status -->
      <div class="text-center text-green-600 font-bold text-sm mb-2">PEMBAYARAN BERHASIL</div>
      <div class="text-center text-[10px] text-gray-600">Terima kasih telah berbelanja!</div>
    </div>

    <!-- Robekan Bawah -->
    <div class="tear bottom"></div>
  </div>

  <!-- Tombol di luar struk -->
  <?php if ($nomor_wa): ?>
    <a 
      href="https://wa.me/<?= $nomor_wa ?>?text=<?= $pesan_wa ?>" 
      target="_blank" 
      class="bg-green-500 hover:bg-green-600 text-white rounded-full px-4 py-2 text-sm block mx-auto mt-3 text-center">
      📩 Kirim ke WhatsApp
    </a>
  <?php else: ?>
    <div class="text-red-500 text-xs text-center mt-3">Nomor WhatsApp tidak tersedia</div>
  <?php endif; ?>

  <a href="dashboard.php" class="bg-gray-800 hover:bg-gray-900 text-white rounded-full px-4 py-2 text-sm block mx-auto mt-2 text-center">
    Selesai
  </a>
</div>

<style>
body {
  background: #f0fdf4;
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
  font-family: monospace;
}

.receipt-wrapper {
  text-align: center;
}

.receipt {
  background: white;
  width: 340px;
  display: inline-block;
  box-shadow: 0 0 10px rgba(0,0,0,0.1);
}

.receipt-content {
  padding: 15px;
}

.tear {
  height: 20px;
  background: repeating-linear-gradient(
    -45deg,
    white 0,
    white 8px,
    transparent 8px,
    transparent 16px
  );
  border-bottom: 1px dashed #ccc;
}

.tear.bottom {
  transform: rotate(180deg);
  border-bottom: none;
  border-top: 1px dashed #ccc;
}
</style>
</body>
</html>
