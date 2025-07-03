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
        dp.panjang_karpet,
        dp.lebar_karpet,
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

// Hitung statistik sebelum HTML
$total_karpet = 0;
$total_nilai = 0;
$total_promo = 0;
$total_nonpromo = 0;
$total_layanan = 0;

mysqli_data_seek($query_karpet, 0);
while($row = mysqli_fetch_array($query_karpet)) {
    $is_promo = (isset($row['total_setelah_diskon']) && $row['total_setelah_diskon'] !== null && $row['total_setelah_diskon'] > 0);
    $nilai = $is_promo ? $row['total_setelah_diskon'] : $row['subtotal'];
    $total_karpet += $row['kuantitas'];
    $total_nilai += $nilai;
    if ($is_promo) {
        $total_promo += $nilai;
    } else {
        $total_nonpromo += $nilai;
    }
    $total_layanan++;
}
// Reset pointer untuk digunakan di tabel
mysqli_data_seek($query_karpet, 0);
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
        <th>Ukuran (m)</th>
        <th>Jumlah</th>
    </tr>
</thead>
<tbody>
<?php
$no = 1;
mysqli_data_seek($query_karpet, 0);
while($data = mysqli_fetch_array($query_karpet)) {
    echo "<tr>";
    echo "<td>" . $no++ . "</td>";
    echo "<td>" . htmlspecialchars($data['nama_layanan']) . "</td>";
    if (isset($data['panjang_karpet']) && isset($data['lebar_karpet']) && $data['panjang_karpet'] && $data['lebar_karpet']) {
    $ukuran = number_format($data['panjang_karpet'], 2, ',', '.') . ' x ' . number_format($data['lebar_karpet'], 2, ',', '.') . ' m';
} else {
    $ukuran = '-';
}
echo "<td>" . $ukuran . "</td>";
    echo "<td>" . htmlspecialchars($data['kuantitas']) . "</td>";
    echo "</tr>";
}
?>
</tbody>
    </table>
</div>

<div class="row mt-3">
    <div class="col-md-6">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <h6 class="card-title">Total Karpet</h6>
                <h4><?= number_format($total_karpet, 2, ',', '.') ?></h4>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card bg-info text-white">
            <div class="card-body">
                <h6 class="card-title">Total Layanan</h6>
                <h4><?= number_format($total_layanan) ?></h4>
            </div>
        </div>
    </div>
</div> 