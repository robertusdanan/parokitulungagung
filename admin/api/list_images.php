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
    'banner'         => '/assets/banner',
    'assets'         => '/assets',
    'icon'           => '/icon',                      // R2 prefix (icon root)
    'icon_kategorial'=> '/icon/kategorial',           // R2 prefix (icon kategorial)
    'person'         => '/person',                    // R2 prefix
    'artikel'        => '/artikel',                   // R2 prefix
    'ogpreview'      => '/ogpreview',                 // R2 prefix
];

$folderParam = $_GET['folder'] ?? '';
if (!array_key_exists($folderParam, $ALLOWED_FOLDERS)) {
    apiJson(['error' => 'Folder tidak valid'], 400);
}

$relFolder = $ALLOWED_FOLDERS[$folderParam];
$isR2Folder = true;
$files = [];
$ALLOWED_EXTS = ['jpg','jpeg','png','gif','webp'];

if ($isR2Folder) {
    // ── Ambil list dari R2 ───────────────────────────────────────
    $ak = defined('R2_ACCESS_KEY') ? R2_ACCESS_KEY : (defined('SECRET_R2_ACCESS_KEY_WRITE') ? SECRET_R2_ACCESS_KEY_WRITE : null);
    $sk = defined('R2_SECRET_KEY') ? R2_SECRET_KEY : (defined('SECRET_R2_SECRET_KEY_WRITE') ? SECRET_R2_SECRET_KEY_WRITE : null);
    $ep = defined('R2_ENDPOINT')   ? R2_ENDPOINT   : (defined('SECRET_R2_ENDPOINT') ? SECRET_R2_ENDPOINT : null);
    $bk = defined('R2_BUCKET')     ? R2_BUCKET     : (defined('SECRET_R2_BUCKET') ? SECRET_R2_BUCKET : null);
    if (!$ak || !$sk || !$ep || !$bk) {
        apiJson(['success' => true, 'images' => [], 'total' => 0, 'page' => 1, 'pages' => 0]);
    }
    try {
        require_once __DIR__ . '/../../includes/R2Client.php';
        $r2 = new R2Client($ak, $sk, $ep, $bk);
        // Tentukan prefix R2 sesuai folder
        $prefix = match ($folderParam) {
            'icon'           => (defined('R2_ICON_PREFIX')     ? R2_ICON_PREFIX     : 'icon/'),
            'icon_kategorial'=> (defined('R2_ICON_KAT_PREFIX') ? R2_ICON_KAT_PREFIX : 'icon/kategorial/'),
            'person'         => (defined('R2_PERSON_PREFIX')   ? R2_PERSON_PREFIX   : 'person/'),
            'artikel'        => (defined('R2_ARTIKEL_PREFIX')  ? R2_ARTIKEL_PREFIX  : 'artikel/'),
            'ogpreview'      => (defined('R2_OG_PREFIX')       ? R2_OG_PREFIX       : 'ogpreview/'),
            default          => ltrim($relFolder, '/') . '/',
        };
        $objects = $r2->listObjects($prefix);
        if (empty($objects) && $folderParam === 'icon_kategorial') {
            // Fallback ke prefix icon/ jika folder kategorial spesifik belum terisi
            $objects = $r2->listObjects(defined('R2_ICON_PREFIX') ? R2_ICON_PREFIX : 'icon/');
        }
        foreach ($objects as $o) {
            $key = $o['key'] ?? '';
            $nm  = basename($key);
            if ($nm === '' || $nm[0] === '.') continue;
            $ext = strtolower(pathinfo($nm, PATHINFO_EXTENSION));
            if (!in_array($ext, $ALLOWED_EXTS)) continue;
            $files[] = [
                'name'     => $nm,
                'url'      => rtrim(defined('R2_CDN_URL') ? R2_CDN_URL : '', '/') . '/' . ltrim($key, '/'),
                'size'     => $o['size'] ?? 0,
                'modified' => isset($o['lastModified']) ? strtotime($o['lastModified']) : 0,
            ];
        }
    } catch (Throwable $e) {
        error_log('[admin/list_images.php] ' . $e->getMessage());
    }
} else {
    // ── Ambil dari disk lokal (banner) ────────────────────────────
    $absFolder = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . $relFolder;
    if (is_dir($absFolder)) {
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
    }
}

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