<?php
require_once __DIR__ . '/../includes/functions.php';

$data      = fetchSupabaseCached('daftar_asisten_imam', [], 'Nama.asc');

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
// URL R2 prefix untuk foto person
$personR2Prefix = (defined('R2_CDN_URL') ? rtrim(R2_CDN_URL, '/') : '')
    . '/' . trim(defined('R2_PERSON_PREFIX') ? R2_PERSON_PREFIX : 'person/', '/');
$aiSeoMap = fetchImageSeoByPrefix($personR2Prefix . '/');
// Guard: jika Supabase mengembalikan error-object (array tapi bukan data rows)
if (is_array($data) && isset($data['message'])) {
    $data = null;
}

$dataError = ($data === null) ? 'Gagal mengambil data dari server.' : null;

function ai_getAllPeriodes(array $data): array {
    $set = [];
    foreach ($data as $row) {
        $p = trim($row['Periode'] ?? '');
        if (preg_match('/^\d{4}-\d{4}$/', $p)) $set[$p] = true;
    }
    $list = array_keys($set);
    rsort($list);
    return $list;
}

function ai_resolveActivePeriode(array $data): string {
    $all     = ai_getAllPeriodes($data);
    $fromUrl = trim($_GET['periode'] ?? '');
    if ($fromUrl && preg_match('/^\d{4}-\d{4}$/', $fromUrl) && in_array($fromUrl, $all)) return $fromUrl;
    $year = (int) date('Y');
    foreach ($all as $p) {
        [$ps, $pe] = explode('-', $p);
        if ($year >= (int)$ps && $year <= (int)$pe) return $p;
    }
    return $all[0] ?? '';
}

$allPeriodes   = is_array($data) ? ai_getAllPeriodes($data) : [];
$activePeriode = is_array($data) ? ai_resolveActivePeriode($data) : '';
$hasMultiple   = count($allPeriodes) > 1;

$filtered = [];
if (is_array($data)) {
    foreach ($data as $r) {
        // Pastikan $r adalah array (bukan string/null dari response Supabase yang aneh)
        if (!is_array($r)) continue;
        $p = $r['Periode'] ?? '';
        if ($p === '' || $p === $activePeriode) $filtered[] = $r;
    }
}

$items = [];
foreach ($filtered as $r) {
    if (!empty($r['Nama']) && !empty($r['Asal Lingk / Stasi'])) $items[] = $r;
}
usort($items, fn($a,$b) =>
    strcmp($a['Asal Lingk / Stasi'] ?? '', $b['Asal Lingk / Stasi'] ?? '') ?:
    strcmp($a['Nama'] ?? '', $b['Nama'] ?? '')
);

$wilList = [];
foreach ($items as $i) {
    $w = $i['Asal Lingk / Stasi'] ?? '';
    if ($w !== '' && !in_array($w, $wilList)) $wilList[] = $w;
}

$seo = [
    'title'       => 'Daftar Asisten Imam – Gereja Katolik Paroki Tulungagung',
    'description' => 'Daftar Asisten Imam Paroki Santa Maria Dengan Tidak Bernoda Asal Tulungagung beserta wilayah pelayanannya.',
    'canonical'   => 'https://www.parokitulungagung.org/profil-ai',
    'keywords'    => 'asisten imam paroki tulungagung, daftar asisten imam, pastor pembantu gereja tulungagung, paroki smdtba',
    'type'        => 'website',
];
$breadcrumbs = [
    ['name' => 'Beranda',      'url' => 'https://www.parokitulungagung.org'],
    ['name' => 'Asisten Imam', 'url' => 'https://www.parokitulungagung.org/profil-ai'],
];
$extraCss = ['/css/content.css'];
?>
<!doctype html>
<html lang="id">
<head>
  <?php include __DIR__ . '/../components/seo_head.php'; ?>
  <script type="application/ld+json">
  <?= json_encode([
    '@context'    => 'https://schema.org',
    '@type'       => 'WebPage',
    'name'        => 'Daftar Asisten Imam Paroki Tulungagung',
    'description' => 'Daftar Asisten Imam Paroki Santa Maria Dengan Tidak Bernoda Asal Tulungagung beserta wilayah pelayanannya.',
    'url'         => 'https://www.parokitulungagung.org/profil-ai',
    'inLanguage'  => 'id',
    'isPartOf'    => ['@id' => 'https://www.parokitulungagung.org/#website'],
  ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT); ?>
  </script>
  <style>
  /* Override: teks nama di card profil harus terbaca di atas background gelap */
  .profile-item-name { color: rgba(255,255,255,0.90) !important; }
  .profile-item:hover .profile-item-name { color: rgba(0,0,0,0.75) !important; }
  .profile-item:hover .profile-item-role { color: #8a6010 !important; }
  </style>
</head>
<body>
<?php $headerTitle = 'Asisten Imam'; include __DIR__ . '/../components/page_header.php'; ?>
<?php include __DIR__ . '/../components/photo_modal.php'; ?>

<main id="main-content" style="padding:6px">


  <div class="page-hero">
    <div class="page-hero-icon">
      <img src="<?= iconUrl('icon_square_ai.png') ?>" alt="" loading="lazy" width="40" height="40">
    </div>
    <div class="page-hero-text">
      <h1>Asisten Imam</h1>
      <p>Paroki Santa Maria Dengan Tidak Bernoda Asal · Tulungagung</p>
    </div>
  </div>


  <?php if ($dataError): ?>
  <div style="text-align:center;padding:40px;">
    <h2 style="color:#d32f2f">⚠️ <?= e($dataError) ?></h2>
    <p>Silakan coba lagi atau hubungi administrator.</p>
  </div>
  <?php else: ?>

  <div style="padding:8px;">

    <?php if ($activePeriode): ?>
    <div class="periode-selector-wrap" id="periodeWrap">
      <div class="periode-badge <?= $hasMultiple ? 'periode-badge--clickable' : '' ?>"
           id="periodeTrigger"
           <?= $hasMultiple ? 'onclick="togglePeriodeDropdown(event)"' : '' ?>
           <?= $hasMultiple ? 'role="button" tabindex="0"' : '' ?>
           <?= $hasMultiple ? 'aria-haspopup="listbox" aria-expanded="false"' : '' ?>>
        <span class="periode-dot"></span>
        <span class="periode-label">Periode <?= e(str_replace('-', '–', $activePeriode)) ?></span>
        <?php if ($hasMultiple): ?>
        <svg class="periode-chevron" id="periodeChevron" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="6 9 12 15 18 9"></polyline>
        </svg>
        <?php endif; ?>
      </div>
      <?php if ($hasMultiple): ?>
      <div class="periode-dropdown" id="periodeDropdown" role="listbox">
        <div class="periode-dropdown-header">Pilih Periode Kepengurusan</div>
        <?php
        $year = (int) date('Y');
        foreach ($allPeriodes as $p):
            $isActive  = ($p === $activePeriode);
            [$ps, $pe] = explode('-', $p);
            $isCurrent = ($year >= (int)$ps && $year <= (int)$pe);
        ?>
        <a href="/profil-ai?periode=<?= urlencode($p) ?>"
           class="periode-dropdown-item <?= $isActive ? 'periode-dropdown-item--active' : '' ?>"
           role="option" aria-selected="<?= $isActive ? 'true' : 'false' ?>">
          <span class="periode-dropdown-check">
            <?php if ($isActive): ?>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                 width="13" height="13" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
            <?php endif; ?>
          </span>
          <span class="periode-dropdown-text"><?= e(str_replace('-', '–', $p)) ?></span>
          <?php if ($isCurrent): ?><span class="periode-now-badge">Saat ini</span><?php endif; ?>
        </a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="w3-container w3-card" style="background-color:#e0e9ee;padding:10px;">

      <?php if (!empty($wilList)): ?>
      <div style="margin-bottom:10px">
        <label for="kringselect" style="font-weight:bold;margin-right:10px;">Pilih Wilayah/Stasi:</label>
        <select id="kringselect" onchange="filterByWilayah(this.value)"
                style="padding:8px;border-radius:4px;border:1px solid #ccc;">
          <option value="all">Semua</option>
          <?php foreach ($wilList as $w): ?>
          <option value="<?= e($w) ?>"><?= e($w) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php endif; ?>

      <div id="konten-asistenimam">
        <?php if (empty($items)): ?>
        <p style="text-align:center;padding:20px;color:#888">Belum ada data asisten imam.</p>
        <?php else: ?>
        <?php foreach ($items as $item):
            $nama    = (string)($item['Nama'] ?? '');
            $wilayah = (string)($item['Asal Lingk / Stasi'] ?? '');
            $fotoCol = trim((string)($item['Foto'] ?? ''));
            $foto    = $fotoCol ? personPhotoUrl($fotoCol) : '';
            // ── SEO ────────────────────────────────────────────────────
            $seoAI    = $foto ? getImgSeo($foto, $aiSeoMap) : [];
            $altAI    = ($seoAI['alt_text'] ?? '')   ?: $nama;
            $titleAI  = ($seoAI['title_attr'] ?? '') ?: ($nama . ' — Asisten Imam Paroki SMDTBA Tulungagung');
            // Gunakan single-quote JS string agar tidak bentrok dengan atribut HTML double-quote.
            // Escape backslash dan single-quote di dalam nilai, lalu bungkus dengan '...'
            $jsEsc = fn(string $s): string =>
                "'" . strtr($s, ["'" => "\\'", '\\' => '\\\\']) . "'";
            $onclick = $foto
                ? 'ShowPhotoBox(' . $jsEsc($nama) . ',' . $jsEsc($foto) . ",\\'ASISTEN IMAM\\'," . $jsEsc($wilayah) . ')'
                : '';
        ?>
        <div class="profile-item"
             data-wilayah="<?= e($wilayah) ?>"
             <?= $onclick ? 'onclick="' . $onclick . '"' : '' ?>>
          <?php if ($foto): ?>
          <img class="profile-item-img" src="<?= e($foto) ?>" alt="<?= e($altAI) ?>" title="<?= e($titleAI) ?>"
               loading="lazy" decoding="async" width="80" height="80" onerror="this.style.opacity='0.3'">
          <?php else: ?>
          <div class="profile-item-img"
               style="display:flex;align-items:center;justify-content:center;background:rgba(201,168,76,.1);color:#c9a84c;font-size:18px;font-weight:700;font-family:'Cormorant Garamond',serif">
            <?= e(strtoupper(mb_substr($nama, 0, 1))) ?>
          </div>
          <?php endif; ?>
          <div class="profile-item-info">
            <div class="profile-item-role"><?= e($wilayah) ?></div>
            <div class="profile-item-name"><?= e($nama) ?></div>
          </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
      </div><!-- /#konten-asistenimam -->

    </div><!-- /.w3-container -->
  </div><!-- /padding -->

  <?php endif; ?>

</main>

<?php include __DIR__ . '/../components/footer.php'; ?>
<script src="/js/app.js?v=<?= substr(md5_file(__DIR__ . '/../js/app.js'), 0, 8) ?>"></script>
<?php if ($hasMultiple): ?>
<script>
/* togglePeriodeDropdown — tidak ada di app.js, didefinisikan inline */
(function () {
    var _open = false;
    window.togglePeriodeDropdown = function (e) {
        e && e.stopPropagation();
        _open = !_open;
        var trigger  = document.getElementById('periodeTrigger');
        var dropdown = document.getElementById('periodeDropdown');
        var chevron  = document.getElementById('periodeChevron');
        if (!dropdown) return;
        trigger  && trigger.setAttribute('aria-expanded', _open);
        trigger  && trigger.classList.toggle('periode-badge--open', _open);
        chevron  && chevron.classList.toggle('periode-chevron--open', _open);
        dropdown.classList.toggle('periode-dropdown--open', _open);
    };
    document.addEventListener('click', function (e) {
        if (!_open) return;
        var wrap = document.getElementById('periodeWrap');
        if (wrap && !wrap.contains(e.target)) {
            _open = false;
            var trigger  = document.getElementById('periodeTrigger');
            var dropdown = document.getElementById('periodeDropdown');
            var chevron  = document.getElementById('periodeChevron');
            trigger  && trigger.setAttribute('aria-expanded', false);
            trigger  && trigger.classList.remove('periode-badge--open');
            chevron  && chevron.classList.remove('periode-chevron--open');
            dropdown && dropdown.classList.remove('periode-dropdown--open');
        }
    });
    var trigger = document.getElementById('periodeTrigger');
    trigger && trigger.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); window.togglePeriodeDropdown(e); }
        if (e.key === 'Escape' && _open) window.togglePeriodeDropdown(e);
    });
})();
</script>
<?php endif; ?>
</body>
</html>