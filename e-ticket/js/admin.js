// ============================================================
// ADMIN.JS — Supabase Edition
// Migrasi dari PocketBase ke Supabase
// SUPABASE_URL, SUPABASE_ANON, TICKET_PAGE_BASE dari PHP
// ============================================================

let allTickets    = [];
let currentFilter = 'pending';
let currentTicket = null;


function getImageUrl(ticket) {
    if (!ticket.image_url) return null;
    return ticket.image_url || null;
}

// ── Load semua tiket ──────────────────────────────────────
async function loadTickets() {
    try {
        document.getElementById('loadingState').style.display = 'block';
        document.getElementById('ticketsTable').style.display = 'none';
        document.getElementById('emptyState').style.display   = 'none';

        allTickets = await SB.getAllTickets();
        updateStats();
        renderTickets();
    } catch (error) {
        console.error('Error loading tickets:', error);
        alert('Gagal memuat data tiket: ' + error.message);
    } finally {
        document.getElementById('loadingState').style.display = 'none';
    }
}

function updateStats() {
    document.getElementById('totalTickets').textContent   = allTickets.length;
    document.getElementById('pendingTickets').textContent = allTickets.filter(t => t.status === 'pending').length;
    document.getElementById('paidTickets').textContent    = allTickets.filter(t => t.status === 'paid').length;
}

function renderTickets() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    let filtered     = allTickets;

    if (currentFilter !== 'all') {
        filtered = filtered.filter(t => t.status === currentFilter);
    }

    if (searchTerm) {
        const terms = searchTerm.split(/\s+/).filter(Boolean);
        filtered = filtered.filter(t => {
            const ticketNum        = t.ticket_number.toLowerCase();
            const ticketNormalized = ticketNum.replace(/[-\s]/g, '');
            const name             = t.name.toLowerCase();
            const phone            = t.phone.replace(/\D/g, '');

            return terms.every(term => {
                const termNormalized = term.replace(/[-\s]/g, '');
                const cleanTerm      = term.replace(/\D/g, '');
                return (
                    ticketNum.includes(term) ||
                    ticketNormalized.includes(termNormalized) ||
                    name.includes(term) ||
                    (cleanTerm && phone.includes(cleanTerm))
                );
            });
        });
    }

    const tbody = document.getElementById('ticketsBody');
    tbody.innerHTML = '';

    if (filtered.length === 0) {
        document.getElementById('ticketsTable').style.display = 'none';
        document.getElementById('emptyState').style.display   = 'block';
        return;
    }

    document.getElementById('ticketsTable').style.display = 'table';
    document.getElementById('emptyState').style.display   = 'none';

    const ZONE_ORDER = ['vvip', 'vip', 'kelas1', 'reguler'];
    const ZONE_META  = {
        vvip:    { label: 'Zona VVIP',    icon: '⭐', headerBg: '#fffbeb', headerColor: '#92400e', borderColor: '#fcd34d' },
        vip:     { label: 'Zona VIP',     icon: '💎', headerBg: '#fff1f2', headerColor: '#881337', borderColor: '#fda4af' },
        kelas1:  { label: 'Zona Kelas 1', icon: '🎫', headerBg: '#f0fdf4', headerColor: '#14532d', borderColor: '#86efac' },
        reguler: { label: 'Zona Reguler', icon: '🎟️', headerBg: '#eff6ff', headerColor: '#1e3a8a', borderColor: '#93c5fd' },
    };

    const grouped = {};
    ZONE_ORDER.forEach(z => grouped[z] = []);
    filtered.forEach(ticket => {
        const z = (ticket.primary_ticket_type || 'reguler').toLowerCase();
        if (grouped[z]) grouped[z].push(ticket);
        else grouped['reguler'].push(ticket);
    });

    const TOTAL_COLS = 11;

    ZONE_ORDER.forEach(zone => {
        const tickets = grouped[zone];
        if (!tickets.length) return;

        const meta = ZONE_META[zone];

        const headerRow = document.createElement('tr');
        headerRow.innerHTML = `
            <td colspan="${TOTAL_COLS}" style="
                padding: 10px 18px;
                background: ${meta.headerBg};
                border-top: 2px solid ${meta.borderColor};
                border-bottom: 1px solid ${meta.borderColor};
                font-size: .8rem;
                font-weight: 700;
                color: ${meta.headerColor};
                letter-spacing: .4px;
                text-transform: uppercase;
                user-select: none;
            ">
                ${meta.icon}&nbsp;&nbsp;${meta.label}
                <span style="
                    margin-left: 10px;
                    font-size: .72rem;
                    font-weight: 600;
                    background: ${meta.borderColor};
                    color: ${meta.headerColor};
                    padding: 2px 10px;
                    border-radius: 99px;
                    opacity: .85;
                ">${tickets.length} nota</span>
            </td>
        `;
        tbody.appendChild(headerRow);

        tickets.forEach(ticket => {
            const row      = document.createElement('tr');
            const imageUrl = getImageUrl(ticket);
            row.innerHTML = `
                <td><strong>${ticket.ticket_number}</strong></td>
                <td>${ticket.name}</td>
                <td>${ticket.phone}</td>
                <td><span class="type-badge type-${ticket.primary_ticket_type}">${String(ticket.primary_ticket_type).toUpperCase()}</span></td>
                <td>${renderSeatTags(ticket)}</td>
                <td>${formatPrice(ticket.price)}</td>
                <td><span class="status-badge status-${ticket.status}">${ticket.status === 'pending' ? 'Pending' : 'Lunas'}</span></td>
                <td>${renderWABadge(ticket)}</td>
                <td>${imageUrl
                    ? `<img src="https://www.parokitulungagung.org/e-ticket${imageUrl}" alt="Bukti Transfer" class="thumbnail-img" onclick="previewImage('https://www.parokitulungagung.org/e-ticket${imageUrl}')" title="Klik untuk memperbesar">`
                    : '<span class="no-image">-</span>'}</td>
                <td>${formatDate(ticket.order_date)}</td>
                <td>
                    ${ticket.status === 'pending'
                        ? `<button class="action-btn btn-verify" onclick="verifyPayment('${ticket.id}')">✓ Verifikasi</button>`
                        : `<button class="action-btn btn-view" onclick="viewQR('${ticket.id}')">📱E-Ticket</button>`}
                    <button class="action-btn btn-detail" onclick="viewDetail('${ticket.id}')">Detail</button>
                </td>
            `;
            tbody.appendChild(row);
        });
    });
}

// ── Seat Display Helpers ──────────────────────────────────
const ZONE_STYLE = {
    vvip:    { bg: '#fff8e1', color: '#b45309', border: '#fde68a' },
    vip:     { bg: '#fef2f2', color: '#991b1b', border: '#fecaca' },
    kelas1:  { bg: '#f0fdf4', color: '#166534', border: '#bbf7d0' },
    reguler: { bg: '#eff6ff', color: '#1e40af', border: '#bfdbfe' },
};

function parseSeats(ticket) {
    const raw = ticket.seat_numbers;
    if (!raw) return [];
    if (Array.isArray(raw)) return raw.map(s => String(s).trim()).filter(Boolean);
    if (typeof raw === 'string') {
        try { const p = JSON.parse(raw); if (Array.isArray(p)) return p.map(s => String(s).trim()).filter(Boolean); } catch(e) {}
        return raw.split(/[,;\s]+/).map(s => s.trim()).filter(Boolean);
    }
    return [];
}

function renderSeatTags(ticket) {
    const seats = parseSeats(ticket);
    if (!seats.length) return '<span style="color:#94a3b8;font-size:.78rem;font-style:italic;">—</span>';
    const zone = (ticket.primary_ticket_type || 'reguler').toLowerCase();
    const s = ZONE_STYLE[zone] || ZONE_STYLE.reguler;
    return '<div style="display:flex;flex-wrap:wrap;gap:4px;max-width:180px;">' +
        seats.map(seat =>
            `<span style="display:inline-block;padding:2px 7px;border-radius:5px;font-size:.72rem;font-weight:700;background:${s.bg};color:${s.color};border:1px solid ${s.border};white-space:nowrap;">${seat}</span>`
        ).join('') +
    '</div>';
}

function renderDetailSeats(ticket) {
    const seats = parseSeats(ticket);
    const zone  = (ticket.primary_ticket_type || 'reguler').toLowerCase();
    const s     = ZONE_STYLE[zone] || ZONE_STYLE.reguler;

    const zoneLabels = { vvip: 'VVIP', vip: 'VIP', kelas1: 'Kelas 1', reguler: 'Reguler' };
    const zoneLabel  = zoneLabels[zone] || zone.toUpperCase();

    if (!seats.length) {
        return `
        <div style="margin-top:16px;padding:14px 16px;border-radius:12px;background:#f8fafc;border:1px dashed #e2e8f0;text-align:center;">
            <div style="font-size:.8rem;color:#94a3b8;font-style:italic;">Belum ada kursi yang dipilih</div>
        </div>`;
    }

    const pills = seats.map(seat =>
        `<span style="display:inline-flex;align-items:center;padding:6px 14px;border-radius:8px;font-size:.88rem;font-weight:700;background:${s.bg};color:${s.color};border:1.5px solid ${s.border};">${seat}</span>`
    ).join('');

    return `
    <div style="margin-top:16px;border-top:1px solid #f1f5f9;padding-top:16px;">
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;">
            <span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:${s.color};"></span>
            <span style="font-size:.75rem;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:${s.color};">Kursi Dipesan — Zona ${zoneLabel}</span>
            <span style="margin-left:auto;font-size:.75rem;color:#64748b;background:#f1f5f9;padding:2px 10px;border-radius:99px;">${seats.length} kursi</span>
        </div>
        <div style="display:flex;flex-wrap:wrap;gap:6px;">${pills}</div>
    </div>`;
}

function formatPrice(price) {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(price);
}

function formatDate(dateString) {
    return new Date(dateString).toLocaleDateString('id-ID', {
        year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit'
    });
}

// ── WA Sent Badge ─────────────────────────────────────────
function renderWABadge(ticket) {
    if (ticket.wa_sent) {
        const sentAt = ticket.wa_sent_at
            ? new Date(ticket.wa_sent_at).toLocaleDateString('id-ID', { day:'2-digit', month:'short', hour:'2-digit', minute:'2-digit' })
            : '';
        return `<span class="wa-badge sent" title="Dikirim: ${sentAt}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="20 6 9 17 4 12"/>
            </svg>
            Terkirim
        </span>`;
    }
    return `<span class="wa-badge not-sent" title="Belum dikirim ke WhatsApp">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
        Belum
    </span>`;
}

function filterStatus(status) {
    currentFilter = status;
    document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');
    renderTickets();
}

document.getElementById('searchInput').addEventListener('input', renderTickets);

// ── Confirm Modal helper ──────────────────────────────────
function showConfirmModal({ type = 'warning', title, desc, okLabel = 'Ya, Lanjutkan', onOk }) {
    const iconMap = {
        verify:  '<polyline points="20 6 9 17 4 12"/>',
        delete:  '<polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/>',
        warning: '<path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
    };
    document.getElementById('confirmIconWrap').className = `confirm-icon-wrap type-${type}`;
    document.getElementById('confirmIcon').innerHTML = iconMap[type] || iconMap.warning;
    document.getElementById('confirmTitle').textContent = title;
    document.getElementById('confirmDesc').textContent  = desc;
    const okBtn = document.getElementById('confirmOkBtn');
    okBtn.textContent  = okLabel;
    okBtn.className    = `confirm-btn-ok type-${type}`;
    okBtn.onclick      = () => { closeConfirmModal(); onOk(); };
    document.getElementById('confirmModal').style.display = 'flex';
}

function closeConfirmModal() {
    document.getElementById('confirmModal').style.display = 'none';
}

// ── Verifikasi pembayaran ─────────────────────────────────
async function verifyPayment(ticketId) {
    showConfirmModal({
        type: 'verify',
        title: 'Verifikasi Pembayaran',
        desc: 'Apakah pembayaran tiket ini sudah dikonfirmasi? Status akan berubah menjadi Lunas.',
        okLabel: 'Ya, Verifikasi',
        onOk: async () => {
            try {
                const qrLink = `${TICKET_PAGE_BASE}?id=${ticketId}`;
                await SB.updateTicket(ticketId, { status: 'paid', qr_data: qrLink });
                showToast('Pembayaran berhasil diverifikasi', 'success');
                loadTickets();
            } catch (err) {
                showToast('Terjadi kesalahan: ' + err.message, 'error');
            }
        }
    });
}

// ── Detail tiket ─────────────────────────────────────────
function viewDetail(ticketId) {
    const ticket   = allTickets.find(t => t.id === ticketId);
    currentTicket  = ticket;
    const imageUrl = getImageUrl(ticket);

    document.getElementById('ticketDetails').innerHTML = `
        <div class="detail-row"><div class="detail-label">Nomor Tiket:</div><div class="detail-value"><strong>${ticket.ticket_number}</strong></div></div>
        <div class="detail-row"><div class="detail-label">Nama:</div><div class="detail-value">${ticket.name}</div></div>
        <div class="detail-row"><div class="detail-label">No. HP:</div><div class="detail-value">${ticket.phone}</div></div>
        <div class="detail-row"><div class="detail-label">Tipe Tiket:</div><div class="detail-value"><span class="type-badge type-${ticket.primary_ticket_type}">${String(ticket.primary_ticket_type).toUpperCase()}</span></div></div>
        <div class="detail-row"><div class="detail-label">Harga:</div><div class="detail-value">${formatPrice(ticket.price)}</div></div>
        <div class="detail-row"><div class="detail-label">Status:</div><div class="detail-value"><span class="status-badge status-${ticket.status}">${ticket.status === 'pending' ? 'Pending' : 'Lunas'}</span></div></div>
        <div class="detail-row"><div class="detail-label">Tanggal Order:</div><div class="detail-value">${formatDate(ticket.order_date)}</div></div>
        ${imageUrl ? `
        <div class="detail-row full-width">
            <div class="detail-label" style="margin-bottom:10px;">Bukti Transfer:</div>
            <div class="detail-value">
                <img src="https://www.parokitulungagung.org/e-ticket${imageUrl}" alt="Bukti Transfer" class="proof-image" onclick="window.open('https://www.parokitulungagung.org/e-ticket${imageUrl}','_blank')">
                <p style="margin-top:10px;font-size:.9em;color:#666;text-align:center;"><em>Klik gambar untuk melihat ukuran penuh</em></p>
            </div>
        </div>` : `
        <div class="detail-row"><div class="detail-label">Bukti Transfer:</div><div class="detail-value" style="color:#999;font-style:italic;">Belum ada bukti transfer</div></div>`}
        ${renderDetailSeats(ticket)}
    `;

    const verifyBtn = document.getElementById('verifyBtn');
    if (ticket.status === 'paid') {
        verifyBtn.style.display = 'none';
    } else {
        verifyBtn.style.display = 'block';
        verifyBtn.onclick = () => { closeModal('detailModal'); verifyPayment(ticketId); };
    }

    document.getElementById('detailTicketLink').href = `${TICKET_PAGE_BASE}?id=${ticket.id}`;
    document.getElementById('detailModal').style.display = 'block';
}

// ── QR Code viewer ────────────────────────────────────────
async function viewQR(ticketId) {
    const ticket = allTickets.find(t => t.id === ticketId);
    if (!ticket.qr_data) { alert('QR Code belum tersedia!'); return; }

    const container = document.getElementById('qrCodeDisplay');
    container.innerHTML = `
        <div id="qrGenerated" style="padding:20px;background:#fff;border-radius:20px;box-shadow:0 8px 30px rgba(0,0,0,.1);display:inline-block;"></div>
        <div style="margin-top:25px;font-size:18px;text-align:center;">
            <strong style="font-size:24px;">${ticket.ticket_number}</strong><br>
            ${ticket.name}<br>
            <span class="type-badge type-${ticket.primary_ticket_type}">${String(ticket.primary_ticket_type).toUpperCase()}</span>
        </div>
    `;

    new QRCode('qrGenerated', {
        text: ticket.qr_data, width: 260, height: 260,
        colorDark: '#000', colorLight: '#fff', correctLevel: QRCode.CorrectLevel.H
    });

    document.getElementById('openTicketPageBtn').onclick = () => {
        window.open(`${TICKET_PAGE_BASE}?id=${ticket.id}`, '_blank');
    };

    currentTicket = ticket;

    // Tampilkan status WA di modal
    updateQRModalWAStatus(ticket.wa_sent, ticket.wa_sent_at);

    document.getElementById('qrModal').style.display = 'block';
}

// ── Update indikator WA di QR Modal ──────────────────────
function updateQRModalWAStatus(isSent, sentAt) {
    // Hapus indikator lama kalau ada
    const oldIndicator = document.getElementById('waStatusIndicator');
    if (oldIndicator) oldIndicator.remove();

    // Cari tombol WA untuk insert setelah tombol WA
    const waBtn = document.querySelector('.wa-btn');
    if (!waBtn) return;
    const btnRow = waBtn.closest('div');
    if (!btnRow) return;

    const indicator = document.createElement('div');
    indicator.id = 'waStatusIndicator';
    indicator.style.marginTop = '10px';

    if (isSent) {
        const sentStr = sentAt
            ? new Date(sentAt).toLocaleDateString('id-ID', { weekday:'short', day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit' })
            : '';
        indicator.innerHTML = `
            <div class="wa-sent-indicator">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
                Tiket sudah dikirim ke WhatsApp${sentStr ? ' — ' + sentStr : ''}
            </div>`;
    } else {
        indicator.innerHTML = `
            <div style="text-align:center;font-size:.78rem;color:#94a3b8;padding:4px 0;">
                ⚪ Belum pernah dikirim ke WhatsApp
            </div>`;
    }

    btnRow.insertAdjacentElement('afterend', indicator);
}

// ── Download QR ───────────────────────────────────────────
function downloadQR() {
    const qrImg = document.querySelector('#qrGenerated img');
    if (!qrImg) { alert('QR belum siap!'); return; }

    const canvas = document.createElement('canvas');
    const ctx    = canvas.getContext('2d');
    canvas.width = 800; canvas.height = 1100;

    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, canvas.width, canvas.height);
    ctx.font = '46px sans-serif'; ctx.fillStyle = '#000'; ctx.textAlign = 'center';
    ctx.fillText('E-TICKET ACCESS', canvas.width / 2, 90);

    const img = new Image();
    img.src = qrImg.src;
    img.onload = () => {
        ctx.drawImage(img, 150, 150, 500, 500);
        ctx.font = 'bold 58px sans-serif';
        ctx.fillText(currentTicket.ticket_number, canvas.width / 2, 760);
        ctx.font = '40px sans-serif';
        ctx.fillText(currentTicket.name, canvas.width / 2, 830);
        ctx.fillStyle = '#444'; ctx.font = '34px sans-serif';
        ctx.fillText(String(currentTicket.primary_ticket_type).toUpperCase(), canvas.width / 2, 900);

        const link      = document.createElement('a');
        link.download   = `QR_${currentTicket.ticket_number}.png`;
        link.href       = canvas.toDataURL('image/png');
        link.click();
    };
}

// ── Kirim QR via WhatsApp ─────────────────────────────────
// FIX: fungsi dijadikan async langsung (bukan nested), kurung tutup diperbaiki
async function sendQRViaWhatsApp() {
    if (!currentTicket) { alert('Data tiket tidak ditemukan!'); return; }

    const phone = currentTicket.phone;
    if (!phone) { alert('Nomor HP tidak tersedia untuk tiket ini!'); return; }

    // Normalise Indonesian phone number → international format (62xxx)
    let normalised = phone.toString().trim().replace(/\D/g, '');
    if (normalised.startsWith('0')) {
        normalised = '62' + normalised.slice(1);
    } else if (!normalised.startsWith('62')) {
        normalised = '62' + normalised;
    }

    const ticketLink = `${TICKET_PAGE_BASE}?id=${currentTicket.id}`;

    // Helper: shorten satu URL via TinyURL, fallback ke link asli jika gagal
const shortenUrl = async (url) => {
    try {
        // Pakai proxy PHP di server sendiri → lolos CSP karena 'self'
        const res = await fetch(`/e-ticket/shorten.php?url=${encodeURIComponent(url)}`);
        if (!res.ok) throw new Error('Server error');
        const short = await res.text();
        return short.trim() || url;
    } catch (e) {
        console.warn('Shortener gagal, pakai link asli:', e);
        return url;
    }
};

    // Shorten kedua link secara paralel
    const [shortTicketLink, shortGroupLink] = await Promise.all([
        shortenUrl(ticketLink),
        shortenUrl('https://chat.whatsapp.com/DphJqo2kL3W30OMR3Gdsr2')
    ]);

    const message =
`★ Halo *${currentTicket.name}*! ★
Kami dengan senang hati menginformasikan bahwa tiket Anda telah *diverifikasi* 
Berikut detail tiket Anda:
*No Tiket:* ${currentTicket.ticket_number}
*Tipe Tiket:* ${String(currentTicket.primary_ticket_type).toUpperCase()}
*Nama:* ${currentTicket.name}

Silakan tunjukkan pesan ini saat memasuki venue. Anda juga dapat mengakses e-tiket Anda melalui tautan berikut:
${shortTicketLink}

Silahkan bergabung grup whatsapp peserta untuk menerima informasi lebih lanjut
${shortGroupLink}

Sampai jumpa di acara! ꉂ(˵˃ ᗜ ˂˵)
*_Pesan ini dikirim secara otomatis oleh sistem tiket. mohon jangan menghapus pesan ini_*`;

    const waUrl = `https://wa.me/${normalised}?text=${encodeURIComponent(message)}`;
    window.open(waUrl, '_blank');

    // ── Tandai tiket sudah dikirim WA ──────────────────────
    try {
        await SB.markWASent(currentTicket.id);
        // Update data lokal supaya badge langsung berubah tanpa reload penuh
        const idx = allTickets.findIndex(t => t.id === currentTicket.id);
        if (idx !== -1) {
            allTickets[idx].wa_sent    = true;
            allTickets[idx].wa_sent_at = new Date().toISOString();
            currentTicket = allTickets[idx];
        }
        renderTickets();
        // Tampilkan indikator "Sudah Dikirim" di dalam QR modal
        updateQRModalWAStatus(true);
        showToast('✅ Tiket berhasil dikirim ke WhatsApp & dicatat!', 'success');
    } catch (err) {
        console.warn('Gagal mencatat wa_sent:', err);
        showToast('⚠️ WA dibuka, tapi gagal menyimpan status kirim', 'error');
    }
}

// ── Hapus tiket ───────────────────────────────────────────
async function deleteTicket() {
    showConfirmModal({
        type: 'delete',
        title: 'Hapus Tiket',
        desc: `Tiket #${currentTicket.ticket_number} akan dihapus permanen dan tidak dapat dikembalikan.`,
        okLabel: 'Ya, Hapus',
        onOk: async () => {
            try {
                await SB.deleteTicket(currentTicket.id);
                showToast('Tiket berhasil dihapus', 'success');
                closeModal('detailModal');
                await loadTickets();
            } catch (error) {
                showToast('Terjadi kesalahan: ' + error.message, 'error');
            }
        }
    });
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

window.onclick = function (event) {
    if (event.target.classList.contains('modal')) event.target.style.display = 'none';
};

// ── Export Excel ──────────────────────────────────────────
async function downloadCSV() {
    const btn          = event.target;
    const originalHTML = btn.innerHTML;
    btn.innerHTML = '<span>⏳</span> <span>Membuat Excel...</span>';
    btn.disabled  = true;

    try {
        if (typeof XLSX === 'undefined') {
            await loadScript('https://cdn.sheetjs.com/xlsx-0.20.1/package/dist/xlsx.full.min.js');
        }

        const total        = allTickets.length;
        const pending      = allTickets.filter(t => t.status === 'pending').length;
        const paid         = allTickets.filter(t => t.status === 'paid').length;
        const totalRevenue = allTickets.reduce((sum, t) => sum + (t.price || 0), 0);

        // Nama tampilan Tipe Tiket (REGULER, KELAS 1, VIP, VVIP) dari config admin
        const typeNameMap = {};
        (window.ticketConfig || []).forEach(tc => {
            typeNameMap[tc.type] = String(tc.name).toUpperCase();
        });

        const wb        = XLSX.utils.book_new();
        const tableData = [];

        tableData.push(['LAPORAN PEMESANAN TIKET']);
        tableData.push([]);
        const now     = new Date();
        const dateStr = now.toLocaleDateString('id-ID', { year:'numeric',month:'long',day:'numeric',hour:'2-digit',minute:'2-digit' });
        tableData.push([`Tanggal Cetak: ${dateStr} WIB`]);
        tableData.push([`Total Tiket: ${total} | Pending: ${pending} | Lunas: ${paid}`]);
        tableData.push([]);
        tableData.push(['No.','No. Tiket','Nama Pemesan','No. HP','Tipe Tiket','Jumlah Tiket','Harga (Rp)','Status','Tanggal Order','Bukti Transfer']);

        allTickets.forEach((ticket, index) => {
            const seatCount = ticket.total_seats || parseSeats(ticket).length || 1;
            const typeLabel = typeNameMap[ticket.primary_ticket_type] || String(ticket.primary_ticket_type).toUpperCase();
            tableData.push([
                index + 1,
                ticket.ticket_number,
                ticket.name,
                ticket.phone,
                typeLabel,
                seatCount,
                ticket.price,
                ticket.status === 'pending' ? 'Pending' : 'Lunas',
                new Date(ticket.order_date).toLocaleString('id-ID', { year:'numeric',month:'2-digit',day:'2-digit',hour:'2-digit',minute:'2-digit' }),
                ticket.image_url ? 'Ada' : 'Tidak Ada'
            ]);
        });

        // Baris header ada di baris ke-6 (1-indexed), data dimulai baris ke-7
        const headerRowNum = 6;
        const firstDataRow = 7;
        const lastDataRow  = firstDataRow + total - 1;
        const totalRowNum  = lastDataRow + 2; // +1 baris kosong pemisah, +1 baris TOTAL

        tableData.push([]); // baris kosong pemisah, supaya TOTAL tidak ikut ter-filter/hidden
        tableData.push(['','','','','','TOTAL (sesuai filter)', totalRevenue,'','','']);

        const ws = XLSX.utils.aoa_to_sheet(tableData);
        ws['!cols'] = [
            {wch:6},{wch:15},{wch:25},{wch:15},{wch:12},{wch:14},{wch:15},{wch:12},{wch:20},{wch:15}
        ];
        ws['!merges'] = [
            { s:{r:0,c:0}, e:{r:0,c:9} },
            { s:{r:2,c:0}, e:{r:2,c:9} },
            { s:{r:3,c:0}, e:{r:3,c:9} }
        ];

        // Format angka Harga (kolom G) jadi ribuan pakai titik, contoh: 1.200.000
        for (let r = firstDataRow; r <= lastDataRow; r++) {
            const cellRef = 'G' + r;
            if (ws[cellRef]) ws[cellRef].z = '#,##0';
        }

        if (total > 0) {
            // Aktifkan filter dropdown HANYA di kolom "Tipe Tiket" (kolom E)
            ws['!autofilter'] = { ref: `E${headerRowNum}:E${lastDataRow}` };

            // Harga (kolom G) di baris TOTAL memakai rumus SUBTOTAL, jadi otomatis
            // menyesuaikan hanya menjumlahkan baris yang masih tampil saat difilter
            ws['G' + totalRowNum] = {
                t: 'n',
                f: `SUBTOTAL(109,G${firstDataRow}:G${lastDataRow})`,
                v: totalRevenue,
                z: '#,##0'
            };
        }

        XLSX.utils.book_append_sheet(wb, ws, 'Laporan Tiket');
        XLSX.writeFile(wb, `Laporan_Tiket_${new Date().toISOString().split('T')[0]}.xlsx`, { cellStyles: true });

    } catch (error) {
        console.error('Error:', error);
        alert('Gagal membuat Excel: ' + error.message);
    } finally {
        btn.innerHTML = originalHTML;
        btn.disabled  = false;
    }
}

function loadScript(src) {
    return new Promise((resolve, reject) => {
        const script   = document.createElement('script');
        script.src     = src;
        script.onload  = resolve;
        script.onerror = reject;
        document.head.appendChild(script);
    });
}

function previewImage(imageUrl) {
    document.getElementById('previewImage').src                = imageUrl;
    document.getElementById('imagePreviewModal').style.display = 'flex';
}

function closeImagePreview() {
    document.getElementById('imagePreviewModal').style.display = 'none';
}

window.addEventListener('click', e => {
    if (e.target.id === 'imagePreviewModal') closeImagePreview();
});

// ── Export PDF Daftar Check-In ────────────────────────────

// Tiket dengan "Tiket Offline" pada Nama Pemesan -> selalu di urutan paling belakang
function isOfflineTicket(ticket) {
    return String(ticket.name || '').toLowerCase().includes('tiket offline');
}

// Tiket dengan pola "tiket-sponsor_" pada No. Tiket -> masuk halaman tersendiri (campur zona)
function isSponsorTicket(ticket) {
    return String(ticket.ticket_number || '').toLowerCase().includes('tiket-sponsor_');
}

// Urutan: non-offline dulu (A-Z), lalu "Tiket Offline" di paling belakang (A-Z)
function sortWithOfflineLast(list) {
    return list.slice().sort((a, b) => {
        const offA = isOfflineTicket(a) ? 1 : 0;
        const offB = isOfflineTicket(b) ? 1 : 0;
        if (offA !== offB) return offA - offB;
        return a.name.localeCompare(b.name, 'id', { sensitivity: 'base' });
    });
}

async function downloadPDF() {
    const btn          = event.target;
    const originalHTML = btn.innerHTML;
    btn.innerHTML = '<span>⏳</span> <span>Membuat PDF...</span>';
    btn.disabled  = true;

    try {
        if (typeof window.jspdf === 'undefined') {
            await loadScript('https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js');
        }
        if (typeof window.jspdf?.jsPDF?.API?.autoTable === 'undefined') {
            await loadScript('https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js');
        }

        const { jsPDF } = window.jspdf;
        const doc     = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' });
        const PAGE_W  = doc.internal.pageSize.getWidth();
        const PAGE_H  = doc.internal.pageSize.getHeight();
        const MARGIN  = 12;
        const now     = new Date();
        const dateStr = now.toLocaleDateString('id-ID', {
            weekday: 'long', year: 'numeric', month: 'long', day: 'numeric',
            hour: '2-digit', minute: '2-digit'
        });

        // Hanya tiket LUNAS
        const paidTickets = allTickets.filter(t => t.status === 'paid');

        // Pisahkan dulu tiket sponsor (tiket-sponsor_xxx) -> halaman tersendiri, campur semua zona
        const sponsorTickets = paidTickets.filter(isSponsorTicket);
        const regularTickets = paidTickets.filter(t => !isSponsorTicket(t));

        const ZONE_ORDER = ['vvip', 'vip', 'kelas1', 'reguler'];
        // Zona yang tidak memiliki nomor kursi, hanya jumlah kursi yang dipesan
        const SEAT_COUNT_ZONES = ['kelas1', 'reguler'];

        const ZONE_META  = {
            vvip:    { label: 'ZONA VVIP',    color: [146, 64,  14],  headerBg: [146, 64,  14],  rowBg: [255, 251, 235], altBg: [254, 243, 199] },
            vip:     { label: 'ZONA VIP',     color: [136, 19,  55],  headerBg: [136, 19,  55],  rowBg: [255, 241, 242], altBg: [254, 228, 232] },
            kelas1:  { label: 'ZONA KELAS 1', color: [20,  83,  45],  headerBg: [20,  83,  45],  rowBg: [240, 253, 244], altBg: [220, 252, 231] },
            reguler: { label: 'ZONA REGULER', color: [30,  58,  138], headerBg: [30,  58,  138], rowBg: [239, 246, 255], altBg: [219, 234, 254] },
            sponsor: { label: 'TIKET SPONSOR', color: [88,  28,  135], headerBg: [88,  28,  135], rowBg: [250, 245, 255], altBg: [237, 233, 254] },
        };

        // Kelompokkan & urutkan A-Z per zona (khusus "Tiket Offline" didorong ke paling belakang)
        const grouped = {};
        ZONE_ORDER.forEach(z => grouped[z] = []);
        regularTickets.forEach(ticket => {
            const z = (ticket.primary_ticket_type || 'reguler').toLowerCase();
            if (grouped[z]) grouped[z].push(ticket);
            else grouped['reguler'].push(ticket);
        });
        ZONE_ORDER.forEach(z => {
            grouped[z] = sortWithOfflineLast(grouped[z]);
        });

        // Tiket sponsor: campur semua zona, tetap "Tiket Offline" didorong ke belakang
        const sponsorSorted = sortWithOfflineLast(sponsorTickets);

        // ── Helper: footer ──
        const drawFooter = (doc, meta, pageLabel) => {
            doc.setDrawColor(...meta.color);
            doc.setLineWidth(0.3);
            doc.line(MARGIN, PAGE_H - 12, PAGE_W - MARGIN, PAGE_H - 12);
            doc.setFontSize(7.5);
            doc.setTextColor(148, 163, 184);
            doc.setFont('helvetica', 'normal');
            doc.text('Paroki Tulungagung - Sistem E-Ticket', MARGIN, PAGE_H - 7);
            doc.text(pageLabel, PAGE_W / 2, PAGE_H - 7, { align: 'center' });
            doc.text('Dokumen dicetak otomatis oleh sistem', PAGE_W - MARGIN, PAGE_H - 7, { align: 'right' });
        };

                // ── Helper: ukur lebar teks terpanjang (untuk kolom dinamis) ──
        const measureMaxWidth = (texts, fontSize, fontStyle) => {
            doc.setFont('helvetica', fontStyle || 'normal');
            doc.setFontSize(fontSize);
            let max = 0;
            texts.forEach(txt => {
                const w = doc.getTextWidth(String(txt == null ? '' : txt));
                if (w > max) max = w;
            });
            return max;
        };

        // Lebar kolom "pas" (fit-content) berdasar isi terpanjang + padding sel,
        // supaya kolom seperti No. Tiket TIDAK PERNAH pecah jadi 2 baris.
        const fitColWidth = (headerText, bodyTexts, opts) => {
            const o = opts || {};
            const padH   = 6.4; // cellPadding kiri+kanan (3 + 3) + sedikit buffer anti-terpotong
            const headerW = measureMaxWidth([headerText], 8.5, 'bold');
            const bodyW   = measureMaxWidth(bodyTexts, o.fontSize || 8, o.fontStyle || 'normal');
            let w = Math.max(headerW, bodyW) + padH;
            if (o.min) w = Math.max(w, o.min);
            if (o.max) w = Math.min(w, o.max);
            return w;
        };

        const TABLE_WIDTH = PAGE_W - MARGIN * 2;

        // Bagi sisa lebar antara kolom "Kursi" (dibatasi max, boleh 2 baris) dan "Nama" (dapat sisanya).
        // Menjamin total lebar kolom TIDAK PERNAH melebihi lebar halaman (anti overflow).
        const splitSeatAndName = (remaining, seatFitWidth, opts) => {
            const o = { seatMin: 22, seatMax: 40, nameMin: 34, ...(opts || {}) };
            let seatW = Math.min(Math.max(seatFitWidth, o.seatMin), o.seatMax);
            let nameW = remaining - seatW;
            if (nameW < o.nameMin) {
                const deficit = o.nameMin - nameW;
                const shrink  = Math.min(deficit, seatW - o.seatMin);
                seatW -= shrink;
                nameW += shrink;
            }
            nameW = Math.max(nameW, 20); // lantai mutlak, terakhir kalau ruang benar-benar sempit
            return { seatW, nameW };
        };

        let isFirstZone = true;

        // ── Render satu halaman/tabel untuk sekumpulan tiket ──
        // mode: 'normal'   -> zona dengan nomor kursi (VVIP, VIP): No, No.Tiket, Nama, Tipe, Kursi, Check In
        //       'seatless' -> zona tanpa nomor kursi (Kelas 1, Reguler): kolom Kursi diganti "Jumlah Tiket"
        //       'mixed'    -> halaman sponsor (campur semua zona): tampilkan Kursi DAN Jumlah Tiket
        const renderZonePage = (tickets, meta, mode) => {
            if (!tickets.length) return;

            if (!isFirstZone) doc.addPage();
            isFirstZone = false;

            const zoneStartPage = doc.internal.getNumberOfPages();

            // ── Data per baris (disiapkan dulu agar bisa dipakai untuk mengukur lebar kolom) ──
            const rows = tickets.map((ticket, idx) => {
                const seats     = parseSeats(ticket);
                const seatStr   = seats.length ? seats.join(', ') : '-';
                const seatCount = ticket.total_seats || seats.length || 1;
                const typeLabel = String(ticket.primary_ticket_type || '-').toUpperCase();
                return {
                    no: idx + 1,
                    ticketNo: ticket.ticket_number,
                    name: ticket.name,
                    type: typeLabel,
                    seatStr,
                    jumlahLabel: seatCount + ' tiket',
                };
            });

            // ── Kolom "pas" (tidak pernah pecah baris): No, No. Tiket, Tipe, Jumlah/Check In ──
            const noW      = fitColWidth('No.', rows.map(r => r.no), { min: 9 });
            const ticketW  = fitColWidth('No. Tiket', rows.map(r => r.ticketNo), { min: 26 });
            const typeW    = fitColWidth('Tipe', rows.map(r => r.type), { min: 18 });
            const checkInW = fitColWidth('Check In', [], { min: 20 });
            const jumlahW  = fitColWidth('Jumlah Tiket', rows.map(r => r.jumlahLabel), { min: 24, fontStyle: 'bold' });

            let head, columnStyles, body, checkInColIndex, nameColIndex;

            if (mode === 'seatless') {
                // Kolom Kursi DIHILANGKAN, diganti kolom "Jumlah Tiket"
                const fixedW = noW + ticketW + typeW + jumlahW + checkInW;
                const nameW  = Math.max(TABLE_WIDTH - fixedW, 40); // sisa lebar -> kolom Nama (dinamis)

                head = [['No.', 'No. Tiket', 'Nama', 'Tipe', 'Jumlah Tiket', 'Check In']];
                columnStyles = {
                    0: { cellWidth: noW,      halign: 'center', fontSize: 8 },
                    1: { cellWidth: ticketW,  halign: 'left',   fontSize: 8 },
                    2: { cellWidth: nameW,    halign: 'left',   fontSize: 8 },
                    3: { cellWidth: typeW,    halign: 'center', fontSize: 8 },
                    4: { cellWidth: jumlahW,  halign: 'center', fontSize: 8, fontStyle: 'bold' },
                    5: { cellWidth: checkInW, halign: 'center', fontSize: 8 },
                };
                checkInColIndex = 5;
                nameColIndex    = 2;
                body = rows.map(r => [r.no, r.ticketNo, r.name, r.type, r.jumlahLabel, '']);

            } else if (mode === 'mixed') {
                const seatFit = fitColWidth('Kursi', rows.map(r => r.seatStr), { fontSize: 7.5 });
                const fixedNarrow = noW + ticketW + typeW + jumlahW + checkInW;
                const remaining   = TABLE_WIDTH - fixedNarrow;
                const { seatW, nameW } = splitSeatAndName(remaining, seatFit, { seatMin: 22, seatMax: 36, nameMin: 34 });

                head = [['No.', 'No. Tiket', 'Nama', 'Tipe', 'Kursi', 'Jumlah Tiket', 'Check In']];
                columnStyles = {
                    0: { cellWidth: noW,      halign: 'center', fontSize: 8   },
                    1: { cellWidth: ticketW,  halign: 'left',   fontSize: 8   },
                    2: { cellWidth: nameW,    halign: 'left',   fontSize: 8   },
                    3: { cellWidth: typeW,    halign: 'center', fontSize: 8   },
                    4: { cellWidth: seatW,    halign: 'left',   fontSize: 7.5 },
                    5: { cellWidth: jumlahW,  halign: 'center', fontSize: 8, fontStyle: 'bold' },
                    6: { cellWidth: checkInW, halign: 'center', fontSize: 8   },
                };
                checkInColIndex = 6;
                nameColIndex    = 2;
                body = rows.map(r => [r.no, r.ticketNo, r.name, r.type, r.seatStr, r.jumlahLabel, '']);

            } else {
                // normal: VVIP / VIP -> ada nomor kursi
                const seatFit = fitColWidth('Kursi', rows.map(r => r.seatStr), { fontSize: 7.5 });
                const fixedNarrow = noW + ticketW + typeW + checkInW;
                const remaining   = TABLE_WIDTH - fixedNarrow;
                const { seatW, nameW } = splitSeatAndName(remaining, seatFit, { seatMin: 26, seatMax: 42, nameMin: 42 });

                head = [['No.', 'No. Tiket', 'Nama', 'Tipe', 'Kursi', 'Check In']];
                columnStyles = {
                    0: { cellWidth: noW,      halign: 'center', fontSize: 8   },
                    1: { cellWidth: ticketW,  halign: 'left',   fontSize: 8   },
                    2: { cellWidth: nameW,    halign: 'left',   fontSize: 8   },
                    3: { cellWidth: typeW,    halign: 'center', fontSize: 8   },
                    4: { cellWidth: seatW,    halign: 'left',   fontSize: 7.5 },
                    5: { cellWidth: checkInW, halign: 'center', fontSize: 8   },
                };
                checkInColIndex = 5;
                nameColIndex    = 2;
                body = rows.map(r => [r.no, r.ticketNo, r.name, r.type, r.seatStr, '']);
            }

            doc.autoTable({
                startY: 43,
                margin: { left: MARGIN, right: MARGIN, bottom: 18, top: 43 },

                head,
                body,
                columnStyles,
                tableWidth: TABLE_WIDTH,

                headStyles: {
                    fillColor: meta.headerBg,
                    textColor: [255, 255, 255],
                    fontStyle: 'bold',
                    fontSize: 8.5,
                    halign: 'center',
                    cellPadding: { top: 3.5, bottom: 3.5, left: 3, right: 3 },
                    lineWidth: 0,
                },

                bodyStyles: {
                    fontSize: 8,
                    cellPadding: { top: 3.5, bottom: 3.5, left: 3, right: 3 },
                    lineWidth: 0.15,
                    lineColor: [220, 228, 240],
                    minCellHeight: 9,
                    textColor: [30, 30, 50],
                    overflow: 'linebreak',   // bungkus teks, jangan pernah terpotong
                    valign: 'middle',
                },

                // Kolom No. dan No. Tiket dipaksa 1 baris (lebarnya sudah dihitung pas)
                didParseCell: (data) => {
                    if (data.column.index === 0 || data.column.index === 1) {
                        data.cell.styles.overflow = 'visible';
                    }
                    if (data.section === 'body') {
                        data.cell.styles.fillColor = data.row.index % 2 === 0
                            ? meta.rowBg
                            : meta.altBg;
                        // Highlight baris "Tiket Offline" agar mudah dikenali
                        const t = tickets[data.row.index];
                        if (t && isOfflineTicket(t)) {
                            data.cell.styles.fillColor = [241, 245, 249];
                            data.cell.styles.fontStyle = data.column.index === nameColIndex ? 'italic' : data.cell.styles.fontStyle;
                        }
                    }
                },

                // Header zona di tiap halaman — hanya teks ASCII
                didDrawPage: (data) => {
                    // Bar background
                    doc.setFillColor(...meta.headerBg);
                    doc.rect(0, 0, PAGE_W, 32, 'F');

                    // Judul zona — font helvetica, ASCII only
                    doc.setTextColor(255, 255, 255);
                    doc.setFont('helvetica', 'bold');
                    doc.setFontSize(20);

                    doc.text(meta.label, PAGE_W / 2, 14, { align: 'center' });

                    // Sub-baris info
                    doc.setFontSize(8.5);
                    doc.setFont('helvetica', 'normal');
                    doc.text(
                        'Paroki Tulungagung - Daftar Check-In Penukaran Gelang',
                        PAGE_W / 2, 21, { align: 'center' }
                    );
                    doc.text(
                        'Dicetak: ' + dateStr + ' WIB',
                        PAGE_W / 2, 27, { align: 'center' }
                    );

                    // Keterangan jumlah tiket (kiri bawah header)
                    doc.setTextColor(...meta.color);
                    doc.setFontSize(8);
                    doc.setFont('helvetica', 'bold');
                    doc.text('Total: ' + tickets.length + ' tiket', MARGIN, 38);

                    // Garis pemisah
                    doc.setDrawColor(...meta.color);
                    doc.setLineWidth(0.4);
                    doc.line(MARGIN, 40, PAGE_W - MARGIN, 40);
                },

                // Kotak checkbox di kolom Check In
                didDrawCell: (data) => {
                    if (data.section === 'body' && data.column.index === checkInColIndex) {
                        const x = data.cell.x + (data.cell.width  - 6) / 2;
                        const y = data.cell.y + (data.cell.height - 6) / 2;
                        doc.setDrawColor(...meta.color);
                        doc.setLineWidth(0.5);
                        doc.roundedRect(x, y, 6, 6, 0.8, 0.8, 'S');
                    }
                },

                showHead: 'everyPage',
                pageBreak: 'auto',
                rowPageBreak: 'avoid',
                tableLineWidth: 0,
            });

            // Footer di setiap halaman zona
            const zoneEndPage   = doc.internal.getNumberOfPages();
            const zonePageTotal = zoneEndPage - zoneStartPage + 1;

            for (let p = zoneStartPage; p <= zoneEndPage; p++) {
                doc.setPage(p);
                const localPage = p - zoneStartPage + 1;
                drawFooter(doc, meta, meta.label + '  -  Halaman ' + localPage + ' dari ' + zonePageTotal);
            }
        };

        // Halaman per zona (VVIP, VIP, Kelas 1, Reguler)
        ZONE_ORDER.forEach(zone => {
            const mode = SEAT_COUNT_ZONES.includes(zone) ? 'seatless' : 'normal';
            renderZonePage(grouped[zone], ZONE_META[zone], mode);
        });

        // Halaman khusus tiket sponsor (campur semua zona) -> tampilkan Kursi & Jumlah Tiket
        // karena bisa berisi campuran zona Kelas 1/Reguler (tanpa nomor kursi) dan zona lain.
        renderZonePage(sponsorSorted, ZONE_META.sponsor, 'mixed');


        const filename = 'CheckIn_Gelang_' + now.toISOString().split('T')[0] + '.pdf';
        doc.save(filename);

    } catch (error) {
        console.error('Error export PDF:', error);
        alert('Gagal membuat PDF: ' + error.message);
    } finally {
        btn.innerHTML = originalHTML;
        btn.disabled  = false;
    }
}

// ── Initialize ────────────────────────────────────────────
(function initDefaultFilter() {
    document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
    const pendingBtn = document.querySelector('.filter-btn[onclick="filterStatus(\'pending\')"]');
    if (pendingBtn) pendingBtn.classList.add('active');
})();

loadTickets();
setInterval(loadTickets, 60000);