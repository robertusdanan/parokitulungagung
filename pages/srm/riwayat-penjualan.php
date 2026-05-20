<?php
$v_css = file_exists(__DIR__ . '/style.css') ? substr(md5_file(__DIR__ . '/style.css'), 0, 8) : '1';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Penjualan - Sri Rejeki Motor</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=<?= $v_css ?>">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
    <style>
        /* ── Page Layout ──────────────────────────────────────────── */
        body { display: flex; min-height: 100vh; background: var(--bg-base); font-family: 'Outfit', sans-serif; }

        .page-wrap {
            flex: 1;
            margin-left: var(--sidebar-w);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* ── Top Bar ──────────────────────────────────────────────── */
        .topbar {
            background: var(--bg-surface);
            border-bottom: 1px solid var(--border);
            padding: 0 32px;
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
        .hamburger {
            display: none;
            flex-direction: column;
            justify-content: center;
            gap: 5px;
            width: 36px; height: 36px;
            border: none; background: transparent; cursor: pointer;
            padding: 6px; border-radius: 8px;
        }
        .hamburger span { display: block; width: 18px; height: 2px; background: var(--text-primary); border-radius: 2px; }
        .topbar-title { font-size: 16px; font-weight: 700; color: var(--text-primary); }
        .topbar-meta  { font-size: 12.5px; color: var(--text-muted); }

        /* ── Content ──────────────────────────────────────────────── */
        .content { flex: 1; padding: 28px 32px 40px; }

        /* ── Summary Strip ────────────────────────────────────────── */
        .summary-strip {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
            margin-bottom: 24px;
        }
        .sum-card {
            background: var(--bg-surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            padding: 16px 20px;
            position: relative;
            overflow: hidden;
        }
        .sum-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 2px;
        }
        .sum-card.c-blue::before   { background: linear-gradient(90deg, #3b82f6, #60a5fa); }
        .sum-card.c-green::before  { background: linear-gradient(90deg, #10b981, #34d399); }
        .sum-card.c-purple::before { background: linear-gradient(90deg, #8b5cf6, #a78bfa); }
        .sum-card.c-orange::before { background: linear-gradient(90deg, #f59e0b, #fbbf24); }
        .sum-lbl { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .07em; color: var(--text-muted); margin-bottom: 6px; }
        .sum-val { font-size: 22px; font-weight: 700; color: var(--text-primary); line-height: 1; }
        .sum-sub { font-size: 11.5px; color: var(--text-muted); margin-top: 4px; }

        /* ── Filter Bar ───────────────────────────────────────────── */
        .filter-bar {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 18px;
            flex-wrap: wrap;
        }
        .filter-input {
            flex: 1;
            min-width: 200px;
            max-width: 320px;
            height: 38px;
            background: var(--bg-surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            padding: 0 12px 0 36px;
            font-family: 'Outfit', sans-serif;
            font-size: 13.5px;
            color: var(--text-primary);
            outline: none;
            transition: border-color .15s;
        }
        .filter-input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(59,130,246,.1); }
        .search-wrap { position: relative; flex: 1; min-width: 200px; max-width: 320px; }
        .search-icon { position: absolute; left: 11px; top: 50%; transform: translateY(-50%); color: var(--text-muted); pointer-events: none; }
        .search-icon svg { width: 15px; height: 15px; }

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

        .filter-count {
            margin-left: auto;
            font-size: 12.5px;
            color: var(--text-muted);
            white-space: nowrap;
        }

        /* ── Table Card ───────────────────────────────────────────── */
        .table-card {
            background: var(--bg-surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            overflow: hidden;
        }

        .riwayat-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13.5px;
        }
        .riwayat-table thead th {
            background: var(--bg-elevated);
            padding: 11px 14px;
            text-align: left;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .07em;
            color: var(--text-muted);
            border-bottom: 1px solid var(--border);
            white-space: nowrap;
            user-select: none;
            cursor: pointer;
        }
        .riwayat-table thead th:hover { color: var(--text-primary); }
        .riwayat-table thead th.sort-asc::after  { content: ' ↑'; }
        .riwayat-table thead th.sort-desc::after { content: ' ↓'; }

        /* Nota row */
        .nota-row {
            cursor: pointer;
            transition: background .12s;
            border-bottom: 1px solid var(--border);
        }
        .nota-row:hover { background: var(--bg-elevated); }
        .nota-row.open  { background: rgba(59,130,246,.04); border-bottom: none; }
        .nota-row td { padding: 12px 14px; vertical-align: middle; }

        /* Expand icon */
        .expand-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 22px; height: 22px;
            border-radius: 6px;
            background: var(--bg-elevated);
            border: 1px solid var(--border);
            transition: transform .2s, background .15s;
            flex-shrink: 0;
        }
        .nota-row.open .expand-icon { transform: rotate(90deg); background: var(--primary-dim); border-color: rgba(59,130,246,.3); }
        .expand-icon svg { width: 11px; height: 11px; color: var(--text-muted); }
        .nota-row.open .expand-icon svg { color: var(--primary); }

        /* ── Detail panel ───────────────────────────────────────── */
        .items-row { display: none; border-bottom: 1px solid var(--border); }
        .items-row.open { display: table-row; }
        .items-row td { padding: 0; }

        .items-inner {
            margin: 0 0 0 48px;
            border-left: 2px solid rgba(59,130,246,.18);
            background: linear-gradient(180deg, rgba(59,130,246,.025) 0%, rgba(59,130,246,.008) 100%);
        }

        /* Metadata strip */
        .detail-meta {
            display: flex;
            gap: 0;
            padding: 12px 20px;
            border-bottom: 1px solid rgba(59,130,246,.1);
            flex-wrap: wrap;
        }
        .detail-meta-item {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 4px 16px 4px 0;
            margin-right: 16px;
            border-right: 1px solid var(--border);
            font-size: 12px;
            color: var(--text-muted);
        }
        .detail-meta-item:last-child { border-right: none; margin-right: 0; }
        .detail-meta-item svg { width: 12px; height: 12px; flex-shrink: 0; opacity: .6; }
        .detail-meta-val { font-weight: 600; color: var(--text-secondary); }

        /* Items table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12.5px;
        }
        .items-table thead tr {
            background: rgba(59,130,246,.04);
        }
        .items-table th {
            padding: 7px 14px;
            text-align: left;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--text-muted);
            border-bottom: 1px solid rgba(59,130,246,.1);
        }
        .items-table td {
            padding: 10px 14px;
            border-bottom: 1px solid rgba(225,229,235,.5);
            color: var(--text-secondary);
            vertical-align: middle;
        }
        .items-table tbody tr:last-child td { border-bottom: none; }
        .items-table tbody tr:hover td { background: rgba(59,130,246,.04); }
        .item-kode {
            font-family: 'Courier New', monospace;
            font-size: 11px;
            font-weight: 600;
            color: var(--text-muted);
            background: var(--bg-elevated);
            padding: 2px 7px;
            border-radius: 4px;
            letter-spacing: .02em;
            white-space: nowrap;
        }
        .item-nama { font-weight: 600; color: var(--text-primary); }
        .item-attr { font-size: 11px; color: var(--text-muted); }
        .price-original {
            text-decoration: line-through;
            color: var(--text-muted);
            font-size: 11px;
            display: block;
        }
        .price-final { font-weight: 600; color: var(--text-secondary); }

        /* Total footer */
        .detail-footer {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 8px;
            padding: 10px 20px;
            border-top: 1px solid rgba(59,130,246,.1);
            background: rgba(59,130,246,.03);
            font-size: 12.5px;
            color: var(--text-muted);
        }
        .detail-footer-total {
            font-size: 14px;
            font-weight: 700;
            color: #10b981;
        }

        /* ── Export PDF button ──────────────────────────────── */
        .btn-export {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            height: 38px;
            padding: 0 16px;
            background: linear-gradient(135deg, #1e40af, #2563eb);
            color: #fff;
            border: none;
            border-radius: var(--radius-sm);
            font-family: 'Outfit', sans-serif;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all .15s;
            box-shadow: 0 2px 8px rgba(37,99,235,.3);
            white-space: nowrap;
        }
        .btn-export:hover { background: linear-gradient(135deg, #1d3fa5, #1d4ed8); box-shadow: 0 4px 14px rgba(37,99,235,.4); transform: translateY(-1px); }
        .btn-export:active { transform: translateY(0); }
        .btn-export svg { width: 14px; height: 14px; }
        .btn-export.loading { opacity: .7; pointer-events: none; }

        /* ── PDF Preview Modal ───────────────────────────────── */
        .pdf-modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.6);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(4px);
        }
        .pdf-modal-overlay.active { display: flex; }
        .pdf-modal {
            background: var(--bg-surface);
            border-radius: 16px;
            width: 480px;
            max-width: calc(100vw - 40px);
            box-shadow: 0 24px 80px rgba(0,0,0,.25);
            overflow: hidden;
            animation: modalIn .2s ease;
        }
        @keyframes modalIn { from { opacity:0; transform:scale(.95) translateY(8px); } to { opacity:1; transform:scale(1) translateY(0); } }
        .pdf-modal-head {
            padding: 20px 24px 16px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .pdf-modal-title { font-size: 15px; font-weight: 700; color: var(--text-primary); }
        .pdf-modal-close {
            width: 30px; height: 30px;
            border: none; background: var(--bg-elevated); border-radius: 8px;
            cursor: pointer; display: flex; align-items: center; justify-content: center;
            color: var(--text-muted); transition: background .12s;
        }
        .pdf-modal-close:hover { background: var(--border); color: var(--text-primary); }
        .pdf-modal-body { padding: 20px 24px; }
        .pdf-field { margin-bottom: 14px; }
        .pdf-field label { display: block; font-size: 11.5px; font-weight: 600; color: var(--text-muted); margin-bottom: 5px; text-transform: uppercase; letter-spacing: .05em; }
        .pdf-field input, .pdf-field select, .pdf-field textarea {
            width: 100%;
            background: var(--bg-elevated);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            padding: 8px 12px;
            font-family: 'Outfit', sans-serif;
            font-size: 13px;
            color: var(--text-primary);
            outline: none;
            box-sizing: border-box;
            transition: border-color .15s;
        }
        .pdf-field input:focus, .pdf-field select:focus, .pdf-field textarea:focus { border-color: var(--primary); }
        .pdf-field textarea { resize: vertical; min-height: 60px; }
        .pdf-field-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .pdf-info-box {
            background: rgba(37,99,235,.06);
            border: 1px solid rgba(37,99,235,.15);
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 12px;
            color: var(--text-secondary);
            margin-bottom: 16px;
            display: flex;
            gap: 8px;
            align-items: flex-start;
        }
        .pdf-info-box svg { width: 14px; height: 14px; color: var(--primary); flex-shrink: 0; margin-top: 1px; }
        .pdf-modal-footer {
            padding: 16px 24px 20px;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            border-top: 1px solid var(--border);
        }
        .btn-cancel {
            height: 38px; padding: 0 16px;
            background: var(--bg-elevated); border: 1px solid var(--border);
            border-radius: var(--radius-sm); font-family: 'Outfit', sans-serif;
            font-size: 13px; font-weight: 600; color: var(--text-secondary);
            cursor: pointer; transition: all .12s;
        }
        .btn-cancel:hover { background: var(--border); }
        .btn-generate {
            height: 38px; padding: 0 20px;
            background: linear-gradient(135deg, #1e40af, #2563eb);
            border: none; border-radius: var(--radius-sm);
            font-family: 'Outfit', sans-serif; font-size: 13px; font-weight: 600;
            color: #fff; cursor: pointer; transition: all .15s;
            display: flex; align-items: center; gap: 7px;
            box-shadow: 0 2px 8px rgba(37,99,235,.3);
        }
        .btn-generate:hover { background: linear-gradient(135deg, #1d3fa5, #1d4ed8); }
        .btn-generate svg { width: 14px; height: 14px; }

        /* Badges */
        .badge {
            display: inline-flex; align-items: center;
            padding: 2px 8px; border-radius: 20px;
            font-size: 11px; font-weight: 600; white-space: nowrap;
        }
        .badge-blue   { background: rgba(59,130,246,.1);  color: #3b82f6; }
        .badge-green  { background: rgba(16,185,129,.1);  color: #10b981; }
        .badge-purple { background: rgba(139,92,246,.1);  color: #8b5cf6; }
        .badge-orange { background: rgba(245,158,11,.1);  color: #f59e0b; }
        .badge-gray   { background: var(--bg-elevated);   color: var(--text-muted); }

        .nota-code {
            font-weight: 700;
            color: var(--text-primary);
            font-size: 13px;
            letter-spacing: .01em;
        }
        .amount-cell {
            font-weight: 600;
            color: var(--text-primary);
            text-align: right;
        }
        .amount-cell.green { color: #10b981; }
        .num-cell { text-align: right; color: var(--text-secondary); }

        /* Empty / loading */
        .table-empty {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-muted);
        }
        .table-empty svg { width: 40px; height: 40px; margin: 0 auto 12px; display: block; opacity: .35; }
        .table-empty p { font-size: 14px; }

        .load-overlay {
            text-align: center;
            padding: 48px 20px;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-size: 13.5px;
        }
        .spinner { width: 18px; height: 18px; border: 2px solid var(--border); border-top-color: var(--primary); border-radius: 50%; animation: spin .8s linear infinite; flex-shrink: 0; }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* Pagination */
        .pagination {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 18px;
            border-top: 1px solid var(--border);
            font-size: 13px;
            color: var(--text-muted);
        }
        .page-btns { display: flex; gap: 4px; }
        .page-btn {
            min-width: 32px; height: 32px;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            background: var(--bg-surface);
            color: var(--text-secondary);
            font-family: 'Outfit', sans-serif;
            font-size: 13px;
            cursor: pointer;
            display: inline-flex; align-items: center; justify-content: center;
            padding: 0 8px;
            transition: all .15s;
        }
        .page-btn:hover:not(:disabled) { background: var(--bg-elevated); color: var(--text-primary); }
        .page-btn.active { background: var(--primary); border-color: var(--primary); color: #fff; font-weight: 600; }
        .page-btn:disabled { opacity: .4; cursor: not-allowed; }

        /* Mobile overlay sidebar */
        .sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.5); z-index: 99; }
        .sidebar-overlay.active { display: block; }

        @media (max-width: 1100px) { .summary-strip { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 768px) {
            .page-wrap { margin-left: 0; }
            .content { padding: 20px 16px 32px; }
            .topbar { padding: 0 16px; }
            .hamburger { display: flex; }
            .sidebar { position: fixed; left: 0; top: 0; bottom: 0; z-index: 200; transform: translateX(-100%); transition: transform .3s cubic-bezier(.4,0,.2,1); }
            .sidebar.active { transform: translateX(0); }
            .riwayat-table thead th:nth-child(4),
            .riwayat-table td:nth-child(4) { display: none; }
            .summary-strip { grid-template-columns: 1fr 1fr; }
        }
    </style>
</head>
<body>

<!-- ── Sidebar ─────────────────────────────────────────────────── -->
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
        <a href="riwayat-penjualan.php" class="nav-item active" onclick="closeSidebar()">
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

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- ── Main ─────────────────────────────────────────────────────── -->
<div class="page-wrap">

    <div class="topbar">
        <div class="topbar-left">
            <button class="hamburger" onclick="toggleSidebar()">
                <span></span><span></span><span></span>
            </button>
            <div>
                <div class="topbar-title">Riwayat Penjualan</div>
                <div class="topbar-meta" id="topbarMeta">Memuat data...</div>
            </div>
        </div>
        <div style="display:flex;align-items:center;gap:12px">
            <div class="sheet-status" style="font-size:12px;color:var(--text-muted)">
                <div class="status-dot" style="width:6px;height:6px"></div>
                <span id="lastUpdate">—</span>
            </div>
            <button class="btn-export" id="btnExportPdf" onclick="openPdfModal()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                    <line x1="12" y1="18" x2="12" y2="12"/>
                    <line x1="9" y1="15" x2="15" y2="15"/>
                </svg>
                Export PDF
            </button>
        </div>
    </div>

    <div class="content">

        <!-- Summary -->
        <div class="summary-strip">
            <div class="sum-card c-blue">
                <div class="sum-lbl">Total Transaksi</div>
                <div class="sum-val" id="sumTx">—</div>
                <div class="sum-sub" id="sumTxSub">tampil</div>
            </div>
            <div class="sum-card c-green">
                <div class="sum-lbl">Total Pendapatan</div>
                <div class="sum-val" id="sumTotal">—</div>
                <div class="sum-sub" id="sumTotalSub">dari filter aktif</div>
            </div>
            <div class="sum-card c-purple">
                <div class="sum-lbl">Laba Bersih</div>
                <div class="sum-val" id="sumAvg">—</div>
                <div class="sum-sub" id="sumAvgSub">omzet − modal</div>
            </div>
            <div class="sum-card c-orange">
                <div class="sum-lbl">Total Item Terjual</div>
                <div class="sum-val" id="sumItems">—</div>
                <div class="sum-sub">pcs dari filter aktif</div>
            </div>
        </div>

        <!-- Filter -->
        <div class="filter-bar">
            <div class="search-wrap">
                <span class="search-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                </span>
                <input type="text" id="searchInput" class="filter-input" placeholder="Cari nota, pelanggan, produk...">
            </div>

            <select id="filterBulan" class="filter-select">
                <option value="">Semua Bulan</option>
            </select>

            <select id="filterGudang" class="filter-select">
                <option value="">Semua Gudang</option>
                <option value="non_ppn">Non-PPN</option>
                <option value="ppn">PPN</option>
            </select>

            <select id="filterMetode" class="filter-select">
                <option value="">Semua Metode</option>
                <option value="Tunai">Tunai</option>
                <option value="Transfer">Transfer</option>
                <option value="QRIS">QRIS</option>
                <option value="Kredit">Kredit</option>
            </select>

            <div class="filter-count" id="filterCount">—</div>
        </div>

        <!-- Table -->
        <div class="table-card">
            <div id="loadingRow" class="load-overlay">
                <div class="spinner"></div>
                <span>Memuat data penjualan dari spreadsheet...</span>
            </div>

            <table class="riwayat-table" id="mainTable" style="display:none">
                <thead>
                    <tr>
                        <th style="width:32px"></th>
                        <th data-col="no_nota">No. Nota</th>
                        <th data-col="tanggal">Tanggal</th>
                        <th data-col="cust">Pelanggan</th>
                        <th data-col="metode">Metode</th>
                        <th data-col="gudang">Gudang</th>
                        <th data-col="qty" class="num-cell">Qty</th>
                        <th data-col="total" class="num-cell">Total</th>
                        <th data-col="keterangan">Ket.</th>
                    </tr>
                </thead>
                <tbody id="tableBody"></tbody>
            </table>

            <div id="emptyRow" class="table-empty" style="display:none">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                <p>Tidak ada transaksi ditemukan</p>
            </div>

            <div class="pagination" id="pagination" style="display:none">
                <span id="pageInfo" style="font-size:12.5px;color:var(--text-muted)">—</span>
                <div class="page-btns" id="pageBtns"></div>
            </div>
        </div>

    </div><!-- /.content -->
</div><!-- /.page-wrap -->

<!-- ── PDF Export Modal ────────────────────────────────────────── -->
<!-- PENTING: Modal harus ada di DOM SEBELUM tag <script> agar addEventListener tidak null -->
<div class="pdf-modal-overlay" id="pdfModalOverlay">
    <div class="pdf-modal">
        <div class="pdf-modal-head">
            <div class="pdf-modal-title">
                <svg style="width:16px;height:16px;vertical-align:middle;margin-right:6px;color:#2563eb" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/>
                </svg>
                Ekspor Laporan PDF
            </div>
            <button class="pdf-modal-close" onclick="closePdfModal()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="14" height="14"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div class="pdf-modal-body">
            <div class="pdf-info-box" id="pdfInfoBox">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <span id="pdfInfoText">—</span>
            </div>
            <div class="pdf-field-row">
                <div class="pdf-field">
                    <label>Nama Perusahaan / Toko</label>
                    <input type="text" id="pdfNama" value="Sri Rejeki Motor">
                </div>
                <div class="pdf-field">
                    <label>Dibuat Oleh</label>
                    <input type="text" id="pdfDibuat" placeholder="Nama pelapor">
                </div>
            </div>
            <div class="pdf-field">
                <label>Alamat</label>
                <textarea id="pdfAlamat" rows="2" placeholder="Opsional..."></textarea>
            </div>
            <div class="pdf-field">
                <label>Kepada (Ditujukan kepada)</label>
                <input type="text" id="pdfKepada" placeholder="Opsional — contoh: Pimpinan Sri Rejeki Motor">
            </div>
            <div class="pdf-field">
                <label>Catatan / Keterangan laporan</label>
                <textarea id="pdfCatatan" rows="2" placeholder="Opsional..."></textarea>
            </div>
        </div>
        <div class="pdf-modal-footer">
            <button class="btn-cancel" onclick="closePdfModal()">Batal</button>
            <button class="btn-generate" id="btnGenerate" onclick="generatePDF()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/>
                    <polyline points="7 10 12 15 17 10"/>
                    <line x1="12" y1="15" x2="12" y2="3"/>
                </svg>
                Download PDF
            </button>
        </div>
    </div>
</div>

<!-- ── JavaScript (setelah semua HTML ada di DOM) ─────────────── -->
<script>
const SPREADSHEET_ID = '1zQtkYOkwWjPPll_D0u7pJNTYebWGTMl3ilPwnEIWVKQ';
const API = `https://opensheet.elk.sh/${SPREADSHEET_ID}/penjualan`;
const API_BARANG = `https://opensheet.elk.sh/${SPREADSHEET_ID}/non_ppn`;

// Map harga beli: kode_barang → harga efektif (harga_final jika ada, else harga_beli)
let hargaBeliMap = {};
let supplierMap  = {};

const MF = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];

/* ── helpers ──────────────────────────────────────────────── */
function fmtRp(n) {
    n = parseInt(n) || 0;
    if (n >= 1e9) return 'Rp' + (n/1e9).toFixed(1).replace('.0','') + 'M';
    if (n >= 1e6) return 'Rp' + (n/1e6).toFixed(1).replace('.0','') + 'Jt';
    return 'Rp ' + n.toLocaleString('id-ID');
}
function fmtRpFull(n) { return 'Rp ' + (parseInt(n)||0).toLocaleString('id-ID'); }

function parseDate(str) {
    if (!str) return null;
    if (str.includes('/')) {
        const [datePart, timePart] = str.split(' ');
        const [d, m, y] = datePart.split('/');
        if (timePart) {
            const [hh, mm, ss] = timePart.split(':');
            return new Date(+y, +m-1, +d, +hh, +mm, +ss);
        }
        return new Date(+y, +m-1, +d);
    }
    return new Date(str);
}
function fmtDate(str) {
    const d = parseDate(str);
    if (!d) return str || '—';
    return `${String(d.getDate()).padStart(2,'0')}/${String(d.getMonth()+1).padStart(2,'0')}/${d.getFullYear()}`;
}

function metodeBadge(m) {
    const map = { 'Tunai':'badge-green', 'Transfer':'badge-blue', 'QRIS':'badge-purple', 'Kredit':'badge-orange' };
    const cls = map[m] || 'badge-gray';
    return `<span class="badge ${cls}">${m||'—'}</span>`;
}
function gudangBadge(g) {
    const norm = (g || '').toLowerCase().replace(/[\s_\-]/g, '');
    return norm === 'ppn'
        ? `<span class="badge badge-purple">PPN</span>`
        : `<span class="badge badge-gray">Non-PPN</span>`;
}

function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('active');
    document.getElementById('sidebarOverlay').classList.toggle('active');
}
function closeSidebar() {
    document.getElementById('sidebar').classList.remove('active');
    document.getElementById('sidebarOverlay').classList.remove('active');
}
document.getElementById('sidebarOverlay').addEventListener('click', closeSidebar);

/* ── State ────────────────────────────────────────────────── */
let rawRows = [];
let notaMap = {};
let notaList = [];
let filtered = [];
let sortCol = 'tanggal';
let sortDir = 'desc';
let page = 1;
const PER_PAGE = 25;

/* ── Load ─────────────────────────────────────────────────── */
async function load() {
    try {
        const [resp, respBarang] = await Promise.all([
            fetch(API),
            fetch(API_BARANG)
        ]);
        rawRows = await resp.json();
        const barangRows = await respBarang.json();

        // Bangun map dari sheet non_ppn: harga beli + supplier
        hargaBeliMap = {};
        supplierMap  = {};
        barangRows.forEach(b => {
            const kode       = (b.kode_barang || b[3]  || '').toString().trim();
            const hargaBeli  = parseInt(b.harga_beli  || b[9]  || 0);
            const hargaFinal = parseInt(b.harga_final || b[15] || 0);
            const supplier   = (b.supplier    || b[8]  || '').toString().trim();
            if (!kode) return;
            hargaBeliMap[kode] = hargaFinal > 0 ? hargaFinal : hargaBeli;
            if (supplier) supplierMap[kode] = supplier;
        });

        notaMap = {};
        rawRows.forEach(row => {
            const nota   = row.no_nota    || row[0]  || '';
            const waktu  = row.waktu_transaksi || row[19] || '';
            const tgl    = waktu ? waktu.split(' ')[0] : (row.tanggal || row[1] || '');
            const gudang = row.jenis_gudang || row[2]  || '';
            const kode   = row.kode_barang|| row[3]  || '';
            const ki     = row.kode_internal||row[4] || '';
            const nama   = row.nama_produk|| row[5]  || '';
            const mobil  = row.nama_mobil || row[6]  || '';
            const merk   = row.merk       || row[7]  || '';
            const kat    = row.kategori   || row[8]  || '';
            const qty    = parseInt(row.qty || row[9] || 0);
            const hJual  = parseInt(row.harga_jual  || row[10] || 0);
            const sub    = parseInt(row.subtotal    || row[11] || 0);
            const total  = parseInt(row.total       || row[12] || 0);
            const metode = (row.metode || row[13] || '').trim();
            const ket    = (row.ket    || row[14] || '').trim();
            const uang   = parseInt(row.uang_diterima||row[15]||0);
            const dis    = parseFloat(row.diskon_pct||row[16]||0);
            const cust   = (row.costumer     || row[17] || '').trim();
            const hAsli  = parseInt(row.harga_asli  || row[18] || hJual);

            if (!nota) return;

            if (!notaMap[nota]) {
                notaMap[nota] = {
                    nota, tgl, gudang,
                    metode: metode || '',
                    ket: ket || '',
                    cust: cust !== '' ? cust : '-',
                    total, uang, waktu,
                    items: [],
                    totalQty: 0,
                    totalOmzet: 0,
                    totalModal: 0,
                };
            } else {
                if (!notaMap[nota].metode && metode) notaMap[nota].metode = metode;
                if (!notaMap[nota].ket && ket)       notaMap[nota].ket = ket;
                if ((notaMap[nota].cust === '-' || !notaMap[nota].cust) && cust) notaMap[nota].cust = cust;
            }

            // Hitung laba per item
            const itemOmzet  = sub > 0 ? sub : (hJual * qty);
            const kodeClean  = (kode||'').toString().trim();
            const hargaBeli  = hargaBeliMap[kodeClean] || 0;
            const itemModal  = hargaBeli * qty;
            const supplier   = supplierMap[kodeClean] || '';
            notaMap[nota].totalOmzet += itemOmzet;
            notaMap[nota].totalModal += itemModal;

            notaMap[nota].items.push({ kode, ki, nama, mobil, merk, kat, qty, hJual, hAsli, sub, dis, supplier });
            notaMap[nota].totalQty += qty;
        });

        notaList = Object.keys(notaMap);

        // Populate month filter
        const months = new Set();
        notaList.forEach(n => {
            const d = parseDate(notaMap[n].tgl);
            if (d) months.add(`${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}`);
        });
        const sortedMonths = [...months].sort().reverse();
        const selBulan = document.getElementById('filterBulan');
        sortedMonths.forEach(m => {
            const [y, mo] = m.split('-');
            const opt = document.createElement('option');
            opt.value = m;
            opt.textContent = `${MF[parseInt(mo)-1]} ${y}`;
            selBulan.appendChild(opt);
        });

        // Default: current month
        const now = new Date();
        const curKey = `${now.getFullYear()}-${String(now.getMonth()+1).padStart(2,'0')}`;
        if (months.has(curKey)) selBulan.value = curKey;

        document.getElementById('lastUpdate').textContent = 'Diperbarui baru saja';

        applyFilters();
        document.getElementById('loadingRow').style.display = 'none';
        document.getElementById('mainTable').style.display  = 'table';

    } catch(e) {
        document.getElementById('loadingRow').innerHTML =
            `<div style="color:#ef4444;padding:40px;text-align:center">Gagal memuat data: ${e.message}</div>`;
    }
}

/* ── Filter + Sort ────────────────────────────────────────── */
function applyFilters() {
    const q      = document.getElementById('searchInput').value.toLowerCase().trim();
    const bulan  = document.getElementById('filterBulan').value;
    const gudang = document.getElementById('filterGudang').value;
    const metode = document.getElementById('filterMetode').value;

    filtered = notaList.filter(nota => {
        const n = notaMap[nota];

        if (bulan) {
            const d = parseDate(n.tgl);
            if (!d) return false;
            const key = `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}`;
            if (key !== bulan) return false;
        }
        if (gudang) {
            const rawG = (n.gudang || '').toLowerCase().replace(/[\s_\-]/g, '');
            const selG = gudang.toLowerCase().replace(/[\s_\-]/g, '');
            if (rawG !== selG) return false;
        }
        if (metode && n.metode !== metode) return false;

        if (q) {
            const hay = [nota, n.cust, n.ket, n.metode,
                ...n.items.map(i => i.nama + ' ' + i.kode + ' ' + i.mobil)
            ].join(' ').toLowerCase();
            if (!hay.includes(q)) return false;
        }
        return true;
    });

    // Sort
    filtered.sort((a, b) => {
        const na = notaMap[a], nb = notaMap[b];
        let va, vb;
        if (sortCol === 'tanggal') {
            va = parseDate(na.tgl)?.getTime() || 0;
            vb = parseDate(nb.tgl)?.getTime() || 0;
        } else if (sortCol === 'total') {
            va = na.total; vb = nb.total;
        } else if (sortCol === 'qty') {
            va = na.totalQty; vb = nb.totalQty;
        } else {
            va = (na[sortCol] || '').toLowerCase();
            vb = (nb[sortCol] || '').toLowerCase();
        }
        return sortDir === 'asc' ? (va > vb ? 1 : -1) : (va < vb ? 1 : -1);
    });

    page = 1;
    updateSummary();
    render();
}

function updateSummary() {
    const totalTx    = filtered.length;
    const totalRp    = filtered.reduce((s,n) => s + (notaMap[n].total||0), 0);
    const totalItems = filtered.reduce((s,n) => s + (notaMap[n].totalQty||0), 0);
    const totalOmzet = filtered.reduce((s,n) => s + (notaMap[n].totalOmzet||0), 0);
    const totalModal = filtered.reduce((s,n) => s + (notaMap[n].totalModal||0), 0);
    const laba       = totalOmzet - totalModal;

    document.getElementById('sumTx').textContent      = totalTx.toLocaleString('id-ID');
    document.getElementById('sumTotal').textContent   = fmtRp(totalRp);
    document.getElementById('sumAvg').textContent     = fmtRp(laba);
    document.getElementById('sumItems').textContent   = totalItems.toLocaleString('id-ID');

    // Warna laba: hijau kalau positif, merah kalau negatif
    const avgEl = document.getElementById('sumAvg');
    avgEl.style.color = laba >= 0 ? 'var(--green, #10b981)' : '#ef4444';
    document.getElementById('sumAvgSub').textContent = laba >= 0 ? 'keuntungan bersih' : 'kerugian';

    const bulanEl = document.getElementById('filterBulan');
    const bulanTxt = bulanEl.value
        ? bulanEl.options[bulanEl.selectedIndex].text
        : 'Semua Bulan';
    document.getElementById('sumTxSub').textContent   = bulanTxt;
    document.getElementById('topbarMeta').textContent =
        `${totalTx} transaksi • ${bulanTxt}`;
    document.getElementById('filterCount').textContent =
        `${totalTx} nota ditemukan`;
}

/* ── Render table ─────────────────────────────────────────── */
function render() {
    const tbody = document.getElementById('tableBody');
    tbody.innerHTML = '';

    const totalPages = Math.max(1, Math.ceil(filtered.length / PER_PAGE));
    if (page > totalPages) page = totalPages;
    const slice = filtered.slice((page-1)*PER_PAGE, page*PER_PAGE);

    if (!slice.length) {
        document.getElementById('emptyRow').style.display   = 'block';
        document.getElementById('pagination').style.display = 'none';
        return;
    }
    document.getElementById('emptyRow').style.display   = 'none';
    document.getElementById('pagination').style.display = 'flex';

    slice.forEach(nota => {
        const n = notaMap[nota];

        const tr = document.createElement('tr');
        tr.className = 'nota-row';
        tr.dataset.nota = nota;
        tr.innerHTML = `
            <td>
                <div class="expand-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <polyline points="9 18 15 12 9 6"/>
                    </svg>
                </div>
            </td>
            <td><span class="nota-code">${nota}</span></td>
            <td style="white-space:nowrap;color:var(--text-secondary)">${fmtDate(n.tgl)}</td>
            <td style="color:var(--text-secondary);max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${n.cust||'—'}</td>
            <td>${metodeBadge(n.metode)}</td>
            <td>${gudangBadge(n.gudang)}</td>
            <td class="num-cell">${n.totalQty.toLocaleString('id-ID')}</td>
            <td class="amount-cell green">${fmtRpFull(n.total)}</td>
            <td style="color:var(--text-muted);font-size:12px;max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${n.ket||'—'}</td>`;
        tbody.appendChild(tr);

        const sub = document.createElement('tr');
        sub.className = 'items-row';
        sub.dataset.nota = nota;

        const itemsHtml = n.items.map(it => `
            <tr>
                <td><span class="item-kode">${it.kode||'—'}</span></td>
                <td>
                    <div class="item-nama">${it.nama||'—'}</div>
                    ${(it.mobil||it.merk) ? `<div class="item-attr">${[it.mobil,it.merk].filter(Boolean).join(' · ')}</div>` : ''}
                </td>
                <td style="color:var(--text-secondary);font-size:12.5px">${it.supplier||'—'}</td>
                <td class="num-cell" style="font-weight:600;color:var(--text-secondary)">${it.qty}×</td>
                <td class="num-cell">
                    ${it.dis > 0 ? `<span class="price-original">${fmtRpFull(it.hAsli)}</span>` : ''}
                    <span class="price-final">${fmtRpFull(it.hJual)}</span>
                    ${it.dis > 0 ? `<span class="badge badge-orange" style="font-size:10px;margin-left:4px;vertical-align:middle">-${it.dis}%</span>` : ''}
                </td>
                <td class="num-cell" style="font-weight:700;color:#10b981;font-size:13px">${fmtRpFull(it.sub)}</td>
            </tr>`).join('');

        sub.innerHTML = `<td colspan="9">
            <div class="items-inner">
                <div class="detail-meta">
                    <div class="detail-meta-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        <span class="detail-meta-val">${fmtDate(n.tgl)}</span>
                    </div>
                    <div class="detail-meta-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        <span class="detail-meta-val">${n.cust||'—'}</span>
                    </div>
                    <div class="detail-meta-item">${metodeBadge(n.metode)}</div>
                    <div class="detail-meta-item">${gudangBadge(n.gudang)}</div>
                    ${n.ket ? `<div class="detail-meta-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                        <span class="detail-meta-val">${n.ket}</span>
                    </div>` : ''}
                </div>
                <table class="items-table">
                    <thead>
                        <tr>
                            <th style="width:130px">Kode</th>
                            <th>Produk</th>
                            <th style="width:140px">Supplier</th>
                            <th class="num-cell" style="width:50px">Qty</th>
                            <th class="num-cell" style="width:160px">Harga Jual</th>
                            <th class="num-cell" style="width:140px">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>${itemsHtml}</tbody>
                </table>
                <div class="detail-footer">
                    <span>${n.items.length} item &nbsp;·&nbsp; ${n.totalQty} pcs</span>
                    <span style="margin-left:16px;color:var(--text-muted)">Total</span>
                    <span class="detail-footer-total">${fmtRpFull(n.total)}</span>
                </div>
            </div>
        </td>`;
        tbody.appendChild(sub);

        tr.addEventListener('click', () => {
            const open = tr.classList.toggle('open');
            sub.classList.toggle('open', open);
        });
    });

    renderPagination(totalPages);
}

function renderPagination(totalPages) {
    const start = (page-1)*PER_PAGE + 1;
    const end   = Math.min(page*PER_PAGE, filtered.length);
    document.getElementById('pageInfo').textContent =
        `Menampilkan ${start}–${end} dari ${filtered.length} nota`;

    const btns = document.getElementById('pageBtns');
    btns.innerHTML = '';

    const addBtn = (label, target, disabled=false, active=false) => {
        const b = document.createElement('button');
        b.className = 'page-btn' + (active ? ' active' : '');
        b.textContent = label;
        b.disabled = disabled;
        b.addEventListener('click', () => { page = target; render(); window.scrollTo(0,0); });
        btns.appendChild(b);
    };

    addBtn('‹', page-1, page===1);
    const range = [];
    for (let i = 1; i <= totalPages; i++) {
        if (i === 1 || i === totalPages || Math.abs(i-page) <= 1) range.push(i);
        else if (range[range.length-1] !== '…') range.push('…');
    }
    range.forEach(r => {
        if (r === '…') {
            const s = document.createElement('span');
            s.textContent = '…';
            s.style.cssText = 'padding:0 4px;color:var(--text-muted)';
            btns.appendChild(s);
        } else {
            addBtn(r, r, false, r===page);
        }
    });
    addBtn('›', page+1, page===totalPages);
}

/* ── Sort headers ─────────────────────────────────────────── */
document.querySelectorAll('#mainTable thead th[data-col]').forEach(th => {
    th.addEventListener('click', () => {
        const col = th.dataset.col;
        if (sortCol === col) sortDir = sortDir === 'asc' ? 'desc' : 'asc';
        else { sortCol = col; sortDir = 'desc'; }
        document.querySelectorAll('#mainTable thead th').forEach(t => t.classList.remove('sort-asc','sort-desc'));
        th.classList.add(sortDir === 'asc' ? 'sort-asc' : 'sort-desc');
        applyFilters();
    });
});
document.querySelector('#mainTable thead th[data-col="tanggal"]')?.classList.add('sort-desc');

/* ── Filter events ────────────────────────────────────────── */
let searchTimer;
document.getElementById('searchInput').addEventListener('input', () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(applyFilters, 250);
});
document.getElementById('filterBulan').addEventListener('change',  applyFilters);
document.getElementById('filterGudang').addEventListener('change', applyFilters);
document.getElementById('filterMetode').addEventListener('change', applyFilters);

/* ── PDF Modal ────────────────────────────────────────────── */
function openPdfModal() {
    const bulanEl  = document.getElementById('filterBulan');
    const gudangEl = document.getElementById('filterGudang');
    const metodeEl = document.getElementById('filterMetode');
    const bulanTxt  = bulanEl.value  ? bulanEl.options[bulanEl.selectedIndex].text  : 'Semua Bulan';
    const gudangTxt = gudangEl.value ? gudangEl.options[gudangEl.selectedIndex].text : 'Semua Gudang';
    const metodeTxt = metodeEl.value ? metodeEl.options[metodeEl.selectedIndex].text : 'Semua Metode';
    document.getElementById('pdfInfoText').textContent =
        `${filtered.length} nota · ${bulanTxt} · ${gudangTxt} · ${metodeTxt}`;
    document.getElementById('pdfModalOverlay').classList.add('active');
}
function closePdfModal() {
    document.getElementById('pdfModalOverlay').classList.remove('active');
}

/* ── Klik overlay untuk tutup modal ──────────────────────── */
document.getElementById('pdfModalOverlay').addEventListener('click', function(e) {
    if (e.target === this) closePdfModal();
});

/* ── Generate PDF ─────────────────────────────────────────── */
async function generatePDF() {
    const btn = document.getElementById('btnGenerate');
    btn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><circle cx="12" cy="12" r="10"/></svg> Membuat PDF...';
    btn.style.opacity = '.7';
    btn.style.pointerEvents = 'none';
    await new Promise(r => setTimeout(r, 80));

    try {
        const { jsPDF } = window.jspdf;
        // A4 Landscape
        const doc = new jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });

        const W  = 297;
        const H  = 210;
        const M  = 14;
        const CW = W - M * 2;
        const now = new Date();

        // ── Form inputs ──
        const nama    = document.getElementById('pdfNama').value    || 'Sri Rejeki Motor';
        const alamat  = document.getElementById('pdfAlamat').value  || '';
        const dibuat  = document.getElementById('pdfDibuat').value  || '';
        const kepada  = document.getElementById('pdfKepada')?.value || '';
        const catatan = document.getElementById('pdfCatatan').value || '';

        // ── Filter labels ──
        const bulanEl  = document.getElementById('filterBulan');
        const gudangEl = document.getElementById('filterGudang');
        const metodeEl = document.getElementById('filterMetode');
        const periodeLabel = bulanEl.value  ? bulanEl.options[bulanEl.selectedIndex].text  : 'Semua Bulan';
        const gudangLabel  = gudangEl.value ? gudangEl.options[gudangEl.selectedIndex].text : 'Semua Gudang';
        const metodeLabel  = metodeEl.value ? metodeEl.options[metodeEl.selectedIndex].text : 'Semua Metode';

        // ── Aggregates ──
        const totalNota  = filtered.length;
        const totalRp    = filtered.reduce((s,n) => s + (notaMap[n].total||0), 0);
        const totalItems = filtered.reduce((s,n) => s + (notaMap[n].totalQty||0), 0);
        const totalOmzetPdf = filtered.reduce((s,n) => s + (notaMap[n].totalOmzet||0), 0);
        const totalModalPdf = filtered.reduce((s,n) => s + (notaMap[n].totalModal||0), 0);
        const labaBersih = totalOmzetPdf - totalModalPdf;

        // ── Helpers ──
        const IDR = v => 'Rp ' + Math.round(v||0).toLocaleString('id-ID');
        const fmtD = str => {
            const d = parseDate(str);
            if (!d) return str || '—';
            return String(d.getDate()).padStart(2,'0') + '/' +
                   String(d.getMonth()+1).padStart(2,'0') + '/' + d.getFullYear();
        };
        const tglCetak = String(now.getDate()).padStart(2,'0') + '/' +
                         String(now.getMonth()+1).padStart(2,'0') + '/' + now.getFullYear() + '  ' +
                         String(now.getHours()).padStart(2,'0') + ':' +
                         String(now.getMinutes()).padStart(2,'0');
        const bulanIndo = ['Januari','Februari','Maret','April','Mei','Juni',
                           'Juli','Agustus','September','Oktober','November','Desember'];

        // ── Palette ──
        const C = {
            blue:    [37,  99,  235],
            navy:    [15,  23,  42],
            navyMid: [30,  41,  59],
            blueDim: [59,  130, 246],
            mid:     [71,  85,  105],
            muted:   [148, 163, 184],
            border:  [226, 232, 240],
            bgAlt:   [248, 250, 252],
            green:   [5,   150, 105],
            white:   [255, 255, 255],
            lightBlue: [191, 219, 254],
            paleBlue:  [239, 246, 255],
        };

        // ════════════════════════════════════════════════
        //  HEADER — dipanggil tiap halaman baru
        // ════════════════════════════════════════════════
        function drawHeader(isFirst) {
            const hH = isFirst ? 30 : 18;

            // Panel kiri — navy gelap
            doc.setFillColor(...C.navy);
            doc.rect(0, 0, W * 0.60, hH, 'F');

            // Panel kanan — biru
            doc.setFillColor(...C.blue);
            doc.rect(W * 0.58, 0, W * 0.42, hH, 'F');

            // Garis diagonal pemisah — buat efek "potongan"
            doc.setFillColor(...C.blue);
            doc.triangle(W * 0.55, 0,  W * 0.62, 0,  W * 0.55, hH, 'F');

            // Garis bawah aksen
            doc.setFillColor(...C.blueDim);
            doc.rect(0, hH, W, 0.8, 'F');

            if (isFirst) {
                // ── Logo bulat dengan inisial ──
                doc.setFillColor(...C.blue);
                doc.circle(M + 7, 15, 7, 'F');
                doc.setDrawColor(191, 219, 254);
                doc.setLineWidth(0.5);
                doc.circle(M + 7, 15, 7, 'S');
                const initials = nama.split(' ').slice(0,2).map(w => w[0]?.toUpperCase() || '').join('');
                doc.setFont('helvetica', 'bold');
                doc.setFontSize(initials.length > 1 ? 7 : 10);
                doc.setTextColor(...C.white);
                doc.text(initials, M + 7, initials.length > 1 ? 16 : 15.5, { align: 'center' });

                // Nama perusahaan
                doc.setFont('helvetica', 'bold');
                doc.setFontSize(15);
                doc.setTextColor(...C.white);
                doc.text(nama, M + 18, 12);

                // Alamat
                if (alamat) {
                    doc.setFont('helvetica', 'normal');
                    doc.setFontSize(7);
                    doc.setTextColor(...C.lightBlue);
                    doc.text(alamat, M + 18, 18);
                }

                // Garis tipis di bawah nama
                doc.setDrawColor(59, 130, 246);
                doc.setLineWidth(0.3);
                doc.line(M + 18, 20.5, W * 0.52, 20.5);

                // Sub label kiri bawah
                doc.setFont('helvetica', 'normal');
                doc.setFontSize(6.5);
                doc.setTextColor(148, 163, 184);
                doc.text('Sistem Manajemen Toko', M + 18, 25.5);

                // Judul laporan (kanan atas)
                doc.setFont('helvetica', 'bold');
                doc.setFontSize(11);
                doc.setTextColor(...C.white);
                doc.text('LAPORAN RIWAYAT PENJUALAN', W - M, 9, { align: 'right' });

                // Garis di bawah judul
                doc.setDrawColor(255, 255, 255);
                doc.setLineWidth(0.25);
                doc.setLineDashPattern([1, 1], 0);
                doc.line(W * 0.65, 11, W - M, 11);
                doc.setLineDashPattern([], 0);

                // Periode & filter info
                doc.setFont('helvetica', 'normal');
                doc.setFontSize(7.5);
                doc.setTextColor(...C.lightBlue);
                doc.text(periodeLabel, W - M, 16, { align: 'right' });

                doc.setFontSize(6.5);
                doc.setTextColor(191, 219, 254);
                doc.text(gudangLabel + '  ·  ' + metodeLabel, W - M, 21, { align: 'right' });
                doc.text('Dicetak: ' + tglCetak, W - M, 26.5, { align: 'right' });

            } else {
                // Header ringkas halaman lanjutan
                doc.setFont('helvetica', 'bold');
                doc.setFontSize(11);
                doc.setTextColor(...C.white);
                doc.text(nama, M, 10.5);
                doc.setFont('helvetica', 'normal');
                doc.setFontSize(7);
                doc.setTextColor(...C.lightBlue);
                doc.text('Laporan Riwayat Penjualan  ·  ' + periodeLabel, M, 15.5);

                doc.setFont('helvetica', 'bold');
                doc.setFontSize(9);
                doc.setTextColor(...C.white);
                doc.text('LAPORAN PENJUALAN', W - M, 8, { align: 'right' });
                doc.setFont('helvetica', 'normal');
                doc.setFontSize(6.5);
                doc.setTextColor(...C.lightBlue);
                doc.text(periodeLabel + '  ·  ' + gudangLabel, W - M, 14.5, { align: 'right' });
            }

            return isFirst ? 33 : 21;
        }

        // ════════════════════════════════════════════════
        //  FOOTER
        // ════════════════════════════════════════════════
        function drawFooter(pNum) {
            const fH  = 10;
            const fY  = H - fH;

            // Garis tipis di atas footer
            doc.setDrawColor(...C.border);
            doc.setLineWidth(0.3);
            doc.line(M, fY - 0.5, W - M, fY - 0.5);

            // Background footer
            doc.setFillColor(...C.navy);
            doc.rect(0, fY, W, fH, 'F');

            // Kiri: nama toko · periode · gudang
            doc.setFont('helvetica', 'normal');
            doc.setFontSize(6.5);
            doc.setTextColor(...C.muted);
            doc.text(nama + '  ·  ' + periodeLabel + '  ·  ' + gudangLabel, M, fY + 6.5);

            // Tengah: dibuat oleh
            if (dibuat) {
                doc.setFont('helvetica', 'normal');
                doc.setFontSize(6.5);
                doc.setTextColor(...C.muted);
                doc.text('Dibuat oleh: ' + dibuat, W / 2, fY + 6.5, { align: 'center' });
            }

            // Kanan: nomor halaman
            doc.setFont('helvetica', 'bold');
            doc.setFontSize(7);
            doc.setTextColor(...C.white);
            doc.text('Halaman  ' + pNum, W - M, fY + 6.5, { align: 'right' });
        }

        // ════════════════════════════════════════════════
        //  HAL 1 — INFO BARIS + KARTU RINGKASAN
        // ════════════════════════════════════════════════
        let Y = drawHeader(true);

        // Baris kecil: dibuat oleh / kepada
        if (dibuat || kepada) {
            doc.setFillColor(...C.paleBlue);
            const infoBoxH = 7;
            doc.roundedRect(M, Y, CW, infoBoxH, 1, 1, 'F');
            doc.setFont('helvetica', 'normal');
            doc.setFontSize(7.5);
            doc.setTextColor(...C.mid);
            let infoStr = '';
            if (dibuat)  infoStr += 'Dibuat oleh: ' + dibuat;
            if (kepada)  infoStr += (infoStr ? '   ·   ' : '') + 'Kepada: ' + kepada;
            doc.text(infoStr, M + 5, Y + 4.8);
            Y += infoBoxH + 4;
        }

        // 4 Kartu ringkasan — horizontal, compact
        const cards = [
            { lbl: 'TOTAL TRANSAKSI',    val: totalNota.toLocaleString('id-ID') + ' nota', sub: periodeLabel,                        accent: C.blue,   bgLight: [239,246,255] },
            { lbl: 'TOTAL PENDAPATAN',   val: IDR(totalRp),                                sub: 'omzet periode ini',                 accent: C.green,  bgLight: [236,253,245] },
            { lbl: 'LABA BERSIH',        val: IDR(labaBersih),                             sub: labaBersih >= 0 ? 'keuntungan bersih' : 'kerugian', accent: labaBersih >= 0 ? C.green : [220,38,38], bgLight: labaBersih >= 0 ? [236,253,245] : [254,242,242] },
            { lbl: 'TOTAL ITEM TERJUAL', val: totalItems.toLocaleString('id-ID') + ' pcs', sub: 'dari filter aktif',                 accent: [180,83,9], bgLight: [255,247,237] },
        ];
        const cW  = (CW - 3 * 5) / 4;
        const cH  = 18;
        cards.forEach((c, i) => {
            const cx = M + i * (cW + 5);

            // Shadow
            doc.setFillColor(215, 225, 236);
            doc.roundedRect(cx + 0.8, Y + 0.8, cW, cH, 2.5, 2.5, 'F');

            // Card background
            doc.setFillColor(...c.bgLight);
            doc.roundedRect(cx, Y, cW, cH, 2.5, 2.5, 'F');

            // Accent bar kiri
            doc.setFillColor(...c.accent);
            doc.roundedRect(cx, Y, 3, cH, 1.5, 1.5, 'F');
            doc.rect(cx + 1.5, Y, 1.5, cH, 'F');

            // Border tipis
            doc.setDrawColor(...c.accent);
            doc.setLineWidth(0.2);
            doc.roundedRect(cx, Y, cW, cH, 2.5, 2.5, 'S');

            // Label
            doc.setFont('helvetica', 'bold');
            doc.setFontSize(5.5);
            doc.setTextColor(...C.mid);
            doc.text(c.lbl, cx + 7, Y + 5.5);

            // Nilai utama
            doc.setFont('helvetica', 'bold');
            const valFontSize = c.val.length > 14 ? 8 : 10;
            doc.setFontSize(valFontSize);
            doc.setTextColor(...c.accent);
            doc.text(c.val, cx + 7, Y + 12.5);

            // Sub-label
            doc.setFont('helvetica', 'normal');
            doc.setFontSize(5.5);
            doc.setTextColor(...C.muted);
            doc.text(c.sub, cx + 7, Y + 16.5);
        });
        Y += cH + 7;

        // ════════════════════════════════════════════════
        //  TABEL UTAMA
        // ════════════════════════════════════════════════

        // Siapkan baris: satu baris per item, grouped per nota
        const tableRows = [];
        let rNum = 1;
        filtered.forEach(nota => {
            const n = notaMap[nota];
            const gudangNorm = (n.gudang || '').toLowerCase().replace(/[\s_\-]/g, '');
            const gudangTxt  = gudangNorm === 'ppn' ? 'PPN' : 'Non-PPN';
            n.items.forEach((it, idx) => {
                const isFirst = idx === 0;
                const custVal = (n.cust && n.cust !== '-' && n.cust !== '—') ? n.cust : '—';
                tableRows.push([
                    isFirst ? rNum++ : '',
                    isFirst ? nota : '',
                    isFirst ? fmtD(n.tgl) : '',
                    isFirst ? custVal : '',
                    isFirst ? (n.metode || '—') : '',
                    isFirst ? gudangTxt : '',
                    it.kode || '—',
                    it.nama || '—',
                    it.supplier || '—',
                    it.qty,
                    it.dis > 0 ? '-' + it.dis + '%' : '—',
                    IDR(it.hJual),
                    IDR(it.sub),
                ]);
            });
        });

        // ── Hitung rowSpan per nota (untuk merge visual) ──
        const notaSpanMap = {};
        let curNotaStart = -1;
        let curNotaLen   = 0;
        tableRows.forEach((row, idx) => {
            if (row[1] !== '') {
                if (curNotaStart >= 0) notaSpanMap[curNotaStart] = curNotaLen;
                curNotaStart = idx;
                curNotaLen   = 1;
            } else {
                curNotaLen++;
            }
        });
        if (curNotaStart >= 0) notaSpanMap[curNotaStart] = curNotaLen;

        const mergeInfo   = {};
        const endYPerNota = {};

        // Judul tabel
        doc.setFillColor(...C.paleBlue);
        doc.roundedRect(M, Y - 5, CW, 5.5, 1, 1, 'F');
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(7);
        doc.setTextColor(...C.blue);
        doc.text('DETAIL TRANSAKSI PENJUALAN', M + 4, Y - 1.3);
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(6.5);
        doc.setTextColor(...C.mid);
        doc.text(filtered.length + ' nota  ·  ' + tableRows.length + ' baris', W - M, Y - 1.3, { align: 'right' });

        doc.autoTable({
            startY: Y,
            margin: { left: M, right: M, bottom: 14 },
            head: [['#', 'No. Nota', 'Tgl', 'Customer', 'Metode', 'Gudang',
                    'Kode Barang', 'Nama Produk', 'Supplier',
                    'Qty', 'Dis%', 'Harga Jual', 'Subtotal']],
            body: tableRows,
            styles: {
                font: 'helvetica',
                fontSize: 7.5,
                cellPadding: { top: 3, right: 3.5, bottom: 3, left: 3.5 },
                textColor: [...C.mid],
                lineColor: [...C.border],
                lineWidth: 0.15,
                overflow: 'linebreak',
                minCellHeight: 8,
            },
            headStyles: {
                fillColor: [...C.navy],
                textColor: [...C.white],
                fontStyle: 'bold',
                fontSize: 6.5,
                cellPadding: { top: 3.5, right: 3, bottom: 3.5, left: 3 },
                lineWidth: 0,
                overflow: 'ellipsize',
                minCellHeight: 0,
            },
            alternateRowStyles: { fillColor: [...C.bgAlt] },
            columnStyles: {
                0:  { cellWidth: 5,    halign: 'center', textColor: [...C.muted], fontSize: 6,   overflow: 'ellipsize' },
                1:  { cellWidth: 28,   fontStyle: 'bold', textColor: [...C.navy], fontSize: 6.5, overflow: 'ellipsize' },
                2:  { cellWidth: 16,   halign: 'center', textColor: [...C.mid],   fontSize: 6.5, overflow: 'ellipsize' },
                3:  { cellWidth: 22,   textColor: [...C.mid],   fontSize: 6.5, overflow: 'ellipsize' },
                4:  { cellWidth: 14,   halign: 'center', textColor: [...C.mid],   fontSize: 6.5, overflow: 'ellipsize' },
                5:  { cellWidth: 17,   halign: 'center', textColor: [...C.mid],   fontSize: 6.5, overflow: 'ellipsize' },
                6:  { cellWidth: 19,   textColor: [...C.muted],  fontSize: 6.5, overflow: 'ellipsize' },
                7:  { cellWidth: 'auto', overflow: 'linebreak', fontSize: 7.5 },
                8:  { cellWidth: 22,   textColor: [...C.muted],  fontSize: 6.5, overflow: 'ellipsize' },
                9:  { cellWidth: 8,    halign: 'center', fontStyle: 'bold', textColor: [...C.navy], fontSize: 7, overflow: 'ellipsize' },
                10: { cellWidth: 11,   halign: 'center', textColor: [180,83,9], fontStyle: 'bold', fontSize: 6, overflow: 'ellipsize' },
                11: { cellWidth: 26,   halign: 'right', textColor: [...C.mid],  fontSize: 7, overflow: 'ellipsize' },
                12: { cellWidth: 27,   halign: 'right', fontStyle: 'bold', textColor: [...C.green], fontSize: 7, overflow: 'ellipsize' },
            },
            didParseCell: (data) => {
                if (data.section !== 'body') return;
                const rowIdx = data.row.index;
                const colIdx = data.column.index;
                const row    = tableRows[rowIdx];

                const nextRow = tableRows[rowIdx + 1];
                const isLastOfNota = !nextRow || nextRow[1] !== '';
                if (isLastOfNota) {
                    data.cell.styles.lineWidthBottom = 0.5;
                    data.cell.styles.lineColor = [148, 163, 184];
                }

                if (colIdx <= 5 && row[1] === '') {
                    data.cell.text = [];
                    data.cell.styles.lineWidthTop    = 0;
                    data.cell.styles.lineWidthBottom = 0;
                    data.cell.styles.lineWidthLeft   = 0;
                    data.cell.styles.lineWidthRight  = 0;
                }
            },
            didDrawCell: (data) => {
                if (data.section !== 'body') return;
                const rowIdx = data.row.index;
                const colIdx = data.column.index;
                const row    = tableRows[rowIdx];
                const cellBot = data.cell.y + data.cell.height;

                let firstIdx = rowIdx;
                while (firstIdx > 0 && tableRows[firstIdx][1] === '') firstIdx--;

                endYPerNota[firstIdx] = Math.max(endYPerNota[firstIdx] || 0, cellBot);

                const span = notaSpanMap[firstIdx] || 1;

                if (colIdx <= 5) {
                    if (row[1] === '') {
                        const bg = rowIdx % 2 === 0 ? C.white : C.bgAlt;
                        doc.setFillColor(...bg);
                        doc.rect(data.cell.x, data.cell.y, data.cell.width, data.cell.height, 'F');
                    } else if (span > 1) {
                        if (!mergeInfo[rowIdx]) mergeInfo[rowIdx] = { firstY: data.cell.y, bg: rowIdx % 2 === 0 ? C.white : C.bgAlt, cols: {} };
                        mergeInfo[rowIdx].cols[colIdx] = { x: data.cell.x, w: data.cell.width };
                    }
                }
            },
            didDrawPage: (data) => {
                const pNum = data.pageNumber;
                drawFooter(pNum);
                if (pNum > 1) drawHeader(false);
            },
        });

        // ── Gambar overlay merge setelah tabel selesai ──
        Object.entries(mergeInfo).forEach(([rIdxStr, info]) => {
            const rIdx   = parseInt(rIdxStr);
            const endY   = endYPerNota[rIdx] || (info.firstY + 8);
            const totalH = endY - info.firstY;
            const centerY = info.firstY + totalH / 2;
            const row    = tableRows[rIdx];

            Object.entries(info.cols).forEach(([cIdxStr, { x, w }]) => {
                const cIdx = parseInt(cIdxStr);

                doc.setFillColor(...info.bg);
                doc.rect(x, info.firstY, w, totalH, 'F');

                const txt = String(row[cIdx] || '');
                if (!txt) return;
                const fs     = cIdx === 0 ? 6 : cIdx === 1 ? 7 : cIdx === 2 ? 6.5 : 6.5;
                const style  = (cIdx === 1) ? 'bold' : 'normal';
                const color  = cIdx === 1 ? C.navy : C.mid;
                const halign = (cIdx === 0 || cIdx === 2 || cIdx === 4 || cIdx === 5) ? 'center' : 'left';
                const textX  = halign === 'center' ? x + w / 2 : x + 3.5;

                doc.setFont('helvetica', style);
                doc.setFontSize(fs);
                doc.setTextColor(...color);
                doc.text(txt, textX, centerY, { align: halign, baseline: 'middle' });
            });
        });


        // ════════════════════════════════════════════════
        //  HALAMAN PENUTUP — Ringkasan + Tanda Tangan
        //  Selalu dimulai di halaman baru yang bersih
        // ════════════════════════════════════════════════
        doc.addPage();
        const summaryPage = doc.internal.getNumberOfPages();
        drawHeader(false);
        drawFooter(summaryPage);

        let tY = 24;  // mulai setelah header halaman lanjutan

        // ── Panel kiri: Catatan (jika ada) ──
        const totW = 100;
        const totX = W - M - totW;

        if (catatan) {
            const cNotaW = totX - M - 6;
            doc.setFillColor(...C.paleBlue);
            doc.roundedRect(M, tY, cNotaW, 18, 2, 2, 'F');
            doc.setFillColor(...C.blue);
            doc.roundedRect(M, tY, 3, 18, 1.5, 1.5, 'F');
            doc.rect(M + 1.5, tY, 1.5, 18, 'F');
            doc.setFont('helvetica', 'bold');
            doc.setFontSize(7);
            doc.setTextColor(...C.blue);
            doc.text('Catatan:', M + 7, tY + 6.5);
            doc.setFont('helvetica', 'normal');
            doc.setFontSize(7);
            doc.setTextColor(...C.mid);
            const cLines = doc.splitTextToSize(catatan, cNotaW - 32);
            cLines.slice(0, 3).forEach((ln, i) => doc.text(ln, M + 7, tY + 12 + i * 4));
        }

        // ── Panel kanan: Ringkasan Keuangan ──
        const totalModal2 = filtered.reduce((s,n) => s + (notaMap[n].totalModal||0), 0);
        const labaColor   = labaBersih >= 0 ? [74, 222, 128] : [248, 113, 113];

        doc.setFillColor(...C.navy);
        doc.roundedRect(totX, tY, totW, 40, 3, 3, 'F');

        // Judul panel
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(7);
        doc.setTextColor(147, 197, 253);
        doc.text('RINGKASAN KEUANGAN', totX + 6, tY + 6.5);

        // Garis tipis pemisah judul
        doc.setDrawColor(59, 130, 246);
        doc.setLineWidth(0.3);
        doc.line(totX + 6, tY + 9, totX + totW - 6, tY + 9);

        // Row 1 — Total Nota
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(7);
        doc.setTextColor(148, 163, 184);
        doc.text('Jumlah Transaksi', totX + 6, tY + 15);
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(7.5);
        doc.setTextColor(...C.white);
        doc.text(totalNota.toLocaleString('id-ID') + ' nota', totX + totW - 6, tY + 15, { align: 'right' });

        // Row 2 — Total Pendapatan
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(7);
        doc.setTextColor(148, 163, 184);
        doc.text('Total Pendapatan', totX + 6, tY + 22);
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(8);
        doc.setTextColor(...C.white);
        doc.text(IDR(totalRp), totX + totW - 6, tY + 22, { align: 'right' });

        // Row 3 — Modal Pokok
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(7);
        doc.setTextColor(148, 163, 184);
        doc.text('Modal Pokok (HPP)', totX + 6, tY + 29);
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(8);
        doc.setTextColor(251, 191, 36);
        doc.text('(' + IDR(totalModal2) + ')', totX + totW - 6, tY + 29, { align: 'right' });

        // Garis sebelum laba
        doc.setDrawColor(59, 130, 246);
        doc.setLineWidth(0.4);
        doc.line(totX + 6, tY + 32, totX + totW - 6, tY + 32);

        // Row 4 — Laba Bersih (lebih besar)
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(6.5);
        doc.setTextColor(147, 197, 253);
        doc.text('LABA BERSIH', totX + 6, tY + 37.5);
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(11);
        doc.setTextColor(...labaColor);
        doc.text(IDR(labaBersih), totX + totW - 6, tY + 38, { align: 'right' });

        // ── Garis pemisah horizontal sebelum tanda tangan ──
        const sigStartY = tY + 50;
        doc.setDrawColor(...C.border);
        doc.setLineWidth(0.3);
        doc.line(M, sigStartY - 4, W - M, sigStartY - 4);

        // Label "Pengesahan Laporan"
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(7);
        doc.setTextColor(...C.mid);
        doc.text('PENGESAHAN LAPORAN', M, sigStartY - 0.5);
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(6.5);
        doc.setTextColor(...C.muted);
        doc.text('Laporan ini dicetak secara otomatis oleh sistem pada ' + tglCetak, W - M, sigStartY - 0.5, { align: 'right' });

        // ── Tiga kotak tanda tangan ──
        const sigH  = 28;
        const sigW  = (CW - 2 * 8) / 3;
        const sigY2 = sigStartY + 5;

        const sigBoxes = [
            { title: 'Diperiksa Oleh,',   sub: 'Supervisor / Kepala Toko', name: '' },
            { title: 'Mengetahui,',        sub: 'Pimpinan / Pemilik',       name: '' },
            { title: 'Dibuat Oleh,',       sub: 'Petugas Laporan',          name: dibuat || '' },
        ];

        sigBoxes.forEach((box, i) => {
            const bx = M + i * (sigW + 8);

            // Background
            doc.setFillColor(...C.bgAlt);
            doc.roundedRect(bx, sigY2, sigW, sigH, 2, 2, 'F');

            // Border
            doc.setDrawColor(...C.border);
            doc.setLineWidth(0.25);
            doc.roundedRect(bx, sigY2, sigW, sigH, 2, 2, 'S');

            // Accent bar atas
            doc.setFillColor(...C.blue);
            doc.roundedRect(bx, sigY2, sigW, 1.5, 1, 0, 'F');

            // Judul kotak
            doc.setFont('helvetica', 'bold');
            doc.setFontSize(6.5);
            doc.setTextColor(...C.mid);
            doc.text(box.title, bx + 6, sigY2 + 7);

            // Sub-label jabatan
            doc.setFont('helvetica', 'normal');
            doc.setFontSize(6);
            doc.setTextColor(...C.muted);
            doc.text(box.sub, bx + 6, sigY2 + 11.5);

            // Nama (jika ada)
            if (box.name) {
                doc.setFont('helvetica', 'bold');
                doc.setFontSize(6.5);
                doc.setTextColor(...C.mid);
                doc.text(box.name, bx + sigW / 2, sigY2 + sigH - 8, { align: 'center' });
            }

            // Garis tanda tangan
            doc.setDrawColor(180, 192, 210);
            doc.setLineWidth(0.4);
            doc.line(bx + 8, sigY2 + sigH - 4, bx + sigW - 8, sigY2 + sigH - 4);
        });

        const fileName = `Laporan_Penjualan_${periodeLabel.replace(/\s+/g,'_')}_${tglCetak.split(' ')[0].replace(/\//g,'-')}.pdf`;
        doc.save(fileName);

    } catch(e) {
        alert('Gagal membuat PDF: ' + e.message);
        console.error(e);
    } finally {
        btn.innerHTML = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg> Download PDF`;
        btn.style.opacity = '';
        btn.style.pointerEvents = '';
        closePdfModal();
    }
}

load();
</script>

</body>
</html>