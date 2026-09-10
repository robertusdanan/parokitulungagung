<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/SupabaseClient.php';
require_once __DIR__ . '/includes/ActivityLogger.php';

startAdminSession();

// Kalau logout dipicu dari widget akun di halaman publik (Home/Artikel/dll),
// redirect kembali ke halaman itu — bukan ke halaman login admin.
// Kalau tidak ada (mis. logout dari dalam panel admin sendiri), tetap ke /admin.
$redirectTo = safeRedirectBack($_GET['redirect'] ?? null);

if (!empty($_SESSION['admin_user'])) {
    try {
        $logger = new ActivityLogger(new SupabaseClient());
        $logger->log($_SESSION['admin_user'], 'LOGOUT', 'auth');
    } catch (Throwable $e) {}
    session_destroy();
}
header('Location: ' . ($redirectTo ?: '/admin'));
exit;
