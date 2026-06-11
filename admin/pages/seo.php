<?php
/**
 * admin/pages/seo.php
 * SEO Generator — Multi-Sumber Edition
 * Tabs: Galeri | Artikel | Profil DPP | Asisten Imam | Profil Wilayah | UMKM
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
adminBoot();
$user = requireSuperadmin();
adminHeader('SEO Generator', 'seo', $user);
?>

<style>
/* ══════════════════════════════════════════════════════
   SEO Generator Multi-Sumber — Style
══════════════════════════════════════════════════════ */

/* ── Section Tabs ──────────────────────────────────── */
.seo-section-tabs {
  display: flex;
  gap: 0;
  border-bottom: 2px solid var(--border);
  margin-bottom: 20px;
  overflow-x: auto;
  scrollbar-width: none;
}
.seo-section-tabs::-webkit-scrollbar { display: none; }
.seo-section-tab {
  display: flex;
  align-items: center;
  gap: 7px;
  padding: 11px 18px;
  font-size: 13px;
  font-weight: 500;
  color: var(--text-secondary);
  background: transparent;
  border: none;
  cursor: pointer;
  white-space: nowrap;
  border-bottom: 2px solid transparent;
  margin-bottom: -2px;
  transition: color .15s, border-color .15s;
  position: relative;
}
.seo-section-tab:hover { color: var(--text-primary); }
.seo-section-tab.active {
  color: var(--accent);
  border-bottom-color: var(--accent);
  font-weight: 600;
}
.seo-tab-badge {
  display: inline-flex; align-items: center; justify-content: center;
  min-width: 20px; height: 18px; padding: 0 6px;
  border-radius: 20px;
  font-size: 10px; font-weight: 700; font-family: 'DM Mono', monospace;
  background: var(--bg-card2);
  color: var(--text-muted);
  border: 1px solid var(--border);
  transition: background .15s, color .15s;
}
.seo-section-tab.active .seo-tab-badge {
  background: rgba(201,168,76,.15);
  color: var(--accent);
  border-color: rgba(201,168,76,.3);
}
.seo-tab-dot {
  width: 7px; height: 7px; border-radius: 50%;
  position: absolute; top: 7px; right: 7px;
  display: none;
}
.seo-tab-dot.has-pending { display: block; background: #f59e0b; }

/* ── Stat Cards ────────────────────────────────────── */
.seo-stats {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
  margin-bottom: 20px;
}
.seo-stat {
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 18px 20px;
  transition: border-color .2s;
}
.seo-stat:hover { border-color: var(--accent); }
.seo-stat-icon {
  width: 34px; height: 34px;
  border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  margin-bottom: 12px;
  font-size: 17px;
}
.seo-stat-icon.gold  { background: rgba(201,168,76,.15); }
.seo-stat-icon.green { background: rgba(60,179,113,.12); }
.seo-stat-icon.amber { background: rgba(245,158,11,.12); }
.seo-stat-icon.blue  { background: rgba(82,148,224,.12); }
.seo-stat-val {
  font-family: 'Playfair Display', serif;
  font-size: 30px; font-weight: 700;
  color: var(--text-primary);
  line-height: 1; margin-bottom: 4px;
}
.seo-stat-val.green { color: #3cb371; }
.seo-stat-val.amber { color: #f59e0b; }
.seo-stat-val.blue  { color: #5294e0; }
.seo-stat-label {
  font-size: 11px; color: var(--text-muted);
  text-transform: uppercase; letter-spacing: .06em; font-weight: 500;
}

/* ── Control Bar ───────────────────────────────────── */
.seo-controls {
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 16px 18px;
  margin-bottom: 16px;
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 10px;
}
.seo-controls-left  { display: flex; gap: 8px; flex-wrap: wrap; flex: 1; }
.seo-controls-right { display: flex; gap: 8px; align-items: center; }
.seo-progress-block {
  display: none; flex-direction: column; gap: 8px;
  width: 100%; margin-top: 10px; padding-top: 14px;
  border-top: 1px solid var(--border);
}
.seo-progress-bar-bg {
  height: 6px; background: var(--bg-card2);
  border-radius: 20px; overflow: hidden;
}
.seo-progress-bar-fill {
  height: 100%;
  background: linear-gradient(90deg, #c9a84c, #f0c060);
  border-radius: 20px; width: 0%;
  transition: width .35s ease;
}
.seo-progress-text {
  font-size: 12.5px; color: var(--text-secondary);
  display: flex; justify-content: space-between;
}
.seo-log-box {
  background: var(--bg-main);
  border: 1px solid var(--border);
  border-radius: 6px; padding: 10px 14px;
  font-size: 11.5px; font-family: 'DM Mono', monospace;
  max-height: 120px; overflow-y: auto;
  color: #7ee787; line-height: 1.7;
  white-space: pre-wrap; word-break: break-all;
}

/* ── Filter Bar ────────────────────────────────────── */
.seo-filter-bar {
  display: flex; gap: 10px; align-items: center;
  margin-bottom: 14px; flex-wrap: wrap;
}
.seo-filter-tabs {
  display: flex; gap: 0;
  border: 1px solid var(--border);
  border-radius: var(--radius-sm); overflow: hidden;
}
.seo-filter-tab {
  padding: 7px 16px; font-size: 12.5px; font-weight: 500;
  color: var(--text-secondary); background: var(--bg-card2);
  border: none; cursor: pointer; transition: all .15s;
  border-right: 1px solid var(--border);
}
.seo-filter-tab:last-child { border-right: none; }
.seo-filter-tab.active { background: var(--accent); color: #111; font-weight: 600; }
.seo-filter-tab:hover:not(.active) { background: var(--bg-card); }

/* ── Status badges ─────────────────────────────────── */
.seo-status-done {
  display: inline-flex; align-items: center; gap: 5px;
  background: rgba(60,179,113,.1); color: #3cb371;
  border: 1px solid rgba(60,179,113,.25);
  border-radius: 20px; padding: 3px 10px;
  font-size: 11px; font-weight: 600; white-space: nowrap;
}
.seo-status-pending {
  display: inline-flex; align-items: center; gap: 5px;
  background: rgba(245,158,11,.08); color: #f59e0b;
  border: 1px solid rgba(245,158,11,.2);
  border-radius: 20px; padding: 3px 10px;
  font-size: 11px; font-weight: 600; white-space: nowrap;
}
.seo-status-empty {
  display: inline-flex; align-items: center; gap: 5px;
  background: rgba(139,148,158,.07); color: #8b949e;
  border: 1px solid rgba(139,148,158,.15);
  border-radius: 20px; padding: 3px 10px;
  font-size: 11px; white-space: nowrap;
}

/* ── Generate button ───────────────────────────────── */
.btn-generate-row {
  padding: 5px 14px; border-radius: 6px;
  border: 1px solid rgba(201,168,76,.3);
  background: rgba(201,168,76,.07); color: var(--accent);
  font-size: 12px; font-weight: 600; cursor: pointer;
  transition: all .15s; white-space: nowrap;
}
.btn-generate-row:hover { background: var(--accent); border-color: var(--accent); color: #111; }
.btn-generate-row:disabled { opacity: .45; cursor: not-allowed; }

/* ── Image thumbnail ───────────────────────────────── */
.seo-thumb {
  width: 44px; height: 44px; border-radius: 8px;
  object-fit: cover; border: 1px solid var(--border);
  background: var(--bg-card2); display: block;
  flex-shrink: 0;
}
.seo-thumb-circle {
  width: 40px; height: 40px; border-radius: 50%;
  object-fit: cover; border: 2px solid var(--border);
  background: var(--bg-card2); display: block;
  flex-shrink: 0;
}
.seo-thumb-placeholder {
  width: 44px; height: 44px; border-radius: 8px;
  background: var(--bg-card2); border: 1px dashed var(--border);
  display: flex; align-items: center; justify-content: center;
  color: var(--text-muted); font-size: 18px; flex-shrink: 0;
}
.seo-name-cell { max-width: 260px; }
.seo-name-primary {
  font-weight: 500; font-size: 13px; color: var(--text-primary);
  overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
.seo-name-sub {
  font-size: 11px; color: var(--text-muted);
  overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
  margin-top: 2px;
}

/* ── Menu/section badges ───────────────────────────── */
.section-badge {
  display: inline-flex; align-items: center; gap: 4px;
  padding: 2px 9px; border-radius: 20px;
  font-size: 10px; font-weight: 700; letter-spacing: .04em;
  white-space: nowrap;
}
.badge-galeri       { background: rgba(82,148,224,.1); color: #5294e0; border: 1px solid rgba(82,148,224,.2); }
.badge-umkm         { background: rgba(60,179,113,.1); color: #3cb371; border: 1px solid rgba(60,179,113,.2); }
.badge-dpp_bgkp     { background: rgba(201,168,76,.1); color: #c9a84c; border: 1px solid rgba(201,168,76,.2); }
.badge-asisten_imam { background: rgba(139,92,246,.1); color: #a78bfa; border: 1px solid rgba(139,92,246,.2); }
.badge-wilayah      { background: rgba(245,158,11,.1); color: #f59e0b; border: 1px solid rgba(245,158,11,.2); }
.badge-artikel      { background: rgba(248,81,73,.1);  color: #f85149; border: 1px solid rgba(248,81,73,.2); }

/* ── Artikel-specific (reuse from seo-artikel.php) ─── */
.menu-badge { display: inline-block; padding: 2px 8px; border-radius: 20px; font-size: 10px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; }
.menu-berita   { background: rgba(82,148,224,.1); color: #5294e0; border: 1px solid rgba(82,148,224,.2); }
.menu-kronik   { background: rgba(201,168,76,.1); color: #c9a84c; border: 1px solid rgba(201,168,76,.2); }
.menu-historia { background: rgba(139,92,246,.1); color: #a78bfa; border: 1px solid rgba(139,92,246,.2); }
.seo-img-count { display: inline-flex; align-items: center; gap: 5px; font-size: 12.5px; color: var(--text-secondary); font-family: 'DM Mono', monospace; }
.seo-img-count.zero { color: var(--text-muted); opacity: .6; }

/* ── Empty state ───────────────────────────────────── */
.seo-empty {
  text-align: center; padding: 60px 20px; color: var(--text-muted);
}
.seo-empty-icon  { font-size: 48px; margin-bottom: 16px; opacity: .5; }
.seo-empty-text  { font-family: 'Playfair Display', serif; font-size: 18px; color: var(--text-secondary); margin-bottom: 6px; }
.seo-empty-sub   { font-size: 13px; }

@media (max-width: 900px) { .seo-stats { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 600px) { .seo-stats { grid-template-columns: repeat(2, 1fr); gap: 10px; } }
</style>

<!-- ── Page Header ────────────────────────────────────────────────────────── -->
<div class="page-header">
  <div class="page-header-left">
    <h1>SEO Generator
      <span style="font-size:13px;font-weight:500;background:rgba(201,168,76,.15);color:var(--accent);border:1px solid rgba(201,168,76,.25);border-radius:20px;padding:3px 10px;vertical-align:middle;letter-spacing:.04em;">✦ AI Powered</span>
    </h1>
    <p>Generate alt text, caption &amp; schema metadata SEO untuk semua gambar website menggunakan Gemini &amp; Groq AI</p>
  </div>
  <button class="btn btn-secondary btn-sm" onclick="clearFileCache()" id="btnClearCache" title="Hapus file cache lokal">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14">
      <polyline points="1 4 1 10 7 10"/><polyline points="23 20 23 14 17 14"/>
      <path d="M20.49 9A9 9 0 005.64 5.64L1 10m22 4l-4.64 4.36A9 9 0 013.51 15"/>
    </svg>
    Clear Cache
  </button>
</div>

<!-- ── Section Tabs ───────────────────────────────────────────────────────── -->
<div class="seo-section-tabs" id="sectionTabs">
  <button class="seo-section-tab active" data-section="galeri"       onclick="switchSection('galeri',this)">
    📸 Galeri <span class="seo-tab-badge" id="badge-galeri">—</span>
    <span class="seo-tab-dot" id="dot-galeri"></span>
  </button>
  <button class="seo-section-tab" data-section="artikel"     onclick="switchSection('artikel',this)">
    📄 Artikel <span class="seo-tab-badge" id="badge-artikel">—</span>
    <span class="seo-tab-dot" id="dot-artikel"></span>
  </button>
  <button class="seo-section-tab" data-section="dpp_bgkp"    onclick="switchSection('dpp_bgkp',this)">
    🏛️ Profil DPP <span class="seo-tab-badge" id="badge-dpp_bgkp">—</span>
    <span class="seo-tab-dot" id="dot-dpp_bgkp"></span>
  </button>
  <button class="seo-section-tab" data-section="asisten_imam" onclick="switchSection('asisten_imam',this)">
    🎓 Asisten Imam <span class="seo-tab-badge" id="badge-asisten_imam">—</span>
    <span class="seo-tab-dot" id="dot-asisten_imam"></span>
  </button>
  <button class="seo-section-tab" data-section="wilayah"     onclick="switchSection('wilayah',this)">
    🏘️ Profil Wilayah <span class="seo-tab-badge" id="badge-wilayah">—</span>
    <span class="seo-tab-dot" id="dot-wilayah"></span>
  </button>
  <button class="seo-section-tab" data-section="umkm"        onclick="switchSection('umkm',this)">
    🛍️ UMKM <span class="seo-tab-badge" id="badge-umkm">—</span>
    <span class="seo-tab-dot" id="dot-umkm"></span>
  </button>
</div>

<!-- ── Stats ─────────────────────────────────────────────────────────────── -->
<div class="seo-stats">
  <div class="seo-stat">
    <div class="seo-stat-icon gold">🖼️</div>
    <div class="seo-stat-val" id="statTotal">—</div>
    <div class="seo-stat-label" id="statTotalLabel">Total Gambar</div>
  </div>
  <div class="seo-stat">
    <div class="seo-stat-icon green">✅</div>
    <div class="seo-stat-val green" id="statDone">—</div>
    <div class="seo-stat-label">Sudah Di-generate</div>
  </div>
  <div class="seo-stat">
    <div class="seo-stat-icon amber">⏳</div>
    <div class="seo-stat-val amber" id="statPending">—</div>
    <div class="seo-stat-label">Belum Di-generate</div>
  </div>
  <div class="seo-stat">
    <div class="seo-stat-icon blue">📊</div>
    <div class="seo-stat-val blue" id="statPct">—</div>
    <div class="seo-stat-label">Persentase Selesai</div>
  </div>
</div>

<!-- ── Control Bar ───────────────────────────────────────────────────────── -->
<div class="seo-controls">
  <div class="seo-controls-left">
    <button class="btn btn-primary" id="btnAll" onclick="startBatch('all')">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><polygon points="5 3 19 12 5 21 5 3"/></svg>
      Generate Semua
    </button>
    <button class="btn btn-secondary" id="btnPending" onclick="startBatch('pending')">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
      Hanya yang Belum
    </button>
    <button class="btn btn-secondary" id="btnRefresh" onclick="loadSection()">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1014.85-3.36L15 9"/></svg>
      Refresh
    </button>
  </div>
  <div class="seo-controls-right">
    <span id="progressCount" style="font-size:12px;color:var(--text-muted)"></span>
  </div>
  <div class="seo-progress-block" id="progressBlock">
    <div class="seo-progress-bar-bg"><div class="seo-progress-bar-fill" id="progressFill"></div></div>
    <div class="seo-progress-text">
      <span id="progressText">Memulai...</span>
      <span id="progressPct">0%</span>
    </div>
    <div class="seo-log-box" id="logBox"></div>
  </div>
</div>

<!-- ── Filter Bar ────────────────────────────────────────────────────────── -->
<div class="seo-filter-bar">
  <div class="seo-filter-tabs">
    <button class="seo-filter-tab active" data-filter="all"     onclick="setFilter('all',this)">Semua</button>
    <button class="seo-filter-tab"        data-filter="pending" onclick="setFilter('pending',this)">Belum</button>
    <button class="seo-filter-tab"        data-filter="done"    onclick="setFilter('done',this)">Sudah</button>
  </div>
  <div class="search-wrap" style="flex:1;max-width:300px">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    <input type="text" class="form-control" id="searchInput" placeholder="Cari nama / file..." oninput="renderTable()">
  </div>
  <span id="rowCount" style="font-size:12.5px;color:var(--text-muted);white-space:nowrap"></span>
</div>

<!-- ── Table ─────────────────────────────────────────────────────────────── -->
<div class="card">
  <div class="table-wrapper">
    <table class="data-table" id="seoTable">
      <thead id="seoThead">
        <tr>
          <th style="width:36px">#</th>
          <th style="width:52px"></th>
          <th>Nama / File</th>
          <th>Info</th>
          <th style="width:140px">Status SEO</th>
          <th style="width:130px">Aksi</th>
        </tr>
      </thead>
      <tbody id="seoTableBody">
        <tr><td colspan="6" style="text-align:center;color:var(--text-muted);padding:40px">
          <span style="font-size:24px;display:block;margin-bottom:8px">⏳</span>
          Memuat data...
        </td></tr>
      </tbody>
    </table>
  </div>
</div>

<script>
/* ══════════════════════════════════════════════════════
   SEO Generator Multi-Sumber — Client Script
══════════════════════════════════════════════════════ */

const API_IMAGES  = '/admin/api/seo-images.php';
const API_ARTIKEL = '/admin/api/seo-artikel.php';

// State
let currentSection  = 'galeri';
let allItems        = [];      // Data yang sedang aktif
let currentFilter   = 'all';
let batchRunning    = false;
let allSectionStats = {};      // Cache stats per section

// Config per section
const SECTIONS = {
  galeri:       { label: 'Galeri',         api: API_IMAGES,  icon: '📸', isArtikel: false },
  artikel:      { label: 'Artikel',        api: API_ARTIKEL, icon: '📄', isArtikel: true  },
  dpp_bgkp:     { label: 'Profil DPP',     api: API_IMAGES,  icon: '🏛️', isArtikel: false },
  asisten_imam: { label: 'Asisten Imam',   api: API_IMAGES,  icon: '🎓', isArtikel: false },
  wilayah:      { label: 'Profil Wilayah', api: API_IMAGES,  icon: '🏘️', isArtikel: false },
  umkm:         { label: 'UMKM',           api: API_IMAGES,  icon: '🛍️', isArtikel: false },
};

// ── Boot ─────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  loadAllStats();
  loadSection();
});

// ── Switch section ────────────────────────────────────────────────────────────
function switchSection(section, btn) {
  if (batchRunning) { toast('Info', 'Batch sedang berjalan, tunggu selesai.', 'info'); return; }
  currentSection = section;
  currentFilter  = 'all';
  allItems       = [];

  // Update active tab
  document.querySelectorAll('.seo-section-tab').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');

  // Update filter tabs
  document.querySelectorAll('.seo-filter-tab').forEach(b => {
    b.classList.toggle('active', b.dataset.filter === 'all');
  });

  // Update table header
  updateTableHeader(section);

  // Update stat label
  el('statTotalLabel').textContent = SECTIONS[section]?.isArtikel ? 'Total Artikel' : 'Total Gambar';

  // Reset stats display
  ['statTotal','statDone','statPending','statPct'].forEach(id => el(id).textContent = '—');

  // Reset search
  el('searchInput').value = '';

  // Load data
  loadSection();
}

// ── Update table header per section ──────────────────────────────────────────
function updateTableHeader(section) {
  const isArtikel = SECTIONS[section]?.isArtikel;
  if (isArtikel) {
    el('seoThead').innerHTML = `<tr>
      <th style="width:36px">#</th>
      <th>Artikel</th>
      <th style="width:100px">Menu</th>
      <th style="width:90px;text-align:center">Gambar</th>
      <th style="width:140px">Status SEO</th>
      <th style="width:130px">Aksi</th>
    </tr>`;
  } else {
    el('seoThead').innerHTML = `<tr>
      <th style="width:36px">#</th>
      <th style="width:52px"></th>
      <th>Nama / File</th>
      <th>Info</th>
      <th style="width:140px">Status SEO</th>
      <th style="width:130px">Aksi</th>
    </tr>`;
  }
}

// ── Load section data ─────────────────────────────────────────────────────────
async function loadSection() {
  el('btnRefresh').disabled = true;
  const section = currentSection;
  const isArtikel = SECTIONS[section]?.isArtikel;

  el('seoTableBody').innerHTML = `<tr><td colspan="6" style="text-align:center;color:var(--text-muted);padding:40px">
    <span style="font-size:24px;display:block;margin-bottom:8px">⏳</span>
    Memuat ${SECTIONS[section]?.label ?? section}...
  </td></tr>`;

  try {
    if (isArtikel) {
      await loadArtikelSection();
    } else {
      await loadImagesSection(section);
    }
  } finally {
    el('btnRefresh').disabled = false;
  }
}

// ── Load Artikel section (uses seo-artikel.php API) ───────────────────────────
async function loadArtikelSection() {
  try {
    // Stats
    const sr = await postJson(API_ARTIKEL, { action: 'stats' });
    if (sr.success) {
      updateStats(sr.total_art, sr.done_art, sr.pending_art, sr.total_art);
      updateTabBadge('artikel', sr.total_art, sr.pending_art);
    }
    // List
    const lr = await postJson(API_ARTIKEL, { action: 'list' });
    if (lr.success) {
      allItems = lr.data.map(art => ({
        ...art,
        _type: 'artikel',
      }));
      renderTable();
    }
  } catch(e) {
    showTableError('Gagal memuat artikel: ' + e.message);
  }
}

// ── Load Images section (uses seo-images.php API) ─────────────────────────────
async function loadImagesSection(section) {
  try {
    const lr = await postJson(API_IMAGES, { action: 'list', section });
    if (lr.success) {
      allItems = lr.data.map(img => ({
        ...img,
        _type: 'image',
      }));
      updateStats(lr.total, lr.done, lr.pending, lr.total);
      updateTabBadge(section, lr.total, lr.pending);
      renderTable();
    } else {
      showTableError(lr.error || 'Gagal memuat data');
    }
  } catch(e) {
    showTableError('Gagal memuat data: ' + e.message);
  }
}

// ── Load stats untuk semua section (untuk badge di tabs) ─────────────────────
async function loadAllStats() {
  try {
    // Artikel stats
    const ar = await postJson(API_ARTIKEL, { action: 'stats' });
    if (ar.success) {
      updateTabBadge('artikel', ar.total_art, ar.pending_art);
    }
    // Image sections stats
    const ir = await postJson(API_IMAGES, { action: 'stats' });
    if (ir.success && ir.sections) {
      for (const [sec, data] of Object.entries(ir.sections)) {
        updateTabBadge(sec, data.total, data.pending);
        allSectionStats[sec] = data;
      }
    }
  } catch(e) { /* silent */ }
}

// ── Update stats cards ────────────────────────────────────────────────────────
function updateStats(total, done, pending, imgTotal) {
  el('statTotal').textContent   = total;
  el('statDone').textContent    = done;
  el('statPending').textContent = pending;
  const pct = total > 0 ? Math.round((done / total) * 100) : 0;
  el('statPct').textContent = pct + '%';
}

// ── Update tab badge ──────────────────────────────────────────────────────────
function updateTabBadge(section, total, pending) {
  const badge = el('badge-' + section);
  const dot   = el('dot-' + section);
  if (badge) badge.textContent = total > 999 ? '999+' : (total || '0');
  if (dot) dot.classList.toggle('has-pending', pending > 0);
}

// ── Render table ──────────────────────────────────────────────────────────────
function renderTable() {
  const q   = (el('searchInput').value || '').toLowerCase();
  const flt = currentFilter;
  const isArtikel = SECTIONS[currentSection]?.isArtikel;

  const filtered = allItems.filter(item => {
    if (isArtikel) {
      if (flt === 'pending' && (item.seo_done || item.img_count === 0)) return false;
      if (flt === 'done'    && !item.seo_done)                           return false;
      const searchable = (item.judul || '').toLowerCase();
      if (q && !searchable.includes(q)) return false;
    } else {
      if (flt === 'pending' && item.seo_done)  return false;
      if (flt === 'done'    && !item.seo_done) return false;
      const searchable = ((item.name||'') + ' ' + (item.filename||'')).toLowerCase();
      if (q && !searchable.includes(q)) return false;
    }
    return true;
  });

  el('rowCount').textContent = filtered.length + (isArtikel ? ' artikel' : ' gambar');

  if (!filtered.length) {
    el('seoTableBody').innerHTML = `<tr><td colspan="6">
      <div class="seo-empty">
        <div class="seo-empty-icon">🔍</div>
        <div class="seo-empty-text">Tidak ada item yang cocok</div>
        <div class="seo-empty-sub">Coba ubah filter atau kata pencarian</div>
      </div>
    </td></tr>`;
    return;
  }

  let html = '';
  filtered.forEach((item, i) => {
    if (isArtikel) {
      html += renderArtikelRow(item, i + 1);
    } else {
      html += renderImageRow(item, i + 1);
    }
  });
  el('seoTableBody').innerHTML = html;
}

// ── Render row: Artikel ───────────────────────────────────────────────────────
function renderArtikelRow(art, idx) {
  const menuCls = 'menu-' + esc(art.menu);
  const menuLbl = { berita:'Berita', kronik:'Kronik', historia:'Historia' }[art.menu] || art.menu;
  const imgHtml = art.img_count > 0
    ? `<span class="seo-img-count"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="13" height="13"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>${art.img_count}</span>`
    : `<span class="seo-img-count zero">—</span>`;

  let statusHtml;
  if (art.img_count === 0) {
    statusHtml = `<span class="seo-status-empty">Tanpa foto</span>`;
  } else if (art.seo_done) {
    statusHtml = `<span class="seo-status-done" id="status-${esc(art.id)}">✓ Done</span>`;
  } else {
    statusHtml = `<span class="seo-status-pending" id="status-${esc(art.id)}">● Belum</span>`;
  }

  const force  = art.seo_done;
  const btnLbl = art.seo_done ? '↻ Re-gen' : '▶ Generate';
  const btnHtml = art.img_count > 0
    ? `<button class="btn-generate-row" id="btn-${esc(art.id)}"
         onclick="processArtikel('${esc(art.id)}','${esc(art.menu)}','${escQ(art.judul)}',${force})">${btnLbl}</button>`
    : `<span style="font-size:11px;color:var(--text-muted)">—</span>`;

  return `<tr data-id="${esc(art.id)}" data-menu="${esc(art.menu)}" data-done="${art.seo_done?'1':'0'}"
              data-hasimg="${art.img_count>0?'1':'0'}" data-judul="${escQ(art.judul)}" data-type="artikel">
    <td style="color:var(--text-muted);font-size:12px;font-family:'DM Mono',monospace">${idx}</td>
    <td colspan="2">
      <div style="max-width:360px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:var(--text-primary);font-weight:500;font-size:13px"
           title="${escQ(art.judul)}">${esc(art.judul)}</div>
      ${art.status !== 'published'
        ? `<div style="font-size:11px;color:var(--warning)">◉ draft</div>`
        : ''}
    </td>
    <td><span class="menu-badge ${menuCls}">${menuLbl}</span></td>
    <td style="text-align:center">${imgHtml}</td>
    <td>${statusHtml}</td>
    <td>${btnHtml}</td>
  </tr>`;
}

// ── Render row: Image ─────────────────────────────────────────────────────────
function renderImageRow(item, idx) {
  const section  = currentSection;
  const isCircle = ['dpp_bgkp','asisten_imam','wilayah'].includes(section);
  const imgKey   = encodeURIComponent(item.image_url).replace(/'/g, '%27');

  let thumbHtml;
  if (item.image_url) {
    const cls = isCircle ? 'seo-thumb-circle' : 'seo-thumb';
    thumbHtml = `<img src="${esc(item.image_url)}" class="${cls}" loading="lazy"
                      onerror="this.outerHTML='<div class=\\"seo-thumb-placeholder\\">📷</div>'">`;
  } else {
    thumbHtml = `<div class="seo-thumb-placeholder">📷</div>`;
  }

  const statusHtml = item.seo_done
    ? `<span class="seo-status-done" id="status-${idx}">✓ Done</span>`
    : `<span class="seo-status-pending" id="status-${idx}">● Belum</span>`;

  const btnLbl  = item.seo_done ? '↻ Re-gen' : '▶ Generate';
  const artDataJ = escAttr(JSON.stringify(item._artData || {}));

  return `<tr data-img="${esc(item.image_url)}" data-done="${item.seo_done?'1':'0'}"
              data-name="${escQ(item.name||item.filename||'')}"
              data-artdata="${artDataJ}" data-type="image">
    <td style="color:var(--text-muted);font-size:12px;font-family:'DM Mono',monospace">${idx}</td>
    <td>${thumbHtml}</td>
    <td class="seo-name-cell">
      <div class="seo-name-primary" title="${esc(item.name||item.filename||'')}">${esc(item.name||item.filename||'—')}</div>
      <div class="seo-name-sub">${esc(item.filename||'')}</div>
    </td>
    <td><div class="seo-name-sub" style="max-width:200px">${esc(item.meta||'')}</div></td>
    <td>${statusHtml}</td>
    <td>
      <button class="btn-generate-row" id="btn-img-${idx}"
              onclick="processImage(this, ${idx})">${btnLbl}</button>
    </td>
  </tr>`;
}

// ── Process single Artikel ────────────────────────────────────────────────────
async function processArtikel(id, menu, judul, force = false) {
  const btn    = el('btn-' + id);
  const status = el('status-' + id);
  if (btn) { btn.disabled = true; btn.textContent = '⏳'; }
  if (status) { status.className = 'seo-status-pending'; status.textContent = '⏳ Proses...'; }

  try {
    const data = await postJson(API_ARTIKEL, {
      action: 'process', artikel_id: id, artikel_menu: menu, artikel_judul: judul, force,
    });

    if (status) {
      status.className = data.ok ? 'seo-status-done' : 'seo-status-pending';
      status.textContent = data.ok ? '✓ Done' : '✗ Error';
    }
    const row = el('seoTableBody').querySelector(`tr[data-id="${id}"]`);
    if (row && data.ok) row.dataset.done = '1';
    if (btn) {
      btn.disabled = false; btn.textContent = '↻ Re-gen';
      btn.setAttribute('onclick', `processArtikel('${id}','${menu}','${judul.replace(/'/g,"\\'")}',true)`);
    }
    return data;
  } catch(e) {
    if (status) status.textContent = '✗ Error';
    if (btn) { btn.disabled = false; btn.textContent = force ? '↻ Re-gen' : '▶ Generate'; }
    return { ok: false, msg: e.message };
  }
}

// ── Process single Image ──────────────────────────────────────────────────────
async function processImage(btnEl, rowIdx) {
  const row     = btnEl.closest('tr');
  const imgUrl  = row?.dataset.img;
  const force   = row?.dataset.done === '1';
  const artData = JSON.parse(row?.dataset.artdata || '{}');
  const status  = el('status-' + rowIdx);

  if (!imgUrl) return;
  btnEl.disabled = true; btnEl.textContent = '⏳';
  if (status) { status.className = 'seo-status-pending'; status.textContent = '⏳ Proses...'; }

  try {
    const data = await postJson(API_IMAGES, {
      action: 'process',
      section: currentSection,
      image_url: imgUrl,
      art_data: artData,
      force,
    });

    if (status) {
      status.className   = data.ok ? 'seo-status-done' : 'seo-status-pending';
      status.textContent = data.ok ? '✓ Done' : '✗ Error';
    }
    if (row && data.ok) { row.dataset.done = '1'; }
    btnEl.disabled = false; btnEl.textContent = '↻ Re-gen';
    return data;
  } catch(e) {
    if (status) status.textContent = '✗ Error';
    btnEl.disabled = false; btnEl.textContent = force ? '↻ Re-gen' : '▶ Generate';
    return { ok: false, msg: e.message };
  }
}

// ── Batch Generate ────────────────────────────────────────────────────────────
async function startBatch(mode) {
  if (batchRunning) return;

  const rows = Array.from(el('seoTableBody').querySelectorAll('tr[data-type]'))
    .filter(r => {
      if (mode === 'pending') return r.dataset.done !== '1';
      return true;
    });

  if (!rows.length) {
    toast('Info', 'Tidak ada item yang perlu di-generate.', 'info');
    return;
  }

  if (mode === 'all') {
    const doneCount = rows.filter(r => r.dataset.done === '1').length;
    if (doneCount > 0) {
      const ok = confirm(
        `"Generate Semua" akan me-replace SEO ${doneCount} item yang sudah ada.\n\n` +
        `Gunakan "Hanya yang Belum" agar yang sudah ada tidak diganti.\n\nLanjutkan?`
      );
      if (!ok) return;
    }
  }

  batchRunning = true;
  el('btnAll').disabled = el('btnPending').disabled = true;

  const pb    = el('progressBlock');
  const fill  = el('progressFill');
  const pText = el('progressText');
  const pPct  = el('progressPct');
  const logEl = el('logBox');

  pb.style.display = 'flex';
  logEl.textContent = '';
  fill.style.width  = '0%';

  let done = 0;
  const total = rows.length;
  const isArtikel = SECTIONS[currentSection]?.isArtikel;

  for (const row of rows) {
    let result;
    const displayName = row.dataset.judul || row.dataset.name || '(item)';
    const force = (mode === 'all' && row.dataset.done === '1');

    if (isArtikel) {
      const id   = row.dataset.id;
      const menu = row.dataset.menu;
      const judul = row.dataset.judul;
      if (!row.dataset.hasimg || row.dataset.hasimg === '0') {
        done++;
        continue; // skip artikel tanpa gambar
      }
      result = await processArtikel(id, menu, judul, force);
    } else {
      const imgUrl  = row.dataset.img;
      const artData = JSON.parse(row.dataset.artdata || '{}');
      if (!imgUrl) { done++; continue; }

      const statusEl = el('status-' + (done + 1));
      if (statusEl) { statusEl.className = 'seo-status-pending'; statusEl.textContent = '⏳ Proses...'; }

      try {
        result = await postJson(API_IMAGES, {
          action: 'process',
          section: currentSection,
          image_url: imgUrl,
          art_data: artData,
          force,
        });
        const statusAfter = el('status-' + (done + 1));
        if (statusAfter) {
          statusAfter.className   = result.ok ? 'seo-status-done' : 'seo-status-pending';
          statusAfter.textContent = result.ok ? '✓ Done' : '✗ Error';
        }
        if (result.ok) row.dataset.done = '1';
        const btnEl = row.querySelector('.btn-generate-row');
        if (btnEl) { btnEl.disabled = false; btnEl.textContent = '↻ Re-gen'; }
      } catch(e) {
        result = { ok: false, msg: e.message };
      }
    }

    done++;
    const pct = Math.round((done / total) * 100);
    fill.style.width = pct + '%';
    pText.textContent = `${done} / ${total} diproses`;
    pPct.textContent  = pct + '%';
    el('progressCount').textContent = `${done}/${total}`;

    const icon = result?.ok ? '✅' : '❌';
    const msg  = result?.msg ?? (result?.ok ? 'OK' : 'Error');
    logEl.textContent += `${icon} [${done}/${total}] ${String(displayName).substring(0,50)} — ${msg}\n`;
    logEl.scrollTop    = logEl.scrollHeight;

    await sleep(600); // jeda agar tidak flood API
  }

  pText.textContent = `✅ Selesai! ${done} item diproses.`;
  pPct.textContent  = '100%';
  logEl.textContent += `\n🎉 SELESAI — ${done} item berhasil diproses.\n`;
  el('btnAll').disabled = el('btnPending').disabled = false;
  el('progressCount').textContent = '';
  batchRunning = false;

  // Reload stats for this section
  setTimeout(() => loadAllStats(), 1000);
}

// ── Filters ───────────────────────────────────────────────────────────────────
function setFilter(f, btn) {
  currentFilter = f;
  document.querySelectorAll('.seo-filter-tab').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  renderTable();
}

// ── Clear cache ───────────────────────────────────────────────────────────────
async function clearFileCache() {
  if (!confirm('Hapus semua file cache lokal ImageSeoGenerator?\nData Supabase tidak akan terpengaruh.')) return;
  const btn = el('btnClearCache');
  btn.disabled = true;
  try {
    const data = await postJson(API_IMAGES, { action: 'clear_cache' });
    if (data.success) toast('Cache Dihapus', data.msg, 'success');
    else toast('Error', data.error, 'error');
  } catch(e) {
    toast('Error', 'Gagal menghubungi server', 'error');
  }
  btn.disabled = false;
}

// ── Utils ─────────────────────────────────────────────────────────────────────
function el(id)          { return document.getElementById(id); }
function esc(s)           { return String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c]); }
function escQ(s)          { return String(s??'').replace(/'/g,"\\'").replace(/"/g,'&quot;'); }
function escAttr(s)       { return String(s??'').replace(/"/g,'&quot;').replace(/'/g,'&#39;'); }
function sleep(ms)        { return new Promise(r => setTimeout(r, ms)); }
function showTableError(msg) {
  el('seoTableBody').innerHTML = `<tr><td colspan="6" style="text-align:center;color:var(--danger);padding:30px">⚠️ ${esc(msg)}</td></tr>`;
}
async function postJson(url, body) {
  const res = await fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(body),
  });
  if (!res.ok) throw new Error('HTTP ' + res.status);
  return res.json();
}
</script>

<?php adminFooter(); ?>
