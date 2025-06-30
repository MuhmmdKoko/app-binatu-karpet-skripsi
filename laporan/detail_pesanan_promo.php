<?php
// Halaman Detail Pesanan yang Menggunakan Promo Tertentu
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Admin','Karyawan'])) {
    echo '<script>alert("Akses ditolak");window.location="../index.php";</script>';
    exit;
}
include '../pengaturan/koneksi.php';
$id_promosi = isset($_GET['id_promosi']) ? intval($_GET['id_promosi']) : 0;
if (!$id_promosi) {
    echo '<script>alert("ID promo tidak valid");window.history.back();</script>';
    exit;
}
// Ambil info promosi
$q_promo = mysqli_query($konek, "SELECT * FROM promosi WHERE id_promosi=$id_promosi");
if (!$promo = mysqli_fetch_assoc($q_promo)) {
    echo '<script>alert("Promo tidak ditemukan");window.history.back();</script>';
    exit;
}
// Ambil daftar pesanan yang menggunakan promo ini
$sql = "SELECT ps.*, pl.nama_pelanggan, pl.nomor_telepon FROM pesanan ps JOIN pelanggan pl ON ps.id_pelanggan=pl.id_pelanggan WHERE ps.id_promosi=$id_promosi ORDER BY ps.tanggal_masuk DESC";
$q_pesanan = mysqli_query($konek, $sql);
?>
<?php if (isset($_GET['ajax'])): ?>
<div class="container mt-2">
<?php else: ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Detail Penggunaan Promo: <?= htmlspecialchars($promo['judul']) ?></title>
    <link rel="stylesheet" href="../assets/bootstrap.min.css">
    <style>
        body { font-size: 14px; }
        .table th, .table td { vertical-align: middle; }
    </style>
</head>
<body>
<div class="container mt-4">
<?php endif; ?>
    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <h5 class="card-title mb-1">Detail Promo</h5>
                    <div><b>Judul Promo:</b> <?= htmlspecialchars($promo['judul']) ?></div>
                    <div><b>Kode Promo:</b> <?= htmlspecialchars($promo['kode_promo']) ?></div>
                    <div><b>Jenis Promo:</b> <?= htmlspecialchars($promo['tipe_promo']) ?></div>
                    <div><b>Nilai Promo:</b> <?= htmlspecialchars($promo['nilai_promo']) ?></div>
                    <div><b>Periode:</b> <?= htmlspecialchars(date('d-m-Y', strtotime($promo['tanggal_buat']))) ?> s/d <?= htmlspecialchars(date('d-m-Y', strtotime($promo['tanggal_berakhir']))) ?></div>
                    <div><b>Status:</b> <span class="badge bg-secondary"><?= htmlspecialchars(ucfirst($promo['status_promo'])) ?></span></div>
                    <div><b>Syarat Minimal Transaksi:</b> <?= htmlspecialchars($promo['syarat_min_transaksi']) ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="card mb-4">
        <div class="card-header">Daftar Pesanan yang Menggunakan Promo Ini</div>
        <div class="card-body p-0">
            <table class="table table-bordered mb-0">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Invoice</th>
                        <th>Tanggal Masuk</th>
                        <th>Pelanggan</th>
                        <th>No. HP</th>
                        <th>Total Transaksi</th>
                        <th>Diskon</th>
                        <th>Status</th>
                        <th>Detail</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                        $no=1; 
                        $total_diskon=0; 
                        $total_pesanan=0; 
                        $pelanggan_unik=array();
                        mysqli_data_seek($q_pesanan, 0);
                        while($ps = mysqli_fetch_assoc($q_pesanan)): 
                            $total_diskon += $ps['diskon'];
                            $total_pesanan++;
                            $pelanggan_unik[$ps['id_pelanggan']] = true;
                    ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= htmlspecialchars($ps['nomor_invoice']) ?></td>
                        <td><?= htmlspecialchars(date('d-m-Y H:i', strtotime($ps['tanggal_masuk']))) ?></td>
                        <td><?= htmlspecialchars($ps['nama_pelanggan']) ?></td>
                        <td><?= htmlspecialchars($ps['nomor_telepon']) ?></td>
                        <td>Rp<?= number_format($ps['total_harga_keseluruhan'],0,',','.') ?></td>
                        <td>Rp<?= number_format($ps['diskon'],0,',','.') ?></td>
                        <td><?= htmlspecialchars($ps['status_pesanan_umum']) ?></td>
                        <td><a href="index.php?page=pesanan_detail&id=<?= $ps['id_pesanan'] ?>" class="btn btn-info btn-sm" target="_blank">Detail</a></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="row mt-3">
        <div class="col-md-4">
            <div class="card bg-success text-white mb-2">
                <div class="card-body">
                    <h6 class="card-title">Total Penggunaan</h6>
                    <h4><?= $total_pesanan ?></h4>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-info text-white mb-2">
                <div class="card-body">
                    <h6 class="card-title">Total Diskon</h6>
                    <h4>Rp<?= number_format($total_diskon,0,',','.') ?></h4>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-warning text-dark mb-2">
                <div class="card-body">
                    <h6 class="card-title">Rata-rata Diskon</h6>
                    <h4>Rp<?= $total_pesanan>0 ? number_format($total_diskon/$total_pesanan,0,',','.') : '0' ?></h4>
                </div>
            </div>
            <div class="card bg-secondary text-white">
                <div class="card-body">
                    <h6 class="card-title">Pelanggan Unik</h6>
                    <h4><?= count($pelanggan_unik) ?></h4>
                </div>
            </div>
        </div>
    </div>
    <?php if (!isset($_GET['ajax'])): ?>
<a href="laporan_promosi.php" class="btn btn-secondary mt-3">Kembali ke Laporan Promosi</a>
</div>
</body>
</html>
<?php endif; ?>
