<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
adminBoot();
$user           = requirePageAccess('asisten_imam');
$defaultPeriode = getCurrentPeriode();
adminHeader('Asisten Imam', 'asisten_imam', $user);
?>
<div class="page-header">
  <div class="page-header-left">
    <h1>Daftar Asisten Imam</h1>
    <p>Kelola data asisten imam paroki</p>
  </div>
  <?php if (userCan($user, 'create')): ?>
  <button class="btn btn-primary" onclick="openAddModal()">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    Tambah Asisten
  </button>
  <?php endif; ?>
</div>
<div class="card">
  <div class="toolbar">
    <div class="toolbar-left" style="flex-wrap:wrap;gap:8px">
      <div class="search-wrap">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" class="form-control" id="searchInput" placeholder="Cari nama / lingkungan...">
      </div>
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
          <tr><th style="width:48px">Foto</th><th>Periode</th><th>Nama</th><th>No. HP</th><th>Asal Lingk / Stasi</th><th>Alamat</th><th id="thAksi" style="width:90px">Aksi</th></tr>
        </thead>
        <tbody id="tableBody"></tbody>
      </table>
    </div>
  </div>
  <div id="emptyState" class="empty-state" style="display:none"><p>Belum ada data asisten imam.</p></div>
</div>
<?php if (userCan($user, 'create') || userCan($user, 'edit')): ?>
<div class="modal-overlay" id="formModal">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title" id="modalTitle">Tambah Asisten Imam</span>
      <button class="modal-close">&times;</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="fieldRow">
      <div class="form-grid">
        <div class="form-group full">
          <label>Periode <span class="required">*</span></label>
          <select class="form-select" id="fieldPeriode">
            <?php foreach (generatePeriodeList() as $p): ?>
            <option value="<?= e($p) ?>" <?= $p===$defaultPeriode?'selected':'' ?>><?= e($p) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group full">
          <label>Nama Lengkap <span class="required">*</span></label>
          <input type="text" class="form-control" id="fieldNama" placeholder="Nama lengkap">
        </div>
        <div class="form-group full">
          <label>No. HP</label>
          <input type="text" class="form-control" id="fieldNoHP" placeholder="08xxx">
        </div>
        <div class="form-group full">
          <label>Asal Lingkungan / Stasi <span class="required">*</span></label>
          <select class="form-select" id="fieldAsal">
            <option value="">— Memuat data lingkungan... —</option>
          </select>
        </div>
        <div class="form-group full">
          <label>Alamat</label>
          <textarea class="form-control" id="fieldAlamat" rows="3" placeholder="Alamat lengkap"></textarea>
        </div>
        <!-- Foto Picker -->
        <div class="form-group full">
          <label>Foto</label>
          <input type="hidden" id="fotoValue">
          <div style="display:flex;align-items:center;gap:12px;margin-top:6px">
            <div style="position:relative;width:56px;height:56px;flex-shrink:0">
              <img id="fotoPreview" src="" alt="" style="width:56px;height:56px;border-radius:50%;object-fit:cover;border:2px solid var(--border);display:none">
              <div id="fotoPlaceholder" style="width:56px;height:56px;border-radius:50%;background:var(--bg-card2);border:2px dashed var(--border);display:flex;align-items:center;justify-content:center;color:var(--text-muted)">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="22" height="22"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
              </div>
            </div>
            <div style="display:flex;flex-direction:column;gap:5px">
              <button type="button" class="btn btn-secondary btn-sm" onclick="FotoPicker.open('fotoValue','fotoPreview',onFotoSelected)">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                Pilih / Upload Foto
              </button>
              <span id="fotoStatus" style="font-size:11px;color:var(--text-muted)">Dari galeri img/person atau upload baru</span>
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
<script src="/admin/js/foto-picker.js"></script>
<script>
const PAGE = 'asisten_imam';
let allData = [], masterLingkungan = [], editRow = null;
document.addEventListener('DOMContentLoaded', function() {
  if (!can('edit') && !can('delete')) { const th=document.getElementById('thAksi'); if(th) th.style.display='none'; }
  loadData(); loadMasterLingkungan();
});
async function loadData() {
  document.getElementById('loadingState').style.display='block';
  document.getElementById('tableContainer').style.display='none';
  const res = await apiPost('/admin/api/sheets.php',{action:'list',page:PAGE});
  document.getElementById('loadingState').style.display='none';
  if (!res.success){toast('Error',res.error,'error');return;}
  allData=res.data||[];
  const periodes=[...new Set(allData.map(r=>r['Periode']).filter(Boolean))].sort().reverse();
  const sel=document.getElementById('filterPeriode');
  while(sel.options.length>1)sel.remove(1);
  periodes.forEach(p=>{const o=document.createElement('option');o.value=p;o.textContent=p;sel.appendChild(o);});
  const aktif='<?= e($defaultPeriode) ?>';
  if(periodes.includes(aktif))sel.value=aktif;
  applyFilter();
}
async function loadMasterLingkungan() {
  const res=await apiPost('/admin/api/sheets.php',{action:'list',page:'master_lingkungan'});
  if(!res.success)return; masterLingkungan=res.data||[]; populateLingkunganSelect('');
}
function populateLingkunganSelect(sel_val) {
  const sel=document.getElementById('fieldAsal');
  sel.innerHTML='<option value="">— Pilih Lingkungan / Stasi —</option>';
  masterLingkungan.forEach(m=>{const o=document.createElement('option');o.value=m['Lingkungan'];o.textContent=`${m['Wilayah']} — ${m['Lingkungan']}`;if(m['Lingkungan']===sel_val)o.selected=true;sel.appendChild(o);});
}
function fotoAvatar(foto){
  return foto
    ?`<img src="/img/person/${escHtml(foto)}" style="width:36px;height:36px;border-radius:50%;object-fit:cover;border:2px solid var(--border)" onerror="this.style.display='none'">`
    :`<div style="width:36px;height:36px;border-radius:50%;background:var(--bg-card2);border:1.5px solid var(--border);display:flex;align-items:center;justify-content:center;color:var(--text-muted)"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="14" height="14"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></div>`;
}
function renderTable(data){
  const tbody=document.getElementById('tableBody');
  document.getElementById('rowCount').textContent=data.length+' asisten';
  if(!data.length){document.getElementById('emptyState').style.display='block';document.getElementById('tableContainer').style.display='none';return;}
  document.getElementById('tableContainer').style.display='block';document.getElementById('emptyState').style.display='none';
  const showAksi=can('edit')||can('delete');
  tbody.innerHTML=data.map(row=>`<tr>
    <td>${fotoAvatar(row['Foto']||'')}</td>
    <td><span class="badge badge-gold" style="font-size:10px">${escHtml(row['Periode']||'—')}</span></td>
    <td style="font-weight:500">${escHtml(row['Nama']||'')}</td>
    <td style="font-family:'DM Mono',monospace;font-size:12px">${escHtml(row['No.HP']||'')}</td>
    <td><span class="badge badge-blue" style="font-size:11px">${escHtml(row['Asal Lingk / Stasi']||'')}</span></td>
    <td style="font-size:12px;color:var(--text-secondary);max-width:180px">${escHtml(row['Alamat']||'')}</td>
    ${showAksi?`<td><div class="actions">
      ${can('edit')?`<button class="btn btn-icon btn-sm" onclick="openEditModal(${row._id})"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></button>`:''}
      ${can('delete')?`<button class="btn btn-icon btn-sm" onclick="deleteRow(${row._id})" style="color:var(--danger);border-color:rgba(224,82,82,0.3)"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a1 1 0 011-1h4a1 1 0 011 1v2"/></svg></button>`:''}
    </div></td>`:'<td style="display:none"></td>'}
  </tr>`).join('');
}
function applyFilter(){
  const q=document.getElementById('searchInput').value.toLowerCase();
  const p=document.getElementById('filterPeriode').value;
  renderTable(allData.filter(r=>(!p||r['Periode']===p)&&(!q||['Nama','No.HP','Asal Lingk / Stasi','Alamat'].some(c=>(r[c]||'').toLowerCase().includes(q)))));
}
document.getElementById('searchInput').addEventListener('input',applyFilter);
function onFotoSelected(filename,url){
  const prev=document.getElementById('fotoPreview'),plac=document.getElementById('fotoPlaceholder'),stat=document.getElementById('fotoStatus');
  if(filename){prev.src=url;prev.style.display='block';plac.style.display='none';stat.textContent=filename.replace('.webp','').replace(/-/g,' ');}
  else{prev.style.display='none';plac.style.display='flex';stat.textContent='Dari galeri atau upload baru';}
}
function resetFotoUI(f){
  document.getElementById('fotoValue').value=f||'';
  const prev=document.getElementById('fotoPreview'),plac=document.getElementById('fotoPlaceholder'),stat=document.getElementById('fotoStatus');
  if(f){prev.src='/img/person/'+f;prev.style.display='block';plac.style.display='none';stat.textContent=f.replace('.webp','').replace(/-/g,' ');}
  else{prev.style.display='none';plac.style.display='flex';stat.textContent='Dari galeri atau upload baru';}
}
function openAddModal(){
  if(!can('create')){toast('Akses Ditolak','Tidak ada izin tambah data','error');return;}
  editRow=null;document.getElementById('modalTitle').textContent='Tambah Asisten Imam';
  document.getElementById('fieldPeriode').value='<?= e($defaultPeriode) ?>';
  ['fieldNama','fieldNoHP','fieldAlamat'].forEach(id=>document.getElementById(id).value='');
  populateLingkunganSelect('');resetFotoUI('');openModal('formModal');
}
function openEditModal(rowNum){
  if(!can('edit')){toast('Akses Ditolak','Tidak ada izin edit data','error');return;}
  const row=allData.find(r=>r._id===rowNum);if(!row)return;
  editRow=rowNum;document.getElementById('modalTitle').textContent='Edit Asisten Imam';
  document.getElementById('fieldRow').value=rowNum;
  document.getElementById('fieldPeriode').value=row['Periode']||'<?= e($defaultPeriode) ?>';
  document.getElementById('fieldNama').value=row['Nama']||'';
  document.getElementById('fieldNoHP').value=row['No.HP']||'';
  document.getElementById('fieldAlamat').value=row['Alamat']||'';
  populateLingkunganSelect(row['Asal Lingk / Stasi']||'');
  resetFotoUI(row['Foto']||'');openModal('formModal');
}
async function submitForm(){
  const btn=document.getElementById('btnSave');const isEdit=!!editRow;
  if(isEdit&&!can('edit')){toast('Akses Ditolak','Tidak ada izin edit','error');return;}
  if(!isEdit&&!can('create')){toast('Akses Ditolak','Tidak ada izin tambah','error');return;}
  const nama=document.getElementById('fieldNama').value.trim();
  const asal=document.getElementById('fieldAsal').value;
  if(!nama||!asal){toast('Error','Nama dan Asal Lingkungan wajib diisi','error');return;}
  const data={'Nama':nama,'No.HP':document.getElementById('fieldNoHP').value.trim(),'Asal Lingk / Stasi':asal,'Alamat':document.getElementById('fieldAlamat').value.trim(),'Periode':document.getElementById('fieldPeriode').value,'Foto':document.getElementById('fotoValue').value};
  btnLoading(btn,true);
  const res=await apiPost('/admin/api/sheets.php',{action:isEdit?'update':'create',page:PAGE,id:editRow,data});
  btnLoading(btn,false);
  if(res.success){toast('Berhasil',isEdit?'Data diperbarui':'Data ditambahkan','success');closeModal('formModal');loadData();}
  else toast('Error',res.error,'error');
}
async function deleteRow(rowNum){
  if(!can('delete')){toast('Akses Ditolak','Tidak ada izin hapus data','error');return;}
  const row=allData.find(r=>r._id===rowNum);
  confirmDialog('Hapus Asisten?',`"${row?.['Nama']||'data ini'}" akan dihapus.`,async()=>{
    const res=await apiPost('/admin/api/sheets.php',{action:'delete',page:PAGE,id:rowNum});
    if(res.success){toast('Berhasil','Dihapus','success');loadData();}else toast('Error',res.error,'error');
  });
}

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