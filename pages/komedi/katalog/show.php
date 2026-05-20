<?php
// Izinkan www dan non-www
$allowedReferers = [
    'https://www.parokitulungagung.org',
    'https://parokitulungagung.org',
];

$referer = $_SERVER['HTTP_REFERER'] ?? '';
$allowed = false;
foreach ($allowedReferers as $r) {
    if (strpos($referer, $r) === 0) {
        $allowed = true;
        break;
    }
}

if (!$allowed) {
    header('HTTP/1.0 403 Forbidden');
    exit('403 Forbidden');
}

// Tangkap path dari parameter GET
$requestedPath = $_GET['file'] ?? '';
$sanitizedPath = str_replace('..', '', $requestedPath);
$fullPath = __DIR__ . '/private/' . $sanitizedPath;

if (file_exists($fullPath) && is_file($fullPath)) {
    $mime = mime_content_type($fullPath);
    header("Content-Type: $mime");
    readfile($fullPath);
} else {
    header("HTTP/1.0 404 Not Found");
    echo "File tidak ditemukan.";
}