<?php
session_start();

// Inisialisasi keranjang jika belum ada
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Koneksi database
$conn = new mysqli("localhost", "root", "", "kasir_apotik");

function tambahProdukKeKeranjang($param, $type, $conn) {
    // $type = "i" untuk id (INT), atau "s" untuk barcode (VARCHAR)

    if ($type === "s") {
        // Jika string (barcode), bersihkan input dari karakter asing
        $param = trim(preg_replace('/[^A-Za-z0-9]/', '', $param));
    }

    // Query berdasarkan id atau barcode
    $column = $type === "i" ? "id" : "barcode";

    $stmt = $conn->prepare("SELECT id, product_name, selling_price, image, qty FROM products WHERE $column = ?");
    $stmt->bind_param($type, $param);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    header('Content-Type: application/json');

    if ($result) {
        $productId = $result['id'];

        if (!isset($_SESSION['cart'][$productId]) && count($_SESSION['cart']) >= 5) {
            echo json_encode(['success' => false, 'message' => 'Keranjang maksimal 5 produk berbeda']);
            exit;
        }

        if (isset($_SESSION['cart'][$productId])) {
            if ($_SESSION['cart'][$productId]['qty'] < $result['qty']) {
                $_SESSION['cart'][$productId]['qty'] += 1;
                echo json_encode(['success' => true, 'message' => 'Jumlah produk ditambah']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Stok tidak mencukupi']);
            }
        } else {
            if ($result['qty'] > 0) {
                $_SESSION['cart'][$productId] = [
                    'id' => $result['id'],
                    'name' => $result['product_name'],
                    'price' => $result['selling_price'],
                    'image' => $result['image'],
                    'qty' => 1,
                    'expiry_time' => time() + 3600 // 1 jam kedaluwarsa
                ];
                echo json_encode(['success' => true, 'message' => 'Produk ditambahkan']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Produk tidak ditemukan']);
            }
        }
    } else {
        echo "Produk tidak ditemukan";
    }
    exit;
}

// === PROSES UPDATE JUMLAH DARI TOMBOL + DAN - ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['id'])) {
    $id = $_POST['id'];
    if (isset($_SESSION['cart'][$id])) {
        if ($_POST['action'] === 'increase') {
            // Cek stok dulu
            $check = $conn->prepare("SELECT qty FROM products WHERE id = ?");
            $check->bind_param("i", $id);
            $check->execute();
            $res = $check->get_result()->fetch_assoc();

            if ($_SESSION['cart'][$id]['qty'] < $res['qty']) {
                $_SESSION['cart'][$id]['qty']++;
            }
        } elseif ($_POST['action'] === 'decrease' && $_SESSION['cart'][$id]['qty'] > 1) {
            $_SESSION['cart'][$id]['qty']--;
        }
    }
    echo json_encode(['success' => true]);
    exit;
}

// Menghapus produk dari keranjang
if (isset($_GET['remove']) && isset($_SESSION['cart'][$_GET['remove']])) {
    unset($_SESSION['cart'][$_GET['remove']]);
    echo json_encode(['success' => true]);
    exit;
}

// Hapus item dari keranjang jika produk sudah tidak ada di database
if (!empty($_SESSION['cart'])) {
    $ids = array_keys($_SESSION['cart']);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids));

    $stmt = $conn->prepare("SELECT id FROM products WHERE id IN ($placeholders)");
    $stmt->bind_param($types, ...$ids);
    $stmt->execute();
    $result = $stmt->get_result();

    $availableIds = [];
    while ($row = $result->fetch_assoc()) {
        $availableIds[] = $row['id'];
    }

    foreach ($_SESSION['cart'] as $key => $item) {
        if (!in_array($key, $availableIds)) {
            unset($_SESSION['cart'][$key]);
        }
    }
}

// PERBAIKAN: Pastikan semua item memiliki expiry_time dan bersihkan yang kedaluwarsa
foreach ($_SESSION['cart'] as $key => $item) {
    // Jika item tidak memiliki expiry_time, tambahkan dengan waktu default 1 jam dari sekarang
    if (!isset($item['expiry_time'])) {
        $_SESSION['cart'][$key]['expiry_time'] = time() + 3600;
    }
    // Hapus item yang sudah kedaluwarsa
    elseif ($item['expiry_time'] <= time()) {
        unset($_SESSION['cart'][$key]);
    }
}

// Tambah produk dari input manual (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['barcode'])) {
    tambahProdukKeKeranjang($_POST['barcode'], "s", $conn);
}

// Tambah produk dari GET
if (isset($_GET['barcode']) || isset($_GET['id'])) {
    if (isset($_GET['id'])) {
        tambahProdukKeKeranjang($_GET['id'], "i", $conn);
    } else {
        tambahProdukKeKeranjang($_GET['barcode'], "s", $conn);
    }
    exit;
}
?>

<!-- Tampilan Keranjang -->
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Keranjang</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-green-200 to-green-50 min-h-screen px-4 py-6">

<!-- Wrapper dua kolom -->
<div class="flex flex-col md:flex-row justify-center items-start gap-6">

  <!-- Kolom Kanan: Daftar Pesanan -->
  <div class="w-full max-w-4xl mx-auto mt-10">

    <!-- Judul -->
    <h1 class="text-3xl font-bold text-center text-green-700 mb-8 tracking-wide">Pesanan Anda</h1>

    <?php if (!empty($_SESSION['cart'])): ?>
      <form method="post" action="konfirpmbyrn.php" class="bg-white p-6 rounded-2xl shadow-lg border border-green-200">
        <div class="divide-y divide-green-100">
          <?php foreach ($_SESSION['cart'] as $item): ?>
            <?php
              $expiry_time = $item['expiry_time'] ?? (time() + 3600);
              $time_left = $expiry_time - time();
            ?>
            <div class="py-4 flex flex-col md:flex-row md:items-center gap-6">

              <!-- Tombol Hapus -->
              <button 
                type="button"
                onclick="hapusProduk('<?= $item['id'] ?>')"
                class="text-red-500 hover:text-red-700 text-lg transition self-start md:self-center"
                title="Hapus produk"
              >
                <i class="fas fa-times-circle"></i>
              </button>

              <!-- Checkbox -->
              <input 
                type="checkbox" 
                name="selected[]" 
                value="<?= $item['id'] ?>" 
                class="w-5 h-5 accent-green-600 mt-1 md:mt-0 checkbox-item"
                data-id="<?= $item['id'] ?>"
                data-price="<?= $item['price'] * $item['qty'] ?>"
              >

              <!-- Gambar Produk -->
              <img src="./assets/produk/<?= htmlspecialchars($item['image']) ?>" 
                   alt="<?= htmlspecialchars($item['name']) ?>" 
                   class="w-20 h-24 rounded-lg object-cover shadow-sm">

              <!-- Info Produk -->
              <div class="flex-1 text-sm text-gray-700 grid grid-cols-2 gap-y-1">
                <p class="font-semibold">No</p> <p><?= $item['id'] ?></p>
                <p class="font-semibold">Nama</p> <p><?= htmlspecialchars($item['name']) ?></p>
                <p class="font-semibold">Harga</p> <p>Rp<?= number_format($item['price'], 0, ',', '.') ?></p>
                <p class="font-semibold">Jumlah</p> <p><?= $item['qty'] ?></p>
                <p class="font-semibold">Waktu Sisa</p>
                <p id="time-left-<?= $item['id'] ?>" data-timeleft="<?= $time_left ?>"></p>
                <p class="font-semibold">Total</p> 
                <p class="text-green-700 font-semibold">Rp<?= number_format($item['price'] * $item['qty'], 0, ',', '.') ?></p>
              </div>

              <!-- Tombol Jumlah -->
              <div class="flex items-center gap-2">
                <button type="button" onclick="updateQty('<?= $item['id'] ?>', 'decrease')" class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded-full shadow">−</button>
                <span class="font-semibold"><?= $item['qty'] ?></span>
                <button type="button" onclick="updateQty('<?= $item['id'] ?>', 'increase')" class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded-full shadow">+</button>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <!-- Total Harga -->
        <div class="flex justify-end mt-6">
          <div class="text-lg text-green-800 font-bold">
            Total Harga: <span id="totalHarga">Rp0</span>
          </div>
        </div>

        <!-- Tombol Bayar -->
        <div class="flex justify-center mt-4">
          <button type="submit" class="bg-green-700 hover:bg-green-800 text-white px-10 py-3 rounded-full shadow-lg font-semibold transition">
            Bayar Sekarang
          </button>
        </div>
      </form>
    <?php else: ?>
      <p class="text-center text-gray-500 text-lg mt-10">Keranjang masih kosong.</p>
    <?php endif; ?>
  </div>
</div>


</div>

<script>
function showNotifikasi(pesan, sukses) {
    const notifikasi = document.getElementById("notifikasi");
    notifikasi.textContent = pesan;
    notifikasi.className = sukses
        ? "bg-green-600 mt-4 text-center text-white font-semibold px-4 py-2 rounded-lg"
        : "bg-red-600 mt-4 text-center text-white font-semibold px-4 py-2 rounded-lg";
    notifikasi.style.display = "block";

    // Sembunyikan setelah 3 detik
    setTimeout(() => {
        notifikasi.style.display = "none";
    }, 3000);
}



// Update quantity produk di keranjang
function updateQty(id, action) {
    const formData = new FormData();
    formData.append("id", id);
    formData.append("action", action);

    fetch("cart.php", {
        method: "POST",
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    });
}

// Hapus produk dari keranjang
function hapusProduk(id) {
    if (confirm('Yakin ingin menghapus produk ini dari keranjang?')) {
        fetch('?remove=' + id)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                location.reload();
            }
        });
    }
}

// Format waktu ke hh:mm:ss
function formatTime(t) {
    let hours = Math.floor(t / 3600);
    let minutes = Math.floor((t % 3600) / 60);
    let seconds = t % 60;
    return `: ${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
}

// PERBAIKAN: Waktu kedaluwarsa dari PHP session cart dengan pengecekan
const expiryTime = <?php
    $expiry = [];
    foreach ($_SESSION['cart'] as $item) {
        // Pastikan expiry_time ada sebelum diakses
        $expiry_time = isset($item['expiry_time']) ? $item['expiry_time'] : (time() + 3600);
        $expiry[$item['id']] = $expiry_time;
    }
    echo json_encode($expiry);
?>;

// Update timer kedaluwarsa tiap detik
function updateAllTimers() {
    const now = Math.floor(Date.now() / 1000);
    for (const id in expiryTime) {
        const elem = document.getElementById(`time-left-${id}`);
        if (!elem) continue;

        const timeLeft = expiryTime[id] - now;
        elem.textContent = timeLeft <= 0 ? ': Kedaluwarsa' : formatTime(timeLeft);
    }
}

// Restore status checkbox dari localStorage
function restoreCheckboxState() {
    const saved = localStorage.getItem('checkboxStatus');
    if (!saved) return;

    const status = JSON.parse(saved);
    document.querySelectorAll('.checkbox-item').forEach(cb => {
        const itemId = cb.dataset.id;
        if (status[itemId] !== undefined) {
            cb.checked = status[itemId];
        }
    });

    hitungTotal();
}

// Hitung total harga berdasarkan checkbox terpilih
function hitungTotal() {
    let total = 0;
    document.querySelectorAll('.checkbox-item').forEach(cb => {
        if (cb.checked) {
            total += parseInt(cb.dataset.price);
        }
    });
    document.getElementById('totalHarga').textContent = 'Rp' + total.toLocaleString('id-ID');
}

// Setup event listener checkbox dan inisialisasi saat DOM siap
window.addEventListener('DOMContentLoaded', () => {
    restoreCheckboxState();
    hitungTotal();
    updateAllTimers();
    setInterval(updateAllTimers, 1000);

    document.querySelectorAll('.checkbox-item').forEach(cb => {
        cb.addEventListener('change', () => {
            hitungTotal();
            const status = JSON.parse(localStorage.getItem('checkboxStatus') || '{}');
            status[cb.dataset.id] = cb.checked;
            localStorage.setItem('checkboxStatus', JSON.stringify(status));
        });
    });

});
</script>
</body>
</html>