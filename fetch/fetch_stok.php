<?php
include "../pengaturan/koneksi.php"; // Pastikan koneksi database benar

$id_barang = $_GET['id_barang'];

// Ambil stok barang dari tabel barang
$query = "SELECT stok FROM barang WHERE id_barang = '$id_barang'";
$result = mysqli_query($konek, $query);

if ($result && mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    echo json_encode(['stok' => $row['stok']]);
} else {
    echo json_encode(['stok' => 0]);
}
?>