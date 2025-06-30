<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Cek akses user
if ($_SESSION['role'] != "Admin") {
    echo '<script>alert("Anda tidak memiliki akses ke halaman ini!");window.location.href="../../index.php";</script>';
    exit;
}

include "../pengaturan/koneksi.php";

// Validasi parameter
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';
$id_pengguna = isset($_GET['id_pengguna']) ? $_GET['id_pengguna'] : '';
if (!$start_date || !$end_date) {
    die("Error: Missing date parameters");
}

// Add WHERE clause for specific employee if selected
$where_pengguna = "";
if (!empty($id_pengguna)) {
    $where_pengguna = " AND p.id_pengguna_penerima = '" . mysqli_real_escape_string($konek, $id_pengguna) . "'";
}

// Jika id_pengguna tidak kosong, ambil data karyawan
$karyawan = null;
if (!empty($id_pengguna)) {
    $query_karyawan = mysqli_query($konek, "
        SELECT nama_lengkap, role, nomor_telepon_internal
        FROM pengguna 
        WHERE id_pengguna = '$id_pengguna'
    ");
    if (!$query_karyawan) {
        die("Error in karyawan query: " . mysqli_error($konek));
    }
    $karyawan = mysqli_fetch_array($query_karyawan);
    if (!$karyawan) {
        die("Error: Karyawan not found");
    }
}

// Query untuk data kinerja
$query_kinerja = mysqli_query($konek, "
    SELECT 
        p.id_pesanan,
        p.nomor_invoice,
        p.tanggal_masuk,
        p.tanggal_selesai_aktual as tanggal_selesai,
        p.status_pesanan_umum as status_pesanan,
        pl.nama_pelanggan,
        pg.nama_lengkap as nama_karyawan,
        GROUP_CONCAT(l.nama_layanan SEPARATOR ', ') as layanan,
        SUM(dp.kuantitas) as total_item
    FROM pesanan p
    JOIN detail_pesanan dp ON p.id_pesanan = dp.id_pesanan
    JOIN layanan l ON dp.id_layanan = l.id_layanan
    JOIN pelanggan pl ON p.id_pelanggan = pl.id_pelanggan
    JOIN pengguna pg ON p.id_pengguna_penerima = pg.id_pengguna
    WHERE DATE(p.tanggal_masuk) BETWEEN '$start_date' AND '$end_date'
    $where_pengguna
    GROUP BY p.id_pesanan, p.nomor_invoice, p.tanggal_masuk, p.tanggal_selesai_aktual, p.status_pesanan_umum, pl.nama_pelanggan, pg.nama_lengkap
    ORDER BY p.tanggal_masuk DESC
");

if (!$query_kinerja) {
    die("Error in kinerja query: " . mysqli_error($konek));
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Kinerja Karyawan</title>
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
        <h3>Laporan Kinerja Karyawan</h3>
        <p>
            <?php if ($karyawan): ?>
                Nama Karyawan: <?= htmlspecialchars($karyawan['nama_lengkap']) ?><br>
                Jabatan: <?= htmlspecialchars($karyawan['role']) ?><br>
                No. Telepon: <?= htmlspecialchars($karyawan['nomor_telepon_internal']) ?><br>
            <?php else: ?>
                <b>Semua Karyawan</b><br>
            <?php endif; ?>
            Periode: <?= date('d/m/Y', strtotime($start_date)) ?> - <?= date('d/m/Y', strtotime($end_date)) ?>
        </p>
    </div>

    <div class="metrics">
        <?php
        $total_pesanan = 0;
        $total_item = 0;
        $pesanan_tepat_waktu = 0;
        $total_selesai = 0; // hanya pesanan selesai
        $temp_data = array();

        while($data = mysqli_fetch_array($query_kinerja)) {
            if (!empty($data['tanggal_selesai'])) {
                $waktu_selesai = strtotime($data['tanggal_selesai']) - strtotime($data['tanggal_masuk']);
                $waktu_selesai_jam = round($waktu_selesai / (60 * 60));
                if($waktu_selesai_jam <= 48) {
                    $pesanan_tepat_waktu++;
                }
                $total_selesai++;
            }
            $total_pesanan++;
            $total_item += $data['total_item'];
            $temp_data[] = $data;
        }
        ?>
        <div class="metric-box">
            <h4>Total Pesanan</h4>
            <h3><?= number_format($total_pesanan) ?></h3>
        </div>
        <div class="metric-box">
            <h4>Total Item</h4>
            <h3><?= number_format($total_item) ?></h3>
        </div>
        <div class="metric-box">
            <h4>Ketepatan Waktu</h4>
            <h3><?= $total_selesai > 0 ? round(($pesanan_tepat_waktu / $total_selesai) * 100) : 0 ?>%</h3>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>No. Invoice</th>
                <th>Tanggal</th>
                <th>Pelanggan</th>
                <th>Pengguna</th>
                <th>Layanan</th>
                <th>Jumlah Item</th>
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
                echo "<td>" . htmlspecialchars($data['nama_karyawan']) . "</td>";
                echo "<td>" . htmlspecialchars($data['layanan']) . "</td>";
                echo "<td>" . htmlspecialchars($data['total_item']) . "</td>";
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