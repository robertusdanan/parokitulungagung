<?php
// ── Cache Busting ──────────────────────────────────────────────────────────────
$v_css   = substr(md5_file(__DIR__ . '/style.css'), 0, 8);
$v_js    = substr(md5_file(__DIR__ . '/app.js'),    0, 8);
$v_build = substr(md5(
    md5_file(__DIR__ . '/style.css')  .
    md5_file(__DIR__ . '/app.js')     .
    (file_exists(__DIR__ . '/index.php') ? md5_file(__DIR__ . '/index.php') : '')
), 0, 12);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Gudang - Sri Rejeki Motor</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&display=swap">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=<?= $v_css ?>">
    <style>
        /* ── Modal ───────────────────────────────────────────────── */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(10, 12, 18, 0.65);
            backdrop-filter: blur(6px);
            -webkit-backdrop-filter: blur(6px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .modal-overlay.open { display: flex; animation: modalFadeIn 0.2s ease; }
        @keyframes modalFadeIn { from { opacity: 0; } to { opacity: 1; } }

        .modal-box {
            background: #ffffff;
            border: none;
            border-radius: 20px;
            width: 100%;
            max-width: 780px;
            max-height: 92vh;
            overflow-y: auto;
            box-shadow: 0 24px 80px rgba(0,0,0,0.28), 0 4px 16px rgba(0,0,0,0.1);
            animation: modalBoxIn 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        @keyframes modalBoxIn {
            from { opacity: 0; transform: scale(0.94) translateY(16px); }
            to   { opacity: 1; transform: scale(1)    translateY(0); }
        }

        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 24px 28px 20px;
            border-bottom: 1px solid #e8eaed;
            position: sticky;
            top: 0;
            background: #ffffff;
            border-radius: 20px 20px 0 0;
            z-index: 2;
        }
        .modal-title {
            
            font-size: 22px;
            font-weight: 600;
            color: #111827;
        }
        .modal-subtitle { font-size: 13px; color: #6b7280; margin-top: 2px; }

        .modal-close {
            width: 36px; height: 36px;
            border-radius: 10px;
            border: 1px solid #e5e7eb;
            background: transparent;
            cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            color: #9ca3af;
            transition: all 0.15s;
        }
        .modal-close:hover { background: #f3f4f6; color: #374151; }

        .modal-body  { padding: 24px 28px; background: #ffffff; }
        .modal-footer {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 10px;
            padding: 20px 28px 24px;
            border-top: 1px solid #e8eaed;
            background: #ffffff;
            border-radius: 0 0 20px 20px;
        }

        /* ── Form inside modal ───────────────────────────────────── */
        .modal-body .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        .modal-body .field-group { display: flex; flex-direction: column; gap: 6px; }
        .modal-body .field-group.full-width { grid-column: 1 / -1; }
        .modal-body .field-label {
            font-size: 12px;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            display: flex; align-items: center; gap: 8px;
        }
        .modal-body .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }
        .modal-body .input-icon {
            position: absolute;
            left: 13px;
            color: #9ca3af;
            display: flex; align-items: center;
            pointer-events: none;
            width: 16px; height: 16px;
        }
        .modal-body .currency-icon {
            font-size: 12px; font-weight: 700;
        }
        .modal-body .field-input {
            width: 100%;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            color: #111827;
            font-family: 'Outfit', sans-serif;
            font-size: 14px;
            padding: 11px 14px 11px 38px;
            outline: none;
            transition: border-color 0.15s, box-shadow 0.15s, background 0.15s;
        }
        .modal-body .field-input:focus {
            background: #ffffff;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59,130,246,0.12);
        }
        .modal-body .field-input::placeholder { color: #d1d5db; }
        .modal-body .input-suffix {
            position: absolute; right: 13px;
            font-size: 12px; color: #9ca3af;
            pointer-events: none;
        }
        .modal-body .field-preview {
            font-size: 12px; color: #6b7280;
            margin-top: 3px; min-height: 16px;
        }
        .modal-body .margin-card {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 10px;
            padding: 12px 16px;
            display: flex; gap: 24px;
        }
        .modal-body .margin-item { display: flex; flex-direction: column; gap: 2px; }
        .modal-body .margin-label { font-size: 11px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.06em; }
        .modal-body .margin-value { font-size: 15px; font-weight: 700; color: #2563eb; }
        .modal-body .margin-value.negative { color: #ef4444; }

        /* Lokasi dropdown */
        .modal-body .custom-select-wrapper { position: relative; }
        .modal-body .select-arrow {
            position: absolute; right: 13px;
            pointer-events: none; color: var(--text-muted);
        }
        .modal-body .dropdown-list {
            display: none;
            position: absolute;
            top: calc(100% + 4px); left: 0; right: 0;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            z-index: 200;
            max-height: 200px; overflow-y: auto;
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
        }
        .modal-body .custom-select-wrapper.open .dropdown-list { display: block; }
        .modal-body .dropdown-item {
            display: flex; align-items: center; gap: 10px;
            padding: 8px 14px; cursor: pointer;
            font-size: 13px; color: #111827;
            transition: background 0.1s;
        }
        .modal-body .dropdown-item:hover,
        .modal-body .dropdown-item.highlighted { background: #eff6ff; }
        .modal-body .item-icon {
            width: 26px; height: 26px;
            background: #dbeafe; border-radius: 6px;
            display: flex; align-items: center; justify-content: center;
            font-size: 10px; font-weight: 700; color: #2563eb;
        }
        .modal-body .dropdown-hint {
            padding: 14px; font-size: 13px; color: #9ca3af;
            display: flex; align-items: center; gap: 8px;
        }
        .modal-body .dropdown-count {
            padding: 6px 14px; font-size: 11px;
            color: #9ca3af; border-top: 1px solid #f3f4f6;
        }
        .badge-scan {
            display: inline-flex; align-items: center; gap: 4px;
            background: #dbeafe; color: #2563eb;
            font-size: 10px; font-weight: 600;
            padding: 2px 7px; border-radius: 4px; letter-spacing: 0.04em;
        }
        .scan-pulse {
            position: absolute; inset: 0; border-radius: 10px;
            pointer-events: none; border: 2px solid #34D399; opacity: 0;
        }
        .scan-pulse.active { animation: scanRing 0.6s ease-out forwards; }
        @keyframes scanRing {
            0%   { opacity: 0.8; transform: scale(0.97); }
            100% { opacity: 0;   transform: scale(1.04); }
        }
        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-6px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ── Buttons ─────────────────────────────────────────────── */
        .btn-add-produk {
            display: inline-flex; align-items: center; gap: 8px;
            background: var(--blue); color: #fff;
            border: none; border-radius: 12px;
            padding: 10px 20px;
            font-family: 'Outfit', sans-serif; font-size: 14px; font-weight: 600;
            cursor: pointer; transition: all 0.18s; white-space: nowrap;
            box-shadow: 0 4px 16px rgba(96,165,250,0.35);
        }
        .btn-add-produk:hover {
            background: #3b82f6;
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(96,165,250,0.45);
        }
        .btn-add-produk:active { transform: translateY(0); }

        .btn-modal-cancel {
            padding: 10px 20px; border-radius: 10px;
            border: 1px solid #e5e7eb; background: transparent;
            color: #6b7280; font-family: 'Outfit', sans-serif;
            font-size: 14px; font-weight: 500; cursor: pointer; transition: all 0.15s;
        }
        .btn-modal-cancel:hover { background: #f3f4f6; color: #374151; }

        .btn-modal-submit {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 10px 24px; border-radius: 10px; border: none;
            background: var(--blue); color: #fff;
            font-family: 'Outfit', sans-serif; font-size: 14px; font-weight: 600;
            cursor: pointer; transition: all 0.18s;
            box-shadow: 0 4px 12px rgba(96,165,250,0.3);
        }
        .btn-modal-submit:hover { background: #3b82f6; }
        .btn-modal-submit:disabled { opacity: 0.6; cursor: not-allowed; }
        .btn-loader {
            width: 14px; height: 14px;
            border: 2px solid rgba(255,255,255,0.3); border-top-color: #fff;
            border-radius: 50%; animation: spin 0.7s linear infinite; display: none;
        }
        .btn-modal-submit.loading .btn-loader { display: block; }
        .btn-modal-submit.loading > span,
        .btn-modal-submit.loading > svg { display: none; }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* ── Generate Kode button ────────────────────────────────── */
        #btnGenerateKode:hover {
            background: #3b82f6 !important;
            color: #fff !important;
            border-color: #3b82f6 !important;
            transform: scale(1.04);
        }
        #btnGenerateKode:active {
            transform: scale(0.97);
        }
        #btnGenerateKode svg {
            transition: transform 0.4s ease;
        }
        #btnGenerateKode:hover svg {
            transform: rotate(180deg);
        }

        /* ── Gudang Toggle Switch ────────────────────────────────── */
        .gudang-toggle-wrap {
            display: flex;
            align-items: center;
            gap: 10px;
            background: var(--bg, #f9fafb);
            border: 1px solid var(--border, #e5e7eb);
            border-radius: 14px;
            padding: 6px 10px 6px 14px;
        }
        .gudang-toggle-label {
            font-size: 13px;
            font-weight: 500;
            color: var(--text-muted, #6b7280);
            white-space: nowrap;
        }
        .toggle-pills {
            display: flex;
            background: var(--border, #e5e7eb);
            border-radius: 9px;
            padding: 3px;
            gap: 3px;
        }
        .toggle-pill {
            padding: 5px 14px;
            border-radius: 7px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            background: transparent;
            color: var(--text-muted, #6b7280);
            transition: all 0.18s;
            white-space: nowrap;
            font-family: 'Outfit', sans-serif;
        }
        .toggle-pill.active {
            background: #ffffff;
            color: var(--blue, #3b82f6);
            box-shadow: 0 1px 6px rgba(0,0,0,0.12);
        }
        .toggle-pill[data-sheet="ppn"].active {
            color: #7c3aed;
        }

        /* ── Gudang Indicator di Form ────────────────────────────── */
        .gudang-indicator {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.04em;
        }
        .gudang-indicator.non-ppn {
            background: rgba(59,130,246,0.1);
            color: #2563eb;
            border: 1px solid rgba(59,130,246,0.2);
        }
        .gudang-indicator.ppn {
            background: rgba(124,58,237,0.1);
            color: #7c3aed;
            border: 1px solid rgba(124,58,237,0.2);
        }
        .indicator-dot {
            width: 7px; height: 7px;
            border-radius: 50%;
            background: currentColor;
            flex-shrink: 0;
        }

        /* ── Gudang header ───────────────────────────────────────── */
        .gudang-header {
            display: flex; align-items: flex-start; justify-content: space-between;
            gap: 16px; margin-bottom: 20px; flex-wrap: wrap;
        }
        .gudang-title {
             font-size: 32px;
            font-weight: 600; color: var(--text-primary); line-height: 1.1;
        }
        .gudang-subtitle { font-size: 14px; color: var(--text-muted); margin-top: 4px; }

        /* ── Alert stok rendah ───────────────────────────────────── */
        .alert-stok {
            display: none;
            background: rgba(251,191,36,0.08);
            border: 1px solid rgba(251,191,36,0.3);
            border-radius: 14px; padding: 14px 18px; margin-bottom: 20px;
            align-items: center; gap: 14px; flex-wrap: wrap;
        }
        .alert-stok.visible { display: flex; }
        .alert-icon {
            width: 36px; height: 36px; border-radius: 10px;
            background: rgba(251,191,36,0.15); display: flex; align-items: center; justify-content: center;
            flex-shrink: 0; color: #f59e0b;
        }
        .alert-text strong { display: block; color: #f59e0b; font-weight: 600; font-size: 14px; }
        .alert-text span   { font-size: 13px; color: var(--text-muted); }
        .alert-tags { display: flex; flex-wrap: wrap; gap: 6px; margin-left: auto; }
        .alert-tag {
            background: rgba(251,191,36,0.12); border: 1px solid rgba(251,191,36,0.25);
            color: #d97706; font-size: 12px; font-weight: 600;
            padding: 3px 10px; border-radius: 6px;
        }

        /* ── Status badges ───────────────────────────────────────── */
        .badge-status {
            display: inline-flex; align-items: center; gap: 5px;
            font-size: 12px; font-weight: 600;
            padding: 4px 10px; border-radius: 8px; white-space: nowrap;
        }
        .badge-status.tersedia { background: rgba(52,211,153,0.1); color: #10b981; border: 1px solid rgba(52,211,153,0.2); }
        .badge-status.rendah   { background: rgba(251,191,36,0.1); color: #d97706; border: 1px solid rgba(251,191,36,0.25); }
        .badge-status.habis    { background: rgba(248,113,113,0.1); color: #ef4444; border: 1px solid rgba(248,113,113,0.2); }
        .stok-rendah-num { color: #d97706; font-weight: 700; }
        .stok-habis-num  { color: #ef4444; font-weight: 700; }

        /* ── Threshold control ───────────────────────────────────── */
        .threshold-control {
            display: flex; align-items: center; gap: 8px;
            background: var(--bg); border: 1px solid var(--border);
            border-radius: 10px; padding: 6px 12px;
            font-size: 13px; color: var(--text-muted);
        }
        .threshold-input {
            width: 48px; background: transparent;
            border: 1px solid var(--border); border-radius: 6px;
            color: var(--text-primary); font-family: 'Outfit', sans-serif;
            font-size: 13px; font-weight: 600; text-align: center;
            padding: 2px 6px; outline: none;
        }
        .threshold-input:focus { border-color: var(--blue); }

        /* ── Auto-update indicator ──────────────────────────────── */
        .autoupdate-indicator {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            font-size: 12px;
            color: var(--text-muted, #6b7280);
            background: var(--bg, #f9fafb);
            border: 1px solid var(--border, #e5e7eb);
            border-radius: 10px;
            padding: 5px 12px;
            font-weight: 500;
            transition: border-color 0.3s, background 0.3s;
            white-space: nowrap;
        }
        .autoupdate-indicator.checking {
            border-color: #93c5fd;
            background: #eff6ff;
            color: #2563eb;
        }
        .autoupdate-indicator.updated {
            border-color: #6ee7b7;
            background: #ecfdf5;
            color: #059669;
        }
        .au-dot {
            width: 7px; height: 7px;
            border-radius: 50%;
            background: #d1d5db;
            flex-shrink: 0;
            transition: background 0.3s;
        }
        .autoupdate-indicator.checking .au-dot {
            background: #3b82f6;
            animation: au-pulse 1s ease-in-out infinite;
        }
        .autoupdate-indicator.updated .au-dot {
            background: #10b981;
        }
        @keyframes au-pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50%       { opacity: 0.4; transform: scale(0.75); }
        }

        /* ── Notification toast ──────────────────────────────────── */
        .notification {
            display: none; align-items: center; gap: 10px;
            padding: 12px 16px; border-radius: 10px;
            font-size: 14px; font-weight: 500;
            position: fixed; top: 20px; right: 20px; z-index: 2000;
            max-width: 380px; box-shadow: 0 8px 24px rgba(0,0,0,0.2);
            animation: notifIn 0.3s ease;
        }
        @keyframes notifIn {
            from { opacity: 0; transform: translateX(20px); }
            to   { opacity: 1; transform: translateX(0); }
        }
        .notification.success { background: rgba(52,211,153,0.12); border: 1px solid rgba(52,211,153,0.3); color: #10b981; }
        .notification.error   { background: rgba(248,113,113,0.12); border: 1px solid rgba(248,113,113,0.3); color: #ef4444; }

        /* ── Clone / Tambah Varian button ───────────────────────── */
        /* Edit button */
        .btn-edit {
            width: 28px; height: 28px; border-radius: 7px;
            border: 1.5px solid var(--blue); background: transparent;
            cursor: pointer; display: flex; align-items: center; justify-content: center;
            color: var(--blue); transition: all 0.15s; padding: 0;
        }
        .btn-edit:hover { background: var(--blue); color: #fff; transform: scale(1.08); }

        /* Tambah Varian button */
        .btn-clone {
            width: 28px; height: 28px; border-radius: 7px;
            border: 1.5px solid #8b5cf6; background: transparent;
            cursor: pointer; display: flex; align-items: center; justify-content: center;
            color: #8b5cf6; transition: all 0.15s; padding: 0;
            font-size: 17px; font-weight: 700; line-height: 1;
        }
        .btn-clone:hover { background: #8b5cf6; color: #fff; transform: scale(1.08); }
        /* Aksi cell — two buttons side by side */
        .aksi-cell { display: flex; align-items: center; gap: 5px; }

        /* ═══════════════════════════════════════════════════════════
           VARIANT GROUP STYLES
        ═══════════════════════════════════════════════════════════ */

        /* ── Group parent row (produk dengan beberapa varian) ── */
        .group-parent-row {
            background: #f8faff;
            cursor: pointer;
            user-select: none;
        }
        .group-parent-row:hover { background: #eff6ff; }
        .group-parent-row td {
            border-top: 2px solid #dbeafe;
            border-bottom: 1px solid #dbeafe;
        }
        .group-parent-row td:first-child {
            border-left: 3px solid #3b82f6;
        }

        /* Chevron toggle button */
        .group-chevron {
            display: inline-flex; align-items: center; justify-content: center;
            width: 20px; height: 20px; border-radius: 5px;
            background: #dbeafe; color: #2563eb;
            transition: transform 0.2s, background 0.15s;
            flex-shrink: 0;
        }
        .group-chevron svg { width: 11px; height: 11px; }
        .group-chevron.open { transform: rotate(90deg); background: #3b82f6; color: #fff; }

        /* Variant count badge */
        .variant-badge {
            display: inline-flex; align-items: center; gap: 4px;
            background: #dbeafe; color: #1d4ed8;
            font-size: 10px; font-weight: 700;
            padding: 2px 8px; border-radius: 99px;
            letter-spacing: 0.03em; white-space: nowrap;
        }
        .variant-badge svg { width: 10px; height: 10px; }

        /* Total stok badge on parent row */
        .total-stok-badge {
            display: inline-flex; align-items: center; gap: 3px;
            font-weight: 700; font-size: 13px; color: #111827;
        }
        .total-stok-badge .unit { font-size: 10px; color: #6b7280; font-weight: 500; }

        /* ── Child rows (varian individual) ── */
        .group-child-row td {
            background: #fdfeff;
            border-bottom: 1px solid #f0f4ff;
        }
        .group-child-row:last-of-type td { border-bottom: 2px solid #dbeafe; }
        .group-child-row:hover td { background: #f0f7ff !important; }

        /* Left indent line for child rows */
        .group-child-row td:first-child {
            border-left: 3px solid #bfdbfe;
            padding-left: 10px;
        }

        /* Sub-number in child row */
        .variant-num {
            display: flex; align-items: center; gap: 5px;
            font-size: 11px; color: #9ca3af;
        }
        .variant-num-dot {
            width: 6px; height: 6px; border-radius: 50%;
            background: #93c5fd; flex-shrink: 0;
        }

        /* "Dimmed" text for columns that are same as parent */
        .td-dim { color: #d1d5db !important; font-size: 11px; }

        /* Highlight columns that differ across variants */
        .td-variant-key {
            font-weight: 600;
        }

        /* Expand-all toggle in toolbar */
        .btn-expand-all {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 6px 12px; border-radius: 8px; font-size: 12px; font-weight: 600;
            font-family: 'Outfit', sans-serif; cursor: pointer; transition: all 0.15s;
            border: 1px solid var(--border); background: #fff; color: var(--text-secondary);
            white-space: nowrap;
        }
        .btn-expand-all:hover { border-color: #3b82f6; color: #3b82f6; background: #eff6ff; }
        .btn-expand-all svg { width: 12px; height: 12px; }

        /* Pagination */
        .pagination-bar {
            display: flex; align-items: center; justify-content: space-between;
            padding: 10px 16px; border-top: 1px solid var(--border);
            background: #fafbfc; font-size: 12px; color: var(--text-muted);
            flex-shrink: 0; flex-wrap: wrap; gap: 6px;
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
        .btn-kasir { width:28px;height:28px;border-radius:7px;border:1.5px solid var(--green-mid);background:var(--green-light);cursor:pointer;display:flex;align-items:center;justify-content:center;color:var(--green);transition:all .15s }
        .btn-kasir:hover:not(:disabled) { background:var(--green);color:#fff;border-color:var(--green) }
        .pg-sep { padding: 0 2px; color: var(--text-muted); font-size: 11px; }
        .pg-size-select {
            height: 28px; padding: 0 6px; border: 1px solid var(--border); border-radius: 6px;
            font-size: 12px; background: #fff; cursor: pointer; color: var(--text-secondary);
        }
        @media (max-width: 768px) {
            .modal-overlay { padding: 0; align-items: flex-end; }
            .modal-box {
                max-width: 100%;
                max-height: 95vh;
                border-radius: 20px 20px 0 0;
                animation: modalSlideUp 0.28s cubic-bezier(0.34, 1.20, 0.64, 1);
            }
            @keyframes modalSlideUp {
                from { opacity: 0; transform: translateY(40px); }
                to   { opacity: 1; transform: translateY(0); }
            }
            .modal-header { border-radius: 20px 20px 0 0; padding: 20px 18px 16px; }
            .modal-footer { border-radius: 0; padding: 16px 18px 28px; }
            .modal-body  { padding: 18px 18px; }
            .modal-body .form-grid { grid-template-columns: 1fr; gap: 12px; }
            .gudang-title { font-size: 26px; }

            /* ── Gudang header: stack vertically ── */
            .gudang-header {
                flex-direction: column !important;
                align-items: stretch !important;
                gap: 10px;
            }
            .gudang-header > div:last-child {
                display: flex !important;
                flex-direction: column !important;
                align-items: stretch !important;
                gap: 8px !important;
            }
            /* Toggle pill fills the toggle wrap */
            .gudang-toggle-wrap {
                width: 100% !important;
                justify-content: space-between;
            }
            .toggle-pills { flex: 1; }
            .toggle-pill  { flex: 1; text-align: center; }

            /* Reduce gap between hamburger row and content */
            .page-header { margin-bottom: 6px; }

            .btn-add-produk { width: 100%; justify-content: center; }
            .table-toolbar { flex-direction: column; align-items: stretch; gap: 10px; }
            .table-toolbar > div:last-child { flex-wrap: wrap; }
            .threshold-control { font-size: 12px; }
            .modal-footer { gap: 8px; }
            .btn-modal-cancel, .btn-modal-submit { flex: 1; justify-content: center; }
            .alert-tags { display: none; }
        }
        @media (max-width: 480px) {
            .modal-body .field-input { font-size: 16px; } /* prevent iOS zoom */
            .gudang-title { font-size: 22px; }
        }
    </style>
</head>
<body>

<!-- Notification toast -->
<div class="notification" id="notification">
    <div class="notif-icon"></div>
    <span class="notif-msg"></span>
</div>

<!-- ════════════════════════════════════════════════════════════════
     MODAL TAMBAH PRODUK
════════════════════════════════════════════════════════════════ -->
<div class="modal-overlay" id="modalOverlay">
    <div class="modal-box">
        <div class="modal-header">
            <div>
                <div class="modal-title" id="modalTitle">Tambah Produk</div>
                <div class="modal-subtitle" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                    <span id="modalSubtitle">Scan barcode atau isi manual untuk menambah data barang</span>
                    <span class="gudang-indicator non-ppn" id="modalGudangIndicator">
                        <span class="indicator-dot"></span>
                        <span id="modalGudangLabel">Gudang Non-PPN</span>
                    </span>
                </div>
            </div>
            <button class="modal-close" id="modalClose" type="button" aria-label="Tutup">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>

        <div class="modal-body">
            <form id="barangForm" autocomplete="off">
                <div class="form-grid">

                    <!-- Kode Barang -->
                    <div class="field-group">
                        <label class="field-label" for="kode_barang">
                            Kode Barang
                            <span class="badge-scan">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="10" height="10">
                                    <path d="M3 9V5a2 2 0 012-2h4M3 15v4a2 2 0 002 2h4M15 3h4a2 2 0 012 2v4M15 21h4a2 2 0 002-2v-4"/>
                                    <line x1="7" y1="8" x2="7" y2="16"/><line x1="10" y1="8" x2="10" y2="16"/>
                                    <line x1="13" y1="8" x2="13" y2="16"/><line x1="16" y1="8" x2="16" y2="16"/>
                                </svg>
                                Scan Barcode
                            </span>
                        </label>
                        <div class="input-wrapper scan-input">
                            <div class="input-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="16" height="16">
                                    <path d="M3 9V5a2 2 0 012-2h4M3 15v4a2 2 0 002 2h4M15 3h4a2 2 0 012 2v4M15 21h4a2 2 0 002-2v-4"/>
                                    <line x1="7" y1="8" x2="7" y2="16"/><line x1="10" y1="8" x2="10" y2="16"/>
                                    <line x1="13" y1="8" x2="13" y2="16"/><line x1="16" y1="8" x2="16" y2="16"/>
                                </svg>
                            </div>
                            <input type="text" id="kode_barang" name="kode_barang" class="field-input"
                                   placeholder="Scan atau ketik kode barang..." data-next="kode_internal">
                            <div class="scan-pulse" id="scanPulse"></div>
                        </div>
                    </div>

                    <!-- Kode Internal -->
                    <div class="field-group">
                        <label class="field-label" for="kode_internal" style="justify-content:space-between;">
                            <span style="display:flex;align-items:center;gap:8px;">
                                Kode Internal
                                <span style="font-size:10px;color:var(--text-muted);font-weight:400;text-transform:none;letter-spacing:0">
                                    🔒 otomatis oleh sistem
                                </span>
                            </span>
                            <button type="button" id="btnGenerateKode"
                                onclick="manualGenerateKode()"
                                title="Generate ulang kode internal"
                                style="display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:7px;border:1.5px solid #3b82f6;background:transparent;color:#3b82f6;font-family:'Outfit',sans-serif;font-size:11px;font-weight:700;cursor:pointer;transition:all 0.15s;letter-spacing:0.03em;white-space:nowrap;text-transform:none;">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="11" height="11">
                                    <polyline points="23 4 23 10 17 10"/>
                                    <polyline points="1 20 1 14 7 14"/>
                                    <path d="M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15"/>
                                </svg>
                                Generate
                            </button>
                        </label>
                        <div class="input-wrapper">
                            <div class="input-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="16" height="16">
                                    <path d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/>
                                </svg>
                            </div>
                            <input type="text" id="kode_internal" name="kode_internal" class="field-input"
                                   placeholder="Akan digenerate otomatis..."
                                   readonly
                                   style="background:#f8fafc;color:#6b7280;cursor:default;border-radius:10px;">
                        </div>
                        <span class="field-preview" id="preview_kode_internal" style="font-family:monospace;color:var(--blue);font-size:11px;font-weight:700;letter-spacing:0.05em"></span>
                    </div>

                    <!-- Nama Produk -->
                    <div class="field-group full-width">
                        <label class="field-label" for="nama_produk">Nama Produk</label>
                        <div class="input-wrapper">
                            <div class="input-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="16" height="16">
                                    <path d="M20 7H4a2 2 0 00-2 2v10a2 2 0 002 2h16a2 2 0 002-2V9a2 2 0 00-2-2z"/>
                                    <path d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2"/>
                                </svg>
                            </div>
                            <input type="text" id="nama_produk" name="nama_produk" class="field-input"
                                   placeholder="Masukkan nama produk..." data-next="nama_mobil">
                        </div>
                    </div>

                    <!-- Nama Mobil -->
                    <div class="field-group full-width">
                        <label class="field-label" for="nama_mobil">Nama Mobil</label>
                        <div class="input-wrapper">
                            <div class="input-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="16" height="16">
                                    <path d="M5 17H3a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v9a2 2 0 01-2 2h-1"/>
                                    <circle cx="7.5" cy="17.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/>
                                </svg>
                            </div>
                            <input type="text" id="nama_mobil" name="nama_mobil" class="field-input"
                                   placeholder="Contoh: Kijang, Avanza, Canter..." data-next="nama_lain">
                        </div>
                    </div>

                    <!-- Nama Lain -->
                    <div class="field-group">
                        <label class="field-label" for="nama_lain">Nama Lain
                            <span style="font-size:10px;color:var(--text-muted);font-weight:400;text-transform:none;letter-spacing:0">alias / sebutan lain produk</span>
                        </label>
                        <div class="input-wrapper">
                            <div class="input-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="16" height="16">
                                    <path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/>
                                </svg>
                            </div>
                            <input type="text" id="nama_lain" name="nama_lain" class="field-input"
                                   placeholder="Nama atau sebutan lain produk ini..." data-next="merk">
                        </div>
                    </div>
                    <!-- Merk -->
                    <div class="field-group">
                        <label class="field-label" for="merk">Merk</label>
                        <div class="input-wrapper">
                            <div class="input-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="16" height="16">
                                    <path d="M20.59 13.41l-7.17 7.17a2 2 0 01-2.83 0L2 12V2h10l8.59 8.59a2 2 0 010 2.82z"/>
                                    <line x1="7" y1="7" x2="7.01" y2="7"/>
                                </svg>
                            </div>
                            <input type="text" id="merk" name="merk" class="field-input"
                                   placeholder="Contoh: Yamaha, Honda, NGK..." data-next="lokasi_rak_input">
                        </div>
                    </div>

                    <!-- Lokasi Rak -->
                    <div class="field-group">
                        <label class="field-label" for="lokasi_rak_input">
                            Lokasi Rak
                            <span style="font-size:11px;color:var(--text-muted);font-weight:400;text-transform:none;letter-spacing:0">AA-00 s/d ZZ-99</span>
                        </label>
                        <div class="input-wrapper custom-select-wrapper" id="lokasiWrapper">
                            <div class="input-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="16" height="16">
                                    <path d="M21 10c0 7-9 13-9 13S3 17 3 10a9 9 0 0118 0z"/>
                                    <circle cx="12" cy="10" r="3"/>
                                </svg>
                            </div>
                            <input type="text" id="lokasi_rak_input" class="field-input"
                                   placeholder="Ketik untuk mencari lokasi rak..."
                                   autocomplete="off" data-next="stok">
                            <input type="hidden" id="lokasi_rak" name="lokasi_rak">
                            <div class="select-arrow">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14">
                                    <polyline points="6 9 12 15 18 9"/>
                                </svg>
                            </div>
                            <div class="dropdown-list" id="lokasiDropdown">
                                <div class="dropdown-hint">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="16" height="16">
                                        <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                                    </svg>
                                    Ketik kode rak (contoh: AB, AB-05, CA-12)
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Stok -->
                    <div class="field-group">
                        <label class="field-label" for="stok">Jumlah Stok</label>
                        <div class="input-wrapper">
                            <div class="input-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="16" height="16">
                                    <line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/>
                                    <line x1="8" y1="18" x2="21" y2="18"/>
                                    <line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/>
                                    <line x1="3" y1="18" x2="3.01" y2="18"/>
                                </svg>
                            </div>
                            <input type="number" id="stok" name="stok" class="field-input"
                                   placeholder="0" min="0" data-next="supplier">
                            <span class="input-suffix">unit</span>
                        </div>
                    </div>

                    <!-- Supplier -->
                    <div class="field-group">
                        <label class="field-label" for="supplier">Supplier</label>
                        <div class="input-wrapper">
                            <div class="input-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="16" height="16">
                                    <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
                                    <circle cx="9" cy="7" r="4"/>
                                    <path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/>
                                </svg>
                            </div>
                            <input type="text" id="supplier" name="supplier" class="field-input"
                                   placeholder="Nama supplier..." data-next="harga_beli_display">
                        </div>
                    </div>

                    <!-- Harga Beli (Pricelist) -->
                    <div class="field-group">
                        <label class="field-label" for="harga_beli_display">Harga Beli <span style="font-size:10px;color:var(--text-muted);font-weight:400;text-transform:none;letter-spacing:0">pricelist asli</span></label>
                        <div class="input-wrapper">
                            <div class="input-icon currency-icon">Rp</div>
                            <input type="text" id="harga_beli_display" class="field-input currency-input"
                                   placeholder="0" data-next="diskon_display"
                                   oninput="updateHargaFinal()">
                            <input type="hidden" id="harga_beli" name="harga_beli">
                        </div>
                        <span class="field-preview" id="preview_beli"></span>
                    </div>

                    <!-- Diskon -->
                    <div class="field-group">
                        <label class="field-label" for="diskon_display">Diskon <span style="font-size:10px;color:var(--text-muted);font-weight:400;text-transform:none;letter-spacing:0">opsional · misal: 20 atau 12.5</span></label>
                        <div class="input-wrapper">
                            <div class="input-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="16" height="16">
                                    <line x1="19" y1="5" x2="5" y2="19"/>
                                    <circle cx="6.5" cy="6.5" r="2.5"/>
                                    <circle cx="17.5" cy="17.5" r="2.5"/>
                                </svg>
                            </div>
                            <input type="text" id="diskon_display" name="diskon" class="field-input"
                                   placeholder="0" inputmode="decimal" data-next="harga_jual_display"
                                   oninput="updateHargaFinal()" onblur="updateHargaFinal()">
                            <span class="input-suffix">%</span>
                        </div>
                        <span class="field-preview" id="preview_diskon"></span>
                    </div>

                    <!-- Harga Final (readonly, hasil diskon) -->
                    <div class="field-group" id="hargaFinalGroup" style="display:none">
                        <label class="field-label" for="harga_final_display">Harga Final <span style="font-size:10px;color:#10b981;font-weight:400;text-transform:none;letter-spacing:0">setelah diskon</span></label>
                        <div class="input-wrapper">
                            <div class="input-icon currency-icon" style="color:#10b981">Rp</div>
                            <input type="text" id="harga_final_display" class="field-input"
                                   readonly style="background:#f0fdf4;color:#10b981;font-weight:700;cursor:default;border-color:#bbf7d0;">
                        </div>
                    </div>

                    <!-- Harga Jual -->
                    <div class="field-group">
                        <label class="field-label" for="harga_jual_display">Harga Jual</label>
                        <div class="input-wrapper">
                            <div class="input-icon currency-icon">Rp</div>
                            <input type="text" id="harga_jual_display" class="field-input currency-input" placeholder="0">
                            <input type="hidden" id="harga_jual" name="harga_jual">
                        </div>
                        <span class="field-preview" id="preview_jual"></span>
                    </div>

                    <!-- Margin -->
                    <div class="field-group full-width">
                        <div class="margin-card" id="marginCard" style="display:none">
                            <div class="margin-item">
                                <span class="margin-label">Margin Keuntungan</span>
                                <span class="margin-value" id="marginValue">-</span>
                            </div>
                            <div class="margin-item">
                                <span class="margin-label">Selisih Harga</span>
                                <span class="margin-value" id="selisihValue">-</span>
                            </div>
                        </div>
                    </div>

                    <!-- Tanggal Beli -->
                    <div class="field-group full-width">
                        <label class="field-label" for="tanggal_beli">
                            Tanggal Beli
                            <span style="font-size:10px;color:var(--text-muted);font-weight:400;text-transform:none;letter-spacing:0">opsional · DD.MM.YYYY atau MM.YYYY</span>
                        </label>
                        <div class="input-wrapper">
                            <div class="input-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="16" height="16">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                    <line x1="16" y1="2" x2="16" y2="6"/>
                                    <line x1="8" y1="2" x2="8" y2="6"/>
                                    <line x1="3" y1="10" x2="21" y2="10"/>
                                </svg>
                            </div>
                            <input type="text" id="tanggal_beli" name="tanggal_beli" class="field-input"
                                   placeholder="DD.MM.YYYY atau MM.YYYY" maxlength="10"
                                   oninput="tanggalRawInput(this)"
                                   onblur="autoFormatTanggal(this)">
                        </div>
                        <span class="field-preview" id="preview_tanggal_beli" style="min-height:16px;"></span>
                    </div>

                </div>
            </form>
        </div>

        <div class="modal-footer">
            <button class="btn-modal-cancel" id="modalCancel" type="button">Batal</button>
            <button class="btn-modal-submit" id="submitBtn" type="button">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="15" height="15">
                    <path d="M22 2L11 13M22 2L15 22 11 13 2 9l20-7z"/>
                </svg>
                <span id="submitBtnLabel">Simpan ke Spreadsheet</span>
                <div class="btn-loader"></div>
            </button>
        </div>
    </div>
</div>

<!-- ════════════════════════════════════════════════════════════════
     APP
════════════════════════════════════════════════════════════════ -->
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
            <a href="gudang.php" class="nav-item active" onclick="closeMobileSidebar()">
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

        <!-- Gudang Header -->
        <div class="gudang-header">
            <div>
                <h1 class="gudang-title" id="gudangTitle">Gudang</h1>
                <p class="gudang-subtitle" id="gudangSubtitle">Memuat data...</p>
            </div>
            <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                <div class="gudang-toggle-wrap">
                    <span class="gudang-toggle-label">Gudang</span>
                    <div class="toggle-pills">
                        <button class="toggle-pill active" data-sheet="non_ppn" id="pillNonPpn">Non-PPN</button>
                        <button class="toggle-pill" data-sheet="ppn" id="pillPpn">PPN</button>
                    </div>
                </div>
                <button class="btn-add-produk" id="btnTambahProduk">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="16" height="16">
                        <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    Tambah Produk
                </button>
            </div>
        </div>

        <!-- Alert Stok Rendah -->
        <div class="alert-stok" id="alertStok">
            <div class="alert-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
                    <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
            </div>
            <div class="alert-text">
                <strong>Peringatan Stok Rendah</strong>
                <span id="alertDesc">-</span>
            </div>
            <div class="alert-tags" id="alertTags"></div>
        </div>

        <!-- Tabel -->
        <div class="table-wrapper">
            <div class="table-toolbar">
                <div style="display:flex; align-items:center; gap:14px;">
                    <span class="table-title">Semua Barang</span>
                    <span class="badge-count" id="rowCount">Memuat...</span>
                </div>
                <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                    <div class="threshold-control">
                        <label for="thresholdInput">Stok Rendah ≤</label>
                        <input type="number" id="thresholdInput" class="threshold-input" value="5" min="0" max="999">
                        <span>unit</span>
                    </div>
                    <div class="search-bar">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"/>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                        </svg>
                        <input type="text" id="searchInput" placeholder="Cari barang...">
                    </div>
                    <button class="refresh-btn" id="refreshBtn">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="23 4 23 10 17 10"/>
                            <path d="M20.49 15a9 9 0 11-2.12-9.36L23 10"/>
                        </svg>
                        Refresh
                    </button>
                    <div class="autoupdate-indicator" id="autoUpdateIndicator" title="Auto-sync setiap 15 detik">
                        <span class="au-dot"></span>
                        <span id="autoUpdateLabel">Sync dalam <b id="autoUpdateCountdown">15</b>d</span>
                    </div>
                    <button class="btn-expand-all" id="btnExpandAll" onclick="toggleAllGroups()">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <polyline points="6 9 12 15 18 9"/>
                        </svg>
                        <span id="expandAllLabel">Buka Semua</span>
                    </button>
                </div>
            </div>

            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Aksi</th>
                            <th>Kode Barang</th>
                            <th>Kode Internal</th>
                            <th>Nama Produk</th>
                            <th>Nama Mobil</th>
                            <th>Nama Lain</th>
                            <th>Merk</th>
                            <th>Lokasi Rak</th>
                            <th>Stok</th>
                            <th>Supplier</th>
                            <th>Harga Beli</th>
                            <th>Diskon</th>
                            <th>Harga Final</th>
                            <th>Harga Jual</th>
                            <th>Tanggal Beli</th>
                            <th>Status</th>
                            <th style="text-align:center" title="Tambah ke Kasir Penjualan">🛒</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        <tr class="loading-row">
                            <td colspan="18">Memuat data dari spreadsheet...</td>
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
                            <option value="200">200</option>
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
// KONFIGURASI
// ================================================================
const SPREADSHEET_ID = '1zQtkYOkwWjPPll_D0u7pJNTYebWGTMl3ilPwnEIWVKQ';

const GUDANG_CONFIG = {
    non_ppn: {
        label:    'Non-PPN',
        title:    'Gudang Non-PPN',
        subtitle: 'produk terdaftar · tanpa PPN',
        apiUrl: `/srm/gudang-read.php?sheet=non_ppn`, // ← INI KURANG KOMA
        indicatorClass: 'non-ppn',
    },
    ppn: {
        label:    'PPN',
        title:    'Gudang PPN',
        subtitle: 'produk terdaftar · termasuk PPN',
        apiUrl:   `/srm/gudang-read.php?sheet=ppn`, // sekalian samakan
        indicatorClass: 'ppn',
    }
};

let activeSheet = 'non_ppn'; // state gudang aktif

// ================================================================
// GUDANG TOGGLE
// ================================================================
function setActiveGudang(sheet) {
    if (activeSheet === sheet) return;
    activeSheet = sheet;

    const cfg = GUDANG_CONFIG[sheet];

    // Update toggle pills
    document.querySelectorAll('.toggle-pill').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.sheet === sheet);
    });

    // Update page title
    document.getElementById('gudangTitle').textContent = cfg.title;

    // Update modal indicator
    const indicator = document.getElementById('modalGudangIndicator');
    indicator.className = 'gudang-indicator ' + cfg.indicatorClass;
    document.getElementById('modalGudangLabel').textContent = cfg.title;

    // Reset lastKnownRowCount supaya checkForNewData tidak false-positive
    _lastKnownRowCount[sheet] = -1;

    // Reload data (akan restart auto-update di dalam loadData)
    loadData();
}

document.querySelectorAll('.toggle-pill').forEach(btn => {
    btn.addEventListener('click', function () {
        setActiveGudang(this.dataset.sheet);
    });
});

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
// MODAL
// ================================================================
function openModal() {
    document.getElementById('modalOverlay').classList.add('open');
    document.body.style.overflow = 'hidden';
    setTimeout(() => document.getElementById('kode_barang').focus(), 200);
}
function closeModal() {
    document.getElementById('modalOverlay').classList.remove('open');
    document.body.style.overflow = '';
    resetForm();
    if (typeof resetEditMode === 'function') resetEditMode();
}

document.getElementById('btnTambahProduk').addEventListener('click', openModal);
document.getElementById('modalClose').addEventListener('click', closeModal);
document.getElementById('modalCancel').addEventListener('click', closeModal);
document.getElementById('modalOverlay').addEventListener('click', function (e) {
    if (e.target === this) closeModal();
});
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && document.getElementById('modalOverlay').classList.contains('open')) closeModal();
});

// ================================================================
// DATA
// ================================================================
let allRows = [];
let lowStockThreshold = 5;

// ── Auto-update state ────────────────────────────────────────────
let _autoUpdateInterval = null;
const AUTO_UPDATE_INTERVAL_MS = 15 * 1000; 
let _lastKnownRowCount = { non_ppn: -1, ppn: -1 }; // -1 = belum pernah load

/**
 * Cek apakah ada data baru di spreadsheet tanpa mengganggu tampilan.
 * Jika jumlah baris berubah → reload otomatis + notifikasi.
 */
async function checkForNewData() {
    const cfg       = GUDANG_CONFIG[activeSheet];
    const CACHE_KEY = 'srm_gudang_' + activeSheet;

    setIndicatorChecking();
    startCountdown(); // reset countdown

    try {
        const res  = await fetch(cfg.apiUrl + '&_t=' + Date.now());
        if (!res.ok) { setIndicatorIdle(AUTO_UPDATE_INTERVAL_MS / 1000); return; }
        const data = await res.json();
        if (!Array.isArray(data)) { setIndicatorIdle(AUTO_UPDATE_INTERVAL_MS / 1000); return; }

        const prevCount = _lastKnownRowCount[activeSheet];

        if (prevCount === -1) {
            _lastKnownRowCount[activeSheet] = data.length;
            setIndicatorIdle(AUTO_UPDATE_INTERVAL_MS / 1000);
            return;
        }

        if (data.length !== prevCount) {
            const diff = data.length - prevCount;
            _lastKnownRowCount[activeSheet] = data.length;

            allRows = data;
            try { localStorage.setItem(CACHE_KEY, JSON.stringify({ data, ts: Date.now() })); } catch(e) {}

            const cfg2 = GUDANG_CONFIG[activeSheet];
            document.getElementById('rowCount').textContent = `${allRows.length} barang`;
            document.getElementById('gudangSubtitle').textContent = `${allRows.length} ${cfg2.subtitle}`;
            renderTable(getFilteredRows());
            updateAlertBanner(allRows);

            setIndicatorUpdated();
            const msg = diff > 0
                ? `🔄 ${diff} data baru ditemukan — tabel diperbarui otomatis`
                : `🔄 ${Math.abs(diff)} data dihapus — tabel diperbarui otomatis`;
            showNotif('success', msg);
        } else {
            setIndicatorIdle(AUTO_UPDATE_INTERVAL_MS / 1000);
        }
    } catch(e) { setIndicatorIdle(AUTO_UPDATE_INTERVAL_MS / 1000); }
}

/** Mulai polling auto-update */
let _countdownInterval = null;

function startAutoUpdate() {
    stopAutoUpdate();
    _autoUpdateInterval = setInterval(checkForNewData, AUTO_UPDATE_INTERVAL_MS);
    startCountdown();
}

function startCountdown() {
    if (_countdownInterval) clearInterval(_countdownInterval);
    const secs = AUTO_UPDATE_INTERVAL_MS / 1000;
    let remaining = secs;
    const el = document.getElementById('autoUpdateCountdown');
    const label = document.getElementById('autoUpdateLabel');
    const indicator = document.getElementById('autoUpdateIndicator');
    if (el) el.textContent = remaining;
    _countdownInterval = setInterval(() => {
        remaining--;
        if (remaining <= 0) remaining = secs;
        if (el) el.textContent = remaining;
        if (label && remaining > 0) {
            label.innerHTML = `Sync dalam <b id="autoUpdateCountdown">${remaining}</b>d`;
        }
    }, 1000);
}

function setIndicatorChecking() {
    const el = document.getElementById('autoUpdateIndicator');
    const label = document.getElementById('autoUpdateLabel');
    if (el) el.classList.add('checking');
    if (label) label.innerHTML = 'Memeriksa...';
}

function setIndicatorIdle(nextSec) {
    const el = document.getElementById('autoUpdateIndicator');
    const label = document.getElementById('autoUpdateLabel');
    if (el) { el.classList.remove('checking'); el.classList.remove('updated'); }
    if (label) label.innerHTML = `Sync dalam <b id="autoUpdateCountdown">${nextSec}</b>d`;
}

function setIndicatorUpdated() {
    const el = document.getElementById('autoUpdateIndicator');
    const label = document.getElementById('autoUpdateLabel');
    if (el) { el.classList.remove('checking'); el.classList.add('updated'); }
    if (label) label.innerHTML = '✓ Diperbarui';
    setTimeout(() => setIndicatorIdle(AUTO_UPDATE_INTERVAL_MS / 1000), 3000);
}

/** Hentikan polling */
function stopAutoUpdate() {
    if (_autoUpdateInterval) { clearInterval(_autoUpdateInterval); _autoUpdateInterval = null; }
    if (_countdownInterval)  { clearInterval(_countdownInterval);  _countdownInterval  = null; }
}

document.getElementById('thresholdInput').addEventListener('input', function () {
    lowStockThreshold = parseInt(this.value) || 0;
    renderTable(getFilteredRows());
    updateAlertBanner(allRows);
});

function formatRp(val) {
    const num = parseInt(String(val).replace(/\D/g, ''), 10);
    if (!num || isNaN(num)) return '-';
    return 'Rp ' + num.toLocaleString('id-ID');
}

function getStokNum(val) {
    const n = parseInt(String(val).replace(/\D/g, ''), 10);
    return isNaN(n) ? 0 : n;
}

function getStatusBadge(stokVal) {
    const n = getStokNum(stokVal);
    if (n === 0)                return `<span class="badge-status habis">● Habis</span>`;
    if (n <= lowStockThreshold) return `<span class="badge-status rendah">⚠ Stok Rendah</span>`;
    return `<span class="badge-status tersedia">✓ Tersedia</span>`;
}

// ================================================================
// PAGINATION STATE
// ================================================================
let currentPage = 0;
let pageSize    = 50;
let displayedRows = [];

function setPage(page) {
    const totalPages = Math.max(1, Math.ceil(displayedRows.length / pageSize));
    currentPage = Math.min(Math.max(0, page), totalPages - 1);
    renderCurrentPage();
    renderPagination();
}

// ================================================================
// GROUP STATE — track which groups are expanded
// ================================================================
const expandedGroups = new Set();
let allGroupsOpen = false;

function safeGroupId(kode) {
    return 'grp_' + (kode || 'x').replace(/[^a-zA-Z0-9]/g, '_');
}

function toggleGroup(gid) {
    if (expandedGroups.has(gid)) {
        expandedGroups.delete(gid);
    } else {
        expandedGroups.add(gid);
    }
    // Toggle chevron + visibility without full re-render for performance
    const chevron = document.getElementById('chev_' + gid);
    if (chevron) chevron.classList.toggle('open', expandedGroups.has(gid));
    document.querySelectorAll('.' + gid).forEach(tr => {
        tr.style.display = expandedGroups.has(gid) ? '' : 'none';
    });
}

function toggleAllGroups() {
    allGroupsOpen = !allGroupsOpen;
    document.getElementById('expandAllLabel').textContent = allGroupsOpen ? 'Tutup Semua' : 'Buka Semua';
    const btn = document.getElementById('btnExpandAll');
    btn.querySelector('svg').style.transform = allGroupsOpen ? 'rotate(180deg)' : '';
    // Update state
    expandedGroups.clear();
    if (allGroupsOpen) {
        // Add all group IDs currently visible
        document.querySelectorAll('[data-group-id]').forEach(el => {
            expandedGroups.add(el.dataset.groupId);
        });
    }
    // Update UI
    document.querySelectorAll('.group-chevron').forEach(chev => {
        chev.classList.toggle('open', allGroupsOpen);
    });
    document.querySelectorAll('tr[data-child-of]').forEach(tr => {
        tr.style.display = allGroupsOpen ? '' : 'none';
    });
}

function renderCurrentPage() {
    const start = currentPage * pageSize;
    const slice = displayedRows.slice(start, start + pageSize);
    const tbody = document.getElementById('tableBody');

    if (!slice.length && !displayedRows.length) {
        tbody.innerHTML = `<tr><td colspan="18">
            <div class="empty-state">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/>
                    <rect x="9" y="3" width="6" height="4" rx="1"/>
                </svg>
                <div>Belum ada data barang</div>
            </div>
        </td></tr>`;
        return;
    }

    // ── Grup baris berdasarkan kode_barang ──────────────────────────
    const groupOrder = [];
    const groupMap   = Object.create(null);
    slice.forEach(row => {
        const kb = String(row['kode_barang'] || row[0] || '__NOCODE__');
        if (!groupMap[kb]) {
            groupMap[kb] = [];
            groupOrder.push(kb);
        }
        groupMap[kb].push(row);
    });

    const DASH = `<span style="color:#e5e7eb">—</span>`;
    let html = '';
    let rowNum = currentPage * pageSize + 1;

    groupOrder.forEach(kb => {
        const rows = groupMap[kb];

        if (rows.length === 1) {
            // ── Single row — tampilan normal ─────────────────────────
            const row      = rows[0];
            const globalIdx = allRows.indexOf(row);
            const stok     = row['stok'] || row[7] || '0';
            const stokNum  = getStokNum(stok);
            const stokHtml = stokNum === 0
                ? `<span class="stok-habis-num">0</span>`
                : stokNum <= lowStockThreshold
                    ? `<span class="stok-rendah-num">${stokNum} ⚠</span>`
                    : stokNum;
            html += `<tr>
                <td style="color:var(--text-muted);font-size:12px">${rowNum}</td>
                <td>
                    <div class="aksi-cell">
                        <button class="btn-edit" title="Edit" onclick="openEditModal(${globalIdx})">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13">
                                <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                                <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                            </svg>
                        </button>
                        <button class="btn-clone" title="Tambah Varian" onclick="cloneRow(${encodeRow(row)})">+</button>
                    </div>
                </td>
                <td class="td-code">${row['kode_barang']   || row[0] || '-'}</td>
                <td class="td-code" style="color:var(--text-muted)">${row['kode_internal'] || row[1] || '-'}</td>
                <td class="td-name">${row['nama_produk']   || row[3] || '-'}</td>
                <td>${row['nama_mobil']    || row[4] || '-'}</td>
                <td>${row['nama_lain']      || row[5] || '-'}</td>
                <td>${row['merk']          || row[2] || '-'}</td>
                <td class="td-code" style="color:var(--blue)">${row['lokasi_rak'] || row[6] || '-'}</td>
                <td class="td-stok">${stokHtml}</td>
                <td>${row['supplier']      || row[8] || '-'}</td>
                <td class="td-price">${formatRp(row['harga_beli']  || row[9])}</td>
                <td style="font-size:12px;color:#f59e0b;font-weight:600;">${(row['diskon'] || row[14]) ? (row['diskon'] || row[14]) + '%' : '—'}</td>
                <td class="td-price" style="color:#10b981;font-weight:700;">${(row['harga_final'] || row[15]) ? formatRp(row['harga_final'] || row[15]) : '—'}</td>
                <td class="td-price" style="color:var(--blue)">${formatRp(row['harga_jual'] || row[10])}</td>
                <td style="font-size:12px;color:var(--text-muted)">${row['tanggal_beli'] || row[11] || '-'}</td>
                <td>${getStatusBadge(stok)}</td>
                <td style="text-align:center">
                    <button class="btn-kasir" title="Tambah ke Kasir"
                        onclick="addToKasir(${encodeRow(row)})"
                        ${parseInt(stok) <= 0 ? 'disabled style="opacity:.35;cursor:not-allowed"' : ''}>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 001.95-1.56l1.65-8.44H6"/></svg>
                    </button>
                </td>
            </tr>`;
            rowNum++;
        } else {
            // ── Group row — beberapa varian ──────────────────────────
            const gid      = safeGroupId(kb);
            const isOpen   = expandedGroups.has(gid);
            const firstRow = rows[0];

            // Hitung total stok & status
            const totalStok = rows.reduce((s, r) => s + getStokNum(r['stok'] || r[7] || 0), 0);
            const anyRendah = rows.some(r => { const n = getStokNum(r['stok']||r[7]); return n > 0 && n <= lowStockThreshold; });
            const anyHabis  = rows.some(r => getStokNum(r['stok']||r[7]) === 0);
            let groupStatusHtml;
            if (totalStok === 0) groupStatusHtml = `<span class="badge-status habis">● Habis</span>`;
            else if (anyHabis || anyRendah) groupStatusHtml = `<span class="badge-status rendah">⚠ Ada Stok Rendah</span>`;
            else groupStatusHtml = `<span class="badge-status tersedia">✓ Tersedia</span>`;

            const totalStokHtml = totalStok === 0
                ? `<span class="stok-habis-num">0</span>`
                : anyRendah || anyHabis
                    ? `<span class="stok-rendah-num">${totalStok} ⚠</span>`
                    : `<span class="total-stok-badge">${totalStok} <span class="unit">total</span></span>`;

            // ── Parent row ──
            html += `<tr class="group-parent-row" onclick="toggleGroup('${gid}')" data-group-id="${gid}">
                <td style="font-size:12px;color:var(--text-muted)">${rowNum}</td>
                <td>
                    <div class="aksi-cell">
                        <span class="group-chevron${isOpen ? ' open' : ''}" id="chev_${gid}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <polyline points="9 18 15 12 9 6"/>
                            </svg>
                        </span>
                        <button class="btn-clone" title="Tambah Varian baru" onclick="event.stopPropagation();cloneRow(${encodeRow(firstRow)})">+</button>
                    </div>
                </td>
                <td class="td-code" style="font-weight:700">
                    <div style="display:flex;align-items:center;gap:7px;flex-wrap:wrap;">
                        ${firstRow['kode_barang'] || firstRow[0] || '-'}
                        <span class="variant-badge">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/>
                            </svg>
                            ${rows.length} varian
                        </span>
                    </div>
                </td>
                <td>${DASH}</td>
                <td class="td-name" style="font-weight:600">${firstRow['nama_produk'] || firstRow[3] || '-'}</td>
                <td>${firstRow['nama_mobil']  || firstRow[4] || '-'}</td>
                <td>${firstRow['nama_lain']    || firstRow[5] || '-'}</td>
                <td>${firstRow['merk']        || firstRow[2] || '-'}</td>
                <td>${DASH}</td>
                <td class="td-stok">${totalStokHtml}</td>
                <td>${DASH}</td>
                <td>${DASH}</td>
                <td>${DASH}</td>
                <td>${DASH}</td>
                <td>${DASH}</td>
                <td>${DASH}</td>
                <td>${groupStatusHtml}</td>
                <td>${DASH}</td>
            </tr>`;

            // ── Child rows (tiap varian) ──
            rows.forEach((row, vi) => {
                const globalIdx = allRows.indexOf(row);
                const stok      = row['stok'] || row[7] || '0';
                const stokNum   = getStokNum(stok);
                const stokHtml  = stokNum === 0
                    ? `<span class="stok-habis-num">0</span>`
                    : stokNum <= lowStockThreshold
                        ? `<span class="stok-rendah-num">${stokNum} ⚠</span>`
                        : stokNum;
                html += `<tr class="group-child-row ${gid}" data-child-of="${gid}" style="${isOpen ? '' : 'display:none'}">
                    <td>
                        <div class="variant-num">
                            <span class="variant-num-dot"></span>
                            <span>${vi + 1}</span>
                        </div>
                    </td>
                    <td>
                        <div class="aksi-cell">
                            <button class="btn-edit" title="Edit varian ini" onclick="openEditModal(${globalIdx})">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13">
                                    <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                                    <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                </svg>
                            </button>
                            <button class="btn-clone" title="Tambah Varian lagi" onclick="cloneRow(${encodeRow(row)})">+</button>
                        </div>
                    </td>
                    <td class="td-dim">${row['kode_barang'] || row[0] || '-'}</td>
                    <td class="td-code td-variant-key" style="color:#7c3aed">${row['kode_internal'] || row[1] || '-'}</td>
                    <td class="td-dim">—</td>
                    <td class="td-dim">—</td>
                    <td class="td-dim">—</td>
                    <td class="td-dim">—</td>
                    <td class="td-code td-variant-key" style="color:var(--blue)">${row['lokasi_rak'] || row[6] || '-'}</td>
                    <td class="td-stok td-variant-key">${stokHtml}</td>
                    <td class="td-variant-key" style="font-size:12px">${row['supplier'] || row[8] || '-'}</td>
                    <td class="td-price td-variant-key">${formatRp(row['harga_beli']  || row[9])}</td>
                    <td style="font-size:12px;color:#f59e0b;font-weight:600;">${(row['diskon'] || row[14]) ? (row['diskon'] || row[14]) + '%' : '—'}</td>
                    <td class="td-price td-variant-key" style="color:#10b981;font-weight:700;">${(row['harga_final'] || row[15]) ? formatRp(row['harga_final'] || row[15]) : '—'}</td>
                    <td class="td-price td-variant-key" style="color:var(--blue)">${formatRp(row['harga_jual'] || row[10])}</td>
                    <td style="font-size:12px;color:var(--text-muted)">${row['tanggal_beli'] || row[11] || '-'}</td>
                    <td>${getStatusBadge(stok)}</td>
                    <td style="text-align:center">
                        <button class="btn-kasir" title="Tambah ke Kasir"
                            onclick="addToKasir(${encodeRow(row)})"
                            ${parseInt(stok) <= 0 ? 'disabled style="opacity:.35;cursor:not-allowed"' : ''}>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 001.95-1.56l1.65-8.44H6"/></svg>
                        </button>
                    </td>
                </tr>`;
            });
            rowNum += rows.length;
        }
    });

    tbody.innerHTML = html;
}

function renderPagination() {
    const total      = displayedRows.length;
    const totalPages = Math.max(1, Math.ceil(total / pageSize));
    const start      = currentPage * pageSize + 1;
    const end        = Math.min((currentPage + 1) * pageSize, total);
    document.getElementById('pgInfo').textContent = total
        ? `${start}–${end} dari ${total} barang`
        : 'Tidak ada data';

    const ctrl = document.getElementById('pgControls');
    let html = `<button class="pg-btn" onclick="setPage(0)" ${currentPage===0?'disabled':''}>«</button>
                <button class="pg-btn" onclick="setPage(${currentPage-1})" ${currentPage===0?'disabled':''}>‹</button>`;
    // page number buttons (max 5 visible)
    const range = 2;
    for (let p = 0; p < totalPages; p++) {
        if (p === 0 || p === totalPages-1 || Math.abs(p - currentPage) <= range) {
            html += `<button class="pg-btn${p===currentPage?' active':''}" onclick="setPage(${p})">${p+1}</button>`;
        } else if (Math.abs(p - currentPage) === range + 1) {
            html += `<span class="pg-sep">…</span>`;
        }
    }
    html += `<button class="pg-btn" onclick="setPage(${currentPage+1})" ${currentPage>=totalPages-1?'disabled':''}>›</button>
             <button class="pg-btn" onclick="setPage(${totalPages-1})" ${currentPage>=totalPages-1?'disabled':''}>»</button>`;
    ctrl.innerHTML = html;
}

function renderTable(rows) {
    displayedRows = rows || [];
    currentPage = 0;
    renderCurrentPage();
    renderPagination();
}

function updateAlertBanner(rows) {
    const alertEl = document.getElementById('alertStok');
    const desc    = document.getElementById('alertDesc');
    const tags    = document.getElementById('alertTags');

    const rendah = rows.filter(r => { const n = getStokNum(r['stok'] || r[7]); return n > 0 && n <= lowStockThreshold; });
    const habis  = rows.filter(r => getStokNum(r['stok'] || r[7]) === 0);
    const total  = rendah.length + habis.length;

    if (total === 0) { alertEl.classList.remove('visible'); return; }

    alertEl.classList.add('visible');
    const parts = [];
    if (rendah.length) parts.push(`${rendah.length} produk stok ≤ ${lowStockThreshold}`);
    if (habis.length)  parts.push(`${habis.length} produk habis stok`);
    desc.textContent = parts.join(' · ');

    const sample = [...habis, ...rendah].slice(0, 5);
    tags.innerHTML = sample.map(r => {
        const kode = r['kode_barang'] || r[0] || '-';
        const n    = getStokNum(r['stok'] || r[7]);
        return `<span class="alert-tag">${kode}: ${n}</span>`;
    }).join('');
}

function norm(s) { return (s || '').toLowerCase().replace(/-/g, ''); }

/**
 * Multi-keyword search: setiap kata dalam query harus cocok di setidaknya
 * satu kolom (kode_barang, kode_internal, nama_produk, merk, nama_mobil, supplier, nama_lain).
 * Contoh: "ngk avanza rem" → cocok jika masing-masing kata ada di field manapun.
 */
function getFilteredRows() {
    const q = document.getElementById('searchInput').value.toLowerCase().trim();
    if (!q) return allRows;

    const words = q.split(/\s+/).filter(w => w.length > 0);

    // Field-field yang dicari (nama key dari API + fallback index)
    const getFields = row => [
        row['kode_barang']   || row[0],
        row['kode_internal'] || row[1],
        row['merk']          || row[2],
        row['nama_produk']   || row[3],
        row['nama_mobil']    || row[4],
        row['nama_lain']      || row[5],
        row['supplier']      || row[8],
    ].map(f => String(f || '').toLowerCase());

    return allRows.filter(row => {
        const fields     = getFields(row);
        const fieldsNorm = fields.map(f => f.replace(/-/g, ''));
        return words.every(w => {
            const wNorm = w.replace(/-/g, '');
            return fields.some(f => f.includes(w)) ||
                   fieldsNorm.some(f => f.includes(wNorm));
        });
    });
}

// ================================================================
// LOAD DATA
// ================================================================
async function loadData(forceRefresh = false) {
    const tbody = document.getElementById('tableBody');
    const count = document.getElementById('rowCount');
    const cfg   = GUDANG_CONFIG[activeSheet];

    const CACHE_KEY = 'srm_gudang_' + activeSheet;
    const CACHE_TTL = 5 * 60 * 1000; // 5 menit
    if (!forceRefresh) {
        try {
            const cached = JSON.parse(localStorage.getItem(CACHE_KEY) || 'null');
            if (cached && (Date.now() - cached.ts < CACHE_TTL)) {
                allRows = cached.data;
                _lastKnownRowCount[activeSheet] = allRows.length;
                count.textContent = `${allRows.length} barang`;
                document.getElementById('gudangSubtitle').textContent = `${allRows.length} ${cfg.subtitle}`;
                renderTable(allRows);
                updateAlertBanner(allRows);
                startAutoUpdate(); // mulai polling setelah load dari cache
                return;
            }
        } catch(e) {}
    }

    tbody.innerHTML = '<tr class="loading-row"><td colspan="18">Memuat data dari Spreadsheet...</td></tr>';
    count.textContent = 'Memuat...';
    document.getElementById('gudangSubtitle').textContent = 'Memuat data...';

    try {
        const res  = await fetch(cfg.apiUrl + '&_t=' + Date.now());
        if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
        const data = await res.json();

        if (data && Array.isArray(data) && data.length > 0) {
            allRows = data;
            _lastKnownRowCount[activeSheet] = allRows.length;
            count.textContent = `${allRows.length} barang`;
            document.getElementById('gudangSubtitle').textContent = `${allRows.length} ${cfg.subtitle}`;
            renderTable(allRows);
            updateAlertBanner(allRows);
            try { localStorage.setItem(CACHE_KEY, JSON.stringify({ data: allRows, ts: Date.now() })); } catch(e) {}
        } else {
            allRows = [];
            _lastKnownRowCount[activeSheet] = 0;
            tbody.innerHTML = `<tr class="loading-row"><td colspan="18">Belum ada data atau spreadsheet kosong</td></tr>`;
            count.textContent = 'Kosong';
            document.getElementById('gudangSubtitle').textContent = `0 ${cfg.subtitle}`;
        }
    } catch (err) {
        let msg = err.message;
        if (msg.includes('Failed to fetch')) msg = 'Gagal terhubung ke API. Cek koneksi internet.';
        tbody.innerHTML = `<tr class="loading-row"><td colspan="18" style="color:var(--red)">⚠️ ${msg}</td></tr>`;
        count.textContent = 'Error';
    } finally {
        startAutoUpdate(); // pastikan polling selalu berjalan setelah loadData
    }
}

// ================================================================
// EVENT LISTENERS
// ================================================================
document.getElementById('refreshBtn').addEventListener('click', function () {
    loadData(true); // paksa fetch ulang, bypass cache
    this.style.opacity = '0.5';
    setTimeout(() => this.style.opacity = '1', 300);
});

document.getElementById('pgSizeSelect').addEventListener('change', function () {
    pageSize = parseInt(this.value);
    setPage(0);
});

document.getElementById('searchInput').addEventListener('input', function () {
    const filtered = getFilteredRows();
    renderTable(filtered);
    document.getElementById('rowCount').textContent =
        this.value.trim() ? `${filtered.length} hasil` : `${allRows.length} barang`;
});

// ================================================================
// EDIT MODE
// ================================================================
let isEditMode  = false;
let editRowIndex = -1;
let isCloneMode = false;   // ← flag khusus mode Tambah Varian

function openEditModal(globalIdx) {
    const row = allRows[globalIdx];
    if (!row) return;
    isEditMode   = true;
    editRowIndex = globalIdx;

    document.getElementById('modalTitle').textContent   = 'Edit Produk';
    document.getElementById('modalSubtitle').textContent = 'Ubah data produk lalu simpan';
    document.getElementById('submitBtnLabel').textContent = 'Simpan Perubahan';

    // Fill all fields
    document.getElementById('kode_barang').value           = row['kode_barang']   || row[0]  || '';
    document.getElementById('kode_internal').value         = row['kode_internal'] || row[1]  || '';
    document.getElementById('merk').value                  = row['merk']          || row[2]  || '';
    document.getElementById('nama_produk').value           = row['nama_produk']   || row[3]  || '';
    document.getElementById('nama_mobil').value            = row['nama_mobil']    || row[4]  || '';
    document.getElementById('nama_lain').value              = row['nama_lain']      || row[5]  || '';
    document.getElementById('lokasi_rak_input').value      = row['lokasi_rak']    || row[6]  || '';
    document.getElementById('stok').value                  = row['stok']          || row[7]  || '';
    document.getElementById('supplier').value              = row['supplier']      || row[8]  || '';
    document.getElementById('tanggal_beli').value          = row['tanggal_beli']  || row[11] || '';

    // Currency fields
    const hargaBeli = parseInt(String(row['harga_beli'] || row[9] || '0').replace(/\D/g,'')) || 0;
    const hargaJual = parseInt(String(row['harga_jual'] || row[10] || '0').replace(/\D/g,'')) || 0;
    document.getElementById('harga_beli').value         = hargaBeli || '';
    document.getElementById('harga_beli_display').value = hargaBeli ? hargaBeli.toLocaleString('id-ID') : '';
    document.getElementById('harga_jual').value         = hargaJual || '';
    document.getElementById('harga_jual_display').value = hargaJual ? hargaJual.toLocaleString('id-ID') : '';

    // Diskon & harga final
    const diskonVal = String(row['diskon'] || row[14] || '');
    document.getElementById('diskon_display').value = diskonVal;
    updateHargaFinal();

    if (typeof updateMargin === 'function') updateMargin();

    document.getElementById('modalOverlay').classList.add('open');
    setTimeout(() => document.getElementById('kode_barang').focus(), 150);
}

function resetEditMode() {
    isEditMode   = false;
    editRowIndex = -1;
    isCloneMode  = false;
    document.getElementById('modalTitle').textContent    = 'Tambah Produk';
    document.getElementById('modalSubtitle').textContent = 'Scan barcode atau isi manual untuk menambah data barang';
    document.getElementById('submitBtnLabel').textContent = 'Simpan ke Spreadsheet';
    const preview = document.getElementById('preview_kode_internal');
    if (preview) preview.innerHTML = '';
}

// ================================================================
// SUBMIT
// ================================================================
document.getElementById('submitBtn').addEventListener('click', async function () {
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

    // Validasi format tanggal_beli (opsional — boleh kosong)
    const tanggalRaw = document.getElementById('tanggal_beli').value.trim();
    if (tanggalRaw) {
        const validFull  = /^\d{2}\.\d{2}\.\d{4}$/.test(tanggalRaw); // DD.MM.YYYY
        const validMonth = /^\d{2}\.\d{4}$/.test(tanggalRaw);         // MM.YYYY
        if (!validFull && !validMonth) {
            showNotif('error', 'Format tanggal tidak valid. Gunakan DD.MM.YYYY atau MM.YYYY.');
            document.getElementById('tanggal_beli').focus();
            return;
        }
    }

    const payload = {
        action:        isEditMode ? 'update' : 'append',
        row_index:     isEditMode ? editRowIndex : undefined,
        sheet_name:    activeSheet,
        kode_barang:   kodeBarang,
        kode_internal: document.getElementById('kode_internal').value.trim(),
        nama_produk:   namaProduk,
        nama_mobil:    document.getElementById('nama_mobil').value.trim(),
        nama_lain:      document.getElementById('nama_lain').value.trim(),
        merk:          document.getElementById('merk').value.trim(),
        lokasi_rak:    document.getElementById('lokasi_rak').value.trim()
                       || document.getElementById('lokasi_rak_input').value.trim(),
        stok:          document.getElementById('stok').value.trim() || '0',
        supplier:      document.getElementById('supplier').value.trim(),
        harga_beli:    document.getElementById('harga_beli').value.trim() || '0',
        diskon:        document.getElementById('diskon_display').value.trim() || '',
        harga_jual:    document.getElementById('harga_jual').value.trim() || '0',
        tanggal_beli:  document.getElementById('tanggal_beli').value.trim(),
    };

    this.classList.add('loading');
    this.disabled = true;

    try {
        const response = await fetch('submit.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const result = await response.json();
        if (result.success) {
            const gudangLabel = GUDANG_CONFIG[activeSheet].label;
            const msg = isEditMode
                ? `✓ "${payload.nama_produk}" berhasil diperbarui!`
                : `✓ "${payload.nama_produk}" disimpan ke Gudang ${gudangLabel}!`;
            showNotif('success', msg);
            closeModal();
            loadData();
        } else {
            showNotif('error', 'Gagal: ' + (result.message || 'Terjadi kesalahan'));
        }
    } catch (err) {
        showNotif('error', 'Koneksi error: ' + err.message);
    } finally {
        this.classList.remove('loading');
        this.disabled = false;
    }
});

// ================================================================
// KODE INTERNAL AUTO-GENERATOR
// Format normal  : D[HARGA_ENCODED](KENARI)-[MERKHURF][NNN]-[SUPPLIER]
// Format no-harga: D(KENARI)-[MERKHURF][NNN]-[SUPPLIER]
//   → Dipakai jika harga beli kosong tapi diskon diisi
// Peta digit: 1=K 2=E 3=N 4=A 5=R 6=I 7=B 8=O 9=X 0=Y
// Contoh: DKRYYY-N001-AHM / DKRYYY(EY)-N002-FDR / D(EY)-N003-AHM
// NNN = urut otomatis, membaca data yang sudah ada agar tidak duplikat
// Data selalu di-sync ke spreadsheet terbaru sebelum generate.
// ================================================================
// ================================================================
// UPDATE HARGA FINAL (kalkulasi diskon live)
// ================================================================
function updateHargaFinal() {
    const harga   = parseInt((document.getElementById('harga_beli').value || '').replace(/\D/g, '')) || 0;
    const diskonRaw = (document.getElementById('diskon_display').value || '').trim();
    const diskon  = parseFloat(diskonRaw) || 0;
    const preview = document.getElementById('preview_diskon');
    const group   = document.getElementById('hargaFinalGroup');
    const finalEl = document.getElementById('harga_final_display');

    if (diskon > 0 && harga > 0) {
        const hargaFinal = Math.round(harga * (1 - diskon / 100));
        const selisih    = harga - hargaFinal;
        group.style.display = '';
        finalEl.value       = hargaFinal.toLocaleString('id-ID');
        if (preview) {
            preview.innerHTML = `<span style="color:#10b981;font-size:11px;font-weight:600;">
                ✓ Hemat Rp ${selisih.toLocaleString('id-ID')}
            </span>`;
        }
    } else if (diskon > 0 && harga === 0) {
        // Mode tanpa harga beli: diskon tetap valid, harga final tidak dihitung otomatis
        group.style.display = 'none';
        finalEl.value       = '';
        if (preview) {
            preview.innerHTML = `<span style="color:#10b981;font-size:11px;font-weight:600;">✓ Diskon ${diskon}% — tanpa harga beli</span>`;
        }
    } else {
        group.style.display = 'none';
        finalEl.value       = '';
        if (preview) preview.textContent = '';
    }
}

const DIGIT_MAP = {'1':'K','2':'E','3':'N','4':'A','5':'R','6':'I','7':'B','8':'O','9':'X','0':'Y'};

function encodeHarga(harga) {
    return String(harga).replace(/\D/g, '').split('').map(d => DIGIT_MAP[d] || d).join('').replace(/\s/g, '');
}

function supplierInisial(supplier) {
    if (!supplier || !supplier.trim()) return '';
    const s = supplier.trim();
    if (s.replace(/\s+/g, '').length <= 3) return s.replace(/\s+/g, '').toUpperCase();
    return s.toUpperCase().split(/\s+/).map(w => w[0]).join('');
}

function getNextKodeCounter(merkHuruf) {
    // Cocokkan 3 ATAU 4 digit agar backward-compatible saat sudah >999
    const pattern = new RegExp(`-${merkHuruf}(\\d{3,4})(?:-|$)`);
    const used = new Set();
    (allRows || []).forEach(row => {
        const ki = (row['kode_internal'] || row[1] || '').trim();
        if (!ki) return;
        const m = ki.match(pattern);
        if (m) used.add(parseInt(m[1], 10));
    });
    // Cari nomor terkecil yang belum dipakai mulai dari 1
    let n = 1;
    while (used.has(n)) n++;
    return n;
}

/**
 * Fetch data terbaru dari spreadsheet dan perbarui allRows.
 * Dipakai sebelum generate kode agar tidak ada duplikat nomor urut.
 * Returns true jika berhasil (allRows diperbarui), false jika gagal (pakai data lama).
 */
async function syncLatestRowsForGenerate() {
    const cfg = GUDANG_CONFIG[activeSheet];
    const CACHE_KEY = 'srm_gudang_' + activeSheet;
    try {
        const res = await fetch(cfg.apiUrl + '&_t=' + Date.now());
        if (!res.ok) return false;
        const data = await res.json();
        if (!Array.isArray(data)) return false;
        allRows = data;
        _lastKnownRowCount[activeSheet] = data.length;
        try { localStorage.setItem(CACHE_KEY, JSON.stringify({ data, ts: Date.now() })); } catch(e) {}
        return true;
    } catch(e) { return false; }
}

function generateKodeInternal() {
    const merk      = document.getElementById('merk').value.trim();
    const supplier  = document.getElementById('supplier').value.trim();
    const diskonRaw = (document.getElementById('diskon_display').value || '').trim();
    // Ambil harga beli dari hidden input (angka murni), fallback ke display
    const hargaRaw  = (document.getElementById('harga_beli').value || '').replace(/\D/g, '')
                   || (document.getElementById('harga_beli_display').value || '').replace(/\D/g, '')
                   || '';

    // 1 huruf inisial merk (fallback 'X')
    const merkHuruf = merk
        ? (merk.toUpperCase().replace(/[^A-Z]/g, '')[0] || 'X')
        : 'X';

    const nextNum = getNextKodeCounter(merkHuruf);
    const num     = nextNum >= 1000 ? String(nextNum) : String(nextNum).padStart(3, '0');
    const sup     = supplierInisial(supplier);

    let result;

    if (!hargaRaw) {
        // ── Mode tanpa harga beli: hanya D(KENARI)-... ──────────────
        // Wajib ada diskon di mode ini (sudah divalidasi sebelum generate)
        const diskonDigits = diskonRaw.replace('.', '').replace(/[^0-9]/g, '');
        const kenari = diskonDigits.split('').map(d => DIGIT_MAP[d] || d).join('');
        result = `D(${kenari})`.replace(/\s/g, '');
    } else {
        // ── Mode normal: D[HARGA_ENCODED] ───────────────────────────
        const encoded = encodeHarga(hargaRaw);
        result = `D${encoded}`.replace(/\s/g, '');
        // Kode KENARI dari diskon disisipkan setelah segmen pertama
        if (diskonRaw && parseFloat(diskonRaw) > 0) {
            const diskonDigits = diskonRaw.replace('.', '').replace(/[^0-9]/g, '');
            const kenari = diskonDigits.split('').map(d => DIGIT_MAP[d] || d).join('');
            result += `(${kenari})`;
        }
    }

    result += `-${merkHuruf.replace(/\s/g, '')}${num}`;
    if (sup) result += `-${sup.replace(/\s/g, '')}`;
    return result.replace(/\s/g, ''); // final safety: hapus semua spasi
}

function applyGeneratedKode(val) {
    const input   = document.getElementById('kode_internal');
    const preview = document.getElementById('preview_kode_internal');
    input.value   = val;
    // Tampilkan kode yang digenerate di preview dengan animasi green
    preview.innerHTML = `<span style="color:#10b981;font-family:monospace;font-size:11px;font-weight:700;letter-spacing:0.05em;">
        ✓ ${val}
    </span>`;
    // Flash border hijau singkat di field readonly
    input.style.borderColor = '#10b981';
    input.style.boxShadow   = '0 0 0 3px rgba(16,185,129,0.18)';
    setTimeout(() => {
        input.style.borderColor = '';
        input.style.boxShadow   = '';
    }, 800);
}

// ================================================================
// KODE INTERNAL — GENERATE MANUAL (klik tombol)
// ================================================================
async function manualGenerateKode() {
    const merk     = document.getElementById('merk').value.trim();
    const harga    = document.getElementById('harga_beli').value.trim();
    const supplier = document.getElementById('supplier').value.trim();
    const preview  = document.getElementById('preview_kode_internal');
    const btn      = document.getElementById('btnGenerateKode');

    // Validasi syarat minimal
    const diskon = (document.getElementById('diskon_display').value || '').trim();
    const bisaGenerate = !!harga || (!!diskon && parseFloat(diskon) > 0);
    const kurang = [];
    if (isCloneMode) {
        if (!supplier)     kurang.push('supplier');
        if (!bisaGenerate) kurang.push('harga beli atau diskon');
    } else {
        // Merk opsional — jika kosong huruf inisial fallback ke 'X'
        // Bisa generate jika ada harga beli ATAU ada diskon
        if (!bisaGenerate) kurang.push('harga beli atau diskon');
    }

    if (kurang.length > 0) {
        preview.innerHTML = `<span style="color:#ef4444;font-size:11px;font-weight:600;">
            ⚠ Isi dulu: <b>${kurang.join(' &amp; ')}</b>
        </span>`;
        // Getar tombol sebagai feedback
        if (btn) {
            btn.style.borderColor = '#ef4444';
            btn.style.color       = '#ef4444';
            setTimeout(() => {
                btn.style.borderColor = '#3b82f6';
                btn.style.color       = '#3b82f6';
            }, 900);
        }
        return;
    }

    // Animasi spin pada ikon tombol
    if (btn) {
        btn.disabled = true;
        btn.style.opacity = '0.7';
    }

    // ── Sync data terbaru sebelum generate ──────────────────────
    preview.innerHTML = `<span style="color:#6b7280;font-size:11px;">⏳ Memeriksa data terbaru...</span>`;
    await syncLatestRowsForGenerate();
    // ────────────────────────────────────────────────────────────

    if (btn) {
        btn.disabled = false;
        btn.style.opacity = '';
    }

    applyGeneratedKode(generateKodeInternal());
}

// ================================================================
// KASIR — Tambah ke Keranjang Kasir Penjualan
// ================================================================
function encodeRow(row) {
    const obj = {
        kode_barang:   row['kode_barang']   || row[0] || '',
        kode_internal: row['kode_internal'] || row[1] || '',
        merk:          row['merk']          || row[2] || '',
        nama_produk:   row['nama_produk']   || row[3] || '',
        nama_mobil:    row['nama_mobil']    || row[4] || '',
        nama_lain:      row['nama_lain']      || row[5] || '',
        lokasi_rak:    row['lokasi_rak']    || row[6] || '',
        harga_jual:    row['harga_jual']    || row[10] || 0,
        stok:          row['stok']          || row[7] || 0,
    };
    return "'" + encodeURIComponent(JSON.stringify(obj)) + "'";
}

function addToKasir(encodedData) {
    const d = JSON.parse(decodeURIComponent(encodedData));
    let cart = [];
    try { cart = JSON.parse(localStorage.getItem('stokpro_cart') || '[]'); } catch(e) {}
    d.gudang = activeSheet;
    const existing = cart.find(c => c.kode_barang === d.kode_barang);
    if (existing) {
        existing.qty = Math.min(parseInt(d.stok||0), existing.qty + 1);
    } else {
        cart.push({ ...d, qty: 1 });
    }
    localStorage.setItem('stokpro_cart', JSON.stringify(cart));
    showNotif('success', `✅ ${(d.nama_produk||'').substring(0,25)} ditambahkan ke Kasir`);
}

// ================================================================
// CLONE ROW — Tambah Varian Produk
// ================================================================
function cloneRow(encodedData) {
    const d = JSON.parse(decodeURIComponent(encodedData));

    // Buka modal
    openModal();

    setTimeout(() => {
        isCloneMode = true;

        // Update judul modal supaya jelas ini mode "Varian"
        document.getElementById('modalTitle').textContent    = 'Tambah Varian Produk';
        document.getElementById('modalSubtitle').textContent = 'Salin dari produk yang sama — ubah harga, stok, supplier, & kode internal';
        document.getElementById('submitBtnLabel').textContent = 'Simpan Varian';

        // Isi field yang DISALIN (identitas produk)
        document.getElementById('kode_barang').value  = d.kode_barang || '';
        document.getElementById('nama_produk').value  = d.nama_produk || '';
        document.getElementById('nama_mobil').value   = d.nama_mobil  || '';
        document.getElementById('nama_lain').value     = d.nama_lain    || '';
        document.getElementById('merk').value         = d.merk        || '';

        // Lokasi rak ikut disalin (biasanya sama)
        const lokasiVal = d.lokasi_rak || '';
        document.getElementById('lokasi_rak_input').value = lokasiVal;
        document.getElementById('lokasi_rak').value       = lokasiVal;

        // Kosongkan field yang HARUS diisi ulang
        document.getElementById('kode_internal').value             = '';
        document.getElementById('stok').value                      = '';
        document.getElementById('supplier').value                  = '';
        document.getElementById('harga_beli_display').value        = '';
        document.getElementById('harga_beli').value                = '';
        document.getElementById('diskon_display').value            = '';
        document.getElementById('preview_diskon').textContent      = '';
        document.getElementById('hargaFinalGroup').style.display   = 'none';
        document.getElementById('harga_final_display').value       = '';
        document.getElementById('harga_jual_display').value        = '';
        document.getElementById('harga_jual').value                = '';
        document.getElementById('preview_beli').textContent        = '';
        document.getElementById('preview_jual').textContent        = '';
        document.getElementById('marginCard').style.display        = 'none';
        document.getElementById('tanggal_beli').value              = '';

        // Tampilkan hint awal
        document.getElementById('preview_kode_internal').innerHTML =
            `<span style="color:#f59e0b;font-size:11px;font-weight:600;">
                ⏳ Isi <b>supplier &amp; harga beli (atau diskon)</b> dulu
            </span>`;

        // Fokus ke supplier (field pertama yang harus diisi)
        document.getElementById('supplier').focus();
    }, 220);
}

// ================================================================
// AUTO FORMAT TANGGAL — diformat SETELAH selesai menulis (onblur)
// ================================================================

// Saat mengetik: hanya izinkan digit & titik, batasi panjang mentah
function tanggalRawInput(el) {
    // Hapus semua karakter selain digit dan titik yang sudah ada
    let raw = el.value.replace(/[^\d.]/g, '');
    // Batasi supaya tidak terlalu panjang saat masih mengetik
    if (raw.replace(/\./g, '').length > 8) {
        raw = raw.slice(0, el.value.length - 1);
    }
    el.value = raw;
}

// Saat pindah field (blur): deteksi pola & format otomatis
function autoFormatTanggal(el) {
    const preview = document.getElementById('preview_tanggal_beli');
    const digits = el.value.replace(/\D/g, '').substring(0, 8);

    if (!digits) {
        el.value = '';
        if (preview) preview.textContent = '';
        return;
    }

    let v, hint;

    if (digits.length <= 6) {
        // ── Mode MM.YYYY (hanya bulan & tahun) ──────────────────
        const mm   = digits.slice(0, 2);
        const yyyy = digits.slice(2, 6);
        const bulan = parseInt(mm, 10);

        if (bulan < 1 || bulan > 12) {
            // Bulan tidak valid → coba mode DD.MM.YYYY
            goto_day_mode();
            return;
        }

        if (yyyy.length === 4) {
            v = mm + '.' + yyyy;
            el.maxLength = 7;
            const namaBulan = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
            hint = '📅 ' + namaBulan[bulan - 1] + ' ' + yyyy;
        } else {
            // Belum cukup digit untuk tahun → tetap simpan apa adanya
            v = digits.length >= 3 ? mm + '.' + yyyy : digits;
            el.maxLength = 7;
            hint = '';
        }
    } else {
        goto_day_mode();
        return;
    }

    el.value = v;
    if (preview) {
        preview.textContent = hint;
        preview.style.color = hint ? '#10b981' : '';
    }
    return;

    function goto_day_mode() {
        // ── Mode DD.MM.YYYY ──────────────────────────────────────
        const d8 = digits.substring(0, 8);
        const dd   = d8.slice(0, 2);
        const mm   = d8.slice(2, 4);
        const yyyy = d8.slice(4, 8);

        if (d8.length >= 5) {
            v = dd + '.' + mm + (yyyy ? '.' + yyyy : '');
        } else if (d8.length >= 3) {
            v = dd + '.' + mm;
        } else {
            v = d8;
        }
        el.maxLength = 10;
        el.value = v;

        if (preview) {
            if (d8.length === 8) {
                const tgl  = parseInt(dd, 10);
                const bln  = parseInt(mm, 10);
                const thn  = parseInt(yyyy, 10);
                const valid = tgl >= 1 && tgl <= 31 && bln >= 1 && bln <= 12 && thn >= 2000;
                const namaBulan = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
                if (valid) {
                    preview.textContent = '📅 ' + tgl + ' ' + namaBulan[bln - 1] + ' ' + thn;
                    preview.style.color = '#10b981';
                } else {
                    preview.textContent = '⚠️ Tanggal sepertinya tidak valid';
                    preview.style.color = '#f59e0b';
                }
            } else {
                preview.textContent = '';
            }
        }
    }
}

document.addEventListener('DOMContentLoaded', function () {
    setupEnterNavigation();
    setupBarcodeScanner();
    setupLokasiRakDropdown();
    setupCurrencyInputs();
    loadData();

    // ── Auto-generate kode_internal (sepenuhnya oleh sistem) ────
    // Syarat generate:
    //   Normal mode : harga_beli ATAU diskon terisi (merk opsional — fallback 'X' jika kosong)
    //   Clone mode  : supplier + (harga_beli ATAU diskon) terisi
    // Jika hanya diskon (tanpa harga beli) → kode: D(KENARI)-[MERK][NNN]-[SUP]
    // Trigger       : blur dari supplier, harga_beli_display, ATAU diskon_display
    // Sebelum generate SELALU fetch data terbaru agar nomor urut tidak duplikat.

    async function tryAutoGenerate() {
        const harga    = document.getElementById('harga_beli').value.trim();
        const diskon   = (document.getElementById('diskon_display').value || '').trim();
        const supplier = document.getElementById('supplier').value.trim();
        const preview  = document.getElementById('preview_kode_internal');

        // Bisa generate jika ada harga beli, ATAU ada diskon (mode tanpa harga beli)
        const bisaGenerate = !!harga || (!!diskon && parseFloat(diskon) > 0);

        if (isCloneMode) {
            // Varian: butuh supplier + (harga_beli ATAU diskon)
            if (supplier && bisaGenerate) {
                preview.innerHTML = `<span style="color:#6b7280;font-size:11px;">⏳ Memeriksa data terbaru...</span>`;
                await syncLatestRowsForGenerate();
                applyGeneratedKode(generateKodeInternal());
            } else {
                const kurang = [];
                if (!supplier)     kurang.push('supplier');
                if (!bisaGenerate) kurang.push('harga beli atau diskon');
                document.getElementById('preview_kode_internal').innerHTML =
                    `<span style="color:#f59e0b;font-size:11px;font-weight:600;">
                        ⏳ Isi <b>${kurang.join(' &amp; ')}</b> dulu
                    </span>`;
            }
        } else {
            // Tambah produk baru: butuh harga_beli ATAU diskon
            // Merk opsional — jika kosong, huruf inisial fallback ke 'X'
            if (bisaGenerate) {
                preview.innerHTML = `<span style="color:#6b7280;font-size:11px;">⏳ Memeriksa data terbaru...</span>`;
                await syncLatestRowsForGenerate();
                applyGeneratedKode(generateKodeInternal());
            }
        }
    }

    document.getElementById('supplier').addEventListener('blur', tryAutoGenerate);

    const hargaBeliDisplay = document.getElementById('harga_beli_display');
    if (hargaBeliDisplay) {
        hargaBeliDisplay.addEventListener('blur', tryAutoGenerate);
    }

    // Trigger ulang generate kode setelah diskon diubah (kode berubah dengan kode KENARI)
    const diskonDisplay = document.getElementById('diskon_display');
    if (diskonDisplay) {
        diskonDisplay.addEventListener('blur', tryAutoGenerate);
    }
});
</script>

<script>
// ── Service Worker: Auto Cache Invalidation ────────────────────────────────────
(function () {
    if (!('serviceWorker' in navigator)) return;
    var BUILD_VERSION = '<?= $v_build ?>';
    var stored = localStorage.getItem('stokpro_build');
    if (stored && stored !== BUILD_VERSION) {
        navigator.serviceWorker.getRegistrations().then(function (regs) {
            regs.forEach(function (r) { r.unregister(); });
            caches.keys().then(function (keys) {
                return Promise.all(keys.map(function (k) { return caches.delete(k); }));
            }).then(function () {
                localStorage.setItem('stokpro_build', BUILD_VERSION);
                window.location.reload(true);
            });
        });
    } else {
        localStorage.setItem('stokpro_build', BUILD_VERSION);
        navigator.serviceWorker.register('service-worker.js')
            .catch(function (e) { console.warn('[SW] Gagal registrasi:', e); });
    }
}());
    
    document.addEventListener('DOMContentLoaded', function () {
    loadData(); // 🔥 WAJIB
});
</script>

</body>
</html>