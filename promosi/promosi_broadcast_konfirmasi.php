<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Admin','Karyawan'])) {
    echo '<script>alert("Akses ditolak");window.location="index.php?page=dashboard_read";</script>';
    exit;
}
include "pengaturan/koneksi.php";

$id = intval($_GET['id'] ?? 0);
$query_promo = mysqli_query($konek, "SELECT * FROM promosi WHERE id_promosi=$id");
$promo = mysqli_fetch_assoc($query_promo);

if (!$promo) {
    echo '<script>alert(\'Promosi tidak ditemukan!\');window.location=\'?page=promosi_read\';</script>';
    exit;
}

// Fetch all customers with a Telegram ID
$query_pelanggan = mysqli_query($konek, "SELECT id_pelanggan, nama_pelanggan FROM pelanggan WHERE id_telegram IS NOT NULL AND id_telegram != '' ORDER BY nama_pelanggan ASC");
$pelanggan_list = [];
while($row = mysqli_fetch_assoc($query_pelanggan)) {
    $pelanggan_list[] = $row;
}
$jumlah_penerima = count($pelanggan_list);

$pesan_broadcast = !empty($promo['isi_pesan']) ? $promo['isi_pesan'] : 'Pesan broadcast belum diatur untuk promo ini.';
?>

<div class="container-fluid mt-4">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Konfirmasi Broadcast Promosi</h3>
        </div>
        <form action="?page=promosi_kirim_proses" method="POST" id="broadcastForm">
            <div class="card-body">
                <p>Anda akan mengirimkan pesan promosi berikut:</p>
                <div class="alert alert-info">
                    <strong><?= htmlspecialchars($promo['judul']) ?></strong><br>
                    <pre style="white-space: pre-wrap; word-wrap: break-word; font-family: inherit;"><?= htmlspecialchars($pesan_broadcast) ?></pre>
                </div>

                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="card-title">Pilih Penerima</h5>
                    </div>
                    <div class="card-body" style="max-height: 300px; overflow-y: auto;">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="selectAll">
                            <label class="form-check-label" for="selectAll">
                                <strong>Pilih Semua (Total: <?= $jumlah_penerima ?> pelanggan)</strong>
                            </label>
                        </div>
                        <hr>
                        <?php if ($jumlah_penerima > 0): ?>
                            <?php foreach ($pelanggan_list as $pelanggan): ?>
                                <div class="form-check">
                                    <input class="form-check-input recipient-checkbox" type="checkbox" name="id_pelanggan[]" value="<?= $pelanggan['id_pelanggan'] ?>" id="pelanggan_<?= $pelanggan['id_pelanggan'] ?>">
                                    <label class="form-check-label" for="pelanggan_<?= $pelanggan['id_pelanggan'] ?>">
                                        <?= htmlspecialchars($pelanggan['nama_pelanggan']) ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>Tidak ada pelanggan dengan ID Telegram yang terdaftar.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="alert alert-warning mt-3">
                    <i class="fas fa-users"></i> Pesan ini akan dikirim ke <strong id="selectedCount">0 pelanggan</strong> yang dipilih.
                </div>
                <p>Pastikan pesan dan penerima sudah benar sebelum melanjutkan. Tindakan ini tidak dapat dibatalkan.</p>
                
                <input type="hidden" name="id_promosi" value="<?= $id ?>">
                <button type="submit" class="btn btn-danger" id="submitBtn" disabled>Kirim Broadcast Sekarang</button>
                <a href="?page=promosi_read" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAllCheckbox = document.getElementById('selectAll');
    const recipientCheckboxes = document.querySelectorAll('.recipient-checkbox');
    const selectedCountSpan = document.getElementById('selectedCount');
    const submitBtn = document.getElementById('submitBtn');

    function updateCount() {
        const count = document.querySelectorAll('.recipient-checkbox:checked').length;
        selectedCountSpan.textContent = count + ' pelanggan';
        submitBtn.disabled = count === 0;
    }

    selectAllCheckbox.addEventListener('change', function() {
        recipientCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updateCount();
    });

    recipientCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            if (!this.checked) {
                selectAllCheckbox.checked = false;
            }
            updateCount();
        });
    });

    // Initial count
    updateCount();
});
</script>
