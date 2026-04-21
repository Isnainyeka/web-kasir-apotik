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

$notification = '';
$notification_type = '';
$should_clean_url = false; // Flag untuk membersihkan URL
$from_keranjang = isset($_GET['from']) && $_GET['from'] === 'keranjang'; // Cek apakah dari keranjang

// Handle barcode scan dari URL parameter
if (isset($_GET['barcode'])) {
    $barcode = $_GET['barcode'];
    // Hanya set flag clean URL jika dari keranjang
    if ($from_keranjang) {
        $should_clean_url = true;
    }
    
    // Cari produk berdasarkan barcode
    $stmt = $conn->prepare("SELECT id, product_name, selling_price, qty, image FROM products WHERE barcode = ?");
    $stmt->bind_param("s", $barcode);
    $stmt->execute();
    $result_scan = $stmt->get_result();
    
    if ($result_scan->num_rows > 0) {
        $product = $result_scan->fetch_assoc();
        
        // Cek stok
        if ($product['qty'] > 0) {
            // Inisialisasi keranjang jika belum ada
            if (!isset($_SESSION['cart'])) {
                $_SESSION['cart'] = array();
            }
            
            $productId = $product['id'];
            
            // Cek apakah produk sudah ada di keranjang (menggunakan id sebagai key seperti di keranjang.php)
            if (isset($_SESSION['cart'][$productId])) {
                // Jika sudah ada, tambah quantity
                if ($_SESSION['cart'][$productId]['qty'] < $product['qty']) {
                    $_SESSION['cart'][$productId]['qty'] += 1;
                    $notification = "Jumlah produk '{$product['product_name']}' ditambah!";
                    $notification_type = 'success';
                } else {
                    $notification = "Stok produk '{$product['product_name']}' tidak mencukupi!";
                    $notification_type = 'error';
                }
            } else {
                // Jika belum ada, tambahkan produk baru dengan struktur yang sama seperti keranjang.php
                $_SESSION['cart'][$productId] = array(
                    'id' => $product['id'],
                    'name' => $product['product_name'],
                    'price' => $product['selling_price'],
                    'image' => $product['image'],
                    'qty' => 1,
                    'expiry_time' => time() + 3600 // 1 jam kedaluwarsa
                );
                $notification = "Produk '{$product['product_name']}' berhasil ditambahkan ke keranjang!";
                $notification_type = 'success';
            }
        } else {
            $notification = "Produk '{$product['product_name']}' stok habis!";
            $notification_type = 'error';
        }
    } else {
        $notification = "Produk dengan barcode '$barcode' tidak ditemukan!";
        $notification_type = 'error';
    }
    
    $stmt->close();
       // Tambahan cek AJAX
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    $response = [
        'notification' => $notification,
        'notification_type' => $notification_type,
    ];

    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    } else {
        $_SESSION['notif'] = $notification;
        $_SESSION['notif_type'] = $notification_type;
        header("Location: cart.php");
        exit;
    }
}

// Handle penambahan produk manual ke keranjang
if (isset($_GET['id'])) {
    $product_id = $_GET['id'];
    // Hanya set flag clean URL jika dari keranjang
    if ($from_keranjang) {
        $should_clean_url = true;
    }
    
    // Ambil data produk
    $stmt = $conn->prepare("SELECT id, product_name, selling_price, qty, image FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result_manual = $stmt->get_result();
    
    if ($result_manual->num_rows > 0) {
        $product = $result_manual->fetch_assoc();
        
        if ($product['qty'] > 0) {
            // Inisialisasi keranjang jika belum ada
            if (!isset($_SESSION['cart'])) {
                $_SESSION['cart'] = array();
            }
            
            $productId = $product['id'];
            
            // Cek apakah produk sudah ada di keranjang (menggunakan id sebagai key seperti di keranjang.php)
            if (isset($_SESSION['cart'][$productId])) {
                // Jika sudah ada, tambah quantity
                if ($_SESSION['cart'][$productId]['qty'] < $product['qty']) {
                    $_SESSION['cart'][$productId]['qty'] += 1;
                    $notification = "Jumlah produk '{$product['product_name']}' ditambah!";
                    $notification_type = 'success';
                } else {
                    $notification = "Stok produk '{$product['product_name']}' tidak mencukupi!";
                    $notification_type = 'error';
                }
            } else {
                // Jika belum ada, tambahkan produk baru dengan struktur yang sama seperti keranjang.php
                $_SESSION['cart'][$productId] = array(
                    'id' => $product['id'],
                    'name' => $product['product_name'],
                    'price' => $product['selling_price'],
                    'image' => $product['image'],
                    'qty' => 1,
                    'expiry_time' => time() + 3600 // 1 jam kedaluwarsa
                );
                $notification = "Produk '{$product['product_name']}' berhasil ditambahkan ke keranjang!";
                $notification_type = 'success';
            }
        } else {
            $notification = "Produk '{$product['product_name']}' stok habis!";
            $notification_type = 'error';
        }
    }
    $stmt->close();
}

// Ambil data produk dari database
$sql = "SELECT id, product_name, selling_price, qty, image, fid_category FROM products";
$result = $conn->query($sql);

// Hitung total jumlah produk dalam keranjang
$total_qty = 0;
if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $total_qty += isset($item['qty']) ? $item['qty'] : 0;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- QuaggaJS untuk camera scanner -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/quagga/0.12.1/quagga.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet"/>
    <style>
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 8px;
            color: white;
            font-weight: bold;
            z-index: 1000;
            animation: slideIn 0.3s ease-out;
        }
        
        .notification.success {
            background-color: #10b981;
        }
        
        .notification.error {
            background-color: #ef4444;
        }
        
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }
        
        .scanner-overlay { /* Style untuk scanner overlay */
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 250px;
            height: 150px;
            border: 3px solid #ff6b6b;
            border-radius: 10px;
            z-index: 10;
            pointer-events: none;
            animation: scannerPulse 2s infinite;
        }
        
        @keyframes scannerPulse {
            0%, 100% { opacity: 0.7; }
            50% { opacity: 1; }
        }
        
        .scanner-corners {
            position: absolute;
            width: 30px;
            height: 30px;
            border: 4px solid #ff6b6b;
        }
        
        .corner-tl { top: -2px; left: -2px; border-right: none; border-bottom: none; }
        .corner-tr { top: -2px; right: -2px; border-left: none; border-bottom: none; }
        .corner-bl { bottom: -2px; left: -2px; border-right: none; border-top: none; }
        .corner-br { bottom: -2px; right: -2px; border-left: none; border-top: none; }
        
        .scan-progress { /* Progress bar untuk scan */
            position: absolute;
            bottom: 10px;
            left: 50%;
            transform: translateX(-50%);
            width: 200px;
            height: 4px;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 2px;
            overflow: hidden;
        }
        
        .scan-progress-bar {
            height: 100%;
            background: #4CAF50;
            width: 0%;
            transition: width 0.1s ease;
        }
        
        .scan-result { /* Hasil scan */
            position: absolute;
            top: 10px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            font-size: 14px;
            display: none;
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-800">
<div class="flex min-h-screen">
    <!-- Notifikasi -->
    <?php if ($notification): ?>
        <div id="notification" class="notification <?= $notification_type ?>">
            <i class="fas <?= $notification_type == 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle' ?> mr-2"></i>
            <?= htmlspecialchars($notification) ?>
        </div>
    <?php endif; ?>

<div class="flex flex-1">
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
        <a href="transaksi.php" class="flex items-center px-4 py-2 rounded-lg bg-[#68975f] font-semibold">
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

<!-- Konten Utama -->
<main class="flex-1 p-10 overflow-y-auto bg-[#F9F6F1]">
  <!-- Header dan Barcode Input -->
  <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
    <h1 class="text-3xl font-bold text-gray-800">Data Produk</h1>

    <!-- Barcode Input -->
    <div class="flex items-center gap-3">
      <!-- Input -->
      <div class="flex bg-white border border-gray-300 rounded-lg shadow overflow-hidden">
        <input 
          type="text" 
          id="barcodeInput" 
          placeholder="Input barcode..."
          class="px-3 py-2 text-sm w-44 focus:outline-none"
        />
        <button onclick="tambahKeKeranjangManual()" class="bg-green-500 hover:bg-green-600 text-white px-3 py-2 text-sm">
          <i class="fas fa-plus"></i>
        </button>
      </div>

      <!-- Scan -->
      <button onclick="openScanner()" class="bg-green-500 w-9 h-9 flex items-center justify-center text-white rounded-full shadow hover:scale-110 transition">
        <i class="fas fa-camera"></i>
      </button>

      <!-- Cart -->
      <button onclick="goToKeranjang()" class="relative bg-green-500 w-9 h-9 flex items-center justify-center text-white rounded-full shadow hover:scale-110 transition">
        <i class="fas fa-shopping-cart"></i>
        <?php if ($total_qty > 0): ?>
          <span class="absolute -top-2 -right-2 text-[10px] font-bold bg-red-600 px-1.5 py-0.5 rounded-full">
            <?= $total_qty ?>
          </span>
        <?php endif; ?>
      </button>
    </div>
  </div>

  <!-- Produk Grid -->
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
        
        <img src="./assets/produk/<?= htmlspecialchars($row['image']) ?>" alt="<?= htmlspecialchars($row['product_name']) ?>"
             class="w-full h-44 object-cover rounded-t-xl">
        <div class="p-4 space-y-2">
          <h3 class="text-lg font-semibold text-gray-800 text-center"><?= htmlspecialchars($row['product_name']) ?></h3>
          <p class="text-center text-gray-700">Rp<?= number_format($row['selling_price'], 0, ',', '.') ?></p>
          <p class="text-center text-sm">
            <span class="inline-block px-2 py-1 rounded-full bg-blue-100 text-blue-700 font-semibold">
              Stok: <?= htmlspecialchars($row['qty']) ?>
            </span>
          </p>
          <div class="flex justify-center pt-2">
            <button onclick="addToCart(<?= $row['id'] ?>)" class="px-3 py-1 bg-green-500 hover:bg-green-600 text-black text-sm rounded transition"> <i class="fas fa-shopping-cart"></i></button>
          </div>
        </div>
      </div>
    <?php endwhile; ?>
  </div>
</main>

    <!-- Modal Scanner -->
    <div id="scannerModal" class="hidden fixed inset-0 z-50 bg-black bg-opacity-70 flex items-center justify-center">
        <div class="bg-white p-6 rounded-xl w-[90%] max-w-lg relative shadow-2xl">
            <h2 class="text-xl font-bold mb-4 text-center text-gray-800">Scan Barcode Produk</h2>
            <div id="scanner" class="w-full h-80 bg-gray-200 rounded-lg border-2 border-gray-300 relative overflow-hidden">
                <!-- Scanner Overlay -->
                <div class="scanner-overlay">
                    <div class="scanner-corners corner-tl"></div>
                    <div class="scanner-corners corner-tr"></div>
                    <div class="scanner-corners corner-bl"></div>
                    <div class="scanner-corners corner-br"></div>
                </div>
                <!-- Hasil scan -->
                <div id="scanResult" class="scan-result"></div>
                <!-- Progress bar -->
                <div class="scan-progress">
                    <div id="scanProgressBar" class="scan-progress-bar"></div>
                </div>
            </div>
            <button onclick="closeScanner()" 
                class="absolute top-3 right-3 text-red-500 hover:text-red-700 text-2xl z-10 bg-white rounded-full w-8 h-8 flex items-center justify-center shadow-md hover:shadow-lg transition-all" 
                aria-label="Close Scanner">
                <i class="fas fa-times"></i>
            </button>
            <p class="text-sm text-gray-600 text-center mt-3">Arahkan kamera ke barcode produk dan tunggu hingga fokus</p>
        </div>
    </div>

    <!-- Hidden input untuk barcode scanner hardware -->
    <input id="barcode-input" type="text" class="hidden" autofocus>

<script>
    function toggleProfile() {
  const modal = document.getElementById('profileModal');
  modal.classList.toggle('hidden');
}

// Fungsi untuk menampilkan notifikasi yang sudah diperbaiki
function showNotifikasi(message, isSuccess) {
    // Hapus notifikasi yang sudah ada
    const existingNotification = document.getElementById('notification');
    if (existingNotification) {
        existingNotification.remove();
    }

    // Buat elemen notifikasi baru
    const notification = document.createElement('div');
    notification.id = 'notification';
    notification.className = `notification ${isSuccess ? 'success' : 'error'}`;
    
    // Icon berdasarkan status
    const icon = isSuccess ? 'fa-check-circle' : 'fa-exclamation-triangle';
    
    notification.innerHTML = `
        <i class="fas ${icon} mr-2"></i>
        ${message}
    `;
    
    // Tambahkan ke body
    document.body.appendChild(notification);
    
    // Auto hide setelah 5 detik
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease-in';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 300);
    }, 5000);
}

// Input manual barcode
function tambahKeKeranjangManual() {
    const barcode = document.getElementById("barcodeInput").value;

    if (!barcode.trim()) {
        showNotifikasi("Masukkan barcode terlebih dahulu!", false);
        return;
    }

    fetch("cart.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body: "barcode=" + encodeURIComponent(barcode)
    })
    .then(response => response.json())
    .then(data => {
        showNotifikasi(data.message, data.success);

        // Kalau produk ditemukan, kosongkan input dan reload halaman
        if (data.success) {
            document.getElementById("barcodeInput").value = "";
            setTimeout(() => {
                location.reload(); 
            }, 1000);
        }
    })
    .catch(error => {
        showNotifikasi("Gagal menambahkan produk", false);
        console.error(error);
    });
}

let isScanning = false;
let scanAttempts = 0;
let lastScannedCode = '';
let scanConfidenceThreshold = 3; // Minimum deteksi yang sama untuk konfirmasi
let currentFacingMode = 'environment';
let torchEnabled = false;

// Fungsi untuk membersihkan URL
function cleanURL() {
    const cleanUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
    window.history.replaceState({}, document.title, cleanUrl);
}

// Fungsi untuk ke halaman keranjang dengan menyimpan URL saat ini
function goToKeranjang() {
    sessionStorage.setItem('transaksi_url', window.location.href);
    window.location.href = 'cart.php';
}

// Auto hide notification setelah 5 detik dan bersihkan URL jika perlu
document.addEventListener('DOMContentLoaded', function() {
    const notification = document.getElementById('notification');
    
    // Bersihkan URL jika ada parameter barcode atau id
    <?php if ($should_clean_url): ?>
    setTimeout(() => {
        cleanURL();
    }, 100);
    <?php endif; ?>
    
    if (notification) {
        setTimeout(() => {
            notification.style.animation = 'slideOut 0.3s ease-in';
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, 5000);
    }
});

// Fungsi untuk membuka camera scanner
function openScanner() {
    const modal = document.getElementById('scannerModal');
    modal.classList.remove('hidden');
    modal.style.display = "flex";
    isScanning = true;
    scanAttempts = 0;
    lastScannedCode = '';
    startQuagga();
}

// Fungsi untuk menutup camera scanner
function closeScanner() {
    const modal = document.getElementById('scannerModal');
    modal.classList.add('hidden');
    modal.style.display = "none";
    isScanning = false;
    
    // Stop Quagga dengan pengecekan yang lebih aman
    if (typeof Quagga !== 'undefined' && Quagga.CameraAccess) {
        try {
            Quagga.stop();
            console.log("Quagga stopped successfully");
        } catch (error) {
            console.error("Error stopping Quagga:", error);
        }
    }
    
    // Clear scanner container
    const scannerDiv = document.getElementById('scanner');
    if (scannerDiv) {
        const videoElements = scannerDiv.querySelectorAll('video');
        videoElements.forEach(video => {
            video.srcObject = null;
        });
        // Reset hanya elemen yang bukan overlay
        const overlay = scannerDiv.querySelector('.scanner-overlay');
        const result = scannerDiv.querySelector('.scan-result');
        const progress = scannerDiv.querySelector('.scan-progress');
        scannerDiv.innerHTML = '';
        if (overlay) scannerDiv.appendChild(overlay);
        if (result) scannerDiv.appendChild(result);
        if (progress) scannerDiv.appendChild(progress);
    }
    
    // Reset progress bar
    const progressBar = document.getElementById('scanProgressBar');
    if (progressBar) {
        progressBar.style.width = '0%';
    }
}

// Fungsi untuk memulai Quagga camera scanner dengan setting yang lebih akurat
function startQuagga() {
    console.log("Inisialisasi Quagga...");
    Quagga.init({
        inputStream: {
            name: "Live",
            type: "LiveStream",
            target: document.querySelector('#scanner'),
            constraints: {
                facingMode: currentFacingMode,
                width: { min: 640 },
                height: { min: 480 }
            }
        },
        locator: {
            patchSize: "medium",
            halfSample: true
        },
        numOfWorkers: 2,
        frequency: 10, // Kurangi frekuensi scanning
        decoder: {
            readers: [
                "ean_reader", 
                "ean_8_reader", 
                "code_128_reader",
                "code_39_reader",
                "codabar_reader",
                "i2of5_reader"
            ]
        },
        locate: true
    }, function(err) {
        if (err) {
            console.error("ERROR Quagga init:", err);
            alert("Gagal mengakses kamera. Pastikan izinkan akses kamera.");
            closeScanner();
            return;
        }
        console.log("Quagga berhasil di-start");
        Quagga.start();
    });

    // Counter untuk tracking deteksi yang sama
    let codeDetectionCount = {};
    let detectionHistory = [];

    Quagga.onProcessed(function(result) {
        if (!isScanning) return;
        
        var drawingCtx = Quagga.canvas.ctx.overlay,
            drawingCanvas = Quagga.canvas.dom.overlay;

        if (result) {
            // Clear canvas
            if (drawingCtx) {
                drawingCtx.clearRect(0, 0, parseInt(drawingCanvas.getAttribute("width")), parseInt(drawingCanvas.getAttribute("height")));
            }
            
            // Draw bounding boxes
            if (result.boxes) {
                drawingCtx.strokeStyle = "green";
                drawingCtx.lineWidth = 2;
                result.boxes.filter(function (box) {
                    return box !== result.box;
                }).forEach(function (box) {
                    Quagga.ImageDebug.drawPath(box, {x: 0, y: 1}, drawingCtx, {color: "green", lineWidth: 2});
                });
            }

            // Draw main detection box
            if (result.box) {
                drawingCtx.strokeStyle = "#00F";
                drawingCtx.lineWidth = 2;
                Quagga.ImageDebug.drawPath(result.box, {x: 0, y: 1}, drawingCtx, {color: "#00F", lineWidth: 2});
            }

            // Draw barcode line
            if (result.codeResult && result.codeResult.code) {
                drawingCtx.strokeStyle = "red";
                drawingCtx.lineWidth = 3;
                Quagga.ImageDebug.drawPath(result.line, {x: 'x', y: 'y'}, drawingCtx, {color: 'red', lineWidth: 3});
            }
        }
    });

    Quagga.onDetected(function(data) {
        if (!isScanning) return;
        
        const kode = data.codeResult.code;
        const confidence = data.codeResult.decodedCodes[0].error || 0;
        
        console.log("Barcode detected:", kode, "Confidence:", confidence);
        
        // Filter berdasarkan confidence score
        if (confidence > 0.1) {
            console.log("Low confidence scan, ignoring");
            return;
        }
        
        // Tambahkan ke history deteksi
        detectionHistory.push(kode);
        
        // Hanya simpan 10 deteksi terakhir
        if (detectionHistory.length > 10) {
            detectionHistory.shift();
        }
        
        // Hitung frekuensi kode yang sama
        if (!codeDetectionCount[kode]) {
            codeDetectionCount[kode] = 0;
        }
        codeDetectionCount[kode]++;
        
        // Update progress bar
        const progressBar = document.getElementById('scanProgressBar');
        const scanResult = document.getElementById('scanResult');
        
        if (progressBar) {
            const progress = Math.min((codeDetectionCount[kode] / scanConfidenceThreshold) * 100, 100);
            progressBar.style.width = progress + '%';
        }
        
        if (scanResult) {
            scanResult.textContent = `Scanning: ${kode} (${codeDetectionCount[kode]}/${scanConfidenceThreshold})`;
            scanResult.style.display = 'block';
        }
        
        // Konfirmasi scan jika sudah mencapai threshold
        if (codeDetectionCount[kode] >= scanConfidenceThreshold) {
            console.log("Barcode confirmed:", kode);
            
            // Reset untuk mencegah multiple scan
            isScanning = false;
            codeDetectionCount = {};
            detectionHistory = [];
            
            // Update UI
            if (scanResult) {
                scanResult.textContent = `✓ Berhasil: ${kode}`;
                scanResult.style.background = 'rgba(76, 175, 80, 0.9)';
            }
            
            if (progressBar) {
                progressBar.style.width = '100%';
                progressBar.style.background = '#4CAF50';
            }
            
            // Delay sebelum redirect untuk memberikan feedback visual
            setTimeout(() => {
                closeScanner();
                window.location.href = "transaksi.php?barcode=" + encodeURIComponent(kode);
            }, 1000);
        }
    });
}

// Fungsi untuk menambah produk ke keranjang (tombol manual)
function addToCart(productId) {
    fetch('cart.php?id=' + productId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Produk berhasil ditambahkan ke keranjang!');
                location.reload(); // supaya badge ikut terupdate
            } else {
                alert('Gagal menambahkan produk ke keranjang.');
            }
        });
}

// Event listener untuk barcode scanner hardware
document.addEventListener('DOMContentLoaded', function () {
    const input = document.getElementById('barcode-input');
    input.focus();

    input.addEventListener('keypress', function (e) {
        if (e.key === 'Enter') {
            const barcode = input.value.trim();
            if (barcode) {
                window.location.href = 'transaksi.php?barcode=' + encodeURIComponent(barcode);
            }
            input.value = ''; // Clear input setelah scan
        }
    });

    // Pastikan input tetap focus untuk barcode scanner
    input.addEventListener('blur', function() {
        setTimeout(() => input.focus(), 100);
    });
    
    // Handle visibility change untuk pause/resume scanner
    document.addEventListener('visibilitychange', function() {
        if (document.hidden && isScanning) {
            // Pause scanner when tab is hidden
            if (typeof Quagga !== 'undefined') {
                try {
                    Quagga.pause();
                } catch (error) {
                    console.log("Could not pause scanner:", error);
                }
            }
        } else if (!document.hidden && isScanning) {
            // Resume scanner when tab is visible
            if (typeof Quagga !== 'undefined') {
                try {
                    Quagga.start();
                } catch (error) {
                    console.log("Could not resume scanner:", error);
                }
            }
        }
    });
});

// Fungsi untuk validasi barcode format
function isValidBarcode(code) {
    // Cek apakah tepat 5 digit angka
    if (/^\d{5}$/.test(code)) {
        return true;
    }
    return false;
}

    // Event listener untuk Enter key pada input barcode
    document.getElementById('barcodeInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            tambahKeKeranjangManual();
        }
    });
</script>
</body>
</html>