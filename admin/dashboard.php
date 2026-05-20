<?php
/**
 * admin/dashboard.php — dengan Cache Status & Flush
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
adminBoot();
$user         = requireLogin();
$isSuperadmin = $user['role'] === ROLE_SUPERADMIN;

// ── Hitung info cache ──────────────────────────────────────────────────
$cache      = getCacheStatus();
$cacheCount = $cache['count'];
$cacheSizeKB = $cache['size_kb'];
$cacheOldest = $cache['oldest'];

adminHeader('Dashboard', 'dashboard', $user);
?>

<div class="page-header">
  <div class="page-header-left">
    <h1>Selamat datang, <?= e(!empty($user['nama']) ? $user['nama'] : $user['username']) ?> 👋</h1>
    <p>Panel admin Paroki Santa Maria Dengan Tidak Bernoda Asal Tulungagung</p>
  </div>
</div>

<!-- ── Stats Overview ─────────────────────────────────────────────────── -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:14px;margin-bottom:20px">
  <?php
    $totalPublished = 0; $totalDraft = 0;
    try {
        $am = new SupabaseArticleManager(getDB());
        foreach (SupabaseArticleManager::MENUS as $mn) {
            $s = $am->stats($mn);
            $totalPublished += $s['published'];
            $totalDraft     += $s['draft'];
        }
    } catch (Throwable $e) {
        error_log('[Dashboard] Gagal menghitung statistik artikel: ' . $e->getMessage());
    }
    $statCards = [
      ['label'=>'Artikel Published','value'=>$totalPublished,'color'=>'var(--success)','path'=>'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
      ['label'=>'Draft Belum Publish','value'=>$totalDraft,'color'=>'var(--accent)','path'=>'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z'],
      ['label'=>'Cache File','value'=>$cacheCount,'color'=>'var(--text-muted)','path'=>'M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2'],
    ];
    foreach ($statCards as $sc): ?>
  <div class="card" style="padding:18px 20px;display:flex;align-items:center;gap:14px">
    <div style="width:40px;height:40px;border-radius:10px;background:rgba(255,255,255,.05);display:flex;align-items:center;justify-content:center;flex-shrink:0">
      <svg viewBox="0 0 24 24" fill="none" stroke="<?= $sc['color'] ?>" stroke-width="1.8" width="20" height="20"><path d="<?= $sc['path'] ?>"/></svg>
    </div>
    <div>
      <div style="font-size:22px;font-weight:700;color:var(--text-primary);line-height:1"><?= $sc['value'] ?></div>
      <div style="font-size:12px;color:var(--text-muted);margin-top:3px"><?= $sc['label'] ?></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- ── Cache Status Card ─────────────────────────────────────────── -->
<div class="card" style="margin-bottom:20px">
  <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
    <div>
      <div style="font-size:13px;font-weight:600;color:var(--text-primary);margin-bottom:4px">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14" style="vertical-align:middle;margin-right:4px"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/></svg>
        Status Cache
      </div>
      <?php if ($cacheCount > 0): ?>
      <div style="font-size:12.5px;color:var(--text-secondary)">
        <span style="color:var(--success);font-weight:600"><?= $cacheCount ?> entri aktif</span>
        &nbsp;·&nbsp; <?= $cacheSizeKB ?> KB
        <?php if ($cacheOldest): ?>
        &nbsp;·&nbsp; Terlama: <?= date('d/m H:i', $cacheOldest) ?>
        <?php endif; ?>
      </div>
      <div style="font-size:11.5px;color:var(--text-muted);margin-top:3px">
        Cache aktif — halaman publik memuat dari cache lokal, lebih cepat
      </div>
      <?php else: ?>
      <div style="font-size:12.5px;color:var(--text-muted)">
        Belum ada cache — akan dibuat otomatis saat halaman publik pertama kali diakses
      </div>
      <?php endif; ?>
    </div>
    <?php if ($isSuperadmin): ?>
    <button class="btn btn-secondary btn-sm" id="btnFlushCache" onclick="flushCache()"
            style="white-space:nowrap">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14">
        <polyline points="1 4 1 10 7 10"/><polyline points="23 20 23 14 17 14"/>
        <path d="M20.49 9A9 9 0 005.64 5.64L1 10m22 4l-4.64 4.36A9 9 0 013.51 15"/>
      </svg>
      Flush Semua Cache
    </button>
    <?php endif; ?>
  </div>
</div>

<!-- ── Info Cards ─────────────────────────────────────────────────── -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:20px">
  <div class="card" style="padding:20px">
    <div style="font-size:11px;text-transform:uppercase;letter-spacing:0.8px;color:var(--text-muted);margin-bottom:8px">Database</div>
    <div style="font-size:18px;font-weight:700;color:var(--text-primary)">Supabase</div>
    <div style="font-size:12px;color:var(--success);margin-top:4px">● Terhubung</div>
  </div>
  <div class="card" style="padding:20px">
    <div style="font-size:11px;text-transform:uppercase;letter-spacing:0.8px;color:var(--text-muted);margin-bottom:8px">Cache Lokal</div>
    <div style="font-size:18px;font-weight:700;color:var(--text-primary)"><?= $cacheCount ?> entri</div>
    <div style="font-size:12px;color:var(--text-secondary);margin-top:4px"><?= $cacheSizeKB ?> KB tersimpan</div>
  </div>
  <div class="card" style="padding:20px">
    <div style="font-size:11px;text-transform:uppercase;letter-spacing:0.8px;color:var(--text-muted);margin-bottom:8px">Role Anda</div>
    <div style="font-size:18px;font-weight:700;color:var(--text-primary)"><?= $isSuperadmin ? 'Super Admin' : 'Admin' ?></div>
    <div style="font-size:12px;color:var(--text-secondary);margin-top:4px"><?= e($user['username']) ?></div>
  </div>
</div>

<?php if ($isSuperadmin): ?>
<script>
async function flushCache() {
  const btn = document.getElementById('btnFlushCache');
  if (!confirm('Hapus semua cache? Halaman publik akan fetch ulang data dari Supabase saat pertama kali diakses.')) return;
  btn.disabled = true;
  btn.textContent = 'Menghapus...';
  try {
    const res = await fetch('/admin/api/cache.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ table: '*' })
    });
    const data = await res.json();
    if (data.success) {
      toast('Cache Dihapus', data.message, 'success');
      setTimeout(() => location.reload(), 1200);
    } else {
      toast('Error', data.error, 'error');
      btn.disabled = false;
      btn.textContent = 'Flush Semua Cache';
    }
  } catch(e) {
    toast('Error', 'Gagal menghubungi server', 'error');
    btn.disabled = false;
    btn.textContent = 'Flush Semua Cache';
  }
}
</script>
<?php endif; ?>

<?php adminFooter(); ?>