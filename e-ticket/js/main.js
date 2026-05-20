// ============================================================
// MAIN.JS
// Storage: LOKAL via upload.php (bukan Supabase Storage)
// SUPABASE_URL, SUPABASE_ANON, ticketPrices, ticketConfig
// sudah tersedia sebagai window variable dari PHP (config.php)
// ============================================================

let currentStep = 1;
let selectedFile = null;

// ============================================================
// REFRESH SEAT STATUS (selalu ambil dari server, no-cache)
// ============================================================
async function refreshSeatStatus() {
    try {
        // Tambah timestamp agar browser tidak memakai cache apapun
        const res = await fetch('/e-ticket/seat-status.php?_=' + Date.now(), {
            cache: 'no-store',
            headers: { 'Cache-Control': 'no-cache', 'Pragma': 'no-cache' }
        });
        if (!res.ok) return;
        const fresh = await res.json();
        window.seatStatus = fresh;
    } catch (e) {
        console.warn('Gagal refresh seat status:', e);
    }
}

// ============================================================
// INITIALIZE
// ============================================================
document.addEventListener('DOMContentLoaded', async function () {
    const isIndexPage         = document.getElementById('singleTicketContainer');
    const isSeatSelectionPage = document.querySelector('.seat-selection-page');

    // Selalu refresh seatStatus dari server saat halaman dibuka,
    // agar tidak bergantung pada cache HTML yang mungkin stale
    await refreshSeatStatus();

    if (isIndexPage) {
        renderTickets();
        setInterval(async () => { await refreshSeatStatus(); renderTickets(); }, 30000);
    }

    if (isSeatSelectionPage) {
        loadBookedSeats();
        // Refresh seatStatus setiap 30 detik agar status closed selalu sinkron
        setInterval(refreshSeatStatus, 30000);
    }
});

// ============================================================
// FETCH BOOKED SEATS
// ============================================================
async function loadBookedSeats() {
    try {
        const tickets = await SB.getAllTickets();

        if (typeof bookedSeats !== 'undefined') {
            bookedSeats.clear();

            // Reset zoneSoldCounts
            window.zoneSoldCounts = {};

            tickets.forEach(ticket => {
                // 1. Tandai kursi individual (VVIP/VIP) sebagai sold
                if (ticket.seat_numbers) {
                    try {
                        const seats = Array.isArray(ticket.seat_numbers)
                            ? ticket.seat_numbers
                            : JSON.parse(ticket.seat_numbers);
                        seats.forEach(seatId => bookedSeats.add(seatId));
                    } catch (e) { console.error('Error parsing seat numbers:', e); }
                }

                // 2. Hitung kuota terjual untuk zona overlay (Kelas 1, Reguler, dst.)
                //    Distribusi: tiket genap → L, tiket ganjil → R (bergantian per zona)
                if (ticket.ticket_type) {
                    try {
                        let ticketTypes = Array.isArray(ticket.ticket_type)
                            ? ticket.ticket_type
                            : (typeof ticket.ticket_type === 'string'
                                ? (ticket.ticket_type.startsWith('[') ? JSON.parse(ticket.ticket_type) : [ticket.ticket_type])
                                : []);

                        const cfg = window.ticketConfig || [];
                        // Hitung per type berapa yang terjual di tiket ini
                        const typeCountInTicket = {};
                        ticketTypes.forEach(type => {
                            const typeIdx = cfg.findIndex(t => t.type === type);
                            if (typeIdx >= 2) { // hanya zona overlay
                                typeCountInTicket[type] = (typeCountInTicket[type] || 0) + 1;
                            }
                        });

                        // Distribusikan sold ke L dan R secara merata (berdasarkan total kumulatif)
                        Object.entries(typeCountInTicket).forEach(([type, count]) => {
                            const keyL = `${type}-L`;
                            const keyR = `${type}-R`;
                            const prevTotal = (window.zoneSoldCounts[keyL] || 0) + (window.zoneSoldCounts[keyR] || 0);
                            // Distribusi bergantian: dari prevTotal, isi L dulu lalu R
                            for (let i = 0; i < count; i++) {
                                const globalIdx = prevTotal + i;
                                if (globalIdx % 2 === 0) {
                                    window.zoneSoldCounts[keyL] = (window.zoneSoldCounts[keyL] || 0) + 1;
                                } else {
                                    window.zoneSoldCounts[keyR] = (window.zoneSoldCounts[keyR] || 0) + 1;
                                }
                            }
                        });
                    } catch (e) { console.error('Error computing zone sold counts:', e); }
                }
            });

            // Tandai kursi individual sebagai sold di UI
            bookedSeats.forEach(seatId => {
                const seat = document.querySelector(`[data-seat-id="${seatId}"]`);
                if (seat) { seat.classList.add('sold'); seat.style.pointerEvents = 'none'; }
            });

            // Refresh overlay cards setelah zoneSoldCounts diupdate
            if (typeof refreshAllOverlayCards === 'function') {
                refreshAllOverlayCards();
            }
        }
    } catch (error) {
        console.error('Error loading booked seats:', error);
    }
}

// ============================================================
// RENDER TICKETS (INDEX PAGE)
// ============================================================
async function renderTickets() {
    const stats     = await fetchTicketStats();
    const container = document.getElementById('singleTicketContainer');
    if (!container) return;

    container.innerHTML = '';

    let totalQuota = 0, totalSold = 0, totalAvailable = 0;

    window.ticketConfig.forEach(config => {
        const openQuota = window.seatStatus?.[config.type]?.open_quota ?? config.quota;
        const stat     = stats[config.type] || { sold: 0, available: openQuota, total: openQuota };
        totalQuota    += openQuota;
        totalSold     += stat.sold;
        totalAvailable += stat.available;
    });

    const percentSold  = ((totalSold / totalQuota) * 100).toFixed(0);
    const availability = totalAvailable / totalQuota;

    const card = document.createElement('div');
    card.className = 'premium-ticket-card';
    card.innerHTML = `
        <div class="premium-ticket-header">
            <div class="premium-badge">🎫 PILIHAN TIKET</div>
            <div class="premium-ticket-category">
                <h3>Semua Kategori Tersedia</h3>
            </div>
        </div>
        <div class="premium-ticket-body">
            <div class="premium-ticket-price">
                <div class="price-amount-range">
                    <div class="price-range-label">Harga Mulai Dari</div>
                    <div class="price-amount">${formatPrice(150000)}</div>
                </div>
                <div class="price-subtext">Per Tempat Duduk</div>
            </div>

            <div class="ticket-categories">
                ${window.ticketConfig.map(config => {
                    const openQuota = window.seatStatus?.[config.type]?.open_quota ?? config.quota;
                    const stat     = stats[config.type] || { sold: 0, available: openQuota, total: openQuota };
                    const price    = window.ticketPrices?.[config.type];
                    const isClosed = window.seatStatus?.[config.type]?.closed === true;
 return `
    <div class="category-badge" style="border-color:${config.color};${isClosed ? 'opacity:.7;filter:grayscale(.3);' : ''}">
        <div class="category-dot" style="background-color:${config.color};"></div>
        <div class="category-info">
            <span class="category-name">${config.name}</span>
            <span class="category-available">${stat.available}/${stat.total}</span>
            <span class="category-price">${price ? 'Rp ' + price.toLocaleString('id-ID') : '-'}</span>
        </div>
        ${isClosed ? `
        <div class="category-closed-bar">
            <span class="category-archived-badge">Ditutup Sementara</span>
        </div>` : ''}
    </div>`;
                }).join('')}
            </div>

            <div class="premium-stock-info">
                <div class="stock-label">Total Ketersediaan</div>
                <div class="stock-bar">
                    <div class="stock-progress" style="width:${percentSold}%;"></div>
                </div>
                <div class="stock-count">
                    <span class="stock-count-available">${totalAvailable} tersedia</span>
                    <span class="stock-count-total">dari ${totalQuota}</span>
                </div>
            </div>

            <button class="premium-btn-order" onclick="navigateToSeatSelection()" ${availability === 0 ? 'disabled' : ''}>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M6 9l6 6 12-12"></path>
                </svg>
                <span>${availability === 0 ? 'Semua Habis Terjual' : 'Pesan Sekarang'}</span>
            </button>
            ${(() => {
                const allClosed = window.ticketConfig &&
                    window.ticketConfig.every(c => window.seatStatus?.[c.type]?.closed === true);
                return allClosed
                    ? `<p style="margin-top:10px;font-size:.82rem;color:#c2410c;font-weight:600;text-align:center;">&#9940; Penjualan tiket sedang ditutup sementara.</p>`
                    : '';
            })()}
        </div>
    `;
    container.appendChild(card);
}

// ============================================================
// FETCH TICKET STATISTICS
// ============================================================
async function fetchTicketStats() {
    try {
        const tickets = await SB.getAllTickets();
        const stats   = {};

        window.ticketConfig.forEach(config => {
            // Gunakan open_quota (kuota yang dibuka ke publik) sebagai total yang terlihat peserta
            const openQuota = window.seatStatus?.[config.type]?.open_quota ?? config.quota;
            stats[config.type] = { sold: 0, available: openQuota, total: openQuota };
        });

        tickets.forEach(ticket => {
            if (ticket.ticket_type) {
                try {
                    let ticketTypes = Array.isArray(ticket.ticket_type)
                        ? ticket.ticket_type
                        : (typeof ticket.ticket_type === 'string'
                            ? (ticket.ticket_type.startsWith('[') ? JSON.parse(ticket.ticket_type) : [ticket.ticket_type])
                            : []);

                    ticketTypes.forEach(type => {
                        if (stats[type]) stats[type].sold += 1;
                    });
                } catch (e) { console.error('Error processing ticket types:', e, ticket); }
            }
        });

        window.ticketConfig.forEach(config => {
            const openQuota = window.seatStatus?.[config.type]?.open_quota ?? config.quota;
            stats[config.type].available = Math.max(0, openQuota - stats[config.type].sold);
        });

        const totalSold      = Object.values(stats).reduce((sum, s) => sum + s.sold, 0);
        const totalAvailable = TOTAL_QUOTA - totalSold;

        const elSold  = document.getElementById('totalSold');
        const elAvail = document.getElementById('totalAvailable');
        if (elSold)  elSold.textContent  = totalSold;
        if (elAvail) elAvail.textContent = Math.max(0, totalAvailable);

        return stats;
    } catch (error) {
        console.error('Error fetching stats:', error);
        return {};
    }
}

function navigateToSeatSelection() {
    window.location.href = '/e-ticket/pages/seat/';
}

// ============================================================
// BOOKING MODAL
// ============================================================
function openBookingModal() {
    const modal = document.getElementById('bookingModal');
    if (!modal) return;

    currentStep  = 1;
    selectedFile = null;
    window._hasClickedWA = false; // Reset flag WA setiap buka modal baru
    const form   = document.getElementById('bookingForm');
    if (form) form.reset();

    const imagePreview = document.getElementById('imagePreview');
    const uploadArea   = document.getElementById('uploadArea');
    const submitBtn    = document.getElementById('submitBtn');

    if (imagePreview) imagePreview.style.display = 'none';
    if (uploadArea)   uploadArea.style.display   = 'flex';
    if (submitBtn)    submitBtn.disabled          = true;

    updateModalSummary();
    goToStep1();
    modal.style.display = 'flex';
    setTimeout(() => modal.classList.add('active'), 10);
}

function closeModal() {
    const modal = document.getElementById('bookingModal');
    if (!modal) return;

    // Jika sudah di halaman sukses, wajib klik WA dulu sebelum bisa close
    const successDiv = document.getElementById('successMessage');
    const isOnSuccess = successDiv && successDiv.style.display !== 'none';
    if (isOnSuccess && !window._hasClickedWA) {
        // Tampilkan animasi shake pada card WA
        _showWAReminder();
        return;
    }

    modal.classList.remove('active');
    setTimeout(() => {
        modal.style.display = 'none';
        window._previewUniquePrice = null; // reset agar sesi berikutnya tidak terkontaminasi
        if (isOnSuccess) {
            window.location.href = '/e-ticket';
        }
    }, 300);
}

function _showWAReminder() {
    // Highlight card WA dengan animasi shake
    const waCard = document.getElementById('waGroupBtn');
    if (waCard) {
        waCard.classList.remove('wa-shake');
        void waCard.offsetWidth; // reflow untuk restart animation
        waCard.classList.add('wa-shake');
    }
}

function updateModalSummary() {
    const summaryCount       = document.getElementById('summaryCount');
    const summarySeatNumbers = document.getElementById('summarySeatNumbers');
    const modalPrice         = document.getElementById('modalPrice');
    const paymentAmount      = document.getElementById('paymentAmount');

    if (typeof selectedSeatsData !== 'undefined' && typeof currentTotalPrice !== 'undefined') {
        // Untuk alur VVIP/VIP dari panel: selectedSeats (denah) kosong,
        // tapi selectedSeatsData sudah diisi oleh vvpProceedToBooking
        const vvpCount = (window.selectedSeatsData || []).length;
        const mapCount = (typeof selectedSeats !== 'undefined') ? selectedSeats.size : 0;
        const displayCount = Math.max(mapCount, vvpCount);

        if (summaryCount) summaryCount.textContent = `${displayCount} kursi`;

        // Label kursi: untuk VVIP/VIP panel label adalah zona (belum ada nomor)
        const labels = (window.selectedSeatsData || []).map(s => s.label).filter(Boolean);
        if (summarySeatNumbers) {
            if (labels.length) {
                const zone  = window._pendingZone;
                const cfg   = zone ? (window.ticketConfig||[]).find(t=>t.type===zone) : null;
                const label = cfg?.name || (zone||'').toUpperCase();
                summarySeatNumbers.textContent = `${displayCount}× ${label} (nomor dipilih setelah bayar)`;
            } else {
                summarySeatNumbers.textContent = selectedSeatsData.map(s => s.label).join(', ') || '-';
            }
        }

        if (modalPrice) modalPrice.textContent = formatPrice(currentTotalPrice);
        // Tampilkan nominal + kode unik jika sudah dihitung
        const displayPrice = window._previewUniquePrice || currentTotalPrice;
        if (paymentAmount) paymentAmount.textContent = formatPrice(displayPrice);
    }
}

// ============================================================
// FORM STEPS
// ============================================================
function goToStep1() {
    currentStep = 1;
    document.getElementById('step1')?.classList.add('active');
    document.getElementById('step2')?.classList.remove('active');
    document.getElementById('step1Indicator')?.classList.add('active');
    document.getElementById('step2Indicator')?.classList.remove('active');
}

function goToStep2() {
    const name  = document.getElementById('customerName');
    const phone = document.getElementById('customerPhone');
    if (!name || !phone) return;

    const nameVal  = name.value.trim();
    const phoneVal = phone.value.trim();

    if (!nameVal || !phoneVal) {
        showWarning('Data Belum Lengkap', 'Mohon lengkapi nama dan nomor HP Anda terlebih dahulu.');
        return;
    }
    if (!/^(\+?62|0)[0-9]{9,12}$/.test(phoneVal)) {
        showWarning('Nomor HP Tidak Valid', 'Format nomor HP: 08xxxxxxxxxx atau +628xxxxxxxxxx');
        return;
    }

    currentStep = 2;
    document.getElementById('step1')?.classList.remove('active');
    document.getElementById('step2')?.classList.add('active');
    document.getElementById('step1Indicator')?.classList.remove('active');
    document.getElementById('step2Indicator')?.classList.add('active');
}

// ============================================================
// FILE UPLOAD (preview + kompresi otomatis sebelum submit)
// ============================================================

// Kompres gambar ke JPEG dengan kualitas & dimensi yang dikontrol
// Mengembalikan File hasil kompresi (< ~500KB target)
async function compressImage(file, maxWidth = 1200, quality = 0.75) {
    return new Promise((resolve) => {
        const reader = new FileReader();
        reader.onload = function (e) {
            const img = new Image();
            img.onload = function () {
                const canvas = document.createElement('canvas');

                // Hitung ukuran proporsional
                let { width, height } = img;
                if (width > maxWidth) {
                    height = Math.round((height * maxWidth) / width);
                    width  = maxWidth;
                }

                canvas.width  = width;
                canvas.height = height;

                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, width, height);

                canvas.toBlob(
                    (blob) => {
                        // Jika hasil kompresi masih > 500KB, kompres lagi dengan kualitas lebih rendah
                        if (blob.size > 500 * 1024 && quality > 0.3) {
                            compressImage(file, maxWidth, quality - 0.15).then(resolve);
                        } else {
                            const compressedFile = new File(
                                [blob],
                                file.name.replace(/\.[^.]+$/, '.jpg'),
                                { type: 'image/jpeg', lastModified: Date.now() }
                            );
                            resolve(compressedFile);
                        }
                    },
                    'image/jpeg',
                    quality
                );
            };
            img.src = e.target.result;
        };
        reader.readAsDataURL(file);
    });
}

function handleFileSelect(event) {
    const file = event.target.files[0];
    if (!file) return;

    if (!file.type.startsWith('image/')) {
        showWarning('Format File Salah', 'Harap upload file gambar (JPG, PNG, WEBP)');
        return;
    }

    // Kompresi otomatis berapapun ukurannya
    const toastEl = document.getElementById('compressToast');
    if (toastEl) { toastEl.style.display = 'flex'; }

    compressImage(file).then((compressed) => {
        if (toastEl) { toastEl.style.display = 'none'; }
        selectedFile = compressed;

        const reader = new FileReader();
        reader.onload = function (e) {
            const preview    = document.getElementById('imagePreview');
            const uploadArea = document.getElementById('uploadArea');
            const submitBtn  = document.getElementById('submitBtn');
            if (preview && uploadArea) {
                preview.src              = e.target.result;
                preview.style.display    = 'block';
                uploadArea.style.display = 'none';
                if (submitBtn) submitBtn.disabled = false;
            }
        };
        reader.readAsDataURL(compressed);
    });
}

// ============================================================
// UPLOAD GAMBAR KE SERVER LOKAL via upload.php
// ============================================================
async function uploadImageToServer(ticketNumber, file) {
    const formData = new FormData();
    formData.append('image', file);
    formData.append('ticket_number', ticketNumber);

    const res = await fetch('/e-ticket/upload.php', {
        method: 'POST',
        body:   formData,
        // Jangan set Content-Type — biarkan browser isi boundary multipart
    });

    if (!res.ok) {
        let errMsg = `Upload gagal (${res.status})`;
        try {
            const err = await res.json();
            errMsg = err.error || errMsg;
        } catch (_) {}
        throw new Error(errMsg);
    }

    const result = await res.json();
    if (!result.success) throw new Error(result.error || 'Upload gagal.');
    return result.url; // path lokal, e.g. /uploads/payment-proofs/TKT-xxx.jpg
}

// ============================================================
// FORM SUBMISSION
// Alur VVIP/VIP (baru):
//   1. Upload gambar ke server → dapat URL lokal
//   2. Insert ke Supabase dengan status pending + seat_selection_status = 'pending_seat'
//   3. Tampilkan modal pemilihan kursi (seatPickerModal) inline
//   4. Setelah kursi dipilih & dikonfirmasi → update seat_numbers di DB
//   5. Tampilkan successMessage dengan link WA
//
// Alur Kelas 1 / Reguler (sama seperti sebelumnya):
//   1. Upload gambar → URL lokal
//   2. Insert ke Supabase → langsung tampilkan successMessage
// ============================================================

// State untuk seat picker
window._pendingTicketId     = null;
window._pendingTicketNumber = null;
window._pendingZone         = null;   // 'vvip' | 'vip'
window._pendingTotalSeats   = 0;      // jumlah kursi yang harus dipilih
window._pendingCustomerName = null;
window._pendingUniquePrice  = 0;

(function () {
    const form = document.getElementById('bookingForm');
    if (!form) return;

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        if (currentStep !== 2) return;

        const name  = document.getElementById('customerName');
        const phone = document.getElementById('customerPhone');
        if (!name || !phone) return;

        const nameVal  = name.value.trim();
        const phoneVal = phone.value.trim();

        if (!nameVal || !phoneVal) {
            showWarning('Data Belum Lengkap', 'Mohon lengkapi semua field yang diperlukan.');
            return;
        }
        if (!selectedFile) {
            showWarning('Bukti Transfer Diperlukan', 'Mohon upload bukti transfer pembayaran Anda.');
            return;
        }

        const submitBtn  = document.getElementById('submitBtn');
        const submitText = document.getElementById('submitText');

        try {
            if (submitBtn)  submitBtn.disabled    = true;
            if (submitText) submitText.textContent = 'Memproses...';

            const ticketNumber = `TKT-${Date.now()}-${Math.random().toString(36).substr(2, 6).toUpperCase()}`;

            // Kursi individual (VVIP / VIP)
            const individualSeatsData = window.selectedSeatsData || [];
            // Zona (Kelas 1 / Reguler)
            const zoneTickets = [];
            const cfg = window.ticketConfig || [];
            cfg.slice(2).forEach(t => {
                ['L', 'R'].forEach(section => {
                    const cnt = (window.classSectionTickets || {})[`${t.type}-${section}`] || 0;
                    for (let i = 0; i < cnt; i++) zoneTickets.push({ zone: t.type, section });
                });
            });

            const individualSeatIds = individualSeatsData.map(s => s.seatId).filter(Boolean);

            const ticketTypes = [
                ...individualSeatsData.map(s => s.zone || 'vvip'),
                ...zoneTickets.map(z => z.zone),
            ];

            const primaryTicketType = ticketTypes.length > 0
                ? ticketTypes.reduce((best, type) =>
                    (window.ticketPrices[type] || 0) > (window.ticketPrices[best] || 0) ? type : best
                  , ticketTypes[0])
                : 'reguler';

            const isVvipVip = (primaryTicketType === 'vvip' || primaryTicketType === 'vip');

            const totalSeats = individualSeatsData.length + zoneTickets.length;

            const zoneDigitMap = { vvip: 1, vip: 2, kelas1: 3, reguler: 4, kelas2: 4 };
            const zoneDigit    = zoneDigitMap[primaryTicketType] ?? 4;

            let uniquePrice = window._previewUniquePrice;
            if (!uniquePrice || typeof uniquePrice !== 'number') {
                const qrData = ticketNumber;
                if (submitText) submitText.textContent = 'Menghitung harga...';
                const zoneCounts  = await SB.getTransactionCountPerZone();
                const zoneOrder   = (zoneCounts[primaryTicketType] || 0) + 1;
                const orderPadded = String(zoneOrder).padStart(3, '0');
                uniquePrice = parseInt(currentTotalPrice)
                            + parseInt(orderPadded) * 10
                            + zoneDigit;
            }
            const qrData = ticketNumber;

            if (submitText) submitText.textContent = 'Mengupload bukti...';
            const imageUrl = await uploadImageToServer(ticketNumber, selectedFile);

            if (submitText) submitText.textContent = 'Menyimpan data...';

            const payload = {
                ticket_number:         ticketNumber,
                name:                  nameVal,
                phone:                 (phoneVal.startsWith('0') ? '62' + phoneVal.slice(1) : phoneVal).replace(/[^0-9]/g, ''),
                ticket_type:           ticketTypes,
                primary_ticket_type:   primaryTicketType,
                status:                'pending',
                price:                 uniquePrice,
                order_date:            new Date().toISOString(),
                qr_data:               qrData,
                // Untuk VVIP/VIP: seat_numbers kosong dulu, diisi setelah pilih kursi
                seat_numbers:          isVvipVip ? null : (individualSeatIds.length > 0 ? individualSeatIds : null),
                total_seats:           totalSeats,
                image_url:             imageUrl,
                selected_zone:         isVvipVip ? primaryTicketType : null,
                seat_selection_status: isVvipVip ? 'pending_seat' : 'completed',
            };

            const created = await SB.createTicket(payload);
            const createdId = Array.isArray(created) ? created[0]?.id : created?.id;

            if (isVvipVip) {
                // ── ALUR VVIP/VIP: simpan state & aktifkan seat-picker di denah utama ──
                window._pendingTicketId     = createdId;
                window._pendingTicketNumber = ticketNumber;
                window._pendingZone         = primaryTicketType;
                window._pendingTotalSeats   = totalSeats;
                window._pendingCustomerName = nameVal;
                window._pendingUniquePrice  = uniquePrice;

                // Simpan ke localStorage untuk recovery
                if (typeof BVR !== 'undefined') {
                    BVR.savePendingSeat(
                        createdId, ticketNumber, nameVal, phoneVal,
                        primaryTicketType, totalSeats, uniquePrice, currentTotalPrice
                    );
                }

                // Tutup booking modal, lalu aktifkan seat-picker mode di denah
                const bookingModal = document.getElementById('bookingModal');
                if (bookingModal) {
                    bookingModal.classList.remove('active');
                    setTimeout(() => {
                        bookingModal.style.display = 'none';
                        if (typeof activateSeatPickerMode === 'function') activateSeatPickerMode();
                    }, 300);
                } else {
                    if (typeof activateSeatPickerMode === 'function') activateSeatPickerMode();
                }
            } else {
                // ── ALUR BIASA (Kelas 1 / Reguler) ──
                // Simpan ke localStorage untuk recovery (menunggu WA)
                if (typeof BVR !== 'undefined') {
                    BVR.savePendingWA(ticketNumber, nameVal, phoneVal, uniquePrice, currentTotalPrice, totalSeats);
                }
                await loadBookedSeats();
                showSuccessMessage(ticketNumber, nameVal, uniquePrice);
            }

        } catch (err) {
            showWarning('Pemesanan Gagal', err.message || 'Terjadi kesalahan saat memproses pemesanan.');
        } finally {
            if (submitBtn)  submitBtn.disabled    = false;
            if (submitText) submitText.textContent = 'Selesaikan Pemesanan';
        }
    });
})();



// ============================================================
// SUCCESS MESSAGE
// ============================================================
function showSuccessMessage(ticketNumber, customerName, uniquePrice) {
    const form       = document.getElementById('bookingForm');
    const successDiv = document.getElementById('successMessage');
    if (form) form.style.display = 'none';

    // Hitung total tiket: individual (VVIP/VIP) + zona (Kelas 1/Reguler)
    const individualCount = (window.selectedSeatsData || []).length;
    const zoneCount = Object.values(window.classSectionTickets || {}).reduce((s, c) => s + c, 0);
    const totalCount = individualCount + zoneCount;

    if (successDiv && typeof currentTotalPrice !== 'undefined') {
        successDiv.style.display = 'block';
        successDiv.innerHTML = `
            <div style="text-align:center;padding:2rem;">
                <div style="font-size:4rem;margin-bottom:1rem;">✅</div>
                <h3 style="font-family:var(--font-display);font-size:2rem;color:var(--primary);margin-bottom:1rem;">Pemesanan Berhasil!</h3>
                <p style="color:var(--gray-600);margin-bottom:1rem;">Terima kasih, <strong>${customerName}</strong>!</p>

                <div style="background:var(--cream);padding:1.5rem;border-radius:12px;margin-bottom:1.5rem;">
                    <div style="font-size:.875rem;color:var(--gray-600);margin-bottom:.5rem;">Nomor Tiket</div>
                    <div style="font-family:var(--font-display);font-size:2rem;font-weight:700;color:var(--primary);letter-spacing:2px;">${ticketNumber}</div>
                </div>

                <div style="background:var(--cream);padding:1.5rem;border-radius:12px;margin-bottom:1.5rem;">
                    <p style="font-weight:600;color:var(--gray-900);margin-bottom:.5rem;">Jumlah Tiket</p>
                    <p style="color:var(--primary);font-size:1.25rem;font-weight:700;">${totalCount} Tiket</p>
                    <p style="font-size:.875rem;color:var(--gray-600);margin-top:.5rem;">Harga Dasar: ${formatPrice(currentTotalPrice)}</p>
                </div>

                <div style="background:linear-gradient(135deg,#EEF2FF,#E0E7FF);border:2px solid #6366F1;padding:1.5rem;border-radius:12px;margin-bottom:1.5rem;">
                    <p style="font-weight:700;color:#4338CA;margin-bottom:.5rem;font-size:.9rem;text-transform:uppercase;letter-spacing:.5px;">💳 Nominal Transfer Unik</p>
                    <p style="font-family:monospace;font-size:2rem;font-weight:800;color:#4338CA;letter-spacing:2px;margin:.5rem 0;">${formatPrice(uniquePrice)}</p>
                </div>

                <div style="background:#FFF3CD;padding:1rem;border-radius:8px;border-left:4px solid #FFC107;margin-bottom:1.5rem;">
                    <p style="font-size:.875rem;color:var(--gray-800);margin:0;">
                        <strong>Status:</strong> Menunggu Verifikasi Panitia<br>
                        Panitia akan menghubungi Anda setelah pembayaran diverifikasi.
                    </p>
                </div>

                <!-- ── KOMUNITAS SECTION (redesign) ── -->
                <div style="margin-bottom:1.5rem;">

                    <div style="text-align:center;margin-bottom:14px;">
                        <div style="font-size:10px;font-weight:700;letter-spacing:3px;text-transform:uppercase;color:#C084FC;margin-bottom:5px;">Langkah Selanjutnya</div>
                        <div style="font-size:1.1rem;font-weight:700;color:#F8FAFC;line-height:1.35;">Bergabung dengan Komunitas Peserta</div>
                        <div style="font-size:11px;color:#64748B;margin-top:4px;line-height:1.5;">Dapatkan informasi terbaru &amp; terhubung bersama sesama peserta</div>
                    </div>

                    <!-- Card WhatsApp -->
                    <a id="waGroupBtn"
                       onclick="handleWAClick('${WA_GROUP_LINK}'); return false;"
                       href="${WA_GROUP_LINK}"
                       target="_blank"
                       style="display:block;text-decoration:none;position:relative;border-radius:16px;padding:16px 18px;background:linear-gradient(135deg,#0D2B1E 0%,#051810 100%);border:1px solid rgba(37,211,102,0.25);box-shadow:0 4px 20px rgba(0,0,0,0.4);transition:transform .2s cubic-bezier(.34,1.56,.64,1),box-shadow .2s ease,border-color .2s ease;margin-bottom:10px;overflow:hidden;cursor:pointer;"
                       onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 12px 36px rgba(0,0,0,0.5),0 0 28px rgba(37,211,102,0.08)';this.style.borderColor='rgba(37,211,102,0.4)';"
                       onmouseout="this.style.transform='translateY(0)';this.style.boxShadow='0 4px 20px rgba(0,0,0,0.4)';this.style.borderColor='rgba(37,211,102,0.25)';"
                    >
                        <div style="position:absolute;inset:0;background:radial-gradient(ellipse at 85% 20%,rgba(37,211,102,0.07) 0%,transparent 60%),radial-gradient(ellipse at 15% 80%,rgba(18,140,126,0.05) 0%,transparent 50%);pointer-events:none;"></div>
                        <div style="position:relative;z-index:1;display:flex;align-items:center;gap:14px;">
                            <div style="flex-shrink:0;width:46px;height:46px;border-radius:13px;background:rgba(37,211,102,0.12);border:1px solid rgba(37,211,102,0.2);display:flex;align-items:center;justify-content:center;">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z" fill="#25D366"/>
                                    <path d="M11.9 0C5.327 0 0 5.327 0 11.9c0 2.088.546 4.042 1.499 5.741L.057 23.9l6.386-1.674A11.855 11.855 0 0011.9 23.8c6.573 0 11.9-5.327 11.9-11.9C23.8 5.327 18.473 0 11.9 0zm0 21.713a9.776 9.776 0 01-4.989-1.365l-.357-.212-3.698.969.985-3.605-.232-.37A9.762 9.762 0 012.088 11.9C2.088 6.52 6.52 2.088 11.9 2.088S21.712 6.52 21.712 11.9 17.28 21.713 11.9 21.713z" fill="#25D366"/>
                                </svg>
                            </div>
                            <div style="flex:1;min-width:0;text-align:left;">
                                <div style="font-size:10px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:#4ADE80;margin-bottom:2px;">WhatsApp Group</div>
                                <div style="font-size:14px;font-weight:700;color:#F8FAFC;margin-bottom:3px;">Grup Peserta Resmi</div>
                                <div style="font-size:11px;color:#64748B;line-height:1.45;">Info terbaru, pengumuman &amp; koordinasi acara</div>
                            </div>
                            <div style="flex-shrink:0;width:30px;height:30px;border-radius:50%;background:rgba(37,211,102,0.1);border:1px solid rgba(37,211,102,0.2);display:flex;align-items:center;justify-content:center;">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#25D366" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                            </div>
                        </div>
                    </a>

                    <!-- Divider -->
                    <div style="display:flex;align-items:center;gap:10px;margin:8px 0;">
                        <div style="flex:1;height:1px;background:rgba(255,255,255,0.06);"></div>
                        <div style="font-size:10px;color:#334155;letter-spacing:1px;text-transform:uppercase;font-weight:600;">dan</div>
                        <div style="flex:1;height:1px;background:rgba(255,255,255,0.06);"></div>
                    </div>

                    <!-- Card Instagram -->
                    <a href="https://www.instagram.com/komsosparokitulungagung/"
                       target="_blank"
                       style="display:block;text-decoration:none;position:relative;border-radius:16px;padding:16px 18px;background:linear-gradient(135deg,#1A0D26 0%,#0F0618 100%);border:1px solid rgba(228,64,95,0.2);box-shadow:0 4px 20px rgba(0,0,0,0.4);transition:transform .2s cubic-bezier(.34,1.56,.64,1),box-shadow .2s ease,border-color .2s ease;overflow:hidden;"
                       onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 12px 36px rgba(0,0,0,0.5),0 0 28px rgba(228,64,95,0.07)';this.style.borderColor='rgba(228,64,95,0.35)';"
                       onmouseout="this.style.transform='translateY(0)';this.style.boxShadow='0 4px 20px rgba(0,0,0,0.4)';this.style.borderColor='rgba(228,64,95,0.2)';"
                    >
                        <div style="position:absolute;inset:0;background:radial-gradient(ellipse at 85% 20%,rgba(228,64,95,0.06) 0%,transparent 55%),radial-gradient(ellipse at 20% 85%,rgba(131,58,180,0.05) 0%,transparent 50%);pointer-events:none;"></div>
                        <div style="position:relative;z-index:1;display:flex;align-items:center;gap:14px;">
                            <div style="flex-shrink:0;width:46px;height:46px;border-radius:13px;background:rgba(228,64,95,0.1);border:1px solid rgba(228,64,95,0.18);display:flex;align-items:center;justify-content:center;">
                                <svg width="22" height="22" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <defs>
                                        <linearGradient id="igGradMain" x1="0%" y1="100%" x2="100%" y2="0%">
                                            <stop offset="0%" stop-color="#f09433"/>
                                            <stop offset="25%" stop-color="#e6683c"/>
                                            <stop offset="50%" stop-color="#dc2743"/>
                                            <stop offset="75%" stop-color="#cc2366"/>
                                            <stop offset="100%" stop-color="#bc1888"/>
                                        </linearGradient>
                                    </defs>
                                    <rect width="20" height="20" x="2" y="2" rx="5" ry="5" fill="none" stroke="url(#igGradMain)" stroke-width="1.8"/>
                                    <circle cx="12" cy="12" r="3.5" fill="none" stroke="url(#igGradMain)" stroke-width="1.8"/>
                                    <circle cx="17.5" cy="6.5" r="1.1" fill="#cc2366"/>
                                </svg>
                            </div>
                            <div style="flex:1;min-width:0;text-align:left;">
                                <div style="font-size:10px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:#F472B6;margin-bottom:2px;">Instagram</div>
                                <div style="font-size:14px;font-weight:700;color:#F8FAFC;margin-bottom:3px;">@komsosparokitulungagung</div>
                                <div style="font-size:11px;color:#64748B;line-height:1.45;">Ikuti kami untuk konten inspiratif Paroki Tulungagung</div>
                            </div>
                            <div style="flex-shrink:0;width:30px;height:30px;border-radius:50%;background:rgba(228,64,95,0.1);border:1px solid rgba(228,64,95,0.2);display:flex;align-items:center;justify-content:center;">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#F472B6" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                            </div>
                        </div>
                    </a>

                </div>
                <!-- ── /KOMUNITAS SECTION ── -->

                <p style="font-size:.875rem;color:var(--gray-600);">Simpan nomor tiket Anda untuk keperluan konfirmasi ke Panitia.</p>
            </div>
        `;
    }
}

// ============================================================
// WA CLICK HANDLER
// ============================================================
async function handleWAClick(waLink) {
    window._hasClickedWA = true;
    window.open(waLink, '_blank');

    // Sesi selesai — hapus cache recovery
    if (typeof BVR !== 'undefined') BVR.clear();

    // Update kolom wa_clicked di Supabase
    try {
        const ticketNumberEl = document.querySelector('#successMessage [style*="letter-spacing:2px"]');
        if (ticketNumberEl) {
            const tktNum = ticketNumberEl.textContent.trim();
            if (tktNum && tktNum.startsWith('TKT-')) {
                await SB.markWAClicked(tktNum);
            }
        }
    } catch(e) {
        console.warn('Gagal update wa_clicked:', e);
    }

    // Update tampilan card WA menjadi "sudah bergabung"
    const waCard = document.getElementById('waGroupBtn');
    if (waCard) {
        waCard.style.background    = 'linear-gradient(135deg,#052E10 0%,#021A09 100%)';
        waCard.style.borderColor   = 'rgba(74,222,128,0.4)';
        waCard.style.pointerEvents = 'none';
        const textDiv = waCard.querySelector('[style*="font-size:14px"]');
        if (textDiv) textDiv.textContent = 'Sudah Bergabung ✓';
        const descDiv = waCard.querySelector('[style*="font-size:11px"]');
        if (descDiv) {
            descDiv.textContent = 'Kini Anda dapat menutup jendela ini dengan tombol ✕';
            descDiv.style.color = '#4ADE80';
        }
        const arrowDiv = waCard.querySelector('[style*="border-radius:50%"]');
        if (arrowDiv) {
            arrowDiv.innerHTML = '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#4ADE80" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>';
        }
    }
}

// ============================================================
// HELPER FUNCTIONS
// ============================================================
function formatPrice(price) {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(price);
}

function showWarning(title = 'Peringatan', message = 'Terjadi kesalahan.') {
    const popup   = document.getElementById('warningPopup');
    const titleEl = document.getElementById('warningTitle');
    const msgEl   = document.getElementById('warningMessage');
    if (popup) {
        if (titleEl) titleEl.textContent = title;
        if (msgEl)   msgEl.textContent   = message;
        popup.style.display = 'flex';
        setTimeout(() => popup.classList.add('active'), 10);
    }
}

function closeWarning() {
    const popup = document.getElementById('warningPopup');
    if (popup) {
        popup.classList.remove('active');
        setTimeout(() => { popup.style.display = 'none'; }, 300);
    }
}

window.onclick = function (event) {
    // bookingModal: TIDAK bisa ditutup dengan klik di luar (harus pakai tombol X)
    const warningPopup = document.getElementById('warningPopup');
    if (event.target === warningPopup) closeWarning();
};
document.addEventListener('keydown', function (e) {
    // Escape hanya menutup warning, BUKAN booking modal
    if (e.key === 'Escape') { closeWarning(); }
});