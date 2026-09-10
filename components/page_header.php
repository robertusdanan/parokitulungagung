<?php
/**
 * components/page_header.php
 * Header terpusat: logo + menubar + menu dropdown
 * Dipakai oleh SEMUA halaman publik.
 */
?>
<?php include __DIR__ . '/loading_screen.php'; ?>
<?php require_once __DIR__ . '/header_auth.php'; ?>

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
    <?php render_login_portal(); ?>
  </div>

  <div id="menu-container">
    <?php
    // Path relatif dari mana pun file ini di-include
    $menuFile = __DIR__ . '/menu.php';
    if (file_exists($menuFile)) include $menuFile;
    ?>
  </div>
</div>
<?php render_login_portal_script(); ?>

<div id="outer-wrapper">
<a id="top"></a>

<header class="divheaderparoki">
  <a href="/">
    <img src="https://img.parokitulungagung.org/assets/header-logo-1.webp"
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