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
    <p>Kelola konten Stories visual (foto &amp; video) yang ditampilkan secara dinamis di website (Maks. 21 media).</p>
  </div>
</div>

<!-- Kapasitas & Status -->
<div class="stories-capacity-card">
  <div class="stories-cap-info">
    <div class="stories-cap-icon">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="12" cy="12" r="10"/><polygon points="10 8 16 12 10 16 10 8"/>
      </svg>
    </div>
    <div>
      <div class="stories-cap-title">Kapasitas Stories</div>
      <div class="stories-cap-sub" id="capText">Memuat data stories...</div>
    </div>
  </div>

  <div class="stories-cap-meter">
    <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--text-secondary)">
      <span>Terisi</span>
      <strong id="capRatio">0 / 21</strong>
    </div>
    <div class="stories-bar-wrap">
      <div class="stories-bar-fill" id="capBarFill"></div>
    </div>
  </div>
</div>

<!-- Upload Section -->
<?php if ($canCreate): ?>
<div class="stories-upload-card" id="uploadSectionCard">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px">
    <strong style="font-size:14px;color:var(--text-primary);display:flex;align-items:center;gap:6px">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
      Upload Foto / Video Stories
    </strong>
    <span style="font-size:12px;color:var(--text-muted)">Foto otomatis dioptimasi WebP, video dikompresi otomatis</span>
  </div>

  <div id="quotaWarningBanner" style="display:none;padding:12px 14px;background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);border-radius:8px;font-size:12.5px;color:var(--danger);margin-bottom:14px">
    ⚠️ <b>Kapasitas Penuh (21/21 Media).</b> Untuk mengunggah media baru, silakan hapus salah satu media yang sudah ada di bawah terlebih dahulu.
  </div>

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px" id="uploadInputsRow">
    <div>
      <label class="form-label" style="font-size:12.5px;font-weight:600">Nama File (Opsional)</label>
      <input type="text" id="storyFileNameInput" class="form-control" placeholder="Contoh: Misa Paskah 2026 (kosongkan untuk default nama asli)">
      <small style="color:var(--text-muted);font-size:11px">Jika diisi, akan menjadi nama file yang terupload.</small>
    </div>
    <div>
      <label class="form-label" style="font-size:12.5px;font-weight:600">Deskripsi (Opsional)</label>
      <input type="text" id="storyDescInput" class="form-control" placeholder="Deskripsi singkat yang tampil saat media diklik...">
      <small style="color:var(--text-muted);font-size:11px">Akan muncul di bagian bawah modal card.</small>
    </div>
  </div>

  <div class="stories-upload-zone" id="storiesDropzone" onclick="triggerFileInput()">
    <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="color:var(--text-muted);margin:0 auto 8px">
      <path d="M4 14.899A7 7 0 1 1 15.71 8h1.79a4.5 4.5 0 0 1 2.5 8.242"/><path d="M12 12v9"/><path d="m16 16-4-4-4 4"/>
    </svg>
    <p style="font-size:13.5px;font-weight:600;color:var(--text-primary);margin:0 0 4px">Seret file foto atau video ke sini, atau klik untuk memilih file</p>
    <p style="font-size:12px;color:var(--text-muted);margin:0">Mendukung Foto (JPG, PNG, WebP) &amp; Video (MP4, MOV, MKV, WebM)</p>
    <input type="file" id="storyFileInput" accept="image/*,video/*" style="display:none" onchange="handleFileSelected(this.files[0])">
  </div>

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

<!-- Modal Edit Story -->
<div class="modal" id="editStoryModal" style="display:none">
  <div class="modal-backdrop" onclick="closeEditModal()"></div>
  <div class="modal-dialog" style="max-width:480px">
    <div class="modal-header">
      <h3 style="font-size:15px;margin:0">Edit Media Stories</h3>
      <button type="button" class="modal-close" onclick="closeEditModal()">✕</button>
    </div>
    <div class="modal-body" style="padding:16px 20px">
      <input type="hidden" id="editStoryId">
      <div style="margin-bottom:14px">
        <label class="form-label" style="font-size:12.5px;font-weight:600">Nama File / Judul</label>
        <input type="text" id="editStoryFileName" class="form-control" placeholder="Nama file / judul media">
      </div>
      <div style="margin-bottom:14px">
        <label class="form-label" style="font-size:12.5px;font-weight:600">Deskripsi</label>
        <textarea id="editStoryDescription" class="form-control" rows="4" placeholder="Tulis deskripsi media di sini..."></textarea>
      </div>
    </div>
    <div class="modal-footer" style="padding:12px 20px;display:flex;justify-content:flex-end;gap:10px">
      <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Batal</button>
      <button type="button" class="btn btn-primary" id="btnSaveEdit" onclick="saveStoryEdit()">Simpan Perubahan</button>
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
  const count = storiesData.length;
  const ratio = `${count} / ${MAX_CAPACITY}`;
  const pct   = Math.min(100, Math.round((count / MAX_CAPACITY) * 100));

  document.getElementById('capRatio').textContent = ratio;
  document.getElementById('capText').textContent = count >= MAX_CAPACITY
    ? 'Kapasitas penuh (21 media). Hapus media untuk menambah baru.'
    : `Tersedia sisa ruang untuk ${MAX_CAPACITY - count} media lagi.`;

  const fill = document.getElementById('capBarFill');
  fill.style.width = pct + '%';
  if (count >= MAX_CAPACITY) {
    fill.classList.add('full');
  } else {
    fill.classList.remove('full');
  }

  const dropzone = document.getElementById('storiesDropzone');
  const banner   = document.getElementById('quotaWarningBanner');
  const inputsRow= document.getElementById('uploadInputsRow');

  if (dropzone && banner) {
    if (count >= MAX_CAPACITY) {
      dropzone.classList.add('disabled');
      banner.style.display = 'block';
      if (inputsRow) inputsRow.style.opacity = '0.5';
    } else {
      dropzone.classList.remove('disabled');
      banner.style.display = 'none';
      if (inputsRow) inputsRow.style.opacity = '1';
    }
  }
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

function triggerFileInput() {
  if (storiesData.length >= MAX_CAPACITY) {
    toast('Kapasitas Penuh', 'Kapasitas maksimal 21 media telah tercapai. Hapus salah satu media terlebih dahulu.', 'warning');
    return;
  }
  document.getElementById('storyFileInput').click();
}

const dz = document.getElementById('storiesDropzone');
if (dz) {
  dz.addEventListener('dragover', (e) => { e.preventDefault(); dz.classList.add('dragover'); });
  dz.addEventListener('dragleave', () => dz.classList.remove('dragover'));
  dz.addEventListener('drop', (e) => {
    e.preventDefault();
    dz.classList.remove('dragover');
    if (storiesData.length >= MAX_CAPACITY) {
      toast('Kapasitas Penuh', 'Kapasitas maksimal 21 media telah tercapai.', 'warning');
      return;
    }
    const file = e.dataTransfer.files[0];
    if (file) handleFileSelected(file);
  });
}

async function handleFileSelected(file) {
  if (!file) return;
  if (storiesData.length >= MAX_CAPACITY) {
    toast('Kapasitas Penuh', 'Kapasitas maksimal 21 media telah tercapai.', 'warning');
    return;
  }

  const prog    = document.getElementById('storyUploadProgress');
  const bar     = document.getElementById('storyUploadBarFill');
  const percent = document.getElementById('storyUploadPercent');
  const status  = document.getElementById('storyUploadStatusLabel');

  prog.style.display  = 'block';
  bar.style.width     = '0%';
  percent.textContent = '0%';
  status.textContent  = `Mengunggah & mengoptimasi ${file.name}...`;

  const customName = document.getElementById('storyFileNameInput')?.value || '';
  const desc       = document.getElementById('storyDescInput')?.value || '';

  const formData = new FormData();
  formData.append('action', 'upload');
  formData.append('file', file);
  formData.append('file_name', customName);
  formData.append('description', desc);

  const xhr = new XMLHttpRequest();
  xhr.open('POST', '/admin/api/stories.php');

  xhr.upload.onprogress = (e) => {
    if (e.lengthComputable) {
      const p = Math.round((e.loaded / e.total) * 85); // 85% untuk upload, sisa untuk proses cloud
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
        toast('Sukses', 'Media berhasil diunggah dan disimpan!', 'success');
        if (document.getElementById('storyFileNameInput')) document.getElementById('storyFileNameInput').value = '';
        if (document.getElementById('storyDescInput')) document.getElementById('storyDescInput').value = '';
        document.getElementById('storyFileInput').value = '';
        loadStories();
      } else {
        toast('Gagal', res.error || 'Gagal mengunggah media', 'error');
      }
    } catch (e) {
      toast('Error', 'Gagal memproses response server', 'error');
    } finally {
      setTimeout(() => { prog.style.display = 'none'; }, 1200);
    }
  };

  xhr.onerror = () => {
    toast('Error', 'Koneksi jaringan terputus saat upload', 'error');
    prog.style.display = 'none';
  };

  xhr.send(formData);
}

function openEditModal(id) {
  const item = storiesData.find(s => s.id === id);
  if (!item) return;

  document.getElementById('editStoryId').value = item.id;
  document.getElementById('editStoryFileName').value = item.file_name || '';
  document.getElementById('editStoryDescription').value = item.description || '';

  document.getElementById('editStoryModal').style.display = 'flex';
}

function closeEditModal() {
  document.getElementById('editStoryModal').style.display = 'none';
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

function escHtml(s) {
  return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

document.addEventListener('DOMContentLoaded', loadStories);
</script>

<?php adminFooter(); ?>
