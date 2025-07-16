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
        l.nama_layanan,
        dp.kuantitas as total_item
    FROM pesanan p
    JOIN detail_pesanan dp ON p.id_pesanan = dp.id_pesanan
    JOIN layanan l ON dp.id_layanan = l.id_layanan
    JOIN pelanggan pl ON p.id_pelanggan = pl.id_pelanggan
    JOIN pengguna pg ON p.id_pengguna_penerima = pg.id_pengguna
    WHERE DATE(p.tanggal_masuk) BETWEEN '$start_date' AND '$end_date'
    $where_pengguna
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
        <h2>BERKAT LAUNDRY</h2>
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

    <?php
    $total_pesanan = 0;
    $total_item = 0;
    $pesanan_tepat_waktu = 0;
    $temp_data = array();

    while($data = mysqli_fetch_array($query_kinerja)) {
        $is_selesai = !empty($data['tanggal_selesai']);
        $waktu_selesai_jam = $is_selesai ? round((strtotime($data['tanggal_selesai']) - strtotime($data['tanggal_masuk'])) / 3600) : null;
        $estimasi_jam = isset($data['tanggal_estimasi_selesai']) ? round((strtotime($data['tanggal_estimasi_selesai']) - strtotime($data['tanggal_masuk'])) / 3600) : 48;
        if ($is_selesai) {
            if ($waktu_selesai_jam <= $estimasi_jam) {
                $pesanan_tepat_waktu++;
            }
        } else {
            // Pesanan belum selesai, hitung durasi berjalan
            $durasi_berjalan = round((time() - strtotime($data['tanggal_masuk'])) / 3600);
            if ($durasi_berjalan <= $estimasi_jam) {
                $pesanan_tepat_waktu++;
            }
        }
        $total_pesanan++;
        $total_item += $data['total_item'];
        $temp_data[] = $data;
    }
    $ketepatan = $total_pesanan > 0 ? round(($pesanan_tepat_waktu / $total_pesanan) * 100) : 0;
    ?>
    <table class="summary" style="margin-bottom:18px; border:1px solid #bbb; background:#fafcff; border-radius:7px; box-shadow:0 1px 2px #eee; width:60%; margin-left:auto; margin-right:auto;">
        <tr>
            <td style="padding:8px 18px; border-right:1px solid #eee;"><strong>Total Pesanan</strong><br><span style="font-size:18px; font-weight:bold; color:#1a7f37;"> <?= number_format($total_pesanan) ?></span></td>
            <td style="padding:8px 18px; border-right:1px solid #eee;"><strong>Total Item</strong><br><span style="font-size:18px; font-weight:bold; color:#0d6efd;"> <?= number_format($total_item) ?></span></td>
            <td style="padding:8px 18px;"><strong>Ketepatan Waktu</strong><br><span style="font-size:18px; font-weight:bold; color:#f59e00;"> <?= $ketepatan ?>%</span></td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>No. Invoice</th>
                <th>Tanggal Masuk</th>
                <th>Karyawan</th>
                <th>Layanan</th>
                <th>Jumlah Item</th>
                <th>Tanggal Selesai</th>
                <th>Ketepatan</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $no = 1;
            // Fungsi format hari+jam
function format_hari_jam($total_jam) {
    $hari = floor($total_jam / 24);
    $jam = $total_jam % 24;
    return $hari . ' hari ' . $jam . ' jam';
}
foreach($temp_data as $data) {
                $is_selesai = !empty($data['tanggal_selesai']);
$waktu_selesai = $is_selesai ? strtotime($data['tanggal_selesai']) - strtotime($data['tanggal_masuk']) : null;
$waktu_selesai_jam = $is_selesai ? round($waktu_selesai / (60 * 60)) : null;
// Estimasi jam: selisih jam antara tanggal masuk dan estimasi selesai (jika ada)
$estimasi_jam = isset($data['tanggal_estimasi_selesai']) ? round((strtotime($data['tanggal_estimasi_selesai']) - strtotime($data['tanggal_masuk'])) / 3600) : 48;
if ($is_selesai) {
    if ($waktu_selesai_jam > $estimasi_jam) {
        $selisih_jam = $waktu_selesai_jam - $estimasi_jam;
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
                $badge = $tepat_waktu === 'Tepat Waktu' ? '<span style="background:#4caf50;color:#fff;padding:2px 8px;border-radius:7px;font-size:12px;">Tepat</span>' : ($tepat_waktu === 'Terlambat' ? '<span style="background:#e53935;color:#fff;padding:2px 8px;border-radius:7px;font-size:12px;">Terlambat</span>' : '<span style="background:#2196f3;color:#fff;padding:2px 8px;border-radius:7px;font-size:12px;">Proses</span>');
                echo "<tr>";
                echo "<td>" . $no++ . "</td>";
                echo "<td>" . htmlspecialchars($data['nomor_invoice']) . "</td>";
                echo "<td>" . date('d/m/Y H:i', strtotime($data['tanggal_masuk'])) . "</td>";
                echo "<td>" . htmlspecialchars($data['nama_karyawan']) . "</td>";
                echo "<td>" . htmlspecialchars($data['nama_layanan']) . "</td>";
                echo "<td>" . htmlspecialchars($data['total_item']) . "</td>";
echo "<td>" . ($is_selesai ? date('d/m/Y H:i', strtotime($data['tanggal_selesai'])) : '-') . "</td>";

                // Kolom Ketepatan: tampilkan badge/keterangan sesuai status
                    if (strpos($tepat_waktu, 'Terlambat') === 0) {
                        echo "<td style='color:#e53935;font-weight:bold;'>$tepat_waktu</td>";
                    } elseif ($tepat_waktu === 'Tepat Waktu') {
                        echo "<td><span style=\"background:#4caf50;color:#1a7f37;padding:2px 8px;border-radius:7px;font-size:12px;\">Tepat</span></td>";
                    } else {
                        echo "<td>-</td>";
}
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