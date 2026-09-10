<?php
/**
 * includes/R2FolderCompressor.php
 * ─────────────────────────────────────────────────────────────────────────
 * Kompresi media SEBELUM diupload ke R2 lewat fitur "Media Manager >
 * Cloudflare R2" — aturan & angka PERSIS SAMA dengan gphotos-migrator
 * (src/MediaCompressor.php) supaya hasil upload folder dari browser
 * konsisten dengan hasil upload dari tool migrator:
 *
 *   - Foto  → didekode ulang & disimpan sebagai WebP (quality 72),
 *             di-resize kalau sisi terpanjang > 2048px.
 *   - Video → ditranscode ulang lewat ffmpeg (H.264 CRF 30, preset veryfast,
 *             tinggi maks 1080px, audio AAC 96k) — KALAU ffmpeg tersedia di
 *             server. Banyak shared hosting TIDAK menyediakan ffmpeg/proc_open,
 *             dalam hal itu video diupload APA ADANYA (fallback aman, sama
 *             seperti filosofi migrator: sebuah file gagal dikompres tidak
 *             boleh menggagalkan seluruh proses upload).
 *   - Kalau kompresi gagal / format tidak didukung → SELALU fallback upload
 *     file asli apa adanya.
 *
 * Beda dari migrator: dipakai dalam konteks 1 request HTTP (bukan proses CLI
 * panjang), jadi FFMPEG_TIMEOUT jauh lebih pendek (default 90 detik) supaya
 * tidak menggantung PHP-FPM worker di shared hosting.
 * ─────────────────────────────────────────────────────────────────────────
 */

final class R2FolderCompressor
{
    // ── Aturan kompresi (Foto: kualitas 60, dimensi maks 1600px) ──────
    public const IMAGE_QUALITY        = 60;
    public const IMAGE_MAX_DIMENSION  = 1600;
    public const VIDEO_CRF            = 30;
    public const VIDEO_PRESET         = 'veryfast';
    public const VIDEO_MAX_HEIGHT     = 1080;
    public const VIDEO_AUDIO_BITRATE  = '96k';
    public const FFMPEG_PATH          = 'ffmpeg';
    public const FFMPEG_TIMEOUT_SECONDS = 90; // dipersingkat utk konteks web request

    private const IMAGE_MIME_SUPPORTED = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/bmp'];
    private const VIDEO_SAFE_REUSE_EXTS = ['mp4', 'mov', 'm4v', 'mkv'];

    private const MIME_MAP = [
        'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
        'gif' => 'image/gif', 'heic' => 'image/heic', 'heif' => 'image/heif',
        'webp' => 'image/webp', 'bmp' => 'image/bmp', 'tiff' => 'image/tiff', 'tif' => 'image/tiff',
        'avif' => 'image/avif', 'mp4' => 'video/mp4', 'mov' => 'video/quicktime',
        'avi' => 'video/x-msvideo', 'mkv' => 'video/x-matroska', 'wmv' => 'video/x-ms-wmv',
        '3gp' => 'video/3gpp', '3g2' => 'video/3gpp2', 'm4v' => 'video/x-m4v',
        'mpg' => 'video/mpeg', 'mpeg' => 'video/mpeg', 'mts' => 'video/mp2t',
        'm2t' => 'video/mp2t', 'm2ts' => 'video/mp2t', 'asf' => 'video/x-ms-asf',
    ];
    private const VIDEO_EXTS = ['mp4','mov','avi','mkv','wmv','3gp','3g2','m4v','mpg','mpeg','mts','m2t','m2ts','asf'];

    private static ?bool $ffmpegAvailable = null;

    public static function mimeTypeFor(string $path): string
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return self::MIME_MAP[$ext] ?? 'application/octet-stream';
    }

    public static function isVideo(string $path): bool
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return in_array($ext, self::VIDEO_EXTS, true);
    }

    public static function isKnownMediaType(string $path): bool
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return isset(self::MIME_MAP[$ext]);
    }

    /**
     * @return array{path:string, isTemp:bool, contentType:string, extOverride:?string, originalSize:int, outputSize:int, compressed:bool, note:?string}
     */
    public static function compress(string $srcPath): array
    {
        $originalSize = (int)(filesize($srcPath) ?: 0);
        if (self::isVideo($srcPath)) {
            return self::compressVideo($srcPath, $originalSize);
        }
        return self::compressImage($srcPath, $originalSize);
    }

    private static function passthrough(string $srcPath, int $originalSize, ?string $note = null): array
    {
        return [
            'path' => $srcPath, 'isTemp' => false, 'contentType' => self::mimeTypeFor($srcPath),
            'extOverride' => null, 'originalSize' => $originalSize, 'outputSize' => $originalSize,
            'compressed' => false, 'note' => $note,
        ];
    }

    private static function compressImage(string $srcPath, int $originalSize): array
    {
        $info = @getimagesize($srcPath);
        if ($info === false || !isset($info['mime']) || !in_array($info['mime'], self::IMAGE_MIME_SUPPORTED, true)) {
            return self::passthrough($srcPath, $originalSize, 'Format gambar tidak didukung untuk kompresi.');
        }

        $raw = @file_get_contents($srcPath);
        if ($raw === false) return self::passthrough($srcPath, $originalSize, 'Gagal membaca file.');

        $img = @imagecreatefromstring($raw);
        unset($raw);
        if ($img === false) return self::passthrough($srcPath, $originalSize, 'Gagal decode gambar.');

        if ($info['mime'] === 'image/jpeg' && function_exists('exif_read_data')) {
            $img = self::applyExifOrientation($img, $srcPath);
        }
        $img = self::resizeIfNeeded($img);

        if (!imageistruecolor($img)) imagepalettetotruecolor($img);
        imagealphablending($img, true);
        imagesavealpha($img, true);

        $tmpPath = self::tempFilePath('webp');
        $ok = @imagewebp($img, $tmpPath, self::IMAGE_QUALITY);
        imagedestroy($img);

        if (!$ok || !is_file($tmpPath)) {
            @unlink($tmpPath);
            return self::passthrough($srcPath, $originalSize, 'Gagal encode WebP.');
        }

        $outputSize = (int)(filesize($tmpPath) ?: 0);
        if ($outputSize === 0 || $outputSize >= $originalSize) {
            @unlink($tmpPath);
            return self::passthrough($srcPath, $originalSize);
        }

        return [
            'path' => $tmpPath, 'isTemp' => true, 'contentType' => 'image/webp',
            'extOverride' => 'webp', 'originalSize' => $originalSize, 'outputSize' => $outputSize,
            'compressed' => true, 'note' => null,
        ];
    }

    private static function applyExifOrientation(\GdImage $img, string $srcPath): \GdImage
    {
        $exif = @exif_read_data($srcPath);
        $orientation = $exif['Orientation'] ?? 1;
        $rotated = match ((int)$orientation) {
            3, 4 => imagerotate($img, 180, 0),
            5, 6 => imagerotate($img, -90, 0),
            7, 8 => imagerotate($img, 90, 0),
            default => $img,
        };
        if ($rotated === false) return $img;
        if (in_array((int)$orientation, [2, 4, 5, 7], true)) imageflip($rotated, IMG_FLIP_HORIZONTAL);
        if ($rotated !== $img) imagedestroy($img);
        return $rotated;
    }

    private static function resizeIfNeeded(\GdImage $img): \GdImage
    {
        $maxDim = self::IMAGE_MAX_DIMENSION;
        $w = imagesx($img); $h = imagesy($img);
        $longest = max($w, $h);
        if ($longest <= $maxDim) return $img;

        $scale = $maxDim / $longest;
        $nw = max(1, (int)round($w * $scale));
        $nh = max(1, (int)round($h * $scale));

        $resized = imagecreatetruecolor($nw, $nh);
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
        imagefilledrectangle($resized, 0, 0, $nw, $nh, $transparent);
        imagecopyresampled($resized, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);
        imagedestroy($img);
        return $resized;
    }

    private static function compressVideo(string $srcPath, int $originalSize): array
    {
        if (!self::isFfmpegAvailable()) {
            return self::passthrough($srcPath, $originalSize, 'ffmpeg tidak tersedia di server — video diupload tanpa kompresi.');
        }

        $originalExt = strtolower(pathinfo($srcPath, PATHINFO_EXTENSION));
        $outExt = in_array($originalExt, self::VIDEO_SAFE_REUSE_EXTS, true) ? $originalExt : 'mp4';
        $tmpPath = self::tempFilePath($outExt);

        $cmd = [
            self::FFMPEG_PATH, '-y', '-i', $srcPath, '-map_metadata', '0',
            '-vf', "scale=-2:'min(" . self::VIDEO_MAX_HEIGHT . ",ih)'",
            '-c:v', 'libx264', '-crf', (string)self::VIDEO_CRF, '-preset', self::VIDEO_PRESET,
            '-pix_fmt', 'yuv420p', '-movflags', '+faststart',
            '-c:a', 'aac', '-b:a', self::VIDEO_AUDIO_BITRATE, '-loglevel', 'error', $tmpPath,
        ];
        $ok = self::runFfmpegCmd($cmd, self::FFMPEG_TIMEOUT_SECONDS);
        if (!$ok || !is_file($tmpPath) || (int)(filesize($tmpPath) ?: 0) === 0) {
            @unlink($tmpPath);
            return self::passthrough($srcPath, $originalSize, 'Kompresi video gagal/timeout — diupload tanpa kompresi.');
        }

        $outputSize = (int)(filesize($tmpPath) ?: 0);
        if ($outputSize >= $originalSize) {
            @unlink($tmpPath);
            return self::passthrough($srcPath, $originalSize);
        }

        return [
            'path' => $tmpPath, 'isTemp' => true,
            'contentType' => $outExt === $originalExt ? self::mimeTypeFor($srcPath) : 'video/mp4',
            'extOverride' => $outExt === $originalExt ? null : $outExt,
            'originalSize' => $originalSize, 'outputSize' => $outputSize,
            'compressed' => true, 'note' => null,
        ];
    }

    /**
     * Ambil 1 frame dari video (screenshot) untuk dipakai sebagai poster/thumbnail
     * di grid galeri publik & sebagai atribut `poster` player video.
     * Diambil dari detik ke-1 (fallback ke frame pertama kalau video < 1 detik),
     * di-scale maks lebar 800px, lalu dikonversi ulang ke WebP quality 72 lewat
     * GD supaya konsisten dengan pipeline kompresi foto lainnya.
     *
     * @return string|null Path file WebP sementara, atau null kalau ffmpeg tidak
     *                      tersedia / ekstraksi gagal (frontend fallback ke ikon).
     */
    public static function extractVideoPoster(string $srcPath): ?string
    {
        if (!self::isFfmpegAvailable()) return null;

        $jpgTmp = self::grabFrame($srcPath, '00:00:01');
        if ($jpgTmp === null) {
            // Video mungkin lebih pendek dari 1 detik — coba frame pertama.
            $jpgTmp = self::grabFrame($srcPath, null);
        }
        if ($jpgTmp === null) return null;

        $raw = @file_get_contents($jpgTmp);
        @unlink($jpgTmp);
        if ($raw === false) return null;

        $img = @imagecreatefromstring($raw);
        unset($raw);
        if ($img === false) return null;

        $webpTmp = self::tempFilePath('webp');
        $ok = @imagewebp($img, $webpTmp, self::IMAGE_QUALITY);
        imagedestroy($img);

        if (!$ok || !is_file($webpTmp) || (int)(filesize($webpTmp) ?: 0) === 0) {
            @unlink($webpTmp);
            return null;
        }
        return $webpTmp;
    }

    private static function grabFrame(string $srcPath, ?string $atTimestamp): ?string
    {
        $tmp = self::tempFilePath('jpg');
        $cmd = [self::FFMPEG_PATH, '-y'];
        if ($atTimestamp !== null) { $cmd[] = '-ss'; $cmd[] = $atTimestamp; }
        $cmd = array_merge($cmd, [
            '-i', $srcPath, '-frames:v', '1', '-vf', 'scale=800:-2',
            '-q:v', '3', '-loglevel', 'error', $tmp,
        ]);
        // Screenshot tunggal jauh lebih cepat dari transcode penuh — batas waktu pendek cukup.
        $ok = self::runFfmpegCmd($cmd, 20);
        if (!$ok || !is_file($tmp) || (int)(filesize($tmp) ?: 0) === 0) {
            @unlink($tmp);
            return null;
        }
        return $tmp;
    }

    private static function runFfmpegCmd(array $cmd, int $timeoutSeconds): bool
    {
        $proc = @proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        if (!is_resource($proc)) return false;

        fclose($pipes[1]);
        stream_set_blocking($pipes[2], false);
        $start = time();
        while (true) {
            stream_get_contents($pipes[2]);
            $status = proc_get_status($proc);
            if (!$status['running']) break;
            if ((time() - $start) > $timeoutSeconds) {
                proc_terminate($proc, 9);
                fclose($pipes[2]);
                proc_close($proc);
                return false;
            }
            usleep(150_000);
        }
        fclose($pipes[2]);
        return proc_close($proc) === 0;
    }

    public static function isFfmpegAvailable(): bool
    {
        if (self::$ffmpegAvailable !== null) return self::$ffmpegAvailable;
        if (!function_exists('proc_open')) { self::$ffmpegAvailable = false; return false; }

        $proc = @proc_open([self::FFMPEG_PATH, '-version'], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        if (!is_resource($proc)) { self::$ffmpegAvailable = false; return false; }
        fclose($pipes[1]); fclose($pipes[2]);
        self::$ffmpegAvailable = proc_close($proc) === 0;
        return self::$ffmpegAvailable;
    }

    private static function tempFilePath(string $ext): string
    {
        $dir = sys_get_temp_dir();
        return $dir . '/r2up_' . bin2hex(random_bytes(8)) . '.' . $ext;
    }
}
