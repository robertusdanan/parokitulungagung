<?php
/**
 * admin/api/cache.php
 * Flush cache manual dari dashboard admin (superadmin only).
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
adminBoot();

header('Content-Type: application/json; charset=utf-8');

$currentUser = apiRequireLogin();
if ($currentUser['role'] !== ROLE_SUPERADMIN) {
    apiJson(['error' => 'Hanya superadmin yang dapat flush cache'], 403);
}

$body  = jsonBody();
$table = $body['table'] ?? '*';

invalidateCache($table);

getLogger()->log($currentUser, 'CACHE_FLUSH', 'cache',
    $table === '*' ? 'Flush semua cache' : 'Flush cache: ' . $table);

apiJson(['success' => true, 'message' => $table === '*' ? 'Semua cache dihapus' : "Cache $table dihapus"]);
