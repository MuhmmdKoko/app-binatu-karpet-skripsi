<?php
// Utilitas pengiriman pesan Telegram

/**
 * Mengirim pesan ke Telegram menggunakan Bot API
 * @param string $bot_token Token bot Telegram
 * @param string $chat_id Chat ID penerima
 * @param string $message Pesan yang akan dikirim
 * @param string $parse_mode (optional) Mode parsing pesan (HTML/Markdown)
 * @return bool True jika sukses, false jika gagal
 */
function send_telegram_message($bot_token, $chat_id, $message, $parse_mode = 'HTML') {
    $debug_telegram_utils = true; // Set ke false jika tidak ingin debug detail
    $url = "https://api.telegram.org/bot{$bot_token}/sendMessage";
    $post_fields = [
        'chat_id' => $chat_id,
        'text' => $message,
        'parse_mode' => $parse_mode
    ];
    if ($debug_telegram_utils) {
        echo "<div style='color:purple'><b>[DEBUG TELEGRAM_UTILS]</b> URL: ".$url."<br>POST Fields: <pre>".htmlspecialchars(print_r($post_fields,1))."</pre></div>";
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $res = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);
    $json = json_decode($res, true);
    if ($debug_telegram_utils) {
        echo "<div style='color:orange'><b>[DEBUG TELEGRAM_UTILS]</b> Response Telegram: <pre>".htmlspecialchars($res)."</pre>";
        if ($err) echo "<div style='color:red'><b>[DEBUG TELEGRAM_UTILS]</b> cURL Error: ".htmlspecialchars($err)."</div>";
        echo "</div>";
    }
    if (!isset($json['ok']) || !$json['ok']) {
        error_log("[TELEGRAM_UTILS] Gagal kirim ke $chat_id: $res | Error: $err");
        return false;
    }
    return true;
}

