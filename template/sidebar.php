<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Modernize Free</title>
  <link rel="shortcut icon" type="image/png" href="assets/images/logos/figure-svgrepo-com.svg" />
  <link rel="stylesheet" href="assets/css/styles.min.css" />
<link rel="stylesheet" href="assets/css/logo-custom.css" />
  <script src="assets/libs/jquery/dist/jquery.min.js"></script>
  <script src="assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
</head>

<body>
  <!--  Body Wrapper -->
  <div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full"
    data-sidebar-position="fixed" data-header-position="fixed">
    <!-- Sidebar Start -->
    <aside class="left-sidebar">
      <!-- Sidebar scroll-->
      <div>
        <div class="brand-logo d-flex align-items-center justify-content-between">
          <a href="index.php?page=dashboard_read" class="text-nowrap logo-img d-block px-3 py-2">
  <img src="assets/images/logos/berkat laundry.png" class="img-fluid sidebar-logo" alt="Berkat Laundry Logo" />
</a>
          <div class="close-btn d-xl-none d-block sidebartoggler cursor-pointer" id="sidebarCollapse">
            <i class="ti ti-x fs-8"></i>
          </div>
        </div>
        <!-- Sidebar navigation-->
        <nav class="sidebar-nav scroll-sidebar" data-simplebar="">
          <ul id="sidebarnav">
          <li class="nav-small-cap">
              <i class="ti ti-dots nav-small-cap-icon fs-4"></i>
              <span class="hide-menu">Menu</span>
            </li>
          <li class="sidebar-item">
              <a class="sidebar-link" href="?page=dashboard_read" aria-expanded="false">
                <span>
                  <i class="ti ti-layout-dashboard"></i>
                </span>
                <span class="hide-menu">Dashboard</span>
              </a>
            </li>
            <li class="nav-small-cap">
              <i class="ti ti-dots nav-small-cap-icon fs-4"></i>
              <span class="hide-menu">Master Data</span>
            </li>
            <li class="sidebar-item">
              <a class="sidebar-link" href="?page=pelanggan_read" aria-expanded="false">
                <span>
                  <i class="ti ti-users"></i>
                </span>
                <span class="hide-menu">Pelanggan</span>
              </a>
            </li>
            <li class="sidebar-item">
              <a class="sidebar-link" href="?page=layanan_read" aria-expanded="false">
                <span>
                  <i class="ti ti-layers-intersect"></i>
                </span>
                <span class="hide-menu">Daftar Layanan</span>
              </a>
            </li>
            <li class="sidebar-item">
              <a class="sidebar-link" href="?page=pesanan_read" aria-expanded="false">
                <span>
                  <i class="ti ti-file-invoice"></i>
                </span>
                <span class="hide-menu">Daftar Pesanan</span>
              </a>
            </li>
            <li class="sidebar-item">
              <a class="sidebar-link" href="?page=promosi_read" aria-expanded="false">
                <span>
                  <i class="ti ti-broadcast"></i>
                </span>
                <span class="hide-menu">Promosi</span>
              </a>
            </li>
            
            <li class="nav-small-cap">
              <i class="ti ti-dots nav-small-cap-icon fs-4"></i>
              <span class="hide-menu">Transaksi</span>
            </li>
            <li class="sidebar-item">
              <a class="sidebar-link" href="?page=pesanan_tambah" aria-expanded="false">
                <span>
                  <i class="ti ti-file-plus"></i>
                </span>
                <span class="hide-menu">Pesanan Baru</span>
              </a>
            </li>
            
            <li class="nav-small-cap">
              <i class="ti ti-dots nav-small-cap-icon fs-4"></i>
              <span class="hide-menu">Laporan</span>
            </li>
            <li class="sidebar-item">
              <a class="sidebar-link" href="?page=laporan_penerima_pesanan_read" aria-expanded="false">
                <span>
                  <i class="ti ti-user-check"></i>
                </span>
                <span class="hide-menu">Laporan Penerima Pesanan</span>
              </a>
            </li>
            <li class="sidebar-item">
              <a class="sidebar-link" href="?page=laporan_status_pesanan_read" aria-expanded="false">
                <span>
                  <i class="ti ti-list-check"></i>
                </span>
                <span class="hide-menu">Laporan Status Pesanan</span>
              </a>
            </li>
            <li class="sidebar-item">
              <a class="sidebar-link" href="?page=laporan_pendapatan_read" aria-expanded="false">
                <span>
                  <i class="ti ti-report-money"></i>
                </span>
                <span class="hide-menu">Laporan Pendapatan</span>
              </a>
            </li>
            <li class="sidebar-item">
              <a class="sidebar-link" href="?page=laporan_pelanggan_loyal_read" aria-expanded="false">
                <span>
                  <i class="ti ti-users"></i>
                </span>
                <span class="hide-menu">Laporan Pelanggan Loyal</span>
              </a>
            </li>
            <li class="sidebar-item">
              <a class="sidebar-link" href="?page=laporan_layanan_karpet_read" aria-expanded="false">
                <span>
                  <i class="ti ti-wash"></i>
                </span>
                <span class="hide-menu">Laporan Layanan Karpet</span>
              </a>
            </li>
            <li class="sidebar-item">
              <a class="sidebar-link" href="?page=laporan_kinerja_karyawan_read" aria-expanded="false">
                <span>
                  <i class="ti ti-report-analytics"></i>
                </span>
                <span class="hide-menu">Laporan Kinerja Karyawan</span>
              </a>
            </li>
            <li class="sidebar-item">
              <a class="sidebar-link" href="?page=laporan_analisis_waktu_read" aria-expanded="false">
                <span>
                  <i class="ti ti-chart-bar"></i>
                </span>
                <span class="hide-menu">Laporan Analisis Waktu</span>
              </a>
            </li>
            <!-- <li class="sidebar-item">
              <a class="sidebar-link" href="?page=laporan_notifikasi_read" aria-expanded="false">
                <span>
                  <i class="ti ti-bell"></i>
                </span>
                <span class="hide-menu">Laporan Notifikasi</span>
              </a>
            </li> -->
            <li class="sidebar-item">
              <a class="sidebar-link" href="?page=laporan_promosi_read" aria-expanded="false">
                <span>
                  <i class="ti ti-ticket"></i>
                </span>
                <span class="hide-menu">Laporan Promosi</span>
              </a>
            </li>
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] == "Admin"): ?>
            <li class="nav-small-cap">
              <i class="ti ti-dots nav-small-cap-icon fs-4"></i>
              <span class="hide-menu">Pengguna</span>
            </li>
            <li class="sidebar-item">
              <a class="sidebar-link" href="index.php?page=pengguna_read" aria-expanded="false">
                <span>
                  <i class="ti ti-user"></i>
                </span>
                <span class="hide-menu">Pengguna</span>
              </a>
            </li>
            <?php endif; ?>
          </ul>
        </nav>
        <!-- End Sidebar navigation -->
      </div>
      <!-- End Sidebar scroll-->
    </aside>
    <!--  Sidebar End -->