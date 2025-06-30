<?php
// Export PDF untuk Laporan Promosi
require '../vendor/autoload.php'; // Pastikan mPDF sudah diinstall di vendor
include 'pengaturan/koneksi.php';

use Mpdf\Mpdf;

$tanggal_mulai = isset($_GET['tanggal_mulai']) ? $_GET['tanggal_mulai'] : date('Y-m-01');
$tanggal_akhir = isset($_GET['tanggal_akhir']) ? $_GET['tanggal_akhir'] : date('Y-m-t');

$sql = "SELECT p.judul, p.kode_promo, p.status_promo, p.tanggal_buat, p.tanggal_berakhir, COUNT(ps.id_pesanan) AS jumlah_penggunaan, SUM(ps.diskon) AS total_diskon, AVG(ps.diskon) AS rata_diskon, COUNT(DISTINCT ps.id_pelanggan) AS pelanggan_unik FROM promosi p LEFT JOIN pesanan ps ON p.id_promosi = ps.id_promosi AND ps.tanggal_masuk BETWEEN ? AND ? GROUP BY p.id_promosi, p.judul, p.kode_promo, p.status_promo, p.tanggal_buat, p.tanggal_berakhir ORDER BY p.id_promosi DESC";
$stmt = mysqli_prepare($konek, $sql);
mysqli_stmt_bind_param($stmt, "ss", $tanggal_mulai, $tanggal_akhir);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$html = '<h2 style="text-align:center;">Laporan Promosi</h2>';
$html .= '<table border="1" cellpadding="6" cellspacing="0" width="100%">';
$html .= '<thead><tr style="background:#f2f2f2;"><th>Judul Promo</th><th>Kode Promo</th><th>Status</th><th>Periode</th><th>Digunakan</th><th>Total Diskon (Rp)</th><th>Rata-rata Diskon</th><th>Pelanggan Unik</th></tr></thead><tbody>';
while ($data = mysqli_fetch_assoc($result)) {
    $periode = ($data['tanggal_buat'] ? date('d-m-Y', strtotime($data['tanggal_buat'])) : '-') . ' s/d ' . ($data['tanggal_berakhir'] ? date('d-m-Y', strtotime($data['tanggal_berakhir'])) : '-');
    $html .= '<tr>';
    $html .= '<td>' . htmlspecialchars($data['judul']) . '</td>';
    $html .= '<td>' . htmlspecialchars($data['kode_promo']) . '</td>';
    $html .= '<td>' . htmlspecialchars(ucfirst($data['status_promo'])) . '</td>';
    $html .= '<td>' . $periode . '</td>';
    $html .= '<td>' . $data['jumlah_penggunaan'] . '</td>';
    $html .= '<td>' . number_format($data['total_diskon'], 0, ',', '.') . '</td>';
    $html .= '<td>' . number_format($data['rata_diskon'], 0, ',', '.') . '</td>';
    $html .= '<td>' . $data['pelanggan_unik'] . '</td>';
    $html .= '</tr>';
}
$html .= '</tbody></table>';

$mpdf = new Mpdf(['format' => 'A4']);
$mpdf->WriteHTML($html);
$mpdf->Output('laporan_promosi.pdf', \Mpdf\Output\Destination::INLINE);
exit;
