<?php
session_start();
include "pengaturan/koneksi.php";

// Filter date handling
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$jenis_layanan = isset($_GET['jenis_layanan']) ? $_GET['jenis_layanan'] : '';

// Cek akses user
if ($_SESSION['role'] != "Admin") {
    echo '<script>alert("Anda tidak memiliki akses ke halaman ini!");window.location.href="../index.php";</script>';
    exit;
}
// include "../template/header.php";
?>
<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

<div class="container-fluid mt-4">
    <!-- Breadcrumb dan Judul -->
    <div class="mb-4">
        <h3 class="mb-1">Laporan Analisis Waktu</h3>
        <div class="text-muted">Analisis ketepatan waktu penyelesaian pesanan laundry</div>
    </div>

    <!-- Filter Section -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0"><i class="ti ti-filter-check"></i> Filter Laporan</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="" class="row g-3 align-items-end">
                <input type="hidden" name="page" value="laporan_analisis_waktu_read">
                <div class="col-md-4">
                    <label for="start_date" class="form-label">Tanggal Mulai</label>
                    <input type="date" id="start_date" name="start_date" class="form-control" value="<?= htmlspecialchars($start_date) ?>">
                </div>
                <div class="col-md-4">
                    <label for="end_date" class="form-label">Tanggal Selesai</label>
                    <input type="date" id="end_date" name="end_date" class="form-control" value="<?= htmlspecialchars($end_date) ?>">
                </div>
                <div class="col-md-3">
                    <label for="jenis_layanan" class="form-label">Jenis Layanan</label>
                    <select id="jenis_layanan" name="jenis_layanan" class="form-control">
                        <option value="">Semua Layanan</option>
                        <?php
                        $query = "SELECT id_layanan, nama_layanan FROM layanan ORDER BY nama_layanan";
                        $result = mysqli_query($konek, $query);
                        while ($row = mysqli_fetch_assoc($result)) {
                            $selected = ($jenis_layanan == $row['id_layanan']) ? 'selected' : '';
                            echo "<option value='" . htmlspecialchars($row['id_layanan']) . "' $selected>" . 
                                 htmlspecialchars($row['nama_layanan']) . "</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100"><i class="ti ti-filter-check"></i> Filter</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Statistik Ringkas -->
    <div class="row mb-4">
        <?php
        // Get time analysis statistics
        $where = "WHERE p.tanggal_masuk BETWEEN '$start_date' AND '$end_date'";
        if ($jenis_layanan) {
            $where .= " AND dp.id_layanan = '" . mysqli_real_escape_string($konek, $jenis_layanan) . "'";
        }
        
        $query = "SELECT 
                    COUNT(DISTINCT p.id_pesanan) as total_pesanan,
                    AVG(TIMESTAMPDIFF(HOUR, p.tanggal_masuk, 
                        CASE 
                            WHEN p.tanggal_selesai_aktual IS NOT NULL THEN p.tanggal_selesai_aktual 
                            ELSE NOW() 
                        END)) / 24 as rata_waktu_pengerjaan,
                    AVG(TIMESTAMPDIFF(HOUR, p.tanggal_masuk, p.tanggal_estimasi_selesai)) / 24 as rata_estimasi,
                    COUNT(DISTINCT CASE WHEN p.tanggal_selesai_aktual <= p.tanggal_estimasi_selesai THEN p.id_pesanan END) * 100.0 / 
                    NULLIF(COUNT(DISTINCT p.id_pesanan), 0) as ketepatan_waktu,
                    AVG(CASE 
                        WHEN p.tanggal_selesai_aktual > p.tanggal_estimasi_selesai THEN
                            TIMESTAMPDIFF(HOUR, p.tanggal_estimasi_selesai, p.tanggal_selesai_aktual) / 24.0
                        WHEN p.tanggal_selesai_aktual IS NULL AND NOW() > p.tanggal_estimasi_selesai THEN
                            TIMESTAMPDIFF(HOUR, p.tanggal_estimasi_selesai, NOW()) / 24.0
                        ELSE NULL
                    END) as rata_keterlambatan
                 FROM pesanan p
                 JOIN detail_pesanan dp ON p.id_pesanan = dp.id_pesanan
                 $where";
        $result = mysqli_query($konek, $query);
        $stats = mysqli_fetch_assoc($result);

        // Calculate if overdue
        $is_overdue = $stats['rata_waktu_pengerjaan'] > $stats['rata_estimasi'];
        $status_class = $is_overdue ? 'bg-danger' : 'bg-info';
        $rata_waktu = number_format($stats['rata_waktu_pengerjaan'], 1);
        $rata_estimasi = number_format($stats['rata_estimasi'], 1);
        $rata_keterlambatan = number_format($stats['rata_keterlambatan'], 1);
        ?>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title mb-1">Total Pesanan</h5>
                    <div class="fs-3 fw-bold"><?= $stats['total_pesanan'] ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title mb-1">Rata-rata Waktu Pengerjaan</h5>
                    <div class="fs-5 mb-1">
                        <span class="badge <?= $status_class ?> text-white"><i class="ti ti-clock"></i> <?= $rata_waktu ?> Hari</span>
                    </div>
                    <?php if ($is_overdue): ?>
                        <small class="text-danger">(melebihi estimasi <?= $rata_estimasi ?> hari)</small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title mb-1">Ketepatan Waktu</h5>
                    <div class="fs-5"><span class="badge bg-success"><i class="ti ti-check"></i> <?= $stats['ketepatan_waktu'] ?>%</span></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title mb-1">Rata-rata Keterlambatan</h5>
                    <div class="fs-5"><span class="badge bg-danger"><i class="ti ti-alert-circle"></i> <?= $rata_keterlambatan ?> Hari</span></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Report Table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title">Laporan Analisis Waktu per Layanan</h5>
            <div class="btn-group">
                <a href="laporan/export_analisis_waktu.php?start_date=<?= $start_date ?>&end_date=<?= $end_date ?>&jenis_layanan=<?= $jenis_layanan ?>" 
                   class="btn btn-success">
                    <i class="ti ti-file-export"></i> Export Excel
                </a>
                <a href="laporan/print_analisis_waktu.php?start_date=<?= $start_date ?>&end_date=<?= $end_date ?>&jenis_layanan=<?= $jenis_layanan ?>" 
                   class="btn btn-primary" target="_blank">
                    <i class="ti ti-printer"></i> Print
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="laporanAnalisisTable" class="table table-bordered table-striped" style="width:100%;">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Jenis Layanan</th>
                            <th>Total Pesanan</th>
                            <th>Rata-rata Waktu (Hari)</th>
                            <th>Estimasi Waktu (Hari)</th>
                            <th>Ketepatan Waktu (%)</th>
                            <th>Rata-rata Keterlambatan (Hari)</th>
                            <th>Detail</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = "SELECT 
                                    l.id_layanan,
                                    l.nama_layanan,
                                    COUNT(DISTINCT p.id_pesanan) as total_pesanan,
                                    AVG(TIMESTAMPDIFF(HOUR, p.tanggal_masuk, 
                                        CASE 
                                            WHEN p.tanggal_selesai_aktual IS NOT NULL THEN p.tanggal_selesai_aktual 
                                            ELSE NOW() 
                                        END)) / 24 as rata_waktu,
                                    AVG(TIMESTAMPDIFF(HOUR, p.tanggal_masuk, p.tanggal_estimasi_selesai)) / 24 as rata_estimasi,
                                    COUNT(DISTINCT CASE WHEN p.tanggal_selesai_aktual <= p.tanggal_estimasi_selesai 
                                          THEN p.id_pesanan END) * 100.0 / 
                                    NULLIF(COUNT(DISTINCT p.id_pesanan), 0) as ketepatan_waktu,
                                    AVG(CASE 
                                        WHEN p.tanggal_selesai_aktual > p.tanggal_estimasi_selesai THEN
                                            TIMESTAMPDIFF(HOUR, p.tanggal_estimasi_selesai, p.tanggal_selesai_aktual) / 24.0
                                        WHEN p.tanggal_selesai_aktual IS NULL AND NOW() > p.tanggal_estimasi_selesai THEN
                                            TIMESTAMPDIFF(HOUR, p.tanggal_estimasi_selesai, NOW()) / 24.0
                                        ELSE NULL
                                    END) as rata_keterlambatan
                                 FROM layanan l
                                 LEFT JOIN detail_pesanan dp ON l.id_layanan = dp.id_layanan
                                 LEFT JOIN pesanan p ON dp.id_pesanan = p.id_pesanan 
                                    AND p.tanggal_masuk BETWEEN '$start_date' AND '$end_date'
                                 " . ($jenis_layanan ? "WHERE l.id_layanan = '" . mysqli_real_escape_string($konek, $jenis_layanan) . "'" : "") . "
                                 GROUP BY l.id_layanan, l.nama_layanan
                                 ORDER BY total_pesanan DESC";
                        
                        $result = mysqli_query($konek, $query);
                        $no = 1;
                        
                        while ($row = mysqli_fetch_assoc($result)) {
                            // Logic for coloring Rata-rata Waktu
                            $rata_waktu_class = $row['rata_waktu'] > $row['rata_estimasi'] ? 'bg-light-warning text-dark' : 'bg-light-info text-dark';
                            
                            // Logic for coloring Rata-rata Keterlambatan
                            $keterlambatan_class = '';
                            $keterlambatan_tooltip = '';
                            if ($row['rata_keterlambatan'] > 1) {
                                $keterlambatan_class = 'bg-light-danger text-danger';
                                $keterlambatan_tooltip = 'Keterlambatan signifikan, perlu perhatian.';
                            } elseif ($row['rata_keterlambatan'] > 0) {
                                $keterlambatan_class = 'bg-light-warning text-dark';
                                $keterlambatan_tooltip = 'Ada sedikit keterlambatan.';
                            } else {
                                $keterlambatan_class = 'bg-light-success text-success';
                                $keterlambatan_tooltip = 'Pengerjaan lebih cepat dari estimasi.';
                            }

                            echo "<tr>";
                            echo "<td>" . $no++ . "</td>";
                            echo "<td data-bs-toggle='tooltip' title='Layanan " . htmlspecialchars($row['nama_layanan']) . "'>" . htmlspecialchars($row['nama_layanan']) . "</td>";
                            echo "<td data-bs-toggle='tooltip' title='Total " . number_format($row['total_pesanan']) . " pesanan dalam periode ini'>" . number_format($row['total_pesanan']) . "</td>";
                            
                            // Rata-rata Waktu
                            echo "<td class='" . $rata_waktu_class . "' data-bs-toggle='tooltip' title='Rata-rata pengerjaan: " . number_format($row['rata_waktu'], 1) . " hari'>" 
                                 . number_format($row['rata_waktu'], 1) . "</td>";
                            
                            // Estimasi Waktu
                            echo "<td data-bs-toggle='tooltip' title='Estimasi standar: " . number_format($row['rata_estimasi'], 1) . " hari'>" 
                                 . number_format($row['rata_estimasi'], 1) . "</td>";
                            
                            // Ketepatan Waktu
                            echo "<td data-bs-toggle='tooltip' title='" . number_format($row['ketepatan_waktu'], 1) . "% pesanan selesai tepat waktu'>" 
                                 . number_format($row['ketepatan_waktu'], 1) . "%</td>";

                            // Rata-rata Keterlambatan
                            echo "<td class='" . $keterlambatan_class . "' data-bs-toggle='tooltip' title='" . $keterlambatan_tooltip . "'>" . 
                                 number_format($row['rata_keterlambatan'], 1) . "</td>";

                            echo "<td>
                                    <button type='button' class='btn btn-info btn-sm' onclick='showDetailWaktu(\"" . $row['id_layanan'] . "\")' data-bs-toggle='tooltip' title='Lihat detail pesanan'>
                                    <i class='ti ti-list-details'></i>
                                    </button>
                                  </td>";
                            echo "</tr>";
                        }
                        
                        if (mysqli_num_rows($result) == 0) {
                            echo "<tr><td colspan='8' class='text-center'>Tidak ada data</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detail -->
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail Analisis Waktu</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detailContent">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>
</div>

<script>
function showDetailWaktu(id_layanan) {
    console.log('showDetailWaktu called', id_layanan);
    // Load detail analisis waktu via AJAX
    $.ajax({
        url: 'laporan/detail_analisis_waktu.php',
        type: 'POST',
        data: {
            id_layanan: id_layanan,
            start_date: '<?= $start_date ?>',
            end_date: '<?= $end_date ?>'
        },
        success: function(response) {
            $('#detailContent').html(response);
            $('#detailModal').modal('show');
        }
    });
}
</script>
<!-- jQuery (required for AJAX and Bootstrap 4, optional for Bootstrap 5 but needed for your code) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Bootstrap JS (choose one version, matching your CSS) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- DataTables & Custom Script -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    // Initialize DataTables with Indonesian language
    $('#laporanAnalisisTable').DataTable({
        "language": {
            "url": "https://cdn.datatables.net/plug-ins/1.13.6/i18n/id.json"
        },
        "pageLength": 10,
        "lengthMenu": [ [10, 25, 50, -1], [10, 25, 50, "Semua"] ]
    });

    // Initialize Bootstrap Tooltips
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
});
</script>