<?php
include "../pengaturan/koneksi.php";

$nama_penerima = $_POST['nama_penerima'];
$tgl_awal = $_POST['tgl_awal'];
$tgl_akhir = $_POST['tgl_akhir'];

// Query untuk data pesanan
$query = mysqli_query($konek, "
    SELECT 
        p.*,
        pl.nama_pelanggan,
        GROUP_CONCAT(l.nama_layanan SEPARATOR ', ') as layanan,
        MAX(dp.status_item_terkini) as status_item_terkini
    FROM pesanan p
    JOIN pelanggan pl ON p.id_pelanggan = pl.id_pelanggan
    JOIN detail_pesanan dp ON p.id_pesanan = dp.id_pesanan
    JOIN layanan l ON dp.id_layanan = l.id_layanan
    JOIN pengguna pg ON p.id_pengguna_penerima = pg.id_pengguna
    WHERE pg.nama_lengkap = '$nama_penerima'
    AND DATE(p.tanggal_masuk) BETWEEN '$tgl_awal' AND '$tgl_akhir'
    GROUP BY p.id_pesanan
    ORDER BY p.tanggal_masuk DESC
");
?>

<h6>Detail Pesanan - <?= htmlspecialchars($nama_penerima) ?></h6>
<p>Periode: <?= date('d/m/Y', strtotime($tgl_awal)) ?> - <?= date('d/m/Y', strtotime($tgl_akhir)) ?></p>

<div class="table-responsive">
    <table class="table table-bordered table-hover">
        <thead>
            <tr>
                <th>No</th>
                <th>No. Invoice</th>
                <th>Tanggal</th>
                <th>Pelanggan</th>
                <th>Layanan</th>
                <th>Total</th>
                <th>Status</th>
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
                echo "<td>" . htmlspecialchars($data['nomor_invoice']) . "</td>";
                echo "<td>" . date('d/m/Y H:i', strtotime($data['tanggal_masuk'])) . "</td>";
                echo "<td>" . htmlspecialchars($data['nama_pelanggan']) . "</td>";
                echo "<td>" . htmlspecialchars($data['layanan']) . "</td>";
                echo "<td>Rp " . number_format($data['total_harga_keseluruhan'], 0, ',', '.') . "</td>";
                echo "<td>" . (isset($data['status_item_terkini']) ? htmlspecialchars($data['status_item_terkini']) : '-') . "</td>";
                echo "</tr>";

                $total_pesanan++;
                $total_nilai += $data['total_harga_keseluruhan'];
            }
            ?>
            <tr>
                <td colspan="5" align="center"><strong>Total</strong></td>
                <td><strong>Rp <?= number_format($total_nilai, 0, ',', '.') ?></strong></td>
                <td></td>
            </tr>
        </tbody>
    </table>
</div>

<div class="row mt-3">
    <div class="col-md-6">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <h6 class="card-title">Total Pesanan</h6>
                <h4><?= number_format($total_pesanan) ?></h4>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card bg-success text-white">
            <div class="card-body">
                <h6 class="card-title">Total Nilai Pesanan</h6>
                <h4>Rp <?= number_format($total_nilai, 0, ',', '.') ?></h4>
            </div>
        </div>
    </div>
</div> 