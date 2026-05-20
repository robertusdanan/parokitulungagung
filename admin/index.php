<?php
/**
 * admin/index.php — Supabase Edition
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/SupabaseClient.php';
require_once __DIR__ . '/includes/UserManager.php';
require_once __DIR__ . '/includes/ActivityLogger.php';
require_once __DIR__ . '/includes/functions.php';

startAdminSession();
if (!empty($_SESSION['admin_user'])) {
    header('Location: /admin/dashboard.php');
    exit;
}

$error   = '';
$expired = isset($_GET['expired']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ── Rate limiting: maks 5 percobaan gagal per IP per 10 menit ─────
    $ipKey     = 'lf_' . md5($_SERVER['REMOTE_ADDR'] ?? 'x');
    $cacheFile = sys_get_temp_dir() . '/' . $ipKey . '.json';
    $now       = time();
    $attempts  = [];
    if (file_exists($cacheFile)) {
        $attempts = json_decode(file_get_contents($cacheFile), true) ?: [];
    }
    $attempts = array_values(array_filter($attempts, fn($t) => $now - $t < 600));
    if (count($attempts) >= 5) {
        $waitSec = 600 - ($now - min($attempts));
        $error   = 'Terlalu banyak percobaan login gagal. Coba lagi dalam ' . ceil($waitSec / 60) . ' menit.';
    } else {

    // ── Verifikasi Cloudflare Turnstile ──────────────────────────────
    $turnstileToken  = $_POST['cf-turnstile-response'] ?? '';
    $turnstileSecret = '0x4AAAAAADHj3YWU-ibI3_2sMtUMm5viLro';
    $turnstileIp     = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';

    $tsVerify = @file_get_contents('https://challenges.cloudflare.com/turnstile/v0/siteverify', false,
        stream_context_create(['http' => [
            'method'  => 'POST',
            'header'  => 'Content-Type: application/x-www-form-urlencoded',
            'content' => http_build_query([
                'secret'   => $turnstileSecret,
                'response' => $turnstileToken,
                'remoteip' => $turnstileIp,
            ]),
        ]])
    );
    $tsResult = $tsVerify ? json_decode($tsVerify, true) : ['success' => false];

    if (empty($tsResult['success'])) {
        $error = 'Verifikasi keamanan gagal. Silakan coba lagi.';
    } else {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        try {
            $db     = new SupabaseClient();
            $um     = new UserManager($db);
            $user   = $um->verifyLogin($username, $password);

            if ($user) {
                session_regenerate_id(true); // Cegah session fixation
                @unlink($cacheFile);         // Reset counter gagal
                $_SESSION['admin_user'] = [
                    'id'          => $user['id'],
                    'username'    => $user['username'],
                    'email'       => $user['email']       ?? '',
                    'role'        => $user['role'],
                    'permissions' => $user['permissions'] ?? [],
                    'nama'        => $user['nama']        ?? '',
                ];
                $_SESSION['admin_expire'] = time() + SESSION_LIFETIME;

                $logger = new ActivityLogger($db);
                $logger->log($_SESSION['admin_user'], 'LOGIN', 'auth', 'Login berhasil');

                header('Location: /admin/dashboard.php');
                exit;
            } else {
                $attempts[] = $now;
                file_put_contents($cacheFile, json_encode($attempts));
                $error = 'Username atau password salah, atau akun tidak aktif.';
            }
        } catch (Throwable $e) {
            $error = 'Gagal menghubungi database. Periksa konfigurasi Supabase.';
            error_log('[index.php] ' . $e->getMessage());
        }
    } else {
        $error = 'Mohon isi username dan password.';
    }

    } // end rate-limit
    } // end turnstile
}

?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login — <?= ADMIN_TITLE ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="icon" href="/favicon.ico">
  <link rel="stylesheet" href="/admin/css/admin.css">
  <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
</head>
<body>
<div class="login-page">
  <div class="login-bg-art"></div>
  <div class="login-box">
    <div class="login-logo">
      <span class="cross">✝</span>
      <h1>SMDTBA</h1>
      <p>Panel Admin Paroki</p>
    </div>

    <?php if ($expired): ?>
    <div class="login-error">Sesi Anda telah berakhir. Silakan login kembali.</div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="login-error"><?= e($error) ?></div>
    <?php endif; ?>

    <form class="login-form" method="POST" autocomplete="on">
      <div class="form-group">
        <label for="loginUsername">Username</label>
        <input type="text" name="username" id="loginUsername" class="form-control"
               value="<?= e($_POST['username'] ?? '') ?>"
               placeholder="Masukkan username"
               autocomplete="username" required autofocus>
      </div>
      <div class="form-group">
        <label for="loginPw">Password</label>
        <div style="position:relative">
          <input type="password" name="password" id="loginPw" class="form-control"
                 placeholder="Masukkan password"
                 autocomplete="current-password" required style="padding-right:40px">
          <button type="button"
                  id="pwToggleBtn"
                  style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-muted);cursor:pointer;padding:4px"
                  tabindex="-1" aria-label="Tampilkan password">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
        </div>
      </div>
      <!-- Cloudflare Turnstile -->
      <div class="cf-turnstile"
           data-sitekey="0x4AAAAAADHj3fP1igadgQSi"
           data-theme="dark"
           style="margin-bottom:12px"></div>
      <button type="submit" class="btn btn-primary login-btn">Masuk</button>
      <div style="text-align:right;margin-top:8px">
        <a href="/admin/forgot-password.php" style="font-size:12px;color:var(--text-muted);text-decoration:none">
          Lupa password?
        </a>
      </div>
    </form>

    <div style="display:flex;align-items:center;gap:10px;margin:20px 0 16px">
      <div style="flex:1;height:1px;background:var(--border)"></div>
      <span style="font-size:11.5px;color:var(--text-muted);white-space:nowrap">Belum punya akun?</span>
      <div style="flex:1;height:1px;background:var(--border)"></div>
    </div>

    <a href="/admin/register.php" class="btn btn-secondary"
       style="width:100%;justify-content:center;padding:10px;font-size:14px;text-decoration:none">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15">
        <path d="M16 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
        <circle cx="8.5" cy="7" r="4"/>
        <line x1="20" y1="8" x2="20" y2="14"/>
        <line x1="23" y1="11" x2="17" y2="11"/>
      </svg>
      Daftar Akun
    </a>

    <div style="text-align:center;margin-top:14px">
      <a href="/" style="font-size:12px;color:var(--text-muted);">← Kembali ke Website</a>
    </div>
  </div>
</div>
<script>
document.getElementById('pwToggleBtn').addEventListener('click', function() {
  var i = document.getElementById('loginPw');
  var show = i.type === 'password';
  i.type = show ? 'text' : 'password';
  this.style.color = show ? 'var(--accent)' : 'var(--text-muted)';
});
</script>
</body>
</html>