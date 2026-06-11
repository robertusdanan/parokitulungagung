<?php
// pages/tvdigital.php
while (ob_get_level() > 0) { ob_end_clean(); }

/* ════════════════════════════════════════════════════════
   SEO VARIABLES — dioptimalkan untuk Google Search
════════════════════════════════════════════════════════ */
$canonical   = 'https://www.parokitulungagung.org/tvdigital';
$site_name   = 'Paroki Tulungagung';
$site_url    = 'https://www.parokitulungagung.org';

$title       = 'TV Digital Indonesia Live Streaming — Nonton Gratis | Paroki Tulungagung';
$desc        = 'Nonton siaran langsung TV digital Indonesia gratis: RCTI, MNCTV, GTV, Trans7, Trans TV, Indosiar, SCTV, iNews, CNN Indonesia, CNBC Indonesia tanpa buffering. Update 2025.';
$og_image    = 'https://www.parokitulungagung.org/img/ogpreview/tvdigital.jpg';
$og_img_w    = '1200';
$og_img_h    = '630';
$og_img_alt  = 'TV Digital Indonesia Live Streaming — Paroki Tulungagung';

/* Daftar channel untuk meta keywords & structured data */
$channels_list = 'RCTI, MNCTV, GTV, Trans7, Trans TV, Indosiar, SCTV, iNews, CNN Indonesia, CNBC Indonesia';

/* JSON-LD: WebPage */
$jsonld_webpage = json_encode([
  '@context'    => 'https://schema.org',
  '@type'       => 'WebPage',
  '@id'         => $canonical . '#webpage',
  'url'         => $canonical,
  'name'        => $title,
  'description' => $desc,
  'inLanguage'  => 'id',
  'isPartOf'    => [
    '@type' => 'WebSite',
    '@id'   => $site_url . '#website',
    'url'   => $site_url,
    'name'  => $site_name,
  ],
  'breadcrumb'  => [
    '@type'           => 'BreadcrumbList',
    'itemListElement' => [
      ['@type' => 'ListItem', 'position' => 1,
       'name' => 'Beranda', 'item' => $site_url],
      ['@type' => 'ListItem', 'position' => 2,
       'name' => 'TV Digital', 'item' => $canonical],
    ],
  ],
  'publisher'   => [
    '@type' => 'Organization',
    'name'  => $site_name,
    'url'   => $site_url,
    'logo'  => [
      '@type'  => 'ImageObject',
      'url'    => $site_url . '/img/logo.png',
    ],
  ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

/* JSON-LD: VideoObject (live stream aggregate) */
$jsonld_video = json_encode([
  '@context'         => 'https://schema.org',
  '@type'            => 'VideoObject',
  'name'             => 'Siaran Langsung TV Digital Indonesia',
  'description'      => 'Live streaming gratis TV digital Indonesia: ' . $channels_list . '. Nonton tanpa buffering langsung di browser.',
  'thumbnailUrl'     => $og_image,
  'uploadDate'       => '2025-01-01',
  'publication'      => [
    '@type'       => 'BroadcastEvent',
    'isLiveBroadcast' => true,
    'startDate'   => '2025-01-01T00:00:00+07:00',
  ],
  'contentUrl'       => $canonical,
  'embedUrl'         => $canonical,
  'inLanguage'       => 'id',
  'publisher'        => [
    '@type' => 'Organization',
    'name'  => $site_name,
    'url'   => $site_url,
  ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

/* JSON-LD: FAQPage (meningkatkan peluang rich snippet) */
$jsonld_faq = json_encode([
  '@context'   => 'https://schema.org',
  '@type'      => 'FAQPage',
  'mainEntity' => [
    [
      '@type'          => 'Question',
      'name'           => 'Apa saja channel TV yang tersedia di halaman ini?',
      'acceptedAnswer' => [
        '@type' => 'Answer',
        'text'  => 'Tersedia ' . $channels_list . '. Semua dapat ditonton gratis secara live streaming langsung di browser tanpa perlu download aplikasi.',
      ],
    ],
    [
      '@type'          => 'Question',
      'name'           => 'Apakah TV digital di sini gratis?',
      'acceptedAnswer' => [
        '@type' => 'Answer',
        'text'  => 'Ya, semua siaran yang tersedia di halaman ini sepenuhnya gratis dan dapat ditonton langsung tanpa biaya berlangganan.',
      ],
    ],
    [
      '@type'          => 'Question',
      'name'           => 'Bagaimana cara menonton RCTI live streaming?',
      'acceptedAnswer' => [
        '@type' => 'Answer',
        'text'  => 'Klik channel RCTI pada daftar di sisi kiri, lalu siaran langsung RCTI akan langsung muncul di layar player. Tidak perlu login atau download aplikasi.',
      ],
    ],
    [
      '@type'          => 'Question',
      'name'           => 'Kenapa Indosiar atau SCTV tiba-tiba berhenti?',
      'acceptedAnswer' => [
        '@type' => 'Answer',
        'text'  => 'Indosiar dan SCTV menggunakan stream via Vidio.com yang membatasi durasi embed sekitar 30 menit. Tekan tombol Refresh (↺) di pojok kanan bawah untuk melanjutkan streaming.',
      ],
    ],
    [
      '@type'          => 'Question',
      'name'           => 'Bisakah menonton TV digital ini di HP?',
      'acceptedAnswer' => [
        '@type' => 'Answer',
        'text'  => 'Ya, halaman ini didesain responsif dan dapat digunakan di smartphone maupun tablet. Buka di browser mobile seperti Chrome atau Safari tanpa perlu aplikasi tambahan.',
      ],
    ],
  ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

?><!DOCTYPE html>
<html lang="id" prefix="og: https://ogp.me/ns#">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<?php include __DIR__ . '/../components/seo_head.php'; ?>
<!-- ═══ PRIMARY SEO ═══ -->
<title><?= htmlspecialchars($title) ?></title>
<meta name="description" content="<?= htmlspecialchars($desc) ?>">
<meta name="keywords"    content="tv digital indonesia, live streaming tv, nonton tv online gratis, rcti live, mnctv live, gtv live, trans7 live, transtv live, indosiar live, sctv live, cnn indonesia live, cnbc indonesia live, inews live, streaming tv indonesia 2025">
<meta name="author"      content="<?= $site_name ?>">
<meta name="robots"      content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
<link rel="canonical"    href="<?= $canonical ?>">

<!-- ═══ OPEN GRAPH (Facebook, WhatsApp, dsb.) ═══ -->
<meta property="og:type"        content="website">
<meta property="og:url"         content="<?= $canonical ?>">
<meta property="og:title"       content="<?= htmlspecialchars($title) ?>">
<meta property="og:description" content="<?= htmlspecialchars($desc) ?>">
<meta property="og:site_name"   content="<?= htmlspecialchars($site_name) ?>">
<meta property="og:locale"      content="id_ID">
<meta property="og:image"       content="<?= $og_image ?>">
<meta property="og:image:secure_url" content="<?= $og_image ?>">
<meta property="og:image:width"  content="<?= $og_img_w ?>">
<meta property="og:image:height" content="<?= $og_img_h ?>">
<meta property="og:image:alt"    content="<?= htmlspecialchars($og_img_alt) ?>">
<meta property="og:image:type"   content="image/jpeg">

<!-- ═══ TWITTER CARD ═══ -->
<meta name="twitter:card"        content="summary_large_image">
<meta name="twitter:title"       content="<?= htmlspecialchars($title) ?>">
<meta name="twitter:description" content="<?= htmlspecialchars($desc) ?>">
<meta name="twitter:image"       content="<?= $og_image ?>">
<meta name="twitter:image:alt"   content="<?= htmlspecialchars($og_img_alt) ?>">

<!-- ═══ STRUCTURED DATA (JSON-LD) ═══ -->
<script type="application/ld+json"><?= $jsonld_webpage ?></script>
<script type="application/ld+json"><?= $jsonld_video ?></script>
<script type="application/ld+json"><?= $jsonld_faq ?></script>

<!-- Lock scroll ASAP -->
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
html,body{
  overflow:hidden!important;height:100%!important;max-height:100%!important;
  width:100%!important;position:fixed!important;top:0!important;left:0!important;
  scrollbar-width:none!important;
}
html::-webkit-scrollbar,body::-webkit-scrollbar{display:none!important;width:0!important;}
body>*:not(.tvshell){display:none!important;height:0!important;overflow:hidden!important;}
</style>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;0,700;1,300;1,400&family=Cinzel:wght@400;500;600&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">

<style>
/* ══════════════════════════════════════════════════════
   DESIGN TOKENS — selaras dengan main site
══════════════════════════════════════════════════════ */
:root{
  --bg:         #080401;
  --panel:      #100804;
  --panel2:     #160c06;
  --surface:    #1e1109;
  --surface2:   #261508;
  --line:       rgba(201,162,58,.14);
  --line2:      rgba(201,162,58,.22);
  --line3:      rgba(201,162,58,.38);
  --gold:       #c9a23a;
  --gold-b:     #f0d98a;
  --gold-mid:   #dab85a;
  --gold-dim:   rgba(201,162,58,.18);
  --gold-glow:  rgba(201,162,58,.08);
  --gold-ghost: rgba(201,162,58,.05);
  --live:       #d44a3a;
  --live-dim:   rgba(212,74,58,.14);
  --online:     #2dbd7e;
  --warn:       #d4861a;
  --txt1:       #ede8df;
  --txt2:       #a09070;
  --txt3:       #5a4530;
  --txt4:       #3a2a18;
  --serif:      'Cormorant Garamond', Georgia, serif;
  --cinzel:     'Cinzel', Georgia, serif;
  --sans:       'DM Sans', 'Archivo Narrow', Arial, sans-serif;
  --sb:   240px;
  --tb:   52px;
  --stb:  46px;
}

html,body{
  overflow:hidden!important;height:100%!important;max-height:100%!important;
  width:100%!important;position:fixed!important;top:0!important;left:0!important;
}
body>*:not(.tvshell){
  display:none!important;height:0!important;
  overflow:hidden!important;visibility:hidden!important;pointer-events:none!important;
}

/* ══════════════════════════════════════════════════════
   SHELL
══════════════════════════════════════════════════════ */
.tvshell{
  position:fixed!important;inset:0!important;
  width:100vw!important;height:100vh!important;height:100dvh!important;
  overflow:hidden!important;
  display:grid!important;
  grid-template-columns:var(--sb) 1fr;
  grid-template-rows:var(--tb) 1fr var(--stb);
  background:var(--bg);
  color:var(--txt1);
  font-family:var(--sans);
  z-index:2147483647!important;
}

.noise::before{
  content:'';position:absolute;inset:0;pointer-events:none;z-index:0;
  background-image:url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.85' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='1'/%3E%3C/svg%3E");
  background-size:200px 200px;opacity:.025;
}

/* ══════════════════════════════════════════════════════
   TOPBAR
══════════════════════════════════════════════════════ */
.topbar{
  grid-column:1/-1;grid-row:1;
  background:linear-gradient(90deg,#100804 0%,#1c0e06 50%,#100804 100%);
  border-bottom:1px solid var(--line);
  display:flex;align-items:center;justify-content:space-between;
  padding:0 16px 0 14px;
  position:relative;z-index:10;overflow:hidden;
}
.topbar::after{
  content:'';position:absolute;bottom:0;left:0;right:0;height:1px;
  background:linear-gradient(90deg,
    transparent 0%,
    rgba(201,162,58,.2) 10%,
    rgba(201,162,58,.7) 35%,
    #f0d060 50%,
    rgba(201,162,58,.7) 65%,
    rgba(201,162,58,.2) 90%,
    transparent 100%
  );opacity:.6;
}

.logo{display:flex;align-items:center;gap:11px;position:relative;z-index:1;}
.logo-cross{
  width:26px;height:26px;
  display:flex;align-items:center;justify-content:center;
  color:var(--gold-mid);
  filter:drop-shadow(0 0 6px rgba(201,162,58,.4));
  flex-shrink:0;
}
.logo-cross svg{display:block;}
.logo-text{display:flex;flex-direction:column;gap:1px;}
.logo-name{
  font-family:var(--serif);font-size:14px;font-weight:600;font-style:italic;
  letter-spacing:.04em;color:var(--txt1);line-height:1;
  background:linear-gradient(135deg,#f0e8d0 0%,var(--gold-b) 45%,var(--gold-mid) 100%);
  -webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;
}
.logo-sub{
  font-family:var(--cinzel);font-size:7px;letter-spacing:.28em;
  text-transform:uppercase;color:rgba(201,162,58,.38);font-weight:400;
}

.topright{display:flex;align-items:center;gap:10px;position:relative;z-index:1;}

.live-badge{
  display:flex;align-items:center;gap:5px;
  background:var(--live-dim);
  border:1px solid rgba(212,74,58,.25);
  color:#e88070;
  font-family:var(--cinzel);font-size:8px;font-weight:500;
  letter-spacing:.22em;padding:4px 10px;border-radius:3px;
  text-transform:uppercase;
}
.live-dot{
  width:5px;height:5px;border-radius:50%;
  background:#d44a3a;box-shadow:0 0 6px rgba(212,74,58,.6);
  animation:livepulse 1.4s ease-in-out infinite;
}
@keyframes livepulse{0%,100%{opacity:1;transform:scale(1);}50%{opacity:.25;transform:scale(.7);}}

.clock-wrap{
  display:flex;align-items:center;gap:7px;
  background:rgba(201,162,58,.05);
  border:1px solid var(--line);
  padding:4px 11px;border-radius:3px;
}
.clock-tz{font-family:var(--cinzel);font-size:7.5px;color:var(--txt3);letter-spacing:1px;}
.clock-val{
  font-family:var(--cinzel);font-size:11px;font-weight:500;
  color:var(--gold-mid);letter-spacing:.04em;font-variant-numeric:tabular-nums;
}

/* Legend */
.legend{display:flex;align-items:center;gap:9px;}
.leg{display:flex;align-items:center;gap:4px;font-family:var(--cinzel);font-size:7px;letter-spacing:.1em;color:var(--txt3);}
.leg-dot{width:5px;height:5px;border-radius:50%;flex-shrink:0;}
.vdiv{width:1px;height:16px;background:var(--line2);}

/* ══════════════════════════════════════════════════════
   SIDEBAR
══════════════════════════════════════════════════════ */
.sidebar{
  grid-column:1;grid-row:2;
  background:linear-gradient(180deg,var(--panel) 0%,var(--bg) 100%);
  border-right:1px solid var(--line);
  display:flex;flex-direction:column;overflow:hidden;
}

.search-wrap{padding:10px 10px 0;flex-shrink:0;}
.search-box{
  width:100%;background:var(--surface);
  border:1px solid var(--line2);border-radius:4px;
  display:flex;align-items:center;gap:6px;padding:0 9px;
  transition:border-color .2s;
}
.search-box:focus-within{border-color:var(--line3);}
.search-box i{font-size:12px;color:var(--txt3);}
.search-box input{
  flex:1;background:transparent;border:none;outline:none;
  color:var(--txt1);font-family:var(--sans);font-size:11px;
  padding:7px 0;
}
.search-box input::placeholder{color:var(--txt4);font-style:italic;}

.cat-wrap{padding:8px 10px;border-bottom:1px solid var(--line);flex-shrink:0;}
.cat-label{
  font-family:var(--cinzel);font-size:7px;letter-spacing:.28em;
  text-transform:uppercase;color:var(--txt3);margin-bottom:6px;
}
.cat-row{display:flex;flex-wrap:wrap;gap:4px;}
.cb{
  font-family:var(--cinzel);font-size:7.5px;font-weight:400;
  letter-spacing:.18em;text-transform:uppercase;
  padding:3px 9px;border-radius:3px;
  border:1px solid var(--line2);
  background:transparent;color:var(--txt3);
  cursor:pointer;transition:all .18s;white-space:nowrap;
}
.cb:hover{background:var(--gold-ghost);color:var(--txt2);border-color:var(--line3);}
.cb.on{background:var(--gold-dim);border-color:var(--line3);color:var(--gold-mid);}

.chcount-wrap{
  padding:5px 12px 3px;flex-shrink:0;
  border-bottom:1px solid rgba(201,162,58,.07);
}
.chcount-label{font-family:var(--cinzel);font-size:7.5px;color:var(--txt3);letter-spacing:.2em;text-transform:uppercase;}

.chlist{
  flex:1;overflow-y:auto;padding:3px 0 8px;min-height:0;
  scrollbar-width:thin;scrollbar-color:rgba(201,162,58,.1) transparent;
}
.chlist::-webkit-scrollbar{width:2px;}
.chlist::-webkit-scrollbar-track{background:transparent;}
.chlist::-webkit-scrollbar-thumb{background:rgba(201,162,58,.12);border-radius:2px;}

.chitem{
  display:flex;align-items:center;gap:8px;
  padding:7px 12px;
  cursor:pointer;border-left:2px solid transparent;
  transition:all .15s;position:relative;
}
.chitem:hover{background:rgba(201,162,58,.04);}
.chitem.on{
  background:linear-gradient(90deg,rgba(201,162,58,.07),transparent);
  border-left-color:var(--gold);
}
.chitem.off-ch{opacity:.28;pointer-events:none;}

.ch-num{
  font-family:var(--cinzel);font-size:8px;font-weight:400;
  color:var(--txt4);width:14px;text-align:right;flex-shrink:0;
  font-variant-numeric:tabular-nums;
}
.chitem.on .ch-num{color:rgba(201,162,58,.5);}

.ch-badge{
  width:32px;height:18px;border-radius:2px;
  background:var(--surface);border:1px solid rgba(201,162,58,.16);
  display:flex;align-items:center;justify-content:center;
  font-family:var(--cinzel);font-size:6px;font-weight:500;
  color:var(--txt3);flex-shrink:0;letter-spacing:.2px;
  position:relative;overflow:hidden;transition:all .15s;
}
.ch-badge::after{
  content:'';position:absolute;inset:0;
  background:linear-gradient(135deg,rgba(255,255,255,.04) 0%,transparent 60%);
}
.chitem.on .ch-badge{background:var(--gold-dim);border-color:var(--line3);color:var(--gold-mid);}

.ch-info{flex:1;min-width:0;}
.ch-name{
  font-size:11px;font-weight:400;color:var(--txt2);
  white-space:nowrap;overflow:hidden;text-overflow:ellipsis;
  transition:color .15s;line-height:1.3;font-family:var(--sans);
}
.chitem.on .ch-name{color:var(--txt1);}
.ch-cat{
  font-family:var(--cinzel);font-size:7.5px;color:var(--txt3);
  margin-top:1px;letter-spacing:.12em;text-transform:uppercase;
}
.chitem.on .ch-cat{color:rgba(201,162,58,.5);}

.sig{width:5px;height:5px;border-radius:50%;flex-shrink:0;}
.sig.ok{background:var(--online);box-shadow:0 0 5px rgba(45,189,126,.35);}
.sig.lim{background:var(--warn);}
.sig.off-s{background:#4a1a0a;}

.chitem.on::after{
  content:'';position:absolute;right:9px;top:50%;transform:translateY(-50%);
  width:2px;height:12px;
  background:linear-gradient(180deg,var(--gold-b),var(--gold));
  border-radius:2px;
  animation:eq .7s ease-in-out infinite alternate;
}
@keyframes eq{from{height:4px;opacity:.4}to{height:14px;opacity:1}}

.ch-empty{
  padding:28px 16px;text-align:center;
  font-family:var(--serif);font-style:italic;font-size:13px;
  color:var(--txt3);
}

/* ══════════════════════════════════════════════════════
   PLAYER
══════════════════════════════════════════════════════ */
.player{
  grid-column:2;grid-row:2;
  background:#030200;position:relative;overflow:hidden;
}
.player iframe{position:absolute;inset:0;width:100%;height:100%;border:none;display:block;}

.watermark{
  position:absolute;bottom:600px;right:12px;z-index:20;
  display:flex;align-items:center;gap:5px;
  background:rgba(8,4,1,.55);
  border:1px solid rgba(201,162,58,.2);
  backdrop-filter:blur(6px);
  padding:4px 10px 4px 8px;
  border-radius:3px;
  text-decoration:none;
  opacity:.55;
  transition:opacity .25s,border-color .25s,background .25s;
  pointer-events:all;
}
.watermark:hover{opacity:.9;background:rgba(8,4,1,.75);border-color:rgba(201,162,58,.45);}
.watermark-cross{color:var(--gold-mid);filter:drop-shadow(0 0 4px rgba(201,162,58,.3));flex-shrink:0;}
.watermark-txt{
  font-family:var(--cinzel);font-size:8px;letter-spacing:.16em;
  color:rgba(240,217,138,.75);text-transform:lowercase;
  font-weight:400;white-space:nowrap;
}

.nosig{
  position:absolute;inset:0;
  display:flex;flex-direction:column;align-items:center;justify-content:center;
  background:radial-gradient(ellipse at 50% 55%,#0e0804 0%,#060402 70%);
}
.nosig-hatch{
  position:absolute;inset:0;
  background-image:repeating-linear-gradient(
    45deg,
    rgba(201,162,58,.018) 0px,rgba(201,162,58,.018) 1px,
    transparent 1px,transparent 28px
  ),repeating-linear-gradient(
    -45deg,
    rgba(201,162,58,.018) 0px,rgba(201,162,58,.018) 1px,
    transparent 1px,transparent 28px
  );
}
.nosig-inner{
  position:relative;z-index:1;text-align:center;
  padding:0 40px;
  display:flex;flex-direction:column;align-items:center;gap:16px;
}
.nosig-ring{
  width:80px;height:80px;border-radius:50%;
  background:rgba(201,162,58,.04);
  border:1px solid rgba(201,162,58,.1);
  display:flex;align-items:center;justify-content:center;
  position:relative;
}
.nosig-ring::before{
  content:'';position:absolute;inset:-10px;border-radius:50%;
  border:1px solid rgba(201,162,58,.05);
  animation:ringpulse 4s ease-in-out infinite;
}
.nosig-ring::after{
  content:'';position:absolute;inset:-20px;border-radius:50%;
  border:1px solid rgba(201,162,58,.03);
  animation:ringpulse 4s ease-in-out infinite .9s;
}
@keyframes ringpulse{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.2;transform:scale(1.06)}}
.nosig-cross{color:rgba(201,162,58,.22);filter:drop-shadow(0 0 8px rgba(201,162,58,.1));}

.nosig-t{
  font-family:var(--serif);font-style:italic;font-size:15px;font-weight:300;
  color:rgba(237,227,208,.2);letter-spacing:.04em;line-height:1.4;
}
.nosig-s{
  font-family:var(--cinzel);font-size:8px;letter-spacing:.2em;
  color:var(--txt4);text-transform:uppercase;
}
.nosig-pills{
  display:flex;align-items:center;gap:6px;flex-wrap:wrap;justify-content:center;
}
.nosig-pill{
  display:flex;align-items:center;gap:5px;
  font-family:var(--cinzel);font-size:7.5px;letter-spacing:.18em;
  text-transform:uppercase;color:var(--txt3);
  background:rgba(201,162,58,.04);
  border:1px solid rgba(201,162,58,.1);
  padding:3px 9px;border-radius:2px;
}

.offsig{
  position:absolute;inset:0;
  display:flex;flex-direction:column;align-items:center;justify-content:center;gap:14px;
  background:radial-gradient(ellipse at 50% 50%,#120504 0%,#060201 70%);
}
.offsig-icon{font-size:36px;color:rgba(212,74,58,.2);}
.offsig-t{
  font-family:var(--serif);font-style:italic;font-size:15px;font-weight:300;
  color:rgba(212,74,58,.32);letter-spacing:.04em;
}
.offsig-s{
  font-family:var(--cinzel);font-size:7.5px;color:var(--txt4);
  letter-spacing:.16em;text-transform:uppercase;
  text-align:center;line-height:1.9;max-width:260px;
}

.player-flash{
  position:absolute;inset:0;background:rgba(201,162,58,.04);
  opacity:0;pointer-events:none;z-index:5;
}
.player-flash.go{animation:flash .25s ease-out forwards;}
@keyframes flash{0%{opacity:.08}100%{opacity:0}}

/* ══════════════════════════════════════════════════════
   STATUSBAR
══════════════════════════════════════════════════════ */
.statusbar{
  grid-column:1/-1;grid-row:3;
  background:linear-gradient(90deg,#100804 0%,#1a0e06 50%,#100804 100%);
  border-top:1px solid var(--line);
  display:flex;align-items:center;justify-content:space-between;
  padding:0 14px;position:relative;overflow:hidden;
}
.statusbar::before{
  content:'';position:absolute;top:0;left:0;right:0;height:1px;
  background:linear-gradient(90deg,transparent,rgba(201,162,58,.35),transparent);
}

.now-info{display:flex;align-items:center;gap:10px;flex:1;min-width:0;}

.sigbars{display:flex;align-items:flex-end;gap:2px;height:14px;flex-shrink:0;}
.sb{width:3px;border-radius:1px;background:rgba(201,162,58,.14);transition:background .3s;}
.sb:nth-child(1){height:4px;}
.sb:nth-child(2){height:7px;}
.sb:nth-child(3){height:10px;}
.sb:nth-child(4){height:14px;}
.sigbars.ok .sb{background:var(--online);}
.sigbars.lim .sb:nth-child(1),.sigbars.lim .sb:nth-child(2){background:var(--warn);}

.now-badge{
  font-family:var(--cinzel);font-size:8px;font-weight:500;
  letter-spacing:.22em;text-transform:uppercase;color:var(--gold-mid);
  background:var(--gold-dim);border:1px solid var(--line2);
  padding:3px 9px;border-radius:2px;white-space:nowrap;flex-shrink:0;
}
.now-texts{min-width:0;}
.now-name{
  font-family:var(--serif);font-size:12px;font-weight:600;
  color:var(--txt1);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;
  letter-spacing:.02em;
}
.now-desc{
  font-family:var(--cinzel);font-size:7.5px;color:var(--txt3);margin-top:1px;
  white-space:nowrap;overflow:hidden;text-overflow:ellipsis;letter-spacing:.12em;
  text-transform:uppercase;
}

.stb-ornament{
  display:flex;align-items:center;gap:7px;
  color:rgba(201,162,58,.2);flex-shrink:0;
  font-family:var(--cinzel);font-size:7px;letter-spacing:.18em;
  text-transform:uppercase;
}
.stb-ornament span{display:block;width:24px;height:1px;background:currentColor;}

.ctrls{display:flex;align-items:center;gap:4px;flex-shrink:0;}
.ch-count{
  font-family:var(--cinzel);font-size:8px;font-weight:400;
  letter-spacing:.18em;color:var(--txt3);white-space:nowrap;
}
.btn{
  width:26px;height:26px;border-radius:3px;
  border:1px solid rgba(201,162,58,.16);
  background:transparent;cursor:pointer;
  display:flex;align-items:center;justify-content:center;
  color:var(--txt3);font-size:11px;transition:all .15s;
}
.btn:hover{background:var(--gold-dim);border-color:var(--line3);color:var(--gold-mid);}
.btn:active{transform:scale(.88);}

*::-webkit-scrollbar{width:2px;height:2px;}
*::-webkit-scrollbar-track{background:transparent;}
*::-webkit-scrollbar-thumb{background:rgba(201,162,58,.1);border-radius:2px;}

/* ══════════════════════════════════════════════════════
   MOBILE  ≤ 767px
══════════════════════════════════════════════════════ */
@media (max-width:767px){
  :root{--tb:44px;--stb:42px;}
  html,body{height:100dvh!important;max-height:100dvh!important;}
  .tvshell{
    display:flex!important;flex-direction:column;
    height:100dvh!important;
    grid-template-columns:unset;grid-template-rows:unset;
  }
  .topbar{order:1;flex-shrink:0;padding:0 12px;}
  .legend,.vdiv{display:none!important;}
  .logo-sub{display:none;}
  .clock-tz{display:none;}
  .player{
    order:2;grid-column:unset;grid-row:unset;
    width:100%;height:auto;aspect-ratio:16/9;
    flex-shrink:0;position:relative;
  }
  .player iframe{position:absolute;inset:0;width:100%;height:100%;}
  .nosig,.offsig{position:absolute;inset:0;}
  .nosig-ring{width:52px;height:52px;}
  .nosig-cross svg{width:16px;height:20px;}
  .nosig-t{font-size:12px;}
  .nosig-s{font-size:7px;}
  .nosig-pill{font-size:7px;padding:2px 7px;}
  .watermark{bottom:190px;right:8px;padding:3px 8px 3px 6px;}
  .watermark-txt{font-size:7px;}
  .sidebar{
    order:3;grid-column:unset;grid-row:unset;
    flex:1;min-height:0;
    border-right:none;border-top:1px solid var(--line);
  }
  .cat-label{display:none;}
  .cat-row{flex-wrap:nowrap;overflow-x:auto;-webkit-overflow-scrolling:touch;scrollbar-width:none;}
  .cat-row::-webkit-scrollbar{display:none;}
  .chitem{padding:7px 10px;}
  .ch-name{font-size:10.5px;}
  .chcount-wrap{padding:3px 12px 2px;}
  .statusbar{order:4;grid-column:unset;grid-row:unset;flex-shrink:0;padding:0 12px;}
  .now-desc,.sigbars,.stb-ornament{display:none;}
  .now-name{font-size:11.5px;}
}

/* TABLET */
@media (min-width:768px) and (max-width:1023px){
  :root{--sb:200px;}
}
</style>
</head>
<body>

<!--
  ════════════════════════════════════════════════════════
  KONTEN SEMANTIK TERSEMBUNYI UNTUK CRAWLER
  Disembunyikan secara visual tapi tetap terbaca mesin pencari.
  Tidak di-display:none agar Google tetap mengindeksnya.
  ════════════════════════════════════════════════════════
-->
<div style="position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;" aria-hidden="true">
  <h1>TV Digital Indonesia — Siaran Langsung Gratis</h1>
  <p>Nonton live streaming TV digital Indonesia gratis: <?= $channels_list ?>. Tersedia 10 channel tanpa perlu download aplikasi atau login. Siaran langsung 24 jam langsung di browser.</p>
  <nav aria-label="Breadcrumb">
    <ol>
      <li><a href="<?= $site_url ?>">Beranda</a></li>
      <li><a href="<?= $canonical ?>">TV Digital</a></li>
    </ol>
  </nav>
  <ul>
    <li><strong>RCTI Live</strong> — Raja Citra Televisi Indonesia, streaming via sindikasi iNews</li>
    <li><strong>MNCTV Live</strong> — MNC Television, streaming via sindikasi iNews</li>
    <li><strong>GTV Live</strong> — Global Television, streaming via sindikasi iNews</li>
    <li><strong>Trans7 Live</strong> — Trans 7 Television, streaming via detik.com</li>
    <li><strong>Trans TV Live</strong> — Trans Television, streaming via detik.com</li>
    <li><strong>Indosiar Live</strong> — Indosiar Visual Mandiri, streaming via Vidio.com</li>
    <li><strong>SCTV Live</strong> — Surya Citra Televisi, streaming via Vidio.com</li>
    <li><strong>iNews Live</strong> — Indonesia News Channel, streaming via sindikasi iNews</li>
    <li><strong>CNN Indonesia Live</strong> — CNN Indonesia 24 jam, streaming via cnnindonesia.com</li>
    <li><strong>CNBC Indonesia Live</strong> — CNBC Indonesia bisnis &amp; ekonomi, streaming via cnbcindonesia.com</li>
  </ul>
  <section>
    <h2>Cara Menonton TV Digital Online</h2>
    <p>Pilih channel dari daftar di sebelah kiri. Klik nama channel yang ingin ditonton, lalu siaran langsung akan otomatis muncul di layar utama. Gunakan tombol panah atas/bawah untuk berpindah channel, atau tekan tombol Refresh (↺) jika siaran berhenti.</p>
  </section>
  <section>
    <h2>Tentang Halaman Ini</h2>
    <p>Halaman TV Digital disediakan oleh <?= $site_name ?> sebagai layanan gratis untuk umat dan masyarakat umum. Semua stream bersumber dari platform resmi masing-masing stasiun TV.</p>
  </section>
</div>

<div class="tvshell">

  <!-- ░░ TOPBAR ░░ -->
  <div class="topbar">
    <div class="logo">
      <div class="logo-cross">
        <svg width="11" height="16" viewBox="0 0 11 16" fill="none" aria-hidden="true">
          <rect x="4" y="0" width="3" height="16" rx="1.5" fill="currentColor"/>
          <rect x="0" y="4" width="11" height="3" rx="1.5" fill="currentColor"/>
        </svg>
      </div>
      <div class="logo-text">
        <div class="logo-name">TV Digital Paroki</div>
        <div class="logo-sub">Paroki SMDTBA · Tulungagung</div>
      </div>
    </div>

    <div class="topright">
      <div class="legend">
        <div class="leg">
          <div class="leg-dot" style="background:var(--online);box-shadow:0 0 4px rgba(45,189,126,.4)"></div>Online
        </div>
        <div class="leg">
          <div class="leg-dot" style="background:var(--warn)"></div>Terbatas
        </div>
        <div class="leg">
          <div class="leg-dot" style="background:#4a1a0a"></div>Offline
        </div>
      </div>
      <div class="vdiv"></div>
      <div class="live-badge"><div class="live-dot"></div>LIVE</div>
      <div class="vdiv"></div>
      <div class="clock-wrap">
        <span class="clock-tz">WIB</span>
        <span class="clock-val" id="clk">—</span>
      </div>
    </div>
  </div>

  <!-- ░░ SIDEBAR ░░ -->
  <div class="sidebar">
    <div class="search-wrap">
      <div class="search-box">
        <i class="ti ti-search"></i>
        <input type="text" id="srch" placeholder="Cari channel…" autocomplete="off" spellcheck="false"
               aria-label="Cari channel TV">
      </div>
    </div>

    <div class="cat-wrap">
      <div class="cat-label">Kategori</div>
      <div class="cat-row" id="cats" role="group" aria-label="Filter kategori channel"></div>
    </div>

    <div class="chcount-wrap">
      <span class="chcount-label" id="chcnt">— channel</span>
    </div>

    <div class="chlist" id="chl" role="listbox" aria-label="Daftar channel TV"></div>
  </div>

  <!-- ░░ PLAYER ░░ -->
  <div class="player" id="player" role="main" aria-label="Player TV">
    <div class="nosig" id="nosig-screen">
      <div class="nosig-hatch"></div>
      <div class="nosig-inner">
        <div class="nosig-ring">
          <span class="nosig-cross" aria-hidden="true">
            <svg width="22" height="32" viewBox="0 0 11 16" fill="none">
              <rect x="4" y="0" width="3" height="16" rx="1.5" fill="currentColor"/>
              <rect x="0" y="4" width="11" height="3" rx="1.5" fill="currentColor"/>
            </svg>
          </span>
        </div>
        <div class="nosig-t">Pilih channel untuk mulai menonton</div>
        <div class="nosig-s">Siaran langsung · Paroki Tulungagung</div>
        <div class="nosig-pills" id="stat-pills"></div>
      </div>
    </div>

    <a href="/" class="watermark" title="Kembali ke Paroki Tulungagung" aria-label="Paroki Tulungagung">
      <span class="watermark-cross" aria-hidden="true">
        <svg width="7" height="10" viewBox="0 0 11 16" fill="none">
          <rect x="4" y="0" width="3" height="16" rx="1.5" fill="currentColor"/>
          <rect x="0" y="4" width="11" height="3" rx="1.5" fill="currentColor"/>
        </svg>
      </span>
      <span class="watermark-txt">parokitulungagung.org</span>
    </a>

    <div class="player-flash" id="flash" aria-hidden="true"></div>
  </div>

  <!-- ░░ STATUSBAR ░░ -->
  <div class="statusbar" role="status" aria-live="polite" aria-atomic="true">
    <div class="now-info">
      <div class="sigbars" id="sigbars" aria-hidden="true">
        <div class="sb"></div><div class="sb"></div>
        <div class="sb"></div><div class="sb"></div>
      </div>
      <div class="now-badge" id="nbadge">—</div>
      <div class="now-texts">
        <div class="now-name" id="nname">Belum ada channel dipilih</div>
        <div class="now-desc" id="ndesc">Pilih channel dari daftar di sebelah kiri</div>
      </div>
    </div>

    <div class="stb-ornament" aria-hidden="true">
      <span></span>
      <svg width="8" height="8" viewBox="0 0 8 8" fill="currentColor"><path d="M4 0L4.9 3.1L8 4L4.9 4.9L4 8L3.1 4.9L0 4L3.1 3.1Z"/></svg>
      <span></span>
    </div>

    <div class="ctrls">
      <span class="ch-count" id="chcnt2">—</span>
      <div class="vdiv" aria-hidden="true"></div>
      <button class="btn" onclick="prevCh()" title="Channel Sebelumnya" aria-label="Channel Sebelumnya">
        <i class="ti ti-chevron-up" aria-hidden="true"></i>
      </button>
      <button class="btn" onclick="nextCh()" title="Channel Berikutnya" aria-label="Channel Berikutnya">
        <i class="ti ti-chevron-down" aria-hidden="true"></i>
      </button>
      <div class="vdiv" aria-hidden="true"></div>
      <button class="btn" onclick="doRefresh()" title="Refresh Player" aria-label="Refresh Player">
        <i class="ti ti-refresh" aria-hidden="true"></i>
      </button>
    </div>
  </div>

</div><!-- /.tvshell -->

<script>
/* ════════════════════════════════════════════════════════
   DATA CHANNEL — Update Mei 2025
   Sumber stream yang sudah diverifikasi embeddable.

   STATUS:
     ok  = streaming normal, tidak terbatas
     lim = streaming terbatas waktu (Vidio.com auto-stop ~30 mnt),
           klik Refresh (R) atau tekan tombol ↺ untuk lanjutkan
     hls = streaming via tvstream.php (HLS.js player)

   CATATAN VIDIO (SCTV & Indosiar):
     Vidio.com membatasi durasi embed gratis ±30 menit.
     Setelah berhenti → tekan tombol Refresh di pojok kanan bawah.

   CATATAN METRO TV & KOMPAS TV (Dailymotion):
     Dailymotion live ID bisa berubah sewaktu-waktu.
     Jika tidak jalan, cek ulang ID di:
     dailymotion.com/search/metro+tv+live
     dailymotion.com/search/kompas+tv+live

   CATATAN TVRI:
     Streaming via HLS.js (tvstream.php). Pastikan file
     tvstream.php sudah diupload ke folder yang SAMA.
     Jika TVRI tidak mau muat, buka langsung: klik.tvri.go.id
════════════════════════════════════════════════════════ */
const CH=[

  /* ── HIBURAN ──────────────────────────────────────── */
  {n:1,  name:'RCTI',       s:'RCTI', cat:'Hiburan', status:'ok',
   desc:'Raja Citra Televisi Indonesia · via sindikasi iNews',
   src:'https://sindikasi.inews.id/embed/video/YWdlbnQ9ZGVza3RvcCZ1cmw9aHR0cHMlM0ElMkYlMkZlbWJlZC5yY3RpcGx1cy5jb20lMkZsaXZlJTJGcmN0aSUyRmluZXdzaWQmaGVpZ2h0PTEwMCUyNSZ3aWR0aD0xMDAlMjU='},

  {n:2,  name:'MNCTV',      s:'MNC',  cat:'Hiburan', status:'ok',
   desc:'MNC Television · via sindikasi iNews',
   src:'https://sindikasi.inews.id/embed/video/YWdlbnQ9ZGVza3RvcCZ1cmw9aHR0cHMlM0ElMkYlMkZlbWJlZC5yY3RpcGx1cy5jb20lMkZsaXZlJTJGbW5jdHYlMkZpbmV3c2lkJmhlaWdodD0xMDAlMjUmd2lkdGg9MTAwJTI1'},

  {n:3,  name:'GTV',        s:'GTV',  cat:'Hiburan', status:'ok',
   desc:'Global Television · via sindikasi iNews',
   src:'https://sindikasi.inews.id/embed/video/YWdlbnQ9ZGVza3RvcCZ1cmw9aHR0cHMlM0ElMkYlMkZlbWJlZC5yY3RpcGx1cy5jb20lMkZsaXZlJTJGZ3R2JTJGaW5ld3NpZCZoZWlnaHQ9MTAwJTI1JndpZHRoPTEwMCUyNQ=='},

  {n:4,  name:'Trans 7',    s:'TR7',  cat:'Hiburan', status:'ok',
   desc:'Trans 7 Television · via 20.detik.com',
   src:'https://20.detik.com/watch/livestreaming-trans7'},

  {n:5,  name:'Trans TV',   s:'TTV',  cat:'Hiburan', status:'ok',
   desc:'Trans Television · via 20.detik.com',
   src:'https://20.detik.com/watch/livestreaming-transtv'},

  /* Indosiar & SCTV via Vidio — STATUS TERBATAS (±30 mnt)
     Jika berhenti → tekan Refresh ↺ */
  {n:6,  name:'Indosiar',   s:'IDS',  cat:'Hiburan', status:'lim',
   desc:'Indosiar Visual Mandiri · via Vidio (terbatas durasi, refresh jika berhenti)',
   src:'https://www.vidio.com/live/205-indosiar-tv-stream/embed?autoplay=true&player_only=true&live_chat=false&mute=false'},

  {n:7,  name:'SCTV',       s:'SCTV', cat:'Hiburan', status:'lim',
   desc:'Surya Citra Televisi · via Vidio (terbatas durasi, refresh jika berhenti)',
   src:'https://www.vidio.com/live/204-sctv-tv-stream/embed?autoplay=true&player_only=true&live_chat=false&mute=false'},

  /* ── BERITA ───────────────────────────────────────── */
  {n:8,  name:'iNews',      s:'iNWS', cat:'Berita',  status:'ok',
   desc:'Indonesia News Channel · via sindikasi iNews',
   src:'https://sindikasi.inews.id/embed/video/YWdlbnQ9ZGVza3RvcCZ1cmw9aHR0cHMlM0ElMkYlMkZlbWJlZC5yY3RpcGx1cy5jb20lMkZsaXZlJTJGaW5ld3MlMkZpbmV3c2lkJmhlaWdodD0xMDAlMjUmd2lkdGg9MTAwJTI1'},

  {n:9,  name:'CNN Indonesia', s:'CNN', cat:'Berita', status:'ok',
   desc:'CNN Indonesia 24 jam · via cnnindonesia.com',
   src:'https://www.cnnindonesia.com/tv/embed?ref=transmedia'},

  {n:10, name:'CNBC Indonesia', s:'CNBC', cat:'Berita', status:'ok',
   desc:'CNBC Indonesia bisnis & ekonomi · via cnbcindonesia.com',
   src:'https://www.cnbcindonesia.com/embed/tv?ref=transmedia'},

];

/* ── KATEGORI ─────────────────────────────────────── */
const CATS=['Semua','Hiburan','Berita','Olahraga'];
let cat='Semua', cur=null, q='';

/* ── HELPERS ──────────────────────────────────────── */
function filtered(){
  let f=cat==='Semua'?CH:CH.filter(c=>c.cat===cat);
  if(q)f=f.filter(c=>c.name.toLowerCase().includes(q)||c.s.toLowerCase().includes(q));
  return f;
}
function updateSig(status){
  const b=document.getElementById('sigbars');
  if(b)b.className='sigbars '+(status||'');
}
function updateStatPills(){
  const ok =CH.filter(c=>c.status==='ok' ||c.status==='local').length;
  const lim=CH.filter(c=>c.status==='lim').length;
  document.getElementById('stat-pills').innerHTML=
    `<div class="nosig-pill"><div style="width:4px;height:4px;border-radius:50%;background:var(--online)"></div>${ok} Online</div>`+
    (lim?`<div class="nosig-pill"><div style="width:4px;height:4px;border-radius:50%;background:var(--warn)"></div>${lim} Terbatas</div>`:'')+
    `<div class="nosig-pill">
      <svg width="7" height="10" viewBox="0 0 11 16" fill="rgba(201,162,58,.4)">
        <rect x="4" y="0" width="3" height="16" rx="1.5"/>
        <rect x="0" y="4" width="11" height="3" rx="1.5"/>
      </svg>
      ${CH.length} Channel
    </div>`;
}
function renderCats(){
  document.getElementById('cats').innerHTML=
    CATS.map(c=>`<button class="cb${cat===c?' on':''}" onclick="setCat('${c}')">${c}</button>`).join('');
}
function setCat(c){cat=c;renderCats();renderList();}
function updateCount(list){
  const t=list.length+' channel';
  document.getElementById('chcnt').textContent=t;
  document.getElementById('chcnt2').textContent=list.length+' ch';
}

/* ── RENDER LIST ──────────────────────────────────── */
function renderList(){
  const f=filtered();
  updateCount(f);
  if(!f.length){
    document.getElementById('chl').innerHTML='<div class="ch-empty">Tidak ada channel ditemukan</div>';
    return;
  }
  document.getElementById('chl').innerHTML=f.map(ch=>`
    <div class="chitem${cur&&cur.n===ch.n?' on':''}${ch.status==='off'?' off-ch':''}"
         onclick="${ch.status!=='off'?'play('+ch.n+')':'showOffline('+ch.n+')'}"
         role="option" aria-selected="${cur&&cur.n===ch.n?'true':'false'}"
         aria-label="${ch.name} — ${ch.cat}${ch.status==='lim'?' (terbatas)':''}">
      <span class="ch-num">${ch.n}</span>
      <div class="ch-badge" aria-hidden="true">${ch.s}</div>
      <div class="ch-info">
        <div class="ch-name">${ch.name}</div>
        <div class="ch-cat">${ch.cat}${ch.status==='lim'?' · Terbatas':''}</div>
      </div>
      <div class="sig ${ch.status==='off'?'off-s':ch.status==='lim'?'lim':'ok'}" aria-hidden="true"></div>
    </div>`).join('');
}

/* ── WATERMARK ────────────────────────────────────── */
function buildWatermark(){
  return `<a href="/" class="watermark" title="Kembali ke Paroki Tulungagung" aria-label="Paroki Tulungagung">
    <span class="watermark-cross" aria-hidden="true">
      <svg width="7" height="10" viewBox="0 0 11 16" fill="none">
        <rect x="4" y="0" width="3" height="16" rx="1.5" fill="currentColor"/>
        <rect x="0" y="4" width="11" height="3" rx="1.5" fill="currentColor"/>
      </svg>
    </span>
    <span class="watermark-txt">parokitulungagung.org</span>
  </a>
  <div class="player-flash" id="flash" aria-hidden="true"></div>`;
}

/* ── PLAY ─────────────────────────────────────────── */
function play(num){
  const ch=CH.find(c=>c.n===num);if(!ch||!ch.src)return;
  cur=ch;renderList();
  document.getElementById('player').innerHTML=
    `<iframe src="${ch.src}"
       allowfullscreen allow="autoplay;encrypted-media;fullscreen"
       title="${ch.name} Live Streaming — TV Digital Indonesia" loading="lazy"
       referrerpolicy="no-referrer-when-downgrade"></iframe>`+
    buildWatermark();
  document.getElementById('nbadge').textContent='Ch '+ch.n;
  document.getElementById('nname').textContent=ch.name+(ch.status==='lim'?' — Terbatas':'');
  document.getElementById('ndesc').textContent=ch.desc;
  updateSig(ch.status==='lim'?'lim':'ok');
  // Update document title for UX (tidak memengaruhi SEO index)
  document.title=ch.name+' Live Streaming — TV Digital Paroki Tulungagung';
  setTimeout(()=>{const f=document.getElementById('flash');if(f)f.classList.add('go');},10);
}

function showOffline(num){
  const ch=CH.find(c=>c.n===num);if(!ch)return;
  cur=ch;renderList();
  document.getElementById('player').innerHTML=
    `<div class="offsig">
      <i class="ti ti-plug-x offsig-icon" aria-hidden="true"></i>
      <div class="offsig-t">${ch.name} — Tidak Tersedia</div>
      <div class="offsig-s">${ch.desc}</div>
    </div>`+buildWatermark();
  document.getElementById('nbadge').textContent='Ch '+ch.n;
  document.getElementById('nname').textContent=ch.name+' — Offline';
  document.getElementById('ndesc').textContent='Sumber stream sedang tidak aktif';
  updateSig('off-s');
}

/* ── NAVIGASI ─────────────────────────────────────── */
function prevCh(){
  const f=filtered().filter(c=>c.status!=='off');if(!f.length)return;
  if(!cur){play(f[0].n);return;}
  const i=f.findIndex(c=>c.n===cur.n);
  play(f[(i-1+f.length)%f.length].n);
  scrollToCur();
}
function nextCh(){
  const f=filtered().filter(c=>c.status!=='off');if(!f.length)return;
  if(!cur){play(f[0].n);return;}
  const i=f.findIndex(c=>c.n===cur.n);
  play(f[(i+1)%f.length].n);
  scrollToCur();
}
function doRefresh(){if(cur&&cur.src)play(cur.n);}
function scrollToCur(){
  const el=document.querySelector('.chitem.on');
  if(el)el.scrollIntoView({block:'nearest',behavior:'smooth'});
}

/* ── SEARCH ───────────────────────────────────────── */
document.getElementById('srch').addEventListener('input',function(){
  q=this.value.trim().toLowerCase();renderList();
});

/* ── JAM WIB ──────────────────────────────────────── */
function tick(){
  const t=new Date();
  const wib=new Date(t.toLocaleString('en-US',{timeZone:'Asia/Jakarta'}));
  document.getElementById('clk').textContent=
    String(wib.getHours()).padStart(2,'0')+':'+
    String(wib.getMinutes()).padStart(2,'0')+':'+
    String(wib.getSeconds()).padStart(2,'0');
}

/* ── KEYBOARD ─────────────────────────────────────── */
document.addEventListener('keydown',e=>{
  if(document.activeElement.tagName==='INPUT')return;
  if(e.key==='ArrowUp'){e.preventDefault();prevCh();}
  if(e.key==='ArrowDown'){e.preventDefault();nextCh();}
  if(e.key==='r'||e.key==='R'){doRefresh();}
});

/* ── SCROLL LOCK ──────────────────────────────────── */
(function lockAll(){
  const H=document.documentElement,B=document.body,P='important';
  const freeze=()=>{
    [H,B].forEach(el=>{
      el.style.setProperty('overflow','hidden',P);
      el.style.setProperty('height','100%',P);
      el.style.setProperty('max-height','100%',P);
      el.style.setProperty('position','fixed',P);
      el.style.setProperty('top','0',P);el.style.setProperty('left','0',P);
      el.style.setProperty('width','100%',P);
      el.style.setProperty('margin','0',P);el.style.setProperty('padding','0',P);
    });
    Array.from(B.children).forEach(el=>{
      if(!el.classList.contains('tvshell')){
        el.style.setProperty('display','none',P);
        el.style.setProperty('height','0',P);
        el.style.setProperty('overflow','hidden',P);
        el.style.setProperty('visibility','hidden',P);
      }
    });
  };
  freeze();
  document.addEventListener('DOMContentLoaded',freeze);
  window.addEventListener('load',freeze);
  new MutationObserver(freeze).observe(B,{childList:true,subtree:false});
  document.addEventListener('wheel',e=>{
    if(e.target.closest('.chlist,.cat-row,.search-box'))return;
    e.preventDefault();
  },{passive:false});
  document.addEventListener('touchmove',e=>{
    if(e.target.closest('.chlist,.cat-row'))return;
    e.preventDefault();
  },{passive:false});
})();

/* ── INIT ─────────────────────────────────────────── */
updateStatPills();renderCats();renderList();tick();setInterval(tick,1000);
</script>
</body>
</html>
<?php exit; ?>