<?php
require_once __DIR__ . '/../includes/functions.php';

// ─── Image SEO Helper ─────────────────────────────────────────────────────────
if (!function_exists('fetchImageSeoByPrefix')) {
    function fetchImageSeoByPrefix(string $prefix): array {
        if (!defined('SUPABASE_URL') || !defined('SUPABASE_ANON_KEY')) return [];
        $ck = 'img_seo_pfx_' . md5($prefix);
        $cv = function_exists('cache_get') ? cache_get($ck) : null;
        if ($cv !== null) return $cv;
        $url = rtrim(SUPABASE_URL, '/') . '/rest/v1/image_seo'
             . '?image_url=like.' . rawurlencode($prefix . '%')
             . '&select=image_url,alt_text,caption,title_attr,schema_description,image_keywords'
             . '&limit=500';
        $ctx = stream_context_create([
            'http' => ['header' => "apikey: " . SUPABASE_ANON_KEY . "\r\nAuthorization: Bearer " . SUPABASE_ANON_KEY . "\r\nAccept: application/json\r\n", 'timeout' => 5, 'ignore_errors' => true],
            'ssl'  => ['verify_peer' => true],
        ]);
        $result = [];
        $res = @file_get_contents($url, false, $ctx);
        if ($res) {
            $rows = json_decode($res, true);
            if (is_array($rows)) foreach ($rows as $row) if (!empty($row['image_url'])) $result[$row['image_url']] = $row;
        }
        if (function_exists('cache_set')) cache_set($ck, $result, 600);
        return $result;
    }
}
if (!function_exists('getImgSeo')) {
    function getImgSeo(string $imgUrl, array $map): array {
        if (!$imgUrl || empty($map)) return [];
        if (isset($map[$imgUrl])) return $map[$imgUrl];
        $p = parse_url($imgUrl, PHP_URL_PATH) ?: $imgUrl;
        foreach ($map as $u => $d) { if ((parse_url($u, PHP_URL_PATH) ?: $u) === $p) return $d; }
        return [];
    }
}

// ── Data Wilayah dari Supabase (server-side) ──────────────────────────
$data         = fetchSupabaseCached('daftar_wilayah', [], 'Wilayah.asc');
$dataError    = ($data === null) ? 'Gagal mengambil data dari server.' : null;
// URL R2 prefix untuk foto person
$personR2Prefix = (defined('R2_CDN_URL') ? rtrim(R2_CDN_URL, '/') : '')
    . '/' . trim(defined('R2_PERSON_PREFIX') ? R2_PERSON_PREFIX : 'person/', '/');
$lingkSeoMap  = fetchImageSeoByPrefix($personR2Prefix . '/');

// ── Periode helpers ───────────────────────────────────────────────────
function wil_getAllPeriodes(array $data, string $col = 'Periode'): array {
    $set = [];
    foreach ($data as $row) {
        $p = trim($row[$col] ?? '');
        if (preg_match('/^\d{4}-\d{4}$/', $p)) $set[$p] = true;
    }
    $list = array_keys($set);
    rsort($list);
    return $list;
}
function wil_resolveActivePeriode(array $data, string $col = 'Periode'): string {
    $all = wil_getAllPeriodes($data, $col);
    $fromUrl = trim($_GET['periode'] ?? '');
    if ($fromUrl && preg_match('/^\d{4}-\d{4}$/', $fromUrl) && in_array($fromUrl, $all)) return $fromUrl;
    $year = (int) date('Y');
    foreach ($all as $p) {
        [$ps, $pe] = explode('-', $p);
        if ($year >= (int)$ps && $year <= (int)$pe) return $p;
    }
    return $all[0] ?? '';
}
function wil_filterByPeriode(array $data, string $periode, string $col = 'Periode'): array {
    if (!$periode) return $data;
    return array_values(array_filter($data, fn($row) => ($row[$col] ?? '') === '' || ($row[$col] ?? '') === $periode));
}

$allPeriodes   = is_array($data) ? wil_getAllPeriodes($data) : [];
$activePeriode = is_array($data) ? wil_resolveActivePeriode($data) : '';
$filtered      = is_array($data) ? wil_filterByPeriode($data, $activePeriode) : [];

$wil1  = array_filter($filtered, fn($d) => ($d['Wilayah'] ?? '') === 'Wilayah 1');
$wil2  = array_filter($filtered, fn($d) => ($d['Wilayah'] ?? '') === 'Wilayah 2');
$wil3  = array_filter($filtered, fn($d) => ($d['Wilayah'] ?? '') === 'Wilayah 3');
$stasi = array_filter($filtered, fn($d) => ($d['Wilayah'] ?? '') === 'Stasi');

// ── SEO ───────────────────────────────────────────────────────────────
$seo = [
    'title'       => 'Profil Wilayah & Lingkungan Umat – Paroki Tulungagung',
    'description' => 'Profil wilayah dan lingkungan umat Paroki Santa Maria Dengan Tidak Bernoda Asal Tulungagung. Daftar koordinator dan ketua lingkungan.',
    'canonical'   => 'https://www.parokitulungagung.org/profil-lingkungan',
    'keywords'    => 'profil wilayah paroki tulungagung, lingkungan umat gereja, ketua lingkungan, stasi paroki tulungagung',
    'type'        => 'website',
];
$breadcrumbs = [
    ['name' => 'Beranda',  'url' => 'https://www.parokitulungagung.org'],
    ['name' => 'Wilayah',  'url' => 'https://www.parokitulungagung.org/profil-lingkungan'],
];
$extraCss = ['/css/content.css'];
?>
<!doctype html>
<html lang="id">
<head>
  <?php include __DIR__ . '/../components/seo_head.php'; ?>
  <script type="application/ld+json">
  <?= json_encode([
    '@context'  => 'https://schema.org',
    '@type'     => 'WebPage',
    'name'      => 'Profil Wilayah & Lingkungan Paroki Tulungagung',
    'description' => 'Profil wilayah dan lingkungan umat Paroki Santa Maria Dengan Tidak Bernoda Asal Tulungagung.',
    'url'       => 'https://www.parokitulungagung.org/profil-lingkungan',
    'inLanguage'=> 'id',
    'isPartOf'  => ['@id' => 'https://www.parokitulungagung.org/#website'],
    'breadcrumb'=> [
        '@type' => 'BreadcrumbList',
        'itemListElement' => [
            ['@type'=>'ListItem','position'=>1,'name'=>'Beranda','item'=>'https://www.parokitulungagung.org'],
            ['@type'=>'ListItem','position'=>2,'name'=>'Profil Wilayah','item'=>'https://www.parokitulungagung.org/profil-lingkungan'],
        ],
    ],
  ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT); ?>
  </script>
</head>
<body>
<?php $headerTitle = 'Wilayah'; include __DIR__ . '/../components/page_header.php'; ?>
<?php include __DIR__ . '/../components/photo_modal.php'; ?>

<main id="main-content" style="padding:6px">


  <div class="page-hero">
    <div class="page-hero-icon">
      <img src="<?= iconUrl('icon_square_lingkungan.png') ?>" alt="" loading="lazy" width="40" height="40">
    </div>
    <div class="page-hero-text">
      <h1>PROFIL WILAYAH</h1>
      <p>Paroki Santa Maria Dengan Tidak Bernoda Asal · Tulungagung</p>
    </div>
  </div>

  <?php if ($dataError): ?>
  <div style="text-align:center;padding:40px;"><h2 style="color:#d32f2f">⚠️ <?= e($dataError) ?></h2></div>
  <?php else: ?>

  <div style="padding:0 6px">
    <?php if (count($allPeriodes) > 1): ?>
    <div class="periode-selector-wrap" id="periodeWrap">
      <div class="periode-badge periode-badge--clickable" id="periodeTrigger"
           onclick="togglePeriodeDropdown(event)" role="button" tabindex="0"
           aria-haspopup="listbox" aria-expanded="false"
           title="Klik untuk ganti periode kepengurusan">
        <span class="periode-dot"></span>
        <span class="periode-label">Periode <?= e(str_replace('-', '–', $activePeriode)) ?></span>
        <svg class="periode-chevron" id="periodeChevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
      </div>
      <div class="periode-dropdown" id="periodeDropdown" role="listbox">
        <div class="periode-dropdown-header">Pilih Periode Kepengurusan</div>
        <?php
        $year = (int) date('Y');
        foreach ($allPeriodes as $p):
            $isActive = ($p === $activePeriode);
            [$ps, $pe] = explode('-', $p);
            $isCurrent = ($year >= (int)$ps && $year <= (int)$pe);
        ?>
        <a href="/profil-lingkungan?periode=<?= urlencode($p) ?>"
           class="periode-dropdown-item <?= $isActive ? 'periode-dropdown-item--active' : '' ?>"
           role="option" aria-selected="<?= $isActive ? 'true' : 'false' ?>">
          <span class="periode-dropdown-check">
            <?php if ($isActive): ?><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="13" height="13" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg><?php endif; ?>
          </span>
          <span class="periode-dropdown-text"><?= e(str_replace('-', '–', $p)) ?></span>
          <?php if ($isCurrent): ?><span class="periode-now-badge">Saat ini</span><?php endif; ?>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
    <?php elseif ($activePeriode): ?>
    <div class="periode-selector-wrap">
      <div class="periode-badge">
        <span class="periode-dot"></span>
        <span class="periode-label">Periode <?= e(str_replace('-', '–', $activePeriode)) ?></span>
      </div>
    </div>
    <?php endif; ?>

    <ul class="tabs" id="tabs-wilayah">
      <li><a href="#wil1">Wil 1</a></li>
      <li><a href="#wil2">Wil 2</a></li>
      <li><a href="#wil3">Wil 3</a></li>
      <li><a href="#stasi">Stasi</a></li>
    </ul>
    <div class="tabcontents" id="tabcontents-wilayah" style="padding:2rem;border-radius:0 12px 12px 12px;box-shadow:0 4px 16px rgba(91,44,111,0.12)">
      <?php
      function renderWilayahGroup(array $items, array $seoMap = []): void {
          $items = array_values($items);
          if (!$items) { echo '<p>Tidak ada data.</p>'; return; }
          $namaWilayah = $items[0]['Wilayah'] ?? '';
          $koorNama    = trim($items[0]['KoordinatorNama'] ?? $items[0]['Koordinator'] ?? '');
          $koorFoto    = trim($items[0]['KoordinatorFoto'] ?? '');
          $fotoKoord   = $koorFoto ? personPhotoUrl($koorFoto) : '';
          $ocKoord     = $fotoKoord ? "ShowPhotoBox('".e($koorNama)."','$fotoKoord','".e($namaWilayah)."','Koordinator')" : '';
          // ── SEO koordinator ────────────────────────────────────────────
          $seoKoord  = $fotoKoord ? getImgSeo($fotoKoord, $seoMap) : [];
          $altKoord  = ($seoKoord['alt_text']   ?? '') ?: ($koorNama ? $koorNama . ' — Koordinator ' . $namaWilayah : '');
          $titleKoord= ($seoKoord['title_attr'] ?? '') ?: ($koorNama . ' — Paroki SMDTBA Tulungagung');
      ?>
      <div class="profile-leader-card" style="margin-bottom:12px;cursor:pointer;" <?= $ocKoord ? "onclick=\"$ocKoord\"" : '' ?>>
        <div class="profile-leader-info">
          <div style="font-family:'Cormorant Garamond',serif;font-size:17px;font-weight:700;color:#564938;"><?= e($namaWilayah) ?></div>
          <?php if ($koorNama): ?><div style="margin-top:4px;"><span class="profile-leader-role">Koordinator</span> <span class="profile-leader-name"><?= e($koorNama) ?></span></div><?php endif; ?>
        </div>
        <?php if ($fotoKoord): ?>
        <img src="<?= e($fotoKoord) ?>" class="profile-leader-img" alt="<?= e($altKoord) ?>" title="<?= e($titleKoord) ?>" loading="lazy" decoding="async" width="120" height="120" onerror="this.style.opacity='0.3'">
        <?php elseif ($koorNama): ?>
        <div class="profile-leader-img" style="display:flex;align-items:center;justify-content:center;background:rgba(201,168,76,.15);color:#c9a84c;font-size:20px;font-weight:700;font-family:'Cormorant Garamond',serif"><?= strtoupper(mb_substr($koorNama,0,1)) ?></div>
        <?php endif; ?>
      </div>
      <?php foreach ($items as $item):
          $ketua   = trim($item['Ketua'] ?? '');
          $lingk   = trim($item['Lingkungan'] ?? '');
          $fotoCol = trim($item['Foto'] ?? '');
          $foto    = $fotoCol ? personPhotoUrl($fotoCol) : '';
          // ── SEO ketua lingkungan ────────────────────────────────────
          $seoKetua  = $foto ? getImgSeo($foto, $seoMap) : [];
          $altKetua  = ($seoKetua['alt_text']   ?? '') ?: ($ketua ? $ketua . ' — Ketua ' . $lingk : '');
          $titleKetua= ($seoKetua['title_attr'] ?? '') ?: ($ketua . ' — Paroki SMDTBA Tulungagung');
      ?>
      <div class="profile-item" onclick="<?= $foto ? "ShowPhotoBox('".e($ketua)."','".e($foto)."','".e($lingk)."','Ketua')" : '' ?>">
        <?php if ($foto): ?>
        <img class="profile-item-img" src="<?= e($foto) ?>" alt="<?= e($altKetua) ?>" title="<?= e($titleKetua) ?>" loading="lazy" decoding="async" width="80" height="80" onerror="this.style.opacity='0.3'">
        <?php else: ?>
        <div class="profile-item-img" style="display:flex;align-items:center;justify-content:center;background:rgba(201,168,76,.1);color:#c9a84c;font-size:18px;font-weight:700;font-family:'Cormorant Garamond',serif"><?= strtoupper(mb_substr($ketua,0,1)) ?></div>
        <?php endif; ?>
        <div class="profile-item-info">
          <?php if ($lingk): ?><div class="profile-item-role"><?= e($lingk) ?></div><?php endif; ?>
          <div class="profile-item-name">Ketua: <b><?= e($ketua) ?></b></div>
        </div>
      </div>
      <?php endforeach; ?>
      <?php } ?>

      <div id="wil1"><?php renderWilayahGroup(array_values($wil1), $lingkSeoMap); ?></div>
      <div id="wil2"><?php renderWilayahGroup(array_values($wil2), $lingkSeoMap); ?></div>
      <div id="wil3"><?php renderWilayahGroup(array_values($wil3), $lingkSeoMap); ?></div>
      <div id="stasi"><?php renderWilayahGroup(array_values($stasi), $lingkSeoMap); ?></div>
    </div>
  </div>

  <?php endif; ?>

</main>

<?php include __DIR__ . '/../components/footer.php'; ?>
<script src="/js/app.js?v=<?= substr(md5_file(__DIR__ . '/../js/app.js'), 0, 8) ?>"></script>
<script>
(function () {
  var _open = false;
  window.togglePeriodeDropdown = function (e) {
    e && e.stopPropagation(); _open = !_open;
    var trigger=document.getElementById('periodeTrigger'),dropdown=document.getElementById('periodeDropdown'),chevron=document.getElementById('periodeChevron');
    if(!dropdown) return;
    trigger&&trigger.setAttribute('aria-expanded',_open);
    trigger&&trigger.classList.toggle('periode-badge--open',_open);
    chevron&&chevron.classList.toggle('periode-chevron--open',_open);
    dropdown.classList.toggle('periode-dropdown--open',_open);
  };
  document.addEventListener('click', function (e) {
    if(!_open) return;
    var wrap=document.getElementById('periodeWrap');
    if(wrap&&!wrap.contains(e.target)){
      _open=false;
      var trigger=document.getElementById('periodeTrigger'),dropdown=document.getElementById('periodeDropdown'),chevron=document.getElementById('periodeChevron');
      trigger&&trigger.setAttribute('aria-expanded',false);
      trigger&&trigger.classList.remove('periode-badge--open');
      chevron&&chevron.classList.remove('periode-chevron--open');
      dropdown&&dropdown.classList.remove('periode-dropdown--open');
    }
  });
  var trigger=document.getElementById('periodeTrigger');
  trigger&&trigger.addEventListener('keydown',function(e){
    if(e.key==='Enter'||e.key===' '){e.preventDefault();window.togglePeriodeDropdown(e);}
    if(e.key==='Escape'&&_open) window.togglePeriodeDropdown(e);
  });
})();
</script>
</body>
</html>