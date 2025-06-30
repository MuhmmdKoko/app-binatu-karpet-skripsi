<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Admin','Karyawan'])) {
    echo '<script>alert("Akses ditolak");window.location="index.php?page=dashboard_read";</script>';
    exit;
}
include "pengaturan/koneksi.php";
include "pengaturan/telegram_notif.php";

$id = intval($_GET['id'] ?? 0);
$q = mysqli_query($konek, "SELECT * FROM promosi WHERE id_promosi=$id");
$promo = mysqli_fetch_assoc($q);
if (!$promo) {
    echo '<script>alert(\'Promosi tidak ditemukan!\');window.location=\'?page=promosi_read\';</script>';
    exit;
}
$isi_pesan = $promo['isi_pesan'];

// Ambil semua pelanggan yang punya id_telegram
$q_pel = mysqli_query($konek, "SELECT id_telegram FROM pelanggan WHERE id_telegram IS NOT NULL AND id_telegram != ''");
$total = 0;
while ($row = mysqli_fetch_assoc($q_pel)) {
    send_telegram($row['id_telegram'], $isi_pesan);
    $total++;
}
// Update status dan tanggal_kirim
mysqli_query($konek, "UPDATE promosi SET status='terkirim', tanggal_kirim=NOW() WHERE id_promosi=$id");

echo '<script>alert("Broadcast berhasil ke ' . $total . ' pelanggan!");window.location="?page=promosi_read";</script>';
exit;
?>
