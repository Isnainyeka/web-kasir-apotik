<?php
session_start();

// Koneksi ke database
$host = "localhost";
$dbname = "kasir_apotik";
$username_db = "root";
$password_db = "";

$conn = new mysqli($host, $username_db, $password_db, $dbname);

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Cek apakah user sudah login
if (isset($_SESSION['id'])) {
    $id_user = $_SESSION['id'];
    $query = $conn->query("SELECT * FROM admin WHERE id = $id_user");
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

// Ambil daftar kategori
$kategori_result = $conn->query("SELECT id, category FROM category ORDER BY category ASC");
$kategori_list = [];
if ($kategori_result) {
    while ($kat = $kategori_result->fetch_assoc()) {
        $kategori_list[] = $kat;
    }
}

// Ambil data produk dari database
$sql = "SELECT p.*, c.category FROM products p LEFT JOIN category c ON p.fid_category = c.id";
$result = $conn->query($sql);

$id = $_SESSION['id'];
$admin = $conn->query("SELECT * FROM admin WHERE id = $id")->fetch_assoc();

if (isset($_GET['hapus'])) {
    $id_hapus = intval($_GET['hapus']);

    // Ambil data produk
    $cek = $conn->query("SELECT qty, image, expiry_date FROM products WHERE id = $id_hapus")->fetch_assoc();

    if ($cek) {
        $today = date('Y-m-d');
        $expiry_date = $cek['expiry_date'];

        // Cek apakah stok habis atau sudah expired
        if (
    $cek['qty'] == 0 || 
    (!empty($expiry_date) && strtotime($expiry_date) <= strtotime($today))
) {
            
            // Hapus gambar kalau ada
            if (!empty($cek['image']) && file_exists('uploads/' . $cek['image'])) {
                unlink('uploads/' . $cek['image']);
            }

            // Hapus produk dari database
            $conn->query("DELETE FROM products WHERE id = $id_hapus");
            header("Location: product.php");
            exit();
        } else {
            // Kalau belum expired & stok masih ada, tolak hapus
            echo "<script>alert('Produk belum kadaluarsa dan stok masih ada, tidak bisa dihapus!'); window.location='product.php';</script>";
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Data Produk</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet"/>
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
    <a href="category.php" class="flex items-center px-4 py-2 rounded-lg hover:bg-green-700 transition">
      <span class="ml-2">Category</span>
    </a>
    <a href="product.php" class="flex items-center px-4 py-2 rounded-lg bg-[#68975f] font-semibold">
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
  <div class="flex items-center justify-between mb-6">
    <h1 class="text-3xl font-bold text-gray-800">Data Produk</h1>
    <a href="tambah_obat.php" class="inline-block px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg shadow transition">
      ➕ Tambah Produk
    </a>
  </div>

  <div class="flex flex-wrap gap-3 mb-6">
  <!-- Tombol Semua -->
  <button onclick="filterProduk('all')" 
          class="px-5 py-2 bg-green-700 text-white font-semibold rounded-full shadow-md hover:bg-green-800 transition duration-300">
    Semua
  </button>

  <!-- Tombol kategori -->
  <?php foreach ($kategori_list as $kat): ?>
    <button onclick="filterProduk('<?= $kat['id'] ?>')" 
            class="px-5 py-2 bg-green-100 text-green-800 font-semibold rounded-full shadow-sm hover:bg-green-300 hover:text-green-900 transition duration-300">
      <?= htmlspecialchars($kat['category']) ?>
    </button>
  <?php endforeach; ?>
</div>

<!-- Grid Produk -->
<div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
<?php while ($row = $result->fetch_assoc()): 
    $today = date('Y-m-d');
    $isOutOfStock = $row['qty'] == 0;
    $isExpired = !empty($row['expiry_date']) && $row['expiry_date'] < $today;

    // Tambah class khusus kalau habis / expired
    $boxClass = ($isOutOfStock || $isExpired) 
        ? "bg-red-100 border-2 border-red-400" 
        : "bg-white";
?>
    <div class="<?= $boxClass ?> rounded-xl shadow-md overflow-hidden hover:shadow-lg transition produk-item" 
         data-kategori="<?= $row['fid_category'] ?>">

        <!-- Label Stok Habis / Expired -->
        <?php if ($isOutOfStock): ?>
            <div class="bg-red-500 text-white text-xs px-2 py-1 text-center font-bold">Stok Habis</div>
        <?php elseif ($isExpired): ?>
            <div class="bg-yellow-500 text-white text-xs px-2 py-1 text-center font-bold">Expired</div>
        <?php endif; ?>

        <!-- Gambar Produk -->
        <img src="./assets/produk/<?= htmlspecialchars($row['image']) ?>"
             alt="<?= htmlspecialchars($row['product_name']) ?>"
             class="w-full max-h-44 object-contain bg-white p-2">

        <!-- Info Produk -->
        <div class="p-4 space-y-2">
            <h3 class="text-lg font-semibold text-gray-800 text-center"><?= htmlspecialchars($row['product_name']) ?></h3>
            <p class="text-center text-gray-700">Rp<?= number_format($row['selling_price'], 0, ',', '.') ?></p>
            <p class="text-center text-sm">
                <span class="inline-block px-2 py-1 rounded-full bg-blue-100 text-blue-700 font-semibold">
                    Stok: <?= htmlspecialchars($row['qty']) ?>
                </span>
            </p>

            <!-- Tombol Aksi -->
            <div class="flex justify-center gap-2 mt-4">
                <a href="edit_obat.php?id=<?= $row['id'] ?>"
                   class="px-3 py-1 bg-blue-500 hover:bg-blue-600 text-white text-sm rounded transition">✏️</a>

                <?php if ($isOutOfStock || $isExpired): ?>
                    <a href="product.php?hapus=<?= $row['id']; ?>"
                       onclick="return confirm('Yakin ingin menghapus produk ini?')"
                       class="px-3 py-1 bg-red-500 hover:bg-red-600 text-white text-sm rounded transition">🗑️</a>
                <?php else: ?>
                    <button class="px-3 py-1 bg-gray-400 text-white text-sm rounded cursor-not-allowed" disabled>🗑️</button>
                <?php endif; ?>

                <button onclick='showDetail(<?= json_encode($row) ?>)'
                        class="px-3 py-1 bg-yellow-500 hover:bg-yellow-600 text-white text-sm rounded transition">👁️</button>
            </div>
        </div>
    </div>
<?php endwhile; ?>
</div>

</main>

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
    
  </div>
</div>

<!-- Modal Detail Produk -->
<div id="produkModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
  <div class="bg-white rounded-lg w-[800px] max-h-[90vh] overflow-y-auto p-6 shadow-lg relative">
    <button onclick="closepModal()" class="absolute top-4 right-4 text-gray-600 hover:text-red-600 font-bold text-2xl">&times;</button>
    <div class="flex justify-center mb-6">
      <img id="modalImage" src="" class="w-24 h-24 rounded object-cover border mb-3" alt="Gambar Produk">
    </div>
    <table class="text-left w-full rounded-xl overflow-hidden mb-6">
      <tbody>
        <tr><th class="p-3 font-bold bg-gray-200 border-r w-1/3">Nama</th><td id="modalName" class="p-3 bg-gray-100"></td></tr>
        <tr><th class="p-3 font-bold bg-gray-200 border-r">Stok</th><td id="modalQty" class="p-3 bg-gray-100"></td></tr>
        <tr><th class="p-3 font-bold bg-gray-200 border-r">Modal</th><td id="modalModal" class="p-3 bg-gray-100"></td></tr>
        <tr><th class="p-3 font-bold bg-gray-200 border-r">Harga</th><td id="modalHarga" class="p-3 bg-gray-100"></td></tr>
        <tr><th class="p-3 font-bold bg-gray-200 border-r">Keuntungan</th><td id="modalUntung" class="p-3 bg-gray-100"></td></tr>
        <tr><th class="p-3 font-bold bg-gray-200 border-r">Kategori</th><td id="modalKategori" class="p-3 bg-gray-100"></td></tr>
        <tr><th class="p-3 font-bold bg-gray-200 border-r">Tanggal Kadaluwarsa</th><td id="modalExpired" class="p-3 bg-gray-100"></td></tr>
        <tr><th class="p-3 font-bold bg-gray-200 border-r">Deskripsi</th><td id="modalDeskripsi" class="p-3 bg-gray-100"></td></tr>
      </tbody>
    </table>
    <div class="flex justify-center mt-8">
      <svg id="modalBarcode"></svg>
    </div>
  </div>
</div>

<script>
  function filterProduk(kategoriId) {
  const semuaProduk = document.querySelectorAll('.produk-item');

  if (kategoriId === 'all') {
    semuaProduk.forEach(produk => {
      produk.style.display = 'block';
    });
  } else {
    semuaProduk.forEach(produk => {
      if (produk.getAttribute('data-kategori') === kategoriId) {
        produk.style.display = 'block';
      } else {
        produk.style.display = 'none';
      }
    });
  }
}

function showDetail(data) {  
  document.getElementById('modalImage').src = 'uploads/' + data.image;
  document.getElementById('modalName').textContent = data.product_name;
  document.getElementById('modalQty').textContent = data.qty;
  document.getElementById('modalModal').textContent = 'Rp ' + parseInt(data.starting_price).toLocaleString('id-ID');
  document.getElementById('modalHarga').textContent = 'Rp ' + parseInt(data.selling_price).toLocaleString('id-ID');
  document.getElementById('modalUntung').textContent = 'Rp ' + (parseInt(data.selling_price) - parseInt(data.starting_price)).toLocaleString('id-ID');
  document.getElementById('modalKategori').textContent = data.category || '-';
  document.getElementById('modalExpired').textContent = data.expiry_date || '-';
  document.getElementById('modalDeskripsi').textContent = data.description || '-';

  JsBarcode("#modalBarcode", data.barcode || '0000000000', {
    format: "CODE128",
    lineColor: "#000",
    width: 2,
    height: 80,
    displayValue: true
  });

  document.getElementById('produkModal').classList.remove('hidden');
  document.getElementById('produkModal').classList.add('flex');
}

function closepModal() {
  document.getElementById('produkModal').classList.add('hidden');
  document.getElementById('produkModal').classList.remove('flex');
}
</script>
</body>
</html>
