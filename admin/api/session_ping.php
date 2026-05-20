<?php
/**
 * admin/api/session_ping.php — perpanjang session tanpa refresh halaman
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); echo json_encode(['error' => 'Method not allowed']); exit;
}

if (empty($_SESSION['admin_user']) || empty($_SESSION['admin_expire']) || time() > $_SESSION['admin_expire']) {
    http_response_code(401); echo json_encode(['expired' => true]); exit;
}

$_SESSION['admin_expire'] = time() + SESSION_LIFETIME;
echo json_encode(['ok' => true, 'expires_in' => SESSION_LIFETIME]);
