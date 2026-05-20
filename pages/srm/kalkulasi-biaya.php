<?php
$v_css = file_exists(__DIR__ . '/style.css') ? substr(md5_file(__DIR__ . '/style.css'), 0, 8) : '1';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kalkulasi Biaya - Sri Rejeki Motor</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=<?= $v_css ?>">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
    <style>
        html, body { height: 100%; }
        body {
            display: flex;
            min-height: 100vh;
            background: var(--bg-base);
            font-family: 'Outfit', sans-serif;
            overflow-x: hidden;
        }

        .page-wrap {
            flex: 1;
            min-width: 0;
            margin-left: var(--sidebar-w);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* ── Topbar ── */
        .topbar {
            background: var(--bg-surface);
            border-bottom: 1px solid var(--border);
            padding: 0 24px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            position: sticky;
            top: 0;
            z-index: 50;
            flex-shrink: 0;
        }
        .topbar-left  { display: flex; align-items: center; gap: 12px; min-width: 0; overflow: hidden; }
        .topbar-right { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }
        .hamburger {
            display: none; flex-direction: column; justify-content: center; gap: 5px;
            width: 36px; height: 36px; border: none; background: transparent;
            cursor: pointer; padding: 6px; border-radius: 8px; flex-shrink: 0;
        }
        .hamburger span { display: block; width: 18px; height: 2px; background: var(--text-primary); border-radius: 2px; }
        .topbar-title { font-size: 16px; font-weight: 700; color: var(--text-primary); white-space: nowrap; }
        .topbar-meta  { font-size: 12px; color: var(--text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 320px; }

        .btn-topbar {
            height: 36px; padding: 0 14px;
            border-radius: var(--radius-sm); border: 1px solid var(--border);
            font-family: 'Outfit', sans-serif; font-size: 13px; font-weight: 600;
            cursor: pointer; transition: all .15s;
            display: flex; align-items: center; gap: 6px; white-space: nowrap;
        }
        .btn-topbar.primary   { background: var(--primary); color: #fff; border-color: var(--primary); }
        .btn-topbar.primary:hover { background: #1d4ed8; }
        .btn-topbar.ghost     { background: var(--bg-surface); color: var(--text-secondary); }
        .btn-topbar.ghost:hover { background: var(--bg-elevated); }
        .btn-topbar.pdf       { background: #dc2626; color: #fff; border-color: #dc2626; }
        .btn-topbar.pdf:hover { background: #b91c1c; }
        .btn-topbar:disabled  { opacity: .45; cursor: not-allowed; pointer-events: none; }

        /* ── Content ── */
        .content { flex: 1; padding: 20px 24px 40px; min-width: 0; }

        /* ── Summary Cards ── */
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-bottom: 18px;
        }
        .sum-card {
            background: var(--bg-surface); border: 1px solid var(--border);
            border-radius: var(--radius-md); padding: 15px 16px;
            position: relative; overflow: hidden; transition: box-shadow .2s;
        }
        .sum-card:hover { box-shadow: var(--shadow-md); }
        .sum-card::after {
            content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px;
            border-radius: var(--radius-md) var(--radius-md) 0 0;
        }
        .sum-card.blue::after   { background: linear-gradient(90deg,#3b82f6,#60a5fa); }
        .sum-card.green::after  { background: linear-gradient(90deg,#10b981,#34d399); }
        .sum-card.orange::after { background: linear-gradient(90deg,#f59e0b,#fbbf24); }
        .sum-card.purple::after { background: linear-gradient(90deg,#8b5cf6,#a78bfa); }
        .sum-label { font-size: 10.5px; font-weight: 600; text-transform: uppercase; letter-spacing: .07em; color: var(--text-muted); margin-bottom: 6px; }
        .sum-value { font-size: 19px; font-weight: 700; color: var(--text-primary); line-height: 1.2; margin-bottom: 3px; word-break: break-all; }
        .sum-sub   { font-size: 11px; color: var(--text-muted); }
        .sum-icon  {
            position: absolute; top: 14px; right: 14px;
            width: 30px; height: 30px; border-radius: var(--radius-sm);
            display: flex; align-items: center; justify-content: center;
        }
        .sum-icon svg { width: 14px; height: 14px; }
        .sum-card.blue .sum-icon   { background: rgba(59,130,246,.1);  color:#3b82f6; }
        .sum-card.green .sum-icon  { background: rgba(16,185,129,.1);  color:#10b981; }
        .sum-card.orange .sum-icon { background: rgba(245,158,11,.1);  color:#f59e0b; }
        .sum-card.purple .sum-icon { background: rgba(139,92,246,.1);  color:#8b5cf6; }

        /* ── Toolbar ── */
        .toolbar {
            background: var(--bg-surface); border: 1px solid var(--border);
            border-radius: var(--radius-md); padding: 11px 14px;
            display: flex; align-items: center; gap: 10px;
            margin-bottom: 12px; flex-wrap: wrap;
        }
        .search-wrap { position: relative; flex: 1; min-width: 180px; }
        .search-wrap svg {
            position: absolute; left: 10px; top: 50%; transform: translateY(-50%);
            color: var(--text-muted); width: 14px; height: 14px; pointer-events: none;
        }
        .search-input {
            width: 100%; height: 34px; padding: 0 10px 0 30px;
            background: var(--bg-elevated); border: 1px solid var(--border);
            border-radius: var(--radius-sm); font-family: 'Outfit', sans-serif;
            font-size: 13px; color: var(--text-primary); outline: none;
            transition: border-color .15s, background .15s;
        }
        .search-input:focus { border-color: var(--primary); background: var(--bg-surface); }
        .search-input::placeholder { color: var(--text-muted); }

        .sheet-tabs {
            display: flex; background: var(--bg-elevated);
            border: 1px solid var(--border); border-radius: 8px; padding: 3px; gap: 2px;
        }
        .sheet-tab {
            padding: 4px 12px; border-radius: 6px; font-size: 13px; font-weight: 600;
            cursor: pointer; border: none; background: transparent; color: var(--text-muted);
            transition: all .15s; font-family: 'Outfit', sans-serif; white-space: nowrap;
        }
        .sheet-tab.active { background: var(--bg-surface); color: var(--primary); box-shadow: 0 1px 4px rgba(0,0,0,.1); }

        .btn-sm {
            height: 34px; padding: 0 12px; border-radius: var(--radius-sm);
            border: 1px solid var(--border); font-family: 'Outfit', sans-serif;
            font-size: 13px; font-weight: 600; cursor: pointer; transition: all .15s; white-space: nowrap;
        }
        .btn-sm.primary { background: var(--primary); color: #fff; border-color: var(--primary); }
        .btn-sm.primary:hover { background: #1d4ed8; }
        .btn-sm.ghost { background: var(--bg-surface); color: var(--text-secondary); }
        .btn-sm.ghost:hover { background: var(--bg-elevated); color: var(--red); border-color: var(--red); }

        /* ── Scope badge ── */
        .sel-scope {
            display: inline-flex; align-items: center; gap: 5px;
            font-size: 11.5px; font-weight: 700; padding: 3px 9px;
            border-radius: 99px; white-space: nowrap; transition: all .2s;
        }
        .sel-scope.all     { background: rgba(16,185,129,.12); color: #059669; border: 1px solid rgba(16,185,129,.3); }
        .sel-scope.partial { background: rgba(59,130,246,.1);  color: #2563eb; border: 1px solid rgba(59,130,246,.25); }
        .sel-scope.none    { background: var(--bg-elevated);   color: var(--text-muted); border: 1px solid var(--border); }

        /* ── Table ── */
        .table-card {
            background: var(--bg-surface); border: 1px solid var(--border);
            border-radius: var(--radius-md); overflow: hidden;
        }
        .table-scroll { overflow-x: auto; width: 100%; -webkit-overflow-scrolling: touch; }
        table { width: 100%; border-collapse: collapse; min-width: 860px; }
        thead tr { background: var(--bg-elevated); }
        th {
            padding: 9px 11px; font-size: 10.5px; font-weight: 700;
            text-transform: uppercase; letter-spacing: .06em; color: var(--text-muted);
            text-align: left; border-bottom: 1px solid var(--border); white-space: nowrap;
        }
        th.right, td.right { text-align: right; }
        th.center, td.center { text-align: center; }
        td {
            padding: 8px 11px; font-size: 12.5px; color: var(--text-primary);
            border-bottom: 1px solid var(--border); vertical-align: middle;
        }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: rgba(59,130,246,.025); }
        td.price { font-weight: 600; font-variant-numeric: tabular-nums; }
        td.price.green  { color: #10b981; }
        td.price.blue   { color: #3b82f6; }
        td.price.orange { color: #f59e0b; }
        td.muted { color: var(--text-muted); font-size: 11.5px; }
        td.name-cell { max-width: 190px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-weight: 600; }

        .check-col { width: 34px; }
        input[type="checkbox"] { width: 15px; height: 15px; accent-color: var(--primary); cursor: pointer; }

        .stok-num { font-weight: 700; }
        .stok-num.zero { color: var(--red); }
        .stok-num.low  { color: #f59e0b; }
        .stok-num.ok   { color: var(--green); }

        .profit-pill {
            display: inline-flex; align-items: center;
            font-size: 11px; font-weight: 700; padding: 2px 6px; border-radius: 99px;
        }
        .profit-pill.pos { background: rgba(16,185,129,.1); color: #059669; }
        .profit-pill.neg { background: rgba(239,68,68,.1);  color: #dc2626; }

        .state-row td { text-align: center; padding: 44px 0; color: var(--text-muted); font-size: 14px; }
        .spinner {
            display: inline-block; width: 20px; height: 20px;
            border: 2.5px solid var(--border); border-top-color: var(--primary);
            border-radius: 50%; animation: spin .7s linear infinite;
            vertical-align: middle; margin-right: 8px;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        .tfoot-row td {
            background: var(--bg-elevated); font-weight: 700; font-size: 12px;
            border-top: 2px solid var(--border); border-bottom: none;
        }

        /* ── Breakdown card ── */
        .breakdown-card {
            background: var(--bg-surface); border: 1px solid var(--border);
            border-radius: var(--radius-md); padding: 14px 18px; margin-top: 12px;
            display: flex; gap: 20px; flex-wrap: wrap; align-items: center;
        }
        .breakdown-item { display: flex; flex-direction: column; gap: 2px; }
        .breakdown-label { font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: .06em; color: var(--text-muted); }
        .breakdown-value { font-size: 17px; font-weight: 700; color: var(--text-primary); }
        .breakdown-value.green { color: #10b981; }
        .breakdown-value.red   { color: var(--red); }
        .breakdown-value.blue  { color: #3b82f6; }
        .breakdown-divider { width: 1px; height: 38px; background: var(--border); flex-shrink: 0; }
        .breakdown-title    { font-size: 13px; font-weight: 700; color: var(--text-primary); }
        .breakdown-subtitle { font-size: 11.5px; color: var(--text-muted); margin-top: 1px; }

        /* ── Responsive ── */
        @media (max-width: 960px) {
            .page-wrap { margin-left: 0; }
            .hamburger { display: flex; }
            .summary-grid { grid-template-columns: repeat(2,1fr); }
            .topbar { padding: 0 14px; }
            .content { padding: 14px 12px 32px; }
        }
        @media (max-width: 560px) {
            .sum-value { font-size: 16px; }
            .btn-topbar span { display: none; }
        }
    </style>
</head>
<body>

<!-- ════ SIDEBAR ════ -->
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
        <a href="index.php" class="nav-item" onclick="closeSidebar()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/>
                <rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>
            </svg>
            <span>Dashboard</span>
        </a>
        <div class="nav-section-label">Gudang</div>
        <a href="gudang.php" class="nav-item" onclick="closeSidebar()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/>
                <rect x="9" y="3" width="6" height="4" rx="1"/>
                <line x1="9" y1="12" x2="15" y2="12"/><line x1="9" y1="16" x2="13" y2="16"/>
            </svg>
            <span>Data Gudang</span>
        </a>
        <a href="barang-kosong.php" class="nav-item" onclick="closeSidebar()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/>
                <line x1="12" y1="12" x2="12" y2="12.01"/>
            </svg>
            <span>Barang Kosong</span>
        </a>
        <a href="label-print.php" class="nav-item" onclick="closeSidebar()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <polyline points="6 9 6 2 18 2 18 9"/>
                <path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/>
                <rect x="6" y="14" width="12" height="8"/>
            </svg>
            <span>Cetak Label</span>
        </a>
        <a href="generate-kode.php" class="nav-item" onclick="closeSidebar()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/>
                <path d="M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15"/>
            </svg>
            <span>Generate Kode</span>
        </a>
        <div class="nav-section-label">Kasir</div>
        <a href="kasir-penjualan.php" class="nav-item" onclick="closeSidebar()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <rect x="2" y="3" width="20" height="14" rx="2"/>
                <line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/>
            </svg>
            <span>Penjualan</span>
        </a>
        <a href="kasir-pembelian.php" class="nav-item" onclick="closeSidebar()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/>
                <line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/>
            </svg>
            <span>Pembelian</span>
        </a>
        <a href="riwayat-penjualan.php" class="nav-item" onclick="closeSidebar()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                <polyline points="14 2 14 8 20 8"/>
                <line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>
                <polyline points="10 9 9 9 8 9"/>
            </svg>
            <span>Riwayat Penjualan</span>
        </a>
        <div class="nav-section-label">Laporan</div>
        <a href="laporan-keuangan.php" class="nav-item" onclick="closeSidebar()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/>
                <line x1="6" y1="20" x2="6" y2="14"/>
            </svg>
            <span>Laporan Keuangan</span>
        </a>
        <a href="kalkulasi-biaya.php" class="nav-item active" onclick="closeSidebar()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <rect x="4" y="2" width="16" height="20" rx="2"/>
                <line x1="8" y1="10" x2="16" y2="10"/>
                <line x1="8" y1="14" x2="16" y2="14"/>
                <line x1="8" y1="18" x2="12" y2="18"/>
                <path d="M14 2v4h6"/>
            </svg>
            <span>Kalkulasi Biaya</span>
        </a>
    </nav>
    <div class="sidebar-footer">
        <div class="sheet-status">
            <div class="status-dot"></div>
            <span>Terhubung ke Spreadsheet</span>
        </div>
    </div>
</aside>
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<!-- ════ MAIN ════ -->
<div class="page-wrap">
    <header class="topbar">
        <div class="topbar-left">
            <button class="hamburger" id="hamburgerBtn" onclick="toggleSidebar()">
                <span></span><span></span><span></span>
            </button>
            <div style="min-width:0">
                <div class="topbar-title">Kalkulasi Biaya</div>
                <div class="topbar-meta" id="topbarMeta">Memuat data…</div>
            </div>
        </div>
        <div class="topbar-right">
            <button class="btn-topbar pdf" id="btnExportPdf" onclick="exportPdf()" disabled>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14">
                    <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                    <line x1="12" y1="15" x2="12" y2="9"/><polyline points="9 12 12 15 15 12"/>
                </svg>
                <span>Export PDF</span>
            </button>
            <button class="btn-topbar ghost" onclick="loadData()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14">
                    <polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/>
                    <path d="M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15"/>
                </svg>
                <span>Refresh</span>
            </button>
        </div>
    </header>

    <div class="content">

        <!-- Summary Cards -->
        <div class="summary-grid">
            <div class="sum-card blue">
                <div class="sum-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M20 7H4a2 2 0 00-2 2v6a2 2 0 002 2h16a2 2 0 002-2V9a2 2 0 00-2-2z"/>
                        <circle cx="12" cy="12" r="2"/>
                    </svg>
                </div>
                <div class="sum-label">Total Biaya Modal</div>
                <div class="sum-value" id="valTotalModal">—</div>
                <div class="sum-sub" id="subTotalModal">barang dipilih</div>
            </div>
            <div class="sum-card green">
                <div class="sum-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
                        <polyline points="17 6 23 6 23 12"/>
                    </svg>
                </div>
                <div class="sum-label">Estimasi Pendapatan</div>
                <div class="sum-value" id="valTotalJual">—</div>
                <div class="sum-sub">jika semua terjual</div>
            </div>
            <div class="sum-card orange">
                <div class="sum-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <line x1="12" y1="1" x2="12" y2="23"/>
                        <path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/>
                    </svg>
                </div>
                <div class="sum-label">Estimasi Keuntungan</div>
                <div class="sum-value" id="valTotalProfit">—</div>
                <div class="sum-sub">selisih jual – modal</div>
            </div>
            <div class="sum-card purple">
                <div class="sum-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/>
                        <rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>
                    </svg>
                </div>
                <div class="sum-label">Item Dipilih</div>
                <div class="sum-value" id="valTotalItem">—</div>
                <div class="sum-sub" id="subTotalItem">dari — jenis barang</div>
            </div>
        </div>

        <!-- Toolbar -->
        <div class="toolbar">
            <div class="sheet-tabs">
                <button class="sheet-tab active" data-sheet="non_ppn" onclick="switchSheet('non_ppn')">Non-PPN</button>
                <button class="sheet-tab" data-sheet="ppn" onclick="switchSheet('ppn')">PPN</button>
            </div>
            <div class="search-wrap">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                <input type="text" class="search-input" id="searchInput"
                    placeholder="Cari nama, kode, merek, supplier…" oninput="onSearch()">
            </div>
            <button class="btn-sm primary" onclick="selectAll()">Pilih Semua</button>
            <button class="btn-sm ghost" onclick="clearAll()">Kosongkan</button>
            <span class="sel-scope none" id="selScope">Belum dipilih</span>
        </div>

        <!-- Table -->
        <div class="table-card">
            <div class="table-scroll">
                <table id="dataTable">
                    <thead>
                        <tr>
                            <th class="check-col center">
                                <input type="checkbox" id="checkAll" onchange="toggleAll(this)">
                            </th>
                            <th>Kode</th>
                            <th>Nama Produk</th>
                            <th>Merek / Mobil</th>
                            <th>Supplier</th>
                            <th class="right">Stok</th>
                            <th class="right">Harga Beli/unit</th>
                            <th class="right">Harga Jual/unit</th>
                            <th class="right">Total Modal</th>
                            <th class="right">Est. Pendapatan</th>
                            <th class="right">Est. Keuntungan</th>
                            <th class="center">Margin</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        <tr class="state-row">
                            <td colspan="12"><span class="spinner"></span>Memuat data dari spreadsheet…</td>
                        </tr>
                    </tbody>
                    <tfoot id="tableFoot"></tfoot>
                </table>
            </div>
        </div>

        <!-- Breakdown -->
        <div class="breakdown-card">
            <div>
                <div class="breakdown-title">Ringkasan Kalkulasi</div>
                <div class="breakdown-subtitle" id="breakdownSub">Pilih barang untuk melihat kalkulasi</div>
            </div>
            <div class="breakdown-divider"></div>
            <div class="breakdown-item">
                <div class="breakdown-label">Total Modal</div>
                <div class="breakdown-value blue" id="brkModal">Rp 0</div>
            </div>
            <div class="breakdown-divider"></div>
            <div class="breakdown-item">
                <div class="breakdown-label">Est. Pendapatan</div>
                <div class="breakdown-value" id="brkJual">Rp 0</div>
            </div>
            <div class="breakdown-divider"></div>
            <div class="breakdown-item">
                <div class="breakdown-label">Est. Keuntungan</div>
                <div class="breakdown-value green" id="brkProfit">Rp 0</div>
            </div>
            <div class="breakdown-divider"></div>
            <div class="breakdown-item">
                <div class="breakdown-label">Return on Cost</div>
                <div class="breakdown-value" id="brkRoc">0%</div>
            </div>
        </div>

    </div>
</div>

<script>
// ================================================================
// HELPERS
// ================================================================
function rp(n) {
    if (!n && n !== 0) return '—';
    return 'Rp ' + Math.round(n).toLocaleString('id-ID');
}
function rpPdf(n) {
    if (!n && n !== 0) return '-';
    return 'Rp ' + Math.round(n).toLocaleString('id-ID');
}
function parseNum(v) {
    if (!v) return 0;
    return parseInt(String(v).replace(/[^\d]/g, ''), 10) || 0;
}
function parseFloat2(v) {
    if (!v) return 0;
    return parseFloat(String(v).replace(/[^\d.]/g, '')) || 0;
}
// Hitung harga beli efektif:
// 1. Prioritas: harga_final (sudah dipotong diskon)
// 2. Fallback: harga_beli (pricelist asli)
// 3. Fallback khusus: jika harga_beli kosong tapi ada diskon & harga_jual
//    → harga_beli = harga_jual × (1 - diskon/100)
function getHargaBeli(r) {
    const final = parseNum(r['harga_final'] || r[15]);
    if (final) return final;
    const beli = parseNum(r['harga_beli'] || r[9]);
    if (beli) return beli;
    // Fallback: hitung dari harga_jual & diskon
    const jual   = parseNum(r['harga_jual'] || r[10]);
    const diskon = parseFloat2(r['diskon']);
    if (jual && diskon) return Math.round(jual * (1 - diskon / 100));
    return 0;
}
function pct(a, b)    { return b ? (((a-b)/b)*100).toFixed(1)+'%' : '—'; }
function pctPdf(a, b) { return b ? (((a-b)/b)*100).toFixed(1)+'%' : '-'; }
function escHtml(s)   { return String(s??'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
function nowStr() {
    return new Date().toLocaleDateString('id-ID',{day:'2-digit',month:'long',year:'numeric'})
        + ' ' + new Date().toLocaleTimeString('id-ID',{hour:'2-digit',minute:'2-digit'});
}

// ================================================================
// STATE
// ================================================================
let allRows=[], filteredRows=[], selected=new Set(), activeSheet='non_ppn';

// ================================================================
// SIDEBAR
// ================================================================
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('sidebarOverlay').style.display =
        document.getElementById('sidebar').classList.contains('open') ? 'block' : 'none';
}
function closeSidebar() {
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('sidebarOverlay').style.display = 'none';
}

// ================================================================
// FETCH
// ================================================================
async function loadData() {
    document.getElementById('tableBody').innerHTML =
        '<tr class="state-row"><td colspan="12"><span class="spinner"></span>Memuat data…</td></tr>';
    document.getElementById('tableFoot').innerHTML = '';
    document.getElementById('topbarMeta').textContent = 'Memuat data…';
    document.getElementById('btnExportPdf').disabled = true;
    try {
        const res  = await fetch(`gudang-read.php?sheet=${activeSheet}&_t=${Date.now()}`);
        const data = await res.json();
        if (data.error) throw new Error(data.error);
        allRows  = Array.isArray(data) ? data : [];
        selected = new Set();
        filterAndRender();
        document.getElementById('topbarMeta').textContent =
            `${allRows.length} item · ${activeSheet === 'non_ppn' ? 'Non-PPN' : 'PPN'}`;
    } catch(e) {
        document.getElementById('tableBody').innerHTML =
            `<tr class="state-row"><td colspan="12">⚠ Gagal memuat: ${e.message}</td></tr>`;
        document.getElementById('topbarMeta').textContent = 'Gagal memuat';
    }
}

function switchSheet(sheet) {
    if (activeSheet === sheet) return;
    activeSheet = sheet;
    document.querySelectorAll('.sheet-tab').forEach(b => b.classList.toggle('active', b.dataset.sheet === sheet));
    loadData();
}

// ================================================================
// FILTER
// ================================================================
function onSearch() { filterAndRender(); }
function filterAndRender() {
    const q = document.getElementById('searchInput').value.trim().toLowerCase();
    filteredRows = !q ? [...allRows] : allRows.filter(r =>
        [r['nama_produk'],r['kode_barang'],r['kode_internal'],r['merk'],r['nama_mobil'],r['supplier'],r['nama_lain']]
        .some(v => v && String(v).toLowerCase().includes(q))
    );
    renderTable();
    updateSummary();
}

// ================================================================
// TABLE
// ================================================================
function rowKey(r) { return r['kode_internal'] || r['kode_barang'] || Math.random().toString(36).slice(2); }

function renderTable() {
    const tbody = document.getElementById('tableBody');
    if (!filteredRows.length) {
        tbody.innerHTML = '<tr class="state-row"><td colspan="12">Tidak ada barang ditemukan.</td></tr>';
        document.getElementById('tableFoot').innerHTML = '';
        document.getElementById('checkAll').checked = false;
        document.getElementById('checkAll').indeterminate = false;
        return;
    }
    let html = '';
    filteredRows.forEach(r => {
        const key  = rowKey(r);
        const stok = parseNum(r['stok']||r[7]);
        const beli = getHargaBeli(r);
        const jual = parseNum(r['harga_jual']||r[10]);
        const tM   = beli*stok, tJ = jual*stok, profit = tJ-tM;
        const isSel = selected.has(key);
        const sCls  = stok===0?'zero':stok<=5?'low':'ok';
        const pCls  = profit>=0?'pos':'neg';
        const mm    = [(r['merk']||''),(r['nama_mobil']||'')].filter(Boolean).join(' / ') || '—';
        html += `
        <tr data-key="${escHtml(key)}">
            <td class="center"><input type="checkbox" ${isSel?'checked':''} onchange="toggleRow(this,'${escHtml(key)}')" /></td>
            <td class="muted" style="font-family:monospace;font-size:11px">${escHtml(r['kode_barang']||'—')}</td>
            <td class="name-cell" title="${escHtml(r['nama_produk']||'')}">${escHtml(r['nama_produk']||'—')}</td>
            <td class="muted">${escHtml(mm)}</td>
            <td class="muted">${escHtml(r['supplier']||'—')}</td>
            <td class="right"><span class="stok-num ${sCls}">${stok.toLocaleString('id-ID')}</span></td>
            <td class="right price">${beli?rp(beli):'—'}</td>
            <td class="right price blue">${jual?rp(jual):'—'}</td>
            <td class="right price orange">${beli&&stok?rp(tM):'—'}</td>
            <td class="right price green">${jual&&stok?rp(tJ):'—'}</td>
            <td class="right">${(beli&&jual&&stok)?`<span class="profit-pill ${pCls}">${profit>=0?'+':''}${rp(profit)}</span>`:'—'}</td>
            <td class="center">${(beli&&jual)?`<span class="profit-pill ${pCls}">${pct(jual,beli)}</span>`:'—'}</td>
        </tr>`;
    });
    tbody.innerHTML = html;
    renderFooter(filteredRows);
    updateCheckAllState();
    updateSummary();
}

function renderFooter(rows) {
    let tM=0,tJ=0,tS=0;
    rows.forEach(r=>{
        const s=parseNum(r['stok']||r[7]),b=getHargaBeli(r),j=parseNum(r['harga_jual']||r[10]);
        tM+=b*s;tJ+=j*s;tS+=s;
    });
    const tP=tJ-tM, pc=tP>=0?'green':'red';
    document.getElementById('tableFoot').innerHTML=`
    <tr class="tfoot-row">
        <td colspan="5" style="color:var(--text-muted);font-size:11px">${rows.length} jenis · ${tS.toLocaleString('id-ID')} unit (tampil)</td>
        <td class="right">${tS.toLocaleString('id-ID')}</td>
        <td></td><td></td>
        <td class="right price orange">${rp(tM)}</td>
        <td class="right price green">${rp(tJ)}</td>
        <td class="right price ${pc}">${tP>=0?'+':''}${rp(tP)}</td>
        <td class="center" style="color:var(--text-muted);font-size:11px">${tM?pct(tJ,tM):'—'}</td>
    </tr>`;
}

// ================================================================
// SELECTION
// ================================================================
function toggleRow(cb, key) {
    if(cb.checked) selected.add(key); else selected.delete(key);
    updateCheckAllState(); updateSummary();
}
function toggleAll(mCb) {
    filteredRows.forEach(r=>{const k=rowKey(r);if(mCb.checked)selected.add(k);else selected.delete(k);});
    document.querySelectorAll('#tableBody input[type="checkbox"]').forEach(cb=>cb.checked=mCb.checked);
    updateSummary();
}
function selectAll() {
    filteredRows.forEach(r=>selected.add(rowKey(r)));
    document.querySelectorAll('#tableBody input[type="checkbox"]').forEach(cb=>cb.checked=true);
    document.getElementById('checkAll').checked=true;
    document.getElementById('checkAll').indeterminate=false;
    updateSummary();
}
function clearAll() {
    selected.clear();
    document.querySelectorAll('#tableBody input[type="checkbox"]').forEach(cb=>cb.checked=false);
    document.getElementById('checkAll').checked=false;
    document.getElementById('checkAll').indeterminate=false;
    updateSummary();
}
function updateCheckAllState() {
    const total=filteredRows.length, cnt=filteredRows.filter(r=>selected.has(rowKey(r))).length;
    const cb=document.getElementById('checkAll');
    cb.checked=cnt===total&&total>0; cb.indeterminate=cnt>0&&cnt<total;
}

// ================================================================
// SUMMARY
// ================================================================
function updateSummary() {
    const sel   = allRows.filter(r=>selected.has(rowKey(r)));
    const isAll = selected.size>0 && selected.size===allRows.length;
    let tM=0,tJ=0,tS=0;
    sel.forEach(r=>{
        const s=parseNum(r['stok']||r[7]),b=getHargaBeli(r),j=parseNum(r['harga_jual']||r[10]);
        tM+=b*s;tJ+=j*s;tS+=s;
    });
    const tP=tJ-tM, roc=tM?(((tJ-tM)/tM)*100).toFixed(1)+'%':'—', n=sel.length;

    document.getElementById('valTotalModal').textContent  = rp(tM);
    document.getElementById('subTotalModal').textContent  = `${n} jenis · ${tS.toLocaleString('id-ID')} unit`;
    document.getElementById('valTotalJual').textContent   = rp(tJ);
    document.getElementById('valTotalProfit').textContent = (tP>=0?'+':'') + rp(tP);
    document.getElementById('valTotalProfit').style.color = tP>=0?'#10b981':'#ef4444';
    document.getElementById('valTotalItem').textContent   = n.toLocaleString('id-ID');
    document.getElementById('subTotalItem').textContent   = `dari ${allRows.length} jenis barang`;
    document.getElementById('brkModal').textContent   = rp(tM);
    document.getElementById('brkJual').textContent    = rp(tJ);
    document.getElementById('brkProfit').textContent  = (tP>=0?'+':'') + rp(tP);
    document.getElementById('brkProfit').className    = 'breakdown-value '+(tP>=0?'green':'red');
    document.getElementById('brkRoc').textContent     = roc;
    document.getElementById('brkRoc').className       = 'breakdown-value '+(tP>=0?'green':'red');
    document.getElementById('breakdownSub').textContent = n>0?`${n} jenis · ${tS.toLocaleString('id-ID')} unit dipilih`:'Pilih barang untuk melihat kalkulasi';

    // Scope badge
    const sc = document.getElementById('selScope');
    if(n===0){ sc.className='sel-scope none'; sc.textContent='Belum dipilih'; }
    else if(isAll){ sc.className='sel-scope all'; sc.textContent='✓ Semua Stok'; }
    else { sc.className='sel-scope partial'; sc.textContent=`${n} item dipilih`; }

    document.getElementById('btnExportPdf').disabled = n===0;
}

// ================================================================
// PDF EXPORT
// ================================================================
function exportPdf() {
    const { jsPDF } = window.jspdf;
    const sel    = allRows.filter(r=>selected.has(rowKey(r)));
    if(!sel.length) return;

    const isAll  = sel.length === allRows.length && allRows.length > 0;
    const sheet  = activeSheet==='non_ppn'?'Non-PPN':'PPN';
    const tgl    = nowStr();

    // ── Hitung total ──
    let tM=0,tJ=0,tS=0;
    sel.forEach(r=>{
        const s=parseNum(r['stok']||r[7]),b=getHargaBeli(r),j=parseNum(r['harga_jual']||r[10]);
        tM+=b*s;tJ+=j*s;tS+=s;
    });
    const tP=tJ-tM, roc=tM?(((tJ-tM)/tM)*100).toFixed(1)+'%':'-';

    const doc = new jsPDF({orientation:'landscape',unit:'mm',format:'a4'});
    const W=doc.internal.pageSize.getWidth(), H=doc.internal.pageSize.getHeight();

    // ── Header ──
    doc.setFillColor(30,64,175); doc.rect(0,0,W,26,'F');
    doc.setTextColor(255,255,255);
    doc.setFontSize(15); doc.setFont('helvetica','bold');
    doc.text('Sri Rejeki Motor',14,11);
    doc.setFontSize(8.5); doc.setFont('helvetica','normal');
    doc.text('Laporan Kalkulasi Biaya & Estimasi Pendapatan',14,18);
    doc.text('Sheet: '+sheet,14,23.5);
    doc.setFontSize(8); doc.setFont('helvetica','normal');
    doc.text('Dicetak: '+tgl, W-14,18,{align:'right'});
    doc.setFont('helvetica','bold');

    // ── Scope label di header kanan ──
    const scopeLabel = isAll ? 'Cakupan: Semua Stok ('+allRows.length+' jenis barang)' : 'Cakupan: '+sel.length+' Produk Dipilih';
    doc.text(scopeLabel, W-14, 23.5, {align:'right'});

    // ── Summary boxes 2 baris x 3 kolom ──
    // Label baris-1 dan baris-2 dipisah agar muat di box
    const estLine2  = isAll ? '(SEMUA STOK)'   : '(PRODUK DIPILIH)';
    const profLine2 = isAll ? '(SEMUA STOK)'   : '(PRODUK DIPILIH)';
    const boxes = [
        {l1:'TOTAL MODAL',         l2:'',          val:rpPdf(tM),                c:[59,130,246],  note:false},
        {l1:'EST. PENDAPATAN',      l2:estLine2,    val:rpPdf(tJ),                c:[16,185,129],  note:true},
        {l1:'EST. KEUNTUNGAN',      l2:profLine2,   val:(tP>=0?'+':'')+rpPdf(tP), c:tP>=0?[16,185,129]:[220,38,38], note:true},
        {l1:'RETURN ON COST',       l2:'',          val:roc,                      c:tP>=0?[16,185,129]:[220,38,38], note:false},
        {l1:'TOTAL UNIT STOK',      l2:'',          val:tS.toLocaleString('id-ID')+' unit', c:[139,92,246], note:false},
        {l1:'JENIS BARANG',         l2:'',          val:sel.length+' item',       c:[245,158,11],  note:false},
    ];
    const bW=(W-28-10)/3, bH=17, bY=30;
    boxes.forEach((b,i)=>{
        const col=i%3, row=Math.floor(i/3);
        const x=14+col*(bW+5), y=bY+row*(bH+3);
        doc.setFillColor(...b.c); doc.roundedRect(x,y,bW,bH,1.5,1.5,'F');
        doc.setTextColor(255,255,255);
        // Baris label 1
        doc.setFont('helvetica','bold'); doc.setFontSize(6);
        doc.text(b.l1, x+3, y+5);
        // Baris label 2 (jika ada)
        if(b.l2){
            doc.setFont('helvetica','normal'); doc.setFontSize(5.8);
            doc.text(b.l2, x+3, y+9);
        }
        // Nilai
        doc.setFont('helvetica','bold'); doc.setFontSize(9);
        doc.text(b.val, x+3, b.l2 ? y+13.5 : y+12.5);
        // Indikator estimasi bila terjual habis
        if(b.note){
            doc.setFont('helvetica','italic'); doc.setFontSize(5.2);
            doc.setTextColor(220,255,220);
            doc.text('* estimasi bila terjual habis', x+3, y+16.2);
            doc.setTextColor(255,255,255);
        }
    });

    // ── Keterangan scope produk ──
    const noteY=bY+2*(bH+3)+5;

    // ── Indikator cakupan ──
    const noteH = 11;
    if(isAll){
        // Background hijau
        doc.setFillColor(220,252,231);
        doc.roundedRect(14, noteY-3.5, W-28, noteH, 1.5, 1.5, 'F');
        doc.setDrawColor(134,239,172); doc.setLineWidth(0.4);
        doc.roundedRect(14, noteY-3.5, W-28, noteH, 1.5, 1.5, 'S');
        // Strip hijau tua di kiri
        doc.setFillColor(21,128,61);
        doc.roundedRect(14, noteY-3.5, 3.5, noteH, 1, 1, 'F');
        // Teks judul
        doc.setTextColor(21,128,61); doc.setFont('helvetica','bold'); doc.setFontSize(7.5);
        doc.text('SEMUA STOK GUDANG', 20, noteY+1.8);
        // Teks keterangan
        doc.setFont('helvetica','normal'); doc.setFontSize(6.5);
        doc.text('Laporan mencakup seluruh '+allRows.length+' jenis barang (sheet '+sheet+'). Estimasi berlaku untuk keseluruhan stok gudang.', 20, noteY+6.5);
    } else {
        // Background kuning
        doc.setFillColor(255,247,205);
        doc.roundedRect(14, noteY-3.5, W-28, noteH, 1.5, 1.5, 'F');
        doc.setDrawColor(234,179,8); doc.setLineWidth(0.4);
        doc.roundedRect(14, noteY-3.5, W-28, noteH, 1.5, 1.5, 'S');
        // Strip kuning tua di kiri
        doc.setFillColor(161,98,7);
        doc.roundedRect(14, noteY-3.5, 3.5, noteH, 1, 1, 'F');
        // Teks judul
        doc.setTextColor(120,53,15); doc.setFont('helvetica','bold'); doc.setFontSize(7.5);
        doc.text('SEBAGIAN PRODUK  —  '+sel.length+' dari '+allRows.length+' jenis dipilih', 20, noteY+1.8);
        // Teks keterangan
        doc.setFont('helvetica','normal'); doc.setFontSize(6.5);
        doc.text('Laporan hanya mencakup produk yang dipilih secara manual dan tidak mencerminkan keseluruhan stok gudang.', 20, noteY+6.5);
    }

    // ── Tabel ──
    const tableRows = sel.map(r=>{
        const stok=parseNum(r['stok']||r[7]);
        const beli=getHargaBeli(r);
        const jual=parseNum(r['harga_jual']||r[10]);
        const tMr=beli*stok, tJr=jual*stok, pr=tJr-tMr;
        return [
            r['kode_barang']||'-',
            r['nama_produk']||'-',
            stok.toLocaleString('id-ID'),
            rpPdf(tMr),
            rpPdf(tJr),
            (pr>=0?'+':'')+rpPdf(pr),
            pctPdf(jual,beli),
        ];
    });

    // Baris total
    tableRows.push([
        {content:'TOTAL',colSpan:2,styles:{fontStyle:'bold',fillColor:[241,245,249],textColor:[15,23,42]}},
        {content:tS.toLocaleString('id-ID'),styles:{fontStyle:'bold',fillColor:[241,245,249],textColor:[15,23,42],halign:'right'}},
        {content:rpPdf(tM),styles:{fontStyle:'bold',fillColor:[241,245,249],textColor:[194,120,0],halign:'right'}},
        {content:rpPdf(tJ),styles:{fontStyle:'bold',fillColor:[241,245,249],textColor:[4,120,87],halign:'right'}},
        {content:(tP>=0?'+':'')+rpPdf(tP),styles:{fontStyle:'bold',fillColor:[241,245,249],textColor:tP>=0?[4,120,87]:[185,28,28],halign:'right'}},
        {content:roc,styles:{fontStyle:'bold',fillColor:[241,245,249],textColor:tP>=0?[4,120,87]:[185,28,28],halign:'right'}},
    ]);

    doc.autoTable({
        startY: noteY+noteH+2,
        head: [['Kode Barang','Nama Produk','Stok','Total Modal','Est. Pendapatan','Est. Keuntungan','Margin']],
        body: tableRows,
        theme: 'grid',
        margin: {left:14, right:14},
        styles: {font:'helvetica', fontSize:8, cellPadding:2.8, textColor:[30,41,59], lineColor:[226,232,240], lineWidth:0.3},
        headStyles: {fillColor:[30,64,175], textColor:255, fontStyle:'bold', fontSize:7.5, halign:'center'},
        columnStyles: {
            0:{cellWidth:26,halign:'left'},
            1:{cellWidth:'auto',halign:'left'},
            2:{cellWidth:17,halign:'right'},
            3:{cellWidth:34,halign:'right'},
            4:{cellWidth:34,halign:'right'},
            5:{cellWidth:34,halign:'right'},
            6:{cellWidth:17,halign:'right'},
        },
        alternateRowStyles: {fillColor:[248,250,252]},
        didDrawPage: data=>{
            doc.setFontSize(7); doc.setTextColor(148,163,184); doc.setFont('helvetica','normal');
            doc.text('Sri Rejeki Motor — Kalkulasi Biaya',14,H-6);
            doc.text('Halaman '+data.pageNumber,W-14,H-6,{align:'right'});
            doc.text(tgl,W/2,H-6,{align:'center'});
        },
    });

    doc.save('kalkulasi-biaya_'+activeSheet+'_'+new Date().toISOString().slice(0,10)+'.pdf');
}

// ================================================================
// INIT
// ================================================================
loadData();
</script>
</body>
</html>