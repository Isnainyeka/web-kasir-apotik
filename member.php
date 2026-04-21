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

// Nonaktifkan member yang tidak transaksi dalam 1 menit
$limit = date('Y-m-d H:i:s', strtotime('-1 minute'));
$conn->query("UPDATE member SET status='non-active' WHERE last_transaction < '$limit' AND status='active'");

// Cek apakah ini request AJAX untuk refresh status tanpa reload
if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
    $sql_ajax = "SELECT id, status FROM member";
    $result_ajax = $conn->query($sql_ajax);
    $members_status = [];
    while ($row_ajax = $result_ajax->fetch_assoc()) {
        $members_status[] = $row_ajax;
    }
    header('Content-Type: application/json');
    echo json_encode($members_status);
    exit;
}

// Ambil data member untuk ditampilkan di halaman utama
$sql = "SELECT * FROM member";
$result = $conn->query($sql);

$id = $_SESSION['id'];
$admin = $conn->query("SELECT * FROM admin WHERE id = $id")->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Members</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet" />
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
    <a href="member.php" class="flex items-center px-4 py-2 rounded-lg bg-[#68975f] font-semibold">
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
        <a href="kasir.php" class="flex items-center px-4 py-2 rounded-lg hover:bg-green-700 transition">
      <span class="ml-2">Kasir</span>
    </a>
    <a href="member.php" class="flex items-center px-4 py-2 rounded-lg bg-[#68975f] font-semibold">
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
  
<!-- Content -->
<main class="flex-1 p-10 overflow-y-auto bg-[#F9F6F1]">
    <!-- Judul + Search sejajar -->
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-3xl font-bold text-green-800 tracking-wide">List Member</h2>
        <input type="text" id="searchInput" 
            placeholder="🔍 Cari member..." 
            class="border border-green-300 rounded-lg px-4 py-2 w-64 focus:outline-none focus:ring-2 focus:ring-green-500 shadow-sm">
    </div>

    <div class="bg-white p-6 rounded-xl shadow-lg border border-green-200 overflow-x-auto">
        <table class="w-full text-left border-collapse" id="memberTable">
            <thead class="bg-green-100 text-green-800 uppercase text-sm font-semibold">
                <tr>
                    <th class="p-3">ID</th>
                    <th class="p-3">Name</th>
                    <th class="p-3">Email</th>
                    <th class="p-3">Phone</th>
                    <th class="p-3">Transaction Amount</th>
                    <th class="p-3">Point</th>
                    <th class="p-3">Status</th>
                    <th class="p-3">Action</th>
                </tr>
            </thead>
            <tbody class="text-gray-700">
                <?php
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr class='border-b hover:bg-green-50 transition'>";
                        echo "<td class='p-3 font-medium'>" . $row['id'] . "</td>";
                        echo "<td class='p-3'>" . htmlspecialchars($row['name']) . "</td>";
                        echo "<td class='p-3'>" . htmlspecialchars($row['email']) . "</td>";
                        echo "<td class='p-3'>" . htmlspecialchars($row['phone']) . "</td>";
                        echo "<td class='p-3'>" . number_format($row['transaction_amount'], 0, ',', '.') . "</td>";
                        echo "<td class='p-3'>" . $row['point'] . "</td>";

                        echo "<td class='p-3' id='status-" . $row['id'] . "'>";
                        if ($row['status'] == 'active') {
                            echo "<span class='px-3 py-1 rounded-full bg-green-500 text-white text-xs font-semibold shadow'>Active</span>";
                        } else {
                            echo "<span class='px-3 py-1 rounded-full bg-red-500 text-white text-xs font-semibold shadow'>Non-Active</span>";
                        }
                        echo "</td>";

                        echo "<td class='p-3 flex space-x-2'>    
    <button onclick=\"window.location.href='edit_member.php?id=" . $row['id'] . "'\" 
        class='bg-blue-500 hover:bg-blue-600 text-white p-2 rounded-full w-9 h-9 flex items-center justify-center shadow transition'>
        <i class='fas fa-pencil-alt text-xs'></i>
    </button>";

                        if (strtolower($row['status']) == 'non-active') {
                            echo "<button onclick=\"delete_member(" . $row['id'] . ")\" 
        class='bg-red-500 hover:bg-red-600 text-white p-2 rounded-full w-9 h-9 flex items-center justify-center shadow transition'>
        <i class='fas fa-trash text-xs'></i>
    </button>";
                        } else {
                            echo "<button class='bg-gray-300 text-white p-2 rounded-full w-9 h-9 flex items-center justify-center cursor-not-allowed opacity-50'>
        <i class='fas fa-trash text-xs'></i>
    </button>";
                        }

                        echo "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='8' class='text-center p-3 text-gray-500'>Tidak ada data member</td></tr>";
                }
                ?>
            </tbody>
        </table>

        <!-- Tombol Tambah Member -->
        <div class="flex justify-center mt-8">
    <button onclick="window.location.href='tambah_member.php'" 
        class="bg-green-600 hover:bg-green-700 text-white py-3 px-8 rounded-full shadow-md font-semibold transition">
        ➕ Add Member
    </button>
</div>
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

<script>
      function toggleModal() {
    const modal = document.getElementById("modalAkun");
    modal.classList.toggle("hidden");
    modal.classList.toggle("flex");
  }
  
    function toggleProfile() {
  const modal = document.getElementById('profileModal');
  modal.classList.toggle('hidden');
}

document.getElementById("searchInput").addEventListener("keyup", function() {
    let filter = this.value.toLowerCase();
    let rows = document.querySelectorAll("#memberTable tbody tr");

    rows.forEach(row => {
        let text = row.innerText.toLowerCase();
        if (text.includes(filter)) {
            row.style.display = "";
        } else {
            row.style.display = "none";
        }
    });
});

    function delete_member(id) {
        if (confirm("Yakin ingin menghapus member ini?")) {
            fetch('delete_member.php?id=' + id, {
                method: 'GET'
            })
            .then(response => response.text())
            .then(data => {
                alert(data); // Menampilkan pesan sukses atau error
                location.reload(); // Refresh halaman setelah menghapus
            })
            .catch(error => console.error('Error:', error));
        }
    }

    // Fungsi untuk refresh status member tanpa reload halaman
    function refreshMemberStatus() {
        fetch('member.php?ajax=1')
        .then(response => response.json())
        .then(data => {
            data.forEach(member => {
                const statusCell = document.getElementById('status-' + member.id);
                if (statusCell) {
                    // Ubah isi status dan warna badge sesuai status terbaru
                    if (member.status === 'active') {
                        statusCell.innerHTML = "<span class='px-3 py-1 rounded-full bg-green-500 text-white'>active</span>";
                    } else {
                        statusCell.innerHTML = "<span class='px-3 py-1 rounded-full bg-red-500 text-white'>non-Active</span>";
                    }
                }
            });
        })
        .catch(error => console.error('Error refreshing member status:', error));
    }

    // Refresh status setiap 10 detik (sesuaikan sesuai kebutuhan)
    setInterval(refreshMemberStatus, 10000);
</script>
</body>
</html>