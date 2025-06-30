<?php
include '../pengaturan/koneksi.php';
$data = ["layanan"=>[], "jumlah"=>[]];
$q = mysqli_query($konek, "SELECT l.nama_layanan, COUNT(*) as jumlah FROM detail_pesanan dp JOIN layanan l ON dp.id_layanan = l.id_layanan GROUP BY l.id_layanan ORDER BY jumlah DESC LIMIT 6");
while($row = mysqli_fetch_assoc($q)) {
    $data["layanan"][] = $row["nama_layanan"];
    $data["jumlah"][] = (int)$row["jumlah"];
}
header('Content-Type: application/json');
echo json_encode($data);
