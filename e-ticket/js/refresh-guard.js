// ============================================================
// REFRESH GUARD
// Cegah refresh/navigasi saat proses pemesanan sedang berlangsung.
// Berlaku untuk: F5, Ctrl+R, swipe-refresh mobile,
//               tombol back/forward browser, close tab.
// ============================================================

(function () {
    'use strict';

    // ── Cek apakah sedang dalam proses ──────────────────────
    function isProcessActive() {
        // 1. Modal booking terbuka
        const bookingModal = document.getElementById('bookingModal');
        if (bookingModal && bookingModal.style.display !== 'none'
            && bookingModal.classList.contains('active')) return true;

        // 2. Modal zona (pilih jumlah VVIP/VIP)
        if (document.getElementById('zoneModal')) return true;

        // 3. Mode seat-picker aktif (banner di atas denah)
        if (document.getElementById('spmBanner')) return true;

        // 4. Ada kursi / zona yang sudah dipilih di denah
        if (typeof selectedSeats !== 'undefined' && selectedSeats.size > 0) return true;
        if (typeof selectedZoneSections !== 'undefined' && selectedZoneSections.size > 0) return true;
        if (typeof window.classSectionTickets !== 'undefined') {
            const zoneTotal = Object.values(window.classSectionTickets).reduce((s, v) => s + v, 0);
            if (zoneTotal > 0) return true;
        }

        // 5. State global VVIP/VIP sudah diisi (tiket dibuat, menunggu pilih kursi)
        if (window._pendingTicketId) return true;

        // 6. Success message sedang tampil (jangan refresh sebelum klik WA)
        const successDiv = document.getElementById('successMessage');
        if (successDiv && successDiv.style.display !== 'none') return true;

        return false;
    }

    // ── Cek apakah tahap kritis (sudah bayar) ───────────────
    function isCriticalStage() {
        const successDiv = document.getElementById('successMessage');
        if (successDiv && successDiv.style.display !== 'none') return true;
        if (window._pendingTicketId) return true;
        return false;
    }

    // ── Teks kontekstual sesuai tahap ───────────────────────
    function getStageInfo() {
        const successDiv = document.getElementById('successMessage');
        if (successDiv && successDiv.style.display !== 'none') {
            return {
                icon : '✅',
                title: 'Pemesanan Hampir Selesai!',
                body : 'Anda sudah melakukan pembayaran. Jika refresh sekarang, <strong>link WhatsApp Group tidak akan muncul lagi</strong> dan tiket tidak bisa diklaim.',
                warn : true,
            };
        }
        if (window._pendingTicketId) {
            return {
                icon : '🪑',
                title: 'Sedang Memilih Kursi',
                body : 'Anda sedang memilih nomor kursi. Jika refresh, <strong>pemesanan yang sudah dibayar akan hilang</strong> dan kursi belum tercatat.',
                warn : true,
            };
        }
        const bookingModal = document.getElementById('bookingModal');
        if (bookingModal && bookingModal.classList.contains('active')) {
            return {
                icon : '💳',
                title: 'Sedang Proses Pembayaran',
                body : 'Anda sedang mengisi data dan menyelesaikan pembayaran. Jika refresh, <strong>semua data yang sudah diisi akan hilang</strong>.',
                warn : false,
            };
        }
        if (document.getElementById('zoneModal')) {
            return {
                icon : '🎟️',
                title: 'Sedang Memilih Zona',
                body : 'Anda sedang memilih zona tiket. Jika refresh, <strong>pilihan akan dibatalkan</strong>.',
                warn : false,
            };
        }
        return {
            icon : '⚠️',
            title: 'Batalkan Proses Pemesanan?',
            body : 'Anda sedang dalam proses pemesanan tiket. Jika refresh, <strong>semua pilihan kursi dan data pembayaran akan hilang</strong>.',
            warn : false,
        };
    }

    // ── Buat & tampilkan modal guard ─────────────────────────
    let guardActive = false;

    function showRefreshGuard(onConfirm) {
        if (guardActive) return;
        guardActive = true;

        const critical = isCriticalStage();
        const info     = getStageInfo();

        // Inject CSS sekali
        if (!document.getElementById('rg-style')) {
            const s = document.createElement('style');
            s.id = 'rg-style';
            s.textContent = `
                .rg-overlay {
                    position: fixed; inset: 0; z-index: 99999;
                    background: rgba(0,0,0,.65); backdrop-filter: blur(6px);
                    display: flex; align-items: center; justify-content: center;
                    opacity: 0; transition: opacity .2s;
                }
                .rg-overlay.rg-show { opacity: 1; }
                .rg-card {
                    background: #0f172a;
                    border: 1.5px solid rgba(239,68,68,.35);
                    border-radius: 20px; padding: 28px 28px 24px;
                    width: min(380px, 92vw);
                    box-shadow: 0 24px 64px rgba(0,0,0,.6), 0 0 0 1px rgba(255,255,255,.05);
                    transform: translateY(14px) scale(.96);
                    transition: transform .25s cubic-bezier(.34,1.5,.64,1);
                    text-align: center;
                }
                .rg-overlay.rg-show .rg-card { transform: translateY(0) scale(1); }
                .rg-icon { font-size: 2.4rem; margin-bottom: 10px; }
                .rg-title {
                    font-size: 1.05rem; font-weight: 800; color: #f1f5f9;
                    margin-bottom: 10px;
                }
                .rg-body {
                    font-size: .84rem; color: #94a3b8; line-height: 1.6;
                    margin-bottom: 22px;
                }
                .rg-body strong { color: #fca5a5; }
                .rg-warn-badge {
                    display: inline-flex; align-items: center; gap: 6px;
                    background: rgba(239,68,68,.12);
                    border: 1px solid rgba(239,68,68,.3);
                    border-radius: 8px; padding: 6px 12px;
                    font-size: .76rem; font-weight: 700; color: #fca5a5;
                    margin-bottom: 18px;
                }
                .rg-actions { display: flex; gap: 10px; }
                .rg-btn-cancel {
                    flex: 1; padding: 12px;
                    background: rgba(255,255,255,.07);
                    border: 1.5px solid rgba(255,255,255,.14);
                    border-radius: 12px; color: #f1f5f9;
                    font-size: .88rem; font-weight: 700; cursor: pointer;
                    transition: background .15s; font-family: inherit;
                }
                .rg-btn-cancel:hover { background: rgba(255,255,255,.13); }
                .rg-btn-confirm {
                    flex: 1; padding: 12px;
                    background: rgba(239,68,68,.85);
                    border: none; border-radius: 12px; color: #fff;
                    font-size: .88rem; font-weight: 700; cursor: pointer;
                    transition: opacity .15s, transform .1s; font-family: inherit;
                }
                .rg-btn-confirm:hover  { opacity: .88; }
                .rg-btn-confirm:active { transform: scale(.97); }

                /* ── Step-2 (double confirm) ── */
                .rg-step2-body {
                    font-size: .84rem; color: #94a3b8; line-height: 1.6;
                    margin-bottom: 8px;
                }
                .rg-step2-body strong { color: #fca5a5; }
                .rg-checkbox-row {
                    display: flex; align-items: flex-start; gap: 10px;
                    background: rgba(239,68,68,.08);
                    border: 1px solid rgba(239,68,68,.25);
                    border-radius: 10px; padding: 12px 14px;
                    margin-bottom: 20px; text-align: left; cursor: pointer;
                }
                .rg-checkbox-row input[type=checkbox] {
                    width: 18px; height: 18px; flex-shrink: 0;
                    accent-color: #ef4444; margin-top: 1px; cursor: pointer;
                }
                .rg-checkbox-label {
                    font-size: .8rem; color: #fca5a5; font-weight: 600;
                    line-height: 1.5; user-select: none;
                }
                .rg-btn-confirm:disabled {
                    opacity: .3; cursor: not-allowed; transform: none;
                }
                .rg-step2-shake {
                    animation: rg-shake .35s ease;
                }
                @keyframes rg-shake {
                    0%,100% { transform: translateX(0); }
                    20%     { transform: translateX(-6px); }
                    40%     { transform: translateX(6px); }
                    60%     { transform: translateX(-4px); }
                    80%     { transform: translateX(4px); }
                }
            `;
            document.head.appendChild(s);
        }

        const overlay = document.createElement('div');
        overlay.id        = 'rgOverlay';
        overlay.className = 'rg-overlay';

        // ── Step 1 HTML ──────────────────────────────────────
        function renderStep1() {
            overlay.innerHTML = `
                <div class="rg-card" id="rgCard">
                    <div class="rg-icon">${info.icon}</div>
                    <div class="rg-title">${info.title}</div>
                    ${info.warn
                        ? `<div class="rg-warn-badge">⚠️ Data pembayaran mungkin hilang</div>`
                        : ''}
                    <div class="rg-body">${info.body}</div>
                    <div class="rg-actions">
                        <button class="rg-btn-cancel"  id="rgBtnCancel">Tetap di Halaman</button>
                        <button class="rg-btn-confirm" id="rgBtnConfirm">
                            ${critical ? 'Lanjut →' : 'Ya, Refresh'}
                        </button>
                    </div>
                </div>`;

            document.getElementById('rgBtnCancel').addEventListener('click', closeGuard);
            document.getElementById('rgBtnConfirm').addEventListener('click', () => {
                if (critical) {
                    // Lanjut ke step 2
                    renderStep2();
                } else {
                    closeGuard();
                    onConfirm();
                }
            });
        }

        // ── Step 2 HTML (hanya untuk kondisi kritis) ─────────
        function renderStep2() {
            const card = document.getElementById('rgCard');
            // Animasi pergantian
            card.style.transition = 'opacity .15s, transform .15s';
            card.style.opacity    = '0';
            card.style.transform  = 'scale(.95)';

            setTimeout(() => {
                overlay.innerHTML = `
                    <div class="rg-card" id="rgCard" style="border-color:rgba(239,68,68,.6);">
                        <div class="rg-icon">🚨</div>
                        <div class="rg-title">Konfirmasi Akhir</div>
                        <div class="rg-step2-body">
                            Ini adalah konfirmasi terakhir.<br>
                            <strong>Data pemesanan Anda yang sudah dibayar tidak dapat dipulihkan</strong>
                            setelah refresh.
                        </div>
                        <label class="rg-checkbox-row" for="rgCheckbox">
                            <input type="checkbox" id="rgCheckbox">
                            <span class="rg-checkbox-label">
                                Saya mengerti bahwa data pembayaran saya akan hilang dan tidak dapat dikembalikan.
                            </span>
                        </label>
                        <div class="rg-actions">
                            <button class="rg-btn-cancel" id="rgBtnBack">← Kembali</button>
                            <button class="rg-btn-confirm" id="rgBtnFinal" disabled
                                style="background:rgba(239,68,68,1);">
                                Refresh Sekarang
                            </button>
                        </div>
                    </div>`;

                // Animate in
                const newCard = document.getElementById('rgCard');
                newCard.style.opacity   = '0';
                newCard.style.transform = 'scale(.95) translateY(8px)';
                newCard.style.transition = 'opacity .2s, transform .2s cubic-bezier(.34,1.5,.64,1)';
                requestAnimationFrame(() => {
                    newCard.style.opacity   = '1';
                    newCard.style.transform = 'scale(1) translateY(0)';
                });

                const checkbox = document.getElementById('rgCheckbox');
                const finalBtn = document.getElementById('rgBtnFinal');

                checkbox.addEventListener('change', () => {
                    finalBtn.disabled = !checkbox.checked;
                });

                document.getElementById('rgBtnBack').addEventListener('click', () => {
                    // Kembali ke step 1
                    overlay.innerHTML = '';
                    renderStep1();
                    // Re-show card
                    requestAnimationFrame(() => {
                        const c = document.getElementById('rgCard');
                        if (c) { c.style.opacity = '1'; c.style.transform = ''; }
                    });
                });

                finalBtn.addEventListener('click', () => {
                    if (!checkbox.checked) {
                        // Shake jika belum centang
                        finalBtn.classList.remove('rg-step2-shake');
                        void finalBtn.offsetWidth;
                        finalBtn.classList.add('rg-step2-shake');
                        return;
                    }
                    closeGuard();
                    onConfirm();
                });
            }, 160);
        }

        renderStep1();
        document.body.appendChild(overlay);
        requestAnimationFrame(() => overlay.classList.add('rg-show'));
    }

    function closeGuard() {
        const overlay = document.getElementById('rgOverlay');
        if (!overlay) { guardActive = false; return; }
        overlay.classList.remove('rg-show');
        setTimeout(() => { overlay.remove(); guardActive = false; }, 220);
    }

    // ── 1. beforeunload — browser native (F5, Ctrl+R, close tab) ──
    // Native handler hanya bisa memunculkan dialog default browser.
    // Kita tampilkan modal kita sendiri hanya untuk kasus yang bisa kita intercept.
    window.addEventListener('beforeunload', function (e) {
        if (!isProcessActive()) return;
        // Trigger native dialog (F5 / close tab tidak bisa di-override sepenuhnya)
        e.preventDefault();
        e.returnValue = ''; // required untuk Chrome/Firefox
    });

    // ── 2. Pull-to-refresh mobile (touchstart + scroll) ──────
    // Deteksi swipe turun saat sudah di posisi scroll paling atas
    let touchStartY = 0;

    document.addEventListener('touchstart', function (e) {
        touchStartY = e.touches[0].clientY;
    }, { passive: true });

    document.addEventListener('touchmove', function (e) {
        if (!isProcessActive()) return;
        const dy = e.touches[0].clientY - touchStartY;
        const atTop = (window.scrollY === 0);
        if (atTop && dy > 60) {
            // Sudah dalam pull-to-refresh territory — cegah native & tunjukkan modal
            e.preventDefault();
        }
    }, { passive: false });

    // ── 3. Keyboard shortcut (F5, Ctrl+R, Cmd+R) ────────────
    document.addEventListener('keydown', function (e) {
        if (!isProcessActive()) return;
        const isRefresh = e.key === 'F5'
            || (e.ctrlKey && e.key === 'r')
            || (e.metaKey && e.key === 'r');
        if (!isRefresh) return;
        e.preventDefault();
        showRefreshGuard(() => { window.location.reload(); });
    });

    // ── 4. Tombol back/forward browser (popstate) ───────────
    // Push dummy state agar ada history entry yang bisa dicegat
    history.pushState({ rgGuard: true }, '');
    window.addEventListener('popstate', function (e) {
        if (!isProcessActive()) {
            return; // biarkan navigasi normal
        }
        // Push lagi agar tetap di halaman ini sementara modal muncul
        history.pushState({ rgGuard: true }, '');
        showRefreshGuard(() => {
            // Hapus dummy state lalu go back
            history.go(-2);
        });
    });

})();