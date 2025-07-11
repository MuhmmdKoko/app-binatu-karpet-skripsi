<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Pastikan koneksi database sudah di-include
include_once dirname(__DIR__) . '/pengaturan/koneksi.php'; // atau sesuaikan path koneksi.php

// Helper PHP
function format_rupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}
function format_tanggal($tgl) {
    return date('d/m/Y', strtotime($tgl));
}

// Cek akses user
if ($_SESSION['role'] != "Admin") {
    echo '<script>alert("Anda tidak memiliki akses ke halaman ini!");window.location.href="index.php";</script>';
    exit;
}
// include "../template/header.php";
// --- QUERY AGREGASI UTAMA SEKALI SAJA ---
$tgl_awal = isset($_POST['tgl_awal']) ? $_POST['tgl_awal'] : (isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : date('Y-m-01'));
$tgl_akhir = isset($_POST['tgl_akhir']) ? $_POST['tgl_akhir'] : (isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : date('Y-m-d'));
$status_pembayaran = isset($_POST['status_pembayaran']) ? $_POST['status_pembayaran'] : (isset($_GET['status_pembayaran']) ? $_GET['status_pembayaran'] : '');

$data_per_tanggal = [];
$total_pesanan = 0;
$total_pendapatan = 0;
$total_lunas = 0;
$total_dp = 0;
$total_belum_lunas = 0;
// Penentuan WHERE clause berdasarkan filter_periode
$filter_periode = isset($_POST['filter_periode']) ? $_POST['filter_periode'] : (isset($_GET['filter_periode']) ? $_GET['filter_periode'] : 'hari');
$where = '';
switch ($filter_periode) {
    case '7hari':
        $today = date('Y-m-d');
        $seven_days_ago = date('Y-m-d', strtotime('-6 days'));
        $where = "WHERE DATE(p.tanggal_masuk) BETWEEN '$seven_days_ago' AND '$today'";
        break;
    case 'minggu':
        // Gunakan YEARWEEK untuk filter mingguan
        $minggu_awal = date('oW', strtotime($tgl_awal));
        $minggu_akhir = date('oW', strtotime($tgl_akhir));
        $where = "WHERE YEARWEEK(p.tanggal_masuk, 1) BETWEEN '$minggu_awal' AND '$minggu_akhir'";
        break;
    case 'bulan':
        $where = "WHERE DATE_FORMAT(p.tanggal_masuk, '%Y-%m') BETWEEN '" . date('Y-m', strtotime($tgl_awal)) . "' AND '" . date('Y-m', strtotime($tgl_akhir)) . "'";
        break;
    case 'kemarin':
        $kemarin = date('Y-m-d', strtotime('-1 day'));
        $where = "WHERE DATE(p.tanggal_masuk) = '$kemarin'";
        break;
    case 'hari':
        $where = "WHERE DATE(p.tanggal_masuk) = '$tgl_awal'";
        break;
    case 'custom':
    default:
        $where = "WHERE DATE(p.tanggal_masuk) BETWEEN '$tgl_awal' AND '$tgl_akhir'";
        break;
}
if (!empty($status_pembayaran)) {
    $where .= " AND p.status_pembayaran = '$status_pembayaran'";
}
// Pastikan hanya pesanan yang punya detail_pesanan yang dihitung (konsisten dengan detail)
// Tidak filter pesanan dengan total 0 agar rekap dan detail benar-benar sama
$query = mysqli_query($konek, "
    SELECT 
        DATE(p.tanggal_masuk) as tanggal,
        COUNT(p.id_pesanan) as jumlah_pesanan,
        SUM(IF(p.total_setelah_diskon IS NOT NULL AND p.total_setelah_diskon > 0, p.total_setelah_diskon, p.total_harga_keseluruhan)) as total_pendapatan,
        SUM(CASE WHEN p.status_pembayaran = 'Lunas' THEN IF(p.total_setelah_diskon IS NOT NULL AND p.total_setelah_diskon > 0, p.total_setelah_diskon, p.total_harga_keseluruhan) ELSE 0 END) as pendapatan_lunas,
        SUM(CASE WHEN p.status_pembayaran = 'DP' THEN p.nominal_pembayaran ELSE 0 END) as pendapatan_dp,
        SUM(CASE WHEN p.status_pembayaran = 'DP' THEN (IF(p.total_setelah_diskon IS NOT NULL AND p.total_setelah_diskon > 0, p.total_setelah_diskon, p.total_harga_keseluruhan) - p.nominal_pembayaran) ELSE 0 END) as sisa_dp,
        SUM(CASE WHEN p.status_pembayaran = 'Belum Lunas' THEN IF(p.total_setelah_diskon IS NOT NULL AND p.total_setelah_diskon > 0, p.total_setelah_diskon, p.total_harga_keseluruhan) ELSE 0 END) as pendapatan_belum_lunas
    FROM pesanan p
    $where
    GROUP BY DATE(p.tanggal_masuk)
    ORDER BY tanggal DESC
");
if (!$query) { die('Query error: ' . mysqli_error($konek)); }
while($row = mysqli_fetch_assoc($query)) {
    $tanggal = $row['tanggal'];
    $data_per_tanggal[$tanggal] = [
        'jumlah_pesanan' => (int)$row['jumlah_pesanan'],
        'total_pendapatan' => (float)$row['total_pendapatan'],
        'pendapatan_lunas' => (float)$row['pendapatan_lunas'],
        'pendapatan_dp' => (float)$row['pendapatan_dp'],
        'sisa_dp' => (float)$row['sisa_dp'],
        'pendapatan_belum_lunas' => (float)$row['pendapatan_belum_lunas'],
        'pendapatan_belum_diterima' => (float)$row['pendapatan_belum_lunas'] + (float)$row['sisa_dp']
    ];
    $total_pesanan += $row['jumlah_pesanan'];
    $total_pendapatan += $row['total_pendapatan'];
    $total_lunas += $row['pendapatan_lunas'];
    $total_dp += $row['pendapatan_dp'];
    $total_belum_lunas += $row['pendapatan_belum_lunas'];
    $total_sisa_dp += $row['sisa_dp'];
}
$jumlah_hari = count($data_per_tanggal);
$rata_rata_harian = $jumlah_hari > 0 ? ($total_pendapatan / $jumlah_hari) : 0;
?>

<div class="container-fluid mt-4">
    <!-- Judul dan Filter -->
    <div class="mb-4">
        <h3 class="mb-1">Laporan Pendapatan</h3>
        <div class="text-muted">Rekapitulasi pendapatan harian berdasarkan status pembayaran</div>
    </div>
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0"><i class="ti ti-filter-check"></i> Filter Laporan</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="" class="row g-3 align-items-end" id="filterForm">
                <div class="row g-3 align-items-end">
                    <div class="col-md-2 col-12">
                        <label for="filter_periode" class="form-label">Periode</label>
                        <select id="filter_periode" name="filter_periode" class="form-select">
                            <option value="hari" <?= (isset($_POST['filter_periode']) && $_POST['filter_periode']=='hari') ? 'selected' : '' ?>>Per Hari</option>
                            <option value="kemarin" <?= (isset($_POST['filter_periode']) && $_POST['filter_periode']=='kemarin') ? 'selected' : '' ?>>Kemarin</option>
                            <option value="7hari" <?= (isset($_POST['filter_periode']) && $_POST['filter_periode']=='7hari') ? 'selected' : '' ?>>7 Hari Terakhir</option>
                            <option value="minggu" <?= (isset($_POST['filter_periode']) && $_POST['filter_periode']=='minggu') ? 'selected' : '' ?>>Per Minggu</option>
                            <option value="bulan" <?= (isset($_POST['filter_periode']) && $_POST['filter_periode']=='bulan') ? 'selected' : '' ?>>Per Bulan</option>
                            <option value="custom" <?= (isset($_POST['filter_periode']) && $_POST['filter_periode']=='custom') ? 'selected' : '' ?>>Custom</option>
                        </select>
                    </div>
                    <div class="col-md-2 col-6">
                        <label for="tgl_awal" class="form-label">Tanggal Awal</label>
                        <input type="date" id="tgl_awal" name="tgl_awal" class="form-control" value="<?= isset($_POST['tgl_awal']) ? $_POST['tgl_awal'] : date('Y-m-01') ?>">
                    </div>
                    <div class="col-md-2 col-6">
                        <label for="tgl_akhir" class="form-label">Tanggal Akhir</label>
                        <input type="date" id="tgl_akhir" name="tgl_akhir" class="form-control" value="<?= isset($_POST['tgl_akhir']) ? $_POST['tgl_akhir'] : date('Y-m-d') ?>">
                    </div>
                    <div class="col-md-3 col-6">
                        <label for="status_pembayaran" class="form-label">Status Pembayaran</label>
                        <select id="status_pembayaran" name="status_pembayaran" class="form-select">
                            <option value="">Semua Status</option>
                            <option value="Belum Lunas" <?= (isset($_POST['status_pembayaran']) && $_POST['status_pembayaran']=='Belum Lunas') ? 'selected' : '' ?>>Belum Lunas</option>
                            <option value="DP" <?= (isset($_POST['status_pembayaran']) && $_POST['status_pembayaran']=='DP') ? 'selected' : '' ?>>DP</option>
                            <option value="Lunas" <?= (isset($_POST['status_pembayaran']) && $_POST['status_pembayaran']=='Lunas') ? 'selected' : '' ?>>Lunas</option>
                        </select>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Statistik Ringkas di luar blok PHP -->
    <div class="row mb-3">
        <div class="col-md-4">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <span class="badge bg-success mb-2"><i class="ti ti-cash"></i> Total Pendapatan</span>
                    <h3 class="mb-0"><?= format_rupiah($total_pendapatan) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <span class="badge bg-primary mb-2"><i class="ti ti-list-details"></i> Total Pesanan</span>
                    <h3 class="mb-0"><?= number_format($total_pesanan) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <span class="badge bg-warning text-dark mb-2"><i class="ti ti-calendar-stats"></i> Rata-rata Harian</span>
                    <h3 class="mb-0"><?= format_rupiah($rata_rata_harian) ?></h3>
                </div>
            </div>
        </div>
    </div>
    <!-- Card Data Table -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0" id="laporanPeriodeTitle"><i class="ti ti-table"></i> Data Pendapatan Harian</h5>
            <div>
                <a href="laporan/export_pendapatan.php?tgl_awal=<?= $tgl_awal ?>&tgl_akhir=<?= $tgl_akhir ?>&status_pembayaran=<?= $status_pembayaran ?>" class="btn btn-success btn-sm me-2" target="_blank"><i class="ti ti-file-export"></i> Excel</a>
                <a href="laporan/print_pendapatan.php?tgl_awal=<?= $tgl_awal ?>&tgl_akhir=<?= $tgl_akhir ?>&status_pembayaran=<?= $status_pembayaran ?>" class="btn btn-primary btn-sm" target="_blank"><i class="ti ti-printer"></i> Print</a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="laporanPendapatanTable" class="table table-bordered table-striped" style="width:100%;">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Tanggal</th>
                            <th>Jumlah Pesanan</th>
                            <th>Total Pendapatan</th>
                            <th>Pendapatan Lunas</th>
                            <th>Pendapatan DP</th>
                            <th>Pendapatan Belum Lunas</th>
                            <th>Pendapatan Belum Diterima</th>
                            <th>Detail</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        if (empty($data_per_tanggal)) {
    // Jika data kosong, tampilkan baris total saja dengan semua nilai 0
    echo "<tr>";
    echo "<td align='center'><strong>Total</strong></td>";
    echo "<td></td>";
    echo "<td>0</td>";
    echo "<td>Rp 0</td>";
    echo "<td>Rp 0</td>";
    echo "<td>Rp 0</td>";
    echo "<td>Rp 0</td>";
    echo "<td>Rp 0</td>";
    echo "<td></td>";
    echo "</tr>";
} else {
                            foreach($data_per_tanggal as $tanggal => $data) {
                                echo "<tr>";
                                echo "<td>" . $no++ . "</td>";
                                echo "<td>" . format_tanggal($tanggal) . "</td>";
                                echo "<td>" . number_format($data['jumlah_pesanan']) . "</td>";
                                echo "<td>" . format_rupiah($data['total_pendapatan']) . "</td>";
                                echo "<td>" . format_rupiah($data['pendapatan_lunas']) . "</td>";
                                echo "<td>" . format_rupiah($data['pendapatan_dp']) . "</td>";
                                echo "<td>" . format_rupiah($data['pendapatan_belum_lunas']) . "</td>";
                                echo "<td>" . format_rupiah($data['pendapatan_belum_diterima']) . "</td>";
                                echo "<td><button type='button' class='btn btn-info btn-sm btn-detail' data-tanggal='$tanggal' data-bs-toggle='tooltip' title='Lihat detail pendapatan'><i class='ti ti-eye'></i></button></td>";
                                echo "</tr>";
                            }
                        }
                        // Baris total hanya jika ada data (dipindah ke dalam else)

                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- DataTables Bootstrap 5 CSS (CDN) & Sorting Icon Fix -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <style>
      th.sorting:after, th.sorting_asc:after, th.sorting_desc:after {
        opacity: 1 !important;
        color: #888 !important;
        font-size: 1em !important;
      }
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
    <!-- Modal Detail -->
    <div class="modal fade" id="detailModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Pendapatan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detailContent">
                    <!-- Content will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- JS Libraries -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <!-- Main Script -->
    <script>
        $(document).ready(function() {
            // --- HELPER FUNCTIONS ---
            function setTanggalPreset() {
                var val = $('#filter_periode').val();
                var today = new Date();
                var yyyy = today.getFullYear();
                var mm = String(today.getMonth() + 1).padStart(2, '0');
                var dd = String(today.getDate()).padStart(2, '0');

                var tgl_awal = $('#tgl_awal');
                var tgl_akhir = $('#tgl_akhir');

                if (val === 'hari') {
                    tgl_awal.val(yyyy + '-' + mm + '-' + dd);
                    tgl_akhir.val(yyyy + '-' + mm + '-' + dd);
                } else if (val === 'kemarin') {
                    var kemarin = new Date(today.getTime() - 86400000);
                    var ky = kemarin.getFullYear();
                    var km = String(kemarin.getMonth() + 1).padStart(2, '0');
                    var kd = String(kemarin.getDate()).padStart(2, '0');
                    tgl_awal.val(ky + '-' + km + '-' + kd);
                    tgl_akhir.val(ky + '-' + km + '-' + kd);
                } else if (val === '7hari') {
                    var sevenDaysAgo = new Date(today.getTime() - 6 * 86400000);
                    var sda_y = sevenDaysAgo.getFullYear();
                    var sda_m = String(sevenDaysAgo.getMonth() + 1).padStart(2, '0');
                    var sda_d = String(sevenDaysAgo.getDate()).padStart(2, '0');
                    tgl_awal.val(sda_y + '-' + sda_m + '-' + sda_d);
                    tgl_akhir.val(yyyy + '-' + mm + '-' + dd);
                } else if (val === 'minggu') {
                    var day = today.getDay() || 7;
                    var monday = new Date(today.getTime() - (day - 1) * 86400000);
                    var sunday = new Date(monday.getTime() + 6 * 86400000);
                    tgl_awal.val(monday.getFullYear() + '-' + String(monday.getMonth() + 1).padStart(2, '0') + '-' + String(monday.getDate()).padStart(2, '0'));
                    tgl_akhir.val(sunday.getFullYear() + '-' + String(sunday.getMonth() + 1).padStart(2, '0') + '-' + String(sunday.getDate()).padStart(2, '0'));
                } else if (val === 'bulan') {
                    tgl_awal.val(yyyy + '-' + mm + '-01');
                    var lastDay = new Date(yyyy, today.getMonth() + 1, 0);
                    tgl_akhir.val(yyyy + '-' + mm + '-' + String(lastDay.getDate()).padStart(2, '0'));
                }

                if (val === 'custom') {
                    tgl_awal.prop('readonly', false);
                    tgl_akhir.prop('readonly', false);
                } else {
                    tgl_awal.prop('readonly', true);
                    tgl_akhir.prop('readonly', true);
                }
            }

            // --- DATA TABLE INITIALIZATION ---
            if ($.fn.DataTable.isDataTable('#laporanPendapatanTable')) {
                $('#laporanPendapatanTable').DataTable().destroy();
            }
            var table = $('#laporanPendapatanTable').DataTable({
                language: { url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/id.json' },
                pageLength: 10,
                lengthMenu: [ [10, 25, 50, -1], [10, 25, 50, 'Semua'] ],
                columnDefs: [ { orderable: false, targets: [0, 8] } ], // No, Detail not orderable
                order: [[1, 'desc']], // Order by Tanggal descending
                drawCallback: function() {
                    // Re-initialize tooltips on every table draw
                    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                    tooltipTriggerList.forEach(function (tooltipTriggerEl) {
                        new bootstrap.Tooltip(tooltipTriggerEl);
                    });
                }
            });

            // --- FILTER FORM EVENT HANDLERS ---
            $('#filter_periode').on('change', function() {
                setTanggalPreset();
                $('#filterForm').submit();
            });

            $('#tgl_awal, #tgl_akhir, #status_pembayaran').on('change', function() {
                // When custom dates are changed, set the dropdown to 'custom'
                if ($(this).is('#tgl_awal') || $(this).is('#tgl_akhir')) {
                    $('#filter_periode').val('custom');
                    $('#tgl_awal, #tgl_akhir').prop('readonly', false);
                }
                $('#filterForm').submit();
            });

            // --- MODAL DETAIL LOADER ---
            $('#laporanPendapatanTable').on('click', '.btn-detail', function() {
                var tanggal = $(this).data('tanggal');
                $('#detailContent').html('<p class="text-center">Memuat detail ...</p>');
                var modal = new bootstrap.Modal(document.getElementById('detailModal'));
                modal.show();

                // Mengambil nilai filter saat ini dari form
                var tgl_awal = $('#tgl_awal').val();
                var tgl_akhir = $('#tgl_akhir').val();
                var status_pembayaran = $('#status_pembayaran').val();

                $.ajax({
                    url: 'laporan/detail_pendapatan.php',
                    type: 'POST',
                    data: {
                        periode: tanggal, // Tanggal spesifik dari baris yang diklik
                        tipe_laporan: 'harian', // Detail selalu bersifat harian
                        tgl_awal: tgl_awal, // Teruskan filter utama
                        tgl_akhir: tgl_akhir, // Teruskan filter utama
                        status_pembayaran: status_pembayaran // Teruskan filter utama
                    },
                    success: function(response) {
                        $('#detailContent').html(response);
                    },
                    error: function() {
                        $('#detailContent').html('<p class="text-center text-danger">Gagal memuat detail. Silakan coba lagi.</p>');
                    }
                });
            });

            // --- TITLE UPDATE LOGIC ---
            const periodeLabels = {
                hari: "Data Pendapatan Harian",
                kemarin: "Data Pendapatan Kemarin",
                '7hari': "Data Pendapatan 7 Hari Terakhir",
                minggu: "Data Pendapatan Mingguan",
                bulan: "Data Pendapatan Bulanan",
                custom: "Data Pendapatan (Custom)"
            };

            function updateLaporanPeriodeTitle() {
                const val = $('#filter_periode').val();
                const title = periodeLabels[val] || "Data Pendapatan";
                $('#laporanPeriodeTitle').html('<i class="ti ti-table"></i> ' + title);
            }

            // Initial call to set title and date presets on page load
            updateLaporanPeriodeTitle();
            setTanggalPreset();

            // Update title on change
            $('#filter_periode').on('change', updateLaporanPeriodeTitle);

        }); // End of $(document).ready()
    </script>
</body>
</html>