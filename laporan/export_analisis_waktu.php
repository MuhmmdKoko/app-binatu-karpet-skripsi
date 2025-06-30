<?php
include "../pengaturan/koneksi.php";

// Set header for Excel download
header("Content-type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=Laporan_Analisis_Waktu.xls");

$tgl_awal = $_GET['tgl_awal'];
$tgl_akhir = $_GET['tgl_akhir'];

// Query untuk data analisis waktu
$query_waktu = mysqli_query($konek, "
    SELECT 
        p.*,
        pl.nama_pelanggan,
        GROUP_CONCAT(l.nama_layanan SEPARATOR ', ') as layanan,
        pg.nama_lengkap as penerima,
        TIMESTAMPDIFF(HOUR, p.tanggal_masuk, p.tanggal_selesai) as durasi_jam
    FROM pesanan p
    JOIN detail_pesanan dp ON p.id_pesanan = dp.id_pesanan
    JOIN layanan l ON dp.id_layanan = l.id_layanan
    JOIN pelanggan pl ON p.id_pelanggan = pl.id_pelanggan
    LEFT JOIN pengguna pg ON p.id_pengguna_penerima = pg.id_pengguna
    WHERE DATE(p.tanggal_masuk) BETWEEN '$tgl_awal' AND '$tgl_akhir'
    AND p.status_pesanan = 'Selesai'
    GROUP BY p.id_pesanan
    ORDER BY durasi_jam DESC
");
?>

<h3>Laporan Analisis Waktu Pengerjaan</h3>
<p>Periode: <?= date('d/m/Y', strtotime($tgl_awal)) ?> - <?= date('d/m/Y', strtotime($tgl_akhir)) ?></p>

<table border="1">
    <thead>
        <tr>
            <th>No</th>
            <th>No. Invoice</th>
            <th>Tanggal Masuk</th>
            <th>Tanggal Selesai</th>
            <th>Durasi (Jam)</th>
            <th>Pelanggan</th>
            <th>Layanan</th>
            <th>Status Ketepatan</th>
            <th>Penerima</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $no = 1;
        $total_pesanan = 0;
        $total_durasi = 0;
        $pesanan_tepat_waktu = 0;

        while($data = mysqli_fetch_array($query_waktu)) {
            $durasi = $data['durasi_jam'];
            $tepat_waktu = $durasi <= 48 ? 'Tepat Waktu' : 'Terlambat';
            
            if($tepat_waktu == 'Tepat Waktu') {
                $pesanan_tepat_waktu++;
            }

            echo "<tr>";
            echo "<td>" . $no++ . "</td>";
            echo "<td>" . $data['nomor_invoice'] . "</td>";
            echo "<td>" . date('d/m/Y H:i', strtotime($data['tanggal_masuk'])) . "</td>";
            echo "<td>" . date('d/m/Y H:i', strtotime($data['tanggal_selesai'])) . "</td>";
            echo "<td>" . $durasi . "</td>";
            echo "<td>" . $data['nama_pelanggan'] . "</td>";
            echo "<td>" . $data['layanan'] . "</td>";
            echo "<td>" . $tepat_waktu . "</td>";
            echo "<td>" . $data['penerima'] . "</td>";
            echo "</tr>";

            $total_pesanan++;
            $total_durasi += $durasi;
        }
        
        $rata_rata_durasi = $total_pesanan > 0 ? round($total_durasi / $total_pesanan, 1) : 0;
        ?>
    </tbody>
</table>

<p>
    Total Pesanan: <?= number_format($total_pesanan) ?><br>
    Rata-rata Durasi: <?= $rata_rata_durasi ?> jam<br>
    Persentase Ketepatan Waktu: <?= $total_pesanan > 0 ? round(($pesanan_tepat_waktu / $total_pesanan) * 100) : 0 ?>%
</p> 