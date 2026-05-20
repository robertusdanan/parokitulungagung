<?php
/**
 * admin/api/kategorial.php
 */

// ── 1. Tangkap semua output ───────────────────────────────────────────
ob_start();
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

// ── 2. Shutdown handler — tangkap fatal error, kembalikan JSON ────────
register_shutdown_function(function () {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        while (ob_get_level() > 0) ob_end_clean();
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        // Kembalikan pesan error agar mudah debug
        echo json_encode([
            'error'  => 'Fatal PHP error',
            'detail' => $err['message'],
            'file'   => basename($err['file']),
            'line'   => $err['line'],
        ], JSON_UNESCAPED_UNICODE);
    }
});

// ── 3. Load ───────────────────────────────────────────────────────────
require_once __DIR__ . '/../includes/functions.php';
adminBoot();

// ── 4. Helper output JSON bersih ──────────────────────────────────────
function sendJson(array $data, int $code = 200): never
{
    while (ob_get_level() > 0) ob_end_clean();
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// ── 5. Auth ───────────────────────────────────────────────────────────
startAdminSession();
if (empty($_SESSION['admin_user'])) {
    sendJson(['error' => 'Unauthorized - silakan login ulang'], 401);
}
if (!empty($_SESSION['admin_expire']) && time() > $_SESSION['admin_expire']) {
    session_destroy();
    sendJson(['error' => 'Sesi habis - silakan login ulang'], 401);
}
$_SESSION['admin_expire'] = time() + SESSION_LIFETIME;
$user = $_SESSION['admin_user'];

// ── 6. Routing ────────────────────────────────────────────────────────
$body   = jsonBody();
$action = $body['action'] ?? $_GET['action'] ?? '';
$db     = getDB();
$TABLE  = 'kelompok_profil';

$BUILT_IN_SLUGS = [
    'adorasi','pdkk','wanita-katolik','gim','legiomaria',
    'me','pk','rosariohidup','ktm','ssvmaria','ssvrosali'
];

function getValidSlugs($db, string $table, array $builtIn): array
{
    static $cache = null;
    if ($cache !== null) return $cache;
    $rows    = $db->readWhere($table, [], '', 'slug');
    $dbSlugs = is_array($rows) ? array_column($rows, 'slug') : [];
    $cache   = array_unique(array_merge($builtIn, $dbSlugs));
    return $cache;
}

switch ($action) {

    case 'getAll': {
        $rows = $db->readWhere($TABLE, [], 'slug.asc', '*');
        sendJson(['success' => true, 'data' => $rows ?? []]);
    }

    case 'get': {
        $slug = preg_replace('/[^a-z0-9\-]/', '', $body['slug'] ?? $_GET['slug'] ?? '');
        $rows = $db->readWhere($TABLE, ['slug=eq.' . $slug], '', '*');
        $data = (!empty($rows) && is_array($rows[0])) ? $rows[0] : ['slug' => $slug];
        sendJson(['success' => true, 'data' => $data]);
    }

    case 'save': {
        $isSA     = ($user['role'] ?? '') === ROLE_SUPERADMIN;
        $permsMap = $isSA ? [] : getPermissionsMap($user);
        $canEdit  = $isSA || in_array('edit', $permsMap['kategorial'] ?? []);
        if (!$canEdit) sendJson(['error' => 'Akses ditolak'], 403);

        $slug = preg_replace('/[^a-z0-9\-]/', '', $body['slug'] ?? '');
        if (!$slug) sendJson(['error' => 'Slug tidak boleh kosong'], 400);

        $isNew = !empty($body['is_new']);

        if ($isNew) {
            if (!preg_match('/^[a-z0-9]([a-z0-9\-]*[a-z0-9])?$/', $slug))
                sendJson(['error' => 'Format slug tidak valid'], 400);
            $dup = $db->readWhere($TABLE, ['slug=eq.' . $slug], '', 'slug');
            if (!empty($dup)) sendJson(['error' => 'Slug "' . $slug . '" sudah digunakan'], 409);
        } else {
            if (!in_array($slug, getValidSlugs($db, $TABLE, $BUILT_IN_SLUGS)))
                sendJson(['error' => 'Slug tidak valid'], 400);
        }

        $cols = ['nama','subtitle','deskripsi','banner','icon',
                 'info','kegiatan','tipe_sosial','handle_sosial','link_sosial','pengurus'];
        $row  = ['slug' => $slug, 'updated_by' => $user['username'] ?? ''];
        foreach ($cols as $col) {
            if (array_key_exists($col, $body)) $row[$col] = $body[$col];
        }
        if (isset($row['pengurus'])) {
            $dec = json_decode($row['pengurus'], true);
            if (!is_array($dec)) $row['pengurus'] = '[]';
        }

        $existing = $db->readWhere($TABLE, ['slug=eq.' . $slug], '', 'slug');
        if (!empty($existing)) {
            $db->update($TABLE, 'slug', $slug, $row);
        } else {
            $db->insert($TABLE, $row);
        }

        $saved = $db->readWhere($TABLE, ['slug=eq.' . $slug], '', '*');
        $data  = (!empty($saved) && is_array($saved[0])) ? $saved[0] : $row;

        try { getLogger()->log($user, 'UPDATE', 'kategorial', 'Update: ' . $slug); }
        catch (Throwable $e) { /* opsional */ }

        sendJson(['success' => true, 'data' => $data]);
    }

    case 'delete': {
        if (($user['role'] ?? '') !== ROLE_SUPERADMIN)
            sendJson(['error' => 'Hanya superadmin yang bisa menghapus'], 403);

        $slug = preg_replace('/[^a-z0-9\-]/', '', $body['slug'] ?? '');
        if (!$slug) sendJson(['error' => 'Slug tidak boleh kosong'], 400);

        $db->delete($TABLE, 'slug', $slug);

        try { getLogger()->log($user, 'DELETE', 'kategorial', 'Hapus: ' . $slug); }
        catch (Throwable $e) { /* opsional */ }

        sendJson(['success' => true]);
    }

    default:
        sendJson(['error' => 'Action tidak dikenal: ' . $action], 400);
}