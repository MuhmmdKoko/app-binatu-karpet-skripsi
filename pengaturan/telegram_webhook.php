<?php
// File: pengaturan/telegram_webhook.php
// Webhook untuk menghubungkan akun Telegram pelanggan ke sistem laundry
// Pastikan file ini bisa diakses publik (untuk webhook Telegram)

include_once __DIR__ . "/koneksi.php";

// Ambil data POST dari Telegram
$content = file_get_contents("php://input");
$update = json_decode($content, true);
if (!$update) exit;

// Ambil chat_id, username, dan pesan
$chat_id = $update["message"]["chat"]["id"] ?? null;
$username = $update["message"]["from"]["username"] ?? '';
$text = trim($update["message"]["text"] ?? '');

// Token bot Telegram
$token = '7661383573:AAEhfcpIkKD6AvdNr-3LBJ0Pl4FBo42S_Bw';

// Fungsi kirim pesan balasan
function reply($chat_id, $pesan, $token) {
    $url = "https://api.telegram.org/bot$token/sendMessage";
    $data = ["chat_id" => $chat_id, "text" => $pesan];
    file_get_contents($url . "?" . http_build_query($data));
}

// 1. Jika /start atau /hubungkan, minta user balas dengan nomor HP
if (in_array(strtolower($text), ["/start", "/hubungkan"])) {
    reply($chat_id, "Silakan balas pesan ini dengan nomor HP Anda yang terdaftar di laundry kami untuk menghubungkan akun Telegram Anda.", $token);
    exit;
}

// 2. Jika user membalas dengan nomor HP
if (preg_match('/^[0-9]{10,15}$/', $text)) {
    $hp = $text;
    // Update id_telegram di database
    $q = mysqli_query($konek, "UPDATE pelanggan SET id_telegram='$chat_id' WHERE nomor_telepon='$hp'");
    if (mysqli_affected_rows($konek) > 0) {
        reply($chat_id, "Akun Telegram Anda berhasil dihubungkan dengan sistem laundry!", $token);
    } else {
        reply($chat_id, "Nomor HP tidak ditemukan di database. Pastikan sudah terdaftar.", $token);
    }
    exit;
}

// 3. Default: info bantuan
if ($chat_id) {
    reply($chat_id, "Ketik /hubungkan untuk menghubungkan akun Telegram Anda ke sistem laundry.", $token);
}
