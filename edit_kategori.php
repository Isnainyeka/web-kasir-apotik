<?php
session_start();

$conn = new mysqli("localhost", "root", "", "kasir_apotik");
if ($conn->connect_error) die("Koneksi gagal: " . $conn->connect_error);

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<script>alert('ID tidak valid!'); window.location='category.php';</script>";
    exit();
}

$id = $_GET['id'];
$result = $conn->query("SELECT * FROM category WHERE id = $id");
if ($result->num_rows === 0) {
    echo "<script>alert('Kategori tidak ditemukan!'); window.location='category.php';</script>";
    exit();
}
$category = $result->fetch_assoc();

// Proses update data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newCategory = trim($_POST['category']);

    // Cek duplikat
    $checkQuery = "SELECT * FROM category WHERE category = '$newCategory' AND id != $id";
    $checkResult = $conn->query($checkQuery);
    if ($checkResult->num_rows > 0) {
        echo "<script>alert('Kategori dengan nama tersebut sudah ada!');</script>";
    } else {
        $update = $conn->query("UPDATE category SET category='$newCategory' WHERE id=$id");
        if ($update) {
            echo "<script>alert('Kategori berhasil diperbarui!'); window.location='category.php';</script>";
        } else {
            echo "<script>alert('Gagal memperbarui kategori!');</script>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Edit Kategori</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-green-500 to-green-200 min-h-screen flex items-center justify-center px-4 py-8">

  <div class="bg-white shadow-2xl rounded-xl max-w-md w-full p-8 space-y-6 border border-green-300">
    <h2 class="text-2xl font-bold text-center text-green-800">✏️ Edit Kategori Obat</h2>

    <form method="POST" enctype="multipart/form-data" class="space-y-5">

      <!-- Input Nama Kategori -->
      <div>
        <label class="text-sm font-medium text-gray-700">Nama Kategori</label>
        <input type="text" name="category" required
               value="<?= htmlspecialchars($category['category']) ?>"
               class="w-full px-4 py-2 mt-1 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-400" />
      </div>

      <!-- Tombol -->
      <div class="flex justify-between gap-4">
        <button type="submit"
                class="flex-1 bg-green-600 hover:bg-green-700 text-white font-medium py-2 rounded-lg transition">
          Simpan Perubahan
        </button>
        <a href="category.php"
           class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-800 font-medium py-2 text-center rounded-lg transition">
          Batal
        </a>
      </div>
    </form>
  </div>

</body>
</html>
