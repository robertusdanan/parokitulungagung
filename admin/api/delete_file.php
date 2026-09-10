<?php
/**
 * /admin/api/delete_file.php
 * Endpoint untuk menghapus file fisik dari folder /public/{dest}/
 *
 * POST params (JSON body):
 *   nama_file — nama file yang akan dihapus (hanya nama file, bukan path penuh)
 *   dest      — subfolder di /public/, default 'downloads'
 *
 * Response JSON:
 *   { success: true }
 *   { success: false, error: "pesan error" }
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
adminBoot();
requireLogin();

header('Content-Type: application/json; charset=utf-8');

$body     = jsonBody();
$namaFile = trim($body['nama_file'] ?? '');
$dest     = trim($body['dest'] ?? 'downloads');

// ── Validasi dest ──────────────────────────────────────────────────────
$allowed = ['downloads'];
if (!in_array($dest, $allowed, true)) {
    echo json_encode(['success' => false, 'error' => 'Folder tujuan tidak diizinkan.']);
    exit;
}

// ── Validasi nama file ─────────────────────────────────────────────────
if (!$namaFile) {
    echo json_encode(['success' => false, 'error' => 'Nama file tidak boleh kosong.']);
    exit;
}

// Cegah path traversal — nama file tidak boleh mengandung / atau \\ atau ..
if (strpos($namaFile, '/') !== false || strpos($namaFile, '\\') !== false || strpos($namaFile, '..') !== false) {
    echo json_encode(['success' => false, 'error' => 'Nama file tidak valid.']);
    exit;
}

// ── Hapus file ─────────────────────────────────────────────────────────
$filePath = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/public/' . $dest . '/' . $namaFile;

if (!file_exists($filePath)) {
    // File sudah tidak ada — anggap sukses (idempotent)
    echo json_encode(['success' => true, 'info' => 'File tidak ditemukan di server (sudah dihapus sebelumnya).']);
    exit;
}

if (!is_file($filePath)) {
    echo json_encode(['success' => false, 'error' => 'Target bukan file.']);
    exit;
}

if (!unlink($filePath)) {
    echo json_encode(['success' => false, 'error' => 'Gagal menghapus file. Periksa permission folder.']);
    exit;
}

// ── Log ────────────────────────────────────────────────────────────────
try {
    $logger = getLogger();
    $logger->log('delete_file', 'dokumen_paroki', null, null, ['nama_file' => $namaFile, 'dest' => $dest]);
} catch (Throwable $e) {
    // Log gagal tidak fatal
}

echo json_encode(['success' => true]);
