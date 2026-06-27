-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 04, 2026 at 04:14 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `tiketkapal`
--

-- --------------------------------------------------------

--
-- Table structure for table `harga`
--

CREATE TABLE `harga` (
  `id` int(11) NOT NULL,
  `asal_id` int(10) UNSIGNED DEFAULT NULL,
  `tujuan_id` int(10) UNSIGNED DEFAULT NULL,
  `layanan` varchar(50) DEFAULT NULL,
  `harga` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `harga`
--

INSERT INTO `harga` (`id`, `asal_id`, `tujuan_id`, `layanan`, `harga`) VALUES
(7, 8, 9, 'Reguler', 22000),
(8, 8, 9, 'Express', 84000),
(9, 9, 8, 'Reguler', 22000),
(10, 9, 8, 'Express', 84000),
(11, 11, 10, 'Reguler', 10000),
(12, 11, 10, 'Express', 20000),
(13, 10, 11, 'Reguler', 10000),
(14, 10, 11, 'Express', 20000);

-- --------------------------------------------------------

--
-- Table structure for table `harga_kendaraan`
--

CREATE TABLE `harga_kendaraan` (
  `id` int(10) UNSIGNED NOT NULL,
  `asal_id` int(10) UNSIGNED NOT NULL,
  `tujuan_id` int(10) UNSIGNED NOT NULL,
  `golongan` varchar(20) NOT NULL,
  `harga_reguler` int(11) NOT NULL DEFAULT 0,
  `harga_express` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `harga_kendaraan`
--

INSERT INTO `harga_kendaraan` (`id`, `asal_id`, `tujuan_id`, `golongan`, `harga_reguler`, `harga_express`) VALUES
(61, 8, 9, 'gol_1', 5000, 10000),
(62, 9, 8, 'gol_1', 5000, 10000),
(63, 8, 9, 'gol_2', 40000, 45000),
(64, 9, 8, 'gol_2', 40000, 45000),
(65, 8, 9, 'gol_3', 111000, 120000),
(66, 9, 8, 'gol_3', 111000, 120000),
(67, 8, 9, 'gol_4a', 459000, 665000),
(68, 9, 8, 'gol_4a', 459000, 665000),
(69, 8, 9, 'gol_4b', 425000, 430000),
(70, 9, 8, 'gol_4b', 425000, 430000),
(71, 8, 9, 'gol_5a', 941000, 1141000),
(72, 9, 8, 'gol_5a', 941000, 1141000),
(73, 8, 9, 'gol_5b', 813000, 820000),
(74, 9, 8, 'gol_5b', 813000, 820000),
(75, 8, 9, 'gol_6a', 1572000, 1931000),
(76, 9, 8, 'gol_6a', 1572000, 1931000),
(77, 8, 9, 'gol_6b', 1263000, 1282000),
(78, 9, 8, 'gol_6b', 1263000, 1282000),
(79, 8, 9, 'gol_7', 1838000, 1891000),
(80, 9, 8, 'gol_7', 1838000, 1891000),
(81, 8, 9, 'gol_8', 2432000, 2536000),
(82, 9, 8, 'gol_8', 2432000, 2536000),
(83, 8, 9, 'gol_9', 3733000, 3914000),
(84, 9, 8, 'gol_9', 3733000, 3914000),
(85, 11, 10, 'gol_1', 5000, 15000),
(86, 10, 11, 'gol_1', 5000, 15000),
(87, 11, 10, 'gol_2', 20000, 40000),
(88, 10, 11, 'gol_2', 20000, 40000),
(89, 11, 10, 'gol_3', 35000, 70000),
(90, 10, 11, 'gol_3', 35000, 70000),
(91, 11, 10, 'gol_4a', 203000, 315000),
(92, 10, 11, 'gol_4a', 203000, 315000),
(93, 11, 10, 'gol_4b', 172000, 190000),
(94, 10, 11, 'gol_4b', 172000, 190000),
(95, 11, 10, 'gol_5a', 410000, 610000),
(96, 10, 11, 'gol_5a', 410000, 610000),
(97, 11, 10, 'gol_5b', 299000, 233000),
(98, 10, 11, 'gol_5b', 299000, 233000),
(99, 11, 10, 'gol_6a', 627000, 915000),
(100, 10, 11, 'gol_6a', 627000, 915000),
(101, 11, 10, 'gol_6b', 501000, 523000),
(102, 10, 11, 'gol_6b', 501000, 523000),
(103, 11, 10, 'gol_7', 620000, 713000),
(104, 10, 11, 'gol_7', 620000, 713000),
(105, 11, 10, 'gol_8', 878000, 104000),
(106, 10, 11, 'gol_8', 878000, 104000),
(107, 11, 10, 'gol_9', 1219000, 1440000),
(108, 10, 11, 'gol_9', 1219000, 1440000);

-- --------------------------------------------------------

--
-- Table structure for table `pelabuhan`
--

CREATE TABLE `pelabuhan` (
  `id` int(10) UNSIGNED NOT NULL,
  `nama_pelabuhan` varchar(150) DEFAULT NULL,
  `lokasi` varchar(150) DEFAULT NULL,
  `pair_id` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pelabuhan`
--

INSERT INTO `pelabuhan` (`id`, `nama_pelabuhan`, `lokasi`, `pair_id`) VALUES
(8, 'Bakauheni', 'Lampung', 9),
(9, 'Merak', 'Banten', 8),
(10, 'Ketapang', 'Jawa Timur', 11),
(11, 'Gilimanuk', 'Bali', 10);

-- --------------------------------------------------------

--
-- Table structure for table `penumpang_detail`
--

CREATE TABLE `penumpang_detail` (
  `id` int(11) NOT NULL,
  `ticket_id` int(11) DEFAULT NULL,
  `kategori` varchar(20) DEFAULT NULL,
  `titel` varchar(20) DEFAULT NULL,
  `nama_lengkap` varchar(100) DEFAULT NULL,
  `jenis_id` varchar(20) DEFAULT NULL,
  `nomor_id` varchar(30) DEFAULT NULL,
  `usia` tinyint(4) DEFAULT NULL,
  `kota_asal` varchar(100) DEFAULT NULL,
  `jumlah` int(11) DEFAULT NULL,
  `no_tlp` int(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `penumpang_detail`
--

INSERT INTO `penumpang_detail` (`id`, `ticket_id`, `kategori`, `titel`, `nama_lengkap`, `jenis_id`, `nomor_id`, `usia`, `kota_asal`, `jumlah`, `no_tlp`) VALUES
(24, 13, 'dewasa', NULL, NULL, NULL, NULL, NULL, NULL, 1, 0),
(25, 14, 'dewasa', 'Nyonya', 'WULAN', 'KTP', '3275128127391283701', 22, 'Bekasi', 1, 0),
(26, 15, 'dewasa', 'Nona', 'WULAN', 'KTP', '3275128127391283701', 22, 'Bekasi', 1, 0),
(27, 16, 'dewasa', 'Tuan', 'WULAN', 'KTP', '3298172391273109237', 20, 'Bekasi', 1, 0),
(28, 17, 'dewasa', 'Tuan', 'TRIWULANDARI -', 'KTP', '3298172391273109237', 22, 'Bekasi', 1, 0),
(29, 18, 'dewasa', 'Tuan', 'WULAN', 'KTP', '3275128127391283701', 22, 'Bekasi', 1, 0),
(30, 18, 'bayi', 'Ananda', 'YAYA', '-', '-', 10, '-', 1, 0),
(31, 19, 'dewasa', 'Tuan', 'WULAN', 'KTP', '3275128127391283701', 22, 'Bekasi', 1, 0),
(32, 19, 'anak', 'Ananda', 'TAFA', 'Akta', '32312762391283', 3, 'Bekasi', 1, 0),
(33, 19, 'bayi', 'Ananda', 'BOBOIBOY', '-', '-', 1, '-', 1, 0),
(34, 20, 'dewasa', 'Nona', 'WULAN', 'KTP', '3275128127391283701', 22, 'Bekasi', 1, 0),
(35, 20, 'anak', 'Ananda', 'TAFA', 'Akta', '323234241112', 3, 'Bekasi', 1, 0),
(36, 20, 'bayi', 'Ananda', 'BOBOIBOY', '-', '-', 10, '-', 1, 0),
(37, 21, 'dewasa', 'Nona', 'WULAN', 'KTP', '3275128127391283701', 19, 'Bekasi', 1, 0),
(38, 21, 'dewasa', 'Tuan', 'ARYA', 'KTP', '323123791823', 21, 'Bekasi', 2, 0),
(39, 21, 'anak', 'Ananda', 'TAFA', 'Akta', '323234241112', 3, 'Bekasi', 1, 0),
(40, 22, 'dewasa', 'Nyonya', 'JOHNNY', 'KTP', '3275123456788909', 20, 'Bekasi', 1, 0),
(41, 23, 'dewasa', 'Tuan', 'LAILYZA INTAN KENANGA', 'KTP', '327500004137187281', 10, 'Bekasi', 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `tickets`
--

CREATE TABLE `tickets` (
  `id_ticket` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `nama_pemesan` varchar(100) DEFAULT NULL,
  `hp_pemesan` varchar(25) DEFAULT NULL,
  `email_pemesan` varchar(120) DEFAULT NULL,
  `kode_booking` varchar(20) DEFAULT NULL,
  `status` enum('BELUM DIGUNAKAN','DIGUNAKAN') DEFAULT 'BELUM DIGUNAKAN',
  `tanggal` date DEFAULT NULL,
  `asal_id` int(10) UNSIGNED NOT NULL,
  `tujuan_id` int(10) UNSIGNED NOT NULL,
  `jam` time DEFAULT NULL,
  `layanan` varchar(50) DEFAULT NULL,
  `jenis_pengguna` varchar(50) DEFAULT NULL,
  `kendaraan` varchar(50) DEFAULT NULL,
  `golongan` varchar(10) DEFAULT NULL,
  `plat` varchar(20) DEFAULT NULL,
  `total_harga` int(11) DEFAULT NULL,
  `total_penumpang` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tickets`
--

INSERT INTO `tickets` (`id_ticket`, `user_id`, `nama_pemesan`, `hp_pemesan`, `email_pemesan`, `kode_booking`, `status`, `tanggal`, `asal_id`, `tujuan_id`, `jam`, `layanan`, `jenis_pengguna`, `kendaraan`, `golongan`, `plat`, `total_harga`, `total_penumpang`) VALUES
(1, 1, NULL, NULL, NULL, 'TKT6C12F7A7', 'BELUM DIGUNAKAN', '2026-05-20', 9, 8, '14:56:00', 'Reguler', 'kendaraan', 'gol_4a', NULL, 'B YMH A', 250000, NULL),
(2, 1, NULL, NULL, NULL, 'TKTF5108555', 'BELUM DIGUNAKAN', '2026-05-27', 9, 8, '09:04:00', 'Reguler', 'kendaraan', 'gol_4a', NULL, 'B 1234 111', 750000, NULL),
(3, 1, NULL, NULL, NULL, 'TKT7474B536', 'DIGUNAKAN', '2026-05-29', 9, 8, '16:10:00', 'Express', 'kendaraan', 'gol_4a', NULL, 'B 1234 111', 225000, NULL),
(4, 4, NULL, NULL, NULL, 'TKT0B1F797E', 'DIGUNAKAN', '2026-05-14', 9, 8, '16:00:00', 'Reguler', 'kendaraan', 'gol_9', NULL, 'B 566 ASZ', 540000, NULL),
(5, 4, NULL, NULL, NULL, 'TKT495A0A9B', 'DIGUNAKAN', '2026-05-30', 9, 8, '07:00:00', 'Express', 'kendaraan', 'gol_4a', NULL, 'B 566 ASZ', 170000, NULL),
(6, 4, NULL, NULL, NULL, 'TKT8C97E643', 'DIGUNAKAN', '2026-05-16', 9, 8, '19:00:00', 'Express', 'penumpang', '', NULL, '', 168000, NULL),
(7, 1, NULL, NULL, NULL, 'TKTDD0ED702', 'BELUM DIGUNAKAN', '2026-05-21', 9, 8, '11:00:00', 'Express', 'kendaraan', 'gol_4a', 'gol_4a', 'B 3033 BGK', 80000, 5),
(8, 5, NULL, NULL, NULL, 'TKTC456336F', 'DIGUNAKAN', '2026-05-27', 9, 8, '13:00:00', 'Reguler', 'penumpang', '', '', '', 44000, 2),
(9, 5, NULL, NULL, NULL, 'TKT330D5A8B', 'BELUM DIGUNAKAN', '2026-05-27', 9, 8, '13:00:00', 'Reguler', 'penumpang', '', '', '', 44000, 2),
(10, 5, NULL, NULL, NULL, 'TKTAD62A4AE', 'BELUM DIGUNAKAN', '2026-05-27', 9, 8, '19:00:00', 'Reguler', 'penumpang', '', '', '', 66000, 3),
(11, 5, NULL, NULL, NULL, 'TKTE6F94072', 'BELUM DIGUNAKAN', '2026-05-27', 9, 8, '15:00:00', 'Reguler', 'penumpang', '', '', '', 22000, 1),
(12, 5, NULL, NULL, NULL, 'TKTA7F68E7C', 'BELUM DIGUNAKAN', '2026-05-28', 9, 8, '18:00:00', 'Reguler', 'penumpang', '', '', '', 22000, 1),
(13, 5, NULL, NULL, NULL, 'TKT665CC3CE', 'BELUM DIGUNAKAN', '2026-05-28', 9, 8, '18:00:00', 'Reguler', 'penumpang', '', '', '', 22000, 1),
(14, 5, NULL, NULL, NULL, 'TKTCB0BFBE0', 'BELUM DIGUNAKAN', '2026-05-28', 9, 8, '18:00:00', 'reguler', 'penumpang', '', '', '', 22000, 1),
(15, 5, 'WULAN', '0897654321345', 'antafa18@gmail.com', 'TKT17E1ECBF', 'DIGUNAKAN', '2026-05-27', 9, 8, '14:00:00', 'reguler', 'penumpang', '', '', '', 10000, 1),
(16, 5, 'WULAN', '08976578086', 'antafa@gmail.com', 'TKT58624564', 'BELUM DIGUNAKAN', '2026-05-27', 9, 8, '20:00:00', 'reguler', 'penumpang', '', '', '', 22000, 1),
(17, 5, 'WULAN', '08976578086', 'triwulandari-m@ubs.ac.id', 'TKT94C5A04C', 'DIGUNAKAN', '2026-05-27', 9, 8, '22:00:00', 'express', 'penumpang', '', '', '', 84000, 1),
(18, 5, 'WULAN', '0897654321345', 'antafa18@gmail.com', 'TKT36C1E3F6', 'BELUM DIGUNAKAN', '2026-05-30', 9, 8, '20:00:00', 'reguler', 'penumpang', '', '', '', 22000, 2),
(19, 5, 'WULAN', '0897654321345', 'antafa18@gmail.com', 'TKTAEAAA8E2', 'DIGUNAKAN', '2026-05-30', 9, 8, '20:00:00', 'reguler', 'penumpang', '', '', '', 44000, 3),
(20, 3, 'WULAN', '0897654321345', 'antafa18@gmail.com', 'TKTB0C0C933', 'DIGUNAKAN', '2026-05-30', 9, 8, '21:00:00', 'reguler', 'penumpang', '', '', '', 44000, 3),
(21, 3, 'WULAN', '0897654321345', 'antafa18@gmail.com', 'TKT7593640F', 'DIGUNAKAN', '2026-05-30', 9, 8, '21:00:00', 'reguler', 'penumpang', '', '', '', 30000, 3),
(22, 4, 'LAILYZA', '082515151515', 'kenanga@gmail.com', 'TKT094CCA81', 'DIGUNAKAN', '2026-06-04', 11, 10, '07:00:00', 'reguler', 'penumpang', '', '', '', 10000, 1),
(23, 4, 'INTAN', '081327483579', 'lailyzaintankenanga@gmail.com', 'TKT74EDF3D0', 'DIGUNAKAN', '2026-06-04', 11, 10, '20:00:00', 'express', 'kendaraan', 'gol_3', 'gol_3', 'B 7777 D', 20000, 1);

-- --------------------------------------------------------

--
-- Table structure for table `boarding_scans`
--

CREATE TABLE `boarding_scans` (
  `id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `pax_no` int(11) NOT NULL,
  `lane` enum('A','B') NOT NULL,
  `scanned_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` enum('user','admin','super_admin') DEFAULT 'user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `nama`, `email`, `password`, `role`) VALUES
(1, 'nana', 'nana@gmail.com', '$2y$10$itEPh3corGqFPaRLoxnKCuVoGLiCckS5PSfpSGivWTl5WXLXQcRJq', 'user'),
(2, 'Super Admin', 'superadmin@gmail.com', '$2y$10$9B4xyrtCleWRJ2wHDizKheIEik8SC7nIH4XT2t2PQyh.QX5Go5rYG', 'super_admin'),
(3, 'Admin nono', 'adminnono@gmail.com', '$2y$10$d/e73j/4ThOqexam3UVvtuqdu1oE7nz3qJX0TqXeGXV.uy0lpAZgi', 'admin'),
(4, 'kenanga', 'kenanga@gmail.com', '$2y$10$ljTZZiAExY4dfEe0dVEx2.j3Vi0Wim6Wj4Eo3BwZ0ftqa6vwBIj5i', 'user'),
(5, 'wulan', 'antafa@gmail.com', '$2y$10$siJLdHDninOM1UMUNU0gk.V0DNHobgzOP4DAUxCVPMkySW/N2WBCC', 'user');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `harga`
--
ALTER TABLE `harga`
  ADD PRIMARY KEY (`id`),
  ADD KEY `asal_id` (`asal_id`),
  ADD KEY `tujuan_id` (`tujuan_id`);

--
-- Indexes for table `harga_kendaraan`
--
ALTER TABLE `harga_kendaraan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `rute_golongan` (`asal_id`,`tujuan_id`,`golongan`),
  ADD KEY `fk_harga_tujuan` (`tujuan_id`);

--
-- Indexes for table `pelabuhan`
--
ALTER TABLE `pelabuhan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_pelabuhan_pair` (`pair_id`);

--
-- Indexes for table `penumpang_detail`
--
ALTER TABLE `penumpang_detail`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ticket_id` (`ticket_id`);

--
-- Indexes for table `tickets`
--
ALTER TABLE `tickets`
  ADD PRIMARY KEY (`id_ticket`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `fk_tickets_asal` (`asal_id`),
  ADD KEY `fk_tickets_tujuan` (`tujuan_id`);

--
-- Indexes for table `boarding_scans`
--
ALTER TABLE `boarding_scans`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_scan` (`ticket_id`,`pax_no`,`lane`),
  ADD KEY `fk_boarding_ticket` (`ticket_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `harga`
--
ALTER TABLE `harga`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `harga_kendaraan`
--
ALTER TABLE `harga_kendaraan`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=133;

--
-- AUTO_INCREMENT for table `pelabuhan`
--
ALTER TABLE `pelabuhan`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `penumpang_detail`
--
ALTER TABLE `penumpang_detail`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `tickets`
--
ALTER TABLE `tickets`
  MODIFY `id_ticket` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `boarding_scans`
--
ALTER TABLE `boarding_scans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `harga`
--
ALTER TABLE `harga`
  ADD CONSTRAINT `harga_ibfk_1` FOREIGN KEY (`asal_id`) REFERENCES `pelabuhan` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `harga_ibfk_2` FOREIGN KEY (`tujuan_id`) REFERENCES `pelabuhan` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `harga_kendaraan`
--
ALTER TABLE `harga_kendaraan`
  ADD CONSTRAINT `fk_harga_asal` FOREIGN KEY (`asal_id`) REFERENCES `pelabuhan` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_harga_tujuan` FOREIGN KEY (`tujuan_id`) REFERENCES `pelabuhan` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `pelabuhan`
--
ALTER TABLE `pelabuhan`
  ADD CONSTRAINT `fk_pelabuhan_pair` FOREIGN KEY (`pair_id`) REFERENCES `pelabuhan` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `penumpang_detail`
--
ALTER TABLE `penumpang_detail`
  ADD CONSTRAINT `fk_penumpang_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id_ticket`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `boarding_scans`
--
ALTER TABLE `boarding_scans`
  ADD CONSTRAINT `fk_boarding_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id_ticket`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tickets`
--
ALTER TABLE `tickets`
  ADD CONSTRAINT `fk_tickets_asal` FOREIGN KEY (`asal_id`) REFERENCES `pelabuhan` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tickets_tujuan` FOREIGN KEY (`tujuan_id`) REFERENCES `pelabuhan` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tickets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
