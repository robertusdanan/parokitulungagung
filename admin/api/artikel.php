<?php
ob_start();
/**
 * admin/api/artikel.php — API CRUD Artikel via Supabase
 * + Auto-konversi OG Preview saat artikel disimpan dengan thumbnail
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../../includes/SupabaseArticleManager.php';
adminBoot();

header('Content-Type: application/json; charset=utf-8');

$body   = jsonBody();
$action = $body['action'] ?? '';
$menu   = $body['menu']   ?? '';

if (!in_array($menu, SupabaseArticleManager::MENUS)) {
    apiJson(['error' => 'Menu tidak valid: ' . $menu], 400);
}

$currentUser = apiRequirePageAccess($menu, 'list');

// ═══════════════════════════════════════════════════════════════════
// HELPER: Role Checker
// ═══════════════════════════════════════════════════════════════════

function isSuperAdmin(array $user): bool {
    return $user['role'] === ROLE_SUPERADMIN;
}
function isEditor(array $user, string $menu): bool {
    if (isSuperAdmin($user)) return true;
    $perms = getPermissionsMap($user);
    return in_array('publish', $perms[$menu] ?? []);
}
function isOwnArtikel(array $user, array $art): bool {
    $penulis  = $art['penulis'] ?? '';
    $nama     = trim($user['nama'] ?? '');
    $username = $user['username'] ?? '';
    return $penulis === $username || ($nama !== '' && $penulis === $nama);
}
function canEditArtikel(array $user, string $menu, array $art): bool {
    if (isSuperAdmin($user)) return true;
    return isOwnArtikel($user, $art);
}
function canDeleteArtikel(array $user, string $menu, array $art): bool {
    return canEditArtikel($user, $menu, $art);
}
function canPublishArtikel(array $user, string $menu): bool {
    return isEditor($user, $menu);
}
function canViewArtikel(array $user, string $menu, array $art): bool {
    if (isSuperAdmin($user) || isEditor($user, $menu)) return true;
    return ($art['status'] ?? '') === 'published' || isOwnArtikel($user, $art);
}
function getDisplayName(array $user): string {
    $nama = trim($user['nama'] ?? '');
    return $nama !== '' ? $nama : ($user['username'] ?? '');
}

// ═══════════════════════════════════════════════════════════════════
// HELPER: Auto OG Preview Conversion
// ═══════════════════════════════════════════════════════════════════

/**
 * Konversi thumbnail artikel ke JPG 1200×630 untuk OG WhatsApp/Facebook.
 * Dipanggil otomatis setelah artikel disimpan.
 *
 * @param  string $thumbnailUrl  URL thumbnail, mis. /img/artikel/thumb-xxx.webp
 * @return array  ['success'=>bool, 'og_url'=>string|null, 'note'=>string]
 */
function autoConvertOG(string $thumbnailUrl): array {
    if (!$thumbnailUrl) return ['success' => false, 'note' => 'Tidak ada thumbnail'];

    $root = rtrim($_SERVER['DOCUMENT_ROOT'], '/');

    // Ambil konten gambar sumber (lokal atau remote R2 CDN)
    $thumbPath = preg_replace('#^https?://[^/]+#', '', $thumbnailUrl);
    $thumbPath = '/' . ltrim($thumbPath, '/');
    $srcFull = $root . $thumbPath;

    $imgData = null;
    if (file_exists($srcFull)) {
        $imgData = @file_get_contents($srcFull);
    } elseif (filter_var($thumbnailUrl, FILTER_VALIDATE_URL)) {
        $imgData = @file_get_contents($thumbnailUrl);
    } else {
        $cdnUrl = function_exists('artikelImageUrl') ? artikelImageUrl($thumbnailUrl) : ('https://img.parokitulungagung.org/artikel/' . basename($thumbnailUrl));
        $imgData = @file_get_contents($cdnUrl);
    }

    if (!$imgData) {
        return ['success' => false, 'note' => 'Gambar thumbnail tidak dapat diakses'];
    }

    if (!function_exists('imagecreatefromstring')) {
        return ['success' => false, 'note' => 'GD Library tidak tersedia'];
    }

    $im = @imagecreatefromstring($imgData);
    if (!$im) return ['success' => false, 'note' => 'GD gagal membaca thumbnail'];

    // Nama & path OG — TANPA prefix "og-", cukup ganti ekstensi ke .jpg
    $thumbBase  = pathinfo(basename($thumbnailUrl), PATHINFO_FILENAME);
    $ogName     = $thumbBase . '.jpg';
    $ogDir      = $root . '/img/ogpreview';
    if (!is_dir($ogDir)) @mkdir($ogDir, 0755, true);
    $ogFull     = $ogDir . '/' . $ogName;

    // Target: 1200×630 (crop center)
    $srcW = imagesx($im);
    $srcH = imagesy($im);
    $dstW = 1200;
    $dstH = 630;

    $srcRatio = $srcW / $srcH;
    $dstRatio = $dstW / $dstH;
    if ($srcRatio > $dstRatio) {
        $cropH = $srcH;
        $cropW = (int)($srcH * $dstRatio);
        $cropX = (int)(($srcW - $cropW) / 2);
        $cropY = 0;
    } else {
        $cropW = $srcW;
        $cropH = (int)($srcW / $dstRatio);
        $cropX = 0;
        $cropY = (int)(($srcH - $cropH) / 2);
    }

    $out   = imagecreatetruecolor($dstW, $dstH);
    $white = imagecolorallocate($out, 255, 255, 255);
    imagefill($out, 0, 0, $white);
    imagecopyresampled($out, $im, 0, 0, $cropX, $cropY, $dstW, $dstH, $cropW, $cropH);
    imagedestroy($im);

    $tmpFull = tempnam(sys_get_temp_dir(), 'og_') . '.jpg';
    $ok      = imagejpeg($out, $tmpFull, 85);
    imagedestroy($out);

    if (!$ok || !file_exists($tmpFull)) {
        @unlink($tmpFull);
        return ['success' => false, 'note' => 'Gagal membuat file OG'];
    }

    $ogKb = round(filesize($tmpFull) / 1024, 1);

    // Upload ke Cloudflare R2 bucket parokitulungagung/ogpreview/
    require_once __DIR__ . '/../../includes/R2WriteClient.php';
    $ak = defined('SECRET_R2_ACCESS_KEY_WRITE') ? SECRET_R2_ACCESS_KEY_WRITE : (defined('R2_ACCESS_KEY') ? R2_ACCESS_KEY : null);
    $sk = defined('SECRET_R2_SECRET_KEY_WRITE') ? SECRET_R2_SECRET_KEY_WRITE : (defined('R2_SECRET_KEY') ? R2_SECRET_KEY : null);
    $ep = defined('SECRET_R2_ENDPOINT') ? SECRET_R2_ENDPOINT : (defined('R2_ENDPOINT') ? R2_ENDPOINT : null);
    $bk = defined('SECRET_R2_BUCKET') ? SECRET_R2_BUCKET : (defined('R2_BUCKET') ? R2_BUCKET : null);

    if ($ak && $sk && $ep && $bk) {
        try {
            $r2 = new R2WriteClient($ak, $sk, $ep, $bk);
            $r2->putObjectFromFile('ogpreview/' . $ogName, $tmpFull, 'image/jpeg');
        } catch (Throwable $e) {
            error_log('[autoConvertOG] R2 upload failed: ' . $e->getMessage());
        }
    }

    if (is_dir($ogDir) && is_writable($ogDir)) {
        if (file_exists($ogFull)) @unlink($ogFull);
        @copy($tmpFull, $ogFull);
    }
    @unlink($tmpFull);

    $cdn = defined('R2_CDN_URL') ? rtrim(R2_CDN_URL, '/') : 'https://img.parokitulungagung.org';
    $ogUrl = $cdn . '/ogpreview/' . $ogName;

    // Update cache OG
    $cacheFile = $root . '/cache/media/media_og.json';
    $cacheDir  = dirname($cacheFile);
    if (!is_dir($cacheDir)) @mkdir($cacheDir, 0755, true);
    $cache = [];
    if (file_exists($cacheFile)) {
        $raw = @file_get_contents($cacheFile);
        if ($raw) $cache = json_decode($raw, true) ?: [];
    }
    $cache[$ogName] = [
        'at'      => date('Y-m-d H:i:s'),
        'src'     => basename($thumbnailUrl),
        'orig_kb' => (int)round(strlen($imgData) / 1024),
        'og_kb'   => (int)$ogKb,
        'size'    => '1200x630',
        'auto'    => true,
    ];
    @file_put_contents($cacheFile, json_encode($cache, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

    return [
        'success' => true,
        'og_url'  => $ogUrl,
        'og_name' => $ogName,
        'og_kb'   => $ogKb,
        'note'    => 'OG preview dibuat otomatis ke Cloudflare R2',
    ];
}

// ═══════════════════════════════════════════════════════════════════
// ACTION HANDLER
// ═══════════════════════════════════════════════════════════════════

if (!isset($action) || !$action) {
    apiJson(['error' => 'Action tidak boleh kosong'], 400);
}

try {
    $am     = new SupabaseArticleManager(getDB());
    $logger = getLogger();

    switch ($action) {

        // ── LIST ──────────────────────────────────────────────────────────
        case 'list':
            $all = $am->getAll($menu);
            $visible = array_values(array_filter($all, fn($a) =>
                canViewArtikel($currentUser, $menu, $a)
            ));
            $visible = array_map(function($art) use ($currentUser, $menu) {
                $art['_can_edit']    = canEditArtikel($currentUser, $menu, $art);
                $art['_can_delete']  = canDeleteArtikel($currentUser, $menu, $art);
                $art['_can_publish'] = canPublishArtikel($currentUser, $menu);
                $art['_is_own']      = isOwnArtikel($currentUser, $art);
                return $art;
            }, $visible);
            apiJson(['success' => true, 'data' => $visible]);
            break;

        // ── STATS ─────────────────────────────────────────────────────────
        case 'stats':
            $result = [];
            foreach (SupabaseArticleManager::MENUS as $m) {
                $permsMap = isSuperAdmin($currentUser) ? [] : getPermissionsMap($currentUser);
                if (isSuperAdmin($currentUser) || array_key_exists($m, $permsMap)) {
                    $allArts = $am->getAll($m);
                    if (!isSuperAdmin($currentUser) && !isEditor($currentUser, $m)) {
                        $allArts = array_filter($allArts, fn($a) => canViewArtikel($currentUser, $m, $a));
                    }
                    $allArts    = array_values($allArts);
                    $result[$m] = [
                        'total'     => count($allArts),
                        'published' => count(array_filter($allArts, fn($a) => ($a['status'] ?? '') === 'published')),
                        'draft'     => count(array_filter($allArts, fn($a) => ($a['status'] ?? '') !== 'published')),
                    ];
                }
            }
            apiJson(['success' => true, 'data' => $result]);
            break;

        // ── GET SINGLE ────────────────────────────────────────────────────
        case 'get':
            $id  = $body['id'] ?? '';
            $art = $am->getById($menu, $id);
            if (!$art) apiJson(['error' => 'Artikel tidak ditemukan'], 404);
            if (!canViewArtikel($currentUser, $menu, $art)) {
                apiJson(['error' => 'Anda tidak memiliki akses ke artikel ini'], 403);
            }
            $art['_can_edit']    = canEditArtikel($currentUser, $menu, $art);
            $art['_can_delete']  = canDeleteArtikel($currentUser, $menu, $art);
            $art['_can_publish'] = canPublishArtikel($currentUser, $menu);
            $art['_is_own']      = isOwnArtikel($currentUser, $art);
            apiJson(['success' => true, 'data' => $art]);
            break;

        // ── SAVE (CREATE / UPDATE) ─────────────────────────────────────────
        case 'save':
            $data         = $body['data'] ?? [];
            $data['menu'] = $menu;
            $isNew        = empty($data['id']);

            if (empty(trim($data['judul'] ?? ''))) {
                apiJson(['error' => 'Judul artikel wajib diisi'], 400);
            }

            if ($isNew) {
                $data['penulis'] = getDisplayName($currentUser);
                if (!canPublishArtikel($currentUser, $menu)) {
                    $data['status'] = 'draft';
                }
            } else {
                // Cari artikel lama — bisa jadi berasal dari menu berbeda (pindah rubrik)
                $existing = $am->getById($menu, $data['id']);
                if (!$existing) apiJson(['error' => 'Artikel tidak ditemukan'], 404);
                // Jika artikel ditemukan di menu lain, pastikan permission dari menu asalnya
                $originalMenu = $existing['menu'] ?? $menu;
                if (!canEditArtikel($currentUser, $originalMenu, $existing)) {
                    $owner = $existing['penulis'] ?? 'penulis lain';
                    apiJson([
                        'error' => "Anda tidak dapat mengedit artikel ini karena ditulis oleh {$owner}."
                    ], 403);
                }
                if (!canPublishArtikel($currentUser, $menu)) {
                    $data['status'] = $existing['status'] ?? 'draft';
                }
                $data['penulis'] = $existing['penulis'] ?? $currentUser['username'];
            }

            $saved = $am->save($data);
            $logger->log(
                $currentUser,
                $isNew ? 'CREATE' : 'UPDATE',
                $menu,
                ($isNew ? 'Artikel baru: ' : 'Update artikel: ') . ($data['judul'] ?? '')
            );

            // ── AUTO OG CONVERSION ─────────────────────────────────────────
            // Jalankan setelah artikel berhasil disimpan, jika ada thumbnail
            $ogResult = ['success' => false, 'note' => 'Tidak ada thumbnail'];
            $thumbnail = $data['thumbnail'] ?? $saved['thumbnail'] ?? '';
            if ($thumbnail) {
                $ogResult = autoConvertOG($thumbnail);
                if ($ogResult['success']) {
                    $logger->log(
                        $currentUser,
                        'CREATE',
                        'media',
                        'Auto OG: ' . ($ogResult['og_name'] ?? '') . ' dari artikel "' . ($data['judul'] ?? '') . '"'
                    );
                }
            }

            $saved['_can_edit']    = canEditArtikel($currentUser, $menu, $saved);
            $saved['_can_delete']  = canDeleteArtikel($currentUser, $menu, $saved);
            $saved['_can_publish'] = canPublishArtikel($currentUser, $menu);
            $saved['_is_own']      = isOwnArtikel($currentUser, $saved);
            $saved['_og']          = $ogResult; // info OG untuk frontend

            invalidateAllRelatedCaches($menu, 'articles');
            apiJson(['success' => true, 'data' => $saved]);
            break;

        // ── PUBLISH ───────────────────────────────────────────────────────
        case 'publish':
            if (!canPublishArtikel($currentUser, $menu)) {
                apiJson(['error' => 'Anda tidak memiliki izin untuk mempublish artikel.'], 403);
            }
            $id  = $body['id'] ?? '';
            $art = $am->getById($menu, $id);
            if (!$art) apiJson(['error' => 'Artikel tidak ditemukan'], 404);
            $am->publish($menu, $id);
            $logger->log($currentUser, 'UPDATE', $menu, 'Publish: ' . ($art['judul'] ?? ''));
            invalidateAllRelatedCaches($menu, 'articles');
            apiJson(['success' => true]);
            break;

        // ── UNPUBLISH ─────────────────────────────────────────────────────
        case 'unpublish':
            if (!canPublishArtikel($currentUser, $menu)) {
                apiJson(['error' => 'Anda tidak memiliki izin untuk mengubah status artikel.'], 403);
            }
            $id  = $body['id'] ?? '';
            $art = $am->getById($menu, $id);
            if (!$art) apiJson(['error' => 'Artikel tidak ditemukan'], 404);
            $am->unpublish($menu, $id);
            $logger->log($currentUser, 'UPDATE', $menu, 'Unpublish: ' . ($art['judul'] ?? ''));
            invalidateAllRelatedCaches($menu, 'articles');
            apiJson(['success' => true]);
            break;

        // ── DELETE ────────────────────────────────────────────────────────
        case 'delete':
            $id  = $body['id'] ?? '';
            $art = $am->getById($menu, $id);
            if (!$art) apiJson(['error' => 'Artikel tidak ditemukan'], 404);
            if (!canDeleteArtikel($currentUser, $menu, $art)) {
                $owner = $art['penulis'] ?? 'penulis lain';
                apiJson([
                    'error' => "Anda tidak dapat menghapus artikel ini karena ditulis oleh {$owner}."
                ], 403);
            }
            $am->delete($menu, $id);
            $logger->log($currentUser, 'DELETE', $menu, 'Hapus: ' . ($art['judul'] ?? ''));
            invalidateAllRelatedCaches($menu, 'articles');
            apiJson(['success' => true]);
            break;

        // ── TAGS: semua unique tag dari artikel ──────────────────────────
        case 'tags':
            $allArts = $am->getAll($menu);
            $tagMap  = [];
            foreach ($allArts as $art) {
                if (empty($art['tags'])) continue;
                $raw  = $art['tags'];
                $list = is_array($raw) ? $raw : (json_decode($raw, true) ?: array_map('trim', explode(',', $raw)));
                foreach ($list as $t) {
                    $t = trim((string)$t);
                    if ($t) $tagMap[strtolower($t)] = $t;
                }
            }
            $sorted = array_values($tagMap);
            sort($sorted, SORT_NATURAL | SORT_FLAG_CASE);
            apiJson(['tags' => $sorted]);
            break;

        default:
            apiJson(['error' => 'Action tidak dikenal: ' . $action], 400);
    }

} catch (Throwable $e) {
    error_log('[api/artikel.php] ' . $e->getMessage());
    apiJson(['error' => $e->getMessage()], 500);
}