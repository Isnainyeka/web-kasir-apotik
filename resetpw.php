<?php
session_start();

// Debugging error style (opsional)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

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

// Cek apakah token tersedia di URL
if (!isset($_GET['token'])) {
    $_SESSION['message'] = 'Token tidak valid!';
    header('Location: login.php');
    exit;
}

$token = $_GET['token'];

// Cek token dalam database
$stmt = $conn->prepare("SELECT email, expires FROM password_reset WHERE token = ?");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();
$reset = $result->fetch_assoc();

// Jika token tidak ditemukan atau sudah kadaluarsa
if (!$reset || strtotime($reset['expires']) < time()) {
    $_SESSION['message'] = 'Token telah kadaluarsa atau tidak valid!';
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Reset Password</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" />
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@700&display=swap');
  </style>
</head>
<body class="min-h-screen flex items-center justify-center" style="background: linear-gradient(135deg, #5aa84f 0%, #d9f0d9 100%)">
  <div class="bg-gray-300 rounded-2xl p-8 w-80 sm:w-96">
    <h1 class="text-3xl font-extrabold text-center mb-8 font-['Inter']">
      <span class="text-green-700">RESET </span><span class="text-black">PASSWORD</span>
    </h1>

    <form action="updatepw.php" method="POST" onsubmit="return validatePasswords()">
      <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

      <div class="relative mb-6">
        <span class="absolute left-2 top-2 text-black text-lg">
          <i class="fas fa-lock"></i>
        </span>
        <input type="password" placeholder="Password Baru" id="password" name="new_password" required class="w-full border-b border-black bg-transparent pl-9 py-1 focus:outline-none"/>
      </div>

      <div class="relative mb-1">
        <span class="absolute left-2 top-2 text-black text-lg">
          <i class="fas fa-lock"></i>
        </span>
        <input type="password" placeholder="Konfirmasi Password" id="confirmpassword" name="confirm_password" required class="w-full border-b border-black bg-transparent pl-9 py-1 focus:outline-none"/>
      </div>

      <button type="submit" name="reset_password" class="w-full bg-green-700 text-black font-bold rounded-full py-2 text-lg mt-5">
        Reset Password
      </button>

      <div class="text-center mt-4">
        <a href="login.php" class="text-xs text-blue-700">Back to login?</a>
      </div>
    </form>
  </div>

  <!-- JavaScript Validasi Password -->
  <script>
    function validatePasswords() {
      const pass = document.getElementById('password').value;
      const confirm = document.getElementById('confirmpassword').value;
      if (pass !== confirm) {
        alert('Password dan Konfirmasi tidak cocok!');
        return false;
      }
      return true;
    }
  </script>
</body>
</html>