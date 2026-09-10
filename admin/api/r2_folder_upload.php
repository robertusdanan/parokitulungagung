<?php
/**
 * admin/api/r2_folder_upload.php
 * Media Manager > Cloudflare R2 — upload folder (drag & drop)
 *
 * Menerima SATU file per request (dipanggil berkali-kali oleh JS untuk
 * setiap file di dalam folder yang di-drop), mengompres file itu dengan
 * ATURAN YANG SAMA PERSIS dengan gphotos-migrator (lihat R2FolderCompressor),
 * lalu mengunggahnya ke Cloudflare R2 dengan key:
 *
 *     galerifoto/{nama_folder_asli}/{path_relatif_di_dalam_folder}
 *
 * (Prefix "galerifoto/" — lihat konstanta R2_ALBUM_PREFIX di config.php —
 * supaya semua folder album foto rapi berada dalam 1 folder khusus, bukan
 * langsung di root bucket bercampur dengan folder lain seperti icon/,
 * umkm/, artikel/, dst.)
 *
 * Nama folder dipertahankan APA ADANYA (hanya dibersihkan dari karakter
 * berbahaya seperti '..' atau null-byte demi keamanan key R2 — bukan
 * di-slug/diterjemahkan).
 *
 * POST (multipart/form-data):
 *   file          — blob file
 *   folder        — nama folder root yang di-drop (mis. "Misa Natal 2024")
 *   relpath       — path relatif file di dalam folder (mis. "IMG_0001.jpg"
 *                   atau "sub/IMG_0002.jpg" kalau ada subfolder)
 *   skip_existing — '1' untuk skip kalau object dengan nama sama sudah ada di R2
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
adminBoot();

header('Content-Type: application/json; charset=utf-8');

$user = apiRequirePageAccess('media', 'create');

// Lepaskan lock session SEGERA agar request upload paralel tidak saling memblokir (session lock bottleneck)
session_write_close();

require_once __DIR__ . '/../../includes/R2WriteClient.php';
require_once __DIR__ . '/../../includes/R2FolderCompressor.php';
require_once __DIR__ . '/../../includes/GitHubDispatcher.php';
require_once __DIR__ . '/../includes/R2AlbumCache.php';

// ── Kredensial R2 (WRITE) ───────────────────────────────────────────────
// Tambahkan di private/secrets.php (di luar public_html):
//   define('SECRET_R2_ACCESS_KEY_WRITE', '...');  // API Token izin Object Read & Write
//   define('SECRET_R2_SECRET_KEY_WRITE', '...');
// Endpoint & bucket memakai konstanta SECRET_R2_ENDPOINT / SECRET_R2_BUCKET yang
// sudah ada (dipakai bareng dengan galeri publik, read-only).
if (!defined('SECRET_R2_ACCESS_KEY_WRITE') || !defined('SECRET_R2_SECRET_KEY_WRITE')
    || !defined('SECRET_R2_ENDPOINT') || !defined('SECRET_R2_BUCKET')) {
    apiJson(['error' => 'Kredensial R2 (write) belum diatur di private/secrets.php. Tambahkan SECRET_R2_ACCESS_KEY_WRITE & SECRET_R2_SECRET_KEY_WRITE.'], 500);
}

@ini_set('memory_limit', '512M');
set_time_limit(600);
ignore_user_abort(false);

// ── Validasi input ───────────────────────────────────────────────────────
$file  = $_FILES['file'] ?? null;
$folder = trim((string)($_POST['folder'] ?? ''));
$relpath = trim((string)($_POST['relpath'] ?? ''));
$skipExisting = ($_POST['skip_existing'] ?? '') === '1';

if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
    $errMsg = [
        UPLOAD_ERR_INI_SIZE   => 'File melebihi batas upload_max_filesize di php.ini.',
        UPLOAD_ERR_FORM_SIZE  => 'File melebihi batas form.',
        UPLOAD_ERR_PARTIAL    => 'File hanya terupload sebagian, coba lagi.',
        UPLOAD_ERR_NO_FILE    => 'Tidak ada file yang dikirim.',
        UPLOAD_ERR_NO_TMP_DIR => 'Folder temp tidak tersedia di server.',
        UPLOAD_ERR_CANT_WRITE => 'Server gagal menulis file sementara.',
    ][$file['error'] ?? UPLOAD_ERR_NO_FILE] ?? 'Upload error.';
    apiJson(['error' => $errMsg], 400);
}

if ($folder === '' || $relpath === '') {
    apiJson(['error' => 'Nama folder / path relatif tidak boleh kosong.'], 400);
}

// Batas ukuran sumber (sanity check saja — kompresi menangani pengecilan)
$maxBytes = 500 * 1024 * 1024; // 500MB per file mentah
if ($file['size'] > $maxBytes) {
    apiJson(['error' => 'File "' . basename($relpath) . '" melebihi batas 500MB.'], 400);
}

/**
 * Bersihkan 1 path (folder ATAU relpath) supaya aman dipakai sebagai R2 key,
 * TANPA mengubah nama yang terlihat (tidak slug/transliterasi) — hanya
 * buang karakter kontrol/null-byte dan segmen '..' yang berbahaya.
 */
function sanitizeR2PathSegment(string $path): string
{
    $path = str_replace("\0", '', $path);
    $path = str_replace('\\', '/', $path);
    $segments = explode('/', $path);
    $clean = [];
    foreach ($segments as $seg) {
        $seg = trim($seg);
        if ($seg === '' || $seg === '.' || $seg === '..') continue;
        // Buang karakter kontrol saja; huruf/spasi/simbol umum (termasuk unicode) dipertahankan.
        $seg = preg_replace('/[\x00-\x1F\x7F]/u', '', $seg) ?? $seg;
        if ($seg !== '') $clean[] = $seg;
    }
    return implode('/', $clean);
}

$folderClean = sanitizeR2PathSegment($folder);
$relpathClean = sanitizeR2PathSegment($relpath);

if ($folderClean === '' || $relpathClean === '') {
    apiJson(['error' => 'Nama folder / nama file tidak valid setelah dibersihkan.'], 400);
}

if (!R2FolderCompressor::isKnownMediaType($relpathClean)) {
    apiJson(['error' => 'Jenis file "' . basename($relpathClean) . '" tidak didukung (bukan foto/video).'], 400);
}

$baseKey = R2_ALBUM_PREFIX . $folderClean . '/' . $relpathClean;
$isVideoFile = R2FolderCompressor::isVideo($relpathClean);

// ── Offload video ke GitHub Actions kalau ffmpeg tidak tersedia di server ──
// Video TIDAK diupload apa adanya ke key final di sini — ditaruh dulu di
// staging R2 dengan key DETERMINISTIK:
//     _pending_video/{albumClean}/{relpath, ekstensi sudah dinormalisasi}
// (bukan nama acak) — supaya GitHub Actions bisa MEN-SCAN SENDIRI semua
// video yang tertunda untuk 1 album (list objek dengan prefix
// "_pending_video/{album}/"), tanpa perlu diberi tahu daftar file satu-per-
// satu oleh PHP. PHP hanya perlu menandai "album ini punya video pending"
// (flag ringan di session admin), lalu setelah SEMUA file di folder selesai
// diupload (loop di JS, admin/pages/galeri.php > runAlbumUpload), JS
// memanggil r2_video_batch_dispatch.php SATU KALI — cukup kirim NAMA ALBUM,
// tanpa daftar job — dan itu memicu SATU repository_dispatch. Di sisi
// GitHub Actions, runner itu scan staging album ini, lalu ffmpeg memproses
// SEMUA video yang ditemukan satu-per-satu (berurutan) dalam 1 run yang
// sama, upload hasilnya ke key final, generate poster, lalu hapus staging.
//
// Kalau kredensial GitHub belum diatur di private/secrets.php, video
// diproses lewat jalur lokal di bawah (fallback lama, butuh ffmpeg lokal).
if ($isVideoFile && !R2FolderCompressor::isFfmpegAvailable()
    && defined('SECRET_GITHUB_TOKEN') && defined('SECRET_GITHUB_REPO')) {

    $r2 = new R2WriteClient(SECRET_R2_ACCESS_KEY_WRITE, SECRET_R2_SECRET_KEY_WRITE, SECRET_R2_ENDPOINT, SECRET_R2_BUCKET);

    if ($skipExisting) {
        $existingSize = $r2->headObjectSize($baseKey);
        if ($existingSize !== null) {
            apiJson([
                'success' => true, 'skipped' => true, 'key' => $baseKey,
                'orig_kb' => round($file['size'] / 1024, 1),
                'new_kb'  => round($existingSize / 1024, 1),
            ]);
        }
    }

    // Key final: aturan ekstensi SAMA dengan R2FolderCompressor::compressVideo()
    // (mp4/mov/m4v/mkv dipertahankan, format lain dipaksa jadi .mp4). Nama file
    // di staging SUDAH pakai ekstensi final ini juga (bukan ekstensi asli),
    // supaya GitHub Actions tinggal cerminkan langsung struktur staging ->
    // final tanpa perlu tahu aturan normalisasi ekstensi ini sama sekali.
    $originalExt = strtolower(pathinfo($relpathClean, PATHINFO_EXTENSION));
    $safeReuseExts = ['mp4', 'mov', 'm4v', 'mkv'];
    $outExt = in_array($originalExt, $safeReuseExts, true) ? $originalExt : 'mp4';
    $targetKey = $baseKey;
    $relpathOutExt = $relpathClean;
    if ($outExt !== $originalExt) {
        $dir  = pathinfo($baseKey, PATHINFO_DIRNAME);
        $name = pathinfo($baseKey, PATHINFO_FILENAME) . '.' . $outExt;
        $targetKey = ($dir !== '.' ? $dir . '/' : '') . $name;

        $rDir  = pathinfo($relpathClean, PATHINFO_DIRNAME);
        $rName = pathinfo($relpathClean, PATHINFO_FILENAME) . '.' . $outExt;
        $relpathOutExt = ($rDir !== '.' ? $rDir . '/' : '') . $rName;
    }
    $posterKey  = $targetKey . '.poster.webp';
    // _pending_video/{album}/{relpath-ekstensi-final} — deterministik, TIDAK
    // acak, supaya GitHub Actions bisa menurunkan $targetKey/$posterKey
    // sendiri hanya dari key staging ini + nama album (lihat R2_PENDING_VIDEO_PREFIX
    // di config.php, konsisten dipakai juga oleh r2_video_batch_dispatch.php).
    $stagingKey = R2_PENDING_VIDEO_PREFIX . $folderClean . '/' . $relpathOutExt;

    try {
        // 1) Upload mentah ke staging (belum boleh dianggap final).
        $r2->putObjectFromFile($stagingKey, $file['tmp_name'], R2FolderCompressor::mimeTypeFor($relpathClean), [
            'original-filename' => basename($relpathClean),
            'uploaded-via'      => 'admin-media-manager-r2-staging',
        ]);

        // 2) JANGAN dispatch sekarang. Cukup tandai album ini "punya video
        //    pending" di session admin (flag ringan, BUKAN daftar job) —
        //    GitHub Actions nanti scan sendiri isi _pending_video/{album}/.
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
        if (!isset($_SESSION['albums_with_pending_video']) || !is_array($_SESSION['albums_with_pending_video'])) {
            $_SESSION['albums_with_pending_video'] = [];
        }
        $_SESSION['albums_with_pending_video'][$folderClean] = true;
        @session_write_close();

        r2AlbumCacheInvalidateStats($folderClean);
        r2AlbumCacheAddNameIfMissing($folderClean);

        apiJson([
            'success'     => true,
            'skipped'     => false,
            'key'         => $targetKey,
            'poster'      => $posterKey,
            'is_video'    => true,
            'queued'      => true,   // diantre; belum dikirim ke GitHub Actions
            'dispatched'  => false,
            'compressed'  => null,   // belum diketahui — masih menunggu batch job GitHub Actions
            'orig_kb'     => round($file['size'] / 1024, 1),
            'new_kb'      => null,
            'saved_pct'   => null,
            'note'        => 'Video diantre — akan diproses bersama video lain di folder ini dalam 1 job GitHub Actions.',
        ]);
    } catch (\Throwable $e) {
        // Staging ke R2 gagal → fallback: upload video apa adanya ke key final,
        // sama seperti perilaku lama (tidak boleh menggagalkan seluruh upload album).
        error_log('[r2_folder_upload] staging video gagal, fallback upload mentah: ' . $e->getMessage());
        try { $r2->deleteObject($stagingKey); } catch (\Throwable $ignore) {}

        try {
            $r2->putObjectFromFile($baseKey, $file['tmp_name'], R2FolderCompressor::mimeTypeFor($relpathClean), [
                'original-filename' => basename($relpathClean),
                'uploaded-via'      => 'admin-media-manager-r2-fallback',
            ]);
            r2AlbumCacheInvalidateStats($folderClean);
            r2AlbumCacheAddNameIfMissing($folderClean);
            apiJson([
                'success' => true, 'skipped' => false, 'key' => $baseKey,
                'poster' => null, 'is_video' => true, 'dispatched' => false, 'queued' => false,
                'compressed' => false,
                'orig_kb' => round($file['size'] / 1024, 1), 'new_kb' => round($file['size'] / 1024, 1),
                'saved_pct' => 0,
                'note' => 'Staging ke R2 gagal — video diupload tanpa kompresi.',
            ]);
        } catch (\Throwable $e2) {
            apiJson(['error' => 'Upload video gagal total: ' . $e2->getMessage()], 502);
        }
    }
}

// ── Compress (foto — atau video kalau ffmpeg LOKAL tersedia / GitHub belum
//    dikonfigurasi, memakai jalur lama seperti sebelumnya) ─────────────────
$compressed = null;
try {
    $compressed = R2FolderCompressor::compress($file['tmp_name']);

    if ($compressed['extOverride'] !== null) {
        $dir  = pathinfo($baseKey, PATHINFO_DIRNAME);
        $name = pathinfo($baseKey, PATHINFO_FILENAME) . '.' . $compressed['extOverride'];
        $baseKey = ($dir !== '.' ? $dir . '/' : '') . $name;
    }

    $r2 = new R2WriteClient(SECRET_R2_ACCESS_KEY_WRITE, SECRET_R2_SECRET_KEY_WRITE, SECRET_R2_ENDPOINT, SECRET_R2_BUCKET);

    if ($skipExisting) {
        $existingSize = $r2->headObjectSize($baseKey);
        if ($existingSize !== null) {
            apiJson([
                'success' => true, 'skipped' => true, 'key' => $baseKey,
                'orig_kb' => round($compressed['originalSize'] / 1024, 1),
                'new_kb'  => round($existingSize / 1024, 1),
            ]);
        }
    }

    $r2->putObjectFromFile($baseKey, $compressed['path'], $compressed['contentType'], [
        'original-filename' => basename($relpathClean),
        'uploaded-via'      => 'admin-media-manager-r2',
    ]);

    // ── Video → generate poster/thumbnail (screenshot frame) ───────────
    // Disimpan sebagai <key-video>.poster.webp di sebelah videonya, dipakai
    // publik untuk thumbnail grid galeri & atribut poster player video.
    // Kalau ffmpeg tidak tersedia / ekstraksi gagal → dilewati diam-diam,
    // frontend fallback ke ikon generik (tidak menggagalkan upload video).
    $posterKey = null;
    if (R2FolderCompressor::isVideo($relpathClean)) {
        $posterTmp = R2FolderCompressor::extractVideoPoster($compressed['path']);
        if ($posterTmp) {
            $posterKey = $baseKey . '.poster.webp';
            try {
                $r2->putObjectFromFile($posterKey, $posterTmp, 'image/webp', [
                    'uploaded-via' => 'admin-media-manager-r2-poster',
                ]);
            } catch (\Throwable $e) {
                error_log('[r2_folder_upload] poster upload gagal: ' . $e->getMessage());
                $posterKey = null;
            } finally {
                @unlink($posterTmp);
            }
        }
    }

    // File baru masuk album -> cache stats (jumlah file/ukuran) album ini basi.
    // Cukup hapus file cache lokal (tidak perlu panggil R2 lagi di sini);
    // dihitung ulang otomatis saat panel R2 berikutnya dibuka.
    r2AlbumCacheInvalidateStats($folderClean);
    r2AlbumCacheAddNameIfMissing($folderClean);
    if (function_exists('invalidateAllRelatedCaches')) {
        invalidateAllRelatedCaches('galeri', 'galeri_foto');
    }

    $origKb = round($compressed['originalSize'] / 1024, 1);
    $newKb  = round($compressed['outputSize'] / 1024, 1);
    $savedPct = $compressed['originalSize'] > 0
        ? round((($compressed['originalSize'] - $compressed['outputSize']) / $compressed['originalSize']) * 100, 1)
        : 0.0;

    apiJson([
        'success'    => true,
        'skipped'    => false,
        'key'        => $baseKey,
        'poster'     => $posterKey,
        'is_video'   => R2FolderCompressor::isVideo($relpathClean),
        'compressed' => $compressed['compressed'],
        'orig_kb'    => $origKb,
        'new_kb'     => $newKb,
        'saved_pct'  => $savedPct,
        'note'       => $compressed['note'],
    ]);
} catch (R2WriteException $e) {
    apiJson(['error' => 'Upload ke R2 gagal: ' . $e->getMessage()], 502);
} catch (\Throwable $e) {
    error_log('[r2_folder_upload] ' . $e->getMessage());
    apiJson(['error' => 'Error server: ' . $e->getMessage()], 500);
} finally {
    if ($compressed && !empty($compressed['isTemp']) && is_file($compressed['path'])) {
        @unlink($compressed['path']);
    }
}
