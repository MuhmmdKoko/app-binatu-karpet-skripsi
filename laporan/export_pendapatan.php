<?php
session_start();

// Cek akses user
if ($_SESSION['role'] != "Admin") {
    echo '<script>alert("Anda tidak memiliki akses ke halaman ini!");window.location.href="../../index.php";</script>';
    exit;
}

include "../pengaturan/koneksi.php";

// Set header untuk download file Excel
header("Content-type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=Laporan_Pendapatan.xls");

$tgl_awal = $_GET['tgl_awal'];
$tgl_akhir = $_GET['tgl_akhir'];
$where = "WHERE DATE(p.tanggal_masuk) BETWEEN '$tgl_awal' AND '$tgl_akhir'";

if(!empty($_GET['status_pembayaran'])) {
    $status_pembayaran = $_GET['status_pembayaran'];
    $where .= " AND p.status_pembayaran = '$status_pembayaran'";
}

// Query untuk data per hari
$query = mysqli_query($konek, "
    SELECT 
        DATE(p.tanggal_masuk) as tanggal,
        COUNT(p.id_pesanan) as jumlah_pesanan,
        SUM(p.total_harga_keseluruhan) as total_pendapatan,
        SUM(CASE WHEN p.status_pembayaran = 'Lunas' THEN p.total_harga_keseluruhan ELSE 0 END) as pendapatan_lunas,
        SUM(CASE WHEN p.status_pembayaran = 'DP' THEN p.nominal_pembayaran ELSE 0 END) as pendapatan_dp,
        SUM(CASE WHEN p.status_pembayaran = 'DP' THEN (p.total_harga_keseluruhan - p.nominal_pembayaran) ELSE 0 END) as sisa_dp,
        SUM(CASE WHEN p.status_pembayaran = 'Belum Lunas' THEN p.total_harga_keseluruhan ELSE 0 END) as pendapatan_belum_lunas
    FROM pesanan p
    $where
    GROUP BY DATE(p.tanggal_masuk)
    ORDER BY tanggal DESC
");
?>

<h2>Laporan Pendapatan</h2>
<p>Periode: <?= date('d/m/Y', strtotime($tgl_awal)) ?> - <?= date('d/m/Y', strtotime($tgl_akhir)) ?></p>
<?php if(!empty($_GET['status_pembayaran'])): ?>
    <p>Status Pembayaran: <?= $_GET['status_pembayaran'] ?></p>
<?php endif; ?>

<table border="1">
    <thead>
        <tr>
            <th>No</th>
            <th>Tanggal</th>
            <th>Jumlah Pesanan</th>
            <th>Total Pendapatan</th>
            <th>Pendapatan Lunas</th>
            <th>Pendapatan DP</th>
            <th>Pendapatan Belum Lunas</th>
            <th>Pendapatan Belum Diterima</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $no = 1;
        $total_pesanan = 0;
        $total_pendapatan = 0;
        $total_lunas = 0;
        $total_dp = 0;
        $total_belum_lunas = 0;
        $total_sisa_dp = 0;

        while($data = mysqli_fetch_array($query)) {
            echo "<tr>";
            echo "<td>" . $no++ . "</td>";
            echo "<td>" . date('d/m/Y', strtotime($data['tanggal'])) . "</td>";
            echo "<td>" . number_format($data['jumlah_pesanan']) . "</td>";
            echo "<td>Rp " . number_format($data['total_pendapatan'], 0, ',', '.') . "</td>";
            echo "<td>Rp " . number_format($data['pendapatan_lunas'], 0, ',', '.') . "</td>";
            echo "<td>Rp " . number_format($data['pendapatan_dp'], 0, ',', '.') . "</td>";
            echo "<td>Rp " . number_format($data['pendapatan_belum_lunas'], 0, ',', '.') . "</td>";
            echo "<td>Rp " . number_format(($data['pendapatan_belum_lunas'] + $data['sisa_dp']), 0, ',', '.') . "</td>";
            echo "</tr>";

            $total_pesanan += $data['jumlah_pesanan'];
            $total_pendapatan += $data['total_pendapatan'];
            $total_lunas += $data['pendapatan_lunas'];
            $total_dp += $data['pendapatan_dp'];
            $total_belum_lunas += $data['pendapatan_belum_lunas'];
            $total_sisa_dp += $data['sisa_dp'];
        }
        ?>
        <tr>
            <td colspan="2" align="center"><strong>Total</strong></td>
            <td><strong><?= number_format($total_pesanan) ?></strong></td>
            <td><strong>Rp <?= number_format($total_pendapatan, 0, ',', '.') ?></strong></td>
            <td><strong>Rp <?= number_format($total_lunas, 0, ',', '.') ?></strong></td>
            <td><strong>Rp <?= number_format($total_dp, 0, ',', '.') ?></strong></td>
            <td><strong>Rp <?= number_format($total_belum_lunas, 0, ',', '.') ?></strong></td>
            <td><strong>Rp <?= number_format($total_belum_lunas + $total_sisa_dp, 0, ',', '.') ?></strong></td>
        </tr>
    </tbody>
</table>

<p>&nbsp;</p>
<p>Ringkasan:</p>
<table border="1">
    <tr>
        <td>Total Hari</td>
        <td><?= number_format($no - 1) ?></td>
    </tr>
    <tr>
        <td>Total Pesanan</td>
        <td><?= number_format($total_pesanan) ?></td>
    </tr>
    <tr>
        <td>Total Pendapatan</td>
        <td>Rp <?= number_format($total_pendapatan, 0, ',', '.') ?></td>
    </tr>
    <tr>
        <td>Total Pendapatan Diterima</td>
        <td>Rp <?= number_format($total_lunas + $total_dp, 0, ',', '.') ?></td>
    </tr>
    <tr>
        <td>Total Pendapatan Belum Diterima</td>
        <td>Rp <?= number_format($total_belum_lunas + $total_sisa_dp, 0, ',', '.') ?></td>
    </tr>
    <tr>
        <td>Rata-rata Pesanan per Hari</td>
        <td><?= number_format($total_pesanan / ($no - 1), 1) ?></td>
    </tr>
    <tr>
        <td>Rata-rata Pendapatan per Hari</td>
        <td>Rp <?= number_format($total_pendapatan / ($no - 1), 0, ',', '.') ?></td>
    </tr>
</table>

<p>Dicetak pada: <?= date('d/m/Y H:i:s') ?></p> 