// ========================================
// LOADING ANIMATIONS & STATS REFRESH
// Migrasi dari PocketBase ke Supabase
// Menggunakan SB helper dari supabase.js
// ========================================

function initPageLoading() {
    const loadingHTML = `
        <div class="page-loading-overlay" id="pageLoadingOverlay">
            <div class="loading-spinner-container">
                <div class="loading-spinner"></div>
                <div class="loading-spinner-inner"></div>
                <div class="loading-logo">🙏</div>
            </div>
            <div class="loading-text">Memuat Data Event</div>
            <div class="loading-subtext">Mohon tunggu sebentar...</div>
        </div>
    `;
    document.body.insertAdjacentHTML('afterbegin', loadingHTML);
    return document.getElementById('pageLoadingOverlay');
}

function hidePageLoading() {
    const overlay = document.getElementById('pageLoadingOverlay');
    if (overlay) {
        overlay.classList.add('hidden');
        setTimeout(() => overlay.remove(), 500);
    }
}

function showSeatLayoutLoading(container) {
    container.innerHTML = `
        <div class="seat-layout-loading" id="seatLayoutLoading">
            <div class="seat-loading-spinner"></div>
            <div class="seat-loading-text">
                Memuat Layout Kursi
                <span class="seat-loading-dots"><span></span><span></span><span></span></span>
            </div>
        </div>
    `;
}

function hideSeatLayoutLoading() {
    const loading = document.getElementById('seatLayoutLoading');
    if (loading) {
        loading.style.opacity = '0';
        setTimeout(() => loading.remove(), 300);
    }
}

// ── Cache ─────────────────────────────────────────────────
const dataCache = { lastFetch: 0, data: null, cacheDuration: 5000 };

/**
 * SUPABASE EDITION — Menggantikan loadDataFromPocketBase()
 * Menggunakan SB.getAllTickets() dari supabase.js
 */
async function loadDataFromSupabase(forceRefresh = false) {
    const now = Date.now();
    if (!forceRefresh && dataCache.data && (now - dataCache.lastFetch) < dataCache.cacheDuration) {
        return dataCache.data;
    }

    try {
        // Mengganti: fetch(`${PB_URL}/api/collections/ticketing/records?perPage=1500`)
        const tickets   = await SB.getAllTickets();
        const totalQuota = (typeof TOTAL_QUOTA !== 'undefined') ? TOTAL_QUOTA : 1200;

        let totalSeatsBooked = 0;
        if (Array.isArray(tickets)) {
            tickets.forEach(ticket => {
                if (ticket.total_seats && typeof ticket.total_seats === 'number') {
                    totalSeatsBooked += ticket.total_seats;
                } else if (ticket.seat_numbers) {
                    try {
                        const seats = Array.isArray(ticket.seat_numbers)
                            ? ticket.seat_numbers : JSON.parse(ticket.seat_numbers);
                        if (Array.isArray(seats)) totalSeatsBooked += seats.length;
                    } catch (e) { totalSeatsBooked += 1; }
                }
            });
        }

        const totalSold      = totalSeatsBooked;
        const totalAvailable = Math.max(0, totalQuota - totalSold);
        const result = { success: true, data: { totalSold, totalAvailable, items: tickets || [] } };

        dataCache.data      = result;
        dataCache.lastFetch = now;
        return result;

    } catch (error) {
        console.error('Error loading data from Supabase:', error);
        if (dataCache.data) return dataCache.data;
        const totalQuota = (typeof TOTAL_QUOTA !== 'undefined') ? TOTAL_QUOTA : 1200;
        return { success: false, data: { totalSold: 0, totalAvailable: totalQuota } };
    }
}

// Alias lama agar tidak ada referensi yang putus
const loadDataFromPocketBase = loadDataFromSupabase;

/**
 * SUPABASE EDITION — Menggantikan loadSeatDataFromPocketBase()
 * Field berubah: seatNumbers → seat_numbers (JSONB, sudah array)
 */
async function loadSeatDataFromSupabase() {
    try {
        const result = await loadDataFromSupabase();
        if (!result.success || !result.data.items) return { success: false, bookedSeats: [] };

        const bookedSeats = [];
        result.data.items.forEach(ticket => {
            if (ticket.seat_numbers) {
                try {
                    const seats = Array.isArray(ticket.seat_numbers)
                        ? ticket.seat_numbers : JSON.parse(ticket.seat_numbers);
                    if (Array.isArray(seats)) bookedSeats.push(...seats);
                } catch (e) { console.error('Error parsing seat_numbers:', e); }
            }
        });
        return { success: true, bookedSeats };
    } catch (error) {
        console.error('Error loading seat data from Supabase:', error);
        return { success: false, bookedSeats: [] };
    }
}

const loadSeatDataFromPocketBase = loadSeatDataFromSupabase;

async function initializePageWithLoading() {
    initPageLoading();
    try {
        const totalQuota = (typeof TOTAL_QUOTA !== 'undefined') ? TOTAL_QUOTA : 1200;
        const data = await Promise.race([
            loadDataFromSupabase(true),
            new Promise(resolve => setTimeout(() => resolve({
                success: true, data: { totalSold: 0, totalAvailable: totalQuota }
            }), 3000))
        ]);
        if (data.success) {
            const elSold  = document.getElementById('totalSold');
            const elAvail = document.getElementById('totalAvailable');
            if (elSold)  animateNumber(elSold,  data.data.totalSold,      1000);
            if (elAvail) animateNumber(elAvail, data.data.totalAvailable, 1000);
        }
        setTimeout(hidePageLoading, 800);
    } catch (error) {
        console.error('Error initializing page:', error);
        setTimeout(hidePageLoading, 800);
    }
}

function animateNumber(element, target, duration) {
    const start     = parseInt(element.textContent) || 0;
    const range     = target - start;
    const startTime = performance.now();
    function update(currentTime) {
        const progress    = Math.min((currentTime - startTime) / duration, 1);
        const easeOutQuad = progress * (2 - progress);
        element.textContent = Math.floor(start + (range * easeOutQuad));
        if (progress < 1) requestAnimationFrame(update);
        else element.textContent = target;
    }
    requestAnimationFrame(update);
}

async function initializeSeatLayoutWithLoading() {
    const leftSection  = document.getElementById('leftSection');
    const rightSection = document.getElementById('rightSection');
    if (!leftSection || !rightSection) return;

    showSeatLayoutLoading(leftSection);
    showSeatLayoutLoading(rightSection);

    try {
        const seatData = await loadSeatDataFromSupabase();
        if (seatData.success && seatData.bookedSeats.length > 0) {
            if (typeof bookedSeats !== 'undefined') {
                seatData.bookedSeats.forEach(id => bookedSeats.add(id));
            }
        }
        if (typeof renderSeatingLayout === 'function') renderSeatingLayout();
        hideSeatLayoutLoading();
        setTimeout(() => {
            if (typeof setupZoneClickHandlers === 'function') setupZoneClickHandlers();
        }, 100);
    } catch (error) {
        console.error('Error loading seat data:', error);
        hideSeatLayoutLoading();
        if (typeof renderSeatingLayout === 'function') renderSeatingLayout();
    }
}

async function refreshStatsData() {
    return loadDataFromSupabase(true);
}

// CSS animations
const _liStyle = document.createElement('style');
_liStyle.textContent = `
    @keyframes fadeIn    { from{opacity:0;transform:scale(.95)} to{opacity:1;transform:scale(1)} }
    @keyframes fadeOut   { from{opacity:1;transform:scale(1)}  to{opacity:.7;transform:scale(.98)} }
    @keyframes statsPulse{ 0%,100%{transform:scale(1)} 50%{transform:scale(1.05)} }
    .stats-updating { animation: statsPulse 0.5s ease; }
`;
document.head.appendChild(_liStyle);

// Init on DOMContentLoaded
function _liInit() {
    const isIndex = document.getElementById('singleTicketContainer');
    const isSeat  = document.querySelector('.seat-selection-page');

    if (isIndex) {
        initPageLoading();
        // main.js handles renderTickets(); cukup hide overlay
        setTimeout(hidePageLoading, 1500);
    }
    if (isSeat) {
        initPageLoading();
        setTimeout(() => { hidePageLoading(); initializeSeatLayoutWithLoading(); }, 800);
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', _liInit);
} else {
    _liInit();
}

// Export
if (typeof window !== 'undefined') {
    window.initPageLoading                 = initPageLoading;
    window.hidePageLoading                 = hidePageLoading;
    window.showSeatLayoutLoading           = showSeatLayoutLoading;
    window.hideSeatLayoutLoading           = hideSeatLayoutLoading;
    window.initializeSeatLayoutWithLoading = initializeSeatLayoutWithLoading;
    window.loadDataFromSupabase            = loadDataFromSupabase;
    window.loadDataFromPocketBase          = loadDataFromSupabase; // alias
    window.refreshStatsData                = refreshStatsData;
    window.animateNumber                   = animateNumber;
}