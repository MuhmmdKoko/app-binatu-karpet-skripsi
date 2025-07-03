<?php
include "../pengaturan/koneksi.php";

$id_pengguna = isset($_POST['id_pengguna']) && ctype_digit($_POST['id_pengguna']) ? $_POST['id_pengguna'] : '';
$tgl_awal = isset($_POST['tgl_awal']) ? $_POST['tgl_awal'] : '';
$tgl_akhir = isset($_POST['tgl_akhir']) ? $_POST['tgl_akhir'] : '';

// Query untuk data pesanan (sinkron dengan laporan utama)
$where = "WHERE 1=1";
if (!empty($id_pengguna)) {
    $id_pengguna_esc = mysqli_real_escape_string($konek, $id_pengguna);
    $where .= " AND p.id_pengguna_penerima = '$id_pengguna_esc'";
}
if (!empty($tgl_awal) && !empty($tgl_akhir)) {
    $tgl_awal_esc = mysqli_real_escape_string($konek, $tgl_awal);
    $tgl_akhir_esc = mysqli_real_escape_string($konek, $tgl_akhir);
    $where .= " AND DATE(p.tanggal_masuk) BETWEEN '$tgl_awal_esc' AND '$tgl_akhir_esc'";
}
$query = mysqli_query($konek, "
    SELECT 
        p.*,
        pl.nama_pelanggan,
        GROUP_CONCAT(l.nama_layanan SEPARATOR ', ') as layanan,
        MAX(dp.status_item_terkini) as status_item_terkini,
        pg.nama_lengkap
    FROM pesanan p
    LEFT JOIN detail_pesanan dp ON p.id_pesanan = dp.id_pesanan
    LEFT JOIN layanan l ON dp.id_layanan = l.id_layanan
    JOIN pelanggan pl ON p.id_pelanggan = pl.id_pelanggan
    JOIN pengguna pg ON p.id_pengguna_penerima = pg.id_pengguna
    $where
    GROUP BY p.id_pesanan
    ORDER BY p.tanggal_masuk DESC
");
?>

<?php
// DEBUGGING OUTPUT
// Remove or comment out after debugging
// echo '<pre>ID Pengguna: ' . htmlspecialchars($id_pengguna) . "\nWHERE: $where\nRows: " . ($query ? mysqli_num_rows($query) : 'ERR') . '</pre>';
// $nama_penerima_show = '';
if ($query && mysqli_num_rows($query) > 0) {
    $row_first = mysqli_fetch_assoc($query);
    $nama_penerima_show = $row_first['nama_lengkap'];
    mysqli_data_seek($query, 0);
} else if (!empty($id_pengguna)) {
    // Fetch the name directly if there are no orders but the user exists
    $res_pengguna = mysqli_query($konek, "SELECT nama_lengkap FROM pengguna WHERE id_pengguna = '" . mysqli_real_escape_string($konek, $id_pengguna) . "'");
    if ($res_pengguna && ($row_pengguna = mysqli_fetch_assoc($res_pengguna))) {
        $nama_penerima_show = $row_pengguna['nama_lengkap'];
    } else {
        $nama_penerima_show = '-';
    }
}
?>
<h6>Detail Penerima - <?= htmlspecialchars($nama_penerima_show) ?></h6>
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