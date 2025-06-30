<?php
include "../pengaturan/koneksi.php";

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$sql = "SELECT * FROM pelanggan ";
if ($q) {
    $q_esc = mysqli_real_escape_string($konek, $q);
    $sql .= "WHERE nama_pelanggan LIKE '%$q_esc%' OR nomor_telepon LIKE '%$q_esc%' ";
}
$sql .= "ORDER BY id_pelanggan DESC LIMIT 30";
$res = mysqli_query($konek, $sql);

if (mysqli_num_rows($res) > 0) {
    while($row = mysqli_fetch_assoc($res)) {
        echo '<tr>';
        echo '<td>' . $row['id_pelanggan'] . '</td>';
        echo '<td>' . htmlspecialchars($row['nama_pelanggan']) . '</td>';
        echo '<td>' . htmlspecialchars($row['nomor_telepon']) . '</td>';
        echo '<td>' . htmlspecialchars($row['alamat']) . '</td>';
        echo '<td>' . htmlspecialchars($row['email']) . '</td>';
        echo '<td>';
        echo '<a href="?page=pelanggan_detail&id=' . $row['id_pelanggan'] . '" class="btn btn-info btn-sm">Detail</a> ';
        echo '<a href="?page=pelanggan_edit&id=' . $row['id_pelanggan'] . '" class="btn btn-warning btn-sm">Edit</a>';
        echo '</td>';
        echo '</tr>';
    }
} else {
    echo '<tr><td colspan="6" class="text-center">Data tidak ditemukan</td></tr>';
}
?>
