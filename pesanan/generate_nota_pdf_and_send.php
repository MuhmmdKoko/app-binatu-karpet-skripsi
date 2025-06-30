<?php
// File: pesanan/generate_nota_pdf_and_send.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include "../pengaturan/koneksi.php";
include "../pengaturan/telegram_notif.php";
require_once __DIR__ . '/../vendor/autoload.php'; // mPDF

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$id) die("ID pesanan tidak valid.");

// Ambil data pesanan & pelanggan
$data_pesanan = mysqli_query($konek, "SELECT p.*, pl.nama_pelanggan, pl.nomor_telepon, pl.alamat, pl.id_telegram FROM pesanan p JOIN pelanggan pl ON p.id_pelanggan=pl.id_pelanggan WHERE p.id_pesanan=$id");
if (!$row = mysqli_fetch_assoc($data_pesanan)) die("Pesanan tidak ditemukan.");
if (empty($row['id_telegram'])) die("Pelanggan belum terhubung ke Telegram.");

$detail = mysqli_query($konek, "SELECT d.*, l.nama_layanan, l.satuan FROM detail_pesanan d JOIN layanan l ON d.id_layanan=l.id_layanan WHERE d.id_pesanan=$id");

// Siapkan HTML nota konsisten dengan pesanan_cetak_nota.php
ob_start();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Nota Pesanan</title>
    <style>
    body { font-size: 13px; }
    .nota { max-width: 450px; margin: auto; border: 1px solid #aaa; padding: 16px; }
    .table { width: 100%; border-collapse: collapse; }
    .table th, .table td { border: 1px solid #aaa; padding: 4px; }
    </style>
</head>
<body>
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
    <table class="table">
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
<?php
$html = ob_get_clean();

// Generate PDF
$mpdf = new \Mpdf\Mpdf();
$mpdf->WriteHTML($html);
$pdf_path = __DIR__.'/nota_invoice_'.$id.'.pdf';
$mpdf->Output($pdf_path, \Mpdf\Output\Destination::FILE);

// Kirim ke Telegram
$caption = "Berikut nota pesanan Anda. Terima kasih telah menggunakan layanan kami!";
$res = send_telegram_pdf($row['id_telegram'], $pdf_path, $caption);

// Hapus file PDF
@unlink($pdf_path);

// Feedback ke user dan redirect kembali ke detail pesanan
if ($res && isset($res['ok']) && $res['ok']) {
    echo '<script>alert("Nota berhasil dikirim ke Telegram!");window.location.href="../index.php?page=pesanan_detail&id='.$id.'";</script>';
    exit;
} else {
    $errMsg = isset($res['description']) ? $res['description'] : 'Gagal mengirim nota ke Telegram.';
    echo '<script>alert("'.$errMsg.'");window.location.href="../index.php?page=pesanan_detail&id='.$id.'";</script>';
    exit;
}