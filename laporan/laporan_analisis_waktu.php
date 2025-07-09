<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
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
            <form method="GET" action="" class="row g-3 align-items-end" id="filterForm">
                <input type="hidden" name="page" value="laporan_analisis_waktu_read">
                <div class="col-md-3">
                    <label for="periode" class="form-label">Periode</label>
                    <select id="periode" name="periode" class="form-select">
                        <option value="hari" <?= (isset($_GET['periode']) && $_GET['periode']=='hari')?'selected':''; ?>>Hari Ini</option>
                        <option value="kemarin" <?= (isset($_GET['periode']) && $_GET['periode']=='kemarin')?'selected':''; ?>>Kemarin</option>
                        <option value="minggu" <?= (isset($_GET['periode']) && $_GET['periode']=='minggu')?'selected':''; ?>>Minggu Ini</option>
                        <option value="bulan" <?= (!isset($_GET['periode']) || $_GET['periode']=='bulan')?'selected':''; ?>>Bulan Ini</option>
                        <option value="custom" <?= (isset($_GET['periode']) && $_GET['periode']=='custom')?'selected':''; ?>>Custom</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="start_date" class="form-label">Tanggal Mulai</label>
                    <input type="date" id="start_date" name="start_date" class="form-control" value="<?= htmlspecialchars($start_date) ?>" <?= (isset($_GET['periode']) && $_GET['periode']!='custom')?'readonly':''; ?> >
                </div>
                <div class="col-md-3">
                    <label for="end_date" class="form-label">Tanggal Selesai</label>
                    <input type="date" id="end_date" name="end_date" class="form-control" value="<?= htmlspecialchars($end_date) ?>" <?= (isset($_GET['periode']) && $_GET['periode']!='custom')?'readonly':''; ?> >
                </div>
                <div class="col-md-2">
                    <label for="jenis_layanan" class="form-label">Jenis Layanan</label>
                    <select id="jenis_layanan" name="jenis_layanan" class="form-select">
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
<script>
$(function(){
    function setTanggalByPeriode(val) {
        var now = new Date();
        var yyyy = now.getFullYear();
        var mm = (now.getMonth()+1).toString().padStart(2,'0');
        var dd = now.getDate().toString().padStart(2,'0');
        if(val==='hari') {
            $('#start_date').val(yyyy+'-'+mm+'-'+dd);
            $('#end_date').val(yyyy+'-'+mm+'-'+dd);
        } else if(val==='kemarin') {
            var kemarin = new Date(now.getTime() - 86400000);
            var ky = kemarin.getFullYear();
            var km = (kemarin.getMonth()+1).toString().padStart(2,'0');
            var kd = kemarin.getDate().toString().padStart(2,'0');
            $('#start_date').val(ky+'-'+km+'-'+kd);
            $('#end_date').val(ky+'-'+km+'-'+kd);
        } else if(val==='minggu') {
            var first = new Date(now.setDate(now.getDate() - now.getDay() + 1));
            var last = new Date(now.setDate(first.getDate() + 6));
            var fy = first.getFullYear();
            var fm = (first.getMonth()+1).toString().padStart(2,'0');
            var fd = first.getDate().toString().padStart(2,'0');
            var ly = last.getFullYear();
            var lm = (last.getMonth()+1).toString().padStart(2,'0');
            var ld = last.getDate().toString().padStart(2,'0');
            $('#start_date').val(fy+'-'+fm+'-'+fd);
            $('#end_date').val(ly+'-'+lm+'-'+ld);
        } else if(val==='bulan') {
            $('#start_date').val(yyyy+'-'+mm+'-01');
            var lastDay = new Date(yyyy, mm, 0).getDate();
            $('#end_date').val(yyyy+'-'+mm+'-'+lastDay);
        }
        if(val!=='custom') {
            $('#start_date,#end_date').prop('readonly',true);
        } else {
            $('#start_date,#end_date').prop('readonly',false);
        }
    }
    $('#periode').on('change', function(){
        setTanggalByPeriode(this.value);
        $('#filterForm').submit();
    });
    $('#start_date,#end_date,#jenis_layanan').on('change', function(){
        $('#periode').val('custom');
        $('#start_date,#end_date').prop('readonly',false);
        $('#filterForm').submit();
    });
    // Inisialisasi awal jika reload
    setTanggalByPeriode($('#periode').val());
});
</script>
        </div>
    </div>

    <!-- Statistik Ringkas -->
    <div class="row mb-3">
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
        $rata_waktu = number_format($stats['rata_waktu_pengerjaan'], 1);
        $rata_estimasi = number_format($stats['rata_estimasi'], 1);
        $rata_keterlambatan = number_format($stats['rata_keterlambatan'], 1);
        $ketepatan_waktu = is_null($stats['ketepatan_waktu']) ? '0.0' : number_format($stats['ketepatan_waktu'], 1);
        ?>
        <div class="col-12">
        <div class="card shadow-sm border border-1 border-primary-subtle bg-light mb-2" style="border-radius:12px;">
            <div class="card-body py-3 px-3">
                <div class="row g-2 text-center">
                    <div class="col-md-3 col-6 mb-2 mb-md-0">
                        <div class="fw-semibold text-secondary mb-1"><i class="ti ti-list-details"></i> Total Pesanan</div>
                        <div class="fs-3 fw-bold text-primary" title="Total pesanan dalam periode ini"><?= $stats['total_pesanan'] ?></div>
                    </div>
                    <div class="col-md-3 col-6 mb-2 mb-md-0">
                        <div class="fw-semibold text-secondary mb-1"><i class="ti ti-clock"></i> Rata-rata Waktu</div>
                        <span class="badge rounded-pill <?= $is_overdue ? 'bg-danger' : 'bg-info' ?> text-white fs-6 px-3 py-2" title="<?= $is_overdue ? 'Melebihi estimasi '.$rata_estimasi.' hari' : 'Sesuai estimasi '.$rata_estimasi.' hari' ?>">
                            <?= $rata_waktu ?> Hari
                        </span>
                        <?php if ($is_overdue): ?>
                            <small class="text-danger d-block mt-1">(Melebihi estimasi <?= $rata_estimasi ?> hari)</small>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="fw-semibold text-secondary mb-1"><i class="ti ti-check"></i> Ketepatan Waktu</div>
                        <span class="badge rounded-pill bg-success fs-6 px-3 py-2" title="Persentase pesanan selesai tepat waktu">
                            <?= $ketepatan_waktu ?>%
                        </span>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="fw-semibold text-secondary mb-1"><i class="ti ti-alert-circle"></i> Rata-rata Keterlambatan</div>
                        <span class="badge rounded-pill <?= ($rata_keterlambatan>0)?'bg-warning text-dark':'bg-success' ?> fs-6 px-3 py-2" title="Rata-rata hari keterlambatan pesanan">
                            <?= $rata_keterlambatan ?> Hari
                        </span>
                    </div>
                </div>
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