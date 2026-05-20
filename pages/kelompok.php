<?php
/**
 * pages/kelompok.php — Profil kelompok kategorial
 * UPDATE: Mendukung slug dinamis yang ditambahkan via admin
 *         Icon, nama, deskripsi diprioritaskan dari DB (Supabase)
 * URL: /pages/kelompok?type={slug}
 */
require_once __DIR__ . '/../includes/functions.php';

$slug = preg_replace('/[^a-z0-9\-]/', '', trim($_GET['type'] ?? ''));
if (!$slug) {
    http_response_code(404); include __DIR__ . '/../error.php'; exit;
}

// ── Metadata statis bawaan (fallback) ──────────────────────────────
$metaBuiltIn = [
    'adorasi'        => ['nama' => 'Adorasi',             'icon' => '/img/icon/kategorial/adorasi.png'],
    'pdkk'           => ['nama' => 'PDKK',                'icon' => '/img/icon/kategorial/pdkk.png'],
    'wanita-katolik' => ['nama' => 'WKRI',                'icon' => '/img/icon/kategorial/wanita-katolik.png'],
    'gim'            => ['nama' => 'Gerakan Iman Maria',  'icon' => '/img/icon/kategorial/gim.png'],
    'legiomaria'     => ['nama' => 'Legio Maria',         'icon' => '/img/icon/kategorial/legiomaria.png'],
    'me'             => ['nama' => 'ME',                  'icon' => '/img/icon/kategorial/me.png'],
    'pk'             => ['nama' => 'Pemuda Katolik',      'icon' => '/img/icon/kategorial/pk.png'],
    'rosariohidup'   => ['nama' => 'Rosario Hidup',       'icon' => '/img/icon/kategorial/rosariohidup.png'],
    'ktm'            => ['nama' => 'KTM',                 'icon' => '/img/icon/kategorial/ktm.png'],
    'ssvmaria'       => ['nama' => 'SSV St. Maria',       'icon' => '/img/icon/kategorial/ssvmaria.png'],
    'ssvrosali'      => ['nama' => 'SSV Rosali',          'icon' => '/img/icon/kategorial/ssvrosali.png'],
];

// ── Ambil profil dari Supabase ─────────────────────────────────────
// Gunakan slug langsung — tidak dibatasi $metaBuiltIn lagi
// sehingga slug dinamis yang ditambahkan admin juga terbaca
$profilRows = fetchSupabaseCached('kelompok_profil', ['slug' => $slug], '', '*');
$profil     = (is_array($profilRows) && isset($profilRows[0]) && is_array($profilRows[0]))
    ? $profilRows[0] : [];

// Jika slug tidak ada di built-in DAN tidak ada di DB → 404
if (!isset($metaBuiltIn[$slug]) && empty($profil)) {
    http_response_code(404); include __DIR__ . '/../error.php'; exit;
}

// ── Ambil semua profil lain untuk sidebar "Kelompok Lainnya" ───────
// Gabungkan: built-in + yang ada di DB
$allOtherRows = fetchSupabaseCached('kelompok_profil', [], 'slug.asc', 'slug,nama,icon');
$dbSlugsMap   = [];
if (is_array($allOtherRows)) {
    foreach ($allOtherRows as $r) {
        if (!is_array($r) || empty($r['slug'])) continue;
        $dbSlugsMap[$r['slug']] = $r;
    }
}
// Susun daftar semua kelompok untuk sidebar
$allKelompok = [];
// Prioritas: built-in dengan nama dari DB jika tersedia
foreach ($metaBuiltIn as $s => $m) {
    $dbRow = $dbSlugsMap[$s] ?? [];
    $allKelompok[$s] = [
        'nama' => $dbRow['nama'] ?? $m['nama'],
        'icon' => $dbRow['icon'] ?? $m['icon'],
    ];
}
// Tambahkan slug dari DB yang bukan built-in
foreach ($dbSlugsMap as $s => $r) {
    if (!isset($allKelompok[$s])) {
        $allKelompok[$s] = [
            'nama' => $r['nama'] ?? ucwords(str_replace(['-','_'], ' ', $s)),
            'icon' => $r['icon'] ?? '',
        ];
    }
}

// ── Resolusi data tampil ────────────────────────────────────────────
// Nama: DB > meta built-in > prettify slug
$namaDB       = trim($profil['nama'] ?? '');
$namaBuiltIn  = $metaBuiltIn[$slug]['nama'] ?? '';
$nama         = $namaDB ?: ($namaBuiltIn ?: ucwords(str_replace(['-','_'], ' ', $slug)));

// Icon: DB > meta built-in > kosong
$iconDB       = trim($profil['icon'] ?? '');
$iconBuiltIn  = $metaBuiltIn[$slug]['icon'] ?? '';
$icon         = $iconDB ?: $iconBuiltIn;

$subtitle  = $profil['subtitle']  ?? '';
$deskripsi = $profil['deskripsi'] ?? '';
$banner    = $profil['banner']    ?? '';
$info      = $profil['info']      ?? '';
$kegiatan  = $profil['kegiatan']  ?? '';
$tipe      = $profil['tipe_sosial']   ?? '';
$handle    = $profil['handle_sosial'] ?? '';
$linkSos   = $profil['link_sosial']   ?? '';

// Pengurus
$pengurus = [];
if (!empty($profil['pengurus'])) {
    $decoded = json_decode($profil['pengurus'], true);
    if (is_array($decoded)) $pengurus = $decoded;
}

// ── SEO: title & description unik per kelompok ─────────────────────
// Title: "Nama Kelompok – Kelompok Kategorial Paroki SMDTBA Tulungagung"
// Description: pakai deskripsi DB jika ada, lalu fallback bertingkat.
// Panjang description dijaga 120–155 karakter agar tidak terpotong di SERP.

$_seoBase   = 'Paroki Tulungagung';

// Fallback description statis per slug (untuk kelompok yang belum punya deskripsi di DB)
$_descFallback = [
    'adorasi'        => 'Kelompok Adorasi ' . $_seoBase . ' — pelayanan adorasi sakramen mahakudus, doa bersama, dan penguatan iman umat melalui kehadiran di hadapan Tuhan.',
    'pdkk'           => 'PDKK (Pembaruan Karismatik Katolik) ' . $_seoBase . ' — komunitas iman yang menghidupi karunia Roh Kudus, pujian, penyembahan, dan pelayanan doa.',
    'wanita-katolik' => 'WKRI (Wanita Katolik Republik Indonesia) ' . $_seoBase . ' — organisasi wanita Katolik yang aktif dalam sosial, pendidikan, dan pengembangan iman umat.',
    'gim'            => 'GIM (Gerakan Iman Maria) ' . $_seoBase . ' — gerakan devosi kepada Bunda Maria yang menghidupi semangat Fátima dalam kehidupan sehari-hari.',
    'legiomaria'     => 'Legio Maria ' . $_seoBase . ' — kelompok apostolik Katolik yang melayani umat melalui kunjungan, doa Rosario bersama, dan karya kerasulan.',
    'me'             => 'ME (Marriage Encounter) ' . $_seoBase . ' — komunitas pasangan suami-istri Katolik yang memperkuat keluarga melalui komunikasi, iman, dan cinta.',
    'pk'             => 'Pemuda Katolik ' . $_seoBase . ' — wadah pembinaan dan pengembangan kaum muda Katolik dalam iman, karakter, dan kontribusi nyata bagi masyarakat.',
    'rosariohidup'   => 'Rosario Hidup ' . $_seoBase . ' — komunitas devosi yang mendaraskan Rosario secara teratur dan bersatu dalam doa bagi Gereja dan dunia.',
    'ktm'            => 'KTM (Komunitas Tritunggal Mahakudus) ' . $_seoBase . ' — komunitas iman yang menghidupi spiritualitas Tritunggal Mahakudus dalam pelayanan dan doa.',
    'ssvmaria'       => 'SSV St. Maria (Serikat Santo Vinsensius) ' . $_seoBase . ' — kelompok sosial Katolik yang melayani kaum miskin dan membutuhkan dengan kasih konkret.',
    'ssvrosali'      => 'SSV Rosali (Serikat Santo Vinsensius) ' . $_seoBase . ' — kelompok vinsentian yang aktif melayani sesama dengan semangat Santo Vinsensius de Paul.',
    'omk'            => 'OMK (Orang Muda Katolik) ' . $_seoBase . ' — komunitas kaum muda Katolik yang dinamis dalam iman, pelayanan, seni budaya, dan pengembangan diri.',
    'misdinar'       => 'Misdinar ' . $_seoBase . ' — kelompok putra-putri altar yang melayani dalam Misa dan liturgi dengan penuh tanggung jawab dan kesetiaan.',
    'pkkt'           => 'PKKT (Paguyuban Keluarga Katolik Terpanggil) ' . $_seoBase . ' — komunitas keluarga Katolik yang terpanggil untuk menghidupi iman bersama dalam kehidupan sehari-hari.',
    'bia'            => 'BIA (Bina Iman Anak) ' . $_seoBase . ' — program pembinaan iman anak-anak Katolik melalui pendidikan katekese, doa, dan kegiatan kreatif.',
    'liturgi'        => 'Tim Liturgi ' . $_seoBase . ' — tim yang bertanggung jawab atas perayaan liturgi, tata cara ibadat, dan keindahan perayaan Ekaristi di paroki.',
    'komsos'         => 'Komsos (Komunikasi Sosial) ' . $_seoBase . ' — komisi yang mengelola komunikasi dan media paroki termasuk E-Lonceng, media sosial, dan dokumentasi.',
];

// Bangun description: DB → fallback statis → generik
if (!empty(trim($deskripsi))) {
    // Deskripsi dari DB — potong jika terlalu panjang
    $_rawDesc  = trim(strip_tags($deskripsi));
    $_seoDesc  = mb_strlen($_rawDesc) > 155
        ? mb_substr($_rawDesc, 0, mb_strrpos(mb_substr($_rawDesc, 0, 152), ' ')) . '...'
        : $_rawDesc;
} elseif (isset($_descFallback[$slug])) {
    $_seoDesc = $_descFallback[$slug];
} else {
    $_seoDesc = $nama . ' adalah kelompok kategorial di ' . $_seoBase
        . '. Temukan informasi, kegiatan rutin, dan pengurus ' . $nama . ' di sini.';
}

// Keywords: nama spesifik + variasi + lokasi
$_seoKw = implode(', ', array_unique(array_filter([
    mb_strtolower($nama),
    mb_strtolower($slug),
    'kategorial ' . mb_strtolower($nama),
    'kelompok ' . mb_strtolower($nama) . ' gereja',
    'paroki smdtba tulungagung',
    'gereja katolik tulungagung',
])));

$seo = [
    'title'       => $nama . ' – Kelompok Kategorial ' . $_seoBase,
    'description' => $_seoDesc,
    'canonical'   => 'https://www.parokitulungagung.org/kategorial/' . $slug,
    'image'       => $banner
        ? 'https://www.parokitulungagung.org' . $banner
        : ($icon ? 'https://www.parokitulungagung.org' . $icon : ''),
    'keywords'    => $_seoKw,
];
$breadcrumbs = [
    ['name' => 'Beranda',    'url' => 'https://www.parokitulungagung.org'],
    ['name' => 'Kategorial', 'url' => 'https://www.parokitulungagung.org/kategorial'],
    ['name' => $nama],
];
$extraCss = [];

// Sosmed icon map
// Inline SVG per platform — tidak perlu load file gambar eksternal
$sosIcons = [
    'instagram' => '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" xmlns="http://www.w3.org/2000/svg"><defs><radialGradient id="ig" cx="30%" cy="107%" r="150%"><stop offset="0%" stop-color="#fdf497"/><stop offset="5%" stop-color="#fdf497"/><stop offset="45%" stop-color="#fd5949"/><stop offset="60%" stop-color="#d6249f"/><stop offset="90%" stop-color="#285AEB"/></radialGradient></defs><rect x="2" y="2" width="20" height="20" rx="5.5" fill="url(#ig)"/><rect x="2" y="2" width="20" height="20" rx="5.5" fill="none" stroke="none"/><path d="M12 7a5 5 0 100 10A5 5 0 0012 7zm0 8.2A3.2 3.2 0 1112 8.8a3.2 3.2 0 010 6.4z" fill="#fff"/><circle cx="17.2" cy="6.8" r="1.2" fill="#fff"/></svg>',
    'whatsapp'  => '<svg viewBox="0 0 24 24" width="22" height="22" xmlns="http://www.w3.org/2000/svg"><rect width="24" height="24" rx="5.5" fill="#25D366"/><path d="M12 4.5A7.5 7.5 0 004.5 12c0 1.32.35 2.6.98 3.72L4.5 19.5l3.9-.96A7.5 7.5 0 1012 4.5zm0 13.5a6 6 0 01-3.06-.84l-.22-.13-2.27.56.6-2.22-.14-.23A6 6 0 1112 18zm3.3-4.42c-.18-.09-1.06-.52-1.22-.58-.17-.06-.29-.09-.41.09-.12.18-.47.58-.57.7-.1.12-.21.13-.39.04-.18-.09-.76-.28-1.44-.89-.53-.47-.89-1.06-1-.1.12-.47-.52-.58a.44.44 0 01.1-.62c.09-.09.18-.23.27-.35.09-.12.12-.2.18-.33.06-.13.03-.24-.02-.33-.05-.09-.41-1-.56-1.37-.15-.36-.3-.31-.41-.32h-.35c-.12 0-.32.05-.49.23-.17.18-.65.63-.65 1.54s.67 1.79.76 1.91c.09.12 1.31 2 3.18 2.73.44.19.79.3 1.06.38.45.14.85.12 1.17.07.36-.05 1.1-.45 1.26-.88.15-.43.15-.8.1-.88-.05-.08-.17-.13-.35-.22z" fill="#fff"/></svg>',
    'facebook'  => '<svg viewBox="0 0 24 24" width="22" height="22" xmlns="http://www.w3.org/2000/svg"><rect width="24" height="24" rx="5.5" fill="#1877F2"/><path d="M16 8h-2a1 1 0 00-1 1v2h3l-.5 3H13v7h-3v-7H8v-3h2V9a4 4 0 014-4h2v3z" fill="#fff"/></svg>',
    'youtube'   => '<svg viewBox="0 0 24 24" width="22" height="22" xmlns="http://www.w3.org/2000/svg"><rect width="24" height="24" rx="5.5" fill="#FF0000"/><path d="M19.6 8.4a2 2 0 00-1.4-1.4C16.8 6.6 12 6.6 12 6.6s-4.8 0-6.2.4A2 2 0 004.4 8.4C4 9.8 4 12 4 12s0 2.2.4 3.6a2 2 0 001.4 1.4c1.4.4 6.2.4 6.2.4s4.8 0 6.2-.4a2 2 0 001.4-1.4C20 14.2 20 12 20 12s0-2.2-.4-3.6z" fill="#fff"/><path d="M10 15l5-3-5-3v6z" fill="#FF0000"/></svg>',
    'website'   => '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="9"/><path d="M12 3c-2.5 3-4 5.5-4 9s1.5 6 4 9M12 3c2.5 3 4 5.5 4 9s-1.5 6-4 9M3 12h18"/></svg>',
];
?>
<!doctype html>
<html lang="id">
<head>
  <?php include __DIR__ . '/../components/seo_head.php'; ?>
  <?php
  // JSON-LD: Organization schema per kelompok — membantu Google memahami entitas ini
  $_klpSchema = array_filter([
    '@context'    => 'https://schema.org',
    '@type'       => 'Organization',
    'name'        => $nama,
    'description' => $_seoDesc,
    'url'         => 'https://www.parokitulungagung.org/kategorial/' . $slug,
    'logo'        => $icon ? 'https://www.parokitulungagung.org' . $icon : null,
    'image'       => $banner ? 'https://www.parokitulungagung.org' . $banner : null,
    'parentOrganization' => [
      '@type' => 'Church',
      'name'  => 'Paroki Santa Maria Dengan Tidak Bernoda Asal Tulungagung',
      'url'   => 'https://www.parokitulungagung.org',
    ],
  ], fn($v) => $v !== null && $v !== '');
  // Tambahkan sameAs jika ada sosmed
  if ($linkSos) $_klpSchema['sameAs'] = [$linkSos];
  ?>
  <script type="application/ld+json">
  <?= json_encode($_klpSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?>
  </script>
  <style>
  /* ── Hero banner ──────────────────────────────────────────── */
  .klp-hero {
    position: relative;
    background: linear-gradient(135deg, #1c1711, #2e2415);
    overflow: hidden;
    min-height: 0;
    display: flex; align-items: flex-end;
  }
  .klp-hero.has-banner { min-height: 220px; }
  .klp-hero-bg {
    position: absolute; inset: 0;
    background-size: cover; background-position: center;
    opacity: .45; transition: opacity .3s;
  }
  .klp-hero-overlay {
    position: absolute; inset: 0;
    background: linear-gradient(to top, rgba(20,14,6,.92) 0%, rgba(20,14,6,.3) 60%, transparent 100%);
  }
  .klp-hero-content {
    position: relative; z-index: 2;
    padding: clamp(1rem,3vw,1.5rem) clamp(1rem,4vw,2rem);
    display: flex; align-items: center; gap: 1rem; width: 100%;
    flex-wrap: wrap;
  }
  .klp-hero.has-banner .klp-hero-content {
    padding: clamp(1.4rem,4vw,2.2rem) clamp(1rem,4vw,2rem) clamp(1.6rem,4vw,2.4rem);
    align-items: flex-end;
  }
  .klp-hero-icon {
    width: clamp(52px,8vw,72px); height: clamp(52px,8vw,72px);
    object-fit: contain; filter: drop-shadow(0 2px 8px rgba(0,0,0,.5));
    flex-shrink: 0;
  }
  .klp-hero-text { flex: 1; min-width: 0; }
  .klp-hero-title {
    font-family: 'Cormorant Garamond', Georgia, serif;
    font-size: clamp(1.6rem,5vw,2.6rem); font-weight: 700;
    color: #fff; line-height: 1.15; margin: 0 0 .25rem;
  }
  .klp-hero-sub {
    font-size: clamp(.72rem,2vw,.82rem);
    color: rgba(255,255,255,.65); font-family: 'Montserrat',sans-serif;
  }
  .klp-hero-desc {
    font-size: clamp(.78rem,2vw,.88rem);
    color: rgba(255,255,255,.8); margin-top: .35rem;
    font-family: 'Archivo Narrow',Arial,sans-serif;
  }

  /* ── Layout ──────────────────────────────────────────────── */
  .klp-body {
    max-width: 920px; margin: 0 auto;
    padding: clamp(1.4rem,4vw,2.5rem) clamp(.8rem,3vw,1.5rem) 3rem;
    display: grid;
    grid-template-columns: 1fr 300px;
    gap: clamp(1rem,3vw,2rem);
    align-items: start;
  }
  @media (max-width: 680px) {
    .klp-body { grid-template-columns: 1fr; }
  }

  /* ── Cards ───────────────────────────────────────────────── */
  .klp-card {
    background: #fff;
    border: 1px solid #e8e0d4;
    border-radius: 12px;
    overflow: hidden;
    margin-bottom: 1rem;
  }
  .klp-card-header {
    padding: .9rem 1.3rem;
    background: linear-gradient(135deg, #faf7f2, #f2ebe0);
    border-bottom: 1px solid #e8e0d4;
    display: flex; align-items: center; gap: .7rem;
  }
  .klp-card-header-icon {
    width: 18px; height: 18px; color: #b8963e; flex-shrink: 0;
  }
  .klp-card-title {
    font-family: 'Cormorant Garamond',serif;
    font-size: 1.05rem; font-weight: 700; color: #2c1a0e;
  }
  .klp-card-body {
    padding: 1.2rem 1.3rem;
    font-size: .88rem; line-height: 1.85; color: #3d3428;
    font-family: 'Archivo Narrow',Arial,sans-serif;
  }
  .klp-card-body p { margin-bottom: .7rem; }
  .klp-card-body p:last-child { margin-bottom: 0; }

  /* ── Pengurus ────────────────────────────────────────────── */
  .klp-pengurus-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(130px,1fr));
    gap: .8rem; padding: 1.2rem 1.3rem;
  }
  .klp-pengurus-item {
    text-align: center; padding: .8rem .6rem;
    background: #faf7f2; border-radius: 8px;
    border: 1px solid #e8e0d4;
  }
  .klp-pengurus-avatar {
    width: 52px; height: 52px; border-radius: 50%;
    background: linear-gradient(135deg,#564938,#7a6655);
    color: #f5debb; font-size: 1.2rem; font-weight: 700;
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto .55rem; border: 2px solid #e8e0d4; overflow: hidden;
    font-family: 'Montserrat',sans-serif;
  }
  .klp-pengurus-avatar img { width: 100%; height: 100%; object-fit: cover; }
  .klp-pengurus-nama  { font-size: .8rem; font-weight: 600; color: #2c1a0e; line-height: 1.3; }
  .klp-pengurus-jabatan { font-size: .7rem; color: #7a6e5f; margin-top: .2rem; }

  /* ── Sidebar ─────────────────────────────────────────────── */
  .klp-sidebar-card {
    background: #fff; border: 1px solid #e8e0d4;
    border-radius: 12px; overflow: hidden; margin-bottom: 1rem;
  }
  .klp-sidebar-title {
    padding: .75rem 1.1rem;
    background: linear-gradient(135deg,#faf7f2,#f2ebe0);
    border-bottom: 1px solid #e8e0d4;
    font-family: 'Cormorant Garamond',serif;
    font-size: .95rem; font-weight: 700; color: #2c1a0e;
  }
  .klp-sidebar-body { padding: 1rem 1.1rem; }

  /* Sosmed button */
  .klp-sosmed-btn {
    display: flex; align-items: center; gap: .7rem;
    padding: .75rem 1rem;
    background: #faf7f2; border: 1px solid #e8e0d4;
    border-radius: 8px; text-decoration: none; color: #2c1a0e;
    font-size: .85rem; font-weight: 500;
    transition: border-color .18s, background .18s;
    font-family: 'Montserrat',sans-serif;
  }
  .klp-sosmed-btn:hover {
    border-color: #b8963e; background: #f5ecd5; color: #7a5800;
  }
  .klp-sosmed-icon { display:flex; align-items:center; flex-shrink:0; }
  .klp-sosmed-icon svg { display:block; }

  /* Back button */
  .klp-back {
    display: inline-flex; align-items: center; gap: .5rem;
    padding: .6rem 1.1rem;
    border: 1px solid #d9cfc4; border-radius: 8px;
    font-size: .8rem; color: #564938; text-decoration: none;
    font-family: 'Montserrat',sans-serif; font-weight: 500;
    transition: all .18s; margin-bottom: 1.2rem;
  }
  .klp-back:hover { background: #b8860b; border-color: #b8860b; color: #fff; }

  /* ── Section block ─────────────────────────────────────── */
  .klp-section {
    background: #fff;
    border: 1px solid #e8e0d4;
    border-radius: 12px;
    overflow: hidden;
    margin-bottom: 1rem;
  }
  .klp-section-header {
    display: flex; align-items: center; gap: 10px;
    padding: 1rem 1.3rem;
    border-bottom: 1px solid #f0e8dc;
  }
  .klp-section-line {
    width: 3px; height: 18px;
    background: linear-gradient(180deg, #b8963e, #d4af6a);
    border-radius: 2px; flex-shrink: 0;
  }
  .klp-section-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.1rem; font-weight: 700;
    color: #2c1a0e; letter-spacing: .02em;
  }
  .klp-section-body {
    padding: 1.2rem 1.4rem;
    font-size: .88rem; line-height: 1.9;
    color: #3d3428;
    font-family: 'Archivo Narrow', Arial, sans-serif;
  }
  .klp-section-body p { margin-bottom: .7rem; }
  .klp-section-body p:last-child { margin-bottom: 0; }
  </style>
</head>
<body>
<?php $headerTitle = 'Kategorial'; include __DIR__ . '/../components/page_header.php'; ?>

  <main>
    <!-- Hero Banner -->
    <div class="klp-hero<?= $banner ? ' has-banner' : '' ?>">
      <?php if ($banner): ?>
      <div class="klp-hero-bg" style="background-image:url('<?= e($banner) ?>')"></div>
      <?php else: ?>
      <div class="klp-hero-bg" style="background:linear-gradient(135deg,#1c1711,#2e2415)"></div>
      <?php endif; ?>
      <div class="klp-hero-overlay"></div>
      <div class="klp-hero-content">
        <?php if ($icon): ?>
        <img src="<?= e($icon) ?>" alt="<?= e($nama) ?>" class="klp-hero-icon"
             width="72" height="72" onerror="this.style.display='none'">
        <?php else: ?>
        <!-- Placeholder icon jika tidak ada -->
        <div style="width:clamp(52px,8vw,72px);height:clamp(52px,8vw,72px);
                    border-radius:50%;background:rgba(255,255,255,.1);
                    display:flex;align-items:center;justify-content:center;
                    font-family:'Cormorant Garamond',serif;font-size:2rem;
                    color:rgba(255,255,255,.8);font-weight:700;flex-shrink:0;">
          <?= strtoupper(mb_substr($nama, 0, 1)) ?>
        </div>
        <?php endif; ?>
        <div class="klp-hero-text">
          <h1 class="klp-hero-title"><?= e($nama) ?></h1>
          <?php if ($subtitle): ?>
          <div class="klp-hero-sub"><?= e($subtitle) ?></div>
          <?php endif; ?>
          <?php if ($deskripsi): ?>
          <div class="klp-hero-desc"><?= e($deskripsi) ?></div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Body -->
    <div class="klp-body">

      <!-- ── Kolom Utama ── -->
      <div>
        <a href="/kategorial" class="klp-back">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14">
            <polyline points="15 18 9 12 15 6"/>
          </svg>
          Kembali ke Kategorial
        </a>

        <!-- Info -->
        <?php if ($info): ?>
        <div class="klp-section">
          <div class="klp-section-header">
            <div class="klp-section-line"></div>
            <div class="klp-section-title">Informasi</div>
          </div>
          <div class="klp-section-body"><?= nl2br(e($info)) ?></div>
        </div>
        <?php endif; ?>

        <!-- Kegiatan -->
        <?php if ($kegiatan): ?>
        <div class="klp-section">
          <div class="klp-section-header">
            <div class="klp-section-line"></div>
            <div class="klp-section-title">Kegiatan Rutin</div>
          </div>
          <div class="klp-section-body"><?= nl2br(e($kegiatan)) ?></div>
        </div>
        <?php endif; ?>

        <!-- Pengurus -->
        <?php if (!empty($pengurus)): ?>
        <div class="klp-section">
          <div class="klp-section-header">
            <div class="klp-section-line"></div>
            <div class="klp-section-title">Pengurus</div>
          </div>
          <div class="klp-pengurus-grid">
            <?php foreach ($pengurus as $p):
              $pNama = trim($p['nama'] ?? '');
              $pJab  = trim($p['jabatan'] ?? '');
              if (!$pNama && !$pJab) continue;
              $inisial = strtoupper(mb_substr($pNama, 0, 1)) ?: '?';
            ?>
            <div class="klp-pengurus-item">
              <div class="klp-pengurus-avatar">
                <?php if (!empty($p['foto'])): ?>
                <img src="<?= e($p['foto']) ?>" alt="<?= e($pNama) ?>"
                     width="80" height="80" onerror="this.style.display='none';this.nextSibling.style.display='flex'">
                <span style="display:none"><?= e($inisial) ?></span>
                <?php else: ?>
                <?= e($inisial) ?>
                <?php endif; ?>
              </div>
              <div class="klp-pengurus-nama"><?= e($pNama) ?></div>
              <?php if ($pJab): ?>
              <div class="klp-pengurus-jabatan"><?= e($pJab) ?></div>
              <?php endif; ?>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>

        <?php if (!$info && !$kegiatan && empty($pengurus)): ?>
        <div class="klp-section">
          <div class="klp-section-body" style="text-align:center;color:#aaa;padding:2.5rem 1rem">
            Profil kelompok belum tersedia.
          </div>
        </div>
        <?php endif; ?>

      </div>

      <!-- ── Sidebar ── -->
      <aside>

        <?php if ($handle || $linkSos): ?>
        <!-- Sosial Media -->
        <div class="klp-sidebar-card">
          <div class="klp-sidebar-title">Ikuti Kami</div>
          <div class="klp-sidebar-body">
            <?php
            $sosUrl   = $linkSos ?: '#';
            $sosIcon  = $sosIcons[$tipe] ?? '';
            $sosLabel = $handle ?: ucfirst($tipe);
            ?>
            <a href="<?= e($sosUrl) ?>" target="_blank" rel="noopener" class="klp-sosmed-btn">
              <span class="klp-sosmed-icon"><?= $sosIcon ?></span>
              <?= e($sosLabel) ?>
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13" style="margin-left:auto;opacity:.4">
                <path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/>
                <polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/>
              </svg>
            </a>
          </div>
        </div>
        <?php endif; ?>

        <!-- Info singkat -->
        <div class="klp-sidebar-card">
          <div class="klp-sidebar-title">Tentang</div>
          <div class="klp-sidebar-body" style="font-size:.82rem;color:#5a4a3a;line-height:1.7">
            <div style="display:flex;gap:.6rem;margin-bottom:.6rem;align-items:flex-start">
              <svg viewBox="0 0 24 24" fill="none" stroke="#b8963e" stroke-width="2" width="16" height="16" style="flex-shrink:0;margin-top:2px"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
              <span>Paroki Tulungagung</span>
            </div>
            <?php if ($deskripsi): ?>
            <div style="display:flex;gap:.6rem;align-items:flex-start">
              <svg viewBox="0 0 24 24" fill="none" stroke="#b8963e" stroke-width="2" width="16" height="16" style="flex-shrink:0;margin-top:2px"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>
              <span><?= e($deskripsi) ?></span>
            </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Kelompok lainnya (dinamis dari DB + built-in) -->
        <div class="klp-sidebar-card">
          <div class="klp-sidebar-title">Kelompok Lainnya</div>
          <div style="padding:.6rem .8rem">
            <?php foreach ($allKelompok as $s => $km):
              if ($s === $slug) continue;
              $kmNama = $km['nama'] ?? ucwords(str_replace(['-','_'], ' ', $s));
              $kmIcon = $km['icon'] ?? '';
            ?>
            <a href="/kategorial/<?= e($s) ?>"
               style="display:flex;align-items:center;gap:.6rem;padding:.5rem .5rem;
                      text-decoration:none;color:#3d3428;border-radius:6px;
                      font-size:.8rem;transition:background .15s"
               onmouseover="this.style.background='#f5ecd5'"
               onmouseout="this.style.background=''">
              <?php if ($kmIcon): ?>
              <img src="<?= e($kmIcon) ?>" alt="" width="20" height="20"
                   style="object-fit:contain;flex-shrink:0;opacity:.8"
                   onerror="this.style.display='none'">
              <?php else: ?>
              <div style="width:20px;height:20px;border-radius:50%;
                          background:rgba(184,150,62,.12);
                          display:flex;align-items:center;justify-content:center;
                          font-size:10px;font-weight:700;color:#b8963e;flex-shrink:0">
                <?= strtoupper(mb_substr($kmNama, 0, 1)) ?>
              </div>
              <?php endif; ?>
              <?= e($kmNama) ?>
            </a>
            <?php endforeach; ?>
          </div>
        </div>

      </aside>
    </div>
  </main>

</div><!-- /#outer-wrapper -->
<?php include __DIR__ . '/../components/footer.php'; ?>
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