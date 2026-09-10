<?php
require_once __DIR__ . '/../includes/functions.php';

// ── Data Kategorial (server-side) ─────────────────────────────────────
$builtIn = [
    'adorasi'        => ['nama' => 'Adorasi',            'icon' => iconKategorialUrl('adorasi.png')],
    'pdkk'           => ['nama' => 'PDKK',               'icon' => iconKategorialUrl('pdkk.png')],
    'wanita-katolik' => ['nama' => 'WKRI',               'icon' => iconKategorialUrl('wanita-katolik.png')],
    'gim'            => ['nama' => 'Gerakan Iman Maria',  'icon' => iconKategorialUrl('gim.png')],
    'legiomaria'     => ['nama' => 'Legio Maria',         'icon' => iconKategorialUrl('legiomaria.png')],
    'me'             => ['nama' => 'ME',                  'icon' => iconKategorialUrl('me.png')],
    'pk'             => ['nama' => 'Pemuda Katolik',      'icon' => iconKategorialUrl('pk.png')],
    'rosariohidup'   => ['nama' => 'Rosario Hidup',       'icon' => iconKategorialUrl('rosariohidup.png')],
    'ktm'            => ['nama' => 'KTM',                 'icon' => iconKategorialUrl('ktm.png')],
    'ssvmaria'       => ['nama' => 'SSV St. Maria',       'icon' => iconKategorialUrl('ssvmaria.png')],
    'ssvrosali'      => ['nama' => 'SSV Rosali',          'icon' => iconKategorialUrl('ssvrosali.png')],
];

$dbRows = fetchSupabaseCached('kelompok_profil', [], 'slug.asc', 'slug,nama,deskripsi,icon');
$dbMap  = [];
if (is_array($dbRows)) { foreach ($dbRows as $r) { if (!empty($r['slug'])) $dbMap[$r['slug']] = $r; } }

$daftarKelompok = [];
foreach ($builtIn as $slug => $meta) {
    $db = $dbMap[$slug] ?? [];
    $rawIcon = ($db['icon'] ?? '') ?: $meta['icon'];
    $daftarKelompok[$slug] = [
        'slug'     => $slug,
        'nama'     => ($db['nama'] ?? '') ?: $meta['nama'],
        'deskripsi'=> $db['deskripsi'] ?? '',
        'icon'     => $rawIcon ? iconKategorialUrl($rawIcon) : '',
    ];
}
foreach ($dbMap as $slug => $r) {
    if (!isset($daftarKelompok[$slug])) {
        $daftarKelompok[$slug] = [
            'slug'     => $slug,
            'nama'     => ($r['nama'] ?? '') ?: ucwords(str_replace(['-','_'],' ',$slug)),
            'deskripsi'=> $r['deskripsi'] ?? '',
            'icon'     => !empty($r['icon']) ? iconKategorialUrl($r['icon']) : '',
        ];
    }
}
$daftarKelompok = array_values($daftarKelompok);

// ── SEO ───────────────────────────────────────────────────────────────
$seo = [
    'title'       => 'Kelompok Kategorial – Paroki Tulungagung',
    'description' => 'Daftar kelompok kategorial umat Paroki Santa Maria Dengan Tidak Bernoda Asal Tulungagung: Adorasi, PDKK, Legio Maria, OMK, dan lainnya.',
    'canonical'   => 'https://www.parokitulungagung.org/kategorial',
    'keywords'    => 'kelompok kategorial paroki, adorasi, pdkk, legio maria, wkri, paroki smdtba tulungagung',
    'type'        => 'website',
];
$breadcrumbs = [
    ['name'=>'Beranda','url'=>'https://www.parokitulungagung.org'],
    ['name'=>'Kategorial','url'=>'https://www.parokitulungagung.org/kategorial'],
];
$extraCss = ['/css/content.css'];

// ── Schema: ItemList kelompok ─────────────────────────────────────────
$itemListSchema = array_map(fn($k, $idx) => [
    '@type'    => 'ListItem',
    'position' => $idx + 1,
    'name'     => $k['nama'],
    'url'      => 'https://www.parokitulungagung.org/kategorial/' . $k['slug'],
], $daftarKelompok, array_keys($daftarKelompok));
?>
<!doctype html>
<html lang="id">
<head>
  <?php include __DIR__ . '/../components/seo_head.php'; ?>
  <script type="application/ld+json">
  <?= json_encode([
    '@context'       => 'https://schema.org',
    '@type'          => 'ItemList',
    'name'           => 'Kelompok Kategorial Paroki Tulungagung',
    'description'    => 'Daftar kelompok kategorial umat Paroki Santa Maria Dengan Tidak Bernoda Asal Tulungagung.',
    'url'            => 'https://www.parokitulungagung.org/kategorial',
    'numberOfItems'  => count($daftarKelompok),
    'itemListElement'=> $itemListSchema,
  ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT); ?>
  </script>
</head>
<body>
<?php $headerTitle = 'Kategorial'; include __DIR__ . '/../components/page_header.php'; ?>
<?php include __DIR__ . '/../components/photo_modal.php'; ?>

<main id="main-content" style="padding:6px">

  <div class="page-hero">
    <div class="page-hero-icon">
      <img src="<?= iconUrl('icon_square_kategorial3.png') ?>" alt="" loading="lazy" width="40" height="40">
    </div>
    <div class="page-hero-text">
      <h1>Kegiatan Kelompok Kategorial</h1>
      <p>Paroki Santa Maria Dengan Tidak Bernoda Asal · Tulungagung</p>
    </div>
  </div>

  <div class="w3-container w3-card" style="background-color:#e0e9ee" id="listKategorial">
    <?php foreach ($daftarKelompok as $k): ?>
    <div class="kategorial-card" onclick="location.href='/kategorial/<?= urlencode($k['slug']) ?>'">
      <div class="kategorial-icon-wrapper">
        <?php if ($k['icon']): ?>
        <img src="<?= e($k['icon']) ?>" alt="<?= e($k['nama']) ?>" class="kategorial-icon" loading="lazy"
             width="48" height="48" onerror="this.onerror=null;this.src='<?= iconKategorialUrl('default_kategorial.png') ?>';">
        <?php else: ?>
        <div style="width:48px;height:48px;border-radius:50%;background:rgba(184,150,62,.18);display:flex;align-items:center;justify-content:center;font-size:1.3rem;font-weight:700;color:#b8963e;font-family:'Cormorant Garamond',serif"><?= strtoupper(mb_substr($k['nama'],0,1)) ?></div>
        <?php endif; ?>
      </div>
      <div class="kategorial-content">
        <h3 class="kategorial-title"><?= e($k['nama']) ?></h3>
        <?php if ($k['deskripsi']): ?><p class="kategorial-description"><?= e($k['deskripsi']) ?></p><?php endif; ?>
      </div>
      <div class="kategorial-arrow"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg></div>
    </div>
    <?php endforeach; ?>
  </div>
</main>

<?php include __DIR__ . '/../components/footer.php'; ?>
<script src="/js/app.js?v=<?= substr(md5_file(__DIR__ . '/../js/app.js'), 0, 8) ?>"></script>
</body>
</html>
