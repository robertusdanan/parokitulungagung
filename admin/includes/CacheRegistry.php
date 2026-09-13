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
                'label'       => 'Cache Data Utama Website',
                'description' => 'Menyimpan sementara data jadwal misa, pengumuman, pengurus, dan agenda paroki agar halaman terbuka instan tanpa menunggu database.',
                'dir'         => $root . '/cache/supabase',
                'patterns'    => ['*.json', '*.stale', '*.tmp.*'],
            ],
            'runtime' => [
                'label'       => 'Cache Tampilan Halaman',
                'description' => 'Menyimpan struktur tampilan halaman website agar pengunjung dapat langsung membaca konten tanpa waktu muat ulang.',
                'dir'         => $root . '/cache/runtime',
                'patterns'    => ['p_*.cache', '*.tmp.*'],
            ],
            'articles' => [
                'label'       => 'Cache Salinan Artikel & Berita',
                'description' => 'Salinan bacaan cepat untuk daftar artikel Berita Paroki, Kronik Kegiatan, dan Historia Gereja.',
                'dir'         => $root . '/cache/articles',
                'patterns'    => ['*.json', '*.cache'],
            ],
            'galeri' => [
                'label'       => 'Cache Album Galeri Foto',
                'description' => 'Daftar album dan judul foto kegiatan paroki dari penyimpanan Cloud (R2) agar tampilan galeri langsung muncul.',
                'dir'         => $root . '/cache/galeri',
                'patterns'    => ['album_*.json'],
            ],
            'r2albums' => [
                'label'       => 'Cache Daftar Album di Admin',
                'description' => 'Rincian jumlah file & ukuran album foto di Media Manager admin agar pengelolaan galeri terasa ringan.',
                'dir'         => $root . '/cache/r2albums',
                'patterns'    => ['*.json'],
                'recursive'   => true,
            ],
            'r2img' => [
                'label'       => 'Cache Gambar Kecil (Thumbnail Galeri)',
                'description' => 'Gambar berukuran kecil khusus untuk tampilan kisi-kisi galeri. Foto asli ukuran besar tetap aman tersimpan di Cloud (R2).',
                'dir'         => $root . '/cache/r2img',
                'patterns'    => ['*'],
                'recursive'   => true,
            ],
            'analytics' => [
                'label'       => 'Cache Laporan Pengunjung',
                'description' => 'Laporan grafik jumlah pengunjung website dari Google Analytics 4 yang tampil di Dashboard admin.',
                'dir'         => $root . '/cache/analytics',
                'patterns'    => ['*.json'],
            ],
            'imgseo' => [
                'label'       => 'Cache Deskripsi SEO Gambar (AI)',
                'description' => 'Hasil pembuatan otomatis teks alt-text dan SEO gambar oleh AI (Gemini/Groq) sebelum tersimpan permanen.',
                'dir'         => $root . '/cache/imgseo',
                'patterns'    => ['*.cache', '*.tmp.*'],
            ],
            'sitemap' => [
                'label'       => 'Cache Peta Website (Sitemap Google)',
                'description' => 'Berkas peta situs (Sitemap XML) yang dibaca oleh mesin pencari Google agar halaman website terindeks dengan baik.',
                'dir'         => $root . '/cache/sitemap',
                'patterns'    => ['*.xml'],
            ],
            'media' => [
                'label'       => 'Cache Status Media Manager',
                'description' => 'Catatan status pengoptimasian gambar dan pratinjau media sosial (OpenGraph) di Media Manager.',
                'dir'         => $root . '/cache/media',
                'patterns'    => ['*.json'],
            ],
            'stories' => [
                'label'       => 'Cache Cerita Umat (Stories)',
                'description' => 'Daftar foto dan video cerita singkat paroki agar dapat diputar langsung tanpa hambatan.',
                'dir'         => $root . '/cache/stories',
                'patterns'    => ['*.json'],
            ],
            'chatbot' => [
                'label'       => 'Cache Jawaban Chatbot AI',
                'description' => 'Penyimpanan sementara percakapan pada fitur Chatbot AI Paroki. (Pengaturan & kuota API tetap aman).',
                'dir'         => $root . '/chatbot/data/cache',
                'patterns'    => ['*.txt'],
            ],
            'tmp' => [
                'label'       => 'Cache Berkas Sementara (Temp)',
                'description' => 'Berkas sisa proses pengunggahan foto atau pembuatan file sementara di server.',
                'dir'         => $root . '/cache/tmp',
                'patterns'    => ['*'],
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
