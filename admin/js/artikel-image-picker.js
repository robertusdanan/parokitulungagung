/**
 * admin/js/artikel-image-picker.js
 * Komponen picker gambar untuk halaman editor artikel.
 *
 * Fitur:
 *   - Tab "Galeri" : browse & pilih gambar dari /img/artikel/
 *   - Tab "Upload" : upload file baru → auto-kompres via api/upload_artikel.php
 *
 * [BARU] Fitur Pemilih Ukuran:
 *   - Selector orientasi (Landscape / Square / Portrait) + kartu preset ukuran
 *   - Simulasi preview real-time tampilan gambar di artikel sebelum upload
 *   - Parameter size_preset dikirim ke upload_artikel.php
 *   - _lastPreset expose preset terpilih → dibaca artikel-editor.php
 *
 * Cara pakai:
 *   ArtikelImagePicker.open({
 *     type      : 'thumbnail' | 'content',
 *     judul     : 'Judul Artikel...',
 *     onSelect  : function(url, filename, alt, ogUrl) {}
 *   })
 */

window.ArtikelImagePicker = (function () {

  let _opts     = {};
  let _overlay  = null;
  let _allFiles = [];
  let _filtered = [];

  // ── Daftar preset ukuran ─────────────────────────────────────────────
  // mode 'fit'  = resize proporsional masuk batas (tidak crop, tidak distorsi)
  // mode 'crop' = crop tengah ke dimensi TEPAT
  const SIZE_PRESETS = {
    content: [
      { key:'landscape_wide',   orient:'landscape', label:'Landscape Lebar',   ratio:'16:9',  w:960, h:540, mode:'fit',  q:78, desc:'Foto kegiatan, pemandangan' },
      { key:'landscape_medium', orient:'landscape', label:'Landscape Sedang',  ratio:'16:9',  w:720, h:405, mode:'fit',  q:78, desc:'Lebih compact, pas di kolom' },
      { key:'landscape_tall',   orient:'landscape', label:'Landscape Klasik',  ratio:'4:3',   w:800, h:600, mode:'fit',  q:78, desc:'Proporsi kamera klasik' },
      { key:'landscape_banner', orient:'landscape', label:'Banner Horizontal', ratio:'2.4:1', w:960, h:400, mode:'crop', q:80, desc:'Header section / banner' },
      { key:'square',           orient:'square',    label:'Kotak (Square)',    ratio:'1:1',   w:600, h:600, mode:'crop', q:80, desc:'Instagram-style, ikon' },
      { key:'square_large',     orient:'square',    label:'Kotak Besar',       ratio:'1:1',   w:800, h:800, mode:'crop', q:80, desc:'Square beresolusi tinggi' },
      { key:'portrait_medium',  orient:'portrait',  label:'Potret Sedang',     ratio:'3:4',   w:540, h:720, mode:'fit',  q:80, desc:'Foto orang / tokoh' },
      { key:'portrait_tall',    orient:'portrait',  label:'Potret Tinggi',     ratio:'9:16',  w:480, h:840, mode:'fit',  q:80, desc:'Foto berdiri / stories-style' },
      { key:'portrait_large',   orient:'portrait',  label:'Potret Besar',      ratio:'2:3',   w:600, h:900, mode:'fit',  q:80, desc:'Potret beresolusi tinggi' },
    ],
    thumbnail: [
      { key:'thumb_landscape',  orient:'landscape', label:'Landscape (Standar)', ratio:'16:10', w:720, h:450, mode:'fit',  q:75, desc:'Default kartu artikel' },
      { key:'thumb_portrait',   orient:'portrait',  label:'Potret',              ratio:'3:4',   w:450, h:600, mode:'fit',  q:75, desc:'Cocok untuk foto tokoh' },
      { key:'thumb_square',     orient:'square',    label:'Kotak',               ratio:'1:1',   w:500, h:500, mode:'crop', q:75, desc:'Thumbnail square konsisten' },
    ],
  };

  // Expose preset terpilih → dibaca artikel-editor.php via ArtikelImagePicker._lastPreset
  let _lastPreset = null;

  // ── CSS ─────────────────────────────────────────────────────────────
  const CSS = `
<style id="aipCSS">
.aip-overlay{position:fixed;inset:0;background:rgba(0,0,0,.78);backdrop-filter:blur(4px);z-index:9500;
  display:flex;align-items:center;justify-content:center;padding:16px;
  opacity:0;visibility:hidden;transition:all .2s}
.aip-overlay.open{opacity:1;visibility:visible}
.aip-modal{background:var(--bg-card);border:1px solid var(--border);border-radius:14px;
  width:100%;max-width:900px;max-height:90vh;display:flex;flex-direction:column;overflow:hidden;
  transform:translateY(18px);transition:transform .25s cubic-bezier(.4,0,.2,1)}
.aip-overlay.open .aip-modal{transform:translateY(0)}

.aip-header{padding:18px 22px 14px;border-bottom:1px solid var(--border);
  display:flex;align-items:center;gap:12px;flex-shrink:0}
.aip-header h3{font-family:'Playfair Display',serif;font-size:16px;font-weight:600;flex:1;margin:0}
.aip-close{background:none;border:none;color:var(--text-muted);font-size:24px;cursor:pointer;
  line-height:1;padding:4px;transition:color .15s}
.aip-close:hover{color:var(--text-primary)}

.aip-tabs{display:flex;gap:2px;padding:0 22px;border-bottom:1px solid var(--border);flex-shrink:0}
.aip-tab{padding:10px 18px;background:none;border:none;border-bottom:2px solid transparent;
  color:var(--text-secondary);font-family:'DM Sans',sans-serif;font-size:13px;font-weight:500;
  cursor:pointer;transition:all .15s;margin-bottom:-1px}
.aip-tab.active{color:var(--accent);border-bottom-color:var(--accent)}
.aip-tab:hover:not(.active){color:var(--text-primary)}

.aip-body{flex:1;overflow:hidden;display:flex;flex-direction:column}
.aip-panel{display:none;flex-direction:column;flex:1;overflow:hidden}
.aip-panel.active{display:flex}

/* ─ Panel Galeri ─ */
.aip-toolbar{padding:12px 22px 10px;display:flex;align-items:center;gap:10px;flex-shrink:0}
.aip-search{flex:1;position:relative}
.aip-search input{width:100%;background:var(--bg-input);border:1px solid var(--border);
  border-radius:8px;color:var(--text-primary);font-size:13px;padding:8px 12px 8px 34px;outline:none;
  transition:border-color .15s}
.aip-search input:focus{border-color:var(--border-focus)}
.aip-search svg{position:absolute;left:10px;top:50%;transform:translateY(-50%);
  color:var(--text-muted);pointer-events:none}
.aip-count{font-size:12px;color:var(--text-muted);white-space:nowrap}
.aip-btn-refresh{background:none;border:1px solid var(--border);border-radius:6px;
  color:var(--text-secondary);padding:6px 10px;cursor:pointer;font-size:12px;
  transition:all .15s;display:flex;align-items:center;gap:5px}
.aip-btn-refresh:hover{border-color:var(--accent);color:var(--accent)}

.aip-grid-wrap{flex:1;overflow-y:auto;padding:0 22px 16px}
.aip-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:10px;padding-top:4px}
.aip-item{border:2px solid var(--border);border-radius:8px;overflow:hidden;cursor:pointer;
  background:var(--bg-card2);transition:all .15s;position:relative}
.aip-item:hover{border-color:var(--accent);transform:translateY(-2px);box-shadow:0 4px 16px rgba(201,168,76,.15)}
.aip-item.selected{border-color:var(--accent);box-shadow:0 0 0 3px rgba(201,168,76,.2)}
.aip-item img{width:100%;height:90px;object-fit:cover;display:block}
.aip-item-name{padding:5px 7px;font-size:10.5px;color:var(--text-muted);
  white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.aip-item-size{padding:0 7px 5px;font-size:10px;color:var(--text-muted)}
.aip-item-check{position:absolute;top:5px;right:5px;width:20px;height:20px;
  background:var(--accent);border-radius:50%;display:none;align-items:center;
  justify-content:center;color:#1a1410;font-size:11px;font-weight:700}
.aip-item.selected .aip-item-check{display:flex}

.aip-empty{text-align:center;padding:40px 20px;color:var(--text-muted);font-size:13.5px}
.aip-empty svg{opacity:.25;display:block;margin:0 auto 12px}

.aip-footer{padding:14px 22px;border-top:1px solid var(--border);
  display:flex;flex-direction:column;align-items:stretch;gap:10px;flex-shrink:0}
.aip-selected-preview{display:flex;align-items:center;gap:10px;flex:1;min-width:0}
.aip-selected-preview img{width:40px;height:40px;border-radius:5px;object-fit:cover;
  border:1px solid var(--border);flex-shrink:0}
.aip-selected-name{font-size:12px;color:var(--text-secondary);
  white-space:nowrap;overflow:hidden;text-overflow:ellipsis}

/* ─ Panel Upload: 2 kolom ─ */
.aip-upload-cols{display:flex;flex:1;overflow:hidden}
.aip-upload-left{width:330px;flex-shrink:0;border-right:1px solid var(--border);
  overflow-y:auto;padding:16px 18px;display:flex;flex-direction:column;gap:12px}
.aip-upload-right{flex:1;overflow-y:auto;padding:16px 18px;display:flex;flex-direction:column;gap:11px}

/* ─ Dropzone ─ */
.aip-dropzone{border:2px dashed var(--border);border-radius:10px;padding:26px 16px;text-align:center;
  cursor:pointer;transition:all .2s;position:relative}
.aip-dropzone:hover,.aip-dropzone.drag{border-color:var(--accent);background:rgba(201,168,76,.04)}
.aip-dropzone input[type=file]{position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%}
.aip-dropzone-icon{font-size:28px;margin-bottom:7px;display:block}
.aip-dropzone p{font-size:12.5px;color:var(--text-secondary);margin-bottom:3px}
.aip-dropzone small{font-size:11px;color:var(--text-muted)}

.aip-upload-preview{display:none;align-items:flex-start;gap:11px;
  background:var(--bg-card2);border:1px solid var(--border);border-radius:8px;padding:11px}
.aip-upload-preview.show{display:flex}
.aip-upload-preview img{width:66px;height:50px;object-fit:cover;border-radius:5px;
  border:1px solid var(--border);flex-shrink:0}
.aip-upload-meta{flex:1;min-width:0}
.aip-upload-meta .fname{font-size:12.5px;font-weight:500;color:var(--text-primary);
  white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-bottom:3px}
.aip-upload-meta .fsize{font-size:11px;color:var(--text-muted)}

.aip-progress{height:4px;background:var(--border);border-radius:2px;margin:4px 0;overflow:hidden;display:none}
.aip-progress.show{display:block}
.aip-progress-bar{height:100%;background:var(--accent);border-radius:2px;
  width:0;transition:width .3s ease}

.aip-upload-result{display:none;font-size:12px;margin-top:4px}
.aip-upload-result.ok{color:var(--success);display:block}
.aip-upload-result.err{color:var(--danger);display:block}

.aip-btn-upload{padding:9px 20px;font-size:13px;margin-top:auto}
.aip-btn-upload:disabled{opacity:.5;cursor:not-allowed}

/* ─ Field inputs ─ */
.aip-namafile-wrap,.aip-alt-wrap{display:flex;flex-direction:column;gap:5px;
  background:rgba(201,168,76,.05);border:1px solid rgba(201,168,76,.2);
  border-radius:8px;padding:11px 12px}
.aip-alt-wrap{background:rgba(82,148,224,.05);border-color:rgba(82,148,224,.2)}
.aip-namafile-wrap label,.aip-alt-wrap label{font-size:11.5px;font-weight:600;color:var(--text-secondary)}
.aip-namafile-input{background:var(--bg-input);border:1px solid var(--border);border-radius:7px;
  color:var(--text-primary);font-size:13px;padding:8px 11px;outline:none;width:100%;
  transition:border-color .15s;font-family:'DM Sans',sans-serif;box-sizing:border-box}
.aip-namafile-input:focus{border-color:var(--border-focus)}
.aip-namafile-hint,.aip-alt-hint{font-size:11px;color:var(--text-muted);line-height:1.5}

/* ─ [BARU] Pemilih ukuran ─ */
.aip-size-section{display:flex;flex-direction:column;gap:7px}
.aip-size-label{font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;
  color:var(--text-muted);display:flex;align-items:center;gap:8px}
.aip-size-badge{font-size:10px;font-weight:500;background:rgba(201,168,76,.15);color:var(--accent);
  border:1px solid rgba(201,168,76,.3);border-radius:10px;padding:1px 7px;
  text-transform:none;letter-spacing:0;transition:all .2s}

.aip-orient-tabs{display:flex;gap:4px}
.aip-orient-tab{flex:1;display:flex;align-items:center;justify-content:center;gap:5px;
  padding:5px 6px;background:var(--bg-card2);border:1px solid var(--border);
  border-radius:7px;cursor:pointer;color:var(--text-muted);font-size:11px;
  font-weight:500;font-family:'DM Sans',sans-serif;transition:all .18s}
.aip-orient-tab.active{background:rgba(201,168,76,.12);border-color:rgba(201,168,76,.4);color:var(--accent)}
.aip-orient-tab:hover:not(.active){color:var(--text-secondary);border-color:var(--border-focus)}

.aip-preset-grid{display:flex;flex-direction:column;gap:4px}
.aip-preset-card{display:flex;align-items:center;gap:9px;padding:6px 9px;border-radius:7px;
  border:1px solid var(--border);cursor:pointer;background:var(--bg-card2);transition:all .14s}
.aip-preset-card:hover{border-color:rgba(201,168,76,.4);background:rgba(201,168,76,.04)}
.aip-preset-card.selected{border-color:var(--accent);background:rgba(201,168,76,.1)}
.aip-preset-icon-box{width:28px;height:20px;flex-shrink:0;display:flex;align-items:center;
  justify-content:center;background:var(--bg-card);border:1px solid var(--border);border-radius:3px}
.aip-preset-icon-inner{background:rgba(201,168,76,.3);border-radius:2px;transition:background .14s}
.aip-preset-card.selected .aip-preset-icon-inner{background:var(--accent)}
.aip-preset-info{flex:1;min-width:0}
.aip-preset-name{font-size:11.5px;font-weight:500;color:var(--text-secondary)}
.aip-preset-card.selected .aip-preset-name{color:var(--accent)}
.aip-preset-meta{font-size:10px;color:var(--text-muted);margin-top:1px}

/* ─ Info kompresi ─ */
.aip-typeinfo{background:rgba(201,168,76,.06);border:1px solid rgba(201,168,76,.18);
  border-radius:7px;padding:9px 12px;font-size:11.5px;color:var(--text-secondary);line-height:1.7}

/* ─ [BARU] Kolom kanan: preview simulasi ─ */
.aip-preview-hdr{font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;
  color:var(--text-muted);display:flex;align-items:center;gap:6px}
.aip-sim-tabs{display:flex;gap:4px}
.aip-sim-tab{padding:4px 12px;border-radius:20px;background:var(--bg-card2);
  border:1px solid var(--border);font-size:11.5px;font-weight:500;color:var(--text-muted);
  cursor:pointer;font-family:'DM Sans',sans-serif;transition:all .14s}
.aip-sim-tab.active{background:rgba(201,168,76,.12);border-color:rgba(201,168,76,.35);color:var(--accent)}

.aip-art-sim{background:var(--bg-card2);border:1px solid var(--border);border-radius:10px;
  padding:13px;display:flex;flex-direction:column;gap:9px}
.aip-sim-lines{display:flex;flex-direction:column;gap:5px}
.aip-sim-line{height:7px;border-radius:4px;background:var(--border)}
.aip-sim-line--title{height:12px;width:65%;background:rgba(201,168,76,.2)}
.aip-sim-line--sub{width:88%}
.aip-sim-line--short{width:50%}
.aip-sim-after{opacity:.5}
.aip-sim-imgwrap{border-radius:7px;overflow:hidden;background:var(--bg-card);
  border:1px solid var(--border);display:flex;align-items:center;justify-content:center;
  min-height:70px;transition:aspect-ratio .3s}
.aip-sim-ph{display:flex;flex-direction:column;align-items:center;gap:6px;
  padding:18px;color:var(--text-muted);font-size:11px}
#aipSimImg{width:100%;display:block}

.aip-card-sim{background:var(--bg-card2);border:1px solid var(--border);border-radius:10px;
  overflow:hidden;max-width:250px;margin:0 auto}
.aip-card-thumb{overflow:hidden;background:var(--bg-card);
  display:flex;align-items:center;justify-content:center;transition:aspect-ratio .3s}
#aipCardImg{width:100%;height:100%;object-fit:cover}
.aip-card-body{padding:10px 12px;display:flex;flex-direction:column;gap:4px}
.aip-card-badge{font-size:10px;font-weight:600;text-transform:uppercase;
  letter-spacing:.06em;color:var(--accent)}
.aip-card-title{font-size:12px;font-weight:600;color:var(--text-secondary);line-height:1.4}
.aip-card-meta{font-size:10.5px;color:var(--text-muted)}

.aip-size-info{background:rgba(201,168,76,.04);border:1px solid rgba(201,168,76,.15);
  border-radius:8px;padding:9px 11px;display:flex;flex-direction:column;gap:5px;margin-top:auto}
.aip-size-info-row{display:flex;justify-content:space-between;align-items:center}
.aip-size-info-lbl{font-size:11px;color:var(--text-muted)}
.aip-size-info-val{font-size:11px;color:var(--accent);font-weight:500}

/* Alt galeri */
.aip-galeri-alt-wrap{display:flex;flex-direction:column;gap:5px}
.aip-galeri-alt-wrap label{font-size:11.5px;font-weight:600;color:var(--text-secondary)}

/* Responsive */
@media(max-width:680px){
  .aip-upload-right{display:none}
  .aip-upload-left{width:100%;border-right:none}
  .aip-modal{max-width:100%}
}

#aipUploadRight {
  transition: all .25s ease;
}

.aip-upload-cols.no-preview .aip-upload-left {
  width: 100%;
}

.aip-upload-cols.no-preview .aip-upload-right {
  display: none !important;
}
</style>`;

  // ── HTML Modal ─────────────────────────────────────────────────────
  const HTML = `
<div class="aip-overlay" id="aipOverlay">
  <div class="aip-modal">
    <div class="aip-header">
      <h3 id="aipTitle">Pilih Gambar Artikel</h3>
      <button class="aip-close" onclick="ArtikelImagePicker.close()">×</button>
    </div>

    <div class="aip-tabs">
      <button class="aip-tab active" id="aipTabGaleri" onclick="ArtikelImagePicker._tab('galeri')">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
             width="13" height="13" style="vertical-align:middle;margin-right:5px">
          <rect x="3" y="3" width="18" height="18" rx="2"/>
          <circle cx="8.5" cy="8.5" r="1.5"/>
          <polyline points="21 15 16 10 5 21"/>
        </svg>
        Dari Galeri
        <span id="aipGaleriCount" style="font-size:10px;color:var(--text-muted);margin-left:4px"></span>
      </button>
      <button class="aip-tab" id="aipTabUpload" onclick="ArtikelImagePicker._tab('upload')">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
             width="13" height="13" style="vertical-align:middle;margin-right:5px">
          <polyline points="16 16 12 12 8 16"/>
          <line x1="12" y1="12" x2="12" y2="21"/>
          <path d="M20.39 18.39A5 5 0 0018 9h-1.26A8 8 0 103 16.3"/>
        </svg>
        Upload Baru
      </button>
    </div>

    <div class="aip-body">

      <!-- ══ Panel: Galeri ═══════════════════════════════════════════ -->
      <div class="aip-panel active" id="aipPanelGaleri">
        <div class="aip-toolbar">
          <div class="aip-search">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14">
              <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
            <input type="text" id="aipSearch" placeholder="Cari nama file..."
                   oninput="ArtikelImagePicker._filter(this.value)">
          </div>
          <span class="aip-count" id="aipCountLabel"></span>
          <button class="aip-btn-refresh" onclick="ArtikelImagePicker._loadGaleri()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12">
              <polyline points="1 4 1 10 7 10"/>
              <path d="M3.51 15a9 9 0 102.13-9.36L1 10"/>
            </svg>
            Refresh
          </button>
        </div>
        <div class="aip-grid-wrap">
          <div class="aip-grid" id="aipGrid"></div>
        </div>
        <div class="aip-footer">
          <div style="display:flex;align-items:center;justify-content:space-between;gap:10px">
            <div class="aip-selected-preview" id="aipSelPreview" style="display:none">
              <img id="aipSelImg" src="" alt="">
              <span class="aip-selected-name" id="aipSelName"></span>
            </div>
            <div style="flex:1" id="aipSelEmpty">Klik gambar untuk memilih</div>
            <div style="display:flex;gap:8px">
              <button class="btn btn-secondary btn-sm" onclick="ArtikelImagePicker.close()">Batal</button>
              <button class="btn btn-primary btn-sm" id="aipBtnPilih"
                      onclick="ArtikelImagePicker._confirmGaleri()" disabled>
                Gunakan Gambar Ini
              </button>
            </div>
          </div>
          <!-- Alt text galeri — hanya content -->
          <div id="aipGaleriAltWrap" style="display:none" class="aip-galeri-alt-wrap">
            <label for="aipGaleriAltInput">
              🔤 Alt text gambar
              <span style="font-weight:400;color:var(--text-muted)">(SEO &amp; aksesibilitas)</span>
            </label>
            <input type="text" class="aip-namafile-input" id="aipGaleriAltInput"
                   placeholder="Deskripsi singkat isi gambar...">
            <span style="font-size:11px;color:var(--text-muted)">
              Digunakan untuk mesin pencari dan pembaca layar.
            </span>
          </div>
        </div>
      </div>

      <!-- ══ Panel: Upload — 2 kolom ════════════════════════════════ -->
      <div class="aip-panel" id="aipPanelUpload">
        <div class="aip-upload-cols no-preview" id="aipUploadCols">

          <!-- Kolom kiri: form upload + pemilih ukuran -->
          <div class="aip-upload-left">

            <div class="aip-dropzone" id="aipDropzone">
              <input type="file" id="aipFileInput" accept="image/jpeg,image/png,image/webp"
                     onchange="ArtikelImagePicker._onFileSelect(this)">
              <span class="aip-dropzone-icon">🖼️</span>
              <p>Klik atau seret gambar ke sini</p>
              <small>JPG, PNG, WebP · Maks 20MB · Otomatis dikompres</small>
            </div>

            <div class="aip-upload-preview" id="aipUploadPreview">
              <img id="aipUploadThumb" src="" alt="">
              <div class="aip-upload-meta">
                <div class="fname" id="aipUploadFname"></div>
                <div class="fsize" id="aipUploadFsize"></div>
                <div class="aip-progress" id="aipProgressWrap">
                  <div class="aip-progress-bar" id="aipProgressBar"></div>
                </div>
                <div class="aip-upload-result" id="aipUploadResult"></div>
              </div>
            </div>

            <div class="aip-typeinfo" id="aipTypeInfo"></div>

            <!-- Input nama file — hanya content -->
            <div class="aip-namafile-wrap" id="aipNamafileWrap" style="display:none">
              <label for="aipNamafileInput">
                ✏️ Nama foto <span style="color:var(--danger,#e05252)">*</span>
              </label>
              <input type="text" class="aip-namafile-input" id="aipNamafileInput"
                     placeholder="contoh: Misa Hari Raya Natal" spellcheck="false" autocomplete="off">
              <span class="aip-namafile-hint">
                Tulis nama foto tanpa ekstensi, misal: <em>Misa Hari Raya Natal</em>
              </span>
            </div>

            <!-- Input alt text — hanya content -->
            <div class="aip-alt-wrap" id="aipAltWrap" style="display:none">
              <label for="aipAltInput">
                🔤 Alt text
                <span style="font-weight:400;color:var(--text-muted)">(SEO &amp; aksesibilitas)</span>
                <span style="color:var(--danger,#e05252)">*</span>
              </label>
              <input type="text" class="aip-namafile-input" id="aipAltInput"
                     placeholder="contoh: Foto baptisan minggu Paskah 2024"
                     spellcheck="false" autocomplete="off">
              <span class="aip-alt-hint">
                Tulis persis seperti yang ingin ditampilkan untuk mesin pencari.
              </span>
            </div>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px">
  <span style="font-size:11px;color:var(--text-muted)">
    Preview simulasi artikel
  </span>
  <button type="button" id="aipTogglePreview"
    style="
      font-size:11px;
      padding:4px 10px;
      border-radius:20px;
      border:1px solid var(--border);
      background:var(--bg-card2);
      color:var(--text-muted);
      cursor:pointer;
      transition:all .2s;
    "
    onclick="ArtikelImagePicker.togglePreview()">
    👁️ Tampilkan
  </button>
</div>

            <!-- ── [BARU] Pemilih ukuran output ─────────────────── -->
            <div class="aip-size-section" id="aipSizeSection">
              <div class="aip-size-label">
                Ukuran Output
                <span class="aip-size-badge" id="aipSizeBadge">—</span>
              </div>
              <div class="aip-orient-tabs" id="aipOrientTabs">
                <button class="aip-orient-tab active" data-orient="landscape"
                        onclick="ArtikelImagePicker._setOrient('landscape')">
                  <svg viewBox="0 0 18 12" fill="none" stroke="currentColor" stroke-width="1.8" width="16" height="11">
                    <rect x="1" y="1" width="16" height="10" rx="1.5"/>
                  </svg>
                  Landscape
                </button>
                <button class="aip-orient-tab" data-orient="square"
                        onclick="ArtikelImagePicker._setOrient('square')">
                  <svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="1.8" width="11" height="11">
                    <rect x="1" y="1" width="10" height="10" rx="1.5"/>
                  </svg>
                  Square
                </button>
                <button class="aip-orient-tab" data-orient="portrait"
                        onclick="ArtikelImagePicker._setOrient('portrait')">
                  <svg viewBox="0 0 12 18" fill="none" stroke="currentColor" stroke-width="1.8" width="11" height="16">
                    <rect x="1" y="1" width="10" height="16" rx="1.5"/>
                  </svg>
                  Portrait
                </button>
              </div>
              <div class="aip-preset-grid" id="aipPresetGrid"></div>
            </div>

            <!-- Tombol Upload -->
            <div style="display:flex;justify-content:flex-end;gap:8px;padding-top:4px;margin-top:auto">
              <button class="btn btn-secondary btn-sm" onclick="ArtikelImagePicker.close()">Batal</button>
              <button class="btn btn-primary btn-sm aip-btn-upload" id="aipBtnUpload"
                      onclick="ArtikelImagePicker._doUpload()" disabled>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13">
                  <polyline points="16 16 12 12 8 16"/>
                  <line x1="12" y1="12" x2="12" y2="21"/>
                  <path d="M20.39 18.39A5 5 0 0018 9h-1.26A8 8 0 103 16.3"/>
                </svg>
                <span id="aipBtnUploadLbl">Upload &amp; Kompres</span>
              </button>
            </div>

          </div><!-- /.aip-upload-left -->

          <!-- Kolom kanan: simulasi preview di artikel -->
          <div class="aip-upload-right" id="aipUploadRight" style="display:none">

            <div class="aip-preview-hdr">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                <circle cx="12" cy="12" r="3"/>
              </svg>
              Simulasi di Artikel
            </div>

            <div class="aip-sim-tabs" id="aipSimTabs">
              <button class="aip-sim-tab active" data-sim="konten"
                      onclick="ArtikelImagePicker._setSimTab('konten')">Di konten</button>
              <button class="aip-sim-tab" data-sim="kartu"
                      onclick="ArtikelImagePicker._setSimTab('kartu')">Di kartu artikel</button>
            </div>

            <!-- Simulasi konten artikel -->
            <div id="aipSimKonten">
              <div class="aip-art-sim">
                <div class="aip-sim-lines">
                  <div class="aip-sim-line aip-sim-line--title"></div>
                  <div class="aip-sim-line aip-sim-line--sub"></div>
                </div>
                <div class="aip-sim-imgwrap" id="aipSimImgWrap">
                  <div class="aip-sim-ph" id="aipSimPh">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                         width="24" height="24" opacity=".3">
                      <rect x="3" y="3" width="18" height="18" rx="2"/>
                      <circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/>
                    </svg>
                    <span>Pratinjau gambar</span>
                  </div>
                  <img id="aipSimImg" src="" alt="" style="display:none">
                </div>
                <div class="aip-sim-lines aip-sim-after">
                  <div class="aip-sim-line"></div>
                  <div class="aip-sim-line"></div>
                  <div class="aip-sim-line aip-sim-line--short"></div>
                </div>
              </div>
            </div>

            <!-- Simulasi kartu artikel -->
            <div id="aipSimKartu" style="display:none">
              <div class="aip-card-sim">
                <div class="aip-card-thumb" id="aipCardThumb">
                  <div class="aip-sim-ph" id="aipCardPh">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                         width="20" height="20" opacity=".3">
                      <rect x="3" y="3" width="18" height="18" rx="2"/>
                      <circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/>
                    </svg>
                  </div>
                  <img id="aipCardImg" src="" alt=""
                       style="display:none;width:100%;height:100%;object-fit:cover">
                </div>
                <div class="aip-card-body">
                  <div class="aip-card-badge">Liputan Berita</div>
                  <div class="aip-card-title" id="aipCardTitle">Judul artikel…</div>
                  <div class="aip-card-meta">Penulis · Hari ini</div>
                </div>
              </div>
            </div>

            <!-- Info detail ukuran -->
            <div class="aip-size-info" id="aipSizeInfo" style="display:none">
              <div class="aip-size-info-row">
                <span class="aip-size-info-lbl">Ukuran output</span>
                <span class="aip-size-info-val" id="aipSiDim">—</span>
              </div>
              <div class="aip-size-info-row">
                <span class="aip-size-info-lbl">Mode</span>
                <span class="aip-size-info-val" id="aipSiMode">—</span>
              </div>
              <div class="aip-size-info-row">
                <span class="aip-size-info-lbl">Orientasi</span>
                <span class="aip-size-info-val" id="aipSiOrient">—</span>
              </div>
            </div>

          </div><!-- /.aip-upload-right -->
        </div><!-- /.aip-upload-cols -->
      </div><!-- /#aipPanelUpload -->

    </div><!-- /.aip-body -->
  </div><!-- /.aip-modal -->
</div><!-- /.aip-overlay -->`;

  // ── Private State ────────────────────────────────────────────────────
  let _selectedFile      = null;
  let _uploadFile        = null;
  let _uploadedUrl       = null;
  let _currentTab        = 'galeri';
  let _currentOrient     = 'landscape';
  let _currentSim        = 'konten';
  let _selectedPresetKey = null;
  let _previewObjectURL  = null;

function waitElement(id, callback) {
  const interval = setInterval(() => {
    const el = document.getElementById(id);
    if (el) {
      clearInterval(interval);
      callback(el);
    }
  }, 10);
}

  // ── Ensure injected ──────────────────────────────────────────────────
  function _ensure() {
    if (document.getElementById('aipOverlay')) return;
    document.head.insertAdjacentHTML('beforeend', CSS);
    document.body.insertAdjacentHTML('beforeend', HTML);
    _overlay = document.getElementById('aipOverlay');

    _overlay.addEventListener('click', function(e) {
      if (e.target === _overlay) ArtikelImagePicker.close();
    });

    const dz = document.getElementById('aipDropzone');
    dz.addEventListener('dragover',  e => { e.preventDefault(); dz.classList.add('drag'); });
    dz.addEventListener('dragleave', () => dz.classList.remove('drag'));
    dz.addEventListener('drop', e => {
      e.preventDefault(); dz.classList.remove('drag');
      const f = e.dataTransfer.files[0];
      if (f) ArtikelImagePicker._onFileSelect(null, f);
    });
  }

  // ── Tab switching ────────────────────────────────────────────────────
  function _tab(name) {
    _currentTab = name;
    document.getElementById('aipTabGaleri').classList.toggle('active', name === 'galeri');
    document.getElementById('aipTabUpload').classList.toggle('active', name === 'upload');
    document.getElementById('aipPanelGaleri').classList.toggle('active', name === 'galeri');
    document.getElementById('aipPanelUpload').classList.toggle('active', name === 'upload');
  }

  // ── Galeri ───────────────────────────────────────────────────────────
  async function _loadGaleri() {
    const grid = document.getElementById('aipGrid');
    grid.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:30px;color:var(--text-muted)"><div class="spinner" style="width:24px;height:24px;margin:0 auto 8px"></div>Memuat...</div>';
    try {
      const res  = await fetch('/admin/api/list_artikel_images.php', {
        cache: 'no-store',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-No-SW': '1' }
      });
      const data = await res.json();
      _allFiles  = data.files || [];
      _filtered  = [..._allFiles];
      document.getElementById('aipGaleriCount').textContent = '(' + _allFiles.length + ')';
      _renderGrid(_filtered);
    } catch(e) {
      grid.innerHTML = '<div class="aip-empty"><p>Gagal memuat galeri.</p></div>';
    }
  }

  function _filter(q) {
    q = q.toLowerCase();
    _filtered = q ? _allFiles.filter(f => f.name.toLowerCase().includes(q)) : [..._allFiles];
    _renderGrid(_filtered);
  }

  function _renderGrid(files) {
    const grid = document.getElementById('aipGrid');
    document.getElementById('aipCountLabel').textContent = files.length + ' gambar';
    if (!files.length) {
      grid.innerHTML = '<div class="aip-empty" style="grid-column:1/-1"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="40" height="40"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg><p>Belum ada gambar di /img/artikel/</p></div>';
      return;
    }
    grid.innerHTML = files.map((f, i) => `
      <div class="aip-item" id="aip_item_${i}" onclick="ArtikelImagePicker._selectItem(${i},'${f.url}','${f.name}')">
        <img src="${f.url}" alt="${f.name}" loading="lazy" onerror="this.src='/admin/img/noimage.png'">
        <div class="aip-item-check">✓</div>
        <div class="aip-item-name" title="${f.name}">${f.name}</div>
        <div class="aip-item-size">${f.size_kb} KB</div>
      </div>`).join('');
  }

  function _selectItem(idx, url, name) {
    document.querySelectorAll('.aip-item.selected').forEach(el => el.classList.remove('selected'));
    const el = document.getElementById('aip_item_' + idx);
    if (el) el.classList.add('selected');
    _selectedFile = { url, name };

    document.getElementById('aipSelImg').src = url;
    document.getElementById('aipSelName').textContent = name;
    document.getElementById('aipSelPreview').style.display = 'flex';
    document.getElementById('aipSelEmpty').style.display   = 'none';
    document.getElementById('aipBtnPilih').disabled = false;

    const galeriAltWrap  = document.getElementById('aipGaleriAltWrap');
    const galeriAltInput = document.getElementById('aipGaleriAltInput');
    if (galeriAltWrap) {
      if (_opts.type === 'thumbnail') {
        galeriAltWrap.style.display = 'none';
      } else {
        galeriAltWrap.style.display = '';
        if (galeriAltInput && !galeriAltInput.value) galeriAltInput.focus();
      }
    }
  }

  function _confirmGaleri() {
    if (!_selectedFile) return;
    const alt = (_opts.type === 'thumbnail')
      ? (_opts.judul || '')
      : (document.getElementById('aipGaleriAltInput')?.value || '').trim();
    _opts.onSelect && _opts.onSelect(_selectedFile.url, _selectedFile.name, alt, null);
    ArtikelImagePicker.close();
  }

  // ── File select & local preview ──────────────────────────────────────
  function _onFileSelect(input, directFile) {
    const file = directFile || (input && input.files[0]);
    if (!file) return;
    _uploadFile  = file;
    _uploadedUrl = null;

    if (_previewObjectURL) { URL.revokeObjectURL(_previewObjectURL); _previewObjectURL = null; }
    _previewObjectURL = URL.createObjectURL(file);

    document.getElementById('aipUploadThumb').src = _previewObjectURL;
    document.getElementById('aipUploadFname').textContent  = file.name;
    document.getElementById('aipUploadFsize').textContent  = (file.size / 1024).toFixed(1) + ' KB (sebelum kompres)';
    document.getElementById('aipUploadPreview').classList.add('show');
    document.getElementById('aipUploadResult').className   = 'aip-upload-result';
    document.getElementById('aipUploadResult').textContent = '';
    document.getElementById('aipBtnUpload').disabled       = false;

    // Tampilkan preview di simulasi
    _showPreviewImg(_previewObjectURL);

    // Auto-detect orientasi, auto-pindah tab (hanya content)
    if (_opts.type !== 'thumbnail') {
      const probe = new Image();
      probe.onload = () => {
        const w = probe.naturalWidth, h = probe.naturalHeight;
        if (h > w * 1.1)                    _setOrient('portrait');
        else if (Math.abs(w - h) < w * 0.1) _setOrient('square');
        else                                 _setOrient('landscape');
      };
      probe.src = _previewObjectURL;
    }

    _updateBtnLabel();
  }

  // ── [BARU] Orientasi & preset ukuran ────────────────────────────────
  function _setOrient(orient) {
    _currentOrient = orient;
    document.querySelectorAll('.aip-orient-tab').forEach(t => {
      t.classList.toggle('active', t.dataset.orient === orient);
    });
    const type    = _opts.type === 'thumbnail' ? 'thumbnail' : 'content';
    const presets = SIZE_PRESETS[type].filter(p => p.orient === orient);
    _renderPresets(presets);
    if (presets.length) _selectPreset(presets[0].key);
  }

  function _renderPresets(presets) {
    const grid = document.getElementById('aipPresetGrid');
    if (!grid) return;
    grid.innerHTML = '';
    presets.forEach(p => {
      const card = document.createElement('div');
      card.className  = 'aip-preset-card' + (_selectedPresetKey === p.key ? ' selected' : '');
      card.dataset.pkey = p.key;
      card.onclick    = () => _selectPreset(p.key);

      const maxIW = 18, maxIH = 13;
      const scl   = Math.min(maxIW / p.w, maxIH / p.h);
      const iw    = Math.max(4, Math.round(p.w * scl));
      const ih    = Math.max(3, Math.round(p.h * scl));

      card.innerHTML = `
        <div class="aip-preset-icon-box">
          <div class="aip-preset-icon-inner" style="width:${iw}px;height:${ih}px"></div>
        </div>
        <div class="aip-preset-info">
          <div class="aip-preset-name">${p.label}</div>
          <div class="aip-preset-meta">${p.w}×${p.h}px · ${p.ratio} · ${p.mode === 'crop' ? 'Crop tengah' : 'Fit'}</div>
        </div>`;
      grid.appendChild(card);
    });
  }

  function _selectPreset(key) {
    const allP = [...SIZE_PRESETS.content, ...SIZE_PRESETS.thumbnail];
    const p    = allP.find(x => x.key === key);
    if (!p) return;
    _selectedPresetKey = key;

    // Expose ke luar — dibaca artikel-editor.php setelah onSelect dipanggil
    ArtikelImagePicker._lastPreset = p;

    document.querySelectorAll('.aip-preset-card').forEach(c => {
      c.classList.toggle('selected', c.dataset.pkey === key);
    });

    const badge = document.getElementById('aipSizeBadge');
    if (badge) badge.textContent = `${p.w}×${p.h}`;

    const info = document.getElementById('aipSizeInfo');
    if (info) info.style.display = '';
    _elTxt('aipSiDim',    `${p.w} × ${p.h} px`);
    _elTxt('aipSiMode',   p.mode === 'crop' ? 'Crop tengah (dimensi tepat)' : 'Fit proporsional');
    _elTxt('aipSiOrient', { landscape:'↔ Landscape', portrait:'↕ Portrait', square:'⬛ Square' }[p.orient] || p.orient);

    _updateSimAspect(p);
    _updateBtnLabel();
  }

  // ── [BARU] Simulasi tampilan di artikel ─────────────────────────────
  function _setSimTab(sim) {
    _currentSim = sim;
    document.querySelectorAll('.aip-sim-tab').forEach(t => {
      t.classList.toggle('active', t.dataset.sim === sim);
    });
    const ke = document.getElementById('aipSimKonten');
    const ka = document.getElementById('aipSimKartu');
    if (ke) ke.style.display = sim === 'konten' ? '' : 'none';
    if (ka) ka.style.display = sim === 'kartu'  ? '' : 'none';
  }

  function _updateSimAspect(p) {
    const ar = (p.w / p.h).toFixed(3);
    const wrap = document.getElementById('aipSimImgWrap');
    if (wrap) {
      wrap.style.aspectRatio = ar;
      wrap.style.maxWidth    = p.orient === 'portrait' ? '55%' : '';
      wrap.style.margin      = p.orient === 'portrait' ? '0 auto' : '';
    }
    const cardThumb = document.getElementById('aipCardThumb');
    if (cardThumb) cardThumb.style.aspectRatio = ar;
    if (_previewObjectURL) _showPreviewImg(_previewObjectURL);
  }

  function _showPreviewImg(url) {
    const ph  = document.getElementById('aipSimPh');
    const img = document.getElementById('aipSimImg');
    if (ph)  ph.style.display  = 'none';
    if (img) { img.src = url; img.style.display = ''; }
    const cardPh  = document.getElementById('aipCardPh');
    const cardImg = document.getElementById('aipCardImg');
    if (cardPh)  cardPh.style.display  = 'none';
    if (cardImg) { cardImg.src = url; cardImg.style.display = ''; }
  }

  function _hidePreviewImg() {
    ['aipSimPh','aipCardPh'].forEach(id => { const el = document.getElementById(id); if (el) el.style.display = ''; });
    ['aipSimImg','aipCardImg'].forEach(id => { const el = document.getElementById(id); if (el) { el.src = ''; el.style.display = 'none'; } });
  }

  // ── Upload ────────────────────────────────────────────────────────────
  async function _doUpload() {
    if (!_uploadFile) return;

    if (_opts.type === 'content') {
      const namaInput = document.getElementById('aipNamafileInput');
      if (!(namaInput.value || '').trim()) {
        namaInput.style.borderColor = 'var(--danger, #e05252)';
        namaInput.focus();
        const hint = namaInput.nextElementSibling;
        if (hint) { hint.style.color = 'var(--danger, #e05252)'; hint.textContent = '⚠ Nama foto wajib diisi sebelum upload.'; }
        namaInput.addEventListener('input', function _fix() {
          namaInput.style.borderColor = '';
          if (hint) { hint.style.color = ''; hint.textContent = 'Tulis nama foto tanpa ekstensi.'; }
          namaInput.removeEventListener('input', _fix);
        });
        return;
      }
      const altInput = document.getElementById('aipAltInput');
      if (!(altInput.value || '').trim()) {
        altInput.style.borderColor = 'var(--danger, #e05252)';
        altInput.focus();
        const altHint = altInput.nextElementSibling;
        if (altHint) { altHint.style.color = 'var(--danger, #e05252)'; altHint.textContent = '⚠ Alt text wajib diisi untuk SEO.'; }
        altInput.addEventListener('input', function _fixAlt() {
          altInput.style.borderColor = '';
          if (altHint) { altHint.style.color = ''; altHint.textContent = 'Tulis persis seperti yang ingin ditampilkan.'; }
          altInput.removeEventListener('input', _fixAlt);
        });
        return;
      }
    }

    const btn    = document.getElementById('aipBtnUpload');
    const lbl    = document.getElementById('aipBtnUploadLbl');
    btn.disabled = true;
    lbl.innerHTML = '<span style="display:inline-block;width:12px;height:12px;border:2px solid currentColor;border-top-color:transparent;border-radius:50%;animation:spin .6s linear infinite;vertical-align:middle"></span> Mengupload...';

    document.getElementById('aipProgressWrap').classList.add('show');
    document.getElementById('aipProgressBar').style.width = '10%';

    const fd = new FormData();
    fd.append('image',       _uploadFile);
    fd.append('type',        _opts.type || 'content');
    // [BARU] Kirim preset ukuran yang dipilih user
    fd.append('size_preset', _selectedPresetKey || (_opts.type === 'thumbnail' ? 'thumb_landscape' : 'landscape_wide'));

    if (_opts.type === 'content') {
      const nama = (document.getElementById('aipNamafileInput').value || '').trim();
      if (nama) fd.append('judul', nama);
    } else {
      if (_opts.judul) fd.append('judul', _opts.judul);
    }

    try {
      let pct = 10;
      const tick = setInterval(() => {
        pct = Math.min(pct + 15, 85);
        document.getElementById('aipProgressBar').style.width = pct + '%';
      }, 200);

      const res = await fetch('/admin/api/upload_artikel.php', {
        method: 'POST', body: fd, cache: 'no-store',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-No-SW': '1' }
      });
      clearInterval(tick);
      document.getElementById('aipProgressBar').style.width = '100%';

      const data = await res.json();
      if (data.success) {
        _uploadedUrl = data.url;
        const saved  = data.saved_pct  ? ` · hemat ${data.saved_pct}%`  : '';
        const dim    = data.dimensions ? ` · ${data.dimensions}`         : '';
        const ogNote = (data.og_url && _opts.type === 'thumbnail') ? ' · OG preview ✓' : '';
        document.getElementById('aipUploadFsize').textContent =
          `${data.orig_kb} KB → ${data.size_kb} KB ${data.format}${saved}${dim}${ogNote}`;

        const resultEl = document.getElementById('aipUploadResult');
        resultEl.className   = 'aip-upload-result ok';
        resultEl.textContent = '✓ Upload berhasil! Klik "Gunakan Gambar" untuk melanjutkan.';

        lbl.innerHTML = '✓ Gunakan Gambar';
        btn.disabled  = false;
        btn.onclick   = () => {
          const alt = (_opts.type === 'thumbnail')
            ? (_opts.judul || '')
            : (document.getElementById('aipAltInput')?.value || '').trim();
          const ogUrl = data.og_url || null;
          _opts.onSelect && _opts.onSelect(_uploadedUrl, data.filename, alt, ogUrl);
          ArtikelImagePicker.close();
        };
      } else {
        throw new Error(data.error || 'Upload gagal');
      }
    } catch(e) {
      document.getElementById('aipProgressBar').style.width = '0';
      const resultEl = document.getElementById('aipUploadResult');
      resultEl.className   = 'aip-upload-result err';
      resultEl.textContent = '✗ ' + e.message;
      lbl.innerHTML  = 'Coba Lagi';
      btn.disabled   = false;
      btn.onclick    = ArtikelImagePicker._doUpload;
    }
  }

  // ── Helpers ──────────────────────────────────────────────────────────
  function _setTypeInfo(type) {
    const info = document.getElementById('aipTypeInfo');
    if (!info) return;
    if (type === 'thumbnail') {
      info.innerHTML = '📐 <strong>Mode Thumbnail</strong> — Ukuran disesuaikan preset yang dipilih. Nama file &amp; alt text otomatis dari judul artikel. OG preview <strong>1200×630px</strong> untuk WhatsApp/Facebook dibuat otomatis.';
    } else {
      info.innerHTML = '📄 <strong>Mode Konten</strong> — Ukuran output sesuai preset yang Anda pilih di bawah. Beri nama file dan alt text yang deskriptif.';
    }
  }

  function _updateBtnLabel() {
    const lbl = document.getElementById('aipBtnUploadLbl');
    if (!lbl) return;
    const allP = [...SIZE_PRESETS.content, ...SIZE_PRESETS.thumbnail];
    const p    = allP.find(x => x.key === _selectedPresetKey);
    if (_uploadFile && p) {
      lbl.textContent = `Upload ${p.label} (${p.w}×${p.h})`;
    } else {
      lbl.innerHTML = 'Upload &amp; Kompres';
    }
  }

  function _elTxt(id, txt) {
    const el = document.getElementById(id);
    if (el) el.textContent = txt;
  }

  // ── Public API ────────────────────────────────────────────────────────
  return {
    open(opts) {
      _opts         = opts || {};
      _selectedFile = null;
      _uploadFile   = null;
      _uploadedUrl  = null;
      _currentSim   = 'konten';

      _ensure();

      const titles = { thumbnail: 'Pilih Thumbnail Artikel', content: 'Sisipkan Gambar ke Konten' };
      document.getElementById('aipTitle').textContent = titles[_opts.type] || 'Pilih Gambar Artikel';

      // Update judul di simulasi kartu artikel
      const cardTitle = document.getElementById('aipCardTitle');
      if (cardTitle) cardTitle.textContent = _opts.judul || 'Judul artikel…';

      // Thumbnail → sim kartu; content → sim konten
      const simTabs = document.getElementById('aipSimTabs');
      if (_opts.type === 'thumbnail') {
        if (simTabs) simTabs.style.display = 'none';
        _setSimTab('kartu');
      } else {
        if (simTabs) simTabs.style.display = '';
        _setSimTab('konten');
      }

      // Reset upload panel
      document.getElementById('aipUploadPreview').classList.remove('show');
      document.getElementById('aipProgressWrap').classList.remove('show');
      document.getElementById('aipProgressBar').style.width  = '0';
      document.getElementById('aipUploadResult').className   = 'aip-upload-result';
      document.getElementById('aipUploadResult').textContent = '';
      document.getElementById('aipBtnUpload').disabled       = true;
      document.getElementById('aipBtnUpload').onclick        = ArtikelImagePicker._doUpload;
      document.getElementById('aipBtnUploadLbl').innerHTML   = 'Upload &amp; Kompres';
      document.getElementById('aipFileInput').value          = '';
      _hidePreviewImg();

      // Reset preview panel ke kondisi tersembunyi setiap kali modal dibuka
      const _colsEl  = document.getElementById('aipUploadCols');
      const _rightEl = document.getElementById('aipUploadRight');
      const _togBtn  = document.getElementById('aipTogglePreview');
      if (_colsEl)  _colsEl.classList.add('no-preview');
      if (_rightEl) _rightEl.style.display = 'none';
      if (_togBtn)  _togBtn.innerHTML = '👁️ Tampilkan';

      if (_previewObjectURL) { URL.revokeObjectURL(_previewObjectURL); _previewObjectURL = null; }

      _setTypeInfo(_opts.type);

      // Input nama & alt: hanya content
      document.getElementById('aipNamafileInput').value        = '';
      document.getElementById('aipNamafileWrap').style.display = (_opts.type === 'content') ? 'flex' : 'none';
      document.getElementById('aipAltInput').value             = '';
      document.getElementById('aipAltWrap').style.display      = (_opts.type === 'content') ? 'flex' : 'none';

      // Reset galeri alt
      const galeriAlt = document.getElementById('aipGaleriAltInput');
      if (galeriAlt) galeriAlt.value = '';
      const galeriAltWrap = document.getElementById('aipGaleriAltWrap');
      if (galeriAltWrap) galeriAltWrap.style.display = 'none';

      // Reset galeri selection
      document.getElementById('aipSelPreview').style.display = 'none';
      document.getElementById('aipSelEmpty').style.display   = '';
      document.getElementById('aipBtnPilih').disabled        = true;
      document.getElementById('aipSearch').value             = '';

      // Reset & inisialisasi preset
      _selectedPresetKey = null;
      ArtikelImagePicker._lastPreset = null;
      document.getElementById('aipSizeInfo').style.display = 'none';

      const defaultOrient = 'landscape';
      const defaultPreset = _opts.type === 'thumbnail' ? 'thumb_landscape' : 'landscape_wide';
      _currentOrient = defaultOrient;
      // Render tab orientasi & preset, lalu select default
      _setOrient(defaultOrient);
      _selectPreset(defaultPreset);

      _tab('galeri');
      _loadGaleri();

      document.getElementById('aipOverlay').classList.add('open');
    },

    close() {
      const ov = document.getElementById('aipOverlay');
      if (ov) ov.classList.remove('open');
    },

    // Expose untuk inline onclick
    _tab,
    _filter,
    _loadGaleri,
    _selectItem,
    _confirmGaleri,
    _onFileSelect,
    _doUpload,
    _setOrient,
    _setSimTab,

    // [BARU] Expose preset terakhir yang dipilih → dibaca artikel-editor.php
    _lastPreset: null,
  };
})();
ArtikelImagePicker.togglePreview = function () {
  const cols  = document.getElementById('aipUploadCols');
  const right = document.getElementById('aipUploadRight');
  const btn   = document.getElementById('aipTogglePreview');

  if (!cols || !btn) return;

  const isHidden = cols.classList.contains('no-preview');

  if (isHidden) {
    cols.classList.remove('no-preview');
    if (right) right.style.display = '';
    btn.innerHTML = '🙈 Sembunyikan';
  } else {
    cols.classList.add('no-preview');
    if (right) right.style.display = 'none';
    btn.innerHTML = '👁️ Tampilkan';
  }
};