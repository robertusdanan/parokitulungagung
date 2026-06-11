<?php
/**
 * admin/pages/artikel-editor.php
 * Halaman TULIS / EDIT artikel — full page, bukan modal
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
adminBoot();

$user = requireLogin();

$isSuperadmin = $user['role'] === ROLE_SUPERADMIN;
$permsMap     = $isSuperadmin ? [] : getPermissionsMap($user);
$allowedMenus = $isSuperadmin
    ? ARTIKEL_PAGES
    : array_values(array_intersect(ARTIKEL_PAGES, array_keys($permsMap)));

if (empty($allowedMenus)) { http_response_code(403); die(renderAccessDenied()); }

$activeMenu = $_GET['menu'] ?? $allowedMenus[0];
if (!in_array($activeMenu, $allowedMenus)) $activeMenu = $allowedMenus[0];

$editId = trim($_GET['id'] ?? '');
$isEdit = $editId !== '';

$canCreate  = $isSuperadmin || in_array('create',  $permsMap[$activeMenu] ?? []);
$canPublish = $isSuperadmin || in_array('publish', $permsMap[$activeMenu] ?? []);

if ($isEdit && !($isSuperadmin || in_array('edit', $permsMap[$activeMenu] ?? []))) {
    http_response_code(403); die(renderAccessDenied());
}
if (!$isEdit && !$canCreate) {
    http_response_code(403); die(renderAccessDenied());
}

$menuLabels = [
    'berita'   => 'Liputan Berita',
    'kronik'   => 'Kronik SMDTBA',
    'historia' => 'Historia Gereja',
];
$pageTitle = $isEdit ? 'Edit Artikel' : 'Tulis Artikel Baru';

adminHeader($pageTitle, 'artikel', $user);
?>

<link href="https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.snow.min.css" rel="stylesheet">

<style>
/* ── Back breadcrumb ─────────────────────────────────────────────── */
.editor-breadcrumb {
  display:flex; align-items:center; gap:8px;
  margin-bottom:20px; font-size:13px; color:var(--text-muted);
}
.editor-breadcrumb a {
  color:var(--text-muted); text-decoration:none;
  display:flex; align-items:center; gap:5px; transition:color .15s;
}
.editor-breadcrumb a:hover { color:var(--accent); }
.editor-breadcrumb span   { color:var(--text-secondary); }

/* ── Layout ─────────────────────────────────────────────────────── */
.editor-cols { display:grid; grid-template-columns:1fr 300px; gap:20px; align-items:start; width:100%; min-width:0; }
.editor-main { display:flex; flex-direction:column; gap:16px; min-width:0; overflow:hidden; }
.ql-wrap, .ql-container, .ql-editor { max-width:100%; box-sizing:border-box; }
.ql-editor { word-break:break-word; overflow-wrap:break-word; white-space:pre-wrap; }

.editor-judul {
  width:100%; background:var(--bg-input); border:1px solid var(--border);
  border-radius:var(--radius-sm); color:var(--text-primary);
  font-family:'Playfair Display',serif; font-size:20px; font-weight:600;
  padding:12px 16px; outline:none; transition:border-color .15s,box-shadow .15s; line-height:1.4;
}
.editor-judul:focus { border-color:var(--border-focus); box-shadow:0 0 0 3px rgba(201,168,76,.08); }
.editor-judul::placeholder { color:var(--text-muted); font-weight:400; font-size:18px; }

/* ── Quill ───────────────────────────────────────────────────────── */
.ql-wrap { border:1px solid var(--border); border-radius:var(--radius-sm); overflow:hidden; background:var(--bg-input); }
.ql-toolbar.ql-snow { background:var(--bg-card2); border:none; border-bottom:1px solid var(--border); padding:8px 10px; flex-wrap:wrap; }
.ql-container.ql-snow { border:none; }
.ql-editor { min-height:420px; color:var(--text-primary); font-family:'DM Sans',sans-serif; font-size:14.5px; line-height:1.8; padding:18px; }
.ql-editor p { margin-bottom:10px; }
.ql-editor h2,.ql-editor h3 { font-family:'Playfair Display',serif; color:var(--text-primary); margin:18px 0 8px; }
.ql-editor img { max-width:100%; border-radius:6px; margin:8px 0; }
.ql-editor blockquote { border-left:3px solid var(--accent); padding:6px 16px; color:var(--text-secondary); margin:12px 0; background:rgba(201,168,76,.04); border-radius:0 4px 4px 0; }
.ql-snow .ql-stroke { stroke:var(--text-secondary); }
.ql-snow .ql-fill   { fill:var(--text-secondary); }
.ql-snow .ql-picker  { color:var(--text-secondary); }
.ql-snow.ql-toolbar button:hover .ql-stroke,
.ql-snow.ql-toolbar button.ql-active .ql-stroke { stroke:var(--accent); }
.ql-snow.ql-toolbar button:hover .ql-fill,
.ql-snow.ql-toolbar button.ql-active .ql-fill   { fill:var(--accent); }
.ql-snow .ql-picker-options { background:var(--bg-card2); border:1px solid var(--border); }
.ql-toolbar .ql-insertImage { font-size:16px; line-height:1; padding:2px 5px; border-radius:4px; background:var(--accent-dim) !important; border:1px solid rgba(201,168,76,.3) !important; }
.ql-toolbar .ql-insertImage:hover { background:rgba(201,168,76,.25) !important; }

/* ── Sidebar ─────────────────────────────────────────────────────── */
.editor-sidebar { display:flex; flex-direction:column; gap:16px; }
.sidebar-card { background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius); padding:18px; }
.sidebar-card-title { font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:.06em; color:var(--text-muted); margin-bottom:14px; display:flex; align-items:center; gap:7px; }

/* ── Thumbnail ───────────────────────────────────────────────────── */
.thumb-area { border:2px dashed var(--border); border-radius:var(--radius-sm); overflow:hidden; cursor:pointer; transition:border-color .2s,background .2s; position:relative; min-height:150px; display:flex; align-items:center; justify-content:center; }
.thumb-area:hover { border-color:var(--accent); background:rgba(201,168,76,.03); }
.thumb-area.filled { border-style:solid; border-color:var(--border); }
.thumb-area img { width:100%; display:block; max-height:170px; object-fit:cover; }
.thumb-filename { font-size:11px; color:var(--text-muted); margin-top:6px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:100%; }
.thumb-empty-zone { display:flex; flex-direction:column; align-items:center; justify-content:center; gap:9px; padding:24px 16px; cursor:pointer; border:2px dashed var(--border); border-radius:var(--radius-sm); color:var(--text-muted); transition:border-color .18s,background .18s,color .18s; }
.thumb-empty-zone span { font-size:12.5px; }
.thumb-empty-zone:hover { border-color:var(--accent); background:rgba(201,168,76,.04); color:var(--accent); }
.thumb-empty-zone svg { opacity:.4; transition:opacity .18s; }
.thumb-empty-zone:hover svg { opacity:.8; }

/* ── Tags ────────────────────────────────────────────────────────── */
#tagsContainer:focus-within { border-color:var(--border-focus); box-shadow:0 0 0 3px rgba(201,168,76,.08); }
.tag-chip { display:inline-flex; align-items:center; gap:5px; background:var(--accent-dim); border:1px solid rgba(201,168,76,.3); color:var(--accent); border-radius:20px; padding:2px 10px 2px 11px; font-size:12px; font-weight:500; line-height:1.6; white-space:nowrap; animation:chipIn .15s ease; }
@keyframes chipIn { from{opacity:0;transform:scale(.85)} to{opacity:1;transform:scale(1)} }
.tag-chip-remove { background:none; border:none; cursor:pointer; color:var(--accent); opacity:.65; padding:0; line-height:1; font-size:14px; font-weight:400; display:flex; align-items:center; transition:opacity .15s; }
.tag-chip-remove:hover { opacity:1; }

/* ── Preview modal styles ───────────────────────────────────────────*/
.preview-article { font-family:'DM Sans',sans-serif; color:#e8e0d0; max-width:800px; margin:0 auto; }
.preview-category-badge { display:inline-block;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:#c9a84c;border:1px solid rgba(201,168,76,.4);border-radius:20px;padding:3px 12px;margin-bottom:16px; }
.preview-title { font-family:'Playfair Display',serif;font-size:clamp(24px,4vw,38px);font-weight:700;line-height:1.25;color:#f0e8d8;margin:0 0 16px; }
.preview-meta { display:flex;align-items:center;gap:14px;flex-wrap:wrap;font-size:13px;color:#8a8075;margin-bottom:24px;padding-bottom:20px;border-bottom:1px solid rgba(255,255,255,.08); }
.preview-thumbnail { width:100%;border-radius:10px;margin-bottom:28px;display:block;object-fit:cover;max-height:460px; }
.preview-ringkasan { font-size:15.5px;color:#a09585;line-height:1.7;font-style:italic;border-left:3px solid #c9a84c;padding:12px 18px;margin-bottom:28px;background:rgba(201,168,76,.04);border-radius:0 6px 6px 0; }
.preview-body { font-size:15px;line-height:1.85;color:#d8cfc0; }
.preview-body p { margin-bottom:14px; }
.preview-body h2 { font-family:'Playfair Display',serif;font-size:22px;color:#f0e8d8;margin:28px 0 10px; }
.preview-body h3 { font-family:'Playfair Display',serif;font-size:18px;color:#e8deca;margin:22px 0 8px; }
.preview-body img { max-width:100%;border-radius:8px;margin:10px 0;display:block; }
.preview-body blockquote { border-left:3px solid #c9a84c;padding:8px 18px;color:#a09585;margin:16px 0;background:rgba(201,168,76,.04);border-radius:0 4px 4px 0; }
.preview-body ul,.preview-body ol { padding-left:24px;margin-bottom:14px; }
.preview-body li { margin-bottom:6px; }
.preview-tags { display:flex;flex-wrap:wrap;gap:7px;margin-top:32px;padding-top:24px;border-top:1px solid rgba(255,255,255,.08); }
.preview-tag { font-size:12px;color:#c9a84c;border:1px solid rgba(201,168,76,.3);border-radius:20px;padding:3px 12px;background:rgba(201,168,76,.06); }
.preview-notice { background:rgba(201,168,76,.07);border:1px solid rgba(201,168,76,.2);border-radius:10px;padding:14px 18px;margin-bottom:28px;display:flex;align-items:flex-start;gap:12px;font-size:13px;color:#a09585;line-height:1.6; }
.preview-notice svg { flex-shrink:0;color:#c9a84c;margin-top:1px; }
/* ── Image-row: gambar berdekatan → 1 baris (sama seperti artikel-detail) ── */
.preview-body .image-row { display:flex;gap:10px;margin:14px 0;align-items:flex-start; }
.preview-body .image-row .img-wrap { flex:1;min-width:0; }
.preview-body .image-row .img-wrap img { width:100%;height:auto;border-radius:8px;margin:0;display:block; }
.preview-body .image-row-caption { font-size:12.5px;color:#7a7060;text-align:center;margin-top:6px;font-style:italic; }
.preview-body .img-caption-hidden { display:none; }
@media (min-width:601px) { .fab-save-bar { display:none !important; } }

/* ════════════════════════════════════════════════════════════════════
   SAVE LOADING OVERLAY — Elegant multi-step
   ════════════════════════════════════════════════════════════════════ */
.save-overlay {
  display: none;
  position: fixed;
  inset: 0;
  z-index: 9000;
  background: rgba(10, 8, 6, 0.82);
  backdrop-filter: blur(12px) saturate(1.2);
  -webkit-backdrop-filter: blur(12px) saturate(1.2);
  align-items: center;
  justify-content: center;
  animation: overlayFadeIn .25s ease;
}
.save-overlay.show { display: flex; }

@keyframes overlayFadeIn {
  from { opacity: 0; }
  to   { opacity: 1; }
}

.save-dialog {
  background: var(--bg-card);
  border: 1px solid rgba(201,168,76,.25);
  border-radius: 20px;
  padding: 40px 44px;
  width: 360px;
  max-width: calc(100vw - 32px);
  text-align: center;
  box-shadow:
    0 0 0 1px rgba(201,168,76,.08),
    0 32px 80px rgba(0,0,0,.6),
    0 0 60px rgba(201,168,76,.06);
  animation: dialogSlideUp .3s cubic-bezier(.34,1.56,.64,1);
  position: relative;
  overflow: hidden;
}

/* shimmer glow di atas card */
.save-dialog::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 1px;
  background: linear-gradient(90deg,
    transparent 0%,
    rgba(201,168,76,.6) 30%,
    rgba(201,168,76,.9) 50%,
    rgba(201,168,76,.6) 70%,
    transparent 100%
  );
}

@keyframes dialogSlideUp {
  from { opacity:0; transform:translateY(24px) scale(.96); }
  to   { opacity:1; transform:translateY(0) scale(1); }
}

/* ── Ikon animasi ────────────────────────────────────────────────── */
.save-icon-wrap {
  width: 64px; height: 64px;
  border-radius: 50%;
  background: var(--accent-dim);
  border: 1px solid rgba(201,168,76,.3);
  display: flex; align-items: center; justify-content: center;
  margin: 0 auto 20px;
  position: relative;
}

/* ring berputar */
.save-icon-wrap::before {
  content: '';
  position: absolute;
  inset: -4px;
  border-radius: 50%;
  border: 2px solid transparent;
  border-top-color: var(--accent);
  border-right-color: rgba(201,168,76,.4);
  animation: spinRing 1.1s linear infinite;
}
/* ring selesai — berhenti berputar */
.save-overlay.done .save-icon-wrap::before {
  animation: none;
  border-color: rgba(60,179,113,.5);
  border-top-color: #3cb371;
}
/* ring error */
.save-overlay.error .save-icon-wrap::before {
  animation: none;
  border-color: rgba(224,82,82,.5);
  border-top-color: #e05252;
}

@keyframes spinRing {
  to { transform: rotate(360deg); }
}

.save-icon-wrap svg { transition: all .3s ease; }

/* ── Teks ────────────────────────────────────────────────────────── */
.save-title {
  font-size: 16px; font-weight: 600;
  color: var(--text-primary);
  margin-bottom: 6px;
  transition: all .3s ease;
}
.save-subtitle {
  font-size: 13px; color: var(--text-muted);
  line-height: 1.5;
  transition: all .3s ease;
  min-height: 40px;
}

/* ── Progress steps ──────────────────────────────────────────────── */
.save-steps {
  margin-top: 24px;
  display: flex;
  flex-direction: column;
  gap: 10px;
  text-align: left;
}
.save-step {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 9px 12px;
  border-radius: 10px;
  border: 1px solid transparent;
  background: transparent;
  transition: all .3s ease;
  font-size: 13px;
  color: var(--text-muted);
  opacity: 0.4;
}
.save-step.active {
  opacity: 1;
  background: rgba(201,168,76,.06);
  border-color: rgba(201,168,76,.2);
  color: var(--text-secondary);
}
.save-step.done-step {
  opacity: 1;
  color: #3cb371;
  background: rgba(60,179,113,.05);
  border-color: rgba(60,179,113,.2);
}
.save-step.error-step {
  opacity: 1;
  color: #e05252;
  background: rgba(224,82,82,.05);
  border-color: rgba(224,82,82,.2);
}

/* Ikon di tiap step */
.step-icon {
  width: 22px; height: 22px;
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
  font-size: 10px;
  background: var(--bg-card2);
  border: 1px solid var(--border);
  transition: all .3s ease;
  position: relative;
}
.save-step.active .step-icon {
  background: rgba(201,168,76,.15);
  border-color: rgba(201,168,76,.4);
}
.save-step.active .step-icon::after {
  content: '';
  position: absolute;
  inset: 2px;
  border-radius: 50%;
  border: 1.5px solid transparent;
  border-top-color: var(--accent);
  animation: spinRing .8s linear infinite;
}
.save-step.done-step .step-icon {
  background: rgba(60,179,113,.15);
  border-color: rgba(60,179,113,.4);
}
.save-step.error-step .step-icon {
  background: rgba(224,82,82,.1);
  border-color: rgba(224,82,82,.4);
}

/* ── Tombol tutup (setelah done/error) ──────────────────────────── */
.save-close-btn {
  margin-top: 20px;
  width: 100%;
  padding: 10px;
  border-radius: 10px;
  border: none;
  font-size: 13.5px; font-weight: 600;
  cursor: pointer;
  transition: all .2s;
  display: none;
  font-family: 'DM Sans', sans-serif;
}
.save-overlay.done  .save-close-btn { display: block; background: rgba(60,179,113,.15); color:#3cb371; border:1px solid rgba(60,179,113,.3); }
.save-overlay.done  .save-close-btn:hover { background: rgba(60,179,113,.25); }
.save-overlay.error .save-close-btn { display: block; background: rgba(224,82,82,.1); color:#e05252; border:1px solid rgba(224,82,82,.3); }
.save-overlay.error .save-close-btn:hover { background: rgba(224,82,82,.2); }

/* ── OG info chip ────────────────────────────────────────────────── */
.og-info-chip {
  display: inline-flex; align-items: center; gap: 6px;
  margin-top: 8px; padding: 4px 12px;
  border-radius: 20px; font-size: 11.5px;
  background: rgba(82,148,224,.1); border: 1px solid rgba(82,148,224,.25);
  color: #5294e0; font-weight: 500;
  animation: chipIn .3s ease;
  display: none;
}

/* ── Responsive ──────────────────────────────────────────────────── */
/* Toolbar scroll horizontal agar tidak terpotong di layar sempit */
.ql-wrap { overflow:hidden; }
.ql-toolbar.ql-snow {
  overflow-x: auto;
  overflow-y: hidden;
  -webkit-overflow-scrolling: touch;
  scrollbar-width: none;
  white-space: nowrap;
  flex-wrap: nowrap !important;
}
.ql-toolbar.ql-snow::-webkit-scrollbar { display:none; }
.ql-toolbar.ql-snow .ql-formats {
  display: inline-flex !important;
  align-items: center;
  flex-shrink: 0;
}

@media (max-width: 860px) {
  .editor-cols { grid-template-columns:1fr; gap:0; }
  .editor-sidebar { display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-top:16px; }
  .editor-sidebar > .sidebar-card:first-child { grid-column:1/-1; }
  .editor-judul { font-size:18px; }
  .ql-editor { min-height:280px; }
}
@media (max-width: 600px) {
  .editor-breadcrumb .bc-mid { display:none; }
  .editor-breadcrumb { margin-bottom:14px; }
  .editor-judul { font-size:16px; padding:10px 13px; }
  .editor-judul::placeholder { font-size:15px; }
  .ql-editor { min-height:220px; font-size:14px; padding:14px; }
  .ql-toolbar.ql-snow { padding:6px 8px; gap:0; }
  .ql-toolbar.ql-snow .ql-formats { margin-right:4px; }
  .ql-toolbar.ql-snow button { width:26px; height:26px; padding:2px; }
  .ql-snow .ql-picker-label { padding:0 4px; }
  .editor-sidebar { grid-template-columns:1fr; margin-top:14px; padding-bottom:80px; }
  .sidebar-card.accordion .sidebar-card-body { display:none; }
  .sidebar-card.accordion.open .sidebar-card-body { display:block; }
  .sidebar-card.accordion .sidebar-card-title { cursor:pointer; margin-bottom:0; padding-bottom:0; user-select:none; justify-content:space-between; }
  .sidebar-card.accordion .sidebar-card-title::after { content:''; display:block; width:8px; height:8px; border-right:2px solid var(--text-muted); border-bottom:2px solid var(--text-muted); transform:rotate(45deg); transition:transform .2s; flex-shrink:0; margin-top:-2px; }
  .sidebar-card.accordion.open .sidebar-card-title::after { transform:rotate(-135deg); margin-top:2px; }
  .sidebar-card.accordion.open .sidebar-card-title { margin-bottom:14px; }
  .sidebar-card-save-desktop { display:none !important; }
  .fab-save-bar { display:flex !important; }
  .thumb-area { min-height:120px; }
  .thumb-area img { max-height:140px; }
  .galeri-grid { grid-template-columns:repeat(2,1fr); max-height:200px; }
  #tagsContainer { min-height:42px; }
  .save-dialog { padding:30px 24px; }
}
.fab-save-bar { display:none; }
.thumb-orient-badge{display:none;align-items:center;gap:5px;font-size:10.5px;font-weight:500;
  padding:2px 9px;border-radius:12px;margin-top:5px;width:fit-content;transition:all .2s}
.thumb-orient-badge.landscape{background:rgba(82,148,224,.1);color:#5294e0;border:1px solid rgba(82,148,224,.25)}
.thumb-orient-badge.portrait{background:rgba(113,201,76,.1);color:#5abb3c;border:1px solid rgba(113,201,76,.25)}
.thumb-orient-badge.square{background:rgba(201,168,76,.1);color:var(--accent);border:1px solid rgba(201,168,76,.25)}
.thumb-dim-tip{font-size:11px;color:var(--text-muted);margin-top:3px;line-height:1.5;display:none}
</style>

<!-- ── Save Loading Overlay ───────────────────────────────────────── -->
<div class="save-overlay" id="saveOverlay">
  <div class="save-dialog">
    <div class="save-icon-wrap" id="saveIconWrap">
      <!-- Ikon loading (default) -->
      <svg id="saveIconLoading" viewBox="0 0 24 24" fill="none" stroke="var(--accent)"
           stroke-width="1.5" width="26" height="26">
        <path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/>
        <polyline points="17 21 17 13 7 13 7 21"/>
        <polyline points="7 3 7 8 15 8"/>
      </svg>
      <!-- Ikon sukses (tersembunyi) -->
      <svg id="saveIconDone" viewBox="0 0 24 24" fill="none" stroke="#3cb371"
           stroke-width="2" width="26" height="26" style="display:none">
        <polyline points="20 6 9 17 4 12"/>
      </svg>
      <!-- Ikon error (tersembunyi) -->
      <svg id="saveIconError" viewBox="0 0 24 24" fill="none" stroke="#e05252"
           stroke-width="2" width="26" height="26" style="display:none">
        <line x1="18" y1="6" x2="6" y2="18"/>
        <line x1="6" y1="6" x2="18" y2="18"/>
      </svg>
    </div>

    <div class="save-title" id="saveTitle">Menyimpan Artikel…</div>
    <div class="save-subtitle" id="saveSubtitle">Harap tunggu, sedang memproses</div>
    <div class="og-info-chip" id="ogInfoChip">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
           width="12" height="12">
        <rect x="3" y="3" width="18" height="18" rx="2"/>
        <circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/>
      </svg>
      <span id="ogInfoText">OG preview dibuat</span>
    </div>

    <!-- Steps -->
    <div class="save-steps">
      <div class="save-step" id="step1">
        <div class="step-icon" id="step1Icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
               width="11" height="11">
            <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
            <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
          </svg>
        </div>
        Validasi data artikel
      </div>
      <div class="save-step" id="step2">
        <div class="step-icon" id="step2Icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
               width="11" height="11">
            <path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/>
          </svg>
        </div>
        Menyimpan ke database
      </div>
      <div class="save-step" id="step3">
        <div class="step-icon" id="step3Icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
               width="11" height="11">
            <rect x="3" y="3" width="18" height="18" rx="2"/>
            <circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/>
          </svg>
        </div>
        Generate OG preview WhatsApp
      </div>
      <div class="save-step" id="step4">
        <div class="step-icon" id="step4Icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
               width="11" height="11">
            <circle cx="11" cy="11" r="8"/>
            <path d="M21 21l-4.35-4.35"/>
            <path d="M11 8v6M8 11h6"/>
          </svg>
        </div>
        Generate SEO gambar (AI)
      </div>
    </div>

    <button class="save-close-btn" id="saveCloseBtn" onclick="closeSaveOverlay()">
      Kembali ke Daftar Artikel
    </button>
  </div>
</div>

<!-- ── Breadcrumb ─────────────────────────────────────────────────── -->
<div class="editor-breadcrumb">
  <a href="/admin/pages/artikel.php?menu=<?= e($activeMenu) ?>">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14">
      <polyline points="15 18 9 12 15 6"/>
    </svg>
    Kelola Artikel
  </a>
  <span class="bc-mid" style="display:contents">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12">
      <polyline points="9 18 15 12 9 6"/>
    </svg>
    <span><?= e($menuLabels[$activeMenu] ?? $activeMenu) ?></span>
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12">
      <polyline points="9 18 15 12 9 6"/>
    </svg>
  </span>
  <span><?= $isEdit ? 'Edit Artikel' : 'Tulis Baru' ?></span>
</div>

<!-- ── Hidden fields ─────────────────────────────────────────────── -->
<input type="hidden" id="fieldId"        value="<?= e($editId) ?>">
<input type="hidden" id="fieldMenu"      value="<?= e($activeMenu) ?>">
<input type="hidden" id="fieldThumbnail" value="">
<input type="hidden" id="fieldThumbnailAlt" value="">
<input type="hidden" id="fieldOgUrl"     value="">
<input type="hidden" id="fieldThumbOrient" value="">
<input type="hidden" id="fieldThumbPreset" value="">

<!-- ── Two columns ───────────────────────────────────────────────── -->
<div class="editor-cols">

  <!-- Kolom kiri: editor -->
  <div class="editor-main">

    <input type="text" id="fieldJudul" class="editor-judul"
           placeholder="Tulis judul artikel di sini…">

    <div>
      <label style="font-size:12px;font-weight:500;color:var(--text-muted);display:block;margin-bottom:6px">
        Isi Artikel
        <span style="font-weight:400;color:var(--text-muted)"> — Klik 🖼 di toolbar untuk sisipkan gambar</span>
      </label>
      <div class="ql-wrap">
        <div id="quillEditor"></div>
      </div>
    </div>

    <!-- Ringkasan -->
    <div>
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;gap:8px">
        <label style="font-size:12px;font-weight:500;color:var(--text-muted);margin:0">
          Deskripsi Singkat
          <span style="font-weight:400"> — tampil di kartu artikel</span>
        </label>
        <div style="display:flex;align-items:center;gap:8px;flex-shrink:0">
          <span id="ringkasanBadge"
                style="font-size:10.5px;color:var(--text-muted);background:var(--bg-card2);
                       border:1px solid var(--border);border-radius:10px;padding:1px 8px">
            0 / 160
          </span>
          <button type="button" id="btnRegenRingkasan" onclick="regenRingkasan()"
                  title="Isi ulang otomatis dari isi artikel"
                  style="display:flex;align-items:center;gap:4px;background:var(--bg-card2);
                         border:1px solid var(--border);border-radius:5px;color:var(--text-muted);
                         font-size:11px;padding:3px 9px;cursor:pointer;transition:all .15s;
                         font-family:'DM Sans',sans-serif;white-space:nowrap">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="11" height="11">
              <polyline points="1 4 1 10 7 10"/>
              <path d="M3.51 15a9 9 0 102.13-9.36L1 10"/>
            </svg>
            Auto-isi
          </button>
        </div>
      </div>
      <textarea id="fieldRingkasan" class="form-control" rows="3" maxlength="160"
                oninput="onRingkasanInput()"
                placeholder="Diisi otomatis dari awal isi artikel. Bisa diedit secara manual…"
                style="resize:none"></textarea>
      <div style="font-size:11px;color:var(--text-muted);margin-top:5px;line-height:1.5">
        Deskripsi ini muncul di bawah judul pada kartu/daftar artikel. Maksimal 160 karakter.
      </div>
    </div>

  </div>

  <!-- Kolom kanan: sidebar -->
  <div class="editor-sidebar">

    <!-- Tombol Simpan -->
    <div class="sidebar-card sidebar-card-save-desktop">
      <div style="display:flex;flex-direction:column;gap:8px">
        <button class="btn btn-primary" id="btnSave" onclick="submitArtikel()" style="width:100%;justify-content:center">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14">
            <path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/>
            <polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/>
          </svg>
          Simpan Artikel
        </button>
        <button class="btn btn-secondary" onclick="openPreview()" style="width:100%;justify-content:center;display:flex;align-items:center;gap:7px">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          Preview Artikel
        </button>
        <a href="/admin/pages/artikel.php?menu=<?= e($activeMenu) ?>"
           class="btn btn-secondary" style="width:100%;justify-content:center;text-decoration:none">
          Batal
        </a>
      </div>
      <div id="saveInfo" style="font-size:11.5px;color:var(--text-muted);margin-top:10px;line-height:1.5"></div>
    </div>

    <!-- Thumbnail -->
    <div class="sidebar-card accordion open" id="accordThumb">
      <div class="sidebar-card-title">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13">
          <rect x="3" y="3" width="18" height="18" rx="2"/>
          <circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/>
        </svg>
        Thumbnail / Foto Sampul
      </div>
      <div class="sidebar-card-body">
        <div id="thumbEmpty" onclick="openThumbPicker()" class="thumb-empty-zone">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="26" height="26">
            <rect x="3" y="3" width="18" height="18" rx="2"/>
            <circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/>
          </svg>
          <span>Klik untuk pilih thumbnail</span>
        </div>
        <div id="thumbFilled" style="display:none">
          <div class="thumb-area filled" id="thumbArea">
            <img id="thumbImg" src="" alt="">
          </div>
          <div class="thumb-filename" id="thumbFilename"></div>
<div id="thumbOrientBadge" class="thumb-orient-badge landscape">
  <span id="thumbOrientLabel">↔ Landscape</span>
</div>
<div class="thumb-dim-tip" id="thumbDimTip"></div>
          <div style="display:flex;gap:6px;margin-top:10px">
            <button type="button" onclick="openThumbPicker()" class="btn btn-secondary btn-sm" style="flex:1;justify-content:center">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12">
                <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
              </svg>
              Ganti
            </button>
            <button type="button" onclick="clearThumb()" class="btn btn-sm"
                    style="flex:1;justify-content:center;border:1px solid rgba(224,82,82,.35);color:var(--danger);background:rgba(224,82,82,.06)">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12">
                <polyline points="3 6 5 6 21 6"/>
                <path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a1 1 0 011-1h4a1 1 0 011 1v2"/>
              </svg>
              Hapus
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Status & Kategori -->
    <div class="sidebar-card accordion open" id="accordPublikasi">
      <div class="sidebar-card-title">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13">
          <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
        </svg>
        Publikasi
      </div>
      <div class="sidebar-card-body">
        <div class="form-group" style="margin-bottom:12px">
          <label style="font-size:12px;font-weight:500;color:var(--text-secondary);display:block;margin-bottom:5px">Status</label>
          <select class="form-select" id="fieldStatus">
            <option value="draft">◷ Draft</option>
            <?php if ($canPublish): ?>
            <option value="published">✓ Published</option>
            <?php endif; ?>
          </select>
          <?php if (!$canPublish): ?>
          <div style="font-size:11px;color:var(--text-muted);margin-top:5px;line-height:1.5">
            Artikel disimpan sebagai draft.<br>Hubungi editor untuk publish.
          </div>
          <?php endif; ?>
        </div>
        <div class="form-group" style="margin-bottom:12px">
          <label style="font-size:12px;font-weight:500;color:var(--text-secondary);display:block;margin-bottom:5px">Kategori</label>
          <select class="form-select" id="fieldMenuSelect">
            <?php foreach ($allowedMenus as $m): ?>
            <option value="<?= e($m) ?>" <?= $m === $activeMenu ? 'selected' : '' ?>>
              <?= e($menuLabels[$m] ?? $m) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label style="font-size:12px;font-weight:500;color:var(--text-secondary);display:block;margin-bottom:5px">Tags</label>
          <input type="hidden" id="fieldTags">
          <div id="tagsContainer" onclick="document.getElementById('tagsInput').focus()"
               style="display:flex;flex-wrap:wrap;align-items:center;gap:5px;min-height:38px;padding:5px 8px;cursor:text;background:var(--bg-input);border:1px solid var(--border);border-radius:var(--radius-sm);transition:border-color .15s,box-shadow .15s">
            <input type="text" id="tagsInput"
                   placeholder="Tambah tag, tekan Enter atau koma…"
                   autocomplete="off" spellcheck="false"
                   onkeydown="_tagsKeydown(event,this)"
                   onblur="_tagsBlur(this)"
                   onpaste="_tagsPaste(event,this)"
                   style="flex:1;min-width:90px;background:transparent;border:none;outline:none;color:var(--text-primary);font-size:13px;font-family:'DM Sans',sans-serif;padding:2px 2px;line-height:1.5">
          </div>
          <div style="font-size:11px;color:var(--text-muted);margin-top:5px">
            Tekan <kbd style="background:var(--bg-card2);border:1px solid var(--border);border-radius:3px;padding:0 5px;font-size:10.5px">Enter</kbd>
            atau <kbd style="background:var(--bg-card2);border:1px solid var(--border);border-radius:3px;padding:0 5px;font-size:10.5px">,</kbd> untuk menambah tag.
            <div id="tagSuggestList" style="display:flex;flex-wrap:wrap;gap:5px;margin-top:8px"></div>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>

<!-- Preview Modal -->
<div id="previewModal" style="display:none;position:fixed;inset:0;z-index:8500;background:rgba(8,6,4,.93);backdrop-filter:blur(10px);overflow-y:auto">
  <div style="max-width:860px;margin:0 auto;padding:0 0 60px">
    <div style="position:sticky;top:0;z-index:10;background:rgba(14,12,10,.97);backdrop-filter:blur(8px);border-bottom:1px solid rgba(201,168,76,.15);padding:12px 20px;display:flex;align-items:center;justify-content:space-between">
      <div style="display:flex;align-items:center;gap:10px">
        <span style="font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.08em;color:var(--accent)">Preview Artikel</span>
        <span style="font-size:11.5px;color:var(--text-muted);background:var(--bg-card2);border:1px solid var(--border);border-radius:10px;padding:2px 10px">Tampilan perkiraan di website</span>
      </div>
      <button onclick="closePreview()" style="background:none;border:1px solid rgba(255,255,255,.15);color:var(--text-secondary);border-radius:8px;padding:6px 14px;cursor:pointer;font-size:13px;display:flex;align-items:center;gap:6px;transition:all .15s" onmouseover="this.style.borderColor='var(--accent)'" onmouseout="this.style.borderColor='rgba(255,255,255,.15)'">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        Tutup
      </button>
    </div>
    <div id="previewContent" style="padding:32px 24px"></div>
  </div>
</div>


<!-- FAB Save Bar (mobile) -->
<div class="fab-save-bar" id="fabSaveBar">
  <div class="fab-status" id="fabStatus">Draft belum disimpan</div>
  <a href="/admin/pages/artikel.php?menu=<?= e($activeMenu) ?>"
     class="btn btn-secondary" style="text-decoration:none">Batal</a>
  <button class="btn btn-secondary" onclick="openPreview()" style="display:flex;align-items:center;gap:6px">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
    Preview
  </button>
  <button class="btn btn-primary" onclick="submitArtikel()">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14">
      <path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/>
      <polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/>
    </svg>
    Simpan
  </button>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.min.js"></script>
<script src="/admin/js/artikel-image-picker.js?v=<?= filemtime($_SERVER['DOCUMENT_ROOT'] . '/admin/js/artikel-image-picker.js') ?>"></script>
<script>
const EDIT_ID     = '<?= e($editId) ?>';
const ACTIVE_MENU = '<?= e($activeMenu) ?>';
const CAN_PUBLISH = <?= json_encode($canPublish) ?>;
let quill = null;

// ════════════════════════════════════════════════════════════════════
// SAVE OVERLAY — Kontrol step-by-step
// ════════════════════════════════════════════════════════════════════

const overlay = {
  el:       () => document.getElementById('saveOverlay'),
  title:    () => document.getElementById('saveTitle'),
  subtitle: () => document.getElementById('saveSubtitle'),
  step:     (n) => document.getElementById('step' + n),
  ogChip:   () => document.getElementById('ogInfoChip'),
  ogText:   () => document.getElementById('ogInfoText'),

  show() {
    const o = this.el();
    o.className = 'save-overlay show';
    // Reset semua step
    [1,2,3,4].forEach(n => {
      const s = this.step(n);
      if (s) s.className = 'save-step';
    });
    // Reset ikon
    document.getElementById('saveIconLoading').style.display = '';
    document.getElementById('saveIconDone').style.display    = 'none';
    document.getElementById('saveIconError').style.display   = 'none';
    this.title().textContent    = 'Menyimpan Artikel…';
    this.subtitle().textContent = 'Harap tunggu, sedang memproses';
    this.ogChip().style.display = 'none';
  },

  setStep(n, state = 'active') {
    // state: 'active' | 'done' | 'error'
    const s = this.step(n);
    if (!s) return;
    if (state === 'active')      s.className = 'save-step active';
    else if (state === 'done')   s.className = 'save-step done-step';
    else if (state === 'error')  s.className = 'save-step error-step';
  },

  done(ogInfo) {
    const o = this.el();
    o.classList.remove('error');
    o.classList.add('done');
    document.getElementById('saveIconLoading').style.display = 'none';
    document.getElementById('saveIconDone').style.display    = '';
    this.title().textContent    = 'Artikel Tersimpan!';
    this.subtitle().textContent = 'Berhasil disimpan' +
      (CAN_PUBLISH ? '' : ' sebagai draft');

    // Tampilkan info OG jika berhasil
    if (ogInfo && ogInfo.success) {
      this.ogChip().style.display = 'inline-flex';
      this.ogText().textContent   = 'OG preview 1200×630 dibuat otomatis';
    }

    document.getElementById('saveCloseBtn').textContent = 'Kembali ke Daftar Artikel';
  },

  error(msg) {
    const o = this.el();
    o.classList.add('error');
    document.getElementById('saveIconLoading').style.display = 'none';
    document.getElementById('saveIconError').style.display   = '';
    this.title().textContent    = 'Gagal Menyimpan';
    this.subtitle().textContent = msg || 'Terjadi kesalahan. Silakan coba lagi.';
    document.getElementById('saveCloseBtn').textContent = 'Tutup';
  },

  hide() {
    this.el().className = 'save-overlay';
  }
};

function closeSaveOverlay() {
  const o = document.getElementById('saveOverlay');
  if (o.classList.contains('done')) {
    // Redirect ke daftar artikel
    window.location.href = '/admin/pages/artikel.php?menu=' +
      document.getElementById('fieldMenuSelect').value;
  } else {
    overlay.hide();
  }
}

// ════════════════════════════════════════════════════════════════════
// QUILL INIT
// ════════════════════════════════════════════════════════════════════

document.addEventListener('DOMContentLoaded', function () {
  _checkDraftRecovery();
  _loadTagSuggestions();
  const icons = Quill.import('ui/icons');
  icons['insertImage'] = '🖼';

  quill = new Quill('#quillEditor', {
    theme: 'snow',
    placeholder: 'Tulis isi artikel di sini…',
    modules: {
      toolbar: {
        container: [
          [{ header: [2, 3, false] }],
          ['bold', 'italic', 'underline'],
          [{ list: 'ordered' }, { list: 'bullet' }],
          ['blockquote', 'link'],
          ['insertImage'],
          ['clean'],
        ],
        handlers: {
          insertImage: function () {
            ArtikelImagePicker.open({
              type: 'content',
onSelect: function (url, filename, alt) {
const range = quill.getSelection(true);
const p     = ArtikelImagePicker._lastPreset || {};

quill.insertEmbed(range.index, 'image', url);
quill.setSelection(range.index + 1);
//
if (alt || p.orient) {
setTimeout(function () {
const [leaf] = quill.getLeaf(range.index);
if (leaf && leaf.domNode && leaf.domNode.tagName === 'IMG') {
const el = leaf.domNode;
if (alt) el.setAttribute('alt', alt);
if (p.orient) el.setAttribute('data-orient', p.orient);
           // Portrait: batasi lebar agar tidak terlalu dominan di konten
if (p.orient === 'portrait') {
el.style.maxWidth = '45%';
el.style.margin   = '8px auto';
el.style.display  = 'block';
}
}
}, 50);
}
}
            });
          }
        }
      }
    }
  });

  quill.on('text-change', function () {
    if (!ringkasanDirty) {
      const val = _genRingkasan();
      document.getElementById('fieldRingkasan').value = val;
      updateRingkasanBadge(val.length);
    }
  });

  initRingkasanBadge();
  _initTagsInput();
  if (EDIT_ID) loadEditData();
  _initAccordions();
  _updateFabStatus();
  document.getElementById('fieldJudul')?.addEventListener('input', _updateFabStatus);
  document.getElementById('fieldStatus')?.addEventListener('change', _updateFabStatus);
});

// ════════════════════════════════════════════════════════════════════
// SAVE ARTIKEL — dengan overlay langkah-demi-langkah
// ════════════════════════════════════════════════════════════════════

async function submitArtikel() {
  const judul = document.getElementById('fieldJudul').value.trim();
  if (!judul) {
    toast('Validasi', 'Judul artikel wajib diisi.', 'error');
    return;
  }

  const menu = document.getElementById('fieldMenuSelect').value;
  const data = {
    judul,
    ringkasan     : document.getElementById('fieldRingkasan').value.trim(),
    konten        : quill ? quill.root.innerHTML : '',
    thumbnail     : document.getElementById('fieldThumbnail').value,
    thumbnail_alt : document.getElementById('fieldThumbnailAlt').value.trim(),
    og_url        : document.getElementById('fieldOgUrl')?.value || '',
    tags          : document.getElementById('fieldTags').value.trim(),
    status        : document.getElementById('fieldStatus').value,
  };
  if (EDIT_ID) data.id = EDIT_ID;

  // ── Tampilkan overlay & mulai step ─────────────────────────────
  overlay.show();

  // Step 1: Validasi
  overlay.setStep(1, 'active');
  await _delay(400);
  overlay.setStep(1, 'done');

  // Step 2: Simpan ke database
  overlay.setStep(2, 'active');
  let res;
  try {
    res = await apiPost('/admin/api/artikel.php', { action: 'save', menu, data });
  } catch (e) {
    overlay.setStep(2, 'error');
    overlay.setStep(3, 'error');
    overlay.setStep(4, 'error');
    overlay.error('Koneksi gagal. Periksa jaringan Anda.');
    return;
  }

  if (!res.success) {
    overlay.setStep(2, 'error');
    overlay.setStep(3, 'error');
    overlay.setStep(4, 'error');
    overlay.error(res.error || 'Gagal menyimpan artikel.');
    return;
  }
  overlay.setStep(2, 'done');

  // Step 3: OG preview (sudah dilakukan server, tinggal tampilkan hasilnya)
  overlay.setStep(3, 'active');
  await _delay(500);

  const ogResult = res.data?._og || null;
  if (ogResult && ogResult.success) {
    overlay.setStep(3, 'done');
  } else if (data.thumbnail) {
    // Ada thumbnail tapi OG gagal — bukan error fatal
    overlay.setStep(3, 'error');
  } else {
    // Tidak ada thumbnail — step 3 dilewati
    overlay.setStep(3, 'done');
  }

  // Step 4: SEO AI — hanya untuk artikel published yang punya gambar
  const isPublished = data.status === 'published';
  const savedId     = res.data?.id || EDIT_ID || null;
  const savedMenu   = menu;
  const savedKonten = data.konten || '';
  const hasImages   = /<img\s/i.test(savedKonten);

  if (isPublished && savedId && hasImages) {
    overlay.setStep(4, 'active');
    try {
      const seoRes = await fetch('/admin/api/seo-artikel.php', {
        method:  'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
          action:        'process',
          artikel_id:    savedId,
          artikel_menu:  savedMenu,
          artikel_judul: data.judul,
          artikel_tags:  data.tags || '',
          force:         false,   // hanya generate gambar yang belum ada
        }),
      });
      const seoData = await seoRes.json();
      if (seoData.ok) {
        overlay.setStep(4, 'done');
      } else {
        // SEO gagal — bukan error fatal
        overlay.setStep(4, 'error');
      }
    } catch (_e) {
      overlay.setStep(4, 'error');
    }
  } else {
    // Draft / tidak ada gambar — lewati step 4
    overlay.setStep(4, 'done');
  }

  await _delay(300);
  overlay.done(ogResult);
}

function _delay(ms) {
  return new Promise(resolve => setTimeout(resolve, ms));
}

// ════════════════════════════════════════════════════════════════════
// RINGKASAN
// ════════════════════════════════════════════════════════════════════

let ringkasanDirty = false;

function _quillToPlain() {
  if (!quill) return '';
  return quill.getText().replace(/\s+/g, ' ').trim();
}
function _genRingkasan() {
  const plain = _quillToPlain();
  if (!plain) return '';
  if (plain.length <= 160) return plain;
  const cut = plain.substring(0, 160);
  const lastSpace = cut.lastIndexOf(' ');
  return (lastSpace > 100 ? cut.substring(0, lastSpace) : cut).trimEnd();
}
function regenRingkasan() {
  const val = _genRingkasan();
  const el  = document.getElementById('fieldRingkasan');
  el.value  = val;
  ringkasanDirty = false;
  updateRingkasanBadge(val.length);
  el.style.borderColor = 'var(--accent)';
  setTimeout(() => el.style.borderColor = '', 800);
}
function onRingkasanInput() {
  ringkasanDirty = true;
  updateRingkasanBadge(document.getElementById('fieldRingkasan').value.length);
}
function updateRingkasanBadge(len) {
  const badge = document.getElementById('ringkasanBadge');
  if (!badge) return;
  badge.textContent = len + ' / 160';
  badge.style.color = len >= 160 ? 'var(--warning)' : len >= 130 ? 'var(--accent)' : 'var(--text-muted)';
}
function initRingkasanBadge() {
  const val = document.getElementById('fieldRingkasan').value;
  updateRingkasanBadge(val.length);
  if (val) ringkasanDirty = true;
}

// ════════════════════════════════════════════════════════════════════
// LOAD EDIT DATA
// ════════════════════════════════════════════════════════════════════

async function loadEditData() {
  document.getElementById('saveInfo').textContent = 'Memuat artikel…';
  try {
    const res = await apiPost('/admin/api/artikel.php', { action: 'get', menu: ACTIVE_MENU, id: EDIT_ID });
    if (!res.success) throw new Error(res.error);
    const art = res.data;
    document.getElementById('fieldJudul').value      = art.judul     || '';
    document.getElementById('fieldRingkasan').value  = art.ringkasan || '';
    if (art.ringkasan) ringkasanDirty = true;
    updateRingkasanBadge((art.ringkasan || '').length);
    _setTagsFromString(art.tags || '');
    document.getElementById('fieldStatus').value     = art.status    || 'draft';
    document.getElementById('fieldMenuSelect').value = art.menu      || ACTIVE_MENU;
    document.getElementById('saveInfo').textContent  = 'Penulis: ' + (art.penulis || '—');
    if (art.konten && quill) quill.clipboard.dangerouslyPasteHTML(art.konten);
    if (art.thumbnail) setThumb(art.thumbnail, art.thumbnail.split('/').pop(), art.thumbnail_alt || '', '');
  } catch (e) {
    document.getElementById('saveInfo').textContent = '';
    toast('Error', e.message, 'error');
  }
}

// ════════════════════════════════════════════════════════════════════
// TAGS
// ════════════════════════════════════════════════════════════════════

function _esc(s) {
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
let _tags = [];
function _renderChips() {
  const container = document.getElementById('tagsContainer');
  const input     = document.getElementById('tagsInput');
  if (!container || !input) return;
  container.querySelectorAll('.tag-chip').forEach(el => el.remove());
  _tags.forEach(function(tag, i) {
    const chip = document.createElement('span');
    chip.className = 'tag-chip';
    chip.innerHTML = _esc(tag) + '<button type="button" class="tag-chip-remove" onclick="removeTag(' + i + ')" title="Hapus">×</button>';
    container.insertBefore(chip, input);
  });
  document.getElementById('fieldTags').value = _tags.join(', ');
  input.placeholder = _tags.length ? '' : 'Tambah tag, tekan Enter atau koma…';
}
function _addTag(raw) {
  const tag = raw.trim().replace(/,+$/, '').trim();
  if (!tag || _tags.indexOf(tag) !== -1) return;
  _tags.push(tag); _renderChips();
}
function removeTag(i) { _tags.splice(i, 1); _renderChips(); }
function _tagsKeydown(e, input) {
  if (e.key === 'Enter' || e.key === ',') { e.preventDefault(); _addTag(input.value); input.value = ''; }
  else if (e.key === 'Backspace' && input.value === '' && _tags.length) { _tags.pop(); _renderChips(); }
}
function _tagsBlur(input) { if (input.value.trim()) { _addTag(input.value); input.value = ''; } }
function _tagsPaste(e, input) {
  e.preventDefault();
  const text = (e.clipboardData || window.clipboardData).getData('text');
  text.split(/[,\n]+/).forEach(function(t) { _addTag(t); });
  input.value = '';
}
function _initTagsInput() {}
function _setTagsFromString(str) {
  _tags = str ? str.split(',').map(function(t){ return t.trim(); }).filter(Boolean) : [];
  _renderChips();
}

// ════════════════════════════════════════════════════════════════════
// THUMBNAIL
// ════════════════════════════════════════════════════════════════════

function openThumbPicker() {
  const judul = (document.getElementById('fieldJudul')?.value || '').trim();
  ArtikelImagePicker.open({
    type    : 'thumbnail',
    judul   : judul,
    onSelect: function(url, filename, alt, ogUrl) {
      const p = ArtikelImagePicker._lastPreset || {};
      setThumb(url, filename, alt, ogUrl, p.orient || 'landscape', p.key || '', p.label || '', p.w || 0, p.h || 0);
    }
  });
}
function setThumb(url, filename, alt, ogUrl, orientation, presetKey, presetLabel, dimW, dimH) {
  orientation = orientation || 'landscape';
 
  document.getElementById('fieldThumbnail').value     = url;
  document.getElementById('fieldThumbnailAlt').value  = alt || '';
  document.getElementById('fieldOgUrl').value         = ogUrl || '';
  document.getElementById('fieldThumbOrient').value   = orientation;
  document.getElementById('fieldThumbPreset').value   = presetKey || '';
 
  const img = document.getElementById('thumbImg');
  img.src = url; img.alt = alt || '';
  // Portrait: object-fit contain agar gambar tidak terpotong di preview sidebar
  img.style.objectFit = orientation === 'portrait' ? 'contain' : 'cover';
 
  document.getElementById('thumbEmpty').style.display  = 'none';
  document.getElementById('thumbFilled').style.display = '';
  document.getElementById('thumbFilename').textContent = filename || url.split('/').pop();
 
  // Badge orientasi
  const badge = document.getElementById('thumbOrientBadge');
  const lbl   = document.getElementById('thumbOrientLabel');
  if (badge && lbl) {
    badge.style.display = 'flex';
    badge.className     = 'thumb-orient-badge ' + orientation;
    const icons = { landscape:'↔', portrait:'↕', square:'⬛' };
    lbl.textContent = (icons[orientation] || '') + ' ' + (presetLabel || orientation);
  }
 
  // Tip dimensi
  const tip = document.getElementById('thumbDimTip');
  if (tip) {
    tip.style.display = dimW && dimH ? '' : 'none';
    tip.textContent   = dimW && dimH ? `Output: ${dimW}×${dimH}px` : '';
  }
}
function clearThumb() {
  document.getElementById('fieldThumbnail').value     = '';
  document.getElementById('fieldThumbnailAlt').value  = '';
  document.getElementById('fieldOgUrl').value         = '';
  document.getElementById('fieldThumbOrient').value   = '';
  document.getElementById('fieldThumbPreset').value   = '';
 
  const img = document.getElementById('thumbImg');
  img.src = ''; img.alt = ''; img.style.objectFit = '';
 
  document.getElementById('thumbEmpty').style.display  = '';
  document.getElementById('thumbFilled').style.display = 'none';
  document.getElementById('thumbFilename').textContent = '';
 
  const badge = document.getElementById('thumbOrientBadge');
  if (badge) badge.style.display = 'none';
  const tip = document.getElementById('thumbDimTip');
  if (tip) tip.style.display = 'none';
}

// ════════════════════════════════════════════════════════════════════
// MOBILE: Accordion & FAB
// ════════════════════════════════════════════════════════════════════

function _initAccordions() {
  document.querySelectorAll('.sidebar-card.accordion .sidebar-card-title').forEach(title => {
    title.addEventListener('click', function () {
      if (window.innerWidth > 600) return;
      this.closest('.sidebar-card').classList.toggle('open');
    });
  });
}
function _updateFabStatus() {
  const judul = (document.getElementById('fieldJudul')?.value || '').trim();
  const st    = document.getElementById('fieldStatus')?.value || 'draft';
  const fab   = document.getElementById('fabStatus');
  if (!fab) return;
  if (!judul) fab.textContent = 'Belum ada judul';
  else {
    const label = st === 'published' ? '✓ Published' : '◷ Draft';
    fab.textContent = label + ' · ' + judul.substring(0, 28) + (judul.length > 28 ? '…' : '');
  }
}

// ──────────────────────────────────────────────────────────────────
// TAG SUGGESTIONS
// ──────────────────────────────────────────────────────────────────
async function _loadTagSuggestions() {
  const wrap = document.getElementById('tagSuggestList');
  if (!wrap) return;
  try {
    const res  = await fetch('/admin/api/artikel.php?action=tags&menu=' + encodeURIComponent(ACTIVE_MENU),
                             { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
    const data = await res.json();
    if (!data.tags || !data.tags.length) return;
    wrap.innerHTML = '<span style="font-size:11px;color:var(--text-muted);align-self:center;margin-right:2px">Tag populer:</span>';
    data.tags.slice(0, 18).forEach(function(tag) {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.textContent = tag;
      btn.style.cssText = 'font-size:11px;color:var(--accent);border:1px solid rgba(201,168,76,.3);border-radius:12px;padding:2px 10px;background:rgba(201,168,76,.06);cursor:pointer;transition:background .15s';
      btn.onmouseover = () => btn.style.background = 'rgba(201,168,76,.18)';
      btn.onmouseout  = () => btn.style.background = 'rgba(201,168,76,.06)';
      btn.onclick = function() {
        const existing = Array.from(document.querySelectorAll('.tag-chip-text')).map(el => el.textContent.trim().toLowerCase());
        if (!existing.includes(tag.toLowerCase())) {
          const input = document.querySelector('[data-tags-input]') || document.querySelector('input[onkeydown*="_tagsKeydown"]');
          if (input) {
            input.value = tag;
            input.dispatchEvent(new KeyboardEvent('keydown', { key: 'Enter', bubbles: true }));
          }
        }
      };
      wrap.appendChild(btn);
    });
  } catch(e) {}
}

// ──────────────────────────────────────────────────────────────────
// AUTOSAVE ke localStorage
// ──────────────────────────────────────────────────────────────────
const AUTOSAVE_KEY = 'draft_' + (EDIT_ID || 'new') + '_' + ACTIVE_MENU;
let _autosaveTimer = null;
let _lastSavedHash = '';

function _getHash() {
  const j = document.getElementById('fieldJudul')?.value || '';
  const k = quill ? quill.root.innerHTML : '';
  return j.length + '|' + k.length;
}

function _autosaveNow() {
  const hash = _getHash();
  if (hash === _lastSavedHash) return;
  try {
    const data = {
      judul    : document.getElementById('fieldJudul')?.value        || '',
      konten   : quill ? quill.root.innerHTML : '',
      ringkasan: document.getElementById('fieldRingkasan')?.value    || '',
      tags     : document.getElementById('fieldTags')?.value         || '',
      thumbnail: document.getElementById('fieldThumbnail')?.value    || '',
      thumbAlt : document.getElementById('fieldThumbnailAlt')?.value || '',
      savedAt  : new Date().toISOString(),
    };
    localStorage.setItem(AUTOSAVE_KEY, JSON.stringify(data));
    _lastSavedHash = hash;
    const el = document.getElementById('saveInfo');
    if (el) { const n=new Date(); el.textContent='Tersimpan '+n.getHours().toString().padStart(2,'0')+':'+n.getMinutes().toString().padStart(2,'0'); }
  } catch(e) {}
}

function _scheduleAutosave() {
  clearTimeout(_autosaveTimer);
  _autosaveTimer = setTimeout(_autosaveNow, 3000);
}

function _clearAutosave() {
  try { localStorage.removeItem(AUTOSAVE_KEY); } catch(e) {}
}

function _checkDraftRecovery() {
  if (EDIT_ID) { localStorage.removeItem(AUTOSAVE_KEY); return; }
  try {
    const raw = localStorage.getItem(AUTOSAVE_KEY);
    if (!raw) return;
    const draft = JSON.parse(raw);
    if (!draft.judul && !draft.konten) { localStorage.removeItem(AUTOSAVE_KEY); return; }
    const diffMin = Math.round((Date.now() - new Date(draft.savedAt).getTime()) / 60000);
    const timeLabel = diffMin < 60 ? diffMin + ' menit lalu' : Math.round(diffMin/60) + ' jam lalu';
    confirmDialog('🗒 Draft Ditemukan',
      'Ada draft tersimpan dari ' + timeLabel + (draft.judul ? ' ("' + draft.judul.substring(0,40) + '")' : '') + '. Pulihkan?',
      function() {
        if (draft.judul) document.getElementById('fieldJudul').value = draft.judul;
        if (draft.konten && quill) quill.clipboard.dangerouslyPasteHTML(draft.konten);
        if (draft.ringkasan) { document.getElementById('fieldRingkasan').value = draft.ringkasan; ringkasanDirty = true; updateRingkasanBadge(draft.ringkasan.length); }
        if (draft.tags) _setTagsFromString(draft.tags);
        if (draft.thumbnail) {
          document.getElementById('fieldThumbnail').value = draft.thumbnail;
          if (draft.thumbAlt) document.getElementById('fieldThumbnailAlt').value = draft.thumbAlt;
          const img = document.getElementById('thumbImg');
          if (img) { img.src=draft.thumbnail; document.getElementById('thumbEmpty').style.display='none'; document.getElementById('thumbFilled').style.display=''; }
        }
        toast('Draft Dipulihkan', 'Data dari ' + timeLabel + ' berhasil dimuat.', 'success');
      }, false);
  } catch(e) {}
}

// ──────────────────────────────────────────────────────────────────
// PREVIEW ARTIKEL
// ──────────────────────────────────────────────────────────────────
const MENU_LABELS = { berita:'Liputan Berita', kronik:'Kronik SMDTBA', historia:'Historia Gereja' };

function _esc(s) {
  if (!s) return '';
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ──────────────────────────────────────────────────────────────────
// IMAGE-ROW: kelompokkan gambar berurutan menjadi 1 baris
// Mereplikasi logika groupImgFiguresToRow() dari artikel-detail.php
// ──────────────────────────────────────────────────────────────────
function _groupImagesInPreview(container) {
  // 1. Bungkus setiap <img> dalam .preview-body menjadi .img-wrap (jika belum)
  //    agar mudah dideteksi sebagai "figure kandidat"
  const body = container.querySelector('.preview-body');
  if (!body) return;

  // Wrap bare <img> yang langsung anak dari body → dalam div.img-wrap sementara
  Array.from(body.childNodes).forEach(function(node) {
    if (node.nodeType === 1 && node.tagName === 'IMG') {
      const wrap = document.createElement('div');
      wrap.className = 'img-wrap _preview-img';
      node.parentNode.insertBefore(wrap, node);
      wrap.appendChild(node);
    }
    // Quill kadang bungkus img dalam <p> atau langsung
    if (node.nodeType === 1 && node.tagName === 'P') {
      const imgs = node.querySelectorAll('img');
      if (imgs.length === 1 && node.textContent.trim() === '') {
        // <p> hanya berisi gambar → jadikan .img-wrap
        node.className = (node.className + ' img-wrap _preview-img').trim();
      }
    }
  });

  // 2. Scan child nodes, kumpulkan run gambar berurutan
  let changed = true;
  while (changed) {
    changed = false;
    const children = Array.from(body.childNodes);

    const isImg = function(n) {
      if (n.nodeType !== 1) return false;
      if (n.classList && n.classList.contains('_preview-img')) return true;
      // Cek apakah node adalah wrap yg sudah di dalam image-row
      return false;
    };

    const isSkippable = function(n) {
      if (n.nodeType === 3) return n.nodeValue.trim() === ''; // text node kosong
      if (n.nodeType === 1 && n.tagName === 'P') {
        return n.textContent.replace(/[\s ]+/g, '') === '';
      }
      return false;
    };

    for (let i = 0; i < children.length; i++) {
      if (!isImg(children[i])) continue;

      // Kumpulkan run
      const run = [children[i]];
      const skipped = [];
      let k = i + 1;
      while (k < children.length) {
        const cur = children[k];
        if (isSkippable(cur))   { skipped.push(cur); k++; continue; }
        if (isImg(cur))         { run.push(cur); skipped.length = 0; k++; continue; }
        break;
      }

      if (run.length < 2) continue; // butuh minimal 2 gambar berdekatan

      // Buat .image-row
      const row = document.createElement('div');
      row.className = 'image-row';
      body.insertBefore(row, children[i]);

      run.forEach(function(wrap) {
        // Pastikan class img-wrap ada
        if (!wrap.classList.contains('img-wrap')) wrap.classList.add('img-wrap');
        wrap.classList.remove('_preview-img');
        // Reset margin/display yang mungkin di-set inline di img
        const img = wrap.querySelector('img');
        if (img) { img.style.margin = '0'; img.style.display = 'block'; }
        row.appendChild(wrap);
      });

      // Hapus node kosong yang dilewati
      skipped.forEach(function(sk) {
        if (sk.parentNode === body) body.removeChild(sk);
      });

      changed = true;
      break; // restart scan
    }
  }
}

function openPreview() {
  const judul     = (document.getElementById('fieldJudul')?.value     || '').trim();
  const ringkasan = (document.getElementById('fieldRingkasan')?.value || '').trim();
  const konten    = quill ? quill.root.innerHTML : '';
  const thumbnail = document.getElementById('fieldThumbnail')?.value  || '';
  const thumbAlt  = document.getElementById('fieldThumbnailAlt')?.value || '';
  const tagsRaw   = document.getElementById('fieldTags')?.value        || '';
  const menu      = ACTIVE_MENU;
  const status    = document.getElementById('fieldStatus')?.value      || 'draft';
  const tags      = tagsRaw ? tagsRaw.split(',').map(t=>t.trim()).filter(Boolean) : [];
  const menuLabel = MENU_LABELS[menu] || menu;

  if (!judul && (!konten || konten === '<p><br></p>')) {
    toast('Preview', 'Tulis judul atau konten terlebih dahulu.', 'info'); return;
  }

  const dateStr = new Date().toLocaleDateString('id-ID', {day:'numeric',month:'long',year:'numeric'});

  const html = `
    <div class="preview-article">
      <div class="preview-notice">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <div>Ini adalah <strong style="color:#c9a84c">preview</strong> — tampilan sebenarnya mungkin sedikit berbeda.
        Artikel ${status==='published'?'sudah':'masih <strong>draft</strong>, belum'} publish.</div>
      </div>
      <span class="preview-category-badge">${_esc(menuLabel)}</span>
      ${judul ? `<h1 class="preview-title">${_esc(judul)}</h1>` : `<h1 class="preview-title" style="color:#554d42;font-style:italic">Belum ada judul…</h1>`}
      <div class="preview-meta">
        <span style="display:flex;align-items:center;gap:5px">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="13" height="13"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
          ${dateStr}
        </span>
        ${tags.slice(0,3).map(t=>`<span style="font-size:11px;color:#c9a84c">#${_esc(t)}</span>`).join('')}
      </div>
      ${thumbnail
        ? `<img src="${thumbnail}" alt="${_esc(thumbAlt)}" class="preview-thumbnail">`
        : `<div style="width:100%;height:180px;background:rgba(255,255,255,.04);border-radius:10px;margin-bottom:28px;display:flex;align-items:center;justify-content:center;color:#554d42;font-size:13px;border:1px dashed rgba(255,255,255,.08)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="28" height="28" style="margin-right:10px;opacity:.4"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
            Belum ada thumbnail
           </div>`}
      ${ringkasan ? `<div class="preview-ringkasan">${_esc(ringkasan)}</div>` : ''}
      ${(konten && konten !== '<p><br></p>')
        ? `<div class="preview-body">${konten}</div>`
        : `<div class="preview-body" style="color:#554d42;font-style:italic">Belum ada isi artikel…</div>`}
      ${tags.length ? `<div class="preview-tags">${tags.map(t=>`<span class="preview-tag">#${_esc(t)}</span>`).join('')}</div>` : ''}
    </div>`;

  document.getElementById('previewContent').innerHTML = html;

  // Kelompokkan gambar berurutan menjadi 1 baris (sama seperti di artikel-detail)
  _groupImagesInPreview(document.getElementById('previewContent'));

  document.getElementById('previewModal').style.display = 'block';
  document.body.style.overflow = 'hidden';
}

function closePreview() {
  document.getElementById('previewModal').style.display = 'none';
  document.body.style.overflow = '';
}

document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape' && document.getElementById('previewModal')?.style.display !== 'none') closePreview();
});

</script>

<?php adminFooter(); ?>