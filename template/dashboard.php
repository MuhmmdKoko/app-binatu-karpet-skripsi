<?php

// Set zona waktu ke Asia/Makassar
date_default_timezone_set('Asia/Makassar');

// Ambil tanggal hari ini
$today = date('Y-m-d');

// 1. Pesanan Masuk Hari Ini
$query_masuk_hari_ini = "SELECT COUNT(id_pesanan) as total FROM pesanan WHERE DATE(tanggal_masuk) = '$today'";
$result_masuk_hari_ini = mysqli_query($konek, $query_masuk_hari_ini);
$data_masuk_hari_ini = mysqli_fetch_assoc($result_masuk_hari_ini);
$pesanan_hari_ini = $data_masuk_hari_ini['total'];

// 2. Pesanan Jatuh Tempo Hari Ini
$query_jatuh_tempo = "SELECT COUNT(id_pesanan) as total FROM pesanan WHERE DATE(tanggal_estimasi_selesai) = '$today' AND status_pesanan_umum NOT IN ('Selesai', 'Diambil', 'Dibatalkan')";
$result_jatuh_tempo = mysqli_query($konek, $query_jatuh_tempo);
$data_jatuh_tempo = mysqli_fetch_assoc($result_jatuh_tempo);
$jatuh_tempo_hari_ini = $data_jatuh_tempo['total'];

// 3. Pesanan Terlambat
$query_terlambat = "SELECT COUNT(id_pesanan) as total FROM pesanan WHERE DATE(tanggal_estimasi_selesai) < '$today' AND status_pesanan_umum NOT IN ('Selesai', 'Diambil', 'Dibatalkan')";
$result_terlambat = mysqli_query($konek, $query_terlambat);
$data_terlambat = mysqli_fetch_assoc($result_terlambat);
$pesanan_terlambat = $data_terlambat['total'];

// 4. Pendapatan Hari Ini
$query_pendapatan = "SELECT SUM(jumlah_bayar) as total FROM log_pembayaran WHERE DATE(tanggal_bayar) = '$today'";
$result_pendapatan = mysqli_query($konek, $query_pendapatan);
$data_pendapatan = mysqli_fetch_assoc($result_pendapatan);
$pendapatan_hari_ini = $data_pendapatan['total'] ?? 0;
?>
<body style="padding-top: 100px;">
<div class="container-fluid">

    <!-- Ringkasan Operasional Harian -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6">
            <a href="index.php?page=pesanan_read&filter=hari_ini" class="text-decoration-none">
                <div class="card bg-primary text-white shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="me-3"><i class="ti ti-clipboard-text fs-6"></i></div>
                            <div>
                                <h6 class="card-title text-white">Pesanan Hari Ini</h6>
                                <h4 class="mb-0 text-white"><?= $pesanan_hari_ini ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-lg-3 col-md-6">
            <a href="index.php?page=pesanan_read&filter=jatuh_tempo" class="text-decoration-none">
                <div class="card bg-warning text-white shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="me-3"><i class="ti ti-clock-hour-4 fs-6"></i></div>
                            <div>
                                <h6 class="card-title text-white">Jatuh Tempo Hari Ini</h6>
                                <h4 class="mb-0 text-white"><?= $jatuh_tempo_hari_ini ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-lg-3 col-md-6">
            <a href="index.php?page=pesanan_read&filter=terlambat" class="text-decoration-none">
                <div class="card bg-danger text-white shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="me-3"><i class="ti ti-alert-circle fs-6"></i></div>
                            <div>
                                <h6 class="card-title text-white">Pesanan Terlambat</h6>
                                <h4 class="mb-0 text-white"><?= $pesanan_terlambat ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card bg-success text-white shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="me-3"><i class="ti ti-cash fs-6"></i></div>
                        <div>
                            <h6 class="card-title text-white">Pendapatan Hari Ini</h6>
                            <h4 class="mb-0 text-white">Rp <?= number_format($pendapatan_hari_ini, 0, ',', '.') ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!--  Row 1 -->
    <div class="row">
        <div class="col-lg-8 d-flex align-items-strech">
            <div class="card w-100">
                <div class="card-body">
                    <div class="d-sm-flex d-block align-items-center justify-content-between mb-9">
                        <div class="mb-3 mb-sm-0">
                            <h5 class="card-title fw-semibold">Ringkasan Total Transaksi Perbulan Tahun <span id="currentYear"></span></h5>
                        </div>
                    </div>
                    <div id="chart"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title fw-semibold mb-4">Layanan Terpopuler</h5>
                            <div id="layanan-populer-chart"></div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title fw-semibold mb-4">Komposisi Status Pembayaran</h5>
                            <div id="status-pembayaran-chart" class="d-flex justify-content-center"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="/app-binatu-skripsi/assets/libs/jquery/dist/jquery.min.js"></script>
<script src="/app-binatu-skripsi/assets/libs/apexcharts/dist/apexcharts.min.js"></script>
<script src="/app-binatu-skripsi/assets/js/dashboard_laundry.js"></script>
</body>