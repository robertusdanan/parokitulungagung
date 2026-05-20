<?php
/**
 * sitemap-article.php
 * Generate sitemap artikel per kategori (menu)
 * URL: /sitemap-berita.xml, /sitemap-kronik.xml, /sitemap-historia.xml
 *
 * PERBAIKAN SEO/GSC:
 * - changefreq disesuaikan dengan usia artikel (bukan seragam monthly)
 * - Artikel < 30 hari → weekly, < 90 hari → monthly, lainnya → yearly
 * - priority dinamis: < 30 hari = 0.9, < 90 hari = 0.8, lainnya = 0.7
 * - image:license ditambahkan untuk Google Image Search
 * - lastmod format ISO 8601 (c) agar GSC baca waktu persisnya
 */

error_reporting(0);
ini_set('display_errors', 0);
ob_start();

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/SupabaseArticleManager.php';

ob_clean();
header('Content-Type: application/xml; charset=utf-8');

$base = 'https://www.parokitulungagung.org';
$now  = date('c');

$allowedMenus = ['berita', 'kronik', 'historia'];
$menu = $_GET['menu'] ?? 'berita';

if (!in_array($menu, $allowedMenus)) {
    http_response_code(404);
    echo 'Invalid sitemap';
    exit;
}

// ─────────────────────────────────────────
// CACHE (1 jam)
// ─────────────────────────────────────────
$cacheDir  = __DIR__ . '/cache';
$cacheFile = $cacheDir . "/sitemap-{$menu}.xml";
$cacheTime = 3600;

if (!is_dir($cacheDir)) mkdir($cacheDir, 0755, true);

if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTime) {
    readfile($cacheFile);
    exit;
}

ob_start();

$am   = new SupabaseArticleManager();
$data = $am->getAll($menu, true);

function xml($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES | ENT_XML1, 'UTF-8');
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
echo '        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";

foreach ($data as $art) {
    $slug = strtolower(trim($art['slug'] ?? $art['id'] ?? ''));
    $slug = preg_replace('/[^a-z0-9\-]+/', '-', $slug);
    $slug = trim($slug, '-');
    if (!$slug) continue;

    $url = "{$base}/artikel/{$menu}/{$slug}";

    // lastmod ISO 8601 lengkap (bukan hanya Y-m-d) agar GSC tahu jam update-nya
    $rawDate = $art['updated_at'] ?? $art['published_at'] ?? $now;
    $lastmod = date('c', strtotime($rawDate));

    // Hitung usia artikel dalam hari
    $artAge = (time() - strtotime($art['published_at'] ?? $art['created_at'] ?? 'now')) / 86400;

    // changefreq & priority sesuai usia artikel
    if ($artAge < 7) {
        $changefreq = 'daily';
        $priority   = '0.9';
    } elseif ($artAge < 30) {
        $changefreq = 'weekly';
        $priority   = '0.9';
    } elseif ($artAge < 90) {
        $changefreq = 'monthly';
        $priority   = '0.8';
    } else {
        $changefreq = 'yearly';
        $priority   = '0.7';
    }

    echo "  <url>\n";
    echo "    <loc>" . xml($url) . "</loc>\n";
    echo "    <lastmod>{$lastmod}</lastmod>\n";
    echo "    <changefreq>{$changefreq}</changefreq>\n";
    echo "    <priority>{$priority}</priority>\n";

    // Image sitemap
    if (!empty($art['thumbnail'])) {
        $img = $art['thumbnail'];
        if (!preg_match('#^https?://#', $img)) {
            $img = $base . '/' . ltrim($img, '/');
        }
        echo "    <image:image>\n";
        echo "      <image:loc>" . xml($img) . "</image:loc>\n";
        if (!empty($art['judul'])) {
            echo "      <image:title>" . xml(strip_tags($art['judul'])) . "</image:title>\n";
        }
        if (!empty($art['ringkasan'])) {
            $caption = mb_substr(strip_tags($art['ringkasan']), 0, 100, 'UTF-8');
            echo "      <image:caption>" . xml($caption) . "</image:caption>\n";
        }
        // Deklarasi lisensi gambar membantu Google Image Search
        echo "      <image:license>" . xml($base) . "</image:license>\n";
        echo "    </image:image>\n";
    }

    echo "  </url>\n";
}

echo '</urlset>';

file_put_contents($cacheFile, ob_get_contents());
ob_end_flush();
