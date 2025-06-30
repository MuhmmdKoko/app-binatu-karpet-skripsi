<?php
include "../pengaturan/koneksi.php";

$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Query untuk data analisis waktu
$query_waktu = mysqli_query($konek, "
    SELECT 
        p.nomor_invoice,
        p.tanggal_masuk,
        p.tanggal_selesai_aktual,
        p.tanggal_estimasi_selesai,
        pl.nama_pelanggan,
        GROUP_CONCAT(l.nama_layanan SEPARATOR ', ') as layanan,
        TIMESTAMPDIFF(HOUR, p.tanggal_masuk, p.tanggal_selesai_aktual) as durasi_jam
    FROM pesanan p
    JOIN detail_pesanan dp ON p.id_pesanan = dp.id_pesanan
    JOIN layanan l ON dp.id_layanan = l.id_layanan
    JOIN pelanggan pl ON p.id_pelanggan = pl.id_pelanggan
    WHERE DATE(p.tanggal_masuk) BETWEEN '$start_date' AND '$end_date'
    
    GROUP BY p.id_pesanan, p.nomor_invoice, p.tanggal_masuk, p.tanggal_selesai_aktual, p.tanggal_estimasi_selesai, pl.nama_pelanggan
    ORDER BY p.tanggal_masuk DESC
");
if (!$query_waktu) {
    die("Query error: " . mysqli_error($konek));
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Analisis Waktu</title>
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
        <h2>CV. KARYA UTAMA</h2>
        <h3>Laporan Analisis Waktu Pengerjaan</h3>
        <p>Periode: <?= date('d/m/Y', strtotime($start_date)) ?> - <?= date('d/m/Y', strtotime($end_date)) ?></p>
    </div>

    <div class="metrics">
        <?php
        $total_durasi = 0;
        $pesanan_tepat_waktu = 0;
        $temp_data = array();
        $total_pesanan_selesai = 0;

        while($data = mysqli_fetch_array($query_waktu)) {
            $durasi = $data['durasi_jam'];
            $temp_data[] = $data;
            if ($data['tanggal_selesai_aktual']) {
                if($durasi <= 48) {
                    $pesanan_tepat_waktu++;
                }
                $total_pesanan_selesai++;
                $total_durasi += $durasi;
            }
        }
        $total_pesanan = count($temp_data); // seluruh pesanan, selesai + belum selesai
        $rata_rata_durasi = $total_pesanan_selesai > 0 ? round($total_durasi / $total_pesanan_selesai, 1) : 0;
        ?>
        <div class="metric-box">
            <h4>Total Pesanan</h4>
            <h3><?= number_format($total_pesanan) ?></h3>
        </div>
        <div class="metric-box">
            <h4>Rata-rata Durasi</h4>
            <h3><?= $rata_rata_durasi ?> jam</h3>
        </div>
        <div class="metric-box">
            <h4>Ketepatan Waktu</h4>
            <h3><?= $total_pesanan > 0 ? round(($pesanan_tepat_waktu / $total_pesanan) * 100) : 0 ?>%</h3>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>No. Invoice</th>
                <th>Tanggal Masuk</th>
                <th>Tanggal Selesai</th>
                <th>Estimasi Selesai</th>
                <th>Pelanggan</th>
                <th>Layanan</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $no = 1;
            foreach($temp_data as $data) {
                $durasi = $data['durasi_jam'];
                if ($data['tanggal_selesai_aktual']) {
                    $tepat_waktu = $durasi <= 48 ? 'Tepat Waktu' : 'Terlambat';
                } else {
                    $tepat_waktu = '-';
                }

                echo "<tr>";
                echo "<td>" . $no++ . "</td>";
                echo "<td>" . htmlspecialchars($data['nomor_invoice']) . "</td>";
                echo "<td>" . date('d/m/Y H:i', strtotime($data['tanggal_masuk'])) . "</td>";
                echo "<td>" . ($data['tanggal_selesai_aktual'] ? date('d/m/Y H:i', strtotime($data['tanggal_selesai_aktual'])) : '-') . "</td>";
                echo "<td>" . date('d/m/Y H:i', strtotime($data['tanggal_estimasi_selesai'])) . "</td>";
                echo "<td>" . htmlspecialchars($data['nama_pelanggan']) . "</td>";
                echo "<td>" . htmlspecialchars($data['layanan']) . "</td>";
                echo "<td>" . $tepat_waktu . "</td>";
                echo "</tr>";
            }
            ?>
        </tbody>
    </table>

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