<?php
/**
 * admin/api/list_artikel_images.php
 * Mengembalikan daftar file gambar artikel dari Cloudflare R2 bucket parokitulungagung/artikel
 * (dengan fallback membaca folder lokal /img/artikel/ jika R2 belum terhubung)
 * untuk dipakai oleh ArtikelImagePicker
 */
ob_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
adminBoot();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Service-Worker-Allowed: /admin/');
header('Vary: X-Requested-With');
apiRequireLogin();

$imgExts   = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
$files     = [];
$cdnBase   = defined('R2_CDN_URL') ? rtrim(R2_CDN_URL, '/') : 'https://img.parokitulungagung.org';
$r2Prefix  = defined('R2_ARTIKEL_PREFIX') ? trim(R2_ARTIKEL_PREFIX, '/') . '/' : 'artikel/';

$ak = defined('R2_ACCESS_KEY') ? R2_ACCESS_KEY : (defined('SECRET_R2_ACCESS_KEY_WRITE') ? SECRET_R2_ACCESS_KEY_WRITE : null);
$sk = defined('R2_SECRET_KEY') ? R2_SECRET_KEY : (defined('SECRET_R2_SECRET_KEY_WRITE') ? SECRET_R2_SECRET_KEY_WRITE : null);
$ep = defined('R2_ENDPOINT')   ? R2_ENDPOINT   : (defined('SECRET_R2_ENDPOINT') ? SECRET_R2_ENDPOINT : null);
$bk = defined('R2_BUCKET')     ? R2_BUCKET     : (defined('SECRET_R2_BUCKET') ? SECRET_R2_BUCKET : null);

if ($ak && $sk && $ep && $bk) {
    try {
        require_once __DIR__ . '/../../includes/R2Client.php';
        $r2 = new R2Client($ak, $sk, $ep, $bk);
        $objects = $r2->listObjects($r2Prefix);
        foreach ($objects as $o) {
            $key = $o['key'] ?? '';
            $nm  = basename($key);
            if ($nm === '' || $nm[0] === '.') continue;
            $ext = strtolower(pathinfo($nm, PATHINFO_EXTENSION));
            if (!in_array($ext, $imgExts)) continue;

            $modTime = !empty($o['lastModified']) ? strtotime($o['lastModified']) : 0;
            $files[] = [
                'name'     => $nm,
                'url'      => $cdnBase . '/' . $r2Prefix . $nm,
                'size_kb'  => round(($o['size'] ?? 0) / 1024, 1),
                'modified' => $modTime,
            ];
        }
    } catch (Throwable $e) {
        error_log('[list_artikel_images] R2 list failed: ' . $e->getMessage());
    }
}

// Fallback jika R2 kosong atau error: baca dari disk lokal
if (empty($files)) {
    $imgDir = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/img/artikel/';
    if (is_dir($imgDir)) {
        $raw = scandir($imgDir);
        foreach ($raw as $nm) {
            if ($nm[0] === '.') continue;
            $fp  = $imgDir . $nm;
            if (!is_file($fp)) continue;
            $ext = strtolower(pathinfo($nm, PATHINFO_EXTENSION));
            if (!in_array($ext, $imgExts)) continue;
            $files[] = [
                'name'     => $nm,
                'url'      => $cdnBase . '/' . $r2Prefix . $nm,
                'size_kb'  => round(filesize($fp) / 1024, 1),
                'modified' => filemtime($fp),
            ];
        }
    }
}

// Urutkan terbaru dulu berdasarkan modified time
usort($files, fn($a, $b) => ($b['modified'] ?? 0) <=> ($a['modified'] ?? 0));

ob_end_clean();
apiJson(['success' => true, 'files' => $files, 'count' => count($files)]);
