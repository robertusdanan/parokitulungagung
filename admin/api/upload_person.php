<?php
/**
 * admin/api/upload_person.php
 * Upload foto person (koordinator, asisten imam, DPP/BGKP) ke /img/person/
 * Kompres ke 200×200px, simpan sebagai WebP/JPEG.
 * Nama file: slug dari nama asli file upload + suffix tanggal.
 */
ob_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
adminBoot();

header('Content-Type: application/json; charset=utf-8');
$currentUser = apiRequireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean(); apiJson(['error' => 'Method not allowed'], 405);
}

if (!function_exists('imagecreatefromjpeg')) {
    ob_end_clean(); apiJson(['error' => 'GD Library tidak tersedia.'], 500);
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

$uploadDir = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/img/person/';
if (!is_dir($uploadDir) && !@mkdir($uploadDir, 0755, true)) {
    ob_end_clean(); apiJson(['error' => 'Folder /img/person/ tidak bisa dibuat.'], 500);
}
if (!is_writable($uploadDir)) {
    ob_end_clean(); apiJson(['error' => 'Folder /img/person/ tidak bisa ditulis. Set permission 755.'], 500);
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

// Buat nama unik
$filename = $slug . '.' . $ext;
$counter  = 1;
while (file_exists($uploadDir . $filename)) {
    $filename = $slug . '-' . $counter . '.' . $ext;
    $counter++;
}

// Load gambar
$src = null;
if ($mimeType === 'image/jpeg')                         $src = @imagecreatefromjpeg($file['tmp_name']);
elseif ($mimeType === 'image/png')                      $src = @imagecreatefrompng($file['tmp_name']);
elseif ($mimeType === 'image/webp' && $webpOk)          $src = @imagecreatefromwebp($file['tmp_name']);

$origKb = round($file['size'] / 1024, 1);

// Fallback: simpan mentah jika GD gagal
if (!$src) {
    $rawExt  = $mimeType === 'image/png' ? 'png' : 'jpg';
    $rawName = $slug . '.' . $rawExt;
    $counter2 = 1;
    while (file_exists($uploadDir . $rawName)) {
        $rawName = $slug . '-' . $counter2 . '.' . $rawExt;
        $counter2++;
    }
    if (!move_uploaded_file($file['tmp_name'], $uploadDir . $rawName)) {
        ob_end_clean(); apiJson(['error' => 'Gagal menyimpan foto.'], 500);
    }
    ob_end_clean();
    apiJson([
        'success'    => true,
        'filename'   => $rawName,
        'url'        => '/img/person/' . $rawName,
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

$savePath = $uploadDir . $filename;
$quality  = 82;
if ($webpOk) $saved = imagewebp($dst, $savePath, $quality);
else         $saved = imagejpeg($dst, $savePath, $quality);
imagedestroy($dst);

if (!$saved || !file_exists($savePath)) {
    ob_end_clean(); apiJson(['error' => 'Gagal menyimpan foto hasil kompres.'], 500);
}

$finalKb  = round(filesize($savePath) / 1024, 1);
$savedPct = $origKb > 0 ? max(0, round((($origKb - $finalKb) / $origKb) * 100)) : 0;

getLogger()->log($currentUser, 'CREATE', 'person', 'Upload foto person: ' . $filename);

ob_end_clean();
apiJson([
    'success'    => true,
    'filename'   => $filename,
    'url'        => '/img/person/' . $filename,
    'size_kb'    => $finalKb,
    'orig_kb'    => $origKb,
    'saved_pct'  => $savedPct,
    'format'     => $webpOk ? 'WebP' : 'JPEG',
    'dimensions' => '200×200px',
]);