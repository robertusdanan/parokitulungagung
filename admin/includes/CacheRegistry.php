<?php
/**
 * admin/includes/CacheRegistry.php
 * ─────────────────────────────────────────────────────────────────────────
 * Sumber tunggal (single source of truth) untuk semua cache di situs ini —
 * baik yang ditulis dari halaman publik (website) maupun dari panel admin.
 *
 * Sebelum file ini ada, cache tersebar & tidak konsisten:
 *   - /cache/supabase, /cache/runtime, /cache/galeri (sudah rapi per-folder)
 *   - /GA4/cache/analytics       (folder terpisah, di luar /cache)
 *   - sys_get_temp_dir()/paroki_*.cache (cache SEO gambar, di luar /cache
 *     sama sekali — hilang tiap server di-restart / temp dibersihkan)
 *   - /cache/sitemap-*.xml, /cache/media_og.json, /cache/media_compressed.json
 *     (file lepas langsung di root /cache, tercampur dengan folder cache lain)
 *
 * Semua itu sekarang dikelompokkan rapi di bawah /cache/<nama-grup>/, dan
 * didaftarkan di sini supaya:
 *   - Halaman admin (Sistem → Cache) bisa menampilkan status & tombol
 *     "Bersihkan" untuk tiap grup tanpa perlu tahu detail masing-masing.
 *   - Menambah cache baru di masa depan cukup daftarkan di getCacheGroups(),
 *     tidak perlu ubah halaman admin atau API-nya.
 *
 * CATATAN: cache chatbot sengaja HANYA mencakup file .txt (konteks jawaban).
 * File rl_*.json (rate-limit) dan gemini_key_idx.json (indeks rotasi API
 * key) adalah STATE operasional, bukan cache, sehingga tidak pernah ikut
 * dihapus lewat halaman ini.
 * ─────────────────────────────────────────────────────────────────────────
 */

if (!function_exists('cacheRootDir')) {
    function cacheRootDir(): string
    {
        return rtrim($_SERVER['DOCUMENT_ROOT'] ?? dirname(__DIR__, 2), '/');
    }
}

/**
 * Daftar semua grup cache di situs ini.
 * 'dir'      : folder absolut tempat file cache disimpan.
 * 'patterns' : daftar glob pattern (relatif ke 'dir') yang dianggap file cache.
 */
if (!function_exists('getCacheGroups')) {
    function getCacheGroups(): array
    {
        $root = cacheRootDir();

        return [
            'supabase' => [
                'label'       => 'Cache Query Supabase',
                'description' => 'Hasil query Supabase (galeri, petugas, wilayah, agenda, dll) untuk halaman publik. Kedaluwarsa otomatis ±5 menit, dipakai fetchSupabaseCached().',
                'dir'         => $root . '/cache/supabase',
                'patterns'    => ['*.json', '*.stale', '*.tmp.*'],
            ],
            'runtime' => [
                'label'       => 'Cache Runtime Halaman',
                'description' => 'Cache ringan serbaguna (artikel, homepage, dll) lewat cache_get()/cache_set().',
                'dir'         => $root . '/cache/runtime',
                'patterns'    => ['p_*.cache', '*.tmp.*'],
            ],
            'galeri' => [
                'label'       => 'Cache Foto Galeri (R2)',
                'description' => 'Daftar foto per album galeri dari Cloudflare R2 — permanen sampai admin refresh manual atau album diubah.',
                'dir'         => $root . '/cache/galeri',
                'patterns'    => ['album_*.json'],
            ],
            'r2albums' => [
                'label'       => 'Cache Daftar Album R2 (Media Manager)',
                'description' => 'Nama album & statistik (jumlah file, total ukuran) Cloudflare R2 di Media Manager — permanen sampai upload/hapus lewat panel ini atau admin klik "↻ Refresh".',
                'dir'         => $root . '/cache/r2albums',
                'patterns'    => ['*.json'],
                'recursive'   => true,
            ],
            'r2img' => [
                'label'       => 'Cache Thumbnail Galeri (R2)',
                'description' => 'HANYA thumbnail WebP kecil per foto (dikelompokkan per album) — untuk grid galeri supaya cepat dimuat. Foto ukuran ASLI tidak pernah disimpan di hosting; browser diarahkan mengambil langsung dari R2 (presigned URL) saat lightbox/zoom dibuka.',
                'dir'         => $root . '/cache/r2img',
                'patterns'    => ['*'],
                'recursive'   => true,
            ],
            'analytics' => [
                'label'       => 'Cache Analytics GA4',
                'description' => 'Hasil laporan Google Analytics 4 di dashboard admin. TTL 30 menit (laporan) / 30 detik (realtime).',
                'dir'         => $root . '/cache/analytics',
                'patterns'    => ['*.json'],
            ],
            'imgseo' => [
                'label'       => 'Cache SEO Gambar (AI)',
                'description' => 'Hasil generate alt-text/SEO gambar via Gemini/Groq, sebelum tersimpan permanen ke Supabase. TTL 1 hari.',
                'dir'         => $root . '/cache/imgseo',
                'patterns'    => ['*.cache', '*.tmp.*'],
            ],
            'sitemap' => [
                'label'       => 'Cache Sitemap',
                'description' => 'Sitemap XML yang di-generate berkala (index, statis, artikel, galeri, kelompok).',
                'dir'         => $root . '/cache/sitemap',
                'patterns'    => ['*.xml'],
            ],
            'media' => [
                'label'       => 'Cache Media Manager',
                'description' => 'Status kompresi gambar & metadata OG image dari Media Manager.',
                'dir'         => $root . '/cache/media',
                'patterns'    => ['*.json'],
            ],
            'stories' => [
                'label'       => 'Cache Stories',
                'description' => 'Metadata media Stories (foto & video) sinkronisasi R2.',
                'dir'         => $root . '/cache/stories',
                'patterns'    => ['*.json'],
            ],
            'chatbot' => [
                'label'       => 'Cache Chatbot',
                'description' => 'Cache konteks jawaban chatbot AI. Data rate-limit & rotasi API key tidak ikut dihapus (bukan cache).',
                'dir'         => $root . '/chatbot/data/cache',
                'patterns'    => ['*.txt'],
            ],
        ];
    }
}

/** Ambil semua file yang cocok dengan pattern sebuah grup (tanpa duplikat). */
if (!function_exists('cacheGroupMatchedFiles')) {
    function cacheGroupMatchedFiles(array $group): array
    {
        $dir = $group['dir'];
        if (!is_dir($dir)) return [];

        // Grup dengan struktur folder bersarang (mis. r2img: per-album)
        // perlu di-scan rekursif — pattern glob biasa tidak menjangkau subfolder.
        if (!empty($group['recursive'])) {
            $files = [];
            $it = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
            );
            foreach ($it as $f) {
                if ($f->isFile()) $files[] = $f->getPathname();
            }
            return $files;
        }

        $files = [];
        foreach ($group['patterns'] as $pattern) {
            foreach (glob($dir . '/' . $pattern, GLOB_NOSORT) ?: [] as $f) {
                if (is_file($f)) $files[$f] = true;
            }
        }
        return array_keys($files);
    }
}

/** Hapus folder kosong sisa setelah flush (khusus grup rekursif seperti r2img). */
if (!function_exists('cacheGroupPruneEmptyDirs')) {
    function cacheGroupPruneEmptyDirs(string $dir): void
    {
        if (!is_dir($dir)) return;
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($it as $f) {
            if ($f->isDir() && count(scandir($f->getPathname())) === 2) {
                @rmdir($f->getPathname());
            }
        }
    }
}

/** Status 1 grup cache: jumlah file, total ukuran, file terlama/terbaru. */
if (!function_exists('cacheGroupStatus')) {
    function cacheGroupStatus(string $key): array
    {
        $groups = getCacheGroups();
        if (!isset($groups[$key])) {
            return ['count' => 0, 'size_kb' => 0, 'oldest' => null, 'newest' => null, 'exists' => false];
        }

        $files  = cacheGroupMatchedFiles($groups[$key]);
        $size   = 0;
        $oldest = null;
        $newest = null;

        foreach ($files as $f) {
            $size += @filesize($f) ?: 0;
            $mt    = @filemtime($f);
            if ($mt) {
                if ($oldest === null || $mt < $oldest) $oldest = $mt;
                if ($newest === null || $mt > $newest) $newest = $mt;
            }
        }

        return [
            'count'    => count($files),
            'size_kb'  => round($size / 1024, 1),
            'oldest'   => $oldest,
            'newest'   => $newest,
            'exists'   => is_dir($groups[$key]['dir']),
        ];
    }
}

/** Status semua grup cache sekaligus — dipakai halaman admin Cache & Dashboard. */
if (!function_exists('getAllCacheGroupsStatus')) {
    function getAllCacheGroupsStatus(): array
    {
        $out = [];
        foreach (getCacheGroups() as $key => $group) {
            $out[$key] = array_merge(
                ['key' => $key, 'label' => $group['label'], 'description' => $group['description']],
                cacheGroupStatus($key)
            );
        }
        return $out;
    }
}

/** Hapus semua file 1 grup cache. Return jumlah file yang berhasil dihapus. */
if (!function_exists('cacheGroupFlush')) {
    function cacheGroupFlush(string $key): int
    {
        $groups = getCacheGroups();
        if (!isset($groups[$key])) return 0;

        $n = 0;
        foreach (cacheGroupMatchedFiles($groups[$key]) as $f) {
            if (@unlink($f)) $n++;
        }

        // Grup rekursif (per-album/full|thumb) — bersihkan folder kosong sisa
        // supaya struktur folder tidak menumpuk tanpa isi.
        if (!empty($groups[$key]['recursive'])) {
            cacheGroupPruneEmptyDirs($groups[$key]['dir']);
        }

        return $n;
    }
}

/** Hapus semua grup cache sekaligus. Return array [key => jumlah file terhapus]. */
if (!function_exists('cacheFlushAll')) {
    function cacheFlushAll(): array
    {
        $result = [];
        foreach (array_keys(getCacheGroups()) as $key) {
            $result[$key] = cacheGroupFlush($key);
        }
        // Purge seluruh Cloudflare Edge Cache jika token terpasang
        if (function_exists('purgeCloudflareCache')) {
            purgeCloudflareCache([], true);
        }
        return $result;
    }
}

/** Ringkasan total (dipakai kartu ringkas di Dashboard). */
if (!function_exists('getCacheOverallSummary')) {
    function getCacheOverallSummary(): array
    {
        $count = 0;
        $sizeKb = 0.0;
        foreach (getAllCacheGroupsStatus() as $g) {
            $count  += $g['count'];
            $sizeKb += $g['size_kb'];
        }
        return ['count' => $count, 'size_kb' => round($sizeKb, 1)];
    }
}
