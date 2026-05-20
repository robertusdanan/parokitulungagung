<?php
/**
 * components/page_header.php
 * Header terpusat: logo + menubar + menu dropdown
 * Dipakai oleh SEMUA halaman publik.
 */
?>
<?php include __DIR__ . '/loading_screen.php'; ?>

<div id="divmenubar">
  <button id="btnmenu" aria-label="Buka menu navigasi">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true">
      <line x1="3" y1="6" x2="21" y2="6"/>
      <line x1="3" y1="12" x2="21" y2="12"/>
      <line x1="3" y1="18" x2="21" y2="18"/>
    </svg>
    Menu
  </button>

  <div id="login-portal-wrap">
    <button class="btn-login-portal" onclick="window.location.href='/admin'" title="Masuk ke Panel Admin">
      <span class="login-dot"></span>
      <span class="login-label">Login</span>
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13" aria-hidden="true">
        <path d="M15 3h4a2 2 0 012 2v14a2 2 0 01-2 2h-4"/>
        <polyline points="10 17 15 12 10 7"/>
        <line x1="15" y1="12" x2="3" y2="12"/>
      </svg>
    </button>
  </div>

  <div id="menu-container">
    <?php
    // Path relatif dari mana pun file ini di-include
    $menuFile = __DIR__ . '/menu.php';
    if (file_exists($menuFile)) include $menuFile;
    ?>
  </div>
</div>

<div id="outer-wrapper">
<a id="top"></a>

<header class="divheaderparoki">
  <a href="/">
    <img src="/img/header-logo-1.webp"
         alt="Paroki Santa Maria Dengan Tidak Bernoda Asal Tulungagung"
         width="4268" height="355"
         style="width:100%;height:auto;border:0;display:block"
         fetchpriority="high" loading="eager" decoding="async">
  </a>
</header>

<?php
$isHomepage = ($_SERVER['REQUEST_URI'] === '/' || $_SERVER['REQUEST_URI'] === '/index.php');
if ($isHomepage): ?>
<h1 style="position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0;padding:0;margin:0;" aria-hidden="false">
  Gereja Katolik Tulungagung – Paroki Santa Maria Dengan Tidak Bernoda Asal
</h1>
<?php endif; ?>