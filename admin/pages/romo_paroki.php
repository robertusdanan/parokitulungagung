<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
adminBoot();
$user = requirePageAccess('romo_paroki');
adminHeader('Romo Paroki', 'romo_paroki', $user);
?>
<div class="page-header">
  <div class="page-header-left">
    <h1>Romo Paroki</h1>
    <p>Kelola data Romo yang tampil di homepage &amp; halaman riwayat/profil Romo</p>
  </div>
  <?php if (userCan($user, 'create')): ?>
  <button class="btn btn-primary" onclick="openAddModal()">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    Tambah Data Romo
  </button>
  <?php endif; ?>
</div>

<div class="card" style="margin-bottom:16px;padding:14px 16px;background:var(--bg-card2);border:1px dashed var(--border)">
  <p style="margin:0;font-size:12.5px;color:var(--text-secondary);line-height:1.6">
    <strong>Cara kerja:</strong> setiap baris di sini mewakili satu <em>masa jabatan</em> seorang Romo di Paroki Tulungagung.
    Yang tampil otomatis di homepage hanya Romo dengan <strong>Tanggal Selesai kosong</strong> (masih menjabat) atau
    tanggal hari ini masih berada di antara Tanggal Mulai &ndash; Tanggal Selesai, dan status <strong>Aktif</strong> menyala.
    Urutan tampil di homepage mengikuti kolom <strong>Urutan</strong> (angka kecil tampil lebih dulu).<br>
    Jika seorang Romo pernah menjabat lebih dari satu periode/jabatan di paroki ini (mis. dulu Romo Rekan, sekarang Romo Paroki),
    tambahkan baris baru dan gunakan <strong>Slug</strong> yang sama persis dengan baris sebelumnya agar riwayat jabatannya
    tergabung dalam satu halaman profil.
  </p>
</div>

<div class="card">
  <div class="toolbar">
    <div class="toolbar-left" style="flex-wrap:wrap;gap:8px">
      <div class="search-wrap">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" class="form-control" id="searchInput" placeholder="Cari nama / jabatan...">
      </div>
      <select class="form-select" id="filterStatus" style="max-width:160px" onchange="applyFilter()">
        <option value="">Semua Status</option>
        <option value="aktif">Masih Menjabat</option>
        <option value="selesai">Purna Tugas</option>
      </select>
    </div>
    <div class="toolbar-right"><span id="rowCount" style="font-size:13px;color:var(--text-secondary)">Memuat...</span></div>
  </div>
  <div id="loadingState" style="text-align:center;padding:40px"><div class="spinner"></div></div>
  <div id="tableContainer" style="display:none">
    <div class="table-wrapper">
      <table class="data-table" id="dataTable">
        <thead>
          <tr><th style="width:48px">Foto</th><th>Nama</th><th>Jabatan</th><th>Masa Jabatan</th><th>Status</th><th style="width:60px">Urutan</th><th id="thAksi" style="width:90px">Aksi</th></tr>
        </thead>
        <tbody id="tableBody"></tbody>
      </table>
    </div>
  </div>
  <div id="emptyState" class="empty-state" style="display:none"><p>Belum ada data Romo Paroki.</p></div>
</div>

<?php if (userCan($user, 'create') || userCan($user, 'edit')): ?>
<div class="modal-overlay" id="formModal">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title" id="modalTitle">Tambah Data Romo</span>
      <button class="modal-close">&times;</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="fieldRow">
      <div class="form-grid">
        <div class="form-group full">
          <label>Nama Romo <span class="required">*</span></label>
          <input type="text" class="form-control" id="fieldNama" placeholder="RD Thomas Aquino Djoko Noegroho" oninput="onNamaInput()">
        </div>
        <div class="form-group full">
          <label>Slug Profil <span class="required">*</span></label>
          <input type="text" class="form-control" id="fieldSlug" placeholder="rd-thomas-aquino-djoko-noegroho">
          <span style="font-size:11px;color:var(--text-muted)">URL halaman: /romo/&lt;slug&gt; &mdash; gunakan slug yang <strong>sama</strong> untuk Romo yang sama walau beda periode/jabatan.</span>
        </div>
        <div class="form-group full">
          <label>Jabatan <span class="required">*</span></label>
          <input type="text" class="form-control" id="fieldJabatan" list="jabatanOptions" placeholder="Romo Paroki">
          <datalist id="jabatanOptions">
            <option value="Romo Paroki">
            <option value="Romo Rekan">
            <option value="Romo Pembantu">
            <option value="Romo Moderator">
          </datalist>
        </div>
        <div class="form-group">
          <label>Tanggal Mulai Menjabat <span class="required">*</span></label>
          <input type="date" class="form-control" id="fieldMulai">
        </div>
        <div class="form-group">
          <label>Tanggal Selesai</label>
          <input type="date" class="form-control" id="fieldSelesai">
          <span style="font-size:11px;color:var(--text-muted)">Kosongkan jika masih menjabat sampai sekarang</span>
        </div>
        <div class="form-group">
          <label>Urutan Tampil</label>
          <input type="number" class="form-control" id="fieldUrutan" value="0" min="0" step="1">
        </div>
        <div class="form-group" style="display:flex;align-items:flex-end">
          <label style="display:flex;align-items:center;gap:8px;cursor:pointer;margin:0">
            <input type="checkbox" id="fieldAktif" checked style="width:16px;height:16px">
            Tampilkan di Website (Aktif)
          </label>
        </div>
        <div class="form-group full">
          <label>Riwayat / Biografi Singkat</label>
          <textarea class="form-control" id="fieldRiwayat" rows="6" placeholder="Riwayat tahbisan, pendidikan, tugas perutusan sebelumnya, dsb. Ditampilkan di halaman profil Romo."></textarea>
          <span style="font-size:11px;color:var(--text-muted)">Cukup diisi pada salah satu baris (biasanya baris masa jabatan terbaru) untuk Romo yang sama.</span>
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
const PAGE = 'romo_paroki';
let allData = [], editRow = null, slugTouched = false;

document.addEventListener('DOMContentLoaded', function() {
  if (!can('edit') && !can('delete')) { const th=document.getElementById('thAksi'); if(th) th.style.display='none'; }
  loadData();
});

function slugify(s) {
  return (s||'').toString().toLowerCase().trim()
    .replace(/[^a-z0-9\s-]/g,'')
    .replace(/\s+/g,'-')
    .replace(/-+/g,'-')
    .replace(/^-|-$/g,'');
}
function onNamaInput() {
  if (slugTouched) return;
  document.getElementById('fieldSlug').value = slugify(document.getElementById('fieldNama').value);
}
document.getElementById('fieldSlug').addEventListener('input', function(){ slugTouched = true; });

async function loadData() {
  document.getElementById('loadingState').style.display='block';
  document.getElementById('tableContainer').style.display='none';
  const res = await apiPost('/admin/api/sheets.php',{action:'list',page:PAGE});
  document.getElementById('loadingState').style.display='none';
  if (!res.success){toast('Error',res.error,'error');return;}
  allData=res.data||[];
  applyFilter();
}
function fotoAvatar(foto){
  return foto
    ?`<img src="${escHtml(window.PERSON_CDN_URL+'/'+foto)}" style="width:36px;height:36px;border-radius:50%;object-fit:cover;border:2px solid var(--border)" onerror="this.style.display='none'">`
    :`<div style="width:36px;height:36px;border-radius:50%;background:var(--bg-card2);border:1.5px solid var(--border);display:flex;align-items:center;justify-content:center;color:var(--text-muted)"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="14" height="14"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></div>`;
}
function fmtTgl(t){
  if(!t) return '—';
  const d=new Date(t+'T00:00:00');
  if(isNaN(d)) return t;
  return d.toLocaleDateString('id-ID',{day:'2-digit',month:'short',year:'numeric'});
}
function isMenjabat(row){
  const today = new Date().toISOString().slice(0,10);
  const mulai = row['tanggal_mulai']||'';
  const selesai = row['tanggal_selesai']||'';
  if (mulai && mulai > today) return false;
  if (selesai && selesai < today) return false;
  return true;
}
function renderTable(data){
  const tbody=document.getElementById('tableBody');
  document.getElementById('rowCount').textContent=data.length+' data';
  if(!data.length){document.getElementById('emptyState').style.display='block';document.getElementById('tableContainer').style.display='none';return;}
  document.getElementById('tableContainer').style.display='block';document.getElementById('emptyState').style.display='none';
  const showAksi=can('edit')||can('delete');
  tbody.innerHTML=data.map(row=>{
    const aktifDb = row['aktif']===true || row['aktif']==='true' || row['aktif']===undefined || row['aktif']===null || row['aktif']==='';
    const menjabat = aktifDb && isMenjabat(row);
    return `<tr>
    <td>${fotoAvatar(row['foto']||'')}</td>
    <td style="font-weight:500">${escHtml(row['nama']||'')}</td>
    <td><span class="badge badge-gold" style="font-size:10px">${escHtml(row['jabatan']||'—')}</span></td>
    <td style="font-size:12px;color:var(--text-secondary)">${fmtTgl(row['tanggal_mulai'])} &ndash; ${row['tanggal_selesai']?fmtTgl(row['tanggal_selesai']):'Sekarang'}</td>
    <td>${menjabat?'<span class="badge badge-green" style="font-size:10px">Masih Menjabat</span>':(aktifDb?'<span class="badge badge-gray" style="font-size:10px">Purna Tugas</span>':'<span class="badge badge-gray" style="font-size:10px">Nonaktif</span>')}</td>
    <td style="text-align:center;font-family:'DM Mono',monospace;font-size:12px">${escHtml(String(row['urutan']??0))}</td>
    ${showAksi?`<td><div class="actions">
      ${can('edit')?`<button class="btn btn-icon btn-sm" onclick="openEditModal(${row._id})"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></button>`:''}
      ${can('delete')?`<button class="btn btn-icon btn-sm" onclick="deleteRow(${row._id})" style="color:var(--danger);border-color:rgba(224,82,82,0.3)"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a1 1 0 011-1h4a1 1 0 011 1v2"/></svg></button>`:''}
    </div></td>`:'<td style="display:none"></td>'}
  </tr>`;}).join('');
  initPagination('dataTable', 20);
}
function applyFilter(){
  const q=document.getElementById('searchInput').value.toLowerCase();
  const st=document.getElementById('filterStatus').value;
  renderTable(allData.filter(r=>{
    const aktifDb = r['aktif']===true || r['aktif']==='true' || r['aktif']===undefined || r['aktif']===null || r['aktif']==='';
    const menjabat = aktifDb && isMenjabat(r);
    if(st==='aktif' && !menjabat) return false;
    if(st==='selesai' && menjabat) return false;
    if(!q) return true;
    return ['nama','jabatan'].some(c=>(r[c]||'').toLowerCase().includes(q));
  }));
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
  if(f){prev.src=window.PERSON_CDN_URL+'/'+f;prev.style.display='block';plac.style.display='none';stat.textContent=f.replace('.webp','').replace(/-/g,' ');}
  else{prev.style.display='none';plac.style.display='flex';stat.textContent='Dari galeri atau upload baru';}
}
function openAddModal(){
  if(!can('create')){toast('Akses Ditolak','Tidak ada izin tambah data','error');return;}
  editRow=null;slugTouched=false;document.getElementById('modalTitle').textContent='Tambah Data Romo';
  ['fieldNama','fieldSlug','fieldJabatan','fieldMulai','fieldSelesai','fieldRiwayat'].forEach(id=>document.getElementById(id).value='');
  document.getElementById('fieldUrutan').value='0';
  document.getElementById('fieldAktif').checked=true;
  resetFotoUI('');openModal('formModal');
}
function openEditModal(rowNum){
  if(!can('edit')){toast('Akses Ditolak','Tidak ada izin edit data','error');return;}
  const row=allData.find(r=>r._id===rowNum);if(!row)return;
  editRow=rowNum;slugTouched=true;document.getElementById('modalTitle').textContent='Edit Data Romo';
  document.getElementById('fieldRow').value=rowNum;
  document.getElementById('fieldNama').value=row['nama']||'';
  document.getElementById('fieldSlug').value=row['slug']||'';
  document.getElementById('fieldJabatan').value=row['jabatan']||'';
  document.getElementById('fieldMulai').value=row['tanggal_mulai']||'';
  document.getElementById('fieldSelesai').value=row['tanggal_selesai']||'';
  document.getElementById('fieldRiwayat').value=row['riwayat']||'';
  document.getElementById('fieldUrutan').value=row['urutan']??0;
  document.getElementById('fieldAktif').checked=!(row['aktif']===false||row['aktif']==='false');
  resetFotoUI(row['foto']||'');openModal('formModal');
}
async function submitForm(){
  const btn=document.getElementById('btnSave');const isEdit=!!editRow;
  if(isEdit&&!can('edit')){toast('Akses Ditolak','Tidak ada izin edit','error');return;}
  if(!isEdit&&!can('create')){toast('Akses Ditolak','Tidak ada izin tambah','error');return;}
  const nama=document.getElementById('fieldNama').value.trim();
  const slug=slugify(document.getElementById('fieldSlug').value.trim());
  const jabatan=document.getElementById('fieldJabatan').value.trim();
  const mulai=document.getElementById('fieldMulai').value;
  const selesai=document.getElementById('fieldSelesai').value;
  if(!nama||!slug||!jabatan||!mulai){toast('Error','Nama, Slug, Jabatan, dan Tanggal Mulai wajib diisi','error');return;}
  if(selesai && selesai<mulai){toast('Error','Tanggal Selesai tidak boleh sebelum Tanggal Mulai','error');return;}
  const data={
    'nama':nama,'slug':slug,'jabatan':jabatan,
    'tanggal_mulai':mulai,'tanggal_selesai':selesai||null,
    'riwayat':document.getElementById('fieldRiwayat').value.trim(),
    'urutan':parseInt(document.getElementById('fieldUrutan').value||'0',10),
    'aktif':document.getElementById('fieldAktif').checked,
    'foto':document.getElementById('fotoValue').value
  };
  btnLoading(btn,true);
  const res=await apiPost('/admin/api/sheets.php',{action:isEdit?'update':'create',page:PAGE,id:editRow,data});
  btnLoading(btn,false);
  if(res.success){toast('Berhasil',isEdit?'Data diperbarui':'Data ditambahkan','success');closeModal('formModal');loadData();}
  else toast('Error',res.error,'error');
}
async function deleteRow(rowNum){
  if(!can('delete')){toast('Akses Ditolak','Tidak ada izin hapus data','error');return;}
  const row=allData.find(r=>r._id===rowNum);
  confirmDialog('Hapus Data Romo?',`"${row?.['nama']||'data ini'}" (${row?.['jabatan']||''}) akan dihapus.`,async()=>{
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
