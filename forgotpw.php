<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Forgot Password</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" />
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@700&display=swap');
  </style>
</head>
<body class="min-h-screen flex items-center justify-center" style="background: linear-gradient(135deg, #5aa84f 0%, #d9f0d9 100%)">
  <div class="bg-gray-300 rounded-2xl p-8 w-80 sm:w-96">
    <h1 class="text-3xl font-extrabold text-center mb-6 font-['Inter']">
      <span class="text-green-700">RESET</span><span class="text-black">PASSWORD</span>
    </h1>

    <!-- Menampilkan pesan notifikasi -->
    <?php if (isset($_SESSION['message'])): ?>
      <div class="<?php echo strpos($_SESSION['message'], 'tidak ditemukan') !== false ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800'; ?> text-sm px-4 py-2 rounded mb-4 text-center">
        <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
      </div>
    <?php endif; ?>

    <form action="sendreset.php" method="POST">
      <div class="relative mb-6">
        <span class="absolute left-2 top-2 text-black text-lg">
          <i class="fas fa-envelope"></i>
        </span>
        <input type="email" name="email" required placeholder="Masukkan email kamu" class="w-full border-b border-black bg-transparent pl-9 py-1 focus:outline-none"/>
      </div>
      <button type="submit" class="w-full bg-green-700 text-black font-bold rounded-full py-2 text-lg">
        Kirim Link Reset
      </button>
      <div class="text-center mt-4">
        <a href="login.php" class="text-xs text-blue-700">Back to login?</a>
      </div>
    </form>
  </div>
</body>
</html>
