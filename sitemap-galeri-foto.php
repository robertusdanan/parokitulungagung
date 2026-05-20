<?php
/**
 * sitemap-galeri-foto.php
 * Sitemap khusus galeri foto — mendukung Google Image Search
 * URL: /sitemap-galeri-foto.xml (via .htaccess)
 *
 * SEO:
 * - image:image dengan loc, title, caption, license per album
 * - lastmod ISO 8601 per item
 * - priority dinamis berdasarkan usia foto
 * - cache 6 jam
 */

error_reporting(0);
ini_set('display_errors', 0);
ob_start();

require_once __DIR__ . '/includes/functions.php';

ob_clean();
header('Content-Type: application/xml; charset=utf-8');

$base      = 'https://www.parokitulungagung.org';
$cacheDir  = __DIR__ . '/cache';
$cacheFile = $cacheDir . '/sitemap-galeri-foto.xml';
$cacheTime = 21600; // 6 jam

if (!is_dir($cacheDir)) mkdir($cacheDir, 0755, true);

if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTime) {
    readfile($cacheFile);
    exit;
}

ob_start();

// Fetch data galeri dari Supabase (via cache lokal jika ada)
$data = fetchSupabaseCached('galeri_foto', [], 'Tanggal.desc');
if (!is_array($data)) $data = [];

function xml_s(string $s): string {
    return htmlspecialchars(strip_tags($s), ENT_QUOTES | ENT_XML1, 'UTF-8');
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
echo '        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";

// ── Halaman utama galeri ──────────────────────────────────────────────
$latestDate = !empty($data[0]['Tanggal']) ? date('c', strtotime($data[0]['Tanggal'])) : date('c');

echo "  <url>\n";
echo "    <loc>" . xml_s($base . '/galeri') . "</loc>\n";
echo "    <lastmod>{$latestDate}</lastmod>\n";
echo "    <changefreq>weekly</changefreq>\n";
echo "    <priority>0.8</priority>\n";

// Sertakan 10 foto terbaru sebagai image:image di halaman utama galeri
$topItems = array_slice($data, 0, 10);
foreach ($topItems as $item) {
    $gambar = $item['Gambar'] ?? '';
    if (!$gambar) continue;
    $imgUrl = str_starts_with($gambar, 'http')
        ? $gambar
        : $base . '/public/galeri/' . $gambar;
    $caption = mb_substr(strip_tags($item['Keterangan'] ?? $item['Judul'] ?? ''), 0, 100, 'UTF-8');
    echo "    <image:image>\n";
    echo "      <image:loc>" . xml_s($imgUrl) . "</image:loc>\n";
    if (!empty($item['Judul'])) {
        echo "      <image:title>" . xml_s($item['Judul']) . "</image:title>\n";
    }
    if ($caption) {
        echo "      <image:caption>" . xml_s($caption) . "</image:caption>\n";
    }
    echo "      <image:license>" . xml_s($base) . "</image:license>\n";
    echo "    </image:image>\n";
}

echo "  </url>\n";

// ── Satu <url> per album galeri ───────────────────────────────────────
// Setiap album punya halaman di /galeri#panel-{tahun} tapi tidak punya
// URL unik per item. Kita daftarkan tiap foto sebagai ImageObject
// yang dikaitkan ke URL galeri dengan fragment anchor per tahun.
// Google mengindeks image:image meski tanpa URL unik per foto.

$byYear = [];
foreach ($data as $item) {
    $tahun = substr($item['Tanggal'] ?? '', 0, 4);
    if ($tahun) $byYear[$tahun][] = $item;
}

krsort($byYear);

foreach ($byYear as $tahun => $items) {
    $yearUrl   = $base . '/galeri#panel-' . $tahun;
    $yearLatest = !empty($items[0]['Tanggal'])
        ? date('c', strtotime($items[0]['Tanggal']))
        : date('c');

    // priority & changefreq berdasarkan tahun
    $age       = (int) date('Y') - (int) $tahun;
    if ($age === 0) {
        $priority   = '0.8';
        $changefreq = 'weekly';
    } elseif ($age === 1) {
        $priority   = '0.7';
        $changefreq = 'monthly';
    } elseif ($age <= 3) {
        $priority   = '0.6';
        $changefreq = 'yearly';
    } else {
        $priority   = '0.5';
        $changefreq = 'yearly';
    }

    echo "  <url>\n";
    echo "    <loc>" . xml_s($base . '/galeri') . "</loc>\n";
    echo "    <lastmod>{$yearLatest}</lastmod>\n";
    echo "    <changefreq>{$changefreq}</changefreq>\n";
    echo "    <priority>{$priority}</priority>\n";

    foreach ($items as $item) {
        $gambar = $item['Gambar'] ?? '';
        if (!$gambar) continue;

        $imgUrl = str_starts_with($gambar, 'http')
            ? $gambar
            : $base . '/public/galeri/' . $gambar;

        $judul   = strip_tags($item['Judul'] ?? '');
        $ket     = strip_tags($item['Keterangan'] ?? '');
        $caption = mb_substr($ket ?: $judul, 0, 100, 'UTF-8');

        echo "    <image:image>\n";
        echo "      <image:loc>" . xml_s($imgUrl) . "</image:loc>\n";
        if ($judul) {
            echo "      <image:title>" . xml_s($judul . ' – Paroki SMDTBA Tulungagung') . "</image:title>\n";
        }
        if ($caption) {
            echo "      <image:caption>" . xml_s($caption) . "</image:caption>\n";
        }
        echo "      <image:license>" . xml_s($base) . "</image:license>\n";
        echo "    </image:image>\n";
    }

    echo "  </url>\n";
}

echo '</urlset>';

$xml = ob_get_contents();
file_put_contents($cacheFile, $xml);
ob_end_flush();
