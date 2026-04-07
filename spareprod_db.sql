-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 07, 2026 at 09:34 AM
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
-- Database: `spareprod_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `brand`
--

CREATE TABLE `brand` (
  `id` varchar(50) NOT NULL,
  `nama_brand` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `brand`
--

INSERT INTO `brand` (`id`, `nama_brand`) VALUES
('advics', 'Advics'),
('bosch', 'Bosch'),
('denso', 'Denso'),
('ngk', 'NGK'),
('toyota_genuine_parts', 'Toyota Genuine Parts');

-- --------------------------------------------------------

--
-- Table structure for table `kategori`
--

CREATE TABLE `kategori` (
  `id` varchar(50) NOT NULL,
  `nama_kategori` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kategori`
--

INSERT INTO `kategori` (`id`, `nama_kategori`) VALUES
('1', 'Brakes'),
('2', 'Engine'),
('3', 'Electrical');

-- --------------------------------------------------------

--
-- Table structure for table `pembelian`
--

CREATE TABLE `pembelian` (
  `id_pembelian` varchar(50) NOT NULL,
  `user_id` varchar(50) NOT NULL,
  `kode_produk` varchar(50) NOT NULL,
  `jumlah` int(11) NOT NULL,
  `total_bayar` bigint(20) NOT NULL COMMENT 'Nilai hasil migrasi disimpan dalam satuan sen dari total SQLite.',
  `tanggal_transaksi` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','dibayar','dikirim','selesai','batal') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pembelian`
--

INSERT INTO `pembelian` (`id_pembelian`, `user_id`, `kode_produk`, `jumlah`, `total_bayar`, `tanggal_transaksi`, `status`) VALUES
('SSK-1001', '2', 'BPS-CER-001', 3, 13079, '2026-04-06 23:19:15', 'pending');

-- --------------------------------------------------------

--
-- Table structure for table `produk`
--

CREATE TABLE `produk` (
  `kode_produk` varchar(50) NOT NULL,
  `nama_produk` varchar(150) NOT NULL,
  `tipe_kendaraan` varchar(100) NOT NULL,
  `kategori_id` varchar(50) NOT NULL,
  `harga` bigint(20) NOT NULL COMMENT 'Nilai hasil migrasi disimpan dalam satuan sen dari harga SQLite.',
  `stok` int(11) NOT NULL,
  `gambar` varchar(255) DEFAULT NULL,
  `brand_id` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `produk`
--

INSERT INTO `produk` (`kode_produk`, `nama_produk`, `tipe_kendaraan`, `kategori_id`, `harga`, `stok`, `gambar`, `brand_id`) VALUES
('AIR-PNL-021', 'Panel Air Filter', 'Toyota Rush', '2', 1825, 30, 'theme/img/products/panel-air-filter.jpg', 'denso'),
('BPS-CER-001', 'Ceramic Brake Pad Set', 'Toyota Avanza', '1', 4500, 40, 'theme/img/products/ceramic-brake-pad-set.jpg', 'bosch'),
('BTC-12V-009', 'Battery Terminal Clamp', 'Toyota Avanza', '3', 1450, 30, 'theme/img/products/battery-terminal-clamp.jpg', 'bosch'),
('OFL-SPN-018', 'Spin-On Oil Filter', 'Toyota Avanza', '2', 1290, 58, 'theme/img/products/spin-on-oil-filter.jpg', 'toyota_genuine_parts'),
('ROT-VNT-014', 'Ventilated Brake Disc Rotor', 'Toyota Avanza', '1', 7250, 10, 'theme/img/products/ventilated-brake-disc-rotor.jpg', 'advics'),
('SPK-IRD-006', 'Iridium Spark Plug', 'Honda Brio', '3', 890, 80, 'theme/img/products/iridium-spark-plug.jpg', 'ngk');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` varchar(50) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','user') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `created_at`) VALUES
('1', 'admin', 'admin@sparesoko.test', '$2y$12$b1LRQJvE4rNd9.aD.SmTS.HgfIwFvl7mnwpYMe339ZL0Nyl3LFk.O', 'admin', '2026-04-06 23:19:14'),
('2', 'demo', 'demo@sparesoko.test', '$2y$12$5bTTTs/O59ZyZuDBojmkjO48BeRPtVoflXHKjVthhPxKtuJyRdwcC', 'user', '2026-04-06 23:19:15');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `brand`
--
ALTER TABLE `brand`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `kategori`
--
ALTER TABLE `kategori`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pembelian`
--
ALTER TABLE `pembelian`
  ADD PRIMARY KEY (`id_pembelian`),
  ADD KEY `fk_pembelian_user` (`user_id`),
  ADD KEY `fk_pembelian_produk` (`kode_produk`);

--
-- Indexes for table `produk`
--
ALTER TABLE `produk`
  ADD PRIMARY KEY (`kode_produk`),
  ADD KEY `fk_produk_kategori` (`kategori_id`),
  ADD KEY `fk_produk_brand` (`brand_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `pembelian`
--
ALTER TABLE `pembelian`
  ADD CONSTRAINT `fk_pembelian_produk` FOREIGN KEY (`kode_produk`) REFERENCES `produk` (`kode_produk`),
  ADD CONSTRAINT `fk_pembelian_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `produk`
--
ALTER TABLE `produk`
  ADD CONSTRAINT `fk_produk_brand` FOREIGN KEY (`brand_id`) REFERENCES `brand` (`id`),
  ADD CONSTRAINT `fk_produk_kategori` FOREIGN KEY (`kategori_id`) REFERENCES `kategori` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
