<?php
/**
 * admin/pages/wilayah.php — Update v2
 * - Koordinator dipilih dari master_koordinator (per periode) dengan foto picker
 * - Ketua Lingkungan/Stasi dengan foto picker (upload/galeri img/person)
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
adminBoot();
$user           = requirePageAccess('wilayah');
$isSuperadmin   = $user['role'] === ROLE_SUPERADMIN;
$defaultPeriode = getCurrentPeriode();
adminHeader('Profil Wilayah', 'wilayah', $user);
?>

<style>
/* ── Foto picker inline preview ── */
.foto-pick-wrap {
  display: flex; align-items: center; gap: 12px; flex-wrap: wrap;
}
.foto-circle {
  position: relative; width: 64px; height: 64px; flex-shrink: 0;
}
.foto-circle img {
  width: 64px; height: 64px; border-radius: 50%; object-fit: cover;
  border: 2px solid var(--border); display: none;
}
.foto-circle .foto-placeholder {
  width: 64px; height: 64px; border-radius: 50%;
  background: var(--bg-card2); border: 2px dashed var(--border);
  display: flex; align-items: center; justify-content: center;
  color: var(--text-muted);
}
.foto-cell-sm {
  display: flex; align-items: center; gap: 8px;
}
.foto-thumb-sm {
  width: 32px; height: 32px; border-radius: 50%; object-fit: cover;
  border: 2px solid var(--border); flex-shrink: 0; background: var(--bg-card2);
}
.foto-thumb-none {
  width: 32px; height: 32px; border-radius: 50%;
  background: var(--bg-card2); border: 2px solid var(--border);
  display: flex; align-items: center; justify-content: center;
  color: var(--text-muted); flex-shrink: 0;
}
</style>

<div class="page-header">
  <div class="page-header-left">
    <h1>Profil Wilayah &amp; Lingkungan</h1>
    <p>Kelola data wilayah, lingkungan, ketua, dan koordinator</p>
  </div>
  <?php if (userCan($user, 'create')): ?>
  <button class="btn btn-primary" onclick="openAddModal()">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    Tambah Lingkungan
  </button>
  <?php endif; ?>
</div>

<div class="card">
  <div class="toolbar">
    <div class="toolbar-left" style="flex-wrap:wrap;gap:8px">
      <div class="search-wrap">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" class="form-control" id="searchInput" placeholder="Cari lingkungan / ketua...">
      </div>
      <select class="form-select" id="filterWilayah" style="max-width:150px" onchange="applyFilter()">
        <option value="">Semua Wilayah</option>
        <option>Wilayah 1</option><option>Wilayah 2</option><option>Wilayah 3</option><option>Stasi</option>
      </select>
      <select class="form-select" id="filterPeriode" style="max-width:140px" onchange="applyFilter()">
        <option value="">Semua Periode</option>
      </select>
    </div>
    <div class="toolbar-right"><span id="rowCount" style="font-size:13px;color:var(--text-secondary)">Memuat...</span></div>
  </div>
  <div id="loadingState" style="text-align:center;padding:40px"><div class="spinner"></div></div>
  <div id="tableContainer" style="display:none">
    <div class="table-wrapper">
      <table class="data-table" id="dataTable">
        <thead>
          <tr>
            <th>Periode</th><th>Wilayah</th><th>Lingkungan</th>
            <th>Ketua</th><th>Koordinator</th>
            <th id="thAksi" style="width:90px">Aksi</th>
          </tr>
        </thead>
        <tbody id="tableBody"></tbody>
      </table>
    </div>
  </div>
  <div id="emptyState" class="empty-state" style="display:none"><p>Belum ada data wilayah.</p></div>
</div>

<!-- ── MODAL FORM ─────────────────────────────────────────────── -->
<?php if (userCan($user, 'create') || userCan($user, 'edit')): ?>
<div class="modal-overlay" id="formModal">
  <div class="modal modal-lg">
    <div class="modal-header">
      <span class="modal-title" id="modalTitle">Tambah Lingkungan</span>
      <button class="modal-close">&times;</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="fieldRow">
      <div class="form-grid">

        <!-- Periode -->
        <div class="form-group full">
          <label>Periode <span class="required">*</span></label>
          <select class="form-select" id="fieldPeriode" onchange="onPeriodeChange()">
            <?php foreach (generatePeriodeList() as $p): ?>
            <option value="<?= e($p) ?>" <?= $p===$defaultPeriode?'selected':'' ?>><?= e($p) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Wilayah -->
        <div class="form-group">
          <label>Wilayah <span class="required">*</span></label>
          <select class="form-select" id="fieldWilayah" onchange="onWilayahChange()">
            <option value="">— Pilih Wilayah —</option>
            <option>Wilayah 1</option><option>Wilayah 2</option>
            <option>Wilayah 3</option><option>Stasi</option>
          </select>
        </div>

        <!-- Lingkungan (dari master lingkungan) -->
        <div class="form-group">
          <label>Lingkungan <span class="required">*</span></label>
          <select class="form-select" id="fieldLingkungan">
            <option value="">— Pilih Wilayah Dulu —</option>
          </select>
        </div>

        <!-- Ketua Lingkungan/Stasi -->
        <div class="form-group full" style="border-top:1px solid var(--border);padding-top:16px;margin-top:4px">
          <label style="font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);margin-bottom:10px;display:block">Ketua Lingkungan / Stasi</label>
          <div class="form-grid" style="gap:12px">
            <div class="form-group">
              <label>Nama Ketua</label>
              <input type="text" class="form-control" id="fieldKetua" placeholder="Nama lengkap ketua">
            </div>
            <div class="form-group">
              <label>Foto Ketua</label>
              <input type="hidden" id="fieldKetuaFoto">
              <div class="foto-pick-wrap">
                <div class="foto-circle">
                  <img id="ketuaFotoPreview" src="" alt="" onerror="this.style.display='none'">
                  <div class="foto-placeholder" id="ketuaFotoPlaceholder">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="24" height="24"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                  </div>
                </div>
                <button type="button" class="btn btn-secondary btn-sm" onclick="FotoPicker.open('fieldKetuaFoto','ketuaFotoPreview',onKetuaFotoSelected)">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                  Pilih / Upload Foto
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Koordinator Wilayah -->
        <div class="form-group full" style="border-top:1px solid var(--border);padding-top:16px">
          <label style="font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);margin-bottom:10px;display:block">Koordinator Wilayah</label>
          <div class="form-grid" style="gap:12px">
            <div class="form-group full">
              <label>Pilih Koordinator <small style="color:var(--text-muted)">(dari Master Koordinator periode ini)</small></label>
              <select class="form-select" id="fieldKoorSelect" onchange="onKoorSelectChange()">
                <option value="">— Pilih Koordinator —</option>
              </select>
            </div>
            <div class="form-group" id="koorCustomWrap" style="display:none">
              <label>Atau ketik nama koordinator</label>
              <input type="text" class="form-control" id="fieldKoordinator" placeholder="Nama koordinator (jika tidak ada di daftar)">
            </div>
            <div class="form-group" id="koorFotoPreviewWrap">
              <label>Foto Koordinator</label>
              <input type="hidden" id="fieldKoorFoto">
              <div class="foto-pick-wrap">
                <div class="foto-circle">
                  <img id="koorFotoPreview" src="" alt="" onerror="this.style.display='none'">
                  <div class="foto-placeholder" id="koorFotoPlaceholder">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="24" height="24"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                  </div>
                </div>
                <div>
                  <button type="button" class="btn btn-secondary btn-sm" onclick="FotoPicker.open('fieldKoorFoto','koorFotoPreview',onKoorFotoSelected)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    Pilih / Upload Foto
                  </button>
                  <div style="font-size:11px;color:var(--text-muted);margin-top:4px">Jika dipilih dari daftar, foto otomatis terisi</div>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('formModal')">Batal</button>
      <button class="btn btn-primary" id="btnSave" onclick="submitForm()">Simpan</button>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- ── WARNING MODAL foto besar ────────────────────────────────── -->
<div class="modal-overlay" id="fotoWarningModal">
  <div class="modal" style="max-width:400px">
    <div class="modal-header" style="border-bottom-color:rgba(184,134,11,0.3)">
      <span class="modal-title" style="color:var(--accent)">⚠️ File Besar Terdeteksi</span>
    </div>
    <div class="modal-body" style="text-align:center;padding:24px">
      <div style="font-size:40px;margin-bottom:12px">📸</div>
      <p id="fotoWarningMsg" style="color:var(--text-secondary);line-height:1.6"></p>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="cancelFotoUpload()">Batal</button>
      <button class="btn btn-primary" onclick="proceedUpload()">Lanjutkan Kompresi</button>
    </div>
  </div>
</div>

<script src="/admin/js/foto-picker.js"></script>
<script>
const PAGE = 'wilayah';
const COLS = ['Wilayah','Lingkungan','Ketua','KoordinatorNama','Periode'];
let allData = [], masterLingkungan = [], masterKoordinator = [], editRow = null;
let pendingFile = null;

// ── Init ──────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
  if (!can('edit') && !can('delete')) {
    const th = document.getElementById('thAksi'); if(th) th.style.display='none';
  }
  loadData();
  loadMasterData();
});

// ── Load Data Wilayah ─────────────────────────────────────────────────
async function loadData() {
  document.getElementById('loadingState').style.display = 'block';
  document.getElementById('tableContainer').style.display = 'none';
  const res = await apiPost('/admin/api/sheets.php', { action:'list', page:PAGE });
  document.getElementById('loadingState').style.display = 'none';
  if (!res.success) { toast('Error', res.error, 'error'); return; }
  allData = res.data || [];
  const periodes = [...new Set(allData.map(r=>r['Periode']).filter(Boolean))].sort().reverse();
  const sel = document.getElementById('filterPeriode');
  while (sel.options.length > 1) sel.remove(1);
  periodes.forEach(p => { const o=document.createElement('option'); o.value=p; o.textContent=p; sel.appendChild(o); });
  const aktif = '<?= e($defaultPeriode) ?>';
  if (periodes.includes(aktif)) sel.value = aktif;
  applyFilter();
}

// ── Load Master Data ──────────────────────────────────────────────────
async function loadMasterData() {
  const [lingkRes, koorRes] = await Promise.all([
    apiPost('/admin/api/sheets.php', { action:'list', page:'master_lingkungan' }),
    apiPost('/admin/api/sheets.php', { action:'list', page:'master_koordinator' }),
  ]);
  if (lingkRes.success) masterLingkungan = lingkRes.data || [];
  if (koorRes.success)  masterKoordinator = koorRes.data || [];
}

// ── Render Tabel ──────────────────────────────────────────────────────
function renderTable(data) {
  const tbody = document.getElementById('tableBody');
  document.getElementById('rowCount').textContent = data.length + ' lingkungan';
  if (!data.length) {
    document.getElementById('emptyState').style.display = 'block';
    document.getElementById('tableContainer').style.display = 'none'; return;
  }
  document.getElementById('tableContainer').style.display = 'block';
  document.getElementById('emptyState').style.display = 'none';
  const showAksi = can('edit') || can('delete');
  tbody.innerHTML = data.map(row => {
    const ketuaFoto = row['Foto'] || '';
    const koorFoto  = row['KoordinatorFoto'] || '';
    const koorNama  = row['KoordinatorNama'] || row['Koordinator'] || '';

    const ketuaHtml = ketuaFoto
      ? `<div class="foto-cell-sm"><img class="foto-thumb-sm" src="/img/person/${escHtml(ketuaFoto)}" alt="" onerror="this.style.display='none'"><span>${escHtml(row['Ketua']||'')}</span></div>`
      : `<span>${escHtml(row['Ketua']||'')}</span>`;

    const koorHtml = koorNama
      ? (koorFoto
          ? `<div class="foto-cell-sm"><img class="foto-thumb-sm" src="/img/person/${escHtml(koorFoto)}" alt="" onerror="this.style.display='none'"><span>${escHtml(koorNama)}</span></div>`
          : `<span style="color:var(--text-secondary)">${escHtml(koorNama)}</span>`)
      : `<span style="color:var(--text-muted)">—</span>`;

    return `<tr>
      <td><span class="badge badge-gold" style="font-size:10px">${escHtml(row['Periode']||'—')}</span></td>
      <td><span class="badge ${row['Wilayah']==='Stasi'?'badge-blue':'badge-gray'}" style="font-size:11px">${escHtml(row['Wilayah']||'')}</span></td>
      <td style="font-weight:500">${escHtml(row['Lingkungan']||'')}</td>
      <td>${ketuaHtml}</td>
      <td>${koorHtml}</td>
      ${showAksi ? `<td><div class="actions">
        ${can('edit') ? `<button class="btn btn-icon btn-sm" onclick="openEditModal(${row._id})">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
        </button>` : ''}
        ${can('delete') ? `<button class="btn btn-icon btn-sm" onclick="deleteRow(${row._id})" style="color:var(--danger);border-color:rgba(224,82,82,0.3)">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a1 1 0 011-1h4a1 1 0 011 1v2"/></svg>
        </button>` : ''}
      </div></td>` : '<td style="display:none"></td>'}
    </tr>`;
  }).join('');
}

function applyFilter() {
  const q = document.getElementById('searchInput').value.toLowerCase();
  const w = document.getElementById('filterWilayah').value;
  const p = document.getElementById('filterPeriode').value;
  renderTable(allData.filter(r =>
    (!w || r['Wilayah']===w) && (!p || r['Periode']===p) &&
    (!q || COLS.some(c => (r[c]||'').toLowerCase().includes(q)))
  ));
}
document.getElementById('searchInput').addEventListener('input', applyFilter);

// ── Dropdown helpers ──────────────────────────────────────────────────
function onPeriodeChange() {
  const periode = document.getElementById('fieldPeriode').value;
  populateKoorSelect('', periode);
}

function onWilayahChange() {
  const wilayah = document.getElementById('fieldWilayah').value;
  populateLingkunganSelect(wilayah, '');
}

function populateLingkunganSelect(wilayah, selected) {
  const sel = document.getElementById('fieldLingkungan');
  sel.innerHTML = '<option value="">— Pilih Lingkungan —</option>';
  if (!wilayah) { sel.innerHTML = '<option value="">— Pilih Wilayah Dulu —</option>'; return; }
  masterLingkungan
    .filter(m => m['Wilayah'] === wilayah)
    .forEach(m => {
      const o = document.createElement('option');
      o.value = m['Lingkungan']; o.textContent = m['Lingkungan'];
      if (m['Lingkungan'] === selected) o.selected = true;
      sel.appendChild(o);
    });
}

function populateKoorSelect(selected, periode) {
  const sel = document.getElementById('fieldKoorSelect');
  sel.innerHTML = '<option value="">— Pilih Koordinator —</option>';
  const custom = document.createElement('option');
  custom.value = '__custom__'; custom.textContent = '+ Ketik nama sendiri';
  sel.appendChild(custom);
  masterKoordinator
    .filter(k => !periode || !k['Periode'] || k['Periode']===periode)
    .forEach(k => {
      const o = document.createElement('option');
      o.value = JSON.stringify({ nama: k['Nama'], foto: k['Foto']||'' });
      o.textContent = k['Nama'] + (k['Periode'] ? ' (' + k['Periode'] + ')' : '');
      if (k['Nama']===selected) o.selected = true;
      sel.appendChild(o);
    });
}

function onKoorSelectChange() {
  const sel = document.getElementById('fieldKoorSelect');
  const customWrap = document.getElementById('koorCustomWrap');
  const val = sel.value;
  if (val === '__custom__') {
    customWrap.style.display = 'block';
    document.getElementById('fieldKoordinator').value = '';
    document.getElementById('fieldKoorFoto').value = '';
    setKoorFotoPreview('');
  } else if (val) {
    customWrap.style.display = 'none';
    try {
      const d = JSON.parse(val);
      document.getElementById('fieldKoordinator').value = d.nama;
      document.getElementById('fieldKoorFoto').value = d.foto;
      setKoorFotoPreview(d.foto);
    } catch(e) {}
  } else {
    customWrap.style.display = 'none';
    document.getElementById('fieldKoordinator').value = '';
    document.getElementById('fieldKoorFoto').value = '';
    setKoorFotoPreview('');
  }
}

function setKoorFotoPreview(foto) {
  const prev = document.getElementById('koorFotoPreview');
  const plac = document.getElementById('koorFotoPlaceholder');
  if (foto) { prev.src = '/img/person/' + foto; prev.style.display = 'block'; plac.style.display = 'none'; }
  else { prev.style.display = 'none'; plac.style.display = 'flex'; }
}

function onKoorFotoSelected(filename, url) {
  document.getElementById('fieldKoorFoto').value = filename;
  setKoorFotoPreview(filename);
}

function onKetuaFotoSelected(filename, url) {
  document.getElementById('fieldKetuaFoto').value = filename;
  const prev = document.getElementById('ketuaFotoPreview');
  const plac = document.getElementById('ketuaFotoPlaceholder');
  if (filename) { prev.src = url; prev.style.display = 'block'; plac.style.display = 'none'; }
  else { prev.style.display = 'none'; plac.style.display = 'flex'; }
}

// ── Modal Tambah ──────────────────────────────────────────────────────
function openAddModal() {
  if (!can('create')) { toast('Akses Ditolak','Tidak ada izin tambah data','error'); return; }
  editRow = null;
  document.getElementById('modalTitle').textContent = 'Tambah Lingkungan';
  document.getElementById('fieldRow').value      = '';
  document.getElementById('fieldPeriode').value  = '<?= e($defaultPeriode) ?>';
  document.getElementById('fieldWilayah').value  = '';
  document.getElementById('fieldKetua').value    = '';
  document.getElementById('fieldKetuaFoto').value = '';
  document.getElementById('fieldKoordinator').value = '';
  document.getElementById('fieldKoorFoto').value = '';
  document.getElementById('fieldLingkungan').innerHTML = '<option value="">— Pilih Wilayah Dulu —</option>';
  document.getElementById('ketuaFotoPreview').style.display = 'none';
  document.getElementById('ketuaFotoPlaceholder').style.display = 'flex';
  setKoorFotoPreview('');
  document.getElementById('koorCustomWrap').style.display = 'none';
  populateKoorSelect('', '<?= e($defaultPeriode) ?>');
  openModal('formModal');
}

// ── Modal Edit ────────────────────────────────────────────────────────
function openEditModal(rowNum) {
  if (!can('edit')) { toast('Akses Ditolak','Tidak ada izin edit data','error'); return; }
  const row = allData.find(r => r._id===rowNum); if (!row) return;
  editRow = rowNum;
  const periode = row['Periode'] || '<?= e($defaultPeriode) ?>';
  document.getElementById('modalTitle').textContent = 'Edit Lingkungan';
  document.getElementById('fieldRow').value      = rowNum;
  document.getElementById('fieldPeriode').value  = periode;
  document.getElementById('fieldWilayah').value  = row['Wilayah'] || '';
  document.getElementById('fieldKetua').value    = row['Ketua'] || '';
  document.getElementById('fieldKetuaFoto').value = row['Foto'] || '';
  document.getElementById('fieldKoordinator').value = row['KoordinatorNama'] || row['Koordinator'] || '';
  document.getElementById('fieldKoorFoto').value = row['KoordinatorFoto'] || '';

  // Preview ketua
  const ketuaFoto = row['Foto'] || '';
  const kprev = document.getElementById('ketuaFotoPreview');
  const kplac = document.getElementById('ketuaFotoPlaceholder');
  if (ketuaFoto) { kprev.src='/img/person/'+ketuaFoto; kprev.style.display='block'; kplac.style.display='none'; }
  else { kprev.style.display='none'; kplac.style.display='flex'; }

  setKoorFotoPreview(row['KoordinatorFoto'] || '');

  // Populate dropdowns
  populateLingkunganSelect(row['Wilayah']||'', row['Lingkungan']||'');
  populateKoorSelect(row['KoordinatorNama']||'', periode);

  // Set koordinator select
  const koorNama = row['KoordinatorNama'] || row['Koordinator'] || '';
  const sel = document.getElementById('fieldKoorSelect');
  let matched = false;
  for (let i=0; i<sel.options.length; i++) {
    try {
      const d = JSON.parse(sel.options[i].value);
      if (d.nama === koorNama) { sel.selectedIndex = i; matched = true; break; }
    } catch(e) {}
  }
  if (!matched && koorNama) {
    sel.value = '__custom__';
    document.getElementById('koorCustomWrap').style.display = 'block';
  } else {
    document.getElementById('koorCustomWrap').style.display = 'none';
  }

  openModal('formModal');
}

// ── Submit ────────────────────────────────────────────────────────────
async function submitForm() {
  const btn = document.getElementById('btnSave');
  const isEdit = !!editRow;
  if (isEdit && !can('edit'))    { toast('Akses Ditolak','Tidak ada izin edit','error'); return; }
  if (!isEdit && !can('create')) { toast('Akses Ditolak','Tidak ada izin tambah','error'); return; }

  const wilayah   = document.getElementById('fieldWilayah').value;
  const lingkungan = document.getElementById('fieldLingkungan').value;
  if (!wilayah || !lingkungan) { toast('Error','Wilayah dan Lingkungan wajib diisi','error'); return; }

  const koordinator = document.getElementById('fieldKoordinator').value.trim();
  const koorFoto    = document.getElementById('fieldKoorFoto').value;
  const ketuaFoto   = document.getElementById('fieldKetuaFoto').value;

  const data = {
    'Wilayah'          : wilayah,
    'Lingkungan'       : lingkungan,
    'Ketua'            : document.getElementById('fieldKetua').value.trim(),
    'Foto'             : ketuaFoto,
    'Koordinator'      : koordinator,
    'KoordinatorNama'  : koordinator,
    'KoordinatorFoto'  : koorFoto,
    'Periode'          : document.getElementById('fieldPeriode').value,
  };

  btnLoading(btn, true);
  const res = await apiPost('/admin/api/sheets.php', {
    action: isEdit ? 'update' : 'create', page: PAGE,
    id: editRow, data
  });
  btnLoading(btn, false);
  if (res.success) {
    toast('Berhasil', isEdit?'Data diperbarui':'Lingkungan ditambahkan','success');
    closeModal('formModal'); loadData();
  } else toast('Error', res.error, 'error');
}

// ── Delete ────────────────────────────────────────────────────────────
async function deleteRow(rowNum) {
  if (!can('delete')) { toast('Akses Ditolak','Tidak ada izin hapus','error'); return; }
  const row = allData.find(r => r._id===rowNum);
  confirmDialog('Hapus Lingkungan?', `"${row?.['Lingkungan']||'data ini'}" akan dihapus.`, async () => {
    const res = await apiPost('/admin/api/sheets.php', { action:'delete', page:PAGE, id:rowNum });
    if (res.success) { toast('Berhasil','Dihapus','success'); loadData(); }
    else toast('Error', res.error,'error');
  });
}

function cancelFotoUpload() { closeModal('fotoWarningModal'); pendingFile = null; }
function proceedUpload() { closeModal('fotoWarningModal'); }

/* ── Mobile card-table labels (auto-injected) ──────────────────────── */
(function(){
  function _applyLabels(tableId){
    if(window.innerWidth > 768) return;
    var t = document.getElementById(tableId);
    if(!t) return;
    var hdrs = Array.from(t.querySelectorAll('thead th')).map(function(th){ return th.textContent.trim(); });
    t.querySelectorAll('tbody tr').forEach(function(tr){
      Array.from(tr.querySelectorAll('td')).forEach(function(td, i){
        var h = hdrs[i] || '';
        if(!h || h === 'Aksi' || td.querySelector('.actions') || td.querySelector('.btn-icon')){
          td.classList.add('td-aksi');
        } else {
          if(!td.hasAttribute('data-label')) td.setAttribute('data-label', h);
        }
      });
    });
  }
  window._applyLabels = _applyLabels;
  // Patch renderTable if it exists on this page
  document.addEventListener('DOMContentLoaded', function(){
    if(typeof renderTable !== 'undefined'){
      var _orig = renderTable;
      renderTable = function(data){
        _orig(data);
        _applyLabels('dataTable');
      };
    }
  });
})();

</script>

<?php adminFooter(); ?>