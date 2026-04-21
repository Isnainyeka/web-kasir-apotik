<?php
session_start();

// Koneksi ke database
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "kasir_apotik";

$conn = new mysqli($servername, $username, $password, $dbname);

// Cek koneksi database
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Cek jika form dikirim
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Query untuk mencari admin dengan email yang sesuai
    $query = "SELECT id, username, email, password, role FROM admin WHERE email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

            // Cek apakah ada kasir lain yang sedang login
    if (strtolower($user['role']) === 'kasir') {
        $cekAktif = $conn->query("SELECT id FROM admin WHERE role='kasir' AND status='Aktif' AND id != {$user['id']}");
        if ($cekAktif->num_rows > 0) {
            header("Location: login.php?error=kasir_aktif");
            exit();
        }
    }

        if ($password === $user['password']) {
            $_SESSION['id'] = $user['id'];
            $_SESSION['email'] = $email;
            $_SESSION['username'] = $user['username'];

            // Nonaktifkan semua admin dulu
            $conn->query("UPDATE admin SET status = 'Tidak Aktif'");

            // Aktifkan admin yang login
            $updateStatus = "UPDATE admin SET status = 'Aktif' WHERE id = ?";
            $updateStmt = $conn->prepare($updateStatus);
            $updateStmt->bind_param("i", $user['id']);
            $updateStmt->execute();

            header("Location: dashboard.php");
            exit();
        } else {
            header("Location: login.php?error=password");
            exit();
        }
    } else {
        header("Location: login.php?error=email");
        exit();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Login</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" />
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@700&display=swap');
  </style>
</head>
<body class="min-h-screen flex items-center justify-center" style="background: linear-gradient(135deg, #5aa84f 0%, #d9f0d9 100%)">
  <div class="bg-gray-300 rounded-2xl p-8 w-80 sm:w-96">
    <h1 class="text-3xl font-extrabold text-center mb-8 font-['Inter']">
      <span class="text-green-700">LO</span><span class="text-black">GIN</span>
    </h1>
    
    <!-- notif -->
<?php if (isset($_GET['error'])): ?>
  <div class="bg-red-200 border border-red-600 text-red-800 px-4 py-2 rounded mb-4 text-sm text-center">
    <?php
      if ($_GET['error'] === 'email') {
        echo "Login gagal! Email belum terdaftar.";
      } elseif ($_GET['error'] === 'password') {
        echo "Login gagal! Kata sandi yang kamu masukkan salah. Silakan coba lagi.";
      } elseif ($_GET['error'] === 'kasir_aktif') {
        echo "Kasir lain sedang aktif. Silakan tunggu hingga kasir tersebut logout.";
      }
    ?>
  </div>
<?php endif; ?>

    <form method="POST" action="login.php">
      <!-- Email -->
      <div class="relative mb-6">
        <span class="absolute left-2 top-2 text-black text-lg">
          <i class="fas fa-envelope"></i>
        </span>
        <input type="email" name="email" placeholder="Email" required class="w-full border-b border-black bg-transparent pl-9 py-1 focus:outline-none" />
      </div>
      
      <!-- Password -->
      <div class="relative mb-1">
        <span class="absolute left-2 top-2 text-black text-lg">
          <i class="fas fa-lock"></i>
        </span>
        <input type="password" name="password" placeholder="Password" required class="w-full border-b border-black bg-transparent pl-9 py-1 focus:outline-none" />
      </div>

      <!-- Forgot Password -->
      <div class="text-right mb-6">
        <a href="forgotpw.php" class="text-xs text-blue-700">Forgot password?</a>
      </div>

      <!-- Submit -->
      <button type="submit" class="w-full bg-green-700 text-black font-bold rounded-full py-2 text-lg mt-2">
        Login
      </button>

    </form>
  </div>
</body>
</html>