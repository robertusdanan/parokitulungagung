<?php
/**
 * components/loading_screen.php
 * Loading screen minimal & elegan — hanya logo + spinner.
 * Tampil sekali per sesi. Include di dalam <body> sebelum konten apapun.
 */

$_currentPath = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
$_currentPath = rtrim($_currentPath, '/') ?: '/';
if ($_currentPath !== '/') return;
?>

<div id="paroki-loading-screen" aria-hidden="true" role="presentation">

  <!-- Ambient glow background -->
  <div class="pls-ambient"></div>

  <!-- Logo + spinner -->
  <div class="pls-logo-section">
    <!-- Outer slow-rotating faint ring -->
    <div class="pls-orbit"></div>
    <!-- Arc spinner -->
    <div class="pls-spinner"></div>
    <!-- Soft glow halo -->
    <div class="pls-aura"></div>
    <!-- Logo -->
    <img
      src="/img/parokitulungagung.png"
      alt="Paroki Santa Maria Dengan Tidak Bernoda Asal Tulungagung"
      class="pls-logo"
      width="120"
      height="120"
      draggable="false"
    >
  </div>

</div>

<style>
/* ================================================================
   PAROKI LOADING SCREEN — Pure Minimal Edition
   ================================================================ */

@import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300&display=swap');

/* ── Custom Properties ─────────────────────────────────────────── */
#paroki-loading-screen {
  --gold:      #d4aa5f;
  --gold-b:    #eedfa0;
  --gold-a70:  rgba(212,170,95,0.70);
  --gold-a30:  rgba(212,170,95,0.30);
  --gold-a12:  rgba(212,170,95,0.12);
  --gold-a06:  rgba(212,170,95,0.06);
  --ease-out-expo: cubic-bezier(0.16, 1, 0.3, 1);
  --transition-out: opacity 1s cubic-bezier(0.4, 0, 0.2, 1),
                    visibility 1s cubic-bezier(0.4, 0, 0.2, 1);
}

/* ── Root container ────────────────────────────────────────────── */
#paroki-loading-screen {
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
  transition: var(--transition-out);
}

#paroki-loading-screen.pls--hidden {
  opacity: 0;
  visibility: hidden;
  pointer-events: none;
}

/* ── Ambient glow ──────────────────────────────────────────────── */
.pls-ambient {
  position: absolute;
  width: 520px;
  height: 520px;
  left: 50%;
  top: 50%;
  transform: translate(-50%, -50%);
  background: radial-gradient(
    ellipse,
    rgba(212,170,95,0.07) 0%,
    rgba(212,170,95,0.02) 45%,
    transparent 72%
  );
  pointer-events: none;
  animation: pls-ambient 7s ease-in-out infinite;
}

@keyframes pls-ambient {
  0%, 100% { opacity: 0.6; transform: translate(-50%,-50%) scale(1);    }
  50%       { opacity: 1;   transform: translate(-50%,-50%) scale(1.12); }
}

/* ── Logo section ──────────────────────────────────────────────── */
.pls-logo-section {
  position: relative;
  width: 120px;
  height: 120px;
  display: flex;
  align-items: center;
  justify-content: center;
  animation: pls-appear 1.4s var(--ease-out-expo) 0.1s both;
}

@keyframes pls-appear {
  from { opacity: 0; transform: scale(0.88); }
  to   { opacity: 1; transform: scale(1);    }
}

/* ── Soft aura behind logo ─────────────────────────────────────── */
.pls-aura {
  position: absolute;
  inset: -28px;
  border-radius: 50%;
  background: radial-gradient(
    circle,
    rgba(212,170,95,0.16) 0%,
    rgba(212,170,95,0.05) 55%,
    transparent 75%
  );
  animation: pls-aura-pulse 4s ease-in-out infinite;
  pointer-events: none;
}

@keyframes pls-aura-pulse {
  0%, 100% { opacity: 0.7; transform: scale(1);    }
  50%       { opacity: 1;   transform: scale(1.10); }
}

/* ── Outer slow orbit ring ─────────────────────────────────────── */
.pls-orbit {
  position: absolute;
  inset: -32px;
  border-radius: 50%;
  border: 1px solid var(--gold-a12);
  animation: pls-orbit-pulse 5s ease-in-out infinite 0.8s;
  pointer-events: none;
}

@keyframes pls-orbit-pulse {
  0%, 100% { opacity: 0.5; transform: scale(1);    }
  50%       { opacity: 1;   transform: scale(1.04); border-color: var(--gold-a30); }
}

/* ── Arc spinner ───────────────────────────────────────────────── */
/* Uses conic-gradient + mask to produce a fading arc */
.pls-spinner {
  position: absolute;
  inset: -20px;
  border-radius: 50%;
  background: conic-gradient(
    from 0turn,
    transparent        0%,
    transparent       60%,
    var(--gold-a30)   75%,
    var(--gold-a70)   88%,
    var(--gold-b)     95%,
    var(--gold-a70)   98%,
    transparent      100%
  );
  /* ring mask: keep only the outer 1.5px rim */
  -webkit-mask:
    radial-gradient(
      farthest-side,
      transparent calc(100% - 1.5px),
      #fff        calc(100% - 1.5px)
    );
  mask:
    radial-gradient(
      farthest-side,
      transparent calc(100% - 1.5px),
      #fff        calc(100% - 1.5px)
    );
  animation: pls-spin 2.4s linear infinite;
  pointer-events: none;
}

@keyframes pls-spin {
  to { transform: rotate(360deg); }
}

/* ── Logo image ────────────────────────────────────────────────── */
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
  animation: pls-logo-glow 5s ease-in-out infinite;
}

@keyframes pls-logo-glow {
  0%, 100% {
    filter: drop-shadow(0 0 10px rgba(212,170,95,0.25))
            drop-shadow(0 0 28px rgba(212,170,95,0.08));
  }
  50% {
    filter: drop-shadow(0 0 20px rgba(212,170,95,0.55))
            drop-shadow(0 0 52px rgba(212,170,95,0.20));
  }
}

/* ── Reduced motion ────────────────────────────────────────────── */
@media (prefers-reduced-motion: reduce) {
  .pls-logo,
  .pls-aura,
  .pls-orbit,
  .pls-ambient,
  .pls-spinner {
    animation: none !important;
  }
  #paroki-loading-screen {
    transition-duration: 0.3s !important;
  }
}
</style>

<script>
(function () {
  'use strict';

  var LS_KEY = 'pls_visited';
  var el     = document.getElementById('paroki-loading-screen');
  var MIN_MS = 1800;

  if (sessionStorage.getItem(LS_KEY)) {
    if (el) el.style.display = 'none';
    return;
  }

  sessionStorage.setItem(LS_KEY, '1');

  var startTime = Date.now();

  function dismiss() {
    if (!el) return;
    el.classList.add('pls--hidden');
    setTimeout(function () {
      if (el && el.parentNode) el.parentNode.removeChild(el);
    }, 1100);
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
    setTimeout(dismiss, 6000);
  }
})();
</script>