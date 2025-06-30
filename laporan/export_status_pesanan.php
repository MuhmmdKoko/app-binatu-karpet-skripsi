<?php
session_start();

// Cek akses user
if ($_SESSION['role'] != "Admin") {
    echo '<script>alert("Anda tidak memiliki akses ke halaman ini!");window.location.href="../../index.php";</script>';
    exit;
}

require "../pengaturan/koneksi.php";

// Set header untuk download file Excel
header("Content-type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=Laporan_Status_Pesanan.xls");

$tgl_awal = $_GET['tgl_awal'];
$tgl_akhir = $_GET['tgl_akhir'];
$where = "WHERE DATE(p.tanggal_masuk) BETWEEN '$tgl_awal' AND '$tgl_akhir'";

if(!empty($_GET['status_pesanan'])) {
    $status_pesanan = $_GET['status_pesanan'];
    $where .= " AND p.status_pesanan_umum = '$status_pesanan'";
}

// Query untuk data utama
$query = mysqli_query($konek, "
    SELECT 
        p.status_pesanan_umum as status_pesanan,
        COUNT(p.id_pesanan) as jumlah_pesanan,
        SUM(p.total_harga_keseluruhan) as total_nilai_pesanan,
        AVG(p.total_harga_keseluruhan) as rata_nilai_pesanan
    FROM pesanan p
    $where
    GROUP BY p.status_pesanan_umum
    ORDER BY FIELD(p.status_pesanan_umum, 'Baru', 'Diproses', 'Selesai', 'Diambil', 'Dibatalkan')
");
?>

<h2>Laporan Status Pesanan</h2>
<p>Periode: <?= date('d/m/Y', strtotime($tgl_awal)) ?> - <?= date('d/m/Y', strtotime($tgl_akhir)) ?></p>

<table border="1">
    <thead>
        <tr>
            <th>No</th>
            <th>Status Pesanan</th>
            <th>Jumlah Pesanan</th>
            <th>Total Nilai Pesanan</th>
            <th>Rata-rata Nilai Pesanan</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $no = 1;
        $total_pesanan = 0;
        $total_nilai = 0;

        while($data = mysqli_fetch_array($query)) {
            echo "<tr>";
            echo "<td>" . $no++ . "</td>";
            echo "<td>" . $data['status_pesanan'] . "</td>";
            echo "<td>" . number_format($data['jumlah_pesanan']) . "</td>";
            echo "<td>Rp " . number_format($data['total_nilai_pesanan'], 0, ',', '.') . "</td>";
            echo "<td>Rp " . number_format($data['rata_nilai_pesanan'], 0, ',', '.') . "</td>";
            echo "</tr>";

            $total_pesanan += $data['jumlah_pesanan'];
            $total_nilai += $data['total_nilai_pesanan'];
        }
        ?>
        <tr>
            <td colspan="2" align="center"><strong>Total</strong></td>
            <td><strong><?= number_format($total_pesanan) ?></strong></td>
            <td><strong>Rp <?= number_format($total_nilai, 0, ',', '.') ?></strong></td>
            <td><strong>Rp <?= number_format($total_nilai / ($total_pesanan ?: 1), 0, ',', '.') ?></strong></td>
        </tr>
    </tbody>
</table>

<p>&nbsp;</p>
<p>Ringkasan:</p>
<table border="1">
    <tr>
        <td>Total Status</td>
        <td><?= number_format($no - 1) ?></td>
    </tr>
    <tr>
        <td>Total Pesanan</td>
        <td><?= number_format($total_pesanan) ?></td>
    </tr>
    <tr>
        <td>Total Nilai Pesanan</td>
        <td>Rp <?= number_format($total_nilai, 0, ',', '.') ?></td>
    </tr>
    <tr>
        <td>Rata-rata Pesanan per Status</td>
        <td><?= number_format($total_pesanan / ($no - 1), 1) ?></td>
    </tr>
    <tr>
        <td>Rata-rata Nilai per Status</td>
        <td>Rp <?= number_format($total_nilai / ($no - 1), 0, ',', '.') ?></td>
    </tr>
</table>

<p>Dicetak pada: <?= date('d/m/Y H:i:s') ?></p> 