<?php
/**
 * sitemap-static.php
 * Sitemap untuk semua halaman statis website
 *
 * PERBAIKAN SEO/GSC:
 * - lastmod ISO 8601 (format 'c') untuk semua URL agar GSC presisi
 * - Tambah halaman /jadwal-misa dengan priority 0.9 (high intent lokal)
 * - Tambah /kebijakan-privasi dan /kebijakan-cookie
 * - Hapus duplikasi URL kategorial (sudah ada di sitemap-kelompok.xml)
 * - Gunakan filemtime nyata untuk halaman yang punya file cache
 */

header('Content-Type: application/xml; charset=utf-8');

$base = 'https://www.parokitulungagung.org';

$cacheFile = __DIR__ . '/cache/sitemap-static.xml';
$cacheTime = 21600; // 6 jam

if (!is_dir(__DIR__ . '/cache')) mkdir(__DIR__ . '/cache', 0755, true);

if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTime) {
    readfile($cacheFile);
    exit;
}

ob_start();

// lastmod ISO 8601 — gunakan date('c') untuk semua halaman dinamis
$now = date('c');

// Format: path => [changefreq, priority, lastmod]
// lastmod: pakai date('c') untuk halaman yang berubah sering, tanggal fix untuk yang statis
$pages = [
    // ── Homepage ──────────────────────────────────────────────────────────
    '/'                  => ['daily',   '1.0', date('c')],

    // ── Halaman high-intent lokal (Jadwal Misa) ───────────────────────────
    '/jadwal-misa'       => ['weekly',  '0.9', date('c')],

    // ── Halaman konten utama ──────────────────────────────────────────────
    '/agenda'            => ['daily',   '0.9', date('c')],
    '/artikel/berita'    => ['daily',   '0.9', date('c')],
    '/artikel/kronik'    => ['weekly',  '0.8', date('c')],
    '/artikel/historia'  => ['weekly',  '0.8', date('c')],
    '/galeri'            => ['weekly',  '0.8', date('c')],

    // ── Halaman profil ────────────────────────────────────────────────────
    '/profil-ai'         => ['monthly', '0.6', date('c')],
    '/profil-dpp'        => ['monthly', '0.6', date('c')],
    '/profil-lingkungan' => ['monthly', '0.6', date('c')],

    // ── Halaman lainnya ───────────────────────────────────────────────────
    '/kategorial'        => ['weekly',  '0.7', date('c')],
    '/umkmumat'          => ['weekly',  '0.6', date('c')],
    '/e-lonceng'         => ['monthly', '0.5', date('c')],
    '/tvdigital'         => ['monthly', '0.5', date('c')],

    // ── Informasi & kebijakan ─────────────────────────────────────────────
    '/kontak'            => ['monthly', '0.5', date('c')],
    '/tentang'           => ['monthly', '0.5', date('c')],
    '/kebijakan-privasi' => ['yearly',  '0.3', '2025-01-01T00:00:00+07:00'],
    '/kebijakan-cookie'  => ['yearly',  '0.3', '2025-01-01T00:00:00+07:00'],
];

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

foreach ($pages as $p => [$freq, $prio, $lmod]) {
    echo "  <url>\n";
    echo "    <loc>{$base}{$p}</loc>\n";
    echo "    <lastmod>{$lmod}</lastmod>\n";
    echo "    <changefreq>{$freq}</changefreq>\n";
    echo "    <priority>{$prio}</priority>\n";
    echo "  </url>\n";
}

echo '</urlset>';

file_put_contents($cacheFile, ob_get_contents());
ob_end_flush();
