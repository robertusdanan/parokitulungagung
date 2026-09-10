<?php
/**
 * pages/penulis-list.php
 * Direktori Kontributor & Penulis Artikel Paroki Tulungagung
 * URL: /penulis
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/SupabaseArticleManager.php';

// ── Cache list kontributor ──────────────────────────────────────────────
$cacheKey = 'all_contributors_v1';
$cachedData = cache_get($cacheKey);

if ($cachedData !== null) {
    $contributors = $cachedData['contributors'];
    $totalArticles = $cachedData['total_articles'];
} else {
    $hdrs = [
        'apikey: ' . SUPABASE_ANON_KEY,
        'Authorization: Bearer ' . SUPABASE_ANON_KEY,
        'Accept: application/json'
    ];

    // 1. Ambil jumlah artikel per penulis
    $urlArts = rtrim(SUPABASE_URL, '/') . '/rest/v1/articles?status=eq.published&select=penulis';
    $ch = curl_init($urlArts);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => $hdrs,
        CURLOPT_TIMEOUT        => 8,
        CURLOPT_SSL_VERIFYPEER => true
    ]);
    $resArts = curl_exec($ch);
    curl_close($ch);
    $arts = json_decode($resArts, true) ?: [];

    $artCounts = [];
    foreach ($arts as $a) {
        $p = trim(strip_tags($a['penulis'] ?? ''));
        if ($p !== '') {
            $artCounts[$p] = ($artCounts[$p] ?? 0) + 1;
        }
    }
    $totalArticles = count($arts);

    // 2. Ambil data users dari Supabase
    $urlUsers = rtrim(SUPABASE_URL, '/') . '/rest/v1/users?select=id,username,nama,bio,jabatan,role,instagram,facebook,twitter,website,is_active';
    $ch = curl_init($urlUsers);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => $hdrs,
        CURLOPT_TIMEOUT        => 8,
        CURLOPT_SSL_VERIFYPEER => true
    ]);
    $resUsers = curl_exec($ch);
    curl_close($ch);
    $users = json_decode($resUsers, true) ?: [];

    // Helper cari foto lokal
    $root = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/');

    $contributors = [];
    $matchedAuthors = [];

    foreach ($users as $u) {
        if (isset($u['is_active']) && !$u['is_active']) continue;

        $displayName = !empty($u['nama']) ? trim($u['nama']) : trim($u['username'] ?? '');
        if (!$displayName) continue;

        // Hitung artikel: cek kecocokan nama atau username
        $count = 0;
        foreach ($artCounts as $authorName => $c) {
            if (strcasecmp($authorName, $displayName) === 0 || strcasecmp($authorName, $u['username'] ?? '') === 0) {
                $count += $c;
                $matchedAuthors[$authorName] = true;
            }
        }

        $photo = !empty($u['id']) ? adminFotoUrl($u['id']) : '';

        $slug = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower(strip_tags($displayName))), '-');

        $contributors[] = [
            'id'          => $u['id'] ?? '',
            'name'        => $displayName,
            'slug'        => $slug,
            'photo'       => $photo,
            'initial'     => strtoupper(substr($displayName, 0, 1)),
            'role'        => $u['role'] ?? 'admin',
            'jabatan'     => !empty($u['jabatan']) ? $u['jabatan'] : (($u['role'] ?? '') === 'superadmin' ? 'Redaksi Komsos' : 'Kontributor Paroki'),
            'bio'         => $u['bio'] ?? '',
            'instagram'   => $u['instagram'] ?? '',
            'facebook'    => $u['facebook'] ?? '',
            'twitter'     => $u['twitter'] ?? '',
            'website'     => $u['website'] ?? '',
            'count'       => $count,
        ];
    }

    // Tambahkan author dari artikel yang belum terdaftar di users
    foreach ($artCounts as $authorName => $cnt) {
        if (!empty($matchedAuthors[$authorName])) continue;

        $slug = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower(strip_tags($authorName))), '-');
        $contributors[] = [
            'id'        => '',
            'name'      => $authorName,
            'slug'      => $slug,
            'photo'     => '',
            'initial'   => strtoupper(substr($authorName, 0, 1)),
            'role'      => 'contributor',
            'jabatan'   => 'Kontributor Artikel',
            'bio'       => '',
            'instagram' => '',
            'facebook'  => '',
            'twitter'   => '',
            'website'   => '',
            'count'     => $cnt,
        ];
    }

    // Urutkan: jumlah artikel terbanyak di atas
    usort($contributors, function ($a, $b) {
        if ($a['count'] !== $b['count']) {
            return $b['count'] <=> $a['count'];
        }
        return strcasecmp($a['name'], $b['name']);
    });

    cache_set($cacheKey, [
        'contributors'   => $contributors,
        'total_articles' => $totalArticles,
    ], 1800); // 30 menit
}

$pageTitle = 'Kontributor & Penulis Artikel – Paroki Tulungagung';
$pageDesc  = 'Daftar kontributor dan penulis artikel Gereja Katolik Santa Maria dengan Para Malaikat Tulungagung. Kenali para pewarta warta dan refleksi iman umat.';
$canonical = 'https://www.parokitulungagung.org/penulis';
$ogImage   = 'https://img.parokitulungagung.org/ogpreview/default-og.jpg';

$seo_override = [
    'title'       => $pageTitle,
    'description' => $pageDesc,
    'canonical'   => $canonical,
    'image'       => $ogImage,
    'type'        => 'website',
];

require_once __DIR__ . '/../components/seo_head.php';
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=Playfair+Display:ital,wght@0,600;0,700;1,400&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

<style>
:root {
  --pen-gold: #b8860b;
  --pen-gold-dark: #8c6b12;
  --pen-gold-light: #fefaf0;
  --pen-gold-border: rgba(184, 134, 11, 0.22);
  --pen-text-main: #2b251d;
  --pen-text-muted: #736453;
  --pen-card-bg: #ffffff;
}

body {
  background: #fbf9f5;
  color: var(--pen-text-main);
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
  margin: 0;
  padding: 0;
}

/* ── Hero Section ────────────────────────────────────────── */
.pen-hero {
  background: linear-gradient(135deg, #1f1a14 0%, #352b20 50%, #241d15 100%);
  color: #fff;
  padding: 70px 20px 60px;
  text-align: center;
  position: relative;
  overflow: hidden;
  border-bottom: 3px solid var(--pen-gold);
}
.pen-hero::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0; bottom: 0;
  background: radial-gradient(circle at center, rgba(184,134,11,0.18) 0%, transparent 70%);
  pointer-events: none;
}
.pen-hero-inner {
  max-width: 860px;
  margin: 0 auto;
  position: relative;
  z-index: 1;
}
.pen-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: rgba(184, 134, 11, 0.22);
  border: 1px solid rgba(184, 134, 11, 0.5);
  color: #f1cf7a;
  padding: 6px 16px;
  border-radius: 20px;
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .12em;
  margin-bottom: 18px;
}
.pen-title {
  font-family: 'Playfair Display', Georgia, serif;
  font-size: clamp(2rem, 5vw, 3.2rem);
  font-weight: 700;
  line-height: 1.2;
  margin: 0 0 14px;
  color: #fffdfa;
  text-shadow: 0 2px 10px rgba(0,0,0,0.4);
}
.pen-subtitle {
  font-size: clamp(0.95rem, 2vw, 1.15rem);
  color: #ddd0be;
  line-height: 1.65;
  margin: 0 auto 30px;
  max-width: 680px;
}
.pen-stats-row {
  display: flex;
  justify-content: center;
  gap: 30px;
  flex-wrap: wrap;
}
.pen-stat-card {
  background: rgba(255, 255, 255, 0.07);
  backdrop-filter: blur(8px);
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 12px;
  padding: 12px 24px;
  min-width: 140px;
}
.pen-stat-num {
  font-family: 'Playfair Display', Georgia, serif;
  font-size: 26px;
  font-weight: 700;
  color: #f5d78a;
  line-height: 1;
}
.pen-stat-label {
  font-size: 11.5px;
  color: #c4b5a2;
  text-transform: uppercase;
  letter-spacing: .06em;
  margin-top: 5px;
}

/* ── Breadcrumb & Container ──────────────────────────────── */
.pen-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 30px 20px 80px;
}
.pen-breadcrumb {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  color: var(--pen-text-muted);
  margin-bottom: 30px;
}
.pen-breadcrumb a {
  color: var(--pen-gold-dark);
  text-decoration: none;
  font-weight: 500;
  transition: color .15s;
}
.pen-breadcrumb a:hover {
  color: #5c450a;
  text-decoration: underline;
}

/* ── Grid Contributors ───────────────────────────────────── */
.pen-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 26px;
}

/* ── Card Style ─────────────────────────────────────────── */
.pen-card {
  background: var(--pen-card-bg);
  border: 1px solid var(--pen-gold-border);
  border-radius: 16px;
  padding: 28px 24px;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  box-shadow: 0 4px 18px rgba(184, 134, 11, 0.05);
  transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
  position: relative;
}
.pen-card:hover {
  transform: translateY(-5px);
  border-color: rgba(184, 134, 11, 0.45);
  box-shadow: 0 12px 30px rgba(184, 134, 11, 0.12);
}

.pen-avatar-wrap {
  position: relative;
  margin-bottom: 16px;
}
.pen-avatar {
  width: 88px;
  height: 88px;
  border-radius: 50%;
  object-fit: cover;
  border: 3px solid var(--pen-gold);
  box-shadow: 0 4px 14px rgba(184, 134, 11, 0.2);
  display: block;
}
.pen-avatar-init {
  width: 88px;
  height: 88px;
  border-radius: 50%;
  background: linear-gradient(135deg, #b8860b 0%, #d4a017 50%, #946c07 100%);
  color: #fff;
  font-family: 'Montserrat', sans-serif;
  font-size: 34px;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 14px rgba(184, 134, 11, 0.2);
}
.pen-art-badge {
  position: absolute;
  bottom: -4px;
  right: -4px;
  background: #2b251d;
  color: #f7d37d;
  border: 2px solid #fff;
  border-radius: 14px;
  padding: 3px 9px;
  font-size: 11px;
  font-weight: 700;
  display: inline-flex;
  align-items: center;
  gap: 4px;
  box-shadow: 0 2px 6px rgba(0,0,0,0.18);
  white-space: nowrap;
}

.pen-name {
  font-family: 'Playfair Display', Georgia, serif;
  font-size: 19px;
  font-weight: 700;
  color: var(--pen-text-main);
  margin: 0 0 6px;
  line-height: 1.3;
}
.pen-jabatan {
  display: inline-block;
  font-size: 12px;
  font-weight: 600;
  color: var(--pen-gold-dark);
  background: var(--pen-gold-light);
  border: 1px solid rgba(184, 134, 11, 0.25);
  border-radius: 20px;
  padding: 4px 12px;
  margin-bottom: 14px;
}
.pen-bio {
  font-size: 13.5px;
  color: var(--pen-text-muted);
  line-height: 1.55;
  margin: 0 0 20px;
  flex: 1;
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.pen-bio.empty {
  font-style: italic;
  color: #a39585;
}

/* ── Socials ─────────────────────────────────────────────── */
.pen-socials {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  margin-bottom: 20px;
}
.pen-social-link {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: #f4ede1;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: var(--pen-text-main);
  transition: all .2s;
  text-decoration: none;
}
.pen-social-link:hover {
  background: var(--pen-gold-dark);
  color: #fff;
  transform: scale(1.1);
}

/* ── Button Profil Penulis ──────────────────────────────── */
.pen-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  width: 100%;
  padding: 10px 20px;
  background: linear-gradient(180deg, #fffefb 0%, #f7f1e4 100%);
  color: var(--pen-gold-dark);
  border: 1.5px solid rgba(184, 134, 11, 0.38);
  border-radius: 24px;
  font-size: 13px;
  font-weight: 700;
  text-decoration: none;
  box-shadow: 0 2px 6px rgba(184, 134, 11, 0.08);
  transition: all 0.25s ease;
  box-sizing: border-box;
}
.pen-btn:hover {
  background: linear-gradient(135deg, #b8860b 0%, #8c6b12 100%);
  color: #ffffff;
  border-color: #8c6b12;
  box-shadow: 0 4px 14px rgba(184, 134, 11, 0.28);
  transform: translateY(-2px);
}
.pen-btn svg {
  transition: transform 0.2s ease;
}
.pen-btn:hover svg {
  transform: translateX(4px);
}

@media (max-width: 640px) {
  .pen-hero { padding: 45px 16px 40px; }
  .pen-grid { grid-template-columns: 1fr; }
  .pen-card { padding: 22px 18px; }
}
</style>

<body>
<?php $headerTitle = ''; include __DIR__ . '/../components/page_header.php'; ?>

<header class="pen-hero">
  <div class="pen-hero-inner">
    <div class="pen-badge">
      <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
      Warta & Karya Tulis Umat
    </div>
    <h1 class="pen-title">Kontributor & Penulis Artikel</h1>
    <p class="pen-subtitle">
      Para pewarta kabar sukacita, sejarah paroki, dan refleksi iman Gereja Katolik Santa Maria dengan Para Malaikat Tulungagung.
    </p>

    <div class="pen-stats-row">
      <div class="pen-stat-card">
        <div class="pen-stat-num"><?= count($contributors) ?></div>
        <div class="pen-stat-label">Kontributor</div>
      </div>
      <div class="pen-stat-card">
        <div class="pen-stat-num"><?= $totalArticles ?></div>
        <div class="pen-stat-label">Artikel Terbit</div>
      </div>
    </div>
  </div>
</header>

<main class="pen-container">
  <nav class="pen-breadcrumb" aria-label="Breadcrumb">
    <a href="/">Beranda</a>
    <span>›</span>
    <span style="color:var(--pen-text-main);font-weight:600">Penulis</span>
  </nav>

  <div class="pen-grid">
    <?php foreach ($contributors as $c):
      $profileUrl = '/penulis/' . rawurlencode($c['slug']);
      $bioText    = !empty($c['bio']) ? $c['bio'] : 'Kontributor dan pewarta artikel aktif di Paroki Santa Maria dengan Para Malaikat Tulungagung.';
    ?>
    <article class="pen-card">
      <div class="pen-avatar-wrap">
        <?php if (!empty($c['photo'])): ?>
          <img src="<?= htmlspecialchars($c['photo'], ENT_QUOTES, 'UTF-8') ?>"
               alt="<?= htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8') ?>"
               class="pen-avatar" width="88" height="88" loading="lazy"
               onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
          <div class="pen-avatar-init" style="display:none"><?= htmlspecialchars($c['initial'], ENT_QUOTES, 'UTF-8') ?></div>
        <?php else: ?>
          <div class="pen-avatar-init"><?= htmlspecialchars($c['initial'], ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <?php if ($c['count'] > 0): ?>
          <span class="pen-art-badge" title="<?= $c['count'] ?> Artikel Terbit">
            <svg viewBox="0 0 24 24" width="11" height="11" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            <?= $c['count'] ?>
          </span>
        <?php endif; ?>
      </div>

      <h2 class="pen-name"><?= htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8') ?></h2>
      <div class="pen-jabatan"><?= htmlspecialchars($c['jabatan'], ENT_QUOTES, 'UTF-8') ?></div>

      <p class="pen-bio"><?= htmlspecialchars($bioText, ENT_QUOTES, 'UTF-8') ?></p>

      <?php if (!empty($c['instagram']) || !empty($c['facebook']) || !empty($c['twitter']) || !empty($c['website'])): ?>
      <div class="pen-socials">
        <?php if (!empty($c['instagram'])):
          $igUrl = (strpos($c['instagram'], 'http') === 0) ? $c['instagram'] : 'https://instagram.com/' . ltrim($c['instagram'], '@/');
        ?>
        <a href="<?= htmlspecialchars($igUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener" class="pen-social-link" title="Instagram" aria-label="Instagram">
          <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>
        </a>
        <?php endif; ?>

        <?php if (!empty($c['facebook'])):
          $fbUrl = (strpos($c['facebook'], 'http') === 0) ? $c['facebook'] : 'https://facebook.com/' . ltrim($c['facebook'], '/');
        ?>
        <a href="<?= htmlspecialchars($fbUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener" class="pen-social-link" title="Facebook" aria-label="Facebook">
          <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
        </a>
        <?php endif; ?>

        <?php if (!empty($c['twitter'])):
          $twUrl = (strpos($c['twitter'], 'http') === 0) ? $c['twitter'] : 'https://twitter.com/' . ltrim($c['twitter'], '@/');
        ?>
        <a href="<?= htmlspecialchars($twUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener" class="pen-social-link" title="Twitter / X" aria-label="Twitter">
          <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
        </a>
        <?php endif; ?>

        <?php if (!empty($c['website'])): ?>
        <a href="<?= htmlspecialchars($c['website'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener" class="pen-social-link" title="Website Pribadi" aria-label="Website">
          <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
        </a>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <a href="<?= htmlspecialchars($profileUrl, ENT_QUOTES, 'UTF-8') ?>"
         class="pen-btn"
         aria-label="Lihat Profil Penulis <?= htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8') ?>">
        <span>Profil Penulis</span>
        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
      </a>
    </article>
    <?php endforeach; ?>
  </div>
</main>

<?php include __DIR__ . '/../components/footer.php'; ?>
<script src="/js/app.js?v=<?= substr(md5_file(__DIR__ . '/../js/app.js'), 0, 8) ?>"></script>
</body>
</html>
