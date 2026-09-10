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
// SECURITY FIX: sebelumnya hanya string ".." yang dibuang sekali (rentan
// terhadap trik encoding/penataan ulang). Sekarang path diselesaikan dengan
// realpath() lalu dipastikan hasilnya BENAR-BENAR masih di dalam folder
// /private/ — pendekatan standar untuk mencegah path traversal, apapun
// bentuk inputnya.
$baseDir       = realpath(__DIR__ . '/private');
$requestedPath = $_GET['file'] ?? '';
$fullPath      = $baseDir !== false ? realpath($baseDir . '/' . $requestedPath) : false;

if ($baseDir !== false && $fullPath !== false
    && strncmp($fullPath, $baseDir . DIRECTORY_SEPARATOR, strlen($baseDir) + 1) === 0
    && is_file($fullPath)
) {
    $mime = mime_content_type($fullPath);
    header("Content-Type: $mime");
    readfile($fullPath);
} else {
    header("HTTP/1.0 404 Not Found");
    echo "File tidak ditemukan.";
}