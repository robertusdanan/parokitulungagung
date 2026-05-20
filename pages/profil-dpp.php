<?php
require_once __DIR__ . '/../includes/functions.php';

$data      = fetchSupabaseCached('kepengurusan_dpp_bgkp', [], 'id.asc');
$dataError = ($data === null) ? 'Gagal mengambil data dari server.' : null;

function dpp_getAllPeriodes(array $data): array {
    $set = [];
    foreach ($data as $row) { $p=trim($row['Periode']??''); if(preg_match('/^\d{4}-\d{4}$/',$p)) $set[$p]=true; }
    $list = array_keys($set); rsort($list); return $list;
}
function dpp_resolveActivePeriode(array $data): string {
    $all = dpp_getAllPeriodes($data);
    $fromUrl = trim($_GET['periode'] ?? '');
    if ($fromUrl && preg_match('/^\d{4}-\d{4}$/',$fromUrl) && in_array($fromUrl,$all)) return $fromUrl;
    $year = (int) date('Y');
    foreach ($all as $p) { [$ps,$pe]=explode('-',$p); if($year>=(int)$ps&&$year<=(int)$pe) return $p; }
    return $all[0] ?? '';
}

$allPeriodes   = is_array($data) ? dpp_getAllPeriodes($data) : [];
$activePeriode = is_array($data) ? dpp_resolveActivePeriode($data) : '';
$filtered      = is_array($data) ? array_filter($data, fn($r) => ($r['Periode']??'')=='' || ($r['Periode']??'')===$activePeriode) : [];

$seo = [
    'title'       => 'DPP & BGKP – Dewan Pastoral Paroki Tulungagung',
    'description' => 'Kepengurusan Dewan Pastoral Paroki (DPP) dan BGKP Paroki Santa Maria Dengan Tidak Bernoda Asal Tulungagung.',
    'canonical'   => 'https://www.parokitulungagung.org/profil-dpp',
    'keywords'    => 'dewan pastoral paroki tulungagung, dpp bgkp, kepengurusan gereja katolik tulungagung, paroki smdtba',
    'type'        => 'website',
];
$breadcrumbs = [
    ['name'=>'Beranda','url'=>'https://www.parokitulungagung.org'],
    ['name'=>'DPP & BGKP','url'=>'https://www.parokitulungagung.org/profil-dpp'],
];
$extraCss = ['/css/content.css'];
?>
<!doctype html>
<html lang="id">
<head>
  <?php include __DIR__ . '/../components/seo_head.php'; ?>
  <script type="application/ld+json">
  <?= json_encode([
    '@context'=>'https://schema.org','@type'=>'WebPage',
    'name'=>'DPP & BGKP Paroki Tulungagung',
    'description'=>'Kepengurusan DPP dan BGKP Paroki Santa Maria Dengan Tidak Bernoda Asal Tulungagung.',
    'url'=>'https://www.parokitulungagung.org/profil-dpp',
    'inLanguage'=>'id','isPartOf'=>['@id'=>'https://www.parokitulungagung.org/#website'],
  ], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT); ?>
  </script>
</head>
<body>
<?php $headerTitle = 'DPP & BGKP'; include __DIR__ . '/../components/page_header.php'; ?>
<?php include __DIR__ . '/../components/photo_modal.php'; ?>

<main id="main-content" style="padding:6px">


  <div class="page-hero">
    <div class="page-hero-icon">
      <img src="/img/icon/icon_square_dpp.png" alt="" loading="lazy" width="40" height="40">
    </div>
    <div class="page-hero-text">
      <h1>Kepengurusan DPP &amp; BGKP</h1>
      <p>Paroki Santa Maria Dengan Tidak Bernoda Asal · Tulungagung</p>
    </div>
  </div>


  <?php if ($dataError): ?>
  <div style="text-align:center;padding:40px;"><h2 style="color:#d32f2f">⚠️ <?= e($dataError) ?></h2></div>
  <?php else: ?>
  <div style="padding:0 6px">
    <?php if (count($allPeriodes) > 1): ?>
    <div class="periode-selector-wrap" id="periodeWrap">
      <div class="periode-badge periode-badge--clickable" id="periodeTrigger" onclick="togglePeriodeDropdown(event)" role="button" tabindex="0" aria-haspopup="listbox" aria-expanded="false">
        <span class="periode-dot"></span>
        <span class="periode-label">Periode <?= e(str_replace('-','–',$activePeriode)) ?></span>
        <svg class="periode-chevron" id="periodeChevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
      </div>
      <div class="periode-dropdown" id="periodeDropdown" role="listbox">
        <div class="periode-dropdown-header">Pilih Periode Kepengurusan</div>
        <?php $year=(int)date('Y'); foreach($allPeriodes as $p): $isActive=($p===$activePeriode); [$ps,$pe]=explode('-',$p); $isCurrent=($year>=(int)$ps&&$year<=(int)$pe); ?>
        <a href="/profil-dpp?periode=<?= urlencode($p) ?>" class="periode-dropdown-item <?= $isActive?'periode-dropdown-item--active':'' ?>" role="option" aria-selected="<?= $isActive?'true':'false' ?>">
          <span class="periode-dropdown-check"><?php if($isActive): ?><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="13" height="13"><polyline points="20 6 9 17 4 12"></polyline></svg><?php endif; ?></span>
          <span class="periode-dropdown-text"><?= e(str_replace('-','–',$p)) ?></span>
          <?php if($isCurrent): ?><span class="periode-now-badge">Saat ini</span><?php endif; ?>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <ul class="tabs" id="tabs-dpp">
      <li><a href="#tab-dpp">DPP</a></li>
      <li><a href="#tab-bgkp">BGKP</a></li>
    </ul>
    <div class="tabcontents" id="tabcontents-dpp" style="padding:2rem;background:var(--white);border-radius:0 12px 12px 12px;box-shadow:0 4px 16px rgba(91,44,111,0.12)">

      <?php
      function renderDppTipe(array $allData, string $tipe): void {
          $byTipe  = array_filter($allData, fn($i) => ($i['Tipe']??'')===$tipe);
          $grouped = [];
          foreach ($byTipe as $i) $grouped[$i['Bidang']??'Lainnya'][] = $i;
          foreach ($grouped as $bidang => $persons):
              $ketua  = current(array_filter($persons, fn($p) => stripos($p['Posisi']??'','ketua')!==false)) ?: [];
              $wakil  = current(array_filter($persons, fn($p) => stripos($p['Posisi']??'','wakil')!==false)) ?: [];
              $others = array_filter($persons, fn($p) => $p!==$ketua && $p!==$wakil);
              $imgKetua = !empty($ketua['Foto']) ? '/img/person/'.$ketua['Foto'] : '';
              $imgWakil = !empty($wakil['Foto']) ? '/img/person/'.$wakil['Foto'] : '';
      ?>
          <div class="galeri-accordion" style="margin-bottom:8px">
            <div class="galeri-accordion-header">
              <div class="galeri-accordion-title">
                <span class="galeri-accordion-month"><?= e($bidang) ?></span>
                <span class="galeri-accordion-hint">Klik untuk lihat anggota</span>
              </div>
              <svg class="galeri-accordion-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
            </div>
            <div class="galeri-accordion-body"><div class="galeri-accordion-inner">
              <?php if (!empty($ketua['Nama']) || !empty($wakil['Nama'])): ?>
              <div class="profile-leader-card">
                <div class="profile-leader-info">
                  <?php if(!empty($ketua['Nama'])): ?><div><span class="profile-leader-role">Ketua</span> <span class="profile-leader-name"><?= e($ketua['Nama']) ?></span></div><?php endif; ?>
                  <?php if(!empty($wakil['Nama'])): ?><div><span class="profile-leader-role">Wakil</span> <span class="profile-leader-name"><?= e($wakil['Nama']) ?></span></div><?php endif; ?>
                </div>
                <div class="profile-leader-photos">
                  <?php if(!empty($ketua['Nama'])): ?>
                  <?php if($imgKetua): ?><img src="<?= e($imgKetua) ?>" class="profile-leader-img" onclick="ShowPhotoBox('<?= e($ketua['Nama']) ?>','<?= e($imgKetua) ?>','<?= e($bidang) ?>','Ketua')" alt="<?= e($ketua['Nama']) ?>" loading="lazy" decoding="async" width="120" height="120" onerror="this.style.opacity='0.3'">
                  <?php else: ?><div class="profile-leader-img" style="display:flex;align-items:center;justify-content:center;background:rgba(201,168,76,.1);color:#c9a84c;font-size:20px;font-weight:700;font-family:'Cormorant Garamond',serif"><?= strtoupper(mb_substr($ketua['Nama'],0,1)) ?></div><?php endif; ?>
                  <?php endif; ?>
                  <?php if(!empty($wakil['Nama'])): ?>
                  <?php if($imgWakil): ?><img src="<?= e($imgWakil) ?>" class="profile-leader-img" onclick="ShowPhotoBox('<?= e($wakil['Nama']) ?>','<?= e($imgWakil) ?>','<?= e($bidang) ?>','Wakil')" alt="<?= e($wakil['Nama']) ?>" loading="lazy" decoding="async" width="120" height="120" onerror="this.style.opacity='0.3'">
                  <?php else: ?><div class="profile-leader-img" style="display:flex;align-items:center;justify-content:center;background:rgba(201,168,76,.1);color:#c9a84c;font-size:20px;font-weight:700;font-family:'Cormorant Garamond',serif"><?= strtoupper(mb_substr($wakil['Nama'],0,1)) ?></div><?php endif; ?>
                  <?php endif; ?>
                </div>
              </div>
              <?php endif; ?>
              <?php foreach(array_values($others) as $idx=>$person):
                  $nama=$person['Nama']??''; $posisi=$person['Posisi']??''; $img=!empty($person['Foto'])?'/img/person/'.$person['Foto']:''; ?>
              <div class="profile-item" style="animation-delay:<?= $idx*35 ?>ms" onclick="<?= $img?"ShowPhotoBox('".e($nama)."','".e($img)."','".e($bidang)."','".e($posisi)."')":'' ?>">
                <?php if($img): ?><img class="profile-item-img" src="<?= e($img) ?>" alt="<?= e($nama) ?>" loading="lazy" decoding="async" width="80" height="80" onerror="this.style.opacity='0.3'">
                <?php else: ?><div class="profile-item-img" style="display:flex;align-items:center;justify-content:center;background:rgba(201,168,76,.1);color:#c9a84c;font-size:18px;font-weight:700;font-family:'Cormorant Garamond',serif"><?= strtoupper(mb_substr($nama,0,1)) ?></div><?php endif; ?>
                <div class="profile-item-info">
                  <div class="profile-item-role"><?= e($posisi) ?></div>
                  <div class="profile-item-name"><?= e($nama) ?></div>
                </div>
              </div>
              <?php endforeach; ?>
            </div></div>
          </div>
      <?php endforeach; }
      ?>

      <div id="tab-dpp"><?php renderDppTipe(array_values($filtered), 'DPP'); ?></div>
      <div id="tab-bgkp"><?php renderDppTipe(array_values($filtered), 'BGKP'); ?></div>
    </div>
  </div>
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
<script>
(function () {
  var _open = false;
  window.togglePeriodeDropdown = function (e) {
    e && e.stopPropagation(); _open = !_open;
    var t=document.getElementById('periodeTrigger'),d=document.getElementById('periodeDropdown'),c=document.getElementById('periodeChevron');
    if(!d) return;
    t&&t.setAttribute('aria-expanded',_open); t&&t.classList.toggle('periode-badge--open',_open);
    c&&c.classList.toggle('periode-chevron--open',_open); d.classList.toggle('periode-dropdown--open',_open);
  };
  document.addEventListener('click', function(e){ if(!_open) return; var w=document.getElementById('periodeWrap'); if(w&&!w.contains(e.target)){_open=false;var t=document.getElementById('periodeTrigger'),d=document.getElementById('periodeDropdown'),c=document.getElementById('periodeChevron');t&&t.setAttribute('aria-expanded',false);t&&t.classList.remove('periode-badge--open');c&&c.classList.remove('periode-chevron--open');d&&d.classList.remove('periode-dropdown--open');}});
})();
</script>
</body>
</html>
