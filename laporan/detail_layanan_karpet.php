<?php
include '../pengaturan/koneksi.php';
if (!isset($_POST['id_pesanan'])) {
    exit('Invalid request');
}

$id_pesanan = mysqli_real_escape_string($konek, $_POST['id_pesanan']);
$tgl_awal = mysqli_real_escape_string($konek, $_POST['tgl_awal']);
$tgl_akhir = mysqli_real_escape_string($konek, $_POST['tgl_akhir']);

// Query untuk data pesanan karpet
$query_karpet = mysqli_query($konek, "
    SELECT 
        p.*,
        p.total_setelah_diskon,
        pl.nama_pelanggan,
        pl.nomor_telepon,
        pl.alamat,
        l.nama_layanan,
        dp.kuantitas,
        dp.harga_saat_pesan as harga,
        dp.subtotal_item as subtotal,
        pg.nama_lengkap as penerima
    FROM pesanan p
    JOIN detail_pesanan dp ON p.id_pesanan = dp.id_pesanan
    JOIN layanan l ON dp.id_layanan = l.id_layanan
    JOIN pelanggan pl ON p.id_pelanggan = pl.id_pelanggan
    LEFT JOIN pengguna pg ON p.id_pengguna_penerima = pg.id_pengguna
    WHERE p.id_pesanan = '$id_pesanan'
    AND l.nama_layanan LIKE '%Karpet%'
    AND DATE(p.tanggal_masuk) BETWEEN '$tgl_awal' AND '$tgl_akhir'
");

if (!$query_karpet) {
    echo "<div class='alert alert-danger'>Error executing query: " . mysqli_error($konek) . "</div>";
    exit;
}

$data = mysqli_fetch_array($query_karpet);
if (!$data) {
    echo "<div class='alert alert-warning'>No data found for this order.</div>";
    exit;
}
?>

<h6>Detail Layanan Karpet</h6>
<p>
    <strong>No. Invoice:</strong> <?= htmlspecialchars($data['nomor_invoice']) ?><br>
    <strong>Tanggal Masuk:</strong> <?= date('d/m/Y H:i', strtotime($data['tanggal_masuk'])) ?><br>
    <strong>Pelanggan:</strong> <?= htmlspecialchars($data['nama_pelanggan']) ?><br>
    <strong>No. Telepon:</strong> <?= htmlspecialchars($data['nomor_telepon']) ?><br>
    <strong>Alamat:</strong> <?= htmlspecialchars($data['alamat']) ?>
</p>

<div class="table-responsive">
    <table class="table table-bordered table-hover">
        <thead>
            <tr>
                <th>No</th>
                <th>Layanan</th>
                <th>Jumlah</th>
                <th>Harga</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $no = 1;
            $total_karpet = 0;
            $total_nilai = 0;
            $total_promo = 0;
            $total_nonpromo = 0;

            // Reset pointer
            mysqli_data_seek($query_karpet, 0);
            
            while($data = mysqli_fetch_array($query_karpet)) {
                $is_promo = ($data['total_setelah_diskon'] !== null && $data['total_setelah_diskon'] > 0);
                $nilai = $is_promo ? $data['total_setelah_diskon'] : $data['subtotal'];
                echo "<tr>";
                echo "<td>" . $no++ . "</td>";
                echo "<td>" . htmlspecialchars($data['nama_layanan']) . "</td>";
                echo "<td>" . htmlspecialchars($data['kuantitas']) . "</td>";
                echo "<td>Rp " . number_format($data['harga'], 0, ',', '.') . "</td>";
                echo "<td>Rp " . number_format($nilai, 0, ',', '.') . "</td>";
                echo "</tr>";

                $total_karpet += $data['kuantitas'];
                $total_nilai += $nilai;
                if ($is_promo) {
                    $total_promo += $nilai;
                } else {
                    $total_nonpromo += $nilai;
                }
            }
            ?>
            <tr>
                <td colspan="4" align="center"><strong>Total</strong></td>
                <td><strong>Rp <?= number_format($total_nilai, 0, ',', '.') ?></strong></td>
            </tr>
            <tr>
                <td colspan="4" align="right"><strong>Total Promo</strong></td>
                <td><strong style="color:green;">Rp <?= number_format($total_promo, 0, ',', '.') ?></strong></td>
            </tr>
            <tr>
                <td colspan="4" align="right"><strong>Total Non-Promo</strong></td>
                <td><strong style="color:blue;">Rp <?= number_format($total_nonpromo, 0, ',', '.') ?></strong></td>
            </tr>
        </tbody>
    </table>
</div>

<div class="row mt-3">
    <div class="col-md-6">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <h6 class="card-title">Total Karpet</h6>
                <h4><?= number_format($total_karpet) ?></h4>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card bg-success text-white">
            <div class="card-body">
                <h6 class="card-title">Total Nilai Layanan</h6>
                <h4>Rp <?= number_format($total_nilai, 0, ',', '.') ?></h4>
            </div>
        </div>
    </div>
</div> 