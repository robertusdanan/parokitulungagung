<?php
// ============================================================
// SEAT-STATUS.PHP — Endpoint publik untuk status tiket
// Dipanggil oleh JS saat halaman dibuka agar seatStatus selalu fresh
// ============================================================

// ── Pastikan selalu return JSON, bahkan saat PHP error ──────
// Ini mencegah halaman error HTML ter-output yang merusak JSON parse di browser
ini_set('display_errors', 0);
error_reporting(0);

set_error_handler(function($errno, $errstr) {
    // Buang semua output sebelumnya (jika ada)
    if (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['error' => true, 'message' => 'Server error']);
    exit;
});

set_exception_handler(function($e) {
    if (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['error' => true, 'message' => 'Server error']);
    exit;
});

// Output buffer agar tidak ada karakter HTML yang bocor sebelum header
ob_start();

require_once __DIR__ . '/config.php';

// Tidak boleh di-cache oleh browser maupun CDN
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

// Buang output buffer (jika ada whitespace/BOM dari include)
ob_end_clean();

echo json_encode(getSeatStatus());
