<?php
/**
 * admin/api/galeri_cache.php
 * ─────────────────────────────────────────────────────────────────────────
 * Kelola cache PERMANEN daftar foto R2 per album (lihat includes/GaleriCache.php).
 * Dipanggil dari admin/pages/galeri.php.
 *
 * Actions (POST, JSON body):
 *  - status     : ringkasan semua cache album yang tersimpan saat ini
 *  - refresh    : hapus cache 1 album lalu ambil ulang dari R2 sekarang juga.
 *                 Dipakai saat foto ditambah/dihapus langsung di R2 (mis.
 *                 lewat rclone/Cloudflare dashboard/Google Takeout), yang
 *                 tidak lewat form admin ini sehingga tidak ter-invalidate
 *                 otomatis.
 *  - flush_all  : hapus semua cache album sekaligus (superadmin only)
 * ─────────────────────────────────────────────────────────────────────────
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
adminBoot();
require_once __DIR__ . '/../../includes/GaleriCache.php';
require_once __DIR__ . '/../../includes/R2Client.php';

header('Content-Type: application/json; charset=utf-8');

$body   = jsonBody();
$action = $body['action'] ?? '';

$currentUser = apiRequirePageAccess('galeri', $action === 'status' ? 'view' : 'edit');

// Alias kredensial R2 — root includes/config.php (situs publik) tidak
// otomatis dimuat di admin, tapi private/secrets.php sudah dimuat lewat
// admin/includes/config.php, jadi konstanta SECRET_R2_* sudah tersedia.
if (!defined('R2_ACCESS_KEY') && defined('SECRET_R2_ACCESS_KEY')) {
    define('R2_ACCESS_KEY', SECRET_R2_ACCESS_KEY);
    define('R2_SECRET_KEY', SECRET_R2_SECRET_KEY);
    define('R2_ENDPOINT',   SECRET_R2_ENDPOINT);
    define('R2_BUCKET',     SECRET_R2_BUCKET);
}

switch ($action) {

    // ── STATUS ───────────────────────────────────────────────────────
    case 'status':
        apiJson(['success' => true] + galeriPhotoCacheStatus());
        break;

    // ── REFRESH 1 ALBUM ─────────────────────────────────────────────
    case 'refresh':
        $albumId = (int)($body['id'] ?? 0);
        if (!$albumId) apiJson(['error' => 'ID album tidak valid'], 400);

        $r2Configured = defined('R2_ACCESS_KEY') && defined('R2_SECRET_KEY') && defined('R2_ENDPOINT') && defined('R2_BUCKET');
        if (!$r2Configured) apiJson(['error' => 'Cloudflare R2 belum dikonfigurasi di server'], 500);

        $db   = getDB();
        $rows = $db->read(TABLE_GALERI, ['id' => $albumId]);
        $row  = $rows[0] ?? null;
        if (!$row) apiJson(['error' => 'Album tidak ditemukan'], 404);

        // Sama persis dengan logika penentuan folder di pages/galeri-album.php
        $folderPrefix = trim((string)($row['Link'] ?? ''));
        if ($folderPrefix === '' || str_starts_with($folderPrefix, 'http://') || str_starts_with($folderPrefix, 'https://')) {
            $folderPrefix = trim((string)($row['Judul'] ?? ''));
        }
        if ($folderPrefix === '') {
            apiJson(['error' => 'Album ini belum punya nama folder (kolom Link/Judul kosong)'], 400);
        }

        try {
            $r2   = new R2Client(R2_ACCESS_KEY, R2_SECRET_KEY, R2_ENDPOINT, R2_BUCKET);
            $objs = $r2->listObjects(R2_ALBUM_PREFIX . $folderPrefix);
            $objs = array_values(array_filter($objs, function ($o) {
                $ext = strtolower(pathinfo($o['key'], PATHINFO_EXTENSION));
                return in_array($ext, ['webp', 'jpg', 'jpeg', 'png', 'gif'], true);
            }));
            usort($objs, fn($a, $b) => strnatcasecmp($a['key'], $b['key']));
        } catch (Throwable $e) {
            error_log('[galeri_cache refresh] ' . $e->getMessage());
            apiJson(['error' => 'Gagal mengambil daftar foto dari R2: ' . $e->getMessage()], 500);
        }

        galeriPhotoCacheSet($albumId, $folderPrefix, $objs);
        getLogger()->log(
            $currentUser, 'CACHE_REFRESH', 'galeri',
            "Refresh cache foto album #$albumId ($folderPrefix): " . count($objs) . ' foto'
        );

        apiJson(['success' => true, 'folder' => $folderPrefix, 'count' => count($objs)]);
        break;

    // ── FLUSH SEMUA ──────────────────────────────────────────────────
    case 'flush_all':
        if ($currentUser['role'] !== ROLE_SUPERADMIN) {
            apiJson(['error' => 'Hanya superadmin yang dapat menghapus semua cache galeri'], 403);
        }
        $n = galeriPhotoCacheInvalidateAll();
        getLogger()->log($currentUser, 'CACHE_FLUSH', 'galeri', "Flush semua cache foto galeri ($n album)");
        apiJson(['success' => true, 'deleted' => $n]);
        break;

    default:
        apiJson(['error' => 'Action tidak dikenal: ' . $action], 400);
}