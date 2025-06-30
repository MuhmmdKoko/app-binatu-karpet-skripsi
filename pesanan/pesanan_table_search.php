<?php
include "../pengaturan/koneksi.php";

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$sql = "SELECT p.*, pl.nama_pelanggan, pl.nomor_telepon FROM pesanan p JOIN pelanggan pl ON p.id_pelanggan=pl.id_pelanggan ";

if ($q) {
    $q_esc = mysqli_real_escape_string($konek, $q);
    $sql .= "WHERE p.nomor_invoice LIKE '%$q_esc%' OR pl.nama_pelanggan LIKE '%$q_esc%' OR pl.nomor_telepon LIKE '%$q_esc%' ";
}

$sql .= "ORDER BY p.id_pesanan DESC LIMIT 50";
$res = mysqli_query($konek, $sql);

$no = 1;
if (mysqli_num_rows($res) > 0) {
    while($row = mysqli_fetch_assoc($res)) {
        echo '<tr>';
        echo '<td>' . $no++ . '</td>';
        echo '<td>' . htmlspecialchars($row['nomor_invoice']) . '</td>';
        echo '<td>' . htmlspecialchars($row['nama_pelanggan']) . '<br><small>' . htmlspecialchars($row['nomor_telepon']) . '</small></td>';
        echo '<td>' . htmlspecialchars($row['tanggal_masuk']) . '</td>';
        echo '<td>' . htmlspecialchars($row['tanggal_estimasi_selesai']) . '</td>';
        echo '<td><span class="badge bg-info text-dark">' . htmlspecialchars($row['status_pesanan_umum']) . '</span></td>';
        echo '<td><span class="badge bg-' . ($row['status_pembayaran']==='Lunas'?'success':'warning') . ' text-dark">' . htmlspecialchars($row['status_pembayaran']) . '</span></td>';
        $total_tampil = (!empty($row['diskon']) && $row['diskon'] > 0) ? $row['total_setelah_diskon'] : $row['total_harga_keseluruhan'];
echo '<td>Rp' . number_format($total_tampil,2,',','.') . '</td>';
        echo '<td>';
        echo '<a href="?page=pesanan_detail&id=' . $row['id_pesanan'] . '" class="btn btn-info btn-sm">Detail</a> ';
        echo '<a href="?page=pesanan_cetak_nota&id=' . $row['id_pesanan'] . '" target="_blank" class="btn btn-secondary btn-sm">Cetak Nota</a> ';
        echo '<a href="?page=pesanan_status_proses&id=' . $row['id_pesanan'] . '" class="btn btn-warning btn-sm">Status</a>';
        echo '</td>';
        echo '</tr>';
    }
} else {
    echo '<tr><td colspan="9" class="text-center text-muted">Data tidak ditemukan</td></tr>';
}
?>
