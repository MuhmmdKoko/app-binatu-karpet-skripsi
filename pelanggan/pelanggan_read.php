<?php
// Hak akses: Admin & Karyawan
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Admin','Karyawan'])) {
    echo '<script>alert("Akses ditolak");window.location="../index.php";</script>';
    exit;
}
include "pengaturan/koneksi.php";
include "../template/header.php";

// Search
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$sql = "SELECT * FROM pelanggan ";
if ($q) {
    $q_esc = mysqli_real_escape_string($konek, $q);
    $sql .= "WHERE nama_pelanggan LIKE '%$q_esc%' OR nomor_telepon LIKE '%$q_esc%' ";
}
$sql .= "ORDER BY id_pelanggan DESC LIMIT 30";
$res = mysqli_query($konek, $sql);
?>

<div class="container-fluid mt-4">
    <h3 class="mb-3">Daftar Pelanggan</h3>
    <form class="row mb-3" id="formCariPelanggan" method="get" action="?page=pelanggan_read">
        <div class="col-md-4">
            <input type="text" class="form-control" id="searchPelanggan" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Cari nama/telepon...">
        </div>
        <div class="col-md-2">
            <button class="btn btn-primary" type="submit">Cari</button>
        </div>
        <div class="col-md-6 text-end">
            <a href="?page=pelanggan_tambah" class="btn btn-success">+ Tambah Pelanggan</a>
        </div>
    </form>
    <table class="table table-bordered table-hover">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nama</th>
                <th>No. Telepon</th>
                <th>Alamat</th>
                <th>Email</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody id="tabelPelanggan">
            <?php while($row = mysqli_fetch_assoc($res)): ?>
            <tr>
                <td><?= $row['id_pelanggan'] ?></td>
                <td><?= htmlspecialchars($row['nama_pelanggan']) ?></td>
                <td><?= htmlspecialchars($row['nomor_telepon']) ?></td>
                <td><?= htmlspecialchars($row['alamat']) ?></td>
                <td><?= htmlspecialchars($row['email']) ?></td>
                <td>
                    <a href="?page=pelanggan_detail&id=<?= $row['id_pelanggan'] ?>" class="btn btn-info btn-sm">Detail</a>
                    <a href="?page=pelanggan_edit&id=<?= $row['id_pelanggan'] ?>" class="btn btn-warning btn-sm">Edit</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
<!-- Pastikan jQuery sudah dimuat -->
<script src="assets/libs/jquery/dist/jquery.min.js"></script>
<script>
$(function(){
  // Live search saat mengetik
  $('#searchPelanggan').on('keyup', function(){
    var q = $(this).val();
    $.get('pelanggan/pelanggan_table_search.php', {q: q}, function(data){
      $('#tabelPelanggan').html(data);
    });
  });
  // Cegah submit form agar tidak reload
  $('#formCariPelanggan').on('submit', function(e){
    e.preventDefault();
    var q = $('#searchPelanggan').val();
    $.get('pelanggan/pelanggan_table_search.php', {q: q}, function(data){
      $('#tabelPelanggan').html(data);
    });
  });
});
</script>
<?php include "template/footer.php"; ?>
