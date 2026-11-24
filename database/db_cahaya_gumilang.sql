-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 11, 2026 at 02:13 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_cahaya_gumilang`
--

-- --------------------------------------------------------

--
-- Table structure for table `detail_pesanan`
--

CREATE TABLE `detail_pesanan` (
  `id_detail` int(11) NOT NULL,
  `id_pesanan` int(11) NOT NULL,
  `id_layanan` int(11) NOT NULL,
  `nama_barang` varchar(150) NOT NULL,
  `spesifikasi` varchar(255) DEFAULT NULL,
  `jumlah` decimal(10,2) NOT NULL,
  `harga_satuan` decimal(12,2) NOT NULL,
  `subtotal` decimal(14,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `detail_pesanan`
--

INSERT INTO `detail_pesanan` (`id_detail`, `id_pesanan`, `id_layanan`, `nama_barang`, `spesifikasi`, `jumlah`, `harga_satuan`, `subtotal`) VALUES
(1, 1, 2, 'Spakbor motor', 'Besi, finishing mengkilap', 20.00, 12000.00, 240000.00),
(2, 2, 5, 'Cincin / liontin', 'Kuningan', 6.00, 35000.00, 210000.00),
(3, 3, 1, 'Bracket besi', 'Tebal 2 mm', 30.00, 7500.00, 225000.00),
(4, 3, 3, 'Baut & mur', 'Campuran', 10.00, 15000.00, 150000.00),
(5, 4, 4, 'Ornamen kuningan', 'Dekorasi', 12.00, 9000.00, 108000.00),
(6, 5, 1, 'kancing', '', 200.00, 0.00, 0.00),
(7, 6, 5, 'kancing', '', 1.00, 0.00, 0.00),
(8, 7, 5, 'kancing', '', 5.00, 35000.00, 175000.00);

-- --------------------------------------------------------

--
-- Table structure for table `layanan`
--

CREATE TABLE `layanan` (
  `id_layanan` int(11) NOT NULL,
  `nama_layanan` varchar(100) NOT NULL,
  `satuan` enum('pcs','kg','dm2') NOT NULL DEFAULT 'kg',
  `harga_satuan` decimal(12,2) NOT NULL DEFAULT 0.00,
  `keterangan` varchar(255) DEFAULT NULL,
  `aktif` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `layanan`
--

INSERT INTO `layanan` (`id_layanan`, `nama_layanan`, `satuan`, `harga_satuan`, `keterangan`, `aktif`) VALUES
(1, 'Pelapisan Nikel', 'kg', 40000.00, 'Nickel plating', 1),
(2, 'Pelapisan Krom', 'kg', 65000.00, 'Chrome plating dekoratif', 1),
(3, 'Pelapisan Zinc', 'kg', 15000.00, 'Galvanis / zinc plating', 1),
(4, 'Pelapisan Tembaga', 'kg', 35000.00, 'Copper plating', 1),
(5, 'Pelapisan Emas', 'kg', 350000.00, 'Gold plating', 1);

-- --------------------------------------------------------

--
-- Table structure for table `log_status`
--

CREATE TABLE `log_status` (
  `id_log` int(11) NOT NULL,
  `id_pesanan` int(11) NOT NULL,
  `status` enum('menunggu','diterima','pembersihan','perendaman_hcl','pembilasan','pelapisan','selesai','diambil') NOT NULL,
  `id_user` int(11) NOT NULL,
  `catatan` varchar(255) DEFAULT NULL,
  `waktu` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `log_status`
--

INSERT INTO `log_status` (`id_log`, `id_pesanan`, `status`, `id_user`, `catatan`, `waktu`) VALUES
(1, 1, 'diterima', 2, 'Barang diterima', '2026-09-28 02:00:00'),
(2, 1, 'pembersihan', 2, NULL, '2026-09-28 03:00:00'),
(3, 1, 'perendaman_hcl', 2, NULL, '2026-09-29 01:30:00'),
(4, 1, 'pembilasan', 2, NULL, '2026-09-29 04:00:00'),
(5, 1, 'pelapisan', 2, NULL, '2026-09-30 02:00:00'),
(6, 1, 'selesai', 2, NULL, '2026-10-01 08:00:00'),
(7, 1, 'diambil', 2, 'Diambil pelanggan', '2026-10-02 03:00:00'),
(8, 2, 'diterima', 2, NULL, '2026-10-01 02:30:00'),
(9, 2, 'pembersihan', 2, NULL, '2026-10-02 02:00:00'),
(10, 2, 'perendaman_hcl', 2, NULL, '2026-10-02 06:00:00'),
(11, 2, 'pembilasan', 2, NULL, '2026-10-03 02:00:00'),
(12, 2, 'pelapisan', 2, NULL, '2026-10-04 02:00:00'),
(13, 2, 'selesai', 2, 'Siap diambil', '2026-10-05 07:00:00'),
(14, 3, 'diterima', 2, NULL, '2026-10-03 03:00:00'),
(15, 3, 'pembersihan', 2, NULL, '2026-10-04 02:00:00'),
(16, 3, 'perendaman_hcl', 2, NULL, '2026-10-05 02:00:00'),
(17, 3, 'pembilasan', 2, NULL, '2026-10-06 02:00:00'),
(18, 3, 'pelapisan', 2, NULL, '2026-10-07 01:00:00'),
(19, 4, 'menunggu', 3, 'Pesanan online dibuat pelanggan', '2026-10-06 04:00:00'),
(20, 5, 'menunggu', 4, 'Pesanan online dibuat pelanggan', '2026-11-11 00:37:48'),
(21, 6, 'menunggu', 4, 'Pesanan online dibuat pelanggan', '2026-11-11 00:38:11'),
(22, 7, 'menunggu', 4, 'Pesanan online dibuat pelanggan', '2026-11-11 01:00:25'),
(23, 7, 'menunggu', 2, 'Biaya diperbarui setelah pengecekan: total Rp 175.000', '2026-11-11 01:01:13');

-- --------------------------------------------------------

--
-- Table structure for table `pelanggan`
--

CREATE TABLE `pelanggan` (
  `id_pelanggan` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `id_user` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pelanggan`
--

INSERT INTO `pelanggan` (`id_pelanggan`, `nama`, `no_hp`, `alamat`, `id_user`, `created_at`) VALUES
(1, 'Bengkel Maju Jaya', '081234567801', 'Cimahi', NULL, '2026-11-11 00:28:25'),
(2, 'CV Logam Prima', '081234567802', 'Bandung', NULL, '2026-11-11 00:28:25'),
(3, 'Budi Santoso', '081234567803', 'Padalarang', 3, '2026-11-11 00:28:25'),
(4, 'Toko Perhiasan Sari', '081234567804', 'Cimahi', NULL, '2026-11-11 00:28:25'),
(5, '123', '123', '123', 4, '2026-11-11 00:36:16');

-- --------------------------------------------------------

--
-- Table structure for table `pembayaran`
--

CREATE TABLE `pembayaran` (
  `id_pembayaran` int(11) NOT NULL,
  `id_pesanan` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `tgl_bayar` date NOT NULL,
  `jumlah` decimal(14,2) NOT NULL,
  `metode` enum('tunai','transfer','qris') NOT NULL DEFAULT 'tunai',
  `keterangan` varchar(255) DEFAULT NULL,
  `waktu` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pembayaran`
--

INSERT INTO `pembayaran` (`id_pembayaran`, `id_pesanan`, `id_user`, `tgl_bayar`, `jumlah`, `metode`, `keterangan`, `waktu`) VALUES
(1, 1, 2, '2026-09-28', 240000.00, 'tunai', 'Pelunasan', '2026-09-28 12:00:00'),
(2, 2, 2, '2026-10-01', 210000.00, 'transfer', 'Pelunasan', '2026-10-01 12:00:00'),
(3, 3, 2, '2026-10-03', 150000.00, 'tunai', 'DP', '2026-10-03 12:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `pengeluaran`
--

CREATE TABLE `pengeluaran` (
  `id_pengeluaran` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `kategori` enum('bahan_kimia','listrik','gaji','peralatan','operasional','lainnya') NOT NULL DEFAULT 'lainnya',
  `keterangan` varchar(255) NOT NULL,
  `jumlah` decimal(14,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pengeluaran`
--

INSERT INTO `pengeluaran` (`id_pengeluaran`, `id_user`, `tanggal`, `kategori`, `keterangan`, `jumlah`) VALUES
(1, 1, '2026-09-25', 'bahan_kimia', 'Pembelian larutan HCl dan cairan pembersih', 350000.00),
(2, 1, '2026-10-01', 'listrik', 'Tagihan listrik bulan ini', 600000.00),
(3, 1, '2026-10-02', 'bahan_kimia', 'Anoda nikel dan larutan pelapis', 480000.00);

-- --------------------------------------------------------

--
-- Table structure for table `pesanan`
--

CREATE TABLE `pesanan` (
  `id_pesanan` int(11) NOT NULL,
  `no_pesanan` varchar(20) NOT NULL,
  `id_pelanggan` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `sumber` enum('offline','online') NOT NULL DEFAULT 'offline',
  `tgl_masuk` date NOT NULL,
  `tgl_estimasi` date DEFAULT NULL,
  `tgl_selesai` date DEFAULT NULL,
  `tgl_diambil` date DEFAULT NULL,
  `total_biaya` decimal(14,2) NOT NULL DEFAULT 0.00,
  `status` enum('menunggu','diterima','pembersihan','perendaman_hcl','pembilasan','pelapisan','selesai','diambil') NOT NULL DEFAULT 'diterima',
  `status_bayar` enum('belum','dp','lunas') NOT NULL DEFAULT 'belum',
  `metode_bayar` enum('tunai','transfer','qris') DEFAULT NULL,
  `catatan` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pesanan`
--

INSERT INTO `pesanan` (`id_pesanan`, `no_pesanan`, `id_pelanggan`, `id_user`, `sumber`, `tgl_masuk`, `tgl_estimasi`, `tgl_selesai`, `tgl_diambil`, `total_biaya`, `status`, `status_bayar`, `metode_bayar`, `catatan`, `created_at`) VALUES
(1, 'PO-260928-001', 1, 2, 'offline', '2026-09-28', '2026-10-02', '2026-10-01', '2026-10-02', 240000.00, 'diambil', 'lunas', NULL, 'Spakbor & handle', '2026-11-11 00:28:25'),
(2, 'PO-261001-001', 4, 2, 'offline', '2026-10-01', '2026-10-05', '2026-10-05', NULL, 210000.00, 'selesai', 'lunas', NULL, 'Cincin & liontin', '2026-11-11 00:28:25'),
(3, 'PO-261003-001', 2, 2, 'offline', '2026-10-03', '2026-10-08', NULL, NULL, 375000.00, 'pelapisan', 'dp', NULL, 'Baut & bracket', '2026-11-11 00:28:25'),
(4, 'PO-261006-001', 3, 3, 'online', '2026-10-06', NULL, NULL, NULL, 108000.00, 'menunggu', 'belum', NULL, 'Dipesan lewat website', '2026-11-11 00:28:25'),
(5, 'PO-261111-001', 5, 4, 'online', '2026-11-11', NULL, NULL, NULL, 0.00, 'menunggu', 'belum', 'transfer', '', '2026-11-11 00:37:48'),
(6, 'PO-261111-002', 5, 4, 'online', '2026-11-11', NULL, NULL, NULL, 0.00, 'menunggu', 'belum', 'transfer', '', '2026-11-11 00:38:11'),
(7, 'PO-261111-003', 5, 4, 'online', '2026-11-11', NULL, NULL, NULL, 175000.00, 'menunggu', 'belum', 'qris', '', '2026-11-11 01:00:25');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id_user` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('pemilik','admin','pelanggan') NOT NULL DEFAULT 'admin',
  `aktif` tinyint(1) NOT NULL DEFAULT 1,
  `notif_dilihat` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id_user`, `nama`, `username`, `password`, `role`, `aktif`, `notif_dilihat`, `created_at`) VALUES
(1, 'Agus Sofyan', 'pemilik', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'pemilik', 1, '2026-11-11 07:28:25', '2026-11-11 00:28:25'),
(2, 'Admin Pegawai', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1, '2026-11-11 08:00:49', '2026-11-11 00:28:25'),
(3, 'Budi Santoso', 'budi', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'pelanggan', 1, '2026-11-11 07:28:25', '2026-11-11 00:28:25'),
(4, '123', '1234', '$2y$10$vqlkbOb/ep9GOMkWOPLvIOHNH16pcnIbN4gmAGhxmtEOlPbP5hnaq', 'pelanggan', 1, '2026-11-11 08:01:29', '2026-11-11 00:36:16');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `detail_pesanan`
--
ALTER TABLE `detail_pesanan`
  ADD PRIMARY KEY (`id_detail`),
  ADD KEY `fk_detail_pesanan` (`id_pesanan`),
  ADD KEY `fk_detail_layanan` (`id_layanan`);

--
-- Indexes for table `layanan`
--
ALTER TABLE `layanan`
  ADD PRIMARY KEY (`id_layanan`);

--
-- Indexes for table `log_status`
--
ALTER TABLE `log_status`
  ADD PRIMARY KEY (`id_log`),
  ADD KEY `fk_log_pesanan` (`id_pesanan`),
  ADD KEY `fk_log_user` (`id_user`);

--
-- Indexes for table `pelanggan`
--
ALTER TABLE `pelanggan`
  ADD PRIMARY KEY (`id_pelanggan`),
  ADD UNIQUE KEY `id_user` (`id_user`);

--
-- Indexes for table `pembayaran`
--
ALTER TABLE `pembayaran`
  ADD PRIMARY KEY (`id_pembayaran`),
  ADD KEY `fk_bayar_pesanan` (`id_pesanan`),
  ADD KEY `fk_bayar_user` (`id_user`);

--
-- Indexes for table `pengeluaran`
--
ALTER TABLE `pengeluaran`
  ADD PRIMARY KEY (`id_pengeluaran`),
  ADD KEY `fk_keluar_user` (`id_user`);

--
-- Indexes for table `pesanan`
--
ALTER TABLE `pesanan`
  ADD PRIMARY KEY (`id_pesanan`),
  ADD UNIQUE KEY `no_pesanan` (`no_pesanan`),
  ADD KEY `fk_pesanan_pelanggan` (`id_pelanggan`),
  ADD KEY `fk_pesanan_user` (`id_user`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `detail_pesanan`
--
ALTER TABLE `detail_pesanan`
  MODIFY `id_detail` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `layanan`
--
ALTER TABLE `layanan`
  MODIFY `id_layanan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `log_status`
--
ALTER TABLE `log_status`
  MODIFY `id_log` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `pelanggan`
--
ALTER TABLE `pelanggan`
  MODIFY `id_pelanggan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `pembayaran`
--
ALTER TABLE `pembayaran`
  MODIFY `id_pembayaran` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `pengeluaran`
--
ALTER TABLE `pengeluaran`
  MODIFY `id_pengeluaran` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `pesanan`
--
ALTER TABLE `pesanan`
  MODIFY `id_pesanan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `detail_pesanan`
--
ALTER TABLE `detail_pesanan`
  ADD CONSTRAINT `fk_detail_layanan` FOREIGN KEY (`id_layanan`) REFERENCES `layanan` (`id_layanan`),
  ADD CONSTRAINT `fk_detail_pesanan` FOREIGN KEY (`id_pesanan`) REFERENCES `pesanan` (`id_pesanan`) ON DELETE CASCADE;

--
-- Constraints for table `log_status`
--
ALTER TABLE `log_status`
  ADD CONSTRAINT `fk_log_pesanan` FOREIGN KEY (`id_pesanan`) REFERENCES `pesanan` (`id_pesanan`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_log_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`);

--
-- Constraints for table `pelanggan`
--
ALTER TABLE `pelanggan`
  ADD CONSTRAINT `fk_pelanggan_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE SET NULL;

--
-- Constraints for table `pembayaran`
--
ALTER TABLE `pembayaran`
  ADD CONSTRAINT `fk_bayar_pesanan` FOREIGN KEY (`id_pesanan`) REFERENCES `pesanan` (`id_pesanan`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_bayar_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`);

--
-- Constraints for table `pengeluaran`
--
ALTER TABLE `pengeluaran`
  ADD CONSTRAINT `fk_keluar_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`);

--
-- Constraints for table `pesanan`
--
ALTER TABLE `pesanan`
  ADD CONSTRAINT `fk_pesanan_pelanggan` FOREIGN KEY (`id_pelanggan`) REFERENCES `pelanggan` (`id_pelanggan`),
  ADD CONSTRAINT `fk_pesanan_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
