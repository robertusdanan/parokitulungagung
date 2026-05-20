<?php
$v_css = file_exists(__DIR__ . '/style.css') ? substr(md5_file(__DIR__ . '/style.css'), 0, 8) : '1';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Keuangan - Sri Rejeki Motor</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=<?= $v_css ?>">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
    <style>
        body { display: flex; min-height: 100vh; background: var(--bg-base); font-family: 'Outfit', sans-serif; }

        .page-wrap {
            flex: 1;
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
            padding: 0 28px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 50;
            flex-shrink: 0;
        }
        .topbar-left { display: flex; align-items: center; gap: 14px; }
        .hamburger { display: none; flex-direction: column; justify-content: center; gap: 5px; width: 36px; height: 36px; border: none; background: transparent; cursor: pointer; padding: 6px; border-radius: 8px; }
        .hamburger span { display: block; width: 18px; height: 2px; background: var(--text-primary); border-radius: 2px; }
        .topbar-title { font-size: 16px; font-weight: 700; color: var(--text-primary); }
        .topbar-meta  { font-size: 12px; color: var(--text-muted); }

        /* ── Content ── */
        .content { flex: 1; padding: 24px 28px 48px; }

        /* ── Period Bar ── */
        .period-bar {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 22px;
            flex-wrap: wrap;
        }
        .period-pills {
            display: flex;
            background: var(--bg-elevated);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 3px;
            gap: 2px;
        }
        .period-pill {
            padding: 6px 16px;
            border-radius: 7px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            background: transparent;
            color: var(--text-muted);
            transition: all .15s;
            font-family: 'Outfit', sans-serif;
            white-space: nowrap;
        }
        .period-pill.active {
            background: var(--bg-surface);
            color: var(--primary);
            box-shadow: 0 1px 4px rgba(0,0,0,.1);
        }
        .period-divider { width: 1px; height: 28px; background: var(--border); }
        .filter-select {
            height: 38px;
            background: var(--bg-surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            padding: 0 10px;
            font-family: 'Outfit', sans-serif;
            font-size: 13px;
            color: var(--text-primary);
            outline: none;
            cursor: pointer;
            min-width: 130px;
        }
        .filter-select:focus { border-color: var(--primary); }
        .period-info {
            margin-left: auto;
            font-size: 12px;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .status-dot { width: 6px; height: 6px; border-radius: 50%; background: var(--green); flex-shrink: 0; box-shadow: 0 0 6px var(--green); }

        /* ── KPI Cards ── */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
            margin-bottom: 22px;
        }
        .kpi-card {
            background: var(--bg-surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            padding: 18px 20px;
            position: relative;
            overflow: hidden;
            transition: box-shadow .15s;
        }
        .kpi-card:hover { box-shadow: 0 4px 20px rgba(0,0,0,.07); }
        .kpi-card::after {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
        }
        .kpi-card.c-blue::after   { background: linear-gradient(90deg,#2563eb,#60a5fa); }
        .kpi-card.c-green::after  { background: linear-gradient(90deg,#10b981,#34d399); }
        .kpi-card.c-red::after    { background: linear-gradient(90deg,#ef4444,#f87171); }
        .kpi-card.c-purple::after { background: linear-gradient(90deg,#8b5cf6,#a78bfa); }
        .kpi-card.c-orange::after { background: linear-gradient(90deg,#f59e0b,#fbbf24); }
        .kpi-card.c-teal::after   { background: linear-gradient(90deg,#0d9488,#2dd4bf); }

        .kpi-lbl {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .07em;
            color: var(--text-muted);
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 7px;
        }
        .kpi-lbl svg { width: 13px; height: 13px; }
        .kpi-val {
            font-size: 22px;
            font-weight: 700;
            color: var(--text-primary);
            line-height: 1.1;
            margin-bottom: 5px;
        }
        .kpi-sub {
            font-size: 11.5px;
            color: var(--text-muted);
        }
        .kpi-change {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            font-size: 11px;
            font-weight: 700;
            padding: 2px 7px;
            border-radius: 4px;
            margin-top: 4px;
        }
        .kpi-change.up   { background: rgba(16,185,129,.1); color: #10b981; }
        .kpi-change.down { background: rgba(239,68,68,.1);  color: #ef4444; }
        .kpi-change.neu  { background: var(--bg-elevated);  color: var(--text-muted); }

        /* ── Charts Row ── */
        .charts-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 16px;
        }
        .charts-row-3 {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 16px;
            margin-bottom: 22px;
        }
        .chart-card {
            background: var(--bg-surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            overflow: hidden;
        }
        .chart-head {
            padding: 14px 18px 12px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .chart-title {
            font-size: 13px;
            font-weight: 700;
            color: var(--text-primary);
        }
        .chart-sub {
            font-size: 11px;
            color: var(--text-muted);
            margin-top: 1px;
        }
        .chart-toggle {
            display: flex;
            gap: 4px;
        }
        .chart-toggle-btn {
            padding: 4px 10px;
            border-radius: 6px;
            border: 1px solid var(--border);
            background: transparent;
            font-size: 11.5px;
            font-weight: 600;
            cursor: pointer;
            color: var(--text-muted);
            font-family: 'Outfit', sans-serif;
            transition: all .12s;
        }
        .chart-toggle-btn.active {
            background: var(--primary-dim);
            border-color: rgba(37,99,235,.2);
            color: var(--primary);
        }
        .chart-body {
            padding: 16px 18px;
            position: relative;
        }
        .chart-body canvas { display: block; }

        /* ── Tables ── */
        .tables-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 16px;
        }
        .table-card {
            background: var(--bg-surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            overflow: hidden;
        }
        .table-head {
            padding: 14px 18px 12px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .table-title { font-size: 13px; font-weight: 700; color: var(--text-primary); }
        .table-sub   { font-size: 11px; color: var(--text-muted); margin-top: 1px; }
        .inner-table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .inner-table th {
            padding: 8px 14px;
            text-align: left;
            font-size: 10.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: var(--text-muted);
            border-bottom: 1px solid var(--border);
            background: var(--bg-elevated);
            white-space: nowrap;
        }
        .inner-table td {
            padding: 10px 14px;
            border-bottom: 1px solid rgba(225,229,235,.5);
            color: var(--text-secondary);
            vertical-align: middle;
        }
        .inner-table tr:last-child td { border-bottom: none; }
        .inner-table tr:hover td { background: var(--bg-elevated); }
        .num-r { text-align: right; }
        .fw-bold { font-weight: 700; color: var(--text-primary); }
        .rank-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 20px; height: 20px;
            border-radius: 5px;
            font-size: 11px;
            font-weight: 700;
            flex-shrink: 0;
        }
        .rank-1 { background: rgba(245,158,11,.15); color: #d97706; }
        .rank-2 { background: rgba(148,163,184,.15); color: #64748b; }
        .rank-3 { background: rgba(180,120,80,.15); color: #92400e; }
        .rank-n { background: var(--bg-elevated); color: var(--text-muted); }

        .bar-mini {
            height: 4px;
            background: var(--bg-elevated);
            border-radius: 2px;
            overflow: hidden;
            margin-top: 3px;
        }
        .bar-mini-fill {
            height: 100%;
            border-radius: 2px;
            background: linear-gradient(90deg, #2563eb, #60a5fa);
            transition: width .6s ease;
        }

        /* Metode bayar list */
        .metode-list { padding: 12px 0; }
        .metode-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 18px;
            transition: background .1s;
        }
        .metode-item:hover { background: var(--bg-elevated); }
        .metode-icon {
            width: 34px; height: 34px;
            border-radius: 9px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
            font-size: 14px;
        }
        .metode-info { flex: 1; min-width: 0; }
        .metode-name { font-size: 13px; font-weight: 600; color: var(--text-primary); }
        .metode-cnt  { font-size: 11px; color: var(--text-muted); }
        .metode-amt  { font-size: 13px; font-weight: 700; color: var(--text-primary); text-align: right; }
        .metode-pct  { font-size: 11px; color: var(--text-muted); text-align: right; }

        /* ── Loading overlay ── */
        .load-overlay {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 60px 20px;
            color: var(--text-muted);
            font-size: 13.5px;
        }
        .spinner { width: 18px; height: 18px; border: 2px solid var(--border); border-top-color: var(--primary); border-radius: 50%; animation: spin .8s linear infinite; flex-shrink: 0; }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* ── Skeleton ── */
        .skel {
            background: linear-gradient(90deg, var(--bg-elevated) 25%, #f0f2f5 50%, var(--bg-elevated) 75%);
            background-size: 200% 100%;
            animation: shimmer 1.4s infinite;
            border-radius: 6px;
        }
        @keyframes shimmer { to { background-position: -200% 0; } }

        /* ── Responsive ── */
        @media (max-width: 1100px) {
            .kpi-grid { grid-template-columns: repeat(2, 1fr); }
            .charts-row, .charts-row-3, .tables-row { grid-template-columns: 1fr; }
            #secondaryCharts { grid-template-columns: 1fr !important; }
        }
        @media (max-width: 768px) {
            .page-wrap { margin-left: 0; }
            .hamburger { display: flex; }
            .kpi-grid { grid-template-columns: 1fr 1fr; }
            .content { padding: 16px 14px 40px; }
            .topbar { padding: 0 14px; }
        }
        @media (max-width: 520px) {
            .kpi-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<!-- ── Sidebar (copied from riwayat-penjualan) ── -->
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
        <a href="laporan-keuangan.php" class="nav-item active" onclick="closeSidebar()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <line x1="18" y1="20" x2="18" y2="10"/>
                <line x1="12" y1="20" x2="12" y2="4"/>
                <line x1="6" y1="20" x2="6" y2="14"/>
                <path d="M2 20h20"/>
            </svg>
            <span>Laporan Keuangan</span>
        </a>
        <a href="kalkulasi-biaya.php" class="nav-item" onclick="closeSidebar()">
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

<!-- ── Main ── -->
<div class="page-wrap">

    <div class="topbar">
        <div class="topbar-left">
            <button class="hamburger" onclick="toggleSidebar()">
                <span></span><span></span><span></span>
            </button>
            <div>
                <div class="topbar-title">Laporan Keuangan</div>
                <div class="topbar-meta" id="topbarMeta">Memuat data...</div>
            </div>
        </div>
        <div style="display:flex;align-items:center;gap:8px">
            <div class="period-info">
                <div class="status-dot"></div>
                <span id="lastUpdate">—</span>
            </div>
        </div>
    </div>

    <div class="content">

        <!-- Period selector -->
        <div class="period-bar">
            <div class="period-pills">
                <button class="period-pill" data-p="7">7 Hari</button>
                <button class="period-pill active" data-p="30">Bulan Ini</button>
                <button class="period-pill" data-p="90">3 Bulan</button>
                <button class="period-pill" data-p="365">Tahun Ini</button>
                <button class="period-pill" data-p="0">Semua</button>
            </div>
            <div class="period-divider"></div>
            <select id="filterGudang" class="filter-select">
                <option value="">Semua Gudang</option>
                <option value="non_ppn">Non-PPN</option>
                <option value="ppn">PPN</option>
            </select>
            <div class="period-info" style="margin-left:auto">
                <span id="periodLabel" style="color:var(--text-secondary);font-weight:500">—</span>
            </div>
        </div>

        <!-- KPI Row: 4 kartu utama -->
        <div class="kpi-grid">
            <div class="kpi-card c-blue">
                <div class="kpi-lbl">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
                    Total Omzet
                </div>
                <div class="kpi-val" id="kpiOmzet">—</div>
                <div class="kpi-sub" id="kpiOmzetSub">dari transaksi terpilih</div>
            </div>
            <div class="kpi-card c-red">
                <div class="kpi-lbl">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/></svg>
                    Modal (HPP)
                </div>
                <div class="kpi-val" id="kpiModal">—</div>
                <div class="kpi-sub">harga beli × qty terjual</div>
            </div>
            <div class="kpi-card c-green">
                <div class="kpi-lbl">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
                    Laba Bersih
                </div>
                <div class="kpi-val" id="kpiLaba">—</div>
                <div class="kpi-sub" id="kpiLabaSub">omzet − modal</div>
            </div>
            <div class="kpi-card c-teal">
                <div class="kpi-lbl">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    Margin Rata-rata
                </div>
                <div class="kpi-val" id="kpiMargin">—</div>
                <div class="kpi-sub">laba ÷ omzet</div>
            </div>
        </div>

        <!-- Charts Row 1: Trend — full width, chart utama -->
        <div style="margin-bottom:16px">
            <div class="chart-card">
                <div class="chart-head">
                    <div>
                        <div class="chart-title">Tren Omzet & Laba</div>
                        <div class="chart-sub" id="trendSub">per hari</div>
                    </div>
                    <div class="chart-toggle">
                        <button class="chart-toggle-btn active" id="trendDay" onclick="setTrendMode('day')">Harian</button>
                        <button class="chart-toggle-btn" id="trendMonth" onclick="setTrendMode('month')">Bulanan</button>
                    </div>
                </div>
                <div class="chart-body" style="height:300px">
                    <canvas id="chartTrend"></canvas>
                </div>
            </div>
        </div>

        <!-- Charts Row 2: Produk + Metode + Kategori (pendukung) -->
        <div style="display:grid;grid-template-columns:1.6fr 1fr 1fr;gap:16px;margin-bottom:22px" id="secondaryCharts">
            <div class="chart-card">
                <div class="chart-head">
                    <div>
                        <div class="chart-title">Top 10 Produk Terlaris</div>
                        <div class="chart-sub">berdasarkan jumlah terjual</div>
                    </div>
                    <div class="chart-toggle">
                        <button class="chart-toggle-btn active" id="prodByQty" onclick="setProdMode('qty')">Qty</button>
                        <button class="chart-toggle-btn" id="prodByOmzet" onclick="setProdMode('omzet')">Omzet</button>
                        <button class="chart-toggle-btn" id="prodByLaba" onclick="setProdMode('laba')">Laba</button>
                    </div>
                </div>
                <div class="chart-body" style="height:280px">
                    <canvas id="chartProduk"></canvas>
                </div>
            </div>
            <div class="chart-card">
                <div class="chart-head">
                    <div>
                        <div class="chart-title">Metode Bayar</div>
                        <div class="chart-sub">distribusi omzet</div>
                    </div>
                </div>
                <div class="chart-body" style="height:280px; display:flex; align-items:center; justify-content:center;">
                    <canvas id="chartMetode" style="max-height:250px;max-width:250px"></canvas>
                </div>
            </div>
            <div class="chart-card">
                <div class="chart-head">
                    <div>
                        <div class="chart-title">Kategori</div>
                        <div class="chart-sub">omzet per kategori</div>
                    </div>
                </div>
                <div class="chart-body" style="height:280px; display:flex; align-items:center; justify-content:center;">
                    <canvas id="chartKategori" style="max-height:250px;max-width:250px"></canvas>
                </div>
            </div>
        </div>

        <!-- Tables Row -->
        <div class="tables-row">
            <div class="table-card">
                <div class="table-head">
                    <div>
                        <div class="table-title">Top Produk Terlaris</div>
                        <div class="table-sub">qty terjual · omzet · laba</div>
                    </div>
                </div>
                <table class="inner-table">
                    <thead>
                        <tr>
                            <th style="width:32px">#</th>
                            <th>Produk</th>
                            <th class="num-r">Qty</th>
                            <th class="num-r">Omzet</th>
                            <th class="num-r">Laba</th>
                        </tr>
                    </thead>
                    <tbody id="tblProduk">
                        <tr><td colspan="5" class="load-overlay" style="padding:24px"><div class="spinner"></div></td></tr>
                    </tbody>
                </table>
            </div>
            <div class="table-card">
                <div class="table-head">
                    <div>
                        <div class="table-title">Metode Pembayaran</div>
                        <div class="table-sub">distribusi per metode</div>
                    </div>
                </div>
                <div class="metode-list" id="metodeList">
                    <div class="load-overlay" style="padding:24px"><div class="spinner"></div></div>
                </div>
            </div>
        </div>

    </div><!-- /.content -->
</div><!-- /.page-wrap -->

<script>
const SPREADSHEET_ID = '1zQtkYOkwWjPPll_D0u7pJNTYebWGTMl3ilPwnEIWVKQ';
const API_JUAL = `https://opensheet.elk.sh/${SPREADSHEET_ID}/penjualan`;
const API_BARANG = `https://opensheet.elk.sh/${SPREADSHEET_ID}/non_ppn`;

// ── State ──────────────────────────────────────────────────────────────
let rawRows    = [];
let filtRows   = [];  // rows after period + gudang filter
let activePeriod = 30; // days (0 = all)
let activeGudang = '';
let trendMode    = 'day';
let prodMode     = 'qty';

// Chart instances
let chartTrend   = null;
let chartMetode  = null;
let chartProduk  = null;
let chartKategori= null;

// ── Formatting ─────────────────────────────────────────────────────────
const IDR = v => 'Rp ' + Math.round(v).toLocaleString('id-ID');
const IDRK = v => {
    if (Math.abs(v) >= 1e9) return 'Rp ' + (v/1e9).toFixed(1).replace('.',',') + 'M';
    if (Math.abs(v) >= 1e6) return 'Rp ' + (v/1e6).toFixed(1).replace('.',',') + 'jt';
    if (Math.abs(v) >= 1e3) return 'Rp ' + (v/1e3).toFixed(0) + 'rb';
    return 'Rp ' + v;
};

function parseDate(s) {
    if (!s) return null;
    // dd/mm/yyyy, dd-mm-yyyy, yyyy-mm-dd
    let m;
    if ((m = s.match(/^(\d{2})[\/\-](\d{2})[\/\-](\d{4})$/))) return new Date(+m[3], +m[2]-1, +m[1]);
    if ((m = s.match(/^(\d{4})[\/\-](\d{2})[\/\-](\d{2})$/))) return new Date(+m[1], +m[2]-1, +m[3]);
    if ((m = s.match(/^(\d{2})[\/\-](\d{2})[\/\-](\d{2})$/))) return new Date(2000+ +m[3], +m[2]-1, +m[1]);
    return new Date(s);
}

const BULAN = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];

// ── Harga beli map (dari sheet non_ppn) ───────────────────────────────
let hargaBeliMap = {}; // kode_barang → harga_beli efektif (harga_final jika ada, else harga_beli)

// ── Load data ──────────────────────────────────────────────────────────
async function load() {
    try {
        // Fetch paralel: sheet penjualan + non_ppn
        const [respJual, respBarang] = await Promise.all([
            fetch(API_JUAL),
            fetch(API_BARANG)
        ]);
        rawRows = await respJual.json();
        const barangRows = await respBarang.json();

        // Bangun map harga beli dari sheet non_ppn
        // Kolom J (index 9)  = harga_beli
        // Kolom P (index 15) = harga_final (jika ada diskon pembelian)
        hargaBeliMap = {};
        barangRows.forEach(b => {
            // Support named key atau index numerik
            const kode      = (b.kode_barang || b[3]  || '').toString().trim();
            const hargaBeli = parseInt(b.harga_beli  || b[9]  || 0);
            const hargaFinal= parseInt(b.harga_final || b[15] || 0);
            if (!kode) return;
            // harga_final dipakai jika > 0 (artinya produk ini dapat diskon pembelian)
            hargaBeliMap[kode] = hargaFinal > 0 ? hargaFinal : hargaBeli;
        });

        document.getElementById('lastUpdate').textContent = 'Diperbarui baru saja';
        document.getElementById('topbarMeta').textContent = rawRows.length + ' baris data dimuat';


        applyFilters();
    } catch(e) {
        document.getElementById('topbarMeta').textContent = 'Gagal memuat: ' + e.message;
    }
}

// ── Filter & compute ───────────────────────────────────────────────────
function applyFilters() {
    const now = new Date();
    const gudang = document.getElementById('filterGudang').value;

    // Compute cutoff date
    let cutoff = null;
    if (activePeriod === 30) {
        // Bulan ini (bukan 30 hari)
        cutoff = new Date(now.getFullYear(), now.getMonth(), 1);
    } else if (activePeriod > 0) {
        cutoff = new Date(now - activePeriod * 86400000);
    }

    filtRows = rawRows.filter(row => {
        const waktu = row.waktu_transaksi || row[19] || '';
        const tglStr = waktu ? waktu.split(' ')[0] : (row.tanggal || row[1] || '');
        if (cutoff) {
            const d = parseDate(tglStr);
            if (!d || d < cutoff) return false;
        }
        if (gudang) {
            // Normalisasi: hilangkan spasi, strip, tanda hubung → lowercase
            const rawG = (row.jenis_gudang || row[2] || '').toLowerCase().replace(/[\s_\-]/g, '');
            const selG = gudang.toLowerCase().replace(/[\s_\-]/g, '');
            if (rawG !== selG) return false;
        }
        return !!(row.no_nota || row[0]);
    });

    // Period label
    let plabel = 'Semua waktu';
    if (activePeriod === 7) plabel = '7 hari terakhir';
    else if (activePeriod === 30) plabel = 'Bulan ' + BULAN[now.getMonth()] + ' ' + now.getFullYear();
    else if (activePeriod === 90) plabel = '3 bulan terakhir';
    else if (activePeriod === 365) plabel = 'Tahun ' + now.getFullYear();
    document.getElementById('periodLabel').textContent = plabel;

    renderAll();
}

// ── Compute aggregates ─────────────────────────────────────────────────
function computeAgg(rows) {
    const notaSet = new Set();
    let omzet = 0, modal = 0, qty = 0;
    const metodeMap = {}, prodMap = {}, katMap = {};
    const dateMap = {}; // key→{omzet,laba}

    rows.forEach(row => {
        const nota  = row.no_nota     || row[0]  || '';
        const waktu = row.waktu_transaksi || row[19] || '';
        const tglStr= waktu ? waktu.split(' ')[0] : (row.tanggal || row[1] || '');
        const gudang= row.jenis_gudang || row[2]  || '';
        const kode  = (row.kode_barang || row[3] || '').toString().trim();
        const nama  = (row.nama_produk|| row[5]  || '').trim();
        const kat   = (row.kategori   || row[8]  || 'Lainnya').trim() || 'Lainnya';
        const q     = parseInt(row.qty || row[9] || 0);
        const hJual = parseInt(row.harga_jual  || row[10] || 0);
        const sub   = parseInt(row.subtotal    || row[11] || 0);
        const total = parseInt(row.total       || row[12] || 0);
        const metode= (row.metode || row[13] || 'Lainnya').trim() || 'Lainnya';

        if (!nota) return;

        // Omzet = subtotal aktual (sudah termasuk diskon ke customer)
        const itemOmzet = sub > 0 ? sub : (hJual * q);

        // Modal (HPP) dari sheet non_ppn:
        // harga_final (kol P) dipakai jika ada (produk dapat diskon beli), else harga_beli (kol J)
        const hargaBeli = hargaBeliMap[kode] || 0;
        const itemModal = hargaBeli * q;

        const itemLaba  = itemOmzet - itemModal;

        omzet += itemOmzet;
        modal += itemModal;
        qty   += q;

        // Metode (per nota agar tidak double-count)
        if (!notaSet.has(nota)) {
            notaSet.add(nota);
            const m = metode || 'Lainnya';
            if (!metodeMap[m]) metodeMap[m] = { cnt: 0, amt: 0 };
            metodeMap[m].cnt++;
            metodeMap[m].amt += total || itemOmzet;
        }

        // Produk
        if (nama) {
            if (!prodMap[nama]) prodMap[nama] = { qty: 0, omzet: 0, laba: 0 };
            prodMap[nama].qty   += q;
            prodMap[nama].omzet += itemOmzet;
            prodMap[nama].laba  += itemLaba;
        }

        // Kategori
        if (!katMap[kat]) katMap[kat] = { omzet: 0, laba: 0 };
        katMap[kat].omzet += itemOmzet;
        katMap[kat].laba  += itemLaba;

        // Date trend
        const d = parseDate(tglStr);
        if (d) {
            const dk = trendMode === 'month'
                ? `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}`
                : `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
            if (!dateMap[dk]) dateMap[dk] = { omzet: 0, laba: 0 };
            dateMap[dk].omzet += itemOmzet;
            dateMap[dk].laba  += itemLaba;
        }
    });

    return { omzet, modal, qty, notaCnt: notaSet.size, metodeMap, prodMap, katMap, dateMap };
}

// ── Render all ─────────────────────────────────────────────────────────
function renderAll() {
    const agg = computeAgg(filtRows);
    renderKPI(agg);
    renderTrend(agg);
    renderMetode(agg);
    renderProduk(agg);
    renderKategori(agg);
    renderTables(agg);
}

// ── KPI ────────────────────────────────────────────────────────────────
function renderKPI(agg) {
    const laba   = agg.omzet - agg.modal;
    const margin = agg.omzet > 0 ? (laba / agg.omzet * 100) : 0;

    document.getElementById('kpiOmzet').textContent  = IDRK(agg.omzet);
    document.getElementById('kpiOmzetSub').textContent = agg.qty + ' item terjual';
    document.getElementById('kpiModal').textContent  = IDRK(agg.modal);
    document.getElementById('kpiLaba').textContent   = IDRK(laba);
    document.getElementById('kpiLabaSub').textContent= laba >= 0 ? 'keuntungan' : 'kerugian';
    document.getElementById('kpiMargin').textContent = margin.toFixed(1).replace('.',',') + '%';
}

// ── Trend Chart ────────────────────────────────────────────────────────
function renderTrend(agg) {
    const keys = Object.keys(agg.dateMap).sort();
    const labels = keys.map(k => {
        if (trendMode === 'month') {
            const [y, m] = k.split('-');
            return BULAN[parseInt(m)-1] + ' ' + y;
        } else {
            const [y, m, d] = k.split('-');
            return d + ' ' + BULAN[parseInt(m)-1];
        }
    });
    const dataOmzet = keys.map(k => agg.dateMap[k].omzet);
    const dataLaba  = keys.map(k => agg.dateMap[k].laba);

    document.getElementById('trendSub').textContent = trendMode === 'day' ? 'per hari' : 'per bulan';

    const ctx = document.getElementById('chartTrend').getContext('2d');
    if (chartTrend) chartTrend.destroy();

    chartTrend = new Chart(ctx, {
        type: 'line',
        data: {
            labels,
            datasets: [
                {
                    label: 'Omzet',
                    data: dataOmzet,
                    borderColor: '#2563eb',
                    backgroundColor: 'rgba(37,99,235,0.08)',
                    borderWidth: 2,
                    pointRadius: keys.length < 15 ? 4 : 2,
                    pointHoverRadius: 6,
                    fill: true,
                    tension: 0.35,
                },
                {
                    label: 'Laba',
                    data: dataLaba,
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16,185,129,0.06)',
                    borderWidth: 2,
                    pointRadius: keys.length < 15 ? 4 : 2,
                    pointHoverRadius: 6,
                    fill: true,
                    tension: 0.35,
                },
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { position: 'top', labels: { font: { family: 'Outfit', size: 12 }, boxWidth: 12, padding: 16 } },
                tooltip: {
                    callbacks: {
                        label: ctx => ' ' + ctx.dataset.label + ': ' + IDRK(ctx.raw)
                    }
                }
            },
            scales: {
                x: { grid: { color: 'rgba(0,0,0,.04)' }, ticks: { font: { family: 'Outfit', size: 11 }, maxRotation: 45 } },
                y: { grid: { color: 'rgba(0,0,0,.04)' }, ticks: { font: { family: 'Outfit', size: 11 }, callback: v => IDRK(v) } }
            }
        }
    });
}

// ── Metode Chart ───────────────────────────────────────────────────────
const METODE_COLORS = {
    'Tunai':    '#2563eb',
    'Transfer': '#10b981',
    'QRIS':     '#8b5cf6',
    'Kredit':   '#f59e0b',
    'Lainnya':  '#94a3b8',
};

function renderMetode(agg) {
    const entries = Object.entries(agg.metodeMap).sort((a,b) => b[1].amt - a[1].amt);
    const labels = entries.map(e => e[0]);
    const data   = entries.map(e => e[1].amt);
    const colors = labels.map(l => METODE_COLORS[l] || '#64748b');

    const ctx = document.getElementById('chartMetode').getContext('2d');
    if (chartMetode) chartMetode.destroy();

    chartMetode = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels,
            datasets: [{ data, backgroundColor: colors, borderWidth: 2, borderColor: '#fff', hoverOffset: 6 }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { font: { family: 'Outfit', size: 12 }, boxWidth: 12, padding: 10 } },
                tooltip: { callbacks: { label: ctx => ' ' + ctx.label + ': ' + IDRK(ctx.raw) } }
            }
        }
    });
}

// ── Produk Chart ───────────────────────────────────────────────────────
function renderProduk(agg) {
    const sorted = Object.entries(agg.prodMap)
        .sort((a,b) => b[1][prodMode] - a[1][prodMode])
        .slice(0, 10);

    const labels = sorted.map(e => e[0].length > 22 ? e[0].slice(0,20)+'…' : e[0]);
    const data   = sorted.map(e => e[1][prodMode]);
    const isRp   = prodMode !== 'qty';

    const ctx = document.getElementById('chartProduk').getContext('2d');
    if (chartProduk) chartProduk.destroy();

    chartProduk = new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: prodMode === 'qty' ? 'Qty Terjual' : prodMode === 'omzet' ? 'Omzet' : 'Laba',
                data,
                backgroundColor: prodMode === 'laba'
                    ? data.map(v => v >= 0 ? 'rgba(16,185,129,0.75)' : 'rgba(239,68,68,0.75)')
                    : 'rgba(37,99,235,0.75)',
                borderRadius: 6,
                borderSkipped: false,
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: ctx => ' ' + (isRp ? IDRK(ctx.raw) : ctx.raw + ' pcs') } }
            },
            scales: {
                x: { grid: { color: 'rgba(0,0,0,.04)' }, ticks: { font: { family:'Outfit', size:11 }, callback: v => isRp ? IDRK(v) : v } },
                y: { grid: { display: false }, ticks: { font: { family:'Outfit', size:11 } } }
            }
        }
    });
}

// ── Kategori Chart ─────────────────────────────────────────────────────
const KAT_COLORS = ['#2563eb','#10b981','#8b5cf6','#f59e0b','#ef4444','#0d9488','#6366f1','#ec4899','#84cc16','#f97316'];

function renderKategori(agg) {
    const sorted = Object.entries(agg.katMap).sort((a,b) => b[1].omzet - a[1].omzet).slice(0, 10);
    const labels = sorted.map(e => e[0]);
    const data   = sorted.map(e => e[1].omzet);
    const colors = KAT_COLORS.slice(0, sorted.length);

    const ctx = document.getElementById('chartKategori').getContext('2d');
    if (chartKategori) chartKategori.destroy();

    chartKategori = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels,
            datasets: [{ data, backgroundColor: colors, borderWidth: 2, borderColor: '#fff', hoverOffset: 6 }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { font: { family:'Outfit', size:11 }, boxWidth: 10, padding: 8 } },
                tooltip: { callbacks: { label: ctx => ' ' + ctx.label + ': ' + IDRK(ctx.raw) } }
            }
        }
    });
}

// ── Tables ─────────────────────────────────────────────────────────────
function renderTables(agg) {
    // Produk table
    const prodSorted = Object.entries(agg.prodMap)
        .sort((a,b) => b[1].qty - a[1].qty)
        .slice(0, 15);

    const maxOmzet = Math.max(...prodSorted.map(e => e[1].omzet), 1);

    const tblBody = document.getElementById('tblProduk');
    if (!prodSorted.length) {
        tblBody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:24px;color:var(--text-muted)">Tidak ada data</td></tr>';
    } else {
        tblBody.innerHTML = prodSorted.map(([nama, d], i) => {
            const rankClass = i === 0 ? 'rank-1' : i === 1 ? 'rank-2' : i === 2 ? 'rank-3' : 'rank-n';
            const pct = (d.omzet / maxOmzet * 100).toFixed(0);
            const labaColor = d.laba >= 0 ? '#10b981' : '#ef4444';
            return `<tr>
                <td><span class="rank-badge ${rankClass}">${i+1}</span></td>
                <td>
                    <div class="fw-bold" style="font-size:12.5px;max-width:180px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${nama}</div>
                    <div class="bar-mini" style="width:100px"><div class="bar-mini-fill" style="width:${pct}%"></div></div>
                </td>
                <td class="num-r" style="font-weight:600">${d.qty.toLocaleString('id-ID')}</td>
                <td class="num-r" style="font-weight:600;font-size:12px">${IDRK(d.omzet)}</td>
                <td class="num-r" style="font-weight:700;font-size:12px;color:${labaColor}">${IDRK(d.laba)}</td>
            </tr>`;
        }).join('');
    }

    // Metode list
    const metodeEntries = Object.entries(agg.metodeMap).sort((a,b) => b[1].amt - a[1].amt);
    const totalAmt = metodeEntries.reduce((s, e) => s + e[1].amt, 0);
    const totalCnt = metodeEntries.reduce((s, e) => s + e[1].cnt, 0);

    const METODE_ICON = {
        'Tunai':    { bg: 'rgba(37,99,235,.1)',   c: '#2563eb', icon: '💵' },
        'Transfer': { bg: 'rgba(16,185,129,.1)',  c: '#10b981', icon: '🏦' },
        'QRIS':     { bg: 'rgba(139,92,246,.1)',  c: '#8b5cf6', icon: '📱' },
        'Kredit':   { bg: 'rgba(245,158,11,.1)',  c: '#f59e0b', icon: '💳' },
        'Lainnya':  { bg: 'rgba(148,163,184,.1)', c: '#94a3b8', icon: '💰' },
    };

    const ml = document.getElementById('metodeList');
    if (!metodeEntries.length) {
        ml.innerHTML = '<div style="padding:24px;text-align:center;color:var(--text-muted)">Tidak ada data</div>';
    } else {
        ml.innerHTML = metodeEntries.map(([m, d]) => {
            const ico = METODE_ICON[m] || METODE_ICON['Lainnya'];
            const pct = totalAmt > 0 ? (d.amt / totalAmt * 100).toFixed(1) : 0;
            return `<div class="metode-item">
                <div class="metode-icon" style="background:${ico.bg}">${ico.icon}</div>
                <div class="metode-info">
                    <div class="metode-name">${m}</div>
                    <div class="metode-cnt">${d.cnt} transaksi · ${pct}%</div>
                    <div class="bar-mini" style="width:120px;margin-top:4px">
                        <div class="bar-mini-fill" style="width:${pct}%;background:${ico.c}"></div>
                    </div>
                </div>
                <div>
                    <div class="metode-amt">${IDRK(d.amt)}</div>
                    <div class="metode-pct">${pct}%</div>
                </div>
            </div>`;
        }).join('') +
        `<div style="padding:10px 18px;border-top:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;font-size:12px;color:var(--text-muted)">
            <span><b style="color:var(--text-primary)">${totalCnt}</b> total transaksi</span>
            <span style="font-weight:700;color:var(--text-primary)">${IDRK(totalAmt)}</span>
        </div>`;
    }
}

// ── Event handlers ─────────────────────────────────────────────────────
function setTrendMode(m) {
    trendMode = m;
    document.getElementById('trendDay').classList.toggle('active', m === 'day');
    document.getElementById('trendMonth').classList.toggle('active', m === 'month');
    const agg = computeAgg(filtRows);
    renderTrend(agg);
}

function setProdMode(m) {
    prodMode = m;
    document.getElementById('prodByQty').classList.toggle('active', m === 'qty');
    document.getElementById('prodByOmzet').classList.toggle('active', m === 'omzet');
    document.getElementById('prodByLaba').classList.toggle('active', m === 'laba');
    const agg = computeAgg(filtRows);
    renderProduk(agg);
    // Juga update tabel
    renderTables(agg);
}

// Period pills
document.querySelectorAll('.period-pill').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.period-pill').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        activePeriod = parseInt(btn.dataset.p);
        applyFilters();
    });
});

document.getElementById('filterGudang').addEventListener('change', () => {
    activeGudang = document.getElementById('filterGudang').value;
    applyFilters();
});

// ── Sidebar ────────────────────────────────────────────────────────────
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('sidebarOverlay').classList.toggle('active');
}
function closeSidebar() {
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('sidebarOverlay').classList.remove('active');
}

// ── Init ───────────────────────────────────────────────────────────────
load();
</script>
</body>
</html>