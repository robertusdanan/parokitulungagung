<?php
/**
 * admin/pages/stories.php
 * ─────────────────────────────────────────────────────────────────────────
 * Halaman Admin untuk mengelola Stories Paroki (Maksimal 21 media).
 * Terintegrasi langsung dengan Cloudflare R2 bucket `stories/`.
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
adminBoot();

$user = requireLogin();
$isSuper = $user['role'] === ROLE_SUPERADMIN;
$userPerms = getPermissionsMap($user);

$hasStories = $isSuper || array_key_exists('stories', $userPerms);
if (!$hasStories) {
    http_response_code(403);
    die(renderAccessDenied());
}

$canCreate = $isSuper || in_array('create', $userPerms['stories'] ?? []);
$canEdit   = $isSuper || in_array('edit',   $userPerms['stories'] ?? []);
$canDelete = $isSuper || in_array('delete', $userPerms['stories'] ?? []);

adminHeader('Stories', 'stories', $user);
?>

<style>
.stories-capacity-card {
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
  padding: 18px 20px;
  margin-bottom: 20px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  flex-wrap: wrap;
}
.stories-cap-info {
  display: flex;
  align-items: center;
  gap: 12px;
}
.stories-cap-icon {
  width: 44px;
  height: 44px;
  border-radius: 10px;
  background: rgba(201, 162, 58, 0.12);
  color: var(--accent);
  display: flex;
  align-items: center;
  justify-content: center;
}
.stories-cap-title {
  font-size: 15px;
  font-weight: 600;
  color: var(--text-primary);
  margin-bottom: 2px;
}
.stories-cap-sub {
  font-size: 12.5px;
  color: var(--text-secondary);
}
.stories-cap-meter {
  min-width: 220px;
  flex: 1;
  max-width: 320px;
}
.stories-bar-wrap {
  width: 100%;
  height: 8px;
  background: var(--bg-card2);
  border-radius: 999px;
  overflow: hidden;
  margin-top: 6px;
}
.stories-bar-fill {
  height: 100%;
  width: 0%;
  background: linear-gradient(90deg, var(--accent), #eab308);
  border-radius: 999px;
  transition: width .3s ease;
}
.stories-bar-fill.full {
  background: linear-gradient(90deg, #ef4444, #dc2626);
}

/* ── Tombol Arsip Media ── */
.btn-archive {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 18px;
  background: rgba(201, 162, 58, 0.12);
  border: 1px solid rgba(201, 162, 58, 0.45);
  color: var(--accent);
  font-size: 13.5px;
  font-weight: 600;
  border-radius: 10px;
  cursor: pointer;
  transition: all .2s ease;
  white-space: nowrap;
}
.btn-archive:hover {
  background: rgba(201, 162, 58, 0.22);
  transform: translateY(-1px);
  box-shadow: 0 4px 14px rgba(0,0,0,0.18);
}
.btn-archive .archive-count {
  background: var(--accent);
  color: #14100a;
  font-size: 11px;
  font-weight: 700;
  min-width: 20px;
  height: 20px;
  padding: 0 6px;
  border-radius: 999px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

/* ── Modal Arsip Media ── */
.archive-modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(10, 8, 16, 0.72);
  backdrop-filter: blur(6px);
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px;
  animation: archiveModalFade .18s ease;
}
@keyframes archiveModalFade {
  from { opacity: 0; }
  to   { opacity: 1; }
}
.archive-modal {
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: 16px;
  width: 100%;
  max-width: 860px;
  max-height: 86vh;
  display: flex;
  flex-direction: column;
  box-shadow: 0 24px 80px rgba(0,0,0,0.5);
  animation: archiveModalSlide .22s cubic-bezier(0.2, 0.8, 0.3, 1);
}
@keyframes archiveModalSlide {
  from { transform: translateY(18px) scale(0.985); opacity: 0; }
  to   { transform: translateY(0) scale(1); opacity: 1; }
}
.archive-modal-header {
  padding: 20px 24px;
  border-bottom: 1px solid var(--border);
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
}
.archive-modal-title {
  display: flex;
  align-items: center;
  gap: 12px;
}
.archive-modal-icon {
  width: 42px;
  height: 42px;
  border-radius: 10px;
  background: rgba(201, 162, 58, 0.14);
  color: var(--accent);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.archive-modal-header h2 {
  margin: 0 0 3px;
  font-size: 17px;
  font-weight: 700;
  color: var(--text-primary);
}
.archive-modal-header p {
  margin: 0;
  font-size: 12.5px;
  color: var(--text-secondary);
  line-height: 1.5;
}
.archive-modal-close {
  width: 34px;
  height: 34px;
  border-radius: 8px;
  border: 1px solid var(--border);
  background: var(--bg-card2);
  color: var(--text-secondary);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all .15s ease;
  flex-shrink: 0;
}
.archive-modal-close:hover {
  color: var(--danger);
  border-color: rgba(239,68,68,0.4);
}
.archive-modal-body {
  padding: 20px 24px;
  overflow-y: auto;
  flex: 1;
}
.archive-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(190px, 1fr));
  gap: 14px;
}
.archive-card {
  background: var(--bg-card2);
  border: 1px solid var(--border);
  border-radius: 12px;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  transition: all .2s ease;
}
.archive-card:hover {
  border-color: rgba(201, 162, 58, 0.4);
  transform: translateY(-2px);
}
.archive-thumb {
  width: 100%;
  aspect-ratio: 16/9;
  background: #111;
  position: relative;
  overflow: hidden;
}
.archive-thumb img, .archive-thumb video {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}
.archive-thumb .story-badge {
  top: 6px;
  left: 6px;
  font-size: 10px;
}
.archive-card-body {
  padding: 10px 12px;
  flex: 1;
  display: flex;
  flex-direction: column;
}
.archive-card-name {
  font-size: 12.5px;
  font-weight: 600;
  color: var(--text-primary);
  margin-bottom: 2px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.archive-card-meta {
  font-size: 11px;
  color: var(--text-muted);
  margin-bottom: 10px;
}
.archive-card-actions {
  margin-top: auto;
  display: flex;
  gap: 6px;
}
.archive-card-actions button {
  flex: 1;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 5px;
  padding: 7px 8px;
  font-size: 11.5px;
  font-weight: 600;
  border-radius: 8px;
  border: 1px solid var(--border);
  cursor: pointer;
  transition: all .15s ease;
  background: var(--bg-card);
}
.archive-act-restore {
  color: #34d399;
  border-color: rgba(52, 211, 153, 0.35);
}
.archive-act-restore:hover {
  background: rgba(52, 211, 153, 0.12);
}
.archive-act-delete {
  color: var(--danger);
  border-color: rgba(239, 68, 68, 0.3);
}
.archive-act-delete:hover {
  background: rgba(239, 68, 68, 0.1);
}
.archive-empty {
  text-align: center;
  padding: 48px 20px;
  color: var(--text-muted);
}
.archive-empty svg {
  margin: 0 auto 12px;
  opacity: 0.5;
}
.archive-empty strong {
  display: block;
  font-size: 14px;
  color: var(--text-primary);
  margin-bottom: 4px;
}
.archive-empty span {
  font-size: 12.5px;
}
.archive-loading {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  padding: 48px 20px;
  color: var(--text-secondary);
  font-size: 13px;
}
.archive-spinner {
  width: 22px;
  height: 22px;
  border-radius: 50%;
  border: 2px solid rgba(201, 162, 58, 0.2);
  border-top-color: var(--accent);
  animation: storyAdminSpin .9s linear infinite;
}

/* Upload card */
.stories-upload-card {
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
  padding: 20px;
  margin-bottom: 24px;
}
.stories-upload-zone {
  border: 2px dashed var(--border);
  border-radius: var(--radius-md);
  padding: 28px 20px;
  text-align: center;
  background: var(--bg-card2);
  cursor: pointer;
  transition: all .2s ease;
  position: relative;
}
.stories-upload-zone:hover, .stories-upload-zone.dragover {
  border-color: var(--accent);
  background: rgba(201, 162, 58, 0.05);
}
.stories-upload-zone.disabled {
  opacity: 0.55;
  cursor: not-allowed;
  pointer-events: none;
}

/* Stories Grid */
.stories-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
  gap: 16px;
  margin-top: 14px;
}
.story-card {
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
  overflow: hidden;
  display: flex;
  flex-direction: column;
  transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
  position: relative;
}
.story-card:hover {
  border-color: rgba(201, 162, 58, 0.4);
  box-shadow: 0 4px 14px rgba(0,0,0,0.06);
  transform: translateY(-2px);
}
.story-thumb-wrap {
  width: 100%;
  aspect-ratio: 16/9;
  background: #111;
  position: relative;
  overflow: hidden;
}
.story-thumb-wrap img, .story-thumb-wrap video {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

/* ── Indikator "Sedang Diproses di Cloud" (admin grid) ── */
.story-thumb-wrap.is-processing {
  background: linear-gradient(160deg, #110e16 0%, #1a1424 100%);
  display: flex;
  align-items: center;
  justify-content: center;
}
.story-thumb-processing {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
  color: #f0d98a;
  padding: 12px;
  text-align: center;
}
.story-thumb-spinner {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  border: 2px solid rgba(240, 217, 138, 0.18);
  border-top-color: #f0d98a;
  border-right-color: #f0d98a;
  animation: storyAdminSpin 1.1s cubic-bezier(0.4, 0.1, 0.3, 1) infinite;
}
.story-thumb-processing-text {
  font-size: 11px;
  font-weight: 600;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  color: rgba(240, 217, 138, 0.85);
  line-height: 1.3;
}
.story-processing-badge {
  position: absolute;
  top: 8px;
  left: 50%;
  transform: translateX(-50%);
  background: rgba(218, 175, 90, 0.92);
  color: #1a1424;
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.05em;
  padding: 3px 9px;
  border-radius: 999px;
  text-transform: uppercase;
  box-shadow: 0 2px 6px rgba(0,0,0,0.3);
  z-index: 3;
}
@keyframes storyAdminSpin {
  to { transform: rotate(360deg); }
}
.story-badge {
  position: absolute;
  top: 8px;
  left: 8px;
  background: rgba(0,0,0,0.65);
  backdrop-filter: blur(4px);
  color: #fff;
  font-size: 11px;
  font-weight: 600;
  padding: 3px 8px;
  border-radius: 6px;
  display: flex;
  align-items: center;
  gap: 4px;
}
.story-order-badge {
  position: absolute;
  top: 8px;
  right: 8px;
  background: rgba(201, 162, 58, 0.9);
  color: #fff;
  font-size: 11px;
  font-weight: 700;
  width: 22px;
  height: 22px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}
.story-body {
  padding: 14px 16px;
  flex: 1;
  display: flex;
  flex-direction: column;
}
.story-name {
  font-weight: 600;
  font-size: 13.5px;
  color: var(--text-primary);
  margin-bottom: 6px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.story-desc {
  font-size: 12px;
  color: var(--text-secondary);
  line-height: 1.45;
  margin-bottom: 12px;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  flex: 1;
}
.story-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-top: 1px solid var(--border);
  padding-top: 10px;
  margin-top: auto;
}
.story-date {
  font-size: 11px;
  color: var(--text-muted);
}
.story-actions {
  display: flex;
  align-items: center;
  gap: 6px;
}
</style>

<div class="page-header">
  <div class="page-header-left">
    <h1>Stories Paroki</h1>
    <p>Kelola konten Stories visual (foto &amp; video) yang ditampilkan secara dinamis di website (Maks. 21 media aktif, rotasi otomatis).</p>
  </div>
  <div class="page-header-right">
    <button type="button" class="btn-archive" onclick="openArchiveModal()">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <polyline points="21 8 21 21 3 21 3 8"></polyline>
        <rect x="1" y="3" width="22" height="5"></rect>
        <line x1="10" y1="12" x2="14" y2="12"></line>
      </svg>
      <span>Arsip Media Stories</span>
      <span class="archive-count" id="headerArchiveCount">...</span>
    </button>
  </div>
</div>

<!-- Upload Section -->
<?php if ($canCreate): ?>
<div class="stories-upload-card" id="uploadSectionCard">
  <form id="storyUploadForm" onsubmit="event.preventDefault(); submitStoryUpload();">
    <input type="file" id="storyFileInput" accept="image/*,video/*" style="display:none" onchange="onFilePicked(this.files[0])">

    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
      <strong style="font-size:15px;color:var(--text-primary);display:flex;align-items:center;gap:8px">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
        Tambah Media Stories Baru
      </strong>
      <span style="font-size:12px;color:var(--text-muted)">Foto otomatis dioptimasi WebP, video dikompresi otomatis</span>
    </div>

    <div id="quotaInfoBanner" style="display:none;padding:12px 14px;background:rgba(59,130,246,0.1);border:1px solid rgba(59,130,246,0.25);border-radius:8px;font-size:12.5px;color:var(--text-primary);margin-bottom:14px">
      ℹ️ <b>Kapasitas Maksimal (21/21 Media Aktif).</b> Mempublikasikan Stories baru akan menggeser media terlama di urutan paling belakang secara otomatis. <i>File media terlama tetap tersimpan aman di arsip.</i>
    </div>

    <!-- Dropzone -->
    <div class="stories-upload-zone" id="storiesDropzone" onclick="triggerFileInput()">
      <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="color:var(--text-muted);margin:0 auto 8px">
        <path d="M4 14.899A7 7 0 1 1 15.71 8h1.79a4.5 4.5 0 0 1 2.5 8.242"/><path d="M12 12v9"/><path d="m16 16-4-4-4 4"/>
      </svg>
      <p style="font-size:13.5px;font-weight:600;color:var(--text-primary);margin:0 0 4px">Seret file foto/video ke sini, atau klik untuk memilih file</p>
      <p style="font-size:12px;color:var(--text-muted);margin:0">Mendukung Foto (JPG, PNG, WebP) &amp; Video (MP4, MOV, MKV, WebM)</p>
    </div>

    <!-- Preview File Terpilih -->
    <div id="selectedFilePreviewCard" style="display:none;margin-top:14px;padding:12px 16px;background:var(--bg-card2);border:1px solid var(--border);border-radius:8px;align-items:center;justify-content:space-between;gap:12px">
      <div style="display:flex;align-items:center;gap:12px;min-width:0;flex:1">
        <div id="filePreviewThumb" style="width:48px;height:48px;border-radius:6px;background:#000;overflow:hidden;flex-shrink:0;display:flex;align-items:center;justify-content:center;color:var(--text-muted)"></div>
        <div style="min-width:0;flex:1">
          <div id="filePreviewName" style="font-size:13px;font-weight:600;color:var(--text-primary);white-space:nowrap;overflow:hidden;text-overflow:ellipsis"></div>
          <div id="filePreviewSize" style="font-size:11.5px;color:var(--text-muted)"></div>
        </div>
      </div>
      <button type="button" class="btn btn-secondary btn-sm" onclick="clearSelectedFile()" style="color:var(--danger)">Ganti File</button>
    </div>

    <!-- Input Fields -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-top:16px" id="uploadInputsRow">
      <div>
        <label class="form-label" style="font-size:12.5px;font-weight:600">Judul / Nama Media <span style="color:var(--danger)">*</span></label>
        <input type="text" id="storyFileNameInput" class="form-control" placeholder="Misal: Perayaan Paskah 2026" oninput="validateFormState()">
        <small style="color:var(--text-muted);font-size:11px">Judul media wajib diisi.</small>
      </div>
      <div>
        <label class="form-label" style="font-size:12.5px;font-weight:600">Deskripsi Stories <span style="color:var(--danger)">*</span></label>
        <input type="text" id="storyDescInput" class="form-control" placeholder="Deskripsi singkat yang tampil saat media dibuka..." oninput="validateFormState()">
        <small style="color:var(--text-muted);font-size:11px">Deskripsi singkat yang tampil di bagian bawah media.</small>
      </div>
    </div>

    <!-- Action Trigger Button -->
    <div style="display:flex;justify-content:flex-end;margin-top:18px">
      <button type="submit" id="btnPublishStory" class="btn btn-primary" style="display:inline-flex;align-items:center;gap:8px;padding:10px 22px;font-weight:600">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
          <polyline points="17 21 13 13 7 13 7 21"/>
          <polyline points="7 3 7 8 15 8"/>
        </svg>
        <span>Publikasikan Stories</span>
      </button>
    </div>
  </form>

  <!-- Progress Bar Upload -->
  <div id="storyUploadProgress" style="display:none;margin-top:14px;padding:12px 14px;background:var(--bg-card2);border-radius:8px;border:1px solid var(--border)">
    <div style="display:flex;justify-content:space-between;font-size:12.5px;font-weight:600;color:var(--text-primary);margin-bottom:6px">
      <span id="storyUploadStatusLabel">Mengunggah &amp; mengoptimasi media...</span>
      <span id="storyUploadPercent">0%</span>
    </div>
    <div class="stories-bar-wrap" style="height:6px">
      <div class="stories-bar-fill" id="storyUploadBarFill" style="width:0%"></div>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Daftar Stories Grid -->
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
  <strong style="font-size:14px;color:var(--text-primary)">Daftar Media Stories</strong>
  <button class="btn btn-secondary btn-sm" onclick="loadStories()">↻ Refresh</button>
</div>

<div id="storiesLoading" style="text-align:center;padding:40px"><div class="spinner"></div></div>
<div id="storiesGrid" class="stories-grid" style="display:none"></div>
<div id="storiesEmpty" style="display:none;text-align:center;padding:50px 20px;background:var(--bg-card);border:1px dashed var(--border);border-radius:var(--radius-md)">
  <div style="font-size:32px;margin-bottom:8px">🎬</div>
  <p style="font-size:14px;color:var(--text-primary);font-weight:600;margin-bottom:4px">Belum ada media Stories</p>
  <p style="font-size:12.5px;color:var(--text-muted);max-width:360px;margin:0 auto">Unggah foto atau video di area atas untuk mulai menampilkan Stories di website.</p>
</div>

<!-- Modal Edit Story (Elegan & Responsif) -->
<div class="modal-overlay" id="editStoryModal">
  <div class="modal" style="max-width:560px">
    <div class="modal-header">
      <div style="display:flex;align-items:center;gap:10px">
        <div style="width:32px;height:32px;border-radius:8px;background:rgba(201,162,58,0.15);color:var(--accent);display:flex;align-items:center;justify-content:center">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
            <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
            <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
          </svg>
        </div>
        <div>
          <span class="modal-title" style="font-size:16px;font-weight:700">Edit Media Stories</span>
          <div style="font-size:11.5px;color:var(--text-muted)">Ubah nama file atau keterangan media</div>
        </div>
      </div>
      <button type="button" class="modal-close" onclick="closeEditModal()">&times;</button>
    </div>
    <div class="modal-body" style="padding:20px 24px">
      <input type="hidden" id="editStoryId">

      <!-- Preview media thumbnail di dalam modal -->
      <div style="display:flex;gap:16px;align-items:flex-start;padding:12px;background:var(--bg-card2);border:1px solid var(--border);border-radius:10px;margin-bottom:18px">
        <div style="width:96px;height:72px;border-radius:8px;overflow:hidden;background:#000;flex-shrink:0;position:relative">
          <img id="editStoryPreviewImg" src="" alt="Preview" style="width:100%;height:100%;object-fit:cover;display:block">
          <div id="editStoryBadgeType" style="position:absolute;bottom:4px;left:4px;font-size:9px;font-weight:700;padding:2px 6px;border-radius:4px;background:rgba(0,0,0,0.7);color:#fff"></div>
        </div>
        <div style="flex:1;min-width:0">
          <div id="editStoryMetaTitle" style="font-size:13px;font-weight:600;color:var(--text-primary);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-bottom:3px"></div>
          <div id="editStoryMetaDate" style="font-size:11.5px;color:var(--text-muted);margin-bottom:6px"></div>
          <a id="editStoryMediaLink" href="#" target="_blank" rel="noopener" style="font-size:11.5px;color:var(--accent);display:inline-flex;align-items:center;gap:4px;text-decoration:none">
            <span>Buka file asli</span>
            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
          </a>
        </div>
      </div>

      <div class="form-group" style="margin-bottom:16px">
        <label class="form-label" style="font-size:12.5px;font-weight:600;margin-bottom:6px;display:block">Nama File / Judul</label>
        <input type="text" id="editStoryFileName" class="form-control" placeholder="Contoh: Misa Natal 2026">
        <small style="color:var(--text-muted);font-size:11px;margin-top:4px;display:block">Nama judul yang muncul di preview stories.</small>
      </div>

      <div class="form-group" style="margin-bottom:0">
        <label class="form-label" style="font-size:12.5px;font-weight:600;margin-bottom:6px;display:block">Deskripsi</label>
        <textarea id="editStoryDescription" class="form-control" rows="3" placeholder="Tulis keterangan atau deskripsi media di sini..."></textarea>
        <small style="color:var(--text-muted);font-size:11px;margin-top:4px;display:block">Keterangan opsional yang tampil di bagian bawah saat stories dibuka.</small>
      </div>
    </div>
    <div class="modal-footer" style="padding:14px 24px;display:flex;justify-content:flex-end;gap:10px;border-top:1px solid var(--border)">
      <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Batal</button>
      <button type="button" class="btn btn-primary" id="btnSaveEdit" onclick="saveStoryEdit()">Simpan Perubahan</button>
    </div>
  </div>
</div>

<!-- Modal Arsip Media Stories (Elegan & Responsif) -->
<div class="archive-modal-overlay" id="archiveModal" style="display:none" onclick="if(event.target===this) closeArchiveModal()">
  <div class="archive-modal">
    <div class="archive-modal-header">
      <div class="archive-modal-title">
        <div class="archive-modal-icon">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="21 8 21 21 3 21 3 8"></polyline>
            <rect x="1" y="3" width="22" height="5"></rect>
            <line x1="10" y1="12" x2="14" y2="12"></line>
          </svg>
        </div>
        <div>
          <h2>Arsip Media Stories</h2>
        </div>
      </div>
      <button type="button" class="archive-modal-close" onclick="closeArchiveModal()" title="Tutup">&times;</button>
    </div>

    <!-- Filter & Toolbar -->
    <div style="padding:12px 24px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;background:var(--bg-card2)">
      <div style="display:flex;align-items:center;gap:10px;flex:1;min-width:200px">
        <input type="text" id="archiveSearchInput" class="form-control" placeholder="Cari nama file media di arsip..." oninput="filterArchiveGrid()" style="font-size:12.5px;padding:7px 12px">
      </div>
      <div style="display:flex;align-items:center;gap:8px">
        <select id="archiveTypeFilter" class="form-control" onchange="filterArchiveGrid()" style="font-size:12.5px;padding:7px 12px;width:auto">
          <option value="all">Semua Tipe</option>
          <option value="image">📷 Foto Saja</option>
          <option value="video">🎬 Video Saja</option>
        </select>
        <button type="button" class="btn btn-secondary btn-sm" onclick="loadArchiveMedia()" style="display:flex;align-items:center;gap:6px;font-size:12px;padding:7px 12px">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/></svg>
          <span>Refresh</span>
        </button>
      </div>
    </div>

    <!-- Content Body -->
    <div class="archive-modal-body">
      <!-- Loading State -->
      <div id="archiveLoading" class="archive-loading">
        <div class="archive-spinner"></div>
        <span>Memuat arsip media...</span>
      </div>

      <!-- Empty State -->
      <div id="archiveEmpty" class="archive-empty" style="display:none">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><polyline points="21 8 21 21 3 21 3 8"/><rect x="1" y="3" width="22" height="5"/><line x1="10" y1="12" x2="14" y2="12"/></svg>
        <strong>Arsip Media Kosong</strong>
        <span>Semua file media Stories saat ini sedang aktif atau belum ada media yang tergeser ke arsip.</span>
      </div>

      <!-- Grid Arsip -->
      <div id="archiveGrid" class="archive-grid"></div>
    </div>

    <!-- Footer -->
    <div style="padding:14px 24px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;background:var(--bg-card2)">
      <div style="font-size:12px;color:var(--text-muted)">
        Total <strong id="archiveTotalCount" style="color:var(--text-primary)">0</strong> file media terarsip.
      </div>
      <button type="button" class="btn btn-secondary btn-sm" onclick="closeArchiveModal()">Tutup</button>
    </div>
  </div>
</div>

<script>
let storiesData = [];
const MAX_CAPACITY = 21;
const CAN_CREATE = <?= json_encode($canCreate) ?>;
const CAN_EDIT   = <?= json_encode($canEdit) ?>;
const CAN_DELETE = <?= json_encode($canDelete) ?>;

async function loadStories() {
  const loading = document.getElementById('storiesLoading');
  const grid    = document.getElementById('storiesGrid');
  const empty   = document.getElementById('storiesEmpty');

  loading.style.display = 'block';
  grid.style.display    = 'none';
  empty.style.display   = 'none';

  try {
    const res = await fetch('/admin/api/stories.php?action=list');
    const d   = await res.json();
    if (d.success) {
      storiesData = d.items || [];
      renderStoriesGrid();
      updateCapacityUI();
    } else {
      toast('Gagal', d.error || 'Gagal memuat data stories', 'error');
    }
  } catch (err) {
    toast('Error', 'Gagal terhubung ke server', 'error');
  } finally {
    loading.style.display = 'none';
  }
}

function updateCapacityUI() {
  updateArchiveBadge();
}

function renderStoriesGrid() {
  const grid  = document.getElementById('storiesGrid');
  const empty = document.getElementById('storiesEmpty');

  if (!storiesData.length) {
    grid.style.display  = 'none';
    empty.style.display = 'block';
    return;
  }

  empty.style.display = 'none';
  grid.style.display  = 'grid';
  grid.innerHTML      = '';

  storiesData.forEach((item, index) => {
    const card = document.createElement('div');
    card.className = 'story-card';

    const isVideo = item.type === 'video';
    const isProcessing = isVideo && (item.video_status === 'processing' || !item.poster_url);
    const thumbSrc = isVideo ? (item.poster_url || item.url) : item.url;

    const thumbHtml = isProcessing
      ? `<div class="story-thumb-processing">
           <div class="story-thumb-spinner"></div>
           <div class="story-thumb-processing-text">Sedang Diproses di Cloud</div>
         </div>`
      : `<img src="${escHtml(thumbSrc)}" alt="${escHtml(item.file_name)}" loading="lazy" onerror="this.src='https://img.parokitulungagung.org/icon/icon_kronik.png'">`;

    card.innerHTML = `
      <div class="story-thumb-wrap${isProcessing ? ' is-processing' : ''}">
        ${thumbHtml}
        <div class="story-badge">
          ${isVideo ? '🎬 Video' : '📷 Foto'}
        </div>
        ${isProcessing ? `<div class="story-processing-badge">⏳ Processing</div>` : ''}
        <div class="story-order-badge">${index + 1}</div>
      </div>
      <div class="story-body">
        <div class="story-name" title="${escHtml(item.file_name)}">${escHtml(item.file_name)}</div>
        <div class="story-desc">${item.description ? escHtml(item.description) : '<i style="color:var(--text-muted)">Tanpa deskripsi</i>'}</div>
        <div class="story-footer">
          <span class="story-date">${item.created_at ? new Date(item.created_at).toLocaleDateString('id-ID', {day:'numeric',month:'short',year:'numeric'}) : ''}</span>
          <div class="story-actions">
            ${CAN_EDIT && index > 0 ? `<button class="btn btn-icon btn-sm" onclick="moveOrder(${index}, -1)" title="Geser ke kiri/naik">◀</button>` : ''}
            ${CAN_EDIT && index < storiesData.length - 1 ? `<button class="btn btn-icon btn-sm" onclick="moveOrder(${index}, 1)" title="Geser ke kanan/turun">▶</button>` : ''}
            ${CAN_EDIT ? `<button class="btn btn-icon btn-sm" onclick="openEditModal('${item.id}')" title="Edit">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            </button>` : ''}
            ${CAN_DELETE ? `<button class="btn btn-icon btn-sm" onclick="deleteStory('${item.id}', '${escHtml(item.file_name)}')" title="Hapus" style="color:var(--danger);border-color:rgba(239,68,68,0.25)">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a1 1 0 011-1h4a1 1 0 011 1v2"/></svg>
            </button>` : ''}
          </div>
        </div>
      </div>
    `;
    grid.appendChild(card);
  });
}

let selectedStoryFile = null;

function triggerFileInput() {
  document.getElementById('storyFileInput').click();
}

function onFilePicked(file) {
  if (!file) return;

  selectedStoryFile = file;

  // Auto fill nama file jika input Judul masih kosong
  const nameInput = document.getElementById('storyFileNameInput');
  if (nameInput && !nameInput.value.trim()) {
    const cleanName = file.name.replace(/\.[^/.]+$/, '').replace(/[_\-]/g, ' ');
    nameInput.value = cleanName;
  }

  // Tampilkan preview card file terpilih
  const previewCard = document.getElementById('selectedFilePreviewCard');
  const nameEl = document.getElementById('filePreviewName');
  const sizeEl = document.getElementById('filePreviewSize');
  const thumbEl = document.getElementById('filePreviewThumb');

  if (previewCard && nameEl && sizeEl && thumbEl) {
    nameEl.textContent = file.name;
    sizeEl.textContent = formatBytes(file.size) + ' • ' + (file.type.startsWith('video/') ? '🎬 Video' : '📷 Foto');

    thumbEl.innerHTML = '';
    if (file.type.startsWith('image/')) {
      const img = document.createElement('img');
      img.style.width = '100%';
      img.style.height = '100%';
      img.style.objectFit = 'cover';
      img.src = URL.createObjectURL(file);
      thumbEl.appendChild(img);
    } else {
      thumbEl.innerHTML = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polygon points="10 8 16 12 10 16 10 8"/></svg>';
    }
    previewCard.style.display = 'flex';
  }

  validateFormState();
}

function clearSelectedFile() {
  selectedStoryFile = null;
  const fileInput = document.getElementById('storyFileInput');
  if (fileInput) fileInput.value = '';

  const previewCard = document.getElementById('selectedFilePreviewCard');
  if (previewCard) previewCard.style.display = 'none';

  validateFormState();
}

function validateFormState() {
  const title = document.getElementById('storyFileNameInput')?.value.trim() || '';
  const desc  = document.getElementById('storyDescInput')?.value.trim() || '';
  const btn   = document.getElementById('btnPublishStory');

  if (btn) {
    const isValid = selectedStoryFile !== null && title !== '' && desc !== '';
    btn.style.opacity = isValid ? '1' : '0.65';
  }
}

function formatBytes(bytes) {
  if (!bytes) return '0 B';
  const k = 1024, sizes = ['B', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
}

const dz = document.getElementById('storiesDropzone');
if (dz) {
  dz.addEventListener('dragover', (e) => { e.preventDefault(); dz.classList.add('dragover'); });
  dz.addEventListener('dragleave', () => dz.classList.remove('dragover'));
  dz.addEventListener('drop', (e) => {
    e.preventDefault();
    dz.classList.remove('dragover');
    const file = e.dataTransfer.files[0];
    if (file) onFilePicked(file);
  });
}

async function submitStoryUpload() {
  if (!selectedStoryFile) {
    toast('File Belum Dipilih', 'Silakan pilih file foto atau video Stories terlebih dahulu.', 'warning');
    return;
  }

  const customName = document.getElementById('storyFileNameInput')?.value.trim() || '';
  const desc       = document.getElementById('storyDescInput')?.value.trim() || '';

  if (!customName) {
    toast('Judul Wajib Diisi', 'Silakan isi Judul / Nama Media terlebih dahulu.', 'warning');
    document.getElementById('storyFileNameInput')?.focus();
    return;
  }

  if (!desc) {
    toast('Deskripsi Wajib Diisi', 'Silakan isi Deskripsi Stories terlebih dahulu.', 'warning');
    document.getElementById('storyDescInput')?.focus();
    return;
  }

  const btn     = document.getElementById('btnPublishStory');
  const prog    = document.getElementById('storyUploadProgress');
  const bar     = document.getElementById('storyUploadBarFill');
  const percent = document.getElementById('storyUploadPercent');
  const status  = document.getElementById('storyUploadStatusLabel');

  if (btn) {
    btn.disabled = true;
    btn.innerHTML = '<span>Mempublikasikan...</span>';
  }

  prog.style.display  = 'block';
  bar.style.width     = '0%';
  percent.textContent = '0%';
  status.textContent  = `Mengunggah & mengoptimasi ${selectedStoryFile.name}...`;

  const formData = new FormData();
  formData.append('action', 'upload');
  formData.append('file', selectedStoryFile);
  formData.append('file_name', customName);
  formData.append('description', desc);

  const xhr = new XMLHttpRequest();
  xhr.open('POST', '/admin/api/stories.php');

  xhr.upload.onprogress = (e) => {
    if (e.lengthComputable) {
      const p = Math.round((e.loaded / e.total) * 85);
      bar.style.width = p + '%';
      percent.textContent = p + '%';
    }
  };

  xhr.onload = () => {
    bar.style.width = '100%';
    percent.textContent = '100%';
    try {
      const res = JSON.parse(xhr.responseText);
      if (res.success) {
        toast('Sukses', 'Stories berhasil dipublikasikan!', 'success');
        clearSelectedFile();
        if (document.getElementById('storyFileNameInput')) document.getElementById('storyFileNameInput').value = '';
        if (document.getElementById('storyDescInput')) document.getElementById('storyDescInput').value = '';
        loadStories();
      } else {
        toast('Gagal', res.error || 'Gagal mengunggah media', 'error');
      }
    } catch (e) {
      toast('Error', 'Gagal memproses response server', 'error');
    } finally {
      if (btn) {
        btn.disabled = false;
        btn.innerHTML = `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
          <polyline points="17 21 13 13 7 13 7 21"/>
          <polyline points="7 3 7 8 15 8"/>
        </svg><span>Publikasikan Stories</span>`;
      }
      setTimeout(() => { prog.style.display = 'none'; }, 1200);
    }
  };

  xhr.onerror = () => {
    toast('Error', 'Koneksi jaringan terputus saat upload', 'error');
    prog.style.display = 'none';
    if (btn) {
      btn.disabled = false;
      btn.innerHTML = `<span>Publikasikan Stories</span>`;
    }
  };

  xhr.send(formData);
}

function openEditModal(id) {
  const item = storiesData.find(s => s.id === id);
  if (!item) return;

  document.getElementById('editStoryId').value = item.id;
  document.getElementById('editStoryFileName').value = item.file_name || '';
  document.getElementById('editStoryDescription').value = item.description || '';

  const isVideo = item.type === 'video';
  const thumbSrc = isVideo ? (item.poster_url || item.url) : item.url;
  const imgEl = document.getElementById('editStoryPreviewImg');
  imgEl.src = thumbSrc || 'https://img.parokitulungagung.org/icon/icon_kronik.png';
  imgEl.onerror = () => { imgEl.src = 'https://img.parokitulungagung.org/icon/icon_kronik.png'; };

  const badgeEl = document.getElementById('editStoryBadgeType');
  badgeEl.textContent = isVideo ? '🎬 Video' : '📷 Foto';

  document.getElementById('editStoryMetaTitle').textContent = item.file_name || 'Tanpa Judul';
  document.getElementById('editStoryMetaDate').textContent = item.created_at
    ? 'Diupload: ' + new Date(item.created_at).toLocaleDateString('id-ID', {day:'numeric',month:'short',year:'numeric'})
    : '';

  const linkEl = document.getElementById('editStoryMediaLink');
  if (item.url) {
    linkEl.href = item.url;
    linkEl.style.display = 'inline-flex';
  } else {
    linkEl.style.display = 'none';
  }

  openModal('editStoryModal');
}

function closeEditModal() {
  closeModal('editStoryModal');
}

async function saveStoryEdit() {
  const id       = document.getElementById('editStoryId').value;
  const fileName = document.getElementById('editStoryFileName').value.trim();
  const desc     = document.getElementById('editStoryDescription').value.trim();
  const btn      = document.getElementById('btnSaveEdit');

  btn.disabled = true;
  btn.textContent = 'Menyimpan...';

  try {
    const res = await fetch('/admin/api/stories.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        action: 'update',
        id: id,
        file_name: fileName,
        description: desc
      })
    });
    const d = await res.json();
    if (d.success) {
      toast('Sukses', 'Data media berhasil diperbarui', 'success');
      closeEditModal();
      loadStories();
    } else {
      toast('Gagal', d.error || 'Gagal menyimpan perubahan', 'error');
    }
  } catch (e) {
    toast('Error', 'Gagal menyimpan data', 'error');
  } finally {
    btn.disabled = false;
    btn.textContent = 'Simpan Perubahan';
  }
}

async function deleteStory(id, name) {
  const ok = confirm(`⚠️ Perhatian!\n\nApakah Anda yakin ingin menghapus media "${name}"?\n\nFile foto/video ini akan dihapus secara permanen dari server dan website.`);
  if (!ok) return;

  try {
    const res = await fetch('/admin/api/stories.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'delete', id: id })
    });
    const d = await res.json();
    if (d.success) {
      toast('Terhapus', 'Media berhasil dihapus dari server dan sistem.', 'success');
      loadStories();
    } else {
      toast('Gagal', d.error || 'Gagal menghapus media', 'error');
    }
  } catch (e) {
    toast('Error', 'Gagal terhubung ke server', 'error');
  }
}

async function moveOrder(fromIdx, dir) {
  const toIdx = fromIdx + dir;
  if (toIdx < 0 || toIdx >= storiesData.length) return;

  const temp = storiesData[fromIdx];
  storiesData[fromIdx] = storiesData[toIdx];
  storiesData[toIdx] = temp;

  renderStoriesGrid();

  const idList = storiesData.map(s => s.id);
  try {
    await fetch('/admin/api/stories.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'reorder', ids: idList })
    });
  } catch (e) {
    console.error('Gagal sync order:', e);
  }
}

let archiveData = [];

async function updateArchiveBadge() {
  try {
    const res = await fetch('/admin/api/stories.php?action=archive');
    const d = await res.json();
    if (d.success) {
      archiveData = d.items || [];
      const badge = document.getElementById('headerArchiveCount');
      if (badge) badge.textContent = archiveData.length;
    }
  } catch (e) {
    console.error('Gagal memuat badge arsip:', e);
  }
}

function openArchiveModal() {
  const modal = document.getElementById('archiveModal');
  if (modal) {
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    loadArchiveMedia();
  }
}

function closeArchiveModal() {
  const modal = document.getElementById('archiveModal');
  if (modal) {
    modal.style.display = 'none';
    document.body.style.overflow = '';
  }
}

async function loadArchiveMedia() {
  const loading = document.getElementById('archiveLoading');
  const empty   = document.getElementById('archiveEmpty');
  const grid    = document.getElementById('archiveGrid');

  if (loading) loading.style.display = 'flex';
  if (empty)   empty.style.display   = 'none';
  if (grid)    grid.style.display    = 'none';

  try {
    const res = await fetch('/admin/api/stories.php?action=archive');
    const d   = await res.json();
    if (d.success) {
      archiveData = d.items || [];
      const badge = document.getElementById('headerArchiveCount');
      if (badge) badge.textContent = archiveData.length;
      renderArchiveGrid();
    } else {
      toast('Gagal', d.error || 'Gagal memuat arsip media', 'error');
    }
  } catch (err) {
    toast('Error', 'Gagal terhubung ke server', 'error');
  } finally {
    if (loading) loading.style.display = 'none';
  }
}

function filterArchiveGrid() {
  renderArchiveGrid();
}

function renderArchiveGrid() {
  const grid  = document.getElementById('archiveGrid');
  const empty = document.getElementById('archiveEmpty');
  const totalCountEl = document.getElementById('archiveTotalCount');

  const searchVal = (document.getElementById('archiveSearchInput')?.value || '').toLowerCase().trim();
  const typeVal   = document.getElementById('archiveTypeFilter')?.value || 'all';

  const filtered = archiveData.filter(item => {
    const matchesSearch = !searchVal || item.file_name.toLowerCase().includes(searchVal) || item.key.toLowerCase().includes(searchVal);
    const matchesType   = typeVal === 'all' || item.type === typeVal;
    return matchesSearch && matchesType;
  });

  if (totalCountEl) totalCountEl.textContent = archiveData.length;

  if (!filtered.length) {
    grid.style.display  = 'none';
    empty.style.display = 'block';
    return;
  }

  empty.style.display = 'none';
  grid.style.display  = 'grid';
  grid.innerHTML      = '';

  filtered.forEach(item => {
    const card = document.createElement('div');
    card.className = 'archive-card';

    const isVideo = item.type === 'video';
    const thumbHtml = isVideo
      ? (item.poster_url ? `<img src="${escHtml(item.poster_url)}" alt="${escHtml(item.file_name)}" loading="lazy">` : `<div style="display:flex;align-items:center;justify-content:center;height:100%;color:var(--text-muted)"><svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polygon points="10 8 16 12 10 16 10 8"/></svg></div>`)
      : `<img src="${escHtml(item.url)}" alt="${escHtml(item.file_name)}" loading="lazy">`;

    const formattedSize = formatBytes(item.size);
    const dateStr = item.last_modified ? new Date(item.last_modified).toLocaleDateString('id-ID', { day:'numeric', month:'short', year:'numeric' }) : '';

    card.innerHTML = `
      <div class="archive-thumb">
        ${thumbHtml}
        <div class="story-badge">
          ${isVideo ? '🎬 Video' : '📷 Foto'}
        </div>
      </div>
      <div class="archive-card-body">
        <div class="archive-card-name" title="${escHtml(item.file_name)}">${escHtml(item.file_name)}</div>
        <div class="archive-card-meta">${formattedSize} • ${dateStr}</div>
        <div class="archive-card-actions">
          <button type="button" class="archive-act-restore" onclick="restoreArchiveItem('${escHtml(item.key)}', '${escHtml(item.file_name)}')" title="Publikasikan Kembali ke Stories Utama">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
            <span>Restore</span>
          </button>
          <button type="button" class="archive-act-delete" onclick="deleteArchiveItem('${escHtml(item.key)}', '${escHtml(item.file_name)}')" title="Hapus Permanen">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
            <span>Hapus</span>
          </button>
        </div>
      </div>
    `;
    grid.appendChild(card);
  });
}

async function restoreArchiveItem(key, name) {
  const ok = confirm(`🚀 Publikasikan Kembali Stories?\n\nMedia "${name}" akan dimasukkan kembali ke daftar aktif Stories utama (posisi pertama).`);
  if (!ok) return;

  try {
    const res = await fetch('/admin/api/stories.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'restore_archive', key: key, file_name: name })
    });
    const d = await res.json();
    if (d.success) {
      toast('Berhasil Restorasi', `Media "${name}" dipublikasikan kembali ke Stories utama.`, 'success');
      loadArchiveMedia();
      loadStories();
    } else {
      toast('Gagal Restorasi', d.error || 'Gagal mempublikasikan media', 'error');
    }
  } catch (e) {
    toast('Error', 'Gagal terhubung ke server', 'error');
  }
}

async function deleteArchiveItem(key, name) {
  const ok = confirm(`⚠️ Hapus Permanen?\n\nFile media "${name}" akan dihapus PERMANEN dari penyimpanan arsip. Tindakan ini tidak dapat dibatalkan!`);
  if (!ok) return;

  try {
    const res = await fetch('/admin/api/stories.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'delete_archive', key: key })
    });
    const d = await res.json();
    if (d.success) {
      toast('Terhapus', `File media "${name}" terhapus permanen.`, 'success');
      loadArchiveMedia();
    } else {
      toast('Gagal', d.error || 'Gagal menghapus file arsip', 'error');
    }
  } catch (e) {
    toast('Error', 'Gagal terhubung ke server', 'error');
  }
}

function escHtml(s) {
  return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

document.addEventListener('DOMContentLoaded', loadStories);
</script>

<?php adminFooter(); ?>
