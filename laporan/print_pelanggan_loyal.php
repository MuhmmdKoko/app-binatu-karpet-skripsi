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
    <title>Cetak Laporan Pelanggan Loyal</title>
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

    $tgl_awal = isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : '';
    $tgl_akhir = isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : '';
    $min_pesanan = (isset($_GET['min_pesanan']) && $_GET['min_pesanan'] !== '' && is_numeric($_GET['min_pesanan'])) ? intval($_GET['min_pesanan']) : 1;
    $total_nilai_minimal = (isset($_GET['total_nilai_minimal']) && $_GET['total_nilai_minimal'] !== '' && is_numeric($_GET['total_nilai_minimal'])) ? intval($_GET['total_nilai_minimal']) : 0;
    // $terakhir_transaksi hanya untuk display, tidak dipakai filter di query
    $terakhir_transaksi = isset($_GET['terakhir_transaksi']) ? $_GET['terakhir_transaksi'] : '';

    // Query untuk data utama
    $query = mysqli_query($konek, "
    SELECT 
        pl.*,
        COUNT(p.id_pesanan) AS jumlah_pesanan,
        SUM(p.total_harga_keseluruhan) AS total_nilai_pesanan,
        AVG(p.total_harga_keseluruhan) AS rata_nilai_pesanan,
        MAX(p.tanggal_masuk) AS terakhir_transaksi
    FROM pelanggan pl
    JOIN pesanan p ON pl.id_pelanggan = p.id_pelanggan
    WHERE 1=1
    " . (
        (!empty($tgl_awal) && !empty($tgl_akhir))
            ? " AND p.tanggal_masuk BETWEEN '$tgl_awal' AND '$tgl_akhir'"
            : ""
      ) . "
      
    GROUP BY pl.id_pelanggan
    HAVING COUNT(p.id_pesanan) >= $min_pesanan
       AND SUM(p.total_harga_keseluruhan) >= $total_nilai_minimal
    ORDER BY jumlah_pesanan DESC, total_nilai_pesanan DESC
");
if(!$query) {
    die("Error SQL: " . mysqli_error($konek));
}
    ?>

    <div class="header">
        <h2>BERKAT LAUNDRY</h2>
        <h3>Laporan Pelanggan Loyal</h3>
        <p>Binatu Karpet</p>
        <p>Periode: 
    <?php 
        if (!empty($tgl_awal) && !empty($tgl_akhir) && strtotime($tgl_awal) && strtotime($tgl_akhir)) {
            echo date('d/m/Y', strtotime($tgl_awal)) . " - " . date('d/m/Y', strtotime($tgl_akhir));
        } else {
            echo "Semua Periode";
        }
    ?>
</p>
        <p>Minimal Pesanan: <?= $min_pesanan ?> pesanan</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Pelanggan</th>
                <th>No. Telepon</th>
                <th>Alamat</th>
                <th>Jumlah Pesanan</th>
                <th>Total Nilai Pesanan</th>
                <th>Rata-rata Nilai Pesanan</th>
            <th>Terakhir Transaksi</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $no = 1;
            $total_pelanggan = 0;
            $total_pesanan = 0;
            $total_nilai = 0;

            while($data = mysqli_fetch_array($query)) {
                echo "<tr>";
                echo "<td>" . $no++ . "</td>";
                echo "<td>" . htmlspecialchars($data['nama_pelanggan']) . "</td>";
                echo "<td>" . htmlspecialchars($data['nomor_telepon']) . "</td>";
                echo "<td>" . htmlspecialchars($data['alamat']) . "</td>";
                echo "<td>" . number_format($data['jumlah_pesanan']) . "</td>";
                echo "<td>Rp " . number_format($data['total_nilai_pesanan'], 0, ',', '.') . "</td>";
                echo "<td>Rp " . number_format($data['rata_nilai_pesanan'], 0, ',', '.') . "</td>";
            echo "<td>" . date('d/m/Y', strtotime($data['terakhir_transaksi'])) . "</td>";
                echo "</tr>";

                $total_pelanggan++;
                $total_pesanan += $data['jumlah_pesanan'];
                $total_nilai += $data['total_nilai_pesanan'];
            }
            ?>
            <tr>
                <td colspan="4" align="center"><strong>Total</strong></td>
                <td><strong><?= number_format($total_pesanan) ?></strong></td>
                <td><strong>Rp <?= number_format($total_nilai, 0, ',', '.') ?></strong></td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <table class="summary">
        <tr>
            <td>Total Pelanggan Loyal</td>
            <td><?= number_format($total_pelanggan) ?></td>
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
            <td>Rata-rata Pesanan per Pelanggan</td>
            <td><?= number_format($total_pesanan / ($total_pelanggan ?: 1), 1) ?></td>
        </tr>
        <tr>
            <td>Rata-rata Nilai per Pelanggan</td>
            <td>Rp <?= number_format($total_nilai / ($total_pelanggan ?: 1), 0, ',', '.') ?></td>
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