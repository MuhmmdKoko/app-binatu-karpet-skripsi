<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Admin','Karyawan'])) {
    echo '<script>alert("Akses ditolak");window.location="index.php?page=dashboard_read";</script>';
    exit;
}
include "pengaturan/koneksi.php";
include "../template/header.php";

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    echo '<script>alert("ID layanan tidak valid.");window.location="?page=layanan_read";</script>';
    exit;
}

$query_get = "SELECT * FROM layanan WHERE id_layanan = $id";
$result_get = mysqli_query($konek, $query_get);
$data = mysqli_fetch_assoc($result_get);

if (!$data) {
    echo '<script>alert("Data layanan tidak ditemukan.");window.location="?page=layanan_read";</script>';
    exit;
}

$err = '';
if (isset($_POST['simpan'])) {
    $nama = trim($_POST['nama_layanan'] ?? '');
    $harga = floatval($_POST['harga_per_unit'] ?? 0);
    $satuan = trim($_POST['satuan'] ?? '');
    $estimasi = intval($_POST['estimasi_waktu_hari'] ?? 0);
    $minimal_order_aktif = isset($_POST['minimal_order_aktif']) ? 1 : 0;
    $minimal_order_kuantitas = floatval($_POST['minimal_order_kuantitas'] ?? 0);

    if (empty($nama) || $harga <= 0 || empty($satuan) || $estimasi < 0) {
        $err = 'Semua field wajib diisi dengan data yang valid!';
    } elseif ($minimal_order_aktif == 1 && $minimal_order_kuantitas <= 0) {
        $err = 'Jika minimal order diaktifkan, kuantitas minimal harus lebih dari 0.';
    } else {
        $nama = mysqli_real_escape_string($konek, $nama);
        $satuan_raw = $_POST['satuan'];
        $satuan_formatted = str_replace('m2', 'm²', $satuan_raw);
        $satuan_formatted = str_replace('m3', 'm³', $satuan_formatted);
        $satuan = mysqli_real_escape_string($konek, $satuan_formatted);

        if ($minimal_order_aktif == 0) {
            $minimal_order_kuantitas = 0;
        }

        $query_update = "UPDATE layanan SET 
            nama_layanan='$nama', 
            harga_per_unit='$harga', 
            satuan='$satuan', 
            estimasi_waktu_hari='$estimasi',
            minimal_order_aktif='$minimal_order_aktif',
            minimal_order_kuantitas='$minimal_order_kuantitas'
            WHERE id_layanan=$id";
            
        if (mysqli_query($konek, $query_update)) {
            echo '<script>alert("Data layanan berhasil diperbarui.");window.location="?page=layanan_read";</script>';
            exit;
        } else {
            $err = 'Gagal memperbarui layanan: ' . mysqli_error($konek);
        }
    }
}
?>

<div class="container-fluid mt-4">
    <div class="card">
        <div class="card-header">
            <h5 class="card-title">Edit Layanan</h5>
        </div>
        <div class="card-body">
            <?php if ($err): ?><div class="alert alert-danger"><?= $err ?></div><?php endif; ?>
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="nama_layanan" class="form-label">Nama Layanan/Item</label>
                    <input type="text" class="form-control" id="nama_layanan" name="nama_layanan" value="<?= htmlspecialchars($data['nama_layanan']); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="harga_per_unit" class="form-label">Harga per Unit</label>
                    <input type="number" class="form-control" id="harga_per_unit" name="harga_per_unit" value="<?= htmlspecialchars($data['harga_per_unit']); ?>" min="0" step="100" required>
                </div>
                <div class="mb-3">
                    <label for="satuan" class="form-label">Satuan</label>
                    <input type="text" class="form-control" id="satuan" name="satuan" value="<?= htmlspecialchars($data['satuan']); ?>" required placeholder="Contoh: m², kg, pcs">
                </div>
                <div class="mb-3">
                    <label for="estimasi_waktu_hari" class="form-label">Estimasi Waktu (hari)</label>
                    <input type="number" class="form-control" id="estimasi_waktu_hari" name="estimasi_waktu_hari" value="<?= htmlspecialchars($data['estimasi_waktu_hari']); ?>" min="0" required>
                </div>

                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="minimal_order_aktif" name="minimal_order_aktif" value="1" <?= !empty($data['minimal_order_aktif']) && $data['minimal_order_aktif'] ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="minimal_order_aktif">Aktifkan Minimal Order?</label>
                </div>

                <div class="mb-3" id="minimal_order_kuantitas_div" style="<?= !empty($data['minimal_order_aktif']) && $data['minimal_order_aktif'] ? 'display: block;' : 'display: none;'; ?>">
                    <label for="minimal_order_kuantitas" class="form-label">Minimal Kuantitas Order</label>
                    <input type="number" class="form-control" id="minimal_order_kuantitas" name="minimal_order_kuantitas" value="<?= htmlspecialchars($data['minimal_order_kuantitas']); ?>" min="0" step="0.01">
                </div>

                <button type="submit" name="simpan" class="btn btn-primary">Simpan Perubahan</button>
                <a href="?page=layanan_read" class="btn btn-secondary">Kembali</a>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkbox = document.getElementById('minimal_order_aktif');
    const kuantitasDiv = document.getElementById('minimal_order_kuantitas_div');

    checkbox.addEventListener('change', function() {
        if (this.checked) {
            kuantitasDiv.style.display = 'block';
        } else {
            kuantitasDiv.style.display = 'none';
        }
    });
});
</script>
