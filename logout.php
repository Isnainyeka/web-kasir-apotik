<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Konfirmasi Logout</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
    }
  </style>
</head>
<body class="bg-gradient-to-br from-green-200 to-green-50 flex items-center justify-center min-h-screen">
  <div class="bg-white p-8 rounded-2xl shadow-2xl border border-[#e1cbb0] w-[90%] max-w-md text-center">
    
    <div class="mb-6">
      <div class="text-red-500 text-5xl mb-2">
        ⚠️
      </div>
      <h1 class="text-2xl font-bold text-[#5b4636]">Yakin Ingin Keluar?</h1>
      <p class="text-sm text-gray-600 mt-2">Tindakan ini akan mengakhiri sesi login kamu sekarang.</p>
    </div>

    <div class="flex justify-center gap-4 mt-6">
      <button onclick="window.location.href='logout_session.php'" class="bg-red-500 hover:bg-red-600 text-white font-medium py-2 px-6 rounded-full transition">
        Ya, Keluar
      </button>
      <button onclick="window.location.href='dashboard.php'" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-medium py-2 px-6 rounded-full transition">
        Batal
      </button>
    </div>
  </div>
</body>
</html>
