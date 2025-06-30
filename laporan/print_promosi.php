<?php
session_start();
include '../pengaturan/koneksi.php';

$tanggal_mulai = isset($_GET['tanggal_mulai']) ? $_GET['tanggal_mulai'] : date('Y-m-01');
$tanggal_akhir = isset($_GET['tanggal_akhir']) ? $_GET['tanggal_akhir'] : date('Y-m-t');

$sql = "SELECT p.judul, p.kode_promo, p.status_promo, p.tanggal_buat, p.tanggal_berakhir, COUNT(ps.id_pesanan) AS jumlah_penggunaan, SUM(ps.diskon) AS total_diskon, AVG(ps.diskon) AS rata_diskon, COUNT(DISTINCT ps.id_pelanggan) AS pelanggan_unik FROM promosi p LEFT JOIN pesanan ps ON p.id_promosi = ps.id_promosi AND ps.tanggal_masuk BETWEEN ? AND ? GROUP BY p.id_promosi, p.judul, p.kode_promo, p.status_promo, p.tanggal_buat, p.tanggal_berakhir ORDER BY p.id_promosi DESC";
$stmt = mysqli_prepare($konek, $sql);
mysqli_stmt_bind_param($stmt, "ss", $tanggal_mulai, $tanggal_akhir);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Laporan Promosi</title>
    <style>
        body { font-family: Arial, sans-serif; }
        h2, h3 { text-align: center; margin: 0; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #333; padding: 8px; text-align: center; }
        th { background: #f2f2f2; }
        .footer { margin-top: 30px; font-size: 12px; }
        .no-print { margin-top: 20px; text-align: center; }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <h2>Laporan Promosi</h2>
    <h3>Periode: <?= date('d/m/Y', strtotime($tanggal_mulai)) ?> - <?= date('d/m/Y', strtotime($tanggal_akhir)) ?></h3>
    <table>
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
            <?php while ($data = mysqli_fetch_assoc($result)): ?>
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
            <?php endwhile; ?>
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
