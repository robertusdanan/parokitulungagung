<?php
/**
 * TAMBAHKAN KE: pages/content.php
 *
 * 1. Di bagian require_once paling atas, tambahkan:
 *    require_once __DIR__ . '/../includes/SupabaseArticleManager.php';
 *
 * 2. Di dalam switch ($pageType) tambahkan case baru:
 *    case 'artikel': renderArtikel($config); break;
 *
 * 3. Tambahkan fungsi renderArtikel() di bawah ini ke content.php
 *    (letakkan di antara fungsi-fungsi render yang sudah ada)
 */

// ── ARTIKEL (Berita / Kronik / Historia) ─────────────────────────────────
function renderArtikel(array $config): void {
    $menu  = $config['menu'] ?? '';
    $am    = new SupabaseArticleManager();
    $label = SupabaseArticleManager::MENU_LABELS[$menu] ?? $config['title'] ?? '';

    // Redirect ke halaman artikel yang dedicated
    // (artikel.php menangani pagination, dll)
    $page = max(1, (int)($_GET['hal'] ?? 1));
    $articles = $am->getAll($menu, publishedOnly: true);
    $perPage  = 9;
    $total    = count($articles);
    $pages    = (int)ceil($total / $perPage);
    $sliced   = array_slice($articles, ($page - 1) * $perPage, $perPage);
    ?>
    <!-- Header -->
    <div style="margin-bottom:1em">
      <table cellpadding="0" cellspacing="0"><tr>
        <td class="tdikonheadline">
          <img class="iconheadline"
               src="<?= e($config['icon'] ?? '/img/icon/icon_square_berita.png') ?>"
               alt="<?= e($label) ?>">
        </td>
        <td style="padding-left:0.4em">
          <span class="headline_title"><?= e($label) ?></span><br>
          <span class="headline_subtitle">PAROKI SANTA MARIA DENGAN TIDAK BERNODA ASAL</span>
        </td>
      </tr></table>
    </div>

    <?php if (empty($sliced)): ?>
    <div style="text-align:center;padding:40px 20px">
      <div style="font-size:40px;margin-bottom:12px">📄</div>
      <p style="color:#888">Belum ada artikel yang dipublikasikan.</p>
    </div>

    <?php else: ?>
    <!-- Grid kartu artikel -->
    <link rel="stylesheet" href="<?= versioned('/css/artikel.css') ?>">
    <div class="art-pub-grid">
      <?php foreach ($sliced as $art):
        $slug    = $art['slug'] ?? $art['id'];
        $url     = '/artikel/' . $menu . '/' . rawurlencode($slug);
        $thumb   = $art['thumbnail'] ?? '';
        $ringkas = $art['ringkasan'] ?? '';
        $tanggal = !empty($art['published_at'])
            ? SupabaseArticleManager::formatTanggal($art['published_at'])
            : (!empty($art['created_at']) ? SupabaseArticleManager::formatTanggal($art['created_at']) : '');
      ?>
      <a class="art-pub-card" href="<?= e($url) ?>">
        <div class="art-pub-thumb-wrap">
          <?php if ($thumb): ?>
          <img src="<?= e($thumb) ?>" alt="<?= e($art['judul'] ?? '') ?>"
               class="art-pub-thumb" loading="lazy"
               onerror="this.closest('.art-pub-thumb-wrap').innerHTML='<div class=\'art-pub-thumb-placeholder\'>📄</div>'">
          <?php else: ?>
          <div class="art-pub-thumb-placeholder">📄</div>
          <?php endif; ?>
          <div class="art-pub-menu-badge"><?= e($label) ?></div>
        </div>
        <div class="art-pub-body">
          <h2 class="art-pub-title"><?= e($art['judul'] ?? '') ?></h2>
          <?php if ($ringkas): ?>
          <p class="art-pub-excerpt"><?= e(mb_substr($ringkas, 0, 120)) ?>…</p>
          <?php endif; ?>
          <div class="art-pub-meta">
            <?php if ($tanggal): ?>
            <span class="art-pub-date">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="11" height="11"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
              <?= e($tanggal) ?>
            </span>
            <?php endif; ?>
            <?php if (!empty($art['penulis'])): ?>
            <span class="art-pub-author">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="11" height="11"><circle cx="12" cy="7" r="4"/><path d="M5.5 21a7 7 0 0113 0"/></svg>
              <?= e($art['penulis']) ?>
            </span>
            <?php endif; ?>
          </div>
        </div>
        <div class="art-pub-arrow">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
        </div>
      </a>
      <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php if ($pages > 1): ?>
    <div class="art-pagination">
      <?php if ($page > 1): ?>
      <a href="?type=<?= $menu ?>&hal=<?= $page-1 ?>#top" class="art-page-btn">‹ Sebelumnya</a>
      <?php endif; ?>
      <?php for ($i = 1; $i <= $pages; $i++): ?>
      <a href="?type=<?= $menu ?>&hal=<?= $i ?>#top"
         class="art-page-btn <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
      <?php endfor; ?>
      <?php if ($page < $pages): ?>
      <a href="?type=<?= $menu ?>&hal=<?= $page+1 ?>#top" class="art-page-btn">Berikutnya ›</a>
      <?php endif; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
    <?php
}