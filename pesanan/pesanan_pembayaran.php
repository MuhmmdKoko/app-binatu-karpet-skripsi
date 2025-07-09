<?php
// --- Update Pembayaran Pesanan ---
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Admin','Karyawan'])) {
    echo '<script>alert("Akses ditolak");window.location="../index.php";</script>';
    exit;
}
include "pengaturan/koneksi.php";
include "../template/header.php";

// --- Helper Notifikasi ---
require_once __DIR__ . '/../pengaturan/telegram_notif.php';
require_once __DIR__ . '/../pengaturan/telegram_utils.php';
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

function catat_notifikasi($konek, $id_pesanan, $id_pelanggan, $pesan, $channel = 'Telegram', $tipe = 'Pembayaran') {
    $pesan_sql = mysqli_real_escape_string($konek, $pesan);
    $channel_sql = mysqli_real_escape_string($konek, $channel);
    $tipe_sql = mysqli_real_escape_string($konek, $tipe);
    $query = "INSERT INTO notifikasi (id_pesanan, id_pelanggan, pesan, waktu_kirim, channel, tipe_notifikasi, status_pengiriman)
              VALUES ('$id_pesanan', '$id_pelanggan', '$pesan_sql', NOW(), '$channel_sql', '$tipe_sql', 'Terkirim')";
    mysqli_query($konek, $query);
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$id) {
    echo '<script>alert("ID pesanan tidak valid");window.history.back();</script>';
    exit;
}

// Ambil data pesanan utama
$q = mysqli_query($konek, "SELECT * FROM pesanan WHERE id_pesanan=$id");
if(!$row = mysqli_fetch_assoc($q)) {
    echo '<script>alert("Pesanan tidak ditemukan");window.history.back();</script>';
    exit;
}

$err = '';
if(isset($_POST['simpan_pembayaran'])) {
    if(in_array($row['status_pesanan_umum'], ['Dibatalkan','Diambil'])){
        $err = 'Pesanan telah dibatalkan/diambil dan tidak bisa menerima pembayaran.';
    } else {
        $tambah = isset($_POST['tambah_pembayaran']) ? floatval(str_replace('.', '', $_POST['tambah_pembayaran'])) : 0;
        $total = floatval($row['total_harga_keseluruhan']);
        $terbayar = floatval($row['nominal_pembayaran']);
        $sisa = $total - $terbayar;

        if($tambah <= 0) {
            $err = 'Nominal pembayaran tidak valid.';
        } else {
            mysqli_begin_transaction($konek);
            try {
                // Jika pembayaran melebihi sisa tagihan, hanya tambahkan sebesar sisa tagihan
                $tambah_simpan = ($tambah > $sisa) ? $sisa : $tambah;
                $terbayar_baru = $terbayar + $tambah_simpan;
                $status_bayar_baru = ($terbayar_baru >= $total) ? 'Lunas' : 'DP';
                $id_pengguna = $_SESSION['id_pengguna'];

                // 1. Update tabel pesanan
                $stmt1 = mysqli_prepare($konek, "UPDATE pesanan SET nominal_pembayaran=?, status_pembayaran=? WHERE id_pesanan=?");
                mysqli_stmt_bind_param($stmt1, 'dsi', $terbayar_baru, $status_bayar_baru, $id);
                mysqli_stmt_execute($stmt1);

                // 2. Insert ke log_pembayaran
                $uang_diterima = isset($_POST['uang_diterima']) ? floatval(str_replace('.', '', $_POST['uang_diterima'])) : $tambah;
                $uang_kembalian = $uang_diterima - $tambah_simpan;
                if ($uang_kembalian < 0) $uang_kembalian = 0;
                $stmt2 = mysqli_prepare($konek, "INSERT INTO log_pembayaran (id_pesanan, id_pengguna, jumlah_bayar, uang_diterima, uang_kembalian) VALUES (?, ?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt2, 'iiddd', $id, $id_pengguna, $tambah_simpan, $uang_diterima, $uang_kembalian);
                mysqli_stmt_execute($stmt2);

                // 3. Siapkan data notifikasi ke pelanggan
                $q_notif = mysqli_query($konek, "SELECT p.nomor_invoice, p.id_pelanggan FROM pesanan p WHERE p.id_pesanan=$id");
                $row_notif = mysqli_fetch_assoc($q_notif);
                $pesan_notif = "Pembayaran sebesar Rp".number_format($tambah,0,',','.')." untuk invoice {$row_notif['nomor_invoice']} telah diterima. Status pembayaran: $status_bayar_baru. Terima kasih.";
                // Tidak lagi insert manual ke notifikasi internal

                mysqli_commit($konek);

                // 4. Kirim notifikasi ke pelanggan & catat ke tabel notifikasi (di luar transaksi)
                if (kirim_notifikasi_pelanggan($row_notif['id_pelanggan'], $pesan_notif, 'Telegram')) {
                    catat_notifikasi($konek, $id, $row_notif['id_pelanggan'], $pesan_notif, 'Telegram', 'Pembayaran');
                }
                echo '<script>alert("Pembayaran berhasil dicatat.");window.location.href=window.location.href;</script>';
                exit;

            } catch (Exception $e) {
                mysqli_rollback($konek);
                $err = "Terjadi kesalahan saat menyimpan data: " . $e->getMessage();
            }
        }
    }
}

// Ambil ulang data pesanan setelah kemungkinan update
$q = mysqli_query($konek, "SELECT * FROM pesanan WHERE id_pesanan=$id");
$row = mysqli_fetch_assoc($q);
// Jika ada diskon/promo, gunakan total_setelah_diskon sebagai total tagihan
$total = (!empty($row['diskon']) && $row['diskon'] > 0) ? floatval($row['total_setelah_diskon']) : floatval($row['total_harga_keseluruhan']);
$terbayar = floatval($row['nominal_pembayaran']);
$sisa = $total - $terbayar;
if ($sisa < 0) $sisa = 0;

// Ambil data log pembayaran
$logs = [];
$q_log = mysqli_query($konek, "SELECT lp.*, pg.nama_lengkap FROM log_pembayaran lp JOIN pengguna pg ON lp.id_pengguna = pg.id_pengguna WHERE lp.id_pesanan = $id ORDER BY lp.tanggal_bayar DESC");
while($log_row = mysqli_fetch_assoc($q_log)) {
    $logs[] = $log_row;
}
?>
<div class="container-fluid mt-4">
    <h3 class="mb-3">Update Pembayaran Pesanan</h3>
    <?php if($err): ?><div class="alert alert-danger"><?= $err ?></div><?php endif; ?>
    <div class="row">
        <div class="col-md-5">
            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="card-title">Info Pembayaran</h5>
                    <p class="mb-1"><b>Nomor Invoice:</b> <?= htmlspecialchars($row['nomor_invoice']) ?></p>
                    <p class="mb-1"><b>Total Tagihan:</b> Rp<?= number_format($total,0,',','.') ?><?= (!empty($row['diskon']) && $row['diskon'] > 0) ? ' <span class="badge bg-success">Promo</span>' : '' ?></p>
                    <p class="mb-1"><b>Sudah Dibayar:</b> Rp<?= number_format($terbayar,0,',','.') ?></p>
                    <p class="fw-bold"><b>Sisa Tagihan:</b> Rp<?= number_format($sisa,0,',','.') ?></p>
                    
                    <?php if($sisa > 0 && !in_array($row['status_pesanan_umum'], ['Dibatalkan','Diambil'])): ?>
                    <hr>
                    <form method="post">
                        <div class="mb-3">
                            <label class="form-label">Tambah Pembayaran (Rp)</label>
                            <input type="text" class="form-control" name="tambah_pembayaran" id="inputPembayaran" required onkeyup="this.value=formatRupiah(this.value);">
<input type="hidden" name="uang_diterima" id="uang_diterima">
                            <div id="quickNominal" class="mt-2 d-flex flex-wrap gap-2">
                                <!-- Tombol nominal akan di-generate oleh JS -->
                            </div>
                            <div id="notifKembalian" style="display:none; margin-top:8px;"></div>
                        </div>
                        <script>
                        (function(){
                            // Data dari PHP
                            const SISATAGIHAN = <?= (int)$sisa ?>;
                            const NOMINALS = [10000,20000,30000,40000,50000,75000,100000,200000,500000];
                            // Generate tombol
                            let quickNom = document.getElementById('quickNominal');
                            let html = '<button type="button" class="btn btn-outline-primary btn-sm" data-nominal="'+SISATAGIHAN+'">Uang Pas</button>';
                            NOMINALS.forEach(n => {
                                if(n < SISATAGIHAN) html += '<button type="button" class="btn btn-outline-primary btn-sm" data-nominal="'+n+'">'+n.toLocaleString('id-ID')+'</button>';
                            });
                            html += '<button type="button" class="btn btn-outline-primary btn-sm" data-nominal="'+SISATAGIHAN+'">'+SISATAGIHAN.toLocaleString('id-ID')+'</button>';
                            quickNom.innerHTML = html;
                            // Event klik tombol
                            quickNom.querySelectorAll('button').forEach(btn => {
                                btn.addEventListener('click', function() {
                                    document.getElementById('inputPembayaran').value = this.dataset.nominal.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
                                    hitungKembalian();
                                });
                            });
                            // Event input manual
                            document.getElementById('inputPembayaran').addEventListener('input', function(){
    document.getElementById('uang_diterima').value = this.value.replace(/\D/g, '');
    hitungKembalian();
});
                            function hitungKembalian() {
                                let bayar = parseInt(document.getElementById('inputPembayaran').value.replace(/\D/g, '')) || 0;
                                let sisa = SISATAGIHAN;
                                let kembalian = bayar - sisa;
                                let notif = document.getElementById('notifKembalian');
                                if (kembalian > 0) {
                                    notif.style.display = '';
                                    notif.className = 'alert alert-info py-1 px-2 mb-0';
                                    notif.innerHTML = 'Kembalian: <b>Rp' + kembalian.toLocaleString('id-ID') + '</b>';
                                } else {
                                    notif.style.display = 'none';
                                }
                            }
                        })();
                        </script>
                        <button type="submit" name="simpan_pembayaran" class="btn btn-success">Simpan</button>
                        <a href="?page=pesanan_detail&id=<?= $id ?>" class="btn btn-secondary">Kembali</a>
                    </form>
                    <?php else: ?>
                        <div class="alert alert-info mt-3">Pembayaran sudah lunas atau pesanan tidak dapat menerima pembayaran saat ini.</div>
                        <a href="?page=pesanan_detail&id=<?= $id ?>" class="btn btn-secondary">Kembali</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-7">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Riwayat Pembayaran</h5>
                    <?php if (empty($logs)): ?>
                        <p>Belum ada riwayat pembayaran.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped">
                                <thead>
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Jumlah</th>
                                        <th>Diterima Oleh</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($logs as $log): ?>
                                        <tr>
                                            <td><?= date('d-m-Y H:i', strtotime($log['tanggal_bayar'])) ?></td>
                                            <td>Rp<?= number_format($log['jumlah_bayar'], 0, ',', '.') ?></td>
                                            <td><?= htmlspecialchars($log['nama_lengkap']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function formatRupiah(angka, prefix){
    var number_string = angka.replace(/[^\d]/g, '').toString(),
    split   	= number_string.split(','),
    sisa     	= split[0].length % 3,
    rupiah     	= split[0].substr(0, sisa),
    ribuan     	= split[0].substr(sisa).match(/\d{3}/gi);

    if(ribuan){
        separator = sisa ? '.' : '';
        rupiah += separator + ribuan.join('.');
    }

    rupiah = split[1] != undefined ? rupiah + ',' + split[1] : rupiah;
    return prefix == undefined ? rupiah : (rupiah ? 'Rp. ' + rupiah : '');
}
</script>
