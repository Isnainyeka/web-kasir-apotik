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

// Ambil nilai enum gender
$gender_enum = [];
$query = "SHOW COLUMNS FROM admin LIKE 'gender'";
$result = $conn->query($query);
if ($result && $row = $result->fetch_assoc()) {
    preg_match("/^enum\('(.*)'\)$/", $row['Type'], $matches);
    $gender_enum = explode("','", $matches[1]);
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $email    = $_POST['email'];
    $password = $_POST['password'];
    $confirm  = $_POST['confirm_password'];
    $telepon  = $_POST['telepon'];
    $gender   = $_POST['gender'];
    $image    = "";

    if ($password !== $confirm) {
        $message = "<p class='text-red-600 font-medium'>Password dan konfirmasi tidak cocok!</p>";
    } else {
// Cek email dan telepon apakah sudah ada
$stmt = $conn->prepare("SELECT email, telepon FROM admin WHERE email = ? OR telepon = ?");
$stmt->bind_param("ss", $email, $telepon);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    if ($row['email'] == $email) {
        $message = "<p class='text-red-600 font-medium'>Email sudah terdaftar!</p>";
    } elseif ($row['telepon'] == $telepon) {
        $message = "<p class='text-red-600 font-medium'>Nomor telepon sudah digunakan!</p>";
    }
} else {
            // Upload Gambar
            $target_dir = "uploads/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }

            if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                $image = basename($_FILES["image"]["name"]);
                $target_file = $target_dir . $image;
                move_uploaded_file($_FILES["image"]["tmp_name"], $target_file);
            }

            // Simpan data admin
$role = "Kasir";
$status = "Tidak Aktif";  // set status otomatis non-active

$sql = "INSERT INTO admin (username, email, password, telepon, gender, image, role, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssssssss", $username, $email, $password, $telepon, $gender, $image, $role, $status);

            if ($stmt->execute()) {
                header("Location: kasir.php");
                exit();
            } else {
                $message = "<p class='text-red-600 font-medium'>Gagal menyimpan data: {$stmt->error}</p>";
            }
        }
        $stmt->close();
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Tambah Kasir</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gradient-to-br from-green-500 to-green-200 flex items-center justify-center p-4">

<form method="POST" enctype="multipart/form-data" class="bg-white shadow-xl rounded-xl w-full max-w-3xl p-10 grid grid-cols-1 md:grid-cols-2 gap-6">
  <h1 class="col-span-full text-center text-3xl font-bold text-green-700">➕ Tambah Kasir</h1>

  <?php if (!empty($message)): ?>
    <div class="col-span-full text-center text-sm"><?= $message ?></div>
  <?php endif; ?>

  <!-- KIRI -->
  <div class="space-y-4">
    <div>
      <label class="text-sm text-gray-700">Nama</label>
      <input type="text" name="username" required class="w-full mt-1 border rounded-lg px-4 py-2" />
    </div>

    <div>
      <label class="text-sm text-gray-700">Email</label>
      <input type="email" name="email" required class="w-full mt-1 border rounded-lg px-4 py-2" />
    </div>

    <div>
      <label class="text-sm text-gray-700">Password</label>
      <input type="password" name="password" required class="w-full mt-1 border rounded-lg px-4 py-2" />
    </div>

    <div>
      <label class="text-sm text-gray-700">Konfirmasi Password</label>
      <input type="password" name="confirm_password" required class="w-full mt-1 border rounded-lg px-4 py-2" />
    </div>
  </div>

  <!-- KANAN -->
  <div class="space-y-4">
    <div>
      <label class="text-sm text-gray-700">Nomor Telepon</label>
      <input type="tel" name="telepon" required class="w-full mt-1 border rounded-lg px-4 py-2" />
    </div>

    <div>
      <label class="text-sm text-gray-700">Jenis Kelamin</label>
      <select name="gender" required class="w-full mt-1 border rounded-lg px-4 py-2">
        <option value="">-- Pilih --</option>
        <?php foreach ($gender_enum as $g): ?>
          <option value="<?= $g ?>"><?= ucfirst($g) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="flex flex-col">
      <label class="text-sm text-gray-700 mb-1">Foto Profil</label>
      <label for="gambarInput" class="cursor-pointer relative">
        <span id="iconContainer" class="w-32 h-32 bg-gray-200 flex items-center justify-center rounded text-gray-600 text-6xl">
          <i class="fas fa-camera"></i>
        </span>
        <img id="imagePreview" class="w-32 h-32 object-cover rounded hidden absolute top-0 left-0" />
      </label>
      <input type="file" id="gambarInput" name="image" accept="image/*" class="hidden" onchange="previewImage(event)">
    </div>
  </div>

  <!-- TOMBOL -->
  <div class="col-span-full mt-4">
    <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 rounded-lg transition">
      Simpan Data Kasir
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
