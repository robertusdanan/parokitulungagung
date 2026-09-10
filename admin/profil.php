<?php
/**
 * admin/profil.php — Pengaturan Profil Sendiri
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
adminBoot();
$user = requireLogin();

// Ambil data user segar dari Supabase agar data profil & sosmed selalu up-to-date
$um = new UserManager(getDB());
$fresh = $um->findById($user['id']);
if ($fresh) {
    $user = array_merge($user, $fresh);
    $_SESSION['admin_user'] = $user;
}
$authorSlug = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower(strip_tags($user['nama'] ?: $user['username']))), '-');

// Path foto profil user ini dari R2 CDN
$fotoPath = !empty($user['id']) ? adminFotoUrl($user['id']) : '';

adminHeader('Pengaturan Profil', 'profil', $user);
?>

<style>
/* ── Profil Page Styles ──────────────────────────────────────────────── */
.profil-layout {
  display: grid;
  grid-template-columns: 280px 1fr;
  gap: 24px;
  align-items: start;
}

/* ── Avatar Card ──────────────────────────────────────────────────────── */
.avatar-card {
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 28px 20px;
  text-align: center;
  position: sticky;
  top: 80px;
}

.avatar-wrap {
  position: relative;
  display: inline-block;
  margin-bottom: 16px;
}

.avatar-img {
  width: 110px;
  height: 110px;
  border-radius: 50%;
  object-fit: cover;
  border: 3px solid var(--accent);
  display: block;
}

.avatar-initials {
  width: 110px;
  height: 110px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--accent), #a07010);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 40px;
  font-weight: 700;
  color: #111;
  font-family: 'Playfair Display', serif;
  border: 3px solid var(--accent);
  margin: 0 auto;
}

.avatar-upload-btn {
  position: absolute;
  bottom: 4px;
  right: 4px;
  width: 30px;
  height: 30px;
  border-radius: 50%;
  background: var(--accent);
  color: #111;
  border: 2px solid var(--bg-card);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all .2s;
  font-size: 14px;
}
.avatar-upload-btn:hover { background: var(--accent-hover); transform: scale(1.1); }

.avatar-name {
  font-family: 'Playfair Display', serif;
  font-size: 18px;
  font-weight: 600;
  color: var(--text-primary);
  margin-bottom: 4px;
}
.avatar-role {
  font-size: 12px;
  color: var(--text-muted);
  text-transform: uppercase;
  letter-spacing: .08em;
  margin-bottom: 16px;
}
.avatar-email {
  font-size: 13px;
  color: var(--text-secondary);
  word-break: break-all;
}

/* Upload progress */
.foto-progress-wrap {
  margin-top: 10px;
  display: none;
}
.foto-progress-bar-bg {
  height: 3px;
  background: var(--border);
  border-radius: 2px;
  overflow: hidden;
}
.foto-progress-bar {
  height: 100%;
  background: var(--accent);
  width: 0%;
  transition: width .3s;
  border-radius: 2px;
}
.foto-status {
  font-size: 11.5px;
  color: var(--text-muted);
  margin-top: 5px;
}

/* ── Form Cards ───────────────────────────────────────────────────────── */
.profil-section {
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  overflow: hidden;
  margin-bottom: 20px;
}
.profil-section-header {
  padding: 16px 22px;
  border-bottom: 1px solid var(--border);
  display: flex;
  align-items: center;
  gap: 10px;
}
.profil-section-icon {
  width: 32px;
  height: 32px;
  border-radius: var(--radius-sm);
  background: var(--accent-dim);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.profil-section-icon svg { width: 16px; height: 16px; color: var(--accent); stroke: currentColor; fill: none; stroke-width: 1.8; }
.profil-section-title {
  font-family: 'Playfair Display', serif;
  font-size: 15px;
  font-weight: 600;
  color: var(--text-primary);
}
.profil-section-sub {
  font-size: 12px;
  color: var(--text-muted);
  margin-top: 1px;
}
.profil-section-body {
  padding: 22px;
}

/* Password strength */
.pw-strength {
  height: 3px;
  border-radius: 2px;
  margin-top: 6px;
  background: var(--border);
  transition: all .3s;
}
.pw-strength-text {
  font-size: 11.5px;
  margin-top: 4px;
  transition: color .3s;
}

/* Warning box */
.warn-box {
  background: rgba(224,154,82,.08);
  border: 1px solid rgba(224,154,82,.25);
  border-radius: var(--radius-sm);
  padding: 10px 14px;
  font-size: 12.5px;
  color: var(--warning);
  line-height: 1.6;
  margin-bottom: 16px;
  display: flex;
  gap: 8px;
  align-items: flex-start;
}
.warn-box svg { flex-shrink: 0; margin-top: 1px; }

@media (max-width: 768px) {
  .profil-layout { grid-template-columns: 1fr; }
  .avatar-card { position: static; }
}
</style>

<div class="page-header">
  <div class="page-header-left">
    <h1>Pengaturan Profil</h1>
    <p>Kelola informasi akun dan foto profil Anda</p>
  </div>
</div>

<div class="profil-layout">

  <!-- ── Kolom kiri: Avatar ─────────────────────────────────────────── -->
  <div class="avatar-card">
    <div class="avatar-wrap">
      <?php if ($fotoPath): ?>
        <img src="<?= e($fotoPath) ?>" class="avatar-img" id="avatarImg" alt="Foto Profil">
      <?php else: ?>
        <div class="avatar-initials" id="avatarInitials">
          <?= strtoupper(substr($user['username'], 0, 1)) ?>
        </div>
        <img src="" class="avatar-img" id="avatarImg" alt="" style="display:none">
      <?php endif; ?>

      <button class="avatar-upload-btn" onclick="document.getElementById('fotoInput').click()"
              title="Ganti foto profil">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="14" height="14">
          <path d="M23 19a2 2 0 01-2 2H3a2 2 0 01-2-2V8a2 2 0 012-2h4l2-3h6l2 3h4a2 2 0 012 2z"/>
          <circle cx="12" cy="13" r="4"/>
        </svg>
      </button>
    </div>
    <input type="file" id="fotoInput" accept="image/jpeg,image/png,image/webp"
           style="display:none" onchange="uploadFoto(this)">

    <div class="avatar-name" id="avatarName"><?= e($user['nama'] ?? $user['username']) ?></div>
    <div class="avatar-role">
      <?= $user['role'] === ROLE_SUPERADMIN ? 'Super Admin' : 'Admin' ?>
    </div>
    <div class="avatar-email" id="avatarEmail"><?= e($user['email'] ?? '—') ?></div>
    <div id="avatarJabatan" style="font-size:12px;color:var(--accent);margin-top:6px;font-weight:600;<?= empty($user['jabatan']) ? 'display:none;' : '' ?>">
      <?= e($user['jabatan'] ?? '') ?>
    </div>

    <div style="margin-top:14px">
      <a href="/penulis/<?= e($authorSlug) ?>" target="_blank"
         style="display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:6px 14px;font-size:11.5px;color:var(--accent);background:var(--accent-dim);border:1px solid var(--accent);border-radius:20px;text-decoration:none;font-weight:600;transition:all .15s"
         title="Buka profil penulis publik di tab baru">
        <span>Profil Publik Saya</span>
        <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
      </a>
    </div>

    <div class="foto-progress-wrap" id="fotoProgressWrap">
      <div class="foto-progress-bar-bg">
        <div class="foto-progress-bar" id="fotoProgressBar"></div>
      </div>
      <div class="foto-status" id="fotoStatus">Mengupload...</div>
    </div>

    <div style="margin-top:16px;padding-top:16px;border-top:1px solid var(--border);font-size:11.5px;color:var(--text-muted);line-height:1.7;text-align:left">
      <strong style="color:var(--text-secondary);display:block;margin-bottom:4px">Foto Profil</strong>
      • Format JPG, PNG, atau WebP<br>
      • Maks 20MB · Dikompres otomatis<br>
      • Dipotong jadi kotak 200×200px<br>
      • Foto lama otomatis tergantikan
    </div>
    <?php if ($fotoPath): ?>
    <button type="button" onclick="hapusFoto()"
      style="margin-top:10px;font-size:12px;color:var(--danger);background:none;border:1px solid rgba(220,80,80,.3);border-radius:6px;padding:5px 14px;cursor:pointer;width:100%;transition:all .15s;display:flex;align-items:center;justify-content:center;gap:6px"
      onmouseover="this.style.background='rgba(220,80,80,.08)'" onmouseout="this.style.background='none'">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/></svg>
      Hapus Foto Profil
    </button>
    <?php endif; ?>
  </div>

  <!-- ── Kolom kanan: Form ──────────────────────────────────────────── -->
  <div>

    <!-- Informasi Akun -->
    <div class="profil-section">
      <div class="profil-section-header">
        <div class="profil-section-icon">
          <svg viewBox="0 0 24 24"><circle cx="12" cy="7" r="4"/><path d="M5.5 21a7 7 0 0113 0"/></svg>
        </div>
        <div>
          <div class="profil-section-title">Informasi Akun</div>
          <div class="profil-section-sub">Username dan alamat email</div>
        </div>
      </div>
      <div class="profil-section-body">
        <div class="warn-box">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15">
            <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
          </svg>
          Username digunakan untuk login dan tidak tampil di artikel. Nama Tampilan yang muncul sebagai penulis artikel.
        </div>

        <div class="form-grid">
          <div class="form-group full">
            <label>Nama Tampilan <span class="required">*</span></label>
            <input type="text" class="form-control" id="fieldNama"
                   value="<?= e($user['nama'] ?? '') ?>"
                   placeholder="Nama lengkap Anda"
                   autocomplete="name">
            <small style="color:var(--text-muted);font-size:11px">
              Nama ini yang akan tampil sebagai <strong>keterangan penulis</strong> di artikel. Maks 60 karakter.
            </small>
          </div>
          <div class="form-group">
            <label>Username <span class="required">*</span></label>
            <input type="text" class="form-control" id="fieldUsername"
                   value="<?= e($user['username']) ?>"
                   placeholder="username_anda"
                   autocomplete="username">
            <small style="color:var(--text-muted);font-size:11px">Digunakan untuk login. Tidak boleh mengandung spasi. Maks 64 karakter.</small>
          </div>
          <div class="form-group">
            <label>Email</label>
            <input type="email" class="form-control" id="fieldEmail"
                   value="<?= e($user['email'] ?? '') ?>"
                   placeholder="email@paroki.org"
                   autocomplete="email">
          </div>
        </div>
      </div>
    </div>

    <!-- Biodata & Peran Penulis -->
    <div class="profil-section">
      <div class="profil-section-header">
        <div class="profil-section-icon">
          <svg viewBox="0 0 24 24"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
        </div>
        <div>
          <div class="profil-section-title">Biodata & Peran Penulis</div>
          <div class="profil-section-sub">Informasi publik untuk profil penulis dan E-E-A-T artikel</div>
        </div>
      </div>
      <div class="profil-section-body">
        <div class="form-grid">
          <div class="form-group full">
            <label>Jabatan / Peran di Paroki</label>
            <input type="text" class="form-control" id="fieldJabatan"
                   value="<?= e($user['jabatan'] ?? '') ?>"
                   placeholder="Contoh: Koordinator Komsos / Tim Redaksi / Seksi Liturgi Lingkungan">
            <small style="color:var(--text-muted);font-size:11px">
              Tampil di bawah nama Anda pada kartu penulis dan halaman profil publik.
            </small>
          </div>
          <div class="form-group full">
            <label>Bio / Biografi Singkat</label>
            <textarea class="form-control" id="fieldBio" rows="4" maxlength="1000"
                      placeholder="Tuliskan biografi singkat, latar belakang pelayanan, atau dedikasi Anda di Paroki Tulungagung..."><?= e($user['bio'] ?? '') ?></textarea>
            <div style="display:flex;justify-content:space-between;margin-top:4px">
              <small style="color:var(--text-muted);font-size:11px">
                Ditampilkan di halaman profil penulis <code>/penulis/<?= e($authorSlug) ?></code> dan direktori kontributor.
              </small>
              <small style="color:var(--text-muted);font-size:11px">Maks 1000 karakter</small>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Media Sosial & Tautan Publik -->
    <div class="profil-section">
      <div class="profil-section-header">
        <div class="profil-section-icon">
          <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1 4-10z"/></svg>
        </div>
        <div>
          <div class="profil-section-title">Media Sosial & Tautan</div>
          <div class="profil-section-sub">Tautan resmi untuk memperkuat identitas penulis di mesin pencari (Schema.org)</div>
        </div>
      </div>
      <div class="profil-section-body">
        <div class="form-grid">
          <div class="form-group">
            <label>Instagram</label>
            <input type="text" class="form-control" id="fieldInstagram"
                   value="<?= e($user['instagram'] ?? '') ?>"
                   placeholder="@username atau link profil">
            <small style="color:var(--text-muted);font-size:11px">Contoh: @komsosparokitulungagung</small>
          </div>
          <div class="form-group">
            <label>Facebook</label>
            <input type="text" class="form-control" id="fieldFacebook"
                   value="<?= e($user['facebook'] ?? '') ?>"
                   placeholder="Nama profil atau URL facebook">
            <small style="color:var(--text-muted);font-size:11px">Contoh: SantaMariaDTBA</small>
          </div>
          <div class="form-group">
            <label>X / Twitter</label>
            <input type="text" class="form-control" id="fieldTwitter"
                   value="<?= e($user['twitter'] ?? '') ?>"
                   placeholder="@username atau link twitter">
          </div>
          <div class="form-group">
            <label>Website Pribadi / Portofolio</label>
            <input type="url" class="form-control" id="fieldWebsite"
                   value="<?= e($user['website'] ?? '') ?>"
                   placeholder="https://website-anda.com">
          </div>
        </div>

        <div style="margin-top:20px;padding-top:16px;border-top:1px solid var(--border);display:flex;justify-content:flex-end">
          <button class="btn btn-primary" id="btnSaveInfo" onclick="saveInfo()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
            Simpan Semua Data Profil
          </button>
        </div>
      </div>
    </div>

    <!-- Ganti Password -->
    <div class="profil-section">
      <div class="profil-section-header">
        <div class="profil-section-icon">
          <svg viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
        </div>
        <div>
          <div class="profil-section-title">Ganti Password</div>
          <div class="profil-section-sub">Kosongkan jika tidak ingin mengganti password</div>
        </div>
      </div>
      <div class="profil-section-body">
        <div class="form-grid">
          <div class="form-group full">
            <label>Password Lama <span class="required">*</span></label>
            <div style="position:relative">
              <input type="password" class="form-control" id="fieldOldPw"
                     placeholder="Masukkan password saat ini"
                     autocomplete="current-password">
              <button type="button" onclick="togglePw('fieldOldPw', this)"
                style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-muted);cursor:pointer;padding:4px">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              </button>
            </div>
          </div>
          <div class="form-group">
            <label>Password Baru</label>
            <div style="position:relative">
              <input type="password" class="form-control" id="fieldNewPw"
                     placeholder="Minimal 8 karakter"
                     autocomplete="new-password"
                     oninput="checkStrength(this.value)">
              <button type="button" onclick="togglePw('fieldNewPw', this)"
                style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-muted);cursor:pointer;padding:4px">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              </button>
            </div>
            <div class="pw-strength" id="pwStrengthBar"></div>
            <div class="pw-strength-text" id="pwStrengthText"></div>
          </div>
          <div class="form-group">
            <label>Konfirmasi Password Baru</label>
            <div style="position:relative">
              <input type="password" class="form-control" id="fieldNewPw2"
                     placeholder="Ulangi password baru"
                     autocomplete="new-password"
                     oninput="checkConfirm()">
              <button type="button" onclick="togglePw('fieldNewPw2', this)"
                style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-muted);cursor:pointer;padding:4px">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              </button>
            </div>
            <div id="pwMatchText" style="font-size:11.5px;margin-top:4px"></div>
          </div>
        </div>

        <div style="margin-top:16px;display:flex;justify-content:flex-end">
          <button class="btn btn-primary" id="btnSavePw" onclick="savePassword()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
            Ganti Password
          </button>
        </div>
      </div>
    </div>

    <!-- Info session aktif -->
    <div class="profil-section" style="border-color:rgba(255,255,255,.04)">
      <div class="profil-section-body" style="padding:14px 22px">
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px">
          <div style="font-size:12.5px;color:var(--text-muted)">
            Session aktif selama <strong style="color:var(--text-secondary)">8 jam</strong> sejak login terakhir.
          </div>
          <a href="/admin/logout.php" class="btn btn-secondary btn-sm">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
            Logout
          </a>
        </div>
      </div>
    </div>

  </div>
</div>

<script>
// ── Upload Foto ────────────────────────────────────────────────────────
async function uploadFoto(input) {
  const file = input.files[0]; if (!file) return;

  const wrap = document.getElementById('fotoProgressWrap');
  const bar  = document.getElementById('fotoProgressBar');
  const stat = document.getElementById('fotoStatus');

  wrap.style.display = 'block';
  bar.style.width    = '30%';
  stat.textContent   = 'Mengupload foto...';

  const fd = new FormData();
  fd.append('foto',   file);
  fd.append('action', 'upload_foto');

  try {
    bar.style.width = '70%';
    const res  = await fetch('/admin/api/profil.php', { method:'POST', body: fd });
    const data = await res.json();
    bar.style.width = '100%';

    setTimeout(() => { wrap.style.display = 'none'; bar.style.width = '0%'; }, 600);

    if (data.success) {
      // Update tampilan avatar langsung
      const img      = document.getElementById('avatarImg');
      const initials = document.getElementById('avatarInitials');
      img.src           = data.path + '?t=' + Date.now();
      img.style.display = 'block';
      if (initials) initials.style.display = 'none';

      stat.textContent = `✓ ${data.size_kb}kb [${data.format}]`;
      toast('Foto Berhasil', `Foto profil diperbarui (${data.size_kb}kb)`, 'success');

      // Update avatar di sidebar juga (ganti dengan foto)
      updateSidebarAvatar(data.path);
    } else {
      stat.textContent = '✗ ' + data.error;
      toast('Gagal', data.error, 'error');
    }
  } catch(e) {
    wrap.style.display = 'none';
    toast('Error', 'Gagal menghubungi server', 'error');
  }
}

function updateSidebarAvatar(path) {
  // Ganti inisial di sidebar dengan foto kecil
  const sidebarAvatar = document.querySelector('.sidebar-footer .user-avatar');
  if (!sidebarAvatar) return;
  sidebarAvatar.style.background = 'transparent';
  sidebarAvatar.style.padding    = '0';
  sidebarAvatar.innerHTML        =
    `<img src="${escHtml(path)}?t=${Date.now()}" style="width:100%;height:100%;object-fit:cover;border-radius:50%" alt="">`;
}

// ── Save Informasi (username + email + bio + jabatan + sosmed) ─────────
async function saveInfo() {
  const btn       = document.getElementById('btnSaveInfo');
  const username  = document.getElementById('fieldUsername').value.trim();
  const email     = document.getElementById('fieldEmail').value.trim();
  const nama      = document.getElementById('fieldNama').value.trim();
  const bio       = document.getElementById('fieldBio') ? document.getElementById('fieldBio').value.trim() : '';
  const jabatan   = document.getElementById('fieldJabatan') ? document.getElementById('fieldJabatan').value.trim() : '';
  const instagram = document.getElementById('fieldInstagram') ? document.getElementById('fieldInstagram').value.trim() : '';
  const facebook  = document.getElementById('fieldFacebook') ? document.getElementById('fieldFacebook').value.trim() : '';
  const twitter   = document.getElementById('fieldTwitter') ? document.getElementById('fieldTwitter').value.trim() : '';
  const website   = document.getElementById('fieldWebsite') ? document.getElementById('fieldWebsite').value.trim() : '';

  if (!username) { toast('Error', 'Username tidak boleh kosong', 'error'); return; }
  if (!nama)     { toast('Error', 'Nama tampilan tidak boleh kosong', 'error'); return; }

  btnLoading(btn, true);
  const res = await apiPost('/admin/api/profil.php', {
    action: 'update',
    username, nama, email,
    bio, jabatan, instagram, facebook, twitter, website,
    old_password: '', new_password: ''
  });
  btnLoading(btn, false);

  if (res.success) {
    // Update tampilan di halaman
    const displayName = res.nama || res.username;
    document.getElementById('avatarName').textContent  = displayName;
    document.getElementById('avatarEmail').textContent = res.email || '—';
    const avJabatan = document.getElementById('avatarJabatan');
    if (avJabatan) {
      if (res.jabatan) {
        avJabatan.textContent = res.jabatan;
        avJabatan.style.display = 'block';
      } else {
        avJabatan.style.display = 'none';
      }
    }
    // Update topbar username
    const topbarUser = document.querySelector('.topbar-user');
    if (topbarUser) topbarUser.textContent = res.username;
    // Update sidebar: tampilkan nama (display name)
    const sidebarName = document.querySelector('.sidebar-footer .user-name');
    if (sidebarName) sidebarName.textContent = displayName;

    toast('Berhasil', 'Semua informasi profil berhasil disimpan', 'success');

    // Tampilkan warning jika username diganti
    if (res.warnings && res.warnings.length) {
      res.warnings.forEach(w => toast('Perhatian', w, 'warning', 6000));
    }
  } else {
    toast('Error', res.error, 'error');
  }
}

// ── Save Password ──────────────────────────────────────────────────────
async function savePassword() {
  const btn    = document.getElementById('btnSavePw');
  const oldPw  = document.getElementById('fieldOldPw').value;
  const newPw  = document.getElementById('fieldNewPw').value;
  const newPw2 = document.getElementById('fieldNewPw2').value;

  if (!oldPw)  { toast('Error', 'Masukkan password lama terlebih dahulu', 'error'); return; }
  if (!newPw)  { toast('Error', 'Masukkan password baru', 'error'); return; }
  if (newPw.length < 8) { toast('Error', 'Password baru minimal 8 karakter', 'error'); return; }
  if (newPw !== newPw2) { toast('Error', 'Konfirmasi password tidak cocok', 'error'); return; }

  btnLoading(btn, true);
  const res = await apiPost('/admin/api/profil.php', {
    action:       'update',
    username:     document.getElementById('fieldUsername').value.trim(),
    nama:         document.getElementById('fieldNama').value.trim(),
    email:        document.getElementById('fieldEmail').value.trim(),
    old_password: oldPw,
    new_password: newPw,
  });
  btnLoading(btn, false);

  if (res.success) {
    document.getElementById('fieldOldPw').value  = '';
    document.getElementById('fieldNewPw').value  = '';
    document.getElementById('fieldNewPw2').value = '';
    document.getElementById('pwStrengthBar').style.width      = '0%';
    document.getElementById('pwStrengthText').textContent     = '';
    document.getElementById('pwMatchText').textContent        = '';
    toast('Berhasil', 'Password berhasil diganti', 'success');
  } else {
    toast('Error', res.error, 'error');
  }
}

// ── Password strength indicator ────────────────────────────────────────
function checkStrength(val) {
  const bar  = document.getElementById('pwStrengthBar');
  const text = document.getElementById('pwStrengthText');
  if (!val) { bar.style.width='0%'; text.textContent=''; return; }

  let score = 0;
  if (val.length >= 8)                    score++;
  if (val.length >= 12)                   score++;
  if (/[A-Z]/.test(val))                  score++;
  if (/[0-9]/.test(val))                  score++;
  if (/[^A-Za-z0-9]/.test(val))          score++;

  const levels = [
    { w:'20%', c:'var(--danger)',  t:'Sangat lemah' },
    { w:'40%', c:'var(--danger)',  t:'Lemah' },
    { w:'60%', c:'var(--warning)', t:'Cukup' },
    { w:'80%', c:'var(--info)',    t:'Kuat' },
    { w:'100%',c:'var(--success)', t:'Sangat kuat' },
  ];
  const lvl = levels[Math.min(score, 4)];
  bar.style.cssText  = `height:3px;border-radius:2px;background:${lvl.c};width:${lvl.w};transition:all .3s`;
  text.style.color   = lvl.c;
  text.textContent   = lvl.t;
}

function checkConfirm() {
  const pw1  = document.getElementById('fieldNewPw').value;
  const pw2  = document.getElementById('fieldNewPw2').value;
  const text = document.getElementById('pwMatchText');
  if (!pw2) { text.textContent = ''; return; }
  if (pw1 === pw2) {
    text.style.color   = 'var(--success)';
    text.textContent   = '✓ Password cocok';
  } else {
    text.style.color   = 'var(--danger)';
    text.textContent   = '✗ Password tidak cocok';
  }
}

// ── Toggle show/hide password ──────────────────────────────────────────
function togglePw(inputId, btn) {
  const input = document.getElementById(inputId);
  const isHidden = input.type === 'password';
  input.type = isHidden ? 'text' : 'password';
  btn.style.color = isHidden ? 'var(--accent)' : 'var(--text-muted)';
}

// ── Init: set foto profil di sidebar jika ada ──────────────────────────
document.addEventListener('DOMContentLoaded', function () {
  <?php if ($fotoPath): ?>
  // Set foto di sidebar langsung
  const p = "<?= e(strtok($fotoPath, '?')) ?>";
  updateSidebarAvatar(p);
  <?php endif; ?>
});
</script>

<?php adminFooter(); ?>
