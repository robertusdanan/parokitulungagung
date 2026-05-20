<?php
/**
 * viewer.php — PDF Viewer Mobile (Paroki SMDTBA)
 * Desain elegan dark-gold. Modal konfirmasi kembali ke halaman sebelumnya.
 */

$fileVer  = filemtime(__FILE__);
$pdfJsVer = '3.11.174';

header('Cache-Control: no-cache, must-revalidate');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $fileVer) . ' GMT');

if (!empty($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
    $clientTime = strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']);
    if ($clientTime >= $fileVer) { http_response_code(304); exit; }
}
header('Content-Type: text/html; charset=UTF-8');
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
<meta name="robots" content="noindex,nofollow">
<meta name="theme-color" content="#0e0c09">
<title>PDF Viewer — Paroki SMDTBA</title>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
  --gold:        #c9a23a;
  --gold-light:  #e8c96a;
  --gold-dim:    rgba(201,162,58,0.18);
  --gold-border: rgba(201,162,58,0.28);
  --bg-deep:     #0e0c09;
  --bg-panel:    #171410;
  --bg-card:     #1e1a14;
  --text-hi:     rgba(255,255,255,0.92);
  --text-mid:    rgba(255,255,255,0.55);
  --text-low:    rgba(255,255,255,0.30);
  --bar-h:       54px;
}

html, body {
  height: 100%; width: 100%;
  background: var(--bg-deep);
  font-family: 'Montserrat', 'Segoe UI', sans-serif;
  overflow: hidden;
  -webkit-tap-highlight-color: transparent;
}

/* ── Toolbar ── */
#toolbar {
  position: fixed; top: 0; left: 0; right: 0;
  height: var(--bar-h);
  background: var(--bg-panel);
  border-bottom: 1px solid var(--gold-border);
  display: flex; align-items: center; gap: 8px;
  padding: 0 10px 0 6px;
  z-index: 200;
}

#btn-back {
  flex-shrink: 0;
  width: 36px; height: 36px;
  border-radius: 10px;
  background: var(--gold-dim);
  border: 1px solid var(--gold-border);
  color: var(--gold);
  display: flex; align-items: center; justify-content: center;
  cursor: pointer;
  transition: background .18s, transform .12s;
}
#btn-back:active { transform: scale(0.92); }

#toolbar-title {
  flex: 1; min-width: 0;
  font-size: 14px; font-weight: 600;
  color: var(--text-hi);
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
  letter-spacing: 0.2px;
  font-family: Georgia, 'Times New Roman', serif;
}

#page-info {
  font-size: 10px; font-weight: 600;
  color: var(--text-low);
  flex-shrink: 0; white-space: nowrap;
  letter-spacing: 0.5px;
}

#btn-dl {
  flex-shrink: 0;
  display: inline-flex; align-items: center; gap: 5px;
  padding: 7px 12px; border-radius: 9px;
  font-size: 11px; font-weight: 600; letter-spacing: 0.3px;
  background: var(--gold-dim);
  color: var(--gold);
  border: 1px solid var(--gold-border);
  text-decoration: none;
  cursor: pointer;
  transition: background .18s, transform .12s;
  white-space: nowrap;
}
#btn-dl:active { transform: scale(0.94); }

/* Progress bar */
#progress-bar {
  position: fixed; top: var(--bar-h); left: 0;
  height: 2px; width: 0%;
  background: linear-gradient(90deg, var(--gold), var(--gold-light));
  z-index: 199;
  transition: width .2s ease;
  box-shadow: 0 0 6px rgba(201,162,58,0.5);
}

/* ── Canvas area ── */
#viewer-wrap {
  position: fixed;
  top: var(--bar-h); left: 0; right: 0; bottom: 0;
  overflow-y: auto; overflow-x: hidden;
  -webkit-overflow-scrolling: touch;
  background: var(--bg-deep);
  display: flex; flex-direction: column; align-items: center;
  gap: 10px;
  padding: 14px 6px 56px;
}

.pdf-page-wrap {
  position: relative;
  border-radius: 3px; overflow: hidden;
  background: #fff; flex-shrink: 0;
  box-shadow: 0 2px 12px rgba(0,0,0,0.65), 0 0 0 1px rgba(201,162,58,0.08);
}
.pdf-page-wrap canvas { display: block; }

.page-num-badge {
  position: absolute; bottom: 6px; right: 8px;
  font-size: 9px; font-weight: 600; letter-spacing: 0.5px;
  color: rgba(0,0,0,0.25);
  pointer-events: none; user-select: none;
}

/* ── Loading ── */
#loading {
  position: fixed; inset: var(--bar-h) 0 0;
  display: flex; flex-direction: column;
  align-items: center; justify-content: center;
  gap: 18px; background: var(--bg-deep);
  z-index: 150;
}

.loading-ornament {
  position: relative; width: 52px; height: 52px;
  display: flex; align-items: center; justify-content: center;
}
.loading-ring {
  position: absolute; inset: 0; border-radius: 50%;
  border: 1.5px solid var(--gold-border);
  border-top-color: var(--gold);
  animation: spin .9s linear infinite;
}
.loading-ring-2 {
  position: absolute; inset: 9px; border-radius: 50%;
  border: 1px solid transparent;
  border-bottom-color: rgba(201,162,58,0.45);
  animation: spin 1.5s linear infinite reverse;
}
.loading-dot {
  width: 5px; height: 5px; border-radius: 50%;
  background: var(--gold); box-shadow: 0 0 8px var(--gold);
}
@keyframes spin { to { transform: rotate(360deg); } }

.loading-label {
  font-size: 10px; font-weight: 600; letter-spacing: 2px;
  text-transform: uppercase; color: var(--text-low);
}
#loading-text {
  font-size: 10px; color: rgba(255,255,255,0.2);
  letter-spacing: 0.3px; margin-top: -10px;
}

/* ── Error ── */
#error-box {
  display: none; position: fixed; inset: var(--bar-h) 0 0;
  flex-direction: column; align-items: center; justify-content: center;
  gap: 14px; background: var(--bg-deep);
  padding: 32px 24px; text-align: center; z-index: 150;
}
#error-box.show { display: flex; }
.error-icon-wrap {
  width: 58px; height: 58px; border-radius: 16px;
  background: rgba(180,50,50,0.1); border: 1px solid rgba(180,50,50,0.22);
  display: flex; align-items: center; justify-content: center;
  font-size: 24px;
}
#error-title {
  font-size: 17px; font-weight: 600; color: var(--text-hi);
  font-family: Georgia, serif;
}
#error-sub { font-size: 11px; color: var(--text-low); line-height: 1.7; max-width: 260px; }
#error-dl {
  display: inline-flex; align-items: center; gap: 7px;
  padding: 11px 24px; border-radius: 11px;
  background: linear-gradient(135deg,#b8860b,#d4a017);
  color: #fff; text-decoration: none;
  font-size: 12px; font-weight: 600;
  box-shadow: 0 4px 18px rgba(184,134,11,.3);
}

/* ── Modal Kembali ── */
#back-modal-overlay {
  position: fixed; inset: 0;
  background: rgba(0,0,0,0);
  z-index: 500; display: none;
  align-items: flex-end; justify-content: center;
}
#back-modal-overlay.show {
  display: flex;
  background: rgba(0,0,0,0.72);
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
}

#back-modal {
  width: 100%; max-width: 480px;
  background: var(--bg-card);
  border-radius: 22px 22px 0 0;
  border-top: 1px solid var(--gold-border);
  padding-bottom: env(safe-area-inset-bottom, 16px);
  transform: translateY(100%);
  transition: transform .34s cubic-bezier(0.32, 0.72, 0, 1);
  overflow: hidden;
}
#back-modal-overlay.show #back-modal {
  transform: translateY(0);
}

.modal-handle {
  display: flex; justify-content: center;
  padding: 12px 0 8px;
}
.modal-handle-bar {
  width: 36px; height: 4px; border-radius: 2px;
  background: rgba(255,255,255,0.12);
}

.modal-header {
  padding: 6px 22px 18px;
  border-bottom: 1px solid rgba(255,255,255,0.05);
}
.modal-eyebrow {
  font-size: 9px; font-weight: 600; letter-spacing: 2.5px;
  text-transform: uppercase; color: var(--gold);
  margin-bottom: 8px;
}
.modal-title {
  font-size: 19px; font-weight: 600; color: var(--text-hi);
  line-height: 1.25; margin-bottom: 6px;
  font-family: Georgia, 'Times New Roman', serif;
}
.modal-sub { font-size: 11px; color: var(--text-mid); line-height: 1.6; }

.modal-filename {
  display: flex; align-items: center; gap: 10px;
  padding: 12px 22px;
  background: rgba(255,255,255,0.025);
  border-bottom: 1px solid rgba(255,255,255,0.04);
}
.modal-filename-icon {
  width: 34px; height: 34px; border-radius: 9px; flex-shrink: 0;
  background: var(--gold-dim); border: 1px solid var(--gold-border);
  display: flex; align-items: center; justify-content: center;
}
.modal-filename-text {
  flex: 1; min-width: 0;
  font-size: 11px; font-weight: 500; color: var(--text-mid);
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}

.modal-actions {
  padding: 14px 22px 6px;
  display: flex; flex-direction: column; gap: 10px;
}

.modal-btn {
  display: flex; align-items: center; gap: 12px;
  padding: 14px 16px; border-radius: 13px;
  cursor: pointer; text-decoration: none;
  border: none; width: 100%; text-align: left;
  font-family: inherit;
  transition: background .18s, transform .12s;
  -webkit-tap-highlight-color: transparent;
}
.modal-btn:active { transform: scale(0.975); }

.modal-btn--back {
  background: linear-gradient(135deg, rgba(184,134,11,0.16), rgba(212,160,23,0.09));
  border: 1px solid var(--gold-border);
}
.modal-btn--stay {
  background: rgba(255,255,255,0.04);
  border: 1px solid rgba(255,255,255,0.07);
}

.modal-btn-icon {
  width: 36px; height: 36px; border-radius: 9px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
}
.modal-btn--back .modal-btn-icon { background: var(--gold-dim); }
.modal-btn--stay .modal-btn-icon { background: rgba(255,255,255,0.05); }

.modal-btn-content { flex: 1; text-align: left; }
.modal-btn-label {
  font-size: 13px; font-weight: 600; line-height: 1.2;
  display: block;
}
.modal-btn-desc {
  font-size: 10px; font-weight: 400; margin-top: 3px;
  display: block;
}
.modal-btn--back .modal-btn-label { color: var(--gold-light); }
.modal-btn--back .modal-btn-desc  { color: rgba(201,162,58,0.5); }
.modal-btn--stay .modal-btn-label { color: var(--text-hi); }
.modal-btn--stay .modal-btn-desc  { color: var(--text-low); }

/* ── FAB ── */
#fab-top {
  position: fixed;
  bottom: calc(18px + env(safe-area-inset-bottom,0px));
  right: 14px;
  width: 40px; height: 40px; border-radius: 12px;
  background: var(--bg-card);
  border: 1px solid var(--gold-border);
  color: var(--gold);
  display: none; align-items: center; justify-content: center;
  cursor: pointer; z-index: 180;
  box-shadow: 0 4px 16px rgba(0,0,0,0.5);
  transition: opacity .2s, transform .15s;
}
#fab-top.visible { display: flex; }
#fab-top:active  { transform: scale(0.9); }
</style>
</head>
<body>

<div id="toolbar">
  <button id="btn-back" onclick="openBackModal()" aria-label="Kembali">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"
         width="16" height="16" stroke-linecap="round" stroke-linejoin="round">
      <path d="M19 12H5"/><polyline points="12 19 5 12 12 5"/>
    </svg>
  </button>

  <div id="toolbar-title">Memuat…</div>
  <div id="page-info"></div>

  <a id="btn-dl" href="#" download>
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"
         width="12" height="12" stroke-linecap="round" stroke-linejoin="round">
      <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/>
      <polyline points="7 10 12 15 17 10"/>
      <line x1="12" y1="15" x2="12" y2="3"/>
    </svg>
    Unduh
  </a>
</div>

<div id="progress-bar"></div>

<div id="loading">
  <div class="loading-ornament">
    <div class="loading-ring"></div>
    <div class="loading-ring-2"></div>
    <div class="loading-dot"></div>
  </div>
  <div class="loading-label">Memuat Dokumen</div>
  <div id="loading-text">Mengambil file…</div>
</div>

<div id="error-box">
  <div class="error-icon-wrap">⚠</div>
  <div id="error-title">Gagal Memuat PDF</div>
  <div id="error-sub">File tidak dapat ditampilkan. Coba unduh langsung.</div>
  <a id="error-dl" href="#">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"
         width="13" height="13" stroke-linecap="round" stroke-linejoin="round">
      <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/>
      <polyline points="7 10 12 15 17 10"/>
      <line x1="12" y1="15" x2="12" y2="3"/>
    </svg>
    Unduh PDF
  </a>
</div>

<div id="viewer-wrap"></div>

<div id="fab-top" onclick="document.getElementById('viewer-wrap').scrollTo({top:0,behavior:'smooth'})">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"
       width="16" height="16" stroke-linecap="round" stroke-linejoin="round">
    <polyline points="18 15 12 9 6 15"/>
  </svg>
</div>

<!-- Modal Kembali -->
<div id="back-modal-overlay" onclick="handleOverlayClick(event)">
  <div id="back-modal" role="dialog" aria-modal="true">

    <div class="modal-handle">
      <div class="modal-handle-bar"></div>
    </div>

    <div class="modal-header">
      <div class="modal-eyebrow">Navigasi</div>
      <div class="modal-title">Kembali ke Halaman Sebelumnya?</div>
      <div class="modal-sub">Dokumen yang sedang dibuka akan ditutup.</div>
    </div>

    <div class="modal-filename">
      <div class="modal-filename-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
             width="16" height="16" stroke-linecap="round" stroke-linejoin="round"
             style="color:var(--gold)">
          <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
          <polyline points="14 2 14 8 20 8"/>
          <line x1="16" y1="13" x2="8" y2="13"/>
          <line x1="16" y1="17" x2="8" y2="17"/>
        </svg>
      </div>
      <div class="modal-filename-text" id="modal-filename-text">—</div>
    </div>

    <div class="modal-actions">
      <button class="modal-btn modal-btn--back" onclick="goBack()">
        <div class="modal-btn-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"
               width="16" height="16" stroke-linecap="round" stroke-linejoin="round"
               style="color:var(--gold)">
            <path d="M19 12H5"/><polyline points="12 19 5 12 12 5"/>
          </svg>
        </div>
        <div class="modal-btn-content">
          <span class="modal-btn-label">Ya, Kembali</span>
          <span class="modal-btn-desc">Tutup viewer dan kembali ke halaman sebelumnya</span>
        </div>
      </button>

      <button class="modal-btn modal-btn--stay" onclick="closeBackModal()">
        <div class="modal-btn-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"
               width="16" height="16" stroke-linecap="round" stroke-linejoin="round"
               style="color:rgba(255,255,255,0.4)">
            <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
            <polyline points="14 2 14 8 20 8"/>
          </svg>
        </div>
        <div class="modal-btn-content">
          <span class="modal-btn-label">Lanjutkan Membaca</span>
          <span class="modal-btn-desc">Tutup dialog ini dan tetap di halaman ini</span>
        </div>
      </button>
    </div>

    <div style="height:10px"></div>
  </div>
</div>

<script src="https://unpkg.com/pdfjs-dist@<?= $pdfJsVer ?>/build/pdf.min.js?v=<?= $fileVer ?>"></script>
<script>
(function () {
  'use strict';

  var params  = new URLSearchParams(location.search);
  var fileUrl = params.get('file') || '';
  var backUrl = params.get('back') || '';

  if (!fileUrl) { showError('Parameter file tidak ditemukan.'); return; }

  var filename = decodeURIComponent(fileUrl.split('/').pop());

  document.getElementById('toolbar-title').textContent       = filename;
  document.getElementById('btn-dl').href                     = fileUrl;
  document.getElementById('btn-dl').setAttribute('download', filename);
  document.getElementById('error-dl').href                   = fileUrl;
  document.getElementById('modal-filename-text').textContent = filename;

  var pdfjsLib = window['pdfjs-dist/build/pdf'];
  pdfjsLib.GlobalWorkerOptions.workerSrc =
    'https://unpkg.com/pdfjs-dist@<?= $pdfJsVer ?>/build/pdf.worker.min.js';

  var _pdf      = null;
  var _total    = 0;
  var _wrap     = document.getElementById('viewer-wrap');
  var _progress = document.getElementById('progress-bar');

  function getDisplayWidth() {
    return Math.min(window.innerWidth - 12, 700);
  }

  function renderPage(num) {
    return _pdf.getPage(num).then(function (page) {
      var scale    = getDisplayWidth() / page.getViewport({ scale: 1 }).width;
      var viewport = page.getViewport({ scale: scale });
      var dpr      = Math.min(window.devicePixelRatio || 1, 2);

      var canvas       = document.createElement('canvas');
      var ctx          = canvas.getContext('2d');
      canvas.width     = Math.floor(viewport.width  * dpr);
      canvas.height    = Math.floor(viewport.height * dpr);
      canvas.style.width  = Math.floor(viewport.width)  + 'px';
      canvas.style.height = Math.floor(viewport.height) + 'px';
      ctx.scale(dpr, dpr);

      var wrap       = document.createElement('div');
      wrap.className = 'pdf-page-wrap';
      wrap.style.width  = Math.floor(viewport.width)  + 'px';
      wrap.style.height = Math.floor(viewport.height) + 'px';

      if (_total > 1) {
        var badge       = document.createElement('div');
        badge.className = 'page-num-badge';
        badge.textContent = num + ' / ' + _total;
        wrap.appendChild(badge);
      }

      wrap.insertBefore(canvas, wrap.firstChild);
      _wrap.appendChild(wrap);

      return page.render({ canvasContext: ctx, viewport: viewport }).promise.then(function () {
        _progress.style.width = Math.round((num / _total) * 100) + '%';
      });
    });
  }

  function renderAll() {
    _wrap.innerHTML = '';
    _progress.style.opacity = '1';
    _progress.style.width   = '0%';
    /* Render halaman satu per satu secara berurutan */
    var seq = Promise.resolve();
    for (var i = 1; i <= _total; i++) {
      (function (n) {
        seq = seq.then(function () { return renderPage(n); });
      })(i);
    }
    seq.then(function () {
      hide('loading');
      document.getElementById('page-info').textContent = _total > 1 ? _total + ' hal.' : '';
      setTimeout(function () { _progress.style.opacity = '0'; }, 700);
    });
  }

  document.getElementById('loading-text').textContent = 'Mengambil dokumen…';

  pdfjsLib.getDocument({ url: fileUrl, withCredentials: true })
    .promise
    .then(function (pdf) {
      _pdf   = pdf;
      _total = pdf.numPages;
      document.getElementById('loading-text').textContent = 'Merender ' + _total + ' halaman…';
      renderAll();
    })
    .catch(function (err) {
      console.error('PDF.js:', err);
      showError('Tidak dapat memuat: ' + (err.message || err));
    });

  /* Re-render saat resize/orientasi */
  var _rt;
  function onResize() {
    if (!_pdf) return;
    clearTimeout(_rt);
    _rt = setTimeout(function () {
      _progress.style.opacity = '1';
      renderAll();
    }, 300);
  }
  window.addEventListener('orientationchange', function () { setTimeout(onResize, 350); });
  window.addEventListener('resize', onResize);

  /* FAB scroll-to-top */
  var fab = document.getElementById('fab-top');
  _wrap.addEventListener('scroll', function () {
    fab.classList.toggle('visible', _wrap.scrollTop > 280);
  });

  /* ── Modal kembali ── */
  var _modalOpen = false;

  window.openBackModal = function () {
    if (_modalOpen) return;
    _modalOpen = true;
    var ov = document.getElementById('back-modal-overlay');
    ov.style.display = 'flex';
    requestAnimationFrame(function () {
      requestAnimationFrame(function () { ov.classList.add('show'); });
    });
  };

  window.closeBackModal = function () {
    if (!_modalOpen) return;
    var ov = document.getElementById('back-modal-overlay');
    ov.classList.remove('show');
    setTimeout(function () { ov.style.display = 'none'; _modalOpen = false; }, 360);
  };

  window.handleOverlayClick = function (e) {
    if (e.target === document.getElementById('back-modal-overlay')) closeBackModal();
  };

  window.goBack = function () {
    if (backUrl) {
      location.href = backUrl;
    } else if (history.length > 1) {
      history.back();
    } else {
      location.href = '/';
    }
  };

  /* Tombol back fisik / swipe gesture */
  history.pushState(null, '', location.href);
  window.addEventListener('popstate', function () {
    if (_modalOpen) {
      closeBackModal();
    } else {
      openBackModal();
    }
    history.pushState(null, '', location.href);
  });

  function hide(id) {
    var el = document.getElementById(id);
    if (el) el.style.display = 'none';
  }
  function showError(msg) {
    hide('loading');
    var eb = document.getElementById('error-box');
    if (eb) {
      eb.classList.add('show');
      var s = document.getElementById('error-sub');
      if (s && msg) s.textContent = msg;
    }
  }

})();
</script>
</body>
</html>
