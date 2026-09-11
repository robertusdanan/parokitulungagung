<?php
/**
 * admin/api/r2_video_job_callback.php
 * ─────────────────────────────────────────────────────────────────────────
 * Dipanggil oleh WORKFLOW GitHub Actions (bukan oleh browser admin) setelah
 * job kompresi video batch selesai (sukses maupun gagal), supaya cache statistik
 * album di Media Manager langsung basi & dihitung ulang saat admin membuka
 * panel R2 berikutnya — TANPA perlu polling dari browser.
 *
 * Endpoint ini SENGAJA tidak lewat adminBoot()/session admin (GitHub Actions
 * tidak punya cookie sesi) — otentikasi memakai shared secret di header,
 * BUKAN session. Simpan secret yang SAMA di:
 *   - private/secrets.php  -> define('SECRET_VIDEO_WEBHOOK_SECRET', '...');
 *   - GitHub repo Settings > Secrets > Actions -> WEBHOOK_SECRET
 *
 * POST JSON body (Batch / Single):
 *   {
 *     "album": "...",
 *     "status": "done"|"failed",
 *     "processed_count": 5,
 *     "target_keys": ["galerifoto/.../C0001.mp4", ...],
 *     "message": "opsional, alasan kalau gagal"
 *   }
 * Header wajib:
 *   X-Webhook-Secret: <SECRET_VIDEO_WEBHOOK_SECRET>
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/R2AlbumCache.php';
require_once __DIR__ . '/../../includes/GaleriCache.php';

header('Content-Type: application/json; charset=utf-8');

function webhookJson(array $data, int $code = 200): never
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

if (!defined('SECRET_VIDEO_WEBHOOK_SECRET')) {
    webhookJson(['error' => 'SECRET_VIDEO_WEBHOOK_SECRET belum diatur di private/secrets.php.'], 500);
}

$given = $_SERVER['HTTP_X_WEBHOOK_SECRET'] ?? '';
if (!hash_equals(SECRET_VIDEO_WEBHOOK_SECRET, $given)) {
    error_log('[r2_video_job_callback] secret tidak cocok, request ditolak.');
    webhookJson(['error' => 'Unauthorized'], 401);
}

$raw  = file_get_contents('php://input');
$body = json_decode((string)$raw, true);
if (!is_array($body)) {
    webhookJson(['error' => 'Body JSON tidak valid.'], 400);
}

$album          = trim((string)($body['album'] ?? ''));
$status         = trim((string)($body['status'] ?? ''));
$message        = trim((string)($body['message'] ?? ''));
$processedCount = (int)($body['processed_count'] ?? 0);
$targetKey      = trim((string)($body['target_key'] ?? ''));

$targetKeys = [];
if (isset($body['target_keys']) && is_array($body['target_keys'])) {
    foreach ($body['target_keys'] as $k) {
        $kTrim = trim((string)$k);
        if ($kTrim !== '') $targetKeys[] = $kTrim;
    }
} elseif ($targetKey !== '') {
    $targetKeys[] = $targetKey;
}

if ($processedCount === 0 && !empty($targetKeys)) {
    $processedCount = count($targetKeys);
}

if ($album === '' || !in_array($status, ['done', 'failed'], true)) {
    webhookJson(['error' => 'Field "album" dan "status" (done/failed) wajib diisi.'], 400);
}

// Cache album basi -> dihitung ulang otomatis saat panel R2 dibuka lagi.
r2AlbumCacheInvalidateStats($album);
r2AlbumCacheAddNameIfMissing($album);

// Invalidate cache web publik galeri foto supaya video terkompres langsung muncul
if (function_exists('galeriPhotoCacheInvalidateByFolder')) {
    galeriPhotoCacheInvalidateByFolder($album);
}

if ($status === 'failed') {
    error_log("[r2_video_job_callback] GitHub Actions GAGAL kompres video. album={$album} count={$processedCount} pesan={$message}");
} else {
    error_log("[r2_video_job_callback] GitHub Actions selesai batch kompres video. album={$album} count={$processedCount}");
}

webhookJson([
    'success'         => true,
    'album'           => $album,
    'processed_count' => $processedCount,
    'target_keys'     => $targetKeys,
    'status'          => $status
]);
