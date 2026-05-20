<?php
/**
 * sitemap.php → /sitemap.xml (via .htaccess)
 * Sitemap Index — titik masuk utama untuk GSC
 *
 * PERBAIKAN SEO/GSC:
 * - lastmod sub-sitemap menggunakan ISO 8601 (format 'c') bukan Y-m-d
 * - Tambahkan sitemap gambar (/sitemap-galeri.xml) untuk Google Image Search
 * - Cache 30 menit
 */

header('Content-Type: application/xml; charset=utf-8');

$base = 'https://www.parokitulungagung.org';
$now  = date('c');

$cacheFile = __DIR__ . '/cache/sitemap-index.xml';
$cacheTime = 1800;

if (!is_dir(__DIR__ . '/cache')) mkdir(__DIR__ . '/cache', 0755, true);

if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTime) {
    readfile($cacheFile);
    exit;
}

ob_start();

$maps = [
    '/sitemap-static.xml',
    '/sitemap-berita.xml',
    '/sitemap-kronik.xml',
    '/sitemap-historia.xml',
    '/sitemap-kelompok.xml',
    '/sitemap-galeri-foto.xml',
];

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

foreach ($maps as $map) {
    $cacheXml = __DIR__ . '/cache' . $map;
    // ISO 8601 dengan timezone untuk presisi GSC
    $lastmod = file_exists($cacheXml)
        ? date('c', filemtime($cacheXml))
        : $now;
    echo "  <sitemap>\n";
    echo "    <loc>{$base}{$map}</loc>\n";
    echo "    <lastmod>{$lastmod}</lastmod>\n";
    echo "  </sitemap>\n";
}

echo '</sitemapindex>';

file_put_contents($cacheFile, ob_get_contents());
ob_end_flush();
