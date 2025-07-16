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
        l.nama_layanan,
        TIMESTAMPDIFF(HOUR, p.tanggal_masuk, p.tanggal_selesai_aktual) as durasi_jam
    FROM pesanan p
    JOIN detail_pesanan dp ON p.id_pesanan = dp.id_pesanan
    JOIN layanan l ON dp.id_layanan = l.id_layanan
    JOIN pelanggan pl ON p.id_pelanggan = pl.id_pelanggan
    WHERE DATE(p.tanggal_masuk) BETWEEN '$start_date' AND '$end_date'
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
    <div class="header" style="text-align:center; margin-bottom:16px;">
        <h2 style="margin-bottom:2px;">BERKAT LAUNDRY</h2>
        <!-- <img src="../assets/logo-laundry.png" alt="Logo" style="height:60px;"> -->
        <div style="font-size:15px; margin-bottom:8px;">Laporan Analisis Waktu Pengerjaan</div>
        <div style="margin-bottom:2px;">
            <strong>Periode:</strong> <?= date('d/m/Y', strtotime($start_date)) ?> - <?= date('d/m/Y', strtotime($end_date)) ?>
        </div>
        <?php if (!empty($_GET['jenis_layanan'])): ?>
        <div style="font-size:13px; margin-bottom:2px;"><strong>Layanan:</strong> <?= htmlspecialchars($_GET['jenis_layanan']) ?></div>
        <?php endif; ?>
    </div>

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
    // Fungsi format hari+jam
function format_hari_jam($total_jam) {
    $hari = floor($total_jam / 24);
    $jam = $total_jam % 24;
    return $hari . ' hari ' . $jam . ' jam';
}
$rata_rata_jam = $total_pesanan_selesai > 0 ? round($total_durasi / $total_pesanan_selesai) : 0;
$rata_rata_durasi_str = format_hari_jam($rata_rata_jam);
    ?>
    <table class="summary" style="margin-bottom:18px; border:1px solid #bbb; background:#fafcff; border-radius:7px; box-shadow:0 1px 2px #eee; width:60%; margin-left:auto; margin-right:auto;">
        <tr>
            <td style="padding:8px 18px; border-right:1px solid #eee;"><strong>Total Layanan</strong><br><span style="font-size:18px; font-weight:bold; color:#1a7f37;"> <?= number_format($total_pesanan) ?></span></td>
            <td style="padding:8px 18px; border-right:1px solid #eee;"><strong>Rata-rata Durasi</strong><br><span style="font-size:18px; font-weight:bold; color:#0d6efd;"> <?= $rata_rata_durasi_str ?></span></td>
            <td style="padding:8px 18px;"><strong>Ketepatan Waktu</strong><br><span style="font-size:18px; font-weight:bold; color:#f59e00;"> <?= $total_pesanan > 0 ? round(($pesanan_tepat_waktu / $total_pesanan) * 100) : 0 ?>%</span></td>
        </tr>
    </table>

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
                <th>Durasi</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $no = 1;
            foreach($temp_data as $data) {
                $durasi = $data['durasi_jam'];
                $estimasi_jam = round((strtotime($data['tanggal_estimasi_selesai']) - strtotime($data['tanggal_masuk'])) / 3600);
                if ($data['tanggal_selesai_aktual']) {
                    if ($durasi > $estimasi_jam) {
                        $selisih_jam = $durasi - $estimasi_jam;
                        $tepat_waktu = 'Terlambat ' . format_hari_jam($selisih_jam);
                    } else {
                        $tepat_waktu = 'Tepat Waktu';
                    }
                } else {
                    // Hitung durasi berjalan dari tanggal masuk hingga sekarang
                    $durasi_berjalan = round((time() - strtotime($data['tanggal_masuk'])) / 3600);
                    if ($durasi_berjalan > $estimasi_jam) {
                        $selisih_jam = $durasi_berjalan - $estimasi_jam;
                        $tepat_waktu = 'Terlambat ' . format_hari_jam($selisih_jam);
                    } else {
                        $tepat_waktu = '-';
                    }
                }

                echo "<tr>";
                echo "<td>" . $no++ . "</td>";
                echo "<td>" . htmlspecialchars($data['nomor_invoice']) . "</td>";
                echo "<td>" . date('d/m/Y H:i', strtotime($data['tanggal_masuk'])) . "</td>";
                echo "<td>" . ($data['tanggal_selesai_aktual'] ? date('d/m/Y H:i', strtotime($data['tanggal_selesai_aktual'])) : '-') . "</td>";
                echo "<td>" . date('d/m/Y H:i', strtotime($data['tanggal_estimasi_selesai'])) . "</td>";
                echo "<td>" . htmlspecialchars($data['nama_pelanggan']) . "</td>";
                echo "<td>" . htmlspecialchars($data['nama_layanan']) . "</td>";
                $durasi_str = $data['durasi_jam'] ? format_hari_jam($data['durasi_jam']) : '-';
                echo "<td>" . $durasi_str . "</td>";
                echo "<td>" . $tepat_waktu . "</td>";
                echo "</tr>";
            }
            ?>
        </tbody>
    </table>

    <div class="no-print" style="margin-top:18px; text-align:center;">
        <button onclick="window.print()">Cetak</button>
        <button onclick="window.close()">Tutup</button>
    </div>

    <div style="width:100%; margin-top:30px; font-size:12px; color:#888; text-align:right;">
        Dicetak oleh: <?= isset($_SESSION['nama']) ? htmlspecialchars($_SESSION['nama']) : 'Administrator' ?> | Tanggal cetak: <?= date('d/m/Y H:i', strtotime('now')) ?>
    </div>

    <style>
    @media print {
        .no-print { display: none; }
        body { margin: 0 10mm 0 10mm; }
        @page { margin: 10mm 10mm 15mm 10mm; }
        .summary { page-break-inside: avoid; }
        table { page-break-inside: auto; }
        tr { page-break-inside: avoid; page-break-after: auto; }
        /* Page numbering */
        body:after {
            content: "Halaman " counter(page);
            position: fixed;
            bottom: 0;
            right: 0;
            font-size: 11px;
            color: #888;
        }
    }
    </style>

    <script>
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html>