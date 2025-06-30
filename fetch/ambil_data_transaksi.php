<?php
// Aktifkan error reporting untuk debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Sertakan file koneksi
include "../pengaturan/koneksi.php";

// Query untuk mengambil data transaksi
$query = "
    SELECT 
        MONTHNAME(tanggal) AS bulan, 
        SUM(CASE WHEN jenis_transaksi = 'masuk' THEN jumlah ELSE 0 END) AS barang_masuk,
        SUM(CASE WHEN jenis_transaksi = 'keluar' THEN jumlah ELSE 0 END) AS barang_keluar
    FROM (
        SELECT DATE(tanggal_masuk) AS tanggal, 'masuk' AS jenis_transaksi, jumlah_masuk AS jumlah 
        FROM barang_masuk
        UNION ALL
        SELECT DATE(tanggal_keluar) AS tanggal, 'keluar' AS jenis_transaksi, jumlah_keluar AS jumlah 
        FROM barang_keluar
    ) transaksi
    WHERE YEAR(tanggal) = YEAR(CURDATE())
    GROUP BY MONTH(tanggal)
    ORDER BY MONTH(tanggal)
";

$result = $konek->query($query);

// Periksa apakah query berhasil
if (!$result) {
    die('Query error: ' . $konek->error);
}

// Ambil hasil query
$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

// Kembalikan hasil sebagai JSON
header('Content-Type: application/json');
echo json_encode($data);

// Tutup koneksi
$konek->close();
?>
