<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require "../pengaturan/koneksi.php";

// Debug only:
// ini_set('display_errors', 1);
// error_reporting(E_ALL);

// Validate and sanitize input
$tgl_awal = isset($_POST['tgl_awal']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['tgl_awal']) ? $_POST['tgl_awal'] : date('Y-m-01');
$tgl_akhir = isset($_POST['tgl_akhir']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['tgl_akhir']) ? $_POST['tgl_akhir'] : date('Y-m-d');
$status_pesanan = isset($_POST['status_pesanan']) ? $_POST['status_pesanan'] : '';

$tgl_awal_esc = mysqli_real_escape_string($konek, $tgl_awal);
$tgl_akhir_esc = mysqli_real_escape_string($konek, $tgl_akhir);
$status_pesanan_esc = mysqli_real_escape_string($konek, $status_pesanan);

$where = "WHERE DATE(p.tanggal_masuk) BETWEEN '$tgl_awal_esc' AND '$tgl_akhir_esc'";
if (!empty($status_pesanan_esc)) {
    $where .= " AND p.status_pesanan_umum = '$status_pesanan_esc'";
}

$query = mysqli_query($konek, "
    SELECT 
        p.*, p.total_setelah_diskon,
        pl.nama_pelanggan,
        pg.nama_lengkap as penerima,
        (
            SELECT GROUP_CONCAT(l2.nama_layanan SEPARATOR ', ')
            FROM detail_pesanan dp2
            JOIN layanan l2 ON dp2.id_layanan = l2.id_layanan
            WHERE dp2.id_pesanan = p.id_pesanan
        ) as layanan
    FROM pesanan p
    JOIN pelanggan pl ON p.id_pelanggan = pl.id_pelanggan
    LEFT JOIN pengguna pg ON p.id_pengguna_penerima = pg.id_pengguna
    $where
    ORDER BY p.tanggal_masuk DESC
");

if (!$query) {
    echo '<div class="alert alert-danger">Gagal memuat detail. ('.htmlspecialchars(mysqli_error($konek)).')</div>';
    exit;
}

?>

<h6>Detail Pesanan<?= !empty($status_pesanan) ? ' - Status: ' . htmlspecialchars($status_pesanan) : '' ?></h6>
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
                <th>Penerima</th>
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
                $nilai = (isset($data['total_setelah_diskon']) && $data['total_setelah_diskon'] > 0) ? $data['total_setelah_diskon'] : $data['total_harga_keseluruhan'];
                echo "<td>Rp " . number_format($nilai, 0, ',', '.') . "</td>";
                echo "<td>" . htmlspecialchars($data['penerima']) . "</td>";
                echo "</tr>";

                $total_pesanan++;
                $total_nilai += $nilai;
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