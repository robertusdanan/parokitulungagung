<?php require_once __DIR__ . '/../../config.php'; ?>
<?php
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$base = $protocol . $host;
$currentUrl = $base . $_SERVER['REQUEST_URI'];
?>
<link rel="icon" href="/favicon.ico?v=2">
<!-- OPEN GRAPH -->
<meta property="og:title" content="E-Ticket Resmi - Berbincang Dengan Romo Eko">
<meta property="og:description" content="Tiket Anda telah berhasil diverifikasi. Klik untuk melihat detail tiket.">

<meta property="og:image" content="<?= $base ?>/e-ticket/pages/ticket/default.jpg">
<meta property="og:image:width" content="630">
<meta property="og:image:height" content="630">

<meta property="og:type" content="website">
<meta property="og:url" content="<?= $currentUrl ?>">
<meta property="fb:app_id" content="943090234902556">
<!-- OPTIONAL -->
<meta name="twitter:card" content="summary_large_image">
<meta name="robots" content="noindex, nofollow">
    <title>E-Ticket - Berbincang Dengan Romo Eko</title>
    <style>
    @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&family=Playfair+Display:ital,wght@0,500;0,600;0,700;1,500&display=swap');
    
    * { margin: 0; padding: 0; box-sizing: border-box; }
    
    body {
        font-family: "Plus Jakarta Sans", sans-serif;
        background: linear-gradient(135deg, #0B0F19 0%, #1A1D24 100%);
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        padding: 20px;
        color: #E2E8F0;
    }

    /* =========================================
       SISTEM TEMA BERDASARKAN KELAS TIKET
       ========================================= */

    /* 1. VVIP (Paling Eksklusif: Merah Gelap & Emas Murni) */
    .ticket-wrapper.vvip {
        --bg-main: linear-gradient(145deg, #380C17, #1A0409);
        --bg-stub: linear-gradient(145deg, #260810, #120206);
        --accent: #FFD700;
        --accent-glow: rgba(255, 215, 0, 0.4);
        --badge-bg: rgba(255, 215, 0, 0.15);
        --text-highlight: #FFFFFF;
        border: 1px solid rgba(255, 215, 0, 0.4);
        box-shadow: 0 30px 60px rgba(0, 0, 0, 0.8), 0 0 20px rgba(255, 215, 0, 0.1), inset 0 1px 1px rgba(255, 215, 0, 0.2);
    }

    /* 2. VIP (Sangat Eksklusif: Hitam Pekat & Emas Premium) */
    .ticket-wrapper.vip {
        --bg-main: linear-gradient(145deg, #1C1C1C, #0A0A0A);
        --bg-stub: linear-gradient(145deg, #141414, #050505);
        --accent: #D4AF37;
        --accent-glow: rgba(212, 175, 55, 0.2);
        --badge-bg: rgba(212, 175, 55, 0.15);
        --text-highlight: #F8FAFC;
        border: 1px solid rgba(212, 175, 55, 0.3);
        box-shadow: 0 30px 60px rgba(0, 0, 0, 0.7), inset 0 1px 1px rgba(212, 175, 55, 0.1);
    }

    /* 3. KELAS 1 (Premium: Hijau Zamrud Gelap & Mint) */
    .ticket-wrapper.kelas1 {
        --bg-main: linear-gradient(145deg, #06281E, #02120D);
        --bg-stub: linear-gradient(145deg, #041C15, #010B08);
        --accent: #A7F3D0;
        --accent-glow: rgba(167, 243, 208, 0.2);
        --badge-bg: rgba(167, 243, 208, 0.15);
        --text-highlight: #F1F5F9;
        border: 1px solid rgba(167, 243, 208, 0.2);
        box-shadow: 0 30px 60px rgba(0, 0, 0, 0.6);
    }

    /* 4. REGULER / KELAS 2 (Standar: Biru Dongker & Biru Muda) */
    .ticket-wrapper.reguler, .ticket-wrapper.kelas2 {
        --bg-main: linear-gradient(145deg, #1E293B, #0F172A);
        --bg-stub: linear-gradient(145deg, #172033, #0B1120);
        --accent: #93C5FD;
        --accent-glow: transparent;
        --badge-bg: rgba(147, 197, 253, 0.15);
        --text-highlight: #E2E8F0;
        border: 1px solid rgba(147, 197, 253, 0.15);
        box-shadow: 0 30px 50px rgba(0, 0, 0, 0.5);
    }

    /* Default Base Styles (Jika tipe tidak dikenali) */
    .ticket-wrapper {
        --bg-main: linear-gradient(145deg, #1E222B, #13151A);
        --bg-stub: linear-gradient(145deg, #171A21, #0E1015);
        --accent: #CBD5E1;
        --badge-bg: rgba(255, 255, 255, 0.1);
        --text-highlight: #FFF;
        
        display: flex;
        flex-direction: row;
        width: 100%;
        max-width: 850px;
        background: var(--bg-main);
        border-radius: 20px;
        overflow: hidden;
        animation: fadeIn 0.8s cubic-bezier(0.16, 1, 0.3, 1);
        position: relative;
        transition: all 0.3s ease;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px) scale(0.98); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }

    /* Bagian Utama Tiket (Kiri) */
    .ticket-main {
        flex: 1;
        padding: 40px;
        position: relative;
        border-right: 2px dashed rgba(255, 255, 255, 0.15);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    /* Efek lubang sobekan tiket di atas dan bawah */
    .ticket-main::before, .ticket-main::after {
        content: '';
        position: absolute;
        right: -16px;
        width: 30px;
        height: 30px;
        background: #12151B; /* Warna disamakan dengan background body */
        border-radius: 50%;
        box-shadow: inset 0 1px 2px rgba(0,0,0,0.5);
        z-index: 2;
    }
    .ticket-main::before { top: -15px; border-bottom: 1px solid rgba(255,255,255,0.05); }
    .ticket-main::after { bottom: -15px; border-top: 1px solid rgba(255,255,255,0.05); }

    /* Header Event */
    .event-eyebrow {
        font-size: 12px;
        letter-spacing: 4px;
        text-transform: uppercase;
        color: var(--accent);
        font-weight: 700;
        margin-bottom: 10px;
        text-shadow: 0 0 10px var(--accent-glow);
    }

    .event-title {
        font-family: "Playfair Display", serif;
        font-size: 32px;
        font-weight: 700;
        color: var(--text-highlight);
        margin-bottom: 30px;
        line-height: 1.2;
    }

    /* Grid Informasi */
    .details-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 25px;
        margin-bottom: 30px;
    }

    .section-title {
        font-size: 11px;
        color: #94A3B8;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        font-weight: 600;
        margin-bottom: 6px;
    }

    .value {
        font-size: 18px;
        font-weight: 600;
        color: #F8FAFC;
        word-break: break-all;
    }

    /* Tempat Duduk */
    .seat-info {
        background: rgba(255, 255, 255, 0.03);
        padding: 16px 20px;
        border-radius: 12px;
        border: 1px solid rgba(255, 255, 255, 0.08);
    }

    .seat-count {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 12px;
        font-size: 13px;
        color: #CBD5E1;
        font-weight: 500;
    }

    .seat-icon {
        width: 24px;
        height: 24px;
        background: var(--accent);
        color: #0F172A; /* Text gelap agar kontras dengan warna cerah accent */
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: 800;
        box-shadow: 0 0 8px var(--accent-glow);
    }

    .seats-list { display: flex; flex-wrap: wrap; gap: 8px; }
    
    .seat-tag {
        background: rgba(255, 255, 255, 0.08);
        padding: 6px 14px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        color: #F1F5F9;
        border: 1px solid rgba(255, 255, 255, 0.15);
    }
    
    .zone-tag {
        background: var(--badge-bg);
        color: var(--accent);
        border-color: rgba(255, 255, 255, 0.1);
    }

    /* Bagian Stub (Kanan) */
    .ticket-stub {
        width: 280px;
        padding: 40px 30px;
        background: var(--bg-stub);
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        text-align: center;
    }

    .ticket-type-badge {
        display: inline-block;
        padding: 10px 24px;
        border-radius: 50px;
        font-size: 14px;
        font-weight: 700;
        margin-bottom: 30px;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        background: var(--badge-bg);
        color: var(--accent);
        border: 1px solid var(--accent);
        box-shadow: 0 0 15px var(--accent-glow);
    }

    .price-value {
        font-family: "Playfair Display", serif;
        font-size: 26px;
        font-weight: 700;
        color: var(--accent);
        margin-top: 8px;
        text-shadow: 0 0 10px var(--accent-glow);
    }

    .footer-note {
        margin-top: 40px;
        font-size: 11px;
        color: #64748B;
        line-height: 1.6;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* State Loading & Error */
    .loading, .error { text-align: center; padding: 40px; font-weight: 600; font-size: 18px; }
    .loading { color: #D4AF37; }
    .error { color: #EF4444; }

    /* Responsif untuk Layar Kecil (Mobile) */
    @media (max-width: 768px) {
        .ticket-wrapper { flex-direction: column; max-width: 400px; }
        .ticket-main {
            border-right: none;
            border-bottom: 2px dashed rgba(255, 255, 255, 0.15);
        }
        .ticket-main::before { top: auto; bottom: -15px; left: -15px; right: auto; border-bottom: none; border-right: 1px solid rgba(255,255,255,0.05); }
        .ticket-main::after { bottom: -15px; right: -15px; border-top: none; border-left: 1px solid rgba(255,255,255,0.05); }
        .ticket-stub { width: 100%; padding: 30px; }
        .details-grid { grid-template-columns: 1fr; gap: 15px; }
        .event-title { font-size: 24px; }
    }
    </style>
</head>
<body>

    <div class="ticket-wrapper" id="ticketWrapper" style="display:none;">
        <div class="ticket-main">
            <div>
                <div class="event-eyebrow">Digital Entry Pass</div>
                <div class="event-title">Malam Kasih dan Ngopi Bareng<br>Bersama Romo Eko</div>
                
                <div class="details-grid">
                    <div>
                        <div class="section-title">Nomor Tiket</div>
                        <div id="ticketNumber" class="value"></div>
                    </div>
                    <div>
                        <div class="section-title">Nama Pemilik</div>
                        <div id="ticketName" class="value"></div>
                    </div>
                </div>
            </div>

            <div id="seatInfoContainer" style="display:none;">
                <div class="section-title">Detail Tempat Duduk</div>
                <div class="seat-info">
                    <div class="seat-count">
                        <div class="seat-icon" id="seatCountIcon">0</div>
                        <span id="seatCountText">Tempat Duduk</span>
                    </div>
                    <div class="seats-list" id="seatsList"></div>
                </div>
            </div>
        </div>

        <div class="ticket-stub">
            <div id="ticketBadge" class="ticket-type-badge"></div>
            
            <div>
                <div class="section-title">Total Pembayaran</div>
                <div id="ticketPrice" class="price-value"></div>
            </div>

            <div class="footer-note">
                Tunjukkan E-Ticket ini saat<br>memasuki area acara.
            </div>
        </div>
    </div>

    <div class="loading" id="loading">
        <div style="font-size:32px;margin-bottom:12px;">⏳</div>Memuat tiket eksklusif...
    </div>
    <div class="error" id="error" style="display:none;">
        <div style="font-size:32px;margin-bottom:12px;">❌</div>
        <div id="errorMessage">Tiket tidak ditemukan</div>
    </div>

    <?= jsConfig($ticketPrices, $ticketConfig) ?>

    <script>
    const SB = (() => {
        function headers(extra = {}) {
            return {
                'apikey':        SUPABASE_ANON,
                'Authorization': `Bearer ${SUPABASE_ANON}`,
                'Content-Type':  'application/json',
                ...extra
            };
        }
        const REST = `${SUPABASE_URL}/rest/v1`;

        async function getTicketById(id) {
            const res = await fetch(
                `${REST}/ticketing?id=eq.${id}&select=*&limit=1`,
                { headers: headers() }
            );
            if (!res.ok) throw new Error('Tiket tidak ditemukan.');
            const rows = await res.json();
            if (!rows.length) throw new Error('Tiket tidak ditemukan.');
            return rows[0];
        }
        function getImageUrl(imageUrl) {
            if (!imageUrl) return null;
            if (imageUrl.startsWith('http')) return imageUrl;
            return `${SUPABASE_URL}/storage/v1/object/public/${STORAGE_BUCKET}/${imageUrl}`;
        }
        return { getTicketById, getImageUrl };
    })();

    function formatPrice(price) {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(price);
    }

    function formatSeatId(seatId) {
        const parts = seatId.split('-');
        if (parts.length !== 3) return seatId;
        const section = parts[0] === 'L' ? 'Kiri' : 'Kanan';
        return `${section} Baris ${parts[1]} No. ${parts[2]}`;
    }

    async function loadTicket() {
        const id = new URLSearchParams(window.location.search).get('id');
        if (!id) { showError('ID tiket tidak ditemukan di URL.'); return; }

        try {
            const data = await SB.getTicketById(id);
            const ticketType = (data.primary_ticket_type || 'reguler').toLowerCase();

            document.getElementById('loading').style.display       = 'none';
            
            // Terapkan Class Tema ke Wrapper sesuai jenis tiket
            const wrapper = document.getElementById('ticketWrapper');
            wrapper.className = `ticket-wrapper ${ticketType}`;
            wrapper.style.display = 'flex'; 

            document.getElementById('ticketNumber').textContent = data.ticket_number;
            document.getElementById('ticketName').textContent   = data.name;

            if (data.price) document.getElementById('ticketPrice').textContent = formatPrice(data.price);

            // Set Badge
            document.getElementById('ticketBadge').textContent = ticketType.toUpperCase();

            const hasIndividualSeats =
                data.seat_numbers &&
                (Array.isArray(data.seat_numbers) ? data.seat_numbers.length > 0
                    : (() => { try { return JSON.parse(data.seat_numbers).length > 0; } catch(_){ return false; } })());

            if (hasIndividualSeats) {
                const seats = Array.isArray(data.seat_numbers)
                    ? data.seat_numbers
                    : JSON.parse(data.seat_numbers);

                document.getElementById('seatInfoContainer').style.display = 'block';
                document.getElementById('seatCountIcon').textContent       = seats.length;
                document.getElementById('seatCountText').textContent       = 'Tempat Duduk Terpilih';
                seats.forEach(seatId => {
                    const tag       = document.createElement('div');
                    tag.className   = 'seat-tag';
                    tag.textContent = formatSeatId(seatId);
                    document.getElementById('seatsList').appendChild(tag);
                });
            } else if (data.total_seats && data.total_seats > 0) {
                document.getElementById('seatInfoContainer').style.display = 'block';
                document.getElementById('seatCountIcon').textContent       = data.total_seats;
                document.getElementById('seatCountText').textContent       = 'Tiket';

                const zoneLabel = ticketType.charAt(0).toUpperCase() + 
                                  ticketType.slice(1).replace('kelas1', 'Kelas 1');
                
                const tag = document.createElement('div');
                tag.className   = 'seat-tag zone-tag';
                tag.textContent = `Zona ${ticketType === 'kelas1' ? 'Kelas 1' : zoneLabel} · ${data.total_seats} Tiket`;
                document.getElementById('seatsList').appendChild(tag);

                const note = document.createElement('div');
                note.style.cssText = 'font-size:11px;color:#94A3B8;margin-top:10px;line-height:1.5;width:100%;';
                note.textContent   = 'Pilih kursi anda di lokasi sesuai zona.';
                document.getElementById('seatsList').appendChild(note);
            }
        } catch (err) {
            showError(err.message || 'Gagal memuat tiket.');
        }
    }

    function showError(message) {
        document.getElementById('loading').style.display   = 'none';
        document.getElementById('error').style.display     = 'block';
        document.getElementById('errorMessage').textContent = message;
    }

    loadTicket();

    // ── Resume seat picker untuk VVIP/VIP yang belum pilih kursi ──
    async function _resumeSeatPicker(ticketId, ticketNumber, zone, totalSeats) {
        // Set state global
        window._pendingTicketId     = ticketId;
        window._pendingTicketNumber = ticketNumber;
        window._pendingZone         = zone;
        window._pendingTotalSeats   = parseInt(totalSeats) || 1;
        window._pendingCustomerName = document.getElementById('ticketName')?.textContent || '';
        window._pendingUniquePrice  = 0;

        // Load booked seats dari Supabase
        try {
            const REST     = `${SUPABASE_URL}/rest/v1`;
            const headers  = { 'apikey': SUPABASE_ANON, 'Authorization': `Bearer ${SUPABASE_ANON}` };
            const res      = await fetch(`${REST}/ticketing?select=seat_numbers&limit=2000`, { headers });
            const tickets  = await res.json();
            window.bookedSeats = new Set();
            tickets.forEach(t => {
                if (t.seat_numbers) {
                    try {
                        const seats = Array.isArray(t.seat_numbers) ? t.seat_numbers : JSON.parse(t.seat_numbers);
                        seats.forEach(s => window.bookedSeats.add(s));
                    } catch(_) {}
                }
            });
        } catch(e) { window.bookedSeats = new Set(); }

        // Inject seat picker modal (copy dari main.js — versi standalone)
        _injectSeatPickerStandalone();
        _renderSeatPickerLayout();

        const modal = document.getElementById('seatPickerModal');
        if (modal) { modal.style.display = 'flex'; setTimeout(() => modal.classList.add('active'), 10); }
    }

    // Versi standalone dari openSeatPickerModal (tanpa dependency seat-memanjang.js)
    function _injectSeatPickerStandalone() {
        if (document.getElementById('seatPickerModal')) return;

        const zone  = window._pendingZone || 'vvip';
        const color = zone === 'vvip' ? '#FFD700' : '#da0424';
        const label = zone === 'vvip' ? 'VVIP' : 'VIP';

        const overlay = document.createElement('div');
        overlay.id = 'seatPickerModal';
        overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,.75);display:none;align-items:center;justify-content:center;z-index:10000;padding:16px;';

        overlay.innerHTML = `
            <div class="spm-container" style="position:relative;">
                <div class="spm-header" style="border-bottom:2px solid ${color}22;">
                    <div class="spm-header-left">
                        <div class="spm-badge" style="background:${color}22;color:${color};border:1px solid ${color}55;">${label}</div>
                        <div>
                            <h2 class="spm-title">Pilih Kursi Anda</h2>
                            <p class="spm-subtitle" id="spmSubtitle">Zona ${label}</p>
                        </div>
                    </div>
                    <div class="spm-counter-wrap">
                        <div class="spm-counter" id="spmCounter" style="border-color:${color}55;color:${color};">
                            <span id="spmSelected">0</span><span class="spm-sep">/</span><span id="spmTotal">0</span>
                        </div>
                        <span class="spm-counter-label">dipilih</span>
                    </div>
                </div>
                <div class="spm-body">
                    <div class="spm-legend">
                        <div class="spm-leg-item"><div class="spm-dot" style="background:${color};"></div><span>Tersedia</span></div>
                        <div class="spm-leg-item"><div class="spm-dot spm-dot-selected" style="background:${color};outline:2px solid #fff;"></div><span>Dipilih</span></div>
                        <div class="spm-leg-item"><div class="spm-dot spm-dot-sold"></div><span>Terjual</span></div>
                    </div>
                    <div class="spm-stage-label">▲ PANGGUNG</div>
                    <div class="spm-seat-scroll">
                        <div id="spmSeatGrid" class="spm-seat-grid"></div>
                    </div>
                    <div id="spmSelectedList" class="spm-selected-list" style="display:none;"></div>
                </div>
                <div class="spm-footer">
                    <div class="spm-footer-info">
                        <span id="spmFooterText" style="color:#94a3b8;font-size:.85rem;">Belum ada kursi dipilih</span>
                    </div>
                    <div class="spm-footer-actions">
                        <button class="spm-btn-clear" onclick="_spmClearSelection()" id="spmClearBtn" disabled>Hapus Pilihan</button>
                        <button class="spm-btn-confirm" onclick="_spmConfirm()" id="spmConfirmBtn" disabled
                            style="background:${color};color:${zone === 'vvip' ? '#1a1a1a' : '#fff'};">
                            <span id="spmConfirmText">Konfirmasi Kursi</span>
                        </button>
                    </div>
                </div>
            </div>`;

        // CSS
        if (!document.getElementById('spm-style')) {
            const s = document.createElement('style');
            s.id = 'spm-style';
            s.textContent = `
                .spm-container {
                    max-width:700px;width:95vw;max-height:92vh;overflow:hidden;
                    display:flex;flex-direction:column;border-radius:20px;
                    background:#0f172a;border:1px solid rgba(255,255,255,.1);
                }
                .spm-header { padding:20px 24px 16px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-shrink:0; }
                .spm-header-left { display:flex;align-items:center;gap:12px; }
                .spm-badge { padding:5px 14px;border-radius:50px;font-size:.8rem;font-weight:800;letter-spacing:1px;flex-shrink:0; }
                .spm-title { font-size:1.1rem;font-weight:700;color:#f1f5f9;margin:0 0 2px; }
                .spm-subtitle { font-size:.8rem;color:#64748b;margin:0; }
                .spm-counter-wrap { display:flex;flex-direction:column;align-items:center;gap:2px; }
                .spm-counter { font-size:1.5rem;font-weight:800;border:2px solid;border-radius:10px;padding:4px 14px;line-height:1.2;min-width:80px;text-align:center;font-family:monospace; }
                .spm-sep { color:#475569;font-size:1.2rem; }
                .spm-counter-label { font-size:.72rem;color:#64748b;font-weight:600;letter-spacing:.5px; }
                .spm-body { flex:1;overflow-y:auto;padding:0 24px 16px; }
                .spm-legend { display:flex;gap:16px;margin-bottom:10px;flex-wrap:wrap; }
                .spm-leg-item { display:flex;align-items:center;gap:6px;font-size:.78rem;color:#94a3b8; }
                .spm-dot { width:14px;height:14px;border-radius:4px; }
                .spm-dot-sold { background:#374151 !important; }
                .spm-stage-label { text-align:center;font-size:.72rem;font-weight:700;letter-spacing:2px;color:#475569;margin-bottom:8px;padding:6px;background:rgba(255,255,255,.03);border-radius:6px;border:1px solid rgba(255,255,255,.06); }
                .spm-seat-scroll { overflow-x:auto;padding-bottom:4px; }
                .spm-seat-grid { display:flex;flex-direction:column;gap:5px;align-items:center;min-width:max-content;padding:8px 0; }
                .spm-row { display:flex;align-items:center;gap:4px; }
                .spm-row-label { font-size:.65rem;color:#475569;font-weight:700;width:28px;text-align:right;flex-shrink:0;font-family:monospace; }
                .spm-seat { width:26px;height:26px;border-radius:5px;border:1.5px solid;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:.6rem;font-weight:700;transition:transform .12s,box-shadow .12s;position:relative;user-select:none; }
                .spm-seat:hover:not(.spm-sold):not(.spm-selected) { transform:scale(1.15);z-index:2; }
                .spm-seat.spm-selected { transform:scale(1.1);box-shadow:0 0 10px var(--spm-color); }
                .spm-seat.spm-sold { cursor:not-allowed;opacity:.35; }
                .spm-seat-gap { width:14px; }
                .spm-selected-list { margin-top:14px;padding:12px 14px;background:rgba(255,255,255,.04);border-radius:10px;border:1px solid rgba(255,255,255,.08); }
                .spm-sel-title { font-size:.75rem;color:#64748b;font-weight:700;letter-spacing:.5px;text-transform:uppercase;margin-bottom:8px; }
                .spm-sel-tags { display:flex;flex-wrap:wrap;gap:6px; }
                .spm-sel-tag { padding:4px 10px;border-radius:6px;font-size:.78rem;font-weight:600;font-family:monospace; }
                .spm-footer { padding:14px 24px;border-top:1px solid rgba(255,255,255,.08);display:flex;align-items:center;justify-content:space-between;gap:12px;flex-shrink:0; }
                .spm-footer-info { flex:1;min-width:0; }
                .spm-footer-actions { display:flex;gap:10px;flex-shrink:0; }
                .spm-btn-clear { padding:10px 16px;border-radius:10px;border:1px solid rgba(255,255,255,.15);background:transparent;color:#94a3b8;font-size:.85rem;font-weight:600;cursor:pointer;transition:background .15s; }
                .spm-btn-clear:hover:not(:disabled) { background:rgba(255,255,255,.07); }
                .spm-btn-clear:disabled { opacity:.35;cursor:not-allowed; }
                .spm-btn-confirm { padding:10px 22px;border-radius:10px;border:none;font-size:.9rem;font-weight:700;cursor:pointer;transition:opacity .15s,transform .1s; }
                .spm-btn-confirm:hover:not(:disabled) { opacity:.88; }
                .spm-btn-confirm:disabled { opacity:.35;cursor:not-allowed; }
                .spm-saving-overlay { position:absolute;inset:0;border-radius:20px;background:rgba(15,23,42,.92);display:flex;flex-direction:column;align-items:center;justify-content:center;gap:14px;z-index:10; }
                .spm-saving-spinner { width:40px;height:40px;border:3px solid rgba(255,255,255,.1);border-top-color:currentColor;border-radius:50%;animation:spm-spin .7s linear infinite; }
                @keyframes spm-spin { to { transform:rotate(360deg); } }
                @media(max-width:480px) { .spm-container{width:100vw;max-height:100vh;border-radius:16px 16px 0 0;} .spm-seat{width:22px;height:22px;} }
            `;
            document.head.appendChild(s);
        }
        document.body.appendChild(overlay);
    }

    function _renderSeatPickerLayout() {
        const zone   = window._pendingZone || 'vvip';
        const total  = window._pendingTotalSeats || 1;
        const color  = zone === 'vvip' ? '#FFD700' : '#da0424';
        const label  = zone === 'vvip' ? 'VVIP' : 'VIP';

        const subtitle = document.getElementById('spmSubtitle');
        const spmTotal = document.getElementById('spmTotal');
        if (subtitle) subtitle.textContent = `Pilih tepat ${total} kursi zona ${label}`;
        if (spmTotal)  spmTotal.textContent  = total;

        // Baris zona dari ticketConfig JS global
        const cfg      = (window.ticketConfig || []).find(t => t.type === zone);
        const rowStart = cfg?.rowStart ?? (zone === 'vvip' ? 1 : 4);
        const rowEnd   = cfg?.rowEnd   ?? (zone === 'vvip' ? 3 : 8);

        const grid = document.getElementById('spmSeatGrid');
        if (!grid) return;
        grid.innerHTML = '';

        const angledRows = window.ANGLED_ROWS || {};
        const normalCols = window.NORMAL_COLS  || 25;

        window._spmSelected = new Set();

        for (let row = rowStart; row <= rowEnd; row++) {
            const cols  = angledRows[row] ?? normalCols;
            const rowEl = document.createElement('div');
            rowEl.className = 'spm-row';

            const rowLabel = document.createElement('div');
            rowLabel.className   = 'spm-row-label';
            rowLabel.textContent = `B${row}`;
            rowEl.appendChild(rowLabel);

            ['L','R'].forEach((side, sideIdx) => {
                if (sideIdx === 1) { const g = document.createElement('div'); g.className='spm-seat-gap'; rowEl.appendChild(g); }
                for (let seat = 1; seat <= cols; seat++) {
                    const seatId = `${side}-${row}-${seat}`;
                    const el     = document.createElement('div');
                    el.className       = 'spm-seat';
                    el.dataset.seatId  = seatId;
                    const isSold = window.bookedSeats?.has(seatId);
                    if (isSold) {
                        el.classList.add('spm-sold');
                        el.style.borderColor = '#374151';
                        el.style.background  = '#1f2937';
                    } else {
                        el.classList.add('spm-available');
                        el.style.borderColor = color + '80';
                        el.style.background  = color + '18';
                        el.style.setProperty('--spm-color', color);
                        el.addEventListener('click', () => _spmToggleSeat(seatId, el, color));
                    }
                    rowEl.appendChild(el);
                }
            });
            grid.appendChild(rowEl);
        }
    }

    function _spmToggleSeat(seatId, el, color) {
        const total = window._pendingTotalSeats || 1;
        const sel   = window._spmSelected;
        if (sel.has(seatId)) {
            sel.delete(seatId);
            el.classList.remove('spm-selected');
            el.style.borderColor = color + '80';
            el.style.background  = color + '18';
        } else {
            if (sel.size >= total) {
                const firstId = [...sel][0];
                sel.delete(firstId);
                const firstEl = document.querySelector(`#spmSeatGrid [data-seat-id="${firstId}"]`);
                if (firstEl) { firstEl.classList.remove('spm-selected'); firstEl.style.borderColor=color+'80'; firstEl.style.background=color+'18'; }
            }
            sel.add(seatId);
            el.classList.add('spm-selected');
            el.style.borderColor = color;
            el.style.background  = color + '33';
        }
        _spmUpdateUI(color);
    }

    function _spmUpdateUI(color) {
        const sel   = window._spmSelected;
        const total = window._pendingTotalSeats || 1;
        const count = sel.size;
        const zone  = window._pendingZone || 'vvip';
        const label = zone === 'vvip' ? 'VVIP' : 'VIP';

        const selEl    = document.getElementById('spmSelected');
        const clearBtn = document.getElementById('spmClearBtn');
        const confBtn  = document.getElementById('spmConfirmBtn');
        const footerTx = document.getElementById('spmFooterText');
        const selList  = document.getElementById('spmSelectedList');

        if (selEl)    selEl.textContent = count;
        if (clearBtn) clearBtn.disabled = (count === 0);
        if (confBtn)  confBtn.disabled  = (count !== total);
        if (footerTx) {
            footerTx.textContent = count === 0 ? 'Belum ada kursi dipilih'
                : count < total ? `Pilih ${total - count} kursi lagi`
                : `✅ ${count} kursi dipilih — siap dikonfirmasi`;
            footerTx.style.color = count === total ? color : '#94a3b8';
        }
        if (selList) {
            if (count === 0) { selList.style.display = 'none'; return; }
            selList.style.display = 'block';
            selList.innerHTML = `
                <div class="spm-sel-title">Kursi Terpilih</div>
                <div class="spm-sel-tags">${[...sel].map(sid => {
                    const p = sid.split('-');
                    return `<div class="spm-sel-tag" style="background:${color}22;color:${color};border:1px solid ${color}44;">
                        ${label} ${p[0]==='L'?'Kiri':'Kanan'} B${p[1]}-${p[2]}</div>`;
                }).join('')}</div>`;
        }
    }

    function _spmClearSelection() {
        const zone  = window._pendingZone || 'vvip';
        const color = zone === 'vvip' ? '#FFD700' : '#da0424';
        window._spmSelected.forEach(seatId => {
            const el = document.querySelector(`#spmSeatGrid [data-seat-id="${seatId}"]`);
            if (el) { el.classList.remove('spm-selected'); el.style.borderColor=color+'80'; el.style.background=color+'18'; }
        });
        window._spmSelected.clear();
        _spmUpdateUI(color);
    }

    async function _spmConfirm() {
        const sel   = window._spmSelected;
        const total = window._pendingTotalSeats || 1;
        if (sel.size !== total) return;

        const ticketId    = window._pendingTicketId;
        const zone        = window._pendingZone;
        const color       = zone === 'vvip' ? '#FFD700' : '#da0424';

        const container = document.querySelector('.spm-container');
        const loader    = document.createElement('div');
        loader.className = 'spm-saving-overlay';
        loader.innerHTML = `<div class="spm-saving-spinner" style="color:${color};"></div>
            <div style="color:${color};font-weight:700;font-size:.9rem;">Menyimpan pilihan kursi...</div>`;
        if (container) { container.style.position='relative'; container.appendChild(loader); }

        try {
            const REST    = `${SUPABASE_URL}/rest/v1`;
            const hdrs    = { 'apikey':SUPABASE_ANON,'Authorization':`Bearer ${SUPABASE_ANON}`,'Content-Type':'application/json','Prefer':'return=representation' };
            const res     = await fetch(`${REST}/ticketing?id=eq.${ticketId}`, {
                method: 'PATCH', headers: hdrs,
                body: JSON.stringify({
                    seat_numbers: [...sel],
                    seat_selection_status: 'completed',
                    seat_selected_at: new Date().toISOString()
                })
            });
            if (!res.ok) throw new Error('Gagal menyimpan kursi.');

            // Tutup modal & reload halaman tiket agar data fresh
            const modal = document.getElementById('seatPickerModal');
            if (modal) { modal.classList.remove('active'); setTimeout(() => { modal.style.display='none'; window.location.reload(); }, 300); }
        } catch(err) {
            if (container && loader) container.removeChild(loader);
            alert('Gagal menyimpan: ' + err.message);
        }
    }
    </script>

</body>
</html>