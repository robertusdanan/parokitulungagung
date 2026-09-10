<?php
/**
 * pages/artikel.php — Daftar artikel publik (Berita / Kronik / Historia)
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/SupabaseArticleManager.php';

$menu = trim($_GET['menu'] ?? '');
if (!in_array($menu, SupabaseArticleManager::MENUS)) {
    http_response_code(404); include __DIR__ . '/../error.php'; exit;
}

$am = new SupabaseArticleManager();

// Cache daftar artikel per menu (5 menit)
$_artikelCacheKey = 'artikel_list_' . $menu . '_v1';
$articles = cache_get($_artikelCacheKey);
if (!is_array($articles) || empty($articles)) {
    $articles = $am->getAll($menu, publishedOnly: true);
    // Pastikan urut published_at terbaru dulu
    usort($articles, function($a, $b) {
        $ta = strtotime($a['published_at'] ?? $a['created_at'] ?? '0');
        $tb = strtotime($b['published_at'] ?? $b['created_at'] ?? '0');
        return $tb - $ta;
    });
    if (!empty($articles)) cache_set($_artikelCacheKey, $articles, 300);
}

$label    = SupabaseArticleManager::MENU_LABELS[$menu];
$page     = max(1, (int)($_GET['hal'] ?? 1));
$perPage  = 12;
$total    = count($articles);
$pages    = (int)ceil($total / $perPage);
$sliced   = array_slice($articles, ($page - 1) * $perPage, $perPage);

// Data SEMUA artikel lintas menu (Berita, Kronik, Historia) untuk pencarian —
// supaya pencarian tetap menemukan hasil dari sub-menu lain meski sedang
// membuka salah satu sub-menu saja.
$allArtikelSearch = [];
foreach (SupabaseArticleManager::MENUS as $__menuKey) {
    if ($__menuKey === $menu) {
        $__list = $articles; // sudah diambil & di-cache di atas
    } else {
        $__ck   = 'artikel_list_' . $__menuKey . '_v1';
        $__list = cache_get($__ck);
        if (!is_array($__list) || empty($__list)) {
            $__list = $am->getAll($__menuKey, publishedOnly: true);
            usort($__list, function($a, $b) {
                $ta = strtotime($a['published_at'] ?? $a['created_at'] ?? '0');
                $tb = strtotime($b['published_at'] ?? $b['created_at'] ?? '0');
                return $tb - $ta;
            });
            if (!empty($__list)) cache_set($__ck, $__list, 300);
        }
    }
    $__lbl = SupabaseArticleManager::MENU_LABELS[$__menuKey];
    foreach ($__list as $__a) {
        $__tgl = !empty($__a['published_at']) ? SupabaseArticleManager::formatTanggal($__a['published_at']) : (!empty($__a['created_at']) ? SupabaseArticleManager::formatTanggal($__a['created_at']) : '');
        $allArtikelSearch[] = [
            'judul'  => html_entity_decode($__a['judul'] ?? '', ENT_QUOTES|ENT_HTML5, 'UTF-8'),
            'tgl'    => $__tgl,
            'tglRaw' => $__a['published_at'] ?? $__a['created_at'] ?? '',
            'url'    => '/artikel/' . $__menuKey . '/' . rawurlencode($__a['slug'] ?? $__a['id']),
            'ringkas'=> mb_substr(html_entity_decode(strip_tags($__a['ringkasan'] ?? ''), ENT_QUOTES|ENT_HTML5, 'UTF-8'), 0, 110, 'UTF-8'),
            'thumb'  => !empty($__a['thumbnail']) ? artikelImageUrl($__a['thumbnail']) : '',
            'label'  => $__lbl,
            'menu'   => $__menuKey,
        ];
    }
}
// Urutkan gabungan berdasar tanggal terbaru agar hasil pencarian rapi
usort($allArtikelSearch, function($a, $b) {
    return strtotime($b['tglRaw'] ?: '0') - strtotime($a['tglRaw'] ?: '0');
});

// Base URL untuk pagination — menggunakan path, bukan query string
// Format: /artikel/berita/page/2  (tidak ada ?hal= agar InfinityFree tidak bermasalah)
$baseUrl  = '/artikel/' . rawurlencode($menu);
$pageUrl  = function(int $p) use ($baseUrl): string {
    return $p <= 1 ? $baseUrl : $baseUrl . '/page/' . $p;
};

$menuDesc = [
    'berita'   => 'Liputan berita dan artikel terkini dari Paroki Santa Maria Dengan Tidak Bernoda Asal Tulungagung.',
    'kronik'   => 'Kronik perjalanan sejarah dan kegiatan Paroki Tulungagung dari masa ke masa.',
    'historia' => 'Sejarah dan historia Gereja Katolik Santa Maria Dengan Tidak Bernoda Asal Tulungagung.',
];

// Halaman ini sendiri — dipakai supaya setelah Daftar → Login, kontributor
// kembali persis ke halaman artikel yang sedang dibuka saat klik "Daftar Sekarang".
$contributorRedirect = $pageUrl($page);
$contributorRegisterUrl = '/admin/register.php?redirect=' . urlencode($contributorRedirect);

$seo = [
    'title'       => $label . ($page > 1 ? ' – Halaman '.$page : '') . ' – Paroki Tulungagung',
    'description' => $menuDesc[$menu] ?? "Artikel {$label} Paroki Tulungagung.",
    'canonical'   => 'https://www.parokitulungagung.org' . $pageUrl($page),
    'keywords'    => "paroki smdtba, {$label}, gereja katolik tulungagung, artikel paroki",
];
$breadcrumbs = [
    ['name' => 'Beranda', 'url' => 'https://www.parokitulungagung.org'],
    ['name' => $label,    'url' => 'https://www.parokitulungagung.org' . $baseUrl],
];
if ($page > 1) $breadcrumbs[] = ['name' => 'Halaman '.$page];
$extraCss = ['/css/artikel.css'];

// ── JSON-LD: ItemList (wajib untuk Google rich results listing artikel) ──
$itemListSchema = null;
if (!empty($sliced)) {
    $_listItems = [];
    foreach ($sliced as $_idx => $_art) {
        $_artSlug = $_art['slug'] ?? $_art['id'];
        $_listItems[] = [
            '@type'    => 'ListItem',
            'position' => (($page - 1) * $perPage) + $_idx + 1,
            'url'      => 'https://www.parokitulungagung.org/artikel/' . $menu . '/' . rawurlencode($_artSlug),
            'name'     => html_entity_decode($_art['judul'] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8'),
        ];
    }
    $itemListSchema = [
        '@context'        => 'https://schema.org',
        '@type'           => 'ItemList',
        'name'            => $label . ' – Paroki Tulungagung',
        'description'     => $menuDesc[$menu] ?? "Artikel {$label} Paroki Tulungagung.",
        'url'             => 'https://www.parokitulungagung.org' . $baseUrl,
        'numberOfItems'   => $total,
        'itemListElement' => $_listItems,
    ];
}
?>
<!doctype html>
<html lang="id">
<head>
  <?php include __DIR__ . '/../components/seo_head.php'; ?>
  <?php if ($page > 1): ?><link rel="prev" href="<?= e('https://www.parokitulungagung.org' . $pageUrl($page-1)) ?>"><?php endif; ?>
  <?php if ($page < $pages): ?><link rel="next" href="<?= e('https://www.parokitulungagung.org' . $pageUrl($page+1)) ?>"><?php endif; ?>
  <?php if (!empty($itemListSchema)): ?>
  <script type="application/ld+json">
  <?= json_encode($itemListSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?>
  </script>
  <?php endif; ?>
  <script>
  var ARTIKEL_ALL = <?php echo json_encode($allArtikelSearch, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
  </script>
</head>
<body>
<?php $headerTitle = $label; include __DIR__ . '/../components/page_header.php'; ?>

<?php
// ── Tombol "Tulis Artikel" (menggantikan ajakan daftar) untuk yang sudah login ──
$writeArtikelUrl = null;
if (!empty($GLOBALS['__adminLoggedIn']) && !empty($GLOBALS['__adminUser'])) {
    $__u = $GLOBALS['__adminUser'];
    if ($__u['role'] === ROLE_SUPERADMIN) {
        $writeArtikelUrl = '/admin/pages/artikel-editor.php?menu=' . $menu;
    } else {
        $__perms = getPermissionsMap($__u);
        $writeArtikelUrl = array_key_exists($menu, $__perms)
            ? '/admin/pages/artikel-editor.php?menu=' . $menu
            : ($GLOBALS['__adminArtikelUrl'] ?? null);
    }
}
?>

  <main style="padding:6px 8px 20px">
    <div class="art-page-header">
      <div class="art-page-title-wrap">
        <h1 class="art-page-title"><?= e($label) ?></h1>
        <p class="art-page-sub">Paroki Santa Maria Dengan Tidak Bernoda Asal Tulungagung</p>
      </div>
      <?php if (!empty($GLOBALS['__adminLoggedIn'])): ?>
        <?php if ($writeArtikelUrl): ?>
        <a href="<?= e($writeArtikelUrl) ?>" class="art-write-badge" title="Tulis artikel baru">
          <span class="art-write-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
          </span>
          <span class="art-write-text">Tulis Artikel</span>
          <svg class="art-write-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="12" height="12" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
        </a>
        <?php endif; ?>
      <?php else: ?>
      <div class="art-contributor-badge">
        <span class="art-contributor-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
        </span>
        <span class="art-contributor-text">Ingin berkontribusi sebagai penulis?</span>
        <a href="<?= e($contributorRegisterUrl) ?>" class="art-contributor-btn" title="Daftar sebagai kontributor artikel">
          Daftar Sekarang
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="11" height="11" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
        </a>
      </div>
      <?php endif; ?>
    </div>

    <!-- SUB-MENU: Artikel / Kronik / Historia (link asli, URL tidak berubah demi SEO) -->
    <nav class="art-submenu" aria-label="Kategori Artikel">
      <?php foreach (SupabaseArticleManager::MENUS as $__m): ?>
      <a href="/artikel/<?= e($__m) ?>"
         class="art-submenu-item<?= $__m === $menu ? ' active' : '' ?>">
        <?= e(SupabaseArticleManager::MENU_LABELS[$__m]) ?>
      </a>
      <?php endforeach; ?>
    </nav>


    <!-- ARTIKEL SEARCH BAR -->
    <style>
    .art-search-wrap{margin:0 0 18px;padding:0 2px;}
    .art-search-box{display:flex;align-items:center;gap:10px;background:#fff;border:1.5px solid #e8e0f0;border-radius:14px;padding:10px 14px;box-shadow:0 2px 12px rgba(91,44,111,.07);transition:border-color .2s,box-shadow .2s;}
    .art-search-box:focus-within{border-color:#7c3aed;box-shadow:0 0 0 3px rgba(124,58,237,.13),0 2px 12px rgba(91,44,111,.1);}
    .art-search-icon{flex-shrink:0;color:#9b7ec8;display:flex;}
    .art-search-input{flex:1;border:none;outline:none;background:transparent;font-size:.97rem;color:#2d1a4e;font-family:inherit;}
    .art-search-input::placeholder{color:#bba8d4;}
    .art-search-clear{flex-shrink:0;display:none;align-items:center;justify-content:center;width:22px;height:22px;border-radius:50%;background:#e8e0f0;border:none;cursor:pointer;color:#7c3aed;font-size:15px;line-height:1;transition:background .15s,transform .15s;padding:0;}
    .art-search-clear:hover{background:#d4c5f0;transform:scale(1.1);}
    .art-search-clear.visible{display:flex;}
    .art-search-info{margin-top:7px;font-size:.83rem;color:#9b7ec8;padding-left:4px;min-height:18px;transition:opacity .2s;}
    #artSearchResults{margin-top:0;}
    .art-search-empty{text-align:center;padding:48px 16px;color:#9b7ec8;font-size:.95rem;}
    .art-search-empty svg{display:block;margin:0 auto 12px;opacity:.4;}
    </style>

    <div class="art-search-wrap" id="artSearchWrap">
      <div class="art-search-box">
        <span class="art-search-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" width="18" height="18">
            <circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
          </svg>
        </span>
        <input type="search" id="artSearchInput" class="art-search-input"
               placeholder="Cari di Artikel, Kronik & Historia…" autocomplete="off" aria-label="Cari artikel di semua kategori">
        <button class="art-search-clear" id="artSearchClear" aria-label="Hapus pencarian">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" width="12" height="12">
            <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
          </svg>
        </button>
      </div>
      <div class="art-search-info" id="artSearchInfo"></div>
    </div>

    <div id="artSearchResults" style="display:none">
      <div class="art-pub-grid" id="artSearchGrid"></div>
      <div class="art-search-empty" id="artSearchEmpty" style="display:none">
        <svg viewBox="0 0 64 64" fill="none" stroke="#9b7ec8" stroke-width="2" width="48" height="48"><circle cx="28" cy="28" r="18"/><line x1="50" y1="50" x2="40" y2="40"/><line x1="22" y1="28" x2="34" y2="28"/></svg>
        Tidak ada artikel yang cocok dengan kata kunci &ldquo;<span id="artSearchKeyword"></span>&rdquo;
      </div>
    </div>

    <!-- Skeleton loading -->
    <div id="artSkeleton" class="art-skeleton-grid" style="margin-bottom:1.5rem">
      <?php for ($i = 0; $i < min(8, $perPage); $i++): ?>
      <div class="art-skeleton-card">
        <div class="art-skeleton-thumb"></div>
        <div class="art-skeleton-body">
          <span class="art-skeleton-line title"></span>
          <span class="art-skeleton-line title2"></span>
          <span class="art-skeleton-line text"></span>
          <span class="art-skeleton-line text2"></span>
          <span class="art-skeleton-line meta"></span>
        </div>
      </div>
      <?php endfor; ?>
    </div>

    <!-- Konten nyata -->
    <div id="artContent" style="display:none">
    <?php if (empty($sliced)): ?>
    <div class="art-empty"><div class="art-empty-icon">📄</div><p>Belum ada artikel yang dipublikasikan.</p></div>
    <?php else: ?>

    <div class="art-pub-grid">
      <?php foreach ($sliced as $art):
        if (!is_array($art)) continue;
        $artThumb   = !empty($art['thumbnail']) ? artikelImageUrl($art['thumbnail']) : '';
        $artRingkas = trim(mb_substr(html_entity_decode(strip_tags($art['ringkasan'] ?? ''), ENT_QUOTES|ENT_HTML5, 'UTF-8'), 0, 120));
        $artJudul   = html_entity_decode($art['judul'] ?? '', ENT_QUOTES|ENT_HTML5, 'UTF-8');
        $artTanggal = !empty($art['published_at'])
            ? SupabaseArticleManager::formatTanggal($art['published_at'])
            : (!empty($art['created_at']) ? SupabaseArticleManager::formatTanggal($art['created_at']) : '');
        $artSlug    = $art['slug'] ?? $art['id'];
        $artUrl     = '/artikel/' . $menu . '/' . rawurlencode($artSlug);
        $artTags    = SupabaseArticleManager::tagsToArray($art['tags'] ?? '');
      ?>
      <a class="art-pub-card" href="<?= e($artUrl) ?>">
        <div class="art-pub-thumb-wrap">
          <?php if ($artThumb): ?>
<img src="<?= e($artThumb) ?>?v=2" alt="<?= e($artJudul) ?>" class="art-pub-thumb"
     loading="lazy" decoding="async" width="400" height="250"
     onerror="this.src='<?= e(assetUrl('og-preview.webp')) ?>';this.onerror=null;">

<?php else: ?>

<img src="<?= e(assetUrl('og-preview.webp')) ?>" alt="Artikel"
     class="art-pub-thumb" loading="lazy" decoding="async" width="400" height="250">
          <?php endif; ?>
          <div class="art-pub-menu-badge"><?= e($label) ?></div>
        </div>
        <div class="art-pub-body">
          <h2 class="art-pub-title"><?= e($artJudul) ?></h2>
          <?php if ($artRingkas): ?><p class="art-pub-excerpt"><?= e($artRingkas) ?>…</p><?php endif; ?>
          <?php if (!empty($artTags)): ?>
          <div class="art-pub-tags">
            <?php foreach (array_slice($artTags, 0, 3) as $tag): ?>
            <span class="art-pub-tag"><?= e($tag) ?></span>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
          <div class="art-pub-meta">
            <?php if ($artTanggal): ?>
            <span class="art-pub-date">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
              <time datetime="<?= e($art['published_at'] ?? $art['created_at'] ?? '') ?>"><?= e($artTanggal) ?></time>
            </span>
            <?php endif; ?>
            <?php if (!empty($art['penulis'])): ?>
            <span class="art-pub-author">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12" aria-hidden="true"><circle cx="12" cy="7" r="4"/><path d="M5.5 21a7 7 0 0113 0"/></svg>
              <?= e($art['penulis']) ?>
            </span>
            <?php endif; ?>
            <?php if (!empty($art['view_count']) && $art['view_count'] > 0): ?>
            <span class="art-pub-views" title="Jumlah tampilan">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              <?php
                $vc = (int)$art['view_count'];
                if ($vc >= 1000000)      echo round($vc/1000000, 1) . ' jt';
                elseif ($vc >= 10000)    echo round($vc/1000) . ' rb';
                elseif ($vc >= 1000)     echo number_format($vc/1000, 1, ',', '.') . ' rb';
                else                     echo number_format($vc, 0, ',', '.');
              ?>
            </span>
            <?php endif; ?>
          </div>
        </div>
        <div class="art-pub-arrow" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
        </div>
      </a>
      <?php endforeach; ?>
    </div>

    <?php if ($pages > 1): ?>
    <nav class="art-pagination" aria-label="Navigasi halaman artikel">
      <?php if ($page > 1): ?>
      <a href="<?= e($pageUrl($page-1)) ?>#top" class="art-page-btn" rel="prev">‹ Sebelumnya</a>
      <?php endif; ?>
      <?php for ($i = 1; $i <= $pages; $i++): ?>
      <a href="<?= e($pageUrl($i)) ?>#top"
         class="art-page-btn <?= $i === $page ? 'active' : '' ?>"
         <?= $i === $page ? 'aria-current="page"' : '' ?>><?= $i ?></a>
      <?php endfor; ?>
      <?php if ($page < $pages): ?>
      <a href="<?= e($pageUrl($page+1)) ?>#top" class="art-page-btn" rel="next">Berikutnya ›</a>
      <?php endif; ?>
    </nav>
    <?php endif; ?>
    <?php endif; ?>
    </div><!-- /#artContent -->
  </main>

</div><!-- /#outer-wrapper -->
<?php include __DIR__ . '/../components/footer.php'; ?>
<script src="/js/app.js?v=<?= substr(md5_file(__DIR__ . '/../js/app.js'), 0, 8) ?>"></script>
<script>
// Override: link kontributor artikel memakai redirect URL dinamis, bukan default app.js
window.openWhatsApp = function () {
  window.location.href = <?= json_encode($contributorRegisterUrl) ?>;
};
</script>
<script>
(function() {
  var sk = document.getElementById('artSkeleton');
  var ct = document.getElementById('artContent');
  if (sk && ct) {
    sk.style.display = 'none';
    ct.style.display = '';
    ct.classList.add('content-loaded');
  }
})();

/* Search Artikel client-side */
(function(){
  var searchInput = document.getElementById('artSearchInput');
  var searchClear = document.getElementById('artSearchClear');
  var searchInfo  = document.getElementById('artSearchInfo');
  var searchRes   = document.getElementById('artSearchResults');
  var searchGrid  = document.getElementById('artSearchGrid');
  var searchEmpty = document.getElementById('artSearchEmpty');
  var searchKw    = document.getElementById('artSearchKeyword');
  var artContent  = document.getElementById('artContent');
  var skeleton    = document.getElementById('artSkeleton');

  if (!searchInput || typeof ARTIKEL_ALL === 'undefined') return;

  function norm(s){ return (s||'').replace(/\s+/g,'').toLowerCase(); }

  function esc(s){
    return (s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  function makeCard(a){
    var defaultOg = <?= json_encode(assetUrl('og-preview.webp')) ?>;
    var thumb = a.thumb
      ? '<img src="'+a.thumb+'?v=2" alt="'+esc(a.judul)+'" class="art-pub-thumb" loading="lazy" decoding="async" width="400" height="250" onerror="this.src=\''+defaultOg+'\';this.onerror=null;">'
      : '<img src="'+defaultOg+'" alt="Artikel" class="art-pub-thumb" loading="lazy" decoding="async" width="400" height="250">';
    var excerpt = a.ringkas ? '<p class="art-pub-excerpt">'+esc(a.ringkas)+'\u2026</p>' : '';
    var dateSvg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>';
    var arrowSvg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M5 12h14M12 5l7 7-7 7"/></svg>';
    return '<a class="art-pub-card" href="'+a.url+'">'
      +'<div class="art-pub-thumb-wrap">'+thumb+'<div class="art-pub-menu-badge art-pub-menu-badge--'+esc(a.menu||'')+'">'+esc(a.label)+'</div></div>'
      +'<div class="art-pub-body"><h2 class="art-pub-title">'+esc(a.judul)+'</h2>'+excerpt
      +'<div class="art-pub-meta">'+(a.tgl?'<span class="art-pub-date">'+dateSvg+'<time>'+esc(a.tgl)+'</time></span>':'')+'</div></div>'
      +'<div class="art-pub-arrow" aria-hidden="true">'+arrowSvg+'</div></a>';
  }

  function doSearch(raw){
    var q = norm(raw);
    if (!q){
      searchRes.style.display = 'none';
      searchGrid.innerHTML    = '';
      searchInfo.textContent  = '';
      searchClear.classList.remove('visible');
      if (skeleton)   skeleton.style.display   = 'none';
      if (artContent) artContent.style.display = '';
      return;
    }
    searchClear.classList.add('visible');
    var matched = ARTIKEL_ALL.filter(function(a){
      return norm(a.judul).indexOf(q)!==-1 || (norm(a.tgl)+norm(a.tglRaw||'')).indexOf(q)!==-1;
    });
    if (skeleton)   skeleton.style.display   = 'none';
    if (artContent) artContent.style.display = 'none';
    searchRes.style.display = '';
    if (matched.length === 0){
      searchGrid.innerHTML = '';
      searchEmpty.style.display = '';
      if (searchKw) searchKw.textContent = raw.trim();
      searchInfo.textContent = 'Tidak ada hasil';
    } else {
      searchEmpty.style.display = 'none';
      searchInfo.textContent    = matched.length + ' artikel ditemukan';
      searchGrid.innerHTML      = matched.map(makeCard).join('');
    }
  }

  var debounceTimer;
  searchInput.addEventListener('input', function(){
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(function(){ doSearch(searchInput.value); }, 220);
  });
  searchClear.addEventListener('click', function(){
    searchInput.value = ''; doSearch(''); searchInput.focus();
  });
  searchInput.addEventListener('keydown', function(e){
    if (e.key==='Escape'){ searchInput.value=''; doSearch(''); }
  });
})();

</script>
</body>
</html>