<?php
/**
 * components/header_auth.php
 * ------------------------------------------------------------------
 * Dipakai oleh SEMUA halaman publik (index.php, page_header.php, dst)
 * untuk mendeteksi apakah admin sedang login, lalu menampilkan
 * foto profil + dropdown akun di menu (menggantikan tombol "Login")
 * tanpa memaksa redirect apa pun — murni read-only status check.
 * ------------------------------------------------------------------
 */

if (!defined('HEADER_AUTH_LOADED')) {
    define('HEADER_AUTH_LOADED', true);

    $GLOBALS['__adminLoggedIn']   = false;
    $GLOBALS['__adminUser']       = null;
    $GLOBALS['__adminFotoPath']   = '';
    $GLOBALS['__adminArtikelUrl'] = null;

    try {
        require_once __DIR__ . '/../admin/includes/config.php';
        require_once __DIR__ . '/../admin/includes/auth.php';

        // Gunakan @ karena pada beberapa halaman publik fungsi ini bisa terpanggil
        // setelah sebagian output terkirim; session tetap terbaca dari cookie yang ada.
        @startAdminSession();

        $sessionOk = !empty($_SESSION['admin_user'])
            && (empty($_SESSION['admin_expire']) || time() <= $_SESSION['admin_expire']);

        if ($sessionOk) {
            $GLOBALS['__adminLoggedIn'] = true;
            $user = $_SESSION['admin_user'];
            $GLOBALS['__adminUser'] = $user;

            // ── Cari foto profil admin dari R2 CDN ──
            if (!empty($user['id'])) {
                $GLOBALS['__adminFotoPath'] = adminFotoUrl($user['id']);
            }

            // ── Tentukan tujuan "Tulis Artikel" sesuai hak akses ──
            if ($user['role'] === ROLE_SUPERADMIN) {
                $GLOBALS['__adminArtikelUrl'] = '/admin/pages/artikel-editor.php?menu=' . ARTIKEL_PAGES[0];
            } else {
                $perms = getPermissionsMap($user);
                foreach (ARTIKEL_PAGES as $ap) {
                    if (array_key_exists($ap, $perms)) {
                        $GLOBALS['__adminArtikelUrl'] = '/admin/pages/artikel-editor.php?menu=' . $ap;
                        break;
                    }
                }
            }
        }
    } catch (Throwable $e) {
        // Jangan sampai halaman publik ikut error hanya karena status login gagal dicek
        $GLOBALS['__adminLoggedIn']   = false;
        $GLOBALS['__adminUser']       = null;
        $GLOBALS['__adminFotoPath']   = '';
        $GLOBALS['__adminArtikelUrl'] = null;
    }
}

/**
 * Render widget akun admin (avatar + dropdown) ATAU tombol Login biasa.
 * Dipanggil di dalam #login-portal-wrap pada setiap halaman publik.
 */
function render_login_portal(): void
{
    $loggedIn = $GLOBALS['__adminLoggedIn'] ?? false;
    $user     = $GLOBALS['__adminUser'] ?? null;
    $foto     = $GLOBALS['__adminFotoPath'] ?? '';
    $artikel  = $GLOBALS['__adminArtikelUrl'] ?? null;

    if (!$loggedIn || !$user) {
        $currentUrl = $_SERVER['REQUEST_URI'] ?? '/';
        $loginUrl   = '/admin?redirect=' . urlencode($currentUrl);
        ?>
        <button class="btn-login-portal" onclick="window.location.href='<?= htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8') ?>'" title="Masuk ke Panel Admin">
          <span class="login-dot"></span>
          <span class="login-label">Login</span>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13" aria-hidden="true">
            <path d="M15 3h4a2 2 0 012 2v14a2 2 0 01-2 2h-4"/>
            <polyline points="10 17 15 12 10 7"/>
            <line x1="15" y1="12" x2="3" y2="12"/>
          </svg>
        </button>
        <?php
        return;
    }

    $displayName = !empty($user['nama']) ? $user['nama'] : $user['username'];
    $initial     = strtoupper(substr($displayName, 0, 1));
    $roleLabel   = ($user['role'] === ROLE_SUPERADMIN) ? 'Super Admin' : 'Admin';
    ?>
    <div class="admin-account" id="adminAccount">
      <button type="button" class="admin-account-trigger" id="adminAccountBtn"
              aria-haspopup="true" aria-expanded="false"
              title="<?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?>">
        <span class="admin-avatar">
          <span class="admin-avatar-face">
            <?php if ($foto): ?>
              <img src="<?= htmlspecialchars($foto, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?>" loading="lazy" decoding="async">
            <?php else: ?>
              <span class="admin-avatar-initial"><?= htmlspecialchars($initial, ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif; ?>
          </span>
          <span class="admin-status-dot" aria-hidden="true"></span>
        </span>
        <svg class="admin-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12" aria-hidden="true">
          <polyline points="6 9 12 15 18 9"/>
        </svg>
      </button>

      <div class="admin-dropdown" id="adminDropdown" role="menu" aria-label="Menu akun admin">
        <div class="admin-dropdown-header">
          <span class="admin-avatar admin-avatar-lg">
            <span class="admin-avatar-face">
              <?php if ($foto): ?>
                <img src="<?= htmlspecialchars($foto, ENT_QUOTES, 'UTF-8') ?>" alt="">
              <?php else: ?>
                <span class="admin-avatar-initial"><?= htmlspecialchars($initial, ENT_QUOTES, 'UTF-8') ?></span>
              <?php endif; ?>
            </span>
          </span>
          <div class="admin-dropdown-id">
            <div class="admin-dropdown-name"><?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?></div>
            <div class="admin-dropdown-role"><?= htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8') ?></div>
          </div>
        </div>

        <div class="admin-dropdown-sep"></div>

        <a href="/admin/profil.php" class="admin-dropdown-item" role="menuitem">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="16" height="16" aria-hidden="true"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          <span>Detail Profil</span>
        </a>

        <?php if ($artikel): ?>
        <a href="<?= htmlspecialchars($artikel, ENT_QUOTES, 'UTF-8') ?>" class="admin-dropdown-item" role="menuitem">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="16" height="16" aria-hidden="true"><path d="M4 19.5A2.5 2.5 0 016.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/></svg>
          <span>Tulis Artikel</span>
        </a>
        <?php endif; ?>

        <a href="/admin/dashboard.php" class="admin-dropdown-item" role="menuitem">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="16" height="16" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
          <span>Panel Admin</span>
        </a>

        <div class="admin-dropdown-sep"></div>

        <?php
        $logoutUrl = '/admin/logout.php?redirect=' . urlencode($_SERVER['REQUEST_URI'] ?? '/');
        ?>
        <a href="<?= htmlspecialchars($logoutUrl, ENT_QUOTES, 'UTF-8') ?>" class="admin-dropdown-item admin-dropdown-item-danger" role="menuitem">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="16" height="16" aria-hidden="true"><path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
          <span>Logout</span>
        </a>
      </div>
    </div>
    <?php
}

/**
 * Script toggle dropdown akun admin. Aman dipanggil di halaman manapun —
 * tidak melakukan apa-apa jika elemennya tidak ada (belum login).
 */
function render_login_portal_script(): void
{
    ?>
    <script>
    (function () {
      var wrap = document.getElementById('adminAccount');
      var btn  = document.getElementById('adminAccountBtn');
      var menu = document.getElementById('adminDropdown');
      if (!wrap || !btn || !menu) return;

      function closeMenu() {
        wrap.classList.remove('open');
        btn.setAttribute('aria-expanded', 'false');
      }
      function openMenu() {
        wrap.classList.add('open');
        btn.setAttribute('aria-expanded', 'true');
      }

      btn.addEventListener('click', function (e) {
        e.stopPropagation();
        wrap.classList.contains('open') ? closeMenu() : openMenu();
      });
      document.addEventListener('click', function (e) {
        if (!wrap.contains(e.target)) closeMenu();
      });
      document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeMenu();
      });
    })();
    </script>
    <?php
}
