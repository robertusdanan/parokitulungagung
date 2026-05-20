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
/* ── STICKY MENUBAR ── */
(function () {
  var bar = document.getElementById('divmenubar');
  if (!bar) return;

  // Ukur tinggi dan posisi awal menubar
  function getBarTop() {
    // Matikan sementara fixed agar getBoundingClientRect akurat
    bar.classList.remove('is-sticky');
    document.body.classList.remove('menu-is-sticky');
    var top = bar.getBoundingClientRect().top + window.scrollY;
    return top;
  }

  var barTop = getBarTop();

  function applySticky() {
    var barHeight = bar.offsetHeight;
    document.body.style.setProperty('--menubar-height', barHeight + 'px');
    if (window.scrollY >= barTop) {
      bar.classList.add('is-sticky');
      document.body.classList.add('menu-is-sticky');
    } else {
      bar.classList.remove('is-sticky');
      document.body.classList.remove('menu-is-sticky');
    }
  }

  // Jalankan sekali saat load (handle hard-refresh di tengah halaman)
  applySticky();

  window.addEventListener('scroll', applySticky, { passive: true });

  // Re-kalkulasi barTop jika layout berubah (gambar load, resize)
  window.addEventListener('resize', function () {
    barTop = getBarTop();
    applySticky();
  }, { passive: true });
})();