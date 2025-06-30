<?php
// Debug: aktifkan error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// Ambil koneksi dan query data
include 'pengaturan/koneksi.php';
include "../template/header.php";

// Logika untuk filter tanggal
$tanggal_mulai = isset($_POST['tanggal_mulai']) ? $_POST['tanggal_mulai'] : date('Y-m-01');
$tanggal_akhir = isset($_POST['tanggal_akhir']) ? $_POST['tanggal_akhir'] : date('Y-m-t');

// Query untuk mengambil data promosi dan agregat penggunaannya
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
            pesanan ps ON p.id_promosi = ps.id_promosi AND ps.tanggal_masuk BETWEEN ? AND ?
        GROUP BY
            p.id_promosi, p.judul, p.kode_promo, p.status_promo, p.tipe_promo, p.nilai_promo, p.tanggal_buat, p.tanggal_berakhir, p.syarat_min_transaksi
        ORDER BY
            p.id_promosi DESC";

$stmt = mysqli_prepare($konek, $sql);

// Tambahkan pengecekan error untuk mysqli_prepare
if ($stmt === false) {
    die('Error preparing statement: ' . htmlspecialchars(mysqli_error($konek)));
}

mysqli_stmt_bind_param($stmt, "ss", $tanggal_mulai, $tanggal_akhir);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

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
            <form method="POST" action="" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="tanggal_mulai" class="form-label">Tanggal Mulai</label>
                    <input type="date" id="tanggal_mulai" name="tanggal_mulai" class="form-control" value="<?= htmlspecialchars($tanggal_mulai) ?>">
                </div>
                <div class="col-md-4">
                    <label for="tanggal_akhir" class="form-label">Tanggal Akhir</label>
                    <input type="date" id="tanggal_akhir" name="tanggal_akhir" class="form-control" value="<?= htmlspecialchars($tanggal_akhir) ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100"><i class="ti ti-filter-check"></i> Filter</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabel Data Laporan -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0"><i class="ti ti-table"></i> Data Penggunaan Promosi</h5>
            <div>
                <a href="laporan_promosi_export_excel.php?tanggal_mulai=<?=urlencode($tanggal_mulai)?>&tanggal_akhir=<?=urlencode($tanggal_akhir)?>" class="btn btn-success btn-sm me-2" target="_blank"><i class="ti ti-file-export"></i> Excel</a>
                <a href="laporan_promosi_export_pdf.php?tanggal_mulai=<?=urlencode($tanggal_mulai)?>&tanggal_akhir=<?=urlencode($tanggal_akhir)?>" class="btn btn-danger btn-sm me-2" target="_blank"><i class="ti ti-file-type-pdf"></i> PDF</a>
                <a href="print_promosi.php?tanggal_mulai=<?=urlencode($tanggal_mulai)?>&tanggal_akhir=<?=urlencode($tanggal_akhir)?>" class="btn btn-primary btn-sm" target="_blank"><i class="ti ti-printer"></i> Print</a>
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
                        <?php while ($row = mysqli_fetch_assoc($result)) : ?>
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
                        <?php endwhile; ?>
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
    $('#laporanPromosiTable').DataTable({
        "language": {
            "url": "https://cdn.datatables.net/plug-ins/1.13.6/i18n/id.json"
        },
        "pageLength": 10,
        "lengthMenu": [ [10, 25, 50, -1], [10, 25, 50, "Semua"] ]
    });
    // Aktifkan tooltip Bootstrap
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    [...tooltipTriggerList].map(el => new bootstrap.Tooltip(el));

    // Modal AJAX Detail Promo
    $(document).on('click', '.btn-detail-promo', function() {
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
