<?php
/**
 * r2-video-webhook.php
 * ─────────────────────────────────────────────────────────────────────────
 * Webhook publik untuk menerima callback dari GitHub Actions setelah
 * kompresi video batch selesai (berada di root agar tidak terblokir oleh
 * Cloudflare WAF / firewall admin).
 *
 * Header / Param:
 *   - Header: X-Webhook-Secret: <SECRET_VIDEO_WEBHOOK_SECRET>
 *   - ATAU GET param: ?token=<SECRET_VIDEO_WEBHOOK_SECRET>
 * Body JSON (Batch):
 *   {
 *     "album": "...",
 *     "status": "done"|"failed",
 *     "processed_count": 5,
 *     "target_keys": ["galerifoto/.../C0001.mp4", ...]
 *   }
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/includes/functions.php';
require_once privatePath('secrets.php');
require_once __DIR__ . '/includes/GaleriCache.php';

function webhookResponse(array $data, int $code = 200): never {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

if (!defined('SECRET_VIDEO_WEBHOOK_SECRET')) {
    webhookResponse(['error' => 'Secret webhook belum disetel'], 500);
}

// Ambil secret dari header atau query string token
$given = $_SERVER['HTTP_X_WEBHOOK_SECRET'] ?? $_GET['token'] ?? '';
if (!hash_equals(SECRET_VIDEO_WEBHOOK_SECRET, (string)$given)) {
    webhookResponse(['error' => 'Unauthorized'], 401);
}

$raw  = file_get_contents('php://input');
$body = json_decode((string)$raw, true);
if (!is_array($body)) {
    // Dukung juga payload via $_POST biasa
    $body = $_POST;
}

$album          = trim((string)($body['album'] ?? $_GET['album'] ?? ''));
$status         = trim((string)($body['status'] ?? 'done'));
$processedCount = (int)($body['processed_count'] ?? 0);
$targetKey      = trim((string)($body['target_key'] ?? ''));

// Dukung format batch (array target_keys) maupun single (target_key)
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

if ($album === '') {
    webhookResponse(['error' => 'Parameter album wajib diisi'], 400);
}

// Invalidate cache galeri foto publik
if (function_exists('galeriPhotoCacheInvalidateByFolder')) {
    galeriPhotoCacheInvalidateByFolder($album);
}

// Update Stories jika album stories atau ada target key stories/
if ($album === 'stories' || strpos(implode(' ', $targetKeys), 'stories/') !== false) {
    $storiesMgrFile = __DIR__ . '/includes/StoriesManager.php';
    if (file_exists($storiesMgrFile)) {
        require_once $storiesMgrFile;
        if (class_exists('StoriesManager')) {
            StoriesManager::handleVideoWebhookCallback($targetKeys);
        }
    }
}

// Invalidate cache admin R2 jika ada
$adminCacheFile = __DIR__ . '/admin/includes/R2AlbumCache.php';
if (file_exists($adminCacheFile)) {
    require_once $adminCacheFile;
    if (function_exists('r2AlbumCacheInvalidateStats')) {
        r2AlbumCacheInvalidateStats($album);
    }
    if (function_exists('r2AlbumCacheAddNameIfMissing')) {
        r2AlbumCacheAddNameIfMissing($album);
    }
}

error_log("[r2_video_webhook] Callback batch video selesai. album={$album}, count={$processedCount}, status={$status}");

webhookResponse([
    'success'         => true,
    'message'         => 'Cache album berhasil diinvalidasi',
    'album'           => $album,
    'processed_count' => $processedCount,
    'target_keys'     => $targetKeys,
    'status'          => $status
]);
