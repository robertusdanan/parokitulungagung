<?php
header("Content-Type: image/jpeg");
readfile(__DIR__ . '/default.jpg');

// === CONFIG SUPABASE ===
$SUPABASE_URL = SUPABASE_URL;
$SUPABASE_KEY = SUPABASE_ANON;

// FETCH DATA TIKET
$url = $SUPABASE_URL . "/rest/v1/ticketing?id=eq.$id&select=*";

$opts = [
    "http" => [
        "method" => "GET",
        "header" => "apikey: $SUPABASE_KEY\r\nAuthorization: Bearer $SUPABASE_KEY\r\n"
    ]
];

$response = file_get_contents($url, false, stream_context_create($opts));
$data = json_decode($response, true);

if (!$data || !isset($data[0])) {
    readfile(__DIR__ . '/default.jpg');
    exit;
}

$ticket = $data[0];

// DATA
$name   = strtoupper($ticket['name']);
$number = $ticket['ticket_number'];
$type   = strtolower($ticket['primary_ticket_type'] ?? 'reguler');

// === WARNA BERDASARKAN TIPE ===
$colors = [
    'vvip'    => [56,12,23],
    'vip'     => [28,28,28],
    'kelas1'  => [6,40,30],
    'reguler' => [30,41,59]
];

$bgColor = $colors[$type] ?? [20,20,20];

// === BUAT CANVAS ===
$width  = 1200;
$height = 630;

$image = imagecreatetruecolor($width, $height);

// COLOR
$bg   = imagecolorallocate($image, $bgColor[0], $bgColor[1], $bgColor[2]);
$white = imagecolorallocate($image, 255, 255, 255);
$accent = imagecolorallocate($image, 255, 215, 0);

// BACKGROUND
imagefilledrectangle($image, 0, 0, $width, $height, $bg);

// === LOAD FONT (WAJIB ADA FILE TTF) ===
$fontBold = __DIR__ . '/font-bold.ttf';
$fontReg  = __DIR__ . '/font-regular.ttf';

// FALLBACK kalau font tidak ada
if (!file_exists($fontBold)) {
    imagestring($image, 5, 50, 50, "E-TICKET", $white);
    imagejpeg($image);
    imagedestroy($image);
    exit;
}

// === TEXT ===

// TITLE
imagettftext($image, 42, 0, 60, 150, $accent, $fontBold, "E-TICKET RESMI");

// EVENT
imagettftext($image, 28, 0, 60, 220, $white, $fontReg, "Berbincang Dengan Romo Eko");

// NAME
imagettftext($image, 36, 0, 60, 340, $white, $fontBold, $name);

// NUMBER
imagettftext($image, 24, 0, 60, 400, $white, $fontReg, "No Tiket: " . $number);

// TYPE BADGE
imagettftext($image, 22, 0, 60, 460, $accent, $fontBold, strtoupper($type));

// FOOTER
imagettftext($image, 20, 0, 60, 560, $white, $fontReg, "Tunjukkan QR saat masuk");

// OUTPUT
imagejpeg($image, null, 90);
imagedestroy($image);