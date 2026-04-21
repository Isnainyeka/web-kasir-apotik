-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 04, 2026 at 02:21 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `kasir_apotik`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `image` varchar(255) NOT NULL,
  `status` enum('Aktif','Tidak Aktif') NOT NULL,
  `telepon` varchar(15) DEFAULT NULL,
  `role` enum('kasir','superadmin') DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `gender` enum('laki-laki','perempuan') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `email`, `username`, `password`, `image`, `status`, `telepon`, `role`, `alamat`, `gender`) VALUES
(1, 'ssnnaaoke@gmail.com', 'isnainy eka zanuarsih', '098', '1754876852_IMG-20250607-WA0022.jpg', 'Tidak Aktif', '089666222875', 'superadmin', 'bekasi', 'perempuan'),
(2, 'isna@gmail.com', 'kasir', '123', '1754282107_1754279907_IMG-20250607-WA0024.jpg', 'Aktif', '089685221524', 'kasir', NULL, 'perempuan'),
(3, 'nana@gmail.com', 'nanaa', '321', 'IMG-20250607-WA0029.jpg', 'Tidak Aktif', '081285248250', 'kasir', NULL, 'perempuan'),
(10, 'isnaegbt@gmail.com', 'isna ekaaa', '321', 'IMG-20250607-WA0022.jpg', 'Tidak Aktif', '083436848632', 'kasir', NULL, 'perempuan');

-- --------------------------------------------------------

--
-- Table structure for table `category`
--

CREATE TABLE `category` (
  `id` int(11) NOT NULL,
  `category` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `category`
--

INSERT INTO `category` (`id`, `category`) VALUES
(1, 'obat bebas'),
(2, 'obat bebas terbatas'),
(3, 'obat keras'),
(6, 'obat jamu');

-- --------------------------------------------------------

--
-- Table structure for table `member`
--

CREATE TABLE `member` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(200) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `transaction_amount` int(100) NOT NULL,
  `point` int(11) NOT NULL,
  `status` enum('active','non-active') NOT NULL,
  `last_transaction` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `member`
--

INSERT INTO `member` (`id`, `name`, `email`, `phone`, `transaction_amount`, `point`, `status`, `last_transaction`) VALUES
(2, 'cici', 'cici@gmail.com', '089666222875', 3, 140, 'non-active', '2025-08-20 08:07:24'),
(3, 'saka', 'saka@gmail.com', '0895379220008', 4, 185, 'non-active', '2025-08-21 03:00:52');

-- --------------------------------------------------------

--
-- Table structure for table `password_reset`
--

CREATE TABLE `password_reset` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `barcode` varchar(255) NOT NULL,
  `qty` int(11) NOT NULL,
  `starting_price` decimal(10,0) NOT NULL,
  `selling_price` decimal(10,0) NOT NULL,
  `margin` decimal(10,0) NOT NULL,
  `fid_category` int(11) NOT NULL,
  `image` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  `expiry_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `product_name`, `barcode`, `qty`, `starting_price`, `selling_price`, `margin`, `fid_category`, `image`, `description`, `expiry_date`) VALUES
(1, 'Komix Herbal Sirup Obat Batuk 1 Box 15ML', '00001', 90, 12000, 17000, 5000, 1, 'komix.png', 'KOMIX Herbal adalah sirup obat batuk dengan kandungan herbal alami seperti lagundi, jahe merah, thymi herba, licorice, peppermint oil, dan madu. Dapat meredakan batuk berdahak, meringankan pilek, serta menyegarkan pernapasan. Dosis: Dewasa & anak >12 th: ', '2030-01-12'),
(2, 'Bodrex Strip 10 Tablet', '00002', 93, 4000, 6000, 2000, 1, 'bodrex.png', 'Bodrex: obat bebas berisi Paracetamol 600 mg + Kafein 50 mg untuk meredakan sakit kepala, sakit gigi, dan menurunkan demam. Dosis: Dewasa/anak >12 th: 1 tablet, 3–4×/hari. Anak 6–12 th: ½–1 tablet, 3–4×/hari. Hindari: alergi kandungan, gangguan hati berat', '2030-01-12'),
(3, 'Kalpanax Cream 5 gr', '00003', 94, 15000, 20000, 5000, 2, 'kalpanax.png', 'Kalpanax mengandung miconazole nitrate untuk mematikan jamur pada kulit. Kenali dosis dan penggunaan Kalpanax pada kemasan untuk mencegah efek samping ringan, seperti rasa gatal dan kemerahan pada kulit.', '2030-01-12'),
(4, 'Decolgen Kids Flu Syrup 60 ml', '00004', 98, 11000, 16000, 5000, 2, 'decolgen.png', 'Decolgen Kids Flu Syrup adalah sirup 60 ml untuk meredakan gejala flu pada anak seperti demam, sakit kepala, hidung tersumbat, dan bersin, mengandung Paracetamol 120 mg, Pseudoephedrine HCl 7,5 mg, dan Chlorpheniramine Maleate 0,5 mg per 5 ml. Dosisnya 20', '2030-01-12'),
(5, 'Hydrocortison 1% Cream 5 gr Kalbe', '00005', 96, 5000, 8000, 3000, 3, 'kalbe.png', 'Hydrocortison 1% Cream 5 gr Kalbe adalah obat keras berbentuk krim dengan kandungan hydrocortisone acetate 1% untuk meredakan peradangan kulit seperti gatal, kemerahan, atau rasa terbakar akibat eksim, dermatitis, atau alergi. Obat ini bekerja menekan res', '2030-01-12');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id_transaksi` int(11) NOT NULL,
  `nama_produk` varchar(100) DEFAULT NULL,
  `tanggal_beli` date DEFAULT NULL,
  `admin` varchar(50) DEFAULT NULL,
  `harga` int(100) NOT NULL,
  `potongan` varchar(100) NOT NULL,
  `total_harga` int(11) DEFAULT NULL,
  `uang_dibayar` int(11) DEFAULT NULL,
  `kembalian` int(11) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `nama_member` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id_transaksi`, `nama_produk`, `tanggal_beli`, `admin`, `harga`, `potongan`, `total_harga`, `uang_dibayar`, `kembalian`, `phone`, `nama_member`) VALUES
(1, 'Tolak Angin (2x)', '2025-08-10', 'isnacantikk', 8000, '800', 7200, 10000, 2800, '089666222875', ''),
(2, 'Kalpanax (1x)', '2025-08-10', 'naomi', 18000, '0', 18000, 20000, 2000, '', ''),
(3, 'Minyak Kayu Putih (5x)', '2025-08-11', 'isnacantik', 100000, '10000', 90000, 100000, 10000, '089666222875', ''),
(4, 'Komix Herbal Sirup Obat Batuk 1 Box 15ML (5x)', '2025-08-18', 'kasir', 85000, '8500', 76500, 100000, 23500, '089666222875', ''),
(5, 'Komix Herbal Sirup Obat Batuk 1 Box 15ML (2x), Hydrocortison 1% Cream 5 gr Kalbe (2x)', '2025-08-19', 'isna ekaaa', 50000, '5000', 45000, 50000, 5000, '0895379220008', ''),
(6, 'Komix Herbal Sirup Obat Batuk 1 Box 15ML (2x), Hydrocortison 1% Cream 5 gr Kalbe (2x)', '2025-08-19', 'isna ekaaa', 50000, '5000', 45000, 50000, 5000, '089666222875', ''),
(7, 'Kalpanax Cream 5 gr (2x), Bodrex Strip 10 Tablet (3x)', '2025-08-19', 'isna ekaaa', 58000, '5800', 52200, 60000, 7800, '0895379220008', ''),
(8, 'Decolgen Kids Flu Syrup 60 ml (1x), Bodrex Strip 10 Tablet (1x), Kalpanax Cream 5 gr (1x)', '2025-08-20', 'isna ekaaa', 42000, '4200', 37800, 40000, 2200, '089666222875', ''),
(9, 'Bodrex Strip 10 Tablet (2x), Kalpanax Cream 5 gr (1x)', '2025-08-20', 'isna ekaaa', 32000, '3200', 28800, 30000, 1200, '089666222875', ''),
(10, 'Decolgen Kids Flu Syrup 60 ml (1x), Kalpanax Cream 5 gr (1x)', '2025-08-21', 'isna ekaaa', 36000, '3600', 32400, 50000, 17600, '0895379220008', ''),
(11, 'Komix Herbal Sirup Obat Batuk 1 Box 15ML (1x)', '2025-08-21', 'isna ekaaa', 17000, '1700', 15300, 20000, 4700, '0895379220008', 'saka'),
(12, 'Kalpanax Cream 5 gr (1x), Bodrex Strip 10 Tablet (1x)', '2025-08-21', 'isna ekaaa', 26000, '2600', 23400, 50000, 26600, '0895379220008', 'saka');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`),
  ADD KEY `email` (`email`);

--
-- Indexes for table `category`
--
ALTER TABLE `category`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `member`
--
ALTER TABLE `member`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_reset`
--
ALTER TABLE `password_reset`
  ADD PRIMARY KEY (`id`),
  ADD KEY `email` (`email`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `products_ibfk_1` (`fid_category`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id_transaksi`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `category`
--
ALTER TABLE `category`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `member`
--
ALTER TABLE `member`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `password_reset`
--
ALTER TABLE `password_reset`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id_transaksi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `password_reset`
--
ALTER TABLE `password_reset`
  ADD CONSTRAINT `password_reset_ibfk_1` FOREIGN KEY (`email`) REFERENCES `admin` (`email`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`fid_category`) REFERENCES `category` (`id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
