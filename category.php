<?php
session_start();

$koneksi = new mysqli("localhost", "root", "", "kasir_apotik");
if ($koneksi->connect_error) {
    die("Koneksi gagal: " . $koneksi->connect_error);
}

// Cek apakah user sudah login
if (isset($_SESSION['id'])) {
    $id_user = $_SESSION['id'];
    $query = $koneksi->query("SELECT * FROM admin WHERE id = $id_user");
    $akun = $query->fetch_assoc();

    // Set role dari database (kalau belum diset di session)
    if (!isset($_SESSION['role']) && isset($akun['role'])) {
        $_SESSION['role'] = $akun['role'];
    }
} else {
    // Jika session id tidak tersedia, redirect ke login
    header("Location: login.php");
    exit();
}

// Cek role valid atau tidak
if (!isset($_SESSION['role'])) {
    echo "<script>alert('Session role tidak ditemukan!'); window.location='login.php';</script>";
    exit();
}
$role = $_SESSION['role']; // Buat variabel lokal biar lebih aman

// Proses hapus kategori jika ada parameter ?hapus=
if (isset($_GET['hapus']) && is_numeric($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];

    // Cek apakah ada produk dengan kategori ini dan stok > 0
    $cekProduk = $koneksi->query("SELECT COUNT(*) as total FROM products WHERE fid_category = $id AND qty > 0");
    $cek = $cekProduk->fetch_assoc();

    if ($cek['total'] > 0) {
        echo "<script>alert('Tidak bisa menghapus kategori karena masih ada produk dengan stok aktif!'); window.location='category.php';</script>";
    } else {
        $hapus = $koneksi->query("DELETE FROM category WHERE id = $id");
        if ($hapus) {
            echo "<script>alert('Kategori berhasil dihapus!'); window.location='category.php';</script>";
        } else {
            echo "<script>alert('Gagal menghapus kategori!'); window.location='category.php';</script>";
        }
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Kategori Obat</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-gray-800">

<div class="flex min-h-screen">
  <!-- Sidebar -->
  <aside class="w-64 bg-[#7db073] text-white flex flex-col shadow-lg">
    <div class="px-6 py-6 text-2xl font-bold text-center border-b border-green-500">
  <img src="logoapotek.png" alt="Apotek Logo" class="mx-auto h-24">
</div>
<nav class="flex-1 px-4 py-6 space-y-2">
  <?php if ($role === 'superadmin'): ?>
    <a href="dashboard.php" class="flex items-center px-4 py-2 rounded-lg hover:bg-green-700 transition">
      <span class="ml-2">Dashboard</span>
    </a>
    <a href="kasir.php" class="flex items-center px-4 py-2 rounded-lg hover:bg-green-700 transition">
      <span class="ml-2">Kasir</span>
    </a>
    <a href="member.php" class="flex items-center px-4 py-2 rounded-lg hover:bg-green-700 transition">
      <span class="ml-2">Members</span>
    </a>
    <a href="category.php" class="flex items-center px-4 py-2 rounded-lg bg-[#68975f] font-semibold">
      <span class="ml-2">Category</span>
    </a>
    <a href="product.php" class="flex items-center px-4 py-2 rounded-lg hover:bg-green-700 transition">
      <span class="ml-2">Products</span>
    </a>
    <a href="report.php" class="flex items-center px-4 py-2 rounded-lg hover:bg-green-700 transition">
      <span class="ml-2">Report</span>
    </a>
  <?php elseif ($role === 'kasir'): ?>
    <a href="dashboard.php" class="flex items-center px-4 py-2 rounded-lg hover:bg-green-700 transition">
      <span class="ml-2">Dashboard</span>
    </a>
        <a href="kasir.php" class="flex items-center px-4 py-2 rounded-lg hover:bg-green-700 transition">
      <span class="ml-2">Kasir</span>
    </a>
    <a href="member.php" class="flex items-center px-4 py-2 rounded-lg hover:bg-green-700 transition">
      <span class="ml-2">Members</span>
    </a>
    <a href="transaksi.php" class="flex items-center px-4 py-2 rounded-lg hover:bg-green-700 transition">
      <span class="ml-2">Transaksi</span>
    </a>
    <a href="report.php" class="flex items-center px-4 py-2 rounded-lg hover:bg-green-700 transition">
      <span class="ml-2">Report</span>
    </a>
  <?php endif; ?>
</nav>

    <!-- Profile Akun -->
    <div class="px-4 py-4 border-t border-green-500">
      <button onclick="toggleModal()" class="w-full text-left px-4 py-2 rounded-lg bg-[#68975f] hover:bg-[#5a824e] transition">
        👤 <?php echo $akun['username']; ?>
      </button>
      <a href="logout.php" class="block mt-3 text-center py-2 rounded bg-red-500 hover:bg-red-600 transition">Logout</a>
    </div>
  </aside>

  <!-- Konten Utama -->
  <main class="flex-1 p-10 overflow-y-auto bg-[#F9F6F1]">
    <div class="flex items-center justify-between mb-8">
      <h1 class="text-3xl font-bold text-gray-800">Kategori Obat</h1>
      <a href="tambah_kategori.php" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded shadow">
        ➕ Tambah Kategori
      </a>
    </div>

<div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
  <?php
  $result = $koneksi->query("SELECT * FROM category");
  while ($row = $result->fetch_assoc()):
    $nama = strtolower($row['category']);

    // Default warna
    $warna = 'bg-gray-400';
    $simbol = '';

if (str_contains($nama, 'obat bebas terbatas')) {
    $warna = 'bg-blue-600';
} elseif (str_contains($nama, 'obat bebas')) {
    $warna = 'bg-green-600';
} elseif (str_contains($nama, 'obat keras')) {
    $warna = 'bg-red-700';
    $simbol = 'K';
} elseif (str_contains($nama, 'psikotropika') || str_contains($nama, 'narkotika')) {
    $warna = 'bg-red-600';
    $simbol = '+';
} elseif (str_contains($nama, 'obat jamu')) {
    $warna = 'bg-yellow-400';
    $simbol = '🌿';
}
  ?>
    <div class="bg-white rounded-xl shadow border p-5 flex flex-col items-center text-center hover:shadow-md transition">
      <!-- Bulatan simbol -->
      <div class="w-20 h-20 rounded-full border-4 border-black flex items-center justify-center text-white text-2xl font-bold <?= $warna ?> mb-4">
        <?= $simbol ?>
      </div>

      <!-- Nama kategori -->
      <h2 class="font-semibold text-lg text-gray-700"><?= htmlspecialchars($row['category']) ?></h2>

      <!-- Tombol aksi -->
      <div class="mt-4 flex gap-2">
        <a href="edit_kategori.php?id=<?= $row['id'] ?>" class="px-4 py-1 bg-blue-500 hover:bg-blue-600 text-white text-sm rounded">✏️</a>
        <?php
  $cekProduk = $koneksi->query("SELECT COUNT(*) as qty FROM products WHERE fid_category = {$row['id']}")->fetch_assoc()['qty'];
  if ($cekProduk == 0):
?>
  <a href="category.php?hapus=<?= $row['id']; ?>" onclick="return confirm('Yakin ingin menghapus kategori ini?')" class="px-4 py-1 bg-red-500 hover:bg-red-600 text-white text-sm rounded">🗑️</a>
<?php else: ?>
  <button class="px-4 py-1 bg-gray-400 text-white text-sm rounded cursor-not-allowed" disabled>🗑️</button>
<?php endif; ?>
      </div>
    </div>
  <?php endwhile; ?>
</div>
  </main>
</div>

<!-- Modal Akun -->
<div id="modalAkun" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
  <div class="bg-white rounded-xl shadow-lg w-full max-w-md p-6 relative">
    <button onclick="toggleModal()" class="absolute top-2 right-3 text-gray-600 hover:text-red-500 text-xl">&times;</button>
    <h2 class="text-2xl font-semibold text-green-700 mb-4">Profil Akun</h2>
    <?php if (!empty($akun['image'])): ?>
  <img src="uploads/<?= $akun['image']; ?>" class="w-24 h-24 rounded-full mx-auto mb-4 border-4 border-green-300 object-cover" alt="Foto Profil">
<?php else: ?>
  <div class="w-24 h-24 mx-auto mb-4 rounded-full bg-green-200 flex items-center justify-center text-2xl text-white font-bold border-4 border-green-300">
    <?= strtoupper($akun['username'][0]); ?>
  </div>
<?php endif; ?>
    <div class="space-y-3 text-sm text-gray-700">
      <p><strong>Username:</strong> <?= $akun['username']; ?></p>
      <p><strong>Email:</strong> <?= $akun['email']; ?></p>
      <p><strong>No. Telepon:</strong> <?= $akun['telepon']; ?></p>
      <p><strong>Role:</strong> <?= ucfirst($akun['role']); ?></p>
      <p><strong>Status:</strong> <?= $akun['status']; ?></p>
      <p><strong>Jenis Kelamin:</strong> <?= ucfirst($akun['gender']); ?></p>
    </div>
  </div>
</div>

<!-- Script Modal -->
<script>
  function toggleModal() {
    const modal = document.getElementById("modalAkun");
    modal.classList.toggle("hidden");
    modal.classList.toggle("flex");
  }
</script>
</body>
</html>
