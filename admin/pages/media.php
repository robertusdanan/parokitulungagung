<?php
/**
 * admin/pages/media.php
 * Manajemen Media — Browser, Upload, Kompress, Rename, Hapus
 * + OG Preview tab untuk folder Artikel (konversi ke JPG 1200×630 untuk WhatsApp)
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
adminBoot();
$user = requirePageAccess('media');
adminHeader('Manajemen Media', 'media', $user);

$canEdit   = userCan($user, 'edit');
$canCreate = userCan($user, 'create');
$canDelete = userCan($user, 'delete');
?>

<style>
/* ── Media Manager ─────────────────────────────────── */
.media-layout { display:flex; gap:0; height:calc(100vh - 120px); min-height:500px; }

.media-sidebar {
  width:220px; flex-shrink:0;
  background:var(--bg-card);
  border:1px solid var(--border);
  border-radius:var(--radius) 0 0 var(--radius);
  overflow-y:auto;
  display:flex; flex-direction:column;
}
.media-sidebar-header {
  padding:16px 16px 12px;
  font-size:11px; font-weight:700; letter-spacing:.1em;
  text-transform:uppercase; color:var(--text-muted);
  border-bottom:1px solid var(--border); flex-shrink:0;
}
.media-folder-item {
  display:flex; align-items:center; gap:10px;
  padding:10px 14px; cursor:pointer;
  border-left:3px solid transparent;
  transition:all .15s; font-size:13px; color:var(--text-secondary);
  border-bottom:1px solid rgba(255,255,255,.03);
  user-select:none;
}
.media-folder-item:hover { background:var(--bg-card2); color:var(--text-primary); }
.media-folder-item.active { border-left-color:var(--accent); background:var(--accent-dim); color:var(--accent); font-weight:500; }
.media-folder-icon { font-size:16px; flex-shrink:0; }
.media-folder-meta { margin-left:auto; font-size:10.5px; color:var(--text-muted); white-space:nowrap; }
.media-folder-item.active .media-folder-meta { color:var(--accent); opacity:.7; }

.media-main {
  flex:1; min-width:0;
  background:var(--bg-card2);
  border:1px solid var(--border); border-left:none;
  border-radius:0 var(--radius) var(--radius) 0;
  display:flex; flex-direction:column; overflow:hidden;
}
.media-topbar {
  display:flex; align-items:center; gap:10px;
  padding:12px 16px; border-bottom:1px solid var(--border);
  background:var(--bg-card); flex-shrink:0; flex-wrap:wrap; gap:8px;
}
.media-topbar-title { font-size:13.5px; font-weight:600; color:var(--text-primary); margin-right:4px; }
.media-search-wrap { position:relative; flex:1; min-width:160px; max-width:280px; }
.media-search-wrap input { width:100%; background:var(--bg-input); border:1px solid var(--border); color:var(--text-primary); font-size:13px; padding:7px 10px 7px 32px; border-radius:var(--radius-sm); outline:none; transition:border-color .15s; }
.media-search-wrap input:focus { border-color:var(--border-focus); }
.media-search-wrap svg { position:absolute; left:9px; top:50%; transform:translateY(-50%); color:var(--text-muted); pointer-events:none; }
.media-view-toggle { display:flex; gap:2px; }
.media-view-btn { width:30px; height:30px; display:flex; align-items:center; justify-content:center; background:none; border:1px solid var(--border); border-radius:var(--radius-sm); cursor:pointer; color:var(--text-muted); transition:all .15s; }
.media-view-btn.active, .media-view-btn:hover { background:var(--accent-dim); border-color:var(--accent); color:var(--accent); }

/* Tab bar (muncul hanya di folder artikel) */
.media-tab-bar {
  display:none; /* JS yang menampilkan */
  align-items:center; gap:0;
  background:var(--bg-card);
  border-bottom:1px solid var(--border);
  flex-shrink:0;
}
.media-tab {
  padding:10px 20px; font-size:13px; font-weight:500;
  color:var(--text-secondary); cursor:pointer; border:none; background:none;
  border-bottom:2px solid transparent; transition:all .15s; white-space:nowrap;
  position:relative;
}
.media-tab:hover { color:var(--text-primary); }
.media-tab.active { color:var(--accent); border-bottom-color:var(--accent); }
.media-tab-badge {
  display:inline-flex; align-items:center; justify-content:center;
  min-width:18px; height:18px; padding:0 5px;
  background:rgba(224,82,82,.2); color:#e05252;
  font-size:10px; font-weight:700; border-radius:10px;
  margin-left:6px;
}
.media-tab-badge.ok { background:rgba(60,179,113,.2); color:#3cb371; }

.media-statsbar {
  display:flex; align-items:center; gap:16px;
  padding:8px 16px; border-bottom:1px solid var(--border);
  background:rgba(0,0,0,.15); flex-shrink:0; font-size:12px; color:var(--text-muted);
}
.media-stat { display:flex; align-items:center; gap:5px; }
.media-stat strong { color:var(--text-primary); }
.media-compress-btn {
  margin-left:auto; display:flex; align-items:center; gap:6px;
  padding:5px 12px; font-size:12px; font-weight:500;
  background:rgba(201,168,76,.1); border:1px solid rgba(201,168,76,.3);
  color:var(--accent); border-radius:20px; cursor:pointer; transition:all .2s;
  white-space:nowrap;
}
.media-compress-btn:hover { background:var(--accent-dim); border-color:var(--accent); }
.media-compress-btn:disabled { opacity:.4; cursor:not-allowed; }

.media-body { flex:1; overflow-y:auto; padding:14px 16px; }

/* Grid view */
.media-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(140px,1fr)); gap:10px; }
.media-card {
  background:var(--bg-card); border:1px solid var(--border);
  border-radius:var(--radius-sm); overflow:hidden;
  transition:border-color .18s, box-shadow .18s; cursor:pointer; position:relative;
  display:flex; flex-direction:column;
}
.media-card:hover { border-color:var(--accent); box-shadow:0 4px 16px rgba(0,0,0,.3); }
.media-card.selected { border-color:var(--accent); box-shadow:0 0 0 2px var(--accent); }
.media-thumb-wrap { width:100%; aspect-ratio:1; background:var(--bg-main); overflow:hidden; display:flex; align-items:center; justify-content:center; position:relative; }
.media-thumb { width:100%; height:100%; object-fit:cover; display:block; }
.media-file-icon { font-size:32px; }
.media-card-compressed { position:absolute; top:5px; right:5px; background:rgba(60,179,113,.85); color:#fff; font-size:9px; font-weight:700; padding:2px 6px; border-radius:10px; }
.media-card-info { padding:7px 9px 8px; }
.media-card-name { font-size:11px; color:var(--text-primary); font-weight:500; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; margin-bottom:2px; }
.media-card-meta { font-size:10px; color:var(--text-muted); display:flex; justify-content:space-between; }
.media-card-actions { display:none; position:absolute; top:4px; left:4px; gap:3px; }
.media-card:hover .media-card-actions { display:flex; }
.media-act-btn { width:24px; height:24px; border-radius:4px; border:none; cursor:pointer; display:flex; align-items:center; justify-content:center; backdrop-filter:blur(4px); transition:all .15s; }

/* List view */
.media-list-view .media-grid { display:block; }
.media-list-view .media-card { flex-direction:row; align-items:center; gap:0; border-radius:var(--radius-sm); margin-bottom:4px; }
.media-list-view .media-thumb-wrap { width:44px; height:44px; aspect-ratio:1; flex-shrink:0; border-radius:var(--radius-sm) 0 0 var(--radius-sm); }
.media-list-view .media-card-info { flex:1; display:flex; align-items:center; gap:12px; padding:8px 12px; }
.media-list-view .media-card-name { flex:1; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; font-size:12.5px; }
.media-list-view .media-card-meta { flex-direction:row; gap:12px; }
.media-list-view .media-card-actions { position:static; display:flex; margin-left:auto; padding-right:10px; }
.media-list-view .media-card:hover .media-card-actions { display:flex; }

.media-empty { text-align:center; padding:60px 20px; color:var(--text-muted); }
.media-empty-icon { font-size:48px; margin-bottom:12px; opacity:.4; }

.media-upload-zone {
  border:2px dashed var(--border); border-radius:var(--radius);
  padding:24px; text-align:center; cursor:pointer;
  transition:all .2s; margin-bottom:14px; display:none;
}
.media-upload-zone.active, .media-upload-zone:hover { border-color:var(--accent); background:var(--accent-dim); }
.media-upload-zone p { font-size:13px; color:var(--text-secondary); margin:6px 0 0; }
.media-upload-zone small { font-size:11px; color:var(--text-muted); }

.compress-result {
  background:var(--bg-card); border:1px solid var(--border);
  border-radius:var(--radius); padding:14px 16px; margin-bottom:14px; display:none;
}
.compress-result-row { display:flex; gap:16px; flex-wrap:wrap; margin-bottom:8px; }
.compress-stat-box { background:var(--bg-card2); border-radius:var(--radius-sm); padding:10px 14px; text-align:center; min-width:80px; }
.compress-stat-num { font-size:22px; font-weight:700; color:var(--accent); }
.compress-stat-label { font-size:10px; color:var(--text-muted); margin-top:2px; }

.media-progress-overlay {
  display:none; position:absolute; inset:0; background:rgba(0,0,0,.7);
  backdrop-filter:blur(4px); align-items:center; justify-content:center;
  flex-direction:column; gap:12px; z-index:10; border-radius:inherit;
}
.media-progress-overlay.show { display:flex; }
.media-progress-text { font-size:14px; color:#fff; font-weight:500; }

/* ── OG Preview Panel ───────────────────────────────── */
#ogPanel { display:none; }

.og-toolbar {
  display:flex; align-items:center; gap:10px; flex-wrap:wrap;
  padding:12px 16px; border-bottom:1px solid var(--border);
  background:rgba(0,0,0,.1); flex-shrink:0;
}
.og-stat { font-size:12px; color:var(--text-muted); display:flex; align-items:center; gap:5px; }
.og-stat strong { color:var(--text-primary); }

.og-bulk-btn {
  display:flex; align-items:center; gap:6px;
  padding:6px 14px; font-size:12px; font-weight:600;
  border-radius:20px; cursor:pointer; transition:all .2s; border:none;
  white-space:nowrap;
}
.og-bulk-btn.primary {
  background:rgba(82,148,224,.15); border:1px solid rgba(82,148,224,.35);
  color:#5294e0;
}
.og-bulk-btn.primary:hover { background:rgba(82,148,224,.25); }
.og-bulk-btn.danger {
  background:rgba(224,82,82,.1); border:1px solid rgba(224,82,82,.25);
  color:var(--danger);
}
.og-select-all-wrap {
  display:flex; align-items:center; gap:6px;
  font-size:12px; color:var(--text-secondary); cursor:pointer; user-select:none;
}

.og-grid {
  display:grid;
  grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
  gap:12px; padding:14px 16px;
}

.og-card {
  background:var(--bg-card); border:2px solid var(--border);
  border-radius:var(--radius-sm); overflow:hidden;
  transition:border-color .18s; position:relative;
}
.og-card.has-og  { border-color:rgba(60,179,113,.35); }
.og-card.selected { border-color:var(--accent) !important; box-shadow:0 0 0 2px rgba(201,168,76,.3); }

/* Checkbox di pojok kiri atas */
.og-card-check {
  position:absolute; top:8px; left:8px; z-index:2;
  width:18px; height:18px; accent-color:var(--accent); cursor:pointer;
}

/* Badge status OG */
.og-status-badge {
  position:absolute; top:8px; right:8px; z-index:2;
  font-size:9px; font-weight:700; padding:3px 8px; border-radius:12px;
  white-space:nowrap;
}
.og-status-badge.done { background:rgba(60,179,113,.85); color:#fff; }
.og-status-badge.none { background:rgba(224,82,82,.75); color:#fff; }

.og-thumb-wrap {
  width:100%; aspect-ratio:16/9;
  background:var(--bg-main); overflow:hidden;
  display:flex; align-items:center; justify-content:center;
}
.og-thumb { width:100%; height:100%; object-fit:cover; display:block; }

.og-card-body { padding:10px 12px 12px; }
.og-card-name {
  font-size:12px; font-weight:600; color:var(--text-primary);
  white-space:nowrap; overflow:hidden; text-overflow:ellipsis; margin-bottom:6px;
}
.og-card-meta { font-size:11px; color:var(--text-muted); margin-bottom:8px; display:flex; gap:10px; flex-wrap:wrap; }

.og-url-wrap {
  display:flex; align-items:center; gap:6px;
  background:var(--bg-main); border:1px solid var(--border);
  border-radius:var(--radius-sm); padding:5px 8px; margin-bottom:8px;
}
.og-url-text {
  flex:1; font-size:10px; color:var(--accent);
  white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
  font-family:monospace;
}
.og-copy-btn {
  flex-shrink:0; background:none; border:none; cursor:pointer;
  color:var(--text-muted); padding:2px; transition:color .15s; display:flex;
}
.og-copy-btn:hover { color:var(--accent); }

.og-card-actions { display:flex; gap:6px; }
.og-action-btn {
  flex:1; display:flex; align-items:center; justify-content:center; gap:5px;
  padding:6px 10px; font-size:11.5px; font-weight:500; border-radius:var(--radius-sm);
  cursor:pointer; transition:all .18s; border:1px solid;
}
.og-action-btn.convert {
  background:rgba(82,148,224,.1); border-color:rgba(82,148,224,.3); color:#5294e0;
}
.og-action-btn.convert:hover { background:rgba(82,148,224,.2); }
.og-action-btn.delete {
  flex:0 0 auto; padding:6px 8px;
  background:rgba(224,82,82,.08); border-color:rgba(224,82,82,.25); color:var(--danger);
}
.og-action-btn.delete:hover { background:rgba(224,82,82,.18); }

.og-empty { text-align:center; padding:60px 20px; color:var(--text-muted); }
.og-loading { text-align:center; padding:60px; }

/* ── Cloudflare R2 panel ────────────────────────────── */
/* Upload area toggle — collapsed by default, expands on button click */
.r2-upload-toggle {
  display:flex; align-items:center; gap:8px;
  padding:10px 16px 8px;
}
.r2-upload-btn {
  display:inline-flex; align-items:center; gap:6px;
  padding:6px 14px; border-radius:var(--radius-sm);
  background:var(--accent-dim); border:1px solid var(--accent);
  color:var(--accent); font-size:12.5px; font-weight:500;
  cursor:pointer; transition:all .18s;
}
.r2-upload-btn:hover { background:var(--accent); color:#fff; }
.r2-upload-btn svg { transition:transform .25s; }
.r2-upload-btn.open svg { transform:rotate(180deg); }
.r2-drop-collapsible {
  display:grid; grid-template-rows:0fr;
  transition:grid-template-rows .28s ease;
}
.r2-drop-collapsible.open { grid-template-rows:1fr; }
.r2-drop-collapsible > .r2-drop-inner { overflow:hidden; }
.r2-drop-wrap { padding:0 16px 0; }
.r2-dropzone {
  border:2px dashed var(--border); border-radius:var(--radius);
  padding:26px 20px; text-align:center; transition:all .2s;
}
.r2-dropzone.active { border-color:var(--accent); background:var(--accent-dim); }
.r2-dropzone p { font-size:13.5px; color:var(--text-secondary); margin:10px 0 4px; }
.r2-dropzone small { font-size:11px; color:var(--text-muted); display:block; max-width:520px; margin:0 auto; }

.r2-progress-box { margin:12px 16px 0; background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius-sm); padding:12px 14px; }
.r2-progress-track { height:8px; border-radius:6px; background:var(--bg-main); overflow:hidden; }
.r2-progress-bar { height:100%; background:var(--accent); transition:width .2s; border-radius:6px; }

.r2-album-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(220px,1fr)); gap:12px; }
.r2-album-card {
  background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius-sm);
  padding:14px 16px; cursor:pointer; transition:border-color .15s, box-shadow .15s;
}
.r2-album-card:hover { border-color:var(--accent); box-shadow:0 4px 16px rgba(0,0,0,.25); }
.r2-album-name { font-size:13.5px; font-weight:600; color:var(--text-primary); display:flex; align-items:center; gap:8px; margin-bottom:8px; word-break:break-all; }
.r2-album-meta { font-size:11.5px; color:var(--text-muted); display:flex; gap:14px; flex-wrap:wrap; }
.r2-stat-loading { opacity:.55; animation:r2Pulse 1.3s ease-in-out infinite; }
@keyframes r2Pulse { 0%,100%{opacity:.35} 50%{opacity:.75} }

@media (max-width:768px) {
  .media-layout { flex-direction:column; height:auto; }
  .media-sidebar { width:100%; max-height:200px; border-radius:var(--radius) var(--radius) 0 0; }
  .media-main { border-left:1px solid var(--border); border-radius:0 0 var(--radius) var(--radius); }
  .media-grid { grid-template-columns:repeat(auto-fill,minmax(100px,1fr)); }
  .og-grid { grid-template-columns:1fr; }
}

/* ── Mobile overrides (auto-injected) ──────────────────────── */
@media (max-width: 768px) {
  .media-layout {
    flex-direction: column;
    height: auto;
    min-height: unset;
  }
  .media-sidebar {
    width: 100%;
    max-height: 56px;
    flex-direction: row;
    overflow-x: auto;
    overflow-y: hidden;
    border-radius: var(--radius) var(--radius) 0 0;
    border: 1px solid var(--border);
    border-bottom: none;
    scrollbar-width: none;
  }
  .media-sidebar::-webkit-scrollbar { display: none; }
  .media-sidebar-header { display: none; }
  .media-folder-item {
    flex-direction: column;
    align-items: center;
    gap: 2px;
    padding: 8px 14px;
    min-width: 80px;
    font-size: 10.5px;
    border-left: none;
    border-bottom: 3px solid transparent;
    white-space: nowrap;
  }
  .media-folder-item.active {
    border-left: none;
    border-bottom-color: var(--accent);
  }
  .media-folder-icon { font-size: 18px; }
  .media-folder-meta { display: none; }
  .media-main {
    border-left: 1px solid var(--border);
    border-top: none;
    border-radius: 0 0 var(--radius) var(--radius);
    min-height: 400px;
  }
  .media-topbar { flex-wrap: wrap; }
  .media-search-wrap { max-width: none; flex: 1 1 100%; }
}

</style>

<div class="page-header">
  <div class="page-header-left">
    <h1>Manajemen Media</h1>
    <p>Kelola file gambar di semua folder website</p>
  </div>
</div>

<div class="media-layout" style="position:relative">
  <!-- Progress overlay -->
  <div class="media-progress-overlay" id="progressOverlay">
    <div class="spinner"></div>
    <div class="media-progress-text" id="progressText">Memproses...</div>
  </div>

  <!-- Sidebar folder -->
  <div class="media-sidebar">
    <div class="media-sidebar-header">Folder</div>
    <div id="folderList">
      <?php
      $folders = [
        'umkm'     => ['icon'=>'🛒', 'label'=>'UMKM Umat'],
        'artikel'  => ['icon'=>'📰', 'label'=>'Artikel'],
        'gereja'   => ['icon'=>'⛪', 'label'=>'Foto Gereja'],
        'icon'     => ['icon'=>'🔷', 'label'=>'Icon'],
        'person'   => ['icon'=>'👤', 'label'=>'Foto Person'],
        'root_img' => ['icon'=>'📁', 'label'=>'Root /img'],
      ];
      foreach ($folders as $key => $f): ?>
      <div class="media-folder-item" data-folder="<?= $key ?>">
        <span class="media-folder-icon"><?= $f['icon'] ?></span>
        <span><?= $f['label'] ?></span>
        <span class="media-folder-meta" id="meta-<?= $key ?>">...</span>
      </div>
      <?php endforeach; ?>
      <!-- Folder "Thumbnail Galeri" lama (/public/galeri) sudah tidak dipakai —
           thumbnail galeri sekarang disimpan di R2 (_thumbnails/galeri/), lihat
           entri "Thumbnail" di bawah bagian Cloud Storage. -->
      <div class="media-sidebar-header" style="border-top:1px solid var(--border);margin-top:4px">Cloud Storage</div>
      <div class="media-folder-item" data-folder="r2galeri">
        <span class="media-folder-icon">🖼️</span>
        <span>Thumbnail</span>
        <span class="media-folder-meta" id="meta-r2galeri">...</span>
      </div>
      <div class="media-folder-item" data-folder="r2cloud">
        <span class="media-folder-icon">☁️</span>
        <span>Galeri Foto</span>
        <span class="media-folder-meta" id="meta-r2cloud">...</span>
      </div>
    </div>
  </div>

  <!-- Main area -->
  <div class="media-main">
    <!-- Topbar -->
    <div class="media-topbar">
      <span class="media-topbar-title" id="currentFolderLabel">Pilih folder</span>
      <div class="media-search-wrap" id="mainSearchWrap">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" id="mediaSearch" placeholder="Cari nama file..." oninput="applyFilter()">
      </div>
      <div class="media-view-toggle" id="viewToggle">
        <button class="media-view-btn active" id="viewGrid" onclick="setView('grid')" title="Grid">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
        </button>
        <button class="media-view-btn" id="viewList" onclick="setView('list')" title="List">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
        </button>
      </div>
      <?php if ($canCreate): ?>
      <button class="btn btn-primary btn-sm" id="btnUpload" onclick="toggleUpload()" style="display:none">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
        Upload
      </button>
      <?php endif; ?>
    </div>

    <!-- Tab bar (hanya folder artikel) -->
    <div class="media-tab-bar" id="tabBar">
      <button class="media-tab active" id="tabFiles" onclick="switchTab('files')">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13" style="vertical-align:middle;margin-right:5px"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
        Semua File
      </button>
      <button class="media-tab" id="tabOG" onclick="switchTab('og')">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13" style="vertical-align:middle;margin-right:5px"><path d="M21 2H3v16h5l3 3 3-3h7V2z"/><path d="M11.5 7.5h1"/><path d="M7 7.5h.5"/><path d="M16.5 7.5h.5"/><path d="M7 11h10"/></svg>
        OG Preview
        <span class="media-tab-badge" id="ogBadgePending">0</span>
      </button>
    </div>

    <!-- Stats bar -->
    <div class="media-statsbar" id="statsBar" style="display:none">
      <div class="media-stat"><span>Total:</span> <strong id="statTotal">0</strong> file</div>
      <div class="media-stat"><span>Ukuran:</span> <strong id="statSize">0</strong> KB</div>
      <div class="media-stat">
        <span style="color:var(--success)">●</span>
        <span>Sudah kompress:</span> <strong id="statCompressed" style="color:var(--success)">0</strong>
      </div>
      <?php if ($canEdit): ?>
      <button class="media-compress-btn" id="btnBulkCompress" onclick="bulkCompress()" style="display:none">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12"><polyline points="21 8 21 21 3 21 3 8"/><rect x="1" y="3" width="22" height="5"/><line x1="10" y1="12" x2="14" y2="12"/></svg>
        Kompress Semua
      </button>
      <?php endif; ?>
    </div>

    <!-- Body (tab files) -->
    <div class="media-body" id="mediaBody">
      <?php if ($canCreate): ?>
      <div class="media-upload-zone" id="uploadZone"
           onclick="document.getElementById('fileUploadInput').click()"
           ondragover="event.preventDefault();this.classList.add('active')"
           ondragleave="this.classList.remove('active')"
           ondrop="handleDrop(event)">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="36" height="36" style="color:var(--text-muted);margin:0 auto">
          <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/>
        </svg>
        <p>Klik atau seret file ke sini</p>
        <small>JPG / PNG / WebP / GIF · Maks 5MB per file · Otomatis dikonversi ke WebP</small>
      </div>
      <input type="file" id="fileUploadInput" accept="image/*" multiple style="display:none" onchange="handleFileSelect(this)">
      <?php endif; ?>

      <div class="compress-result" id="compressResult"></div>
      <div id="mediaLoading" style="text-align:center;padding:60px"><div class="spinner"></div></div>
      <div id="mediaGrid" class="media-grid" style="display:none"></div>
      <div id="mediaEmpty" class="media-empty" style="display:none">
        <div class="media-empty-icon">📂</div>
        <p>Tidak ada file di folder ini.</p>
      </div>
    </div>

    <!-- Body (tab OG preview) -->
    <div id="ogPanel">
      <!-- OG Toolbar -->
      <div class="og-toolbar">
        <label class="og-select-all-wrap">
          <input type="checkbox" id="ogSelectAll" onchange="ogToggleSelectAll(this.checked)"
                 style="accent-color:var(--accent);width:15px;height:15px;cursor:pointer">
          Pilih Semua
        </label>
        <div class="og-stat">
          Total: <strong id="ogStatTotal">0</strong>
        </div>
        <div class="og-stat">
          <span style="color:var(--success)">●</span>
          Sudah OG: <strong id="ogStatDone" style="color:var(--success)">0</strong>
        </div>
        <div class="og-stat">
          <span style="color:var(--danger)">●</span>
          Belum: <strong id="ogStatPending" style="color:var(--danger)">0</strong>
        </div>
        <?php if ($canCreate || $canEdit): ?>
        <button class="og-bulk-btn primary" onclick="ogBulkConvert(false)" id="btnOgBulkConvert">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><polygon points="5 3 19 12 5 21 5 3"/></svg>
          Konversi Belum OG
        </button>
        <button class="og-bulk-btn primary" onclick="ogBulkConvert(true)" id="btnOgBulkAll" style="opacity:.7">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><path d="M21.5 2l-19 10 8 2 2 8 9-20z"/></svg>
          Konversi Terpilih
        </button>
        <?php endif; ?>
      </div>

      <!-- OG Grid -->
      <div style="flex:1;overflow-y:auto">
        <div id="ogLoading" class="og-loading"><div class="spinner"></div></div>
        <div id="ogGrid" class="og-grid" style="display:none"></div>
        <div id="ogEmpty" class="og-empty" style="display:none">
          <div style="font-size:48px;opacity:.3">🖼️</div>
          <p>Tidak ada gambar di folder artikel.</p>
        </div>
      </div>
    </div>

    <!-- Body (Cloudflare R2) -->
    <div id="r2Panel" style="display:none;flex:1;flex-direction:column;overflow:hidden">

      <!-- Album list view -->
      <div id="r2AlbumsView" style="flex:1;display:flex;flex-direction:column;overflow:hidden">
        <?php if ($canCreate): ?>
        <div class="r2-upload-toggle">
          <button type="button" class="r2-upload-btn" id="r2UploadToggleBtn" onclick="toggleR2Dropzone()">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Upload Album Baru
            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-left:2px"><polyline points="6 9 12 15 18 9"/></svg>
          </button>
        </div>
        <div class="r2-drop-collapsible" id="r2DropCollapsible">
          <div class="r2-drop-inner">
            <div class="r2-drop-wrap" style="padding-bottom:12px">
              <div class="r2-dropzone" id="r2Dropzone">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="40" height="40" style="color:var(--text-muted);margin:0 auto">
                  <path d="M22 19a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2h5l2 3h9a2 2 0 012 2z"/>
                </svg>
                <p><strong>Seret folder foto/video ke sini</strong> — akan otomatis dikompres &amp; diupload ke R2</p>
                <small>Nama folder yang di-drop akan dipakai APA ADANYA sebagai nama album di R2. Foto → WebP (kualitas 72, maks 2048px). Video → H.264 (kalau ffmpeg tersedia di server).</small>
                <div style="margin-top:10px">
                  <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('r2FolderInput').click()">Atau klik untuk pilih folder</button>
                  <label style="display:inline-flex;align-items:center;gap:6px;margin-left:12px;font-size:12px;color:var(--text-secondary);cursor:pointer">
                    <input type="checkbox" id="r2SkipExisting" checked style="accent-color:var(--accent)">
                    Lewati file yang sudah ada di R2
                  </label>
                </div>
                <input type="file" id="r2FolderInput" webkitdirectory directory multiple style="display:none">
              </div>
            </div>
          </div>
        </div>
        <?php endif; ?>

        <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 16px 6px">
          <span style="font-size:12.5px;font-weight:600;color:var(--text-secondary)">Album di Bucket R2</span>
          <button class="btn btn-secondary btn-sm" onclick="loadR2Albums(true)">↻ Refresh</button>
        </div>
        <div style="padding:4px 16px 10px">
          <div class="media-search-wrap" style="max-width:100%">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" id="r2AlbumSearch" placeholder="Cari nama folder album..." oninput="debounceR2AlbumSearch()">
          </div>
        </div>

        <div style="flex:1;overflow-y:auto;padding:8px 16px 16px">
          <div id="r2AlbumsLoading" style="text-align:center;padding:40px"><div class="spinner"></div></div>
          <div id="r2AlbumsGrid" class="r2-album-grid" style="display:none"></div>
          <div id="r2AlbumsEmpty" class="media-empty" style="display:none">
            <div class="media-empty-icon">☁️</div>
            <p>Belum ada album di bucket R2. Seret folder foto ke atas untuk mulai upload.</p>
          </div>
        </div>
      </div>

      <!-- Album detail view (grid file dalam 1 album) -->
      <div id="r2AlbumDetailView" style="display:none;flex:1;flex-direction:column;overflow:hidden">
        <div style="display:flex;align-items:center;gap:10px;padding:12px 16px;border-bottom:1px solid var(--border);background:var(--bg-card)">
          <button class="btn btn-secondary btn-sm" onclick="backToR2Albums()">← Kembali</button>
          <strong id="r2AlbumDetailTitle" style="font-size:13.5px"></strong>
          <?php if ($canDelete): ?>
          <button class="btn btn-danger btn-sm" style="margin-left:auto" onclick="deleteR2Album()">Hapus Album Ini</button>
          <?php endif; ?>
        </div>

        <?php if ($canCreate): ?>
        <div class="r2-upload-toggle">
          <button type="button" class="r2-upload-btn" id="r2AddFileToggleBtn" onclick="toggleR2AddDropzone()">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Tambah Foto / Video
            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-left:2px"><polyline points="6 9 12 15 18 9"/></svg>
          </button>
        </div>
        <div class="r2-drop-collapsible" id="r2AddDropCollapsible">
          <div class="r2-drop-inner">
            <div class="r2-drop-wrap" style="padding:0 0 12px">
              <div class="r2-dropzone" id="r2AlbumAddDropzone" style="padding:16px">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="26" height="26" style="color:var(--text-muted);margin:0 auto">
                  <path d="M22 19a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2h5l2 3h9a2 2 0 012 2z"/>
                </svg>
                <p style="margin:6px 0 2px"><strong>Seret foto/video ke sini</strong> untuk ditambahkan ke album ini</p>
                <small>Aturan sama seperti upload album baru: Foto → WebP (kualitas 72, maks 2048px), Video → dikompres via GitHub Actions/ffmpeg.</small>
                <div style="margin-top:8px">
                  <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('r2AlbumAddInput').click()">Atau klik untuk pilih file</button>
                </div>
                <input type="file" id="r2AlbumAddInput" accept="image/*,video/*" multiple style="display:none">
              </div>
            </div>
          </div>
        </div>
        <?php endif; ?>

        <div style="flex:1;overflow-y:auto;padding:14px 16px">
          <div id="r2FilesLoading" style="text-align:center;padding:40px"><div class="spinner"></div></div>
          <div id="r2FilesGrid" class="media-grid" style="display:none"></div>
          <div id="r2FilesEmpty" class="media-empty" style="display:none">
            <div class="media-empty-icon">📂</div><p>Album ini kosong.</p>
          </div>
        </div>
      </div>

      <!-- Progress & hasil upload — di level r2Panel supaya tetap terlihat baik
           saat berada di daftar album MAUPUN saat sedang membuka 1 album -->
      <?php if ($canCreate): ?>
      <div style="padding:0 16px">
        <div class="r2-progress-box" id="r2ProgressBox" style="display:none">
          <div style="display:flex;justify-content:space-between;align-items:center;font-size:12.5px;margin-bottom:6px">
            <span id="r2ProgressLabel" style="font-weight:600;color:var(--text-primary)">Mengupload...</span>
            <span id="r2ProgressCount" style="font-weight:600;color:var(--accent)">0 / 0</span>
          </div>
          <div class="r2-progress-track"><div class="r2-progress-bar" id="r2ProgressBar" style="width:0%"></div></div>
          <div id="r2ProgressDetail" style="display:flex;justify-content:space-between;font-size:11.5px;color:var(--text-muted);margin-top:6px;gap:8px">
            <span id="r2ProgressFileName" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:65%"></span>
            <span id="r2ProgressStats" style="white-space:nowrap"></span>
          </div>
        </div>
        <div class="compress-result" id="r2Result"></div>
      </div>
      <?php endif; ?>
    </div>

  </div><!-- /.media-main -->
</div><!-- /.media-layout -->

<!-- ── Modal Preview / Detail ─────────────────────────────────────── -->
<div class="modal-overlay" id="previewModal">
  <div class="modal" style="max-width:560px">
    <div class="modal-header">
      <span class="modal-title" id="previewTitle">Detail File</span>
      <button class="modal-close">&times;</button>
    </div>
    <div class="modal-body" style="padding:0">
      <div style="background:var(--bg-main);display:flex;align-items:center;justify-content:center;min-height:200px;max-height:340px;overflow:hidden">
        <img id="previewImg" src="" alt="" style="max-width:100%;max-height:340px;object-fit:contain;display:block">
        <div id="previewFileIcon" style="font-size:64px;display:none"></div>
      </div>
      <div style="padding:16px 20px">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;font-size:13px">
          <div><span style="color:var(--text-muted)">Nama:</span><br><strong id="detailName" style="word-break:break-all"></strong></div>
          <div><span style="color:var(--text-muted)">Ukuran:</span><br><strong id="detailSize"></strong></div>
          <div><span style="color:var(--text-muted)">URL:</span><br><code id="detailUrl" style="font-size:11px;color:var(--accent);word-break:break-all"></code></div>
          <div><span style="color:var(--text-muted)">Dimodifikasi:</span><br><strong id="detailModified"></strong></div>
        </div>
        <div id="detailCompressInfo" style="margin-top:10px;padding:8px 12px;background:rgba(60,179,113,.08);border:1px solid rgba(60,179,113,.2);border-radius:6px;font-size:12px;display:none">
          <span style="color:var(--success)">✓ Sudah dikompress</span>
          <span id="detailCompressDetail" style="color:var(--text-muted);margin-left:8px"></span>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <?php if ($canEdit): ?>
      <button class="btn btn-secondary btn-sm" id="btnPreviewRename" onclick="renameFromPreview()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
        Rename
      </button>
      <button class="btn btn-secondary btn-sm" id="btnPreviewCompress" onclick="compressFromPreview()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12"><polyline points="21 8 21 21 3 21 3 8"/><rect x="1" y="3" width="22" height="5"/></svg>
        Kompress
      </button>
      <?php endif; ?>
      <?php if ($canDelete): ?>
      <button class="btn btn-danger btn-sm" id="btnPreviewDelete" onclick="deleteFromPreview()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a1 1 0 011-1h4a1 1 0 011 1v2"/></svg>
        Hapus
      </button>
      <?php endif; ?>
      <button class="btn btn-secondary" onclick="closeModal('previewModal')">Tutup</button>
    </div>
  </div>
</div>

<!-- ── Modal Rename ───────────────────────────────────────────────── -->
<div class="modal-overlay" id="renameModal">
  <div class="modal" style="max-width:420px">
    <div class="modal-header">
      <span class="modal-title">Rename File</span>
      <button class="modal-close">&times;</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="renameOldName">
      <div class="form-group">
        <label>Nama baru <span class="required">*</span></label>
        <input type="text" class="form-control" id="renameNewName" placeholder="nama-file-baru.webp">
        <small style="color:var(--text-muted);font-size:11px;margin-top:4px;display:block">Sertakan ekstensi file (.webp, .jpg, dll)</small>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('renameModal')">Batal</button>
      <button class="btn btn-primary" onclick="submitRename()">Simpan</button>
    </div>
  </div>
</div>

<script>
const CAN_EDIT   = <?= json_encode($canEdit) ?>;
const CAN_DELETE = <?= json_encode($canDelete) ?>;
const CAN_CREATE = <?= json_encode($canCreate) ?>;

let currentFolder = '';
let allFiles      = [];
let currentFile   = null;
let viewMode      = 'grid';
let folderStats   = {};
let currentTab    = 'files';   // 'files' | 'og'
let ogAllItems    = [];        // data OG dari API

// ── Init ──────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('.media-folder-item').forEach(function(el) {
    el.addEventListener('click', function() { selectFolder(this.dataset.folder); });
  });
  loadStats();
  selectFolder('umkm');
});

// ── Load stats semua folder ───────────────────────────────────────────────
async function loadStats() {
  const res = await apiPost('/admin/api/media.php', { action: 'stats' });
  if (!res.success) return;
  folderStats = res.data;
  Object.entries(res.data).forEach(([key, info]) => {
    const el = document.getElementById('meta-' + key);
    if (el) el.textContent = info.count + ' file';
  });
}

// ── Select folder ─────────────────────────────────────────────────────────
async function selectFolder(folder) {
  currentFolder = folder;
  currentTab    = 'files';
  document.querySelectorAll('.media-folder-item').forEach(el =>
    el.classList.toggle('active', el.dataset.folder === folder)
  );

  if (folder === 'r2cloud') {
    document.getElementById('mediaSearch').value = '';
    document.getElementById('tabBar').style.display = 'none';
    document.getElementById('ogPanel').style.display = 'none';
    document.getElementById('mediaBody').style.display = 'none';
    document.getElementById('statsBar').style.display = 'none';
    document.getElementById('mainSearchWrap').style.display = 'none';
    document.getElementById('viewToggle').style.display = 'none';
    const btnUp = document.getElementById('btnUpload');
    if (btnUp) btnUp.style.display = 'none';
    document.getElementById('currentFolderLabel').textContent = 'Galeri Foto (Cloudflare R2)';
    showR2Panel();
    return;
  }

  document.getElementById('r2Panel').style.display = 'none';
  document.getElementById('mediaSearch').value = '';
  document.getElementById('compressResult').style.display = 'none';

  if (folder === 'r2galeri') {
    document.getElementById('tabBar').style.display = 'none';
    document.getElementById('ogPanel').style.display = 'none';
    document.getElementById('mediaBody').style.display = '';
    document.getElementById('statsBar').style.display = 'none';
    document.getElementById('mainSearchWrap').style.display = '';
    document.getElementById('viewToggle').style.display = '';
    const uz = document.getElementById('uploadZone');
    if (uz) uz.style.display = 'none';
    const btnUp = document.getElementById('btnUpload');
    if (btnUp) btnUp.style.display = 'none';
    document.getElementById('tabFiles').classList.add('active');
    document.getElementById('tabOG').classList.remove('active');
    await loadFiles();
    return;
  }

  // Tab bar hanya muncul di folder artikel
  const isArtikel = folder === 'artikel';
  const tabBar    = document.getElementById('tabBar');
  tabBar.style.display = isArtikel ? 'flex' : 'none';

  // Reset tab aktif
  document.getElementById('tabFiles').classList.add('active');
  document.getElementById('tabOG').classList.remove('active');
  document.getElementById('ogPanel').style.display = 'none';
  document.getElementById('mediaBody').style.display = '';
  document.getElementById('statsBar').style.display = 'none';
  document.getElementById('mainSearchWrap').style.display = '';
  document.getElementById('viewToggle').style.display = '';

  const btnUp = document.getElementById('btnUpload');
  if (btnUp) btnUp.style.display = '';

  await loadFiles();
  if (isArtikel) loadOGStats();   // muat badge OG di background
}

// ── Tab switch ────────────────────────────────────────────────────────────
function switchTab(tab) {
  currentTab = tab;
  document.getElementById('tabFiles').classList.toggle('active', tab === 'files');
  document.getElementById('tabOG').classList.toggle('active', tab === 'og');

  if (tab === 'files') {
    document.getElementById('mediaBody').style.display = '';
    document.getElementById('ogPanel').style.display   = 'none';
    document.getElementById('statsBar').style.display  = 'flex';
    document.getElementById('mainSearchWrap').style.display = '';
    document.getElementById('viewToggle').style.display = '';
  } else {
    document.getElementById('mediaBody').style.display = 'none';
    document.getElementById('ogPanel').style.display   = 'flex';
    document.getElementById('ogPanel').style.flexDirection = 'column';
    document.getElementById('ogPanel').style.flex = '1';
    document.getElementById('ogPanel').style.overflow = 'hidden';
    document.getElementById('statsBar').style.display  = 'none';
    document.getElementById('mainSearchWrap').style.display = 'none';
    document.getElementById('viewToggle').style.display = 'none';
    loadOGList();
  }
}

// ── Load OG badge stats (background, tanpa spinner) ───────────────────────
async function loadOGStats() {
  const res = await apiPost('/admin/api/media.php', { action: 'list_og' });
  if (!res.success) return;
  const pending = res.total - res.has_og;
  const badge   = document.getElementById('ogBadgePending');
  badge.textContent = pending;
  badge.className   = 'media-tab-badge' + (pending === 0 ? ' ok' : '');
}

// ── Load OG List ──────────────────────────────────────────────────────────
async function loadOGList() {
  document.getElementById('ogLoading').style.display = 'block';
  document.getElementById('ogGrid').style.display    = 'none';
  document.getElementById('ogEmpty').style.display   = 'none';

  const res = await apiPost('/admin/api/media.php', { action: 'list_og' });
  document.getElementById('ogLoading').style.display = 'none';

  if (!res.success) { toast('Error', res.error, 'error'); return; }

  ogAllItems = res.data || [];
  updateOGStats(res);
  renderOGGrid(ogAllItems);
}

function updateOGStats(res) {
  const pending = res.total - res.has_og;
  document.getElementById('ogStatTotal').textContent   = res.total;
  document.getElementById('ogStatDone').textContent    = res.has_og;
  document.getElementById('ogStatPending').textContent = pending;

  const badge = document.getElementById('ogBadgePending');
  badge.textContent = pending;
  badge.className   = 'media-tab-badge' + (pending === 0 ? ' ok' : '');
}

function renderOGGrid(items) {
  const grid = document.getElementById('ogGrid');
  if (!items.length) {
    grid.style.display = 'none';
    document.getElementById('ogEmpty').style.display = 'block';
    return;
  }
  grid.style.display = 'grid';
  document.getElementById('ogEmpty').style.display = 'none';
  document.getElementById('ogSelectAll').checked = false;

  grid.innerHTML = items.map((item, idx) => {
    const hasOG  = item.has_og;
    const ogInfo = item.og_info || {};
    return `
    <div class="og-card${hasOG ? ' has-og' : ''}" id="ogcard-${idx}" data-idx="${idx}">
      <input type="checkbox" class="og-card-check" id="ogchk-${idx}"
             onchange="ogOnCheck(${idx}, this.checked)">
      <span class="og-status-badge ${hasOG ? 'done' : 'none'}">
        ${hasOG ? '✓ OG Siap' : '✗ Belum OG'}
      </span>
      <div class="og-thumb-wrap">
        <img class="og-thumb" src="${escHtml(item.src_url)}" alt="${escHtml(item.src_name)}"
             loading="lazy" onerror="this.src='https://img.parokitulungagung.org/assets/og-preview.webp'">
      </div>
      <div class="og-card-body">
        <div class="og-card-name" title="${hasOG ? escHtml(item.og_name) : escHtml(item.src_name)}">
  ${hasOG ? escHtml(item.og_name) : escHtml(item.src_name)}
</div>
        <div class="og-card-meta">
          <span title="Sumber: ${escHtml(item.src_name)}">Sumber: ${item.src_kb} KB</span>
          ${hasOG ? `<span style="color:var(--success)">OG: ${item.og_kb} KB · 1200×630</span>` : '<span style="color:var(--text-muted)">Belum dikonversi</span>'}
          <span>${item.modified}</span>
        </div>
        ${hasOG ? `
        <div class="og-url-wrap">
          <span class="og-url-text" id="ogurl-${idx}">${escHtml(item.og_full_url)}</span>
          <button class="og-copy-btn" onclick="ogCopyUrl(${idx})" title="Salin URL">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
          </button>
        </div>` : ''}
        <div class="og-card-actions">
          ${(CAN_CREATE || CAN_EDIT) ? `
          <button class="og-action-btn convert" onclick="ogConvertOne('${escHtml(item.src_name)}', ${idx})">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><polygon points="5 3 19 12 5 21 5 3"/></svg>
            ${hasOG ? 'Konversi Ulang' : 'Konversi ke OG'}
          </button>` : ''}
          ${(hasOG && CAN_DELETE) ? `
          <button class="og-action-btn delete" onclick="ogDeleteOne('${escHtml(item.og_name)}', ${idx})" title="Hapus OG">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6"/></svg>
          </button>` : ''}
        </div>
      </div>
    </div>`;
  }).join('');
}

// ── OG: Konversi satu file ────────────────────────────────────────────────
async function ogConvertOne(name, idx) {
  if (!CAN_CREATE && !CAN_EDIT) return;
  showProgress('Mengonversi ' + name + ' → JPG 1200×630...');
  const res = await apiPost('/admin/api/media.php', { action: 'convert_og', name });
  hideProgress();
  if (res.success) {
    toast('Berhasil', `${name} → ${res.og_name} (${res.og_kb}KB)`, 'success');
    await loadOGList();
  } else {
    toast('Error', res.error, 'error');
  }
}

// ── OG: Bulk konversi ─────────────────────────────────────────────────────
async function ogBulkConvert(selectedOnly) {
  if (!CAN_CREATE && !CAN_EDIT) return;

  let names = [];
  if (selectedOnly) {
    // Ambil yang dicentang
    document.querySelectorAll('.og-card-check:checked').forEach(chk => {
      const idx = parseInt(chk.closest('.og-card').dataset.idx);
      if (ogAllItems[idx]) names.push(ogAllItems[idx].src_name);
    });
    if (!names.length) { toast('Info', 'Pilih setidaknya satu gambar', 'warning'); return; }
  }

  const label = selectedOnly
    ? `${names.length} file terpilih`
    : 'semua file yang belum punya OG';

  confirmDialog(
    'Konversi OG?',
    `${label} akan dikonversi ke JPG 1200×630 dan diunggah ke Cloudflare R2 (ogpreview/). Proses mungkin memakan beberapa detik.`,
    async () => {
      showProgress('Mengonversi... Harap tunggu.');
      const payload = { action: 'bulk_convert_og' };
      if (selectedOnly) payload.names = names;
      const res = await apiPost('/admin/api/media.php', payload);
      hideProgress();
      if (res.success) {
        const r = res.results;
        toast('Selesai', `${r.converted} dikonversi, ${r.skipped} dilewati, ${r.failed} gagal`, 'success');
        await loadOGList();
      } else {
        toast('Error', res.error, 'error');
      }
    }
  );
}

// ── OG: Hapus satu OG ────────────────────────────────────────────────────
async function ogDeleteOne(ogName, idx) {
  if (!CAN_DELETE) return;
  confirmDialog('Hapus OG?', `File "${ogName}" di Cloudflare R2 (ogpreview/) akan dihapus. Gambar asli tidak terpengaruh.`, async () => {
    const res = await apiPost('/admin/api/media.php', { action: 'delete_og', og_name: ogName });
    if (res.success) {
      toast('Berhasil', 'File OG dihapus', 'success');
      await loadOGList();
    } else {
      toast('Error', res.error, 'error');
    }
  });
}

// ── OG: Salin URL ────────────────────────────────────────────────────────
async function ogCopyUrl(idx) {
  const url = document.getElementById('ogurl-' + idx)?.textContent;
  if (!url) return;
  try { await navigator.clipboard.writeText(url); }
  catch(e) {
    const t = document.createElement('textarea'); t.value = url;
    t.style.cssText = 'position:fixed;opacity:0'; document.body.appendChild(t);
    t.select(); document.execCommand('copy'); document.body.removeChild(t);
  }
  toast('Disalin', 'URL OG berhasil disalin', 'success');
}

// ── OG: Select / deselect ────────────────────────────────────────────────
function ogOnCheck(idx, checked) {
  const card = document.getElementById('ogcard-' + idx);
  if (card) card.classList.toggle('selected', checked);
}
function ogToggleSelectAll(checked) {
  document.querySelectorAll('.og-card-check').forEach(chk => {
    chk.checked = checked;
    const card = chk.closest('.og-card');
    if (card) card.classList.toggle('selected', checked);
  });
}

// ════════════════════════════════════════════════════════════════════════════
// MEDIA (FILES TAB) — fungsi yang sudah ada
// ════════════════════════════════════════════════════════════════════════════

async function loadFiles() {
  document.getElementById('mediaLoading').style.display = 'block';
  document.getElementById('mediaGrid').style.display    = 'none';
  document.getElementById('mediaEmpty').style.display   = 'none';

  // "Thumbnail" (galeri) — dibaca dari R2 (_thumbnails/galeri/), bukan lagi
  // dari folder lokal /public/galeri yang sudah tidak dipakai.
  if (currentFolder === 'r2galeri') {
    const res = await apiPost('/admin/api/list_galeri_media.php', {});
    document.getElementById('mediaLoading').style.display = 'none';
    if (!res.success) { toast('Error', res.error || 'Gagal memuat thumbnail dari R2', 'error'); return; }

    allFiles = (res.files || []).map(f => {
      const nm = f.filename.split('/').pop();
      return {
        name: nm,
        ext: (nm.split('.').pop() || '').toLowerCase(),
        is_image: true,
        size_kb: f.size_kb,
        url: f.url,
        modified: f.mtime ? new Date(f.mtime * 1000).toLocaleString('id-ID') : '-',
        compressed: false,
        compress_info: null,
        r2key: f.filename, // key R2 penuh, dipakai untuk hapus
      };
    });

    document.getElementById('currentFolderLabel').textContent = 'Thumbnail (Cloudflare R2)';
    const btnUp = document.getElementById('btnUpload');
    if (btnUp) btnUp.style.display = 'none';
    const btnBC = document.getElementById('btnBulkCompress');
    if (btnBC) btnBC.style.display = 'none';

    updateStats(allFiles, { skip: true });
    const metaEl = document.getElementById('meta-r2galeri');
    if (metaEl) metaEl.textContent = allFiles.length + ' file';
    applyFilter();
    return;
  }

  const res = await apiPost('/admin/api/media.php', { action: 'list', folder: currentFolder });
  document.getElementById('mediaLoading').style.display = 'none';
  if (!res.success) { toast('Error', res.error, 'error'); return; }

  allFiles = res.data || [];
  const cfg = res.folder || {};

  document.getElementById('currentFolderLabel').textContent = cfg.label || currentFolder;
  document.getElementById('btnUpload') && (document.getElementById('btnUpload').style.display = '');
  const btnBC = document.getElementById('btnBulkCompress');
  if (btnBC) btnBC.style.display = cfg.skip ? 'none' : '';

  updateStats(allFiles, cfg);
  applyFilter();
}

function updateStats(files, cfg) {
  const bar      = document.getElementById('statsBar');
  bar.style.display = 'flex';
  const images     = files.filter(f => f.is_image);
  const compressed = files.filter(f => f.compressed);
  const totalKb    = files.reduce((s,f) => s + f.size_kb, 0);
  document.getElementById('statTotal').textContent      = files.length;
  document.getElementById('statSize').textContent       = totalKb.toFixed(1);
  document.getElementById('statCompressed').textContent = compressed.length + ' / ' + images.length;
}

function applyFilter() {
  const q = document.getElementById('mediaSearch').value.toLowerCase();
  const filtered = q ? allFiles.filter(f => f.name.toLowerCase().includes(q)) : allFiles;
  renderGrid(filtered);
}

function renderGrid(files) {
  const grid  = document.getElementById('mediaGrid');
  const empty = document.getElementById('mediaEmpty');
  if (!files.length) { grid.style.display='none'; empty.style.display='block'; return; }
  grid.style.display = viewMode === 'grid' ? 'grid' : 'block';
  empty.style.display = 'none';
  window._mediaFiles = files;

  const IMG_EXTS = ['jpg','jpeg','png','webp','gif'];
  grid.innerHTML = files.map((f, idx) => {
    const isImg = IMG_EXTS.includes(f.ext.toLowerCase());
    const thumb = isImg
      ? `<img class="media-thumb" src="${escHtml(f.url)}" alt="${escHtml(f.name)}" loading="lazy" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
         <div class="media-file-icon" style="display:none">🖼️</div>`
      : `<div class="media-file-icon">${getFileIcon(f.ext)}</div>`;
    return `<div class="media-card${f.compressed?' compressed':''}" onclick="openPreview(${idx})">
      <div class="media-thumb-wrap">
        ${thumb}
        ${f.compressed ? '<span class="media-card-compressed">✓</span>' : ''}
        <div class="media-card-actions">
          ${(CAN_EDIT && currentFolder !== 'r2galeri') ? `<button class="media-act-btn" style="background:rgba(201,168,76,.85);color:#1a1410" onclick="event.stopPropagation();openRename('${escHtml(f.name)}')" title="Rename">✎</button>` : ''}
          ${(CAN_EDIT && currentFolder !== 'r2galeri') ? `<button class="media-act-btn" style="background:rgba(60,179,113,.85);color:#fff" onclick="event.stopPropagation();compressFile('${escHtml(f.name)}')" title="Kompress">⚡</button>` : ''}
          ${CAN_DELETE ? `<button class="media-act-btn" style="background:rgba(224,82,82,.85);color:#fff" onclick="event.stopPropagation();deleteFile('${escHtml(f.name)}')" title="Hapus">✕</button>` : ''}
        </div>
      </div>
      <div class="media-card-info">
        <div class="media-card-name" title="${escHtml(f.name)}">${escHtml(f.name)}</div>
        <div class="media-card-meta"><span>${f.ext.toUpperCase()}</span><span>${f.size_kb} KB</span></div>
      </div>
    </div>`;
  }).join('');

  initPagination('mediaGrid', 40, ':scope > .media-card');
}

function getFileIcon(ext) {
  const map = { jpg:'🖼️', jpeg:'🖼️', png:'🖼️', webp:'🖼️', gif:'🎞️', pdf:'📄', svg:'🔷' };
  return map[ext.toLowerCase()] || '📎';
}

function setView(mode) {
  viewMode = mode;
  document.getElementById('viewGrid').classList.toggle('active', mode==='grid');
  document.getElementById('viewList').classList.toggle('active', mode==='list');
  const body = document.getElementById('mediaBody');
  body.classList.toggle('media-list-view', mode==='list');
  applyFilter();
}

function toggleUpload() {
  const z = document.getElementById('uploadZone');
  z.style.display = z.style.display === 'none' ? 'block' : 'none';
}
function handleDrop(e) {
  e.preventDefault(); document.getElementById('uploadZone').classList.remove('active');
  const files = Array.from(e.dataTransfer.files).filter(f => f.type.startsWith('image/'));
  if (files.length) uploadFiles(files);
}
function handleFileSelect(input) { if (input.files.length) uploadFiles(Array.from(input.files)); input.value=''; }

async function uploadFiles(files) {
  showProgress('Mengupload ' + files.length + ' file...');
  let ok = 0, fail = 0;
  for (const file of files) {
    const fd = new FormData(); fd.append('file', file); fd.append('folder', currentFolder);
    try {
      const res = await fetch('/admin/api/upload_media.php', { method:'POST', body:fd });
      const d   = await res.json();
      if (d.success) ok++; else fail++;
    } catch(e) { fail++; }
  }
  hideProgress();
  if (ok > 0)   toast('Berhasil', `${ok} file berhasil diupload`, 'success');
  if (fail > 0) toast('Sebagian Gagal', `${fail} file gagal diupload`, 'error');
  await loadFiles(); loadStats();
  if (currentFolder === 'artikel') loadOGStats();
}

async function compressFile(name) {
  if (!CAN_EDIT || currentFolder === 'r2galeri') return;
  showProgress('Mengkompress ' + name + '...');
  const res = await apiPost('/admin/api/media.php', { action:'compress', folder:currentFolder, name });
  hideProgress();
  if (res.success) {
    toast('Berhasil', `${name} → ${res.new_name} (${res.orig_kb}KB → ${res.new_kb}KB, -${res.saved_pct}%)`, 'success');
    await loadFiles();
  } else toast('Error', res.error, 'error');
}

async function bulkCompress() {
  if (!CAN_EDIT) return;
  const pending = allFiles.filter(f => f.is_image && !f.compressed).length;
  if (!pending) { toast('Info', 'Semua file sudah dikompress', 'success'); return; }
  confirmDialog('Kompress Semua?',
    `${pending} file akan dikompress ke WebP. File yang sudah dikompress akan dilewati.`,
    async () => {
      showProgress('Mengkompress semua file...');
      const res = await apiPost('/admin/api/media.php', { action:'bulk_compress', folder:currentFolder });
      hideProgress();
      if (res.success) {
        const r = res.results || {};
        const box = document.getElementById('compressResult');
        box.style.display = 'block';
        box.innerHTML = `
          <div style="font-weight:600;font-size:13.5px;margin-bottom:10px;color:var(--text-primary)">✅ Bulk Kompress Selesai</div>
          <div class="compress-result-row">
            <div class="compress-stat-box"><div class="compress-stat-num" style="color:var(--success)">${r.compressed||0}</div><div class="compress-stat-label">Dikompress</div></div>
            <div class="compress-stat-box"><div class="compress-stat-num" style="color:var(--text-muted)">${r.skipped||0}</div><div class="compress-stat-label">Dilewati</div></div>
            <div class="compress-stat-box"><div class="compress-stat-num" style="color:var(--danger)">${r.failed||0}</div><div class="compress-stat-label">Gagal</div></div>
            <div class="compress-stat-box"><div class="compress-stat-num" style="color:var(--accent)">${r.saved_kb||0}</div><div class="compress-stat-label">KB Hemat</div></div>
          </div>`;
        toast('Selesai', `${r.compressed} file dikompress, hemat ${r.saved_kb} KB`, 'success');
        await loadFiles(); loadStats();
      } else toast('Error', res.error, 'error');
    }
  );
}

function openPreview(idx) {
  const file = window._mediaFiles ? window._mediaFiles[idx] : null;
  if (!file) return;
  currentFile = file;
  const IMG_EXTS = ['jpg','jpeg','png','webp','gif'];
  const isImg = IMG_EXTS.includes(file.ext.toLowerCase());
  document.getElementById('previewImg').style.display      = isImg ? 'block' : 'none';
  document.getElementById('previewFileIcon').style.display = isImg ? 'none' : 'flex';
  if (isImg) document.getElementById('previewImg').src = file.url + '?t=' + Date.now();
  else document.getElementById('previewFileIcon').textContent = getFileIcon(file.ext);
  document.getElementById('previewTitle').textContent   = file.name;
  document.getElementById('detailName').textContent     = file.name;
  document.getElementById('detailSize').textContent     = file.size_kb + ' KB';
  document.getElementById('detailUrl').textContent      = file.url;
  document.getElementById('detailModified').textContent = file.modified;
  const cInfo = document.getElementById('detailCompressInfo');
  if (file.compressed && file.compress_info) {
    cInfo.style.display = 'flex';
    document.getElementById('detailCompressDetail').textContent =
      `${file.compress_info.orig_kb}KB → ${file.compress_info.new_kb}KB · ${file.compress_info.at}`;
  } else { cInfo.style.display = 'none'; }
  const isR2Galeri = currentFolder === 'r2galeri';
  const btnCmp = document.getElementById('btnPreviewCompress');
  if (btnCmp) btnCmp.style.display = (isR2Galeri || file.compressed || folderStats[currentFolder]?.skip) ? 'none' : '';
  const btnRen = document.getElementById('btnPreviewRename');
  if (btnRen) btnRen.style.display = isR2Galeri ? 'none' : '';
  openModal('previewModal');
}

function renameFromPreview()   { closeModal('previewModal'); if (currentFile) openRename(currentFile.name); }
function compressFromPreview() { closeModal('previewModal'); if (currentFile) compressFile(currentFile.name); }
function deleteFromPreview()   { closeModal('previewModal'); if (currentFile) deleteFile(currentFile.name); }

function openRename(name) {
  document.getElementById('renameOldName').value = name;
  document.getElementById('renameNewName').value = name;
  openModal('renameModal');
  setTimeout(() => { const el=document.getElementById('renameNewName'); el.focus(); el.select(); }, 150);
}
async function submitRename() {
  if (currentFolder === 'r2galeri') { closeModal('renameModal'); return; }
  const oldN = document.getElementById('renameOldName').value;
  const newN = document.getElementById('renameNewName').value.trim();
  if (!newN || newN === oldN) { closeModal('renameModal'); return; }
  const res = await apiPost('/admin/api/media.php', { action:'rename', folder:currentFolder, old_name:oldN, new_name:newN });
  if (res.success) { toast('Berhasil', 'File diubah namanya', 'success'); closeModal('renameModal'); await loadFiles(); }
  else toast('Error', res.error, 'error');
}

async function deleteFile(name) {
  if (currentFolder === 'r2galeri') {
    const file = allFiles.find(f => f.name === name);
    if (!file) return;
    confirmDialog('Hapus Thumbnail?',
      `"${name}" akan dihapus permanen dari R2.<br><small style="color:var(--text-muted)">Pastikan thumbnail ini sudah tidak dipakai oleh entri galeri manapun.</small>`,
      async () => {
        const res = await apiPost('/admin/api/r2_manager.php', { action: 'delete_file', key: file.r2key });
        if (res.success) { toast('Berhasil', 'Thumbnail dihapus dari R2', 'success'); await loadFiles(); }
        else toast('Error', res.error, 'error');
      }
    );
    return;
  }
  confirmDialog('Hapus File?', `"${name}" akan dihapus permanen.`, async () => {
    const res = await apiPost('/admin/api/media.php', { action:'delete', folder:currentFolder, name });
    if (res.success) { toast('Berhasil', 'File dihapus', 'success'); await loadFiles(); loadStats(); }
    else toast('Error', res.error, 'error');
  });
}

function showProgress(text) {
  document.getElementById('progressText').textContent = text || 'Memproses...';
  document.getElementById('progressOverlay').classList.add('show');
}
function hideProgress() { document.getElementById('progressOverlay').classList.remove('show'); }

// ════════════════════════════════════════════════════════════════════════════
// ── Upload area toggle (collapsed by default) ──────────────────────────────
function toggleR2Dropzone() {
  const col = document.getElementById('r2DropCollapsible');
  const btn = document.getElementById('r2UploadToggleBtn');
  if (!col) return;
  const opening = !col.classList.contains('open');
  col.classList.toggle('open', opening);
  if (btn) btn.classList.toggle('open', opening);
}

function toggleR2AddDropzone() {
  const col = document.getElementById('r2AddDropCollapsible');
  const btn = document.getElementById('r2AddFileToggleBtn');
  if (!col) return;
  const opening = !col.classList.contains('open');
  col.classList.toggle('open', opening);
  if (btn) btn.classList.toggle('open', opening);
}

// Auto-collapse add-dropzone saat balik ke daftar album
function collapseR2AddDropzone() {
  const col = document.getElementById('r2AddDropCollapsible');
  const btn = document.getElementById('r2AddFileToggleBtn');
  if (col) col.classList.remove('open');
  if (btn) btn.classList.remove('open');
}

// CLOUDFLARE R2 — upload folder (drag & drop) + kelola album
// ════════════════════════════════════════════════════════════════════════════

const R2_MEDIA_EXT_RE = /\.(jpe?g|png|gif|webp|bmp|heic|heif|avif|tiff?|mp4|mov|avi|mkv|wmv|3gp|3g2|m4v|mpg|mpeg|mts|m2ts|asf)$/i;

let r2CurrentAlbum = null;
let r2HideTimer     = null; // handle setTimeout hideR2Progress, supaya bisa dibatalkan kalau box mau ditampilkan lagi

document.addEventListener('DOMContentLoaded', function () {
  initR2DragDrop();
  loadR2Albums();     // isi angka "meta-r2cloud" di sidebar sejak awal, tanpa pindah panel
  loadR2GaleriMeta();  // isi angka "meta-r2galeri" di sidebar sejak awal
});

// ── "Thumbnail" (galeri) — cuma isi angka meta sidebar, tanpa pindah panel.
//    Data lengkapnya baru diambil oleh loadFiles() saat folder ini dipilih.
async function loadR2GaleriMeta() {
  const res = await apiPost('/admin/api/list_galeri_media.php', {});
  if (!res.success) return;
  const metaEl = document.getElementById('meta-r2galeri');
  if (metaEl) metaEl.textContent = (res.files || []).length + ' file';
}

function initR2DragDrop() {
  const dz = document.getElementById('r2Dropzone');
  const input = document.getElementById('r2FolderInput');
  if (dz && input) {
    // Auto-buka panel saat user menyeret file masuk ke area r2AlbumsView
    const albumsView = document.getElementById('r2AlbumsView');
    if (albumsView) {
      albumsView.addEventListener('dragenter', function (e) {
        const col = document.getElementById('r2DropCollapsible');
        const btn = document.getElementById('r2UploadToggleBtn');
        if (col && !col.classList.contains('open')) {
          col.classList.add('open');
          if (btn) btn.classList.add('open');
        }
      });
    }
    dz.addEventListener('dragover', function (e) { e.preventDefault(); dz.classList.add('active'); });
    dz.addEventListener('dragleave', function () { dz.classList.remove('active'); });
    dz.addEventListener('drop', handleR2Drop);
    input.addEventListener('change', handleR2FolderInputChange);
  }

  // Dropzone "tambah file" di dalam 1 album yang sedang dibuka
  const addDz = document.getElementById('r2AlbumAddDropzone');
  const addInput = document.getElementById('r2AlbumAddInput');
  if (addDz && addInput) {
    addDz.addEventListener('dragover', function (e) { e.preventDefault(); addDz.classList.add('active'); });
    addDz.addEventListener('dragleave', function () { addDz.classList.remove('active'); });
    addDz.addEventListener('drop', handleR2AlbumAddDrop);
    addInput.addEventListener('change', handleR2AlbumAddInputChange);
  }
}

// ── Traversal folder hasil drag & drop (webkitGetAsEntry) ──────────────────
function readDirectoryRecursive(dirEntry, basePath) {
  return new Promise(function (resolve) {
    const reader = dirEntry.createReader();
    let allFiles = [];
    function readBatch() {
      reader.readEntries(async function (entries) {
        if (!entries.length) { resolve(allFiles); return; }
        for (const ent of entries) {
          const relPath = basePath ? basePath + '/' + ent.name : ent.name;
          if (ent.isFile) {
            try {
              const file = await new Promise(function (res, rej) { ent.file(res, rej); });
              allFiles.push({ relpath: relPath, file: file });
            } catch (e) { /* skip file yang gagal dibaca */ }
          } else if (ent.isDirectory) {
            const sub = await readDirectoryRecursive(ent, relPath);
            allFiles = allFiles.concat(sub);
          }
        }
        readBatch(); // readEntries hanya mengembalikan sebagian per panggilan, lanjut baca batch berikutnya
      }, function () { resolve(allFiles); });
    }
    readBatch();
  });
}

async function handleR2Drop(e) {
  e.preventDefault();
  document.getElementById('r2Dropzone').classList.remove('active');
  if (!CAN_CREATE) return;

  const items = e.dataTransfer.items;
  if (!items || !items.length) return;

  const entries = [];
  for (let i = 0; i < items.length; i++) {
    const entry = items[i].webkitGetAsEntry && items[i].webkitGetAsEntry();
    if (entry) entries.push(entry);
  }
  if (!entries.length) {
    toast('Info', 'Browser ini tidak mendukung drag folder. Gunakan tombol "klik untuk pilih folder".', 'warning');
    return;
  }

  const grouped = {}; // namaFolder -> [{relpath, file}]
  let hasLooseFile = false;
  showR2Progress('Membaca isi folder...', 0, 0);
  for (const entry of entries) {
    if (entry.isDirectory) {
      const files = await readDirectoryRecursive(entry, '');
      if (files.length) grouped[entry.name] = (grouped[entry.name] || []).concat(files);
    } else {
      hasLooseFile = true;
    }
  }
  const albumNames = Object.keys(grouped);
  if (!albumNames.length) {
    hideR2Progress();
    toast('Info', hasLooseFile
      ? 'Seret FOLDER (bukan file satuan) ke area ini — nama foldernya akan dipakai sebagai nama album di R2.'
      : 'Folder yang diseret kosong / tidak berisi file yang didukung.', 'warning');
    return;
  }

  for (const albumName of albumNames) {
    await runR2Upload(albumName, grouped[albumName]);
  }
  hideR2Progress();
  await loadR2Albums();
}

// ── Fallback: klik untuk pilih folder (input webkitdirectory) ──────────────
async function handleR2FolderInputChange(e) {
  const fileList = Array.from(e.target.files || []);
  e.target.value = '';
  if (!fileList.length) return;

  const grouped = {};
  for (const f of fileList) {
    const rel = f.webkitRelativePath || f.name;
    const slash = rel.indexOf('/');
    const albumName = slash !== -1 ? rel.substring(0, slash) : 'Root';
    const relpath   = slash !== -1 ? rel.substring(slash + 1) : rel;
    (grouped[albumName] = grouped[albumName] || []).push({ relpath: relpath, file: f });
  }
  for (const albumName of Object.keys(grouped)) {
    await runR2Upload(albumName, grouped[albumName]);
  }
  hideR2Progress();
  await loadR2Albums();
}

// ── Tambah foto/video ke ALBUM YANG SUDAH ADA (sedang dibuka di detail view) ─
// Backend (r2_folder_upload.php) sudah generik: cukup diberi nama folder
// (baru ATAUPUN yang sudah ada) + file, jadi cukup panggil ulang
// runR2Upload() dengan r2CurrentAlbum sebagai target — aturan kompres/
// convert-nya otomatis identik dengan upload album baru.
async function handleR2AlbumAddDrop(e) {
  e.preventDefault();
  document.getElementById('r2AlbumAddDropzone')?.classList.remove('active');
  if (!CAN_CREATE || !r2CurrentAlbum) return;

  const items = e.dataTransfer.items;
  if (!items || !items.length) return;

  const entries = [];
  for (let i = 0; i < items.length; i++) {
    const entry = items[i].webkitGetAsEntry && items[i].webkitGetAsEntry();
    if (entry) entries.push(entry);
  }
  if (!entries.length) {
    toast('Info', 'Browser ini tidak mendukung drag file. Gunakan tombol "klik untuk pilih file".', 'warning');
    return;
  }

  showR2Progress('Membaca file...', 0, 0);
  let fileEntries = [];
  for (const entry of entries) {
    if (entry.isFile) {
      try {
        const file = await new Promise(function (res, rej) { entry.file(res, rej); });
        fileEntries.push({ relpath: entry.name, file: file });
      } catch (err) { /* skip file yang gagal dibaca */ }
    } else if (entry.isDirectory) {
      // Kalau yang diseret ternyata folder (bukan file lepas), tetap didukung —
      // isinya digabung rata (flatten) ke dalam album yang sama, subfolder-nya
      // dijaga sebagai bagian dari relpath.
      const sub = await readDirectoryRecursive(entry, entry.name);
      fileEntries = fileEntries.concat(sub);
    }
  }

  if (!fileEntries.length) {
    hideR2Progress();
    toast('Info', 'Tidak ada file yang bisa dibaca dari yang diseret.', 'warning');
    return;
  }

  await runR2Upload(r2CurrentAlbum, fileEntries);
  hideR2Progress();
  await openR2Album(r2CurrentAlbum); // refresh grid file di album ini
}

async function handleR2AlbumAddInputChange(e) {
  const fileList = Array.from(e.target.files || []);
  e.target.value = '';
  if (!fileList.length || !r2CurrentAlbum) return;

  const fileEntries = fileList.map(function (f) { return { relpath: f.name, file: f }; });
  await runR2Upload(r2CurrentAlbum, fileEntries);
  hideR2Progress();
  await openR2Album(r2CurrentAlbum); // refresh grid file di album ini
}

// ── Helper upload 1 file dengan XHR + progress event live ───────────────────
function uploadOneR2File(fileEntry, folderName, skipExisting, onProgress) {
  return new Promise(function (resolve) {
    const xhr = new XMLHttpRequest();
    xhr.open('POST', '/admin/api/r2_folder_upload.php', true);

    let lastLoaded = 0;
    let lastTime = Date.now();
    let speedStr = '';

    if (xhr.upload && onProgress) {
      xhr.upload.onprogress = function (e) {
        if (e.lengthComputable && e.total > 0) {
          const now = Date.now();
          const dt = (now - lastTime) / 1000;
          if (dt >= 0.4) {
            const bytesPerSec = (e.loaded - lastLoaded) / dt;
            speedStr = (bytesPerSec / (1024 * 1024)).toFixed(1) + ' MB/s';
            lastLoaded = e.loaded;
            lastTime = now;
          }
          onProgress(e.loaded, e.total, speedStr);
        }
      };
    }

    xhr.onload = function () {
      if (xhr.status >= 200 && xhr.status < 300) {
        try {
          const data = JSON.parse(xhr.responseText);
          resolve(data);
        } catch (err) {
          resolve({ success: false, error: 'Format respons server tidak valid' });
        }
      } else {
        let errMsg = 'HTTP Error ' + xhr.status;
        try {
          const errData = JSON.parse(xhr.responseText);
          if (errData && errData.error) errMsg = errData.error;
        } catch (_) {}
        resolve({ success: false, error: errMsg });
      }
    };

    xhr.onerror = function () {
      resolve({ success: false, error: 'Koneksi jaringan terputus' });
    };

    xhr.ontimeout = function () {
      resolve({ success: false, error: 'Upload timeout (melebihi batas)' });
    };

    xhr.timeout = 600000; // 10 menit

    const fd = new FormData();
    fd.append('file', fileEntry.file);
    fd.append('folder', folderName);
    fd.append('relpath', fileEntry.relpath);
    fd.append('skip_existing', skipExisting ? '1' : '0');

    xhr.send(fd);
  });
}

// ── Upload 1 album (folder) ke R2, dengan XHR progress & antrean cerdas ────
async function runR2Upload(folderName, fileEntries) {
  const filtered = fileEntries.filter(function (fe) { return R2_MEDIA_EXT_RE.test(fe.relpath); });
  if (!filtered.length) {
    toast('Info', `Folder "${folderName}" tidak berisi file foto/video yang didukung.`, 'warning');
    return;
  }

  const skipExisting = document.getElementById('r2SkipExisting')?.checked ?? true;
  const total = filtered.length;
  let done = 0, ok = 0, fail = 0, skipped = 0, savedKb = 0;
  let dispatchedVideos = 0, githubUrl = null;
  let queuedVideos = 0;
  showR2Progress(`Mengupload album "${folderName}"...`, 0, total);

  // Pisahkan file video / file besar (>6MB) untuk diupload sekuensial (1 per 1)
  // agar bandwidth penuh dan tidak memicu timeout upload paralel
  const isVideoOrLarge = function (fe) {
    const isVid = /\.(mp4|mov|avi|mkv|wmv|3gp|m4v)$/i.test(fe.relpath);
    return isVid || (fe.file && fe.file.size > 6 * 1024 * 1024);
  };

  const largeQueue = filtered.filter(isVideoOrLarge);
  const smallQueue = filtered.filter(function (fe) { return !isVideoOrLarge(fe); });

  async function processEntry(fe) {
    const fName = fe.relpath;
    const fSize = fe.file ? fe.file.size : 0;
    const fSizeMb = (fSize / (1024 * 1024)).toFixed(1);

    updateR2FileStatus(fName, `0 MB / ${fSizeMb} MB (0%)`);

    const onProgress = function (loaded, fTotal, speed) {
      const lMb = (loaded / (1024 * 1024)).toFixed(1);
      const tMb = (fTotal / (1024 * 1024)).toFixed(1);
      const pct = fTotal > 0 ? Math.round((loaded / fTotal) * 100) : 0;
      const spd = speed ? ` • ${speed}` : '';
      updateR2FileStatus(fName, `${lMb} MB / ${tMb} MB (${pct}%)${spd}`);
    };

    let d = await uploadOneR2File(fe, folderName, skipExisting, onProgress);
    if (!d.success && d.error && (d.error.includes('jaringan') || d.error.includes('timeout'))) {
      // 1x retry otomatis jika ada gangguan jaringan sesaat
      updateR2FileStatus(fName, 'Mencoba ulang upload...');
      d = await uploadOneR2File(fe, folderName, skipExisting, onProgress);
    }

    if (d.success) {
      if (d.skipped) skipped++;
      else if (d.queued) {
        queuedVideos++;
      } else {
        ok++;
        savedKb += Math.max(0, (d.orig_kb || 0) - (d.new_kb || 0));
      }
    } else {
      fail++;
      console.warn('Upload error:', fName, d.error);
    }
    done++;
    updateR2Progress(done, total);
  }

  // 1) Proses video / file besar satu per satu
  for (const fe of largeQueue) {
    await processEntry(fe);
  }

  // 2) Proses foto kecil dengan concurrency 2
  let sIdx = 0;
  async function smallWorker() {
    while (sIdx < smallQueue.length) {
      const fe = smallQueue[sIdx++];
      await processEntry(fe);
    }
  }
  if (smallQueue.length > 0) {
    const concurrency = 2;
    await Promise.all(Array.from({ length: Math.min(concurrency, smallQueue.length) }, smallWorker));
  }

  updateR2FileStatus('', '');

  // Semua file selesai diupload. Jika ada video, dispatch 1 batch job ke GitHub Actions
  if (queuedVideos > 0) {
    showR2Progress(`Memicu kompresi GitHub Actions untuk ${queuedVideos} video...`, total, total);
    try {
      const rb = await fetch('/admin/api/r2_video_batch_dispatch.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'folder=' + encodeURIComponent(folderName)
      });
      const db = await rb.json();
      if (db.success && db.dispatched) {
        dispatchedVideos = db.count || queuedVideos;
        githubUrl = db.github_url || null;
        ok += queuedVideos;
      } else {
        ok += queuedVideos;
      }
    } catch (err) {
      fail += queuedVideos;
    }
  }

  const box = document.getElementById('r2Result');
  if (box) {
    box.style.display = 'block';
    const githubBadge = dispatchedVideos > 0 ? `
      <div style="margin-top:12px;padding:10px 14px;border-radius:8px;background:rgba(99,102,241,0.08);border:1px solid rgba(99,102,241,0.25);display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap">
        <span style="font-size:12.5px;color:var(--text-primary);display:inline-flex;align-items:center;gap:6px">🐙 <b>${dispatchedVideos} video</b> berhasil dikirim ke antrean kompresi GitHub Actions</span>
        ${githubUrl ? `<a href="${githubUrl}" target="_blank" rel="noopener" style="font-size:12px;font-weight:600;color:var(--accent);text-decoration:none;padding:4px 10px;border-radius:6px;background:rgba(99,102,241,0.15);white-space:nowrap;transition:all .15s">Lihat Antrean GitHub →</a>` : ''}
      </div>` : '';
    box.innerHTML = `
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
        <div style="font-weight:600;font-size:13.5px;color:var(--text-primary);display:flex;align-items:center;gap:6px">
          <span>☁️</span> <span>Upload "${escHtml(folderName)}" Selesai</span>
        </div>
        <button type="button" onclick="document.getElementById('r2Result').style.display='none'" style="background:var(--bg-card2);border:1px solid var(--border);color:var(--text-muted);width:26px;height:26px;border-radius:6px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;font-size:14px;line-height:1;transition:all .15s" onmouseover="this.style.color='var(--text-primary)';this.style.borderColor='var(--accent)'" onmouseout="this.style.color='var(--text-muted)';this.style.borderColor='var(--border)'" title="Tutup">✕</button>
      </div>
      <div class="compress-result-row">
        <div class="compress-stat-box"><div class="compress-stat-num" style="color:var(--success)">${ok}</div><div class="compress-stat-label">Berhasil</div></div>
        <div class="compress-stat-box"><div class="compress-stat-num" style="color:var(--text-muted)">${skipped}</div><div class="compress-stat-label">Dilewati</div></div>
        <div class="compress-stat-box"><div class="compress-stat-num" style="color:var(--danger)">${fail}</div><div class="compress-stat-label">Gagal</div></div>
        <div class="compress-stat-box"><div class="compress-stat-num" style="color:var(--accent)">${savedKb.toFixed(0)}</div><div class="compress-stat-label">KB Hemat</div></div>
      </div>${githubBadge}`;
  }
  toast(fail ? 'Selesai (ada yang gagal)' : 'Selesai', `Album "${folderName}": ${ok} berhasil, ${skipped} dilewati, ${fail} gagal${dispatchedVideos ? `, ${dispatchedVideos} video diproses via GitHub` : ''}`, fail ? 'error' : 'success');
}

function showR2Progress(label, done, total) {
  if (r2HideTimer) { clearTimeout(r2HideTimer); r2HideTimer = null; }
  const box = document.getElementById('r2ProgressBox');
  if (!box) return;
  box.style.display = 'block';
  document.getElementById('r2ProgressLabel').textContent = label;
  updateR2Progress(done, total);
}
function updateR2Progress(done, total) {
  const countEl = document.getElementById('r2ProgressCount');
  const barEl   = document.getElementById('r2ProgressBar');
  if (countEl) countEl.textContent = `${done} / ${total}`;
  if (barEl)   barEl.style.width = (total > 0 ? Math.round((done / total) * 100) : 0) + '%';
}
function updateR2FileStatus(fileName, stats) {
  const nameEl  = document.getElementById('r2ProgressFileName');
  const statsEl = document.getElementById('r2ProgressStats');
  if (nameEl)  nameEl.textContent = fileName ? `File: ${fileName}` : '';
  if (statsEl) statsEl.textContent = stats || '';
}
function hideR2Progress() {
  if (r2HideTimer) clearTimeout(r2HideTimer);
  r2HideTimer = setTimeout(function () {
    const box = document.getElementById('r2ProgressBox');
    if (box) box.style.display = 'none';
    r2HideTimer = null;
  }, 1200);
}

// ── Panel switch & daftar album ─────────────────────────────────────────────
function showR2Panel() {
  document.getElementById('r2Panel').style.display = 'flex';
  document.getElementById('r2AlbumsView').style.display = 'flex';
  document.getElementById('r2AlbumDetailView').style.display = 'none';
  loadR2Albums();
}

function humanBytesR2(bytes) {
  const units = ['B', 'KB', 'MB', 'GB'];
  let i = 0, b = bytes;
  while (b >= 1024 && i < units.length - 1) { b /= 1024; i++; }
  return b.toFixed(i === 0 ? 0 : 1) + ' ' + units[i];
}

// ── Daftar album — LAZY LOAD ─────────────────────────────────────────────
// Tahap 1: ambil SEMUA nama album (murah — cuma nama folder, tanpa
//          menghitung isinya — supaya total halaman langsung diketahui).
// Tahap 2: hitung jumlah file & ukuran (yang "berat") HANYA untuk album-
//          album yang tampil di halaman pagination yang sedang aktif.
//          Pindah halaman = fetch stats baru cuma utk halaman itu saja;
//          halaman yang sudah pernah dibuka disimpan di cache (tidak fetch
//          ulang selama panel belum di-refresh).
const R2_ALBUMS_PER_PAGE = 24;
let r2AlbumNames        = [];
let r2AlbumNamesFiltered = []; // hasil filter pencarian
let r2AlbumsPage        = 1;
let r2StatsCache        = {}; // nama album -> {file_count, total_bytes, last_modified}
let r2ForceRefresh      = false; // true sesaat setelah klik "↻ Refresh" -> bypass cache lokal server utk 1x muat
let r2AlbumSearchTimer  = null; // timer debounce untuk kotak pencarian

function debounceR2AlbumSearch() {
  clearTimeout(r2AlbumSearchTimer);
  r2AlbumSearchTimer = setTimeout(function () {
    const q = (document.getElementById('r2AlbumSearch')?.value || '').toLowerCase().trim();
    r2AlbumNamesFiltered = q
      ? r2AlbumNames.filter(function (n) { return n.toLowerCase().includes(q); })
      : r2AlbumNames.slice();
    r2AlbumsPage = 1;

    const emptyEl   = document.getElementById('r2AlbumsEmpty');
    const gridEl    = document.getElementById('r2AlbumsGrid');
    if (!r2AlbumNamesFiltered.length) {
      if (gridEl) gridEl.style.display = 'none';
      if (emptyEl) {
        emptyEl.style.display = 'block';
        const p = emptyEl.querySelector('p');
        if (p) p.textContent = q ? 'Tidak ada album yang cocok dengan pencarian "' + q + '".' : 'Belum ada album di bucket R2.';
      }
      document.querySelectorAll('.pagination[data-for="r2AlbumsGrid"]').forEach(function (el) { el.remove(); });
    } else {
      if (emptyEl) emptyEl.style.display = 'none';
      renderR2AlbumsPage();
    }
  }, 400); // 400ms delay — tidak terlalu cepat, tidak terlalu lambat
}

async function loadR2Albums(force) {
  r2ForceRefresh = !!force;

  const loadingEl = document.getElementById('r2AlbumsLoading');
  const gridEl    = document.getElementById('r2AlbumsGrid');
  const emptyEl   = document.getElementById('r2AlbumsEmpty');
  if (loadingEl) loadingEl.style.display = 'block';
  if (gridEl)    gridEl.style.display = 'none';
  if (emptyEl)   emptyEl.style.display = 'none';

  const res = await apiPost('/admin/api/r2_manager.php', { action: 'list_album_names', force: r2ForceRefresh });
  if (loadingEl) loadingEl.style.display = 'none';
  if (!res.success) { if (gridEl) toast('Error', res.error || 'Gagal memuat daftar album R2', 'error'); return; }

  r2AlbumNames = res.data || [];
  r2AlbumNamesFiltered = r2AlbumNames.slice();
  r2StatsCache = {};
  r2AlbumsPage = 1;
  // Reset kotak pencarian setiap kali album di-refresh dari server
  const searchEl = document.getElementById('r2AlbumSearch');
  if (searchEl) searchEl.value = '';

  const metaEl = document.getElementById('meta-r2cloud');
  if (metaEl) metaEl.textContent = r2AlbumNames.length + ' album';

  if (!gridEl) return; // dipanggil saat init sebelum panel pernah dibuka pun aman (elemen tetap ada di DOM)
  if (!r2AlbumNames.length) { emptyEl.style.display = 'block'; return; }

  await renderR2AlbumsPage();
}

async function renderR2AlbumsPage() {
  const gridEl = document.getElementById('r2AlbumsGrid');
  if (!gridEl) return;

  // Gunakan daftar yang sudah difilter (atau semua jika tidak ada filter)
  const source = r2AlbumNamesFiltered.length || !r2AlbumNames.length
    ? r2AlbumNamesFiltered
    : r2AlbumNames;

  const total = source.length;
  const pages = Math.max(1, Math.ceil(total / R2_ALBUMS_PER_PAGE));
  r2AlbumsPage = Math.min(Math.max(1, r2AlbumsPage), pages);

  const start     = (r2AlbumsPage - 1) * R2_ALBUMS_PER_PAGE;
  const pageNames = source.slice(start, start + R2_ALBUMS_PER_PAGE);

  // Render dulu skeleton-nya (nama sudah pasti ada) — statistik yang belum
  // ada di cache ditampilkan "Memuat…" dulu supaya halaman langsung terasa
  // responsif, baru diisi setelah stage 2 (album_stats) selesai.
  gridEl.style.display = 'grid';
  gridEl.innerHTML = pageNames.map(function (name) {
    const cached = r2StatsCache[name];
    const metaHtml = cached
      ? `<span>${cached.file_count} file</span><span>${humanBytesR2(cached.total_bytes)}</span>`
      : `<span class="r2-stat-loading">Memuat…</span>`;
    return `<div class="r2-album-card" data-album="${escHtml(name)}">
      <div class="r2-album-name">📁 ${escHtml(name)}</div>
      <div class="r2-album-meta" data-stats-for="${escHtml(name)}">${metaHtml}</div>
    </div>`;
  }).join('');
  gridEl.querySelectorAll('.r2-album-card').forEach(function (el) {
    el.addEventListener('click', function () { openR2Album(el.dataset.album); });
  });

  renderR2AlbumsPagerControls(pages);

  // Tahap 2 (lazy): hitung statistik HANYA untuk nama yang sedang tampil di
  // halaman ini dan belum ada di cache.
  const needStats = pageNames.filter(function (n) { return !r2StatsCache[n]; });
  if (!needStats.length) return;

  const requestedPage = r2AlbumsPage; // jaga-jaga kalau user keburu pindah halaman lagi
  const res = await apiPost('/admin/api/r2_manager.php', { action: 'album_stats', folders: needStats, force: r2ForceRefresh });
  r2ForceRefresh = false; // force cuma berlaku 1x muat sesaat setelah klik Refresh, bukan utk pindah halaman berikutnya
  if (!res.success) return; // biarkan tetap "Memuat…" — tidak fatal, tidak menghentikan apa pun

  (res.data || []).forEach(function (s) { r2StatsCache[s.name] = s; });
  if (r2AlbumsPage !== requestedPage) return; // sudah pindah halaman, jangan timpa DOM halaman lain

  needStats.forEach(function (name) {
    const s = r2StatsCache[name];
    if (!s) return;
    const metaEl = gridEl.querySelector('.r2-album-meta[data-stats-for="' + CSS.escape(name) + '"]');
    if (metaEl) metaEl.innerHTML = `<span>${s.file_count} file</span><span>${humanBytesR2(s.total_bytes)}</span>`;
  });
}

function renderR2AlbumsPagerControls(pages) {
  document.querySelectorAll('.pagination[data-for="r2AlbumsGrid"]').forEach(function (el) { el.remove(); });
  if (pages <= 1) return;

  const gridEl = document.getElementById('r2AlbumsGrid');
  const bar = document.createElement('div');
  bar.className   = 'pagination';
  bar.dataset.for  = 'r2AlbumsGrid';
  gridEl.after(bar);

  const mkBtn = function (label, page, active) {
    const b = document.createElement('button');
    b.type = 'button';
    b.className = 'page-btn' + (active ? ' active' : '');
    b.textContent = label;
    b.onclick = function () { r2AlbumsPage = page; renderR2AlbumsPage(); };
    return b;
  };
  if (r2AlbumsPage > 1) bar.appendChild(mkBtn('‹', r2AlbumsPage - 1));
  for (let i = 1; i <= pages; i++) {
    if (i === 1 || i === pages || Math.abs(i - r2AlbumsPage) <= 2) {
      bar.appendChild(mkBtn(i, i, i === r2AlbumsPage));
    } else if (Math.abs(i - r2AlbumsPage) === 3) {
      const sp = document.createElement('span');
      sp.className = 'page-info'; sp.textContent = '…';
      bar.appendChild(sp);
    }
  }
  if (r2AlbumsPage < pages) bar.appendChild(mkBtn('›', r2AlbumsPage + 1));

  const info = document.createElement('span');
  info.className = 'page-info';
  info.style.marginLeft = '10px';
  info.textContent = r2AlbumNames.length + ' album · hal ' + r2AlbumsPage + '/' + pages;
  bar.appendChild(info);
}

async function openR2Album(name) {
  r2CurrentAlbum = name;
  document.getElementById('r2AlbumsView').style.display = 'none';
  document.getElementById('r2AlbumDetailView').style.display = 'flex';
  document.getElementById('r2AlbumDetailTitle').textContent = name;

  const loadingEl = document.getElementById('r2FilesLoading');
  const gridEl    = document.getElementById('r2FilesGrid');
  const emptyEl   = document.getElementById('r2FilesEmpty');
  loadingEl.style.display = 'block'; gridEl.style.display = 'none'; emptyEl.style.display = 'none';

  const res = await apiPost('/admin/api/r2_manager.php', { action: 'list_files', folder: name });
  loadingEl.style.display = 'none';
  if (!res.success) { toast('Error', res.error || 'Gagal memuat isi album', 'error'); return; }

  const files = res.data || [];
  if (!files.length) { emptyEl.style.display = 'block'; return; }

  gridEl.style.display = 'grid';
  gridEl.innerHTML = files.map(function (f) {
    const thumb = f.is_image
      ? `<img class="media-thumb" src="${escHtml(f.url)}" alt="${escHtml(f.name)}" loading="lazy" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
         <div class="media-file-icon" style="display:none">🎞️</div>`
      : `<div class="media-file-icon">🎞️</div>`;
    return `<div class="media-card">
      <div class="media-thumb-wrap">
        ${thumb}
        <div class="media-card-actions">
          ${CAN_DELETE ? `<button class="media-act-btn r2-del-file" data-key="${escHtml(f.key)}" style="background:rgba(224,82,82,.85);color:#fff" title="Hapus">✕</button>` : ''}
        </div>
      </div>
      <div class="media-card-info">
        <div class="media-card-name" title="${escHtml(f.name)}">${escHtml(f.name)}</div>
        <div class="media-card-meta"><span>${f.ext.toUpperCase()}</span><span>${f.size_kb} KB</span></div>
      </div>
    </div>`;
  }).join('');

  gridEl.querySelectorAll('.r2-del-file').forEach(function (btn) {
    btn.addEventListener('click', function (ev) { ev.stopPropagation(); deleteR2File(btn.dataset.key); });
  });

  initPagination('r2FilesGrid', 40, ':scope > .media-card');
}

function backToR2Albums() {
  collapseR2AddDropzone();
  document.getElementById('r2AlbumDetailView').style.display = 'none';
  document.getElementById('r2AlbumsView').style.display = 'flex';
  loadR2Albums();
}

async function deleteR2File(key) {
  confirmDialog('Hapus File?', `File di R2:<br><code>${escHtml(key)}</code><br>akan dihapus permanen.`, async function () {
    const res = await apiPost('/admin/api/r2_manager.php', { action: 'delete_file', key: key });
    if (res.success) {
      toast('Berhasil', 'File dihapus dari R2', 'success');
      if (r2CurrentAlbum) openR2Album(r2CurrentAlbum);
    } else toast('Error', res.error, 'error');
  });
}

async function deleteR2Album() {
  if (!r2CurrentAlbum) return;
  confirmDialog('Hapus Seluruh Album?',
    `Album "<strong>${escHtml(r2CurrentAlbum)}</strong>" beserta SEMUA file di dalamnya akan dihapus permanen dari R2. Tindakan ini tidak bisa dibatalkan.`,
    async function () {
      const res = await apiPost('/admin/api/r2_manager.php', { action: 'delete_album', folder: r2CurrentAlbum });
      if (res.success) { toast('Berhasil', `Album dihapus (${res.deleted} file)`, 'success'); backToR2Albums(); }
      else toast('Error', res.error || 'Gagal menghapus album', 'error');
    }
  );
}
</script>

<?php adminFooter(); ?>