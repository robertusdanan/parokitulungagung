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

/* ── Thumb upload — collapsed by default ── */
.thumb-toggle-btn {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 7px 14px; border-radius: var(--radius-sm);
  background: var(--bg-card2); border: 1px dashed var(--border);
  color: var(--text-secondary); font-size: 12.5px; cursor: pointer;
  transition: all .18s; width: 100%; justify-content: center;
}
.thumb-toggle-btn:hover { border-color: var(--accent); color: var(--accent); background: var(--accent-dim); }
.thumb-toggle-btn.has-thumb { border-style: solid; border-color: var(--accent); color: var(--accent); background: var(--accent-dim); }
.thumb-toggle-btn svg.chevron { transition: transform .25s; }
.thumb-toggle-btn.open svg.chevron { transform: rotate(180deg); }
.thumb-collapse {
  display: grid; grid-template-rows: 0fr;
  transition: grid-template-rows .28s ease;
  margin-top: 0;
}
.thumb-collapse.open { grid-template-rows: 1fr; }
.thumb-collapse > .thumb-collapse-inner { overflow: hidden; padding-top: 8px; }

/* ── Album upload zone — redesign bersih ── */
.album-upload-zone-v2 {
  border: 2px dashed var(--border);
  border-radius: var(--radius-sm);
  padding: 18px 16px;
  text-align: center;
  cursor: pointer;
  transition: all .2s;
}
.album-upload-zone-v2:hover,
.album-upload-zone-v2.drag-over {
  border-color: var(--accent);
  background: var(--accent-dim);
}
.album-upload-zone-v2.uploading {
  pointer-events: none; opacity: .7;
}
</style>

<div class="page-header">
  <div class="page-header-left">
    <h1>Galeri Foto</h1>
    <p>Kelola data galeri foto paroki</p>
  </div>
  <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
    <?php if (userCan($user, 'create')): ?>
    <button class="btn btn-primary" onclick="openAddModal()">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      Tambah Album
    </button>
    <?php endif; ?>
  </div>
</div>

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
            <th>Thumbnail</th><th>Kreditasi Foto</th><th>Folder R2</th><th>Keterangan</th>
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
      <span class="modal-title" id="modalTitle">Tambah Album</span>
      <button class="modal-close">&times;</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="fieldRow">
      <div class="form-grid">

        <!-- Tanggal (Bulan otomatis dari Tanggal) -->
        <div class="form-group">
          <label>Tanggal <span class="required">*</span></label>
          <input type="date" class="form-control" id="fieldTanggal" oninput="autoFillBulan(this.value)">
          <input type="hidden" id="fieldBulan">
        </div>
        <div style="display:none">
          <span id="bulanDisplay">—</span>
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
            <!-- Toggle button — menampilkan thumb preview atau placeholder klik -->
            <button type="button" class="thumb-toggle-btn" id="thumbToggleBtn" onclick="toggleThumbUpload()">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
              <span id="thumbToggleLabel">Pilih thumbnail…</span>
              <svg class="chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="11" height="11"><polyline points="6 9 12 15 18 9"/></svg>
            </button>
            <!-- Collapsible area -->
            <div class="thumb-collapse" id="thumbCollapse">
              <div class="thumb-collapse-inner">
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
                Belum ada thumbnail yang diupload ke R2
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

        <!-- Folder Album R2 — search autocomplete + inline upload -->
        <div class="form-group full">
          <label>Folder Album</label>

          <!-- Wrapper search — position:relative agar dropdown muncul di bawah input -->
          <div style="position:relative">
            <!-- Icon folder di kiri -->
            <span style="position:absolute;left:11px;top:50%;transform:translateY(-50%);
                         pointer-events:none;z-index:1;display:flex;align-items:center">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                   width="15" height="15" style="color:var(--text-muted)">
                <path d="M22 19a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2h5l2 3h9a2 2 0 012 2z"/>
              </svg>
            </span>

            <input type="text" class="form-control" id="fieldLink"
                   placeholder="Cari nama folder album"
                   autocomplete="off"
                   style="padding-left:34px;padding-right:110px"
                   oninput="onFolderInput(this.value)"
                   onfocus="onFolderFocus()"
                   onblur="onFolderBlur()">

            <!-- Spinner + hint badge di kanan field -->
            <span style="position:absolute;right:10px;top:50%;transform:translateY(-50%);
                         display:flex;align-items:center;gap:5px;pointer-events:none">
              <span id="folderSearchSpinner" style="display:none">
                <div class="spinner" style="width:13px;height:13px;border-width:2px;margin:0"></div>
              </span>
              <span id="folderSearchHint"
                    style="font-size:10.5px;color:var(--text-muted);background:var(--bg-card2);
                           border:1px solid var(--border);border-radius:4px;padding:1px 7px;
                           white-space:nowrap">ketik untuk cari</span>
            </span>

            <!-- Dropdown — muncul tepat di bawah input, dalam wrapper ini -->
            <div id="folderDropdown"
                 style="display:none;position:absolute;top:100%;left:0;right:0;z-index:999;
                        background:var(--bg-card);border:1px solid var(--border);
                        border-top:none;border-radius:0 0 var(--radius-sm) var(--radius-sm);
                        box-shadow:0 8px 20px rgba(0,0,0,.22);max-height:200px;overflow-y:auto">
            </div>
          </div>

          <!-- Divider -->
          <div style="display:flex;align-items:center;gap:10px;margin:10px 0 8px">
            <div style="flex:1;height:1px;background:var(--border)"></div>
            <span style="font-size:11px;color:var(--text-muted);white-space:nowrap">atau upload album baru</span>
            <div style="flex:1;height:1px;background:var(--border)"></div>
          </div>

          <!-- Tombol toggle upload -->
          <button type="button" class="btn btn-secondary" id="btnToggleUploadAlbum"
                  onclick="toggleAlbumUploadArea()"
                  style="width:100%;justify-content:center;gap:6px;font-size:12.5px;border-style:dashed">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14">
              <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/>
              <polyline points="17 8 12 3 7 8"/>
              <line x1="12" y1="3" x2="12" y2="15"/>
            </svg>
            Upload Album Baru
          </button>

          <!-- Panel upload — tersembunyi sampai tombol diklik -->
          <div id="albumUploadArea" style="display:none;margin-top:8px;padding:14px;
               background:var(--bg-card2);border:1px solid var(--border);
               border-radius:var(--radius-sm)">

            <!-- Override nama folder -->
            <div style="margin-bottom:10px">
              <label style="font-size:11.5px;font-weight:500;color:var(--text-secondary);
                            margin-bottom:4px;display:block">
                Nama Album
                <span style="font-weight:400;color:var(--text-muted)">
                  — opsional, kosongkan untuk pakai nama folder asli
                </span>
              </label>
              <input type="text" class="form-control" id="albumFolderName"
                     placeholder="cth: Misa Natal 2024"
                     style="font-size:13px">
            </div>

            <!-- Dropzone -->
            <div class="album-upload-zone-v2" id="albumDropzone"
                 onclick="document.getElementById('albumFolderInput').click()"
                 ondragover="onAlbumDragOver(event)"
                 ondragleave="onAlbumDragLeave(event)"
                 ondrop="onAlbumDrop(event)">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                   width="28" height="28" style="color:var(--text-muted);margin-bottom:8px">
                <path d="M22 19a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2h5l2 3h9a2 2 0 012 2z"/>
                <polyline points="16 16 12 12 8 16"/>
                <line x1="12" y1="12" x2="12" y2="21"/>
              </svg>
              <div style="font-size:13px;font-weight:600;color:var(--text-secondary)">Seret folder ke sini</div>
              <div style="font-size:11.5px;color:var(--text-muted);margin-top:3px">
                atau <span style="color:var(--accent);text-decoration:underline">klik untuk pilih folder</span>
              </div>
              <div style="font-size:10.5px;color:var(--text-muted);margin-top:8px;padding-top:8px;border-top:1px solid var(--border)">
                Foto → WebP · Video → H.264/MP4 + thumbnail otomatis
              </div>
            </div>
            <input type="file" id="albumFolderInput" webkitdirectory multiple style="display:none"
                   onchange="onAlbumFolderInputChange(event)">

            <!-- Progress bar -->
            <div id="albumUploadProgress" class="album-upload-progress" style="display:none">
              <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px">
                <span id="albumProgressLabel" style="font-size:12px;color:var(--text-secondary)">Mengupload...</span>
                <span id="albumProgressCount" style="font-size:12px;color:var(--text-muted)">0 / 0</span>
              </div>
              <div class="album-upload-progress-bar-bg">
                <div class="album-upload-progress-bar" id="albumProgressBar"></div>
              </div>
            </div>

            <!-- Log per-file -->
            <div class="album-upload-log" id="albumUploadLog"></div>

            <!-- Ringkasan selesai -->
            <div id="albumUploadResult"
                 style="display:none;margin-top:8px;padding:10px 12px;
                        background:rgba(60,179,113,.08);border:1px solid rgba(60,179,113,.25);
                        border-radius:var(--radius-sm);font-size:12.5px;color:var(--text-secondary)">
            </div>
          </div>
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

// ── Folder R2 autocomplete state ──────────────────────────────────────
let allFolderNames = [];
let folderNamesLoaded = false;
let folderDebounceTimer = null;
let folderDropdownVisible = false;

const BULAN_NAMES = ['Januari','Februari','Maret','April','Mei','Juni',
                     'Juli','Agustus','September','Oktober','November','Desember'];

// ── Auto-fill Bulan dari Tanggal ──────────────────────────────────────
function autoFillBulan(dateVal) {
  if (!dateVal) {
    document.getElementById('fieldBulan').value = '';
    document.getElementById('bulanDisplay').textContent = '—';
    return;
  }
  const d = new Date(dateVal + 'T00:00:00');
  const bulan = BULAN_NAMES[d.getMonth()];
  document.getElementById('fieldBulan').value = bulan;
  document.getElementById('bulanDisplay').textContent = bulan;
}

// ── Folder R2 Autocomplete ────────────────────────────────────────────
async function loadFolderNames() {
  if (folderNamesLoaded) return;
  try {
    const res = await apiPost('/admin/api/r2_manager.php', { action: 'list_album_names' });
    if (res.success && Array.isArray(res.data)) {
      allFolderNames = res.data;
      folderNamesLoaded = true;
    }
  } catch(e) { /* silent fail */ }
}

// Escape karakter spesial regex agar aman dipakai di new RegExp()
function escapeRegex(str) {
  return str.replace(/[-[\]{}()*+?.,\\^$|#\s]/g, '\\$&');
}

function onFolderInput(val) {
  clearTimeout(folderDebounceTimer);
  if (!val.trim()) { hideFolderDropdown(); return; }
  // Debounce 350ms — tidak tiap keystroke
  folderDebounceTimer = setTimeout(() => showFolderResults(val.trim()), 350);
}

function onFolderFocus() {
  const val = document.getElementById('fieldLink').value.trim();
  if (val) showFolderResults(val);
  else loadFolderNames(); // preload di background
}

function onFolderBlur() {
  // Delay agar klik pada dropdown sempat terdaftar sebelum blur menutup dropdown
  setTimeout(() => hideFolderDropdown(), 200);
}

async function showFolderResults(query) {
  const spinner = document.getElementById('folderSearchSpinner');
  const hint    = document.getElementById('folderSearchHint');
  spinner.style.display = 'inline-block';
  if (hint) hint.style.display = 'none';

  if (!folderNamesLoaded) await loadFolderNames();

  spinner.style.display = 'none';

  const q = query.toLowerCase();
  const matches = allFolderNames.filter(n => n.toLowerCase().includes(q));

  const dd = document.getElementById('folderDropdown');

  if (!matches.length) {
    dd.innerHTML = `<div style="padding:11px 14px;font-size:12.5px;color:var(--text-muted);
                        display:flex;align-items:center;gap:7px">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="13" height="13">
        <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
      </svg>
      Tidak ada folder yang cocok dengan "<em>${escHtml(query)}</em>"
    </div>`;
  } else {
    const countLabel = matches.length > 1
      ? `<div style="padding:5px 12px 4px;font-size:10.5px;color:var(--text-muted);
             border-bottom:1px solid var(--border);letter-spacing:.03em">
           ${matches.length} folder ditemukan
         </div>`
      : '';
    dd.innerHTML = countLabel + matches.map(name => {
      const hi = name.replace(
        new RegExp('(' + escapeRegex(query) + ')', 'gi'),
        '<strong style="color:var(--accent)">$1</strong>'
      );
      return `<div class="folder-dd-item" onmousedown="selectFolder(${JSON.stringify(name)})"
                   style="padding:9px 14px;font-size:13px;cursor:pointer;
                          border-bottom:1px solid var(--border);
                          display:flex;align-items:center;gap:9px;transition:background .12s"
                   onmouseover="this.style.background='var(--accent-dim)'"
                   onmouseout="this.style.background=''">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
             width="14" height="14" style="color:var(--accent);opacity:.7;flex-shrink:0">
          <path d="M22 19a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2h5l2 3h9a2 2 0 012 2z"/>
        </svg>
        <span style="flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${hi}</span>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
             width="11" height="11" style="color:var(--text-muted);flex-shrink:0;opacity:.5">
          <polyline points="9 18 15 12 9 6"/>
        </svg>
      </div>`;
    }).join('');
  }
  dd.style.display = 'block';
  folderDropdownVisible = true;
}

function selectFolder(name) {
  document.getElementById('fieldLink').value = name;
  // Tampilkan badge "✓ terpilih" di kanan field
  const hint = document.getElementById('folderSearchHint');
  if (hint) {
    hint.textContent = '✓ terpilih';
    hint.style.color       = 'var(--success, #3cb371)';
    hint.style.borderColor = 'rgba(60,179,113,.3)';
    hint.style.background  = 'rgba(60,179,113,.08)';
    hint.style.display     = 'inline-block';
  }
  hideFolderDropdown();
}

function hideFolderDropdown() {
  document.getElementById('folderDropdown').style.display = 'none';
  folderDropdownVisible = false;
}

// ── Init ─────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
  if (!can('edit') && !can('delete')) {
    const th = document.getElementById('thAksi');
    if (th) th.style.display = 'none';
  }
  loadData();
});

// ── Helper: resolve URL gambar dari berbagai format yang mungkin ada di DB ───
// Admin langsung ke CDN R2 — tanpa proxy watermark, ringan & cepat.
// Format kolom Gambar:
//   - URL https lengkap                  → pakai apa adanya
//   - R2 key baru: "_thumbnails/galeri/x.webp" → prefix CDN
//   - Nama file lama: "cover.jpg"        → coba cari di _thumbnails/galeri/
//   - Path lama: "/public/galeri/x.jpg"  → strip prefix, coba _thumbnails/galeri/
const R2_CDN = 'https://img.parokitulungagung.org';

function resolveGambarUrl(gambar) {
  if (!gambar) return '';
  if (gambar.startsWith('http://') || gambar.startsWith('https://')) return gambar;
  // R2 key baru dengan prefix lengkap
  if (gambar.startsWith('_thumbnails/')) return R2_CDN + '/' + gambar;
  // Strip path lama jika ada
  const filename = gambar.replace(/^\/?public\/galeri\//, '');
  // Asumsi ada di _thumbnails/galeri/ (data baru) atau root (data lama)
  return R2_CDN + '/_thumbnails/galeri/' + filename;
}

// ── Helper: nilai yang disimpan ke DB ───────────────────────────────────────
// - Data baru: r2key penuh "_thumbnails/galeri/x.webp" atau URL https
// - Data lama: nama file saja "cover.jpg" atau "/public/galeri/cover.jpg"
// Semua disimpan apa adanya; resolveGambarUrl() yang handle tampilannya.
function normalizeGambarValue(gambar) {
  if (!gambar) return '';
  if (gambar.startsWith('http://') || gambar.startsWith('https://')) return gambar;
  if (gambar.startsWith('_thumbnails/')) return gambar; // r2key baru, simpan apa adanya
  return gambar.replace(/^\/?public\/galeri\//, ''); // data lama, strip prefix
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
    // Indikator ringan — tidak load gambar, hanya tanda ada/tidak
    const thumbDot = gambar
      ? `<span title="Thumbnail tersedia: ${escHtml(gambar.split('/').pop())}"
               style="display:inline-flex;align-items:center;gap:5px;font-size:11.5px;
                      color:var(--success,#3cb371)">
           <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                width="13" height="13" style="flex-shrink:0">
             <rect x="3" y="3" width="18" height="18" rx="2"/>
             <circle cx="8.5" cy="8.5" r="1.5"/>
             <polyline points="21 15 16 10 5 21"/>
           </svg>
           Ada
         </span>`
      : `<span style="display:inline-flex;align-items:center;gap:4px;font-size:11.5px;
                      color:var(--text-muted)">
           <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                width="12" height="12" style="flex-shrink:0">
             <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
           </svg>
           —
         </span>`;
    return `<tr>
      <td style="color:var(--text-muted)">${i + 1}</td>
      <td style="white-space:nowrap">${escHtml(row['Tanggal'] || '')}</td>
      <td>${escHtml(row['Bulan'] || '')}</td>
      <td style="font-weight:500">${escHtml(row['Judul'] || '')}</td>
      <td style="text-align:center">${thumbDot}</td>
      <td style="font-size:12px">${escHtml(row['Foto'] || '')}</td>
      <td style="font-size:12px">
        ${row['Link'] ? `<code style="font-size:11px">${escHtml(row['Link'])}</code>` : `<span style="color:var(--text-muted)">—</span>`}
        ${row['id'] ? `<br><a href="/galeri/album/${row['id']}" target="_blank" style="color:var(--info);font-size:11px">Lihat halaman album ↗</a>` : ''}
      </td>
      <td style="font-size:12px;max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${escHtml(row['Keterangan'] || '')}</td>
      ${showAksiCol ? `
      <td>
        <div class="actions">
          ${can('edit') && row['Link'] ? `<button class="btn btn-icon btn-sm" onclick="refreshGaleriCache(${row._id}, this)" title="Refresh cache foto (kalau foto ditambah/dihapus langsung di R2)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><polyline points="1 4 1 10 7 10"/><polyline points="23 20 23 14 17 14"/><path d="M20.49 9A9 9 0 005.64 5.64L1 10m22 4l-4.64 4.36A9 9 0 013.51 15"/></svg>
          </button>` : ''}
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

  initPagination('dataTable', 20);
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
// ── Thumbnail upload toggle ───────────────────────────────────────────
function toggleThumbUpload() {
  const col = document.getElementById('thumbCollapse');
  const btn = document.getElementById('thumbToggleBtn');
  if (!col) return;
  const opening = !col.classList.contains('open');
  col.classList.toggle('open', opening);
  if (btn) btn.classList.toggle('open', opening);
}

function setThumbToggleFilled(filename) {
  const btn   = document.getElementById('thumbToggleBtn');
  const label = document.getElementById('thumbToggleLabel');
  if (!btn || !label) return;
  const name = filename ? filename.split('/').pop() : '';
  label.textContent = name || 'Thumbnail terpilih';
  btn.classList.add('has-thumb');
  btn.classList.remove('open');
  // Tutup panel setelah foto berhasil dipilih
  const col = document.getElementById('thumbCollapse');
  if (col) col.classList.remove('open');
}

function resetThumbToggle() {
  const btn   = document.getElementById('thumbToggleBtn');
  const label = document.getElementById('thumbToggleLabel');
  if (btn)   { btn.classList.remove('has-thumb', 'open'); }
  if (label) { label.textContent = 'Pilih thumbnail…'; }
  const col = document.getElementById('thumbCollapse');
  if (col)   col.classList.remove('open');
}

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
    if (!opts.keepValue) resetThumbToggle();
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
  fd.append('file', file);

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
      setThumbToggleFilled(data.filename);

      const savedNote = data.compressed ? ` · hemat ${data.saved_pct}% dari ${data.orig_kb}KB` : '';
      const dispName = data.filename ? data.filename.split('/').pop() : '';
      badgeT.textContent = `✓ ${dispName} · ${data.size_kb}KB [${data.format}] · ${data.dimensions}${savedNote}`;
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
  resetThumbToggle();
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
         title="${escHtml(f.filename)} · ${f.size_kb}KB">
      <img src="${escHtml(f.url)}" alt="${escHtml(f.filename)}" loading="lazy"
           onerror="this.parentElement.style.opacity='.35'">
      <div class="media-item-name">${escHtml(f.filename.split('/').pop())}</div>
    </div>
  `).join('');
}

// Event delegation: satu listener untuk semua .media-item, aman untuk nama file
// yang mengandung tanda kutip/apostrof (sebelumnya pakai onclick inline + JSON.stringify
// yang bisa merusak atribut HTML dan memicu "Uncaught SyntaxError: Unexpected end of input").
document.getElementById('mediaGrid').addEventListener('click', function (e) {
  const item = e.target.closest('.media-item');
  if (!item) return;
  selectMediaFile(item.dataset.filename);
});

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
  document.getElementById('mediaSelectedText').textContent = filename.split('/').pop();
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

// ── Reset Form ────────────────────────────────────────────────────────
function resetForm() {
  document.getElementById('fieldRow').value        = '';
  document.getElementById('fieldTanggal').value    = '';
  document.getElementById('fieldBulan').value      = '';
  document.getElementById('bulanDisplay').textContent = '—';
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
  hideFolderDropdown();
  // Reset badge hint field R2
  const _hint = document.getElementById('folderSearchHint');
  if (_hint) {
    _hint.textContent = 'ketik untuk cari';
    _hint.style.color = _hint.style.borderColor = _hint.style.background = '';
    _hint.style.display = 'inline-block';
  }
  // Default ke mode upload (tanpa keepValue → reset semua state upload juga)
  switchThumbMode('upload');
}

// ── Open Modals ───────────────────────────────────────────────────────
function openAddModal() {
  if (!can('create')) { toast('Akses Ditolak', 'Anda tidak punya izin tambah data', 'error'); return; }
  editRow = null;
  resetForm();

  // Tampilkan tombol + divider upload album (hanya di mode Tambah)
  const uploadBtn  = document.getElementById('btnToggleUploadAlbum');
  const uploadDiv  = uploadBtn ? uploadBtn.previousElementSibling : null; // divider
  if (uploadBtn) uploadBtn.style.display = '';
  if (uploadDiv) uploadDiv.style.display = '';

  // Pastikan panel upload tertutup & bersih
  const uploadArea = document.getElementById('albumUploadArea');
  if (uploadArea) uploadArea.style.display = 'none';
  const uploadLog  = document.getElementById('albumUploadLog');
  if (uploadLog)  { uploadLog.innerHTML = ''; uploadLog.style.display = 'none'; }
  const uploadRes  = document.getElementById('albumUploadResult');
  if (uploadRes)  uploadRes.style.display = 'none';
  const uploadProg = document.getElementById('albumUploadProgress');
  if (uploadProg) uploadProg.style.display = 'none';
  if (uploadBtn)  { uploadBtn.style.background=''; uploadBtn.style.borderColor=''; uploadBtn.style.color=''; }
  albumUploadRunning = false;
  const folderNameEl = document.getElementById('albumFolderName');
  if (folderNameEl) folderNameEl.value = '';

  document.getElementById('modalTitle').textContent = 'Tambah Album';
  openModal('formModal');
}

function openEditModal(rowNum) {
  if (!can('edit')) { toast('Akses Ditolak', 'Anda tidak punya izin edit data', 'error'); return; }
  const row = allData.find(r => r._id === rowNum);
  if (!row) return;
  editRow = rowNum;
  document.getElementById('modalTitle').textContent = 'Edit Album';
  // Sembunyikan tombol, divider, & area upload di mode Edit (tidak relevan)
  const uploadBtn2  = document.getElementById('btnToggleUploadAlbum');
  const uploadDiv2  = uploadBtn2 ? uploadBtn2.previousElementSibling : null;
  if (uploadBtn2) uploadBtn2.style.display = 'none';
  if (uploadDiv2) uploadDiv2.style.display = 'none';
  const uploadArea2 = document.getElementById('albumUploadArea');
  if (uploadArea2) uploadArea2.style.display = 'none';

  // Reset form dulu (termasuk bersihkan fieldGambar & mode upload)
  resetForm();

  // Isi field-field teks
  document.getElementById('fieldRow').value        = rowNum;
  const tanggalVal = row['Tanggal'] || '';
  document.getElementById('fieldTanggal').value    = tanggalVal;
  // Isi Bulan: pakai data dari DB jika ada, kalau tidak auto-derive dari Tanggal
  const existingBulan = row['Bulan'] || '';
  if (existingBulan) {
    document.getElementById('fieldBulan').value = existingBulan;
    document.getElementById('bulanDisplay').textContent = existingBulan;
  } else {
    autoFillBulan(tanggalVal);
  }
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
      setThumbToggleFilled(normalizedVal);
      // Buka panel agar preview langsung terlihat saat edit
      const _col = document.getElementById('thumbCollapse');
      const _btn = document.getElementById('thumbToggleBtn');
      if (_col) _col.classList.add('open');
      if (_btn) _btn.classList.add('open');
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

// ── Cache Foto R2 (per-baris, dari kolom Aksi) ─────────────────────────
async function refreshGaleriCache(rowNum, btnEl) {
  if (!can('edit')) { toast('Akses Ditolak', 'Anda tidak punya izin edit', 'error'); return; }
  const originalHtml = btnEl.innerHTML;
  btnEl.disabled = true;
  btnEl.innerHTML = `<div class="spinner" style="width:14px;height:14px;margin:0;border-width:2px"></div>`;

  const res = await apiPost('/admin/api/galeri_cache.php', { action: 'refresh', id: rowNum });

  btnEl.disabled = false;
  btnEl.innerHTML = originalHtml;

  if (res.success) {
    toast('Cache Diperbarui', `${res.count} foto ditemukan di folder "${res.folder}"`, 'success');
  } else {
    toast('Error', res.error || 'Gagal refresh cache', 'error');
  }
}

// ── Upload Album Inline ──────────────────────────────────────────────
const R2_ALBUM_EXT_RE = /\.(jpe?g|png|webp|gif|bmp|heic|heif|avif|mp4|mov|avi|mkv|wmv|3gp|3g2|m4v|mpg|mpeg|mts|m2t|m2ts|asf)$/i;
let albumUploadRunning = false;

function toggleAlbumUploadArea() {
  const area = document.getElementById('albumUploadArea');
  const btn  = document.getElementById('btnToggleUploadAlbum');
  const visible = area.style.display !== 'none';
  area.style.display = visible ? 'none' : 'block';
  btn.style.background  = visible ? '' : 'var(--accent-dim)';
  btn.style.borderColor = visible ? '' : 'var(--accent)';
  btn.style.color       = visible ? '' : 'var(--accent)';
  if (!visible) {
    // reset state area upload saat dibuka ulang
    document.getElementById('albumUploadProgress').style.display = 'none';
    document.getElementById('albumUploadLog').style.display = 'none';
    document.getElementById('albumUploadLog').innerHTML = '';
    document.getElementById('albumUploadResult').style.display = 'none';
    document.getElementById('albumDropzone').classList.remove('uploading');
    albumUploadRunning = false;
  }
}

function onAlbumDragOver(e) {
  e.preventDefault();
  document.getElementById('albumDropzone').classList.add('drag-over');
}
function onAlbumDragLeave(e) {
  document.getElementById('albumDropzone').classList.remove('drag-over');
}

async function onAlbumDrop(e) {
  e.preventDefault();
  document.getElementById('albumDropzone').classList.remove('drag-over');
  if (albumUploadRunning) return;

  const items = e.dataTransfer.items;
  if (!items || !items.length) return;

  const entries = [];
  for (let i = 0; i < items.length; i++) {
    const entry = items[i].webkitGetAsEntry && items[i].webkitGetAsEntry();
    if (entry) entries.push(entry);
  }
  if (!entries.length) {
    toast('Info', 'Browser ini tidak mendukung drag folder. Gunakan klik untuk pilih folder.', 'warning');
    return;
  }

  const grouped = {};
  document.getElementById('albumProgressLabel').textContent = 'Membaca isi folder...';
  document.getElementById('albumUploadProgress').style.display = 'block';

  for (const entry of entries) {
    if (entry.isDirectory) {
      const files = await readDirRecursive(entry, '');
      if (files.length) grouped[entry.name] = (grouped[entry.name] || []).concat(files);
    } else {
      toast('Info', 'Seret FOLDER (bukan file satuan). Nama folder = nama album di R2.', 'warning');
    }
  }
  const albumNames = Object.keys(grouped);
  if (!albumNames.length) {
    document.getElementById('albumUploadProgress').style.display = 'none';
    return;
  }
  // Ambil override nama dari input (hanya berlaku kalau 1 folder)
  const nameOverride = document.getElementById('albumFolderName').value.trim();
  for (const albumName of albumNames) {
    const finalName = (albumNames.length === 1 && nameOverride) ? nameOverride : albumName;
    await runAlbumUpload(finalName, grouped[albumName]);
  }
}

async function onAlbumFolderInputChange(e) {
  const fileList = Array.from(e.target.files || []);
  e.target.value = '';
  if (!fileList.length || albumUploadRunning) return;

  const grouped = {};
  for (const f of fileList) {
    const rel = f.webkitRelativePath || f.name;
    const slash = rel.indexOf('/');
    const albumName = slash !== -1 ? rel.substring(0, slash) : 'Album';
    const relpath   = slash !== -1 ? rel.substring(slash + 1) : rel;
    if (!R2_ALBUM_EXT_RE.test(relpath)) continue;
    (grouped[albumName] = grouped[albumName] || []).push({ relpath, file: f });
  }

  const albumNames = Object.keys(grouped);
  if (!albumNames.length) {
    toast('Info', 'Tidak ada file foto/video yang didukung dalam folder ini.', 'warning');
    return;
  }

  document.getElementById('albumUploadProgress').style.display = 'block';
  const nameOverride = document.getElementById('albumFolderName').value.trim();
  for (const albumName of albumNames) {
    const finalName = (albumNames.length === 1 && nameOverride) ? nameOverride : albumName;
    await runAlbumUpload(finalName, grouped[albumName]);
  }
}

// Reuse readDirectoryRecursive dari media.php (copas di sini agar self-contained)
function readDirRecursive(dirEntry, basePath) {
  return new Promise(function(resolve) {
    const reader = dirEntry.createReader();
    let allFiles = [];
    function readBatch() {
      reader.readEntries(async function(entries) {
        if (!entries.length) { resolve(allFiles); return; }
        for (const ent of entries) {
          const relPath = basePath ? basePath + '/' + ent.name : ent.name;
          if (ent.isFile) {
            try {
              const file = await new Promise(function(res, rej) { ent.file(res, rej); });
              allFiles.push({ relpath: relPath, file });
            } catch(e) { /* skip */ }
          } else if (ent.isDirectory) {
            const sub = await readDirRecursive(ent, relPath);
            allFiles = allFiles.concat(sub);
          }
        }
        readBatch();
      }, function() { resolve(allFiles); });
    }
    readBatch();
  });
}

function uploadOneAlbumFile(fileEntry, folderName, onProgress) {
  return new Promise(function (resolve) {
    const xhr = new XMLHttpRequest();
    xhr.open('POST', '/admin/api/r2_folder_upload.php', true);

    let lastLoaded = 0;
    let lastTime = Date.now();
    let speedStr = '';

    if (xhr.upload && onProgress) {
      xhr.upload.onprogress = function (e) {
        if (e.lengthComputable && e.total > 0) {
          const now = Date.now();
          const dt = (now - lastTime) / 1000;
          if (dt >= 0.4) {
            const bytesPerSec = (e.loaded - lastLoaded) / dt;
            speedStr = (bytesPerSec / (1024 * 1024)).toFixed(1) + ' MB/s';
            lastLoaded = e.loaded;
            lastTime = now;
          }
          onProgress(e.loaded, e.total, speedStr);
        }
      };
    }

    xhr.onload = function () {
      if (xhr.status >= 200 && xhr.status < 300) {
        try {
          const data = JSON.parse(xhr.responseText);
          resolve(data);
        } catch (err) {
          resolve({ success: false, error: 'Format respons server tidak valid' });
        }
      } else {
        let errMsg = 'HTTP Error ' + xhr.status;
        try {
          const errData = JSON.parse(xhr.responseText);
          if (errData && errData.error) errMsg = errData.error;
        } catch (_) {}
        resolve({ success: false, error: errMsg });
      }
    };

    xhr.onerror = function () {
      resolve({ success: false, error: 'Koneksi jaringan terputus' });
    };

    xhr.ontimeout = function () {
      resolve({ success: false, error: 'Upload timeout (melebihi batas)' });
    };

    xhr.timeout = 600000; // 10 menit

    const fd = new FormData();
    fd.append('file', fileEntry.file);
    fd.append('folder', folderName);
    fd.append('relpath', fileEntry.relpath);
    fd.append('skip_existing', '0');

    xhr.send(fd);
  });
}

async function runAlbumUpload(folderName, fileEntries) {
  albumUploadRunning = true;
  const dz  = document.getElementById('albumDropzone');
  const log = document.getElementById('albumUploadLog');
  const res = document.getElementById('albumUploadResult');

  dz.classList.add('uploading');
  log.style.display = 'block';
  log.innerHTML = '';
  res.style.display = 'none';

  const filtered = fileEntries.filter(fe => R2_ALBUM_EXT_RE.test(fe.relpath));
  if (!filtered.length) {
    toast('Info', `Folder "${folderName}" tidak berisi file yang didukung.`, 'warning');
    albumUploadRunning = false;
    dz.classList.remove('uploading');
    return;
  }

  const total = filtered.length;
  let done = 0, ok = 0, fail = 0, skipped = 0, savedKb = 0;

  document.getElementById('albumProgressLabel').textContent = `Mengupload "${folderName}"...`;
  document.getElementById('albumUploadProgress').style.display = 'block';
  document.getElementById('albumProgressCount').textContent = `0 / ${total}`;
  document.getElementById('albumProgressBar').style.width = '0%';

  function addLog(msg, cls = '') {
    const line = document.createElement('div');
    line.className = 'album-upload-log-line' + (cls ? ' ' + cls : '');
    line.textContent = msg;
    log.appendChild(line);
    log.scrollTop = log.scrollHeight;
  }

  let queuedVideos = 0;

  const isVideoOrLarge = function (fe) {
    const isVid = /\.(mp4|mov|avi|mkv|wmv|3gp|m4v)$/i.test(fe.relpath);
    return isVid || (fe.file && fe.file.size > 6 * 1024 * 1024);
  };

  const largeQueue = filtered.filter(isVideoOrLarge);
  const smallQueue = filtered.filter(fe => !isVideoOrLarge(fe));

  async function processEntry(fe) {
    const fName = fe.relpath;
    const fSize = fe.file ? fe.file.size : 0;
    const fSizeMb = (fSize / (1024 * 1024)).toFixed(1);

    const logLine = document.createElement('div');
    logLine.className = 'album-upload-log-line';
    logLine.textContent = `⏳ Uploading: ${fName} (0 / ${fSizeMb} MB)...`;
    log.appendChild(logLine);
    log.scrollTop = log.scrollHeight;

    const onProgress = function (loaded, fTotal, speed) {
      const lMb = (loaded / (1024 * 1024)).toFixed(1);
      const tMb = (fTotal / (1024 * 1024)).toFixed(1);
      const pct = fTotal > 0 ? Math.round((loaded / fTotal) * 100) : 0;
      const spd = speed ? ` • ${speed}` : '';
      logLine.textContent = `⏳ Uploading: ${fName} (${lMb}/${tMb} MB · ${pct}%${spd})`;
    };

    let d = await uploadOneAlbumFile(fe, folderName, onProgress);
    if (!d.success && d.error && (d.error.includes('jaringan') || d.error.includes('timeout'))) {
      logLine.textContent = `↻ Retry: ${fName}...`;
      d = await uploadOneAlbumFile(fe, folderName, onProgress);
    }

    if (d.success) {
      if (d.skipped) {
        skipped++;
        logLine.className = 'album-upload-log-line skip';
        logLine.textContent = `⊘ Dilewati: ${fName}`;
      } else if (d.queued) {
        queuedVideos++;
        logLine.className = 'album-upload-log-line ok';
        logLine.textContent = `⏳ ${fName}: diantre untuk batch kompresi GitHub`;
      } else {
        ok++;
        savedKb += Math.max(0, (d.orig_kb || 0) - (d.new_kb || 0));
        const note   = d.saved_pct > 0 ? ` (hemat ${d.saved_pct}%)` : '';
        const poster = d.is_video ? (d.poster ? ' · thumbnail ✓' : ' · thumbnail gagal dibuat') : '';
        const warn   = (!d.compressed && d.note) ? ` — ${d.note}` : '';
        logLine.className = 'album-upload-log-line ok';
        logLine.textContent = `✓ ${fName} · ${d.new_kb}KB${note}${poster}${warn}`;
      }
    } else {
      fail++;
      logLine.className = 'album-upload-log-line err';
      logLine.textContent = `✗ ${fName}: ${d.error || 'gagal'}`;
    }
    done++;
    document.getElementById('albumProgressCount').textContent = `${done} / ${total}`;
    document.getElementById('albumProgressBar').style.width = Math.round((done / total) * 100) + '%';
  }

  // 1) Video & file besar satu per satu
  for (const fe of largeQueue) {
    await processEntry(fe);
  }

  // 2) Foto kecil concurrency 2
  let sIdx = 0;
  async function smallWorker() {
    while (sIdx < smallQueue.length) {
      const fe = smallQueue[sIdx++];
      await processEntry(fe);
    }
  }
  if (smallQueue.length > 0) {
    const concurrency = 2;
    await Promise.all(Array.from({ length: Math.min(concurrency, smallQueue.length) }, smallWorker));
  }

  // Semua file (foto + video) sudah selesai diupload/diantre. Kalau ada
  // video yang diantre, kirim SATU batch dispatch ke GitHub Actions supaya
  // semua video folder ini diproses dalam 1 run (bukan 1 run per video).
  if (queuedVideos > 0) {
    addLog(`⏳ Mengirim ${queuedVideos} video sebagai 1 batch job ke GitHub Actions...`, '');
    try {
      const rb = await fetch('/admin/api/r2_video_batch_dispatch.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'folder=' + encodeURIComponent(folderName)
      });
      const db = await rb.json();
      if (db.success && db.dispatched) {
        addLog(`✓ ${db.count} video dikirim sebagai 1 job GitHub Actions (bukan ${db.count} run terpisah).`, 'ok');
      } else if (db.success) {
        addLog(`⚠ Batch video: ${db.note || 'gagal dispatch, fallback dipakai.'}`, 'err');
      } else {
        addLog(`✗ Gagal mengirim batch video: ${db.error}`, 'err');
      }
    } catch (err) {
      addLog('✗ Gagal menghubungi server untuk batch dispatch video.', 'err');
    }
  }

  // Selesai — update cache folder names supaya autocomplete langsung nemu folder baru
  folderNamesLoaded = false;
  loadFolderNames();

  // Otomatis isi fieldLink dengan nama folder yang baru diupload
  selectFolder(folderName); // isi field + tampilkan badge ✓ terpilih

  // Tampilkan ringkasan
  res.style.display = 'block';
  res.innerHTML = `<strong style="color:var(--text-primary)">✅ Upload "${escHtml(folderName)}" selesai</strong>
    <div style="display:flex;gap:16px;margin-top:6px;font-size:12px">
      <span style="color:var(--success)">✓ ${ok} berhasil</span>
      <span style="color:var(--text-muted)">⊘ ${skipped} dilewati</span>
      <span style="color:var(--danger)">✗ ${fail} gagal</span>
      <span style="color:var(--accent)">↓ ${savedKb.toFixed(0)} KB hemat</span>
    </div>`;

  toast(fail ? 'Selesai (ada yang gagal)' : 'Upload Selesai',
    `"${folderName}": ${ok} berhasil, ${skipped} dilewati, ${fail} gagal`,
    fail ? 'error' : 'success');

  dz.classList.remove('uploading');
  albumUploadRunning = false;
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