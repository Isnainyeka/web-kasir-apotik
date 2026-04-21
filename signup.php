<?php
// Koneksi ke database
$host = "localhost";
$dbname = "kasir_apotik";
$username_db = "root";
$password_db = "";

// Membuat koneksi
$conn = new mysqli($host, $username_db, $password_db, $dbname);

// Memeriksa koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Ambil nilai enum role dari database
$role_enum = [];
$query = "SHOW COLUMNS FROM admin LIKE 'role'";
$result = $conn->query($query);
if ($result && $row = $result->fetch_assoc()) {
    preg_match("/^enum\('(.*)'\)$/", $row['Type'], $matches);
    $role_enum = explode("','", $matches[1]);
}

// Ambil nilai enum gender dari database
$gender_enum = [];
$query = "SHOW COLUMNS FROM admin LIKE 'gender'";
$result = $conn->query($query);
if ($result && $row = $result->fetch_assoc()) {
    preg_match("/^enum\('(.*)'\)$/", $row['Type'], $matches);
    $gender_enum = explode("','", $matches[1]);
}

// Inisialisasi variabel untuk pesan
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Mengambil data dari form
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $telepon = $_POST['telepon'];
    $role = $_POST['role'];
    $alamat = $_POST['alamat'];
    $gender = $_POST['gender'];

    // Validasi jika password dan confirm password cocok
    if ($password !== $confirm_password) {
        $message = "<p style='color: red;'>Password dan Confirm Password tidak cocok!</p>";
    } else {
        // Mengecek apakah email sudah ada di database
        $checkEmailSql = "SELECT email FROM admin WHERE email = ?";
        $stmt = $conn->prepare($checkEmailSql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $message = "<p style='color: red;'>Email sudah terdaftar!</p>";
        } else {
            // Query untuk memasukkan data ke dalam tabel admin (pastikan tabel memiliki kolom baru)
            $sql = "INSERT INTO admin (username, email, password, telepon, role, alamat, gender) VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssss", $username, $email, $password, $telepon, $role, $alamat, $gender);
            
            if ($stmt->execute()) {
                header("Location: login.php");
                exit();
            } else {
                $message = "<p style='color: red;'>Error: " . $stmt->error . "</p>";
            }
        }
        $stmt->close();
    }
}
$conn->close();
?>

<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Sign Up</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css"
  />
</head>
<body class="min-h-screen bg-gradient-to-br from-[#5ea94a] to-[#d9f0e0] flex items-center justify-center p-4">
<!-- FORM DIMULAI DI SINI -->
<form method="POST" action="" class="bg-gray-300 rounded-2xl max-w-3xl w-full p-8 md:p-12 grid grid-cols-1 md:grid-cols-2 gap-x-12 gap-y-6" aria-label="Sign Up Form">
  <h1 class="col-span-full text-center text-4xl font-extrabold text-[#2f6f2a] leading-none" aria-label="Sign Up">
    <span class="text-[#5ea94a]">SIGN</span> UP
  </h1>

  <!-- TAMPILKAN PESAN -->
  <?php if (!empty($message)): ?>
    <div class="col-span-full text-center">
      <?= $message ?>
    </div>
  <?php endif; ?>

  <div class="space-y-4">
    <!-- username -->
    <label class="relative block">
      <span class="absolute inset-y-0 left-3 flex items-center text-black text-lg" aria-hidden="true">
        <i class="fas fa-user"></i></span>
      <input type="text" name="username" placeholder="Name" class="w-full rounded-md py-2 pl-10 pr-3 text-black text-base" required/>
    </label>

    <!-- email -->
    <label class="relative block">
      <span class="absolute inset-y-0 left-3 flex items-center text-black text-lg" aria-hidden="true">
        <i class="fas fa-envelope"></i></span>
      <input type="email" name="email" placeholder="Email" class="w-full rounded-md py-2 pl-10 pr-3 text-black text-base" required/>
    </label>

    <!-- password -->
    <label class="relative block">
      <span class="absolute inset-y-0 left-3 flex items-center text-black text-lg" aria-hidden="true">
        <i class="fas fa-eye-slash"></i></span>
      <input type="password" name="password" placeholder="Password" class="w-full rounded-md py-2 pl-10 pr-3 text-black text-base" required/>
    </label>

    <!-- confirm password -->
    <label class="relative block">
      <span class="absolute inset-y-0 left-3 flex items-center text-black text-lg" aria-hidden="true">
        <i class="fas fa-eye-slash"></i></span>
      <input type="password" name="confirm_password" placeholder="Confirm Password" class="w-full rounded-md py-2 pl-10 pr-3 text-black text-base" required/>
    </label>
  </div>

  <div class="space-y-4">
    <!-- telepon -->
    <label class="relative block">
      <span class="absolute inset-y-0 left-3 flex items-center text-black text-lg" aria-hidden="true">
        <i class="fas fa-phone"></i></span>
      <input type="tel" name="telepon" placeholder="Phone" class="w-full rounded-md py-2 pl-10 pr-3 text-black text-base" required/>
    </label>

    <!-- gender -->
    <label class="relative block">
      <span class="absolute inset-y-0 left-3 flex items-center text-black text-lg" aria-hidden="true">
        <i class="fas fa-mars"></i></span>
      <select name="gender" class="w-full rounded-md py-2 pl-10 pr-3 text-black text-base" required>
        <option value="">Select Gender</option>
        <?php foreach ($gender_enum as $g): ?>
          <option value="<?= $g ?>"><?= ucfirst($g) ?></option>
        <?php endforeach; ?>
      </select>
    </label>

    <!-- role -->
    <label class="relative block">
      <span class="absolute inset-y-0 left-3 flex items-center text-black text-lg" aria-hidden="true">
        <i class="fas fa-briefcase"></i></span>
      <select name="role" class="w-full rounded-md py-2 pl-10 pr-3 text-black text-base" required>
        <option value="">Select Position</option>
        <?php foreach ($role_enum as $j): ?>
          <option value="<?= $j ?>"><?= ucfirst($j) ?></option>
        <?php endforeach; ?>
      </select>
    </label>

    <!-- alamat -->
    <label class="relative block">
      <span class="absolute inset-y-0 left-3 flex items-center text-black text-lg" aria-hidden="true">
        <i class="fas fa-map-marker-alt"></i></span>
      <input type="text" name="alamat" placeholder="Location" class="w-full rounded-md py-2 pl-10 pr-3 text-black text-base" required/>
    </label>
  </div>

  <!-- SUBMIT BUTTON -->
  <button type="submit" class="col-span-full mt-6 bg-[#5ea94a] text-black font-extrabold text-xl rounded-full py-3 px-12 hover:bg-[#4a7a36] transition-colors">Sign Up</button>

  <!-- LINK KE LOGIN -->
  <div class="col-span-full text-center mt-4">
    <a href="login.php" class="text-xs text-blue-700">Already have an account?</a>
  </div>
</form>
</body>
</html>