<?php
/**
 * includes/artikel-seo-reader.php
 * ─────────────────────────────────────────────────────────────────────────
 * Helper READ-ONLY untuk halaman publik (artikel-detail.php).
 *
 * File ini HANYA membaca data dari tabel Supabase `image_seo`.
 * TIDAK ada logika generate, TIDAK ada tulis ke Supabase.
 * Jika data SEO belum ada untuk suatu gambar → img ditampilkan apa adanya.
 *
 * Cara pakai di artikel-detail.php:
 *
 *   require_once __DIR__ . '/../includes/artikel-seo-reader.php';
 *
 *   // 1. Preload semua SEO data artikel sekaligus (1 query, bukan N+1)
 *   $seoReader = new ArtikelSeoReader($art['id']);
 *
 *   // 2. Render konten dengan alt/caption dari Supabase
 *   echo $seoReader->enhanceImages($art['konten']);
 *
 *   // 3. (Opsional) JSON-LD schema.org di <head>
 *   $seoReader->echoJsonLd($art, $menu, $currentPageUrl);
 * ─────────────────────────────────────────────────────────────────────────
 */

// Helper: baca dimensi gambar dari disk, return ' width="W" height="H"' atau fallback
if (!function_exists('_reader_img_dims')) {
    function _reader_img_dims(string $src): string {
        if (!$src) return ' width="800" height="450"';
        $path = preg_replace('#^https?://[^/]+#', '', $src);
        $path = '/' . ltrim($path, '/');
        $fs   = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/') . $path;
        if (file_exists($fs)) {
            $dim = @getimagesize($fs);
            if ($dim && $dim[0] > 0 && $dim[1] > 0) {
                return sprintf(' width="%d" height="%d"', $dim[0], $dim[1]);
            }
        }
        return ' width="800" height="450"'; // fallback default artikel
    }
}

class ArtikelSeoReader
{
    private const TABLE = 'image_seo';

    /** [ image_url => seo_data ] */
    private array $cache = [];

    private string $artikelId;

    public function __construct(string $artikelId)
    {
        $this->artikelId = $artikelId;
        $this->preload();
    }

    // ══════════════════════════════════════════════════════════════════════
    //  PUBLIC API
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Ganti semua <img> dalam HTML artikel dengan versi yang sudah
     * memiliki alt, title, dan dibungkus <figure><figcaption> jika ada caption.
     *
     * Jika data SEO tidak ditemukan di Supabase → img dikembalikan apa adanya
     * (tidak ada generate, tidak ada tulis apapun).
     */
    public function enhanceImages(string $konten): string
    {
        if (empty($konten)) return $konten;

        return preg_replace_callback(
            '/<img\b([^>]*)>/i',
            function (array $m): string {
                $attrs = $m[1];

                // Ekstrak src
                preg_match('/\bsrc\s*=\s*["\']([^"\']+)["\']/', $attrs, $srcM);
                $src = trim($srcM[1] ?? '');
                if (!$src) return $m[0];

                // Cari SEO data dari cache (sudah di-preload)
                $seo = $this->getSeo($src);

                // Jika tidak ada data SEO → kembalikan img asli + lazy load + dimensi saja
                if (!$seo) {
                    $extra = '';
                    if (!preg_match('/\\bloading\\b/i',  $attrs)) $extra .= ' loading="lazy"';
                    if (!preg_match('/\\bdecoding\\b/i', $attrs)) $extra .= ' decoding="async"';
                    // Inject width & height — cegah CLS meski tidak ada data SEO
                    if (!preg_match('/\\bwidth\\b/i', $attrs) && !preg_match('/\\bheight\\b/i', $attrs)) {
                        preg_match('/\\bsrc\\s*=\\s*["\']([^"\']+)["\']/', $attrs, $sM);
                        $sSrc  = trim($sM[1] ?? '');
                        $extra .= _reader_img_dims($sSrc);
                    }
                    return '<img' . $attrs . $extra . '>';
                }

                // Bersihkan alt & title lama
                $clean = preg_replace('/\s*\b(alt|title)\s*=\s*(["\'])[^"\']*\2/', '', $attrs);

                // Tambahkan class artikel-img
                if (preg_match('/\bclass\s*=\s*["\']([^"\']*)["\']/', $clean, $clsM)) {
                    if (!str_contains($clsM[1], 'artikel-img')) {
                        $clean = preg_replace(
                            '/\bclass\s*=\s*["\']([^"\']*)["\']/',
                            'class="' . $clsM[1] . ' artikel-img"',
                            $clean
                        );
                    }
                } else {
                    $clean .= ' class="artikel-img"';
                }

                // Lazy load
                if (!preg_match('/\\bloading\\b/i',  $clean)) $clean .= ' loading="lazy"';
                if (!preg_match('/\\bdecoding\\b/i', $clean)) $clean .= ' decoding="async"';

                // Inject width & height — cegah CLS
                if (!preg_match('/\\bwidth\\b/i', $clean) && !preg_match('/\\bheight\\b/i', $clean)) {
                    $clean .= _reader_img_dims($src);
                }

                $alt   = htmlspecialchars($seo['alt']   ?? '', ENT_QUOTES, 'UTF-8');
                $title = htmlspecialchars($seo['title'] ?? '', ENT_QUOTES, 'UTF-8');

                $imgTag = sprintf('<img%s alt="%s" title="%s">', $clean, $alt, $title);

                // Bungkus dengan figure jika ada caption
                $caption = trim($seo['caption'] ?? '');
                if ($caption !== '') {
                    return sprintf(
                        '<figure class="artikel-figure">%s<figcaption class="artikel-caption">%s</figcaption></figure>',
                        $imgTag,
                        htmlspecialchars($caption, ENT_QUOTES, 'UTF-8')
                    );
                }

                return $imgTag;
            },
            $konten
        );
    }

    /**
     * Cetak JSON-LD schema.org Article lengkap ke halaman.
     * Panggil dari dalam <head>.
     *
     * @param array  $art     Data artikel (id, judul, konten, thumbnail, tags, penulis, created_at)
     * @param string $menu    'berita' | 'kronik' | 'historia'
     * @param string $pageUrl URL lengkap halaman ini
     */
    public function echoJsonLd(array $art, string $menu, string $pageUrl): void
    {
        $baseUrl = $this->baseUrl();

        $menuLabels = [
            'berita'   => 'Liputan Berita',
            'kronik'   => 'Kronik SMDTBA',
            'historia' => 'Historia Gereja',
        ];
        $kategori = $menuLabels[$menu] ?? 'Paroki SMDTBA';

        // Thumbnail
        $thumbUrl = '';
        if (!empty($art['thumbnail'])) {
            $t = $art['thumbnail'];
            $thumbUrl = preg_match('#^https?://#i', $t)
                ? $t
                : rtrim($baseUrl, '/') . '/' . ltrim($t, '/');
        }

        // ImageObject schemas dari cache
        $imgSchemas = $this->buildImageSchemas($art['konten'] ?? '', $baseUrl);

        // Meta description: ambil teks pertama dari konten
        $desc = $this->buildMetaDesc($art['konten'] ?? '');

        $datePublished = !empty($art['created_at'])
            ? date('c', strtotime($art['created_at'])) : '';
        $dateModified  = !empty($art['updated_at'])
            ? date('c', strtotime($art['updated_at'])) : $datePublished;

        $ldJson = array_filter([
            '@context'       => 'https://schema.org',
            '@type'          => 'Article',
            'headline'       => mb_substr($art['judul'] ?? '', 0, 110),
            'name'           => $art['judul'] ?? '',
            'description'    => $desc,
            'url'            => $pageUrl,
            'datePublished'  => $datePublished,
            'dateModified'   => $dateModified,
            'author'         => [
                '@type' => 'Organization',
                'name'  => 'Paroki SMDTBA Tulungagung',
                'url'   => $baseUrl,
            ],
            'publisher'      => [
                '@type'  => 'Organization',
                'name'   => 'Paroki Santa Maria Dengan Tidak Bernoda Asal Tulungagung',
                'url'    => $baseUrl,
                'logo'   => [
                    '@type' => 'ImageObject',
                    'url'   => $baseUrl . '/img/smdtba-logo.png',
                ],
            ],
            'image'          => !empty($imgSchemas) ? $imgSchemas
                              : ($thumbUrl          ? $thumbUrl  : null),
            'thumbnailUrl'   => $thumbUrl ?: null,
            'articleSection' => $kategori,
            'inLanguage'     => 'id-ID',
            'isPartOf'       => [
                '@type' => 'WebSite',
                'name'  => 'Paroki SMDTBA Tulungagung',
                'url'   => $baseUrl,
            ],
        ], fn($v) => $v !== null && $v !== '');

        echo '<script type="application/ld+json">'
             . json_encode($ldJson, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
             . '</script>' . PHP_EOL;
    }

    /**
     * Kembalikan meta description bersih dari konten HTML artikel.
     */
    public function buildMetaDesc(string $konten, int $maxLen = 160): string
    {
        $text = preg_replace('/\s+/', ' ', strip_tags($konten));
        $text = trim($text);
        if (mb_strlen($text) <= $maxLen) return $text;
        $cut  = mb_substr($text, 0, $maxLen);
        $last = mb_strrpos($cut, ' ');
        return ($last > 100 ? mb_substr($cut, 0, $last) : $cut) . '…';
    }

    /**
     * Kembalikan alt text untuk satu gambar (untuk dipakai manual di template).
     * Return string kosong jika belum ada data.
     */
    public function getAlt(string $src): string
    {
        return $this->getSeo($src)['alt'] ?? '';
    }

    /**
     * Kembalikan caption untuk satu gambar.
     */
    public function getCaption(string $src): string
    {
        return $this->getSeo($src)['caption'] ?? '';
    }

    // ══════════════════════════════════════════════════════════════════════
    //  PRIVATE
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Batch fetch semua SEO data artikel dari Supabase.
     * 1 request, simpan ke $this->cache.
     */
    private function preload(): void
    {
        if (!$this->artikelId || !$this->sbUrl() || !$this->sbKey()) return;

        $url = $this->sbUrl() . '/rest/v1/' . self::TABLE
             . '?artikel_id=eq.' . urlencode($this->artikelId)
             . '&select=image_url,alt_text,caption,title_attr,schema_description,image_keywords';

        $ctx = stream_context_create([
            'http' => [
                'header'        => "apikey: " . $this->sbKey() . "\r\n"
                                 . "Authorization: Bearer " . $this->sbKey() . "\r\n"
                                 . "Accept: application/json\r\n",
                'timeout'       => 5,
                'ignore_errors' => true,
            ],
            'ssl' => ['verify_peer' => true],
        ]);

        $resp = @file_get_contents($url, false, $ctx);
        if (!$resp) return;

        $rows = json_decode($resp, true);
        if (!is_array($rows)) return;

        foreach ($rows as $row) {
            $imgUrl = trim($row['image_url'] ?? '');
            if (!$imgUrl) continue;

            $kw = $row['image_keywords'] ?? '[]';
            if (is_string($kw)) $kw = json_decode($kw, true);
            if (!is_array($kw)) $kw = [];

            $this->cache[$imgUrl] = [
                'alt'         => $row['alt_text']          ?? '',
                'caption'     => $row['caption']            ?? '',
                'title'       => $row['title_attr']         ?? '',
                'description' => $row['schema_description'] ?? '',
                'keywords'    => $kw,
            ];
        }
    }

    private function getSeo(string $src): ?array
    {
        return $this->cache[$src] ?? null;
    }

    private function buildImageSchemas(string $konten, string $baseUrl): array
    {
        if (empty($konten)) return [];

        preg_match_all('/<img[^>]+src\s*=\s*["\']([^"\']+)["\'][^>]*>/i', $konten, $m);
        $srcs = array_unique(array_filter($m[1] ?? []));
        if (empty($srcs)) return [];

        $schemas = [];
        foreach ($srcs as $src) {
            $seo = $this->getSeo($src);
            if (!$seo) continue;

            $imgUrl = preg_match('#^https?://#i', $src)
                ? $src
                : rtrim($baseUrl, '/') . '/' . ltrim($src, '/');

            $schemas[] = array_filter([
                '@type'       => 'ImageObject',
                'url'         => $imgUrl,
                'contentUrl'  => $imgUrl,
                'name'        => $seo['title']       ?? '',
                'description' => $seo['description'] ?? '',
                'caption'     => $seo['caption']     ?? '',
            ], fn($v) => $v !== '');
        }

        return $schemas;
    }

    private function baseUrl(): string
    {
        $scheme = (function_exists('is_https') && is_https()) ? 'https' : 'http';
        return $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'www.parokitulungagung.org');
    }

    private function sbUrl(): string
    {
        return defined('SUPABASE_URL') ? rtrim(SUPABASE_URL, '/') : '';
    }

    private function sbKey(): string
    {
        return defined('SUPABASE_ANON_KEY') ? SUPABASE_ANON_KEY : '';
    }
}
