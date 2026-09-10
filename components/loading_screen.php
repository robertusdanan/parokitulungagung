<?php

$_currentPath = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
$_currentPath = rtrim($_currentPath, '/') ?: '/';
if ($_currentPath !== '/') return;
?>

<div id="paroki-loading-screen" aria-hidden="true" role="presentation">

  <!-- Satu layer glow ambient (gabungan ambient+aura lama) -->
  <div class="pls-glow"></div>

  <div class="pls-logo-section">
    <!-- Arc spinner (gabungan orbit ring lama jadi satu elemen) -->
    <div class="pls-spinner"></div>
    <!-- Logo -->
    <img
      src="data:image/png;base64,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"
      alt="Paroki Santa Maria Dengan Tidak Bernoda Asal Tulungagung"
      class="pls-logo"
      width="120"
      height="120"
      draggable="false"
      decoding="async"
    >
  </div>

</div>

<style>
/* ================================================================
   PAROKI LOADING SCREEN — Lightweight Edition
   ================================================================ */

#paroki-loading-screen {
  --gold:      #d4aa5f;
  --gold-b:    #eedfa0;
  --gold-a70:  rgba(212,170,95,0.70);
  --gold-a30:  rgba(212,170,95,0.30);
  --gold-a12:  rgba(212,170,95,0.12);
  --transition-out: opacity .8s ease, visibility .8s ease;

  position: fixed;
  inset: 0;
  z-index: 99999;
  display: flex;
  align-items: center;
  justify-content: center;
  background: radial-gradient(
    ellipse 90% 80% at 50% 48%,
    #100b18 0%,
    #08050d 50%,
    #040308 100%
  );
  overflow: hidden;
  contain: strict;
  transition: var(--transition-out);
}

#paroki-loading-screen.pls--hidden {
  opacity: 0;
  visibility: hidden;
  pointer-events: none;
}

/* ── Satu layer glow ambient (menggantikan ambient + aura) ───────── */
.pls-glow {
  position: absolute;
  width: 420px;
  height: 420px;
  left: 50%;
  top: 50%;
  transform: translate(-50%, -50%);
  border-radius: 50%;
  background: radial-gradient(
    circle,
    rgba(212,170,95,0.12) 0%,
    rgba(212,170,95,0.03) 50%,
    transparent 75%
  );
  pointer-events: none;
  will-change: opacity, transform;
  animation: pls-glow-pulse 5s ease-in-out infinite;
}

@keyframes pls-glow-pulse {
  0%, 100% { opacity: 0.6; transform: translate(-50%,-50%) scale(1);    }
  50%       { opacity: 1;   transform: translate(-50%,-50%) scale(1.08); }
}

/* ── Logo section ──────────────────────────────────────────────── */
.pls-logo-section {
  position: relative;
  width: 120px;
  height: 120px;
  display: flex;
  align-items: center;
  justify-content: center;
  animation: pls-appear .9s cubic-bezier(0.16, 1, 0.3, 1) both;
}

@keyframes pls-appear {
  from { opacity: 0; transform: scale(0.9); }
  to   { opacity: 1; transform: scale(1);   }
}

/* ── Arc spinner (satu elemen, gabungan ring + arc) ───────────────── */
.pls-spinner {
  position: absolute;
  inset: -24px;
  border-radius: 50%;
  border: 1px solid var(--gold-a12);
  background: conic-gradient(
    from 0turn,
    transparent      0%,
    transparent     65%,
    var(--gold-a30) 80%,
    var(--gold-b)   95%,
    transparent     100%
  );
  -webkit-mask: radial-gradient(farthest-side, transparent calc(100% - 1.5px), #fff calc(100% - 1.5px));
  mask: radial-gradient(farthest-side, transparent calc(100% - 1.5px), #fff calc(100% - 1.5px));
  will-change: transform;
  animation: pls-spin 2.2s linear infinite;
  pointer-events: none;
}

@keyframes pls-spin {
  to { transform: rotate(360deg); }
}

/* ── Logo image (statis, tanpa animasi glow terus-menerus) ───────── */
.pls-logo {
  width: 100%;
  height: 100%;
  object-fit: contain;
  border-radius: 50%;
  display: block;
  user-select: none;
  -webkit-user-drag: none;
  position: relative;
  z-index: 1;
  filter: drop-shadow(0 0 14px rgba(212,170,95,0.35));
}

/* ── Reduced motion ────────────────────────────────────────────── */
@media (prefers-reduced-motion: reduce) {
  .pls-glow,
  .pls-spinner {
    animation: none !important;
  }
  #paroki-loading-screen {
    transition-duration: 0.2s !important;
  }
}
</style>

<script>
(function () {
  'use strict';

  var LS_KEY = 'pls_visited';
  var el     = document.getElementById('paroki-loading-screen');
  var MIN_MS = 1200;

  if (sessionStorage.getItem(LS_KEY)) {
    if (el) el.remove();
    return;
  }

  sessionStorage.setItem(LS_KEY, '1');

  var startTime = Date.now();
  var dismissed = false;

  function cleanup() {
    if (el && el.parentNode) el.parentNode.removeChild(el);
  }

  function dismiss() {
    if (dismissed || !el) return;
    dismissed = true;
    el.classList.add('pls--hidden');
    el.addEventListener('transitionend', cleanup, { once: true });
    // fallback jika transitionend tidak terpicu
    setTimeout(cleanup, 1000);
  }

  function onReady() {
    var elapsed = Date.now() - startTime;
    var remain  = Math.max(0, MIN_MS - elapsed);
    setTimeout(dismiss, remain);
  }

  if (document.readyState === 'complete') {
    onReady();
  } else {
    window.addEventListener('load', onReady, { once: true });
    setTimeout(dismiss, 4500);
  }
})();
</script>