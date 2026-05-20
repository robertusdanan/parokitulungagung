<?php
// ── Helper: baca file aset lokal, embed inline (mencegah error 525 Cloudflare) ──
if (!function_exists('readAsset')) {
    function readAsset(string $path): string {
        $full = rtrim($_SERVER['DOCUMENT_ROOT'] ?? __DIR__ . '/../../', '/') . '/' . ltrim($path, '/');
        return file_exists($full) ? file_get_contents($full) : "/* FILE NOT FOUND: {$path} */";
    }
}

// ── Konstanta situs ────────────────────────────────────────────────────
if (!defined('SITE_BASE'))     define('SITE_BASE',     'https://www.parokitulungagung.org');
if (!defined('SITE_NAME'))     define('SITE_NAME',     'Paroki Tulungagung');
if (!defined('SITE_NAME_FULL'))define('SITE_NAME_FULL','Paroki Santa Maria Dengan Tidak Bernoda Asal Tulungagung');
if (!defined('SITE_OG_IMAGE')) define('SITE_OG_IMAGE', SITE_BASE . '/img/ogpreview/default.jpg'); // 🔥 tetap JPG
if (!defined('SITE_LOGO'))     define('SITE_LOGO',     SITE_BASE . '/img/header-logo-1.webp');
if (!defined('SITE_FB_APP'))   define('SITE_FB_APP',   '943090234902556');

// ── Defaults ───────────────────────────────────────────────────────────
$seo = array_merge([
    'title'        => SITE_NAME,
    'description'  => 'Website resmi Gereja Katolik Santa Maria Dengan Tidak Bernoda Asal (SMDTBA) Tulungagung. Jadwal misa, kegiatan paroki, artikel, galeri foto, dan informasi umat.',
    'canonical'    => SITE_BASE . strtok($_SERVER['REQUEST_URI'] ?? '/', '?'),
    'type'         => 'website',
    'image'        => SITE_OG_IMAGE,
    'keywords'     => 'paroki smdtba, gereja katolik tulungagung, santa maria tidak bernoda asal, misa tulungagung',
    'published'    => '',
    'modified'     => '',
    'author'       => '',
    'noindex'      => false,
    // ── Ekstensi schema ─────────────────────────────────────────────────
    // 'faq'    => [['q'=>'Pertanyaan?','a'=>'Jawaban.'], ...]  → inject FAQPage schema
    // 'video'  => ['url'=>'...','name'=>'...','description'=>'...','thumbnail'=>'...','upload_date'=>'...']
    // 'article_type' => 'NewsArticle' | 'BlogPosting' | 'Article' (default NewsArticle)
    'faq'          => [],
    'video'        => [],
    'article_type' => 'NewsArticle',
], $seo ?? []);

$breadcrumbs = $breadcrumbs ?? [];
$extraCss    = $extraCss    ?? [];

// ── Sanitasi ───────────────────────────────────────────────────────────
function _seo_e(string $s): string {
    return htmlspecialchars(strip_tags($s), ENT_QUOTES | ENT_HTML5, 'UTF-8');
}
$sTitle  = _seo_e($seo['title']);
$sDesc   = _seo_e($seo['description']);
$sCanon  = htmlspecialchars($seo['canonical'], ENT_QUOTES, 'UTF-8');

// ============================================================
// 🔥 OG IMAGE SYSTEM (FINAL FIX - SESUAI SISTEM KAMU)
// ============================================================

$_root = rtrim($_SERVER['DOCUMENT_ROOT'], '/');

// ambil image dari SEO
$_rawImg = $seo['image'] ?: SITE_OG_IMAGE;

// normalisasi path
$_imgPath = preg_replace('#^https?://[^/]+#', '', $_rawImg);
$_imgPath = '/' . ltrim($_imgPath, '/');

// ambil nama file tanpa ekstensi
$_imgBase = pathinfo($_imgPath, PATHINFO_FILENAME);

// 🔥 paksa OG ke folder ogpreview + JPG
$_ogRelPath = '/img/ogpreview/' . $_imgBase . '.jpg';
$_ogFsPath  = $_root . $_ogRelPath;

// cek file OG ada atau tidak
if (file_exists($_ogFsPath)) {
    $_finalImg = SITE_BASE . $_ogRelPath;
} else {
    // fallback tetap ke JPG (bukan webp)
    $_finalImg = SITE_BASE . '/img/ogpreview/' . $_imgBase . '.jpg';
}

// normalize domain www
$_finalImg = str_replace('https://parokitulungagung.org', 'https://www.parokitulungagung.org', $_finalImg);

// output final
$sImg = htmlspecialchars($_finalImg, ENT_QUOTES, 'UTF-8');

// 🔥 WA SAFE (paksa jpeg)
$_imgType = 'image/jpeg';

// ── lainnya tetap ───────────────────────────────────────────
$sKw     = _seo_e($seo['keywords']);
$sType   = in_array($seo['type'], ['website','article']) ? $seo['type'] : 'website';
$sAuthor = _seo_e($seo['author'] ?: SITE_NAME);
$sRobots = $seo['noindex'] ? 'noindex, nofollow' : 'index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1';

// Title lengkap — hindari duplikasi jika judul sudah mengandung nama situs
$_titleContainsSiteName = (
    $seo['title'] === SITE_NAME ||
    $seo['title'] === SITE_NAME_FULL ||
    mb_stripos($seo['title'], 'Paroki Tulungagung') !== false ||
    mb_stripos($seo['title'], 'Gereja Katolik Tulungagung') !== false
);
$fullTitle = $_titleContainsSiteName
    ? _seo_e($seo['title'])
    : _seo_e($seo['title'] . ' – Paroki Tulungagung');

// ── JSON-LD: Organization ──────────────────────────────────────────────
$orgSchema = [
    '@context'      => 'https://schema.org',
    '@type'         => 'Church',
    '@id'           => SITE_BASE . '/#organization',
    'name'          => SITE_NAME_FULL,
    'alternateName' => 'Paroki SMDTBA',
    'url'           => SITE_BASE,
    'logo'          => ['@type' => 'ImageObject', 'url' => SITE_LOGO, 'width' => 400, 'height' => 400],
    'image'         => SITE_LOGO,
    'description'   => 'Gereja Katolik Paroki Santa Maria Dengan Tidak Bernoda Asal Tulungagung, Jawa Timur.',
    'address'       => [
        '@type'           => 'PostalAddress',
        'streetAddress'   => 'Jl. Ahmad Yani Tim. Gg. IV No.1, Bago',
        'addressLocality' => 'Tulungagung',
        'addressRegion'   => 'Jawa Timur',
        'postalCode'      => '66218',
        'addressCountry'  => 'ID',
    ],
    'geo'          => ['@type' => 'GeoCoordinates', 'latitude' => -8.065658, 'longitude' => 111.905091],
    'telephone'    => '+628563678844',
    'openingHoursSpecification' => [
        // Misa pagi Senin-Kamis
        ['@type' => 'OpeningHoursSpecification', 'dayOfWeek' => ['Monday','Tuesday','Wednesday','Thursday'], 'opens' => '05:30', 'closes' => '06:15'],
        // Misa Jumat sore
        ['@type' => 'OpeningHoursSpecification', 'dayOfWeek' => ['Friday'], 'opens' => '17:00', 'closes' => '18:00'],
        // Misa Sabtu sore
        ['@type' => 'OpeningHoursSpecification', 'dayOfWeek' => ['Saturday'], 'opens' => '18:00', 'closes' => '19:00'],
        // Misa Minggu pagi
        ['@type' => 'OpeningHoursSpecification', 'dayOfWeek' => ['Sunday'], 'opens' => '07:00', 'closes' => '08:00'],
    ],
    'currenciesAccepted' => 'IDR',
    'priceRange'         => 'Gratis',
    'sameAs'       => [
        // URL harus IDENTIK dengan yang ada di footer.php
        // Footer: facebook.com/SantaMariaDTBA, instagram.com/komsosparokitulungagung, youtube.com/@KomsosParokiTulungagung
        'https://www.facebook.com/SantaMariaDTBA',
        'https://www.instagram.com/komsosparokitulungagung/',
        'https://www.youtube.com/@KomsosParokiTulungagung',
    ],
    'areaServed' => [
        '@type' => 'City',
        'name' => 'Tulungagung'
    ],
    'hasMap' => 'https://www.google.com/maps/place/Gereja+Katolik+Santa+Maria+Dengan+Tidak+Bernoda+Asal/@-8.065658,111.905091,17z',
    'identifier' => [
        '@type' => 'PropertyValue',
        'name'  => 'Google Maps CID',
        'value' => 'ChIJ9bY4OeAieC8RrqoIxS7uXlk',
    ],
    'foundingDate' => '1917',
];

// ── JSON-LD: BreadcrumbList ────────────────────────────────────────────
$breadcrumbSchema = null;
if (!empty($breadcrumbs)) {
    $_bcItems = [];
    foreach ($breadcrumbs as $_bcIdx => $bc) {
        $_bcItem = ['@type' => 'ListItem', 'position' => $_bcIdx + 1, 'name' => strip_tags($bc['name'] ?? '')];
        if (!empty($bc['url'])) $_bcItem['item'] = $bc['url'];
        $_bcItems[] = $_bcItem;
    }
    $breadcrumbSchema = ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', '@id' => ($seo['canonical'] ?? SITE_BASE) . '#breadcrumb', 'itemListElement' => $_bcItems];
}

// ── Bahasa & Locale ───────────────────────────────────────────────────
$_pageLang   = $seo['lang']       ?? 'id';
$_ogLocale   = $seo['og_locale']  ?? ($_pageLang === 'en' ? 'en_US' : 'id_ID');
$_inLanguage = ($_pageLang === 'en') ? 'en-US' : 'id-ID';

// ── JSON-LD: Article ───────────────────────────────────────────────────
// $seo_images_schema → array ImageObject dari artikel-detail.php (pass via global)
// Jika tidak ada (halaman non-artikel), fallback ke OG image saja.
$_articleImages = [];
if (!empty($GLOBALS['_seo_images_schema']) && is_array($GLOBALS['_seo_images_schema'])) {
    $_articleImages = $GLOBALS['_seo_images_schema'];
}
if (empty($_articleImages)) {
    $_articleImages = [['@type' => 'ImageObject', 'url' => $_finalImg, 'contentUrl' => $_finalImg]];
}

$articleSchema = null;
if ($sType === 'article') {
    $authorName = strip_tags($seo['author'] ?: '');

    // Author: Person dengan @id agar bisa diverifikasi Google (E-E-A-T)
    // @id pakai URL profil penulis — fallback ke anchor unik di domain ini
    $authorSlug   = $authorName ? preg_replace('/[^a-z0-9]+/', '-', strtolower($authorName)) : '';
    $authorEntity = $authorName
        ? [
            '@type' => 'Person',
            '@id'   => SITE_BASE . '/penulis/' . $authorSlug,
            'name'  => $authorName,
            'url'   => SITE_BASE . '/penulis/' . $authorSlug,
          ]
        : ['@type' => 'Organization', '@id' => SITE_BASE . '/#organization',
           'name'  => SITE_NAME,      'url'  => SITE_BASE];

    // articleSection dari menu (berita/kronik/historia)
    $_menuLabel = $seo['menu_label'] ?? '';

    $_artType = in_array($seo['article_type'] ?? '', ['NewsArticle','BlogPosting','Article'])
        ? $seo['article_type']
        : 'NewsArticle';

    $articleSchema = [
        '@context'         => 'https://schema.org',
        '@type'            => $_artType,
        '@id'              => ($seo['canonical'] ?? '') . '#newsarticle',
        'headline'         => mb_substr(strip_tags($seo['title']), 0, 110),
        'name'             => strip_tags($seo['title']),
        'description'      => strip_tags($seo['description']),
        'image'            => $_articleImages,
        'datePublished'    => $seo['published'] ?: date('c'),
        'dateModified'     => $seo['modified']  ?: ($seo['published'] ?: date('c')),
        'author'           => $authorEntity,
        'publisher'        => [
            '@type' => 'Organization',
            '@id'   => SITE_BASE . '/#organization',
            'name'  => SITE_NAME_FULL,
            'logo'  => [
                '@type'  => 'ImageObject',
                'url'    => SITE_OG_IMAGE,
                'width'  => 400,
                'height' => 400,
            ],
        ],
        'mainEntityOfPage' => [
            '@type' => 'WebPage',
            '@id'   => $seo['canonical'] ?? '',
        ],
        'isPartOf' => [
            '@type' => 'WebSite',
            '@id'   => SITE_BASE . '/#website',
        ],
        'inLanguage'       => $_inLanguage,
    ];

    if (!empty($seo['keywords']))  $articleSchema['keywords']       = strip_tags($seo['keywords']);
    if (!empty($_menuLabel))       $articleSchema['articleSection'] = $_menuLabel;

    // Speakable — tunjuk headline & deskripsi ke Google Assistant / voice search
    $articleSchema['speakable'] = [
        '@type'       => 'SpeakableSpecification',
        'cssSelector' => ['.art-detail-title', '.art-detail-content > p:first-of-type'],
    ];

    // wordCount — hitung dari deskripsi sebagai perkiraan minimum (jika tidak ada konten HTML)
    // Halaman artikel-detail.php bisa override ini dengan $seo['word_count']
    if (!empty($seo['word_count'])) {
        $articleSchema['wordCount'] = (int) $seo['word_count'];
    }

    // VideoObject — jika halaman punya video embed (YouTube)
    if (!empty($seo['video']) && !empty($seo['video']['url'])) {
        $articleSchema['video'] = [
            '@type'           => 'VideoObject',
            'name'            => strip_tags($seo['video']['name'] ?? strip_tags($seo['title'])),
            'description'     => strip_tags($seo['video']['description'] ?? strip_tags($seo['description'])),
            'thumbnailUrl'    => $seo['video']['thumbnail'] ?? $_finalImg,
            'uploadDate'      => $seo['video']['upload_date'] ?? ($seo['published'] ?: date('c')),
            'embedUrl'        => $seo['video']['url'],
            'publisher'       => ['@id' => SITE_BASE . '/#organization'],
        ];
    }
}

// ── JSON-LD: WebSite + Sitelinks Searchbox ────────────────────────────
$websiteSchema = [
    '@context'        => 'https://schema.org',
    '@type'           => 'WebSite',
    '@id'             => SITE_BASE . '/#website',
    'name'            => SITE_NAME_FULL,
    'alternateName'   => 'Paroki SMDTBA Tulungagung',
    'url'             => SITE_BASE,
    'inLanguage'      => 'id-ID',
    'publisher'       => ['@id' => SITE_BASE . '/#organization'],
    'potentialAction' => [
        '@type'       => 'SearchAction',
        'target'      => [
            '@type'       => 'EntryPoint',
            'urlTemplate' => SITE_BASE . '/artikel/berita?q={search_term_string}',
        ],
        'query-input' => 'required name=search_term_string',
    ],
];

// ── JSON-LD: FAQPage — inject jika ada $seo['faq'] ─────────────────────
$faqSchema = null;
if (!empty($seo['faq']) && is_array($seo['faq']) && count($seo['faq']) > 0) {
    $_faqItems = [];
    foreach ($seo['faq'] as $_faq) {
        if (empty($_faq['q']) || empty($_faq['a'])) continue;
        $_faqItems[] = [
            '@type'          => 'Question',
            'name'           => strip_tags($_faq['q']),
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text'  => strip_tags($_faq['a']),
            ],
        ];
    }
    if (!empty($_faqItems)) {
        $faqSchema = [
            '@context'   => 'https://schema.org',
            '@type'      => 'FAQPage',
            'mainEntity' => $_faqItems,
        ];
    }
}

// Gabung semua schema
$schemas = [$orgSchema, $websiteSchema];
if ($breadcrumbSchema) $schemas[] = $breadcrumbSchema;
if ($articleSchema)    $schemas[] = $articleSchema;
if ($faqSchema)        $schemas[] = $faqSchema;

// ── JSON-LD: WebPage / HomePage (untuk halaman non-artikel) ───────────────
if ($sType !== 'article') {
    $_isHomepageSchema = ($seo['canonical'] === SITE_BASE || $seo['canonical'] === SITE_BASE . '/');

    $webpageSchema = [
        '@context'         => 'https://schema.org',
        '@type'            => $_isHomepageSchema ? 'WebPage' : 'WebPage',
        '@id'              => ($seo['canonical'] ?? SITE_BASE) . '#webpage',
        'url'              => $seo['canonical'] ?? SITE_BASE,
        'name'             => strip_tags($seo['title']),
        'description'      => strip_tags($seo['description']),
        'inLanguage'       => $_inLanguage,
        'isPartOf'         => ['@id' => SITE_BASE . '/#website'],
        'about'            => ['@id' => SITE_BASE . '/#organization'],
        'publisher'        => ['@id' => SITE_BASE . '/#organization'],
    ];

    // Speakable + significantLink khusus homepage
    if ($_isHomepageSchema) {
        $webpageSchema['speakable'] = [
            '@type'       => 'SpeakableSpecification',
            'cssSelector' => ['title', 'meta[name="description"]'],
        ];
        $webpageSchema['significantLink'] = [
            SITE_BASE . '/jadwal-misa',
            SITE_BASE . '/agenda',
            SITE_BASE . '/artikel/berita',
            SITE_BASE . '/galeri',
            SITE_BASE . '/kontak',
        ];
        $webpageSchema['specialty'] = 'Gereja Katolik, Jadwal Misa, Paroki Tulungagung';
    }

    if (!empty($breadcrumbs)) {
        $webpageSchema['breadcrumb'] = ['@id' => ($seo['canonical'] ?? SITE_BASE) . '#breadcrumb'];
    }
    $schemas[] = $webpageSchema;
}

// ── versioned() helper lokal jika belum ada ───────────────────────────
if (!function_exists('_vr')) {
    function _vr(string $path): string {
        // Buang query string lama sebelum tambah versi baru
        $cleanPath = strtok($path, '?');
        $file = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/') . '/' . ltrim($cleanPath, '/');
        return $cleanPath . (file_exists($file) ? '?v=' . filemtime($file) : '');
    }
}
$_vfn = function_exists('versioned') ? 'versioned' : '_vr';
?>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta http-equiv="X-UA-Compatible" content="IE=edge">

<!-- SEO core -->
<title><?= $fullTitle ?></title>
<meta name="description" content="<?= $sDesc ?>">
<?php if ($sKw): ?><meta name="keywords" content="<?= $sKw ?>"><?php endif; ?>
<meta name="robots" content="<?= $sRobots ?>">
<meta name="author" content="<?= $sAuthor ?>">
<link rel="canonical" href="<?= $sCanon ?>">
<?php if ($_pageLang === 'en'): ?>
<link rel="alternate" hreflang="en" href="<?= $sCanon ?>">
<link rel="alternate" hreflang="x-default" href="https://www.parokitulungagung.org">
<?php else: ?>
<link rel="alternate" hreflang="id" href="<?= $sCanon ?>">
<?php endif; ?>

<!-- Open Graph -->
<meta property="og:site_name" content="<?= htmlspecialchars(SITE_NAME, ENT_QUOTES) ?>">
<meta property="og:locale" content="<?= $_ogLocale ?>">
<?php if ($_pageLang === 'en'): ?>
<meta property="og:locale:alternate" content="id_ID">
<?php elseif ($sType === 'article'): ?>
<meta property="og:locale:alternate" content="en_US">
<?php endif; ?>
<meta property="og:type" content="<?= $sType ?>">
<meta property="og:title" content="<?= $sTitle ?>">
<meta property="og:description" content="<?= $sDesc ?>">
<meta property="og:image" content="<?= $sImg ?>">
<meta property="og:image:secure_url" content="<?= $sImg ?>">
<meta property="og:image:type" content="<?= $_imgType ?>">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="<?= $sTitle ?>">
<meta property="og:url" content="<?= $sCanon ?>">
<meta property="fb:app_id" content="<?= SITE_FB_APP ?>">
<?php if ($sType === 'article'): ?>
<?php if ($seo['published']): ?><meta property="article:published_time" content="<?= htmlspecialchars($seo['published'], ENT_QUOTES) ?>"><?php endif; ?>
<?php if ($seo['modified'] ?: $seo['published']): ?><meta property="article:modified_time" content="<?= htmlspecialchars($seo['modified'] ?: $seo['published'], ENT_QUOTES) ?>"><?php endif; ?>
<?php if ($seo['author']): ?><meta property="article:author" content="<?= htmlspecialchars(SITE_BASE . '/penulis/' . preg_replace('/[^a-z0-9]+/', '-', strtolower(strip_tags($seo['author']))), ENT_QUOTES) ?>"><?php endif; ?>
<meta property="article:section" content="<?= htmlspecialchars($seo['menu_label'] ?? 'Artikel', ENT_QUOTES) ?>">
<?php foreach (array_slice(explode(',', $seo['keywords'] ?? ''), 0, 6) as $_kw): if (trim($_kw)): ?>
<meta property="article:tag" content="<?= htmlspecialchars(trim($_kw), ENT_QUOTES) ?>">
<?php endif; endforeach; ?>
<?php endif; ?>

<!-- Twitter -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:site" content="@parokitulungagung">
<meta name="twitter:title" content="<?= $sTitle ?>">
<meta name="twitter:description" content="<?= $sDesc ?>">
<meta name="twitter:image" content="<?= $sImg ?>">

  <!-- Favicon -->
  <link rel="icon"          href="/favicon.ico?v=2" type="image/x-icon">
  <link rel="shortcut icon" href="/favicon.ico?v=2">
  <link rel="apple-touch-icon" href="/img/apple-touch-icon.png">
  <link rel="manifest" href="/manifest.webmanifest">

  <!-- PWA / Browser Identity -->
  <meta name="theme-color" content="#1a1208">
  <meta name="application-name" content="Paroki Tulungagung">
  <meta name="apple-mobile-web-app-title" content="Paroki Tulungagung">
  <meta name="mobile-web-app-capable" content="yes">

  <!-- Geo tags — membantu pencarian lokal -->
  <meta name="geo.region" content="ID-JI">
  <meta name="geo.placename" content="Tulungagung, Jawa Timur, Indonesia">
  <meta name="geo.position" content="-8.065658;111.905091">
  <meta name="ICBM" content="-8.065658, 111.905091">

  <style>
<?= readAsset('/css/fonts.css') ?>
<?= readAsset('/css/style.css') ?>
<?php foreach ($extraCss as $css): ?>
<?= readAsset($css) ?>
<?php endforeach; ?>
  </style>



  <?php
  // Preload gambar OG sebagai LCP hint untuk halaman selain homepage
  // (homepage punya preload hero sendiri; artikel-detail punya preload thumbnail sendiri)
  // Ini berlaku untuk halaman galeri, agenda, profil, dsb yang punya custom OG image
  $isHomepage  = ($seo['canonical'] === 'https://www.parokitulungagung.org' || $seo['canonical'] === 'https://www.parokitulungagung.org/');
  $isArticle   = ($sType === 'article');
  if (!$isHomepage && !$isArticle && $seo['image'] !== SITE_OG_IMAGE):
  ?>
  <link rel="preload" as="image" href="<?= htmlspecialchars($seo['image'], ENT_QUOTES, 'UTF-8') ?>" fetchpriority="high">
  <?php endif; ?>

<!-- Preconnect: Google Tag Manager + Supabase (percepat koneksi awal) -->
<link rel="preconnect" href="https://www.googletagmanager.com" crossorigin>
<link rel="dns-prefetch" href="https://www.googletagmanager.com">
<link rel="preconnect" href="<?= defined('SUPABASE_URL') ? rtrim(SUPABASE_URL, '/') : 'https://rkzaathgygfjovrpdlqi.supabase.co' ?>" crossorigin>
<link rel="dns-prefetch" href="<?= defined('SUPABASE_URL') ? rtrim(SUPABASE_URL, '/') : 'https://rkzaathgygfjovrpdlqi.supabase.co' ?>">
<link rel="dns-prefetch" href="https://static.cloudflareinsights.com">
<link rel="dns-prefetch" href="https://challenges.cloudflare.com">

<!-- Google Analytics 4 — dimuat setelah halaman siap agar tidak ganggu LCP -->
<script>
(function() {
  var GA_ID = 'G-DXFR44BFSG';
  function loadGA() {
    if (window._gaLoaded) return;
    window._gaLoaded = true;
    var s = document.createElement('script');
    s.async = true;
    s.src = 'https://www.googletagmanager.com/gtag/js?id=' + GA_ID;
    document.head.appendChild(s);
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    window.gtag = gtag;
    gtag('js', new Date());
    gtag('config', GA_ID, { send_page_view: true });
  }
  // Tunggu browser idle — GA tidak boleh ganggu render & LCP
  if ('requestIdleCallback' in window) {
    requestIdleCallback(loadGA, { timeout: 4000 });
  } else {
    setTimeout(loadGA, 3000);
  }
})();
</script>

<!-- Structured Data -->
<?php foreach ($schemas as $schema): ?>
<script type="application/ld+json">
<?= json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?>
</script>
<?php endforeach; ?>