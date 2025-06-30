<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Admin','Karyawan'])) {
    echo '<script>alert("Akses ditolak");window.location="index.php?page=dashboard_read";</script>';
    exit;
}

include "pengaturan/koneksi.php";
include "../template/header.php";

// Search logic
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$sql = "SELECT * FROM layanan ";
if ($q) {
    $q_esc = mysqli_real_escape_string($konek, $q);
    $sql .= "WHERE nama_layanan LIKE '%$q_esc%' ";
}
$sql .= "ORDER BY id_layanan DESC";
$result = mysqli_query($konek, $sql);
?>

<div class="container-fluid mt-4">
    <h3 class="mb-3">Daftar Layanan</h3>

    <!-- Search Form -->
    <form class="row mb-3" id="formCariLayanan" method="get" action="?page=layanan_read">
        <div class="col-md-4">
            <input type="text" class="form-control" id="searchLayanan" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Cari nama layanan...">
        </div>
        <div class="col-md-2">
            <button class="btn btn-primary" type="submit">Cari</button>
        </div>
        <div class="col-md-6 text-end">
            <a href="?page=layanan_tambah" class="btn btn-success">+ Tambah Layanan</a>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-bordered table-hover">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nama Layanan</th>
                    <th>Harga per Unit</th>
                    <th>Satuan</th>
                    <th>Estimasi Waktu (Hari)</th>
                    <th>Minimal Order</th>
                    <th>Minimal Kuantitas</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody id="tabelLayanan">
                <?php if(mysqli_num_rows($result) > 0): ?>
                    <?php while($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?= $row['id_layanan']; ?></td>
                            <td><?= htmlspecialchars($row['nama_layanan']); ?></td>
                            <td>Rp <?= number_format($row['harga_per_unit'], 0, ',', '.'); ?></td>
                            <td><?= htmlspecialchars($row['satuan']); ?></td>
                            <td><?= htmlspecialchars($row['estimasi_waktu_hari']); ?></td>
                            <td><?= $row['minimal_order_aktif'] ? '<span class="badge bg-success">Ya</span>' : '<span class="badge bg-secondary">Tidak</span>'; ?></td>
                            <td><?= $row['minimal_order_aktif'] ? htmlspecialchars($row['minimal_order_kuantitas']) . ' ' . htmlspecialchars($row['satuan']) : '-'; ?></td>
                            <td>
                                <a href="?page=layanan_edit&id=<?= $row['id_layanan']; ?>" class="btn btn-warning btn-sm">Edit</a>
                                <a href="?page=layanan_delete&id=<?= $row['id_layanan']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Apakah Anda yakin ingin menghapus layanan ini?');">Hapus</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center">Layanan tidak ditemukan.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="assets/libs/jquery/dist/jquery.min.js"></script>
<script>
$(function(){
  // Live search saat mengetik
  $('#searchLayanan').on('keyup', function(){
    var q = $(this).val();
    $.get('layanan/layanan_table_search.php', {q: q}, function(data){
      $('#tabelLayanan').html(data);
    });
  });

  // Cegah submit form agar tidak reload halaman
  $('#formCariLayanan').on('submit', function(e){
    e.preventDefault();
    var q = $('#searchLayanan').val();
    $.get('layanan/layanan_table_search.php', {q: q}, function(data){
      $('#tabelLayanan').html(data);
    });
  });
});
</script>

