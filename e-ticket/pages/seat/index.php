<?php
// ============================================================
// INLINE-SEAT.PHP
// Versi khusus yang mem-embed SEMUA CSS & JS secara inline.
// Tidak ada external <link> atau <script src=""> sehingga
// error 525 SSL dari Cloudflare tidak bisa memblokir halaman.
// ============================================================

require_once __DIR__ . '/../../config.php';
$seatStatus = getSeatStatus();

// Pastikan HTML halaman tidak di-cache browser
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

// ── Helper: baca file JS/CSS, fallback string kosong jika tidak ada ──
function readAsset(string $path): string {
    $full = __DIR__ . '/../../' . ltrim($path, '/');
    return file_exists($full) ? file_get_contents($full) : "/* FILE NOT FOUND: {$path} */";
}

// ── Kumpulkan semua CSS ──────────────────────────────────────────────
$cssAll = readAsset('css/style.css')
        . "\n" . readAsset('css/seat.css')
        . "\n" . readAsset('css/seat-improvements.css')
        . "\n" . readAsset('css/seat-zone-cards.css');

// ── Kumpulkan semua JS (urutan sama persis dengan versi asli) ────────
$jsAll  = readAsset('js/seat-memanjang.js')
        . "\n" . readAsset('js/supabase.js')
        . "\n" . readAsset('js/main.js')
        . "\n" . readAsset('js/loading-improvements.js')
        . "\n" . readAsset('js/refresh-guard.js')
        . "\n" . readAsset('js/session-recovery.js');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" href="/favicon.ico?v=2">
    <title>Pilih Tempat Duduk - Berbincang Dengan Romo Eko</title>

    <!-- ✅ SEMUA CSS INLINE — tidak ada request eksternal -->
    <style>
<?= $cssAll ?>

/* ================================================================
   EXPORT PDF BUTTON
   ================================================================ */
.export-pdf-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 14px;
    background: #6366f1;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s, opacity 0.2s, transform 0.1s;
    white-space: nowrap;
    margin-left: auto;
}
.export-pdf-btn:hover {
    background: #4f46e5;
}
.export-pdf-btn:active {
    transform: scale(0.97);
}
.export-pdf-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}
.export-pdf-btn svg {
    flex-shrink: 0;
}

/* Spinner animasi untuk tombol saat loading */
@keyframes exportSpinAnim {
    0%   { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
.export-spin {
    animation: exportSpinAnim 0.9s linear infinite;
}
    </style>
</head>

<body class="seat-selection-page">

    <!-- HEADER -->
    <div class="page-header">
        <button class="back-button" onclick="window.location.href='/e-ticket'">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M19 12H5M12 19l-7-7 7-7"/>
            </svg>
            <span>Kembali</span>
        </button>
        <h1 class="page-title">Pilih Tempat Duduk</h1>

        <!-- ✅ TOMBOL EXPORT PDF -->
        <button class="export-pdf-btn" id="exportPdfBtn" onclick="exportSeatLayoutPDF()">
            <svg id="exportPdfIcon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 16V8M8 12l4 4 4-4"/>
                <rect x="3" y="17" width="18" height="3" rx="1"/>
            </svg>
            <span id="exportBtnText">Export PDF</span>
        </button>
    </div>

    <!-- MAIN CONTENT -->
    <div class="seat-page-container" id="seatPageContainer">

        <!-- LEGEND -->
        <div class="seat-legend">
            <?php
            $legendLabels = [
                'vvip'    => 'VVIP',
                'vip'     => 'VIP',
                'kelas1'  => 'Kelas 1',
                'reguler' => 'Reguler',
                'kelas2'  => 'Reguler',
            ];
            foreach ($ticketPrices as $type => $price): ?>
            <div class="legend-item">
                <div class="legend-color <?= $type ?>"></div>
                <span><?= $legendLabels[$type] ?> - Rp <?= number_format($price, 0, ',', '.') ?></span>
            </div>
            <?php endforeach; ?>
            <div class="legend-item">
                <div class="legend-color selected"></div>
                <span>Dipilih</span>
            </div>
            <div class="legend-item">
                <div class="legend-color sold"></div>
                <span>Terjual</span>
            </div>
        </div>

        <!-- INFO INSTRUKSI -->
        <div class="selection-instruction">
            <div class="instruction-content">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <path d="M12 16v-4"></path>
                    <path d="M12 8h.01"></path>
                </svg>
                <div class="instruction-text">
                    <strong>VVIP & VIP:</strong> Pilih zona - selesaikan pembayaran - pilih kursi yang diinginkan
                    <span class="instruction-separator">•</span>
                    <strong>Kelas 1, Reguler:</strong> Pilih zona - duduk sesuai zona saat datang di venue
                </div>
            </div>
        </div>

        <!-- SEATING LAYOUT -->
        <div class="seat-layout-wrapper" id="seatLayoutWrapper">
            <div class="seat-scroll-container">
                <div class="seat-layout-content">
                    <!-- Stage -->
                    <div class="stage-container">
                        <div class="stage-area">
                            <div class="main-stage">
                                <div class="main-stage-title">PANGGUNG</div>
                            </div>
                        </div>
                        <div class="stage-arrows">
                            <div class="stage-arrow-left">◀</div>
                            <div class="stage-arrow-right">▶</div>
                        </div>
                    </div>

                    <!-- Seating Sections -->
                    <div class="seating-wrapper">
                        <div class="section-container">
                            <div class="section-title"></div>
                            <div id="leftSection" class="section"></div>
                        </div>
                        <div class="section-container">
                            <div class="section-title"></div>
                            <div id="rightSection" class="section"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SELECTION SUMMARY (STICKY BOTTOM) -->
        <div class="selection-summary-bar">
            <div class="summary-info">
                <div class="summary-item">
                    <span class="summary-label">Kursi Dipilih:</span>
                    <span class="summary-value" id="selectedCount">0</span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Total:</span>
                    <span class="summary-price" id="totalPrice">Rp 0</span>
                </div>
            </div>
            <div class="summary-actions">
                <button class="btn-secondary" onclick="clearAllSeats()" id="clearBtn" disabled>Batalkan Pilihan</button>
                <button class="btn-primary" onclick="proceedToBooking()" id="proceedBtn" disabled>Lanjutkan</button>
            </div>
        </div>

        <!-- SELECTED SEATS LIST -->
        <div class="selected-seats-container" id="selectedSeatsContainer" style="display: none;">
            <div class="selected-seats-header">Tempat Duduk yang Dipilih:</div>
            <div class="selected-seats-list" id="selectedSeatsList"></div>
        </div>

    </div>

    <!-- MODAL PEMILIHAN JUMLAH TIKET KELAS -->
    <div class="modal-overlay" id="classTicketModal">
        <div class="modal-container class-ticket-modal">
            <button class="modal-close" onclick="closeClassTicketModal()">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>

            <div class="modal-header">
                <h2 class="modal-title">Pilih Jumlah Tiket</h2>
                <p class="modal-subtitle">Tentukan berapa tiket yang ingin Anda beli untuk setiap kelas</p>
            </div>

            <div class="class-ticket-content">
                <div id="classTicketOptions"></div>

                <div class="class-ticket-summary">
                    <div class="summary-row">
                        <span class="summary-label">Total Tiket Kelas:</span>
                        <span class="summary-value" id="classTicketTotal">0</span>
                    </div>
                    <div class="summary-row total">
                        <span class="summary-label">Estimasi Total:</span>
                        <span class="summary-value" id="classTicketPrice">Rp 0</span>
                    </div>
                </div>

                <div class="class-ticket-actions">
                    <button class="btn-secondary" onclick="closeClassTicketModal()">Batal</button>
                    <button class="btn-primary" onclick="confirmClassTickets()" id="confirmClassBtn">
                        <span>Konfirmasi & Lanjutkan</span>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M5 12h14M12 5l7 7-7 7"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL BOOKING/PAYMENT -->
    <div class="modal-overlay" id="bookingModal">
        <div class="modal-container">
            <button class="modal-close" onclick="closeModal()">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>

            <div class="modal-header">
                <h2 class="modal-title">Data Diri & Pembayaran</h2>
                <div class="progress-steps">
                    <div class="step active" id="step1Indicator">
                        <div class="step-number">1</div>
                        <div class="step-label">Data Diri</div>
                    </div>
                    <div class="step-connector"></div>
                    <div class="step" id="step2Indicator">
                        <div class="step-number">2</div>
                        <div class="step-label">Pembayaran</div>
                    </div>
                </div>
            </div>

            <form id="bookingForm">

                <!-- STEP 1: DATA DIRI -->
                <div class="form-step active" id="step1">
                    <div class="form-content">
                        <div class="ticket-summary">
                            <div class="summary-row">
                                <span class="summary-label">Jumlah Tiket:</span>
                                <span class="summary-value" id="summaryCount">0</span>
                            </div>
                            <div class="summary-row">
                                <span class="summary-label">Tempat Duduk:</span>
                                <span class="summary-value" id="summarySeatNumbers">-</span>
                            </div>
                        </div>

                        <div class="input-group">
                            <label class="input-label">Nama Lengkap (Kota Asal)</label>
                            <input type="text" id="customerName" class="input-field" required placeholder="Masukkan nama + Kota asal anda">
                        </div>

                        <div class="input-group">
                            <label class="input-label">Nomor HP</label>
                            <input type="tel" id="customerPhone" class="input-field" required placeholder="08xxxxxxxxxx">
                        </div>

                        <div class="price-display">
                            <div class="price-label">Total Pembayaran</div>
                            <div class="price-value" id="modalPrice"></div>
                        </div>

                        <button type="button" class="btn-primary" onclick="goToStep2()">
                            <span>Lanjut ke Pembayaran</span>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M5 12h14M12 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- STEP 2: PEMBAYARAN -->
                <div class="form-step" id="step2">
                    <div class="form-content">

                        <div class="payment-card">
                            <div class="payment-header">
                                <div class="payment-icon">💳</div>
                                <h3>Informasi Pembayaran</h3>
                            </div>
                            <p class="payment-instruction">Silakan transfer ke rekening berikut:</p>
                            <div class="bank-info">
                                <div class="bank-row">
                                    <span class="bank-label">Bank</span>
                                    <span class="bank-value"><?= BANK_NAME ?></span>
                                </div>
                                <div class="bank-row">
                                    <span class="bank-label">No. Rekening</span>
                                    <span class="bank-value"><?= BANK_NUMBER ?></span>
                                </div>
                                <div class="bank-row">
                                    <span class="bank-label">Atas Nama</span>
                                    <span class="bank-value"><?= BANK_HOLDER ?></span>
                                </div>
                                <div class="bank-row total">
                                    <span class="bank-label">Nominal Transfer</span>
                                    <span class="bank-value amount" id="paymentAmount"></span>
                                </div>
                                <div class="bank-row" style="background:#EEF2FF;border-radius:8px;padding:8px 12px;margin-top:6px;">
                                    <span style="font-size:.78rem;color:#4338CA;line-height:1.5;">💳 Nominal di atas sudah termasuk kode unik untuk memudahkan verifikasi. Mohon transfer <strong>tepat sesuai nominal tersebut</strong>.</span>
                                </div>
                            </div>
                        </div>

                        <div class="input-group">
                            <label class="input-label">Bukti Transfer</label>
                            <input type="file" id="paymentProof" accept="image/*"
                                   style="display: none;" onchange="handleFileSelect(event)">
                            <div class="upload-box" id="uploadArea" onclick="document.getElementById('paymentProof').click()">
                                <div class="upload-icon">
                                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                        <polyline points="17 8 12 3 7 8"/>
                                        <line x1="12" y1="3" x2="12" y2="15"/>
                                    </svg>
                                </div>
                                <p class="upload-text"><strong>Klik untuk upload bukti pembayaran</strong></p>
                                <p class="upload-hint">Format: JPG, PNG, WEBP — Ukuran berapapun otomatis dikompres</p>
                            </div>
                            <div id="compressToast" style="display:none;align-items:center;gap:10px;background:#EEF2FF;border:1px solid #6366F1;border-radius:10px;padding:10px 14px;margin-top:8px;font-size:13px;color:#4338CA;font-weight:600;">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#6366F1" stroke-width="2" style="flex-shrink:0;animation:spin 1s linear infinite">
                                    <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>
                                </svg>
                                <span>Mengompresi gambar, harap tunggu...</span>
                            </div>
                            <img id="imagePreview" class="image-preview" style="display: none;">
                        </div>

                        <div class="button-group">
                            <button type="button" class="btn-secondary" onclick="goToStep1()">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                                </svg>
                                <span>Kembali</span>
                            </button>
                            <button type="submit" class="btn-primary" id="submitBtn" disabled>
                                <span id="submitText">Selesaikan Pemesanan</span>
                            </button>
                        </div>

                    </div>
                </div>

            </form>

            <div id="successMessage" class="success-message" style="display: none;"></div>
        </div>
    </div>

    <!-- WARNING POPUP -->
    <div id="warningPopup" class="warning-overlay" style="display:none;">
        <div class="warning-container">
            <button class="warning-close" onclick="closeWarning()">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
            <div class="warning-content">
                <div class="warning-icon">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                </div>
                <h3 class="warning-title" id="warningTitle">Peringatan</h3>
                <p class="warning-message" id="warningMessage">Terjadi kesalahan. Silakan coba lagi.</p>
            </div>
        </div>
    </div>

    <!-- ✅ CONFIG dari PHP → JS (inline, tidak ada request ke server) -->
    <?= jsConfig($ticketPrices, $ticketConfig) ?>
    <?= jsSeatConfig() ?>
    <?= jsSeatStatus($seatStatus) ?>

    <!-- ✅ SEMUA JS INLINE — tidak ada request eksternal sama sekali -->
    <script>
<?= $jsAll ?>

// ================================================================
// EXPORT SEAT LAYOUT TO PDF
// Menggunakan html2canvas + jsPDF dari CDN (dimuat on-demand)
// Capture seluruh layout kursi persis seperti tampilan di layar:
//   warna zona, status terjual/tersedia, legend, instruksi — semua.
// ================================================================

function exportSeatLayoutPDF() {
    var btn      = document.getElementById('exportPdfBtn');
    var btnText  = document.getElementById('exportBtnText');
    var btnIcon  = document.getElementById('exportPdfIcon');

    // Tampilkan state loading
    btn.disabled = true;
    btnText.textContent = 'Memproses...';
    btnIcon.innerHTML = '<path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>';
    btnIcon.classList.add('export-spin');

    // ── Muat library CDN secara on-demand ────────────────────────
    function loadScript(src, cb) {
        if (document.querySelector('script[src="' + src + '"]')) { cb(); return; }
        var s    = document.createElement('script');
        s.src    = src;
        s.onload = cb;
        s.onerror = function() {
            _resetExportBtn(btn, btnText, btnIcon);
            alert('Gagal memuat library PDF. Periksa koneksi internet Anda lalu coba lagi.');
        };
        document.head.appendChild(s);
    }

    // html2canvas 1.4.1 tidak support CSS color() function → pakai versi dari npm CDN
    var H2C_SRC   = 'https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js';
    var JSPDF_SRC = 'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js';

    loadScript(H2C_SRC, function() {
        loadScript(JSPDF_SRC, function() {
            _captureAndExport(btn, btnText, btnIcon);
        });
    });
}

// ── Reset tombol ke state semula ─────────────────────────────────
function _resetExportBtn(btn, btnText, btnIcon) {
    btn.disabled = false;
    btnText.textContent = 'Export PDF';
    btnIcon.innerHTML = '<path d="M12 16V8M8 12l4 4 4-4"/><rect x="3" y="17" width="18" height="3" rx="1"/>';
    btnIcon.classList.remove('export-spin');
}

// ================================================================
// SANITIZE UNSUPPORTED COLORS
// html2canvas 1.4.x gagal parse: color(), oklch(), lab(), lch(),
// color-mix(), dan beberapa oklch() dari Tailwind/browser defaults.
// Solusi: walk semua elemen di cloned doc → ambil computed style →
// timpa inline style dengan nilai rgb() / hex yang aman.
// ================================================================
function _sanitizeUnsupportedColors(doc) {
    // Pola warna yang TIDAK didukung html2canvas
    var unsafePattern = /color\(|oklch\(|oklab\(|lab\(|lch\(|color-mix\(/i;

    // Property CSS warna yang perlu dicek & di-override
    var colorProps = [
        'color', 'backgroundColor', 'borderColor',
        'borderTopColor', 'borderRightColor', 'borderBottomColor', 'borderLeftColor',
        'outlineColor', 'textDecorationColor', 'caretColor',
        'fill', 'stroke',
    ];

    // Helper: konversi nilai computed color ke rgb() literal yang aman
    // Browser sudah resolve ke rgb() di getComputedStyle, jadi ini sebagai
    // double-check; kalau masih ada color() → fallback ke transparent / black
    function safeColor(val, prop) {
        if (!val || val === 'none' || val === 'transparent' || val === 'inherit' || val === 'initial') return null;
        if (!unsafePattern.test(val)) return null; // sudah aman, skip

        // Coba baca via canvas untuk konversi (tidak selalu berhasil)
        // Fallback cerdas berdasarkan nama property
        if (prop === 'backgroundColor' || prop === 'background') return 'transparent';
        if (prop === 'color') return '#1a1a1a';
        if (prop === 'borderTopColor' || prop === 'borderRightColor' ||
            prop === 'borderBottomColor' || prop === 'borderLeftColor' ||
            prop === 'borderColor') return 'transparent';
        return 'transparent';
    }

    // Walk semua elemen dalam cloned doc
    var allEls = doc.querySelectorAll('*');
    for (var i = 0; i < allEls.length; i++) {
        var el = allEls[i];
        try {
            // Gunakan getComputedStyle dari window utama karena cloned doc
            // mungkin tidak punya akses ke stylesheet asli
            var cs = window.getComputedStyle(
                document.querySelector('[id="' + el.id + '"]') || document.body
            );

            colorProps.forEach(function(prop) {
                var raw = el.style[prop] || '';
                if (unsafePattern.test(raw)) {
                    var safe = safeColor(raw, prop);
                    if (safe !== null) el.style[prop] = safe;
                }
            });
        } catch(e) { /* skip elemen yang tidak bisa diakses */ }
    }

    // Patch <style> tags di dalam cloned doc:
    // Ganti semua occurrence color(...) di inline <style> dengan fallback
    var styleTags = doc.querySelectorAll('style');
    for (var j = 0; j < styleTags.length; j++) {
        var css = styleTags[j].innerHTML;
        if (unsafePattern.test(css)) {
            // Replace color(display-p3 ...) → fallback
            css = css.replace(/color\([^)]+\)/g, 'inherit');
            css = css.replace(/oklch\([^)]+\)/g, 'inherit');
            css = css.replace(/oklab\([^)]+\)/g, 'inherit');
            css = css.replace(/lab\([^)]+\)/g, 'inherit');
            css = css.replace(/lch\([^)]+\)/g, 'inherit');
            css = css.replace(/color-mix\([^)]+\)/g, 'inherit');
            styleTags[j].innerHTML = css;
        }
    }
}

// ── Proses capture DOM → PDF ─────────────────────────────────────
function _captureAndExport(btn, btnText, btnIcon) {

    // Elemen yang TIDAK perlu masuk PDF
    var excludeSelectors = [
        '.selection-summary-bar',
        '.selected-seats-container',
        '#bookingModal',
        '#classTicketModal',
        '#warningPopup',
        '.export-pdf-btn',
        '.back-button',
    ];

    // Simpan & sembunyikan elemen yang dikecualikan
    var hiddenEls      = [];
    var originalStyles = [];
    excludeSelectors.forEach(function(sel) {
        var el = document.querySelector(sel);
        if (el) {
            hiddenEls.push(el);
            originalStyles.push(el.style.display);
            el.style.display = 'none';
        }
    });

    // Simpan & perluas scroll container agar semua kursi ter-capture
    var wrapper    = document.getElementById('seatLayoutWrapper');
    var scrollEl   = document.querySelector('.seat-scroll-container');
    var contentEl  = document.querySelector('.seat-layout-content');

    var savedWrapper  = wrapper  ? { overflow: wrapper.style.overflow,  maxHeight: wrapper.style.maxHeight,  height: wrapper.style.height  } : null;
    var savedScroll   = scrollEl ? { overflow: scrollEl.style.overflow, overflowX: scrollEl.style.overflowX, width: scrollEl.style.width   } : null;
    var savedScrollLeft = scrollEl ? scrollEl.scrollLeft : 0;

    if (wrapper)  { wrapper.style.overflow = 'visible'; wrapper.style.maxHeight = 'none'; wrapper.style.height = 'auto'; }
    if (scrollEl) { scrollEl.style.overflow = 'visible'; scrollEl.style.overflowX = 'visible'; scrollEl.style.width = 'auto'; scrollEl.scrollLeft = 0; }

    // Target elemen yang di-capture: seluruh page-container
    var target = document.getElementById('seatPageContainer') || document.querySelector('.seat-page-container') || document.body;

    // Tunggu sebentar agar DOM selesai reflow sebelum capture
    setTimeout(function() {

        html2canvas(target, {
            scale            : 2,           // retina quality
            useCORS          : true,
            allowTaint       : true,
            backgroundColor  : '#f8fafc',
            scrollX          : 0,
            scrollY          : -window.scrollY,
            windowWidth      : Math.max(target.scrollWidth, window.innerWidth),
            width            : target.scrollWidth,
            height           : target.scrollHeight,
            logging          : false,

            // Konfigurasi tambahan pada clone DOM sebelum di-render
            onclone: function(clonedDoc) {

                // ── Sanitasi semua warna CSS yang tidak didukung html2canvas ──
                _sanitizeUnsupportedColors(clonedDoc);

                // Pastikan layout wrapper tidak clip di clone
                var cWrapper = clonedDoc.getElementById('seatLayoutWrapper');
                if (cWrapper) {
                    cWrapper.style.overflow  = 'visible';
                    cWrapper.style.maxHeight = 'none';
                    cWrapper.style.height    = 'auto';
                }

                var cScroll = clonedDoc.querySelector('.seat-scroll-container');
                if (cScroll) {
                    cScroll.style.overflow  = 'visible';
                    cScroll.style.overflowX = 'visible';
                    cScroll.style.width     = 'auto';
                }

                var cContent = clonedDoc.querySelector('.seat-layout-content');
                if (cContent) {
                    cContent.style.transform = 'none';
                    cContent.style.width     = 'auto';
                    cContent.style.minWidth  = 'unset';
                }

                // Sembunyikan elemen yang dikecualikan di clone juga
                excludeSelectors.forEach(function(sel) {
                    var el = clonedDoc.querySelector(sel);
                    if (el) el.style.display = 'none';
                });

                // Tampilkan judul page di clone (header tanpa tombol)
                var cHeader = clonedDoc.querySelector('.page-header');
                if (cHeader) {
                    cHeader.style.paddingBottom = '12px';
                    cHeader.style.marginBottom  = '8px';
                }
            }

        }).then(function(canvas) {

            // ── Restore semua perubahan DOM ──────────────────────
            hiddenEls.forEach(function(el, i) { el.style.display = originalStyles[i]; });

            if (wrapper && savedWrapper) {
                wrapper.style.overflow  = savedWrapper.overflow;
                wrapper.style.maxHeight = savedWrapper.maxHeight;
                wrapper.style.height    = savedWrapper.height;
            }
            if (scrollEl && savedScroll) {
                scrollEl.style.overflow  = savedScroll.overflow;
                scrollEl.style.overflowX = savedScroll.overflowX;
                scrollEl.style.width     = savedScroll.width;
                scrollEl.scrollLeft      = savedScrollLeft;
            }

            // ── Buat PDF ─────────────────────────────────────────
            var imgData = canvas.toDataURL('image/jpeg', 0.95);
            var imgW    = canvas.width;
            var imgH    = canvas.height;

            // Lebar PDF = A4 (210mm), tinggi proporsional mengikuti konten
            var pdfW = 210;
            var pdfH = Math.ceil((imgH / imgW) * pdfW);
            // Minimal setinggi A4 portrait
            if (pdfH < 297) pdfH = 297;

            // Inisialisasi jsPDF (UMD global)
            var JsPDF = (window.jspdf && window.jspdf.jsPDF) ? window.jspdf.jsPDF : window.jsPDF;
            var doc   = new JsPDF({
                orientation : 'portrait',
                unit        : 'mm',
                format      : [pdfW, pdfH]
            });

            // Gambar layout kursi memenuhi seluruh halaman
            var renderedH = (imgH / imgW) * pdfW;
            doc.addImage(imgData, 'JPEG', 0, 0, pdfW, renderedH);

            // Footer kecil: judul + tanggal cetak
            var today = new Date().toLocaleDateString('id-ID', {
                day   : '2-digit',
                month : 'long',
                year  : 'numeric'
            });
            doc.setFontSize(7);
            doc.setTextColor(160, 160, 160);
            doc.text(
                'Layout Tempat Duduk \u2014 Berbincang Dengan Romo Eko  |  Dicetak: ' + today,
                pdfW / 2,
                pdfH - 3,
                { align: 'center' }
            );

            // Simpan file
            doc.save('layout-tempat-duduk-romo-eko.pdf');

            // ── Reset tombol ─────────────────────────────────────
            _resetExportBtn(btn, btnText, btnIcon);

        }).catch(function(err) {
            console.error('[Export PDF] html2canvas error:', err);

            // Restore DOM meskipun error
            hiddenEls.forEach(function(el, i) { el.style.display = originalStyles[i]; });
            if (wrapper && savedWrapper) {
                wrapper.style.overflow  = savedWrapper.overflow;
                wrapper.style.maxHeight = savedWrapper.maxHeight;
                wrapper.style.height    = savedWrapper.height;
            }
            if (scrollEl) { scrollEl.scrollLeft = savedScrollLeft; }

            _resetExportBtn(btn, btnText, btnIcon);
            alert('Gagal mengekspor PDF. Silakan coba lagi.');
        });

    }, 250); // 250ms delay — beri waktu reflow DOM
}
    </script>

</body>
</html>