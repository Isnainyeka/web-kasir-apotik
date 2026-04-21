<?php
session_start();
date_default_timezone_set('Asia/Jakarta');

$koneksi = new mysqli("localhost", "root", "", "kasir_apotik");

// Validasi ID
if (!isset($_GET['id'])) {
    header("Location: member.php");
    exit();
}

$id = (int)$_GET['id'];

// Ambil data member dari DB
$member = $koneksi->query("SELECT * FROM member WHERE id = $id")->fetch_assoc();
if (!$member) {
    echo "Member tidak ditemukan.";
    exit();
}

// Proses Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name   = $_POST['name'];
    $email  = $_POST['email'];
    $phone  = $_POST['phone'];
    $status = $_POST['status'];
    $now = date('Y-m-d H:i:s'); // waktu sekarang

    // 🔹 Cek apakah nomor telepon sudah ada pada member lain
    $check = $koneksi->prepare("SELECT id FROM member WHERE phone = ? AND id != ?");
    $check->bind_param("si", $phone, $id);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $error = "Nomor telepon sudah terdaftar pada member lain!";
    } else {
        // 🔹 Lanjut update jika tidak duplikat
        $stmt = $koneksi->prepare("UPDATE member SET name = ?, email = ?, phone = ?, status = ?, last_transaction = ? WHERE id = ?");
        $stmt->bind_param("sssssi", $name, $email, $phone, $status, $now, $id);

        if ($stmt->execute()) {
            header("Location: member.php?updated=1");
            exit();
        } else {
            $error = "Gagal memperbarui data member.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Edit Member</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gradient-to-br from-green-500 to-green-200">

<div class="min-h-screen flex items-center justify-center px-4">
  <div class="bg-white p-8 r ounded-xl shadow-md w-full max-w-md border border-gray-200">
    <h1 class="text-2xl font-bold text-green-700 mb-6 text-center">✏️ Edit Member</h1>

    <?php if (!empty($error)): ?>
      <div class="bg-red-100 text-red-700 px-4 py-2 rounded mb-4 text-sm">
        <?= $error ?>
      </div>
    <?php endif; ?>

    <form method="POST">
      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-600">Nama</label>
        <input type="text" name="name" value="<?= htmlspecialchars($member['name']); ?>" required class="w-full px-4 py-2 rounded border border-gray-300 focus:ring-2 focus:ring-blue-400">
      </div>

      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-600">Email</label>
        <input type="email" name="email" value="<?= htmlspecialchars($member['email']); ?>" required class="w-full px-4 py-2 rounded border border-gray-300 focus:ring-2 focus:ring-blue-400">
      </div>

      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-600">Nomor Telepon</label>
        <input type="text" name="phone" value="<?= htmlspecialchars($member['phone']); ?>" required class="w-full px-4 py-2 rounded border border-gray-300 focus:ring-2 focus:ring-blue-400">
      </div>

      <div class="mb-6">
        <label class="block text-sm font-medium text-gray-600">Status</label>
        <select name="status" required class="w-full px-4 py-2 rounded border border-gray-300 focus:ring-2 focus:ring-blue-400">
          <option value="active" <?= $member['status'] === 'active' ? 'selected' : '' ?>>Active</option>
          <option value="non-active" <?= $member['status'] === 'non-active' ? 'selected' : '' ?>>Non-Active</option>
        </select>
      </div>

      <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-2 rounded-lg transition">
        Simpan Perubahan
      </button>

      <a href="member.php" class="block text-center text-sm text-green-700 mt-4 hover:underline">← Kembali ke Data Member</a>
    </form>
  </div>
</div>

</body>
</html>
