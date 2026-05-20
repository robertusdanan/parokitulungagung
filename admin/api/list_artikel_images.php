<?php
/**
 * admin/api/list_artikel_images.php
 * Mengembalikan daftar file gambar di /img/artikel/
 * untuk dipakai oleh ArtikelImagePicker
 */
ob_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
adminBoot();

// Cegah service worker mencache / mencegat response ini
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Service-Worker-Allowed: /admin/');   // izinkan SW hanya di /admin
header('Vary: X-Requested-With');
apiRequireLogin();

$imgDir  = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/img/artikel/';
$imgExts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
$files   = [];

if (is_dir($imgDir)) {
    $raw = scandir($imgDir);
    rsort($raw); // terbaru dulu (nama file pakai uniqid/timestamp)
    foreach ($raw as $nm) {
        if ($nm[0] === '.') continue;
        $fp  = $imgDir . $nm;
        if (!is_file($fp)) continue;
        $ext = strtolower(pathinfo($nm, PATHINFO_EXTENSION));
        if (!in_array($ext, $imgExts)) continue;
        $files[] = [
            'name'     => $nm,
            'url'      => '/img/artikel/' . $nm,
            'size_kb'  => round(filesize($fp) / 1024, 1),
            'modified' => filemtime($fp),
        ];
    }
}

ob_end_clean();
apiJson(['success' => true, 'files' => $files, 'count' => count($files)]);