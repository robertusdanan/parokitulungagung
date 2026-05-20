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

    // Normalisasi ke path relatif
    $thumbPath = preg_replace('#^https?://[^/]+#', '', $thumbnailUrl);
    $thumbPath = '/' . ltrim($thumbPath, '/');

    $srcFull = $root . $thumbPath;
    if (!file_exists($srcFull)) {
        return ['success' => false, 'note' => 'File thumbnail tidak ditemukan: ' . $thumbPath];
    }

    // GD harus tersedia
    if (!function_exists('imagecreatefromjpeg')) {
        return ['success' => false, 'note' => 'GD Library tidak tersedia'];
    }

    // Nama & path OG — TANPA prefix "og-", cukup ganti ekstensi ke .jpg
    $thumbBase  = pathinfo(basename($thumbPath), PATHINFO_FILENAME);
    $ogName     = $thumbBase . '.jpg';
    $ogDir      = $root . '/img/ogpreview';
    $ogFull     = $ogDir . '/' . $ogName;
    $ogRelUrl   = '/img/ogpreview/' . $ogName;

    // Buat folder jika belum ada
    if (!is_dir($ogDir)) @mkdir($ogDir, 0755, true);

    // Load gambar sumber
    $mime = @mime_content_type($srcFull) ?: '';
    $im   = null;
    if (str_contains($mime, 'jpeg') || str_contains($mime, 'jpg')) $im = @imagecreatefromjpeg($srcFull);
    elseif (str_contains($mime, 'png'))  $im = @imagecreatefrompng($srcFull);
    elseif (str_contains($mime, 'webp')) $im = @imagecreatefromwebp($srcFull);
    elseif (str_contains($mime, 'gif'))  $im = @imagecreatefromgif($srcFull);

    if (!$im) return ['success' => false, 'note' => 'GD gagal membaca thumbnail'];

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

    $tmpFull = $ogFull . '.tmp';
    $ok      = imagejpeg($out, $tmpFull, 85);
    imagedestroy($out);

    if (!$ok || !file_exists($tmpFull)) {
        @unlink($tmpFull);
        return ['success' => false, 'note' => 'Gagal menyimpan file OG'];
    }

    // Ganti file OG lama jika ada
    if (file_exists($ogFull)) @unlink($ogFull);
    rename($tmpFull, $ogFull);

    // Update cache OG
    $cacheFile = $root . '/cache/media_og.json';
    $cacheDir  = dirname($cacheFile);
    if (!is_dir($cacheDir)) @mkdir($cacheDir, 0755, true);
    $cache = [];
    if (file_exists($cacheFile)) {
        $raw = @file_get_contents($cacheFile);
        if ($raw) $cache = json_decode($raw, true) ?: [];
    }
    $cache[$ogName] = [
        'at'      => date('Y-m-d H:i:s'),
        'src'     => basename($thumbPath),
        'orig_kb' => (int)round(filesize($srcFull) / 1024),
        'og_kb'   => (int)round(filesize($ogFull) / 1024),
        'size'    => '1200x630',
        'auto'    => true,
    ];
    @file_put_contents($cacheFile, json_encode($cache, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

    return [
        'success' => true,
        'og_url'  => 'https://www.parokitulungagung.org' . $ogRelUrl,
        'og_name' => $ogName,
        'og_kb'   => round(filesize($ogFull) / 1024, 1),
        'note'    => 'OG preview dibuat otomatis',
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