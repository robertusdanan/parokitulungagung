<?php
/**
 * admin/pages/seo-artikel.php
 * Halaman admin: Generator SEO Gambar & Artikel — Supabase Edition
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
adminBoot();
$user = requireSuperadmin();
adminHeader('SEO Generator — Artikel', 'seo', $user);
?>

<style>
/* ══════════════════════════════════════════════════════
   SEO Generator — Elegant Dark Theme
   Menggunakan CSS variables dari admin.css
══════════════════════════════════════════════════════ */

/* ── Stat Cards ───────────────────────────────────────── */
.seo-stats {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
  margin-bottom: 24px;
}
.seo-stat {
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 20px 22px;
  position: relative;
  overflow: hidden;
  transition: border-color .2s;
}
.seo-stat::before {
  content: '';
  position: absolute;
  inset: 0;
  opacity: 0;
  transition: opacity .2s;
}
.seo-stat:hover { border-color: var(--accent); }
.seo-stat:hover::before { opacity: 1; }
.seo-stat-icon {
  width: 36px; height: 36px;
  border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  margin-bottom: 14px;
  font-size: 18px;
}
.seo-stat-icon.gold   { background: rgba(201,168,76,.15); }
.seo-stat-icon.green  { background: rgba(60,179,113,.12); }
.seo-stat-icon.amber  { background: rgba(245,158,11,.12); }
.seo-stat-icon.blue   { background: rgba(82,148,224,.12); }
.seo-stat-val {
  font-family: 'Playfair Display', serif;
  font-size: 32px;
  font-weight: 700;
  color: var(--text-primary);
  line-height: 1;
  margin-bottom: 4px;
  letter-spacing: -.02em;
}
.seo-stat-val.green { color: #3cb371; }
.seo-stat-val.amber { color: #f59e0b; }
.seo-stat-val.blue  { color: #5294e0; }
.seo-stat-label {
  font-size: 11.5px;
  color: var(--text-muted);
  text-transform: uppercase;
  letter-spacing: .06em;
  font-weight: 500;
}

/* ── Control Bar ─────────────────────────────────────── */
.seo-controls {
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 18px 20px;
  margin-bottom: 20px;
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 10px;
}
.seo-controls-left {
  display: flex; gap: 8px; flex-wrap: wrap; flex: 1;
}
.seo-controls-right {
  display: flex; gap: 8px; align-items: center;
}

/* ── Progress Block ──────────────────────────────────── */
.seo-progress-block {
  display: none;
  flex-direction: column;
  gap: 8px;
  width: 100%;
  margin-top: 10px;
  padding-top: 14px;
  border-top: 1px solid var(--border);
}
.seo-progress-bar-bg {
  height: 6px;
  background: var(--bg-card2);
  border-radius: 20px;
  overflow: hidden;
}
.seo-progress-bar-fill {
  height: 100%;
  background: linear-gradient(90deg, #c9a84c, #f0c060);
  border-radius: 20px;
  width: 0%;
  transition: width .35s ease;
}
.seo-progress-text {
  font-size: 12.5px;
  color: var(--text-secondary);
  display: flex;
  justify-content: space-between;
}
.seo-log-box {
  background: var(--bg-main);
  border: 1px solid var(--border);
  border-radius: 6px;
  padding: 10px 14px;
  font-size: 11.5px;
  font-family: 'DM Mono', monospace;
  max-height: 130px;
  overflow-y: auto;
  color: #7ee787;
  line-height: 1.7;
  white-space: pre-wrap;
  word-break: break-all;
}

/* ── Filter Bar ──────────────────────────────────────── */
.seo-filter-bar {
  display: flex;
  gap: 10px;
  align-items: center;
  margin-bottom: 14px;
  flex-wrap: wrap;
}
.seo-filter-tabs {
  display: flex;
  gap: 0;
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  overflow: hidden;
}
.seo-filter-tab {
  padding: 7px 16px;
  font-size: 12.5px;
  font-weight: 500;
  color: var(--text-secondary);
  background: var(--bg-card2);
  border: none;
  cursor: pointer;
  transition: all .15s;
  border-right: 1px solid var(--border);
}
.seo-filter-tab:last-child { border-right: none; }
.seo-filter-tab.active {
  background: var(--accent);
  color: #111;
  font-weight: 600;
}
.seo-filter-tab:hover:not(.active) { background: var(--bg-card); }

/* ── Table Enhancements ──────────────────────────────── */
.seo-judul-cell {
  max-width: 320px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  color: var(--text-primary);
  font-weight: 500;
  font-size: 13.5px;
}
.seo-judul-sub {
  font-size: 11.5px;
  color: var(--text-muted);
  font-weight: 400;
  margin-top: 2px;
}
.seo-img-count {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  font-size: 12.5px;
  color: var(--text-secondary);
  font-family: 'DM Mono', monospace;
}
.seo-img-count.zero { color: var(--text-muted); opacity: .6; }

.seo-status-done {
  display: inline-flex; align-items: center; gap: 5px;
  background: rgba(60,179,113,.1);
  color: #3cb371;
  border: 1px solid rgba(60,179,113,.25);
  border-radius: 20px;
  padding: 3px 10px;
  font-size: 11px;
  font-weight: 600;
  letter-spacing: .03em;
  white-space: nowrap;
}
.seo-status-pending {
  display: inline-flex; align-items: center; gap: 5px;
  background: rgba(245,158,11,.08);
  color: #f59e0b;
  border: 1px solid rgba(245,158,11,.2);
  border-radius: 20px;
  padding: 3px 10px;
  font-size: 11px;
  font-weight: 600;
  letter-spacing: .03em;
  white-space: nowrap;
}
.seo-status-empty {
  display: inline-flex; align-items: center; gap: 5px;
  background: rgba(139,148,158,.07);
  color: #8b949e;
  border: 1px solid rgba(139,148,158,.15);
  border-radius: 20px;
  padding: 3px 10px;
  font-size: 11px;
  letter-spacing: .03em;
  white-space: nowrap;
}

.btn-generate-row {
  padding: 5px 14px;
  border-radius: 6px;
  border: 1px solid rgba(201,168,76,.3);
  background: rgba(201,168,76,.07);
  color: var(--accent);
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: all .15s;
  white-space: nowrap;
}
.btn-generate-row:hover {
  background: var(--accent);
  border-color: var(--accent);
  color: #111;
}
.btn-generate-row:disabled {
  opacity: .45;
  cursor: not-allowed;
}

/* ── Menu badge ──────────────────────────────────────── */
.menu-badge {
  display: inline-block;
  padding: 2px 8px;
  border-radius: 20px;
  font-size: 10px;
  font-weight: 700;
  letter-spacing: .04em;
  text-transform: uppercase;
}
.menu-berita   { background: rgba(82,148,224,.1);  color: #5294e0; border: 1px solid rgba(82,148,224,.2); }
.menu-kronik   { background: rgba(201,168,76,.1);  color: #c9a84c; border: 1px solid rgba(201,168,76,.2); }
.menu-historia { background: rgba(139,92,246,.1);  color: #a78bfa; border: 1px solid rgba(139,92,246,.2); }

/* ── Empty state ─────────────────────────────────────── */
.seo-empty {
  text-align: center;
  padding: 60px 20px;
  color: var(--text-muted);
}
.seo-empty-icon {
  font-size: 48px;
  margin-bottom: 16px;
  opacity: .5;
}
.seo-empty-text {
  font-family: 'Playfair Display', serif;
  font-size: 18px;
  color: var(--text-secondary);
  margin-bottom: 6px;
}
.seo-empty-sub {
  font-size: 13px;
}

/* ── Responsive ─────────────────────────────────────── */
@media (max-width: 900px) {
  .seo-stats { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 600px) {
  .seo-stats { grid-template-columns: repeat(2, 1fr); gap: 10px; }
  .seo-stat-val { font-size: 24px; }
}
</style>

<!-- ── Page Header ─────────────────────────────────────────────────────── -->
<div class="page-header">
  <div class="page-header-left">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px">
      <a href="/admin/pages/seo.php" style="display:inline-flex;align-items:center;gap:5px;font-size:12px;color:var(--text-secondary);text-decoration:none;padding:4px 10px;border:1px solid var(--border);border-radius:6px;background:var(--bg-card2);transition:all .15s"
         onmouseover="this.style.color='var(--accent)'" onmouseout="this.style.color='var(--text-secondary)'">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><polyline points="15 18 9 12 15 6"/></svg>
        SEO Generator
      </a>
      <span style="font-size:11px;color:var(--text-muted)">/ Artikel</span>
    </div>
    <h1>SEO Generator <span style="font-size:13px;font-weight:500;background:rgba(201,168,76,.15);color:var(--accent);border:1px solid rgba(201,168,76,.25);border-radius:20px;padding:3px 10px;vertical-align:middle;letter-spacing:.04em;">✦ AI Powered</span></h1>
    <p>Generate alt text, caption &amp; schema gambar artikel secara otomatis menggunakan Gemini &amp; Groq AI</p>
  </div>
  <button class="btn btn-secondary btn-sm" onclick="clearFileCache()" id="btnClearCache"
          title="Hapus file cache lokal agar data Supabase terbaru dimuat ulang">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14">
      <polyline points="1 4 1 10 7 10"/><polyline points="23 20 23 14 17 14"/>
      <path d="M20.49 9A9 9 0 005.64 5.64L1 10m22 4l-4.64 4.36A9 9 0 013.51 15"/>
    </svg>
    Clear Cache
  </button>
</div>

<!-- ── Stats Cards ─────────────────────────────────────────────────────── -->
<div class="seo-stats" id="statsWrap">
  <?php
  $statDefs = [
    ['icon' => '📄', 'cls' => 'gold',  'id' => 'statTotal',   'label' => 'Total Artikel'],
    ['icon' => '✅', 'cls' => 'green', 'id' => 'statDone',    'label' => 'Sudah Di-generate'],
    ['icon' => '⏳', 'cls' => 'amber', 'id' => 'statPending', 'label' => 'Belum Di-generate'],
    ['icon' => '🖼️', 'cls' => 'blue',  'id' => 'statImg',     'label' => 'Total Gambar'],
  ];
  foreach ($statDefs as $s): ?>
  <div class="seo-stat">
    <div class="seo-stat-icon <?= $s['cls'] ?>"><?= $s['icon'] ?></div>
    <div class="seo-stat-val <?= $s['cls'] === 'gold' ? '' : $s['cls'] ?>" id="<?= $s['id'] ?>">—</div>
    <div class="seo-stat-label"><?= $s['label'] ?></div>
  </div>
  <?php endforeach; ?>
</div>

<!-- ── Control Bar ─────────────────────────────────────────────────────── -->
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
    <button class="btn btn-secondary" id="btnRefresh" onclick="loadData()" style="margin-left:4px">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1014.85-3.36L15 9"/></svg>
      Refresh
    </button>
  </div>
  <div class="seo-controls-right">
    <span id="progressCount" style="font-size:12px;color:var(--text-muted)"></span>
  </div>

  <!-- Progress block (hidden saat idle) -->
  <div class="seo-progress-block" id="progressBlock">
    <div class="seo-progress-bar-bg">
      <div class="seo-progress-bar-fill" id="progressFill"></div>
    </div>
    <div class="seo-progress-text">
      <span id="progressText">Memulai...</span>
      <span id="progressPct">0%</span>
    </div>
    <div class="seo-log-box" id="logBox"></div>
  </div>
</div>

<!-- ── Filter Bar ─────────────────────────────────────────────────────── -->
<div class="seo-filter-bar">
  <div class="seo-filter-tabs">
    <button class="seo-filter-tab active" data-filter="all"     onclick="setFilter('all',this)">Semua</button>
    <button class="seo-filter-tab"        data-filter="pending" onclick="setFilter('pending',this)">Belum</button>
    <button class="seo-filter-tab"        data-filter="done"    onclick="setFilter('done',this)">Sudah</button>
    <button class="seo-filter-tab"        data-filter="noimg"   onclick="setFilter('noimg',this)">Tanpa Foto</button>
  </div>
  <div class="search-wrap" style="flex:1;max-width:300px">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    <input type="text" class="form-control" id="searchInput" placeholder="Cari judul artikel...">
  </div>
  <span id="rowCount" style="font-size:12.5px;color:var(--text-muted);white-space:nowrap"></span>
</div>

<!-- ── Table ──────────────────────────────────────────────────────────── -->
<div class="card">
  <div class="table-wrapper">
    <table class="data-table" id="seoTable">
      <thead>
        <tr>
          <th style="width:36px">#</th>
          <th>Artikel</th>
          <th style="width:100px">Menu</th>
          <th style="width:90px;text-align:center">Gambar</th>
          <th style="width:140px">Status SEO</th>
          <th style="width:120px">Aksi</th>
        </tr>
      </thead>
      <tbody id="seoTableBody">
        <tr><td colspan="6" style="text-align:center;color:var(--text-muted);padding:40px">
          <span style="font-size:24px;display:block;margin-bottom:8px">⏳</span>
          Memuat data artikel...
        </td></tr>
      </tbody>
    </table>
  </div>
</div>

<script>
/* ══════════════════════════════════════════════════════
   SEO Generator — Client Script
══════════════════════════════════════════════════════ */
const API = '/admin/api/seo-artikel.php';

let allArticles  = [];
let currentFilter = 'all';
let batchRunning  = false;

// ── Boot ──────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  loadData();
  document.getElementById('searchInput').addEventListener('input', renderTable);
});

// ── Load data (stats + list) ──────────────────────────────────────────────
async function loadData() {
  document.getElementById('btnRefresh').disabled = true;
  try {
    // Stats
    const sr = await fetch(API, {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({ action: 'stats' }),
    });
    const sd = await sr.json();
    if (sd.success) {
      el('statTotal').textContent   = sd.total_art;
      el('statDone').textContent    = sd.done_art;
      el('statPending').textContent = sd.pending_art;
      el('statImg').textContent     = sd.total_img;
    }

    // Article list
    const lr = await fetch(API, {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({ action: 'list' }),
    });
    const ld = await lr.json();
    if (ld.success) {
      allArticles = ld.data;
      renderTable();
    } else {
      showTableError(ld.error || 'Gagal memuat daftar artikel');
    }
  } catch(e) {
    showTableError('Gagal menghubungi server: ' + e.message);
  } finally {
    document.getElementById('btnRefresh').disabled = false;
  }
}

// ── Render table ──────────────────────────────────────────────────────────
function renderTable() {
  const q   = (el('searchInput').value || '').toLowerCase();
  const flt = currentFilter;

  const filtered = allArticles.filter(art => {
    if (flt === 'pending' && (art.seo_done || art.img_count === 0)) return false;
    if (flt === 'done'    && !art.seo_done)                          return false;
    if (flt === 'noimg'   && art.img_count !== 0)                    return false;
    if (q && !art.judul.toLowerCase().includes(q))                   return false;
    return true;
  });

  el('rowCount').textContent = filtered.length + ' artikel';

  if (!filtered.length) {
    el('seoTableBody').innerHTML = `
      <tr><td colspan="6">
        <div class="seo-empty">
          <div class="seo-empty-icon">🔍</div>
          <div class="seo-empty-text">Tidak ada artikel yang cocok</div>
          <div class="seo-empty-sub">Coba ubah filter atau kata pencarian</div>
        </div>
      </td></tr>`;
    return;
  }

  let html = '';
  filtered.forEach((art, i) => {
    const menuCls  = 'menu-' + esc(art.menu);
    const menuLbl  = menuLabels[art.menu] || art.menu;
    const imgHtml  = art.img_count > 0
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

    const btnLabel  = art.seo_done ? '↻ Re-generate' : '▶ Generate';
    // Re-generate = force:true (replace existing), Generate baru = force:false
    const forceFlag = art.seo_done ? 'true' : 'false';
    const btnHtml   = art.img_count > 0
      ? `<button class="btn-generate-row" onclick="processOne('${esc(art.id)}','${esc(art.menu)}','${escQ(art.judul)}',false,${forceFlag})" id="btn-${esc(art.id)}">${btnLabel}</button>`
      : `<span style="font-size:11px;color:var(--text-muted)">—</span>`;

    html += `<tr data-id="${esc(art.id)}" data-menu="${esc(art.menu)}"
                 data-done="${art.seo_done ? '1':'0'}" data-hasimg="${art.img_count > 0 ? '1':'0'}"
                 data-judul="${escQ(art.judul)}">
      <td style="color:var(--text-muted);font-size:12px;font-family:'DM Mono',monospace">${i+1}</td>
      <td>
        <div class="seo-judul-cell" title="${escQ(art.judul)}">${esc(art.judul)}</div>
        ${art.status !== 'published'
          ? `<div class="seo-judul-sub"><span style="color:var(--warning);font-size:10px">◉ draft</span></div>`
          : ''}
      </td>
      <td><span class="menu-badge ${menuCls}">${menuLbl}</span></td>
      <td style="text-align:center">${imgHtml}</td>
      <td>${statusHtml}</td>
      <td>${btnHtml}</td>
    </tr>`;
  });

  el('seoTableBody').innerHTML = html;
}

// ── Process single article ────────────────────────────────────────────────
async function processOne(id, menu, judul, silent = false, force = false) {
  const btn    = el('btn-' + id);
  const status = el('status-' + id);

  if (btn) { btn.disabled = true; btn.textContent = '⏳'; }
  if (status && !silent) {
    status.className   = 'seo-status-pending';
    status.textContent = '⏳ Proses...';
  }

  try {
    const res  = await fetch(API, {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({
        action: 'process', artikel_id: id, artikel_menu: menu, artikel_judul: judul, force: force,
      }),
    });
    const data = await res.json();

    // Update status cell
    if (status && !silent) {
      if (data.ok) {
        status.className   = 'seo-status-done';
        status.innerHTML   = '✓ Done';
      } else {
        status.className   = 'seo-status-pending';
        status.textContent = '✗ Error';
      }
    }

    // Update row data attribute
    const row = el('seoTableBody').querySelector(`tr[data-id="${id}"]`);
    if (row && data.ok) row.dataset.done = '1';

    // Update button label — setelah generate, tombol selalu jadi Re-generate (force:true)
    if (btn) {
      btn.disabled    = false;
      btn.textContent = '↻ Re-generate';
      // Update onclick agar berikutnya selalu force:true
      btn.setAttribute('onclick',
        `processOne('${id}','${menu}','${judul.replace(/'/g,"\\'")}',false,true)`
      );
    }

    return data;
  } catch (e) {
    if (status && !silent) { status.textContent = '✗ Error'; }
    if (btn) {
      btn.disabled = false;
      // Kembalikan ke label & force sesuai kondisi sebelumnya
      if (force) {
        btn.textContent = '↻ Re-generate';
        btn.setAttribute('onclick',
          `processOne('${id}','${menu}','${judul.replace(/'/g,"\\'")}',false,true)`
        );
      } else {
        btn.textContent = '▶ Generate';
      }
    }
    return { ok: false, msg: e.message };
  }
}

// ── Batch generate ────────────────────────────────────────────────────────
async function startBatch(mode) {
  if (batchRunning) return;

  const rows = Array.from(
    el('seoTableBody').querySelectorAll('tr[data-hasimg="1"]')
  ).filter(r => mode === 'all' ? true : r.dataset.done !== '1');

  if (!rows.length) {
    toast('Info', 'Tidak ada artikel yang perlu di-generate.', 'info');
    return;
  }

  // Konfirmasi jika mode 'all' dan ada artikel yang sudah punya SEO (akan di-replace)
  if (mode === 'all') {
    const doneCount = rows.filter(r => r.dataset.done === '1').length;
    if (doneCount > 0) {
      const ok = confirm(
        `"Generate Semua" akan me-replace SEO ${doneCount} artikel yang sudah ada.\n\n` +
        `Gunakan "Hanya yang Belum" jika tidak ingin mengganti yang sudah ada.\n\n` +
        `Lanjutkan?`
      );
      if (!ok) return;
    }
  }

  batchRunning = true;
  el('btnAll').disabled     = true;
  el('btnPending').disabled = true;

  const pb    = el('progressBlock');
  const fill  = el('progressFill');
  const pText = el('progressText');
  const pPct  = el('progressPct');
  const logEl = el('logBox');

  pb.style.display = 'flex';
  logEl.textContent = '';

  let done = 0;
  const total = rows.length;

  for (const row of rows) {
    const id     = row.dataset.id;
    const menu   = row.dataset.menu;
    const judul  = row.dataset.judul;
    // 'all' mode: artikel yang sudah done harus di-force replace; 'pending' hanya yang belum
    const force  = (mode === 'all' && row.dataset.done === '1');

    const result = await processOne(id, menu, judul, true, force);
    done++;

    const pct        = Math.round((done / total) * 100);
    fill.style.width = pct + '%';
    pText.textContent = `${done} / ${total} artikel diproses`;
    pPct.textContent  = pct + '%';
    el('progressCount').textContent = `${done}/${total}`;

    const icon = result.ok ? '✅' : '❌';
    const msg  = result.msg ?? (result.ok ? 'OK' : 'Error');
    logEl.textContent += `${icon} [${done}/${total}] ${judul.substring(0,50)} — ${msg}\n`;
    logEl.scrollTop    = logEl.scrollHeight;

    // Update row appearance
    const statusEl = el('status-' + id);
    if (statusEl) {
      if (result.ok) {
        statusEl.className   = 'seo-status-done';
        statusEl.innerHTML   = '✓ Done';
        row.dataset.done = '1';
      } else {
        statusEl.className   = 'seo-status-pending';
        statusEl.textContent = '✗ Error';
      }
    }
    const btnEl = el('btn-' + id);
    if (btnEl) {
      btnEl.disabled = false;
      btnEl.textContent = '↻ Re-generate';
      btnEl.setAttribute('onclick',
        `processOne('${id}','${menu}','${judul.replace(/'/g,"\\'")}',false,true)`
      );
    }

    await sleep(500); // jeda agar tidak flood API Gemini/Groq
  }

  pText.textContent = `✅ Selesai! ${done} artikel diproses.`;
  pPct.textContent  = '100%';
  logEl.textContent += `\n🎉 SELESAI — ${done} artikel berhasil diproses.\n`;

  el('btnAll').disabled     = false;
  el('btnPending').disabled = false;
  el('progressCount').textContent = '';
  batchRunning = false;

  // Refresh stats
  setTimeout(loadStats, 800);
}

// ── Load stats only ───────────────────────────────────────────────────────
async function loadStats() {
  const sr = await fetch(API, {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({ action: 'stats' }),
  });
  const sd = await sr.json();
  if (sd.success) {
    el('statTotal').textContent   = sd.total_art;
    el('statDone').textContent    = sd.done_art;
    el('statPending').textContent = sd.pending_art;
    el('statImg').textContent     = sd.total_img;
  }
}

// ── Clear cache ───────────────────────────────────────────────────────────
async function clearFileCache() {
  if (!confirm('Hapus semua file cache lokal ImageSeoGenerator?\nData Supabase tidak akan terpengaruh.')) return;
  const btn = el('btnClearCache');
  btn.disabled = true;
  try {
    const res  = await fetch(API, {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({ action: 'clear_cache' }),
    });
    const data = await res.json();
    if (data.success) {
      toast('Cache Dihapus', data.msg, 'success');
    } else {
      toast('Error', data.error, 'error');
    }
  } catch(e) {
    toast('Error', 'Gagal menghubungi server', 'error');
  }
  btn.disabled = false;
}

// ── Filter tabs ───────────────────────────────────────────────────────────
function setFilter(f, btn) {
  currentFilter = f;
  document.querySelectorAll('.seo-filter-tab').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  renderTable();
}

// ── Utils ─────────────────────────────────────────────────────────────────
const menuLabels = { berita: 'Berita', kronik: 'Kronik', historia: 'Historia' };

function el(id)           { return document.getElementById(id); }
function esc(s)            { return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c]); }
function escQ(s)           { return String(s).replace(/'/g, "\\'").replace(/"/g, '&quot;'); }
function sleep(ms)         { return new Promise(r => setTimeout(r, ms)); }

function showTableError(msg) {
  el('seoTableBody').innerHTML = `
    <tr><td colspan="6" style="text-align:center;color:var(--danger);padding:30px">
      ⚠️ ${esc(msg)}
    </td></tr>`;
}
</script>

<?php adminFooter(); ?>
