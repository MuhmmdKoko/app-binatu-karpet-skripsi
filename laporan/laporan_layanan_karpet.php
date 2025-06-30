<?php
// Filter date handling
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$jenis_layanan = isset($_GET['jenis_layanan']) ? $_GET['jenis_layanan'] : '';

// Cek akses user
if ($_SESSION['role'] != "Admin") {
    echo '<script>alert("Anda tidak memiliki akses ke halaman ini!");window.location.href="index.php";</script>';
    exit;
}

// include "../template/header.php";

// Penentuan filter periode
$filter_periode = isset($_POST['filter_periode']) ? $_POST['filter_periode'] : 'hari';
if ($filter_periode === 'custom') {
    $tgl_awal = isset($_POST['tgl_awal']) ? $_POST['tgl_awal'] : date('Y-m-d');
    $tgl_akhir = isset($_POST['tgl_akhir']) ? $_POST['tgl_akhir'] : date('Y-m-d');
} else {
    $today = date('Y-m-d');
    if ($filter_periode === 'hari') {
        $tgl_awal = $tgl_akhir = $today;
    } elseif ($filter_periode === 'kemarin') {
        $tgl_awal = $tgl_akhir = date('Y-m-d', strtotime('-1 day', strtotime($today)));
    } elseif ($filter_periode === 'minggu') {
        $dayOfWeek = date('N', strtotime($today)); // 1 (Senin) - 7 (Minggu)
        $tgl_awal = date('Y-m-d', strtotime($today . ' -' . ($dayOfWeek - 1) . ' days'));
        $tgl_akhir = date('Y-m-d', strtotime($tgl_awal . ' +6 days'));
    } elseif ($filter_periode === 'bulan') {
        $tgl_awal = date('Y-m-01', strtotime($today));
        $tgl_akhir = date('Y-m-t', strtotime($today));
    } elseif ($filter_periode === '7hari') {
        $tgl_awal = date('Y-m-d', strtotime('-6 days', strtotime($today)));
        $tgl_akhir = $today;
    } else {
        $tgl_awal = $tgl_akhir = $today;
    }
}

?>

<div class="container-fluid mt-4">
    
    <!-- Judul dan Filter -->
    <div class="mb-4">
        <h3 class="mb-1">Laporan Layanan Karpet</h3>
        <div class="text-muted">Daftar transaksi layanan karpet per periode</div>
    </div>
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0"><i class="ti ti-filter-check"></i> Filter Laporan</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label for="filter_periode" class="form-label">Filter Periode</label>
                    <select id="filter_periode" name="filter_periode" class="form-control">
                        <option value="hari"<?= (!isset($_POST['filter_periode']) || $_POST['filter_periode']==='hari') ? ' selected' : '' ?>>Per Hari</option>
                        <option value="kemarin"<?= (isset($_POST['filter_periode']) && $_POST['filter_periode']==='kemarin') ? ' selected' : '' ?>>Kemarin</option>
                        <option value="minggu"<?= (isset($_POST['filter_periode']) && $_POST['filter_periode']==='minggu') ? ' selected' : '' ?>>Per Minggu</option>
                        <option value="bulan"<?= (isset($_POST['filter_periode']) && $_POST['filter_periode']==='bulan') ? ' selected' : '' ?>>Per Bulan</option>
                        <option value="7hari"<?= (isset($_POST['filter_periode']) && $_POST['filter_periode']==='7hari') ? ' selected' : '' ?>>7 Hari Terakhir</option>
                        <option value="custom"<?= (isset($_POST['filter_periode']) && $_POST['filter_periode']==='custom') ? ' selected' : '' ?>>Custom</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="tgl_awal" class="form-label">Tanggal Awal</label>
                    <input type="date" id="tgl_awal" name="tgl_awal" class="form-control" value="<?= $tgl_awal ?>">
                </div>
                <div class="col-md-3">
                    <label for="tgl_akhir" class="form-label">Tanggal Akhir</label>
                    <input type="date" id="tgl_akhir" name="tgl_akhir" class="form-control" value="<?= $tgl_akhir ?>">
                </div>
                <div class="col-md-3">
                    <label for="layanan_karpet" class="form-label">Layanan Karpet</label>
                    <select id="layanan_karpet" name="layanan_karpet" class="form-control">
                        <option value="">-- Semua Layanan Karpet --</option>
                        <?php
                        $qLayanan = mysqli_query($konek, "SELECT * FROM layanan WHERE nama_layanan LIKE '%Karpet%' ORDER BY nama_layanan ASC");
                        $layanan_karpet_selected = isset($_POST['layanan_karpet']) ? $_POST['layanan_karpet'] : '';
                        while($row = mysqli_fetch_assoc($qLayanan)) {
                            $selected = ($layanan_karpet_selected == $row['id_layanan']) ? 'selected' : '';
                            echo '<option value="'.htmlspecialchars($row['id_layanan']).'" '.$selected.'>'.htmlspecialchars($row['nama_layanan']).'</option>';
                        }
                        ?>
                    </select>
                </div>
                
<script>
$(document).ready(function() {
    function setTanggalByPeriode(periode) {
        var today = new Date();
        var yyyy = today.getFullYear();
        var mm = (today.getMonth()+1).toString().padStart(2,'0');
        var dd = today.getDate().toString().padStart(2,'0');
        var tgl_awal = '', tgl_akhir = '';
        if (periode === 'hari') {
            tgl_awal = tgl_akhir = yyyy+'-'+mm+'-'+dd;
        } else if (periode === 'kemarin') {
            var yesterday = new Date();
            yesterday.setDate(today.getDate() - 1);
            var yyy = yesterday.getFullYear();
            var mmm = (yesterday.getMonth()+1).toString().padStart(2,'0');
            var ddd = yesterday.getDate().toString().padStart(2,'0');
            tgl_awal = tgl_akhir = yyy+'-'+mmm+'-'+ddd;
        } else if (periode === '7hari') {
            var sevenDaysAgo = new Date();
            sevenDaysAgo.setDate(today.getDate() - 6);
            var yyy = sevenDaysAgo.getFullYear();
            var mmm = (sevenDaysAgo.getMonth()+1).toString().padStart(2,'0');
            var ddd = sevenDaysAgo.getDate().toString().padStart(2,'0');
            tgl_awal = yyy+'-'+mmm+'-'+ddd;
            tgl_akhir = yyyy+'-'+mm+'-'+dd;
        } else if (periode === 'minggu') {
            var dayOfWeek = today.getDay() || 7;
            var start = new Date(today);
            start.setDate(today.getDate() - dayOfWeek + 1);
            var end = new Date(start);
            end.setDate(start.getDate() + 6);
            tgl_awal = start.toISOString().slice(0,10);
            tgl_akhir = end.toISOString().slice(0,10);
        } else if (periode === 'bulan') {
            tgl_awal = yyyy+'-'+mm+'-01';
            var lastDay = new Date(yyyy, today.getMonth()+1, 0).getDate();
            tgl_akhir = yyyy+'-'+mm+'-'+lastDay.toString().padStart(2,'0');
        }
        if(periode !== 'custom') {
            $('#tgl_awal').val(tgl_awal).prop('disabled', true);
            $('#tgl_akhir').val(tgl_akhir).prop('disabled', true);
        } else {
            $('#tgl_awal, #tgl_akhir').prop('disabled', false);
        }
    }
    // Inisialisasi awal
    setTanggalByPeriode($('#filter_periode').val());
    // Ganti filter periode
    $('#filter_periode').on('change', function() {
        setTanggalByPeriode(this.value);
        $(this).closest('form').submit();
    });
    // Jika custom, submit saat tanggal berubah
    $('#tgl_awal, #tgl_akhir').on('change', function() {
        if($('#filter_periode').val() === 'custom') {
            $(this).closest('form').submit();
        }
    });
    // Submit otomatis saat filter layanan karpet berubah
    $('#layanan_karpet').on('change', function() {
        $(this).closest('form').submit();
    });
});
</script>
            </form>
        </div>
    </div>
    
    <!-- Statistik Ringkas -->
    <?php
    // Inisialisasi nilai default jika belum dihitung
    if (!isset($total_karpet)) $total_karpet = 0;
    if (!isset($total_nilai)) $total_nilai = 0;
    if (!isset($total_promo)) $total_promo = 0;
    if (!isset($total_nonpromo)) $total_nonpromo = 0;
    ?>
    <div class="row mb-3">
        <div class="col-md-3 col-6">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <span class="badge bg-primary mb-2"><i class="ti ti-stack"></i> Total Karpet</span>
                    <h3 class="mb-0"><?= number_format($total_karpet) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <span class="badge bg-success mb-2"><i class="ti ti-cash"></i> Total Nilai Layanan</span>
                    <h3 class="mb-0">Rp <?= number_format($total_nilai, 0, ',', '.') ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6 mt-3 mt-md-0">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <span class="badge bg-info text-dark mb-2"><i class="ti ti-discount-2"></i> Total Promo</span>
                    <h3 class="mb-0">Rp <?= number_format($total_promo, 0, ',', '.') ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6 mt-3 mt-md-0">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <span class="badge bg-secondary mb-2"><i class="ti ti-receipt"></i> Total Non-Promo</span>
                    <h3 class="mb-0">Rp <?= number_format($total_nonpromo, 0, ',', '.') ?></h3>
                </div>
            </div>
        </div>
    </div>
    <!-- END Statistik Ringkas -->

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0"><i class="ti ti-table"></i> Data Layanan Karpet</h5>
            <div>
                <a href="laporan/export_layanan_karpet.php?tgl_awal=<?= $tgl_awal ?>&tgl_akhir=<?= $tgl_akhir ?>" class="btn btn-success btn-sm me-2" target="_blank" data-bs-toggle="tooltip" title="Export ke Excel"><i class="ti ti-file-export"></i> Excel</a>
                <a href="laporan/print_layanan_karpet.php?tgl_awal=<?= $tgl_awal ?>&tgl_akhir=<?= $tgl_akhir ?>" class="btn btn-primary btn-sm" target="_blank" data-bs-toggle="tooltip" title="Print laporan"><i class="ti ti-printer"></i> Print</a>
            </div>
        </div>
        <div class="card-body">
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Filter layanan karpet
                        $layanan_karpet_selected = isset($_POST['layanan_karpet']) ? $_POST['layanan_karpet'] : '';
                        $where_layanan = '';
                        if ($layanan_karpet_selected) {
                            $where_layanan = "l.id_layanan = '" . mysqli_real_escape_string($konek, $layanan_karpet_selected) . "'";
                        } else {
                            $where_layanan = "l.nama_layanan LIKE '%Karpet%'";
                        }
                        $sql = "
                            SELECT 
                                p.*,
                                pl.nama_pelanggan,
                                pl.nomor_telepon,
                                pl.alamat,
                                l.nama_layanan,
                                dp.kuantitas,
                                dp.harga_saat_pesan as harga,
                                dp.panjang_karpet,
                                dp.lebar_karpet
                                dp.subtotal_item as subtotal,
                                pg.nama_lengkap as penerima
                            FROM pesanan p
                            JOIN detail_pesanan dp ON p.id_pesanan = dp.id_pesanan
                            JOIN layanan l ON dp.id_layanan = l.id_layanan
                            JOIN pelanggan pl ON p.id_pelanggan = pl.id_pelanggan
                            LEFT JOIN pengguna pg ON p.id_pengguna_penerima = pg.id_pengguna
                            WHERE $where_layanan
                            AND DATE(p.tanggal_masuk) BETWEEN '$tgl_awal' AND '$tgl_akhir'
                            ORDER BY p.tanggal_masuk DESC
                        ";
                        $query = mysqli_query($konek, $sql);
                        if (!$query) {
                            echo "<tr><td colspan='11'>Error executing query: " . mysqli_error($konek) . "</td></tr>";
                        } else {
                            $no = 1;
                            $total_karpet = 0;
                            $total_nilai = 0;
                            $total_promo = 0;
                            $total_nonpromo = 0;
                            while($data = mysqli_fetch_array($query)) {
    echo "<tr>";
    echo "<td>" . $no++ . "</td>";
    echo "<td>" . htmlspecialchars($data['nomor_invoice']) . "</td>";
    echo "<td>" . date('d/m/Y H:i', strtotime($data['tanggal_masuk'])) . "</td>";
    echo "<td>" . ($data['tanggal_selesai_aktual'] ? date('d/m/Y H:i', strtotime($data['tanggal_selesai_aktual'])) : '-') . "</td>";
    echo "<td>" . ($data['tanggal_diambil'] ? date('d/m/Y H:i', strtotime($data['tanggal_diambil'])) : '-') . "</td>";
    echo "<td>" . htmlspecialchars($data['nama_pelanggan']) . "</td>";
    echo "<td>" . htmlspecialchars($data['nama_layanan']) . "</td>";
    echo "<td>" . (isset($data['panjang_karpet']) && isset($data['lebar_karpet']) ? htmlspecialchars($data['panjang_karpet']) . " x " . htmlspecialchars($data['lebar_karpet']) . " m" : '-') . "</td>";
    echo "<td>" . number_format($data['kuantitas']) . "</td>";
    echo "</tr>";
    $total_karpet += $data['kuantitas'];
    // Nilai promo/nonpromo tetap dihitung jika diperlukan di statistik atas
}
                        }
                        ?>
                    </tbody>
                    <tfoot>
                            <tr>
                                <td colspan="7" align="center"><strong>Total</strong></td>
                                <td><strong>Rp <?= number_format($total_nilai, 0, ',', '.') ?></strong></td>
                                <td colspan="3"></td>
                            </tr>
                            <tr>
                                <td colspan="7" align="right"><strong>Total Promo</strong></td>
                                <td><strong class="text-success">Rp <?= number_format(isset($total_promo) ? $total_promo : 0, 0, ',', '.') ?></strong></td>
                                <td colspan="3"></td>
                            </tr>
                            <tr>
                                <td colspan="7" align="right"><strong>Total Non-Promo</strong></td>
                                <td><strong class="text-primary">Rp <?= number_format(isset($total_nonpromo) ? $total_nonpromo : 0, 0, ',', '.') ?></strong></td>
                                <td colspan="3"></td>
                            </tr>
                        </tfoot>
                </table>
            </div>
        </div>
    </div>
    <!-- Modal Detail -->
    <div class="modal fade" id="detailModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Layanan Karpet</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detailContent">
                    <!-- Content will be loaded here -->
                </div>
            </div>
        </div>
    </div>
    <!-- DataTables & Bootstrap 5 JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script>
    $(document).ready(function() {
        // Inisialisasi DataTables
        $('#layananKarpetTable').DataTable({
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/id.json'
            },
            pageLength: 10,
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, 'Semua']]
        });
        // Inisialisasi tooltip Bootstrap 5
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.forEach(function (tooltipTriggerEl) {
            new bootstrap.Tooltip(tooltipTriggerEl);
        });
        // Event delegation tombol detail
        $('#layananKarpetTable').on('click', '.btn-detail', function() {
            var id_pesanan = $(this).data('id');
            $('#detailContent').html('<p class="text-center">Memuat detail ...</p>');
            var modal = new bootstrap.Modal(document.getElementById('detailModal'));
            modal.show();
            $.ajax({
                url: 'laporan/detail_layanan_karpet.php',
                type: 'POST',
                data: {
                    id_pesanan: id_pesanan,
                    tgl_awal: '<?= $tgl_awal ?>',
                    tgl_akhir: '<?= $tgl_akhir ?>'
                },
                success: function(response) {
                    $('#detailContent').html(response);
                },
                error: function() {
                    $('#detailContent').html('<p class="text-center text-danger">Gagal memuat detail. Silakan coba lagi.</p>');
                }
            });
        });
    });
    </script>