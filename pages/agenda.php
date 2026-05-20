<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/SupabaseArticleManager.php';

// ── Data Agenda dari Supabase ─────────────────────────────────────────
$data      = fetchSupabaseCached('info_paroki', [], 'tanggal.asc');
$dataError = ($data === null) ? 'Gagal mengambil data dari server. Silakan coba lagi.' : null;

if ($data && is_array($data)) {
    $bulanMap = [
        'januari'=>1,'februari'=>2,'maret'=>3,'april'=>4,
        'mei'=>5,'juni'=>6,'juli'=>7,'agustus'=>8,
        'september'=>9,'oktober'=>10,'november'=>11,'desember'=>12,
        'jan'=>1,'feb'=>2,'mar'=>3,'apr'=>4,
        'jun'=>6,'jul'=>7,'ags'=>8,'sep'=>9,'okt'=>10,'nov'=>11,'des'=>12,
    ];
    usort($data, function($a, $b) use ($bulanMap) {
        $monthA = $bulanMap[strtolower(trim($a['bulan'] ?? ''))] ?? 1;
        $monthB = $bulanMap[strtolower(trim($b['bulan'] ?? ''))] ?? 1;
        $valueA = ($monthA * 100) + (int)($a['tanggal'] ?? 1);
        $valueB = ($monthB * 100) + (int)($b['tanggal'] ?? 1);
        return $valueA - $valueB;
    });
}

// ── Data Dokumen ──────────────────────────────────────────────────────
$dokumenData  = fetchSupabaseCached('dokumen_paroki', [], 'urutan.asc,id.desc');
$dokumenError = ($dokumenData === null) ? 'Gagal memuat dokumen.' : null;
if (is_array($dokumenData)) {
    $dokumenData = array_values(array_filter($dokumenData, fn($d) => !empty($d['aktif'])));
}
$dokumenCount = is_array($dokumenData) ? count($dokumenData) : 0;

// ── Data Jadwal Petugas ───────────────────────────────────────────────
$petugasData  = fetchSupabaseCached('jadwal_petugas_gambar', [], 'urutan.asc,id.desc');
$petugasError = ($petugasData === null) ? 'Gagal memuat jadwal petugas.' : null;
if (is_array($petugasData)) {
    $petugasData = array_values(array_filter($petugasData, fn($d) => !empty($d['aktif'])));
}
$petugasCount = is_array($petugasData) ? count($petugasData) : 0;

// ── SEO ───────────────────────────────────────────────────────────────
$seo = [
    'title'       => 'Info & Agenda Paroki – Gereja Katolik Tulungagung',
    'description' => 'Jadwal misa harian & mingguan, agenda kegiatan, jadwal petugas liturgi, dan dokumen resmi Paroki Santa Maria Dengan Tidak Bernoda Asal (SMDTBA) Tulungagung. Update setiap hari.',
    'canonical'   => 'https://www.parokitulungagung.org/agenda',
    'keywords'    => 'agenda paroki, jadwal misa tulungagung, jadwal petugas liturgi, info paroki smdtba, kegiatan gereja katolik tulungagung',
    'type'        => 'website',
];
$breadcrumbs = [
    ['name' => 'Beranda',    'url' => 'https://www.parokitulungagung.org'],
    ['name' => 'Info Paroki','url' => 'https://www.parokitulungagung.org/agenda'],
];
$extraCss = ['/css/content.css'];

// ── Render helper: file type icon untuk download ──────────────────────
function agendaDlFileType(string $ext): array
{
    $ext = strtolower($ext);
    $types = [
        'pdf'  => ['#e53935','#fff5f5','PDF','<path d="M10 16h6M10 20h14M10 24h10" stroke="#e53935" stroke-width="1.8" stroke-linecap="round"/>'],
        'doc'  => ['#1565c0','#e8f0fe','DOC','<path d="M10 16h6M10 20h14M10 24h10" stroke="#1565c0" stroke-width="1.8" stroke-linecap="round"/>'],
        'docx' => ['#1565c0','#e8f0fe','DOCX','<path d="M10 16h6M10 20h14M10 24h10" stroke="#1565c0" stroke-width="1.8" stroke-linecap="round"/>'],
        'xls'  => ['#2e7d32','#e8f5e9','XLS','<path d="M10 16h14M10 20h14M10 24h14M24 16v12" stroke="#2e7d32" stroke-width="1.5" stroke-linecap="round"/>'],
        'xlsx' => ['#2e7d32','#e8f5e9','XLSX','<path d="M10 16h14M10 20h14M10 24h14M24 16v12" stroke="#2e7d32" stroke-width="1.5" stroke-linecap="round"/>'],
        'ppt'  => ['#e65100','#fff3e0','PPT','<rect x="10" y="14" width="18" height="12" rx="2" stroke="#e65100" stroke-width="1.5"/>'],
        'pptx' => ['#e65100','#fff3e0','PPTX','<rect x="10" y="14" width="18" height="12" rx="2" stroke="#e65100" stroke-width="1.5"/>'],
        'jpg'  => ['#6a1b9a','#f3e5f5','JPG','<circle cx="16" cy="17" r="3" stroke="#6a1b9a" stroke-width="1.5"/><path d="M8 27l7-7 5 5 4-4 6 6" stroke="#6a1b9a" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>'],
        'jpeg' => ['#6a1b9a','#f3e5f5','JPEG','<circle cx="16" cy="17" r="3" stroke="#6a1b9a" stroke-width="1.5"/><path d="M8 27l7-7 5 5 4-4 6 6" stroke="#6a1b9a" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>'],
        'png'  => ['#0277bd','#e1f5fe','PNG','<circle cx="16" cy="17" r="3" stroke="#0277bd" stroke-width="1.5"/><path d="M8 27l7-7 5 5 4-4 6 6" stroke="#0277bd" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>'],
        'mp4'  => ['#00695c','#e0f2f1','MP4','<polygon points="16,13 16,27 30,20" stroke="#00695c" stroke-width="1.5" fill="none" stroke-linejoin="round"/>'],
        'txt'  => ['#546e7a','#eceff1','TXT','<path d="M10 16h18M10 20h14M10 24h16" stroke="#546e7a" stroke-width="1.8" stroke-linecap="round"/>'],
    ];
    if (isset($types[$ext])) {
        [$color, $bg, $label, $detail] = $types[$ext];
    } else {
        $color = '#546e7a'; $bg = '#eceff1';
        $label = $ext ? strtoupper($ext) : 'FILE';
        $detail = '<path d="M10 16h18M10 20h14M10 24h16" stroke="#546e7a" stroke-width="1.8" stroke-linecap="round"/>';
    }
    $fs  = strlen($label) <= 3 ? 9 : (strlen($label) <= 4 ? 8 : 7);
    $svg = '<svg viewBox="0 0 46 54" fill="none" xmlns="http://www.w3.org/2000/svg" width="46" height="54" style="flex-shrink:0">'
         . '<rect x="1" y="1" width="44" height="52" rx="5" fill="' . $bg . '" stroke="' . $color . '" stroke-width="1.5"/>'
         . '<rect x="1" y="35" width="44" height="18" rx="0" fill="' . $color . '"/>'
         . '<text x="23" y="47" font-family="Montserrat,Arial,sans-serif" font-size="' . $fs . '" font-weight="800" fill="#fff" text-anchor="middle">' . $label . '</text>'
         . $detail . '</svg>';
    return ['color' => $color, 'bg' => $bg, 'label' => $label, 'svg' => $svg];
}
?>
<!doctype html>
<html lang="id">
<head>
  <?php include __DIR__ . '/../components/seo_head.php'; ?>
  <!-- Structured Data: halaman agenda sebagai WebPage -->
  <script type="application/ld+json">
  <?= json_encode([
    '@context'  => 'https://schema.org',
    '@type'     => 'WebPage',
    'name'      => 'Info & Agenda Paroki Tulungagung',
    'description' => 'Informasi jadwal misa, agenda kegiatan, jadwal petugas liturgi, dan dokumen terbaru Paroki Santa Maria Dengan Tidak Bernoda Asal Tulungagung.',
    'url'       => 'https://www.parokitulungagung.org/agenda',
    'inLanguage'=> 'id',
    'isPartOf'  => ['@id' => 'https://www.parokitulungagung.org/#website'],
    'breadcrumb'=> [
        '@type' => 'BreadcrumbList',
        'itemListElement' => [
            ['@type'=>'ListItem','position'=>1,'name'=>'Beranda','item'=>'https://www.parokitulungagung.org'],
            ['@type'=>'ListItem','position'=>2,'name'=>'Info Paroki','item'=>'https://www.parokitulungagung.org/agenda'],
        ],
    ],
  ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT); ?>
  </script>
  <style>


  /* ── Jadwal Petugas ───────────────────────────────────────────── */
  .petugas-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    padding: 6px 0 10px;
  }
  .petugas-card {
    display: flex;
    align-items: center;
    gap: 12px;
    background: #fffdf7;
    border: 1px solid rgba(184,134,11,0.18);
    border-radius: 10px;
    padding: 10px 14px;
    cursor: pointer;
    transition: background .18s, border-color .18s, box-shadow .18s;
    width: 100%;
    max-width: 360px;
  }
  .petugas-card:hover {
    background: #fff9ec;
    border-color: rgba(184,134,11,0.4);
    box-shadow: 0 3px 12px rgba(184,134,11,0.12);
  }
  .petugas-card-thumb {
    width: 48px; height: 48px;
    border-radius: 7px;
    object-fit: cover;
    flex-shrink: 0;
    border: 1px solid rgba(184,134,11,0.15);
    background: #f5f0e8;
  }
  .petugas-card-body {
    flex: 1; min-width: 0;
  }
  .petugas-card-title {
    font-family: 'Cormorant Garamond', 'EB Garamond', Georgia, serif;
    font-size: 14px;
    font-weight: 600;
    color: #3a2a0a;
    line-height: 1.35;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
  }
  .petugas-card-actions {
    display: flex;
    gap: 6px;
    margin-top: 6px;
  }
  .petugas-btn {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 5px 10px;
    border-radius: 7px;
    font-size: 11px;
    font-weight: 700;
    font-family: 'Montserrat', sans-serif;
    cursor: pointer;
    text-decoration: none;
    border: 0;
    transition: opacity .15s, transform .12s;
    white-space: nowrap;
  }
  .petugas-btn--view {
    background: rgba(184,134,11,0.12);
    color: #7a5800;
    border: 1px solid rgba(184,134,11,0.28);
  }
  .petugas-btn--view:hover { background: rgba(184,134,11,0.22); }
  .petugas-btn--dl {
    background: linear-gradient(135deg,#b8860b,#d4a017);
    color: #fff;
    box-shadow: 0 2px 6px rgba(184,134,11,0.28);
  }
  .petugas-btn--dl:hover { opacity: .88; transform: translateY(-1px); }
  .petugas-card { cursor: default !important; }

  .petugas-card-hint {
    font-size: 11px;
    color: #b8860b;
    margin-top: 2px;
    display: flex; align-items: center; gap: 4px;
  }
  .petugas-empty {
    text-align: center;
    padding: 48px 20px;
    color: #9a8060;
  }
  .petugas-empty svg { color: #c9a84c; margin-bottom: 10px; }
  .petugas-empty-title {
    font-family: 'Cormorant Garamond', Georgia, serif;
    font-size: 16px; font-weight: 600;
    color: #6a4e1a; margin: 0 0 6px;
  }

  /* ── Lightbox Petugas ─────────────────────────────────────────── */
  .petugas-lightbox {
    display: none;
    position: fixed; inset: 0; z-index: 9000;
    background: rgba(10,6,2,0.88);
    align-items: center; justify-content: center;
    padding: 16px;
  }
  .petugas-lightbox.open { display: flex; }
  .petugas-lightbox-inner {
    position: relative;
    max-width: 640px;
    width: 100%;
    animation: lbFadeIn .22s ease;
  }
  @keyframes lbFadeIn { from { opacity:0; transform:scale(.94); } to { opacity:1; transform:scale(1); } }
  .petugas-lightbox-inner img {
    display: block;
    width: 100%;
    max-height: 85vh;
    object-fit: contain;
    border-radius: 10px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.5);
  }
  .petugas-lightbox-caption {
    text-align: center;
    margin-top: 12px;
    font-family: 'Cormorant Garamond', Georgia, serif;
    font-size: 16px;
    color: rgba(255,255,255,0.85);
    letter-spacing: .02em;
  }
  .petugas-lightbox-close {
    position: absolute;
    top: -14px; right: -14px;
    width: 36px; height: 36px;
    background: #fff;
    border: none; border-radius: 50%;
    font-size: 20px; line-height: 1;
    cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    color: #333;
    box-shadow: 0 2px 8px rgba(0,0,0,0.3);
    transition: background .15s;
  }
  .petugas-lightbox-close:hover { background: #f5f5f5; }
  .petugas-lightbox-nav {
    position: absolute;
    top: 50%; transform: translateY(-50%);
    width: 40px; height: 40px;
    background: rgba(255,255,255,0.15);
    border: 1.5px solid rgba(255,255,255,0.3);
    border-radius: 50%;
    cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    color: #fff;
    transition: background .15s;
  }
  .petugas-lightbox-nav:hover { background: rgba(255,255,255,0.3); }
  .petugas-lightbox-prev { left: -54px; }
  .petugas-lightbox-next { right: -54px; }
  @media (max-width: 640px) {
    .petugas-lightbox-prev { left: 8px; }
    .petugas-lightbox-next { right: 8px; }
    .petugas-lightbox-close { top: -12px; right: -4px; }
  }
  </style>
</head>
<body>
<?php $headerTitle = 'Info Paroki'; include __DIR__ . '/../components/page_header.php'; ?>
<?php include __DIR__ . '/../components/photo_modal.php'; ?>

<!-- Lightbox Jadwal Petugas -->
<div class="petugas-lightbox" id="petugasLightbox" onclick="closePetugasLightbox(event)">
  <div class="petugas-lightbox-inner" id="petugasLightboxInner">
    <button class="petugas-lightbox-close" onclick="closePetugasLightbox(null, true)" title="Tutup">&times;</button>
    <button class="petugas-lightbox-nav petugas-lightbox-prev" id="petugasLbPrev" onclick="navPetugasLightbox(-1)" title="Sebelumnya">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="16" height="16"><polyline points="15 18 9 12 15 6"/></svg>
    </button>
    <button class="petugas-lightbox-nav petugas-lightbox-next" id="petugasLbNext" onclick="navPetugasLightbox(1)" title="Berikutnya">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="16" height="16"><polyline points="9 18 15 12 9 6"/></svg>
    </button>
    <img id="petugasLbImg" src="" alt="" style="min-height:200px;background:#1a1205;">
    <div class="petugas-lightbox-caption" id="petugasLbCaption"></div>
  </div>
</div>

<main id="main-content" style="padding:6px">

  <div class="page-hero">
    <div class="page-hero-icon">
      <img src="/img/icon/icon_square_agenda.png" alt="" loading="lazy" width="40" height="40">
    </div>
    <div class="page-hero-text">
      <h1>Informasi</h1>
      <p>Paroki Santa Maria Dengan Tidak Bernoda Asal · Tulungagung</p>
    </div>
  </div>

  <div style="padding:8px;">
    <div class="w3-container w3-card" style="background-color:#dfe0e0">

      <div class="w3-bar tab-header" style="margin-top:10px;">
        <button class="w3-bar-item w3-button tablink active-tab"
                id="agendaTabBtn-petugas"
                onclick="switchAgendaTab('petugas', this)">
          <img src="/img/icon/icon_square_petugas.png" alt="" width="14" height="14"
               style="width:14px;height:14px;object-fit:contain;vertical-align:middle;margin-right:4px;border-radius:2px;">
          Jadwal
          <?php if ($petugasCount > 0): ?>
          <span class="agenda-tab-badge"><?= $petugasCount ?></span>
          <?php endif; ?>
        </button>
        <button class="w3-bar-item w3-button tablink agenda-tab-btn"
                id="agendaTabBtn-download"
                onclick="switchAgendaTab('download', this)">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="13" height="13" class="tab-btn-icon">
            <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
            <polyline points="14 2 14 8 20 8"/>
            <line x1="12" y1="12" x2="12" y2="18"/>
            <polyline points="9 15 12 18 15 15"/>
          </svg>
          Dokumen
          <?php if ($dokumenCount > 0): ?>
          <span class="agenda-tab-badge"><?= $dokumenCount ?></span>
          <?php endif; ?>
        </button>
        <button class="w3-bar-item w3-button tablink"
                id="agendaTabBtn-info"
                onclick="switchAgendaTab('info', this)">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="13" height="13" class="tab-btn-icon">
            <rect x="3" y="4" width="18" height="18" rx="2"/>
            <line x1="3" y1="10" x2="21" y2="10"/>
            <line x1="8" y1="2" x2="8" y2="6"/>
            <line x1="16" y1="2" x2="16" y2="6"/>
            <line x1="8" y1="14" x2="13" y2="14"/>
            <line x1="8" y1="18" x2="16" y2="18"/>
          </svg>
          Agenda
        </button>

      </div>

      <!-- Tab: Agenda -->
      <div id="agendaPane-info" class="agenda-tab-pane" style="display:none;padding-top:10px;">
        <?php if ($dataError): ?>
        <div style="text-align:center;padding:40px;"><h2 style="color:#d32f2f">⚠️ <?= e($dataError) ?></h2></div>
        <?php elseif (!$data || !count($data)): ?>
        <p style="text-align:center;padding:20px;">Tidak ada data agenda.</p>
        <?php else: ?>
        <div class="agenda-modern">
          <?php foreach ($data as $row):
            $isHoliday = strtolower($row['hari_libur'] ?? '') === 'ya';
            $tanggal   = e($row['tanggal'] ?? '');
            $bulan     = e($row['bulan']   ?? '');
            $judul     = e($row['judul']   ?? '');
            $desc      = e($row['keterangan'] ?? '');
            $icon      = $row['icon'] ?? '';
          ?>
          <div class="agenda-item <?= $isHoliday ? 'agenda-item--holiday' : '' ?>">
            <div class="agenda-date">
              <span class="agenda-month"><?= $bulan ?></span>
              <span class="agenda-day <?= $isHoliday ? 'agenda-day--holiday' : '' ?>"><?= $tanggal ?></span>
            </div>
            <div class="agenda-body">
              <div class="agenda-title"><?= $judul ?></div>
              <?php if ($desc): ?><div class="agenda-desc"><?= $desc ?></div><?php endif; ?>
            </div>
            <?php if (!empty($icon)): ?>
            <img src="<?= e($icon) ?>" class="agenda-icon" alt="" loading="lazy" width="24" height="24">
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>

      <!-- Tab: Download -->
      <div id="agendaPane-download" class="agenda-tab-pane" style="display:none;padding-top:10px;">
        <?php if ($dokumenError): ?>
        <div class="dl-empty"><p style="color:#c0392b"><?= e($dokumenError) ?></p></div>
        <?php elseif (!$dokumenData || !count($dokumenData)): ?>
        <div class="dl-empty">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3" width="52" height="52" style="color:#c9a84c">
            <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/>
            <polyline points="7 10 12 15 17 10"/>
            <line x1="12" y1="15" x2="12" y2="3"/>
          </svg>
          <div class="dl-empty-title">Belum Ada Dokumen</div>
          <p style="margin:0;font-size:12px;color:#9a8060">Dokumen akan segera tersedia.</p>
        </div>
        <?php else:
          $grouped = [];
          foreach ($dokumenData as $d) {
              $kat = trim($d['kategori'] ?? '') ?: 'Umum';
              $grouped[$kat][] = $d;
          }
        ?>
        <div class="dl-wrap">
          <?php foreach ($grouped as $kat => $items): ?>
          <div class="dl-section-label"><?= e($kat) ?></div>
          <div class="dl-grid">
          <?php foreach ($items as $idx => $doc):
              $namaFile  = $doc['nama_file'] ?? '';
              $ext       = strtolower(pathinfo($namaFile, PATHINFO_EXTENSION));
              $ft        = agendaDlFileType($ext);
              $url       = '/public/downloads/' . rawurlencode($namaFile);
              $judul     = $doc['judul']     ?? $namaFile;
              $deskripsi = $doc['deskripsi'] ?? '';
              $ukuran    = $doc['ukuran']    ?? '';
              $kategori  = trim($doc['kategori'] ?? '');
              $cardId    = 'dlcard-' . md5($namaFile . $idx);
              $isPdf     = ($ext === 'pdf');
              $isReadable = in_array($ext, ['pdf','jpg','jpeg','png','webp','gif','mp4','mp3','txt']);
          ?>
            <div class="dl-card" id="<?= e($cardId) ?>" style="--dl-accent:<?= e($ft['color']) ?>">
              <div class="dl-card-top">
                <?= $ft['svg'] ?>
                <div class="dl-card-right">
                  <div class="dl-body">
                    <div class="dl-title"><?= e($judul) ?></div>
                    <?php if ($deskripsi): ?><div class="dl-desc"><?= e($deskripsi) ?></div><?php endif; ?>
                    <div class="dl-meta">
                      <?php if ($ukuran): ?><span class="dl-chip dl-chip--size"><?= e($ukuran) ?></span><?php endif; ?>
                      <?php if ($kategori): ?><span class="dl-chip dl-chip--kategori"><?= e($kategori) ?></span><?php endif; ?>
                    </div>
                  </div>
                  <div class="dl-actions">
                    <?php if ($isReadable): ?>
                    <button class="dl-btn dl-btn--read" id="<?= e($cardId) ?>-readbtn"
                            onclick="dlToggleReader('<?= e($cardId) ?>','<?= e($url) ?>','<?= e(addslashes($namaFile)) ?>','<?= e($ft['color']) ?>','<?= e(strtoupper($ext)) ?>')"
                            title="<?= $isPdf ? 'Buka PDF' : 'Baca di sini' ?>">
                      <?php if ($isPdf): ?>
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" width="12" height="12" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                      Buka
                      <?php else: ?>
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" width="12" height="12" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 014 4v14a3 3 0 00-3-3H2z"/><path d="M22 3h-6a4 4 0 00-4 4v14a3 3 0 013-3h7z"/></svg>
                      Baca
                      <?php endif; ?>
                    </button>
                    <?php endif; ?>
                    <a class="dl-btn dl-btn--dl" href="<?= e($url) ?>" download="<?= e($namaFile) ?>" target="_blank" rel="noopener" title="Unduh file">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" width="12" height="12" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                      Unduh
                    </a>
                  </div>
                </div>
              </div>
              <div class="dl-reader" id="<?= e($cardId) ?>-reader">
                <div class="dl-reader-toolbar">
                  <div class="dl-reader-toolbar-left">
                    <span class="dl-reader-badge"><?= e(strtoupper($ext)) ?></span>
                    <span class="dl-reader-filename"><?= e($judul) ?></span>
                  </div>
                  <div class="dl-reader-toolbar-right">
                    <a class="dl-reader-open-tab" id="<?= e($cardId) ?>-opentab" href="<?= e($url) ?>" target="_blank" rel="noopener noreferrer" title="Buka di tab baru">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" width="11" height="11" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                      Buka
                    </a>
                    <button class="dl-reader-close" onclick="dlCloseReader('<?= e($cardId) ?>')" title="Tutup">×</button>
                  </div>
                </div>
                <div class="dl-reader-frame-wrap" id="<?= e($cardId) ?>-framewrap">
                  <div class="dl-reader-skeleton" id="<?= e($cardId) ?>-skeleton">
                    <div class="dl-reader-spinner"></div>
                    <div class="dl-reader-skeleton-text">Memuat dokumen…</div>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
          </div>
          <div style="height:8px"></div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div><!-- /#agendaPane-download -->

      <!-- ══ Tab: Jadwal Petugas ══════════════════════════════════════════ -->
      <div id="agendaPane-petugas" class="agenda-tab-pane" style="display:block;padding-top:12px;padding-bottom:6px;">
        <?php if ($petugasError): ?>
        <div class="dl-empty"><p style="color:#c0392b"><?= e($petugasError) ?></p></div>
        <?php elseif (!$petugasData || !count($petugasData)): ?>
        <div class="petugas-empty">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3" width="52" height="52">
            <rect x="3" y="4" width="18" height="18" rx="2"/>
            <line x1="3" y1="10" x2="21" y2="10"/>
            <line x1="8" y1="2" x2="8" y2="6"/>
            <line x1="16" y1="2" x2="16" y2="6"/>
          </svg>
          <div class="petugas-empty-title">Belum Ada Jadwal Petugas</div>
          <p style="margin:0;font-size:12px;">Jadwal petugas akan segera tersedia.</p>
        </div>
        <?php else: ?>
        <div class="petugas-grid" id="petugasGrid">
          <?php foreach ($petugasData as $idx => $p):
            $namaFile = $p['nama_file'] ?? '';
            $judul    = $p['judul'] ?? 'Jadwal Petugas';
            $imgUrl   = '/public/jadwal_petugas/' . rawurlencode($namaFile);
          ?>
          <div class="petugas-card" data-idx="<?= $idx ?>">
            <img class="petugas-card-thumb" src="<?= e($imgUrl) ?>" alt="<?= e($judul) ?>" loading="lazy"
                 onerror="this.style.opacity='0'">
            <div class="petugas-card-body">
              <div class="petugas-card-title"><?= e($judul) ?></div>
              <div class="petugas-card-actions">
                <button class="petugas-btn petugas-btn--view" onclick="openPetugasLightbox(<?= $idx ?>)" title="Lihat gambar">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/></svg>
                  Buka
                </button>
                <a class="petugas-btn petugas-btn--dl" href="<?= e($imgUrl) ?>" download="<?= e($judul) ?>" title="Download gambar">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                  Download
                </a>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div><!-- /#agendaPane-petugas -->

    </div>
  </div>

</main>

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
<script>
// ── Jadwal Petugas data untuk lightbox ─────────────────────────────────
const _petugasItems = <?= json_encode(
  array_values(array_map(fn($p) => [
    'judul'    => $p['judul'] ?? 'Jadwal Petugas',
    'imgUrl'   => '/public/jadwal_petugas/' . rawurlencode($p['nama_file'] ?? ''),
  ], $petugasData ?: [])),
  JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
) ?>;
let _petugasLbIdx = 0;

function openPetugasLightbox(idx) {
  _petugasLbIdx = idx;
  _petugasLbRender();
  document.getElementById('petugasLightbox').classList.add('open');
  document.body.style.overflow = 'hidden';
}

function _petugasLbRender() {
  const item = _petugasItems[_petugasLbIdx];
  if (!item) return;
  const img = document.getElementById('petugasLbImg');
  img.src = item.imgUrl;
  img.alt = item.judul;
  document.getElementById('petugasLbCaption').textContent = item.judul;
  document.getElementById('petugasLbPrev').style.display = _petugasLbIdx > 0 ? 'flex' : 'none';
  document.getElementById('petugasLbNext').style.display = _petugasLbIdx < _petugasItems.length - 1 ? 'flex' : 'none';
}

function navPetugasLightbox(dir) {
  const next = _petugasLbIdx + dir;
  if (next < 0 || next >= _petugasItems.length) return;
  _petugasLbIdx = next;
  _petugasLbRender();
}

function closePetugasLightbox(e, force) {
  if (!force && e && e.target !== document.getElementById('petugasLightbox')) return;
  document.getElementById('petugasLightbox').classList.remove('open');
  document.body.style.overflow = '';
}

// Keyboard nav for lightbox
document.addEventListener('keydown', function(e) {
  if (!document.getElementById('petugasLightbox').classList.contains('open')) return;
  if (e.key === 'ArrowLeft')  navPetugasLightbox(-1);
  if (e.key === 'ArrowRight') navPetugasLightbox(1);
  if (e.key === 'Escape')     closePetugasLightbox(null, true);
});

function switchAgendaTab(name, btn) {
  document.querySelectorAll('.agenda-tab-pane').forEach(function(p) { p.style.display = 'none'; });
  document.querySelectorAll('.tab-header .tablink').forEach(function(b) { b.classList.remove('active-tab'); });
  var pane = document.getElementById('agendaPane-' + name);
  if (pane) pane.style.display = 'block';
  if (btn)  btn.classList.add('active-tab');
}

// ── Download reader (shared logic) ────────────────────────────────────
(function(){
  var _openCard = null;
  function _isMobile() { return (window.innerWidth < 768) || /Android|iPhone|iPad|iPod|Mobile/i.test(navigator.userAgent); }
  var _viewerMap = { pdf:'pdf', jpg:'img', jpeg:'img', png:'img', webp:'img', gif:'img', mp4:'video', mov:'video', mp3:'audio', wav:'audio', txt:'iframe' };

  window.dlToggleReader = function(cardId, url, filename, color, extLabel) {
    var ext = extLabel.toLowerCase(), type = _viewerMap[ext] || 'fallback';
    var reader = document.getElementById(cardId + '-reader');
    if (!reader) return;
    var isOpen = reader.classList.contains('open');
    if (_openCard && _openCard !== cardId) dlCloseReader(_openCard);
    if (isOpen) { dlCloseReader(cardId); return; }
    _openCard = cardId;
    reader.classList.add('open');
    var readBtn = document.getElementById(cardId + '-readbtn');
    if (readBtn) readBtn.classList.add('active');
    var card = document.getElementById(cardId);
    setTimeout(function(){ card && card.scrollIntoView({behavior:'smooth', block:'start'}); }, 120);
    var wrap = document.getElementById(cardId + '-framewrap');
    var skeleton = document.getElementById(cardId + '-skeleton');
    var openTab  = document.getElementById(cardId + '-opentab');
    if (openTab) { openTab.href = url; openTab.classList.add('visible'); }
    if (type === 'pdf')    { _buildPdfViewer(wrap, skeleton, url, filename, color, extLabel); return; }
    if (type === 'iframe') { _buildIframeViewer(wrap, skeleton, url, filename, color, extLabel); return; }
    if (type === 'img')    { _buildImgViewer(wrap, skeleton, url, filename, color, extLabel); return; }
    if (type === 'video')  { _buildVideoViewer(wrap, skeleton, url); return; }
    if (type === 'audio')  { _buildAudioViewer(wrap, skeleton, url); return; }
    _buildFallback(wrap, skeleton, url, filename, color, extLabel);
  };

  window.dlCloseReader = function(cardId) {
    var reader  = document.getElementById(cardId + '-reader');
    var readBtn = document.getElementById(cardId + '-readbtn');
    var openTab = document.getElementById(cardId + '-opentab');
    if (!reader) return;
    reader.classList.remove('open');
    if (readBtn) readBtn.classList.remove('active');
    if (openTab) { openTab.classList.remove('visible'); openTab.href = '#'; }
    var wrap = document.getElementById(cardId + '-framewrap');
    if (wrap) {
      wrap.querySelectorAll('.dl-reader-frame,.dl-reader-fallback,video,audio').forEach(function(v){ v.remove(); });
      var sk = document.getElementById(cardId + '-skeleton');
      if (sk) sk.style.display = 'flex';
    }
    if (_openCard === cardId) _openCard = null;
  };

  function _buildPdfViewer(wrap, skeleton, url, filename, color, extLabel) {
    if (_isMobile()) {
      var absUrlM = (url.indexOf('http') === 0) ? url : location.origin + url;
      location.href = '/public/pdfviewer/viewer.php?file=' + encodeURIComponent(absUrlM) + '&back=' + encodeURIComponent(location.href);
      return;
    }
    var absUrl = (url.indexOf('http') === 0) ? url : location.origin + url;
    var fr = document.createElement('iframe');
    fr.className = 'dl-reader-frame';
    fr.src = absUrl + '#toolbar=1&navpanes=0&scrollbar=1';
    fr.setAttribute('allowfullscreen', '');
    fr.style.cssText = 'border:0;width:100%;height:100%;';
    var _done = false;
    fr.onload = function() { if (_done) return; _done = true; fr.classList.add('loaded'); if (skeleton) skeleton.style.display = 'none'; };
    fr.onerror = function() { fr.remove(); _buildFallback(wrap, skeleton, url, filename, color, extLabel); };
    wrap.appendChild(fr);
    setTimeout(function() { if (!_done) { fr.remove(); _buildFallback(wrap, skeleton, url, filename, color, extLabel); } }, 12000);
  }
  function _buildIframeViewer(wrap, skeleton, url, filename, color, extLabel) {
    var fr = document.createElement('iframe'); fr.className = 'dl-reader-frame'; fr.src = url; fr.setAttribute('allowfullscreen', '');
    fr.onload = function() { fr.classList.add('loaded'); if (skeleton) skeleton.style.display = 'none'; };
    fr.onerror = function() { fr.remove(); _buildFallback(wrap, skeleton, url, filename, color, extLabel); };
    wrap.appendChild(fr);
  }
  function _buildImgViewer(wrap, skeleton, url, filename, color, extLabel) {
    var img = document.createElement('img'); img.className = 'dl-reader-frame';
    img.style.cssText = 'object-fit:contain;background:#111;width:100%;height:100%;'; img.src = url; img.alt = filename;
    img.onload  = function() { img.classList.add('loaded'); if (skeleton) skeleton.style.display = 'none'; };
    img.onerror = function() { img.remove(); _buildFallback(wrap, skeleton, url, filename, color, extLabel); };
    wrap.appendChild(img);
  }
  function _buildVideoViewer(wrap, skeleton, url) {
    var vid = document.createElement('video'); vid.className = 'dl-reader-frame';
    vid.style.cssText = 'background:#000;width:100%;height:100%;max-height:100%;object-fit:contain;';
    vid.controls = true; vid.playsInline = true; vid.src = url;
    vid.oncanplay = function() { vid.classList.add('loaded'); if (skeleton) skeleton.style.display = 'none'; };
    wrap.appendChild(vid);
  }
  function _buildAudioViewer(wrap, skeleton, url) {
    if (skeleton) skeleton.style.display = 'none';
    var aud = document.createElement('div');
    aud.style.cssText = 'display:flex;align-items:center;justify-content:center;height:100%;padding:30px 20px;';
    aud.innerHTML = '<audio controls style="width:100%;max-width:360px"><source src="'+url+'"></audio>';
    wrap.appendChild(aud);
  }
  function _buildFallback(wrap, skeleton, url, filename, color, extLabel) {
    if (skeleton) skeleton.style.display = 'none';
    var fb = document.createElement('div'); fb.className = 'dl-reader-fallback';
    fb.innerHTML = '<div class="dl-reader-fallback-icon" style="background:'+color+'">'+extLabel+'</div>'
      +'<div class="dl-reader-fallback-title">Pratinjau tidak tersedia</div>'
      +'<div class="dl-reader-fallback-sub">Buka atau unduh file langsung.</div>'
      +'<div style="display:flex;gap:8px;flex-wrap:wrap;justify-content:center;margin-top:8px">'
      +'<a href="'+url+'" target="_blank" rel="noopener" class="dl-reader-fallback-dl">'
      +'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" width="13" height="13"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg> Buka di Tab Baru</a>'
      +'<a href="'+url+'" download="'+filename+'" rel="noopener" class="dl-reader-fallback-dl" style="background:#2e7d32">'
      +'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" width="13" height="13"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg> Unduh</a></div>';
    wrap.appendChild(fb);
  }
})();
</script>
</body>
</html>