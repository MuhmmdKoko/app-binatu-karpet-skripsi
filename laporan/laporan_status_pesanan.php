<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require 'pengaturan/koneksi.php';
// include "../template/header.php";

// Cek akses user
if ($_SESSION['role'] != "Admin") {
    echo '<script>alert("Anda tidak memiliki akses ke halaman ini!");window.location.href="index.php";</script>';
    exit;
}

// Selalu definisikan tanggal awal/akhir dan versi escape-nya
$tgl_awal = isset($_POST['tgl_awal']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['tgl_awal']) ? $_POST['tgl_awal'] : date('Y-m-01');
$tgl_akhir = isset($_POST['tgl_akhir']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['tgl_akhir']) ? $_POST['tgl_akhir'] : date('Y-m-d');
$tgl_awal_esc = mysqli_real_escape_string($konek, $tgl_awal);
$tgl_akhir_esc = mysqli_real_escape_string($konek, $tgl_akhir);

// Query statistik ringkas untuk card
$stat_total_pesanan = 0;
$stat_total_nilai = 0;
$stat_rata_nilai = 0;
$where_stat = "WHERE DATE(p.tanggal_masuk) BETWEEN '$tgl_awal_esc' AND '$tgl_akhir_esc'";
if (!empty($_POST['status_pesanan']) && in_array($_POST['status_pesanan'], ['Baru','Diproses','Selesai','Diambil','Dibatalkan'])) {
    $where_stat .= " AND p.status_pesanan_umum = '" . mysqli_real_escape_string($konek, $_POST['status_pesanan']) . "'";
}
$res_stat = mysqli_query($konek, "
    SELECT 
        COUNT(p.id_pesanan) as jumlah_pesanan,
        SUM(COALESCE(p.total_setelah_diskon, p.total_harga_keseluruhan, 0)) as total_nilai_pesanan,
        AVG(COALESCE(p.total_setelah_diskon, p.total_harga_keseluruhan, 0)) as rata_nilai_pesanan
    FROM pesanan p
    $where_stat
");
if ($res_stat) {
    $row_stat = mysqli_fetch_assoc($res_stat);
    $stat_total_pesanan = $row_stat['jumlah_pesanan'] ?? 0;
    $stat_total_nilai = $row_stat['total_nilai_pesanan'] ?? 0;
    $stat_rata_nilai = $row_stat['rata_nilai_pesanan'] ?? 0;
}
?>

<!-- JQuery, Bootstrap 5, DataTables JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
$(document).ready(function() {
    // Preset date logic
    function pad(n) { return n < 10 ? '0' + n : n; }
    function setTanggalPreset() {
        var now = new Date();
        var filter = $('#filter_periode').val();
        var tgl_awal = '', tgl_akhir = '';
        if (filter === 'hari') {
            tgl_awal = tgl_akhir = now.getFullYear() + '-' + pad(now.getMonth()+1) + '-' + pad(now.getDate());
        } else if (filter === 'kemarin') {
            var yesterday = new Date(now);
            yesterday.setDate(now.getDate()-1);
            tgl_awal = tgl_akhir = yesterday.getFullYear() + '-' + pad(yesterday.getMonth()+1) + '-' + pad(yesterday.getDate());
        } else if (filter === 'minggu') {
            var firstDay = new Date(now);
            firstDay.setDate(now.getDate() - now.getDay());
            tgl_awal = firstDay.getFullYear() + '-' + pad(firstDay.getMonth()+1) + '-' + pad(firstDay.getDate());
            tgl_akhir = now.getFullYear() + '-' + pad(now.getMonth()+1) + '-' + pad(now.getDate());
        } else if (filter === 'bulan') {
            tgl_awal = now.getFullYear() + '-' + pad(now.getMonth()+1) + '-01';
            tgl_akhir = now.getFullYear() + '-' + pad(now.getMonth()+1) + '-' + pad(now.getDate());
        } else if (filter === 'custom') {
            return;
        }
        if (tgl_awal && tgl_akhir) {
            $('#tgl_awal').val(tgl_awal);
            $('#tgl_akhir').val(tgl_akhir);
        }
    }
    $('#filter_periode').on('change', function() {
        setTanggalPreset();
        $('#filterForm').submit();
    });
    $('#tgl_awal, #tgl_akhir, #status_pesanan').on('change', function() {
        $('#filterForm').submit();
    });
    // DataTables
    $('#laporanStatusPesananTable').DataTable({
        language: {
            url: "https://cdn.datatables.net/plug-ins/1.13.6/i18n/id.json"
        },
        pageLength: 10,
        lengthMenu: [ [10, 25, 50, -1], [10, 25, 50, "Semua"] ],
        responsive: true,
        ordering: true,
        searching: true
    });
    // Bootstrap 5 tooltips
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function(el) {
        new bootstrap.Tooltip(el);
    });
    // Modal detail AJAX
    var detailModal = new bootstrap.Modal(document.getElementById('detailModal'));
    var detailContent = $('#detailContent');
    $('#laporanStatusPesananTable').on('click', '.btn-detail', function() {
        var status = $(this).data('status');
        detailContent.html('<p class="text-center">Memuat data...</p>');
        detailModal.show();
        $.ajax({
    url: 'laporan/detail_status_pesanan.php',
    type: 'POST',
    data: {
        status_pesanan: status,
        tgl_awal: $('#tgl_awal').val(),
        tgl_akhir: $('#tgl_akhir').val()
    },
    success: function(response) {
        detailContent.html(response);
        // Tambahkan console.log untuk debug:
        console.log("AJAX Success:", response);
    },
    error: function(xhr, status, error) {
        detailContent.html('<p class="text-center text-danger">Gagal memuat detail. Silakan coba lagi.</p>');
        // Tambahkan debug:
        console.log("AJAX Error:", status, error, xhr.responseText);
    }
});
    });
});
</script>

<div class="container-fluid mt-4">
    <!-- Judul dan Subjudul -->
    <div class="mb-4">
        <h3 class="mb-1">Laporan Status Pesanan</h3>
        <div class="text-muted">Rekapitulasi pesanan berdasarkan status dan periode</div>
    </div>
    <!-- Filter Card -->
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
                            <option value="minggu" <?= (isset($_POST['filter_periode']) && $_POST['filter_periode']=='minggu') ? 'selected' : '' ?>>Per Minggu</option>
                            <option value="bulan" <?= (isset($_POST['filter_periode']) && $_POST['filter_periode']=='bulan') ? 'selected' : '' ?>>Per Bulan</option>
                            <option value="custom" <?= (isset($_POST['filter_periode']) && $_POST['filter_periode']=='custom') ? 'selected' : '' ?>>Custom</option>
                        </select>
                    </div>
                    <div class="col-md-2 col-6">
                        <label for="tgl_awal" class="form-label">Tanggal Awal</label>
                        <input type="date" id="tgl_awal" name="tgl_awal" class="form-control" value="<?= $tgl_awal ?>">
                    </div>
                    <div class="col-md-2 col-6">
                        <label for="tgl_akhir" class="form-label">Tanggal Akhir</label>
                        <input type="date" id="tgl_akhir" name="tgl_akhir" class="form-control" value="<?= $tgl_akhir ?>">
                    </div>
                    <div class="col-md-3 col-12">
                        <label for="status_pesanan" class="form-label">Status Pesanan</label>
                        <select name="status_pesanan" id="status_pesanan" class="form-select">
                            <option value="">Semua Status</option>
                            <option value="Baru" <?= (isset($_POST['status_pesanan']) && $_POST['status_pesanan']=='Baru') ? 'selected' : '' ?>>Baru</option>
                            <option value="Diproses" <?= (isset($_POST['status_pesanan']) && $_POST['status_pesanan']=='Diproses') ? 'selected' : '' ?>>Diproses</option>
                            <option value="Selesai" <?= (isset($_POST['status_pesanan']) && $_POST['status_pesanan']=='Selesai') ? 'selected' : '' ?>>Selesai</option>
                            <option value="Diambil" <?= (isset($_POST['status_pesanan']) && $_POST['status_pesanan']=='Diambil') ? 'selected' : '' ?>>Diambil</option>
                            <option value="Dibatalkan" <?= (isset($_POST['status_pesanan']) && $_POST['status_pesanan']=='Dibatalkan') ? 'selected' : '' ?>>Dibatalkan</option>
                        </select>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Statistik Ringkas (opsional, untuk konsistensi) -->
    
    <div class="row mb-3">
    <div class="col-md-4">
        <div class="card text-center shadow-sm">
            <div class="card-body">
                <span class="badge bg-primary mb-2"><i class="ti ti-list-details"></i> Total Pesanan</span>
                <h3 class="mb-0"><?= number_format($stat_total_pesanan) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center shadow-sm">
            <div class="card-body">
                <span class="badge bg-success mb-2"><i class="ti ti-cash"></i> Total Nilai Pesanan</span>
                <h3 class="mb-0">Rp <?= number_format($stat_total_nilai, 0, ',', '.') ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center shadow-sm">
            <div class="card-body">
                <span class="badge bg-warning text-dark mb-2"><i class="ti ti-coin"></i> Rata-rata Nilai Pesanan</span>
                <h3 class="mb-0">Rp <?= number_format($stat_rata_nilai, 0, ',', '.') ?></h3>
            </div>
        </div>
    </div>
</div>

    <!-- Data Table Card -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0"><i class="ti ti-table"></i> Data Status Pesanan</h5>
            <div>
                <a href="export_status_pesanan.php?<?php echo http_build_query(['tgl_awal'=>$tgl_awal,'tgl_akhir'=>$tgl_akhir,'status_pesanan'=>$_POST['status_pesanan']??'']); ?>" class="btn btn-success btn-sm me-2" target="_blank" data-bs-toggle="tooltip" title="Export ke Excel"><i class="ti ti-file-spreadsheet"></i> Export Excel</a>
                <a href="print_status_pesanan.php?<?php echo http_build_query(['tgl_awal'=>$tgl_awal,'tgl_akhir'=>$tgl_akhir,'status_pesanan'=>$_POST['status_pesanan']??'']); ?>" class="btn btn-primary btn-sm" target="_blank" data-bs-toggle="tooltip" title="Cetak Laporan"><i class="ti ti-printer"></i> Print</a>
            </div>
        </div>
        <div class="card-body">
            <?php
            // Validasi dan sanitasi input tanggal
            // (You already declared $tgl_awal, $tgl_akhir, $tgl_awal_esc, $tgl_akhir_esc at the top)
            // Query untuk data utama
            $query = mysqli_query($konek, "
                SELECT 
                    COUNT(p.id_pesanan) as jumlah_pesanan,
                    SUM(COALESCE(p.total_setelah_diskon, p.total_harga_keseluruhan, 0)) as total_nilai_pesanan,
                    AVG(COALESCE(p.total_setelah_diskon, p.total_harga_keseluruhan, 0)) as rata_nilai_pesanan
                FROM pesanan p
                WHERE 1=1
                    AND DATE(p.tanggal_masuk) BETWEEN '$tgl_awal_esc' AND '$tgl_akhir_esc'
                    " . (
                        !empty($_POST['status_pesanan']) && in_array($_POST['status_pesanan'], ['Baru','Diproses','Selesai','Diambil','Dibatalkan'])
                        ? " AND p.status_pesanan_umum = '" . mysqli_real_escape_string($konek, $_POST['status_pesanan']) . "'"
                        : ""
                    ) . "
            ");

            if (!$query) {
                echo '<div class="alert alert-danger">Error: ' . mysqli_error($konek) . '</div>';
            } else {
            ?>
            <div class="table-responsive">
                <table id="laporanStatusPesananTable" class="table table-bordered table-hover w-100">
                <thead>
                            <th>No</th>
                            <th>Status Pesanan</th>
                            <th>Jumlah Pesanan</th>
                            <th>Total Nilai Pesanan</th>
                            <th>Rata-rata Nilai Pesanan</th>
                            <th>Detail</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        $total_pesanan = 0;
                        $total_nilai = 0;

                        // Ambil ulang semua pesanan pada filter ini untuk validasi promo
                        $sql_pesanan = mysqli_query($konek, "SELECT total_setelah_diskon, total_harga_keseluruhan FROM pesanan WHERE DATE(tanggal_masuk) BETWEEN '$tgl_awal_esc' AND '$tgl_akhir_esc'" . (
    !empty($_POST['status_pesanan']) && in_array($_POST['status_pesanan'], ['Baru','Diproses','Selesai','Diambil','Dibatalkan'])
    ? " AND status_pesanan_umum = '" . mysqli_real_escape_string($konek, $_POST['status_pesanan']) . "'"
    : ""
));
                        $jumlah_pesanan = 0;
                        $total_nilai_pesanan = 0;
                        while($row = mysqli_fetch_assoc($sql_pesanan)) {
                            $nilai = (isset($row['total_setelah_diskon']) && $row['total_setelah_diskon'] > 0) ? $row['total_setelah_diskon'] : $row['total_harga_keseluruhan'];
                            $total_nilai_pesanan += $nilai;
                            $jumlah_pesanan++;
                        }
                        $rata_nilai_pesanan = ($jumlah_pesanan > 0) ? ($total_nilai_pesanan / $jumlah_pesanan) : 0;

                        // Tampilkan satu baris saja
                        echo "<tr>";
                        echo "<td>1</td>";
                        echo "<td>" . (!empty($_POST['status_pesanan']) ? htmlspecialchars($_POST['status_pesanan']) : 'Semua Status') . "</td>";
                        echo "<td>" . number_format($jumlah_pesanan) . "</td>";
                        echo "<td>Rp " . number_format($total_nilai_pesanan, 0, ',', '.') . "</td>";
                        echo "<td>Rp " . number_format($rata_nilai_pesanan, 0, ',', '.') . "</td>";
                        echo "<td><button type='button' class='btn btn-info btn-sm btn-detail' data-status=\"" . (!empty($_POST['status_pesanan']) ? $_POST['status_pesanan'] : '') . "\" data-bs-toggle=\"tooltip\" title=\"Lihat detail pesanan\"><i class='ti ti-eye'></i></button></td>";
                        echo "</tr>";

                        $total_pesanan = $jumlah_pesanan;
                        $total_nilai = $total_nilai_pesanan;
                        // Tidak perlu blok else dan while lama, karena summary sudah satu baris.
                        ?>
                    </tbody>
                    <?php if ($jumlah_pesanan == 0): ?>
                    <tr>
                        <td colspan="2" align="center"><strong>Total</strong></td>
                        <td><strong>0</strong></td>
                        <td><strong>Rp 0</strong></td>
                        <td><strong>Rp 0</strong></td>
                        <td></td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    <?php } ?>
    </div> <!-- close card-body -->
</div> <!-- close card -->
</div> <!-- close container-fluid -->

<!-- Modal Detail (single, outside cards) -->
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail Pesanan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detailContent">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>
</div>