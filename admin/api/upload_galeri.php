<?php
/**
 * admin/api/upload_galeri.php
 * Upload thumbnail galeri foto — simpan ke Cloudflare R2
 *
 * Folder tujuan di R2: _thumbnails/galeri/<filename>
 * Kolom 'Gambar' di Supabase menyimpan nama file saja.
 * CDN URL: https://img.parokitulungagung.org/_thumbnails/galeri/<filename>
 *
 * Kompresi: maks 800×500px, WebP/JPEG, target <80KB
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
adminBoot();

header('Content-Type: application/json; charset=utf-8');

$currentUser = apiRequirePageAccess('galeri', 'create');

if (!defined('SECRET_R2_ACCESS_KEY_WRITE') || !defined('SECRET_R2_SECRET_KEY_WRITE')) {
    apiJson(['error' => 'Kredensial R2 (write) belum diatur di secrets.php.'], 500);
}

require_once __DIR__ . '/../../includes/R2WriteClient.php';

// ── Cek GD tersedia ───────────────────────────────────────────────────
if (!function_exists('imagecreatefromjpeg')) {
    apiJson(['error' => 'GD Library tidak tersedia di server ini.'], 500);
}

$file = $_FILES['file'] ?? null;

if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
    $errMsg = [
        UPLOAD_ERR_INI_SIZE   => 'File terlalu besar (batas php.ini). Gunakan gambar maksimal 20MB.',
        UPLOAD_ERR_FORM_SIZE  => 'File terlalu besar (batas form).',
        UPLOAD_ERR_PARTIAL    => 'Upload tidak lengkap, coba lagi.',
        UPLOAD_ERR_NO_FILE    => 'Tidak ada file yang dipilih.',
        UPLOAD_ERR_NO_TMP_DIR => 'Folder sementara tidak tersedia di server.',
        UPLOAD_ERR_CANT_WRITE => 'Server tidak bisa menulis file sementara.',
    ];
    $code = $file['error'] ?? UPLOAD_ERR_NO_FILE;
    apiJson(['error' => $errMsg[$code] ?? 'Upload gagal (kode: ' . $code . ')'], 400);
}

if ($file['size'] > 20 * 1024 * 1024) {
    $mb = round($file['size'] / 1024 / 1024, 1);
    apiJson(['error' => "File terlalu besar ({$mb}MB). Maksimum 20MB."], 400);
}

// ── Validasi tipe file ────────────────────────────────────────────────
$mimeType = '';
if (function_exists('finfo_open')) {
    $fi = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($fi, $file['tmp_name']);
    finfo_close($fi);
} else {
    $h = @fopen($file['tmp_name'], 'rb');
    if ($h) {
        $hdr = fread($h, 12);
        fclose($h);
        if (substr($hdr, 0, 2) === "\xFF\xD8")                              $mimeType = 'image/jpeg';
        elseif (substr($hdr, 0, 4) === "\x89PNG")                           $mimeType = 'image/png';
        elseif (substr($hdr, 0, 4) === 'RIFF' && substr($hdr, 8, 4) === 'WEBP') $mimeType = 'image/webp';
    }
}
if (!in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp'])) {
    apiJson(['error' => 'Format tidak didukung. Gunakan JPG, PNG, atau WebP.'], 400);
}

// ── Helper: slug filename ─────────────────────────────────────────────
function makeThumbFilename(string $originalName, string $ext): string
{
    $base = pathinfo($originalName, PATHINFO_FILENAME);
    $map  = ['à'=>'a','á'=>'a','â'=>'a','ä'=>'a','è'=>'e','é'=>'e','ê'=>'e',
             'ì'=>'i','í'=>'i','î'=>'i','ò'=>'o','ó'=>'o','ô'=>'o',
             'ù'=>'u','ú'=>'u','û'=>'u','ñ'=>'n','ç'=>'c'];
    $slug = strtolower(strtr($base, $map));
    $slug = preg_replace('/[^a-z0-9\s\-]/', '', $slug);
    $slug = preg_replace('/[\s\-]+/', '-', trim($slug));
    $slug = substr($slug ?: 'thumbnail', 0, 60);
    return $slug . '-' . date('Ymd') . '-' . substr(md5(uniqid('', true)), 0, 6) . '.' . $ext;
}

$origKb         = round($file['size'] / 1024, 1);
$webpAvailable  = function_exists('imagewebp') && function_exists('imagecreatefromwebp');
$tmpOut         = null;

try {
    // ── Load gambar sumber ────────────────────────────────────────────
    $src = null;
    if ($mimeType === 'image/jpeg')                        $src = @imagecreatefromjpeg($file['tmp_name']);
    elseif ($mimeType === 'image/png')                     $src = @imagecreatefrompng($file['tmp_name']);
    elseif ($mimeType === 'image/webp' && $webpAvailable)  $src = @imagecreatefromwebp($file['tmp_name']);

    if (!$src) {
        // GD gagal → upload file asli apa adanya
        $ext      = ($mimeType === 'image/png') ? 'png' : 'jpg';
        $filename = makeThumbFilename($file['name'] ?? 'thumb', $ext);
        $r2key    = '_thumbnails/galeri/' . $filename;
        $r2 = new R2WriteClient(SECRET_R2_ACCESS_KEY_WRITE, SECRET_R2_SECRET_KEY_WRITE, SECRET_R2_ENDPOINT, SECRET_R2_BUCKET);
        $r2->putObjectFromFile($r2key, $file['tmp_name'], $mimeType, ['uploaded-via' => 'admin-galeri-thumb']);
        apiJson([
            'success'    => true,
            'url'        => rtrim(R2_CDN_URL, '/') . '/' . $r2key,
            'filename'   => $r2key,
            'size_kb'    => $origKb,
            'orig_kb'    => $origKb,
            'compressed' => false,
            'format'     => strtoupper($ext),
        ]);
    }

    // ── Resize: maks 800×500 ──────────────────────────────────────────
    $origW = imagesx($src);
    $origH = imagesy($src);
    $maxW  = 800; $maxH = 500;

    if ($origW > $maxW || $origH > $maxH) {
        $scale = min($maxW / $origW, $maxH / $origH);
        $newW  = max(1, (int)($origW * $scale));
        $newH  = max(1, (int)($origH * $scale));
    } else {
        $newW = $origW; $newH = $origH;
    }

    $dst = imagecreatetruecolor($newW, $newH);
    $white = imagecolorallocate($dst, 255, 255, 255);
    imagefilledrectangle($dst, 0, 0, $newW, $newH, $white);
    if ($mimeType === 'image/png') imagealphablending($dst, true);
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
    imagedestroy($src);

    // ── Simpan ke file temp ───────────────────────────────────────────
    $quality = 78;
    if ($webpAvailable) {
        $ext     = 'webp';
        $ctype   = 'image/webp';
        $tmpOut  = tempnam(sys_get_temp_dir(), 'gth_') . '.webp';
        $saved   = imagewebp($dst, $tmpOut, $quality);
    } else {
        $ext     = 'jpg';
        $ctype   = 'image/jpeg';
        $tmpOut  = tempnam(sys_get_temp_dir(), 'gth_') . '.jpg';
        $saved   = imagejpeg($dst, $tmpOut, $quality);
    }
    imagedestroy($dst);

    if (!$saved || !file_exists($tmpOut)) {
        apiJson(['error' => 'Gagal memproses gambar. Coba lagi.'], 500);
    }

    // ── Upload ke R2 ─────────────────────────────────────────────────
    $filename = makeThumbFilename($file['name'] ?? 'thumb', $ext);
    $r2key    = '_thumbnails/galeri/' . $filename;

    $r2 = new R2WriteClient(SECRET_R2_ACCESS_KEY_WRITE, SECRET_R2_SECRET_KEY_WRITE, SECRET_R2_ENDPOINT, SECRET_R2_BUCKET);
    $r2->putObjectFromFile($r2key, $tmpOut, $ctype, [
        'original-filename' => $file['name'] ?? '',
        'uploaded-via'      => 'admin-galeri-thumb',
        'dimensions'        => $newW . 'x' . $newH,
    ]);

    $finalKb  = round(filesize($tmpOut) / 1024, 1);
    $savedPct = $origKb > 0 ? max(0, round((($origKb - $finalKb) / $origKb) * 100)) : 0;

    apiJson([
        'success'    => true,
        'url'        => rtrim(R2_CDN_URL, '/') . '/' . $r2key,
        'filename'   => $r2key,        // simpan r2key penuh ke kolom Gambar
        'size_kb'    => $finalKb,
        'orig_kb'    => $origKb,
        'compressed' => $finalKb < $origKb,
        'saved_pct'  => $savedPct,
        'format'     => strtoupper($ext),
        'dimensions' => $newW . '×' . $newH . 'px',
    ]);

} catch (Throwable $e) {
    error_log('[upload_galeri] ' . $e->getMessage());
    apiJson(['error' => 'Error: ' . $e->getMessage()], 500);
} finally {
    if ($tmpOut && is_file($tmpOut)) @unlink($tmpOut);
}