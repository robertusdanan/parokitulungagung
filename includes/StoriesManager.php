<?php
/**
 * includes/StoriesManager.php
 * ─────────────────────────────────────────────────────────────────────────
 * Manager data Stories Paroki Tulungagung.
 * Menyimpan maksimal 21 media (foto & video) di Cloudflare R2 (prefix `stories/`).
 * Sinkronisasi metadata otomatis antara file lokal `/cache/stories/stories.json`
 * dan Cloudflare R2 `stories/metadata.json`.
 */

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/R2WriteClient.php';

final class StoriesManager
{
    public const MAX_STORIES = 21;
    public const R2_PREFIX   = 'stories/';
    public const META_KEY    = 'stories/metadata.json';

    public static function getCacheFilePath(): string
    {
        $dir = rtrim(function_exists('cacheRootDir') ? cacheRootDir() : dirname(__DIR__), '/');
        return $dir . '/cache/stories/stories.json';
    }

    /**
     * Ambil seluruh item stories (maks 21 item), berurutan sesuai order.
     */
    public static function getStories(bool $forceRefresh = false): array
    {
        $path = self::getCacheFilePath();

        if (!$forceRefresh && file_exists($path)) {
            $raw = @file_get_contents($path);
            if ($raw) {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    return self::sortItems($decoded);
                }
            }
        }

        // Jika file lokal belum ada atau forceRefresh, coba ambil dari Cloudflare R2 CDN
        $items = self::fetchMetadataFromR2();
        if ($items !== null) {
            self::saveLocalCache($items);
            return self::sortItems($items);
        }

        return [];
    }

    /**
     * Tambah media baru ke stories (maks 21).
     */
    public static function addStory(array $storyData): array
    {
        $items = self::getStories();
        if (count($items) >= self::MAX_STORIES) {
            throw new RuntimeException('Batas maksimal ' . self::MAX_STORIES . ' media telah tercapai. Hapus media lain terlebih dahulu.');
        }

        $id = !empty($storyData['id']) ? (string)$storyData['id'] : 'st_' . bin2hex(random_bytes(6));
        $storyData['id']         = $id;
        $storyData['order']      = count($items); // taruh di urutan paling akhir
        $storyData['created_at'] = $storyData['created_at'] ?? date('c');

        $items[] = $storyData;
        self::saveAll($items);

        return $storyData;
    }

    /**
     * Update data sebuah media stories (misal: deskripsi atau nama file tampilan).
     */
    public static function updateStory(string $id, array $updates): bool
    {
        $items = self::getStories();
        $found = false;

        foreach ($items as &$item) {
            if (($item['id'] ?? '') === $id) {
                if (array_key_exists('file_name', $updates)) {
                    $item['file_name'] = trim((string)$updates['file_name']);
                }
                if (array_key_exists('description', $updates)) {
                    $item['description'] = trim((string)$updates['description']);
                }
                if (array_key_exists('order', $updates)) {
                    $item['order'] = (int)$updates['order'];
                }
                $item['updated_at'] = date('c');
                $found = true;
                break;
            }
        }
        unset($item);

        if (!$found) return false;

        return self::saveAll($items);
    }

    /**
     * Hapus media stories dari database dan Cloudflare R2 secara bersamaan.
     */
    public static function deleteStory(string $id): bool
    {
        $items = self::getStories();
        $targetIndex = null;
        $targetItem  = null;

        foreach ($items as $idx => $item) {
            if (($item['id'] ?? '') === $id) {
                $targetIndex = $idx;
                $targetItem  = $item;
                break;
            }
        }

        if ($targetIndex === null || $targetItem === null) {
            return false;
        }

        // Hapus file fisik dari Cloudflare R2
        try {
            $r2 = self::getR2Client();
            if (!empty($targetItem['r2_key'])) {
                $r2->deleteObject($targetItem['r2_key']);
            }
            if (!empty($targetItem['poster_key'])) {
                $r2->deleteObject($targetItem['poster_key']);
            }
        } catch (Throwable $e) {
            error_log('[StoriesManager] Gagal menghapus file R2: ' . $e->getMessage());
        }

        // Hapus dari list
        array_splice($items, $targetIndex, 1);

        // Re-index order
        foreach ($items as $k => &$it) {
            $it['order'] = $k;
        }
        unset($it);

        return self::saveAll($items);
    }

    /**
     * Urutkan ulang ID stories.
     */
    public static function reorderStories(array $orderedIds): bool
    {
        $items = self::getStories();
        $map   = [];
        foreach ($items as $it) {
            $map[$it['id']] = $it;
        }

        $newItems = [];
        $order = 0;
        foreach ($orderedIds as $id) {
            if (isset($map[$id])) {
                $map[$id]['order'] = $order++;
                $newItems[] = $map[$id];
                unset($map[$id]);
            }
        }

        // Tambahkan sisa jika ada yang terlewat
        foreach ($map as $remaining) {
            $remaining['order'] = $order++;
            $newItems[] = $remaining;
        }

        return self::saveAll($newItems);
    }

    // ── Helper Internal ───────────────────────────────────────────────────

    private static function sortItems(array $items): array
    {
        usort($items, function ($a, $b) {
            $oa = $a['order'] ?? 0;
            $ob = $b['order'] ?? 0;
            if ($oa === $ob) return 0;
            return ($oa < $ob) ? -1 : 1;
        });
        return array_values($items);
    }

    private static function saveLocalCache(array $items): void
    {
        $path = self::getCacheFilePath();
        $dir  = dirname($path);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $tmp = $path . '.tmp.' . bin2hex(random_bytes(4));
        file_put_contents($tmp, json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        @rename($tmp, $path);
    }

    private static function saveAll(array $items): bool
    {
        $items = self::sortItems($items);
        self::saveLocalCache($items);

        // Upload metadata.json ke R2 agar permanen dan sinkron
        try {
            $r2 = self::getR2Client();
            $jsonString = json_encode($items, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $tmpFile = tempnam(sys_get_temp_dir(), 'st_meta_');
            file_put_contents($tmpFile, $jsonString);

            $r2->putObjectFromFile(self::META_KEY, $tmpFile, 'application/json', [
                'cache-control' => 'no-cache, must-revalidate'
            ]);
            @unlink($tmpFile);
            return true;
        } catch (Throwable $e) {
            error_log('[StoriesManager] Gagal sinkronisasi metadata ke R2: ' . $e->getMessage());
            return true; // Local cache tetap sukses
        }
    }

    private static function fetchMetadataFromR2(): ?array
    {
        $cdnUrl = defined('R2_CDN_URL') ? rtrim(R2_CDN_URL, '/') : 'https://img.parokitulungagung.org';
        $metaUrl = $cdnUrl . '/' . self::META_KEY . '?v=' . time();

        $ch = curl_init($metaUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => true
        ]);
        $res  = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code === 200 && is_string($res)) {
            $decoded = json_decode($res, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }
        return null;
    }

    private static function getR2Client(): R2WriteClient
    {
        if (function_exists('getR2WriteClient')) {
            return getR2WriteClient();
        }
        $ak = defined('SECRET_R2_ACCESS_KEY_WRITE') ? SECRET_R2_ACCESS_KEY_WRITE : (defined('SECRET_R2_ACCESS_KEY') ? SECRET_R2_ACCESS_KEY : '');
        $sk = defined('SECRET_R2_SECRET_KEY_WRITE') ? SECRET_R2_SECRET_KEY_WRITE : (defined('SECRET_R2_SECRET_KEY') ? SECRET_R2_SECRET_KEY : '');
        $ep = defined('SECRET_R2_ENDPOINT') ? SECRET_R2_ENDPOINT : '';
        $bk = defined('SECRET_R2_BUCKET') ? SECRET_R2_BUCKET : '';
        return new R2WriteClient($ak, $sk, $ep, $bk);
    }
}
