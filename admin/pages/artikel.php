<?php
/**
 * admin/pages/artikel.php
 * Halaman list artikel — Berita · Kronik · Historia
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

if (empty($allowedMenus)) {
    http_response_code(403); die(renderAccessDenied());
}

$activeMenu = $_GET['menu'] ?? $allowedMenus[0];
if (!in_array($activeMenu, $allowedMenus)) $activeMenu = $allowedMenus[0];

$menuLabels = [
    'berita'   => 'Liputan Berita',
    'kronik'   => 'Kronik SMDTBA',
    'historia' => 'Historia Gereja',
];
$canCreate = $isSuperadmin || in_array('create', $permsMap[$activeMenu] ?? []);

adminHeader('Kelola Artikel', 'artikel', $user);
?>
<style>
/* ── Menu tabs ───────────────────────────────────────────────────── */
.menu-tabs {
  display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 22px;
}
.menu-tab {
  padding: 7px 18px; border-radius: 20px; border: 1px solid var(--border);
  background: transparent; color: var(--text-secondary); font-size: 13px;
  font-weight: 500; cursor: pointer; transition: all .15s;
  text-decoration: none; display: inline-flex; align-items: center; gap: 7px;
}
.menu-tab:hover  { border-color: var(--accent); color: var(--accent); }
.menu-tab.active { background: var(--accent-dim); border-color: var(--accent); color: var(--accent); }

/* ── Desktop table styles ────────────────────────────────────────── */
.art-thumb {
  width: 52px; height: 38px; object-fit: cover; border-radius: 4px;
  border: 1px solid var(--border); background: var(--bg-card2); display: block;
}
.art-thumb-empty {
  width: 52px; height: 38px; border-radius: 4px; border: 1px solid var(--border);
  background: var(--bg-card2); display: flex; align-items: center;
  justify-content: center; color: var(--text-muted);
}
.art-judul {
  font-weight: 500; font-size: 13.5px; max-width: 260px;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.art-meta { font-size: 11.5px; color: var(--text-muted); margin-top: 2px; }

/* ── Status badges ───────────────────────────────────────────────── */
.st-published {
  background: rgba(60,179,113,.12); color: #3cb371;
  padding: 2px 10px; border-radius: 12px; font-size: 11.5px; white-space: nowrap;
}
.st-revisi {
  background: rgba(245,158,11,.15); color: #f59e0b;
  border: 1px solid rgba(245,158,11,.3);
  padding: 2px 10px; border-radius: 12px; font-size: 11.5px; white-space: nowrap;
  font-weight: 500; display: inline-flex; align-items: center; gap: 4px;
}
.btn-catatan-chip {
  display: inline-flex; align-items: center; gap: 4px;
  background: rgba(245,158,11,.12); color: #d97706;
  border: 1px solid rgba(245,158,11,.35);
  border-radius: 12px; padding: 2px 8px; font-size: 11px;
  cursor: pointer; text-decoration: none; font-weight: 500;
  transition: all .15s; margin-left: 4px;
}
.btn-catatan-chip:hover {
  background: rgba(245,158,11,.22); color: #b45309;
  border-color: #f59e0b;
}
.art-card-btn.revisi {
  border-color: rgba(245,158,11,.4); color: #f59e0b;
}

/* Modal Catatan Revisi & Form Tolak */
.modal-revisi-overlay {
  position: fixed; inset: 0; background: rgba(0,0,0,.75);
  backdrop-filter: blur(4px); z-index: 100000;
  display: flex; align-items: center; justify-content: center;
  padding: 16px; opacity: 0; visibility: hidden; transition: all .2s ease;
}
.modal-revisi-overlay.open { opacity: 1; visibility: visible; }
.modal-revisi-card {
  background: #1c1815; border: 1px solid rgba(201,168,76,.3);
  box-shadow: 0 16px 40px rgba(0,0,0,.6); border-radius: 14px;
  width: 100%; max-width: 480px; padding: 24px; color: #ede6dc;
  animation: scaleIn .2s ease forwards;
}
@keyframes scaleIn { from { transform: scale(0.95); opacity: 0; } to { transform: scale(1); opacity: 1; } }

.st-draft {
  background: rgba(224,154,82,.1); color: var(--warning);
  padding: 2px 10px; border-radius: 12px; font-size: 11.5px; white-space: nowrap;
}

/* ── Mobile card list ────────────────────────────────────────────── */
.art-card-list { display: none; flex-direction: column; }
.pagination[data-for="cardList"] { display: none; }

.art-card {
  /* Pastikan card tidak overflow — box-sizing dan width eksplisit */
  box-sizing: border-box;
  width: 100%;
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 14px 16px;
  background: var(--bg-card);
  border-bottom: 1px solid var(--border);
  transition: background .15s;
  overflow: hidden;           /* kunci: cegah child overflow ke luar */
}
.art-card:last-child { border-bottom: none; }
.art-card:active { background: var(--bg-card2); }

/* Thumbnail — ukuran tetap, tidak ikut mengecil */
.art-card-thumb {
  width: 72px; height: 54px;
  border-radius: 6px; object-fit: cover;
  border: 1px solid var(--border); background: var(--bg-card2);
  flex-shrink: 0; display: block;
}
.art-card-thumb-empty {
  width: 72px; height: 54px;
  border-radius: 6px; border: 1px solid var(--border);
  background: var(--bg-card2); flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  color: var(--text-muted);
}

/* Body — flex:1 + min-width:0 adalah kunci agar teks wrap, bukan overflow */
.art-card-body {
  flex: 1;
  min-width: 0;          /* CRITICAL: tanpa ini flex child tidak mau menyusut */
  overflow: hidden;
}

/* Judul — wrap 2 baris, tidak pernah lebih lebar dari parent */
.art-card-judul {
  font-size: 13.5px; font-weight: 600; color: var(--text-primary);
  line-height: 1.45; margin-bottom: 5px;
  /* clamp ke 2 baris */
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  word-break: break-word;    /* pecah kata panjang jika perlu */
}

/* Meta row — wrap normal, semua elemen boleh pindah ke baris baru */
.art-card-meta {
  display: flex; align-items: center;
  gap: 6px; flex-wrap: wrap;
  margin-bottom: 9px;
}
/* Teks penulis bisa dipotong tapi tidak paksa lebar */
.art-card-penulis {
  font-size: 11px; color: var(--text-muted);
  overflow: hidden; text-overflow: ellipsis;
  white-space: nowrap; max-width: 120px;
}

/* Tombol aksi — SELALU wrap, tidak pernah overflow */
.art-card-actions {
  display: flex; gap: 6px;
  flex-wrap: wrap;           /* biarkan tombol turun ke baris baru */
  align-items: center;
}
.art-card-btn {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 6px 14px; border-radius: 20px;  /* pill shape */
  font-size: 12px; font-weight: 500;
  font-family: 'DM Sans', sans-serif;
  border: 1px solid var(--border);
  background: var(--bg-card2);
  color: var(--text-secondary);
  cursor: pointer; transition: all .15s;
  text-decoration: none;
  white-space: nowrap;
  flex-shrink: 0;
}
.art-card-btn:active { opacity: .75; }
.art-card-btn.edit   { border-color: rgba(82,148,224,.35); color: var(--info); }
.art-card-btn.publish{ border-color: rgba(60,179,113,.35); color: var(--success); }
.art-card-btn.unpub  { border-color: rgba(224,154,82,.35); color: var(--warning); }
.art-card-btn.del    { border-color: rgba(224,82,82,.35);  color: var(--danger); }

/* ── FAB tulis artikel (mobile only) ────────────────────────────── */
.fab-write {
  display: none;
  position: fixed; bottom: 22px; right: 18px; z-index: 500;
  width: 54px; height: 54px; border-radius: 50%;
  background: var(--accent); color: #1a1410;
  border: none; cursor: pointer;
  box-shadow: 0 4px 20px rgba(201,168,76,.45);
  align-items: center; justify-content: center;
  transition: transform .15s, box-shadow .15s;
  text-decoration: none;
}
.fab-write:active {
  transform: scale(.95);
  box-shadow: 0 2px 12px rgba(201,168,76,.3);
}

/* ── Mobile filter bar ───────────────────────────────────────────── */
.mobile-filter {
  display: none;
  gap: 8px; padding: 12px 14px;
  background: var(--bg-card2);
  border-bottom: 1px solid var(--border);
  box-sizing: border-box; width: 100%;
}
.mobile-filter .search-wrap { flex: 1; min-width: 0; max-width: none; }
.mobile-filter .form-select  { width: 110px; flex-shrink: 0; font-size: 13px; }

/* ── Responsive: ≤640px ──────────────────────────────────────────── */
@media (max-width: 640px) {

  /* Pastikan tidak ada yang overflow secara horizontal */
  .main-content { overflow-x: hidden; }

  /* Sembunyikan tabel, tampilkan card list */
  #tableContainer .table-wrapper { display: none; }
  .art-card-list  { display: flex; }

  /* Paginator tabel desktop ikut disembunyikan; paginator card mobile ditampilkan */
  .pagination[data-for="dataTable"] { display: none; }
  .pagination[data-for="cardList"]  { display: flex; }

  /* Sembunyikan toolbar desktop, tampilkan mobile filter */
  .card > .toolbar { display: none; }
  .mobile-filter   { display: flex; }

  /* Sembunyikan tombol header, tampilkan FAB */
  .btn-tulis-desktop { display: none; }
  .fab-write { display: flex; }

  /* Page header compact */
  .page-header { margin-bottom: 14px; flex-wrap: nowrap; gap: 8px; }
  .page-header-left h1 { font-size: 18px; }
  .page-header-left p  { font-size: 12px; margin-top: 1px; }

  /* Menu tabs: scroll horizontal, tidak wrap */
  .menu-tabs {
    flex-wrap: nowrap;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none;
    gap: 6px;
    padding-bottom: 14px;
    margin-bottom: 0;
  }
  .menu-tabs::-webkit-scrollbar { display: none; }
  .menu-tab { flex-shrink: 0; padding: 6px 16px; font-size: 12.5px; }

  /* Card container — no padding, full bleed */
  .card {
    padding: 0;
    border-radius: var(--radius);
    overflow: hidden;
  }

  /* Counter */
  #rowCount-mobile {
    display: block;
    padding: 10px 16px 6px;
    font-size: 12px; color: var(--text-muted);
    border-bottom: 1px solid var(--border);
  }

  /* Padding bawah agar card terakhir tidak ketutup FAB */
  .art-card-list { padding-bottom: 80px; }
}
</style>

<!-- ── Page header ─────────────────────────────────────────────────── -->
<div class="page-header">
  <div class="page-header-left">
    <h1>Kelola Artikel</h1>
    <p>Tulis, edit, dan publish artikel paroki</p>
  </div>
  <?php if ($canCreate): ?>
  <a href="/admin/pages/artikel-editor.php?menu=<?= e($activeMenu) ?>"
     class="btn btn-primary btn-tulis-desktop">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15">
      <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
    </svg>
    Tulis Artikel Baru
  </a>
  <?php endif; ?>
</div>

<!-- Menu tabs -->
<div class="menu-tabs">
  <?php foreach ($allowedMenus as $m): ?>
  <a href="?menu=<?= e($m) ?>" class="menu-tab <?= $m === $activeMenu ? 'active' : '' ?>">
    <?= e($menuLabels[$m] ?? $m) ?>
    <span class="badge badge-gray" id="cnt_<?= e($m) ?>" style="font-size:10px">…</span>
  </a>
  <?php endforeach; ?>
</div>

<!-- ── Mobile filter bar (tampil di mobile saja) ──────────────────── -->
<div class="mobile-filter">
  <div class="search-wrap">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
    </svg>
    <input type="text" class="form-control" id="searchInputMobile"
           placeholder="Cari judul…" oninput="syncSearch(this.value)">
  </div>
  <select class="form-select" id="filterStatusMobile" onchange="syncFilter(this.value)">
    <option value="">Semua</option>
    <option value="published">Published</option>
    <option value="draft">Draft</option>
    <option value="revisi">Perlu Revisi</option>
  </select>
</div>

<!-- ── Card / Tabel ────────────────────────────────────────────────── -->
<div class="card">

  <!-- Desktop toolbar -->
  <div class="toolbar">
    <div class="toolbar-left">
      <div class="search-wrap">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
        </svg>
        <input type="text" class="form-control" id="searchInput"
               placeholder="Cari judul / penulis…">
      </div>
      <select class="form-select" id="filterStatus" style="max-width:140px" onchange="applyFilter()">
        <option value="">Semua Status</option>
        <option value="published">Published</option>
        <option value="draft">Draft</option>
    <option value="revisi">Perlu Revisi</option>
      </select>
    </div>
    <div class="toolbar-right">
      <span id="rowCount" style="font-size:13px;color:var(--text-secondary)">Memuat…</span>
    </div>
  </div>

  <div id="loadingState" style="text-align:center;padding:40px"><div class="spinner"></div></div>

  <!-- Desktop table -->
  <div id="tableContainer" style="display:none">
    <div class="table-wrapper">
      <table class="data-table" id="dataTable">
        <thead>
          <tr>
            <th style="width:60px">Thumb</th>
            <th>Judul</th>
            <th>Status</th>
            <th>Tanggal</th>
            <th style="width:120px">Aksi</th>
          </tr>
        </thead>
        <tbody id="tableBody"></tbody>
      </table>
    </div>
    <!-- Mobile card list (di dalam tableContainer agar ikut show/hide) -->
    <span id="rowCount-mobile"></span>
    <div class="art-card-list" id="cardList"></div>
  </div>

  <div id="emptyState" class="empty-state" style="display:none">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="44" height="44">
      <path d="M4 19.5A2.5 2.5 0 016.5 17H20"/>
      <path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/>
    </svg>
    <p>Belum ada artikel di menu ini.</p>
    <?php if ($canCreate): ?>
    <a href="/admin/pages/artikel-editor.php?menu=<?= e($activeMenu) ?>"
       class="btn btn-primary" style="margin-top:12px">
      Tulis Artikel Pertama
    </a>
    <?php endif; ?>
  </div>
</div>

<?php if ($canCreate): ?>
<!-- FAB tulis artikel — mobile only -->
<a href="/admin/pages/artikel-editor.php?menu=<?= e($activeMenu) ?>"
   class="fab-write" title="Tulis Artikel Baru">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="22" height="22">
    <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
  </svg>
</a>
<?php endif; ?>

<!-- Modal 1: Form Minta Revisi (Editor/Superadmin) -->
<div class="modal-revisi-overlay" id="modalRejectArticle" onclick="if(event.target===this)closeRejectModal()">
  <div class="modal-revisi-card">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
      <div style="display:flex;align-items:center;gap:8px">
        <div style="width:32px;height:32px;border-radius:8px;background:rgba(245,158,11,.15);border:1px solid rgba(245,158,11,.3);display:flex;align-items:center;justify-content:center;color:#f59e0b">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        </div>
        <div>
          <h3 style="margin:0;font-size:16px;font-family:'Playfair Display',serif;color:#f5debb">Minta Revisi Artikel</h3>
          <div style="font-size:11.5px;color:#a89f91" id="rejectArticleTitle">Artikel</div>
        </div>
      </div>
      <button onclick="closeRejectModal()" style="background:none;border:none;color:#a89f91;font-size:20px;cursor:pointer;line-height:1">&times;</button>
    </div>
    <div style="margin-bottom:16px">
      <label style="display:block;font-size:12px;font-weight:600;color:#c9a84c;margin-bottom:6px;text-transform:uppercase;letter-spacing:.05em">
        Catatan Alasan Ditolak / Saran Perbaikan:
      </label>
      <textarea id="rejectNotesInput" class="form-control" rows="4" style="width:100%;resize:vertical;font-size:13px;padding:10px 12px;background:#14110e;border:1px solid rgba(255,255,255,.15);border-radius:8px;color:#ede6dc" placeholder="Tuliskan poin perbaikan secara jelas, misal: judul kurang spesifik, foto buram, atau butuh penambahan nama romo..."></textarea>
      <div style="font-size:11px;color:#8c8275;margin-top:5px">Catatan ini akan langsung terbaca oleh penulis artikel saat login ke admin.</div>
    </div>
    <input type="hidden" id="rejectArticleId" value="">
    <div style="display:flex;gap:10px;justify-content:flex-end">
      <button type="button" class="btn btn-secondary" onclick="closeRejectModal()" style="padding:7px 16px;font-size:13px">Batal</button>
      <button type="button" class="btn btn-primary" id="btnSubmitReject" onclick="submitRejectArticle()" style="padding:7px 18px;font-size:13px;background:#d97706;border-color:#b45309">
        Kirim Catatan Revisi
      </button>
    </div>
  </div>
</div>

<!-- Modal 2: Lihat Catatan Revisi (Penulis / Admin) -->
<div class="modal-revisi-overlay" id="modalViewNotes" onclick="if(event.target===this)closeViewNotesModal()">
  <div class="modal-revisi-card">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
      <div style="display:flex;align-items:center;gap:8px">
        <div style="width:32px;height:32px;border-radius:8px;background:rgba(245,158,11,.15);border:1px solid rgba(245,158,11,.3);display:flex;align-items:center;justify-content:center;color:#f59e0b">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
        </div>
        <div>
          <h3 style="margin:0;font-size:16px;font-family:'Playfair Display',serif;color:#f5debb">Catatan & Saran Redaksi</h3>
          <div style="font-size:11.5px;color:#a89f91" id="viewNotesArticleTitle">Artikel</div>
        </div>
      </div>
      <button onclick="closeViewNotesModal()" style="background:none;border:none;color:#a89f91;font-size:20px;cursor:pointer;line-height:1">&times;</button>
    </div>
    <div style="background:#14110e;border:1px solid rgba(245,158,11,.25);border-radius:8px;padding:14px;margin-bottom:16px">
      <div style="font-size:11.5px;color:#c9a84c;margin-bottom:6px;font-weight:600" id="viewNotesReviewerInfo">Dari Redaksi:</div>
      <div style="font-size:13px;color:#ede6dc;line-height:1.6;white-space:pre-wrap" id="viewNotesContent">—</div>
    </div>
    <div style="display:flex;gap:10px;justify-content:flex-end">
      <button type="button" class="btn btn-secondary" onclick="closeViewNotesModal()" style="padding:7px 16px;font-size:13px">Tutup</button>
      <a href="#" id="viewNotesEditLink" class="btn btn-primary" style="padding:7px 18px;font-size:13px;text-decoration:none;display:inline-flex;align-items:center;gap:6px">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
        Edit & Perbaiki Artikel
      </a>
    </div>
  </div>
</div>

<script>
const ACTIVE_MENU   = '<?= e($activeMenu) ?>';
const ALLOWED_MENUS = <?= json_encode($allowedMenus) ?>;
const CAN_PUBLISH   = <?= json_encode($isSuperadmin || in_array('publish', $permsMap[$activeMenu] ?? [])) ?>;
let allData = [];

// ── Load ──────────────────────────────────────────────────────────────
async function loadData() {
  document.getElementById('loadingState').style.display = 'block';
  document.getElementById('tableContainer').style.display = 'none';
  document.getElementById('emptyState').style.display = 'none';
  try {
    const res = await apiPost('/admin/api/artikel.php', { action: 'list', menu: ACTIVE_MENU });
    if (!res.success) throw new Error(res.error);
    allData = res.data || [];
    const el = document.getElementById('cnt_' + ACTIVE_MENU);
    if (el) el.textContent = allData.length;
    applyFilter();
  } catch(e) {
    toast('Error', e.message, 'error');
  } finally {
    document.getElementById('loadingState').style.display = 'none';
  }
}

// ── Filter ────────────────────────────────────────────────────────────
function applyFilter() {
  const q  = document.getElementById('searchInput').value.toLowerCase();
  const st = document.getElementById('filterStatus').value;
  const filtered = allData.filter(a => {
    const mQ  = !q  || (a.judul||'').toLowerCase().includes(q) || (a.penulis||'').toLowerCase().includes(q);
    const mSt = !st || (a.status||'draft') === st;
    return mQ && mSt;
  });
  renderTable(filtered);
  renderCards(filtered);
}

// Sync filter mobile ↔ desktop
function syncSearch(val) {
  document.getElementById('searchInput').value = val;
  applyFilter();
}
function syncFilter(val) {
  document.getElementById('filterStatus').value = val;
  applyFilter();
}

document.getElementById('searchInput').addEventListener('input', function() {
  document.getElementById('searchInputMobile').value = this.value;
  applyFilter();
});

function toCdnArticleUrl(url) {
  if (!url) return '';
  if (/^https?:\/\//i.test(url)) {
    if (url.includes('parokitulungagung.org/img/artikel/')) {
      return url.replace(/https?:\/\/[^\/]+\/img\/artikel\//i, 'https://img.parokitulungagung.org/artikel/');
    }
    return url;
  }
  const cleaned = url.replace(/^\/?(img\/)?artikel\//i, '').replace(/^\//, '');
  return 'https://img.parokitulungagung.org/artikel/' + cleaned;
}

// ── Render Desktop Table ──────────────────────────────────────────────
function renderTable(data) {
  document.getElementById('rowCount').textContent = data.length + ' artikel';
  const tbody = document.getElementById('tableBody');
  if (!data.length) {
    document.getElementById('tableContainer').style.display = 'none';
    document.getElementById('emptyState').style.display = 'block';
    return;
  }
  document.getElementById('tableContainer').style.display = 'block';
  document.getElementById('emptyState').style.display = 'none';

  document.getElementById('tableBody').innerHTML = data.map(art => {
    const artId = escHtml(art.id || art._id || '');
    const thumbUrl = toCdnArticleUrl(art.thumbnail);
    const thumb = thumbUrl
      ? `<img src="${escHtml(thumbUrl)}" class="art-thumb" alt=""
              onerror="this.style.display='none'">`
      : `<div class="art-thumb-empty">
           <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                width="16" height="16">
             <rect x="3" y="3" width="18" height="18" rx="2"/>
             <circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/>
           </svg>
         </div>`;

    let statusBadge = '';
    if (art.status === 'published') {
      statusBadge = `<span class="st-published">✓ Published</span>`;
    } else if (art.status === 'revisi') {
      const notesJson = escHtml(JSON.stringify({ id: artId, judul: art.judul||'', catatan: art.catatan_revisi||'', reviewer: art.reviewer||'' }));
      statusBadge = `<div style="display:flex;align-items:center;flex-wrap:wrap;gap:4px">
        <span class="st-revisi">⚠ Perlu Revisi</span>
        <button type="button" class="btn-catatan-chip" onclick='openViewNotesModal(${notesJson})' title="Lihat catatan perbaikan">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="11" height="11"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
          Catatan
        </button>
      </div>`;
    } else {
      statusBadge = `<span class="st-draft">◷ Draft</span>`;
    }

    const tgl = (art.tanggal || art.created_at || '').substring(0, 10);

    const btnEdit = art._can_edit
      ? `<a href="/admin/pages/artikel-editor.php?menu=${ACTIVE_MENU}&id=${artId}"
            class="btn btn-icon btn-sm" title="Edit">
           <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                width="14" height="14">
             <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
             <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
           </svg>
         </a>` : '';

    let btnPublish = '';
    let btnReject = '';
    if (art._can_publish && CAN_PUBLISH) {
      if (art.status === 'published') {
        btnPublish = `<button class="btn btn-icon btn-sm"
              style="color:var(--warning);border-color:rgba(224,154,82,.3)"
              onclick="togglePublish('${artId}','unpublish')" title="Unpublish">
             <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14">
               <circle cx="12" cy="12" r="10"/><line x1="8" y1="12" x2="16" y2="12"/>
             </svg>
           </button>`;
      } else {
        btnPublish = `<button class="btn btn-icon btn-sm"
              style="color:var(--success);border-color:rgba(60,179,113,.3)"
              onclick="togglePublish('${artId}','publish')" title="Publish Artikel">
             <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14">
               <polyline points="20 6 9 17 4 12"/>
             </svg>
           </button>`;
        const rejectDataJson = escHtml(JSON.stringify({ id: artId, judul: art.judul||'', catatan: art.catatan_revisi||'' }));
        btnReject = `<button class="btn btn-icon btn-sm"
              style="color:#f59e0b;border-color:rgba(245,158,11,.35)"
              onclick='openRejectModal(${rejectDataJson})' title="Minta Revisi / Beri Catatan">
             <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14">
               <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
               <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
             </svg>
           </button>`;
      }
    }

    const btnDel = art._can_delete
      ? `<button class="btn btn-icon btn-sm"
              style="color:var(--danger);border-color:rgba(224,82,82,.3)"
              onclick="delArtikel('${artId}','${escHtml((art.judul||'').replace(/'/g,"\\\'"))}')"
              title="Hapus">
           <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                width="14" height="14">
             <polyline points="3 6 5 6 21 6"/>
             <path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a1 1 0 011-1h4a1 1 0 011 1v2"/>
           </svg>
         </button>` : '';

    return `<tr>
      <td>${thumb}</td>
      <td>
        <div class="art-judul" title="${escHtml(art.judul||'')}">${escHtml(art.judul||'—')}</div>
        <div class="art-meta">${escHtml(art.penulis||'')}${art.tags ? ' · ' + escHtml(art.tags) : ''}</div>
      </td>
      <td>${statusBadge}</td>
      <td style="font-size:12px;color:var(--text-secondary);white-space:nowrap">${tgl||'—'}</td>
      <td><div class="actions">${btnEdit}${btnPublish}${btnReject}${btnDel}</div></td>
    </tr>`;
  }).join('');

  initPagination('dataTable', 20);
}

// ── Render Mobile Cards ───────────────────────────────────────────────
function renderCards(data) {
  const list    = document.getElementById('cardList');
  const counter = document.getElementById('rowCount-mobile');
  if (!list) return;

  counter.textContent = data.length + ' artikel';

  if (!data.length) { list.innerHTML = ''; return; }

  list.innerHTML = data.map(art => {
    const artId = escHtml(art.id || art._id || '');
    const tgl   = (art.tanggal || art.created_at || '').substring(0, 10);
    const thumbUrl = toCdnArticleUrl(art.thumbnail);

    const thumb = thumbUrl
      ? `<img src="${escHtml(thumbUrl)}" class="art-card-thumb" alt=""
              onerror="this.style.display='none'">`
      : `<div class="art-card-thumb-empty">
           <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                width="20" height="20">
             <rect x="3" y="3" width="18" height="18" rx="2"/>
             <circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/>
           </svg>
         </div>`;

    let statusBadge = '';
    if (art.status === 'published') {
      statusBadge = `<span class="st-published">✓ Published</span>`;
    } else if (art.status === 'revisi') {
      const notesJson = escHtml(JSON.stringify({ id: artId, judul: art.judul||'', catatan: art.catatan_revisi||'', reviewer: art.reviewer||'' }));
      statusBadge = `<span class="st-revisi">⚠ Perlu Revisi</span>
        <button type="button" class="btn-catatan-chip" onclick='openViewNotesModal(${notesJson})'>💬 Catatan</button>`;
    } else {
      statusBadge = `<span class="st-draft">◷ Draft</span>`;
    }

    // Tombol aksi — teks + ikon agar mudah disentuh
    const btnEdit = art._can_edit
      ? `<a href="/admin/pages/artikel-editor.php?menu=${ACTIVE_MENU}&id=${artId}"
            class="art-card-btn edit">
           <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                width="12" height="12">
             <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
             <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
           </svg>
           Edit
         </a>` : '';

    let btnPublish = '';
    let btnReject = '';
    if (art._can_publish && CAN_PUBLISH) {
      if (art.status === 'published') {
        btnPublish = `<button class="art-card-btn unpub" onclick="togglePublish('${artId}','unpublish')">
             <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12">
               <circle cx="12" cy="12" r="10"/><line x1="8" y1="12" x2="16" y2="12"/>
             </svg>
             Unpublish
           </button>`;
      } else {
        btnPublish = `<button class="art-card-btn publish" onclick="togglePublish('${artId}','publish')">
             <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12">
               <polyline points="20 6 9 17 4 12"/>
             </svg>
             Publish
           </button>`;
        const rejectDataJson = escHtml(JSON.stringify({ id: artId, judul: art.judul||'', catatan: art.catatan_revisi||'' }));
        btnReject = `<button class="art-card-btn revisi" onclick='openRejectModal(${rejectDataJson})'>
             <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12">
               <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
             </svg>
             Revisi
           </button>`;
      }
    }

    const btnDel = art._can_delete
      ? `<button class="art-card-btn del"
              onclick="delArtikel('${artId}','${escHtml((art.judul||'').replace(/'/g,"\\\'"))}')">
           <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                width="12" height="12">
             <polyline points="3 6 5 6 21 6"/>
             <path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a1 1 0 011-1h4a1 1 0 011 1v2"/>
           </svg>
           Hapus
         </button>` : '';

    return `<div class="art-card">
      ${thumb}
      <div class="art-card-body">
        <div class="art-card-judul">${escHtml(art.judul||'—')}</div>
        <div class="art-card-meta">
          ${statusBadge}
          ${tgl ? `<span style="font-size:11px;color:var(--text-muted)">${tgl}</span>` : ''}
          ${art.penulis ? `<span class="art-card-penulis">${escHtml(art.penulis)}</span>` : ''}
        </div>
        <div class="art-card-actions">
          ${btnEdit}${btnPublish}${btnReject}${btnDel}
        </div>
      </div>
    </div>`;
  }).join('');

  initPagination('cardList', 20, ':scope > .art-card');
}

// ── Actions ───────────────────────────────────────────────────────────
async function togglePublish(id, action) {
  confirmDialog(
    action === 'publish' ? 'Publish Artikel?' : 'Unpublish Artikel?',
    action === 'publish'
      ? 'Artikel akan tampil di website.'
      : 'Artikel akan disembunyikan dari website.',
    async () => {
      try {
        const res = await apiPost('/admin/api/artikel.php', { action, menu: ACTIVE_MENU, id });
        if (!res.success) throw new Error(res.error);
        toast('Berhasil',
          action === 'publish' ? 'Artikel dipublish.' : 'Artikel di-unpublish.',
          'success');
        loadData();
      } catch(e) { toast('Error', e.message, 'error'); }
    }, action === 'unpublish'
  );
}

async function delArtikel(id, judul) {
  confirmDialog('Hapus Artikel?', `"${judul}" akan dihapus permanen.`, async () => {
    try {
      const res = await apiPost('/admin/api/artikel.php', { action: 'delete', menu: ACTIVE_MENU, id });
      if (!res.success) throw new Error(res.error);
      toast('Berhasil', 'Artikel dihapus.', 'success');
      loadData();
    } catch(e) { toast('Error', e.message, 'error'); }
  });
}

// ── Init ──────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
  loadData();
  ALLOWED_MENUS.forEach(m => {
    apiPost('/admin/api/artikel.php', { action: 'list', menu: m })
      .then(r => {
        if (r.success) {
          const el = document.getElementById('cnt_' + m);
          if (el) el.textContent = r.data.length;
        }
      }).catch(() => {});
  });
});

// ── Modal Tolak / Minta Revisi Handler ────────────────────────────────
function openRejectModal(data) {
  document.getElementById('rejectArticleId').value = data.id || '';
  document.getElementById('rejectArticleTitle').textContent = data.judul || 'Artikel';
  document.getElementById('rejectNotesInput').value = data.catatan || '';
  document.getElementById('modalRejectArticle').classList.add('open');
  setTimeout(() => document.getElementById('rejectNotesInput').focus(), 100);
}
function closeRejectModal() {
  document.getElementById('modalRejectArticle').classList.remove('open');
}
async function submitRejectArticle() {
  const id = document.getElementById('rejectArticleId').value;
  const catatan = document.getElementById('rejectNotesInput').value.trim();
  if (!catatan) {
    toast('Perhatian', 'Harap isi catatan / alasan revisi terlebih dahulu.', 'warning');
    return;
  }
  const btn = document.getElementById('btnSubmitReject');
  btn.disabled = true;
  btn.textContent = 'Menyimpan…';
  try {
    const res = await apiPost('/admin/api/artikel.php', {
      action: 'reject',
      menu: ACTIVE_MENU,
      id,
      catatan
    });
    if (!res.success) throw new Error(res.error || 'Gagal mengirim catatan revisi');
    toast('Berhasil', 'Artikel ditandai perlu revisi dengan catatan.', 'success');
    closeRejectModal();
    loadData();
  } catch (e) {
    toast('Gagal', e.message || 'Terjadi kesalahan sistem.', 'error');
  } finally {
    btn.disabled = false;
    btn.textContent = 'Kirim Catatan Revisi';
  }
}

// ── Modal Lihat Catatan Revisi Handler ────────────────────────────────
function openViewNotesModal(data) {
  document.getElementById('viewNotesArticleTitle').textContent = data.judul || 'Artikel';
  document.getElementById('viewNotesContent').textContent = data.catatan || '(Tidak ada catatan terlampir)';
  const reviewerText = data.reviewer ? `Dari Redaksi (${escHtml(data.reviewer)}):` : 'Dari Redaksi:';
  document.getElementById('viewNotesReviewerInfo').textContent = reviewerText;
  document.getElementById('viewNotesEditLink').href = `/admin/pages/artikel-editor.php?menu=${ACTIVE_MENU}&id=${data.id}`;
  document.getElementById('modalViewNotes').classList.add('open');
}
function closeViewNotesModal() {
  document.getElementById('modalViewNotes').classList.remove('open');
}

</script>
<?php adminFooter(); ?>