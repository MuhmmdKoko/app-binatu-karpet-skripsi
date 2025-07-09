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

<div class="mb-2">
    <div class="row g-2">
        <div class="col-md-3 col-6">
            <div class="card bg-primary text-white mb-2">
                <div class="card-body p-2 text-center">
                    <div class="small">Total Pesanan</div>
                    <div class="fw-bold fs-5"><?= number_format($stats['total_pesanan']) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card bg-success text-white mb-2">
                <div class="card-body p-2 text-center">
                    <div class="small">Total Item</div>
                    <div class="fw-bold fs-5"><?= number_format($stats['total_item']) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card <?= $stats['rata_waktu_pengerjaan'] > $stats['rata_estimasi'] ? 'bg-danger' : 'bg-info' ?> text-white mb-2">
                <div class="card-body p-2 text-center">
                    <div class="small">Rata-rata Waktu</div>
                    <div class="fw-bold fs-5"><?= round($stats['rata_waktu_pengerjaan'], 1) ?> hari</div>
                    <?php if ($stats['rata_waktu_pengerjaan'] > $stats['rata_estimasi']): ?>
                        <small class="d-block">(melebihi estimasi <?= round($stats['rata_estimasi'], 1) ?> hari)</small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card bg-warning text-white mb-2">
                <div class="card-body p-2 text-center">
                    <div class="small">Ketepatan Waktu</div>
                    <div class="fw-bold fs-5"><?= round($stats['ketepatan_waktu']) ?>%</div>
                </div>
            </div>
        </div>
    </div>
    <div class="mb-2">
        <strong>Nama Karyawan:</strong> <?= htmlspecialchars($karyawan['nama_lengkap']) ?> |
        <strong>Jabatan:</strong> <?= htmlspecialchars($karyawan['role']) ?> |
        <strong>No. Telepon:</strong> <?= htmlspecialchars($karyawan['nomor_telepon_internal']) ?> |
        <strong>Periode:</strong> <?= date('d/m/Y', strtotime($tgl_awal)) ?> - <?= date('d/m/Y', strtotime($tgl_akhir)) ?>
    </div>
</div>

<!-- Spinner Loading (hidden by default) -->
<div id="spinnerDetailKinerja" style="display:none;text-align:center;padding:20px;">
    <div class="spinner-border text-primary" role="status">
      <span class="visually-hidden">Loading...</span>
    </div>
</div>

<div class="table-responsive">
    <table id="detailKinerjaTable" class="table table-bordered table-hover table-sm">
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
                $waktu_pengerjaan = round($data['waktu_pengerjaan'], 1);
                $estimasi_waktu = round($data['estimasi_waktu'], 1);
                $badge = '';
                if ($data['tanggal_selesai_aktual']) {
                    if (strtotime($data['tanggal_selesai_aktual']) <= strtotime($data['tanggal_estimasi_selesai'])) {
                        $badge = '<span class="badge bg-success">Tepat ('.$waktu_pengerjaan.' hari)</span>';
                    } else {
                        $badge = '<span class="badge bg-danger">Terlambat ('.$waktu_pengerjaan.' hari)</span>';
                    }
                } else {
                    if (time() > strtotime($data['tanggal_estimasi_selesai'])) {
                        $badge = '<span class="badge bg-danger">Melebihi Estimasi ('.$waktu_pengerjaan.' hari dari '.$estimasi_waktu.' hari)</span>';
                    } else {
                        $badge = '<span class="badge bg-info">Dalam Proses ('.$waktu_pengerjaan.' hari dari '.$estimasi_waktu.' hari)</span>';
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
                echo "<td>" . $badge . "</td>";
                echo "</tr>";
            }
            ?>
        </tbody>
    </table>
</div>

<script>
    $(document).ready(function() {
        $('#detailKinerjaTable').DataTable({
            "paging": true,
            "lengthChange": true,
            "searching": true,
            "ordering": true,
            "info": true,
            "autoWidth": false,
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.20/i18n/Indonesian.json"
            }
        });
    });
</script>