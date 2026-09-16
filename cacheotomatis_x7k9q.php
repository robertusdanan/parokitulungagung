<?php
// cacheotomatis_x7k9q.php — Automated Cache Warmer Paroki SMDTBA (v2.0)
// ──────────────────────────────────────────────────────────────────
// Warming cache Cloudflare & server-side runtime cache untuk SEMUA
// halaman publik aktif (statis, sitemap, kategorial, romo, galeri album, artikel).
//
// Cron cPanel (tiap 6 jam) — JANGAN ditulis dalam /* */ block comment,
// pola "*/6" akan menutup comment lebih awal dan memicu PHP parse error:
//   0 */6 * * * php /home/ejtkecoh/public_html/cacheotomatis_x7k9q.php >> /home/ejtkecoh/logs/cache_warmer.log 2>&1
// ──────────────────────────────────────────────────────────────────

// Load sistem internal jika dijalankan via CLI / Web
if (file_exists(__DIR__ . '/includes/functions.php')) {
    require_once __DIR__ . '/includes/functions.php';
    if (function_exists('privatePath') && file_exists(privatePath('secrets.php'))) {
        @require_once privatePath('secrets.php');
    }
}

define('SITE_BASE', 'https://www.parokitulungagung.org');
define('REQUEST_TIMEOUT', 15);
define('REQUEST_DELAY', 250000); // 0.25 detik

function log_msg(string $msg): void {
    echo '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL;
}

function warm_url(string $url): bool {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => REQUEST_TIMEOUT,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 3,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_USERAGENT      => 'CacheWarmer/2.0 (parokitulungagung.org)',
        CURLOPT_HTTPHEADER     => [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language: id-ID,id;q=0.9,en-US;q=0.8',
        ],
    ]);
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error    = curl_error($ch);
    curl_close($ch);

    if ($error) {
        log_msg("  ERROR: $url -> $error");
        return false;
    }

    $ok = ($httpCode >= 200 && $httpCode < 400);
    log_msg(($ok ? '  OK' : '  GAGAL') . " [$httpCode] $url");
    return $ok;
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
$total   = 0;
$success = 0;

// 1. Halaman Utama Statis & Menu Navigasi
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

log_msg('--- [1/4] Halaman Utama & Menu Statis ---');
foreach ($staticPaths as $path) {
    $url = SITE_BASE . $path;
    if (warm_url($url)) $success++;
    $total++;
    usleep(REQUEST_DELAY);
}

// 2. Sub-Sitemap Dinamis (Kategorial, Artikel Berita/Kronik/Historia)
$subSitemaps = [
    'Kategorial'  => SITE_BASE . '/sitemap-kelompok.xml',
    'Berita'      => SITE_BASE . '/sitemap-berita.xml',
    'Kronik'      => SITE_BASE . '/sitemap-kronik.xml',
    'Historia'    => SITE_BASE . '/sitemap-historia.xml',
];

log_msg('--- [2/4] Halaman Dinamis dari Sitemap (Artikel & Kategorial) ---');
foreach ($subSitemaps as $label => $smUrl) {
    log_msg("--- Sitemap $label ---");
    $urls = parse_sitemap_urls($smUrl);
    if (empty($urls)) {
        log_msg("  (sitemap kosong/tidak terbaca)");
        continue;
    }
    foreach ($urls as $u) {
        if (warm_url($u)) $success++;
        $total++;
        usleep(REQUEST_DELAY);
    }
}

// 3. Album Galeri Foto (Fetch ID & Slug langsung dari Supabase/Cache)
log_msg('--- [3/4] Album Galeri Foto ---');
if (function_exists('fetchSupabaseCached')) {
    $albums = fetchSupabaseCached('galeri_foto', [], 'Tanggal.desc') ?? [];
    if (!empty($albums)) {
        log_msg('  Ditemukan ' . count($albums) . ' album galeri');
        foreach ($albums as $alb) {
            $albId   = $alb['id'] ?? 0;
            $albSlug = function_exists('slugify') ? slugify($alb['Judul'] ?? '') : 'album';
            if ($albId) {
                $albUrl = SITE_BASE . '/galeri/album/' . $albId . '/' . $albSlug;
                if (warm_url($albUrl)) $success++;
                $total++;
                usleep(REQUEST_DELAY);
            }
        }
    }
}

// 4. Detail Romo Paroki
log_msg('--- [4/4] Profil Romo Paroki ---');
if (function_exists('fetchSupabaseCached')) {
    $romos = fetchSupabaseCached('romo_paroki', [], 'tanggal_mulai.asc') ?? [];
    $processedSlugs = [];
    foreach ($romos as $r) {
        $slug = trim($r['slug'] ?? '');
        if ($slug && !isset($processedSlugs[$slug])) {
            $processedSlugs[$slug] = true;
            $romoUrl = SITE_BASE . '/romo/' . rawurlencode($slug);
            if (warm_url($romoUrl)) $success++;
            $total++;
            usleep(REQUEST_DELAY);
        }
    }
}

log_msg('=== Selesai: ' . $success . '/' . $total . ' URL berhasil di-warm ===');
