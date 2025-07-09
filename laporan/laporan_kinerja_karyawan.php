<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include 'pengaturan/koneksi.php';

// Filter date handling
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$id_karyawan = isset($_GET['id_karyawan']) ? $_GET['id_karyawan'] : '';

// Cek akses user
if ($_SESSION['role'] != "Admin") {
    echo '<script>alert("Anda tidak memiliki akses ke halaman ini!");window.location.href="index.php";</script>';
    exit;
}
?>

<div class="container-fluid mt-4">
    <!-- Breadcrumb dan Judul -->
    <div class="mb-4">
        <h3 class="mb-1">Laporan Kinerja Karyawan</h3>
        <div class="text-muted">Analisis produktivitas dan ketepatan waktu karyawan laundry</div>
    </div>
    <!-- Filter Section -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0"><i class="ti ti-filter-check"></i> Filter Laporan</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="index.php" class="row g-3 align-items-end" id="filterForm">
                <input type="hidden" name="page" value="laporan_kinerja_karyawan_read">
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
                    <label for="id_karyawan" class="form-label">Karyawan</label>
                    <select id="id_karyawan" name="id_karyawan" class="form-select">
                        <option value="">Semua Karyawan</option>
                        <?php
                        $query = "SELECT id_pengguna, nama_lengkap FROM pengguna WHERE role IN ('Admin', 'Karyawan') ORDER BY nama_lengkap";
                        $result = mysqli_query($konek, $query);
                        while ($row = mysqli_fetch_assoc($result)) {
                            $selected = ($id_karyawan == $row['id_pengguna']) ? 'selected' : '';
                            echo "<option value='" . htmlspecialchars($row['id_pengguna']) . "' $selected>" . 
                                 htmlspecialchars($row['nama_lengkap']) . "</option>";
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
    $('#start_date,#end_date,#id_karyawan').on('change', function(){
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
    <div class="row mb-4">
        <?php
        // Get performance statistics
        $where = "WHERE p.tanggal_masuk BETWEEN '$start_date' AND '$end_date'";
        if ($id_karyawan) {
            $where .= " AND p.id_pengguna_penerima = '" . mysqli_real_escape_string($konek, $id_karyawan) . "'";
        }
        
        $query = "SELECT 
                    COUNT(p.id_pesanan) as total_pesanan,
                    AVG(TIMESTAMPDIFF(HOUR, p.tanggal_masuk, 
                        CASE 
                            WHEN p.tanggal_selesai_aktual IS NOT NULL THEN p.tanggal_selesai_aktual 
                            ELSE NOW() 
                        END)) / 24 as rata_waktu_pengerjaan,
                    AVG(TIMESTAMPDIFF(HOUR, p.tanggal_masuk, p.tanggal_estimasi_selesai)) / 24 as rata_estimasi,
                    COUNT(CASE WHEN p.tanggal_selesai_aktual <= p.tanggal_estimasi_selesai THEN 1 END) * 100.0 / NULLIF(COUNT(*), 0) as ketepatan_waktu
                 FROM pesanan p
                 $where";
        $result = mysqli_query($konek, $query);
        $stats = mysqli_fetch_assoc($result);
        $total_pesanan = $stats['total_pesanan'] ?? 0;
        $rata_waktu = isset($stats['rata_waktu_pengerjaan']) ? round($stats['rata_waktu_pengerjaan'],1) : 0;
        $rata_estimasi = isset($stats['rata_estimasi']) ? round($stats['rata_estimasi'],1) : 0;
        $ketepatan = isset($stats['ketepatan_waktu']) ? round($stats['ketepatan_waktu'],1) : 0;
        $is_overdue = $rata_waktu > $rata_estimasi && $rata_estimasi > 0;
        ?>
        <div class="d-flex flex-wrap justify-content-center gap-3">
            <div class="card shadow-sm border-0" style="min-width:220px;">
                <div class="card-body text-center">
                    <div class="mb-1">
                        <span class="badge bg-primary"><i class="ti ti-clipboard-check"></i> Total Pesanan</span>
                    </div>
                    <div class="fs-3 fw-bold text-primary"><?= number_format($total_pesanan) ?></div>
                </div>
            </div>
            <div class="card shadow-sm border-0" style="min-width:220px;">
                <div class="card-body text-center">
                    <div class="mb-1">
                        <span class="badge <?= $is_overdue ? 'bg-danger' : 'bg-info' ?>"><i class="ti ti-clock"></i> Rata-rata Waktu</span>
                    </div>
                    <div class="fs-4 fw-bold <?= $is_overdue ? 'text-danger' : 'text-info' ?>"><?= $rata_waktu ?> hari
                        <?php if($is_overdue): ?>
                        <small class="d-block text-danger">(melebihi estimasi <?= $rata_estimasi ?> hari)</small>
                        <?php elseif($rata_estimasi > 0): ?>
                        <small class="d-block text-muted">(estimasi <?= $rata_estimasi ?> hari)</small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="card shadow-sm border-0" style="min-width:220px;">
                <div class="card-body text-center">
                    <div class="mb-1">
                        <span class="badge bg-success"><i class="ti ti-check"></i> Ketepatan Waktu</span>
                    </div>
                    <div class="fs-4 fw-bold text-success"><?= $ketepatan ?>%</div>
                </div>
            </div>
        </div>
        <style>
        @media (max-width: 767px) {
            .d-flex.flex-wrap.gap-3 > .card { min-width: 120px; }
        }
        </style>
        <!--
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title mb-1">Total Pesanan Ditangani</h5>
                    <div class="fs-3 fw-bold"><i class="ti ti-clipboard-check"></i> <?= number_format($stats['total_pesanan']) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title mb-1">Rata-rata Waktu Pengerjaan</h5>
                    <div class="fs-5 mb-1">
                        <?= $rata_waktu ?> hari
                        <?php if($is_overdue): ?>
                        <small class="text-danger">(melebihi estimasi <?= $rata_estimasi ?> hari)</small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title mb-1">Ketepatan Waktu</h5>
                    <div class="fs-5"><span class="badge bg-success"><i class="ti ti-check"></i> <?= number_format($stats['ketepatan_waktu'], 1) ?>%</span></div>
                </div>
            </div>
        </div>
        -->
    </div>

    <!-- Tabel Data Laporan -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0"><i class="ti ti-table"></i> Data Kinerja Karyawan</h5>
            <div>
                <a href="laporan/print_kinerja_karyawan.php?start_date=<?=urlencode($start_date)?>&end_date=<?=urlencode($end_date)?>&id_karyawan=<?=urlencode($id_karyawan)?>" class="btn btn-primary btn-sm" target="_blank"><i class="ti ti-printer"></i> Print</a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="laporanKinerjaTable" class="table table-bordered table-striped" style="width:100%;">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Karyawan</th>
                            <th>Total Pesanan</th>
                            <th>Rata-rata Waktu (Hari)</th>
                            <th>Ketepatan Waktu (%)</th>
                            <th>Detail</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = "SELECT 
                                    pg.id_pengguna as id_karyawan,
                                    pg.nama_lengkap as nama_karyawan,
                                    COUNT(p.id_pesanan) as total_pesanan,
                                    AVG(TIMESTAMPDIFF(HOUR, p.tanggal_masuk, 
                                        CASE 
                                            WHEN p.tanggal_selesai_aktual IS NOT NULL THEN p.tanggal_selesai_aktual 
                                            ELSE NOW() 
                                        END)) / 24 as rata_waktu,
                                    AVG(TIMESTAMPDIFF(HOUR, p.tanggal_masuk, p.tanggal_estimasi_selesai)) / 24 as rata_estimasi,
                                    COUNT(CASE WHEN p.tanggal_selesai_aktual <= p.tanggal_estimasi_selesai THEN 1 END) * 100.0 / NULLIF(COUNT(*), 0) as ketepatan_waktu
                                 FROM pengguna pg
                                 LEFT JOIN pesanan p ON pg.id_pengguna = p.id_pengguna_penerima 
                                    AND p.tanggal_masuk BETWEEN '$start_date' AND '$end_date'
                                 WHERE pg.role IN ('Admin', 'Karyawan')
                                 " . ($id_karyawan ? "AND pg.id_pengguna = '" . mysqli_real_escape_string($konek, $id_karyawan) . "'" : "") . "
                                 GROUP BY pg.id_pengguna, pg.nama_lengkap
                                 ORDER BY total_pesanan DESC";
                        
                        $result = mysqli_query($konek, $query);
                        $no = 1;
                        
                        while ($row = mysqli_fetch_assoc($result)) {
                            $is_row_overdue = $row['rata_waktu'] > $row['rata_estimasi'] && $row['rata_estimasi'] > 0;
                            $row_status_class = $is_row_overdue ? 'table-danger' : '';
                            $badge_class = $is_row_overdue ? 'bg-danger' : 'bg-info';
                            $tooltip = $is_row_overdue ? 'Melebihi estimasi ' . number_format($row['rata_estimasi'],1) . ' hari' : 'Sesuai estimasi (' . number_format($row['rata_estimasi'],1) . ' hari)';
                            echo "<tr class='$row_status_class'>";
                            echo "<td>" . $no++ . "</td>";
                            echo "<td>" . htmlspecialchars($row['nama_karyawan']) . "</td>";
                            echo "<td>" . number_format($row['total_pesanan']) . "</td>";
                            echo "<td><span class='badge $badge_class' data-bs-toggle='tooltip' title='$tooltip'>" . number_format($row['rata_waktu'], 1) . 
                                 "</span></td>";
                            echo "<td>" . number_format($row['ketepatan_waktu'], 1) . "%</td>";
                            echo "<td>
                                    <button type='button' class='btn btn-info btn-sm' onclick='showDetail(\"" . $row['id_karyawan'] . "\")'>
                                        <i class='ti ti-user-search'></i>
                                    </button>
                                  </td>";
                            echo "</tr>";
                        }
                        
                        if (mysqli_num_rows($result) == 0) {
                            echo "<tr><td colspan='6' class='text-center'>Tidak ada data</td></tr>";
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
                <h5 class="modal-title">Detail Kinerja Karyawan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detailContent">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>
</div>

<!-- DataTables & Tooltip Init -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
$(function(){
    $('#laporanKinerjaTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/id.json'
        },
        order: [[2,"desc"]]
    });
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.forEach(function (tooltipTriggerEl) {
        new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
function showDetail(id_karyawan) {
    // Load detail kinerja karyawan via AJAX
    $.ajax({
        url: 'laporan/detail_kinerja_karyawan.php',
        type: 'POST',
        data: {
            id_karyawan: id_karyawan,
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