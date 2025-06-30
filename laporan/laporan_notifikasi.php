<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include 'pengaturan/koneksi.php';

// Cek akses user
if ($_SESSION['role'] != "Admin") {
    echo '<script>alert("Anda tidak memiliki akses ke halaman ini!");window.location.href="../index.php";</script>';
    exit;
}
// Laporan Notifikasi dinonaktifkan sementara sesuai permintaan user.

?>
<div class="container-fluid mt-4">
    <div class="alert alert-warning mt-5 text-center">
        <h4>Laporan Notifikasi Dinonaktifkan</h4>
        <p>Fitur laporan notifikasi saat ini dinonaktifkan sesuai permintaan. Silakan hubungi admin jika ingin mengaktifkan kembali.</p>
    </div>
</div>
    <!-- DataTables & Bootstrap 5 JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#notifikasiTable').DataTable({
                "language": {
                    "url": "https://cdn.datatables.net/plug-ins/1.13.6/i18n/id.json"
                },
                "pageLength": 10,
                "lengthMenu": [ [10, 25, 50, -1], [10, 25, 50, "Semua"] ]
            });
            // Aktifkan tooltip Bootstrap
            const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
            [...tooltipTriggerList].map(el => new bootstrap.Tooltip(el));
        });
        function exportToExcel() {
            window.location.href = '/app-binatu-skripsi/laporan/export_notifikasi.php?tgl_awal=<?= $start_date ?>&tgl_akhir=<?= $end_date ?>&tipe_channel=<?= urlencode($tipe_channel) ?>&filter_by=<?= urlencode($filter_by) ?>';
        }
        function exportToPDF() {
            window.location.href = '/app-binatu-skripsi/laporan/print_notifikasi.php?tgl_awal=<?= $start_date ?>&tgl_akhir=<?= $end_date ?>&tipe_channel=<?= urlencode($tipe_channel) ?>&filter_by=<?= urlencode($filter_by) ?>';
        }
    </script>
    <style>
        .modal-content {
            background: #fff !important;
            box-shadow: 0 0 40px rgba(0,0,0,0.2);
        }
        .modal-backdrop {
            opacity: 0.5 !important;
        }
    </style>