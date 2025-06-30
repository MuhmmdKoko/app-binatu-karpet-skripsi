<!DOCTYPE html>
<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Cek akses user
if ($_SESSION['role'] != "Admin") {
    echo '<script>alert("Anda tidak memiliki akses ke halaman ini!");window.location.href="../../index.php";</script>';
    exit;
}
?>
<html>
<head>
    <title>Cetak Laporan Pendapatan</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h2, .header p {
            margin: 5px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table, th, td {
            border: 1px solid black;
        }
        th, td {
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .footer {
            margin-top: 30px;
            text-align: right;
        }
        .summary {
            margin-top: 20px;
            width: 50%;
        }
        .summary td:first-child {
            width: 200px;
        }
        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <?php
    include "../pengaturan/koneksi.php";

    $tgl_awal = $_GET['tgl_awal'];
    $tgl_akhir = $_GET['tgl_akhir'];
    $where = "WHERE DATE(p.tanggal_masuk) BETWEEN '$tgl_awal' AND '$tgl_akhir'";

    if(!empty($_GET['status_pembayaran'])) {
        $status_pembayaran = $_GET['status_pembayaran'];
        $where .= " AND p.status_pembayaran = '$status_pembayaran'";
    }

    // Query untuk data per hari
    $query = mysqli_query($konek, "
        SELECT 
            DATE(p.tanggal_masuk) as tanggal,
            COUNT(p.id_pesanan) as jumlah_pesanan,
            SUM(p.total_harga_keseluruhan) as total_pendapatan,
            SUM(CASE WHEN p.status_pembayaran = 'Lunas' THEN p.total_harga_keseluruhan ELSE 0 END) as pendapatan_lunas,
            SUM(CASE WHEN p.status_pembayaran = 'DP' THEN p.nominal_pembayaran ELSE 0 END) as pendapatan_dp,
            SUM(CASE WHEN p.status_pembayaran = 'DP' THEN (p.total_harga_keseluruhan - p.nominal_pembayaran) ELSE 0 END) as sisa_dp,
            SUM(CASE WHEN p.status_pembayaran = 'Belum Lunas' THEN p.total_harga_keseluruhan ELSE 0 END) as pendapatan_belum_lunas
        FROM pesanan p
        $where
        GROUP BY DATE(p.tanggal_masuk)
        ORDER BY tanggal DESC
    ");
    ?>

    <div class="header">
        <h2>Laporan Pendapatan</h2>
        <p>Binatu Karpet</p>
        <p>Periode: <?= date('d/m/Y', strtotime($tgl_awal)) ?> - <?= date('d/m/Y', strtotime($tgl_akhir)) ?></p>
        <?php if(!empty($_GET['status_pembayaran'])): ?>
            <p>Status Pembayaran: <?= htmlspecialchars($_GET['status_pembayaran']) ?></p>
        <?php endif; ?>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>Jumlah Pesanan</th>
                <th>Total Pendapatan</th>
                <th>Pendapatan Lunas</th>
                <th>Pendapatan DP</th>
                <th>Pendapatan Belum Lunas</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $no = 1;
            $total_pesanan = 0;
            $total_pendapatan = 0;
            $total_lunas = 0;
            $total_dp = 0;
            $total_belum_lunas = 0;
            $total_sisa_dp = 0;

            while($data = mysqli_fetch_array($query)) {
                echo "<tr>";
                echo "<td>" . $no++ . "</td>";
                echo "<td>" . date('d/m/Y', strtotime($data['tanggal'])) . "</td>";
                echo "<td>" . number_format($data['jumlah_pesanan']) . "</td>";
                echo "<td>Rp " . number_format($data['total_pendapatan'], 0, ',', '.') . "</td>";
                echo "<td>Rp " . number_format($data['pendapatan_lunas'], 0, ',', '.') . "</td>";
                echo "<td>Rp " . number_format($data['pendapatan_dp'], 0, ',', '.') . "</td>";
                echo "<td>Rp " . number_format(($data['pendapatan_belum_lunas'] + $data['sisa_dp']), 0, ',', '.') . "</td>";
                echo "</tr>";

                $total_pesanan += $data['jumlah_pesanan'];
                $total_pendapatan += $data['total_pendapatan'];
                $total_lunas += $data['pendapatan_lunas'];
                $total_dp += $data['pendapatan_dp'];
                $total_belum_lunas += $data['pendapatan_belum_lunas'];
                $total_sisa_dp += $data['sisa_dp'];
            }
            ?>
            <tr>
                <td colspan="2" align="center"><strong>Total</strong></td>
                <td><strong><?= number_format($total_pesanan) ?></strong></td>
                <td><strong>Rp <?= number_format($total_pendapatan, 0, ',', '.') ?></strong></td>
                <td><strong>Rp <?= number_format($total_lunas, 0, ',', '.') ?></strong></td>
                <td><strong>Rp <?= number_format($total_dp, 0, ',', '.') ?></strong></td>
                <td><strong>Rp <?= number_format($total_belum_lunas + $total_sisa_dp, 0, ',', '.') ?></strong></td>
            </tr>
        </tbody>
    </table>

    <table class="summary">
        <tr>
            <td>Total Hari</td>
            <td><?= number_format($no - 1) ?></td>
        </tr>
        <tr>
            <td>Total Pesanan</td>
            <td><?= number_format($total_pesanan) ?></td>
        </tr>
        <tr>
            <td>Total Pendapatan</td>
            <td>Rp <?= number_format($total_pendapatan, 0, ',', '.') ?></td>
        </tr>
        <tr>
            <td>Total Pendapatan Diterima</td>
            <td>Rp <?= number_format($total_lunas + $total_dp, 0, ',', '.') ?></td>
        </tr>
        <tr>
            <td>Total Pendapatan Belum Diterima</td>
            <td>Rp <?= number_format($total_belum_lunas + $total_sisa_dp, 0, ',', '.') ?></td>
        </tr>
        <tr>
            <td>Rata-rata Pesanan per Hari</td>
            <td><?= ($no - 1) > 0 ? number_format($total_pesanan / ($no - 1), 1) : '0.0' ?></td>
        </tr>
        <tr>
            <td>Rata-rata Pendapatan per Hari</td>
            <td>Rp <?= ($no - 1) > 0 ? number_format($total_pendapatan / ($no - 1), 0, ',', '.') : '0' ?></td>
        </tr>
    </table>

    <div class="footer">
        <p>Dicetak pada: <?= date('d/m/Y H:i:s') ?></p>
        <p>Dicetak oleh: <?= htmlspecialchars($_SESSION['nama_lengkap']) ?></p>
    </div>

    <div class="no-print" style="margin-top: 20px;">
        <button onclick="window.print()">Cetak</button>
        <button onclick="window.close()">Tutup</button>
    </div>

    <script>
        // Otomatis print saat halaman dimuat
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html> 