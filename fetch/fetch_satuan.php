<?php
include "../pengaturan/koneksi.php"; // Pastikan koneksi database benar

$id_barang = $_GET['id_barang'];

// Ambil stok barang dan nama satuan dari tabel barang dan satuan
$query = "SELECT s.nama_satuan FROM barang b JOIN satuan s ON b.satuan_id = s.id_satuan WHERE b.id_barang = '$id_barang'";
$result = mysqli_query($konek, $query);

if ($result && mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    echo json_encode(['satuan' => $row['nama_satuan']]);
} else {
    echo json_encode(['satuan' => 'Satuan']); // Default jika tidak ditemukan
}
?>
