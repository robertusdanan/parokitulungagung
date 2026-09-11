<?php
/**
 * pages/stories.php
 * ─────────────────────────────────────────────────────────────────────────
 * Halaman Publik "Stories" Paroki SMDTBA Tulungagung.
 * Menampilkan Carousel Reels Instagram di bagian atas dan galeri visual
 * 21 media (foto & video) di bagian bawah secara dinamis dan responsif.
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/StoriesManager.php';

$stories = StoriesManager::getStories();
$totalStories = count($stories);

$seo = [
    'title'       => 'Stories · Paroki Santa Maria Dengan Tidak Bernoda Asal Tulungagung',
    'description' => 'Stories visual dokumentasi foto dan video seputar kegiatan paroki, misa, sakramen, dan dinamika umat Paroki SMDTBA Tulungagung.',
    'canonical'   => 'https://www.parokitulungagung.org/stories',
    'keywords'    => 'stories paroki tulungagung, reels komsos tulungagung, video gereja katolik tulungagung, foto cerita paroki smdtba',
    'type'        => 'website',
    'image'       => !empty($stories[0]['url']) ? $stories[0]['url'] : assetUrl('og-preview.webp'),
];

$breadcrumbs = [
    ['name' => 'Beranda', 'url' => 'https://www.parokitulungagung.org'],
    ['name' => 'Stories', 'url' => 'https://www.parokitulungagung.org/stories'],
];

$extraCss = ['/css/content.css'];
?>
<!doctype html>
<html lang="id">
<head>
  <?php include __DIR__ . '/../components/seo_head.php'; ?>
  <style>
  /* ── Gaya Khusus Instagram Carousel (Sesuai Homepage) ── */
  .instagram-modern { padding-bottom: 24px; }
  .ig-home-card { width: 100%; }
  .ig-home-frame {
    --ig-ref-width: 880px;
    --ig-frame-h: 300px;
    --ig-shift: 210px;
    position: relative;
    overflow: hidden;
    border-radius: var(--r-md);
    height: var(--ig-frame-h);
  }
  .ig-home-card-body {
    position: absolute;
    top: 0;
    left: 0;
    width: var(--ig-ref-width);
    height: calc(var(--ig-frame-h) + var(--ig-shift) + 40px);
    transform-origin: top left;
    transform: translateY(calc(-1 * var(--ig-shift)));
  }
  .ig-home-card-body,
  .ig-home-card-body .tagembed-widget,
  .ig-home-card-body .tagembed-widget * { background: transparent !important; }
  .ig-home-card-body .tagembed-widget { overflow: hidden !important; }

  .ig-home-follow-wrap { display: flex; justify-content: center; margin-top: 18px; }
  .ig-home-follow {
    display: inline-flex; align-items: center; gap: 9px;
    padding: 11px 26px;
    border-radius: 999px;
    background: #fff;
    border: 1px solid rgba(201,162,58,.35);
    box-shadow: var(--shadow-sm);
    color: var(--primary-brown);
    font-family: var(--font-cinzel);
    font-size: .6rem; font-weight: 500;
    letter-spacing: .16em; text-transform: uppercase;
    text-decoration: none;
    transition: all var(--ease-base);
  }
  .ig-home-follow svg { flex-shrink: 0; }
  .ig-home-follow:hover {
    color: #fff;
    border-color: transparent;
    background: linear-gradient(135deg, #d6249f, var(--primary-gold));
    box-shadow: var(--shadow-md);
    transform: translateY(-2px);
  }

  /* ── Bagian Stories Media Grid ── */
  .stories-section {
    padding: 30px 0 50px;
  }
  .stories-container {
    max-width: 1180px;
    margin: 0 auto;
    padding: 0 16px;
  }
  .stories-feed-wrap {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 14px;
    margin-top: 24px;
  }

  /* Kartu Media Story: proporsi tegak (9:16) khas Stories / Reels */
  .story-feed-item {
    position: relative;
    width: calc((100% - 4 * 14px) / 5); /* 5 kolom di Desktop */
    aspect-ratio: 9 / 15;
    border-radius: 14px;
    overflow: hidden;
    background: #181614;
    box-shadow: 0 4px 16px rgba(0,0,0,0.08);
    cursor: pointer;
    transition: transform .28s cubic-bezier(0.2, 0.8, 0.2, 1), box-shadow .28s ease;
    border: 1px solid rgba(201, 162, 58, 0.18);
  }
  .story-feed-item:hover {
    transform: translateY(-4px) scale(1.02);
    box-shadow: 0 10px 24px rgba(201, 162, 58, 0.22);
    border-color: rgba(201, 162, 58, 0.45);
  }

  /* Di Desktop: jika 21 media, baris ke-5 (item ke-21) disembunyikan agar pas 5x4 = 20 item */
  @media (min-width: 769px) {
    .story-feed-item:nth-child(n+21) {
      display: none;
    }
  }

  /* Di Mobile: 3 kolom x 7 baris = 21 item pas */
  @media (max-width: 768px) {
    .story-feed-item {
      width: calc((100% - 2 * 10px) / 3);
      aspect-ratio: 9 / 15;
      gap: 10px;
      border-radius: 10px;
    }
    .stories-feed-wrap {
      gap: 10px;
    }
  }

  .story-media-cover {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    transition: transform .4s ease;
  }
  .story-feed-item:hover .story-media-cover {
    transform: scale(1.05);
  }

  /* Badge Video / Play Overlay */
  .story-play-pill {
    position: absolute;
    top: 10px;
    right: 10px;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(6px);
    color: #fff;
    padding: 3px 8px;
    border-radius: 999px;
    font-size: 10px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 4px;
    border: 0.5px solid rgba(255,255,255,0.25);
    z-index: 2;
  }

  /* Gradient Overlay Bawah + Judul / Nama */
  .story-caption-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(180deg, rgba(0,0,0,0) 45%, rgba(0,0,0,0.4) 70%, rgba(0,0,0,0.85) 100%);
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
    padding: 12px;
    pointer-events: none;
    z-index: 2;
  }
  .story-overlay-title {
    color: #fff;
    font-size: 12px;
    font-weight: 600;
    line-height: 1.35;
    text-shadow: 0 1px 3px rgba(0,0,0,0.8);
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }

  /* ── MODAL CARD LIGHTBOX STORIES ── */
  .story-modal {
    position: fixed;
    inset: 0;
    background: rgba(8, 8, 8, 0.88);
    backdrop-filter: blur(14px);
    z-index: 99999;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    visibility: hidden;
    transition: opacity .25s ease, visibility .25s ease;
    padding: 20px;
  }
  .story-modal.active {
    opacity: 1;
    visibility: visible;
  }
  .story-modal-card {
    background: #141312;
    border: 1px solid rgba(201, 162, 58, 0.3);
    border-radius: 18px;
    width: 100%;
    max-width: 440px;
    max-height: 92vh;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    box-shadow: 0 20px 50px rgba(0,0,0,0.6);
    position: relative;
    animation: storyModalSlide .3s cubic-bezier(0.16, 1, 0.3, 1);
  }
  @keyframes storyModalSlide {
    from { opacity: 0; transform: translateY(20px) scale(0.96); }
    to   { opacity: 1; transform: translateY(0) scale(1); }
  }

  .story-modal-media-wrap {
    width: 100%;
    background: #000;
    aspect-ratio: 9 / 14;
    max-height: 60vh;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    border-top-left-radius: 17px;
    border-top-right-radius: 17px;
    overflow: hidden;
  }
  .story-modal-media-wrap img, .story-modal-media-wrap video {
    width: 100%;
    height: 100%;
    object-fit: contain;
    background: #000;
  }
  .story-modal-content {
    padding: 18px 20px 22px;
    color: #e5e5e5;
  }
  .story-modal-title {
    font-size: 15px;
    font-weight: 600;
    color: #fff;
    margin-bottom: 6px;
    line-height: 1.4;
  }
  .story-modal-desc {
    font-size: 13px;
    color: #a3a3a3;
    line-height: 1.6;
    white-space: pre-line;
  }
  .story-modal-close {
    position: absolute;
    top: 14px;
    right: 14px;
    width: 34px;
    height: 34px;
    border-radius: 50%;
    background: rgba(0, 0, 0, 0.65);
    border: 1px solid rgba(255,255,255,0.25);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 16px;
    z-index: 10;
    transition: all .15s ease;
  }
  .story-modal-close:hover {
    background: rgba(201, 162, 58, 0.85);
    transform: scale(1.08);
  }

  /* Navigasi Kiri & Kanan */
  .story-nav-btn {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: rgba(20, 20, 20, 0.7);
    border: 1px solid rgba(255,255,255,0.2);
    color: #fff;
    font-size: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    z-index: 10;
    transition: all .2s;
  }
  .story-nav-btn:hover {
    background: rgba(201, 162, 58, 0.9);
    border-color: transparent;
  }
  .story-nav-prev { left: -60px; }
  .story-nav-next { right: -60px; }

  @media (max-width: 600px) {
    .story-nav-prev { left: 10px; }
    .story-nav-next { right: 10px; }
    .story-nav-btn {
      width: 36px;
      height: 36px;
      font-size: 16px;
      background: rgba(0,0,0,0.5);
    }
    .story-modal-card {
      max-height: 94vh;
      border-radius: 14px;
    }
  }
  </style>
</head>
<body>

<?php $headerTitle = 'Stories'; include __DIR__ . '/../components/page_header.php'; ?>

<div class="container-main">

  <!-- ═══════════════════════════════════════════════════════════════════
       BAGIAN ATAS: CAROUSEL FEED INSTAGRAM (Persis seperti Homepage)
       ═══════════════════════════════════════════════════════════════════ -->
  <div class="content-section reveal-up instagram-modern" style="margin-top: 20px;">
    <div class="section-heading">
      <div class="section-heading__ornament" aria-hidden="true">
        <span></span>
        <svg width="9" height="9" viewBox="0 0 9 9" fill="currentColor">
          <path d="M4.5 0L5.5 3.5L9 4.5L5.5 5.5L4.5 9L3.5 5.5L0 4.5L3.5 3.5Z"/>
        </svg>
        <span></span>
      </div>
      <h2>Reels</h2>
      <p class="section-heading__sub">@komsosparokitulungagung</p>
    </div>

    <div class="ig-home-card">
      <div class="ig-home-frame" id="igHomeFrame">
        <div class="ig-home-card-body" id="igHomeBody">
          <div class="tagembed-widget" style="width:100%;height:100%;overflow:auto;" data-widget-id="334168" data-website="1"></div>
          <script src="https://widget.tagembed.com/embed.min.js" type="text/javascript" async></script>
        </div>
      </div>
      <div class="ig-home-follow-wrap">
        <a href="https://www.instagram.com/komsosparokitulungagung/" class="ig-home-follow" target="_blank" rel="noopener">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
            <rect x="3" y="3" width="18" height="18" rx="5"/>
            <circle cx="12" cy="12" r="4"/>
            <circle cx="17.2" cy="6.8" r="1" fill="currentColor" stroke="none"/>
          </svg>
          Ikuti di Instagram
        </a>
      </div>
    </div>
  </div>

  <!-- ═══════════════════════════════════════════════════════════════════
       BAGIAN BAWAH: GRID VISUAL STORIES (FOTO & VIDEO)
       ═══════════════════════════════════════════════════════════════════ -->
  <div class="content-section reveal-up stories-section">
    <div class="section-heading">
      <div class="section-heading__ornament" aria-hidden="true">
        <span></span>
        <svg width="9" height="9" viewBox="0 0 9 9" fill="currentColor">
          <path d="M4.5 0L5.5 3.5L9 4.5L5.5 5.5L4.5 9L3.5 5.5L0 4.5L3.5 3.5Z"/>
        </svg>
        <span></span>
      </div>
      <h2>Momen Paroki</h2>
      <p class="section-heading__sub">Dokumentasi video &amp; foto pilihan kegiatan umat</p>
    </div>

    <?php if ($totalStories > 0): ?>
    <div class="stories-feed-wrap" id="storiesFeedWrap">
      <?php foreach ($stories as $idx => $st):
        $isVideo = ($st['type'] ?? '') === 'video';
        $thumb = $isVideo ? (!empty($st['poster_url']) ? $st['poster_url'] : $st['url']) : $st['url'];
        $title = !empty($st['file_name']) ? $st['file_name'] : 'Momen Paroki';
      ?>
      <div class="story-feed-item" onclick="openStoryModal(<?= (int)$idx ?>)" role="button" tabindex="0" aria-label="<?= htmlspecialchars($title, ENT_QUOTES) ?>">
        <img src="<?= htmlspecialchars($thumb, ENT_QUOTES) ?>" alt="<?= htmlspecialchars($title, ENT_QUOTES) ?>" class="story-media-cover" loading="lazy">
        <?php if ($isVideo): ?>
        <div class="story-play-pill">
          <svg width="10" height="10" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"/></svg>
          <span>Video</span>
        </div>
        <?php endif; ?>
        <div class="story-caption-overlay">
          <div class="story-overlay-title"><?= htmlspecialchars($title, ENT_QUOTES) ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div style="text-align:center;padding:50px 20px;color:var(--text-muted)">
      <p style="font-size:14px">Belum ada konten Stories saat ini.</p>
    </div>
    <?php endif; ?>
  </div>

</div>

<!-- ═══════════════════════════════════════════════════════════════════
     MODAL LIGHTBOX STORIES CARD
     ═══════════════════════════════════════════════════════════════════ -->
<div class="story-modal" id="storyModal" onclick="handleModalBackdropClick(event)">
  <button class="story-nav-btn story-nav-prev" onclick="prevStory(event)" aria-label="Sebelumnya">‹</button>
  <button class="story-nav-btn story-nav-next" onclick="nextStory(event)" aria-label="Berikutnya">›</button>

  <div class="story-modal-card" id="storyModalCard">
    <button class="story-modal-close" onclick="closeStoryModal()" title="Tutup">✕</button>
    <div class="story-modal-media-wrap" id="modalMediaContainer"></div>
    <div class="story-modal-content">
      <div class="story-modal-title" id="modalStoryTitle"></div>
      <div class="story-modal-desc" id="modalStoryDesc"></div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../components/footer.php'; ?>

<!-- ── JAVASCRIPT: INSTAGRAM AUTO-SCALE & STORIES LIGHTBOX ── -->
<script>
// Data Stories dari Server
const storiesList = <?= json_encode($stories, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
let currentStoryIdx = 0;

function openStoryModal(idx) {
  if (!storiesList || !storiesList[idx]) return;
  currentStoryIdx = idx;
  renderModalContent();
  const modal = document.getElementById('storyModal');
  modal.classList.add('active');
  document.body.style.overflow = 'hidden';
}

function closeStoryModal() {
  const modal = document.getElementById('storyModal');
  modal.classList.remove('active');
  const container = document.getElementById('modalMediaContainer');
  container.innerHTML = ''; // Hentikan video saat modal ditutup
  document.body.style.overflow = '';
}

function handleModalBackdropClick(e) {
  if (e.target.id === 'storyModal') {
    closeStoryModal();
  }
}

function renderModalContent() {
  const item = storiesList[currentStoryIdx];
  if (!item) return;

  const container = document.getElementById('modalMediaContainer');
  const titleEl   = document.getElementById('modalStoryTitle');
  const descEl    = document.getElementById('modalStoryDesc');

  titleEl.textContent = item.file_name || 'Momen Paroki';
  descEl.textContent  = item.description || '';
  descEl.style.display = item.description ? 'block' : 'none';

  container.innerHTML = '';
  if (item.type === 'video') {
    const video = document.createElement('video');
    video.src = item.url;
    video.controls = true;
    video.autoplay = true;
    video.playsInline = true;
    if (item.poster_url) video.poster = item.poster_url;
    video.style.width = '100%';
    video.style.height = '100%';
    video.style.objectFit = 'contain';
    container.appendChild(video);
  } else {
    const img = document.createElement('img');
    img.src = item.url;
    img.alt = item.file_name || 'Momen Paroki';
    img.style.width = '100%';
    img.style.height = '100%';
    img.style.objectFit = 'contain';
    container.appendChild(img);
  }
}

function prevStory(e) {
  if (e) e.stopPropagation();
  if (currentStoryIdx > 0) {
    currentStoryIdx--;
  } else {
    currentStoryIdx = storiesList.length - 1;
  }
  renderModalContent();
}

function nextStory(e) {
  if (e) e.stopPropagation();
  if (currentStoryIdx < storiesList.length - 1) {
    currentStoryIdx++;
  } else {
    currentStoryIdx = 0;
  }
  renderModalContent();
}

// Navigasi Keyboard (Panah Kiri/Kanan & Escape)
document.addEventListener('keydown', (e) => {
  const modal = document.getElementById('storyModal');
  if (!modal || !modal.classList.contains('active')) return;
  if (e.key === 'ArrowLeft') prevStory();
  if (e.key === 'ArrowRight') nextStory();
  if (e.key === 'Escape') closeStoryModal();
});

// ── Instagram Auto Scale (Persis Homepage) ──────────────────
(function(){
  var frame = document.getElementById('igHomeFrame');
  var body  = document.getElementById('igHomeBody');
  if(!frame || !body) return;

  function num(v, fallback){
    var n = parseFloat(v);
    return isNaN(n) ? fallback : n;
  }

  function applyScale(){
    var cs = getComputedStyle(frame);
    var refWidth = num(cs.getPropertyValue('--ig-ref-width'), 880);
    var frameH   = num(cs.getPropertyValue('--ig-frame-h'), 300);
    var shift    = num(cs.getPropertyValue('--ig-shift'), 210);
    var actualWidth = frame.clientWidth || refWidth;
    var scale = actualWidth / refWidth;

    frame.style.height = (frameH * scale) + 'px';
    body.style.transform = 'scale(' + scale + ') translateY(-' + shift + 'px)';
  }

  if('ResizeObserver' in window){
    new ResizeObserver(function(){ applyScale(); }).observe(frame);
  } else {
    window.addEventListener('resize', applyScale);
  }
  applyScale();
})();

// ── Scroll Reveal Animation ────────────────────────────────
(function(){
  var els = document.querySelectorAll('.reveal-up');
  if(!els.length) return;
  if(!('IntersectionObserver' in window)){
    els.forEach(function(el){ el.classList.add('is-visible'); });
    return;
  }
  var io = new IntersectionObserver(function(entries, observer){
    entries.forEach(function(entry){
      if(entry.isIntersecting){
        entry.target.classList.add('is-visible');
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.12 });
  els.forEach(function(el){ io.observe(el); });
})();
</script>

</body>
</html>
