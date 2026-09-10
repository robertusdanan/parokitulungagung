<?php

$primary = base64_decode('aHR0cHM6Ly9yb2JlcnR1c2RhbmFuLmdpdGh1Yi5pby9wcm9maWxlLw==');
$fallback = base64_decode('aHR0cHM6Ly9yb2JlcnR1c2RhbmFuLm9ucmVuZGVyLmNvbS8=');

$ch = curl_init($primary);

curl_setopt_array($ch, [
    CURLOPT_NOBODY => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 5,
    CURLOPT_FOLLOWLOCATION => true,
]);

curl_exec($ch);

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

curl_close($ch);

$url = ($httpCode >= 200 && $httpCode < 400)
    ? $primary
    : $fallback;

header("Location: $url");
exit;