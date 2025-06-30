<?php
include "../pengaturan/koneksi.php";

// Check if parameters are set in POST or GET
$id_layanan = isset($_POST['id_layanan']) ? $_POST['id_layanan'] : (isset($_GET['id_layanan']) ? $_GET['id_layanan'] : '');
$start_date = isset($_POST['start_date']) ? $_POST['start_date'] : (isset($_GET['start_date']) ? $_GET['start_date'] : '');
$end_date = isset($_POST['end_date']) ? $_POST['end_date'] : (isset($_GET['end_date']) ? $_GET['end_date'] : '');

// Validate required parameters
if (empty($id_layanan) || empty($start_date) || empty($end_date)) {
    die("Error: Missing required parameters");
}

// Query untuk data layanan
$query_layanan = mysqli_query($konek, "
    SELECT nama_layanan
    FROM layanan 
    WHERE id_layanan = '" . mysqli_real_escape_string($konek, $id_layanan) . "'
");

if (!$query_layanan) {
    die("Error in layanan query: " . mysqli_error($konek));
}

$layanan = mysqli_fetch_array($query_layanan);
if (!$layanan) {
    die("Error: Layanan not found");
}

// Query untuk statistik waktu
$query_stats = mysqli_query($konek, "
    SELECT 
        COUNT(DISTINCT p.id_pesanan) as total_pesanan,
        AVG(TIMESTAMPDIFF(HOUR, p.tanggal_masuk, 
            CASE 
                WHEN p.tanggal_selesai_aktual IS NOT NULL THEN p.tanggal_selesai_aktual 
                ELSE NOW() 
            END)) / 24 as rata_waktu_pengerjaan,
        AVG(TIMESTAMPDIFF(HOUR, p.tanggal_masuk, p.tanggal_estimasi_selesai)) / 24 as rata_estimasi,
        COUNT(DISTINCT CASE WHEN p.tanggal_selesai_aktual <= p.tanggal_estimasi_selesai 
              THEN p.id_pesanan END) * 100.0 / 
        NULLIF(COUNT(DISTINCT p.id_pesanan), 0) as ketepatan_waktu,
        AVG(CASE 
            WHEN p.tanggal_selesai_aktual > p.tanggal_estimasi_selesai 
            THEN TIMESTAMPDIFF(HOUR, p.tanggal_estimasi_selesai, p.tanggal_selesai_aktual) / 24.0 
            ELSE 0 
        END) as rata_keterlambatan
    FROM pesanan p
    JOIN detail_pesanan dp ON p.id_pesanan = dp.id_pesanan
    WHERE dp.id_layanan = '" . mysqli_real_escape_string($konek, $id_layanan) . "'
    AND p.tanggal_masuk BETWEEN '" . mysqli_real_escape_string($konek, $start_date) . "' 
    AND '" . mysqli_real_escape_string($konek, $end_date) . "'
");

if (!$query_stats) {
    die("Error in stats query: " . mysqli_error($konek));
}

$stats = mysqli_fetch_array($query_stats);
?>

<h6>Detail Analisis Waktu - <?= htmlspecialchars($layanan['nama_layanan']) ?></h6>
<p>
    <strong>Periode:</strong> <?= date('d/m/Y', strtotime($start_date)) ?> - <?= date('d/m/Y', strtotime($end_date)) ?><br>

    <strong>Total Pesanan:</strong> <?= number_format($stats['total_pesanan']) ?><br>
    <strong>Rata-rata Waktu Pengerjaan:</strong> <?= number_format($stats['rata_waktu_pengerjaan'], 1) ?> hari<br>
    <strong>Ketepatan Waktu:</strong> <?= number_format($stats['ketepatan_waktu'], 1) ?>%
</p>

<div class="table-responsive">
    <table class="table table-bordered table-hover">
        <thead>
            <tr>
                <th>No</th>
                <th>No. Invoice</th>
                <th>Pelanggan</th>
                <th>Tanggal Masuk</th>
                <th>Estimasi Selesai</th>
                <th>Selesai Aktual</th>
                <th>Durasi (Hari)</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $query = mysqli_query($konek, "
                SELECT 
                    p.id_pesanan,
                    p.nomor_invoice,
                    p.tanggal_masuk,
                    p.tanggal_estimasi_selesai,
                    p.tanggal_selesai_aktual,
                    pl.nama_pelanggan,
                    TIMESTAMPDIFF(HOUR, p.tanggal_masuk, p.tanggal_selesai_aktual) / 24 as durasi_aktual,
                    TIMESTAMPDIFF(HOUR, p.tanggal_estimasi_selesai, p.tanggal_selesai_aktual) / 24 as keterlambatan_hari
                FROM pesanan p
                JOIN detail_pesanan dp ON p.id_pesanan = dp.id_pesanan
                JOIN pelanggan pl ON p.id_pelanggan = pl.id_pelanggan
                WHERE dp.id_layanan = '" . mysqli_real_escape_string($konek, $id_layanan) . "'
                AND p.tanggal_masuk BETWEEN '" . mysqli_real_escape_string($konek, $start_date) . "' 
                AND '" . mysqli_real_escape_string($konek, $end_date) . "'
                ORDER BY p.tanggal_masuk DESC
            ");

            $no = 1;
            while($data = mysqli_fetch_array($query)) {
                $status_waktu = '';
                $status_class = '';
                $durasi_text = '-';

                if ($data['tanggal_selesai_aktual']) {
                    $durasi_text = round($data['durasi_aktual'], 1);
                    if (strtotime($data['tanggal_selesai_aktual']) <= strtotime($data['tanggal_estimasi_selesai'])) {
                        $status_waktu = 'Tepat Waktu';
                        $status_class = 'bg-success';
                    } else {
                        $keterlambatan = round($data['keterlambatan_hari'], 1);
                        $status_waktu = 'Terlambat (' . $keterlambatan . ' hari)';
                        $status_class = 'bg-danger';
                    }
                } else {
                    if (time() > strtotime($data['tanggal_estimasi_selesai'])) {
                        $status_waktu = 'Terlambat';
                        $status_class = 'bg-danger';
                    } else {
                        $status_waktu = 'Dalam Proses';
                        $status_class = 'bg-primary';
                    }
                }

                echo "<tr>";
                echo "<td>" . $no++ . "</td>";
                echo "<td>" . htmlspecialchars($data['nomor_invoice']) . "</td>";
                echo "<td>" . htmlspecialchars($data['nama_pelanggan']) . "</td>";
                echo "<td>" . date('d/m/Y H:i', strtotime($data['tanggal_masuk'])) . "</td>";
                echo "<td>" . date('d/m/Y H:i', strtotime($data['tanggal_estimasi_selesai'])) . "</td>";
                echo "<td>" . ($data['tanggal_selesai_aktual'] ? date('d/m/Y H:i', strtotime($data['tanggal_selesai_aktual'])) : '-') . "</td>";
                echo "<td>" . $durasi_text . "</td>";
                echo "<td><span class='badge " . $status_class . "'>" . $status_waktu . "</span></td>";
                echo "</tr>";
            }
            
            if (mysqli_num_rows($query) == 0) {
                echo "<tr><td colspan='8' class='text-center'>Tidak ada data</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div> 