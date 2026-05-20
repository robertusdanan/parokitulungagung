/**
 * admin/js/foto-picker.js
 * Komponen reusable: picker foto dari img/person + upload foto baru
 * Dipakai oleh: wilayah.php, asisten_imam.php, dpp_bgkp.php, master/*.php
 *
 * Cara pakai:
 *   FotoPicker.init(targetInputId, previewImgId, options)
 *   FotoPicker.open(targetInputId)  → buka modal picker
 *
 * targetInputId : id dari <input type="hidden"> yang menyimpan nama file
 * previewImgId  : id dari <img> yang menampilkan preview
 */

window.FotoPicker = (function () {
  // Daftar semua foto di /img/person/
  const PERSON_PHOTOS = [
    "Aan-Yudhi-Harianto","Adrianus-Tri-Hardianto","Agustinus-Ambrosius-Gatot-Muryoto",
    "Agustinus-Yosafat-Suryanto","Albertus-Sujiwa","Albertus-Untung-Eko-Prabowo",
    "Aloysius-Rudy-Hantanto","Amadeus-Egar","Anastasia-Augustine-Siswandari",
    "Andreas-Andrie-Djatmiko","Antonia-Bambang-Puspitasari","Antonius-Hadi-Handoko",
    "Antonius-Nuryanto-Sudiarto","Antonius-Valentinus-Soni-Harsono","Ayuning-Tyas-Candra-Dewi",
    "Basilius-Yustianus-Bedi","Bernardus-Didik-Sumarsono","Bintoro",
    "Bonaventura-Pikir-Djoko-Sri-Mulyo","Bonifasius-Bambang-Tjahjo-Adiwijono",
    "CH-Erma-Aris-Sulistiowati","Chatarina-Feliyana-Saridewi","Didit-Setiawan",
    "Dominika-Apriliya-Kristiani","Dominikus-Dimas-Pamungkas","Ernesta-Maria",
    "Filipus-Nerius-Duali-Suprih-Adi","Firman-Handoko","Franciscus-Xaverius-Bambang-Kumbayana",
    "Fransisca-Romana-Karmi","Fransiska-Sri-Maryanti","Fredy-Wibowo",
    "Heribertus-Tarmoko","Herman-Yoseph-Agung-Effrianto","Hieronimus-Haryono",
    "Hubertus-Ratijo","Ir-Yosef-Sutrisno","James-Agus-Wahyudi",
    "Johanes-Pembaptis-Sunarto","Jovita-Sudarti","Kamillus-Sukamto",
    "Katarina-Diyah-Prastiwi","Katarina-Dyah-Paskasita","Lazarus-Krisnugroho",
    "Lucia-Eva-Perwiratri","Lucia-Tri-Muljati","Marc-Danu-Saksono",
    "Margaretha-Dewi-Sari-Mulia","Maria-Goretty-Dewi-Anjani","Maria-Stephanie-Kiem-Lie",
    "Maria-Theresia-Nurjati-Watyaningsih","Marianus-Ari-Wijaya","Martinus-Sunaryo",
    "Matius-Surahmad","Monica-Lilik-Meliana","P-Yudiyanto","PC-Priyatni",
    "RD-Thomas-Aquino-Djoko-Noegroho","RD-Yohanes-Setiawan","Robertus-Dananhadi-Pamungkas",
    "Stefanus-Felix-Hendra-Gunawan","Stefanus-Iba-Pantyas-Towo","Stefanus-Jimmy",
    "Stefanus-Triadi-Atmono","Stephanus-Budi-Purnomo","Susilorini",
    "Theresia-Marlina-Wijayanti","Theresia-Muryati","Thomas-Antonius-Anom-Tjaroko",
    "Timotheus-Elfin-Widijatmoko","Ubertus-Ratijo","Valentinus-Buku",
    "Vincentia-Sunarlin","Vincentius-Bonaventura-Rudy-Widjaja","Vincentius-Pairin",
    "Y-Heny-Supriyanti","Yanuarius-Budi-Ernawan","Yohanes-Bagus-Kuncoro",
    "Yohanes-Rasul-Hariyadi","Yohanes-Tri-Cahyono","Yosef-Tas-Au",
    "Yuliana-Asterina","Yuliana-Endyah-Retnowati","Yulius-Ignatius-Salamun",
    "Yustinus-Edi-Wandowo"
  ];

  let _targetInputId  = null;
  let _previewImgId   = null;
  let _uploadCallback = null;
  let _modalEl        = null;
  let _filtered       = [...PERSON_PHOTOS];

  // ── Inject modal HTML + CSS sekali saja ──────────────────────────────
  function _ensureModal() {
    if (document.getElementById('fotoPickerModal')) return;

    const css = `
<style id="fotoPickerCSS">
.fp-overlay{position:fixed;inset:0;background:rgba(0,0,0,.75);backdrop-filter:blur(4px);z-index:9000;display:flex;align-items:center;justify-content:center;padding:16px;opacity:0;visibility:hidden;transition:all .2s}
.fp-overlay.open{opacity:1;visibility:visible}
.fp-modal{background:var(--bg-card);border:1px solid var(--border);border-radius:14px;width:100%;max-width:680px;max-height:88vh;display:flex;flex-direction:column;overflow:hidden;transform:translateY(16px);transition:transform .25s cubic-bezier(.4,0,.2,1)}
.fp-overlay.open .fp-modal{transform:translateY(0)}
.fp-header{padding:18px 20px 14px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:12px;flex-shrink:0}
.fp-header h3{font-family:'Playfair Display',serif;font-size:16px;font-weight:600;flex:1;margin:0}
.fp-close{background:none;border:none;color:var(--text-muted);font-size:22px;cursor:pointer;line-height:1;padding:4px;transition:color .15s}
.fp-close:hover{color:var(--text-primary)}
.fp-tabs{display:flex;gap:2px;padding:12px 20px 0;border-bottom:1px solid var(--border);flex-shrink:0}
.fp-tab{padding:8px 16px;background:none;border:none;border-bottom:2px solid transparent;color:var(--text-secondary);font-family:'DM Sans',sans-serif;font-size:13px;font-weight:500;cursor:pointer;transition:all .15s;margin-bottom:-1px}
.fp-tab.active{color:var(--accent);border-bottom-color:var(--accent)}
.fp-tab:hover:not(.active){color:var(--text-primary)}
.fp-body{flex:1;overflow:hidden;display:flex;flex-direction:column}
.fp-panel{display:none;flex-direction:column;flex:1;overflow:hidden}
.fp-panel.active{display:flex}

/* Panel: Pilih dari Galeri */
.fp-search-wrap{padding:14px 20px 10px;flex-shrink:0;position:relative}
.fp-search-wrap input{width:100%;background:var(--bg-input);border:1px solid var(--border);border-radius:8px;color:var(--text-primary);font-size:13.5px;padding:8px 12px 8px 36px;outline:none;transition:border-color .15s}
.fp-search-wrap input:focus{border-color:var(--border-focus);box-shadow:0 0 0 3px rgba(201,168,76,.08)}
.fp-search-wrap svg{position:absolute;left:30px;top:50%;transform:translateY(-50%);color:var(--text-muted);pointer-events:none}
.fp-count{font-size:11.5px;color:var(--text-muted);padding:0 20px 8px;flex-shrink:0}
.fp-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(100px,1fr));gap:8px;padding:4px 20px 16px;overflow-y:auto;flex:1}
.fp-item{display:flex;flex-direction:column;align-items:center;gap:6px;padding:8px 6px;border-radius:8px;cursor:pointer;border:2px solid transparent;transition:all .15s;background:var(--bg-card2)}
.fp-item:hover{border-color:rgba(201,168,76,.4);background:var(--accent-dim)}
.fp-item.selected{border-color:var(--accent);background:var(--accent-dim)}
.fp-item img{width:60px;height:60px;object-fit:cover;border-radius:50%;border:2px solid var(--border)}
.fp-item.selected img{border-color:var(--accent)}
.fp-item-name{font-size:10px;color:var(--text-secondary);text-align:center;line-height:1.3;word-break:break-word;max-width:90px}
.fp-item.selected .fp-item-name{color:var(--accent)}
.fp-empty{text-align:center;padding:40px 20px;color:var(--text-muted);font-size:13px}

/* Panel: Upload */
.fp-upload-area{margin:16px 20px;border:2px dashed var(--border);border-radius:10px;padding:28px 20px;text-align:center;cursor:pointer;transition:all .2s}
.fp-upload-area:hover,.fp-upload-area.drag{border-color:var(--accent);background:var(--accent-dim)}
.fp-upload-area p{font-size:13px;color:var(--text-secondary);margin:8px 0 0}
.fp-upload-area small{font-size:11px;color:var(--text-muted)}
.fp-upload-preview{margin:0 20px 12px;display:none;align-items:center;gap:12px;background:var(--bg-card2);border:1px solid var(--border);border-radius:8px;padding:10px 14px}
.fp-upload-preview img{width:56px;height:56px;object-fit:cover;border-radius:50%;border:2px solid var(--border)}
.fp-upload-progress{height:3px;background:var(--border);border-radius:2px;overflow:hidden;margin:0 20px 8px}
.fp-upload-progress-bar{height:100%;background:var(--accent);width:0%;transition:width .3s}
.fp-upload-status{font-size:12px;color:var(--text-muted);padding:0 20px 8px;min-height:18px}

/* Footer */
.fp-footer{padding:12px 20px 16px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;flex-shrink:0;gap:8px}
.fp-selected-preview{display:flex;align-items:center;gap:8px;font-size:12.5px;color:var(--text-secondary)}
.fp-selected-preview img{width:32px;height:32px;object-fit:cover;border-radius:50%;border:1.5px solid var(--accent)}
.fp-selected-name{color:var(--text-primary);font-weight:500}
.fp-no-selection{font-size:12px;color:var(--text-muted);font-style:italic}
.fp-actions{display:flex;gap:8px}
@media(max-width:600px){.fp-grid{grid-template-columns:repeat(auto-fill,minmax(80px,1fr))}.fp-item img{width:48px;height:48px}}
</style>`;

    const html = `
${css}
<div class="fp-overlay" id="fotoPickerModal">
  <div class="fp-modal">
    <div class="fp-header">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="20" height="20" style="color:var(--accent);flex-shrink:0"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
      <h3>Pilih Foto</h3>
      <button class="fp-close" onclick="FotoPicker.close()">&times;</button>
    </div>
    <div class="fp-tabs">
      <button class="fp-tab active" onclick="FotoPicker.switchTab('gallery')" id="fpTabGallery">
        📁 Dari Galeri (<?= count_or_86 ?>)
      </button>
      <button class="fp-tab" onclick="FotoPicker.switchTab('upload')" id="fpTabUpload">
        ⬆️ Upload Foto Baru
      </button>
    </div>
    <div class="fp-body">

      <!-- Tab Gallery -->
      <div class="fp-panel active" id="fpPanelGallery">
        <div class="fp-search-wrap">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          <input type="text" id="fpSearchInput" placeholder="Cari nama..." oninput="FotoPicker.search(this.value)">
        </div>
        <div class="fp-count" id="fpCount">86 foto tersedia</div>
        <div class="fp-grid" id="fpGrid"></div>
      </div>

      <!-- Tab Upload -->
      <div class="fp-panel" id="fpPanelUpload">
        <div class="fp-upload-area" id="fpDropArea"
             onclick="document.getElementById('fpFileInput').click()"
             ondragover="FotoPicker._onDragOver(event)"
             ondragleave="FotoPicker._onDragLeave(event)"
             ondrop="FotoPicker._onDrop(event)">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="40" height="40" style="color:var(--text-muted);margin:0 auto"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
          <p>Klik atau seret foto ke sini</p>
          <small>JPG/PNG/WebP · Maks 20MB · Dikompres otomatis</small>
        </div>
        <input type="file" id="fpFileInput" accept="image/jpeg,image/png,image/webp" style="display:none" onchange="FotoPicker._onFileSelected(this)">
        <div class="fp-upload-preview" id="fpUploadPreview">
          <img id="fpUploadPreviewImg" src="" alt="">
          <div>
            <div id="fpUploadPreviewName" style="font-size:13px;font-weight:500;color:var(--text-primary)"></div>
            <div id="fpUploadPreviewSize" style="font-size:11.5px;color:var(--text-muted)"></div>
          </div>
        </div>
        <div class="fp-upload-progress" id="fpUploadProgressWrap" style="display:none">
          <div class="fp-upload-progress-bar" id="fpUploadProgressBar"></div>
        </div>
        <div class="fp-upload-status" id="fpUploadStatus"></div>
      </div>
    </div>

    <div class="fp-footer">
      <div id="fpFooterPreview">
        <div class="fp-no-selection">Belum ada foto dipilih</div>
      </div>
      <div class="fp-actions">
        <button class="btn btn-secondary btn-sm" onclick="FotoPicker.clearSelection()">Hapus Foto</button>
        <button class="btn btn-primary btn-sm" id="fpBtnConfirm" onclick="FotoPicker.confirm()" disabled>Pilih Foto Ini</button>
      </div>
    </div>
  </div>
</div>`;

    document.body.insertAdjacentHTML('beforeend', html);
    _modalEl = document.getElementById('fotoPickerModal');

    // Close on overlay click
    _modalEl.addEventListener('click', function(e) {
      if (e.target === _modalEl) FotoPicker.close();
    });
  }

  // ── Render grid foto ─────────────────────────────────────────────────
  function _renderGrid(list) {
    const grid = document.getElementById('fpGrid');
    const count = document.getElementById('fpCount');
    if (!grid) return;
    if (!list.length) {
      grid.innerHTML = '<div class="fp-empty">Tidak ada foto yang cocok</div>';
      if (count) count.textContent = '0 foto';
      return;
    }
    if (count) count.textContent = list.length + ' foto tersedia';
    grid.innerHTML = list.map(name => {
      const src   = '/img/person/' + name + '.webp';
      const label = name.replace(/-/g, ' ');
      return `<div class="fp-item" data-name="${name}" onclick="FotoPicker.selectFromGallery('${name}')">
        <img src="${src}" alt="${label}" loading="lazy" onerror="this.src='/img/icon/icon_romo.png'">
        <span class="fp-item-name">${label}</span>
      </div>`;
    }).join('');

    // Highlight yg sudah dipilih
    if (_currentSelection && _currentSelection.source === 'gallery') {
      const el = grid.querySelector(`[data-name="${_currentSelection.filename.replace('.webp','')}"]`);
      if (el) el.classList.add('selected');
    }
  }

  // ── State ────────────────────────────────────────────────────────────
  let _currentSelection = null; // { filename, displayName, previewUrl, source }
  let _activeTab        = 'gallery';
  let _uploadedResult   = null; // hasil upload: { filename, url }

  function _updateFooter() {
    const preview = document.getElementById('fpFooterPreview');
    const btn     = document.getElementById('fpBtnConfirm');
    if (!preview) return;
    if (_currentSelection) {
      preview.innerHTML = `<div class="fp-selected-preview">
        <img src="${_currentSelection.previewUrl}" alt="" onerror="this.src='/img/icon/icon_romo.png'">
        <span class="fp-selected-name">${_currentSelection.displayName}</span>
      </div>`;
      if (btn) btn.disabled = false;
    } else {
      preview.innerHTML = '<div class="fp-no-selection">Belum ada foto dipilih</div>';
      if (btn) btn.disabled = true;
    }
  }

  // ── Public API ───────────────────────────────────────────────────────
  return {
    init: function(targetInputId, previewImgId, uploadCallback) {
      _targetInputId  = targetInputId;
      _previewImgId   = previewImgId;
      _uploadCallback = uploadCallback || null;
      _ensureModal();
    },

    open: function(targetInputId, previewImgId, uploadCallback) {
      if (targetInputId) _targetInputId = targetInputId;
      if (previewImgId)  _previewImgId  = previewImgId;
      if (uploadCallback) _uploadCallback = uploadCallback;
      _ensureModal();
      _currentSelection = null;
      _uploadedResult   = null;

      // Cek nilai yg sudah ada
      const inputEl = document.getElementById(_targetInputId);
      if (inputEl && inputEl.value) {
        const existing = inputEl.value;
        const name = existing.replace('.webp','').replace(/^.*\//,'');
        const isPersonPhoto = PERSON_PHOTOS.includes(name);
        if (isPersonPhoto) {
          _currentSelection = {
            filename: name + '.webp',
            displayName: name.replace(/-/g,' '),
            previewUrl: '/img/person/' + name + '.webp',
            source: 'gallery'
          };
        } else if (existing) {
          _currentSelection = {
            filename: existing,
            displayName: existing.replace(/^.*\//,'').replace('.webp','').replace(/-/g,' '),
            previewUrl: '/img/person/' + existing,
            source: 'existing'
          };
        }
      }

      // Reset search
      const searchEl = document.getElementById('fpSearchInput');
      if (searchEl) searchEl.value = '';
      _filtered = [...PERSON_PHOTOS];
      _renderGrid(_filtered);
      _updateFooter();

      // Reset upload panel
      document.getElementById('fpUploadPreview').style.display = 'none';
      document.getElementById('fpUploadStatus').textContent = '';
      document.getElementById('fpUploadProgressWrap').style.display = 'none';

      // Buka modal
      _modalEl.classList.add('open');
      document.body.style.overflow = 'hidden';
      FotoPicker.switchTab('gallery');
    },

    close: function() {
      if (_modalEl) _modalEl.classList.remove('open');
      document.body.style.overflow = '';
    },

    switchTab: function(tab) {
      _activeTab = tab;
      document.getElementById('fpPanelGallery').classList.toggle('active', tab === 'gallery');
      document.getElementById('fpPanelUpload').classList.toggle('active', tab === 'upload');
      document.getElementById('fpTabGallery').classList.toggle('active', tab === 'gallery');
      document.getElementById('fpTabUpload').classList.toggle('active', tab === 'upload');
    },

    search: function(q) {
      q = q.toLowerCase().replace(/-/g,' ');
      _filtered = q
        ? PERSON_PHOTOS.filter(n => n.toLowerCase().replace(/-/g,' ').includes(q))
        : [...PERSON_PHOTOS];
      _renderGrid(_filtered);
    },

    selectFromGallery: function(name) {
      _currentSelection = {
        filename: name + '.webp',
        displayName: name.replace(/-/g, ' '),
        previewUrl: '/img/person/' + name + '.webp',
        source: 'gallery'
      };
      // Visual: deselect all, select this
      document.querySelectorAll('.fp-item').forEach(el => el.classList.remove('selected'));
      const el = document.querySelector(`.fp-item[data-name="${name}"]`);
      if (el) {
        el.classList.add('selected');
        el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
      }
      _updateFooter();
    },

    clearSelection: function() {
      _currentSelection = null;
      document.querySelectorAll('.fp-item').forEach(el => el.classList.remove('selected'));
      // Clear target
      const inputEl = document.getElementById(_targetInputId);
      const imgEl   = document.getElementById(_previewImgId);
      if (inputEl) inputEl.value = '';
      if (imgEl) {
        imgEl.src = '';
        imgEl.style.display = 'none';
        const parent = imgEl.closest('.fp-preview-wrap');
        if (parent) parent.querySelector('.fp-placeholder') && (parent.querySelector('.fp-placeholder').style.display = 'flex');
      }
      if (_uploadCallback) _uploadCallback('', '');
      _updateFooter();
      FotoPicker.close();
    },

    confirm: function() {
      if (!_currentSelection) return;
      const inputEl = document.getElementById(_targetInputId);
      const imgEl   = document.getElementById(_previewImgId);
      if (inputEl) inputEl.value = _currentSelection.filename;
      if (imgEl) {
        imgEl.src = _currentSelection.previewUrl;
        imgEl.style.display = 'block';
      }
      if (_uploadCallback) _uploadCallback(_currentSelection.filename, _currentSelection.previewUrl);
      FotoPicker.close();
    },

    // ── Upload handlers ─────────────────────────────────────────────
    _onDragOver: function(e) {
      e.preventDefault();
      document.getElementById('fpDropArea').classList.add('drag');
    },
    _onDragLeave: function() {
      document.getElementById('fpDropArea').classList.remove('drag');
    },
    _onDrop: function(e) {
      e.preventDefault();
      document.getElementById('fpDropArea').classList.remove('drag');
      const file = e.dataTransfer.files[0];
      if (file) FotoPicker._processUpload(file);
    },
    _onFileSelected: function(input) {
      const file = input.files[0];
      if (file) FotoPicker._processUpload(file);
      input.value = '';
    },
    _processUpload: async function(file) {
      const statusEl   = document.getElementById('fpUploadStatus');
      const progressWr = document.getElementById('fpUploadProgressWrap');
      const progressBr = document.getElementById('fpUploadProgressBar');
      const previewWr  = document.getElementById('fpUploadPreview');
      const previewImg = document.getElementById('fpUploadPreviewImg');
      const previewNm  = document.getElementById('fpUploadPreviewName');
      const previewSz  = document.getElementById('fpUploadPreviewSize');

      if (file.size > 20 * 1024 * 1024) {
        statusEl.textContent = '⚠ File terlalu besar (maks 20MB)';
        statusEl.style.color = 'var(--danger)';
        return;
      }
      if (!['image/jpeg','image/png','image/webp'].includes(file.type)) {
        statusEl.textContent = '⚠ Format tidak didukung (JPG/PNG/WebP)';
        statusEl.style.color = 'var(--danger)';
        return;
      }

      // Tampilkan preview lokal dulu
      const localUrl = URL.createObjectURL(file);
      previewImg.src = localUrl;
      previewNm.textContent = file.name;
      previewSz.textContent = (file.size/1024).toFixed(1) + ' KB (original)';
      previewWr.style.display = 'flex';

      statusEl.textContent = 'Mengupload dan mengompres...';
      statusEl.style.color = 'var(--text-muted)';
      progressWr.style.display = 'block';
      progressBr.style.width   = '30%';

      const formData = new FormData();
      formData.append('image', file);

      try {
        progressBr.style.width = '60%';
        const res  = await fetch('/admin/api/upload_person.php', { method: 'POST', body: formData });
        progressBr.style.width = '90%';
        const data = await res.json();
        progressBr.style.width = '100%';

        if (data.success) {
          statusEl.textContent = `✓ Berhasil! ${data.size_kb}KB · ${data.dimensions}`;
          statusEl.style.color = 'var(--success)';
          previewSz.textContent = `${data.orig_kb}KB → ${data.size_kb}KB (${data.saved_pct}% lebih kecil)`;
          previewImg.src = data.url;

          _currentSelection = {
            filename: data.filename,
            displayName: data.filename.replace('.webp','').replace(/-/g,' '),
            previewUrl: data.url,
            source: 'upload'
          };
          _updateFooter();
        } else {
          statusEl.textContent = '✗ ' + (data.error || 'Upload gagal');
          statusEl.style.color = 'var(--danger)';
          progressBr.style.width = '0%';
        }
      } catch(err) {
        statusEl.textContent = '✗ Gagal menghubungi server';
        statusEl.style.color = 'var(--danger)';
        progressBr.style.width = '0%';
      }
    }
  };
})();