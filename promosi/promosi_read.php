<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Admin','Karyawan'])) {
    echo '<script>alert("Akses ditolak");window.location="index.php?page=dashboard_read";</script>';
    exit;
}
include "pengaturan/koneksi.php";
include "../template/header.php";
// Otomatis nonaktifkan promo yang sudah lewat masa berlaku
mysqli_query($konek, "UPDATE promosi SET status_promo='tidak aktif' WHERE tanggal_berakhir < NOW() AND status_promo='aktif'");
// Otomatis aktifkan promo yang tanggal_mulai sudah tiba dan status masih 'akan aktif'
mysqli_query($konek, "UPDATE promosi SET status_promo='aktif' WHERE tanggal_mulai <= NOW() AND status_promo='akan aktif' AND (tanggal_berakhir IS NULL OR tanggal_berakhir >= NOW())");
$res = mysqli_query($konek, "SELECT * FROM promosi ORDER BY tanggal_buat DESC");
?>
<div class="container-fluid mt-4">
    <h3 class="mb-3">Daftar Promosi</h3>
    <a href="?page=promosi_tambah" class="btn btn-success mb-3">+ Tambah Promosi</a>
    <div class="table-responsive">
        <table class="table table-bordered table-hover">
            <thead class="table-light">
                <tr>
                    <th>Judul</th>
                    <th>Jenis</th>
                    <th>Nilai</th>
                    <th>Masa Berlaku</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php while($row = mysqli_fetch_assoc($res)): 
                // Logika untuk menampilkan nilai promo
                $nilai_tampil = '';
                if ($row['tipe_promo'] == 'persen') {
                    $nilai_tampil = rtrim(rtrim($row['nilai_promo'], '0'), '.') . '%';
                } else {
                    $nilai_tampil = 'Rp' . number_format($row['nilai_promo'], 0, ',', '.');
                }

                // Logika untuk menampilkan masa berlaku
                $masa_berlaku = 'Selamanya';
                if ($row['tanggal_mulai'] && $row['tanggal_berakhir']) {
                    $masa_berlaku = date('d/m/y', strtotime($row['tanggal_mulai'])) . ' - ' . date('d/m/y', strtotime($row['tanggal_berakhir']));
                } elseif ($row['tanggal_mulai']) {
                    $masa_berlaku = 'Mulai ' . date('d/m/y', strtotime($row['tanggal_mulai']));
                }

                // Logika untuk badge status
                $status_badge = '';
                switch($row['status_promo']) {
                    case 'aktif': $status_badge = 'bg-success'; break;
                    case 'draft': $status_badge = 'bg-secondary'; break;
                    case 'tidak_aktif': $status_badge = 'bg-danger'; break;
                    case 'terkirim': $status_badge = 'bg-info'; break;
                }
            ?>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars($row['judul']) ?></strong><br>
                        <small class="text-muted">Kode: <?= htmlspecialchars($row['kode_promo'] ?: '-') ?></small>
                    </td>
                    <td><?= ucfirst($row['tipe_promo']) ?></td>
                    <td><?= $nilai_tampil ?></td>
                    <td><?= $masa_berlaku ?></td>
                    <td><span class="badge <?= $status_badge ?>"><?= ucfirst($row['status_promo']) ?></span></td>
                    <td>
                        <a href="?page=promosi_edit&id=<?= $row['id_promosi'] ?>" class="btn btn-warning btn-sm">Edit</a>
                        <?php if ($row['status_promo'] == 'aktif'): ?>
                            <a href="?page=promosi_broadcast_konfirmasi&id=<?= $row['id_promosi'] ?>" class="btn btn-sm btn-info">Broadcast</a>
                        <?php endif; ?>
                        <a href="?page=promosi_delete&id=<?= $row['id_promosi'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin hapus promosi ini?');">Hapus</a>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
