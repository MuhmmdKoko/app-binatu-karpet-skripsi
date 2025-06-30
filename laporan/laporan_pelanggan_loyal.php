<?php
// Cek akses user
if ($_SESSION['role'] != "Admin") {
    echo '<script>alert("Anda tidak memiliki akses ke halaman ini!");window.location.href="index.php";</script>';
    exit;
}
// include "../template/header.php";

// Ambil tanggal awal/akhir dari POST (form) atau GET (print/export)
$tgl_awal = isset($_POST['tgl_awal']) ? $_POST['tgl_awal'] : (isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : date('Y-m-01'));
$tgl_akhir = isset($_POST['tgl_akhir']) ? $_POST['tgl_akhir'] : (isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : date('Y-m-d'));
$terakhir_transaksi = isset($_POST['terakhir_transaksi']) ? $_POST['terakhir_transaksi'] : '';
$min_pesanan = isset($_POST['min_pesanan']) ? $_POST['min_pesanan'] : 3;
$total_nilai_minimal = isset($_POST['total_nilai_minimal']) ? $_POST['total_nilai_minimal'] : 0;
?>

<div class="container-fluid mt-4">
    <!-- Judul dan Filter -->
    <div class="mb-4">
        <h3 class="mb-1">Laporan Pelanggan Loyal</h3>
        <div class="text-muted">Daftar pelanggan dengan transaksi dan nilai pesanan terbanyak</div>
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
                    <div class="col-md-3 col-6">
                        <label for="min_pesanan" class="form-label">Minimal Pesanan</label>
                        <input type="number" id="min_pesanan" name="min_pesanan" class="form-control" value="<?= $min_pesanan ?>" min="1">
                    </div>
                    <div class="col-md-3 col-6">
                        <label for="total_nilai_minimal" class="form-label">Total Nilai Transaksi Minimal</label>
                        <input type="number" id="total_nilai_minimal" name="total_nilai_minimal" class="form-control" value="<?= $total_nilai_minimal ?>" min="0">
                    </div>

                </div>
            </form>
<script>
$(document).ready(function() {
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
            var day = now.getDay();
            var diff = (day === 0 ? 6 : day - 1); // Senin = 1, Minggu = 0
            firstDay.setDate(now.getDate() - diff);
            tgl_awal = firstDay.getFullYear() + '-' + pad(firstDay.getMonth()+1) + '-' + pad(firstDay.getDate());
            tgl_akhir = now.getFullYear() + '-' + pad(now.getMonth()+1) + '-' + pad(now.getDate());
        } else if (filter === 'bulan') {
            tgl_awal = now.getFullYear() + '-' + pad(now.getMonth()+1) + '-01';
            tgl_akhir = now.getFullYear() + '-' + pad(now.getMonth()+1) + '-' + pad(now.getDate());
        } else if (filter === 'custom') {
            // Biarkan user mengisi manual
            return;
        }
        if (tgl_awal && tgl_akhir) {
            $('#tgl_awal').val(tgl_awal);
            $('#tgl_akhir').val(tgl_akhir);
        }
    }
    // Saat preset periode diubah
    $('#filter_periode').on('change', function() {
        setTanggalPreset();
        $('#filterForm').submit();
    });
    // Live filter untuk input tanggal, min pesanan, total nilai
    $('#tgl_awal, #tgl_akhir, #min_pesanan, #total_nilai_minimal').on('change', function() {
        $('#filterForm').submit();
    });
});
</script>
        </div>
    </div>
    <!-- Statistik Ringkas -->
<?php
// Hitung statistik dari hasil query di bawah (akan diisi ulang di tabel)
$stat_total_pelanggan = 0;
$stat_total_pesanan = 0;
$stat_total_nilai = 0;
$tmp_query = mysqli_query($konek, "
    SELECT 
        pl.*,
        COUNT(p.id_pesanan) as jumlah_pesanan,
        SUM(IF(p.total_setelah_diskon IS NOT NULL AND p.total_setelah_diskon > 0, p.total_setelah_diskon, p.total_harga_keseluruhan)) as total_nilai_pesanan,
        AVG(IF(p.total_setelah_diskon IS NOT NULL AND p.total_setelah_diskon > 0, p.total_setelah_diskon, p.total_harga_keseluruhan)) as rata_nilai_pesanan,
        MAX(p.tanggal_masuk) as terakhir_transaksi
    FROM pelanggan pl
    JOIN pesanan p ON pl.id_pelanggan = p.id_pelanggan
    WHERE p.tanggal_masuk BETWEEN '$tgl_awal' AND '$tgl_akhir'
    GROUP BY pl.id_pelanggan
    HAVING COUNT(p.id_pesanan) >= $min_pesanan 
        AND SUM(IF(p.total_setelah_diskon IS NOT NULL AND p.total_setelah_diskon > 0, p.total_setelah_diskon, p.total_harga_keseluruhan)) >= $total_nilai_minimal
    ORDER BY jumlah_pesanan DESC, total_nilai_pesanan DESC
");
while($row = mysqli_fetch_assoc($tmp_query)) {
    $stat_total_pelanggan++;
    $stat_total_pesanan += $row['jumlah_pesanan'];
    $stat_total_nilai += $row['total_nilai_pesanan'];
}
?>
<div class="row mb-3">
    <div class="col-md-4">
        <div class="card text-center shadow-sm">
            <div class="card-body">
                <span class="badge bg-primary mb-2"><i class="ti ti-users"></i> Total Pelanggan Loyal</span>
                <h3 class="mb-0"><?= number_format($stat_total_pelanggan) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center shadow-sm">
            <div class="card-body">
                <span class="badge bg-success mb-2"><i class="ti ti-list-details"></i> Total Pesanan</span>
                <h3 class="mb-0"><?= number_format($stat_total_pesanan) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center shadow-sm">
            <div class="card-body">
                <span class="badge bg-warning text-dark mb-2"><i class="ti ti-cash"></i> Total Nilai Transaksi</span>
                <h3 class="mb-0">Rp <?= number_format($stat_total_nilai, 0, ',', '.') ?></h3>
            </div>
        </div>
    </div>
</div>
<!-- END Statistik Ringkas -->
<div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0"><i class="ti ti-table"></i> Data Pelanggan Loyal</h5>
            <div>
                <a href="laporan/export_pelanggan_loyal.php?min_pesanan=<?= $min_pesanan ?>&total_nilai_minimal=<?= $total_nilai_minimal ?>&terakhir_transaksi=<?= $terakhir_transaksi ?>&tgl_awal=<?= $tgl_awal ?>&tgl_akhir=<?= $tgl_akhir ?>" class="btn btn-success btn-sm me-2" target="_blank"><i class="ti ti-file-export"></i> Excel</a>
                <?php
    $print_params = [];
    if (!empty($min_pesanan)) $print_params['min_pesanan'] = $min_pesanan;
    if (!empty($total_nilai_minimal)) $print_params['total_nilai_minimal'] = $total_nilai_minimal;
    if (!empty($tgl_awal)) $print_params['tgl_awal'] = $tgl_awal;
    if (!empty($tgl_akhir)) $print_params['tgl_akhir'] = $tgl_akhir;
?>
<a href="laporan/print_pelanggan_loyal.php<?= $print_params ? ('?' . http_build_query($print_params)) : '' ?>" class="btn btn-primary btn-sm" target="_blank"><i class="ti ti-printer"></i> Print</a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="laporanPelangganLoyalTable" class="table table-bordered table-striped" style="width:100%;">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Pelanggan</th>
                            <th>No. Telepon</th>
                            <th>Alamat</th>
                            <th>Jumlah Pesanan</th>
                            <th>Total Nilai Pesanan</th>
                            <th>Rata-rata Nilai Pesanan</th>
                            <th>Terakhir Transaksi</th>
                            <th>Detail</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = mysqli_query($konek, "
    SELECT 
        pl.*,
        COUNT(p.id_pesanan) as jumlah_pesanan,
        SUM(IF(p.total_setelah_diskon IS NOT NULL AND p.total_setelah_diskon > 0, p.total_setelah_diskon, p.total_harga_keseluruhan)) as total_nilai_pesanan,
        AVG(IF(p.total_setelah_diskon IS NOT NULL AND p.total_setelah_diskon > 0, p.total_setelah_diskon, p.total_harga_keseluruhan)) as rata_nilai_pesanan,
        MAX(p.tanggal_masuk) as terakhir_transaksi
    FROM pelanggan pl
    JOIN pesanan p ON pl.id_pelanggan = p.id_pelanggan
    WHERE p.tanggal_masuk BETWEEN '$tgl_awal' AND '$tgl_akhir'
    GROUP BY pl.id_pelanggan
    HAVING COUNT(p.id_pesanan) >= $min_pesanan 
        AND SUM(IF(p.total_setelah_diskon IS NOT NULL AND p.total_setelah_diskon > 0, p.total_setelah_diskon, p.total_harga_keseluruhan)) >= $total_nilai_minimal
    ORDER BY jumlah_pesanan DESC, total_nilai_pesanan DESC
");
                        $no = 1;
                        $total_pelanggan = 0;
                        $total_pesanan = 0;
                        $total_nilai = 0;
                        while($data = mysqli_fetch_array($query)) {
                            echo "<tr>";
                            echo "<td>" . $no++ . "</td>";
                            echo "<td>" . htmlspecialchars($data['nama_pelanggan']) . "</td>";
                            echo "<td>" . htmlspecialchars($data['nomor_telepon']) . "</td>";
                            echo "<td>" . htmlspecialchars($data['alamat']) . "</td>";
                            echo "<td>" . number_format($data['jumlah_pesanan']) . "</td>";
                            echo "<td>Rp " . number_format($data['total_nilai_pesanan'], 0, ',', '.') . "</td>";
                            echo "<td>Rp " . number_format($data['rata_nilai_pesanan'], 0, ',', '.') . "</td>";
                            echo "<td>" . date('d/m/Y', strtotime($data['terakhir_transaksi'])) . "</td>";
                            echo "<td><button type='button' class='btn btn-info btn-sm btn-detail' data-id='" . $data['id_pelanggan'] . "' data-bs-toggle='tooltip' title='Lihat detail transaksi pelanggan'><i class='ti ti-eye'></i></button></td>";
                            echo "</tr>";
                            $total_pelanggan++;
                            $total_pesanan += $data['jumlah_pesanan'];
                            $total_nilai += $data['total_nilai_pesanan'];
                        }
                        ?>
                        <tr>
                            <td align="center"><strong>Total</strong></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td><strong><?= number_format($total_pesanan) ?></strong></td>
                            <td><strong>Rp <?= number_format($total_nilai, 0, ',', '.') ?></strong></td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- Modal Detail -->
    <div class="modal fade" id="detailModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Pesanan Pelanggan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detailContent">
                    <!-- Content will be loaded here -->
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
        var detailModal = new bootstrap.Modal(document.getElementById('detailModal'));
        var detailContent = $('#detailContent');
        $('#laporanPelangganLoyalTable').DataTable({
    language: {
        url: "https://cdn.datatables.net/plug-ins/1.13.6/i18n/id.json"
    },
    pageLength: 10,
    lengthMenu: [ [10, 25, 50, -1], [10, 25, 50, "Semua"] ],
    responsive: true,
    ordering: true,
    searching: true
});
// Aktifkan tooltip Bootstrap 5 untuk semua tombol/icon
document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function(el) {
    new bootstrap.Tooltip(el);
});
// Event delegation untuk tombol detail
$('tbody').on('click', '.btn-detail', function() {
    var id_pelanggan = $(this).data('id');
    detailContent.html('<p class="text-center">Memuat riwayat...</p>');
    detailModal.show();
    var terakhir_transaksi = $('input[name="terakhir_transaksi"]').val();
    $.ajax({
        url: 'laporan/detail_pelanggan_loyal.php',
        type: 'POST',
        data: { 
            id_pelanggan: id_pelanggan,
            terakhir_transaksi: terakhir_transaksi
        },
        success: function(response) {
            detailContent.html(response);
        },
        error: function() {
            detailContent.html('<p class="text-center text-danger">Gagal memuat detail. Silakan coba lagi.</p>');
        }
    });
});
    });
    </script>