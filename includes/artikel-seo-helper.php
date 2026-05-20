<?php
/**
 * includes/artikel-seo-helper.php
 * ─────────────────────────────────────────────────────────────────────────
 * Helper integrasi ImageSeoGenerator untuk halaman artikel-detail.php.
 *
 * Cara pakai di artikel-detail.php (tambahkan setelah load SupabaseArticleManager):
 *
 *   require_once __DIR__ . '/../includes/seo-keywords.php';
 *   require_once __DIR__ . '/../includes/ImageSeoGenerator.php';
 *   require_once __DIR__ . '/../includes/artikel-seo-helper.php';
 *
 *   // 1. Pre-load SEO data artikel sekaligus (mencegah N+1 query ke Supabase)
 *   ImageSeoGenerator::preloadForArticle($art['id']);
 *
 *   // 2. Render konten dengan SEO inject
 *   echo enhance_artikel_images($art['konten'], [
 *       'id'       => $art['id'],
 *       'judul'    => $art['judul'],
 *       'tags'     => $art['tags'],
 *       'kategori' => $menuLabel,   // mis. 'Liputan Berita'
 *       'menu'     => $menu,        // mis. 'berita'
 *   ]);
 *
 *   // 3. (Opsional) Dapatkan ImageObject schema untuk <script type="ld+json">
 *   $schemas = get_artikel_image_schemas($art['konten'], $artData);
 * ─────────────────────────────────────────────────────────────────────────
 */

if (!function_exists('enhance_artikel_images')) {

    /**
     * Ganti semua <img> di dalam HTML artikel dengan versi SEO-enhanced.
     *
     * Fitur:
     * - Inject alt, title attribute dari ImageSeoGenerator
     * - Bungkus dengan <figure> + <figcaption> untuk gambar yang punya caption
     * - Tambahkan atribut loading="lazy" dan decoding="async"
     * - Tambahkan data-schema untuk schema.org ImageObject
     * - Tambahkan class 'artikel-img' untuk styling
     *
     * @param  string $konten  HTML konten artikel
     * @param  array  $artData ['id','judul','tags','kategori','menu']
     * @return string          HTML dengan img tags yang di-enhance
     */
    function enhance_artikel_images(string $konten, array $artData): string
    {
        if (empty($konten)) return $konten;

        // Pastikan ImageSeoGenerator sudah di-load
        if (!class_exists('ImageSeoGenerator')) return $konten;

        return preg_replace_callback(
            '/<img\b([^>]*)>/i',
            static function (array $m) use ($artData): string {
                $attrs = $m[1];

                // Ekstrak src
                preg_match('/\bsrc\s*=\s*["\']([^"\']+)["\']/', $attrs, $srcM);
                $src = trim($srcM[1] ?? '');
                if (!$src) return $m[0]; // biarkan img original jika tidak ada src

                // Dapatkan SEO data (dari memory cache → file cache → Supabase → generate)
                $seo = ImageSeoGenerator::generate($src, $artData);

                // Bersihkan atribut alt & title yang lama dari HTML (akan diganti)
                $clean = preg_replace('/\s*\b(alt|title)\s*=\s*(["\'])[^"\']*\2/', '', $attrs);
                $clean = preg_replace('/\s*\balt\s*=\s*[^\s>]+/', '', $clean);

                // Siapkan nilai baru
                $alt     = htmlspecialchars($seo['alt']     ?? '', ENT_QUOTES, 'UTF-8');
                $title   = htmlspecialchars($seo['title']   ?? '', ENT_QUOTES, 'UTF-8');
                $caption = $seo['caption'] ?? '';
                $desc    = htmlspecialchars($seo['description'] ?? '', ENT_QUOTES, 'UTF-8');
                $kwJson  = htmlspecialchars(json_encode($seo['keywords'] ?? [], JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');

                // Tambahkan class artikel-img jika belum ada
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

                // Tambahkan loading & decoding jika belum ada
                if (!preg_match('/\\bloading\\b/i', $clean))  $clean .= ' loading="lazy"';
                if (!preg_match('/\\bdecoding\\b/i', $clean)) $clean .= ' decoding="async"';

                // ── Inject width & height untuk mencegah CLS (Core Web Vitals) ──
                // Hanya inject jika belum ada atribut width/height sama sekali
                if (!preg_match('/\\bwidth\\b/i', $clean) && !preg_match('/\\bheight\\b/i', $clean)) {
                    // Coba baca dimensi file dari disk (hanya untuk path lokal)
                    $srcPath = preg_replace('#^https?://[^/]+#', '', $src);
                    $srcPath = '/' . ltrim($srcPath, '/');
                    $fsFull  = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/') . $srcPath;

                    $imgW = 0; $imgH = 0;
                    if (file_exists($fsFull)) {
                        $dim = @getimagesize($fsFull);
                        if ($dim) { $imgW = (int)$dim[0]; $imgH = (int)$dim[1]; }
                    }

                    if ($imgW > 0 && $imgH > 0) {
                        $clean .= sprintf(' width="%d" height="%d"', $imgW, $imgH);
                    } else {
                        // Fallback dimensi default jika file tidak terbaca (URL eksternal, dsb)
                        $clean .= ' width="800" height="450"';
                    }
                }

                // Bangun tag img baru
                $imgTag = sprintf(
                    '<img%s alt="%s" title="%s" data-seo-desc="%s" data-seo-kw="%s">',
                    $clean, $alt, $title, $desc, $kwJson
                );

                // Bungkus dengan <figure> jika caption tersedia
                if (!empty(trim($caption))) {
                    $captionEsc = htmlspecialchars($caption, ENT_QUOTES, 'UTF-8');
                    return sprintf(
                        '<figure class="artikel-figure">%s<figcaption class="artikel-caption">%s</figcaption></figure>',
                        $imgTag,
                        $captionEsc
                    );
                }

                return $imgTag;
            },
            $konten
        );
    }

    /**
     * Generate array ImageObject schema.org untuk semua gambar dalam artikel.
     * Hasilnya bisa dimasukkan ke dalam JSON-LD di <head>.
     *
     * Contoh penggunaan di artikel-detail.php:
     *
     *   $imgSchemas = get_artikel_image_schemas($art['konten'], $artData);
     *   if ($imgSchemas) {
     *       $ldJson = [
     *           '@context' => 'https://schema.org',
     *           '@type'    => 'Article',
     *           'name'     => $art['judul'],
     *           'image'    => $imgSchemas,
     *       ];
     *       echo '<script type="application/ld+json">' . json_encode($ldJson, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';
     *   }
     *
     * @param  string $konten  HTML konten artikel
     * @param  array  $artData ['id','judul','tags','kategori','menu']
     * @return array           Array dari ImageObject schema
     */
    function get_artikel_image_schemas(string $konten, array $artData): array
    {
        if (empty($konten) || !class_exists('ImageSeoGenerator')) return [];

        preg_match_all('/<img[^>]+src\s*=\s*["\']([^"\']+)["\'][^>]*>/i', $konten, $m);
        $srcs = array_unique(array_filter($m[1] ?? []));
        if (empty($srcs)) return [];

        $schemas = [];
        $baseUrl = (function_exists('is_https') && is_https() ? 'https' : 'http')
                 . '://' . ($_SERVER['HTTP_HOST'] ?? 'www.parokitulungagung.org');

        foreach ($srcs as $src) {
            $seo = ImageSeoGenerator::generate($src, $artData);

            // Buat URL absolut jika relatif
            $imgUrl = $src;
            if (!preg_match('#^https?://#i', $src)) {
                $imgUrl = rtrim($baseUrl, '/') . '/' . ltrim($src, '/');
            }

            $schema = [
                '@type'       => 'ImageObject',
                'url'         => $imgUrl,
                'contentUrl'  => $imgUrl,
                'name'        => $seo['title']       ?? '',
                'description' => $seo['description'] ?? '',
                'caption'     => $seo['caption']     ?? '',
            ];

            // Hapus key yang kosong
            $schemas[] = array_filter($schema, fn($v) => $v !== '');
        }

        return $schemas;
    }

    /**
     * Build meta description untuk halaman artikel dari konten.
     * Ambil teks pertama dari artikel, bersihkan HTML.
     *
     * @param  string $konten  HTML konten artikel
     * @param  int    $maxLen  Panjang maksimum karakter
     * @return string          Teks bersih untuk meta description
     */
    function build_artikel_meta_description(string $konten, int $maxLen = 160): string
    {
        $text = strip_tags($konten);
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);
        if (mb_strlen($text) <= $maxLen) return $text;
        $cut  = mb_substr($text, 0, $maxLen);
        $last = mb_strrpos($cut, ' ');
        return ($last > 100 ? mb_substr($cut, 0, $last) : $cut) . '…';
    }

    /**
     * Inject schema.org Article JSON-LD lengkap ke halaman artikel.
     * Panggil dari dalam <head> atau sebelum </body>.
     *
     * @param array  $art     Array artikel (id, judul, konten, thumbnail, tags, penulis, created_at, updated_at)
     * @param string $menu    Menu/section artikel ('berita','kronik','historia')
     * @param string $pageUrl URL lengkap halaman ini
     */
    function echo_artikel_json_ld(array $art, string $menu, string $pageUrl): void
    {
        $baseUrl = (function_exists('is_https') && is_https() ? 'https' : 'http')
                 . '://' . ($_SERVER['HTTP_HOST'] ?? 'www.parokitulungagung.org');

        $menuLabels = [
            'berita'   => 'Liputan Berita',
            'kronik'   => 'Kronik SMDTBA',
            'historia' => 'Historia Gereja',
        ];
        $kategoriLabel = $menuLabels[$menu] ?? 'Paroki SMDTBA';

        $artData = [
            'id'       => $art['id']    ?? '',
            'judul'    => $art['judul'] ?? '',
            'tags'     => $art['tags']  ?? '',
            'kategori' => $kategoriLabel,
            'menu'     => $menu,
        ];

        // Thumbnail
        $thumbUrl = '';
        if (!empty($art['thumbnail'])) {
            $t = $art['thumbnail'];
            $thumbUrl = preg_match('#^https?://#i', $t)
                ? $t
                : rtrim($baseUrl, '/') . '/' . ltrim($t, '/');
        }

        // Image schemas dari konten
        $imgSchemas = [];
        if (!empty($art['konten']) && class_exists('ImageSeoGenerator')) {
            $imgSchemas = get_artikel_image_schemas($art['konten'], $artData);
        }

        // Keywords dari seo-keywords.php
        $kw = '';
        if (function_exists('build_page_keywords')) {
            $kw = build_page_keywords(
                $art['judul'] ?? '',
                $art['tags']  ?? [],
                $kategoriLabel,
                20
            );
        }

        $datePublished = !empty($art['created_at'])
            ? date('c', strtotime($art['created_at']))
            : '';
        $dateModified  = !empty($art['updated_at'])
            ? date('c', strtotime($art['updated_at']))
            : $datePublished;

        $ldJson = array_filter([
            '@context'         => 'https://schema.org',
            '@type'            => 'Article',
            'headline'         => mb_substr($art['judul'] ?? '', 0, 110),
            'name'             => $art['judul'] ?? '',
            'description'      => build_artikel_meta_description($art['konten'] ?? ''),
            'url'              => $pageUrl,
            'datePublished'    => $datePublished,
            'dateModified'     => $dateModified,
            'author'           => [
                '@type' => 'Organization',
                'name'  => 'Paroki SMDTBA Tulungagung',
                'url'   => $baseUrl,
            ],
            'publisher'        => [
                '@type' => 'Organization',
                'name'  => 'Paroki Santa Maria Dengan Tidak Bernoda Asal Tulungagung',
                'url'   => $baseUrl,
                'logo'  => [
                    '@type' => 'ImageObject',
                    'url'   => $baseUrl . '/img/smdtba-logo.png',
                ],
            ],
            'image'            => !empty($imgSchemas) ? $imgSchemas : ($thumbUrl ? $thumbUrl : null),
            'thumbnailUrl'     => $thumbUrl ?: null,
            'articleSection'   => $kategoriLabel,
            'keywords'         => $kw ?: null,
            'inLanguage'       => 'id-ID',
            'isPartOf'         => [
                '@type' => 'WebSite',
                'name'  => 'Paroki SMDTBA Tulungagung',
                'url'   => $baseUrl,
            ],
        ], fn($v) => $v !== null && $v !== '');

        echo '<script type="application/ld+json">'
             . json_encode($ldJson, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
             . '</script>' . PHP_EOL;
    }

} // end if !function_exists
