<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kasir Penjualan - Sri Rejeki Motor</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&display=swap">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
<style>
/* ── Variables ── */
:root {
    --blue:#2563eb; --blue-dark:#1d4ed8; --blue-light:#eff6ff; --blue-mid:#bfdbfe;
    --green:#16a34a; --green-light:#f0fdf4; --green-mid:#bbf7d0;
    --red:#dc2626; --red-light:#fef2f2; --red-mid:#fecaca;
    --orange:#ea580c; --orange-light:#fff7ed;
    --purple:#7c3aed; --purple-light:#f5f3ff;
    --bg:#f1f5f9; --card:#fff; --border:#e2e8f0; --border-focus:#93c5fd;
    --text:#0f172a; --text-secondary:#475569; --text-muted:#94a3b8;
    --sidebar-w:220px; --radius:10px;
    --shadow:0 1px 3px rgba(0,0,0,.07),0 1px 2px rgba(0,0,0,.05);
    --shadow-md:0 4px 16px rgba(0,0,0,.09);
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html,body{height:100%;font-family:'Outfit',sans-serif;font-size:14px;color:var(--text);background:var(--bg)}
body{display:flex;overflow:hidden}

/* ── Sidebar ── */
.sidebar{width:var(--sidebar-w);background:#0f1117;border-right:1px solid rgba(255,255,255,0.06);display:flex;flex-direction:column;flex-shrink:0;z-index:100;box-shadow:4px 0 24px rgba(0,0,0,.18)}
.sidebar-brand{padding:20px 16px 16px;border-bottom:1px solid rgba(255,255,255,0.06);display:flex;align-items:center;gap:10px}
.brand-icon{width:36px;height:36px;background:linear-gradient(135deg,#3b82f6,#1d4ed8);border-radius:9px;display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 4px 12px rgba(59,130,246,0.35)}
.brand-icon svg{width:18px;height:18px;stroke:#fff}
.brand-name{font-size:13px;font-weight:700;color:#e2e8f0;line-height:1.2}
.brand-sub{font-size:10.5px;color:#2d3f52;font-weight:500}
.sidebar-nav{flex:1;padding:10px 8px;display:flex;flex-direction:column;gap:2px;overflow-y:auto}
.nav-item{display:flex;align-items:center;gap:10px;padding:9px 10px;border-radius:8px;text-decoration:none;color:#4e6481;font-size:13px;font-weight:500;transition:all .15s;position:relative}
.nav-item svg{width:17px;height:17px;flex-shrink:0}
.nav-item:hover{background:rgba(255,255,255,0.05);color:#94a3b8}
.nav-item.active{background:rgba(59,130,246,0.13);color:#60a5fa;font-weight:600}
.nav-item.active::before{content:'';position:absolute;left:0;top:50%;transform:translateY(-50%);width:3px;height:55%;background:#3b82f6;border-radius:0 3px 3px 0}
.nav-section-label,.nav-group-label{font-size:10px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:#2d3f52;padding:14px 10px 5px}
.sidebar-footer{padding:12px 16px;border-top:1px solid rgba(255,255,255,0.06)}
.sheet-status{display:flex;align-items:center;gap:6px;font-size:11px;color:#2d3f52}
.status-dot{width:6px;height:6px;border-radius:50%;background:var(--green);flex-shrink:0;box-shadow:0 0 6px var(--green)}

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

/* ── Layout ── */
.main-content{flex:1;display:flex;flex-direction:column;overflow:hidden;min-width:0}
.top-bar{background:#fff;border-bottom:1px solid var(--border);padding:0 20px;height:52px;display:flex;align-items:center;gap:12px;flex-shrink:0}
.page-title{font-size:16px;font-weight:700;color:var(--text)}
.page-sub{font-size:12px;color:var(--text-muted);font-weight:400}

.pos-layout{flex:1;display:grid;grid-template-columns:1fr 340px;overflow:hidden;gap:0}

/* ── Left panel ── */
.pos-left{display:flex;flex-direction:column;overflow:hidden;padding:16px;gap:12px}

/* Gudang selector */
.gudang-bar{display:flex;align-items:center;gap:8px;background:#fff;border:1px solid var(--border);border-radius:var(--radius);padding:8px 12px}
.gudang-bar-label{font-size:12px;color:var(--text-muted);font-weight:600;flex-shrink:0}
.gudang-tabs{display:flex;gap:4px;margin-left:8px}
.gudang-tab{padding:5px 14px;border-radius:6px;border:1.5px solid var(--border);background:#fff;font-size:12.5px;font-weight:600;cursor:pointer;transition:all .15s;color:var(--text-secondary)}
.gudang-tab:hover{border-color:var(--blue-mid);color:var(--blue)}
.gudang-tab.active{background:var(--blue);border-color:var(--blue);color:#fff}

/* Scanner */
.scanner-card{background:#fff;border:1px solid var(--border);border-radius:var(--radius);padding:14px 16px}
.scanner-label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text-muted);margin-bottom:8px;display:flex;align-items:center;gap:6px}
.scanner-label svg{width:14px;height:14px}
.scanner-row{display:flex;gap:8px;position:relative}
.scan-input{flex:1;height:44px;padding:0 14px 0 40px;border:2px solid var(--border);border-radius:8px;font-size:14px;font-family:'Outfit',sans-serif;font-weight:600;letter-spacing:.04em;transition:border-color .15s;background:#fff;color:var(--text)}
.scan-input:focus{outline:none;border-color:var(--blue)}
.scan-icon{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--text-muted);pointer-events:none}
.scan-icon svg{width:18px;height:18px;display:block}
.scan-demo{font-size:11px;color:var(--text-muted);margin-top:5px}

/* Search dropdown */
.search-dropdown{position:absolute;top:calc(100% + 4px);left:0;right:0;background:#fff;border:1px solid var(--border);border-radius:8px;box-shadow:var(--shadow-md);z-index:200;max-height:260px;overflow-y:auto;display:none}
.search-dropdown.open{display:block}
.search-item{padding:10px 14px;cursor:pointer;border-bottom:1px solid #f1f5f9;transition:background .1s;display:flex;align-items:center;gap:10px}
.search-item:last-child{border-bottom:none}
.search-item:hover,.search-item.focused{background:var(--blue-light)}
.search-item-info{flex:1;min-width:0}
.search-item-name{font-size:13px;font-weight:700;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.search-item-sub{font-size:11px;color:var(--text-muted);margin-top:1px}
.search-item-price{font-size:13px;font-weight:700;color:var(--blue);flex-shrink:0}
.search-item-stok{font-size:11px;font-weight:600;padding:2px 7px;border-radius:4px;flex-shrink:0}
.stok-ok{background:var(--green-light);color:var(--green)}
.stok-low{background:var(--orange-light);color:var(--orange)}
.stok-nol{background:var(--red-light);color:var(--red)}
.search-empty{padding:20px;text-align:center;color:var(--text-muted);font-size:13px}

/* Cart */
.cart-section{flex:1;display:flex;flex-direction:column;background:#fff;border:1px solid var(--border);border-radius:var(--radius);overflow:hidden}
.cart-header{padding:12px 16px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}
.cart-title{font-size:13px;font-weight:700;color:var(--text)}
.cart-count{font-size:11px;background:var(--blue);color:#fff;padding:2px 8px;border-radius:10px;font-weight:700}
.btn-clear{font-size:11.5px;color:var(--red);font-weight:600;cursor:pointer;border:none;background:none;padding:4px 8px;border-radius:5px;transition:background .15s}
.btn-clear:hover{background:var(--red-light)}
.cart-body{flex:1;overflow-y:auto}
.cart-empty{display:flex;flex-direction:column;align-items:center;justify-content:center;height:100%;gap:12px;padding:40px}
.cart-empty-icon{width:56px;height:56px;background:var(--bg);border-radius:14px;display:flex;align-items:center;justify-content:center}
.cart-empty-icon svg{width:26px;height:26px;color:var(--text-muted)}
.cart-empty-text{font-size:13px;color:var(--text-muted);text-align:center}
.cart-table{width:100%;border-collapse:collapse}
.cart-table th{font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);padding:8px 12px;text-align:left;border-bottom:1px solid var(--border);background:var(--bg);white-space:nowrap}
.cart-table td{padding:10px 12px;border-bottom:1px solid #f8fafc;vertical-align:middle}
.cart-table tr:hover td{background:#fafbfd}
.cart-prod-name{font-size:13px;font-weight:700;color:var(--text)}
.cart-prod-sub{font-size:11px;color:var(--text-muted);margin-top:1px}
.qty-control{display:flex;align-items:center;gap:4px}
.qty-btn{width:26px;height:26px;border-radius:6px;border:1.5px solid var(--border);background:#fff;cursor:pointer;font-size:15px;font-weight:700;color:var(--text-secondary);display:flex;align-items:center;justify-content:center;transition:all .15s;line-height:1;flex-shrink:0}
.qty-btn:hover{border-color:var(--blue);color:var(--blue);background:var(--blue-light)}
.qty-val{width:36px;text-align:center;font-size:14px;font-weight:700;border:1.5px solid var(--border);border-radius:6px;height:26px;padding:0;font-family:'Outfit',sans-serif;background:#fff}
.qty-val:focus{outline:none;border-color:var(--blue)}
.cart-subtotal{font-size:13px;font-weight:700;color:var(--text);text-align:right;white-space:nowrap}
.cart-price-unit{font-size:11px;color:var(--text-muted)}
.btn-remove{width:26px;height:26px;border-radius:6px;border:none;background:transparent;cursor:pointer;color:var(--text-muted);display:flex;align-items:center;justify-content:center;transition:all .15s}
.btn-remove:hover{background:var(--red-light);color:var(--red)}
.btn-remove svg{width:14px;height:14px;display:block}

/* ── Harga custom edit ── */
.price-edit-wrap{display:flex;flex-direction:column;align-items:flex-end;gap:2px}
.price-input{width:90px;text-align:right;font-size:12px;font-weight:700;border:1.5px solid var(--border);border-radius:6px;height:26px;padding:0 6px;font-family:'Outfit',sans-serif;background:#fff;color:var(--text);transition:border-color .15s}
.price-input:focus{outline:none;border-color:var(--blue)}
.price-input.discounted{border-color:var(--orange);background:var(--orange-light);color:var(--orange)}
.price-original{font-size:10px;color:var(--text-muted);text-decoration:line-through}
.discount-badge{display:inline-flex;align-items:center;gap:3px;background:var(--orange-light);border:1px solid #fed7aa;color:var(--orange);font-size:10px;font-weight:700;padding:1px 6px;border-radius:4px;white-space:nowrap}

/* ── Customer section ── */
.customer-section{margin:0 20px 10px;background:#fffbeb;border:1.5px solid #fde68a;border-radius:8px;padding:10px 12px;display:none}
.customer-section.show{display:block}
.customer-section-label{font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#92400e;margin-bottom:6px;display:flex;align-items:center;gap:5px}
.customer-input{width:100%;height:34px;padding:0 10px;border:1.5px solid #fde68a;border-radius:6px;font-size:13px;font-weight:600;font-family:'Outfit',sans-serif;background:#fff;color:var(--text);transition:border-color .15s}
.customer-input:focus{outline:none;border-color:var(--orange)}

/* ── Waktu transaksi ── */
.waktu-section{background:#f0fdf4;border:1.5px solid #bbf7d0;border-radius:8px;padding:10px 12px}
.waktu-section-label{font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#166534;margin-bottom:6px;display:flex;align-items:center;gap:5px}
.waktu-row{display:flex;gap:6px;align-items:center}
.waktu-input{flex:1;height:34px;padding:0 10px;border:1.5px solid #bbf7d0;border-radius:6px;font-size:12.5px;font-weight:600;font-family:'Outfit',sans-serif;background:#fff;color:var(--text);transition:border-color .15s}
.waktu-input:focus{outline:none;border-color:var(--green)}
.waktu-input.custom{border-color:var(--green);background:var(--green-light)}
.btn-waktu-reset{height:34px;padding:0 10px;border:1.5px solid #bbf7d0;border-radius:6px;background:#fff;font-size:11px;font-weight:700;color:#16a34a;cursor:pointer;white-space:nowrap;transition:all .15s;font-family:'Outfit',sans-serif;flex-shrink:0}
.btn-waktu-reset:hover{background:var(--green);color:#fff;border-color:var(--green)}
.waktu-hint{font-size:10.5px;color:#16a34a;margin-top:4px;display:flex;align-items:center;gap:4px}

/* ── Right panel (payment) ── */
.pos-right{background:#fff;border-left:1px solid var(--border);display:flex;flex-direction:column;overflow:hidden}
.pay-header{padding:16px 20px 12px;border-bottom:1px solid var(--border)}
.pay-header-title{font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text-muted)}

.total-card{margin:16px 20px 0;background:var(--blue-light);border:1.5px solid var(--blue-mid);border-radius:var(--radius);padding:14px 16px}
.total-row{display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;font-size:12.5px;color:var(--text-secondary)}
.total-row:last-child{margin-bottom:0}
.total-label{font-size:11px;color:#64748b;font-weight:500}
.total-main{display:flex;justify-content:space-between;align-items:center;margin-top:10px;padding-top:10px;border-top:1.5px solid var(--blue-mid)}
.total-main-label{font-size:14px;font-weight:700;color:var(--text)}
.total-main-val{font-size:20px;font-weight:700;color:var(--blue)}

.pay-section{padding:0 20px;margin-top:14px}
.pay-section-label{font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--text-muted);margin-bottom:8px}

/* Metode bayar */
.metode-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:6px;margin-bottom:14px}
.metode-btn{border:2px solid var(--border);background:#fff;border-radius:8px;padding:8px 4px;cursor:pointer;transition:all .15s;text-align:center;font-family:'Outfit',sans-serif}
.metode-btn svg{width:20px;height:20px;display:block;margin:0 auto 4px;color:var(--text-muted)}
.metode-btn span{font-size:11.5px;font-weight:700;color:var(--text-secondary);display:block}
.metode-btn:hover{border-color:var(--blue-mid)}
.metode-btn.active{border-color:var(--blue);background:var(--blue-light)}
.metode-btn.active svg{color:var(--blue)}
.metode-btn.active span{color:var(--blue)}

/* Keterangan */
.ket-grid{display:grid;grid-template-columns:1fr 1fr;gap:6px;margin-bottom:14px}
.ket-btn{border:2px solid var(--border);background:#fff;border-radius:8px;padding:9px 8px;cursor:pointer;transition:all .15s;text-align:center;display:flex;align-items:center;justify-content:center;gap:6px;font-family:'Outfit',sans-serif}
.ket-btn svg{width:16px;height:16px;color:var(--text-muted)}
.ket-btn span{font-size:12.5px;font-weight:700;color:var(--text-secondary)}
.ket-btn:hover{border-color:var(--blue-mid)}
.ket-btn.active{border-color:var(--blue);background:var(--blue-light)}
.ket-btn.active svg,.ket-btn.active span{color:var(--blue)}

/* Uang diterima */
.uang-row{display:flex;align-items:center;gap:0;border:2px solid var(--border);border-radius:8px;overflow:hidden;margin-bottom:8px;transition:border-color .15s}
.uang-row:focus-within{border-color:var(--blue)}
.uang-prefix{padding:0 12px;background:var(--bg);font-size:13px;font-weight:700;color:var(--text-muted);border-right:1.5px solid var(--border);height:44px;display:flex;align-items:center;flex-shrink:0}
.uang-input{flex:1;height:44px;padding:0 12px;border:none;font-size:16px;font-weight:700;font-family:'Outfit',sans-serif;color:var(--text);background:#fff}
.uang-input:focus{outline:none}
.quick-amounts{display:grid;grid-template-columns:repeat(3,1fr);gap:5px;margin-bottom:8px}
.quick-btn{padding:7px 4px;border:1.5px solid var(--border);background:#fff;border-radius:7px;font-size:11.5px;font-weight:700;color:var(--text-secondary);cursor:pointer;transition:all .15s;font-family:'Outfit',sans-serif}
.quick-btn:hover{border-color:var(--blue-mid);color:var(--blue);background:var(--blue-light)}
.uang-pas-btn{width:100%;padding:8px;border:1.5px solid var(--blue-mid);background:var(--blue-light);border-radius:7px;font-size:12.5px;font-weight:700;color:var(--blue);cursor:pointer;transition:all .15s;margin-bottom:12px;font-family:'Outfit',sans-serif}
.uang-pas-btn:hover{background:var(--blue);color:#fff}

.kembalian-card{background:var(--green-light);border:1.5px solid var(--green-mid);border-radius:8px;padding:10px 14px;display:flex;justify-content:space-between;align-items:center;margin-bottom:14px}
.kembalian-label{font-size:12px;font-weight:600;color:var(--green)}
.kembalian-val{font-size:17px;font-weight:700;color:var(--green)}
.kurang-card{background:var(--red-light);border:1.5px solid var(--red-mid);border-radius:8px;padding:10px 14px;display:flex;justify-content:space-between;align-items:center;margin-bottom:14px}
.kurang-label{font-size:12px;font-weight:600;color:var(--red)}
.kurang-val{font-size:17px;font-weight:700;color:var(--red)}

.btn-proses{width:100%;height:52px;background:var(--blue);color:#fff;border:none;border-radius:10px;font-size:15px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;transition:all .15s;font-family:'Outfit',sans-serif;flex-shrink:0;margin-top:auto}
.btn-proses:hover:not(:disabled){background:var(--blue-dark);transform:translateY(-1px);box-shadow:0 4px 16px rgba(37,99,235,.3)}
.btn-proses:disabled{background:#cbd5e1;cursor:not-allowed;transform:none;box-shadow:none}
.btn-proses svg{width:20px;height:20px}
.pay-footer{padding:12px 20px 16px;margin-top:auto;flex-shrink:0}

/* ── Nota Print Modal ── */
.nota-overlay{position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:500;display:flex;align-items:center;justify-content:center;opacity:0;pointer-events:none;transition:opacity .2s}
.nota-overlay.open{opacity:1;pointer-events:all}
.nota-box{background:#fff;border-radius:14px;width:360px;max-height:90vh;display:flex;flex-direction:column;box-shadow:0 20px 60px rgba(0,0,0,.2);transform:translateY(10px);transition:transform .2s}
.nota-overlay.open .nota-box{transform:translateY(0)}
.nota-header{padding:16px 20px 14px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}
.nota-header-title{font-size:15px;font-weight:700}
.nota-close{width:32px;height:32px;border-radius:8px;border:1px solid var(--border);background:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;color:var(--text-muted)}
.nota-close:hover{background:var(--bg);color:var(--text)}
.nota-preview{flex:1;overflow-y:auto;padding:20px;display:flex;justify-content:center;background:#f8fafc}
#notaContent{background:#fff;width:220px;padding:10px;font-family:'Courier New',Courier,monospace;font-size:9.5pt;color:#000;line-height:1.5;box-shadow:0 2px 12px rgba(0,0,0,.1);border-radius:4px}
.nota-footer{padding:14px 20px;border-top:1px solid var(--border);display:flex;gap:8px}
.btn-nota-print{flex:1;height:42px;background:var(--blue);color:#fff;border:none;border-radius:8px;font-size:13.5px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;font-family:'Outfit',sans-serif;transition:background .15s}
.btn-nota-print:hover{background:var(--blue-dark)}
.btn-nota-new{height:42px;padding:0 16px;background:#fff;color:var(--text);border:1.5px solid var(--border);border-radius:8px;font-size:13.5px;font-weight:700;cursor:pointer;font-family:'Outfit',sans-serif;transition:all .15s;white-space:nowrap}
.btn-nota-new:hover{border-color:var(--blue);color:var(--blue);background:var(--blue-light)}

/* ── Responsive Mobile ── */
@media (max-width:768px){
    .sidebar{position:fixed;top:0;left:0;height:100%;z-index:200;transform:translateX(-100%);transition:transform .3s cubic-bezier(.4,0,.2,1);box-shadow:4px 0 24px rgba(0,0,0,.12)}
    .sidebar.active{transform:translateX(0)}
    .hamburger{display:flex}
    body{display:flex;flex-direction:column;overflow:auto;height:auto;min-height:100vh}
    .main-content{flex:1;display:flex;flex-direction:column;overflow:visible;height:auto;min-height:0}
    .top-bar{padding:0 12px;flex-shrink:0}
    .pos-layout{display:flex !important;flex-direction:column !important;overflow:visible !important;flex:none !important;height:auto !important;}
    .pos-left{overflow:visible !important;padding:10px 10px 0;flex:none !important;height:auto !important;}
    .cart-section{max-height:320px;flex:none}
    .pos-right{border-left:none !important;border-top:1px solid var(--border);flex:none !important;height:auto !important;overflow:visible !important;}
    .pos-right > div{overflow-y:visible !important;flex:none !important;}
    .pay-footer{position:sticky;bottom:0;background:#fff;z-index:20;border-top:1px solid var(--border);box-shadow:0 -4px 16px rgba(0,0,0,.08);padding-bottom:max(16px, env(safe-area-inset-bottom));}
    .btn-proses{margin-top:12px}
    .metode-grid{grid-template-columns:repeat(3,1fr)}
}

/* ── Tablet ── */
@media (min-width:769px) and (max-width:1024px){
    :root{--sidebar-w:190px}
    .sidebar{width:190px}
    .pos-layout{grid-template-columns:1fr 280px}
    .main-content{min-width:0;overflow:hidden}
}

/* ── Nota Print CSS ── */
@media print {
    @page { size: 58mm auto; margin: 2mm 3mm; }
    html, body { margin:0 !important; padding:0 !important; background:#fff !important; }
    body > *:not(#printArea) { display:none !important; }
    #printArea {
        display: block !important;
        width: 52mm;
        font-family: 'Courier New', Courier, monospace;
        font-size: 9.5pt;
        color: #000;
        line-height: 1.5;
    }
    .print-copy { display: block; }
    .print-copy:first-child { page-break-after: always; }
    .print-center { text-align: center; }
    .print-bold   { font-weight: 700; }
    .print-hr     { border: none; border-top: 1px dashed #000; margin: 3pt 0; }
    .print-hr-solid { border: none; border-top: 1px solid #000; margin: 2pt 0; }
    .print-row    { display: flex; justify-content: space-between; }
    .print-item   { margin: 1pt 0; }
    .print-sm     { font-size: 8.5pt; }
    .print-total  { font-size: 11pt; font-weight: 700; }
    * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
}

/* Loading overlay */
.loading-overlay{position:fixed;inset:0;background:rgba(255,255,255,.8);z-index:999;display:none;align-items:center;justify-content:center;flex-direction:column;gap:12px}
.loading-overlay.show{display:flex}
.loading-spinner{width:36px;height:36px;border:3px solid var(--blue-mid);border-top-color:var(--blue);border-radius:50%;animation:spin .7s linear infinite}
.loading-text{font-size:14px;font-weight:600;color:var(--text-secondary)}
@keyframes spin{to{transform:rotate(360deg)}}

/* Toast */
.toast-container{position:fixed;bottom:20px;right:20px;z-index:999;display:flex;flex-direction:column;gap:8px;pointer-events:none}
.toast{padding:12px 16px;border-radius:9px;font-size:13px;font-weight:600;box-shadow:0 4px 20px rgba(0,0,0,.15);display:flex;align-items:center;gap:8px;transform:translateX(120%);transition:transform .25s;pointer-events:none;max-width:280px}
.toast.show{transform:translateX(0)}
.toast.success{background:var(--green);color:#fff}
.toast.error{background:var(--red);color:#fff}
.toast svg{width:16px;height:16px;flex-shrink:0}

/* Scrollbar */
::-webkit-scrollbar{width:5px;height:5px}
::-webkit-scrollbar-track{background:transparent}
::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:99px}
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
        <a href="kasir-penjualan.php" class="nav-item active" onclick="closeSidebar()">
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

<div class="main-content">
    <!-- Top bar -->
    <div class="top-bar">
        <button class="hamburger" id="hamburgerBtn" aria-label="Toggle menu">
            <span></span><span></span><span></span>
        </button>
        <div>
            <div class="page-title">Kasir Penjualan</div>
        </div>
        <div style="margin-left:auto;font-size:12.5px;color:var(--text-muted)" id="clockDisplay"></div>
    </div>

    <!-- POS Layout -->
    <div class="pos-layout">
        <!-- LEFT: scan + cart -->
        <div class="pos-left">
            <!-- Gudang selector -->
            <div class="gudang-bar">
                <span class="gudang-bar-label">Gudang</span>
                <div class="gudang-tabs">
                    <button class="gudang-tab active" onclick="setGudang('non_ppn')">Non-PPN</button>
                    <button class="gudang-tab" onclick="setGudang('ppn')">PPN</button>
                </div>
                <span style="margin-left:auto;font-size:11.5px;color:var(--text-muted)" id="produkCount">Memuat produk...</span>
                <button onclick="clearProductCache()" title="Refresh data produk" style="border:none;background:none;cursor:pointer;color:var(--text-muted);padding:3px 6px;border-radius:5px;font-size:14px;line-height:1;flex-shrink:0;transition:all .15s" onmouseover="this.style.background='var(--bg)';this.style.color='var(--blue)'" onmouseout="this.style.background='none';this.style.color='var(--text-muted)'">↻</button>
            </div>

            <!-- Scanner -->
            <div class="scanner-card">
                <div class="scanner-label">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="7" width="4" height="10"/><rect x="10" y="7" width="2" height="10"/><rect x="15" y="7" width="4" height="10"/><line x1="1" y1="5" x2="1" y2="5"/></svg>
                    Scanner / Pencarian Produk
                </div>
                <div class="scanner-row" style="position:relative">
                    <div class="scan-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></div>
                    <input type="text" id="scanInput" class="scan-input" placeholder="Scan atau ketik kode / nama produk..." autocomplete="off" autocorrect="off" spellcheck="false">
                    <div class="search-dropdown" id="searchDropdown"></div>
                </div>
                <div class="scan-demo" id="scanDemo">Ketik minimal 2 karakter atau scan QR code</div>
            </div>

            <!-- Cart -->
            <div class="cart-section">
                <div class="cart-header">
                    <span class="cart-title">🛒 Keranjang Belanja</span>
                    <span class="cart-count" id="cartCount">0 item</span>
                    <button class="btn-clear" onclick="clearCart()">Kosongkan</button>
                </div>
                <div class="cart-body" id="cartBody">
                    <div class="cart-empty" id="cartEmpty">
                        <div class="cart-empty-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 001.95-1.56l1.65-8.44H6"/></svg></div>
                        <div class="cart-empty-text">Scan produk untuk memulai transaksi</div>
                    </div>
                    <table class="cart-table" id="cartTable" style="display:none">
                        <thead>
                            <tr>
                                <th>Produk</th>
                                <th style="text-align:center">Qty</th>
                                <th style="text-align:right">Harga</th>
                                <th style="text-align:right">Subtotal</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="cartTbody"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- RIGHT: payment -->
        <div class="pos-right">
            <div class="pay-header">
                <div class="pay-header-title">Ringkasan Pembayaran</div>
            </div>

            <!-- Total -->
            <div class="total-card">
                <div class="total-row">
                    <span class="total-label" id="totalItemLabel">Total (0 item)</span>
                    <span id="totalItemVal">Rp 0</span>
                </div>
                <div class="total-main">
                    <span class="total-main-label">Total Pembayaran</span>
                    <span class="total-main-val" id="totalBayar">Rp 0</span>
                </div>
            </div>

            <div style="flex:1;overflow-y:auto;padding:0 0 8px">
                <!-- Metode -->
                <div class="pay-section" style="margin-top:14px">
                    <div class="pay-section-label">Metode Pembayaran</div>
                    <div class="metode-grid">
                        <button class="metode-btn active" data-metode="Tunai" onclick="setMetode(this,'Tunai')">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2"/></svg>
                            <span>Tunai</span>
                        </button>
                        <button class="metode-btn" data-metode="Transfer" onclick="setMetode(this,'Transfer')">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                            <span>Transfer</span>
                        </button>
                        <button class="metode-btn" data-metode="QRIS" onclick="setMetode(this,'QRIS')">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="3" height="3"/><rect x="19" y="14" width="2" height="2"/><rect x="14" y="19" width="2" height="2"/><rect x="18" y="18" width="3" height="3"/></svg>
                            <span>QRIS</span>
                        </button>
                    </div>
                </div>

                <!-- Keterangan -->
                <div class="pay-section">
                    <div class="pay-section-label">Keterangan Transaksi</div>
                    <div class="ket-grid">
                        <button class="ket-btn active" data-ket="Toko" onclick="setKet(this,'Toko')">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                            <span>Toko</span>
                        </button>
                        <button class="ket-btn" data-ket="Online" onclick="setKet(this,'Online')">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"/></svg>
                            <span>Online</span>
                        </button>
                    </div>
                </div>

                <!-- Waktu Transaksi -->
                <div class="pay-section">
                    <div class="pay-section-label">Waktu Transaksi</div>
                    <div class="waktu-section">
                        <div class="waktu-section-label">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            Atur Waktu (opsional)
                        </div>
                        <div class="waktu-row">
                            <input type="datetime-local" id="waktuInput" class="waktu-input"
                                oninput="onWaktuInput()" title="Kosongkan untuk menggunakan waktu sekarang saat tombol Proses ditekan">
                            <button class="btn-waktu-reset" onclick="resetWaktu()" title="Reset ke waktu sekarang">Reset</button>
                        </div>
                        <div class="waktu-hint" id="waktuHint">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="11" height="11"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                            <span id="waktuHintText">Otomatis: waktu saat Proses ditekan</span>
                        </div>
                    </div>
                </div>

                <!-- Nama Pembeli (muncul otomatis saat ada diskon) -->
                <div class="customer-section" id="customerSection">
                    <div class="customer-section-label">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        Nama Pembeli
                    </div>
                    <input type="text" id="customerInput" class="customer-input" placeholder="Ketik nama pembeli..." autocomplete="off">
                </div>

                <!-- Uang diterima (hanya Tunai) -->
                <div class="pay-section" id="uangSection">
                    <div class="pay-section-label">Uang Diterima</div>
                    <div class="uang-row">
                        <div class="uang-prefix">Rp</div>
                        <input type="text" id="uangInput" class="uang-input" placeholder="0" oninput="onUangInput()" autocomplete="off">
                    </div>
                    <div class="quick-amounts">
                        <button class="quick-btn" onclick="addUang(50000)">+50K</button>
                        <button class="quick-btn" onclick="addUang(100000)">+100K</button>
                        <button class="quick-btn" onclick="addUang(200000)">+200K</button>
                    </div>
                    <button class="uang-pas-btn" id="uangPasBtn" onclick="setUangPas()">Uang Pas: Rp 0</button>
                    <div class="kembalian-card" id="kembalianCard" style="display:none">
                        <span class="kembalian-label">💵 Kembalian</span>
                        <span class="kembalian-val" id="kembalianVal">Rp 0</span>
                    </div>
                    <div class="kurang-card" id="kurangCard" style="display:none">
                        <span class="kurang-label">⚠️ Kurang</span>
                        <span class="kurang-val" id="kurangVal">Rp 0</span>
                    </div>
                </div>
            </div>

            <!-- Tombol proses -->
            <div class="pay-footer">
                <button class="btn-proses" id="btnProses" onclick="prosesPembayaran()" disabled>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                    Proses Pembayaran
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Nota modal -->
<div class="nota-overlay" id="notaOverlay">
    <div class="nota-box">
        <div class="nota-header">
            <span class="nota-header-title">🧾 Nota Transaksi</span>
            <button class="nota-close" onclick="closeNota()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div class="nota-preview"><div id="notaContent"></div></div>
        <div class="nota-footer">
            <button class="btn-nota-print" onclick="printNota()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                Cetak Nota
            </button>
            <button class="btn-nota-new" onclick="transaksiBarupasca()">Transaksi Baru</button>
        </div>
    </div>
</div>

<!-- Hidden print area -->
<div id="printArea" style="display:none"></div>

<!-- Loading -->
<div class="loading-overlay" id="loadingOverlay">
    <div class="loading-spinner"></div>
    <div class="loading-text" id="loadingText">Memproses...</div>
</div>

<!-- Toast container -->
<div class="toast-container" id="toastContainer"></div>

<script>
// ================================================================
// STATE
// ================================================================
const SPREADSHEET_ID = '1zQtkYOkwWjPPll_D0u7pJNTYebWGTMl3ilPwnEIWVKQ';
let activeGudang   = localStorage.getItem('kasir_gudang') || 'non_ppn';
let allProducts    = [];
let cart           = [];
let activeMetode   = 'Tunai';
let activeKet      = 'Toko';
let lastNoNota     = '';
let activeCostumer = '';
let activeWaktu    = '';

// ── Restore keranjang dari sessionStorage ──
(function restoreSession() {
    const fromIndex = localStorage.getItem('stokpro_cart');
    if (fromIndex) {
        try {
            const parsed = JSON.parse(fromIndex);
            if (Array.isArray(parsed) && parsed.length > 0) {
                cart = parsed;
                if (parsed[0].gudang) activeGudang = parsed[0].gudang;
            }
        } catch(e) {}
        localStorage.removeItem('stokpro_cart');
        return;
    }
    try {
        const s = sessionStorage.getItem('kasir_penjualan_cart');
        if (s) {
            const parsed = JSON.parse(s);
            if (Array.isArray(parsed) && parsed.length > 0) {
                cart = parsed;
                if (parsed[0].gudang) activeGudang = parsed[0].gudang;
            }
        }
        const m = sessionStorage.getItem('kasir_penjualan_metode');
        if (m) activeMetode = m;
        const k = sessionStorage.getItem('kasir_penjualan_ket');
        if (k) activeKet = k;
        const cs = sessionStorage.getItem('kasir_penjualan_costumer');
        if (cs) activeCostumer = cs;
        const w = sessionStorage.getItem('kasir_penjualan_waktu');
        if (w) activeWaktu = w;
    } catch(e) {}
})();

function saveCartSession() {
    try {
        sessionStorage.setItem('kasir_penjualan_cart',     JSON.stringify(cart));
        sessionStorage.setItem('kasir_penjualan_metode',   activeMetode);
        sessionStorage.setItem('kasir_penjualan_ket',      activeKet);
        sessionStorage.setItem('kasir_penjualan_costumer', activeCostumer);
        sessionStorage.setItem('kasir_penjualan_waktu',    activeWaktu);
    } catch(e) {}
}

function clearCartSession() {
    try {
        ['kasir_penjualan_cart','kasir_penjualan_metode','kasir_penjualan_ket',
         'kasir_penjualan_costumer','kasir_penjualan_waktu'].forEach(k => sessionStorage.removeItem(k));
    } catch(e) {}
}

// ================================================================
// INIT
// ================================================================
document.addEventListener('DOMContentLoaded', () => {
    updateGudangUI();
    loadProducts();
    updateClock();
    setInterval(updateClock, 1000);
    renderCart();
    restoreMetodeKetUI();

    const inp = document.getElementById('scanInput');
    inp.addEventListener('input', onScanInput);
    inp.addEventListener('keydown', onScanKeydown);
    document.addEventListener('click', e => {
        if (!e.target.closest('.scanner-row')) closeDropdown();
    });

    document.getElementById('customerInput').addEventListener('input', function() {
        activeCostumer = this.value.trim();
        saveCartSession();
    });

    if (activeWaktu) {
        document.getElementById('waktuInput').value = activeWaktu;
        document.getElementById('waktuInput').classList.add('custom');
        updateWaktuHint(activeWaktu);
    }

    inp.focus();
});

function restoreMetodeKetUI() {
    document.querySelectorAll('.metode-btn').forEach(b => {
        b.classList.toggle('active', b.dataset.metode === activeMetode);
    });
    document.getElementById('uangSection').style.display = activeMetode === 'Tunai' ? '' : 'none';
    document.querySelectorAll('.ket-btn').forEach(b => {
        b.classList.toggle('active', b.dataset.ket === activeKet);
    });
    if (activeCostumer) {
        document.getElementById('customerInput').value = activeCostumer;
        document.getElementById('customerSection').classList.add('show');
    }
}

function updateClock() {
    const now = new Date();
    document.getElementById('clockDisplay').textContent =
        now.toLocaleDateString('id-ID',{weekday:'long',day:'numeric',month:'long',year:'numeric'}) +
        ' · ' + now.toLocaleTimeString('id-ID',{hour:'2-digit',minute:'2-digit',second:'2-digit'});
}

// ================================================================
// WAKTU TRANSAKSI
// ================================================================
function onWaktuInput() {
    const val = document.getElementById('waktuInput').value;
    activeWaktu = val;
    const inp = document.getElementById('waktuInput');
    if (val) {
        inp.classList.add('custom');
        updateWaktuHint(val);
    } else {
        inp.classList.remove('custom');
        document.getElementById('waktuHintText').textContent = 'Otomatis: waktu saat Proses ditekan';
    }
    saveCartSession();
}

function resetWaktu() {
    activeWaktu = '';
    const inp = document.getElementById('waktuInput');
    inp.value = '';
    inp.classList.remove('custom');
    document.getElementById('waktuHintText').textContent = 'Otomatis: waktu saat Proses ditekan';
    saveCartSession();
}

function updateWaktuHint(val) {
    if (!val) return;
    const d = new Date(val);
    const formatted = d.toLocaleString('id-ID', {
        weekday:'long', day:'numeric', month:'long', year:'numeric',
        hour:'2-digit', minute:'2-digit'
    });
    document.getElementById('waktuHintText').textContent = 'Manual: ' + formatted;
}

function getWaktuTransaksi() {
    let d;
    if (activeWaktu) {
        d = new Date(activeWaktu);
        if (isNaN(d.getTime())) d = new Date();
    } else {
        d = new Date();
    }
    const pad = n => String(n).padStart(2, '0');
    return `${pad(d.getDate())}/${pad(d.getMonth()+1)}/${d.getFullYear()} ` +
           `${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;
}

// ================================================================
// GUDANG
// ================================================================
function clearProductCache() {
    localStorage.removeItem('srm_produk_non_ppn');
    localStorage.removeItem('srm_produk_ppn');
    loadProducts();
    showToast('Data produk diperbarui', 'success');
}

function setGudang(sheet) {
    if (activeGudang === sheet) return;
    if (cart.length > 0 && !confirm('Ganti gudang akan mengosongkan keranjang. Lanjutkan?')) return;
    activeGudang = sheet;
    cart = [];
    localStorage.setItem('kasir_gudang', sheet);
    updateGudangUI();
    loadProducts();
    renderCart();
}

function updateGudangUI() {
    document.querySelectorAll('.gudang-tab').forEach(btn => {
        btn.classList.toggle('active',
            (activeGudang === 'non_ppn' && btn.textContent.trim() === 'Non-PPN') ||
            (activeGudang === 'ppn' && btn.textContent.trim() === 'PPN'));
    });
}

// ================================================================
// LOAD PRODUCTS
// ================================================================
async function loadProducts() {
    const CACHE_KEY = 'srm_produk_' + activeGudang;
    const CACHE_TTL = 5 * 60 * 1000;
    try {
        const cached = JSON.parse(localStorage.getItem(CACHE_KEY) || 'null');
        if (cached && (Date.now() - cached.ts < CACHE_TTL)) {
            allProducts = cached.data;
            document.getElementById('produkCount').textContent = allProducts.length + ' produk';
            return;
        }
    } catch(e) {}

    document.getElementById('produkCount').textContent = 'Memuat...';
    const url = `https://opensheet.elk.sh/${SPREADSHEET_ID}/${activeGudang}`;
    try {
        const res  = await fetch(url);
        const data = await res.json();
        allProducts = Array.isArray(data) ? data : [];
        document.getElementById('produkCount').textContent = allProducts.length + ' produk';
        try { localStorage.setItem(CACHE_KEY, JSON.stringify({ data: allProducts, ts: Date.now() })); } catch(e) {}
    } catch(e) {
        document.getElementById('produkCount').textContent = 'Gagal memuat';
        showToast('Gagal memuat data produk', 'error');
    }
}

// ================================================================
// SEARCH
// ================================================================
let dropdownIdx = -1;
let searchItems = [];

function norm(s) { return (s || '').toLowerCase().replace(/-/g, ''); }

function onScanInput() {
    const raw = document.getElementById('scanInput').value.trim().toLowerCase();
    if (raw.length < 2) { closeDropdown(); return; }
    const words = raw.split(/\s+/).filter(w => w.length > 0);
searchItems = allProducts.filter(p => {
        const fields = [p.kode_barang, p.kode_internal, p.nama_produk,
            p.merk, p.nama_mobil, p.supplier, p.nama_lain /* nama lain */].map(f => (f || '').toLowerCase());
        const fieldsNorm = fields.map(f => f.replace(/-/g, ''));
        return words.every(w => {
            const wNorm = w.replace(/-/g, '');
            return fields.some(f => f.includes(w)) || fieldsNorm.some(f => f.includes(wNorm));
        });
    }).slice(0, 15);
    renderDropdown(searchItems, raw, norm(raw));
}

function renderDropdown(items, q, qNorm) {
    const dd = document.getElementById('searchDropdown');
    if (!items.length) {
        dd.innerHTML = `<div class="search-empty">Produk tidak ditemukan</div>`;
        dd.classList.add('open'); dropdownIdx = -1; return;
    }
    const stokClass = s => parseInt(s||0) === 0 ? 'stok-nol' : parseInt(s||0) <= 5 ? 'stok-low' : 'stok-ok';
    const stokLabel = s => parseInt(s||0) === 0 ? 'Habis' : 'Stok: ' + s;
    dd.innerHTML = items.map((p, i) => `
        <div class="search-item" data-idx="${i}" onmousedown="addFromSearch(${i})">
            <div class="search-item-info">
                <div class="search-item-name">${highlight(p.nama_produk || '-', q, qNorm)}</div>
                <div class="search-item-sub">
                    ${p.kode_barang ? `<span style="color:var(--text-secondary);font-family:monospace">${highlight(p.kode_barang, q, qNorm)}</span>` : ''}
                    ${p.merk ? ` · ${highlight(p.merk, q, qNorm)}` : ''}
                    ${p.nama_mobil ? ` · ${highlight(p.nama_mobil, q, qNorm)}` : ''}
                    ${p.supplier ? ` · <span style="color:var(--text-muted)">${highlight(p.supplier, q, qNorm)}</span>` : ''}
${p.lokasi_rak ? ` · <span style="background:#f5f3ff;color:#7c3aed;font-weight:700;padding:1px 5px;border-radius:4px;font-size:10px">📦 ${highlight(p.lokasi_rak, q, qNorm)}</span>` : ''}
                </div>
            </div>
            <span class="search-item-stok ${stokClass(p.stok)}">${stokLabel(p.stok)}</span>
            <span class="search-item-price">${formatRp(p.harga_jual || 0)}</span>
        </div>`).join('');
    dd.classList.add('open');
    dropdownIdx = -1;
}

function highlight(text, q, qNorm) {
    if (!text) return '';
    if (!q) return text;
    const escaped = q.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    const re = new RegExp(`(${escaped})`, 'gi');
    if (re.test(text)) return text.replace(re, '<strong style="color:var(--blue)">$1</strong>');
    if (qNorm) {
        const escapedNorm = qNorm.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        const textNorm    = text.replace(/-/g, '');
        const reNorm      = new RegExp(escapedNorm, 'gi');
        const matchPos    = [];
        let m;
        while ((m = reNorm.exec(textNorm)) !== null) matchPos.push([m.index, m.index + m[0].length]);
        if (matchPos.length) {
            let result = '', normIdx = 0, inHL = false;
            for (let i = 0; i < text.length; i++) {
                const ch = text[i], isDash = ch === '-';
                const active = !isDash && matchPos.some(([s, e]) => normIdx >= s && normIdx < e);
                if (active && !inHL)  { result += '<strong style="color:var(--blue)">'; inHL = true; }
                if (!active && inHL)  { result += '</strong>'; inHL = false; }
                result += ch;
                if (!isDash) normIdx++;
            }
            if (inHL) result += '</strong>';
            return result;
        }
    }
    return text;
}

function onScanKeydown(e) {
    const dd = document.getElementById('searchDropdown');
    if (!dd.classList.contains('open')) {
        if (e.key === 'Enter') {
            const q = document.getElementById('scanInput').value.trim(), qNorm = norm(q);
            const exact = allProducts.find(p =>
                norm(p.kode_barang) === qNorm || norm(p.kode_internal) === qNorm ||
                (p.kode_barang||'') === q || (p.kode_internal||'') === q);
            if (exact) { addToCart(exact); document.getElementById('scanInput').value = ''; }
        }
        return;
    }
    const items = dd.querySelectorAll('.search-item');
    if (e.key === 'ArrowDown') { e.preventDefault(); dropdownIdx = Math.min(dropdownIdx+1, items.length-1); highlightDropdown(items); }
    else if (e.key === 'ArrowUp') { e.preventDefault(); dropdownIdx = Math.max(dropdownIdx-1, 0); highlightDropdown(items); }
    else if (e.key === 'Enter') { e.preventDefault(); if (dropdownIdx >= 0) addFromSearch(dropdownIdx); else if (searchItems.length === 1) addFromSearch(0); }
    else if (e.key === 'Escape') closeDropdown();
}

function highlightDropdown(items) {
    items.forEach((el, i) => el.classList.toggle('focused', i === dropdownIdx));
    if (dropdownIdx >= 0) items[dropdownIdx]?.scrollIntoView({block:'nearest'});
}

function addFromSearch(idx) {
    const p = searchItems[idx];
    if (!p) return;
    if (parseInt(p.stok || 0) <= 0) { showToast('Stok produk ini habis!', 'error'); closeDropdown(); return; }
    addToCart(p);
    document.getElementById('scanInput').value = '';
    closeDropdown();
}

function closeDropdown() {
    document.getElementById('searchDropdown').classList.remove('open');
    dropdownIdx = -1;
}

// ================================================================
// PRICE OVERRIDE & DISCOUNT
// ================================================================
function effectivePrice(item) {
    return item.harga_custom !== undefined && item.harga_custom !== null
        ? item.harga_custom : parseInt(item.harga_jual || 0);
}

function discountPct(item) {
    const original = parseInt(item.harga_jual || 0);
    if (!item.harga_custom || item.harga_custom >= original || original === 0) return 0;
    return Math.round((1 - item.harga_custom / original) * 1000) / 10;
}

function updatePrice(kode, supplier, rawValue) {
    const num  = parseInt(String(rawValue).replace(/\D/g, '')) || 0;
    const item = cart.find(c => c.kode_barang === kode && c.supplier === supplier);
    if (!item) return;
    const original = parseInt(item.harga_jual || 0);
    item.harga_custom = (num === original) ? null : num;
    checkCustomerSection();
    renderCart();
    saveCartSession();
}

function checkCustomerSection() {
    const hasDiscount = cart.some(c => discountPct(c) > 0);
    const section = document.getElementById('customerSection');
    if (hasDiscount) {
        section.classList.add('show');
        activeCostumer = document.getElementById('customerInput').value.trim();
    } else {
        section.classList.remove('show');
        activeCostumer = '';
        document.getElementById('customerInput').value = '';
    }
    saveCartSession();
}

// ================================================================
// CART OPERATIONS
// ================================================================
function addToCart(product) {
    const existing = cart.find(c =>
        c.kode_barang === product.kode_barang &&
        c.supplier === product.supplier
    );
    const maxStok = parseInt(product.stok || 0);
    if (existing) {
        if (existing.qty >= maxStok) {
            showToast(`Stok hanya ${maxStok}!`, 'error');
            return;
        }
        existing.qty++;
    } else {
        cart.push({
            ...product,
            qty: 1,
            gudang: activeGudang,
            supplier: product.supplier
        });
    }
    renderCart();
    saveCartSession();
    showToast(`${(product.nama_produk || '').substring(0,30)} ditambahkan`, 'success');
}

function removeFromCart(kode, supplier) {
    cart = cart.filter(c =>
        !(c.kode_barang === kode && c.supplier === supplier)
    );
    checkCustomerSection();
    renderCart();
    saveCartSession();
}

// ✅ FIX: semua 3 parameter (kode, supplier, newQty) selalu diteruskan dengan benar
function updateQty(kode, supplier, newQty) {
    const item = cart.find(c =>
        c.kode_barang === kode && c.supplier === supplier
    );
    if (!item) return;
    item.qty = Math.max(1, Math.min(parseInt(newQty) || 1, parseInt(item.stok || 0)));
    renderCart();
    saveCartSession();
}

function clearCart() {
    if (!cart.length) return;
    if (!confirm('Kosongkan semua keranjang?')) return;
    cart = [];
    activeCostumer = '';
    document.getElementById('customerInput').value = '';
    document.getElementById('customerSection').classList.remove('show');
    renderCart();
    saveCartSession();
}

// ================================================================
// RENDER CART
// ================================================================
function renderCart() {
    const tbody   = document.getElementById('cartTbody');
    const empty   = document.getElementById('cartEmpty');
    const table   = document.getElementById('cartTable');
    const countEl = document.getElementById('cartCount');
    const total   = cart.reduce((s, c) => s + effectivePrice(c) * c.qty, 0);
    const count   = cart.reduce((s, c) => s + c.qty, 0);

    countEl.textContent = count + ' item';
    document.getElementById('totalItemLabel').textContent = `Total (${count} item)`;
    document.getElementById('totalItemVal').textContent   = formatRp(total);
    document.getElementById('totalBayar').textContent     = formatRp(total);
    document.getElementById('uangPasBtn').textContent     = `Uang Pas: ${formatRp(total)}`;
    document.getElementById('btnProses').disabled = cart.length === 0;
    updateKembalian();

    if (!cart.length) { empty.style.display=''; table.style.display='none'; return; }
    empty.style.display='none'; table.style.display='';

    tbody.innerHTML = cart.map(item => {
        const hargaAsli      = parseInt(item.harga_jual || 0);
        const hargaEff       = effectivePrice(item);
        const pct            = discountPct(item);
        const subtotal       = hargaEff * item.qty;
        const stok           = parseInt(item.stok || 0);
        const isDiskon       = pct > 0;
        const hargaFormatted = hargaEff > 0 ? hargaEff.toLocaleString('id-ID') : '';

        // ✅ FIX: escapeAttr untuk mencegah masalah jika supplier mengandung karakter khusus
        const kodeAttr     = escAttr(item.kode_barang);
        const supplierAttr = escAttr(item.supplier);

        return `<tr>
            <td>
                <div class="cart-prod-name">${item.nama_produk || '-'}</div>
                <div class="cart-prod-sub">${[item.merk, item.nama_mobil].filter(Boolean).join(' · ') || item.kode_barang}</div>
            </td>
            <td style="text-align:center">
                <div class="qty-control">
                    <button class="qty-btn" onclick="updateQty('${kodeAttr}','${supplierAttr}',${item.qty - 1})">−</button>
                    <input type="number" class="qty-val" value="${item.qty}" min="1" max="${stok}"
                        onchange="updateQty('${kodeAttr}','${supplierAttr}',parseInt(this.value)||1)"
                        onfocus="this.select()">
                    <button class="qty-btn" onclick="updateQty('${kodeAttr}','${supplierAttr}',${item.qty + 1})">+</button>
                </div>
            </td>
            <td style="text-align:right">
                <div class="price-edit-wrap">
                    <input type="text" class="price-input${isDiskon ? ' discounted' : ''}"
                        value="${hargaFormatted}"
                        title="Edit harga untuk diskon"
                        oninput="this.value=this.value.replace(/[^0-9.]/g,'')"
                        onblur="updatePrice('${kodeAttr}','${supplierAttr}',this.value)"
                        onkeydown="if(event.key==='Enter'){this.blur()}"
                        onfocus="this.select()">
                    ${isDiskon
                        ? `<span class="price-original">${formatRp(hargaAsli)}</span>
                           <span class="discount-badge">−${pct}%</span>`
                        : ''}
                </div>
            </td>
            <td><div class="cart-subtotal">${formatRp(subtotal)}</div></td>
            <td>
                <button class="btn-remove" onclick="removeFromCart('${kodeAttr}','${supplierAttr}')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 011-1h4a1 1 0 011 1v2"/></svg>
                </button>
            </td>
        </tr>`;
    }).join('');
}

// Helper: escape single quotes untuk inline HTML attribute
function escAttr(str) {
    return (str || '').replace(/\\/g, '\\\\').replace(/'/g, "\\'");
}

// ================================================================
// PAYMENT
// ================================================================
function setMetode(el, metode) {
    activeMetode = metode;
    document.querySelectorAll('.metode-btn').forEach(b => b.classList.toggle('active', b === el));
    document.getElementById('uangSection').style.display = metode === 'Tunai' ? '' : 'none';
    saveCartSession();
}

function setKet(el, ket) {
    activeKet = ket;
    document.querySelectorAll('.ket-btn').forEach(b => b.classList.toggle('active', b === el));
    saveCartSession();
}

function onUangInput() {
    const raw = document.getElementById('uangInput').value.replace(/\D/g,'');
    document.getElementById('uangInput').value = raw;
    updateKembalian();
}

function addUang(amount) {
    const cur = parseInt(document.getElementById('uangInput').value.replace(/\D/g,'') || '0');
    document.getElementById('uangInput').value = cur + amount;
    updateKembalian();
}

function setUangPas() {
    const total = cart.reduce((s,c) => s + effectivePrice(c)*c.qty, 0);
    document.getElementById('uangInput').value = total;
    updateKembalian();
}

function updateKembalian() {
    if (activeMetode !== 'Tunai') {
        document.getElementById('kembalianCard').style.display = 'none';
        document.getElementById('kurangCard').style.display    = 'none';
        return;
    }
    const total = cart.reduce((s,c) => s + effectivePrice(c)*c.qty, 0);
    const uang  = parseInt(document.getElementById('uangInput')?.value?.replace(/\D/g,'') || '0');
    const diff  = uang - total;
    document.getElementById('kembalianCard').style.display = diff >= 0 && uang > 0 ? '' : 'none';
    document.getElementById('kurangCard').style.display    = diff < 0 && uang > 0  ? '' : 'none';
    if (diff >= 0) document.getElementById('kembalianVal').textContent = formatRp(diff);
    else           document.getElementById('kurangVal').textContent    = formatRp(Math.abs(diff));
}

// ================================================================
// PROSES PEMBAYARAN
// ================================================================
async function prosesPembayaran() {
    if (!cart.length) return;
    if (activeMetode === 'Tunai') {
        const total = cart.reduce((s,c) => s + effectivePrice(c)*c.qty, 0);
        const uang  = parseInt(document.getElementById('uangInput').value.replace(/\D/g,'') || '0');
        if (uang > 0 && uang < total) {
            if (!confirm('Uang diterima kurang dari total. Lanjutkan?')) return;
        }
    }

    activeCostumer = document.getElementById('customerInput').value.trim();
    const waktuTransaksi = getWaktuTransaksi();

    const now    = new Date();
    const noNota = 'SRM-' + '26' +
        String(now.getMonth()+1).padStart(2,'0') + String(now.getDate()).padStart(2,'0') +
        '-' + String(Math.floor(Math.random()*900)+100);
    const tanggal = now.toLocaleDateString('id-ID',{day:'2-digit',month:'2-digit',year:'numeric'});
    const total   = cart.reduce((s,c) => s + effectivePrice(c)*c.qty, 0);
    lastNoNota    = noNota;

    const uangDiterima = activeMetode === 'Tunai'
        ? parseInt(document.getElementById('uangInput')?.value?.replace(/\D/g,'') || '0')
        : 0;

    const payload = {
        action:           'jual',
        gudang:           activeGudang,
        no_nota:          noNota,
        tanggal:          tanggal,
        waktu_transaksi:  waktuTransaksi,
        metode_bayar:     activeMetode,
        keterangan:       activeKet,
        costumer:         activeCostumer,
        total:            total,
        uang_diterima:    uangDiterima,
        items: cart.map(c => ({
            kode_barang:   c.kode_barang   || '',
            kode_internal: c.kode_internal || '',
            nama_produk:   c.nama_produk   || '',
            nama_mobil:    c.nama_mobil    || '',
            merk:          c.merk          || '',
            nama_lain:     c.nama_lain      || '',
            supplier:      c.supplier      || '',
            qty:           c.qty,
            harga_jual:    effectivePrice(c),
            harga_asli:    parseInt(c.harga_jual || 0),
            diskon_pct:    discountPct(c),
        })),
    };

    showLoading('Menyimpan transaksi...');
    try {
        const res  = await fetch('kasir-submit.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        });
        const json = await res.json();
        hideLoading();
        if (!json.success) throw new Error(json.message || 'Gagal');

        showNota(noNota, tanggal, waktuTransaksi, total, uangDiterima);

        cart = [];
        clearCartSession();
        renderCart();
    } catch(e) {
        hideLoading();
        showToast('Error: ' + e.message, 'error');
    }
}

// ================================================================
// NOTA
// ================================================================
function buildNotaHTML(noNota, tanggal, waktuTransaksi, total, uangDiterima) {
    const kembalian = uangDiterima > 0 ? uangDiterima - total : 0;

    let html = `<div style="text-align:center;font-weight:700;font-size:11pt">SRI REJEKI MOTOR</div>
<div style="text-align:center;font-size:6.5pt">Jl. Kimangun Sarkoro, Jawa Timur 66233</div>
<div style="text-align:center;font-size:6.5pt">Telp: 0812-3400-0225</div>
<div style="border-top:1px solid #000;margin:4pt 0"></div>
<table style="width:100%;font-size:9pt">
  <tr><td>No. Nota</td><td>:</td><td><b>${noNota}</b></td></tr>
  <tr><td>Tanggal</td><td>:</td><td>${tanggal}</td></tr>
  <tr><td>Waktu</td><td>:</td><td>${waktuTransaksi}</td></tr>
  <tr><td>Metode</td><td>:</td><td>${activeMetode}</td></tr>
  <tr><td>Ket.</td><td>:</td><td>${activeKet}</td></tr>
  ${activeCostumer ? `<tr><td>Pembeli</td><td>:</td><td><b>${activeCostumer}</b></td></tr>` : ''}
</table>
<div style="border-top:1px dashed #000;margin:4pt 0"></div>`;

    cart.forEach(item => {
        const hargaEff  = effectivePrice(item);
        const hargaAsli = parseInt(item.harga_jual || 0);
        const pct       = discountPct(item);
        const sub       = [item.merk, item.nama_mobil].filter(Boolean).join(' / ');

        html += `<div style="font-weight:700;font-size:10pt;margin-top:3pt">${item.nama_produk || '-'}</div>`;
        if (sub) html += `<div style="font-size:8.5pt;color:#444">${sub}</div>`;
        if (item.kode_barang) html += `<div style="font-size:8pt;color:#666;font-family:'Courier New',monospace">${item.kode_barang}</div>`;
        if (pct > 0) {
            html += `<div style="font-size:8pt;color:#888;text-decoration:line-through">${formatRpRaw(hargaAsli)} /pcs</div>`;
            html += `<div style="font-size:8pt;color:#ea580c;font-weight:700">Diskon ${pct}% → ${formatRpRaw(hargaEff)} /pcs</div>`;
        }
        html += `<div style="display:flex;justify-content:space-between;font-size:9.5pt;margin-bottom:2pt">
            <span>${item.qty} x ${formatRpRaw(hargaEff)}</span>
            <span><b>${formatRpRaw(hargaEff * item.qty)}</b></span>
        </div>`;
    });

    html += `<div style="border-top:1px solid #000;margin:4pt 0"></div>
<div style="display:flex;justify-content:space-between;font-weight:700;font-size:10.5pt">
    <span>TOTAL</span><span>${formatRpRaw(total)}</span>
</div>`;

    if (activeMetode === 'Tunai' && uangDiterima > 0) {
        html += `<div style="display:flex;justify-content:space-between;font-size:9pt;margin-top:2pt">
            <span>Uang Diterima</span><span>${formatRpRaw(uangDiterima)}</span>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:9pt">
            <span>Kembalian</span><span><b>${formatRpRaw(Math.max(0,kembalian))}</b></span>
        </div>`;
    }

    html += `<div style="border-top:1px dashed #000;margin:6pt 0"></div>
<div style="text-align:center;font-size:8.5pt">Terima kasih telah berbelanja!</div>
<div style="text-align:center;font-size:8pt;color:#555">Barang yang sudah dibeli tidak</div>
<div style="text-align:center;font-size:8pt;color:#555">dapat dikembalikan</div>`;

    return html;
}

function showNota(noNota, tanggal, waktuTransaksi, total, uangDiterima) {
    document.getElementById('notaContent').innerHTML = buildNotaHTML(noNota, tanggal, waktuTransaksi, total, uangDiterima);
    document.getElementById('notaOverlay').classList.add('open');
}

function closeNota() {
    document.getElementById('notaOverlay').classList.remove('open');
}

function printNota() {
    const printArea = document.getElementById('printArea');
    const nota      = document.getElementById('notaContent').innerHTML;
    printArea.innerHTML =
        '<div class="print-copy">' + nota + '</div>' +
        '<div class="print-copy">' + nota + '</div>';
    printArea.style.display = 'block';
    window.print();
    printArea.style.display = 'none';

    activeCostumer = '';
    document.getElementById('customerInput').value = '';
    document.getElementById('customerSection').classList.remove('show');
    saveCartSession();
}

function transaksiBarupasca() {
    closeNota();
    cart = [];
    activeCostumer = '';
    activeWaktu    = '';
    document.getElementById('customerInput').value = '';
    document.getElementById('customerSection').classList.remove('show');
    document.getElementById('waktuInput').value = '';
    document.getElementById('waktuInput').classList.remove('custom');
    document.getElementById('waktuHintText').textContent = 'Otomatis: waktu saat Proses ditekan';
    clearCartSession();
    document.getElementById('uangInput').value = '';
    document.getElementById('kembalianCard').style.display = 'none';
    document.getElementById('kurangCard').style.display    = 'none';
    renderCart();
    loadProducts();
    document.getElementById('scanInput').focus();
    showToast('Transaksi berhasil! Stok diperbarui.', 'success');
}

// ================================================================
// UTILS
// ================================================================
function formatRp(val) {
    const n = parseInt(String(val).replace(/\D/g,'')) || 0;
    return 'Rp ' + n.toLocaleString('id-ID');
}
function formatRpRaw(val) {
    const n = parseInt(val) || 0;
    return 'Rp ' + n.toLocaleString('id-ID');
}
function showLoading(txt='Memproses...') {
    document.getElementById('loadingText').textContent = txt;
    document.getElementById('loadingOverlay').classList.add('show');
}
function hideLoading() {
    document.getElementById('loadingOverlay').classList.remove('show');
}
function showToast(msg, type='success') {
    const c = document.getElementById('toastContainer');
    const t = document.createElement('div');
    const icon = type === 'success'
        ? '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>'
        : '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>';
    t.className = `toast ${type}`;
    t.innerHTML = icon + msg;
    c.appendChild(t);
    requestAnimationFrame(() => requestAnimationFrame(() => t.classList.add('show')));
    setTimeout(() => { t.classList.remove('show'); setTimeout(() => t.remove(), 300); }, 3000);
}

// ── Sidebar Mobile Toggle ──
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
</script>
</body>
</html>