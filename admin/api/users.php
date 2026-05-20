<?php
/**
 * admin/api/users.php — API manajemen user via Supabase
 * Hanya bisa diakses oleh superadmin
 */
ob_start(); // tangkap semua output (notice/warning) agar tidak merusak JSON response
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
adminBoot();

header('Content-Type: application/json; charset=utf-8');

$currentUser = apiRequireLogin();

if ($currentUser['role'] !== ROLE_SUPERADMIN) {
    apiJson(['error' => 'Akses ditolak. Hanya superadmin yang bisa mengelola user.'], 403);
}

$body   = jsonBody();
$action = $body['action'] ?? '';

try {
    $um     = new UserManager(getDB());
    $logger = getLogger();

    switch ($action) {

        case 'list':
            $users = $um->getAll();
            // Jangan kirim password_hash ke frontend
            $safe = array_map(function ($u) {
                unset($u['password_hash']);
                return $u;
            }, $users);
            apiJson(['success' => true, 'data' => $safe]);
            break;

        case 'create':
            $username = trim($body['username'] ?? '');
            $password = $body['password'] ?? '';
            if (!$username) apiJson(['error' => 'Username wajib diisi'], 400);
            if (strlen($password) < 8) apiJson(['error' => 'Password minimal 8 karakter'], 400);

            $res = $um->create([
                'username'    => $username,
                'email'       => trim($body['email'] ?? ''),
                'password'    => $password,
                'role'        => $body['role'] ?? ROLE_ADMIN,
                'permissions' => $body['permissions'] ?? [],
                'nama'        => trim($body['nama'] ?? ''),
            ], $currentUser['username']);
            $logger->log($currentUser, 'CREATE', 'users', 'Membuat user: ' . $username);
            apiJson(['success' => true, 'user' => $res]);
            break;

        case 'update':
            $id = trim($body['id'] ?? '');
            if (!$id) apiJson(['error' => 'ID user diperlukan'], 400);

            $updateData = [];
            if (isset($body['email']))       $updateData['email']       = $body['email'];
            if (isset($body['role']))        $updateData['role']        = $body['role'];
            if (isset($body['permissions'])) $updateData['permissions'] = $body['permissions'];
            if (!empty($body['password']))   $updateData['password']    = $body['password'];
            if (isset($body['nama']))        $updateData['nama']        = $body['nama'];

            // FIX: Normalisasi is_active ke boolean PHP sebelum dikirim ke UserManager.
            // Frontend bisa mengirim berbagai format: true (bool), 1 (int),
            // "1" (string), atau bahkan "true" (string) — semua harus ditangani.
            if (array_key_exists('is_active', $body)) {
                $v = $body['is_active'];
                if (is_bool($v)) {
                    $updateData['is_active'] = $v;
                } else {
                    $updateData['is_active'] = filter_var($v, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
                }
            }

            $ok = $um->update($id, $updateData);
            if (!$ok) apiJson(['error' => 'User tidak ditemukan'], 404);
            $logger->log($currentUser, 'UPDATE', 'users', 'Update user ID: ' . $id);
            apiJson(['success' => true]);
            break;

        case 'delete':
            $id = trim($body['id'] ?? '');
            if (!$id) apiJson(['error' => 'ID user diperlukan'], 400);
            if ($id === $currentUser['id']) {
                apiJson(['error' => 'Tidak dapat menghapus akun sendiri'], 400);
            }
            $ok = $um->delete($id);
            if (!$ok) apiJson(['error' => 'User tidak ditemukan'], 404);
            $logger->log($currentUser, 'DELETE', 'users', 'Hapus user ID: ' . $id);
            apiJson(['success' => true]);
            break;

        default:
            apiJson(['error' => 'Action tidak dikenal: ' . $action], 400);
    }

} catch (Throwable $e) {
    error_log('[users.php] Error: ' . $e->getMessage());
    apiJson(['error' => $e->getMessage()], 500);
}