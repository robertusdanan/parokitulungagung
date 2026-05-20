<?php
/**
 * admin/api/list_images.php
 * Daftar gambar yang sudah ada di folder tertentu untuk media browser.
 *
 * GET params:
 *   folder — 'banner' atau 'icon'
 *   page   — halaman (default 1)
 *
 * Respons JSON:
 *   { success: true, images: [...], total: N, page: N, pages: N }
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
adminBoot();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
apiRequireLogin();

$user = requireLogin();

$ALLOWED_FOLDERS = [
    'banner' => '/img/banner',
    'icon'   => '/img/icon/kategorial',
    'person' => '/img/person',
];

$folderParam = $_GET['folder'] ?? '';
if (!array_key_exists($folderParam, $ALLOWED_FOLDERS)) {
    apiJson(['error' => 'Folder tidak valid'], 400);
}

$relFolder = $ALLOWED_FOLDERS[$folderParam];
$absFolder = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . $relFolder;

if (!is_dir($absFolder)) {
    apiJson(['success' => true, 'images' => [], 'total' => 0, 'page' => 1, 'pages' => 0]);
}

$ALLOWED_EXTS = ['jpg','jpeg','png','gif','webp'];
$files = [];

$dir = opendir($absFolder);
while (($file = readdir($dir)) !== false) {
    if ($file === '.' || $file === '..') continue;
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    if (!in_array($ext, $ALLOWED_EXTS)) continue;
    $absPath = $absFolder . '/' . $file;
    $files[] = [
        'name'     => $file,
        'url'      => $relFolder . '/' . $file,
        'size'     => filesize($absPath),
        'modified' => filemtime($absPath),
    ];
}
closedir($dir);

// Urutkan: terbaru dulu
usort($files, fn($a, $b) => $b['modified'] - $a['modified']);

$perPage = 24;
$page    = max(1, (int)($_GET['page'] ?? 1));
$total   = count($files);
$pages   = (int)ceil($total / $perPage);
$sliced  = array_slice($files, ($page - 1) * $perPage, $perPage);

// Format ukuran
foreach ($sliced as &$f) {
    $bytes = $f['size'];
    $f['size_fmt'] = $bytes < 1024
        ? $bytes . ' B'
        : ($bytes < 1024*1024
            ? round($bytes/1024, 1) . ' KB'
            : round($bytes/(1024*1024), 2) . ' MB');
    unset($f['modified']);
}

apiJson([
    'success' => true,
    'images'  => $sliced,
    'total'   => $total,
    'page'    => $page,
    'pages'   => $pages,
]);