<?php
$v_css = file_exists(__DIR__ . '/style.css') ? substr(md5_file(__DIR__ . '/style.css'), 0, 8) : 'x';
$v_js  = file_exists(__DIR__ . '/app.js')    ? substr(md5_file(__DIR__ . '/app.js'),    0, 8) : 'x';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Kode Internal — CV Sri Rejeki Motor</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;500;600&family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=<?= $v_css ?>">
    <style>

    /* ── Page header ── */
    .gen-header {
        display: flex; align-items: flex-start; justify-content: space-between;
        gap: 16px; margin-bottom: 20px; flex-wrap: wrap;
    }
    .gen-title {
        font-family: 'Cormorant Garamond', serif;
        font-size: 30px; font-weight: 600; color: var(--text-primary); line-height: 1.1;
    }
    .gen-subtitle { font-size: 14px; color: var(--text-muted); margin-top: 4px; }

    /* ── Gudang toggle ── */
    .gudang-toggle-wrap {
        display: flex; align-items: center; gap: 10px;
        background: var(--bg-base); border: 1px solid var(--border);
        border-radius: 14px; padding: 6px 10px 6px 14px;
    }
    .gudang-toggle-label { font-size: 13px; font-weight: 500; color: var(--text-muted); white-space: nowrap; }
    .toggle-pills { display: flex; background: var(--border); border-radius: 9px; padding: 3px; gap: 3px; }
    .toggle-pill {
        padding: 5px 14px; border-radius: 7px; font-size: 13px; font-weight: 600;
        cursor: pointer; border: none; background: transparent; color: var(--text-muted);
        transition: all 0.18s; font-family: 'Outfit', sans-serif;
    }
    .toggle-pill.active { background: #fff; color: var(--blue); box-shadow: 0 1px 6px rgba(0,0,0,0.12); }
    .toggle-pill[data-sheet="ppn"].active { color: #7c3aed; }

    /* ── Action bar ── */
    .action-bar {
        display: flex; align-items: center; justify-content: space-between;
        gap: 12px; background: #fff; border: 1px solid var(--border);
        border-radius: 14px; padding: 12px 18px; margin-bottom: 16px;
        box-shadow: var(--shadow-sm); flex-wrap: wrap;
    }
    .action-bar-left  { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
    .action-bar-right { display: flex; align-items: center; gap: 10px; }

    .stat-chip {
        display: flex; align-items: center; gap: 7px;
        background: var(--bg-base); border: 1px solid var(--border);
        border-radius: 9px; padding: 6px 14px; font-size: 13px; color: var(--text-secondary);
    }
    .stat-chip strong { color: var(--text-primary); font-weight: 700; }
    .stat-chip.warn strong { color: #d97706; }
    .stat-chip.ok   strong { color: #10b981; }

    /* Buttons */
    .btn-action {
        display: inline-flex; align-items: center; gap: 7px;
        padding: 9px 18px; border-radius: 10px; font-size: 13px; font-weight: 600;
        font-family: 'Outfit', sans-serif; cursor: pointer; transition: all 0.15s;
        border: 1px solid var(--border); background: #fff; color: var(--text-secondary);
        white-space: nowrap;
    }
    .btn-action:hover { border-color: var(--blue); color: var(--blue); background: #eff6ff; }
    .btn-action.primary {
        background: var(--blue); color: #fff; border-color: var(--blue);
        box-shadow: 0 4px 14px rgba(59,130,246,0.3);
    }
    .btn-action.primary:hover { background: #2563eb; transform: translateY(-1px); }
    .btn-action.success {
        background: #10b981; color: #fff; border-color: #10b981;
        box-shadow: 0 4px 14px rgba(16,185,129,0.3);
    }
    .btn-action.success:hover { background: #059669; }
    .btn-action:disabled { opacity: 0.45; cursor: not-allowed; transform: none !important; }

    /* ── Logic badge ── */
    .logic-badge {
        display: inline-flex; align-items: center; gap: 8px;
        background: #f0fdf4; border: 1px solid #bbf7d0;
        border-radius: 10px; padding: 8px 14px; font-size: 12px;
        color: #166534; font-weight: 500; flex-wrap: wrap;
    }
    .logic-badge code {
        background: #dcfce7; border-radius: 5px; padding: 2px 7px;
        font-family: 'Courier New', monospace; font-weight: 700; font-size: 12px;
        color: #15803d; letter-spacing: 0.05em;
    }

    /* ── Table ── */
    .gen-table-wrap {
        background: #fff; border: 1px solid var(--border);
        border-radius: 16px; overflow: hidden; box-shadow: var(--shadow-sm);
    }
    .gen-table { width: 100%; border-collapse: collapse; font-size: 13px; }
    .gen-table thead { background: var(--bg-base); }
    .gen-table th {
        padding: 11px 14px; text-align: left; font-size: 11px; font-weight: 700;
        color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.07em;
        border-bottom: 1px solid var(--border);
    }
    .gen-table td { padding: 9px 14px; border-bottom: 1px solid #f1f3f7; vertical-align: middle; }
    .gen-table tbody tr:last-child td { border-bottom: none; }
    .gen-table tbody tr:hover { background: #fafbff; }
    /* Pagination */
    .pagination-bar {
        display: flex; align-items: center; justify-content: space-between;
        padding: 10px 16px; border-top: 1px solid var(--border);
        background: #fafbfc; font-size: 12px; color: var(--text-muted); flex-wrap: wrap; gap: 6px;
    }
    .pg-info { font-weight: 500; }
    .pagination-controls { display: flex; gap: 4px; align-items: center; flex-wrap: wrap; }
    .pg-btn {
        min-width: 30px; height: 28px; padding: 0 8px;
        border: 1px solid var(--border); border-radius: 6px; background: #fff;
        cursor: pointer; font-size: 12px; font-weight: 600; color: var(--text-secondary);
        display: flex; align-items: center; justify-content: center; transition: all 0.12s;
    }
    .pg-btn:hover:not(:disabled) { background: var(--blue); color: #fff; border-color: var(--blue); }
    .pg-btn:disabled { opacity: 0.38; cursor: default; }
    .pg-btn.active { background: var(--blue); color: #fff; border-color: var(--blue); }
    .pg-sep { padding: 0 2px; color: var(--text-muted); font-size: 11px; }
    .pg-size-select {
        height: 28px; padding: 0 6px; border: 1px solid var(--border); border-radius: 6px;
        font-size: 12px; background: #fff; cursor: pointer; color: var(--text-secondary);
    }

    /* Kode input inline */
    .kode-input {
        font-family: 'Courier New', monospace; font-size: 12px; font-weight: 700;
        color: var(--text-primary); background: #f8fafc; border: 1px solid #e2e8f0;
        border-radius: 7px; padding: 5px 10px; width: 150px; outline: none;
        transition: all 0.15s; letter-spacing: 0.06em;
    }
    .kode-input:focus { border-color: var(--blue); background: #fff; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
    .kode-input.changed { border-color: #f59e0b; background: #fffbeb; color: #92400e; }
    .kode-input.generated { border-color: #10b981; background: #f0fdf4; }

    /* Badge: status kode */
    .kode-badge {
        display: inline-flex; align-items: center; gap: 4px;
        font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 5px;
        text-transform: uppercase; letter-spacing: 0.05em;
    }
    .kode-badge.new    { background: #dcfce7; color: #166534; }
    .kode-badge.edited { background: #fef3c7; color: #92400e; }
    .kode-badge.empty  { background: #fee2e2; color: #991b1b; }
    .kode-badge.ada    { background: #e0f2fe; color: #0369a1; }
    .btn-action.purple { background: var(--purple, #7c3aed); color: #fff; border-color: var(--purple, #7c3aed); }
    .btn-action.purple:hover:not(:disabled) { background: #6d28d9; transform: translateY(-1px); }
    input.row-chk { width: 15px; height: 15px; cursor: pointer; accent-color: var(--blue); }

    /* Duplikat warning */
    .kode-input.duplicate { border-color: #ef4444; background: #fef2f2; color: #991b1b; }
    .dup-warn { font-size: 10px; color: #ef4444; font-weight: 600; margin-top: 2px; display: none; }
    .dup-warn.show { display: block; }

    /* ── Empty / Loading ── */
    .state-row td { text-align: center; padding: 40px; color: var(--text-muted); font-size: 14px; }

    /* ── Spinner ── */
    .spin { animation: spin 0.7s linear infinite; }
    @keyframes spin { to { transform: rotate(360deg); } }

    /* ── Notification ── */
    .notification {
        display: none; align-items: center; gap: 10px; padding: 12px 16px;
        border-radius: 10px; font-size: 14px; font-weight: 500;
        position: fixed; top: 20px; right: 20px; z-index: 2000;
        max-width: 400px; box-shadow: 0 8px 24px rgba(0,0,0,0.2);
    }
    .notification.success { display: flex; background: rgba(52,211,153,0.12); border: 1px solid rgba(52,211,153,0.3); color: #10b981; }
    .notification.error   { display: flex; background: rgba(248,113,113,0.12); border: 1px solid rgba(248,113,113,0.3); color: #ef4444; }
    .notification.info    { display: flex; background: rgba(59,130,246,0.1);  border: 1px solid rgba(59,130,246,0.25); color: #2563eb; }

    /* ── Responsive ── */
    @media (max-width: 768px) {
        /* Page header gap */
        .page-header { margin-bottom: 6px; }

        /* Gen header: stack title + toggle */
        .gen-header {
            flex-direction: column;
            align-items: stretch;
            gap: 10px;
        }
        .gen-title { font-size: 22px; }
        .gen-subtitle { font-size: 13px; }

        /* Toggle fills width */
        .gudang-toggle-wrap { width: 100%; justify-content: space-between; }
        .toggle-pills { flex: 1; }
        .toggle-pill  { flex: 1; text-align: center; }

        /* Logic badge: wrap text */
        .logic-badge { font-size: 11px; word-break: break-word; line-height: 1.6; }
        .logic-badge code { display: inline-block; margin-bottom: 2px; }

        /* Action bar: vertical */
        .action-bar {
            flex-direction: column;
            align-items: stretch;
            gap: 10px;
        }
        .action-bar-left  { flex-wrap: wrap; gap: 6px; }
        /* Buttons in 2×2 grid on mobile */
        .action-bar-right {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }
        .btn-action { font-size: 12px; padding: 8px 10px; justify-content: center; }

        /* Kode input narrower */
        .kode-input { width: 110px; }
    }

    /* ── Tablet (769-1024px) ── */
    @media (min-width: 769px) and (max-width: 1024px) {
        .action-bar-right { flex-wrap: wrap; gap: 8px; }
        .btn-action { font-size: 12px; padding: 8px 12px; }
    }
    </style>
</head>
<body>

<div class="notification" id="notif"></div>

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
            <a href="barang-kosong.php" class="nav-item" onclick="closeMobileSidebar()">
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
            <a href="generate-kode.php" class="nav-item active" onclick="closeMobileSidebar()">
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
        <div class="nav-section-label">Laporan</div>
        <a href="laporan-keuangan.php" class="nav-item" onclick="closeMobileSidebar()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/>
                <line x1="6" y1="20" x2="6" y2="14"/>
            </svg>
            <span>Laporan Keuangan</span>
        </a>
        <a href="kalkulasi-biaya.php" class="nav-item" onclick="closeMobileSidebar()">
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

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <main class="main-content">
        <header class="page-header">
            <div class="header-left">
                <button class="hamburger" id="hamburgerBtn">
                    <span></span><span></span><span></span>
                </button>
            </div>
        </header>

        <!-- Page Header -->
        <div class="gen-header">
            <div>
                <h1 class="gen-title">Generate Kode Internal</h1>
                <p class="gen-subtitle" id="pageSubtitle">Pilih gudang lalu klik Generate Semua</p>
            </div>
            <div class="gudang-toggle-wrap">
                <span class="gudang-toggle-label">Gudang</span>
                <div class="toggle-pills">
                    <button class="toggle-pill active" data-sheet="non_ppn">Non-PPN</button>
                    <button class="toggle-pill" data-sheet="ppn">PPN</button>
                </div>
            </div>
        </div>

        <!-- Logic explanation -->
        <div class="logic-badge" style="margin-bottom:16px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            Format: <code>D[HARGA_ENCODED]-[MERK+NNN]-[SUPPLIER]</code>
            &nbsp;·&nbsp; Peta digit: 1=K 2=E 3=N 4=A 5=R 6=I 7=B 8=O 9=X 0=Y
            &nbsp;·&nbsp; Contoh: <code>DKRYYY-N001-AHM</code> · <code>DBRYY-Y002-FDR</code> · tanpa supplier: <code>DKRYYY-N003</code>
        </div>

        <!-- Action bar -->
        <div class="action-bar">
            <div class="action-bar-left">
                <div class="stat-chip" id="statTotal">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/></svg>
                    <strong id="totalCount">—</strong> produk
                </div>
                <div class="stat-chip warn" id="statKosong" style="display:none;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <strong id="kosongCount">0</strong> kode kosong
                </div>
                <div class="stat-chip" id="statDup" style="display:none;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14" style="color:#ef4444"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                    <strong id="dupCount" style="color:#ef4444;">0</strong> duplikat
                </div>
            </div>
            <div class="action-bar-right">
                <button class="btn-action" id="btnLoad">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14">
                        <polyline points="23 4 23 10 17 10"/>
                        <path d="M20.49 15a9 9 0 11-2.12-9.36L23 10"/>
                    </svg>
                    Muat Data
                </button>
                <button class="btn-action primary" id="btnGenAll" disabled>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14">
                        <polyline points="23 4 23 10 17 10"/>
                        <polyline points="1 20 1 14 7 14"/>
                        <path d="M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15"/>
                    </svg>
                    Generate Otomatis
                </button>
                <button class="btn-action purple" id="btnGenSelected" disabled>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14">
                        <polyline points="9 11 12 14 22 4"/>
                        <path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/>
                    </svg>
                    Generate Terpilih (<span id="selectedCount">0</span>)
                </button>
                <button class="btn-action success" id="btnDownload" disabled>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14">
                        <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/>
                        <polyline points="7 10 12 15 17 10"/>
                        <line x1="12" y1="15" x2="12" y2="3"/>
                    </svg>
                    Download CSV
                </button>
            </div>
        </div>

        <!-- Table -->
        <div class="gen-table-wrap">
            <div style="overflow-x:auto;">
                <table class="gen-table">
                    <thead>
                        <tr>
                            <th style="width:32px"><input type="checkbox" id="chkAll" title="Pilih semua" onchange="toggleSelectAll(this)"></th>
                            <th style="width:36px">#</th>
                            <th>Kode Barang</th>
                            <th>Merk</th>
                            <th>Nama Produk</th>
                            <th>Kode Internal (Edit bebas)</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        <tr class="state-row">
                            <td colspan="6">Klik <strong>Muat Data</strong> untuk memulai</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="pagination-bar" id="paginationBar">
                <span class="pg-info" id="pgInfo">—</span>
                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                    <span style="font-size:12px;color:var(--text-muted)">Per halaman:
                        <select class="pg-size-select" id="pgSizeSelect">
                            <option value="25">25</option>
                            <option value="50" selected>50</option>
                            <option value="100">100</option>
                        </select>
                    </span>
                    <div class="pagination-controls" id="pgControls"></div>
                </div>
            </div>
        </div>

    </main>
</div>

<script src="app.js?v=<?= $v_js ?>"></script>
<script>
// ================================================================
// CONFIG
// ================================================================
const SPREADSHEET_ID = '1zQtkYOkwWjPPll_D0u7pJNTYebWGTMl3ilPwnEIWVKQ';
const GUDANG_CONFIG  = {
    non_ppn: { label: 'Non-PPN', apiUrl: `https://opensheet.elk.sh/${SPREADSHEET_ID}/non_ppn` },
    ppn:     { label: 'PPN',     apiUrl: `https://opensheet.elk.sh/${SPREADSHEET_ID}/ppn`     }
};

let activeSheet = 'non_ppn';
let allRows     = [];      // raw from API
let generated   = [];      // { origRow, kodeInternal, status:'new'|'edited' }

// ================================================================
// SIDEBAR
// ================================================================
document.getElementById('hamburgerBtn').addEventListener('click', function () {
    document.getElementById('sidebar').classList.toggle('active');
    document.getElementById('sidebarOverlay').classList.toggle('active');
    this.classList.toggle('active');
});
document.getElementById('sidebarOverlay').addEventListener('click', closeMobileSidebar);
function closeMobileSidebar() {
    document.getElementById('sidebar').classList.remove('active');
    document.getElementById('sidebarOverlay').classList.remove('active');
    document.getElementById('hamburgerBtn').classList.remove('active');
}

// ================================================================
// TOGGLE GUDANG
// ================================================================
document.querySelectorAll('.toggle-pill').forEach(btn => {
    btn.addEventListener('click', function () {
        if (activeSheet === this.dataset.sheet) return;
        activeSheet = this.dataset.sheet;
        document.querySelectorAll('.toggle-pill').forEach(b =>
            b.classList.toggle('active', b.dataset.sheet === activeSheet));
        allRows = []; generated = [];
        resetUI();
    });
});

function resetUI() {
    document.getElementById('tableBody').innerHTML =
        '<tr class="state-row"><td colspan="6">Klik <strong>Muat Data</strong> untuk memulai</td></tr>';
    document.getElementById('btnGenAll').disabled      = true;
    document.getElementById('btnGenSelected').disabled = true;
    document.getElementById('btnDownload').disabled    = true;
    document.getElementById('selectedCount').textContent = '0';
    document.getElementById('totalCount').textContent = '—';
    document.getElementById('statKosong').style.display = 'none';
    document.getElementById('statDup').style.display    = 'none';
    document.getElementById('pageSubtitle').textContent = 'Pilih gudang lalu klik Muat Data';
}

// ================================================================
// KODE GENERATOR LOGIC (sama persis dengan index.php)
// ================================================================
const STOP_WORDS = new Set(['dan','the','untuk','dengan','of','and','new','plus','type','tipe',
    'series','pro','max','ultra','mini','super','dan','untuk']);

function slugPart(str, maxLen = 3) {
    if (!str) return '';
    const words = str.toUpperCase()
        .replace(/[^A-Z0-9\s]/g, '')
        .split(/\s+/)
        .filter(w => w.length > 0 && !STOP_WORDS.has(w.toLowerCase()));

    if (words.length === 0)
        return str.toUpperCase().replace(/[^A-Z0-9]/g,'').substring(0, maxLen);
    if (words.length === 1)
        return words[0].substring(0, maxLen);

    const initials = words.map(w => w[0]).join('');
    if (initials.length >= maxLen) return initials.substring(0, maxLen);

    return (words[0].substring(0, maxLen - initials.length + 1) + initials.substring(1))
        .substring(0, maxLen);
}

/**
 * Ekstrak kode dari nama_produk untuk bagian ke-3:
 * - Jika ada token yang mengandung angka (KF40, ST100, 55) → pakai token itu
 * - Jika tidak ada angka → ambil 3 huruf pertama dari kata PALING BELAKANG
 * Lalu tambahkan inisial supplier (3 huruf, tanpa dash) jika supplier ada.
 */
// Peta digit → huruf kode harga
const DIGIT_MAP = {'1':'K','2':'E','3':'N','4':'A','5':'R','6':'I','7':'B','8':'O','9':'X','0':'Y'};

function encodeHarga(harga) {
    return String(harga).replace(/\D/g, '').split('').map(d => DIGIT_MAP[d] || d).join('');
}

function supplierInisial(supplier) {
    if (!supplier || !supplier.trim()) return '';
    const s = supplier.trim();
    // Jika sudah 2-3 karakter (sudah inisial) → pakai langsung
    if (s.replace(/\s+/g, '').length <= 3) return s.replace(/\s+/g, '').toUpperCase();
    // Lebih dari 3 karakter → huruf depan setiap kata
    return s.toUpperCase().split(/\s+/).map(w => w[0]).join('');
}

/**
 * Format: D[HARGA_ENCODED]-[MERK_INISIAL+NNN]-[SUPPLIER]
 * Kode unik: 1 huruf inisial merk + 3 digit urut per run (001, 002, 003...)
 * Contoh: harga 15000, merk NGK (ke-3), supplier AHM → DKRYYY-N003-AHM
 * Jika merk kosong → huruf X
 */
/**
 * Baca semua kode_internal yang SUDAH ADA di allRows,
 * kembalikan map {merkHuruf: maxCounter} sebagai titik awal.
 */
function buildExistingCounters() {
    const counters = {};
    // Pola: -[1 HURUF KAPITAL][3 DIGIT] diikuti tanda - atau akhir string
    const pattern = /-([A-Z])(\d{3})(?:-|$)/;
    (allRows || []).forEach(row => {
        const ki = (row['kode_internal'] || row[1] || '').trim();
        if (!ki) return;
        const m = ki.match(pattern);
        if (m) {
            const huruf = m[1];
            const n     = parseInt(m[2], 10);
            if (!counters[huruf] || n > counters[huruf]) counters[huruf] = n;
        }
    });
    return counters;
}

function generateAll(rows) {
    // Seed counter dari kode yang SUDAH ADA — hindari duplikat
    const counters = buildExistingCounters();

    return rows.map(row => {
        const harga    = row['harga_beli'] || row[8] || '0';
        const merk     = (row['merk']      || row[2] || '').trim();
        const supplier = row['supplier']   || row[7] || '';

        const merkHuruf = merk ? merk.toUpperCase().replace(/[^A-Z]/g, '')[0] || 'X' : 'X';
        counters[merkHuruf] = (counters[merkHuruf] || 0) + 1;
        const num      = String(counters[merkHuruf]).padStart(3, '0');
        const kodeUnik = `${merkHuruf}${num}`;

        const encoded = encodeHarga(harga);
        const sup     = supplierInisial(supplier);

        let result = `D${encoded}-${kodeUnik}`;
        if (sup) result += `-${sup}`;
        return result;
    });
}

// ================================================================
// LOAD DATA
// ================================================================
document.getElementById('btnLoad').addEventListener('click', loadData);

document.getElementById('pgSizeSelect').addEventListener('change', function () {
    gkPageSize = parseInt(this.value);
    gkSetPage(0);
});

async function loadData() {
    const btn = document.getElementById('btnLoad');
    const tbody = document.getElementById('tableBody');

    btn.disabled = true;
    btn.innerHTML = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14" class="spin"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 11-2.12-9.36L23 10"/></svg> Memuat...`;
    tbody.innerHTML = '<tr class="state-row"><td colspan="6">Memuat data dari spreadsheet...</td></tr>';
    document.getElementById('pageSubtitle').textContent = 'Memuat data...';

    try {
        const res  = await fetch(GUDANG_CONFIG[activeSheet].apiUrl);
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const data = await res.json();
        allRows = Array.isArray(data) ? data : [];

        document.getElementById('totalCount').textContent = allRows.length;
        document.getElementById('pageSubtitle').textContent =
            `${allRows.length} produk dimuat · Gudang ${GUDANG_CONFIG[activeSheet].label}`;

        // Count empty kode_internal
        const emptyCount = allRows.filter(r => !(r['kode_internal'] || r[1] || '').trim()).length;
        const statKosong = document.getElementById('statKosong');
        document.getElementById('kosongCount').textContent = emptyCount;
        statKosong.style.display = emptyCount > 0 ? 'flex' : 'none';

        generated = allRows.map(r => ({
            origRow:      r,
            kodeInternal: (r['kode_internal'] || r[1] || '').trim(),
            status:       'original'
        }));

        renderTable();
        document.getElementById('btnGenAll').disabled = false;
        document.getElementById('btnGenSelected').disabled = false;
        updateSelectedCount();
        showNotif('info', `${allRows.length} produk berhasil dimuat.`);

    } catch (err) {
        tbody.innerHTML = `<tr class="state-row"><td colspan="6" style="color:var(--red)">⚠️ Gagal: ${err.message}</td></tr>`;
        showNotif('error', 'Gagal memuat: ' + err.message);
    } finally {
        btn.disabled = false;
        btn.innerHTML = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 11-2.12-9.36L23 10"/></svg> Muat Data`;
    }
}

// ================================================================
// GENERATE ALL
// ================================================================
document.getElementById('btnGenAll').addEventListener('click', () => {
    if (!allRows.length) return;

    // Hanya generate produk yang: (1) belum punya kode internal, DAN (2) punya harga_beli & harga_jual
    const eligible = generated.filter(item => {
        const sudahPunyaKode = (item.kodeInternal || '').trim() !== '';
        if (sudahPunyaKode) return false; // skip yang sudah punya kode
        const hargaBeli = parseInt((item.origRow['harga_beli'] || item.origRow[8] || '').toString().replace(/\D/g,'') || '0');
        const hargaJual = parseInt((item.origRow['harga_jual'] || item.origRow[9] || '').toString().replace(/\D/g,'') || '0');
        return hargaBeli > 0 && hargaJual > 0; // harus punya kedua harga
    });

    if (!eligible.length) {
        showNotif('info', 'Tidak ada produk yang memenuhi syarat: belum berkode + punya harga_beli & harga_jual.');
        return;
    }

    // Generate hanya untuk produk eligible, sisanya tetap
    const eligibleRows = eligible.map(item => item.origRow);
    const codes = generateAll(eligibleRows);
    let codeIdx = 0;
    generated = generated.map(item => {
        const sudahPunyaKode = (item.kodeInternal || '').trim() !== '';
        const hargaBeli = parseInt((item.origRow['harga_beli'] || item.origRow[8] || '').toString().replace(/\D/g,'') || '0');
        const hargaJual = parseInt((item.origRow['harga_jual'] || item.origRow[9] || '').toString().replace(/\D/g,'') || '0');
        const isEligible = !sudahPunyaKode && hargaBeli > 0 && hargaJual > 0;
        if (isEligible) {
            return { ...item, kodeInternal: codes[codeIdx++], status: 'new' };
        }
        return item; // tetap tidak berubah
    });

    renderTable();
    checkDuplicates();
    document.getElementById('btnDownload').disabled = false;
    showNotif('success', `✓ ${eligible.length} kode digenerate (${generated.length - eligible.length} dilewati: sudah berkode atau tanpa harga).`);
});

// GENERATE TERPILIH — generate baris yang dicentang, apapun kondisinya
document.getElementById('btnGenSelected').addEventListener('click', () => {
    const checked = getCheckedIndices();
    if (!checked.length) { showNotif('info', 'Belum ada baris yang dipilih.'); return; }

    const selectedRows = checked.map(i => generated[i].origRow);
    const codes = generateAll(selectedRows);
    checked.forEach((globalIdx, ci) => {
        generated[globalIdx] = { ...generated[globalIdx], kodeInternal: codes[ci], status: 'new' };
    });

    // Uncheck semua setelah generate
    generated.forEach(item => item._checked = false);
    renderTable();
    checkDuplicates();
    updateSelectedCount();
    document.getElementById('btnDownload').disabled = false;
    showNotif('success', `✓ ${checked.length} kode berhasil digenerate untuk baris terpilih.`);
});

function getCheckedIndices() {
    const indices = [];
    generated.forEach((item, i) => { if (item._checked) indices.push(i); });
    return indices;
}

function updateSelectedCount() {
    const n = getCheckedIndices().length;
    document.getElementById('selectedCount').textContent = n;
    document.getElementById('btnGenSelected').disabled = n === 0;
}

function toggleSelectAll(chk) {
    const start = gkCurrentPage * gkPageSize;
    const end   = Math.min(start + gkPageSize, generated.length);
    for (let i = start; i < end; i++) generated[i]._checked = chk.checked;
    renderCurrentPage();
    updateSelectedCount();
}

// ================================================================
// RENDER TABLE
// ================================================================
// ================================================================
// PAGINATION STATE (generate-kode)
// ================================================================
let gkCurrentPage = 0;
let gkPageSize    = 50;

function gkSetPage(page) {
    const total = Math.max(1, Math.ceil(generated.length / gkPageSize));
    gkCurrentPage = Math.min(Math.max(0, page), total - 1);
    renderCurrentPage();
    renderPagination();
}

function renderCurrentPage() {
    const tbody = document.getElementById('tableBody');
    if (!generated.length) {
        tbody.innerHTML = '<tr class="state-row"><td colspan="6">Belum ada data.</td></tr>';
        return;
    }
    const start = gkCurrentPage * gkPageSize;
    const slice = generated.slice(start, start + gkPageSize);

    tbody.innerHTML = slice.map((item, si) => {
        const i    = start + si; // global index
        const r    = item.origRow;
        const kb   = r['kode_barang']   || r[0] || '-';
        const merk = r['merk']          || r[2] || '-';
        const nama = r['nama_produk']   || r[3] || '-';
        const ki   = item.kodeInternal  || '';
        const hargaBeli = parseInt((r['harga_beli'] || r[8] || '').toString().replace(/\D/g,'') || '0');
        const hargaJual = parseInt((r['harga_jual'] || r[9] || '').toString().replace(/\D/g,'') || '0');
        const tanpaHarga = hargaBeli === 0 || hargaJual === 0;

        const cls = item.status === 'new'    ? 'generated'
                  : item.status === 'edited' ? 'changed'
                  : '';

        const badgeHtml = !ki
            ? (tanpaHarga
                ? `<span class="kode-badge empty" title="Harga beli/jual kosong — tidak digenerate otomatis">⚠ Tanpa Harga</span>`
                : `<span class="kode-badge empty">Kosong</span>`)
            : item.status === 'new'
                ? `<span class="kode-badge new">✓ Generate</span>`
                : item.status === 'edited'
                    ? `<span class="kode-badge edited">✎ Edited</span>`
                    : `<span class="kode-badge" style="background:#e0f2fe;color:#0369a1">Ada</span>`;

        const isChecked = item._checked ? 'checked' : '';
        const rowStyle  = tanpaHarga && !ki ? 'opacity:0.6' : '';

        return `<tr data-idx="${i}" style="${rowStyle}">
            <td><input type="checkbox" class="row-chk" data-idx="${i}" ${isChecked} onchange="onRowCheck(this)"></td>
            <td style="color:var(--text-muted);font-size:12px">${i + 1}</td>
            <td style="font-family:monospace;font-size:12px;font-weight:700">${kb}</td>
            <td style="font-size:13px;color:var(--text-secondary)">${merk}</td>
            <td style="font-size:13px;max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="${nama}">${nama}</td>
            <td>
                <input type="text" class="kode-input ${cls}" value="${ki}"
                    data-idx="${i}" data-original="${ki}"
                    placeholder="Belum ada kode"
                    spellcheck="false">
                <div class="dup-warn" id="dup_${i}">⚠ Duplikat!</div>
            </td>
            <td>${badgeHtml}</td>
        </tr>`;
    }).join('');

    // Input change listeners
    tbody.querySelectorAll('.kode-input').forEach(inp => {
        inp.addEventListener('input', function () {
            const idx = parseInt(this.dataset.idx);
            generated[idx].kodeInternal = this.value.trim();
            generated[idx].status = 'edited';
            this.className = 'kode-input changed';
            const tr = this.closest('tr');
            tr.querySelector('td:nth-last-child(1)').innerHTML = `<span class="kode-badge edited">✎ Edited</span>`;
            checkDuplicates();
            document.getElementById('btnDownload').disabled = false;
        });
    });
}

function onRowCheck(chk) {
    const idx = parseInt(chk.dataset.idx);
    generated[idx]._checked = chk.checked;
    // Sync header checkbox dengan kondisi halaman saat ini
    const start   = gkCurrentPage * gkPageSize;
    const end     = Math.min(start + gkPageSize, generated.length);
    const pageAll = generated.slice(start, end).every(item => item._checked);
    document.getElementById('chkAll').checked = pageAll;
    updateSelectedCount();
}

function renderPagination() {
    const total      = generated.length;
    const totalPages = Math.max(1, Math.ceil(total / gkPageSize));
    const start      = gkCurrentPage * gkPageSize + 1;
    const end        = Math.min((gkCurrentPage + 1) * gkPageSize, total);
    document.getElementById('pgInfo').textContent = total
        ? `${start}–${end} dari ${total} produk`
        : 'Belum ada data';

    const ctrl = document.getElementById('pgControls');
    let html = `<button class="pg-btn" onclick="gkSetPage(0)" ${gkCurrentPage===0?'disabled':''}>«</button>
                <button class="pg-btn" onclick="gkSetPage(${gkCurrentPage-1})" ${gkCurrentPage===0?'disabled':''}>‹</button>`;
    const range = 2;
    for (let p = 0; p < totalPages; p++) {
        if (p === 0 || p === totalPages-1 || Math.abs(p - gkCurrentPage) <= range) {
            html += `<button class="pg-btn${p===gkCurrentPage?' active':''}" onclick="gkSetPage(${p})">${p+1}</button>`;
        } else if (Math.abs(p - gkCurrentPage) === range + 1) {
            html += `<span class="pg-sep">…</span>`;
        }
    }
    html += `<button class="pg-btn" onclick="gkSetPage(${gkCurrentPage+1})" ${gkCurrentPage>=totalPages-1?'disabled':''}>›</button>
             <button class="pg-btn" onclick="gkSetPage(${totalPages-1})" ${gkCurrentPage>=totalPages-1?'disabled':''}>»</button>`;
    ctrl.innerHTML = html;
}

function renderTable() {
    gkCurrentPage = 0;
    renderCurrentPage();
    renderPagination();
}

// ================================================================
// DUPLICATE CHECK
// ================================================================
function checkDuplicates() {
    const counts = {};
    generated.forEach((item, i) => {
        const k = (item.kodeInternal || '').trim().toUpperCase();
        if (!k) return;
        counts[k] = counts[k] || [];
        counts[k].push(i);
    });

    let dupTotal = 0;
    generated.forEach((item, i) => {
        const k   = (item.kodeInternal || '').trim().toUpperCase();
        const inp = document.querySelector(`.kode-input[data-idx="${i}"]`);
        const warn = document.getElementById(`dup_${i}`);
        if (!inp || !warn) return;

        const isDup = k && counts[k] && counts[k].length > 1;
        if (isDup) {
            inp.classList.add('duplicate');
            inp.classList.remove('generated', 'changed');
            warn.classList.add('show');
            dupTotal++;
        } else {
            inp.classList.remove('duplicate');
            warn.classList.remove('show');
        }
    });

    const statDup = document.getElementById('statDup');
    document.getElementById('dupCount').textContent = dupTotal;
    statDup.style.display = dupTotal > 0 ? 'flex' : 'none';
}

// ================================================================
// DOWNLOAD CSV
// ================================================================
document.getElementById('btnDownload').addEventListener('click', downloadCSV);

function downloadCSV() {
    if (!generated.length) return;

    // Header sesuai urutan kolom spreadsheet:
    // A=kode_barang, B=kode_internal, C=merk, D=nama_produk, E=kategori,
    // F=lokasi_rak, G=stok, H=supplier, I=harga_beli, J=harga_jual
    const headers = ['kode_barang','kode_internal','merk','nama_produk','kategori',
                     'lokasi_rak','stok','supplier','harga_beli','harga_jual'];

    const escCsv = v => {
        const s = String(v ?? '');
        return s.includes(',') || s.includes('"') || s.includes('\n')
            ? `"${s.replace(/"/g,'""')}"`
            : s;
    };

    const rows = [headers.join(',')];

    generated.forEach(item => {
        const r = item.origRow;
        rows.push([
            r['kode_barang']   || r[0] || '',
            item.kodeInternal  || '',                    // ← hasil generate/edit
            r['merk']          || r[2] || '',
            r['nama_produk']   || r[3] || '',
            r['kategori']      || r[4] || '',
            r['lokasi_rak']    || r[5] || '',
            r['stok']          || r[6] || '',
            r['supplier']      || r[7] || '',
            r['harga_beli']    || r[8] || '',
            r['harga_jual']    || r[9] || '',
        ].map(escCsv).join(','));
    });

    const blob = new Blob(['\uFEFF' + rows.join('\r\n')], { type: 'text/csv;charset=utf-8;' });
    const url  = URL.createObjectURL(blob);
    const a    = document.createElement('a');
    const sheet = GUDANG_CONFIG[activeSheet].label.replace(/[^a-z0-9]/gi, '_');
    a.href     = url;
    a.download = `kode_internal_${sheet}_${new Date().toISOString().slice(0,10)}.csv`;
    a.click();
    URL.revokeObjectURL(url);

    showNotif('success', `✓ CSV berhasil diunduh — ${generated.length} baris.`);
}

// ================================================================
// NOTIFICATION
// ================================================================
let notifTimer;
function showNotif(type, msg) {
    const el = document.getElementById('notif');
    el.className = 'notification ' + type;
    el.textContent = msg;
    clearTimeout(notifTimer);
    notifTimer = setTimeout(() => {
        el.style.opacity = '0';
        el.style.transition = 'opacity 0.4s';
        setTimeout(() => { el.style.display = 'none'; el.style.opacity = '1'; el.style.transition = ''; }, 400);
    }, 4000);
}

// ================================================================
// INIT
// ================================================================
document.addEventListener('DOMContentLoaded', () => {
    // Auto-load on open
    loadData();
});
</script>
</body>
</html>