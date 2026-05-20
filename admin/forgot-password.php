<?php
/**
 * admin/forgot-password.php
 * Halaman lupa password — kirim link reset ke email user
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/SupabaseClient.php';
require_once __DIR__ . '/includes/UserManager.php';
require_once __DIR__ . '/includes/Mailer.php';

startAdminSession();
if (!empty($_SESSION['admin_user'])) {
    header('Location: /admin/dashboard.php'); exit;
}

$error   = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = strtolower(trim($_POST['identifier'] ?? ''));

    if (!$identifier) {
        $error = 'Masukkan username atau email Anda.';
    } else {
        try {
            $db   = new SupabaseClient();
            $um   = new UserManager($db);

            // Cari user by username atau email
            $user = $um->findByUsername($identifier);
            if (!$user) $user = $um->findByEmail($identifier);

            // Selalu tampilkan pesan sukses meski user tidak ditemukan (keamanan)
            if ($user && !empty($user['email'])) {
                // Generate token aman
                $token  = bin2hex(random_bytes(32)); // 64 hex chars
                $expiry = time() + 3600; // 1 jam

                // Simpan token ke DB
                $db->update('users', 'id', $user['id'], [
                    'reset_token'     => $token,
                    'reset_token_exp' => $expiry,
                ]);

                // Kirim email
                $resetUrl = ((function_exists('is_https') && is_https() ? 'https' : 'http')
                          . '://' . $_SERVER['HTTP_HOST']
                          . '/admin/reset-password.php?token=' . $token;

                $htmlBody = _buildResetEmail(
                    $user['nama'] ?: $user['username'],
                    $user['username'],
                    $resetUrl
                );

                $mailer = new Mailer();
                $mailer->send(
                    $user['email'],
                    $user['nama'] ?: $user['username'],
                    'Reset Password — Admin Panel SMDTBA',
                    $htmlBody
                );

                error_log('[forgot-password] Reset link sent to: ' . $user['email']);
            }

            $success = true;

        } catch (Throwable $e) {
            error_log('[forgot-password] Error: ' . $e->getMessage());
            // DEBUG sementara — hapus setelah masalah ditemukan
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

function _buildResetEmail(string $nama, string $username, string $resetUrl): string {
    $expHuman = '1 jam';
    $photoUrl = 'https://www.parokitulungagung.org/img/parokitulungagung.png';

    return <<<HTML
<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f4f1eb;font-family:'Segoe UI',sans-serif">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f1eb;padding:32px 16px">
  <tr><td align="center">
    <table width="520" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.08)">
      <!-- Header -->
      <tr><td style="background:#1a1410;padding:28px 36px;text-align:center">
        <img src="{$photoUrl}"
             alt="Komsos Paroki SMDTBA"
             width="80" height="80"
             style="width:80px;height:80px;display:block;margin:0 auto 10px">
        <div style="font-family:'Georgia',serif;font-size:18px;color:#fff;font-weight:600">Komsos Paroki SMDTBA</div>
        <div style="font-size:11px;color:#9a8a70;letter-spacing:.12em;text-transform:uppercase;margin-top:3px">Tulungagung</div>
      </td></tr>
      <!-- Body -->
      <tr><td style="padding:36px 36px 28px">
        <p style="margin:0 0 4px;font-size:15px;color:#2e2013">Halo, <strong>{$nama}</strong></p>
        <p style="margin:0 0 22px;font-size:14px;color:#5a4a38">Username: <code style="background:#f8f3eb;padding:2px 8px;border-radius:4px;font-family:monospace;color:#c9a84c">{$username}</code></p>
        <p style="margin:0 0 20px;font-size:14px;color:#5a4a38;line-height:1.65">
          Kami menerima permintaan untuk mereset password akun Anda. Klik tombol di bawah untuk membuat password baru.
        </p>
        <table cellpadding="0" cellspacing="0" style="margin:0 auto 24px">
          <tr><td style="background:#c9a84c;border-radius:8px;padding:14px 32px">
            <a href="{$resetUrl}" style="color:#1a1410;font-size:14px;font-weight:700;text-decoration:none;letter-spacing:.03em">Reset Password Saya →</a>
          </td></tr>
        </table>
        <p style="margin:0 0 8px;font-size:12.5px;color:#8a7a6a;line-height:1.6">
          Atau salin link berikut ke browser Anda:
        </p>
        <div style="background:#f8f3eb;border:1px solid #e8dfc8;border-radius:6px;padding:10px 14px;margin-bottom:20px">
          <a href="{$resetUrl}" style="font-size:11.5px;color:#c9a84c;word-break:break-all">{$resetUrl}</a>
        </div>
        <div style="background:#fef9ec;border:1px solid #f0e0a0;border-radius:6px;padding:12px 14px">
          <p style="margin:0;font-size:12px;color:#7a6030;line-height:1.6">
            ⚠ Link ini berlaku selama <strong>{$expHuman}</strong> dan hanya bisa digunakan <strong>sekali</strong>.
            Jika Anda tidak meminta reset password, abaikan email ini — akun Anda tetap aman.
          </p>
        </div>
      </td></tr>
      <!-- Footer -->
      <tr><td style="border-top:1px solid #f0ebe0;padding:16px 36px;text-align:center">
        <p style="margin:0;font-size:11px;color:#b0a090">
          Email ini dikirim otomatis oleh sistem · Paroki SMDTBA Tulungagung
        </p>
      </td></tr>
    </table>
  </td></tr>
</table>
</body>
</html>
HTML;
}

function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Lupa Password — <?= ADMIN_TITLE ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/admin/css/admin.css">
</head>
<body>
<div class="login-page">
  <div class="login-bg-art"></div>
  <div class="login-box">
    <div class="login-logo">
      <span class="cross">✝</span>
      <h1>SMDTBA</h1>
      <p>Reset Password</p>
    </div>

    <?php if ($success): ?>
    <div style="background:rgba(60,179,113,.1);border:1px solid rgba(60,179,113,.3);color:var(--success);border-radius:var(--radius-sm);padding:16px;font-size:13.5px;text-align:center;line-height:1.7;margin-bottom:16px">
      <div style="font-size:28px;margin-bottom:8px">📧</div>
      <strong>Email terkirim!</strong><br>
      Jika username/email Anda terdaftar, kami telah mengirimkan link reset password.<br>
      <small style="color:var(--text-muted);font-size:11.5px">Cek folder Spam jika tidak ada di Inbox. Link berlaku 1 jam.</small>
    </div>
    <?php else: ?>

    <p style="font-size:13px;color:var(--text-secondary);margin-bottom:20px;text-align:center;line-height:1.6">
      Masukkan username atau email yang terdaftar. Kami akan mengirimkan link untuk membuat password baru.
    </p>

    <?php if ($error): ?>
    <div class="login-error"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="POST" autocomplete="off">
      <div class="form-group">
        <label>Username atau Email</label>
        <input type="text" name="identifier" class="form-control"
               value="<?= e($_POST['identifier'] ?? '') ?>"
               placeholder="username atau email@contoh.com"
               required autofocus>
      </div>
      <button type="submit" class="btn btn-primary login-btn">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
        Kirim Link Reset
      </button>
    </form>

    <?php endif; ?>

    <div style="text-align:center;margin-top:18px">
      <a href="/admin" style="font-size:12.5px;color:var(--text-muted);text-decoration:none">
        ← Kembali ke Login
      </a>
    </div>
  </div>
</div>
</body>
</html>