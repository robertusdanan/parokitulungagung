<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/SupabaseClient.php';
require_once __DIR__ . '/includes/ActivityLogger.php';

startAdminSession();
if (!empty($_SESSION['admin_user'])) {
    try {
        $logger = new ActivityLogger(new SupabaseClient());
        $logger->log($_SESSION['admin_user'], 'LOGOUT', 'auth');
    } catch (Throwable $e) {}
    session_destroy();
}
header('Location: /admin');
exit;
