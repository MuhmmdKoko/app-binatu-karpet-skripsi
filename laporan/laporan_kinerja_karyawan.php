<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include 'pengaturan/koneksi.php';
include "../template/header.php";

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
            <form method="GET" action="index.php" class="row g-3 align-items-end">
                <input type="hidden" name="page" value="laporan_kinerja_karyawan_read">
                <div class="col-md-4">
                    <label for="start_date" class="form-label">Tanggal Mulai</label>
                    <input type="date" id="start_date" name="start_date" class="form-control" value="<?= htmlspecialchars($start_date) ?>">
                </div>
                <div class="col-md-4">
                    <label for="end_date" class="form-label">Tanggal Selesai</label>
                    <input type="date" id="end_date" name="end_date" class="form-control" value="<?= htmlspecialchars($end_date) ?>">
                </div>
                <div class="col-md-3">
                    <label for="id_karyawan" class="form-label">Karyawan</label>
                    <select id="id_karyawan" name="id_karyawan" class="form-control">
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

        // Calculate if overdue
        $is_overdue = $stats['rata_waktu_pengerjaan'] > $stats['rata_estimasi'];
        $status_class = $is_overdue ? 'bg-danger' : 'bg-info';
        $rata_waktu = number_format($stats['rata_waktu_pengerjaan'], 1);
        $rata_estimasi = number_format($stats['rata_estimasi'], 1);
        ?>
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
                        <span class="badge <?= $status_class ?> text-white"><i class="ti ti-clock"></i> <?= $rata_waktu ?> Hari</span>
                    </div>
                    <?php if ($is_overdue): ?>
                        <small class="text-danger">(melebihi estimasi <?= $rata_estimasi ?> hari)</small>
                    <?php endif; ?>
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
                            $is_row_overdue = $row['rata_waktu'] > $row['rata_estimasi'];
                            $row_status_class = $is_row_overdue ? 'text-danger' : 'text-info';
                            
                            echo "<tr>";
                            echo "<td>" . $no++ . "</td>";
                            echo "<td>" . htmlspecialchars($row['nama_karyawan']) . "</td>";
                            echo "<td>" . number_format($row['total_pesanan']) . "</td>";
                            echo "<td class='" . $row_status_class . "'>" . 
                                 number_format($row['rata_waktu'], 1) . 
                                 ($is_row_overdue ? ' <small>(est. ' . number_format($row['rata_estimasi'], 1) . ')</small>' : '') . 
                                 "</td>";
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

<script>
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