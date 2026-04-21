<?php
session_start();

// Cek apakah user sudah login
if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

$koneksi = new mysqli("localhost", "root", "", "kasir_apotik");

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

// Fungsi bantu buat render kartu
function renderCard($href, $iconClass, $title, $count) {
    return "
    <a href=\"$href\" class=\"bg-white border-2 border-green-500 rounded-xl shadow-md p-6 flex flex-col items-center justify-center cursor-pointer hover:bg-green-50 transition no-underline\">
      <div class=\"text-green-600 mb-4\">
        <i class=\"$iconClass fa-3x\"></i>
      </div>
      <h3 class=\"text-green-800 uppercase tracking-wide font-semibold mb-1\">$title</h3>
      <p class=\"text-3xl font-extrabold text-green-700\">$count</p>
    </a>
    ";
}

$jumlah_kasir = $koneksi->query("SELECT COUNT(*) as total FROM admin WHERE role = 'kasir'")->fetch_assoc()['total'];
$jumlah_member = $koneksi->query("SELECT COUNT(*) as total FROM member")->fetch_assoc()['total'];
$jumlah_kategori = $koneksi->query("SELECT COUNT(*) as total FROM category")->fetch_assoc()['total'];
$jumlah_produk = $koneksi->query("SELECT COUNT(*) as total FROM products")->fetch_assoc()['total'];
$penjualan_query = $koneksi->query("SELECT SUM(total_harga) as total FROM transactions");
$penjualan = $penjualan_query->fetch_assoc()['total'] ?? 0;

$penjualan_per_hari = [];
$result = $koneksi->query("SELECT DAY(tanggal_beli) as hari, SUM(total_harga) as total 
                          FROM transactions 
                          WHERE MONTH(tanggal_beli) = MONTH(CURDATE()) 
                          AND YEAR(tanggal_beli) = YEAR(CURDATE()) 
                          GROUP BY DAY(tanggal_beli)");

while ($row = $result->fetch_assoc()) {
    $penjualan_per_hari[intval($row['hari'])] = intval($row['total']);
}

$produk_counter = [];

$query = $koneksi->query("SELECT nama_produk FROM transactions WHERE nama_produk IS NOT NULL AND nama_produk != ''");

while ($row = $query->fetch_assoc()) {
    $produk_list = explode(',', $row['nama_produk']);

    foreach ($produk_list as $produk_raw) {
        // Ambil nama dan jumlah (misal: ' hijab instan (milo) (2x)')
        if (preg_match('/^(.*)\((\d+)x\)$/', trim($produk_raw), $match)) {
            $nama = trim($match[1]); // 'hijab instan (milo)'
            $jumlah = intval($match[2]); // 2
        } else {
            // Jika tidak sesuai pola, anggap 1
            $nama = trim(preg_replace('/\(\d+x\)$/', '', $produk_raw));
            $jumlah = 1;
        }

        // Tambahkan ke counter
        if (!isset($produk_counter[$nama])) {
            $produk_counter[$nama] = 0;
        }
        $produk_counter[$nama] += $jumlah;
    }
}

// Urutkan dari yang paling banyak
arsort($produk_counter);

// Ambil 5 teratas
$labels = array_slice(array_keys($produk_counter), 0, 5);
$data = array_slice(array_values($produk_counter), 0, 5);

?>

<html>
<head>
<title>Dashboard naabelle</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
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
    <a href="dashboard.php" class="flex items-center px-4 py-2 rounded-lg bg-[#68975f] font-semibold">
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
    <a href="product.php" class="flex items-center px-4 py-2 rounded-lg hover:bg-green-700 transition">
      <span class="ml-2">Products</span>
    </a>
    <a href="report.php" class="flex items-center px-4 py-2 rounded-lg hover:bg-green-700 transition">
      <span class="ml-2">Report</span>
    </a>
  <?php elseif ($role === 'kasir'): ?>
    <a href="dashboard.php" class="flex items-center px-4 py-2 rounded-lg bg-[#68975f] font-semibold">
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
  <h1 class="text-3xl font-bold text-gray-800">Dashboard</h1>
</div>


<div
  class="grid grid-cols-1 sm:grid-cols-2 mb-8 px-6
  <?= ($role === 'kasir') ? 'lg:grid-cols-2 gap-10' : 'lg:grid-cols-4 gap-6' ?>
  "
>
  <?= renderCard("kasir.php", "fas fa-user-tie", "Kasir", $jumlah_kasir) ?>
  <?= renderCard("member.php", "fas fa-users", "Member", $jumlah_member) ?>

  <?php if ($role === 'superadmin'): ?>
    <?= renderCard("category.php", "fas fa-th-large", "Kategori", $jumlah_kategori) ?>
    <?= renderCard("product.php", "fas fa-capsules", "Produk", $jumlah_produk) ?>
  <?php endif; ?>
</div>

                <div class="grid grid-cols-2 gap-6">
                    <div class="p-6 bg-white rounded-lg shadow-lg">
                        <h2 class="text-center font-bold text-lg mb-4">Data Penjualan</h2>
                        <canvas id="lineChart"></canvas>
                    </div>
                    <div class="p-6 bg-white rounded-lg shadow-lg">
                        <canvas id="pieChart"></canvas>
                        </div>
                </div>
            </div>
        </div>
        <main>
    </div>
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
      <p><strong>Alamat:</strong> <?= $akun['alamat']; ?></p>
        <!-- Tombol Edit -->
  <a href="edit_kasir.php?id=<?= $akun['id']; ?>"
     class="mt-4 inline-block px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg shadow transition">
    ✏️ Edit Profil
  </a>
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

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        function toggleProfile() {
  const modal = document.getElementById('profileModal');
  modal.classList.toggle('hidden');
}

    const lineCtx = document.getElementById('lineChart').getContext('2d');
    new Chart(lineCtx, {
        type: 'line',
        data: {
            labels: [<?php for ($i=1; $i<=date('t'); $i++) echo "'$i',"; ?>],
            datasets: [{
                label: 'Penjualan Harian',
                data: [
                    <?php
                        for ($i=1; $i<=date('t'); $i++) {
                            echo isset($penjualan_per_hari[$i]) ? $penjualan_per_hari[$i] . "," : "0,";
                        }
                    ?>
                ],
                borderColor: '#339b30ff',
                borderWidth: 2,
                fill: false
            }]
        }
    });

    const pieCtx = document.getElementById('pieChart').getContext('2d');
    new Chart(pieCtx, {
        type: 'pie',
        data: {
            labels: <?= json_encode($labels) ?>,
            datasets: [{
                data: <?= json_encode($data) ?>,
                backgroundColor: ['#339b30ff', '#1ed22dff', '#2da03cff', '#3fcd6eff', '#87de9dff']
            }]
        }
    });
</script>

</body>
</html>
