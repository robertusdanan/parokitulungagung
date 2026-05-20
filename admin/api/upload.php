<?php
/**
 * /admin/api/upload.php
 * Endpoint upload file fisik ke /public/downloads/ (atau folder lain yang diizinkan)
 *
 * POST params:
 *   file   — file yang diupload (multipart/form-data)
 *   dest   — subfolder tujuan di /public/, default 'downloads'
 *
 * Response JSON:
 *   { success: true,  nama_file: "nama_aman.pdf" }
 *   { success: false, error: "pesan error" }
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
adminBoot();
requireLogin();

header('Content-Type: application/json; charset=utf-8');

// ── Validasi dest ──────────────────────────────────────────────────────
$dest    = trim($_POST['dest'] ?? 'downloads');

$allowed = ['downloads', 'jadwal_petugas'];

if (!in_array($dest, $allowed, true)) {
    echo json_encode(['success' => false, 'error' => 'Folder tujuan tidak diizinkan.']);
    exit;
}

// ── Validasi file ──────────────────────────────────────────────────────
$file = $_FILES['file'] ?? null;
if (!$file) {
    echo json_encode(['success' => false, 'error' => 'Tidak ada file yang dikirim.']);
    exit;
}
if ($file['error'] !== UPLOAD_ERR_OK) {
    $errMsg = [
        UPLOAD_ERR_INI_SIZE   => 'File melebihi batas upload_max_filesize di php.ini.',
        UPLOAD_ERR_FORM_SIZE  => 'File melebihi batas MAX_FILE_SIZE form.',
        UPLOAD_ERR_PARTIAL    => 'File hanya terupload sebagian.',
        UPLOAD_ERR_NO_FILE    => 'Tidak ada file yang dipilih.',
        UPLOAD_ERR_NO_TMP_DIR => 'Folder temp tidak ditemukan.',
        UPLOAD_ERR_CANT_WRITE => 'Gagal menulis file ke disk.',
        UPLOAD_ERR_EXTENSION  => 'Upload dihentikan oleh ekstensi PHP.',
    ][$file['error']] ?? 'Upload error: kode ' . $file['error'];
    echo json_encode(['success' => false, 'error' => $errMsg]);
    exit;
}

// ── Batasan ukuran (50 MB) ─────────────────────────────────────────────
$maxBytes = 50 * 1024 * 1024;
if ($file['size'] > $maxBytes) {
    echo json_encode(['success' => false, 'error' => 'Ukuran file melebihi batas 50 MB.']);
    exit;
}

// ── Sanitasi nama file ─────────────────────────────────────────────────
// Ambil nama asli, pertahankan ekstensi, ganti karakter non-aman
$origName = basename($file['name']);
$ext      = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
$baseName = pathinfo($origName, PATHINFO_FILENAME);

// Bersihkan: hanya huruf, angka, titik, strip, underscore
$safeName = preg_replace('/[^a-zA-Z0-9._\-]/', '_', $baseName);
$safeName = preg_replace('/_+/', '_', trim($safeName, '_'));
if (!$safeName) $safeName = 'file_' . time();

// Tambahkan timestamp agar tidak bentrok
$finalName = $safeName . '_' . time() . ($ext ? '.' . $ext : '');

// ── Pastikan folder ada ────────────────────────────────────────────────
$uploadDir = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/public/' . $dest . '/';
if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) {
        echo json_encode(['success' => false, 'error' => 'Gagal membuat folder tujuan.']);
        exit;
    }
}

$destPath = $uploadDir . $finalName;

// ── Pindahkan file ─────────────────────────────────────────────────────
if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    echo json_encode(['success' => false, 'error' => 'Gagal menyimpan file ke server. Periksa permission folder.']);
    exit;
}

// ── Log aktivitas ──────────────────────────────────────────────────────
try {
    $logger = getLogger();
    $logger->log(
        'upload',
        'dokumen_paroki',
        null,
        null,
        ['nama_file' => $finalName, 'ukuran' => $file['size'], 'dest' => $dest]
    );
} catch (Throwable $e) {
    // Log gagal tidak fatal
}

echo json_encode([
    'success'    => true,
    'nama_file'  => $finalName,
    'ukuran_bytes' => $file['size'],
    'ukuran'     => $file['size'] > 1048576
        ? round($file['size'] / 1048576, 1) . ' MB'
        : round($file['size'] / 1024, 0)    . ' KB',
]);
