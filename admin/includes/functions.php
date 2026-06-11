<?php
/**
 * admin/includes/functions.php
 * Helper functions admin — Supabase Edition
 */

function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function adminBoot(): void {
    $inc = __DIR__;
    require_once $inc . '/config.php';
    require_once $inc . '/auth.php';
    require_once $inc . '/SupabaseClient.php';
    require_once $inc . '/UserManager.php';
    require_once $inc . '/ActivityLogger.php';
    require_once $inc . '/Mailer.php';
    require_once $inc . '/../../includes/SupabaseArticleManager.php';
}

function jsonBody(): array {
    $raw = file_get_contents('php://input');
    return json_decode($raw, true) ?? [];
}

function getDB(): SupabaseClient {
    static $instance = null;
    if (!$instance) $instance = new SupabaseClient();
    return $instance;
}

function getSheets(): SupabaseClient { return getDB(); }

function getLogger(): ActivityLogger {
    static $instance = null;
    if (!$instance) $instance = new ActivityLogger(getDB());
    return $instance;
}

// ── Fungsi Periode ─────────────────────────────────────────────────────

function generatePeriodeList(): array {
    $list = [];
    for ($y = 2020; $y <= 2044; $y += 4) {
        $list[] = $y . '-' . ($y + 3);
    }
    return $list;
}

function getCurrentPeriode(): string {
    $year   = (int) date('Y');
    $offset = ($year - 2020) % 4;
    $start  = $year - $offset;
    if ($start < 2020) $start = 2020;
    return $start . '-' . ($start + 3);
}

// ── Admin Layout ────────────────────────────────────────────────────────

function adminHeader(string $pageTitle, string $activePage, array $user): void {
    $pages        = PAGE_LABELS;
    $isSuperadmin = $user['role'] === ROLE_SUPERADMIN;
    $permsMap     = $isSuperadmin ? [] : getPermissionsMap($user);

    // Data pages = semua kecuali artikel, master, dan media (media masuk ke Sistem)
    $excludeFromData = array_merge(ARTIKEL_PAGES, ['master', 'media']);
    $dataPageKeys = array_keys(array_diff_key($pages, array_flip($excludeFromData)));

    if ($isSuperadmin) {
        $visibleDataPages    = $dataPageKeys;
        $visibleArtikelPages = ARTIKEL_PAGES;
    } else {
        $visibleDataPages    = array_intersect($dataPageKeys, array_keys($permsMap));
        $visibleArtikelPages = array_intersect(ARTIKEL_PAGES, array_keys($permsMap));
    }

    $pageActionsJs = $isSuperadmin
        ? array_merge(PAGE_ACTIONS, ['publish'])
        : ($user['page_actions'] ?? ['view']);

    $canSeeMedia    = $isSuperadmin || array_key_exists('media',      $permsMap);
    $canSeeMaster   = $isSuperadmin || array_key_exists('master',     $permsMap);
    $canSeeKategori = $isSuperadmin || array_key_exists('kategorial', $permsMap);
    ?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle) ?> — <?= ADMIN_TITLE ?></title>
  <!-- Admin fonts — non-blocking -->
  <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=DM+Sans:wght@300;400;500;600&family=DM+Mono:wght@400;500&display=swap"
        media="print" onload="this.media='all'">
  <noscript>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=DM+Sans:wght@300;400;500;600&family=DM+Mono:wght@400;500&display=swap">
  </noscript>
  <link rel="icon" href="/favicon.ico?v=2">
  <link rel="stylesheet" href="/admin/css/admin.css">
  <script src="/admin/js/admin.js" defer></script>
  <script>
    window.PAGE_ACTIONS = <?= json_encode($pageActionsJs) ?>;
    window.can = function(action) {
      return window.PAGE_ACTIONS.indexOf(action) !== -1;
    };
  </script>
</head>
<body>
<div class="admin-layout">
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
      <span class="brand-cross">✝</span>
      <div>
        <div class="brand-title">SMDTBA</div>
        <div class="brand-sub">Admin Panel</div>
      </div>
    </div>

    <nav class="sidebar-nav">
      <!-- ── Menu Utama ── -->
      <div class="nav-section-label">Menu</div>
      <a href="/admin/dashboard.php" class="nav-item <?= $activePage === 'dashboard' ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
        Dashboard
      </a>

      <!-- ── Data Paroki ── -->
      <?php if (!empty($visibleDataPages)): ?>
      <div class="nav-section-label">Data Paroki</div>
      <?php
      $dataIcons = [
          'galeri'       => '<path d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><circle cx="12" cy="13" r="3"/>',
          'petugas'      => '<path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/>',
          'wilayah'      => '<path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>',
          'asisten_imam' => '<path d="M12 14l9-5-9-5-9 5 9 5z"/><path d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/>',
          'dpp_bgkp'     => '<path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>',
          'agenda'       => '<rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>',
          'umkm'         => '<path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/>',
          'kategorial'   => '<path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/>',
      ];
      foreach ($visibleDataPages as $pg):
          if (!isset($pages[$pg])) continue;
          $pgActions  = $isSuperadmin ? PAGE_ACTIONS : ($permsMap[$pg] ?? ['view']);
          if (empty($pgActions)) $pgActions = ['view'];
          $isViewOnly = (count($pgActions) === 1 && in_array('view', $pgActions));
      ?>
      <a href="/admin/pages/<?= e($pg) ?>.php" class="nav-item <?= $activePage === $pg ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><?= $dataIcons[$pg] ?? '' ?></svg>
        <?= e($pages[$pg]) ?>
        <?php if ($isViewOnly && !$isSuperadmin): ?>
          <span class="badge badge-gray" style="font-size:9px;margin-left:auto;padding:2px 6px">View</span>
        <?php endif; ?>
      </a>
      <?php endforeach; ?>

      <?php endif; ?>

      <!-- ── Artikel ── -->
      <?php if (!empty($visibleArtikelPages)): ?>
      <div class="nav-section-label">Artikel</div>
      <?php $artikelActive = in_array($activePage, ARTIKEL_PAGES) || $activePage === 'artikel'; ?>
      <a href="/admin/pages/artikel.php" class="nav-item <?= $artikelActive ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
          <path d="M4 19.5A2.5 2.5 0 016.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/>
        </svg>
        Kelola Artikel
        <span class="badge badge-gold" style="font-size:9px;margin-left:auto;padding:2px 6px">
          <?= count($visibleArtikelPages) ?> menu
        </span>
      </a>
      <?php endif; ?>

      <!-- ── Sistem (superadmin only + media) ── -->
      <?php if ($isSuperadmin || $canSeeMaster || $canSeeMedia): ?>
      <div class="nav-section-label">Sistem</div>

      <?php if ($canSeeMaster): ?>
      <a href="/admin/pages/master.php" class="nav-item <?= $activePage === 'master' ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
          <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
        </svg>
        Master Data
        <span class="badge badge-gray" style="font-size:9px;margin-left:auto;padding:2px 6px">3 data</span>
      </a>
      <?php endif; ?>

      <?php if ($canSeeMedia): ?>
      <a href="/admin/pages/media.php" class="nav-item <?= $activePage === 'media' ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
          <rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/>
        </svg>
        Media Manager
      </a>
      <?php endif; ?>

      <?php if ($isSuperadmin): ?>
      <a href="/admin/users.php" class="nav-item <?= $activePage === 'users' ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
        Manajemen User
      </a>
      <a href="/admin/activity.php" class="nav-item <?= $activePage === 'activity' ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
        Log Aktivitas
      </a>
      <a href="/admin/pages/seo.php" class="nav-item <?= in_array($activePage, ['seo','seo-artikel']) ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35M11 8v6M8 11h6"/></svg>
        SEO Generator
        <span class="badge badge-gold" style="font-size:9px;margin-left:auto;padding:2px 6px">✦ AI</span>
      </a>
      <?php endif; ?>
      <?php endif; ?>

    </nav>

    <div class="sidebar-footer">
      <?php
      $profilFotoPath = '';
      foreach (['webp','jpg','png'] as $_ext) {
          $_f = rtrim($_SERVER['DOCUMENT_ROOT'],'/') . '/img/admin/profil/profil-' . $user['id'] . '.' . $_ext;
          if (file_exists($_f)) {
              $profilFotoPath = '/img/admin/profil/profil-' . $user['id'] . '.' . $_ext . '?v=' . filemtime($_f);
              break;
          }
      }
      ?>
      <a href="/admin/profil.php" class="user-info" style="text-decoration:none;flex:1;min-width:0" title="Pengaturan Profil">
        <div class="user-avatar" style="<?= $profilFotoPath ? 'background:transparent;padding:0;overflow:hidden' : '' ?>">
          <?php if ($profilFotoPath): ?>
            <img src="<?= e($profilFotoPath) ?>" style="width:100%;height:100%;object-fit:cover;border-radius:50%;display:block" alt="<?= e($user['username']) ?>">
          <?php else: ?>
            <?= strtoupper(substr($user['username'], 0, 1)) ?>
          <?php endif; ?>
        </div>
        <div style="min-width:0">
          <div class="user-name"><?= e(!empty($user['nama']) ? $user['nama'] : $user['username']) ?></div>
          <div class="user-role" title="Login: <?= e($user['username']) ?>"><?= $user['role'] === ROLE_SUPERADMIN ? 'Super Admin' : 'Admin' ?></div>
        </div>
      </a>
      <a href="/admin/logout.php" class="btn-logout" title="Logout">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
      </a>
    </div>
  </aside>

  <div class="main-area">
    <header class="topbar">
      <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar">
        <span></span><span></span><span></span>
      </button>
      <div class="topbar-title"><?= e($pageTitle) ?></div>
      <div class="topbar-right">
        <span class="topbar-user"><?= e($user['username']) ?></span>
      </div>
    </header>
    <main class="main-content">
    <div id="toast-container"></div>
    <?php
}

function adminFooter(): void { ?>
    </main>
  </div>
</div>

<script>
(function(){
  const WARN_MS  = <?= ((SESSION_LIFETIME - 300) * 1000) ?>;
  const TOTAL_MS = <?= (SESSION_LIFETIME * 1000) ?>;
  let warnTimer, expireTimer;
  function start() {
    clearTimeout(warnTimer); clearTimeout(expireTimer);
    warnTimer = setTimeout(function() {
      if (typeof toast === 'function')
        toast('Sesi Hampir Habis', 'Sesi Anda akan berakhir dalam 5 menit. Simpan pekerjaan Anda.', 'warning', 15000);
    }, WARN_MS);
    expireTimer = setTimeout(function() {
      window.location.href = '/admin/index.php?expired=1';
    }, TOTAL_MS);
  }
  ['click','keydown','scroll'].forEach(function(ev) {
    document.addEventListener(ev, function() {
      if (!window._lastPing || Date.now() - window._lastPing > 600000) {
        window._lastPing = Date.now();
        fetch('/admin/api/session_ping.php', { method:'POST' }).catch(function(){});
      }
      start();
    }, { passive: true });
  });
  start();
})();
</script>

</body>
</html>
    <?php
}

// ── Cache Helpers ──────────────────────────────────────────────────────

if (!defined('CACHE_DIR')) {
    define('CACHE_DIR', rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/cache/supabase');
}

function invalidateCache(string $table = '*'): void {
    $dir = CACHE_DIR;
    if (!is_dir($dir)) return;
    $files = glob($dir . '/*.json') ?: [];
    foreach ($files as $file) {
        if ($table === '*' || str_starts_with(basename($file), $table . '_')) {
            @unlink($file);
        }
    }
}

function getCacheStatus(): array {
    $dir   = defined('CACHE_DIR') ? CACHE_DIR : '';
    $files = ($dir && is_dir($dir)) ? (glob($dir . '/*.json') ?: []) : [];
    $sizeB = 0; $oldest = null;
    foreach ($files as $f) {
        $sizeB += @filesize($f);
        $mt = @filemtime($f);
        if ($mt && ($oldest === null || $mt < $oldest)) $oldest = $mt;
    }
    return ['count' => count($files), 'size_kb' => round($sizeB / 1024, 1), 'oldest' => $oldest];
}