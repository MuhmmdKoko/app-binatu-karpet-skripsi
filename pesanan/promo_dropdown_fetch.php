<?php
// Endpoint AJAX untuk mengambil daftar promo aktif dan promo yang dipakai pesanan (meski tidak aktif)
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include '../pengaturan/koneksi.php';

$kode_promo_pesanan = isset($_GET['kode_promo_pesanan']) ? trim($_GET['kode_promo_pesanan']) : '';
$promo_list = [];
$now = date('Y-m-d H:i:s');

// Ambil semua promo aktif
$q = mysqli_query($konek, "SELECT * FROM promosi WHERE status_promo='aktif' AND (tanggal_mulai IS NULL OR tanggal_mulai <= '$now') AND (tanggal_berakhir IS NULL OR tanggal_berakhir >= '$now') ORDER BY judul");
while($row = mysqli_fetch_assoc($q)) {
    $promo_list[] = [
        'id_promosi' => $row['id_promosi'],
        'kode_promo' => $row['kode_promo'],
        'judul' => $row['judul'],
        'status' => 'aktif',
    ];
}
// Jika kode promo pesanan ada dan tidak aktif, tambahkan ke list dengan status tidak_aktif
if($kode_promo_pesanan !== '') {
    $exists = false;
    foreach($promo_list as $p) {
        if(strtolower($p['kode_promo']) === strtolower($kode_promo_pesanan)) {
            $exists = true;
            break;
        }
    }
    if(!$exists) {
        $q2 = mysqli_query($konek, "SELECT * FROM promosi WHERE kode_promo='".mysqli_real_escape_string($konek,$kode_promo_pesanan)."' LIMIT 1");
        if($row2 = mysqli_fetch_assoc($q2)) {
            $promo_list[] = [
                'id_promosi' => $row2['id_promosi'],
                'kode_promo' => $row2['kode_promo'],
                'judul' => $row2['judul'],
                'status' => 'tidak_aktif',
            ];
        }
    }
}
echo json_encode($promo_list);
