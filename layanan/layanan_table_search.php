<?php
include "../pengaturan/koneksi.php";

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$sql = "SELECT * FROM layanan ";
if ($q) {
    $q_esc = mysqli_real_escape_string($konek, $q);
    $sql .= "WHERE nama_layanan LIKE '%$q_esc%' ";
}
$sql .= "ORDER BY id_layanan DESC";
$result = mysqli_query($konek, $sql);

if (mysqli_num_rows($result) > 0) {
    while($row = mysqli_fetch_assoc($result)) {
        echo '<tr>';
        echo '<td>' . $row['id_layanan'] . '</td>';
        echo '<td>' . htmlspecialchars($row['nama_layanan']) . '</td>';
        echo '<td>Rp ' . number_format($row['harga_per_unit'], 0, ',', '.') . '</td>';
        echo '<td>' . htmlspecialchars($row['satuan']) . '</td>';
        echo '<td>' . htmlspecialchars($row['estimasi_waktu_hari']) . '</td>';
        echo '<td>';
        echo '<a href="?page=layanan_edit&id=' . $row['id_layanan'] . '" class="btn btn-warning btn-sm">Edit</a> ';
        echo '<a href="?page=layanan_delete&id=' . $row['id_layanan'] . '" class="btn btn-danger btn-sm" onclick="return confirm(&apos;Apakah Anda yakin ingin menghapus layanan ini?&apos;);">Hapus</a>';
        echo '</td>';
        echo '</tr>';
    }
} else {
    echo '<tr><td colspan="6" class="text-center">Layanan tidak ditemukan.</td></tr>';
}
?>
