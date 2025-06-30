<?php
include '../pengaturan/koneksi.php';

header('Content-Type: application/json');

// Query untuk mendapatkan komposisi status pembayaran
$sql = "SELECT status_pembayaran, COUNT(id_pesanan) AS jumlah 
        FROM pesanan 
        GROUP BY status_pembayaran";

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
