<?php
// --- Detail Pesanan: Info Umum, Daftar Item, Status Proses per Item, Tombol Cetak Nota ---
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Admin','Karyawan'])) {
    echo '<script>alert("Akses ditolak");window.location="../index.php";</script>';
    exit;
}
include "pengaturan/koneksi.php";
include "../template/header.php";
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$id) {
    echo '<script>alert("ID pesanan tidak valid");window.history.back();</script>';
    exit;
}
// Ambil data pesanan
$data_pesanan = mysqli_query($konek, "SELECT p.*, pl.nama_pelanggan, pl.nomor_telepon, pl.alamat, pl.id_telegram FROM pesanan p JOIN pelanggan pl ON p.id_pelanggan=pl.id_pelanggan WHERE p.id_pesanan=$id");
if (!$row = mysqli_fetch_assoc($data_pesanan)) {
    echo '<script>alert("Pesanan tidak ditemukan");window.history.back();</script>';
    exit;
}
// Ambil detail item
$detail = mysqli_query($konek, "SELECT d.*, l.nama_layanan, l.satuan FROM detail_pesanan d JOIN layanan l ON d.id_layanan=l.id_layanan WHERE d.id_pesanan=$id");
?>
<div class="container-fluid mt-4">
    <h3>Detail Pesanan</h3>
    <div class="card mb-3">
        <div class="card-body">
            <h5 class="card-title">Info Pesanan</h5>
            <div class="row mb-2">
                <div class="col-md-4"><b>Nomor Invoice:</b> <?= htmlspecialchars($row['nomor_invoice']) ?></div>
                <div class="col-md-4"><b>Pelanggan:</b> <?= htmlspecialchars($row['nama_pelanggan']) ?></div>
                <div class="col-md-4"><b>Tanggal Masuk:</b> <?= htmlspecialchars(date('d-m-Y H:i', strtotime($row['tanggal_masuk']))) ?></div>
            </div>
            <div class="row mb-2">
                <div class="col-md-4"><b>Estimasi Selesai:</b> <?= htmlspecialchars(date('d-m-Y H:i', strtotime($row['tanggal_estimasi_selesai']))) ?></div>
                <div class="col-md-4"><b>Status Umum:</b> <?= htmlspecialchars($row['status_pesanan_umum']) ?></div>
                <div class="col-md-4"><b>Metode Bayar:</b> <?= htmlspecialchars($row['metode_pembayaran']) ?></div>

            </div>
            <?php if (!empty($row['tanggal_selesai_aktual']) || !empty($row['tanggal_diambil'])) : ?>
            <div class="row mb-2">
                <?php if (!empty($row['tanggal_selesai_aktual'])) : ?>
                <div class="col-md-4"><b>Selesai Aktual:</b> <?= htmlspecialchars(date('d-m-Y H:i', strtotime($row['tanggal_selesai_aktual']))) ?></div>
                <?php endif; ?>
                <?php if (!empty($row['tanggal_diambil'])) : ?>
                <div class="col-md-4"><b>Tanggal Diambil:</b> <?= htmlspecialchars(date('d-m-Y H:i', strtotime($row['tanggal_diambil']))) ?></div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <div class="row mb-2">
                <div class="col-md-4"><b>Status Pembayaran:</b> <?= htmlspecialchars($row['status_pembayaran']) ?></div>
                <div class="col-md-8"><b>Catatan:</b> <?= htmlspecialchars($row['catatan_pesanan']) ?></div>
            </div>
            <a href="pesanan/pesanan_cetak_nota.php?id=<?= $id ?>" target="_blank" class="btn btn-info btn-sm">Cetak Nota</a>
            <?php if (!empty($row['id_telegram'])): ?>
            <a href="pesanan/generate_nota_pdf_and_send.php?id=<?= $id ?>" class="btn btn-success btn-sm">Kirim Nota via Telegram</a>
            <?php endif; ?>
            <?php if(!in_array($row['status_pesanan_umum'], ['Diambil','Dibatalkan'])): ?>
            <a href="?page=pesanan_edit&id=<?= $id ?>" class="btn btn-primary btn-sm">Edit Pesanan</a>
            <?php endif; ?>
            <?php if($row['status_pembayaran']!='Lunas' && !in_array($row['status_pesanan_umum'], ['Dibatalkan','Diambil'])): ?>
            <a href="?page=pesanan_pembayaran&id=<?= $id ?>" class="btn btn-success btn-sm">Update Pembayaran</a>
            <?php endif; ?>
            <a href="?page=pesanan_status_proses&id=<?= $id ?>" class="btn btn-warning btn-sm">Status Proses</a>
            <a href="?page=pesanan_read" class="btn btn-secondary btn-sm">Kembali</a>
        </div>
    </div>
    <div class="card mb-3">
        <div class="card-body">
            <h5 class="card-title">Daftar Item/Layanan</h5>
            <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Layanan</th>
                        <th>Deskripsi</th>
                        <th>Ukuran (P x L)</th>
                        <th>Kuantitas</th>
                        <th>Harga Satuan</th>
                        <th>Subtotal</th>
                        <th>Status Item</th>
                        <th>Catatan</th>
                    </tr>
                </thead>
                <tbody>
                <?php while($d = mysqli_fetch_assoc($detail)): ?>
                    <tr>
                        <td><?= htmlspecialchars($d['nama_layanan']) ?></td>
                        <td><?= htmlspecialchars($d['deskripsi_item_spesifik']) ?></td>
                        <td>
<?php
if (($d['satuan'] === 'm2' || $d['satuan'] === 'm²') && $d['panjang_karpet'] > 0 && $d['lebar_karpet'] > 0) {
    echo htmlspecialchars($d['panjang_karpet']) . ' x ' . htmlspecialchars($d['lebar_karpet']) . ' m';
} else {
    echo '-';
}
?>
</td>
                        <td><?= htmlspecialchars($d['kuantitas']) . ' ' . htmlspecialchars($d['satuan']) ?></td>
                        <td>Rp<?= number_format($d['harga_saat_pesan'],2,',','.') ?></td>
                        <td>Rp<?= number_format($d['subtotal_item'],2,',','.') ?></td>
                        <td><?= htmlspecialchars($d['status_item_terkini']) ?></td>
                        <td><?= htmlspecialchars($d['catatan_item']) ?></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
            </div>
            <div class="text-end">
                <h5 class="mb-1">Subtotal: Rp<?= number_format($row['total_harga_keseluruhan'], 2, ',', '.') ?></h5>
<!-- DEBUG: <?= $row['total_harga_keseluruhan'] ?> -->
<?php if (!empty($row['diskon']) && $row['diskon'] > 0): ?>
    <div class="text-danger">Diskon: -Rp<?= number_format($row['diskon'], 2, ',', '.') ?></div>
    <h4 class="mt-2">Total: Rp<?= number_format($row['total_setelah_diskon'], 2, ',', '.') ?></h4>
<?php else: ?>
    <h4 class="mt-2">Total: Rp<?= number_format($row['total_harga_keseluruhan'], 2, ',', '.') ?></h4>
<?php endif; ?>
            </div>
        </div>
    </div>
</div>
