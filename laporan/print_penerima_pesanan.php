<!DOCTYPE html>
<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Cek akses user
if ($_SESSION['role'] != "Admin") {
    echo '<script>alert("Anda tidak memiliki akses ke halaman ini!");window.location.href="../../index.php";</script>';
    exit;
}
$nama_lengkap = isset($_SESSION['nama_lengkap']) ? $_SESSION['nama_lengkap'] : '-';
session_write_close();
?>
<html>
<head>
    <title>Cetak Laporan Penerima Pesanan</title>
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

    if(!empty($_GET['id_pengguna'])) {
        $id_pengguna = $_GET['id_pengguna'];
        $where .= " AND p.id_pengguna_penerima = '$id_pengguna'";
    }

    // Query untuk data utama
    $query = mysqli_query($konek, "
        SELECT 
            pg.nama_lengkap,
            COUNT(p.id_pesanan) as jumlah_pesanan,
            SUM(p.total_harga_keseluruhan) as total_nilai_pesanan
        FROM pengguna pg
        LEFT JOIN pesanan p ON pg.id_pengguna = p.id_pengguna_penerima
        $where
        GROUP BY pg.id_pengguna
        ORDER BY jumlah_pesanan DESC
    ");
    ?>

    <div class="header">
        <h2>Laporan Penerima Pesanan</h2>
        <p>Binatu Karpet</p>
        <p>Periode: <?= date('d/m/Y', strtotime($tgl_awal)) ?> - <?= date('d/m/Y', strtotime($tgl_akhir)) ?></p>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Penerima</th>
                <th>Jumlah Pesanan</th>
                <th>Total Nilai Pesanan</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $no = 1;
            $total_pesanan = 0;
            $total_nilai = 0;

            while($data = mysqli_fetch_array($query)) {
                echo "<tr>";
                echo "<td>" . $no++ . "</td>";
                echo "<td>" . htmlspecialchars($data['nama_lengkap']) . "</td>";
                echo "<td>" . number_format($data['jumlah_pesanan']) . "</td>";
                echo "<td>Rp " . number_format($data['total_nilai_pesanan'], 0, ',', '.') . "</td>";
                echo "</tr>";

                $total_pesanan += $data['jumlah_pesanan'];
                $total_nilai += $data['total_nilai_pesanan'];
            }
            ?>
            <tr>
                <td colspan="2" align="center"><strong>Total</strong></td>
                <td><strong><?= number_format($total_pesanan) ?></strong></td>
                <td><strong>Rp <?= number_format($total_nilai, 0, ',', '.') ?></strong></td>
            </tr>
        </tbody>
    </table>

    <table class="summary">
        <tr>
            <td>Total Penerima Pesanan</td>
            <td><?= number_format($no - 1) ?></td>
        </tr>
        <tr>
            <td>Total Pesanan</td>
            <td><?= number_format($total_pesanan) ?></td>
        </tr>
        <tr>
            <td>Total Nilai Pesanan</td>
            <td>Rp <?= number_format($total_nilai, 0, ',', '.') ?></td>
        </tr>
        <tr>
            <td>Rata-rata Pesanan per Penerima</td>
            <td><?= ($no - 1) > 0 ? number_format($total_pesanan / ($no - 1), 1) : '0.0' ?></td>
        </tr>
        <tr>
            <td>Rata-rata Nilai per Penerima</td>
            <td>Rp <?= ($no - 1) > 0 ? number_format($total_nilai / ($no - 1), 0, ',', '.') : '0' ?></td>
        </tr>
    </table>

    <div class="footer">
        <p>Dicetak pada: <?= date('d/m/Y H:i:s') ?></p>
        <p>Dicetak oleh: <?= htmlspecialchars($nama_lengkap) ?></p>
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