<?php
$v_css   = file_exists(__DIR__ . '/style.css')  ? substr(md5_file(__DIR__ . '/style.css'),  0, 8) : 'x';
$v_js    = file_exists(__DIR__ . '/app.js')     ? substr(md5_file(__DIR__ . '/app.js'),     0, 8) : 'x';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Label — Sri Rejeki Motor</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;500;600&family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=<?= $v_css ?>">
    <!-- QRCode.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>
    /* ═══════════════════════════════════════════════════
       SCREEN STYLES
    ═══════════════════════════════════════════════════ */
    .print-toolbar {
        display: flex; align-items: center; justify-content: space-between;
        gap: 14px; margin-bottom: 20px; flex-wrap: wrap;
    }
    .print-title {
        font-family: 'Cormorant Garamond', serif;
        font-size: 30px; font-weight: 600; color: var(--text-primary); line-height: 1.1;
    }
    .print-subtitle { font-size: 14px; color: var(--text-muted); margin-top: 4px; }
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
        transition: all 0.18s; white-space: nowrap; font-family: 'Outfit', sans-serif;
    }
    .toggle-pill.active { background: #fff; color: var(--blue); box-shadow: 0 1px 6px rgba(0,0,0,0.12); }
    .toggle-pill[data-sheet="ppn"].active { color: #7c3aed; }
    .action-bar {
        display: flex; align-items: center; gap: 10px;
        background: #fff; border: 1px solid var(--border);
        border-radius: 14px; padding: 10px 16px;
        margin-bottom: 16px; flex-wrap: wrap;
        box-shadow: var(--shadow-sm);
    }
    .action-bar-left  { display: flex; align-items: center; gap: 10px; flex: 1; flex-wrap: wrap; }
    .action-bar-right { display: flex; align-items: center; gap: 10px; }
    .select-count {
        font-size: 13px; color: var(--text-secondary);
        background: var(--bg-base); border: 1px solid var(--border);
        border-radius: 8px; padding: 5px 12px; white-space: nowrap;
    }
    .select-count strong { color: var(--text-primary); font-weight: 700; }
    .btn-select-all, .btn-deselect-all {
        padding: 7px 14px; border-radius: 9px; font-size: 13px; font-weight: 600;
        font-family: 'Outfit', sans-serif; cursor: pointer; transition: all 0.15s;
        border: 1px solid var(--border); background: #fff; color: var(--text-secondary);
    }
    .btn-select-all:hover   { border-color: var(--blue); color: var(--blue); background: var(--blue-dim); }
    .btn-deselect-all:hover { border-color: var(--red);  color: var(--red);  background: rgba(239,68,68,0.07); }
    .search-bar-sm {
        display: flex; align-items: center; gap: 8px;
        background: var(--bg-base); border: 1px solid var(--border);
        border-radius: 9px; padding: 7px 12px;
    }
    .search-bar-sm svg { color: var(--text-muted); flex-shrink: 0; }
    .search-bar-sm input {
        border: none; background: transparent; outline: none;
        font-family: 'Outfit', sans-serif; font-size: 13px; color: var(--text-primary);
        width: 180px;
    }
    .search-bar-sm input::placeholder { color: var(--text-muted); }
    .btn-print {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 10px 22px; border-radius: 10px; border: none;
        background: var(--blue); color: #fff;
        font-family: 'Outfit', sans-serif; font-size: 14px; font-weight: 600;
        cursor: pointer; transition: all 0.18s;
        box-shadow: 0 4px 14px rgba(59,130,246,0.35);
        white-space: nowrap;
    }
    .btn-print:hover { background: #2563eb; transform: translateY(-1px); box-shadow: 0 6px 20px rgba(59,130,246,0.45); }
    .btn-print:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }
    .check-table-wrap {
        background: #fff; border: 1px solid var(--border);
        border-radius: 16px; overflow: hidden;
        box-shadow: var(--shadow-sm);
    }
    .check-table { width: 100%; border-collapse: collapse; font-size: 13px; }
    .check-table thead { background: var(--bg-base); }
    .check-table th {
        padding: 11px 14px; text-align: left;
        font-size: 11px; font-weight: 700; color: var(--text-muted);
        text-transform: uppercase; letter-spacing: 0.07em;
        border-bottom: 1px solid var(--border);
    }
    .check-table th:first-child, .check-table td:first-child { padding-left: 18px; width: 44px; }
    .check-table td { padding: 10px 14px; border-bottom: 1px solid #f1f3f7; vertical-align: middle; }
    .check-table tbody tr:last-child td { border-bottom: none; }
    .check-table tbody tr:hover { background: #fafbff; }
    .check-table tbody tr.selected-row { background: #eff6ff; }
    .check-table .td-code { font-family: 'Outfit', monospace; font-weight: 600; color: var(--text-primary); }
    .check-table .td-name { color: var(--text-primary); }
    .check-table .td-rak  { color: var(--blue); font-weight: 600; font-size: 12px; }
    .cb-wrap { display: flex; align-items: center; justify-content: center; }
    .cb-wrap input[type="checkbox"] { display: none; }
    .cb-box {
        width: 18px; height: 18px; border-radius: 5px;
        border: 2px solid var(--border); background: #fff;
        cursor: pointer; display: flex; align-items: center; justify-content: center;
        transition: all 0.15s; flex-shrink: 0;
    }
    .cb-box svg { display: none; }
    .cb-wrap input:checked + .cb-box { background: var(--blue); border-color: var(--blue); }
    .cb-wrap input:checked + .cb-box svg { display: block; }
    .qty-input {
        width: 58px; background: var(--bg-base); border: 1px solid var(--border);
        border-radius: 7px; color: var(--text-primary);
        font-family: 'Outfit', sans-serif; font-size: 13px; font-weight: 600;
        text-align: center; padding: 5px 6px; outline: none; transition: border-color 0.15s;
    }
    .qty-input:focus { border-color: var(--blue); background: #fff; }
    .badge-cetak {
        display: inline-flex; align-items: center; gap: 4px;
        font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 5px;
        white-space: nowrap; letter-spacing: 0.03em;
    }
    .badge-cetak.sudah { background: #dcfce7; color: #15803d; }
    .badge-cetak.belum { background: #fef3c7; color: #92400e; }
    .check-table tbody tr.row-sudah-cetak { opacity: 0.6; }
    .check-table tbody tr.row-sudah-cetak:hover { opacity: 1; }
    .filter-cetak-wrap { display: flex; align-items: center; gap: 6px; }
    .filter-cetak-btn {
        padding: 5px 12px; border-radius: 7px; font-size: 12px; font-weight: 600;
        font-family: 'Outfit', sans-serif; cursor: pointer; transition: all 0.15s;
        border: 1px solid var(--border); background: #fff; color: var(--text-muted);
    }
    .filter-cetak-btn:hover { border-color: var(--blue); color: var(--blue); }
    .filter-cetak-btn.active { background: var(--blue); border-color: var(--blue); color: #fff; }
    .loading-row td, .empty-row td {
        text-align: center; padding: 40px; color: var(--text-muted); font-size: 14px;
    }
    .preview-overlay {
        display: none; position: fixed; inset: 0;
        background: rgba(10,12,18,0.6); backdrop-filter: blur(6px);
        z-index: 1000; align-items: center; justify-content: center; padding: 20px;
    }
    .preview-overlay.open { display: flex; animation: fadeIn 0.2s ease; }
    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    .preview-box {
        background: #fff; border-radius: 20px;
        width: 100%; max-width: 520px; max-height: 90vh;
        display: flex; flex-direction: column;
        box-shadow: 0 24px 80px rgba(0,0,0,0.28);
        animation: scaleIn 0.25s cubic-bezier(0.34,1.56,0.64,1);
    }
    @keyframes scaleIn {
        from { opacity:0; transform: scale(0.94) translateY(16px); }
        to   { opacity:1; transform: scale(1) translateY(0); }
    }
    .preview-header {
        display: flex; align-items: center; justify-content: space-between;
        padding: 22px 26px 18px; border-bottom: 1px solid #e8eaed; flex-shrink: 0;
    }
    .preview-header-title { font-family: 'Cormorant Garamond', serif; font-size: 20px; font-weight: 600; }
    .preview-header-sub { font-size: 13px; color: var(--text-muted); margin-top: 2px; }
    .preview-close {
        width: 34px; height: 34px; border-radius: 9px; border: 1px solid #e5e7eb;
        background: transparent; cursor: pointer; display: flex; align-items: center;
        justify-content: center; color: #9ca3af; transition: all 0.15s;
    }
    .preview-close:hover { background: #f3f4f6; color: #374151; }
    .preview-scroll { overflow-y: auto; flex: 1; padding: 24px 26px; }
    .label-preview-grid { display: flex; flex-wrap: wrap; gap: 14px; justify-content: center; }
    .label-preview-card {
        width: 200px; height: 150px;
        background: #fff; border: 1.5px solid #d1d5db;
        border-radius: 5px; padding: 4px 12px 4px 6px;
        font-family: 'Outfit', Arial, sans-serif;
        box-shadow: 0 2px 8px rgba(0,0,0,0.09);
        display: flex; flex-direction: column;
        overflow: hidden; box-sizing: border-box; position: relative;
    }
    .lp-header { margin-bottom: 2px; flex-shrink: 0; }
    .lp-nama {
        font-size: 11.5px; font-weight: 900; color: #000;
        line-height: 1.15; word-break: break-word; text-align: center;
        overflow: hidden; max-height: 2.5em;
    }
    .lp-merk { font-size: 8.5px; color: #111; font-weight: 600; text-align: center; margin-top: 1px; }
    .lp-divider { border: none; border-top: 1px solid #000; margin: 2px 0; flex-shrink: 0; }
    .lp-body { flex: 1; display: flex; gap: 4px; overflow: hidden; min-height: 0; }
    .lp-info {
        flex: 1; display: flex; flex-direction: column; justify-content: space-evenly;
        overflow: hidden; min-width: 0;
    }
    .lp-info-row { font-size: 9px; color: #111; font-weight: 600; white-space: nowrap; overflow: hidden; line-height: 1.2; }
    .lp-right {
        flex-shrink: 0; width: 56px;
        display: flex; align-items: center; justify-content: center; padding-right: 10px;
    }
    .lp-qr img { width: 48px; height: 48px; display: block; image-rendering: pixelated; }
    .lp-qr-placeholder {
        width: 48px; height: 48px; background: #f3f4f6;
        border: 1px dashed #d1d5db; display: flex; align-items: center;
        justify-content: center; font-size: 7px; color: #9ca3af;
    }
    .lp-kode-int {
        font-size: 10px; font-weight: 900; color: #000; text-align: center;
        letter-spacing: 0.04em; font-family: 'Courier New', monospace;
        line-height: 1.2; margin-top: 2px; flex-shrink: 0;
        white-space: nowrap;
    }
    .lp-brand {
        position: absolute; right: 2px; top: 0; bottom: 0; width: 10px;
        display: flex; align-items: center; justify-content: center;
    }
    .lp-brand span {
        color: #333; font-size: 5px; font-weight: 800;
        writing-mode: vertical-rl; text-orientation: mixed;
        transform: rotate(180deg); letter-spacing: 0.1em; white-space: nowrap;
    }
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
    .preview-footer {
        display: flex; align-items: center; justify-content: space-between;
        padding: 16px 26px 22px; border-top: 1px solid #e8eaed; gap: 10px;
        flex-shrink: 0; flex-wrap: wrap;
    }
    .preview-info { font-size: 13px; color: var(--text-muted); }
    .preview-info strong { color: var(--text-primary); }
    .btn-do-print {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 10px 24px; border-radius: 10px; border: none;
        background: var(--blue); color: #fff;
        font-family: 'Outfit', sans-serif; font-size: 14px; font-weight: 600;
        cursor: pointer; box-shadow: 0 4px 12px rgba(59,130,246,0.3); transition: all 0.15s;
    }
    .btn-do-print:hover { background: #2563eb; }
    @media (max-width: 768px) {
        .page-header { margin-bottom: 6px; }
        .print-toolbar { flex-direction: column; align-items: stretch; gap: 10px; }
        .print-title  { font-size: 22px; }
        .print-subtitle { font-size: 13px; }
        .gudang-toggle-wrap { width: 100%; justify-content: space-between; }
        .toggle-pills { flex: 1; }
        .toggle-pill  { flex: 1; text-align: center; }
        .action-bar { flex-direction: column; align-items: stretch; gap: 8px; }
        .action-bar-left { flex-direction: column; align-items: stretch; gap: 8px; }
        .filter-cetak-wrap { flex-wrap: wrap; gap: 5px; }
        .btn-select-all, .btn-deselect-all { flex: 1; text-align: center; }
        .search-bar-sm { flex: 1; }
        .search-bar-sm input { width: 100%; min-width: 0; flex: 1; }
        .action-bar-right { width: 100%; }
        .btn-print { width: 100%; justify-content: center; }
        .preview-box { max-height: 100vh; border-radius: 0; }
    }
    @media (min-width: 769px) and (max-width: 1024px) {
        .action-bar { flex-wrap: wrap; }
        .action-bar-right { margin-left: auto; }
    }

    /* ═══════════════════════════════════════════════════
       PRINT — 40mm × 30mm horizontal thermal label
    ═══════════════════════════════════════════════════ */
    @media print {
        * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        html, body { margin:0 !important; padding:0 !important; background:#fff !important; width:40mm !important; }
        body > *:not(#printArea) { display:none !important; }
        #printArea { display:block !important; margin:0 !important; padding:0 !important; }
        @page { size: 40mm 30mm; margin: 0mm; }
        .print-label {
            width: 40mm; height: 30mm; box-sizing: border-box;
            padding: 1mm 3.5mm 1mm 1.5mm;
            display: flex !important; flex-direction: column; justify-content: center;
            page-break-after: always; page-break-inside: avoid;
            overflow: hidden; background: #fff;
            font-family: 'Outfit', Arial, sans-serif; position: relative;
        }
        .print-label:last-child { page-break-after: auto; }
        .pl-brand {
            position: absolute; right: 0.4mm; top: 0; bottom: 0;
            width: 3mm; display: flex; align-items: center; justify-content: center;
        }
        .pl-brand span {
            color: #333; font-size: 4pt; font-weight: 800;
            writing-mode: vertical-rl; text-orientation: mixed;
            transform: rotate(180deg); letter-spacing: 0.14em; white-space: nowrap;
            font-family: 'Outfit', Arial, sans-serif;
        }
        .pl-header { flex-shrink: 0; margin-bottom: 0.3mm; }
        .pl-nama {
            font-size: 10.5pt; font-weight: 900; color: #000;
            text-align: center; line-height: 1.1; word-break: break-word;
            overflow: hidden; max-height: 2.3em;
        }
        .pl-merk { font-size: 7pt; font-weight: 600; color: #111; text-align: center; line-height: 1.15; margin-top: 0.2mm; }
        .pl-divider { border: none; border-top: 0.35mm solid #000; margin: 0.4mm 0 0.5mm 0; flex-shrink: 0; }
        .pl-body { display: flex; flex-direction: row; gap: 1mm; align-items: center; }
        .pl-info { width: 22mm; flex-shrink: 0; display: flex; flex-direction: column; justify-content: center; gap: 0.5mm; }
        .pl-info-row { font-size: 7.5pt; font-weight: 600; color: #000; line-height: 1.1; overflow: hidden; }
        .kode-barang { white-space: nowrap; overflow: hidden; text-overflow: clip; letter-spacing: 0.05em; }
        .pl-info-row:not(.kode-barang):not(.harga-label) { white-space: normal; word-break: break-all; }
        .pl-info-row.harga-label { white-space: nowrap !important; overflow: hidden !important; word-break: normal !important; }
        .pl-right { width: 13mm; flex-shrink: 0; display: flex; align-items: center; justify-content: center; }
        .pl-qr img { width: 13mm !important; height: 13mm !important; display: block; image-rendering: pixelated; image-rendering: crisp-edges; }
        .pl-kode-int {
            flex-shrink: 0;
            font-size: 9pt; font-weight: 900; color: #000;
            text-align: center; letter-spacing: 0.04em;
            font-family: 'Courier New', Courier, monospace;
            line-height: 1.2; margin-top: 0.4mm;
            white-space: nowrap;
        }
    }
    #printArea { display: none; }
    .harga-label {
        font-weight: 900; letter-spacing: 0.03em;
        white-space: nowrap !important; overflow: hidden !important; word-break: normal !important;
    }
    </style>
</head>
<body>

<!-- ── Hidden Print Area ──────────────────────────────────────── -->
<div id="printArea"></div>

<!-- ── Preview Modal ─────────────────────────────────────────── -->
<div class="preview-overlay" id="previewOverlay">
    <div class="preview-box">
        <div class="preview-header">
            <div>
                <div class="preview-header-title">Preview Label</div>
                <div class="preview-header-sub" id="previewSubtitle">— label siap cetak</div>
            </div>
            <button class="preview-close" id="previewClose" type="button">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
        <div class="preview-scroll">
            <div class="label-preview-grid" id="previewGrid"></div>
        </div>
        <div class="preview-footer">
            <div>
                <div class="preview-info" style="margin-bottom:8px">
                    <strong id="previewTotal">0</strong> label · ukuran <strong>40 × 30 mm</strong>
                </div>
                <div style="font-size:11px;color:#374151;line-height:1.7;background:#fffbeb;border:1px solid #fcd34d;border-radius:8px;padding:10px 14px;max-width:360px;">
                    <strong style="color:#b45309;display:block;margin-bottom:4px;">⚙️ Wajib diatur di Print Dialog (XPrinter XP-D4601B):</strong>
                    <span style="display:flex;gap:6px;align-items:baseline;">
                        <span style="color:#b45309;font-weight:700;flex-shrink:0;">1.</span>
                        <span>Printer → pilih <strong>XPrinter XP-D4601B</strong></span>
                    </span>
                    <span style="display:flex;gap:6px;align-items:baseline;margin-top:2px;">
                        <span style="color:#b45309;font-weight:700;flex-shrink:0;">2.</span>
                        <span>Paper size → <strong>40 × 30 mm</strong> (custom jika belum ada)</span>
                    </span>
                    <span style="display:flex;gap:6px;align-items:baseline;margin-top:2px;">
                        <span style="color:#b45309;font-weight:700;flex-shrink:0;">3.</span>
                        <span>Margin → <strong>None / Tanpa Batas</strong></span>
                    </span>
                    <span style="display:flex;gap:6px;align-items:baseline;margin-top:2px;">
                        <span style="color:#b45309;font-weight:700;flex-shrink:0;">4.</span>
                        <span>Scale → <strong>100%</strong> (jangan "Fit to page")</span>
                    </span>
                    <span style="display:flex;gap:6px;align-items:baseline;margin-top:2px;">
                        <span style="color:#b45309;font-weight:700;flex-shrink:0;">5.</span>
                        <span><strong>Headers and footers</strong> → <strong style="color:#dc2626;">OFF</strong></span>
                    </span>
                    <span style="display:block;margin-top:6px;font-size:10px;color:#6b7280;">Chrome: Klik "More settings" → Layout: <strong>Landscape</strong> → uncheck Headers and footers</span>
                </div>
            </div>
            <button class="btn-do-print" id="btnDoPrint" type="button">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15">
                    <polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/>
                    <rect x="6" y="14" width="12" height="8"/>
                </svg>
                Print Sekarang
            </button>
        </div>
    </div>
</div>

<!-- ── App Layout ─────────────────────────────────────────────── -->
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
            <a href="label-print.php" class="nav-item active" onclick="closeMobileSidebar()">
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

        <div class="print-toolbar">
            <div>
                <h1 class="print-title">Cetak Label</h1>
                <p class="print-subtitle" id="pageSubtitle">Memuat data...</p>
            </div>
            <div class="gudang-toggle-wrap">
                <span class="gudang-toggle-label">Gudang</span>
                <div class="toggle-pills">
                    <button class="toggle-pill active" data-sheet="non_ppn">Non-PPN</button>
                    <button class="toggle-pill" data-sheet="ppn">PPN</button>
                </div>
            </div>
        </div>

        <div class="action-bar">
            <div class="action-bar-left">
                <button class="btn-select-all" id="btnSelectAll" type="button">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13" style="margin-right:4px">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                    Pilih Semua
                </button>
                <button class="btn-deselect-all" id="btnDeselectAll" type="button">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13" style="margin-right:4px">
                        <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                    Batal Semua
                </button>
                <span class="select-count" id="selectCount"><strong>0</strong> dipilih</span>
                <div class="filter-cetak-wrap">
                    <button class="filter-cetak-btn active" id="filterSemua" onclick="setFilterCetak('semua')">Semua</button>
                    <button class="filter-cetak-btn" id="filterBelum" onclick="setFilterCetak('belum')">Belum Cetak</button>
                    <button class="filter-cetak-btn" id="filterSudah" onclick="setFilterCetak('sudah')">Sudah Cetak</button>
                </div>
                <div class="search-bar-sm">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14">
                        <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                    <input type="text" id="searchInput" placeholder="Cari barang...">
                </div>
            </div>
            <div class="action-bar-right">
                <button class="btn-print" id="btnPrint" type="button" disabled>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15">
                        <polyline points="6 9 6 2 18 2 18 9"/>
                        <path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/>
                        <rect x="6" y="14" width="12" height="8"/>
                    </svg>
                    Preview &amp; Print
                    <span id="printBtnCount"></span>
                </button>
            </div>
        </div>

        <div class="check-table-wrap">
            <div style="overflow-x:auto;">
                <table class="check-table">
                    <thead>
                        <tr>
                            <th>
                                <div class="cb-wrap" id="cbHeaderWrap">
                                    <input type="checkbox" id="cbAll">
                                    <label class="cb-box" for="cbAll">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3" width="11" height="11"><polyline points="20 6 9 17 4 12"/></svg>
                                    </label>
                                </div>
                            </th>
                            <th>#</th>
                            <th>Kode Barang</th>
                            <th>Kode Internal</th>
                            <th>Nama Produk</th>
                            <th>Kategori</th>
                            <th>Lokasi Rak</th>
                            <th>Stok</th>
                            <th style="text-align:center;">Qty Label</th>
                            <th style="text-align:center;">Cetak Label</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        <tr class="loading-row">
                            <td colspan="10">Memuat data dari spreadsheet...</td>
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
    non_ppn: { label: 'Non-PPN', apiUrl: `/srm/gudang-read.php?sheet=non_ppn` },
    ppn:     { label: 'PPN',     apiUrl: `/srm/gudang-read.php?sheet=ppn`     }
};

let activeSheet  = 'non_ppn';
let allRows      = [];
let filteredRows = [];
let selected     = new Set();

// ================================================================
// COMPRESS HARGA ONLY — subscript unicode hanya untuk segmen harga & diskon
//
// Format kode_internal: D{harga}[(diskon)]-{merk}{num}[-{supplier}]
// Contoh:  "DYYY-Y001-SRM"      → "DY₃-Y001-SRM"
//          "DYYY(AN)-Y001-SRM"  → "DY₃(AN)-Y001-SRM"
//          "DKRYYYY-A002"       → "DKRY₄-A002"
//
// Aturan: hanya bagian SEBELUM '-' pertama yang di-compress (harga & diskon).
//         Segmen merk, nomor urut, dan supplier ditulis normal apa adanya.
// ================================================================
const SUBSCRIPT_MAP = {'0':'₀','1':'₁','2':'₂','3':'₃','4':'₄','5':'₅','6':'₆','7':'₇','8':'₈','9':'₉'};

function compressSegment(seg) {
    if (!seg) return seg;
    const groups = [];
    let result = seg.replace(/([A-Z])\1+/g, (match, char, offset) => {
        groups.push({ match, char, offset });
        return '\x00'; // placeholder sementara
    });
    let gi = 0;
    result = result.replace(/\x00/g, () => {
        const g = groups[gi++];
        const isLast = (gi === groups.length);
        if (isLast) {
            // Grup terakhir: huruf tunggal + subscript (misal YYYY → Y₄)
            const count = g.match.length;
            const sub = String(count).split('').map(d => SUBSCRIPT_MAP[d]).join('');
            return g.char + sub;
        }
        // Grup awal/tengah: tulis apa adanya
        return g.match;
    });
    return result;
}

function compressRepeats(str) {
    if (!str) return str;
    const dashIdx = str.indexOf('-');
    if (dashIdx === -1) return compressSegment(str); // tidak ada dash → semua harga
    const hargaPart = str.slice(0, dashIdx);  // "DYYY" atau "DYYY(AN)" — di-compress
    const restPart  = str.slice(dashIdx);     // "-Y001-SRM" — tulis normal
    return compressSegment(hargaPart) + restPart;
}

// ================================================================
// SIDEBAR MOBILE
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
// GUDANG TOGGLE
// ================================================================
document.querySelectorAll('.toggle-pill').forEach(btn => {
    btn.addEventListener('click', function () {
        if (activeSheet === this.dataset.sheet) return;
        activeSheet = this.dataset.sheet;
        document.querySelectorAll('.toggle-pill').forEach(b =>
            b.classList.toggle('active', b.dataset.sheet === activeSheet));
        selected.clear();
        loadData();
    });
});

// ================================================================
// DATA
// ================================================================
async function loadData() {
    const tbody = document.getElementById('tableBody');
    tbody.innerHTML = '<tr class="loading-row"><td colspan="10">Memuat data dari spreadsheet...</td></tr>';
    document.getElementById('pageSubtitle').textContent = 'Memuat data...';
    updateSelectionUI();

    try {
        const res  = await fetch(GUDANG_CONFIG[activeSheet].apiUrl + '&_t=' + Date.now());
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const data = await res.json();

        allRows = Array.isArray(data) ? data : [];
        filteredRows = allRows;
        document.getElementById('pageSubtitle').textContent =
            `${allRows.length} produk · Gudang ${GUDANG_CONFIG[activeSheet].label}`;
        renderTable(filteredRows);
        updateSelectionUI();
    } catch (err) {
        tbody.innerHTML = `<tr class="empty-row"><td colspan="10" style="color:var(--red)">⚠️ Gagal memuat: ${err.message}</td></tr>`;
        document.getElementById('pageSubtitle').textContent = 'Gagal memuat data';
    }
}

// ================================================================
// PAGINATION
// ================================================================
let lpCurrentPage = 0;
let lpPageSize    = 50;

function lpSetPage(page) {
    const total = Math.max(1, Math.ceil(filteredRows.length / lpPageSize));
    lpCurrentPage = Math.min(Math.max(0, page), total - 1);
    renderCurrentPage();
    renderPagination();
}

function renderCurrentPage() {
    const start = lpCurrentPage * lpPageSize;
    const slice = filteredRows.slice(start, start + lpPageSize);
    const tbody = document.getElementById('tableBody');
    if (!slice.length) {
        tbody.innerHTML = '<tr class="empty-row"><td colspan="10">Tidak ada data yang cocok.</td></tr>';
        syncHeaderCheckbox();
        return;
    }

    tbody.innerHTML = slice.map((row, fi) => {
        const origIdx    = allRows.indexOf(row);
        const isChecked  = selected.has(origIdx);
        const kodeBarang = row['kode_barang']   || row[0] || '';
        const kodeInt    = compressRepeats(row['kode_internal'] || row[1] || '');
        const namaProduk = row['nama_produk']   || row[3] || '-';
        const namaMobil  = row['nama_mobil']    || row[4] || '';
        const kategori   = row['kategori']      || row[5] || '-';
        const lokasiRak  = row['lokasi_rak']    || row[6] || '-';
        const stok       = row['stok']          || row[7] || '0';
        const cetakLabel = row['cetak_label']   || row[13] || '';
        const stokNum    = parseInt(String(stok).replace(/\D/g,''), 10) || 0;
        if (!row._printQty && row._printQty !== 0) row._printQty = stokNum;
        const qty = row._printQty;

        const sudahCetak = cetakLabel && cetakLabel.toString().trim() !== '';
        const cetakBadge = sudahCetak
            ? `<span class="badge-cetak sudah" id="badge_${origIdx}">✓ ${cetakLabel}</span>`
            : `<span class="badge-cetak belum" id="badge_${origIdx}">Belum</span>`;

        return `<tr data-orig="${origIdx}" class="${isChecked ? 'selected-row' : ''}${sudahCetak ? ' row-sudah-cetak' : ''}">
            <td>
                <div class="cb-wrap">
                    <input type="checkbox" id="cb_${origIdx}" data-orig="${origIdx}" ${isChecked ? 'checked' : ''}>
                    <label class="cb-box" for="cb_${origIdx}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3" width="11" height="11"><polyline points="20 6 9 17 4 12"/></svg>
                    </label>
                </div>
            </td>
            <td style="color:var(--text-muted);font-size:12px">${lpCurrentPage * lpPageSize + fi + 1}</td>
            <td class="td-code">${kodeBarang || '-'}</td>
            <td class="td-code" style="color:var(--text-muted)">${kodeInt || '-'}</td>
            <td class="td-name">${namaProduk}${namaMobil ? ` <span style="color:var(--text-muted);font-weight:400">· ${namaMobil}</span>` : ''}</td>
            <td style="color:var(--text-secondary)">${kategori}</td>
            <td class="td-rak">${lokasiRak}</td>
            <td style="font-size:12px">${stok}</td>
            <td style="text-align:center">
                <input type="number" class="qty-input" data-orig="${origIdx}"
                    value="${qty}" min="0" max="9999"
                    title="Jumlah label yang akan dicetak">
            </td>
            <td style="text-align:center">${cetakBadge}</td>
        </tr>`;
    }).join('');

    tbody.querySelectorAll('input[type="checkbox"][data-orig]').forEach(cb => {
        cb.addEventListener('change', function () {
            const idx = parseInt(this.dataset.orig);
            if (this.checked) selected.add(idx);
            else selected.delete(idx);
            const tr = document.querySelector(`tr[data-orig="${idx}"]`);
            if (tr) tr.classList.toggle('selected-row', this.checked);
            syncHeaderCheckbox();
            updateSelectionUI();
        });
    });

    tbody.querySelectorAll('.qty-input').forEach(inp => {
        inp.addEventListener('change', function () {
            const idx = parseInt(this.dataset.orig);
            const val = Math.max(0, Math.min(9999, parseInt(this.value) || 0));
            this.value = val;
            if (allRows[idx]) allRows[idx]._printQty = val;
        });
    });

    syncHeaderCheckbox();
}

function renderPagination() {
    const total      = filteredRows.length;
    const totalPages = Math.max(1, Math.ceil(total / lpPageSize));
    const start      = lpCurrentPage * lpPageSize + 1;
    const end        = Math.min((lpCurrentPage + 1) * lpPageSize, total);
    document.getElementById('pgInfo').textContent = total
        ? `${start}–${end} dari ${total} produk`
        : 'Tidak ada data';

    const ctrl = document.getElementById('pgControls');
    let html = `<button class="pg-btn" onclick="lpSetPage(0)" ${lpCurrentPage===0?'disabled':''}>«</button>
                <button class="pg-btn" onclick="lpSetPage(${lpCurrentPage-1})" ${lpCurrentPage===0?'disabled':''}>‹</button>`;
    const range = 2;
    for (let p = 0; p < totalPages; p++) {
        if (p === 0 || p === totalPages-1 || Math.abs(p - lpCurrentPage) <= range) {
            html += `<button class="pg-btn${p===lpCurrentPage?' active':''}" onclick="lpSetPage(${p})">${p+1}</button>`;
        } else if (Math.abs(p - lpCurrentPage) === range + 1) {
            html += `<span class="pg-sep">…</span>`;
        }
    }
    html += `<button class="pg-btn" onclick="lpSetPage(${lpCurrentPage+1})" ${lpCurrentPage>=totalPages-1?'disabled':''}>›</button>
             <button class="pg-btn" onclick="lpSetPage(${totalPages-1})" ${lpCurrentPage>=totalPages-1?'disabled':''}>»</button>`;
    ctrl.innerHTML = html;
}

function renderTable(rows) {
    filteredRows = rows || [];
    lpCurrentPage = 0;
    renderCurrentPage();
    renderPagination();
}

// ================================================================
// SELECTION HELPERS
// ================================================================
function syncHeaderCheckbox() {
    const start = lpCurrentPage * lpPageSize;
    const pageRows = filteredRows.slice(start, start + lpPageSize);
    const cbAll = document.getElementById('cbAll');
    const visibleIdxs = pageRows.map(r => allRows.indexOf(r));
    const allChecked  = visibleIdxs.length > 0 && visibleIdxs.every(i => selected.has(i));
    const someChecked = visibleIdxs.some(i => selected.has(i));
    cbAll.checked       = allChecked;
    cbAll.indeterminate = !allChecked && someChecked;
}

function updateSelectionUI() {
    const count    = selected.size;
    const totalQty = [...selected].reduce((sum, i) => sum + (allRows[i]?._printQty || 1), 0);
    document.getElementById('selectCount').innerHTML =
        `<strong>${count}</strong> dipilih · <strong>${totalQty.toLocaleString('id-ID')}</strong> label`;
    const btn = document.getElementById('btnPrint');
    btn.disabled = count === 0;
    document.getElementById('printBtnCount').textContent = count > 0 ? ` (${totalQty.toLocaleString('id-ID')})` : '';
}

document.getElementById('cbAll').addEventListener('change', function () {
    const start = lpCurrentPage * lpPageSize;
    const pageIdxs = filteredRows.slice(start, start + lpPageSize).map(r => allRows.indexOf(r));
    if (this.checked) pageIdxs.forEach(i => selected.add(i));
    else              pageIdxs.forEach(i => selected.delete(i));
    renderCurrentPage();
    renderPagination();
    syncHeaderCheckbox();
    updateSelectionUI();
});

document.getElementById('btnSelectAll').addEventListener('click', () => {
    allRows.forEach((_, i) => selected.add(i));
    renderTable(filteredRows);
    updateSelectionUI();
});

document.getElementById('btnDeselectAll').addEventListener('click', () => {
    selected.clear();
    renderTable(filteredRows);
    updateSelectionUI();
});

document.getElementById('pgSizeSelect').addEventListener('change', function () {
    lpPageSize = parseInt(this.value);
    lpSetPage(0);
});

function norm(s) { return (s || '').toLowerCase().replace(/-/g, ''); }

let activeCetakFilter = 'semua';

function setFilterCetak(mode) {
    activeCetakFilter = mode;
    document.querySelectorAll('.filter-cetak-btn').forEach(b => {
        b.classList.toggle('active', b.id === 'filter' + mode.charAt(0).toUpperCase() + mode.slice(1));
    });
    applyFilters();
}

function applyFilters() {
    const q     = document.getElementById('searchInput').value.toLowerCase().trim();
    const qNorm = norm(q);
    let rows = allRows;
    if (q) {
        rows = rows.filter(row =>
            Object.values(row).some(v => {
                const val = String(v).toLowerCase();
                return val.includes(q) || norm(val).includes(qNorm);
            })
        );
    }
    if (activeCetakFilter === 'belum') {
        rows = rows.filter(row => !(row['cetak_label'] || row[13] || '').toString().trim());
    } else if (activeCetakFilter === 'sudah') {
        rows = rows.filter(row => !!(row['cetak_label'] || row[13] || '').toString().trim());
    }
    renderTable(rows);
    syncHeaderCheckbox();
}

document.getElementById('searchInput').addEventListener('input', applyFilters);

// ================================================================
// QR CODE HELPER
// ================================================================
function makeQRDataURL(value) {
    if (!value || value.trim() === '' || value === '-') return null;
    try {
        const container = document.createElement('div');
        container.style.cssText = 'position:absolute;left:-9999px;top:-9999px;';
        document.body.appendChild(container);
        new QRCode(container, {
            text: value, width: 128, height: 128,
            colorDark: '#000000', colorLight: '#ffffff',
            correctLevel: QRCode.CorrectLevel.M,
        });
        const canvas  = container.querySelector('canvas');
        const dataURL = canvas ? canvas.toDataURL('image/png') : null;
        document.body.removeChild(container);
        return dataURL;
    } catch (e) { return null; }
}

// ================================================================
// BUILD LABEL HTML
// ================================================================
function scalePt(text, maxPt, minPt, charsPerLine) {
    const len = (text || '').length;
    if (len <= charsPerLine) return maxPt;
    return Math.max(minPt, Math.round(maxPt * charsPerLine / len * 10) / 10);
}
function scalePx(text, maxPx, minPx, charsPerLine) {
    const len = (text || '').length;
    if (len <= charsPerLine) return maxPx;
    return Math.max(minPx, Math.floor(maxPx * charsPerLine / len * 4) / 4);
}

function buildLabelHtml(row, forPrint = false) {
    const kodeBarang  = row['kode_barang']   || row[0]  || '';
    const kodeInt     = compressRepeats(row['kode_internal'] || row[1]  || '');
    const merk        = row['merk']          || row[2]  || '';
    const namaProduk  = row['nama_produk']   || row[3]  || '-';
    const namaMobil   = row['nama_mobil']    || row[4]  || '';
    const lokasiRak   = row['lokasi_rak']    || row[6]  || '';
    const hargaJual   = row['harga_jual']    || row[10] || '';
    const tanggalBeli = row['tanggal_beli']  || row[11] || '';

    // QR dibuat dari kode_internal mentah (tanpa compress) agar scanner bisa baca
    const qrValue = row['kode_internal'] || row[1] || kodeBarang;
    const qrImg   = makeQRDataURL(qrValue);
    const qrLabel = kodeInt || kodeBarang || '—';

    const hargaNum = parseInt(String(hargaJual).replace(/\D/g,''), 10);
    const hargaStr = hargaNum > 0 ? 'Rp ' + hargaNum.toLocaleString('id-ID') : '';

    const headerTitle = namaMobil ? `${namaProduk} - ${namaMobil}` : namaProduk;

    const namaPtSize = scalePt(headerTitle, 10.5, 6.5, 18);
    const merkPtSize = scalePt(merk,         7.0, 5.0, 24);
    const namaPxSize = scalePx(headerTitle, 11.5, 7.0, 18);
    const merkPxSize = scalePx(merk,         8.5, 5.5, 24);

    const infoRows = [
        kodeBarang  ? kodeBarang  : null,
        hargaStr    ? hargaStr    : null,
        tanggalBeli ? tanggalBeli : null,
        lokasiRak   ? lokasiRak   : null,
    ].filter(Boolean);

    if (forPrint) {
        const qrHtml = qrImg
            ? `<img src="${qrImg}" style="width:12.5mm;height:12.5mm;display:block;image-rendering:pixelated;">`
            : `<div style="width:12.5mm;height:12.5mm;background:#eee;display:flex;align-items:center;justify-content:center;font-size:4pt;color:#999;">QR</div>`;

        const infoHtml = infoRows.map((r, i) => {
            const isKode  = i === 0;
            const isHarga = r === hargaStr;
            let fontSize;
            if (isHarga)     fontSize = scalePt(r, 10, 6.5, 14);
            else if (isKode) fontSize = scalePt(r, 7.5, 2.5, 26);
            else             fontSize = scalePt(r, 7.5, 4.5, 14);
            return `<div class="pl-info-row ${isKode ? 'kode-barang' : ''} ${isHarga ? 'harga-label' : ''}" style="font-size:${fontSize}pt;">${r}</div>`;
        }).join('');

        return `
        <div class="print-label">
            <div class="pl-brand"><span>Sri Rejeki Motor</span></div>
            <div class="pl-header">
                <div class="pl-nama" style="font-size:${namaPtSize}pt">${headerTitle}</div>
                ${merk ? `<div class="pl-merk" style="font-size:${merkPtSize}pt">${merk}</div>` : ''}
            </div>
            <hr class="pl-divider">
            <div class="pl-body">
                <div class="pl-info">${infoHtml}</div>
                <div class="pl-right"><div class="pl-qr">${qrHtml}</div></div>
            </div>
            <div class="pl-kode-int" style="font-size:${scalePt(qrLabel, 9, 5.5, 18)}pt">${qrLabel}</div>
        </div>`;

    } else {
        const qrHtml = qrImg
            ? `<img src="${qrImg}" style="width:46px;height:46px;display:block;image-rendering:pixelated;">`
            : `<div class="lp-qr-placeholder">QR</div>`;

        const infoHtml = infoRows.map((r) => {
            const isHarga = r === hargaStr;
            const fs = scalePx(r, 9, 5.5, 12);
            return `<div class="lp-info-row${isHarga ? ' harga-label' : ''}" style="font-size:${fs}px;">${r}</div>`;
        }).join('');

        return `
        <div class="label-preview-card">
            <div class="lp-brand"><span>Sri Rejeki Motor</span></div>
            <div class="lp-header">
                <div class="lp-nama" style="font-size:${namaPxSize}px">${headerTitle}</div>
                ${merk ? `<div class="lp-merk" style="font-size:${merkPxSize}px">${merk}</div>` : ''}
            </div>
            <div class="lp-divider"></div>
            <div class="lp-body">
                <div class="lp-info">${infoHtml}</div>
                <div class="lp-right"><div class="lp-qr">${qrHtml}</div></div>
            </div>
            <div class="lp-kode-int" style="font-size:${scalePx(qrLabel, 10, 6, 17)}px">${qrLabel}</div>
        </div>`;
    }
}

// ================================================================
// PREVIEW MODAL
// ================================================================
document.getElementById('btnPrint').addEventListener('click', openPreview);

function openPreview() {
    const grid = document.getElementById('previewGrid');
    grid.innerHTML = '';
    let totalLabels = 0;
    const PREVIEW_CAP = 20;
    let previewCount  = 0;

    [...selected].forEach(idx => {
        const row = allRows[idx];
        if (!row) return;
        const qty = row._printQty !== undefined ? row._printQty : (parseInt(String(row['stok'] || row[7] || '0').replace(/\D/g,''),10) || 0);
        totalLabels += qty;
        if (previewCount < PREVIEW_CAP) {
            const show = Math.min(qty, PREVIEW_CAP - previewCount);
            for (let q = 0; q < show; q++) {
                grid.insertAdjacentHTML('beforeend', buildLabelHtml(row, false));
                previewCount++;
            }
        }
    });

    if (previewCount < totalLabels) {
        grid.insertAdjacentHTML('beforeend',
            `<div style="width:100%;text-align:center;padding:12px;font-size:13px;color:var(--text-muted);">
                ...dan <strong style="color:var(--text-primary)">${(totalLabels - previewCount).toLocaleString('id-ID')}</strong> label lainnya
            </div>`);
    }

    document.getElementById('previewTotal').textContent = totalLabels.toLocaleString('id-ID');
    document.getElementById('previewSubtitle').textContent =
        `${selected.size} produk · ${totalLabels.toLocaleString('id-ID')} label siap cetak`;
    document.getElementById('previewOverlay').classList.add('open');
    document.body.style.overflow = 'hidden';
}

document.getElementById('previewClose').addEventListener('click', closePreview);
document.getElementById('previewOverlay').addEventListener('click', function (e) { if (e.target === this) closePreview(); });
document.addEventListener('keydown', e => { if (e.key === 'Escape') closePreview(); });
function closePreview() {
    document.getElementById('previewOverlay').classList.remove('open');
    document.body.style.overflow = '';
}

// ================================================================
// PRINT
// ================================================================
document.getElementById('btnDoPrint').addEventListener('click', doPrint);

function doPrint() {
    const printArea = document.getElementById('printArea');
    printArea.innerHTML = '';
    const printedIndices = [];

    [...selected].forEach(idx => {
        const row = allRows[idx];
        if (!row) return;
        const qty = row._printQty !== undefined ? row._printQty : (parseInt(String(row['stok'] || row[7] || '0').replace(/\D/g,''),10) || 0);
        for (let q = 0; q < qty; q++) {
            printArea.insertAdjacentHTML('beforeend', buildLabelHtml(row, true));
        }
        if (qty > 0) printedIndices.push(idx);
    });

    setTimeout(() => {
        window.print();
        if (printedIndices.length > 0) markCetakLabel(printedIndices);
    }, 300);
}

async function markCetakLabel(indices) {
    const now      = new Date();
    const tgl      = now.toLocaleDateString('id-ID', {day:'2-digit', month:'2-digit', year:'numeric'});
    const jam      = now.toLocaleTimeString('id-ID', {hour:'2-digit', minute:'2-digit'});
    const stampVal = `${tgl} ${jam}`;

    const rows = indices.map(i => ({ sheet_row: i + 2, value: stampVal }));

    try {
        const res  = await fetch('submit.php', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'mark_cetak', sheet_name: activeSheet, rows })
        });
        const json = await res.json();
        if (!json.success) { console.warn('mark_cetak gagal:', json.message); return; }

        indices.forEach(i => {
            if (allRows[i]) allRows[i]['cetak_label'] = stampVal;
            const badge = document.getElementById(`badge_${i}`);
            if (badge) {
                badge.className   = 'badge-cetak sudah';
                badge.textContent = '✓ ' + stampVal;
                const tr = document.querySelector(`tr[data-orig="${i}"]`);
                if (tr) tr.classList.add('row-sudah-cetak');
            }
        });
    } catch(e) { console.warn('mark_cetak error:', e); }
}

// ================================================================
// INIT
// ================================================================
document.addEventListener('DOMContentLoaded', () => { loadData(); });
</script>
</body>
</html>