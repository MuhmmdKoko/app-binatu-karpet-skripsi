<?php
// Set header for Excel download
header("Content-type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=Laporan_Layanan_Karpet.xls");

$tgl_awal = $_GET['tgl_awal'];
$tgl_akhir = $_GET['tgl_akhir'];

// Query untuk data pesanan karpet
$query_karpet = mysqli_query($konek, "
    SELECT 
        p.*,
        pl.nama_pelanggan,
        pl.nomor_telepon,
        pl.alamat,
        l.nama_layanan,
        dp.jumlah,
        dp.harga,
        dp.subtotal,
        pg.nama_lengkap as penerima
    FROM pesanan p
    JOIN detail_pesanan dp ON p.id_pesanan = dp.id_pesanan
    JOIN layanan l ON dp.id_layanan = l.id_layanan
    JOIN pelanggan pl ON p.id_pelanggan = pl.id_pelanggan
    LEFT JOIN pengguna pg ON p.id_pengguna_penerima = pg.id_pengguna
    WHERE l.nama_layanan LIKE '%Karpet%'
    AND DATE(p.tanggal_masuk) BETWEEN '$tgl_awal' AND '$tgl_akhir'
    ORDER BY p.tanggal_masuk DESC
");
?>

<h2>BERKAT LAUNDRY</h2>
<h3>Laporan Layanan Karpet</h3>
<p>Periode: <?= date('d/m/Y', strtotime($tgl_awal)) ?> - <?= date('d/m/Y', strtotime($tgl_akhir)) ?></p>

<table border="1">
    <thead>
        <tr>
            <th>No</th>
            <th>No. Invoice</th>
            <th>Tanggal</th>
            <th>Pelanggan</th>
            <th>No. Telepon</th>
            <th>Alamat</th>
            <th>Layanan</th>
            <th>Jumlah</th>
            <th>Harga</th>
            <th>Subtotal</th>
            <th>Status</th>
            <th>Penerima</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $no = 1;
        $total_karpet = 0;
        $total_nilai = 0;

        while($data = mysqli_fetch_array($query_karpet)) {
            echo "<tr>";
            echo "<td>" . $no++ . "</td>";
            echo "<td>" . $data['nomor_invoice'] . "</td>";
            echo "<td>" . date('d/m/Y H:i', strtotime($data['tanggal_masuk'])) . "</td>";
            echo "<td>" . $data['nama_pelanggan'] . "</td>";
            echo "<td>" . $data['nomor_telepon'] . "</td>";
            echo "<td>" . $data['alamat'] . "</td>";
            echo "<td>" . $data['nama_layanan'] . "</td>";
            echo "<td>" . $data['jumlah'] . "</td>";
            echo "<td>Rp " . number_format($data['harga'], 0, ',', '.') . "</td>";
            echo "<td>Rp " . number_format($data['subtotal'], 0, ',', '.') . "</td>";
            echo "<td>" . $data['status_pesanan'] . "</td>";
            echo "<td>" . $data['penerima'] . "</td>";
            echo "</tr>";

            $total_karpet += $data['jumlah'];
            $total_nilai += $data['subtotal'];
        }
        ?>
        <tr>
            <td colspan="9" align="center"><strong>Total</strong></td>
            <td><strong>Rp <?= number_format($total_nilai, 0, ',', '.') ?></strong></td>
            <td colspan="2"></td>
        </tr>
    </tbody>
</table>

<p>
    <strong>Total Karpet: </strong><?= number_format($total_karpet) ?><br>
    <strong>Total Nilai Layanan: </strong>Rp <?= number_format($total_nilai, 0, ',', '.') ?>
</p> 