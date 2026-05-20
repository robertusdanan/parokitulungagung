<?php
require_once __DIR__ . '/../includes/functions.php';

// ── SEO ─────────────────────────────────────────────────────────────────
$seo = [
    'title'       => 'Kontak – Paroki Santa Maria Tidak Bernoda Asal Tulungagung',
    'description' => 'Hubungi Gereja Katolik Paroki Tulungagung. Alamat, nomor telepon, jadwal sekretariat, dan formulir pesan tersedia di sini.',
    'canonical'   => 'https://www.parokitulungagung.org/kontak',
    'type'        => 'website',
    'keywords'    => 'kontak paroki tulungagung, hubungi gereja katolik tulungagung, sekretariat paroki smdtba',
];

$breadcrumbs = [
    ['name' => 'Beranda', 'url' => 'https://www.parokitulungagung.org'],
    ['name' => 'Kontak'],
];

$extraCss = [];

// ── ContactPage Schema ────────────────────────────────────────────────
$_contactPageSchema = json_encode([
    '@context' => 'https://schema.org',
    '@type'    => 'ContactPage',
    '@id'      => 'https://www.parokitulungagung.org/kontak#contactpage',
    'name'     => 'Kontak Paroki Santa Maria Dengan Tidak Bernoda Asal Tulungagung',
    'url'      => 'https://www.parokitulungagung.org/kontak',
    'description' => 'Hubungi Gereja Katolik Paroki Santa Maria Dengan Tidak Bernoda Asal (SMDTBA) Tulungagung. Tersedia alamat, nomor telepon, jadwal sekretariat, dan formulir pesan.',
    'isPartOf' => ['@id' => 'https://www.parokitulungagung.org/#website'],
    'about'    => ['@id' => 'https://www.parokitulungagung.org/#organization'],
    'mainEntity' => [
        '@type'     => 'Church',
        '@id'       => 'https://www.parokitulungagung.org/#organization',
        'name'      => 'Paroki Santa Maria Dengan Tidak Bernoda Asal Tulungagung',
        'telephone' => '+628563678844',
        'address'   => [
            '@type'           => 'PostalAddress',
            'streetAddress'   => 'Jl. Ahmad Yani Tim. Gg. IV No.1, Bago',
            'addressLocality' => 'Tulungagung',
            'addressRegion'   => 'Jawa Timur',
            'postalCode'      => '66218',
            'addressCountry'  => 'ID',
        ],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?php include __DIR__ . '/seo_head.php'; ?>

  <style>
  /* ── Font (sama dengan project) ── */
  /* Font sudah di-self-host via /css/fonts.css — tidak perlu @import Google Fonts */

  /* ── Reset & Base ── */
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  /* ── Hero ── */
  .kontak-hero {
    position: relative;
    background: #1a1208;
    padding: 72px 24px 64px;
    text-align: center;
    overflow: hidden;
  }
  .kontak-hero::before {
    content: '';
    position: absolute;
    inset: 0;
    background:
      radial-gradient(ellipse 60% 40% at 50% 0%, rgba(201,169,110,0.12) 0%, transparent 70%);
  }
  .kontak-hero-eyebrow {
    font-family: 'DM Sans', sans-serif;
    font-size: 11px;
    font-weight: 400;
    letter-spacing: 0.24em;
    text-transform: uppercase;
    color: #c9a96e;
    position: relative;
    margin-bottom: 16px;
  }
  .kontak-hero h1 {
    font-family: 'Playfair Display', serif;
    font-size: clamp(32px, 5vw, 52px);
    font-weight: 600;
    color: #e8dcc8;
    letter-spacing: 0.04em;
    position: relative;
    line-height: 1.15;
  }
  .kontak-hero-sub {
    font-family: 'DM Sans', sans-serif;
    font-size: 15px;
    font-weight: 300;
    color: #7a6a52;
    position: relative;
    margin-top: 14px;
    letter-spacing: 0.03em;
  }
  .kontak-hero-divider {
    display: flex;
    align-items: center;
    gap: 12px;
    justify-content: center;
    max-width: 320px;
    margin: 28px auto 0;
    position: relative;
  }
  .kontak-hero-divider .hline {
    flex: 1;
    height: 0.5px;
    background: linear-gradient(90deg, transparent, #c9a96e);
  }
  .kontak-hero-divider .hline.r {
    background: linear-gradient(90deg, #c9a96e, transparent);
  }

  /* ── Layout utama ── */
  .kontak-layout {
    display: grid;
    grid-template-columns: 1fr 1.5fr;
    gap: 0;
    max-width: 1040px;
    margin: 0 auto;
    padding: 56px 24px 80px;
  }

  /* ── Panel kiri: Info kontak ── */
  .kontak-info {
    padding-right: 48px;
    border-right: 0.5px solid #e8dcc8;
  }

  .kontak-section-label {
    font-family: 'DM Sans', sans-serif;
    font-size: 10px;
    font-weight: 500;
    letter-spacing: 0.22em;
    text-transform: uppercase;
    color: #c9a96e;
    margin-bottom: 20px;
  }

  .kontak-info-card {
    display: flex;
    align-items: flex-start;
    gap: 16px;
    margin-bottom: 28px;
  }

  .kontak-info-icon {
    width: 40px;
    height: 40px;
    flex-shrink: 0;
    border: 0.5px solid rgba(201,169,110,0.4);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(201,169,110,0.06);
  }

  .kontak-info-body h3 {
    font-family: 'Playfair Display', serif;
    font-size: 15px;
    font-weight: 600;
    color: #2c2416;
    margin-bottom: 4px;
  }

  .kontak-info-body p,
  .kontak-info-body a {
    font-family: 'DM Sans', sans-serif;
    font-size: 13.5px;
    font-weight: 300;
    color: #7a6a52;
    line-height: 1.6;
    text-decoration: none;
  }
  .kontak-info-body a:hover { color: #8b6a3e; }

  .kontak-divider {
    height: 0.5px;
    background: linear-gradient(90deg, #e8dcc8, transparent);
    margin: 28px 0;
  }

  /* Nama person di sekretariat */
  .kontak-person-name {
    font-family: 'Playfair Display', serif;
    font-size: 17px;
    font-weight: 600;
    color: #2c2416;
    margin-bottom: 18px;
    letter-spacing: 0.02em;
  }

  /* Jadwal sekretariat */
  .jadwal-row {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    gap: 12px;
    padding: 9px 0;
    border-bottom: 0.5px solid #f0ebe0;
    font-family: 'DM Sans', sans-serif;
    font-size: 13px;
  }
  .jadwal-row:last-child { border-bottom: none; }
  .jadwal-row span:first-child {
    color: #7a6a52;
    font-weight: 300;
    flex: 1;
    min-width: 0;
  }
  .jadwal-row span:last-child {
    color: #3d2e1a;
    font-weight: 400;
    font-family: 'Playfair Display', serif;
    font-size: 14px;
    white-space: nowrap;
    flex-shrink: 0;
  }
  .jadwal-row .badge-tutup {
    font-family: 'DM Sans', sans-serif;
    font-size: 10px;
    font-weight: 400;
    background: #fdf3f3;
    color: #b06060;
    border: 0.5px solid #e8c4c4;
    border-radius: 20px;
    padding: 2px 8px;
    letter-spacing: 0.04em;
    white-space: nowrap;
  }
  /* badge khusus libur (kuning) */
  .jadwal-row .badge-libur {
    font-family: 'DM Sans', sans-serif;
    font-size: 10px;
    font-weight: 400;
    background: #fdf8ee;
    color: #9a7c30;
    border: 0.5px solid #e8d898;
    border-radius: 20px;
    padding: 2px 8px;
    letter-spacing: 0.04em;
    white-space: nowrap;
  }

  /* Map embed */
  .kontak-map-wrap {
    margin-top: 28px;
    border-radius: 10px;
    overflow: hidden;
    border: 0.5px solid #e8dcc8;
  }
  .kontak-map-wrap iframe {
    display: block;
    width: 100%;
    height: 200px;
    border: none;
  }

  /* ── Panel kanan: Formulir ── */
  .kontak-form-wrap {
    padding-left: 48px;
  }

  .kontak-form-title {
    font-family: 'Playfair Display', serif;
    font-size: 22px;
    font-weight: 600;
    color: #2c2416;
    margin-bottom: 6px;
  }
  .kontak-form-desc {
    font-family: 'DM Sans', sans-serif;
    font-size: 13.5px;
    font-weight: 300;
    color: #9d8f7a;
    margin-bottom: 28px;
    line-height: 1.6;
  }

  /* Form fields */
  .form-group {
    margin-bottom: 18px;
  }
  .form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
  }
  .form-group label {
    display: block;
    font-family: 'DM Sans', sans-serif;
    font-size: 11px;
    font-weight: 500;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    color: #7a6a52;
    margin-bottom: 7px;
  }
  .form-group label .req {
    color: #c9a96e;
    margin-left: 2px;
  }
  .form-group input,
  .form-group select,
  .form-group textarea {
    width: 100%;
    padding: 11px 14px;
    font-family: 'DM Sans', sans-serif;
    font-size: 14px;
    font-weight: 300;
    color: #2c2416;
    background: #faf8f4;
    border: 0.5px solid #d4c9b0;
    border-radius: 6px;
    outline: none;
    transition: border-color 0.2s, box-shadow 0.2s;
    appearance: none;
    -webkit-appearance: none;
  }
  .form-group input:focus,
  .form-group select:focus,
  .form-group textarea:focus {
    border-color: #c9a96e;
    box-shadow: 0 0 0 3px rgba(201,169,110,0.12);
    background: #fff;
  }
  .form-group input.error,
  .form-group select.error,
  .form-group textarea.error {
    border-color: #d08080;
  }
  .form-group textarea {
    resize: vertical;
    min-height: 130px;
    line-height: 1.6;
  }
  .form-group select {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath d='M2 4l4 4 4-4' stroke='%23c9a96e' stroke-width='1.5' fill='none' stroke-linecap='round'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 14px center;
    padding-right: 36px;
    cursor: pointer;
  }

  /* Honeypot */
  .form-honey { display: none !important; }

  /* Karakter counter */
  .char-count {
    font-family: 'DM Sans', sans-serif;
    font-size: 11px;
    color: #b5a990;
    text-align: right;
    margin-top: 4px;
  }

  /* Tombol kirim */
  .btn-kirim {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 13px 32px;
    background: #1a1208;
    color: #e8dcc8;
    font-family: 'DM Sans', sans-serif;
    font-size: 13px;
    font-weight: 400;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    border: 0.5px solid #1a1208;
    border-radius: 4px;
    cursor: pointer;
    transition: background 0.2s, color 0.2s, border-color 0.2s;
    margin-top: 6px;
  }
  .btn-kirim:hover {
    background: #c9a96e;
    border-color: #c9a96e;
    color: #1a1208;
  }
  .btn-kirim:disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }
  .btn-kirim svg { transition: transform 0.2s; }
  .btn-kirim:hover svg { transform: translateX(3px); }

  /* Alert */
  .form-alert {
    display: none;
    border-radius: 6px;
    padding: 14px 16px;
    margin-bottom: 18px;
    font-family: 'DM Sans', sans-serif;
    font-size: 13.5px;
    font-weight: 300;
    line-height: 1.5;
  }
  .form-alert.success {
    background: #f1f9f4;
    border: 0.5px solid #a3d4b5;
    color: #2d6644;
  }
  .form-alert.error {
    background: #fdf3f3;
    border: 0.5px solid #e8c4c4;
    color: #8b3535;
  }
  .form-alert ul { margin: 6px 0 0 18px; }
  .form-alert li { margin-bottom: 2px; }

  /* Sukses state */
  .kontak-sukses {
    display: none;
    text-align: center;
    padding: 48px 24px;
  }
  .kontak-sukses svg { margin-bottom: 20px; }
  .kontak-sukses h2 {
    font-family: 'Playfair Display', serif;
    font-size: 24px;
    font-weight: 600;
    color: #2c2416;
    margin-bottom: 10px;
  }
  .kontak-sukses p {
    font-family: 'DM Sans', sans-serif;
    font-size: 14px;
    font-weight: 300;
    color: #7a6a52;
    line-height: 1.7;
  }

  /* ── Responsive ── */
  @media (max-width: 768px) {
    .kontak-layout {
      grid-template-columns: 1fr;
      padding: 40px 20px 60px;
    }
    .kontak-info {
      padding-right: 0;
      border-right: none;
      border-bottom: 0.5px solid #e8dcc8;
      padding-bottom: 36px;
      margin-bottom: 36px;
    }
    .kontak-form-wrap {
      padding-left: 0;
    }
    .form-row {
      grid-template-columns: 1fr;
    }
  }
  </style>

<!-- ContactPage Structured Data -->
<script type="application/ld+json">
<?= $_contactPageSchema ?>
</script>
</head>
<body>
<?php $headerTitle = 'Kontak'; include __DIR__ . '/page_header.php'; ?>

<main>

  <!-- Hero -->
  <section class="kontak-hero">
    <p class="kontak-hero-eyebrow">Paroki Tulungagung</p>
    <h1>Hubungi Kami</h1>
    <p class="kontak-hero-sub">Kami siap membantu Anda — sampaikan pesan, pertanyaan, atau kebutuhan rohani Anda.</p>
    <div class="kontak-hero-divider">
      <span class="hline"></span>
      <svg width="20" height="20" viewBox="0 0 28 28" fill="none">
        <rect x="12" y="2" width="4" height="24" rx="1" fill="#c9a96e"/>
        <rect x="4" y="10" width="20" height="4" rx="1" fill="#c9a96e"/>
      </svg>
      <span class="hline r"></span>
    </div>
  </section>

  <!-- Konten utama -->
  <div class="kontak-layout">

    <!-- Panel kiri: Informasi -->
    <aside class="kontak-info">

      <!-- ── Sekretariat ─────────────────────────────── -->
      <p class="kontak-section-label">Sekretariat Paroki</p>
      <p class="kontak-person-name">Edy Wandowo</p>

      <!-- Alamat -->
      <div class="kontak-info-card">
        <div class="kontak-info-icon">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#c9a96e" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/>
            <circle cx="12" cy="10" r="3"/>
          </svg>
        </div>
        <div class="kontak-info-body">
          <h3>Alamat</h3>
          <p>Jl. Ahmad Yani Tim. Gg. IV No.1<br>Bago, Tulungagung, Jawa Timur</p>
        </div>
      </div>

      <!-- Telepon Kantor -->
      <div class="kontak-info-card">
        <div class="kontak-info-icon">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#c9a96e" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.81a19.79 19.79 0 01-3.07-8.68A2 2 0 012 1h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.09 8.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/>
          </svg>
        </div>
        <div class="kontak-info-body">
          <h3>Telepon Kantor</h3>
          <a href="tel:+62355321727">(0355) 321727</a>
        </div>
      </div>

      <!-- WhatsApp Sekretariat -->
      <div class="kontak-info-card">
        <div class="kontak-info-icon">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="#c9a96e">
            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
          </svg>
        </div>
        <div class="kontak-info-body">
          <h3>WhatsApp</h3>
          <a href="https://wa.me/628563678844" target="_blank" rel="noopener">08563678844</a>
        </div>
      </div>

      <!-- Email Sekretariat -->
      <div class="kontak-info-card">
        <div class="kontak-info-icon">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#c9a96e" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
            <polyline points="22,6 12,13 2,6"/>
          </svg>
        </div>
        <div class="kontak-info-body">
          <h3>Email</h3>
          <a href="mailto:sanmardtba@gmail.com">sanmardtba@gmail.com</a>
        </div>
      </div>

      <div class="kontak-divider"></div>

      <!-- ── Jam Sekretariat ─────────────────────────── -->
      <p class="kontak-section-label">Jam Sekretariat</p>

      <div class="jadwal-row">
        <span>Buka</span>
        <span>08.00 – 14.00</span>
      </div>
      <div class="jadwal-row">
        <span>Rabu</span>
        <span class="badge-libur">Libur</span>
      </div>


      <div class="kontak-divider"></div>

      <!-- ── Komsos Paroki ───────────────────────────── -->
      <p class="kontak-person-name">Komsos Paroki</p>

      <!-- WA Komsos -->
      <div class="kontak-info-card">
        <div class="kontak-info-icon">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="#c9a96e">
            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
          </svg>
        </div>
        <div class="kontak-info-body">
          <h3>WhatsApp</h3>
          <a href="https://wa.me/6285183068895" target="_blank" rel="noopener">085183068895</a>
        </div>
      </div>

      <!-- Instagram Komsos -->
      <div class="kontak-info-card">
        <div class="kontak-info-icon">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#c9a96e" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <rect x="2" y="2" width="20" height="20" rx="5" ry="5"/>
            <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/>
            <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/>
          </svg>
        </div>
        <div class="kontak-info-body">
          <h3>Instagram</h3>
          <a href="https://www.instagram.com/komsosparokitulungagung/" target="_blank" rel="noopener">@komsosparokitulungagung</a>
        </div>
      </div>

      <!-- Email Komsos -->
      <div class="kontak-info-card">
        <div class="kontak-info-icon">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#c9a96e" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
            <polyline points="22,6 12,13 2,6"/>
          </svg>
        </div>
        <div class="kontak-info-body">
          <h3>Email</h3>
          <a href="mailto:support@parokitulungagung.org">support@parokitulungagung.org</a>
        </div>
      </div>

      <!-- Map embed -->
      <div class="kontak-map-wrap">
        <iframe
          src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3950.3507707877948!2d111.90509109999999!3d-8.065658100000002!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e78e2e039c8b6f5%3A0x596dee2ec5880aea!2sGereja%20Katolik%20Santa%20Maria%20Dengan%20Tidak%20Bernoda%20Asal%2C%20Tulungagung!5e0!3m2!1sen!2sid!4v1777537555555!5m2!1sen!2sid"
          title="Lokasi Paroki Tulungagung"
          loading="lazy"
          allowfullscreen
          referrerpolicy="no-referrer-when-downgrade">
        </iframe>
      </div>

    </aside>

    <!-- Panel kanan: Formulir -->
    <section class="kontak-form-wrap">
      <h2 class="kontak-form-title">Kirim Pesan</h2>
      <p class="kontak-form-desc">
        Isi formulir di bawah ini, lalu aplikasi email Anda akan terbuka otomatis dengan isi pesan yang sudah terisi.
        Cukup klik <strong>Kirim</strong> di aplikasi email Anda.
      </p>

      <!-- Alert -->
      <div class="form-alert" id="formAlert" role="alert"></div>

      <!-- Form sukses -->
      <div class="kontak-sukses" id="kontakSukses">
        <svg width="52" height="52" viewBox="0 0 52 52" fill="none">
          <circle cx="26" cy="26" r="25" stroke="#c9a96e" stroke-width="1"/>
          <path d="M15 26l8 8 14-14" stroke="#c9a96e" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <h2>Aplikasi Email Terbuka</h2>
        <p>Draft pesan sudah siap di aplikasi email Anda.<br>
        Silakan klik <strong>Kirim</strong> untuk mengirimkan pesan.<br>
        Tim sekretariat akan segera merespons.</p>
        <p style="margin-top:16px;font-style:italic;color:#b5a990;">Berkah Dalem ✝</p>
      </div>

      <!-- Form utama -->
      <form id="kontakForm" novalidate>
        <!-- Honeypot -->
        <div class="form-honey" aria-hidden="true">
          <input type="text" name="website" tabindex="-1" autocomplete="off">
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="nama">Nama Lengkap <span class="req">*</span></label>
            <input type="text" id="nama" name="nama" placeholder="Nama Anda" maxlength="100" required>
          </div>
          <div class="form-group">
            <label for="email">Alamat Email <span class="req">*</span></label>
            <input type="email" id="email" name="email" placeholder="email@anda.com" maxlength="150" required>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="telepon">No. Telepon <span style="color:#b5a990;font-size:10px;">(Opsional)</span></label>
            <input type="tel" id="telepon" name="telepon" placeholder="08xx-xxxx-xxxx" maxlength="20">
          </div>
          <div class="form-group">
            <label for="subjek">Keperluan <span class="req">*</span></label>
            <select id="subjek" name="subjek" required>
              <option value="" disabled selected>Pilih keperluan&hellip;</option>
              <optgroup label="Pelayanan Sakramen">
                <option value="Permohonan Baptis">Permohonan Baptis</option>
                <option value="Permohonan Pernikahan">Permohonan Pernikahan</option>
                <option value="Permohonan Krisma">Permohonan Krisma</option>
                <option value="Surat Keterangan Baptis">Surat Keterangan Baptis</option>
              </optgroup>
              <optgroup label="Informasi">
                <option value="Jadwal Misa & Kegiatan">Jadwal Misa &amp; Kegiatan</option>
                <option value="Informasi Wilayah/Lingkungan">Informasi Wilayah / Lingkungan</option>
                <option value="Informasi UMKM Umat">Informasi UMKM Umat</option>
              </optgroup>
              <optgroup label="Lainnya">
                <option value="Pengaduan & Saran">Pengaduan &amp; Saran</option>
                <option value="Donasi & Persembahan">Donasi &amp; Persembahan</option>
                <option value="Lainnya">Lainnya</option>
              </optgroup>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label for="pesan">Pesan <span class="req">*</span></label>
          <textarea id="pesan" name="pesan" placeholder="Tuliskan pesan Anda di sini…" maxlength="1000" required></textarea>
          <p class="char-count"><span id="charCount">0</span> / 1000</p>
        </div>

        <button type="submit" class="btn-kirim" id="btnKirim">
          <span id="btnLabel">Kirim Pesan</span>
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <line x1="5" y1="12" x2="19" y2="12"/>
            <polyline points="12 5 19 12 12 19"/>
          </svg>
        </button>
      </form>
    </section>

  </div>
</main>


<script>
(function () {
  const ADMIN_EMAIL = 'sanmardtba@gmail.com';

  const form    = document.getElementById('kontakForm');
  const alertEl = document.getElementById('formAlert');
  const sukses  = document.getElementById('kontakSukses');
  const btn     = document.getElementById('btnKirim');
  const label   = document.getElementById('btnLabel');
  const pesan   = document.getElementById('pesan');
  const counter = document.getElementById('charCount');

  // Char counter
  pesan.addEventListener('input', () => {
    counter.textContent = pesan.value.length;
  });

  // Tampilkan alert
  function showAlert(type, html) {
    alertEl.className     = 'form-alert ' + type;
    alertEl.innerHTML     = html;
    alertEl.style.display = 'block';
    alertEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }

  // Validasi
  function validate() {
    const errs   = [];
    const fields = ['nama', 'email', 'subjek', 'pesan'];
    fields.forEach(id => document.getElementById(id).classList.remove('error'));

    const nama  = form.nama.value.trim();
    const email = form.email.value.trim();
    const subj  = form.subjek.value;
    const msg   = form.pesan.value.trim();

    if (nama.length < 2)
      { errs.push('Nama minimal 2 karakter.');   document.getElementById('nama').classList.add('error'); }
    if (!/\S+@\S+\.\S+/.test(email))
      { errs.push('Email tidak valid.');          document.getElementById('email').classList.add('error'); }
    if (!subj)
      { errs.push('Pilih keperluan.');            document.getElementById('subjek').classList.add('error'); }
    if (msg.length < 10)
      { errs.push('Pesan minimal 10 karakter.');  document.getElementById('pesan').classList.add('error'); }

    return errs;
  }

  // Submit → mailto:
  form.addEventListener('submit', function (e) {
    e.preventDefault();
    alertEl.style.display = 'none';

    const errs = validate();
    if (errs.length) {
      showAlert('error', '<strong>Mohon perbaiki:</strong><ul>' +
        errs.map(x => '<li>' + x + '</li>').join('') + '</ul>');
      return;
    }

    const nama    = form.nama.value.trim();
    const email   = form.email.value.trim();
    const telepon = form.telepon.value.trim();
    const subjek  = form.subjek.value;
    const msg     = form.pesan.value.trim();

    // Susun subject & body email
    const subject = '[Kontak Website] ' + subjek;

    const bodyLines = [
      'Nama     : ' + nama,
      'Email    : ' + email,
      telepon ? 'Telepon  : ' + telepon : null,
      'Keperluan: ' + subjek,
      '',
      '--- Pesan ---',
      msg,
      '',
      '---',
      'Pesan ini dikirim melalui formulir kontak parokitulungagung.org',
    ];

    const body = bodyLines.filter(l => l !== null).join('\n');

    // Buka aplikasi email pengguna
    const mailto = 'mailto:' + ADMIN_EMAIL
      + '?subject=' + encodeURIComponent(subject)
      + '&body='    + encodeURIComponent(body);

    window.location.href = mailto;

    // Tampilkan sukses setelah jeda singkat (beri waktu browser membuka email)
    btn.disabled      = true;
    label.textContent = 'Membuka email\u2026';

    setTimeout(function () {
      form.style.display   = 'none';
      sukses.style.display = 'block';
      sukses.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }, 800);
  });
})();
</script>

<?php include __DIR__ . '/footer.php'; ?>
<script>
window.togglemenudiv = function () {
  const div = document.getElementById('divmenu');
  if (!div) return;
  div.style.display = (div.style.display === 'none' || div.style.display === '') ? 'block' : 'none';
};

/* ============================================================
   PHOTO MODAL
   ============================================================ */
window.ShowPhotoBox = function (txt, fotopath, title, subtxt) {
  const set = (id, val) => { const el = document.getElementById(id); if (el) el.innerHTML = val || ''; };
  set('boxModalTitle',   title);
  set('boxModalText',    txt);
  set('boxModalSubText', subtxt);
  const img   = document.getElementById('boxModalImage');
  const modal = document.getElementById('boxModal');
  if (img)   img.src = fotopath;
  if (modal) modal.style.display = 'block';
};

/* ============================================================
   ABOUT MODAL
   ============================================================ */
window.ShowAboutBox = function () {
  const modal = document.getElementById('aboutModal');
  if (modal) modal.style.display = 'block';
};

/* ============================================================
   TAB SYSTEM – generic, dipakai di semua halaman bertab
   Panggil initTabs(tabsId, contentsId) setelah DOM siap
   ============================================================ */
function initTabs(tabsId, contentsId) {
  const tabsEl    = document.getElementById(tabsId);
  const contentsEl = document.getElementById(contentsId) || document.querySelector('.tabcontents');
  if (!tabsEl) return;

  const tabs     = tabsEl.querySelectorAll('a');
  const contents = contentsEl ? contentsEl.querySelectorAll(':scope > div') : [];

  function switchTab(targetId) {
    tabs.forEach(t => t.parentElement.classList.remove('selected'));
    contents.forEach(c => c.classList.remove('active'));
    tabs.forEach(t => {
      if (t.getAttribute('href') === '#' + targetId) t.parentElement.classList.add('selected');
    });
    const target = document.getElementById(targetId);
    if (target) target.classList.add('active');
  }

  tabs.forEach(tab => {
    tab.addEventListener('click', function (e) {
      e.preventDefault();
      switchTab(this.getAttribute('href').substring(1));
    });
  });

  // Aktifkan tab pertama
  if (tabs.length > 0) switchTab(tabs[0].getAttribute('href').substring(1));
}

/* ============================================================
   ACCORDION (galeri bulan, DPP bidang)
   ============================================================ */
function initAccordions() {
  document.querySelectorAll('.galeri-accordion-header').forEach(function (header) {
    header.addEventListener('click', function () {
      const acc = this.closest('.galeri-accordion');
      if (acc) acc.classList.toggle('open');
    });
  });
}

/* ============================================================
   SCROLL BUTTONS (untuk tabs-tahun galeri)
   ============================================================ */
function initScrollButtons() {
  const tabsEl  = document.getElementById('tabs-tahun');
  const btnLeft  = document.getElementById('btnScrollLeft');
  const btnRight = document.getElementById('btnScrollRight');
  if (!tabsEl || !btnLeft || !btnRight) return;

  function update() {
    btnLeft.style.display  = tabsEl.scrollLeft > 0 ? 'block' : 'none';
    const maxScroll        = tabsEl.scrollWidth - tabsEl.clientWidth;
    btnRight.style.display = tabsEl.scrollLeft < maxScroll - 1 ? 'block' : 'none';
  }

  btnLeft.addEventListener('click',  () => tabsEl.scrollBy({ left: -150, behavior: 'smooth' }));
  btnRight.addEventListener('click', () => tabsEl.scrollBy({ left:  150, behavior: 'smooth' }));
  tabsEl.addEventListener('scroll', update);
  window.addEventListener('resize', update);
  setTimeout(update, 100);
}

/* ============================================================
   FILTER ASISTEN IMAM by wilayah
   ============================================================ */
window.filterByWilayah = function (value) {
  document.querySelectorAll('#konten-asistenimam .profile-item').forEach(function (card) {
    // .profile-item memakai display:flex — jangan override ke 'block'
    card.style.display = (value === 'all' || card.getAttribute('data-wilayah') === value) ? 'flex' : 'none';
  });
};

/* ============================================================
   W3 TABS (agenda – style lama)
   ============================================================ */
window.openTab = function (evt, tabName) {
  document.querySelectorAll('.tabcontent').forEach(el => el.style.display = 'none');
  document.querySelectorAll('.tablink').forEach(el => el.classList.remove('active-tab'));
  const tab = document.getElementById(tabName);
  if (tab) tab.style.display = 'block';
  if (evt && evt.currentTarget) evt.currentTarget.classList.add('active-tab');
};

/* ============================================================
   KONTRIBUTOR — redirect ke halaman registrasi
   ============================================================ */
window.openWhatsApp = function () {
  // Dialihkan ke halaman registrasi (tidak lagi ke WhatsApp)
  window.location.href = '/admin/register.php';
};

/* ============================================================
   KEYBOARD NAVIGATION MENU
   ============================================================ */
function initMenuKeyboard() {
  document.querySelectorAll('.divtombol').forEach(function (item) {
    item.addEventListener('keypress', function (e) {
      if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); this.click(); }
    });
  });
}

/* ============================================================
   LOADING BAR – utilities
   Topbar muncul otomatis dari HTML (class="indeterminate").
   PHP sudah selesai render saat JS ini jalan → langsung selesaikan.
   Untuk iframe: topbar menunggu sampai iframe onload.
   ============================================================ */

function _fillBar(percent) {
  var bar = document.getElementById('progressBar');
  if (!bar) return;
  bar.classList.remove('indeterminate');
  bar.style.width = percent + '%';
}

function _setStatus(text) {
  var el = document.getElementById('loadingStatusText');
  if (el) el.textContent = text;
}

function _hideLoader() {
  var topbar = document.getElementById('contentTopbar');
  var status = document.getElementById('loadingStatus');
  var bar    = document.getElementById('progressBar');
  if (bar)    { bar.classList.remove('indeterminate'); bar.style.width = '100%'; }
  setTimeout(function () {
    if (topbar) topbar.classList.add('hidden');
    if (status) status.classList.add('hidden');
  }, 400);
}

// Expose supaya iframe onload bisa panggil
window._hidePageLoader = _hideLoader;

/* ============================================================
   NAVIGATION LOADER
   Saat user klik link → tampilkan indikator di halaman sekarang
   (mengisi jeda saat PHP sedang memproses request berikutnya)
   ============================================================ */
(function () {
  var NAV_BAR_ID  = 'nav-progress-bar';
  var NAV_WRAP_ID = 'nav-progress-wrap';

  function createNavBar() {
    if (document.getElementById(NAV_BAR_ID)) return;
    var wrap = document.createElement('div');
    wrap.id  = NAV_WRAP_ID;
    wrap.style.cssText = [
      'position:fixed', 'top:0', 'left:0', 'right:0', 'z-index:9999',
      'height:3px', 'pointer-events:none', 'opacity:0',
      'transition:opacity 0.2s ease'
    ].join(';');
    var bar = document.createElement('div');
    bar.id  = NAV_BAR_ID;
    bar.style.cssText = [
      'height:100%', 'width:0%',
      'background:linear-gradient(90deg,#5b2c6f,#b8860b)',
      'box-shadow:0 0 8px rgba(184,134,11,0.6)',
      'border-radius:2px',
      'transition:width 0.3s ease'
    ].join(';');
    wrap.appendChild(bar);
    document.body.appendChild(wrap);
  }

  function startNavProgress() {
    createNavBar();
    var wrap = document.getElementById(NAV_WRAP_ID);
    var bar  = document.getElementById(NAV_BAR_ID);
    if (!wrap || !bar) return;
    wrap.style.opacity = '1';
    bar.style.width    = '0%';
    // Simulasi progres: cepat ke 70%, lalu tahan menunggu server
    setTimeout(function () { bar.style.width = '70%'; }, 50);
    setTimeout(function () { bar.style.width = '85%'; }, 800);
  }

  document.addEventListener('click', function (e) {
    var link = e.target.closest('a[href]');
    if (!link) return;
    var href = link.getAttribute('href');
    // Hanya untuk navigasi internal, bukan blank/hash/external
    if (!href || href.startsWith('#') || href.startsWith('http') ||
        href.startsWith('mailto') || link.target === '_blank') return;
    startNavProgress();
  });
})();


  // ── Bind tombol menu ────────────────────────────────────────
  var btnMenu = document.getElementById('btnmenu');
  if (btnMenu) btnMenu.addEventListener('click', window.togglemenudiv);

  // ── Tutup photo modal ───────────────────────────────────────
  var boxModal  = document.getElementById('boxModal');
  var boxClose  = document.getElementById('boxModalClose');
  if (boxClose && boxModal) boxClose.addEventListener('click', function () { boxModal.style.display = 'none'; });

  // ── Tutup about modal ───────────────────────────────────────
  var aboutModal = document.getElementById('aboutModal');
  var aboutClose = aboutModal ? aboutModal.querySelector('.close') : null;
  if (aboutClose && aboutModal) aboutClose.addEventListener('click', function () { aboutModal.style.display = 'none'; });

  // ── Klik di luar modal → tutup ──────────────────────────────
  window.addEventListener('click', function (e) {
    if (e.target === boxModal)   boxModal.style.display   = 'none';
    if (e.target === aboutModal) aboutModal.style.display = 'none';
  });

  // ── LOADING BAR: halaman sudah selesai di-render PHP ────────
  var isIframePage = !!document.getElementById('framecontent');

  if (isIframePage) {
    // Iframe masih load async → topbar tetap sampai iframe.onload
    _fillBar(40);
    _setStatus('Memuat konten');
    // Fallback: jika iframe tidak memanggil _hidePageLoader dalam 12 detik
    setTimeout(function () {
      if (!window._iframeLoaded) _hideLoader();
    }, 12000);
  } else {
    // Semua data sudah ada → langsung selesaikan bar
    _fillBar(80);
    _setStatus('Selesai');
    setTimeout(_hideLoader, 200);
  }
// ===================== IMAGE SYSTEM UPGRADE =====================
document.addEventListener("DOMContentLoaded", function () {
  // ── Interaktivitas ──────────────────────────────────────────
  initAccordions();
  initMenuKeyboard();

  // ── Tab systems per halaman ─────────────────────────────────
  if (document.getElementById('tabs-tahun')) {
    initTabs('tabs-tahun', 'tabcontents-dinamis');
    initScrollButtons();
  }
  if (document.getElementById('tabs-wilayah')) {
    initTabs('tabs-wilayah', 'tabcontents-wilayah');
  }
  if (document.getElementById('tabs-dpp')) {
    initTabs('tabs-dpp', 'tabcontents-dpp');
  }
  if (document.getElementById('tabs-kategorial')) {
    initTabs('tabs-kategorial', null);
  }
});

/* ═══════════════════════════════════════════════════════
   HERO BACKGROUND ROTATOR — Homepage only
   Hanya desktop; mobile pakai solid color
   ═══════════════════════════════════════════════════════ */
(function () {
  var hero = document.querySelector('.hero-paroki');
  if (!hero) return; // bukan homepage, langsung keluar
  if (window.innerWidth <= 600) return; // mobile: skip

  var images = [
    '/img/gereja/exterior-blank.webp',
    '/img/gereja/interiorwide.webp',
  ];
  var idx = 0, cachedImg = null, heroTimer = null, heroVisible = true;

  function setBg() {
    if (cachedImg) { cachedImg.onload = null; cachedImg = null; }
    var img = new Image();
    cachedImg = img;
    img.src = images[idx];
    img.onload = function () {
      if (img === cachedImg) hero.style.backgroundImage = "url('" + images[idx] + "')";
    };
  }

  function shouldRun() { return heroVisible && !document.hidden; }

  function startHeroRotation() {
    clearInterval(heroTimer);
    if (!shouldRun()) return;
    heroTimer = setInterval(function () { idx = (idx + 1) % images.length; setBg(); }, 6000);
  }

  function stopHeroRotation() { clearInterval(heroTimer); }

  document.addEventListener('visibilitychange', function () {
    document.hidden ? stopHeroRotation() : startHeroRotation();
  });

  if ('IntersectionObserver' in window) {
    var io = new IntersectionObserver(function (entries) {
      heroVisible = entries[0].isIntersecting;
      heroVisible ? startHeroRotation() : stopHeroRotation();
    }, { threshold: 0.1 });
    io.observe(hero);
  }

  // Gambar pertama sudah ada di CSS → langsung mulai timer rotasi
  startHeroRotation();
})();

/* ═══════════════════════════════════════════════════════
   ARTIKEL SLIDER — Homepage only
   ═══════════════════════════════════════════════════════ */
(function () {
  var slider = document.getElementById('artikelSlider');
  if (!slider) return; // bukan homepage

  var slides   = slider.querySelectorAll('.artikel-slide');
  var dots     = document.querySelectorAll('.dot');
  var btnPrev  = slider.querySelector('.slider-btn.prev');
  var btnNext  = slider.querySelector('.slider-btn.next');
  var progress = document.getElementById('sliderProgress');

  if (!slides.length) return;

  var DURATION = 5000, current = 0, timer = null, sliderVisible = true, hovered = false;

  function show(i) {
    slides[current].style.willChange = 'auto';
    slides.forEach(function (s, x) {
      s.classList.toggle('active', x === i);
      s.tabIndex = x === i ? 0 : -1;
    });
    dots.forEach(function (d, x) { d.classList.toggle('active', x === i); });
    current = i;
    slides[current].style.willChange = 'opacity';
    startProgress();
  }

  function shouldRun() { return sliderVisible && !document.hidden && !hovered; }

  function startAutoplay() {
    clearInterval(timer);
    if (!shouldRun()) return;
    timer = setInterval(function () { show((current + 1) % slides.length); }, DURATION);
  }

  function stopAutoplay() { clearInterval(timer); }

  function startProgress() {
    if (!progress) return;
    progress.style.transition = 'none';
    progress.style.width = '0%';
    progress.offsetWidth; // force reflow
    progress.style.transition = 'width ' + DURATION + 'ms linear';
    progress.style.width = '100%';
  }

  if (btnPrev) btnPrev.addEventListener('click', function () { show((current - 1 + slides.length) % slides.length); startAutoplay(); });
  if (btnNext) btnNext.addEventListener('click', function () { show((current + 1) % slides.length); startAutoplay(); });

  dots.forEach(function (d, x) { d.addEventListener('click', function () { show(x); startAutoplay(); }); });

  var touchStartX = 0;
  slider.addEventListener('touchstart', function (e) { touchStartX = e.changedTouches[0].clientX; }, { passive: true });
  slider.addEventListener('touchend', function (e) {
    var dx = e.changedTouches[0].clientX - touchStartX;
    if (Math.abs(dx) < 40) return;
    show(dx < 0 ? (current + 1) % slides.length : (current - 1 + slides.length) % slides.length);
    startAutoplay();
  }, { passive: true });

  slider.addEventListener('mouseenter', function () { hovered = true; stopAutoplay(); });
  slider.addEventListener('mouseleave', function () { hovered = false; startAutoplay(); });

  slider.addEventListener('keydown', function (e) {
    if (e.key === 'ArrowRight') { show((current + 1) % slides.length); startAutoplay(); }
    if (e.key === 'ArrowLeft')  { show((current - 1 + slides.length) % slides.length); startAutoplay(); }
  });

  document.addEventListener('visibilitychange', function () {
    document.hidden ? stopAutoplay() : startAutoplay();
  });

  if ('IntersectionObserver' in window) {
    var io2 = new IntersectionObserver(function (entries) {
      sliderVisible = entries[0].isIntersecting;
      sliderVisible ? startAutoplay() : stopAutoplay();
    }, { threshold: 0.25 });
    io2.observe(slider);
  }

  show(0);
  startAutoplay();
})();
</script>
</body>
</html>