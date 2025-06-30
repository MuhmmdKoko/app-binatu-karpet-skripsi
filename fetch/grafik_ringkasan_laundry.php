<?php
include '../pengaturan/koneksi.php';
$tahun = date('Y');
$sql = "SELECT MONTH(tanggal_masuk) as bulan, COUNT(*) as jumlah_pesanan, SUM(total_harga_keseluruhan) as pendapatan
        FROM pesanan
        WHERE YEAR(tanggal_masuk) = '$tahun'
        GROUP BY MONTH(tanggal_masuk)";
$result = mysqli_query($konek, $sql);
$data = [];
$bulanNama = [1=>'Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
for ($i=1;$i<=12;$i++) $data[$i] = ['bulan'=>$bulanNama[$i],'jumlah_pesanan'=>0,'pendapatan'=>0];
while($row = mysqli_fetch_assoc($result)){
    $data[(int)$row['bulan']] = [
        'bulan' => $bulanNama[(int)$row['bulan']],
        'jumlah_pesanan' => (int)$row['jumlah_pesanan'],
        'pendapatan' => (int)$row['pendapatan']
    ];
}
echo json_encode(array_values($data));
