<?php
/**
 * admin/api/upload_galeri.php
 * Upload thumbnail galeri foto — dengan kompresi otomatis
 *
 * Target: simpan ke /public/galeri/
 * Kolom 'Gambar' di Supabase menyimpan nama file saja (bukan full path),
 * karena content.php me-resolve dengan: '/public/galeri/' . $gambar
 * Dimensi: maks 800×500px (rasio 16/10, cukup untuk thumbnail galeri)
 * Target ukuran: < 80KB
 * Format: WebP jika tersedia, fallback JPEG
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
adminBoot();

header('Content-Type: application/json; charset=utf-8');

$currentUser = apiRequireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    apiJson(['error' => 'Method not allowed'], 405);
}

$file = $_FILES['image'] ?? null;

// ── Cek GD tersedia ───────────────────────────────────────────────────
if (!function_exists('imagecreatefromjpeg')) {
    apiJson(['error' => 'GD Library tidak tersedia di server ini. Hubungi hosting.'], 500);
}

// ── Validasi upload ───────────────────────────────────────────────────
if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
    $errMsg = [
        UPLOAD_ERR_INI_SIZE   => 'File terlalu besar (batas php.ini). Gunakan gambar maksimal 20MB.',
        UPLOAD_ERR_FORM_SIZE  => 'File terlalu besar (batas form).',
        UPLOAD_ERR_PARTIAL    => 'Upload tidak lengkap, coba lagi.',
        UPLOAD_ERR_NO_FILE    => 'Tidak ada file yang dipilih.',
        UPLOAD_ERR_NO_TMP_DIR => 'Folder sementara tidak tersedia di server.',
        UPLOAD_ERR_CANT_WRITE => 'Server tidak bisa menulis file. Periksa permission folder.',
    ];
    $code = $file['error'] ?? UPLOAD_ERR_NO_FILE;
    apiJson(['error' => $errMsg[$code] ?? 'Upload gagal (kode: ' . $code . ')'], 400);
}

// ── Batas ukuran: 20MB (kompresi otomatis menangani pengecilan file) ────────────────
if ($file['size'] > 20 * 1024 * 1024) {
    $mb = round($file['size'] / 1024 / 1024, 1);
    apiJson(['error' => "File terlalu besar ({$mb}MB). Maksimum 20MB. Gambar dikompres otomatis setelah upload."], 400);
}

// ── Validasi tipe file ────────────────────────────────────────────────
$mimeType = '';
if (function_exists('finfo_open')) {
    $fi       = finfo_open(FILEINFO_MIME_TYPE);
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
        else $mimeType = @mime_content_type($file['tmp_name']) ?: 'unknown';
    }
}

if (!in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp'])) {
    apiJson(['error' => 'Format tidak didukung. Gunakan JPG atau PNG.'], 400);
}

// ── Buat direktori upload ─────────────────────────────────────────────
$uploadDir = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/public/galeri/';
if (!is_dir($uploadDir)) {
    if (!@mkdir($uploadDir, 0755, true)) {
        apiJson([
            'error' => 'Gagal membuat folder /public/galeri/ — ' .
                       'Buat manual di cPanel File Manager dan set permission 755.'
        ], 500);
    }
}
if (!is_writable($uploadDir)) {
    apiJson([
        'error' => 'Folder /public/galeri/ tidak bisa ditulis. ' .
                   'Set permission 755 di cPanel File Manager.'
    ], 500);
}

// ── Deteksi WebP support ──────────────────────────────────────────────
$webpAvailable = function_exists('imagewebp') && function_exists('imagecreatefromwebp');
$origKb        = round($file['size'] / 1024, 1);

// ── Helper: buat nama file bergaya slug (komuni-pertama-2015.webp) ───
function makeGaleriFilename(string $originalName, string $ext): string
{
    // Buang ekstensi dari nama asli
    $base = pathinfo($originalName, PATHINFO_FILENAME);

    // Transliterasi karakter Indonesia → ASCII
    $map = [
        'à'=>'a','á'=>'a','â'=>'a','ã'=>'a','ä'=>'a',
        'è'=>'e','é'=>'e','ê'=>'e','ë'=>'e',
        'ì'=>'i','í'=>'i','î'=>'i','ï'=>'i',
        'ò'=>'o','ó'=>'o','ô'=>'o','õ'=>'o','ö'=>'o',
        'ù'=>'u','ú'=>'u','û'=>'u','ü'=>'u',
        'ý'=>'y','ñ'=>'n','ç'=>'c',
    ];
    $slug = strtolower(strtr($base, $map));
    $slug = preg_replace('/[^a-z0-9\s\-]/', '', $slug);
    $slug = preg_replace('/[\s\-]+/', '-', trim($slug));
    $slug = substr($slug, 0, 60) ?: 'galeri';

    // Tambahkan suffix tanggal singkat agar unik (misal: -20251231)
    $suffix = date('Ymd');
    $filename = $slug . '-' . $suffix . '.' . $ext;

    // Jika sudah ada file dengan nama sama, tambahkan counter
    $uploadDir = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/public/galeri/';
    $counter   = 1;
    while (file_exists($uploadDir . $filename)) {
        $filename = $slug . '-' . $suffix . '-' . $counter . '.' . $ext;
        $counter++;
    }

    return $filename;
}

// ── Load gambar sumber ────────────────────────────────────────────────
try {
    $src = null;
    if ($mimeType === 'image/jpeg')                           $src = @imagecreatefromjpeg($file['tmp_name']);
    elseif ($mimeType === 'image/png')                        $src = @imagecreatefrompng($file['tmp_name']);
    elseif ($mimeType === 'image/webp' && $webpAvailable)    $src = @imagecreatefromwebp($file['tmp_name']);

    // GD gagal load → simpan file asli sebagai fallback
    if (!$src) {
        $ext      = ($mimeType === 'image/png') ? 'png' : 'jpg';
        $filename = makeGaleriFilename($file['name'] ?? '', $ext);
        if (!move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
            apiJson(['error' => 'Gagal menyimpan gambar. Coba lagi.'], 500);
        }
        apiJson([
            'success'    => true,
            'url'        => '/public/galeri/' . $filename,
            'filename'   => $filename,
            'size_kb'    => $origKb,
            'orig_kb'    => $origKb,
            'compressed' => false,
            'format'     => strtoupper($ext),
            'note'       => 'Disimpan tanpa kompresi (GD tidak bisa baca format ini).',
        ]);
    }

    $origW = imagesx($src);
    $origH = imagesy($src);

    // ── Target dimensi galeri thumbnail ──────────────────────────────
    // Maks 800×500, preserve aspect ratio
    $maxW    = 800;
    $maxH    = 500;
    $quality = 78; // agresif tapi masih bagus

    if ($origW > $maxW || $origH > $maxH) {
        $scale = min($maxW / $origW, $maxH / $origH);
        $newW  = max(1, (int)($origW * $scale));
        $newH  = max(1, (int)($origH * $scale));
    } else {
        $newW = $origW;
        $newH = $origH;
    }

    // ── Buat canvas baru ──────────────────────────────────────────────
    $dst = imagecreatetruecolor($newW, $newH);
    if (!$dst) {
        imagedestroy($src);
        apiJson(['error' => 'Gagal membuat canvas. Coba gambar lebih kecil.'], 500);
    }

    // Background putih (untuk PNG transparan)
    $white = imagecolorallocate($dst, 255, 255, 255);
    imagefilledrectangle($dst, 0, 0, $newW, $newH, $white);
    if ($mimeType === 'image/png') imagealphablending($dst, true);

    imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
    imagedestroy($src);

    // ── Simpan: WebP atau fallback JPEG ───────────────────────────────
    if ($webpAvailable) {
        $filename = makeGaleriFilename($file['name'] ?? '', 'webp');
        $savePath = $uploadDir . $filename;
        $saved    = imagewebp($dst, $savePath, $quality);
        $format   = 'WebP';
    } else {
        $filename = makeGaleriFilename($file['name'] ?? '', 'jpg');
        $savePath = $uploadDir . $filename;
        $saved    = imagejpeg($dst, $savePath, $quality);
        $format   = 'JPEG';
    }
    imagedestroy($dst);

    if (!$saved || !file_exists($savePath)) {
        apiJson(['error' => 'Gambar diproses tapi gagal disimpan. Coba lagi.'], 500);
    }

    $finalKb  = round(filesize($savePath) / 1024, 1);
    $savedPct = $origKb > 0 ? max(0, round((($origKb - $finalKb) / $origKb) * 100)) : 0;

    apiJson([
        'success'    => true,
        'url'        => '/public/galeri/' . $filename,
        'filename'   => $filename,
        'size_kb'    => $finalKb,
        'orig_kb'    => $origKb,
        'compressed' => $finalKb < $origKb,
        'saved_pct'  => $savedPct,
        'format'     => $format,
        'dimensions' => $newW . '×' . $newH . 'px',
    ]);

} catch (Throwable $e) {
    error_log('[upload_galeri] ' . $e->getMessage());
    apiJson([
        'error' => 'Error memproses gambar: ' . $e->getMessage()
    ], 500);
}