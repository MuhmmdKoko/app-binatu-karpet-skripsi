-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3307:3307
-- Waktu pembuatan: 30 Jun 2025 pada 14.04
-- Versi server: 10.4.21-MariaDB
-- Versi PHP: 8.0.11

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `binatu_karpetv2`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `detail_pesanan`
--

CREATE TABLE `detail_pesanan` (
  `id_detail_pesanan` int(11) NOT NULL,
  `id_pesanan` int(11) NOT NULL,
  `id_layanan` int(11) NOT NULL,
  `deskripsi_item_spesifik` varchar(255) DEFAULT NULL,
  `panjang_karpet` float DEFAULT NULL,
  `lebar_karpet` float DEFAULT NULL,
  `ukuran_karpet` varchar(20) DEFAULT NULL,
  `kuantitas` decimal(10,2) NOT NULL,
  `harga_saat_pesan` decimal(10,2) NOT NULL,
  `subtotal_item` decimal(12,2) NOT NULL,
  `catatan_item` text DEFAULT NULL,
  `status_item_terkini` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data untuk tabel `detail_pesanan`
--

INSERT INTO `detail_pesanan` (`id_detail_pesanan`, `id_pesanan`, `id_layanan`, `deskripsi_item_spesifik`, `panjang_karpet`, `lebar_karpet`, `ukuran_karpet`, `kuantitas`, `harga_saat_pesan`, `subtotal_item`, `catatan_item`, `status_item_terkini`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'Karpet merah motif bunga 2x3m', NULL, NULL, NULL, '6.00', '25000.00', '150000.00', 'Noda kopi di sudut kiri atas.', 'Dibatalkan', '2025-05-25 10:50:34', '2025-06-15 12:39:20'),
(2, 2, 2, 'Karpet Persia Coklat 2.5x4m', NULL, NULL, NULL, '10.00', '40000.00', '400000.00', NULL, 'Diambil', '2025-05-25 10:50:34', '2025-06-15 12:00:48'),
(3, 3, 3, 'Karpet Biru Polos 2x2m', NULL, NULL, NULL, '4.00', '60000.00', '240000.00', 'Prioritas, butuh cepat.', 'Dibatalkan', '2025-05-25 10:50:34', '2025-06-15 11:58:03'),
(4, 3, 4, 'Antar Jemput Area Banjarbaru Kota', NULL, NULL, NULL, '1.00', '15000.00', '15000.00', 'Jemput jam 08:00, antar setelah selesai.', 'Dibatalkan', '2025-05-25 10:50:34', '2025-06-15 11:58:03'),
(5, 4, 1, 'Karpet anak gambar mobil 1.5x2m', NULL, NULL, NULL, '3.00', '25000.00', '75000.00', 'Banyak noda permen lengket.', 'Diproses', '2025-05-25 10:50:34', '2025-06-13 14:45:16'),
(26, 5, 1, '', NULL, NULL, NULL, '0.00', '0.00', '0.00', '', 'Selesai', '2025-06-15 15:33:29', '2025-06-22 14:21:28'),
(46, 6, 4, '', NULL, NULL, NULL, '0.00', '0.00', '0.00', '', 'Diterima', '2025-06-15 15:48:37', '2025-06-15 15:48:37'),
(53, 6, 1, '', NULL, NULL, NULL, '0.00', '0.00', '0.00', '', 'Diterima', '2025-06-15 15:48:37', '2025-06-15 15:48:37'),
(81, 7, 2, '', NULL, NULL, NULL, '0.00', '0.00', '0.00', '', 'Diterima', '2025-06-15 15:58:49', '2025-06-15 15:58:49'),
(82, 8, 3, '', NULL, NULL, NULL, '0.00', '0.00', '0.00', '', 'Diterima', '2025-06-15 15:59:31', '2025-06-15 15:59:31'),
(90, 10, 4, '', NULL, NULL, NULL, '0.00', '0.00', '0.00', '', 'Diterima', '2025-06-15 16:46:02', '2025-06-15 16:46:02'),
(91, 10, 1, '', NULL, NULL, NULL, '0.00', '0.00', '0.00', '', 'Diterima', '2025-06-15 16:46:02', '2025-06-15 16:46:02'),
(93, 11, 2, '', NULL, NULL, NULL, '0.00', '0.00', '0.00', '', 'Diterima', '2025-06-15 16:51:17', '2025-06-15 16:51:17'),
(101, 13, 3, 'aa', NULL, NULL, NULL, '4.00', '60000.00', '240000.00', '', 'Diterima', '2025-06-15 17:51:24', '2025-06-15 17:51:24'),
(102, 12, 2, 'aa', NULL, NULL, NULL, '3.00', '40000.00', '120000.00', '', 'Diterima', '2025-06-15 18:11:03', '2025-06-15 18:11:03'),
(103, 14, 3, '2', NULL, NULL, NULL, '2.00', '60000.00', '120000.00', '', 'Diproses', '2025-06-16 15:17:18', '2025-06-16 17:31:46'),
(104, 15, 4, 'aa', NULL, NULL, NULL, '1.00', '15000.00', '15000.00', '', 'Diterima', '2025-06-16 16:06:14', '2025-06-16 16:06:14'),
(105, 15, 3, 'aa', NULL, NULL, NULL, '3.00', '60000.00', '180000.00', '', 'Diterima', '2025-06-16 16:06:14', '2025-06-16 16:06:14'),
(106, 16, 3, '2', NULL, NULL, NULL, '2.00', '60000.00', '120000.00', '', 'Diterima', '2025-06-18 16:30:27', '2025-06-18 16:30:27'),
(107, 17, 2, '2', NULL, NULL, NULL, '2.00', '40000.00', '80000.00', '', 'Diambil', '2025-06-18 16:33:53', '2025-06-22 14:03:58'),
(108, 18, 5, '2', NULL, NULL, NULL, '2.00', '15000.00', '30000.00', '', 'Diambil', '2025-06-19 05:41:09', '2025-06-19 05:42:19'),
(109, 19, 4, 'ad', NULL, NULL, NULL, '1.00', '15000.00', '15000.00', '', 'Diterima', '2025-06-22 14:32:08', '2025-06-22 14:32:08'),
(110, 19, 3, 'aa', NULL, NULL, NULL, '3.00', '60000.00', '180000.00', '', 'Diterima', '2025-06-22 14:32:08', '2025-06-22 14:32:08'),
(111, 21, 3, '2', NULL, NULL, NULL, '2.00', '60000.00', '120000.00', '', 'Diterima', '2025-06-22 16:48:08', '2025-06-22 16:48:08'),
(112, 21, 2, '2', NULL, NULL, NULL, '2.00', '40000.00', '80000.00', '', 'Diterima', '2025-06-22 16:48:08', '2025-06-22 16:48:08'),
(113, 22, 4, 'aa', NULL, NULL, NULL, '1.00', '15000.00', '15000.00', '', 'Diterima', '2025-06-22 16:54:37', '2025-06-22 16:54:37'),
(114, 22, 3, 'aa', NULL, NULL, NULL, '1.00', '60000.00', '60000.00', '', 'Diterima', '2025-06-22 16:54:37', '2025-06-22 16:54:37'),
(115, 23, 3, '2', NULL, NULL, NULL, '2.00', '60000.00', '120000.00', '', 'Selesai', '2025-06-25 06:21:48', '2025-06-25 08:17:36'),
(116, 24, 3, 'a', NULL, NULL, NULL, '2.00', '60000.00', '120000.00', '', 'Diambil', '2025-06-25 06:30:46', '2025-06-25 07:52:11'),
(117, 26, 7, 'asd', 2.1, 1.6, NULL, '3.36', '18.00', '60480.00', '', 'Diterima', '2025-06-26 13:01:38', '2025-06-26 13:01:38'),
(118, 27, 7, '123', 2.1, 3.1, NULL, '6.51', '18.00', '117180.00', '', 'Diterima', '2025-06-26 13:02:49', '2025-06-26 13:02:49'),
(125, 28, 7, 'asd', 2.1, 3.1, NULL, '6.51', '18.00', '117180.00', '', 'Diterima', '2025-06-27 12:30:31', '2025-06-27 12:30:31');

-- --------------------------------------------------------

--
-- Struktur dari tabel `layanan`
--

CREATE TABLE `layanan` (
  `id_layanan` int(11) NOT NULL,
  `nama_layanan` varchar(255) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `harga_per_unit` decimal(10,2) NOT NULL,
  `satuan` varchar(50) NOT NULL,
  `estimasi_waktu_hari` int(11) DEFAULT NULL,
  `minimal_order_kuantitas` decimal(10,2) DEFAULT 0.00,
  `minimal_order_aktif` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data untuk tabel `layanan`
--

INSERT INTO `layanan` (`id_layanan`, `nama_layanan`, `deskripsi`, `harga_per_unit`, `satuan`, `estimasi_waktu_hari`, `minimal_order_kuantitas`, `minimal_order_aktif`, `created_at`, `updated_at`) VALUES
(1, 'Cuci Karpet Standar', 'Pencucian karpet standar dengan deterjen berkualitas, pembilasan, dan pengeringan.', '25000.00', 'm²', 3, '0.00', 0, '2025-05-25 10:50:34', '2025-05-25 10:50:34'),
(2, 'Cuci Karpet Premium', 'Pencucian mendalam dengan treatment khusus untuk noda membandel, pewangi premium, dan pengeringan cepat.', '40000.00', 'm²', 2, '0.00', 0, '2025-05-25 10:50:34', '2025-05-25 10:50:34'),
(3, 'Cuci Karpet Express', 'Layanan cuci karpet super cepat, selesai dalam 24 jam.', '60000.00', 'm²', 1, '0.00', 0, '2025-05-25 10:50:34', '2025-05-25 10:50:34'),
(4, 'Antar Jemput Karpet', 'Layanan pengambilan dan pengantaran karpet ke alamat pelanggan.', '15000.00', 'kali', 0, '0.00', 0, '2025-05-25 10:50:34', '2025-05-25 10:50:34'),
(5, 'Karpet Bulu', NULL, '15000.00', 'm²', 5, '0.00', 0, '2025-06-19 05:37:46', '2025-06-25 08:26:43'),
(6, 'Karpet Permadani', NULL, '24000.00', 'm²', 4, '0.00', 0, '2025-06-25 08:26:36', '2025-06-25 08:26:36'),
(7, 'Karpet Reguler', NULL, '18000.00', 'm²', 4, '3.20', 1, '2025-06-25 08:39:09', '2025-06-25 08:39:09'),
(8, 'asd', NULL, '12000.00', 'm²', 0, '0.00', 0, '2025-06-25 09:21:07', '2025-06-25 09:21:07'),
(9, 'ads', NULL, '15000.00', 'm²', 3, '0.00', 0, '2025-06-25 15:06:31', '2025-06-25 15:06:31'),
(10, 'Cuci Karpet Ditempat', NULL, '700000.00', 'Kali', 2, '0.00', 0, '2025-06-29 06:16:07', '2025-06-29 06:17:10');

-- --------------------------------------------------------

--
-- Struktur dari tabel `log_pembayaran`
--

CREATE TABLE `log_pembayaran` (
  `id_log_pembayaran` int(11) NOT NULL,
  `id_pesanan` int(11) NOT NULL,
  `id_pengguna` int(11) NOT NULL,
  `jumlah_bayar` decimal(12,2) NOT NULL,
  `tanggal_bayar` timestamp NOT NULL DEFAULT current_timestamp(),
  `metode_pembayaran` varchar(50) DEFAULT 'Tunai',
  `catatan` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data untuk tabel `log_pembayaran`
--

INSERT INTO `log_pembayaran` (`id_log_pembayaran`, `id_pesanan`, `id_pengguna`, `jumlah_bayar`, `tanggal_bayar`, `metode_pembayaran`, `catatan`) VALUES
(1, 19, 1, '5000.00', '2025-06-22 14:42:59', 'Tunai', NULL),
(2, 19, 1, '5000.00', '2025-06-22 14:46:27', 'Tunai', NULL),
(3, 24, 1, '120000.00', '2025-06-25 06:43:27', 'Tunai', NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `pelanggan`
--

CREATE TABLE `pelanggan` (
  `id_pelanggan` int(11) NOT NULL,
  `nama_pelanggan` varchar(255) NOT NULL,
  `nomor_telepon` varchar(20) NOT NULL,
  `id_telegram` varchar(100) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `tanggal_daftar` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data untuk tabel `pelanggan`
--

INSERT INTO `pelanggan` (`id_pelanggan`, `nama_pelanggan`, `nomor_telepon`, `id_telegram`, `alamat`, `email`, `catatan`, `tanggal_daftar`, `updated_at`) VALUES
(1, 'Budi Santoso', '081234567890', 'budisantoso_tg', 'Jl. Melati No. 10, Banjarbaru', 'budi.s@example.com', 'Pelanggan reguler, minta dihubungi via WhatsApp jika selesai.', '2025-05-25 10:50:34', '2025-05-25 10:50:34'),
(2, 'Ani Wijaya', '085678901234', NULL, 'Jl. Anggrek Blok C2 No. 5, Martapura', 'ani.wijaya@example.com', NULL, '2025-05-25 10:50:34', '2025-05-25 10:50:34'),
(3, 'Citra Lestari', '087712345678', 'citralestari88', 'Jl. Kenanga No. 22, Landasan Ulin', 'citra.l@example.com', 'Karpetnya tebal, butuh perhatian ekstra saat pengeringan.', '2025-05-25 10:50:34', '2025-05-25 10:50:34'),
(4, 'Dewi Puspita', '081198765432', NULL, 'Komplek Griya Asri, Blok Mawar No. 1, Banjarbaru', 'dewi.p@example.com', 'Minta nota fisik juga.', '2025-05-25 10:50:34', '2025-05-25 10:50:34'),
(5, 'Koko', '085787894367', '1916343560', 'KOMP. BANUA PERMAI JL. GUNUNG PERMAI BARAT VI NO. 252', 'muhmmdkoko@gmail.com', '', '2025-06-13 12:17:29', '2025-06-16 15:49:45'),
(6, 'Ibu Purwanti', '085750845023', '8153601926', 'Jl. Sukarelawan', '', '', '2025-06-16 16:00:53', '2025-06-16 16:01:36'),
(7, 'M. Adhy Haryadi', '085822013409', '1985153680', '', '', '', '2025-06-16 16:03:49', '2025-06-16 16:04:36'),
(21, 'Ibu Rizky', '085752303909', '', '', '', '', '2025-06-19 05:35:08', '2025-06-19 05:35:08');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pengguna`
--

CREATE TABLE `pengguna` (
  `id_pengguna` int(11) NOT NULL,
  `nama_lengkap` varchar(255) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` varchar(50) NOT NULL COMMENT 'e.g., ''Admin'', ''Karyawan''',
  `nomor_telepon_internal` varchar(20) DEFAULT NULL,
  `email_internal` varchar(255) DEFAULT NULL,
  `status_aktif` tinyint(1) NOT NULL DEFAULT 1,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data untuk tabel `pengguna`
--

INSERT INTO `pengguna` (`id_pengguna`, `nama_lengkap`, `username`, `password_hash`, `role`, `nomor_telepon_internal`, `email_internal`, `status_aktif`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'Admin Utama', 'admin', '$2y$10$QdXikVLuSoCMZtjpj5FgzOKRerEdWc2otqrLu04JwORZqmzVpYzoy', 'Admin', '0511123456', 'admin@binatukita.com', 1, '2025-06-16 15:45:44', '2025-05-25 10:50:34', '2025-06-16 15:45:44'),
(2, 'Rina Kasir', 'rina_kasir', '$2y$10$uc8eX.Bg0p7F47XpfuHh1.r59bdPyRom3BcWfx6jRLVPZBZF5iZDS', 'Karyawan', '081211112222', 'rina.kasir@binatukita.com', 1, NULL, '2025-05-25 10:50:34', '2025-05-25 11:03:27'),
(3, 'Agus Lapangan', 'agus_lapangan', '$2y$10$qrXm2SlyZ27mMqz5IWtdXOK7Jc5olN6bSuEJcc/GdBwmlJFUy0Ize', 'Karyawan', '081233334444', 'agus.lapangan@binatukita.com', 1, NULL, '2025-05-25 10:50:34', '2025-05-25 11:03:27');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pesanan`
--

CREATE TABLE `pesanan` (
  `id_pesanan` int(11) NOT NULL,
  `id_pelanggan` int(11) NOT NULL,
  `id_pengguna_penerima` int(11) NOT NULL,
  `nomor_invoice` varchar(50) NOT NULL,
  `tanggal_masuk` datetime NOT NULL DEFAULT current_timestamp(),
  `tanggal_estimasi_selesai` datetime DEFAULT NULL,
  `tanggal_selesai_aktual` datetime DEFAULT NULL,
  `tanggal_diambil` datetime DEFAULT NULL,
  `total_harga_keseluruhan` decimal(12,2) DEFAULT 0.00,
  `status_pesanan_umum` varchar(50) NOT NULL COMMENT 'e.g., ''Baru'', ''Diproses'', ''Selesai'', ''Diambil'', ''Dibatalkan''',
  `catatan_pesanan` text DEFAULT NULL,
  `metode_pembayaran` varchar(50) DEFAULT NULL,
  `status_pembayaran` varchar(50) NOT NULL COMMENT 'e.g., ''Belum Lunas'', ''Lunas'', ''DP''',
  `nominal_pembayaran` decimal(12,2) DEFAULT 0.00,
  `id_promosi` int(11) DEFAULT NULL,
  `diskon` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_setelah_diskon` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data untuk tabel `pesanan`
--

INSERT INTO `pesanan` (`id_pesanan`, `id_pelanggan`, `id_pengguna_penerima`, `nomor_invoice`, `tanggal_masuk`, `tanggal_estimasi_selesai`, `tanggal_selesai_aktual`, `tanggal_diambil`, `total_harga_keseluruhan`, `status_pesanan_umum`, `catatan_pesanan`, `metode_pembayaran`, `status_pembayaran`, `nominal_pembayaran`, `id_promosi`, `diskon`, `total_setelah_diskon`, `created_at`, `updated_at`) VALUES
(1, 1, 2, 'INV-20250525-001', '2025-05-25 10:00:00', '2025-05-28 10:00:00', NULL, NULL, '150000.00', 'Diproses', 'Karpet ruang tamu, ada noda kopi sedikit.', 'Tunai', 'Belum Lunas', '0.00', NULL, '0.00', '0.00', '2025-05-25 10:50:34', '2025-06-15 12:38:39'),
(2, 2, 2, 'INV-20250525-002', '2025-05-25 11:30:00', '2025-05-27 11:30:00', NULL, NULL, '400000.00', 'Diambil', NULL, 'Transfer Bank', 'Lunas', '0.00', NULL, '0.00', '0.00', '2025-05-25 10:50:34', '2025-06-15 11:58:23'),
(3, 1, 3, 'INV-20250526-001', '2025-05-26 09:15:00', '2025-05-27 09:15:00', NULL, NULL, '255000.00', 'Dibatalkan', 'Ada layanan antar jemput.', 'QRIS', 'DP', '0.00', NULL, '0.00', '0.00', '2025-05-25 10:50:34', '2025-06-15 11:58:03'),
(4, 3, 2, 'INV-20250526-002', '2025-05-26 14:00:00', '2025-05-29 14:00:00', NULL, NULL, '75000.00', 'Dibatalkan', 'Karpet kamar anak, banyak noda makanan.', 'Tunai', 'Belum Lunas', '0.00', NULL, '0.00', '0.00', '2025-05-25 10:50:34', '2025-06-15 11:26:10'),
(5, 5, 1, 'INV-20250615-195135', '2025-06-15 19:51:35', '2025-06-18 00:00:00', '2025-06-22 22:21:17', NULL, '0.00', 'Selesai', '', 'Tunai', 'Belum Lunas', '0.00', NULL, '0.00', '0.00', '2025-06-15 12:51:35', '2025-06-22 14:21:17'),
(6, 4, 1, 'INV-20250615-223456', '2025-06-15 22:34:56', '2025-06-22 00:00:00', NULL, NULL, '0.00', 'Baru', 'tt', 'Tunai', 'Belum Lunas', '0.00', NULL, '0.00', '0.00', '2025-06-15 15:34:56', '2025-06-15 15:48:37'),
(7, 5, 1, 'INV-20250615-224906', '2025-06-15 22:49:06', '2025-06-17 00:00:00', NULL, NULL, '0.00', 'Baru', 'aa', 'Tunai', 'Belum Lunas', '0.00', NULL, '0.00', '0.00', '2025-06-15 15:49:06', '2025-06-15 15:50:40'),
(8, 5, 1, 'INV-20250615-225931', '2025-06-15 22:59:31', '2025-06-16 00:00:00', NULL, NULL, '120000.00', 'Baru', '', 'Tunai', 'Belum Lunas', '0.00', NULL, '0.00', '0.00', '2025-06-15 15:59:31', '2025-06-15 15:59:31'),
(9, 5, 1, 'INV-20250615-233519', '2025-06-15 23:35:19', '2025-06-17 00:00:00', NULL, NULL, '120000.00', 'Baru', '', 'Tunai', 'DP', '3200.00', NULL, '0.00', '0.00', '2025-06-15 16:35:19', '2025-06-16 18:02:02'),
(10, 2, 1, 'INV-20250615-234148', '2025-06-15 23:41:48', '2025-06-19 00:00:00', NULL, NULL, '0.00', 'Baru', 'aa', 'Tunai', 'Belum Lunas', '0.00', NULL, '0.00', '0.00', '2025-06-15 16:41:48', '2025-06-15 16:46:02'),
(11, 4, 1, 'INV-20250615-234657', '2025-06-15 23:46:57', '2025-06-18 00:00:00', NULL, NULL, '0.00', 'Baru', 'aa', 'Tunai', 'Belum Lunas', '0.00', NULL, '0.00', '0.00', '2025-06-15 16:46:57', '2025-06-15 16:51:17'),
(12, 5, 1, 'INV-20250615-235157', '2025-06-15 23:51:57', '2025-06-18 00:00:00', NULL, NULL, '120000.00', 'Baru', '', 'Tunai', 'Lunas', '120000.00', NULL, '0.00', '0.00', '2025-06-15 16:51:57', '2025-06-16 17:44:43'),
(13, 4, 1, 'INV-20250615-235708', '2025-06-15 23:57:08', '2025-06-17 00:00:00', NULL, NULL, '240000.00', 'Baru', '', 'Tunai', 'Belum Lunas', '0.00', NULL, '0.00', '0.00', '2025-06-15 16:57:08', '2025-06-15 17:51:24'),
(14, 5, 1, 'INV-20250616-221718', '2025-06-16 22:17:18', '2025-06-17 00:00:00', NULL, NULL, '120000.00', 'Diproses', '', 'Tunai', 'Lunas', '120000.00', NULL, '0.00', '0.00', '2025-06-16 15:17:18', '2025-06-16 17:39:19'),
(15, 7, 1, 'INV-20250616-230614', '2025-06-16 23:06:14', '2025-06-18 00:00:00', NULL, NULL, '195000.00', 'Baru', '', 'Tunai', 'Belum Lunas', '0.00', NULL, '0.00', '0.00', '2025-06-16 16:06:14', '2025-06-16 16:06:14'),
(16, 5, 1, 'INV-20250618-233027', '2025-06-18 23:30:27', '2025-06-20 00:00:00', NULL, NULL, '120000.00', 'Baru', '', 'Tunai', 'Lunas', '120000.00', NULL, '0.00', '0.00', '2025-06-18 16:30:27', '2025-06-18 16:31:49'),
(17, 5, 1, 'INV-20250618-233353', '2025-06-18 23:33:53', '2025-06-21 00:00:00', NULL, '2025-06-22 22:03:58', '80000.00', 'Diambil', '', 'Tunai', 'Lunas', '80000.00', NULL, '0.00', '0.00', '2025-06-18 16:33:53', '2025-06-22 14:03:58'),
(18, 5, 1, 'INV-20250619-124109', '2025-06-19 12:41:09', '2025-06-24 00:00:00', '2025-06-25 15:27:01', '2025-06-25 15:21:10', '30000.00', 'Selesai', '', 'Tunai', 'Lunas', '30000.00', NULL, '0.00', '0.00', '2025-06-19 05:41:09', '2025-06-25 07:27:01'),
(19, 5, 1, 'INV-20250622-213208', '2025-06-22 21:32:08', '2025-06-23 00:00:00', NULL, NULL, '195000.00', 'Baru', '', 'Tunai', 'DP', '10000.00', NULL, '0.00', '0.00', '2025-06-22 14:32:08', '2025-06-22 14:46:27'),
(21, 5, 1, 'INV-20250622-234808', '2025-06-22 23:48:08', '2025-06-25 00:00:00', NULL, NULL, '200000.00', 'Baru', '', 'Tunai', 'Belum Lunas', '0.00', NULL, '0.00', '200000.00', '2025-06-22 16:48:08', '2025-06-22 16:48:08'),
(22, 5, 1, 'INV-20250622-235437', '2025-06-22 23:54:37', '2025-06-24 00:00:00', NULL, NULL, '75000.00', 'Baru', '', 'Tunai', 'Belum Lunas', '0.00', NULL, '7500.00', '67500.00', '2025-06-22 16:54:37', '2025-06-22 16:54:37'),
(23, 5, 1, 'INV-20250625-132148', '2025-06-25 13:21:48', '2025-06-26 00:00:00', '2025-06-25 16:17:36', NULL, '120000.00', 'Selesai', '', 'Tunai', 'Belum Lunas', '0.00', NULL, '0.00', '120000.00', '2025-06-25 06:21:48', '2025-06-25 08:17:36'),
(24, 5, 1, 'INV-20250625-133046', '2025-06-25 13:30:46', '2025-06-26 13:30:46', '2025-06-25 15:31:51', '2025-06-25 15:58:15', '120000.00', 'Diambil', '', 'Tunai', 'Lunas', '120000.00', NULL, '0.00', '120000.00', '2025-06-25 06:30:46', '2025-06-25 07:58:15'),
(26, 5, 1, 'INV-20250626-200138', '2025-06-26 20:01:38', '2025-06-30 20:01:38', NULL, NULL, '60480.00', 'Baru', '', 'Tunai', 'Belum Lunas', '0.00', NULL, '0.00', '0.00', '2025-06-26 13:01:38', '2025-06-26 13:01:38'),
(27, 5, 1, 'INV-20250626-200249', '2025-06-26 20:02:49', '2025-06-30 20:02:49', NULL, NULL, '117180.00', 'Baru', '', 'Tunai', 'Belum Lunas', '0.00', NULL, '11718.00', '105462.00', '2025-06-26 13:02:49', '2025-06-26 13:02:49'),
(28, 5, 1, 'INV-20250626-201446', '2025-06-26 20:14:46', '2025-06-30 20:14:46', NULL, NULL, '117180.00', 'Baru', '', 'Tunai', 'Belum Lunas', '0.00', 6, '11718.00', '105462.00', '2025-06-26 13:14:46', '2025-06-27 12:32:03');

-- --------------------------------------------------------

--
-- Struktur dari tabel `promosi`
--

CREATE TABLE `promosi` (
  `id_promosi` int(11) NOT NULL,
  `judul` varchar(100) DEFAULT NULL,
  `kode_promo` varchar(50) DEFAULT NULL,
  `tipe_promo` enum('persen','nominal') NOT NULL DEFAULT 'nominal',
  `nilai_promo` decimal(10,2) NOT NULL DEFAULT 0.00,
  `syarat_min_transaksi` decimal(10,2) DEFAULT NULL,
  `isi_pesan` text NOT NULL,
  `tanggal_buat` datetime DEFAULT current_timestamp(),
  `tanggal_kirim` datetime DEFAULT NULL,
  `tanggal_mulai` date DEFAULT NULL,
  `tanggal_berakhir` date DEFAULT NULL,
  `status_promo` enum('draft','aktif','tidak_aktif','terkirim') NOT NULL DEFAULT 'draft'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data untuk tabel `promosi`
--

INSERT INTO `promosi` (`id_promosi`, `judul`, `kode_promo`, `tipe_promo`, `nilai_promo`, `syarat_min_transaksi`, `isi_pesan`, `tanggal_buat`, `tanggal_kirim`, `tanggal_mulai`, `tanggal_berakhir`, `status_promo`) VALUES
(1, 'Promo Cuci Karpet Juni', NULL, 'nominal', '0.00', NULL, 'Dapatkan diskon 20% untuk layanan cuci karpet selama bulan Juni! Info lebih lanjut hubungi admin.', '2025-06-18 23:22:06', '2025-06-19 13:56:32', NULL, NULL, 'terkirim'),
(2, 'Gratis Antar Jemput', NULL, 'nominal', '0.00', NULL, 'Nikmati layanan antar jemput karpet GRATIS untuk pemesanan minimal 2 karpet minggu ini!', '2025-06-18 23:22:06', NULL, NULL, NULL, 'draft'),
(3, 'Pelanggan Loyal', NULL, 'nominal', '0.00', NULL, 'Terima kasih telah menjadi pelanggan setia! Dapatkan voucher potongan Rp10.000 untuk transaksi berikutnya.', '2025-06-18 23:22:06', NULL, NULL, NULL, 'draft'),
(4, 'Layanan Terlaris', '', 'nominal', '0.00', NULL, 'asd', '2025-06-19 13:11:43', NULL, '2025-06-24', '2025-06-25', 'tidak_aktif'),
(6, 'Diskon 10%', '10%', 'persen', '10.00', NULL, 'Diskon 10%', '2025-06-27 19:05:44', NULL, NULL, NULL, 'aktif');

-- --------------------------------------------------------

--
-- Struktur dari tabel `promosi_pelanggan`
--

CREATE TABLE `promosi_pelanggan` (
  `id_promosi` int(11) NOT NULL,
  `id_pelanggan` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data untuk tabel `promosi_pelanggan`
--

INSERT INTO `promosi_pelanggan` (`id_promosi`, `id_pelanggan`) VALUES
(1, 1),
(1, 2),
(1, 3),
(1, 4),
(1, 5),
(1, 6),
(1, 7),
(2, 1),
(2, 3),
(2, 5),
(3, 1),
(3, 4);

-- --------------------------------------------------------

--
-- Struktur dari tabel `riwayat_status_item`
--

CREATE TABLE `riwayat_status_item` (
  `id_riwayat` int(11) NOT NULL,
  `id_detail_pesanan` int(11) NOT NULL,
  `status_sebelumnya` varchar(50) DEFAULT NULL,
  `status_baru` varchar(50) NOT NULL,
  `waktu_perubahan` datetime NOT NULL,
  `id_pengguna` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data untuk tabel `riwayat_status_item`
--

INSERT INTO `riwayat_status_item` (`id_riwayat`, `id_detail_pesanan`, `status_sebelumnya`, `status_baru`, `waktu_perubahan`, `id_pengguna`) VALUES
(1, 116, 'Selesai', 'Diambil', '2025-06-25 14:58:04', 1),
(2, 116, 'Selesai', 'Diambil', '2025-06-25 15:32:12', 1),
(3, 116, 'Selesai', 'Diambil', '2025-06-25 15:52:11', 1),
(4, 115, 'Diproses', 'Selesai', '2025-06-25 16:17:36', 1);

-- --------------------------------------------------------

--
-- Struktur dari tabel `riwayat_status_pesanan`
--

CREATE TABLE `riwayat_status_pesanan` (
  `id_riwayat_pesanan` int(11) NOT NULL,
  `id_pesanan` int(11) NOT NULL,
  `status_sebelumnya` varchar(50) NOT NULL,
  `status_baru` varchar(50) NOT NULL,
  `waktu_perubahan` datetime NOT NULL,
  `id_pengguna` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data untuk tabel `riwayat_status_pesanan`
--

INSERT INTO `riwayat_status_pesanan` (`id_riwayat_pesanan`, `id_pesanan`, `status_sebelumnya`, `status_baru`, `waktu_perubahan`, `id_pengguna`) VALUES
(1, 24, 'Diambil', '', '2025-06-25 15:14:15', 1),
(2, 18, 'Diambil', '', '2025-06-25 15:16:36', 1),
(3, 18, '', 'Diambil', '2025-06-25 15:21:10', 1),
(4, 18, 'Diambil', 'Selesai', '2025-06-25 15:27:01', 1),
(5, 24, 'Selesai', 'Diambil', '2025-06-25 15:38:20', 1),
(6, 23, 'Diproses', 'Selesai', '2025-06-25 16:17:36', 1);

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `detail_pesanan`
--
ALTER TABLE `detail_pesanan`
  ADD PRIMARY KEY (`id_detail_pesanan`),
  ADD KEY `fk_Detail_Pesanan_Pesanan_idx` (`id_pesanan`),
  ADD KEY `fk_Detail_Pesanan_Layanan_idx` (`id_layanan`);

--
-- Indeks untuk tabel `layanan`
--
ALTER TABLE `layanan`
  ADD PRIMARY KEY (`id_layanan`);

--
-- Indeks untuk tabel `log_pembayaran`
--
ALTER TABLE `log_pembayaran`
  ADD PRIMARY KEY (`id_log_pembayaran`),
  ADD KEY `fk_log_pembayaran_pesanan` (`id_pesanan`),
  ADD KEY `fk_log_pembayaran_pengguna` (`id_pengguna`);

--
-- Indeks untuk tabel `pelanggan`
--
ALTER TABLE `pelanggan`
  ADD PRIMARY KEY (`id_pelanggan`);

--
-- Indeks untuk tabel `pengguna`
--
ALTER TABLE `pengguna`
  ADD PRIMARY KEY (`id_pengguna`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indeks untuk tabel `pesanan`
--
ALTER TABLE `pesanan`
  ADD PRIMARY KEY (`id_pesanan`),
  ADD UNIQUE KEY `nomor_invoice` (`nomor_invoice`),
  ADD KEY `fk_Pesanan_Pelanggan_idx` (`id_pelanggan`),
  ADD KEY `fk_Pesanan_Pengguna_idx` (`id_pengguna_penerima`),
  ADD KEY `id_promosi` (`id_promosi`);

--
-- Indeks untuk tabel `promosi`
--
ALTER TABLE `promosi`
  ADD PRIMARY KEY (`id_promosi`),
  ADD UNIQUE KEY `kode_promo` (`kode_promo`);

--
-- Indeks untuk tabel `promosi_pelanggan`
--
ALTER TABLE `promosi_pelanggan`
  ADD PRIMARY KEY (`id_promosi`,`id_pelanggan`),
  ADD KEY `id_pelanggan` (`id_pelanggan`);

--
-- Indeks untuk tabel `riwayat_status_item`
--
ALTER TABLE `riwayat_status_item`
  ADD PRIMARY KEY (`id_riwayat`),
  ADD KEY `id_detail_pesanan` (`id_detail_pesanan`),
  ADD KEY `id_pengguna` (`id_pengguna`);

--
-- Indeks untuk tabel `riwayat_status_pesanan`
--
ALTER TABLE `riwayat_status_pesanan`
  ADD PRIMARY KEY (`id_riwayat_pesanan`),
  ADD KEY `id_pesanan` (`id_pesanan`),
  ADD KEY `id_pengguna` (`id_pengguna`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `detail_pesanan`
--
ALTER TABLE `detail_pesanan`
  MODIFY `id_detail_pesanan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=126;

--
-- AUTO_INCREMENT untuk tabel `layanan`
--
ALTER TABLE `layanan`
  MODIFY `id_layanan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT untuk tabel `log_pembayaran`
--
ALTER TABLE `log_pembayaran`
  MODIFY `id_log_pembayaran` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `pelanggan`
--
ALTER TABLE `pelanggan`
  MODIFY `id_pelanggan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT untuk tabel `pengguna`
--
ALTER TABLE `pengguna`
  MODIFY `id_pengguna` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `pesanan`
--
ALTER TABLE `pesanan`
  MODIFY `id_pesanan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT untuk tabel `promosi`
--
ALTER TABLE `promosi`
  MODIFY `id_promosi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `riwayat_status_item`
--
ALTER TABLE `riwayat_status_item`
  MODIFY `id_riwayat` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `riwayat_status_pesanan`
--
ALTER TABLE `riwayat_status_pesanan`
  MODIFY `id_riwayat_pesanan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `detail_pesanan`
--
ALTER TABLE `detail_pesanan`
  ADD CONSTRAINT `fk_Detail_Pesanan_Layanan` FOREIGN KEY (`id_layanan`) REFERENCES `layanan` (`id_layanan`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_Detail_Pesanan_Pesanan` FOREIGN KEY (`id_pesanan`) REFERENCES `pesanan` (`id_pesanan`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `log_pembayaran`
--
ALTER TABLE `log_pembayaran`
  ADD CONSTRAINT `fk_log_pembayaran_pengguna` FOREIGN KEY (`id_pengguna`) REFERENCES `pengguna` (`id_pengguna`) ON DELETE NO ACTION ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_log_pembayaran_pesanan` FOREIGN KEY (`id_pesanan`) REFERENCES `pesanan` (`id_pesanan`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `pesanan`
--
ALTER TABLE `pesanan`
  ADD CONSTRAINT `fk_Pesanan_Pelanggan` FOREIGN KEY (`id_pelanggan`) REFERENCES `pelanggan` (`id_pelanggan`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_Pesanan_Pengguna` FOREIGN KEY (`id_pengguna_penerima`) REFERENCES `pengguna` (`id_pengguna`) ON UPDATE CASCADE,
  ADD CONSTRAINT `pesanan_ibfk_1` FOREIGN KEY (`id_promosi`) REFERENCES `promosi` (`id_promosi`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `promosi_pelanggan`
--
ALTER TABLE `promosi_pelanggan`
  ADD CONSTRAINT `promosi_pelanggan_ibfk_1` FOREIGN KEY (`id_promosi`) REFERENCES `promosi` (`id_promosi`) ON DELETE CASCADE,
  ADD CONSTRAINT `promosi_pelanggan_ibfk_2` FOREIGN KEY (`id_pelanggan`) REFERENCES `pelanggan` (`id_pelanggan`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
