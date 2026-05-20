<?php
/**
 * admin/includes/auth.php
 * Middleware autentikasi & otorisasi — dengan sistem permission granular per-halaman per-aksi
 *
 * Format permissions (JSON di sheet):
 *   {"galeri":["create","edit","delete"],"petugas":["view"],"wilayah":["edit"]}
 *
 * Aturan:
 *   - superadmin              → semua akses
 *   - halaman tidak ada di key → tidak bisa buka halaman (403)
 *   - key ada, array []        → otomatis ["view"] (lihat saja)
 *   - "view"                   → bisa lihat, semua tombol tambah/edit/hapus disembunyikan UI
 *   - "edit"                   → tombol Edit muncul
 *   - "create"                 → tombol Tambah muncul
 *   - "delete"                 → tombol Hapus muncul
 */

function startAdminSession(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_set_cookie_params([
            'lifetime' => SESSION_LIFETIME,
            'path'     => '/admin',
            'secure'   => (function_exists('is_https') ? is_https() : isset($_SERVER['HTTPS'])),
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
        session_start();
    }
}

function requireLogin(): array
{
    startAdminSession();
    if (empty($_SESSION['admin_user'])) {
        header('Location: /admin/index.php'); exit;
    }
    if (!empty($_SESSION['admin_expire']) && time() > $_SESSION['admin_expire']) {
        session_destroy();
        header('Location: /admin/index.php?expired=1'); exit;
    }
    $_SESSION['admin_expire'] = time() + SESSION_LIFETIME;
    return $_SESSION['admin_user'];
}

function requireSuperadmin(): array
{
    $user = requireLogin();
    if ($user['role'] !== ROLE_SUPERADMIN) {
        http_response_code(403); die(renderAccessDenied());
    }
    return $user;
}

/**
 * Cek akses halaman. Kembalikan user + inject 'page_actions' ke array user
 * sehingga halaman PHP tahu aksi apa yang boleh ditampilkan.
 */
function requirePageAccess(string $page): array
{
    $user = requireLogin();
    if ($user['role'] === ROLE_SUPERADMIN) {
        // Superadmin dapat semua aksi
        $user['page_actions'] = PAGE_ACTIONS;
        return $user;
    }

    $perms = getPermissionsMap($user);

    // Tidak punya akses ke halaman ini sama sekali
    if (!array_key_exists($page, $perms)) {
        http_response_code(403); die(renderAccessDenied());
    }

    $actions = $perms[$page];
    // 'view' selalu ada secara implisit — inject otomatis jika belum ada
    if (!in_array('view', $actions)) array_unshift($actions, 'view');

    $user['page_actions'] = $actions;
    return $user;
}

/**
 * Cek akses halaman untuk API. Return user atau kirim JSON error.
 */
function apiRequireLogin(): array
{
    startAdminSession();
    if (empty($_SESSION['admin_user'])) apiJson(['error' => 'Unauthorized'], 401);
    if (!empty($_SESSION['admin_expire']) && time() > $_SESSION['admin_expire']) {
        session_destroy(); apiJson(['error' => 'Session expired'], 401);
    }
    $_SESSION['admin_expire'] = time() + SESSION_LIFETIME;
    return $_SESSION['admin_user'];
}

/**
 * Cek akses halaman DAN aksi spesifik untuk API.
 * $action = 'create' | 'edit' | 'delete' | 'list'
 */
function apiRequirePageAccess(string $page, string $action = 'list'): array
{
    $user = apiRequireLogin();
    if ($user['role'] === ROLE_SUPERADMIN) return $user;

    $perms = getPermissionsMap($user);

    if (!array_key_exists($page, $perms)) {
        apiJson(['error' => 'Akses ditolak: tidak punya akses ke halaman ' . $page], 403);
    }

    $actions = $perms[$page];
    // 'view' selalu ada implisit — inject jika belum ada
    if (!in_array('view', $actions)) array_unshift($actions, 'view');

    // 'list' dan 'view' selalu boleh jika punya akses halaman
    if ($action === 'list' || $action === 'view') return $user;

    // Untuk create/edit/delete/publish, cek apakah ada di actions
    if (!in_array($action, $actions)) {
        $label = PAGE_ACTION_LABELS[$action] ?? $action;
        apiJson(['error' => 'Akses ditolak: Anda tidak punya izin "' . $label . '" di halaman ini'], 403);
    }

    return $user;
}

/**
 * Parse permissions dari session user menjadi associative array:
 * ['galeri' => ['create','edit','delete'], 'petugas' => ['view'], ...]
 *
 * Mendukung 2 format lama (backward compatible):
 *   - Format lama (array string):  ["galeri","petugas","wilayah"]
 *     → di-convert ke semua aksi untuk tiap halaman
 *   - Format baru (object):        {"galeri":["create","edit"],"petugas":["view"]}
 */
function getPermissionsMap(array $user): array
{
    $raw = $user['permissions'] ?? [];

    // Sudah object/associative → format baru
    if (is_array($raw) && !empty($raw) && is_string(array_key_first($raw))) {
        $result = [];
        foreach ($raw as $page => $actions) {
            $acts = is_array($actions) ? $actions : [$actions];
            // 'view' selalu ada secara implisit — inject otomatis jika belum ada
            if (!in_array('view', $acts)) array_unshift($acts, 'view');
            $result[$page] = $acts;
        }
        return $result;
    }

    // Format lama (array of strings ["galeri","petugas"]) → beri semua aksi
    if (is_array($raw)) {
        $result = [];
        foreach ($raw as $page) {
            if (is_string($page)) {
                $result[$page] = ['view','create','edit','delete'];
            }
        }
        return $result;
    }

    return [];
}

/**
 * Helper: cek apakah user punya aksi tertentu di halaman tertentu.
 * Berguna di template PHP.
 */
function userCan(array $user, string $action): bool
{
    if ($user['role'] === ROLE_SUPERADMIN) return true;
    $actions = $user['page_actions'] ?? [];
    return in_array($action, $actions);
}

function apiJson(array $data, int $code = 200): never
{
    // Bersihkan semua output buffer (notice/warning PHP yang mungkin sudah terkirim)
    while (ob_get_level() > 0) ob_end_clean();
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function renderAccessDenied(): string
{
    return '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Akses Ditolak</title>
    <style>body{font-family:sans-serif;display:flex;align-items:center;justify-content:center;
    height:100vh;margin:0;background:#0f0e17;color:#fff;}
    .box{text-align:center;padding:40px;}h1{font-size:48px;margin:0;color:#e63946;}
    p{color:#aaa;}a{color:#a8dadc;}</style></head>
    <body><div class="box"><h1>403</h1><p>Anda tidak memiliki akses ke halaman ini.</p>
    <a href="/admin/dashboard.php">← Kembali ke Dashboard</a></div></body></html>';
}