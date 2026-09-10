<?php
/**
 * admin/api/upload_artikel.php
 * Upload gambar artikel — Dioptimasi untuk InfinityFree
 *
 * Penyesuaian InfinityFree:
 * - Batas upload: 20MB (kompresi otomatis aktif)
 * - Auto-detect WebP → fallback JPEG jika tidak tersedia
 * - Kompresi agresif untuk hemat storage 3GB
 * - Dimensi lebih kecil agar proses cepat di shared hosting
 *
 * type=thumbnail → dua file sekaligus:
 *   • {slug}-{id}.webp → max 720×450px, target <60KB  (dipakai di kartu artikel)
 *   • og-{id}.webp   → 1200×630px crop tengah, target <120KB  (OG WhatsApp/meta)
 *
 * type=content → satu file:
 *   • {slug}-{id}.webp  → max 960×720px, target <150KB
 */
ob_start(); // Tangkap notice/warning agar tidak merusak JSON response
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../../includes/R2WriteClient.php';
adminBoot();

header('Content-Type: application/json; charset=utf-8');

$currentUser = apiRequireLogin();

$r2ak = defined('SECRET_R2_ACCESS_KEY_WRITE') ? SECRET_R2_ACCESS_KEY_WRITE : (defined('R2_ACCESS_KEY') ? R2_ACCESS_KEY : null);
$r2sk = defined('SECRET_R2_SECRET_KEY_WRITE') ? SECRET_R2_SECRET_KEY_WRITE : (defined('R2_SECRET_KEY') ? R2_SECRET_KEY : null);
$r2ep = defined('SECRET_R2_ENDPOINT') ? SECRET_R2_ENDPOINT : (defined('R2_ENDPOINT') ? R2_ENDPOINT : null);
$r2bk = defined('SECRET_R2_BUCKET') ? SECRET_R2_BUCKET : (defined('R2_BUCKET') ? R2_BUCKET : null);

$r2Client  = ($r2ak && $r2sk && $r2ep && $r2bk) ? new R2WriteClient($r2ak, $r2sk, $r2ep, $r2bk) : null;
$r2CdnBase = defined('R2_CDN_URL') ? rtrim(R2_CDN_URL, '/') : 'https://img.parokitulungagung.org';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    apiJson(['error' => 'Method not allowed'], 405);
}

$file  = $_FILES['image'] ?? null;
$type  = $_POST['type']  ?? 'content'; // 'thumbnail' atau 'content'
$judul = trim($_POST['judul'] ?? '');  // nama file / judul artikel (wajib)

// ── Helper: konversi teks → slug aman untuk nama file ────────────────────
function toSlug(string $text): string {
    // Transliterasi karakter umum Indonesia/aksara Latin ke ASCII
    $map = [
        'à'=>'a','á'=>'a','â'=>'a','ã'=>'a','ä'=>'a','å'=>'a',
        'è'=>'e','é'=>'e','ê'=>'e','ë'=>'e',
        'ì'=>'i','í'=>'i','î'=>'i','ï'=>'i',
        'ò'=>'o','ó'=>'o','ô'=>'o','õ'=>'o','ö'=>'o',
        'ù'=>'u','ú'=>'u','û'=>'u','ü'=>'u',
        'ý'=>'y','ÿ'=>'y','ñ'=>'n','ç'=>'c',
        'À'=>'a','Á'=>'a','Â'=>'a','Ã'=>'a','Ä'=>'a','Å'=>'a',
        'È'=>'e','É'=>'e','Ê'=>'e','Ë'=>'e',
        'Ì'=>'i','Í'=>'i','Î'=>'i','Ï'=>'i',
        'Ò'=>'o','Ó'=>'o','Ô'=>'o','Õ'=>'o','Ö'=>'o',
        'Ù'=>'u','Ú'=>'u','Û'=>'u','Ü'=>'u',
        'Ý'=>'y','Ñ'=>'n','Ç'=>'c',
    ];
    $text = strtr($text, $map);
    $text = mb_strtolower($text, 'UTF-8');
    // Hapus karakter selain huruf, angka, spasi, tanda hubung
    $text = preg_replace('/[^a-z0-9\s\-]/', '', $text);
    // Spasi dan tanda hubung berulang → satu tanda hubung
    $text = preg_replace('/[\s\-]+/', '-', $text);
    $text = trim($text, '-');
    // Potong maks 80 karakter, jangan putus di tengah kata
    if (mb_strlen($text) > 80) {
        $text = mb_substr($text, 0, 80);
        $lastDash = strrpos($text, '-');
        if ($lastDash > 40) $text = substr($text, 0, $lastDash);
        $text = trim($text, '-');
    }
    return $text ?: 'gambar-artikel';
}

// ── Cek GD tersedia ───────────────────────────────────────────────────────
if (!function_exists('imagecreatefromjpeg')) {
    apiJson(['error' => 'GD Library tidak tersedia di server ini. Hubungi hosting.'], 500);
}

// ── Validasi judul / nama file wajib diisi ────────────────────────────────
if ($judul === '') {
    $labelField = ($type === 'thumbnail') ? 'judul artikel' : 'nama foto';
    apiJson(['error' => "Kolom {$labelField} wajib diisi sebelum upload gambar."], 400);
}

// ── Validasi upload ───────────────────────────────────────────────────────
if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
    $errMsg = [
        UPLOAD_ERR_INI_SIZE   => 'File terlalu besar (batas php.ini). Gunakan gambar maksimal 20MB.',
        UPLOAD_ERR_FORM_SIZE  => 'File terlalu besar (batas form)',
        UPLOAD_ERR_PARTIAL    => 'Upload tidak lengkap, coba lagi',
        UPLOAD_ERR_NO_FILE    => 'Tidak ada file yang dipilih',
        UPLOAD_ERR_NO_TMP_DIR => 'Folder sementara tidak tersedia di server',
        UPLOAD_ERR_CANT_WRITE => 'Server tidak bisa menulis file. Periksa permission folder.',
    ];
    $code = $file['error'] ?? UPLOAD_ERR_NO_FILE;
    apiJson(['error' => $errMsg[$code] ?? 'Upload gagal (kode error: ' . $code . ')'], 400);
}

// ── Batas ukuran: 20MB (kompresi otomatis menangani pengecilan file) ─────────────────
define('MAX_UPLOAD_BYTES', 20 * 1024 * 1024); // 20MB
if ($file['size'] > MAX_UPLOAD_BYTES) {
    $sizeMb = round($file['size'] / 1024 / 1024, 1);
    apiJson([
        'error' => "File terlalu besar ({$sizeMb}MB). Maksimum 20MB. " .
                   "Gambar akan dikompres otomatis setelah upload."
    ], 400);
}

// ── Validasi tipe file ────────────────────────────────────────────────────
$mimeType = '';
if (function_exists('finfo_open')) {
    $finfo    = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
} else {
    $handle = @fopen($file['tmp_name'], 'rb');
    if ($handle) {
        $header = fread($handle, 12);
        fclose($handle);
        if (substr($header, 0, 2) === "\xFF\xD8")                                     $mimeType = 'image/jpeg';
        elseif (substr($header, 0, 4) === "\x89PNG")                                  $mimeType = 'image/png';
        elseif (substr($header, 0, 4) === 'RIFF' && substr($header, 8, 4) === 'WEBP') $mimeType = 'image/webp';
        else $mimeType = @mime_content_type($file['tmp_name']) ?: 'unknown';
    }
}

$allowed = ['image/jpeg', 'image/png', 'image/webp'];
if (!in_array($mimeType, $allowed)) {
    apiJson(['error' => 'Format tidak didukung. Gunakan JPG atau PNG.'], 400);
}

// ── Buat direktori upload (lokal / fallback tmp) ─────────────────────────
$uploadDir = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/img/artikel/';
if (!is_dir($uploadDir)) {
    if (!@mkdir($uploadDir, 0755, true)) {
        $uploadDir = rtrim(sys_get_temp_dir(), '/') . '/artikel_upload/';
        if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);
    }
}
if (!is_writable($uploadDir)) {
    $uploadDir = rtrim(sys_get_temp_dir(), '/') . '/artikel_upload/';
    if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);
}

// ── Deteksi kapabilitas WebP server ──────────────────────────────────────
$webpAvailable = function_exists('imagewebp') && function_exists('imagecreatefromwebp');

// ── Helper: simpan GD resource ke file ───────────────────────────────────
function saveGdImage($gdRes, string $path, bool $webpAvailable, int $quality): array {
    if ($webpAvailable) {
        $saved  = imagewebp($gdRes, $path . '.webp', $quality);
        return ['path' => $path . '.webp', 'ext' => 'webp', 'format' => 'WebP', 'saved' => $saved];
    }
    $saved = imagejpeg($gdRes, $path . '.jpg', $quality);
    return ['path' => $path . '.jpg', 'ext' => 'jpg', 'format' => 'JPEG', 'saved' => $saved];
}

// ── Helper: resize ke dalam batas (preserve ratio, TIDAK crop) ────────────
function resizeFit($src, int $origW, int $origH, int $maxW, int $maxH): \GdImage|false {
    if ($origW > $maxW || $origH > $maxH) {
        $scale = min($maxW / $origW, $maxH / $origH);
        $newW  = max(1, (int)($origW * $scale));
        $newH  = max(1, (int)($origH * $scale));
    } else {
        $newW = $origW;
        $newH = $origH;
    }
    $dst = imagecreatetruecolor($newW, $newH);
    if (!$dst) return false;
    $white = imagecolorallocate($dst, 255, 255, 255);
    imagefilledrectangle($dst, 0, 0, $newW, $newH, $white);
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
    return $dst;
}

// ── Helper: resize + crop tengah ke dimensi TEPAT (untuk OG) ─────────────
function resizeCropCenter($src, int $origW, int $origH, int $targetW, int $targetH): \GdImage|false {
    // Scale minimal agar gambar memenuhi target (cover, bukan contain)
    $scale  = max($targetW / $origW, $targetH / $origH);
    $scaledW = (int)($origW * $scale);
    $scaledH = (int)($origH * $scale);

    // Hitung offset crop (ambil dari tengah)
    $srcX = (int)(($scaledW - $targetW) / 2 / $scale);
    $srcY = (int)(($scaledH - $targetH) / 2 / $scale);
    $srcW = (int)($targetW / $scale);
    $srcH = (int)($targetH / $scale);

    // Pastikan tidak keluar batas
    $srcX = max(0, min($srcX, $origW - $srcW));
    $srcY = max(0, min($srcY, $origH - $srcH));

    $dst = imagecreatetruecolor($targetW, $targetH);
    if (!$dst) return false;
    $white = imagecolorallocate($dst, 255, 255, 255);
    imagefilledrectangle($dst, 0, 0, $targetW, $targetH, $white);
    imagecopyresampled($dst, $src, 0, 0, $srcX, $srcY, $targetW, $targetH, $srcW, $srcH);
    return $dst;
}

// ── Load gambar sumber ────────────────────────────────────────────────────
$origKb = round($file['size'] / 1024, 1);

try {
    $src = null;
    if ($mimeType === 'image/jpeg')                              $src = @imagecreatefromjpeg($file['tmp_name']);
    elseif ($mimeType === 'image/png')                           $src = @imagecreatefrompng($file['tmp_name']);
    elseif ($mimeType === 'image/webp' && $webpAvailable)        $src = @imagecreatefromwebp($file['tmp_name']);

    // ── Fallback: GD gagal load → simpan file asli ───────────────────────
    if (!$src) {
        $ext  = ($mimeType === 'image/png') ? 'png' : 'jpg';
        $slug = toSlug($judul);

        $candidate = $slug;
        $counter   = 2;
        while (file_exists($uploadDir . $candidate . '.' . $ext)) {
            $candidate = $slug . '-' . $counter;
            $counter++;
        }

        $filename = $candidate . '.' . $ext;
        $savePath = $uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $savePath)) {
            apiJson(['error' => 'Gagal menyimpan gambar. Coba lagi atau gunakan gambar lain.'], 500);
        }

        $r2Url = $r2CdnBase . '/artikel/' . $filename;
        if ($r2Client) {
            try {
                $r2Client->putObjectFromFile('artikel/' . $filename, $savePath, $file['type'] ?: 'image/jpeg');
            } catch (Throwable $e) {
                error_log('[upload_artikel] R2 putObject raw failed: ' . $e->getMessage());
            }
        }

        apiJson([
            'success'    => true,
            'url'        => $r2Url,
            'filename'   => $filename,
            'og_url'     => null,
            'og_filename'=> null,
            'size_kb'    => $origKb,
            'orig_kb'    => $origKb,
            'compressed' => false,
            'format'     => strtoupper($ext),
            'note'       => 'Disimpan tanpa kompresi (GD tidak bisa baca format ini).',
        ]);
    }

    $origW = imagesx($src);
    $origH = imagesy($src);

    // ════════════════════════════════════════════════════════════════
    // THUMBNAIL — buat dua file sekaligus:
    //   1) {slug}-{id}  → 720×450px  (kartu artikel)
    //   2) og-{id}     → 1200×630px crop tengah  (OG/WhatsApp preview)
    // ════════════════════════════════════════════════════════════════
    if ($type === 'thumbnail') {

        $slug = toSlug($judul);
        $ext  = $webpAvailable ? 'webp' : 'jpg';

        // Coba nama bersih dulu — tambah suffix hanya jika file sudah ada
        $candidate = $slug;
        $counter   = 2;
        while (file_exists($uploadDir . $candidate . '.' . $ext)
            || file_exists($uploadDir . 'og-' . $candidate . '.' . $ext)) {
            $candidate = $slug . '-' . $counter;
            $counter++;
        }

        // ── 1. Thumbnail biasa (fit) ──────────────────────────────────
        $thumbDst = resizeFit($src, $origW, $origH, 720, 450);
        if (!$thumbDst) {
            imagedestroy($src);
            apiJson(['error' => 'Gagal membuat canvas thumbnail. Coba gambar lebih kecil.'], 500);
        }

        $thumbBase = $uploadDir . $candidate;
        $thumbInfo = saveGdImage($thumbDst, $thumbBase, $webpAvailable, 75);
        imagedestroy($thumbDst);

        if (!$thumbInfo['saved'] || !file_exists($thumbInfo['path'])) {
            imagedestroy($src);
            apiJson(['error' => 'Thumbnail diproses tapi gagal disimpan. Coba lagi.'], 500);
        }

        // ── 2. OG Preview (crop tengah 1200×630, selalu JPEG untuk WA) ─
        $ogDst = resizeCropCenter($src, $origW, $origH, 1200, 630);
        imagedestroy($src); // tidak dibutuhkan lagi

        $ogUrl      = null;
        $ogFilename = null;
        $ogKb       = null;

        if ($ogDst) {
            $ogFilename = $candidate . '.jpg';
            $ogPath     = $uploadDir . $ogFilename;
            $ogSaved    = imagejpeg($ogDst, $ogPath, 85);
            imagedestroy($ogDst);

            if ($ogSaved && file_exists($ogPath)) {
                $ogUrl = $r2CdnBase . '/ogpreview/' . $ogFilename;
                $ogKb  = round(filesize($ogPath) / 1024, 1);
                if ($r2Client) {
                    try {
                        $r2Client->putObjectFromFile('ogpreview/' . $ogFilename, $ogPath, 'image/jpeg');
                    } catch (Throwable $e) {
                        error_log('[upload_artikel] R2 putObject OG failed: ' . $e->getMessage());
                    }
                }
            }
        }

        $thumbFilename = $candidate . '.' . $thumbInfo['ext'];
        $thumbUrl      = $r2CdnBase . '/artikel/' . $thumbFilename;
        if ($r2Client) {
            try {
                $thumbMime = $thumbInfo['ext'] === 'webp' ? 'image/webp' : 'image/jpeg';
                $r2Client->putObjectFromFile('artikel/' . $thumbFilename, $thumbInfo['path'], $thumbMime);
            } catch (Throwable $e) {
                error_log('[upload_artikel] R2 putObject thumb failed: ' . $e->getMessage());
            }
        }
        $finalKb       = round(filesize($thumbInfo['path']) / 1024, 1);
        $savedPct      = $origKb > 0 ? max(0, round((($origKb - $finalKb) / $origKb) * 100)) : 0;

        apiJson([
            'success'     => true,
            'url'         => $thumbUrl,
            'filename'    => $thumbFilename,
            'og_url'      => $ogUrl,
            'og_filename' => $ogFilename,
            'og_size_kb'  => $ogKb,
            'size_kb'     => $finalKb,
            'orig_kb'     => $origKb,
            'compressed'  => $finalKb < $origKb,
            'saved_pct'   => $savedPct,
            'format'      => $thumbInfo['format'],
            'dimensions'  => imagesx(imagecreatefromstring(file_get_contents($thumbInfo['path'])))
                             . '×' . imagesy(imagecreatefromstring(file_get_contents($thumbInfo['path']))) . 'px',
        ]);
    }

    // ════════════════════════════════════════════════════════════════
    // CONTENT — satu file saja (tidak ada OG)
    // ════════════════════════════════════════════════════════════════
    $maxW    = 960;
    $maxH    = 720;
    $quality = 78;

    $dst = resizeFit($src, $origW, $origH, $maxW, $maxH);
    imagedestroy($src);

    if (!$dst) {
        apiJson(['error' => 'Gagal membuat canvas. Memory server tidak cukup — coba gambar lebih kecil.'], 500);
    }

    $slug = toSlug($judul);
    $ext  = $webpAvailable ? 'webp' : 'jpg';

    // Coba nama bersih dulu — tambah suffix hanya jika file sudah ada
    $candidate = $slug;
    $counter   = 2;
    while (file_exists($uploadDir . $candidate . '.' . $ext)) {
        $candidate = $slug . '-' . $counter;
        $counter++;
    }

    $base = $uploadDir . $candidate;
    $info = saveGdImage($dst, $base, $webpAvailable, $quality);
    imagedestroy($dst);

    if (!$info['saved'] || !file_exists($info['path'])) {
        apiJson(['error' => 'Gambar diproses tapi gagal disimpan. Coba lagi.'], 500);
    }

    $filename = $candidate . '.' . $info['ext'];
    $finalUrl = $r2CdnBase . '/artikel/' . $filename;
    if ($r2Client) {
        try {
            $contentMime = $info['ext'] === 'webp' ? 'image/webp' : 'image/jpeg';
            $r2Client->putObjectFromFile('artikel/' . $filename, $info['path'], $contentMime);
        } catch (Throwable $e) {
            error_log('[upload_artikel] R2 putObject content failed: ' . $e->getMessage());
        }
    }
    $finalKb  = round(filesize($info['path']) / 1024, 1);
    $savedPct = $origKb > 0 ? max(0, round((($origKb - $finalKb) / $origKb) * 100)) : 0;

    apiJson([
        'success'    => true,
        'url'        => $finalUrl,
        'filename'   => $filename,
        'og_url'     => null,
        'og_filename'=> null,
        'size_kb'    => $finalKb,
        'orig_kb'    => $origKb,
        'compressed' => $finalKb < $origKb,
        'saved_pct'  => $savedPct,
        'format'     => $info['format'],
        'dimensions' => imagesx(imagecreatefromstring(file_get_contents($info['path'])))
                        . '×' . imagesy(imagecreatefromstring(file_get_contents($info['path']))) . 'px',
    ]);

} catch (Throwable $e) {
    error_log('[upload_artikel] ' . $e->getMessage());
    apiJson([
        'error' => 'Error memproses gambar: ' . $e->getMessage() .
                   ' — Coba gambar lain atau lebih kecil.',
        'debug' => $e->getFile() . ':' . $e->getLine(),
    ], 500);
}