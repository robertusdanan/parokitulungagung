<?php
require_once __DIR__ . '/../includes/functions.php';

$seo = [
    'title'       => 'Jadwal Misa Tulungagung – Gereja Katolik Paroki Tulungagung',
    'description' => 'Jadwal misa harian dan mingguan Gereja Katolik Tulungagung, Paroki Santa Maria Dengan Tidak Bernoda Asal (SMDTBA). Misa Senin–Kamis 05.30, Jumat & Sabtu 18.00, Minggu 07.00. Termasuk jadwal misa stasi.',
    'canonical'   => 'https://www.parokitulungagung.org/jadwal-misa',
    'type'        => 'website',
    'keywords'    => 'jadwal misa tulungagung, jadwal misa gereja katolik tulungagung, misa hari minggu tulungagung, gereja katolik tulungagung misa, jadwal misa smdtba, jadwal misa paroki tulungagung',
    'image'       => 'https://www.parokitulungagung.org/img/ogpreview/og-homepage.jpg',
];

$breadcrumbs = [
    ['name' => 'Beranda', 'url' => 'https://www.parokitulungagung.org'],
    ['name' => 'Jadwal Misa'],
];

$extraCss = [];

// Data jadwal (sumber tunggal — ubah di sini, update seluruh halaman)
$jadwalParoki = [
    ['hari' => 'Senin – Rabu',       'waktu' => '05.30', 'keterangan' => 'Misa Harian'],
    ['hari' => 'Kamis',              'waktu' => '05.30', 'keterangan' => 'Misa Harian (di Susteran)'],
    ['hari' => 'Jumat',              'waktu' => '18.00', 'keterangan' => 'Misa Sore'],
    ['hari' => 'Sabtu',              'waktu' => '18.00', 'keterangan' => 'Misa Sore'],
    ['hari' => 'Minggu',             'waktu' => '07.00', 'keterangan' => 'Misa Mingguan'],
];

$jadwalStasi = [
    ['nama' => 'Stasi Gembala yang Baik — Ngunut',  'waktu' => 'Sabtu, 18.00'],
    ['nama' => 'Stasi St. Maria — Rejotangan',       'waktu' => 'Sabtu, 16.00'],
    ['nama' => 'Stasi St. Maria — Trenggalek',       'waktu' => 'Minggu, 07.00'],
    ['nama' => 'Stasi Kalangbret',                   'waktu' => 'Minggu I & II, 10.00'],
    ['nama' => 'Stasi St. Maria — Dongko',           'waktu' => 'Minggu I & II, 11.00'],
    ['nama' => 'Stasi Sendang',                      'waktu' => 'Minggu II, 11.00'],
];

// ── FAQPage Schema — muncul sebagai rich snippet di Google ───────────
$_faqSchema = json_encode([
    '@context'   => 'https://schema.org',
    '@type'      => 'FAQPage',
    'mainEntity' => [
        [
            '@type'          => 'Question',
            'name'           => 'Jam berapa misa di Gereja Katolik Tulungagung?',
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text'  => 'Jadwal misa Gereja Katolik Tulungagung (Paroki SMDTBA): Senin–Rabu pukul 05.30, Kamis pukul 05.30 (di Susteran), Jumat & Sabtu pukul 18.00, Minggu pukul 07.00 WIB.',
            ],
        ],
        [
            '@type'          => 'Question',
            'name'           => 'Kapan jadwal misa Minggu di Gereja Tulungagung?',
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text'  => 'Misa Minggu di Paroki Santa Maria Dengan Tidak Bernoda Asal Tulungagung diadakan setiap Minggu pukul 07.00 WIB.',
            ],
        ],
        [
            '@type'          => 'Question',
            'name'           => 'Di mana alamat Gereja Katolik Tulungagung?',
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text'  => 'Gereja Katolik Paroki Santa Maria Dengan Tidak Bernoda Asal (SMDTBA) beralamat di Jl. Ahmad Yani Tim. Gg. IV No.1, Bago, Tulungagung, Jawa Timur 66218.',
            ],
        ],
        [
            '@type'          => 'Question',
            'name'           => 'Apakah ada misa di stasi-stasi Paroki Tulungagung?',
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text'  => 'Ya, ada misa di beberapa stasi: Stasi Ngunut (Sabtu 18.00), Stasi Rejotangan (Sabtu 16.00), Stasi Trenggalek (Minggu 07.00), Stasi Kalangbret (Minggu I & II, 10.00), Stasi Dongko (Minggu I & II, 11.00), dan Stasi Sendang (Minggu II, 11.00).',
            ],
        ],
        [
            '@type'          => 'Question',
            'name'           => 'Berapa nomor telepon Gereja Katolik Tulungagung?',
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text'  => 'Nomor telepon/WhatsApp Paroki Santa Maria Tidak Bernoda Asal Tulungagung adalah +62 856-3678-844.',
            ],
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
  /* ── Hero ── */
  .jm-hero {
    position: relative;
    background: #000;
    padding: 64px 24px 56px;
    text-align: center;
    overflow: hidden;
  }
  .jm-hero::before {
    content: '';
    position: absolute;
    inset: 0;
  }
  .jm-eyebrow {
    font-family: 'DM Sans', sans-serif;
    font-size: 11px;
    font-weight: 400;
    letter-spacing: 0.24em;
    text-transform: uppercase;
    color: #c9a96e;
    position: relative;
    margin-bottom: 14px;
  }
  .jm-hero h1 {
    font-family: 'Playfair Display', serif;
    font-size: clamp(28px, 5vw, 46px);
    font-weight: 600;
    color: #e8dcc8;
    letter-spacing: 0.04em;
    position: relative;
    line-height: 1.2;
    margin: 0;
  }
  .jm-hero-sub {
    font-family: 'DM Sans', sans-serif;
    font-size: 15px;
    font-weight: 300;
    color: #7a6a52;
    position: relative;
    margin-top: 12px;
  }
  .jm-hero-divider {
    display: flex;
    align-items: center;
    gap: 12px;
    justify-content: center;
    max-width: 280px;
    margin: 24px auto 0;
    position: relative;
  }
  .jm-hero-divider .hl  { flex:1; height:.5px; background: linear-gradient(90deg,transparent,#c9a96e); }
  .jm-hero-divider .hlr { flex:1; height:.5px; background: linear-gradient(90deg,#c9a96e,transparent); }

  /* ── Layout ── */
  .jm-wrap {
    max-width: 860px;
    margin: 0 auto;
    padding: 40px 20px 60px;
  }

  /* ── Section title ── */
  .jm-section-label {
    font-family: 'DM Sans', sans-serif;
    font-size: 10px;
    font-weight: 500;
    letter-spacing: 0.22em;
    text-transform: uppercase;
    color: #c9a96e;
    margin: 0 0 20px;
    display: flex;
    align-items: center;
    gap: 10px;
  }
  .jm-section-label::after {
    content: '';
    flex: 1;
    height: .5px;
    background: linear-gradient(90deg, rgba(201,169,110,0.35), transparent);
  }

  /* ── Jadwal table card ── */
  .jm-card {
    background: var(--color-background-secondary, #f9f7f4);
    border: .5px solid var(--color-border-tertiary, #e5e0d8);
    border-radius: 14px;
    overflow: hidden;
    margin-bottom: 32px;
  }
  .jm-card-header {
    padding: 18px 24px 14px;
    border-bottom: .5px solid var(--color-border-tertiary, #e5e0d8);
    display: flex;
    align-items: center;
    gap: 10px;
  }
  .jm-card-header-icon {
    width: 32px;
    height: 32px;
    background: rgba(201,169,110,0.12);
    border: .5px solid rgba(201,169,110,0.3);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }
  .jm-card-title {
    font-family: 'Playfair Display', serif;
    font-size: 17px;
    font-weight: 600;
    color: var(--color-text-primary, #1a1208);
    margin: 0;
  }
  .jm-card-subtitle {
    font-size: 12px;
    color: var(--color-text-secondary, #7a6a52);
    margin-top: 2px;
    font-weight: 300;
  }

  /* ── Jadwal rows ── */
  .jm-row {
    display: grid;
    grid-template-columns: 1fr auto;
    align-items: center;
    padding: 14px 24px;
    border-bottom: .5px solid var(--color-border-tertiary, #e5e0d8);
    gap: 12px;
  }
  .jm-row:last-child { border-bottom: none; }
  .jm-row-hari {
    font-family: 'DM Sans', sans-serif;
    font-size: 14.5px;
    font-weight: 400;
    color: var(--color-text-primary, #1a1208);
  }
  .jm-row-ket {
    font-size: 11.5px;
    color: var(--color-text-secondary, #9d8f7a);
    margin-top: 2px;
    font-weight: 300;
  }
  .jm-row-waktu {
    font-family: 'Playfair Display', serif;
    font-size: 22px;
    font-weight: 600;
    color: #c9a96e;
    white-space: nowrap;
    letter-spacing: .02em;
  }

  /* ── Stasi rows ── */
  .jm-stasi-row {
    display: grid;
    grid-template-columns: 1fr auto;
    align-items: center;
    padding: 13px 24px;
    border-bottom: .5px solid var(--color-border-tertiary, #e5e0d8);
    gap: 16px;
  }
  .jm-stasi-row:last-child { border-bottom: none; }
  .jm-stasi-nama {
    font-size: 14px;
    font-weight: 400;
    color: var(--color-text-primary, #1a1208);
    line-height: 1.4;
  }
  .jm-stasi-waktu {
    font-family: 'Playfair Display', serif;
    font-size: 14px;
    font-weight: 500;
    color: #c9a96e;
    text-align: right;
    white-space: nowrap;
  }

  /* ── Catatan ── */
  .jm-note {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 14px 24px;
    background: rgba(201,169,110,0.06);
    border-top: .5px solid rgba(201,169,110,0.2);
    font-size: 12.5px;
    color: var(--color-text-secondary, #7a6a52);
    font-weight: 300;
    line-height: 1.55;
  }
  .jm-note svg { flex-shrink: 0; margin-top: 1px; opacity: .7; }

  /* ── Info gereja ── */
  .jm-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 14px;
    margin-bottom: 32px;
  }
  .jm-info-item {
    background: var(--color-background-secondary, #f9f7f4);
    border: .5px solid var(--color-border-tertiary, #e5e0d8);
    border-radius: 12px;
    padding: 16px 18px;
    display: flex;
    gap: 12px;
    align-items: flex-start;
  }
  .jm-info-item svg { flex-shrink:0; margin-top:2px; }
  .jm-info-label {
    font-size: 10px;
    letter-spacing: .18em;
    text-transform: uppercase;
    color: #c9a96e;
    font-weight: 500;
    margin-bottom: 4px;
  }
  .jm-info-val {
    font-size: 13.5px;
    color: var(--color-text-primary, #1a1208);
    line-height: 1.5;
    font-weight: 400;
  }

  /* ── Konten deskriptif ── */
  .jm-prose {
    font-family: 'DM Sans', sans-serif;
    font-size: 15px;
    font-weight: 300;
    color: var(--color-text-secondary, #5a4f3e);
    line-height: 1.8;
    margin-bottom: 36px;
  }
  .jm-prose p { margin: 0 0 14px; }
  .jm-prose p:last-child { margin: 0; }

  /* ── Map ── */
  .jm-map-wrap {
    border-radius: 14px;
    overflow: hidden;
    border: .5px solid var(--color-border-tertiary, #e5e0d8);
    margin-bottom: 32px;
  }
  .jm-map-wrap iframe {
    display: block;
    width: 100%;
    height: 280px;
    border: none;
  }

  /* ── CTA ── */
  .jm-cta {
    text-align: center;
    padding: 32px 24px;
    background: linear-gradient(135deg, #1a1208, #2c1e10);
    border-radius: 16px;
    border: .5px solid rgba(201,169,110,0.25);
  }
  .jm-cta-title {
    font-family: 'Playfair Display', serif;
    font-size: 20px;
    font-weight: 600;
    color: #e8dcc8;
    margin: 0 0 8px;
  }
  .jm-cta-sub {
    font-size: 13.5px;
    color: #7a6a52;
    font-weight: 300;
    margin: 0 0 20px;
  }
  .jm-cta-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(201,169,110,0.12);
    border: .5px solid rgba(201,169,110,0.4);
    border-radius: 50px;
    padding: 10px 22px;
    color: #c9a96e;
    font-family: 'DM Sans', sans-serif;
    font-size: 13px;
    font-weight: 500;
    text-decoration: none;
    transition: background .25s, color .25s;
  }
  .jm-cta-btn:hover { background: rgba(201,169,110,0.22); color: #e8dcc8; }

  @media (max-width: 480px) {
    .jm-row, .jm-stasi-row { padding: 12px 16px; }
    .jm-card-header { padding: 14px 16px; }
    .jm-wrap { padding: 28px 14px 48px; }
    .jm-note { padding: 12px 16px; }
    .jm-info-grid { grid-template-columns: 1fr; }
  }
  </style>

  <!-- Schema Event per jadwal misa -->
  <script type="application/ld+json">
  [
    {
      "@context": "https://schema.org",
      "@type": "Event",
      "name": "Misa Harian Pagi – Paroki Tulungagung",
      "description": "Misa harian pagi setiap Senin hingga Kamis pukul 05.30 WIB di Gereja Katolik Paroki Santa Maria Dengan Tidak Bernoda Asal, Tulungagung.",
      "startDate": "2026-01-01T05:30:00+07:00",
      "eventSchedule": {
        "@type": "Schedule",
        "byDay": ["https://schema.org/Monday","https://schema.org/Tuesday","https://schema.org/Wednesday","https://schema.org/Thursday"],
        "startTime": "05:30",
        "endTime": "06:15",
        "scheduleTimezone": "Asia/Jakarta",
        "repeatFrequency": "P1W"
      },
      "location": {
        "@type": "Place",
        "name": "Gereja Katolik Santa Maria Dengan Tidak Bernoda Asal",
        "address": {
          "@type": "PostalAddress",
          "streetAddress": "Jl. Ahmad Yani Tim. Gg. IV No.1, Bago",
          "addressLocality": "Tulungagung",
          "addressRegion": "Jawa Timur",
          "postalCode": "66218",
          "addressCountry": "ID"
        },
        "geo": {"@type":"GeoCoordinates","latitude":-8.065445637787036,"longitude":111.9050910954876}
      },
      "organizer": {"@type":"Organization","name":"Paroki Tulungagung","url":"https://www.parokitulungagung.org"},
      "isAccessibleForFree": true,
      "eventAttendanceMode": "https://schema.org/OfflineEventAttendanceMode",
      "eventStatus": "https://schema.org/EventScheduled"
    },
    {
      "@context": "https://schema.org",
      "@type": "Event",
      "name": "Misa Sore Jumat & Sabtu – Paroki Tulungagung",
      "description": "Misa sore setiap Jumat dan Sabtu pukul 18.00 WIB di Gereja Katolik Paroki Tulungagung.",
      "startDate": "2026-01-02T18:00:00+07:00",
      "eventSchedule": {
        "@type": "Schedule",
        "byDay": ["https://schema.org/Friday","https://schema.org/Saturday"],
        "startTime": "18:00",
        "endTime": "19:00",
        "scheduleTimezone": "Asia/Jakarta",
        "repeatFrequency": "P1W"
      },
      "location": {
        "@type": "Place",
        "name": "Gereja Katolik Santa Maria Dengan Tidak Bernoda Asal",
        "address": {
          "@type": "PostalAddress",
          "streetAddress": "Jl. Ahmad Yani Tim. Gg. IV No.1, Bago",
          "addressLocality": "Tulungagung",
          "addressRegion": "Jawa Timur",
          "postalCode": "66218",
          "addressCountry": "ID"
        }
      },
      "organizer": {"@type":"Organization","name":"Paroki Tulungagung","url":"https://www.parokitulungagung.org"},
      "isAccessibleForFree": true,
      "eventAttendanceMode": "https://schema.org/OfflineEventAttendanceMode",
      "eventStatus": "https://schema.org/EventScheduled"
    },
    {
      "@context": "https://schema.org",
      "@type": "Event",
      "name": "Misa Minggu – Paroki Tulungagung",
      "description": "Misa Minggu pukul 07.00 WIB di Gereja Katolik Paroki Santa Maria Dengan Tidak Bernoda Asal Tulungagung.",
      "startDate": "2026-01-04T07:00:00+07:00",
      "eventSchedule": {
        "@type": "Schedule",
        "byDay": ["https://schema.org/Sunday"],
        "startTime": "07:00",
        "endTime": "08:00",
        "scheduleTimezone": "Asia/Jakarta",
        "repeatFrequency": "P1W"
      },
      "location": {
        "@type": "Place",
        "name": "Gereja Katolik Santa Maria Dengan Tidak Bernoda Asal",
        "address": {
          "@type": "PostalAddress",
          "streetAddress": "Jl. Ahmad Yani Tim. Gg. IV No.1, Bago",
          "addressLocality": "Tulungagung",
          "addressRegion": "Jawa Timur",
          "postalCode": "66218",
          "addressCountry": "ID"
        }
      },
      "organizer": {"@type":"Organization","name":"Paroki Tulungagung","url":"https://www.parokitulungagung.org"},
      "isAccessibleForFree": true,
      "eventAttendanceMode": "https://schema.org/OfflineEventAttendanceMode",
      "eventStatus": "https://schema.org/EventScheduled"
    }
  ]
  </script>

<!-- FAQPage Structured Data — Rich Snippet Google -->
<script type="application/ld+json">
<?= $_faqSchema ?>
</script>
</head>

<body>
<?php $headerTitle = ''; include __DIR__ . '/page_header.php'; ?>

<main>

<!-- HERO -->
<div class="jm-hero">
  <p class="jm-eyebrow">Gereja Katolik Tulungagung</p>
  <h1>Jadwal Misa</h1>
  <p class="jm-hero-sub">Paroki Santa Maria Dengan Tidak Bernoda Asal &middot; Tulungagung</p>
  <div class="jm-hero-divider">
    <span class="hl"></span>
    <svg width="18" height="18" viewBox="0 0 28 28" fill="none" aria-hidden="true">
      <rect x="12" y="2" width="4" height="24" rx="1" fill="#c9a96e"/>
      <rect x="4" y="10" width="20" height="4" rx="1" fill="#c9a96e"/>
    </svg>
    <span class="hlr"></span>
  </div>
</div>

<div class="jm-wrap">

  <!-- INFO GEREJA -->
  <div class="jm-info-grid">
    <div class="jm-info-item">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#c9a96e" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M21 10c0 7-9 13-9 13S3 17 3 10a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/>
      </svg>
      <div>
        <div class="jm-info-label">Alamat</div>
        <div class="jm-info-val">Jl. Ahmad Yani Tim. Gg. IV No.1, Bago, Tulungagung</div>
      </div>
    </div>
    <div class="jm-info-item">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#c9a96e" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.8a19.79 19.79 0 01-3.07-8.67A2 2 0 012 0h3a2 2 0 012 1.72c.127 1.01.36 2 .7 2.93a2 2 0 01-.45 2.11L6.09 7.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.93.34 1.92.573 2.93.7A2 2 0 0122 14.92v2z"/>
      </svg>
      <div>
        <div class="jm-info-label">Telepon</div>
        <div class="jm-info-val"><a href="tel:+62355321727" style="color:inherit;text-decoration:none;">(0355) 321727</a></div>
      </div>
    </div>
    <div class="jm-info-item">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#c9a96e" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
      </svg>
      <div>
        <div class="jm-info-label">Misa Harian</div>
        <div class="jm-info-val">Senin – Kamis, pukul 05.30 WIB</div>
      </div>
    </div>
  </div>

  <!-- JADWAL MISA PAROKI -->
  <p class="jm-section-label">Misa Paroki Induk</p>
  <div class="jm-card">
    <div class="jm-card-header">
      <div class="jm-card-header-icon">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#c9a96e" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
        </svg>
      </div>
      <div>
        <p class="jm-card-title">Jadwal Misa Mingguan</p>
        <div class="jm-card-subtitle">Gereja Paroki Tulungagung</div>
      </div>
    </div>

    <?php foreach ($jadwalParoki as $j): ?>
    <div class="jm-row">
      <div>
        <div class="jm-row-hari"><?= htmlspecialchars($j['hari']) ?></div>
        <div class="jm-row-ket"><?= htmlspecialchars($j['keterangan']) ?></div>
      </div>
      <div class="jm-row-waktu"><?= htmlspecialchars($j['waktu']) ?> <span style="font-size:13px;font-weight:300;color:#9d8f7a;">WIB</span></div>
    </div>
    <?php endforeach; ?>

    <div class="jm-note">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#c9a96e" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
      </svg>
      Jadwal misa dapat berubah pada hari raya liturgi, hari besar nasional, atau keperluan pastoral khusus. Pantau pengumuman terbaru di halaman <a href="/agenda" style="color:#c9a96e;text-decoration:none;border-bottom:.5px solid rgba(201,169,110,.4);">Info &amp; Agenda</a>.
    </div>
  </div>

  <!-- JADWAL MISA STASI -->
  <p class="jm-section-label">Misa Stasi</p>
  <div class="jm-card">
    <div class="jm-card-header">
      <div class="jm-card-header-icon">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#c9a96e" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>
        </svg>
      </div>
      <div>
        <p class="jm-card-title">Jadwal Misa Stasi</p>
        <div class="jm-card-subtitle">Wilayah pelayanan Paroki Tulungagung</div>
      </div>
    </div>

    <?php foreach ($jadwalStasi as $s): ?>
    <div class="jm-stasi-row">
      <div class="jm-stasi-nama"><?= htmlspecialchars($s['nama']) ?></div>
      <div class="jm-stasi-waktu"><?= htmlspecialchars($s['waktu']) ?></div>
    </div>
    <?php endforeach; ?>

    <div class="jm-note">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#c9a96e" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
      </svg>
      Jadwal misa stasi dapat berbeda setiap bulannya. Hubungi sekretariat paroki untuk informasi terkini.
    </div>
  </div>

  <!-- KONTEN DESKRIPTIF SEO -->
  <p class="jm-section-label">Tentang Gereja</p>
  <div class="jm-prose">
    <p>
      Paroki Santa Maria Dengan Tidak Bernoda Asal (SMDTBA) adalah gereja Katolik yang berlokasi di pusat Kota Tulungagung, Jawa Timur. Berada di bawah naungan Keuskupan Surabaya, paroki ini melayani umat Katolik di wilayah Tulungagung dan sekitarnya, termasuk sejumlah stasi yang tersebar di Ngunut, Rejotangan, Trenggalek, Kalangbret, Dongko, dan Sendang.
    </p>
    <p>
      Misa harian diselenggarakan setiap Senin hingga Kamis pukul 05.30 WIB, menjadi sarana doa pagi bagi umat yang ingin memulai hari dengan Ekaristi. Pada hari Jumat dan Sabtu, misa sore diselenggarakan pukul 18.00 WIB, sementara Misa Minggu dilaksanakan pukul 07.00 WIB untuk melayani seluruh umat paroki.
    </p>
    <p>
      Selain jadwal misa rutin, paroki juga menyelenggarakan misa khusus pada hari-hari raya liturgi seperti Natal, Paskah, Kenaikan Tuhan, dan hari raya Katolik lainnya. Informasi jadwal misa hari raya selalu diumumkan melalui halaman agenda resmi dan papan pengumuman gereja.
    </p>
    <p>
      Paroki terbuka untuk seluruh umat Katolik, tamu, maupun siapa saja yang ingin menghadiri perayaan Ekaristi. Tidak dipungut biaya untuk menghadiri misa. Untuk informasi lebih lanjut, silakan hubungi sekretariat paroki melalui telepon (0355) 321727 atau email <a href="mailto:support@parokitulungagung.org" style="color:#c9a96e;text-decoration:none;">support@parokitulungagung.org</a>.
    </p>
  </div>

  <!-- PETA -->
  <p class="jm-section-label">Lokasi Gereja</p>
  <div class="jm-map-wrap">
    <iframe
      src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3951.4!2d111.9042!3d-8.0664!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e78a0d9bbbbbbbb%3A0x0!2sGereja+Katolik+Santa+Maria+Dengan+Tidak+Bernoda+Asal+Tulungagung!5e0!3m2!1sid!2sid!4v1"
      loading="lazy"
      referrerpolicy="no-referrer-when-downgrade"
      title="Lokasi Gereja Katolik Santa Maria Dengan Tidak Bernoda Asal Tulungagung"
      aria-label="Peta lokasi Paroki Tulungagung">
    </iframe>
  </div>

  <!-- CTA -->
  <div class="jm-cta">
    <p class="jm-cta-title">Ada yang ingin ditanyakan?</p>
    <p class="jm-cta-sub">Hubungi sekretariat paroki untuk informasi misa, pastoral, dan kegiatan umat.</p>
    <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
      <a href="/kontak" class="jm-cta-btn">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/>
        </svg>
        Hubungi Kami
      </a>
      <a href="/agenda" class="jm-cta-btn">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
        </svg>
        Info &amp; Agenda
      </a>
    </div>
  </div>

</div><!-- /jm-wrap -->

</main>

<?php include __DIR__ . '/footer.php'; ?>
</div>

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
