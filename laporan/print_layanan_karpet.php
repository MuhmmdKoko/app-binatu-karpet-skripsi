<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Cek akses user
if ($_SESSION['role'] != "Admin") {
    echo '<script>alert("Anda tidak memiliki akses ke halaman ini!");window.location.href="../../index.php";</script>';
    exit;
}

include "../pengaturan/koneksi.php";

$tgl_awal = $_GET['tgl_awal'];
$tgl_akhir = $_GET['tgl_akhir'];
$filter_layanan = isset($_GET['layanan']) ? trim(urldecode($_GET['layanan'])) : '';

// Query untuk data pesanan karpet
$sql = "
    SELECT 
        p.id_pesanan,
        p.nomor_invoice,
        p.tanggal_masuk,
        p.status_pesanan_umum,
        pl.nama_pelanggan,
        pl.nomor_telepon,
        pl.alamat,
        l.nama_layanan,
        dp.kuantitas,
        dp.panjang_karpet,
        dp.lebar_karpet
    FROM pesanan p
    JOIN detail_pesanan dp ON p.id_pesanan = dp.id_pesanan
    JOIN layanan l ON dp.id_layanan = l.id_layanan
    JOIN pelanggan pl ON p.id_pelanggan = pl.id_pelanggan
    LEFT JOIN pengguna pg ON p.id_pengguna_penerima = pg.id_pengguna
    WHERE DATE(p.tanggal_masuk) BETWEEN '$tgl_awal' AND '$tgl_akhir' ";

if ($filter_layanan !== '') {
    $sql .= " AND l.nama_layanan = '" . mysqli_real_escape_string($konek, $filter_layanan) . "' ";
} else {
    $sql .= " AND l.nama_layanan LIKE '%Karpet%' ";
}
$sql .= " ORDER BY p.tanggal_masuk DESC";

$query_karpet = mysqli_query($konek, $sql);

if (!$query_karpet) {
    die("Error in query: " . mysqli_error($konek));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Layanan Karpet</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .summary {
            margin-top: 20px;
        }
        .metrics {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
            margin-bottom: 20px;
        }
        .metric-box {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
            width: 30%;
        }
        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>BERKAT LAUNDRY</h2>
        <h3>Laporan Layanan Karpet</h3>
        <p>Periode: <?= date('d/m/Y', strtotime($tgl_awal)) ?> - <?= date('d/m/Y', strtotime($tgl_akhir)) ?></p>
    </div>

    <?php
    $temp_data = array();
    if ($query_karpet) {
        while($data = mysqli_fetch_array($query_karpet)) {
            $temp_data[] = $data;
        }
    }
?>
<?php if (count($temp_data) > 0): ?>
    <table>
    <thead>
        <tr>
            <th>No</th>
            <th>No. Invoice</th>
            <th>Tanggal</th>
            <th>Pelanggan</th>
            <th>Layanan</th>
            <th>Ukuran (m)</th>
            <th>Kuantitas</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $no = 1;
        $total_karpet = 0;
        foreach($temp_data as $data) {
            echo "<tr>";
            echo "<td>" . $no++ . "</td>";
            echo "<td>" . htmlspecialchars($data['nomor_invoice']) . "</td>";
            echo "<td>" . date('d/m/Y H:i', strtotime($data['tanggal_masuk'])) . "</td>";
            echo "<td>" . htmlspecialchars($data['nama_pelanggan']) . "</td>";
            echo "<td>" . htmlspecialchars($data['nama_layanan']) . "</td>";
            if (isset($data['panjang_karpet']) && isset($data['lebar_karpet']) && $data['panjang_karpet'] && $data['lebar_karpet']) {
                $ukuran = number_format($data['panjang_karpet'], 2, ',', '.') . ' x ' . number_format($data['lebar_karpet'], 2, ',', '.') . ' m';
            } else {
                $ukuran = '-';
            }
            echo "<td>" . $ukuran . "</td>";
            echo "<td>" . number_format($data['kuantitas'], 2, ',', '.') . "</td>";
            echo "</tr>";
            $total_karpet += $data['kuantitas'];
        }
        ?>
        <tr>
            <td colspan="6" align="right"><strong>Total Kuantitas</strong></td>
            <td><strong><?= number_format($total_karpet, 2, ',', '.') ?></strong></td>
        </tr>
    </tbody>
</table>
    <?php else: ?>
    <div class="alert alert-info">
        <p>Tidak ada data layanan karpet untuk periode yang dipilih.</p>
    </div>
    <?php endif; ?>

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