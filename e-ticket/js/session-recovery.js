// ============================================================
// SESSION RECOVERY
// Simpan state pemesanan ke localStorage, pulihkan otomatis
// saat halaman dibuka kembali (dalam 24 jam).
//
// Key localStorage  : 'bvr_session'   (booking recovery)
// Data yang disimpan:
//   ticket_number, ticket_id, name, phone,
//   zone, total_seats, unique_price, total_price,
//   stage: 'pending_seat' | 'pending_wa'
//   saved_at: ISO timestamp
// ============================================================

(function () {
    'use strict';

    const LS_KEY    = 'bvr_session';
    const TTL_MS    = 24 * 60 * 60 * 1000; // 24 jam

    // ── Simpan sesi ─────────────────────────────────────────
    window.BVR = {

        save: function (data) {
            try {
                localStorage.setItem(LS_KEY, JSON.stringify({
                    ...data,
                    saved_at: new Date().toISOString(),
                }));
            } catch (e) { /* localStorage penuh / private mode */ }
        },

        load: function () {
            try {
                const raw = localStorage.getItem(LS_KEY);
                if (!raw) return null;
                const data = JSON.parse(raw);
                // Cek TTL 24 jam
                if (!data.saved_at) return null;
                if (Date.now() - new Date(data.saved_at).getTime() > TTL_MS) {
                    localStorage.removeItem(LS_KEY);
                    return null;
                }
                return data;
            } catch (e) { return null; }
        },

        clear: function () {
            try { localStorage.removeItem(LS_KEY); } catch (e) {}
        },

        // ── Panggil ini setelah submit berhasil (VVIP/VIP — menunggu pilih kursi)
        savePendingSeat: function (ticketId, ticketNumber, name, phone, zone, totalSeats, uniquePrice, totalPrice) {
            BVR.save({
                stage       : 'pending_seat',
                ticket_id   : ticketId,
                ticket_number: ticketNumber,
                name        : name,
                phone       : phone,
                zone        : zone,
                total_seats : totalSeats,
                unique_price: uniquePrice,
                total_price : totalPrice,
            });
        },

        // ── Panggil ini setelah submit berhasil (semua tipe — menunggu WA)
        savePendingWA: function (ticketNumber, name, phone, uniquePrice, totalPrice, totalSeats) {
            BVR.save({
                stage        : 'pending_wa',
                ticket_number: ticketNumber,
                name         : name,
                phone        : phone,
                unique_price : uniquePrice,
                total_price  : totalPrice,
                total_seats  : totalSeats,
            });
        },
    };

    // ── CSS recovery card ────────────────────────────────────
    function injectCSS() {
        if (document.getElementById('bvr-style')) return;
        const s = document.createElement('style');
        s.id = 'bvr-style';
        s.textContent = `
            /* ── Overlay ── */
            .bvr-overlay {
                position: fixed; inset: 0; z-index: 11000;
                background: rgba(0,0,0,.7); backdrop-filter: blur(7px);
                display: flex; align-items: center; justify-content: center;
                opacity: 0; transition: opacity .22s;
            }
            .bvr-overlay.bvr-show { opacity: 1; }

            /* ── Card ── */
            .bvr-card {
                background: #0f172a;
                border: 1.5px solid rgba(99,102,241,.4);
                border-radius: 22px; padding: 28px 26px 24px;
                width: min(400px, 94vw);
                box-shadow: 0 28px 70px rgba(0,0,0,.65),
                            0 0 0 1px rgba(255,255,255,.05),
                            0 0 40px rgba(99,102,241,.12);
                transform: translateY(16px) scale(.95);
                transition: transform .28s cubic-bezier(.34,1.5,.64,1);
            }
            .bvr-overlay.bvr-show .bvr-card { transform: translateY(0) scale(1); }

            /* ── Header ── */
            .bvr-header {
                display: flex; align-items: center; gap: 14px; margin-bottom: 18px;
            }
            .bvr-icon-wrap {
                width: 48px; height: 48px; border-radius: 14px; flex-shrink: 0;
                display: flex; align-items: center; justify-content: center;
                font-size: 1.5rem;
                background: rgba(99,102,241,.15);
                border: 1px solid rgba(99,102,241,.3);
            }
            .bvr-title {
                font-size: 1rem; font-weight: 800; color: #f1f5f9; margin-bottom: 3px;
            }
            .bvr-subtitle { font-size: .78rem; color: #64748b; }

            /* ── Info rows ── */
            .bvr-info {
                background: rgba(255,255,255,.04);
                border: 1px solid rgba(255,255,255,.08);
                border-radius: 12px; padding: 14px 16px;
                margin-bottom: 16px;
            }
            .bvr-row {
                display: flex; justify-content: space-between; align-items: center;
                padding: 5px 0; font-size: .83rem;
            }
            .bvr-row + .bvr-row { border-top: 1px solid rgba(255,255,255,.05); }
            .bvr-row-label { color: #64748b; font-weight: 600; }
            .bvr-row-val   { color: #f1f5f9; font-weight: 700; text-align: right; max-width: 60%; }
            .bvr-row-val.accent { color: #818cf8; }

            /* ── Stage badge ── */
            .bvr-stage-badge {
                display: inline-flex; align-items: center; gap: 6px;
                border-radius: 8px; padding: 7px 12px;
                font-size: .78rem; font-weight: 700; margin-bottom: 16px; width: 100%;
                box-sizing: border-box;
            }
            .bvr-stage-badge.seat {
                background: rgba(251,191,36,.1);
                border: 1px solid rgba(251,191,36,.3);
                color: #fbbf24;
            }
            .bvr-stage-badge.wa {
                background: rgba(34,197,94,.1);
                border: 1px solid rgba(34,197,94,.3);
                color: #4ade80;
            }

            /* ── Saved time ── */
            .bvr-saved-time {
                font-size: .72rem; color: #475569; text-align: center;
                margin-bottom: 16px;
            }

            /* ── Actions ── */
            .bvr-actions { display: flex; gap: 10px; }
            .bvr-btn-discard {
                flex: 1; padding: 11px;
                background: transparent;
                border: 1.5px solid rgba(255,255,255,.13);
                border-radius: 12px; color: #94a3b8;
                font-size: .86rem; font-weight: 700; cursor: pointer;
                transition: background .15s; font-family: inherit;
            }
            .bvr-btn-discard:hover { background: rgba(255,255,255,.07); color: #f1f5f9; }
            .bvr-btn-resume {
                flex: 1; padding: 11px;
                background: linear-gradient(135deg, #6366f1, #4f46e5);
                border: none; border-radius: 12px; color: #fff;
                font-size: .88rem; font-weight: 800; cursor: pointer;
                transition: opacity .15s, transform .1s; font-family: inherit;
                display: flex; align-items: center; justify-content: center; gap: 8px;
            }
            .bvr-btn-resume:hover { opacity: .9; }
            .bvr-btn-resume:active { transform: scale(.97); }
            .bvr-btn-resume:disabled {
                opacity: .4; cursor: wait;
            }
            .bvr-spinner {
                width: 16px; height: 16px; border: 2px solid rgba(255,255,255,.3);
                border-top-color: #fff; border-radius: 50%;
                animation: bvr-spin .6s linear infinite; flex-shrink: 0;
            }
            @keyframes bvr-spin { to { transform: rotate(360deg); } }
        `;
        document.head.appendChild(s);
    }

    // ── Format tanggal singkat ───────────────────────────────
    function formatSavedAt(isoStr) {
        try {
            const d    = new Date(isoStr);
            const now  = new Date();
            const diff = Math.floor((now - d) / 60000); // menit
            if (diff < 1)  return 'baru saja';
            if (diff < 60) return `${diff} menit yang lalu`;
            const h = Math.floor(diff / 60);
            if (h < 24) return `${h} jam yang lalu`;
            return `${Math.floor(h/24)} hari yang lalu`;
        } catch { return ''; }
    }

    // ── Tampilkan recovery card ──────────────────────────────
    function showRecoveryCard(session, onResume, onDiscard) {
        injectCSS();

        const isPendingSeat = session.stage === 'pending_seat';
        const fmt = p => new Intl.NumberFormat('id-ID',{
            style:'currency', currency:'IDR', minimumFractionDigits:0
        }).format(p);

        const overlay = document.createElement('div');
        overlay.id        = 'bvrOverlay';
        overlay.className = 'bvr-overlay';
        overlay.innerHTML = `
            <div class="bvr-card">
                <div class="bvr-header">
                    <div class="bvr-icon-wrap">${isPendingSeat ? '🪑' : '✅'}</div>
                    <div>
                        <div class="bvr-title">Sesi Pemesanan Ditemukan</div>
                        <div class="bvr-subtitle">Lanjutkan proses yang belum selesai?</div>
                    </div>
                </div>

                <div class="bvr-stage-badge ${isPendingSeat ? 'seat' : 'wa'}">
                    ${isPendingSeat
                        ? '🎯 Menunggu pemilihan kursi'
                        : '💬 Menunggu bergabung grup WhatsApp'}
                </div>

                <div class="bvr-info">
                    <div class="bvr-row">
                        <span class="bvr-row-label">Nama</span>
                        <span class="bvr-row-val">${session.name}</span>
                    </div>
                    <div class="bvr-row">
                        <span class="bvr-row-label">No. Tiket</span>
                        <span class="bvr-row-val accent">${session.ticket_number}</span>
                    </div>
                    ${isPendingSeat ? `
                    <div class="bvr-row">
                        <span class="bvr-row-label">Zona</span>
                        <span class="bvr-row-val">${(session.zone||'').toUpperCase()} · ${session.total_seats} kursi</span>
                    </div>` : ''}
                    <div class="bvr-row">
                        <span class="bvr-row-label">Nominal Transfer</span>
                        <span class="bvr-row-val accent">${fmt(session.unique_price || session.total_price)}</span>
                    </div>
                </div>

                <div class="bvr-saved-time">
                    🕐 Terakhir disimpan: ${formatSavedAt(session.saved_at)}
                </div>

<div class="bvr-actions">
    <button class="bvr-btn-resume" id="bvrBtnResume">
        <span id="bvrBtnText">Lanjutkan →</span>
    </button>
</div>
            </div>`;

        document.body.appendChild(overlay);
        requestAnimationFrame(() => overlay.classList.add('bvr-show'));


        document.getElementById('bvrBtnResume').addEventListener('click', async () => {
            const btn  = document.getElementById('bvrBtnResume');
            const text = document.getElementById('bvrBtnText');
            btn.disabled = true;
            text.innerHTML = '<div class="bvr-spinner"></div> Memuat...';
            try {
                await onResume();
            } catch (e) {
                btn.disabled  = false;
                text.textContent = 'Lanjutkan →';
                // Tampilkan error ringan
                const card = document.querySelector('.bvr-card');
                if (card) {
                    const err = document.createElement('div');
                    err.style.cssText = 'margin-top:10px;padding:8px 12px;background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.3);border-radius:8px;font-size:.78rem;color:#fca5a5;text-align:center;';
                    err.textContent = 'Gagal memuat data. Coba lagi atau abaikan sesi ini.';
                    card.appendChild(err);
                }
            }
        });
    }

    function closeRecovery() {
        const overlay = document.getElementById('bvrOverlay');
        if (!overlay) return;
        overlay.classList.remove('bvr-show');
        setTimeout(() => overlay.remove(), 220);
    }

    // ── Jalankan recovery saat DOM siap ─────────────────────
    document.addEventListener('DOMContentLoaded', async function () {
        // Tunggu semua script (SB, ticketConfig, dll) siap
        await new Promise(r => setTimeout(r, 600));

        const session = BVR.load();
        if (!session) return;

        // Validasi: cek ke Supabase apakah tiket masih dalam status yang perlu recovery
        let ticket = null;
        try {
            ticket = await SB.getTicketByNumber(session.ticket_number);
        } catch (e) {
            BVR.clear(); // tiket tidak ada di DB → hapus cache
            return;
        }

        // Tentukan apakah masih perlu recovery
        const needsSeatPick = ticket.seat_selection_status === 'pending_seat'
                           && ticket.status !== 'cancelled';
        const needsWA       = ticket.status === 'pending'
                           && ticket.wa_clicked !== true
                           && ticket.seat_selection_status !== 'pending_seat';

        if (!needsSeatPick && !needsWA) {
            BVR.clear(); // sudah selesai, tidak perlu recovery
            return;
        }

        showRecoveryCard(session,
            // onResume
            async () => {
                if (needsSeatPick) {
                    // Pulihkan state global untuk seat-picker
                    window._pendingTicketId     = ticket.id;
                    window._pendingTicketNumber = ticket.ticket_number;
                    window._pendingZone         = ticket.selected_zone || session.zone;
                    window._pendingTotalSeats   = ticket.total_seats   || session.total_seats;
                    window._pendingCustomerName = ticket.name          || session.name;
                    window._pendingUniquePrice  = ticket.price         || session.unique_price;
                    currentTotalPrice           = session.total_price  || ticket.price;

                    // Pulihkan selectedSeatsData agar summary modal benar
                    const zone  = window._pendingZone;
                    const price = window.ticketPrices?.[zone] || 0;
                    window.selectedSeatsData = [];
                    for (let i = 0; i < window._pendingTotalSeats; i++)
                        window.selectedSeatsData.push({ seatId: null, zone, label: zone.toUpperCase(), price });

                    await loadBookedSeats();
                    closeRecovery();
                    if (typeof activateSeatPickerMode === 'function') activateSeatPickerMode();

                } else if (needsWA) {
                    // Pulihkan success message
                    currentTotalPrice = session.total_price || ticket.price;
                    window.selectedSeatsData = Array.from({ length: ticket.total_seats || 1 }, () => ({
                        seatId: null, zone: ticket.primary_ticket_type, label: '', price: 0,
                    }));
                    window.classSectionTickets = {};

                    closeRecovery();
                    // Buka booking modal dalam mode success
                    const bookingModal = document.getElementById('bookingModal');
                    if (bookingModal) {
                        const form = document.getElementById('bookingForm');
                        if (form) form.style.display = 'none';
                        bookingModal.style.display = 'flex';
                        setTimeout(() => bookingModal.classList.add('active'), 10);
                    }
                    if (typeof showSuccessMessage === 'function')
                        showSuccessMessage(ticket.ticket_number, ticket.name, ticket.price);
                }
            },
            // onDiscard
            () => {
                BVR.clear();
            }
        );
    });

})();