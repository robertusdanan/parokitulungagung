<?php
/**
 * r2-image.php
 * ─────────────────────────────────────────────────────────────────────────
 * Proxy gambar untuk foto album yang tersimpan di Cloudflare R2.
 *
 * Pemakaian:
 *   /r2-image.php?key=<folder-album>/<file>.webp             → ukuran ASLI
 *   /r2-image.php?key=<folder-album>/<file>.webp&size=thumb  → thumbnail ringan
 *
 * WATERMARK ANTI-DOWNLOAD-BEBAS
 * ─────────────────────────────────────────────────────────────────────────
 * Kedua mode (thumb & full) SELALU melewati proxy ini — tidak ada lagi jalur
 * yang mengarahkan browser langsung ke R2 tanpa diproses. Alasannya: kalau
 * pengunjung klik-kanan → "Simpan gambar sebagai…" (atau drag foto ke
 * desktop, dsb), yang tersimpan di komputer mereka adalah persis bytes yang
 * dikirim proxy ini. Supaya hasil download SELALU membawa watermark
 * "parokitulungagung.org", watermark dibubuhkan di sini, di server, ke
 * dalam piksel gambar — bukan sekadar overlay CSS/JS yang gampang dilewati.
 * Logika watermark ada di includes/ImageWatermark.php (dipakai bersama
 * dengan galeri-cover.php untuk foto sampul album).
 *
 * Konsekuensi arsitektur (dari versi sebelumnya yang redirect langsung ke
 * R2 untuk foto ukuran penuh): mode "full" sekarang benar-benar mengambil
 * bytes dari R2 lewat server (bukan 302 redirect lagi), supaya GD bisa
 * menggambar watermark sebelum dikirim ke browser. Ini berarti bandwidth &
 * CPU hosting terpakai untuk foto ukuran penuh juga (sebelumnya 0, karena
 * browser ambil langsung dari R2). Untuk menahan biaya ini, HASIL yang
 * sudah diberi watermark di-cache di disk (cache/r2img/<slug-album>/full-wm1/…)
 * sehingga R2 & GD hanya diproses SEKALI per foto, permintaan berikutnya
 * langsung dilayani dari cache lokal.
 *
 * Fallback: kalau GD/FreeType tidak tersedia di server, proxy jatuh balik
 * ke presigned-URL redirect (perilaku lama, TANPA watermark) supaya foto
 * tetap bisa tampil — bukan error total.
 * ─────────────────────────────────────────────────────────────────────────
 */

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/R2Client.php';
require_once __DIR__ . '/includes/ImageWatermark.php';

$key = $_GET['key'] ?? '';
$key = ltrim((string)$key, '/');

// ── Validasi dasar: tolak path traversal & key kosong ──────────────────
if ($key === '' || str_contains($key, '..') || strlen($key) > 500) {
    http_response_code(400);
    header('Content-Type: text/plain');
    echo 'Bad request';
    exit;
}

// ── Hanya izinkan ekstensi gambar ───────────────────────────────────────
$ext = strtolower(pathinfo($key, PATHINFO_EXTENSION));
$allowedExt = ['webp', 'jpg', 'jpeg', 'png', 'gif'];
if (!in_array($ext, $allowedExt, true)) {
    http_response_code(400);
    header('Content-Type: text/plain');
    echo 'Unsupported file type';
    exit;
}

if (!defined('R2_ACCESS_KEY') || !defined('R2_SECRET_KEY') || !defined('R2_ENDPOINT') || !defined('R2_BUCKET')) {
    http_response_code(503);
    header('Content-Type: text/plain');
    echo 'R2 belum dikonfigurasi di server (lihat includes/config.php).';
    exit;
}

// Masa berlaku presigned URL — hanya dipakai untuk jalur FALLBACK (GD tidak
// tersedia). Selama GD aktif, foto selalu lewat proxy ini (untuk watermark).
const R2_PRESIGN_TTL = 7 * 24 * 3600;

$wantThumb   = (($_GET['size'] ?? '') === 'thumb');
$gdAvailable = watermarkGdAvailable();
$canTtf      = watermarkTtfAvailable();

function sendCached(string $file, string $contentType, ?string $etag): void
{
    if ($etag) header('ETag: "' . $etag . '"');
    header('Content-Type: ' . $contentType);
    header('Cache-Control: public, max-age=31536000, immutable');
    header('Content-Length: ' . filesize($file));

    $ifNoneMatch = $_SERVER['HTTP_IF_NONE_MATCH'] ?? '';
    if ($etag && trim($ifNoneMatch, '"') === $etag) {
        http_response_code(304);
        return;
    }
    readfile($file);
}

function sendBytesAndCache(string $data, string $contentType, ?string $etag, string $cacheDir, string $cacheFile, string $cacheMeta): void
{
    if (!is_dir($cacheDir)) @mkdir($cacheDir, 0755, true);
    $tmp = $cacheFile . '.tmp.' . getmypid();
    if (@file_put_contents($tmp, $data, LOCK_EX) !== false) {
        @rename($tmp, $cacheFile);
        @file_put_contents($cacheMeta, json_encode(['contentType' => $contentType, 'etag' => $etag]), LOCK_EX);
    } else {
        @unlink($tmp);
    }

    if ($etag) header('ETag: "' . $etag . '"');
    header('Content-Type: ' . $contentType);
    header('Cache-Control: public, max-age=31536000, immutable');
    echo $data;
}

/** Redirect presigned ke R2 — HANYA dipakai kalau GD tidak tersedia (fallback, tanpa watermark). */
function redirectToR2(string $key): void
{
    $r2  = new R2Client(R2_ACCESS_KEY, R2_SECRET_KEY, R2_ENDPOINT, R2_BUCKET);
    $ttl = R2_PRESIGN_TTL;
    $url = $r2->getPresignedUrl($key, $ttl);
    header('Cache-Control: private, max-age=' . ($ttl - 300));
    header('Location: ' . $url, true, 302);
    exit;
}

// ── GD tidak tersedia sama sekali → tidak bisa watermark, fallback lama ──
if (!$gdAvailable) {
    redirectToR2($key);
}

$albumRaw  = dirname($key);
$albumSlug = ($albumRaw === '.' || $albumRaw === '') ? '_root' : slugify($albumRaw);
$hash      = md5($key);

$modeFolder = ($wantThumb ? 'thumb' : 'full') . '-' . WATERMARK_VERSION;
$cacheDir   = rtrim($_SERVER['DOCUMENT_ROOT'] ?? __DIR__, '/') . '/cache/r2img/' . $albumSlug . '/' . $modeFolder;
$cacheFile  = $cacheDir . '/' . $hash . '.' . ($wantThumb ? 'webp' : $ext);
$cacheMeta  = $cacheDir . '/' . $hash . '.meta.json';

// ── Cache hit — versi berwatermark sudah pernah dibuat ──────────────────
if (file_exists($cacheFile) && file_exists($cacheMeta)) {
    $meta = json_decode(file_get_contents($cacheMeta), true) ?: [];
    sendCached($cacheFile, $meta['contentType'] ?? 'image/webp', $meta['etag'] ?? null);
    exit;
}

// ── Cache miss — ambil bytes dari R2 SEKALI, proses, simpan hasilnya ────
$r2  = new R2Client(R2_ACCESS_KEY, R2_SECRET_KEY, R2_ENDPOINT, R2_BUCKET);
$obj = $r2->getObjectRaw($key);

if (!$obj || $obj['status'] !== 200 || $obj['body'] === '') {
    http_response_code(404);
    header('Content-Type: text/plain');
    echo 'Foto tidak ditemukan';
    exit;
}

$etag = $obj['etag'] ?? null;

// Foto kamera/DSLR bisa besar — beri sedikit ruang memori ekstra untuk GD
// (best-effort; kalau host membatasi lebih ketat, ini tidak akan gagal keras).
@ini_set('memory_limit', '256M');

$src = @imagecreatefromstring($obj['body']);
unset($obj); // bytes ukuran penuh tidak pernah ditulis ke disk hosting

if (!$src) {
    // Format aneh / GD gagal decode → fallback redirect tanpa watermark
    // (lebih baik foto tetap tampil daripada error total).
    redirectToR2($key);
}

if ($wantThumb) {
    $thumbWidth = isset($_GET['w']) ? (int)$_GET['w'] : 480;
    $thumbWidth = max(100, min(640, $thumbWidth));

    $w = imagesx($src);
    $h = imagesy($src);
    if ($w > $thumbWidth) {
        $newW = $thumbWidth;
        $newH = max(1, (int) round($h * ($thumbWidth / $w)));
    } else {
        $newW = $w;
        $newH = $h;
    }
    $dst = imagecreatetruecolor($newW, $newH);
    imagealphablending($dst, false);
    imagesavealpha($dst, true);
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $w, $h);
    imagedestroy($src);

    applyWatermarkText($dst, $canTtf);
    $encoded = encodeImageByExt($dst, 'webp', 68);
    imagedestroy($dst);
} else {
    // Mode FULL: watermark di resolusi asli, tidak di-resize sama sekali
    // (tetap "ukuran ASLI" seperti sebelumnya — hanya ditambah watermark).
    applyWatermarkText($src, $canTtf);
    $encoded = encodeImageByExt($src, $ext, 90);
    imagedestroy($src);
}

sendBytesAndCache($encoded['data'], $encoded['contentType'], $etag, $cacheDir, $cacheFile, $cacheMeta);
