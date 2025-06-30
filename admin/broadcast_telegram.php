<?php
// Form broadcast Telegram ke semua pelanggan yang sudah terhubung
include_once __DIR__ . '/../pengaturan/koneksi.php';
include_once __DIR__ . '/../pengaturan/telegram_notif.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pesan = trim($_POST['pesan']);
    if ($pesan != '') {
        $q = mysqli_query($konek, "SELECT id_telegram FROM pelanggan WHERE id_telegram IS NOT NULL AND id_telegram != ''");
        $total = 0;
        while ($row = mysqli_fetch_assoc($q)) {
            send_telegram($row['id_telegram'], $pesan);
            $total++;
            // (Opsional) Log notifikasi broadcast
        }
        echo '<script>alert("Pesan berhasil dikirim ke ' . $total . ' pelanggan!");window.location="../index.php?page=dashboard";</script>';
        exit;
    }
}
?>
<div class="container mt-4">
    <h3>Broadcast Telegram ke Pelanggan</h3>
    <form method="post">
        <div class="mb-3">
            <label for="pesan">Pesan Broadcast Telegram:</label>
            <textarea name="pesan" id="pesan" class="form-control" required rows="4"></textarea>
        </div>
        <button type="submit" class="btn btn-success">Kirim ke Semua Pelanggan</button>
    </form>
</div>
