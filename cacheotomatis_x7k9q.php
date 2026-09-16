<?php
// cacheotomatis_x7k9q.php — Automated Cache Warmer Paroki SMDTBA (v2.0)
// ──────────────────────────────────────────────────────────────────
// Warming cache Cloudflare & server-side runtime cache untuk SEMUA
// halaman publik aktif.
//
// Cron cPanel (tiap 6 jam):
//   0 */6 * * * php /home/ejtkecoh/public_html/cacheotomatis_x7k9q.php >> /home/ejtkecoh/logs/cache_warmer.log 2>&1
// ──────────────────────────────────────────────────────────────────

@ini_set('max_execution_time', '600');
@set_time_limit(600);
@ini_set('memory_limit', '256M');

// Output text/plain agar bisa distreaming langsung jika diakses via browser
if (php_sapi_name() !== 'cli') {
    header('Content-Type: text/plain; charset=UTF-8');
    header('X-Accel-Buffering: no');
    if (function_exists('apache_setenv')) {
        @apache_setenv('no-gzip', '1');
    }
    @ini_set('output_buffering', 'off');
    @ini_set('zlib.output_compression', 'off');
}

// Load helper publik jika tersedia
if (file_exists(__DIR__ . '/includes/functions.php')) {
    require_once __DIR__ . '/includes/functions.php';
    if (function_exists('privatePath') && file_exists(privatePath('secrets.php'))) {
        @require_once privatePath('secrets.php');
    }
}

define('SITE_BASE', 'https://www.parokitulungagung.org');
define('CONCURRENCY', 5); // 5 request paralel agar cepat dan tidak kena timeout web server

function log_msg(string $msg): void {
    echo '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL;
    if (php_sapi_name() !== 'cli') {
        @ob_flush();
        @flush();
    }
}

/**
 * Fetch URLs secara paralel menggunakan curl_multi
 */
function warm_urls_parallel(array $urls): array {
    $results = ['success' => 0, 'total' => count($urls)];
    if (empty($urls)) return $results;

    $chunks = array_chunk($urls, CONCURRENCY);

    foreach ($chunks as $chunk) {
        $mh = curl_multi_init();
        $handles = [];

        foreach ($chunk as $url) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 12,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS      => 3,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_USERAGENT      => 'CacheWarmer/2.0 (parokitulungagung.org)',
                CURLOPT_HTTPHEADER     => [
                    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language: id-ID,id;q=0.9,en-US;q=0.8',
                ],
            ]);
            curl_multi_add_handle($mh, $ch);
            $handles[$url] = $ch;
        }

        $running = null;
        do {
            curl_multi_exec($mh, $running);
            curl_multi_select($mh);
        } while ($running > 0);

        foreach ($handles as $url => $ch) {
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error    = curl_error($ch);
            $ok       = ($httpCode >= 200 && $httpCode < 400);

            if ($error) {
                log_msg("  ERROR: $url -> $error");
            } else {
                log_msg(($ok ? '  OK' : '  GAGAL') . " [$httpCode] $url");
            }

            if ($ok) $results['success']++;
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }

        curl_multi_close($mh);
        usleep(100000); // 0.1s jeda antar batch
    }

    return $results;
}

function parse_sitemap_urls(string $sitemapUrl): array {
    $ch = curl_init($sitemapUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_USERAGENT      => 'CacheWarmer/2.0 SitemapReader',
    ]);
    $xml = curl_exec($ch);
    curl_close($ch);

    if (!$xml) return [];

    $urls = [];
    if (preg_match_all('#<loc>(https?://[^<]+)</loc>#i', $xml, $m)) {
        foreach ($m[1] as $u) {
            $urls[] = trim($u);
        }
    }
    return array_unique($urls);
}

log_msg('=== Cache Warmer Dimulai ===');
$grandTotal   = 0;
$grandSuccess = 0;

// 1. Kumpulkan semua Halaman Utama Statis
$staticPaths = [
    '/',
    '/jadwal-misa',
    '/agenda',
    '/galeri',
    '/stories',
    '/artikel/berita',
    '/artikel/kronik',
    '/artikel/historia',
    '/profil-ai',
    '/profil-dpp',
    '/profil-lingkungan',
    '/kategorial',
    '/umkmumat',
    '/e-lonceng',
    '/tvdigital',
    '/penulis',
    '/kontak',
    '/tentang',
    '/e-ticket/',
    '/komedi/',
    '/srm/',
    '/analisis',
    '/kebijakan-privasi',
    '/kebijakan-cookie',
    '/sitemap.xml',
    '/sitemap-static.xml',
];
$staticUrls = array_map(fn($p) => SITE_BASE . $p, $staticPaths);

log_msg('--- [1/4] Halaman Utama & Menu Statis (' . count($staticUrls) . ' URL) ---');
$res = warm_urls_parallel($staticUrls);
$grandSuccess += $res['success'];
$grandTotal   += $res['total'];

// 2. Kumpulkan Halaman dari Sub-Sitemap (Artikel & Kategorial)
$subSitemaps = [
    'Kategorial'  => SITE_BASE . '/sitemap-kelompok.xml',
    'Berita'      => SITE_BASE . '/sitemap-berita.xml',
    'Kronik'      => SITE_BASE . '/sitemap-kronik.xml',
    'Historia'    => SITE_BASE . '/sitemap-historia.xml',
];

log_msg('--- [2/4] Halaman Dinamis dari Sitemap ---');
$sitemapUrls = [];
foreach ($subSitemaps as $label => $smUrl) {
    $parsed = parse_sitemap_urls($smUrl);
    log_msg("  Sitemap $label: " . count($parsed) . " URL");
    $sitemapUrls = array_merge($sitemapUrls, $parsed);
}
$sitemapUrls = array_unique($sitemapUrls);
$res = warm_urls_parallel($sitemapUrls);
$grandSuccess += $res['success'];
$grandTotal   += $res['total'];

// 3. Kumpulkan Album Galeri Foto
log_msg('--- [3/4] Album Galeri Foto ---');
$galeriUrls = [];
if (function_exists('fetchSupabaseCached')) {
    $albums = fetchSupabaseCached('galeri_foto', [], 'Tanggal.desc') ?? [];
    foreach ($albums as $alb) {
        $albId   = $alb['id'] ?? 0;
        $albSlug = function_exists('slugify') ? slugify($alb['Judul'] ?? '') : 'album';
        if ($albId) {
            $galeriUrls[] = SITE_BASE . '/galeri/album/' . $albId . '/' . $albSlug;
        }
    }
}
log_msg('  Ditemukan ' . count($galeriUrls) . ' album galeri');
$res = warm_urls_parallel($galeriUrls);
$grandSuccess += $res['success'];
$grandTotal   += $res['total'];

// 4. Kumpulkan Profil Romo Paroki
log_msg('--- [4/4] Profil Romo Paroki ---');
$romoUrls = [];
if (function_exists('fetchSupabaseCached')) {
    $romos = fetchSupabaseCached('romo_paroki', [], 'tanggal_mulai.asc') ?? [];
    $processedSlugs = [];
    foreach ($romos as $r) {
        $slug = trim($r['slug'] ?? '');
        if ($slug && !isset($processedSlugs[$slug])) {
            $processedSlugs[$slug] = true;
            $romoUrls[] = SITE_BASE . '/romo/' . rawurlencode($slug);
        }
    }
}
log_msg('  Ditemukan ' . count($romoUrls) . ' profil romo');
$res = warm_urls_parallel($romoUrls);
$grandSuccess += $res['success'];
$grandTotal   += $res['total'];

log_msg('=== Selesai: ' . $grandSuccess . '/' . $grandTotal . ' URL berhasil di-warm ===');
