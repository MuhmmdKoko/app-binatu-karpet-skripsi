<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Admin','Karyawan'])) {
    echo '<script>alert("Akses ditolak");window.location="../index.php";</script>';
    exit;
}
include "pengaturan/koneksi.php";
include "../template/header.php";

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo '<script>alert("ID pelanggan tidak valid");window.location="?page=pelanggan_read";</script>';
    exit;
}
$id = (int)$_GET['id'];

// Ambil data pelanggan lama
$res = mysqli_query($konek, "SELECT * FROM pelanggan WHERE id_pelanggan=$id");
if (!$row = mysqli_fetch_assoc($res)) {
    echo '<script>alert("Data pelanggan tidak ditemukan");window.location="?page=pelanggan_read";</script>';
    exit;
}

$err = $sukses = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama_pelanggan']);
    $telp = trim($_POST['nomor_telepon']);
    $id_telegram = trim($_POST['id_telegram']);
    $alamat = trim($_POST['alamat']);
    $email = trim($_POST['email']);
    $catatan = trim($_POST['catatan']);
    if ($nama=='' || $telp=='') {
        $err = 'Nama dan nomor telepon wajib diisi!';
    } else {
        // Cek duplikasi nomor telepon kecuali milik sendiri
        $cek = mysqli_query($konek, "SELECT id_pelanggan FROM pelanggan WHERE nomor_telepon='$telp' AND id_pelanggan!=$id");
        if (mysqli_num_rows($cek)>0) {
            $err = 'Nomor telepon sudah terdaftar pada pelanggan lain!';
        } else {
            $q = mysqli_query($konek, "UPDATE pelanggan SET nama_pelanggan='$nama', nomor_telepon='$telp', id_telegram='$id_telegram', alamat='$alamat', email='$email', catatan='$catatan' WHERE id_pelanggan=$id");
            if ($q) {
                $sukses = 'Data pelanggan berhasil diperbarui!';
                echo '<script>alert("Data berhasil diupdate");window.location="?page=pelanggan_read";</script>';
                exit;
            } else {
                $err = 'Gagal mengupdate pelanggan.';
            }
        }
    }
}
?>

<div class="container-fluid mt-4">
    <h3 class="mb-3">Edit Data Pelanggan</h3>
    <?php if($err): ?><div class="alert alert-danger"><?= $err ?></div><?php endif; ?>
    <?php if($sukses): ?><div class="alert alert-success"><?= $sukses ?></div><?php endif; ?>
    <form method="post">
        <div class="mb-3">
            <label>Nama Pelanggan *</label>
            <input type="text" name="nama_pelanggan" class="form-control" value="<?= htmlspecialchars($row['nama_pelanggan']) ?>" required>
        </div>
        <div class="mb-3">
            <label>Nomor Telepon (WA) *</label>
            <input type="text" name="nomor_telepon" class="form-control" value="<?= htmlspecialchars($row['nomor_telepon']) ?>" required>
        </div>
        <div class="mb-3">
            <label>ID Telegram</label>
            <input type="text" name="id_telegram" class="form-control" value="<?= htmlspecialchars($row['id_telegram']) ?>">
        </div>
        <div class="mb-3">
            <label>Alamat</label>
            <textarea name="alamat" class="form-control"><?= htmlspecialchars($row['alamat']) ?></textarea>
        </div>
        <div class="mb-3">
            <label>Email</label>
            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($row['email']) ?>">
        </div>
        <div class="mb-3">
            <label>Catatan</label>
            <textarea name="catatan" class="form-control"><?= htmlspecialchars($row['catatan']) ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Update</button>
        <a href="?page=pelanggan_read" class="btn btn-secondary">Batal</a>
    </form>
</div>
<?php include "template/footer.php"; ?>
