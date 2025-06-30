<?php
// Utility untuk kirim notifikasi Telegram dari sistem
// Ganti dengan token bot Telegram Anda
$TELEGRAM_BOT_TOKEN = '7661383573:AAEhfcpIkKD6AvdNr-3LBJ0Pl4FBo42S_Bw'; // GANTI dengan token bot Anda

function send_telegram($id_telegram, $pesan, $token_bot = null) {
    global $TELEGRAM_BOT_TOKEN;
    if (!$token_bot) $token_bot = $TELEGRAM_BOT_TOKEN;
    $url = "https://api.telegram.org/bot$token_bot/sendMessage";
    $data = [
        'chat_id' => $id_telegram,
        'text' => $pesan,
        'parse_mode' => 'HTML'
    ];
    $options = [
        'http' => [
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data),
            'timeout' => 5
        ]
    ];
    $context  = stream_context_create($options);
    $result = @file_get_contents($url, false, $context);
    return $result ? json_decode($result, true) : false;
}

function send_telegram_pdf($id_telegram, $pdf_path, $caption = '', $token_bot = null) {
    global $TELEGRAM_BOT_TOKEN;
    if (!$token_bot) $token_bot = $TELEGRAM_BOT_TOKEN;
    $url = "https://api.telegram.org/bot$token_bot/sendDocument";
    $post_fields = [
        'chat_id'   => $id_telegram,
        'caption'   => $caption,
        'document'  => new CURLFile(realpath($pdf_path))
    ];
    $ch = curl_init(); 
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type:multipart/form-data"]);
    curl_setopt($ch, CURLOPT_URL, $url); 
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields); 
    $output = curl_exec($ch);
    curl_close($ch);
    return $output ? json_decode($output, true) : false;
}
