<?php
/**
 * admin/pages/umkm.php
 * Kelola UMKM Umat — dengan ownership (milik sendiri) dan status draft/published
 *
 * Akses per role:
 * - superadmin            : semua aksi + publish semua
 * - admin dengan 'publish': semua aksi + publish semua
 * - admin biasa (create/edit/delete): hanya kelola milik sendiri, tidak bisa publish
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
adminBoot();
$user         = requirePageAccess('umkm');
$isSuperadmin = $user['role'] === ROLE_SUPERADMIN;
$permsMap     = $isSuperadmin ? [] : getPermissionsMap($user);
$umkmPerms    = $isSuperadmin ? ['view','create','edit','delete','publish'] : ($permsMap['umkm'] ?? ['view']);
$canCreate    = in_array('create',  $umkmPerms);
$canEdit      = in_array('edit',    $umkmPerms);
$canDelete    = in_array('delete',  $umkmPerms);
$canPublish   = in_array('publish', $umkmPerms) || $isSuperadmin;
$canSeeAll    = $canPublish; // yang bisa publish bisa lihat semua; lainnya hanya milik sendiri

adminHeader('UMKM Umat', 'umkm', $user);
?>

<style>
.umkm-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 16px;
}
.umkm-card {
  background: var(--bg-card2);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  overflow: hidden;
  transition: border-color .2s, box-shadow .2s;
  position: relative;
}
.umkm-card:hover { border-color: var(--accent); box-shadow: 0 4px 18px rgba(0,0,0,.3); }
.umkm-card-img {
  width: 100%; aspect-ratio: 4/3; object-fit: cover;
  display: block; background: var(--bg-main);
}
.umkm-card-body { padding: 10px 14px 6px; }
.umkm-card-title { font-weight: 600; font-size: 13px; color: var(--text-primary); margin-bottom: 2px;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.umkm-card-sub { font-size: 11.5px; color: var(--text-secondary); }
.umkm-card-owner { font-size: 10.5px; color: var(--text-muted); margin-top: 3px; }

/* Status badge di pojok gambar */
.umkm-status-badge {
  position: absolute; top: 8px; left: 8px;
  font-size: 10px; font-weight: 600; padding: 3px 9px;
  border-radius: 20px; backdrop-filter: blur(4px);
}
.umkm-status-badge.draft     { background: rgba(0,0,0,.55); color: #ffd580; }
.umkm-status-badge.published { background: rgba(60,179,113,.85); color: #fff; }

.umkm-card-inactive { opacity: .5; }
.umkm-card-actions { display: flex; gap: 5px; padding: 6px 10px 10px; flex-wrap: wrap; }

.umkm-drop {
  border: 2px dashed var(--border); border-radius: var(--radius);
  padding: 24px 16px; text-align: center; cursor: pointer; transition: all .2s;
}
.umkm-drop:hover, .umkm-drop.drag { border-color: var(--accent); background: var(--accent-dim); }
.umkm-drop p     { font-size: 13px; color: var(--text-secondary); margin: 6px 0 0; }
.umkm-drop small { font-size: 11px; color: var(--text-muted); }
.umkm-preview-wrap { display:none; margin-top:10px; position:relative; }
.umkm-preview-img  { width:100%; max-height:180px; object-fit:cover; border-radius:var(--radius-sm); display:block; }
.umkm-preview-rm   {
  position:absolute; top:6px; right:6px; width:24px; height:24px; border-radius:50%;
  background:rgba(0,0,0,.6); color:#fff; border:none; cursor:pointer; font-size:15px;
  display:flex; align-items:center; justify-content:center;
}
.umkm-preview-rm:hover { background:var(--danger); }
.umkm-upload-prog     { height:3px; background:var(--border); border-radius:2px; overflow:hidden; margin-top:6px; display:none; }
.umkm-upload-prog-bar { height:100%; background:var(--accent); width:0%; transition:width .3s; }

/* Info box untuk user non-publisher */
.umkm-access-info {
  background: rgba(201,168,76,.07); border: 1px solid rgba(201,168,76,.2);
  border-radius: var(--radius-sm); padding: 10px 14px; margin-bottom: 16px;
  font-size: 12.5px; color: var(--text-secondary); line-height: 1.6;
}
.umkm-access-info strong { color: var(--accent); }
</style>

<div class="page-header">
  <div class="page-header-left">
    <h1>UMKM Umat</h1>
    <p>Kelola banner &amp; flyer promosi usaha umat paroki<?= $canSeeAll ? '' : ' milik Anda' ?></p>
  </div>
  <?php if ($canCreate): ?>
  <button class="btn btn-primary" onclick="openAddModal()">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    Tambah Promosi
  </button>
  <?php endif; ?>
</div>

<?php if (!$canPublish): ?>
<div class="umkm-access-info">
  <strong>ℹ Info:</strong> Anda hanya dapat mengelola promosi <strong>milik sendiri</strong>.
  Promosi yang Anda tambahkan akan berstatus <strong>Draft</strong> dan perlu di-publish oleh Admin/Superadmin agar tampil di website.
</div>
<?php endif; ?>

<!-- Toolbar -->
<div class="card" style="margin-bottom:16px">
  <div class="toolbar" style="margin-bottom:0">
    <div class="toolbar-left">
      <div class="search-wrap">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" class="form-control" id="searchInput" placeholder="Cari judul / nama usaha...">
      </div>
      <select class="form-select" id="filterStatus" style="max-width:140px" onchange="applyFilter()">
        <option value="">Semua Status</option>
        <option value="published">Published</option>
        <option value="draft">Draft</option>
      </select>
    </div>
    <div class="toolbar-right">
      <span id="rowCount" style="font-size:13px;color:var(--text-secondary)">Memuat...</span>
    </div>
  </div>
</div>

<div id="loadingState" style="text-align:center;padding:60px"><div class="spinner"></div></div>
<div id="umkmGrid" class="umkm-grid" style="display:none"></div>
<div id="emptyState" class="empty-state" style="display:none">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="48" height="48"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
  <p>Belum ada promosi UMKM<?= $canSeeAll ? '' : ' yang Anda tambahkan' ?>.</p>
</div>

<!-- ── MODAL FORM ────────────────────────────────────────────── -->
<?php if ($canCreate || $canEdit): ?>
<div class="modal-overlay" id="formModal">
  <div class="modal modal-lg">
    <div class="modal-header">
      <span class="modal-title" id="modalTitle">Tambah Promosi UMKM</span>
      <button class="modal-close">&times;</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="fieldId">

      <!-- Upload gambar -->
      <div class="form-group" style="margin-bottom:18px">
        <label>Gambar Banner / Flyer <span class="required">*</span></label>
        <input type="hidden" id="fieldGambar">
        <div class="umkm-drop" id="dropArea"
             onclick="document.getElementById('fileInput').click()"
             ondragover="onDragOver(event)" ondragleave="onDragLeave(event)" ondrop="onDrop(event)">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="32" height="32" style="color:var(--text-muted);margin:0 auto">
            <rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/>
            <polyline points="21 15 16 10 5 21"/>
          </svg>
          <p>Klik atau seret gambar ke sini</p>
          <small>JPG / PNG / WebP · Maks 20MB · Otomatis dikompres</small>
        </div>
        <input type="file" id="fileInput" accept="image/jpeg,image/png,image/webp" style="display:none" onchange="onFileSelected(this)">
        <div class="umkm-preview-wrap" id="previewWrap">
          <img id="previewImg" class="umkm-preview-img" src="" alt="preview">
          <button type="button" class="umkm-preview-rm" onclick="removeImage()">&times;</button>
        </div>
        <div class="umkm-upload-prog" id="uploadProg"><div class="umkm-upload-prog-bar" id="uploadProgBar"></div></div>
        <div id="uploadStatus" style="font-size:12px;color:var(--text-muted);margin-top:4px"></div>
      </div>

      <div class="form-grid">
        <div class="form-group full">
          <label>Judul Promosi <span class="required">*</span></label>
          <input type="text" class="form-control" id="fieldJudul" placeholder="Contoh: Catering Bu Sari — Paket Misa">
        </div>
        <div class="form-group">
          <label>Nama Usaha</label>
          <input type="text" class="form-control" id="fieldNamaUsaha" placeholder="Toko / brand / nama umat">
        </div>
        <div class="form-group">
          <label>Kontak</label>
          <input type="text" class="form-control" id="fieldKontak" placeholder="No WA / Instagram / telepon">
        </div>
        <div class="form-group full">
          <label>Deskripsi</label>
          <textarea class="form-control" id="fieldDeskripsi" rows="3" placeholder="Informasi singkat..."></textarea>
        </div>
        <div class="form-group full">
          <label>Link Google Maps</label>
          <div style="display:flex;align-items:center;gap:8px">
            <svg viewBox="0 0 24 24" fill="#1a73e8" width="18" height="18" style="flex-shrink:0"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
            <input type="url" class="form-control" id="fieldMapsUrl" placeholder="https://maps.app.goo.gl/... atau https://goo.gl/maps/...">
          </div>
          <small style="color:var(--text-muted);font-size:11px;margin-top:4px;display:block">Opsional. Buka Google Maps → bagikan lokasi → salin link</small>
        </div>
        <div class="form-group">
          <label>Urutan Tampil</label>
          <input type="number" class="form-control" id="fieldUrutan" value="0" min="0" style="max-width:100px">
          <small style="color:var(--text-muted);font-size:11px">Angka kecil tampil lebih dulu</small>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('formModal')">Batal</button>
      <button class="btn btn-primary" id="btnSave" onclick="submitForm()">Simpan sebagai Draft</button>
    </div>
  </div>
</div>
<?php endif; ?>

<script>
const CAN_CREATE  = <?= json_encode($canCreate) ?>;
const CAN_EDIT    = <?= json_encode($canEdit) ?>;
const CAN_DELETE  = <?= json_encode($canDelete) ?>;
const CAN_PUBLISH = <?= json_encode($canPublish) ?>;
const CAN_SEE_ALL = <?= json_encode($canSeeAll) ?>;
const MY_USERNAME = <?= json_encode($user['username']) ?>;

let allData = [], editId = null;

// ── Load data ─────────────────────────────────────────────────────────
async function loadData() {
  document.getElementById('loadingState').style.display = 'block';
  document.getElementById('umkmGrid').style.display     = 'none';
  const res = await apiPost('/admin/api/sheets.php', { action:'list', page:'umkm' });
  document.getElementById('loadingState').style.display = 'none';
  if (!res.success) { toast('Error', res.error, 'error'); return; }

  // Semua user bisa melihat semua data
  // Pembatasan "milik sendiri" hanya berlaku untuk aksi edit/hapus (di renderGrid)
  allData = res.data || [];
  applyFilter();
}

function applyFilter() {
  const q = document.getElementById('searchInput').value.toLowerCase();
  const s = document.getElementById('filterStatus').value;
  const filtered = allData.filter(r =>
    (!q || (r['judul']||'').toLowerCase().includes(q) || (r['nama_usaha']||'').toLowerCase().includes(q)) &&
    (!s || (r['status']||'draft') === s)
  );
  document.getElementById('rowCount').textContent = filtered.length + ' item';
  renderGrid(filtered);
}
document.getElementById('searchInput').addEventListener('input', applyFilter);

// ── Render grid ───────────────────────────────────────────────────────
function renderGrid(data) {
  const grid  = document.getElementById('umkmGrid');
  const empty = document.getElementById('emptyState');
  if (!data.length) { grid.style.display='none'; empty.style.display='block'; return; }
  grid.style.display  = 'grid';
  empty.style.display = 'none';

  const sorted = [...data].sort((a,b)=>(a['urutan']??999)-(b['urutan']??999));
  grid.innerHTML = sorted.map(r => {
    const status   = r['status'] || 'draft';
    const isPublished = status === 'published';
    const imgSrc   = r['gambar'] ? '/public/umkm/' + r['gambar'] : '';
    const isMine   = r['created_by'] === MY_USERNAME;
    const canEditThis   = CAN_EDIT   && (CAN_SEE_ALL || isMine);
    const canDeleteThis = CAN_DELETE && (CAN_SEE_ALL || isMine);

    return `<div class="umkm-card ${isPublished?'':'umkm-card-inactive'}">
      ${imgSrc
        ? `<img class="umkm-card-img" src="${escHtml(imgSrc)}" alt="${escHtml(r['judul']||'')}" loading="lazy">`
        : `<div class="umkm-card-img" style="background:var(--bg-card2);display:flex;align-items:center;justify-content:center;color:var(--text-muted);font-size:28px">📷</div>`}
      <span class="umkm-status-badge ${status}">${isPublished ? '✓ Published' : '◌ Draft'}</span>
      <div class="umkm-card-body">
        <div class="umkm-card-title">${escHtml(r['judul']||'—')}</div>
        ${r['nama_usaha'] ? `<div class="umkm-card-sub">${escHtml(r['nama_usaha'])}</div>` : ''}
        ${r['kontak']     ? `<div class="umkm-card-sub" style="color:var(--accent)">${escHtml(r['kontak'])}</div>` : ''}
        ${r['maps_url']   ? `<div class="umkm-card-sub" style="color:#4285f4;font-size:10.5px">📍 Ada lokasi Maps</div>` : ''}
        ${CAN_SEE_ALL && r['created_by'] ? `<div class="umkm-card-owner">oleh: ${escHtml(r['created_by'])}</div>` : ''}
      </div>
      <div class="umkm-card-actions">
        ${canEditThis ? `<button class="btn btn-secondary btn-sm" style="flex:1" onclick="openEditModal(${r._id})">Edit</button>` : ''}
        ${CAN_PUBLISH ? (isPublished
          ? `<button class="btn btn-secondary btn-sm" onclick="setStatus(${r._id},'draft')" title="Tarik ke Draft">↩ Draft</button>`
          : `<button class="btn btn-primary btn-sm" onclick="setStatus(${r._id},'published')" title="Publish">▶ Publish</button>`
        ) : ''}
        ${canDeleteThis ? `<button class="btn btn-icon btn-sm" style="color:var(--danger);border-color:rgba(224,82,82,.3)" onclick="deleteItem(${r._id})" title="Hapus">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a1 1 0 011-1h4a1 1 0 011 1v2"/></svg>
        </button>` : ''}
      </div>
    </div>`;
  }).join('');
}

// ── Upload ────────────────────────────────────────────────────────────
function onDragOver(e)  { e.preventDefault(); document.getElementById('dropArea').classList.add('drag'); }
function onDragLeave()  { document.getElementById('dropArea').classList.remove('drag'); }
function onDrop(e)      { e.preventDefault(); document.getElementById('dropArea').classList.remove('drag'); const f=e.dataTransfer.files[0]; if(f) processFile(f); }
function onFileSelected(i) { if(i.files[0]) processFile(i.files[0]); i.value=''; }

async function processFile(file) {
  const statusEl = document.getElementById('uploadStatus');
  const progWrap = document.getElementById('uploadProg');
  const progBar  = document.getElementById('uploadProgBar');
  const prevWrap = document.getElementById('previewWrap');
  const prevImg  = document.getElementById('previewImg');
  const dropArea = document.getElementById('dropArea');

  if (file.size > 20*1024*1024) { statusEl.textContent='⚠ File terlalu besar (maks 20MB)'; statusEl.style.color='var(--danger)'; return; }
  if (!['image/jpeg','image/png','image/webp'].includes(file.type)) { statusEl.textContent='⚠ Format tidak didukung'; statusEl.style.color='var(--danger)'; return; }

  prevImg.src = URL.createObjectURL(file);
  prevWrap.style.display = 'block'; dropArea.style.display = 'none';
  statusEl.textContent = 'Mengupload...'; statusEl.style.color='var(--text-muted)';
  progWrap.style.display='block'; progBar.style.width='30%';

  const fd = new FormData(); fd.append('image', file);
  try {
    progBar.style.width='60%';
    const res  = await fetch('/admin/api/upload_umkm.php', { method:'POST', body:fd });
    progBar.style.width='100%';
    const data = await res.json();
    if (data.success) {
      document.getElementById('fieldGambar').value = data.filename;
      prevImg.src = data.url;
      statusEl.textContent = `✓ ${data.size_kb}KB · ${data.dimensions}`; statusEl.style.color='var(--success)';
    } else {
      statusEl.textContent = '✗ '+(data.error||'Upload gagal'); statusEl.style.color='var(--danger)';
      removeImage();
    }
  } catch(e) { statusEl.textContent='✗ Gagal menghubungi server'; statusEl.style.color='var(--danger)'; removeImage(); }
}

function removeImage() {
  document.getElementById('fieldGambar').value='';
  document.getElementById('previewWrap').style.display='none';
  document.getElementById('dropArea').style.display='block';
  document.getElementById('uploadStatus').textContent='';
  document.getElementById('uploadProg').style.display='none';
  document.getElementById('uploadProgBar').style.width='0%';
}

// ── Modal ─────────────────────────────────────────────────────────────
function resetForm() {
  document.getElementById('fieldId').value        = '';
  document.getElementById('fieldJudul').value     = '';
  document.getElementById('fieldNamaUsaha').value = '';
  document.getElementById('fieldKontak').value    = '';
  document.getElementById('fieldDeskripsi').value = '';
  document.getElementById('fieldUrutan').value    = '0';
  document.getElementById('fieldMapsUrl').value   = '';
  removeImage();
}

function openAddModal() {
  if (!CAN_CREATE) { toast('Akses Ditolak','Tidak ada izin tambah','error'); return; }
  editId = null;
  document.getElementById('modalTitle').textContent = 'Tambah Promosi UMKM';
  resetForm();
  openModal('formModal');
}

function openEditModal(id) {
  if (!CAN_EDIT) { toast('Akses Ditolak','Tidak ada izin edit','error'); return; }
  const r = allData.find(x => x._id===id); if (!r) return;
  // Cek ownership
  if (!CAN_SEE_ALL && r['created_by'] !== MY_USERNAME) { toast('Akses Ditolak','Anda hanya bisa mengedit milik sendiri','error'); return; }

  editId = id;
  document.getElementById('modalTitle').textContent = 'Edit Promosi UMKM';
  document.getElementById('fieldId').value        = id;
  document.getElementById('fieldJudul').value     = r['judul']      || '';
  document.getElementById('fieldNamaUsaha').value = r['nama_usaha'] || '';
  document.getElementById('fieldKontak').value    = r['kontak']     || '';
  document.getElementById('fieldDeskripsi').value = r['deskripsi']  || '';
  document.getElementById('fieldUrutan').value    = r['urutan']     ?? 0;
  document.getElementById('fieldMapsUrl').value   = r['maps_url']   || '';

  if (r['gambar']) {
    document.getElementById('fieldGambar').value         = r['gambar'];
    document.getElementById('previewImg').src            = '/public/umkm/' + r['gambar'];
    document.getElementById('previewWrap').style.display = 'block';
    document.getElementById('dropArea').style.display    = 'none';
    document.getElementById('uploadStatus').textContent  = '';
  } else { removeImage(); }
  openModal('formModal');
}

// ── Submit ────────────────────────────────────────────────────────────
async function submitForm() {
  const btn    = document.getElementById('btnSave');
  const id     = document.getElementById('fieldId').value;
  const isEdit = !!editId;
  const judul  = document.getElementById('fieldJudul').value.trim();
  const gambar = document.getElementById('fieldGambar').value;

  if (!judul)  { toast('Error','Judul wajib diisi','error'); return; }
  if (!gambar) { toast('Error','Gambar wajib diupload','error'); return; }

  const data = {
    'judul'      : judul,
    'nama_usaha' : document.getElementById('fieldNamaUsaha').value.trim(),
    'kontak'     : document.getElementById('fieldKontak').value.trim(),
    'deskripsi'  : document.getElementById('fieldDeskripsi').value.trim(),
    'maps_url'   : document.getElementById('fieldMapsUrl').value.trim() || null,
    'gambar'     : gambar,
    'urutan'     : parseInt(document.getElementById('fieldUrutan').value) || 0,
    'aktif'      : false,
    'status'     : 'draft',
    'created_by' : isEdit ? undefined : MY_USERNAME,
  };
  // Hapus created_by jika edit (tidak diubah)
  if (isEdit) delete data['created_by'];

  btnLoading(btn, true);
  const res = await apiPost('/admin/api/sheets.php', {
    action: isEdit ? 'update' : 'create', page:'umkm',
    ...(id && {id}), data
  });
  btnLoading(btn, false);

  if (res.success) {
    toast('Berhasil', isEdit ? 'Data diperbarui (status: Draft)' : 'Ditambahkan sebagai Draft', 'success');
    closeModal('formModal'); loadData();
  } else toast('Error', res.error, 'error');
}

// ── Publish / Unpublish ───────────────────────────────────────────────
async function setStatus(id, newStatus) {
  if (!CAN_PUBLISH) { toast('Akses Ditolak','Hanya admin/superadmin yang bisa publish','error'); return; }
  const r = allData.find(x=>x._id===id);
  const label = newStatus === 'published' ? 'Publish' : 'Tarik ke Draft';
  confirmDialog(label + '?',
    newStatus === 'published'
      ? `"${r?.['judul']||'item ini'}" akan tampil di website.`
      : `"${r?.['judul']||'item ini'}" akan disembunyikan dari website.`,
    async () => {
      const res = await apiPost('/admin/api/sheets.php', {
        action:'update', page:'umkm', id,
        data: {
          'status' : newStatus,
          'aktif'  : newStatus === 'published',
        }
      });
      if (res.success) { toast('Berhasil', label + ' berhasil', 'success'); loadData(); }
      else toast('Error', res.error, 'error');
    }
  );
}

// ── Delete ────────────────────────────────────────────────────────────
async function deleteItem(id) {
  if (!CAN_DELETE) { toast('Akses Ditolak','Tidak ada izin hapus','error'); return; }
  const r = allData.find(x=>x._id===id);
  if (!CAN_SEE_ALL && r?.['created_by'] !== MY_USERNAME) { toast('Akses Ditolak','Anda hanya bisa menghapus milik sendiri','error'); return; }
  confirmDialog('Hapus Promosi?', `"${r?.['judul']||'item ini'}" akan dihapus permanen.`, async () => {
    const res = await apiPost('/admin/api/sheets.php', { action:'delete', page:'umkm', id });
    if (res.success) { toast('Berhasil','Dihapus','success'); loadData(); }
    else toast('Error', res.error, 'error');
  });
}

document.addEventListener('DOMContentLoaded', loadData);
</script>

<?php adminFooter(); ?>