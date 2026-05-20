<?php
// Bersihkan semua buffer output agar JSON tidak kotor
while (ob_get_level()) ob_end_clean();

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';


header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

apiRequireLogin();

$dir  = __DIR__ . '/../../public/galeri/';
$exts = ['jpg','jpeg','png','webp','gif'];
$files = [];

if (is_dir($dir)) {
    $raw = scandir($dir);
    rsort($raw); // terbaru dulu

    $limit = 80;
    $count = 0;

    foreach ($raw as $f) {
        if ($f === '.' || $f === '..') continue;

        $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
        if (!in_array($ext, $exts)) continue;

        $path = $dir . $f;
        if (!is_file($path)) continue;

        $size  = @filesize($path);
        $mtime = @filemtime($path);

        $files[] = [
            'filename' => $f,
            'url'      => '/public/galeri/' . rawurlencode($f),
            'size_kb'  => $size ? round($size / 1024) : 0,
            'mtime'    => $mtime ?: 0,
        ];

        $count++;
        if ($count >= $limit) break;
    }
}

echo json_encode([
    'success' => true,
    'files'   => $files,
    'count'   => count($files)
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

exit;