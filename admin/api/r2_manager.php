<?php
/**
 * admin/api/r2_manager.php
 * Media Manager > Cloudflare R2 — list album, list file dalam album,
 * hapus file, hapus album (semua object di bawah prefix-nya).
 *
 * POST JSON: { action: 'list_album_names' | 'album_stats' | 'list_albums' | 'list_files' | 'delete_file' | 'delete_album', ... }
 *
 * 'list_album_names' + 'album_stats' dipakai bareng untuk LAZY LOAD daftar
 * album: 'list_album_names' cuma ambil nama folder (murah, pakai delimiter
 * listing, tidak meng-enumerasi isi tiap album) supaya total halaman bisa
 * langsung diketahui. 'album_stats' baru menghitung jumlah file & ukuran
 * — TAPI HANYA untuk nama-nama album yang diminta (yaitu yang sedang tampil
 * di halaman pagination aktif), bukan untuk semua album sekaligus.
 * 'list_albums' (lama, hitung semua sekaligus) tetap ada untuk kompatibilitas
 * tapi sudah tidak dipakai oleh halaman admin.
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
adminBoot();

$body   = jsonBody();
$action = $body['action'] ?? '';
$force  = !empty($body['force']); // bypass cache lokal — dipakai tombol "↻ Refresh" manual

$user = apiRequirePageAccess('media', $action === 'delete_file' || $action === 'delete_album' ? 'delete' : 'list');

require_once __DIR__ . '/../../includes/R2WriteClient.php';
require_once __DIR__ . '/../includes/R2AlbumCache.php';

if (!defined('SECRET_R2_ACCESS_KEY_WRITE') || !defined('SECRET_R2_SECRET_KEY_WRITE')
    || !defined('SECRET_R2_ENDPOINT') || !defined('SECRET_R2_BUCKET')) {
    apiJson(['error' => 'Kredensial R2 (write) belum diatur di private/secrets.php.'], 500);
}

$r2 = new R2WriteClient(SECRET_R2_ACCESS_KEY_WRITE, SECRET_R2_SECRET_KEY_WRITE, SECRET_R2_ENDPOINT, SECRET_R2_BUCKET);

$IMG_EXTS = ['jpg','jpeg','png','webp','gif','bmp','heic','heif','avif'];

function extOf(string $key): string {
    return strtolower(pathinfo($key, PATHINFO_EXTENSION));
}

switch ($action) {

    case 'list_album_names': {
        if (!$force) {
            $cached = r2AlbumCacheGetNames();
            if ($cached !== null) {
                apiJson(['success' => true, 'data' => $cached, 'cached' => true]);
            }
        }

        $names = $r2->listFolderNames(R2_ALBUM_PREFIX);
        sort($names, SORT_STRING | SORT_FLAG_CASE);
        r2AlbumCacheSetNames($names);
        apiJson(['success' => true, 'data' => $names, 'cached' => false]);
    }

    case 'album_stats': {
        $folders = $body['folders'] ?? [];
        if (!is_array($folders) || empty($folders)) {
            apiJson(['error' => 'Parameter folders wajib diisi (array nama album).'], 400);
        }
        if (count($folders) > 100) {
            apiJson(['error' => 'Maksimal 100 nama album per permintaan.'], 400);
        }

        $stats    = [];
        $needFetch = [];
        foreach ($folders as $folder) {
            $folder = trim((string)$folder);
            if ($folder === '') continue;

            if (!$force) {
                $cachedStat = r2AlbumCacheGetStats($folder);
                if ($cachedStat !== null) { $stats[] = $cachedStat; continue; }
            }
            $needFetch[] = $folder;
        }

        foreach ($needFetch as $folder) {
            $objects = $r2->listAllObjects(R2_ALBUM_PREFIX . $folder . '/');
            $count = 0; $bytes = 0; $lastMod = '';
            foreach ($objects as $o) {
                if (extOf($o['key']) !== 'json') $count++;
                $bytes += $o['size'];
                if ($o['lastModified'] > $lastMod) $lastMod = $o['lastModified'];
            }
            $s = ['name' => $folder, 'file_count' => $count, 'total_bytes' => $bytes, 'last_modified' => $lastMod];
            r2AlbumCacheSetStats($folder, $s);
            $stats[] = $s;
        }
        apiJson(['success' => true, 'data' => $stats]);
    }

    case 'list_albums': {
        // Hanya scan di bawah prefix album (R2_ALBUM_PREFIX), BUKAN seluruh
        // bucket — supaya folder lain di root (icon/, umkm/, artikel/, dst)
        // tidak ikut ter-enumerasi seolah-olah "album".
        $objects = $r2->listAllObjects(R2_ALBUM_PREFIX);
        $prefixLen = strlen(R2_ALBUM_PREFIX);
        $albums = [];
        foreach ($objects as $o) {
            $relKey = substr($o['key'], $prefixLen);
            $slashPos = strpos($relKey, '/');
            if ($slashPos === false) continue; // object langsung di bawah prefix, bukan bagian album
            $albumName = substr($relKey, 0, $slashPos);
            $ext = extOf($o['key']);
            if (!isset($albums[$albumName])) {
                $albums[$albumName] = ['name' => $albumName, 'file_count' => 0, 'total_bytes' => 0, 'last_modified' => ''];
            }
            $albums[$albumName]['total_bytes'] += $o['size'];
            if ($ext !== 'json') $albums[$albumName]['file_count']++; // sidecar metadata tidak dihitung sbg "file"
            if ($o['lastModified'] > $albums[$albumName]['last_modified']) {
                $albums[$albumName]['last_modified'] = $o['lastModified'];
            }
        }
        $list = array_values($albums);
        usort($list, fn($a, $b) => strcasecmp($a['name'], $b['name']));
        apiJson(['success' => true, 'data' => $list]);
    }

    case 'list_files': {
        $folder = trim((string)($body['folder'] ?? ''));
        if ($folder === '') apiJson(['error' => 'Parameter folder wajib diisi.'], 400);

        $objects = $r2->listAllObjects(R2_ALBUM_PREFIX . $folder . '/');
        $files = [];
        foreach ($objects as $o) {
            $ext = extOf($o['key']);
            if ($ext === 'json') continue; // sidecar metadata, tidak ditampilkan sbg file media
            $isImg = in_array($ext, $GLOBALS['IMG_EXTS'], true);
            $files[] = [
                'key'           => $o['key'],
                'name'          => basename($o['key']),
                'ext'           => $ext,
                'size_kb'       => round($o['size'] / 1024, 1),
                'last_modified' => $o['lastModified'],
                'is_image'      => $isImg,
                'url'           => $isImg ? $r2->getPresignedUrl($o['key'], 3600) : null,
            ];
        }
        usort($files, fn($a, $b) => strcasecmp($a['name'], $b['name']));
        apiJson(['success' => true, 'data' => $files]);
    }

    case 'delete_file': {
        $key = trim((string)($body['key'] ?? ''));
        if ($key === '') apiJson(['error' => 'Parameter key wajib diisi.'], 400);

        $ok = $r2->deleteObject($key);
        // Hapus juga sidecar JSON kalau ada (best-effort, tidak masalah kalau tidak ada)
        $r2->deleteObject($key . '.json');

        if ($ok) {
            // Nama album = segmen setelah prefix "galerifoto/" dan sebelum '/' berikutnya.
            $prefixLen = strlen(R2_ALBUM_PREFIX);
            if (str_starts_with($key, R2_ALBUM_PREFIX)) {
                $relKey = substr($key, $prefixLen);
                $slashPos = strpos($relKey, '/');
                if ($slashPos !== false) r2AlbumCacheInvalidateStats(substr($relKey, 0, $slashPos));
            }
            getLogger()->log($user, 'DELETE', 'media', "Hapus file R2: $key");
            apiJson(['success' => true]);
        }
        apiJson(['error' => 'Gagal menghapus file di R2.'], 500);
    }

    case 'delete_album': {
        $folder = trim((string)($body['folder'] ?? ''));
        if ($folder === '') apiJson(['error' => 'Parameter folder wajib diisi.'], 400);

        $objects = $r2->listAllObjects(R2_ALBUM_PREFIX . $folder . '/');
        $keys = array_map(fn($o) => $o['key'], $objects);
        if (empty($keys)) apiJson(['success' => true, 'deleted' => 0]);

        $result = $r2->deleteObjectsBatch($keys);
        r2AlbumCacheInvalidateStats($folder);
        r2AlbumCacheInvalidateNames(); // album hilang total -> daftar nama berubah
        getLogger()->log($user, 'DELETE', 'media', "Hapus album R2 '$folder' (" . count($result['deleted']) . " file)");

        apiJson([
            'success' => empty($result['failed']),
            'deleted' => count($result['deleted']),
            'failed'  => count($result['failed']),
        ]);
    }

    default:
        apiJson(['error' => 'Action tidak dikenal.'], 400);
}
