<?php
/**
 * admin/api/list_galeri_media.php
 * List thumbnail galeri dari Cloudflare R2 (prefix: _thumbnails/galeri/)
 * Menggantikan scan folder lokal /public/galeri/ yang sudah tidak dipakai.
 */
while (ob_get_level()) ob_end_clean();

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
adminBoot(); // wajib — me-load auth.php (apiRequireLogin, apiJson, dll)

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

apiRequireLogin();

if (!defined('SECRET_R2_ACCESS_KEY_WRITE') || !defined('SECRET_R2_SECRET_KEY_WRITE')) {
    echo json_encode(['success' => false, 'error' => 'Kredensial R2 belum diatur.']);
    exit;
}

require_once __DIR__ . '/../../includes/R2WriteClient.php';

try {
    $r2 = new R2WriteClient(
        SECRET_R2_ACCESS_KEY_WRITE,
        SECRET_R2_SECRET_KEY_WRITE,
        SECRET_R2_ENDPOINT,
        SECRET_R2_BUCKET
    );

    $prefix  = '_thumbnails/galeri/';
    $objects = $r2->listAllObjects($prefix);

    // Sort terbaru dulu
    usort($objects, fn($a, $b) => strcmp($b['lastModified'], $a['lastModified']));

    $cdnBase = rtrim(R2_CDN_URL, '/');
    $files   = [];
    $rawBody = json_decode(file_get_contents('php://input'), true);
    $limitParam = isset($rawBody['limit']) ? (int)$rawBody['limit'] : (isset($_GET['limit']) ? (int)$_GET['limit'] : 0);
    $sliceList = ($limitParam > 0) ? array_slice($objects, 0, $limitParam) : $objects;

    foreach ($sliceList as $obj) {
        $key      = $obj['key'];
        $filename = basename($key);
        $files[]  = [
            'filename' => $key,           // simpan r2key penuh (dipakai di kolom Gambar)
            'url'      => $cdnBase . '/' . $key,
            'size_kb'  => round($obj['size'] / 1024),
            'mtime'    => strtotime($obj['lastModified']) ?: 0,
        ];
    }

    echo json_encode([
        'success' => true,
        'files'   => $files,
        'count'   => count($objects),
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    error_log('[list_galeri_media] ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
exit;