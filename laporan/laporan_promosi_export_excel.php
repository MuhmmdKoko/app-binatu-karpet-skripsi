<?php
// Export Excel untuk Laporan Promosi
require '../vendor/autoload.php'; // Pastikan PHPSpreadsheet sudah diinstall di vendor
include 'pengaturan/koneksi.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="laporan_promosi.xlsx"');
header('Cache-Control: max-age=0');

$tanggal_mulai = isset($_GET['tanggal_mulai']) ? $_GET['tanggal_mulai'] : date('Y-m-01');
$tanggal_akhir = isset($_GET['tanggal_akhir']) ? $_GET['tanggal_akhir'] : date('Y-m-t');

$sql = "SELECT p.judul, p.kode_promo, p.status_promo, p.tanggal_buat, p.tanggal_berakhir, COUNT(ps.id_pesanan) AS jumlah_penggunaan, SUM(ps.diskon) AS total_diskon, AVG(ps.diskon) AS rata_diskon, COUNT(DISTINCT ps.id_pelanggan) AS pelanggan_unik FROM promosi p LEFT JOIN pesanan ps ON p.id_promosi = ps.id_promosi AND ps.tanggal_masuk BETWEEN ? AND ? GROUP BY p.id_promosi, p.judul, p.kode_promo, p.status_promo, p.tanggal_buat, p.tanggal_berakhir ORDER BY p.id_promosi DESC";
$stmt = mysqli_prepare($konek, $sql);
mysqli_stmt_bind_param($stmt, "ss", $tanggal_mulai, $tanggal_akhir);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setCellValue('A1', 'Judul Promo')
    ->setCellValue('B1', 'Kode Promo')
    ->setCellValue('C1', 'Status')
    ->setCellValue('D1', 'Periode')
    ->setCellValue('E1', 'Digunakan')
    ->setCellValue('F1', 'Total Diskon (Rp)')
    ->setCellValue('G1', 'Rata-rata Diskon')
    ->setCellValue('H1', 'Pelanggan Unik');
$row = 2;
while ($data = mysqli_fetch_assoc($result)) {
    $periode = ($data['tanggal_buat'] ? date('d-m-Y', strtotime($data['tanggal_buat'])) : '-') . ' s/d ' . ($data['tanggal_berakhir'] ? date('d-m-Y', strtotime($data['tanggal_berakhir'])) : '-');
    $sheet->setCellValue('A'.$row, $data['judul'])
        ->setCellValue('B'.$row, $data['kode_promo'])
        ->setCellValue('C'.$row, ucfirst($data['status_promo']))
        ->setCellValue('D'.$row, $periode)
        ->setCellValue('E'.$row, $data['jumlah_penggunaan'])
        ->setCellValue('F'.$row, $data['total_diskon'])
        ->setCellValue('G'.$row, $data['rata_diskon'])
        ->setCellValue('H'.$row, $data['pelanggan_unik']);
    $row++;
}
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
