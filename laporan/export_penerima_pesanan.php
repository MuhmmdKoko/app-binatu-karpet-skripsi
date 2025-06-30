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
header("Content-Disposition: attachment; filename=Laporan_Penerima_Pesanan.xls");

$tgl_awal = $_GET['tgl_awal'];
$tgl_akhir = $_GET['tgl_akhir'];

// Query untuk data utama
$query = mysqli_query($konek, "
    SELECT 
        p.id_pengguna_penerima,
        pg.nama_lengkap,
        DATE(p.tanggal_masuk) as tanggal,
        COUNT(*) as jumlah_pesanan,
        SUM(p.total_harga_keseluruhan) as total_nilai
    FROM pesanan p
    JOIN pengguna pg ON p.id_pengguna_penerima = pg.id_pengguna
    WHERE DATE(p.tanggal_masuk) BETWEEN '$tgl_awal' AND '$tgl_akhir'
    GROUP BY p.id_pengguna_penerima, DATE(p.tanggal_masuk)
    ORDER BY tanggal DESC, jumlah_pesanan DESC
");
?>

<h2>Laporan Penerima Pesanan</h2>
<p>Periode: <?= date('d/m/Y', strtotime($tgl_awal)) ?> - <?= date('d/m/Y', strtotime($tgl_akhir)) ?></p>

<table border="1">
    <thead>
        <tr>
            <th>No</th>
            <th>Tanggal</th>
            <th>Nama Karyawan</th>
            <th>Jumlah Pesanan</th>
            <th>Total Nilai Pesanan</th>
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
            echo "<td>" . date('d/m/Y', strtotime($data['tanggal'])) . "</td>";
            echo "<td>" . $data['nama_lengkap'] . "</td>";
            echo "<td>" . number_format($data['jumlah_pesanan']) . "</td>";
            echo "<td>Rp " . number_format($data['total_nilai'], 0, ',', '.') . "</td>";
            echo "</tr>";

            $total_pesanan += $data['jumlah_pesanan'];
            $total_nilai += $data['total_nilai'];
        }
        ?>
        <tr>
            <td colspan="3" align="center"><strong>Total</strong></td>
            <td><strong><?= number_format($total_pesanan) ?></strong></td>
            <td><strong>Rp <?= number_format($total_nilai, 0, ',', '.') ?></strong></td>
        </tr>
    </tbody>
</table>

<p>Dicetak pada: <?= date('d/m/Y H:i:s') ?></p> 