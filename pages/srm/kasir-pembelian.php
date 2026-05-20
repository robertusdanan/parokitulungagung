<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kasir Pembelian - Sri Rejeki Motor</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
:root {
    --blue:#2563eb;--blue-dark:#1d4ed8;--blue-light:#eff6ff;--blue-mid:#bfdbfe;
    --green:#16a34a;--green-light:#f0fdf4;--green-mid:#bbf7d0;
    --red:#dc2626;--red-light:#fef2f2;
    --orange:#ea580c;--orange-light:#fff7ed;
    --bg:#f1f5f9;--card:#fff;--border:#e2e8f0;
    --text:#0f172a;--text-secondary:#475569;--text-muted:#94a3b8;
    --sidebar-w:220px;--radius:10px;
    --shadow:0 1px 3px rgba(0,0,0,.07);--shadow-md:0 4px 16px rgba(0,0,0,.09);
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html,body{height:100%;font-family:'Outfit',sans-serif;font-size:14px;color:var(--text);background:var(--bg)}
body{display:flex;overflow:hidden}

/* Sidebar — same as other pages */
.sidebar{width:var(--sidebar-w);background:#0f1117;border-right:1px solid rgba(255,255,255,0.06);display:flex;flex-direction:column;flex-shrink:0;box-shadow:4px 0 24px rgba(0,0,0,.18)}
.sidebar-brand{padding:18px 16px 14px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:10px}
.brand-icon{width:34px;height:34px;background:linear-gradient(135deg,var(--blue),#1e40af);border-radius:9px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.brand-icon svg{width:18px;height:18px;stroke:#fff}
.brand-name{font-size:12.5px;font-weight:800;color:var(--text);line-height:1.2}
.brand-sub{font-size:10.5px;color:var(--text-muted);font-weight:500}
.sidebar-nav{flex:1;padding:10px 8px;display:flex;flex-direction:column;gap:2px;overflow-y:auto}
.nav-item{display:flex;align-items:center;gap:10px;padding:9px 10px;border-radius:8px;text-decoration:none;color:var(--text-secondary);font-size:13px;font-weight:500;transition:all .15s}
.nav-item svg{width:17px;height:17px;flex-shrink:0}
.nav-item:hover{background:var(--bg);color:var(--text)}
.nav-item.active{background:var(--blue-light);color:var(--blue);font-weight:700}
.nav-group-label{font-size:10px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--text-muted);padding:8px 10px 4px;margin-top:4px}
.sidebar-footer{padding:12px 16px;border-top:1px solid var(--border)}
.sheet-status{display:flex;align-items:center;gap:6px;font-size:11px;color:#2d3f52}
.status-dot{width:6px;height:6px;border-radius:50%;background:var(--green);flex-shrink:0}

/* ── Hamburger ── */
.hamburger{display:none;flex-direction:column;justify-content:center;gap:5px;width:36px;height:36px;border:none;background:transparent;cursor:pointer;padding:6px;border-radius:8px;flex-shrink:0}
.hamburger:hover{background:var(--bg)}
.hamburger span{display:block;width:18px;height:2px;background:var(--text);border-radius:2px;transition:all .25s}
.hamburger.active span:nth-child(1){transform:translateY(7px) rotate(45deg)}
.hamburger.active span:nth-child(2){opacity:0;transform:scaleX(0)}
.hamburger.active span:nth-child(3){transform:translateY(-7px) rotate(-45deg)}

/* ── Sidebar Overlay ── */
.sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:199}
.sidebar-overlay.active{display:block}

/* ── Responsive ── */
@media (max-width:768px){
    body{display:block;overflow:auto}
    .sidebar{position:fixed;top:0;left:0;height:100%;z-index:200;transform:translateX(-100%);transition:transform .3s cubic-bezier(.4,0,.2,1);box-shadow:4px 0 24px rgba(0,0,0,.12)}
    .sidebar.active{transform:translateX(0)}
    .hamburger{display:flex}
    .main-content{height:auto;min-height:100vh;display:flex;flex-direction:column}
    .top-bar{padding:0 12px}
    .content-area{padding:12px}
    .form-card{padding:14px 16px}
    .form-grid{grid-template-columns:1fr !important}
    /* Po header card: stack on mobile */
    .po-header-card{flex-direction:column;align-items:flex-start;gap:12px}
    .po-meta{width:100%}
    /* Gudang selector: full width options */
    .gudang-selector{flex-direction:column;gap:6px}
    .gudang-opt{padding:8px 12px;display:flex;align-items:center;gap:10px;text-align:left}
    .gudang-opt svg{margin:0}
    /* Action bar: stack submit + reset */
    .action-bar{flex-direction:column;align-items:stretch;padding:12px}
    .btn-submit,.btn-reset{width:100%;justify-content:center}
    .action-hint{margin-left:0;text-align:center}
}

/* ── Tablet (769px – 1024px) ── */
@media (min-width:769px) and (max-width:1024px){
    :root{--sidebar-w:190px}
    .sidebar{width:190px}
    .main-content{min-width:0}
    .content-area{padding:16px}
    .form-grid.cols3{grid-template-columns:repeat(2,1fr)}
}

/* Main */
.main-content{flex:1;display:flex;flex-direction:column;overflow:hidden;min-width:0}
.top-bar{background:#fff;border-bottom:1px solid var(--border);padding:0 24px;height:52px;display:flex;align-items:center;gap:12px;flex-shrink:0}
.page-title{font-size:16px;font-weight:800;color:var(--text)}

.content-area{flex:1;overflow-y:auto;padding:24px}

/* PO Header card */
.po-header-card{background:#fff;border:1px solid var(--border);border-radius:var(--radius);padding:18px 22px;margin-bottom:20px;display:flex;align-items:center;gap:16px}
.po-icon{width:48px;height:48px;background:var(--orange-light);border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.po-icon svg{width:26px;height:26px;color:var(--orange)}
.po-info{flex:1;min-width:0}
.po-no{font-size:17px;font-weight:900;color:var(--text)}
.po-sub{font-size:12px;color:var(--text-muted);margin-top:2px}
.po-meta{display:flex;gap:10px;flex-wrap:wrap}
.po-meta-item{background:var(--bg);border:1px solid var(--border);border-radius:7px;padding:6px 12px;font-size:12px;font-weight:600;color:var(--text-secondary)}
.po-meta-item span{color:var(--text);font-weight:700}

/* Form layout */
.form-card{background:#fff;border:1px solid var(--border);border-radius:var(--radius);padding:22px 24px;margin-bottom:16px}
.form-card-title{font-size:13px;font-weight:800;color:var(--text);text-transform:uppercase;letter-spacing:.06em;margin-bottom:16px;display:flex;align-items:center;gap:8px;padding-bottom:12px;border-bottom:1px solid var(--border)}
.form-card-title svg{width:16px;height:16px;color:var(--blue)}

.form-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:14px 20px}
.form-grid.cols3{grid-template-columns:repeat(3,1fr)}
.field-group{display:flex;flex-direction:column;gap:5px}
.field-group.full{grid-column:1/-1}
.field-label{font-size:11.5px;font-weight:700;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.06em}
.field-label .req{color:var(--red)}
.input-wrap{position:relative}
.field-input{width:100%;height:42px;padding:0 12px 0 38px;border:1.5px solid var(--border);border-radius:8px;font-size:13.5px;font-family:'Outfit',sans-serif;font-weight:600;color:var(--text);background:#fff;transition:border-color .15s}
.field-input.no-icon{padding-left:12px}
.field-input:focus{outline:none;border-color:var(--blue);box-shadow:0 0 0 3px rgba(37,99,235,.1)}
.field-input.error{border-color:var(--red)}
.input-icon{position:absolute;left:11px;top:50%;transform:translateY(-50%);color:var(--text-muted);pointer-events:none;display:flex;align-items:center}
.input-icon svg{width:16px;height:16px}

/* Gudang selector in form */
.gudang-selector{display:flex;gap:8px}
.gudang-opt{flex:1;border:2px solid var(--border);background:#fff;border-radius:8px;padding:10px 8px;cursor:pointer;text-align:center;transition:all .15s;font-family:'Outfit',sans-serif}
.gudang-opt .g-label{font-size:12.5px;font-weight:700;color:var(--text-secondary);display:block;margin-top:3px}
.gudang-opt .g-desc{font-size:10px;color:var(--text-muted);display:block}
.gudang-opt svg{width:20px;height:20px;display:block;margin:0 auto;color:var(--text-muted)}
.gudang-opt:hover{border-color:var(--blue-mid)}
.gudang-opt.active{border-color:var(--blue);background:var(--blue-light)}
.gudang-opt.active svg,.gudang-opt.active .g-label{color:var(--blue)}

/* Currency display */
.currency-display{font-size:12px;color:var(--blue);font-weight:700;margin-top:3px;min-height:16px}
.margin-display{background:var(--green-light);border:1px solid var(--green-mid);border-radius:7px;padding:8px 12px;display:flex;justify-content:space-between;align-items:center;margin-top:8px}
.margin-label{font-size:11px;font-weight:600;color:var(--green)}
.margin-val{font-size:13px;font-weight:800;color:var(--green)}

/* Kode internal — readonly, oleh sistem */
.kode-internal-preview{font-size:11px;font-weight:700;font-family:monospace;letter-spacing:.05em;margin-top:3px;min-height:16px}
.field-input.readonly-sys{background:#f8fafc;color:#6b7280;cursor:default}

/* Summary card */
.summary-card{background:linear-gradient(135deg,var(--blue),#1d4ed8);border-radius:var(--radius);padding:20px 22px;margin-bottom:20px;color:#fff}
.summary-title{font-size:12px;font-weight:700;opacity:.8;text-transform:uppercase;letter-spacing:.07em;margin-bottom:14px}
.summary-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.summary-item-label{font-size:11px;opacity:.7}
.summary-item-val{font-size:14px;font-weight:800;margin-top:2px}
.summary-total{grid-column:1/-1;padding-top:12px;border-top:1px solid rgba(255,255,255,.2)}
.summary-total-label{font-size:13px;font-weight:700;opacity:.9}
.summary-total-val{font-size:22px;font-weight:900;margin-top:2px}

/* Action bar */
.action-bar{display:flex;gap:10px;align-items:center;background:#fff;border:1px solid var(--border);border-radius:var(--radius);padding:14px 18px}
.btn-submit{height:46px;padding:0 28px;background:var(--blue);color:#fff;border:none;border-radius:9px;font-size:14.5px;font-weight:800;cursor:pointer;display:flex;align-items:center;gap:8px;font-family:'Outfit',sans-serif;transition:all .15s}
.btn-submit:hover{background:var(--blue-dark);transform:translateY(-1px);box-shadow:0 4px 16px rgba(37,99,235,.3)}
.btn-submit:disabled{background:#cbd5e1;cursor:not-allowed;transform:none;box-shadow:none}
.btn-reset{height:46px;padding:0 20px;background:#fff;color:var(--text);border:1.5px solid var(--border);border-radius:9px;font-size:14px;font-weight:700;cursor:pointer;font-family:'Outfit',sans-serif;transition:all .15s}
.btn-reset:hover{border-color:var(--red);color:var(--red);background:var(--red-light)}
.action-hint{font-size:11.5px;color:var(--text-muted);margin-left:auto}

/* Notif */
.notif{display:none;position:fixed;top:20px;right:20px;z-index:500;padding:14px 18px;border-radius:10px;font-size:13.5px;font-weight:700;box-shadow:var(--shadow-md);max-width:320px;align-items:center;gap:10px}
.notif.show{display:flex}
.notif.success{background:var(--green);color:#fff}
.notif.error{background:var(--red);color:#fff}
.notif svg{width:18px;height:18px;flex-shrink:0}

/* Loading */
.loading-overlay{position:fixed;inset:0;background:rgba(255,255,255,.8);z-index:999;display:none;align-items:center;justify-content:center;flex-direction:column;gap:12px}
.loading-overlay.show{display:flex}
.loading-spinner{width:36px;height:36px;border:3px solid var(--blue-mid);border-top-color:var(--blue);border-radius:50%;animation:spin .7s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}
</style>
</head>
<body>

<!-- Sidebar -->
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
        <div class="nav-group-label">Gudang</div>
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
        <div class="nav-group-label">Kasir</div>
        <a href="kasir-penjualan.php" class="nav-item" onclick="closeSidebar()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <rect x="2" y="3" width="20" height="14" rx="2"/>
                <line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/>
            </svg>
            <span>Penjualan</span>
        </a>
        <a href="kasir-pembelian.php" class="nav-item active" onclick="closeSidebar()">
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

<div class="main-content">
    <div class="top-bar">
        <button class="hamburger" id="hamburgerBtn" aria-label="Toggle menu">
            <span></span><span></span><span></span>
        </button>
        <div>
            <div class="page-title">Kasir Pembelian</div>
        </div>
        <div style="margin-left:auto;font-size:12.5px;color:var(--text-muted)" id="clockDisplay"></div>
    </div>

    <div class="content-area">
        <!-- PO Header -->
        <div class="po-header-card">
            <div class="po-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
            </div>
            <div class="po-info">
                <div class="po-no" id="poNoDisplay">PO-...</div>
                <div class="po-sub">Purchase Order Baru</div>
            </div>
            <div class="po-meta">
                <div class="po-meta-item">Tanggal: <span id="poTglDisplay">-</span></div>
                <div class="po-meta-item">Gudang: <span id="poGudangDisplay">Non-PPN</span></div>
            </div>
        </div>

        <!-- Gudang selector -->
        <div class="form-card" style="margin-bottom:16px;padding:16px 22px">
            <div class="form-card-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg>
                Target Gudang
            </div>
            <div class="gudang-selector">
                <button class="gudang-opt active" data-gudang="non_ppn" onclick="setGudang(this,'non_ppn')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20 7H4a2 2 0 00-2 2v10a2 2 0 002 2h16a2 2 0 002-2V9a2 2 0 00-2-2z"/><path d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2"/></svg>
                    <span class="g-label">Non-PPN</span>
                    <span class="g-desc">Gudang tanpa pajak</span>
                </button>
                <button class="gudang-opt" data-gudang="ppn" onclick="setGudang(this,'ppn')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20 7H4a2 2 0 00-2 2v10a2 2 0 002 2h16a2 2 0 002-2V9a2 2 0 00-2-2z"/><path d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2"/><line x1="12" y1="12" x2="12" y2="16"/><line x1="10" y1="14" x2="14" y2="14"/></svg>
                    <span class="g-label">PPN</span>
                    <span class="g-desc">Gudang termasuk pajak</span>
                </button>
            </div>
        </div>

        <!-- Identifikasi Barang -->
        <div class="form-card">
            <div class="form-card-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 3v4M8 3v4M2 11h20"/></svg>
                Identifikasi Barang
            </div>
            <div class="form-grid">
                <div class="field-group">
                    <label class="field-label">Kode Barang <span class="req">*</span></label>
                    <div class="input-wrap">
                        <div class="input-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="7" width="4" height="10"/><rect x="10" y="7" width="2" height="10"/><rect x="15" y="7" width="4" height="10"/></svg></div>
                        <input type="text" id="kode_barang" class="field-input" placeholder="Contoh: MK-321002" autocomplete="off" oninput="updateSummary()">
                    </div>
                </div>
                <div class="field-group">
                    <label class="field-label" style="display:flex;align-items:center;justify-content:space-between;">
                        Kode Internal
                        <span style="font-size:10px;color:var(--text-muted);font-weight:400;text-transform:none;letter-spacing:0">🔒 otomatis oleh sistem</span>
                    </label>
                    <div class="input-wrap">
                        <div class="input-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/></svg></div>
                        <input type="text" id="kode_internal" class="field-input readonly-sys"
                               placeholder="Isi merk + supplier + harga beli dulu..."
                               readonly>
                    </div>
                    <div class="kode-internal-preview" id="preview_kode_internal"></div>
                </div>
            </div>
        </div>

        <!-- Detail Produk -->
        <div class="form-card">
            <div class="form-card-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                Detail Produk
            </div>
            <div class="form-grid">
                <div class="field-group full">
                    <label class="field-label">Nama Produk <span class="req">*</span></label>
                    <div class="input-wrap">
                        <div class="input-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></div>
                        <input type="text" id="nama_produk" class="field-input" placeholder="Nama lengkap produk" autocomplete="off" oninput="updateSummary()">
                    </div>
                </div>
                <div class="field-group">
                    <label class="field-label">Nama Mobil / Tipe</label>
                    <div class="input-wrap">
                        <div class="input-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M5 17H3a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v9a2 2 0 01-2 2h-3"/><circle cx="7.5" cy="17.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/></svg></div>
                        <input type="text" id="nama_mobil" class="field-input" placeholder="Contoh: MT PS135, Taruna" autocomplete="off">
                    </div>
                </div>
                <div class="field-group">
                    <label class="field-label">Merk</label>
                    <div class="input-wrap">
                        <div class="input-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89L17 22l-5-3-5 3 1.523-9.11"/></svg></div>
                        <input type="text" id="merk" class="field-input" placeholder="Contoh: Sanyco, NGK, AHM" autocomplete="off">
                    </div>
                </div>
                <div class="field-group">
                    <label class="field-label">Kategori</label>
                    <div class="input-wrap">
                        <div class="input-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg></div>
                        <input type="text" id="kategori" class="field-input" placeholder="Rem, Kopling, Filter..." autocomplete="off">
                    </div>
                </div>
                <div class="field-group">
                    <label class="field-label">Lokasi Rak</label>
                    <div class="input-wrap">
                        <div class="input-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg></div>
                        <input type="text" id="lokasi_rak" class="field-input" placeholder="Contoh: A-01, B-12" autocomplete="off">
                    </div>
                </div>
            </div>
        </div>

        <!-- Pembelian Detail -->
        <div class="form-card">
            <div class="form-card-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
                Detail Pembelian
            </div>
            <div class="form-grid cols3">
                <div class="field-group">
                    <label class="field-label">Jumlah <span class="req">*</span></label>
                    <div class="input-wrap">
                        <div class="input-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg></div>
                        <input type="number" id="stok" class="field-input" placeholder="0" min="0" oninput="updateSummary()">
                    </div>
                </div>
                <div class="field-group">
                    <label class="field-label">Harga Beli</label>
                    <div class="input-wrap">
                        <div class="input-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg></div>
                        <input type="text" id="harga_beli_display" class="field-input" placeholder="0" oninput="onCurrencyInput(this,'harga_beli')">
                        <input type="hidden" id="harga_beli">
                    </div>
                    <div class="currency-display" id="preview_beli"></div>
                </div>
                <div class="field-group">
                    <label class="field-label">Harga Jual</label>
                    <div class="input-wrap">
                        <div class="input-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg></div>
                        <input type="text" id="harga_jual_display" class="field-input" placeholder="0" oninput="onCurrencyInput(this,'harga_jual')">
                        <input type="hidden" id="harga_jual">
                    </div>
                    <div class="currency-display" id="preview_jual"></div>
                </div>
                <div class="field-group">
                    <label class="field-label">Supplier</label>
                    <div class="input-wrap">
                        <div class="input-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg></div>
                        <input type="text" id="supplier" class="field-input" placeholder="Nama supplier" autocomplete="off">
                    </div>
                </div>
                <div class="field-group">
                    <label class="field-label">Tanggal Beli</label>
                    <div class="input-wrap">
                        <div class="input-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></div>
                        <input type="text" id="tanggal_beli" class="field-input" placeholder="DD.MM.YYYY" maxlength="10" oninput="autoFormatTanggal(this)">
                    </div>
                </div>
                <div class="field-group">
                    <label class="field-label">Keterangan</label>
                    <div class="input-wrap">
                        <div class="input-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="16" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/></svg></div>
                        <input type="text" id="keterangan" class="field-input" placeholder="Catatan pembelian...">
                    </div>
                </div>
            </div>

            <!-- Margin display -->
            <div class="margin-display" id="marginDisplay" style="display:none">
                <span class="margin-label">📈 Margin Keuntungan</span>
                <span class="margin-val" id="marginVal">-</span>
            </div>
        </div>

        <!-- Summary -->
        <div class="summary-card" id="summaryCard">
            <div class="summary-title">📋 Ringkasan Purchase Order</div>
            <div class="summary-grid">
                <div>
                    <div class="summary-item-label">Produk</div>
                    <div class="summary-item-val" id="summNama">-</div>
                </div>
                <div>
                    <div class="summary-item-label">Gudang Tujuan</div>
                    <div class="summary-item-val" id="summGudang">Non-PPN</div>
                </div>
                <div>
                    <div class="summary-item-label">Jumlah Beli</div>
                    <div class="summary-item-val" id="summQty">0 pcs</div>
                </div>
                <div>
                    <div class="summary-item-label">Harga Beli / pcs</div>
                    <div class="summary-item-val" id="summHarga">Rp 0</div>
                </div>
                <div class="summary-total">
                    <div class="summary-item-label">Total Nilai Pembelian</div>
                    <div class="summary-total-val" id="summTotal">Rp 0</div>
                </div>
            </div>
        </div>

        <!-- Action bar -->
        <div class="action-bar">
            <button class="btn-submit" id="btnSubmit" onclick="submitPembelian()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="18" height="18"><polyline points="20 6 9 17 4 12"/></svg>
                Simpan & Perbarui Gudang
            </button>
            <button class="btn-reset" onclick="resetForm()">Reset Form</button>
            
        </div>
    </div>
</div>

<!-- Notif -->
<div class="notif" id="notif">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" id="notifIcon"></svg>
    <span id="notifMsg"></span>
</div>

<!-- Loading -->
<div class="loading-overlay" id="loadingOverlay">
    <div class="loading-spinner"></div>
    <div style="font-size:14px;font-weight:600;color:var(--text-secondary)" id="loadingText">Menyimpan...</div>
</div>

<script>
// ================================================================
// INIT
// ================================================================
let activeGudang = 'non_ppn';
let noPO = '';


function updateClock() {
    const now = new Date();
    const tgl = now.toLocaleDateString('id-ID',{day:'2-digit',month:'2-digit',year:'numeric'}).replace(/\//g,'.');
    document.getElementById('poTglDisplay').textContent =
        now.toLocaleDateString('id-ID',{day:'numeric',month:'long',year:'numeric'});
    document.getElementById('clockDisplay').textContent =
        now.toLocaleDateString('id-ID',{weekday:'long',day:'numeric',month:'long',year:'numeric'}) +
        ' · ' + now.toLocaleTimeString('id-ID',{hour:'2-digit',minute:'2-digit'});

    // Auto-isi tanggal beli jika kosong
    const tglInput = document.getElementById('tanggal_beli');
    if (!tglInput.value) tglInput.value = tgl;
}

function generatePO() {
    const now = new Date();
    noPO = 'PO-' + '26' +
        String(now.getMonth()+1).padStart(2,'0') + String(now.getDate()).padStart(2,'0') +
        '-' + String(Math.floor(Math.random()*900)+100);
    document.getElementById('poNoDisplay').textContent = noPO;
}

// ================================================================
// GUDANG
// ================================================================
function setGudang(el, gudang) {
    activeGudang = gudang;
    document.querySelectorAll('.gudang-opt').forEach(b => b.classList.toggle('active', b === el));
    document.getElementById('poGudangDisplay').textContent = gudang === 'non_ppn' ? 'Non-PPN' : 'PPN';
    document.getElementById('summGudang').textContent      = gudang === 'non_ppn' ? 'Non-PPN' : 'PPN';
    // Re-generate kode jika field sudah terisi (counter bisa berbeda per gudang)
    const ki = document.getElementById('kode_internal').value.trim();
    if (ki) tryAutoGenerateKode();
}

// ================================================================
// CURRENCY INPUT
// ================================================================
function onCurrencyInput(el, field) {
    const raw = parseInt(el.value.replace(/\D/g,'')) || 0;
    document.getElementById(field).value = raw;
    const previewId = field === 'harga_beli' ? 'preview_beli' : 'preview_jual';
    document.getElementById(previewId).textContent = raw > 0 ? formatRp(raw) : '';
    updateSummary();
    updateMargin();
}

function updateMargin() {
    const beli = parseInt(document.getElementById('harga_beli').value || 0);
    const jual = parseInt(document.getElementById('harga_jual').value || 0);
    const md   = document.getElementById('marginDisplay');
    if (beli > 0 && jual > 0) {
        const margin = ((jual - beli) / beli * 100).toFixed(1);
        const selisih = jual - beli;
        document.getElementById('marginVal').textContent =
            `${margin}% · Selisih ${formatRp(selisih)}`;
        md.style.display = '';
        md.style.background = selisih >= 0 ? '' : 'var(--red-light)';
        md.querySelector('.margin-label').style.color = selisih >= 0 ? '' : 'var(--red)';
        md.querySelector('.margin-val').style.color   = selisih >= 0 ? '' : 'var(--red)';
    } else {
        md.style.display = 'none';
    }
}

function updateSummary() {
    const nama  = document.getElementById('nama_produk').value.trim() || '-';
    const qty   = parseInt(document.getElementById('stok').value || 0);
    const harga = parseInt(document.getElementById('harga_beli').value || 0);
    document.getElementById('summNama').textContent  = nama.substring(0,30) || '-';
    document.getElementById('summQty').textContent   = qty + ' pcs';
    document.getElementById('summHarga').textContent = formatRp(harga);
    document.getElementById('summTotal').textContent = formatRp(harga * qty);
}

// ================================================================
// AUTO FORMAT TANGGAL
// ================================================================
function autoFormatTanggal(el) {
    let v = el.value.replace(/\D/g,'').substring(0,8);
    if (v.length >= 5)      v = v.slice(0,2) + '.' + v.slice(2,4) + '.' + v.slice(4);
    else if (v.length >= 3) v = v.slice(0,2) + '.' + v.slice(2);
    el.value = v;
}

// ================================================================
// SUBMIT
// ================================================================
async function submitPembelian() {
    const kodeBarang = document.getElementById('kode_barang').value.trim();
    const namaProduk = document.getElementById('nama_produk').value.trim();

    if (!kodeBarang) { showNotif('Kode barang wajib diisi!', 'error'); document.getElementById('kode_barang').focus(); return; }
    if (!namaProduk) { showNotif('Nama produk wajib diisi!', 'error'); document.getElementById('nama_produk').focus(); return; }

    const now     = new Date();
    const tanggal = now.toLocaleDateString('id-ID',{day:'2-digit',month:'2-digit',year:'numeric'}).replace(/\//g,'.') +
        ' ' + now.toLocaleTimeString('id-ID',{hour:'2-digit',minute:'2-digit'});

    const payload = {
        action:        'beli',
        gudang:        activeGudang,
        no_po:         noPO,
        tanggal:       tanggal,
        kode_barang:   kodeBarang,
        kode_internal: document.getElementById('kode_internal').value.trim(),
        merk:          document.getElementById('merk').value.trim(),
        nama_produk:   namaProduk,
        nama_mobil:    document.getElementById('nama_mobil').value.trim(),
        kategori:      document.getElementById('kategori').value.trim(),
        lokasi_rak:    document.getElementById('lokasi_rak').value.trim(),
        stok:          parseInt(document.getElementById('stok').value || 0),
        supplier:      document.getElementById('supplier').value.trim(),
        harga_beli:    parseInt(document.getElementById('harga_beli').value || 0),
        harga_jual:    parseInt(document.getElementById('harga_jual').value || 0),
        tanggal_beli:  document.getElementById('tanggal_beli').value.trim(),
        keterangan:    document.getElementById('keterangan').value.trim(),
    };

    document.getElementById('loadingText').textContent = 'Menyimpan pembelian & memperbarui gudang...';
    document.getElementById('loadingOverlay').classList.add('show');

    try {
        const res  = await fetch('kasir-submit.php', {
            method:  'POST',
            headers: {'Content-Type':'application/json'},
            body:    JSON.stringify(payload),
        });
        const json = await res.json();
        document.getElementById('loadingOverlay').classList.remove('show');

        if (!json.success) throw new Error(json.message || 'Gagal');

        const act = json.action_gudang === 'appended' ? 'Barang baru ditambahkan ke gudang.' : 'Stok gudang diperbarui.';
        showNotif('✅ Pembelian disimpan! ' + act, 'success');
        generatePO();
        resetFormFields();

    } catch(e) {
        document.getElementById('loadingOverlay').classList.remove('show');
        showNotif('Error: ' + e.message, 'error');
    }
}

function resetForm() {
    if (!confirm('Reset semua isian form?')) return;
    resetFormFields();
}

function resetFormFields() {
    ['kode_barang','kode_internal','nama_produk','nama_mobil','merk','kategori',
     'lokasi_rak','stok','supplier','harga_beli_display','harga_jual_display',
     'keterangan'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = '';
    });
    document.getElementById('harga_beli').value = '';
    document.getElementById('harga_jual').value = '';
    document.getElementById('preview_beli').textContent = '';
    document.getElementById('preview_jual').textContent = '';
    document.getElementById('preview_kode_internal').innerHTML = '';
    document.getElementById('marginDisplay').style.display = 'none';

    // Reset tanggal ke hari ini
    const now = new Date();
    document.getElementById('tanggal_beli').value =
        now.toLocaleDateString('id-ID',{day:'2-digit',month:'2-digit',year:'numeric'}).replace(/\//g,'.');

    updateSummary();
    document.getElementById('kode_barang').focus();
}

// ================================================================
// UTILS
// ================================================================
function formatRp(val) {
    const n = parseInt(String(val).replace(/\D/g,'')) || 0;
    return 'Rp ' + n.toLocaleString('id-ID');
}

function showNotif(msg, type='success') {
    const el    = document.getElementById('notif');
    const icon  = document.getElementById('notifIcon');
    const msgEl = document.getElementById('notifMsg');
    icon.innerHTML = type === 'success'
        ? '<polyline points="20 6 9 17 4 12"/>'
        : '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>';
    msgEl.textContent = msg;
    el.className = `notif ${type} show`;
    setTimeout(() => el.classList.remove('show'), 4000);
}

// ── Sidebar Mobile Toggle ──────────────────────────────────────────────────
function closeSidebar() {
    document.getElementById('sidebar').classList.remove('active');
    document.getElementById('sidebarOverlay').classList.remove('active');
    document.getElementById('hamburgerBtn').classList.remove('active');
}
document.getElementById('hamburgerBtn').addEventListener('click', function () {
    document.getElementById('sidebar').classList.toggle('active');
    document.getElementById('sidebarOverlay').classList.toggle('active');
    this.classList.toggle('active');
});
document.getElementById('sidebarOverlay').addEventListener('click', closeSidebar);

// ================================================================
// GENERATE KODE INTERNAL — oleh sistem
// ================================================================
const SPREADSHEET_ID = '1zQtkYOkwWjPPll_D0u7pJNTYebWGTMl3ilPwnEIWVKQ';
const DIGIT_MAP = {'1':'K','2':'E','3':'N','4':'A','5':'R','6':'I','7':'B','8':'O','9':'X','0':'Y'};

// Cache rows per sheet agar tidak re-fetch setiap kali
const gudangRowsCache = { non_ppn: null, ppn: null };
let gudangRowsLoaded  = false;

async function loadGudangRows() {
    // Load kedua sheet sekaligus (parallel)
    const [resA, resB] = await Promise.all([
        fetch(`https://opensheet.elk.sh/${SPREADSHEET_ID}/non_ppn`),
        fetch(`https://opensheet.elk.sh/${SPREADSHEET_ID}/ppn`),
    ]);
    const [dataA, dataB] = await Promise.all([resA.json(), resB.json()]);
    gudangRowsCache.non_ppn = Array.isArray(dataA) ? dataA : [];
    gudangRowsCache.ppn     = Array.isArray(dataB) ? dataB : [];
    gudangRowsLoaded = true;
}

function getNextKodeCounter(merkHuruf) {
    const rows    = gudangRowsCache[activeGudang] || [];
    const pattern = new RegExp(`-${merkHuruf}(\\d{3})(?:-|$)`);
    let max = 0;
    rows.forEach(row => {
        const ki = (row['kode_internal'] || row[1] || '').trim();
        if (!ki) return;
        const m = ki.match(pattern);
        if (m) {
            const n = parseInt(m[1], 10);
            if (n > max) max = n;
        }
    });
    return max + 1;
}

function encodeHarga(harga) {
    return String(harga).replace(/\D/g,'').split('').map(d => DIGIT_MAP[d] || d).join('');
}

function supplierInisial(supplier) {
    if (!supplier || !supplier.trim()) return '';
    const s = supplier.trim();
    if (s.replace(/\s+/g,'').length <= 3) return s.replace(/\s+/g,'').toUpperCase();
    return s.toUpperCase().split(/\s+/).map(w => w[0]).join('');
}

function generateKodeInternal() {
    const merk      = document.getElementById('merk').value.trim();
    const supplier  = document.getElementById('supplier').value.trim();
    const hargaRaw  = (document.getElementById('harga_beli').value || '').replace(/\D/g,'')
                   || (document.getElementById('harga_beli_display').value || '').replace(/\D/g,'')
                   || '0';
    const merkHuruf = merk ? (merk.toUpperCase().replace(/[^A-Z]/g,'')[0] || 'X') : 'X';
    const encoded   = encodeHarga(hargaRaw);
    const nextNum   = getNextKodeCounter(merkHuruf);
    const num       = String(nextNum).padStart(3, '0');
    const sup       = supplierInisial(supplier);
    let result = `D${encoded}-${merkHuruf}${num}`;
    if (sup) result += `-${sup}`;
    return result;
}

function applyKodeInternal(val) {
    const input   = document.getElementById('kode_internal');
    const preview = document.getElementById('preview_kode_internal');
    input.value   = val;
    preview.innerHTML = `<span style="color:#10b981;font-family:monospace;font-size:11px;font-weight:700;letter-spacing:.05em;">✓ ${val}</span>`;
    input.style.borderColor = '#10b981';
    input.style.boxShadow   = '0 0 0 3px rgba(16,185,129,.18)';
    setTimeout(() => { input.style.borderColor = ''; input.style.boxShadow = ''; }, 800);
}

function tryAutoGenerateKode() {
    const merk     = document.getElementById('merk').value.trim();
    const supplier = document.getElementById('supplier').value.trim();
    const harga    = document.getElementById('harga_beli').value.trim();
    const preview  = document.getElementById('preview_kode_internal');

    if (merk && supplier && harga) {
        if (!gudangRowsLoaded) {
            // Data belum selesai dimuat — tampilkan loading, lalu generate saat selesai
            preview.innerHTML = `<span style="color:#94a3b8;font-size:11px;font-weight:600;">⏳ Memuat data gudang...</span>`;
            loadGudangRows().then(() => applyKodeInternal(generateKodeInternal()));
        } else {
            applyKodeInternal(generateKodeInternal());
        }
    } else {
        const kurang = [];
        if (!merk)     kurang.push('merk');
        if (!supplier) kurang.push('supplier');
        if (!harga)    kurang.push('harga beli');
        if (kurang.length < 3) {
            preview.innerHTML = `<span style="color:#f59e0b;font-size:11px;font-weight:600;">⏳ Isi <b>${kurang.join(' &amp; ')}</b> dulu</span>`;
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    generatePO();
    updateClock();
    setInterval(updateClock, 1000);
    document.getElementById('kode_barang').focus();

    // Load data gudang di background saat halaman pertama kali dibuka
    loadGudangRows().catch(() => {
        // Gagal load — tetap bisa generate, counter mulai dari 001
        gudangRowsLoaded = true;
    });

    // Trigger generate saat blur dari merk, supplier, atau harga_beli
    ['merk', 'supplier'].forEach(id => {
        document.getElementById(id).addEventListener('blur', tryAutoGenerateKode);
    });
    document.getElementById('harga_beli_display').addEventListener('blur', tryAutoGenerateKode);
});
</script>
</body>
</html>