<?php
$koneksi = new mysqli("localhost", "root", "", "kasir_apotik");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $category = trim($_POST['category']);

  // Cek duplikat kategori
  $cek = $koneksi->prepare("SELECT COUNT(*) FROM category WHERE category = ?");
  $cek->bind_param("s", $category);
  $cek->execute();
  $cek->bind_result($jumlah);
  $cek->fetch();
  $cek->close();

  if ($jumlah > 0) {
    echo "<script>alert('Kategori $category sudah ada, silakan gunakan nama lain!'); window.location='category.php';</script>";
    exit();
  } else {
    $stmt = $koneksi->prepare("INSERT INTO category (category) VALUES (?)");
    $stmt->bind_param("s", $category);

    if ($stmt->execute()) {
      header("Location: category.php");
      exit();
    } else {
      echo "<script>alert('Gagal menyimpan data kategori!'); window.location='category.php';</script>";
    }
  }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Tambah Kategori</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-green-500 to-green-200 min-h-screen flex items-center justify-center px-4 py-8">

  <div class="bg-white shadow-2xl rounded-xl max-w-md w-full p-8 space-y-6 border border-green-300">
    <h2 class="text-2xl font-bold text-center text-green-800">➕ Tambah Kategori Obat</h2>

    <form method="POST" enctype="multipart/form-data" class="space-y-5">
      <?php if (!empty($message)): ?>
        <p class="text-red-600 text-center text-sm font-medium"><?= $message ?></p>
      <?php endif; ?>

      <!-- Input Nama Kategori -->
      <div>
        <label class="text-sm font-medium text-gray-700">Nama Kategori</label>
        <input type="text" name="category" required
               class="w-full px-4 py-2 mt-1 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-400" />
      </div>

      <!-- Tombol -->
      <div class="flex justify-between gap-4">
        <button type="submit"
                class="flex-1 bg-green-600 hover:bg-green-700 text-white font-medium py-2 rounded-lg transition">
          Simpan
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

