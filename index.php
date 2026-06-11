<?php
// ================================================================
// index.php — Halaman Utama Paroki SMDTBA Tulungagung
// ================================================================

// ── Load secrets dari luar public_html ────────────────────
require_once dirname(__FILE__, 2) . '/private/secrets.php';
$_sb_url  = SECRET_SUPABASE_URL;
$_sb_anon = SECRET_SUPABASE_ANON_KEY;

// Konfigurasi dasar (sesuaikan jika perlu)
$site_title       = 'Gereja Katolik Tulungagung | Paroki SMDTBA';
$site_description = 'Website resmi Gereja Katolik Tulungagung — Paroki Santa Maria Dengan Tidak Bernoda Asal (SMDTBA). Cek jadwal misa, agenda, galeri foto, dan artikel rohani umat Tulungagung.';
$site_url         = 'https://www.parokitulungagung.org';
$og_image         = $site_url . '/img/og-preview.webp';

// Path chatbot (sesuaikan dengan struktur direktori server Anda)
// Pastikan file ini ada: __DIR__ . '/../chatbot/index.php'
$chatbot_path = __DIR__ . '/../chatbot/index.php';

?><!doctype html>
<html lang="id">
<head>
<meta charset="UTF-8">
<?php include __DIR__ . '/../components/seo_head.php'; ?>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars($site_title); ?></title>
<meta name="description" content="<?php echo htmlspecialchars($site_description); ?>">
<meta name="keywords" content="gereja katolik tulungagung, jadwal misa tulungagung, paroki smdtba tulungagung, santa maria tidak bernoda asal, misa minggu tulungagung">
<link rel="canonical" href="<?php echo htmlspecialchars($site_url); ?>">
<meta property="og:type" content="website">
<meta property="og:title" content="<?php echo htmlspecialchars($site_title); ?>">
<meta property="og:description" content="<?php echo htmlspecialchars($site_description); ?>">
<meta property="og:image" content="<?php echo htmlspecialchars($og_image); ?>">
<meta property="og:url" content="<?php echo htmlspecialchars($site_url); ?>">
<meta name="twitter:card" content="summary_large_image">

<!-- Preconnect -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="preconnect" href="https://rkzaathgygfjovrpdlqi.supabase.co" crossorigin>

<!-- Preload LCP images -->
<link rel="preload" as="image" href="/img/gereja/interiorwide.webp" fetchpriority="high">
<link rel="preload" as="image" href="/img/parokitulungagung.png" fetchpriority="high">

<style>
/* ================================================================
   GOOGLE FONTS IMPORT
   ================================================================ */
@import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400;1,600&family=Cinzel:wght@400;500;600&family=DM+Sans:ital,wght@0,300;0,400;1,300&family=Montserrat:wght@300;400;500;600;700;800&family=Archivo+Narrow:wght@400;500;600;700&display=swap');

/* ================================================================
   CSS VARIABLES & RESET
   ================================================================ */
:root {
  --font-serif: 'Cormorant Garamond', Georgia, 'Times New Roman', serif;
  --font-cinzel: 'Cinzel', Georgia, serif;
  --font-sans: 'Montserrat', 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
  --font-narrow: 'Archivo Narrow', 'Arial Narrow', Arial, sans-serif;
  --font-body: 'DM Sans', 'Archivo Narrow', Arial, sans-serif;

  /* Brand */
  --primary-brown: #3a2410;
  --primary-gold: #c9a23a;
  --gold-bright: #f0d98a;
  --gold-mid: #dab85a;
  --accent-cream: #f5f1e8;
  --accent-burgundy: #722f37;

  /* Neutral */
  --off-white: #fafaf8;
  --linen: #ede8df;
  --light-gray: #f0ece5;
  --medium-gray: #9e9e9e;
  --dark-gray: #424242;
  --text-dark: #1e1008;
  --text-mid: #564938;
  --text-light: #8a7060;

  /* Shadows */
  --shadow-sm: 0 2px 10px rgba(30, 16, 8, .06);
  --shadow-md: 0 4px 20px rgba(30, 16, 8, .10);
  --shadow-lg: 0 8px 32px rgba(30, 16, 8, .14);
  --shadow-xl: 0 16px 48px rgba(30, 16, 8, .18);

  /* Spacing */
  --sp-xs: .5rem;
  --sp-sm: 1rem;
  --sp-md: 1.5rem;
  --sp-lg: 2.5rem;
  --sp-xl: 4rem;

  /* Radius */
  --r-sm: 8px;
  --r-md: 14px;
  --r-lg: 20px;
  --r-xl: 28px;

  /* Transition */
  --ease-fast: .2s ease;
  --ease-base: .3s ease;
  --ease-slow: .55s ease;
  --ease-spring: .4s cubic-bezier(.22, 1, .36, 1);

  /* Navbar height */
  --navbar-h: 44px;

  /* Gold variables used by footer */
  --ft-gold-bright: #f0d98a;
  --ft-gold-mid: #dab85a;
  --ft-gold-deep: #c9a84c;
  --ft-gold-dim: rgba(218,175,90,0.5);
  --ft-gold-faint: rgba(218,175,90,0.1);
  --ft-gold-ghost: rgba(218,175,90,0.06);
  --ft-cream: #ede3d0;
  --ft-cream-dim: rgba(237,227,208,0.55);
  --ft-bg: #080610;
  --ft-bg-card: #0e0b18;
  --ft-line: rgba(218,175,90,0.18);
}

*, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
html { font-display: swap; }
body {
  font-family: var(--font-body);
  font-size: 15px;
  line-height: 1.6;
  color: var(--text-dark);
  background: var(--linen);
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
  padding-top: var(--navbar-h);
  overflow-x: hidden;
}
html, body { overflow-x: hidden; max-width: 100%; }
body > * { max-width: 100%; box-sizing: border-box; }

h1,h2,h3,h4,h5,h6 { font-family: var(--font-serif); font-weight: 600; line-height: 1.25; color: var(--primary-brown); }
p { margin-bottom: 0; font-size: .875rem; }
a, a:visited { text-decoration: none; transition: all var(--ease-fast); }
a:hover { color: var(--primary-gold); }
a:focus { outline: 0; border: 0; }
img { max-width: 100%; height: auto; display: block; }

/* ================================================================
   OUTER WRAPPER
   ================================================================ */
#outer-wrapper {
  background: var(--linen);
  width: 100%;
  margin: 0 auto;
  padding: 0;
  box-sizing: border-box;
  text-align: left;
}

/* ================================================================
   NAVBAR — FIXED
   ================================================================ */
#divmenubar {
  margin: 0;
  padding: 6px 14px 6px 8px;
  background: linear-gradient(90deg, #1c0c04 0%, #2e1608 60%, #1c0c04 100%);
  box-shadow: 0 2px 16px rgba(0,0,0,.4);
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 999;
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-bottom: 1.5px solid rgba(201, 162, 58, .35);
  height: var(--navbar-h);
}
#btnmenu {
  border: 1.5px solid rgba(201, 162, 58, .5);
  border-radius: 30px;
  color: #f5d98a;
  background: transparent;
  padding: 5px 14px;
  margin: 0;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  letter-spacing: .06em;
  font-family: var(--font-sans);
  transition: all .25s ease;
  white-space: nowrap;
}
#btnmenu:hover { background-color: #fe5100; color: #fff; border-color: #fe5100; }
#menu-container { position: absolute; top: 100%; left: 0; width: 100%; box-sizing: border-box; z-index: 998; }
.menutombol { width: 100%; box-sizing: border-box; }
#divmenu {
  display: none;
  padding: 6px;
  margin: 0;
  background: linear-gradient(90deg, #1c0c04 0%, #2e1608 60%, #1c0c04 100%);
  position: relative;
  box-sizing: border-box;
  border-bottom: 1.5px solid rgba(201, 162, 58, .35);
  box-shadow: 0 6px 24px rgba(0,0,0,.45);
  animation: slideDown .35s ease-out;
}
@keyframes slideDown { from { opacity: 0; transform: translateY(-16px); } to { opacity: 1; transform: translateY(0); } }
.menu-wrapper {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(72px, 90px));
  gap: 8px;
  padding: 6px;
  justify-items: center;
  justify-content: center;
  border-radius: var(--r-lg);
  margin: 0;
  width: 100%;
  box-sizing: border-box;
}
.divtombol {
  background: linear-gradient(160deg, #6b5740 0, #3e2e20 100%);
  color: #fff;
  text-align: center;
  border-radius: 14px;
  width: 100%;
  max-width: 90px;
  aspect-ratio: 1/1;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  cursor: pointer;
  box-shadow: 2px 2px 6px rgba(0,0,0,.35);
  transition: all var(--ease-base);
  position: relative;
  overflow: hidden;
  box-sizing: border-box;
  text-decoration: none;
}
.divtombol::before {
  content: '';
  position: absolute;
  top: 0; left: -100%;
  width: 100%; height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,.18), transparent);
  transition: left var(--ease-slow);
}
.divtombol:hover::before { left: 100%; }
.divtombol:hover {
  transform: translateY(-6px) scale(1.05);
  box-shadow: var(--shadow-xl);
  background: linear-gradient(135deg, var(--primary-gold) 0, #d4a017 100%);
  color: #fff;
}
.imgtombol { width: 58%; height: auto; border: 0; border-radius: 0; display: block; margin: 0 auto 4px; filter: brightness(1.1); transition: transform var(--ease-base); pointer-events: none; }
.divtombol:hover .imgtombol { transform: scale(1.1) rotate(5deg); }
.tulisan { color: #fff; font-size: 13px; font-weight: 500; text-align: center; line-height: 1.3; margin-top: var(--sp-xs); letter-spacing: .3px; }

/* Login button */
#login-portal-wrap { margin-left: auto; display: flex; align-items: center; padding: 0 4px; }
button.btn-login-portal {
  display: inline-flex; align-items: center; gap: 8px;
  padding: 7px 20px;
  background: linear-gradient(135deg, #c9931a, #f0c040, #c9931a);
  background-size: 200% auto;
  border: none; border-radius: 30px;
  color: #1c0e00;
  font-family: var(--font-sans); font-size: 12px; font-weight: 800;
  letter-spacing: .12em; cursor: pointer; white-space: nowrap;
  box-shadow: 0 2px 12px rgba(180,130,0,.55), inset 0 1px 0 rgba(255,255,255,.3);
  transition: background-position .4s ease, box-shadow .3s ease, transform .2s ease;
}
button.btn-login-portal:hover { background-position: right center; box-shadow: 0 4px 22px rgba(180,130,0,.75); transform: translateY(-1px); }
button.btn-login-portal .login-dot { width: 5px; height: 5px; border-radius: 50%; background: #1c0e00; flex-shrink: 0; animation: loginPulse 2s ease-in-out infinite; }
@keyframes loginPulse { 0%,100% { opacity: .4; transform: scale(1); } 50% { opacity: 1; transform: scale(1.4); } }

/* Photo modal */
.modal, .modalx { display: none; position: fixed; z-index: 1000; inset: 0; padding: 20px; background-color: rgba(20,12,28,.75); backdrop-filter: blur(8px); overflow-y: auto; align-items: center; justify-content: center; animation: modalFadeIn .25s ease; }
.modal[style*="block"], .modalx[style*="block"] { display: flex !important; }
@keyframes modalFadeIn { from { opacity: 0; } to { opacity: 1; } }
@keyframes modalSlideUp { from { transform: translateY(40px) scale(.96); opacity: 0; } to { transform: translateY(0) scale(1); opacity: 1; } }
.photo-modal-content { background: #fff; border-radius: 20px; width: 100%; max-width: 300px; margin: auto; overflow: hidden; box-shadow: 0 32px 80px rgba(0,0,0,.45); animation: modalSlideUp .32s cubic-bezier(.34,1.56,.64,1); }
.photo-modal-hero { position: relative; width: 100%; aspect-ratio: 3/3.5; overflow: hidden; background: #e8e0d8; }
.photo-modal-hero img { width: 100%; height: 100%; object-fit: cover; object-position: top center; display: block; }
.photo-modal-overlay { position: absolute; bottom: 0; left: 0; right: 0; height: 50%; background: linear-gradient(to top, rgba(10,6,16,.7), transparent); pointer-events: none; }
.photo-modal-close { position: absolute; top: 10px; right: 10px; width: 32px; height: 32px; border-radius: 50%; background: rgba(0,0,0,.45); border: 0; color: rgba(255,255,255,.9); font-size: 18px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all .2s ease; z-index: 2; line-height: 1; }
.photo-modal-close:hover { background: rgba(184,134,11,.8); color: #fff; }
.photo-modal-info { padding: 16px 18px 20px; background: #fff; }
.photo-modal-badge { display: inline-block; font-family: var(--font-sans); font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 1.2px; color: #b8860b; background: rgba(184,134,11,.1); border: 1px solid rgba(184,134,11,.25); padding: 3px 10px; border-radius: 20px; margin-bottom: 8px; }
.photo-modal-name { font-family: var(--font-serif); font-size: 20px; font-weight: 700; color: #2c2c2c; margin: 0 0 5px; line-height: 1.25; }
.photo-modal-sub { font-family: var(--font-sans); font-size: 11px; font-weight: 500; color: #9e9e9e; letter-spacing: .3px; }

/* Skeleton shimmer */
@keyframes hp-shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
.hp-skel { background: linear-gradient(90deg, #e0d9cc 25%, #ede8df 50%, #e0d9cc 75%); background-size: 200% 100%; animation: hp-shimmer 1.4s infinite; border-radius: 8px; }
.hp-skel-wrap { transition: opacity .35s ease; overflow: hidden; }
.hp-skel-wrap.hp-hidden { opacity: 0; height: 0; padding: 0; margin: 0; pointer-events: none; }

/* ================================================================
   HERO SECTION
   ================================================================ */
.site-hero {
  position: relative;
  width: 100%;
  height: 100vh;
  min-height: 460px;
  max-height: 650px;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
  isolation: isolate;
  background: #0b0705;
}

.site-hero__bg {
  position: fixed;
  top: 0; left: 0;
  width: 100%; height: 100vh;
  background-image: url('/img/gereja/interiorwide.webp');
  background-size: cover;
  background-position: center center;
  background-repeat: no-repeat;
  filter: brightness(.68) saturate(1.05) contrast(1.03);
  transform: scale(1.03);
  will-change: transform;
  animation: heroSlowZoom 18s ease-in-out infinite alternate;
  z-index: -2;
}

@keyframes heroSlowZoom {
  from { transform: scale(1.03); }
  to { transform: scale(1.08); }
}

.site-hero__overlay {
  position: absolute; inset: 0;
  background: linear-gradient(to bottom, rgba(8,4,1,.82) 0%, rgba(8,4,1,.55) 35%, rgba(8,4,1,.68) 65%, rgba(8,4,1,.92) 100%);
  z-index: 1;
}

.site-hero__noise {
  position: absolute; inset: 0; z-index: 1; opacity: .035;
  background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.85' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='1'/%3E%3C/svg%3E");
  background-size: 200px 200px;
  pointer-events: none;
}

.site-hero::after {
  content: '';
  position: absolute; left: 0; right: 0; bottom: 0;
  height: 2px;
  background: linear-gradient(90deg, transparent 0%, rgba(201,162,58,.4) 15%, rgba(201,162,58,.85) 35%, #f0d060 50%, rgba(201,162,58,.85) 65%, rgba(201,162,58,.4) 85%, transparent 100%);
  z-index: 5;
}

.site-hero__content {
  position: relative; z-index: 3;
  text-align: center;
  padding: 0 24px 48px;
  display: flex; flex-direction: column; align-items: center;
  animation: heroReveal 1.4s cubic-bezier(.22,1,.36,1) both;
  animation-delay: .25s;
}

@keyframes heroReveal {
  from { opacity: 0; transform: translateY(28px); }
  to { opacity: 1; transform: translateY(0); }
}

.site-hero__seal {
  width: 84px; height: 84px;
  border-radius: 50%;
  border: 1.5px solid rgba(201,162,58,.45);
  padding: 5px;
  background: rgba(255,255,255,.07);
  backdrop-filter: blur(6px);
  margin-bottom: 20px;
  box-shadow: 0 0 0 1px rgba(201,162,58,.15), 0 0 40px rgba(201,162,58,.2);
  animation: sealPulse 5s ease-in-out infinite;
}

@keyframes sealPulse {
  0%,100% { box-shadow: 0 0 0 1px rgba(201,162,58,.15), 0 0 40px rgba(201,162,58,.2); }
  50% { box-shadow: 0 0 0 1px rgba(201,162,58,.3), 0 0 56px rgba(201,162,58,.35); }
}

.site-hero__seal img { width: 100%; height: 100%; border-radius: 50%; object-fit: contain; }

.site-hero__eyebrow {
  font-family: var(--font-cinzel);
  font-size: .90rem;
  letter-spacing: .42em;
  text-transform: uppercase;
  color: rgba(201,162,58,.72);
  margin-bottom: 16px;
  font-weight: 400;
  white-space: nowrap;
}

.site-hero__title {
  font-family: 'Cormorant Garamond', serif;
  font-size: clamp(2.8rem, 7vw, 4.8rem);
  font-weight: 700;
  font-style: normal;
  line-height: 1.25;
  letter-spacing: .02em;
  margin-bottom: 14px;
  text-align: center;
  white-space: nowrap;
  text-shadow: 0 4px 32px rgba(0,0,0,.55), 0 1px 2px rgba(0,0,0,.35);
  background: linear-gradient(135deg, #ffffff 0%, rgba(255,255,255,.96) 38%, #f4dfa0 72%, #d4ae43 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  text-rendering: optimizeLegibility;
  animation: heroTitleGlow 6s ease-in-out infinite alternate;
}

@keyframes heroTitleGlow {
  from { filter: drop-shadow(0 0 0 rgba(240,208,96,0)); }
  to { filter: drop-shadow(0 0 10px rgba(240,208,96,.18)); }
}

.site-hero__subtitle {
  font-family: var(--font-cinzel);
  font-size: clamp(.48rem, 1.4vw, .65rem);
  letter-spacing: .3em;
  text-transform: uppercase;
  color: rgba(255,255,255,.52);
  margin-bottom: 22px;
  font-weight: 400;
}

.site-hero__divider {
  display: flex; align-items: center; gap: 14px;
  margin-bottom: 26px;
  color: rgba(201,162,58,.6);
}
.site-hero__divider span { display: block; width: 52px; height: 1px; background: currentColor; }
.site-hero__divider svg { flex-shrink: 0; }

.site-hero__actions { display: flex; gap: 12px; flex-wrap: nowrap; justify-content: center; width: 100%; }

.hero-btn {
  display: inline-flex; align-items: center; gap: 7px;
  padding: 11px 26px;
  border-radius: 4px;
  font-family: var(--font-cinzel);
  font-size: .58rem; font-weight: 500;
  letter-spacing: .22em; text-transform: uppercase;
  text-decoration: none;
  transition: all var(--ease-spring);
  white-space: nowrap;
}

.hero-btn--primary {
  background: linear-gradient(135deg, #c9a23a 0%, #e8c45a 45%, #c9a23a 100%);
  background-size: 200%;
  color: #16090a;
  box-shadow: 0 4px 20px rgba(201,162,58,.4), inset 0 1px 0 rgba(255,255,255,.25);
}
.hero-btn--primary:hover { background-position: right center; transform: translateY(-3px); box-shadow: 0 10px 32px rgba(201,162,58,.55); color: #16090a; }

.hero-btn--ghost {
  background: rgba(255,255,255,.08); color: rgba(255,255,255,.82);
  border: 1px solid rgba(255,255,255,.22); backdrop-filter: blur(10px);
}
.hero-btn--ghost:hover { background: rgba(255,255,255,.14); border-color: rgba(201,162,58,.55); color: var(--gold-bright); transform: translateY(-3px); }

.site-hero__scroll {
  position: absolute; left: 50%; bottom: 22px;
  transform: translateX(-50%); z-index: 3;
  display: flex; flex-direction: column; align-items: center; gap: 4px;
  color: rgba(255,255,255,.35);
  animation: scrollBounce 2.5s ease-in-out infinite;
}
.site-hero__scroll-label {
  font-family: var(--font-cinzel); font-size: .42rem;
  letter-spacing: .3em; text-transform: uppercase; color: rgba(255,255,255,.99);
}
@keyframes scrollBounce {
  0%,100% { transform: translateX(-50%) translateY(0); opacity: .35; }
  50% { transform: translateX(-50%) translateY(8px); opacity: .6; }
}

main, .container-main, .page-wrapper { position: relative; z-index: 2; background: var(--linen); }

/* ================================================================
   MAIN CONTENT LAYOUT
   ================================================================ */
.container-main { max-width: 960px; margin: 0 auto; padding: 0 16px; }
.content-section { border-radius: var(--r-lg); padding: 28px 24px 24px; margin-bottom: 16px; position: relative; overflow: hidden; }
main { padding-top: 20px; padding-bottom: 32px; }

/* ================================================================
   SECTION HEADINGS
   ================================================================ */
.section-heading { text-align: center; margin-bottom: 24px; }
.section-heading__ornament { display: flex; align-items: center; justify-content: center; gap: 10px; margin-bottom: 8px; color: var(--primary-gold); opacity: .65; }
.section-heading__ornament span { display: block; width: 28px; height: 1px; background: currentColor; }
.section-heading__ornament svg { flex-shrink: 0; }
.section-heading h2 { font-family: var(--font-serif); font-size: clamp(1.35rem,3.5vw,2rem); font-weight: 600; color: var(--primary-brown); letter-spacing: .02em; margin: 0 0 4px; line-height: 1.2; }
.section-heading__sub { font-family: var(--font-cinzel); font-size: .48rem; letter-spacing: .32em; text-transform: uppercase; color: var(--text-light); margin: 0; font-weight: 400; opacity: .75; }

/* ================================================================
   ARTIKEL
   ================================================================ */
.artikel-layout { display: grid; grid-template-columns: 1.6fr 1fr; gap: 14px; align-items: stretch; min-width: 0; }
.artikel-layout > * { min-width: 0; }
.artikel-side { display: flex; flex-direction: column; gap: 12px; }
#artikelSlider { position: relative; border-radius: var(--r-md); overflow: hidden; height: 270px; display: block; cursor: pointer; contain: layout style; box-shadow: var(--shadow-md); }
.artikel-slide { position: absolute; inset: 0; opacity: 0; transition: opacity .6s ease; text-decoration: none; display: block; }
.artikel-slide.active { opacity: 1; z-index: 1; }
.artikel-slide img { width: 100%; height: 100%; object-fit: cover; display: block; }
.artikel-overlay { position: absolute; inset: 0; background: linear-gradient(to top,rgba(0,0,0,.78) 0%,rgba(0,0,0,.12) 55%,transparent 100%); display: flex; flex-direction: column; justify-content: flex-end; padding: 18px 18px 16px; pointer-events: none; }
.artikel-overlay h3 { color: #fff; font-size: 1rem; line-height: 1.4; margin: 0 0 8px; text-align: left; font-family: var(--font-serif); font-weight: 600; text-shadow: 0 1px 8px rgba(0,0,0,.5); }
.slider-btn { position: absolute; top: 50%; transform: translateY(-50%); z-index: 10; width: 36px; height: 36px; border-radius: 50%; background: rgba(255,255,255,.14); backdrop-filter: blur(8px); border: 1px solid rgba(255,255,255,.28); color: #fff; font-size: 16px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: background .2s,transform .2s; user-select: none; line-height: 1; }
.slider-btn:hover { background: rgba(201,162,58,.75); border-color: rgba(201,162,58,.6); transform: translateY(-50%) scale(1.08); }
.slider-btn.prev { left: 10px; }
.slider-btn.next { right: 10px; }
.slider-dots { position: absolute; bottom: 12px; right: 14px; display: flex; gap: 5px; z-index: 10; }
.dot { width: 6px; height: 6px; border-radius: 50%; background: rgba(255,255,255,.35); transition: background .3s,transform .3s; cursor: pointer; }
.dot.active { background: #fff; transform: scale(1.35); }
.slider-progress { position: absolute; bottom: 0; left: 0; height: 3px; background: linear-gradient(90deg,var(--primary-gold),#d4af37); z-index: 10; transition: width .1s linear; border-radius: 0 2px 0 0; }
.artikel-small { position: relative; display: block; border-radius: var(--r-sm); overflow: hidden; text-decoration: none; color: #fff; flex: 1; min-height: 120px; background: #1a0e05; box-shadow: var(--shadow-sm); transition: transform .28s var(--ease-spring),box-shadow .28s ease; }
.artikel-small:hover { transform: translateY(-3px) scale(1.01); box-shadow: var(--shadow-md); }
.artikel-small img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; object-position: center top; display: block; transition: transform .4s ease; filter: brightness(.8) saturate(1.05); }
.artikel-small:hover img { transform: scale(1.06); filter: brightness(.68) saturate(1.1); }
.artikel-small::after { content: ''; position: absolute; inset: 0; background: linear-gradient(to top,rgba(20,10,3,.9) 0%,rgba(20,10,3,.45) 45%,rgba(20,10,3,.08) 75%,transparent 100%); z-index: 1; }
.artikel-small:hover::before { height: 78%; }
.artikel-small-content { position: absolute; inset: 0; z-index: 2; display: flex; flex-direction: column; justify-content: flex-end; padding: 10px 12px 12px 14px; }
.artikel-small-content p { font-size: .72rem; line-height: 1.4; margin: 0; text-align: left; color: #fff; font-family: var(--font-narrow); font-weight: 500; letter-spacing: .01em; text-shadow: 0 1px 6px rgba(0,0,0,.6); display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }

/* ================================================================
   QUICK NAVIGATION
   ================================================================ */
.quick-nav-section { margin-bottom: 16px; }
.quick-nav { display: grid; grid-template-columns: repeat(3,1fr); gap: 12px; }
.quick-nav-card { background: #fff; border: 1px solid rgba(201,162,58,.13); border-radius: var(--r-md); padding: 22px 12px 20px; text-align: center; text-decoration: none; color: var(--text-dark); transition: all var(--ease-spring); display: flex; flex-direction: column; align-items: center; gap: 12px; position: relative; overflow: hidden; box-shadow: var(--shadow-sm); }
.quick-nav-card::after { content: ''; position: absolute; bottom: 0; left: 0; right: 0; height: 2px; background: linear-gradient(90deg,transparent,var(--primary-gold),transparent); transform: scaleX(0); transition: transform .32s ease; }
.quick-nav-card:hover { transform: translateY(-5px); box-shadow: var(--shadow-lg); border-color: rgba(201,162,58,.35); color: var(--text-dark); }
.quick-nav-card:hover::after { transform: scaleX(1); }
.quick-nav-icon { width: 44px; height: 44px; border-radius: 50%; background: rgba(201,162,58,.08); border: 1px solid rgba(201,162,58,.18); display: flex; align-items: center; justify-content: center; color: var(--primary-gold); transition: all .25s ease; flex-shrink: 0; }
.quick-nav-card:hover .quick-nav-icon { background: linear-gradient(135deg,var(--primary-gold),#e0b840); color: #fff; border-color: transparent; box-shadow: 0 4px 16px rgba(201,162,58,.35); }
.quick-nav-label { font-family: var(--font-cinzel); font-size: .58rem; font-weight: 500; letter-spacing: .22em; text-transform: uppercase; color: var(--text-mid); transition: color .2s ease; }
.quick-nav-card:hover .quick-nav-label { color: var(--primary-brown); }

/* ================================================================
   EVENT BANNER
   ================================================================ */
.event-banner-section { margin: 0 0 16px; display: flex; justify-content: center; }
.event-banner-link { display: inline-flex; max-width: 100%; text-decoration: none; color: inherit; margin: 0 auto; border-radius: var(--r-lg); overflow: hidden; }
.event-banner-wrapper { display: inline-flex; align-items: stretch; position: relative; border-radius: var(--r-lg); overflow: hidden; background: linear-gradient(135deg,#1a0905 0%,#2c1a0e 40%,#3a2210 70%,#1a0905 100%); border: 1px solid rgba(201,162,58,.28); box-shadow: 0 8px 40px rgba(0,0,0,.42),inset 0 1px 0 rgba(255,255,255,.04); transition: transform var(--ease-spring),box-shadow .3s ease; }
.event-banner-link:hover .event-banner-wrapper { transform: translateY(-4px); box-shadow: 0 18px 56px rgba(0,0,0,.52),0 0 32px rgba(201,162,58,.14); }
.event-banner-glow { position: absolute; inset: 0; pointer-events: none; z-index: 0; background: radial-gradient(ellipse 140px 100px at 28% 50%,rgba(201,162,58,.09) 0%,transparent 70%),radial-gradient(ellipse 80px 80px at 85% 20%,rgba(212,175,55,.06) 0%,transparent 65%); }
.event-banner-wrapper::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 1px; z-index: 10; background: linear-gradient(90deg,transparent 0%,rgba(201,162,58,.65) 30%,rgba(212,175,55,.92) 55%,rgba(201,162,58,.5) 80%,transparent 100%); }
.event-banner-info { position: relative; z-index: 1; padding: 22px 18px 22px 24px; display: flex; flex-direction: column; gap: 8px; justify-content: center; min-width: 220px; max-width: 300px; }
.event-badge { display: inline-flex; align-items: center; gap: 5px; background: transparent; border: 1px solid rgba(212,175,55,.42); color: #e8c84a; font-size: .56rem; font-weight: 700; padding: 3px 10px; border-radius: 4px; letter-spacing: .12em; text-transform: uppercase; width: fit-content; }
.event-banner-title { margin: 0; font-size: 1rem; font-weight: 700; line-height: 1.25; color: #f0e8d0; font-family: var(--font-serif); }
.event-banner-title span { display: block; font-size: 1.2em; font-weight: 800; background: linear-gradient(90deg,#f5d96a,#e8c84a,#fff8dc); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
.event-banner-subtitle { margin: 0; font-size: .62rem; color: rgba(255,255,255,.38); line-height: 1.5; font-style: italic; }
.event-banner-info::after { content: ''; display: block; height: 1px; background: linear-gradient(90deg,rgba(212,175,55,.22),transparent); margin: -2px 0; }
.event-banner-meta { display: flex; flex-direction: column; gap: 4px; }
.event-meta-item { font-size: .65rem; color: rgba(255,255,255,.72); display: flex; align-items: flex-start; gap: 7px; line-height: 1.4; }
.event-banner-tickets { display: flex; flex-wrap: wrap; gap: 5px; margin-top: 2px; }
.event-banner-tickets span { background: rgba(212,175,55,.06); border: 1px solid rgba(212,175,55,.2); color: rgba(245,217,106,.82); font-size: .56rem; font-weight: 600; padding: 3px 8px; border-radius: 4px; white-space: nowrap; }
.event-banner-cta { margin-top: 4px; }
.event-cta-btn { display: inline-flex; align-items: center; gap: 6px; background: linear-gradient(135deg,#c8a020 0%,#e8c84a 50%,#c8a020 100%); background-size: 200%; color: #12080f; font-size: .7rem; font-weight: 800; padding: 8px 18px; border-radius: 6px; letter-spacing: .06em; text-transform: uppercase; box-shadow: 0 4px 16px rgba(212,175,55,.3),inset 0 1px 0 rgba(255,255,255,.25); animation: shimmer-btn 3.5s linear infinite; }
@keyframes shimmer-btn { 0% { background-position: 0%; } 100% { background-position: 200%; } }
.event-banner-flyer { position: relative; flex: 0 0 auto; width: 145px; align-self: stretch; overflow: hidden; }
.event-banner-flyer::before { content: ''; position: absolute; inset: 0 auto 0 0; width: 60px; background: linear-gradient(to right,#1a0905,rgba(26,9,5,.55) 60%,transparent 100%); z-index: 1; pointer-events: none; }
.event-banner-flyer::after { content: ''; position: absolute; inset: 0; background: linear-gradient(to top,rgba(26,9,5,.35) 0%,transparent 50%); z-index: 1; pointer-events: none; }
.event-banner-flyer img { width: 100%; height: 100%; display: block; object-fit: cover; object-position: top center; filter: brightness(1.05) contrast(1.02); }

/* ================================================================
   ROMO PAROKI
   ================================================================ */
.romo-modern { text-align: center; }
.romo-wrapper { display: flex; justify-content: center; align-items: stretch; gap: 28px; flex-wrap: wrap; width: 100%; margin-top: 40px; }
.romo-card.modern { position: relative; flex: 0 0 200px; width: 200px; padding: 0 0 24px; border-radius: var(--r-lg); background: #fff; border: 1px solid rgba(201,162,58,.2); box-shadow: var(--shadow-sm); transition: transform .35s var(--ease-spring),box-shadow .35s ease; overflow: visible; }
.romo-card.modern:hover { transform: translateY(-10px); box-shadow: var(--shadow-xl),0 0 0 1.5px rgba(201,162,58,.28); }
.romo-card-top { border-radius: var(--r-lg) var(--r-lg) 0 0; background: linear-gradient(135deg,#1c0c04 0%,#4a2a14 60%,#1c0c04 100%); position: relative; height: 52px; }
.romo-card-top::after { content: ''; position: absolute; inset: 0; border-radius: var(--r-lg) var(--r-lg) 0 0; background: repeating-linear-gradient(45deg,transparent,transparent 18px,rgba(201,162,58,.055) 18px,rgba(201,162,58,.055) 19px); }
.romo-img-wrap { position: absolute; top: -40px; left: 50%; transform: translateX(-50%); width: 82px; height: 82px; border-radius: 50%; background: #fff; padding: 3px; box-shadow: 0 6px 24px rgba(30,16,8,.18); z-index: 2; }
.romo-img-inner { width: 100%; height: 100%; border-radius: 50%; overflow: hidden; border: 1.5px solid rgba(201,162,58,.45); }
.romo-img-wrap img { width: 100%; height: 100%; object-fit: cover; display: block; border-radius: 50%; }
.romo-card-body { padding: 30px 18px 0; text-align: center; display: flex; flex-direction: column; align-items: center; }
.romo-role { display: inline-block; font-size: .52rem; font-weight: 500; letter-spacing: .22em; text-transform: uppercase; color: var(--primary-gold); background: rgba(201,162,58,.08); border: .5px solid rgba(201,162,58,.28); padding: 4px 12px; border-radius: 20px; margin-bottom: 10px; font-family: var(--font-cinzel); }
.romo-name { font-family: var(--font-serif); font-size: 1.05rem; font-weight: 600; color: var(--primary-brown); line-height: 1.35; margin: 0; text-align: center; width: 100%; }
.romo-ornament { display: flex; align-items: center; justify-content: center; gap: 8px; margin: 12px auto 0; opacity: .45; }
.romo-ornament span { display: inline-block; height: .5px; width: 28px; background: var(--primary-gold); }
.romo-ornament i { width: 4px; height: 4px; background: var(--primary-gold); transform: rotate(45deg); display: inline-block; flex-shrink: 0; }

/* ================================================================
   FOOTER
   ================================================================ */
.ft-wrap{background:var(--ft-bg);color:var(--ft-cream);font-family:'DM Sans',sans-serif;font-weight:300;position:relative;overflow:hidden}
.ft-wrap::after{content:'';position:absolute;inset:0;background-image:url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.85' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='1'/%3E%3C/svg%3E");background-size:200px 200px;opacity:.022;pointer-events:none;z-index:0}
.ft-ambient{position:absolute;width:700px;height:400px;left:50%;top:0;transform:translateX(-50%);background:radial-gradient(ellipse,rgba(218,175,90,0.055) 0%,transparent 65%);pointer-events:none;z-index:0}
.ft-topline{position:relative;z-index:1;height:1px;background:linear-gradient(90deg,transparent 0%,rgba(218,175,90,0.15) 15%,rgba(218,175,90,0.6) 35%,var(--ft-gold-bright) 50%,rgba(218,175,90,0.6) 65%,rgba(218,175,90,0.15) 85%,transparent 100%)}
.ft-header{position:relative;z-index:1;display:flex;align-items:center;justify-content:space-between;gap:20px;max-width:960px;margin:0 auto;padding:5px 50px;border-bottom:1px solid var(--ft-line)}
.ft-header-identity{display:flex;align-items:center;gap:14px;flex-shrink:0}
.ft-header-cross{color:var(--ft-gold-mid);filter:drop-shadow(0 0 4px rgba(218,175,90,0.45));flex-shrink:0}
.ft-header-name{font-family:'Cormorant Garamond',serif;font-weight:300;font-style:italic;font-size:1.15rem;letter-spacing:.04em;margin:0;line-height:1.1;background:linear-gradient(135deg,var(--ft-gold-deep) 0%,var(--ft-gold-bright) 45%,#ffe9a0 50%,var(--ft-gold-bright) 55%,var(--ft-gold-deep) 100%);background-size:200% auto;-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.ft-header-sub{font-family:'Cinzel',serif;font-size:.42rem;letter-spacing:.28em;text-transform:uppercase;color:rgba(218,175,90,0.38);margin:3px 0 0;font-weight:400}
.ft-grid{position:relative;z-index:1;display:grid;grid-template-columns:repeat(3,1fr);max-width:960px;margin:0 auto}
.ft-col{padding:8px 24px 4px;border-right:1px solid var(--ft-line)}
.ft-col:last-child{border-right:none}
.ft-col-title{font-family:'Cinzel',serif;font-size:.48rem;font-weight:500;letter-spacing:.32em;text-transform:uppercase;color:var(--ft-gold-deep);margin:0 0 16px;display:flex;align-items:center;gap:10px}
.ft-col-title::after{content:'';flex:1;height:1px;background:linear-gradient(90deg,var(--ft-gold-faint),transparent)}
.ft-nav{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:2px}
.ft-nav li a{display:flex;align-items:center;gap:8px;color:rgba(237,227,208,0.4);text-decoration:none;font-size:.75rem;font-weight:300;letter-spacing:.04em;padding:4px 0;transition:color .25s,gap .25s;position:relative}
.ft-nav li a::before{content:'';width:0;height:1px;background:var(--ft-gold-mid);transition:width .25s;flex-shrink:0}
.ft-nav li a:hover{color:var(--ft-cream);gap:12px}
.ft-nav li a:hover::before{width:14px}
.ft-jadwal-item{display:flex;justify-content:space-between;align-items:baseline;padding:4px 0;border-bottom:1px solid rgba(218,175,90,0.08)}
.ft-jadwal-item:last-of-type{border-bottom:none}
.ft-jadwal-hari{font-size:.72rem;font-weight:300;color:rgba(237,227,208,0.45);letter-spacing:.02em}
.ft-jadwal-waktu{font-family:'Cormorant Garamond',serif;font-size:.95rem;font-weight:400;color:var(--ft-gold-mid);letter-spacing:.05em}
.ft-btn-ghost{display:inline-flex;align-items:center;gap:7px;background:transparent;border:1px solid rgba(218,175,90,0.22);border-radius:100px;padding:5px 12px;color:var(--ft-gold-dim);font-family:'Cinzel',serif;font-size:.46rem;font-weight:400;letter-spacing:.25em;text-transform:uppercase;cursor:pointer;text-decoration:none;margin-top:14px;transition:border-color .25s,color .25s,background .25s}
.ft-btn-ghost svg{width:11px;height:11px;stroke:currentColor;fill:none;stroke-width:1.5;flex-shrink:0}
.ft-btn-ghost:hover{border-color:var(--ft-gold-mid);color:var(--ft-gold-bright);background:var(--ft-gold-ghost)}
.ft-info-item{display:flex;align-items:flex-start;gap:10px;margin-bottom:10px}
.ft-info-icon{flex-shrink:0;width:14px;height:14px;stroke:var(--ft-gold-mid);fill:none;stroke-width:1.5;stroke-linecap:round;stroke-linejoin:round;opacity:.75;margin-top:2px}
.ft-info-text{font-size:.72rem;font-weight:300;color:rgba(237,227,208,0.45);line-height:1.6;letter-spacing:.02em}
.ft-col-sep{height:1px;background:linear-gradient(90deg,var(--ft-gold-faint),transparent);margin:14px 0}
.ft-bottom{position:relative;z-index:1;border-top:1px solid var(--ft-line);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;padding:5px 40px;max-width:960px;margin:0 auto}
.ft-copy{font-size:.62rem;font-weight:300;color:rgba(237,227,208,0.2);letter-spacing:.06em}
.ft-social{display:flex;gap:10px;align-items:center}
.ft-social-btn{width:32px;height:32px;border:1px solid rgba(218,175,90,0.18);border-radius:50%;display:flex;align-items:center;justify-content:center;text-decoration:none;transition:border-color .25s,background .25s,transform .25s}
.ft-social-btn svg{width:13px;height:13px;stroke:var(--ft-gold-mid);fill:none;stroke-width:1.5;transition:stroke .25s}
.ft-social-btn:hover{border-color:var(--ft-gold-mid);background:var(--ft-gold-ghost);transform:translateY(-2px)}
.ft-social-btn:hover svg{stroke:var(--ft-gold-bright)}
.ft-modal-backdrop{position:fixed;inset:0;z-index:9000;background:rgba(6,4,10,0.88);backdrop-filter:blur(18px) saturate(1.2);display:flex;align-items:center;justify-content:center;opacity:0;pointer-events:none;transition:opacity .38s ease}
.ft-modal-backdrop.open{opacity:1;pointer-events:all}
.ft-modal-card{background:linear-gradient(160deg,#0e0b18 0%,#080610 100%);border:1px solid rgba(218,175,90,0.22);border-radius:20px;overflow:hidden;box-shadow:0 48px 96px rgba(0,0,0,0.7);transform:scale(0.94) translateY(16px);transition:transform .38s cubic-bezier(0.22,0.61,0.36,1);width:360px;max-width:calc(100vw - 40px)}
.ft-modal-card--wide{width:420px}
.ft-modal-backdrop.open .ft-modal-card{transform:scale(1) translateY(0)}
.ft-modal-header{padding:28px 28px 20px;text-align:center;border-bottom:1px solid rgba(218,175,90,0.12);position:relative;background:linear-gradient(180deg,rgba(218,175,90,0.05) 0%,transparent 100%)}
.ft-modal-close{position:absolute;top:14px;right:16px;background:rgba(218,175,90,0.06);border:1px solid rgba(218,175,90,0.15);border-radius:50%;width:28px;height:28px;display:flex;align-items:center;justify-content:center;color:rgba(237,227,208,0.4);font-size:16px;cursor:pointer;line-height:1;transition:background .2s,color .2s}
.ft-modal-close:hover{background:rgba(218,175,90,0.14);color:var(--ft-cream)}
.ft-modal-logo{width:80px;margin:0 auto 14px;display:block;filter:drop-shadow(0 0 12px rgba(218,175,90,0.2))}
.ft-modal-title{font-family:'Cormorant Garamond',serif;font-style:italic;font-size:1.1rem;font-weight:300;color:var(--ft-cream);letter-spacing:.04em;margin:0 0 4px}
.ft-modal-subtitle{font-family:'Cinzel',serif;font-size:.52rem;font-weight:400;color:rgba(218,175,90,0.45);letter-spacing:.3em;text-transform:uppercase;margin:0}
.ft-modal-body{padding:22px 28px 26px}
.ft-modal-label{font-family:'Cinzel',serif;font-size:.5rem;letter-spacing:.35em;text-transform:uppercase;color:rgba(218,175,90,0.45);font-weight:400;margin-bottom:10px;display:block}
.ft-modal-email{font-family:'Cormorant Garamond',serif;font-size:.9rem;color:var(--ft-cream-dim);font-style:italic;letter-spacing:.03em;margin-bottom:12px}
.ft-modal-dev{font-size:.78rem;color:rgba(237,227,208,0.4);font-weight:300}
.ft-modal-dev a{color:var(--ft-gold-mid);font-weight:400;text-decoration:none;border-bottom:1px solid rgba(218,175,90,0.25);padding-bottom:1px}
.ft-modal-divider{height:1px;background:linear-gradient(to right,transparent,rgba(218,175,90,0.2),transparent);margin:18px 0}
.ft-modal-jadwal-item{display:flex;justify-content:space-between;align-items:baseline;padding:8px 0;border-bottom:1px solid rgba(218,175,90,0.08)}
.ft-modal-jadwal-item:last-child{border-bottom:none}
.ft-modal-jadwal-hari{font-size:.8rem;font-weight:300;color:rgba(237,227,208,0.45);letter-spacing:.02em;flex:1}
.ft-modal-jadwal-waktu{font-family:'Cormorant Garamond',serif;font-size:1rem;font-weight:400;color:var(--ft-gold-mid);text-align:right;flex-shrink:0;margin-left:12px}
.ft-modal-stasi-item{display:flex;justify-content:space-between;align-items:flex-start;padding:9px 0;border-bottom:1px solid rgba(218,175,90,0.07)}
.ft-modal-stasi-item:last-child{border-bottom:none}
.ft-modal-stasi-nama{font-size:.78rem;font-weight:300;color:rgba(237,227,208,0.42);letter-spacing:.02em;flex:1;line-height:1.5}
.ft-modal-stasi-waktu{font-family:'Cormorant Garamond',serif;font-size:.9rem;font-weight:400;color:var(--ft-gold-mid);text-align:right;flex-shrink:0;margin-left:12px;line-height:1.5}
.ft-modal-note{display:flex;align-items:center;gap:7px;margin-top:16px;padding-top:14px;border-top:1px solid rgba(218,175,90,0.1);font-size:.7rem;font-weight:300;color:rgba(237,227,208,0.28);letter-spacing:.02em}
.ft-modal-note svg{width:12px;height:12px;flex-shrink:0;stroke:var(--ft-gold-mid);fill:none;stroke-width:2;opacity:.45}

/* ================================================================
   LOADING SCREEN
   ================================================================ */
#paroki-loading-screen{--gold:#d4aa5f;--gold-b:#eedfa0;--gold-a70:rgba(212,170,95,0.70);--gold-a30:rgba(212,170,95,0.30);--gold-a12:rgba(212,170,95,0.12);--transition-out:opacity 1s cubic-bezier(0.4,0,0.2,1),visibility 1s cubic-bezier(0.4,0,0.2,1);}
#paroki-loading-screen{position:fixed;inset:0;z-index:99999;display:flex;align-items:center;justify-content:center;background:radial-gradient(ellipse 90% 80% at 50% 48%,#100b18 0%,#08050d 50%,#040308 100%);overflow:hidden;transition:var(--transition-out)}
#paroki-loading-screen.pls--hidden{opacity:0;visibility:hidden;pointer-events:none}
.pls-ambient{position:absolute;width:520px;height:520px;left:50%;top:50%;transform:translate(-50%,-50%);background:radial-gradient(ellipse,rgba(212,170,95,0.07) 0%,rgba(212,170,95,0.02) 45%,transparent 72%);pointer-events:none;animation:pls-ambient 7s ease-in-out infinite}
@keyframes pls-ambient{0%,100%{opacity:.6;transform:translate(-50%,-50%) scale(1)}50%{opacity:1;transform:translate(-50%,-50%) scale(1.12)}}
.pls-logo-section{position:relative;width:120px;height:120px;display:flex;align-items:center;justify-content:center;animation:pls-appear 1.4s cubic-bezier(0.16,1,0.3,1) .1s both}
@keyframes pls-appear{from{opacity:0;transform:scale(.88)}to{opacity:1;transform:scale(1)}}
.pls-aura{position:absolute;inset:-28px;border-radius:50%;background:radial-gradient(circle,rgba(212,170,95,0.16) 0%,rgba(212,170,95,0.05) 55%,transparent 75%);animation:pls-aura-pulse 4s ease-in-out infinite;pointer-events:none}
@keyframes pls-aura-pulse{0%,100%{opacity:.7;transform:scale(1)}50%{opacity:1;transform:scale(1.10)}}
.pls-orbit{position:absolute;inset:-32px;border-radius:50%;border:1px solid rgba(212,170,95,0.12);animation:pls-orbit-pulse 5s ease-in-out infinite .8s;pointer-events:none}
@keyframes pls-orbit-pulse{0%,100%{opacity:.5;transform:scale(1)}50%{opacity:1;transform:scale(1.04);border-color:rgba(212,170,95,0.30)}}
.pls-spinner{position:absolute;inset:-20px;border-radius:50%;background:conic-gradient(from 0turn,transparent 0%,transparent 60%,rgba(212,170,95,0.30) 75%,rgba(212,170,95,0.70) 88%,#eedfa0 95%,rgba(212,170,95,0.70) 98%,transparent 100%);-webkit-mask:radial-gradient(farthest-side,transparent calc(100% - 1.5px),#fff calc(100% - 1.5px));mask:radial-gradient(farthest-side,transparent calc(100% - 1.5px),#fff calc(100% - 1.5px));animation:pls-spin 2.4s linear infinite;pointer-events:none}
@keyframes pls-spin{to{transform:rotate(360deg)}}
.pls-logo{width:100%;height:100%;object-fit:contain;border-radius:50%;display:block;user-select:none;-webkit-user-drag:none;position:relative;z-index:1;animation:pls-logo-glow 5s ease-in-out infinite}
@keyframes pls-logo-glow{0%,100%{filter:drop-shadow(0 0 10px rgba(212,170,95,0.25)) drop-shadow(0 0 28px rgba(212,170,95,0.08))}50%{filter:drop-shadow(0 0 20px rgba(212,170,95,0.55)) drop-shadow(0 0 52px rgba(212,170,95,0.20))}}

/* ================================================================
   RESPONSIVE
   ================================================================ */
@media (max-width: 768px) {
  .site-hero__bg { position: absolute; height: 100%; }
  .site-hero { height: 82vh; min-height: 520px; }
  .site-hero__content { padding: 0 16px 70px; }
  .site-hero__seal { width: 74px; height: 74px; }
  .site-hero__divider span { width: 34px; }
  .site-hero__eyebrow { font-size: clamp(.52rem, 2.2vw, .90rem); letter-spacing: .18em; }
  .site-hero__title { font-size: clamp(1rem, 7vw, 3rem); white-space: nowrap; }
  .site-hero__actions { gap: 10px; }
  .hero-btn { flex: 1; justify-content: center; min-width: 0; padding: 11px 14px; }
}
@media (max-width: 680px) {
  .site-hero { height: 58vh; min-height: 380px; }
  .site-hero__content { padding-bottom: 36px; }
  .site-hero__title { font-size: clamp(1rem, 7vw, 3rem); }
  .site-hero__seal { width: 68px; height: 68px; margin-bottom: 14px; }
  .hero-btn { padding: 9px 18px; font-size: .52rem; }
  .content-section { padding: 20px 16px 18px; border-radius: var(--r-md); }
  .artikel-layout { grid-template-columns: 1fr; gap: 10px; }
  #artikelSlider { height: 210px; border-radius: var(--r-sm); }
  .artikel-side { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
  .artikel-small { min-height: 120px; border-radius: var(--r-sm); }
  .quick-nav { gap: 8px; }
  .quick-nav-card { padding: 16px 8px 14px; gap: 8px; border-radius: var(--r-sm); }
  .quick-nav-icon { width: 36px; height: 36px; }
  .quick-nav-label { font-size: .52rem; letter-spacing: .16em; }
  .romo-wrapper { gap: 14px; justify-content: center; flex-wrap: nowrap; margin-top: 40px; padding: 0 8px; }
  .romo-card.modern { flex: 1 1 0; min-width: 0; max-width: calc(50% - 7px); }
  .event-banner-info { min-width: 170px; max-width: 230px; padding: 16px 12px 16px 16px; gap: 6px; }
  .event-banner-title { font-size: .88rem; }
  .event-banner-flyer { width: 108px; }
  .event-cta-btn { font-size: .62rem; padding: 7px 13px; animation: none; background: #c8a020; }
  .slider-btn { display: none; }
  .ft-header { flex-direction: column; align-items: flex-start; gap: 12px; padding: 16px 20px 14px; }
  .ft-grid { grid-template-columns: 1fr 1fr; }
  .ft-col:nth-child(1) { grid-column: 1/-1; border-right: none; border-bottom: 1px solid var(--ft-line); padding-bottom: 14px; }
  .ft-col:nth-child(2) { border-bottom: 1px solid var(--ft-line); padding-bottom: 14px; }
  .ft-col:nth-child(3) { border-right: none; border-bottom: 1px solid var(--ft-line); padding-bottom: 14px; }
  .ft-bottom { padding: 12px 20px 14px; flex-direction: column; align-items: center; text-align: center; }
  .ft-nav { display: grid; grid-template-columns: 1fr 1fr; gap: 0 16px; }
}
@media (max-width: 420px) {
  .ft-grid { grid-template-columns: 1fr; }
  .ft-col { border-right: none; border-bottom: 1px solid var(--ft-line); }
  .ft-col:last-child { border-bottom: none; }
}
@media (max-width: 360px) {
  .event-banner-flyer { width: 90px; }
  .event-banner-title { font-size: .8rem; }
}
@media (max-width: 769px) {
  .menu-wrapper { gap: 6px; padding: 4px; }
  .tulisan { font-size: 11px; }
}
@media (max-width: 480px) {
  .menu-wrapper { gap: 5px; padding: 3px; }
  .tulisan { font-size: 10px; }
}
@media (prefers-reduced-motion: reduce) {
  *, *::before, *::after { animation-duration: .01ms !important; transition-duration: .01ms !important; }
}
.hidden { display: none; }
@keyframes xanimatetop { from { top: -800px; opacity: 1; } to { top: 0; opacity: 1; } }
</style>
</head>

<body>

<!-- ================================================================
     LOADING SCREEN
     ================================================================ -->
<div id="paroki-loading-screen" aria-hidden="true" role="presentation">
  <div class="pls-ambient"></div>
  <div class="pls-logo-section">
    <div class="pls-orbit"></div>
    <div class="pls-spinner"></div>
    <div class="pls-aura"></div>
    <img src="/img/parokitulungagung.png" alt="Paroki Santa Maria Dengan Tidak Bernoda Asal Tulungagung" class="pls-logo" width="120" height="120" draggable="false">
  </div>
</div>

<!-- ================================================================
     NAVBAR
     ================================================================ -->
<div id="divmenubar">
  <button id="btnmenu" aria-label="Buka menu navigasi">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true">
      <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
    </svg>
    Menu
  </button>
  <div id="login-portal-wrap">
    <button class="btn-login-portal" onclick="window.location.href='/admin'" title="Masuk ke Panel Admin">
      <span class="login-dot"></span>
      <span class="login-label">Login</span>
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13" aria-hidden="true">
        <path d="M15 3h4a2 2 0 012 2v14a2 2 0 01-2 2h-4"/>
        <polyline points="10 17 15 12 10 7"/>
        <line x1="15" y1="12" x2="3" y2="12"/>
      </svg>
    </button>
  </div>
  <div id="menu-container">
    <div>
      <div id="divmenu" class="menutombol" style="display:none">
        <div class="menu-wrapper">
          <a href="/" class="divtombol"><img class="imgtombol" src="/img/icon/icon_square_home1.png" alt="Beranda" width="48" height="48"><span class="tulisan">Home</span></a>
          <a href="/agenda" class="divtombol"><img class="imgtombol" src="/img/icon/icon_square_petugas.png" alt="Info Paroki dan Agenda" width="48" height="48"><span class="tulisan">Info/Agenda</span></a>
          <a href="/jadwal-misa" class="divtombol"><img class="imgtombol" src="/img/icon/icon_square_jadwal_misa.png" alt="Jadwal Misa" width="48" height="48"><span class="tulisan">Jadwal Misa</span></a>
          <a href="/galeri" class="divtombol"><img class="imgtombol" src="/img/icon/icon_square_foto.png" alt="Galeri Foto" width="48" height="48"><span class="tulisan">Galeri</span></a>
          <a href="/profil-lingkungan" class="divtombol"><img class="imgtombol" src="/img/icon/icon_square_lingkungan.png" alt="Wilayah Lingkungan" width="48" height="48"><span class="tulisan">Wilayah</span></a>
          <a href="/profil-ai" class="divtombol"><img class="imgtombol" src="/img/icon/icon_square_ai.png" alt="Asisten Imam" width="48" height="48"><span class="tulisan">Asisten Imam</span></a>
          <a href="/profil-dpp" class="divtombol"><img class="imgtombol" src="/img/icon/icon_square_dpp.png" alt="DPP & BGKP" width="48" height="48"><span class="tulisan">DPP &amp; BGKP</span></a>
          <a href="/kategorial" class="divtombol"><img class="imgtombol" src="/img/icon/icon_square_kategorial3.png" alt="Kategorial" width="48" height="48"><span class="tulisan">Kategorial</span></a>
          <a href="/e-lonceng" class="divtombol"><img class="imgtombol" src="/img/icon/icon_square_e-lonceng.png" alt="E-Lonceng" width="48" height="48"><span class="tulisan">E-Lonceng</span></a>
          <a href="/artikel/berita" class="divtombol"><img class="imgtombol" src="/img/icon/icon_square_berita.png" alt="Artikel" width="48" height="48"><span class="tulisan">Artikel</span></a>
          <a href="/artikel/kronik" class="divtombol"><img class="imgtombol" src="/img/icon/icon_kronik.png" alt="Kronik" width="48" height="48"><span class="tulisan">Kronik</span></a>
          <a href="/artikel/historia" class="divtombol"><img class="imgtombol" src="/img/icon/icon_historia.png" alt="Historia" width="48" height="48"><span class="tulisan">Historia</span></a>
          <a href="/umkmumat" class="divtombol"><img class="imgtombol" src="/img/icon/umkm.png" alt="Pasar Umat" width="48" height="48"><span class="tulisan">Pasar Umat</span></a>
          <a href="/komedi" class="divtombol"><img class="imgtombol" src="/img/icon/icon_square_komedi.png" alt="KoMeDi" width="48" height="48"><span class="tulisan">KoMeDi</span></a>
          <a href="/e-ticket" class="divtombol"><img class="imgtombol" src="/img/icon/icon_square_eticket.png" alt="E-Ticket" width="48" height="48"><span class="tulisan">E-Ticket</span></a>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ================================================================
     HERO
     ================================================================ -->
<header class="site-hero" id="siteHero" aria-label="Paroki SMDTBA Tulungagung">
  <div class="site-hero__bg site-hero__bg--1" aria-hidden="true"></div>
  <div class="site-hero__overlay" aria-hidden="true"></div>
  <div class="site-hero__noise" aria-hidden="true"></div>

  <div class="site-hero__content">
    <div class="site-hero__seal" aria-hidden="true">
      <img src="/img/parokitulungagung.png" alt="" width="84" height="84" loading="eager" fetchpriority="high" decoding="async">
    </div>
    <p class="site-hero__eyebrow">Gereja Katolik &middot; Keuskupan Surabaya</p>
    <h1 class="site-hero__title" aria-label="Paroki Tulungagung">PAROKI TULUNGAGUNG</h1>
    <p class="site-hero__subtitle">Santa Maria Dengan Tidak Bernoda Asal</p>
    <div class="site-hero__divider" aria-hidden="true">
      <span></span>
      <svg width="11" height="11" viewBox="0 0 11 11" fill="currentColor">
        <path d="M5.5 0L6.65 4.35L11 5.5L6.65 6.65L5.5 11L4.35 6.65L0 5.5L4.35 4.35Z"/>
      </svg>
      <span></span>
    </div>
    <div class="site-hero__actions">
      <a href="/jadwal-misa" class="hero-btn hero-btn--primary" aria-label="Lihat Jadwal Misa">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
          <rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
        </svg>
        Jadwal Misa
      </a>
      <a href="/tentang" class="hero-btn hero-btn--ghost" aria-label="Tentang Paroki">
        Tentang Paroki
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
          <polyline points="9 18 15 12 9 6"/>
        </svg>
      </a>
    </div>
  </div>

  <div class="site-hero__scroll" aria-hidden="true">
    <span class="site-hero__scroll-label">Gulir</span>
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round">
      <polyline points="6 9 12 15 18 9"/>
    </svg>
  </div>
</header>

<!-- ================================================================
     MAIN CONTENT
     ================================================================ -->
<div id="outer-wrapper">
<a id="top"></a>

<main>
<div class="container-main">

  <!-- ARTIKEL SECTION -->
  <div class="content-section" style="margin-top: 20px;">
    <div class="section-heading">
      <div class="section-heading__ornament" aria-hidden="true">
        <span></span>
        <svg width="9" height="9" viewBox="0 0 9 9" fill="currentColor">
          <path d="M4.5 0L5.5 3.5L9 4.5L5.5 5.5L4.5 9L3.5 5.5L0 4.5L3.5 3.5Z"/>
        </svg>
        <span></span>
      </div>
      <h2>Berita &amp; Artikel</h2>
      <p class="section-heading__sub">Warta Paroki Tulungagung</p>
    </div>

    <div class="hp-skel-wrap" id="skelArtikel" aria-hidden="true">
      <div style="display:flex;gap:14px">
        <div class="hp-skel" style="flex:1.6;height:240px;border-radius:12px"></div>
        <div style="flex:1;display:flex;flex-direction:column;gap:10px">
          <div class="hp-skel" style="height:112px;border-radius:10px"></div>
          <div class="hp-skel" style="height:112px;border-radius:10px"></div>
        </div>
      </div>
    </div>

    <section id="secArtikel" style="display:none">
      <div class="artikel-layout">
        <div id="artikelSlider">
          <button class="slider-btn prev" aria-label="Sebelumnya">&#8249;</button>
          <button class="slider-btn next" aria-label="Berikutnya">&#8250;</button>
          <div class="slider-dots" id="sliderDots"></div>
          <div class="slider-progress" id="sliderProgress"></div>
        </div>
        <div class="artikel-side" id="artikelSide"></div>
      </div>
    </section>
  </div>

  <!-- QUICK NAVIGATION -->
  <section class="quick-nav-section" aria-label="Menu Cepat">
    <nav class="quick-nav">
      <a href="/agenda" class="quick-nav-card" aria-label="Lihat Agenda Paroki">
        <div class="quick-nav-icon" aria-hidden="true">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
            <line x1="8" y1="14" x2="8" y2="14" stroke-width="2.5"/><line x1="12" y1="14" x2="12" y2="14" stroke-width="2.5"/><line x1="12" y1="18" x2="12" y2="18" stroke-width="2.5"/>
          </svg>
        </div>
        <span class="quick-nav-label">Agenda</span>
      </a>
      <a href="/artikel/berita" class="quick-nav-card" aria-label="Baca Artikel Paroki">
        <div class="quick-nav-icon" aria-hidden="true">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
            <polyline points="14 2 14 8 20 8"/>
            <line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/>
          </svg>
        </div>
        <span class="quick-nav-label">Artikel</span>
      </a>
      <a href="/galeri" class="quick-nav-card" aria-label="Lihat Galeri Paroki">
        <div class="quick-nav-icon" aria-hidden="true">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/>
            <polyline points="21 15 16 10 5 21"/>
          </svg>
        </div>
        <span class="quick-nav-label">Galeri</span>
      </a>
    </nav>
  </section>

  <!-- EVENT BANNER -->
  <section class="event-banner-section">
    <a href="https://www.parokitulungagung.org/e-ticket/" target="_blank" rel="noopener" class="event-banner-link" aria-label="Beli Tiket Malam Kasih dan Ngopi Bareng bersama Romo Eko">
      <div class="event-banner-wrapper">
        <div class="event-banner-glow"></div>
        <div class="event-banner-info">
          <div class="event-badge">✦ Event Eksklusif</div>
          <h3 class="event-banner-title">Malam Kasih &amp; Ngopi Bareng<br><span>Bersama Romo Eko</span></h3>
          <p class="event-banner-subtitle">Untuk Pembangunan Griya Pastoral Umat<br>Paroki Tulungagung</p>
          <div class="event-banner-meta">
            <div class="event-meta-item">📅 <span>Jumat, 17 Juli 2026</span></div>
            <div class="event-meta-item">🕕 <span>17.00 – 21.00 WIB &nbsp;·&nbsp; Open Gate 16.00</span></div>
            <div class="event-meta-item">📍 <span>Crown Victoria Hotel, Tulungagung</span></div>
          </div>
          <div class="event-banner-tickets">
            <span>Rp 150.000</span><span>Rp 250.000</span><span>Rp 500.000</span><span>Rp 1.000.000</span>
          </div>
          <div class="event-banner-cta">
            <span class="event-cta-btn">🛒 &nbsp;Beli Tiket Sekarang</span>
          </div>
        </div>
        <div class="event-banner-flyer">
          <picture>
            <source type="image/webp" srcset="/img/event/ngopi-bareng-romo-eko-sm.webp 1x, /img/event/ngopi-bareng-romo-eko-md.webp 2x">
            <img src="/img/event/ngopi-bareng-romo-eko.jpg" alt="Malam Kasih dan Ngopi Bareng bersama Romo Eko" width="130" height="200" loading="eager" fetchpriority="high" decoding="async">
          </picture>
        </div>
      </div>
    </a>
  </section>

  <!-- ROMO PAROKI — Skeleton -->
  <div class="hp-skel-wrap" id="skelRomo" aria-hidden="true">
    <div class="content-section" style="padding-bottom:28px">
      <div style="display:flex;flex-direction:column;align-items:center;gap:12px;margin-bottom:28px">
        <div class="hp-skel" style="height:12px;width:80px"></div>
        <div class="hp-skel" style="height:20px;width:160px"></div>
      </div>
      <div style="display:flex;gap:20px;justify-content:center">
        <div style="display:flex;flex-direction:column;align-items:center;gap:10px">
          <div class="hp-skel" style="width:82px;height:82px;border-radius:50%"></div>
          <div class="hp-skel" style="height:12px;width:90px"></div>
          <div class="hp-skel" style="height:12px;width:120px"></div>
        </div>
        <div style="display:flex;flex-direction:column;align-items:center;gap:10px">
          <div class="hp-skel" style="width:82px;height:82px;border-radius:50%"></div>
          <div class="hp-skel" style="height:12px;width:90px"></div>
          <div class="hp-skel" style="height:12px;width:120px"></div>
        </div>
      </div>
    </div>
  </div>

  <!-- ROMO PAROKI — Content -->
  <div class="content-section romo-modern" id="secRomo" style="display:none; padding-bottom: 32px;">
    <div class="section-heading">
      <div class="section-heading__ornament" aria-hidden="true">
        <span></span>
        <svg width="9" height="9" viewBox="0 0 9 9" fill="currentColor">
          <path d="M4.5 0L5.5 3.5L9 4.5L5.5 5.5L4.5 9L3.5 5.5L0 4.5L3.5 3.5Z"/>
        </svg>
        <span></span>
      </div>
      <h2>Romo Paroki</h2>
      <p class="section-heading__sub">Gembala Umat Tulungagung</p>
    </div>

    <div class="romo-wrapper">
      <div class="romo-card modern">
        <div class="romo-card-top"></div>
        <div class="romo-img-wrap">
          <div class="romo-img-inner">
            <img src="/img/person/RD-Thomas-Aquino-Djoko-Noegroho.webp"
                 alt="RD Thomas Aquino Djoko Noegroho" width="82" height="82" loading="lazy" decoding="async">
          </div>
        </div>
        <div class="romo-card-body">
          <span class="romo-role">Romo Paroki</span>
          <p class="romo-name">RD Thomas Aquino<br>Djoko Noegroho</p>
          <div class="romo-ornament"><span></span><i></i><span></span></div>
        </div>
      </div>

      <div class="romo-card modern">
        <div class="romo-card-top"></div>
        <div class="romo-img-wrap">
          <div class="romo-img-inner">
            <img src="/img/person/RD-Yohanes-Setiawan.webp"
                 alt="RD Yohanes Setiawan" width="82" height="82" loading="lazy" decoding="async">
          </div>
        </div>
        <div class="romo-card-body">
          <span class="romo-role">Romo Rekan</span>
          <p class="romo-name">RD Yohanes<br>Setiawan</p>
          <div class="romo-ornament"><span></span><i></i><span></span></div>
        </div>
      </div>
    </div>
  </div>

</div><!-- /container-main -->
</main>

<!-- PHOTO MODAL -->
<div id="boxModal" class="modal" style="display:none">
  <div class="photo-modal-content">
    <div class="photo-modal-hero">
      <img id="boxModalImage" src="" alt="">
      <div class="photo-modal-overlay"></div>
      <button class="photo-modal-close" id="boxModalClose" aria-label="Tutup">&#215;</button>
    </div>
    <div class="photo-modal-info">
      <div class="photo-modal-badge" id="boxModalTitle"></div>
      <p class="photo-modal-name" id="boxModalText"></p>
      <p class="photo-modal-sub" id="boxModalSubText"></p>
    </div>
  </div>
</div>

</div><!-- /outer-wrapper -->

<!-- ================================================================
     FOOTER MODALS
     ================================================================ -->
<div class="ft-modal-backdrop" id="footerModalTeam" onclick="ftModalClose('footerModalTeam', event)">
  <div class="ft-modal-card">
    <div class="ft-modal-header">
      <button class="ft-modal-close" onclick="document.getElementById('footerModalTeam').classList.remove('open')" aria-label="Tutup">&#215;</button>
      <img class="ft-modal-logo" src="/img/smdtba-sharelogo.webp" alt="SMDTBA Logo" width="192" height="192">
      <p class="ft-modal-title">Team Web SMDTBA</p>
      <p class="ft-modal-subtitle">Paroki Tulungagung</p>
    </div>
    <div class="ft-modal-body">
      <span class="ft-modal-label">Kontak &amp; Dukungan</span>
      <p class="ft-modal-email">support@parokitulungagung.org</p>
      <p class="ft-modal-dev">Dikembangkan oleh <a href="/redirect.php" target="_blank">Tenaga IT Paroki Tulungagung</a></p>
    </div>
  </div>
</div>

<div class="ft-modal-backdrop" id="footerModalJadwal" onclick="ftModalClose('footerModalJadwal', event)">
  <div class="ft-modal-card ft-modal-card--wide">
    <div class="ft-modal-header">
      <button class="ft-modal-close" onclick="document.getElementById('footerModalJadwal').classList.remove('open')" aria-label="Tutup">&#215;</button>
      <div style="width:40px;height:40px;border:1px solid rgba(218,175,90,0.25);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;background:rgba(218,175,90,0.06);">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--ft-gold-mid)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
      </div>
      <p class="ft-modal-title">Jadwal Misa Lengkap</p>
      <p class="ft-modal-subtitle">Paroki Tulungagung</p>
    </div>
    <div class="ft-modal-body">
      <span class="ft-modal-label">Misa Paroki</span>
      <div class="ft-modal-jadwal-item"><span class="ft-modal-jadwal-hari">Senin &ndash; Rabu</span><span class="ft-modal-jadwal-waktu">05.30</span></div>
      <div class="ft-modal-jadwal-item"><span class="ft-modal-jadwal-hari">Kamis <small style="font-size:11px;opacity:0.55">(Susteran)</small></span><span class="ft-modal-jadwal-waktu">05.30</span></div>
      <div class="ft-modal-jadwal-item"><span class="ft-modal-jadwal-hari">Jumat</span><span class="ft-modal-jadwal-waktu">18.00</span></div>
      <div class="ft-modal-jadwal-item"><span class="ft-modal-jadwal-hari">Sabtu</span><span class="ft-modal-jadwal-waktu">18.00</span></div>
      <div class="ft-modal-jadwal-item"><span class="ft-modal-jadwal-hari">Minggu</span><span class="ft-modal-jadwal-waktu">07.00</span></div>
      <div class="ft-modal-divider"></div>
      <span class="ft-modal-label">Misa Stasi</span>
      <div class="ft-modal-stasi-item"><span class="ft-modal-stasi-nama">Stasi Gembala yang Baik Ngunut</span><span class="ft-modal-stasi-waktu">Sabtu 18.00</span></div>
      <div class="ft-modal-stasi-item"><span class="ft-modal-stasi-nama">Stasi St. Maria Rejotangan</span><span class="ft-modal-stasi-waktu">Sabtu 16.00</span></div>
      <div class="ft-modal-stasi-item"><span class="ft-modal-stasi-nama">Stasi St. Maria Trenggalek</span><span class="ft-modal-stasi-waktu">Minggu 08.00</span></div>
      <div class="ft-modal-stasi-item"><span class="ft-modal-stasi-nama">Stasi Kalangbret</span><span class="ft-modal-stasi-waktu">Minggu I &amp; II 10.00</span></div>
      <div class="ft-modal-stasi-item"><span class="ft-modal-stasi-nama">Stasi St. Maria Dongko</span><span class="ft-modal-stasi-waktu">Minggu I &amp; II 11.00</span></div>
      <div class="ft-modal-stasi-item"><span class="ft-modal-stasi-nama">Stasi Sendang</span><span class="ft-modal-stasi-waktu">Minggu II 11.00</span></div>
      <div class="ft-modal-note">
        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        Jadwal dapat berubah pada hari raya &amp; liturgi khusus
      </div>
    </div>
  </div>
</div>

<!-- ================================================================
     FOOTER
     ================================================================ -->
<footer class="ft-wrap">
  <div class="ft-ambient" aria-hidden="true"></div>
  <div class="ft-topline" aria-hidden="true"></div>
  <div class="ft-header">
    <div class="ft-header-identity">
      <span class="ft-header-cross" aria-hidden="true">
        <svg width="11" height="16" viewBox="0 0 11 16" fill="none">
          <rect x="4" y="0" width="3" height="16" rx="1.5" fill="currentColor"/>
          <rect x="0" y="4" width="11" height="3" rx="1.5" fill="currentColor"/>
        </svg>
      </span>
      <div class="ft-header-text">
        <h2 class="ft-header-name">Gereja Katolik Tulungagung</h2>
        <p class="ft-header-sub">Paroki Santa Maria Dengan Tidak Bernoda Asal &middot; Keuskupan Surabaya</p>
      </div>
    </div>
  </div>
  <div class="ft-grid">
    <div class="ft-col">
      <p class="ft-col-title">Navigasi</p>
      <ul class="ft-nav">
        <li><a href="/">Beranda</a></li>
        <li><a href="/tentang">Tentang</a></li>
        <li><a href="/agenda">Info &amp; Agenda</a></li>
        <li><a href="/jadwal-misa">Jadwal Misa</a></li>
        <li><a href="/galeri">Galeri Foto</a></li>
        <li><a href="/kontak">Kontak</a></li>
        <li><a href="/tvdigital">TV Digital</a></li>
        <li><a href="/babykeyboard">Baby Keyboard Fun</a></li>
        <li><a href="/kebijakan-privasi">Kebijakan Privasi</a></li>
        <li><a href="/kebijakan-cookie">Kebijakan Cookie</a></li>
      </ul>
    </div>
    <div class="ft-col">
      <p class="ft-col-title">Jadwal Misa</p>
      <div class="ft-jadwal-item"><span class="ft-jadwal-hari">Senin &ndash; Rabu</span><span class="ft-jadwal-waktu">05.30</span></div>
      <div class="ft-jadwal-item"><span class="ft-jadwal-hari">Kamis (Susteran)</span><span class="ft-jadwal-waktu">05.30</span></div>
      <div class="ft-jadwal-item"><span class="ft-jadwal-hari">Jumat</span><span class="ft-jadwal-waktu">18.00</span></div>
      <div class="ft-jadwal-item"><span class="ft-jadwal-hari">Sabtu</span><span class="ft-jadwal-waktu">18.00</span></div>
      <div class="ft-jadwal-item"><span class="ft-jadwal-hari">Minggu</span><span class="ft-jadwal-waktu">07.00</span></div>
      <a href="/jadwal-misa" class="ft-btn-ghost" aria-label="Lihat jadwal misa lengkap">
        <svg viewBox="0 0 24 24" aria-hidden="true"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
        Selengkapnya
      </a>
    </div>
    <div class="ft-col">
      <p class="ft-col-title">Kontak</p>
      <div class="ft-info-item">
        <svg class="ft-info-icon" viewBox="0 0 16 16" aria-hidden="true"><path d="M8 1.5C5.5 1.5 3.5 3.5 3.5 6c0 3.5 4.5 8.5 4.5 8.5S12.5 9.5 12.5 6c0-2.5-2-4.5-4.5-4.5z"/><circle cx="8" cy="6" r="1.5" fill="var(--ft-gold-mid)" stroke="none"/></svg>
        <span class="ft-info-text">Jl. Ahmad Yani Tim. Gg. IV No.1, Bago, Tulungagung</span>
      </div>
      <div class="ft-info-item">
        <svg class="ft-info-icon" viewBox="0 0 16 16" aria-hidden="true"><path d="M3 3h2l1 3-1.5 1.5a9 9 0 003 3L9 9l3 1v2a1 1 0 01-1 1C6 13 3 7 3 4a1 1 0 010-1z"/></svg>
        <span class="ft-info-text">(0355) 321727</span>
      </div>
      <div class="ft-info-item">
        <svg class="ft-info-icon" viewBox="0 0 16 16" aria-hidden="true"><circle cx="8" cy="8" r="6"/><path d="M8 4v4l2.5 2.5" stroke-linecap="round"/></svg>
        <span class="ft-info-text">Misa Harian &amp; Mingguan</span>
      </div>
      <div class="ft-col-sep"></div>
      <button class="ft-btn-ghost" onclick="document.getElementById('footerModalTeam').classList.add('open')">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        Team Web SMDTBA
      </button>
    </div>
  </div>
  <div class="ft-bottom">
    <span class="ft-copy">&copy; <?php echo date('Y'); ?> Paroki Santa Maria Dengan Tidak Bernoda Asal Tulungagung</span>
    <div class="ft-social">
      <a href="https://www.facebook.com/SantaMariaDTBA" class="ft-social-btn" target="_blank" rel="noopener" title="Facebook" aria-label="Facebook">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z"/></svg>
      </a>
      <a href="https://www.instagram.com/komsosparokitulungagung/" class="ft-social-btn" target="_blank" rel="noopener" title="Instagram" aria-label="Instagram">
        <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="var(--ft-gold-mid)" stroke="none"/></svg>
      </a>
      <a href="https://www.youtube.com/@KomsosParokiTulungagung" class="ft-social-btn" target="_blank" rel="noopener" title="YouTube" aria-label="YouTube">
        <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="2" y="5" width="20" height="14" rx="3"/><path d="M10 9l6 3-6 3V9z" fill="var(--ft-gold-mid)" stroke="none"/></svg>
      </a>
    </div>
  </div>
</footer>

<?php
// ================================================================
// CHATBOT INCLUDE
// Pastikan path ke file chatbot/index.php benar sesuai
// struktur direktori server Anda.
// ================================================================
if (file_exists($chatbot_path)) {
    include $chatbot_path;
} else {
    // Fallback: coba path relatif langsung
    $alt_path = __DIR__ . '/chatbot/index.php';
    if (file_exists($alt_path)) {
        include $alt_path;
    }
    // Jika tidak ditemukan, chatbot diabaikan tanpa error fatal
}
?>

<!-- ================================================================
     JAVASCRIPT
     ================================================================ -->
<script>
/* ── LOADING SCREEN dismiss ───────────────────────────────────── */
(function(){
  var LS_KEY = 'pls_visited';
  var el = document.getElementById('paroki-loading-screen');
  var MIN_MS = 1800;
  if(sessionStorage.getItem(LS_KEY)){if(el)el.style.display='none';return;}
  sessionStorage.setItem(LS_KEY,'1');
  var startTime=Date.now();
  function dismiss(){if(!el)return;el.classList.add('pls--hidden');setTimeout(function(){if(el&&el.parentNode)el.parentNode.removeChild(el);},1100);}
  function onReady(){var elapsed=Date.now()-startTime;var remain=Math.max(0,MIN_MS-elapsed);setTimeout(dismiss,remain);}
  if(document.readyState==='complete'){onReady();}else{window.addEventListener('load',onReady,{once:true});setTimeout(dismiss,7000);}
})();

/* ── MENU TOGGLE ──────────────────────────────────────────────── */
window.togglemenudiv = function(){
  var div=document.getElementById('divmenu');
  if(!div)return;
  div.style.display=(div.style.display==='none'||div.style.display==='')?'block':'none';
};
var btnMenu=document.getElementById('btnmenu');
if(btnMenu)btnMenu.addEventListener('click',window.togglemenudiv);

/* ── PHOTO MODAL ──────────────────────────────────────────────── */
window.ShowPhotoBox=function(txt,fotopath,title,subtxt){
  var set=function(id,val){var el=document.getElementById(id);if(el)el.innerHTML=val||'';};
  set('boxModalTitle',title);set('boxModalText',txt);set('boxModalSubText',subtxt);
  var img=document.getElementById('boxModalImage');
  var modal=document.getElementById('boxModal');
  if(img)img.src=fotopath;
  if(modal)modal.style.display='block';
};

/* ── MODAL CLOSE ──────────────────────────────────────────────── */
var boxModal=document.getElementById('boxModal');
var boxClose=document.getElementById('boxModalClose');
if(boxClose&&boxModal)boxClose.addEventListener('click',function(){boxModal.style.display='none';});
window.addEventListener('click',function(e){if(e.target===boxModal)boxModal.style.display='none';});

/* ── FOOTER MODAL ─────────────────────────────────────────────── */
function ftModalClose(id,e){if(e.target===document.getElementById(id))document.getElementById(id).classList.remove('open');}
function footerModalClose(id,e){ftModalClose(id,e);}
function ShowAboutBox(){document.getElementById('footerModalTeam').classList.add('open');}
document.addEventListener('keydown',function(e){
  if(e.key==='Escape'){document.querySelectorAll('.ft-modal-backdrop.open').forEach(function(el){el.classList.remove('open');});}
});

/* ── NAV PROGRESS BAR ─────────────────────────────────────────── */
(function(){
  var NAV_BAR_ID='nav-progress-bar';
  var NAV_WRAP_ID='nav-progress-wrap';
  function createNavBar(){
    if(document.getElementById(NAV_BAR_ID))return;
    var wrap=document.createElement('div');
    wrap.id=NAV_WRAP_ID;
    wrap.style.cssText='position:fixed;top:0;left:0;right:0;z-index:9999;height:3px;pointer-events:none;opacity:0;transition:opacity 0.2s ease';
    var bar=document.createElement('div');
    bar.id=NAV_BAR_ID;
    bar.style.cssText='height:100%;width:0%;background:linear-gradient(90deg,#c9a23a,#f0d060);box-shadow:0 0 8px rgba(201,162,58,0.6);border-radius:2px;transition:width 0.3s ease';
    wrap.appendChild(bar);document.body.appendChild(wrap);
  }
  function startNavProgress(){
    createNavBar();
    var wrap=document.getElementById(NAV_WRAP_ID);var bar=document.getElementById(NAV_BAR_ID);
    if(!wrap||!bar)return;
    wrap.style.opacity='1';bar.style.width='0%';
    setTimeout(function(){bar.style.width='70%';},50);
    setTimeout(function(){bar.style.width='85%';},800);
  }
  document.addEventListener('click',function(e){
    var link=e.target.closest('a[href]');
    if(!link)return;
    var href=link.getAttribute('href');
    if(!href||href.startsWith('#')||href.startsWith('http')||href.startsWith('mailto')||link.target==='_blank')return;
    startNavProgress();
  });
})();

/* ── HERO BACKGROUND ROTATOR ──────────────────────────────────── */
(function(){
  var hero=document.getElementById('siteHero');
  if(!hero)return;
  if(window.innerWidth<=600)return;
  var swapped=false,heroVisible=true,heroTimer=null;
  function rotate(){
    swapped=!swapped;
    if(swapped)hero.classList.add('hero-swap');
    else hero.classList.remove('hero-swap');
  }
  function shouldRun(){return heroVisible&&!document.hidden;}
  function start(){clearInterval(heroTimer);if(!shouldRun())return;heroTimer=setInterval(rotate,6000);}
  function stop(){clearInterval(heroTimer);}
  document.addEventListener('visibilitychange',function(){document.hidden?stop():start();});
  if('IntersectionObserver' in window){
    var io=new IntersectionObserver(function(entries){heroVisible=entries[0].isIntersecting;heroVisible?start():stop();},{threshold:0.1});
    io.observe(hero);
  }
  start();
})();

/* ── REVEAL SECTIONS ──────────────────────────────────────────── */
(function(){
  function reveal(skelId,secId){
    var skel=document.getElementById(skelId);var sec=document.getElementById(secId);
    if(!sec)return;
    sec.style.display='';
    if(skel)skel.classList.add('hp-hidden');
  }
  function observeReveal(skelId,secId){
    var skel=document.getElementById(skelId);
    if(!skel||!('IntersectionObserver' in window)){reveal(skelId,secId);return;}
    var io=new IntersectionObserver(function(entries,observer){
      entries.forEach(function(e){if(e.isIntersecting){reveal(skelId,secId);observer.disconnect();}});
    },{rootMargin:'120px 0px'});
    io.observe(skel);
  }
  function runIdle(fn){
    if('requestIdleCallback' in window){requestIdleCallback(fn,{timeout:800});}else{setTimeout(fn,80);}
  }
  runIdle(function(){observeReveal('skelRomo','secRomo');});
})();

/* ── ARTIKEL SLIDER DINAMIS (Supabase) ────────────────────────── */
(function(){
  var SB_URL='<?= htmlspecialchars($_sb_url,  ENT_QUOTES) ?>';
  var SB_KEY='<?= htmlspecialchars($_sb_anon, ENT_QUOTES) ?>';
  var FALLBACK_IMG='/img/parokitulungagung.png';

  function esc(s){return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}
  function shuffle(a){
    for(var i=a.length-1;i>0;i--){var j=Math.floor(Math.random()*(i+1));var t=a[i];a[i]=a[j];a[j]=t;}
    return a;
  }

  function renderArtikel(data){
    var sliderEl=document.getElementById('artikelSlider');
    var sideEl=document.getElementById('artikelSide');
    var dotsEl=document.getElementById('sliderDots');
    if(!sliderEl||!data.length)return;

    var top5=shuffle(data.slice(0,Math.min(5,data.length)));
    var sliderSlugs={};
    top5.forEach(function(a){sliderSlugs[a.slug]=1;});
    var allShuffled=shuffle(data.slice(0));
    var featured=allShuffled.filter(function(a){return !sliderSlugs[a.slug];}).slice(0,2);
    if(featured.length<2)featured=allShuffled.slice(0,2);

    var slidesHtml='';
    top5.forEach(function(a,i){
      var href='/artikel/'+esc(a.menu)+'/'+esc(a.slug);
      var src=esc(a.thumbnail||FALLBACK_IMG);
      var alt=esc(a.judul||'Artikel');
      var loading=i===0?'eager':'lazy';
      var fp=i===0?' fetchpriority="high"':'';
      slidesHtml+='<a href="'+href+'" class="artikel-slide'+(i===0?' active':'')+'" tabindex="'+(i===0?'0':'-1')+'">';
      slidesHtml+='<img src="'+src+'" alt="'+alt+'" width="600" height="450" loading="'+loading+'"'+fp+' decoding="async">';
      slidesHtml+='<div class="artikel-overlay"><h3>'+esc(a.judul)+'</h3></div>';
      slidesHtml+='</a>';
    });

    var dotsHtml='';
    top5.forEach(function(_,i){dotsHtml+='<span class="dot'+(i===0?' active':'')+'" data-idx="'+i+'"></span>';});

    var sideHtml='';
    featured.forEach(function(a){
      var href='/artikel/'+esc(a.menu)+'/'+esc(a.slug);
      var src=esc(a.thumbnail||FALLBACK_IMG);
      var alt=esc(a.judul||'Artikel');
      sideHtml+='<a href="'+href+'" class="artikel-small">';
      sideHtml+='<img src="'+src+'" alt="'+alt+'" loading="lazy" decoding="async">';
      sideHtml+='<div class="artikel-small-content"><p>'+esc(a.judul)+'</p></div>';
      sideHtml+='</a>';
    });

    var prevBtn=sliderEl.querySelector('.slider-btn.prev');
    var tmp=document.createElement('div');
    tmp.innerHTML=slidesHtml;
    while(tmp.firstChild){sliderEl.insertBefore(tmp.firstChild,prevBtn);}

    if(dotsEl)dotsEl.innerHTML=dotsHtml;
    if(sideEl)sideEl.innerHTML=sideHtml;

    var sec=document.getElementById('secArtikel');
    var skel=document.getElementById('skelArtikel');
    if(sec)sec.style.display='';
    if(skel)skel.classList.add('hp-hidden');

    initArtikelSlider();
  }

  function initArtikelSlider(){
    var slider=document.getElementById('artikelSlider');
    if(!slider)return;
    var slides=slider.querySelectorAll('.artikel-slide');
    var dots=slider.parentNode.querySelectorAll('.dot');
    var btnPrev=slider.querySelector('.slider-btn.prev');
    var btnNext=slider.querySelector('.slider-btn.next');
    var progress=document.getElementById('sliderProgress');
    if(!slides.length)return;
    var DURATION=5000,current=0,timer=null,sliderVisible=true,hovered=false;

    function show(i){
      slides[current].style.willChange='auto';
      slides.forEach(function(s,x){s.classList.toggle('active',x===i);s.tabIndex=x===i?0:-1;});
      dots.forEach(function(d,x){d.classList.toggle('active',x===i);});
      current=i;
      slides[current].style.willChange='opacity';
      startProg();
    }
    function shouldRun(){return sliderVisible&&!document.hidden&&!hovered;}
    function startAutoplay(){clearInterval(timer);if(!shouldRun())return;timer=setInterval(function(){show((current+1)%slides.length);},DURATION);}
    function stopAutoplay(){clearInterval(timer);}
    function startProg(){
      if(!progress)return;
      progress.style.transition='none';progress.style.width='0%';
      progress.offsetWidth;
      progress.style.transition='width '+DURATION+'ms linear';
      progress.style.width='100%';
    }
    if(btnPrev)btnPrev.addEventListener('click',function(){show((current-1+slides.length)%slides.length);startAutoplay();});
    if(btnNext)btnNext.addEventListener('click',function(){show((current+1)%slides.length);startAutoplay();});
    dots.forEach(function(d,x){d.addEventListener('click',function(){show(x);startAutoplay();});});
    var touchX=0;
    slider.addEventListener('touchstart',function(e){touchX=e.changedTouches[0].clientX;},{passive:true});
    slider.addEventListener('touchend',function(e){
      var dx=e.changedTouches[0].clientX-touchX;
      if(Math.abs(dx)<40)return;
      show(dx<0?(current+1)%slides.length:(current-1+slides.length)%slides.length);
      startAutoplay();
    },{passive:true});
    slider.addEventListener('mouseenter',function(){hovered=true;stopAutoplay();});
    slider.addEventListener('mouseleave',function(){hovered=false;startAutoplay();});
    slider.addEventListener('keydown',function(e){
      if(e.key==='ArrowRight'){show((current+1)%slides.length);startAutoplay();}
      if(e.key==='ArrowLeft'){show((current-1+slides.length)%slides.length);startAutoplay();}
    });
    document.addEventListener('visibilitychange',function(){document.hidden?stopAutoplay():startAutoplay();});
    if('IntersectionObserver' in window){
      var io2=new IntersectionObserver(function(entries){
        sliderVisible=entries[0].isIntersecting;sliderVisible?startAutoplay():stopAutoplay();
      },{threshold:0.25});
      io2.observe(slider);
    }
    show(0);startAutoplay();
  }

  function loadArtikelDinamis(){
    var url=SB_URL+'/rest/v1/articles'
      +'?select=judul,slug,thumbnail,menu'
      +'&status=eq.published'
      +'&order=published_at.desc'
      +'&limit=100';

    fetch(url,{
      headers:{'apikey':SB_KEY,'Authorization':'Bearer '+SB_KEY},
      cache:'no-store'
    })
    .then(function(r){if(!r.ok)throw new Error(r.status);return r.json();})
    .then(function(data){
      if(Array.isArray(data)&&data.length>0){renderArtikel(data);}
      else{hideSkel();}
    })
    .catch(function(){hideSkel();});
  }

  function hideSkel(){
    var skel=document.getElementById('skelArtikel');
    if(skel)skel.classList.add('hp-hidden');
  }

  loadArtikelDinamis();
})();
</script>

</body>
</html>