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

// ── Data Galeri dari Supabase (server-side) ───────────────────────────
$data         = fetchSupabaseCached('galeri_foto', [], 'Tanggal.desc');
$galeriSeoMap = fetchImageSeoByPrefix('/public/galeri/');
$dataError = ($data === null) ? 'Gagal memuat data galeri.' : null;

// ── Kelompokkan per tahun & bulan ─────────────────────────────────────
$tahunSet  = [];
$grouped   = [];
$bulanIndo = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];

if (is_array($data)) {
    foreach ($data as $item) {
        $t = substr($item['Tanggal'] ?? '', 0, 4);
        if ($t) $tahunSet[$t] = true;
    }
    $tahunArr = array_keys($tahunSet);
    rsort($tahunArr);
    foreach ($tahunArr as $t) $grouped[$t] = [];
    foreach ($data as $item) {
        $t = substr($item['Tanggal'] ?? '', 0, 4);
        if ($t && isset($grouped[$t])) {
            $grouped[$t][$item['Bulan'] ?? 'Lainnya'][] = $item;
        }
    }
} else {
    $tahunArr = [];
}

// ── SEO ───────────────────────────────────────────────────────────────
// Hitung statistik untuk deskripsi dinamis
$totalAlbum  = is_array($data) ? count($data) : 0;
$latestItem  = is_array($data) && !empty($data) ? $data[0] : null;
$latestJudul = $latestItem ? ($latestItem['Judul'] ?? '') : '';
$latestTgl   = $latestItem ? ($latestItem['Tanggal'] ?? '') : '';

// Hitung tahun aktif
$tahunRange = '';
if (!empty($tahunArr)) {
    $oldest = end($tahunArr);
    $newest = $tahunArr[0];
    $tahunRange = ($oldest === $newest) ? $oldest : $oldest . '–' . $newest;
}

$seoDesc = 'Galeri foto dokumentasi kegiatan Paroki SMDTBA Tulungagung — misa, sakramen, perayaan Natal, Paskah, dan momen berkesan umat sejak ' . ($tahunRange ?: '2015') . '.';
if ($totalAlbum > 0) {
    $seoDesc = $totalAlbum . ' album foto dokumentasi kegiatan Paroki Santa Maria Dengan Tidak Bernoda Asal Tulungagung. Misa, sakramen, hari raya, dan kegiatan umat sejak ' . ($tahunRange ?: '2015') . '.';
}

$seo = [
    'title'       => 'Galeri Foto Kegiatan Paroki Tulungagung – ' . $totalAlbum . ' Album Dokumentasi',
    'description' => $seoDesc,
    'canonical'   => 'https://www.parokitulungagung.org/galeri',
    'keywords'    => 'galeri foto paroki tulungagung, foto kegiatan gereja katolik tulungagung, dokumentasi paroki smdtba, foto misa tulungagung, kegiatan umat katolik tulungagung, foto sakramen paroki smdtba',
    'type'        => 'website',
    'image'       => !empty($latestItem['Gambar'])
        ? 'https://www.parokitulungagung.org/public/galeri/' . $latestItem['Gambar']
        : 'https://www.parokitulungagung.org/img/og-preview.webp',
    'modified'    => $latestTgl ?: date('Y-m-d'),
];
$breadcrumbs = [
    ['name' => 'Beranda',    'url' => 'https://www.parokitulungagung.org'],
    ['name' => 'Galeri Foto','url' => 'https://www.parokitulungagung.org/galeri'],
];
$extraCss = ['/css/content.css'];

// ── Kumpulkan semua data kartu untuk JS (hindari inline onclick JSON) ─
$galeriDataJS = [];
?>
<!doctype html>
<html lang="id">
<head>
  <?php include __DIR__ . '/../components/seo_head.php'; ?>
  <script type="application/ld+json">
  <?php
  // ── Bangun ImageGallery schema dengan sampel foto terbaru ──────────────
  $_base    = 'https://www.parokitulungagung.org';
  $_galSample = is_array($data) ? array_slice($data, 0, 16) : [];
  $_imageObjects = [];
  foreach ($_galSample as $_img) {
      $_src = !empty($_img['Gambar'])
          ? ($_src = str_starts_with($_img['Gambar'], 'http')
              ? $_img['Gambar']
              : $_base . '/public/galeri/' . $_img['Gambar'])
          : '';
      if (!$_src) continue;
      $_obj = [
          '@type'       => 'ImageObject',
          'contentUrl'  => $_src,
          'url'         => $_src,
          'name'        => strip_tags($_img['Judul'] ?? ''),
          'description' => mb_substr(strip_tags($_img['Keterangan'] ?? $_img['Judul'] ?? ''), 0, 160, 'UTF-8'),
          'datePublished' => $_img['Tanggal'] ?? '',
          'author'      => ['@id' => $_base . '/#organization'],
          'copyrightHolder' => ['@id' => $_base . '/#organization'],
          'copyrightNotice' => '© ' . date('Y') . ' Paroki SMDTBA Tulungagung',
          'creditText'  => 'Paroki Santa Maria Dengan Tidak Bernoda Asal Tulungagung',
          'license'     => $_base,
          'acquireLicensePage' => $_base . '/kebijakan-privasi',
          'creator'     => [
              '@type' => 'Person',
              '@id'   => $_base . '/penulis/komsos-paroki-tulungagung',
              'name'  => 'Komsos Paroki Tulungagung',
              'url'   => $_base . '/penulis/komsos-paroki-tulungagung',
          ],
      ];
      if (!empty($_img['Judul'])) {
          $_obj['caption'] = strip_tags($_img['Judul']);
      }
      $_imageObjects[] = $_obj;
  }

  // ── ItemList: 16 album terbaru untuk Google Discover ─────────────────
  $_listItems = [];
  foreach ($_galSample as $_idx => $_item) {
      $_thumbSrc = !empty($_item['Gambar'])
          ? (str_starts_with($_item['Gambar'], 'http')
              ? $_item['Gambar']
              : $_base . '/public/galeri/' . $_item['Gambar'])
          : '';
      $_listItems[] = [
          '@type'    => 'ListItem',
          'position' => $_idx + 1,
          'name'     => strip_tags($_item['Judul'] ?? ''),
          'description' => mb_substr(strip_tags($_item['Keterangan'] ?? ''), 0, 120, 'UTF-8'),
          'image'    => $_thumbSrc ?: null,
      ];
  }

  $_galSchema = [
      '@context'    => 'https://schema.org',
      '@type'       => ['CollectionPage', 'ImageGallery'],
      '@id'         => $_base . '/galeri#gallery',
      'name'        => 'Galeri Foto Kegiatan Paroki Tulungagung',
      'alternateName' => 'Galeri Foto Paroki SMDTBA',
      'description' => 'Koleksi ' . $totalAlbum . ' album foto dokumentasi kegiatan Paroki Santa Maria Dengan Tidak Bernoda Asal Tulungagung sejak ' . ($tahunRange ?: '2015') . '.',
      'url'         => $_base . '/galeri',
      'inLanguage'  => 'id-ID',
      'isPartOf'    => ['@id' => $_base . '/#website'],
      'about'       => ['@id' => $_base . '/#organization'],
      'publisher'   => ['@id' => $_base . '/#organization'],
      'dateModified' => $latestTgl ?: date('Y-m-d'),
      'thumbnailUrl' => !empty($_galSample[0]['Gambar'])
          ? $_base . '/public/galeri/' . $_galSample[0]['Gambar']
          : $_base . '/img/og-preview.webp',
  ];
  if (!empty($_imageObjects)) {
      $_galSchema['image']           = $_imageObjects;
      $_galSchema['primaryImageOfPage'] = $_imageObjects[0];
  }
  if (!empty($_listItems)) {
      $_galSchema['hasPart'] = [
          '@type'           => 'ItemList',
          'name'            => 'Album Foto Terbaru',
          'numberOfItems'   => count($_listItems),
          'itemListElement' => $_listItems,
      ];
  }

  echo json_encode($_galSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
  ?>
  </script>
</head>
<body>
<?php $headerTitle = 'Galeri Foto'; include __DIR__ . '/../components/page_header.php'; ?>

<!-- Modal Detail Foto -->
<div id="galeri-modal-overlay" role="dialog" aria-modal="true" aria-labelledby="galeri-modal-title">
  <div id="galeri-modal">
    <div class="galeri-modal-thumb">
      <img id="galeri-modal-img" src="" alt="">
      <button class="galeri-modal-close" id="galeri-modal-close" aria-label="Tutup">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
          <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
        </svg>
      </button>
      <div class="galeri-modal-thumb-bar"></div>
    </div>
    <div class="galeri-modal-body">
      <div class="galeri-modal-chip">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" width="11" height="11">
          <rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/>
          <line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
        </svg>
        <span id="galeri-modal-date"></span>
      </div>
      <h2 class="galeri-modal-title" id="galeri-modal-title"></h2>
      <hr class="galeri-modal-divider">
      <p class="galeri-modal-keterangan" id="galeri-modal-keterangan"></p>
      <div class="galeri-modal-meta">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
          <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
          <circle cx="12" cy="13" r="4"/>
        </svg>
        <span id="galeri-modal-foto"></span>
      </div>
      <a href="#" id="galeri-modal-link" class="galeri-modal-btn-album" target="_blank" rel="noopener noreferrer">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/>
          <line x1="4" y1="22" x2="4" y2="15"/>
        </svg>
        Buka Album Foto
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="13" height="13" style="opacity:.6">
          <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
          <polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/>
        </svg>
      </a>
    </div>
  </div>
</div>

<main id="main-content" style="padding:6px">


  <div class="page-hero">
    <div class="page-hero-icon">
      <img src="/img/icon/icon_square_foto.png" alt="" loading="lazy" width="40" height="40">
    </div>
    <div class="page-hero-text">
      <h1>Galeri Foto</h1>
      <p>Paroki Santa Maria Dengan Tidak Bernoda Asal · Tulungagung</p>
    </div>
  </div>

  <!-- ======================================
       GALERI SEARCH BAR
  ======================================== -->
  <style>
  .galeri-search-wrap{margin:0 0 18px;padding:0 2px;}
  .galeri-search-box{
    display:flex;align-items:center;gap:10px;
    background:#fff;border:1.5px solid #e8e0f0;border-radius:14px;
    padding:10px 14px;
    box-shadow:0 2px 12px rgba(91,44,111,.07);
    transition:border-color .2s,box-shadow .2s;
  }
  .galeri-search-box:focus-within{
    border-color:#7c3aed;
    box-shadow:0 0 0 3px rgba(124,58,237,.13),0 2px 12px rgba(91,44,111,.1);
  }
  .galeri-search-icon{flex-shrink:0;color:#9b7ec8;display:flex;}
  .galeri-search-input{
    flex:1;border:none;outline:none;background:transparent;
    font-size:.97rem;color:#2d1a4e;font-family:inherit;
  }
  .galeri-search-input::placeholder{color:#bba8d4;}
  .galeri-search-clear{
    flex-shrink:0;display:none;align-items:center;justify-content:center;
    width:22px;height:22px;border-radius:50%;
    background:#e8e0f0;border:none;cursor:pointer;
    color:#7c3aed;font-size:15px;line-height:1;
    transition:background .15s,transform .15s;padding:0;
  }
  .galeri-search-clear:hover{background:#d4c5f0;transform:scale(1.1);}
  .galeri-search-clear.visible{display:flex;}
  .galeri-search-info{
    margin-top:7px;font-size:.83rem;color:#9b7ec8;
    padding-left:4px;min-height:18px;transition:opacity .2s;
  }
  #galeriSearchResults{margin-top:4px;}
  .galeri-search-empty{
    text-align:center;padding:48px 16px;color:#9b7ec8;font-size:.95rem;
  }
  .galeri-search-empty svg{display:block;margin:0 auto 12px;opacity:.4;}
  </style>

  <div class="galeri-search-wrap" id="galeriSearchWrap" style="display:none">
    <div class="galeri-search-box">
      <span class="galeri-search-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"
             stroke-linecap="round" width="18" height="18">
          <circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
        </svg>
      </span>
      <input type="search" id="galeriSearchInput" class="galeri-search-input"
             placeholder="Cari judul atau tanggal album…" autocomplete="off"
             aria-label="Cari galeri">
      <button class="galeri-search-clear" id="galeriSearchClear" aria-label="Hapus pencarian">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
             stroke-linecap="round" width="12" height="12">
          <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
        </svg>
      </button>
    </div>
    <div class="galeri-search-info" id="galeriSearchInfo"></div>
  </div>

  <div id="galeriSearchResults" style="display:none">
    <div class="galeri-grid" id="galeriSearchGrid"></div>
    <div class="galeri-search-empty" id="galeriSearchEmpty" style="display:none">
      <svg viewBox="0 0 64 64" fill="none" stroke="#9b7ec8" stroke-width="2" width="48" height="48">
        <circle cx="28" cy="28" r="18"/><line x1="50" y1="50" x2="40" y2="40"/>
        <line x1="22" y1="28" x2="34" y2="28"/>
      </svg>
      Tidak ada album yang cocok dengan kata kunci &ldquo;<span id="galeriSearchKeyword"></span>&rdquo;
    </div>
  </div>


  <?php if ($dataError): ?>
  <div class="galeri-empty">
    <p style="color:#d32f2f">&#9888; <?= e($dataError) ?></p>
    <p>Silakan coba lagi atau hubungi administrator.</p>
  </div>

  <?php elseif (empty($tahunArr)): ?>
  <div class="galeri-empty">
    <p>Belum ada foto yang tersedia.</p>
  </div>

  <?php else: ?>

  <div class="galeri-year-tabs-wrap">
    <button class="galeri-year-arrow" id="galeri-arrow-left" aria-label="Geser kiri" data-disabled="1">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" width="14" height="14">
        <polyline points="15 18 9 12 15 6"/>
      </svg>
    </button>
    <div class="galeri-year-track">
      <div class="galeri-year-tabs" id="galeri-year-tabs-inner" role="tablist">
        <?php foreach ($tahunArr as $idx => $t): ?>
        <button class="galeri-year-tab<?= $idx === 0 ? ' active' : '' ?>"
                role="tab"
                aria-selected="<?= $idx === 0 ? 'true' : 'false' ?>"
                aria-controls="galeri-panel-<?= e($t) ?>"
                data-year="<?= e($t) ?>"><?= e($t) ?></button>
        <?php endforeach; ?>
      </div>
    </div>
    <button class="galeri-year-arrow" id="galeri-arrow-right" aria-label="Geser kanan">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" width="14" height="14">
        <polyline points="9 18 15 12 9 6"/>
      </svg>
    </button>
  </div>

  <?php foreach ($tahunArr as $idx => $tahun): ?>
  <div class="galeri-tab-panel<?= $idx === 0 ? ' active' : '' ?>"
       id="galeri-panel-<?= e($tahun) ?>"
       role="tabpanel">

    <?php
    $bulanData = $grouped[$tahun];
    uksort($bulanData, fn($a,$b) =>
        (($ia = array_search($a, $bulanIndo)) === false ? 99 : $ia) -
        (($ib = array_search($b, $bulanIndo)) === false ? 99 : $ib)
    );
    $bulanData  = array_reverse($bulanData, true);
    $firstMonth = true;
    foreach ($bulanData as $bulan => $items):
    ?>
    <div class="galeri-month-section">
      <div class="galeri-month-header"
           role="button" tabindex="0"
           aria-expanded="false">
        <div class="galeri-month-left">
          <span class="galeri-month-dot"></span>
          <span class="galeri-month-name"><?= e($bulan . ' ' . $tahun) ?></span>
          <span class="galeri-month-count">(<?= count($items) ?> Album)</span>
        </div>
        <svg class="galeri-month-chevron" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
          <polyline points="6 9 12 15 18 9"/>
        </svg>
      </div>
      <div class="galeri-month-body">
        <div class="galeri-grid">
          <?php foreach ($items as $idx2 => $item):
            $gambar = $item['Gambar'] ?? '';
            $imgSrc = str_starts_with($gambar, 'http') ? $gambar : '/public/galeri/' . $gambar;

            // ── SEO data dari image_seo Supabase ───────────────────────────────
            $imgSeo  = getImgSeo($imgSrc, $galeriSeoMap);
            $imgAlt  = $imgSeo['alt_text']   ?? '';
            $imgTitle= $imgSeo['title_attr'] ?? '';
            $imgCap  = $imgSeo['caption']    ?? '';
            // Fallback ke judul jika SEO belum di-generate
            $altFinal   = $imgAlt   ?: (($item['Judul'] ?? '') . ' – Dokumentasi Paroki SMDTBA Tulungagung');
            $titleFinal = $imgTitle ?: ($item['Judul'] ?? '');

            // ── Gunakan id Supabase sebagai key (unik, stabil, tidak perlu slugify) ──
            $cardId = 'g' . ($item['id'] ?? count($galeriDataJS));
            $galeriDataJS[$cardId] = [
              'img'     => $imgSrc,
              'judul'   => $item['Judul'] ?? '',
              'tgl'     => formatTanggalIndo($item['Tanggal'] ?? ''),
              'tglRaw'  => $item['Tanggal'] ?? '',
              'ket'     => $item['Keterangan'] ?? '',
              'foto'    => $item['Foto'] ?? '',
              'link'    => $item['Link'] ?? '#',
              'id'      => $item['id'] ?? 0,
              'seo_alt' => $altFinal,
              'seo_cap' => $imgCap,
              'seo_tit' => $titleFinal,
            ];
          ?>
          <div class="galeri-card"
               style="animation-delay:<?= $idx2 * 35 ?>ms"
               tabindex="0" role="button"
               aria-label="Lihat detail: <?= e($item['Judul'] ?? '') ?>"
               data-gid="<?= $cardId ?>"
               onkeydown="if(event.key==='Enter'||event.key===' ')this.click()">
            <img src="<?= e($imgSrc) ?>"
                 alt="<?= e($altFinal) ?>"
                 title="<?= e($titleFinal) ?>"
                 loading="<?= ($idx2 < 4 && $firstMonth) ? 'eager' : 'lazy' ?>"
                 fetchpriority="<?= ($idx2 === 0 && $firstMonth) ? 'high' : 'auto' ?>"
                 decoding="async"
                 width="400" height="300"
                 onerror="this.parentElement.style.opacity='.5'">
            <div class="galeri-card-overlay">
              <div class="galeri-card-title"><?= e($item['Judul'] ?? '') ?></div>
              <div class="galeri-card-date"><?= e(formatTanggalIndo($item['Tanggal'] ?? '')) ?></div>
            </div>
            <div class="galeri-card-num"><?= $idx2 + 1 ?></div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <?php $firstMonth = false; endforeach; ?>
  </div>
  <?php endforeach; ?>

  <?php endif; ?>

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

</script>
<script>
(function(){
  /* ── Data galeri dari PHP (aman, tidak ada masalah escape) ── */
window.GALERI = <?= json_encode($galeriDataJS, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

  /* ── Tab tahun + panah ── */
  var tabsInner   = document.getElementById('galeri-year-tabs-inner');
  var arrowLeft   = document.getElementById('galeri-arrow-left');
  var arrowRight  = document.getElementById('galeri-arrow-right');

  function updateTabArrows() {
    if (!tabsInner) return;
    var scrollLeft  = tabsInner.scrollLeft;
    var clientWidth = tabsInner.clientWidth;
    var scrollWidth = tabsInner.scrollWidth;
    var hasOverflow = scrollWidth > clientWidth + 2;
    var atStart = scrollLeft < 4;
    var atEnd   = !hasOverflow || (scrollLeft + clientWidth >= scrollWidth - 4);


    arrowLeft.style.opacity       = atStart ? '.3' : '1';
    arrowLeft.style.pointerEvents = atStart ? 'none' : 'auto';

    /* panah kanan: selalu aktif jika ada overflow dan belum di ujung */
    arrowRight.style.opacity       = (hasOverflow && !atEnd) ? '1' : '.3';
    arrowRight.style.pointerEvents = (hasOverflow && !atEnd) ? 'auto' : 'none';
  }

  if (tabsInner) {
    tabsInner.addEventListener('scroll', updateTabArrows);
    /* Tunggu layout selesai sebelum cek overflow pertama kali */
    if (document.readyState === 'complete') {
      updateTabArrows();
    } else {
      window.addEventListener('load', updateTabArrows);
    }
    /* ResizeObserver: re-cek saat ukuran layar berubah (resize, rotate) */
    if (typeof ResizeObserver !== 'undefined') {
      new ResizeObserver(updateTabArrows).observe(tabsInner);
    }
  }
  if (arrowLeft)  arrowLeft.addEventListener('click',  function(){ tabsInner.scrollBy({ left: -160, behavior: 'smooth' }); });
  if (arrowRight) arrowRight.addEventListener('click', function(){ tabsInner.scrollBy({ left:  160, behavior: 'smooth' }); });

  document.querySelectorAll('.galeri-year-tab').forEach(function(btn){
    btn.addEventListener('click', function(){
      document.querySelectorAll('.galeri-year-tab').forEach(function(b){
        b.classList.remove('active');
        b.setAttribute('aria-selected','false');
      });
      document.querySelectorAll('.galeri-tab-panel').forEach(function(p){
        p.classList.remove('active');
      });
      btn.classList.add('active');
      btn.setAttribute('aria-selected','true');
      document.getElementById('galeri-panel-' + btn.dataset.year).classList.add('active');
      btn.scrollIntoView({ behavior:'smooth', block:'nearest', inline:'center' });
    });
  });

  /* ── Accordion bulan ── */
  document.querySelectorAll('.galeri-month-header').forEach(function(hdr){
    hdr.addEventListener('click', function(){
      var open = hdr.classList.contains('open');
      hdr.classList.toggle('open', !open);
      hdr.setAttribute('aria-expanded', String(!open));
      hdr.nextElementSibling.classList.toggle('open', !open);
    });
    hdr.addEventListener('keydown', function(e){
      if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); hdr.click(); }
    });
  });

  /* ── Kartu galeri: klik via data-gid, bukan inline onclick ── */
  document.addEventListener('click', function(e){
    var card = e.target.closest('.galeri-card[data-gid]');
    if (!card) return;
    var d = GALERI[card.dataset.gid];
    if (d) galeriOpenModal(d);
  });

  /* ── Modal ── */
  var ov = document.getElementById('galeri-modal-overlay');

  window.galeriOpenModal = function(d){
    document.getElementById('galeri-modal-img').src       = d.img;
    document.getElementById('galeri-modal-img').alt       = d.seo_alt || d.judul;
    document.getElementById('galeri-modal-img').title     = d.seo_tit || d.judul;
    document.getElementById('galeri-modal-title').textContent     = d.judul;
    document.getElementById('galeri-modal-date').textContent      = d.tgl;
    document.getElementById('galeri-modal-keterangan').textContent = d.seo_cap || d.ket || '—';
    document.getElementById('galeri-modal-foto').textContent      = 'Foto: ' + (d.foto || '—');
    document.getElementById('galeri-modal-link').href             = d.link;
    ov.classList.add('visible');
    document.body.style.overflow = 'hidden';
    document.getElementById('galeri-modal-close').focus();
  };

  function closeModal(){
    ov.classList.remove('visible');
    document.body.style.overflow = '';
  }

  document.getElementById('galeri-modal-close').addEventListener('click', closeModal);
  ov.addEventListener('click', function(e){ if (e.target === ov) closeModal(); });
  document.addEventListener('keydown', function(e){ if (e.key === 'Escape') closeModal(); });

  /* ── Auto-open album via ?album=ID (id Supabase, lebih stabil dari slug) ── */
  (function(){
    var params  = new URLSearchParams(window.location.search);
    var target  = params.get('album');
    if (!target) return;

    /* Cari by id numerik (misal ?album=157) — O(1) langsung lewat key 'g{id}' */
    var numId     = parseInt(target, 10);
    var matchGid  = null;
    var matchData = null;

    if (!isNaN(numId)) {
      /* Path cepat: key langsung 'g157' */
      var directKey = 'g' + numId;
      if (GALERI[directKey]) {
        matchGid  = directKey;
        matchData = GALERI[directKey];
      }
    }

    /* Fallback: scan semua — cocokkan field id numerik (kalau format key berubah) */
    if (!matchData) {
      Object.keys(window.GALERI).forEach(function(gid){
        if (matchGid) return;
        if (String(GALERI[gid].id) === String(target)) {
          matchGid  = gid;
          matchData = GALERI[gid];
        }
      });
    }

    if (!matchData) return;

    /* Temukan kartu DOM */
    var card = document.querySelector('.galeri-card[data-gid="' + matchGid + '"]');
    if (!card) return;

    /* ── LANGKAH 1: Aktifkan tab tahun (sync) ── */
    var panel = card.closest('.galeri-tab-panel');
    if (panel) {
      document.querySelectorAll('.galeri-tab-panel').forEach(function(p){ p.classList.remove('active'); });
      document.querySelectorAll('.galeri-year-tab').forEach(function(b){
        b.classList.remove('active');
        b.setAttribute('aria-selected', 'false');
      });
      panel.classList.add('active');
      var year    = panel.id.replace('galeri-panel-', '');
      var yearBtn = document.querySelector('.galeri-year-tab[data-year="' + year + '"]');
      if (yearBtn) {
        yearBtn.classList.add('active');
        yearBtn.setAttribute('aria-selected', 'true');
      }
    }

    /* ── LANGKAH 2: Buka accordion bulan (sync) ── */
    var monthBody = card.closest('.galeri-month-body');
    if (monthBody) {
      var monthHdr = monthBody.previousElementSibling;
      if (monthHdr) {
        monthHdr.classList.add('open');
        monthHdr.setAttribute('aria-expanded', 'true');
        monthBody.classList.add('open');
      }
    }

    /* ── LANGKAH 3: Tunggu 2 frame render, baru scroll & buka modal ── */
    requestAnimationFrame(function(){
      requestAnimationFrame(function(){
        var yearBtn2 = document.querySelector('.galeri-year-tab.active');
        if (yearBtn2) yearBtn2.scrollIntoView({ behavior: 'instant', block: 'nearest', inline: 'center' });
        card.scrollIntoView({ behavior: 'smooth', block: 'center' });
        setTimeout(function(){ galeriOpenModal(matchData); }, 650);
      });
    });
  })();

})();

  /* ================================================
     SEARCH GALERI (client-side, space-insensitive)
  ================================================ */
  (function(){
    var searchWrap   = document.getElementById('galeriSearchWrap');
    var searchInput  = document.getElementById('galeriSearchInput');
    var searchClear  = document.getElementById('galeriSearchClear');
    var searchInfo   = document.getElementById('galeriSearchInfo');
    var searchRes    = document.getElementById('galeriSearchResults');
    var searchGrid   = document.getElementById('galeriSearchGrid');
    var searchEmpty  = document.getElementById('galeriSearchEmpty');
    var searchKw     = document.getElementById('galeriSearchKeyword');
    var yearTabsWrap = document.querySelector('.galeri-year-tabs-wrap');
    var tabPanels    = document.querySelectorAll('.galeri-tab-panel');

    if (!searchWrap || !searchInput || typeof window.GALERI === 'undefined') return;

    // Tampilkan search bar setelah GALERI data tersedia
    searchWrap.style.display = '';

    /** Normalisasi: hapus semua spasi, lowercase */
    function norm(s){ return (s || '').replace(/\s+/g, '').toLowerCase(); }

function doSearch(raw){
  var q = (raw || '').trim().toLowerCase();

  if (!q){
    searchRes.style.display = 'none';

    if (yearTabsWrap) yearTabsWrap.style.display = '';

    tabPanels.forEach(function(p){
      p.style.display = '';
    });

    searchGrid.innerHTML = '';
    searchInfo.textContent = '';
    searchClear.classList.remove('visible');
    return;
  }

  searchClear.classList.add('visible');

  // Pecah keyword
  var keywords = q.split(/\s+/).filter(Boolean);

  var matched = [];

  Object.keys(window.GALERI).forEach(function(gid){

    var d = window.GALERI[gid];

    var searchText = [
      d.judul || '',
      d.tgl || '',
      d.tglRaw || '',
      d.ket || '',
      d.foto || ''
    ]
    .join(' ')
    .toLowerCase();

    // Semua keyword harus cocok
    var isMatch = keywords.every(function(word){
      return searchText.indexOf(word) !== -1;
    });

    if (isMatch){
      matched.push({
        gid: gid,
        d: d
      });
    }
  });

  // Hide normal tabs
  if (yearTabsWrap) yearTabsWrap.style.display = 'none';

  tabPanels.forEach(function(p){
    p.style.display = 'none';
  });

  searchRes.style.display = '';

  if (matched.length === 0){

    searchGrid.innerHTML = '';
    searchEmpty.style.display = '';

    if (searchKw){
      searchKw.textContent = raw.trim();
    }

    searchInfo.textContent = 'Tidak ada hasil';

  } else {

    searchEmpty.style.display = 'none';

    searchInfo.textContent =
      matched.length + ' album ditemukan';

    searchGrid.innerHTML = '';

    matched.forEach(function(item, idx){

      var orig = document.querySelector(
        '.galeri-card[data-gid="' + item.gid + '"]'
      );

      if (orig){

        var clone = orig.cloneNode(true);

        clone.style.animationDelay =
          (idx * 30) + 'ms';

        searchGrid.appendChild(clone);
      }
    });
  }
}

    var debounceTimer;
    searchInput.addEventListener('input', function(){
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(function(){ doSearch(searchInput.value); }, 220);
    });

    searchClear.addEventListener('click', function(){
      searchInput.value = '';
      doSearch('');
      searchInput.focus();
    });

    searchInput.addEventListener('keydown', function(e){
      if (e.key === 'Escape'){ searchInput.value = ''; doSearch(''); }
    });
  })();

</script>
</body>
</html>