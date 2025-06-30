<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Admin','Karyawan'])) {
    echo '<script>alert("Akses ditolak");window.location="index.php?page=dashboard_read";</script>';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id_promosi']) || !isset($_POST['id_pelanggan']) || !is_array($_POST['id_pelanggan'])) {
    echo '<script>alert("Akses tidak sah atau tidak ada pelanggan yang dipilih.");window.history.back();</script>';
    exit;
}

include "pengaturan/koneksi.php";
include "pengaturan/telegram_notif.php";

// --- Helper Notifikasi ---
function kirim_notifikasi_pelanggan($id_pelanggan, $pesan, $channel = 'Telegram') {
    // Dummy, selalu sukses
    return true;
}
function catat_notifikasi($konek, $id_pesanan, $id_pelanggan, $pesan, $channel = 'Telegram', $tipe = 'Promosi') {
    $pesan_sql = mysqli_real_escape_string($konek, $pesan);
    $channel_sql = mysqli_real_escape_string($konek, $channel);
    $tipe_sql = mysqli_real_escape_string($konek, $tipe);
    $id_pesanan_sql = is_null($id_pesanan) ? 'NULL' : "'".intval($id_pesanan)."'";
    $query = "INSERT INTO notifikasi (id_pesanan, id_pelanggan, pesan, waktu_kirim, channel, tipe_notifikasi, status_pengiriman)
              VALUES ($id_pesanan_sql, '$id_pelanggan', '$pesan_sql', NOW(), '$channel_sql', '$tipe_sql', 'Terkirim')";
    mysqli_query($konek, $query);
}


$id_promosi = intval($_POST['id_promosi']);
$id_pelanggan_array = $_POST['id_pelanggan'];

// Sanitize the array of IDs
$sanitized_ids = array_map('intval', $id_pelanggan_array);
$id_list_string = implode(',', $sanitized_ids);

if (empty($id_list_string)) {
    echo '<script>alert("Tidak ada pelanggan yang dipilih.");window.history.back();</script>';
    exit;
}

$query_promo = mysqli_query($konek, "SELECT * FROM promosi WHERE id_promosi=$id_promosi");
$promo = mysqli_fetch_assoc($query_promo);

if (!$promo) {
    echo '<script>alert(\'Promosi tidak ditemukan!\');window.location=\'?page=promosi_read\';</script>';
    exit;
}

$pesan_broadcast = !empty($promo['isi_pesan']) ? $promo['isi_pesan'] : 'Ada promo baru untuk Anda!';

// Ambil id_telegram dari pelanggan yang dipilih
$query_pelanggan = mysqli_query($konek, "SELECT id_telegram FROM pelanggan WHERE id_pelanggan IN ($id_list_string) AND id_telegram IS NOT NULL AND id_telegram != ''");
$total_terkirim = 0;

while ($pelanggan = mysqli_fetch_assoc($query_pelanggan)) {
    if (function_exists('send_telegram')) {
        send_telegram($pelanggan['id_telegram'], $pesan_broadcast);
        // Catat notifikasi ke tabel notifikasi
        $id_pelanggan = null;
        // Ambil id_pelanggan dari id_telegram
        $q_idp = mysqli_query($konek, "SELECT id_pelanggan FROM pelanggan WHERE id_telegram='".mysqli_real_escape_string($konek, $pelanggan['id_telegram'])."' LIMIT 1");
        if ($r_idp = mysqli_fetch_assoc($q_idp)) {
            $id_pelanggan = $r_idp['id_pelanggan'];
        }
        if ($id_pelanggan) {
            if (kirim_notifikasi_pelanggan($id_pelanggan, $pesan_broadcast, 'Telegram')) {
                catat_notifikasi($konek, null, $id_pelanggan, $pesan_broadcast, 'Telegram', 'Promosi');
            }
        }
        $total_terkirim++;
    }
}

// Update status promosi menjadi 'terkirim' dan catat tanggal kirim
if ($total_terkirim > 0) {
    $tanggal_kirim = date('Y-m-d H:i:s');
    mysqli_query($konek, "UPDATE promosi SET status_promo='terkirim', tanggal_kirim='$tanggal_kirim' WHERE id_promosi=$id_promosi");
}

echo '<script>alert("Broadcast berhasil dikirim ke ' . $total_terkirim . ' pelanggan!");window.location="?page=promosi_read";</script>';
exit;
?>
