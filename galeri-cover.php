<?php
/**
 * galeri-cover.php — Legacy Proxy / Redirect ke Cloudflare R2 CDN
 * Mengalihkan request cover album lama langsung ke CDN R2 _thumbnails/galeri/
 */
require_once __DIR__ . '/includes/config.php';

$file = $_GET['file'] ?? '';
if ($file !== '') {
    $fn  = basename(str_replace('\\', '/', $file));
    $cdn = defined('R2_CDN_URL') ? rtrim(R2_CDN_URL, '/') : 'https://img.parokitulungagung.org';
    $prefix = defined('R2_GALERI_THUMB_PREFIX') ? trim(R2_GALERI_THUMB_PREFIX, '/') : '_thumbnails/galeri';
    header('Location: ' . $cdn . '/' . $prefix . '/' . rawurlencode($fn), true, 302);
    exit;
}

http_response_code(404);
header('Content-Type: text/plain');
echo 'File not found';
