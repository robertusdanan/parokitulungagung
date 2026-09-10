<?php
/**
 * admin/api/upload_umkm.php
 * Upload gambar/banner/flyer UMKM ke /public/umkm/
 * Kompres otomatis, max 1200×900px, target < 200KB
 * Format: WebP jika tersedia, fallback JPEG
 */
ob_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
adminBoot();

header('Content-Type: application/json; charset=utf-8');
$currentUser = apiRequirePageAccess('umkm', 'create');

if (!function_exists('imagecreatefromjpeg')) {
    ob_end_clean(); apiJson(['error' => 'GD Library tidak tersedia di server.'], 500);
}

$file = $_FILES['image'] ?? null;

if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
    $errMap = [
        UPLOAD_ERR_INI_SIZE  => 'File terlalu besar (batas php.ini). Gunakan gambar maksimal 20MB.',
        UPLOAD_ERR_FORM_SIZE => 'File terlalu besar.',
        UPLOAD_ERR_PARTIAL   => 'Upload tidak lengkap.',
        UPLOAD_ERR_NO_FILE   => 'Tidak ada file dipilih.',
        UPLOAD_ERR_CANT_WRITE=> 'Server tidak bisa menulis file.',
    ];
    ob_end_clean();
    apiJson(['error' => $errMap[$file['error'] ?? 0] ?? 'Upload gagal.'], 400);
}

if ($file['size'] > 20 * 1024 * 1024) {
    ob_end_clean();
    apiJson(['error' => 'File terlalu besar (' . round($file['size']/1024/1024,1) . 'MB). Maks 20MB. Gambar dikompres otomatis setelah upload.'], 400);
}

// Deteksi MIME
$mimeType = '';
if (function_exists('finfo_open')) {
    $fi = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($fi, $file['tmp_name']);
    finfo_close($fi);
} else {
    $h = @fopen($file['tmp_name'], 'rb');
    if ($h) {
        $hdr = fread($h, 12); fclose($h);
        if (substr($hdr,0,2) === "\xFF\xD8")                              $mimeType = 'image/jpeg';
        elseif (substr($hdr,0,4) === "\x89PNG")                           $mimeType = 'image/png';
        elseif (substr($hdr,0,4)==='RIFF' && substr($hdr,8,4)==='WEBP')   $mimeType = 'image/webp';
        else $mimeType = @mime_content_type($file['tmp_name']) ?: 'unknown';
    }
}

if (!in_array($mimeType, ['image/jpeg','image/png','image/webp'])) {
    ob_end_clean();
    apiJson(['error' => 'Format tidak didukung. Gunakan JPG, PNG, atau WebP.'], 400);
}

// Generate nama file slug
$base = pathinfo($file['name'] ?? 'umkm', PATHINFO_FILENAME);
$map  = ['à'=>'a','á'=>'a','è'=>'e','é'=>'e','ì'=>'i','í'=>'i','ò'=>'o','ó'=>'o','ù'=>'u','ú'=>'u','ñ'=>'n','ç'=>'c'];
$slug = strtolower(strtr($base, $map));
$slug = preg_replace('/[^a-z0-9\-\s]/', '', $slug);
$slug = preg_replace('/[\s\-]+/', '-', trim($slug));
$slug = substr($slug, 0, 60) ?: 'umkm';

$webpOk = function_exists('imagewebp') && function_exists('imagecreatefromwebp');
$ext    = $webpOk ? 'webp' : 'jpg';

$suffix   = date('Ymd_His');
$filename = $slug . '-' . $suffix . '.' . $ext;
$r2Prefix = defined('R2_UMKM_PREFIX') ? trim(R2_UMKM_PREFIX, '/') : 'umkm';
$r2Key    = $r2Prefix . '/' . $filename;

// Load & proses gambar
$origKb = round($file['size'] / 1024, 1);
$src = null;
if ($mimeType === 'image/jpeg')                        $src = @imagecreatefromjpeg($file['tmp_name']);
elseif ($mimeType === 'image/png')                     $src = @imagecreatefrompng($file['tmp_name']);
elseif ($mimeType === 'image/webp' && $webpOk)         $src = @imagecreatefromwebp($file['tmp_name']);

// GD gagal → upload file mentah langsung ke R2
if (!$src) {
    $rawExt  = $mimeType === 'image/png' ? 'png' : ($mimeType === 'image/webp' ? 'webp' : 'jpg');
    $rawName = $slug . '-' . $suffix . '.' . $rawExt;
    $rawKey  = $r2Prefix . '/' . $rawName;
    try {
        $r2 = getR2WriteClient();
        $r2->putObjectFromFile($rawKey, $file['tmp_name'], $mimeType, ['uploaded-via' => 'admin-umkm-raw']);
    } catch (Throwable $e) {
        ob_end_clean();
        apiJson(['error' => 'Gagal upload ke R2: ' . $e->getMessage()], 500);
    }
    $cdnUrl = (defined('R2_CDN_URL') ? rtrim(R2_CDN_URL, '/') : 'https://img.parokitulungagung.org') . '/' . $rawKey;
    ob_end_clean();
    apiJson([
        'success'    => true,
        'filename'   => $rawName,
        'url'        => $cdnUrl,
        'size_kb'    => $origKb,
        'orig_kb'    => $origKb,
        'saved_pct'  => 0,
        'format'     => strtoupper($rawExt),
        'dimensions' => 'original'
    ]);
}

$origW = imagesx($src); $origH = imagesy($src);

// Max 1200×900 — landscape friendly untuk banner/flyer
$maxW = 1200; $maxH = 900; $quality = 82;
if ($origW > $maxW || $origH > $maxH) {
    $scale = min($maxW/$origW, $maxH/$origH);
    $newW  = max(1,(int)($origW*$scale));
    $newH  = max(1,(int)($origH*$scale));
} else { $newW=$origW; $newH=$origH; }

$dst   = imagecreatetruecolor($newW, $newH);
$white = imagecolorallocate($dst, 255, 255, 255);
imagefilledrectangle($dst, 0, 0, $newW, $newH, $white);
if ($mimeType === 'image/png') imagealphablending($dst, true);
imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
imagedestroy($src);

$tmpSave = tempnam(sys_get_temp_dir(), 'umkm_') . '.' . $ext;
if ($webpOk) $saved = imagewebp($dst, $tmpSave, $quality);
else         $saved = imagejpeg($dst, $tmpSave, $quality);
imagedestroy($dst);

if (!$saved || !file_exists($tmpSave)) {
    @unlink($tmpSave);
    ob_end_clean(); apiJson(['error' => 'Gagal mengompres gambar.'], 500);
}

$finalKb  = round(filesize($tmpSave) / 1024, 1);
$savedPct = $origKb > 0 ? max(0, round((($origKb-$finalKb)/$origKb)*100)) : 0;

try {
    $r2 = getR2WriteClient();
    $r2->putObjectFromFile($r2Key, $tmpSave, $webpOk ? 'image/webp' : 'image/jpeg', ['uploaded-via' => 'admin-umkm']);
} catch (Throwable $e) {
    @unlink($tmpSave);
    ob_end_clean();
    apiJson(['error' => 'Gagal upload ke Cloudflare R2: ' . $e->getMessage()], 500);
}
@unlink($tmpSave);

$cdnUrl = (defined('R2_CDN_URL') ? rtrim(R2_CDN_URL, '/') : 'https://img.parokitulungagung.org') . '/' . $r2Key;

getLogger()->log($currentUser, 'CREATE', 'umkm', 'Upload gambar UMKM: ' . $filename);

ob_end_clean();
apiJson([
    'success'    => true,
    'filename'   => $filename,
    'url'        => $cdnUrl,
    'size_kb'    => $finalKb,
    'orig_kb'    => $origKb,
    'saved_pct'  => $savedPct,
    'format'     => $webpOk ? 'WebP' : 'JPEG',
    'dimensions' => $newW . '×' . $newH . 'px',
]);