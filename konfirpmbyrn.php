<?php
session_start();

// Koneksi ke database
$conn = new mysqli("localhost", "root", "", "kasir_apotik");
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Mulai transaksi
$conn->begin_transaction();

try {
    // Cek login
if (!isset($_SESSION['id'])) {
    throw new Exception("Error: User belum login.");
}

$id_user = intval($_SESSION['id']);
$queryAdmin = "SELECT username FROM admin WHERE id = $id_user LIMIT 1";
$resultAdmin = $conn->query($queryAdmin);
$admin = "Tidak Diketahui";
if ($rowAdmin = $resultAdmin->fetch_assoc()) {
    $admin = $rowAdmin['username'];
}
$nama_member = 'Bukan Member';
    $no_telp = '';
    $status = 'Bukan Member';
    $diskon = 0;
    $totalHarga = 0;
    $produkList = [];

    if (isset($_POST['selected'])) {
        foreach ($_POST['selected'] as $productId) {
            if (isset($_SESSION['cart'][$productId])) {
                $produk = $_SESSION['cart'][$productId];
                $subtotal = $produk['price'] * $produk['qty'];
                $totalHarga += $subtotal;
                $produkList[] = "{$produk['name']} ({$produk['qty']}x)";
            }
        }
    }

    $produkStr = implode(", ", $produkList);
    $tanggalSekarang = date("Y-m-d");
    $potongan = 0;
    $totalSetelahDiskon = $totalHarga;

    if (isset($_POST['no_telp']) && $_POST['no_telp'] !== '') {
    $no_telp = $conn->real_escape_string($_POST['no_telp']);
    $queryMember = "SELECT name, point, status FROM member WHERE phone = '$no_telp' LIMIT 1";
    $resultMember = $conn->query($queryMember);
    if ($rowMember = $resultMember->fetch_assoc()) {
        // ambil nama member
        $nama_member = $rowMember['name'];  
        
        $status = ($rowMember['status'] === 'active') ? 'Member Aktif' : 'Member Tidak Aktif';
        if ($status === 'Member Aktif') {
            $diskon = 10;

            // KURANGI 5 poin untuk penggunaan diskon
            $kurangiPoinQuery = "UPDATE member SET point = point - 5 WHERE phone = '$no_telp' AND point >= 5";
            $conn->query($kurangiPoinQuery);
        }
    } else {
        $nama_member = 'Tidak Ditemukan';
    }

    $potongan = ($totalHarga * $diskon) / 100;
    $totalSetelahDiskon = $totalHarga - $potongan;
}

    // Simpan transaksi dan redirect ke invoice jika form dikirim
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nominal'])) {
        $uang_dibayar = (int)$_POST['nominal'];
        $_POST['produk'] ?? '';
        $totalSetelahDiskon = (int)($_POST['total_harga'] ?? 0);
        $tanggalSekarang = $_POST['tanggal'] ?? date("Y-m-d");
        $admin = $_POST['admin'] ?? 'Admin';
        $kembalian = $uang_dibayar - $totalSetelahDiskon;

// ...existing code...
$stmt = $conn->prepare("INSERT INTO transactions (nama_member, nama_produk, tanggal_beli, admin, harga, total_harga, uang_dibayar, kembalian, phone, potongan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssiiiisi", $nama_member, $produkStr, $tanggalSekarang, $admin, $totalHarga, $totalSetelahDiskon, $uang_dibayar, $kembalian, $no_telp, $potongan);
// ...existing code...
        if ($stmt->execute()) {
            $last_id = $stmt->insert_id;

            // Update stock for each purchased product
            if (isset($_POST['selected'])) {
                foreach ($_POST['selected'] as $productId) {
                    if (isset($_SESSION['cart'][$productId])) {
                        $qtyPurchased = $_SESSION['cart'][$productId]['qty'];

                        // Validasi stok tersedia
                        $stmtCheckStock = $conn->prepare("SELECT qty FROM products WHERE id = ?");
                        $stmtCheckStock->bind_param("i", $productId);
                        $stmtCheckStock->execute();
                        $resultStock = $stmtCheckStock->get_result();
                        if ($rowStock = $resultStock->fetch_assoc()) {
                            if ($rowStock['qty'] < $qtyPurchased) {
                                throw new Exception("Stok produk ID $productId tidak mencukupi.");
                            }
                        } else {
                            throw new Exception("Produk ID $productId tidak ditemukan.");
                        }
                        $stmtCheckStock->close();

                        $updateStockQuery = "UPDATE products SET qty = qty - ? WHERE id = ?";
                        $stmtUpdateStock = $conn->prepare($updateStockQuery);
                        $stmtUpdateStock->bind_param("ii", $qtyPurchased, $productId);
                        $stmtUpdateStock->execute();
                        $stmtUpdateStock->close();
                    }
                }
            }

            // Jika transaksi dilakukan oleh member, update transaction_amount dan point
            if (!empty($no_telp) && $status === 'Member Aktif') {
                $updateMemberQuery = "UPDATE member SET 
                    transaction_amount = transaction_amount + 1,
                    point = point + ?
                    WHERE phone = ?";
                
                $stmtUpdate = $conn->prepare($updateMemberQuery);
                $earned_points = 50; // tetap 50 poin per transaksi
                $stmtUpdate->bind_param("is", $earned_points, $no_telp);
                $stmtUpdate->execute();
                $stmtUpdate->close();
            }

$now = date('Y-m-d H:i:s');
$updateLastTransaction = $conn->prepare("UPDATE member SET last_transaction = ? WHERE phone = ?");
$updateLastTransaction->bind_param("ss", $now, $no_telp);
$updateLastTransaction->execute();
$updateLastTransaction->close();

            // Commit transaksi
            $conn->commit();

            // Hapus item yang telah dibayar dari session cart
foreach ($_POST['selected'] as $productId) {
    unset($_SESSION['cart'][$productId]);
}
  
            header("Location: invoice.php?id=$last_id");
            exit;
        } else {
            throw new Exception("Gagal menyimpan transaksi: " . $conn->error);
        }
    }
} catch (Exception $e) {
    // Rollback transaksi jika terjadi kesalahan
    $conn->rollback();
    die("Terjadi kesalahan: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Konfirmasi Pembayaran</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-green-200 to-green-50 min-h-screen flex items-center justify-center">
<!-- Bagian kanan untuk input nomor telepon -->
<div class="absolute top-4 right-4 flex flex-col items-end space-y-2 text-sm">
  <div class="flex items-center space-x-2">
    <form action="" method="POST">
      <label for="kode-member" class="text-[#5d3a2f] font-semibold">No. Telepon:</label>
      <input type="text" id="kode-member" name="no_telp" placeholder="08xxxxxxxxxx"
           class="px-3 py-1 rounded-md text-black border border-[#5d3a2f] focus:outline-none focus:ring-2 focus:ring-[#5d3a2f]">
      <?php foreach ($_POST['selected'] ?? [] as $id_produk): ?>
        <input type="hidden" name="selected[]" value="<?= htmlspecialchars($id_produk); ?>">
      <?php endforeach; ?>
      <button type="submit" class="bg-green-700 hover:bg-green-800 text-white px-3 py-1 rounded-md">Cek</button>
    </form>
  </div>
    <div id="hasil-member" class="text-[#5d3a2f] font-semibold">
    <?= !empty($no_telp) ? "Nama: $nama_member <br>Status Member: $status" : ''; ?>
  </div>
</div>

<!-- Panel utama konfirmasi -->
<div class="bg-white w-full max-w-lg mx-auto p-6 rounded-2xl shadow-xl border border-green-200">
  <!-- Header -->
  <div class="flex items-center justify-center mb-6">
    <h2 class="ml-3 text-2xl font-bold text-green-700">Konfirmasi Pembayaran</h2>
  </div>

  <!-- Tanggal -->
  <div class="flex justify-between items-center py-2 border-b border-green-100">
    <span class="font-medium text-gray-700">Tanggal Pembelian</span>
    <span class="text-gray-900"><?= $tanggalSekarang; ?></span>
  </div>

    <!-- Nama Member -->
  <div class="flex justify-between items-center py-2 border-b border-green-100">
    <span class="font-medium text-gray-700">Nama Member</span>
    <span class="text-gray-900"><?= htmlspecialchars($nama_member); ?></span>
  </div>

  <!-- Produk -->
  <div class="flex justify-between items-center py-2 border-b border-green-100">
    <span class="font-medium text-gray-700">Produk</span>
    <span class="text-gray-900"><?= htmlspecialchars($produkStr); ?></span>
  </div>

  <!-- Harga -->
  <div class="flex justify-between items-center py-2 border-b border-green-100">
    <span class="font-medium text-gray-700">Harga</span>
    <span class="text-gray-900">Rp <?= number_format($totalHarga, 0, ',', '.'); ?></span>
  </div>

  <!-- Diskon -->
  <div class="flex justify-between items-center py-2 border-b border-green-100">
    <span class="font-medium text-gray-700">Diskon</span>
    <span class="text-gray-900">
      <?= $diskon; ?>% (-Rp<?= number_format($potongan, 0, ',', '.'); ?>)
    </span>
  </div>

  <!-- Total -->
  <div class="flex justify-between items-center py-3 mt-4 bg-green-100 px-4 rounded-lg border border-green-200">
    <span class="font-bold text-green-800 text-lg">Total</span>
    <span class="font-bold text-green-700 text-lg">
      Rp<?= number_format($totalSetelahDiskon, 0, ',', '.'); ?>
    </span>
  </div>

  <!-- Admin -->
  <div class="flex justify-between items-center py-2 mt-4">
    <span class="font-medium text-gray-700">Admin</span>
    <span class="text-gray-900"><?= htmlspecialchars($admin); ?></span>
  </div>

<!-- Form pembayaran langsung -->
<form action="" method="POST" class="mt-6" id="cash-section">
  <input type="hidden" name="tanggal" value="<?= $tanggalSekarang; ?>">
  <input type="hidden" name="member" value="<?= htmlspecialchars($nama_member); ?>">
  <input type="hidden" name="produk" value="<?= htmlspecialchars($produkStr); ?>">
  <input type="hidden" name="total_harga" value="<?= $totalSetelahDiskon; ?>">
  <input type="hidden" name="admin" value="<?= htmlspecialchars($admin); ?>">
  <input type="hidden" name="no_telp" value="<?= htmlspecialchars($no_telp); ?>">

  <?php foreach ($_POST['selected'] ?? [] as $id_produk): ?>
    <input type="hidden" name="selected[]" value="<?= htmlspecialchars($id_produk); ?>">
  <?php endforeach; ?>

  <label class="block mt-2 text-black">Masukkan Nominal Cash</label>
  <input type="number" name="nominal" class="w-full px-2 py-1 rounded-md bg-gray-300 text-black" required>
  <p id="warning" class="text-red-500 text-sm mt-1 hidden">Nominal harus lebih besar atau sama dengan total.</p>
  <button id="btn-bayar" type="submit" class="w-full mt-4 bg-green-700 hover:bg-green-800 text-white py-2 rounded-md" disabled>Konfirmasi Pembayaran</button>
</form>

<!-- Toast -->
<div id="toast-poin" class="fixed top-4 left-1/2 transform -translate-x-1/2 bg-green-500 text-white px-4 py-2 rounded shadow-lg hidden z-50 transition duration-500 ease-in-out">
  ✅ Poin member berhasil diperbarui! 50 poin telah ditambahkan!
</div>

  <script>
const inputNominal = document.querySelector('input[name="nominal"]');
const btnBayar = document.getElementById('btn-bayar');
const totalHarusDibayar = <?= $totalSetelahDiskon; ?>;

inputNominal.addEventListener('input', function () {
  const nilai = parseInt(this.value) || 0;
  btnBayar.disabled = nilai < totalHarusDibayar;
});

  const warning = document.getElementById('warning');

inputNominal.addEventListener('input', function () {
  const nilai = parseInt(this.value) || 0;
  if (nilai < totalHarusDibayar) {
    btnBayar.disabled = true;
    warning.classList.remove('hidden');
  } else {
    btnBayar.disabled = false;
    warning.classList.add('hidden');
  }
});

  <?php if (isset($_SESSION['poin_diperbarui'])): ?>
    const toast = document.getElementById('toast-poin');
    toast.classList.remove('hidden');
    setTimeout(() => toast.classList.add('hidden'), 3000);
    <?php unset($_SESSION['poin_diperbarui']); ?>
  <?php endif; ?>
</script>

</body>
</html>