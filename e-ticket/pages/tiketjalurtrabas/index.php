<?php
// ============================================================
// PAGES/INPUT-MANUAL/INDEX.PHP
// Halaman Input Tiket Manual + Manajemen Tiket (Edit/Lihat Semua)
// - Input tiket manual tanpa pilih kursi di denah (dropdown + search)
// - ticket_number bisa diisi manual
// - Logika submit SAMA PERSIS dengan halaman pemesanan (seat)
// - Tabel tiket lengkap (seperti admin) dengan fitur EDIT inline
// ============================================================

require_once __DIR__ . '/../../config.php';

// ── Helper: baca file JS/CSS ─────────────────────────────────
function readAsset(string $path): string {
    $full = __DIR__ . '/../../' . ltrim($path, '/');
    return file_exists($full) ? file_get_contents($full) : "/* FILE NOT FOUND: {$path} */";
}

$cssAdmin = readAsset('css/admin-style.css');
$jsSupabase = readAsset('js/supabase.js');

// Bangun daftar semua kursi yang tersedia (L-row-seat dan R-row-seat)
$angledRows = unserialize(ANGLED_ROWS);
$normalCols = NORMAL_COLS;
$totalRows  = TOTAL_ROWS;

$allSeats = [];
for ($row = 1; $row <= $totalRows; $row++) {
    $cols = $angledRows[$row] ?? $normalCols;
    for ($seat = 1; $seat <= $cols; $seat++) {
        $allSeats[] = "L-{$row}-{$seat}";
        $allSeats[] = "R-{$row}-{$seat}";
    }
}

$allSeatsJson = json_encode($allSeats);
$seatConfigJson = json_encode(generateSeatConfigPhp());
$ticketPricesJson = json_encode($ticketPrices);
$ticketConfigJson = json_encode($ticketConfig);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" href="/favicon.ico?v=2">
    <title>Input Manual Tiket — Ticketing Management</title>

    <style>
<?= $cssAdmin ?>

/* ================================================================
   OVERRIDE & TAMBAHAN UNTUK HALAMAN INPUT MANUAL
   ================================================================ */

/* ── Navigasi Tab ── */
.page-tabs {
    display: flex;
    gap: 8px;
    margin-bottom: 24px;
    background: #fff;
    padding: 6px;
    border-radius: 16px;
    box-shadow: var(--shadow);
    border: 1px solid var(--border);
}
.tab-btn {
    flex: 1;
    padding: 12px 20px;
    border: none;
    border-radius: 12px;
    font-size: .9rem;
    font-weight: 600;
    font-family: inherit;
    cursor: pointer;
    transition: all .2s;
    color: var(--gray);
    background: transparent;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}
.tab-btn.active {
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    color: #fff;
    box-shadow: 0 4px 12px rgba(99,102,241,.35);
}
.tab-btn:not(.active):hover {
    background: var(--bg);
    color: var(--dark);
}
.tab-panel { display: none; }
.tab-panel.active { display: block; }

/* ── Form Card ── */
.form-card {
    background: #fff;
    border-radius: 20px;
    padding: 32px;
    box-shadow: var(--shadow-lg);
    border: 1px solid var(--border);
    margin-bottom: 24px;
}
.form-card h2 {
    font-size: 1.15rem;
    font-weight: 700;
    color: var(--dark);
    margin-bottom: 6px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.form-card p.subtitle {
    font-size: .83rem;
    color: var(--gray);
    margin-bottom: 24px;
}

.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 18px;
}
.form-grid.full { grid-template-columns: 1fr; }
@media (max-width: 640px) { .form-grid { grid-template-columns: 1fr; } }

.field-group { display: flex; flex-direction: column; gap: 6px; }
.field-group label {
    font-size: .82rem;
    font-weight: 600;
    color: #374151;
}
.field-group input,
.field-group select {
    padding: 11px 14px;
    border: 2px solid var(--border);
    border-radius: 10px;
    font-size: .92rem;
    font-family: inherit;
    color: var(--dark);
    outline: none;
    transition: border-color .2s, box-shadow .2s;
    background: #fff;
}
.field-group input:focus,
.field-group select:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(99,102,241,.1);
}
.field-group .hint {
    font-size: .75rem;
    color: var(--gray-light);
}

/* ── Custom Select with Search (Seat Dropdown) ── */
.custom-select-wrap {
    position: relative;
}
.custom-select-trigger {
    width: 100%;
    padding: 11px 40px 11px 14px;
    border: 2px solid var(--border);
    border-radius: 10px;
    font-size: .92rem;
    font-family: inherit;
    color: var(--dark);
    background: #fff;
    cursor: pointer;
    text-align: left;
    transition: border-color .2s, box-shadow .2s;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: space-between;
    user-select: none;
    min-height: 46px;
}
.custom-select-trigger:focus,
.custom-select-trigger.open {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(99,102,241,.1);
    outline: none;
}
.custom-select-trigger .arrow {
    position: absolute;
    right: 14px;
    top: 50%;
    transform: translateY(-50%);
    pointer-events: none;
    transition: transform .2s;
    color: var(--gray);
}
.custom-select-trigger.open .arrow { transform: translateY(-50%) rotate(180deg); }

.custom-select-dropdown {
    display: none;
    position: absolute;
    top: calc(100% + 4px);
    left: 0;
    right: 0;
    background: #fff;
    border: 2px solid var(--primary);
    border-radius: 12px;
    box-shadow: var(--shadow-xl);
    z-index: 1000;
    overflow: hidden;
    animation: dropIn .15s ease;
}
.custom-select-dropdown.open { display: block; }
@keyframes dropIn {
    from { opacity: 0; transform: translateY(-8px); }
    to   { opacity: 1; transform: translateY(0); }
}
.dropdown-search {
    padding: 10px 12px;
    border-bottom: 1px solid var(--border);
    position: sticky;
    top: 0;
    background: #fff;
    z-index: 1;
}
.dropdown-search input {
    width: 100%;
    padding: 8px 12px;
    border: 1.5px solid var(--border);
    border-radius: 8px;
    font-size: .88rem;
    font-family: inherit;
    outline: none;
}
.dropdown-search input:focus { border-color: var(--primary); }
.dropdown-list {
    max-height: 220px;
    overflow-y: auto;
    padding: 6px;
}
.dropdown-item {
    padding: 8px 12px;
    border-radius: 8px;
    cursor: pointer;
    font-size: .88rem;
    color: var(--dark);
    transition: background .15s;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.dropdown-item:hover { background: #f1f5f9; }
.dropdown-item.selected { background: #eef2ff; color: var(--primary); font-weight: 600; }
.dropdown-item .seat-type-badge {
    font-size: .72rem;
    padding: 2px 8px;
    border-radius: 99px;
    font-weight: 700;
}
.dropdown-item.disabled-item {
    opacity: .4;
    cursor: not-allowed;
    pointer-events: none;
    text-decoration: line-through;
}
.dropdown-empty {
    padding: 20px;
    text-align: center;
    color: var(--gray-light);
    font-size: .85rem;
}

/* Selected seats chips */
.selected-seats-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-top: 8px;
    min-height: 0;
}
.seat-chip {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 10px;
    border-radius: 99px;
    font-size: .78rem;
    font-weight: 700;
    cursor: pointer;
    border: 1.5px solid transparent;
    transition: opacity .15s;
}
.seat-chip:hover { opacity: .75; }
.seat-chip .chip-remove { font-size: .9em; line-height: 1; }

/* ── Price Preview ── */
.price-preview-box {
    background: linear-gradient(135deg, #eef2ff, #f5f3ff);
    border: 2px solid #c7d2fe;
    border-radius: 14px;
    padding: 18px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
}
.price-preview-box .price-label { font-size: .85rem; color: #4338ca; font-weight: 600; }
.price-preview-box .price-amount { font-size: 1.4rem; font-weight: 800; color: #3730a3; }

/* ── Submit Button ── */
.btn-submit-manual {
    width: 100%;
    padding: 14px;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    color: #fff;
    border: none;
    border-radius: 12px;
    font-size: 1rem;
    font-weight: 700;
    font-family: inherit;
    cursor: pointer;
    transition: opacity .2s, transform .1s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}
.btn-submit-manual:hover { opacity: .9; }
.btn-submit-manual:active { transform: scale(.98); }
.btn-submit-manual:disabled { opacity: .5; cursor: not-allowed; transform: none; }

/* ── Status Toast ── */
.toast {
    position: fixed;
    bottom: 24px;
    right: 24px;
    background: #1e293b;
    color: #fff;
    padding: 14px 20px;
    border-radius: 12px;
    font-size: .88rem;
    font-weight: 500;
    box-shadow: 0 10px 25px rgba(0,0,0,.25);
    z-index: 9999;
    opacity: 0;
    transform: translateY(12px);
    transition: all .3s ease;
    max-width: 360px;
    pointer-events: none;
}
.toast.show { opacity: 1; transform: translateY(0); }
.toast.success { border-left: 4px solid #10b981; }
.toast.error   { border-left: 4px solid #ef4444; }
.toast.info    { border-left: 4px solid #6366f1; }

/* ── Edit Modal ── */
.edit-modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,.6);
    z-index: 5000;
    align-items: center;
    justify-content: center;
    padding: 20px;
    backdrop-filter: blur(4px);
}
.edit-modal-overlay.open { display: flex; }
.edit-modal-box {
    background: #fff;
    border-radius: 20px;
    padding: 32px;
    max-width: 680px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
    animation: modalIn .25s cubic-bezier(.16,1,.3,1);
    box-shadow: var(--shadow-xl);
}
@keyframes modalIn {
    from { opacity:0; transform:scale(.95) translateY(20px); }
    to   { opacity:1; transform:scale(1) translateY(0); }
}
.edit-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 24px;
}
.edit-modal-header h2 {
    font-size: 1.2rem;
    font-weight: 700;
    color: var(--dark);
}
.edit-modal-close {
    width: 32px; height: 32px;
    border: none;
    background: #f1f5f9;
    border-radius: 8px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    color: var(--gray);
    transition: background .15s;
}
.edit-modal-close:hover { background: #fee2e2; color: #dc2626; }

/* ── Tabel Tiket ── */
.tickets-card {
    background: #fff;
    border-radius: 20px;
    box-shadow: var(--shadow-lg);
    border: 1px solid var(--border);
    overflow: hidden;
}
.tickets-card-header {
    padding: 20px 24px;
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
}
.tickets-card-header h2 {
    font-size: 1.05rem;
    font-weight: 700;
    color: var(--dark);
    display: flex;
    align-items: center;
    gap: 8px;
}
.table-controls {
    display: flex;
    gap: 10px;
    align-items: center;
    flex-wrap: wrap;
}
.table-search {
    padding: 9px 14px;
    border: 1.5px solid var(--border);
    border-radius: 10px;
    font-size: .88rem;
    font-family: inherit;
    outline: none;
    min-width: 220px;
    transition: border-color .2s;
}
.table-search:focus { border-color: var(--primary); }
.filter-select-sm {
    padding: 9px 12px;
    border: 1.5px solid var(--border);
    border-radius: 10px;
    font-size: .88rem;
    font-family: inherit;
    outline: none;
    background: #fff;
    color: var(--dark);
    transition: border-color .2s;
}
.filter-select-sm:focus { border-color: var(--primary); }

table { width: 100%; border-collapse: collapse; }
thead tr { background: #f8fafc; }
th {
    padding: 12px 14px;
    text-align: left;
    font-size: .78rem;
    font-weight: 700;
    color: var(--gray);
    text-transform: uppercase;
    letter-spacing: .4px;
    border-bottom: 1px solid var(--border);
    white-space: nowrap;
}
td {
    padding: 12px 14px;
    font-size: .87rem;
    color: var(--dark);
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
}
tr:last-child td { border-bottom: none; }
tr:hover td { background: #fafbff; }

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 3px 10px;
    border-radius: 99px;
    font-size: .75rem;
    font-weight: 700;
    white-space: nowrap;
}
.status-pending { background: #fef3c7; color: #92400e; }
.status-paid    { background: #d1fae5; color: #065f46; }
.type-badge {
    display: inline-block;
    padding: 2px 9px;
    border-radius: 6px;
    font-size: .73rem;
    font-weight: 700;
    text-transform: uppercase;
}
.type-vvip    { background: #fff8e1; color: #92400e; border: 1px solid #fde68a; }
.type-vip     { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
.type-kelas1  { background: #f0fdf4; color: #14532d; border: 1px solid #bbf7d0; }
.type-reguler { background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; }

.action-btn-sm {
    padding: 6px 12px;
    border: none;
    border-radius: 8px;
    font-size: .78rem;
    font-weight: 600;
    font-family: inherit;
    cursor: pointer;
    transition: opacity .15s;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    white-space: nowrap;
}
.action-btn-sm:hover { opacity: .8; }
.btn-edit-sm    { background: #eef2ff; color: #4338ca; }
.btn-delete-sm  { background: #fee2e2; color: #dc2626; }
.btn-verify-sm  { background: #d1fae5; color: #065f46; }

.table-loading {
    padding: 48px 24px;
    text-align: center;
    color: var(--gray);
    font-size: .9rem;
}
.spinner-sm {
    width: 28px; height: 28px;
    border: 3px solid #e2e8f0;
    border-top-color: var(--primary);
    border-radius: 50%;
    animation: spin .7s linear infinite;
    margin: 0 auto 12px;
}
@keyframes spin { to { transform: rotate(360deg); } }

.empty-rows {
    padding: 48px 24px;
    text-align: center;
    color: var(--gray-light);
    font-size: .9rem;
}

/* Zone group header rows */
.zone-header-row td {
    font-size: .78rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .4px;
    padding: 8px 14px;
}

/* Edit seat chips in modal */
.edit-seat-chips { display: flex; flex-wrap: wrap; gap: 5px; margin-top: 6px; }

/* Confirm delete modal */
.confirm-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,.55);
    z-index: 6000;
    align-items: center;
    justify-content: center;
}
.confirm-overlay.open { display: flex; }
.confirm-box {
    background: #fff;
    border-radius: 20px;
    padding: 36px;
    max-width: 400px;
    width: 90%;
    text-align: center;
    animation: modalIn .2s ease;
    box-shadow: var(--shadow-xl);
}
.confirm-box h3 { font-size: 1.15rem; font-weight: 700; color: var(--dark); margin-bottom: 10px; }
.confirm-box p { font-size: .88rem; color: var(--gray); line-height: 1.6; margin-bottom: 24px; }
.confirm-box-actions { display: flex; gap: 12px; justify-content: center; }
.btn-cancel-sm {
    padding: 10px 20px; border: 2px solid var(--border); border-radius: 10px;
    font-size: .9rem; font-weight: 600; font-family: inherit; cursor: pointer;
    background: #fff; color: var(--gray); transition: border-color .2s;
}
.btn-cancel-sm:hover { border-color: var(--gray); }
.btn-danger-sm {
    padding: 10px 20px; background: var(--danger); color: #fff;
    border: none; border-radius: 10px; font-size: .9rem; font-weight: 700;
    font-family: inherit; cursor: pointer; transition: opacity .2s;
}
.btn-danger-sm:hover { opacity: .85; }

.success-banner {
    background: linear-gradient(135deg, #d1fae5, #a7f3d0);
    border: 2px solid #10b981;
    border-radius: 14px;
    padding: 18px 20px;
    margin-bottom: 20px;
    display: none;
    align-items: center;
    gap: 14px;
    animation: slideIn .3s ease;
}
@keyframes slideIn { from { opacity:0; transform:translateY(-10px); } to { opacity:1; transform:translateY(0); } }
.success-banner .sb-icon { font-size: 1.6rem; }
.success-banner .sb-text h3 { font-size: .95rem; font-weight: 700; color: #065f46; margin-bottom: 3px; }
.success-banner .sb-text p  { font-size: .82rem; color: #047857; }

/* ─ Seat tags display in table ─ */
.seat-tags { display: flex; flex-wrap: wrap; gap: 3px; max-width: 160px; }
.seat-tag {
    display: inline-block; padding: 1px 6px; border-radius: 4px;
    font-size: .7rem; font-weight: 700; white-space: nowrap;
}
</style>
</head>

<body>
<div class="container">

    <!-- HEADER -->
    <div class="header" style="margin-bottom:20px;">
        <div class="header-top">
            <div class="header-title">
                <h1>⌨️ Input Manual Tiket</h1>
                <p>Input tiket tanpa pilih kursi di denah & manajemen tiket lengkap</p>
            </div>
            <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
                <a href="/e-ticket/admin/" style="text-decoration:none;">
                    <button class="refresh-btn" style="background:#f1f5f9;color:#374151;border:1px solid #e2e8f0;padding:10px 18px;border-radius:10px;font-size:.87rem;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:6px;">
                        🔒 Admin Panel
                    </button>
                </a>
                <a href="/e-ticket/" style="text-decoration:none;">
                    <button class="refresh-btn" style="background:#f1f5f9;color:#374151;border:1px solid #e2e8f0;padding:10px 18px;border-radius:10px;font-size:.87rem;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:6px;">
                        🏠 Beranda
                    </button>
                </a>
            </div>
        </div>
    </div>

    <!-- TABS -->
    <div class="page-tabs">
        <button class="tab-btn active" onclick="switchTab('tab-input')" id="btn-tab-input">
            <span>➕</span> Input Tiket Manual
        </button>
        <button class="tab-btn" onclick="switchTab('tab-list')" id="btn-tab-list">
            <span>📋</span> Daftar Semua Tiket
        </button>
    </div>

    <!-- ================================================================
         TAB 1: INPUT TIKET MANUAL
         ================================================================ -->
    <div class="tab-panel active" id="tab-input">

        <div id="successBannerWrap" class="success-banner">
            <div class="sb-icon">✅</div>
            <div class="sb-text">
                <h3 id="successBannerTitle">Tiket berhasil ditambahkan!</h3>
                <p id="successBannerMsg"></p>
            </div>
        </div>

        <div class="form-card">
            <h2>📝 Data Pemesan</h2>
            <p class="subtitle">Semua data dikirim ke database dengan logika yang sama persis saat pemesanan online. Nomor tiket bisa diisi manual.</p>

            <!-- ROW 1: Nama, No HP -->
            <div class="form-grid" style="margin-bottom:18px;">
                <div class="field-group">
                    <label for="m-name">Nama Lengkap (Kota Asal) <span style="color:#ef4444;">*</span></label>
                    <input type="text" id="m-name" placeholder="Contoh: Budi Santoso (Surabaya)" autocomplete="off">
                </div>
                <div class="field-group">
                    <label for="m-phone">Nomor HP <span style="color:#ef4444;">*</span></label>
                    <input type="tel" id="m-phone" placeholder="08xxxxxxxxxx" autocomplete="off">
                </div>
            </div>

            <!-- ROW 2: Ticket Number Manual, Status -->
            <div class="form-grid" style="margin-bottom:18px;">
                <div class="field-group">
                    <label for="m-ticket-number">Nomor Tiket (Manual) <span style="color:#ef4444;">*</span></label>
                    <input type="text" id="m-ticket-number" placeholder="TKT-xxx atau format bebas" autocomplete="off">
                    <span class="hint">Isi bebas — bisa format TKT-xxxx atau nomor manual lainnya</span>
                </div>
                <div class="field-group">
                    <label for="m-status">Status Pembayaran</label>
                    <select id="m-status">
                        <option value="pending">Pending (Belum Lunas)</option>
                        <option value="paid">Lunas</option>
                    </select>
                </div>
            </div>

            <!-- ROW 3: Ticket Type, Primary Ticket Type -->
            <div class="form-grid" style="margin-bottom:18px;">
                <div class="field-group">
                    <label for="m-ticket-type">Tipe Tiket (ticket_type) <span style="color:#ef4444;">*</span></label>
                    <select id="m-ticket-type" onchange="onTicketTypeChange()">
                        <option value="">-- Pilih Tipe Tiket --</option>
                        <?php foreach ($ticketConfig as $tc): ?>
                        <option value="<?= $tc['type'] ?>" data-price="<?= $ticketPrices[$tc['type']] ?>">
                            <?= $tc['name'] ?> — Rp <?= number_format($ticketPrices[$tc['type']], 0, ',', '.') ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="hint">Ini akan jadi array [tipe] di database</span>
                </div>
                <div class="field-group">
                    <label for="m-primary-type">Primary Ticket Type <span style="color:#ef4444;">*</span></label>
                    <select id="m-primary-type" onchange="onPrimaryTypeChange()">
                        <option value="">-- Pilih Primary Type --</option>
                        <?php foreach ($ticketConfig as $tc): ?>
                        <option value="<?= $tc['type'] ?>"><?= $tc['name'] ?> (<?= strtoupper($tc['type']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                    <span class="hint">primary_ticket_type menentukan zona utama tiket</span>
                </div>
            </div>

            <!-- ROW 4: Seat Numbers (Dropdown + Search), Total Seats (auto) -->
            <div class="form-grid" style="margin-bottom:18px;">
                <div class="field-group" style="grid-column: 1 / -1;">
                    <label>Nomor Kursi (seat_numbers) <span style="color:#94a3b8;font-weight:400;">— opsional, bisa kosong</span></label>
                    <div class="custom-select-wrap" id="seatSelectWrap">
                        <button type="button" class="custom-select-trigger" id="seatDropdownTrigger" onclick="toggleSeatDropdown()">
                            <span id="seatDropdownLabel" style="color:#94a3b8;">Pilih kursi (ketik untuk cari)…</span>
                            <svg class="arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="6 9 12 15 18 9"/>
                            </svg>
                        </button>
                        <div class="custom-select-dropdown" id="seatDropdown">
                            <div class="dropdown-search">
                                <input type="text" id="seatSearchInput" placeholder="🔍 Cari nomor kursi... (L-1-1, R-3-5, ...)" oninput="filterSeatDropdown()" autocomplete="off">
                            </div>
                            <div class="dropdown-list" id="seatDropdownList">
                                <!-- Populated by JS -->
                            </div>
                        </div>
                    </div>
                    <div class="selected-seats-chips" id="selectedSeatsChips"></div>
                    <span class="hint">Klik kursi untuk memilih/membatalkan. Ketik di kotak pencarian untuk filter cepat.</span>
                </div>
            </div>

            <!-- ROW 5: Total Seats (readonly auto), Price -->
            <div class="form-grid" style="margin-bottom:24px;">
                <div class="field-group">
                    <label for="m-total-seats">Total Kursi (total_seats)</label>
                    <input type="number" id="m-total-seats" value="1" min="1" max="20" oninput="updatePricePreview()">
                    <span class="hint">Otomatis berubah sesuai jumlah kursi yang dipilih, tapi bisa diedit manual</span>
                </div>
                <div class="field-group">
                    <label for="m-price">Harga (price) <span style="color:#ef4444;">*</span></label>
                    <input type="number" id="m-price" placeholder="0" min="0" step="1">
                    <span class="hint">Isi manual, atau lihat estimasi di bawah</span>
                </div>
            </div>

            <!-- Price Preview -->
            <div class="price-preview-box" style="margin-bottom:24px;">
                <div>
                    <div class="price-label">💰 Estimasi Harga (belum termasuk kode unik)</div>
                    <div id="priceEstimateLabel" style="font-size:.78rem;color:#6366f1;margin-top:3px;">Pilih tipe tiket untuk melihat estimasi</div>
                </div>
                <div class="price-amount" id="priceEstimateVal">—</div>
            </div>

            <!-- Submit -->
            <button class="btn-submit-manual" id="submitManualBtn" onclick="submitManualTicket()">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 5v14M5 12l7 7 7-7"/>
                </svg>
                <span id="submitManualText">Simpan Tiket ke Database</span>
            </button>

            <p style="text-align:center;font-size:.78rem;color:var(--gray-light);margin-top:12px;">
                ⚠️ Tidak ada validasi upload bukti bayar di halaman ini. Data langsung masuk ke Supabase.
            </p>
        </div>
    </div>

    <!-- ================================================================
         TAB 2: DAFTAR SEMUA TIKET (+ EDIT)
         ================================================================ -->
    <div class="tab-panel" id="tab-list">
        <div class="tickets-card">
            <div class="tickets-card-header">
                <h2>📋 Semua Tiket <span id="totalTicketCount" style="font-size:.82rem;color:var(--gray);font-weight:500;"></span></h2>
                <div class="table-controls">
                    <input type="text" class="table-search" id="listSearchInput" placeholder="🔍 Cari tiket, nama, HP..." oninput="renderTicketList()">
                    <select class="filter-select-sm" id="listStatusFilter" onchange="renderTicketList()">
                        <option value="all">Semua Status</option>
                        <option value="pending">Pending</option>
                        <option value="paid">Lunas</option>
                    </select>
                    <select class="filter-select-sm" id="listZoneFilter" onchange="renderTicketList()">
                        <option value="all">Semua Zona</option>
                        <option value="vvip">VVIP</option>
                        <option value="vip">VIP</option>
                        <option value="kelas1">Kelas 1</option>
                        <option value="reguler">Reguler</option>
                    </select>
                    <button onclick="loadAllTickets()" class="refresh-btn" style="padding:9px 16px;font-size:.85rem;display:flex;align-items:center;gap:6px;">
                        🔄 Refresh
                    </button>
                </div>
            </div>

            <div id="listLoadingState" class="table-loading">
                <div class="spinner-sm"></div>
                Memuat data tiket...
            </div>
            <div id="listEmptyState" class="empty-rows" style="display:none;">
                <div style="font-size:2rem;margin-bottom:12px;">🎟️</div>
                Tidak ada tiket ditemukan
            </div>

            <div style="overflow-x:auto;">
                <table id="ticketListTable" style="display:none;">
                    <thead>
                        <tr>
                            <th>No. Tiket</th>
                            <th>Nama</th>
                            <th>HP</th>
                            <th>Tipe</th>
                            <th>Primary Type</th>
                            <th>Kursi</th>
                            <th>Seats</th>
                            <th>Harga</th>
                            <th>Status</th>
                            <th>Tanggal</th>
                            <th style="min-width:110px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="ticketListBody"></tbody>
                </table>
            </div>
        </div>
    </div>

</div><!-- /container -->

<!-- ================================================================
     EDIT MODAL
     ================================================================ -->
<div class="edit-modal-overlay" id="editModalOverlay" onclick="handleEditOverlayClick(event)">
    <div class="edit-modal-box">
        <div class="edit-modal-header">
            <h2>✏️ Edit Tiket</h2>
            <button class="edit-modal-close" onclick="closeEditModal()">✕</button>
        </div>

        <input type="hidden" id="edit-ticket-id">

        <div class="form-grid" style="margin-bottom:16px;">
            <div class="field-group">
                <label for="edit-ticket-number">Nomor Tiket</label>
                <input type="text" id="edit-ticket-number" placeholder="TKT-xxxxx">
            </div>
            <div class="field-group">
                <label for="edit-status">Status</label>
                <select id="edit-status">
                    <option value="pending">Pending</option>
                    <option value="paid">Lunas</option>
                </select>
            </div>
        </div>

        <div class="form-grid" style="margin-bottom:16px;">
            <div class="field-group">
                <label for="edit-name">Nama Lengkap</label>
                <input type="text" id="edit-name" placeholder="Nama + Kota asal">
            </div>
            <div class="field-group">
                <label for="edit-phone">Nomor HP</label>
                <input type="tel" id="edit-phone" placeholder="62xxxxxxxxxx">
            </div>
        </div>

        <div class="form-grid" style="margin-bottom:16px;">
            <div class="field-group">
                <label for="edit-ticket-type">Ticket Type</label>
                <select id="edit-ticket-type" onchange="onEditTicketTypeChange()">
                    <?php foreach ($ticketConfig as $tc): ?>
                    <option value="<?= $tc['type'] ?>"><?= $tc['name'] ?> (<?= strtoupper($tc['type']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field-group">
                <label for="edit-primary-type">Primary Ticket Type</label>
                <select id="edit-primary-type" onchange="onEditPrimaryTypeChange()">
                    <?php foreach ($ticketConfig as $tc): ?>
                    <option value="<?= $tc['type'] ?>"><?= $tc['name'] ?> (<?= strtoupper($tc['type']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- Edit Seat Dropdown -->
        <div class="field-group" style="margin-bottom:16px;">
            <label>Seat Numbers</label>
            <div class="custom-select-wrap" id="editSeatSelectWrap">
                <button type="button" class="custom-select-trigger" id="editSeatDropdownTrigger" onclick="toggleEditSeatDropdown()">
                    <span id="editSeatDropdownLabel" style="color:#94a3b8;">Pilih kursi…</span>
                    <svg class="arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="6 9 12 15 18 9"/>
                    </svg>
                </button>
                <div class="custom-select-dropdown" id="editSeatDropdown">
                    <div class="dropdown-search">
                        <input type="text" id="editSeatSearchInput" placeholder="🔍 Cari nomor kursi..." oninput="filterEditSeatDropdown()" autocomplete="off">
                    </div>
                    <div class="dropdown-list" id="editSeatDropdownList"></div>
                </div>
            </div>
            <div class="edit-seat-chips" id="editSelectedSeatsChips"></div>
        </div>

        <div class="form-grid" style="margin-bottom:24px;">
            <div class="field-group">
                <label for="edit-total-seats">Total Seats</label>
                <input type="number" id="edit-total-seats" min="1" max="50" value="1">
                <span class="hint">Otomatis dari jumlah kursi dipilih</span>
            </div>
            <div class="field-group">
                <label for="edit-price">Harga</label>
                <input type="number" id="edit-price" min="0" step="1">
            </div>
        </div>

        <div style="display:flex;gap:12px;">
            <button onclick="closeEditModal()" style="flex:1;padding:12px;border:2px solid var(--border);border-radius:12px;font-size:.92rem;font-weight:600;font-family:inherit;cursor:pointer;background:#fff;color:var(--gray);">
                Batal
            </button>
            <button onclick="saveEditTicket()" id="saveEditBtn" style="flex:2;padding:12px;background:linear-gradient(135deg,var(--primary),var(--secondary));color:#fff;border:none;border-radius:12px;font-size:.92rem;font-weight:700;font-family:inherit;cursor:pointer;transition:opacity .2s;">
                💾 Simpan Perubahan
            </button>
        </div>
    </div>
</div>

<!-- CONFIRM DELETE MODAL -->
<div class="confirm-overlay" id="confirmDeleteOverlay">
    <div class="confirm-box">
        <div style="font-size:2.5rem;margin-bottom:16px;">🗑️</div>
        <h3>Hapus Tiket?</h3>
        <p id="confirmDeleteMsg">Tiket ini akan dihapus permanen dari database dan tidak bisa dikembalikan.</p>
        <div class="confirm-box-actions">
            <button class="btn-cancel-sm" onclick="closeConfirmDelete()">Batal</button>
            <button class="btn-danger-sm" id="confirmDeleteOkBtn" onclick="executeDelete()">Ya, Hapus</button>
        </div>
    </div>
</div>

<!-- TOAST -->
<div class="toast" id="globalToast"></div>

<!-- ================================================================
     CONFIG PHP → JS
     ================================================================ -->
<?= jsConfig($ticketPrices, $ticketConfig) ?>
<?= jsSeatConfig() ?>

<script>
// ────────────────────────────────────────────────────────────
// SUPABASE CLIENT (INLINE)
// ────────────────────────────────────────────────────────────
<?= $jsSupabase ?>

// ────────────────────────────────────────────────────────────
// GLOBAL STATE
// ────────────────────────────────────────────────────────────
const ALL_SEATS    = <?= $allSeatsJson ?>;
const SEAT_CONFIG  = seatConfig; // dari jsSeatConfig()

let selectedSeats    = [];  // untuk form input manual
let editSelectedSeats = []; // untuk edit modal
let allTicketsList   = [];  // cache list tiket
let deleteTargetId   = null;
let bookedSeatSet    = new Set(); // kursi yang sudah dipesan

// Warna per type
const TYPE_COLORS = {
    vvip:    { bg: '#fff8e1', color: '#b45309', border: '#fde68a' },
    vip:     { bg: '#fef2f2', color: '#991b1b', border: '#fecaca' },
    kelas1:  { bg: '#f0fdf4', color: '#166534', border: '#bbf7d0' },
    reguler: { bg: '#eff6ff', color: '#1e40af', border: '#bfdbfe' },
};

// ────────────────────────────────────────────────────────────
// TOAST
// ────────────────────────────────────────────────────────────
function showToast(msg, type = 'success') {
    const t = document.getElementById('globalToast');
    t.textContent = msg;
    t.className   = 'toast ' + type + ' show';
    clearTimeout(window._toastTmr);
    window._toastTmr = setTimeout(() => t.classList.remove('show'), 3500);
}

// ────────────────────────────────────────────────────────────
// TAB SWITCHING
// ────────────────────────────────────────────────────────────
function switchTab(tabId) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById(tabId).classList.add('active');
    document.getElementById('btn-' + tabId).classList.add('active');
    if (tabId === 'tab-list') loadAllTickets();
}

// ────────────────────────────────────────────────────────────
// SEAT DROPDOWN — INPUT FORM
// ────────────────────────────────────────────────────────────
function buildSeatDropdown() {
    const list = document.getElementById('seatDropdownList');
    renderSeatItems(list, ALL_SEATS, selectedSeats, bookedSeatSet, function(seat) {
        toggleSeat(seat);
    });
}

function filterSeatDropdown() {
    const q    = document.getElementById('seatSearchInput').value.toLowerCase();
    const filtered = ALL_SEATS.filter(s => s.toLowerCase().includes(q));
    const list = document.getElementById('seatDropdownList');
    renderSeatItems(list, filtered, selectedSeats, bookedSeatSet, function(seat) {
        toggleSeat(seat);
    });
}

function renderSeatItems(list, seats, selected, booked, onClick) {
    list.innerHTML = '';
    if (!seats.length) {
        list.innerHTML = '<div class="dropdown-empty">Tidak ada kursi ditemukan</div>';
        return;
    }
    seats.forEach(seat => {
        const type     = SEAT_CONFIG[seat] || 'reguler';
        const colors   = TYPE_COLORS[type] || TYPE_COLORS.reguler;
        const isBooked = booked.has(seat);
        const isSelected = selected.includes(seat);

        const item = document.createElement('div');
        item.className = 'dropdown-item' + (isSelected ? ' selected' : '') + (isBooked ? ' disabled-item' : '');
        item.innerHTML = `
            <span>${seat}</span>
            <span class="seat-type-badge" style="background:${colors.bg};color:${colors.color};border:1px solid ${colors.border};">
                ${type.toUpperCase()}${isBooked ? ' 🚫' : ''}
            </span>
        `;
        if (!isBooked) {
            item.onclick = () => { onClick(seat); };
        }
        list.appendChild(item);
    });
}

function toggleSeatDropdown() {
    const dd      = document.getElementById('seatDropdown');
    const trigger = document.getElementById('seatDropdownTrigger');
    const isOpen  = dd.classList.contains('open');
    if (isOpen) {
        dd.classList.remove('open');
        trigger.classList.remove('open');
    } else {
        buildSeatDropdown();
        dd.classList.add('open');
        trigger.classList.add('open');
        document.getElementById('seatSearchInput').focus();
    }
}

function toggleSeat(seat) {
    const idx = selectedSeats.indexOf(seat);
    if (idx === -1) selectedSeats.push(seat);
    else selectedSeats.splice(idx, 1);
    updateSelectedSeatsChips();
    buildSeatDropdown();
    filterSeatDropdown();
    // Auto update total seats
    document.getElementById('m-total-seats').value = selectedSeats.length || 1;
    updatePricePreview();
}

function updateSelectedSeatsChips() {
    const wrap = document.getElementById('selectedSeatsChips');
    const label = document.getElementById('seatDropdownLabel');
    wrap.innerHTML = '';
    if (!selectedSeats.length) {
        label.textContent = 'Pilih kursi (ketik untuk cari)…';
        label.style.color = '#94a3b8';
        return;
    }
    label.textContent = `${selectedSeats.length} kursi dipilih`;
    label.style.color = '#1e293b';
    selectedSeats.forEach(seat => {
        const type   = SEAT_CONFIG[seat] || 'reguler';
        const colors = TYPE_COLORS[type] || TYPE_COLORS.reguler;
        const chip   = document.createElement('span');
        chip.className = 'seat-chip';
        chip.style.cssText = `background:${colors.bg};color:${colors.color};border-color:${colors.border};`;
        chip.innerHTML = `${seat} <span class="chip-remove">✕</span>`;
        chip.onclick = () => toggleSeat(seat);
        wrap.appendChild(chip);
    });
}

// Close dropdown when clicking outside
document.addEventListener('click', function(e) {
    const wrap = document.getElementById('seatSelectWrap');
    if (wrap && !wrap.contains(e.target)) {
        document.getElementById('seatDropdown').classList.remove('open');
        document.getElementById('seatDropdownTrigger').classList.remove('open');
    }
    const editWrap = document.getElementById('editSeatSelectWrap');
    if (editWrap && !editWrap.contains(e.target)) {
        document.getElementById('editSeatDropdown').classList.remove('open');
        document.getElementById('editSeatDropdownTrigger').classList.remove('open');
    }
});

// ────────────────────────────────────────────────────────────
// TICKET TYPE CHANGE HANDLERS — INPUT FORM
// ────────────────────────────────────────────────────────────
function onTicketTypeChange() {
    const sel = document.getElementById('m-ticket-type');
    const val = sel.value;
    // Sync primary type if not set
    const primarySel = document.getElementById('m-primary-type');
    if (!primarySel.value) primarySel.value = val;
    updatePricePreview();
}

function onPrimaryTypeChange() {
    updatePricePreview();
}

function updatePricePreview() {
    const type     = document.getElementById('m-ticket-type').value;
    const totalEl  = document.getElementById('m-total-seats');
    const total    = parseInt(totalEl.value) || 1;
    const prices   = window.ticketPrices || {};
    const basePrice = prices[type] || 0;
    const est       = basePrice * total;

    const valEl  = document.getElementById('priceEstimateVal');
    const lblEl  = document.getElementById('priceEstimateLabel');

    if (!type) {
        valEl.textContent  = '—';
        lblEl.textContent  = 'Pilih tipe tiket untuk melihat estimasi';
        return;
    }
    valEl.textContent  = 'Rp ' + est.toLocaleString('id-ID');
    lblEl.textContent  = `${type.toUpperCase()} × ${total} = Rp ${basePrice.toLocaleString('id-ID')} × ${total} (belum termasuk kode unik)`;

    // Auto-fill price field jika kosong
    const priceEl = document.getElementById('m-price');
    if (!priceEl.value || priceEl.value === '0') {
        priceEl.value = est;
    }
}

// ────────────────────────────────────────────────────────────
// SUBMIT MANUAL TICKET
// Logika SAMA PERSIS dengan main.js: ticket_type = array,
// primary_ticket_type, seat_numbers (array|null), total_seats, price
// ────────────────────────────────────────────────────────────
async function submitManualTicket() {
    const nameVal      = document.getElementById('m-name').value.trim();
    const phoneRaw     = document.getElementById('m-phone').value.trim();
    const ticketNumVal = document.getElementById('m-ticket-number').value.trim();
    const ticketType   = document.getElementById('m-ticket-type').value;
    const primaryType  = document.getElementById('m-primary-type').value;
    const totalSeats   = parseInt(document.getElementById('m-total-seats').value) || 1;
    const priceVal     = parseInt(document.getElementById('m-price').value) || 0;
    const statusVal    = document.getElementById('m-status').value;

    // Validasi wajib
    if (!nameVal)      { showToast('❌ Nama wajib diisi', 'error'); return; }
    if (!phoneRaw)     { showToast('❌ Nomor HP wajib diisi', 'error'); return; }
    if (!ticketNumVal) { showToast('❌ Nomor tiket wajib diisi', 'error'); return; }
    if (!ticketType)   { showToast('❌ Tipe tiket wajib dipilih', 'error'); return; }
    if (!primaryType)  { showToast('❌ Primary type wajib dipilih', 'error'); return; }

    const btn  = document.getElementById('submitManualBtn');
    const text = document.getElementById('submitManualText');
    btn.disabled    = true;
    text.textContent = 'Menyimpan...';

    try {
        // Format phone (sama dengan main.js)
        const phoneFormatted = (phoneRaw.startsWith('0') ? '62' + phoneRaw.slice(1) : phoneRaw).replace(/[^0-9]/g, '');

        // ticket_type sebagai array (sama dengan main.js)
        // Untuk manual: satu type tapi dikali total seats
        const ticketTypes = Array(totalSeats).fill(ticketType);

        // seat_numbers
        const seatNumbersVal = selectedSeats.length > 0 ? selectedSeats : null;

        // Cek apakah VVIP/VIP (sama dengan main.js)
        const isVvipVip = (primaryType === 'vvip' || primaryType === 'vip');

        const payload = {
            ticket_number:         ticketNumVal,
            name:                  nameVal,
            phone:                 phoneFormatted,
            ticket_type:           ticketTypes,
            primary_ticket_type:   primaryType,
            status:                statusVal,
            price:                 priceVal,
            order_date:            new Date().toISOString(),
            qr_data:               ticketNumVal,
            seat_numbers:          seatNumbersVal,
            total_seats:           totalSeats,
            image_url:             null,
            selected_zone:         isVvipVip ? primaryType : null,
            seat_selection_status: (isVvipVip && !seatNumbersVal) ? 'pending_seat' : 'completed',
        };

        await SB.createTicket(payload);

        // Tampilkan sukses
        const banner = document.getElementById('successBannerWrap');
        banner.style.display  = 'flex';
        document.getElementById('successBannerTitle').textContent = `✅ Tiket "${ticketNumVal}" berhasil disimpan!`;
        document.getElementById('successBannerMsg').textContent   =
            `${nameVal} — ${primaryType.toUpperCase()} — Rp ${priceVal.toLocaleString('id-ID')} — Status: ${statusVal}`;

        showToast('✅ Tiket berhasil ditambahkan!', 'success');

        // Reset form
        resetManualForm();

        // Scroll ke banner
        banner.scrollIntoView({ behavior: 'smooth', block: 'start' });

        // Refresh booked seats
        await loadBookedSeats();

    } catch (err) {
        console.error(err);
        showToast('❌ Gagal menyimpan: ' + (err.message || 'Error tidak diketahui'), 'error');
    } finally {
        btn.disabled    = false;
        text.textContent = 'Simpan Tiket ke Database';
    }
}

function resetManualForm() {
    document.getElementById('m-name').value          = '';
    document.getElementById('m-phone').value         = '';
    document.getElementById('m-ticket-number').value = '';
    document.getElementById('m-ticket-type').value   = '';
    document.getElementById('m-primary-type').value  = '';
    document.getElementById('m-total-seats').value   = '1';
    document.getElementById('m-price').value         = '';
    document.getElementById('m-status').value        = 'pending';
    selectedSeats = [];
    updateSelectedSeatsChips();
    document.getElementById('priceEstimateVal').textContent  = '—';
    document.getElementById('priceEstimateLabel').textContent = 'Pilih tipe tiket untuk melihat estimasi';
}

// ────────────────────────────────────────────────────────────
// LOAD BOOKED SEATS (untuk mark di dropdown)
// ────────────────────────────────────────────────────────────
async function loadBookedSeats() {
    try {
        const tickets = await SB.getAllTickets();
        bookedSeatSet.clear();
        tickets.forEach(t => {
            if (t.seat_numbers) {
                try {
                    const seats = Array.isArray(t.seat_numbers) ? t.seat_numbers : JSON.parse(t.seat_numbers);
                    seats.forEach(s => bookedSeatSet.add(String(s).trim()));
                } catch(e) {}
            }
        });
    } catch(e) { console.warn('Gagal load booked seats:', e); }
}

// ────────────────────────────────────────────────────────────
// TICKET LIST
// ────────────────────────────────────────────────────────────
async function loadAllTickets() {
    document.getElementById('listLoadingState').style.display = 'block';
    document.getElementById('ticketListTable').style.display  = 'none';
    document.getElementById('listEmptyState').style.display   = 'none';

    try {
        allTicketsList = await SB.getAllTickets();
        renderTicketList();
    } catch(err) {
        showToast('❌ Gagal memuat tiket: ' + err.message, 'error');
    } finally {
        document.getElementById('listLoadingState').style.display = 'none';
    }
}

function renderTicketList() {
    const search      = document.getElementById('listSearchInput').value.toLowerCase();
    const statusF     = document.getElementById('listStatusFilter').value;
    const zoneF       = document.getElementById('listZoneFilter').value;

    let list = allTicketsList;

    if (statusF !== 'all') list = list.filter(t => t.status === statusF);
    if (zoneF !== 'all')   list = list.filter(t => (t.primary_ticket_type || 'reguler') === zoneF);
    if (search) {
        list = list.filter(t => {
            const tn = (t.ticket_number || '').toLowerCase();
            const nm = (t.name || '').toLowerCase();
            const ph = (t.phone || '').replace(/\D/g, '');
            const sq = search.replace(/\D/g, '');
            return tn.includes(search) || nm.includes(search) || (sq && ph.includes(sq));
        });
    }

    document.getElementById('totalTicketCount').textContent = `(${list.length} / ${allTicketsList.length})`;

    const tbody = document.getElementById('ticketListBody');
    tbody.innerHTML = '';

    if (!list.length) {
        document.getElementById('ticketListTable').style.display = 'none';
        document.getElementById('listEmptyState').style.display  = 'block';
        return;
    }

    document.getElementById('ticketListTable').style.display = 'table';
    document.getElementById('listEmptyState').style.display  = 'none';

    const ZONE_ORDER = ['vvip', 'vip', 'kelas1', 'reguler'];
    const ZONE_META  = {
        vvip:    { label: 'Zona VVIP',    icon: '⭐', headerBg: '#fffbeb', headerColor: '#92400e', borderColor: '#fcd34d' },
        vip:     { label: 'Zona VIP',     icon: '💎', headerBg: '#fff1f2', headerColor: '#881337', borderColor: '#fda4af' },
        kelas1:  { label: 'Zona Kelas 1', icon: '🎫', headerBg: '#f0fdf4', headerColor: '#14532d', borderColor: '#86efac' },
        reguler: { label: 'Zona Reguler', icon: '🎟️', headerBg: '#eff6ff', headerColor: '#1e3a8a', borderColor: '#93c5fd' },
    };

    if (zoneF === 'all') {
        // Group by zone
        const grouped = {};
        ZONE_ORDER.forEach(z => grouped[z] = []);
        list.forEach(t => {
            const z = (t.primary_ticket_type || 'reguler').toLowerCase();
            (grouped[z] || grouped['reguler']).push(t);
        });

        ZONE_ORDER.forEach(zone => {
            const tickets = grouped[zone];
            if (!tickets.length) return;
            const meta = ZONE_META[zone];
            const headerRow = document.createElement('tr');
            headerRow.className = 'zone-header-row';
            headerRow.innerHTML = `
                <td colspan="11" style="background:${meta.headerBg};color:${meta.headerColor};border-top:2px solid ${meta.borderColor};border-bottom:1px solid ${meta.borderColor};">
                    ${meta.icon}&nbsp;&nbsp;${meta.label}
                    <span style="margin-left:8px;font-size:.7rem;background:${meta.borderColor};color:${meta.headerColor};padding:2px 8px;border-radius:99px;">${tickets.length} nota</span>
                </td>`;
            tbody.appendChild(headerRow);
            tickets.forEach(t => tbody.appendChild(buildTicketRow(t)));
        });
    } else {
        list.forEach(t => tbody.appendChild(buildTicketRow(t)));
    }
}

function buildTicketRow(t) {
    const seats = parseSeats(t);
    const zone  = (t.primary_ticket_type || 'reguler').toLowerCase();
    const c     = TYPE_COLORS[zone] || TYPE_COLORS.reguler;

    const seatHtml = seats.length
        ? '<div class="seat-tags">' + seats.map(s => `<span class="seat-tag" style="background:${c.bg};color:${c.color};border:1px solid ${c.border};">${s}</span>`).join('') + '</div>'
        : '<span style="color:#94a3b8;font-size:.75rem;">—</span>';

    const row = document.createElement('tr');
    row.innerHTML = `
        <td><strong style="font-size:.82rem;">${t.ticket_number}</strong></td>
        <td style="max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="${esc(t.name)}">${esc(t.name)}</td>
        <td style="font-size:.82rem;white-space:nowrap;">${esc(t.phone || '—')}</td>
        <td><span class="type-badge type-${zone}">${zone.toUpperCase()}</span></td>
        <td><span class="type-badge type-${zone}">${(t.primary_ticket_type || '—').toUpperCase()}</span></td>
        <td>${seatHtml}</td>
        <td style="text-align:center;font-size:.85rem;">${t.total_seats || 0}</td>
        <td style="font-size:.82rem;white-space:nowrap;">Rp ${(t.price || 0).toLocaleString('id-ID')}</td>
        <td><span class="status-badge status-${t.status}">${t.status === 'paid' ? '✅ Lunas' : '⏳ Pending'}</span></td>
        <td style="font-size:.75rem;white-space:nowrap;color:var(--gray);">${formatDate(t.order_date)}</td>
        <td>
            <div style="display:flex;gap:5px;flex-wrap:wrap;">
                <button class="action-btn-sm btn-edit-sm" onclick="openEditModal('${t.id}')">✏️ Edit</button>
                <button class="action-btn-sm btn-delete-sm" onclick="confirmDelete('${t.id}','${esc(t.ticket_number)}')">🗑️</button>
            </div>
        </td>
    `;
    return row;
}

function parseSeats(t) {
    const raw = t.seat_numbers;
    if (!raw) return [];
    if (Array.isArray(raw)) return raw.map(s => String(s).trim()).filter(Boolean);
    if (typeof raw === 'string') {
        try { const p = JSON.parse(raw); if (Array.isArray(p)) return p; } catch(e) {}
        return raw.split(/[,;\s]+/).filter(Boolean);
    }
    return [];
}

function formatDate(iso) {
    if (!iso) return '—';
    const d = new Date(iso);
    return d.toLocaleDateString('id-ID', { day:'2-digit', month:'short', year:'numeric' });
}

function esc(str) {
    if (!str) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ────────────────────────────────────────────────────────────
// EDIT MODAL
// ────────────────────────────────────────────────────────────
function openEditModal(ticketId) {
    const t = allTicketsList.find(x => x.id === ticketId);
    if (!t) { showToast('Tiket tidak ditemukan', 'error'); return; }

    document.getElementById('edit-ticket-id').value     = t.id;
    document.getElementById('edit-ticket-number').value = t.ticket_number || '';
    document.getElementById('edit-name').value          = t.name || '';
    document.getElementById('edit-phone').value         = t.phone || '';
    document.getElementById('edit-ticket-type').value   = t.ticket_type ? (Array.isArray(t.ticket_type) ? t.ticket_type[0] : t.ticket_type) : '';
    document.getElementById('edit-primary-type').value  = t.primary_ticket_type || '';
    document.getElementById('edit-total-seats').value   = t.total_seats || 1;
    document.getElementById('edit-price').value         = t.price || 0;
    document.getElementById('edit-status').value        = t.status || 'pending';

    // Seats
    editSelectedSeats = parseSeats(t);
    renderEditSeatChips();
    buildEditSeatDropdown();

    document.getElementById('editModalOverlay').classList.add('open');
}

function closeEditModal() {
    document.getElementById('editModalOverlay').classList.remove('open');
    editSelectedSeats = [];
}

function handleEditOverlayClick(e) {
    if (e.target === document.getElementById('editModalOverlay')) closeEditModal();
}

// Edit seat dropdown
function toggleEditSeatDropdown() {
    const dd      = document.getElementById('editSeatDropdown');
    const trigger = document.getElementById('editSeatDropdownTrigger');
    const isOpen  = dd.classList.contains('open');
    if (isOpen) {
        dd.classList.remove('open');
        trigger.classList.remove('open');
    } else {
        buildEditSeatDropdown();
        dd.classList.add('open');
        trigger.classList.add('open');
        document.getElementById('editSeatSearchInput').focus();
    }
}

function buildEditSeatDropdown() {
    const list = document.getElementById('editSeatDropdownList');
    renderSeatItems(list, ALL_SEATS, editSelectedSeats, new Set(), function(seat) {
        toggleEditSeat(seat);
    });
}

function filterEditSeatDropdown() {
    const q = document.getElementById('editSeatSearchInput').value.toLowerCase();
    const filtered = ALL_SEATS.filter(s => s.toLowerCase().includes(q));
    const list = document.getElementById('editSeatDropdownList');
    renderSeatItems(list, filtered, editSelectedSeats, new Set(), function(seat) {
        toggleEditSeat(seat);
    });
}

function toggleEditSeat(seat) {
    const idx = editSelectedSeats.indexOf(seat);
    if (idx === -1) editSelectedSeats.push(seat);
    else editSelectedSeats.splice(idx, 1);
    renderEditSeatChips();
    buildEditSeatDropdown();
    filterEditSeatDropdown();
    document.getElementById('edit-total-seats').value = editSelectedSeats.length || parseInt(document.getElementById('edit-total-seats').value) || 1;
}

function renderEditSeatChips() {
    const wrap  = document.getElementById('editSelectedSeatsChips');
    const label = document.getElementById('editSeatDropdownLabel');
    wrap.innerHTML = '';
    if (!editSelectedSeats.length) {
        label.textContent = 'Pilih kursi…';
        label.style.color = '#94a3b8';
        return;
    }
    label.textContent = `${editSelectedSeats.length} kursi dipilih`;
    label.style.color = '#1e293b';
    editSelectedSeats.forEach(seat => {
        const type   = SEAT_CONFIG[seat] || 'reguler';
        const colors = TYPE_COLORS[type] || TYPE_COLORS.reguler;
        const chip   = document.createElement('span');
        chip.className = 'seat-chip';
        chip.style.cssText = `background:${colors.bg};color:${colors.color};border-color:${colors.border};`;
        chip.innerHTML = `${seat} <span class="chip-remove">✕</span>`;
        chip.onclick = () => toggleEditSeat(seat);
        wrap.appendChild(chip);
    });
}

function onEditTicketTypeChange() { /* no-op for now, can add sync */ }
function onEditPrimaryTypeChange() { /* no-op */ }

async function saveEditTicket() {
    const id           = document.getElementById('edit-ticket-id').value;
    const ticketNumber = document.getElementById('edit-ticket-number').value.trim();
    const name         = document.getElementById('edit-name').value.trim();
    const phone        = document.getElementById('edit-phone').value.trim();
    const ticketType   = document.getElementById('edit-ticket-type').value;
    const primaryType  = document.getElementById('edit-primary-type').value;
    const totalSeats   = parseInt(document.getElementById('edit-total-seats').value) || 1;
    const price        = parseInt(document.getElementById('edit-price').value) || 0;
    const status       = document.getElementById('edit-status').value;

    if (!name || !ticketNumber) {
        showToast('❌ Nama dan nomor tiket wajib diisi', 'error');
        return;
    }

    const btn = document.getElementById('saveEditBtn');
    btn.disabled    = true;
    btn.textContent = '⏳ Menyimpan...';

    try {
        const patch = {
            ticket_number:       ticketNumber,
            name:                name,
            phone:               phone.replace(/[^0-9]/g, ''),
            ticket_type:         Array(totalSeats).fill(ticketType),
            primary_ticket_type: primaryType,
            seat_numbers:        editSelectedSeats.length > 0 ? editSelectedSeats : null,
            total_seats:         totalSeats,
            price:               price,
            status:              status,
        };

        await SB.updateTicket(id, patch);

        // Update local cache
        const idx = allTicketsList.findIndex(x => x.id === id);
        if (idx !== -1) allTicketsList[idx] = { ...allTicketsList[idx], ...patch };

        showToast('✅ Tiket berhasil diupdate!', 'success');
        closeEditModal();
        renderTicketList();

    } catch(err) {
        showToast('❌ Gagal update: ' + err.message, 'error');
    } finally {
        btn.disabled    = false;
        btn.textContent = '💾 Simpan Perubahan';
    }
}

// ────────────────────────────────────────────────────────────
// DELETE
// ────────────────────────────────────────────────────────────
function confirmDelete(id, ticketNumber) {
    deleteTargetId = id;
    document.getElementById('confirmDeleteMsg').textContent =
        `Tiket "${ticketNumber}" akan dihapus permanen dari database dan tidak bisa dikembalikan.`;
    document.getElementById('confirmDeleteOverlay').classList.add('open');
}
function closeConfirmDelete() {
    deleteTargetId = null;
    document.getElementById('confirmDeleteOverlay').classList.remove('open');
}
async function executeDelete() {
    if (!deleteTargetId) return;
    const btn = document.getElementById('confirmDeleteOkBtn');
    btn.disabled    = true;
    btn.textContent = '⏳ Menghapus...';
    try {
        await SB.deleteTicket(deleteTargetId);
        allTicketsList = allTicketsList.filter(t => t.id !== deleteTargetId);
        renderTicketList();
        showToast('🗑️ Tiket berhasil dihapus', 'success');
        closeConfirmDelete();
    } catch(err) {
        showToast('❌ Gagal hapus: ' + err.message, 'error');
    } finally {
        btn.disabled    = false;
        btn.textContent = 'Ya, Hapus';
    }
}

// ────────────────────────────────────────────────────────────
// INIT
// ────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', async function() {
    await loadBookedSeats();
    buildSeatDropdown();
});
</script>

</body>
</html>
