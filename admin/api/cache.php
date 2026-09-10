<?php
/**
 * admin/api/cache.php
 * ─────────────────────────────────────────────────────────────────────────
 * API status & flush cache — dipakai halaman admin/pages/cache.php
 * (Sistem → Cache) dan ringkasan kecil di dashboard.
 *
 * Actions (POST, JSON body):
 *  - status : ringkasan semua grup cache (jumlah file, ukuran, terlama/terbaru)
 *  - flush  : hapus 1 grup cache ({"group":"supabase"}) atau semua sekaligus
 *             ({"group":"*"}) — superadmin only.
 *
 * Lihat admin/includes/CacheRegistry.php untuk daftar grup cache.
 * ─────────────────────────────────────────────────────────────────────────
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
adminBoot();

header('Content-Type: application/json; charset=utf-8');

$currentUser = apiRequireLogin();
$body        = jsonBody();
$action      = $body['action'] ?? 'status';

switch ($action) {

    // ── STATUS ───────────────────────────────────────────────────────
    case 'status':
        apiJson(['success' => true, 'groups' => getAllCacheGroupsStatus()]);
        break;

    // ── FLUSH ────────────────────────────────────────────────────────
    case 'flush':
        if ($currentUser['role'] !== ROLE_SUPERADMIN) {
            apiJson(['error' => 'Hanya superadmin yang dapat flush cache'], 403);
        }

        $group  = (string)($body['group'] ?? '');
        $groups = getCacheGroups();

        if ($group === '*' || $group === 'all' || $group === '') {
            $deleted = cacheFlushAll();
            $total   = array_sum($deleted);
            getLogger()->log($currentUser, 'CACHE_FLUSH', 'cache', "Flush semua cache ($total file)");
            apiJson(['success' => true, 'message' => "Semua cache dihapus ($total file)", 'deleted' => $deleted]);
        }

        if (!isset($groups[$group])) {
            apiJson(['error' => 'Grup cache tidak dikenal: ' . $group], 400);
        }

        $n = cacheGroupFlush($group);
        getLogger()->log($currentUser, 'CACHE_FLUSH', 'cache', 'Flush cache: ' . $groups[$group]['label'] . " ($n file)");
        apiJson(['success' => true, 'message' => $groups[$group]['label'] . ": $n file dihapus", 'deleted' => $n]);
        break;

    default:
        apiJson(['error' => 'Action tidak dikenal: ' . $action], 400);
}
