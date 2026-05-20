<?php
/**
 * admin/users.php — Manajemen User (Supabase Edition)
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
adminBoot();
$user = requireSuperadmin();

$userList = [];
try {
    $um       = new UserManager(getDB());
    $userList = $um->getAll();
} catch (Throwable $e) {
    error_log('[admin/users.php] ' . $e->getMessage());
}

$pages = PAGE_LABELS;

// Aksi dasar — tanpa 'view' (otomatis) dan tanpa 'publish' (hanya artikel)
$actions = array_filter(PAGE_ACTION_LABELS, fn($k) => !in_array($k, ['view','publish']), ARRAY_FILTER_USE_KEY);

adminHeader('Manajemen User', 'users', $user);
?>

<style>
/* Responsive switch: tabel vs card */
#tableWrapDesktop { display: block; }
#cardListMobile   { display: none;  }

@media (max-width: 768px) {
  #tableWrapDesktop { display: none  !important; }
  #cardListMobile   { display: flex  !important; }
}

/* ── Mobile card list (≤768px) ──────────────────────────────────── */
@media (max-width: 768px) {
  /* Sembunyikan tabel, tampilkan card */
  /* Page header compact */
  .page-header { flex-wrap: wrap; gap: 8px; }
  .page-header h1 { font-size: 18px; }
}
@media (min-width: 769px) {
  }

/* ── User card mobile ────────────────────────────────────────────── */
.user-mob-card {
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 14px 16px;
  display: flex; align-items: center; gap: 12px;
  transition: border-color .15s;
}
.user-mob-card:active { background: var(--bg-card2); }
.user-mob-avatar {
  width: 40px; height: 40px; border-radius: 50%;
  background: var(--accent-dim); color: var(--accent);
  display: flex; align-items: center; justify-content: center;
  font-size: 15px; font-weight: 700; flex-shrink: 0;
  border: 2px solid rgba(201,168,76,.2); overflow: hidden;
}
.user-mob-avatar img { width: 100%; height: 100%; object-fit: cover; }
.user-mob-info { flex: 1; min-width: 0; }
.user-mob-name {
  font-size: 14px; font-weight: 600; color: var(--text-primary);
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.user-mob-sub {
  font-size: 11.5px; color: var(--text-muted); margin-top: 2px;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.user-mob-meta {
  display: flex; align-items: center; gap: 6px; margin-top: 5px;
  flex-wrap: wrap;
}
.user-mob-actions {
  display: flex; gap: 6px; flex-shrink: 0;
}
.user-mob-btn {
  display: inline-flex; align-items: center; justify-content: center;
  width: 34px; height: 34px; border-radius: 8px;
  border: 1px solid var(--border); background: var(--bg-card2);
  color: var(--text-secondary); cursor: pointer;
  transition: all .15s;
}
.user-mob-btn:hover { border-color: var(--accent); color: var(--accent); }
.user-mob-btn.del  { border-color: rgba(224,82,82,.3); color: var(--danger); }

/* Toolbar search di mobile */
@media (max-width: 768px) {
  .toolbar { flex-direction: column; gap: 8px; align-items: stretch; }
  .search-wrap { max-width: none; }
  .hide-mobile { display: none !important; }
}
</style>

<div class="page-header">
  <div class="page-header-left">
    <h1>Manajemen User</h1>
    <p>Kelola akun admin dan permission akses per-halaman</p>
  </div>
  <button class="btn btn-primary" onclick="openAddModal()">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    Tambah User
  </button>
</div>

<div class="card">
  <div class="toolbar">
    <div class="toolbar-left">
      <div class="search-wrap">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" class="form-control" id="searchInput" placeholder="Cari username / nama...">
      </div>
    </div>
    <div class="toolbar-right">
      <span id="userCount" style="font-size:13px;color:var(--text-secondary)"><?= count($userList) ?> user</span>
    </div>
  </div>
  <div class="table-wrapper" id="tableWrapDesktop">
    <table class="data-table" id="dataTable">
      <thead>
        <tr>
          <th>User</th><th>Email</th><th>Role</th>
          <th class="hide-mobile">Permissions</th><th>Status</th><th class="hide-mobile">Dibuat</th>
          <th style="width:100px">Aksi</th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($userList)): ?>
        <tr><td colspan="7" style="text-align:center;color:var(--text-muted);padding:30px">
          Belum ada data user atau gagal memuat.
        </td></tr>
      <?php endif; ?>
      <?php foreach ($userList as $u):
        $isSelf   = $u['id'] === $user['id'];
        $permsMap = is_array($u['permissions']) ? $u['permissions'] : [];
        $permLabels = [];
        foreach ($permsMap as $pg => $acts) {
            $pgLabel     = $pages[$pg] ?? $pg;
            $actsDisplay = array_filter(is_array($acts) ? $acts : [], fn($a) => $a !== 'view');
            if (empty($actsDisplay)) {
                $permLabels[] = "<span class='badge badge-gray' style='font-size:10px'>" . e($pgLabel) . ": Lihat Saja</span>";
            } else {
                $actStr = implode(' ', array_map(fn($a) =>
                    "<span class='badge badge-" .
                    ($a==='delete'?'red':($a==='create'?'green':($a==='edit'?'blue':($a==='publish'?'gold':'gray')))) .
                    "' style='font-size:9px'>" . e(PAGE_ACTION_LABELS[$a] ?? $a) . "</span>",
                array_values($actsDisplay)));
                $permLabels[] = "<span style='font-size:11px;color:var(--text-secondary)'>" . e($pgLabel) . ":</span> $actStr";
            }
        }
      ?>
        <tr>
          <td>
            <div style="display:flex;align-items:center;gap:8px">
              <?php
              $profilFoto = '';
              foreach (['webp','jpg','png'] as $_ext) {
                  $_f = rtrim($_SERVER['DOCUMENT_ROOT'],'/') . '/img/admin/profil/profil-' . $u['id'] . '.' . $_ext;
                  if (file_exists($_f)) { $profilFoto = '/img/admin/profil/profil-' . $u['id'] . '.' . $_ext . '?v=' . filemtime($_f); break; }
              }
              ?>
              <div class="user-avatar" style="width:28px;height:28px;font-size:12px;<?= $profilFoto ? 'background:transparent;padding:0;overflow:hidden' : '' ?>">
                <?php if ($profilFoto): ?>
                  <img src="<?= e($profilFoto) ?>" style="width:100%;height:100%;object-fit:cover;border-radius:50%;display:block" alt="">
                <?php else: ?>
                  <?= strtoupper(substr($u['username'] ?? 'U', 0, 1)) ?>
                <?php endif; ?>
              </div>
              <div>
                <div style="font-weight:500"><?= e($u['username'] ?? '') ?></div>
                <?php if (!empty($u['nama'])): ?><div style="font-size:11.5px;color:var(--text-secondary)"><?= e($u['nama']) ?></div><?php endif; ?>
                <?php if ($isSelf): ?><div style="font-size:10px;color:var(--accent)">← Anda</div><?php endif; ?>
              </div>
            </div>
          </td>
          <td style="font-size:13px;color:var(--text-secondary)"><?= e($u['email'] ?? '') ?></td>
          <td><span class="badge <?= ($u['role']??'') === ROLE_SUPERADMIN ? 'badge-gold' : 'badge-blue' ?>"><?= ($u['role']??'') === ROLE_SUPERADMIN ? 'Super Admin' : 'Admin' ?></span></td>
          <td class="hide-mobile">
            <?php if (($u['role']??'') === ROLE_SUPERADMIN): ?>
              <span class="badge badge-gold" style="font-size:10px">✓ Semua Akses</span>
            <?php elseif (empty($permLabels)): ?>
              <span style="color:var(--text-muted);font-size:12px">—</span>
            <?php else: ?>
              <div style="display:flex;flex-wrap:wrap;gap:4px;align-items:center;max-width:320px"><?= implode(' &nbsp; ', $permLabels) ?></div>
            <?php endif; ?>
          </td>
          <td><span class="badge <?= ($u['is_active']??'1') === '0' ? 'badge-red' : 'badge-green' ?>"><?= ($u['is_active']??'1') === '0' ? 'Nonaktif' : 'Aktif' ?></span></td>
          <td style="font-size:12px;color:var(--text-secondary);white-space:nowrap"><?= e(substr($u['created_at']??'',0,10)) ?></td>
          <td>
            <div class="actions">
              <button class="btn btn-icon btn-sm"
                      onclick="openEditModal(<?= htmlspecialchars(json_encode(['id'=>$u['id'],'username'=>$u['username'],'email'=>$u['email']??'','role'=>$u['role'],'permissions'=>$u['permissions']??[],'is_active'=>$u['is_active']??'1','nama'=>$u['nama']??'']), ENT_QUOTES) ?>)"
                      title="Edit">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
              </button>
              <?php if (!$isSelf): ?>
              <button class="btn btn-icon btn-sm" onclick="deleteUser('<?= e($u['id']) ?>','<?= e($u['username']??'') ?>')" title="Hapus" style="color:var(--danger);border-color:rgba(224,82,82,0.3)">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a1 1 0 011-1h4a1 1 0 011 1v2"/></svg>
              </button>
              <?php endif; ?>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div><!-- /#tableWrapDesktop -->

  <!-- ── Mobile card list ── -->
  <div id="cardListMobile" style="flex-direction:column;gap:10px;padding:12px">
    <?php if (empty($userList)): ?>
    <div style="text-align:center;padding:30px;color:var(--text-muted);font-size:13px">Belum ada user.</div>
    <?php endif; ?>
    <?php foreach ($userList as $u):
      $isSelf  = $u['id'] === $user['id'];
      $mobFoto = '';
      foreach (['webp','jpg','png'] as $_ext) {
          $_f = rtrim($_SERVER['DOCUMENT_ROOT'],'/') . '/img/admin/profil/profil-' . $u['id'] . '.' . $_ext;
          if (file_exists($_f)) { $mobFoto = '/img/admin/profil/profil-' . $u['id'] . '.' . $_ext; break; }
      }
    ?>
    <div class="user-mob-card">
      <div class="user-mob-avatar">
        <?php if ($mobFoto): ?>
        <img src="<?= e($mobFoto) ?>" alt="">
        <?php else: ?>
        <?= strtoupper(substr($u['username']??'U',0,1)) ?>
        <?php endif; ?>
      </div>
      <div class="user-mob-info">
        <div class="user-mob-name">
          <?= e($u['username']??'') ?>
          <?php if ($isSelf): ?><span style="font-size:10px;color:var(--accent);margin-left:4px">← Anda</span><?php endif; ?>
        </div>
        <div class="user-mob-sub"><?= e($u['nama'] ?: ($u['email'] ?? '—')) ?></div>
        <div class="user-mob-meta">
          <span class="badge <?= ($u['role']??'') === ROLE_SUPERADMIN ? 'badge-gold' : 'badge-blue' ?>" style="font-size:10px">
            <?= ($u['role']??'') === ROLE_SUPERADMIN ? 'Super Admin' : 'Admin' ?>
          </span>
          <span class="badge <?= ($u['is_active']??'1') === '0' ? 'badge-red' : 'badge-green' ?>" style="font-size:10px">
            <?= ($u['is_active']??'1') === '0' ? 'Nonaktif' : 'Aktif' ?>
          </span>
        </div>
      </div>
      <div class="user-mob-actions">
        <button class="user-mob-btn"
                onclick="openEditModal(<?= htmlspecialchars(json_encode(['id'=>$u['id'],'username'=>$u['username'],'email'=>$u['email']??'','role'=>$u['role'],'permissions'=>$u['permissions']??[],'is_active'=>$u['is_active']??'1','nama'=>$u['nama']??'']), ENT_QUOTES) ?>)"
                title="Edit">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
        </button>
        <?php if (!$isSelf): ?>
        <button class="user-mob-btn del"
                onclick="deleteUser('<?= e($u['id']) ?>','<?= e($u['username']??'') ?>')"
                title="Hapus">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a1 1 0 011-1h4a1 1 0 011 1v2"/></svg>
        </button>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div><!-- /#cardListMobile -->

</div><!-- /.card -->

<!-- ── MODAL ── -->
<div class="modal-overlay" id="formModal">
  <div class="modal modal-lg">
    <div class="modal-header">
      <span class="modal-title" id="modalTitle">Tambah User</span>
      <button class="modal-close">&times;</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="fieldId">
      <div class="form-grid">
        <div class="form-group">
          <label>Username <span class="required">*</span></label>
          <input type="text" class="form-control" id="fieldUsername" placeholder="username_admin" autocomplete="off">
        </div>
        <div class="form-group">
          <label>Email</label>
          <input type="email" class="form-control" id="fieldEmail" placeholder="email@paroki.org">
        </div>
        <div class="form-group full">
          <label>Nama Tampilan</label>
          <input type="text" class="form-control" id="fieldNama" placeholder="Nama lengkap (tampil di artikel)">
          <small style="color:var(--text-muted);font-size:11px;margin-top:4px;display:block">Tampil sebagai keterangan penulis artikel. Jika kosong, username akan digunakan.</small>
        </div>
        <div class="form-group">
          <label>Password <span class="required" id="pwRequired">*</span></label>
          <input type="password" class="form-control" id="fieldPassword" placeholder="Min. 8 karakter" autocomplete="new-password">
          <small id="pwHint" style="color:var(--text-muted);font-size:11px;display:none">Kosongkan jika tidak ingin mengganti password</small>
        </div>
        <div class="form-group">
          <label>Role <span class="required">*</span></label>
          <select class="form-select" id="fieldRole" onchange="onRoleChange()">
            <option value="admin">Admin</option>
            <option value="superadmin">Super Admin</option>
          </select>
        </div>
        <div class="form-group" id="statusGroup" style="display:none">
          <label>Status Akun</label>
          <select class="form-select" id="fieldIsActive">
            <option value="1">Aktif</option>
            <option value="0">Nonaktif</option>
          </select>
        </div>
      </div>

      <!-- Permission section -->
      <div id="permSection" style="margin-top:20px">
        <div style="font-size:13px;font-weight:600;color:var(--text-primary);margin-bottom:4px">Pengaturan Akses Per-Halaman</div>
        <div style="font-size:12px;color:var(--text-secondary);margin-bottom:6px">Centang halaman yang bisa diakses, lalu pilih aksi yang diizinkan.</div>
        <div style="font-size:11.5px;color:var(--text-muted);margin-bottom:14px;background:rgba(201,168,76,.06);border:1px solid rgba(201,168,76,.18);border-radius:6px;padding:7px 11px;line-height:1.6">
          ℹ <strong style="color:var(--accent)">Lihat Saja</strong> sudah otomatis aktif untuk setiap halaman yang dicentang.
        </div>

        <div id="permGrid">
        <?php
        // Susun halaman per section
        $permSections = [
            'Data Paroki' => ['galeri','petugas','wilayah','asisten_imam','dpp_bgkp','agenda','umkm','media','kategorial'],
            'Artikel'     => ['berita','kronik','historia'],
            'Sistem'      => ['master'],
        ];
        $allPermPages = [];
        foreach ($permSections as $secLabel => $secPages) {
            foreach ($secPages as $pg) {
                $allPermPages[$pg] = $pages[$pg] ?? $pg;
            }
        }
        $currentSection = '';
        foreach ($allPermPages as $pg => $pgLabel):
            // Cari section untuk halaman ini
            foreach ($permSections as $secLabel => $secPages) {
                if (in_array($pg, $secPages)) { $currentSection = $secLabel; break; }
            }
            // Ambil aksi dari PAGE_AVAILABLE_ACTIONS, buang 'view'
            $availActions = array_values(array_filter(
                PAGE_AVAILABLE_ACTIONS[$pg] ?? PAGE_ACTIONS,
                fn($a) => $a !== 'view'
            ));
            $isArtikel  = in_array($pg, ['berita','kronik','historia']);
            $isKategori = ($pg === 'kategorial');

            // Section header (render sekali per section)
            static $lastSection = '';
            if ($currentSection !== $lastSection):
                $lastSection = $currentSection;
        ?>
          <div style="font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;
                      color:<?= $currentSection==='Artikel' ? 'var(--info)' : ($currentSection==='Sistem'?'var(--warning)':'var(--text-muted)') ?>;
                      margin:<?= $lastSection==='' ? '0' : '18px' ?> 0 8px">
            <?= e($currentSection) ?>
            <?php if ($currentSection === 'Artikel'): ?>
            <span style="font-size:9.5px;color:var(--text-muted);text-transform:none;letter-spacing:0;font-weight:400;margin-left:6px">
              — opsi "Publish" hanya tersedia di sini
            </span>
            <?php endif; ?>
          </div>
        <?php endif; ?>

          <div class="perm-page-row" style="background:var(--bg-card2);border:1px solid var(--border);border-radius:var(--radius-sm);padding:14px 16px;margin-bottom:8px">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px">
              <input type="checkbox" id="page_<?= $pg ?>" value="<?= $pg ?>"
                     style="width:16px;height:16px;accent-color:var(--accent);cursor:pointer"
                     onchange="onPageCheck('<?= $pg ?>')">
              <label for="page_<?= $pg ?>" style="font-size:13.5px;font-weight:500;cursor:pointer;color:var(--text-primary)">
                <?= e($pgLabel) ?>
                <?php if ($isArtikel): ?>
                <span class="badge badge-blue" style="font-size:9px;margin-left:4px">Artikel</span>
                <?php elseif ($pg === 'umkm'): ?>
                <span class="badge badge-gold" style="font-size:9px;margin-left:4px">+ Publish</span>
                <?php elseif ($isKategori): ?>
                <span class="badge badge-gray" style="font-size:9px;margin-left:4px">Kategorial</span>
                <?php endif; ?>
              </label>
            </div>
            <div id="actions_<?= $pg ?>" style="display:none;padding-left:26px">
              <div style="font-size:11px;color:var(--text-muted);margin-bottom:8px;text-transform:uppercase;letter-spacing:0.08em">Izinkan aksi:</div>
              <div style="display:flex;flex-wrap:wrap;gap:8px">
                <?php foreach ($availActions as $act):
                  $actLabel = PAGE_ACTION_LABELS[$act] ?? $act; ?>
                <label style="display:flex;align-items:center;gap:6px;cursor:pointer;padding:6px 12px;border-radius:20px;border:1px solid var(--border);font-size:12.5px;transition:all 0.15s;color:var(--text-secondary)"
                       class="action-label" id="lbl_<?= $pg ?>_<?= $act ?>">
                  <input type="checkbox" id="act_<?= $pg ?>_<?= $act ?>" value="<?= $act ?>"
                         data-page="<?= $pg ?>" style="accent-color:var(--accent);cursor:pointer"
                         onchange="onActionCheck('<?= $pg ?>','<?= $act ?>')">
                  <?= e($actLabel) ?>
                </label>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
        </div><!-- /#permGrid -->
      </div><!-- /#permSection -->
    </div><!-- /.modal-body -->
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('formModal')">Batal</button>
      <button class="btn btn-primary" id="btnSave" onclick="submitForm()">Simpan</button>
    </div>
  </div>
</div>

<script>
const PAGES         = <?= json_encode(array_keys($allPermPages)) ?>;
const PAGE_AVAIL_JS = <?= json_encode(
    array_map(
        fn($pg) => array_values(array_filter(PAGE_AVAILABLE_ACTIONS[$pg] ?? PAGE_ACTIONS, fn($a) => $a !== 'view')),
        array_combine(array_keys($allPermPages), array_keys($allPermPages))
    )
) ?>;

function onRoleChange() {
  const isSuperadmin = document.getElementById('fieldRole').value === 'superadmin';
  document.getElementById('permSection').style.display = isSuperadmin ? 'none' : 'block';
}

function onPageCheck(pg) {
  const checked = document.getElementById('page_' + pg).checked;
  const actDiv  = document.getElementById('actions_' + pg);
  actDiv.style.display = checked ? 'block' : 'none';
  if (!checked) {
    document.querySelectorAll(`input[data-page="${pg}"]`).forEach(cb => cb.checked = false);
    updateActionLabels(pg);
  }
}

function onActionCheck(pg, act) { updateActionLabels(pg); }

function updateActionLabels(pg) {
  document.querySelectorAll(`input[data-page="${pg}"]`).forEach(cb => {
    const lbl = document.getElementById('lbl_' + pg + '_' + cb.value);
    if (!lbl) return;
    const colors = { create:'#3cb371', edit:'var(--info)', delete:'var(--danger)', publish:'var(--accent)' };
    const bgs    = { create:'rgba(60,179,113,.1)', edit:'rgba(82,148,224,.1)', delete:'rgba(224,82,82,.1)', publish:'rgba(201,168,76,.1)' };
    if (cb.checked) {
      lbl.style.borderColor = colors[cb.value] || 'var(--accent)';
      lbl.style.background  = bgs[cb.value]    || 'rgba(201,168,76,.1)';
      lbl.style.color       = colors[cb.value] || 'var(--accent)';
    } else {
      lbl.style.borderColor = 'var(--border)';
      lbl.style.background  = '';
      lbl.style.color       = 'var(--text-secondary)';
    }
  });
}

function resetPermissions() {
  PAGES.forEach(pg => {
    const pageCb = document.getElementById('page_' + pg);
    if (pageCb) pageCb.checked = false;
    const actDiv = document.getElementById('actions_' + pg);
    if (actDiv) actDiv.style.display = 'none';
    document.querySelectorAll(`input[data-page="${pg}"]`).forEach(cb => { cb.checked = false; });
    updateActionLabels(pg);
  });
}

function loadPermissions(perms) {
  resetPermissions();
  if (!perms || typeof perms !== 'object') return;
  const entries = Array.isArray(perms)
    ? perms.map(pg => [pg, ['create','edit','delete']])
    : Object.entries(perms);
  entries.forEach(([pg, acts]) => {
    const pageCb = document.getElementById('page_' + pg); if (!pageCb) return;
    pageCb.checked = true;
    const actDiv = document.getElementById('actions_' + pg);
    if (actDiv) actDiv.style.display = 'block';
    (Array.isArray(acts) ? acts : []).forEach(act => {
      const cb = document.getElementById('act_' + pg + '_' + act); if (cb) cb.checked = true;
    });
    updateActionLabels(pg);
  });
}

function collectPermissions() {
  const perms = {};
  PAGES.forEach(pg => {
    const pageCb = document.getElementById('page_' + pg);
    if (!pageCb || !pageCb.checked) return;
    const acts = [];
    document.querySelectorAll(`input[data-page="${pg}"]:checked`).forEach(cb => acts.push(cb.value));
    perms[pg] = acts;
  });
  return perms;
}

function openAddModal() {
  document.getElementById('modalTitle').textContent = 'Tambah User';
  ['fieldId','fieldUsername','fieldEmail','fieldNama','fieldPassword'].forEach(id => document.getElementById(id).value = '');
  document.getElementById('fieldRole').value     = 'admin';
  document.getElementById('fieldIsActive').value = '1';
  document.getElementById('pwRequired').style.display  = 'inline';
  document.getElementById('pwHint').style.display      = 'none';
  document.getElementById('statusGroup').style.display = 'none';
  document.getElementById('permSection').style.display = 'block';
  resetPermissions();
  openModal('formModal');
}

function openEditModal(u) {
  document.getElementById('modalTitle').textContent  = 'Edit User';
  document.getElementById('fieldId').value       = u.id;
  document.getElementById('fieldUsername').value = u.username;
  document.getElementById('fieldEmail').value    = u.email    || '';
  document.getElementById('fieldNama').value     = u.nama     || '';
  document.getElementById('fieldPassword').value = '';
  document.getElementById('fieldRole').value     = u.role;
  document.getElementById('fieldIsActive').value = u.is_active || '1';
  document.getElementById('pwRequired').style.display  = 'none';
  document.getElementById('pwHint').style.display      = 'inline';
  document.getElementById('statusGroup').style.display = 'flex';
  document.getElementById('permSection').style.display = u.role === 'superadmin' ? 'none' : 'block';
  loadPermissions(u.permissions || {});
  openModal('formModal');
}

async function submitForm() {
  const btn      = document.getElementById('btnSave');
  const id       = document.getElementById('fieldId').value.trim();
  const username = document.getElementById('fieldUsername').value.trim();
  const password = document.getElementById('fieldPassword').value;
  const role     = document.getElementById('fieldRole').value;
  const isNew    = !id;

  if (!username) { toast('Error', 'Username wajib diisi', 'error'); return; }
  if (isNew && password.length < 8) { toast('Error', 'Password minimal 8 karakter', 'error'); return; }
  if (!isNew && password && password.length < 8) { toast('Error', 'Password minimal 8 karakter', 'error'); return; }

  const payload = {
    action: isNew ? 'create' : 'update',
    ...(id && { id }),
    username,
    email      : document.getElementById('fieldEmail').value.trim(),
    nama       : document.getElementById('fieldNama').value.trim(),
    role,
    permissions: role === 'superadmin' ? {} : collectPermissions(),
    is_active  : document.getElementById('fieldIsActive').value,
    ...(password && { password }),
  };

  btnLoading(btn, true);
  const res = await apiPost('/admin/api/users.php', payload);
  btnLoading(btn, false);

  if (res.success) {
    toast('Berhasil', isNew ? 'User berhasil ditambahkan' : 'User diperbarui', 'success');
    closeModal('formModal');
    setTimeout(() => location.reload(), 900);
  } else {
    toast('Error', res.error || 'Gagal menyimpan', 'error');
  }
}

async function deleteUser(id, username) {
  confirmDialog('Hapus User?', `Akun "${username}" akan dihapus permanen.`, async () => {
    const res = await apiPost('/admin/api/users.php', { action: 'delete', id });
    if (res.success) {
      toast('Berhasil', 'User dihapus', 'success');
      setTimeout(() => location.reload(), 900);
    } else {
      toast('Error', res.error || 'Gagal menghapus', 'error');
    }
  });
}

document.addEventListener('DOMContentLoaded', function() {
  initSearch('searchInput', 'dataTable');
  document.getElementById('searchInput').addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('.user-mob-card').forEach(card => {
      card.style.display = card.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
  });
});
</script>

<?php adminFooter(); ?>