<?php
// --- Nota Cetak: Tampilkan info pesanan dan detail dalam format siap print ---
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Admin','Karyawan'])) {
    echo '<script>alert("Akses ditolak");window.close();</script>';
    exit;
}
include "../pengaturan/koneksi.php";
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$id) {
    echo '<script>alert("ID pesanan tidak valid");window.close();</script>';
    exit;
}
$data_pesanan = mysqli_query($konek, "SELECT p.*, pl.nama_pelanggan, pl.nomor_telepon, pl.alamat FROM pesanan p JOIN pelanggan pl ON p.id_pelanggan=pl.id_pelanggan WHERE p.id_pesanan=$id");
if (!$row = mysqli_fetch_assoc($data_pesanan)) {
    echo '<script>alert("Pesanan tidak ditemukan");window.close();</script>';
    exit;
}
// Blokir cetak jika dibatalkan
if($row['status_pesanan_umum']=='Dibatalkan'){
    echo '<script>alert("Pesanan dibatalkan. Nota tidak dapat dicetak");window.close();</script>';
    exit;
}
$detail = mysqli_query($konek, "SELECT d.*, l.nama_layanan, l.satuan FROM detail_pesanan d JOIN layanan l ON d.id_layanan=l.id_layanan WHERE d.id_pesanan=$id");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Nota Pesanan</title>
    <link rel="stylesheet" href="../assets/libs/bootstrap/dist/css/bootstrap.min.css">
    <style>
    body { font-size: 13px; }
    .nota { max-width: 450px; margin: auto; border: 1px solid #aaa; padding: 16px; }
    @media print {
        body { margin: 0; background: #fff !important; }
        .nota { box-shadow: none !important; border: 1px solid #000; }
        .table thead th, .table tfoot td { background: #fff !important; -webkit-print-color-adjust: exact; }
        a, button { display: none !important; }
    }
    </style>
</head>
<body onload="window.print()">
<div class="nota">
    <h5 class="text-center">Nota Pesanan Laundry</h5>
    <hr>
    <div><b>Invoice:</b> <?= htmlspecialchars($row['nomor_invoice']) ?></div>
    <div><b>Pelanggan:</b> <?= htmlspecialchars($row['nama_pelanggan']) ?> (<?= htmlspecialchars($row['nomor_telepon']) ?>)</div>
    <div><b>Alamat:</b> <?= htmlspecialchars($row['alamat']) ?></div>
    <div><b>Tanggal Masuk:</b> <?= htmlspecialchars(date('d-m-Y H:i', strtotime($row['tanggal_masuk']))) ?></div>
    <div><b>Estimasi Selesai:</b> <?= htmlspecialchars(date('d-m-Y H:i', strtotime($row['tanggal_estimasi_selesai']))) ?></div>
    <?php if (!empty($row['tanggal_selesai_aktual'])) : ?>
    <div><b>Selesai Aktual:</b> <?= htmlspecialchars(date('d-m-Y H:i', strtotime($row['tanggal_selesai_aktual']))) ?></div>
    <?php endif; ?>
    <?php if (!empty($row['tanggal_diambil'])) : ?>
    <div><b>Tanggal Diambil:</b> <?= htmlspecialchars(date('d-m-Y H:i', strtotime($row['tanggal_diambil']))) ?></div>
    <?php endif; ?>
    <hr>
    <table class="table table-sm table-bordered mb-2">
        <thead>
            <tr>
                <th>Layanan</th>
                <th>Qty</th>
                <th>Harga</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
        <?php while($d = mysqli_fetch_assoc($detail)): ?>
            <tr>
                <td><?= htmlspecialchars($d['nama_layanan']) ?></td>
                <td><?= htmlspecialchars($d['kuantitas']) . ' ' . htmlspecialchars($d['satuan']) ?></td>
                <td>Rp<?= number_format($d['harga_saat_pesan'],0,',','.') ?></td>
                <td>Rp<?= number_format($d['subtotal_item'],0,',','.') ?></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" class="text-end"><b>Subtotal</b></td>
                <td><b>Rp<?= number_format($row['total_harga_keseluruhan'], 0, ',', '.') ?></b></td>
            </tr>
            <?php if (isset($row['diskon']) && $row['diskon'] > 0) : ?>
            <tr>
                <td colspan="3" class="text-end text-danger">Diskon</td>
                <td class="text-danger">- Rp<?= number_format($row['diskon'], 0, ',', '.') ?></td>
            </tr>
            <tr>
                <td colspan="3" class="text-end"><b>Total Akhir</b></td>
                <td><b>Rp<?= number_format($row['total_setelah_diskon'], 0, ',', '.') ?></b></td>
            </tr>
            <?php endif; ?>
            <tr>
                <td colspan="3" class="text-end">Sudah Dibayar</td>
                <td>Rp<?= number_format($row['nominal_pembayaran'], 0, ',', '.') ?></td>
            </tr>
            <tr>
                <td colspan="3" class="text-end"><b>Sisa Tagihan</b></td>
                <td><b>Rp<?= number_format(($row['diskon'] > 0 ? $row['total_setelah_diskon'] : $row['total_harga_keseluruhan']) - $row['nominal_pembayaran'], 0, ',', '.') ?></b></td>
            </tr>
        </tfoot>
    </table>
    <hr>
    <div><b>Status:</b> <?= htmlspecialchars($row['status_pesanan_umum']) ?> | <b>Pembayaran:</b> <?= htmlspecialchars($row['status_pembayaran']) ?></div>
    <div class="text-center mt-2">Terima kasih telah menggunakan layanan kami.</div>
</div>
</body>
</html>
