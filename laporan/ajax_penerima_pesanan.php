<?php
// AJAX endpoint untuk filter laporan penerima pesanan tanpa reload
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../pengaturan/koneksi.php';

header('Content-Type: application/json');

$tgl_awal = isset($_POST['tgl_awal']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['tgl_awal']) ? $_POST['tgl_awal'] : date('Y-m-01');
$tgl_akhir = isset($_POST['tgl_akhir']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['tgl_akhir']) ? $_POST['tgl_akhir'] : date('Y-m-d');
$id_pengguna = isset($_POST['id_pengguna']) ? $_POST['id_pengguna'] : '';

$where = "WHERE DATE(p.tanggal_masuk) BETWEEN '$tgl_awal' AND '$tgl_akhir'";
if ($id_pengguna && ctype_digit($id_pengguna)) {
    $id_pengguna = mysqli_real_escape_string($konek, $id_pengguna);
    $where .= " AND p.id_pengguna_penerima = '$id_pengguna'";
}

$query = mysqli_query($konek, "
    SELECT 
        pg.id_pengguna,
        pg.nama_lengkap,
        COUNT(p.id_pesanan) as jumlah_pesanan,
        SUM(COALESCE(p.total_harga_keseluruhan, 0)) as total_nilai_pesanan,
        AVG(COALESCE(p.total_harga_keseluruhan, 0)) as rata_nilai_pesanan
    FROM pengguna pg
    LEFT JOIN pesanan p ON pg.id_pengguna = p.id_pengguna_penerima
    $where
    GROUP BY pg.id_pengguna
    ORDER BY jumlah_pesanan DESC
");

$data = [];
$total_pesanan = 0;
$total_nilai = 0;

if ($query) {
    while($row = mysqli_fetch_assoc($query)) {
        $data[] = [
            'id_pengguna' => $row['id_pengguna'],
            'nama_lengkap' => $row['nama_lengkap'],
            'jumlah_pesanan' => (int)$row['jumlah_pesanan'],
            'total_nilai_pesanan' => (int)$row['total_nilai_pesanan'],
            'rata_nilai_pesanan' => (int)$row['rata_nilai_pesanan'],
        ];
        $total_pesanan += $row['jumlah_pesanan'];
        $total_nilai += $row['total_nilai_pesanan'];
    }
}

$total_penerima = count($data);
$response = [
    'data' => $data,
    'total_penerima' => $total_penerima,
    'total_pesanan' => $total_pesanan,
    'total_nilai' => $total_nilai,
    'rata_per_penerima' => $total_penerima > 0 ? ($total_pesanan / $total_penerima) : 0
];
echo json_encode($response);
