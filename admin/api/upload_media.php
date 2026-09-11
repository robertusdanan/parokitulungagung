<?php
/**
 * admin/api/upload_media.php
 *
 * Endpoint upload generik untuk halaman Media Manager (admin/pages/media.php)
 * Dipanggil oleh fungsi uploadFiles() di JS (line 1220 media.php) ketika admin
 * mengklik tombol "+" atau drop file ke zona upload.
 *
 * Menerima SATU file per request (file & folder), mengompres otomatis sesuai
 * aturan folder yang dipilih, dan menyimpannya ke:
 *   - Cloudflare R2 (prefix sesuai folder, lihat $FOLDERS_R2)
 *   - Disk lokal (folder /public/...)
 *
 * Folder yang didukung (sesuai dengan Media Manager):
 *   umkm, artikel, gereja, assets, person, icon, root_img (alias assets)
 *
 * POST (multipart/form-data):
 *   file   — file gambar (JPG / PNG / WebP / GIF)
 *   folder — kunci folder (mis. "umkm", "person", "gereja")
 *
 * Response JSON:
 *   { success: true, filename, url, size_kb, orig_kb, saved_pct, ... }
 *   { success: false, error: "..." }
 */

ob_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../../includes/R2WriteClient.php';
adminBoot();

header('Content-Type: application/json; charset=utf-8');
$currentUser = apiRequireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean();
    apiJson(['error' => 'Method not allowed'], 405);
}

// ── Cek GD tersedia ─────────────────────────────────────────────────────
if (!function_exists('imagecreatefromjpeg')) {
    ob_end_clean();
    apiJson(['error' => 'GD Library tidak tersedia di server.'], 500);
}

// ── Validasi file upload ─────────────────────────────────────────────────
$file = $_FILES['file'] ?? null;
if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
    $errMap = [
        UPLOAD_ERR_INI_SIZE   => 'File terlalu besar (melebihi batas php.ini).',
        UPLOAD_ERR_FORM_SIZE  => 'File terlalu besar (melebihi batas form).',
        UPLOAD_ERR_PARTIAL    => 'Upload tidak lengkap, coba lagi.',
        UPLOAD_ERR_NO_FILE    => 'Tidak ada file yang dipilih.',
        UPLOAD_ERR_NO_TMP_DIR => 'Folder sementara tidak tersedia di server.',
        UPLOAD_ERR_CANT_WRITE => 'Server tidak bisa menulis file. Periksa permission folder.',
    ];
    $code = $file['error'] ?? UPLOAD_ERR_NO_FILE;
    ob_end_clean();
    apiJson(['error' => $errMap[$code] ?? 'Upload gagal (kode: ' . $code . ').'], 400);
}

// Batas ukuran sumber 20MB
if ($file['size'] > 20 * 1024 * 1024) {
    $mb = round($file['size'] / 1024 / 1024, 1);
    ob_end_clean();
    apiJson(['error' => "File terlalu besar ({$mb}MB). Maks 20MB."], 400);
}

// ── Validasi folder ──────────────────────────────────────────────────────
$folderKey = trim((string)($_POST['folder'] ?? ''));

// Konfigurasi folder. Harus SELARAS dengan $FOLDERS di admin/api/media.php
// supaya Media Manager konsisten.
// 'storage' = 'r2'   → upload ke Cloudflare R2
// 'storage' = 'local'→ upload ke /public/{path}/
// 'crop'           → untuk person (200×200 tengah)
// 'max_w','max_h'  → batas resize untuk foto
$FOLDERS = [
    'umkm'           => ['storage' => 'r2',    'r2_prefix' => defined('R2_UMKM_PREFIX') ? R2_UMKM_PREFIX : 'umkm/',           'max_w' => 1200, 'max_h' => 900,  'quality' => 82, 'label' => 'UMKM Umat'],
    'jadwal_petugas' => ['storage' => 'r2',    'r2_prefix' => defined('R2_JADWAL_PETUGAS_PREFIX') ? R2_JADWAL_PETUGAS_PREFIX : 'jadwal_petugas/', 'max_w' => 1600, 'max_h' => 1200, 'quality' => 85, 'label' => 'Jadwal Petugas'],
    'artikel'        => ['storage' => 'r2',    'r2_prefix' => defined('R2_ARTIKEL_PREFIX') ? R2_ARTIKEL_PREFIX : 'artikel/',  'max_w' => 960,  'max_h' => 720,  'quality' => 80, 'label' => 'Artikel'],
    'gereja'         => ['storage' => 'r2',    'r2_prefix' => 'assets/gereja/',                                            'max_w' => 1600, 'max_h' => 1200, 'quality' => 80, 'label' => 'Foto Gereja'],
    'assets'         => ['storage' => 'r2',    'r2_prefix' => defined('R2_ASSETS_PREFIX') ? R2_ASSETS_PREFIX : 'assets/',   'max_w' => 1600, 'max_h' => 1200, 'quality' => 80, 'label' => 'Assets', 'raw' => true],
    'root_img'       => ['storage' => 'r2',    'r2_prefix' => defined('R2_ASSETS_PREFIX') ? R2_ASSETS_PREFIX : 'assets/',   'max_w' => 1600, 'max_h' => 1200, 'quality' => 80, 'label' => 'Assets', 'raw' => true],
    'person'         => ['storage' => 'r2',    'r2_prefix' => defined('R2_PERSON_PREFIX') ? R2_PERSON_PREFIX : 'person/',  'crop' => 'square200', 'quality' => 82, 'label' => 'Foto Person'],
    'icon'           => ['storage' => 'r2',    'r2_prefix' => defined('R2_ICON_PREFIX') ? R2_ICON_PREFIX : 'icon/',         'raw' => true, 'label' => 'Icon'],
    'icon_kategorial'=> ['storage' => 'r2',    'r2_prefix' => defined('R2_ICON_KAT_PREFIX') ? R2_ICON_KAT_PREFIX : 'icon/kategorial/', 'raw' => true, 'label' => 'Icon Kategorial'],
    'downloads'      => ['storage' => 'r2',    'r2_prefix' => defined('R2_DOWNLOADS_PREFIX') ? R2_DOWNLOADS_PREFIX : 'downloads/', 'raw' => true, 'label' => 'Dokumen Unduhan', 'allow_pdf' => true],
    'ogpreview'      => ['storage' => 'r2',    'r2_prefix' => defined('R2_OG_PREFIX') ? R2_OG_PREFIX : 'ogpreview/',     'max_w' => 1200, 'max_h' => 630, 'quality' => 85, 'label' => 'OG Preview'],
];

if (!isset($FOLDERS[$folderKey])) {
    ob_end_clean();
    apiJson(['error' => 'Folder tidak dikenali: ' . htmlspecialchars($folderKey)], 400);
}
$cfg = $FOLDERS[$folderKey];

// ── Deteksi MIME (validasi keamanan) ─────────────────────────────────────
$mimeType = '';
if (function_exists('finfo_open')) {
    $finfo    = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']) ?: '';
    finfo_close($finfo);
} else {
    $h = @fopen($file['tmp_name'], 'rb');
    if ($h) {
        $hdr = fread($h, 12);
        fclose($h);
        if (substr($hdr, 0, 2) === "\xFF\xD8")                                    $mimeType = 'image/jpeg';
        elseif (substr($hdr, 0, 4) === "\x89PNG")                                $mimeType = 'image/png';
        elseif (substr($hdr, 0, 4) === 'RIFF' && substr($hdr, 8, 4) === 'WEBP') $mimeType = 'image/webp';
        elseif (substr($hdr, 0, 6) === "GIF87a" || substr($hdr, 0, 6) === "GIF89a") $mimeType = 'image/gif';
    }
}

$allowPdf  = !empty($cfg['allow_pdf']);
$allowed   = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
if ($allowPdf) {
    $allowed[] = 'application/pdf';
}
if (!in_array($mimeType, $allowed, true)) {
    ob_end_clean();
    apiJson(['error' => 'Format tidak didukung (' . htmlspecialchars($mimeType) . '). Hanya JPG, PNG, WebP, GIF' . ($allowPdf ? ', PDF' : '') . ' yang diizinkan.'], 400);
}

// ── Helper slug nama file (aman untuk key R2 / nama file lokal) ──────────
function mgrToSlug(string $text): string {
    $map = [
        'à'=>'a','á'=>'a','â'=>'a','ã'=>'a','ä'=>'a','å'=>'a',
        'è'=>'e','é'=>'e','ê'=>'e','ë'=>'e',
        'ì'=>'i','í'=>'i','î'=>'i','ï'=>'i',
        'ò'=>'o','ó'=>'o','ô'=>'o','õ'=>'o','ö'=>'o',
        'ù'=>'u','ú'=>'u','û'=>'u','ü'=>'u',
        'ý'=>'y','ÿ'=>'y','ñ'=>'n','ç'=>'c',
    ];
    $text = strtr($text, $map);
    $text = mb_strtolower($text, 'UTF-8');
    $text = preg_replace('/[^a-z0-9\s\-]/', '', $text);
    $text = preg_replace('/[\s\-]+/', '-', trim($text));
    return mb_substr($text, 0, 60) ?: 'file';
}

$origKb  = round($file['size'] / 1024, 1);
$origName = pathinfo($file['name'] ?? 'file', PATHINFO_FILENAME);
$slug    = mgrToSlug($origName);
$suffix  = date('Ymd_His');
$webpOk  = function_exists('imagewebp') && function_exists('imagecreatefromwebp');

// ── Folder khusus: person (200×200, crop tengah) ─────────────────────────
if (!empty($cfg['crop']) && $cfg['crop'] === 'square200') {
    if (!in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp'], true)) {
        ob_end_clean();
        apiJson(['error' => 'Foto person harus JPG, PNG, atau WebP.'], 400);
    }
    $extOut = $webpOk ? 'webp' : 'jpg';
    $filename = $slug . '.' . $extOut;
    $r2Key    = rtrim($cfg['r2_prefix'], '/') . '/' . $filename;
    $r2Prefix = rtrim($cfg['r2_prefix'], '/') . '/';

    // Decode
    $src = null;
    if ($mimeType === 'image/jpeg')         $src = @imagecreatefromjpeg($file['tmp_name']);
    elseif ($mimeType === 'image/png')      $src = @imagecreatefrompng($file['tmp_name']);
    elseif ($mimeType === 'image/webp' && $webpOk) $src = @imagecreatefromwebp($file['tmp_name']);

    if (!$src) {
        // Fallback: upload mentah apa adanya
        $rawExt  = $mimeType === 'image/png' ? 'png' : 'jpg';
        $rawName = $slug . '.' . $rawExt;
        $r2Key2  = $r2Prefix . $rawName;
        try {
            $r2 = getR2WriteClient();
            $r2->putObjectFromFile($r2Key2, $file['tmp_name'], $mimeType, ['uploaded-via' => 'media-manager-person-raw']);
        } catch (Throwable $e) {
            ob_end_clean();
            apiJson(['error' => 'Gagal upload ke R2: ' . $e->getMessage()], 500);
        }
        $cdn = defined('R2_CDN_URL') ? rtrim(R2_CDN_URL, '/') : 'https://img.parokitulungagung.org';
        getLogger()->log($currentUser, 'CREATE', 'media', 'Upload person (raw): ' . $rawName);
        ob_end_clean();
        apiJson([
            'success'    => true,
            'filename'   => $rawName,
            'url'        => $cdn . '/' . $r2Key2,
            'size_kb'    => $origKb,
            'orig_kb'    => $origKb,
            'saved_pct'  => 0,
            'format'     => strtoupper($rawExt),
            'dimensions' => 'original',
        ]);
    }

    $origW = imagesx($src);
    $origH = imagesy($src);
    $size  = min($origW, $origH);
    $srcX  = (int)(($origW - $size) / 2);
    $srcY  = (int)(($origH - $size) / 2);
    $dst   = imagecreatetruecolor(200, 200);
    $white = imagecolorallocate($dst, 255, 255, 255);
    imagefilledrectangle($dst, 0, 0, 200, 200, $white);
    if ($mimeType === 'image/png') {
        imagealphablending($dst, true);
        imagecolortransparent($dst, $white);
    }
    imagecopyresampled($dst, $src, 0, 0, $srcX, $srcY, 200, 200, $size, $size);
    imagedestroy($src);

    $tmpOut = tempnam(sys_get_temp_dir(), 'medpr_') . '.' . $extOut;
    $saved  = $webpOk ? imagewebp($dst, $tmpOut, (int)$cfg['quality']) : imagejpeg($dst, $tmpOut, (int)$cfg['quality']);
    imagedestroy($dst);
    if (!$saved || !file_exists($tmpOut)) {
        @unlink($tmpOut);
        ob_end_clean();
        apiJson(['error' => 'Gagal mengompres foto person.'], 500);
    }

    $finalKb = round(filesize($tmpOut) / 1024, 1);
    try {
        $r2 = getR2WriteClient();
        $r2->putObjectFromFile($r2Key, $tmpOut, $webpOk ? 'image/webp' : 'image/jpeg', ['uploaded-via' => 'media-manager-person']);
    } catch (Throwable $e) {
        @unlink($tmpOut);
        ob_end_clean();
        apiJson(['error' => 'Gagal upload ke R2: ' . $e->getMessage()], 500);
    }
    @unlink($tmpOut);
    $cdn = defined('R2_CDN_URL') ? rtrim(R2_CDN_URL, '/') : 'https://img.parokitulungagung.org';
    getLogger()->log($currentUser, 'CREATE', 'media', 'Upload person: ' . $filename . ' (200x200)');
    ob_end_clean();
    apiJson([
        'success'    => true,
        'filename'   => $filename,
        'url'        => $cdn . '/' . $r2Key,
        'size_kb'    => $finalKb,
        'orig_kb'    => $origKb,
        'saved_pct'  => $origKb > 0 ? max(0, round((($origKb - $finalKb) / $origKb) * 100)) : 0,
        'format'     => $webpOk ? 'WebP' : 'JPEG',
        'dimensions' => '200x200',
    ]);
}

// ── Folder khusus: 'raw' (asset / icon / downloads / PDF) — simpan apa adanya ──
if (!empty($cfg['raw'])) {
    $extIn  = strtolower(pathinfo($file['name'] ?? 'file', PATHINFO_EXTENSION));
    $extOut = $extIn ?: 'bin';
    $filename = $slug . '-' . $suffix . '.' . $extOut;
    $r2Key    = rtrim($cfg['r2_prefix'], '/') . '/' . $filename;

    try {
        $r2 = getR2WriteClient();
        $r2->putObjectFromFile($r2Key, $file['tmp_name'], $mimeType, ['uploaded-via' => 'media-manager-raw']);
    } catch (Throwable $e) {
        ob_end_clean();
        apiJson(['error' => 'Gagal upload ke R2: ' . $e->getMessage()], 500);
    }
    $cdn = defined('R2_CDN_URL') ? rtrim(R2_CDN_URL, '/') : 'https://img.parokitulungagung.org';
    getLogger()->log($currentUser, 'CREATE', 'media', 'Upload raw: ' . $filename . ' (' . $cfg['label'] . ')');
    ob_end_clean();
    apiJson([
        'success'    => true,
        'filename'   => $filename,
        'url'        => $cdn . '/' . $r2Key,
        'size_kb'    => $origKb,
        'orig_kb'    => $origKb,
        'saved_pct'  => 0,
        'format'     => strtoupper($extOut),
        'dimensions' => 'original',
    ]);
}

// ── Folder dengan kompresi + konversi WebP ───────────────────────────────
$extOut  = $webpOk ? 'webp' : 'jpg';
$filename = $slug . '-' . $suffix . '.' . $extOut;
$r2Key    = rtrim($cfg['r2_prefix'], '/') . '/' . $filename;

$src = null;
if ($mimeType === 'image/jpeg')         $src = @imagecreatefromjpeg($file['tmp_name']);
elseif ($mimeType === 'image/png')      $src = @imagecreatefrompng($file['tmp_name']);
elseif ($mimeType === 'image/webp' && $webpOk) $src = @imagecreatefromwebp($file['tmp_name']);
elseif ($mimeType === 'image/gif' && function_exists('imagecreatefromgif')) $src = @imagecreatefromgif($file['tmp_name']);

if (!$src) {
    // GD gagal → upload mentah apa adanya (fallback aman)
    $rawExt  = $mimeType === 'image/png' ? 'png' : ($mimeType === 'image/webp' ? 'webp' : 'jpg');
    $rawName = $slug . '-' . $suffix . '.' . $rawExt;
    $rawKey  = rtrim($cfg['r2_prefix'], '/') . '/' . $rawName;
    try {
        $r2 = getR2WriteClient();
        $r2->putObjectFromFile($rawKey, $file['tmp_name'], $mimeType, ['uploaded-via' => 'media-manager-raw-fallback']);
    } catch (Throwable $e) {
        ob_end_clean();
        apiJson(['error' => 'Gagal upload ke R2: ' . $e->getMessage()], 500);
    }
    $cdn = defined('R2_CDN_URL') ? rtrim(R2_CDN_URL, '/') : 'https://img.parokitulungagung.org';
    getLogger()->log($currentUser, 'CREATE', 'media', 'Upload raw fallback: ' . $rawName . ' (' . $cfg['label'] . ')');
    ob_end_clean();
    apiJson([
        'success'    => true,
        'filename'   => $rawName,
        'url'        => $cdn . '/' . $rawKey,
        'size_kb'    => $origKb,
        'orig_kb'    => $origKb,
        'saved_pct'  => 0,
        'format'     => strtoupper($rawExt),
        'dimensions' => 'original',
    ]);
}

$origW = imagesx($src);
$origH = imagesy($src);
$maxW  = (int)($cfg['max_w'] ?? 1600);
$maxH  = (int)($cfg['max_h'] ?? 1200);

if ($origW > $maxW || $origH > $maxH) {
    $scale = min($maxW / $origW, $maxH / $origH);
    $newW  = max(1, (int)($origW * $scale));
    $newH  = max(1, (int)($origH * $scale));
} else {
    $newW = $origW;
    $newH = $origH;
}

$dst   = imagecreatetruecolor($newW, $newH);
$white = imagecolorallocate($dst, 255, 255, 255);
imagefilledrectangle($dst, 0, 0, $newW, $newH, $white);
if ($mimeType === 'image/png') {
    imagealphablending($dst, true);
    imagecolortransparent($dst, $white);
}
imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
imagedestroy($src);

$tmpOut = tempnam(sys_get_temp_dir(), 'med_') . '.' . $extOut;
$saved  = $webpOk ? imagewebp($dst, $tmpOut, (int)$cfg['quality']) : imagejpeg($dst, $tmpOut, (int)$cfg['quality']);
imagedestroy($dst);
if (!$saved || !file_exists($tmpOut)) {
    @unlink($tmpOut);
    ob_end_clean();
    apiJson(['error' => 'Gagal mengompres gambar.'], 500);
}

$finalKb = round(filesize($tmpOut) / 1024, 1);
try {
    $r2 = getR2WriteClient();
    $r2->putObjectFromFile($r2Key, $tmpOut, $webpOk ? 'image/webp' : 'image/jpeg', ['uploaded-via' => 'media-manager']);
} catch (Throwable $e) {
    @unlink($tmpOut);
    ob_end_clean();
    apiJson(['error' => 'Gagal upload ke R2: ' . $e->getMessage()], 500);
}
@unlink($tmpOut);

$cdn = defined('R2_CDN_URL') ? rtrim(R2_CDN_URL, '/') : 'https://img.parokitulungagung.org';
getLogger()->log($currentUser, 'CREATE', 'media', 'Upload ' . $cfg['label'] . ': ' . $filename . ' (' . $origKb . 'KB -> ' . $finalKb . 'KB)');

ob_end_clean();
apiJson([
    'success'    => true,
    'filename'   => $filename,
    'url'        => $cdn . '/' . $r2Key,
    'size_kb'    => $finalKb,
    'orig_kb'    => $origKb,
    'saved_pct'  => $origKb > 0 ? max(0, round((($origKb - $finalKb) / $origKb) * 100)) : 0,
    'format'     => $webpOk ? 'WebP' : 'JPEG',
    'dimensions' => $newW . 'x' . $newH,
]);
