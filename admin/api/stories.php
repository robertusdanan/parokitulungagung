<?php
/**
 * admin/api/stories.php
 * ─────────────────────────────────────────────────────────────────────────
 * Backend API untuk manajemen Stories (maksimal 21 media).
 * Mendukung list, upload (foto WebP q60 max 1600px / video mp4 + webp poster),
 * update deskripsi & nama file, reorder urutan, dan delete (termasuk hapus dari R2).
 */

ob_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../../includes/StoriesManager.php';
require_once __DIR__ . '/../../includes/R2FolderCompressor.php';
require_once __DIR__ . '/../../includes/GitHubDispatcher.php';

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? ($_POST['action'] ?? '');

if ($method === 'GET' && ($action === 'list' || $action === '')) {
    apiRequirePageAccess('stories', 'list');
    $items = StoriesManager::getStories();
    apiJson([
        'success' => true,
        'items'   => $items,
        'total'   => count($items),
        'max'     => StoriesManager::MAX_STORIES
    ]);
}

// Untuk POST actions
if ($method === 'POST') {
    if (empty($action)) {
        $body = jsonBody();
        $action = $body['action'] ?? '';
    }

    if ($action === 'upload') {
        apiRequirePageAccess('stories', 'create');

        $currentItems = StoriesManager::getStories();
        if (count($currentItems) >= StoriesManager::MAX_STORIES) {
            apiJson([
                'success' => false,
                'error'   => 'Batas maksimal ' . StoriesManager::MAX_STORIES . ' media telah tercapai. Hapus salah satu media terlebih dahulu.'
            ], 400);
        }

        if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $errCode = $_FILES['file']['error'] ?? -1;
            apiJson(['success' => false, 'error' => 'File tidak berhasil diunggah (error code: ' . $errCode . ')'], 400);
        }

        $tmpPath      = $_FILES['file']['tmp_name'];
        $origName     = $_FILES['file']['name'];
        $customName   = trim($_POST['file_name'] ?? '');
        $description  = trim($_POST['description'] ?? '');
        $fileSize     = (int)($_FILES['file']['size'] ?? filesize($tmpPath) ?: 0);

        // Tentukan nama dasar file
        $baseRawName = $customName !== '' ? $customName : pathinfo($origName, PATHINFO_FILENAME);
        // Sanitasi nama file agar aman di URL & S3
        $safeBase = preg_replace('/[^a-zA-Z0-9_\-\s]/', '', $baseRawName);
        $safeBase = preg_replace('/\s+/', '_', trim($safeBase));
        if ($safeBase === '') {
            $safeBase = 'story_' . time();
        }

        $uniqueSuffix = '_' . substr(bin2hex(random_bytes(3)), 0, 5);
        $isVideo = R2FolderCompressor::isVideo($origName);
        $r2 = function_exists('getR2WriteClient') ? getR2WriteClient() : null;
        if (!$r2) {
            apiJson(['success' => false, 'error' => 'Klien Cloudflare R2 tidak tersedia.'], 500);
        }

        $cdnUrl = defined('R2_CDN_URL') ? rtrim(R2_CDN_URL, '/') : 'https://img.parokitulungagung.org';

        if ($isVideo) {
            // ── PROSES VIDEO: OFFLOAD KE GITHUB ACTIONS ──
            // Target format selalu .mp4 untuk kompatibilitas web maksimal
            $targetKey    = StoriesManager::R2_PREFIX . $safeBase . $uniqueSuffix . '.mp4';
            $posterKey    = StoriesManager::R2_PREFIX . $safeBase . $uniqueSuffix . '.webp';
            $stagingPrefix = (defined('R2_PENDING_VIDEO_PREFIX') ? R2_PENDING_VIDEO_PREFIX : '_pending_video/') . 'stories/';
            $stagingKey   = $stagingPrefix . $safeBase . $uniqueSuffix . '.mp4';

            $mimeType = R2FolderCompressor::mimeTypeFor($origName);
            if (empty($mimeType) || $mimeType === 'application/octet-stream') {
                $mimeType = 'video/mp4';
            }

            try {
                // 1) Upload mentah ke R2 staging
                $r2->putObjectFromFile($stagingKey, $tmpPath, $mimeType, [
                    'original-filename' => basename($origName),
                    'uploaded-via'      => 'admin-stories-staging',
                ]);
            } catch (Throwable $e) {
                apiJson(['success' => false, 'error' => 'Gagal upload video ke server: ' . $e->getMessage()], 500);
            }

            // 2) Dispatch ke GitHub Actions (compress-video-batch)
            $dispatched = false;
            if (defined('SECRET_GITHUB_TOKEN') && defined('SECRET_GITHUB_REPO')) {
                try {
                    $gh = new GitHubDispatcher(SECRET_GITHUB_TOKEN, SECRET_GITHUB_REPO);
                    $gh->dispatch('compress-video-batch', [
                        'album'          => 'stories',
                        'staging_prefix' => $stagingPrefix,
                        'target_prefix'  => StoriesManager::R2_PREFIX,
                    ]);
                    $dispatched = true;
                } catch (Throwable $e) {
                    error_log('[stories.php] Dispatch GitHub Actions gagal: ' . $e->getMessage());
                }
            }

            $storyData = [
                'type'         => 'video',
                'file_name'    => $baseRawName,
                'r2_key'       => $targetKey,
                'url'          => $cdnUrl . '/' . $targetKey,
                'poster_key'   => $posterKey,
                'poster_url'   => $cdnUrl . '/' . $posterKey,
                'video_status' => 'processing',
                'description'  => $description,
                'size'         => $fileSize,
            ];

            $saved = StoriesManager::addStory($storyData);
            apiJson([
                'success'    => true,
                'item'       => $saved,
                'dispatched' => $dispatched,
                'note'       => 'Video sedang dikompresi otomatis di cloud. Poster dan video web-ready akan tampil setelah selesai.'
            ]);

        } else {
            // ── PROSES FOTO (WebP q60 max 1600px) ──
            $compResult = R2FolderCompressor::compress($tmpPath);
            $photoPathToUpload = $compResult['path'];
            $targetExt = $compResult['extOverride'] ?: 'webp';
            $targetKey = StoriesManager::R2_PREFIX . $safeBase . $uniqueSuffix . '.' . $targetExt;

            try {
                $r2->putObjectFromFile($targetKey, $photoPathToUpload, $compResult['contentType'] ?: 'image/webp', [
                    'cache-control' => 'public, max-age=31536000, immutable'
                ]);
            } catch (Throwable $e) {
                if ($compResult['isTemp'] && file_exists($compResult['path'])) @unlink($compResult['path']);
                apiJson(['success' => false, 'error' => 'Gagal upload foto ke Cloudflare R2: ' . $e->getMessage()], 500);
            }

            if ($compResult['isTemp'] && file_exists($compResult['path'])) {
                @unlink($compResult['path']);
            }

            $storyData = [
                'type'        => 'image',
                'file_name'   => $baseRawName,
                'r2_key'      => $targetKey,
                'url'         => $cdnUrl . '/' . $targetKey,
                'poster_key'  => null,
                'poster_url'  => null,
                'description' => $description,
                'size'        => $compResult['outputSize'] ?: $fileSize,
            ];

            $saved = StoriesManager::addStory($storyData);
            apiJson(['success' => true, 'item' => $saved]);
        }
    }

    if ($action === 'update') {
        apiRequirePageAccess('stories', 'edit');
        $body = jsonBody();
        $id   = trim((string)($body['id'] ?? ''));
        if ($id === '') {
            apiJson(['success' => false, 'error' => 'ID media wajib diisi.'], 400);
        }

        $updates = [];
        if (isset($body['file_name']))   $updates['file_name']   = $body['file_name'];
        if (isset($body['description'])) $updates['description'] = $body['description'];
        if (isset($body['order']))       $updates['order']       = (int)$body['order'];

        $ok = StoriesManager::updateStory($id, $updates);
        if (!$ok) {
            apiJson(['success' => false, 'error' => 'Media tidak ditemukan atau gagal diperbarui.'], 404);
        }
        apiJson(['success' => true]);
    }

    if ($action === 'delete') {
        apiRequirePageAccess('stories', 'delete');
        $body = jsonBody();
        $id   = trim((string)($body['id'] ?? ''));
        if ($id === '') {
            apiJson(['success' => false, 'error' => 'ID media wajib diisi.'], 400);
        }

        $ok = StoriesManager::deleteStory($id);
        if (!$ok) {
            apiJson(['success' => false, 'error' => 'Media tidak ditemukan atau gagal dihapus.'], 404);
        }
        apiJson(['success' => true]);
    }

    if ($action === 'reorder') {
        apiRequirePageAccess('stories', 'edit');
        $body = jsonBody();
        $ids  = $body['ids'] ?? [];
        if (!is_array($ids)) {
            apiJson(['success' => false, 'error' => 'Array ID tidak valid.'], 400);
        }

        $ok = StoriesManager::reorderStories($ids);
        apiJson(['success' => $ok]);
    }
}

apiJson(['error' => 'Action tidak dikenal.'], 400);
