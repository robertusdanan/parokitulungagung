<?php
/**
 * pages/stories.php
 * ─────────────────────────────────────────────────────────────────────────
 * Halaman Publik "Stories" Paroki SMDTBA Tulungagung.
 * Menampilkan galeri visual dinamis & elegan (maksimal 21 media foto & video).
 *
 * Layout Responsif:
 * - Desktop: 5 kolom × 4 baris (20 media tampil, item ke-21 tidak dipaksakan).
 * - Mobile: 3 kolom × 7 baris (21 media tampil penuh).
 * - Terpusat dinamis (justify-content: center) jika jumlah item tidak kelipatan baris.
 * - Modal card lightbox elegan dengan deskripsi multiline & navigasi keyboard/touch.
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/StoriesManager.php';

$stories = StoriesManager::getStories();
$totalStories = count($stories);

$seo = [
    'title'       => 'Stories · Paroki Santa Maria Dengan Tidak Bernoda Asal Tulungagung',
    'description' => 'Stories visual dokumentasi foto dan video seputar kegiatan paroki, misa, sakramen, dan dinamika umat Paroki SMDTBA Tulungagung.',
    'canonical'   => 'https://www.parokitulungagung.org/stories',
    'keywords'    => 'stories paroki tulungagung, cerita paroki smdtba, video gereja katolik tulungagung, foto kegiatan katolik tulungagung',
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
  /* ── Desain Stories Paroki Tulungagung ── */
  .stories-section-wrap {
    max-width: 1200px;
    margin: 0 auto 40px;
    padding: 0 4px;
  }

  .stories-stats-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
    padding: 12px 18px;
    background: #fff;
    border: 1px solid rgba(218, 175, 90, 0.25);
    border-radius: 14px;
    box-shadow: 0 2px 10px rgba(91, 44, 111, 0.04);
  }
  .stories-stats-title {
    font-size: 13px;
    font-weight: 600;
    color: var(--primary-brown, #4a2c11);
    display: flex;
    align-items: center;
    gap: 8px;
  }
  .stories-stats-badge {
    background: rgba(218, 175, 90, 0.15);
    color: #8c6819;
    font-size: 11px;
    font-weight: 700;
    padding: 3px 10px;
    border-radius: 999px;
    letter-spacing: .05em;
  }

  /* ── Grid Flex Terpusat ── */
  .stories-feed-wrap {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 16px;
  }

  /* Kartu Story: aspek rasio tegak 9:15 */
  .story-card {
    position: relative;
    width: calc((100% - 4 * 16px) / 5); /* 5 kolom di Desktop */
    aspect-ratio: 9 / 15;
    border-radius: 16px;
    overflow: hidden;
    background: #141217;
    border: 1px solid rgba(218, 175, 90, 0.25);
    box-shadow: 0 4px 18px rgba(0, 0, 0, 0.12);
    cursor: pointer;
    user-select: none;
    -webkit-tap-highlight-color: transparent;
    transition: transform .28s cubic-bezier(0.2, 0.8, 0.2, 1), box-shadow .28s ease, border-color .28s ease;
  }

  .story-card:hover {
    transform: translateY(-5px) scale(1.015);
    box-shadow: 0 12px 28px rgba(218, 175, 90, 0.2), 0 4px 14px rgba(0, 0, 0, 0.25);
    border-color: rgba(218, 175, 90, 0.65);
  }

  /* Desktop: batas 20 item jika ada 21 item (5x4 pas) */
  @media (min-width: 769px) {
    .story-card:nth-child(n+21) {
      display: none;
    }
  }

  /* Mobile: 3 kolom x 7 baris = 21 item pas */
  @media (max-width: 768px) {
    .stories-feed-wrap {
      gap: 10px;
    }
    .story-card {
      width: calc((100% - 2 * 10px) / 3);
      aspect-ratio: 9 / 15;
      border-radius: 12px;
    }
  }

  .story-cover-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    background: #18151f;
    transition: transform .4s ease;
  }
  .story-card:hover .story-cover-img {
    transform: scale(1.05);
  }

  /* Fallback jika gambar gagal dimuat */
  .story-card.is-fallback .story-cover-img {
    display: none;
  }
  .story-card.is-fallback {
    background: linear-gradient(145deg, #1b1722, #292133);
    display: flex;
    align-items: center;
    justify-content: center;
  }

  /* Badge Video */
  .story-video-pill {
    position: absolute;
    top: 10px;
    right: 10px;
    background: rgba(14, 11, 18, 0.72);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    color: #f5e6be;
    padding: 3px 9px;
    border-radius: 999px;
    font-size: 10px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    border: 1px solid rgba(218, 175, 90, 0.35);
    z-index: 2;
    box-shadow: 0 2px 8px rgba(0,0,0,0.3);
  }
  .story-video-pill svg {
    fill: currentColor;
  }

  /* Scrim Gradient & Judul */
  .story-scrim {
    position: absolute;
    inset: 0;
    background: linear-gradient(180deg, rgba(0,0,0,0) 45%, rgba(10,8,14,0.45) 70%, rgba(10,8,14,0.92) 100%);
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
    padding: 14px 12px;
    pointer-events: none;
    z-index: 2;
  }
  .story-caption-title {
    color: #fff;
    font-size: 12.5px;
    font-weight: 600;
    line-height: 1.35;
    text-shadow: 0 1px 3px rgba(0,0,0,0.85);
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }

  /* ── Modal Lightbox Stories ── */
  .story-lightbox {
    position: fixed;
    inset: 0;
    background: rgba(6, 4, 10, 0.9);
    backdrop-filter: blur(18px);
    -webkit-backdrop-filter: blur(18px);
    z-index: 999999;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    visibility: hidden;
    transition: opacity .25s ease, visibility .25s ease;
    padding: 16px;
  }
  .story-lightbox.active {
    opacity: 1;
    visibility: visible;
  }

  .story-dialog-card {
    background: #14111a;
    border: 1px solid rgba(218, 175, 90, 0.35);
    border-radius: 20px;
    width: 100%;
    max-width: 440px;
    max-height: 92vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    box-shadow: 0 24px 60px rgba(0, 0, 0, 0.7);
    position: relative;
    animation: storyDialogZoom .28s cubic-bezier(0.16, 1, 0.3, 1);
  }
  @keyframes storyDialogZoom {
    from { opacity: 0; transform: translateY(16px) scale(0.96); }
    to   { opacity: 1; transform: translateY(0) scale(1); }
  }

  .story-dialog-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px;
    background: rgba(20, 17, 26, 0.95);
    border-bottom: 1px solid rgba(218, 175, 90, 0.15);
    color: #e6dbbf;
    font-size: 12px;
    font-weight: 500;
  }
  .story-dialog-counter {
    font-family: var(--font-cinzel, serif);
    letter-spacing: .08em;
  }
  .story-dialog-close {
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(218, 175, 90, 0.3);
    color: #fff;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 14px;
    transition: all .15s;
  }
  .story-dialog-close:hover {
    background: rgba(218, 175, 90, 0.85);
    color: #14111a;
    transform: scale(1.08);
  }

  .story-dialog-media {
    width: 100%;
    background: #000;
    max-height: 56vh;
    aspect-ratio: 9 / 14;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    overflow: hidden;
  }
  .story-dialog-media img, .story-dialog-media video {
    width: 100%;
    height: 100%;
    object-fit: contain;
    background: #000;
  }

  .story-dialog-body {
    padding: 16px 20px 20px;
    overflow-y: auto;
    color: #e5e5e5;
    background: #14111a;
  }
  .story-dialog-title {
    font-size: 15px;
    font-weight: 600;
    color: #fff;
    margin-bottom: 6px;
    line-height: 1.4;
  }
  .story-dialog-desc {
    font-size: 13px;
    color: #b3acc0;
    line-height: 1.6;
    white-space: pre-line;
  }

  /* Navigasi Panah Kiri & Kanan */
  .story-arrow-btn {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: rgba(20, 17, 26, 0.75);
    border: 1px solid rgba(218, 175, 90, 0.35);
    color: #f5e6be;
    font-size: 22px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    z-index: 10;
    transition: all .2s;
  }
  .story-arrow-btn:hover {
    background: rgba(218, 175, 90, 0.95);
    color: #14111a;
    border-color: transparent;
  }
  .story-arrow-prev { left: -60px; }
  .story-arrow-next { right: -60px; }

  @media (max-width: 600px) {
    .story-arrow-prev { left: 8px; }
    .story-arrow-next { right: 8px; }
    .story-arrow-btn {
      width: 36px;
      height: 36px;
      font-size: 18px;
      background: rgba(14, 11, 18, 0.65);
    }
  }
  </style>
</head>
<body>

<?php $headerTitle = 'Stories'; include __DIR__ . '/../components/page_header.php'; ?>

<main id="main-content" style="padding:6px">

  <!-- ── Page Hero ── -->
  <div class="page-hero">
    <div class="page-hero-icon">
      <img src="https://img.parokitulungagung.org/icon/icon_kronik.png" alt="Stories" loading="eager" width="40" height="40">
    </div>
    <div class="page-hero-text">
      <h1>Stories</h1>
      <p>Paroki Santa Maria Dengan Tidak Bernoda Asal · Tulungagung</p>
    </div>
  </div>

  <div class="stories-section-wrap">
    
    <div class="stories-stats-bar">
      <div class="stories-stats-title">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
        </svg>
        <span>Dokumentasi Foto &amp; Video Pilihan</span>
      </div>
      <div class="stories-stats-badge">
        <?= (int)$totalStories ?> Momen
      </div>
    </div>

    <?php if ($totalStories > 0): ?>
    <div class="stories-feed-wrap" id="storiesFeedWrap">
      <?php foreach ($stories as $idx => $st):
        $isVideo = ($st['type'] ?? '') === 'video';
        $thumb = $isVideo ? (!empty($st['poster_url']) ? $st['poster_url'] : '') : ($st['url'] ?? '');
        $title = !empty($st['file_name']) ? $st['file_name'] : 'Momen Paroki';
      ?>
      <div class="story-card" onclick="openStoryLightbox(<?= (int)$idx ?>)" role="button" tabindex="0" aria-label="<?= htmlspecialchars($title, ENT_QUOTES) ?>">
        <?php if (!empty($thumb)): ?>
        <img src="<?= htmlspecialchars($thumb, ENT_QUOTES) ?>"
             alt="<?= htmlspecialchars($title, ENT_QUOTES) ?>"
             class="story-cover-img"
             loading="lazy"
             onerror="this.parentElement.classList.add('is-fallback')">
        <?php endif; ?>

        <?php if ($isVideo): ?>
        <div class="story-video-pill">
          <svg width="9" height="9" viewBox="0 0 24 24"><polygon points="5 3 19 12 5 21 5 3"/></svg>
          <span>Video</span>
        </div>
        <?php endif; ?>

        <div class="story-scrim">
          <div class="story-caption-title"><?= htmlspecialchars($title, ENT_QUOTES) ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div style="text-align:center;padding:60px 20px;background:#fff;border-radius:16px;border:1px dashed #d9d0e2;color:#8a7e9b">
      <p style="font-size:14px;margin:0">Belum ada konten Stories saat ini.</p>
    </div>
    <?php endif; ?>

  </div>

</main>

<!-- ── Modal Lightbox Card ── -->
<div class="story-lightbox" id="storyLightbox" onclick="handleLightboxBackdrop(event)">
  <button class="story-arrow-btn story-arrow-prev" onclick="prevStory(event)" aria-label="Sebelumnya">‹</button>
  <button class="story-arrow-btn story-arrow-next" onclick="nextStory(event)" aria-label="Berikutnya">›</button>

  <div class="story-dialog-card" id="storyDialogCard">
    <div class="story-dialog-header">
      <div class="story-dialog-counter" id="storyDialogCounter">1 / 1</div>
      <button class="story-dialog-close" onclick="closeStoryLightbox()" title="Tutup">✕</button>
    </div>
    <div class="story-dialog-media" id="storyDialogMedia"></div>
    <div class="story-dialog-body">
      <div class="story-dialog-title" id="storyDialogTitle"></div>
      <div class="story-dialog-desc" id="storyDialogDesc"></div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../components/footer.php'; ?>

<script>
const storiesData = <?= json_encode($stories, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
let activeStoryIdx = 0;

function openStoryLightbox(idx) {
  if (!storiesData || !storiesData[idx]) return;
  activeStoryIdx = idx;
  renderStoryMedia();
  const lb = document.getElementById('storyLightbox');
  lb.classList.add('active');
  document.body.style.overflow = 'hidden';
}

function closeStoryLightbox() {
  const lb = document.getElementById('storyLightbox');
  lb.classList.remove('active');
  const mediaContainer = document.getElementById('storyDialogMedia');
  mediaContainer.innerHTML = '';
  document.body.style.overflow = '';
}

function handleLightboxBackdrop(e) {
  if (e.target.id === 'storyLightbox') {
    closeStoryLightbox();
  }
}

function renderStoryMedia() {
  const item = storiesData[activeStoryIdx];
  if (!item) return;

  const mediaEl   = document.getElementById('storyDialogMedia');
  const titleEl   = document.getElementById('storyDialogTitle');
  const descEl    = document.getElementById('storyDialogDesc');
  const counterEl = document.getElementById('storyDialogCounter');

  counterEl.textContent = (activeStoryIdx + 1) + ' / ' + storiesData.length;
  titleEl.textContent   = item.file_name || 'Momen Paroki';
  descEl.textContent    = item.description || '';
  descEl.style.display  = item.description ? 'block' : 'none';

  mediaEl.innerHTML = '';
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
    mediaEl.appendChild(video);
  } else {
    const img = document.createElement('img');
    img.src = item.url;
    img.alt = item.file_name || 'Momen Paroki';
    img.style.width = '100%';
    img.style.height = '100%';
    img.style.objectFit = 'contain';
    mediaEl.appendChild(img);
  }
}

function prevStory(e) {
  if (e) e.stopPropagation();
  if (activeStoryIdx > 0) {
    activeStoryIdx--;
  } else {
    activeStoryIdx = storiesData.length - 1;
  }
  renderStoryMedia();
}

function nextStory(e) {
  if (e) e.stopPropagation();
  if (activeStoryIdx < storiesData.length - 1) {
    activeStoryIdx++;
  } else {
    activeStoryIdx = 0;
  }
  renderStoryMedia();
}

// Navigasi Keyboard
document.addEventListener('keydown', (e) => {
  const lb = document.getElementById('storyLightbox');
  if (!lb || !lb.classList.contains('active')) return;
  if (e.key === 'ArrowLeft') prevStory();
  if (e.key === 'ArrowRight') nextStory();
  if (e.key === 'Escape') closeStoryLightbox();
});

// Gesture Swipe Mobile
(function(){
  let touchStartX = 0;
  let touchEndX = 0;
  const card = document.getElementById('storyDialogCard');
  if (!card) return;

  card.addEventListener('touchstart', (e) => {
    touchStartX = e.changedTouches[0].screenX;
  }, { passive: true });

  card.addEventListener('touchend', (e) => {
    touchEndX = e.changedTouches[0].screenX;
    if (touchEndX < touchStartX - 50) nextStory();
    if (touchEndX > touchStartX + 50) prevStory();
  }, { passive: true });
})();
</script>

</body>
</html>
