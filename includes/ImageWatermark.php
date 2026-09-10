<?php
/**
 * includes/ImageWatermark.php
 * ─────────────────────────────────────────────────────────────────────────
 * Logika watermark bersama, dipakai oleh:
 *   - r2-image.php     (foto isi album, sumber Cloudflare R2)
 *   - galeri-cover.php (foto sampul album, sumber lokal /public/galeri/)
 *
 * Watermark: teks "parokitulungagung.org" di pojok kanan-bawah, pil gelap
 * semi-transparan + teks putih tipis di atasnya — cukup terbaca di foto
 * terang maupun gelap, tanpa mengganggu komposisi foto.
 *
 * Naikkan WATERMARK_VERSION kalau desain watermark diubah — cache lama
 * otomatis "kadaluarsa" karena nama foldernya beda, tidak perlu hapus
 * manual folder cache.
 * ─────────────────────────────────────────────────────────────────────────
 */

const WATERMARK_VERSION = 'wm1';
const WATERMARK_TEXT    = 'parokitulungagung.org';
const WATERMARK_FONT    = __DIR__ . '/../fonts/ttf/Montserrat-Var.ttf';

function watermarkGdAvailable(): bool
{
    return extension_loaded('gd') && function_exists('imagecreatefromstring');
}

function watermarkTtfAvailable(): bool
{
    return watermarkGdAvailable()
        && function_exists('imagettftext')
        && function_exists('imagettfbbox')
        && is_file(WATERMARK_FONT);
}

/**
 * Bubuhkan watermark teks elegan di pojok kanan-bawah gambar (in-place).
 * Ukuran teks & margin proporsional terhadap lebar gambar.
 */
function applyWatermarkText($im, bool $canTtf): void
{
    if (!$im) return;
    $w = imagesx($im);
    $h = imagesy($im);
    if ($w < 60 || $h < 60) return; // gambar terlalu kecil, lewati saja

    imagealphablending($im, true);
    imagesavealpha($im, true);

    $text     = WATERMARK_TEXT;
    $fontSize = max(11, min(30, $w * 0.020));
    $margin   = max(10, $w * 0.02);

    if ($canTtf) {
        $bbox  = imagettfbbox($fontSize, 0, WATERMARK_FONT, $text);
        $textW = abs($bbox[2] - $bbox[0]);
        $textH = abs($bbox[7] - $bbox[1]);
    } else {
        // Fallback tanpa FreeType: font bitmap bawaan GD (tetap terbaca,
        // hanya kurang halus dibanding TTF).
        $gdFont   = 5;
        $textW    = imagefontwidth($gdFont) * strlen($text);
        $textH    = imagefontheight($gdFont);
        $fontSize = $textH;
    }

    $padX = max(12, $fontSize * 0.6);
    $padY = max(8, $fontSize * 0.5);

    $pillW = $textW + $padX * 2;
    $pillH = $textH + $padY * 2;
    $pillX = max(0, $w - $pillW - $margin);
    $pillY = max(0, $h - $pillH - $margin);

    // Pil gelap semi-transparan → menjaga kontras teks di atas foto apa pun
    $pill = imagecolorallocatealpha($im, 10, 10, 10, 82);
    imagefilledrectangle($im, (int)$pillX, (int)$pillY, (int)($pillX + $pillW), (int)($pillY + $pillH), $pill);

    $textX     = (int)($pillX + $padX);
    $baselineY = (int)($pillY + $pillH - $padY);

    if ($canTtf) {
        $shadow = imagecolorallocatealpha($im, 0, 0, 0, 95);
        $white  = imagecolorallocatealpha($im, 255, 255, 255, 32);
        imagettftext($im, $fontSize, 0, $textX + 1, $baselineY + 1, $shadow, WATERMARK_FONT, $text);
        imagettftext($im, $fontSize, 0, $textX, $baselineY, $white, WATERMARK_FONT, $text);
    } else {
        $white = imagecolorallocatealpha($im, 255, 255, 255, 32);
        imagestring($im, 5, $textX, (int)$pillY + (int)$padY, $text, $white);
    }
}

/** Encode gambar sesuai ekstensi asli, kembalikan ['data'=>string,'contentType'=>string]. */
function encodeImageByExt($im, string $ext, int $quality = 90): array
{
    ob_start();
    switch ($ext) {
        case 'jpg':
        case 'jpeg':
            imagejpeg($im, null, $quality);
            $ct = 'image/jpeg';
            break;
        case 'webp':
            imagewebp($im, null, $quality);
            $ct = 'image/webp';
            break;
        case 'png':
            imagepng($im, null, 6);
            $ct = 'image/png';
            break;
        case 'gif':
            imagegif($im);
            $ct = 'image/gif';
            break;
        default:
            imagejpeg($im, null, $quality);
            $ct = 'image/jpeg';
    }
    return ['data' => ob_get_clean(), 'contentType' => $ct];
}
