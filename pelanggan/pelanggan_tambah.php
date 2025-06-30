<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Admin','Karyawan'])) {
    echo '<script>alert("Akses ditolak");window.location="../index.php";</script>';
    exit;
}
include "pengaturan/koneksi.php";
include "../template/header.php";

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
        $cek = mysqli_query($konek, "SELECT id_pelanggan FROM pelanggan WHERE nomor_telepon='$telp'");
        if (mysqli_num_rows($cek)>0) {
            $err = 'Nomor telepon sudah terdaftar!';
        } else {
            $q = mysqli_query($konek, "INSERT INTO pelanggan (nama_pelanggan, nomor_telepon, id_telegram, alamat, email, catatan) VALUES ('$nama','$telp','$id_telegram','$alamat','$email','$catatan')");
            if ($q) {
                // Pesanan berhasil
                echo '<script>alert("Pelanggan berhasil ditambahkan!");window.location="index.php?page=pelanggan_read";</script>';
                exit;
            } else {
                $err = 'Gagal menambah pelanggan.';
            }
        }
    }
}
?>

    <div class="container-fluid mt-4">
    <h3 class="mb-3">Tambah Pelanggan Baru</h3>
    <?php if($err): ?><div class="alert alert-danger"><?= $err ?></div><?php endif; ?>
    <?php if($sukses): ?><div class="alert alert-success"><?= $sukses ?></div><?php endif; ?>
    <form method="post">
        <div class="mb-3">
            <label>Nama Pelanggan *</label>
            <input type="text" name="nama_pelanggan" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Nomor Telepon (WA) *</label>
            <input type="text" name="nomor_telepon" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>ID Telegram</label>
            <input type="text" name="id_telegram" class="form-control">
        </div>
        <div class="mb-3">
            <label>Alamat</label>
            <textarea name="alamat" class="form-control"></textarea>
        </div>
        <div class="mb-3">
            <label>Email</label>
            <input type="email" name="email" class="form-control">
        </div>
        <div class="mb-3">
            <label>Catatan</label>
            <textarea name="catatan" class="form-control"></textarea>
        </div>
        <button type="submit" class="btn btn-success">Simpan</button>
        <a href="?page=pelanggan_read" class="btn btn-secondary">Batal</a>
    </form>
</div>
