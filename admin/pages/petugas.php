<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
adminBoot();
$user = requirePageAccess('petugas');
adminHeader('Petugas Liturgi', 'petugas', $user);
?>
<div class="page-header">
  <div class="page-header-left"><h1>Petugas Liturgi</h1><p>Kelola jadwal petugas misa bulanan</p></div>
  <?php if (userCan($user, 'create')): ?>
  <button class="btn btn-primary" onclick="openAddModal()">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg> Tambah Baris
  </button>
  <?php endif; ?>
</div>
<div class="card" style="border-color:rgba(201,168,76,0.2);background:rgba(201,168,76,0.04)">
  <p style="font-size:13px;color:var(--text-secondary)">💡 <strong style="color:var(--accent)">Tips:</strong> Setiap baris mewakili satu sesi misa.</p>
</div>
<div class="card">
  <div class="toolbar">
    <div class="toolbar-left">
      <div class="search-wrap">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" class="form-control" id="searchInput" placeholder="Cari pekan / koor / lektor...">
      </div>
    </div>
    <div class="toolbar-right"><span id="rowCount" style="font-size:13px;color:var(--text-secondary)">Memuat...</span></div>
  </div>
  <div id="loadingState" style="text-align:center;padding:40px"><div class="spinner"></div></div>
  <div id="tableContainer" style="display:none">
    <div class="table-wrapper">
      <table class="data-table" id="dataTable">
        <thead>
          <tr><th>No</th><th>Hari/Tanggal</th><th>Pekan</th><th>Koor</th><th>Organis</th><th>Pemazmur</th><th>Lektor</th><th>Pemandu</th><th>Dekorasi</th><th>Asisten</th><th>Saran</th><th id="thAksi" style="width:90px">Aksi</th></tr>
        </thead>
        <tbody id="tableBody"></tbody>
      </table>
    </div>
  </div>
  <div id="emptyState" class="empty-state" style="display:none"><p>Belum ada data petugas.</p></div>
</div>
<?php if (userCan($user, 'create') || userCan($user, 'edit')): ?>
<div class="modal-overlay" id="formModal">
  <div class="modal modal-lg">
    <div class="modal-header"><span class="modal-title" id="modalTitle">Tambah Baris Petugas</span><button class="modal-close">&times;</button></div>
    <div class="modal-body">
      <input type="hidden" id="fieldRow">
      <div class="form-grid">
        <div class="form-group"><label>No</label><input type="number" class="form-control" id="fieldNo" placeholder="1" min="1"></div>
        <div class="form-group"><label>Hari &amp; Tanggal <span class="required">*</span></label><input type="text" class="form-control" id="fieldHariTanggal" placeholder="SABTU / 1-11-2025 (05.30)"></div>
        <div class="form-group full"><label>Nama Pekan / Hari Raya</label><input type="text" class="form-control" id="fieldPekan" placeholder="HARI SEMUA ORANG KUDUS"></div>
        <div class="form-group"><label>Petugas Koor</label><input type="text" class="form-control" id="fieldKoor"></div>
        <div class="form-group"><label>Organis</label><input type="text" class="form-control" id="fieldOrganis"></div>
        <div class="form-group"><label>Pemazmur</label><input type="text" class="form-control" id="fieldPemazmur"></div>
        <div class="form-group"><label>Lektor</label><input type="text" class="form-control" id="fieldLektor"></div>
        <div class="form-group"><label>Pemandu Umat</label><input type="text" class="form-control" id="fieldPemandu"></div>
        <div class="form-group"><label>Dekorasi Altar</label><input type="text" class="form-control" id="fieldDekorasi"></div>
        <div class="form-group full"><label>Asisten Imam</label><textarea class="form-control" id="fieldAsisten" rows="4" placeholder="Satu nama per baris"></textarea></div>
        <div class="form-group full"><label>Saran Doa &amp; Lagu</label><textarea class="form-control" id="fieldSaran" rows="3"></textarea></div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('formModal')">Batal</button>
      <button class="btn btn-primary" id="btnSave" onclick="submitForm()">Simpan</button>
    </div>
  </div>
</div>
<?php endif; ?>
<script>
const PAGE = 'petugas';
const COLS = ['No','HariTanggal','Pekan','Koor','Organis','Pemazmur','Lektor','Pemandu','Dekorasi','Asisten','Saran'];
const fieldMap = {
  'No':'fieldNo','HariTanggal':'fieldHariTanggal','Pekan':'fieldPekan','Koor':'fieldKoor',
  'Organis':'fieldOrganis','Pemazmur':'fieldPemazmur','Lektor':'fieldLektor','Pemandu':'fieldPemandu',
  'Dekorasi':'fieldDekorasi','Asisten':'fieldAsisten','Saran':'fieldSaran'
};
let allData = [], editRow = null;
document.addEventListener('DOMContentLoaded', function() {
  if (!can('edit') && !can('delete')) { const th = document.getElementById('thAksi'); if(th) th.style.display='none'; }
});
async function loadData() {
  document.getElementById('loadingState').style.display='block'; document.getElementById('tableContainer').style.display='none';
  const res = await apiPost('/admin/api/sheets.php', { action:'list', page:PAGE });
  document.getElementById('loadingState').style.display='none';
  if (!res.success) { toast('Error', res.error,'error'); return; }
  allData = res.data||[]; renderTable(allData);
}
const short = (s, n=30) => s&&s.length>n ? s.substr(0,n)+'…' : (s||'');
function renderTable(data) {
  const tbody = document.getElementById('tableBody');
  document.getElementById('rowCount').textContent = data.length + ' baris';
  if (!data.length) { document.getElementById('emptyState').style.display='block'; document.getElementById('tableContainer').style.display='none'; return; }
  document.getElementById('tableContainer').style.display='block'; document.getElementById('emptyState').style.display='none';
  const showAksi = can('edit') || can('delete');
  tbody.innerHTML = data.map(row => `<tr>
    <td style="text-align:center">${escHtml(row['No']||'')}</td>
    <td style="white-space:nowrap;font-size:12px">${escHtml(row['HariTanggal']||'')}</td>
    <td style="font-size:12px;max-width:120px">${escHtml(short(row['Pekan']))}</td>
    <td style="font-size:12px">${escHtml(row['Koor']||'')}</td>
    <td style="font-size:12px">${escHtml(row['Organis']||'')}</td>
    <td style="font-size:12px">${escHtml(row['Pemazmur']||'')}</td>
    <td style="font-size:12px">${escHtml(row['Lektor']||'')}</td>
    <td style="font-size:12px">${escHtml(row['Pemandu']||'')}</td>
    <td style="font-size:12px">${escHtml(row['Dekorasi']||'')}</td>
    <td style="font-size:11.5px;max-width:100px;white-space:pre-line">${escHtml(short(row['Asisten']||'',40))}</td>
    <td style="font-size:11px;max-width:90px">${escHtml(short(row['Saran']||'',40))}</td>
    ${showAksi ? `<td><div class="actions">
      ${can('edit') ? `<button class="btn btn-icon btn-sm" onclick="openEditModal(${row._id})"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></button>` : ''}
      ${can('delete') ? `<button class="btn btn-icon btn-sm" onclick="deleteRow(${row._id})" style="color:var(--danger);border-color:rgba(224,82,82,0.3)"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a1 1 0 011-1h4a1 1 0 011 1v2"/></svg></button>` : ''}
    </div></td>` : '<td style="display:none"></td>'}
  </tr>`).join('');
}
document.getElementById('searchInput').addEventListener('input', function() {
  const q = this.value.toLowerCase();
  renderTable(q ? allData.filter(r => COLS.some(c => (r[c]||'').toLowerCase().includes(q))) : allData);
});
function openAddModal() {
  if (!can('create')) { toast('Akses Ditolak','Tidak ada izin tambah data','error'); return; }
  editRow = null; document.getElementById('modalTitle').textContent = 'Tambah Baris Petugas';
  Object.values(fieldMap).forEach(id => { const el=document.getElementById(id); if(el) el.value=''; });
  openModal('formModal');
}
function openEditModal(rowNum) {
  if (!can('edit')) { toast('Akses Ditolak','Tidak ada izin edit data','error'); return; }
  const row = allData.find(r => r._id===rowNum); if(!row) return;
  editRow = rowNum; document.getElementById('modalTitle').textContent = 'Edit Baris Petugas';
  document.getElementById('fieldRow').value = rowNum;
  Object.entries(fieldMap).forEach(([col, id]) => { const el=document.getElementById(id); if(el) el.value=row[col]||''; });
  openModal('formModal');
}
async function submitForm() {
  const btn = document.getElementById('btnSave'); const isEdit = !!editRow;
  if (isEdit && !can('edit'))    { toast('Akses Ditolak','Tidak ada izin edit','error'); return; }
  if (!isEdit && !can('create')) { toast('Akses Ditolak','Tidak ada izin tambah','error'); return; }
  if (!document.getElementById('fieldHariTanggal').value.trim()) { toast('Error','Hari/Tanggal wajib diisi','error'); return; }
  const data = {}; Object.entries(fieldMap).forEach(([col,id]) => { const el=document.getElementById(id); data[col]=el?el.value.trim():''; });
  btnLoading(btn, true);
  const res = await apiPost('/admin/api/sheets.php', { action:isEdit?'update':'create', page:PAGE, id:editRow, data });
  btnLoading(btn, false);
  if (res.success) { toast('Berhasil',isEdit?'Data diperbarui':'Data ditambahkan','success'); closeModal('formModal'); loadData(); }
  else toast('Error', res.error,'error');
}
async function deleteRow(rowNum) {
  if (!can('delete')) { toast('Akses Ditolak','Tidak ada izin hapus data','error'); return; }
  const row = allData.find(r => r._id===rowNum);
  confirmDialog('Hapus Baris?', `"${row?.['HariTanggal']||'baris ini'}" akan dihapus.`, async () => {
    const res = await apiPost('/admin/api/sheets.php', { action:'delete', page:PAGE, id:rowNum });
    if (res.success) { toast('Berhasil','Dihapus','success'); loadData(); }
    else toast('Error', res.error,'error');
  });
}
document.addEventListener('DOMContentLoaded', loadData);
</script>
<?php adminFooter(); ?>
