<?php
/**
 * pages/penulis.php
 * Halaman profil penulis artikel — E-E-A-T & Schema.org Person
 * URL: /penulis/{slug}
 *
 * PERBAIKAN:
 * 1. Fetch data user lengkap dari tabel `users` (id, nama, email, role, bio, jabatan, sosmed)
 *    dengan fallback graceful jika kolom bio/jabatan belum ada di DB.
 * 2. Foto penulis ditampilkan dengan benar (foto nyata → inisial).
 * 3. Blok bio/jabatan ditampilkan jika ada data, kosong tersembunyi — tidak ada konten palsu.
 * 4. Schema.org Person diperkaya: sameAs (sosmed), jobTitle, description.
 * 5. Link kanonik penulis sudah dipasskan ke artikel via itemprop.
 * 6. Breadcrumb 3 level: Beranda › Penulis › [Nama].
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/SupabaseArticleManager.php';

// ── Slug dari URL ────────────────────────────────────────────────────────
$slug = $_GET['slug'] ?? '';
$slug = preg_replace('/[^a-z0-9\-]/', '', strtolower(trim($slug)));

if (!$slug) {
    http_response_code(404);
    include __DIR__ . '/../error.php';
    exit;
}

$am = new SupabaseArticleManager();

// ── Helper: fetch data user dari Supabase (nama + kolom opsional E-E-A-T) ─
function fetchAuthorUser(string $rawName): array
{
    $cacheKey = 'author_user_full_' . md5($rawName);
    $cached   = cache_get($cacheKey);
    if ($cached !== null) return $cached;

    $default = [
        'id'       => '',
        'nama'     => $rawName,
        'username' => '',
        'email'    => '',
        'role'     => '',
        'bio'      => '',
        'jabatan'  => '',
        'instagram'=> '',
        'facebook' => '',
        'twitter'  => '',
        'website'  => '',
    ];

    if (!defined('SUPABASE_URL') || !defined('SUPABASE_ANON_KEY')) {
        cache_set($cacheKey, $default, 300);
        return $default;
    }

    // Coba ambil dengan kolom lengkap dulu; fallback ke kolom minimal jika kolom tambahan belum ada
    $selectFull    = 'id,username,nama,email,role,bio,jabatan,instagram,facebook,twitter,website';
    $selectMinimal = 'id,username,nama,email,role';

    $hdrs = [
        'apikey: '        . SUPABASE_ANON_KEY,
        'Authorization: Bearer ' . SUPABASE_ANON_KEY,
        'Accept: application/json',
    ];
    $ctx = stream_context_create([
        'http' => ['header' => implode("\r\n", $hdrs) . "\r\n", 'timeout' => 6, 'ignore_errors' => true],
        'ssl'  => ['verify_peer' => true],
    ]);

    $doFetch = function(string $select) use ($rawName, $hdrs, $ctx): ?array {
        $url = rtrim(SUPABASE_URL, '/') . '/rest/v1/users'
             . '?or=(nama.ilike.' . urlencode($rawName) . ',username.ilike.' . urlencode($rawName) . ')'
             . '&select=' . $select . '&limit=1';
        $res = @file_get_contents($url, false, $ctx);
        if ($res === false && function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => $hdrs,
                CURLOPT_SSL_VERIFYPEER => true, CURLOPT_TIMEOUT => 6]);
            $res = curl_exec($ch); curl_close($ch);
        }
        if (!$res) return null;
        $decoded = json_decode($res, true);
        return (is_array($decoded) && !empty($decoded)) ? $decoded[0] : null;
    };

    // Coba fetch kolom penuh
    $row = $doFetch($selectFull);

    // Jika gagal (kolom belum ada → Supabase error), fallback ke minimal
    if (!$row) {
        $row = $doFetch($selectMinimal);
    }

    if (!$row) {
        cache_set($cacheKey, $default, 300);
        return $default;
    }

    $result = array_merge($default, array_filter($row, fn($v) => $v !== null && $v !== ''));
    cache_set($cacheKey, $result, 3600);
    return $result;
}

// ── Helper: cari foto profil dari uid ────────────────────────────────────
function resolveAuthorPhoto(string $uid): string
{
    if (!$uid) return '';
    $root = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/');
    foreach (['webp', 'jpg', 'png'] as $ext) {
        $path = '/img/admin/profil/profil-' . $uid . '.' . $ext;
        if (file_exists($root . $path)) return $path;
    }
    return '';
}

// ── Cache halaman penulis ────────────────────────────────────────────────
$cacheKey = 'author_profile_v2_' . md5($slug);
$cached   = cache_get($cacheKey);

$authorData     = null;
$authorArticles = [];
$authorUser     = [];

if ($cached !== null) {
    $authorData     = $cached['data']     ?? null;
    $authorArticles = $cached['articles'] ?? [];
    $authorUser     = $cached['user']     ?? [];
} else {
    // Kumpulkan artikel dari semua menu
    foreach (SupabaseArticleManager::MENUS as $menu) {
        $arts = $am->getByAuthorSlug($menu, $slug);
        foreach ($arts as $art) {
            if (!$authorData && !empty($art['penulis'])) {
                $rawName    = strip_tags($art['penulis']);
                $authorData = ['name' => $rawName, 'slug' => $slug];
            }
            $authorArticles[] = [
                'judul'        => $art['judul']        ?? '',
                'slug'         => $art['slug']         ?? '',
                'menu'         => $menu,
                'published_at' => $art['published_at'] ?? '',
                'thumbnail'    => $art['thumbnail']    ?? '',
            ];
        }
    }

    // Fetch data user dari Supabase (E-E-A-T)
    if ($authorData) {
        $authorUser = fetchAuthorUser($authorData['name']);
    }

    cache_set($cacheKey, [
        'data'     => $authorData,
        'articles' => $authorArticles,
        'user'     => $authorUser,
    ], 600);
}

if (!$authorData) {
    http_response_code(404);
    include __DIR__ . '/../error.php';
    exit;
}

// ── Variabel tampilan ────────────────────────────────────────────────────
$authorName    = htmlspecialchars($authorData['name'], ENT_QUOTES, 'UTF-8');
$authorUrl     = 'https://www.parokitulungagung.org/penulis/' . $slug;
$articleCount  = count($authorArticles);
$authorInitial = strtoupper(mb_substr(strip_tags($authorData['name']), 0, 1, 'UTF-8'));
$authorPhoto   = resolveAuthorPhoto($authorUser['id'] ?? '');

// Bio, jabatan, sosmed dari data user
$authorBio     = trim($authorUser['bio']       ?? '');
$authorJabatan = trim($authorUser['jabatan']   ?? '');
$authorIG      = trim($authorUser['instagram'] ?? '');
$authorFB      = trim($authorUser['facebook']  ?? '');
$authorTW      = trim($authorUser['twitter']   ?? '');
$authorWeb     = trim($authorUser['website']   ?? '');
$hasSocmed     = ($authorIG || $authorFB || $authorTW || $authorWeb);

// Statistik per menu
$menuLabels = SupabaseArticleManager::MENU_LABELS;
$menuCounts = [];
foreach ($authorArticles as $a) {
    $menuCounts[$a['menu']] = ($menuCounts[$a['menu']] ?? 0) + 1;
}
$latestArticle = !empty($authorArticles) ? $authorArticles[0] : null;
$latestYear    = $latestArticle ? date('Y', strtotime($latestArticle['published_at'])) : date('Y');

// ── SEO ──────────────────────────────────────────────────────────────────
$seoDescBase = $authorData['name'] . ' adalah penulis artikel di Paroki Santa Maria Dengan Tidak Bernoda Asal (SMDTBA) Tulungagung.';
if ($articleCount > 0) {
    $seoDescBase .= ' Telah menulis ' . $articleCount . ' artikel seputar kehidupan Gereja Katolik, liturgi, dan paroki Tulungagung.';
}
$seoDesc = $authorBio ? mb_substr(strip_tags($authorBio), 0, 155, 'UTF-8') : $seoDescBase;

$seo = [
    'title'       => $authorData['name'] . ' – Penulis Artikel Paroki Tulungagung',
    'description' => $seoDesc,
    'canonical'   => $authorUrl,
    'type'        => 'website',
    'keywords'    => $authorData['name'] . ', penulis artikel, paroki tulungagung, gereja katolik tulungagung, SMDTBA, artikel katolik',
    'author'      => $authorData['name'],
    'noindex'     => false,
];

$breadcrumbs = [
    ['name' => 'Beranda', 'url' => 'https://www.parokitulungagung.org'],
    ['name' => 'Penulis', 'url' => 'https://www.parokitulungagung.org/penulis'],
    ['name' => $authorData['name']],
];

// ── Schema.org: Person (E-E-A-T anchor) ─────────────────────────────────
$itemListElements = [];
foreach (array_slice($authorArticles, 0, 10) as $idx => $a) {
    $itemListElements[] = [
        '@type'    => 'ListItem',
        'position' => $idx + 1,
        'url'      => 'https://www.parokitulungagung.org/artikel/' . $a['menu'] . '/' . $a['slug'],
        'name'     => $a['judul'],
    ];
}

$personSchema = [
    '@context'    => 'https://schema.org',
    '@type'       => 'Person',
    '@id'         => $authorUrl,
    'name'        => $authorData['name'],
    'url'         => $authorUrl,
    'description' => $seoDescBase,
    'jobTitle'    => $authorJabatan ?: 'Penulis Artikel',
    'worksFor'    => [
        '@type' => 'Organization',
        '@id'   => 'https://www.parokitulungagung.org/#organization',
        'name'  => 'Paroki Santa Maria Dengan Tidak Bernoda Asal Tulungagung',
    ],
    'affiliation' => [
        '@type' => 'Organization',
        '@id'   => 'https://www.parokitulungagung.org/#organization',
    ],
    'knowsAbout'  => ['Liturgi Katolik', 'Kehidupan Gereja', 'Paroki Tulungagung', 'Iman Katolik'],
];

// Tambahkan foto jika ada
if ($authorPhoto) {
    $personSchema['image'] = 'https://www.parokitulungagung.org' . $authorPhoto;
}

// Tambahkan sameAs (sosmed) jika ada
$sameAs = [];
if ($authorIG)  $sameAs[] = (strpos($authorIG, 'http') === 0) ? $authorIG : 'https://instagram.com/' . ltrim($authorIG, '@/');
if ($authorFB)  $sameAs[] = (strpos($authorFB, 'http') === 0) ? $authorFB : 'https://facebook.com/' . ltrim($authorFB, '/');
if ($authorTW)  $sameAs[] = (strpos($authorTW, 'http') === 0) ? $authorTW : 'https://twitter.com/' . ltrim($authorTW, '@/');
if ($authorWeb) $sameAs[] = $authorWeb;
if ($sameAs) $personSchema['sameAs'] = $sameAs;

// Tambahkan daftar artikel
if (!empty($itemListElements)) {
    $personSchema['mainEntityOfPage'] = [
        '@type'           => 'CollectionPage',
        '@id'             => $authorUrl,
        'name'            => $authorData['name'] . ' – Artikel',
        'numberOfItems'   => $articleCount,
        'itemListElement' => $itemListElements,
    ];
}

// ── WebPage / ProfilePage schema ─────────────────────────────────────────
$webpageSchema = [
    '@context'    => 'https://schema.org',
    '@type'       => 'ProfilePage',
    '@id'         => $authorUrl . '#webpage',
    'url'         => $authorUrl,
    'name'        => $authorData['name'] . ' – Penulis Artikel Paroki Tulungagung',
    'description' => $seoDesc,
    'inLanguage'  => 'id-ID',
    'isPartOf'    => ['@id' => 'https://www.parokitulungagung.org/#website'],
    'about'       => ['@id' => $authorUrl],
    'publisher'   => ['@id' => 'https://www.parokitulungagung.org/#organization'],
    'dateModified'=> date('c'),
    'mainEntity'  => ['@id' => $authorUrl],
];

// BreadcrumbList schema
$breadcrumbSchema = [
    '@context'        => 'https://schema.org',
    '@type'           => 'BreadcrumbList',
    'itemListElement' => [
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Beranda', 'item' => 'https://www.parokitulungagung.org'],
        ['@type' => 'ListItem', 'position' => 2, 'name' => 'Penulis', 'item' => 'https://www.parokitulungagung.org/penulis'],
        ['@type' => 'ListItem', 'position' => 3, 'name' => $authorData['name'], 'item' => $authorUrl],
    ],
];

$extraCss = [];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<?php include __DIR__ . '/../components/seo_head.php'; ?>

<script type="application/ld+json">
<?= json_encode($personSchema,    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?>
</script>
<script type="application/ld+json">
<?= json_encode($webpageSchema,   JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?>
</script>
<script type="application/ld+json">
<?= json_encode($breadcrumbSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?>
</script>

<style>
/* ═══════════════════════════════════════════════════════════════
   PENULIS PAGE — Editorial / Catholic journal aesthetic
   Palet: warm cream + deep navy + gold accent
   Typography: Cormorant Garamond (serif) + Montserrat (sans)
═══════════════════════════════════════════════════════════════ */

:root {
  --ink:        #1c1916;
  --ink-soft:   #4a443c;
  --ink-muted:  #8a7f72;
  --cream:      #faf7f2;
  --cream-mid:  #f0ebe1;
  --cream-dark: #e4ddd1;
  --gold:       #b8832a;
  --gold-light: #d4a44e;
  --gold-dim:   rgba(184,131,42,0.12);
  --navy:       #1e2d45;
  --card-bg:    #ffffff;
  --radius:     6px;
  --shadow-sm:  0 1px 4px rgba(28,25,22,.07);
  --shadow-md:  0 4px 20px rgba(28,25,22,.10);
  --font-serif: 'Cormorant Garamond', Georgia, serif;
  --font-sans:  'Montserrat', system-ui, sans-serif;
  --transition: 0.22s ease;
}

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

.pnl-page {
  background: var(--cream);
  min-height: 100vh;
  font-family: var(--font-sans);
  color: var(--ink);
}

/* ── Hero Header ──────────────────────────────────────────────── */
.pnl-hero {
  background: var(--navy);
  position: relative;
  overflow: hidden;
}

.pnl-hero::before {
  content: '';
  position: absolute;
  inset: 0;
  background-image:
    radial-gradient(ellipse 80% 60% at 70% 50%, rgba(184,131,42,.14) 0%, transparent 65%),
    radial-gradient(ellipse 40% 80% at 10% 20%, rgba(255,255,255,.04) 0%, transparent 60%);
  pointer-events: none;
}

.pnl-hero-ornament {
  height: 3px;
  background: linear-gradient(90deg, transparent, var(--gold) 20%, var(--gold-light) 50%, var(--gold) 80%, transparent);
}

.pnl-hero-inner {
  position: relative;
  z-index: 1;
  max-width: 780px;
  margin: 0 auto;
  padding: 48px 24px 44px;
  display: flex;
  gap: 32px;
  align-items: center;
}

/* ── Avatar ── */
.pnl-avatar-wrap {
  flex-shrink: 0;
  position: relative;
}

.pnl-avatar,
.pnl-avatar-img {
  width: 104px;
  height: 104px;
  border-radius: 50%;
  box-shadow: 0 0 0 3px rgba(184,131,42,.4), 0 0 0 6px rgba(184,131,42,.14);
  animation: avatarIn .5s cubic-bezier(.34,1.56,.64,1) both;
}

.pnl-avatar {
  background: linear-gradient(135deg, var(--gold) 0%, var(--gold-light) 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: var(--font-serif);
  font-size: 2.6rem;
  font-weight: 600;
  color: #fff;
  letter-spacing: -0.02em;
}

.pnl-avatar-img {
  object-fit: cover;
  display: block;
}

@keyframes avatarIn {
  from { transform: scale(.7); opacity: 0; }
  to   { transform: scale(1);  opacity: 1; }
}

/* ── Teks hero ── */
.pnl-hero-text { flex: 1; min-width: 0; }

.pnl-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-family: var(--font-sans);
  font-size: .72rem;
  font-weight: 600;
  letter-spacing: .12em;
  text-transform: uppercase;
  color: var(--gold-light);
  margin-bottom: 10px;
  opacity: 0;
  animation: fadeUp .4s .15s ease both;
}

.pnl-name {
  font-family: var(--font-serif);
  font-size: clamp(1.65rem, 4vw, 2.4rem);
  font-weight: 600;
  color: #fff;
  line-height: 1.18;
  letter-spacing: -.01em;
  margin-bottom: 6px;
  opacity: 0;
  animation: fadeUp .4s .25s ease both;
}

.pnl-jabatan {
  font-size: .82rem;
  font-weight: 500;
  color: var(--gold-light);
  letter-spacing: .04em;
  margin-bottom: 8px;
  opacity: 0;
  animation: fadeUp .4s .30s ease both;
}

.pnl-tagline {
  font-size: .88rem;
  color: rgba(255,255,255,.55);
  line-height: 1.5;
  font-weight: 300;
  opacity: 0;
  animation: fadeUp .4s .35s ease both;
}

/* ── Sosmed icons ── */
.pnl-socmed {
  display: flex;
  gap: 10px;
  margin-top: 14px;
  opacity: 0;
  animation: fadeUp .4s .42s ease both;
}

.pnl-socmed-link {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px; height: 32px;
  border-radius: 50%;
  background: rgba(255,255,255,.10);
  border: 1px solid rgba(255,255,255,.15);
  color: rgba(255,255,255,.75);
  text-decoration: none;
  font-size: .8rem;
  transition: all var(--transition);
}
.pnl-socmed-link:hover {
  background: var(--gold-dim);
  border-color: var(--gold);
  color: var(--gold-light);
}

/* ── Stat strip ── */
.pnl-stats-strip {
  background: var(--navy);
  border-top: 1px solid rgba(255,255,255,.06);
}
.pnl-stats-inner {
  max-width: 780px;
  margin: 0 auto;
  padding: 0 24px;
  display: flex;
}
.pnl-stat {
  padding: 18px 28px 18px 0;
  display: flex;
  align-items: center;
  gap: 10px;
  opacity: 0;
  animation: fadeUp .4s .48s ease both;
}
.pnl-stat + .pnl-stat {
  border-left: 1px solid rgba(255,255,255,.08);
  padding-left: 28px;
}
.pnl-stat-num {
  font-family: var(--font-serif);
  font-size: 1.6rem;
  font-weight: 600;
  color: var(--gold-light);
  line-height: 1;
}
.pnl-stat-label {
  font-size: .75rem;
  color: rgba(255,255,255,.42);
  font-weight: 300;
  letter-spacing: .02em;
  line-height: 1.35;
}

@keyframes fadeUp {
  from { transform: translateY(12px); opacity: 0; }
  to   { transform: translateY(0);    opacity: 1; }
}

/* ── Breadcrumb ── */
.pnl-breadcrumb { background: var(--cream-mid); border-bottom: 1px solid var(--cream-dark); }
.pnl-breadcrumb-inner {
  max-width: 780px; margin: 0 auto; padding: 10px 24px;
  font-size: .75rem; color: var(--ink-muted);
  display: flex; align-items: center; gap: 6px; flex-wrap: wrap;
}
.pnl-breadcrumb a { color: var(--ink-soft); text-decoration: none; }
.pnl-breadcrumb a:hover { color: var(--gold); }
.pnl-breadcrumb-sep { color: var(--cream-dark); font-size: .9em; }

/* ── Body ── */
.pnl-body {
  max-width: 780px;
  margin: 0 auto;
  padding: 40px 24px 80px;
}

/* ── Bio card ── */
.pnl-bio-card {
  background: var(--card-bg);
  border: 1px solid var(--cream-dark);
  border-left: 3px solid var(--gold);
  border-radius: var(--radius);
  padding: 22px 24px;
  margin-bottom: 36px;
  box-shadow: var(--shadow-sm);
}
.pnl-bio-label {
  font-size: .7rem;
  font-weight: 700;
  letter-spacing: .12em;
  text-transform: uppercase;
  color: var(--gold);
  margin-bottom: 8px;
}
.pnl-bio-text {
  font-size: .92rem;
  color: var(--ink-soft);
  line-height: 1.75;
}

/* ── Section heading ── */
.pnl-section-head {
  display: flex;
  align-items: baseline;
  gap: 12px;
  margin-bottom: 24px;
  padding-bottom: 12px;
  border-bottom: 1px solid var(--cream-dark);
}
.pnl-section-title {
  font-family: var(--font-serif);
  font-size: 1.15rem;
  font-weight: 600;
  color: var(--ink);
}
.pnl-section-count {
  font-size: .78rem;
  font-weight: 600;
  color: var(--gold);
  background: var(--gold-dim);
  padding: 2px 9px;
  border-radius: 20px;
  border: 1px solid rgba(184,131,42,.2);
}

/* ── Filter per rubrik ── */
.pnl-filter {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-bottom: 24px;
}
.pnl-filter-btn {
  font-family: var(--font-sans);
  font-size: .75rem;
  font-weight: 600;
  letter-spacing: .04em;
  text-transform: uppercase;
  padding: 5px 14px;
  border-radius: 20px;
  border: 1.5px solid var(--cream-dark);
  background: var(--card-bg);
  color: var(--ink-soft);
  cursor: pointer;
  transition: all var(--transition);
}
.pnl-filter-btn:hover,
.pnl-filter-btn.active {
  border-color: var(--gold);
  background: var(--gold-dim);
  color: var(--gold);
}

/* ── Artikel cards ── */
.pnl-list {
  list-style: none;
  display: flex;
  flex-direction: column;
  gap: 1px;
}
.pnl-item { animation: cardIn .35s ease both; }
.pnl-item:nth-child(1)  { animation-delay:.05s }
.pnl-item:nth-child(2)  { animation-delay:.10s }
.pnl-item:nth-child(3)  { animation-delay:.15s }
.pnl-item:nth-child(4)  { animation-delay:.20s }
.pnl-item:nth-child(5)  { animation-delay:.25s }
.pnl-item:nth-child(6)  { animation-delay:.30s }

@keyframes cardIn {
  from { opacity: 0; transform: translateY(10px); }
  to   { opacity: 1; transform: none; }
}

.pnl-card {
  display: flex;
  gap: 20px;
  align-items: flex-start;
  padding: 18px 20px;
  background: var(--card-bg);
  border: 1px solid var(--cream-dark);
  border-radius: var(--radius);
  text-decoration: none;
  color: inherit;
  transition: all var(--transition);
  position: relative;
  margin-bottom: 10px;
}
.pnl-card::before {
  content: '';
  position: absolute;
  left: 0; top: 12px; bottom: 12px;
  width: 2.5px;
  border-radius: 0 2px 2px 0;
  background: var(--gold);
  opacity: 0;
  transition: opacity var(--transition);
}
.pnl-card:hover {
  border-color: rgba(184,131,42,.3);
  box-shadow: var(--shadow-md);
  transform: translateY(-2px);
}
.pnl-card:hover::before { opacity: 1; }

.pnl-card-thumb {
  flex-shrink: 0;
  width: 88px; height: 60px;
  border-radius: 4px;
  object-fit: cover;
  background: var(--cream-mid);
}
.pnl-card-thumb-placeholder {
  flex-shrink: 0;
  width: 88px; height: 60px;
  border-radius: 4px;
  background: linear-gradient(135deg, var(--cream-mid), var(--cream-dark));
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--ink-muted);
  font-size: 1.4rem;
}
.pnl-card-body { flex: 1; min-width: 0; }
.pnl-card-meta {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 5px;
  flex-wrap: wrap;
}
.pnl-card-cat {
  font-size: .68rem;
  font-weight: 600;
  letter-spacing: .08em;
  text-transform: uppercase;
  color: var(--gold);
}
.pnl-card-sep { color: var(--cream-dark); font-size: .8em; }
.pnl-card-date { font-size: .75rem; color: var(--ink-muted); }
.pnl-card-title {
  font-family: var(--font-serif);
  font-size: .98rem;
  font-weight: 600;
  line-height: 1.42;
  color: var(--ink);
  transition: color var(--transition);
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.pnl-card:hover .pnl-card-title { color: var(--gold); }
.pnl-card-arrow {
  flex-shrink: 0;
  align-self: center;
  color: var(--cream-dark);
  font-size: 1rem;
  transition: all var(--transition);
  margin-left: 4px;
}
.pnl-card:hover .pnl-card-arrow { color: var(--gold); transform: translateX(3px); }

/* ── Empty state ── */
.pnl-empty {
  text-align: center;
  padding: 60px 20px;
  color: var(--ink-muted);
}
.pnl-empty-icon { font-size: 2.5rem; margin-bottom: 12px; opacity: .5; }
.pnl-empty-text { font-size: .92rem; }

/* ── Hidden (filter) ── */
.pnl-item.hidden { display: none; }

/* ── Responsive ── */
@media (max-width: 560px) {
  .pnl-hero-inner { flex-direction: column; align-items: flex-start; gap: 20px; padding: 36px 20px 36px; }
  .pnl-avatar, .pnl-avatar-img { width: 80px; height: 80px; font-size: 2rem; }
  .pnl-stats-inner { gap: 0; }
  .pnl-stat { padding: 14px 16px 14px 0; }
  .pnl-stat + .pnl-stat { padding-left: 16px; }
  .pnl-stat-num { font-size: 1.3rem; }
  .pnl-body { padding: 28px 16px 60px; }
  .pnl-card { padding: 14px 14px; gap: 12px; }
  .pnl-card-thumb, .pnl-card-thumb-placeholder { width: 72px; height: 50px; }
}
</style>
</head>

<body class="pnl-page">
<?php include __DIR__ . '/../components/menu.php'; ?>

<!-- ══════════════════════════════════════════════════════════
     HERO
══════════════════════════════════════════════════════════ -->
<header class="pnl-hero" itemscope itemtype="https://schema.org/Person" itemid="<?= htmlspecialchars($authorUrl) ?>">
  <link itemprop="url" href="<?= htmlspecialchars($authorUrl) ?>">
  <?php if ($authorPhoto): ?>
    <link itemprop="image" href="https://www.parokitulungagung.org<?= htmlspecialchars($authorPhoto) ?>">
  <?php endif; ?>
  <meta itemprop="jobTitle" content="<?= htmlspecialchars($authorJabatan ?: 'Penulis Artikel', ENT_QUOTES, 'UTF-8') ?>">
  <meta itemprop="description" content="<?= htmlspecialchars($seoDescBase, ENT_QUOTES, 'UTF-8') ?>">

  <div class="pnl-hero-ornament"></div>
  <div class="pnl-hero-inner">

    <!-- Avatar: foto nyata atau inisial -->
    <div class="pnl-avatar-wrap">
      <?php if ($authorPhoto): ?>
      <img class="pnl-avatar-img"
           src="<?= htmlspecialchars($authorPhoto, ENT_QUOTES, 'UTF-8') ?>"
           alt="Foto <?= $authorName ?>"
           width="104" height="104"
           loading="eager" decoding="async">
      <?php else: ?>
      <div class="pnl-avatar" aria-hidden="true"><?= $authorInitial ?></div>
      <?php endif; ?>
    </div>

    <div class="pnl-hero-text">
      <p class="pnl-badge">&#10013; Profil Penulis</p>
      <h1 class="pnl-name" itemprop="name"><?= $authorName ?></h1>

      <?php if ($authorJabatan): ?>
      <p class="pnl-jabatan" itemprop="jobTitle"><?= htmlspecialchars($authorJabatan, ENT_QUOTES, 'UTF-8') ?></p>
      <?php endif; ?>

      <p class="pnl-tagline" itemprop="worksFor" itemscope itemtype="https://schema.org/Organization">
        Kontributor artikel —
        <span itemprop="name">Paroki Santa Maria Tidak Bernoda Asal (SMDTBA) Tulungagung</span>
      </p>

<?php if ($hasSocmed): ?>
<div class="pnl-socmed" aria-label="Profil media sosial <?= $authorName ?>">

  <!-- Instagram -->
  <?php if ($authorIG): ?>
  <a class="pnl-socmed-link"
     href="<?= htmlspecialchars((strpos($authorIG,'http')===0 ? $authorIG : 'https://instagram.com/'.ltrim($authorIG,'@/')), ENT_QUOTES, 'UTF-8') ?>"
     target="_blank" rel="noopener noreferrer"
     aria-label="Instagram <?= $authorName ?>"
     itemprop="sameAs">
    <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" aria-hidden="true">
      <path d="M7 2C4.2 2 2 4.2 2 7v10c0 2.8 2.2 5 5 5h10c2.8 0 5-2.2 5-5V7c0-2.8-2.2-5-5-5H7zm5 5.8A4.2 4.2 0 1 1 7.8 12 4.2 4.2 0 0 1 12 7.8zm0 6.9A2.7 2.7 0 1 0 9.3 12 2.7 2.7 0 0 0 12 14.7zm4.5-7.6a1 1 0 1 1 1-1 1 1 0 0 1-1 1z"/>
    </svg>
  </a>
  <?php endif; ?>

  <!-- Facebook -->
  <?php if ($authorFB): ?>
  <a class="pnl-socmed-link"
     href="<?= htmlspecialchars((strpos($authorFB,'http')===0 ? $authorFB : 'https://facebook.com/'.ltrim($authorFB,'/')), ENT_QUOTES, 'UTF-8') ?>"
     target="_blank" rel="noopener noreferrer"
     aria-label="Facebook <?= $authorName ?>"
     itemprop="sameAs">
    <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" aria-hidden="true">
      <path d="M13 22v-9h3l.5-3H13V8.5c0-.9.3-1.5 1.6-1.5H17V4.2C16.5 4.1 15.6 4 14.6 4 12.3 4 11 5.3 11 7.6V10H8v3h3v9h2z"/>
    </svg>
  </a>
  <?php endif; ?>

  <!-- Twitter / X -->
  <?php if ($authorTW): ?>
  <a class="pnl-socmed-link"
     href="<?= htmlspecialchars((strpos($authorTW,'http')===0 ? $authorTW : 'https://twitter.com/'.ltrim($authorTW,'@/')), ENT_QUOTES, 'UTF-8') ?>"
     target="_blank" rel="noopener noreferrer"
     aria-label="Twitter <?= $authorName ?>"
     itemprop="sameAs">
    <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" aria-hidden="true">
      <path d="M18 2h3l-7.5 8.5L22 22h-6l-4.7-6.1L5.5 22H2l8-9.1L2 2h6l4.2 5.6L18 2z"/>
    </svg>
  </a>
  <?php endif; ?>

  <!-- Website -->
  <?php if ($authorWeb): ?>
  <a class="pnl-socmed-link"
     href="<?= htmlspecialchars($authorWeb, ENT_QUOTES, 'UTF-8') ?>"
     target="_blank" rel="noopener noreferrer"
     aria-label="Website <?= $authorName ?>"
     itemprop="sameAs">
    <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" aria-hidden="true">
      <path d="M12 2a10 10 0 100 20 10 10 0 000-20zm7.9 9h-3.2a15.7 15.7 0 00-1.2-5A8 8 0 0119.9 11zM12 4c1.3 1.5 2.2 3.8 2.5 7H9.5c.3-3.2 1.2-5.5 2.5-7zM4.1 13h3.2a15.7 15.7 0 001.2 5A8 8 0 014.1 13zm3.2-2H4.1a8 8 0 014.4-5 15.7 15.7 0 00-1.2 5zM12 20c-1.3-1.5-2.2-3.8-2.5-7h5c-.3 3.2-1.2 5.5-2.5 7zm3.5-2a15.7 15.7 0 001.2-5h3.2a8 8 0 01-4.4 5z"/>
    </svg>
  </a>
  <?php endif; ?>

</div>
<?php endif; ?>

    </div>
  </div><!-- /.pnl-hero-inner -->

  <!-- Stat strip -->
  <div class="pnl-stats-strip">
    <div class="pnl-stats-inner">
      <div class="pnl-stat">
        <div>
          <div class="pnl-stat-num"><?= $articleCount ?></div>
          <div class="pnl-stat-label">Artikel<br>Diterbitkan</div>
        </div>
      </div>
      <?php if (count($menuCounts) > 1): ?>
      <div class="pnl-stat">
        <div>
          <div class="pnl-stat-num"><?= count($menuCounts) ?></div>
          <div class="pnl-stat-label">Kategori<br>Rubrik</div>
        </div>
      </div>
      <?php endif; ?>
      <?php if ($latestArticle): ?>
      <div class="pnl-stat">
        <div>
          <div class="pnl-stat-num"><?= $latestYear ?></div>
          <div class="pnl-stat-label">Artikel<br>Terakhir</div>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>

</header><!-- /.pnl-hero -->

<!-- ── Breadcrumb ── -->
<nav class="pnl-breadcrumb" aria-label="Breadcrumb">
  <div class="pnl-breadcrumb-inner">
    <a href="/">Beranda</a>
    <span class="pnl-breadcrumb-sep">›</span>
    <a href="/penulis">Penulis</a>
    <span class="pnl-breadcrumb-sep">›</span>
    <span aria-current="page"><?= $authorName ?></span>
  </div>
</nav>

<!-- ══════════════════════════════════════════════════════════
     MAIN CONTENT
══════════════════════════════════════════════════════════ -->
<main class="pnl-body" id="main-content">

  <!-- Bio — hanya tampil jika ada isi -->
  <?php if ($authorBio): ?>
  <div class="pnl-bio-card" itemscope itemtype="https://schema.org/Person" itemid="<?= htmlspecialchars($authorUrl) ?>">
    <p class="pnl-bio-label">Tentang Penulis</p>
    <p class="pnl-bio-text" itemprop="description"><?= nl2br(htmlspecialchars($authorBio, ENT_QUOTES, 'UTF-8')) ?></p>
  </div>
  <?php endif; ?>

  <!-- Daftar artikel -->
  <?php if (!empty($authorArticles)): ?>

  <div class="pnl-section-head">
    <h2 class="pnl-section-title">Artikel oleh <?= $authorName ?></h2>
    <span class="pnl-section-count"><?= $articleCount ?></span>
  </div>

  <?php if (count($menuCounts) > 1): ?>
  <div class="pnl-filter" role="group" aria-label="Filter rubrik">
    <button class="pnl-filter-btn active" data-filter="all">Semua</button>
    <?php foreach ($menuCounts as $m => $cnt): ?>
    <button class="pnl-filter-btn" data-filter="<?= $m ?>">
      <?= htmlspecialchars($menuLabels[$m] ?? ucfirst($m), ENT_QUOTES, 'UTF-8') ?>
      <span style="opacity:.55;font-weight:400">&thinsp;(<?= $cnt ?>)</span>
    </button>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <ul class="pnl-list" id="pnl-article-list" aria-label="Daftar artikel oleh <?= $authorName ?>">
    <?php foreach ($authorArticles as $art): ?>
    <?php
      $artUrl     = '/artikel/' . $art['menu'] . '/' . $art['slug'];
      $artTitle   = htmlspecialchars($art['judul'], ENT_QUOTES, 'UTF-8');
      $artDate    = !empty($art['published_at']) ? SupabaseArticleManager::formatTanggal($art['published_at']) : '';
      $artDateIso = !empty($art['published_at']) ? date('c', strtotime($art['published_at'])) : '';
      $artCat     = htmlspecialchars($menuLabels[$art['menu']] ?? ucfirst($art['menu']), ENT_QUOTES, 'UTF-8');
    ?>
    <li class="pnl-item" data-menu="<?= $art['menu'] ?>"
        itemscope itemtype="https://schema.org/Article">
      <meta itemprop="author" content="<?= $authorName ?>">
      <link itemprop="author" href="<?= htmlspecialchars($authorUrl) ?>">
      <?php if ($artDateIso): ?><meta itemprop="datePublished" content="<?= $artDateIso ?>"><?php endif; ?>
      <meta itemprop="headline" content="<?= $artTitle ?>">

      <a href="<?= htmlspecialchars($artUrl) ?>" class="pnl-card" itemprop="url">

        <?php if (!empty($art['thumbnail'])): ?>
        <img class="pnl-card-thumb"
             src="<?= htmlspecialchars($art['thumbnail']) ?>"
             alt="<?= $artTitle ?>"
             width="88" height="60"
             loading="lazy" decoding="async"
             itemprop="image">
        <?php else: ?>
        <div class="pnl-card-thumb-placeholder" aria-hidden="true">&#10013;</div>
        <?php endif; ?>

        <div class="pnl-card-body">
          <div class="pnl-card-meta">
            <span class="pnl-card-cat"><?= $artCat ?></span>
            <?php if ($artDate): ?>
            <span class="pnl-card-sep">·</span>
            <time class="pnl-card-date" datetime="<?= $artDateIso ?>"><?= $artDate ?></time>
            <?php endif; ?>
          </div>
          <h3 class="pnl-card-title"><?= $artTitle ?></h3>
        </div>

        <span class="pnl-card-arrow" aria-hidden="true">›</span>
      </a>
    </li>
    <?php endforeach; ?>
  </ul>

  <?php else: ?>
  <div class="pnl-empty">
    <div class="pnl-empty-icon">&#10013;</div>
    <p class="pnl-empty-text">Belum ada artikel yang ditemukan untuk penulis ini.</p>
  </div>
  <?php endif; ?>

</main>

<?php include __DIR__ . '/../components/footer.php'; ?>

<script>
(function () {
  const btns  = document.querySelectorAll('.pnl-filter-btn');
  const items = document.querySelectorAll('.pnl-item[data-menu]');

  btns.forEach(btn => {
    btn.addEventListener('click', () => {
      const filter = btn.dataset.filter;
      btns.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      items.forEach(item => {
        item.classList.toggle('hidden', filter !== 'all' && item.dataset.menu !== filter);
      });
    });
  });
})();
</script>
</body>
</html>