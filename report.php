<?php
session_start();
$koneksi = new mysqli("localhost", "root", "", "kasir_apotik");
if ($koneksi->connect_error) die("Koneksi gagal: " . $koneksi->connect_error);

// Cek apakah user sudah login
if (isset($_SESSION['id'])) {
    $id_user = $_SESSION['id'];
    $query = $koneksi->query("SELECT * FROM admin WHERE id = $id_user");
    $akun = $query->fetch_assoc();

    // Set role dari database (kalau belum diset di session)
    if (!isset($_SESSION['role']) && isset($akun['role'])) {
        $_SESSION['role'] = $akun['role'];
    }
} else {
    // Jika session id tidak tersedia, redirect ke login
    header("Location: login.php");
    exit();
}

// Cek role valid atau tidak
if (!isset($_SESSION['role'])) {
    echo "<script>alert('Session role tidak ditemukan!'); window.location='login.php';</script>";
    exit();
}
$role = $_SESSION['role']; // Buat variabel lokal biar lebih aman

$filter = $_GET['filter'] ?? 'tahunan';
$tanggal = $_GET['tanggal'] ?? null;

$labels = [];
$totals_modal = [];
$totals_keuntungan = [];
$totals_penjualan = [];

if ($tanggal) {
    $tgl = new DateTime($tanggal);
    $tahun = (int)$tgl->format('Y');
    $bulan = (int)$tgl->format('m');
    $minggu_ke = (int)$tgl->format('W');
    $hari = $tgl->format('Y-m-d');
}

// Function to get financial data for transactions
function getFinancialData($koneksi, $where_clause) {
    $query = "
        SELECT 
            t.tanggal_beli,
            t.nama_produk,
            t.total_harga,
            p.starting_price,
            p.margin
        FROM transactions t
        LEFT JOIN products p ON p.product_name LIKE CONCAT('%', SUBSTRING_INDEX(TRIM(SUBSTRING_INDEX(t.nama_produk, '(', 1)), ' ', -1), '%')
        WHERE $where_clause
        ORDER BY t.tanggal_beli
    ";
    
    $result = $koneksi->query($query);
    $data = [];
    
    while ($row = $result->fetch_assoc()) {
        // Extract quantity from product name (assuming format like "product name (qty x)")
        preg_match('/\((\d+)x\)/', $row['nama_produk'], $matches);
        $qty = isset($matches[1]) ? (int)$matches[1] : 1;
        
$modal = ($row['starting_price'] ?? 0) * $qty;
$penjualan = $row['total_harga'] ?? 0;
$keuntungan = $penjualan - $modal;
        
        $date_key = $row['tanggal_beli'];
        if (!isset($data[$date_key])) {
            $data[$date_key] = ['modal' => 0, 'keuntungan' => 0, 'penjualan' => 0];
        }
        
        $data[$date_key]['modal'] += $modal;
        $data[$date_key]['keuntungan'] += $keuntungan;
        $data[$date_key]['penjualan'] += $penjualan;
    }
    
    return $data;
}

if ($filter == 'harian' && isset($hari)) {
    $labels[] = $tgl->format('d M Y');
    $financial_data = getFinancialData($koneksi, "tanggal_beli = '$hari'");
    
    $totals_modal[] = $financial_data[$hari]['modal'] ?? 0;
    $totals_keuntungan[] = $financial_data[$hari]['keuntungan'] ?? 0;
    $totals_penjualan[] = $financial_data[$hari]['penjualan'] ?? 0;
}
elseif ($filter == 'mingguan' && isset($minggu_ke)) {
    $start = new DateTime();
    $start->setISODate($tahun, $minggu_ke);
    $end = clone $start;
    $end->modify('+6 days');

    $period = new DatePeriod($start, new DateInterval('P1D'), (clone $end)->modify('+1 day'));
    foreach ($period as $day) {
        $labels[] = $day->format('d M');
        $totals_modal[] = 0;
        $totals_keuntungan[] = 0;
        $totals_penjualan[] = 0;
    }

    $financial_data = getFinancialData($koneksi, "tanggal_beli BETWEEN '{$start->format('Y-m-d')}' AND '{$end->format('Y-m-d')}'");
    
    foreach ($financial_data as $date => $data) {
        $key = array_search((new DateTime($date))->format('d M'), $labels);
        if ($key !== false) {
            $totals_modal[$key] = $data['modal'];
            $totals_keuntungan[$key] = $data['keuntungan'];
            $totals_penjualan[$key] = $data['penjualan'];
        }
    }
}
elseif ($filter == 'bulanan' && isset($bulan)) {
    $days_in_month = cal_days_in_month(CAL_GREGORIAN, $bulan, $tahun);
    for ($i = 1; $i <= $days_in_month; $i++) {
        $tgl_str = sprintf('%04d-%02d-%02d', $tahun, $bulan, $i);
        $labels[] = date('d M', strtotime($tgl_str));
        $totals_modal[] = 0;
        $totals_keuntungan[] = 0;
        $totals_penjualan[] = 0;
    }

    $financial_data = getFinancialData($koneksi, "YEAR(tanggal_beli) = $tahun AND MONTH(tanggal_beli) = $bulan");
    
    foreach ($financial_data as $date => $data) {
        $key = array_search((new DateTime($date))->format('d M'), $labels);
        if ($key !== false) {
            $totals_modal[$key] = $data['modal'];
            $totals_keuntungan[$key] = $data['keuntungan'];
            $totals_penjualan[$key] = $data['penjualan'];
        }
    }
}
elseif ($filter == 'tahunan' && isset($tahun)) {
    $bulan_nama = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    $labels = $bulan_nama;
    $totals_modal = array_fill(0, 12, 0);
    $totals_keuntungan = array_fill(0, 12, 0);
    $totals_penjualan = array_fill(0, 12, 0);

    $financial_data = getFinancialData($koneksi, "YEAR(tanggal_beli) = $tahun");
    
    foreach ($financial_data as $date => $data) {
        $month_index = (int)date('m', strtotime($date)) - 1;
        $totals_modal[$month_index] += $data['modal'];
        $totals_keuntungan[$month_index] += $data['keuntungan'];
        $totals_penjualan[$month_index] += $data['penjualan'];
    }
}
else {
    // Default: group by year
    $financial_data = getFinancialData($koneksi, "1=1");
    $yearly_data = [];
    
    foreach ($financial_data as $date => $data) {
        $year = date('Y', strtotime($date));
        if (!isset($yearly_data[$year])) {
            $yearly_data[$year] = ['modal' => 0, 'keuntungan' => 0, 'penjualan' => 0];
        }
        $yearly_data[$year]['modal'] += $data['modal'];
        $yearly_data[$year]['keuntungan'] += $data['keuntungan'];
        $yearly_data[$year]['penjualan'] += $data['penjualan'];
    }
    
    foreach ($yearly_data as $year => $data) {
        $labels[] = $year;
        $totals_modal[] = $data['modal'];
        $totals_keuntungan[] = $data['keuntungan'];
        $totals_penjualan[] = $data['penjualan'];
    }
}

$koneksi->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Laporan Keuangan</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet"/>
</head>
<body class="bg-gray-50 text-gray-800">

<div class="flex min-h-screen">
  <!-- Sidebar -->
  <aside class="w-64 bg-[#7db073] text-white flex flex-col shadow-lg">
    <div class="px-6 py-6 text-2xl font-bold text-center border-b border-green-500">
  <img src="logoapotek.png" alt="Apotek Logo" class="mx-auto h-24">
</div>
<nav class="flex-1 px-4 py-6 space-y-2">
  <?php if ($role === 'superadmin'): ?>
    <a href="dashboard.php" class="flex items-center px-4 py-2 rounded-lg hover:bg-green-700 transition">
      <span class="ml-2">Dashboard</span>
    </a>
    <a href="kasir.php" class="flex items-center px-4 py-2 rounded-lg hover:bg-green-700 transition">
      <span class="ml-2">Kasir</span>
    </a>
    <a href="member.php" class="flex items-center px-4 py-2 rounded-lg hover:bg-green-700 transition">
      <span class="ml-2">Members</span>
    </a>
    <a href="category.php" class="flex items-center px-4 py-2 rounded-lg hover:bg-green-700 transition">
      <span class="ml-2">Category</span>
    </a>
    <a href="product.php" class="flex items-center px-4 py-2 rounded-lg hover:bg-green-700 transition">
      <span class="ml-2">Products</span>
    </a>
    <a href="report.php" class="flex items-center px-4 py-2 rounded-lg bg-[#68975f] font-semibold">
      <span class="ml-2">Report</span>
    </a>
  <?php elseif ($role === 'kasir'): ?>
    <a href="dashboard.php" class="flex items-center px-4 py-2 rounded-lg hover:bg-green-700 transition">
      <span class="ml-2">Dashboard</span>
    </a>
        <a href="kasir.php" class="flex items-center px-4 py-2 rounded-lg hover:bg-green-700 transition">
      <span class="ml-2">Kasir</span>
    </a>
    <a href="member.php" class="flex items-center px-4 py-2 rounded-lg hover:bg-green-700 transition">
      <span class="ml-2">Members</span>
    </a>
    <a href="transaksi.php" class="flex items-center px-4 py-2 rounded-lg hover:bg-green-700 transition">
      <span class="ml-2">Transaksi</span>
    </a>
    <a href="report.php" class="flex items-center px-4 py-2 rounded-lg bg-[#68975f] font-semibold">
      <span class="ml-2">Report</span>
    </a>
  <?php endif; ?>
</nav>

    <!-- Profile Akun -->
    <div class="px-4 py-4 border-t border-green-500">
      <button onclick="toggleModal()" class="w-full text-left px-4 py-2 rounded-lg bg-[#68975f] hover:bg-[#5a824e] transition">
        👤 <?php echo $akun['username']; ?>
      </button>
      <a href="logout.php" class="block mt-3 text-center py-2 rounded bg-red-500 hover:bg-red-600 transition">Logout</a>
    </div>
  </aside>

  <!-- Modal Akun -->
<div id="modalAkun" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
  <div class="bg-white rounded-xl shadow-lg w-full max-w-md p-6 relative">
    <button onclick="toggleModal()" class="absolute top-2 right-3 text-gray-600 hover:text-red-500 text-xl">&times;</button>
    <h2 class="text-2xl font-semibold text-green-700 mb-4">Profil Akun</h2>
    <?php if (!empty($akun['image'])): ?>
  <img src="uploads/<?= $akun['image']; ?>" class="w-24 h-24 rounded-full mx-auto mb-4 border-4 border-green-300 object-cover" alt="Foto Profil">
<?php else: ?>
  <div class="w-24 h-24 mx-auto mb-4 rounded-full bg-green-200 flex items-center justify-center text-2xl text-white font-bold border-4 border-green-300">
    <?= strtoupper($akun['username'][0]); ?>
  </div>
<?php endif; ?>
    <div class="space-y-3 text-sm text-gray-700">
      <p><strong>Username:</strong> <?= $akun['username']; ?></p>
      <p><strong>Email:</strong> <?= $akun['email']; ?></p>
      <p><strong>No. Telepon:</strong> <?= $akun['telepon']; ?></p>
      <p><strong>Role:</strong> <?= ucfirst($akun['role']); ?></p>
      <p><strong>Status:</strong> <?= $akun['status']; ?></p>
      <p><strong>Jenis Kelamin:</strong> <?= ucfirst($akun['gender']); ?></p>
    </div>
  </div>
</div>

<!-- Script Modal -->
<script>
  function toggleModal() {
    const modal = document.getElementById("modalAkun");
    modal.classList.toggle("hidden");
    modal.classList.toggle("flex");
  }
</script>

<!-- Main Content -->
<div class="flex-1 px-8 py-12 bg-[#F9F6F1]">
    <h1 class="text-3xl font-bold text-gray-800">Laporan Keuangan</h1>

    <!-- Summary Cards -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-10 mb-10">
      <div class="bg-green-100 border-l-8 border-green-600 rounded-xl p-6 shadow-md">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-green-800 font-medium">Total Modal</p>
            <p class="text-2xl font-bold text-green-900">Rp <?= number_format(array_sum($totals_modal), 0, ',', '.') ?></p>
          </div>
          <i class="fas fa-coins text-3xl text-green-700"></i>
        </div>
      </div>

      <div class="bg-blue-100 border-l-8 border-blue-600 rounded-xl p-6 shadow-md">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-blue-800 font-medium">Total Keuntungan</p>
            <p class="text-2xl font-bold text-blue-900">Rp <?= number_format(array_sum($totals_keuntungan), 0, ',', '.') ?></p>
          </div>
          <i class="fas fa-chart-line text-3xl text-blue-700"></i>
        </div>
      </div>

      <div class="bg-purple-100 border-l-8 border-purple-600 rounded-xl p-6 shadow-md">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-purple-800 font-medium">Total Penjualan</p>
            <p class="text-2xl font-bold text-purple-900">Rp <?= number_format(array_sum($totals_penjualan), 0, ',', '.') ?></p>
          </div>
          <i class="fas fa-money-bill-wave text-3xl text-purple-700"></i>
        </div>
      </div>
    </div>

    <!-- Filter Form -->
    <form method="GET" class="flex flex-col md:flex-row md:items-center justify-between bg-[#f1e7dc] px-6 py-4 rounded-xl mb-10 shadow">
      <div class="flex items-center gap-2 mb-4 md:mb-0">
        <label for="filter" class="text-sm font-semibold text-[#5b4636]">Filter Waktu:</label>
        <select id="filter" name="filter" class="rounded-lg border border-gray-300 p-2">
          <option value="harian" <?= $filter == 'harian' ? 'selected' : '' ?>>Harian</option>
          <option value="mingguan" <?= $filter == 'mingguan' ? 'selected' : '' ?>>Mingguan</option>
          <option value="bulanan" <?= $filter == 'bulanan' ? 'selected' : '' ?>>Bulanan</option>
          <option value="tahunan" <?= $filter == 'tahunan' ? 'selected' : '' ?>>Tahunan</option>
        </select>
      </div>

      <div class="flex items-center gap-2 mb-4 md:mb-0">
        <label for="tanggal" class="text-sm font-semibold text-[#5b4636]">Tanggal:</label>
        <input type="date" id="tanggal" name="tanggal" value="<?= $tanggal ?>" class="rounded-lg border border-gray-300 p-2">
      </div>

<button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg transition">Tampilkan</button>
    </form>

    <!-- Grafik Bar -->
    <div class="bg-[#fffdfc] p-6 rounded-xl shadow-md mb-10">
      <h2 class="text-lg font-semibold text-[#5b4636] mb-4">Grafik Laporan</h2>
      <canvas id="laporanChart" height="100"></canvas>
    </div>

    <!-- Pie Chart -->
    <div class="bg-[#fffdfc] p-6 rounded-xl shadow-md">
      <h2 class="text-lg font-semibold text-[#5b4636] mb-4 text-center">Distribusi Keuangan</h2>
      <div class="flex justify-center">
        <canvas id="pieChart" style="max-width: 400px; max-height: 400px;"></canvas>
      </div>
    </div>

<!-- Tabel Keuangan (Disembunyikan untuk PDF) -->
<div id="hiddenTable" style="display:none; padding: 40px; background-color: white; font-family: Arial, sans-serif;">
  <h2 style="text-align:center; margin-bottom: 20px; font-size: 18px; font-weight: bold;">Laporan Keuangan</h2>
  <table style="width: 100%; border-collapse: collapse; font-size: 11px;">
    <thead>
      <tr style="background-color: #f0dfc8; color: #000;">
        <th style="border: 1px solid #000; padding: 6px 10px; width: 5%;">No</th>
        <th style="border: 1px solid #000; padding: 6px 10px; width: 25%;">Periode</th>
        <th style="border: 1px solid #000; padding: 6px 10px; width: 25%;">Total Modal</th>
        <th style="border: 1px solid #000; padding: 6px 10px; width: 25%;">Total Keuntungan</th>
        <th style="border: 1px solid #000; padding: 6px 10px; width: 20%;">Total Penjualan</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($labels as $i => $label): ?>
        <tr>
          <td style="border: 1px solid #000; padding: 6px 10px; text-align: center;"><?= $i + 1 ?></td>
          <td style="border: 1px solid #000; padding: 6px 10px;"><?= $label ?></td>
          <td style="border: 1px solid #000; padding: 6px 10px;">Rp <?= number_format($totals_modal[$i], 0, ',', '.') ?></td>
          <td style="border: 1px solid #000; padding: 6px 10px;">Rp <?= number_format($totals_keuntungan[$i], 0, ',', '.') ?></td>
          <td style="border: 1px solid #000; padding: 6px 10px;">Rp <?= number_format($totals_penjualan[$i], 0, ',', '.') ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
    <tfoot>
      <tr style="background-color: #e8d8c3; font-weight: bold;">
        <td colspan="2" style="border: 1px solid #000; padding: 6px 10px; text-align: center;">TOTAL</td>
        <td style="border: 1px solid #000; padding: 6px 10px;">Rp <?= number_format(array_sum($totals_modal), 0, ',', '.') ?></td>
        <td style="border: 1px solid #000; padding: 6px 10px;">Rp <?= number_format(array_sum($totals_keuntungan), 0, ',', '.') ?></td>
        <td style="border: 1px solid #000; padding: 6px 10px;">Rp <?= number_format(array_sum($totals_penjualan), 0, ',', '.') ?></td>
      </tr>
    </tfoot>
  </table>
</div>

    <!-- Tombol Cetak -->
    <div class="flex justify-center mt-10">
      <button id="btnCetakPDF" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg transition">
        📥 Download PDF
      </button>
    </div>
  </div>
</div>
</div>

<!-- Chart.js dan Cetak PDF Script -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

<script>
  function toggleProfile() {
    const modal = document.getElementById('profileModal');
    modal.classList.toggle('hidden');
  }

  document.addEventListener('DOMContentLoaded', () => {
  const ctx = document.getElementById('laporanChart').getContext('2d');
  new Chart(ctx, {
    type: 'line',
    data: {
      labels: <?php echo json_encode($labels); ?>,
      datasets: [
        {
          label: 'Total Modal',
          data: <?php echo json_encode($totals_modal); ?>,
          borderColor: '#22c55e',
          backgroundColor: 'rgba(34, 197, 94, 0.2)',
          fill: false,
          tension: 0.3,
          pointRadius: 4,
          pointBackgroundColor: '#16a34a',
        },
        {
          label: 'Total Keuntungan',
          data: <?php echo json_encode($totals_keuntungan); ?>,
          borderColor: '#3b82f6',
          backgroundColor: 'rgba(59, 130, 246, 0.2)',
          fill: false,
          tension: 0.3,
          pointRadius: 4,
          pointBackgroundColor: '#2563eb',
        },
        {
          label: 'Total Penjualan',
          data: <?php echo json_encode($totals_penjualan); ?>,
          borderColor: '#a855f7',
          backgroundColor: 'rgba(168, 85, 247, 0.2)',
          fill: false,
          tension: 0.3,
          pointRadius: 4,
          pointBackgroundColor: '#9333ea',
        }
      ]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { position: 'top' }
      },
      scales: {
        y: {
          beginAtZero: true
        }
      }
    }
  });

const pieCtx = document.getElementById('pieChart').getContext('2d');

// Ambil total dari masing-masing dataset
const totalModal = <?= array_sum($totals_modal) ?>;
const totalKeuntungan = <?= array_sum($totals_keuntungan) ?>;
const totalPenjualan = <?= array_sum($totals_penjualan) ?>;

new Chart(pieCtx, {
  type: 'pie',
  data: {
    labels: ['Total Modal', 'Total Keuntungan', 'Total Penjualan'],
    datasets: [{
      label: 'Proporsi',
      data: [totalModal, totalKeuntungan, totalPenjualan],
      backgroundColor: [
        'rgba(34, 197, 94, 0.6)',
        'rgba(59, 130, 246, 0.6)',
        'rgba(168, 85, 247, 0.6)'
      ],
      borderColor: [
        '#16a34a',
        '#2563eb',
        '#9333ea'
      ],
      borderWidth: 1
    }]
  },
  options: {
    responsive: false,
    plugins: {
      legend: { position: 'bottom' }
      }
    }
  });
});

  document.getElementById('filter').addEventListener('change', function () {
    const tanggalInput = document.getElementById('tanggal');
    tanggalInput.type = this.value === 'tahunan' ? 'number' : 'date';
    if (this.value === 'tahunan') {
      tanggalInput.placeholder = 'Masukkan tahun (mis: 2025)';
      tanggalInput.value = new Date().getFullYear();
    } else {
      tanggalInput.placeholder = '';
    }
  });

document.addEventListener('DOMContentLoaded', () => {
  const btnCetak = document.getElementById('btnCetakPDF');
  btnCetak.addEventListener('click', () => {
    const { jsPDF } = window.jspdf;
    const margin = 40;
    const spacing = 20;

    const chartCanvas = document.getElementById('laporanChart');
    const tableElement = document.getElementById('hiddenTable');
    const pieCanvas = document.getElementById('pieChart');

    // Tampilkan tabel untuk dirender
    tableElement.style.display = 'block';
    tableElement.style.position = 'absolute';
    tableElement.style.left = '-9999px';
    tableElement.style.visibility = 'visible';

    // 1️⃣ Render semua elemen dulu untuk menghitung ukuran total
    Promise.all([
      html2canvas(tableElement, { scale: 2, backgroundColor: '#ffffff' }),
      html2canvas(chartCanvas, { scale: 2 }),
      html2canvas(pieCanvas, { scale: 2 })
    ]).then(([tableCanvas, chartCanvasImage, pieCanvasImage]) => {
      
      // Hitung lebar maksimum dari semua elemen
      const maxWidth = Math.max(
        tableCanvas.width,
        chartCanvasImage.width,
        pieCanvasImage.width
      );
      
      // Tentukan lebar PDF berdasarkan konten (minimum 595pt untuk A4)
      const pdfWidth = Math.max(595, (maxWidth / 2) + (margin * 2));
      
      // Hitung tinggi setiap elemen dengan proporsi yang benar
      const tableHeight = (tableCanvas.height * (pdfWidth - margin * 2)) / tableCanvas.width;
      const chartHeight = (chartCanvasImage.height * (pdfWidth - margin * 2)) / chartCanvasImage.width;
      
      // Untuk pie chart, batasi tinggi maksimum tapi tetap proporsional
      const maxPieHeight = 400;
      let pieWidth = pdfWidth - margin * 2;
      let pieHeight = (pieCanvasImage.height * pieWidth) / pieCanvasImage.width;
      
      if (pieHeight > maxPieHeight) {
        pieHeight = maxPieHeight;
        pieWidth = (pieCanvasImage.width * pieHeight) / pieCanvasImage.height;
      }
      
      // Hitung total tinggi yang dibutuhkan
      const totalHeight = margin + tableHeight + spacing + chartHeight + spacing + pieHeight + margin;
      
      // Buat PDF dengan ukuran custom
      const pdf = new jsPDF({
        orientation: 'portrait',
        unit: 'pt',
        format: [pdfWidth, totalHeight]
      });

      let yPos = margin;

      // 2️⃣ Tambahkan tabel
      const tableImgData = tableCanvas.toDataURL('image/png');
      pdf.addImage(tableImgData, 'PNG', margin, yPos, pdfWidth - margin * 2, tableHeight);
      yPos += tableHeight + spacing;

      // 3️⃣ Tambahkan grafik
      const chartImgData = chartCanvasImage.toDataURL('image/png');
      pdf.addImage(chartImgData, 'PNG', margin, yPos, pdfWidth - margin * 2, chartHeight);
      yPos += chartHeight + spacing;

      // 4️⃣ Tambahkan pie chart (tengah)
      const pieImgData = pieCanvasImage.toDataURL('image/png');
      const centerX = (pdfWidth - pieWidth) / 2;
      pdf.addImage(pieImgData, 'PNG', centerX, yPos, pieWidth, pieHeight);

      // Simpan PDF
      pdf.save('laporan_keuangan.pdf');

      // Reset tampilan tabel
      tableElement.style.display = 'none';
      tableElement.style.position = '';
      tableElement.style.left = '';
      tableElement.style.visibility = 'hidden';
      
    }).catch(err => {
      console.error('Gagal generate PDF:', err);
      
      // Reset tampilan tabel jika error
      tableElement.style.display = 'none';
      tableElement.style.position = '';
      tableElement.style.left = '';
      tableElement.style.visibility = 'hidden';
    });
  });
}); 
</script>

</body>
</html>