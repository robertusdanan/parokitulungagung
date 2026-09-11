<?php
/**
 * includes/GaleriCache.php
 * ─────────────────────────────────────────────────────────────────────────
 * Cache PERMANEN untuk daftar foto R2 per album galeri.
 *
 * Beda dengan cache_get()/cache_set() di includes/functions.php (TTL 15
 * menit lalu otomatis fetch ulang ke R2), cache di sini TIDAK PERNAH
 * kedaluwarsa sendiri — sekali dibuat, dipakai terus-menerus sampai ada
 * yang secara eksplisit memanggil galeriPhotoCacheInvalidate(), yaitu:
 *   - admin mengubah / menghapus baris album di halaman admin Galeri
 *     (lihat admin/api/sheets.php)
 *   - admin menekan tombol "Refresh Foto" manual di admin (untuk kasus
 *     foto ditambah/dihapus langsung di R2, di luar admin ini — misalnya
 *     lewat Google Takeout / rclone / dashboard Cloudflare)
 *
 * Tujuan: menghilangkan panggilan ListObjectsV2 ke Cloudflare R2 pada
 * hampir semua kunjungan halaman album (yang sebelumnya terjadi tiap
 * 15 menit per-album), sehingga:
 *   - request Class-B R2 jauh berkurang → lebih hemat biaya/kuota
 *   - halaman album jadi lebih cepat (tidak perlu round-trip ke R2)
 *
 * Struktur file: /cache/galeri/album_<id>.json
 *   {
 *     "album_id":  12,
 *     "folder":    "Rekoleksi Keluarga",
 *     "photos":    [ {key,size,lastModified,etag}, ... ],
 *     "count":     42,
 *     "cached_at": 1690000000
 *   }
 *
 * "folder" ikut disimpan sebagai jaring pengaman: kalau suatu saat kolom
 * Link/Judul album berubah tanpa cache-nya sempat diinvalidasi (mis. ada
 * jalur update lain di masa depan yang lupa memanggil invalidate), maka
 * pages/galeri-album.php akan mendeteksi folder tidak cocok dan otomatis
 * mengambil ulang dari R2 — cache lama tidak pernah menyesatkan.
 * ─────────────────────────────────────────────────────────────────────────
 */

if (!function_exists('galeriCacheDir')) {
    function galeriCacheDir(): string
    {
        $root = $_SERVER['DOCUMENT_ROOT'] ?? dirname(__DIR__);
        return rtrim($root, '/') . '/cache/galeri';
    }
}

if (!function_exists('galeriCacheFile')) {
    function galeriCacheFile(int $albumId): string
    {
        return galeriCacheDir() . '/album_' . $albumId . '.json';
    }
}

/**
 * Ambil cache foto 1 album. Tidak ada pengecekan TTL — kalau file ada,
 * langsung dianggap valid (validitasnya dijaga lewat invalidasi eksplisit,
 * bukan waktu).
 *
 * @return array{album_id:int,folder:string,photos:array,count:int,cached_at:int}|null
 */
if (!function_exists('galeriPhotoCacheGet')) {
    function galeriPhotoCacheGet(int $albumId): ?array
    {
        $file = galeriCacheFile($albumId);
        if (!is_file($file)) return null;

        $raw = @file_get_contents($file);
        if ($raw === false || $raw === '') return null;

        $data = json_decode($raw, true);
        if (!is_array($data) || !isset($data['photos']) || !is_array($data['photos'])) {
            return null;
        }
        return $data;
    }
}

/**
 * Simpan/replace cache foto 1 album — tulis atomik (tmp → rename) supaya
 * tidak pernah korup meski ada request bersamaan yang menulis di saat
 * yang sama.
 */
if (!function_exists('galeriPhotoCacheSet')) {
    function galeriPhotoCacheSet(int $albumId, string $folder, array $photos): bool
    {
        $dir = galeriCacheDir();
        if (!is_dir($dir) && !@mkdir($dir, 0755, true)) return false;

        $payload = [
            'album_id'  => $albumId,
            'folder'    => $folder,
            'photos'    => array_values($photos),
            'count'     => count($photos),
            'cached_at' => time(),
        ];

        $file = galeriCacheFile($albumId);
        $tmp  = $file . '.tmp.' . getmypid();
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false || @file_put_contents($tmp, $json, LOCK_EX) === false) {
            @unlink($tmp);
            return false;
        }
        return @rename($tmp, $file);
    }
}

/**
 * Hapus cache 1 album — dipanggil setiap admin mengubah/menghapus baris
 * album tersebut, supaya kunjungan berikutnya mengambil data terbaru.
 */
if (!function_exists('galeriPhotoCacheInvalidate')) {
    function galeriPhotoCacheInvalidate(int $albumId): bool
    {
        $file = galeriCacheFile($albumId);
        if (is_file($file)) return @unlink($file);
        return true;
    }
}

/**
 * Hapus cache album berdasarkan nama folder R2 — dipanggil saat callback
 * kompresi video selesai di GitHub Actions, supaya web publik langsung
 * mengambil file video terbaru dari R2 tanpa perlu simpan ulang manual di admin.
 */
if (!function_exists('galeriPhotoCacheInvalidateByFolder')) {
    function galeriPhotoCacheInvalidateByFolder(string $folder): int
    {
        $dir = galeriCacheDir();
        if (!is_dir($dir)) return 0;
        $folderNorm = trim($folder);
        if ($folderNorm === '') return 0;

        $deleted = 0;
        foreach (glob($dir . '/album_*.json') ?: [] as $f) {
            $raw = @file_get_contents($f);
            if ($raw === false) continue;
            $data = json_decode($raw, true);
            if (is_array($data) && isset($data['folder'])) {
                if (strcasecmp(trim((string)$data['folder']), $folderNorm) === 0) {
                    if (@unlink($f)) $deleted++;
                }
            }
        }
        return $deleted;
    }
}

/**
 * Hapus semua cache album sekaligus (flush total).
 * @return int Jumlah file yang berhasil dihapus.
 */
if (!function_exists('galeriPhotoCacheInvalidateAll')) {
    function galeriPhotoCacheInvalidateAll(): int
    {
        $dir = galeriCacheDir();
        if (!is_dir($dir)) return 0;
        $n = 0;
        foreach (glob($dir . '/album_*.json') ?: [] as $f) {
            if (@unlink($f)) $n++;
        }
        return $n;
    }
}

/**
 * Ringkasan semua cache album yang tersimpan — dipakai panel admin untuk
 * menampilkan status cache (jumlah album ter-cache, total foto, ukuran,
 * kapan terakhir di-cache).
 */
if (!function_exists('galeriPhotoCacheStatus')) {
    function galeriPhotoCacheStatus(): array
    {
        $dir = galeriCacheDir();
        if (!is_dir($dir)) {
            return ['count' => 0, 'total_photos' => 0, 'size_kb' => 0, 'albums' => []];
        }

        $albums      = [];
        $totalPhotos = 0;
        $totalSize   = 0;

        foreach (glob($dir . '/album_*.json') ?: [] as $f) {
            $raw  = @file_get_contents($f);
            $data = $raw ? json_decode($raw, true) : null;
            if (!is_array($data)) continue;

            $sz = @filesize($f) ?: 0;
            $totalSize   += $sz;
            $totalPhotos += (int)($data['count'] ?? 0);

            $albums[] = [
                'album_id'  => $data['album_id'] ?? null,
                'folder'    => $data['folder'] ?? '',
                'count'     => $data['count'] ?? 0,
                'cached_at' => $data['cached_at'] ?? 0,
                'size_kb'   => round($sz / 1024, 1),
            ];
        }

        usort($albums, fn($a, $b) => ($b['cached_at'] ?? 0) <=> ($a['cached_at'] ?? 0));

        return [
            'count'        => count($albums),
            'total_photos' => $totalPhotos,
            'size_kb'      => round($totalSize / 1024, 1),
            'albums'       => $albums,
        ];
    }
}
