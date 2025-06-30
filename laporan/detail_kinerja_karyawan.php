<?php
include "../pengaturan/koneksi.php";

// Check if parameters are set in POST or GET
$id_pengguna = isset($_POST['id_karyawan']) ? $_POST['id_karyawan'] : (isset($_GET['id_pengguna']) ? $_GET['id_pengguna'] : '');
$tgl_awal = isset($_POST['start_date']) ? $_POST['start_date'] : (isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : '');
$tgl_akhir = isset($_POST['end_date']) ? $_POST['end_date'] : (isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : '');

// Validate required parameters
if (empty($id_pengguna) || empty($tgl_awal) || empty($tgl_akhir)) {
    die("Error: Missing required parameters");
}

// Query untuk data karyawan
$query_karyawan = mysqli_query($konek, "
    SELECT nama_lengkap, role, nomor_telepon_internal
    FROM pengguna 
    WHERE id_pengguna = '" . mysqli_real_escape_string($konek, $id_pengguna) . "'
");

if (!$query_karyawan) {
    die("Error in karyawan query: " . mysqli_error($konek));
}

$karyawan = mysqli_fetch_array($query_karyawan);
if (!$karyawan) {
    die("Error: Karyawan not found");
}

// Query untuk statistik kinerja
$query_stats = mysqli_query($konek, "
    SELECT 
        COUNT(DISTINCT p.id_pesanan) as total_pesanan,
        SUM(dp.kuantitas) as total_item,
        AVG(TIMESTAMPDIFF(HOUR, p.tanggal_masuk, 
            CASE 
                WHEN p.tanggal_selesai_aktual IS NOT NULL THEN p.tanggal_selesai_aktual 
                ELSE NOW() 
            END)) / 24 as rata_waktu_pengerjaan,
        AVG(TIMESTAMPDIFF(HOUR, p.tanggal_masuk, p.tanggal_estimasi_selesai)) / 24 as rata_estimasi,
        COUNT(CASE WHEN p.tanggal_selesai_aktual IS NOT NULL 
                   AND p.tanggal_selesai_aktual <= p.tanggal_estimasi_selesai 
              THEN 1 END) * 100.0 / COUNT(*) as ketepatan_waktu
    FROM pesanan p
    JOIN detail_pesanan dp ON p.id_pesanan = dp.id_pesanan
    WHERE p.id_pengguna_penerima = '" . mysqli_real_escape_string($konek, $id_pengguna) . "'
    AND DATE(p.tanggal_masuk) BETWEEN '" . mysqli_real_escape_string($konek, $tgl_awal) . "' 
    AND '" . mysqli_real_escape_string($konek, $tgl_akhir) . "'
");

if (!$query_stats) {
    die("Error in stats query: " . mysqli_error($konek));
}

$stats = mysqli_fetch_array($query_stats);

// Query untuk detail pesanan
$query_kinerja = mysqli_query($konek, "
    SELECT 
        p.id_pesanan,
        p.nomor_invoice,
        p.tanggal_masuk,
        p.tanggal_selesai_aktual,
        p.tanggal_estimasi_selesai,
        p.status_pesanan_umum,
        pl.nama_pelanggan,
        GROUP_CONCAT(l.nama_layanan SEPARATOR ', ') as layanan,
        SUM(dp.kuantitas) as total_item,
        TIMESTAMPDIFF(HOUR, p.tanggal_masuk, 
            CASE 
                WHEN p.tanggal_selesai_aktual IS NOT NULL THEN p.tanggal_selesai_aktual 
                ELSE NOW() 
            END) / 24 as waktu_pengerjaan,
        TIMESTAMPDIFF(HOUR, p.tanggal_masuk, p.tanggal_estimasi_selesai) / 24 as estimasi_waktu
    FROM pesanan p
    JOIN detail_pesanan dp ON p.id_pesanan = dp.id_pesanan
    JOIN layanan l ON dp.id_layanan = l.id_layanan
    JOIN pelanggan pl ON p.id_pelanggan = pl.id_pelanggan
    WHERE p.id_pengguna_penerima = '" . mysqli_real_escape_string($konek, $id_pengguna) . "'
    AND DATE(p.tanggal_masuk) BETWEEN '" . mysqli_real_escape_string($konek, $tgl_awal) . "' 
    AND '" . mysqli_real_escape_string($konek, $tgl_akhir) . "'
    GROUP BY p.id_pesanan, p.nomor_invoice, p.tanggal_masuk, p.tanggal_selesai_aktual, p.tanggal_estimasi_selesai, p.status_pesanan_umum, pl.nama_pelanggan
    ORDER BY p.tanggal_masuk DESC
");

if (!$query_kinerja) {
    die("Error in kinerja query: " . mysqli_error($konek));
}
?>

<h6>Detail Kinerja Karyawan</h6>
<p>
    <strong>Nama Karyawan:</strong> <?= htmlspecialchars($karyawan['nama_lengkap']) ?><br>
    <strong>Jabatan:</strong> <?= htmlspecialchars($karyawan['role']) ?><br>
    <strong>No. Telepon:</strong> <?= htmlspecialchars($karyawan['nomor_telepon_internal']) ?><br>
    <strong>Periode:</strong> <?= date('d/m/Y', strtotime($tgl_awal)) ?> - <?= date('d/m/Y', strtotime($tgl_akhir)) ?>
</p>

<div class="table-responsive">
    <table class="table table-bordered table-hover">
        <thead>
            <tr>
                <th>No</th>
                <th>No. Invoice</th>
                <th>Tanggal</th>
                <th>Pelanggan</th>
                <th>Layanan</th>
                <th>Jumlah Item</th>
                <th>Status</th>
                <th>Waktu Pengerjaan</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $no = 1;
            while($data = mysqli_fetch_array($query_kinerja)) {
                $status_waktu = '';
                $waktu_pengerjaan = round($data['waktu_pengerjaan'], 1);
                $estimasi_waktu = round($data['estimasi_waktu'], 1);
                
                if ($data['tanggal_selesai_aktual']) {
                    if (strtotime($data['tanggal_selesai_aktual']) <= strtotime($data['tanggal_estimasi_selesai'])) {
                        $status_waktu = '<span class="text-success">Tepat Waktu</span>';
                    } else {
                        $status_waktu = '<span class="text-danger">Terlambat</span>';
                    }
                    $status_waktu .= ' (' . $waktu_pengerjaan . ' hari)';
                } else {
                    if (time() > strtotime($data['tanggal_estimasi_selesai'])) {
                        $status_waktu = '<span class="text-danger">Melebihi Estimasi</span>';
                        $status_waktu .= ' (' . $waktu_pengerjaan . ' hari dari ' . $estimasi_waktu . ' hari)';
                    } else {
                        $status_waktu = '<span class="text-primary">Dalam Proses</span>';
                        $status_waktu .= ' (' . $waktu_pengerjaan . ' hari dari ' . $estimasi_waktu . ' hari)';
                    }
                }

                echo "<tr>";
                echo "<td>" . $no++ . "</td>";
                echo "<td>" . htmlspecialchars($data['nomor_invoice']) . "</td>";
                echo "<td>" . date('d/m/Y H:i', strtotime($data['tanggal_masuk'])) . "</td>";
                echo "<td>" . htmlspecialchars($data['nama_pelanggan']) . "</td>";
                echo "<td>" . htmlspecialchars($data['layanan']) . "</td>";
                echo "<td>" . htmlspecialchars($data['total_item']) . "</td>";
                echo "<td>" . htmlspecialchars($data['status_pesanan_umum']) . "</td>";
                echo "<td>" . $status_waktu . "</td>";
                echo "</tr>";
            }
            ?>
        </tbody>
    </table>
</div>

<div class="row mt-3">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <h6 class="card-title">Total Pesanan</h6>
                <h4><?= number_format($stats['total_pesanan']) ?></h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <h6 class="card-title">Total Item</h6>
                <h4><?= number_format($stats['total_item']) ?></h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card <?= $stats['rata_waktu_pengerjaan'] > $stats['rata_estimasi'] ? 'bg-danger' : 'bg-info' ?> text-white">
            <div class="card-body">
                <h6 class="card-title">Rata-rata Waktu</h6>
                <h4><?= round($stats['rata_waktu_pengerjaan'], 1) ?> hari
                <?php if ($stats['rata_waktu_pengerjaan'] > $stats['rata_estimasi']): ?>
                    <small class="d-block">(melebihi estimasi <?= round($stats['rata_estimasi'], 1) ?> hari)</small>
                <?php endif; ?>
                </h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <h6 class="card-title">Ketepatan Waktu</h6>
                <h4><?= round($stats['ketepatan_waktu']) ?>%</h4>
            </div>
        </div>
    </div> 