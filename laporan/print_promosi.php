<?php
session_start();
include '../pengaturan/koneksi.php';

$tanggal_mulai = isset($_GET['tanggal_mulai']) ? $_GET['tanggal_mulai'] : date('Y-m-01');
$tanggal_akhir = isset($_GET['tanggal_akhir']) ? $_GET['tanggal_akhir'] : date('Y-m-t');
$periode = isset($_GET['periode']) ? $_GET['periode'] : 'custom';
$status_promo = isset($_GET['status_promo']) ? $_GET['status_promo'] : 'semua';
$minimal_penggunaan = isset($_GET['minimal_penggunaan']) ? intval($_GET['minimal_penggunaan']) : 1;

$sql = "SELECT p.judul, p.kode_promo, p.status_promo, p.tanggal_buat, p.tanggal_berakhir, COUNT(ps.id_pesanan) AS jumlah_penggunaan, SUM(ps.diskon) AS total_diskon, AVG(ps.diskon) AS rata_diskon, COUNT(DISTINCT ps.id_pelanggan) AS pelanggan_unik
FROM promosi p
LEFT JOIN pesanan ps ON p.id_promosi = ps.id_promosi AND ps.tanggal_masuk BETWEEN ? AND ?
" . ($status_promo !== 'semua' ? "WHERE p.status_promo = ?\n" : "") .
"GROUP BY p.id_promosi, p.judul, p.kode_promo, p.status_promo, p.tanggal_buat, p.tanggal_berakhir
HAVING jumlah_penggunaan >= ?
ORDER BY p.id_promosi DESC";

if ($status_promo !== 'semua') {
    $stmt = mysqli_prepare($konek, $sql);
    mysqli_stmt_bind_param($stmt, "sssi", $tanggal_mulai, $tanggal_akhir, $status_promo, $minimal_penggunaan);
} else {
    $stmt = mysqli_prepare($konek, $sql);
    mysqli_stmt_bind_param($stmt, "ssi", $tanggal_mulai, $tanggal_akhir, $minimal_penggunaan);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Laporan Promosi</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 24px;
            background: #fff;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h2 {
            margin-bottom: 6px;
        }
        .header .filters {
            margin: 0 auto 10px auto;
            display: inline-block;
            text-align: left;
        }
        .summary {
            margin: 0 auto 24px auto;
            width: 70%;
            font-size: 15px;
        }
        .summary td {
            padding: 6px 12px;
        }
        table.data-table {
            border-collapse: collapse;
            width: 100%;
            margin-bottom: 20px;
        }
        table.data-table, .data-table th, .data-table td {
            border: 1px solid #333;
        }
        .data-table th, .data-table td {
            padding: 8px;
            text-align: center;
        }
        .data-table th {
            background: #f2f2f2;
        }
        .footer {
            margin-top: 30px;
            font-size: 12px;
            text-align: right;
        }
        .no-print {
            margin-top: 20px;
            text-align: center;
        }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h2 style="margin-bottom:2px;">BERKAT LAUNDRY</h2>
        <div style="font-size:15px; margin-bottom:8px;">Laporan Promosi</div>
        <div class="filters" style="margin-bottom:6px;">
            <p style="margin:2px 0;"><strong>Periode</strong>: <?= date('d/m/Y', strtotime($tanggal_mulai)) ?> - <?= date('d/m/Y', strtotime($tanggal_akhir)) ?></p>
            <p style="margin:2px 0;"><strong>Status Promo</strong>: <?= ($status_promo=='semua')?'Semua':ucfirst($status_promo) ?></p>
            <p style="margin:2px 0;"><strong>Minimal Penggunaan</strong>: <?= $minimal_penggunaan ?></p>
        </div>
    </div>
<?php
// Reset result pointer dan inisialisasi summary
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$total_promo_aktif = 0;
$total_penggunaan = 0;
$total_diskon = 0;
$total_promo = 0;
$total_rata_diskon = 0;
$total_pelanggan_unik = 0;
$rows = [];
while ($data = mysqli_fetch_assoc($result)) {
    $rows[] = $data;
    $total_promo++;
    if (strtolower($data['status_promo']) === 'aktif') $total_promo_aktif++;
    $total_penggunaan += (int)$data['jumlah_penggunaan'];
    $total_diskon += (float)$data['total_diskon'];
    $total_rata_diskon += (float)$data['rata_diskon'];
    $total_pelanggan_unik += (int)$data['pelanggan_unik'];
}
$avg_diskon = $total_promo > 0 ? $total_rata_diskon / $total_promo : 0;
?>
<table class="summary" style="border:1px solid #bbb; background:#fafcff; border-radius:7px; box-shadow:0 1px 2px #eee; margin-bottom:18px;">
    <tr>
        <td style="border-right:1px solid #eee;"><strong>Promo Aktif</strong><br><span style="font-size:18px; font-weight:bold; color:#1a7f37;"><?= $total_promo_aktif ?></span></td>
        <td style="border-right:1px solid #eee;"><strong>Total Penggunaan</strong><br><span style="font-size:18px; font-weight:bold; color:#0a58ca;"><?= $total_penggunaan ?></span></td>
        <td style="border-right:1px solid #eee;"><strong>Total Diskon</strong><br><span style="font-size:18px; font-weight:bold; color:#0d6efd;">Rp <?= number_format($total_diskon, 0, ',', '.') ?></span></td>
        <td style="border-right:1px solid #eee;"><strong>Rata-rata Diskon</strong><br><span style="font-size:18px; font-weight:bold; color:#f59e00;">Rp <?= number_format($avg_diskon, 0, ',', '.') ?></span></td>
        <td><strong>Pelanggan Unik</strong><br><span style="font-size:18px; font-weight:bold; color:#0a58ca;"><?= $total_pelanggan_unik ?></span></td>
    </tr>
</table>

    <table class="data-table">
        <thead>
            <tr>
                <th>Judul Promo</th>
                <th>Kode Promo</th>
                <th>Status</th>
                <th>Periode</th>
                <th>Digunakan</th>
                <th>Total Diskon (Rp)</th>
                <th>Rata-rata Diskon</th>
                <th>Pelanggan Unik</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $data): ?>
            <tr>
                <td><?= htmlspecialchars($data['judul']) ?></td>
                <td><?= htmlspecialchars($data['kode_promo']) ?></td>
                <td><?= ucfirst($data['status_promo']) ?></td>
                <td><?= ($data['tanggal_buat'] ? date('d-m-Y', strtotime($data['tanggal_buat'])) : '-') . ' s/d ' . ($data['tanggal_berakhir'] ? date('d-m-Y', strtotime($data['tanggal_berakhir'])) : '-') ?></td>
                <td><?= $data['jumlah_penggunaan'] ?></td>
                <td><?= number_format($data['total_diskon'], 0, ',', '.') ?></td>
                <td><?= number_format($data['rata_diskon'], 0, ',', '.') ?></td>
                <td><?= $data['pelanggan_unik'] ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <div class="footer">
        <p>Dicetak pada: <?= date('d/m/Y H:i:s') ?></p>
        <p>Dicetak oleh: <?= htmlspecialchars($_SESSION['nama_lengkap'] ?? '-') ?></p>
    </div>
    <div class="no-print">
        <button onclick="window.print()">Cetak</button>
        <button onclick="window.close()">Tutup</button>
    </div>
    <script>
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html>
