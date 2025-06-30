<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Admin','Karyawan'])) {
    echo '<script>alert("Akses ditolak");window.location="../index.php";</script>';
    exit;
}
include "pengaturan/koneksi.php";

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo '<script>alert("ID pelanggan tidak valid");window.location="?page=pelanggan_read";</script>';
    exit;
}
$id = (int)$_GET['id'];

// Ambil data pelanggan
$res = mysqli_query($konek, "SELECT * FROM pelanggan WHERE id_pelanggan=$id");
if (!$pelanggan = mysqli_fetch_assoc($res)) {
    echo '<script>alert("Data pelanggan tidak ditemukan");window.location="?page=pelanggan_read";</script>';
    exit;
}

// Ambil riwayat pesanan pelanggan
$q_pesanan = mysqli_query($konek, "SELECT id_pesanan, nomor_invoice, tanggal_masuk, status_pesanan_umum, total_harga_keseluruhan FROM pesanan WHERE id_pelanggan=$id ORDER BY tanggal_masuk DESC");
?>
<div class="container-fluid mt-4">
    <h3 class="mb-3">Detail Pelanggan</h3>
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title mb-3">Informasi Pelanggan</h5>
            <table class="table table-borderless">
                <tr><th width="200">Nama</th><td><?= htmlspecialchars($pelanggan['nama_pelanggan']) ?></td></tr>
                <tr><th>No. Telepon</th><td><?= htmlspecialchars($pelanggan['nomor_telepon']) ?></td></tr>
                <tr><th>ID Telegram</th><td><?= htmlspecialchars($pelanggan['id_telegram']) ?></td></tr>
                <tr><th>Alamat</th><td><?= htmlspecialchars($pelanggan['alamat']) ?></td></tr>
                <tr><th>Email</th><td><?= htmlspecialchars($pelanggan['email']) ?></td></tr>
                <tr><th>Catatan</th><td><?= htmlspecialchars($pelanggan['catatan']) ?></td></tr>
            </table>
            <a href="?page=pelanggan_edit&id=<?= $pelanggan['id_pelanggan'] ?>" class="btn btn-warning btn-sm">Edit</a>
            <a href="?page=pelanggan_read" class="btn btn-secondary btn-sm">Kembali</a>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <h5 class="card-title mb-3">Riwayat Pesanan</h5>
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>No. Invoice</th>
                        <th>Tanggal Masuk</th>
                        <th>Status</th>
                        <th>Total Harga</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php if(mysqli_num_rows($q_pesanan)>0): while($row=mysqli_fetch_assoc($q_pesanan)): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['nomor_invoice']) ?></td>
                        <td><?= date('d-m-Y', strtotime($row['tanggal_masuk'])) ?></td>
                        <td><span class="badge bg-info text-dark"><?= htmlspecialchars($row['status_pesanan_umum']) ?></span></td>
                        <td>Rp<?= number_format($row['total_harga_keseluruhan'],0,',','.') ?></td>
                        <td><a href="?page=pesanan_detail&id=<?= $row['id_pesanan'] ?>" class="btn btn-info btn-sm">Detail</a></td>
                    </tr>
                <?php endwhile; else: ?>
                    <tr><td colspan="5" class="text-center text-muted">Belum ada pesanan</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
