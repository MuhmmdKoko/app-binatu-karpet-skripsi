<?php
include "../pengaturan/koneksi.php";

$periode = $_POST['periode'];
$tipe_laporan = $_POST['tipe_laporan'];
$status_pembayaran = isset($_POST['status_pembayaran']) ? $_POST['status_pembayaran'] : '';
$tgl_awal = isset($_POST['tgl_awal']) ? $_POST['tgl_awal'] : '';
$tgl_akhir = isset($_POST['tgl_akhir']) ? $_POST['tgl_akhir'] : '';

// Sinkronisasi filter tanggal dengan laporan_pendapatan.php
$where = "WHERE 1=1";
// Jika tipe harian dan periode (tanggal yang diklik) ada, prioritaskan filter tanggal harian
if ($tipe_laporan == 'harian' && !empty($periode)) {
    $where .= " AND DATE(p.tanggal_masuk) = '" . mysqli_real_escape_string($konek, $periode) . "'";
} else if (!empty($tgl_awal) && !empty($tgl_akhir)) {
    $where .= " AND DATE(p.tanggal_masuk) BETWEEN '" . mysqli_real_escape_string($konek, $tgl_awal) . "' AND '" . mysqli_real_escape_string($konek, $tgl_akhir) . "'";
}
if (!empty($status_pembayaran)) {
    $where .= " AND p.status_pembayaran = '" . mysqli_real_escape_string($konek, $status_pembayaran) . "'";
}

// Query untuk data pesanan
$query = mysqli_query($konek, "
    SELECT 
        p.*, p.total_setelah_diskon,
        pl.nama_pelanggan,
        GROUP_CONCAT(l.nama_layanan SEPARATOR ', ') as layanan,
        pg.nama_lengkap as penerima
    FROM pesanan p
    JOIN pelanggan pl ON p.id_pelanggan = pl.id_pelanggan
    JOIN detail_pesanan dp ON p.id_pesanan = dp.id_pesanan
    JOIN layanan l ON dp.id_layanan = l.id_layanan
    LEFT JOIN pengguna pg ON p.id_pengguna_penerima = pg.id_pengguna
    $where
    GROUP BY p.id_pesanan
    ORDER BY p.tanggal_masuk DESC
");

// Format judul periode
$periode_text = "";
switch($tipe_laporan) {
    case 'harian':
        $periode_text = date('d/m/Y', strtotime($periode));
        break;
    case 'mingguan':
        $week = substr($periode, -2);
        $year = substr($periode, 0, 4);
        $periode_text = "Minggu " . $week . ", " . $year;
        break;
    case 'bulanan':
        $periode_text = date('F Y', strtotime($periode . '-01'));
        break;
}
?>

<h6>Detail Pendapatan - <?= $periode_text ?></h6>
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
                <th>Status Pembayaran</th>
                <th>Penerima</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $no = 1;
            $total_pendapatan = 0;
            $total_lunas = 0;
            $total_dp = 0;
            $total_belum_lunas = 0;

            $total_promo = 0;
            $total_nonpromo = 0;
            while($data = mysqli_fetch_array($query)) {
                echo "<tr>";
                echo "<td>" . $no++ . "</td>";
                echo "<td>" . htmlspecialchars($data['nomor_invoice']) . "</td>";
                echo "<td>" . date('d/m/Y H:i', strtotime($data['tanggal_masuk'])) . "</td>";
                echo "<td>" . htmlspecialchars($data['nama_pelanggan']) . "</td>";
                echo "<td>" . htmlspecialchars($data['layanan']) . "</td>";
                $nilai = ($data['total_setelah_diskon'] !== null && $data['total_setelah_diskon'] > 0) ? $data['total_setelah_diskon'] : $data['total_harga_keseluruhan'];
                echo "<td>Rp " . number_format($nilai, 0, ',', '.') . "</td>";
                echo "<td>" . htmlspecialchars($data['status_pembayaran']);
                if ($data['status_pembayaran'] == 'DP') {
                    $sisa = $nilai - $data['nominal_pembayaran'];
                    echo '<br><small class="text-info">DP dibayar: Rp ' . number_format($data['nominal_pembayaran'], 0, ',', '.') . ' | Sisa: Rp ' . number_format($sisa, 0, ',', '.') . '</small>';
                }
                echo "</td>";
                echo "<td>" . htmlspecialchars($data['penerima']) . "</td>";
                echo "</tr>";

                $total_pendapatan += $nilai;
                if ($data['total_setelah_diskon'] !== null && $data['total_setelah_diskon'] > 0) {
                    $total_promo += $data['total_setelah_diskon'];
                } else {
                    $total_nonpromo += $data['total_harga_keseluruhan'];
                }
                if ($data['status_pembayaran'] == 'Lunas') {
                    $total_lunas += $nilai;
                } elseif ($data['status_pembayaran'] == 'DP') {
                    $total_dp += $data['nominal_pembayaran'];
                    $total_belum_lunas += ($nilai - $data['nominal_pembayaran']);
                } elseif ($data['status_pembayaran'] == 'Belum Lunas') {
                    $total_belum_lunas += $nilai;
                }
            }
            ?>
            <tr>
                <td colspan="5" align="center"><strong>Total Promo</strong></td>
                <td><strong>Rp <?= number_format($total_promo, 0, ',', '.') ?></strong></td>
                <td colspan="2"></td>
            </tr>
            <tr>
                <td colspan="5" align="center"><strong>Total Non-Promo</strong></td>
                <td><strong>Rp <?= number_format($total_nonpromo, 0, ',', '.') ?></strong></td>
                <td colspan="2"></td>
            </tr>
            <tr>
                <td colspan="5" align="center"><strong>Total</strong></td>
                <td><strong>Rp <?= number_format($total_pendapatan, 0, ',', '.') ?></strong></td>
                <td colspan="2"></td>
            </tr>
        </tbody>
    </table>
</div>

<div class="row mt-3">
    <div class="col-md-4">
        <div class="card bg-success text-white">
            <div class="card-body">
                <h6 class="card-title">Pembayaran Lunas</h6>
                <h4>Rp <?= number_format($total_lunas, 0, ',', '.') ?></h4>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-info text-white">
            <div class="card-body">
                <h6 class="card-title">Pembayaran DP</h6>
                <h4>Rp <?= number_format($total_dp, 0, ',', '.') ?></h4>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-warning text-dark">
            <div class="card-body">
                <h6 class="card-title">Belum Lunas</h6>
                <h4>Rp <?= number_format($total_belum_lunas, 0, ',', '.') ?></h4>
            </div>
        </div>
    </div>
</div>