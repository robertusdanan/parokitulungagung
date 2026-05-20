<?php
/**
 * admin/reset-password.php
 * Halaman reset password via token yang dikirim ke email
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/SupabaseClient.php';
require_once __DIR__ . '/includes/UserManager.php';

startAdminSession();
if (!empty($_SESSION['admin_user'])) {
    header('Location: /admin/dashboard.php'); exit;
}

$token   = trim($_GET['token'] ?? '');
$error   = '';
$success = false;
$user    = null;

function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

// Validasi token
if (!$token || strlen($token) !== 64 || !preg_match('/^[a-f0-9]+$/', $token)) {
    $error = 'Link reset tidak valid atau sudah kadaluarsa.';
} else {
    try {
        $db   = new SupabaseClient();
        $user = $db->readWhere('users', ['reset_token=eq.' . $token], '', 'id,username,email,nama,reset_token_exp');
        $user = !empty($user) ? $user[0] : null;

        if (!$user) {
            $error = 'Link reset tidak valid atau sudah pernah digunakan.';
        } elseif (time() > ($user['reset_token_exp'] ?? 0)) {
            // Token kadaluarsa — hapus dari DB
            $db->update('users', 'id', $user['id'], [
                'reset_token'     => null,
                'reset_token_exp' => null,
            ]);
            $error = 'Link reset sudah kadaluarsa (berlaku 1 jam). Silakan minta link baru.';
            $user  = null;
        }
    } catch (Throwable $e2) {
        error_log('[reset-password] Token lookup error: ' . $e2->getMessage());
        $error = 'Terjadi kesalahan server. Silakan coba lagi.';
        $user  = null;
    }
}

// Proses set password baru
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
    $password = $_POST['password']  ?? '';
    $confirm  = $_POST['confirm']   ?? '';

    if (strlen($password) < 8) {
        $error = 'Password minimal 8 karakter.';
    } elseif ($password !== $confirm) {
        $error = 'Konfirmasi password tidak cocok.';
    } else {
        try {
            $db2 = new SupabaseClient();
            $um  = new UserManager($db2);

            // Update password + hapus token
            $um->update($user['id'], ['password' => $password]);
            $db2->update('users', 'id', $user['id'], [
                'reset_token'     => null,
                'reset_token_exp' => null,
            ]);

            $success = true;
        } catch (Throwable $e3) {
            error_log('[reset-password] Update error: ' . $e3->getMessage());
            $error = 'Gagal menyimpan password baru. Silakan coba lagi.';
        }
    }
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Reset Password — <?= ADMIN_TITLE ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/admin/css/admin.css">
  <style>
    .pw-strength{height:3px;border-radius:2px;margin-top:5px;background:var(--border);transition:all .3s}
    .pw-toggle{position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-muted);cursor:pointer;padding:4px}
  </style>
</head>
<body>
<div class="login-page">
  <div class="login-bg-art"></div>
  <div class="login-box">
    <div class="login-logo">
      <span class="cross">✝</span>
      <h1>SMDTBA</h1>
      <p>Buat Password Baru</p>
    </div>

    <?php if ($success): ?>
    <div style="background:rgba(60,179,113,.1);border:1px solid rgba(60,179,113,.3);color:var(--success);border-radius:var(--radius-sm);padding:16px;font-size:13.5px;text-align:center;line-height:1.7;margin-bottom:20px">
      <div style="font-size:28px;margin-bottom:8px">✅</div>
      <strong>Password berhasil diubah!</strong><br>
      Silakan login dengan password baru Anda.
    </div>
    <a href="/admin/index.php" class="btn btn-primary" style="width:100%;justify-content:center;text-decoration:none">
      Masuk Sekarang
    </a>

    <?php elseif ($error && !$user): ?>
    <div class="login-error"><?= e($error) ?></div>
    <div style="text-align:center;margin-top:16px">
      <a href="/admin/forgot-password.php" class="btn btn-secondary" style="text-decoration:none">
        Minta Link Reset Baru
      </a>
    </div>

    <?php else: ?>
    <?php if ($user): ?>
    <p style="font-size:13px;color:var(--text-secondary);text-align:center;margin-bottom:20px">
      Halo <strong style="color:var(--text-primary)"><?= e($user['nama'] ?: $user['username']) ?></strong>,
      buat password baru untuk akun Anda.
    </p>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="login-error"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="POST" autocomplete="off">
      <input type="hidden" name="token" value="<?= e($token) ?>">
      <div class="form-group">
        <label>Password Baru <span style="color:var(--accent)">*</span></label>
        <div style="position:relative">
          <input type="password" name="password" id="pw1" class="form-control"
                 placeholder="Minimal 8 karakter" required autofocus
                 oninput="checkStrength(this.value)" style="padding-right:40px">
          <button type="button" class="pw-toggle" onclick="togglePw('pw1',this)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
        </div>
        <div class="pw-strength" id="pwBar"></div>
        <div id="pwText" style="font-size:11.5px;margin-top:3px;color:var(--text-muted)"></div>
      </div>
      <div class="form-group">
        <label>Konfirmasi Password <span style="color:var(--accent)">*</span></label>
        <div style="position:relative">
          <input type="password" name="confirm" id="pw2" class="form-control"
                 placeholder="Ulangi password baru" required
                 oninput="checkConfirm()" style="padding-right:40px">
          <button type="button" class="pw-toggle" onclick="togglePw('pw2',this)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
        </div>
        <div id="confirmText" style="font-size:11.5px;margin-top:3px"></div>
      </div>
      <button type="submit" class="btn btn-primary login-btn">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
        Simpan Password Baru
      </button>
    </form>
    <?php endif; ?>

    <div style="text-align:center;margin-top:18px">
      <a href="/admin/index.php" style="font-size:12.5px;color:var(--text-muted);text-decoration:none">
        ← Kembali ke Login
      </a>
    </div>
  </div>
</div>

<script>
function togglePw(id, btn) {
  const el = document.getElementById(id);
  const show = el.type === 'password';
  el.type = show ? 'text' : 'password';
  btn.style.color = show ? 'var(--accent)' : 'var(--text-muted)';
}
function checkStrength(val) {
  const bar  = document.getElementById('pwBar');
  const txt  = document.getElementById('pwText');
  if (!val) { bar.style.width='0%'; txt.textContent=''; return; }
  let score = 0;
  if (val.length >= 8)  score++;
  if (val.length >= 12) score++;
  if (/[A-Z]/.test(val)) score++;
  if (/[0-9]/.test(val)) score++;
  if (/[^A-Za-z0-9]/.test(val)) score++;
  const colors = ['#e05252','#e0a050','#d4b44a','#5cb85c','#3cb371'];
  const labels = ['Sangat Lemah','Lemah','Cukup','Kuat','Sangat Kuat'];
  bar.style.cssText = `height:3px;border-radius:2px;margin-top:5px;background:${colors[score-1] || '#e05252'};width:${(score/5)*100}%;transition:all .3s`;
  txt.style.color   = colors[score-1] || '#e05252';
  txt.textContent   = labels[score-1] || labels[0];
}
function checkConfirm() {
  const p1  = document.getElementById('pw1').value;
  const p2  = document.getElementById('pw2').value;
  const txt = document.getElementById('confirmText');
  if (!p2) { txt.textContent=''; return; }
  if (p1 === p2) { txt.textContent='✓ Password cocok'; txt.style.color='var(--success)'; }
  else           { txt.textContent='✗ Password tidak cocok'; txt.style.color='var(--danger)'; }
}
</script>
</body>
</html>