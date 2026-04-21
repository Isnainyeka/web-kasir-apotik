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
    header("Location: login.php");
    exit();
}

// Cek role valid atau tidak
if (!isset($_SESSION['role'])) {
    echo "<script>alert('Session role tidak ditemukan!'); window.location='login.php';</script>";
    exit();
}
$role = $_SESSION['role']; // Buat variabel lokal biar lebih aman

// Proses hapus kasir jika ada parameter ?hapus=
if (isset($_GET['hapus']) && is_numeric($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];

    // Jika role kasir, tidak bisa hapus akun manapun
    if ($_SESSION['role'] === 'kasir') {
        echo "<script>alert('Anda tidak memiliki izin untuk menghapus akun!'); window.location='kasir.php';</script>";
        exit();
    }

    // Admin tidak bisa menghapus dirinya sendiri
    if ($id == $id_user) {
        echo "<script>alert('Anda tidak bisa menghapus akun Anda sendiri!'); window.location='kasir.php';</script>";
        exit();
    }

    $cek = $koneksi->query("SELECT * FROM admin WHERE id = $id AND role = 'Kasir'");
    if ($cek->num_rows == 0) {
        echo "<script>alert('Data kasir tidak ditemukan!'); window.location='kasir.php';</script>";
        exit();
    }

    $hapus = $koneksi->query("DELETE FROM admin WHERE id = $id");
    if ($hapus) {
        echo "<script>alert('Kasir berhasil dihapus!'); window.location='kasir.php';</script>";
    } else {
        echo "<script>alert('Gagal menghapus kasir!'); window.location='kasir.php';</script>";
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Kasir Apotek</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />
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
    <a href="kasir.php" class="flex items-center px-4 py-2 rounded-lg bg-[#68975f] font-semibold">
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
    <a href="dashboard.php" class="flex items-center px-4 py-2 rounded-lg hover:bg-green-700 transition">
      <span class="ml-2">Dashboard</span>
    </a>
        <a href="kasir.php" class="flex items-center px-4 py-2 rounded-lg bg-[#68975f] font-semibold">
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
  <h1 class="text-3xl font-bold text-gray-800">List Kasir</h1>
  <?php if ($role === 'superadmin'): ?>
    <a href="tambah_kasir.php"
       class="inline-block px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg shadow transition">
      ➕ Tambah Kasir
    </a>
  <?php endif; ?>
</div>

  <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
    <?php
    $result = $koneksi->query("SELECT * FROM admin WHERE role = 'Kasir'");
    while ($row = $result->fetch_assoc()):
    ?>
      <div onclick="showDetailModal(event, <?= htmlspecialchars(json_encode($row)) ?>)"
     class="bg-white rounded-xl shadow-lg border border-gray-200 p-6 flex flex-col items-center text-center hover:shadow-xl transition cursor-pointer">
     <img class="w-24 h-24 rounded-full object-cover border-4 border-green-300 shadow"
     src="<?php echo !empty($row['image']) ? 'uploads/' . $row['image'] : 'uploads/default.png'; ?>"
     alt="Foto <?php echo $row['username']; ?>">

        <h2 class="text-xl font-semibold mt-4 text-gray-800"><?php echo htmlspecialchars($row['username']); ?></h2>
        <p class="text-gray-500 text-sm"><?php echo htmlspecialchars($row['email']); ?></p>

        <!-- Tombol Aksi -->
        <div class="flex gap-2 mt-4">
<?php
// Tombol edit
if ($role === 'superadmin') {
    // superadmin bisa edit semua
    echo '<a href="edit_kasir.php?id=' . $row['id'] . '" class="bg-blue-500 p-2 rounded-full w-10 h-10 flex items-center justify-center hover:bg-blue-600 transition" onclick="event.stopPropagation()"><i class="fas fa-pencil-alt"></i></a>';
} elseif ($role === 'kasir') {
    if ($_SESSION['id'] == $row['id']) {
        // kasir bisa edit akun sendiri
        echo '<a href="edit_kasir.php?id=' . $row['id'] . '" class="bg-blue-500 p-2 rounded-full w-10 h-10 flex items-center justify-center hover:bg-blue-600 transition" onclick="event.stopPropagation()"><i class="fas fa-pencil-alt"></i></a>';
    } else {
        // akun lain tombol edit disabled
        echo '<button class="bg-gray-400 cursor-not-allowed p-2 rounded-full w-10 h-10 flex items-center justify-center" disabled onclick="event.stopPropagation()"><i class="fas fa-pencil-alt"></i></button>';
    }
}

// Tombol hapus
if ($role === 'superadmin') {
    if ($row['id'] == $_SESSION['id']) {
        // Tidak bisa hapus diri sendiri
        echo '<button class="bg-gray-400 cursor-not-allowed p-2 rounded-full w-10 h-10 flex items-center justify-center" disabled><i class="fas fa-trash"></i></button>';
    } else {
        if ($row['status'] == 'Tidak Aktif') {
            // Bisa hapus kasir lain yang tidak aktif
            echo '<a href="kasir.php?hapus=' . $row['id'] . '" onclick="event.stopPropagation(); return confirm(\'Yakin ingin menghapus kasir ini?\')" class="bg-red-500 p-2 rounded-full w-10 h-10 flex items-center justify-center hover:bg-red-600 transition"><i class="fas fa-trash"></i></a>';
        } else {
            // Kasir lain yang aktif tombol hapus disabled
            echo '<button class="bg-gray-400 cursor-not-allowed p-2 rounded-full w-10 h-10 flex items-center justify-center" disabled onclick="event.stopPropagation()"><i class="fas fa-trash"></i></button>';
        }
    }
} elseif ($role === 'kasir') {
    // Kasir tidak boleh hapus siapa pun, tombol hapus semua disabled
    echo '<button class="bg-gray-400 cursor-not-allowed p-2 rounded-full w-10 h-10 flex items-center justify-center" disabled onclick="event.stopPropagation()"><i class="fas fa-trash"></i></button>';
}
?>

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

function showDetailModal(event, data) {
  event.stopPropagation(); // Biar tidak memicu klik parent lainnya

  // Contoh isi modal (kamu bisa sesuaikan tampilannya)
  const modalContent = `
    <div class="bg-white rounded-xl shadow-lg w-full max-w-md p-6 relative">
      <button onclick="closeDetailModal()" class="absolute top-2 right-3 text-gray-600 hover:text-red-500 text-xl">&times;</button>
      <h2 class="text-2xl font-semibold text-green-700 mb-4">Detail Kasir</h2>
      <img src="${data.image ? 'uploads/' + data.image : 'uploads/default.png'}" 
           class="w-24 h-24 rounded-full mx-auto mb-4 border-4 border-green-300 object-cover" 
           alt="Foto Profil">
      <div class="space-y-3 text-sm text-gray-700">
        <p><strong>Username:</strong> ${data.username}</p>
        <p><strong>Email:</strong> ${data.email}</p>
        <p><strong>No. Telepon:</strong> ${data.telepon}</p>
        <p><strong>Role:</strong> ${data.role}</p>
        <p><strong>Status:</strong> ${data.status}</p>
        <p><strong>Jenis Kelamin:</strong> ${data.gender}</p>
        <p><strong>Alamat:</strong> ${data.alamat}</p>
      </div>
    </div>
  `;

  // Buat elemen modal
  const modalWrapper = document.createElement("div");
  modalWrapper.id = "detailModal";
  modalWrapper.className = "fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50";
  modalWrapper.innerHTML = modalContent;

  document.body.appendChild(modalWrapper);
}

function closeDetailModal() {
  const modal = document.getElementById("detailModal");
  if (modal) modal.remove();
}
</script>
  </div>
</div>

</body>
</html>