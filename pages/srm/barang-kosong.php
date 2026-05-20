<?php
$v_css = file_exists(__DIR__ . '/style.css') ? substr(md5_file(__DIR__ . '/style.css'), 0, 8) : '1';
$v_js  = file_exists(__DIR__ . '/app.js')    ? substr(md5_file(__DIR__ . '/app.js'),    0, 8) : '1';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barang Kosong – Sri Rejeki Motor</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=<?= $v_css ?>">

    <style>
        /* ═══════════════════════════════════════════════════
           BARANG KOSONG — PAGE-SPECIFIC STYLES
        ═══════════════════════════════════════════════════ */

        /* ── Page Header ─────────────────────────────────── */
        .bk-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }
        .bk-title {
            font-size: 32px;
            font-weight: 700;
            color: var(--text-primary);
            line-height: 1.1;
            letter-spacing: -0.5px;
        }
        .bk-title span {
            color: #ef4444;
        }
        .bk-subtitle {
            font-size: 14px;
            color: var(--text-muted);
            margin-top: 5px;
        }

        /* ── Stat Cards ──────────────────────────────────── */
        .bk-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 14px;
            margin-bottom: 24px;
        }
        .stat-card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 18px 20px;
            display: flex;
            align-items: center;
            gap: 14px;
            transition: box-shadow 0.2s;
        }
        .stat-card:hover { box-shadow: 0 4px 20px rgba(0,0,0,0.07); }
        .stat-icon {
            width: 44px; height: 44px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .stat-icon.red   { background: rgba(239,68,68,0.1);  color: #ef4444; }
        .stat-icon.blue  { background: rgba(59,130,246,0.1); color: #3b82f6; }
        .stat-icon.purple{ background: rgba(124,58,237,0.1); color: #7c3aed; }
        .stat-body { display: flex; flex-direction: column; gap: 2px; }
        .stat-value {
            font-size: 26px;
            font-weight: 700;
            color: var(--text-primary);
            line-height: 1;
        }
        .stat-value.red { color: #ef4444; }
        .stat-label {
            font-size: 12px;
            color: var(--text-muted);
            font-weight: 500;
        }

        /* ── Toggle Pills ────────────────────────────────── */
        .bk-toggle-wrap {
            display: flex;
            align-items: center;
            gap: 10px;
            background: var(--bg, #f9fafb);
            border: 1px solid var(--border, #e5e7eb);
            border-radius: 14px;
            padding: 6px 10px 6px 14px;
        }
        .bk-toggle-label {
            font-size: 13px; font-weight: 500;
            color: var(--text-muted); white-space: nowrap;
        }
        .bk-toggle-pills {
            display: flex;
            background: var(--border, #e5e7eb);
            border-radius: 9px;
            padding: 3px; gap: 3px;
        }
        .bk-pill {
            padding: 5px 14px; border-radius: 7px;
            font-size: 13px; font-weight: 600;
            cursor: pointer; border: none;
            background: transparent;
            color: var(--text-muted);
            transition: all 0.18s;
            font-family: 'Outfit', sans-serif;
        }
        .bk-pill.active {
            background: #fff;
            color: #ef4444;
            box-shadow: 0 1px 6px rgba(0,0,0,0.12);
        }
        .bk-pill[data-filter="non_ppn"].active { color: #92400e; }
        .bk-pill[data-filter="ppn"].active     { color: #7c3aed; }

        /* ── Toolbar ─────────────────────────────────────── */
        .bk-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 0;
        }
        .bk-toolbar-left  { display: flex; align-items: center; gap: 12px; }
        .bk-toolbar-right { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }

        /* ── Export PDF Button ───────────────────────────── */
        .btn-export-pdf {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 18px;
            border-radius: 10px;
            border: none;
            background: #ef4444;
            color: #fff;
            font-family: 'Outfit', sans-serif;
            font-size: 13px; font-weight: 600;
            cursor: pointer;
            transition: all 0.18s;
            box-shadow: 0 4px 14px rgba(239,68,68,0.28);
            white-space: nowrap;
        }
        .btn-export-pdf:hover {
            background: #dc2626;
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(239,68,68,0.35);
        }
        .btn-export-pdf:active { transform: translateY(0); }
        .btn-export-pdf:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }

        /* ── Refresh Button ──────────────────────────────── */
        .btn-bk-refresh {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 8px 14px; border-radius: 10px;
            border: 1px solid var(--border); background: #fff;
            color: var(--text-secondary);
            font-family: 'Outfit', sans-serif; font-size: 13px; font-weight: 500;
            cursor: pointer; transition: all 0.15s;
        }
        .btn-bk-refresh:hover { border-color: #3b82f6; color: #3b82f6; }

        /* ── Table Wrapper ───────────────────────────────── */
        .bk-table-wrapper {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }
        .bk-table-header {
            display: flex; align-items: center;
            justify-content: space-between;
            padding: 16px 20px;
            border-bottom: 1px solid var(--border);
            background: #fafbfc;
            flex-wrap: wrap; gap: 10px;
        }
        .bk-table-title {
            font-size: 14px; font-weight: 600;
            color: var(--text-primary);
            display: flex; align-items: center; gap: 10px;
        }
        .bk-count-badge {
            display: inline-flex; align-items: center;
            background: rgba(239,68,68,0.1);
            color: #ef4444;
            border: 1px solid rgba(239,68,68,0.2);
            font-size: 11px; font-weight: 700;
            padding: 2px 9px; border-radius: 20px;
        }

        /* ── Table ───────────────────────────────────────── */
        .bk-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        .bk-table thead tr {
            background: #f8fafc;
            border-bottom: 1px solid var(--border);
        }
        .bk-table th {
            padding: 11px 14px;
            text-align: left;
            font-size: 11px;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.06em;
            white-space: nowrap;
        }
        .bk-table th.th-center { text-align: center; }
        .bk-table td {
            padding: 12px 14px;
            border-bottom: 1px solid #f3f4f6;
            color: var(--text-primary);
            vertical-align: middle;
        }
        .bk-table tbody tr:last-child td { border-bottom: none; }
        .bk-table tbody tr:hover td { background: #fef2f2; }

        /* Gudang badge */
        .gudang-badge {
            display: inline-flex; align-items: center; gap: 4px;
            font-size: 10px; font-weight: 700;
            padding: 2px 8px; border-radius: 6px;
            white-space: nowrap; letter-spacing: 0.04em;
        }
        .gudang-badge.non-ppn { background: rgba(234,179,8,0.15); color: #92400e; border: 1px solid rgba(234,179,8,0.4); }
        .gudang-badge.ppn     { background: rgba(124,58,237,0.1); color: #7c3aed; border: 1px solid rgba(124,58,237,0.2); }

        /* Stok kosong badge */
        .stok-zero {
            display: inline-flex; align-items: center; gap: 5px;
            background: rgba(239,68,68,0.08);
            color: #ef4444; border: 1px solid rgba(239,68,68,0.18);
            font-size: 11px; font-weight: 700;
            padding: 3px 10px; border-radius: 8px;
        }
        .stok-zero::before {
            content: '';
            width: 6px; height: 6px; border-radius: 50%;
            background: #ef4444; flex-shrink: 0;
        }

        /* Price cell */
        .td-price { font-weight: 600; white-space: nowrap; }
        .price-buy  { color: var(--text-secondary); }
        .price-sell { color: #059669; }

        /* Row number */
        .td-num { font-size: 11px; color: var(--text-muted); font-weight: 500; }

        /* Lokasi rak */
        .td-rak { color: #3b82f6; font-weight: 600; font-family: monospace; letter-spacing: 0.04em; }

        /* Code */
        .td-kode { font-family: monospace; font-size: 12px; color: var(--text-secondary); }

        /* Empty / Loading state */
        .bk-state-row td {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-muted);
        }
        .bk-state-icon {
            width: 56px; height: 56px;
            border-radius: 16px;
            background: rgba(239,68,68,0.08);
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 14px;
            color: #ef4444;
        }
        .bk-state-title { font-size: 15px; font-weight: 600; color: var(--text-primary); margin-bottom: 4px; }
        .bk-state-desc  { font-size: 13px; color: var(--text-muted); }

        /* Search */
        .bk-search {
            display: flex; align-items: center; gap: 8px;
            background: #f9fafb; border: 1px solid var(--border);
            border-radius: 10px; padding: 8px 12px;
            min-width: 200px;
        }
        .bk-search svg { color: var(--text-muted); flex-shrink: 0; }
        .bk-search input {
            border: none; background: transparent;
            font-family: 'Outfit', sans-serif; font-size: 13px;
            color: var(--text-primary); outline: none;
            width: 100%;
        }
        .bk-search input::placeholder { color: var(--text-muted); }

        /* ── Notification toast ──────────────────────────── */
        .notification {
            display: none; align-items: center; gap: 10px;
            padding: 12px 16px; border-radius: 10px;
            font-size: 14px; font-weight: 500;
            position: fixed; top: 20px; right: 20px; z-index: 2000;
            max-width: 380px; box-shadow: 0 8px 24px rgba(0,0,0,0.2);
        }
        .notification.success { background: rgba(52,211,153,0.12); border: 1px solid rgba(52,211,153,0.3); color: #10b981; display: flex; }
        .notification.error   { background: rgba(248,113,113,0.12); border: 1px solid rgba(248,113,113,0.3); color: #ef4444; display: flex; }

        /* ── Responsive ──────────────────────────────────── */
        @media (max-width: 900px) {
            .bk-stats { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 640px) {
            .bk-stats { grid-template-columns: 1fr; }
            .bk-header { flex-direction: column; align-items: stretch; }
            .bk-toolbar { flex-direction: column; align-items: stretch; }
            .bk-toolbar-right { justify-content: flex-end; }
            .bk-title { font-size: 26px; }
            .bk-table { font-size: 12px; }
            .bk-table td, .bk-table th { padding: 9px 10px; }
        }

        /* ── PDF Preview overlay (hidden, used for jsPDF generation) */
        #pdfHiddenContent { display: none !important; }
    </style>
</head>
<body>

<!-- Notification toast -->
<div class="notification" id="notification"><span class="notif-icon"></span><span class="notif-msg" id="notifMsg"></span></div>

<!-- ════════════════════════════════════════════════
     APP SHELL
════════════════════════════════════════════════ -->
<div class="app-wrapper">
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <div class="brand-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                    <polyline points="9 22 9 12 15 12 15 22"/>
                </svg>
            </div>
            <div class="brand-text">
                <span class="brand-name">Sri Rejeki Motor</span>
                <span class="brand-sub">Manajemen Toko</span>
            </div>
        </div>
        <nav class="sidebar-nav">
            <a href="index.php" class="nav-item" onclick="closeMobileSidebar()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/>
                    <rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>
                </svg>
                <span>Dashboard</span>
            </a>
            <div class="nav-section-label">Gudang</div>
            <a href="gudang.php" class="nav-item" onclick="closeMobileSidebar()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/>
                    <rect x="9" y="3" width="6" height="4" rx="1"/>
                    <line x1="9" y1="12" x2="15" y2="12"/><line x1="9" y1="16" x2="13" y2="16"/>
                </svg>
                <span>Data Gudang</span>
            </a>
            <a href="barang-kosong.php" class="nav-item active" onclick="closeMobileSidebar()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/>
                    <line x1="12" y1="12" x2="12" y2="12.01"/>
                </svg>
                <span>Barang Kosong</span>
            </a>
            <a href="label-print.php" class="nav-item" onclick="closeMobileSidebar()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <polyline points="6 9 6 2 18 2 18 9"/>
                    <path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/>
                    <rect x="6" y="14" width="12" height="8"/>
                </svg>
                <span>Cetak Label</span>
            </a>
            <a href="generate-kode.php" class="nav-item" onclick="closeMobileSidebar()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/>
                    <path d="M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15"/>
                </svg>
                <span>Generate Kode</span>
            </a>
            <div class="nav-section-label">Kasir</div>
            <a href="kasir-penjualan.php" class="nav-item" onclick="closeMobileSidebar()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <rect x="2" y="3" width="20" height="14" rx="2"/>
                    <line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/>
                </svg>
                <span>Penjualan</span>
            </a>
            <a href="kasir-pembelian.php" class="nav-item" onclick="closeMobileSidebar()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/>
                    <line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/>
                </svg>
                <span>Pembelian</span>
            </a>
        <a href="riwayat-penjualan.php" class="nav-item" onclick="closeMobileSidebar()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                <polyline points="14 2 14 8 20 8"/>
                <line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>
                <polyline points="10 9 9 9 8 9"/>
            </svg>
            <span>Riwayat Penjualan</span>
        </a>
        </nav>
        <div class="sidebar-footer">
            <div class="sheet-status">
                <div class="status-dot"></div>
                <span>Terhubung ke Spreadsheet</span>
            </div>
        </div>
    </aside>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <main class="main-content">
        <!-- Mobile hamburger -->
        <header class="page-header">
            <div class="header-left">
                <button class="hamburger" id="hamburgerBtn">
                    <span></span><span></span><span></span>
                </button>
            </div>
        </header>

        <!-- ── Page Header ────────────────────────── -->
        <div class="bk-header">
            <div>
                <h1 class="bk-title">Barang <span>Kosong</span></h1>
                <p class="bk-subtitle" id="bkSubtitle">Memuat laporan stok habis...</p>
            </div>
            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                <div class="bk-toggle-wrap">
                    <span class="bk-toggle-label">Tampilkan</span>
                    <div class="bk-toggle-pills">
                        <button class="bk-pill active" data-filter="semua">Semua</button>
                        <button class="bk-pill" data-filter="non_ppn">BC Kuning</button>
                        <button class="bk-pill" data-filter="ppn">PPN</button>
                    </div>
                </div>
                <button class="btn-export-pdf" id="btnExportPDF" disabled>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14">
                        <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                        <line x1="12" y1="18" x2="12" y2="12"/>
                        <polyline points="9 15 12 18 15 15"/>
                    </svg>
                    Export PDF
                </button>
            </div>
        </div>

        <!-- ── Stat Cards ─────────────────────────── -->
        <div class="bk-stats">
            <div class="stat-card">
                <div class="stat-icon red">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="22" height="22">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="8" y1="12" x2="16" y2="12"/>
                    </svg>
                </div>
                <div class="stat-body">
                    <span class="stat-value red" id="statTotal">—</span>
                    <span class="stat-label">Total Barang Kosong</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon blue">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="22" height="22">
                        <rect x="1" y="3" width="15" height="13" rx="2"/>
                        <path d="M16 8h6l2 4v3h-8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>
                    </svg>
                </div>
                <div class="stat-body">
                    <span class="stat-value" id="statNonPpn">—</span>
                    <span class="stat-label">Gudang BC Kuning</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon purple">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="22" height="22">
                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                    </svg>
                </div>
                <div class="stat-body">
                    <span class="stat-value" id="statPpn">—</span>
                    <span class="stat-label">Gudang PPN</span>
                </div>
            </div>
        </div>

        <!-- ── Table ──────────────────────────────── -->
        <div class="bk-table-wrapper">
            <div class="bk-table-header">
                <div class="bk-table-title">
                    Daftar Barang Stok Habis
                    <span class="bk-count-badge" id="bkCountBadge">—</span>
                </div>
                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                    <div class="bk-search">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14">
                            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                        </svg>
                        <input type="text" id="bkSearch" placeholder="Cari nama, kode, merk, supplier...">
                    </div>
                    <button class="btn-bk-refresh" id="btnRefresh">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13">
                            <polyline points="23 4 23 10 17 10"/>
                            <path d="M20.49 15a9 9 0 11-2.12-9.36L23 10"/>
                        </svg>
                        Refresh
                    </button>
                </div>
            </div>

            <div style="overflow-x:auto;">
                <table class="bk-table">
                    <thead>
                        <tr>
                            <th style="width:40px">#</th>
                            <th>Kode Barang</th>
                            <th>Nama Produk</th>
                            <th>Nama Mobil</th>
                            <th>Merk</th>
                            <th>Lokasi Rak</th>
                            <th>Supplier</th>
                            <th class="th-center">Gudang</th>
                            <th>Harga Beli</th>
                            <th>Harga Jual</th>
                            <th class="th-center">Stok</th>
                        </tr>
                    </thead>
                    <tbody id="bkTableBody">
                        <tr class="bk-state-row">
                            <td colspan="11">
                                <div class="bk-state-icon" style="background:rgba(59,130,246,0.08);color:#3b82f6;">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="26" height="26">
                                        <circle cx="12" cy="12" r="10"/>
                                        <polyline points="12 6 12 12 16 14"/>
                                    </svg>
                                </div>
                                <div class="bk-state-title">Memuat data...</div>
                                <div class="bk-state-desc">Mengambil data dari spreadsheet</div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </main>
</div>

<!-- ─── jsPDF + AutoTable (CDN) ─────────────────────────── -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
<script src="app.js?v=<?= $v_js ?>"></script>

<script>
// ================================================================
// KONFIGURASI
// ================================================================
const SPREADSHEET_ID = '1zQtkYOkwWjPPll_D0u7pJNTYebWGTMl3ilPwnEIWVKQ';
const API_NON_PPN = `https://opensheet.elk.sh/${SPREADSHEET_ID}/non_ppn`;
const API_PPN     = `https://opensheet.elk.sh/${SPREADSHEET_ID}/ppn`;
const CACHE_TTL   = 5 * 60 * 1000;

// ── STATE
let allEmpty = [];
let activeFilter = 'semua';

// ================================================================
// UTIL
// ================================================================
function getStokNum(val){
    const n = parseInt(String(val||'0').replace(/\D/g,''),10);
    return isNaN(n)?0:n;
}

function formatRp(val){
    const n = parseInt(String(val||'0').replace(/\D/g,''),10);
    return (!n||isNaN(n)) ? '—' : 'Rp '+n.toLocaleString('id-ID');
}

function esc(s){
    return String(s||'').replace(/[&<>]/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;'}[m]));
}

function safeAddEvent(id,event,handler){
    const el = document.getElementById(id);
    if(el) el.addEventListener(event,handler);
}

// ================================================================
// SIDEBAR (ANTI ERROR)
// ================================================================
safeAddEvent('hamburgerBtn','click',()=>{
    document.getElementById('sidebar')?.classList.toggle('active');
    document.getElementById('sidebarOverlay')?.classList.toggle('active');
});

safeAddEvent('sidebarOverlay','click',()=>{
    document.getElementById('sidebar')?.classList.remove('active');
    document.getElementById('sidebarOverlay')?.classList.remove('active');
});

// ================================================================
// FETCH
// ================================================================
async function fetchSheet(url, cacheKey){
    try{
        const cached = JSON.parse(localStorage.getItem(cacheKey)||'null');
        if(cached && Date.now()-cached.ts < CACHE_TTL) return cached.data;
    }catch(e){}

    const res = await fetch(url);
    const data = await res.json();

    localStorage.setItem(cacheKey, JSON.stringify({data,ts:Date.now()}));
    return Array.isArray(data)?data:[];
}

// ================================================================
// LOAD DATA (LOGIKA FINAL)
// ================================================================
async function loadData(force=false){
    const tbody = document.getElementById('bkTableBody');

    tbody.innerHTML = `<tr><td colspan="11" style="text-align:center;padding:40px">Loading...</td></tr>`;

    if(force){
        localStorage.removeItem('srm_gudang_non_ppn');
        localStorage.removeItem('srm_gudang_ppn');
    }

    try{
        const [non,ppn] = await Promise.all([
            fetchSheet(API_NON_PPN,'srm_gudang_non_ppn'),
            fetchSheet(API_PPN,'srm_gudang_ppn')
        ]);

        const allRows = [
            ...non.map(r=>({...r,_gudang:'non_ppn'})),
            ...ppn.map(r=>({...r,_gudang:'ppn'}))
        ];

// GROUP BY kode_barang
const grouped = {};

allRows.forEach(r=>{
    const kode = (r.kode_barang || r[0] || '')
    .toLowerCase()
    .replace(/[\s\-_.]/g, '') // 🔥 hapus spasi, strip, titik
    .trim();
    if(!kode) return;

    (grouped[kode] ||= []).push(r);
});

// FILTER: hanya tampil jika SEMUA supplier kosong
allEmpty = [];

Object.values(grouped).forEach(group=>{
    const semuaKosong = group.every(r => getStokNum(r.stok || r[7]) === 0);

    if(semuaKosong){
allEmpty.push({
    ...group[0],
    _gudang_list: [...new Set(group.map(r => r._gudang))],
    _total_supplier: group.length,

    // 🔥 TAMBAHAN PENTING
    _supplier_list: [...new Set(
        group.map(r => (r.supplier || r[8] || '').trim()).filter(s => s)
    )]
});
    }
});

        updateStats();
        renderTable();

        // Enable PDF button once data is loaded
        const btnPDF = document.getElementById('btnExportPDF');
        if(btnPDF) btnPDF.disabled = false;
        const sub = document.getElementById('bkSubtitle');
        if(sub) sub.textContent = `${allEmpty.length} barang dengan stok habis · Diperbarui ${new Date().toLocaleTimeString('id-ID')}`;

    }catch(e){
        console.error(e);
        tbody.innerHTML = `<tr><td colspan="11" style="text-align:center;color:red">Gagal load data</td></tr>`;
    }
}

// ================================================================
// STATS (FIXED)
// ================================================================
function updateStats(){
    const non = allEmpty.filter(r=>r._gudang_list.includes('non_ppn')).length;
    const ppn = allEmpty.filter(r=>r._gudang_list.includes('ppn')).length;

    document.getElementById('statTotal').textContent = allEmpty.length;
    document.getElementById('statNonPpn').textContent = non;
    document.getElementById('statPpn').textContent = ppn;
}

// ================================================================
// FILTER
// ================================================================
function getVisibleRows(){
    if(activeFilter==='semua') return allEmpty;
    return allEmpty.filter(r=>r._gudang_list.includes(activeFilter));
}

// ================================================================
// RENDER TABLE (FIX TOTAL)
// ================================================================
function renderTable(){
    const q = (document.getElementById('bkSearch')?.value||'').toLowerCase().trim();
    let rows = getVisibleRows();
    if(q){
        rows = rows.filter(r=>{
            const haystack = [
                r.kode_barang||r[0]||'',
                r.nama_produk||r[3]||'',
                r.nama_mobil||r[4]||'',
                r.merk||r[2]||'',
                (r._supplier_list||[]).join(' ')
            ].join(' ').toLowerCase();
            return haystack.includes(q);
        });
    }
    const tbody = document.getElementById('bkTableBody');
    const badge = document.getElementById('bkCountBadge');

    badge.textContent = rows.length + ' barang';

    if(!rows.length){
        tbody.innerHTML = `<tr><td colspan="11" style="text-align:center;padding:40px">Tidak ada data</td></tr>`;
        return;
    }

    tbody.innerHTML = rows.map((r,i)=>{
        const g = r._gudang_list;

        let label='PPN';
        let cls='ppn';

        if(g.includes('non_ppn') && g.includes('ppn')){
            label='Campuran'; cls='ppn';
        }else if(g.includes('non_ppn')){
            label='BC Kuning'; cls='non-ppn';
        }

        return `
        <tr>
            <td>${i+1}</td>
            <td>${esc(r.kode_barang||r[0])}</td>
            <td>${esc(r.nama_produk||r[3])}</td>
            <td>${esc(r.nama_mobil||r[4])}</td>
            <td>${esc(r.merk||r[2])}</td>
            <td>${esc(r.lokasi_rak||r[6])}</td>
            <td style="font-size:12px">
    ${esc((r._supplier_list || []).join(', ') || '—')}
</td>
            <td style="text-align:center">
                <span class="gudang-badge ${cls}">${label}</span>
            </td>
            <td>${formatRp(r.harga_beli||r[9])}</td>
            <td>${formatRp(r.harga_jual||r[10])}</td>
            <td style="text-align:center"><span class="stok-zero">0</span></td>
        </tr>`;
    }).join('');
}

// ================================================================
// EXPORT PDF
// ================================================================
function exportPDF(){
    const btn = document.getElementById('btnExportPDF');
    if(!btn) return;
    btn.disabled = true;
    btn.textContent = 'Generating...';

    try{
        const { jsPDF } = window.jspdf;
        if(!jsPDF){
            alert('Library jsPDF tidak berhasil dimuat. Pastikan koneksi internet aktif.');
            return;
        }

        const doc = new jsPDF({ orientation:'landscape', unit:'mm', format:'a4' });

        // ── Header
        const now = new Date();
        const tgl = now.toLocaleDateString('id-ID',{weekday:'long',year:'numeric',month:'long',day:'numeric'});
        const jam = now.toLocaleTimeString('id-ID');
        const filterLabel = activeFilter==='semua' ? 'Semua Gudang'
            : activeFilter==='non_ppn' ? 'Gudang BC Kuning' : 'Gudang PPN';

        doc.setFillColor(239,68,68);
        doc.rect(0,0,297,18,'F');
        doc.setTextColor(255,255,255);
        doc.setFontSize(13); doc.setFont('helvetica','bold');
        doc.text('Sri Rejeki Motor — Laporan Barang Kosong', 14, 11);
        doc.setFontSize(8); doc.setFont('helvetica','normal');
        doc.text(`Filter: ${filterLabel}   |   Dicetak: ${tgl}, ${jam}`, 14, 16.5);

        // ── Rows
        const q = (document.getElementById('bkSearch')?.value||'').toLowerCase().trim();
        let rows = getVisibleRows();
        if(q){
            rows = rows.filter(r=>{
                const h = [r.kode_barang||r[0]||'',r.nama_produk||r[3]||'',r.nama_mobil||r[4]||'',r.merk||r[2]||'',(r._supplier_list||[]).join(' ')].join(' ').toLowerCase();
                return h.includes(q);
            });
        }

        const body = rows.map((r,i)=>{
            const g = r._gudang_list;
            let gudang = 'PPN';
            if(g.includes('non_ppn')&&g.includes('ppn')) gudang='Campuran';
            else if(g.includes('non_ppn')) gudang='BC Kuning';
            return [
                i+1,
                r.kode_barang||r[0]||'',
                r.nama_produk||r[3]||'',
                r.nama_mobil||r[4]||'',
                r.merk||r[2]||'',
                r.lokasi_rak||r[6]||'',
                (r._supplier_list||[]).join(', ')||'—',
                gudang,
                formatRp(r.harga_beli||r[9]),
                formatRp(r.harga_jual||r[10]),
                '0'
            ];
        });

        doc.autoTable({
            startY: 22,
            head: [['#','Kode Barang','Nama Produk','Nama Mobil','Merk','Lok. Rak','Supplier','Gudang','Harga Beli','Harga Jual','Stok']],
            body: body,
            styles:{ fontSize:7.5, cellPadding:2.5, font:'helvetica', overflow:'linebreak' },
            headStyles:{ fillColor:[248,113,113], textColor:255, fontStyle:'bold', fontSize:8 },
            alternateRowStyles:{ fillColor:[254,242,242] },
            columnStyles:{
                0:{cellWidth:8,   halign:'center'},
                1:{cellWidth:22},
                2:{cellWidth:54},
                3:{cellWidth:28},
                4:{cellWidth:18},
                5:{cellWidth:16,  halign:'center'},
                6:{cellWidth:36},
                7:{cellWidth:20,  halign:'center'},
                8:{cellWidth:24,  halign:'right'},
                9:{cellWidth:24,  halign:'right'},
                10:{cellWidth:10, halign:'center'},
            },
            tableWidth: 'wrap',
            margin:{ left:14, right:14 },
            didDrawPage: function(data){
                // Footer
                const pageCount = doc.internal.getNumberOfPages();
                doc.setFontSize(7); doc.setTextColor(150);
                doc.text(`Halaman ${data.pageNumber} dari ${pageCount}`, 14, doc.internal.pageSize.height-5);
                doc.text(`Total: ${rows.length} barang kosong`, 297/2, doc.internal.pageSize.height-5, {align:'center'});
            }
        });

        const filename = `barang-kosong_${now.toISOString().slice(0,10)}.pdf`;
        doc.save(filename);
    } catch(err){
        console.error('PDF error:', err);
        alert('Gagal generate PDF: ' + err.message);
    } finally {
        btn.disabled = false;
        btn.innerHTML = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><polyline points="9 15 12 18 15 15"/></svg> Export PDF`;
    }
}

// ================================================================
// EVENT
// ================================================================
document.querySelectorAll('.bk-pill').forEach(btn=>{
    btn.addEventListener('click',function(){
        document.querySelectorAll('.bk-pill').forEach(b=>b.classList.remove('active'));
        this.classList.add('active');
        activeFilter = this.dataset.filter;
        renderTable();
    });
});

safeAddEvent('bkSearch','input',()=>renderTable());
safeAddEvent('btnRefresh','click',()=>loadData(true));
safeAddEvent('btnExportPDF','click',exportPDF);

// ================================================================
// INIT
// ================================================================
document.addEventListener('DOMContentLoaded',()=>{
    loadData();
});
</script>

</body>
</html>