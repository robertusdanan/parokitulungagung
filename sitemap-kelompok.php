<?php
/**
 * sitemap-kelompok.php
 * Generate sitemap untuk semua halaman kelompok kategorial
 *
 * PERBAIKAN SEO/GSC:
 * - lastmod ISO 8601 (format 'c') — bukan hanya Y-m-d
 * - Hapus duplikasi /kategorial indeks (sudah ada di sitemap-static)
 * - Tambahkan omk, misdinar, pkkt, bia, liturgi, komsos ke builtInSlugs
 */

error_reporting(0);
ini_set('display_errors', 0);
ob_start();

require_once __DIR__ . '/includes/functions.php';

ob_clean();
header('Content-Type: application/xml; charset=utf-8');

$base = 'https://www.parokitulungagung.org';

$cacheDir  = __DIR__ . '/cache';
$cacheFile = $cacheDir . '/sitemap-kelompok.xml';
$cacheTime = 1800;

if (!is_dir($cacheDir)) mkdir($cacheDir, 0755, true);

if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTime) {
    readfile($cacheFile);
    exit;
}

// ── Slug built-in (lengkap, semua kelompok di website) ───────────────
$builtInSlugs = [
    'adorasi', 'pdkk', 'wanita-katolik', 'gim', 'legiomaria',
    'me', 'pk', 'rosariohidup', 'ktm', 'ssvmaria', 'ssvrosali',
    'omk', 'misdinar', 'pkkt', 'bia', 'liturgi', 'komsos',
    'perangkaibunga',
];

// ── Slug dari Supabase (dinamis, dari admin) ─────────────────────────
$dbSlugs = [];
$dbRows  = fetchSupabaseCached('kelompok_profil', [], 'slug.asc', 'slug');
if (is_array($dbRows)) {
    foreach ($dbRows as $r) {
        if (!empty($r['slug'])) $dbSlugs[] = $r['slug'];
    }
}

$allSlugs = array_values(array_unique(array_merge($builtInSlugs, $dbSlugs)));

// ISO 8601 lastmod untuk tiap slug — gunakan date modifikasi cache profil jika ada
$lastmodDefault = date('c');

$xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

foreach ($allSlugs as $slug) {
    $slug = preg_replace('/[^a-z0-9\-]/', '', $slug);
    if (!$slug) continue;

    // Cek apakah ada cache file profil untuk slug ini → ambil filemtime-nya sebagai lastmod
    $profCacheFile = $cacheDir . '/supabase/kelompok_profil_' . md5($slug) . '.json';
    $lastmod = file_exists($profCacheFile)
        ? date('c', filemtime($profCacheFile))
        : $lastmodDefault;

    $xml .= "  <url>\n";
    $xml .= "    <loc>{$base}/kategorial/{$slug}</loc>\n";
    $xml .= "    <lastmod>{$lastmod}</lastmod>\n";
    $xml .= "    <changefreq>monthly</changefreq>\n";
    $xml .= "    <priority>0.6</priority>\n";
    $xml .= "  </url>\n";
}

$xml .= '</urlset>';

file_put_contents($cacheFile, $xml);
ob_end_clean();
echo $xml;
