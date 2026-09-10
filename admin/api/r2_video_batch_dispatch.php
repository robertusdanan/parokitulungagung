<?php
/**
 * admin/api/r2_video_batch_dispatch.php
 * ─────────────────────────────────────────────────────────────────────────
 * Dipanggil SATU KALI oleh JS (admin/pages/galeri.php & admin/pages/media.php
 * > runAlbumUpload/runR2Upload) SETELAH loop upload semua file dalam 1
 * folder/album selesai.
 *
 * PHP di sini TIDAK mengirim daftar file video — cukup NAMA ALBUM. GitHub
 * Actions sendiri yang men-scan isi staging R2
 * ({R2_PENDING_VIDEO_PREFIX}{album}/...) dan memutuskan video mana saja yang
 * perlu diproses (lihat r2_folder_upload.php: video yang ffmpeg-nya
 * di-offload ditaruh di staging dengan KEY DETERMINISTIK — bukan nama acak —
 * supaya GitHub Actions bisa menurunkan sendiri key final-nya hanya dari
 * struktur path staging + nama album, TANPA perlu diberi tahu satu-satu).
 *
 * Hasilnya: 1 folder dengan N video = 1 run GitHub Actions (bukan N run
 * paralel terpisah), dan payload dispatch-nya SELALU kecil (cuma nama
 * album) berapa pun banyaknya video di dalamnya.
 *
 * Alur di sisi hosting SETELAH endpoint ini sukses: TIDAK ADA lagi yang
 * perlu ditunggu dari server hosting — upload album dianggap selesai dari
 * sisi hosting, kompresi video berjalan async murni di GitHub Actions
 * (lapor balik ke r2_video_job_callback.php per video selesai).
 *
 * POST (form-urlencoded ATAU JSON body):
 *   folder — nama folder/album (sama seperti dikirim ke r2_folder_upload.php)
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
adminBoot();

header('Content-Type: application/json; charset=utf-8');

$user = apiRequirePageAccess('media', 'create');

require_once __DIR__ . '/../../includes/R2WriteClient.php';
require_once __DIR__ . '/../../includes/GitHubDispatcher.php';
require_once __DIR__ . '/../includes/R2AlbumCache.php';

$folder = trim((string)($_POST['folder'] ?? ''));
if ($folder === '') {
    $raw = json_decode((string)file_get_contents('php://input'), true);
    if (is_array($raw)) $folder = trim((string)($raw['folder'] ?? ''));
}
if ($folder === '') {
    apiJson(['error' => 'Nama folder wajib diisi.'], 400);
}

/**
 * Sanitasi SAMA PERSIS dengan sanitizeR2PathSegment() di r2_folder_upload.php
 * — HARUS identik supaya nama album ($folderClean) yang dipakai sebagai key
 * flag session & prefix staging cocok dengan yang ditulis di sana.
 */
function sanitizeR2PathSegmentForBatch(string $path): string
{
    $path = str_replace("\0", '', $path);
    $path = str_replace('\\', '/', $path);
    $segments = explode('/', $path);
    $clean = [];
    foreach ($segments as $seg) {
        $seg = trim($seg);
        if ($seg === '' || $seg === '.' || $seg === '..') continue;
        $seg = preg_replace('/[\x00-\x1F\x7F]/u', '', $seg) ?? $seg;
        if ($seg !== '') $clean[] = $seg;
    }
    return implode('/', $clean);
}

$folderClean = sanitizeR2PathSegmentForBatch($folder);
if ($folderClean === '') {
    apiJson(['error' => 'Nama folder tidak valid setelah dibersihkan.'], 400);
}

$hadPendingVideo = !empty($_SESSION['albums_with_pending_video'][$folderClean]);
unset($_SESSION['albums_with_pending_video'][$folderClean]);
session_write_close();

if (!$hadPendingVideo) {
    apiJson([
        'success'    => true,
        'dispatched' => false,
        'count'      => 0,
        'note'       => 'Tidak ada video yang diantre untuk album ini (mungkin folder tidak berisi video, atau sudah pernah didispatch).',
    ]);
}

if (!defined('SECRET_GITHUB_TOKEN') || !defined('SECRET_GITHUB_REPO')) {
    apiJson(['error' => 'Kredensial GitHub (SECRET_GITHUB_TOKEN / SECRET_GITHUB_REPO) belum diatur di private/secrets.php.'], 500);
}

$stagingPrefix = R2_PENDING_VIDEO_PREFIX . $folderClean . '/';

try {
    $gh = new GitHubDispatcher(SECRET_GITHUB_TOKEN, SECRET_GITHUB_REPO);

    // Payload SELALU kecil — cuma nama album + prefix staging-nya. GitHub
    // Actions yang men-scan sendiri berapa pun banyak video di dalamnya.
    $gh->dispatch('compress-video-batch', [
        'album'          => $folderClean,
        'staging_prefix' => $stagingPrefix,
        'target_prefix'  => R2_ALBUM_PREFIX . $folderClean . '/',
    ]);

    r2AlbumCacheInvalidateStats($folderClean);
    r2AlbumCacheAddNameIfMissing($folderClean);

    apiJson([
        'success'    => true,
        'dispatched' => true,
        'github_url' => $gh->actionsUrl('compress-video.yml'),
        'note'       => 'Album "' . $folderClean . '" dikirim ke GitHub Actions (1 run, semua video di dalamnya diproses berurutan).',
    ]);
} catch (\Throwable $e) {
    // Dispatch gagal total (mis. GitHub API down / token salah) → fallback:
    // list SEMUA object yang ada di staging album ini, lalu salin masing-
    // masing ke key final APA ADANYA (server-side copy di R2, tanpa
    // kompresi) supaya album tidak "menggantung" menunggu job yang gagal
    // terkirim. Staging dibersihkan setelah berhasil disalin.
    error_log('[r2_video_batch_dispatch] dispatch batch gagal, fallback scan+copy mentah: ' . $e->getMessage());

    if (!defined('SECRET_R2_ACCESS_KEY_WRITE') || !defined('SECRET_R2_SECRET_KEY_WRITE')
        || !defined('SECRET_R2_ENDPOINT') || !defined('SECRET_R2_BUCKET')) {
        apiJson(['error' => 'Dispatch GitHub gagal, dan kredensial R2 (write) juga belum diatur untuk fallback: ' . $e->getMessage()], 502);
    }

    $r2 = new R2WriteClient(SECRET_R2_ACCESS_KEY_WRITE, SECRET_R2_SECRET_KEY_WRITE, SECRET_R2_ENDPOINT, SECRET_R2_BUCKET);
    $objects = $r2->listAllObjects($stagingPrefix);

    $okCount = 0;
    $failCount = 0;
    foreach ($objects as $obj) {
        $stagingKey = $obj['key'];
        $relpath = substr($stagingKey, strlen($stagingPrefix));
        if ($relpath === '' || $relpath === false) { $failCount++; continue; }
        $targetKey = R2_ALBUM_PREFIX . $folderClean . '/' . $relpath;
        try {
            $r2->copyObject($stagingKey, $targetKey);
            $r2->deleteObject($stagingKey);
            $okCount++;
        } catch (\Throwable $e2) {
            error_log('[r2_video_batch_dispatch] fallback copy gagal untuk ' . $stagingKey . ': ' . $e2->getMessage());
            $failCount++;
        }
    }

    r2AlbumCacheInvalidateStats($folderClean);

    apiJson([
        'success'        => true,
        'dispatched'     => false,
        'count'          => count($objects),
        'fallback_ok'    => $okCount,
        'fallback_fail'  => $failCount,
        // Pesan asli dari GitHubDispatcher (kode HTTP + potongan respons API
        // GitHub, ATAU pesan cURL kalau request-nya sendiri gagal jalan) —
        // ditampilkan langsung di UI admin (lihat galeri.php/media.php JS,
        // yang menampilkan field 'note' ini di log upload) supaya tidak perlu
        // buka error_log server untuk diagnosis.
        'dispatch_error' => $e->getMessage(),
        'note'           => 'GitHub Actions gagal dihubungi (' . $e->getMessage() . ') — video diupload tanpa kompresi (fallback salin mentah).',
    ]);
}
