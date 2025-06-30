<?php
include dirname(__DIR__) . "/pengaturan/koneksi.php";

$tgl_awal = $_GET['tgl_awal'];
$tgl_akhir = $_GET['tgl_akhir'];
$tipe_channel = isset($_GET['tipe_channel']) ? $_GET['tipe_channel'] : '';
$filter_by = isset($_GET['filter_by']) ? $_GET['filter_by'] : 'waktu_kirim';

// Validate filter_by to avoid SQL injection
$allowed_filters = ['waktu_kirim', 'waktu_dijadwalkan', 'created_at'];
if (!in_array($filter_by, $allowed_filters)) {
    $filter_by = 'waktu_kirim';
}

// Build WHERE clause
$where = "WHERE DATE(n.$filter_by) BETWEEN '" . mysqli_real_escape_string($konek, $tgl_awal) . "' AND '" . mysqli_real_escape_string($konek, $tgl_akhir) . "'";
if (!empty($tipe_channel)) {
    $where .= " AND n.tipe_channel = '" . mysqli_real_escape_string($konek, $tipe_channel) . "'";
}

// Query untuk data notifikasi
$query_sql = "
    SELECT 
        n.*,
        p.nomor_invoice,
        pl.nama_pelanggan
    FROM notifikasi n
    LEFT JOIN pesanan p ON n.id_pesanan = p.id_pesanan
    LEFT JOIN pelanggan pl ON p.id_pelanggan = pl.id_pelanggan
    $where
    ORDER BY n.$filter_by DESC
";
$query_notifikasi = mysqli_query($konek, $query_sql);
if (!$query_notifikasi) {
    echo '<b>SQL Error:</b> ' . mysqli_error($konek) . '<br><b>Query:</b> ' . htmlspecialchars($query_sql);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Notifikasi</title>
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
        <h3>Laporan Notifikasi</h3>
        <p>Periode: <?= date('d/m/Y', strtotime($tgl_awal)) ?> - <?= date('d/m/Y', strtotime($tgl_akhir)) ?></p>
    </div>

    <div class="metrics">
        <?php
        $total_notifikasi = 0;
        $notifikasi_terkirim = 0;
        $notifikasi_dibaca = 0;
        $temp_data = array();

        while($data = mysqli_fetch_array($query_notifikasi)) {
            $total_notifikasi++;
            if(isset($data['status_pengiriman']) && $data['status_pengiriman'] == 'Terkirim') {
                $notifikasi_terkirim++;
            }
            if(isset($data['status_pengiriman']) && $data['status_pengiriman'] == 'Dibaca') {
                $notifikasi_dibaca++;
            }
            $temp_data[] = $data;
        }
        ?>
        <div class="metric-box">
            <h4>Total Notifikasi</h4>
            <h3><?= number_format($total_notifikasi) ?></h3>
        </div>
        <div class="metric-box">
            <h4>Terkirim</h4>
            <h3><?= number_format($notifikasi_terkirim) ?></h3>
        </div>
        <div class="metric-box">
            <h4>Dibaca</h4>
            <h3><?= number_format($notifikasi_dibaca) ?></h3>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Waktu</th>
                <th>No. Invoice</th>
                <th>Pelanggan</th>
                <th>Pesan</th>
                <th>Status</th>
                <th>Pengirim</th>
                <th>Tipe</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $no = 1;
            foreach($temp_data as $data) {
                echo "<tr>";
                echo "<td>" . $no++ . "</td>";
                // Use waktu_kirim, waktu_dijadwalkan, or created_at based on filter_by
                $waktu = isset($data[$filter_by]) ? $data[$filter_by] : (isset($data['waktu_kirim']) ? $data['waktu_kirim'] : '');
                echo "<td>" . ($waktu ? date('d/m/Y H:i', strtotime($waktu)) : '-') . "</td>";
                echo "<td>" . htmlspecialchars(isset($data['nomor_invoice']) ? $data['nomor_invoice'] : '-') . "</td>";
                echo "<td>" . htmlspecialchars(isset($data['nama_pelanggan']) ? $data['nama_pelanggan'] : '-') . "</td>";
                echo "<td>" . htmlspecialchars(isset($data['isi_pesan']) ? $data['isi_pesan'] : '-') . "</td>";
                echo "<td>" . htmlspecialchars(isset($data['status_pengiriman']) ? $data['status_pengiriman'] : '-') . "</td>";
                // Remove pengirim (not available)
                echo "<td>-</td>";
                echo "<td>" . htmlspecialchars(isset($data['tipe_channel']) ? $data['tipe_channel'] : '-') . "</td>";
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