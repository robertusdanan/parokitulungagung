<?php
$v_css = file_exists(__DIR__ . '/style.css') ? substr(md5_file(__DIR__ . '/style.css'), 0, 8) : '1';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sri Rejeki Motor</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=<?= $v_css ?>">
    <style>
        /* ── Dashboard Layout ─────────────────────────────────────── */
        .dashboard-content {
            flex: 1;
            margin-left: var(--sidebar-w);
            padding: 32px 36px;
            overflow-y: auto;
            background: var(--bg-base);
        }
        .dash-header { margin-bottom: 28px; }
        .dash-title { font-size: 24px; font-weight: 700; color: var(--text-primary); letter-spacing: -0.3px; }
        .dash-sub { font-size: 14px; color: var(--text-muted); margin-top: 4px; }

        /* ── Stat Cards ──────────────────────────────────────────── */
        .stats-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: 16px; margin-bottom: 28px; }
        .stat-card {
            background: var(--bg-surface); border: 1px solid var(--border);
            border-radius: var(--radius-md); padding: 20px 22px;
            position: relative; overflow: hidden; transition: box-shadow 0.2s;
        }
        .stat-card:hover { box-shadow: var(--shadow-md); }
        .stat-card::after {
            content: ''; position: absolute; top: 0; left: 0; right: 0;
            height: 3px; border-radius: var(--radius-md) var(--radius-md) 0 0;
        }
        .stat-card.blue::after   { background: linear-gradient(90deg,#3b82f6,#60a5fa); }
        .stat-card.green::after  { background: linear-gradient(90deg,#10b981,#34d399); }
        .stat-card.orange::after { background: linear-gradient(90deg,#f59e0b,#fbbf24); }
        .stat-card.red::after    { background: linear-gradient(90deg,#ef4444,#f87171); }
        .stat-label { font-size: 11.5px; font-weight: 600; text-transform: uppercase; letter-spacing: .07em; color: var(--text-muted); margin-bottom: 10px; }
        .stat-value { font-size: 28px; font-weight: 700; color: var(--text-primary); line-height: 1; margin-bottom: 6px; }
        .stat-meta  { font-size: 12px; color: var(--text-muted); }
        .stat-icon  {
            position: absolute; top: 18px; right: 18px;
            width: 38px; height: 38px; border-radius: var(--radius-sm);
            display: flex; align-items: center; justify-content: center;
        }
        .stat-icon svg { width: 18px; height: 18px; }
        .stat-card.blue .stat-icon   { background: rgba(59,130,246,.1);  color:#3b82f6; }
        .stat-card.green .stat-icon  { background: rgba(16,185,129,.1);  color:#10b981; }
        .stat-card.orange .stat-icon { background: rgba(245,158,11,.1);  color:#f59e0b; }
        .stat-card.red .stat-icon    { background: rgba(239,68,68,.1);   color:#ef4444; }

        /* ── Layout Row ──────────────────────────────────────────── */
        .dash-row { display: grid; grid-template-columns: 1fr 340px; gap: 20px; margin-bottom: 24px; }

        /* ── Cards ───────────────────────────────────────────────── */
        .card { background: var(--bg-surface); border: 1px solid var(--border); border-radius: var(--radius-md); overflow: hidden; }
        .card-header { display: flex; align-items: flex-start; justify-content: space-between; padding: 18px 22px 0; }
        .card-title  { font-size: 14px; font-weight: 700; color: var(--text-primary); }
        .card-sub    { font-size: 12px; color: var(--text-muted); margin-top: 2px; }
        .card-body   { padding: 18px 22px 22px; }

        /* ── Bar Chart ───────────────────────────────────────────── */
        .chart-wrap  { position: relative; height: 220px; }
        .chart-bars  { display: flex; align-items: flex-end; gap: 6px; height: 100%; padding-top: 20px; }
        .bar-col     { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 5px; height: 100%; justify-content: flex-end; }
        .bar-value-label { font-size: 9.5px; color: var(--text-muted); white-space: nowrap; text-align: center; opacity: 0; transition: opacity .2s; }
        .bar-col:hover .bar-value-label { opacity: 1; }
        .bar {
            width: 100%; border-radius: 5px 5px 0 0;
            background: linear-gradient(180deg,#3b82f6,#2563eb);
            transition: height .65s cubic-bezier(.34,1.3,.64,1), filter .15s;
            min-height: 3px; cursor: pointer;
        }
        .bar:hover { filter: brightness(1.15); }
        .bar.cur { background: linear-gradient(180deg,#10b981,#059669); }
        .bar-label { font-size: 10px; color: var(--text-muted); text-align: center; white-space: nowrap; }

        /* ── Kritis List ─────────────────────────────────────────── */
        .kritis-list { display: flex; flex-direction: column; }
        .kritis-item { display: flex; align-items: center; gap: 12px; padding: 11px 0; border-bottom: 1px solid var(--border); }
        .kritis-item:last-child { border-bottom: none; }
        .kritis-icon {
            width: 32px; height: 32px; border-radius: var(--radius-sm);
            background: rgba(239,68,68,.08); border: 1px solid rgba(239,68,68,.15);
            display: flex; align-items: center; justify-content: center; color: #ef4444; flex-shrink: 0;
        }
        .kritis-icon svg { width: 13px; height: 13px; }
        .kritis-info { flex: 1; min-width: 0; }
        .kritis-name { font-size: 13px; font-weight: 600; color: var(--text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .kritis-meta { font-size: 11.5px; color: var(--text-muted); margin-top: 1px; }
        .kritis-stok { font-size: 13px; font-weight: 700; color: #ef4444; flex-shrink: 0; }
        .kritis-stok.warn { color: #f59e0b; }
        .kritis-empty { text-align: center; padding: 28px 0; color: var(--text-muted); font-size: 13px; }

        /* ── Shortcuts ───────────────────────────────────────────── */
        .shortcut-grid { display: grid; grid-template-columns: repeat(6,1fr); gap: 12px; }
        .shortcut-btn {
            display: flex; flex-direction: column; align-items: center; gap: 10px;
            padding: 18px 10px; background: var(--bg-surface);
            border: 1px solid var(--border); border-radius: var(--radius-md);
            text-decoration: none; color: var(--text-secondary); font-size: 12px;
            font-weight: 500; text-align: center; transition: all .18s; cursor: pointer;
        }
        .shortcut-btn:hover {
            border-color: var(--primary); background: var(--primary-dim);
            color: var(--primary); transform: translateY(-2px); box-shadow: var(--shadow-md);
        }
        .s-icon {
            width: 44px; height: 44px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            background: var(--bg-elevated); transition: background .18s;
        }
        .shortcut-btn:hover .s-icon { background: var(--primary-dim); }

        /* ── Spinner / loading ───────────────────────────────────── */
        .load-center { display:flex; align-items:center; justify-content:center; gap:8px; color:var(--text-muted); font-size:13px; }
        .spinner { width:16px; height:16px; border:2px solid var(--border); border-top-color:var(--primary); border-radius:50%; animation:spin .8s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* ── Mobile ──────────────────────────────────────────────── */
        .mobile-bar { display:none; align-items:center; gap:12px; padding:12px 20px; background:var(--bg-surface); border-bottom:1px solid var(--border); position:sticky; top:0; z-index:10; }
        .mobile-bar-title { font-size:15px; font-weight:700; color:var(--text-primary); }
        .hamburger { display:flex; flex-direction:column; justify-content:center; gap:5px; width:36px; height:36px; border:none; background:transparent; cursor:pointer; padding:6px; border-radius:8px; }
        .hamburger span { display:block; width:18px; height:2px; background:var(--text-primary); border-radius:2px; }
        .sidebar-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:99; }
        .sidebar-overlay.active { display:block; }

        @media(max-width:1200px) { .shortcut-grid { grid-template-columns:repeat(3,1fr); } }
        @media(max-width:1100px) { .stats-grid { grid-template-columns:repeat(2,1fr); } }
        @media(max-width:900px)  { .dash-row { grid-template-columns:1fr; } }
        @media(max-width:768px)  {
            .dashboard-content { margin-left:0; padding:20px; }
            .mobile-bar { display:flex; }
            .sidebar { position:fixed; left:0; top:0; bottom:0; z-index:200; transform:translateX(-100%); transition:transform .3s cubic-bezier(.4,0,.2,1); }
            .sidebar.active { transform:translateX(0); }
        }
        @media(max-width:540px) { .shortcut-grid { grid-template-columns:repeat(2,1fr); } }
    </style>
</head>
<body>

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
        <a href="index.php" class="nav-item active" onclick="closeSidebar()">
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

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="dashboard-content">

    <div class="mobile-bar">
        <button class="hamburger" onclick="toggleSidebar()">
            <span></span><span></span><span></span>
        </button>
        <span class="mobile-bar-title">Dashboard</span>
    </div>

    <div class="dash-header">
        <h1 class="dash-title">Dashboard</h1>
        <p class="dash-sub" id="dashDate">Memuat data dari spreadsheet...</p>
    </div>

    <!-- Stat Cards -->
    <div class="stats-grid">
        <div class="stat-card blue">
            <div class="stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/>
                </svg>
            </div>
            <div class="stat-label">Total Produk</div>
            <div class="stat-value" id="statProduk">—</div>
            <div class="stat-meta" id="statProdukMeta">Memuat...</div>
        </div>
        <div class="stat-card green">
            <div class="stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <line x1="12" y1="1" x2="12" y2="23"/>
                    <path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/>
                </svg>
            </div>
            <div class="stat-label">Penjualan Bulan Ini</div>
            <div class="stat-value" id="statPenjualan">—</div>
            <div class="stat-meta" id="statPenjualanMeta">Memuat...</div>
        </div>
        <div class="stat-card orange">
            <div class="stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8" x2="12" y2="12"/>
                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
            </div>
            <div class="stat-label">Stok Kritis</div>
            <div class="stat-value" id="statKritis">—</div>
            <div class="stat-meta">Stok 1–5 unit</div>
        </div>
        <div class="stat-card red">
            <div class="stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
            </div>
            <div class="stat-label">Barang Kosong</div>
            <div class="stat-value" id="statKosong">—</div>
            <div class="stat-meta">Stok = 0 unit</div>
        </div>
    </div>

    <!-- Chart + Kritis -->
    <div class="dash-row">
        <div class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Grafik Penjualan</div>
                    <div class="card-sub">Pendapatan per bulan (12 bulan terakhir)</div>
                </div>
                <div id="chartTotal" style="font-size:13px;font-weight:700;color:var(--primary);margin-top:2px"></div>
            </div>
            <div class="card-body">
                <div id="chartLoading" class="load-center" style="height:220px">
                    <div class="spinner"></div><span>Memuat data penjualan...</span>
                </div>
                <div class="chart-wrap" id="chartWrap" style="display:none">
                    <div class="chart-bars" id="chartBars"></div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Stok Kritis</div>
                    <div class="card-sub">Perlu restock segera</div>
                </div>
            </div>
            <div class="card-body" style="padding-top:8px">
                <div id="kritisLoading" class="load-center" style="height:120px">
                    <div class="spinner"></div><span>Memuat...</span>
                </div>
                <div class="kritis-list" id="kritisList" style="display:none"></div>
            </div>
        </div>
    </div>

    <!-- Shortcuts -->
    <div class="card" style="margin-bottom:40px">
        <div class="card-header"><div class="card-title">Akses Cepat</div></div>
        <div class="card-body">
            <div class="shortcut-grid">
                <a href="kasir-penjualan.php" class="shortcut-btn">
                    <div class="s-icon" style="color:#3b82f6">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="22" height="22"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                    </div>
                    Kasir Penjualan
                </a>
                <a href="kasir-pembelian.php" class="shortcut-btn">
                    <div class="s-icon" style="color:#10b981">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="22" height="22"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
                    </div>
                    Kasir Pembelian
                </a>
                <a href="gudang.php" class="shortcut-btn">
                    <div class="s-icon" style="color:#8b5cf6">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="22" height="22"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/><line x1="9" y1="12" x2="15" y2="12"/><line x1="9" y1="16" x2="13" y2="16"/></svg>
                    </div>
                    Data Gudang
                </a>
                <a href="barang-kosong.php" class="shortcut-btn">
                    <div class="s-icon" style="color:#ef4444">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="22" height="22"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/><line x1="12" y1="12" x2="12" y2="12.01"/></svg>
                    </div>
                    Barang Kosong
                </a>
                <a href="label-print.php" class="shortcut-btn">
                    <div class="s-icon" style="color:#f59e0b">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="22" height="22"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                    </div>
                    Cetak Label
                </a>
                <a href="generate-kode.php" class="shortcut-btn">
                    <div class="s-icon" style="color:#06b6d4">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="22" height="22"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15"/></svg>
                    </div>
                    Generate Kode
                </a>
            </div>
        </div>
    </div>

</div>

<script>
const SPREADSHEET_ID = '1zQtkYOkwWjPPll_D0u7pJNTYebWGTMl3ilPwnEIWVKQ';
const API = `https://opensheet.elk.sh/${SPREADSHEET_ID}`;
const MN = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
const MF = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
const now = new Date();

// ── UI ────────────────────────────────────────────────────────
document.getElementById('dashDate').textContent =
    `${MF[now.getMonth()]} ${now.getFullYear()} — Live dari Google Spreadsheet`;

function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('active');
    document.getElementById('sidebarOverlay').classList.toggle('active');
}
function closeSidebar() {
    document.getElementById('sidebar').classList.remove('active');
    document.getElementById('sidebarOverlay').classList.remove('active');
}
document.getElementById('sidebarOverlay').addEventListener('click', closeSidebar);

// ── Format helpers ────────────────────────────────────────────
function fmt(n) {
    if (n >= 1e9) return 'Rp ' + (n/1e9).toFixed(1).replace('.0','') + 'M';
    if (n >= 1e6) return 'Rp ' + (n/1e6).toFixed(1).replace('.0','') + 'Jt';
    if (n >= 1e3) return 'Rp ' + Math.round(n/1000) + 'rb';
    return 'Rp ' + n.toLocaleString('id-ID');
}
function fmtFull(n) { return 'Rp ' + n.toLocaleString('id-ID'); }

function parseDate(str) {
    if (!str) return null;
    if (str.includes('/')) {
        const [d,m,y] = str.split('/');
        return new Date(+y, +m-1, +d);
    }
    return new Date(str);
}

// ── Load ──────────────────────────────────────────────────────
async function loadAll() {
    const [rJual, rNon, rPpn] = await Promise.allSettled([
        fetch(`${API}/penjualan`).then(r => r.json()),
        fetch(`${API}/non_ppn`).then(r => r.json()),
        fetch(`${API}/ppn`).then(r => r.json()),
    ]);

    const penjualan = rJual.status === 'fulfilled' ? rJual.value : [];
    const allGudang = [
        ...(rNon.status === 'fulfilled' ? rNon.value : []),
        ...(rPpn.status === 'fulfilled' ? rPpn.value : []),
    ];

    buildStats(penjualan, allGudang);
    buildChart(penjualan);
    buildKritis(allGudang);
}

function buildStats(penjualan, gudang) {
    // Total produk
    const kodes = new Set(gudang.map(r => r.kode_barang || r[0]).filter(Boolean));
    document.getElementById('statProduk').textContent = kodes.size.toLocaleString('id-ID');
    document.getElementById('statProdukMeta').textContent = `${gudang.length} baris • 2 gudang`;

    // Penjualan bulan ini — kolom M (total) adalah per-nota, muncul berulang di tiap item
    // Ambil hanya 1x per no_nota untuk menghindari double-count
    const tm = now.getMonth(), ty = now.getFullYear();
    const notaSeen = new Set();
    let totalBulan = 0, txBulan = 0;
    penjualan.forEach(row => {
        const nota  = row.no_nota || row[0];
        const tgl   = parseDate(row.tanggal || row[1]);
        const total = parseInt(row.total || row[12] || 0);
        if (!nota || !tgl) return;
        if (tgl.getMonth() !== tm || tgl.getFullYear() !== ty) return;
        if (notaSeen.has(nota)) return;   // sudah dihitung, skip baris item berikutnya
        notaSeen.add(nota);
        totalBulan += total;
        txBulan++;
    });
    document.getElementById('statPenjualan').textContent = fmt(totalBulan);
    document.getElementById('statPenjualanMeta').textContent = `${txBulan} transaksi bulan ini`;

    // Kritis & kosong
    let kritis = 0, kosong = 0;
    const stokPerKode = {};
    gudang.forEach(row => {
        const kode = row.kode_barang || row[0];
        const stok = parseInt(row.stok || row[9] || 0);
        if (!kode) return;
        stokPerKode[kode] = (stokPerKode[kode] || 0) + stok;
    });
    Object.values(stokPerKode).forEach(s => {
        if (s === 0) kosong++;
        else if (s <= 5) kritis++;
    });
    document.getElementById('statKritis').textContent = kritis.toLocaleString('id-ID');
    document.getElementById('statKosong').textContent = kosong.toLocaleString('id-ID');
}

function buildChart(penjualan) {
    // Build 12-month buckets
    const buckets = {};
    for (let i = 11; i >= 0; i--) {
        const d   = new Date(now.getFullYear(), now.getMonth() - i, 1);
        const key = `${d.getFullYear()}-${d.getMonth()}`;
        buckets[key] = { label: MN[d.getMonth()], total: 0, m: d.getMonth(), y: d.getFullYear() };
    }

    // Aggregate by nota
    const seen = {};
    penjualan.forEach(row => {
        const nota  = row.no_nota || row[0];
        const tgl   = parseDate(row.tanggal || row[1]);
        const total = parseInt(row.total || row[12] || 0);
        if (!tgl || !total || seen[nota]) return;
        const key = `${tgl.getFullYear()}-${tgl.getMonth()}`;
        if (!buckets[key]) return;
        seen[nota] = true;
        buckets[key].total += total;
    });

    const months = Object.values(buckets);
    const maxVal = Math.max(...months.map(m => m.total), 1);
    const barsEl = document.getElementById('chartBars');
    barsEl.innerHTML = '';

    months.forEach(m => {
        const pct = (m.total / maxVal) * 100;
        const isCur = m.m === now.getMonth() && m.y === now.getFullYear();
        const col = document.createElement('div');
        col.className = 'bar-col';
        col.innerHTML = `
            <div class="bar-value-label">${fmt(m.total)}</div>
            <div class="bar${isCur?' cur':''}" style="height:0" title="${MF[m.m]} ${m.y}: ${fmtFull(m.total)}"></div>
            <div class="bar-label">${m.label}</div>`;
        barsEl.appendChild(col);
        setTimeout(() => {
            col.querySelector('.bar').style.height = (m.total > 0 ? Math.max(pct, 3) : 0) + '%';
        }, 60);
    });

    const ytd = months.filter(m => m.y === now.getFullYear()).reduce((a,m)=>a+m.total,0);
    document.getElementById('chartTotal').textContent = `YTD ${now.getFullYear()}: ${fmt(ytd)}`;
    document.getElementById('chartLoading').style.display = 'none';
    document.getElementById('chartWrap').style.display    = 'block';
}

function buildKritis(gudang) {
    const stokMap = {};
    gudang.forEach(row => {
        const kode = row.kode_barang || row[0];
        const nama = row.nama_produk || row[5] || row[3] || kode;
        const stok = parseInt(row.stok || row[9] || 0);
        if (!kode) return;
        if (!stokMap[kode]) stokMap[kode] = { nama, stok: 0 };
        stokMap[kode].stok += stok;
    });

    const list = Object.entries(stokMap)
        .filter(([,v]) => v.stok > 0 && v.stok <= 5)
        .sort(([,a],[,b]) => a.stok - b.stok)
        .slice(0, 9);

    document.getElementById('kritisLoading').style.display = 'none';
    const el = document.getElementById('kritisList');
    el.style.display = 'flex';

    if (!list.length) {
        el.innerHTML = `<div class="kritis-empty">
            <svg viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="1.5" width="28" height="28" style="display:block;margin:0 auto 8px">
                <path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
            </svg>
            Tidak ada stok kritis saat ini
        </div>`;
        return;
    }

    list.forEach(([kode, data]) => {
        const veryLow = data.stok <= 2;
        const item = document.createElement('div');
        item.className = 'kritis-item';
        item.innerHTML = `
            <div class="kritis-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
            </div>
            <div class="kritis-info">
                <div class="kritis-name">${data.nama}</div>
                <div class="kritis-meta">${kode}</div>
            </div>
            <div class="kritis-stok${veryLow ? '' : ' warn'}">${data.stok} pcs</div>`;
        el.appendChild(item);
    });
}

loadAll();
</script>
</body>
</html>
