<?php
header('Content-Type: application/json');

// Sertakan koneksi ke database
require_once __DIR__ . '/../pengaturan/koneksi.php'; // File koneksi harus mendefinisikan $konek

// Query untuk menghitung jumlah entri data
$query = "
    SELECT 
        (SELECT COUNT(*) FROM barang_masuk) AS barang_masuk_entries,
        (SELECT COUNT(*) FROM barang_keluar) AS barang_keluar_entries
";

$result = $konek->query($query); // Gunakan $konek untuk query

if ($result->num_rows > 0) {
    $data = $result->fetch_assoc();
    echo json_encode([$data]);
} else {
    echo json_encode(['message' => 'Data tidak ditemukan.']);
}

$konek->close(); // Tutup koneksi
?>
