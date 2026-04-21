<?php
session_start();
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "kasir_apotik";
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = $_GET['id'];
    $query = "SELECT * FROM products WHERE id = $id";
    $result = mysqli_query($conn, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        $product = mysqli_fetch_assoc($result);
    } else {
        echo "<script>alert('Produk tidak ditemukan!'); window.location='product.php';</script>";
        exit();
    }
} else {
    echo "<script>alert('ID tidak valid!'); window.location='product.php';</script>";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $product_name = trim($_POST['product_name']);
    $qty = intval($_POST['qty']);
    $starting_price = floatval($_POST['starting_price']);
    $selling_price = floatval($_POST['selling_price']);
    $margin = floatval($selling_price - $starting_price);
    $fid_category = $_POST['fid_category'];
    $expiry_date = $_POST['expiry_date'];
    $description = $_POST['description'];
    $image = $product['image'];

    $check = "SELECT * FROM products WHERE product_name = '$product_name' AND id != $id";
    $checkResult = mysqli_query($conn, $check);
    if (mysqli_num_rows($checkResult) > 0) {
        echo "<script>alert('Nama produk sudah digunakan!'); window.history.back();</script>";
        exit();
    }

    if (isset($_FILES['image']['name']) && $_FILES['image']['error'] === 0) {
        $image = $_FILES['image']['name'];
        $target = "assets/produk/" . $image;
        if (!move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
            echo "<script>alert('Gagal mengupload gambar!');</script>";
        }
    }

$updateQuery = "UPDATE products SET 
    product_name='$product_name',
    qty='$qty',
    starting_price='$starting_price',
    selling_price='$selling_price',
    margin='$margin',
    fid_category='$fid_category',
    image='$image',
    description='$description',
    expiry_date='$expiry_date'
    WHERE id=$id";

    if (mysqli_query($conn, $updateQuery)) {
        echo "<script>alert('Produk berhasil diperbarui!'); window.location='product.php';</script>";
    } else {
        echo "<script>alert('Gagal memperbarui produk!');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Produk</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet"/>
</head>
<body class="min-h-screen bg-gradient-to-br from-green-500 to-green-200 flex items-center justify-center p-4">

<form method="POST" enctype="multipart/form-data" class="bg-white shadow-xl rounded-xl w-full max-w-3xl p-10 grid grid-cols-1 md:grid-cols-2 gap-6">
  <h1 class="col-span-full text-center text-3xl font-bold text-green-700">✏️ Edit Obat</h1>

  <div class="space-y-4">
    <div>
      <label class="text-sm text-gray-700">Nama Produk</label>
      <input type="text" name="product_name" value="<?= $product['product_name'] ?>" required class="w-full mt-1 border rounded-lg px-4 py-2" />
    </div>
    <div>
      <label class="text-sm text-gray-700">Stok</label>
      <input type="number" name="qty" value="<?= $product['qty'] ?>" required class="w-full mt-1 border rounded-lg px-4 py-2" />
    </div>
    <div>
      <label class="text-sm text-gray-700">Harga Modal</label>
      <input type="number" name="starting_price" value="<?= $product['starting_price'] ?>" required class="w-full mt-1 border rounded-lg px-4 py-2" />
    </div>
    <div>
      <label class="text-sm text-gray-700">Harga Jual</label>
      <input type="number" name="selling_price" value="<?= $product['selling_price'] ?>" required class="w-full mt-1 border rounded-lg px-4 py-2" />
    </div>
  </div>

  <div class="space-y-4">
    <div>
      <label class="text-sm text-gray-700">Kategori</label>
      <select name="fid_category" required class="w-full mt-1 border rounded-lg px-4 py-2">
        <option value="">-- Pilih Kategori --</option>
        <?php
        $result = $conn->query("SELECT * FROM category");
        while ($row = $result->fetch_assoc()) {
          $selected = $row['id'] == $product['fid_category'] ? 'selected' : '';
          echo "<option value='{$row['id']}' $selected>{$row['category']}</option>";
        }
        ?>
      </select>
    </div>
    <div>
  <label class="text-sm text-gray-700">Tanggal Kedaluwarsa</label>
  <input type="date" name="expiry_date" value="<?= $product['expiry_date'] ?>" required class="w-full mt-1 border rounded-lg px-4 py-2" />
</div>
    <div>
      <label class="text-sm text-gray-700">Deskripsi</label>
      <input type="text" name="description" value="<?= $product['description'] ?>" class="w-full mt-1 border rounded-lg px-4 py-2" />
    </div>
    <div class="flex flex-col">
      <label class="text-sm text-gray-700 mb-1">Foto Produk</label>
      <label for="gambarInput" class="cursor-pointer relative">
        <span id="iconContainer" class="w-32 h-32 bg-gray-200 flex items-center justify-center rounded text-gray-600 text-6xl">
          <i class="fas fa-camera"></i>
        </span>
        <img id="imagePreview" src="assets/produk/<?= $product['image'] ?>" class="w-32 h-32 object-cover rounded absolute top-0 left-0 <?= $product['image'] ? '' : 'hidden' ?>" />
      </label>
      <input type="file" id="gambarInput" name="image" accept="image/*" class="hidden" onchange="previewImage(event)">
    </div>
  </div>

  <div class="col-span-full mt-4">
    <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 rounded-lg transition">
      Simpan Perubahan Produk
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
