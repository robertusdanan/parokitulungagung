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

// Data semua artikel untuk search
$allArtikelSearch = [];
foreach ($articles as $__a) {
    $__tgl = !empty($__a['published_at']) ? SupabaseArticleManager::formatTanggal($__a['published_at']) : (!empty($__a['created_at']) ? SupabaseArticleManager::formatTanggal($__a['created_at']) : '');
    $allArtikelSearch[] = [
        'judul'  => html_entity_decode($__a['judul'] ?? '', ENT_QUOTES|ENT_HTML5, 'UTF-8'),
        'tgl'    => $__tgl,
        'tglRaw' => $__a['published_at'] ?? $__a['created_at'] ?? '',
        'url'    => '/artikel/' . $menu . '/' . rawurlencode($__a['slug'] ?? $__a['id']),
        'ringkas'=> mb_substr(html_entity_decode(strip_tags($__a['ringkasan'] ?? ''), ENT_QUOTES|ENT_HTML5, 'UTF-8'), 0, 110, 'UTF-8'),
        'thumb'  => $__a['thumbnail'] ?? '',
        'label'  => $label,
    ];
}

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

  <main style="padding:6px 8px 20px">
    <div class="art-page-header">
      <div class="art-page-title-wrap">
        <h1 class="art-page-title"><?= e($label) ?></h1>
        <p class="art-page-sub">Paroki Santa Maria Dengan Tidak Bernoda Asal Tulungagung</p>
      </div>
      <div class="art-contributor-badge">
        <span class="art-contributor-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
        </span>
        <span class="art-contributor-text">Ingin berkontribusi sebagai penulis?</span>
        <a href="/admin/register.php" class="art-contributor-btn" title="Daftar sebagai kontributor artikel">
          Daftar Sekarang
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="11" height="11" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
        </a>
      </div>
    </div>

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
               placeholder="Cari judul atau tanggal artikel…" autocomplete="off" aria-label="Cari artikel">
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
        $artThumb   = $art['thumbnail'] ?? '';
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
     onerror="this.src='/img/og-preview.webp?v=2';this.onerror=null;">

<?php else: ?>

<img src="/img/og-preview.webp?v=2" alt="Artikel"
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
    var thumb = a.thumb
      ? '<img src="'+a.thumb+'?v=2" alt="'+esc(a.judul)+'" class="art-pub-thumb" loading="lazy" decoding="async" width="400" height="250" onerror="this.src=\'/img/og-preview.webp?v=2\';this.onerror=null;">'
      : '<img src="/img/og-preview.webp?v=2" alt="Artikel" class="art-pub-thumb" loading="lazy" decoding="async" width="400" height="250">';
    var excerpt = a.ringkas ? '<p class="art-pub-excerpt">'+esc(a.ringkas)+'\u2026</p>' : '';
    var dateSvg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>';
    var arrowSvg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M5 12h14M12 5l7 7-7 7"/></svg>';
    return '<a class="art-pub-card" href="'+a.url+'">'
      +'<div class="art-pub-thumb-wrap">'+thumb+'<div class="art-pub-menu-badge">'+esc(a.label)+'</div></div>'
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