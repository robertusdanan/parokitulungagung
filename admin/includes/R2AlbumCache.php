<?php
/**
 * admin/includes/R2AlbumCache.php
 * ─────────────────────────────────────────────────────────────────────────
 * Cache lokal (file JSON di server) untuk tampilan awal Media Manager >
 * Cloudflare R2: daftar NAMA album, dan STATISTIK per album (jumlah file +
 * total ukuran). Tujuannya supaya membuka panel R2 tidak perlu memanggil
 * R2 (listFolderNames / listAllObjects) tiap kali — cukup baca file lokal.
 *
 * ISI FOLDER (list_files, saat album dibuka) SENGAJA TIDAK memakai cache
 * ini — tetap live-query ke R2 seperti sebelumnya, supaya file yang baru
 * saja diupload/dihapus selalu akurat saat dilihat langsung.
 *
 * Cache ini PERMANEN (tidak ada TTL) — hanya jadi basi kalau:
 *   - Album baru diupload lewat panel ini      → lihat r2_folder_upload.php
 *     (invalidate stats album ybs, tambahkan nama album ke cache nama kalau
 *     baru)
 *   - File/album dihapus lewat panel ini       → lihat r2_manager.php
 *     (invalidate stats album ybs; invalidate daftar nama kalau album
 *     dihapus total)
 *   - Admin klik tombol "↻ Refresh"            → bypass cache (parameter
 *     'force'), dipakai kalau ada perubahan di luar panel ini (mis. lewat
 *     rclone / dashboard Cloudflare langsung)
 *
 * Struktur penyimpanan:
 *   cache/r2albums/names.json          — { names: [...], generated_at }
 *   cache/r2albums/stats/<md5(nama)>.json  — { name, file_count,
 *                                              total_bytes, last_modified,
 *                                              cached_at }
 * ─────────────────────────────────────────────────────────────────────────
 */

require_once __DIR__ . '/CacheRegistry.php'; // pakai cacheRootDir() yang sama

if (!function_exists('r2AlbumCacheDir')) {
    function r2AlbumCacheDir(): string
    {
        return cacheRootDir() . '/cache/r2albums';
    }
}

if (!function_exists('r2AlbumCacheStatsDir')) {
    function r2AlbumCacheStatsDir(): string
    {
        return r2AlbumCacheDir() . '/stats';
    }
}

if (!function_exists('r2AlbumCacheNamesFile')) {
    function r2AlbumCacheNamesFile(): string
    {
        return r2AlbumCacheDir() . '/names.json';
    }
}

if (!function_exists('r2AlbumCacheStatsFile')) {
    function r2AlbumCacheStatsFile(string $name): string
    {
        return r2AlbumCacheStatsDir() . '/' . md5($name) . '.json';
    }
}

/** Baca+decode JSON dari file, return null kalau tidak ada / rusak. */
if (!function_exists('r2AlbumCacheReadJson')) {
    function r2AlbumCacheReadJson(string $path): ?array
    {
        if (!is_file($path)) return null;
        $raw = @file_get_contents($path);
        if ($raw === false || $raw === '') return null;
        $data = json_decode($raw, true);
        return is_array($data) ? $data : null;
    }
}

/** Tulis JSON ke file secara atomik (tulis ke file temp lalu rename). */
if (!function_exists('r2AlbumCacheWriteJson')) {
    function r2AlbumCacheWriteJson(string $path, array $data): void
    {
        $dir = dirname($path);
        if (!is_dir($dir)) @mkdir($dir, 0775, true);
        $tmp = $path . '.tmp.' . uniqid();
        if (@file_put_contents($tmp, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) !== false) {
            @rename($tmp, $path);
        } else {
            @unlink($tmp);
        }
    }
}

// ── Daftar nama album ───────────────────────────────────────────────────

if (!function_exists('r2AlbumCacheGetNames')) {
    /** Return array nama album, atau null kalau belum ada cache. */
    function r2AlbumCacheGetNames(): ?array
    {
        $data = r2AlbumCacheReadJson(r2AlbumCacheNamesFile());
        if ($data === null || !isset($data['names']) || !is_array($data['names'])) return null;
        return $data['names'];
    }
}

if (!function_exists('r2AlbumCacheSetNames')) {
    function r2AlbumCacheSetNames(array $names): void
    {
        r2AlbumCacheWriteJson(r2AlbumCacheNamesFile(), [
            'names'        => array_values($names),
            'generated_at' => time(),
        ]);
    }
}

if (!function_exists('r2AlbumCacheInvalidateNames')) {
    function r2AlbumCacheInvalidateNames(): void
    {
        @unlink(r2AlbumCacheNamesFile());
    }
}

/**
 * Tambahkan 1 nama album ke cache nama TANPA fetch ulang ke R2 — dipakai
 * saat upload folder baru selesai. Hanya berlaku kalau cache nama sudah
 * ada; kalau belum ada (belum pernah dibuka), dibiarkan saja, nanti
 * dibangun otomatis lengkap saat panel dibuka pertama kali.
 */
if (!function_exists('r2AlbumCacheAddNameIfMissing')) {
    function r2AlbumCacheAddNameIfMissing(string $name): void
    {
        $file = r2AlbumCacheNamesFile();
        $data = r2AlbumCacheReadJson($file);
        if ($data === null || !isset($data['names']) || !is_array($data['names'])) return; // belum ada cache, skip

        if (!in_array($name, $data['names'], true)) {
            $data['names'][] = $name;
            sort($data['names'], SORT_STRING | SORT_FLAG_CASE);
            r2AlbumCacheWriteJson($file, $data);
        }
    }
}

// ── Statistik per album ─────────────────────────────────────────────────

if (!function_exists('r2AlbumCacheGetStats')) {
    /** Return stats 1 album (name, file_count, total_bytes, last_modified), atau null kalau belum ada cache. */
    function r2AlbumCacheGetStats(string $name): ?array
    {
        $data = r2AlbumCacheReadJson(r2AlbumCacheStatsFile($name));
        if ($data === null || !isset($data['name'])) return null;
        unset($data['cached_at']);
        return $data;
    }
}

if (!function_exists('r2AlbumCacheSetStats')) {
    function r2AlbumCacheSetStats(string $name, array $stats): void
    {
        $stats['cached_at'] = time();
        r2AlbumCacheWriteJson(r2AlbumCacheStatsFile($name), $stats);
    }
}

if (!function_exists('r2AlbumCacheInvalidateStats')) {
    function r2AlbumCacheInvalidateStats(string $name): void
    {
        @unlink(r2AlbumCacheStatsFile($name));
    }
}

// ── Flush total (dipakai halaman Sistem > Cache lewat CacheRegistry) ────

if (!function_exists('r2AlbumCacheFlushAll')) {
    function r2AlbumCacheFlushAll(): int
    {
        $n = 0;
        if (is_file(r2AlbumCacheNamesFile()) && @unlink(r2AlbumCacheNamesFile())) $n++;
        $statsDir = r2AlbumCacheStatsDir();
        if (is_dir($statsDir)) {
            foreach (glob($statsDir . '/*.json') ?: [] as $f) {
                if (@unlink($f)) $n++;
            }
        }
        return $n;
    }
}
