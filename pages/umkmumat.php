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
$umkmSeoMap = fetchImageSeoByPrefix('/public/umkm/');

// ── Data UMKM dari Supabase (server-side) ─────────────────────────────
$data  = [];
$error = '';
try {
    $url = rtrim(SUPABASE_URL,'/') . '/rest/v1/umkm_umat'
         . '?status=eq.published&order=urutan.asc,id.desc&select=judul,gambar,nama_usaha,kontak,urutan,deskripsi,maps_url&limit=200';
    $headers = [
        'apikey: ' . SUPABASE_ANON_KEY,
        'Authorization: Bearer ' . SUPABASE_ANON_KEY,
        'Accept: application/json',
    ];
    $ctx = stream_context_create([
        'http' => ['header'=>implode("\r\n",$headers)."\r\n",'timeout'=>8,'ignore_errors'=>true],
        'ssl'  => ['verify_peer'=>true,'verify_peer_name'=>true],
    ]);
    $result = @file_get_contents($url, false, $ctx);
    if ($result !== false) {
        $decoded = json_decode($result, true);
        if (is_array($decoded)) $data = $decoded;
    }
    if (!$result && function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_HTTPHEADER=>$headers,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_TIMEOUT=>8]);
        $json = curl_exec($ch); curl_close($ch);
        if ($json) { $decoded = json_decode($json, true); if (is_array($decoded)) $data = $decoded; }
    }
} catch (Throwable $e) { $error = $e->getMessage(); }

$seo = [
    'title'       => 'UMKM Umat – Pasar Umat Paroki Tulungagung',
    'description' => 'Pasar Umat – Promosi usaha dan karya UMKM umat Paroki Santa Maria Dengan Tidak Bernoda Asal Tulungagung.',
    'canonical'   => 'https://www.parokitulungagung.org/umkmumat',
    'keywords'    => 'umkm umat paroki, pasar umat, usaha umat paroki smdtba tulungagung',
    'type'        => 'website',
];
$breadcrumbs = [
    ['name'=>'Beranda','url'=>'https://www.parokitulungagung.org'],
    ['name'=>'UMKM Umat','url'=>'https://www.parokitulungagung.org/umkmumat'],
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
    'name'=>'UMKM Umat Paroki Tulungagung',
    'description'=>'Pasar Umat – Promosi usaha dan karya UMKM umat Paroki Santa Maria Dengan Tidak Bernoda Asal Tulungagung.',
    'url'=>'https://www.parokitulungagung.org/umkmumat',
    'inLanguage'=>'id','isPartOf'=>['@id'=>'https://www.parokitulungagung.org/#website'],
  ], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT); ?>
  </script>
</head>
<body>
<?php $headerTitle = 'UMKM Umat'; include __DIR__ . '/../components/page_header.php'; ?>
<?php include __DIR__ . '/../components/photo_modal.php'; ?>

<main id="main-content" style="padding:6px">
<div class="uk-wrap">

  <div class="uk-header">
    <table cellpadding="0" cellspacing="0" style="margin-bottom:10px"><tr>
      <td class="tdiconheadline"><img class="iconheadline" src="/img/icon/umkm.png" alt="" loading="lazy"></td>
    </tr></table>
    <h1 class="headline_title">UMKM Umat</h1>
    <div class="uk-subhead">Promosi usaha &amp; karya umat Paroki Tulungagung</div>
    <hr class="uk-divider">
  </div>

  <div class="uk-cta-banner">
    <div class="uk-cta-icon">
      <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.2" width="18" height="18" stroke-linecap="round" stroke-linejoin="round">
        <path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5M2 12l10 5 10-5"/>
      </svg>
    </div>
    <div class="uk-cta-body">
      <div class="uk-cta-title">Ingin memasang iklan gratis?</div>
      <div class="uk-cta-sub">Promosikan usaha Anda kepada seluruh umat paroki.</div>
    </div>
    <a class="uk-cta-btn" href="/admin/">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" width="12" height="12" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
      Daftar di sini
    </a>
  </div>

  <?php if ($error): ?>
  <div class="uk-empty">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="40" height="40"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
    Gagal memuat data. Silakan coba lagi.
  </div>
  <?php elseif (empty($data)): ?>
  <div class="uk-empty">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="40" height="40"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
    Belum ada promosi yang ditampilkan.
  </div>
  <?php else: ?>
  <div class="uk-grid" id="ukGrid">
    <?php foreach ($data as $idx => $item):
        $gambar    = $item['gambar']    ?? '';
        $judul     = $item['judul']     ?? '';
        $namaUsaha = $item['nama_usaha'] ?? '';
        $kontak    = $item['kontak']    ?? '';
        $deskripsi = $item['deskripsi'] ?? '';
        $mapsUrl   = $item['maps_url']  ?? '';
        $imgSrc    = $gambar ? '/public/umkm/' . $gambar : '';
        if (!$imgSrc) continue;
        // ── SEO data untuk gambar ini ──────────────────────────────────
        $imgSeo   = getImgSeo($imgSrc, $umkmSeoMap);
        $imgAlt   = $imgSeo['alt_text']   ?: $judul;
        $imgTitle = $imgSeo['title_attr'] ?: ($judul ?: $namaUsaha);
        $imgCap   = $imgSeo['caption']    ?? '';
        $modalData = htmlspecialchars(json_encode(['src'=>$imgSrc,'judul'=>$judul,'namaUsaha'=>$namaUsaha,'kontak'=>$kontak,'deskripsi'=>$deskripsi,'mapsUrl'=>$mapsUrl,'seoAlt'=>$imgAlt,'seoTitle'=>$imgTitle,'seoCap'=>$imgCap]), ENT_QUOTES, 'UTF-8');
    ?>
    <div class="uk-card" style="animation-delay:<?= $idx*50 ?>ms" onclick="ukOpenModal(<?= $modalData ?>)">
      <div class="uk-card-img-wrap">
        <img class="uk-card-img" src="<?= e($imgSrc) ?>" alt="<?= e($imgAlt) ?>" title="<?= e($imgTitle) ?>" loading="lazy"
             onerror="this.closest('.uk-card').style.display='none'">
        <div class="uk-card-overlay">
          <?php if ($judul): ?><div class="uk-card-overlay-name"><?= e($judul) ?></div><?php endif; ?>
          <?php if ($kontak): ?>
          <div class="uk-card-overlay-wa">
            <svg viewBox="0 0 24 24" fill="currentColor" width="10" height="10" style="display:inline;vertical-align:middle;margin-right:2px"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 0C5.373 0 0 5.373 0 12c0 2.127.558 4.126 1.532 5.858L.054 23.454a.5.5 0 00.492.546h.048l5.788-1.517A11.95 11.95 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-1.907 0-3.693-.504-5.231-1.383l-.375-.217-3.885 1.018 1.036-3.774-.237-.386A9.959 9.959 0 012 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/></svg>
            <?= e($kontak) ?>
          </div>
          <?php endif; ?>
          <?php if ($mapsUrl): ?>
          <div class="uk-card-overlay-maps">
            <svg viewBox="0 0 24 24" fill="currentColor" width="10" height="10" style="display:inline;vertical-align:middle;margin-right:2px"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
            Lihat Lokasi
          </div>
          <?php endif; ?>
        </div>
      </div>
      <?php if ($judul || $namaUsaha): ?>
      <div class="uk-card-body">
        <?php if ($judul): ?><div class="uk-card-judul"><?= e($judul) ?></div><?php endif; ?>
        <?php if ($namaUsaha): ?><div class="uk-card-usaha"><?= e($namaUsaha) ?></div><?php endif; ?>
      </div>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

</div><!-- /.uk-wrap -->

<!-- Lightbox -->
<div class="uk-lightbox-overlay" id="ukLightboxOverlay" onclick="ukCloseLightbox()">
  <button class="uk-lightbox-close" onclick="ukCloseLightbox()" title="Tutup">&times;</button>
  <img class="uk-lightbox-img" id="ukLightboxImg" src="" alt="">
</div>

<!-- Modal -->
<div class="uk-modal-overlay" id="ukModalOverlay" onclick="ukCloseModal(event)">
  <div class="uk-modal" id="ukModal" onclick="event.stopPropagation()">
    <div class="uk-modal-inner">
      <div class="uk-modal-img-col">
        <img class="uk-modal-img" id="ukModalImg" src="" alt="" onclick="ukOpenLightbox(this.src, this.alt)" title="Klik untuk perbesar">
        <p id="ukModalCaption" style="display:none;margin:6px 0 0;font-size:12px;color:rgba(255,255,255,0.65);font-style:italic;line-height:1.5;text-align:center;"></p>
        <span class="uk-modal-badge">Pasar Umat</span>
      </div>
      <div class="uk-modal-body">
        <button class="uk-modal-close" onclick="ukCloseModal()" title="Tutup">&times;</button>
        <div class="uk-modal-kategori">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" width="10" height="10" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
          Pasar Umat · Paroki Tulungagung
        </div>
        <div class="uk-modal-judul" id="ukModalJudul"></div>
        <div class="uk-modal-usaha" id="ukModalUsaha"></div>
        <hr class="uk-modal-sep">
        <div class="uk-modal-desc" id="ukModalDesc"></div>
        <div id="ukModalMapsWrap"></div>
        <div id="ukModalWaWrap"></div>
      </div>
    </div>
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
(function () {
  var overlay = document.getElementById('ukModalOverlay');
  var _waWrap   = document.getElementById('ukModalWaWrap');
  var _mapsWrap = document.getElementById('ukModalMapsWrap');
  function toWaNumber(raw) { if(!raw) return ''; var n=raw.replace(/\D/g,''); if(n.startsWith('0')) n='62'+n.slice(1); if(!n.startsWith('62')) n='62'+n; return n; }

  window.ukOpenModal = function (data) {
    var img=document.getElementById('ukModalImg'),inner=document.querySelector('.uk-modal-inner');
    inner.classList.remove('uk-modal--landscape');
    img.src=data.src||''; img.alt=data.seoAlt||data.judul||''; img.title=data.seoTitle||'';
    img.onload=function(){ if(img.naturalWidth>img.naturalHeight) inner.classList.add('uk-modal--landscape'); else inner.classList.remove('uk-modal--landscape'); };
    if(img.complete&&img.naturalWidth){ if(img.naturalWidth>img.naturalHeight) inner.classList.add('uk-modal--landscape'); }
    document.getElementById('ukModalJudul').textContent=data.judul||data.namaUsaha||'';
    var usahaEl=document.getElementById('ukModalUsaha');
    if(data.namaUsaha){ usahaEl.innerHTML='<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>'+document.createTextNode(data.namaUsaha).nodeValue; usahaEl.style.display='flex'; }
    else { usahaEl.style.display='none'; }
    var descEl=document.getElementById('ukModalDesc');
    if(data.deskripsi&&data.deskripsi.trim()){ descEl.className='uk-modal-desc'; descEl.textContent=data.deskripsi; }
    else { descEl.className='uk-modal-desc-empty'; descEl.textContent='Belum ada deskripsi untuk usaha ini.'; }
    // ── SEO caption (ditampilkan sebagai keterangan foto jika ada) ──────
    var capEl=document.getElementById('ukModalCaption');
    if(capEl){ if(data.seoCap&&data.seoCap.trim()){ capEl.textContent=data.seoCap; capEl.style.display='block'; } else { capEl.style.display='none'; } }

    // ── Maps ──────────────────────────────────────────────────
    _mapsWrap.innerHTML='';
    if(data.mapsUrl&&data.mapsUrl.trim()){
      var mapsLink=document.createElement('a');
      mapsLink.className='uk-modal-maps';
      mapsLink.href=data.mapsUrl.trim();
      mapsLink.target='_blank';
      mapsLink.rel='noopener noreferrer';
      mapsLink.innerHTML='<span class="uk-modal-maps-icon"><svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg></span><span class="uk-modal-maps-text"><span class="uk-modal-maps-label">Lokasi Usaha</span><span class="uk-modal-maps-sub">Buka di Google Maps</span></span><span class="uk-modal-maps-arrow"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="14" height="14"><path d="M5 12h14M12 5l7 7-7 7"/></svg></span>';
      _mapsWrap.appendChild(mapsLink);
    }

    // ── WhatsApp ──────────────────────────────────────────────
    _waWrap.innerHTML='';
    var waNum=toWaNumber(data.kontak);
    if(waNum){ var waLink=document.createElement('a'); waLink.className='uk-modal-wa'; waLink.href='https://wa.me/'+waNum; waLink.target='_blank'; waLink.rel='noopener noreferrer'; waLink.innerHTML='<svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 0C5.373 0 0 5.373 0 12c0 2.127.558 4.126 1.532 5.858L.054 23.454a.5.5 0 00.492.546h.048l5.788-1.517A11.95 11.95 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-1.907 0-3.693-.504-5.231-1.383l-.375-.217-3.885 1.018 1.036-3.774-.237-.386A9.959 9.959 0 012 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/></svg> Chat WhatsApp'; _waWrap.appendChild(waLink); }
    else { _waWrap.innerHTML='<div class="uk-modal-wa-nokontak">Kontak tidak tersedia</div>'; }
    overlay.classList.add('open'); document.body.style.overflow='hidden';
  };
  window.ukCloseModal=function(e){ if(e&&e.target!==overlay) return; overlay.classList.remove('open'); var img=document.getElementById('ukModalImg'),inner=document.querySelector('.uk-modal-inner'); img.src=''; img.onload=null; inner.classList.remove('uk-modal--landscape'); document.body.style.overflow=''; };
  window.ukOpenLightbox=function(src,alt){ if(!src) return; var lb=document.getElementById('ukLightboxOverlay'),lbImg=document.getElementById('ukLightboxImg'); lbImg.src=src; lbImg.alt=alt||''; lb.classList.add('open'); event&&event.stopPropagation&&event.stopPropagation(); };
  window.ukCloseLightbox=function(){ var lb=document.getElementById('ukLightboxOverlay'); lb.classList.remove('open'); document.getElementById('ukLightboxImg').src=''; };
  document.addEventListener('keydown',function(e){ if(e.key==='Escape'){ var lb=document.getElementById('ukLightboxOverlay'); if(lb&&lb.classList.contains('open')){ lb.classList.remove('open'); document.getElementById('ukLightboxImg').src=''; return; } overlay.classList.remove('open'); var img=document.getElementById('ukModalImg'),inner=document.querySelector('.uk-modal-inner'); img.src=''; img.onload=null; inner.classList.remove('uk-modal--landscape'); document.body.style.overflow=''; }});
})();
</script>
</body>
</html>