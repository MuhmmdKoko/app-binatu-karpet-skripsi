<?php
// --- Detail Pesanan: Info Umum, Daftar Item, Status Proses per Item, Tombol Cetak Nota ---
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Admin','Karyawan'])) {
    echo '<script>alert("Akses ditolak");window.location="../index.php";</script>';
    exit;
}
include "pengaturan/koneksi.php";
// --- Helper Notifikasi (agar tidak error fatal) ---
require_once __DIR__ . '/../pengaturan/telegram_utils.php';
if (!function_exists('kirim_notifikasi_pelanggan')) {
    function kirim_notifikasi_pelanggan($id_pelanggan, $pesan, $channel = 'Telegram') {
        global $konek, $TELEGRAM_BOT_TOKEN;
        if ($channel !== 'Telegram') return false;
        $q = mysqli_query($konek, "SELECT id_telegram FROM pelanggan WHERE id_pelanggan='$id_pelanggan' LIMIT 1");
        $row = mysqli_fetch_assoc($q);
        if (empty($row['id_telegram'])) {
            error_log("[TELEGRAM] Gagal: id_telegram pelanggan kosong untuk id_pelanggan $id_pelanggan");
            return false;
        }
        $chat_id = $row['id_telegram'];
        $pesan = trim($pesan);
        $result = send_telegram_message($TELEGRAM_BOT_TOKEN, $chat_id, $pesan, 'HTML');
        if (!$result) {
            error_log("[TELEGRAM] Gagal kirim ke $chat_id oleh utilitas telegram_utils.php");
        }
        return $result;
    }
}
if (!function_exists('catat_notifikasi')) {
    function catat_notifikasi($konek, $id_pesanan, $id_pelanggan, $pesan, $channel = 'Telegram', $tipe = 'Status Pesanan') {
        $pesan_sql = mysqli_real_escape_string($konek, $pesan);
        $channel_sql = mysqli_real_escape_string($konek, $channel);
        $tipe_sql = mysqli_real_escape_string($konek, $tipe);
        $query = "INSERT INTO notifikasi (id_pesanan, id_pelanggan, pesan, waktu_kirim, channel, tipe_notifikasi, status_pengiriman)
                  VALUES ('$id_pesanan', '$id_pelanggan', '$pesan_sql', NOW(), '$channel_sql', '$tipe_sql', 'Terkirim')";
        mysqli_query($konek, $query);
    }
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$id) {
    echo '<script>alert("ID pesanan tidak valid");window.history.back();</script>';
    exit;
}
// Ambil data pesanan
$data_pesanan = mysqli_query($konek, "SELECT p.*, pl.nama_pelanggan, pl.nomor_telepon, pl.alamat, pl.id_telegram FROM pesanan p JOIN pelanggan pl ON p.id_pelanggan=pl.id_pelanggan WHERE p.id_pesanan=$id");
if (!$row = mysqli_fetch_assoc($data_pesanan)) {
    echo '<script>alert("Pesanan tidak ditemukan");window.history.back();</script>';
    exit;
}
// Ambil detail item
$detail = mysqli_query($konek, "SELECT d.*, l.nama_layanan, l.satuan FROM detail_pesanan d JOIN layanan l ON d.id_layanan=l.id_layanan WHERE d.id_pesanan=$id");
?>
<div class="container-fluid mt-4">
    <h3>Detail Pesanan</h3>
    <div class="card mb-3">
        <div class="card-body">
            <h5 class="card-title">Info Pesanan</h5>
            <div class="row mb-2">
                <div class="col-md-4"><b>Nomor Invoice:</b> <?= htmlspecialchars($row['nomor_invoice']) ?></div>
                <div class="col-md-4"><b>Pelanggan:</b> <?= htmlspecialchars($row['nama_pelanggan']) ?></div>
                <div class="col-md-4"><b>Tanggal Masuk:</b> <?= htmlspecialchars(date('d-m-Y H:i', strtotime($row['tanggal_masuk']))) ?></div>
            </div>
            <div class="row mb-2">
                <div class="col-md-4"><b>Estimasi Selesai:</b> <?= htmlspecialchars(date('d-m-Y H:i', strtotime($row['tanggal_estimasi_selesai']))) ?></div>
                <div class="col-md-4"><b>Status Umum:</b> <?= htmlspecialchars($row['status_pesanan_umum']) ?></div>
                <div class="col-md-4"><b>Metode Bayar:</b> <?= htmlspecialchars($row['metode_pembayaran']) ?></div>

            </div>
            <?php if (!empty($row['tanggal_selesai_aktual']) || !empty($row['tanggal_diambil'])) : ?>
            <div class="row mb-2">
                <?php if (!empty($row['tanggal_selesai_aktual'])) : ?>
                <div class="col-md-4"><b>Selesai Aktual:</b> <?= htmlspecialchars(date('d-m-Y H:i', strtotime($row['tanggal_selesai_aktual']))) ?></div>
                <?php endif; ?>
                <?php if (!empty($row['tanggal_diambil'])) : ?>
                <div class="col-md-4"><b>Tanggal Diambil:</b> <?= htmlspecialchars(date('d-m-Y H:i', strtotime($row['tanggal_diambil']))) ?></div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <div class="row mb-2">
                <div class="col-md-4"><b>Status Pembayaran:</b> <?= htmlspecialchars($row['status_pembayaran']) ?></div>
                <div class="col-md-8"><b>Catatan:</b> <?= htmlspecialchars($row['catatan_pesanan']) ?></div>
            </div>
            <a href="pesanan/pesanan_cetak_nota.php?id=<?= $id ?>" target="_blank" class="btn btn-info btn-sm">Cetak Nota</a>
            <?php if (!empty($row['id_telegram'])): ?>
            <a href="pesanan/generate_nota_pdf_and_send.php?id=<?= $id ?>" class="btn btn-success btn-sm">Kirim Nota via Telegram</a>
            <?php endif; ?>
            <?php if(!in_array($row['status_pesanan_umum'], ['Diambil','Dibatalkan'])): ?>
            <a href="?page=pesanan_edit&id=<?= $id ?>" class="btn btn-primary btn-sm">Edit Pesanan</a>
            <?php endif; ?>
            <?php if($row['status_pembayaran']!='' && !in_array($row['status_pesanan_umum'], ['Dibatalkan','Diambil'])): ?>
            <a href="?page=pesanan_pembayaran&id=<?= $id ?>" class="btn btn-success btn-sm">Update Pembayaran</a>
            <?php endif; ?>
            <a href="?page=pesanan_status_proses&id=<?= $id ?>" class="btn btn-warning btn-sm">Status Proses</a>
            <a href="?page=pesanan_read" class="btn btn-secondary btn-sm">Kembali</a>
            <?php if($row['status_pesanan_umum']==='Selesai' && !in_array($row['status_pesanan_umum'], ['Diambil','Dibatalkan'])): ?>
            <form method="post" style="display:block; margin-top:10px;">
                <button name="jadikan_diambil" class="btn btn-success" onclick="return confirm('Jadikan pesanan ini Diambil?')">Jadikan Diambil</button>
            </form>
            <?php endif; ?>
            <?php
            if (isset($_POST['jadikan_diambil']) && $row['status_pesanan_umum']==='Selesai') {
                if ($row['status_pembayaran'] !== 'Lunas') {
                    echo '<script>alert("Pembayaran belum lunas, silakan lunasi pembayaran terlebih dahulu!");window.location="?page=pesanan_detail&id='.$id.'";</script>';
                    exit;
                }
                $update_query = "UPDATE pesanan SET status_pesanan_umum='Diambil', tanggal_diambil=NOW() WHERE id_pesanan=$id";
                if (mysqli_query($konek, $update_query)) {
                    $id_pengguna = $_SESSION['id_pengguna'];
                    $log_umum_sql = "INSERT INTO riwayat_status_pesanan (id_pesanan, status_sebelumnya, status_baru, id_pengguna, waktu_perubahan) VALUES ('$id', 'Selesai', 'Diambil', '$id_pengguna', NOW())";
                    mysqli_query($konek, $log_umum_sql);
                    // Update semua status item menjadi Diambil dan log riwayat item
                    $res_items = mysqli_query($konek, "SELECT id_detail_pesanan, status_item_terkini FROM detail_pesanan WHERE id_pesanan=$id");
                    while ($item = mysqli_fetch_assoc($res_items)) {
                        $id_detail = $item['id_detail_pesanan'];
                        $old_status = $item['status_item_terkini'];
                        if ($old_status != 'Diambil') {
                            mysqli_query($konek, "UPDATE detail_pesanan SET status_item_terkini='Diambil' WHERE id_detail_pesanan=$id_detail");
                            $log_item_sql = "INSERT INTO riwayat_status_item (id_detail_pesanan, status_sebelumnya, status_baru, id_pengguna, waktu_perubahan) VALUES ('$id_detail', '".mysqli_real_escape_string($konek,$old_status)."', 'Diambil', '$id_pengguna', NOW())";
                            mysqli_query($konek, $log_item_sql);
                        }
                    }
                    $id_pelanggan = $row['id_pelanggan'];
                    $pesan_notif = "Status pesanan Anda dengan nomor invoice ".$row['nomor_invoice']." telah berubah menjadi: Diambil.";
                    kirim_notifikasi_pelanggan($id_pelanggan, $pesan_notif, 'Telegram');
                    catat_notifikasi($konek, $id, $id_pelanggan, $pesan_notif, 'Telegram', 'Status Pesanan');
                    echo '<script>alert("Pesanan sudah dijadikan Diambil.");window.location="?page=pesanan_detail&id='.$id.'";</script>';
                    exit;
                } else {
                    echo '<script>alert("Gagal update status pesanan!");window.location="?page=pesanan_detail&id='.$id.'";</script>';
                    exit;
                }
            }
            ?>
        </div>
    </div>
    <div class="card mb-3">
        <div class="card-body">
            <h5 class="card-title">Daftar Item/Layanan</h5>
            <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Layanan</th>
                        <th>Deskripsi</th>
                        <th>Ukuran (P x L)</th>
                        <th>Kuantitas</th>
                        <th>Harga Satuan</th>
                        <th>Subtotal</th>
                        <th>Status Item</th>
                        <th>Catatan</th>
                    </tr>
                </thead>
                <tbody>
                <?php while($d = mysqli_fetch_assoc($detail)): ?>
                    <tr>
                        <td><?= htmlspecialchars($d['nama_layanan']) ?></td>
                        <td><?= htmlspecialchars($d['deskripsi_item_spesifik']) ?></td>
                        <td>
<?php
if (($d['satuan'] === 'm2' || $d['satuan'] === 'm²') && $d['panjang_karpet'] > 0 && $d['lebar_karpet'] > 0) {
    echo htmlspecialchars($d['panjang_karpet']) . ' x ' . htmlspecialchars($d['lebar_karpet']) . ' m';
} else {
    echo '-';
}
?>
</td>
                        <td><?= htmlspecialchars($d['kuantitas']) . ' ' . htmlspecialchars($d['satuan']) ?></td>
                        <td>Rp<?= number_format($d['harga_saat_pesan'],0,',','.') ?></td>
                        <td>Rp<?= number_format($d['subtotal_item'],0,',','.') ?></td>
                        <td><?= htmlspecialchars($d['status_item_terkini']) ?></td>
                        <td><?= htmlspecialchars($d['catatan_item']) ?></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
            </div>
            <div class="text-end">
                <?php
// Hitung ulang subtotal dari detail item (jika ingin pastikan akurat dari data per item)
mysqli_data_seek($detail, 0); // reset pointer
$subtotal_penuh = 0;
while($dtmp = mysqli_fetch_assoc($detail)) {
    $subtotal_penuh += (int)$dtmp['subtotal_item'];
}
?>
<h5 class="mb-1">Subtotal: Rp<?= number_format($subtotal_penuh, 0, ',', '.') ?></h5>
<!-- DEBUG: <?= $row['total_harga_keseluruhan'] ?> -->
<?php if (!empty($row['diskon']) && $row['diskon'] > 0): ?>
    <div class="text-danger">Diskon: -Rp<?= number_format($row['diskon'], 0, ',', '.') ?></div>
    <h4 class="mt-2">Total: Rp<?= number_format($row['total_setelah_diskon'], 1, ',', '.') ?></h4>
<?php else: ?>
    <h4 class="mt-2">Total: Rp<?= number_format($row['total_harga_keseluruhan'], 0, ',', '.') ?></h4>
<?php endif; ?>
            </div>
        </div>
    </div>
</div>
