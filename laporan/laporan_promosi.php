<?php
// Debug: aktifkan error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// Ambil koneksi dan query data
include 'pengaturan/koneksi.php';
// include "../template/header.php";

// Logika untuk filter periode preset
date_default_timezone_set('Asia/Jakarta');
$periode = isset($_POST['periode']) ? $_POST['periode'] : 'bulan';
$minimal_penggunaan = isset($_POST['minimal_penggunaan']) ? intval($_POST['minimal_penggunaan']) : 1;
$status_promo = isset($_POST['status_promo']) ? $_POST['status_promo'] : 'semua';
if ($periode === 'hari') {
    $tanggal_mulai = date('Y-m-d');
    $tanggal_akhir = date('Y-m-d');
} elseif ($periode === 'kemarin') {
    $tanggal_mulai = date('Y-m-d', strtotime('-1 day'));
    $tanggal_akhir = date('Y-m-d', strtotime('-1 day'));
} elseif ($periode === 'minggu') {
    $tanggal_mulai = date('Y-m-d', strtotime('monday this week'));
    $tanggal_akhir = date('Y-m-d', strtotime('sunday this week'));
} elseif ($periode === 'bulan') {
    $tanggal_mulai = date('Y-m-01');
    $tanggal_akhir = date('Y-m-t');
} elseif ($periode === 'custom') {
    $tanggal_mulai = isset($_POST['tanggal_mulai']) ? $_POST['tanggal_mulai'] : date('Y-m-01');
    $tanggal_akhir = isset($_POST['tanggal_akhir']) ? $_POST['tanggal_akhir'] : date('Y-m-t');
} else {
    $tanggal_mulai = date('Y-m-01');
    $tanggal_akhir = date('Y-m-t');
}

// Query untuk mengambil data promosi dan agregat penggunaannya
// Tambahkan 1 hari ke tanggal akhir agar filter < tanggal_akhir_next (inklusif seluruh hari)
$tanggal_akhir_next = date('Y-m-d', strtotime($tanggal_akhir . ' +1 day'));

$sql = "SELECT 
            p.id_promosi,
            p.judul, 
            p.kode_promo, 
            p.status_promo,
            p.tipe_promo,
            p.nilai_promo,
            p.tanggal_buat,
            p.tanggal_berakhir,
            p.syarat_min_transaksi,
            COUNT(ps.id_pesanan) AS jumlah_penggunaan,
            SUM(ps.diskon) AS total_diskon,
            AVG(ps.diskon) AS rata_diskon,
            COUNT(DISTINCT ps.id_pelanggan) AS pelanggan_unik
        FROM 
            promosi p
        LEFT JOIN 
            pesanan ps ON p.id_promosi = ps.id_promosi AND ps.tanggal_masuk >= ? AND ps.tanggal_masuk < ?
        ".($status_promo!='semua' ? "WHERE p.status_promo = ?\n" : "").
        "GROUP BY
            p.id_promosi, p.judul, p.kode_promo, p.status_promo, p.tipe_promo, p.nilai_promo, p.tanggal_buat, p.tanggal_berakhir, p.syarat_min_transaksi
        HAVING jumlah_penggunaan >= ?
        ORDER BY
            p.id_promosi DESC";

$stmt = mysqli_prepare($konek, $sql);

// Tambahkan pengecekan error untuk mysqli_prepare
if ($stmt === false) {
    die('Error preparing statement: ' . htmlspecialchars(mysqli_error($konek)));
}

if($status_promo!='semua') {
    mysqli_stmt_bind_param($stmt, "sssi", $tanggal_mulai, $tanggal_akhir_next, $status_promo, $minimal_penggunaan);
} else {
    mysqli_stmt_bind_param($stmt, "ssi", $tanggal_mulai, $tanggal_akhir_next, $minimal_penggunaan);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// --- Statistik Ringkas Promosi ---
$stat_promo_aktif = 0;
$stat_total_penggunaan = 0;
$stat_total_diskon = 0;
$stat_rata_diskon = 0;
$stat_pelanggan_unik = 0;
$tmp_jumlah_promo = 0;
$tmp_pelanggan_unik_set = [];
$tmp_total_diskon = 0;

$tmp_result = [];
while ($row = mysqli_fetch_assoc($result)) {
    $tmp_result[] = $row;
    if (strtolower($row['status_promo']) == 'aktif') $stat_promo_aktif++;
    $stat_total_penggunaan += (int)$row['jumlah_penggunaan'];
    $stat_total_diskon += (int)$row['total_diskon'];
    $tmp_jumlah_promo++;
    $stat_rata_diskon += (float)$row['rata_diskon'];
    $stat_pelanggan_unik += (int)$row['pelanggan_unik'];
}
// Rata-rata diskon per promo
$stat_rata_diskon = $tmp_jumlah_promo > 0 ? $stat_rata_diskon / $tmp_jumlah_promo : 0;
// Reset result pointer for table
$result = $tmp_result;

?>

<div class="container-fluid mt-4">
    
    <!-- Breadcrumb dan Judul -->
    <div class="mb-4">
        <h3 class="mb-1">Laporan Promosi</h3>
        <div class="text-muted">Analisis efektivitas penggunaan kode promo</div>
    </div>

    <!-- Filter Section -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0"><i class="ti ti-filter-check"></i> Filter Laporan</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="" class="row g-3 align-items-end" id="filterForm">
                <div class="col-md-3 col-12">
    <label for="periode" class="form-label">Periode</label>
    <select id="periode" name="periode" class="form-select">
        <option value="hari" <?= ($periode=='hari')?'selected':'' ?>>Hari Ini</option>
        <option value="kemarin" <?= ($periode=='kemarin')?'selected':'' ?>>Kemarin</option>
        <option value="minggu" <?= ($periode=='minggu')?'selected':'' ?>>Minggu Ini</option>
        <option value="bulan" <?= ($periode=='bulan')?'selected':'' ?>>Bulan Ini</option>
        <option value="custom" <?= ($periode=='custom')?'selected':'' ?>>Custom</option>
    </select>
</div>
<div class="col-md-3 col-6">
    <label for="tanggal_mulai" class="form-label">Tanggal Mulai</label>
    <input type="date" id="tanggal_mulai" name="tanggal_mulai" class="form-control" value="<?= htmlspecialchars($tanggal_mulai) ?>" <?= ($periode!='custom')?'readonly':'' ?> >
</div>
<div class="col-md-3 col-6">
    <label for="tanggal_akhir" class="form-label">Tanggal Akhir</label>
    <input type="date" id="tanggal_akhir" name="tanggal_akhir" class="form-control" value="<?= htmlspecialchars($tanggal_akhir) ?>" <?= ($periode!='custom')?'readonly':'' ?> >
</div>
<div class="col-md-3 col-6">
    <label for="minimal_penggunaan" class="form-label">Minimal Penggunaan</label>
    <input type="number" id="minimal_penggunaan" name="minimal_penggunaan" class="form-control" value="<?= htmlspecialchars($minimal_penggunaan) ?>" min="0">
</div>
<div class="col-md-3 col-6">
    <label for="status_promo" class="form-label">Status Promo</label>
    <select id="status_promo" name="status_promo" class="form-select">
        <option value="semua" <?= ($status_promo=='semua')?'selected':'' ?>>Semua</option>
        <option value="Aktif" <?= ($status_promo=='Aktif')?'selected':'' ?>>Aktif</option>
        <option value="Draft" <?= ($status_promo=='Draft')?'selected':'' ?>>Draft</option>
        <option value="Tidak Aktif" <?= ($status_promo=='Tidak Aktif')?'selected':'' ?>>Tidak Aktif</option>
        <option value="Selesai" <?= ($status_promo=='Selesai')?'selected':'' ?>>Selesai</option>
    </select>
</div>
</form>
<script>
$(function() {
    function setTanggalByPeriode(val) {
        var now = new Date();
        var yyyy = now.getFullYear();
        var mm = (now.getMonth()+1).toString().padStart(2,'0');
        var dd = now.getDate().toString().padStart(2,'0');
        if(val==='hari') {
            $('#tanggal_mulai').val(yyyy+'-'+mm+'-'+dd);
            $('#tanggal_akhir').val(yyyy+'-'+mm+'-'+dd);
        } else if(val==='kemarin') {
            var kemarin = new Date(now.getTime() - 86400000);
            var ky = kemarin.getFullYear();
            var km = (kemarin.getMonth()+1).toString().padStart(2,'0');
            var kd = kemarin.getDate().toString().padStart(2,'0');
            $('#tanggal_mulai').val(ky+'-'+km+'-'+kd);
            $('#tanggal_akhir').val(ky+'-'+km+'-'+kd);
        } else if(val==='minggu') {
            var day = now.getDay()||7;
            var monday = new Date(now.getTime() - (day-1)*86400000);
            var sunday = new Date(monday.getTime() + 6*86400000);
            var my = monday.getFullYear();
            var mmn = (monday.getMonth()+1).toString().padStart(2,'0');
            var md = monday.getDate().toString().padStart(2,'0');
            var sy = sunday.getFullYear();
            var sm = (sunday.getMonth()+1).toString().padStart(2,'0');
            var sd = sunday.getDate().toString().padStart(2,'0');
            $('#tanggal_mulai').val(my+'-'+mmn+'-'+md);
            $('#tanggal_akhir').val(sy+'-'+sm+'-'+sd);
        } else if(val==='bulan') {
            $('#tanggal_mulai').val(yyyy+'-'+mm+'-01');
            var last = new Date(yyyy, mm, 0).getDate();
            $('#tanggal_akhir').val(yyyy+'-'+mm+'-'+last);
        }
    }
    $('#periode').on('change', function(){
        var val = $(this).val();
        if(val!=='custom') {
            setTanggalByPeriode(val);
            $('#tanggal_mulai, #tanggal_akhir').prop('readonly',true);
        } else {
            $('#tanggal_mulai, #tanggal_akhir').prop('readonly',false);
        }
        $('#filterForm').submit();
    });
    $('#tanggal_mulai, #tanggal_akhir').on('change', function(){
        $('#periode').val('custom');
        $('#tanggal_mulai, #tanggal_akhir').prop('readonly',false);
        $('#filterForm').submit();
    });
    $('#minimal_penggunaan').on('change', function(){
        $('#filterForm').submit();
    });
    $('#status_promo').on('change', function(){
        $('#filterForm').submit();
    });
});
</script>
        </div>
    </div>
    
<!-- Statistik Ringkas -->
<div class="row mb-4 justify-content-center g-3">
    <div class="col-lg-2 col-md-4 col-6">
        <div class="card h-100 border-0 shadow-sm text-center">
            <div class="card-body d-flex flex-column align-items-center justify-content-center py-3">
                <span class="badge bg-primary mb-2 px-2 py-1" style="font-size:0.95rem;"><i class="ti ti-bolt"></i> Promo Aktif</span>
                <h3 class="fw-bold mb-0" id="stat-promo-aktif"><?= number_format($stat_promo_aktif) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-lg-2 col-md-4 col-6">
        <div class="card h-100 border-0 shadow-sm text-center">
            <div class="card-body d-flex flex-column align-items-center justify-content-center py-3">
                <span class="badge bg-teal mb-2 px-2 py-1" style="font-size:0.95rem;"><i class="ti ti-table"></i> Total Penggunaan</span>
                <h3 class="fw-bold mb-0" id="stat-total-penggunaan"><?= number_format($stat_total_penggunaan) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-4 col-6">
        <div class="card h-100 border-0 shadow-sm text-center">
            <div class="card-body d-flex flex-column align-items-center justify-content-center py-3">
                <span class="badge bg-info mb-2 px-2 py-1" style="font-size:0.95rem;"><i class="ti ti-currency-dollar"></i> Total Diskon</span>
                <h3 class="fw-bold mb-0" id="stat-total-diskon">Rp <?= number_format($stat_total_diskon, 0, ',', '.') ?></h3>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-4 col-6">
        <div class="card h-100 border-0 shadow-sm text-center">
            <div class="card-body d-flex flex-column align-items-center justify-content-center py-3">
                <span class="badge bg-warning text-dark mb-2 px-2 py-1" style="font-size:0.95rem;"><i class="ti ti-chart-bar"></i> Rata-rata Diskon</span>
                <h3 class="fw-bold mb-0" id="stat-rata-diskon">Rp <?= number_format($stat_rata_diskon, 0, ',', '.') ?></h3>
            </div>
        </div>
    </div>
    <div class="col-lg-2 col-md-4 col-6">
        <div class="card h-100 border-0 shadow-sm text-center">
            <div class="card-body d-flex flex-column align-items-center justify-content-center py-3">
                <span class="badge bg-info mb-2 px-2 py-1" style="font-size:0.95rem;"><i class="ti ti-users"></i> Pelanggan Unik</span>
                <h3 class="fw-bold mb-0" id="stat-pelanggan-unik"><?= number_format($stat_pelanggan_unik) ?></h3>
            </div>
        </div>
    </div>
</div>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<style>
    .bg-teal { background-color: #20c997 !important; color: #fff; }
    /* Paksa ikon sort DataTables agar selalu tampak */
    th.sorting:after, th.sorting_asc:after, th.sorting_desc:after {
      opacity: 1 !important;
      color: #888 !important;
      font-size: 1em !important;
    }
    /* SVG panah sorting agar selalu tampil di semua tema */
    table.dataTable thead .sorting:after,
    table.dataTable thead .sorting_asc:after,
    table.dataTable thead .sorting_desc:after {
      content: "" !important;
      display: inline-block !important;
      width: 10px;
      height: 10px;
      margin-left: 6px;
      vertical-align: middle;
      background-repeat: no-repeat;
      background-size: contain;
    }
    table.dataTable thead .sorting:after {
      background-image: url('data:image/svg+xml;utf8,<svg width="10" height="10" xmlns="http://www.w3.org/2000/svg"><polygon points="0,3 5,8 10,3" style="fill:%23888;"/></svg>');
    }
    table.dataTable thead .sorting_asc:after {
      background-image: url('data:image/svg+xml;utf8,<svg width="10" height="10" xmlns="http://www.w3.org/2000/svg"><polygon points="0,7 5,2 10,7" style="fill:%23888;"/></svg>');
    }
    table.dataTable thead .sorting_desc:after {
      background-image: url('data:image/svg+xml;utf8,<svg width="10" height="10" xmlns="http://www.w3.org/2000/svg"><polygon points="0,3 5,8 10,3" style="fill:%23888;"/></svg>');
    }
</style>

    <!-- Tabel Data Laporan -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0"><i class="ti ti-table"></i> Data Penggunaan Promosi</h5>
            <div>
                <a href="laporan_promosi_export_excel.php?tanggal_mulai=<?=urlencode($tanggal_mulai)?>&tanggal_akhir=<?=urlencode($tanggal_akhir)?>" class="btn btn-success btn-sm me-2" target="_blank"><i class="ti ti-file-export"></i> Excel</a>
                <a href="laporan_promosi_export_pdf.php?tanggal_mulai=<?=urlencode($tanggal_mulai)?>&tanggal_akhir=<?=urlencode($tanggal_akhir)?>" class="btn btn-danger btn-sm me-2" target="_blank"><i class="ti ti-file-type-pdf"></i> PDF</a>
                <a href="laporan/print_promosi.php?periode=<?= urlencode($periode) ?>&tanggal_mulai=<?= urlencode($tanggal_mulai) ?>&tanggal_akhir=<?= urlencode($tanggal_akhir) ?>" class="btn btn-primary btn-sm" target="_blank"><i class="ti ti-printer"></i> Print</a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="laporanPromosiTable" class="table table-bordered table-striped" style="width:100%;">
                    <thead>
                        <tr>
                            <th>Judul Promo</th>
                            <th>Kode Promo</th>
                            <th>Status</th>
                            <th>Periode</th>
                            <th>Digunakan</th>
                            <th>Total Diskon (Rp)</th>
                            <th>Rata-rata Diskon</th>
                            <th>Pelanggan Unik</th>
                            <th>Detail</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($result as $row) : ?>
                        <tr>
                            <td><?= htmlspecialchars($row['judul']) ?></td>
                            <td><?= htmlspecialchars($row['kode_promo']) ?></td>
                            <td>
                                <?php
                                $status = strtolower($row['status_promo']);
                                $badge = 'bg-secondary';
                                if ($status == 'aktif') $badge = 'bg-success';
                                elseif ($status == 'draft') $badge = 'bg-warning';
                                elseif ($status == 'tidak aktif') $badge = 'bg-danger';
                                elseif ($status == 'terkirim') $badge = 'bg-info';
                                ?>
                                <span class="badge <?= $badge ?>" data-bs-toggle="tooltip" title="Status promo"><?= htmlspecialchars(ucfirst($row['status_promo'])) ?></span>
                            </td>
                            <td><?= $row['tanggal_buat'] ? htmlspecialchars(date('d-m-Y', strtotime($row['tanggal_buat']))) : '-' ?> s/d <?= $row['tanggal_berakhir'] ? htmlspecialchars(date('d-m-Y', strtotime($row['tanggal_berakhir']))) : '-' ?></td>
                            <td><?= $row['jumlah_penggunaan'] !== null ? htmlspecialchars($row['jumlah_penggunaan']) : '0' ?></td>
                            <td><?= isset($row['total_diskon']) ? number_format($row['total_diskon'], 0, ',', '.') : '0' ?></td>
                            <td><?= isset($row['rata_diskon']) ? number_format($row['rata_diskon'], 0, ',', '.') : '0' ?></td>
                            <td><?= $row['pelanggan_unik'] !== null ? htmlspecialchars($row['pelanggan_unik']) : '0' ?></td>
                            <td><button type="button" class="btn btn-info btn-sm btn-detail-promo" data-id="<?= $row['id_promosi'] ?>" data-bs-toggle="tooltip" title="Lihat detail penggunaan promosi"><i class="ti ti-search"></i> Detail</button></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detail Promo -->
<div class="modal fade" id="modalDetailPromo" tabindex="-1" aria-labelledby="modalDetailPromoLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalDetailPromoLabel">Detail Penggunaan Promo</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="modalDetailPromoBody">
        <!-- AJAX content here -->
      </div>
    </div>
  </div>
</div>

<!-- DataTables & Bootstrap JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
$(document).ready(function() {
    // Destroy if already initialized
    if ($.fn.DataTable.isDataTable('#laporanPromosiTable')) {
        $('#laporanPromosiTable').DataTable().destroy();
    }
    // DataTables initialization
    $('#laporanPromosiTable').DataTable({
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/id.json'
        },
        pageLength: 10,
        lengthMenu: [ [10, 25, 50, -1], [10, 25, 50, 'Semua'] ],
        columnDefs: [
            { orderable: false, targets: [8] }
        ],
        order: [[4, 'desc']]
    });
    // Bootstrap tooltip initialization
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.forEach(function (tooltipTriggerEl) {
        new bootstrap.Tooltip(tooltipTriggerEl);
    });
    // Event delegation for detail button click to load modal content via AJAX
    $('#laporanPromosiTable').on('click', '.btn-detail-promo', function() {
        var id = $(this).data('id');
        $('#modalDetailPromoBody').html('<div class="text-center py-5"><div class="spinner-border text-info"></div><div>Memuat detail...</div></div>');
        var modal = new bootstrap.Modal(document.getElementById('modalDetailPromo'));
        modal.show();
        $.ajax({
            url: 'laporan/detail_pesanan_promo.php',
            type: 'GET',
            data: {id_promosi: id, ajax: 1},
            success: function(res) {
                $('#modalDetailPromoBody').html(res);
            },
            error: function() {
                $('#modalDetailPromoBody').html('<div class="alert alert-danger">Gagal memuat detail promo.</div>');
            }
        });
    });
});
</script>
