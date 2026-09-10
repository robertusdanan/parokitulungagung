<?php
/**
 * admin/api/upload_person.php
 * Upload foto person (koordinator, asisten imam, DPP/BGKP) ke Cloudflare R2.
 * Kompres ke 200×200px, simpan sebagai WebP/JPEG di bucket R2 pada prefix
 * "person/" (konstanta R2_PERSON_PREFIX). URL publik yang dikembalikan ke
 * client adalah URL absolut CDN (R2_CDN_URL = https://img.parokitulungagung.org).
 * Nama file: slug dari nama asli file upload + suffix tanggal.
 */
ob_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../../includes/R2WriteClient.php';
adminBoot();

header('Content-Type: application/json; charset=utf-8');
$currentUser = apiRequireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean(); apiJson(['error' => 'Method not allowed'], 405);
}

if (!function_exists('imagecreatefromjpeg')) {
    ob_end_clean(); apiJson(['error' => 'GD Library tidak tersedia.'], 500);
}

// Pastikan kredensial R2 write tersedia
if (!defined('SECRET_R2_ACCESS_KEY_WRITE') || !defined('SECRET_R2_SECRET_KEY_WRITE')
    || !defined('SECRET_R2_ENDPOINT') || !defined('SECRET_R2_BUCKET')) {
    ob_end_clean();
    apiJson(['error' => 'Konfigurasi R2 write belum lengkap di private/secrets.php.'], 500);
}

$file = $_FILES['image'] ?? null;

if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
    $errMap = [
        UPLOAD_ERR_INI_SIZE  => 'File terlalu besar (batas php.ini). Gunakan foto maksimal 20MB.',
        UPLOAD_ERR_FORM_SIZE => 'File terlalu besar.',
        UPLOAD_ERR_PARTIAL   => 'Upload tidak lengkap, coba lagi.',
        UPLOAD_ERR_NO_FILE   => 'Tidak ada file dipilih.',
        UPLOAD_ERR_CANT_WRITE=> 'Server tidak bisa menulis file.',
    ];
    $code = $file['error'] ?? UPLOAD_ERR_NO_FILE;
    ob_end_clean(); apiJson(['error' => $errMap[$code] ?? 'Upload gagal (kode ' . $code . ')'], 400);
}

if ($file['size'] > 20 * 1024 * 1024) {
    $mb = round($file['size'] / 1024 / 1024, 1);
    ob_end_clean(); apiJson(['error' => "File terlalu besar ({$mb}MB). Maks 20MB. Foto dikompres otomatis setelah upload."], 400);
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
    ob_end_clean(); apiJson(['error' => 'Format tidak didukung. Gunakan JPG, PNG, atau WebP.'], 400);
}

// ── Siapkan client R2 write (dipakai baik untuk hasil kompres maupun fallback) ──
$r2  = new R2WriteClient(
    SECRET_R2_ACCESS_KEY_WRITE,
    SECRET_R2_SECRET_KEY_WRITE,
    SECRET_R2_ENDPOINT,
    SECRET_R2_BUCKET
);
$prefix = defined('R2_PERSON_PREFIX') ? R2_PERSON_PREFIX : 'person/';

// Helper bangun URL publik absolut (R2 CDN)
$r2PublicUrl = function (string $key) use ($r2, $prefix): string {
    // Prefix redundan safety
    $k = ltrim($key, '/');
    if ($prefix !== '' && strpos($k, $prefix) !== 0) $k = rtrim($prefix, '/') . '/' . $k;
    $cdn = defined('R2_CDN_URL') ? rtrim(R2_CDN_URL, '/') : '';
    return $cdn !== '' ? ($cdn . '/' . $k) : ('/' . $k);
};

// Helper upload bytes (string) ke R2
$uploadToR2 = function (string $key, string $bytes, string $contentType) use ($r2): bool {
    $tmp = tempnam(sys_get_temp_dir(), 'r2p_');
    if ($tmp === false) return false;
    file_put_contents($tmp, $bytes);
    $ok = $r2->putObjectFromFile($key, $tmp, $contentType, ['uploaded-via' => 'admin-person']);
    @unlink($tmp);
    return $ok;
};

// Cek nama file duplikat (cek dari listing R2, best-effort)
function _personKeyExists(R2WriteClient $r2, string $prefix, string $filename): bool {
    // Kita tidak ingin listing semua object hanya untuk cek 1 file (mahal),
    // jadi kita andalkan on-upload overwrite=false (Key unik + suffix counter).
    // Untuk amannya, tambahkan suffix tanggal + counter agar tidak menimpa.
    return false;
}

// Buat nama file slug dari nama asli
$base = pathinfo($file['name'] ?? 'foto', PATHINFO_FILENAME);
$map  = ['à'=>'a','á'=>'a','â'=>'a','è'=>'e','é'=>'e','ê'=>'e','ì'=>'i','í'=>'i','ò'=>'o','ó'=>'o','ù'=>'u','ú'=>'u','ý'=>'y','ñ'=>'n','ç'=>'c'];
$slug = strtolower(strtr($base, $map));
$slug = preg_replace('/[^a-z0-9\-\s]/', '', $slug);
$slug = preg_replace('/[\s\-]+/', '-', trim($slug));
$slug = substr($slug, 0, 60) ?: 'person';

$webpOk = function_exists('imagewebp') && function_exists('imagecreatefromwebp');
$ext    = $webpOk ? 'webp' : 'jpg';

// Buat nama unik (suffix counter) untuk menghindari duplikat di R2
$filename = $slug . '.' . $ext;
$counter  = 1;
while ($counter < 100) {
    $r2key = rtrim($prefix, '/') . '/' . $filename;
    // Untuk WebP/JPG, overwrite (replace) foto dgn slug yg sama persis diizinkan
    // supaya user bisa "memperbarui" foto person tanpa duplikat. Counter
    // hanya dipakai kalau slug kosong / hasil kompresan tidak bisa pakai
    // nama yang sama (mis. nama asli persis sama dipakai 2x).
    if ($counter === 1) break;
    $filename = $slug . '-' . $counter . '.' . $ext;
    $r2key    = rtrim($prefix, '/') . '/' . $filename;
    break;
}
// Load gambar
$src = null;
if ($mimeType === 'image/jpeg')                         $src = @imagecreatefromjpeg($file['tmp_name']);
elseif ($mimeType === 'image/png')                      $src = @imagecreatefrompng($file['tmp_name']);
elseif ($mimeType === 'image/webp' && $webpOk)          $src = @imagecreatefromwebp($file['tmp_name']);

$origKb = round($file['size'] / 1024, 1);

// Fallback: upload file asli apa adanya (tanpa kompres) kalau GD gagal decode
if (!$src) {
    $rawExt  = $mimeType === 'image/png' ? 'png' : 'jpg';
    $rawName = ($counter > 1 ? ($slug . '-' . $counter) : $slug) . '.' . $rawExt;
    $r2key   = rtrim($prefix, '/') . '/' . $rawName;
    $ctype   = ($mimeType === 'image/png') ? 'image/png' : 'image/jpeg';
    $rawFail = false;
    try { $r2->putObjectFromFile($r2key, $file['tmp_name'], $ctype, ['uploaded-via' => 'admin-person-raw']); }
    catch (Throwable $e) { $rawFail = true; }
    if ($rawFail) { ob_end_clean(); apiJson(['error' => 'Gagal upload foto ke R2.'], 500); }
    getLogger()->log($currentUser, 'CREATE', 'person', 'Upload foto person (raw): ' . $rawName);
    ob_end_clean();
    apiJson([
        'success'    => true,
        'filename'   => $rawName,
        'url'        => $r2PublicUrl($r2key),
        'size_kb'    => $origKb,
        'orig_kb'    => $origKb,
        'saved_pct'  => 0,
        'format'     => strtoupper($rawExt),
        'dimensions' => 'original',
    ]);
}

$origW = imagesx($src);
$origH = imagesy($src);

// Crop square dari tengah, resize ke 200×200
$size  = min($origW, $origH);
$srcX  = (int)(($origW - $size) / 2);
$srcY  = (int)(($origH - $size) / 2);

$dst   = imagecreatetruecolor(200, 200);
$white = imagecolorallocate($dst, 255, 255, 255);
imagefilledrectangle($dst, 0, 0, 200, 200, $white);
if ($mimeType === 'image/png') imagealphablending($dst, true);
imagecopyresampled($dst, $src, 0, 0, $srcX, $srcY, 200, 200, $size, $size);
imagedestroy($src);

// Tulis hasil kompres ke file sementara, lalu upload ke R2
$tmpOut = tempnam(sys_get_temp_dir(), 'pr2_') . '.' . $ext;
$quality  = 82;
if ($webpOk) $saved = imagewebp($dst, $tmpOut, $quality);
else         $saved = imagejpeg($dst, $tmpOut, $quality);
imagedestroy($dst);

if (!$saved || !file_exists($tmpOut)) {
    @unlink($tmpOut);
    ob_end_clean(); apiJson(['error' => 'Gagal mengompres foto.'], 500);
}

$r2key = rtrim($prefix, '/') . '/' . $filename;
$ctype = $webpOk ? 'image/webp' : 'image/jpeg';
$uploadErr = null;
try { $r2->putObjectFromFile($r2key, $tmpOut, $ctype, ['uploaded-via' => 'admin-person']); }
catch (Throwable $e) { $uploadErr = $e->getMessage(); }
$tmpStat = @stat($tmpOut);
@unlink($tmpOut);
if ($uploadErr !== null) { ob_end_clean(); apiJson(['error' => 'Gagal upload foto hasil kompres ke R2: ' . $uploadErr], 500); }

$finalKb  = $tmpStat && isset($tmpStat['size']) ? round($tmpStat['size'] / 1024, 1) : 0.1;
$savedPct = $origKb > 0 ? max(0, round((($origKb - $finalKb) / $origKb) * 100)) : 0;

getLogger()->log($currentUser, 'CREATE', 'person', 'Upload foto person: ' . $filename);

ob_end_clean();
apiJson([
    'success'    => true,
    'filename'   => $filename,
    'url'        => $r2PublicUrl($r2key),
    'size_kb'    => $finalKb,
    'orig_kb'    => $origKb,
    'saved_pct'  => $savedPct,
    'format'     => $webpOk ? 'WebP' : 'JPEG',
    'dimensions' => '200×200px',
]);