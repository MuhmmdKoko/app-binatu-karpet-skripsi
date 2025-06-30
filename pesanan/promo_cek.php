<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
header('Content-Type: application/json');
include '../pengaturan/koneksi.php';

$kode = isset($_POST['kode']) ? trim($_POST['kode']) : '';
$total = isset($_POST['total']) ? floatval($_POST['total']) : 0;

if ($kode === '') {
    echo json_encode(['status'=>'error','msg'=>'Kode promo wajib diisi']);
    exit;
}

$now = date('Y-m-d H:i:s');
$q = mysqli_query($konek, "SELECT * FROM promosi WHERE kode_promo='$kode' AND status_promo='aktif' AND (tanggal_mulai IS NULL OR tanggal_mulai <= '$now') AND (tanggal_berakhir IS NULL OR tanggal_berakhir >= '$now') LIMIT 1;");
if (!$row = mysqli_fetch_assoc($q)) {
    echo json_encode(['status'=>'error','msg'=>'Kode promo tidak ditemukan/aktif']);
    exit;
}

// Cek syarat minimal transaksi
if ($row['syarat_min_transaksi'] > 0 && $total < $row['syarat_min_transaksi']) {
    echo json_encode(['status'=>'error','msg'=>'Minimal transaksi untuk promo ini: Rp'.number_format($row['syarat_min_transaksi'],0,',','.')]);
    exit;
}

// Hitung diskon
$diskon = 0;
$tipe = $row['tipe_promo'];
$nilai = $row['nilai_promo'];
if ($tipe == 'nominal') {
    $diskon = $nilai;
} elseif ($tipe == 'persen') {
    $diskon = $total * $nilai / 100;
    // Jika ada batas maksimal diskon, tambahkan logika di sini
    if (!empty($row['nilai_promo_max']) && $row['nilai_promo_max'] > 0 && $diskon > $row['nilai_promo_max']) {
        $diskon = $row['nilai_promo_max'];
    }
}

// Pastikan diskon tidak melebihi total
if ($diskon > $total) $diskon = $total;

// Kirim data promo
$res = [
    'status' => 'ok',
    'id_promosi' => $row['id_promosi'],
    'judul' => $row['judul'],
    'kode_promo' => $row['kode_promo'],
    'tipe_promo' => $tipe,
    'nilai_promo' => $nilai,
    'diskon' => $diskon,
    'syarat_min_transaksi' => $row['syarat_min_transaksi'],
    'msg' => 'Promo berhasil diterapkan',
];
echo json_encode($res);
