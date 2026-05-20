<?php

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/SupabaseArticleManager.php';
require_once __DIR__ . '/../includes/artikel-seo-helper.php';   // tersedia jika dibutuhkan extend
require_once __DIR__ . '/../includes/artikel-seo-reader.php';   // tersedia jika dibutuhkan extend


// cache_get() / cache_set() tersedia dari includes/functions.php (persisten di /cache/runtime/)


// ─────────────────────────────────────────────────────────
// IMAGE SEO — preload batch dari tabel image_seo Supabase
// ─────────────────────────────────────────────────────────

/**
 * Fetch semua record image_seo untuk satu artikel sekaligus (1 request).
 * Return: array berindeks image_url → row data.
 * Di-cache 10 menit (artikel lama). Artikel yang baru dipublish (< 2 jam) di-cache 60 detik
 * agar caption & alt_text langsung tersedia setelah SEO generator selesai.
 */
function preloadImageSeoForArticle(string $artikelId, string $publishedAt = ''): array {
    if (!$artikelId) return [];

    $ck = 'image_seo_batch_' . md5($artikelId);
    $cv = cache_get($ck);
    if ($cv !== null) return $cv;

    $url = rtrim(SUPABASE_URL, '/') . '/rest/v1/image_seo'
         . '?artikel_id=eq.' . urlencode($artikelId)
         . '&select=image_url,alt_text,caption,title_attr,schema_description,image_keywords'
         . '&limit=200';

    $headers = [
        'apikey: '        . SUPABASE_ANON_KEY,
        'Authorization: Bearer ' . SUPABASE_ANON_KEY,
        'Accept: application/json',
    ];
    $ctx = stream_context_create([
        'http' => [
            'header'        => implode("\r\n", $headers) . "\r\n",
            'timeout'       => 5,
            'ignore_errors' => true,
        ],
        'ssl'  => ['verify_peer' => true],
    ]);

    $res = @file_get_contents($url, false, $ctx);

    if ($res === false) {
        return [];
    }
    $result = [];

    if ($res) {
        $rows = json_decode($res, true);
        if (is_array($rows)) {
            foreach ($rows as $row) {
                if (!empty($row['image_url'])) {
                    $result[$row['image_url']] = $row;
                }
            }
        }
    }

    // Artikel yang baru dipublish (< 2 jam) → cache singkat agar SEO data segera fresh
    $ttl = 600;
    if ($publishedAt) {
        $age = time() - strtotime($publishedAt);
        if ($age < 7200) $ttl = 60;   // < 2 jam → 60 detik
        elseif ($age < 86400) $ttl = 180; // < 1 hari → 3 menit
    }

    cache_set($ck, $result, $ttl);
    return $result;
}

/**
 * Cari data SEO gambar dari hasil preload.
 * Mendukung partial match (path saja) jika full URL tidak cocok.
 * Return: array row atau null jika tidak ditemukan.
 */
function getSeoFromCache(string $src, array $cache): ?array {
    if (!$src || empty($cache)) return null;

    // 1. Exact match
    if (isset($cache[$src])) return $cache[$src];

    // 2. Match berdasarkan path saja (misal: URL absolut vs relatif)
    $srcPath = parse_url($src, PHP_URL_PATH) ?: $src;
    foreach ($cache as $url => $data) {
        $urlPath = parse_url($url, PHP_URL_PATH) ?: $url;
        if ($srcPath === $urlPath) return $data;
    }

    return null;
}

// ─────────────────────────────────────────────────────────
// AUTHOR PHOTO — cache file 1 jam
// ─────────────────────────────────────────────────────────
function getAuthorPhoto(string $penulis): string {
    if (!$penulis) return '';

    $ck = 'author_photo_' . $penulis;
    $cv = cache_get($ck);
    if ($cv !== null) return $cv;

    $foto = ''; $root = rtrim($_SERVER['DOCUMENT_ROOT'], '/');
    try {
        $url  = rtrim(SUPABASE_URL, '/') . '/rest/v1/users'
              . '?or=(nama.ilike.' . urlencode($penulis) . ',username.ilike.' . urlencode($penulis) . ')'
              . '&select=id&limit=1';
        $hdrs = ['apikey: '.SUPABASE_ANON_KEY, 'Authorization: Bearer '.SUPABASE_ANON_KEY, 'Accept: application/json'];
        $ctx  = stream_context_create(['http'=>['header'=>implode("\r\n",$hdrs)."\r\n",'timeout'=>4,'ignore_errors'=>true],'ssl'=>['verify_peer'=>true]]);
        $res  = @file_get_contents($url, false, $ctx);
        if ($res) {
            $rows = json_decode($res, true);
            if (!empty($rows[0]['id'])) {
                $uid = $rows[0]['id'];
                foreach (['webp','jpg','png'] as $ext) {
                    if (file_exists($root.'/img/admin/profil/profil-'.$uid.'.'.$ext)) {
                        $foto = '/img/admin/profil/profil-'.$uid.'.'.$ext; break;
                    }
                }
            }
        }
    } catch (Throwable $e) {}

    cache_set($ck, $foto, 3600);
    return $foto;
}

function seo_meta_description($text) {
    $text = trim(strip_tags($text));
    $text = preg_replace('/\s+/', ' ', $text);
    if (preg_match('/^(.{120,160}?[.!?])\s/', $text, $m)) {
        return trim($m[1]);
    }
    return mb_substr($text, 0, 155) . '...';
}

function seo_keywords($judul, $tags, $kategori) {
    $base = [
        'gereja katolik tulungagung',
        'paroki smdtba',
        'paroki tulungagung',
        'paroki kabupaten tulungagung'
    ];
    $judulWords = explode(' ', strtolower($judul));
    $judulWords = array_slice($judulWords, 0, 5);
    $tagsArr = is_array($tags) ? $tags : explode(',', $tags);
    return implode(', ', array_unique(array_merge($base, $tagsArr, $judulWords, [$kategori])));
}

function auto_internal_link($html) {
    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    $xpath = new DOMXPath($dom);

    $links = [
        'Santa Maria Dengan Tidak Bernoda Asal Tulungagung' => ['url' => '/',               'title' => 'Paroki Tulungagung'],
        'gereja katolik tulungagung'                        => ['url' => '/',               'title' => 'Gereja Katolik Paroki Tulungagung'],
        'paroki santa maria'                                => ['url' => '/',               'title' => 'Paroki Santa Maria Dengan Tidak Bernoda Asal Tulungagung'],
        'paroki smdtba'                                     => ['url' => '/',               'title' => 'Paroki Tulungagung'],
        'paroki tulungagung'                                => ['url' => '/',               'title' => 'Paroki Tulungagung'],
        'jadwal misa harian'                                => ['url' => '/agenda',         'title' => 'Jadwal Misa Harian Paroki Tulungagung'],
        'jadwal misa'                                       => ['url' => '/agenda',         'title' => 'Jadwal Misa Paroki Tulungagung'],
        'jadwal petugas liturgi'                            => ['url' => '/petugas',        'title' => 'Jadwal Petugas Liturgi Paroki Tulungagung'],
        'jadwal petugas'                                    => ['url' => '/petugas',        'title' => 'Jadwal Petugas Liturgi'],
        'agenda paroki'                                     => ['url' => '/agenda',         'title' => 'Agenda Kegiatan Paroki Tulungagung'],
        'kegiatan paroki'                                   => ['url' => '/agenda',         'title' => 'Agenda Kegiatan Paroki Tulungagung'],
        'foto kegiatan paroki'                              => ['url' => '/galeri',         'title' => 'Galeri Foto Paroki Tulungagung'],
        'foto gereja katolik tulungagung'                   => ['url' => '/galeri',         'title' => 'Foto Gereja Katolik Tulungagung'],
        'galeri foto'                                       => ['url' => '/galeri',         'title' => 'Galeri Foto Kegiatan Paroki Tulungagung'],
        'dokumentasi kegiatan'                              => ['url' => '/galeri',         'title' => 'Galeri Dokumentasi Kegiatan Paroki'],
        'berita paroki'                                     => ['url' => '/artikel/berita', 'title' => 'Berita Terbaru Paroki Tulungagung'],
        'kronik paroki'                                     => ['url' => '/artikel/kronik', 'title' => 'Kronik Paroki Tulungagung'],
        'historia paroki'                                   => ['url' => '/artikel/historia','title' => 'Historia Gereja Katolik Tulungagung'],
        'sejarah gereja'                                    => ['url' => '/artikel/historia','title' => 'Sejarah Gereja Katolik Tulungagung'],
        'sejarah paroki'                                    => ['url' => '/artikel/historia','title' => 'Historia Gereja Katolik Tulungagung'],
        'dewan pastoral paroki'                             => ['url' => '/profil-dpp',     'title' => 'Dewan Pastoral Paroki Tulungagung'],
        'DPP paroki'                                        => ['url' => '/profil-dpp',     'title' => 'DPP Paroki Tulungagung'],
        'asisten imam'                                      => ['url' => '/profil-ai',      'title' => 'Daftar Asisten Imam Paroki Tulungagung'],
        'profil lingkungan'                                 => ['url' => '/profil-lingkungan','title' => 'Profil Lingkungan Paroki Tulungagung'],
        'wilayah dan lingkungan'                            => ['url' => '/profil-lingkungan','title' => 'Wilayah dan Lingkungan Paroki Tulungagung'],
        'kelompok kategorial'                               => ['url' => '/kategorial',     'title' => 'Kelompok Kategorial Paroki Tulungagung'],
        'kategorial paroki'                                 => ['url' => '/kategorial',     'title' => 'Kategorial Paroki Tulungagung'],
        'UMKM umat'                                         => ['url' => '/umkmumat',       'title' => 'UMKM Umat Paroki Tulungagung'],
        'usaha umat'                                        => ['url' => '/umkmumat',       'title' => 'Pasar Umat UMKM Paroki Tulungagung'],
        'warta paroki'                                      => ['url' => '/anyflip',        'title' => 'Warta Digital Paroki Tulungagung'],
        'majalah paroki'                                    => ['url' => '/anyflip',        'title' => 'Majalah Digital Paroki Tulungagung'],
    ];

    $used = [];
    $textNodes = $xpath->query('//text()[not(ancestor::a)]');
    foreach ($textNodes as $node) {
        foreach ($links as $keyword => $linkData) {
            if (isset($used[$keyword])) continue;
            if (stripos($node->nodeValue, $keyword) !== false) {
                $url   = $linkData['url'];
                $title = htmlspecialchars($linkData['title'], ENT_QUOTES);
                $newHTML = preg_replace(
                    '/\b(' . preg_quote($keyword, '/') . ')\b/iu',
                    '<a href="' . $url . '" title="' . $title . '" rel="noopener">$1</a>',
                    $node->nodeValue,
                    1
                );
                $fragment = $dom->createDocumentFragment();
                @$fragment->appendXML($newHTML);
                if ($fragment) {
                    $node->parentNode->replaceChild($fragment, $node);
                    $used[$keyword] = true;
                }
                break;
            }
        }
    }
    return $dom->saveHTML();
}

function auto_heading($content) {
    // Jika sudah ada heading → tidak perlu auto-generate
    if (strpos($content, '<h2') !== false || strpos($content, '<h3') !== false) {
        return $content;
    }

    // Cari paragraf pertama yang layak dijadikan H2:
    // - Panjang antara 20–120 karakter (bukan kalimat panjang, bukan terlalu pendek)
    // - Tidak diakhiri titik di tengah kalimat panjang (hindari jadikan paragraf sebagai heading)
    // - Tidak mengandung tag HTML kompleks di dalamnya
    return preg_replace_callback('/<p>((?:(?!<\/p>).){20,120})<\/p>/s', function($m) {
        $text = strip_tags($m[1]);
        // Skip jika terlalu panjang setelah strip (artinya paragraf isi, bukan judul)
        if (mb_strlen($text) > 120) return $m[0];
        // Skip jika mengandung kalimat lengkap panjang (ada titik di bukan akhir)
        if (preg_match('/\.\s+\w/u', $text)) return $m[0];
        return '<h2>' . $m[1] . '</h2>';
    }, $content, 1);
}

function seo_title($title) {
    return $title;
}

function related_score($current, $candidate) {
    $tagsA  = SupabaseArticleManager::tagsToArray($current['tags']   ?? '');
    $tagsB  = SupabaseArticleManager::tagsToArray($candidate['tags'] ?? '');
    $titleA = strtolower($current['judul']   ?? '');
    $titleB = strtolower($candidate['judul'] ?? '');

    $score = 0;
    $score += count(array_intersect($tagsA, $tagsB)) * 5;
    similar_text($titleA, $titleB, $percent);
    $score += $percent / 10;
    $days = (time() - strtotime($candidate['published_at'] ?? 'now')) / 86400;
    if ($days < 30)       $score += 3;
    elseif ($days < 90)   $score += 2;
    elseif ($days < 180)  $score += 1;

    return $score;
}


$menu = trim($_GET['menu'] ?? '');
$slug = trim($_GET['slug'] ?? '');
if (!in_array($menu, SupabaseArticleManager::MENUS) || !$slug) {
    http_response_code(404); include __DIR__ . '/../error.php'; exit;
}

$am  = new SupabaseArticleManager();
$art = $am->getBySlug($menu, $slug);
if (!$art || ($art['status'] ?? '') !== 'published') {
    http_response_code(404); include __DIR__ . '/../error.php'; exit;
}

$label       = SupabaseArticleManager::MENU_LABELS[$menu];
$artTags     = SupabaseArticleManager::tagsToArray($art['tags'] ?? '');
$tanggal     = SupabaseArticleManager::formatTanggal($art['published_at'] ?? $art['created_at'] ?? '');
$authorPhoto = getAuthorPhoto($art['penulis'] ?? '');
$metaDesc    = seo_meta_description($art['ringkasan'] ?? $art['konten'] ?? '');

$_root = rtrim($_SERVER['DOCUMENT_ROOT'], '/');

$metaImage = 'https://www.parokitulungagung.org/img/ogpreview/default.jpg';

if (!empty($art['thumbnail'])) {
    $_thumbRaw  = $art['thumbnail'];
    $_thumbPath = preg_replace('#^https?://[^/]+#', '', $_thumbRaw);
    $_thumbPath = '/' . ltrim($_thumbPath, '/');
    $_thumbBase = pathinfo($_thumbPath, PATHINFO_FILENAME);
    $_ogRelPath = '/img/ogpreview/' . $_thumbBase . '.jpg';
    $_ogFsPath  = $_root . $_ogRelPath;
    if (file_exists($_ogFsPath)) {
        $metaImage = 'https://www.parokitulungagung.org' . $_ogRelPath;
    } else {
        $metaImage = 'https://www.parokitulungagung.org/img/ogpreview/' . $_thumbBase . '.jpg';
    }
}

$pubDate = !empty($art['published_at']) ? date('c', strtotime($art['published_at'])) : '';
$modDate = !empty($art['updated_at'])   ? date('c', strtotime($art['updated_at']))   : $pubDate;
$listUrl = '/artikel/' . $menu;

$seo = [
    'title'       => seo_title($art['judul']),
    'description' => $metaDesc,
    'canonical'   => 'https://www.parokitulungagung.org/artikel/'.$menu.'/'.rawurlencode($slug),
    'type'        => 'article',
    'image'       => $metaImage,
    'keywords' => seo_keywords(
        $art['judul'] ?? '',
        SupabaseArticleManager::metaKeywords($art['tags'] ?? ''),
        $label),
    'published'   => $pubDate,
    'modified'    => $modDate,
    'author'      => $art['penulis'] ?? '',
    'menu_label'  => $label,  // untuk articleSection di NewsArticle schema
];
// ── Deteksi bahasa artikel ─────────────────────────────────────────
// Deteksi sederhana: cek field 'language' di data, fallback ke heuristic slug/judul
$_artLang = 'id'; // default Indonesia
if (!empty($art['language'])) {
    // Jika tabel punya kolom language (future-proof)
    $_artLang = strtolower(substr($art['language'], 0, 2));
} else {
    // Heuristic: jika slug atau judul dominan kata bahasa Inggris
    $_slugWords  = preg_split('/[\-_]+/', $slug);
    $_titleWords = preg_split('/\s+/', strtolower($art['judul'] ?? ''));
    $_enStopwords = ['the','a','an','of','in','on','at','to','for','and','or','is','was','are','were','with','by','from','that','this','as','be','have','has','had','not','but','pope','bishop','cardinal','vatican','holy','see'];
    $_enHits = 0;
    foreach (array_merge($_slugWords, $_titleWords) as $_w) {
        if (in_array(strtolower($_w), $_enStopwords)) $_enHits++;
    }
    if ($_enHits >= 3) $_artLang = 'en';
}
$seo['lang']     = $_artLang;
$seo['og_locale'] = ($_artLang === 'en') ? 'en_US' : 'id_ID';

$breadcrumbs = [
    ['name' => 'Beranda', 'url' => 'https://www.parokitulungagung.org'],
    ['name' => $label,    'url' => 'https://www.parokitulungagung.org' . $listUrl],
    ['name' => mb_substr($art['judul'] ?? '', 0, 60), 'url' => $seo['canonical']],
];
$extraCss = ['/css/artikel.css'];

// ─────────────────────────────────────────────────────────
// RELATED ARTICLES
// ─────────────────────────────────────────────────────────
$relatedCacheKey = 'related_' . $menu . '_' . ($art['id'] ?? md5($slug));
$related = cache_get($relatedCacheKey);

if ($related === null) {
    $allRelated = array_filter(
        $am->getAll($menu, true, ['id','judul','slug','tags','thumbnail','published_at']),
        fn($a) => $a['id'] !== $art['id']
    );

    foreach ($allRelated as &$item) {
        $item['_score'] = related_score($art, $item);
    }
    unset($item);

    $allRelated = array_values(array_filter($allRelated, fn($a) => $a['_score'] > 2));
    usort($allRelated, fn($a, $b) => $b['_score'] <=> $a['_score']);

    if (!empty($artTags)) {
        $tagMatch = array_filter($allRelated, fn($a) => !empty(array_intersect($artTags, SupabaseArticleManager::tagsToArray($a['tags'] ?? ''))));
        if (count($tagMatch) >= 2) {
            $allRelated = array_values($tagMatch);
            usort($allRelated, function($a, $b) use ($artTags) {
                $sA = count(array_intersect($artTags, SupabaseArticleManager::tagsToArray($a['tags'] ?? '')));
                $sB = count(array_intersect($artTags, SupabaseArticleManager::tagsToArray($b['tags'] ?? '')));
                return $sB - $sA;
            });
        }
    }

    $related = array_slice($allRelated, 0, 3);
    cache_set($relatedCacheKey, $related, 600);
}

// ─────────────────────────────────────────────────────────
// CROSS-CATEGORY CANDIDATES (computed once, used in sidebar)
// ─────────────────────────────────────────────────────────
$crossCandidates = [
    'agenda' => [
        'url'      => '/agenda',
        'label'    => 'Agenda Paroki',
        'desc'     => 'Jadwal misa dan kegiatan terbaru paroki.',
        'icon'     => '📅',
        'keywords' => ['misa','liturgi','sakramen','baptis','perkawinan','krisma','ibadat','doa',
                       'novena','adorasi','perayaan','hari raya','paskah','natal','adven',
                       'prapaskah','agenda','jadwal','kegiatan','acara','lingkungan','wilayah',
                       'kategorial','pertemuan','retret','ziarah','pelayanan','koor','lektor',
                       'putra altar','pramuka'],
        'menus'    => ['berita','kronik'],
        'base'     => 1,
    ],
    'anyflip' => [
        'url'      => '/anyflip',
        'label'    => 'E-Lonceng (Warta Digital)',
        'desc'     => 'Baca warta dan majalah paroki secara online.',
        'icon'     => '📖',
        'keywords' => ['warta','majalah','buletin','baca','lonceng','e-lonceng','digital',
                       'renungan','refleksi','katekese','iman','pewartaan','pastoral','liturgi'],
        'menus'    => ['kronik','historia'],
        'base'     => 0,
    ],
    'historia' => [
        'url'      => '/artikel/historia',
        'label'    => 'Historia',
        'desc'     => 'Sejarah dan historia Gereja Katolik Tulungagung.',
        'icon'     => '⛪',
        'keywords' => ['sejarah','historia','asal usul','berdiri','dibangun','pendiri','misionaris',
                       'belanda','tahun','zaman','masa lalu','dulu','tradisi','warisan','budaya',
                       'adat','situs','cagar','arsitektur','gereja lama','kronik'],
        'menus'    => ['kronik'],
        'base'     => 0,
    ],
    'kronik' => [
        'url'      => '/artikel/kronik',
        'label'    => 'Kronik',
        'desc'     => 'Catatan perjalanan dan kronik kegiatan paroki.',
        'icon'     => '📜',
        'keywords' => ['kronik','catatan','laporan','dokumentasi','peristiwa','perjalanan',
                       'momen','kenangan','rekam','jejak','ulasan'],
        'menus'    => ['berita','historia'],
        'base'     => 0,
    ],
    'berita' => [
        'url'      => '/artikel/berita',
        'label'    => 'Berita Paroki',
        'desc'     => 'Berita & liputan terkini Paroki Tulungagung.',
        'icon'     => '📰',
        'keywords' => ['berita','terkini','liputan','info','informasi','pengumuman','kabar',
                       'update','terbaru'],
        'menus'    => ['kronik','historia'],
        'base'     => 0,
    ],
    'profil_lingkungan' => [
        'url'      => '/profil-lingkungan',
        'label'    => 'Profil Lingkungan',
        'desc'     => 'Profil wilayah dan lingkungan umat paroki.',
        'icon'     => '🏘️',
        'keywords' => ['lingkungan','wilayah','stasi','umat','komunitas','basis','rt','rw',
                       'rukun','ketua','pengurus','pendataan'],
        'menus'    => ['berita','kronik'],
        'base'     => 0,
    ],
    'profil_dpp' => [
        'url'      => '/profil-dpp',
        'label'    => 'DPP Paroki',
        'desc'     => 'Kepengurusan Dewan Pastoral Paroki Tulungagung.',
        'icon'     => '🏛️',
        'keywords' => ['dpp','dewan pastoral','pengurus','kepengurusan','ketua','sekretaris',
                       'bendahara','panitia','koordinator','komisi','bidang','struktur'],
        'menus'    => ['berita'],
        'base'     => 0,
    ],
    'profil_ai' => [
        'url'      => '/profil-ai',
        'label'    => 'Asisten Imam',
        'desc'     => 'Daftar Asisten Imam Paroki Tulungagung.',
        'icon'     => '✝️',
        'keywords' => ['romo','pastor','imam','diakon','bruder','suster','frater','klerus',
                       'tahbisan','pembimbing','pelayan'],
        'menus'    => ['berita','kronik','historia'],
        'base'     => 0,
    ],
    'kategorial' => [
        'url'      => '/kategorial',
        'label'    => 'Kategorial',
        'desc'     => 'Kelompok kategorial umat Paroki Tulungagung.',
        'icon'     => '👥',
        'keywords' => ['kategorial','mudika','wanita katolik','lansia','keluarga','pria',
                       'orang muda','pemuda','wkri','ikatan','kelompok','komunitas','ormas',
                       'organisasi','legio','cursillo','taize','karismatik'],
        'menus'    => ['berita','kronik'],
        'base'     => 0,
    ],
];

$artTagsLower  = array_map('strtolower', $artTags);
$artTitleWords = array_filter(
    preg_split('/[\s,.\-\/]+/', strtolower($art['judul'] ?? '')),
    fn($w) => strlen($w) > 3
);
$artSignals = array_unique(array_merge($artTagsLower, $artTitleWords));

$dynamicScored = [];
foreach ($crossCandidates as $key => $cand) {
    if ($cand['url'] === '/artikel/' . $menu) continue;
    $score = $cand['base'];
    foreach ($cand['keywords'] as $kw) {
        foreach ($artSignals as $sig) {
            if (strpos($sig, $kw) !== false || strpos($kw, $sig) !== false) {
                $score += 3; break;
            }
        }
    }
    if (in_array($menu, $cand['menus'], true)) $score += 2;
    $dynamicScored[$key] = ['data' => $cand, 'score' => $score];
}
uasort($dynamicScored, fn($a, $b) => $b['score'] <=> $a['score']);
$dynamicSlots = array_slice(array_column($dynamicScored, 'data'), 0, 3);

// Reading time estimate
$wordCount   = str_word_count(strip_tags($art['konten'] ?? ''));
$readMinutes = max(1, (int) ceil($wordCount / 200));

// ── Preload SEO gambar dari tabel image_seo (1 request batch) ──────────
$imageSeoCache = [];
if (!empty($art['id'])) {
    $imageSeoCache = preloadImageSeoForArticle(
        (string) $art['id'],
        $art['published_at'] ?? $art['created_at'] ?? ''
    );
}

?>
<!doctype html>
<html lang="<?= $seo['lang'] ?? 'id' ?>">
<head>
  <?php
  // ── Pass imagesSchema ke seo_head via global sebelum include ─────────
  // seo_head.php akan membaca ini untuk memperkaya NewsArticle image array.
  // Pada titik ini $imagesSchema masih kosong (belum diproses konten),
  // sehingga kita set placeholder dulu; yang final diupdate setelah body render.
  // Untuk SEO schema yang benar, kita gunakan minimal thumbnail yang sudah tersedia.
  $GLOBALS['_seo_images_schema'] = []; // akan diisi setelah konten diproses
  ?>
  <?php include __DIR__ . '/../components/seo_head.php'; ?>

  <?php
  // ── WebPage schema — terhubung ke NewsArticle & Organization via @id ─
  // Organization dan WebSite sudah di-output oleh seo_head.php,
  // jadi di sini hanya WebPage yang dibutuhkan untuk melengkapi @graph.
  $webPageSchema = [
    '@context'           => 'https://schema.org',
    '@type'              => 'WebPage',
    '@id'                => 'https://www.parokitulungagung.org/artikel/' . $menu . '/' . rawurlencode($slug) . '#webpage',
    'url'                => 'https://www.parokitulungagung.org/artikel/' . $menu . '/' . rawurlencode($slug),
    'name'               => htmlspecialchars_decode($seo['title'] ?? ''),
    'description'        => htmlspecialchars_decode($seo['description'] ?? ''),
    'inLanguage'         => 'id',
    'isPartOf'           => ['@id' => 'https://www.parokitulungagung.org/#website'],
    'about'              => ['@id' => 'https://www.parokitulungagung.org/#organization'],
    'breadcrumb'         => ['@id' => 'https://www.parokitulungagung.org/artikel/' . $menu . '/' . rawurlencode($slug) . '#breadcrumb'],
    'primaryImageOfPage' => [
        '@type'      => 'ImageObject',
        '@id'        => $metaImage . '#primaryimage',
        'contentUrl' => $metaImage,
        'url'        => $metaImage,
    ],
    'datePublished'      => $pubDate,
    'dateModified'       => $modDate,
  ];
  ?>
  <script type="application/ld+json">
  <?= json_encode($webPageSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?>
  </script>

  <!-- Preload thumbnail artikel sebagai LCP — gambar ini tampil pertama kali, harus cepat -->
  <?php if (!empty($art['thumbnail'])): ?>
  <link rel="preload" as="image" href="<?= htmlspecialchars($art['thumbnail'], ENT_QUOTES, 'UTF-8') ?>"
        fetchpriority="high" imagesizes="(min-width: 900px) 800px, 100vw">
  <?php endif; ?>

  <style>
.art-three-col{display:block}.art-sidebar-left{display:none}.art-sidebar-right{display:none}.art-below-article{display:block}@media (min-width:1100px){.art-detail-main{max-width:100% !important;padding:0 !important}.art-three-col{display:grid;grid-template-columns:220px 1fr 260px;grid-template-areas:"left center right";gap:0;align-items:start;max-width:1360px;margin:0 auto;padding:0 1rem}.art-sidebar-left{grid-area:left}.art-center-col{grid-area:center}.art-sidebar-right{grid-area:right}.art-sidebar-left,.art-sidebar-right{display:block}.art-sidebar-left-inner,.art-sidebar-right-inner{position:sticky;top:80px;max-height:calc(100vh - 100px);overflow-y:auto;scrollbar-width:thin;scrollbar-color:rgba(184,134,11,.3) transparent}.art-detail-meta{display:none !important}.art-below-article{display:none}.art-breadcrumb-wrap{max-width:1360px;margin:0 auto;padding:0 1rem}}.art-sidebar-left-inner{padding:1.25rem 1rem 1.25rem 0}.art-author-card{background:#fffdf8;border:1px solid rgba(184,134,11,.18);border-radius:14px;padding:1.25rem 1rem;text-align:center;box-shadow:0 2px 12px rgba(184,134,11,.07)}.art-author-card-photo{width:72px;height:72px;border-radius:50%;object-fit:cover;border:3px solid rgba(184,134,11,.25);margin:0 auto .75rem;display:block}.art-author-card-initials{width:72px;height:72px;border-radius:50%;background:linear-gradient(135deg,#b8860b 0%,#e6b800 100%);color:#fff;font-size:28px;font-weight:700;display:flex;align-items:center;justify-content:center;margin:0 auto .75rem;font-family:'Montserrat',sans-serif;letter-spacing:.5px}.art-author-card-name{font-size:14px;font-weight:700;color:#3d2e1a;font-family:'Montserrat',sans-serif;margin-bottom:2px}.art-author-card-role{font-size:11px;color:#9a836a;text-transform:uppercase;letter-spacing:.6px;margin-bottom:1rem}.art-author-card-divider{border:none;border-top:1px solid rgba(184,134,11,.15);margin:.85rem 0}.art-author-card-meta{display:flex;flex-direction:column;gap:.55rem;text-align:left}.art-author-card-meta-row{display:flex;align-items:flex-start;gap:.5rem}.art-author-card-meta-icon{font-size:13px;flex-shrink:0;margin-top:1px;opacity:.7}.art-author-card-meta-label{font-size:10px;color:#9a836a;text-transform:uppercase;letter-spacing:.5px;line-height:1.2}.art-author-card-meta-value{font-size:12px;color:#4a3828;font-weight:600;line-height:1.3;font-family:'Montserrat',sans-serif}.art-author-card-category{display:inline-block;background:rgba(184,134,11,.12);color:#7a5c00;font-size:11px;font-weight:700;padding:3px 9px;border-radius:20px;border:1px solid rgba(184,134,11,.2);font-family:'Montserrat',sans-serif;letter-spacing:.3px}.art-author-card-readtime{display:flex;align-items:center;gap:4px;font-size:11px;color:#9a836a;justify-content:center;margin-top:.5rem}.art-author-card-back{display:block;margin-top:1rem;padding:8px 12px;border-radius:8px;background:rgba(184,134,11,.08);border:1px solid rgba(184,134,11,.2);color:#7a5c00;font-size:12px;font-weight:600;text-decoration:none;text-align:center;font-family:'Montserrat',sans-serif;transition:background .18s,border-color .18s}.art-author-card-back:hover{background:rgba(184,134,11,.15);border-color:rgba(184,134,11,.35)}.art-author-card-views{display:flex;align-items:center;gap:5px;font-size:11px;color:#9a836a;justify-content:center;margin-top:.4rem;padding:5px 10px;background:rgba(184,134,11,.06);border:1px solid rgba(184,134,11,.12);border-radius:8px}.art-views-icon{font-size:13px}.art-views-label strong{color:#7a5c00;font-weight:700}.art-view-badge{display:inline-flex;align-items:center;gap:5px;font-size:11px;color:#9a836a;background:rgba(184,134,11,.07);border:1px solid rgba(184,134,11,.15);border-radius:20px;padding:3px 11px 3px 8px;margin-bottom:.5rem;font-family:'Montserrat',sans-serif}.art-view-badge svg{opacity:.7;flex-shrink:0}@media (min-width:1100px){.art-view-badge{display:none !important}}.art-sidebar-right-inner{padding:1.25rem 0 1.25rem 1rem}.art-sidebar-related-title{font-size:12px;font-weight:700;color:#6b5a3e;margin:0 0 .75rem;text-transform:uppercase;letter-spacing:.5px;font-family:'Montserrat',sans-serif;display:flex;align-items:center;gap:6px}.art-sidebar-related-title::before{content:'';display:inline-block;width:3px;height:13px;background:#b8860b;border-radius:2px;flex-shrink:0}.art-sidebar-related-list{display:flex;flex-direction:column;gap:10px;margin-bottom:1.5rem}.art-sidebar-related-card{display:flex;gap:10px;align-items:flex-start;text-decoration:none;padding:9px 10px;border-radius:10px;background:rgba(184,134,11,.04);border:1px solid rgba(184,134,11,.1);transition:background .18s,border-color .18s,box-shadow .18s}.art-sidebar-related-card:hover{background:rgba(184,134,11,.1);border-color:rgba(184,134,11,.28);box-shadow:0 2px 8px rgba(184,134,11,.1)}.art-sidebar-related-thumb{width:54px;height:54px;object-fit:cover;border-radius:7px;flex-shrink:0;background:#f0e8d8}.art-sidebar-related-thumb-placeholder{width:54px;height:54px;border-radius:7px;background:rgba(184,134,11,.1);display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0}.art-sidebar-related-info{flex:1;min-width:0}.art-sidebar-related-card-title{font-size:12px;font-weight:600;color:#3d2e1a;line-height:1.4;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden;font-family:'Montserrat',sans-serif}.art-sidebar-related-date{font-size:10px;color:#9a836a;margin-top:4px}.art-sidebar-crosslink-title{font-size:12px;font-weight:700;color:#6b5a3e;margin:0 0 .75rem;text-transform:uppercase;letter-spacing:.5px;font-family:'Montserrat',sans-serif;display:flex;align-items:center;gap:6px}.art-sidebar-crosslink-title::before{content:'';display:inline-block;width:3px;height:13px;background:#b8860b;border-radius:2px;flex-shrink:0}.art-sidebar-crosslink-list{display:flex;flex-direction:column;gap:7px}.art-sidebar-crosslink-card{display:flex;align-items:center;gap:10px;padding:9px 10px;border-radius:9px;text-decoration:none;background:rgba(184,134,11,.04);border:1px solid rgba(184,134,11,.1);transition:background .18s,border-color .18s,box-shadow .18s}.art-sidebar-crosslink-card:hover{background:rgba(184,134,11,.1);border-color:rgba(184,134,11,.28);box-shadow:0 2px 8px rgba(184,134,11,.1)}.art-sidebar-crosslink-icon{font-size:18px;flex-shrink:0}.art-sidebar-crosslink-info{flex:1;min-width:0}.art-sidebar-crosslink-label{font-size:12px;font-weight:600;color:#3d2e1a;font-family:'Montserrat',sans-serif;line-height:1.3}.art-sidebar-crosslink-desc{font-size:10px;color:#7a6b52;margin-top:1px;line-height:1.3}.art-sidebar-crosslink-arrow{color:#b8860b;flex-shrink:0}@media (min-width:1100px){.art-center-col{padding:0 1.5rem;border-left:1px solid rgba(184,134,11,.1);border-right:1px solid rgba(184,134,11,.1);min-width:0}}.art-crosslink{margin:2rem 0 1rem}.art-crosslink-title{font-size:14px;font-weight:700;color:#6b5a3e;margin:0 0 .8rem;text-transform:uppercase;letter-spacing:.5px;font-family:'Montserrat',sans-serif}.art-crosslink-grid{display:flex;flex-direction:column;gap:8px}.art-crosslink-card{display:flex;align-items:center;gap:12px;padding:11px 14px;border-radius:10px;text-decoration:none;background:rgba(184,134,11,.05);border:1px solid rgba(184,134,11,.15);transition:background .18s,border-color .18s,box-shadow .18s}.art-crosslink-card:hover{background:rgba(184,134,11,.1);border-color:rgba(184,134,11,.3);box-shadow:0 2px 8px rgba(184,134,11,.1)}.art-crosslink-icon{font-size:20px;flex-shrink:0}.art-crosslink-info{flex:1;min-width:0}.art-crosslink-label{font-size:13px;font-weight:600;color:#3d2e1a;font-family:'Montserrat',sans-serif}.art-crosslink-desc{font-size:11px;color:#7a6b52;margin-top:1px}.art-crosslink-arrow{color:#b8860b;flex-shrink:0}
/* ── Tags di footer artikel ─────────────────────────── */
.art-detail-tags--footer {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 6px;
  padding-top: 16px;
  margin-top: 4px;
  border-top: 1px solid rgba(0, 0, 0, 0.07);
  width: 100%;
}

.art-detail-tags-label {
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: #999;
  margin-right: 2px;
  flex-shrink: 0;
}

/* Pastikan footer bisa wrap ke baris baru (tags full-width di bawah share) */
.art-detail-footer {
  flex-wrap: wrap;
}

/* Tag chip — sedikit lebih subtle di posisi footer */
.art-detail-tags--footer .art-detail-tag {
  font-size: 11.5px;
  padding: 3px 10px;
  border-radius: 20px;
  background: rgba(0, 0, 0, 0.05);
  color: #666;
  text-decoration: none;
  transition: background 0.15s, color 0.15s;
  border: 1px solid rgba(0, 0, 0, 0.08);
}

.art-detail-tags--footer .art-detail-tag:hover {
  background: rgba(0, 0, 0, 0.1);
  color: #333;
}

@media (max-width: 600px) {
  .art-detail-tags--footer {
    padding-top: 14px;
    gap: 5px;
  }
  .art-detail-tags--footer .art-detail-tag {
    font-size: 11px;
    padding: 3px 9px;
  }
}
</style>
</head>
<body>
<?php $headerTitle = ''; include __DIR__ . '/../components/page_header.php'; ?>

  <main class="art-detail-main">

    <!-- Breadcrumb: full-width above the 3-col grid -->
    <div class="art-breadcrumb-wrap">
      <nav class="art-breadcrumb" aria-label="Breadcrumb">
        <a href="/">Beranda</a><span>›</span>
        <a href="<?= $listUrl ?>"><?= e($label) ?></a><span>›</span>
        <span><?= e(mb_substr($art['judul'] ?? '', 0, 40)) ?>…</span>
      </nav>
    </div>

    <!-- Three-column grid wrapper -->
    <div class="art-three-col">

      <!-- ═══════════════════════════════════════════
           LEFT SIDEBAR — Author card (desktop only)
      ═══════════════════════════════════════════ -->
      <aside class="art-sidebar-left" aria-label="Informasi penulis">
        <div class="art-sidebar-left-inner">
          <div class="art-author-card" itemscope itemtype="https://schema.org/Person">

            <?php if (!empty($art['penulis'])): ?>
              <?php if ($authorPhoto): ?>
              <img src="<?= e($authorPhoto) ?>"
                   alt="Foto <?= e($art['penulis']) ?>"
                   class="art-author-card-photo"
                   width="72" height="72" loading="lazy"
                   onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
              <div class="art-author-card-initials" style="display:none"><?= e(strtoupper(substr($art['penulis'],0,1))) ?></div>
              <?php else: ?>
              <div class="art-author-card-initials"><?= e(strtoupper(substr($art['penulis'],0,1))) ?></div>
              <?php endif; ?>

              <?php
              // FIX #4: Buat slug penulis untuk link ke halaman profil /penulis/{slug}
              $penulisSlug = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower(strip_tags($art['penulis']))), '-');
              ?>
              <a href="/penulis/<?= e($penulisSlug) ?>"
                 itemprop="url"
                 style="text-decoration:none;color:inherit;">
                <div class="art-author-card-name" itemprop="name"><?= e($art['penulis']) ?></div>
              </a>
              <div class="art-author-card-role">Penulis Artikel</div>
            <?php else: ?>
              <div class="art-author-card-initials">✝</div>
              <div class="art-author-card-name">Tim Redaksi</div>
              <div class="art-author-card-role">Paroki Tulungagung</div>
            <?php endif; ?>

            <hr class="art-author-card-divider">

            <div class="art-author-card-meta">

              <?php if ($tanggal): ?>
              <div class="art-author-card-meta-row">
                <span class="art-author-card-meta-icon">📅</span>
                <div>
                  <div class="art-author-card-meta-label">Tanggal Terbit</div>
                  <div class="art-author-card-meta-value">
                    <time datetime="<?= e($pubDate) ?>"><?= e($tanggal) ?></time>
                  </div>
                </div>
              </div>
              <?php endif; ?>

              <div class="art-author-card-meta-row">
                <span class="art-author-card-meta-icon">📂</span>
                <div>
                  <div class="art-author-card-meta-label">Kategori</div>
                  <div class="art-author-card-meta-value">
                    <a href="<?= $listUrl ?>" class="art-author-card-category"><?= e($label) ?></a>
                  </div>
                </div>
              </div>


            </div><!-- /.art-author-card-meta -->

            <div class="art-author-card-readtime">
              <span>⏱</span>
              <span>Estimasi baca: <strong><?= $readMinutes ?> menit</strong></span>
            </div>

            <!-- View count indicator (desktop sidebar) -->
            <div class="art-author-card-views" id="sidebar-view-count" style="display:none">
              <span class="art-views-icon">👁</span>
              <span class="art-views-label">Dilihat <strong id="sidebar-vc-num">–</strong> kali</span>
            </div>

            <a href="<?= $listUrl ?>" class="art-author-card-back">
              ← Kembali ke <?= e($label) ?>
            </a>

          </div><!-- /.art-author-card -->
        </div>
      </aside><!-- /.art-sidebar-left -->


      <!-- ═══════════════════════════════════════════
           CENTER — Article content
      ═══════════════════════════════════════════ -->
      <div class="art-center-col">

        <article class="art-detail-article" itemscope itemtype="https://schema.org/NewsArticle">
          <meta itemprop="datePublished" content="<?= e($pubDate) ?>">
          <meta itemprop="dateModified"  content="<?= e($modDate) ?>">
          <meta itemprop="image"         content="<?= e($metaImage) ?>">
          <meta itemprop="wordCount" content="<?= $wordCount ?>">
          <meta itemprop="timeRequired" content="PT<?= $readMinutes ?>M">

          <header class="art-detail-header">
            <div class="art-detail-menu-badge"><?= e($label) ?></div>
            <h1 class="art-detail-title" itemprop="headline"><?= e($art['judul'] ?? '') ?></h1>

            <?php if (!empty($art['ringkasan'])): ?>
            <p class="art-detail-lead" itemprop="description"><?= e($art['ringkasan']) ?></p>
            <?php endif; ?>


            <!-- View count badge (mobile / tampil di semua ukuran sebelum meta) -->
            <div class="art-view-badge" id="mobile-view-count" style="display:none" aria-label="Jumlah tampilan artikel">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              <span id="mobile-vc-num">–</span> kali dilihat
            </div>


            <div class="art-detail-meta">
              <?php if (!empty($art['penulis'])): ?>
              <div class="art-detail-meta-item" itemprop="author" itemscope itemtype="https://schema.org/Person">
                <?php if ($authorPhoto): ?>
                <img src="<?= e($authorPhoto) ?>" alt="<?= e($art['penulis']) ?>"
                     class="art-detail-avatar" style="object-fit:cover;border:2px solid rgba(86,73,56,.15)"
                     width="34" height="34" loading="lazy"
                     onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                <div class="art-detail-avatar" style="display:none"><?= e(strtoupper(substr($art['penulis'],0,1))) ?></div>
                <?php else: ?>
                <div class="art-detail-avatar"><?= e(strtoupper(substr($art['penulis'],0,1))) ?></div>
                <?php endif; ?>
                <div>
                  <div class="art-detail-meta-name" itemprop="name"><?= e($art['penulis']) ?></div>
                  <div class="art-detail-meta-role">Penulis</div>
                </div>
              </div>
              <?php endif; ?>
              <?php if ($tanggal): ?>
              <div class="art-detail-meta-item" style="margin-left:auto;flex-direction:column;align-items:flex-end">
                <div class="art-detail-meta-name">
                  <time datetime="<?= e($pubDate) ?>"><?= e($tanggal) ?></time>
                </div>
                <div class="art-detail-meta-role">Tanggal Publikasi</div>
              </div>
              <?php endif; ?>
            </div>
          </header>

          <?php
          // ── Inisialisasi array schema gambar (thumbnail masuk pertama jika ada) ──
          $imagesSchema = [];
          ?>

          <?php if (!empty($art['thumbnail'])): ?>
          <?php
          // SEO thumbnail — ambil dari tabel image_seo, fallback ke judul artikel
          $thumbSeo      = getSeoFromCache($art['thumbnail'], $imageSeoCache);
          $thumbAlt      = !empty($thumbSeo['alt_text'])          ? $thumbSeo['alt_text']          : ($art['judul'] ?? '');
          $thumbTitle    = !empty($thumbSeo['title_attr'])        ? $thumbSeo['title_attr']         : $thumbAlt;
          $thumbCaption  = !empty($thumbSeo['caption'])           ? $thumbSeo['caption']            : '';
          $thumbSchemaDesc = !empty($thumbSeo['schema_description']) ? $thumbSeo['schema_description'] : '';
          $thumbKeywords = $thumbSeo['image_keywords'] ?? [];
          if (is_string($thumbKeywords)) $thumbKeywords = json_decode($thumbKeywords, true) ?: [];
          // Masukkan thumbnail ke imagesSchema (posisi pertama = primary image)
          $thumbSchemaEntry = [
              '@type'      => 'ImageObject',
              'contentUrl' => $art['thumbnail'],
              'name'       => $thumbTitle,
              'caption'    => $thumbCaption ?: $thumbAlt,
          ];
          if ($thumbSchemaDesc) $thumbSchemaEntry['description'] = $thumbSchemaDesc;
          if (!empty($thumbKeywords)) $thumbSchemaEntry['keywords'] = implode(', ', array_map('strval', (array)$thumbKeywords));
          $imagesSchema[] = $thumbSchemaEntry;
          ?>
          <figure class="art-detail-thumb-wrap"<?= $thumbSchemaDesc ? ' itemscope itemtype="https://schema.org/ImageObject"' : '' ?>>
            <?php if ($thumbSchemaDesc): ?>
            <meta itemprop="description" content="<?= e($thumbSchemaDesc) ?>">
            <?php endif; ?>
            <?php if ($thumbTitle): ?>
            <meta itemprop="name" content="<?= e($thumbTitle) ?>">
            <?php endif; ?>
            <?php if (!empty($thumbKeywords)): ?>
            <meta itemprop="keywords" content="<?= e(implode(', ', array_map('strval', (array)$thumbKeywords))) ?>">
            <?php endif; ?>
            <img src="<?= e($art['thumbnail']) ?>"
                 alt="<?= e($thumbAlt) ?>"
                 title="<?= e($thumbTitle) ?>"
                 class="art-detail-thumb" itemprop="image" loading="eager" fetchpriority="high" decoding="async" width="800" height="450">
            <?php if ($thumbCaption): ?>
            <figcaption class="art-detail-thumb-caption" itemprop="caption"><?= e($thumbCaption) ?></figcaption>
            <?php endif; ?>
          </figure>
          <?php endif; ?>

          <div class="art-detail-content" itemprop="articleBody">
          <?php

          $konten = $art['konten'] ?? '';
          $konten = auto_heading($konten);
          $konten = auto_internal_link($konten);

          // $imagesSchema sudah diinisialisasi di atas (thumbnail masuk lebih dulu jika ada)

          function groupImgFiguresToRow(string $html): string {
              if (trim($html) === '') return $html;

              libxml_use_internal_errors(true);
              $doc = new DOMDocument('1.0', 'utf-8');

              // PHP 8.2+: mb_convert_encoding ke HTML-ENTITIES sudah deprecated.
              // Solusi: pakai XML encoding declaration agar DOMDocument bisa parsing
              // UTF-8 dengan benar tanpa konversi entity.
              $wrapped = '<?xml encoding="utf-8"?><div id="__artroot__">' . $html . '</div>';
              $doc->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING);

              $root = $doc->getElementById('__artroot__');
              if (!$root) return $html;

              $isFig = static function ($node): bool {
                  return $node instanceof DOMElement && $node->nodeName === 'figure' && str_contains($node->getAttribute('class'), 'img-figure');
              };

              $isSkippable = static function ($node): bool {
                  if ($node instanceof DOMText) return trim($node->nodeValue) === '';
                  if ($node instanceof DOMElement && $node->nodeName === 'p') {
                      return preg_replace('/[\s\x{00A0}]+/u', '', $node->textContent) === '';
                  }
                  return false;
              };

              $changed = true;
              while ($changed) {
                  $changed   = false;
                  $childList = iterator_to_array($root->childNodes, false);
                  $total     = count($childList);

                  for ($i = 0; $i < $total; $i++) {
                      if (!$isFig($childList[$i])) continue;
                      $run = [$childList[$i]]; $skipped = []; $k = $i + 1;
                      while ($k < $total) {
                          $cur = $childList[$k];
                          if ($isSkippable($cur))  { $skipped[] = $cur; $k++; continue; }
                          if ($isFig($cur))        { $run[] = $cur; $skipped = []; $k++; continue; }
                          break;
                      }
                      if (count($run) < 2) {
                          // ── Gambar tunggal: tambahkan caption visible di bawah figure ──
                          $singleFig = $run[0];
                          $singleCap = trim($singleFig->getAttribute('data-caption'));
                          if ($singleCap) {
                              $capDiv = $doc->createElement('div');
                              $capDiv->setAttribute('class', 'image-row-caption');
                              $capDiv->setAttribute('role', 'note');
                              $capDiv->setAttribute('aria-label', 'Keterangan gambar: ' . $singleCap);
                              $capDiv->nodeValue = $singleCap;
                              $nextSib = $singleFig->nextSibling;
                              if ($nextSib) {
                                  $root->insertBefore($capDiv, $nextSib);
                              } else {
                                  $root->appendChild($capDiv);
                              }
                              $changed = true; break;
                          }
                          continue;
                      }
                      $row = $doc->createElement('div');
                      $row->setAttribute('class', 'image-row');
                      $root->insertBefore($row, $childList[$i]);
                      // ── Kumpulkan caption & bangun img-wraps ─────────────────────
                      $allCaptions = [];
                      foreach ($run as $fig) {
                          // Ambil caption dari data-caption (untuk caption gabungan)
                          $capAttr = trim($fig->getAttribute('data-caption'));
                          if ($capAttr) $allCaptions[] = $capAttr;

                          $existingWrap = null;
                          foreach ($fig->childNodes as $fc) {
                              if ($fc instanceof DOMElement && $fc->nodeName === 'div' && str_contains($fc->getAttribute('class'), 'img-wrap')) {
                                  $existingWrap = $fc; break;
                              }
                          }
                          if ($existingWrap) {
                              // Sembunyikan figcaption individual → tetap di DOM untuk SEO
                              foreach (iterator_to_array($existingWrap->childNodes, false) as $wc) {
                                  if ($wc instanceof DOMElement && $wc->nodeName === 'figcaption') {
                                      $wc->setAttribute('class', trim($wc->getAttribute('class') . ' img-caption-hidden'));
                                  }
                              }
                              $row->appendChild($existingWrap);
                          } else {
                              $wrap = $doc->createElement('div');
                              $wrap->setAttribute('class', 'img-wrap');
                              foreach (iterator_to_array($fig->childNodes, false) as $child) {
                                  // Sembunyikan figcaption individual → tetap di DOM untuk SEO
                                  if ($child instanceof DOMElement && $child->nodeName === 'figcaption') {
                                      $child->setAttribute('class', trim($child->getAttribute('class') . ' img-caption-hidden'));
                                  }
                                  $wrap->appendChild($child);
                              }
                              $row->appendChild($wrap);
                          }
                          $root->removeChild($fig);
                      }

                      // ── Caption gabungan di LUAR container gambar (.image-row) ─
                      // Di-insert sebagai sibling setelah $row di $root,
                      // bukan sebagai child di dalam flex container.
                      // Visually-hidden (lihat CSS .image-row-caption) → tetap ada di DOM untuk SEO.
                      if (!empty($allCaptions)) {
                          $capDiv = $doc->createElement('div');
                          $capDiv->setAttribute('class', 'image-row-caption');
                          $capDiv->setAttribute('role', 'note');
                          $capDiv->setAttribute('aria-label', 'Keterangan gambar: ' . $allCaptions[0]);
                          $capDiv->nodeValue = $allCaptions[0];
                          // Insert setelah $row (bukan di dalam $row)
                          $nextSibling = $row->nextSibling;
                          if ($nextSibling) {
                              $root->insertBefore($capDiv, $nextSibling);
                          } else {
                              $root->appendChild($capDiv);
                          }
                      }

                      foreach ($skipped as $sk) {
                          if ($sk->parentNode === $root) $root->removeChild($sk);
                      }
                      $changed = true; break;
                  }
              }

              $out = '';
              foreach ($root->childNodes as $child) $out .= $doc->saveHTML($child);
              return $out ?: $html;
          }

          $konten = preg_replace_callback('/<img([^>]*)>/i', function($match) use (&$imagesSchema, $imageSeoCache) {
              $imgTag = $match[0];
              preg_match('/src\s*=\s*["\'](.*?)["\']/', $imgTag, $srcMatch);
              $src = $srcMatch[1] ?? '';

              // ── Ambil data SEO dari tabel image_seo (read-only) ──────
              $seoData     = $src ? getSeoFromCache($src, $imageSeoCache) : null;
              $altText     = trim($seoData['alt_text']           ?? '');
              $titleAttr   = trim($seoData['title_attr']         ?? '');
              $captionText = trim($seoData['caption']            ?? '');
              $schemaDesc  = trim($seoData['schema_description'] ?? '');
              $kw          = $seoData['image_keywords'] ?? [];
              $imgKeywords = is_array($kw) ? $kw : (json_decode((string)$kw, true) ?: []);

              // ── Alt text: hapus alt lama di HTML, inject dari tabel ───
              $imgTag = preg_replace('/\s*\balt\s*=\s*["\'][^"\']*["\']/', '', $imgTag, 1);
              $imgTag = str_replace('<img', '<img alt="' . htmlspecialchars($altText, ENT_QUOTES) . '"', $imgTag);
              // ── Title attribute ────────────────────────────────────
              $titleVal = $titleAttr ?: $altText;
              if (!preg_match('/\btitle\s*=\s*["\'].*?["\']/', $imgTag)) {
                  $imgTag = str_replace('<img', '<img title="' . htmlspecialchars($titleVal, ENT_QUOTES) . '"', $imgTag);
              } elseif ($titleAttr) {
                  // Update title dari tabel jika sudah ada di HTML
                  $imgTag = preg_replace('/\btitle\s*=\s*["\'].*?["\']/', 'title="' . htmlspecialchars($titleAttr, ENT_QUOTES) . '"', $imgTag, 1);
              }

              // ── Caption
              $captionVal = $captionText ?: $altText;

              // ── Schema ImageObject (dari tabel image_seo)
              if (!empty($src)) {
                  $schemaEntry = [
                      '@type'      => 'ImageObject',
                      'contentUrl' => $src,
                      'name'       => $titleVal,
                      'caption'    => $captionVal,
                  ];
                  if ($schemaDesc) {
                      $schemaEntry['description'] = $schemaDesc;
                  }
                  if (!empty($imgKeywords)) {
                      $schemaEntry['keywords'] = implode(', ', array_map('strval', (array) $imgKeywords));
                  }
                  $imagesSchema[] = $schemaEntry;
              }

              // ── Render <figure> dengan caption untuk SEO ────────────────────
              // figcaption disembunyikan secara visual (SEO only), kecuali untuk
              // gambar tunggal yang tidak masuk image-row → tampilkan caption di bawah.
              $figcaptionSeo = $captionVal
                  ? '<figcaption class="img-caption-hidden" aria-hidden="true">'
                    . htmlspecialchars($captionVal, ENT_QUOTES) . '</figcaption>'
                  : '';

              return '<figure class="img-figure" data-caption="' . htmlspecialchars($captionVal, ENT_QUOTES) . '">'
                   . $imgTag
                   . $figcaptionSeo
                   . '</figure>';
          }, $konten);

          $konten = groupImgFiguresToRow($konten);
          echo $konten;
          ?>
          </div><!-- /.art-detail-content -->

          <footer class="art-detail-footer">
            <a href="<?= $listUrl ?>" class="art-back-btn">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><polyline points="15 18 9 12 15 6"/></svg>
              Kembali ke <?= e($label) ?>
            </a>
            <div class="art-share">
              <span style="font-size:12px;color:#888">Bagikan:</span>
              <a href="https://wa.me/?text=<?= urlencode(($art['judul']??'').' https://www.parokitulungagung.org/artikel/'.$menu.'/'.$slug) ?>"
                 target="_blank" rel="noopener" class="art-share-btn" title="Bagikan ke WhatsApp">
                <svg viewBox="0 0 24 24" fill="#25d366" width="18" height="18" xmlns="http://www.w3.org/2000/svg"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
              </a>
              <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode('https://www.parokitulungagung.org/artikel/'.$menu.'/'.$slug) ?>"
                 target="_blank" rel="noopener" class="art-share-btn" title="Bagikan ke Facebook">
                <svg viewBox="0 0 24 24" fill="#1877f2" width="18" height="18"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
              </a>
              <button type="button" onclick="copyLink()" class="art-share-btn"
                      title="Salin link" style="background:none;border:1px solid rgba(0,0,0,.12);cursor:pointer;padding:0">
                <svg viewBox="0 0 24 24" fill="none" stroke="#888" stroke-width="2" width="18" height="18"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
              </button>
            </div>
            <?php if (!empty($artTags)): ?>       <!-- TAMBAHKAN INI -->
            <div class="art-detail-tags art-detail-tags--footer">
              <span class="art-detail-tags-label">Tag:</span>
              <?php foreach ($artTags as $tag): ?>
              <a href="<?= $listUrl ?>?tag=<?= urlencode($tag) ?>"
                 class="art-detail-tag" rel="tag"><?= e($tag) ?></a>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
          </footer>
        </article><!-- /.art-detail-article -->

        <!--
          .art-below-article:
          - VISIBLE on mobile → related articles + crosslinks appear below article (existing behavior)
          - HIDDEN on desktop → these sections live in the right sidebar instead
        -->
        <div class="art-below-article">

          <?php if ($related): ?>
          <section class="art-related" aria-label="Artikel terkait">
            <h3 class="art-related-title">Artikel Lainnya</h3>
            <div class="art-related-grid">
              <?php foreach ($related as $rel):
                $relSlug  = $rel['slug'] ?? $rel['id'];
                $relJudul = html_entity_decode($rel['judul'] ?? '', ENT_QUOTES|ENT_HTML5, 'UTF-8');
                $relDate  = SupabaseArticleManager::formatTanggal($rel['published_at'] ?? $rel['created_at'] ?? '');
              ?>
              <a class="art-related-card" href="/artikel/<?= $menu ?>/<?= rawurlencode($relSlug) ?>">
                <?php if (!empty($rel['thumbnail'])): ?>
                <img src="<?= e($rel['thumbnail']) ?>" alt="<?= e($relJudul) ?>"
                     class="art-related-thumb" loading="lazy" width="300" height="169">
                <?php else: ?>
                <div class="art-related-thumb art-related-thumb-placeholder">📄</div>
                <?php endif; ?>
                <div class="art-related-info">
                  <div class="art-related-card-title"><?= e($relJudul) ?></div>
                  <?php if ($relDate): ?><div class="art-related-date"><?= e($relDate) ?></div><?php endif; ?>
                </div>
              </a>
              <?php endforeach; ?>
            </div>
          </section>
          <?php endif; ?>

          <section class="art-crosslink" aria-label="Jelajahi kategori lain">
            <h3 class="art-crosslink-title">Dari Umat, Untuk Umat</h3>
            <div class="art-crosslink-grid">
              <a class="art-crosslink-card" href="/galeri">
                <span class="art-crosslink-icon">🖼️</span>
                <div class="art-crosslink-info">
                  <div class="art-crosslink-label">Galeri Foto</div>
                  <div class="art-crosslink-desc">Dokumentasi visual kegiatan Paroki Tulungagung.</div>
                </div>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14" class="art-crosslink-arrow" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
              </a>
              <a class="art-crosslink-card" href="/umkmumat">
                <span class="art-crosslink-icon">🛍️</span>
                <div class="art-crosslink-info">
                  <div class="art-crosslink-label">UMKM Umat</div>
                  <div class="art-crosslink-desc">Pasar umat — produk & usaha warga Paroki Tulungagung.</div>
                </div>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14" class="art-crosslink-arrow" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
              </a>
              <?php foreach ($dynamicSlots as $slot): ?>
              <a class="art-crosslink-card" href="<?= e($slot['url']) ?>">
                <span class="art-crosslink-icon"><?= $slot['icon'] ?></span>
                <div class="art-crosslink-info">
                  <div class="art-crosslink-label"><?= e($slot['label']) ?></div>
                  <div class="art-crosslink-desc"><?= e($slot['desc']) ?></div>
                </div>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14" class="art-crosslink-arrow" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
              </a>
              <?php endforeach; ?>
            </div>
          </section>

        </div><!-- /.art-below-article -->

      </div><!-- /.art-center-col -->


      <!-- ═══════════════════════════════════════════
           RIGHT SIDEBAR — Related + Cross-links (desktop only)
      ═══════════════════════════════════════════ -->
      <aside class="art-sidebar-right" aria-label="Artikel terkait dan navigasi">
        <div class="art-sidebar-right-inner">

          <?php if ($related): ?>
          <p class="art-sidebar-related-title">Artikel Lainnya</p>
          <div class="art-sidebar-related-list">
            <?php foreach ($related as $rel):
              $relSlug  = $rel['slug'] ?? $rel['id'];
              $relJudul = html_entity_decode($rel['judul'] ?? '', ENT_QUOTES|ENT_HTML5, 'UTF-8');
              $relDate  = SupabaseArticleManager::formatTanggal($rel['published_at'] ?? $rel['created_at'] ?? '');
            ?>
            <a class="art-sidebar-related-card" href="/artikel/<?= $menu ?>/<?= rawurlencode($relSlug) ?>">
              <?php if (!empty($rel['thumbnail'])): ?>
              <img src="<?= e($rel['thumbnail']) ?>" alt="<?= e($relJudul) ?>"
                   class="art-sidebar-related-thumb" loading="lazy" width="54" height="54">
              <?php else: ?>
              <div class="art-sidebar-related-thumb-placeholder">📄</div>
              <?php endif; ?>
              <div class="art-sidebar-related-info">
                <div class="art-sidebar-related-card-title"><?= e($relJudul) ?></div>
                <?php if ($relDate): ?><div class="art-sidebar-related-date"><?= e($relDate) ?></div><?php endif; ?>
              </div>
            </a>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>

          <p class="art-sidebar-crosslink-title">Dari Umat, Untuk Umat</p>
          <div class="art-sidebar-crosslink-list">

            <a class="art-sidebar-crosslink-card" href="/galeri">
              <span class="art-sidebar-crosslink-icon">🖼️</span>
              <div class="art-sidebar-crosslink-info">
                <div class="art-sidebar-crosslink-label">Galeri Foto</div>
                <div class="art-sidebar-crosslink-desc">Dokumentasi visual kegiatan paroki.</div>
              </div>
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12" class="art-sidebar-crosslink-arrow" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
            </a>

            <a class="art-sidebar-crosslink-card" href="/umkmumat">
              <span class="art-sidebar-crosslink-icon">🛍️</span>
              <div class="art-sidebar-crosslink-info">
                <div class="art-sidebar-crosslink-label">UMKM Umat</div>
                <div class="art-sidebar-crosslink-desc">Produk & usaha warga paroki.</div>
              </div>
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12" class="art-sidebar-crosslink-arrow" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
            </a>

            <?php foreach ($dynamicSlots as $slot): ?>
            <a class="art-sidebar-crosslink-card" href="<?= e($slot['url']) ?>">
              <span class="art-sidebar-crosslink-icon"><?= $slot['icon'] ?></span>
              <div class="art-sidebar-crosslink-info">
                <div class="art-sidebar-crosslink-label"><?= e($slot['label']) ?></div>
                <div class="art-sidebar-crosslink-desc"><?= e($slot['desc']) ?></div>
              </div>
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12" class="art-sidebar-crosslink-arrow" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
            </a>
            <?php endforeach; ?>

          </div><!-- /.art-sidebar-crosslink-list -->

        </div><!-- /.art-sidebar-right-inner -->
      </aside><!-- /.art-sidebar-right -->

    </div><!-- /.art-three-col -->

  </main>

</div><!-- /#outer-wrapper -->
<?php include __DIR__ . '/../components/footer.php'; ?>
<script>
window.togglemenudiv = function () {
  const div = document.getElementById('divmenu');
  if (!div) return;
  div.style.display = (div.style.display === 'none' || div.style.display === '') ? 'block' : 'none';
};

/* ============================================================
   PHOTO MODAL
   ============================================================ */
window.ShowPhotoBox = function (txt, fotopath, title, subtxt) {
  const set = (id, val) => { const el = document.getElementById(id); if (el) el.innerHTML = val || ''; };
  set('boxModalTitle',   title);
  set('boxModalText',    txt);
  set('boxModalSubText', subtxt);
  const img   = document.getElementById('boxModalImage');
  const modal = document.getElementById('boxModal');
  if (img)   img.src = fotopath;
  if (modal) modal.style.display = 'block';
};

/* ============================================================
   ABOUT MODAL
   ============================================================ */
window.ShowAboutBox = function () {
  const modal = document.getElementById('aboutModal');
  if (modal) modal.style.display = 'block';
};

/* ============================================================
   TAB SYSTEM – generic, dipakai di semua halaman bertab
   Panggil initTabs(tabsId, contentsId) setelah DOM siap
   ============================================================ */
function initTabs(tabsId, contentsId) {
  const tabsEl    = document.getElementById(tabsId);
  const contentsEl = document.getElementById(contentsId) || document.querySelector('.tabcontents');
  if (!tabsEl) return;

  const tabs     = tabsEl.querySelectorAll('a');
  const contents = contentsEl ? contentsEl.querySelectorAll(':scope > div') : [];

  function switchTab(targetId) {
    tabs.forEach(t => t.parentElement.classList.remove('selected'));
    contents.forEach(c => c.classList.remove('active'));
    tabs.forEach(t => {
      if (t.getAttribute('href') === '#' + targetId) t.parentElement.classList.add('selected');
    });
    const target = document.getElementById(targetId);
    if (target) target.classList.add('active');
  }

  tabs.forEach(tab => {
    tab.addEventListener('click', function (e) {
      e.preventDefault();
      switchTab(this.getAttribute('href').substring(1));
    });
  });

  // Aktifkan tab pertama
  if (tabs.length > 0) switchTab(tabs[0].getAttribute('href').substring(1));
}

/* ============================================================
   ACCORDION (galeri bulan, DPP bidang)
   ============================================================ */
function initAccordions() {
  document.querySelectorAll('.galeri-accordion-header').forEach(function (header) {
    header.addEventListener('click', function () {
      const acc = this.closest('.galeri-accordion');
      if (acc) acc.classList.toggle('open');
    });
  });
}

/* ============================================================
   SCROLL BUTTONS (untuk tabs-tahun galeri)
   ============================================================ */
function initScrollButtons() {
  const tabsEl  = document.getElementById('tabs-tahun');
  const btnLeft  = document.getElementById('btnScrollLeft');
  const btnRight = document.getElementById('btnScrollRight');
  if (!tabsEl || !btnLeft || !btnRight) return;

  function update() {
    btnLeft.style.display  = tabsEl.scrollLeft > 0 ? 'block' : 'none';
    const maxScroll        = tabsEl.scrollWidth - tabsEl.clientWidth;
    btnRight.style.display = tabsEl.scrollLeft < maxScroll - 1 ? 'block' : 'none';
  }

  btnLeft.addEventListener('click',  () => tabsEl.scrollBy({ left: -150, behavior: 'smooth' }));
  btnRight.addEventListener('click', () => tabsEl.scrollBy({ left:  150, behavior: 'smooth' }));
  tabsEl.addEventListener('scroll', update);
  window.addEventListener('resize', update);
  setTimeout(update, 100);
}

/* ============================================================
   FILTER ASISTEN IMAM by wilayah
   ============================================================ */
window.filterByWilayah = function (value) {
  document.querySelectorAll('#konten-asistenimam .profile-item').forEach(function (card) {
    // .profile-item memakai display:flex — jangan override ke 'block'
    card.style.display = (value === 'all' || card.getAttribute('data-wilayah') === value) ? 'flex' : 'none';
  });
};

/* ============================================================
   W3 TABS (agenda – style lama)
   ============================================================ */
window.openTab = function (evt, tabName) {
  document.querySelectorAll('.tabcontent').forEach(el => el.style.display = 'none');
  document.querySelectorAll('.tablink').forEach(el => el.classList.remove('active-tab'));
  const tab = document.getElementById(tabName);
  if (tab) tab.style.display = 'block';
  if (evt && evt.currentTarget) evt.currentTarget.classList.add('active-tab');
};

/* ============================================================
   KONTRIBUTOR — redirect ke halaman registrasi
   ============================================================ */
window.openWhatsApp = function () {
  // Dialihkan ke halaman registrasi (tidak lagi ke WhatsApp)
  window.location.href = '/admin/register.php';
};

/* ============================================================
   KEYBOARD NAVIGATION MENU
   ============================================================ */
function initMenuKeyboard() {
  document.querySelectorAll('.divtombol').forEach(function (item) {
    item.addEventListener('keypress', function (e) {
      if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); this.click(); }
    });
  });
}

/* ============================================================
   LOADING BAR – utilities
   Topbar muncul otomatis dari HTML (class="indeterminate").
   PHP sudah selesai render saat JS ini jalan → langsung selesaikan.
   Untuk iframe: topbar menunggu sampai iframe onload.
   ============================================================ */

function _fillBar(percent) {
  var bar = document.getElementById('progressBar');
  if (!bar) return;
  bar.classList.remove('indeterminate');
  bar.style.width = percent + '%';
}

function _setStatus(text) {
  var el = document.getElementById('loadingStatusText');
  if (el) el.textContent = text;
}

function _hideLoader() {
  var topbar = document.getElementById('contentTopbar');
  var status = document.getElementById('loadingStatus');
  var bar    = document.getElementById('progressBar');
  if (bar)    { bar.classList.remove('indeterminate'); bar.style.width = '100%'; }
  setTimeout(function () {
    if (topbar) topbar.classList.add('hidden');
    if (status) status.classList.add('hidden');
  }, 400);
}

// Expose supaya iframe onload bisa panggil
window._hidePageLoader = _hideLoader;

/* ============================================================
   NAVIGATION LOADER
   Saat user klik link → tampilkan indikator di halaman sekarang
   (mengisi jeda saat PHP sedang memproses request berikutnya)
   ============================================================ */
(function () {
  var NAV_BAR_ID  = 'nav-progress-bar';
  var NAV_WRAP_ID = 'nav-progress-wrap';

  function createNavBar() {
    if (document.getElementById(NAV_BAR_ID)) return;
    var wrap = document.createElement('div');
    wrap.id  = NAV_WRAP_ID;
    wrap.style.cssText = [
      'position:fixed', 'top:0', 'left:0', 'right:0', 'z-index:9999',
      'height:3px', 'pointer-events:none', 'opacity:0',
      'transition:opacity 0.2s ease'
    ].join(';');
    var bar = document.createElement('div');
    bar.id  = NAV_BAR_ID;
    bar.style.cssText = [
      'height:100%', 'width:0%',
      'background:linear-gradient(90deg,#5b2c6f,#b8860b)',
      'box-shadow:0 0 8px rgba(184,134,11,0.6)',
      'border-radius:2px',
      'transition:width 0.3s ease'
    ].join(';');
    wrap.appendChild(bar);
    document.body.appendChild(wrap);
  }

  function startNavProgress() {
    createNavBar();
    var wrap = document.getElementById(NAV_WRAP_ID);
    var bar  = document.getElementById(NAV_BAR_ID);
    if (!wrap || !bar) return;
    wrap.style.opacity = '1';
    bar.style.width    = '0%';
    // Simulasi progres: cepat ke 70%, lalu tahan menunggu server
    setTimeout(function () { bar.style.width = '70%'; }, 50);
    setTimeout(function () { bar.style.width = '85%'; }, 800);
  }

  document.addEventListener('click', function (e) {
    var link = e.target.closest('a[href]');
    if (!link) return;
    var href = link.getAttribute('href');
    // Hanya untuk navigasi internal, bukan blank/hash/external
    if (!href || href.startsWith('#') || href.startsWith('http') ||
        href.startsWith('mailto') || link.target === '_blank') return;
    startNavProgress();
  });
})();


  // ── Bind tombol menu ────────────────────────────────────────
  var btnMenu = document.getElementById('btnmenu');
  if (btnMenu) btnMenu.addEventListener('click', window.togglemenudiv);

  // ── Tutup photo modal ───────────────────────────────────────
  var boxModal  = document.getElementById('boxModal');
  var boxClose  = document.getElementById('boxModalClose');
  if (boxClose && boxModal) boxClose.addEventListener('click', function () { boxModal.style.display = 'none'; });

  // ── Tutup about modal ───────────────────────────────────────
  var aboutModal = document.getElementById('aboutModal');
  var aboutClose = aboutModal ? aboutModal.querySelector('.close') : null;
  if (aboutClose && aboutModal) aboutClose.addEventListener('click', function () { aboutModal.style.display = 'none'; });

  // ── Klik di luar modal → tutup ──────────────────────────────
  window.addEventListener('click', function (e) {
    if (e.target === boxModal)   boxModal.style.display   = 'none';
    if (e.target === aboutModal) aboutModal.style.display = 'none';
  });

  // ── LOADING BAR: halaman sudah selesai di-render PHP ────────
  var isIframePage = !!document.getElementById('framecontent');

  if (isIframePage) {
    // Iframe masih load async → topbar tetap sampai iframe.onload
    _fillBar(40);
    _setStatus('Memuat konten');
    // Fallback: jika iframe tidak memanggil _hidePageLoader dalam 12 detik
    setTimeout(function () {
      if (!window._iframeLoaded) _hideLoader();
    }, 12000);
  } else {
    // Semua data sudah ada → langsung selesaikan bar
    _fillBar(80);
    _setStatus('Selesai');
    setTimeout(_hideLoader, 200);
  }
// ===================== IMAGE SYSTEM UPGRADE =====================
document.addEventListener("DOMContentLoaded", function () {
  // ── Interaktivitas ──────────────────────────────────────────
  initAccordions();
  initMenuKeyboard();

  // ── Tab systems per halaman ─────────────────────────────────
  if (document.getElementById('tabs-tahun')) {
    initTabs('tabs-tahun', 'tabcontents-dinamis');
    initScrollButtons();
  }
  if (document.getElementById('tabs-wilayah')) {
    initTabs('tabs-wilayah', 'tabcontents-wilayah');
  }
  if (document.getElementById('tabs-dpp')) {
    initTabs('tabs-dpp', 'tabcontents-dpp');
  }
  if (document.getElementById('tabs-kategorial')) {
    initTabs('tabs-kategorial', null);
  }
});

/* ═══════════════════════════════════════════════════════
   HERO BACKGROUND ROTATOR — Homepage only
   Hanya desktop; mobile pakai solid color
   ═══════════════════════════════════════════════════════ */
(function () {
  var hero = document.querySelector('.hero-paroki');
  if (!hero) return; // bukan homepage, langsung keluar
  if (window.innerWidth <= 600) return; // mobile: skip

  var images = [
    '/img/gereja/exterior-blank.webp',
    '/img/gereja/interiorwide.webp',
  ];
  var idx = 0, cachedImg = null, heroTimer = null, heroVisible = true;

  function setBg() {
    if (cachedImg) { cachedImg.onload = null; cachedImg = null; }
    var img = new Image();
    cachedImg = img;
    img.src = images[idx];
    img.onload = function () {
      if (img === cachedImg) hero.style.backgroundImage = "url('" + images[idx] + "')";
    };
  }

  function shouldRun() { return heroVisible && !document.hidden; }

  function startHeroRotation() {
    clearInterval(heroTimer);
    if (!shouldRun()) return;
    heroTimer = setInterval(function () { idx = (idx + 1) % images.length; setBg(); }, 6000);
  }

  function stopHeroRotation() { clearInterval(heroTimer); }

  document.addEventListener('visibilitychange', function () {
    document.hidden ? stopHeroRotation() : startHeroRotation();
  });

  if ('IntersectionObserver' in window) {
    var io = new IntersectionObserver(function (entries) {
      heroVisible = entries[0].isIntersecting;
      heroVisible ? startHeroRotation() : stopHeroRotation();
    }, { threshold: 0.1 });
    io.observe(hero);
  }

  // Gambar pertama sudah ada di CSS → langsung mulai timer rotasi
  startHeroRotation();
})();

/* ═══════════════════════════════════════════════════════
   ARTIKEL SLIDER — Homepage only
   ═══════════════════════════════════════════════════════ */
(function () {
  var slider = document.getElementById('artikelSlider');
  if (!slider) return; // bukan homepage

  var slides   = slider.querySelectorAll('.artikel-slide');
  var dots     = document.querySelectorAll('.dot');
  var btnPrev  = slider.querySelector('.slider-btn.prev');
  var btnNext  = slider.querySelector('.slider-btn.next');
  var progress = document.getElementById('sliderProgress');

  if (!slides.length) return;

  var DURATION = 5000, current = 0, timer = null, sliderVisible = true, hovered = false;

  function show(i) {
    slides[current].style.willChange = 'auto';
    slides.forEach(function (s, x) {
      s.classList.toggle('active', x === i);
      s.tabIndex = x === i ? 0 : -1;
    });
    dots.forEach(function (d, x) { d.classList.toggle('active', x === i); });
    current = i;
    slides[current].style.willChange = 'opacity';
    startProgress();
  }

  function shouldRun() { return sliderVisible && !document.hidden && !hovered; }

  function startAutoplay() {
    clearInterval(timer);
    if (!shouldRun()) return;
    timer = setInterval(function () { show((current + 1) % slides.length); }, DURATION);
  }

  function stopAutoplay() { clearInterval(timer); }

  function startProgress() {
    if (!progress) return;
    progress.style.transition = 'none';
    progress.style.width = '0%';
    progress.offsetWidth; // force reflow
    progress.style.transition = 'width ' + DURATION + 'ms linear';
    progress.style.width = '100%';
  }

  if (btnPrev) btnPrev.addEventListener('click', function () { show((current - 1 + slides.length) % slides.length); startAutoplay(); });
  if (btnNext) btnNext.addEventListener('click', function () { show((current + 1) % slides.length); startAutoplay(); });

  dots.forEach(function (d, x) { d.addEventListener('click', function () { show(x); startAutoplay(); }); });

  var touchStartX = 0;
  slider.addEventListener('touchstart', function (e) { touchStartX = e.changedTouches[0].clientX; }, { passive: true });
  slider.addEventListener('touchend', function (e) {
    var dx = e.changedTouches[0].clientX - touchStartX;
    if (Math.abs(dx) < 40) return;
    show(dx < 0 ? (current + 1) % slides.length : (current - 1 + slides.length) % slides.length);
    startAutoplay();
  }, { passive: true });

  slider.addEventListener('mouseenter', function () { hovered = true; stopAutoplay(); });
  slider.addEventListener('mouseleave', function () { hovered = false; startAutoplay(); });

  slider.addEventListener('keydown', function (e) {
    if (e.key === 'ArrowRight') { show((current + 1) % slides.length); startAutoplay(); }
    if (e.key === 'ArrowLeft')  { show((current - 1 + slides.length) % slides.length); startAutoplay(); }
  });

  document.addEventListener('visibilitychange', function () {
    document.hidden ? stopAutoplay() : startAutoplay();
  });

  if ('IntersectionObserver' in window) {
    var io2 = new IntersectionObserver(function (entries) {
      sliderVisible = entries[0].isIntersecting;
      sliderVisible ? startAutoplay() : stopAutoplay();
    }, { threshold: 0.25 });
    io2.observe(slider);
  }

  show(0);
  startAutoplay();
})();
</script>
<script>
/* ═══════════════════════════════════════════════════════
   ARTIKEL DETAIL — Page-specific scripts
   Dijalankan hanya di halaman artikel-detail
   ═══════════════════════════════════════════════════════ */
(function () {
  if (!document.querySelector('.art-detail-content')) return; // bukan artikel-detail

  document.addEventListener('DOMContentLoaded', function () {

    // ── Block 1 ──
    const container = document.querySelector('.art-detail-content');
      if (!container) return;
    
      const nodes = Array.from(container.children);
      // groupItems: array of { wrap, caption, parentNode } per group
      let groupItems = [];
    
      nodes.forEach((node, index) => {
        const imgs = node.querySelectorAll ? node.querySelectorAll('img') : [];
        if (imgs.length === 0) return;
    
        imgs.forEach(img => {
          // 1. Bungkus img dalam .img-wrap jika belum
          if (!img.closest('.img-wrap')) {
            const wrap = document.createElement('div');
            wrap.className = 'img-wrap';
            img.parentNode.insertBefore(wrap, img);
            wrap.appendChild(img);
          }
          const imgWrap = img.closest('.img-wrap');
    
          // 2. Kumpulkan caption dari figcaption saudara kandung img-wrap
          const parentEl = imgWrap.parentElement; // biasanya <figure class="img-figure">
          let captionText = '';
          if (parentEl) {
            const figcap = parentEl.querySelector('figcaption');
            if (figcap) captionText = figcap.textContent.trim();
          }
          // Fallback: data-caption pada node
          if (!captionText && node.dataset && node.dataset.caption) {
            captionText = node.dataset.caption;
          }
    
          groupItems.push({ wrap: imgWrap, caption: captionText, parentNode: node });
        });
    
        // 3. Cek apakah node berikutnya juga punya gambar
        const next = nodes[index + 1];
        const nextHasImg = next && next.querySelector && next.querySelector('img');
    
        if (!nextHasImg) {
          // Akhir grup — proses jika ada lebih dari 1 gambar
          if (groupItems.length > 1) {
            const row = document.createElement('div');
            row.className = 'image-row';
    
            // Caption pertama yang tersedia untuk grup
            const groupCaption = (groupItems.find(item => item.caption) || {}).caption || '';
            const seenParents = new Set();
    
            groupItems.forEach(({ wrap, parentNode }) => {
              // Pindahkan figcaption ke dalam img-wrap sebagai elemen SEO (hidden)
              const parentEl = wrap.parentElement;
              if (parentEl && parentEl !== row) {
                const figcap = parentEl.querySelector('figcaption:not(.img-caption-hidden)');
                if (figcap) {
                  figcap.classList.add('img-caption-hidden');
                  wrap.appendChild(figcap); // pindah masuk ke wrap, tersembunyi
                }
              }
              row.appendChild(wrap); // pindahkan wrap ke row
              seenParents.add(parentNode);
            });
    
            // 4. Tambahkan SATU caption gabungan di bawah semua gambar
            if (groupCaption) {
              const cap = document.createElement('div');
              cap.className = 'image-row-caption';
              cap.textContent = groupCaption;
              row.appendChild(cap);
            }
    
            // 5. Sisipkan row setelah node terakhir grup
            node.parentNode.insertBefore(row, node.nextSibling);
    
            // 6. Hapus container gambar yang sekarang sudah kosong (figurenya)
            seenParents.forEach(p => {
              if (p && p.parentNode && p.querySelectorAll('img').length === 0) {
                p.parentNode.removeChild(p);
              }
            });
          }
    
          groupItems = [];
        }
      });

    // ── Block 2 ──
    document.querySelectorAll('.image-row').forEach(row => {
        const wraps = row.querySelectorAll('.img-wrap');
        wraps.forEach(w => {
          const imgs = w.querySelectorAll('img');
          if (imgs.length > 1) {
            imgs.forEach((img, i) => {
              if (i === 0) return;
              const newWrap = document.createElement('div');
              newWrap.className = 'img-wrap';
              w.parentNode.insertBefore(newWrap, w.nextSibling);
              newWrap.appendChild(img);
            });
          }
        });
      });

    // ── Block 3 ──
    document.querySelectorAll('.image-row img').forEach(img => {
        img.style.cursor = 'zoom-in';
        img.addEventListener('click', function () {
          const overlay = document.createElement('div');
          overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,.9);display:flex;align-items:center;justify-content:center;z-index:9999;cursor:zoom-out;';
          const clone = document.createElement('img');
          clone.src = img.src; clone.alt = img.alt || '';
          clone.style.cssText = 'max-width:90%;max-height:90%;border-radius:12px;box-shadow:0 10px 40px rgba(0,0,0,.5);';
          overlay.appendChild(clone);
          overlay.addEventListener('click', () => overlay.remove());
          document.body.appendChild(overlay);
        });
      });

    // ── Block 4 ──
    document.querySelectorAll('.image-row img').forEach(img => {
        const check = () => { if (img.naturalHeight > img.naturalWidth) img.classList.add('portrait'); };
        if (img.complete) check(); else img.onload = check;
      });

  });
})();

</script>
<script>
const _artUrl = 'https://www.parokitulungagung.org/artikel/<?= $menu ?>/<?= rawurlencode($slug) ?>';
async function copyLink() {
  try { await navigator.clipboard.writeText(_artUrl); }
  catch(e) {
    const t=document.createElement('textarea'); t.value=_artUrl;
    t.style.cssText='position:fixed;opacity:0'; document.body.appendChild(t);
    t.select(); document.execCommand('copy'); document.body.removeChild(t);
  }
  const el=document.createElement('div');
  el.textContent='Link disalin ✓';
  el.style.cssText='position:fixed;bottom:24px;left:50%;transform:translateX(-50%);background:#222;color:#fff;padding:10px 20px;border-radius:20px;font-size:13px;z-index:9999;box-shadow:0 4px 16px rgba(0,0,0,.3)';
  document.body.appendChild(el);
  setTimeout(()=>el.remove(), 2500);
}
</script>

<!-- ===================== VIEW COUNT ===================== -->
<script>
(function() {
  const SUPA_URL  = 'https://rkzaathgygfjovrpdlqi.supabase.co';
  const SUPA_KEY  = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InJremFhdGhneWdmam92cnBkbHFpIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NzM4NzU4ODksImV4cCI6MjA4OTQ1MTg4OX0.soAwJ97mp9HbMolaNH1I3kGzTTha1lOOH8XkLBY-aiE';
  const MENU      = '<?= addslashes($menu) ?>';
  const SLUG      = '<?= addslashes($slug) ?>';
  const SESSION_KEY = 'art_viewed_' + MENU + '_' + SLUG;

  // Format angka view: 1234 → "1.234", 12345 → "12,3 rb"
  function fmtView(n) {
    if (n >= 1000000) return (n/1000000).toFixed(1).replace('.',',') + ' jt';
    if (n >= 10000)   return Math.round(n/1000).toFixed(0) + ' rb';
    if (n >= 1000)    return (n/1000).toFixed(1).replace('.',',') + ' rb';
    return n.toLocaleString('id-ID');
  }

  // Tampilkan view count di UI
  function showViewCount(count) {
    const txt = fmtView(count);
    // Sidebar desktop
    const sNum = document.getElementById('sidebar-vc-num');
    const sBox = document.getElementById('sidebar-view-count');
    if (sNum && sBox) { sNum.textContent = txt; sBox.style.display = 'flex'; }
    // Badge mobile/header
    const mNum = document.getElementById('mobile-vc-num');
    const mBox = document.getElementById('mobile-view-count');
    if (mNum && mBox) { mNum.textContent = txt; mBox.style.display = 'inline-flex'; }
  }

  // Fetch view count saat ini dari Supabase
  async function fetchViewCount() {
    try {
      const res = await fetch(
        SUPA_URL + '/rest/v1/articles?select=view_count&slug=eq.' + encodeURIComponent(SLUG) + '&menu=eq.' + encodeURIComponent(MENU) + '&status=eq.published&limit=1',
        { headers: { 'apikey': SUPA_KEY, 'Authorization': 'Bearer ' + SUPA_KEY } }
      );
      if (!res.ok) return;
      const data = await res.json();
      if (data && data[0] && typeof data[0].view_count === 'number') {
        showViewCount(data[0].view_count);
      }
    } catch(e) { /* silent fail */ }
  }

  // Increment view count (sekali per sesi)
  async function incrementView() {
    if (sessionStorage.getItem(SESSION_KEY)) return; // sudah dihitung sesi ini
    sessionStorage.setItem(SESSION_KEY, '1');
    try {
      await fetch(
        SUPA_URL + '/rest/v1/articles?slug=eq.' + encodeURIComponent(SLUG) + '&menu=eq.' + encodeURIComponent(MENU) + '&status=eq.published',
        {
          method: 'PATCH',
          headers: {
            'apikey': SUPA_KEY,
            'Authorization': 'Bearer ' + SUPA_KEY,
            'Content-Type': 'application/json',
            'Prefer': 'return=minimal'
          },
          body: JSON.stringify({ view_count: '(view_count+1)' })
        }
      );
    } catch(e) { /* silent fail */ }
  }

  // Jalankan setelah halaman load
  // Gunakan raw PATCH dengan expression — fallback ke RPC jika gagal
  async function trackView() {
    // Cek apakah kolom view_count ada (graceful degradation)
    await fetchViewCount();

    // Increment setelah 2 detik (user benar-benar membaca, bukan sekadar mampir)
    setTimeout(async () => {
      if (sessionStorage.getItem(SESSION_KEY)) return;
      sessionStorage.setItem(SESSION_KEY, '1');
      try {
        // Gunakan RPC function increment_article_view
        const res = await fetch(SUPA_URL + '/rest/v1/rpc/increment_article_view', {
          method: 'POST',
          headers: {
            'apikey': SUPA_KEY,
            'Authorization': 'Bearer ' + SUPA_KEY,
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({ article_slug: SLUG, article_menu: MENU })
        });
        // Setelah increment, ambil nilai terbaru
        if (res.ok) {
          setTimeout(fetchViewCount, 500);
        }
      } catch(e) { /* silent fail */ }
    }, 2000);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', trackView);
  } else {
    trackView();
  }
})();
</script>

<!-- ===================== IMAGE SYSTEM UPGRADE ===================== -->


<!-- ===================== LIGHTBOX ===================== -->

<!-- ===================== PORTRAIT DETECT ===================== -->

<?php if (!empty($imagesSchema)):
  // Update global agar tersedia jika butuh referensi di luar
  $GLOBALS['_seo_images_schema'] = $imagesSchema;
?>
<script type="application/ld+json">
<?= json_encode([
  '@context'         => 'https://schema.org',
  '@type'            => 'NewsArticle',
  '@id'              => 'https://www.parokitulungagung.org/artikel/' . $menu . '/' . rawurlencode($slug) . '#newsarticle',
  'headline'         => mb_substr(strip_tags($seo['title'] ?? ''), 0, 110),
  'image'            => $imagesSchema,
  'datePublished'    => $pubDate,
  'dateModified'     => $modDate,
  'mainEntityOfPage' => ['@id' => 'https://www.parokitulungagung.org/artikel/' . $menu . '/' . rawurlencode($slug) . '#webpage'],
  'author'           => !empty($art['penulis'])
      ? [
          '@type' => 'Person',
          '@id'   => 'https://www.parokitulungagung.org/penulis/' . preg_replace('/[^a-z0-9]+/', '-', strtolower($art['penulis'] ?? '')),
          'name'  => $art['penulis'],
        ]
      : ['@id' => 'https://www.parokitulungagung.org/#organization'],
  'publisher'        => ['@id' => 'https://www.parokitulungagung.org/#organization'],
  'isPartOf'         => ['@id' => 'https://www.parokitulungagung.org/#website'],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT); ?>
</script>
<?php endif; ?>

<?php
// ── FAQPage Schema — otomatis dari heading artikel ────────────────────
// Cara kerja:
//   1. Parse semua <h2> di konten artikel → menjadi "Question"
//   2. Ambil teks paragraf pertama setelah tiap <h2> → menjadi "Answer"
//   3. Jika minimal 2 pasangan valid ditemukan → inject FAQPage schema
//   4. Google pakai ini untuk rich snippet "People Also Ask" di SERP
//
// Syarat artikel eligible:
//   - Punya minimal 2 <h2> dengan paragraf setelahnya
//   - Jawaban minimal 30 karakter (bukan heading kosong)
//   - Berlaku untuk semua menu (berita, kronik, historia)

$faqItems = [];

$rawKonten = $art['konten'] ?? '';
if (!empty($rawKonten)) {
    libxml_use_internal_errors(true);
    $faqDom = new DOMDocument();
    $faqDom->loadHTML(
        '<?xml encoding="utf-8"?><div>' . $rawKonten . '</div>',
        LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOWARNING | LIBXML_NOERROR
    );
    libxml_clear_errors();

    $faqXpath = new DOMXPath($faqDom);
    // Ambil semua h2 di dalam konten
    $h2nodes = $faqXpath->query('//h2');

    foreach ($h2nodes as $h2) {
        $question = trim(strip_tags($faqDom->saveHTML($h2)));
        if (strlen($question) < 5) continue; // skip heading terlalu pendek

        // Cari paragraf pertama yang langsung mengikuti h2 ini
        $answer = '';
        $sibling = $h2->nextSibling;
        while ($sibling) {
            // Skip text nodes berisi whitespace saja
            if ($sibling->nodeType === XML_TEXT_NODE) {
                $sibling = $sibling->nextSibling;
                continue;
            }
            // Ambil <p> atau <ul> atau <ol> sebagai jawaban
            $nodeName = strtolower($sibling->nodeName ?? '');
            if (in_array($nodeName, ['p', 'ul', 'ol', 'div', 'blockquote'])) {
                $answer = trim(strip_tags($faqDom->saveHTML($sibling)));
                break;
            }
            // Berhenti kalau ketemu heading lain
            if (in_array($nodeName, ['h1','h2','h3','h4'])) break;
            $sibling = $sibling->nextSibling;
        }

        // Validasi: jawaban harus cukup panjang dan informatif
        if (strlen($answer) < 30) continue;

        // Potong jawaban di 300 karakter agar tidak terlalu panjang di rich snippet
        if (strlen($answer) > 300) {
            $answer = mb_substr($answer, 0, 297) . '...';
        }

        $faqItems[] = [
            '@type'          => 'Question',
            'name'           => $question,
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text'  => $answer,
            ],
        ];
    }
}

// Hanya output schema jika minimal 2 FAQ item ditemukan
if (count($faqItems) >= 2):
?>
<script type="application/ld+json">
<?= json_encode([
    '@context'   => 'https://schema.org',
    '@type'      => 'FAQPage',
    'mainEntity' => $faqItems,
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT); ?>
</script>
<?php endif; ?>
</body>
</html>