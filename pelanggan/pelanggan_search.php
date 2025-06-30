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
while($row = mysqli_fetch_assoc($res)) {
    echo '<div class="card mb-2 pelanggan-result-item" style="cursor:pointer;">';
    echo '<div class="card-body py-2 px-3">';
    echo '<div class="d-flex justify-content-between align-items-center">';
    echo '<div>';
    echo '<div class="fw-bold" style="font-size:1.1em;">' . htmlspecialchars($row['nama_pelanggan']) . '</div>';
    echo '<div class="text-muted" style="font-size:0.95em;">' . htmlspecialchars($row['nomor_telepon']) . ' | ' . htmlspecialchars($row['email']) . '</div>';
    echo '<div class="text-muted" style="font-size:0.95em;">' . htmlspecialchars($row['alamat']) . '</div>';
    echo '</div>';
    echo '<div>';
    echo '<a href="?page=pelanggan_detail&id=' . $row['id_pelanggan'] . '" class="btn btn-info btn-sm me-1" target="_blank">Detail</a>';
    echo '<a href="?page=pelanggan_edit&id=' . $row['id_pelanggan'] . '" class="btn btn-warning btn-sm" target="_blank">Edit</a>';
    echo '</div>';
    echo '</div>';
    echo '<input type="hidden" class="id-pelanggan-data" value="' . $row['id_pelanggan'] . '">';
    echo '<input type="hidden" class="nama-pelanggan-data" value="' . htmlspecialchars($row['nama_pelanggan']) . '">';
    echo '</div>';
    echo '</div>';

}
if(mysqli_num_rows($res) == 0) {
    echo '<tr><td colspan="6" class="text-center text-muted">Data tidak ditemukan</td></tr>';
}
