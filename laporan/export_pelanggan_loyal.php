<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Cek akses user
if ($_SESSION['role'] != "Admin") {
    echo '<script>alert("Anda tidak memiliki akses ke halaman ini!");window.location.href="../../index.php";</script>';
    exit;
}

include "../pengaturan/koneksi.php";

$min_pesanan = isset($_GET['min_pesanan']) ? (int)$_GET['min_pesanan'] : 3;
$total_nilai_minimal = isset($_GET['total_nilai_minimal']) ? (int)$_GET['total_nilai_minimal'] : 0;
$terakhir_transaksi = isset($_GET['terakhir_transaksi']) ? $_GET['terakhir_transaksi'] : '';
$terakhir_transaksi_setelah = isset($_GET['terakhir_transaksi_setelah']) ? $_GET['terakhir_transaksi_setelah'] : '';

// ... existing code ...