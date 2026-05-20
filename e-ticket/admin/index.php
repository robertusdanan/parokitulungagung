<?php
// ============================================================
// ADMIN/INDEX.PHP — Admin Panel dengan Auth + Seat Manager
// Versi khusus yang mem-embed SEMUA CSS & JS secara inline.
// Tidak ada external <link> atau <script src=""> sehingga
// error 525 SSL dari Cloudflare tidak bisa memblokir halaman.
// ============================================================

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config.php';

// URL builder
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$base = $protocol . $host;
$currentUrl = $base . $_SERVER['REQUEST_URI'];

// ── Helper: baca file JS/CSS, fallback string kosong jika tidak ada ──
function readAsset(string $path): string {
    $full = __DIR__ . '/../' . ltrim($path, '/');
    return file_exists($full) ? file_get_contents($full) : "/* FILE NOT FOUND: {$path} */";
}

// ── Kumpulkan aset ────────────────────────────────────────────────────
$cssAdmin  = readAsset('css/admin-style.css');
// Coba baca dari file lokal dulu; jika tidak ada, gunakan CDN sebagai fallback
$jsQrCode  = readAsset('js/vendor/qrcode.min.js'); // simpan dari: https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js
$qrCodeMissing = (strpos($jsQrCode, 'FILE NOT FOUND') !== false || trim($jsQrCode) === '');
$jsAll     = readAsset('js/supabase.js') . "\n" . readAsset('js/admin.js');
?>

<?php if (!isAdminLoggedIn()): ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- OPEN GRAPH -->
    <meta property="og:title" content="Admin Ticketing - Berbincang Dengan Romo Eko">
    <meta property="og:description" content="Halaman Admin Ticket Event">

    <meta property="og:image" content="<?= $base ?>/img/ogpreview/default.jpg">
    <meta property="og:image:width" content="630">
    <meta property="og:image:height" content="630">

    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= $currentUrl ?>">
    <meta property="fb:app_id" content="943090234902556">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" href="/favicon.ico?v=2">
    <title>Admin Login — Ticketing Management</title>

    <!-- ✅ SEMUA CSS INLINE — tidak ada request eksternal -->
    <style>
<?= $cssAdmin ?>
        .login-wrap {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .login-card {
            background: #fff;
            border-radius: 24px;
            padding: 48px 40px;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,.3);
            animation: slideDown .5s cubic-bezier(.16,1,.3,1);
        }
        .login-logo { text-align: center; margin-bottom: 32px; }
        .login-logo .icon { font-size: 48px; display: block; margin-bottom: 12px; }
        .login-logo h1 { font-size: 1.5rem; font-weight: 800; color: #1e293b; margin-bottom: 4px; }
        .login-logo p { color: #64748b; font-size: .9rem; }
        .login-field { margin-bottom: 20px; }
        .login-field label { display: block; font-size: .85rem; font-weight: 600; color: #374151; margin-bottom: 8px; }
        .login-field input {
            width: 100%; padding: 12px 16px;
            border: 2px solid #e5e7eb; border-radius: 12px;
            font-size: .95rem; font-family: inherit;
            outline: none; transition: border-color .2s, box-shadow .2s; color: #1e293b;
        }
        .login-field input:focus { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,.12); }
        .login-btn {
            width: 100%; padding: 14px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: #fff; border: none; border-radius: 12px;
            font-size: 1rem; font-weight: 700; font-family: inherit;
            cursor: pointer; transition: opacity .2s, transform .1s;
        }
        .login-btn:hover { opacity: .92; }
        .login-btn:active { transform: scale(.98); }
        .login-error {
            background: #fef2f2; color: #dc2626;
            border: 1px solid #fecaca; border-radius: 10px;
            padding: 12px 16px; font-size: .88rem; margin-bottom: 20px;
            display: flex; align-items: center; gap: 8px;
        }
        @keyframes slideDown { from { opacity:0; transform:translateY(-20px); } to { opacity:1; transform:translateY(0); } }
    </style>
</head>
<body>
    <div class="login-wrap">
        <div class="login-card">
            <div class="login-logo">
                <span class="icon">🔐</span>
                <h1>Admin Panel</h1>
                <p>Masukkan password untuk melanjutkan</p>
            </div>
            <?php if (!empty($loginError)): ?>
            <div class="login-error">
                <span>⚠️</span>
                <span><?= htmlspecialchars($loginError) ?></span>
            </div>
            <?php endif; ?>
            <form method="POST" action="index.php">
                <input type="hidden" name="action" value="login">
                <div class="login-field">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password"
                        placeholder="Masukkan password admin..."
                        autocomplete="current-password" autofocus required>
                </div>
                <button type="submit" class="login-btn">Masuk ke Admin Panel →</button>
            </form>
        </div>
    </div>
</body>
</html>
<?php
    exit;
endif;

// Baca seat config yang tersimpan
$seatConfigFile = __DIR__ . '/../data/seat-config.json';
$savedSeatCfg   = [];
if (file_exists($seatConfigFile)) {
    $decoded = json_decode(file_get_contents($seatConfigFile), true);
    if (is_array($decoded)) $savedSeatCfg = $decoded;
}
foreach ($ticketConfig as $tc) {
    if (!isset($savedSeatCfg[$tc['type']])) {
        $savedSeatCfg[$tc['type']] = ['open_quota' => $tc['quota'], 'closed' => false];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Ticketing Management</title>

    <!-- ✅ SEMUA CSS INLINE — tidak ada request eksternal -->
    <style>
<?= $cssAdmin ?>
        .logout-btn {
            background: rgba(239,68,68,.12); color: #ef4444;
            border: 1px solid rgba(239,68,68,.25); padding: 8px 16px;
            border-radius: 8px; font-size: .85rem; font-weight: 600;
            font-family: inherit; cursor: pointer; text-decoration: none;
            display: inline-flex; align-items: center; gap: 6px;
            transition: background .2s;
        }
        .logout-btn:hover { background: rgba(239,68,68,.2); }
        .section-title { font-size: 1rem; font-weight: 700; color: #1e293b; margin-bottom: 4px; }
        .section-sub { font-size: .83rem; color: #64748b; }
        .seat-manager-card {
            background: #fff; border-radius: 16px;
            border: 1px solid #e5e7eb; box-shadow: 0 1px 4px rgba(0,0,0,.06);
            margin-bottom: 24px; overflow: hidden;
        }
        .seat-manager-header {
            padding: 20px 24px; border-bottom: 1px solid #f1f5f9;
            display: flex; align-items: center; justify-content: space-between;
            flex-wrap: wrap; gap: 12px;
        }
        .seat-manager-title { display: flex; align-items: center; gap: 10px; }
        .seat-manager-title .icon { font-size: 22px; }
        .seat-row {
            display: grid; grid-template-columns: 1fr auto auto auto;
            align-items: center; gap: 16px; padding: 16px 24px;
            border-bottom: 1px solid #f8fafc; transition: background .15s;
        }
        .seat-row:last-child { border-bottom: none; }
        .seat-row:hover { background: #f8fafc; }
        .seat-class-info { display: flex; align-items: center; gap: 12px; }
        .seat-color-dot { width: 12px; height: 12px; border-radius: 50%; flex-shrink: 0; }
        .seat-class-name { font-weight: 600; font-size: .95rem; color: #1e293b; }
        .seat-class-sub { font-size: .78rem; color: #94a3b8; margin-top: 2px; }
        .quota-field-wrap { display: flex; flex-direction: column; gap: 5px; }
        .quota-field-label { font-size: .78rem; color: #64748b; font-weight: 500; }

        .quota-progress-wrap { margin-top: 6px; }
        .quota-progress-bar {
            height: 6px; background: #e2e8f0; border-radius: 99px; overflow: hidden; width: 140px;
        }
        .quota-progress-fill {
            height: 100%; border-radius: 99px;
            background: linear-gradient(90deg, #6366f1, #818cf8);
            transition: width .4s ease;
        }
        .quota-progress-label { font-size: .72rem; color: #94a3b8; margin-top: 3px; }
        .seat-quota-input { display: flex; align-items: center; gap: 8px; }
        .seat-quota-input input {
            width: 90px; padding: 8px 12px;
            border: 2px solid #e5e7eb; border-radius: 8px;
            font-size: .9rem; font-family: inherit; font-weight: 600;
            color: #1e293b; text-align: center; outline: none; transition: border-color .2s;
        }
        .seat-quota-input input:focus { border-color: #6366f1; }
        .quota-save-btn {
            padding: 8px 14px; background: #6366f1; color: #fff;
            border: none; border-radius: 8px; font-size: .82rem;
            font-weight: 600; font-family: inherit; cursor: pointer;
            transition: opacity .2s; white-space: nowrap;
        }
        .quota-save-btn:hover { opacity: .85; }
        .quota-save-btn:disabled { opacity: .5; cursor: not-allowed; }
        .toggle-wrap { display: flex; align-items: center; gap: 10px; }
        .toggle-label { font-size: .82rem; font-weight: 500; min-width: 68px; text-align: center; }
        .toggle-label.open   { color: #10b981; }
        .toggle-label.closed { color: #94a3b8; }
        .toggle-switch { position: relative; width: 46px; height: 26px; cursor: pointer; }
        .toggle-switch input { opacity: 0; width: 0; height: 0; position: absolute; }
        .toggle-slider {
            position: absolute; inset: 0;
            background: #d1d5db; border-radius: 26px; transition: background .2s;
        }
        .toggle-slider::before {
            content: ''; position: absolute;
            height: 20px; width: 20px; left: 3px; bottom: 3px;
            background: #fff; border-radius: 50%;
            transition: transform .2s; box-shadow: 0 1px 3px rgba(0,0,0,.2);
        }
        .toggle-switch input:checked + .toggle-slider { background: #10b981; }
        .toggle-switch input:checked + .toggle-slider::before { transform: translateX(20px); }
        .archived-badge {
            display: inline-flex; align-items: center; gap: 5px;
            background: #f1f5f9; color: #64748b;
            border: 1px solid #e2e8f0; border-radius: 6px;
            padding: 4px 10px; font-size: .75rem; font-weight: 600; letter-spacing: .3px;
        }
        .archived-badge .dot { width: 6px; height: 6px; border-radius: 50%; background: #94a3b8; }
        .toast {
            position: fixed; bottom: 24px; right: 24px;
            background: #1e293b; color: #fff;
            padding: 14px 20px; border-radius: 12px;
            font-size: .88rem; font-weight: 500;
            box-shadow: 0 10px 25px rgba(0,0,0,.25);
            z-index: 9999; opacity: 0; transform: translateY(12px);
            transition: all .3s ease; max-width: 320px; pointer-events: none;
        }
        .toast.show { opacity: 1; transform: translateY(0); }
        .toast.success { border-left: 4px solid #10b981; }
        .toast.error   { border-left: 4px solid #ef4444; }
        .quota-total-bar {
            display: flex; gap: 8px; padding: 12px 24px;
            background: #f8fafc; border-top: 1px solid #f1f5f9;
            font-size: .82rem; color: #64748b; flex-wrap: wrap; align-items: center;
        }
        .quota-total-bar strong { color: #1e293b; }
        @media (max-width: 640px) {
            .seat-row { grid-template-columns: 1fr; gap: 12px; }
            .seat-quota-input { justify-content: flex-start; }
        }
        /* ── Confirm Modal ── */
        .confirm-modal-content {
            max-width: 400px;
            text-align: center;
            padding: 40px 32px 32px;
            border-radius: 24px;
        }
        .confirm-icon-wrap {
            width: 64px; height: 64px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 20px;
        }
        .confirm-icon-wrap.type-verify  { background: #d1fae5; }
        .confirm-icon-wrap.type-delete  { background: #fee2e2; }
        .confirm-icon-wrap.type-warning { background: #fef3c7; }
        .confirm-icon-wrap svg { width: 28px; height: 28px; }
        .confirm-icon-wrap.type-verify  svg { stroke: #059669; }
        .confirm-icon-wrap.type-delete  svg { stroke: #dc2626; }
        .confirm-icon-wrap.type-warning svg { stroke: #d97706; }
        .confirm-title {
            font-size: 1.2rem; font-weight: 700;
            color: #1e293b; margin-bottom: 10px;
        }
        .confirm-desc {
            font-size: .9rem; color: #64748b;
            line-height: 1.6; margin-bottom: 28px;
        }
        .confirm-actions {
            display: flex; gap: 12px;
        }
        .confirm-btn-cancel {
            flex: 1; padding: 12px;
            background: #f1f5f9; color: #64748b;
            border: none; border-radius: 12px;
            font-size: .9rem; font-weight: 600;
            font-family: inherit; cursor: pointer;
            transition: background .2s;
        }
        .confirm-btn-cancel:hover { background: #e2e8f0; }
        .confirm-btn-ok {
            flex: 1; padding: 12px;
            border: none; border-radius: 12px;
            font-size: .9rem; font-weight: 600;
            font-family: inherit; cursor: pointer;
            color: #fff; transition: opacity .2s;
        }
        .confirm-btn-ok:hover { opacity: .88; }
        .confirm-btn-ok.type-verify  { background: linear-gradient(135deg, #059669, #10b981); }
        .confirm-btn-ok.type-delete  { background: linear-gradient(135deg, #dc2626, #ef4444); }
        .confirm-btn-ok.type-warning { background: linear-gradient(135deg, #d97706, #f59e0b); }
    </style>
</head>
<body>
    <div class="container">
        <!-- HEADER -->
        <div class="header">
            <div class="header-top">
                <div class="header-title">
                    <h1>Admin Panel</h1>
                    <p>Kelola dan verifikasi pemesanan tiket dengan mudah</p>
                </div>
                <a href="?action=logout" class="logout-btn">🚪 Keluar</a>
            </div>
            <div class="stats-grid">
                <div class="stat-card total">
                    <div class="stat-label">Total Tiket</div>
                    <div class="stat-value" id="totalTickets">0</div>
                    <div class="stat-icon">🎟️</div>
                </div>
                <div class="stat-card pending">
                    <div class="stat-label">Pending</div>
                    <div class="stat-value" id="pendingTickets">0</div>
                    <div class="stat-icon">⏳</div>
                </div>
                <div class="stat-card paid">
                    <div class="stat-label">Lunas</div>
                    <div class="stat-value" id="paidTickets">0</div>
                    <div class="stat-icon">✅</div>
                </div>
            </div>
        </div>

        <!-- SEAT MANAGER -->
        <div class="seat-manager-card">
            <div class="seat-manager-header">
                <div class="seat-manager-title">
                    <span class="icon">🪑</span>
                    <div>
                        <div class="section-title">Pengaturan Kuota &amp; Status Tiket</div>
                        <div class="section-sub">Atur kuota seat yang dibuka pada zona yang dipilih. </div>
                    </div>
                </div>
            </div>
            <div class="seat-rows">
<?php foreach ($ticketConfig as $tc):
    $type          = $tc['type'];
    $originalQuota = (int)$tc['quota'];   // READ-ONLY dari config.php
    $cfg           = $savedSeatCfg[$type] ?? ['open_quota' => $originalQuota, 'closed' => false];
    $isClosed      = (bool)($cfg['closed'] ?? false);
    $openQuota     = (int)($cfg['open_quota'] ?? $originalQuota);
    $openQuota     = min($openQuota, $originalQuota);
    $pct           = $originalQuota > 0 ? round($openQuota / $originalQuota * 100) : 0;
?>
                <div class="seat-row" id="row-<?= $type ?>">
                    <div class="seat-class-info">
                        <div class="seat-color-dot" style="background:<?= htmlspecialchars($tc['color']) ?>;"></div>
                        <div>
                            <div class="seat-class-name"><?= htmlspecialchars($tc['name']) ?></div>
                            <div class="seat-class-sub"><?= htmlspecialchars($tc['category']) ?> &middot; Baris <?= $tc['rowStart'] ?>–<?= $tc['rowEnd'] ?></div>
                        </div>
                        <div class="archived-badge" id="badge-<?= $type ?>" style="<?= $isClosed ? '' : 'display:none;' ?>">
                            <span class="dot"></span> Ditutup
                        </div>
                    </div>
                    <!-- Kuota Dibuka -->
                    <div class="quota-field-wrap">
                        <div class="quota-field-label">Kuota Dibuka</div>
                        <div class="seat-quota-input">
                            <input type="number" id="open-quota-<?= $type ?>"
                                value="<?= $openQuota ?>" min="0" max="<?= $originalQuota ?>"
                                data-type="<?= $type ?>">
                            <button class="quota-save-btn" onclick="saveOpenQuota('<?= $type ?>')">Simpan</button>
                        </div>

                        <!-- Progress bar: open_quota vs original -->
                        <div class="quota-progress-wrap">
                            <div class="quota-progress-bar">
                                <div class="quota-progress-fill" id="progress-<?= $type ?>"
                                    style="width:<?= $pct ?>%;"></div>
                            </div>
                            <div class="quota-progress-label">
                                <span id="progress-label-<?= $type ?>"><?= $openQuota ?> / <?= $originalQuota ?></span> dibuka
                            </div>
                        </div>
                    </div>
                    <div class="toggle-wrap">
                        <label class="toggle-switch">
                            <input type="checkbox" id="toggle-<?= $type ?>"
                                <?= $isClosed ? '' : 'checked' ?>
                                onchange="toggleStatus('<?= $type ?>')">
                            <span class="toggle-slider"></span>
                        </label>
                        <span class="toggle-label <?= $isClosed ? 'closed' : 'open' ?>" id="tlabel-<?= $type ?>">
                            <?= $isClosed ? 'Archived' : 'Open' ?>
                        </span>
                    </div>
                </div>
<?php endforeach; ?>
            </div>
            <div class="quota-total-bar">
                <span>Total kuota semua kelas:</span>
                <strong id="totalQuotaDisplay">
                    <?= array_sum(array_map(fn($tc) => (int)($savedSeatCfg[$tc['type']]['quota'] ?? $tc['quota']), $ticketConfig)) ?>
                </strong>
                <span>seat</span>
            </div>
        </div>

        <!-- CONTROLS -->
        <div class="controls">
            <div class="controls-row">
<div class="search-box">
    <span class="search-icon">🔍</span>
    <input type="text" id="searchInput" placeholder="Cari nomor tiket, nama, atau HP...">
</div>
                <div class="filter-group">
                    <button class="filter-btn" onclick="filterStatus('all')">Semua</button>
                    <button class="filter-btn active" onclick="filterStatus('pending')">Pending</button>
                    <button class="filter-btn" onclick="filterStatus('paid')">Lunas</button>
                </div>
                <div class="action-buttons">
                    <button class="refresh-btn" onclick="loadTickets()"><span>🔄</span><span>Refresh</span></button>
                    <button class="download-btn" onclick="downloadCSV()"><span>📥</span><span>Export</span></button>
                    <button onclick="downloadPDF()" class="action-btn btn-detail">
    📄 Export PDF Check-In
</button>
                </div>
                
            </div>
        </div>

        <!-- TABLE -->
        <div class="table-container">
            <div id="loadingState" class="loading">
                <div class="spinner"></div>
                <p style="margin-top: 20px; color: var(--gray); font-weight: 500;">Memuat data tiket...</p>
            </div>
            <table id="ticketsTable" style="display: none;">
                <thead>
                    <tr>
                        <th>No. Tiket</th><th>Nama</th><th>No. HP</th><th>Tipe</th>
                        <th>Kursi</th><th>Harga</th><th>Status</th><th>WA</th><th>Bukti</th><th>Tanggal</th><th>Aksi</th>
                    </tr>
                </thead>
                <tbody id="ticketsBody"></tbody>
            </table>
            <div id="emptyState" class="empty-state" style="display: none;">
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V5h14v14z"/>
                    <path d="M7 10h2v7H7zm4-3h2v10h-2zm4 6h2v4h-2z"/>
                </svg>
                <h3>Belum ada data tiket</h3>
                <p>Data tiket akan muncul di sini setelah ada pemesanan</p>
            </div>
        </div>
    </div>

    <!-- QR MODAL -->
    <div class="modal" id="qrModal">
        <div class="modal-content">
            <button class="close-btn" onclick="closeModal('qrModal')">&times;</button>
            <h2>QR Code Tiket</h2>
            <div class="qr-container">
                <div id="qrCodeDisplay"></div>
                <div style="display:flex;gap:12px;margin-top:24px;">
                    <button class="download-btn" onclick="downloadQR()" style="flex:1;"><span>📥</span><span>Download QR</span></button>
                    <button class="download-btn wa-btn" onclick="sendQRViaWhatsApp()" style="flex:1;background:linear-gradient(135deg,#25D366,#128C7E);"><span>💬</span><span>Kirim via WhatsApp</span></button>
                </div>
                <button class="action-btn btn-view" id="openTicketPageBtn" style="margin-top: 12px; width: 100%; justify-content: center;"><span>🔗</span><span>Buka Halaman Tiket</span></button>
            </div>
        </div>
    </div>

    <!-- DETAIL MODAL -->
    <div class="modal" id="detailModal">
        <div class="modal-content">
            <button class="close-btn" onclick="closeModal('detailModal')">&times;</button>
            <h2>Detail Tiket</h2>
            <div id="ticketDetails"></div>
            <div style="margin-top: 24px; display: flex; gap: 12px;">
                <button class="action-btn btn-verify" id="verifyBtn" style="flex: 1; justify-content: center;"><span>✓</span><span>Verifikasi Pembayaran</span></button>
                <a href="#" id="detailTicketLink" target="_blank" style="flex: 1;">
                    <button class="action-btn btn-view" style="width: 100%; justify-content: center;"><span>🔗</span><span>Lihat Tiket</span></button>
                </a>
            </div>

        </div>
    </div>

    <!-- IMAGE PREVIEW MODAL -->
    <div id="imagePreviewModal" class="modal">
        <div style="position: relative; max-width: 90%; max-height: 90%;">
            <button class="close-btn" onclick="closeImagePreview()">&times;</button>
            <img id="previewImage" alt="Preview">
        </div>
    </div>

    <!-- CONFIRMATION MODAL -->
    <div class="modal" id="confirmModal">
        <div class="modal-content confirm-modal-content">
            <div class="confirm-icon-wrap" id="confirmIconWrap">
                <svg id="confirmIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"></svg>
            </div>
            <h2 class="confirm-title" id="confirmTitle">Konfirmasi</h2>
            <p class="confirm-desc" id="confirmDesc"></p>
            <div class="confirm-actions">
                <button class="confirm-btn-cancel" onclick="closeConfirmModal()">Batal</button>
                <button class="confirm-btn-ok" id="confirmOkBtn">Ya, Lanjutkan</button>
            </div>
        </div>
    </div>

    <!-- TOAST -->
    <div class="toast" id="toast"></div>

    <!-- ✅ SEMUA JS INLINE — tidak ada request eksternal -->
    <?= jsConfig($ticketPrices, $ticketConfig) ?>
    <?php if ($qrCodeMissing): ?>
    <!-- QRCode library dari CDN (fallback karena js/vendor/qrcode.min.js tidak ditemukan) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js" integrity="sha512-CNgIRecGo7nphbeZ04Sc13ka07paqdeTu0WR1IM4kNcpmW73+eSsEF6eEHYQJD36/Kbz0fQ4HJRV3efODFAg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <?php else: ?>
    <script>
<?= $jsQrCode ?>
    </script>
    <?php endif; ?>
    <script>
<?= $jsAll ?>
    </script>
    <script>
    function showToast(msg, type = 'success') {
        const t = document.getElementById('toast');
        t.textContent = msg;
        t.className = 'toast ' + type + ' show';
        clearTimeout(window._toastTimer);
        window._toastTimer = setTimeout(() => t.classList.remove('show'), 3200);
    }

    async function saveOpenQuota(type) {
        const input = document.getElementById('open-quota-' + type);
        const btn   = input.nextElementSibling;
        const openQuota = parseInt(input.value);
        if (isNaN(openQuota) || openQuota < 0) { showToast('Kuota tidak valid', 'error'); return; }
        btn.disabled = true; btn.textContent = '⏳';
        try {
            const fd = new FormData();
            fd.append('action', 'update_open_quota');
            fd.append('type', type);
            fd.append('open_quota', openQuota);
            const res  = await fetch('seat-manager.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success) {
                showToast('✅ Kuota dibuka ' + type + ' berhasil disimpan', 'success');
                if (data.config) updateAllQuotaInputs(data.config);
            } else {
                showToast('❌ ' + data.message, 'error');
            }
        } catch (e) {
            showToast('❌ Gagal terhubung ke server', 'error');
        } finally {
            btn.disabled = false; btn.textContent = 'Simpan';
        }
    }

    async function toggleStatus(type) {
        const checkbox = document.getElementById('toggle-' + type);
        const label    = document.getElementById('tlabel-' + type);
        const badge    = document.getElementById('badge-' + type);
        try {
            const fd = new FormData();
            fd.append('action', 'toggle_status');
            fd.append('type', type);
            const res  = await fetch('seat-manager.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success) {
                const isClosed = data.closed;
                label.textContent   = isClosed ? 'Archived' : 'Open';
                label.className     = 'toggle-label ' + (isClosed ? 'closed' : 'open');
                badge.style.display = isClosed ? 'inline-flex' : 'none';
                showToast(
                    isClosed ? '🔒 Tiket ' + type + ' ditutup (Archived)' : '✅ Tiket ' + type + ' dibuka kembali',
                    isClosed ? 'error' : 'success'
                );
            } else {
                checkbox.checked = !checkbox.checked;
                showToast('❌ ' + data.message, 'error');
            }
        } catch (e) {
            checkbox.checked = !checkbox.checked;
            showToast('❌ Gagal terhubung ke server', 'error');
        }
    }

    function updateAllQuotaInputs(cfg) {
        for (const [type, val] of Object.entries(cfg)) {
            const openInput    = document.getElementById('open-quota-' + type);
            const progressFill = document.getElementById('progress-' + type);
            const progressLbl  = document.getElementById('progress-label-' + type);
            const openQuota    = val.open_quota ?? 0;
            const origQuota    = val.quota ?? 0;      // kuota asli (READ-ONLY dari PHP)
            if (openInput) openInput.value = openQuota;
            if (progressFill && origQuota > 0) {
                progressFill.style.width = Math.round(openQuota / origQuota * 100) + '%';
            }
            if (progressLbl) progressLbl.textContent = openQuota + ' / ' + origQuota;
        }
    }
    </script>
</body>
</html>