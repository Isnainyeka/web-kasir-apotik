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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $product_name = $_POST['product_name'];
    $qty = intval($_POST['qty']);
    $starting_price = floatval($_POST['starting_price']);
    $selling_price = floatval($_POST['selling_price']);
    $margin = $selling_price - $starting_price;
    $fid_category = $_POST['fid_category'];
    $description = $_POST['description'];
    $expiry_date = $_POST['expiry_date'];

        // Cek apakah nama produk sudah ada
    $check = $conn->prepare("SELECT id FROM products WHERE product_name = ?");
    $check->bind_param("s", $product_name);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        echo "<script>alert('Produk dengan nama ini sudah ada!'); window.location='tambah_obat.php';</script>";
        exit();
    }
    $check->close();

    // Upload gambar
    $target_dir = "assets/produk/";
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $image = $_FILES['image']['name'];
    $target_file = $target_dir . basename($image);
    move_uploaded_file($_FILES['image']['tmp_name'], $target_file);

    // Simpan ke database
$stmt = $conn->prepare("INSERT INTO products (product_name, qty, starting_price, selling_price, margin, fid_category, image, description, expiry_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sidddisss", $product_name, $qty, $starting_price, $selling_price, $margin, $fid_category, $image, $description, $expiry_date);

    if ($stmt->execute()) {
        $last_id = $conn->insert_id;
        $barcode = str_pad($last_id, 5, '0', STR_PAD_LEFT);
        $update = $conn->prepare("UPDATE products SET barcode = ? WHERE id = ?");
        $update->bind_param("si", $barcode, $last_id);
        $update->execute();
        $update->close();

        echo "<script>alert('Produk berhasil ditambahkan!'); window.location='product.php';</script>";
    } else {
        echo "<script>alert('Gagal menambahkan produk!');</script>";
    }

    $stmt->close();
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tambah Produk</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet"/>
</head>
<body class="min-h-screen bg-gradient-to-br from-green-500 to-green-200 flex items-center justify-center p-4">

<form method="POST" enctype="multipart/form-data" class="bg-white shadow-xl rounded-xl w-full max-w-3xl p-10 grid grid-cols-1 md:grid-cols-2 gap-6">
  <h1 class="col-span-full text-center text-3xl font-bold text-green-700">➕ Tambah Obat</h1>

  <div class="space-y-4">
    <div>
      <label class="text-sm text-gray-700">Nama Produk</label>
      <input type="text" name="product_name" required class="w-full mt-1 border rounded-lg px-4 py-2" />
    </div>
    <div>
      <label class="text-sm text-gray-700">Stok</label>
      <input type="number" name="qty" required class="w-full mt-1 border rounded-lg px-4 py-2" />
    </div>
    <div>
      <label class="text-sm text-gray-700">Harga Modal</label>
      <input type="number" name="starting_price" required class="w-full mt-1 border rounded-lg px-4 py-2" />
    </div>
    <div>
      <label class="text-sm text-gray-700">Harga Jual</label>
      <input type="number" name="selling_price" required class="w-full mt-1 border rounded-lg px-4 py-2" />
    </div>
  </div>

  <div class="space-y-4">
    <div>
      <label class="text-sm text-gray-700">Kategori</label>
      <select name="fid_category" required class="w-full mt-1 border rounded-lg px-4 py-2">
        <option value="">-- Pilih Kategori --</option>
        <?php
        $cat = $conn->query("SELECT * FROM category");
        while ($c = $cat->fetch_assoc()) {
          echo "<option value='{$c['id']}'>{$c['category']}</option>";
        }
        ?>
      </select>
    </div>
    <div>
  <label class="text-sm text-gray-700">Tanggal Kedaluwarsa</label>
  <input type="date" name="expiry_date" required class="w-full mt-1 border rounded-lg px-4 py-2" />
</div>
    <div>
      <label class="text-sm text-gray-700">Deskripsi</label>
      <input type="text" name="description" class="w-full mt-1 border rounded-lg px-4 py-2" />
    </div>
    <div class="flex flex-col">
      <label class="text-sm text-gray-700 mb-1">Foto Produk</label>
      <label for="gambarInput" class="cursor-pointer relative">
        <span id="iconContainer" class="w-32 h-32 bg-gray-200 flex items-center justify-center rounded text-gray-600 text-6xl">
          <i class="fas fa-camera"></i>
        </span>
        <img id="imagePreview" class="w-32 h-32 object-cover rounded hidden absolute top-0 left-0" />
      </label>
      <input type="file" id="gambarInput" name="image" accept="image/*" class="hidden" onchange="previewImage(event)">
    </div>
  </div>

  <div class="col-span-full mt-4">
    <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 rounded-lg transition">
      Simpan Data Produk
    </button>
    <a href="product.php" class="block text-center text-sm text-green-700 mt-3 hover:underline">← Kembali ke Daftar Produk</a>
  </div>
</form>

<script>
function previewImage(event) {
  const imagePreview = document.getElementById('imagePreview');
  imagePreview.src = URL.createObjectURL(event.target.files[0]);
  imagePreview.classList.remove('hidden');
}
</script>

</body>
</html>
