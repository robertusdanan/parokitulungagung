<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/R2Client.php';
require_once __DIR__ . '/../includes/GaleriCache.php';

// Ekstensi yang dikenali sebagai foto / video di dalam album R2.
// Video umumnya sudah ditranscode ke mp4/mov/m4v/mkv oleh
// R2FolderCompressor saat diupload — daftar lain dipertahankan sebagai
// jaring pengaman untuk video lama / ffmpeg tidak tersedia saat upload.
const GALERI_IMAGE_EXTS = ['webp', 'jpg', 'jpeg', 'png', 'gif'];
const GALERI_VIDEO_EXTS = ['mp4', 'mov', 'm4v', 'mkv', 'avi', 'wmv', '3gp', '3g2', 'mpg', 'mpeg', 'mts', 'm2t', 'm2ts', 'asf', 'webm'];

// ── Ambil & validasi ID album ──────────────────────────────────────────
$albumId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$albumId) {
    header('Location: /galeri');
    exit;
}

// ── Ambil data album dari Supabase (cached 15 menit) ───────────────────
$rows = fetchSupabaseCached('galeri_foto', ['id' => $albumId], '', '*', 900);
$row  = (is_array($rows) && !empty($rows)) ? $rows[0] : null;

if (!$row) {
    http_response_code(404);
    header('Location: /galeri');
    exit;
}

$judul     = $row['Judul'] ?? 'Album Foto';
$tanggal   = $row['Tanggal'] ?? '';
$bulan     = $row['Bulan'] ?? '';
$keterangan= $row['Keterangan'] ?? '';
$kredit    = $row['Foto'] ?? '';
$gambar    = $row['Gambar'] ?? '';
// $coverSrc dipakai untuk tampilan publik (hero background & og:image) →
// langsung dari Cloudflare R2 CDN (_thumbnails/galeri/)
$_coverUrls = galeriCoverUrls($gambar);
$coverSrc   = $_coverUrls['display'] ?: assetUrl('og-preview.webp');

// ── Nama folder album di R2 ──────────────────────────────────────────────
// Memakai kolom "Link" (dulu dipakai untuk URL Google Foto) yang sekarang
// diisi admin dengan NAMA FOLDER ALBUM di R2 — persis sama dengan nama
// folder hasil Google Takeout. Fallback ke Judul kalau kolom itu kosong.
$folderPrefix = trim((string)($row['Link'] ?? ''));
if ($folderPrefix === '' || str_starts_with($folderPrefix, 'http://') || str_starts_with($folderPrefix, 'https://')) {
    $folderPrefix = trim($judul);
}

// ── Ambil daftar foto dari R2 (cache PERMANEN per-album) ────────────────
// Sekali dibuat, cache ini berlaku selamanya — tidak pernah fetch ulang
// ke R2 di kunjungan berikutnya. Cache baru dihapus (invalidate) saat
// admin mengubah/menghapus album (admin/api/sheets.php) atau menekan
// tombol "Refresh Foto" manual di admin (admin/api/galeri_cache.php).
$photos      = [];
$r2Error     = null;
$r2Configured = defined('R2_ACCESS_KEY') && defined('R2_SECRET_KEY') && defined('R2_ENDPOINT') && defined('R2_BUCKET');

if ($r2Configured && $folderPrefix !== '') {
    $cached = galeriPhotoCacheGet($albumId);

    // Cache dipakai selama nama folder album masih sama persis dengan saat
    // cache dibuat. Kalau kolom Link/Judul berubah tanpa invalidasi (jaring
    // pengaman), mismatch ini otomatis memicu pengambilan ulang dari R2.
    if ($cached !== null && ($cached['folder'] ?? null) === $folderPrefix) {
        $photos = $cached['photos'];
    } else {
        try {
            $r2  = new R2Client(R2_ACCESS_KEY, R2_SECRET_KEY, R2_ENDPOINT, R2_BUCKET);
            $objs = $r2->listObjects(R2_ALBUM_PREFIX . $folderPrefix);
            // File gambar ATAU video yang didukung. Poster/thumbnail video
            // (key + ".poster.webp") dibuang dari daftar utama — itu bukan
            // item media tersendiri, hanya aset pendukung untuk 1 video.
            $objs = array_values(array_filter($objs, function ($o) {
                if (str_ends_with($o['key'], '.poster.webp')) return false;
                $ext = strtolower(pathinfo($o['key'], PATHINFO_EXTENSION));
                return in_array($ext, GALERI_IMAGE_EXTS, true) || in_array($ext, GALERI_VIDEO_EXTS, true);
            }));
            // Urutkan alami berdasar nama file (mis. IMG_1.webp sebelum IMG_10.webp)
            usort($objs, fn($a, $b) => strnatcasecmp($a['key'], $b['key']));
            $photos = $objs;
            galeriPhotoCacheSet($albumId, $folderPrefix, $photos);
        } catch (Throwable $e) {
            // R2 sedang bermasalah — kalau masih ada cache lama (folder
            // berbeda karena baru diganti), lebih baik tampilkan itu
            // daripada halaman kosong.
            if ($cached !== null) {
                $photos = $cached['photos'];
            } else {
                $r2Error = 'Gagal memuat foto dari penyimpanan.';
            }
            error_log('R2 listObjects error (album #' . $albumId . '): ' . $e->getMessage());
        }
    }
} elseif (!$r2Configured) {
    $r2Error = 'Penyimpanan foto (Cloudflare R2) belum dikonfigurasi di server.';
}

// ── SEO per-foto (tabel image_seo, dicocokkan lewat prefix) ─────────────
if (!function_exists('fetchImageSeoByPrefixR2')) {
    function fetchImageSeoByPrefixR2(string $prefix): array {
        if (!defined('SUPABASE_URL') || !defined('SUPABASE_ANON_KEY')) return [];
        $ck = 'img_seo_r2_' . md5($prefix);
        $cv = cache_get($ck);
        if ($cv !== null) return $cv;
        $url = rtrim(SUPABASE_URL, '/') . '/rest/v1/image_seo'
             . '?image_url=like.' . rawurlencode($prefix . '%')
             . '&select=image_url,alt_text,caption,title_attr,schema_description,image_keywords'
             . '&limit=1000';
        $ctx = stream_context_create([
            'http' => ['header' => "apikey: " . SUPABASE_ANON_KEY . "\r\nAuthorization: Bearer " . SUPABASE_ANON_KEY . "\r\nAccept: application/json\r\n", 'timeout' => 5, 'ignore_errors' => true],
            'ssl'  => ['verify_peer' => true],
        ]);
        $result = [];
        $res = @file_get_contents($url, false, $ctx);
        if ($res) {
            $decoded = json_decode($res, true);
            if (is_array($decoded)) foreach ($decoded as $r) if (!empty($r['image_url'])) $result[$r['image_url']] = $r;
        }
        cache_set($ck, $result, 600);
        return $result;
    }
}

// ── URL foto: langsung ke Cloudflare CDN (custom domain R2), TANPA watermark ──
// (dulu lewat proxy /r2-image.php?key=... di server; sekarang browser
// mengambil bytes langsung dari R2 lewat CDN Cloudflare — lebih cepat,
// tapi foto tidak lagi diberi watermark server-side. Lihat catatan di
// includes/config.php soal setup Custom Domain R2 yang benar.)
$seoPrefixUrl = r2CdnUrl(R2_ALBUM_PREFIX . rtrim($folderPrefix, '/') . '/');
$photoSeoMap  = $folderPrefix !== '' ? fetchImageSeoByPrefixR2($seoPrefixUrl) : [];

// ── Bangun model foto untuk render + JS lightbox ─────────────────────────
// 'url'      → ukuran ASLI (dipakai lightbox/zoom & JSON-LD schema)
// 'urlThumb' → dipakai grid. Sama dengan 'url' (ukuran ASLI) selama
//              R2_CDN_IMAGE_RESIZING masih false — tidak ada resize
//              server-side lagi setelah lepas dari proxy r2-image.php.
//              Nyalakan konstanta itu (setelah aktifkan Cloudflare Image
//              Resizing di dashboard) supaya grid otomatis pakai versi kecil.
$photoList = [];
$videoList = [];
$photoIdx  = 0;
$videoIdx  = 0;
foreach ($photos as $p) {
    $ext = strtolower(pathinfo($p['key'], PATHINFO_EXTENSION));

    if (in_array($ext, GALERI_VIDEO_EXTS, true)) {
        $videoIdx++;
        $videoUrl  = r2CdnUrl($p['key']);
        $posterUrl = r2CdnUrl($p['key'] . '.poster.webp');
        $videoList[] = [
            'url'      => $videoUrl,
            'poster'   => $posterUrl,
            'alt'      => $judul . ' — Video ' . $videoIdx . ' — Dokumentasi Paroki SMDTBA Tulungagung',
            'title'    => $judul,
        ];
        continue;
    }

    $imgUrl   = r2CdnUrl($p['key']);
    $thumbUrl = r2CdnThumbUrl($p['key']);
    $seo      = $photoSeoMap[$imgUrl] ?? [];
    $photoIdx++;
    $altText    = $seo['alt_text'] ?? ($judul . ' — Foto ' . $photoIdx . ' — Dokumentasi Paroki SMDTBA Tulungagung');
    $photoList[] = [
        'url'      => $imgUrl,
        'urlThumb' => $thumbUrl,
        'alt'      => $altText,
        'title'    => $seo['title_attr'] ?? $judul,
        'cap'      => $seo['caption'] ?? '',
    ];
}

$totalFoto   = count($photoList);
$totalVideo  = count($videoList);
$hasPhotos   = $totalFoto > 0;
$hasVideos   = $totalVideo > 0;
$showTabs    = $hasPhotos && $hasVideos;
$activeTab   = ($showTabs && ($_GET['tab'] ?? '') === 'video') ? 'video' : ($hasPhotos ? 'foto' : 'video');

// ── Pagination ────────────────────────────────────────────────────────
// Supaya browser tidak perlu memuat SEMUA foto album sekaligus (bisa
// ratusan), foto dibagi per halaman: 60 foto/halaman di desktop, 45
// foto/halaman di mobile (grid mobile dibuat 3 kolom lewat CSS, jadi 45
// pas menjadi kelipatan 3 → tidak ada baris ganjil).
$isMobile   = isMobileUserAgent();
$perPage    = $isMobile ? 45 : 60;
$totalPages = max(1, (int)ceil($totalFoto / $perPage));
$page       = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
$page       = max(1, min($page, $totalPages));
$pageOffset = ($page - 1) * $perPage;
$pagePhotos = array_slice($photoList, $pageOffset, $perPage);

// Fallback background hero jika cover belum ada: pakai foto pertama
if (($coverSrc === '' || $coverSrc === assetUrl('og-preview.webp')) && !empty($photoList[0]['url'])) {
    $coverSrc = $photoList[0]['url'];
}

// ── SEO halaman ───────────────────────────────────────────────────────
$slug          = slugify($judul);
$canonicalBase = 'https://www.parokitulungagung.org/galeri/album/' . $albumId . '/' . $slug;
$seo = [
    'title'       => $judul . ($page > 1 ? ' — Halaman ' . $page : '') . ' — Galeri Foto Paroki Tulungagung',
    'description' => $keterangan
        ? mb_substr(strip_tags($keterangan), 0, 155, 'UTF-8')
        : ($totalFoto . ' foto dokumentasi "' . $judul . '" — Paroki Santa Maria Dengan Tidak Bernoda Asal Tulungagung.'),
    'canonical'   => $canonicalBase . ($page > 1 ? '?page=' . $page : ''),
    'keywords'    => 'galeri foto paroki tulungagung, ' . mb_strtolower($judul) . ', dokumentasi paroki smdtba',
    'type'        => 'website',
    'image'       => str_starts_with($coverSrc, 'http') ? $coverSrc : 'https://www.parokitulungagung.org' . $coverSrc,
    'modified'    => $tanggal ?: date('Y-m-d'),
];
$breadcrumbs = [
    ['name' => 'Beranda',    'url' => 'https://www.parokitulungagung.org'],
    ['name' => 'Galeri Foto','url' => 'https://www.parokitulungagung.org/galeri'],
    ['name' => $judul,       'url' => $canonicalBase],
];
$extraCss = ['/css/content.css'];
?>
<!doctype html>
<html lang="id">
<head>
  <?php include __DIR__ . '/../components/seo_head.php'; ?>
  <script type="application/ld+json">
  <?php
  $_base = 'https://www.parokitulungagung.org';
  $_imgObjs = [];
  foreach (array_slice($photoList, 0, 30) as $_i => $_p) {
      $_imgObjs[] = [
          '@type'      => 'ImageObject',
          'contentUrl' => $_p['url'],
          'url'        => $_p['url'],
          'name'       => strip_tags($_p['title'] ?: $judul),
          'description'=> strip_tags($_p['cap'] ?: $_p['alt']),
          'datePublished' => $tanggal,
          'creditText' => $kredit ?: 'Paroki Santa Maria Dengan Tidak Bernoda Asal Tulungagung',
          'copyrightNotice' => '© ' . date('Y') . ' Paroki SMDTBA Tulungagung',
          'author'     => ['@id' => $_base . '/#organization'],
      ];
  }
  $_schema = [
      '@context'   => 'https://schema.org',
      '@type'      => ['CollectionPage', 'ImageGallery'],
      '@id'        => $seo['canonical'] . '#gallery',
      'name'       => $judul,
      'description'=> $seo['description'],
      'url'        => $seo['canonical'],
      'inLanguage' => 'id-ID',
      'isPartOf'   => ['@id' => $_base . '/#website'],
      'about'      => ['@id' => $_base . '/#organization'],
      'publisher'  => ['@id' => $_base . '/#organization'],
      'dateModified' => $tanggal ?: date('Y-m-d'),
      'datePublished'=> $tanggal ?: date('Y-m-d'),
  ];
  if (!empty($_imgObjs)) {
      $_schema['image'] = $_imgObjs;
      $_schema['primaryImageOfPage'] = $_imgObjs[0];
      $_schema['numberOfItems'] = $totalFoto;
  }
  echo json_encode($_schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
  ?>
  </script>

  <style>
  .ga-wrap{max-width:1100px;margin:0 auto;padding:0 0 32px}
  .ga-breadcrumb{display:flex;flex-wrap:wrap;align-items:center;gap:6px;font-size:12px;color:var(--g-ink3,#7a7570);padding:18px 2px 0;font-family:'Montserrat',sans-serif}
  .ga-breadcrumb a{color:var(--g-gold,#b8922a);text-decoration:none;font-weight:600}
  .ga-breadcrumb a:hover{text-decoration:underline}
  .ga-breadcrumb svg{width:11px;height:11px;flex-shrink:0;opacity:.5}

  .ga-hero{position:relative;border-radius:16px;overflow:hidden;margin:14px 2px 26px;min-height:220px;display:flex;align-items:flex-end;background:#1a1814}
  .ga-hero-bg{position:absolute;inset:0;background-size:cover;background-position:center;filter:brightness(.5) saturate(1.05);transform:scale(1.03)}
  .ga-hero-scrim{position:absolute;inset:0;background:linear-gradient(180deg,rgba(20,18,14,.15) 0%,rgba(20,18,14,.85) 100%)}
  .ga-hero-inner{position:relative;padding:22px 22px 20px;color:#fff;width:100%}
  .ga-hero-chip{display:inline-flex;align-items:center;gap:6px;font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#1a1814;background:var(--g-gold,#b8922a);border-radius:20px;padding:4px 12px;margin-bottom:12px}
  .ga-hero-title{font-family:'Cormorant Garamond',Georgia,serif;font-size:clamp(24px,4vw,36px);font-weight:700;line-height:1.15;margin:0 0 8px;text-shadow:0 2px 12px rgba(0,0,0,.4)}
  .ga-hero-meta{display:flex;flex-wrap:wrap;gap:14px;font-size:12.5px;color:rgba(255,255,255,.85)}
  .ga-hero-meta span{display:inline-flex;align-items:center;gap:6px}
  .ga-hero-meta svg{width:14px;height:14px;flex-shrink:0;opacity:.85}

  .ga-desc{background:var(--g-cream,#faf8f4);border-left:3px solid var(--g-gold,#b8922a);border-radius:0 12px 12px 0;padding:16px 18px;margin:0 2px 26px;font-size:14px;line-height:1.75;color:var(--g-ink2,#3d3a34)}

  .ga-toolbar{display:flex;align-items:center;justify-content:space-between;gap:12px;margin:0 2px 16px;flex-wrap:wrap}
  .ga-count{font-size:12.5px;color:var(--g-ink3,#7a7570);font-family:'Montserrat',sans-serif}
  .ga-count strong{color:var(--g-ink,#1a1814)}
  .ga-back{display:inline-flex;align-items:center;gap:6px;font-size:12.5px;font-weight:600;color:var(--g-ink,#1a1814);text-decoration:none;padding:8px 14px;border:1.5px solid var(--g-line,#e8e2d8);border-radius:20px;transition:all .2s}
  .ga-back:hover{border-color:var(--g-gold,#b8922a);color:var(--g-gold,#b8922a)}
  .ga-back svg{width:14px;height:14px}

  .ga-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:10px;padding:0 2px}
  .ga-item{position:relative;border-radius:10px;overflow:hidden;cursor:zoom-in;background:var(--g-cream,#f4efe6);aspect-ratio:1/1;border:1px solid var(--g-line,#e8e2d8);animation:gaItemIn .4s ease both}
  @keyframes gaItemIn{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:none}}
  .ga-item img{width:100%;height:100%;object-fit:cover;display:block;transition:transform .35s ease,opacity .25s ease}
  .ga-item:hover img{transform:scale(1.05)}
  .ga-item-num{position:absolute;bottom:6px;right:6px;background:rgba(20,18,14,.55);color:rgba(255,255,255,.85);font-size:10px;font-weight:600;padding:2px 7px;border-radius:20px;backdrop-filter:blur(4px)}

  /* Mobile: grid dipaksa 3 kolom (bukan auto-fill) supaya rapi & konsisten
     dengan pagination 45 foto/halaman (kelipatan 3, tidak ada baris ganjil) */
  @media(max-width:768px){
    .ga-grid{grid-template-columns:repeat(3,1fr);gap:6px}
    .ga-item{border-radius:7px}
    .ga-item-num{font-size:9px;padding:1px 5px;bottom:4px;right:4px}
  }

  /* ── Pagination ── */
  .ga-pagination{display:flex;align-items:center;justify-content:center;flex-wrap:wrap;gap:6px;margin:28px 2px 6px}
  .ga-page-btn{display:inline-flex;align-items:center;justify-content:center;min-width:36px;height:36px;padding:0 10px;border-radius:9px;border:1.5px solid var(--g-line,#e8e2d8);background:#fff;color:var(--g-ink2,#3d3a34);font-size:13px;font-weight:600;text-decoration:none;font-family:'Montserrat',sans-serif;transition:all .2s}
  .ga-page-btn:hover{border-color:var(--g-gold,#b8922a);color:var(--g-gold,#b8922a)}
  .ga-page-btn.active{background:var(--g-ink,#1a1814);border-color:var(--g-ink,#1a1814);color:#fff}
  .ga-page-btn.disabled{opacity:.35;pointer-events:none}
  .ga-page-ellipsis{display:inline-flex;align-items:center;justify-content:center;min-width:24px;height:36px;color:var(--g-ink3,#7a7570);font-size:13px}
  @media(max-width:480px){
    .ga-page-btn{min-width:32px;height:32px;padding:0 8px;font-size:12px}
    .ga-page-ellipsis{height:32px}
  }

  .ga-empty{text-align:center;padding:70px 20px;color:var(--g-ink3,#7a7570)}
  .ga-empty svg{margin:0 auto 14px;display:block;opacity:.35}
  .ga-empty-title{font-weight:700;font-size:16px;color:var(--g-ink,#1a1814);margin-bottom:6px}
  .ga-empty-sub{font-size:13px;max-width:380px;margin:0 auto}

  /* Lightbox nav tambahan (memakai kerangka .uk-lightbox-* dari content.css) */
  .ga-lb-nav{position:fixed;top:50%;transform:translateY(-50%);width:44px;height:44px;border-radius:50%;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.28);color:#fff;display:flex;align-items:center;justify-content:center;cursor:pointer;z-index:11000;transition:background .15s}
  .ga-lb-nav:hover{background:rgba(255,255,255,.24)}
  .ga-lb-nav--prev{left:16px}
  .ga-lb-nav--next{right:16px}
  .ga-lb-counter{position:fixed;bottom:18px;left:50%;transform:translateX(-50%);z-index:11000;color:rgba(255,255,255,.8);font-size:12px;font-family:'Montserrat',sans-serif;background:rgba(255,255,255,.1);padding:5px 14px;border-radius:20px}
  @media(max-width:560px){.ga-lb-nav{width:38px;height:38px}.ga-lb-nav--prev{left:6px}.ga-lb-nav--next{right:6px}}

  /* ── Tab dinamis Foto / Video (hanya muncul kalau album punya keduanya) ── */
  .ga-tabs{display:inline-flex;gap:4px;background:var(--g-cream,#faf8f4);border:1px solid var(--g-line,#e8e2d8);border-radius:24px;padding:4px;margin:0 2px 18px}
  .ga-tab{display:inline-flex;align-items:center;gap:6px;padding:9px 16px;border-radius:20px;border:0;background:transparent;color:var(--g-ink3,#7a7570);font-size:12.5px;font-weight:600;font-family:'Montserrat',sans-serif;cursor:pointer;transition:background .2s,color .2s}
  .ga-tab svg{width:14px;height:14px;flex-shrink:0}
  .ga-tab:hover{color:var(--g-gold,#b8922a)}
  .ga-tab.active{background:var(--g-ink,#1a1814);color:#fff}
  .ga-tab-count{font-size:10px;font-weight:700;padding:1px 6px;border-radius:20px;background:rgba(0,0,0,.06)}
  .ga-tab.active .ga-tab-count{background:rgba(255,255,255,.18)}
  @media(max-width:480px){.ga-tab{padding:8px 13px;font-size:11.5px}}

  /* ── Panel Foto / Video ── */
  .ga-panel{display:none}
  .ga-panel.active{display:block}

  /* ── Kartu video di grid: poster + tombol putar ── */
  .ga-item--video{cursor:pointer}
  .ga-item--video::after{content:'';position:absolute;inset:0;background:linear-gradient(to top,rgba(20,18,14,.4) 0%,transparent 45%);pointer-events:none}
  .ga-item--video .ga-play-badge{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;pointer-events:none;z-index:1}
  .ga-item--video .ga-play-badge svg{width:42px;height:42px;filter:drop-shadow(0 2px 10px rgba(0,0,0,.55));transition:transform .25s cubic-bezier(.4,0,.2,1)}
  .ga-item--video:hover .ga-play-badge svg{transform:scale(1.14)}
  .ga-item--video.ga-noposter{background:linear-gradient(135deg,#2e2a20,#1a1814)}
  @media(max-width:768px){.ga-item--video .ga-play-badge svg{width:30px;height:30px}}

  /* ── Player video elegan ── */
  .gv-lightbox-overlay{display:none;position:fixed;inset:0;z-index:10999;background:rgba(0,0,0,.93);backdrop-filter:blur(10px);-webkit-backdrop-filter:blur(10px);align-items:center;justify-content:center;padding:20px}
  .gv-lightbox-overlay.open{display:flex}
  .gv-lb-video-wrap{width:100%;max-width:960px;animation:ukLbIn .22s cubic-bezier(.34,1.4,.64,1)}
  .gv-lb-video{width:100%;max-height:86vh;border-radius:10px;box-shadow:0 20px 60px rgba(0,0,0,.6);background:#000;display:block}
  </style>
</head>
<body>
<?php $headerTitle = $judul; include __DIR__ . '/../components/page_header.php'; ?>

<main id="main-content" style="padding:6px">
<div class="ga-wrap">

  <nav class="ga-breadcrumb" aria-label="Breadcrumb">
    <a href="/">Beranda</a>
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="9 18 15 12 9 6"/></svg>
    <a href="/galeri">Galeri Foto</a>
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="9 18 15 12 9 6"/></svg>
    <span><?= e($judul) ?></span>
  </nav>

  <div class="ga-hero">
    <div class="ga-hero-bg" style="background-image:url('<?= e($coverSrc) ?>')"></div>
    <div class="ga-hero-scrim"></div>
    <div class="ga-hero-inner">
      <?php if ($bulan || $tanggal): ?>
      <span class="ga-hero-chip">
        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        <?= e($bulan . ($bulan && $tanggal ? ' · ' : '') . substr($tanggal, 0, 4)) ?>
      </span>
      <?php endif; ?>
      <h1 class="ga-hero-title"><?= e($judul) ?></h1>
      <div class="ga-hero-meta">
        <?php if ($tanggal): ?>
        <span>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
          <?= e(formatTanggalIndo($tanggal)) ?>
        </span>
        <?php endif; ?>
        <?php if ($hasPhotos): ?>
        <span>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
          <?= $totalFoto ?> Foto
        </span>
        <?php endif; ?>
        <?php if ($hasVideos): ?>
        <span>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2"/></svg>
          <?= $totalVideo ?> Video
        </span>
        <?php endif; ?>
        <?php if ($kredit): ?>
        <span>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          <?= e($kredit) ?>
        </span>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <?php if ($keterangan): ?>
  <p class="ga-desc"><?= nl2br(e($keterangan)) ?></p>
  <?php endif; ?>

  <div class="ga-toolbar">
    <div class="ga-count">
      <span class="ga-count-foto" <?= $activeTab !== 'foto' ? 'style="display:none"' : '' ?>><?php if ($totalFoto > 0): ?>
        Menampilkan <strong><?= count($pagePhotos) ?></strong> dari <strong><?= $totalFoto ?></strong> foto
        <?php if ($totalPages > 1): ?>&nbsp;·&nbsp;Halaman <?= $page ?> / <?= $totalPages ?><?php endif; ?>
      <?php endif; ?></span>
      <span class="ga-count-video" <?= $activeTab !== 'video' ? 'style="display:none"' : '' ?>><?php if ($totalVideo > 0): ?>
        Menampilkan <strong><?= $totalVideo ?></strong> video
      <?php endif; ?></span>
    </div>
    <a href="/galeri" class="ga-back">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
      Kembali ke Galeri
    </a>
  </div>

  <?php if ($showTabs): ?>
  <div class="ga-tabs" role="tablist" id="gaTabs">
    <button type="button" class="ga-tab <?= $activeTab === 'foto' ? 'active' : '' ?>" data-tab="foto" role="tab" aria-selected="<?= $activeTab === 'foto' ? 'true' : 'false' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
      Foto <span class="ga-tab-count"><?= $totalFoto ?></span>
    </button>
    <button type="button" class="ga-tab <?= $activeTab === 'video' ? 'active' : '' ?>" data-tab="video" role="tab" aria-selected="<?= $activeTab === 'video' ? 'true' : 'false' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2"/></svg>
      Video <span class="ga-tab-count"><?= $totalVideo ?></span>
    </button>
  </div>
  <?php endif; ?>

  <?php if (!$hasPhotos && !$hasVideos): ?>
  <div class="ga-empty">
    <svg viewBox="0 0 64 64" fill="none" stroke="#c9a84c" stroke-width="2" width="52" height="52"><rect x="8" y="12" width="48" height="40" rx="4"/><circle cx="22" cy="26" r="5"/><path d="M8 44l14-12 10 9 8-7 16 14"/></svg>
    <div class="ga-empty-title">Belum Ada Media Ditampilkan</div>
    <p class="ga-empty-sub">
      <?= $r2Error ? e($r2Error) : 'Album ini belum memiliki foto/video, atau folder album di penyimpanan belum sesuai.' ?>
    </p>
  </div>
  <?php endif; ?>

  <?php if ($hasPhotos): ?>
  <div class="ga-panel <?= $activeTab === 'foto' ? 'active' : '' ?>" id="gaPanelFoto" data-tab-panel="foto">
    <div class="ga-grid" id="gaGrid">
      <?php foreach ($pagePhotos as $idx => $p): ?>
      <div class="ga-item" data-idx="<?= $idx ?>" tabindex="0" role="button" aria-label="Lihat foto <?= $pageOffset + $idx + 1 ?>">
        <img src="<?= e($p['urlThumb']) ?>"
             alt="<?= e($p['alt']) ?>"
             title="<?= e($p['title']) ?>"
             loading="<?= $idx < 8 ? 'eager' : 'lazy' ?>"
             decoding="async"
             data-full="<?= e($p['url']) ?>"
             onerror="if(!this.dataset.fb){this.dataset.fb='1';this.src=this.dataset.full;}else{this.closest('.ga-item').style.opacity='.35';}">
        <span class="ga-item-num"><?= $pageOffset + $idx + 1 ?></span>
      </div>
      <?php endforeach; ?>
    </div>

    <?php if ($totalPages > 1):
          // Jendela nomor halaman: current ± 2, selalu tampilkan halaman
          // pertama & terakhir, sisanya diwakili "…"
          $windowStart = max(1, $page - 2);
          $windowEnd   = min($totalPages, $page + 2);
          $pageUrl     = fn(int $p) => '/galeri/album/' . $albumId . '/' . $slug . ($p > 1 ? '?page=' . $p : '');
    ?>
    <nav class="ga-pagination" aria-label="Navigasi halaman foto">
      <a href="<?= e($pageUrl(max(1, $page - 1))) ?>" class="ga-page-btn <?= $page <= 1 ? 'disabled' : '' ?>" aria-label="Halaman sebelumnya">‹</a>

      <?php if ($windowStart > 1): ?>
        <a href="<?= e($pageUrl(1)) ?>" class="ga-page-btn">1</a>
        <?php if ($windowStart > 2): ?><span class="ga-page-ellipsis">…</span><?php endif; ?>
      <?php endif; ?>

      <?php for ($p = $windowStart; $p <= $windowEnd; $p++): ?>
        <a href="<?= e($pageUrl($p)) ?>" class="ga-page-btn <?= $p === $page ? 'active' : '' ?>"><?= $p ?></a>
      <?php endfor; ?>

      <?php if ($windowEnd < $totalPages): ?>
        <?php if ($windowEnd < $totalPages - 1): ?><span class="ga-page-ellipsis">…</span><?php endif; ?>
        <a href="<?= e($pageUrl($totalPages)) ?>" class="ga-page-btn"><?= $totalPages ?></a>
      <?php endif; ?>

      <a href="<?= e($pageUrl(min($totalPages, $page + 1))) ?>" class="ga-page-btn <?= $page >= $totalPages ? 'disabled' : '' ?>" aria-label="Halaman berikutnya">›</a>
    </nav>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <?php if ($hasVideos): ?>
  <div class="ga-panel <?= $activeTab === 'video' ? 'active' : '' ?>" id="gaPanelVideo" data-tab-panel="video">
    <div class="ga-grid ga-grid--video" id="gaVideoGrid">
      <?php foreach ($videoList as $idx => $v): ?>
      <div class="ga-item ga-item--video" data-idx="<?= $idx ?>" tabindex="0" role="button" aria-label="Putar video <?= $idx + 1 ?>">
        <img src="<?= e($v['poster']) ?>"
             alt="<?= e($v['alt']) ?>"
             loading="lazy"
             decoding="async"
             onerror="this.closest('.ga-item--video').classList.add('ga-noposter');this.remove();">
        <span class="ga-play-badge">
          <svg viewBox="0 0 24 24" fill="#fff"><circle cx="12" cy="12" r="11" fill="rgba(20,18,14,.5)"/><path d="M10 8.5l6 3.5-6 3.5v-7z"/></svg>
        </span>
        <span class="ga-item-num"><?= $idx + 1 ?></span>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

</div>
</main>

<!-- Lightbox foto (kerangka class dari content.css: .uk-lightbox-*) -->
<div class="uk-lightbox-overlay" id="gaLightbox">
  <button class="uk-lightbox-close" id="gaLbClose" title="Tutup">&times;</button>
  <button class="ga-lb-nav ga-lb-nav--prev" id="gaLbPrev" aria-label="Sebelumnya">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
  </button>
  <img class="uk-lightbox-img" id="gaLbImg" src="" alt="">
  <button class="ga-lb-nav ga-lb-nav--next" id="gaLbNext" aria-label="Berikutnya">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
  </button>
  <div class="ga-lb-counter" id="gaLbCounter"></div>
</div>

<!-- Player video — elegan, autoplay saat dibuka -->
<div class="gv-lightbox-overlay" id="gvLightbox">
  <button class="uk-lightbox-close" id="gvLbClose" title="Tutup">&times;</button>
  <button class="ga-lb-nav ga-lb-nav--prev" id="gvLbPrev" aria-label="Video sebelumnya">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
  </button>
  <div class="gv-lb-video-wrap">
    <video class="gv-lb-video" id="gvLbVideo" controls playsinline preload="none"></video>
  </div>
  <button class="ga-lb-nav ga-lb-nav--next" id="gvLbNext" aria-label="Video berikutnya">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
  </button>
  <div class="ga-lb-counter" id="gvLbCounter"></div>
</div>

<?php include __DIR__ . '/../components/footer.php'; ?>
<script src="/js/app.js?v=<?= substr(md5_file(__DIR__ . '/../js/app.js'), 0, 8) ?>"></script>
<script>
(function(){
  var PHOTOS = <?= json_encode(array_map(fn($p) => ['url' => $p['url'], 'alt' => $p['alt']], $pagePhotos), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
  var grid    = document.getElementById('gaGrid');
  var overlay = document.getElementById('gaLightbox');
  var lbImg   = document.getElementById('gaLbImg');
  var lbClose = document.getElementById('gaLbClose');
  var lbPrev  = document.getElementById('gaLbPrev');
  var lbNext  = document.getElementById('gaLbNext');
  var lbCounter = document.getElementById('gaLbCounter');
  var current = 0;

  if (!grid || !overlay || !PHOTOS.length) return;

  function show(idx){
    current = (idx + PHOTOS.length) % PHOTOS.length;
    var p = PHOTOS[current];
    lbImg.src = p.url;
    lbImg.alt = p.alt || '';
    lbCounter.textContent = (current + 1) + ' / ' + PHOTOS.length;
  }

  function open(idx){
    show(idx);
    overlay.classList.add('open');
    document.body.style.overflow = 'hidden';
  }

  function close(){
    overlay.classList.remove('open');
    document.body.style.overflow = '';
  }

  grid.addEventListener('click', function(e){
    var item = e.target.closest('.ga-item[data-idx]');
    if (!item) return;
    open(parseInt(item.dataset.idx, 10));
  });
  grid.addEventListener('keydown', function(e){
    var item = e.target.closest('.ga-item[data-idx]');
    if (!item) return;
    if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); open(parseInt(item.dataset.idx, 10)); }
  });

  lbClose.addEventListener('click', close);
  lbPrev.addEventListener('click', function(e){ e.stopPropagation(); show(current - 1); });
  lbNext.addEventListener('click', function(e){ e.stopPropagation(); show(current + 1); });
  overlay.addEventListener('click', function(e){ if (e.target === overlay || e.target === lbImg) close(); });

  document.addEventListener('keydown', function(e){
    if (!overlay.classList.contains('open')) return;
    if (e.key === 'Escape')     close();
    if (e.key === 'ArrowLeft')  show(current - 1);
    if (e.key === 'ArrowRight') show(current + 1);
  });

  // Swipe sederhana untuk mobile
  var touchX = null;
  overlay.addEventListener('touchstart', function(e){ touchX = e.changedTouches[0].clientX; }, {passive:true});
  overlay.addEventListener('touchend', function(e){
    if (touchX === null) return;
    var dx = e.changedTouches[0].clientX - touchX;
    if (Math.abs(dx) > 40) show(current + (dx < 0 ? 1 : -1));
    touchX = null;
  }, {passive:true});
})();

// ── Tab dinamis Foto / Video ─────────────────────────────────────────────
(function(){
  var tabs = document.getElementById('gaTabs');
  if (!tabs) return; // album cuma 1 jenis media → tidak ada tab, tidak perlu JS ini

  var buttons     = tabs.querySelectorAll('.ga-tab');
  var panelFoto   = document.getElementById('gaPanelFoto');
  var panelVideo  = document.getElementById('gaPanelVideo');
  var countFoto   = document.querySelector('.ga-count-foto');
  var countVideo  = document.querySelector('.ga-count-video');

  function activate(tab){
    buttons.forEach(function(b){
      var on = b.dataset.tab === tab;
      b.classList.toggle('active', on);
      b.setAttribute('aria-selected', on ? 'true' : 'false');
    });
    if (panelFoto)  panelFoto.classList.toggle('active', tab === 'foto');
    if (panelVideo) panelVideo.classList.toggle('active', tab === 'video');
    if (countFoto)  countFoto.style.display  = tab === 'foto'  ? '' : 'none';
    if (countVideo) countVideo.style.display = tab === 'video' ? '' : 'none';

    var url = new URL(window.location.href);
    if (tab === 'video') url.searchParams.set('tab', 'video');
    else url.searchParams.delete('tab');
    window.history.replaceState(null, '', url.pathname + url.search);
  }

  buttons.forEach(function(b){
    b.addEventListener('click', function(){ activate(b.dataset.tab); });
  });
})();

// ── Player video ──────────────────────────────────────────────────────────
(function(){
  var VIDEOS = <?= json_encode(array_map(fn($v) => ['url' => $v['url'], 'poster' => $v['poster'], 'alt' => $v['alt']], $videoList), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
  var grid    = document.getElementById('gaVideoGrid');
  var overlay = document.getElementById('gvLightbox');
  var vid     = document.getElementById('gvLbVideo');
  var vClose  = document.getElementById('gvLbClose');
  var vPrev   = document.getElementById('gvLbPrev');
  var vNext   = document.getElementById('gvLbNext');
  var vCounter= document.getElementById('gvLbCounter');
  var current = 0;

  if (!grid || !overlay || !VIDEOS.length) return;

  function show(idx){
    current = (idx + VIDEOS.length) % VIDEOS.length;
    var v = VIDEOS[current];
    vid.pause();
    vid.poster = v.poster || '';
    vid.src    = v.url;
    vid.setAttribute('aria-label', v.alt || '');
    vCounter.textContent = (current + 1) + ' / ' + VIDEOS.length;
    vid.play().catch(function(){ /* autoplay diblok browser — user tinggal tekan play */ });
  }

  function open(idx){
    show(idx);
    overlay.classList.add('open');
    document.body.style.overflow = 'hidden';
  }

  function close(){
    vid.pause();
    vid.removeAttribute('src');
    vid.load();
    overlay.classList.remove('open');
    document.body.style.overflow = '';
  }

  grid.addEventListener('click', function(e){
    var item = e.target.closest('.ga-item--video[data-idx]');
    if (!item) return;
    open(parseInt(item.dataset.idx, 10));
  });
  grid.addEventListener('keydown', function(e){
    var item = e.target.closest('.ga-item--video[data-idx]');
    if (!item) return;
    if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); open(parseInt(item.dataset.idx, 10)); }
  });

  vClose.addEventListener('click', close);
  vPrev.addEventListener('click', function(e){ e.stopPropagation(); show(current - 1); });
  vNext.addEventListener('click', function(e){ e.stopPropagation(); show(current + 1); });
  overlay.addEventListener('click', function(e){ if (e.target === overlay) close(); });

  document.addEventListener('keydown', function(e){
    if (!overlay.classList.contains('open')) return;
    if (e.key === 'Escape') close();
    // ArrowLeft/Right dibiarkan untuk kontrol seek bawaan <video>, navigasi
    // antar-video tetap lewat tombol prev/next supaya tidak bentrok.
  });
})();
</script>
</body>
</html>
