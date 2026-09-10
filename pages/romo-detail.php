<?php
require_once __DIR__ . '/../includes/functions.php';

$slug = trim($_GET['slug'] ?? '');
if ($slug === '') { http_response_code(404); include __DIR__ . '/../error.php'; exit; }

// Semua baris (masa jabatan) milik Romo dengan slug ini, urut dari yang paling lama
$rows = fetchSupabaseCached('romo_paroki', ['slug' => $slug], 'tanggal_mulai.asc');

if (!is_array($rows) || empty($rows)) {
    http_response_code(404); include __DIR__ . '/../error.php'; exit;
}

$today = date('Y-m-d');

function romo_isAktifMenjabat(array $r, string $today): bool {
    $aktif = !array_key_exists('aktif', $r) || $r['aktif'] === true || $r['aktif'] === null;
    if (!$aktif) return false;
    $mulai   = $r['tanggal_mulai']   ?? '';
    $selesai = $r['tanggal_selesai'] ?? '';
    if ($mulai && $mulai > $today) return false;
    if ($selesai && $selesai < $today) return false;
    return true;
}

// Urutkan riwayat dari yang terbaru
$history = $rows;
usort($history, fn($a, $b) => strcmp($b['tanggal_mulai'] ?? '', $a['tanggal_mulai'] ?? ''));

// Baris yang sedang aktif menjabat (kalau ada), fallback ke baris paling baru
$current = null;
foreach ($history as $r) { if (romo_isAktifMenjabat($r, $today)) { $current = $r; break; } }
if (!$current) $current = $history[0] ?? null;

if (!$current) { http_response_code(404); include __DIR__ . '/../error.php'; exit; }

// Biografi: ambil dari baris manapun yang diisi, prioritaskan baris aktif
$riwayatTeks = trim($current['riwayat'] ?? '');
if ($riwayatTeks === '') {
    foreach ($history as $r) {
        if (trim($r['riwayat'] ?? '') !== '') { $riwayatTeks = trim($r['riwayat']); break; }
    }
}

$nama    = $current['nama'] ?? 'Romo';
$jabatan = $current['jabatan'] ?? '';
// Bangun URL foto person dari R2 CDN (bukan /img/person/ lokal lagi).
$foto    = !empty($current['foto'])
    ? personPhotoUrl($current['foto'])
    : assetUrl('parokitulungagung.webp');
$isMenjabat = romo_isAktifMenjabat($current, $today);

function romo_fmtTgl(?string $t): string {
    if (!$t) return '';
    $ts = strtotime($t);
    if (!$ts) return $t;
    $bulan = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    return (int)date('j', $ts) . ' ' . $bulan[(int)date('n', $ts)] . ' ' . date('Y', $ts);
}

// Fallback gambar: kalau personPhotoUrl() mengembalikan path relatif
// (R2_CDN_URL tidak terdefinisi), pakai placeholder lokal
$fotoAbs  = (strpos($foto, '://') !== false) ? $foto : ('https://www.parokitulungagung.org' . $foto);
$seo = [
    'title'       => $nama . ' — ' . $jabatan . ' Paroki Tulungagung',
    'description' => trim(($nama . ' — ' . $jabatan . ' di Paroki Santa Maria Dengan Tidak Bernoda Asal Tulungagung. ' . mb_substr($riwayatTeks, 0, 120))),
    'canonical'   => 'https://www.parokitulungagung.org/romo/' . rawurlencode($slug),
    'keywords'    => 'romo paroki tulungagung, ' . strtolower($jabatan) . ' tulungagung, ' . strtolower($nama) . ', gereja katolik tulungagung',
    'type'        => 'profile',
    'image'       => $fotoAbs,
];
$breadcrumbs = [
    ['name' => 'Beranda', 'url' => 'https://www.parokitulungagung.org'],
    ['name' => $nama,     'url' => 'https://www.parokitulungagung.org/romo/' . rawurlencode($slug)],
];
$extraCss = ['/css/content.css'];
?>
<!doctype html>
<html lang="id">
<head>
  <?php include __DIR__ . '/../components/seo_head.php'; ?>
  <script type="application/ld+json">
  <?= json_encode([
      '@context' => 'https://schema.org', '@type' => 'Person',
      'name'      => $nama,
      'jobTitle'  => $jabatan,
      'image'     => $fotoAbs,
      'url'       => 'https://www.parokitulungagung.org/romo/' . $slug,
      'affiliation' => [
          '@type' => 'Church',
          'name'  => 'Paroki Santa Maria Dengan Tidak Bernoda Asal Tulungagung',
          'url'   => 'https://www.parokitulungagung.org',
      ],
  ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?>
  </script>
  <style>
    .romo-detail-hero{display:flex;gap:22px;align-items:center;flex-wrap:wrap;background:#fff;border:1px solid rgba(201,162,58,.2);border-radius:16px;padding:24px;box-shadow:0 4px 16px rgba(91,44,111,.06);margin:0 6px 20px}
    .romo-detail-photo{width:120px;height:120px;border-radius:50%;overflow:hidden;flex-shrink:0;border:3px solid rgba(201,162,58,.35);box-shadow:0 6px 20px rgba(30,16,8,.15)}
    .romo-detail-photo img{width:100%;height:100%;object-fit:cover;display:block}
    .romo-detail-name{font-family:'Cormorant Garamond',Georgia,serif;font-size:1.7rem;font-weight:600;color:#3b2312;margin:0 0 6px}
    .romo-detail-role{display:inline-block;font-size:.68rem;font-weight:600;letter-spacing:.14em;text-transform:uppercase;color:#a9821f;background:rgba(201,162,58,.1);border:1px solid rgba(201,162,58,.3);padding:5px 14px;border-radius:20px;margin-bottom:8px}
    .romo-detail-status{font-size:.85rem;color:#6b6b6b;margin-top:4px}
    .romo-detail-status.aktif{color:#2e8b57;font-weight:600}
    .romo-section{background:#fff;border:1px solid rgba(201,162,58,.2);border-radius:16px;padding:22px 24px;margin:0 6px 20px;box-shadow:0 4px 16px rgba(91,44,111,.05)}
    .romo-section h2{font-family:'Cormorant Garamond',Georgia,serif;font-size:1.25rem;color:#3b2312;margin:0 0 14px;padding-bottom:10px;border-bottom:1px solid rgba(201,162,58,.18)}
    .romo-bio{font-size:.95rem;line-height:1.75;color:#3a3a3a;white-space:pre-line}
    .romo-timeline{list-style:none;margin:0;padding:0}
    .romo-timeline li{position:relative;padding:0 0 18px 24px;border-left:2px solid rgba(201,162,58,.3)}
    .romo-timeline li:last-child{border-left-color:transparent;padding-bottom:0}
    .romo-timeline li::before{content:'';position:absolute;left:-6px;top:2px;width:10px;height:10px;border-radius:50%;background:#c9a23a}
    .romo-timeline li.now::before{background:#2e8b57}
    .romo-timeline .tl-jabatan{font-weight:600;color:#3b2312;font-size:.95rem}
    .romo-timeline .tl-masa{font-size:.8rem;color:#7a7a7a;margin-top:2px}
    .romo-timeline .tl-badge{display:inline-block;font-size:.65rem;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:#2e8b57;background:rgba(46,139,87,.1);border-radius:10px;padding:2px 8px;margin-left:8px}
  </style>
</head>
<body>
<?php $headerTitle = $nama; include __DIR__ . '/../components/page_header.php'; ?>
<?php include __DIR__ . '/../components/photo_modal.php'; ?>

<main id="main-content" style="padding:6px">

  <div class="page-hero">
    <div class="page-hero-icon">
      <img src="<?= iconUrl('icon_square_dpp.png') ?>" alt="" loading="lazy" width="40" height="40" onerror="this.style.display='none'">
    </div>
    <div class="page-hero-text">
      <h1>Profil &amp; Riwayat Romo</h1>
      <p>Paroki Santa Maria Dengan Tidak Bernoda Asal · Tulungagung</p>
    </div>
  </div>

  <div class="romo-detail-hero">
    <div class="romo-detail-photo">
      <img src="<?= e($foto) ?>" alt="<?= e($nama) ?>" width="120" height="120" loading="eager" decoding="async" onerror="this.src='<?= e(assetUrl('parokitulungagung.png')) ?>'">
    </div>
    <div>
      <span class="romo-detail-role"><?= e($jabatan) ?></span>
      <h1 class="romo-detail-name"><?= e($nama) ?></h1>
      <div class="romo-detail-status <?= $isMenjabat ? 'aktif' : '' ?>">
        <?php if ($isMenjabat): ?>
          &#10003; Saat ini masih menjabat sebagai <?= e($jabatan) ?>
          <?php if (!empty($current['tanggal_mulai'])): ?>
            &middot; sejak <?= e(romo_fmtTgl($current['tanggal_mulai'])) ?>
          <?php endif; ?>
        <?php else: ?>
          Purna tugas sebagai <?= e($jabatan) ?> di Paroki Tulungagung
          <?php if (!empty($current['tanggal_mulai'])): ?>
            (<?= e(romo_fmtTgl($current['tanggal_mulai'])) ?> &ndash; <?= $current['tanggal_selesai'] ? e(romo_fmtTgl($current['tanggal_selesai'])) : 'sekarang' ?>)
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <?php if ($riwayatTeks !== ''): ?>
  <div class="romo-section">
    <h2>Riwayat &amp; Biografi Singkat</h2>
    <div class="romo-bio"><?= nl2br(e($riwayatTeks)) ?></div>
  </div>
  <?php endif; ?>

  <div class="romo-section">
    <h2>Riwayat Jabatan di Paroki Tulungagung</h2>
    <ul class="romo-timeline">
      <?php foreach ($history as $h):
          $hAktif = romo_isAktifMenjabat($h, $today);
      ?>
      <li class="<?= $hAktif ? 'now' : '' ?>">
        <div class="tl-jabatan">
          <?= e($h['jabatan'] ?? '') ?>
          <?php if ($hAktif): ?><span class="tl-badge">Sekarang</span><?php endif; ?>
        </div>
        <div class="tl-masa">
          <?= e(romo_fmtTgl($h['tanggal_mulai'] ?? '')) ?>
          &ndash;
          <?= !empty($h['tanggal_selesai']) ? e(romo_fmtTgl($h['tanggal_selesai'])) : 'Sekarang' ?>
        </div>
      </li>
      <?php endforeach; ?>
    </ul>
  </div>

  <div style="padding:0 6px 10px">
    <a href="/" style="display:inline-flex;align-items:center;gap:6px;font-size:.85rem;color:#a9821f;text-decoration:none;font-weight:600">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
      Kembali ke Beranda
    </a>
  </div>

</main>

<?php include __DIR__ . '/../components/footer.php'; ?>
<script src="/js/app.js?v=<?= substr(md5_file(__DIR__ . '/../js/app.js'), 0, 8) ?>"></script>
</body>
</html>
