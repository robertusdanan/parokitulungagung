// ============================================================
// SUPABASE.JS — Helper API Client
// Database: Supabase REST API
// Storage : LOKAL (upload via upload.php, bukan Supabase Storage)
// SUPABASE_URL dan SUPABASE_ANON di-inject dari PHP (config.php)
// ============================================================

const SB = (() => {

    // ── Base headers ─────────────────────────────────────────
    function headers(extra = {}) {
        return {
            'apikey':        SUPABASE_ANON,
            'Authorization': `Bearer ${SUPABASE_ANON}`,
            'Content-Type':  'application/json',
            'Prefer':        'return=representation',
            ...extra
        };
    }

    const REST = `${SUPABASE_URL}/rest/v1`;

    // ── GET semua tiket ──────────────────────────────────────
    async function getAllTickets() {
        const res = await fetch(
            `${REST}/ticketing?select=id,ticket_number,name,phone,ticket_type,primary_ticket_type,seat_numbers,total_seats,price,status,image_url,qr_data,order_date,created_at,wa_clicked,wa_clicked_at,wa_sent,wa_sent_at&order=created_at.desc&limit=1500`,
            { headers: headers() }
        );
        if (!res.ok) throw new Error(`Gagal memuat tiket: ${res.status}`);
        return res.json();
    }

    // ── GET satu tiket by ID ──────────────────────────────────
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

    // ── INSERT tiket baru ─────────────────────────────────────
    // Upload gambar dilakukan terpisah via upload.php (PHP)
    // imageUrl sudah berupa path lokal yang dikirim dari main.js
    async function createTicket(payload) {
        const res = await fetch(`${REST}/ticketing`, {
            method:  'POST',
            headers: headers(),
            body:    JSON.stringify(payload)
        });

        if (!res.ok) {
            const err = await res.json().catch(() => ({}));
            throw new Error(err.message || `Server error: ${res.status}`);
        }
        return res.json();
    }

    // ── UPDATE tiket (verifikasi pembayaran) ──────────────────
    async function updateTicket(id, patch) {
        const res = await fetch(`${REST}/ticketing?id=eq.${id}`, {
            method:  'PATCH',
            headers: headers(),
            body:    JSON.stringify(patch)
        });
        if (!res.ok) throw new Error('Gagal update tiket.');
        return res.json();
    }

    // ── DELETE tiket ──────────────────────────────────────────
    async function deleteTicket(id) {
        const res = await fetch(`${REST}/ticketing?id=eq.${id}`, {
            method:  'DELETE',
            headers: headers({ 'Prefer': 'return=minimal' })
        });
        if (!res.ok) throw new Error('Gagal menghapus tiket.');
        return true;
    }

    // ── Resolve URL gambar ────────────────────────────────────
    // Gambar kini disimpan lokal, image_url berisi path relatif
    // seperti /uploads/payment-proofs/TKT-xxx.jpg
    // Jika sudah full URL (http...), kembalikan langsung.
    function getImageUrl(imageUrl) {
        if (!imageUrl) return null;
        return imageUrl; // path lokal atau full URL, keduanya langsung bisa dipakai
    }

    // ── GET jumlah transaksi per zona (untuk kode unik harga) ────────
    // Mengembalikan objek { vvip: N, vip: N, kelas1: N, reguler: N, ... }
    // Setiap transaksi dihitung 1, terlepas dari jumlah kursi di dalamnya.
    async function getTransactionCountPerZone() {
        const res = await fetch(
            `${REST}/ticketing?select=primary_ticket_type&order=created_at.asc`,
            { headers: headers() }
        );
        if (!res.ok) throw new Error(`Gagal memuat data transaksi: ${res.status}`);
        const rows = await res.json();

        const counts = {};
        rows.forEach(row => {
            const z = (row.primary_ticket_type || 'reguler').toLowerCase();
            counts[z] = (counts[z] || 0) + 1;
        });
        return counts;
    }

    // ── Mark WA Sent (Admin kirim tiket via WhatsApp) ────────
    // Dipanggil setelah admin klik tombol "Kirim via WhatsApp" di QR modal
    async function markWASent(ticketId) {
        const res = await fetch(`${REST}/ticketing?id=eq.${ticketId}`, {
            method:  'PATCH',
            headers: headers(),
            body:    JSON.stringify({
                wa_sent:    true,
                wa_sent_at: new Date().toISOString()
            })
        });
        if (!res.ok) throw new Error('Gagal update wa_sent.');
        return res.json();
    }

    // ── Mark WA Clicked ──────────────────────────────────────
    // Tandai bahwa peserta sudah klik link WhatsApp Group
    async function markWAClicked(ticketNumber) {
        const res = await fetch(`${REST}/ticketing?ticket_number=eq.${encodeURIComponent(ticketNumber)}`, {
            method:  'PATCH',
            headers: headers(),
            body:    JSON.stringify({
                wa_clicked:    true,
                wa_clicked_at: new Date().toISOString()
            })
        });
        if (!res.ok) throw new Error('Gagal update wa_clicked.');
        return res.json();
    }

    // ── UPDATE seat_numbers & seat_selection_status ──────────────
    // Dipanggil setelah peserta VVIP/VIP selesai memilih kursi
    async function updateSeatNumbers(ticketId, seatNumbersArr) {
        const res = await fetch(`${REST}/ticketing?id=eq.${ticketId}`, {
            method:  'PATCH',
            headers: headers(),
            body:    JSON.stringify({
                seat_numbers:         seatNumbersArr,
                seat_selection_status: 'completed',
                seat_selected_at:     new Date().toISOString(),
            })
        });
        if (!res.ok) throw new Error('Gagal menyimpan pilihan kursi.');
        return res.json();
    }

    // ── GET tiket berdasarkan ticket_number (untuk resume seat selection) ──
    async function getTicketByNumber(ticketNumber) {
        const res = await fetch(
            `${REST}/ticketing?ticket_number=eq.${encodeURIComponent(ticketNumber)}&select=*&limit=1`,
            { headers: headers() }
        );
        if (!res.ok) throw new Error('Tiket tidak ditemukan.');
        const rows = await res.json();
        if (!rows.length) throw new Error('Tiket tidak ditemukan.');
        return rows[0];
    }

    // ── Public API ───────────────────────────────────────────
    return { getAllTickets, getTicketById, createTicket, updateTicket, deleteTicket, getImageUrl, getTransactionCountPerZone, markWASent, markWAClicked, updateSeatNumbers, getTicketByNumber };
})();
