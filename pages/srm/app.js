/* ================================================================
   StokPro - app.js
   Handles: Barcode scan, Lokasi Rak search, Rupiah format, Submit
   ================================================================ */

'use strict';

// ================================================================
// LOKASI RAK SEARCH ENGINE
// ================================================================
const LETTERS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';

function searchLokasiRak(query) {
    if (!query || query.length < 1) return { items: [], total: 0 };

    query = query.toUpperCase().trim();
    const results = [];
    const MAX = 80;

    for (let i = 0; i < 26 && results.length < MAX; i++) {
        for (let j = 0; j < 26 && results.length < MAX; j++) {
            for (let k = 0; k < 100 && results.length < MAX; k++) {
                const loc = LETTERS[i] + LETTERS[j] + '-' + String(k).padStart(2, '0');
                if (loc.startsWith(query)) {
                    results.push(loc);
                }
            }
        }
    }

    // Count total matches (estimate)
    let total = 0;
    for (let i = 0; i < 26; i++) {
        for (let j = 0; j < 26; j++) {
            for (let k = 0; k < 100; k++) {
                const loc = LETTERS[i] + LETTERS[j] + '-' + String(k).padStart(2, '0');
                if (loc.startsWith(query)) total++;
            }
        }
    }

    return { items: results.slice(0, MAX), total };
}

// ================================================================
// RUPIAH FORMATTER
// ================================================================
function formatRupiah(value) {
    if (!value && value !== 0) return '';
    const num = parseInt(String(value).replace(/\D/g, ''), 10);
    if (isNaN(num)) return '';
    return num.toLocaleString('id-ID');
}

function parseRupiah(formatted) {
    return parseInt(String(formatted).replace(/\./g, '').replace(/,/g, ''), 10) || 0;
}

function formatRupiahFull(num) {
    if (!num || isNaN(num)) return '';
    return 'Rp ' + num.toLocaleString('id-ID');
}

// ================================================================
// NOTIFICATION
// ================================================================
function showNotif(type, msg) {
    const el = document.getElementById('notification');
    el.className = 'notification ' + type;
    el.querySelector('.notif-msg').textContent = msg;
    el.querySelector('.notif-icon').innerHTML = type === 'success'
        ? `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><polyline points="20 6 9 17 4 12"/></svg>`
        : `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>`;
    el.style.display = 'flex';
    setTimeout(() => {
        el.style.opacity = '0';
        el.style.transition = 'opacity 0.4s';
        setTimeout(() => {
            el.style.display = 'none';
            el.style.opacity = '1';
            el.style.transition = '';
        }, 400);
    }, 4000);
}

// ================================================================
// RESET FORM
// ================================================================
function resetForm() {
    document.getElementById('barangForm').reset();
    document.getElementById('harga_beli').value = '';
    document.getElementById('harga_jual').value = '';
    document.getElementById('lokasi_rak').value = '';
    document.getElementById('preview_beli').textContent = '';
    document.getElementById('preview_jual').textContent = '';
    const prevKI = document.getElementById('preview_kode_internal');
    if (prevKI) prevKI.textContent = '';
    document.getElementById('marginCard').style.display = 'none';
    document.getElementById('kode_barang').focus();
}

// ================================================================
// MARGIN CALCULATOR
// ================================================================
function updateMargin() {
    const beli = parseInt(document.getElementById('harga_beli').value) || 0;
    const jual = parseInt(document.getElementById('harga_jual').value) || 0;

    if (beli > 0 && jual > 0) {
        const selisih = jual - beli;
        const margin = ((selisih / beli) * 100).toFixed(1);

        const card = document.getElementById('marginCard');
        const marginVal = document.getElementById('marginValue');
        const selisihVal = document.getElementById('selisihValue');

        card.style.display = 'flex';
        marginVal.textContent = (selisih >= 0 ? '+' : '') + margin + '%';
        marginVal.className = 'margin-value' + (selisih < 0 ? ' negative' : '');
        selisihVal.textContent = (selisih >= 0 ? '+' : '') + formatRupiahFull(selisih);
        selisihVal.className = 'margin-value' + (selisih < 0 ? ' negative' : '');
    } else {
        document.getElementById('marginCard').style.display = 'none';
    }
}

// ================================================================
// ENTER KEY NAVIGATION
// ================================================================
function setupEnterNavigation() {
    const inputs = document.querySelectorAll('input[data-next]');
    if (!inputs.length) return; // not on this page
    inputs.forEach(input => {
        input.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const nextId = this.getAttribute('data-next');
                if (nextId) {
                    const next = document.getElementById(nextId);
                    if (next) next.focus();
                }
            }
        });
    });
}

// ================================================================
// BARCODE SCANNER
// ================================================================
function setupBarcodeScanner() {
    const input = document.getElementById('kode_barang');
    if (!input) return; // not on this page
    const scanPulse = document.getElementById('scanPulse');

    // --- Barcode scanner detection ---
    // Scanner devices typically type all chars very fast then send Enter.
    // We track keystroke timing: if multiple chars arrive in < 50ms each,
    // it's likely a scanner (not a human typing).
    let lastKeyTime = 0;
    let keystrokeCount = 0;
    let scanCheckTimer = null;
    const SCAN_SPEED_THRESHOLD_MS = 50; // chars faster than this = scanner
    const SCAN_MIN_LENGTH = 4;           // minimum chars to consider a scan

    function showScanSuccess(value) {
        // Green flash on the input
        input.style.borderColor = 'var(--green)';
        input.style.boxShadow = '0 0 0 3px rgba(52,211,153,0.25)';
        input.style.background = 'rgba(52,211,153,0.05)';

        // Pulse animation
        if (scanPulse) {
            scanPulse.classList.add('active');
        }

        // Show scan badge notification near the input
        showScanBadge(value);

        setTimeout(() => {
            input.style.borderColor = '';
            input.style.boxShadow = '';
            input.style.background = '';
            if (scanPulse) scanPulse.classList.remove('active');
        }, 800);
    }

    function showScanBadge(value) {
        // Remove existing badge if any
        const existing = document.getElementById('scanBadge');
        if (existing) existing.remove();

        const badge = document.createElement('div');
        badge.id = 'scanBadge';
        badge.style.cssText = `
            position: absolute;
            top: -32px;
            left: 0;
            background: var(--green, #34D399);
            color: #fff;
            font-size: 11px;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 4px;
            letter-spacing: 0.03em;
            pointer-events: none;
            z-index: 20;
            white-space: nowrap;
            box-shadow: 0 2px 8px rgba(52,211,153,0.4);
            animation: fadeInDown 0.2s ease;
        `;
        badge.textContent = '✓ Barcode terbaca: ' + value;

        // Insert into input-wrapper
        const wrapper = input.closest('.input-wrapper') || input.parentElement;
        if (wrapper) {
            wrapper.style.position = 'relative';
            wrapper.appendChild(badge);
        }

        setTimeout(() => {
            badge.style.opacity = '0';
            badge.style.transition = 'opacity 0.3s';
            setTimeout(() => badge.remove(), 300);
        }, 2500);
    }

    // Track keystroke timing to detect scanner vs manual
    input.addEventListener('keydown', function (e) {
        const now = Date.now();
        const timeDiff = now - lastKeyTime;

        if (e.key !== 'Enter' && e.key !== 'Tab' && e.key.length === 1) {
            if (timeDiff < SCAN_SPEED_THRESHOLD_MS) {
                keystrokeCount++;
            } else {
                // Reset count on slow keystrokes (human typing)
                keystrokeCount = 1;
            }
            lastKeyTime = now;
        }

        if (e.key === 'Enter') {
            e.preventDefault();
            const value = this.value.trim();
            if (!value) return;

            // Determine if this was a scan (fast input) or manual entry
            const isScan = keystrokeCount >= SCAN_MIN_LENGTH;

            if (isScan) {
                showScanSuccess(value);
            } else {
                // Manual entry: simple border flash
                this.style.borderColor = 'var(--blue, #60A5FA)';
                this.style.boxShadow = '0 0 0 3px rgba(96,165,250,0.15)';
                setTimeout(() => {
                    this.style.borderColor = '';
                    this.style.boxShadow = '';
                }, 500);
            }

            // Either way, move to next field
            setTimeout(() => {
                document.getElementById('kode_internal').focus();
            }, 100);

            // Reset counter
            keystrokeCount = 0;
        }
    });

    // Also handle paste (e.g. copy-paste a barcode manually)
    input.addEventListener('paste', function (e) {
        setTimeout(() => {
            const value = this.value.trim();
            if (value.length >= SCAN_MIN_LENGTH) {
                showScanSuccess(value);
                setTimeout(() => document.getElementById('kode_internal').focus(), 200);
            }
        }, 10);
    });

    // Reset keystroke counter when field is cleared/refocused
    input.addEventListener('focus', function () {
        keystrokeCount = 0;
        lastKeyTime = 0;
    });
}

// ================================================================
// LOKASI RAK DROPDOWN
// ================================================================
function setupLokasiRakDropdown() {
    const wrapper = document.getElementById('lokasiWrapper');
    if (!wrapper) return; // not on this page
    const input = document.getElementById('lokasi_rak_input');
    const hidden = document.getElementById('lokasi_rak');
    const dropdown = document.getElementById('lokasiDropdown');
    let highlightIdx = -1;

    function renderDropdown(query) {
        if (!query) {
            dropdown.innerHTML = `
                <div class="dropdown-hint">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="20" height="20">
                        <circle cx="11" cy="11" r="8"/>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                    Ketik kode rak (contoh: AB, AB-05, CA-12)
                </div>`;
            highlightIdx = -1;
            return;
        }

        const { items, total } = searchLokasiRak(query);
        highlightIdx = -1;

        if (items.length === 0) {
            dropdown.innerHTML = `<div class="dropdown-hint">Tidak ada hasil untuk "<strong>${query}</strong>"</div>`;
            return;
        }

        const html = items.map((item, i) => `
            <div class="dropdown-item" data-value="${item}" data-idx="${i}">
                <div class="item-icon">${item.substring(0,2)}</div>
                ${item}
            </div>
        `).join('');

        const moreText = total > items.length
            ? `<div class="dropdown-count">Menampilkan ${items.length} dari ${total} lokasi. Ketik lebih spesifik.</div>`
            : `<div class="dropdown-count">${total} lokasi ditemukan</div>`;

        dropdown.innerHTML = html + moreText;

        // Click events
        dropdown.querySelectorAll('.dropdown-item').forEach(item => {
            item.addEventListener('mousedown', function (e) {
                e.preventDefault();
                selectItem(this.getAttribute('data-value'));
            });
        });
    }

    function selectItem(value) {
        input.value = value;
        hidden.value = value;
        wrapper.classList.remove('open');
        input.focus();

        // Move to next field
        const next = document.getElementById('stok');
        if (next) setTimeout(() => next.focus(), 50);
    }

    function getHighlightedItems() {
        return dropdown.querySelectorAll('.dropdown-item');
    }

    input.addEventListener('input', function () {
        wrapper.classList.add('open');
        hidden.value = '';
        renderDropdown(this.value);
    });

    input.addEventListener('focus', function () {
        wrapper.classList.add('open');
        if (!this.value) renderDropdown('');
        else renderDropdown(this.value);
    });

    input.addEventListener('blur', function () {
        setTimeout(() => wrapper.classList.remove('open'), 150);
    });

    input.addEventListener('keydown', function (e) {
        const items = getHighlightedItems();

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            highlightIdx = Math.min(highlightIdx + 1, items.length - 1);
            items.forEach((el, i) => el.classList.toggle('highlighted', i === highlightIdx));
            if (items[highlightIdx]) items[highlightIdx].scrollIntoView({ block: 'nearest' });
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            highlightIdx = Math.max(highlightIdx - 1, 0);
            items.forEach((el, i) => el.classList.toggle('highlighted', i === highlightIdx));
            if (items[highlightIdx]) items[highlightIdx].scrollIntoView({ block: 'nearest' });
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (highlightIdx >= 0 && items[highlightIdx]) {
                selectItem(items[highlightIdx].getAttribute('data-value'));
            } else if (items.length === 1) {
                selectItem(items[0].getAttribute('data-value'));
            }
        } else if (e.key === 'Escape') {
            wrapper.classList.remove('open');
        } else if (e.key === 'Tab') {
            wrapper.classList.remove('open');
        }
    });
}

// ================================================================
// CURRENCY INPUTS
// ================================================================
function setupCurrencyInputs() {
    if (!document.getElementById('harga_beli_display')) return; // not on this page
    const pairs = [
        { display: 'harga_beli_display', hidden: 'harga_beli', preview: 'preview_beli' },
        { display: 'harga_jual_display', hidden: 'harga_jual', preview: 'preview_jual' }
    ];

    pairs.forEach(({ display, hidden, preview }) => {
        const displayEl = document.getElementById(display);
        const hiddenEl = document.getElementById(hidden);
        const previewEl = document.getElementById(preview);

        displayEl.addEventListener('input', function () {
            // Remove all non-digits
            let raw = this.value.replace(/\D/g, '');
            const num = parseInt(raw, 10) || 0;

            // Format with dots
            const formatted = num === 0 ? '' : num.toLocaleString('id-ID');
            this.value = formatted;

            // Store raw value
            hiddenEl.value = num || '';

            // Preview
            previewEl.textContent = num > 0 ? '= Rp ' + num.toLocaleString('id-ID') : '';

            updateMargin();
        });

        // Handle pasting
        displayEl.addEventListener('paste', function (e) {
            e.preventDefault();
            const pasted = (e.clipboardData || window.clipboardData).getData('text');
            const raw = pasted.replace(/\D/g, '');
            const num = parseInt(raw, 10) || 0;
            this.value = num > 0 ? num.toLocaleString('id-ID') : '';
            hiddenEl.value = num || '';
            previewEl.textContent = num > 0 ? '= Rp ' + num.toLocaleString('id-ID') : '';
            updateMargin();
        });

        // Enter key
        displayEl.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const nextId = this.getAttribute('data-next');
                if (nextId) {
                    const next = document.getElementById(nextId);
                    if (next) next.focus();
                }
            }
        });
    });
}

// ================================================================
// FORM SUBMIT
// ================================================================
function setupFormSubmit() {
    const form = document.getElementById('barangForm');
    if (!form) return; // not on this page
    const submitBtn = document.getElementById('submitBtn');

    form.addEventListener('submit', async function (e) {
        e.preventDefault();

        // Validate required fields
        const kodeBarang = document.getElementById('kode_barang').value.trim();
        const namaProduk = document.getElementById('nama_produk').value.trim();

        if (!kodeBarang) {
            showNotif('error', 'Kode Barang wajib diisi atau di-scan.');
            document.getElementById('kode_barang').focus();
            return;
        }

        if (!namaProduk) {
            showNotif('error', 'Nama Produk wajib diisi.');
            document.getElementById('nama_produk').focus();
            return;
        }

        // Build data object
        const data = {
            kode_barang:   document.getElementById('kode_barang').value.trim(),
            kode_internal: document.getElementById('kode_internal').value.trim(),
            nama_produk:   document.getElementById('nama_produk').value.trim(),
            kategori:      document.getElementById('kategori').value.trim(),
            lokasi_rak:    document.getElementById('lokasi_rak').value.trim()
                           || document.getElementById('lokasi_rak_input').value.trim(),
            stok:          document.getElementById('stok').value.trim() || '0',
            supplier:      document.getElementById('supplier').value.trim(),
            harga_beli:    document.getElementById('harga_beli').value.trim() || '0',
            harga_jual:    document.getElementById('harga_jual').value.trim() || '0',
        };

        // Loading state
        submitBtn.classList.add('loading');
        submitBtn.disabled = true;

        try {
            const response = await fetch('submit.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.success) {
                showNotif('success', `✓ Data "${data.nama_produk}" berhasil disimpan ke spreadsheet!`);
                resetForm();
            } else {
                showNotif('error', 'Gagal menyimpan: ' + (result.message || 'Terjadi kesalahan'));
            }
        } catch (err) {
            showNotif('error', 'Koneksi error: ' + err.message);
        } finally {
            submitBtn.classList.remove('loading');
            submitBtn.disabled = false;
        }
    });
}

// ================================================================
// INIT
// ================================================================
document.addEventListener('DOMContentLoaded', function () {
    setupEnterNavigation();
    setupBarcodeScanner();
    setupLokasiRakDropdown();
    setupCurrencyInputs();
    setupFormSubmit();


    // Focus kode_barang on load
    document.getElementById('kode_barang')?.focus();
});