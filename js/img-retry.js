/**
 * img-retry.js  v2.0
 * ─────────────────────────────────────────────────────────────────────
 * Menangani gambar gagal load di semua halaman secara otomatis:
 *
 *  1. SKELETON  — setiap <img> diberi placeholder shimmer animasi
 *                 selagi menunggu gambar asli selesai dimuat.
 *  2. RETRY     — jika gambar gagal (onerror), coba ulang otomatis
 *                 hingga 3× dengan jeda bertahap (800ms → 2s → 4s).
 *  3. FALLBACK  — setelah 3× tetap gagal, tampilkan avatar inisial
 *                 agar layout tidak rusak / broken.
 *  4. GATING    — expose window._imgGatePromises agar loading screen
 *                 bisa menunggu gambar above-the-fold selesai dimuat.
 * ─────────────────────────────────────────────────────────────────────
 */
(function () {
  'use strict';

  var MAX_RETRIES  = 3;
  var RETRY_DELAYS = [800, 2000, 4000];
  var ABOVE_FOLD_H = window.innerHeight || 700;

  /* ── Inject CSS skeleton sekali saja ─────────────────────────────── */
  if (!document.getElementById('img-retry-css')) {
    var s = document.createElement('style');
    s.id = 'img-retry-css';
    s.textContent =
      '@keyframes _irs{0%{background-position:200% 0}100%{background-position:-200% 0}}' +
      '.img-sk{background:linear-gradient(90deg,rgba(255,255,255,.05) 25%,rgba(255,255,255,.12) 50%,rgba(255,255,255,.05) 75%)!important;background-size:200% 100%!important;animation:_irs 1.6s infinite linear!important;border-radius:inherit;}' +
      '.img-av{display:flex!important;align-items:center;justify-content:center;background:rgba(201,168,76,.12)!important;color:#c9a84c!important;font-size:clamp(13px,2.5vw,20px);font-weight:700;font-family:"Cormorant Garamond",Georgia,serif;border-radius:inherit;user-select:none;}';
    (document.head || document.documentElement).appendChild(s);
  }

  /* ── Gate: promise list untuk gambar above-fold ───────────────────── */
  window._imgGatePromises = window._imgGatePromises || [];

  /* ── Helpers ──────────────────────────────────────────────────────── */
  function getInitial(img) {
    var t = (img.alt || img.title || img.getAttribute('aria-label') || '').trim();
    var m = t.match(/\b\w/g);
    if (m && m.length >= 2) return (m[0] + m[1]).toUpperCase();
    return t.slice(0, 1).toUpperCase() || '·';
  }

  function isAboveFold(img) {
    var r = img.getBoundingClientRect();
    return r.top < ABOVE_FOLD_H;
  }

  function addSkeleton(img) {
    var p = img.parentElement;
    if (p && p.tagName !== 'BODY' && getComputedStyle(p).position !== 'static') {
      p.classList.add('img-sk');
    } else {
      img.classList.add('img-sk');
    }
  }

  function removeSkeleton(img) {
    img.classList.remove('img-sk');
    var p = img.parentElement;
    if (p) p.classList.remove('img-sk');
  }

  function buildFallback(img) {
    removeSkeleton(img);
    var fb = document.createElement('div');
    fb.className = 'img-av';
    fb.setAttribute('aria-label', img.alt || 'Foto');
    var w = img.offsetWidth  || img.width  || 60;
    var h = img.offsetHeight || img.height || 60;
    fb.style.cssText = 'width:' + w + 'px;height:' + h + 'px;';
    fb.textContent = getInitial(img);
    if (img.parentNode) img.parentNode.insertBefore(fb, img);
    img.style.display = 'none';
  }

  /* ── Core retry ───────────────────────────────────────────────────── */
  window.imgRetry = function (img) {
    if (!img || img._rDone) return;
    var retries = parseInt(img.dataset.retries || '0', 10);

    if (retries >= MAX_RETRIES) {
      img._rDone = true;
      buildFallback(img);
      if (img._gate) img._gate();
      return;
    }

    img.dataset.retries = retries + 1;
    if (!img.dataset.src) img.dataset.src = img.getAttribute('src') || '';

    setTimeout(function () {
      if (img._rDone) return;
      var b = img.dataset.src;
      img.src = b + (b.indexOf('?') >= 0 ? '&' : '?') + '_r=' + Date.now();
    }, RETRY_DELAYS[retries] || 4000);
  };

  /* ── Attach ke satu elemen img ────────────────────────────────────── */
  function attach(img) {
    if (img._rAttached) return;
    img._rAttached = true;

    var src = img.getAttribute('src') || '';
    if (src && !img.dataset.src) img.dataset.src = src;

    /* Gate: above-fold non-lazy images harus selesai sebelum page reveal */
    if (isAboveFold(img) && img.getAttribute('loading') !== 'lazy') {
      var p = new Promise(function (res) { img._gate = res; });
      window._imgGatePromises.push(p);
    }

    if (!img.complete || img.naturalWidth === 0) addSkeleton(img);

    img.addEventListener('load', function () {
      removeSkeleton(img);
      if (img._gate) { img._gate(); img._gate = null; }
    });

    /* Attach error — tidak menimpa jika sudah ada imgRetry inline */
    var oe = img.getAttribute('onerror') || '';
    if (oe.indexOf('imgRetry') !== -1) {
      /* inline sudah ada imgRetry → cukup handle skeleton removal */
      img.addEventListener('error', function () { removeSkeleton(img); });
    } else {
      img.onerror = function () { window.imgRetry(img); };
    }

    /* Gambar sudah broken sebelum script jalan */
    if (img.complete && img.naturalWidth === 0 && src) window.imgRetry(img);
  }

  /* ── Inisialisasi + observer untuk konten dinamis ─────────────────── */
  function init() {
    document.querySelectorAll('img').forEach(attach);

    if (window.MutationObserver) {
      new MutationObserver(function (muts) {
        muts.forEach(function (m) {
          m.addedNodes.forEach(function (n) {
            if (!n || n.nodeType !== 1) return;
            if (n.tagName === 'IMG') attach(n);
            else if (n.querySelectorAll) n.querySelectorAll('img').forEach(attach);
          });
        });
      }).observe(document.body || document.documentElement, { childList: true, subtree: true });
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

})();
