<?php
// Reminder otomatis laundry: pengambilan laundry selesai >2 hari, belum diambil
if (php_sapi_name() !== 'cli') {
    // Biar tidak bisa diakses via web
    exit('Akses hanya via CLI/cron');
}

include dirname(__DIR__).'/pengaturan/koneksi.php';
include dirname(__DIR__).'/pengaturan/telegram_notif.php';

// Helper notifikasi (pastikan sama dengan standar aplikasi)
function kirim_notifikasi_pelanggan($id_pelanggan, $pesan, $channel = 'Telegram') {
    global $konek;
    // Ambil id_telegram dari pelanggan
    $q = mysqli_query($konek, "SELECT id_telegram FROM pelanggan WHERE id_pelanggan='".intval($id_pelanggan)."' LIMIT 1");
    $r = mysqli_fetch_assoc($q);
    if ($r && !empty($r['id_telegram'])) {
        return send_telegram($r['id_telegram'], $pesan);
    }
    return false;
}
function catat_notifikasi($konek, $id_pesanan, $id_pelanggan, $pesan, $channel = 'Telegram', $tipe = 'Reminder') {
    $pesan_sql = mysqli_real_escape_string($konek, $pesan);
    $channel_sql = mysqli_real_escape_string($konek, $channel);
    $tipe_sql = mysqli_real_escape_string($konek, $tipe);
    $id_pesanan_sql = is_null($id_pesanan) ? 'NULL' : "'".intval($id_pesanan)."'";
    $query = "INSERT INTO notifikasi (id_pesanan, id_pelanggan, pesan, waktu_kirim, channel, tipe_notifikasi, status_pengiriman)
              VALUES ($id_pesanan_sql, '$id_pelanggan', '$pesan_sql', NOW(), '$channel_sql', '$tipe_sql', 'Terkirim')";
    mysqli_query($konek, $query);
}

$tgl_sekarang = date('Y-m-d');
$query = "SELECT p.id_pesanan, p.id_pelanggan, p.nomor_invoice, p.tanggal_selesai_aktual, pl.nama_pelanggan
          FROM pesanan p
          JOIN pelanggan pl ON p.id_pelanggan = pl.id_pelanggan
          WHERE p.status_pesanan_umum = 'Selesai'
            AND p.tanggal_diambil IS NULL
            AND p.tanggal_selesai_aktual IS NOT NULL
            AND DATEDIFF('$tgl_sekarang', p.tanggal_selesai_aktual) >= 2";

$result = mysqli_query($konek, $query);
if (!$result) {
    die("Query error: " . mysqli_error($konek) . "\nQuery: $query\n");
}
$total_reminder = 0;
while ($row = mysqli_fetch_assoc($result)) {
    $pesan = "Reminder: Laundry Anda dengan nota #" . $row['nomor_invoice'] . " sudah selesai sejak " . date('d/m/Y', strtotime($row['tanggal_selesai_aktual'])) . ". Silakan segera diambil. Terima kasih.";
    // Cek duplikasi reminder hari yang sama
    $cek = mysqli_query($konek, "SELECT 1 FROM notifikasi WHERE id_pesanan='" . $row['id_pesanan'] . "' AND tipe_notifikasi='Reminder' AND DATE(waktu_kirim)='$tgl_sekarang'");
    if (!$cek) {
        die("Query error (notifikasi): " . mysqli_error($konek) . "\nQuery: SELECT 1 FROM notifikasi WHERE id_pesanan='" . $row['id_pesanan'] . "' AND tipe_notifikasi='Reminder' AND DATE(waktu_kirim)='$tgl_sekarang'");
    }
    if (mysqli_num_rows($cek) == 0) {
        if (kirim_notifikasi_pelanggan($row['id_pelanggan'], $pesan, 'Telegram')) {
            catat_notifikasi($konek, $row['id_pesanan'], $row['id_pelanggan'], $pesan, 'Telegram', 'Reminder');
            $total_reminder++;
        }
    }
}
echo "Total reminder terkirim: $total_reminder\n";
