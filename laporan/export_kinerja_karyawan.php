<?php
include "../pengaturan/koneksi.php";

// Set header for Excel download
header("Content-type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=Laporan_Kinerja_Karyawan.xls");

$id_pengguna = $_GET['id_pengguna'];
$tgl_awal = $_GET['tgl_awal'];
$tgl_akhir = $_GET['tgl_akhir'];

// Query untuk data karyawan
$query_karyawan = mysqli_query($konek, "
    SELECT nama_lengkap, jabatan, nomor_telepon
    FROM pengguna 
    WHERE id_pengguna = '$id_pengguna'
");
$karyawan = mysqli_fetch_array($query_karyawan);

// Query untuk data kinerja
$query_kinerja = mysqli_query($konek, "
    SELECT 
        p.*,
        pl.nama_pelanggan,
        GROUP_CONCAT(l.nama_layanan SEPARATOR ', ') as layanan,
        SUM(dp.jumlah) as total_item,
        COUNT(DISTINCT p.id_pesanan) as total_pesanan
    FROM pesanan p
    JOIN detail_pesanan dp ON p.id_pesanan = dp.id_pesanan
    JOIN layanan l ON dp.id_layanan = l.id_layanan
    JOIN pelanggan pl ON p.id_pelanggan = pl.id_pelanggan
    WHERE p.id_pengguna_penerima = '$id_pengguna'
    AND DATE(p.tanggal_masuk) BETWEEN '$tgl_awal' AND '$tgl_akhir'
    GROUP BY p.id_pesanan
    ORDER BY p.tanggal_masuk DESC
");
?>

<h3>Laporan Kinerja Karyawan</h3>
<p>
    Nama Karyawan: <?= $karyawan['nama_lengkap'] ?><br>
    Jabatan: <?= $karyawan['jabatan'] ?><br>
    No. Telepon: <?= $karyawan['nomor_telepon'] ?><br>
    Periode: <?= date('d/m/Y', strtotime($tgl_awal)) ?> - <?= date('d/m/Y', strtotime($tgl_akhir)) ?>
</p>

<table border="1">
    <thead>
        <tr>
            <th>No</th>
            <th>No. Invoice</th>
            <th>Tanggal</th>
            <th>Pelanggan</th>
            <th>Layanan</th>
            <th>Jumlah Item</th>
            <th>Status</th>
            <th>Waktu Selesai</th>
            <th>Ketepatan Waktu</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $no = 1;
        $total_pesanan = 0;
        $total_item = 0;
        $pesanan_tepat_waktu = 0;

        while($data = mysqli_fetch_array($query_kinerja)) {
            $waktu_selesai = strtotime($data['tanggal_selesai']) - strtotime($data['tanggal_masuk']);
            $waktu_selesai_jam = round($waktu_selesai / (60 * 60));
            $tepat_waktu = $waktu_selesai_jam <= 48 ? 'Tepat Waktu' : 'Terlambat';
            
            if($tepat_waktu == 'Tepat Waktu') {
                $pesanan_tepat_waktu++;
            }

            echo "<tr>";
            echo "<td>" . $no++ . "</td>";
            echo "<td>" . $data['nomor_invoice'] . "</td>";
            echo "<td>" . date('d/m/Y H:i', strtotime($data['tanggal_masuk'])) . "</td>";
            echo "<td>" . $data['nama_pelanggan'] . "</td>";
            echo "<td>" . $data['layanan'] . "</td>";
            echo "<td>" . $data['total_item'] . "</td>";
            echo "<td>" . $data['status_pesanan'] . "</td>";
            echo "<td>" . date('d/m/Y H:i', strtotime($data['tanggal_selesai'])) . "</td>";
            echo "<td>" . $tepat_waktu . " (" . $waktu_selesai_jam . " jam)</td>";
            echo "</tr>";

            $total_pesanan++;
            $total_item += $data['total_item'];
        }
        ?>
    </tbody>
</table>

<p>
    Total Pesanan: <?= number_format($total_pesanan) ?><br>
    Total Item: <?= number_format($total_item) ?><br>
    Persentase Ketepatan Waktu: <?= $total_pesanan > 0 ? round(($pesanan_tepat_waktu / $total_pesanan) * 100) : 0 ?>%
</p> 