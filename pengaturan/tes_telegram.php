<?php
require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/telegram_notif.php'; // WAJIB!
require_once __DIR__ . '/telegram_utils.php';

// Ganti dengan chat_id Telegram Anda
$chat_id = '1916343560';
$pesan = "Tes notifikasi Telegram via send_telegram_message!";

global $TELEGRAM_BOT_TOKEN;
$result = send_telegram_message($TELEGRAM_BOT_TOKEN, $chat_id, $pesan, 'HTML');

if ($result) {
    echo "<b>Berhasil mengirim notifikasi ke Telegram!</b>";
} else {
    echo "<b style='color:red'>Gagal mengirim notifikasi ke Telegram!</b>";
}
?>