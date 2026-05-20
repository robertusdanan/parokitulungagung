<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
adminBoot();
$user = requirePageAccess('agenda');
adminHeader('Agenda / Info Paroki', 'agenda', $user);
?>

<!-- ═══════════════════════════════════════════════════════════════════
     PAGE HEADER
═══════════════════════════════════════════════════════════════════ -->
<div class="page-header">
  <div class="page-header-left">
    <h1>Agenda &amp; Info Paroki</h1>
    <p>Kelola agenda, pengumuman, dokumen unduhan, dan jadwal petugas paroki</p>
  </div>
  <div id="headerActionWrap">
    <?php if (userCan($user, 'create')): ?>
    <button class="btn btn-primary" id="btnTambahUtama" onclick="openAddAgendaModal()">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      Tambah Agenda
    </button>
    <?php endif; ?>
  </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════
     MASTER TAB — Info / Download / Jadwal Petugas
═══════════════════════════════════════════════════════════════════ -->
<div class="admin-master-tabs" id="adminMasterTabs">
  <button class="amt-tab amt-tab--active" onclick="switchMasterTab('agenda',this)" id="tabBtnAgenda">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="15" height="15">
      <rect x="3" y="4" width="18" height="18" rx="2"/><line x1="3" y1="10" x2="21" y2="10"/>
      <line x1="8" y1="2" x2="8" y2="6"/><line x1="16" y1="2" x2="16" y2="6"/>
    </svg>
    Info / Agenda
    <span class="amt-badge" id="badgeAgenda">0</span>
  </button>
  <button class="amt-tab" onclick="switchMasterTab('dokumen',this)" id="tabBtnDokumen">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="15" height="15">
      <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/>
      <polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>
    </svg>
    Dokumen Download
    <span class="amt-badge amt-badge--gold" id="badgeDokumen">0</span>
  </button>
  <button class="amt-tab" onclick="switchMasterTab('petugas',this)" id="tabBtnPetugas">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="15" height="15">
      <rect x="3" y="4" width="18" height="18" rx="2"/>
      <line x1="3" y1="10" x2="21" y2="10"/>
      <circle cx="9" cy="16" r="2"/><circle cx="15" cy="16" r="2"/>
    </svg>
    Jadwal Petugas
    <span class="amt-badge amt-badge--teal" id="badgePetugas">0</span>
  </button>
</div>

<style>
/* ── Master Tab ── */
.admin-master-tabs {
  display: flex; gap: 0;
  margin-bottom: 0;
  border-bottom: 2px solid var(--border, #e8e0d4);
  flex-wrap: wrap;
}
.amt-tab {
  display: inline-flex; align-items: center; gap: 7px;
  padding: 10px 20px;
  background: none; border: none;
  font-family: 'DM Sans', sans-serif;
  font-size: 13.5px; font-weight: 500;
  color: var(--text-secondary, #888);
  cursor: pointer; border-bottom: 2px solid transparent;
  margin-bottom: -2px;
  transition: color .18s, border-color .18s;
}
.amt-tab:hover { color: var(--accent, #b8860b); }
.amt-tab--active {
  color: var(--accent, #b8860b);
  border-bottom-color: var(--accent, #b8860b);
  font-weight: 700;
}
.amt-badge {
  min-width: 20px; height: 20px; padding: 0 6px;
  background: var(--bg-secondary,#f0ebe3);
  color: var(--text-secondary,#888);
  font-size: 10px; font-weight: 700;
  border-radius: 20px;
  display: inline-flex; align-items: center; justify-content: center;
}
.amt-badge--gold {
  background: rgba(184,134,11,0.13);
  color: #b8860b;
}
.amt-badge--teal {
  background: rgba(0,128,128,0.1);
  color: #007070;
}

/* ── Upload progress bar ── */
.upload-progress-wrap {
  display: none; margin-top: 10px;
  background: var(--bg-secondary,#f5f0e8);
  border-radius: 8px; overflow: hidden; height: 8px;
}
.upload-progress-bar {
  height: 100%; width: 0%;
  background: linear-gradient(90deg,#b8860b,#d4a017);
  border-radius: 8px;
  transition: width .25s;
}

/* ── Dokumen table extras ── */
.dt-icon-cell { display: flex; align-items: center; gap: 9px; }
.dt-file-badge {
  display: inline-flex; align-items: center; justify-content: center;
  min-width: 38px; height: 22px; padding: 0 7px;
  border-radius: 5px; font-size: 9.5px; font-weight: 800;
  letter-spacing: .04em; text-transform: uppercase; color: #fff;
  flex-shrink: 0;
}

/* ── Jadwal Petugas Panel ── */
.petugas-admin-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 14px;
  padding: 4px 0 10px;
}
.petugas-admin-card {
  background: var(--bg-card, #fff);
  border: 1px solid var(--border, #e8e0d4);
  border-radius: 12px;
  overflow: hidden;
  transition: box-shadow .18s;
}
.petugas-admin-card:hover {
  box-shadow: 0 4px 16px rgba(184,134,11,0.13);
}
.petugas-admin-img {
  width: 100%;
  aspect-ratio: 3/4;
  object-fit: cover;
  display: block;
  background: #f5f0e8;
}
.petugas-admin-body {
  padding: 10px 12px;
  border-top: 1px solid var(--border, #e8e0d4);
}
.petugas-admin-title {
  font-size: 13px;
  font-weight: 600;
  color: var(--text, #2a1f0a);
  margin-bottom: 8px;
  line-height: 1.35;
}
.petugas-admin-meta {
  font-size: 11px;
  color: var(--text-secondary, #888);
  margin-bottom: 8px;
}
.petugas-admin-actions {
  display: flex;
  gap: 6px;
}

/* ── Drop zone ── */
.pt-drop-zone {
  border: 2px dashed rgba(0,128,128,0.3);
  border-radius: 10px;
  padding: 22px;
  text-align: center;
  cursor: pointer;
  background: rgba(0,128,128,0.03);
  transition: border-color .18s, background .18s;
}
.pt-drop-zone:hover,
.pt-drop-zone.drag-over {
  border-color: #007070;
  background: rgba(0,128,128,0.07);
}
</style>

<!-- ═══════════════════════════════════════════════════════════════════
     PANEL: AGENDA
═══════════════════════════════════════════════════════════════════ -->
<div id="panel-agenda">
<div class="card" style="border-radius:0 12px 12px 12px;">
  <div class="toolbar">
    <div class="toolbar-left">
      <div class="search-wrap">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" class="form-control" id="searchInput" placeholder="Cari judul agenda...">
      </div>
      <select class="form-select" id="filterHariLibur" style="max-width:160px" onchange="applyFilter()">
        <option value="">Semua</option>
        <option value="ya">Hari Libur</option>
        <option value="tidak">Bukan Libur</option>
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
            <th>Tanggal</th><th>Bulan</th><th>Judul</th>
            <th>Keterangan</th><th>Ikon</th><th>Hari Libur</th>
            <th id="thAksi" style="width:90px">Aksi</th>
          </tr>
        </thead>
        <tbody id="tableBody"></tbody>
      </table>
    </div>
  </div>
  <div id="emptyState" class="empty-state" style="display:none">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="4" width="18" height="18" rx="2"/></svg>
    <p>Belum ada agenda.</p>
  </div>
</div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════
     PANEL: DOKUMEN DOWNLOAD
═══════════════════════════════════════════════════════════════════ -->
<div id="panel-dokumen" style="display:none">
<div class="card" style="border-radius:0 12px 12px 12px;">
  <div class="toolbar">
    <div class="toolbar-left">
      <div class="search-wrap">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" class="form-control" id="searchDokumen" placeholder="Cari judul dokumen...">
      </div>
      <select class="form-select" id="filterKategori" style="max-width:160px" onchange="applyDokumenFilter()">
        <option value="">Semua Kategori</option>
      </select>
    </div>
    <div class="toolbar-right">
      <span id="dokumenCount" style="font-size:13px;color:var(--text-secondary)">Memuat...</span>
      <?php if (userCan($user, 'create')): ?>
      <button class="btn btn-primary btn-sm" onclick="openAddDokumenModal()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Upload Dokumen
      </button>
      <?php endif; ?>
    </div>
  </div>

  <!-- Panduan tabel Supabase -->
  <div id="dokumenSqlHint" style="margin:0 20px 14px;padding:12px 16px;background:rgba(184,134,11,0.07);border-left:3px solid #b8860b;border-radius:0 8px 8px 0;font-size:12px;line-height:1.7;display:none">
    <strong>Tabel Supabase belum ada?</strong> Jalankan SQL berikut di Supabase SQL Editor:<br>
    <code style="font-size:11px;background:#fff;padding:8px 10px;border-radius:6px;display:block;margin-top:6px;white-space:pre-wrap;border:1px solid rgba(184,134,11,0.2)">CREATE TABLE dokumen_paroki (
  id          bigserial PRIMARY KEY,
  judul       text NOT NULL,
  deskripsi   text,
  nama_file   text NOT NULL,
  ukuran      text,
  kategori    text DEFAULT 'Umum',
  urutan      int DEFAULT 0,
  aktif       boolean DEFAULT true,
  created_at  timestamptz DEFAULT now()
);</code>
  </div>

  <div id="dokumenLoadingState" style="text-align:center;padding:40px"><div class="spinner"></div></div>
  <div id="dokumenTableContainer" style="display:none">
    <div class="table-wrapper">
      <table class="data-table" id="dokumenTable">
        <thead>
          <tr>
            <th style="width:44px">#</th>
            <th>File &amp; Judul</th>
            <th>Kategori</th>
            <th>Deskripsi</th>
            <th>Ukuran</th>
            <th>Status</th>
            <th style="width:90px">Aksi</th>
          </tr>
        </thead>
        <tbody id="dokumenTableBody"></tbody>
      </table>
    </div>
  </div>
  <div id="dokumenEmptyState" class="empty-state" style="display:none">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="40" height="40">
      <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/>
      <polyline points="7 10 12 15 17 10"/>
      <line x1="12" y1="15" x2="12" y2="3"/>
    </svg>
    <p>Belum ada dokumen.</p>
    <?php if (userCan($user, 'create')): ?>
    <button class="btn btn-primary btn-sm" onclick="openAddDokumenModal()">Upload Dokumen Pertama</button>
    <?php endif; ?>
  </div>
</div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════
     PANEL: JADWAL PETUGAS
═══════════════════════════════════════════════════════════════════ -->
<div id="panel-petugas" style="display:none">
<div class="card" style="border-radius:0 12px 12px 12px;">
  <div class="toolbar">
    <div class="toolbar-left">
      <div class="search-wrap">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" class="form-control" id="searchPetugas" placeholder="Cari judul jadwal...">
      </div>
    </div>
    <div class="toolbar-right">
      <span id="petugasCount" style="font-size:13px;color:var(--text-secondary)">Memuat...</span>
      <?php if (userCan($user, 'create')): ?>
      <button class="btn btn-primary btn-sm" onclick="openAddPetugasModal()" style="background:linear-gradient(135deg,#007070,#009090)">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Upload Jadwal
      </button>
      <?php endif; ?>
    </div>
  </div>

  <!-- Hint SQL -->
  <div id="petugasSqlHint" style="margin:0 20px 14px;padding:12px 16px;background:rgba(0,128,128,0.07);border-left:3px solid #007070;border-radius:0 8px 8px 0;font-size:12px;line-height:1.7;display:none">
    <strong>Tabel Supabase belum ada?</strong> Jalankan SQL berikut di Supabase SQL Editor:<br>
    <code style="font-size:11px;background:#fff;padding:8px 10px;border-radius:6px;display:block;margin-top:6px;white-space:pre-wrap;border:1px solid rgba(0,128,128,0.2)">CREATE TABLE jadwal_petugas_gambar (
  id          bigserial PRIMARY KEY,
  judul       text NOT NULL,
  nama_file   text NOT NULL,
  urutan      int DEFAULT 0,
  aktif       boolean DEFAULT true,
  created_at  timestamptz DEFAULT now()
);</code>
  </div>

  <div id="petugasLoadingState" style="text-align:center;padding:40px"><div class="spinner"></div></div>

  <div id="petugasGridContainer" style="display:none;padding:16px 20px 8px">
    <div class="petugas-admin-grid" id="petugasAdminGrid"></div>
  </div>

  <div id="petugasEmptyState" class="empty-state" style="display:none">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="40" height="40">
      <rect x="3" y="4" width="18" height="18" rx="2"/>
      <line x1="3" y1="10" x2="21" y2="10"/>
      <circle cx="9" cy="16" r="2"/><circle cx="15" cy="16" r="2"/>
    </svg>
    <p>Belum ada jadwal petugas.</p>
    <?php if (userCan($user, 'create')): ?>
    <button class="btn btn-primary btn-sm" onclick="openAddPetugasModal()" style="background:linear-gradient(135deg,#007070,#009090)">Upload Jadwal Pertama</button>
    <?php endif; ?>
  </div>
</div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════
     MODAL: TAMBAH / EDIT AGENDA
═══════════════════════════════════════════════════════════════════ -->
<?php if (userCan($user, 'create') || userCan($user, 'edit')): ?>
<div class="modal-overlay" id="formModal">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title" id="modalTitle">Tambah Agenda</span>
      <button class="modal-close" onclick="closeModal('formModal')">&times;</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="fieldRow">
      <div class="form-grid">
        <div class="form-group">
          <label>Tanggal (angka) <span class="required">*</span></label>
          <input type="number" class="form-control" id="fieldTanggal" placeholder="17" min="1" max="31">
        </div>
        <div class="form-group">
          <label>Bulan (3 Huruf) <span class="required">*</span></label>
          <input type="text" class="form-control" id="fieldBulan" placeholder="Feb">
        </div>
        <div class="form-group full">
          <label>Judul <span class="required">*</span></label>
          <input type="text" class="form-control" id="fieldJudul" placeholder="Misa Rabu Abu">
        </div>
        <div class="form-group full">
          <label>Keterangan</label>
          <textarea class="form-control" id="fieldKeterangan" rows="3"></textarea>
        </div>
        <div class="form-group">
          <label>URL Ikon</label>
          <input type="url" class="form-control" id="fieldIcon" placeholder="https://...">
        </div>
        <div class="form-group">
          <label>Hari Libur?</label>
          <select class="form-select" id="fieldHariLibur">
            <option value="">Tidak</option>
            <option value="Ya">Ya</option>
          </select>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('formModal')">Batal</button>
      <button class="btn btn-primary" id="btnSave" onclick="submitAgendaForm()">Simpan</button>
    </div>
  </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════
     MODAL: TAMBAH / EDIT DOKUMEN
═══════════════════════════════════════════════════════════════════ -->
<div class="modal-overlay" id="dokumenModal">
  <div class="modal" style="max-width:540px">
    <div class="modal-header">
      <span class="modal-title" id="dokumenModalTitle">Upload Dokumen</span>
      <button class="modal-close" onclick="closeModal('dokumenModal')">&times;</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="dkFieldId">
      <input type="hidden" id="dkFieldNamaFileLama">
      <div class="form-grid">
        <div class="form-group full">
          <label>Judul Dokumen <span class="required">*</span></label>
          <input type="text" class="form-control" id="dkFieldJudul"
                 placeholder="mis. Formulir Baptis Dewasa">
        </div>
        <div class="form-group">
          <label>Kategori</label>
          <input type="text" class="form-control" id="dkFieldKategori"
                 placeholder="Formulir / Warta / Liturgi..." list="kategoriList">
          <datalist id="kategoriList">
            <option value="Formulir">
            <option value="Warta Paroki">
            <option value="Liturgi">
            <option value="Pengumuman">
            <option value="Umum">
          </datalist>
        </div>
        <div class="form-group">
          <label>Ukuran File</label>
          <input type="text" class="form-control" id="dkFieldUkuran"
                 placeholder="mis. 1.2 MB">
          <small style="color:var(--text-secondary);font-size:11px">
            Isi manual atau otomatis setelah upload
          </small>
        </div>
        <div class="form-group full">
          <label>Deskripsi singkat</label>
          <textarea class="form-control" id="dkFieldDeskripsi" rows="2"
                    placeholder="Penjelasan singkat tentang dokumen ini"></textarea>
        </div>
        <div class="form-group">
          <label>Urutan tampil</label>
          <input type="number" class="form-control" id="dkFieldUrutan" value="0" min="0">
        </div>
        <div class="form-group">
          <label>Status</label>
          <select class="form-select" id="dkFieldAktif">
            <option value="true">Aktif (tampil di website)</option>
            <option value="false">Nonaktif (disembunyikan)</option>
          </select>
        </div>
        <div class="form-group full">
          <label>File <span class="required" id="dkFileRequired">*</span>
            <span id="dkCurrentFile" style="font-size:11px;color:var(--text-secondary);font-weight:400"></span>
          </label>
          <div id="dkDropZone" style="
              border: 2px dashed rgba(184,134,11,0.35);
              border-radius: 10px;
              padding: 24px;
              text-align: center;
              cursor: pointer;
              background: rgba(184,134,11,0.03);
              transition: border-color .18s, background .18s;
          " ondragover="dkDragOver(event)" ondragleave="dkDragLeave(event)" ondrop="dkDrop(event)" onclick="document.getElementById('dkFileInput').click()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                 width="36" height="36" style="color:#b8860b;margin-bottom:8px">
              <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/>
              <polyline points="17 8 12 3 7 8"/>
              <line x1="12" y1="3" x2="12" y2="15"/>
            </svg>
            <div style="font-size:13px;font-weight:600;color:#6a4e1a">Drag &amp; drop file di sini</div>
            <div style="font-size:11.5px;color:#9a7040;margin-top:4px">atau klik untuk pilih file</div>
            <div id="dkSelectedFileName" style="margin-top:10px;font-size:12px;color:#b8860b;font-weight:600;display:none"></div>
          </div>
          <input type="file" id="dkFileInput" style="display:none"
                 onchange="dkFileChosen(this)">
          <div class="upload-progress-wrap" id="dkProgressWrap">
            <div class="upload-progress-bar" id="dkProgressBar"></div>
          </div>
          <div id="dkProgressText" style="font-size:11px;color:var(--text-secondary);margin-top:4px;display:none"></div>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('dokumenModal')">Batal</button>
      <button class="btn btn-primary" id="btnDkSave" onclick="submitDokumenForm()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        Upload &amp; Simpan
      </button>
    </div>
  </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════
     MODAL: TAMBAH / EDIT JADWAL PETUGAS
═══════════════════════════════════════════════════════════════════ -->
<div class="modal-overlay" id="petugasModal">
  <div class="modal" style="max-width:520px">
    <div class="modal-header">
      <span class="modal-title" id="petugasModalTitle">Upload Jadwal Petugas</span>
      <button class="modal-close" onclick="closeModal('petugasModal')">&times;</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="ptFieldId">
      <input type="hidden" id="ptFieldNamaFileLama">
      <div class="form-grid">

        <!-- Judul -->
        <div class="form-group full">
          <label>Judul Jadwal <span class="required">*</span></label>
          <input type="text" class="form-control" id="ptFieldJudul"
                 placeholder="mis. Jadwal Petugas Mei 2025">
        </div>

        <!-- Urutan & Status -->
        <div class="form-group">
          <label>Urutan tampil</label>
          <input type="number" class="form-control" id="ptFieldUrutan" value="0" min="0">
        </div>
        <div class="form-group">
          <label>Status</label>
          <select class="form-select" id="ptFieldAktif">
            <option value="true">Aktif (tampil di website)</option>
            <option value="false">Nonaktif (disembunyikan)</option>
          </select>
        </div>

        <!-- Upload Gambar -->
        <div class="form-group full">
          <label>
            Gambar Jadwal
            <span class="required" id="ptFileRequired">*</span>
            <span id="ptCurrentFile" style="font-size:11px;color:var(--text-secondary);font-weight:400"></span>
          </label>
          <div class="pt-drop-zone" id="ptDropZone"
               ondragover="ptDragOver(event)" ondragleave="ptDragLeave(event)" ondrop="ptDrop(event)"
               onclick="document.getElementById('ptFileInput').click()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                 width="38" height="38" style="color:#007070;margin-bottom:10px">
              <rect x="3" y="3" width="18" height="18" rx="2"/>
              <circle cx="8.5" cy="8.5" r="1.5"/>
              <polyline points="21 15 16 10 5 21"/>
            </svg>
            <div style="font-size:13px;font-weight:600;color:#004a4a">Drag &amp; drop gambar di sini</div>
            <div style="font-size:11.5px;color:#006060;margin-top:4px">JPG, PNG, WEBP — atau klik untuk pilih</div>
            <div id="ptSelectedFileName" style="margin-top:10px;font-size:12px;color:#007070;font-weight:600;display:none"></div>
          </div>
          <!-- Preview gambar baru yang dipilih -->
          <div id="ptImgPreviewWrap" style="display:none;margin-top:10px;text-align:center">
            <img id="ptImgPreview" src="" alt="Preview"
                 style="max-height:200px;max-width:100%;border-radius:8px;border:1px solid rgba(0,128,128,0.2);object-fit:contain;background:#f5f0e8">
          </div>
          <input type="file" id="ptFileInput" style="display:none"
                 accept="image/*" onchange="ptFileChosen(this)">
          <!-- Progress upload -->
          <div class="upload-progress-wrap" id="ptProgressWrap" style="background:rgba(0,128,128,0.1)">
            <div class="upload-progress-bar" id="ptProgressBar" style="background:linear-gradient(90deg,#007070,#009090)"></div>
          </div>
          <div id="ptProgressText" style="font-size:11px;color:var(--text-secondary);margin-top:4px;display:none"></div>
        </div>

      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('petugasModal')">Batal</button>
      <button class="btn btn-primary" id="btnPtSave" onclick="submitPetugasForm()"
              style="background:linear-gradient(135deg,#007070,#009090)">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        Upload &amp; Simpan
      </button>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- ═══════════════════════════════════════════════════════════════════
     JAVASCRIPT
═══════════════════════════════════════════════════════════════════ -->
<script>
// ── Helpers ────────────────────────────────────────────────────────
const PAGE    = 'agenda';
const DK_PAGE = 'dokumen_paroki';
const PT_PAGE = 'jadwal_petugas_gambar';
let allData = [], editAgendaRow = null;
let allDokumen = [], editDokumenId = null, dkChosenFile = null;
let allPetugas = [], editPetugasId = null, ptChosenFile = null;

/* ────────────────────────────────────────────────────────────────
   MASTER TAB SWITCH
──────────────────────────────────────────────────────────────── */
function switchMasterTab(name, btn) {
    document.querySelectorAll('.amt-tab').forEach(b => b.classList.remove('amt-tab--active'));
    btn.classList.add('amt-tab--active');
    document.getElementById('panel-agenda').style.display   = name === 'agenda'   ? '' : 'none';
    document.getElementById('panel-dokumen').style.display  = name === 'dokumen'  ? '' : 'none';
    document.getElementById('panel-petugas').style.display  = name === 'petugas'  ? '' : 'none';

    const haw = document.getElementById('headerActionWrap');
    const canCreate = <?= userCan($user, 'create') ? 'true' : 'false' ?>;
    if (name === 'agenda') {
        haw.innerHTML = canCreate
            ? '<button class="btn btn-primary" onclick="openAddAgendaModal()"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg> Tambah Agenda</button>'
            : '';
    } else if (name === 'dokumen') {
        haw.innerHTML = canCreate
            ? '<button class="btn btn-primary" onclick="openAddDokumenModal()"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg> Upload Dokumen</button>'
            : '';
    } else if (name === 'petugas') {
        haw.innerHTML = canCreate
            ? '<button class="btn btn-primary" onclick="openAddPetugasModal()" style="background:linear-gradient(135deg,#007070,#009090)"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg> Upload Jadwal</button>'
            : '';
        if (!allPetugas.length) loadPetugas();
    }
    if (name === 'dokumen' && !allDokumen.length) loadDokumen();
}

/* ════════════════════════════════════════════════════════════════
   AGENDA SECTION
════════════════════════════════════════════════════════════════ */
document.addEventListener('DOMContentLoaded', function() {
    if (!can('edit') && !can('delete')) {
        var th = document.getElementById('thAksi');
        if (th) th.style.display = 'none';
    }
    loadData();
});

async function loadData() {
  document.getElementById('loadingState').style.display = 'block';
  document.getElementById('tableContainer').style.display = 'none';
  document.getElementById('emptyState').style.display = 'none';
  try {
    const res = await apiPost('/admin/api/sheets.php', { action: 'list', page: PAGE });
    if (!res.success) throw new Error(res.error);
    allData = res.data || [];
    document.getElementById('badgeAgenda').textContent = allData.length;
    renderTable(allData);
  } catch (err) {
    console.error(err);
    toast('Error', 'Gagal load data', 'error');
  } finally {
    document.getElementById('loadingState').style.display = 'none';
  }
}

function renderTable(data) {
    const tbody = document.getElementById('tableBody');
    document.getElementById('rowCount').textContent = data.length + ' agenda';
    if (!data.length) {
        document.getElementById('emptyState').style.display = 'block';
        document.getElementById('tableContainer').style.display = 'none';
        return;
    }
    document.getElementById('tableContainer').style.display = 'block';
    document.getElementById('emptyState').style.display = 'none';
    const showAksi = can('edit') || can('delete');
    tbody.innerHTML = data.map(row => {
        const isLibur = (row['hari_libur']||'').toLowerCase() === 'ya';
        return `<tr ${isLibur ? 'style="background:rgba(224,82,82,0.04)"' : ''}>
          <td style="text-align:center;font-family:'DM Mono',monospace;font-weight:600;font-size:16px;color:${isLibur?'var(--danger)':'var(--accent)'}">${escHtml(row['tanggal']||'')}</td>
          <td>${escHtml(row['bulan']||'')}</td>
          <td style="font-weight:500">${escHtml(row['judul']||'')}</td>
          <td style="font-size:12.5px;color:var(--text-secondary);max-width:200px">${escHtml(row['keterangan']||'')}</td>
          <td>${row['icon']?`<img src="${escHtml(row['icon'])}" style="width:24px;height:24px;object-fit:contain">`:''}</td>
          <td>${isLibur?'<span class="badge badge-red">Libur</span>':''}</td>
          ${showAksi?`<td><div class="actions">
            ${can('edit')?`<button class="btn btn-icon btn-sm" onclick="openEditAgendaModal(${row._id})">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            </button>`:''}
            ${can('delete')?`<button class="btn btn-icon btn-sm" onclick="deleteAgendaRow(${row._id})" style="color:var(--danger);border-color:rgba(224,82,82,0.3)">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a1 1 0 011-1h4a1 1 0 011 1v2"/></svg>
            </button>`:''}
          </div></td>`:'<td style="display:none"></td>'}
        </tr>`;
    }).join('');
}

function applyFilter() {
    const q  = document.getElementById('searchInput').value.toLowerCase();
    const hl = document.getElementById('filterHariLibur').value;
    renderTable(allData.filter(r => {
        const matchQ  = !q  || (r['judul']||'').toLowerCase().includes(q);
        const isLibur = (r['hari_libur']||'').toLowerCase() === 'ya';
        const matchHL = !hl || (hl==='ya'&&isLibur) || (hl==='tidak'&&!isLibur);
        return matchQ && matchHL;
    }));
}
document.getElementById('searchInput').addEventListener('input', applyFilter);

function openAddAgendaModal() {
    if (!can('create')) { toast('Akses Ditolak','Tidak ada izin tambah','error'); return; }
    editAgendaRow = null;
    document.getElementById('modalTitle').textContent = 'Tambah Agenda';
    ['fieldTanggal','fieldBulan','fieldJudul','fieldKeterangan','fieldIcon'].forEach(id => document.getElementById(id).value='');
    document.getElementById('fieldHariLibur').value = '';
    openModal('formModal');
}

function openEditAgendaModal(rowNum) {
    if (!can('edit')) { toast('Akses Ditolak','Tidak ada izin edit','error'); return; }
    const row = allData.find(r => r._id === rowNum); if (!row) return;
    editAgendaRow = rowNum;
    document.getElementById('modalTitle').textContent    = 'Edit Agenda';
    document.getElementById('fieldRow').value            = rowNum;
    document.getElementById('fieldTanggal').value        = row['tanggal']    || '';
    document.getElementById('fieldBulan').value          = row['bulan']      || '';
    document.getElementById('fieldJudul').value          = row['judul']      || '';
    document.getElementById('fieldKeterangan').value     = row['keterangan'] || '';
    document.getElementById('fieldIcon').value           = row['icon']       || '';
    document.getElementById('fieldHariLibur').value      = row['hari_libur'] || '';
    openModal('formModal');
}

async function submitAgendaForm() {
    const btn    = document.getElementById('btnSave');
    const isEdit = !!editAgendaRow;
    if (isEdit && !can('edit'))    { toast('Akses Ditolak','Tidak ada izin edit','error'); return; }
    if (!isEdit && !can('create')) { toast('Akses Ditolak','Tidak ada izin tambah','error'); return; }
    if (!document.getElementById('fieldTanggal').value || !document.getElementById('fieldJudul').value.trim()) {
        toast('Error','Tanggal dan Judul wajib diisi','error'); return;
    }
    const data = {
        tanggal:    document.getElementById('fieldTanggal').value,
        bulan:      document.getElementById('fieldBulan').value.trim(),
        judul:      document.getElementById('fieldJudul').value.trim(),
        keterangan: document.getElementById('fieldKeterangan').value.trim(),
        icon:       document.getElementById('fieldIcon').value.trim(),
        hari_libur: document.getElementById('fieldHariLibur').value,
    };
    btnLoading(btn, true);
    const res = await apiPost('/admin/api/sheets.php', { action: isEdit?'update':'create', page: PAGE, id: editAgendaRow, data });
    btnLoading(btn, false);
    if (res.success) { toast('Berhasil', isEdit?'Data diperbarui':'Agenda ditambahkan', 'success'); closeModal('formModal'); loadData(); }
    else toast('Error', res.error, 'error');
}

async function deleteAgendaRow(rowNum) {
    if (!can('delete')) { toast('Akses Ditolak','Tidak ada izin hapus','error'); return; }
    const row = allData.find(r => r._id === rowNum);
    confirmDialog('Hapus Agenda?', `"${row?.['judul']||'agenda ini'}" akan dihapus.`, async () => {
        const res = await apiPost('/admin/api/sheets.php', { action:'delete', page: PAGE, id: rowNum });
        if (res.success) { toast('Berhasil','Dihapus','success'); loadData(); }
        else toast('Error', res.error, 'error');
    });
}

/* ════════════════════════════════════════════════════════════════
   DOKUMEN SECTION
════════════════════════════════════════════════════════════════ */
const EXT_COLORS = {
    pdf:  '#e53935', doc: '#1565c0', docx: '#1565c0',
    xls:  '#2e7d32', xlsx:'#2e7d32', ppt: '#e65100', pptx:'#e65100',
    jpg:  '#6a1b9a', jpeg:'#6a1b9a', png: '#0277bd', gif: '#00838f',
    zip:  '#795548', rar: '#795548', mp3: '#d81b60', mp4: '#00695c',
};
function extColor(ext) { return EXT_COLORS[ext.toLowerCase()] || '#546e7a'; }

async function loadDokumen() {
    document.getElementById('dokumenLoadingState').style.display = 'block';
    document.getElementById('dokumenTableContainer').style.display = 'none';
    const res = await apiPost('/admin/api/sheets.php', { action: 'list', page: 'dokumen_paroki' });
    document.getElementById('dokumenLoadingState').style.display = 'none';
    if (!res.success) {
        if ((res.error||'').toLowerCase().includes('does not exist') || (res.error||'').toLowerCase().includes('relation')) {
            document.getElementById('dokumenSqlHint').style.display = 'block';
        }
        toast('Error', res.error, 'error');
        document.getElementById('dokumenEmptyState').style.display = 'block';
        return;
    }
    allDokumen = res.data || [];
    document.getElementById('badgeDokumen').textContent = allDokumen.length;
    const kats = [...new Set(allDokumen.map(d => d.kategori||'Umum').filter(Boolean))].sort();
    const sel  = document.getElementById('filterKategori');
    sel.innerHTML = '<option value="">Semua Kategori</option>' +
        kats.map(k => `<option value="${escHtml(k)}">${escHtml(k)}</option>`).join('');
    renderDokumenTable(allDokumen);
}

function renderDokumenTable(data) {
    const tbody = document.getElementById('dokumenTableBody');
    document.getElementById('dokumenCount').textContent = data.length + ' dokumen';
    if (!data.length) {
        document.getElementById('dokumenEmptyState').style.display = 'block';
        document.getElementById('dokumenTableContainer').style.display = 'none';
        return;
    }
    document.getElementById('dokumenTableContainer').style.display = 'block';
    document.getElementById('dokumenEmptyState').style.display = 'none';
    tbody.innerHTML = data.map((dk) => {
        const namaFile = dk.nama_file || '';
        const ext      = (namaFile.split('.').pop()||'').toLowerCase();
        const color    = extColor(ext);
        const isAktif  = dk.aktif === true || dk.aktif === 'true' || dk.aktif === 1;
        return `<tr>
          <td style="text-align:center;color:var(--text-secondary);font-size:12px">${dk.urutan||0}</td>
          <td>
            <div class="dt-icon-cell">
              <span class="dt-file-badge" style="background:${color}">${escHtml(ext.toUpperCase()||'FILE')}</span>
              <div>
                <div style="font-weight:600;font-size:13px">${escHtml(dk.judul||namaFile)}</div>
                <div style="font-size:11px;color:var(--text-secondary)">${escHtml(namaFile)}</div>
              </div>
            </div>
          </td>
          <td><span style="font-size:12px;background:rgba(184,134,11,0.09);color:#7a5800;padding:3px 9px;border-radius:20px;font-weight:600">${escHtml(dk.kategori||'Umum')}</span></td>
          <td style="font-size:12px;color:var(--text-secondary);max-width:180px">${escHtml(dk.deskripsi||'')}</td>
          <td style="font-size:12px;color:var(--text-secondary)">${escHtml(dk.ukuran||'')}</td>
          <td>${isAktif ? '<span class="badge badge-green">Aktif</span>' : '<span class="badge badge-gray">Nonaktif</span>'}</td>
          <td><div class="actions">
            ${can('edit')?`<button class="btn btn-icon btn-sm" onclick="openEditDokumenModal(${dk._id})" title="Edit">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            </button>`:''}
            <a href="/public/downloads/${encodeURIComponent(namaFile)}" target="_blank"
               class="btn btn-icon btn-sm" title="Unduh" style="color:var(--accent)">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            </a>
            ${can('delete')?`<button class="btn btn-icon btn-sm" onclick="deleteDokumen(${dk._id})" title="Hapus" style="color:var(--danger);border-color:rgba(224,82,82,0.3)">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a1 1 0 011-1h4a1 1 0 011 1v2"/></svg>
            </button>`:''}
          </div></td>
        </tr>`;
    }).join('');
}

function applyDokumenFilter() {
    const q   = document.getElementById('searchDokumen').value.toLowerCase();
    const kat = document.getElementById('filterKategori').value;
    renderDokumenTable(allDokumen.filter(d => {
        const matchQ   = !q   || (d.judul||'').toLowerCase().includes(q) || (d.nama_file||'').toLowerCase().includes(q);
        const matchKat = !kat || (d.kategori||'Umum') === kat;
        return matchQ && matchKat;
    }));
}
document.getElementById('searchDokumen').addEventListener('input', applyDokumenFilter);
document.getElementById('filterKategori').addEventListener('change', applyDokumenFilter);

function openAddDokumenModal() {
    if (!can('create')) { toast('Akses Ditolak','Tidak ada izin upload','error'); return; }
    editDokumenId = null; dkChosenFile = null;
    document.getElementById('dokumenModalTitle').textContent = 'Upload Dokumen';
    document.getElementById('btnDkSave').innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg> Upload & Simpan';
    ['dkFieldJudul','dkFieldKategori','dkFieldUkuran','dkFieldDeskripsi'].forEach(id => document.getElementById(id).value='');
    document.getElementById('dkFieldUrutan').value = '0';
    document.getElementById('dkFieldAktif').value  = 'true';
    document.getElementById('dkFieldNamaFileLama').value = '';
    document.getElementById('dkCurrentFile').textContent = '';
    document.getElementById('dkFileRequired').style.display = '';
    dkResetDropZone();
    openModal('dokumenModal');
}

function openEditDokumenModal(id) {
    if (!can('edit')) { toast('Akses Ditolak','Tidak ada izin edit','error'); return; }
    const dk = allDokumen.find(d => d._id === id); if (!dk) return;
    editDokumenId = id; dkChosenFile = null;
    document.getElementById('dokumenModalTitle').textContent = 'Edit Dokumen';
    document.getElementById('btnDkSave').innerHTML = 'Simpan Perubahan';
    document.getElementById('dkFieldId').value            = id;
    document.getElementById('dkFieldJudul').value         = dk.judul     || '';
    document.getElementById('dkFieldKategori').value      = dk.kategori  || '';
    document.getElementById('dkFieldUkuran').value        = dk.ukuran    || '';
    document.getElementById('dkFieldDeskripsi').value     = dk.deskripsi || '';
    document.getElementById('dkFieldUrutan').value        = dk.urutan    || 0;
    document.getElementById('dkFieldAktif').value         = dk.aktif ? 'true' : 'false';
    document.getElementById('dkFieldNamaFileLama').value  = dk.nama_file || '';
    document.getElementById('dkCurrentFile').textContent  = dk.nama_file ? ' (file: ' + dk.nama_file + ')' : '';
    document.getElementById('dkFileRequired').style.display = 'none';
    dkResetDropZone();
    openModal('dokumenModal');
}

function dkDragOver(e)  { e.preventDefault(); document.getElementById('dkDropZone').style.borderColor='#b8860b'; document.getElementById('dkDropZone').style.background='rgba(184,134,11,0.07)'; }
function dkDragLeave(e) { document.getElementById('dkDropZone').style.borderColor='rgba(184,134,11,0.35)'; document.getElementById('dkDropZone').style.background='rgba(184,134,11,0.03)'; }
function dkDrop(e) { e.preventDefault(); dkDragLeave(e); const f=e.dataTransfer.files[0]; if(f) dkSetFile(f); }
function dkFileChosen(inp) { if(inp.files[0]) dkSetFile(inp.files[0]); }
function dkSetFile(f) {
    dkChosenFile = f;
    const nm = document.getElementById('dkSelectedFileName');
    nm.textContent = '📎 ' + f.name + ' (' + (f.size>1048576?(f.size/1048576).toFixed(1)+' MB':(f.size/1024).toFixed(0)+' KB') + ')';
    nm.style.display = 'block';
    const ukEl = document.getElementById('dkFieldUkuran');
    if (!ukEl.value) ukEl.value = f.size>1048576?(f.size/1048576).toFixed(1)+' MB':(f.size/1024).toFixed(0)+' KB';
    const judEl = document.getElementById('dkFieldJudul');
    if (!judEl.value) judEl.value = f.name.replace(/\.[^.]+$/, '').replace(/[-_]/g,' ');
}
function dkResetDropZone() {
    const nm = document.getElementById('dkSelectedFileName');
    nm.style.display='none'; nm.textContent='';
    document.getElementById('dkFileInput').value='';
    document.getElementById('dkProgressWrap').style.display='none';
    document.getElementById('dkProgressText').style.display='none';
    document.getElementById('dkProgressBar').style.width='0%';
}

async function submitDokumenForm() {
    const btn    = document.getElementById('btnDkSave');
    const isEdit = !!editDokumenId;
    const judul  = document.getElementById('dkFieldJudul').value.trim();
    if (!judul) { toast('Error','Judul wajib diisi','error'); return; }
    if (!isEdit && !dkChosenFile) { toast('Error','Pilih file terlebih dahulu','error'); return; }
    btnLoading(btn, true);
    let namaFile = document.getElementById('dkFieldNamaFileLama').value;
    if (dkChosenFile) {
        namaFile = dkChosenFile.name;
        const fd = new FormData();
        fd.append('file', dkChosenFile);
        fd.append('dest', 'downloads');
        const pw = document.getElementById('dkProgressWrap');
        const pb = document.getElementById('dkProgressBar');
        const pt = document.getElementById('dkProgressText');
        pw.style.display = 'block'; pt.style.display = 'block'; pt.textContent = 'Mengunggah...';
        try {
            await new Promise((resolve, reject) => {
                const xhr = new XMLHttpRequest();
                xhr.open('POST', '/admin/api/upload.php');
                xhr.upload.onprogress = function(e) {
                    if (e.lengthComputable) { const pct = Math.round(e.loaded/e.total*100); pb.style.width = pct + '%'; pt.textContent = 'Mengunggah... ' + pct + '%'; }
                };
                xhr.onload = function() {
                    try { const r = JSON.parse(xhr.responseText); if (r.success) { namaFile = r.nama_file || dkChosenFile.name; resolve(); } else reject(new Error(r.error||'Upload gagal')); }
                    catch(e) { reject(new Error('Response tidak valid')); }
                };
                xhr.onerror = () => reject(new Error('Network error'));
                xhr.send(fd);
            });
        } catch (err) { btnLoading(btn, false); toast('Error Upload', err.message, 'error'); return; }
        pb.style.width = '100%'; pt.textContent = '✓ Upload selesai';
    }
    const namaFileLama = document.getElementById('dkFieldNamaFileLama').value;
    const payload = {
        action: isEdit ? 'update' : 'create', page: 'dokumen_paroki', id: editDokumenId,
        data: {
            judul, deskripsi: document.getElementById('dkFieldDeskripsi').value.trim(),
            nama_file: namaFile, ukuran: document.getElementById('dkFieldUkuran').value.trim(),
            kategori: document.getElementById('dkFieldKategori').value.trim() || 'Umum',
            urutan: parseInt(document.getElementById('dkFieldUrutan').value)||0,
            aktif: document.getElementById('dkFieldAktif').value === 'true',
        }
    };
    const res = await apiPost('/admin/api/sheets.php', payload);
    btnLoading(btn, false);
    if (res.success) {
        if (isEdit && dkChosenFile && namaFileLama && namaFileLama !== namaFile) {
            try { await apiPost('/admin/api/delete_file.php', { nama_file: namaFileLama, dest: 'downloads' }); } catch(e) { console.warn('File lama gagal dihapus:', e); }
        }
        toast('Berhasil', isEdit ? 'Dokumen diperbarui' : 'Dokumen berhasil diupload', 'success');
        closeModal('dokumenModal'); dkResetDropZone(); await loadDokumen();
    } else { toast('Error', res.error, 'error'); }
}

async function deleteDokumen(id) {
    if (!can('delete')) { toast('Akses Ditolak','Tidak ada izin hapus','error'); return; }
    const dk = allDokumen.find(d => d._id === id);
    confirmDialog('Hapus Dokumen?', `"${dk?.judul||'dokumen ini'}" akan dihapus beserta filenya.`, async () => {
        const res = await apiPost('/admin/api/sheets.php', { action:'delete', page:'dokumen_paroki', id });
        if (!res.success) { toast('Error', res.error, 'error'); return; }
        if (dk?.nama_file) {
            try { await apiPost('/admin/api/delete_file.php', { nama_file: dk.nama_file, dest: 'downloads' }); } catch(e) { console.warn('File fisik gagal dihapus:', e); }
        }
        toast('Berhasil','Dokumen dan file berhasil dihapus','success'); loadDokumen();
    });
}

/* ════════════════════════════════════════════════════════════════
   JADWAL PETUGAS SECTION
════════════════════════════════════════════════════════════════ */

async function loadPetugas() {
    document.getElementById('petugasLoadingState').style.display = 'block';
    document.getElementById('petugasGridContainer').style.display = 'none';
    document.getElementById('petugasEmptyState').style.display = 'none';

    const res = await apiPost('/admin/api/sheets.php', { action: 'list', page: PT_PAGE });
    document.getElementById('petugasLoadingState').style.display = 'none';

    if (!res.success) {
        if ((res.error||'').toLowerCase().includes('does not exist') || (res.error||'').toLowerCase().includes('relation')) {
            document.getElementById('petugasSqlHint').style.display = 'block';
        }
        toast('Error', res.error, 'error');
        document.getElementById('petugasEmptyState').style.display = 'block';
        return;
    }
    allPetugas = res.data || [];
    document.getElementById('badgePetugas').textContent = allPetugas.length;
    renderPetugasGrid(allPetugas);
}

function renderPetugasGrid(data) {
    document.getElementById('petugasCount').textContent = data.length + ' jadwal';
    if (!data.length) {
        document.getElementById('petugasEmptyState').style.display = 'block';
        document.getElementById('petugasGridContainer').style.display = 'none';
        return;
    }
    document.getElementById('petugasGridContainer').style.display = 'block';
    document.getElementById('petugasEmptyState').style.display = 'none';

    const showAksi = can('edit') || can('delete');
    const grid = document.getElementById('petugasAdminGrid');
    grid.innerHTML = data.map(p => {
        const imgUrl   = '/public/jadwal_petugas/' + encodeURIComponent(p.nama_file || '');
        const isAktif  = p.aktif === true || p.aktif === 'true' || p.aktif === 1;
        return `<div class="petugas-admin-card">
          <img src="${escHtml(imgUrl)}" alt="${escHtml(p.judul||'')}" class="petugas-admin-img" loading="lazy"
               onerror="this.style.background='#ddd';this.style.minHeight='160px'">
          <div class="petugas-admin-body">
            <div class="petugas-admin-title">${escHtml(p.judul||'Jadwal Petugas')}</div>
            <div class="petugas-admin-meta">
              Urutan: ${p.urutan||0} &nbsp;·&nbsp;
              ${isAktif ? '<span style="color:#2e7d32;font-weight:600">Aktif</span>' : '<span style="color:#999">Nonaktif</span>'}
            </div>
            ${showAksi ? `<div class="petugas-admin-actions">
              ${can('edit') ? `<button class="btn btn-icon btn-sm" onclick="openEditPetugasModal(${p._id})" title="Edit" style="flex:1;justify-content:center;gap:5px;font-size:11.5px">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg> Edit
              </button>` : ''}
              ${can('delete') ? `<button class="btn btn-icon btn-sm" onclick="deletePetugas(${p._id})" title="Hapus" style="color:var(--danger);border-color:rgba(224,82,82,0.3);flex:1;justify-content:center;gap:5px;font-size:11.5px">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a1 1 0 011-1h4a1 1 0 011 1v2"/></svg> Hapus
              </button>` : ''}
            </div>` : ''}
          </div>
        </div>`;
    }).join('');
}

function applyPetugasFilter() {
    const q = document.getElementById('searchPetugas').value.toLowerCase();
    renderPetugasGrid(q ? allPetugas.filter(p => (p.judul||'').toLowerCase().includes(q)) : allPetugas);
}
document.getElementById('searchPetugas').addEventListener('input', applyPetugasFilter);

// ── Modal Jadwal Petugas ──────────────────────────────────────────

function openAddPetugasModal() {
    if (!can('create')) { toast('Akses Ditolak','Tidak ada izin upload','error'); return; }
    editPetugasId = null; ptChosenFile = null;
    document.getElementById('petugasModalTitle').textContent = 'Upload Jadwal Petugas';
    document.getElementById('btnPtSave').innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg> Upload & Simpan';
    document.getElementById('ptFieldJudul').value  = '';
    document.getElementById('ptFieldUrutan').value = '0';
    document.getElementById('ptFieldAktif').value  = 'true';
    document.getElementById('ptFieldNamaFileLama').value = '';
    document.getElementById('ptCurrentFile').textContent = '';
    document.getElementById('ptFileRequired').style.display = '';
    ptResetDropZone();
    openModal('petugasModal');
}

function openEditPetugasModal(id) {
    if (!can('edit')) { toast('Akses Ditolak','Tidak ada izin edit','error'); return; }
    const p = allPetugas.find(x => x._id === id); if (!p) return;
    editPetugasId = id; ptChosenFile = null;
    document.getElementById('petugasModalTitle').textContent = 'Edit Jadwal Petugas';
    document.getElementById('btnPtSave').innerHTML = 'Simpan Perubahan';
    document.getElementById('ptFieldId').value            = id;
    document.getElementById('ptFieldJudul').value         = p.judul  || '';
    document.getElementById('ptFieldUrutan').value        = p.urutan || 0;
    document.getElementById('ptFieldAktif').value         = p.aktif ? 'true' : 'false';
    document.getElementById('ptFieldNamaFileLama').value  = p.nama_file || '';
    document.getElementById('ptCurrentFile').textContent  = p.nama_file ? ' (gambar saat ini: ' + p.nama_file + ')' : '';
    document.getElementById('ptFileRequired').style.display = 'none';
    // Tampilkan preview gambar saat ini
    if (p.nama_file) {
        const previewWrap = document.getElementById('ptImgPreviewWrap');
        const previewImg  = document.getElementById('ptImgPreview');
previewImg.src = '/public/jadwal_petugas/' + encodeURIComponent(p.nama_file);
        previewWrap.style.display = 'block';
    }
    ptResetDropZone(false); // false = jangan hapus preview
    openModal('petugasModal');
}

// ── Drag & Drop Petugas ──────────────────────────────────────────
function ptDragOver(e)  { e.preventDefault(); document.getElementById('ptDropZone').classList.add('drag-over'); }
function ptDragLeave(e) { document.getElementById('ptDropZone').classList.remove('drag-over'); }
function ptDrop(e)      { e.preventDefault(); ptDragLeave(e); const f=e.dataTransfer.files[0]; if(f) ptSetFile(f); }
function ptFileChosen(inp) { if(inp.files[0]) ptSetFile(inp.files[0]); }

function ptSetFile(f) {
    ptChosenFile = f;
    const nm = document.getElementById('ptSelectedFileName');
    nm.textContent = '🖼️ ' + f.name + ' (' + (f.size>1048576?(f.size/1048576).toFixed(1)+' MB':(f.size/1024).toFixed(0)+' KB') + ')';
    nm.style.display = 'block';
    // Auto isi judul dari nama file
    const judEl = document.getElementById('ptFieldJudul');
    if (!judEl.value) judEl.value = f.name.replace(/\.[^.]+$/, '').replace(/[-_]/g,' ');
    // Preview gambar lokal
    const reader = new FileReader();
    reader.onload = function(e) {
        const pw = document.getElementById('ptImgPreviewWrap');
        const pi = document.getElementById('ptImgPreview');
        pi.src = e.target.result; pw.style.display = 'block';
    };
    reader.readAsDataURL(f);
}

function ptResetDropZone(clearPreview = true) {
    const nm = document.getElementById('ptSelectedFileName');
    nm.style.display='none'; nm.textContent='';
    document.getElementById('ptFileInput').value='';
    document.getElementById('ptProgressWrap').style.display='none';
    document.getElementById('ptProgressText').style.display='none';
    document.getElementById('ptProgressBar').style.width='0%';
    if (clearPreview) {
        document.getElementById('ptImgPreviewWrap').style.display='none';
        document.getElementById('ptImgPreview').src='';
    }
}

// ── Submit Jadwal Petugas ─────────────────────────────────────────
async function submitPetugasForm() {
    const btn    = document.getElementById('btnPtSave');
    const isEdit = !!editPetugasId;
    const judul  = document.getElementById('ptFieldJudul').value.trim();
    if (!judul) { toast('Error','Judul wajib diisi','error'); return; }
    if (!isEdit && !ptChosenFile) { toast('Error','Pilih gambar terlebih dahulu','error'); return; }

    btnLoading(btn, true);
    let namaFile = document.getElementById('ptFieldNamaFileLama').value;

    // ── Upload gambar (jika ada file baru) ──
    if (ptChosenFile) {
        const fd = new FormData();
        fd.append('file', ptChosenFile);
        fd.append('dest', 'jadwal_petugas'); 

        const pw = document.getElementById('ptProgressWrap');
        const pb = document.getElementById('ptProgressBar');
        const pt = document.getElementById('ptProgressText');
        pw.style.display = 'block'; pt.style.display = 'block'; pt.textContent = 'Mengunggah gambar...';

        try {
            await new Promise((resolve, reject) => {
                const xhr = new XMLHttpRequest();
                xhr.open('POST', '/admin/api/upload.php');
                xhr.upload.onprogress = function(e) {
                    if (e.lengthComputable) { const pct = Math.round(e.loaded/e.total*100); pb.style.width = pct + '%'; pt.textContent = 'Mengunggah... ' + pct + '%'; }
                };
                xhr.onload = function() {
                    try { const r = JSON.parse(xhr.responseText); if (r.success) { namaFile = r.nama_file || ptChosenFile.name; resolve(); } else reject(new Error(r.error||'Upload gagal')); }
                    catch(e) { reject(new Error('Response tidak valid')); }
                };
                xhr.onerror = () => reject(new Error('Network error'));
                xhr.send(fd);
            });
        } catch (err) { btnLoading(btn, false); toast('Error Upload', err.message, 'error'); return; }
        pb.style.width = '100%'; pt.textContent = '✓ Upload selesai';
    }

    // ── Simpan metadata ke Supabase ──
    const namaFileLama = document.getElementById('ptFieldNamaFileLama').value;
    const payload = {
        action: isEdit ? 'update' : 'create',
        page:   PT_PAGE,
        id:     editPetugasId,
        data: {
            judul,
            nama_file: namaFile,
            urutan:    parseInt(document.getElementById('ptFieldUrutan').value)||0,
            aktif:     document.getElementById('ptFieldAktif').value === 'true',
        }
    };

    const res = await apiPost('/admin/api/sheets.php', payload);
    btnLoading(btn, false);

    if (res.success) {
        // Hapus gambar lama dari server jika ada gambar baru saat edit
        if (isEdit && ptChosenFile && namaFileLama && namaFileLama !== namaFile) {
            try { await apiPost('/admin/api/delete_file.php', { nama_file: namaFileLama, dest: 'jadwal_petugas' }); }
            catch(e) { console.warn('Gambar lama gagal dihapus:', e); }
        }
        toast('Berhasil', isEdit ? 'Jadwal diperbarui' : 'Jadwal berhasil diupload', 'success');
        closeModal('petugasModal');
        ptResetDropZone();
        await loadPetugas();
    } else {
        toast('Error', res.error, 'error');
    }
}

// ── Hapus Jadwal Petugas ──────────────────────────────────────────
async function deletePetugas(id) {
    if (!can('delete')) { toast('Akses Ditolak','Tidak ada izin hapus','error'); return; }
    const p = allPetugas.find(x => x._id === id);
    confirmDialog(
        'Hapus Jadwal Petugas?',
        `"${p?.judul||'jadwal ini'}" akan dihapus beserta gambarnya dari server.`,
        async () => {
            // 1. Hapus record dari Supabase
            const res = await apiPost('/admin/api/sheets.php', { action:'delete', page: PT_PAGE, id });
            if (!res.success) { toast('Error', res.error, 'error'); return; }
            // 2. Hapus file gambar fisik dari server
            if (p?.nama_file) {
                try { await apiPost('/admin/api/delete_file.php', { nama_file: p.nama_file, dest: 'jadwal_petugas' }); }
                catch(e) { console.warn('Gambar fisik gagal dihapus:', e); }
            }
            toast('Berhasil','Jadwal dan gambar berhasil dihapus','success');
            loadPetugas();
        }
    );
}

/* ── Mobile card-table auto-labels ── */
(function(){
    function applyLabels(tableId){
        if(window.innerWidth>768) return;
        var t=document.getElementById(tableId); if(!t) return;
        var hdrs=Array.from(t.querySelectorAll('thead th')).map(th=>th.textContent.trim());
        t.querySelectorAll('tbody tr').forEach(tr=>{
            Array.from(tr.querySelectorAll('td')).forEach((td,i)=>{
                var h=hdrs[i]||'';
                if(!h||h==='Aksi'||td.querySelector('.actions')||td.querySelector('.btn-icon')) td.classList.add('td-aksi');
                else if(!td.hasAttribute('data-label')) td.setAttribute('data-label',h);
            });
        });
    }
    window._applyLabels=applyLabels;
})();
</script>

<?php adminFooter(); ?>