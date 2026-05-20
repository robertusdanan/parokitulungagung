<?php
// ============================================================
// SEAT-STATUS.PHP — Endpoint publik untuk status tiket
// Dipanggil oleh JS saat halaman dibuka agar seatStatus selalu fresh
// ============================================================

require_once __DIR__ . '/config.php';

// Tidak boleh di-cache oleh browser maupun CDN
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

echo json_encode(getSeatStatus());
