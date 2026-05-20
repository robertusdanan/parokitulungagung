<?php
/**
 * admin/register.php — Supabase Edition
 * Registrasi Mandiri Kontributor (Penulis Artikel + UMKM Umat)
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
$success = '';

$DEFAULT_PERMISSIONS = [
    'berita'   => ['create', 'edit', 'delete'],
    'kronik'   => ['create', 'edit', 'delete'],
    'historia' => ['create', 'edit', 'delete'],
    'umkm'     => ['create', 'edit', 'delete'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama     = trim($_POST['nama']     ?? '');
    $username = trim($_POST['username'] ?? '');
    $email    = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password']      ?? '';
    $confirm  = $_POST['confirm']       ?? '';

    if (!$nama || !$username || !$email || !$password) {
        $error = 'Semua field wajib diisi.';
    } elseif (mb_strlen($nama) > 60) {
        $error = 'Nama tampilan maksimal 60 karakter.';
    } elseif (strlen($username) < 3 || strlen($username) > 64) {
        $error = 'Username minimal 3 dan maksimal 64 karakter.';
    } elseif (preg_match('/\s/', $username)) {
        $error = 'Username tidak boleh mengandung spasi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif (strlen($password) < 8) {
        $error = 'Password minimal 8 karakter.';
    } elseif ($password !== $confirm) {
        $error = 'Konfirmasi password tidak cocok.';
    } else {
        try {
            $db = new SupabaseClient();
            $um = new UserManager($db);

            if ($um->findByUsername($username)) {
                $error = "Username \"{$username}\" sudah digunakan. Pilih username lain.";
            } elseif ($um->findByEmail($email)) {
                $error = "Email \"{$email}\" sudah terdaftar. Gunakan email lain atau login.";
            } else {
                $newUser = $um->create([
                    'username'    => $username,
                    'email'       => $email,
                    'password'    => $password,
                    'role'        => ROLE_ADMIN,
                    'permissions' => $DEFAULT_PERMISSIONS,
                    'nama'        => $nama,
                ], 'self-register');

                $logger = new ActivityLogger($db);
                $logger->log(
                    ['id' => $newUser['id'], 'username' => $username],
                    'CREATE', 'register',
                    'Registrasi mandiri: ' . $nama . ' (' . $username . ')'
                );

                $success = $username;
            }
        } catch (Throwable $e) {
            error_log('[register.php] ' . $e->getMessage());
            $error = 'Gagal mendaftar: ' . $e->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Daftar Akun — <?= ADMIN_TITLE ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/admin/css/admin.css">
  <style>
    .register-page{min-height:100vh;background:var(--bg-main);display:flex;align-items:center;justify-content:center;padding:24px 20px;position:relative;overflow:hidden}
    .register-bg{position:absolute;inset:0;background:radial-gradient(ellipse 60% 40% at 20% 50%,rgba(201,168,76,.06) 0%,transparent 70%),radial-gradient(ellipse 40% 60% at 80% 30%,rgba(82,148,224,.04) 0%,transparent 70%);pointer-events:none}
    .register-box{background:var(--bg-card);border:1px solid var(--border);border-radius:14px;padding:40px;width:100%;max-width:460px;position:relative;z-index:1;box-shadow:var(--shadow)}
    .register-logo{text-align:center;margin-bottom:28px}
    .register-logo .cross{font-size:32px;color:var(--accent);display:block;margin-bottom:6px}
    .register-logo h1{font-family:'Playfair Display',serif;font-size:20px;font-weight:700;color:var(--text-primary);margin-bottom:4px}
    .register-logo p{font-size:13px;color:var(--text-secondary)}
    .role-info{background:rgba(201,168,76,.07);border:1px solid rgba(201,168,76,.2);border-radius:var(--radius-sm);padding:12px 14px;margin-bottom:20px;font-size:12.5px;color:var(--text-secondary);line-height:1.7}
    .role-info strong{color:var(--accent)}
    .role-info ul{margin:6px 0 0 16px}
    .role-info li{margin-bottom:2px}
    .pw-strength{height:3px;border-radius:2px;margin-top:5px;background:var(--border);transition:all .3s}
    .pw-strength-text{font-size:11.5px;margin-top:3px}
    .login-error{background:rgba(224,82,82,.12);border:1px solid rgba(224,82,82,.3);color:var(--danger);border-radius:var(--radius-sm);padding:10px 14px;font-size:13px;margin-bottom:16px}
    .login-success{background:rgba(60,179,113,.12);border:1px solid rgba(60,179,113,.3);color:var(--success);border-radius:var(--radius-sm);padding:16px;font-size:13.5px;margin-bottom:16px;text-align:center;line-height:1.7}
    .pw-toggle{position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-muted);cursor:pointer;padding:4px}
    .form-group{margin-bottom:14px}
    .form-group label{display:block;font-size:12.5px;font-weight:500;color:var(--text-secondary);margin-bottom:6px}
    .register-btn{width:100%;margin-top:6px;justify-content:center;padding:11px;font-size:14.5px}
    .login-link{text-align:center;margin-top:16px;font-size:13px;color:var(--text-muted)}
    .login-link a{color:var(--accent);text-decoration:none}
    .login-link a:hover{text-decoration:underline}
    .char-count{font-size:11px;color:var(--text-muted);margin-top:3px;text-align:right}
  </style>
</head>
<body>
<div class="register-page">
  <div class="register-bg"></div>
  <div class="register-box">
    <div class="register-logo">
      <span class="cross">✝</span>
      <h1>Daftar Akun</h1>
      <p>Paroki SMDTBA Tulungagung</p>
    </div>

    <?php if ($success): ?>
    <div class="login-success">
      <div style="font-size:28px;margin-bottom:8px">🎉</div>
      <strong>Pendaftaran Berhasil!</strong><br>
      Akun <strong><?= htmlspecialchars($success) ?></strong> berhasil dibuat.<br>
      <small style="color:var(--text-muted)">Silakan login untuk mulai menulis artikel.</small>
    </div>
    <a href="/admin/index.php" class="btn btn-primary register-btn">Masuk Sekarang →</a>

    <?php else: ?>

    <div class="role-info">
      <strong>Akses yang akan diberikan:</strong>
      <ul>
        <li>Menulis &amp; edit artikel di <strong>Berita, Kronik, Historia</strong> milik sendiri (status draft)</li>
        <li>Tambah &amp; kelola <strong>promosi UMKM milik sendiri</strong> (status draft)</li>
        <li>Publish artikel &amp; UMKM hanya oleh <strong>Editor/Superadmin</strong></li>
      </ul>
    </div>

    <?php if ($error): ?>
    <div class="login-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" autocomplete="off">
      <div class="form-group">
        <label>Nama Tampilan <span style="color:var(--accent)">*</span></label>
        <input type="text" name="nama" class="form-control"
               value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>"
               placeholder="Nama lengkap Anda" maxlength="60" required autofocus
               oninput="document.getElementById('namaCount').textContent=this.value.length">
        <div class="char-count"><span id="namaCount"><?= strlen($_POST['nama'] ?? '') ?></span>/60 · Tampil sebagai penulis artikel &amp; pemilik promosi UMKM</div>
      </div>
      <div class="form-group">
        <label>Username <span style="color:var(--accent)">*</span></label>
        <input type="text" name="username" class="form-control"
               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
               placeholder="Untuk login (tidak tampil di artikel)"
               maxlength="64" required>
        <div style="font-size:11px;color:var(--text-muted);margin-top:3px">Tidak boleh mengandung spasi</div>
      </div>
      <div class="form-group">
        <label>Email <span style="color:var(--accent)">*</span></label>
        <input type="email" name="email" class="form-control"
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
               placeholder="email@contoh.com" required autocomplete="email">
        <div style="font-size:11px;color:var(--text-muted);margin-top:3px">Digunakan untuk reset password jika lupa</div>
      </div>
      <div class="form-group">
        <label>Password <span style="color:var(--accent)">*</span></label>
        <div style="position:relative">
          <input type="password" name="password" id="pw1" class="form-control"
                 placeholder="Minimal 8 karakter" required
                 oninput="checkPwStrength(this.value)" style="padding-right:40px">
          <button type="button" class="pw-toggle" onclick="togglePw('pw1',this)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
        </div>
        <div class="pw-strength" id="pwBar"></div>
        <div class="pw-strength-text" id="pwText"></div>
      </div>
      <div class="form-group">
        <label>Konfirmasi Password <span style="color:var(--accent)">*</span></label>
        <div style="position:relative">
          <input type="password" name="confirm" id="pw2" class="form-control"
                 placeholder="Ulangi password" oninput="checkConfirm()"
                 style="padding-right:40px" required>
          <button type="button" class="pw-toggle" onclick="togglePw('pw2',this)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
        </div>
        <div id="confirmText" style="font-size:11.5px;margin-top:3px"></div>
      </div>
      <button type="submit" class="btn btn-primary register-btn">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M16 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
        Daftar
      </button>
    </form>

    <?php endif; ?>

    <div class="login-link">
      Sudah punya akun? <a href="/admin">Masuk di sini</a>
    </div>
  </div>
</div>
<script>
function checkPwStrength(val) {
  const bar=document.getElementById('pwBar'),text=document.getElementById('pwText');
  if(!val){bar.style.width='0';text.textContent='';return;}
  let s=0;
  if(val.length>=8)s++;if(val.length>=12)s++;
  if(/[A-Z]/.test(val))s++;if(/[0-9]/.test(val))s++;if(/[^A-Za-z0-9]/.test(val))s++;
  const l=[{w:'20%',c:'#e05252',t:'Sangat lemah'},{w:'40%',c:'#e05252',t:'Lemah'},{w:'60%',c:'#e09a52',t:'Cukup'},{w:'80%',c:'#5294e0',t:'Kuat'},{w:'100%',c:'#3cb371',t:'Sangat kuat'}];
  const x=l[Math.min(s,4)];
  bar.style.cssText=`height:3px;border-radius:2px;background:${x.c};width:${x.w};transition:all .3s`;
  text.style.color=x.c;text.textContent=x.t;
}
function checkConfirm(){
  const v1=document.getElementById('pw1').value,v2=document.getElementById('pw2').value;
  const t=document.getElementById('confirmText');
  if(!v2){t.textContent='';return;}
  if(v1===v2){t.style.color='#3cb371';t.textContent='✓ Password cocok';}
  else{t.style.color='#e05252';t.textContent='✗ Tidak cocok';}
}
function togglePw(id,btn){
  const el=document.getElementById(id);
  el.type=el.type==='password'?'text':'password';
  btn.style.color=el.type==='text'?'#c9a84c':'';
}
</script>
</body>
</html>