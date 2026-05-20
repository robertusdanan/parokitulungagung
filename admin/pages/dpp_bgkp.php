<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
adminBoot();
$user           = requirePageAccess('dpp_bgkp');
$isSuperadmin   = $user['role'] === ROLE_SUPERADMIN;
$defaultPeriode = getCurrentPeriode();
adminHeader('DPP & BGKP', 'dpp_bgkp', $user);
?>
<div class="page-header">
  <div class="page-header-left"><h1>Kepengurusan DPP &amp; BGKP</h1><p>Kelola data pengurus Dewan Paroki dan Badan Gerejawi</p></div>
  <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
    <?php if ($isSuperadmin): ?>
    <button class="btn btn-secondary" onclick="openMasterModal()">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
      Kelola Master Bidang
    </button>
    <?php endif; ?>
    <?php if (userCan($user, 'create')): ?>
    <button class="btn btn-primary" onclick="openAddModal()">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      Tambah Pengurus
    </button>
    <?php endif; ?>
  </div>
</div>

<div class="card">
  <div class="toolbar">
    <div class="toolbar-left" style="flex-wrap:wrap;gap:8px">
      <div class="search-wrap">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" class="form-control" id="searchInput" placeholder="Cari nama / bidang...">
      </div>
      <select class="form-select" id="filterTipe" style="max-width:130px" onchange="applyFilter()">
        <option value="">DPP &amp; BGKP</option><option value="DPP">DPP</option><option value="BGKP">BGKP</option>
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
        <thead><tr><th>Periode</th><th>Tipe</th><th>Bidang</th><th>Posisi</th><th>Nama</th><th id="thAksi" style="width:90px">Aksi</th></tr></thead>
        <tbody id="tableBody"></tbody>
      </table>
    </div>
  </div>
  <div id="emptyState" class="empty-state" style="display:none"><p>Belum ada data kepengurusan.</p></div>
</div>

<!-- ── MODAL FORM ─────────────────────────────────────────────────── -->
<?php if (userCan($user, 'create') || userCan($user, 'edit')): ?>
<div class="modal-overlay" id="formModal">
  <div class="modal">
    <div class="modal-header"><span class="modal-title" id="modalTitle">Tambah Pengurus</span><button class="modal-close">&times;</button></div>
    <div class="modal-body">
      <input type="hidden" id="fieldRow">
      <div class="form-grid">

        <!-- Periode -->
        <div class="form-group full">
          <label>Periode <span class="required">*</span></label>
          <select class="form-select" id="fieldPeriode">
            <?php foreach (generatePeriodeList() as $p): ?>
            <option value="<?= e($p) ?>" <?= $p === $defaultPeriode ? 'selected' : '' ?>><?= e($p) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Tipe -->
        <div class="form-group">
          <label>Tipe <span class="required">*</span></label>
          <select class="form-select" id="fieldTipe" onchange="onTipeChange()">
            <option value="DPP">DPP</option>
            <option value="BGKP">BGKP</option>
          </select>
        </div>

        <!-- Bidang (dari master, filter by Tipe) -->
        <div class="form-group">
          <label>Bidang <span class="required">*</span></label>
          <select class="form-select" id="fieldBidang">
            <option value="">— Pilih Tipe Dulu —</option>
          </select>
        </div>

        <!-- Posisi -->
        <div class="form-group full">
          <label>Posisi <span class="required">*</span></label>
          <input type="text" class="form-control" id="fieldPosisi" placeholder="Ketua / Wakil / Koordinator Sie ...">
        </div>

        <!-- Nama -->
        <div class="form-group full">
          <label>Nama Lengkap <span class="required">*</span></label>
          <input type="text" class="form-control" id="fieldNama" placeholder="Nama lengkap">
        </div>

        <!-- Foto (picker dari galeri atau upload) -->
        <div class="form-group full">
          <label>Foto</label>
          <input type="hidden" id="fieldFoto">
          <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
            <div style="position:relative;width:64px;height:64px;flex-shrink:0">
              <img id="fotoPreview" src="" alt=""
                   style="width:64px;height:64px;border-radius:50%;object-fit:cover;border:2px solid var(--border);display:none"
                   onerror="this.style.display='none';document.getElementById('fotoPlaceholder').style.display='flex'">
              <div id="fotoPlaceholder" style="width:64px;height:64px;border-radius:50%;background:var(--bg-card2);border:2px dashed var(--border);display:flex;align-items:center;justify-content:center;color:var(--text-muted)">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="24" height="24"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
              </div>
            </div>
            <div style="display:flex;flex-direction:column;gap:6px">
              <button type="button" class="btn btn-secondary btn-sm" onclick="FotoPicker.open('fieldFoto','fotoPreview',onFotoSelected)">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                Pilih / Upload Foto
              </button>
              <span style="font-size:11px;color:var(--text-muted)">Pilih dari galeri 86 foto atau upload baru</span>
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

<!-- ── MODAL MASTER BIDANG (superadmin) ──────────────────────────── -->
<?php if ($isSuperadmin): ?>
<div class="modal-overlay" id="masterModal">
  <div class="modal modal-lg">
    <div class="modal-header">
      <span class="modal-title">Kelola Master Bidang</span>
      <button class="modal-close" onclick="closeModal('masterModal')">&times;</button>
    </div>
    <div class="modal-body">
      <p style="font-size:13px;color:var(--text-secondary);margin-bottom:16px">Daftar Bidang untuk DPP dan BGKP. Digunakan sebagai pilihan di form tambah/edit data.</p>
      <div style="display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap">
        <select class="form-select" id="masterTipe" style="max-width:120px">
          <option value="DPP">DPP</option><option value="BGKP">BGKP</option>
        </select>
        <input type="text" class="form-control" id="masterBidang" placeholder="Nama bidang (DPP Harian / Bidang Sumber ...)" style="max-width:280px">
        <button class="btn btn-primary btn-sm" onclick="addMasterBidang()">+ Tambah</button>
      </div>
      <div id="masterLoadingState" style="text-align:center;padding:20px"><div class="spinner"></div></div>
      <div id="masterTableWrap" style="display:none">
        <div class="table-wrapper">
          <table class="data-table" id="masterTable">
            <thead><tr><th>Tipe</th><th>Bidang</th><th style="width:60px">Hapus</th></tr></thead>
            <tbody id="masterTableBody"></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>



<script>
const PAGE = 'dpp_bgkp'; const COLS = ['Bidang','Posisi','Nama','Tipe','Periode','Foto'];
let allData = [], masterBidang = [], editRow = null;

document.addEventListener('DOMContentLoaded', function() {
  if (!can('edit') && !can('delete')) { const th = document.getElementById('thAksi'); if(th) th.style.display='none'; }
  loadData();
  loadMasterBidang();
});

/* ── Load Data ─────────────────────────────────────────────────────── */
async function loadData() {
  document.getElementById('loadingState').style.display='block';
  document.getElementById('tableContainer').style.display='none';
  const res = await apiPost('/admin/api/sheets.php', { action:'list', page:PAGE });
  document.getElementById('loadingState').style.display='none';
  if (!res.success) { toast('Error', res.error,'error'); return; }
  allData = res.data || [];
  const periodes = [...new Set(allData.map(r => r['Periode']).filter(Boolean))].sort().reverse();
  const sel = document.getElementById('filterPeriode');
  while (sel.options.length > 1) sel.remove(1);
  periodes.forEach(p => { const o = document.createElement('option'); o.value=p; o.textContent=p; sel.appendChild(o); });
  const aktif = '<?= e($defaultPeriode) ?>';
  if (periodes.includes(aktif)) sel.value = aktif;
  applyFilter();
}

/* ── Load Master Bidang ──────────────────────────────────────────────── */
async function loadMasterBidang() {
  const res = await apiPost('/admin/api/sheets.php', { action:'list', page:'master_bidang' });
  if (!res.success) return;
  masterBidang = res.data || [];
  renderMasterTable();
  // Populate bidang dropdown untuk form
  onTipeChange();
}

function onTipeChange() {
  const tipe = document.getElementById('fieldTipe').value;
  const sel  = document.getElementById('fieldBidang');
  sel.innerHTML = '<option value="">— Pilih Bidang —</option>';
  masterBidang.filter(m => m['Tipe'] === tipe).forEach(m => {
    const o = document.createElement('option'); o.value = m['Bidang']; o.textContent = m['Bidang']; sel.appendChild(o);
  });
}

/* ── Render Table ─────────────────────────────────────────────────── */
function renderTable(data) {
  const tbody = document.getElementById('tableBody');
  document.getElementById('rowCount').textContent = data.length + ' pengurus';
  if (!data.length) { document.getElementById('emptyState').style.display='block'; document.getElementById('tableContainer').style.display='none'; return; }
  document.getElementById('tableContainer').style.display='block'; document.getElementById('emptyState').style.display='none';
  const showAksi = can('edit') || can('delete');
  tbody.innerHTML = data.map(row => {
    const foto = row['Foto'] || '';
    const namaHtml = foto
      ? `<div style="display:flex;align-items:center;gap:8px">
           <img src="/img/person/${escHtml(foto)}" alt="" style="width:32px;height:32px;border-radius:50%;object-fit:cover;border:2px solid var(--border)" onerror="this.style.display='none'">
           <span style="font-weight:500">${escHtml(row['Nama']||'')}</span>
         </div>`
      : `<span style="font-weight:500">${escHtml(row['Nama']||'')}</span>`;
    return `<tr>
    <td><span class="badge badge-gold" style="font-size:10px">${escHtml(row['Periode']||'—')}</span></td>
    <td><span class="badge ${row['Tipe']==='DPP'?'badge-gold':'badge-blue'}">${escHtml(row['Tipe']||'')}</span></td>
    <td style="font-size:13px">${escHtml(row['Bidang']||'')}</td>
    <td style="font-size:13px;color:var(--text-secondary)">${escHtml(row['Posisi']||'')}</td>
    <td>${namaHtml}</td>
    ${showAksi ? `<td><div class="actions">
      ${can('edit') ? `<button class="btn btn-icon btn-sm" onclick="openEditModal(${row._id})">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
      </button>` : ''}
      ${can('delete') ? `<button class="btn btn-icon btn-sm" onclick="deleteRow(${row._id})" style="color:var(--danger);border-color:rgba(224,82,82,0.3)">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a1 1 0 011-1h4a1 1 0 011 1v2"/></svg>
      </button>` : ''}
    </div></td>` : '<td style="display:none"></td>'}
  </tr>`;}).join('');
}

function applyFilter() {
  const q = document.getElementById('searchInput').value.toLowerCase();
  const t = document.getElementById('filterTipe').value;
  const p = document.getElementById('filterPeriode').value;
  renderTable(allData.filter(r =>
    (!t || r['Tipe']===t) &&
    (!p || r['Periode']===p) &&
    (!q || COLS.some(c => (r[c]||'').toLowerCase().includes(q)))
  ));
}
document.getElementById('searchInput').addEventListener('input', applyFilter);

/* ── Modal Tambah ─────────────────────────────────────────────────── */
function openAddModal() {
  if (!can('create')) { toast('Akses Ditolak','Tidak ada izin tambah data','error'); return; }
  editRow = null; document.getElementById('modalTitle').textContent = 'Tambah Pengurus';
  document.getElementById('fieldPeriode').value = '<?= e($defaultPeriode) ?>';
  document.getElementById('fieldTipe').value    = 'DPP';
  onTipeChange();
  document.getElementById('fieldPosisi').value  = '';
  document.getElementById('fieldNama').value    = '';
  document.getElementById('fieldFoto').value    = '';
  document.getElementById('fotoPreview').style.display   = 'none';
  document.getElementById('fotoPlaceholder').style.display = 'flex';
  openModal('formModal');
}

/* ── Modal Edit ───────────────────────────────────────────────────── */
function openEditModal(rowNum) {
  if (!can('edit')) { toast('Akses Ditolak','Tidak ada izin edit data','error'); return; }
  const row = allData.find(r => r._id === rowNum); if (!row) return;
  editRow = rowNum; document.getElementById('modalTitle').textContent = 'Edit Pengurus';
  document.getElementById('fieldRow').value     = rowNum;
  document.getElementById('fieldPeriode').value = row['Periode'] || '<?= e($defaultPeriode) ?>';
  document.getElementById('fieldTipe').value    = row['Tipe']   || 'DPP';
  onTipeChange();
  document.getElementById('fieldBidang').value  = row['Bidang'] || '';
  document.getElementById('fieldPosisi').value  = row['Posisi'] || '';
  document.getElementById('fieldNama').value    = row['Nama']   || '';
  document.getElementById('fieldFoto').value    = row['Foto']   || '';
  const prev = document.getElementById('fotoPreview');
  const plac = document.getElementById('fotoPlaceholder');
  if (row['Foto']) { prev.src='/img/person/'+row['Foto']; prev.style.display='block'; plac.style.display='none'; }
  else { prev.style.display='none'; plac.style.display='flex'; }
  openModal('formModal');
}

/* ── Submit ───────────────────────────────────────────────────────── */
async function submitForm() {
  const btn = document.getElementById('btnSave'); const isEdit = !!editRow;
  if (isEdit && !can('edit'))    { toast('Akses Ditolak','Tidak ada izin edit','error'); return; }
  if (!isEdit && !can('create')) { toast('Akses Ditolak','Tidak ada izin tambah','error'); return; }
  const bidang = document.getElementById('fieldBidang').value;
  const posisi = document.getElementById('fieldPosisi').value.trim();
  const nama   = document.getElementById('fieldNama').value.trim();
  if (!bidang || !posisi || !nama) { toast('Error','Bidang, Posisi, dan Nama wajib diisi','error'); return; }
  const data = {
    'Bidang': bidang, 'Posisi': posisi, 'Nama': nama,
    'Tipe':   document.getElementById('fieldTipe').value,
    'Periode':document.getElementById('fieldPeriode').value,
    'Foto':   document.getElementById('fieldFoto').value,
  };
  btnLoading(btn, true);
  const res = await apiPost('/admin/api/sheets.php', { action:isEdit?'update':'create', page:PAGE, id:editRow, data });
  btnLoading(btn, false);
  if (res.success) { toast('Berhasil', isEdit?'Data diperbarui':'Data ditambahkan','success'); closeModal('formModal'); loadData(); }
  else toast('Error', res.error,'error');
}

/* ── Delete ───────────────────────────────────────────────────────── */
async function deleteRow(rowNum) {
  if (!can('delete')) { toast('Akses Ditolak','Tidak ada izin hapus','error'); return; }
  const row = allData.find(r => r._id === rowNum);
  confirmDialog('Hapus Pengurus?', `"${row?.['Nama']||'data ini'}" akan dihapus.`, async () => {
    const res = await apiPost('/admin/api/sheets.php', { action:'delete', page:PAGE, id:rowNum });
    if (res.success) { toast('Berhasil','Dihapus','success'); loadData(); }
    else toast('Error', res.error,'error');
  });
}

/* ── MASTER BIDANG (superadmin) ──────────────────────────────────── */
function renderMasterTable() {
  const tbody = document.getElementById('masterTableBody');
  if (!tbody) return;
  document.getElementById('masterLoadingState').style.display='none';
  document.getElementById('masterTableWrap').style.display='block';
  if (!masterBidang.length) {
    tbody.innerHTML = '<tr><td colspan="3" style="text-align:center;color:var(--text-muted);padding:20px">Belum ada data bidang.</td></tr>'; return;
  }
  tbody.innerHTML = masterBidang.map(r => `<tr>
    <td><span class="badge ${r['Tipe']==='DPP'?'badge-gold':'badge-blue'}">${escHtml(r['Tipe']||'')}</span></td>
    <td style="font-weight:500">${escHtml(r['Bidang']||'')}</td>
    <td><button class="btn btn-icon btn-sm" onclick="deleteMasterBidang(${r._id})" style="color:var(--danger)">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a1 1 0 011-1h4a1 1 0 011 1v2"/></svg>
    </button></td>
  </tr>`).join('');
}

function openMasterModal() { openModal('masterModal'); }

async function addMasterBidang() {
  const t = document.getElementById('masterTipe').value;
  const b = document.getElementById('masterBidang').value.trim();
  if (!b) { toast('Error','Isi nama bidang','error'); return; }
  const res = await apiPost('/admin/api/sheets.php', { action:'create', page:'master_bidang', data:{'Tipe':t,'Bidang':b} });
  if (res.success) {
    document.getElementById('masterBidang').value = '';
    toast('Berhasil','Bidang ditambahkan','success');
    await loadMasterBidang();
  } else toast('Error', res.error,'error');
}

async function deleteMasterBidang(rowNum) {
  confirmDialog('Hapus Bidang?','Data bidang ini akan dihapus dari daftar pilihan.', async () => {
    const res = await apiPost('/admin/api/sheets.php', { action:'delete', page:'master_bidang', id:rowNum });
    if (res.success) { toast('Berhasil','Dihapus dari master','success'); await loadMasterBidang(); }
    else toast('Error', res.error,'error');
  });
}

// ── Foto Picker callback ───────────────────────────────────────────────
function onFotoSelected(filename, url) {
  const prev = document.getElementById('fotoPreview');
  const plac = document.getElementById('fotoPlaceholder');
  document.getElementById('fieldFoto').value = filename || '';
  if (filename) { prev.src = url; prev.style.display = 'block'; plac.style.display = 'none'; }
  else { prev.style.display = 'none'; plac.style.display = 'flex'; }
}
</script>
<script src="/admin/js/foto-picker.js">
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