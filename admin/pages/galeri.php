<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
adminBoot();
$user = requirePageAccess('galeri');
adminHeader('Galeri Foto', 'galeri', $user);
?>

<style>
/* ── Tab pilih mode thumbnail ── */
.thumb-mode-tabs {
  display: flex;
  gap: 0;
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  overflow: hidden;
  margin-bottom: 10px;
}
.thumb-mode-tab {
  flex: 1;
  padding: 8px 12px;
  background: transparent;
  border: none;
  color: var(--text-secondary);
  font-family: 'DM Sans', sans-serif;
  font-size: 13px;
  cursor: pointer;
  transition: all .2s;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
}
.thumb-mode-tab.active {
  background: var(--accent-dim);
  color: var(--accent);
  font-weight: 500;
}
.thumb-mode-tab:not(.active):hover {
  background: rgba(255,255,255,0.04);
  color: var(--text-primary);
}

/* ── Upload area ── */
.thumb-upload-area {
  border: 2px dashed var(--border);
  border-radius: var(--radius-sm);
  padding: 20px;
  text-align: center;
  cursor: pointer;
  transition: all .2s;
  position: relative;
}
.thumb-upload-area:hover {
  border-color: var(--accent);
  background: var(--accent-dim);
}
.thumb-upload-area.drag-over {
  border-color: var(--accent);
  background: var(--accent-dim);
}
.thumb-preview-img {
  width: 100%;
  max-height: 160px;
  object-fit: cover;
  border-radius: var(--radius-sm);
  display: block;
}
.thumb-remove-btn {
  position: absolute;
  top: 6px;
  right: 6px;
  width: 26px;
  height: 26px;
  border-radius: 50%;
  background: rgba(0,0,0,.6);
  color: #fff;
  border: none;
  cursor: pointer;
  font-size: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  line-height: 1;
}
.thumb-remove-btn:hover { background: var(--danger); }

/* ── Upload progress ── */
.upload-prog-wrap {
  margin-top: 8px;
  display: none;
}
.upload-prog-bg {
  height: 3px;
  background: var(--border);
  border-radius: 2px;
  overflow: hidden;
}
.upload-prog-bar {
  height: 100%;
  background: var(--accent);
  width: 0%;
  transition: width .3s;
  border-radius: 2px;
}
.upload-prog-text {
  font-size: 11.5px;
  color: var(--text-muted);
  margin-top: 4px;
}

/* ── Info badge ── */
.thumb-info-badge {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  font-size: 11px;
  color: var(--text-muted);
  margin-top: 6px;
  background: var(--bg-card2);
  border: 1px solid var(--border);
  border-radius: 20px;
  padding: 3px 10px;
}
.thumb-info-badge.success { color: var(--success); border-color: rgba(60,179,113,.3); background: rgba(60,179,113,.08); }
.thumb-info-badge.error   { color: var(--danger);  border-color: rgba(224,82,82,.3);  background: rgba(224,82,82,.08); }

/* ── Media picker grid ── */
.media-picker-wrap {
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  overflow: hidden;
}
.media-picker-toolbar {
  padding: 8px 10px;
  background: var(--bg-card2);
  border-bottom: 1px solid var(--border);
  display: flex;
  gap: 8px;
  align-items: center;
}
.media-picker-toolbar input {
  flex: 1;
  font-size: 12px;
  padding: 5px 8px;
  height: 30px;
}
.media-picker-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
  gap: 6px;
  padding: 10px;
  max-height: 240px;
  overflow-y: auto;
}
.media-item {
  border: 2px solid transparent;
  border-radius: var(--radius-sm);
  overflow: hidden;
  cursor: pointer;
  position: relative;
  aspect-ratio: 4/3;
  background: var(--bg-card2);
  transition: border-color .15s;
}
.media-item:hover { border-color: var(--accent); }
.media-item.selected { border-color: var(--accent); }
.media-item.selected::after {
  content: '✓';
  position: absolute;
  top: 3px; right: 4px;
  background: var(--accent);
  color: #fff;
  width: 16px; height: 16px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 10px;
  line-height: 16px;
  text-align: center;
}
.media-item img {
  width: 100%; height: 100%;
  object-fit: cover;
  display: block;
}
.media-item-name {
  position: absolute;
  bottom: 0; left: 0; right: 0;
  background: rgba(0,0,0,.55);
  color: #fff;
  font-size: 9px;
  padding: 2px 4px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.media-empty {
  text-align: center;
  padding: 24px 12px;
  color: var(--text-muted);
  font-size: 12.5px;
}
.media-loading {
  text-align: center;
  padding: 20px;
}
</style>

<div class="page-header">
  <div class="page-header-left">
    <h1>Galeri Foto</h1>
    <p>Kelola data galeri foto paroki</p>
  </div>
  <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
    <?php if (userCan($user, 'edit')): ?>
    <button class="btn btn-secondary" onclick="openBatchConvertModal()" title="Konversi semua thumbnail yang masih berupa link URL menjadi file lokal">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15"><polyline points="1 4 1 10 7 10"/><polyline points="23 20 23 14 17 14"/><path d="M20.49 9A9 9 0 005.64 5.64L1 10m22 4l-4.64 4.36A9 9 0 013.51 15"/></svg>
      Konversi Link → File
    </button>
    <?php endif; ?>
    <?php if (userCan($user, 'create')): ?>
    <button class="btn btn-primary" onclick="openAddModal()">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      Tambah Foto
    </button>
    <?php endif; ?>
  </div>
</div>

<!-- ── MODAL BATCH CONVERT ─────────────────────────────────────── -->
<?php if (userCan($user, 'edit')): ?>
<div class="modal-overlay" id="batchModal">
  <div class="modal" style="max-width:580px">
    <div class="modal-header">
      <span class="modal-title">Konversi Thumbnail Link → File Lokal</span>
      <button class="modal-close">&times;</button>
    </div>
    <div class="modal-body">
      <div style="background:rgba(201,168,76,.07);border:1px solid rgba(201,168,76,.2);border-radius:var(--radius-sm);padding:12px 14px;font-size:13px;color:var(--text-secondary);line-height:1.7;margin-bottom:16px">
        <strong style="color:var(--accent)">Apa yang dilakukan?</strong><br>
        Semua baris yang kolom <em>Gambar</em>-nya masih berupa link URL (http...) akan didownload,
        dikompres otomatis, disimpan ke <code>/public/galeri/</code>, lalu
        data di Supabase diperbarui dengan nama file baru. Link lama tidak akan dipakai lagi.
      </div>
      <!-- Status sebelum jalan -->
      <div id="batchIdle">
        <p style="font-size:13.5px;color:var(--text-primary)">Klik tombol di bawah untuk memulai proses konversi.</p>
        <p style="font-size:12px;color:var(--text-muted);margin-top:6px">
          ⚠ Proses ini mungkin memakan waktu beberapa menit tergantung jumlah data dan kecepatan server.<br>
          Jangan tutup halaman ini selama proses berjalan.
        </p>
      </div>
      <!-- Progress -->
      <div id="batchRunning" style="display:none">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px">
          <div class="spinner" style="width:20px;height:20px;margin:0;border-width:2px"></div>
          <span style="font-size:13.5px;color:var(--text-primary)" id="batchStatusText">Memproses...</span>
        </div>
      </div>
      <!-- Hasil -->
      <div id="batchResult" style="display:none">
        <div id="batchSummary" style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:14px"></div>
        <div style="max-height:300px;overflow-y:auto;border:1px solid var(--border);border-radius:var(--radius-sm)">
          <table class="data-table" id="batchResultTable">
            <thead><tr><th>Judul</th><th>Status</th><th>Detail</th></tr></thead>
            <tbody id="batchResultBody"></tbody>
          </table>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('batchModal')" id="btnBatchClose">Tutup</button>
      <button class="btn btn-primary" id="btnBatchStart" onclick="startBatchConvert()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15"><polyline points="1 4 1 10 7 10"/><polyline points="23 20 23 14 17 14"/><path d="M20.49 9A9 9 0 005.64 5.64L1 10m22 4l-4.64 4.36A9 9 0 013.51 15"/></svg>
        Mulai Konversi
      </button>
    </div>
  </div>
</div>
<?php endif; ?>

<div class="card">
  <div class="toolbar">
    <div class="toolbar-left">
      <div class="search-wrap">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" class="form-control" id="searchInput" placeholder="Cari judul...">
      </div>
      <select class="form-select" id="filterBulan" style="max-width:160px" onchange="applyFilter()">
        <option value="">Semua Bulan</option>
        <?php
        $bulanList = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
        foreach ($bulanList as $b) echo "<option value=\"$b\">$b</option>";
        ?>
      </select>
    </div>
    <div class="toolbar-right">
      <span id="rowCount" style="font-size:13px;color:var(--text-secondary)">Memuat...</span>
    </div>
  </div>
  <div id="loadingState" style="text-align:center;padding:40px"><div class="spinner"></div></div>
  <div id="tableContainer" style="display:none">
    <div class="table-wrapper">
      <table class="data-table" id="dataTable">
        <thead>
          <tr>
            <th>#</th><th>Tanggal</th><th>Bulan</th><th>Judul</th>
            <th>Thumbnail</th><th>Foto</th><th>Link</th><th>Keterangan</th>
            <th id="thAksi" style="width:90px">Aksi</th>
          </tr>
        </thead>
        <tbody id="tableBody"></tbody>
      </table>
    </div>
  </div>
  <div id="emptyState" class="empty-state" style="display:none">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><circle cx="12" cy="13" r="3"/></svg>
    <p>Belum ada data galeri foto.</p>
  </div>
</div>

<?php if (userCan($user, 'create') || userCan($user, 'edit')): ?>
<div class="modal-overlay" id="formModal">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title" id="modalTitle">Tambah Foto</span>
      <button class="modal-close">&times;</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="fieldRow">
      <div class="form-grid">

        <!-- Tanggal & Bulan -->
        <div class="form-group">
          <label>Tanggal <span class="required">*</span></label>
          <input type="date" class="form-control" id="fieldTanggal">
        </div>
        <div class="form-group">
          <label>Bulan <span class="required">*</span></label>
          <select class="form-select" id="fieldBulan">
            <?php foreach ($bulanList as $b) echo "<option>$b</option>"; ?>
          </select>
        </div>

        <!-- Judul -->
        <div class="form-group full">
          <label>Judul <span class="required">*</span></label>
          <input type="text" class="form-control" id="fieldJudul" placeholder="Nama acara/kegiatan">
        </div>

        <!-- Thumbnail — pilih Upload, Media, atau Link -->
        <div class="form-group full">
          <label>Thumbnail</label>

          <!-- Tab pilih mode -->
          <div class="thumb-mode-tabs">
            <button type="button" class="thumb-mode-tab active" id="tabModeUpload" onclick="switchThumbMode('upload')">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
              Upload Foto
            </button>
            <button type="button" class="thumb-mode-tab" id="tabModeMedia" onclick="switchThumbMode('media')">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
              Media
            </button>
            <button type="button" class="thumb-mode-tab" id="tabModeLink" onclick="switchThumbMode('link')">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 00-7.54-.54l-3 3a5 5 0 007.07 7.07l1.71-1.71"/></svg>
              Copy Link
            </button>
          </div>

          <!-- Mode Upload -->
          <div id="thumbUploadMode">
            <div class="thumb-upload-area" id="thumbDropArea"
                 onclick="document.getElementById('thumbFileInput').click()"
                 ondragover="onDragOver(event)"
                 ondragleave="onDragLeave(event)"
                 ondrop="onDrop(event)">
              <!-- Preview (hidden saat kosong) -->
              <div id="thumbPreviewWrap" style="display:none;position:relative">
                <img id="thumbPreviewImg" class="thumb-preview-img" alt="thumbnail">
                <button type="button" class="thumb-remove-btn" onclick="removeThumbnail(event)">×</button>
              </div>
              <!-- Placeholder -->
              <div id="thumbPlaceholder">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="32" height="32" style="color:var(--text-muted);margin-bottom:8px"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                <div style="font-size:13px;color:var(--text-secondary)">Klik atau seret foto ke sini</div>
                <div style="font-size:11.5px;color:var(--text-muted);margin-top:4px">JPG / PNG · Maks 20MB · Auto-kompres</div>
              </div>
            </div>
            <input type="file" id="thumbFileInput" accept="image/jpeg,image/png,image/webp" style="display:none" onchange="uploadThumb(this.files[0])">

            <!-- Progress bar -->
            <div class="upload-prog-wrap" id="thumbProgWrap">
              <div class="upload-prog-bg"><div class="upload-prog-bar" id="thumbProgBar"></div></div>
              <div class="upload-prog-text" id="thumbProgText">Mengupload...</div>
            </div>

            <!-- Info hasil upload -->
            <div id="thumbInfoBadge" style="display:none" class="thumb-info-badge success">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="11" height="11"><polyline points="20 6 9 17 4 12"/></svg>
              <span id="thumbInfoText"></span>
            </div>
          </div>

          <!-- Mode Media -->
          <div id="thumbMediaMode" style="display:none">
            <div class="media-picker-wrap">
              <div class="media-picker-toolbar">
                <input type="text" class="form-control" id="mediaSearch"
                       placeholder="Cari nama file..." oninput="filterMedia()">
                <button type="button" class="btn btn-secondary"
                        style="font-size:12px;padding:4px 10px;height:30px;white-space:nowrap"
                        onclick="loadMediaFiles(true)">↻ Refresh</button>
              </div>
              <div id="mediaLoading" class="media-loading">
                <div class="spinner" style="width:20px;height:20px;margin:auto;border-width:2px"></div>
              </div>
              <div class="media-picker-grid" id="mediaGrid" style="display:none"></div>
              <div id="mediaEmpty" class="media-empty" style="display:none">
                Tidak ada file gambar di <code>/public/galeri/</code>
              </div>
            </div>
            <!-- Badge file terpilih -->
            <div id="mediaSelectedBadge" style="display:none;margin-top:6px" class="thumb-info-badge success">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="11" height="11"><polyline points="20 6 9 17 4 12"/></svg>
              <span id="mediaSelectedText"></span>
            </div>
          </div>

          <!-- Mode Link -->
          <div id="thumbLinkMode" style="display:none">
            <input type="text" class="form-control" id="fieldGambarLink"
                   placeholder="https://lh3.googleusercontent.com/... atau URL gambar lain"
                   oninput="onLinkInput()">
            <div style="font-size:11.5px;color:var(--text-muted);margin-top:5px">
              Paste link langsung ke file gambar (JPG/PNG/WebP). Cocok untuk link Google Photos, dll.
            </div>
            <!-- Preview link -->
            <div id="linkPreviewWrap" style="display:none;margin-top:8px">
              <img id="linkPreviewImg" style="width:100%;max-height:120px;object-fit:cover;border-radius:var(--radius-sm);border:1px solid var(--border)"
                   alt="preview"
                   onerror="this.style.display='none';document.getElementById('linkPreviewErr').style.display='block'"
                   onload="this.style.display='block';document.getElementById('linkPreviewErr').style.display='none'">
              <div id="linkPreviewErr" style="display:none;font-size:12px;color:var(--danger);margin-top:4px">
                ⚠ URL tidak mengarah ke gambar yang valid
              </div>
            </div>
          </div>

          <!-- Hidden field yang menyimpan final nilai gambar -->
          <input type="hidden" id="fieldGambar">
        </div>

        <!-- Link Album -->
        <div class="form-group full">
          <label>Link Album (Google Foto)</label>
          <input type="url" class="form-control" id="fieldLink" placeholder="https://photos.app.goo.gl/...">
        </div>

        <!-- Kreditasi -->
        <div class="form-group">
          <label>Kreditasi Foto</label>
          <input type="text" class="form-control" id="fieldFoto" placeholder="Tim Dokumentasi SMDTBA">
        </div>

        <!-- Keterangan -->
        <div class="form-group full">
          <label>Keterangan</label>
          <textarea class="form-control" id="fieldKeterangan" rows="2"></textarea>
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

<script>
const PAGE = 'galeri';
const COLS = ['Tanggal','Bulan','Judul','Gambar','Foto','Link','Keterangan'];
let allData = [], editRow = null;
let thumbMode = 'upload'; // 'upload' | 'media' | 'link'

// ── Media state ───────────────────────────────────────────────────────
let allMediaFiles = [];
let mediaLoaded   = false;

// ── Init ─────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
  if (!can('edit') && !can('delete')) {
    const th = document.getElementById('thAksi');
    if (th) th.style.display = 'none';
  }
  loadData();
});

// ── Helper: resolve URL gambar dari berbagai format yang mungkin ada di DB ───
function resolveGambarUrl(gambar) {
  if (!gambar) return '';
  if (gambar.startsWith('http://') || gambar.startsWith('https://')) return gambar;
  if (gambar.startsWith('/public/')) return gambar;
  if (gambar.startsWith('public/')) return '/' + gambar;
  return '/public/galeri/' + gambar;
}

// ── Helper: nilai yang disimpan ke DB (nama file saja, atau URL eksternal) ───
function normalizeGambarValue(gambar) {
  if (!gambar) return '';
  if (gambar.startsWith('http://') || gambar.startsWith('https://')) return gambar;
  return gambar.replace(/^\/?public\/galeri\//, '');
}

// ── Load Data ─────────────────────────────────────────────────────────
async function loadData() {
  document.getElementById('loadingState').style.display = 'block';
  document.getElementById('tableContainer').style.display = 'none';
  document.getElementById('emptyState').style.display = 'none';
  const res = await apiPost('/admin/api/sheets.php', { action: 'list', page: PAGE });
  document.getElementById('loadingState').style.display = 'none';
  if (!res.success) { toast('Error', res.error || 'Gagal memuat data', 'error'); return; }
  allData = res.data || [];
  renderTable(allData);
}

// ── Render Table ──────────────────────────────────────────────────────
function renderTable(data) {
  const tbody = document.getElementById('tableBody');
  document.getElementById('rowCount').textContent = data.length + ' data';
  if (!data.length) {
    document.getElementById('emptyState').style.display = 'block';
    document.getElementById('tableContainer').style.display = 'none';
    tbody.innerHTML = ''; return;
  }
  document.getElementById('tableContainer').style.display = 'block';
  document.getElementById('emptyState').style.display = 'none';

  const showAksiCol = can('edit') || can('delete');
  tbody.innerHTML = data.map((row, i) => {
    const gambar = row['Gambar'] || '';
    const imgSrc = resolveGambarUrl(gambar);
    const thumbCell = imgSrc
      ? `<img src="${escHtml(imgSrc)}" style="width:56px;height:36px;object-fit:cover;border-radius:4px;border:1px solid var(--border)" onerror="this.style.opacity='.3'">`
      : `<span style="color:var(--text-muted);font-size:11px">—</span>`;
    return `<tr>
      <td style="color:var(--text-muted)">${i + 1}</td>
      <td style="white-space:nowrap">${escHtml(row['Tanggal'] || '')}</td>
      <td>${escHtml(row['Bulan'] || '')}</td>
      <td style="font-weight:500">${escHtml(row['Judul'] || '')}</td>
      <td>${thumbCell}</td>
      <td style="font-size:12px">${escHtml(row['Foto'] || '')}</td>
      <td>${row['Link'] ? `<a href="${escHtml(row['Link'])}" target="_blank" style="color:var(--info);font-size:12px">Buka ↗</a>` : ''}</td>
      <td style="font-size:12px;max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${escHtml(row['Keterangan'] || '')}</td>
      ${showAksiCol ? `
      <td>
        <div class="actions">
          ${can('edit') ? `<button class="btn btn-icon btn-sm" onclick="openEditModal(${row._id})" title="Edit">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
          </button>` : ''}
          ${can('delete') ? `<button class="btn btn-icon btn-sm" onclick="deleteRow(${row._id})" title="Hapus" style="color:var(--danger);border-color:rgba(224,82,82,0.3)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a1 1 0 011-1h4a1 1 0 011 1v2"/></svg>
          </button>` : ''}
        </div>
      </td>` : '<td style="display:none"></td>'}
    </tr>`;
  }).join('');
}

// ── Filter ─────────────────────────────────────────────────────────────
function applyFilter() {
  const q     = document.getElementById('searchInput').value.toLowerCase();
  const bulan = document.getElementById('filterBulan').value;
  renderTable(allData.filter(row => {
    const matchQ = !q || (row['Judul'] || '').toLowerCase().includes(q) || (row['Keterangan'] || '').toLowerCase().includes(q);
    const matchB = !bulan || row['Bulan'] === bulan;
    return matchQ && matchB;
  }));
}
document.getElementById('searchInput').addEventListener('input', applyFilter);

// ── Thumbnail Mode Switcher ───────────────────────────────────────────
// opts.keepValue = true  → jangan reset fieldGambar (dipakai saat openEditModal)
function switchThumbMode(mode, opts = {}) {
  thumbMode = mode;
  document.getElementById('tabModeUpload').classList.toggle('active', mode === 'upload');
  document.getElementById('tabModeMedia').classList.toggle('active',  mode === 'media');
  document.getElementById('tabModeLink').classList.toggle('active',   mode === 'link');
  document.getElementById('thumbUploadMode').style.display = mode === 'upload' ? '' : 'none';
  document.getElementById('thumbMediaMode').style.display  = mode === 'media'  ? '' : 'none';
  document.getElementById('thumbLinkMode').style.display   = mode === 'link'   ? '' : 'none';

  // Hanya reset fieldGambar jika TIDAK ada flag keepValue
  if (!opts.keepValue) {
    document.getElementById('fieldGambar').value = '';
  }

  if (mode === 'upload') {
    resetThumbUpload();
  } else if (mode === 'media') {
    loadMediaFiles();
    document.getElementById('mediaSelectedBadge').style.display = 'none';
  } else {
    // mode === 'link'
    if (!opts.keepValue) {
      document.getElementById('fieldGambarLink').value = '';
    }
    document.getElementById('linkPreviewWrap').style.display = 'none';
  }
}

// ── Upload Thumbnail ──────────────────────────────────────────────────
async function uploadThumb(file) {
  if (!file) return;

  const progWrap = document.getElementById('thumbProgWrap');
  const progBar  = document.getElementById('thumbProgBar');
  const progText = document.getElementById('thumbProgText');
  const badge    = document.getElementById('thumbInfoBadge');
  const badgeT   = document.getElementById('thumbInfoText');

  progWrap.style.display = 'block';
  progBar.style.width    = '30%';
  progText.textContent   = 'Mengupload dan memproses...';
  badge.style.display    = 'none';

  const fd = new FormData();
  fd.append('image', file);

  try {
    progBar.style.width = '70%';
    const res  = await fetch('/admin/api/upload_galeri.php', { method: 'POST', body: fd });
    const data = await res.json();
    progBar.style.width = '100%';
    setTimeout(() => { progWrap.style.display = 'none'; progBar.style.width = '0%'; }, 500);

    if (data.success) {
      document.getElementById('thumbPlaceholder').style.display  = 'none';
      document.getElementById('thumbPreviewWrap').style.display   = 'block';
      document.getElementById('thumbPreviewImg').src              = data.url;
      document.getElementById('fieldGambar').value                = data.filename;

      const savedNote = data.compressed ? ` · hemat ${data.saved_pct}% dari ${data.orig_kb}KB` : '';
      badgeT.textContent = `✓ ${data.filename} · ${data.size_kb}KB [${data.format}] · ${data.dimensions}${savedNote}`;
      badge.className    = 'thumb-info-badge success';
      badge.style.display = 'inline-flex';

      // Tandai file baru di media list supaya kalau user pindah ke tab Media langsung kelihatan
      mediaLoaded = false;
    } else {
      progText.textContent = '✗ ' + data.error;
      badgeT.textContent   = data.error;
      badge.className      = 'thumb-info-badge error';
      badge.style.display  = 'inline-flex';
      toast('Gagal Upload', data.error, 'error');
    }
  } catch(e) {
    progWrap.style.display = 'none';
    toast('Error', 'Gagal menghubungi server', 'error');
  }
}

function removeThumbnail(e) {
  e.stopPropagation();
  e.preventDefault();
  resetThumbUpload();
  document.getElementById('fieldGambar').value = '';
  document.getElementById('thumbFileInput').value = '';
  document.getElementById('thumbInfoBadge').style.display = 'none';
}

function resetThumbUpload() {
  document.getElementById('thumbPreviewWrap').style.display = 'none';
  document.getElementById('thumbPlaceholder').style.display = '';
  document.getElementById('thumbProgWrap').style.display    = 'none';
  document.getElementById('thumbInfoBadge').style.display   = 'none';
}

// ── Drag & Drop ───────────────────────────────────────────────────────
function onDragOver(e) {
  e.preventDefault();
  document.getElementById('thumbDropArea').classList.add('drag-over');
}
function onDragLeave(e) {
  document.getElementById('thumbDropArea').classList.remove('drag-over');
}
function onDrop(e) {
  e.preventDefault();
  document.getElementById('thumbDropArea').classList.remove('drag-over');
  const file = e.dataTransfer?.files?.[0];
  if (file && file.type.startsWith('image/')) {
    uploadThumb(file);
  } else {
    toast('Error', 'Hanya file gambar (JPG/PNG) yang diterima', 'error');
  }
}

// ── Media Picker ──────────────────────────────────────────────────────
async function loadMediaFiles(force = false) {
  if (mediaLoaded && !force) return;

  document.getElementById('mediaLoading').style.display = 'block';
  document.getElementById('mediaGrid').style.display    = 'none';
  document.getElementById('mediaEmpty').style.display   = 'none';

  try {
    const res  = await fetch('/admin/api/list_galeri_media.php', {
      cache: 'no-store',
      credentials: 'same-origin'
    });
    const data = await res.json();
    if (!data.success) throw new Error(data.error || 'Gagal memuat media');
    allMediaFiles = data.files || [];
    mediaLoaded   = true;
  } catch(e) {
    allMediaFiles = [];
    mediaLoaded   = true;
    toast('Peringatan', 'Gagal memuat daftar media: ' + e.message, 'error');
  }

  document.getElementById('mediaLoading').style.display = 'none';
  renderMediaGrid(allMediaFiles);
}

function filterMedia() {
  const q = document.getElementById('mediaSearch').value.toLowerCase();
  const filtered = q
    ? allMediaFiles.filter(f => f.filename.toLowerCase().includes(q))
    : allMediaFiles;
  renderMediaGrid(filtered);
}

function renderMediaGrid(files) {
  const grid  = document.getElementById('mediaGrid');
  const empty = document.getElementById('mediaEmpty');

  if (!files.length) {
    grid.style.display  = 'none';
    empty.style.display = 'block';
    return;
  }

  // Ambil nilai saat ini dari fieldGambar untuk highlight item yang sudah terpilih
  const currentVal = document.getElementById('fieldGambar').value;

  grid.style.display  = 'grid';
  empty.style.display = 'none';
  grid.innerHTML = files.map(f => `
    <div class="media-item ${currentVal === f.filename ? 'selected' : ''}"
         data-filename="${escHtml(f.filename)}"
         onclick="selectMediaFile(${JSON.stringify(f.filename)})"
         title="${escHtml(f.filename)} · ${f.size_kb}KB">
      <img src="${escHtml(f.url)}" alt="${escHtml(f.filename)}" loading="lazy"
           onerror="this.parentElement.style.opacity='.35'">
      <div class="media-item-name">${escHtml(f.filename)}</div>
    </div>
  `).join('');
}

function selectMediaFile(filename) {
  // Simpan hanya nama file ke fieldGambar
  document.getElementById('fieldGambar').value = filename;

  // Update visual selected state pakai data-filename attribute
  document.querySelectorAll('#mediaGrid .media-item').forEach(el => {
    el.classList.toggle('selected', el.dataset.filename === filename);
  });

  // Badge konfirmasi
  const badge = document.getElementById('mediaSelectedBadge');
  badge.style.display = 'inline-flex';
  document.getElementById('mediaSelectedText').textContent = filename;
}

// ── Link Input → Auto Download ke File Lokal ─────────────────────────
let linkDownloadTimer = null;
let linkIsDownloading = false;

function onLinkInput() {
  const url = document.getElementById('fieldGambarLink').value.trim();
  clearTimeout(linkDownloadTimer);

  const wrap  = document.getElementById('linkPreviewWrap');
  const badge = document.getElementById('thumbInfoBadge');

  if (!url) {
    wrap.style.display  = 'none';
    badge.style.display = 'none';
    document.getElementById('fieldGambar').value = '';
    return;
  }

  badge.className     = 'thumb-info-badge';
  badge.style.display = 'inline-flex';
  document.getElementById('thumbInfoText').textContent = '⏳ Menunggu selesai mengetik...';

  linkDownloadTimer = setTimeout(() => autoDownloadFromLink(url), 1000);
}

async function autoDownloadFromLink(url) {
  if (linkIsDownloading) return;
  if (!url.startsWith('http://') && !url.startsWith('https://')) return;

  linkIsDownloading = true;
  const badge     = document.getElementById('thumbInfoBadge');
  const badgeText = document.getElementById('thumbInfoText');
  const wrap      = document.getElementById('linkPreviewWrap');

  badge.className     = 'thumb-info-badge';
  badge.style.display = 'inline-flex';
  badgeText.textContent = '⬇ Mendownload dan mengkompres foto...';

  const judul = document.getElementById('fieldJudul').value.trim();

  try {
    const res  = await fetch('/admin/api/download_galeri.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'download_single', url, judul }),
    });
    const data = await res.json();

    if (data.success) {
      document.getElementById('fieldGambar').value = data.filename;

      wrap.style.display = 'block';
      const img = document.getElementById('linkPreviewImg');
      img.style.display = 'block';
      img.src = data.url;

      const savedNote = data.saved_pct > 0 ? ` · hemat ${data.saved_pct}%` : '';
      badge.className    = 'thumb-info-badge success';
      badgeText.textContent = `✓ Tersimpan: ${data.filename} · ${data.size_kb}KB [${data.format}]${savedNote}`;

      toast('Berhasil', `Foto didownload & dikompres → ${data.filename}`, 'success', 3000);
      mediaLoaded = false; // supaya tab Media refresh otomatis
    } else {
      document.getElementById('fieldGambar').value = url;
      wrap.style.display = 'block';
      document.getElementById('linkPreviewImg').src = url;

      badge.className    = 'thumb-info-badge error';
      badgeText.textContent = `⚠ Gagal download: ${data.error} — URL asli akan disimpan`;
    }
  } catch(e) {
    document.getElementById('fieldGambar').value = url;
    badge.className     = 'thumb-info-badge error';
    badge.style.display = 'inline-flex';
    badgeText.textContent = '⚠ Gagal koneksi server — URL asli akan disimpan';
  }

  linkIsDownloading = false;
}

// ── Batch Convert ─────────────────────────────────────────────────────
function openBatchConvertModal() {
  document.getElementById('batchIdle').style.display    = 'block';
  document.getElementById('batchRunning').style.display = 'none';
  document.getElementById('batchResult').style.display  = 'none';
  document.getElementById('btnBatchStart').disabled     = false;
  document.getElementById('btnBatchStart').style.display = '';
  openModal('batchModal');
}

async function startBatchConvert() {
  const btnStart = document.getElementById('btnBatchStart');
  const statusEl = document.getElementById('batchStatusText');

  btnStart.disabled = true;
  btnStart.style.display = 'none';
  document.getElementById('batchIdle').style.display    = 'none';
  document.getElementById('batchRunning').style.display = 'block';
  statusEl.textContent = 'Menghubungi server dan memproses data...';

  try {
    const res  = await fetch('/admin/api/download_galeri.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'batch_convert' }),
    });
    const data = await res.json();

    document.getElementById('batchRunning').style.display = 'none';
    document.getElementById('batchResult').style.display  = 'block';

    if (!data.success) {
      document.getElementById('batchSummary').innerHTML =
        `<div class="badge badge-red">${escHtml(data.error || 'Terjadi kesalahan')}</div>`;
      return;
    }

    const s = data.summary;
    document.getElementById('batchSummary').innerHTML = `
      <div class="badge badge-green">✓ ${s.ok} berhasil dikonversi</div>
      <div class="badge badge-gray">— ${s.skip} sudah file lokal</div>
      ${s.error > 0 ? `<div class="badge badge-red">✗ ${s.error} gagal</div>` : ''}
      <div class="badge" style="background:var(--bg-card2);color:var(--text-muted)">Total: ${s.total}</div>
    `;

    const tbody = document.getElementById('batchResultBody');
    tbody.innerHTML = data.results.map(r => {
      const statusBadge = r.status === 'ok'
        ? `<span class="badge badge-green">✓ OK</span>`
        : r.status === 'skip'
          ? `<span class="badge badge-gray">Skip</span>`
          : `<span class="badge badge-red">✗ Gagal</span>`;
      const detail = r.status === 'ok'
        ? `${escHtml(r.filename)} · ${r.size_kb}KB (hemat ${r.saved_pct||0}%)`
        : escHtml(r.reason || '—');
      return `<tr>
        <td style="font-size:12.5px">${escHtml(r.judul || '—')}</td>
        <td>${statusBadge}</td>
        <td style="font-size:11.5px;color:var(--text-secondary)">${detail}</td>
      </tr>`;
    }).join('');

    if (s.ok > 0) {
      toast('Batch Convert Selesai', `${s.ok} thumbnail berhasil dikonversi ke file lokal`, 'success', 5000);
      mediaLoaded = false;
      loadData();
    }

  } catch(e) {
    document.getElementById('batchRunning').style.display = 'none';
    document.getElementById('batchResult').style.display  = 'block';
    document.getElementById('batchSummary').innerHTML =
      `<div class="badge badge-red">Error koneksi: ${escHtml(e.message)}</div>`;
  }
}

// ── Reset Form ────────────────────────────────────────────────────────
function resetForm() {
  document.getElementById('fieldRow').value        = '';
  document.getElementById('fieldTanggal').value    = '';
  document.getElementById('fieldBulan').value      = '';
  document.getElementById('fieldJudul').value      = '';
  document.getElementById('fieldLink').value       = '';
  document.getElementById('fieldFoto').value       = 'Tim Dokumentasi SMDTBA';
  document.getElementById('fieldKeterangan').value = '';
  document.getElementById('fieldGambar').value     = '';
  document.getElementById('fieldGambarLink').value = '';
  document.getElementById('thumbFileInput').value  = '';
  document.getElementById('linkPreviewWrap').style.display    = 'none';
  document.getElementById('mediaSearch').value                = '';
  document.getElementById('mediaSelectedBadge').style.display = 'none';
  // Default ke mode upload (tanpa keepValue → reset semua state upload juga)
  switchThumbMode('upload');
}

// ── Open Modals ───────────────────────────────────────────────────────
function openAddModal() {
  if (!can('create')) { toast('Akses Ditolak', 'Anda tidak punya izin tambah data', 'error'); return; }
  editRow = null;
  document.getElementById('modalTitle').textContent = 'Tambah Foto';
  resetForm();
  openModal('formModal');
}

function openEditModal(rowNum) {
  if (!can('edit')) { toast('Akses Ditolak', 'Anda tidak punya izin edit data', 'error'); return; }
  const row = allData.find(r => r._id === rowNum);
  if (!row) return;
  editRow = rowNum;
  document.getElementById('modalTitle').textContent = 'Edit Foto';

  // Reset form dulu (termasuk bersihkan fieldGambar & mode upload)
  resetForm();

  // Isi field-field teks
  document.getElementById('fieldRow').value        = rowNum;
  document.getElementById('fieldTanggal').value    = row['Tanggal']    || '';
  document.getElementById('fieldBulan').value      = row['Bulan']      || '';
  document.getElementById('fieldJudul').value      = row['Judul']      || '';
  document.getElementById('fieldLink').value       = row['Link']       || '';
  document.getElementById('fieldFoto').value       = row['Foto']       || '';
  document.getElementById('fieldKeterangan').value = row['Keterangan'] || '';

  const existingGambar = row['Gambar'] || '';
  const normalizedVal  = normalizeGambarValue(existingGambar);

  if (existingGambar) {
    if (existingGambar.startsWith('http://') || existingGambar.startsWith('https://')) {
      // URL eksternal → tampilkan di mode link
      // Gunakan keepValue:true agar switchThumbMode tidak mengosongkan fieldGambar lagi
      switchThumbMode('link', { keepValue: true });
      document.getElementById('fieldGambar').value     = existingGambar;
      document.getElementById('fieldGambarLink').value = existingGambar;
      document.getElementById('linkPreviewWrap').style.display = 'block';
      document.getElementById('linkPreviewImg').src    = existingGambar;
    } else {
      // File lokal → tampilkan preview di mode upload
      const previewSrc = resolveGambarUrl(existingGambar);
      switchThumbMode('upload', { keepValue: true });
      // Set fieldGambar SETELAH switchThumbMode agar tidak ditimpa
      document.getElementById('fieldGambar').value               = normalizedVal;
      document.getElementById('thumbPlaceholder').style.display  = 'none';
      document.getElementById('thumbPreviewWrap').style.display  = 'block';
      document.getElementById('thumbPreviewImg').src             = previewSrc;
      const badge = document.getElementById('thumbInfoBadge');
      badge.className     = 'thumb-info-badge';
      badge.style.display = 'inline-flex';
      document.getElementById('thumbInfoText').textContent =
        normalizedVal + ' · Ganti dengan upload baru atau simpan apa adanya';
    }
  }
  // Jika tidak ada gambar, biarkan mode upload kosong (sudah di-reset oleh resetForm)

  openModal('formModal');
}

// ── Submit Form ───────────────────────────────────────────────────────
async function submitForm() {
  const btn    = document.getElementById('btnSave');
  const isEdit = !!editRow;

  if (isEdit && !can('edit'))    { toast('Akses Ditolak', 'Anda tidak punya izin edit', 'error'); return; }
  if (!isEdit && !can('create')) { toast('Akses Ditolak', 'Anda tidak punya izin tambah', 'error'); return; }

  const judul = document.getElementById('fieldJudul').value.trim();
  if (!judul) { toast('Error', 'Judul wajib diisi', 'error'); return; }

  const gambar = document.getElementById('fieldGambar').value.trim();

  const data = {
    'Tanggal':    document.getElementById('fieldTanggal').value,
    'Bulan':      document.getElementById('fieldBulan').value,
    'Judul':      judul,
    'Gambar':     gambar,
    'Foto':       document.getElementById('fieldFoto').value.trim(),
    'Link':       document.getElementById('fieldLink').value.trim(),
    'Keterangan': document.getElementById('fieldKeterangan').value.trim(),
  };

  btnLoading(btn, true);
  const res = await apiPost('/admin/api/sheets.php', {
    action: isEdit ? 'update' : 'create',
    page: PAGE,
    id: editRow,
    data
  });
  btnLoading(btn, false);

  if (res.success) {
    toast('Berhasil', isEdit ? 'Data diperbarui' : 'Data ditambahkan', 'success');
    closeModal('formModal');
    loadData();
  } else {
    toast('Error', res.error || 'Gagal menyimpan', 'error');
  }
}

// ── Delete ────────────────────────────────────────────────────────────
async function deleteRow(rowNum) {
  if (!can('delete')) { toast('Akses Ditolak', 'Anda tidak punya izin hapus data', 'error'); return; }
  const row = allData.find(r => r._id === rowNum);
  confirmDialog('Hapus Data?', `"${row?.['Judul'] || 'data ini'}" akan dihapus permanen.`, async () => {
    const res = await apiPost('/admin/api/sheets.php', { action: 'delete', page: PAGE, id: rowNum });
    if (res.success) { toast('Berhasil', 'Data dihapus', 'success'); loadData(); }
    else toast('Error', res.error, 'error');
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