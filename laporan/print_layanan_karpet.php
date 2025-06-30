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

// Query untuk data pesanan karpet
$query_karpet = mysqli_query($konek, "
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
        dp.harga_saat_pesan as harga,
        dp.subtotal_item as subtotal,
        p.total_setelah_diskon,
        pg.nama_lengkap as penerima
    FROM pesanan p
    JOIN detail_pesanan dp ON p.id_pesanan = dp.id_pesanan
    JOIN layanan l ON dp.id_layanan = l.id_layanan
    JOIN pelanggan pl ON p.id_pelanggan = pl.id_pelanggan
    LEFT JOIN pengguna pg ON p.id_pengguna_penerima = pg.id_pengguna
    WHERE l.nama_layanan LIKE '%Karpet%'
    AND DATE(p.tanggal_masuk) BETWEEN '$tgl_awal' AND '$tgl_akhir'
    ORDER BY p.tanggal_masuk DESC
");

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

    <div class="metrics">
        <?php
        $total_karpet = 0;
        $total_nilai = 0;
        $total_promo = 0;
        $total_nonpromo = 0;
        $temp_data = array();

        if ($query_karpet) {
            while($data = mysqli_fetch_array($query_karpet)) {
                $is_promo = ($data['total_setelah_diskon'] !== null && $data['total_setelah_diskon'] > 0);
                $nilai = $is_promo ? $data['total_setelah_diskon'] : $data['subtotal'];
                $total_karpet += $data['kuantitas'];
                $total_nilai += $nilai;
                if ($is_promo) {
                    $total_promo += $nilai;
                } else {
                    $total_nonpromo += $nilai;
                }
                // Simpan nilai yang sudah dipilih ke array
                $data['nilai_final'] = $nilai;
                $data['is_promo'] = $is_promo;
                $temp_data[] = $data;
            }
        }
        ?>
        <div class="metric-box">
            <h4>Total Karpet</h4>
            <h3><?= number_format($total_karpet) ?></h3>
        </div>
        <div class="metric-box">
            <h4>Total Nilai Layanan</h4>
            <h3>Rp <?= number_format($total_nilai, 0, ',', '.') ?></h3>
        </div>
    </div>

    <?php if (count($temp_data) > 0): ?>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>No. Invoice</th>
                <th>Tanggal</th>
                <th>Pelanggan</th>
                <th>Layanan</th>
                <th>Jumlah</th>
                <th>Harga</th>
                <th>Subtotal</th>
                <th>Status</th>
                <th>Penerima</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $no = 1;
            foreach($temp_data as $data) {
                echo "<tr>";
                echo "<td>" . $no++ . "</td>";
                echo "<td>" . htmlspecialchars($data['nomor_invoice']) . "</td>";
                echo "<td>" . date('d/m/Y H:i', strtotime($data['tanggal_masuk'])) . "</td>";
                echo "<td>" . htmlspecialchars($data['nama_pelanggan']) . "</td>";
                echo "<td>" . htmlspecialchars($data['nama_layanan']) . "</td>";
                echo "<td>" . htmlspecialchars($data['kuantitas']) . "</td>";
                echo "<td>Rp " . number_format($data['harga'], 0, ',', '.') . "</td>";
                echo "<td>Rp " . number_format($data['nilai_final'], 0, ',', '.') . "</td>";
                echo "<td>" . htmlspecialchars($data['status_pesanan_umum']) . "</td>";
                echo "<td>" . htmlspecialchars($data['penerima']) . "</td>";
                echo "</tr>";
            }
            ?>
            <tr>
                <td colspan="7" align="center"><strong>Total</strong></td>
                <td><strong>Rp <?= number_format($total_nilai, 0, ',', '.') ?></strong></td>
                <td colspan="2"></td>
            </tr>
            <tr>
                <td colspan="7" align="right"><strong>Total Promo</strong></td>
                <td colspan="2"><strong style="color:green;">Rp <?= number_format($total_promo, 0, ',', '.') ?></strong></td>
                <td colspan="2"></td>
            </tr>
            <tr>
                <td colspan="7" align="right"><strong>Total Non-Promo</strong></td>
                <td colspan="2"><strong style="color:blue;">Rp <?= number_format($total_nonpromo, 0, ',', '.') ?></strong></td>
                <td colspan="2"></td>
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