<?php
require_once __DIR__ . '/../includes/functions.php';

$data      = fetchSupabaseCached('kepengurusan_dpp_bgkp', [], 'id.asc');
$dataError = ($data === null) ? 'Gagal mengambil data dari server.' : null;

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
// URL R2 prefix untuk foto person (lihat includes/config.php R2_CDN_URL)
$personR2Prefix = (defined('R2_CDN_URL') ? rtrim(R2_CDN_URL, '/') : '')
    . '/' . trim(defined('R2_PERSON_PREFIX') ? R2_PERSON_PREFIX : 'person/', '/');
$dppSeoMap = fetchImageSeoByPrefix($personR2Prefix . '/');

function dpp_getAllPeriodes(array $data): array {
    $set = [];
    foreach ($data as $row) { $p=trim($row['Periode']??''); if(preg_match('/^\d{4}-\d{4}$/',$p)) $set[$p]=true; }
    $list = array_keys($set); rsort($list); return $list;
}
function dpp_resolveActivePeriode(array $data): string {
    $all = dpp_getAllPeriodes($data);
    $fromUrl = trim($_GET['periode'] ?? '');
    if ($fromUrl && preg_match('/^\d{4}-\d{4}$/',$fromUrl) && in_array($fromUrl,$all)) return $fromUrl;
    $year = (int) date('Y');
    foreach ($all as $p) { [$ps,$pe]=explode('-',$p); if($year>=(int)$ps&&$year<=(int)$pe) return $p; }
    return $all[0] ?? '';
}

$allPeriodes   = is_array($data) ? dpp_getAllPeriodes($data) : [];
$activePeriode = is_array($data) ? dpp_resolveActivePeriode($data) : '';
$filtered      = is_array($data) ? array_filter($data, fn($r) => ($r['Periode']??'')=='' || ($r['Periode']??'')===$activePeriode) : [];

$seo = [
    'title'       => 'DPP & BGKP – Dewan Pastoral Paroki Tulungagung',
    'description' => 'Kepengurusan Dewan Pastoral Paroki (DPP) dan BGKP Paroki Santa Maria Dengan Tidak Bernoda Asal Tulungagung.',
    'canonical'   => 'https://www.parokitulungagung.org/profil-dpp',
    'keywords'    => 'dewan pastoral paroki tulungagung, dpp bgkp, kepengurusan gereja katolik tulungagung, paroki smdtba',
    'type'        => 'website',
];
$breadcrumbs = [
    ['name'=>'Beranda','url'=>'https://www.parokitulungagung.org'],
    ['name'=>'DPP & BGKP','url'=>'https://www.parokitulungagung.org/profil-dpp'],
];
$extraCss = ['/css/content.css'];
?>
<!doctype html>
<html lang="id">
<head>
  <?php include __DIR__ . '/../components/seo_head.php'; ?>
  <script type="application/ld+json">
  <?= json_encode([
    '@context'=>'https://schema.org','@type'=>'WebPage',
    'name'=>'DPP & BGKP Paroki Tulungagung',
    'description'=>'Kepengurusan DPP dan BGKP Paroki Santa Maria Dengan Tidak Bernoda Asal Tulungagung.',
    'url'=>'https://www.parokitulungagung.org/profil-dpp',
    'inLanguage'=>'id','isPartOf'=>['@id'=>'https://www.parokitulungagung.org/#website'],
  ], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT); ?>
  </script>
</head>
<body>
<?php $headerTitle = 'DPP & BGKP'; include __DIR__ . '/../components/page_header.php'; ?>
<?php include __DIR__ . '/../components/photo_modal.php'; ?>

<main id="main-content" style="padding:6px">


  <div class="page-hero">
    <div class="page-hero-icon">
      <img src="<?= iconUrl('icon_square_dpp.png') ?>" alt="" loading="lazy" width="40" height="40">
    </div>
    <div class="page-hero-text">
      <h1>Kepengurusan DPP &amp; BGKP</h1>
      <p>Paroki Santa Maria Dengan Tidak Bernoda Asal · Tulungagung</p>
    </div>
  </div>


  <?php if ($dataError): ?>
  <div style="text-align:center;padding:40px;"><h2 style="color:#d32f2f">⚠️ <?= e($dataError) ?></h2></div>
  <?php else: ?>
  <div style="padding:0 6px">
    <?php if (count($allPeriodes) > 1): ?>
    <div class="periode-selector-wrap" id="periodeWrap">
      <div class="periode-badge periode-badge--clickable" id="periodeTrigger" onclick="togglePeriodeDropdown(event)" role="button" tabindex="0" aria-haspopup="listbox" aria-expanded="false">
        <span class="periode-dot"></span>
        <span class="periode-label">Periode <?= e(str_replace('-','–',$activePeriode)) ?></span>
        <svg class="periode-chevron" id="periodeChevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
      </div>
      <div class="periode-dropdown" id="periodeDropdown" role="listbox">
        <div class="periode-dropdown-header">Pilih Periode Kepengurusan</div>
        <?php $year=(int)date('Y'); foreach($allPeriodes as $p): $isActive=($p===$activePeriode); [$ps,$pe]=explode('-',$p); $isCurrent=($year>=(int)$ps&&$year<=(int)$pe); ?>
        <a href="/profil-dpp?periode=<?= urlencode($p) ?>" class="periode-dropdown-item <?= $isActive?'periode-dropdown-item--active':'' ?>" role="option" aria-selected="<?= $isActive?'true':'false' ?>">
          <span class="periode-dropdown-check"><?php if($isActive): ?><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="13" height="13"><polyline points="20 6 9 17 4 12"></polyline></svg><?php endif; ?></span>
          <span class="periode-dropdown-text"><?= e(str_replace('-','–',$p)) ?></span>
          <?php if($isCurrent): ?><span class="periode-now-badge">Saat ini</span><?php endif; ?>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <ul class="tabs" id="tabs-dpp">
      <li><a href="#tab-dpp">DPP</a></li>
      <li><a href="#tab-bgkp">BGKP</a></li>
    </ul>
    <div class="tabcontents" id="tabcontents-dpp" style="padding:2rem;background:var(--white);border-radius:0 12px 12px 12px;box-shadow:0 4px 16px rgba(91,44,111,0.12)">

      <?php
      function renderDppTipe(array $allData, string $tipe, array $seoMap = []): void {
          $byTipe  = array_filter($allData, fn($i) => ($i['Tipe']??'')===$tipe);
          $grouped = [];
          foreach ($byTipe as $i) $grouped[$i['Bidang']??'Lainnya'][] = $i;
          foreach ($grouped as $bidang => $persons):
              $ketua  = current(array_filter($persons, fn($p) => stripos($p['Posisi']??'','ketua')!==false)) ?: [];
              $wakil  = current(array_filter($persons, fn($p) => stripos($p['Posisi']??'','wakil')!==false)) ?: [];
              $others = array_filter($persons, fn($p) => $p!==$ketua && $p!==$wakil);
              $imgKetua = !empty($ketua['Foto']) ? personPhotoUrl($ketua['Foto']) : '';
              $imgWakil = !empty($wakil['Foto']) ? personPhotoUrl($wakil['Foto']) : '';
              // ── SEO data per foto ──────────────────────────────────────
              $seoKetua = $imgKetua ? getImgSeo($imgKetua, $seoMap) : [];
              $seoWakil = $imgWakil ? getImgSeo($imgWakil, $seoMap) : [];
      ?>
          <div class="galeri-accordion" style="margin-bottom:8px">
            <div class="galeri-accordion-header">
              <div class="galeri-accordion-title">
                <span class="galeri-accordion-month"><?= e($bidang) ?></span>
                <span class="galeri-accordion-hint">Klik untuk lihat anggota</span>
              </div>
              <svg class="galeri-accordion-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
            </div>
            <div class="galeri-accordion-body"><div class="galeri-accordion-inner">
              <?php if (!empty($ketua['Nama']) || !empty($wakil['Nama'])): ?>
              <div class="profile-leader-card">
                <div class="profile-leader-info">
                  <?php if(!empty($ketua['Nama'])): ?><div><span class="profile-leader-role">Ketua</span> <span class="profile-leader-name"><?= e($ketua['Nama']) ?></span></div><?php endif; ?>
                  <?php if(!empty($wakil['Nama'])): ?><div><span class="profile-leader-role">Wakil</span> <span class="profile-leader-name"><?= e($wakil['Nama']) ?></span></div><?php endif; ?>
                </div>
                <div class="profile-leader-photos">
                  <?php if(!empty($ketua['Nama'])): ?>
                  <?php if($imgKetua): ?><img src="<?= e($imgKetua) ?>" class="profile-leader-img" onclick="ShowPhotoBox('<?= e($ketua['Nama']) ?>','<?= e($imgKetua) ?>','<?= e($bidang) ?>','Ketua')" alt="<?= e($seoKetua['alt_text'] ?? $ketua['Nama']) ?>" title="<?= e($seoKetua['title_attr'] ?? ($ketua['Nama'] . ' — Paroki SMDTBA Tulungagung')) ?>" loading="lazy" decoding="async" width="120" height="120" onerror="this.style.opacity='0.3'">
                  <?php else: ?><div class="profile-leader-img" style="display:flex;align-items:center;justify-content:center;background:rgba(201,168,76,.1);color:#c9a84c;font-size:20px;font-weight:700;font-family:'Cormorant Garamond',serif"><?= strtoupper(mb_substr($ketua['Nama'],0,1)) ?></div><?php endif; ?>
                  <?php endif; ?>
                  <?php if(!empty($wakil['Nama'])): ?>
                  <?php if($imgWakil): ?><img src="<?= e($imgWakil) ?>" class="profile-leader-img" onclick="ShowPhotoBox('<?= e($wakil['Nama']) ?>','<?= e($imgWakil) ?>','<?= e($bidang) ?>','Wakil')" alt="<?= e($seoWakil['alt_text'] ?? $wakil['Nama']) ?>" title="<?= e($seoWakil['title_attr'] ?? ($wakil['Nama'] . ' — Paroki SMDTBA Tulungagung')) ?>" loading="lazy" decoding="async" width="120" height="120" onerror="this.style.opacity='0.3'">
                  <?php else: ?><div class="profile-leader-img" style="display:flex;align-items:center;justify-content:center;background:rgba(201,168,76,.1);color:#c9a84c;font-size:20px;font-weight:700;font-family:'Cormorant Garamond',serif"><?= strtoupper(mb_substr($wakil['Nama'],0,1)) ?></div><?php endif; ?>
                  <?php endif; ?>
                </div>
              </div>
              <?php endif; ?>
              <?php foreach(array_values($others) as $idx=>$person):
                  $nama=$person['Nama']??''; $posisi=$person['Posisi']??''; $img=!empty($person['Foto'])?personPhotoUrl($person['Foto']):'';
                  $seoP = $img ? getImgSeo($img, $seoMap) : []; ?>
              <div class="profile-item" style="animation-delay:<?= $idx*35 ?>ms" onclick="<?= $img?"ShowPhotoBox('".e($nama)."','".e($img)."','".e($bidang)."','".e($posisi)."')":'' ?>">
                <?php if($img): ?><img class="profile-item-img" src="<?= e($img) ?>" alt="<?= e($seoP['alt_text'] ?? $nama) ?>" title="<?= e($seoP['title_attr'] ?? ($nama . ' — Paroki SMDTBA Tulungagung')) ?>" loading="lazy" decoding="async" width="80" height="80" onerror="this.style.opacity='0.3'">
                <?php else: ?><div class="profile-item-img" style="display:flex;align-items:center;justify-content:center;background:rgba(201,168,76,.1);color:#c9a84c;font-size:18px;font-weight:700;font-family:'Cormorant Garamond',serif"><?= strtoupper(mb_substr($nama,0,1)) ?></div><?php endif; ?>
                <div class="profile-item-info">
                  <div class="profile-item-role"><?= e($posisi) ?></div>
                  <div class="profile-item-name"><?= e($nama) ?></div>
                </div>
              </div>
              <?php endforeach; ?>
            </div></div>
          </div>
      <?php endforeach; }
      ?>

      <div id="tab-dpp"><?php renderDppTipe(array_values($filtered), 'DPP', $dppSeoMap); ?></div>
      <div id="tab-bgkp"><?php renderDppTipe(array_values($filtered), 'BGKP', $dppSeoMap); ?></div>
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
    var t=document.getElementById('periodeTrigger'),d=document.getElementById('periodeDropdown'),c=document.getElementById('periodeChevron');
    if(!d) return;
    t&&t.setAttribute('aria-expanded',_open); t&&t.classList.toggle('periode-badge--open',_open);
    c&&c.classList.toggle('periode-chevron--open',_open); d.classList.toggle('periode-dropdown--open',_open);
  };
  document.addEventListener('click', function(e){ if(!_open) return; var w=document.getElementById('periodeWrap'); if(w&&!w.contains(e.target)){_open=false;var t=document.getElementById('periodeTrigger'),d=document.getElementById('periodeDropdown'),c=document.getElementById('periodeChevron');t&&t.setAttribute('aria-expanded',false);t&&t.classList.remove('periode-badge--open');c&&c.classList.remove('periode-chevron--open');d&&d.classList.remove('periode-dropdown--open');}});
})();
</script>
</body>
</html>
