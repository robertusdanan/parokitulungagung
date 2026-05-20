<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: text/plain; charset=utf-8');

$url = $_GET['url'] ?? '';
if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) {
    http_response_code(400);
    echo $url;
    exit;
}

$apiUrl = 'https://is.gd/create.php?format=simple&url=' . urlencode($url);
$result = @file_get_contents($apiUrl);

if ($result && str_starts_with($result, 'https://is.gd/')) {
    echo trim($result);
} else {
    echo $url; // fallback ke link asli
}