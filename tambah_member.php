<?php
session_start();
date_default_timezone_set('Asia/Jakarta'); // WIB

$koneksi = new mysqli("localhost", "root", "", "kasir_apotik");

// Proses submit form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name      = $_POST['name'];
    $email     = $_POST['email'];
    $phone     = $_POST['phone'];
    $status    = $_POST['status'];

    // Nilai default
    $point     = 0;
    $amount    = 0;
    $last_trx  = date('Y-m-d H:i:s'); // Waktu WIB

    // 🔹 Cek apakah nomor telepon sudah ada
    $check = $koneksi->prepare("SELECT id FROM member WHERE phone = ?");
    $check->bind_param("s", $phone);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $error = "Nomor telepon sudah terdaftar!";
    } else {
        // 🔹 Simpan ke database jika tidak duplikat
        $stmt = $koneksi->prepare("INSERT INTO member (name, email, phone, transaction_amount, point, status, last_transaction) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssisss", $name, $email, $phone, $amount, $point, $status, $last_trx);

        if ($stmt->execute()) {
            header("Location: member.php?success=1");
            exit();
        } else {
            $error = "Gagal menambahkan member.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Tambah Member</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gradient-to-br from-green-500 to-green-200">

<div class="min-h-screen flex items-center justify-center px-4">
  <div class="bg-white p-8 rounded-xl shadow-md w-full max-w-md border border-gray-200">
    <h1 class="text-2xl font-bold text-green-700 mb-6 text-center">➕ Tambah Member</h1>

    <?php if (!empty($error)): ?>
      <div class="bg-red-100 text-red-700 px-4 py-2 rounded mb-4 text-sm">
        <?= $error ?>
      </div>
    <?php endif; ?>

    <form method="POST">
      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-600">Nama</label>
        <input type="text" name="name" required class="w-full px-4 py-2 rounded border border-gray-300 focus:ring-2 focus:ring-green-400">
      </div>

      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-600">Email</label>
        <input type="email" name="email" required class="w-full px-4 py-2 rounded border border-gray-300 focus:ring-2 focus:ring-green-400">
      </div>

      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-600">Nomor Telepon</label>
        <input type="text" name="phone" required class="w-full px-4 py-2 rounded border border-gray-300 focus:ring-2 focus:ring-green-400">
      </div>

      <div class="mb-6">
        <label class="block text-sm font-medium text-gray-600">Status</label>
        <select name="status" required class="w-full px-4 py-2 rounded border border-gray-300 focus:ring-2 focus:ring-green-400">
          <option value="active">Active</option>
          <option value="non-active">Non-Active</option>
        </select>
      </div>

      <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-2 rounded-lg transition">
        Simpan Member
      </button>

      <a href="member.php" class="block text-center text-sm text-green-700 mt-4 hover:underline">← Kembali ke Data Member</a>
    </form>
  </div>
</div>

</body>
</html>
