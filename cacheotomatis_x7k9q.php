<?php
// cache_warmer.php
// Script untuk mengisi cache Cloudflare secara otomatis.
// Jalankan via Cron Job di cPanel setiap 6 jam:
//   0 */6 * * * php /home/[username]/public_html/cacheotomatis_x7k9q.php >> /home/[username]/logs/cache_warmer.log 2>&1

// -- Konfigurasi --
define('SITE_BASE', 'https://www.parokitulungagung.org');
define('SUPABASE_URL', 'https://rkzaathgygfjovrpdlqi.supabase.co');
define('SUPABASE_ANON_KEY', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InJremFhdGhneWdmam92cnBkbHFpIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NzM4NzU4ODksImV4cCI6MjA4OTQ1MTg4OX0.soAwJ97mp9HbMolaNH1I3kGzTTha1lOOH8XkLBY-aiE');

// Timeout per request (detik)
define('REQUEST_TIMEOUT', 30);

// Jeda antar request agar tidak overload server (mikrodetik)
define('REQUEST_DELAY', 500000); // 0.5 detik

// -- Logger --
function log_msg(string $msg): void {
    echo '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL;
}

// -- Fetch satu URL --
function warm_url(string $url): bool {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => REQUEST_TIMEOUT,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 3,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_USERAGENT      => 'CacheWarmer/1.0 (parokitulungagung.org)',
        CURLOPT_HTTPHEADER     => ['Accept: text/html'],
    ]);
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error    = curl_error($ch);
    curl_close($ch);

    if ($error) {
        log_msg("  ERROR: $url -> $error");
        return false;
    }

    $ok = $httpCode >= 200 && $httpCode < 400;
    log_msg(($ok ? '  OK' : '  GAGAL') . " [$httpCode] $url");
    return $ok;
}

// -- Ambil slug artikel terbaru dari Supabase --
function fetch_latest_articles(string $menu, int $limit = 10): array {
    $url = SUPABASE_URL . '/rest/v1/articles'
        . '?menu=eq.' . $menu
        . '&status=eq.published'
        . '&select=slug'
        . '&order=published_at.desc'
        . '&limit=' . $limit;

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_HTTPHEADER     => [
            'apikey: ' . SUPABASE_ANON_KEY,
            'Authorization: Bearer ' . SUPABASE_ANON_KEY,
            'Accept: application/json',
        ],
    ]);
    $json = curl_exec($ch);
    curl_close($ch);

    if (!$json) return [];
    $data = json_decode($json, true);
    return is_array($data) ? array_column($data, 'slug') : [];
}

// -- Daftar URL statis yang selalu di-warm --
$staticUrls = [
    '/',
    '/galeri',
    '/agenda',
    '/jadwal-misa',
    '/tentang',
    '/kontak',
    '/kategorial',
    '/profil-ai',
    '/profil-dpp',
    '/profil-lingkungan',
    '/artikel/berita',
    '/artikel/kronik',
    '/artikel/historia',
    '/e-ticket',
];

// -- Mulai warming --
log_msg('=== Cache Warmer Dimulai ===');
$total   = 0;
$success = 0;

// Warm halaman statis
log_msg('--- Halaman Statis ---');
foreach ($staticUrls as $path) {
    $ok = warm_url(SITE_BASE . $path);
    if ($ok) $success++;
    $total++;
    usleep(REQUEST_DELAY);
}

// Warm artikel terbaru (berita, kronik, historia)
foreach (['berita', 'kronik', 'historia'] as $menu) {
    log_msg("--- Artikel: $menu ---");
    $slugs = fetch_latest_articles($menu, 10);

    if (empty($slugs)) {
        log_msg("  (tidak ada artikel ditemukan untuk menu: $menu)");
        continue;
    }

    foreach ($slugs as $slug) {
        $ok = warm_url(SITE_BASE . '/artikel/' . $menu . '/' . $slug);
        if ($ok) $success++;
        $total++;
        usleep(REQUEST_DELAY);
    }
}

// -- Ringkasan --
log_msg('=== Selesai: ' . $success . '/' . $total . ' URL berhasil di-warm ===');