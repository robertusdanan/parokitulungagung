<?php
/**
4| * cpanel-deploy.php — Webhook Auto-Deployment Instan & Real-time
5| * ─────────────────────────────────────────────────────────────────────────
6| * Menerima payload push dari GitHub Webhook dan mengeksekusi pull + deploy
7| * secara sinkron (detik itu juga) tanpa harus mengantre di queue cPanel.
8| * ─────────────────────────────────────────────────────────────────────────
9| */

header('Content-Type: application/json');

// Token keamanan untuk mencegah trigger liar
$secretToken = 'paroki_deploy_secure_908430316';

if (($_GET['token'] ?? '') !== $secretToken) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Token tidak valid.']);
    exit;
}

if (($_GET['action'] ?? '') === 'read_error_log') {
    $logs = [];
    $candidates = [
        __DIR__ . '/error_log',
        __DIR__ . '/admin/error_log',
        __DIR__ . '/admin/api/error_log',
        '/home/ejtkecoh/logs/parokitulungagung.org.php.error.log',
    ];
    foreach ($candidates as $f) {
        if (file_exists($f)) {
            $lines = file($f, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $logs[basename(dirname($f)) . '/' . basename($f)] = array_slice($lines, -25);
        }
    }
    echo json_encode(['status' => 'success', 'logs' => $logs], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

$repoDir = '/home/ejtkecoh/repositories/parokitulungagung';
$webDir  = '/home/ejtkecoh/public_html';

$output = [];
$output['time'] = date('Y-m-d H:i:s');

// Opsi 1: Coba jalankan Git Pull secara langsung (Synchronous & Instan)
if (is_dir($repoDir)) {
    // Jalankan git pull di folder repository
    $cmdPull = "cd " . escapeshellarg($repoDir) . " && git pull origin main 2>&1";
    $resPull = shell_exec($cmdPull);
    $output['git_pull'] = trim((string)$resPull);

    // Jalankan penyalinan file ke public_html
    if (strpos((string)$resPull, 'Updating') !== false || strpos((string)$resPull, 'Already up to date') !== false || strpos((string)$resPull, 'Fast-forward') !== false) {
        $cmdCopy = "cp -a " . escapeshellarg($repoDir) . "/. " . escapeshellarg($webDir) . "/ 2>&1";
        $resCopy = shell_exec($cmdCopy);
        $output['file_deploy'] = 'Files copied successfully';
        if ($resCopy) {
            $output['file_deploy_error'] = trim((string)$resCopy);
        }

        // Fix permissions agar LiteSpeed tidak memicu 404/403
        shell_exec("find " . escapeshellarg($webDir) . " -type d -exec chmod 755 {} +");
        shell_exec("find " . escapeshellarg($webDir) . " -type f -exec chmod 644 {} +");
        $output['permissions'] = 'Reset to 755/644';
    }
} else {
    $output['error'] = 'Repository directory tidak ditemukan.';
}

// Opsi 2: Cadangan via cPanel UAPI jika direct shell_exec dibatasi
if (empty($output['git_pull']) || strpos(($output['git_pull']), 'Permission denied') !== false) {
    $output['mode'] = 'uapi_fallback';
    // Gunakan cPanel UAPI di level sistem
    $uapiUpdate = shell_exec("/usr/local/cpanel/bin/uapi VersionControl update repository_root=" . escapeshellarg($repoDir) . " 2>&1");
    $uapiDeploy = shell_exec("/usr/local/cpanel/bin/uapi VersionControlDeployment create repository_root=" . escapeshellarg($repoDir) . " 2>&1");
    $output['uapi_update'] = json_decode($uapiUpdate, true) ?: trim((string)$uapiUpdate);
    $output['uapi_deploy'] = json_decode($uapiDeploy, true) ?: trim((string)$uapiDeploy);
} else {
    $output['mode'] = 'direct_git';
}

echo json_encode(['status' => 'success', 'details' => $output]);
