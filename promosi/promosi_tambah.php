<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Admin','Karyawan'])) {
    echo '<script>alert("Akses ditolak");window.location="index.php?page=dashboard_read";</script>';
    exit;
}
include "pengaturan/koneksi.php";
include "../template/header.php";
$err = '';
$judul = $kode_promo = $tipe_promo = $nilai_promo = $syarat_min_transaksi = $tanggal_mulai = $tanggal_berakhir = $status_promo = $isi_pesan = '';

if (isset($_POST['simpan'])) {
    // Ambil semua data dari form
    $judul = mysqli_real_escape_string($konek, $_POST['judul']);
    $kode_promo = mysqli_real_escape_string($konek, strtoupper($_POST['kode_promo']));
    $tipe_promo = $_POST['tipe_promo'];
    $nilai_promo = filter_var($_POST['nilai_promo'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $syarat_min_transaksi = empty($_POST['syarat_min_transaksi']) ? 'NULL' : filter_var($_POST['syarat_min_transaksi'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $tanggal_mulai = empty($_POST['tanggal_mulai']) ? 'NULL' : "'" . $_POST['tanggal_mulai'] . "'";
    $tanggal_berakhir = empty($_POST['tanggal_berakhir']) ? 'NULL' : "'" . $_POST['tanggal_berakhir'] . "'";
    $status_promo = $_POST['status_promo'];
    $isi_pesan = mysqli_real_escape_string($konek, $_POST['isi_pesan']);

    if (empty($judul) || empty($nilai_promo)) {
        $err = 'Judul dan Nilai Promo wajib diisi.';
    } else {
        // Validasi status promo: jika expired dan status dipilih 'aktif', paksa jadi 'tidak_aktif'
        $today = date('Y-m-d');
        if (!empty($tanggal_berakhir) && $tanggal_berakhir < $today && $status_promo == 'aktif') {
            $status_promo = 'tidak_aktif';
        }
        $sql = "INSERT INTO promosi (judul, kode_promo, tipe_promo, nilai_promo, syarat_min_transaksi, tanggal_mulai, tanggal_berakhir, status_promo, isi_pesan, tanggal_buat) VALUES ('$judul', '$kode_promo', '$tipe_promo', $nilai_promo, $syarat_min_transaksi, $tanggal_mulai, $tanggal_berakhir, '$status_promo', '$isi_pesan', NOW())";
        
        if (mysqli_query($konek, $sql)) {
            echo '<script>alert("Promosi berhasil ditambahkan.");window.location="?page=promosi_read";</script>';
            exit;
        } else {
            $err = 'Gagal menambah promosi: ' . mysqli_error($konek);
        }
    }
}
?>
<div class="container-fluid mt-4">
    <h3 class="mb-3">Tambah Promosi Baru</h3>
    <?php if ($err): ?><div class="alert alert-danger"><?= $err ?></div><?php endif; ?>
    <form method="post" class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <div class="mb-3">
                        <label for="judul" class="form-label">Judul Promosi *</label>
                        <input type="text" class="form-control" id="judul" name="judul" value="<?= htmlspecialchars($judul) ?>" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="kode_promo" class="form-label">Kode Promo (Opsional)</label>
                        <input type="text" class="form-control" id="kode_promo" name="kode_promo" value="<?= htmlspecialchars($kode_promo) ?>" placeholder="e.g., DISKON20">
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="tipe_promo" class="form-label">Tipe Promo</label>
                        <select class="form-select" id="tipe_promo" name="tipe_promo">
                            <option value="persen" <?= $tipe_promo == 'persen' ? 'selected' : '' ?>>Persen (%)</option>
                            <option value="nominal" <?= $tipe_promo == 'nominal' ? 'selected' : '' ?>>Nominal (Rp)</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="nilai_promo" id="label_nilai_promo" class="form-label">Nilai Promo *</label>
                        <input type="number" step="any" class="form-control" id="nilai_promo" name="nilai_promo" value="<?= htmlspecialchars($nilai_promo) ?>" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="syarat_min_transaksi" class="form-label">Min. Transaksi (Rp)</label>
                        <input type="number" class="form-control" id="syarat_min_transaksi" name="syarat_min_transaksi" value="<?= htmlspecialchars($syarat_min_transaksi) ?>" placeholder="Kosongkan jika tidak ada">
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="tanggal_mulai" class="form-label">Tanggal Mulai</label>
                        <input type="date" class="form-control" id="tanggal_mulai" name="tanggal_mulai" value="<?= htmlspecialchars($tanggal_mulai) ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="tanggal_berakhir" class="form-label">Tanggal Berakhir</label>
                        <input type="date" class="form-control" id="tanggal_berakhir" name="tanggal_berakhir" value="<?= htmlspecialchars($tanggal_berakhir) ?>">
                    </div>
                </div>
                <div class="col-md-4">
                     <div class="mb-3">
                        <label for="status_promo" class="form-label">Status</label>
                        <select class="form-select" id="status_promo" name="status_promo">
                            <option value="draft" <?= $status_promo == 'draft' ? 'selected' : '' ?>>Draft</option>
                            <option value="aktif" <?= $status_promo == 'aktif' ? 'selected' : '' ?>>Aktif</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <label for="isi_pesan" class="form-label">Isi Pesan (Untuk Broadcast)</label>
                <textarea class="form-control" id="isi_pesan" name="isi_pesan" rows="3" placeholder="Tulis pesan yang akan dikirim ke pelanggan..."><?= htmlspecialchars($isi_pesan) ?></textarea>
            </div>
        </div>
        <div class="card-footer text-end">
            <a href="?page=promosi_read" class="btn btn-secondary">Batal</a>
            <button type="submit" name="simpan" class="btn btn-primary">Simpan Promosi</button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tipePromo = document.getElementById('tipe_promo');
    const labelNilaiPromo = document.getElementById('label_nilai_promo');

    function updateLabel() {
        if (tipePromo.value === 'persen') {
            labelNilaiPromo.textContent = 'Nilai Promo (%) *';
        } else {
            labelNilaiPromo.textContent = 'Nilai Promo (Rp) *';
        }
    }

    tipePromo.addEventListener('change', updateLabel);
    updateLabel(); // Set initial label on page load
});
</script>
