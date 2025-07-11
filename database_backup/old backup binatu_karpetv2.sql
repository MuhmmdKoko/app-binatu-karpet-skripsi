-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3307:3307
-- Waktu pembuatan: 25 Bulan Mei 2025 pada 12.51
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

INSERT INTO `detail_pesanan` (`id_detail_pesanan`, `id_pesanan`, `id_layanan`, `deskripsi_item_spesifik`, `kuantitas`, `harga_saat_pesan`, `subtotal_item`, `catatan_item`, `status_item_terkini`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'Karpet merah motif bunga 2x3m', '6.00', '25000.00', '150000.00', 'Noda kopi di sudut kiri atas.', 'Diterima', '2025-05-25 10:50:34', '2025-05-25 10:50:34'),
(2, 2, 2, 'Karpet Persia Coklat 2.5x4m', '10.00', '40000.00', '400000.00', NULL, 'Proses Cuci', '2025-05-25 10:50:34', '2025-05-25 10:50:34'),
(3, 3, 3, 'Karpet Biru Polos 2x2m', '4.00', '60000.00', '240000.00', 'Prioritas, butuh cepat.', 'Diterima', '2025-05-25 10:50:34', '2025-05-25 10:50:34'),
(4, 3, 4, 'Antar Jemput Area Banjarbaru Kota', '1.00', '15000.00', '15000.00', 'Jemput jam 08:00, antar setelah selesai.', 'Antar Jemput Dipesan', '2025-05-25 10:50:34', '2025-05-25 10:50:34'),
(5, 4, 1, 'Karpet anak gambar mobil 1.5x2m', '3.00', '25000.00', '75000.00', 'Banyak noda permen lengket.', 'Diterima', '2025-05-25 10:50:34', '2025-05-25 10:50:34');

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
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data untuk tabel `layanan`
--

INSERT INTO `layanan` (`id_layanan`, `nama_layanan`, `deskripsi`, `harga_per_unit`, `satuan`, `estimasi_waktu_hari`, `created_at`, `updated_at`) VALUES
(1, 'Cuci Karpet Standar', 'Pencucian karpet standar dengan deterjen berkualitas, pembilasan, dan pengeringan.', '25000.00', 'm²', 3, '2025-05-25 10:50:34', '2025-05-25 10:50:34'),
(2, 'Cuci Karpet Premium', 'Pencucian mendalam dengan treatment khusus untuk noda membandel, pewangi premium, dan pengeringan cepat.', '40000.00', 'm²', 2, '2025-05-25 10:50:34', '2025-05-25 10:50:34'),
(3, 'Cuci Karpet Express', 'Layanan cuci karpet super cepat, selesai dalam 24 jam.', '60000.00', 'm²', 1, '2025-05-25 10:50:34', '2025-05-25 10:50:34'),
(4, 'Antar Jemput Karpet', 'Layanan pengambilan dan pengantaran karpet ke alamat pelanggan.', '15000.00', 'kali', 0, '2025-05-25 10:50:34', '2025-05-25 10:50:34');

-- --------------------------------------------------------

--
-- Struktur dari tabel `notifikasi`
--

CREATE TABLE `notifikasi` (
  `id_notifikasi` int(11) NOT NULL,
  `id_pesanan` int(11) NOT NULL,
  `id_pelanggan` int(11) NOT NULL,
  `tipe_channel` varchar(50) NOT NULL COMMENT 'e.g., ''WhatsApp'', ''Telegram'', ''Email''',
  `target_penerima` varchar(255) NOT NULL,
  `isi_pesan` text NOT NULL,
  `status_pengiriman` varchar(50) NOT NULL COMMENT 'e.g., ''Terkirim'', ''Gagal'', ''Menunggu Jadwal''',
  `waktu_kirim` timestamp NULL DEFAULT NULL,
  `waktu_dijadwalkan` timestamp NULL DEFAULT NULL,
  `respon_gateway` text DEFAULT NULL,
  `jumlah_percobaan` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data untuk tabel `notifikasi`
--

INSERT INTO `notifikasi` (`id_notifikasi`, `id_pesanan`, `id_pelanggan`, `tipe_channel`, `target_penerima`, `isi_pesan`, `status_pengiriman`, `waktu_kirim`, `waktu_dijadwalkan`, `respon_gateway`, `jumlah_percobaan`, `created_at`) VALUES
(1, 2, 2, 'WhatsApp', '085678901234', 'Halo Ibu Ani Wijaya, pesanan karpet Anda dengan nomor invoice INV-20250525-002 sudah selesai dicuci dan siap diambil. Terima kasih.', 'Menunggu Jadwal', NULL, '2025-05-27 02:00:00', NULL, 0, '2025-05-25 10:50:34');

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
(4, 'Dewi Puspita', '081198765432', NULL, 'Komplek Griya Asri, Blok Mawar No. 1, Banjarbaru', 'dewi.p@example.com', 'Minta nota fisik juga.', '2025-05-25 10:50:34', '2025-05-25 10:50:34');

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
(1, 'Admin Utama', 'admin', '$2b$10$abcdefghijklmnopqrstuv', 'Admin', '0511123456', 'admin@binatukita.com', 1, NULL, '2025-05-25 10:50:34', '2025-05-25 10:50:34'),
(2, 'Rina Kasir', 'rina_kasir', '$2b$10$abcdefghijklmnopqrstu', 'Karyawan', '081211112222', 'rina.kasir@binatukita.com', 1, NULL, '2025-05-25 10:50:34', '2025-05-25 10:50:34'),
(3, 'Agus Lapangan', 'agus_lapangan', '$2b$10$abcdefghijklmnopqrst', 'Karyawan', '081233334444', 'agus.lapangan@binatukita.com', 1, NULL, '2025-05-25 10:50:34', '2025-05-25 10:50:34');

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
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data untuk tabel `pesanan`
--

INSERT INTO `pesanan` (`id_pesanan`, `id_pelanggan`, `id_pengguna_penerima`, `nomor_invoice`, `tanggal_masuk`, `tanggal_estimasi_selesai`, `tanggal_selesai_aktual`, `total_harga_keseluruhan`, `status_pesanan_umum`, `catatan_pesanan`, `metode_pembayaran`, `status_pembayaran`, `created_at`, `updated_at`) VALUES
(1, 1, 2, 'INV-20250525-001', '2025-05-25 10:00:00', '2025-05-28 10:00:00', NULL, '150000.00', 'Baru', 'Karpet ruang tamu, ada noda kopi sedikit.', 'Tunai', 'Belum Lunas', '2025-05-25 10:50:34', '2025-05-25 10:50:34'),
(2, 2, 2, 'INV-20250525-002', '2025-05-25 11:30:00', '2025-05-27 11:30:00', NULL, '400000.00', 'Diproses', NULL, 'Transfer Bank', 'Lunas', '2025-05-25 10:50:34', '2025-05-25 10:50:34'),
(3, 1, 3, 'INV-20250526-001', '2025-05-26 09:15:00', '2025-05-27 09:15:00', NULL, '255000.00', 'Baru', 'Ada layanan antar jemput.', 'QRIS', 'DP', '2025-05-25 10:50:34', '2025-05-25 10:50:34'),
(4, 3, 2, 'INV-20250526-002', '2025-05-26 14:00:00', '2025-05-29 14:00:00', NULL, '75000.00', 'Baru', 'Karpet kamar anak, banyak noda makanan.', 'Tunai', 'Belum Lunas', '2025-05-25 10:50:34', '2025-05-25 10:50:34');

-- --------------------------------------------------------

--
-- Struktur dari tabel `status_proses`
--

CREATE TABLE `status_proses` (
  `id_status_proses` int(11) NOT NULL,
  `id_detail_pesanan` int(11) NOT NULL,
  `id_pengguna_update` int(11) NOT NULL,
  `status_deskripsi` varchar(255) NOT NULL COMMENT 'e.g., ''Karpet diterima di workshop'', ''Proses pencucian dimulai'', ''Pengeringan'', ''Inspeksi kualitas'', ''Siap diambil/dikirim''',
  `waktu_update_status` timestamp NOT NULL DEFAULT current_timestamp(),
  `catatan_tambahan` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data untuk tabel `status_proses`
--

INSERT INTO `status_proses` (`id_status_proses`, `id_detail_pesanan`, `id_pengguna_update`, `status_deskripsi`, `waktu_update_status`, `catatan_tambahan`) VALUES
(1, 1, 2, 'Karpet diterima di workshop', '2025-05-25 10:50:34', 'Kondisi sesuai deskripsi pelanggan.'),
(2, 1, 3, 'Proses pencucian dimulai', '2025-05-25 10:50:34', 'Menggunakan deterjen standar.'),
(3, 2, 3, 'Karpet diterima di workshop', '2025-05-25 10:50:34', NULL),
(4, 2, 3, 'Proses pencucian dimulai', '2025-05-25 10:50:34', 'Treatment khusus noda.'),
(5, 2, 3, 'Pengeringan', '2025-05-25 10:50:34', 'Masuk ruang pengering khusus.'),
(6, 3, 2, 'Karpet diterima di workshop', '2025-05-25 10:50:34', 'Segera diproses karena express.'),
(7, 5, 2, 'Karpet diterima di workshop', '2025-05-25 10:50:34', NULL);

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
-- Indeks untuk tabel `notifikasi`
--
ALTER TABLE `notifikasi`
  ADD PRIMARY KEY (`id_notifikasi`),
  ADD KEY `fk_Notifikasi_Pesanan_idx` (`id_pesanan`),
  ADD KEY `fk_Notifikasi_Pelanggan_idx` (`id_pelanggan`);

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
  ADD KEY `fk_Pesanan_Pengguna_idx` (`id_pengguna_penerima`);

--
-- Indeks untuk tabel `status_proses`
--
ALTER TABLE `status_proses`
  ADD PRIMARY KEY (`id_status_proses`),
  ADD KEY `fk_Status_Proses_Detail_Pesanan_idx` (`id_detail_pesanan`),
  ADD KEY `fk_Status_Proses_Pengguna_idx` (`id_pengguna_update`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `detail_pesanan`
--
ALTER TABLE `detail_pesanan`
  MODIFY `id_detail_pesanan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `layanan`
--
ALTER TABLE `layanan`
  MODIFY `id_layanan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `notifikasi`
--
ALTER TABLE `notifikasi`
  MODIFY `id_notifikasi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `pelanggan`
--
ALTER TABLE `pelanggan`
  MODIFY `id_pelanggan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `pengguna`
--
ALTER TABLE `pengguna`
  MODIFY `id_pengguna` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `pesanan`
--
ALTER TABLE `pesanan`
  MODIFY `id_pesanan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `status_proses`
--
ALTER TABLE `status_proses`
  MODIFY `id_status_proses` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

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
-- Ketidakleluasaan untuk tabel `notifikasi`
--
ALTER TABLE `notifikasi`
  ADD CONSTRAINT `fk_Notifikasi_Pelanggan` FOREIGN KEY (`id_pelanggan`) REFERENCES `pelanggan` (`id_pelanggan`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_Notifikasi_Pesanan` FOREIGN KEY (`id_pesanan`) REFERENCES `pesanan` (`id_pesanan`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `pesanan`
--
ALTER TABLE `pesanan`
  ADD CONSTRAINT `fk_Pesanan_Pelanggan` FOREIGN KEY (`id_pelanggan`) REFERENCES `pelanggan` (`id_pelanggan`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_Pesanan_Pengguna` FOREIGN KEY (`id_pengguna_penerima`) REFERENCES `pengguna` (`id_pengguna`) ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `status_proses`
--
ALTER TABLE `status_proses`
  ADD CONSTRAINT `fk_Status_Proses_Detail_Pesanan` FOREIGN KEY (`id_detail_pesanan`) REFERENCES `detail_pesanan` (`id_detail_pesanan`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_Status_Proses_Pengguna` FOREIGN KEY (`id_pengguna_update`) REFERENCES `pengguna` (`id_pengguna`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
