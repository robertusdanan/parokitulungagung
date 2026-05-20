<?php
/**
 * admin/kategorial.php — Manajemen Profil Kelompok Kategorial
 * Fitur: Edit profil + CRUD data (tambah/edit/hapus baris di Supabase)
 */
require_once __DIR__ . '/../includes/functions.php';
adminBoot();
$user = requireLogin();

$isSA     = $user['role'] === ROLE_SUPERADMIN;
$permsMap = $isSA ? [] : getPermissionsMap($user);
$canEdit  = $isSA || in_array('edit',   $permsMap['kategorial'] ?? []);
$canView  = $isSA || in_array('view',   $permsMap['kategorial'] ?? []) || $canEdit;

$db    = getDB();
$TABLE = 'kelompok_profil';

$BUILT_IN_SLUGS = [
    'adorasi','pdkk','wanita-katolik','gim','legiomaria',
    'me','pk','rosariohidup','ktm','ssvmaria','ssvrosali'
];
$BUILT_IN_ICONS = [
    'adorasi'        => '/img/icon/kategorial/adorasi.png',
    'pdkk'           => '/img/icon/kategorial/pdkk.png',
    'wanita-katolik' => '/img/icon/kategorial/wanita-katolik.png',
    'gim'            => '/img/icon/kategorial/gim.png',
    'legiomaria'     => '/img/icon/kategorial/legiomaria.png',
    'me'             => '/img/icon/kategorial/me.png',
    'pk'             => '/img/icon/kategorial/pk.png',
    'rosariohidup'   => '/img/icon/kategorial/rosariohidup.png',
    'ktm'            => '/img/icon/kategorial/ktm.png',
    'ssvmaria'       => '/img/icon/kategorial/ssvmaria.png',
    'ssvrosali'      => '/img/icon/kategorial/ssvrosali.png',
];

// Ambil semua profil dari Supabase
$allProfil = [];
try {
    $rows = $db->readWhere($TABLE, [], 'slug.asc', '*');
    if (is_array($rows)) {
        foreach ($rows as $r) {
            if (is_array($r) && !empty($r['slug'])) {
                $allProfil[$r['slug']] = $r;
            }
        }
    }
} catch (Throwable $e) {
    error_log('[admin/kategorial.php] ' . $e->getMessage());
}

// Gabungkan slug built-in + dari DB
$allSlugs = array_unique(array_merge(
    $BUILT_IN_SLUGS,
    array_keys($allProfil)
));
sort($allSlugs);

$totalFilled = 0;
foreach ($allSlugs as $s) {
    $p = $allProfil[$s] ?? [];
    if (!empty($p['nama']) || !empty($p['deskripsi']) || !empty($p['info'])) $totalFilled++;
}
$totalNew = count(array_diff(array_keys($allProfil), $BUILT_IN_SLUGS));

adminHeader('Profil Kategorial', 'kategorial', $user);
?>
<style>
/* ── Grid card ──────────────────────────────────────────────────── */
.kat-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px,1fr));
  gap: 14px;
}
@media(max-width:600px){ .kat-grid{grid-template-columns:repeat(2,1fr);gap:10px} }

.kat-card {
  background:var(--bg-card); border:1px solid var(--border);
  border-radius:var(--radius); padding:18px 14px 14px;
  display:flex; flex-direction:column; align-items:center;
  gap:7px; cursor:pointer; position:relative; text-align:center;
  transition:border-color .18s,box-shadow .18s,transform .18s;
}
.kat-card:hover { border-color:var(--accent); box-shadow:0 4px 18px rgba(201,168,76,.14); transform:translateY(-2px); }
.kat-card-status { position:absolute;top:8px;left:8px;width:8px;height:8px;border-radius:50%; }
.kat-card-status.filled { background:#3cb371;box-shadow:0 0 0 2px rgba(60,179,113,.2); }
.kat-card-status.empty  { background:var(--border); }
.kat-card-icon { width:52px;height:52px;object-fit:contain;border-radius:50%;background:var(--bg-card2);padding:6px;border:1.5px solid var(--border); }
.kat-card-icon-ph { width:52px;height:52px;border-radius:50%;background:var(--accent-dim);color:var(--accent);display:flex;align-items:center;justify-content:center;font-size:20px;font-weight:700;font-family:'Cormorant Garamond',serif;border:1.5px solid var(--border); }
.kat-card-name { font-size:13px;font-weight:600;color:var(--text-primary);line-height:1.3; }
.kat-card-sub  { font-size:11px;color:var(--text-muted);line-height:1.4;max-height:34px;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical; }
.kat-card-badges { display:flex;gap:4px;flex-wrap:wrap;justify-content:center; }
/* overlay buttons top-right + bottom-right */
.kat-card-actions { position:absolute;top:6px;right:6px;display:flex;gap:4px;opacity:0;transition:opacity .15s; }
.kat-card:hover .kat-card-actions { opacity:1; }
.kat-card-btn { width:26px;height:26px;background:var(--bg-card2);border:1px solid var(--border);border-radius:6px;display:flex;align-items:center;justify-content:center;color:var(--text-muted);cursor:pointer;transition:all .15s; }
.kat-card-btn:hover { color:var(--accent);border-color:var(--accent); }
.kat-card-btn.del:hover { color:var(--danger);border-color:rgba(224,82,82,.4); }

/* ── Stats pill ──────────────────────────────────────────────────── */
.kat-stats { display:flex;gap:12px;flex-wrap:wrap;margin-bottom:16px; }
.kat-stat-pill { background:var(--bg-card2);border:1px solid var(--border);border-radius:20px;padding:5px 14px;font-size:12px;color:var(--text-secondary);display:flex;align-items:center;gap:6px; }
.kat-stat-pill b { color:var(--text-primary); }

/* ── Modal xl + tabs ─────────────────────────────────────────────── */
.modal-xl { max-width:780px;width:96vw; }
.modal-tabs { display:flex;gap:4px;border-bottom:1px solid var(--border);padding:0 4px;margin-bottom:16px;flex-wrap:wrap; }
.modal-tab-btn { padding:8px 14px;font-size:12.5px;font-weight:500;border:none;background:transparent;color:var(--text-secondary);cursor:pointer;border-bottom:2px solid transparent;margin-bottom:-1px;transition:all .15s;white-space:nowrap; }
.modal-tab-btn.active { color:var(--accent);border-bottom-color:var(--accent); }
.modal-tab-pane { display:none; }
.modal-tab-pane.active { display:block; }

/* ── Form helpers ────────────────────────────────────────────────── */
.form-two-col { display:grid;grid-template-columns:1fr 1fr;gap:0 16px; }
@media(max-width:560px){ .form-two-col{grid-template-columns:1fr} }
.full-col { grid-column:1/-1; }
.slug-chip { display:inline-flex;align-items:center;gap:5px;background:var(--accent-dim);color:var(--accent);font-size:11px;font-weight:600;padding:2px 9px;border-radius:20px;font-family:monospace; }

/* ── Pengurus builder ────────────────────────────────────────────── */
.pengurus-list { display:flex;flex-direction:column;gap:8px;margin-top:6px; }
.pengurus-row { display:grid;grid-template-columns:auto 1fr 1fr auto;gap:8px;align-items:center;background:var(--bg-card2);border-radius:8px;padding:8px 10px;border:1px solid var(--border); }
@media(max-width:600px){ .pengurus-row{grid-template-columns:auto 1fr auto;} .pengurus-row .pengurus-jabatan{grid-column:2;} }
/* Avatar pengurus */
.peng-avatar { width:38px;height:38px;border-radius:50%;background:var(--accent-dim);border:1.5px solid var(--border);object-fit:cover;cursor:pointer;transition:border-color .15s;flex-shrink:0;display:block; }
.peng-avatar:hover { border-color:var(--accent); }
.peng-avatar-wrap { position:relative;width:38px;height:38px;flex-shrink:0; }
.peng-avatar-wrap input[type=file] { display:none; }
.peng-avatar-ph { width:38px;height:38px;border-radius:50%;background:var(--accent-dim);border:1.5px dashed var(--border);display:flex;align-items:center;justify-content:center;cursor:pointer;transition:all .15s;color:var(--text-muted); }
.peng-avatar-ph:hover { border-color:var(--accent);color:var(--accent);background:var(--bg-card2); }
.peng-avatar-uploading { position:absolute;inset:0;border-radius:50%;background:rgba(0,0,0,.5);display:flex;align-items:center;justify-content:center; }
/* Popup menu foto pengurus */
.peng-photo-menu {
  position:absolute; top:44px; left:50%; transform:translateX(-50%);
  background:var(--bg-card); border:1px solid var(--border);
  border-radius:10px; box-shadow:0 6px 20px rgba(0,0,0,.25);
  z-index:200; min-width:160px; overflow:hidden;
  animation:pengMenuIn .15s ease;
}
@keyframes pengMenuIn { from{opacity:0;transform:translateX(-50%) translateY(-6px)} to{opacity:1;transform:translateX(-50%) translateY(0)} }
.peng-photo-menu-item {
  display:flex; align-items:center; gap:8px;
  padding:9px 13px; font-size:12.5px; color:var(--text-primary);
  cursor:pointer; transition:background .12s; white-space:nowrap;
}
.peng-photo-menu-item:hover { background:var(--bg-card2); }
.peng-photo-menu-item svg { flex-shrink:0; color:var(--text-muted); }
.peng-photo-menu-sep { height:1px; background:var(--border); margin:2px 0; }
.peng-photo-menu-item.danger { color:var(--danger); }
.peng-photo-menu-item.danger svg { color:var(--danger); }

/* ── Sosmed row ──────────────────────────────────────────────────── */
.sosmed-row { display:grid;grid-template-columns:130px 1fr 1fr;gap:8px;align-items:start; }
@media(max-width:500px){ .sosmed-row{grid-template-columns:1fr} }

/* ── Image picker ────────────────────────────────────────────────── */
.img-picker { border:2px dashed var(--border);border-radius:var(--radius);cursor:pointer;transition:border-color .18s,background .18s;overflow:hidden;position:relative; }
.img-picker:hover { border-color:var(--accent);background:var(--accent-dim); }
.img-picker-preview { width:100%;min-height:120px;display:flex;align-items:center;justify-content:center;position:relative; }
.img-picker-empty { display:flex;flex-direction:column;align-items:center;justify-content:center;padding:22px 16px;width:100%; }
.img-picker--icon { width:90px;flex-shrink:0;border-radius:50%; }
.img-picker-preview--icon { width:90px;height:90px;min-height:unset;border-radius:50%;overflow:hidden; }
.img-picker-empty--icon { padding:10px 6px; }
.img-picker-actions { padding:7px 10px;background:var(--bg-card2);border-top:1px solid var(--border);display:flex;align-items:center;gap:8px;flex-wrap:wrap; }
.img-picker-info { font-size:10.5px;color:var(--text-muted);margin-left:auto; }
.img-picker-uploading { position:absolute;inset:0;background:rgba(0,0,0,.55);display:flex;flex-direction:column;align-items:center;justify-content:center;gap:8px;z-index:10; }
.img-picker-uploading-text { color:#fff;font-size:12px;font-weight:600; }
.img-picker-progress { width:60%;height:4px;background:rgba(255,255,255,.3);border-radius:4px;overflow:hidden; }
.img-picker-progress-bar { height:100%;background:var(--accent);border-radius:4px;animation:uploadPulse 1.2s ease-in-out infinite; }
@keyframes uploadPulse{0%,100%{width:15%}50%{width:80%}}
.img-saved-badge { display:inline-flex;align-items:center;gap:4px;background:rgba(60,179,113,.12);color:#3cb371;border:1px solid rgba(60,179,113,.25);border-radius:20px;font-size:10px;font-weight:600;padding:2px 8px; }

/* ── Media browser modal ─────────────────────────────────────────── */
.media-modal-overlay { position:fixed;inset:0;z-index:1100;background:rgba(0,0,0,.65);backdrop-filter:blur(3px);display:none;align-items:center;justify-content:center;padding:16px; }
.media-modal-overlay.open { display:flex; }
.media-modal { background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);width:min(860px,96vw);max-height:90vh;display:flex;flex-direction:column;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,.4);animation:mediaIn .18s ease; }
@keyframes mediaIn{from{opacity:0;transform:scale(.96)}to{opacity:1;transform:scale(1)}}
.media-modal-header { padding:14px 18px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:10px;flex-shrink:0; }
.media-modal-title { font-size:14px;font-weight:600;color:var(--text-primary);flex:1; }
.media-modal-body { flex:1;overflow-y:auto;padding:16px; }
.media-modal-tabs { display:flex;gap:6px;margin-bottom:14px;flex-wrap:wrap; }
.media-modal-tab { padding:6px 14px;font-size:12px;font-weight:500;border:1px solid var(--border);border-radius:20px;background:var(--bg-card2);color:var(--text-secondary);cursor:pointer;transition:all .15s; }
.media-modal-tab.active { background:var(--accent-dim);color:var(--accent);border-color:var(--accent); }
.media-upload-zone { border:2px dashed var(--border);border-radius:var(--radius);padding:28px 16px;text-align:center;cursor:pointer;transition:border-color .15s,background .15s;margin-bottom:16px; }
.media-upload-zone:hover,.media-upload-zone.dragging { border-color:var(--accent);background:var(--accent-dim); }
.media-upload-queue { display:flex;flex-direction:column;gap:6px;margin-bottom:12px; }
.media-upload-item { display:flex;align-items:center;gap:10px;background:var(--bg-card2);border:1px solid var(--border);border-radius:8px;padding:8px 12px;font-size:12px; }
.media-upload-item-name { flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:var(--text-primary); }
.media-upload-item-bar { height:3px;background:var(--border);border-radius:3px;margin-top:5px;overflow:hidden; }
.media-upload-item-bar-fill { height:100%;background:var(--accent);border-radius:3px;transition:width .3s; }
.media-gallery { display:grid;grid-template-columns:repeat(auto-fill,minmax(110px,1fr));gap:8px; }
.media-thumb { border:2px solid var(--border);border-radius:8px;overflow:hidden;cursor:pointer;position:relative;aspect-ratio:1;background:var(--bg-card2);transition:border-color .15s,transform .15s; }
.media-thumb:hover { border-color:var(--accent);transform:scale(1.02); }
.media-thumb.selected { border-color:var(--accent);box-shadow:0 0 0 3px var(--accent-dim); }
.media-thumb img { width:100%;height:100%;object-fit:cover;display:block; }
.media-thumb-check { position:absolute;top:4px;right:4px;width:20px;height:20px;border-radius:50%;background:var(--accent);color:#fff;display:none;align-items:center;justify-content:center;font-size:11px;font-weight:700; }
.media-thumb.selected .media-thumb-check { display:flex; }
/* Thumb: aspect-ratio dikurangi agar ada ruang nama di bawah */
.media-thumb { aspect-ratio:unset !important; }
.media-thumb-img-wrap { width:100%;aspect-ratio:1;overflow:hidden;position:relative;background:var(--bg-card2); }
.media-thumb-img-wrap img { width:100%;height:100%;object-fit:cover;display:block;transition:transform .2s; }
.media-thumb:hover .media-thumb-img-wrap img { transform:scale(1.05); }
.media-thumb-check { position:absolute;top:5px;right:5px;width:20px;height:20px;border-radius:50%;background:var(--accent);color:#fff;display:none;align-items:center;justify-content:center;font-size:11px;font-weight:700;box-shadow:0 1px 4px rgba(0,0,0,.3); }
.media-thumb.selected .media-thumb-check { display:flex; }
.media-thumb-label {
  padding:5px 6px 6px;
  font-size:10px; line-height:1.3;
  color:var(--text-secondary);
  word-break:break-all;
  display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical;
  overflow:hidden;
}
.media-thumb.selected .media-thumb-label { color:var(--accent); font-weight:600; }
/* Search box di browse panel */
.media-search-wrap {
  position:relative; margin-bottom:12px;
}
.media-search-wrap svg {
  position:absolute; left:10px; top:50%; transform:translateY(-50%);
  color:var(--text-muted); pointer-events:none;
}
.media-search-input {
  width:100%; padding:8px 10px 8px 34px;
  background:var(--bg-card2); border:1px solid var(--border);
  border-radius:8px; font-size:13px; color:var(--text-primary);
  outline:none; transition:border-color .15s; box-sizing:border-box;
}
.media-search-input:focus { border-color:var(--accent); }
.media-count-label { font-size:11.5px; color:var(--text-muted); margin-bottom:8px; }
.media-modal-footer { padding:12px 18px;border-top:1px solid var(--border);display:flex;align-items:center;gap:10px;flex-shrink:0; }
.media-selected-preview { display:flex;align-items:center;gap:8px;flex:1;min-width:0; }
.media-selected-thumb { width:36px;height:36px;border-radius:6px;object-fit:cover;border:1px solid var(--border);flex-shrink:0; }
.media-selected-url { font-size:11px;color:var(--text-muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;font-family:monospace; }
.media-pagination { display:flex;align-items:center;justify-content:center;gap:6px;margin-top:14px;flex-wrap:wrap; }
.media-page-btn { width:30px;height:30px;border-radius:6px;border:1px solid var(--border);background:var(--bg-card2);color:var(--text-secondary);cursor:pointer;font-size:12px;display:flex;align-items:center;justify-content:center;transition:all .15s; }
.media-page-btn:hover,.media-page-btn.active { background:var(--accent-dim);border-color:var(--accent);color:var(--accent); }
@keyframes spin{to{transform:rotate(360deg)}}
.spin { animation:spin .8s linear infinite; }

/* ── Supabase data table ─────────────────────────────────────────── */
.supa-table-wrap { overflow-x:auto;margin-top:0; }
.supa-table { width:100%;border-collapse:collapse;font-size:13px; }
.supa-table th { padding:10px 12px;text-align:left;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);border-bottom:1px solid var(--border);white-space:nowrap;background:var(--bg-card2); }
.supa-table td { padding:10px 12px;border-bottom:1px solid var(--border);color:var(--text-primary);vertical-align:top; }
.supa-table tr:last-child td { border-bottom:none; }
.supa-table tr:hover td { background:var(--bg-card2); }
.supa-table td.muted { color:var(--text-muted);font-size:12px; }
.supa-table td.mono { font-family:monospace;font-size:12px;color:var(--accent); }
.supa-cell-img { width:40px;height:40px;object-fit:cover;border-radius:6px;border:1px solid var(--border); }
.supa-cell-img-ph { width:40px;height:40px;border-radius:6px;background:var(--accent-dim);display:flex;align-items:center;justify-content:center;font-size:16px;font-weight:700;color:var(--accent);font-family:'Cormorant Garamond',serif; }
.supa-truncate { max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis; }
.supa-actions { display:flex;gap:6px;white-space:nowrap; }

/* ── View switcher ───────────────────────────────────────────────── */
.view-switcher { display:flex;gap:4px;background:var(--bg-card2);border:1px solid var(--border);border-radius:8px;padding:3px; }
.view-btn { width:32px;height:32px;border:none;background:transparent;border-radius:6px;cursor:pointer;display:flex;align-items:center;justify-content:center;color:var(--text-muted);transition:all .15s; }
.view-btn.active { background:var(--bg-card);color:var(--accent);box-shadow:0 1px 4px rgba(0,0,0,.1); }
</style>

<!-- ── Page header ── -->
<div class="page-header">
  <div class="page-header-left">
    <h1>Profil Kategorial</h1>
    <p>Kelola profil dan data kelompok kategorial paroki</p>
  </div>
  <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
    <?php if ($canEdit): ?>
    <button class="btn btn-secondary" onclick="openAddModal()">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      Tambah Kelompok
    </button>
    <?php endif; ?>
    <!-- View switcher: Card | Tabel -->
    <div class="view-switcher" title="Ubah tampilan">
      <button class="view-btn active" id="btnViewCard" onclick="setView('card')" title="Tampilan card">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
      </button>
      <button class="view-btn" id="btnViewTable" onclick="setView('table')" title="Tampilan tabel Supabase">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
      </button>
    </div>
  </div>
</div>

<!-- ── Stats ── -->
<div class="kat-stats">
  <div class="kat-stat-pill">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
    Total: <b><?= count($allSlugs) ?></b>
  </div>
  <div class="kat-stat-pill">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><polyline points="20 6 9 17 4 12"/></svg>
    Terisi: <b><?= $totalFilled ?></b>
  </div>
  <?php if ($totalNew > 0): ?>
  <div class="kat-stat-pill">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    Ditambahkan: <b><?= $totalNew ?></b>
  </div>
  <?php endif; ?>
</div>

<!-- ════════════════════════════════════════════════════════════════
     VIEW: CARD
═════════════════════════════════════════════════════════════════ -->
<div id="viewCard" class="card" style="padding:20px">
  <div class="kat-grid">
    <?php foreach ($allSlugs as $slug):
      $p           = $allProfil[$slug] ?? [];
      $nama        = $p['nama'] ?? '';
      $namaDisplay = $nama ?: ucwords(str_replace(['-','_'],' ',$slug));
      $desc        = $p['deskripsi'] ?? $p['subtitle'] ?? '';
      $icon        = $p['icon'] ?? $BUILT_IN_ICONS[$slug] ?? '';
      $isNew       = !in_array($slug, $BUILT_IN_SLUGS);
      $isFilled    = !empty($p['nama']) || !empty($p['deskripsi']) || !empty($p['info']);
    ?>
    <div class="kat-card" onclick="<?= $canEdit ? "openEditModal('".e($slug)."')" : "window.open('/kategorial/".urlencode($slug)."','_blank')" ?>">
      <div class="kat-card-status <?= $isFilled?'filled':'empty' ?>"></div>
      <?php if ($canEdit): ?>
      <div class="kat-card-actions" onclick="event.stopPropagation()">
        <button class="kat-card-btn" onclick="openEditModal('<?= e($slug) ?>')" title="Edit profil">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
        </button>
        <?php if ($isSA): ?>
        <button class="kat-card-btn del" onclick="deleteKategorial('<?= e($slug) ?>','<?= e($namaDisplay) ?>')" title="Hapus data profil kelompok ini">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a1 1 0 011-1h4a1 1 0 011 1v2"/></svg>
        </button>
        <?php endif; ?>
      </div>
      <?php endif; ?>
      <?php if ($icon): ?>
        <img src="<?= e($icon) ?>" alt="" class="kat-card-icon" onerror="this.style.display='none';this.nextSibling.style.display='flex'">
        <div class="kat-card-icon-ph" style="display:none"><?= strtoupper(substr($namaDisplay,0,1)) ?></div>
      <?php else: ?>
        <div class="kat-card-icon-ph"><?= strtoupper(substr($namaDisplay,0,1)) ?></div>
      <?php endif; ?>
      <div class="kat-card-name"><?= e($namaDisplay) ?></div>
      <?php if ($desc): ?><div class="kat-card-sub"><?= e($desc) ?></div><?php endif; ?>
      <div class="kat-card-badges">
        <?php if ($isNew): ?><span class="badge badge-green" style="font-size:9px">Baru</span><?php endif; ?>
        <span class="badge <?= $isFilled?'badge-blue':'badge-gray' ?>" style="font-size:9px"><?= $isFilled?'Terisi':'Kosong' ?></span>
        <?php if (!empty($p['pengurus']) && $p['pengurus'] !== '[]'): ?>
          <span class="badge badge-gold" style="font-size:9px">Pengurus</span>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- ════════════════════════════════════════════════════════════════
     VIEW: TABEL SUPABASE
═════════════════════════════════════════════════════════════════ -->
<div id="viewTable" class="card" style="padding:0;display:none">
  <div style="padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:10px;flex-wrap:wrap">
    <div style="flex:1">
      <div style="font-size:13px;font-weight:600;color:var(--text-primary)">Data Supabase — kelompok_profil</div>
      <div style="font-size:11.5px;color:var(--text-muted);margin-top:2px"><?= count($allProfil) ?> baris tersimpan di database</div>
    </div>
    <?php if ($canEdit): ?>
    <button class="btn btn-primary btn-sm" onclick="openAddModal()">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      Tambah
    </button>
    <?php endif; ?>
  </div>
  <div class="supa-table-wrap">
    <table class="supa-table" id="supaTable">
      <thead>
        <tr>
          <th>Slug</th>
          <th>Icon</th>
          <th>Nama</th>
          <th>Deskripsi</th>
          <th>Info</th>
          <th>Sosmed</th>
          <th>Pengurus</th>
          <th>Update oleh</th>
          <th style="width:90px">Aksi</th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($allProfil)): ?>
        <tr><td colspan="9" style="text-align:center;padding:30px;color:var(--text-muted)">
          Belum ada data di Supabase. Built-in slugs belum pernah disimpan.
        </td></tr>
      <?php endif; ?>
      <?php foreach ($allProfil as $slug => $p):
        $nama       = $p['nama']     ?? '';
        $desc       = $p['deskripsi']?? '';
        $info       = $p['info']     ?? '';
        $icon       = $p['icon']     ?? $BUILT_IN_ICONS[$slug] ?? '';
        $sosmed     = trim(($p['handle_sosial'] ?? '') ?: ($p['tipe_sosial'] ?? ''));
        $pengurusRaw= $p['pengurus'] ?? '[]';
        $pengurusArr= json_decode($pengurusRaw, true) ?: [];
        $nDisplay   = $nama ?: ucwords(str_replace(['-','_'],' ',$slug));
        $isNew      = !in_array($slug, $BUILT_IN_SLUGS);
      ?>
        <tr>
          <td class="mono"><?= e($slug) ?>
            <?php if ($isNew): ?><br><span class="badge badge-green" style="font-size:9px">Baru</span><?php endif; ?>
          </td>
          <td>
            <?php if ($icon): ?>
              <img src="<?= e($icon) ?>" class="supa-cell-img" alt="" onerror="this.style.display='none'">
            <?php else: ?>
              <div class="supa-cell-img-ph"><?= strtoupper(substr($nDisplay,0,1)) ?></div>
            <?php endif; ?>
          </td>
          <td style="font-weight:500"><?= e($nDisplay) ?></td>
          <td class="muted supa-truncate"><?= e(mb_substr($desc,0,60)) ?><?= mb_strlen($desc)>60?'…':'' ?></td>
          <td class="muted"><?= $info ? '<span class="badge badge-blue" style="font-size:9px">Ada</span>' : '<span style="color:var(--text-muted);font-size:12px">—</span>' ?></td>
          <td class="muted"><?= $sosmed ? e($sosmed) : '—' ?></td>
          <td class="muted" style="text-align:center"><?= count($pengurusArr) ?: '—' ?></td>
          <td class="muted" style="font-size:11px"><?= e($p['updated_by'] ?? '—') ?></td>
          <td>
            <div class="supa-actions">
              <?php if ($canEdit): ?>
              <button class="btn btn-icon btn-sm" onclick="openEditModal('<?= e($slug) ?>')" title="Edit">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
              </button>
              <?php if ($isSA): ?>
              <button class="btn btn-icon btn-sm" onclick="deleteKategorial('<?= e($slug) ?>','<?= e($nDisplay) ?>')" title="Hapus data profil" style="color:var(--danger);border-color:rgba(224,82,82,.3)">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a1 1 0 011-1h4a1 1 0 011 1v2"/></svg>
              </button>
              <?php endif; ?>
              <?php else: ?>
              <a href="/kategorial/<?= urlencode($slug) ?>" target="_blank" class="btn btn-icon btn-sm" title="Lihat">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              </a>
              <?php endif; ?>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if (count($allProfil) < count($allSlugs)): ?>
  <div style="padding:12px 20px;border-top:1px solid var(--border);font-size:12px;color:var(--text-muted)">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13" style="vertical-align:middle;margin-right:4px"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
    <?= count($allSlugs) - count($allProfil) ?> kelompok built-in belum pernah disimpan (belum ada di tabel). Klik Edit untuk mengisi dan menyimpannya.
  </div>
  <?php endif; ?>
</div>

<!-- ════════════════════════════════════════════════════════════════
     MODAL EDIT / TAMBAH
═════════════════════════════════════════════════════════════════ -->
<div class="modal-overlay" id="katModal">
  <div class="modal modal-xl">
    <div class="modal-header">
      <span class="modal-title" id="katModalTitle">Edit Profil Kelompok</span>
      <button class="modal-close">&times;</button>
    </div>
    <div class="modal-body">
      <!-- Slug row -->
      <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;flex-wrap:wrap">
        <span style="font-size:12px;color:var(--text-muted)">Kelompok:</span>
        <span class="slug-chip" id="slugDisplay">—</span>
        <div id="newSlugFields" style="display:none;flex:1;min-width:200px">
          <input type="text" class="form-control" id="fieldSlugNew"
                 placeholder="cth: orang-muda-katolik"
                 style="font-family:monospace;font-size:12px"
                 oninput="sanitizeSlugInput(this)">
          <small style="color:var(--text-muted);font-size:10.5px;margin-top:2px;display:block">
            Huruf kecil, angka, tanda hubung (-). Tidak bisa diubah setelah disimpan.
          </small>
        </div>
        <input type="hidden" id="fieldSlug">
        <a id="previewLink" href="#" target="_blank" style="font-size:11px;color:var(--accent);display:none">Lihat di website →</a>
      </div>

      <!-- Tabs -->
      <div class="modal-tabs">
        <button class="modal-tab-btn active" onclick="switchTab('identitas',this)">Identitas</button>
        <button class="modal-tab-btn" onclick="switchTab('konten',this)">Info & Kegiatan</button>
        <button class="modal-tab-btn" onclick="switchTab('pengurus',this)">Pengurus</button>
        <button class="modal-tab-btn" onclick="switchTab('sosmed',this)">Sosial Media</button>
      </div>

      <!-- TAB: Identitas -->
      <div id="tab-identitas" class="modal-tab-pane active">
        <div class="form-two-col">
          <div class="form-group">
            <label>Nama Kelompok <span class="required">*</span></label>
            <input type="text" class="form-control" id="fieldNama" placeholder="cth: Adorasi Mahakudus">
          </div>
          <div class="form-group">
            <label>Subtitle / Tagline</label>
            <input type="text" class="form-control" id="fieldSubtitle" placeholder="cth: Memuliakan Tuhan">
          </div>
          <div class="form-group full-col">
            <label>Deskripsi Singkat</label>
            <textarea class="form-control" id="fieldDeskripsi" rows="2" placeholder="Deskripsi singkat (tampil di card & hero)"></textarea>
          </div>

          <!-- Banner -->
          <div class="form-group full-col">
            <label>Banner / Foto Header</label>
            <input type="hidden" id="fieldBanner">
            <div class="img-picker" id="pickerBanner" onclick="triggerPicker('banner')">
              <div class="img-picker-preview" id="previewBanner">
                <div class="img-picker-empty" id="emptyBanner">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="30" height="30" style="opacity:.35"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                  <div style="font-size:12px;color:var(--text-primary);margin-top:6px;font-weight:500">Belum ada banner</div>
                  <div style="display:flex;gap:8px;margin-top:10px;flex-wrap:wrap;justify-content:center">
                    <button type="button" onclick="event.stopPropagation();triggerPicker('banner')" class="btn btn-sm btn-primary" style="font-size:11.5px">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                      Upload Baru
                    </button>
                    <button type="button" onclick="event.stopPropagation();openMediaBrowser('banner')" class="btn btn-sm btn-secondary" style="font-size:11.5px">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                      Pilih dari Server
                    </button>
                  </div>
                  <div style="font-size:10px;color:var(--text-muted);margin-top:8px">JPG, PNG, WebP · Maks 10 MB · Auto-kompres ke WebP</div>
                </div>
                <img id="imgPreviewBanner" src="" alt="" style="display:none;width:100%;height:100%;object-fit:cover;border-radius:6px">
              </div>
              <div class="img-picker-actions" id="actionsBanner" style="display:none">
                <button type="button" class="btn btn-sm btn-secondary" onclick="event.stopPropagation();triggerPicker('banner')">Upload Baru</button>
                <button type="button" class="btn btn-sm btn-secondary" onclick="event.stopPropagation();openMediaBrowser('banner')">Pilih Lain</button>
                <button type="button" class="btn btn-sm" onclick="event.stopPropagation();clearImage('banner')" style="color:var(--danger);border-color:rgba(224,82,82,.3)">Hapus</button>
                <span class="img-picker-info" id="infoBanner"></span>
              </div>
            </div>
            <input type="file" id="fileBanner" accept="image/jpeg,image/png,image/gif,image/webp" style="display:none" onchange="handleFileSelect(this,'banner')">
            <small style="color:var(--text-muted);font-size:10.5px;margin-top:4px;display:block">Ideal: 1600×600 px. Otomatis dikompres ke WebP.</small>
          </div>

          <!-- Icon -->
          <div class="form-group full-col">
            <label>Icon / Logo Kelompok</label>
            <input type="hidden" id="fieldIcon">
            <div style="display:flex;align-items:flex-start;gap:14px;flex-wrap:wrap">
              <div class="img-picker img-picker--icon" id="pickerIcon" onclick="triggerPicker('icon')">
                <div class="img-picker-preview img-picker-preview--icon" id="previewIcon">
                  <div class="img-picker-empty img-picker-empty--icon" id="emptyIcon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="22" height="22" style="opacity:.35"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                    <div style="font-size:10px;color:var(--text-muted);margin-top:3px;text-align:center">Icon</div>
                  </div>
                  <img id="imgPreviewIcon" src="" alt="" style="display:none;width:100%;height:100%;object-fit:contain;padding:6px;border-radius:50%">
                </div>
              </div>
              <div style="flex:1;min-width:180px">
                <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:6px">
                  <button type="button" class="btn btn-sm btn-secondary" onclick="triggerPicker('icon')">Upload Baru</button>
                  <button type="button" class="btn btn-sm btn-secondary" onclick="openMediaBrowser('icon')">Pilih dari Server</button>
                  <button type="button" class="btn btn-sm" onclick="clearImage('icon')" id="btnClearIcon" style="display:none;color:var(--danger);border-color:rgba(224,82,82,.3)">Hapus</button>
                </div>
                <div class="img-picker-info" id="infoIcon" style="font-size:11px;color:var(--text-muted)"></div>
                <small style="color:var(--text-muted);font-size:10.5px;display:block;margin-top:4px">Dikompres ke 200×200 px WebP. Kosongkan untuk pakai icon bawaan.</small>
              </div>
            </div>
            <input type="file" id="fileIcon" accept="image/jpeg,image/png,image/gif,image/webp" style="display:none" onchange="handleFileSelect(this,'icon')">
          </div>
        </div>
      </div>

      <!-- TAB: Info & Kegiatan -->
      <div id="tab-konten" class="modal-tab-pane">
        <div class="form-group">
          <label>Informasi Umum</label>
          <textarea class="form-control" id="fieldInfo" rows="5" placeholder="Sejarah, latar belakang, visi/misi kelompok..."></textarea>
        </div>
        <div class="form-group" style="margin-top:12px">
          <label>Kegiatan Rutin</label>
          <textarea class="form-control" id="fieldKegiatan" rows="4" placeholder="Jadwal dan jenis kegiatan rutin..."></textarea>
        </div>
      </div>

      <!-- TAB: Pengurus -->
      <div id="tab-pengurus" class="modal-tab-pane">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;flex-wrap:wrap;gap:8px">
          <div style="font-size:12.5px;color:var(--text-secondary)">Pengurus ditampilkan di halaman detail kelompok.</div>
          <button class="btn btn-sm btn-secondary" onclick="addPengurusRow()" type="button">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Tambah Pengurus
          </button>
        </div>
        <div id="pengurusList" class="pengurus-list">
          <div style="text-align:center;padding:20px;color:var(--text-muted);font-size:12.5px" id="pengurusEmpty">Belum ada pengurus.</div>
        </div>
      </div>

      <!-- TAB: Sosial Media -->
      <div id="tab-sosmed" class="modal-tab-pane">
        <div style="font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.09em;color:var(--text-muted);margin-bottom:8px">Tautan Sosial Media</div>
        <div class="sosmed-row">
          <div class="form-group">
            <label>Platform</label>
            <select class="form-select" id="fieldTipeSosial">
              <option value="">— Pilih —</option>
              <option value="instagram">Instagram</option>
              <option value="whatsapp">WhatsApp</option>
              <option value="facebook">Facebook</option>
              <option value="youtube">YouTube</option>
              <option value="website">Website</option>
            </select>
          </div>
          <div class="form-group">
            <label>Handle / Username</label>
            <input type="text" class="form-control" id="fieldHandleSosial" placeholder="cth: @adorasi.smdtba">
          </div>
          <div class="form-group">
            <label>URL</label>
            <input type="text" class="form-control" id="fieldLinkSosial" placeholder="https://...">
          </div>
        </div>
      </div>
    </div>

    <div class="modal-footer">
      <div style="flex:1;font-size:11.5px;color:var(--text-muted)" id="katSavedBy"></div>
      <?php if ($isSA): ?>
      <button class="btn" id="btnKatDelete" onclick="deleteKategorialFromModal()"
              style="display:none;color:var(--danger);border-color:rgba(224,82,82,.3)">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a1 1 0 011-1h4a1 1 0 011 1v2"/></svg>
        Hapus Kelompok
      </button>
      <?php endif; ?>
      <button class="btn btn-secondary" onclick="closeModal('katModal')">Batal</button>
      <button class="btn btn-primary" id="btnKatSave" onclick="saveKategorial()">Simpan</button>
    </div>
  </div>
</div>

<!-- ── Media browser modal ── -->
<div class="media-modal-overlay" id="mediaModal">
  <div class="media-modal">
    <div class="media-modal-header">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16" style="color:var(--accent)"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
      <span class="media-modal-title" id="mediaModalTitle">Pilih Gambar</span>
      <button class="btn btn-icon btn-sm" onclick="closeMediaBrowser()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div class="media-modal-body">
      <div class="media-modal-tabs">
        <button class="media-modal-tab active" id="mediaTabUpload" onclick="switchMediaTab('upload')">Upload Baru</button>
        <button class="media-modal-tab" id="mediaTabBrowse" onclick="switchMediaTab('browse')">Gambar Tersimpan</button>
      </div>
      <div id="mediaPanelUpload">
        <div class="media-upload-zone" id="mediaUploadZone"
             onclick="document.getElementById('mediaFileInput').click()"
             ondragover="mediaDragOver(event)" ondragleave="mediaDragLeave(event)" ondrop="mediaDrop(event)">
          <div style="opacity:.35;margin-bottom:8px"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="36" height="36" style="margin:0 auto;display:block"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg></div>
          <div style="font-size:13px;color:var(--text-primary);font-weight:500">Klik atau seret gambar ke sini</div>
          <div style="font-size:11px;color:var(--text-muted);margin-top:4px">JPG, PNG, WebP, GIF · Maks 10 MB · Otomatis dikompres ke WebP</div>
          <input type="file" id="mediaFileInput" accept="image/jpeg,image/png,image/gif,image/webp" style="display:none" onchange="mediaHandleFiles(this.files)">
        </div>
        <div class="media-upload-queue" id="mediaUploadQueue"></div>
      </div>
      <div id="mediaPanelBrowse" style="display:none">
        <!-- Search -->
        <div class="media-search-wrap">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          <input type="text" class="media-search-input" id="mediaSearchInput"
                 placeholder="Cari nama file…" oninput="filterMediaGallery(this.value)">
        </div>
        <div class="media-count-label" id="mediaCountLabel"></div>
        <div id="mediaGalleryLoading" style="display:flex;align-items:center;justify-content:center;gap:8px;padding:30px;color:var(--text-muted);font-size:13px">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18" class="spin"><path d="M21 12a9 9 0 11-18 0 9 9 0 0118 0"/></svg> Memuat…
        </div>
        <div class="media-gallery" id="mediaGallery" style="display:none"></div>
        <div id="mediaGalleryEmpty" style="display:none;text-align:center;padding:40px;color:var(--text-muted);font-size:13px">Belum ada gambar di folder ini.</div>
        <div class="media-pagination" id="mediaGalleryPagination"></div>
      </div>
    </div>
    <div class="media-modal-footer">
      <div class="media-selected-preview" id="mediaSelectedPreview" style="display:none">
        <img class="media-selected-thumb" id="mediaSelectedThumb" src="" alt="">
        <span class="media-selected-url" id="mediaSelectedUrl"></span>
      </div>
      <div id="mediaNoSelection" style="flex:1"><span style="font-size:12px;color:var(--text-muted)">Belum ada gambar dipilih</span></div>
      <button class="btn btn-secondary" onclick="closeMediaBrowser()">Batal</button>
      <button class="btn btn-primary" id="btnMediaSelect" onclick="applyMediaSelection()" disabled>Gunakan Gambar Ini</button>
    </div>
  </div>
</div>

<script>
// ════════════════════════════════════════════════════════════════
// DATA
// ════════════════════════════════════════════════════════════════
const ALL_PROFIL        = <?= json_encode($allProfil, JSON_UNESCAPED_UNICODE) ?>;
const BUILT_IN_SLUGS    = <?= json_encode($BUILT_IN_SLUGS) ?>;
const BUILT_IN_ICONS    = <?= json_encode($BUILT_IN_ICONS) ?>;
const IS_SA             = <?= json_encode($isSA) ?>;
const CAN_EDIT          = <?= json_encode($canEdit) ?>;

// ════════════════════════════════════════════════════════════════
// VIEW SWITCHER
// ════════════════════════════════════════════════════════════════
function setView(v) {
  document.getElementById('viewCard').style.display  = v==='card'  ? '' : 'none';
  document.getElementById('viewTable').style.display = v==='table' ? '' : 'none';
  document.getElementById('btnViewCard').classList.toggle('active',  v==='card');
  document.getElementById('btnViewTable').classList.toggle('active', v==='table');
  localStorage.setItem('kat_view', v);
}
document.addEventListener('DOMContentLoaded', () => {
  const saved = localStorage.getItem('kat_view') || 'card';
  setView(saved);

  // Drag-drop banner
  const picker = document.getElementById('pickerBanner');
  if (picker) {
    ['dragover','dragenter'].forEach(ev => picker.addEventListener(ev, e => {
      e.preventDefault(); picker.style.borderColor='var(--accent)';
    }));
    ['dragleave','dragend'].forEach(ev => picker.addEventListener(ev, () => {
      picker.style.borderColor='';
    }));
    picker.addEventListener('drop', e => {
      e.preventDefault(); picker.style.borderColor='';
      if (e.dataTransfer.files[0]) uploadImage(e.dataTransfer.files[0], 'banner');
    });
  }

  // Media modal: tutup jika klik overlay
  const mm = document.getElementById('mediaModal');
  if (mm) mm.addEventListener('click', e => { if (e.target===mm) closeMediaBrowser(); });
});

// ════════════════════════════════════════════════════════════════
// MODAL TABS
// ════════════════════════════════════════════════════════════════
function switchTab(name, btn) {
  document.querySelectorAll('.modal-tab-pane').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('.modal-tab-btn').forEach(b => b.classList.remove('active'));
  document.getElementById('tab-'+name).classList.add('active');
  btn.classList.add('active');
}

// ════════════════════════════════════════════════════════════════
// SLUG
// ════════════════════════════════════════════════════════════════
function sanitizeSlugInput(el) {
  el.value = el.value.toLowerCase().replace(/[^a-z0-9\-]/g,'').replace(/--+/g,'-');
  updateSlugPreview();
}
function updateSlugPreview() {
  const isNew = document.getElementById('newSlugFields').style.display !== 'none';
  const slug  = isNew ? document.getElementById('fieldSlugNew').value
                      : document.getElementById('fieldSlug').value;
  document.getElementById('slugDisplay').textContent = slug || '—';
  const link = document.getElementById('previewLink');
  if (slug) { link.href='/kategorial/'+encodeURIComponent(slug); link.style.display=''; }
  else link.style.display='none';
}

// ════════════════════════════════════════════════════════════════
// PENGURUS BUILDER
// ════════════════════════════════════════════════════════════════
let _pc = 0;
// ── Pengurus photo menu ───────────────────────────────────────────────
let _pengMenuOpen = null; // id row yang menu-nya terbuka

function buildAvatarHtml(id, foto) {
  const imgPart = foto
    ? `<img class="peng-avatar" src="${escHtml(foto)}" onclick="togglePengMenu(event,${id})"
           onerror="this.style.display='none';this.nextSibling.style.display='flex'">
       <div class="peng-avatar-ph" style="display:none" onclick="togglePengMenu(event,${id})">
         <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
       </div>`
    : `<div class="peng-avatar-ph" onclick="togglePengMenu(event,${id})">
         <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
       </div>`;
  return `<div class="peng-avatar-wrap" id="pengWrap_${id}">
    ${imgPart}
    <input type="file" id="pengFile_${id}" accept="image/jpeg,image/png,image/webp" onchange="pengUpload(${id},this)">
  </div>`;
}

function addPengurusRow(nama='', jabatan='', foto='') {
  const id = ++_pc;
  document.getElementById('pengurusEmpty')?.remove();
  const row = document.createElement('div');
  row.className='pengurus-row'; row.dataset.id=id;
  row.innerHTML = buildAvatarHtml(id, foto) + `
    <input type="hidden" class="pengurus-foto" value="${escHtml(foto)}">
    <input type="text" class="form-control pengurus-nama" placeholder="Nama" value="${escHtml(nama)}" style="font-size:13px">
    <input type="text" class="form-control pengurus-jabatan" placeholder="Jabatan" value="${escHtml(jabatan)}" style="font-size:13px">
    <button type="button" class="btn btn-icon btn-sm" onclick="this.closest('.pengurus-row').remove();checkPengurusEmpty()" style="color:var(--danger);border-color:rgba(224,82,82,.3)">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6"/></svg>
    </button>`;
  document.getElementById('pengurusList').appendChild(row);
}

// Buka/tutup popup menu foto
function togglePengMenu(e, id) {
  e.stopPropagation();
  closePengMenu(); // tutup menu lain dulu

  const wrap = document.getElementById('pengWrap_' + id);
  if (!wrap) return;

  const menu = document.createElement('div');
  menu.className = 'peng-photo-menu';
  menu.id = 'pengMenu_' + id;
  menu.innerHTML = `
    <div class="peng-photo-menu-item" onclick="pengTriggerUpload(${id})">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
      Upload Baru
    </div>
    <div class="peng-photo-menu-item" onclick="openPengBrowser(${id})">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
      Pilih dari Server
    </div>
    <div class="peng-photo-menu-sep"></div>
    <div class="peng-photo-menu-item danger" onclick="pengClearFoto(${id})">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6"/></svg>
      Hapus Foto
    </div>`;
  wrap.appendChild(menu);
  _pengMenuOpen = id;
}

function closePengMenu() {
  if (_pengMenuOpen !== null) {
    document.getElementById('pengMenu_' + _pengMenuOpen)?.remove();
    _pengMenuOpen = null;
  }
}
document.addEventListener('click', () => closePengMenu());

function pengTriggerUpload(id) {
  closePengMenu();
  document.getElementById('pengFile_' + id)?.click();
}

function pengClearFoto(id) {
  closePengMenu();
  const row = document.querySelector(`.pengurus-row[data-id="${id}"]`);
  if (!row) return;
  row.querySelector('.pengurus-foto').value = '';
  const wrap = document.getElementById('pengWrap_' + id);
  wrap.innerHTML = `
    <div class="peng-avatar-ph" onclick="togglePengMenu(event,${id})">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
    </div>
    <input type="file" id="pengFile_${id}" accept="image/jpeg,image/png,image/webp" onchange="pengUpload(${id},this)">`;
}

// Nama dari input → dijadikan nama file yang readable
function pengGetSafeName(id) {
  const row  = document.querySelector(`.pengurus-row[data-id="${id}"]`);
  const nama = row?.querySelector('.pengurus-nama')?.value?.trim() || '';
  // "P. Yudianto" → "p-yudianto", "Fr. Budi" → "fr-budi"
  return nama.toLowerCase()
    .replace(/[^a-z0-9\s]/g, '')   // hapus titik, koma, dll
    .trim()
    .replace(/\s+/g, '-')           // spasi → tanda hubung
    .substring(0, 40)
    || 'pengurus';
}

async function pengUpload(id, input) {
  const file = input.files[0];
  if (!file) return;
  if (file.size > 5 * 1024 * 1024) { toast('Error','Foto maks 5 MB','error'); input.value=''; return; }

  const row  = document.querySelector(`.pengurus-row[data-id="${id}"]`);
  const wrap = document.getElementById('pengWrap_' + id);
  if (!wrap) return;

  const spin = document.createElement('div');
  spin.className = 'peng-avatar-uploading';
  spin.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5" width="16" height="16" style="animation:spin .7s linear infinite"><path d="M21 12a9 9 0 11-18 0"/></svg>';
  wrap.appendChild(spin);

  try {
    const safeName = pengGetSafeName(id);
    // Rename file sebelum kirim agar nama file di server = nama pengurus
    const renamed  = new File([file], safeName + '.' + file.name.split('.').pop(), { type: file.type });

    const form = new FormData();
    form.append('file', renamed);
    form.append('folder', 'person');
    const resp = await fetch('/admin/api/upload_image.php', { method:'POST', body:form, credentials:'same-origin' });
    const data = await resp.json();
    if (!data.success) { toast('Error', data.error||'Upload gagal', 'error'); return; }

    row.querySelector('.pengurus-foto').value = data.url;
    wrap.innerHTML = `
      <img class="peng-avatar" src="${escHtml(data.url)}?v=${Date.now()}"
           onclick="togglePengMenu(event,${id})" title="Klik untuk ganti">
      <input type="file" id="pengFile_${id}" accept="image/jpeg,image/png,image/webp" onchange="pengUpload(${id},this)">`;
    toast('Berhasil', 'Foto ' + (row.querySelector('.pengurus-nama')?.value||'pengurus') + ' diupload', 'success');
  } catch(err) {
    toast('Error','Gagal upload foto','error'); console.error(err);
  } finally {
    spin.remove(); input.value = '';
  }
}

// Browse /img/person dari media browser yang sudah ada
let _pengBrowserTarget = null;
function openPengBrowser(id) {
  closePengMenu();
  _pengBrowserTarget = id;
  _mediaCurrent = 'person';
  document.getElementById('mediaModalTitle').textContent = 'Pilih Foto Pengurus';
  document.getElementById('mediaUploadQueue').innerHTML = '';
  resetMediaSelection();
  switchMediaTab('browse');
  document.getElementById('mediaModal').classList.add('open');
  document.body.style.overflow = 'hidden';
}

// Override applyMediaSelection untuk handle pengurus photo
const _origApply = applyMediaSelection;
window.applyMediaSelection = function() {
  if (_pengBrowserTarget !== null) {
    const id  = _pengBrowserTarget;
    const url = _mediaSelectedUrl;
    _pengBrowserTarget = null;
    closeMediaBrowser();
    if (!url) return;

    const row = document.querySelector(`.pengurus-row[data-id="${id}"]`);
    if (!row) return;
    row.querySelector('.pengurus-foto').value = url;
    const wrap = document.getElementById('pengWrap_' + id);
    wrap.innerHTML = `
      <img class="peng-avatar" src="${escHtml(url)}?v=${Date.now()}"
           onclick="togglePengMenu(event,${id})" title="Klik untuk ganti">
      <input type="file" id="pengFile_${id}" accept="image/jpeg,image/png,image/webp" onchange="pengUpload(${id},this)">`;
    toast('Berhasil','Foto dipilih','success');
    return;
  }
  _origApply();
};
function checkPengurusEmpty() {
  if (!document.querySelectorAll('.pengurus-row').length) {
    document.getElementById('pengurusList').innerHTML='<div style="text-align:center;padding:20px;color:var(--text-muted);font-size:12.5px" id="pengurusEmpty">Belum ada pengurus.</div>';
  }
}
function loadPengurus(arr) {
  document.getElementById('pengurusList').innerHTML='<div style="text-align:center;padding:20px;color:var(--text-muted);font-size:12.5px" id="pengurusEmpty">Belum ada pengurus.</div>';
  _pc=0;
  if (Array.isArray(arr) && arr.length) arr.forEach(p=>addPengurusRow(p.nama||'',p.jabatan||'',p.foto||''));
}
function collectPengurus() {
  return Array.from(document.querySelectorAll('.pengurus-row')).map(row=>({
    nama:    row.querySelector('.pengurus-nama')?.value.trim()||'',
    jabatan: row.querySelector('.pengurus-jabatan')?.value.trim()||'',
    foto:    row.querySelector('.pengurus-foto')?.value.trim()||'',
  })).filter(p=>p.nama||p.jabatan);
}

// ════════════════════════════════════════════════════════════════
// OPEN MODAL
// ════════════════════════════════════════════════════════════════
function openEditModal(slug) {
  const p = ALL_PROFIL[slug] || {};
  document.getElementById('katModalTitle').textContent = 'Edit: ' + (p.nama || slug);
  document.getElementById('fieldSlug').value = slug;
  document.getElementById('newSlugFields').style.display = 'none';
  document.getElementById('slugDisplay').textContent = slug;
  const link = document.getElementById('previewLink');
  link.href='/kategorial/'+encodeURIComponent(slug); link.style.display='';

  document.getElementById('fieldNama').value        = p.nama       || '';
  document.getElementById('fieldSubtitle').value    = p.subtitle   || '';
  document.getElementById('fieldDeskripsi').value   = p.deskripsi  || '';
  document.getElementById('fieldInfo').value        = p.info       || '';
  document.getElementById('fieldKegiatan').value    = p.kegiatan   || '';
  document.getElementById('fieldTipeSosial').value  = p.tipe_sosial   || '';
  document.getElementById('fieldHandleSosial').value= p.handle_sosial || '';
  document.getElementById('fieldLinkSosial').value  = p.link_sosial   || '';

  // Gunakan icon dari DB, fallback ke icon bawaan jika kosong
  const iconVal = p.icon || BUILT_IN_ICONS[slug] || '';
  loadImagePreviews(p.banner || '', iconVal);

  let pengurus=[];
  try { pengurus=JSON.parse(p.pengurus||'[]'); } catch{}
  loadPengurus(Array.isArray(pengurus)?pengurus:[]);

  document.getElementById('katSavedBy').textContent = p.updated_by ? 'Diperbarui oleh: '+p.updated_by : '';

  const btnDel = document.getElementById('btnKatDelete');
  if (btnDel) btnDel.style.display = IS_SA ? 'inline-flex' : 'none';

  switchTab('identitas', document.querySelector('.modal-tab-btn'));
  openModal('katModal');
}

function openAddModal() {
  document.getElementById('katModalTitle').textContent = 'Tambah Kelompok Baru';
  document.getElementById('fieldSlug').value = '';
  document.getElementById('slugDisplay').textContent = '—';
  document.getElementById('newSlugFields').style.display = 'flex';
  document.getElementById('fieldSlugNew').value = '';
  document.getElementById('previewLink').style.display = 'none';
  ['fieldNama','fieldSubtitle','fieldDeskripsi','fieldInfo','fieldKegiatan',
   'fieldTipeSosial','fieldHandleSosial','fieldLinkSosial'].forEach(id=>{
    const el=document.getElementById(id); if(el) el.value='';
  });
  loadImagePreviews('','');
  loadPengurus([]);
  document.getElementById('katSavedBy').textContent='';
  const btnDel=document.getElementById('btnKatDelete');
  if(btnDel) btnDel.style.display='none';
  switchTab('identitas', document.querySelector('.modal-tab-btn'));
  openModal('katModal');
}

// ════════════════════════════════════════════════════════════════
// SAVE
// ════════════════════════════════════════════════════════════════
async function saveKategorial() {
  const btn   = document.getElementById('btnKatSave');
  const isNew = document.getElementById('newSlugFields').style.display !== 'none';
  const slug  = isNew
    ? document.getElementById('fieldSlugNew').value.trim()
    : document.getElementById('fieldSlug').value.trim();

  if (!slug) { toast('Error','Slug wajib diisi','error'); return; }
  if (isNew && !/^[a-z0-9]([a-z0-9\-]*[a-z0-9])?$/.test(slug)) {
    toast('Error','Format slug tidak valid','error'); return;
  }
  const nama = document.getElementById('fieldNama').value.trim();
  if (!nama) {
    toast('Error','Nama kelompok wajib diisi','error');
    switchTab('identitas', document.querySelector('.modal-tab-btn'));
    document.getElementById('fieldNama').focus(); return;
  }

  const payload = {
    action:       'save',
    slug,
    nama,
    subtitle:     document.getElementById('fieldSubtitle').value.trim(),
    deskripsi:    document.getElementById('fieldDeskripsi').value.trim(),
    banner:       document.getElementById('fieldBanner').value.trim(),
    icon:         document.getElementById('fieldIcon').value.trim(),
    info:         document.getElementById('fieldInfo').value.trim(),
    kegiatan:     document.getElementById('fieldKegiatan').value.trim(),
    tipe_sosial:  document.getElementById('fieldTipeSosial').value,
    handle_sosial:document.getElementById('fieldHandleSosial').value.trim(),
    link_sosial:  document.getElementById('fieldLinkSosial').value.trim(),
    pengurus:     JSON.stringify(collectPengurus()),
  };
  if (isNew) payload.is_new = true;

  btnLoading(btn, true);
  const res = await apiPost('/admin/api/kategorial.php', payload);
  btnLoading(btn, false);

  if (res.success) {
    toast('Berhasil', isNew?'Kelompok baru ditambahkan':'Profil disimpan', 'success');
    closeModal('katModal');
    setTimeout(()=>location.reload(), 900);
  } else {
    toast('Error', res.error||'Gagal menyimpan', 'error');
  }
}

// ════════════════════════════════════════════════════════════════
// DELETE
// ════════════════════════════════════════════════════════════════
function deleteKategorial(slug, nama) {
  const isBuiltIn = BUILT_IN_SLUGS.includes(slug);
  const extraNote = isBuiltIn
    ? '<br><span style="font-size:11px;color:var(--text-muted)">Catatan: kelompok ini tetap muncul di daftar (dari kode statis), hanya data profilnya yang terhapus dari Supabase.</span>'
    : '';
  confirmDialog('Hapus Data Profil?',
    `Data profil "<strong>${escHtml(nama)}</strong>" akan dihapus permanen dari Supabase.${extraNote}<br><br><span style="color:var(--danger);font-size:12px">Tindakan ini tidak bisa dibatalkan.</span>`,
    async () => {
      const res = await apiPost('/admin/api/kategorial.php', {action:'delete', slug});
      if (res.success) { toast('Berhasil',`"${nama}" dihapus`,'success'); setTimeout(()=>location.reload(),900); }
      else toast('Error', res.error||'Gagal menghapus', 'error');
    }
  );
}
function deleteKategorialFromModal() {
  const slug = document.getElementById('fieldSlug').value;
  const nama = document.getElementById('fieldNama').value||slug;
  closeModal('katModal');
  setTimeout(()=>deleteKategorial(slug,nama),200);
}

// ════════════════════════════════════════════════════════════════
// IMAGE PICKER
// ════════════════════════════════════════════════════════════════
function triggerPicker(type) {
  document.getElementById(type==='banner'?'fileBanner':'fileIcon').click();
}
function handleFileSelect(input, type) {
  const file=input.files[0]; if(!file) return;
  if(file.size>10*1024*1024){toast('Error','Ukuran max 10 MB','error');input.value='';return;}
  if(!['image/jpeg','image/png','image/gif','image/webp'].includes(file.type)){toast('Error','Format tidak didukung','error');input.value='';return;}
  uploadImage(file,type);
  input.value='';
}
async function uploadImage(file, type) {
  const picker=document.getElementById(type==='banner'?'pickerBanner':'pickerIcon');
  const overlay=document.createElement('div');
  overlay.className='img-picker-uploading';
  overlay.innerHTML=`<div class="img-picker-uploading-text">Mengupload &amp; mengompresi…</div><div class="img-picker-progress"><div class="img-picker-progress-bar"></div></div>`;
  picker.style.position='relative'; picker.appendChild(overlay);
  try {
    const form=new FormData(); form.append('file',file); form.append('folder',type==='banner'?'banner':'icon');
    const resp=await fetch('/admin/api/upload_image.php',{method:'POST',body:form,credentials:'same-origin'});
    const data=await resp.json();
    if(!data.success){toast('Error',data.error||'Upload gagal','error');return;}
    document.getElementById(type==='banner'?'fieldBanner':'fieldIcon').value=data.url;
    setImagePreview(type,data.url,data);
    const info=document.getElementById(type==='banner'?'infoBanner':'infoIcon');
    if(info){const saved=data.saved_pct>0?` · <span class="img-saved-badge">✓ Hemat ${data.saved_pct}%</span>`:'';info.innerHTML=`${data.width}×${data.height}px · ${formatBytes(data.size_bytes)}${saved}`;}
    toast('Berhasil',`Gambar diupload${data.saved_pct>0?' (hemat '+data.saved_pct+'%)':''}`, 'success');
  } catch(err){toast('Error','Gagal menghubungi server','error');console.error(err);}
  finally{overlay.remove();}
}
function setImagePreview(type,url,data) {
  if(type==='banner'){
    const img=document.getElementById('imgPreviewBanner');
    const empty=document.getElementById('emptyBanner');
    const acts=document.getElementById('actionsBanner');
    img.src=url+'?v='+Date.now(); img.style.display='block';
    if(empty) empty.style.display='none';
    if(acts)  acts.style.display='flex';
    const prev=document.getElementById('previewBanner');
    if(prev&&data?.height&&data?.width){
      const r=data.height/data.width;
      prev.style.minHeight=Math.max(80,Math.min(300,400*r))+'px';
    }
  } else {
    const img=document.getElementById('imgPreviewIcon');
    const empty=document.getElementById('emptyIcon');
    const btn=document.getElementById('btnClearIcon');
    img.src=url+'?v='+Date.now(); img.style.display='block';
    if(empty) empty.style.display='none';
    if(btn)   btn.style.display='inline-flex';
  }
}
function clearImage(type) {
  document.getElementById(type==='banner'?'fieldBanner':'fieldIcon').value='';
  if(type==='banner'){
    const img=document.getElementById('imgPreviewBanner');
    img.src=''; img.style.display='none';
    document.getElementById('emptyBanner').style.display='flex';
    document.getElementById('actionsBanner').style.display='none';
    document.getElementById('infoBanner').innerHTML='';
  } else {
    const img=document.getElementById('imgPreviewIcon');
    img.src=''; img.style.display='none';
    document.getElementById('emptyIcon').style.display='flex';
    const btn=document.getElementById('btnClearIcon');
    if(btn) btn.style.display='none';
    document.getElementById('infoIcon').innerHTML='';
  }
}
function loadImagePreviews(banner,icon) {
  if(banner){
    document.getElementById('fieldBanner').value=banner;
    const img=document.getElementById('imgPreviewBanner');
    img.src=banner; img.style.display='block';
    document.getElementById('emptyBanner').style.display='none';
    document.getElementById('actionsBanner').style.display='flex';
    document.getElementById('infoBanner').innerHTML='<span style="font-size:10.5px;color:var(--text-muted)">Tersimpan</span>';
  } else { clearImage('banner'); }
  if(icon){
    document.getElementById('fieldIcon').value=icon;
    const img=document.getElementById('imgPreviewIcon');
    img.src=icon; img.style.display='block';
    document.getElementById('emptyIcon').style.display='none';
    const btn=document.getElementById('btnClearIcon');
    if(btn) btn.style.display='inline-flex';
    document.getElementById('infoIcon').innerHTML='<span style="font-size:10.5px;color:var(--text-muted)">Icon tersimpan</span>';
  } else { clearImage('icon'); }
}
function formatBytes(b){if(b<1024)return b+' B';if(b<1048576)return(b/1024).toFixed(1)+' KB';return(b/1048576).toFixed(2)+' MB';}

// ════════════════════════════════════════════════════════════════
// MEDIA BROWSER
// ════════════════════════════════════════════════════════════════
let _mediaCurrent=null, _mediaSelectedUrl=null, _mediaPage=1;

function openMediaBrowser(type) {
  _mediaCurrent=type; _mediaSelectedUrl=null; _mediaPage=1;
  document.getElementById('mediaModalTitle').textContent = type==='banner'?'Pilih / Upload Banner':'Pilih / Upload Icon';
  document.getElementById('mediaUploadQueue').innerHTML='';
  resetMediaSelection();
  switchMediaTab('upload');
  document.getElementById('mediaModal').classList.add('open');
  document.body.style.overflow='hidden';
}
function closeMediaBrowser() {
  document.getElementById('mediaModal').classList.remove('open');
  document.body.style.overflow='';
  _mediaCurrent=null; _mediaSelectedUrl=null;
}
function switchMediaTab(tab) {
  document.getElementById('mediaTabUpload').classList.toggle('active', tab==='upload');
  document.getElementById('mediaTabBrowse').classList.toggle('active', tab==='browse');
  document.getElementById('mediaPanelUpload').style.display = tab==='upload'?'':'none';
  document.getElementById('mediaPanelBrowse').style.display = tab==='browse'?'':'none';
  if(tab==='browse') {
    const s = document.getElementById('mediaSearchInput');
    if (s) s.value = '';
    loadMediaGallery(1);
  }
}
async function loadMediaGallery(page=1) {
  _mediaPage=page;
  const loading=document.getElementById('mediaGalleryLoading');
  const gallery=document.getElementById('mediaGallery');
  const empty=document.getElementById('mediaGalleryEmpty');
  const pager=document.getElementById('mediaGalleryPagination');
  loading.style.display='flex'; gallery.style.display='none'; empty.style.display='none'; pager.innerHTML='';
  try {
    // Gunakan _mediaCurrent langsung sebagai folder (banner/icon/person)
    const folder = ['banner','icon','person'].includes(_mediaCurrent) ? _mediaCurrent : 'icon';
    const res=await fetch(`/admin/api/list_images.php?folder=${folder}&page=${page}`,{credentials:'same-origin'});
    const data=await res.json();
    loading.style.display='none';
    if(!data.success||!data.images?.length){empty.style.display='block';return;}
    gallery.innerHTML='';
    data.images.forEach(img=>{
      // Label: nama file tanpa ekstensi, maksimal readable
      const label = img.name.replace(/\.[^.]+$/, '').replace(/[_\-]/g,' ');
      const el=document.createElement('div');
      el.className='media-thumb'; el.dataset.url=img.url; el.dataset.name=img.name.toLowerCase();
      el.innerHTML=`
        <div class="media-thumb-img-wrap">
          <img src="${escHtml(img.url)}?v=${Date.now()}" alt="${escHtml(img.name)}" loading="lazy">
          <div class="media-thumb-check">✓</div>
        </div>
        <div class="media-thumb-label" title="${escHtml(img.name)}">${escHtml(label)}</div>`;
      el.addEventListener('click',()=>selectMediaThumb(el,img.url));
      gallery.appendChild(el);
    });
    gallery.style.display='grid';
    // Update count label
    const countEl = document.getElementById('mediaCountLabel');
    if (countEl) countEl.textContent = `${data.total} file · halaman ${page} dari ${data.pages||1}`;
    // Reset search
    const searchEl = document.getElementById('mediaSearchInput');
    if (searchEl) searchEl.value = '';
    if(data.pages>1){for(let i=1;i<=data.pages;i++){const b=document.createElement('button');b.className='media-page-btn'+(i===page?' active':'');b.textContent=i;b.onclick=()=>loadMediaGallery(i);pager.appendChild(b);}}
  } catch(err){loading.style.display='none';empty.style.display='block';console.error(err);}
}
// Filter thumb berdasarkan keyword search (client-side, tanpa reload)
function filterMediaGallery(q) {
  const keyword = q.toLowerCase().trim();
  const thumbs  = document.querySelectorAll('.media-thumb');
  let visible   = 0;
  thumbs.forEach(el => {
    const match = !keyword || el.dataset.name.includes(keyword);
    el.style.display = match ? '' : 'none';
    if (match) visible++;
  });
  const countEl = document.getElementById('mediaCountLabel');
  if (countEl) {
    if (keyword) {
      countEl.textContent = `${visible} hasil untuk "${q}"`;
      countEl.style.color = visible ? 'var(--text-muted)' : 'var(--danger)';
    } else {
      const total = thumbs.length;
      countEl.textContent = `${total} file`;
      countEl.style.color = '';
    }
  }
  // Jika yang dipilih tersembunyi → reset seleksi
  const selected = document.querySelector('.media-thumb.selected');
  if (selected && selected.style.display === 'none') {
    selected.classList.remove('selected');
    _mediaSelectedUrl = null;
    updateMediaFooter(null);
  }
}

function selectMediaThumb(el,url) {
  document.querySelectorAll('.media-thumb').forEach(t=>t.classList.remove('selected'));
  el.classList.add('selected'); _mediaSelectedUrl=url; updateMediaFooter(url);
}
function updateMediaFooter(url) {
  const preview=document.getElementById('mediaSelectedPreview');
  const noSel=document.getElementById('mediaNoSelection');
  const btn=document.getElementById('btnMediaSelect');
  if(url){preview.style.display='flex';noSel.style.display='none';document.getElementById('mediaSelectedThumb').src=url;document.getElementById('mediaSelectedUrl').textContent=url;btn.disabled=false;}
  else{preview.style.display='none';noSel.style.display='';btn.disabled=true;}
}
function resetMediaSelection(){_mediaSelectedUrl=null;document.querySelectorAll('.media-thumb').forEach(t=>t.classList.remove('selected'));updateMediaFooter(null);}
function applyMediaSelection() {
  if(!_mediaSelectedUrl||!_mediaCurrent) return;
  document.getElementById(_mediaCurrent==='banner'?'fieldBanner':'fieldIcon').value=_mediaSelectedUrl;
  setImagePreview(_mediaCurrent,_mediaSelectedUrl,null);
  const info=document.getElementById(_mediaCurrent==='banner'?'infoBanner':'infoIcon');
  if(info) info.innerHTML='<span style="font-size:10.5px;color:var(--text-muted)">Dari server</span>';
  closeMediaBrowser();
  toast('Berhasil','Gambar dipilih','success');
}
function mediaDragOver(e){e.preventDefault();document.getElementById('mediaUploadZone').classList.add('dragging');}
function mediaDragLeave(){document.getElementById('mediaUploadZone').classList.remove('dragging');}
function mediaDrop(e){e.preventDefault();document.getElementById('mediaUploadZone').classList.remove('dragging');if(e.dataTransfer.files.length) mediaHandleFiles(e.dataTransfer.files);}
function mediaHandleFiles(files){Array.from(files).forEach(f=>mediaUploadOne(f));}
async function mediaUploadOne(file) {
  if(file.size>10*1024*1024){toast('Error',file.name+': Maks 10 MB','error');return;}
  if(!['image/jpeg','image/png','image/gif','image/webp'].includes(file.type)){toast('Error',file.name+': Format tidak didukung','error');return;}
  const queue=document.getElementById('mediaUploadQueue');
  const safeId='mq_'+Date.now()+'_'+Math.random().toString(36).slice(2);
  const item=document.createElement('div'); item.className='media-upload-item';
  item.innerHTML=`<div style="flex:1;min-width:0"><div style="display:flex;align-items:center;gap:8px"><span class="media-upload-item-name">${escHtml(file.name)}</span><span style="font-size:11px;color:var(--text-muted)">${formatBytes(file.size)}</span><span id="st_${safeId}"></span></div><div class="media-upload-item-bar"><div class="media-upload-item-bar-fill" id="bar_${safeId}" style="width:0%"></div></div></div>`;
  queue.appendChild(item);
  const bar=document.getElementById('bar_'+safeId);
  const progInt=setInterval(()=>{if(bar){const w=parseFloat(bar.style.width||0);bar.style.width=Math.min(w+Math.random()*20,85)+'%';}},180);
  try {
    const form=new FormData(); form.append('file',file); form.append('folder',_mediaCurrent==='banner'?'banner':'icon');
    const resp=await fetch('/admin/api/upload_image.php',{method:'POST',body:form,credentials:'same-origin'});
    const data=await resp.json();
    clearInterval(progInt); if(bar) bar.style.width='100%';
    const st=document.getElementById('st_'+safeId);
    if(!data.success){if(st) st.innerHTML='<svg viewBox="0 0 24 24" fill="none" stroke="#e05252" stroke-width="2.5" width="13" height="13"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>';toast('Error',data.error||'Upload gagal','error');return;}
    if(st){const saved=data.saved_pct>0?` · hemat ${data.saved_pct}%`:'';st.innerHTML=`<svg viewBox="0 0 24 24" fill="none" stroke="#3cb371" stroke-width="2.5" width="13" height="13"><polyline points="20 6 9 17 4 12"/></svg><span style="font-size:10px;color:#3cb371">${formatBytes(data.size_bytes)}${saved}</span>`;}
    _mediaSelectedUrl=data.url; updateMediaFooter(data.url);
    document.getElementById(_mediaCurrent==='banner'?'fieldBanner':'fieldIcon').value=data.url;
    setImagePreview(_mediaCurrent,data.url,data);
  } catch(err){clearInterval(progInt);toast('Error','Gagal menghubungi server','error');console.error(err);}
}
</script>

<?php adminFooter(); ?>