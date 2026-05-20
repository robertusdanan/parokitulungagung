<?php
// ============================================================
// SEAT-MANAGER.PHP — Kelola Kuota Dibuka & Status Tiket
// Kuota asli bersumber dari config.php dan TIDAK bisa diubah.
// Yang bisa diatur: open_quota (berapa yang dibuka saat ini).
// ============================================================

require_once __DIR__ . '/../admin/auth.php';
require_once __DIR__ . '/../config.php';

if (!isAdminLoggedIn()) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ── FILE penyimpanan status tiket ──────────────────────────
define('SEAT_CONFIG_FILE', __DIR__ . '/../data/seat-config.json');

if (!is_dir(__DIR__ . '/../data')) {
    mkdir(__DIR__ . '/../data', 0755, true);
}

// Baca config — kuota asli SELALU dari config.php, open_quota dari JSON
function readSeatConfig(): array {
    global $ticketConfig;
    $saved = [];
    if (file_exists(SEAT_CONFIG_FILE)) {
        $decoded = json_decode(file_get_contents(SEAT_CONFIG_FILE), true);
        if (is_array($decoded)) $saved = $decoded;
    }
    $result = [];
    foreach ($ticketConfig as $tc) {
        $type          = $tc['type'];
        $originalQuota = (int)$tc['quota'];                          // selalu dari config.php
        $openQuota     = (int)($saved[$type]['open_quota'] ?? $originalQuota);
        $openQuota     = min($openQuota, $originalQuota);            // jaga konsistensi
        $result[$type] = [
            'quota'      => $originalQuota,                          // READ-ONLY, dari config.php
            'open_quota' => $openQuota,
            'closed'     => (bool)($saved[$type]['closed'] ?? false),
        ];
    }
    return $result;
}

// Simpan — hanya tulis open_quota & closed ke JSON (quota tidak disimpan)
function saveSeatConfig(array $config): void {
    global $ticketConfig;
    $toSave = [];
    foreach ($ticketConfig as $tc) {
        $type = $tc['type'];
        $toSave[$type] = [
            'open_quota' => (int)($config[$type]['open_quota'] ?? $tc['quota']),
            'closed'     => (bool)($config[$type]['closed'] ?? false),
        ];
    }
    file_put_contents(SEAT_CONFIG_FILE, json_encode($toSave, JSON_PRETTY_PRINT));
}

// ── UPDATE KUOTA DIBUKA (open_quota) ──────────────────────
if ($action === 'update_open_quota') {
    $type         = $_POST['type'] ?? '';
    $newOpenQuota = (int)($_POST['open_quota'] ?? 0);

    if (empty($type) || $newOpenQuota < 0) {
        echo json_encode(['success' => false, 'message' => 'Parameter tidak valid']);
        exit;
    }

    $seatCfg = readSeatConfig();

    if (!isset($seatCfg[$type])) {
        echo json_encode(['success' => false, 'message' => 'Tipe tiket tidak ditemukan']);
        exit;
    }

    $originalQuota = (int)$seatCfg[$type]['quota'];  // dari config.php

    if ($newOpenQuota > $originalQuota) {
        echo json_encode([
            'success' => false,
            'message' => "Kuota dibuka maksimal {$originalQuota} (kuota asli zona {$type}). Tidak boleh melebihi kuota asli.",
        ]);
        exit;
    }

    $seatCfg[$type]['open_quota'] = $newOpenQuota;
    saveSeatConfig($seatCfg);

    echo json_encode([
        'success'        => true,
        'message'        => 'Kuota dibuka berhasil diperbarui',
        'open_quota'     => $newOpenQuota,
        'original_quota' => $originalQuota,
        'config'         => $seatCfg,
    ]);
    exit;
}

// ── TOGGLE STATUS TIKET (open / closed) ───────────────────
if ($action === 'toggle_status') {
    $type = $_POST['type'] ?? '';

    if (empty($type)) {
        echo json_encode(['success' => false, 'message' => 'Tipe tiket tidak ditemukan']);
        exit;
    }

    $seatCfg = readSeatConfig();

    if (!isset($seatCfg[$type])) {
        echo json_encode(['success' => false, 'message' => 'Tipe tiket tidak ditemukan']);
        exit;
    }

    $seatCfg[$type]['closed'] = !($seatCfg[$type]['closed'] ?? false);
    saveSeatConfig($seatCfg);

    $status = $seatCfg[$type]['closed'] ? 'archived' : 'open';
    echo json_encode([
        'success' => true,
        'message' => "Tiket {$type} sekarang: {$status}",
        'closed'  => $seatCfg[$type]['closed'],
        'config'  => $seatCfg,
    ]);
    exit;
}

// ── GET CONFIG (untuk dibaca halaman lain) ────────────────
if ($action === 'get_config' || $_SERVER['REQUEST_METHOD'] === 'GET') {
    echo json_encode([
        'success' => true,
        'config'  => readSeatConfig(),
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Action tidak dikenali']);