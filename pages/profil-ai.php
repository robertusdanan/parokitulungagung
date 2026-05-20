<?php
require_once __DIR__ . '/../includes/functions.php';

$data      = fetchSupabaseCached('daftar_asisten_imam', [], 'Nama.asc');

// Guard: jika Supabase mengembalikan error-object (array tapi bukan data rows)
if (is_array($data) && isset($data['message'])) {
    $data = null;
}

$dataError = ($data === null) ? 'Gagal mengambil data dari server.' : null;

function ai_getAllPeriodes(array $data): array {
    $set = [];
    foreach ($data as $row) {
        $p = trim($row['Periode'] ?? '');
        if (preg_match('/^\d{4}-\d{4}$/', $p)) $set[$p] = true;
    }
    $list = array_keys($set);
    rsort($list);
    return $list;
}

function ai_resolveActivePeriode(array $data): string {
    $all     = ai_getAllPeriodes($data);
    $fromUrl = trim($_GET['periode'] ?? '');
    if ($fromUrl && preg_match('/^\d{4}-\d{4}$/', $fromUrl) && in_array($fromUrl, $all)) return $fromUrl;
    $year = (int) date('Y');
    foreach ($all as $p) {
        [$ps, $pe] = explode('-', $p);
        if ($year >= (int)$ps && $year <= (int)$pe) return $p;
    }
    return $all[0] ?? '';
}

$allPeriodes   = is_array($data) ? ai_getAllPeriodes($data) : [];
$activePeriode = is_array($data) ? ai_resolveActivePeriode($data) : '';
$hasMultiple   = count($allPeriodes) > 1;

$filtered = [];
if (is_array($data)) {
    foreach ($data as $r) {
        // Pastikan $r adalah array (bukan string/null dari response Supabase yang aneh)
        if (!is_array($r)) continue;
        $p = $r['Periode'] ?? '';
        if ($p === '' || $p === $activePeriode) $filtered[] = $r;
    }
}

$items = [];
foreach ($filtered as $r) {
    if (!empty($r['Nama']) && !empty($r['Asal Lingk / Stasi'])) $items[] = $r;
}
usort($items, fn($a,$b) =>
    strcmp($a['Asal Lingk / Stasi'] ?? '', $b['Asal Lingk / Stasi'] ?? '') ?:
    strcmp($a['Nama'] ?? '', $b['Nama'] ?? '')
);

$wilList = [];
foreach ($items as $i) {
    $w = $i['Asal Lingk / Stasi'] ?? '';
    if ($w !== '' && !in_array($w, $wilList)) $wilList[] = $w;
}

$seo = [
    'title'       => 'Daftar Asisten Imam – Gereja Katolik Paroki Tulungagung',
    'description' => 'Daftar Asisten Imam Paroki Santa Maria Dengan Tidak Bernoda Asal Tulungagung beserta wilayah pelayanannya.',
    'canonical'   => 'https://www.parokitulungagung.org/profil-ai',
    'keywords'    => 'asisten imam paroki tulungagung, daftar asisten imam, pastor pembantu gereja tulungagung, paroki smdtba',
    'type'        => 'website',
];
$breadcrumbs = [
    ['name' => 'Beranda',      'url' => 'https://www.parokitulungagung.org'],
    ['name' => 'Asisten Imam', 'url' => 'https://www.parokitulungagung.org/profil-ai'],
];
$extraCss = ['/css/content.css'];
?>
<!doctype html>
<html lang="id">
<head>
  <?php include __DIR__ . '/../components/seo_head.php'; ?>
  <script type="application/ld+json">
  <?= json_encode([
    '@context'    => 'https://schema.org',
    '@type'       => 'WebPage',
    'name'        => 'Daftar Asisten Imam Paroki Tulungagung',
    'description' => 'Daftar Asisten Imam Paroki Santa Maria Dengan Tidak Bernoda Asal Tulungagung beserta wilayah pelayanannya.',
    'url'         => 'https://www.parokitulungagung.org/profil-ai',
    'inLanguage'  => 'id',
    'isPartOf'    => ['@id' => 'https://www.parokitulungagung.org/#website'],
  ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT); ?>
  </script>
  <style>
  /* Override: teks nama di card profil harus terbaca di atas background gelap */
  .profile-item-name { color: rgba(255,255,255,0.90) !important; }
  .profile-item:hover .profile-item-name { color: rgba(0,0,0,0.75) !important; }
  .profile-item:hover .profile-item-role { color: #8a6010 !important; }
  </style>
</head>
<body>
<?php $headerTitle = 'Asisten Imam'; include __DIR__ . '/../components/page_header.php'; ?>
<?php include __DIR__ . '/../components/photo_modal.php'; ?>

<main id="main-content" style="padding:6px">


  <div class="page-hero">
    <div class="page-hero-icon">
      <img src="/img/icon/icon_square_ai.png" alt="" loading="lazy" width="40" height="40">
    </div>
    <div class="page-hero-text">
      <h1>Asisten Imam</h1>
      <p>Paroki Santa Maria Dengan Tidak Bernoda Asal · Tulungagung</p>
    </div>
  </div>


  <?php if ($dataError): ?>
  <div style="text-align:center;padding:40px;">
    <h2 style="color:#d32f2f">⚠️ <?= e($dataError) ?></h2>
    <p>Silakan coba lagi atau hubungi administrator.</p>
  </div>
  <?php else: ?>

  <div style="padding:8px;">

    <?php if ($activePeriode): ?>
    <div class="periode-selector-wrap" id="periodeWrap">
      <div class="periode-badge <?= $hasMultiple ? 'periode-badge--clickable' : '' ?>"
           id="periodeTrigger"
           <?= $hasMultiple ? 'onclick="togglePeriodeDropdown(event)"' : '' ?>
           <?= $hasMultiple ? 'role="button" tabindex="0"' : '' ?>
           <?= $hasMultiple ? 'aria-haspopup="listbox" aria-expanded="false"' : '' ?>>
        <span class="periode-dot"></span>
        <span class="periode-label">Periode <?= e(str_replace('-', '–', $activePeriode)) ?></span>
        <?php if ($hasMultiple): ?>
        <svg class="periode-chevron" id="periodeChevron" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="6 9 12 15 18 9"></polyline>
        </svg>
        <?php endif; ?>
      </div>
      <?php if ($hasMultiple): ?>
      <div class="periode-dropdown" id="periodeDropdown" role="listbox">
        <div class="periode-dropdown-header">Pilih Periode Kepengurusan</div>
        <?php
        $year = (int) date('Y');
        foreach ($allPeriodes as $p):
            $isActive  = ($p === $activePeriode);
            [$ps, $pe] = explode('-', $p);
            $isCurrent = ($year >= (int)$ps && $year <= (int)$pe);
        ?>
        <a href="/profil-ai?periode=<?= urlencode($p) ?>"
           class="periode-dropdown-item <?= $isActive ? 'periode-dropdown-item--active' : '' ?>"
           role="option" aria-selected="<?= $isActive ? 'true' : 'false' ?>">
          <span class="periode-dropdown-check">
            <?php if ($isActive): ?>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                 width="13" height="13" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
            <?php endif; ?>
          </span>
          <span class="periode-dropdown-text"><?= e(str_replace('-', '–', $p)) ?></span>
          <?php if ($isCurrent): ?><span class="periode-now-badge">Saat ini</span><?php endif; ?>
        </a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="w3-container w3-card" style="background-color:#e0e9ee;padding:10px;">

      <?php if (!empty($wilList)): ?>
      <div style="margin-bottom:10px">
        <label for="kringselect" style="font-weight:bold;margin-right:10px;">Pilih Wilayah/Stasi:</label>
        <select id="kringselect" onchange="filterByWilayah(this.value)"
                style="padding:8px;border-radius:4px;border:1px solid #ccc;">
          <option value="all">Semua</option>
          <?php foreach ($wilList as $w): ?>
          <option value="<?= e($w) ?>"><?= e($w) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php endif; ?>

      <div id="konten-asistenimam">
        <?php if (empty($items)): ?>
        <p style="text-align:center;padding:20px;color:#888">Belum ada data asisten imam.</p>
        <?php else: ?>
        <?php foreach ($items as $item):
            $nama    = (string)($item['Nama'] ?? '');
            $wilayah = (string)($item['Asal Lingk / Stasi'] ?? '');
            $fotoCol = trim((string)($item['Foto'] ?? ''));
            $foto    = $fotoCol ? '/img/person/' . $fotoCol : '';
            // Gunakan single-quote JS string agar tidak bentrok dengan atribut HTML double-quote.
            // Escape backslash dan single-quote di dalam nilai, lalu bungkus dengan '...'
            $jsEsc = fn(string $s): string =>
                "'" . strtr($s, ["'" => "\\'", '\\' => '\\\\']) . "'";
            $onclick = $foto
                ? 'ShowPhotoBox(' . $jsEsc($nama) . ',' . $jsEsc($foto) . ",\\'ASISTEN IMAM\\'," . $jsEsc($wilayah) . ')'
                : '';
        ?>
        <div class="profile-item"
             data-wilayah="<?= e($wilayah) ?>"
             <?= $onclick ? 'onclick="' . $onclick . '"' : '' ?>>
          <?php if ($foto): ?>
          <img class="profile-item-img" src="<?= e($foto) ?>" alt="<?= e($nama) ?>"
               loading="lazy" decoding="async" width="80" height="80" onerror="this.style.opacity='0.3'">
          <?php else: ?>
          <div class="profile-item-img"
               style="display:flex;align-items:center;justify-content:center;background:rgba(201,168,76,.1);color:#c9a84c;font-size:18px;font-weight:700;font-family:'Cormorant Garamond',serif">
            <?= e(strtoupper(mb_substr($nama, 0, 1))) ?>
          </div>
          <?php endif; ?>
          <div class="profile-item-info">
            <div class="profile-item-role"><?= e($wilayah) ?></div>
            <div class="profile-item-name"><?= e($nama) ?></div>
          </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
      </div><!-- /#konten-asistenimam -->

    </div><!-- /.w3-container -->
  </div><!-- /padding -->

  <?php endif; ?>

</main>

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
<?php if ($hasMultiple): ?>
<script>
/* togglePeriodeDropdown — tidak ada di app.js, didefinisikan inline */
(function () {
    var _open = false;
    window.togglePeriodeDropdown = function (e) {
        e && e.stopPropagation();
        _open = !_open;
        var trigger  = document.getElementById('periodeTrigger');
        var dropdown = document.getElementById('periodeDropdown');
        var chevron  = document.getElementById('periodeChevron');
        if (!dropdown) return;
        trigger  && trigger.setAttribute('aria-expanded', _open);
        trigger  && trigger.classList.toggle('periode-badge--open', _open);
        chevron  && chevron.classList.toggle('periode-chevron--open', _open);
        dropdown.classList.toggle('periode-dropdown--open', _open);
    };
    document.addEventListener('click', function (e) {
        if (!_open) return;
        var wrap = document.getElementById('periodeWrap');
        if (wrap && !wrap.contains(e.target)) {
            _open = false;
            var trigger  = document.getElementById('periodeTrigger');
            var dropdown = document.getElementById('periodeDropdown');
            var chevron  = document.getElementById('periodeChevron');
            trigger  && trigger.setAttribute('aria-expanded', false);
            trigger  && trigger.classList.remove('periode-badge--open');
            chevron  && chevron.classList.remove('periode-chevron--open');
            dropdown && dropdown.classList.remove('periode-dropdown--open');
        }
    });
    var trigger = document.getElementById('periodeTrigger');
    trigger && trigger.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); window.togglePeriodeDropdown(e); }
        if (e.key === 'Escape' && _open) window.togglePeriodeDropdown(e);
    });
})();
</script>
<?php endif; ?>
</body>
</html>