<?php
/**
 * admin/pages/master.php
 * Halaman Master Data: Lingkungan · Koordinator · Bidang
 * Akses: superadmin + admin dengan permission 'master'
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
adminBoot();

// Akses: superadmin atau admin dengan permission 'master'
$user = requireLogin();
$isSuperadmin = $user['role'] === ROLE_SUPERADMIN;
if (!$isSuperadmin) {
    $permsMap = getPermissionsMap($user);
    if (!array_key_exists('master', $permsMap)) {
        http_response_code(403); die(renderAccessDenied());
    }
}

$defaultPeriode = getCurrentPeriode();
$canEdit = $isSuperadmin || in_array('edit', getPermissionsMap($user)['master'] ?? []);
$canCreate = $isSuperadmin || in_array('create', getPermissionsMap($user)['master'] ?? []);
$canDelete = $isSuperadmin || in_array('delete', getPermissionsMap($user)['master'] ?? []);

// Tab aktif dari URL
$activeTab = in_array($_GET['tab'] ?? '', ['lingkungan','koordinator','bidang'])
    ? $_GET['tab']
    : 'lingkungan';

adminHeader('Master Data', 'master', $user);
?>

<style>
.master-tabs{display:flex;gap:0;border-bottom:2px solid var(--border);margin-bottom:24px}
.master-tab{display:flex;align-items:center;gap:8px;padding:12px 22px;background:none;border:none;border-bottom:3px solid transparent;color:var(--text-secondary);font-family:'DM Sans',sans-serif;font-size:13.5px;font-weight:500;cursor:pointer;transition:all .18s;white-space:nowrap;margin-bottom:-2px}
.master-tab:hover{color:var(--text-primary);background:rgba(255,255,255,.03)}
.master-tab.active{color:var(--accent);border-bottom-color:var(--accent);font-weight:600}
.master-tab svg{flex-shrink:0}
.master-panel{display:none}.master-panel.active{display:block}

/* Foto preview cell */
.foto-cell{display:flex;align-items:center;gap:8px}
.foto-thumb{width:34px;height:34px;border-radius:50%;object-fit:cover;border:2px solid var(--border);flex-shrink:0;background:var(--bg-card2)}
.foto-thumb-placeholder{width:34px;height:34px;border-radius:50%;background:var(--bg-card2);border:2px solid var(--border);display:flex;align-items:center;justify-content:center;color:var(--text-muted);flex-shrink:0}
</style>

<div class="page-header">
  <div class="page-header-left">
    <h1>Master Data</h1>
    <p>Kelola data referensi: Lingkungan, Koordinator, dan Bidang per periode</p>
  </div>
</div>

<!-- ── Tabs ── -->
<div class="master-tabs">
  <button class="master-tab <?= $activeTab==='lingkungan'?'active':'' ?>" onclick="switchMasterTab('lingkungan')">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="16" height="16"><path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
    Lingkungan
  </button>
  <button class="master-tab <?= $activeTab==='koordinator'?'active':'' ?>" onclick="switchMasterTab('koordinator')">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="16" height="16"><circle cx="12" cy="7" r="4"/><path d="M5.5 21a7 7 0 0113 0"/></svg>
    Koordinator
  </button>
  <button class="master-tab <?= $activeTab==='bidang'?'active':'' ?>" onclick="switchMasterTab('bidang')">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="16" height="16"><path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
    Bidang DPP/BGKP
  </button>
</div>

<!-- ════════════════════════════════════════════════════════════
     TAB: LINGKUNGAN
════════════════════════════════════════════════════════════ -->
<div class="master-panel <?= $activeTab==='lingkungan'?'active':'' ?>" id="panelLingkungan">
  <div class="card">
    <div class="toolbar">
      <div class="toolbar-left" style="flex-wrap:wrap;gap:8px">
        <div class="search-wrap">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          <input type="text" class="form-control" id="searchLingk" placeholder="Cari lingkungan...">
        </div>
        <select class="form-select" id="filterLingkWilayah" style="max-width:150px" onchange="renderLingkungan()">
          <option value="">Semua Wilayah</option>
          <option>Wilayah 1</option><option>Wilayah 2</option><option>Wilayah 3</option><option>Stasi</option>
        </select>
      </div>
      <div class="toolbar-right" style="gap:8px">
        <span id="countLingk" style="font-size:13px;color:var(--text-secondary)">Memuat...</span>
        <?php if ($canCreate): ?>
        <button class="btn btn-primary btn-sm" onclick="openLingkModal()">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Tambah
        </button>
        <?php endif; ?>
      </div>
    </div>
    <div id="loadingLingk" style="text-align:center;padding:40px"><div class="spinner"></div></div>
    <div id="tableLingkWrap" style="display:none">
      <div class="table-wrapper">
        <table class="data-table" id="tableLingk">
          <thead><tr><th>Wilayah</th><th>Lingkungan</th><th style="width:80px">Aksi</th></tr></thead>
          <tbody id="bodyLingk"></tbody>
        </table>
      </div>
    </div>
    <div id="emptyLingk" class="empty-state" style="display:none"><p>Belum ada data lingkungan.</p></div>
  </div>
</div>

<!-- ════════════════════════════════════════════════════════════
     TAB: KOORDINATOR
════════════════════════════════════════════════════════════ -->
<div class="master-panel <?= $activeTab==='koordinator'?'active':'' ?>" id="panelKoordinator">
  <div class="card">
    <div class="toolbar">
      <div class="toolbar-left" style="flex-wrap:wrap;gap:8px">
        <div class="search-wrap">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          <input type="text" class="form-control" id="searchKoor" placeholder="Cari koordinator...">
        </div>
        <select class="form-select" id="filterKoorPeriode" style="max-width:150px" onchange="loadKoordinator()">
          <option value="">Semua Periode</option>
        </select>
      </div>
      <div class="toolbar-right" style="gap:8px">
        <span id="countKoor" style="font-size:13px;color:var(--text-secondary)">Memuat...</span>
        <?php if ($canCreate): ?>
        <button class="btn btn-primary btn-sm" onclick="openKoorModal()">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Tambah
        </button>
        <?php endif; ?>
      </div>
    </div>
    <div id="loadingKoor" style="text-align:center;padding:40px"><div class="spinner"></div></div>
    <div id="tableKoorWrap" style="display:none">
      <div class="table-wrapper">
        <table class="data-table" id="tableKoor">
          <thead><tr><th>Foto</th><th>Nama</th><th>Periode</th><th style="width:80px">Aksi</th></tr></thead>
          <tbody id="bodyKoor"></tbody>
        </table>
      </div>
    </div>
    <div id="emptyKoor" class="empty-state" style="display:none"><p>Belum ada data koordinator.</p></div>
  </div>
</div>

<!-- ════════════════════════════════════════════════════════════
     TAB: BIDANG
════════════════════════════════════════════════════════════ -->
<div class="master-panel <?= $activeTab==='bidang'?'active':'' ?>" id="panelBidang">
  <div class="card">
    <div class="toolbar">
      <div class="toolbar-left" style="flex-wrap:wrap;gap:8px">
        <div class="search-wrap">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          <input type="text" class="form-control" id="searchBidang" placeholder="Cari bidang...">
        </div>
        <select class="form-select" id="filterBidangTipe" style="max-width:120px" onchange="loadBidang()">
          <option value="">Semua</option><option value="DPP">DPP</option><option value="BGKP">BGKP</option>
        </select>
        <select class="form-select" id="filterBidangPeriode" style="max-width:150px" onchange="loadBidang()">
          <option value="">Semua Periode</option>
        </select>
      </div>
      <div class="toolbar-right" style="gap:8px">
        <span id="countBidang" style="font-size:13px;color:var(--text-secondary)">Memuat...</span>
        <?php if ($canCreate): ?>
        <button class="btn btn-primary btn-sm" onclick="openBidangModal()">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Tambah
        </button>
        <?php endif; ?>
      </div>
    </div>
    <div id="loadingBidang" style="text-align:center;padding:40px"><div class="spinner"></div></div>
    <div id="tableBidangWrap" style="display:none">
      <div class="table-wrapper">
        <table class="data-table" id="tableBidang">
          <thead><tr><th>Periode</th><th>Tipe</th><th>Bidang</th><th style="width:80px">Aksi</th></tr></thead>
          <tbody id="bodyBidang"></tbody>
        </table>
      </div>
    </div>
    <div id="emptyBidang" class="empty-state" style="display:none"><p>Belum ada data bidang.</p></div>
  </div>
</div>

<!-- ═══ MODAL LINGKUNGAN ═════════════════════════════════════ -->
<div class="modal-overlay" id="modalLingk">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title" id="titleLingk">Tambah Lingkungan</span>
      <button class="modal-close">&times;</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="lingkId">
      <div class="form-grid">
        <div class="form-group">
          <label>Wilayah <span class="required">*</span></label>
          <select class="form-select" id="lingkWilayah">
            <option value="">— Pilih —</option>
            <option>Wilayah 1</option><option>Wilayah 2</option><option>Wilayah 3</option><option>Stasi</option>
          </select>
        </div>
        <div class="form-group">
          <label>Nama Lingkungan <span class="required">*</span></label>
          <input type="text" class="form-control" id="lingkNama" placeholder="St. Agnes">
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('modalLingk')">Batal</button>
      <button class="btn btn-primary" id="btnSaveLingk" onclick="saveLingkungan()">Simpan</button>
    </div>
  </div>
</div>

<!-- ═══ MODAL KOORDINATOR ════════════════════════════════════ -->
<div class="modal-overlay" id="modalKoor">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title" id="titleKoor">Tambah Koordinator</span>
      <button class="modal-close">&times;</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="koorId">
      <input type="hidden" id="koorFotoValue">
      <div class="form-grid">
        <div class="form-group full">
          <label>Periode <span class="required">*</span></label>
          <select class="form-select" id="koorPeriode">
            <?php foreach (generatePeriodeList() as $p): ?>
            <option value="<?= e($p) ?>" <?= $p===$defaultPeriode?'selected':'' ?>><?= e($p) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group full">
          <label>Nama Lengkap <span class="required">*</span></label>
          <input type="text" class="form-control" id="koorNama" placeholder="Nama koordinator wilayah">
        </div>
        <div class="form-group full">
          <label>Foto</label>
          <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
            <div style="position:relative;width:64px;height:64px;flex-shrink:0">
              <img id="koorFotoPreview" src="" alt="" style="width:64px;height:64px;border-radius:50%;object-fit:cover;border:2px solid var(--border);display:none">
              <div id="koorFotoPlaceholder" style="width:64px;height:64px;border-radius:50%;background:var(--bg-card2);border:2px dashed var(--border);display:flex;align-items:center;justify-content:center;color:var(--text-muted)">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="24" height="24"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
              </div>
            </div>
            <div style="display:flex;flex-direction:column;gap:6px">
              <button type="button" class="btn btn-secondary btn-sm" onclick="FotoPicker.open('koorFotoValue','koorFotoPreview',onKoorFotoSelected)">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                Pilih / Upload Foto
              </button>
              <span style="font-size:11px;color:var(--text-muted)">Dari galeri atau upload baru</span>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('modalKoor')">Batal</button>
      <button class="btn btn-primary" id="btnSaveKoor" onclick="saveKoordinator()">Simpan</button>
    </div>
  </div>
</div>

<!-- ═══ MODAL BIDANG ═════════════════════════════════════════ -->
<div class="modal-overlay" id="modalBidang">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title" id="titleBidang">Tambah Bidang</span>
      <button class="modal-close">&times;</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="bidangId">
      <div class="form-grid">
        <div class="form-group full">
          <label>Periode <span class="required">*</span></label>
          <select class="form-select" id="bidangPeriode">
            <?php foreach (generatePeriodeList() as $p): ?>
            <option value="<?= e($p) ?>" <?= $p===$defaultPeriode?'selected':'' ?>><?= e($p) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Tipe <span class="required">*</span></label>
          <select class="form-select" id="bidangTipe">
            <option value="DPP">DPP</option><option value="BGKP">BGKP</option>
          </select>
        </div>
        <div class="form-group">
          <label>Nama Bidang <span class="required">*</span></label>
          <input type="text" class="form-control" id="bidangNama" placeholder="DPP Harian / Bidang Sumber...">
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('modalBidang')">Batal</button>
      <button class="btn btn-primary" id="btnSaveBidang" onclick="saveBidang()">Simpan</button>
    </div>
  </div>
</div>

<script src="/admin/js/foto-picker.js"></script>
<script>
const CAN_EDIT   = <?= json_encode($canEdit) ?>;
const CAN_DELETE = <?= json_encode($canDelete) ?>;
const DEFAULT_PERIODE = <?= json_encode($defaultPeriode) ?>;

// ── Tab switch ──────────────────────────────────────────────────────────
function switchMasterTab(tab) {
  document.querySelectorAll('.master-panel').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('.master-tab').forEach(t => t.classList.remove('active'));
  document.getElementById('panel' + tab.charAt(0).toUpperCase() + tab.slice(1)).classList.add('active');
  document.querySelectorAll('.master-tab').forEach(t => {
    if (t.textContent.trim().toLowerCase().startsWith(tab === 'bidang' ? 'bidang' : tab.charAt(0))) {
      t.classList.add('active');
    }
  });
  history.replaceState(null,'',location.pathname + '?tab=' + tab);
}

// ── Periode helper ──────────────────────────────────────────────────────
function populatePeriodeFilter(selId, data, colName, defaultVal) {
  const periodes = [...new Set(data.map(r=>r[colName]||'').filter(Boolean))].sort().reverse();
  const sel = document.getElementById(selId);
  while (sel.options.length > 1) sel.remove(1);
  periodes.forEach(p => {
    const o = document.createElement('option');
    o.value = p; o.textContent = p;
    if (p === defaultVal) o.selected = true;
    sel.appendChild(o);
  });
}

// ══════════════════════════════════════════════════
// LINGKUNGAN
// ══════════════════════════════════════════════════
let allLingk = [], editLingkId = null;

async function loadLingkungan() {
  document.getElementById('loadingLingk').style.display = 'block';
  document.getElementById('tableLingkWrap').style.display = 'none';
  const res = await apiPost('/admin/api/sheets.php', { action:'list', page:'master_lingkungan' });
  document.getElementById('loadingLingk').style.display = 'none';
  if (!res.success) { toast('Error', res.error, 'error'); return; }
  allLingk = res.data || [];
  renderLingkungan();
}

function renderLingkungan() {
  const q = document.getElementById('searchLingk').value.toLowerCase();
  const w = document.getElementById('filterLingkWilayah').value;
  const filtered = allLingk.filter(r =>
    (!w || r['Wilayah']===w) &&
    (!q || (r['Lingkungan']||'').toLowerCase().includes(q) || (r['Wilayah']||'').toLowerCase().includes(q))
  );
  document.getElementById('countLingk').textContent = filtered.length + ' lingkungan';
  const tbody = document.getElementById('bodyLingk');
  if (!filtered.length) {
    document.getElementById('tableLingkWrap').style.display = 'none';
    document.getElementById('emptyLingk').style.display = 'block';
    return;
  }
  document.getElementById('tableLingkWrap').style.display = 'block';
  document.getElementById('emptyLingk').style.display = 'none';
  tbody.innerHTML = filtered.map(r => `<tr>
    <td><span class="badge badge-gray" style="font-size:11px">${escHtml(r['Wilayah']||'')}</span></td>
    <td style="font-weight:500">${escHtml(r['Lingkungan']||'')}</td>
    <td><div class="actions">
      ${CAN_EDIT?`<button class="btn btn-icon btn-sm" onclick="editLingkungan(${r._id})">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
      </button>`:''}
      ${CAN_DELETE?`<button class="btn btn-icon btn-sm" style="color:var(--danger);border-color:rgba(224,82,82,.3)" onclick="deleteLingkungan(${r._id})">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a1 1 0 011-1h4a1 1 0 011 1v2"/></svg>
      </button>`:''}
    </div></td>
  </tr>`).join('');
}

document.getElementById('searchLingk').addEventListener('input', renderLingkungan);

function openLingkModal(id) {
  editLingkId = id || null;
  document.getElementById('titleLingk').textContent = id ? 'Edit Lingkungan' : 'Tambah Lingkungan';
  document.getElementById('lingkId').value = id || '';
  if (id) {
    const r = allLingk.find(x => x._id === id);
    if (r) {
      document.getElementById('lingkWilayah').value = r['Wilayah'] || '';
      document.getElementById('lingkNama').value    = r['Lingkungan'] || '';
    }
  } else {
    document.getElementById('lingkWilayah').value = '';
    document.getElementById('lingkNama').value    = '';
  }
  openModal('modalLingk');
}
function editLingkungan(id) { openLingkModal(id); }

async function saveLingkungan() {
  const btn  = document.getElementById('btnSaveLingk');
  const id   = document.getElementById('lingkId').value;
  const wil  = document.getElementById('lingkWilayah').value;
  const nama = document.getElementById('lingkNama').value.trim();
  if (!wil || !nama) { toast('Error','Wilayah dan Nama wajib diisi','error'); return; }
  btnLoading(btn, true);
  const res = await apiPost('/admin/api/sheets.php', {
    action: id ? 'update' : 'create', page: 'master_lingkungan',
    ...(id && {id}),
    data: { 'Wilayah': wil, 'Lingkungan': nama }
  });
  btnLoading(btn, false);
  if (res.success) { toast('Berhasil', id?'Data diperbarui':'Lingkungan ditambahkan','success'); closeModal('modalLingk'); loadLingkungan(); }
  else toast('Error', res.error, 'error');
}

async function deleteLingkungan(id) {
  const r = allLingk.find(x => x._id === id);
  confirmDialog('Hapus Lingkungan?', `"${r?.['Lingkungan']||'data ini'}" akan dihapus.`, async () => {
    const res = await apiPost('/admin/api/sheets.php', { action:'delete', page:'master_lingkungan', id });
    if (res.success) { toast('Berhasil','Dihapus','success'); loadLingkungan(); }
    else toast('Error',res.error,'error');
  });
}

// ══════════════════════════════════════════════════
// KOORDINATOR
// ══════════════════════════════════════════════════
let allKoor = [], editKoorId = null;

async function loadKoordinator() {
  document.getElementById('loadingKoor').style.display = 'block';
  document.getElementById('tableKoorWrap').style.display = 'none';
  const res = await apiPost('/admin/api/sheets.php', { action:'list', page:'master_koordinator' });
  document.getElementById('loadingKoor').style.display = 'none';
  if (!res.success) { toast('Error', res.error, 'error'); return; }
  allKoor = res.data || [];
  populatePeriodeFilter('filterKoorPeriode', allKoor, 'Periode', DEFAULT_PERIODE);
  renderKoordinator();
}

function renderKoordinator() {
  const q = document.getElementById('searchKoor').value.toLowerCase();
  const p = document.getElementById('filterKoorPeriode').value;
  const filtered = allKoor.filter(r =>
    (!p || r['Periode']===p) && (!q || (r['Nama']||'').toLowerCase().includes(q))
  );
  document.getElementById('countKoor').textContent = filtered.length + ' koordinator';
  if (!filtered.length) {
    document.getElementById('tableKoorWrap').style.display = 'none';
    document.getElementById('emptyKoor').style.display = 'block';
    return;
  }
  document.getElementById('tableKoorWrap').style.display = 'block';
  document.getElementById('emptyKoor').style.display = 'none';
  document.getElementById('bodyKoor').innerHTML = filtered.map(r => {
    const fotoFile = r['Foto'] || '';
    const fotoSrc  = fotoFile ? '/img/person/' + fotoFile : '';
    const fotoHtml = fotoSrc
      ? `<img class="foto-thumb" src="${escHtml(fotoSrc)}" alt="" onerror="this.style.display='none'">` 
      : `<div class="foto-thumb-placeholder"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="16" height="16"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></div>`;
    return `<tr>
      <td><div class="foto-cell">${fotoHtml}</div></td>
      <td style="font-weight:500">${escHtml(r['Nama']||'')}</td>
      <td><span class="badge badge-gold" style="font-size:10px">${escHtml(r['Periode']||'—')}</span></td>
      <td><div class="actions">
        ${CAN_EDIT?`<button class="btn btn-icon btn-sm" onclick="editKoordinator(${r._id})">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
        </button>`:''}
        ${CAN_DELETE?`<button class="btn btn-icon btn-sm" style="color:var(--danger);border-color:rgba(224,82,82,.3)" onclick="deleteKoordinator(${r._id})">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a1 1 0 011-1h4a1 1 0 011 1v2"/></svg>
        </button>`:''}
      </div></td>
    </tr>`;
  }).join('');
}

document.getElementById('searchKoor').addEventListener('input', renderKoordinator);

function onKoorFotoSelected(filename, url) {
  const prev = document.getElementById('koorFotoPreview');
  const plac = document.getElementById('koorFotoPlaceholder');
  if (filename) {
    prev.src = url; prev.style.display = 'block'; plac.style.display = 'none';
  } else {
    prev.style.display = 'none'; plac.style.display = 'flex';
  }
}

function openKoorModal(id) {
  editKoorId = id || null;
  document.getElementById('titleKoor').textContent = id ? 'Edit Koordinator' : 'Tambah Koordinator';
  document.getElementById('koorId').value = id || '';
  const prev = document.getElementById('koorFotoPreview');
  const plac = document.getElementById('koorFotoPlaceholder');
  if (id) {
    const r = allKoor.find(x => x._id === id);
    if (r) {
      document.getElementById('koorPeriode').value   = r['Periode'] || DEFAULT_PERIODE;
      document.getElementById('koorNama').value      = r['Nama'] || '';
      document.getElementById('koorFotoValue').value = r['Foto'] || '';
      if (r['Foto']) {
        prev.src = '/img/person/' + r['Foto']; prev.style.display = 'block'; plac.style.display = 'none';
      } else {
        prev.style.display = 'none'; plac.style.display = 'flex';
      }
    }
  } else {
    document.getElementById('koorPeriode').value   = DEFAULT_PERIODE;
    document.getElementById('koorNama').value      = '';
    document.getElementById('koorFotoValue').value = '';
    prev.style.display = 'none'; plac.style.display = 'flex';
  }
  openModal('modalKoor');
}
function editKoordinator(id) { openKoorModal(id); }

async function saveKoordinator() {
  const btn  = document.getElementById('btnSaveKoor');
  const id   = document.getElementById('koorId').value;
  const per  = document.getElementById('koorPeriode').value;
  const nama = document.getElementById('koorNama').value.trim();
  const foto = document.getElementById('koorFotoValue').value;
  if (!nama) { toast('Error','Nama wajib diisi','error'); return; }
  btnLoading(btn, true);
  const res = await apiPost('/admin/api/sheets.php', {
    action: id ? 'update' : 'create', page: 'master_koordinator',
    ...(id && {id}),
    data: { 'Nama': nama, 'Periode': per, 'Foto': foto }
  });
  btnLoading(btn, false);
  if (res.success) { toast('Berhasil', id?'Data diperbarui':'Koordinator ditambahkan','success'); closeModal('modalKoor'); loadKoordinator(); }
  else toast('Error', res.error, 'error');
}

async function deleteKoordinator(id) {
  const r = allKoor.find(x => x._id === id);
  confirmDialog('Hapus Koordinator?', `"${r?.['Nama']||'data ini'}" akan dihapus.`, async () => {
    const res = await apiPost('/admin/api/sheets.php', { action:'delete', page:'master_koordinator', id });
    if (res.success) { toast('Berhasil','Dihapus','success'); loadKoordinator(); }
    else toast('Error',res.error,'error');
  });
}

// ══════════════════════════════════════════════════
// BIDANG
// ══════════════════════════════════════════════════
let allBidang = [], editBidangId = null;

async function loadBidang() {
  document.getElementById('loadingBidang').style.display = 'block';
  document.getElementById('tableBidangWrap').style.display = 'none';
  const res = await apiPost('/admin/api/sheets.php', { action:'list', page:'master_bidang' });
  document.getElementById('loadingBidang').style.display = 'none';
  if (!res.success) { toast('Error', res.error, 'error'); return; }
  allBidang = res.data || [];
  populatePeriodeFilter('filterBidangPeriode', allBidang, 'Periode', DEFAULT_PERIODE);
  renderBidang();
}

function renderBidang() {
  const q = document.getElementById('searchBidang').value.toLowerCase();
  const t = document.getElementById('filterBidangTipe').value;
  const p = document.getElementById('filterBidangPeriode').value;
  const filtered = allBidang.filter(r =>
    (!t || r['Tipe']===t) && (!p || r['Periode']===p) &&
    (!q || (r['Bidang']||'').toLowerCase().includes(q))
  );
  document.getElementById('countBidang').textContent = filtered.length + ' bidang';
  if (!filtered.length) {
    document.getElementById('tableBidangWrap').style.display = 'none';
    document.getElementById('emptyBidang').style.display = 'block';
    return;
  }
  document.getElementById('tableBidangWrap').style.display = 'block';
  document.getElementById('emptyBidang').style.display = 'none';
  document.getElementById('bodyBidang').innerHTML = filtered.map(r => `<tr>
    <td><span class="badge badge-gold" style="font-size:10px">${escHtml(r['Periode']||'—')}</span></td>
    <td><span class="badge ${r['Tipe']==='DPP'?'badge-gold':'badge-blue'}">${escHtml(r['Tipe']||'')}</span></td>
    <td style="font-size:13px">${escHtml(r['Bidang']||'')}</td>
    <td><div class="actions">
      ${CAN_EDIT?`<button class="btn btn-icon btn-sm" onclick="editBidang(${r._id})">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
      </button>`:''}
      ${CAN_DELETE?`<button class="btn btn-icon btn-sm" style="color:var(--danger);border-color:rgba(224,82,82,.3)" onclick="deleteBidang(${r._id})">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a1 1 0 011-1h4a1 1 0 011 1v2"/></svg>
      </button>`:''}
    </div></td>
  </tr>`).join('');
}

document.getElementById('searchBidang').addEventListener('input', renderBidang);

function openBidangModal(id) {
  editBidangId = id || null;
  document.getElementById('titleBidang').textContent = id ? 'Edit Bidang' : 'Tambah Bidang';
  document.getElementById('bidangId').value = id || '';
  if (id) {
    const r = allBidang.find(x => x._id === id);
    if (r) {
      document.getElementById('bidangPeriode').value = r['Periode'] || DEFAULT_PERIODE;
      document.getElementById('bidangTipe').value    = r['Tipe']    || 'DPP';
      document.getElementById('bidangNama').value    = r['Bidang']  || '';
    }
  } else {
    document.getElementById('bidangPeriode').value = DEFAULT_PERIODE;
    document.getElementById('bidangTipe').value    = 'DPP';
    document.getElementById('bidangNama').value    = '';
  }
  openModal('modalBidang');
}
function editBidang(id) { openBidangModal(id); }

async function saveBidang() {
  const btn  = document.getElementById('btnSaveBidang');
  const id   = document.getElementById('bidangId').value;
  const per  = document.getElementById('bidangPeriode').value;
  const tipe = document.getElementById('bidangTipe').value;
  const nama = document.getElementById('bidangNama').value.trim();
  if (!tipe || !nama) { toast('Error','Tipe dan Nama wajib diisi','error'); return; }
  btnLoading(btn, true);
  const res = await apiPost('/admin/api/sheets.php', {
    action: id ? 'update' : 'create', page: 'master_bidang',
    ...(id && {id}),
    data: { 'Tipe': tipe, 'Bidang': nama, 'Periode': per }
  });
  btnLoading(btn, false);
  if (res.success) { toast('Berhasil', id?'Data diperbarui':'Bidang ditambahkan','success'); closeModal('modalBidang'); loadBidang(); }
  else toast('Error', res.error, 'error');
}

async function deleteBidang(id) {
  const r = allBidang.find(x => x._id === id);
  confirmDialog('Hapus Bidang?', `"${r?.['Bidang']||'data ini'}" akan dihapus.`, async () => {
    const res = await apiPost('/admin/api/sheets.php', { action:'delete', page:'master_bidang', id });
    if (res.success) { toast('Berhasil','Dihapus','success'); loadBidang(); }
    else toast('Error',res.error,'error');
  });
}

// ── Init ────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
  loadLingkungan();
  loadKoordinator();
  loadBidang();
});

/* ── Mobile card-table labels ──────────────────────────────────────── */
(function(){
  function _applyLabels(tableId){
    if(window.innerWidth > 768) return;
    var t = document.getElementById(tableId);
    if(!t) return;
    var hdrs = Array.from(t.querySelectorAll('thead th')).map(function(th){ return th.textContent.trim(); });
    t.querySelectorAll('tbody tr').forEach(function(tr){
      Array.from(tr.querySelectorAll('td')).forEach(function(td, i){
        var h = hdrs[i] || '';
        if(!h || h==='Aksi' || h==='' || td.querySelector('.actions') || td.querySelector('.btn-icon')){
          td.classList.add('td-aksi');
        } else {
          if(!td.hasAttribute('data-label')) td.setAttribute('data-label', h);
        }
      });
    });
  }
  window._applyLabels = _applyLabels;
  // Patch all render functions for master.php
  document.addEventListener('DOMContentLoaded', function(){
    var tableMap = {
      renderLingkungan: 'tableLingk',
      renderKoordinator: 'tableKoor',
      renderBidang: 'tableBidang',
      renderTable: 'dataTable',
    };
    Object.keys(tableMap).forEach(function(fn){
      if(typeof window[fn] !== 'undefined'){
        var _orig = window[fn];
        window[fn] = function(data){
          _orig(data);
          _applyLabels(tableMap[fn]);
        };
      }
    });
  });
})();

</script>

<?php adminFooter(); ?>