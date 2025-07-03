<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require "pengaturan/koneksi.php";
// include "../template/header.php";

// Cek akses user
if ($_SESSION['role'] != "Admin") {
    echo '<script>alert("Anda tidak memiliki akses ke halaman ini!");window.location.href="index.php";</script>';
    exit;
}
?>

<div class="container-fluid mt-4">
    <!-- Judul dan Deskripsi -->
    <div class="mb-4">
        <h3 class="mb-1">Laporan Penerima Pesanan</h3>
        <div class="text-muted">Daftar penerima pesanan dan statistik per periode</div>
    </div> 
        <!-- Filter Card -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0"><i class="ti ti-filter-check"></i> Filter Laporan</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="" class="row g-3 align-items-end" id="filterPenerimaForm">
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
                    <input type="date" id="tgl_awal" name="tgl_awal" class="form-control" value="<?= isset($tgl_awal) ? $tgl_awal : date('Y-m-01') ?>">
                </div>
                <div class="col-md-3">
                    <label for="tgl_akhir" class="form-label">Tanggal Akhir</label>
                    <input type="date" id="tgl_akhir" name="tgl_akhir" class="form-control" value="<?= isset($tgl_akhir) ? $tgl_akhir : date('Y-m-d') ?>">
                </div>
                <div class="col-md-3">
                    <label for="id_pengguna" class="form-label">Penerima Pesanan</label>
                    <select id="id_pengguna" name="id_pengguna" class="form-control">
                        <option value="">-- Semua Penerima --</option>
                        <?php
                        $query_pengguna = mysqli_query($konek, "SELECT * FROM pengguna ORDER BY nama_lengkap");
                        $id_pengguna_selected = isset($_POST['id_pengguna']) ? $_POST['id_pengguna'] : '';
                        while($pengguna = mysqli_fetch_array($query_pengguna)) {
                            $selected = ($id_pengguna_selected == $pengguna['id_pengguna']) ? 'selected' : '';
                            echo '<option value="'.htmlspecialchars($pengguna['id_pengguna']).'" '.$selected.'>'.htmlspecialchars($pengguna['nama_lengkap']).'</option>';
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
                    setTanggalByPeriode($('#filter_periode').val());
                    updateLaporanPenerima();
                    $('#filter_periode').on('change', function() {
                        setTanggalByPeriode(this.value);
                        $(this).closest('form').submit();
                    });
                    function updateLaporanPenerima() {
                        var tgl_awal = $('#tgl_awal').val();
                        var tgl_akhir = $('#tgl_akhir').val();
                        var id_pengguna = $('#id_pengguna').val();
                        $.ajax({
                            url: 'laporan/ajax_penerima_pesanan.php',
                            type: 'POST',
                            data: {
                                tgl_awal: tgl_awal,
                                tgl_akhir: tgl_akhir,
                                id_pengguna: id_pengguna
                            },
                            dataType: 'json',
                            success: function(resp) {
                                var tbody = '';
                                var no = 1;
                                var total_pesanan = 0;
                                var total_nilai = 0;
                                resp.data.forEach(function(row) {
                                    tbody += '<tr>' +
                                        '<td>' + (no++) + '</td>' +
                                        '<td>' + row.nama_lengkap + '</td>' +
                                        '<td>' + row.jumlah_pesanan.toLocaleString('id-ID') + '</td>' +
                                        '<td>Rp ' + row.total_nilai_pesanan.toLocaleString('id-ID') + '</td>' +
                                        '<td>Rp ' + row.rata_nilai_pesanan.toLocaleString('id-ID') + '</td>' +
                                        '<td><button type="button" class="btn btn-info btn-sm" onclick="showDetail(' + row.id_pengguna + ')"><i class="ti ti-eye"></i></button></td>' +
                                    '</tr>';
                                    total_pesanan += row.jumlah_pesanan;
                                    total_nilai += row.total_nilai_pesanan;
                                });
                                if (resp.data.length === 0) {
                                    tbody = '<tr><td colspan="6" class="text-center">Tidak ada data</td></tr>';
                                }
                                tbody += '<tr>' +
                                    '<td colspan="2" align="center"><strong>Total</strong></td>' +
                                    '<td><strong>' + total_pesanan.toLocaleString('id-ID') + '</strong></td>' +
                                    '<td><strong>Rp ' + total_nilai.toLocaleString('id-ID') + '</strong></td>' +
                                    '<td><strong>Rp ' + (total_pesanan > 0 ? (total_nilai / total_pesanan).toLocaleString('id-ID', {maximumFractionDigits:0}) : '0') + '</strong></td>' +
                                    '<td></td>' +
                                '</tr>';
                                $('#penerimaPesananTable tbody').html(tbody);
                                // Update ringkasan statistik dari response AJAX
                                $('#stat-total-penerima').text(resp.total_penerima.toLocaleString('id-ID'));
                                $('#stat-total-pesanan').text(resp.total_pesanan.toLocaleString('id-ID'));
                                $('#stat-total-nilai').text('Rp ' + resp.total_nilai.toLocaleString('id-ID'));
                                $('#stat-rata-per-penerima').text(resp.total_penerima > 0 ? (resp.rata_per_penerima).toLocaleString('id-ID', {maximumFractionDigits:1}) : '0.0');
                            }
                        });
                    }
                    $('#tgl_awal, #tgl_akhir, #id_pengguna, #filter_periode').on('change', function() {
                        if($('#filter_periode').val() === 'custom' || $(this).attr('id') === 'id_pengguna') {
                            updateLaporanPenerima();
                        }
                    });
                    // Inisialisasi awal
                    updateLaporanPenerima();
                });
                </script>
<script>
// Fungsi JS untuk detail modal penerima pesanan
function showDetail(id_pengguna) {
    var tgl_awal = $('#tgl_awal').val();
    var tgl_akhir = $('#tgl_akhir').val();
    $.ajax({
        url: 'laporan/detail_penerima_pesanan.php',
        type: 'POST',
        data: {
            id_pengguna: id_pengguna,
            tgl_awal: tgl_awal,
            tgl_akhir: tgl_akhir
        },
        success: function(response) {
            $('#detailContent').html(response);
            $('#detailModal').modal('show');
        }
    });
}
</script>
            </form>
        </div>
    </div>

            

            

            <div class="d-flex justify-content-end mb-2">
    <a href="#" class="btn btn-success btn-export-penerima me-2" target="_blank">
        <i class="ti ti-file-export"></i> Export Excel
    </a>
    <a href="#" id="btnPrintPenerima" class="btn btn-primary btn-print-penerima" target="_blank">
    <i class="ti ti-printer"></i> Print
</a>
<script>
$('#btnPrintPenerima').on('click', function(e) {
    e.preventDefault();
    var tgl_awal = $('#tgl_awal').val();
    var tgl_akhir = $('#tgl_akhir').val();
    var id_pengguna = $('#id_pengguna').val();
    var filter_periode = $('#filter_periode').val();
    var url = 'laporan/print_penerima_pesanan.php?tgl_awal=' + encodeURIComponent(tgl_awal)
        + '&tgl_akhir=' + encodeURIComponent(tgl_akhir)
        + '&id_pengguna=' + encodeURIComponent(id_pengguna)
        + '&filter_periode=' + encodeURIComponent(filter_periode);
    window.open(url, '_blank');
});
</script>
</div>
<div class="table-responsive">
    <table id="penerimaPesananTable" class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Penerima</th>
                            <th>Jumlah Pesanan</th>
                            <th>Total Nilai Pesanan</th>
                            <th>Rata-rata Nilai Pesanan</th>
                            <th>Detail</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td colspan="6" class="text-center">Tidak ada data</td></tr>
                    </tbody>
                </table>
            </div>

            <!-- Statistik Ringkas -->
    <div class="row mb-3">
        <div class="col-md-3 col-6 mb-2">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <span class="badge bg-primary mb-2"><i class="ti ti-user"></i> Total Penerima</span>
                    <h3 class="mb-0" id="stat-total-penerima">0</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-2">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <span class="badge bg-success mb-2"><i class="ti ti-table"></i> Total Pesanan</span>
                    <h3 class="mb-0" id="stat-total-pesanan">0</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-2">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <span class="badge bg-info mb-2"><i class="ti ti-currency-dollar"></i> Total Nilai</span>
                    <h3 class="mb-0" id="stat-total-nilai">Rp 0</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-2">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <span class="badge bg-warning mb-2"><i class="ti ti-chart-bar"></i> Rata-rata per Penerima</span>
                    <h3 class="mb-0" id="stat-rata-per-penerima">0.0</h3>
                </div>
            </div>
        </div>
    </div>
    <!-- END Statistik Ringkas -->

            <!-- Modal Detail -->
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

        </div>
    </div>
</div> 