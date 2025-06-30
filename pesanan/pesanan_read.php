<?php
// --- Daftar Pesanan: Tabel, Search, Link ke Detail ---
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Admin','Karyawan'])) {
    echo '<script>alert("Akses ditolak");window.location="../index.php";</script>';
    exit;
}
include "pengaturan/koneksi.php";
include "../template/header.php";

// Search and Filter
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$filter = isset($_GET['filter']) ? $_GET['filter'] : '';

// Menggunakan nama kolom yang benar: status_pesanan_umum
$sql = "SELECT 
            p.id_pesanan, p.nomor_invoice, p.tanggal_masuk, p.tanggal_estimasi_selesai, 
            p.status_pesanan_umum, p.status_pembayaran, p.total_harga_keseluruhan,
            pl.nama_pelanggan, pl.nomor_telepon 
        FROM pesanan p 
        JOIN pelanggan pl ON p.id_pelanggan = pl.id_pelanggan ";
$where_clauses = [];
$page_title = "Daftar Pesanan";

if ($q) {
    $q_esc = mysqli_real_escape_string($konek, $q);
    $where_clauses[] = "(p.nomor_invoice LIKE '%$q_esc%' OR pl.nama_pelanggan LIKE '%$q_esc%' OR pl.nomor_telepon LIKE '%$q_esc%')";
}

if ($filter) {
    date_default_timezone_set('Asia/Makassar');
    $today = date('Y-m-d');
    switch ($filter) {
        case 'hari_ini':
            $where_clauses[] = "DATE(p.tanggal_masuk) = '$today'";
            $page_title = "Pesanan Masuk Hari Ini";
            break;
        case 'jatuh_tempo':
            $where_clauses[] = "DATE(p.tanggal_estimasi_selesai) = '$today' AND p.status_pesanan_umum NOT IN ('Selesai', 'Diambil', 'Dibatalkan')";
            $page_title = "Pesanan Jatuh Tempo Hari Ini";
            break;
        case 'terlambat':
            $where_clauses[] = "DATE(p.tanggal_estimasi_selesai) < '$today' AND p.status_pesanan_umum NOT IN ('Selesai', 'Diambil', 'Dibatalkan')";
            $page_title = "Pesanan Terlambat";
            break;
    }
}

if (!empty($where_clauses)) {
    $sql .= "WHERE " . implode(' AND ', $where_clauses);
}
$sql .= "ORDER BY p.id_pesanan DESC LIMIT 50";
$res = mysqli_query($konek, $sql);
?>
<div class="container-fluid mt-4">
    <h3 class="mb-3"><?= $page_title ?></h3>
    <?php if ($filter): ?>
        <div class="mb-3">
            <a href="?page=pesanan_read" class="btn btn-secondary btn-sm">&larr; Kembali ke Semua Pesanan</a>
        </div>
    <?php endif; ?>
    <form class="mb-3" id="formCariPesanan" method="get">
        <input type="hidden" name="page" value="pesanan_read">
        <div class="input-group" style="max-width:400px;">
            <input type="text" class="form-control" id="searchPesanan" name="q" placeholder="Cari invoice/nama/telepon..." value="<?= htmlspecialchars($q) ?>">
            <button class="btn btn-primary" type="submit">Cari</button>
            <a href="?page=pesanan_tambah" class="btn btn-success">+ Pesanan Baru</a>
        </div>
    </form>
    <div class="table-responsive">
    <table class="table table-bordered table-striped">
        <thead class="table-light">
            <tr>
                <th>No</th>
                <th>Invoice</th>
                <th>Pelanggan</th>
                <th>Masuk</th>
                <th>Estimasi Selesai</th>
                <th>Status</th>
                <th>Pembayaran</th>
                <th>Total</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody id="tabelPesanan">
        <?php $no=1; while($row = mysqli_fetch_assoc($res)): ?>
            <tr>
                <td><?= $no++ ?></td>
                <td><?= htmlspecialchars($row['nomor_invoice']) ?></td>
                <td><?= htmlspecialchars($row['nama_pelanggan']) ?><br><small><?= htmlspecialchars($row['nomor_telepon']) ?></small></td>
                <td><?= htmlspecialchars($row['tanggal_masuk']) ?></td>
                <td><?= date('Y-m-d H:i:s', strtotime($row['tanggal_estimasi_selesai'])) ?></td>
                <td><span class="badge bg-info text-dark"><?= htmlspecialchars($row['status_pesanan_umum']) ?></span></td>
                <td><span class="badge bg-<?= $row['status_pembayaran']==='Lunas'?'success':'warning' ?> text-dark"><?= htmlspecialchars($row['status_pembayaran']) ?></span></td>
                <td>
<?php
// Ambil diskon dan total_setelah_diskon jika ada
$id_pesanan = $row['id_pesanan'];
$q_diskon = mysqli_query($konek, "SELECT diskon, total_setelah_diskon FROM pesanan WHERE id_pesanan=$id_pesanan LIMIT 1");
$diskon_row = mysqli_fetch_assoc($q_diskon);
if (!empty($diskon_row['diskon']) && $diskon_row['diskon'] > 0) {
    echo 'Rp' . number_format($diskon_row['total_setelah_diskon'], 2, ',', '.');
} else {
    echo 'Rp' . number_format($row['total_harga_keseluruhan'], 2, ',', '.');
}
?>
                </td>
                <td>
                    <a href="?page=pesanan_detail&id=<?= $row['id_pesanan'] ?>" class="btn btn-info btn-sm">Detail</a>
                    <a href="?page=pesanan_cetak_nota&id=<?= $row['id_pesanan'] ?>" target="_blank" class="btn btn-secondary btn-sm">Cetak Nota</a>
                    <a href="?page=pesanan_status_proses&id=<?= $row['id_pesanan'] ?>" class="btn btn-warning btn-sm">Status</a>
                </td>
            </tr>
        <?php endwhile; if($no==1): ?>
            <tr><td colspan="9" class="text-center text-muted">Data tidak ditemukan</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    </div>
</div>

<script src="assets/libs/jquery/dist/jquery.min.js"></script>
<script>
$(function(){
  // Live search saat mengetik
  $('#searchPesanan').on('keyup', function(){
    var q = $(this).val();
    $.get('pesanan/pesanan_table_search.php', {q: q}, function(data){
      $('#tabelPesanan').html(data);
    });
  });

  // Cegah submit form agar tidak reload halaman
  $('#formCariPesanan').on('submit', function(e){
    e.preventDefault();
    var q = $('#searchPesanan').val();
    $.get('pesanan/pesanan_table_search.php', {q: q}, function(data){
      $('#tabelPesanan').html(data);
    });
  });
});
</script>

