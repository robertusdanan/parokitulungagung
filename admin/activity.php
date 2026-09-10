<?php
/**
 * admin/activity.php — Log Aktivitas Admin
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
adminBoot();
$user = requireSuperadmin();

$filterUser   = trim($_GET['user']   ?? '');
$filterAction = trim($_GET['action'] ?? '');
$filterPage   = trim($_GET['page']   ?? '');
$limitReq     = min(500, max(50, (int)($_GET['limit'] ?? 200)));

$logs = [];
$err  = '';
try {
    $logs = getLogger()->getFiltered($limitReq, $filterUser, $filterAction, $filterPage);
} catch (Throwable $e) {
    $err = $e->getMessage();
}

adminHeader('Log Aktivitas', 'activity', $user);
?>

<div class="page-header">
  <div class="page-header-left">
    <h1>Log Aktivitas</h1>
    <p><?= count($logs) ?> entri<?= $filterUser || $filterAction || $filterPage ? ' (terfilter)' : '' ?></p>
  </div>
</div>

<?php if ($err): ?>
<div class="card" style="border-color:var(--danger)"><p style="color:var(--danger)"><?= e($err) ?></p></div>
<?php endif; ?>

<div class="card" style="margin-bottom:16px">
  <form method="GET" style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end">
    <div style="flex:1;min-width:140px">
      <label style="font-size:11.5px;color:var(--text-muted);display:block;margin-bottom:4px">Username</label>
      <input type="text" name="user" class="form-control" value="<?= e($filterUser) ?>" placeholder="Semua user">
    </div>
    <div style="min-width:130px">
      <label style="font-size:11.5px;color:var(--text-muted);display:block;margin-bottom:4px">Aksi</label>
      <select name="action" class="form-select">
        <option value="">Semua aksi</option>
        <?php foreach (['LOGIN','LOGOUT','CREATE','UPDATE','DELETE','UPLOAD','CACHE_FLUSH'] as $a): ?>
        <option value="<?= $a ?>" <?= $filterAction === $a ? 'selected' : '' ?>><?= $a ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div style="flex:1;min-width:120px">
      <label style="font-size:11.5px;color:var(--text-muted);display:block;margin-bottom:4px">Halaman</label>
      <input type="text" name="page" class="form-control" value="<?= e($filterPage) ?>" placeholder="artikel, galeri…">
    </div>
    <div style="min-width:90px">
      <label style="font-size:11.5px;color:var(--text-muted);display:block;margin-bottom:4px">Tampilkan</label>
      <select name="limit" class="form-select">
        <?php foreach ([50,100,200,500] as $lv): ?>
        <option value="<?= $lv ?>" <?= $limitReq === $lv ? 'selected' : '' ?>><?= $lv ?> entri</option>
        <?php endforeach; ?>
      </select>
    </div>
    <div style="display:flex;gap:6px">
      <button type="submit" class="btn btn-primary btn-sm">Filter</button>
      <a href="/admin/activity.php" class="btn btn-secondary btn-sm">Reset</a>
    </div>
  </form>
</div>

<div class="card">
  <div class="toolbar">
    <div class="toolbar-left">
      <div class="search-wrap">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" class="form-control" id="searchLog" placeholder="Cari di hasil ini…">
      </div>
    </div>
  </div>
  <div class="table-wrapper">
    <table class="data-table" id="logTable">
      <thead>
        <tr>
          <th>Waktu</th><th>User</th><th>Aksi</th><th>Halaman</th><th>Detail</th><th>IP</th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($logs)): ?>
        <tr><td colspan="6" style="text-align:center;color:var(--text-muted);padding:30px">Belum ada log aktivitas.</td></tr>
      <?php endif; ?>
      <?php foreach ($logs as $log):
        $colors = ['LOGIN'=>'badge-green','LOGOUT'=>'badge-gray','CREATE'=>'badge-blue','UPDATE'=>'badge-gold','DELETE'=>'badge-red','UPLOAD'=>'badge-blue','CACHE_FLUSH'=>'badge-gray'];
        $bc = $colors[$log['action']] ?? 'badge-gray';
      ?>
        <tr>
          <td style="white-space:nowrap;font-family:'DM Mono',monospace;font-size:12px"><?= e($log['timestamp']) ?></td>
          <td><span class="badge badge-gold"><?= e($log['username']) ?></span></td>
          <td><span class="badge <?= $bc ?>"><?= e($log['action']) ?></span></td>
          <td style="font-size:13px"><?= e($log['page']) ?></td>
          <td style="font-size:12px;color:var(--text-secondary);max-width:250px"><?= e($log['detail']) ?></td>
          <td style="font-family:'DM Mono',monospace;font-size:12px;color:var(--text-muted)"><?= e($log['ip']) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
  initSearch('searchLog', 'logTable');
  initPagination('logTable', 30);
});
</script>

<?php adminFooter(); ?>
