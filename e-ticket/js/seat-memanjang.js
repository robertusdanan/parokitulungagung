// ========================================
// SEAT-MEMANJANG.JS  (v2 — layout menyerong)
// ----------------------------------------
// Baris 1-3  : MENYERONG ke panggung
//              Kolom lebih sedikit, di-offset agar simetris mengarah tengah
// Baris 4+   : Normal (lurus), 25 kursi/sisi
//
// VVIP / VIP : Klik kursi individual, tampil status sold/available
// Kelas lain : Overlay card per zona (klik untuk pilih jumlah)
// ========================================

// ── LABELS & WARNA (dibaca dari ticketConfig jika tersedia) ─────────
function buildLabelsFromConfig() {
    const cfg = window.ticketConfig || [];
    const labels = {};
    const colors = {};
    cfg.forEach(t => {
        labels[t.type] = t.name;
        colors[t.type] = t.color;
    });
    return { labels, colors };
}

// Fallback defaults
const defaultLabels = { vvip:'VVIP', vip:'VIP', kelas1:'Kelas 1', reguler:'Reguler', kelas2:'Reguler', kelas3:'Reguler' };
const defaultColors = { vvip:'#ffcf32', vip:'#da0424', kelas1:'#6B8E23', reguler:'#4682B4', kelas2:'#4682B4', kelas3:'#4682B4' };

function getSeatLabel(type) {
    return (window.ticketConfig || []).find(t => t.type === type)?.name ?? defaultLabels[type] ?? type;
}
function getSeatColor(type) {
    return (window.ticketConfig || []).find(t => t.type === type)?.color ?? defaultColors[type] ?? '#888';
}

// Mana saja tipe yang pakai seat individual (VVIP+VIP = baris 1-8 default)
function isIndividualZone(type) {
    const cfg = window.ticketConfig || [];
    if (!cfg.length) return type === 'vvip' || type === 'vip';
    // Individual = 2 tipe pertama dalam config
    return cfg.indexOf(cfg.find(t => t.type === type)) < 2;
}

const sectionLabels = { L: 'Section A', R: 'Section B' };
const sectionNames  = { L: 'Blok Kiri',  R: 'Blok Kanan' };

// ── LAYOUT CONSTANTS (dari PHP via jsConfig) ────────────────────────
// ANGLED_ROWS : { "1": 15, "2": 15, "3": 20 }   (baris => kolom per sisi)
// NORMAL_COLS : 25
// TOTAL_ROWS  : 25

function getAngledRows() { return window.ANGLED_ROWS || { 1:13, 2:17, 3:20 }; }
function getNormalCols() { return window.NORMAL_COLS || 25; }
function getTotalRows()  { return window.TOTAL_ROWS  || 25; }

function getColsForRow(row) {
    const ar = getAngledRows();
    return ar[row] ?? getNormalCols();
}

// ── GLOBAL STATE ────────────────────────────────────────────────────
let selectedSeats        = new Set();
let bookedSeats          = new Set();
let selectedZoneSections = new Set();
let currentTotalPrice    = 0;

// ── ZONA LOCK — peserta hanya boleh memilih 1 zona per pemesanan ──
// Null = belum ada zona dipilih. Terisi setelah pertama kali pilih kursi/zona.
let lockedZone = null;

function getLockedZone()      { return lockedZone; }
function setLockedZone(zone)  { lockedZone = zone; }
function clearLockedZone()    { lockedZone = null; }

/**
 * Cek apakah zona boleh dipilih.
 * Jika lockedZone null → boleh (zona apapun).
 * Jika lockedZone terisi → hanya boleh jika sama dengan zona yang sedang dikunci.
 * Jika berbeda → tampilkan peringatan dan return false.
 */
function isZoneAllowed(zone) {
    if (!lockedZone) return true;
    if (lockedZone === zone) return true;
    const lockedLabel = getSeatLabel(lockedZone);
    const tryLabel    = getSeatLabel(zone);
    showWarning(
        'Zona Berbeda',
        `Anda sudah memilih zona ${lockedLabel}. Harap selesaikan pemesanan ini terlebih dahulu, atau hapus pilihan (Clear) untuk mengganti zona ke ${tryLabel}.`
    );
    return false;
}

window.selectedSeatsData   = [];
window.randomAssignedSeats = [];
window.classSectionTickets = {};

// Kuota zona (Kelas 1 / Reguler) yang sudah terjual, dari DB
// key: "kelas1-L", "kelas1-R", "reguler-L", "reguler-R", dst.
window.zoneSoldCounts = {};

// ── HELPER: hitung sudut serong per baris ──────────────────────────
// Baris paling dekat panggung (row=1) → sudut paling tajam
// Baris terakhir angled → sudut ringan
function getRowAngle(row, angledRows, lastAngledRow) {
    const maxAngle = 20; // derajat maksimum (baris 1)
    const minAngle = 4;  // derajat minimum (baris terakhir angled)
    if (lastAngledRow <= 1) return maxAngle;
    // Interpolasi: baris 1 → max, baris lastAngledRow → min
    const t = (row - 1) / (lastAngledRow - 1);
    return Math.round(maxAngle - t * (maxAngle - minAngle));
}

// ========================================
// RENDER SEATING LAYOUT
// ========================================
function renderSeatingLayout() {
    const leftSection  = document.getElementById('leftSection');
    const rightSection = document.getElementById('rightSection');
    if (!leftSection || !rightSection) return;

    leftSection.innerHTML  = '';
    rightSection.innerHTML = '';

    const totalRows  = getTotalRows();
    const normalCols = getNormalCols();
    const angledRows = getAngledRows();

    // ── KIRI ────────────────────────────────────────────────────────

    // Header kolom — hanya untuk baris normal (baris 4+)
    const leftHeader = document.createElement('div');
    leftHeader.className = 'row-container column-header';
    leftHeader.appendChild(makeRowNumEl(''));
    const lhSeats = document.createElement('div');
    lhSeats.className = 'seats-row';
    lhSeats.appendChild(makeColumnNumbers(normalCols, Math.ceil(normalCols / 2) + 1, true));
    lhSeats.appendChild(makeColumnNumbers(Math.ceil(normalCols / 2), 1, true));
    leftHeader.appendChild(lhSeats);
    leftSection.appendChild(leftHeader);

    const angledRowNums = Object.keys(angledRows).map(Number);
    const lastAngledRow = angledRowNums.length ? Math.max(...angledRowNums) : 0;
    let dividerAddedLeft = false;

    // Label zona VVIP (di atas baris menyerong)
    if (lastAngledRow > 0) {
        const vvipLabel = document.createElement('div');
        vvipLabel.className = 'vvip-zone-label';
        vvipLabel.innerHTML = _buildZoneBadgeHTML('vvip');
        leftSection.appendChild(vvipLabel);
    }

    for (let row = 1; row <= totalRows; row++) {
        const cols      = getColsForRow(row);
        const isAngled  = !!angledRows[row];
        const zoneClass = getZoneClass(row);

        // Tambahkan divider tepat setelah baris angled terakhir
        if (!isAngled && !dividerAddedLeft && lastAngledRow > 0) {
            const divider = document.createElement('div');
            divider.className = 'vvip-divider';
            leftSection.appendChild(divider);

            // Label "VIP Zone" di bawah divider
            const vipLabel = document.createElement('div');
            vipLabel.className = 'vip-zone-label';
            vipLabel.innerHTML = _buildZoneBadgeHTML('vip');
            leftSection.appendChild(vipLabel);

            dividerAddedLeft = true;
        }

        const rowContainer = document.createElement('div');
        rowContainer.className = 'row-container' + (isAngled ? ' angled-row' : '');
        if (isAngled) {
            rowContainer.dataset.angledRow = row;
            rowContainer.dataset.angledOf  = lastAngledRow;
        }

        // Row number: untuk baris angled, miring ke kanan (mengarah panggung di sisi kiri)
        const rowNumEl = makeRowNumEl(row);
        if (isAngled) {
            // Sisi kiri: row number miring ke kiri (ujung kiri naik = rotate negatif)
            const angleDeg = getRowAngle(row, angledRows, lastAngledRow);
            rowNumEl.style.transform = `rotate(-${angleDeg}deg)`;
            rowNumEl.style.transformOrigin = 'center center';
            rowNumEl.classList.add('angled-row-num');
        }
        rowContainer.appendChild(rowNumEl);

        const seatsRow = document.createElement('div');
        seatsRow.className = 'seats-row';

        if (isAngled) {
            // Baris menyerong: satu blok saja, di-offset agar mengarah ke tengah (panggung)
            // Sisi kiri: kursi digeser ke kanan (mendekati lorong tengah)
            const offset = normalCols - cols; // berapa banyak kursi yang "hilang" di kiri
            const spacer = document.createElement('div');
            spacer.className = 'angled-spacer';
            spacer.style.width = (offset * 22) + 'px'; // 22px = lebar seat + gap
            seatsRow.appendChild(spacer);

            const block = makeSeatBlock(zoneClass, 'L', 'angled');
            block.classList.add('angled-block');
            for (let seat = cols; seat >= 1; seat--)
                block.appendChild(createSeatElement('L', row, seat, zoneClass));
            seatsRow.appendChild(block);
        } else {
            // Baris normal: 2 blok, kanan dulu lalu kiri (sisi kiri)
            const half = Math.ceil(cols / 2);
            const rightBlock = makeSeatBlock(zoneClass, 'L', 'right');
            for (let seat = cols; seat >= half + 1; seat--)
                rightBlock.appendChild(createSeatElement('L', row, seat, zoneClass));

            const leftBlock = makeSeatBlock(zoneClass, 'L', 'left');
            for (let seat = half; seat >= 1; seat--)
                leftBlock.appendChild(createSeatElement('L', row, seat, zoneClass));

            seatsRow.appendChild(rightBlock);
            seatsRow.appendChild(leftBlock);
        }

        rowContainer.appendChild(seatsRow);
        leftSection.appendChild(rowContainer);
    }

    // ── KANAN ───────────────────────────────────────────────────────

    const rightHeader = document.createElement('div');
    rightHeader.className = 'row-container column-header';
    rightHeader.appendChild(makeRowNumEl(''));
    const rhSeats = document.createElement('div');
    rhSeats.className = 'seats-row';
    rhSeats.appendChild(makeColumnNumbers(1, Math.ceil(normalCols / 2), false));
    rhSeats.appendChild(makeColumnNumbers(Math.ceil(normalCols / 2) + 1, normalCols, false));
    rightHeader.appendChild(rhSeats);
    rightSection.appendChild(rightHeader);

    // Label zona VVIP untuk sisi kanan
    if (lastAngledRow > 0) {
        const vvipLabelR = document.createElement('div');
        vvipLabelR.className = 'vvip-zone-label';
        vvipLabelR.innerHTML = _buildZoneBadgeHTML('vvip');
        rightSection.appendChild(vvipLabelR);
    }

    let dividerAddedRight = false;

    for (let row = 1; row <= totalRows; row++) {
        const cols      = getColsForRow(row);
        const isAngled  = !!angledRows[row];
        const zoneClass = getZoneClass(row);

        // Tambahkan divider tepat setelah baris angled terakhir
        if (!isAngled && !dividerAddedRight && lastAngledRow > 0) {
            const divider = document.createElement('div');
            divider.className = 'vvip-divider';
            rightSection.appendChild(divider);

            // Label "VIP Zone" di bawah divider
            const vipLabelR = document.createElement('div');
            vipLabelR.className = 'vip-zone-label';
            vipLabelR.innerHTML = _buildZoneBadgeHTML('vip');
            rightSection.appendChild(vipLabelR);

            dividerAddedRight = true;
        }

        const rowContainer = document.createElement('div');
        rowContainer.className = 'row-container' + (isAngled ? ' angled-row' : '');
        if (isAngled) {
            rowContainer.dataset.angledRow = row;
            rowContainer.dataset.angledOf  = lastAngledRow;
        }

        // Row number: untuk baris angled sisi kanan, miring ke kanan (ujung kanan naik)
        const rowNumEl2 = makeRowNumEl(row);
        if (isAngled) {
            const angleDeg = getRowAngle(row, angledRows, lastAngledRow);
            rowNumEl2.style.transform = `rotate(${angleDeg}deg)`;
            rowNumEl2.style.transformOrigin = 'center center';
            rowNumEl2.classList.add('angled-row-num');
        }
        rowContainer.appendChild(rowNumEl2);

        const seatsRow = document.createElement('div');
        seatsRow.className = 'seats-row';

        if (isAngled) {
            // Sisi kanan: kursi digeser ke kiri (mendekati lorong tengah)
            const block = makeSeatBlock(zoneClass, 'R', 'angled');
            block.classList.add('angled-block');
            for (let seat = 1; seat <= cols; seat++)
                block.appendChild(createSeatElement('R', row, seat, zoneClass));
            seatsRow.appendChild(block);

            const offset = normalCols - cols;
            const spacer = document.createElement('div');
            spacer.className = 'angled-spacer';
            spacer.style.width = (offset * 22) + 'px';
            seatsRow.appendChild(spacer);
        } else {
            const half = Math.ceil(cols / 2);
            const leftBlock = makeSeatBlock(zoneClass, 'R', 'left');
            for (let seat = 1; seat <= half; seat++)
                leftBlock.appendChild(createSeatElement('R', row, seat, zoneClass));

            const rightBlock = makeSeatBlock(zoneClass, 'R', 'right');
            for (let seat = half + 1; seat <= cols; seat++)
                rightBlock.appendChild(createSeatElement('R', row, seat, zoneClass));

            seatsRow.appendChild(leftBlock);
            seatsRow.appendChild(rightBlock);
        }

        rowContainer.appendChild(seatsRow);
        rightSection.appendChild(rowContainer);
    }

    // Inject CSS untuk baris menyerong jika belum ada
    injectAngledCSS();
}

// ── CSS DINAMIS untuk layout menyerong trapesium ────────────────────
function injectAngledCSS() {
    const existing = document.getElementById('angled-style');
    if (existing) existing.remove();
    const style = document.createElement('style');
    style.id = 'angled-style';
    style.textContent = `
        /* ── Wrapper angled row ── */
        .angled-row { position: relative; }
        .angled-block { display: flex; align-items: flex-end; }
        .angled-spacer { display: inline-block; flex-shrink: 0; }

        /* ── Row number menyerong ── */
        .angled-row-num {
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.2s;
        }

        /* ── VVIP Section: arah serong MENUJU PANGGUNG (ke tengah/atas) ──
         *
         * Panggung ada di ATAS TENGAH.
         * Sisi KIRI : ujung KIRI (luar) naik → pivot sudut kanan-bawah
         *             rotate negatif = berlawanan jarum jam = kiri naik, kanan tetap
         * Sisi KANAN: ujung KANAN (luar) naik → pivot sudut kiri-bawah
         *             rotate positif = searah jarum jam = kanan naik, kiri tetap
         */

/* =========================
   DESKTOP (default)
========================= */

/* kode desktop Anda tetap */
#leftSection  .row-container[data-angled-row="1"] .seat-block {
    transform: rotate(12deg);
    transform-origin: 100% 600%;
    margin-left: -4px;
}
#rightSection .row-container[data-angled-row="1"] .seat-block {
    transform: rotate(-12deg);
    transform-origin: 0% 100%;
    margin-right: -4px;
}

#leftSection  .row-container[data-angled-row="2"] .seat-block {
    transform: rotate(10deg);
    transform-origin: 100% 200%;
    margin-left: -2px;
}
#rightSection .row-container[data-angled-row="2"] .seat-block {
    transform: rotate(-10deg);
    transform-origin: 0% 100%;
    margin-right: -2px;
}

#leftSection  .row-container[data-angled-row="3"] .seat-block {
    transform: rotate(8deg);
    transform-origin: 100% -100%;
}
#rightSection .row-container[data-angled-row="3"] .seat-block {
    transform: rotate(-8deg);
    transform-origin: 0% 100%;
}


/* =========================
   MOBILE / TABLET
========================= */
@media (max-width: 768px) {

    /* Baris 1 */
    #leftSection .row-container[data-angled-row="1"] .seat-block {
        transform: rotate(7deg);
        transform-origin: 100% -1400%;
        margin-left: -2px;
    }

    #rightSection .row-container[data-angled-row="1"] .seat-block {
        transform: rotate(-7deg);
        transform-origin: 0% 100%;
        margin-right: -2px;
    }

    /* Baris 2 */
    #leftSection .row-container[data-angled-row="2"] .seat-block {
        transform: rotate(5deg);
        transform-origin: 100% -2000%;
        margin-left: -1px;
    }

    #rightSection .row-container[data-angled-row="2"] .seat-block {
        transform: rotate(-5deg);
        transform-origin: 0% 100%;
        margin-right: -1px;
    }

    /* Baris 3 */
    #leftSection .row-container[data-angled-row="3"] .seat-block {
        transform: rotate(3deg);
        transform-origin: 100% -3500%;
    }

    #rightSection .row-container[data-angled-row="3"] .seat-block {
        transform: rotate(-3deg);
        transform-origin: 0% 100%;
    }
}

        /* ── Garis pemisah visual antara VVIP dan baris normal ── */
        .vvip-divider {
            width: 100%;
            height: 2px;
            background: linear-gradient(90deg, transparent, #ffcf32 20%, #ffcf32 80%, transparent);
            margin: 6px 0 10px;
            border-radius: 2px;
            opacity: 0.7;
        }

        /* ── Label "VVIP Zone" menyerong sesuai arah blok ── */
        .vvip-zone-label {
            text-align: center;
            font-size: 0.65rem;
            font-weight: 800;
            letter-spacing: 2px;
            color: #b8860b;
            margin-bottom: 4px;
            text-transform: uppercase;
            opacity: 0.8;
        }
        /* Sisi kiri: label miring ke kiri (ujung kiri naik) */
        #leftSection  .vvip-zone-label {
            transform: rotate(8deg);
            transform-origin: center center;
            display: block;
        }
        /* Sisi kanan: label miring ke kanan (simetris) */
        #rightSection .vvip-zone-label {
            transform: rotate(-8deg);
            transform-origin: center center;
            display: block;
        }
        
        /* MOBILE */
@media (max-width: 768px) {
    #leftSection .vvip-zone-label,
    #rightSection .vvip-zone-label {
        position: relative;
        top: -22px;   /* naikkan ke atas */
        margin-bottom: 8px;
        z-index: 20;
    }
}

        /* ── Label "VIP Zone" di bawah divider ── */
        .vip-zone-label {
            text-align: center;
            font-size: 0.65rem;
            font-weight: 800;
            letter-spacing: 2px;
            color: #da0424;
            margin-bottom: 6px;
            margin-top: 2px;
            text-transform: uppercase;
            opacity: 0.85;
        }
        #leftSection  .vip-zone-label { display: block; }
        #rightSection .vip-zone-label { display: block; }

        /* ── Panah penunjuk dari VVIP ke panggung ── */
        .stage-container {
            position: relative;
        }
        .stage-arrows {
            display: flex;
            justify-content: space-between;
            padding: 0 40px;
            margin-top: 6px;
            margin-bottom: -4px;
        }
        .stage-arrow-left,
        .stage-arrow-right {
            font-size: 1.1rem;
            color: #b8860b;
            opacity: 0.6;
            line-height: 1;
        }
        .stage-arrow-left  { transform: rotate(-30deg); }
        .stage-arrow-right { transform: rotate(30deg); }
    `;
    document.head.appendChild(style);
}

// ── CSS: Tooltip kursi VVIP/VIP terkunci ─────────────────────
(function() {
    if (document.getElementById('seat-locked-tooltip-style')) return;
    const s = document.createElement('style');
    s.id = 'seat-locked-tooltip-style';
    s.textContent = `
        /* Overlay indikator VVIP/VIP: kursor info */
        .seat.zone-vvip, .seat.zone-vip {
            cursor: help !important;
        }
        .seat.zone-vvip::after, .seat.zone-vip::after {
            content: '🔒';
            position: absolute;
            top: -2px; right: -2px;
            font-size: 7px;
            line-height: 1;
            pointer-events: none;
        }

        /* Tooltip "pilih setelah bayar" */
        .seat-locked-tooltip {
            position: fixed;
            z-index: 9999;
            pointer-events: none;
            opacity: 0;
            transform: translate(-50%, -100%) translateY(4px);
            transition: opacity .2s ease, transform .2s ease;
        }
        .seat-locked-tooltip.slt-show {
            opacity: 1;
            transform: translate(-50%, -100%) translateY(0);
        }
        .slt-body {
            background: #1a1a2e;
            border: 1.5px solid var(--slt-color, #ffd700);
            border-radius: 10px;
            padding: 8px 13px;
            text-align: center;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 11.5px;
            color: #e2e8f0;
            line-height: 1.5;
            box-shadow: 0 8px 24px rgba(0,0,0,.5);
            white-space: nowrap;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 2px;
        }
        .slt-body::after {
            content: '';
            position: absolute;
            bottom: -6px;
            left: 50%;
            transform: translateX(-50%);
            border-left: 6px solid transparent;
            border-right: 6px solid transparent;
            border-top: 6px solid var(--slt-color, #ffd700);
        }
        .slt-lock { font-size: 14px; }

        /* Banner zona VVIP/VIP di bawah label baris */
        .vvip-pay-banner, .vip-pay-banner {
            text-align: center;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .5px;
            padding: 3px 8px;
            border-radius: 4px;
            margin-top: 2px;
            pointer-events: none;
        }
        .vvip-pay-banner { background: rgba(255,215,0,.15); color: #ffd700; }
        .vip-pay-banner  { background: rgba(218,4,36,.12);  color: #da0424; }
    `;
    document.head.appendChild(s);
})();


// ── HELPERS ─────────────────────────────────────────────────────────

function makeRowNumEl(text) {
    const el = document.createElement('div');
    el.className   = 'row-number';
    el.textContent = text;
    return el;
}

function makeColumnNumbers(from, to, descending) {
    const block = document.createElement('div');
    block.className = 'seat-block column-numbers';
    const range = descending
        ? Array.from({ length: from - to + 1 }, (_, i) => from - i)
        : Array.from({ length: to - from + 1 }, (_, i) => from + i);
    range.forEach(n => {
        const el = document.createElement('div');
        el.className   = 'column-number';
        el.textContent = n;
        block.appendChild(el);
    });
    return block;
}

function makeSeatBlock(zoneClass, section, side) {
    const block = document.createElement('div');
    block.className = `seat-block ${zoneClass}`;
    block.dataset.zone      = zoneClass.replace('zone-', '');
    block.dataset.section   = section;
    block.dataset.blockSide = side;
    return block;
}

function getZoneClass(row) {
    // Baca dari ticketConfig: cari tipe yang rowStart <= row <= rowEnd
    const cfg = window.ticketConfig || [];
    for (const t of cfg) {
        if (row >= (t.rowStart || 1) && row <= (t.rowEnd || 999))
            return `zone-${t.type}`;
    }
    // Fallback default
    if (row <= 3)  return 'zone-vvip';
    if (row <= 8)  return 'zone-vip';
    if (row <= 15) return 'zone-kelas1';
    return 'zone-reguler';
}

function createSeatElement(section, row, seatNum, zoneClass) {
    const seatId  = `${section}-${row}-${seatNum}`;
    const seatDiv = document.createElement('div');
    seatDiv.className      = 'seat';
    seatDiv.dataset.seatId = seatId;

    const zone = seatConfig ? seatConfig[seatId] : null;
    if (!zone) {
        seatDiv.classList.add('masked');
        seatDiv.style.pointerEvents = 'none';
        return seatDiv;
    }

    const individual = isIndividualZone(zone);

    if (individual) {
        // VVIP / VIP: klik individual, tampil status sold/available
        seatDiv.title = `${section === 'L' ? 'Kiri' : 'Kanan'} Baris ${row} No ${seatNum}`;
        // Cek apakah zona ini di-archive oleh admin
        if (window.seatStatus?.[zone]?.closed === true) {
            seatDiv.classList.add('archived-zone');
            seatDiv.style.pointerEvents = 'none';
            seatDiv.style.opacity = '0.35';
            seatDiv.style.cursor  = 'not-allowed';
            seatDiv.title = `${getSeatLabel(zone)} — Penjualan ditutup sementara`;
        } else if (bookedSeats.has(seatId)) {
            seatDiv.classList.add('sold');
            seatDiv.style.pointerEvents = 'none';
        } else if (isIndividualZone(zone)) {
            // VVIP/VIP: klik diizinkan tapi hanya tampilkan tooltip info
            seatDiv.classList.add('vvip-vip-locked');
            seatDiv.style.cursor = 'help';
            seatDiv.title = `${getSeatLabel(zone)} — Pilih kursi setelah pembayaran selesai`;
            seatDiv.addEventListener('click', () => handleSeatClick(seatId));
        } else {
            seatDiv.addEventListener('click', () => handleSeatClick(seatId));
            const tooltip = document.createElement('div');
            tooltip.className = 'seat-tooltip';
            tooltip.innerHTML = `<strong>${getSeatLabel(zone)}</strong><br>${section === 'L' ? 'Kiri' : 'Kanan'} – Baris ${row} – No. ${seatNum}<br>${formatPrice(window.ticketPrices[zone])}`;
            seatDiv.appendChild(tooltip);
        }
    } else {
        // Kelas lainnya: uniform appearance, overlay card
        seatDiv.classList.add('masked');
        seatDiv.style.pointerEvents = 'none';
    }

    return seatDiv;
}

// ========================================
// ZONE OVERLAY CARDS (untuk kelas non-individual)
// ========================================
function setupZoneClickHandlers() {
    const cfg = window.ticketConfig || [];
    // Ambil tipe yang bukan individual (baris ke-3 dan seterusnya)
    const overlayTypes = cfg.slice(2).map(t => t.type);
    if (!overlayTypes.length) return;

    ['L', 'R'].forEach(section => {
        const sectionEl = section === 'L'
            ? document.getElementById('leftSection')
            : document.getElementById('rightSection');
        if (!sectionEl) return;
        sectionEl.style.position = 'relative';
        overlayTypes.forEach(type => placeOverlayCard(type, section, sectionEl));
    });
}

function placeOverlayCard(type, section, sectionEl) {
    const blocks = sectionEl.querySelectorAll(`.seat-block.zone-${type}`);
    if (!blocks.length) return;

    const parentRect = sectionEl.getBoundingClientRect();
    let minTop = Infinity, maxBottom = -Infinity, minLeft = Infinity, maxRight = -Infinity;

    blocks.forEach(block => {
        const r = block.getBoundingClientRect();
        minTop    = Math.min(minTop,    r.top    - parentRect.top    + sectionEl.scrollTop);
        maxBottom = Math.max(maxBottom, r.bottom - parentRect.top    + sectionEl.scrollTop);
        minLeft   = Math.min(minLeft,   r.left   - parentRect.left);
        maxRight  = Math.max(maxRight,  r.right  - parentRect.left);
    });

    const cardId   = `zone-card-${type}-${section}`;
    const existing = document.getElementById(cardId);
    if (existing) existing.remove();

    const sectionKey = `${type}-${section}`;
    const available  = getAvailableSeatsForSection(type, section).length;
    const isSelected = selectedZoneSections.has(sectionKey);
    const selCount   = window.classSectionTickets[sectionKey] || 0;
    const color      = getSeatColor(type);
    const label      = getSeatLabel(type);
    const PAD = 6;

    const card = document.createElement('div');
    card.id        = cardId;
    card.className = `zone-overlay-card${isSelected ? ' selected' : ''}`;
    card.dataset.zone    = type;
    card.dataset.section = section;

    Object.assign(card.style, {
        position      : 'absolute',
        top           : `${minTop    - PAD}px`,
        left          : `${minLeft   - PAD}px`,
        width         : `${maxRight  - minLeft + PAD * 2}px`,
        height        : `${maxBottom - minTop  + PAD * 2}px`,
        zIndex        : '10',
        pointerEvents : 'auto',
        cursor        : 'pointer',
        boxSizing     : 'border-box',
        '--zoc-color' : color,
    });

    const isClosed = window.seatStatus?.[type]?.closed === true;
    card.innerHTML = buildCardHTML(type, section, available, isSelected, selCount, color, label, isClosed);
    if (isClosed) {
        card.style.cursor = 'not-allowed';
    } else {
        card.addEventListener('click', () => showTicketCountModal(type, section));
    }
    sectionEl.appendChild(card);
}

function buildCardHTML(type, section, available, isSelected, selCount, color, label, isClosed = false) {
    if (isClosed) {
        return `
        <div class="zoc-inner" style="opacity:.6;filter:grayscale(.5);">
            <div class="zoc-top-row">
                <span class="zoc-pill" style="border-color:${color}66;">${sectionLabels[section]}</span>
                <span style="font-size:10px;font-weight:700;letter-spacing:.4px;background:#fff7ed;color:#c2410c;border:1px solid #fed7aa;border-radius:4px;padding:2px 7px;">PENJUALAN DITUTUP</span>
            </div>
            <div class="zoc-name" style="color:${color};">${label}</div>
            <div class="zoc-sub">${sectionNames[section]}</div>
            <div class="zoc-sep" style="background:${color}44;"></div>
            <div class="zoc-avail">
                <span class="zoc-count" style="color:${color};">${available}</span>
                <span class="zoc-label">kursi tersedia</span>
            </div>
            <div class="zoc-cta" style="border-color:#fed7aa;color:#c2410c;background:#fff7ed;cursor:not-allowed;">
                &#9940; Penjualan Ditutup Sementara
            </div>
        </div>
        `;
    }
    return `
        <div class="zoc-inner${isSelected ? ' is-selected' : ''}">
            <div class="zoc-top-row">
                <span class="zoc-pill" style="border-color:${color}66;">${sectionLabels[section]}</span>
                ${isSelected ? `<span class="zoc-checkmark" style="background:${color};">&#10003;</span>` : ''}
            </div>
            <div class="zoc-name" style="color:${color};">${label}</div>
            <div class="zoc-sub">${sectionNames[section]}</div>
            <div class="zoc-sep" style="background:${color}44;"></div>
            <div class="zoc-avail">
                <span class="zoc-count" style="color:${color};">${available}</span>
                <span class="zoc-label">kursi tersedia</span>
            </div>
            <div class="zoc-cta${isSelected ? ' is-selected' : ''}"
                 style="${isSelected ? `background:${color}22;border-color:${color}66;color:${color};` : `border-color:${color}55;color:${color};`}">
                ${isSelected ? `&#10003; ${selCount} tiket dipilih &mdash; klik untuk ubah` : 'Klik untuk memilih'}
            </div>
        </div>
    `;
}

function refreshOverlayCard(type, section) {
    const sectionEl = section === 'L'
        ? document.getElementById('leftSection')
        : document.getElementById('rightSection');
    if (sectionEl) placeOverlayCard(type, section, sectionEl);
}

function refreshAllOverlayCards() {
    const cfg = window.ticketConfig || [];
    const overlayTypes = cfg.slice(2).map(t => t.type);
    ['L', 'R'].forEach(section => {
        const sectionEl = section === 'L'
            ? document.getElementById('leftSection')
            : document.getElementById('rightSection');
        if (sectionEl) overlayTypes.forEach(type => placeOverlayCard(type, section, sectionEl));
    });
}

// ========================================
// SEAT CLICK HANDLER (individual VVIP/VIP)
// ========================================
// ALUR BARU: Kursi VVIP/VIP dipilih SETELAH pembayaran.
// Klik pada kursi di halaman seat hanya menampilkan tooltip informatif.
// Pemilihan kursi sesungguhnya terjadi di modal seatPickerModal (main.js).
function handleSeatClick(seatId) {
    const zone = seatConfig[seatId];
    if (!zone) return;
    if (window.seatStatus?.[zone]?.closed === true) return;
    const seatDiv = document.querySelector(`[data-seat-id="${seatId}"]`);
    if (!seatDiv || seatDiv.classList.contains('sold')) return;

    // Zona VVIP/VIP: tampilkan tooltip "pilih setelah bayar"
    if (isIndividualZone(zone)) {
        showSeatLockedTooltip(seatDiv, zone);
        return;
    }

    // Zona lain (Kelas 1, Reguler): logika normal
    if (selectedSeats.has(seatId)) {
        selectedSeats.delete(seatId);
        seatDiv.classList.remove('selected');
        currentTotalPrice -= window.ticketPrices[zone];
        window.selectedSeatsData = window.selectedSeatsData.filter(d => d.seatId !== seatId);
        if (selectedSeats.size === 0 && Object.values(window.classSectionTickets).every(v => v === 0)) {
            clearLockedZone();
        }
    } else {
        if (!isZoneAllowed(zone)) return;
        selectedSeats.add(seatId);
        seatDiv.classList.add('selected');
        window.selectedSeatsData.push({ seatId, zone, label: getSeatLabel(zone), price: window.ticketPrices[zone] });
        currentTotalPrice += window.ticketPrices[zone];
        setLockedZone(zone);
    }
    updateSelectionDisplay();
}

// Tooltip informatif untuk kursi VVIP/VIP yang belum bisa dipilih
function showSeatLockedTooltip(seatDiv, zone) {
    document.querySelectorAll('.seat-locked-tooltip').forEach(el => el.remove());
    const color = getSeatColor(zone);
    const label = getSeatLabel(zone);
    const tip = document.createElement('div');
    tip.className = 'seat-locked-tooltip';
    tip.innerHTML = `
        <div class="slt-body" style="--slt-color:${color};">
            <span class="slt-lock">🔒</span>
            <strong style="color:${color};">Zona ${label}</strong>
            <span>Pilih kursi setelah<br>pembayaran selesai</span>
        </div>`;
    const rect = seatDiv.getBoundingClientRect();
    Object.assign(tip.style, {
        position: 'fixed',
        left:     `${rect.left + rect.width / 2}px`,
        top:      `${rect.top - 6}px`,
        transform:'translate(-50%, -100%)',
        zIndex:   '9999',
        pointerEvents: 'none',
    });
    document.body.appendChild(tip);
    requestAnimationFrame(() => tip.classList.add('slt-show'));
    setTimeout(() => {
        tip.classList.remove('slt-show');
        setTimeout(() => tip.remove(), 250);
    }, 2200);
}
// ========================================
// MODAL PILIH JUMLAH TIKET KELAS
// ========================================
function showTicketCountModal(type, section) {
    // Cek apakah tiket ini di-archive oleh admin
    if (window.seatStatus?.[type]?.closed === true) return;
    // Cek apakah zona overlay ini sesuai dengan zona yang sedang dikunci
    if (!isZoneAllowed(type)) return;

    const sectionKey = `${type}-${section}`;
    const available  = getAvailableSeatsForSection(type, section).length;
    const color      = getSeatColor(type);
    const label      = getSeatLabel(type);
    const price      = window.ticketPrices[type] || 0;
    const current    = window.classSectionTickets[sectionKey] || 0;

    const modal = document.getElementById('classTicketModal');
    const opts  = document.getElementById('classTicketOptions');
    if (!modal || !opts) return;

    opts.innerHTML = `
        <div style="padding:16px;background:${color}11;border-radius:12px;margin-bottom:16px;">
            <div style="display:flex;align-items:center;gap:12px;">
                <div style="width:16px;height:16px;border-radius:4px;background:${color};"></div>
                <div>
                    <strong style="color:${color};font-size:16px;">${label} — ${sectionNames[section]}</strong>
                    <div style="font-size:13px;color:#888;margin-top:2px;">${formatPrice(price)} per kursi · ${available} kursi tersedia</div>
                </div>
            </div>
        </div>
        <div style="display:flex;align-items:center;gap:16px;justify-content:center;padding:8px 0;">
            <button onclick="changeClassCount(-1,'${type}','${section}')"
                style="width:40px;height:40px;border-radius:50%;border:1.5px solid ${color};background:transparent;color:${color};font-size:22px;cursor:pointer;display:flex;align-items:center;justify-content:center;">−</button>
            <span id="classCountDisplay" style="font-size:28px;font-weight:600;min-width:40px;text-align:center;">${current}</span>
            <button onclick="changeClassCount(1,'${type}','${section}')"
                style="width:40px;height:40px;border-radius:50%;border:1.5px solid ${color};background:${color};color:#fff;font-size:22px;cursor:pointer;display:flex;align-items:center;justify-content:center;">+</button>
        </div>
        <div style="font-size:12px;color:#aaa;text-align:center;margin-top:4px;">Maks. ${available} kursi</div>
    `;

    modal.dataset.currentZone    = type;
    modal.dataset.currentSection = section;
    modal.dataset.available      = available;
    updateClassTicketSummary();

    modal.style.display = 'flex';
    setTimeout(() => modal.classList.add('active'), 10);
}

function changeClassCount(delta, type, section) {
    const modal    = document.getElementById('classTicketModal');
    const available = parseInt(modal.dataset.available || 0);
    const sectionKey = `${type}-${section}`;
    let current    = window.classSectionTickets[sectionKey] || 0;
    current = Math.max(0, Math.min(available, current + delta));
    window.classSectionTickets[sectionKey] = current;

    const display = document.getElementById('classCountDisplay');
    if (display) display.textContent = current;
    updateClassTicketSummary();
}

function updateClassTicketSummary() {
    const modal = document.getElementById('classTicketModal');
    if (!modal) return;
    const type    = modal.dataset.currentZone;
    const section = modal.dataset.currentSection;
    const key     = `${type}-${section}`;
    const count   = window.classSectionTickets[key] || 0;
    const price   = window.ticketPrices[type] || 0;

    const totalEl = document.getElementById('classTicketTotal');
    const priceEl = document.getElementById('classTicketPrice');
    const confirmBtn = document.getElementById('confirmClassBtn');
    if (totalEl) totalEl.textContent = count;
    if (priceEl) priceEl.textContent = formatPrice(count * price);
    if (confirmBtn) confirmBtn.disabled = (count === 0);
}

function confirmClassTickets() {
    const modal   = document.getElementById('classTicketModal');
    if (!modal) return;
    const type    = modal.dataset.currentZone;
    const section = modal.dataset.currentSection;
    const key     = `${type}-${section}`;
    const count   = window.classSectionTickets[key] || 0;

    // ── VALIDASI: cek status closed terbaru dari seatStatus ──
    if (window.seatStatus?.[type]?.closed === true) {
        closeClassTicketModal();
        showWarning('Penjualan Ditutup', 'Penjualan tiket jenis ini sedang ditutup sementara. Silakan pilih kategori tiket lain.');
        // Reset hitungan jika sudah dipilih sebelumnya
        window.classSectionTickets[key] = 0;
        selectedZoneSections.delete(key);
        const allZeroOverlay = Object.values(window.classSectionTickets).every(v => v === 0);
        if (allZeroOverlay && selectedSeats.size === 0) clearLockedZone();
        updateSelectionDisplay();
        return;
    }

    if (count > 0) {
        selectedZoneSections.add(key);
        // Kunci zona setelah konfirmasi tiket zona
        setLockedZone(type);
    } else {
        selectedZoneSections.delete(key);
        // Jika semua zona overlay sudah 0 dan tidak ada kursi individual → buka kunci
        const allZeroOverlay = Object.values(window.classSectionTickets).every(v => v === 0);
        if (allZeroOverlay && selectedSeats.size === 0) clearLockedZone();
    }

    closeClassTicketModal();
    refreshOverlayCard(type, section);
    assignRandomSeatsForSection(type, section, count);
    updateSelectionDisplay();
}

function closeClassTicketModal() {
    const modal = document.getElementById('classTicketModal');
    if (modal) {
        modal.classList.remove('active');
        setTimeout(() => { modal.style.display = 'none'; }, 300);
    }
}

// ========================================
// QUOTA CHECK (kelas overlay — tanpa nomor kursi)
// ========================================
function getAvailableSeatsForSection(type, section) {
    if (!seatConfig) return [];

    // Semua kursi fisik zona ini di section ini
    const totalSeats = Object.keys(seatConfig).filter(seatId =>
        seatConfig[seatId] === type && seatId.startsWith(section + '-')
    );

    // Zona overlay (Kelas 1, Reguler, dst.): kurangi dengan sold dari DB
    const cfg      = window.ticketConfig || [];
    const typeIdx  = cfg.findIndex(t => t.type === type);
    if (typeIdx >= 2) {
        const soldFromDB = window.zoneSoldCounts[`${type}-${section}`] || 0;
        const physicalAvail = Math.max(0, totalSeats.length - soldFromDB);
        // Batasi oleh open_quota (kuota yang dibuka ke publik) per section (L & R = masing2 setengah)
        const openQuotaTotal = window.seatStatus?.[type]?.open_quota ?? totalSeats.length * 2;
        const openQuotaPerSection = Math.ceil(openQuotaTotal / 2);
        const available = Math.max(0, Math.min(physicalAvail, openQuotaPerSection - soldFromDB));
        return totalSeats.slice(0, available); // array dummy panjang = available
    }

    // Zona individual (VVIP/VIP): pakai bookedSeats
    return totalSeats.filter(seatId => !bookedSeats.has(seatId));
}

function assignRandomSeatsForSection(type, section, count) {
    // Zona Kelas 1 & Reguler: tidak ada nomor kursi, hanya catat zona & jumlah
    const key = `${type}-${section}`;

    // Hapus entry lama untuk zona ini dari randomAssignedSeats
    window.randomAssignedSeats = window.randomAssignedSeats.filter(
        d => !(d.zone === type && d.section === section)
    );

    if (count === 0) { updateSelectionDisplay(); return; }

    // Cek ketersediaan berdasarkan kuota (tanpa assign seat ID spesifik)
    const available = getAvailableSeatsForSection(type, section);
    if (available.length < count) {
        showWarning('Kuota Tidak Cukup',
            `Hanya tersedia ${available.length} kursi di ${getSeatLabel(type)} ${sectionLabels[section]}.`);
        return;
    }

    // Simpan sebagai zona saja — TANPA seatId individual
    for (let i = 0; i < count; i++) {
        window.randomAssignedSeats.push({
            seatId  : null,          // tidak ada nomor kursi
            zone    : type,
            section : section,
            label   : `${getSeatLabel(type)} ${sectionLabels[section]}`,
            price   : window.ticketPrices[type] || 0,
            noSeat  : true,          // flag: tiket zona, bukan kursi individual
        });
    }
    // Tidak menambahkan ke selectedSeats (yang berbasis seatId) karena tidak ada ID kursi
    updateSelectionDisplay();
}

// ========================================
// SELECTION DISPLAY
// ========================================
function updateSelectionDisplay() {
    const total = window.selectedSeatsData.length +
        Object.values(window.classSectionTickets).reduce((s, c) => s + c, 0);

    let totalPrice = 0;
    window.selectedSeatsData.forEach(d    => totalPrice += d.price);
    window.randomAssignedSeats.forEach(d  => totalPrice += d.price);
    currentTotalPrice = totalPrice;

    const elCount    = document.getElementById('selectedCount');
    const elZone     = document.getElementById('zoneCount');
    const elPrice    = document.getElementById('totalPrice');
    const clearBtn   = document.getElementById('clearBtn');
    const proceedBtn = document.getElementById('proceedBtn');

    if (elCount)    elCount.textContent  = total;
    if (elPrice)    elPrice.textContent  = formatPrice(totalPrice);
    if (clearBtn)   clearBtn.disabled    = (total === 0);
    if (proceedBtn) proceedBtn.disabled  = (total === 0);
    if (elZone)     elZone.innerHTML     = generateClassBreakdown() || '0';

    updateSelectedSeatsList();
}

function generateClassBreakdown() {
    const parts = [];
    const cfg   = window.ticketConfig || [];

    // Individual zones (2 pertama)
    cfg.slice(0, 2).forEach(t => {
        const cnt = window.selectedSeatsData.filter(d => d.zone === t.type).length;
        if (cnt) parts.push(`${t.name}: ${cnt} Tiket`);
    });

    // Overlay zones (sisa)
    cfg.slice(2).forEach(t => {
        ['L', 'R'].forEach(section => {
            const cnt = window.classSectionTickets[`${t.type}-${section}`] || 0;
            if (cnt) parts.push(`${t.name} ${sectionLabels[section]}: ${cnt} Tiket`);
        });
    });
    return parts.join(', ');
}

function updateSelectedSeatsList() {
    const container = document.getElementById('selectedSeatsContainer');
    const list      = document.getElementById('selectedSeatsList');
    if (!container || !list) return;

    const zoneTotal = Object.values(window.classSectionTickets).reduce((s, c) => s + c, 0);
    const total = window.selectedSeatsData.length + zoneTotal;
    if (total === 0) { container.style.display = 'none'; return; }

    container.style.display = 'block';
    list.innerHTML = '';

    // VVIP / VIP — kursi individual dengan nomor
    window.selectedSeatsData.forEach(data => {
        const item = document.createElement('div');
        item.className = 'selected-seat-item';
        item.innerHTML = `
            <span class="seat-label">${data.label} – ${data.seatId}</span>
            <span class="seat-price">${formatPrice(data.price)}</span>
        `;
        list.appendChild(item);
    });

    // Kelas 1 / Reguler — zona saja, tanpa nomor kursi
    const cfg = window.ticketConfig || [];
    cfg.slice(2).forEach(t => {
        ['L', 'R'].forEach(section => {
            const cnt = window.classSectionTickets[`${t.type}-${section}`] || 0;
            if (!cnt) return;
            const item = document.createElement('div');
            item.className = 'selected-seat-item zone-seats';
            item.innerHTML = `
                <span class="seat-label"><strong>${cnt} Tiket</strong> (${t.name} ${sectionLabels[section]})</span>
                <span class="seat-price">${formatPrice(cnt * (window.ticketPrices[t.type] || 0))}</span>
            `;
            list.appendChild(item);
        });
    });
}

// ========================================
// CLEAR & PROCEED
// ========================================
function clearAllSeats() {
    selectedSeats.clear();
    window.selectedSeatsData    = [];
    window.randomAssignedSeats  = [];
    selectedZoneSections.clear();
    window.classSectionTickets  = {};
    clearLockedZone(); // Buka kunci zona saat semua pilihan dihapus

    document.querySelectorAll('.seat.selected').forEach(s => s.classList.remove('selected'));
    refreshAllOverlayCards();
    updateSelectionDisplay();
}

async function proceedToBooking() {
    const zoneTotal  = Object.values(window.classSectionTickets).reduce((s, c) => s + c, 0);
    const totalSeats = window.selectedSeatsData.length + zoneTotal;
    if (totalSeats === 0) { showWarning('Tidak Ada Kursi', 'Silakan pilih kursi terlebih dahulu.'); return; }

    // ── VALIDASI: pastikan tidak ada tiket closed yang terpilih ──
    const allSelectedTypes = [
        ...window.selectedSeatsData.map(s => s.zone),
        ...Object.entries(window.classSectionTickets)
            .filter(([, v]) => v > 0)
            .map(([k]) => k.split('-')[0])
    ];
    const closedType = allSelectedTypes.find(t => window.seatStatus?.[t]?.closed === true);
    if (closedType) {
        const closedName = (window.ticketConfig || []).find(c => c.type === closedType)?.name || closedType;
        showWarning(
            'Penjualan Ditutup',
            `Penjualan tiket ${closedName} sedang ditutup sementara. Silakan refresh halaman dan pilih kategori tiket lain.`
        );
        return;
    }

    const summaryCount       = document.getElementById('summaryCount');
    const summarySeatNumbers = document.getElementById('summarySeatNumbers');
    const modalPrice         = document.getElementById('modalPrice');
    const paymentAmount      = document.getElementById('paymentAmount');

    if (summaryCount) summaryCount.textContent = totalSeats;

    if (summarySeatNumbers) {
        const breakdown = [];
        const cfg = window.ticketConfig || [];
        cfg.slice(2).forEach(t => {
            ['L', 'R'].forEach(section => {
                const cnt = window.classSectionTickets[`${t.type}-${section}`] || 0;
                if (cnt) breakdown.push(`${t.name} ${sectionLabels[section]} (${cnt})`);
            });
        });
        if (window.selectedSeatsData.length > 0)
            breakdown.push(`${cfg.slice(0,2).map(t=>t.name).join('/')} (${window.selectedSeatsData.length})`);
        summarySeatNumbers.textContent = breakdown.length
            ? breakdown.join(', ')
            : window.selectedSeatsData.map(d => d.seatId).join(', ');
    }

    // Hitung & tampilkan kode unik harga secara real-time
    const zoneDigitMap = { vvip: 1, vip: 2, kelas1: 3, reguler: 4, kelas2: 4 };

    // Tentukan primary zone dari pilihan saat ini
    const allTypes = [
        ...window.selectedSeatsData.map(s => s.zone),
        ...Object.entries(window.classSectionTickets)
            .filter(([, v]) => v > 0)
            .map(([k]) => k.split('-')[0])
    ];
    const primaryType = allTypes.length > 0
        ? allTypes.reduce((best, type) =>
            (window.ticketPrices[type] || 0) > (window.ticketPrices[best] || 0) ? type : best
          , allTypes[0])
        : 'reguler';

    const zoneDigit = zoneDigitMap[primaryType] ?? 4;

    // Tampilkan harga dasar dulu sambil fetch
    if (modalPrice)    modalPrice.textContent    = formatPrice(currentTotalPrice);
    if (paymentAmount) paymentAmount.textContent = '⏳ Menghitung...';

    let previewUniquePrice = currentTotalPrice;
    try {
        const zoneCounts  = await SB.getTransactionCountPerZone();
        const zoneOrder   = (zoneCounts[primaryType] || 0) + 1;
        const orderPadded = String(zoneOrder).padStart(3, '0');
        previewUniquePrice = parseInt(currentTotalPrice)
                           + parseInt(orderPadded) * 10
                           + zoneDigit;
    } catch (e) {
        console.warn('Gagal fetch kode unik untuk preview:', e);
        previewUniquePrice = currentTotalPrice;
    }

    // Simpan preview ke window agar mudah diakses saat submit
    window._previewUniquePrice  = previewUniquePrice;
    window._previewPrimaryType  = primaryType;

    if (paymentAmount) paymentAmount.textContent = formatPrice(previewUniquePrice);

    const modal = document.getElementById('bookingModal');
    if (modal) { modal.style.display = 'flex'; setTimeout(() => modal.classList.add('active'), 10); }
}

// ========================================
// HELPERS
// ========================================
function formatPrice(price) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency', currency: 'IDR', minimumFractionDigits: 0
    }).format(price);
}

function showWarning(title, message) {
    const popup = document.getElementById('warningPopup');
    if (popup) {
        document.getElementById('warningTitle').textContent   = title;
        document.getElementById('warningMessage').textContent = message;
        popup.style.display = 'flex';
        setTimeout(() => popup.classList.add('active'), 10);
    }
}

function closeWarning() {
    const popup = document.getElementById('warningPopup');
    if (popup) {
        popup.classList.remove('active');
        setTimeout(() => { popup.style.display = 'none'; }, 300);
    }
}

// ========================================
// ZONE BADGE BUTTONS (VVIP / VIP)
// ========================================
function _buildZoneBadgeHTML(zone) {
    const cfg    = (window.ticketConfig || []).find(t => t.type === zone);
    const color  = cfg?.color || (zone === 'vvip' ? '#ffcf32' : '#da0424');
    const label  = cfg?.name  || zone.toUpperCase();
    const isClosed = window.seatStatus?.[zone]?.closed === true;
    if (isClosed) {
        return `<span class="zone-badge-btn zone-badge-closed" style="--zb-color:${color};">
                    — ${label} Zone — <span class="zb-tag">Tutup</span>
                </span>`;
    }
    return `<button class="zone-badge-btn" style="--zb-color:${color};"
                onclick="openZoneModal('${zone}')" type="button">
                — ${label} Zone —
                <span class="zb-tag">Pilih Zona</span>
            </button>`;
}

window.openZoneModal = function(zone) {
    const existing = document.getElementById('zoneModal');
    if (existing) existing.remove();

    const cfg   = (window.ticketConfig || []).find(t => t.type === zone);
    const color = cfg?.color || (zone === 'vvip' ? '#ffcf32' : '#da0424');
    const label = cfg?.name  || zone.toUpperCase();
    const quota = window.seatStatus?.[zone]?.open_quota ?? (cfg?.quota ?? 0);
    const price = window.ticketPrices?.[zone] || 0;

    const fmt = p => new Intl.NumberFormat('id-ID',{style:'currency',currency:'IDR',minimumFractionDigits:0}).format(p);

    const overlay = document.createElement('div');
    overlay.id        = 'zoneModal';
    overlay.className = 'zm-overlay';
    overlay.innerHTML = `
        <div class="zm-card" style="--zm-color:${color};">
            <button class="zm-close" onclick="closeZoneModal()" aria-label="Tutup">✕</button>
            <div class="zm-header">
                <div class="zm-dot" style="background:${color};box-shadow:0 0 8px ${color}80;"></div>
                <div>
                    <div class="zm-title">${label}</div>
                    <div class="zm-price">${fmt(price)} / kursi</div>
                </div>
                <div class="zm-avail" style="color:${color};">${quota} tersedia</div>
            </div>
            <div class="zm-qty-row">
                <span class="zm-qty-label">Jumlah Kursi</span>
                <div class="zm-qty-ctrl">
                    <button class="zm-qty-btn" id="zmMinus" type="button"
                        onclick="_zmChange('${zone}',-1,${quota},${price})">−</button>
                    <span class="zm-qty-num" id="zmNum">1</span>
                    <button class="zm-qty-btn" id="zmPlus" type="button"
                        onclick="_zmChange('${zone}',1,${quota},${price})">+</button>
                </div>
            </div>
            <div class="zm-total-row">
                <span>Total</span>
                <span class="zm-total-val" id="zmTotal" style="color:${color};">${fmt(price)}</span>
            </div>
            <button class="zm-confirm-btn" id="zmConfirmBtn" type="button"
                style="background:${color};color:${zone==='vvip'?'#1a1a1a':'#fff'};"
                onclick="_zmConfirm('${zone}',${price})">
                Lanjutkan ke Pembayaran →
            </button>
        </div>`;

    document.body.appendChild(overlay);
    requestAnimationFrame(() => overlay.classList.add('zm-visible'));
    overlay.addEventListener('click', e => { if (e.target === overlay) closeZoneModal(); });

    window._zmCount = 1;
    _zmUpdateButtons(quota);
};

window.closeZoneModal = function() {
    const m = document.getElementById('zoneModal');
    if (!m) return;
    m.classList.remove('zm-visible');
    setTimeout(() => m.remove(), 220);
};

window._zmChange = function(zone, delta, quota, price) {
    window._zmCount = Math.max(1, Math.min(quota, (window._zmCount || 1) + delta));
    const fmt = p => new Intl.NumberFormat('id-ID',{style:'currency',currency:'IDR',minimumFractionDigits:0}).format(p);
    document.getElementById('zmNum').textContent  = window._zmCount;
    document.getElementById('zmTotal').textContent = fmt(price * window._zmCount);
    _zmUpdateButtons(quota);
};

function _zmUpdateButtons(quota) {
    const n = window._zmCount || 1;
    const minus = document.getElementById('zmMinus');
    const plus  = document.getElementById('zmPlus');
    if (minus) minus.disabled = (n <= 1);
    if (plus)  plus.disabled  = (n >= quota);
}

window._zmConfirm = async function(zone, price) {
    const count = window._zmCount || 1;
    const total = price * count;
    const btn   = document.getElementById('zmConfirmBtn');
    if (btn) { btn.disabled = true; btn.textContent = 'Menghitung harga...'; }

    window.selectedSeatsData   = [];
    window.classSectionTickets = {};
    window._pendingZone        = zone;
    window._pendingTotalSeats  = count;
    for (let i = 0; i < count; i++)
        window.selectedSeatsData.push({ seatId: null, zone, label: zone.toUpperCase(), price });
    currentTotalPrice = total;

    try {
        const zoneDigitMap = { vvip: 1, vip: 2, kelas1: 3, reguler: 4, kelas2: 4 };
        const zoneDigit    = zoneDigitMap[zone] ?? 4;
        const zoneCounts   = await SB.getTransactionCountPerZone();
        const zoneOrder    = (zoneCounts[zone] || 0) + 1;
        const orderPadded  = String(zoneOrder).padStart(3, '0');
        window._previewUniquePrice = parseInt(total) + parseInt(orderPadded) * 10 + zoneDigit;
    } catch(e) {
        window._previewUniquePrice = null;
    }

    closeZoneModal();
    if (typeof openBookingModal === 'function') openBookingModal();
};

// ── Inject CSS: zone badge + modal ───────────────────────────
(function injectZoneBadgeCSS() {
    if (document.getElementById('zone-badge-style')) return;
    const s = document.createElement('style');
    s.id = 'zone-badge-style';
    s.textContent = `
        /* ── Zone badge button ── */
        .zone-badge-btn {
            display: inline-flex; align-items: center; gap: 6px;
            background: transparent;
            border: 1.5px solid color-mix(in srgb, var(--zb-color) 50%, transparent);
            border-radius: 20px; padding: 3px 10px 3px 12px;
            font-size: .63rem; font-weight: 800; letter-spacing: 1.5px;
            cursor: pointer;
            text-transform: uppercase;
            transition: background .18s, transform .12s, box-shadow .18s;
            white-space: nowrap; font-family: inherit;
        }
        .zone-badge-btn:hover {
            background: color-mix(in srgb, var(--zb-color) 12%, transparent);
            box-shadow: 0 0 10px color-mix(in srgb, var(--zb-color) 30%, transparent);
            transform: scale(1.04);
        }
        .zone-badge-btn:active { transform: scale(.97); }
        .zone-badge-btn.zone-badge-closed { cursor:default; opacity:.5; border-style:dashed; }
        .zb-tag {
            background: color-mix(in srgb, var(--zb-color) 20%, transparent);
            border: 1px solid color-mix(in srgb, var(--zb-color) 35%, transparent);
            border-radius: 10px; padding: 1px 7px;
            font-size: .58rem; font-weight: 900; letter-spacing: .6px;
        }

        /* ── Modal overlay ── */
        .zm-overlay {
            position: fixed; inset: 0; z-index: 12000;
            display: flex; align-items: center; justify-content: center;
            background: rgba(0,0,0,.6); backdrop-filter: blur(5px);
            opacity: 0; transition: opacity .22s;
        }
        .zm-overlay.zm-visible { opacity: 1; }

        /* ── Modal card ── */
        .zm-card {
            position: relative;
            background: #0f172a;
            border: 1.5px solid color-mix(in srgb, var(--zm-color) 35%, transparent);
            border-radius: 20px; padding: 24px 26px 22px;
            width: min(360px, 92vw);
            box-shadow: 0 24px 64px rgba(0,0,0,.55), 0 0 0 1px rgba(255,255,255,.05);
            transform: translateY(14px) scale(.96);
            transition: transform .25s cubic-bezier(.34,1.5,.64,1);
        }
        .zm-overlay.zm-visible .zm-card { transform: translateY(0) scale(1); }
        .zm-close {
            position: absolute; top: 14px; right: 14px;
            background: rgba(255,255,255,.07); border: none; border-radius: 50%;
            width: 26px; height: 26px; font-size: .72rem; color: #94a3b8;
            cursor: pointer; display: flex; align-items: center; justify-content: center;
            transition: background .15s;
        }
        .zm-close:hover { background: rgba(255,255,255,.15); color: #f1f5f9; }
        .zm-header {
            display: flex; align-items: center; gap: 12px;
            margin-bottom: 20px; padding-right: 28px;
        }
        .zm-dot { width: 14px; height: 14px; border-radius: 4px; flex-shrink:0; }
        .zm-title { font-size: 1rem; font-weight: 800; color: #f1f5f9; }
        .zm-price { font-size: .74rem; color: #64748b; margin-top: 2px; }
        .zm-avail { margin-left: auto; font-size: .75rem; font-weight: 700; white-space: nowrap; }
        .zm-qty-row {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 14px; padding: 12px 14px;
            background: rgba(255,255,255,.04);
            border: 1px solid rgba(255,255,255,.08); border-radius: 12px;
        }
        .zm-qty-label { font-size: .82rem; color: #94a3b8; font-weight: 600; }
        .zm-qty-ctrl  { display: flex; align-items: center; }
        .zm-qty-btn {
            width: 30px; height: 30px;
            background: rgba(255,255,255,.07); border: 1.5px solid rgba(255,255,255,.13);
            border-radius: 8px; color: #f1f5f9; font-size: 1.1rem; font-weight: 700;
            cursor: pointer; display: flex; align-items: center; justify-content: center;
            transition: background .14s;
        }
        .zm-qty-btn:hover:not(:disabled) { background: rgba(255,255,255,.14); }
        .zm-qty-btn:disabled { opacity: .3; cursor: not-allowed; }
        .zm-qty-num {
            min-width: 38px; text-align: center;
            font-size: .98rem; font-weight: 800; color: #f1f5f9;
        }
        .zm-total-row {
            display: flex; justify-content: space-between; align-items: center;
            padding: 10px 0 16px;
            border-top: 1px solid rgba(255,255,255,.07);
            font-size: .82rem; color: #94a3b8; font-weight: 600;
        }
        .zm-total-val { font-size: 1.05rem; font-weight: 800; }
        .zm-confirm-btn {
            width: 100%; padding: 13px; border: none; border-radius: 12px;
            font-size: .92rem; font-weight: 800; cursor: pointer; letter-spacing: .3px;
            transition: opacity .15s, transform .1s;
        }
        .zm-confirm-btn:hover:not(:disabled) { opacity: .9; }
        .zm-confirm-btn:active { transform: scale(.98); }
        .zm-confirm-btn:disabled { opacity: .4; cursor: wait; }
    `;
    document.head.appendChild(s);
})();

// ========================================
// SEAT-PICKER MODE  (pakai denah asli)
// ========================================
window.activateSeatPickerMode = function() {
    const zone  = window._pendingZone;
    const total = window._pendingTotalSeats || 1;
    const cfg   = (window.ticketConfig || []).find(t => t.type === zone);
    const color = cfg?.color || (zone === 'vvip' ? '#ffcf32' : '#da0424');
    const label = cfg?.name  || zone.toUpperCase();

    _applyZoneVisibility(zone);
    _enableSeatPickerClicks(zone, color, total);
    _injectSeatPickerBanner(zone, label, color, total);
    setTimeout(() => _scrollToZone(zone), 150);

    // Sembunyikan sticky bar bawaan & selected seats container
    const bar = document.querySelector('.selection-summary-bar');
    if (bar) bar.style.display = 'none';
};

window.deactivateSeatPickerMode = function() {
    document.getElementById('spmBanner')?.remove();
    document.querySelectorAll('.seat-row-hidden').forEach(el => el.classList.remove('seat-row-hidden'));
    document.querySelectorAll('.section-zone-hidden').forEach(el => el.classList.remove('section-zone-hidden'));
    document.querySelectorAll('.seat.spm-pickable').forEach(el => {
        el.classList.remove('spm-pickable', 'spm-picked');
        el.style.outline = ''; el.style.boxShadow = '';
        el.classList.add('vvip-vip-locked');
        el.style.cursor = 'help';
    });
    const bar = document.querySelector('.selection-summary-bar');
    if (bar) bar.style.display = '';
    window._spmSelected = new Set();
};

function _applyZoneVisibility(activeZone) {
    const activeCfg = (window.ticketConfig || []).find(t => t.type === activeZone);
    const rowStart  = activeCfg?.rowStart ?? 1;
    const rowEnd    = activeCfg?.rowEnd   ?? 3;

    ['leftSection','rightSection'].forEach(sId => {
        const sec = document.getElementById(sId);
        if (!sec) return;
        // Sembunyikan baris di luar zona
        sec.querySelectorAll('.row-container').forEach(rc => {
            const rowNum = parseInt(rc.querySelector('.row-number')?.textContent);
            if (!isNaN(rowNum) && (rowNum < rowStart || rowNum > rowEnd))
                rc.classList.add('seat-row-hidden');
        });
        // Sembunyikan column-header, divider, dan zone label zona lain
        sec.querySelectorAll('.column-header, .vvip-divider, .vvip-zone-label, .vip-zone-label').forEach(el => {
            el.classList.add('section-zone-hidden');
        });
        // Sembunyikan overlay cards zona lain
        sec.querySelectorAll('.zone-overlay-card').forEach(el => el.classList.add('section-zone-hidden'));
    });
}

function _enableSeatPickerClicks(zone, color, total) {
    window._spmSelected = new Set();
    document.querySelectorAll('.seat.vvip-vip-locked').forEach(seat => {
        if ((seatConfig?.[seat.dataset.seatId]) !== zone) return;
        if (seat.classList.contains('sold') || seat.classList.contains('archived-zone')) return;
        // Clone untuk hapus listener lama
        const fresh = seat.cloneNode(true);
        fresh.classList.remove('vvip-vip-locked');
        fresh.classList.add('spm-pickable');
        fresh.style.cursor     = 'pointer';
        fresh.style.background = color + '22';
        fresh.style.borderColor = color + '80';
        fresh.title = '';
        // Hapus tooltip lama
        fresh.querySelectorAll('.seat-tooltip, .seat-locked-tooltip').forEach(t => t.remove());
        seat.parentNode.replaceChild(fresh, seat);
        fresh.addEventListener('click', () => _spmPickerToggle(fresh, zone, color, total));
    });
}

function _spmPickerToggle(el, zone, color, total) {
    const seatId = el.dataset.seatId;
    const sel    = window._spmSelected || (window._spmSelected = new Set());
    if (sel.has(seatId)) {
        sel.delete(seatId);
        el.classList.remove('spm-picked');
        el.style.outline     = '';
        el.style.boxShadow   = '';
        el.style.background  = color + '22';
        el.style.borderColor = color + '80';
    } else {
        if (sel.size >= total) {
            const firstId = [...sel][0];
            sel.delete(firstId);
            const firstEl = document.querySelector(`[data-seat-id="${firstId}"]`);
            if (firstEl) {
                firstEl.classList.remove('spm-picked');
                firstEl.style.outline     = '';
                firstEl.style.boxShadow   = '';
                firstEl.style.background  = color + '22';
                firstEl.style.borderColor = color + '80';
            }
        }
        sel.add(seatId);
        el.classList.add('spm-picked');
        el.style.outline     = `2.5px solid ${color}`;
        el.style.boxShadow   = `0 0 10px ${color}70`;
        el.style.background  = color + '44';
        el.style.borderColor = color;
    }
    _updateSpmBanner(color, total);
}

function _injectSeatPickerBanner(zone, label, color, total) {
    document.getElementById('spmBanner')?.remove();
    const banner = document.createElement('div');
    banner.id        = 'spmBanner';
    banner.className = 'spm-inline-banner';
    banner.style.setProperty('--spb-color', color);
    banner.innerHTML = `
        <div class="spb-inner">
            <div class="spb-left">
                <span class="spb-badge" style="background:${color}20;color:${color};border-color:${color}50;">${label}</span>
                <span class="spb-text" id="spbText">Pilih <strong>${total}</strong> kursi yang Anda inginkan</span>
            </div>
            <div class="spb-right">
                <span class="spb-count" id="spbCount" style="color:${color};">0 / ${total}</span>
                <button class="spb-confirm-btn" id="spbConfirmBtn" disabled type="button"
                    style="background:${color};color:${zone==='vvip'?'#1a1a1a':'#fff'};"
                    onclick="_spmInlineConfirm()">Konfirmasi Kursi</button>
            </div>
        </div>`;

    const wrapper = document.getElementById('seatLayoutWrapper');
    if (wrapper) wrapper.before(banner);

    if (!document.getElementById('spb-style')) {
        const s = document.createElement('style');
        s.id = 'spb-style';
        s.textContent = `
            .spm-inline-banner {
                position: sticky; top: 0; z-index: 100;
                background: linear-gradient(135deg,#0f172a,#1e1b4b);
                border: 1.5px solid color-mix(in srgb, var(--spb-color) 40%, transparent);
                border-radius: 14px; margin-bottom: 10px;
                box-shadow: 0 4px 24px rgba(0,0,0,.4);
                animation: spb-in .3s cubic-bezier(.34,1.4,.64,1) both;
            }
            @keyframes spb-in { from { opacity:0; transform:translateY(-10px); } }
            .spb-inner {
                display: flex; align-items: center; justify-content: space-between;
                gap: 12px; padding: 11px 18px; flex-wrap: wrap;
            }
            .spb-left  { display: flex; align-items: center; gap: 10px; min-width: 0; }
            .spb-right { display: flex; align-items: center; gap: 10px; flex-shrink: 0; }
            .spb-badge {
                border: 1px solid; border-radius: 20px; padding: 3px 10px;
                font-size: .7rem; font-weight: 800; letter-spacing: .8px;
                text-transform: uppercase; white-space: nowrap; flex-shrink: 0;
            }
            .spb-text { font-size: .82rem; color: #94a3b8; white-space: nowrap; }
            .spb-text strong { color: #f1f5f9; }
            .spb-count { font-size: .9rem; font-weight: 800; font-family: monospace; }
            .spb-confirm-btn {
                padding: 8px 18px; border: none; border-radius: 10px;
                font-size: .84rem; font-weight: 800; cursor: pointer; white-space: nowrap;
                transition: opacity .15s, transform .1s;
            }
            .spb-confirm-btn:disabled { opacity: .35; cursor: not-allowed; }
            .spb-confirm-btn:hover:not(:disabled) { opacity: .88; }
            .spb-confirm-btn:active:not(:disabled) { transform: scale(.97); }
            /* Rows hidden in seat-picker mode */
            .seat-row-hidden    { display: none !important; }
            .section-zone-hidden { display: none !important; }
            /* Picked seat */
            .seat.spm-picked { transform: scale(1.12) !important; z-index: 3; }
        `;
        document.head.appendChild(s);
    }
}

function _updateSpmBanner(color, total) {
    const count = (window._spmSelected || new Set()).size;
    const countEl  = document.getElementById('spbCount');
    const textEl   = document.getElementById('spbText');
    const confirmBtn = document.getElementById('spbConfirmBtn');
    if (countEl)   countEl.textContent = `${count} / ${total}`;
    if (textEl) {
        if (count === 0)        textEl.innerHTML = `Pilih <strong>${total}</strong> kursi yang Anda inginkan`;
        else if (count < total) textEl.innerHTML = `Pilih <strong>${total - count}</strong> kursi lagi`;
        else                    textEl.innerHTML = `<strong style="color:${color};">✓ ${count} kursi dipilih</strong>`;
    }
    if (confirmBtn) confirmBtn.disabled = (count !== total);
}

function _scrollToZone(zone) {
    const cfg      = (window.ticketConfig || []).find(t => t.type === zone);
    const rowStart = cfg?.rowStart ?? 1;
    const sc       = document.querySelector('.seat-scroll-container');
    const allRows  = document.querySelectorAll('#leftSection .row-container');
    for (const rc of allRows) {
        const num = parseInt(rc.querySelector('.row-number')?.textContent);
        if (num === rowStart) {
            // Scroll container horizontal ke tengah, vertical ke baris
            if (sc) sc.scrollLeft = (sc.scrollWidth - sc.clientWidth) / 2;
            rc.scrollIntoView({ behavior: 'smooth', block: 'center' });
            break;
        }
    }
}

window._spmInlineConfirm = async function() {
    const sel   = window._spmSelected || new Set();
    const total = window._pendingTotalSeats || 1;
    if (sel.size !== total) return;

    const btn = document.getElementById('spbConfirmBtn');
    if (btn) { btn.disabled = true; btn.textContent = 'Menyimpan...'; }

    const ticketId     = window._pendingTicketId;
    const ticketNumber = window._pendingTicketNumber;
    const customerName = window._pendingCustomerName;
    const uniquePrice  = window._pendingUniquePrice;

    if (!ticketId) {
        if (typeof showWarning === 'function')
            showWarning('Error', 'Data tiket tidak ditemukan. Silakan mulai ulang pemesanan.');
        if (btn) { btn.disabled = false; btn.textContent = 'Konfirmasi Kursi'; }
        return;
    }

    try {
        await SB.updateSeatNumbers(ticketId, [...sel]);

        // Update cache: stage berubah dari pending_seat → pending_wa
        if (typeof BVR !== 'undefined') {
            BVR.savePendingWA(
                ticketNumber, customerName, null,
                uniquePrice, currentTotalPrice,
                window._pendingTotalSeats
            );
        }

        deactivateSeatPickerMode();
        await loadBookedSeats();
        // Tampilkan success di booking modal
        if (typeof showSuccessMessage === 'function')
            showSuccessMessage(ticketNumber, customerName, uniquePrice);
        const bookingModal = document.getElementById('bookingModal');
        if (bookingModal) {
            const form = document.getElementById('bookingForm');
            if (form) form.style.display = 'none';
            bookingModal.style.display = 'flex';
            setTimeout(() => bookingModal.classList.add('active'), 10);
        }
    } catch(err) {
        if (btn) { btn.disabled = false; btn.textContent = 'Konfirmasi Kursi'; }
        if (typeof showWarning === 'function')
            showWarning('Gagal Menyimpan', err.message || 'Terjadi kesalahan. Silakan coba lagi.');
    }
};

// ========================================
// INITIALIZE
// ========================================
if (document.querySelector('.seat-selection-page')) {
    document.addEventListener('DOMContentLoaded', function () {
        renderSeatingLayout();

        // Setup overlay cards setelah layout render
        setTimeout(() => {
            setupZoneClickHandlers();

            // Load data dari DB, lalu refresh overlay cards dengan kuota terkini
            if (typeof loadBookedSeats === 'function') {
                loadBookedSeats().then(() => {
                    // Setelah zoneSoldCounts terisi, refresh ulang overlay cards
                    setTimeout(() => refreshAllOverlayCards(), 50);
                }).catch(err => console.error('loadBookedSeats error:', err));
            }
        }, 500);

        // Centre scroll di mobile
        setTimeout(() => {
            const sc = document.querySelector('.seat-scroll-container');
            if (sc && window.innerWidth <= 768) {
                sc.scrollLeft = (sc.scrollWidth - sc.clientWidth) / 2;
            }
        }, 100);
    });
}