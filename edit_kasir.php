<?php
session_start();

$host = "localhost";
$dbname = "kasir_apotik";
$username_db = "root";
$password_db = "";

$conn = new mysqli($host, $username_db, $password_db, $dbname);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

if (!isset($_SESSION['id'])) {
    echo "<script>alert('Silakan login terlebih dahulu!'); window.location='login.php';</script>";
    exit();
}

$loggedInId = $_SESSION['id'];

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<script>alert('ID tidak valid!'); window.location='kasir.php';</script>";
    exit();
}

$id = (int)$_GET['id'];
$query = "SELECT * FROM admin WHERE id = $id";
$result = $conn->query($query);

if (!$result || $result->num_rows == 0) {
    echo "<script>alert('Kasir tidak ditemukan!'); window.location='kasir.php';</script>";
    exit();
}

$admin = $result->fetch_assoc();
$message = "";

// Ambil nilai enum gender
$gender_enum = [];
$gender_result = $conn->query("SHOW COLUMNS FROM admin LIKE 'gender'");
if ($gender_result && $row = $gender_result->fetch_assoc()) {
    preg_match("/^enum\('(.*)'\)$/", $row['Type'], $matches);
    $gender_enum = explode("','", $matches[1]);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = $_POST['username'];
    $email    = $_POST['email'];
    $password = $_POST['password'];
    $status   = $_POST['status'];
    $telepon  = $_POST['telepon'];
    $gender   = $_POST['gender'];
    $image    = $admin['image'];

    // Validasi email dan telepon tidak duplikat
    $cekDuplikat = $conn->prepare("SELECT id, email, telepon FROM admin WHERE (email = ? OR telepon = ?) AND id != ?");
    $cekDuplikat->bind_param("ssi", $email, $telepon, $id);
    $cekDuplikat->execute();
    $result = $cekDuplikat->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if ($row['email'] == $email) {
            $message = "<p class='text-red-600 font-medium'>Email sudah digunakan pengguna lain!</p>";
        } elseif ($row['telepon'] == $telepon) {
            $message = "<p class='text-red-600 font-medium'>Nomor telepon sudah digunakan pengguna lain!</p>";
        }
    } else {
        // Upload gambar jika ada
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $uploadDir = "uploads/";
            if (!is_dir($uploadDir)) mkdir($uploadDir);
            $imageName = time() . '_' . basename($_FILES['image']['name']);
            $targetFile = $uploadDir . $imageName;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                $image = $imageName;
            }
        }

        // Update semua data
        $sql = "UPDATE admin SET username=?, email=?, password=?, status=?, telepon=?, gender=?, image=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssssi", $username, $email, $password, $status, $telepon, $gender, $image, $id);

        if ($stmt->execute()) {
            $_SESSION['success_msg'] = "Kasir berhasil diperbarui!";
            header("Location: kasir.php");
            exit();
        } else {
            $message = "<p class='text-red-600 font-medium'>Gagal memperbarui kasir: {$stmt->error}</p>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Edit Kasir</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</head>
<body class="min-h-screen bg-gradient-to-br from-green-500 to-green-200 flex items-center justify-center p-4">

<form method="POST" enctype="multipart/form-data" class="bg-white shadow-xl rounded-xl w-full max-w-3xl p-10 grid grid-cols-1 md:grid-cols-2 gap-6">
  <h1 class="col-span-full text-center text-3xl font-bold text-green-700">✏️ Edit Kasir</h1>

  <?php if (!empty($message)): ?>
    <div class="col-span-full text-center text-sm"><?= $message ?></div>
  <?php endif; ?>

  <!-- KIRI -->
  <div class="space-y-4">
    <div>
      <label class="text-sm text-gray-700">Nama</label>
      <input type="text" name="username" value="<?= htmlspecialchars($admin['username']) ?>" required class="w-full mt-1 border rounded-lg px-4 py-2" />
    </div>

    <div>
      <label class="text-sm text-gray-700">Email</label>
      <input type="email" name="email" value="<?= htmlspecialchars($admin['email']) ?>" required class="w-full mt-1 border rounded-lg px-4 py-2" />
    </div>

    <div>
      <label class="text-sm text-gray-700">Password</label>
      <input type="text" name="password" value="<?= htmlspecialchars($admin['password']) ?>" required class="w-full mt-1 border rounded-lg px-4 py-2" />
    </div>

    <div>
      <label class="text-sm text-gray-700">Status</label>
<select name="status" class="w-full mt-1 border rounded-lg px-4 py-2">
        <option value="Aktif" <?= $admin['status'] === 'Aktif' ? 'selected' : '' ?>>Aktif</option>
        <option value="Tidak Aktif" <?= $admin['status'] === 'Tidak Aktif' ? 'selected' : '' ?>>Tidak Aktif</option>
      </select>
    </div>
  </div>

  <!-- KANAN -->
  <div class="space-y-4">
    <div>
      <label class="text-sm text-gray-700">Nomor Telepon</label>
      <input type="tel" name="telepon" value="<?= htmlspecialchars($admin['telepon']) ?>" required class="w-full mt-1 border rounded-lg px-4 py-2" />
    </div>

<div>
  <label class="text-sm text-gray-700">Jenis Kelamin</label>
<select name="gender" class="w-full mt-1 border rounded-lg px-4 py-2">
    <option value="">-- Pilih --</option>
    <?php foreach ($gender_enum as $g): ?>
      <option value="<?= $g ?>" <?= $admin['gender'] === $g ? 'selected' : '' ?>><?= ucfirst($g) ?></option>
    <?php endforeach; ?>
  </select>
</div>

    <div class="flex flex-col">
      <label class="text-sm text-gray-700 mb-1 block">Foto Profil</label>
      <label for="gambarInput" class="cursor-pointer relative inline-block">
        <span id="iconContainer" class="w-32 h-32 bg-gray-200 flex items-center justify-center rounded text-gray-600 text-6xl">
          <i class="fas fa-camera"></i>
        </span>
        <img id="imagePreview" src="uploads/<?= htmlspecialchars($admin['image'] ?: 'default.png') ?>" class="w-32 h-32 object-cover rounded absolute top-0 left-0 <?= empty($admin['image']) ? 'hidden' : '' ?>" />
      </label>
      <input type="file" id="gambarInput" name="image" accept="image/*" class="hidden" onchange="previewImage(event)">
    </div>
  </div>

  <!-- TOMBOL -->
  <div class="col-span-full mt-4">
    <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 rounded-lg transition">
      Simpan Perubahan
    </button>
    <a href="kasir.php" class="block text-center text-sm text-green-700 mt-3 hover:underline">← Kembali ke Daftar Kasir</a>
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
