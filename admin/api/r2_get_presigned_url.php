<?php
/**
 * admin/api/r2_get_presigned_url.php
 * Media Manager > Cloudflare R2 — Presigned PUT URL Generator for Direct Video Upload
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
adminBoot();

header('Content-Type: application/json; charset=utf-8');

$user = apiRequirePageAccess('media', 'create');

require_once __DIR__ . '/../../includes/R2WriteClient.php';
require_once __DIR__ . '/../../includes/R2FolderCompressor.php';
require_once __DIR__ . '/../includes/R2AlbumCache.php';

if (!defined('SECRET_R2_ACCESS_KEY_WRITE') || !defined('SECRET_R2_SECRET_KEY_WRITE')
    || !defined('SECRET_R2_ENDPOINT') || !defined('SECRET_R2_BUCKET')) {
    apiJson(['error' => 'Kredensial R2 (write) belum diatur di private/secrets.php.'], 500);
}

$folder = trim((string)($_POST['folder'] ?? ''));
$relpath = trim((string)($_POST['relpath'] ?? ''));

if ($folder === '' || $relpath === '') {
    apiJson(['error' => 'Nama folder / file tidak boleh kosong.'], 400);
}

function sanitizeR2Path(string $path): string
{
    $path = str_replace("\0", '', $path);
    $path = str_replace('\\', '/', $path);
    $segments = explode('/', $path);
    $clean = [];
    foreach ($segments as $seg) {
        $seg = trim($seg);
        if ($seg === '' || $seg === '.' || $seg === '..') continue;
        $seg = preg_replace('/[\x00-\x1F\x7F]/u', '', $seg) ?? $seg;
        if ($seg !== '') $clean[] = $seg;
    }
    return implode('/', $clean);
}

$folderClean = sanitizeR2Path($folder);
$relpathClean = sanitizeR2Path($relpath);

if ($folderClean === '' || $relpathClean === '') {
    apiJson(['error' => 'Nama path tidak valid setelah sanitasi.'], 400);
}

$originalExt = strtolower(pathinfo($relpathClean, PATHINFO_EXTENSION));
$safeReuseExts = ['mp4', 'mov', 'm4v', 'mkv'];
$outExt = in_array($originalExt, $safeReuseExts, true) ? $originalExt : 'mp4';
$relpathOutExt = $relpathClean;
if ($outExt !== $originalExt) {
    $rDir  = pathinfo($relpathClean, PATHINFO_DIRNAME);
    $rName = pathinfo($relpathClean, PATHINFO_FILENAME) . '.' . $outExt;
    $relpathOutExt = ($rDir !== '.' ? $rDir . '/' : '') . $rName;
}

$stagingKey = R2_PENDING_VIDEO_PREFIX . $folderClean . '/' . $relpathOutExt;
$finalKey = R2_ALBUM_PREFIX . $folderClean . '/' . $relpathOutExt;

$r2 = new R2WriteClient(SECRET_R2_ACCESS_KEY_WRITE, SECRET_R2_SECRET_KEY_WRITE, SECRET_R2_ENDPOINT, SECRET_R2_BUCKET);
$presignedPutUrl = $r2->getPresignedPutUrl($stagingKey, 3600);

if (!isset($_SESSION['albums_with_pending_video']) || !is_array($_SESSION['albums_with_pending_video'])) {
    $_SESSION['albums_with_pending_video'] = [];
}
$_SESSION['albums_with_pending_video'][$folderClean] = true;
session_write_close();

r2AlbumCacheInvalidateStats($folderClean);
r2AlbumCacheAddNameIfMissing($folderClean);

apiJson([
    'success'       => true,
    'presigned_url' => $presignedPutUrl,
    'staging_key'   => $stagingKey,
    'final_key'     => $finalKey,
    'folder'        => $folderClean,
    'relpath'       => $relpathClean,
]);
