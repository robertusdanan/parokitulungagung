<?php
require_once __DIR__ . '/../includes/functions.php';

$seo = [
    'title'       => 'E-Lonceng Warta Digital – Paroki Tulungagung',
    'description' => 'Baca warta dan majalah digital E-Lonceng Paroki Santa Maria Dengan Tidak Bernoda Asal (SMDTBA) Tulungagung secara online. Terbit rutin setiap minggu berisi pengumuman, jadwal misa, renungan, dan kegiatan umat.',
    'canonical'   => 'https://www.parokitulungagung.org/e-lonceng',
    'keywords'    => 'e-lonceng, warta paroki, majalah digital paroki smdtba tulungagung, warta minggu, bulletin gereja',
    'type'        => 'website',
];
$breadcrumbs = [
    ['name' => 'Beranda', 'url' => 'https://www.parokitulungagung.org'],
    ['name' => 'E-Lonceng', 'url' => 'https://www.parokitulungagung.org/e-lonceng'],
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
    'name'        => 'E-Lonceng Warta Digital Paroki Tulungagung',
    'description' => 'Warta dan majalah digital Paroki Santa Maria Dengan Tidak Bernoda Asal Tulungagung. Terbit rutin berisi pengumuman, jadwal misa, renungan, dan kegiatan umat.',
    'url'         => 'https://www.parokitulungagung.org/e-lonceng',
    'inLanguage'  => 'id',
    'isPartOf'    => ['@id' => 'https://www.parokitulungagung.org/#website'],
    'about'       => [
      '@type'       => 'Periodical',
      'name'        => 'E-Lonceng',
      'description' => 'Warta digital mingguan Paroki Santa Maria Dengan Tidak Bernoda Asal Tulungagung',
      'publisher'   => [
        '@type' => 'Organization',
        'name'  => 'Paroki Santa Maria Dengan Tidak Bernoda Asal Tulungagung',
        'url'   => 'https://www.parokitulungagung.org',
      ],
      'inLanguage'  => 'id',
    ],
  ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT); ?>
  </script>
  <style>
  .elonceng-wrap { max-width: 900px; margin: 0 auto; padding: 0 12px 2rem; }

  /* ── Intro section — diindeks Google ── */
  .elonceng-intro {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
    margin: 1.4rem 0 1.8rem;
  }
  @media (max-width: 580px) { .elonceng-intro { grid-template-columns: 1fr; gap: 1rem; } }

  .elonceng-about {
    background: #fff;
    border: 1px solid #e8e0d4;
    border-radius: 12px;
    padding: 1.2rem 1.4rem;
  }
  .elonceng-about h2 {
    font-family: 'Cormorant Garamond', Georgia, serif;
    font-size: 1.15rem; font-weight: 700;
    color: #2c1a0e; margin: 0 0 .7rem;
  }
  .elonceng-about p {
    font-size: .85rem; line-height: 1.8;
    color: #4a3c2e; margin: 0 0 .55rem;
    font-family: 'Archivo Narrow', Arial, sans-serif;
  }
  .elonceng-about p:last-child { margin-bottom: 0; }

  .elonceng-rubriks {
    background: #faf7f2;
    border: 1px solid #e8e0d4;
    border-radius: 12px;
    padding: 1.2rem 1.4rem;
  }
  .elonceng-rubriks h2 {
    font-family: 'Cormorant Garamond', Georgia, serif;
    font-size: 1.15rem; font-weight: 700;
    color: #2c1a0e; margin: 0 0 .8rem;
  }
  .elonceng-rubrik-list { list-style: none; margin: 0; padding: 0; }
  .elonceng-rubrik-list li {
    display: flex; align-items: center; gap: .6rem;
    font-size: .83rem; color: #4a3c2e; padding: .35rem 0;
    border-bottom: 1px dashed #e4d9cc;
    font-family: 'Archivo Narrow', Arial, sans-serif;
  }
  .elonceng-rubrik-list li:last-child { border-bottom: 0; }
  .elonceng-rubrik-dot {
    width: 7px; height: 7px; border-radius: 50%;
    background: #b8963e; flex-shrink: 0;
  }

  /* ── Iframe reader ── */
  .elonceng-reader-wrap {
    background: #fff;
    border: 1px solid #e8e0d4;
    border-radius: 12px;
    overflow: hidden;
    margin-bottom: 1.5rem;
  }
  .elonceng-reader-head {
    display: flex; align-items: center; gap: .7rem;
    padding: .8rem 1.2rem;
    background: linear-gradient(135deg, #faf7f2, #f2ebe0);
    border-bottom: 1px solid #e8e0d4;
  }
  .elonceng-reader-head-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1rem; font-weight: 700; color: #2c1a0e;
  }
  .elonceng-reader-head-sub {
    font-size: .72rem; color: #8a7460;
    font-family: 'Montserrat', sans-serif;
    margin-left: auto;
  }
  .elonceng-iframe-wrap {
    position: relative;
    height: 75vh; min-height: 480px;
  }
  #iframe-loader {
    position: absolute; inset: 0;
    display: flex; flex-direction: column;
    align-items: center; justify-content: center; gap: 10px;
    background: #faf7f2;
    transition: opacity .35s ease;
  }
  #iframe-loader.hidden { opacity: 0; pointer-events: none; }
  .skel-block { height: 52px; width: 55%; border-radius: 6px; background: #e0d5c8; }
  .skel-line  { height: 12px; border-radius: 4px; background: #e0d5c8; }
  #framecontent {
    position: absolute; inset: 0;
    width: 100%; height: 100%; border: 0;
  }

  /* ── Cara baca section ── */
  .elonceng-howto {
    background: #fff;
    border: 1px solid #e8e0d4;
    border-radius: 12px;
    padding: 1.2rem 1.4rem;
    margin-bottom: 1rem;
  }
  .elonceng-howto h2 {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.1rem; font-weight: 700;
    color: #2c1a0e; margin: 0 0 .8rem;
  }
  .elonceng-steps {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: .8rem;
  }
  .elonceng-step {
    background: #faf7f2; border-radius: 8px;
    padding: .9rem 1rem;
    font-family: 'Archivo Narrow', Arial, sans-serif;
  }
  .elonceng-step-num {
    font-size: .65rem; font-weight: 700;
    color: #b8963e; letter-spacing: 1px;
    text-transform: uppercase; margin-bottom: .3rem;
  }
  .elonceng-step-text { font-size: .82rem; color: #4a3c2e; line-height: 1.6; }
  </style>
</head>
<body>
<?php $headerTitle = 'E-Lonceng'; include __DIR__ . '/../components/page_header.php'; ?>

<main id="main-content">
<div class="elonceng-wrap">

  <section class="elonceng-intro" aria-label="Tentang E-Lonceng">

    <div class="elonceng-about">
      <h2>Apa itu E-Lonceng?</h2>
      <p>
        <strong>E-Lonceng</strong> adalah warta digital resmi Paroki Santa Maria Dengan Tidak Bernoda Asal
        (SMDTBA) Tulungagung. E-Lonceng hadir sebagai pengganti buletin
        cetak yang dapat diakses kapan saja dan di mana saja melalui perangkat apapun.
      </p>
      <p>
        Nama <em>Lonceng</em> diambil dari tradisi gereja — bunyi lonceng yang mengundang umat untuk
        berkumpul dan bersatu dalam iman. Kini panggilan itu hadir dalam format digital.
      </p>
      <p>
        E-Lonceng diterbitkan oleh Komisi Komunikasi Sosial (Komsos) Paroki Tulungagung
        dan dapat diakses secara gratis oleh seluruh umat.
      </p>
    </div>

    <div class="elonceng-rubriks">
      <h2>Isi Setiap Edisi</h2>
      <ul class="elonceng-rubrik-list">
        <li><span class="elonceng-rubrik-dot"></span> Pengumuman & agenda paroki pekan ini</li>
        <li><span class="elonceng-rubrik-dot"></span> Jadwal misa dan petugas liturgi</li>
        <li><span class="elonceng-rubrik-dot"></span> Renungan & refleksi iman mingguan</li>
        <li><span class="elonceng-rubrik-dot"></span> Liputan kegiatan kategorial & wilayah</li>
        <li><span class="elonceng-rubrik-dot"></span> Info pastoral dan pelayanan umat</li>
        <li><span class="elonceng-rubrik-dot"></span> Kolom khusus: keluarga, kaum muda, lansia</li>
        <li><span class="elonceng-rubrik-dot"></span> Daftar kelahiran, baptis, dan pernikahan</li>
      </ul>
    </div>

  </section>

  <!-- Iframe reader -->
  <div class="elonceng-reader-wrap">
    <div class="elonceng-reader-head">
      <svg viewBox="0 0 24 24" fill="none" stroke="#b8963e" stroke-width="2"
           width="18" height="18" aria-hidden="true">
        <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/>
        <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>
      </svg>
      <span class="elonceng-reader-head-title">Baca E-Lonceng</span>
      <span class="elonceng-reader-head-sub">Semua edisi tersedia</span>
    </div>

    <div class="elonceng-iframe-wrap">
      <div id="iframe-loader" aria-hidden="true">
        <div class="skel-block"></div>
        <div class="skel-line" style="width:68%"></div>
        <div class="skel-line" style="width:48%"></div>
      </div>
      <iframe id="framecontent"
              src="https://anyflip.com/bookcase/dxiqc"
              title="E-Lonceng Warta Digital Paroki Tulungagung — Semua Edisi"
              allow="fullscreen"
              loading="lazy"></iframe>
    </div>
  </div>

  <!-- Cara baca — konten tambahan untuk SEO & UX -->
  <div class="elonceng-howto">
    <h2>Cara Membaca E-Lonceng</h2>
    <div class="elonceng-steps">
      <div class="elonceng-step">
        <div class="elonceng-step-num">Langkah 1</div>
        <div class="elonceng-step-text">Pilih edisi yang ingin dibaca dari rak buku di atas.</div>
      </div>
      <div class="elonceng-step">
        <div class="elonceng-step-num">Langkah 2</div>
        <div class="elonceng-step-text">Klik cover edisi untuk membuka dan membaca secara lengkap.</div>
      </div>
      <div class="elonceng-step">
        <div class="elonceng-step-num">Langkah 3</div>
        <div class="elonceng-step-text">Gunakan ikon fullscreen untuk pengalaman membaca lebih nyaman.</div>
      </div>
      <div class="elonceng-step">
        <div class="elonceng-step-num">Akses Gratis</div>
        <div class="elonceng-step-text">Setiap edisi dapat dibaca dengan gratis</div>
      </div>
    </div>
  </div>

</div>
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
  var frame  = document.getElementById('framecontent');
  var loader = document.getElementById('iframe-loader');
  if (!frame || !loader) return;

  function onReady() {
    loader.classList.add('hidden');
    // Hapus dari DOM setelah transisi selesai agar tidak block klik
    setTimeout(function () { loader.style.display = 'none'; }, 400);
  }

  frame.addEventListener('load', onReady);

  // Fallback: jika iframe sudah complete saat script jalan (cache browser)
  if (frame.contentDocument && frame.contentDocument.readyState === 'complete') {
    onReady();
  }

  // Safety timeout: sembunyikan loader setelah 8 detik walau iframe belum load
  setTimeout(onReady, 8000);
}());
</script>
</body>
</html>