<?php
require_once __DIR__ . '/../includes/functions.php';

$seo = [
    'title'       => 'Tentang Paroki Santa Maria Tidak Bernoda Asal Tulungagung',
    'description' => 'Sejarah, visi, misi, dan profil lengkap Gereja Katolik Paroki Santa Maria Dengan Tidak Bernoda Asal (SMDTBA) Tulungagung, berdiri sejak 1917 di bawah Keuskupan Surabaya.',
    'canonical'   => 'https://www.parokitulungagung.org/tentang',
    'type'        => 'website',
    'keywords'    => 'tentang paroki tulungagung, sejarah gereja katolik tulungagung, profil paroki smdtba, gereja katolik tulungagung berdiri, keuskupan surabaya tulungagung',
];

$breadcrumbs = [
    ['name' => 'Beranda', 'url' => 'https://www.parokitulungagung.org'],
    ['name' => 'Tentang Paroki'],
];

$extraCss = [];
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?php include __DIR__ . '/seo_head.php'; ?>

  <style>
  /* ── Hero ── */
  .ttg-hero {
    position: relative;
    background: #1a1208;
    padding: 72px 24px 64px;
    text-align: center;
    overflow: hidden;
  }
  .ttg-hero::before {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(ellipse 60% 40% at 50% 0%, rgba(201,169,110,0.12) 0%, transparent 70%);
  }
  .ttg-hero-eyebrow {
    font-family: 'DM Sans', sans-serif;
    font-size: 11px;
    font-weight: 400;
    letter-spacing: 0.24em;
    text-transform: uppercase;
    color: #c9a96e;
    position: relative;
    margin-bottom: 16px;
  }
  .ttg-hero h1 {
    font-family: 'Playfair Display', serif;
    font-size: clamp(30px, 5vw, 50px);
    font-weight: 600;
    color: #e8dcc8;
    letter-spacing: 0.04em;
    position: relative;
    line-height: 1.15;
  }
  .ttg-hero-sub {
    font-family: 'DM Sans', sans-serif;
    font-size: 15px;
    font-weight: 300;
    color: #7a6a52;
    position: relative;
    margin-top: 14px;
  }
  .ttg-hero-divider {
    display: flex;
    align-items: center;
    gap: 12px;
    justify-content: center;
    max-width: 320px;
    margin: 28px auto 0;
    position: relative;
  }
  .ttg-hero-divider .hline      { flex:1; height:.5px; background: linear-gradient(90deg,transparent,#c9a96e); }
  .ttg-hero-divider .hline.r   { background: linear-gradient(90deg,#c9a96e,transparent); }

  /* ── Layout ── */
  .ttg-wrap {
    max-width: 860px;
    margin: 0 auto;
    padding: 56px 24px 80px;
  }

  /* ── Section label ── */
  .ttg-label {
    font-family: 'DM Sans', sans-serif;
    font-size: 10px;
    font-weight: 500;
    letter-spacing: 0.24em;
    text-transform: uppercase;
    color: #c9a96e;
    margin-bottom: 12px;
  }

  /* ── Sejarah singkat ── */
  .ttg-sejarah {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 48px;
    align-items: start;
    margin-bottom: 64px;
  }
  .ttg-tahun-besar {
    font-family: 'Playfair Display', serif;
    font-size: 72px;
    font-weight: 700;
    color: #6f6e6c;
    line-height: 1;
    letter-spacing: -2px;
  }
  .ttg-tahun-sub {
    font-family: 'DM Sans', sans-serif;
    font-size: 12px;
    font-weight: 300;
    color: #b5a990;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    margin-top: 4px;
  }
  .ttg-sejarah-text h2 {
    font-family: 'Playfair Display', serif;
    font-size: 22px;
    font-weight: 600;
    color: #2c2416;
    margin-bottom: 14px;
    line-height: 1.3;
  }
  .ttg-sejarah-text p {
    font-family: 'DM Sans', sans-serif;
    font-size: 14.5px;
    font-weight: 300;
    color: #5a4f3e;
    line-height: 1.85;
    margin-bottom: 12px;
  }

  /* ── Divider ornamen ── */
  .ttg-ornamen {
    display: flex;
    align-items: center;
    gap: 16px;
    margin: 48px 0;
  }
  .ttg-ornamen .oline { flex:1; height:.5px; background: #e8dcc8; }

  /* ── Visi Misi ── */
  .ttg-vimis {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
    margin-bottom: 64px;
  }
  .ttg-card {
    border: 0.5px solid #e8dcc8;
    border-radius: 10px;
    padding: 28px 24px;
    position: relative;
    background: #faf8f4;
  }
  .ttg-card-accent {
    position: absolute;
    top: 0; left: 28px;
    width: 40px; height: 2px;
    background: #c9a96e;
    border-radius: 0 0 2px 2px;
  }
  .ttg-card h3 {
    font-family: 'Playfair Display', serif;
    font-size: 18px;
    font-weight: 600;
    color: #2c2416;
    margin: 12px 0 14px;
  }
  .ttg-card p, .ttg-card li {
    font-family: 'DM Sans', sans-serif;
    font-size: 14px;
    font-weight: 300;
    color: #5a4f3e;
    line-height: 1.8;
  }
  .ttg-card ul {
    padding-left: 16px;
  }
  .ttg-card li { margin-bottom: 6px; }

  /* ── Fakta Paroki ── */
  .ttg-fakta {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 16px;
    margin-bottom: 64px;
  }
  .ttg-fakta-item {
    text-align: center;
    padding: 24px 16px;
    border: 0.5px solid #e8dcc8;
    border-radius: 8px;
    background: #fff;
  }
  .ttg-fakta-angka {
    font-family: 'Playfair Display', serif;
    font-size: 32px;
    font-weight: 600;
    color: #8b6a3e;
    line-height: 1;
    margin-bottom: 8px;
  }
  .ttg-fakta-label {
    font-family: 'DM Sans', sans-serif;
    font-size: 12px;
    font-weight: 300;
    color: #9d8f7a;
    letter-spacing: 0.06em;
  }

  /* ── Keuskupan ── */
  .ttg-keuskupan {
    background: #1a1208;
    border-radius: 10px;
    padding: 36px 32px;
    margin-bottom: 64px;
    display: flex;
    align-items: center;
    gap: 28px;
  }
  .ttg-keuskupan-cross {
    flex-shrink: 0;
  }
  .ttg-keuskupan-body h3 {
    font-family: 'Playfair Display', serif;
    font-size: 18px;
    font-weight: 600;
    color: #e8dcc8;
    margin-bottom: 8px;
  }
  .ttg-keuskupan-body p {
    font-family: 'DM Sans', sans-serif;
    font-size: 13.5px;
    font-weight: 300;
    color: #9d8f7a;
    line-height: 1.7;
  }
  .ttg-keuskupan-body a {
    color: #c9a96e;
    text-decoration: none;
  }
  .ttg-keuskupan-body a:hover { text-decoration: underline; }

  /* ── Lokasi ── */
  .ttg-lokasi h2 {
    font-family: 'Playfair Display', serif;
    font-size: 22px;
    font-weight: 600;
    color: #2c2416;
    margin-bottom: 16px;
  }
  .ttg-map-wrap {
    border-radius: 10px;
    overflow: hidden;
    border: 0.5px solid #e8dcc8;
  }
  .ttg-map-wrap iframe {
    display: block;
    width: 100%;
    height: 280px;
    border: none;
  }

  /* ── CTA bawah ── */
  .ttg-cta {
    text-align: center;
    margin-top: 64px;
    padding: 48px 24px;
    border-top: 0.5px solid #e8dcc8;
  }
  .ttg-cta p {
    font-family: 'Playfair Display', serif;
    font-style: italic;
    font-size: 18px;
    color: #5a4f3e;
    margin-bottom: 24px;
    line-height: 1.6;
  }
  .ttg-cta-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 28px;
    background: #1a1208;
    color: #e8dcc8 !important;
    font-family: 'DM Sans', sans-serif;
    font-size: 12px;
    font-weight: 400;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    border-radius: 4px;
    text-decoration: none;
    transition: background .2s, color .2s;
  }
  .ttg-cta-btn:hover { background: #c9a96e; color: #1a1208; }

  /* ── Responsive ── */
  @media (max-width: 640px) {
    .ttg-sejarah { grid-template-columns: 1fr; gap: 24px; }
    .ttg-tahun-besar { font-size: 52px; }
    .ttg-vimis { grid-template-columns: 1fr; }
    .ttg-keuskupan { flex-direction: column; text-align: center; }
  }
  </style>
</head>
<body>
<?php $headerTitle = 'Tentang Paroki'; include __DIR__ . '/page_header.php'; ?>

<main>

  <!-- Hero -->
  <section class="ttg-hero">
    <p class="ttg-hero-eyebrow">Keuskupan Surabaya · Jawa Timur</p>
    <h1>Tentang Paroki</h1>
    <p class="ttg-hero-sub">Santa Maria Dengan Tidak Bernoda Asal, Tulungagung</p>
    <div class="ttg-hero-divider">
      <span class="hline"></span>
      <svg width="20" height="20" viewBox="0 0 28 28" fill="none">
        <rect x="12" y="2" width="4" height="24" rx="1" fill="#c9a96e"/>
        <rect x="4" y="10" width="20" height="4" rx="1" fill="#c9a96e"/>
      </svg>
      <span class="hline r"></span>
    </div>
  </section>

  <div class="ttg-wrap">

    <!-- Sejarah -->
    <div class="ttg-sejarah">
      <div>
        <div class="ttg-label">Berdiri Sejak</div>
        <div class="ttg-tahun-besar">1950<br><span style="font-size:36px">-an</span></div>
        <div class="ttg-tahun-sub">Abad ke-20</div>
      </div>
      <div class="ttg-sejarah-text">
        <div class="ttg-label">Sejarah Singkat</div>
        <h2>Pelayanan Iman yang Telah Berdiri Lebih dari Satu Abad</h2>
        <p>
          Paroki Santa Maria Dengan Tidak Bernoda Asal (SMDTBA) Tulungagung adalah salah satu paroki
          tertua di wilayah Keuskupan Surabaya. Gereja ini telah menjadi pusat pelayanan rohani
          umat Katolik di Tulungagung dan sekitarnya selama lebih dari satu abad.
        </p>
        <p>
          Berlokasi di Jalan Ahmad Yani Timur, Bago, Tulungagung, gereja ini menjadi rumah iman
          bagi ribuan umat yang tersebar di berbagai wilayah dan lingkungan di Kabupaten Tulungagung.
          Paroki ini melayani kegiatan rohani mulai dari misa harian, misa mingguan, pelayanan sakramen,
          hingga berbagai kegiatan komunitas umat yang aktif dan berkembang.
        </p>
      </div>
    </div>

    <!-- Divider -->
    <div class="ttg-ornamen">
      <span class="oline"></span>
      <svg width="18" height="18" viewBox="0 0 28 28" fill="none">
        <rect x="12" y="2" width="4" height="24" rx="1" fill="#c9a96e"/>
        <rect x="4" y="10" width="20" height="4" rx="1" fill="#c9a96e"/>
      </svg>
      <span class="oline"></span>
    </div>

    <!-- Visi & Misi -->
    <div class="ttg-label" style="margin-bottom:16px;">Visi &amp; Misi</div>
    <div class="ttg-vimis">
      <div class="ttg-card">
        <div class="ttg-card-accent"></div>
        <h3>Visi</h3>
        <p>
          Menjadi komunitas iman yang hidup, bertumbuh dalam kasih Kristus, dan hadir sebagai
          terang bagi masyarakat Tulungagung melalui pelayanan yang tulus dan berkelanjutan.
        </p>
      </div>
      <div class="ttg-card">
        <div class="ttg-card-accent"></div>
        <h3>Misi</h3>
        <ul>
          <li>Menyelenggarakan liturgi dan sakramen secara bermartabat.</li>
          <li>Membangun persaudaraan umat lintas wilayah dan lingkungan.</li>
          <li>Memberdayakan umat melalui pendidikan iman dan kegiatan sosial.</li>
          <li>Hadir dan berdialog bersama masyarakat sekitar.</li>
        </ul>
      </div>
    </div>

    <!-- Fakta Paroki -->
    <div class="ttg-label" style="margin-bottom:16px;">Paroki dalam Angka</div>
    <div class="ttg-fakta">
      <div class="ttg-fakta-item">
        <div class="ttg-fakta-angka">100+</div>
        <div class="ttg-fakta-label">Tahun Pelayanan</div>
      </div>
      <div class="ttg-fakta-item">
        <div class="ttg-fakta-angka">5×</div>
        <div class="ttg-fakta-label">Misa Per Minggu</div>
      </div>
      <div class="ttg-fakta-item">
        <div class="ttg-fakta-angka">7</div>
        <div class="ttg-fakta-label">Hari Misa Harian</div>
      </div>
      <div class="ttg-fakta-item">
        <div class="ttg-fakta-angka">∞</div>
        <div class="ttg-fakta-label">Komunitas Aktif</div>
      </div>
    </div>

    <!-- Keuskupan -->
    <div class="ttg-keuskupan">
      <div class="ttg-keuskupan-cross">
        <svg width="36" height="48" viewBox="0 0 36 48" fill="none">
          <rect x="14" y="0" width="8" height="48" rx="2" fill="#c9a96e"/>
          <rect x="0" y="14" width="36" height="8" rx="2" fill="#c9a96e"/>
        </svg>
      </div>
      <div class="ttg-keuskupan-body">
        <h3>Bagian dari Keuskupan Surabaya</h3>
        <p>
          Paroki Santa Maria Dengan Tidak Bernoda Asal Tulungagung berada di bawah naungan
          <a href="https://www.keuskupansurabaya.org" target="_blank" rel="noopener">Keuskupan Surabaya</a>,
          salah satu keuskupan Gereja Katolik Roma di Indonesia yang meliputi wilayah Jawa Timur bagian selatan.
          Paroki ini dipimpin oleh Pastor Kepala yang ditugaskan oleh Uskup Surabaya.
        </p>
      </div>
    </div>

    <!-- Lokasi -->
    <div class="ttg-lokasi">
      <div class="ttg-label">Lokasi</div>
      <h2>Temukan Kami</h2>
      <div class="ttg-map-wrap">
        <iframe
          src="https://www.google.com/maps?q=Gereja+Katolik+Santa+Maria+Dengan+Tidak+Bernoda+Asal+Tulungagung&output=embed"
          title="Lokasi Paroki Tulungagung"
          loading="lazy">
        </iframe>
      </div>
      <p style="font-family:'DM Sans',sans-serif;font-size:13px;font-weight:300;color:#9d8f7a;margin-top:10px;">
        Jl. Ahmad Yani Tim. Gg. IV No.1, Bago, Tulungagung, Jawa Timur
      </p>
    </div>

    <!-- CTA -->
    <div class="ttg-cta">
      <p>&ldquo;Datanglah, bergabunglah, dan rasakan kehangatan komunitas Gereja Katolik.&rdquo;</p>
      <a href="/kontak" class="ttg-cta-btn">
        Hubungi Kami
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
          <line x1="5" y1="12" x2="19" y2="12"/>
          <polyline points="12 5 19 12 12 19"/>
        </svg>
      </a>
    </div>

  </div>
</main>

<?php include __DIR__ . '/footer.php'; ?>
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
</body>
</html>
