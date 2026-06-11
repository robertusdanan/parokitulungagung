<?php
// ============================================================
// INDEX.PHP — Homepage
// Versi khusus yang mem-embed SEMUA CSS & JS secara inline.
// Tidak ada external <link> atau <script src=""> sehingga
// error 525 SSL dari Cloudflare tidak bisa memblokir halaman.
// ============================================================

require_once __DIR__ . '/config.php';
$seatStatus = getSeatStatus();

// Pastikan HTML halaman tidak di-cache browser agar seatStatus selalu fresh
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

// ── Helper: baca file JS/CSS, fallback string kosong jika tidak ada ──
function readAsset(string $path): string {
    $full = __DIR__ . '/' . ltrim($path, '/');
    return file_exists($full) ? file_get_contents($full) : "/* FILE NOT FOUND: {$path} */";
}

// ── Kumpulkan semua CSS ──────────────────────────────────────────────
$cssAll = readAsset('css/style.css')
        . "\n" . readAsset('css/seat-improvements.css');

// ── Kumpulkan semua JS (urutan sama persis dengan versi asli) ────────
$jsAll  = readAsset('js/supabase.js')
        . "\n" . readAsset('js/main.js')
        . "\n" . readAsset('js/loading-improvements.js');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" href="/favicon.ico?v=2">
    <title>Ngopi Bareng Romo Eko</title>

    <!-- ✅ SEMUA CSS INLINE — tidak ada request eksternal -->
    <style>
<?= $cssAll ?>
    </style>
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-3946321979629144"
     crossorigin="anonymous"></script>
</head>

<body>
<!-- BACK TO HOME BUTTON -->
<a href="/" class="btn-home">
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
        <polyline points="9 22 9 12 15 12 15 22"/>
    </svg>
    Beranda
</a>
    <!-- HERO SECTION -->
    <div class="hero-section">
        <div class="hero-ornament"></div>
        <div class="hero-content">
            <div class="event-badge">"Peka, Peduli, dan Bertindak" (Yakobus 1:22)</div>
            <h1 class="main-title">Malam Kasih dan Ngopi Bareng<br><span class="highlight">Bersama Romo Eko</span></h1>
            <p class="hero-subtitle">Untuk Pembangunan Griya Pastoral Umat Paroki Tulungagung</p>

            <div class="hero-stats">
                <div class="stat-item">
                    <div class="stat-value" id="totalSold">0</div>
                    <div class="stat-desc">Tiket Terjual</div>
                </div>
                <div class="stat-divider"></div>
                <div class="stat-item">
                    <div class="stat-value" id="totalAvailable"><?= TOTAL_QUOTA ?></div>
                    <div class="stat-desc">Tiket Tersedia</div>
                </div>
            </div>
        </div>
    </div>

    <div class="main-container">

        <!-- SEATING LAYOUT SECTION -->
        <section class="seating-section">
            <div class="section-header">
                <div class="section-label">Tata Letak Tempat Duduk</div>
                <h2 class="section-title">Pilih Posisi Terbaik Anda</h2>
                <p class="section-desc">Gambar di bawah ini merupakan ilustrasi tata letak acara sebagai gambaran suasana saat event berlangsung</p>
            </div>
            <div class="seating-layout-container">
                <div class="seating-image-wrapper">
                    <img src="assets/seating-layout.jpg" alt="Seating Layout" class="seating-image">
                </div>
            </div>
        </section>

        <!-- INFO SECTION -->
        <section class="info-section">
            <div class="section-header">
                <div class="section-label">Cara Pemesanan</div>
                <h2 class="section-title">Tiga Langkah Mudah</h2>
            </div>
            <div class="info-cards">
                <div class="info-card">
                    <div class="info-number">1</div>
                    <h3 class="info-title">Pilih Tiket</h3>
                    <p class="info-desc">Klik tombol "Pesan Sekarang" untuk memulai</p>
                </div>
                <div class="info-card">
                    <div class="info-number">2</div>
                    <h3 class="info-title">Pilih Tempat Duduk</h3>
                    <p class="info-desc">Pilih tempat duduk sesuai keinginan Anda</p>
                </div>
                <div class="info-card">
                    <div class="info-number">3</div>
                    <h3 class="info-title">Selesaikan Pembayaran</h3>
                    <p class="info-desc">Lengkapi data diri dan upload bukti pembayaran</p>
                </div>
            </div>
        </section>

        <!-- TICKET SECTION -->
        <section class="tickets-section">
            <div class="single-ticket-container" id="singleTicketContainer">
                <!-- Diisi oleh main.js -->
            </div>
        </section>

    </div>

    <!-- Config dari PHP → JS -->
    <?= jsConfig($ticketPrices, $ticketConfig) ?>

    <!-- ✅ SEMUA JS INLINE — tidak ada request eksternal -->
    <?= jsSeatStatus($seatStatus) ?>
    <script>
<?= $jsAll ?>
    </script>
    <script>
(function() {
    const btn = document.querySelector('.btn-home');
    const hero = document.querySelector('.hero-section');

    function updateBtnColor() {
        const heroBottom = hero.getBoundingClientRect().bottom;
        if (heroBottom <= 60) {
            // Sudah keluar dari hero, background putih
            btn.style.color = '#1a1a1a';
            btn.style.borderColor = 'rgba(0, 0, 0, 0.2)';
            btn.style.background = 'rgba(255, 255, 255, 0.85)';
        } else {
            // Masih di hero, background gelap
            btn.style.color = '#ffffff';
            btn.style.borderColor = 'rgba(255, 255, 255, 0.3)';
            btn.style.background = 'rgba(255, 255, 255, 0.15)';
        }
    }

    window.addEventListener('scroll', updateBtnColor, { passive: true });
    updateBtnColor(); // jalankan sekali saat load
})();
    </script>
</body>
</html>