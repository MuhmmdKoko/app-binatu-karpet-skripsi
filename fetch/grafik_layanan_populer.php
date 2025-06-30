<?php
include '../pengaturan/koneksi.php';

header('Content-Type: application/json');

// Query untuk mendapatkan 5 layanan paling populer berdasarkan jumlah pemesanan
$sql = "SELECT l.nama_layanan, COUNT(dp.id_layanan) AS jumlah_pemesanan 
        FROM detail_pesanan dp
        JOIN layanan l ON dp.id_layanan = l.id_layanan
        GROUP BY l.nama_layanan
        ORDER BY jumlah_pemesanan DESC
        LIMIT 5";

$result = mysqli_query($konek, $sql);

if (!$result) {
    http_response_code(500);
    echo json_encode(['error' => 'Query failed: ' . mysqli_error($konek)]);
    exit;
}

$data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $data[] = $row;
}

echo json_encode($data);
?>
