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

// ── Whitelist ekstensi + verifikasi tipe file sebenarnya ────────────────
// SECURITY FIX: sebelumnya endpoint ini menerima file APAPUN (termasuk
// .php/.phtml) dan menyimpannya langsung ke /public/{dest}/, folder yang
// bisa diakses langsung lewat browser. Admin (atau siapapun yang berhasil
// membajak sesi admin) bisa upload web-shell dan langsung menjalankannya.
// Sekarang hanya tipe dokumen/gambar yang benar-benar dipakai di sini yang
// diizinkan, dan isi file dicek pakai magic bytes — bukan cuma nama file,
// karena ekstensi gampang dipalsukan.
$extMimeMap = [
    'pdf'  => ['application/pdf'],
    'doc'  => ['application/msword'],
    'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
    'xls'  => ['application/vnd.ms-excel'],
    'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'],
    'jpg'  => ['image/jpeg'],
    'jpeg' => ['image/jpeg'],
    'png'  => ['image/png'],
    'webp' => ['image/webp'],
];

if (!array_key_exists($ext, $extMimeMap)) {
    echo json_encode(['success' => false, 'error' => 'Jenis file .' . htmlspecialchars($ext) . ' tidak diizinkan. Hanya PDF, Word, Excel, JPG, PNG, atau WEBP.']);
    exit;
}

$actualMime = '';
if (function_exists('finfo_open')) {
    $finfo      = finfo_open(FILEINFO_MIME_TYPE);
    $actualMime = finfo_file($finfo, $file['tmp_name']) ?: '';
    finfo_close($finfo);
} elseif (function_exists('mime_content_type')) {
    $actualMime = mime_content_type($file['tmp_name']) ?: '';
}

if (!$actualMime || !in_array($actualMime, $extMimeMap[$ext], true)) {
    echo json_encode(['success' => false, 'error' => 'Isi file tidak sesuai dengan ekstensinya (terdeteksi: ' . htmlspecialchars($actualMime ?: 'tidak dikenali') . ').']);
    exit;
}

// Bersihkan: hanya huruf, angka, titik, strip, underscore
$safeName = preg_replace('/[^a-zA-Z0-9._\-]/', '_', $baseName);
$safeName = preg_replace('/_+/', '_', trim($safeName, '_'));
if (!$safeName) $safeName = 'file_' . time();

// Tambahkan timestamp agar tidak bentrok
$finalName = $safeName . '_' . time() . ($ext ? '.' . $ext : '');

// ── Upload ke Cloudflare R2 ───────────────────────────────────────────
$r2Prefix = ($dest === 'jadwal_petugas')
    ? (defined('R2_JADWAL_PETUGAS_PREFIX') ? R2_JADWAL_PETUGAS_PREFIX : 'jadwal_petugas/')
    : (defined('R2_DOWNLOADS_PREFIX') ? R2_DOWNLOADS_PREFIX : 'downloads/');

$r2Key = trim($r2Prefix, '/') . '/' . $finalName;

try {
    $r2 = getR2WriteClient();
    $r2->putObjectFromFile($r2Key, $file['tmp_name'], $actualMime, ['uploaded-via' => 'admin-upload-' . $dest]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'error' => 'Gagal upload ke Cloudflare R2: ' . $e->getMessage()]);
    exit;
}

$cdnUrl = (defined('R2_CDN_URL') ? rtrim(R2_CDN_URL, '/') : 'https://img.parokitulungagung.org') . '/' . $r2Key;

// ── Log aktivitas ──────────────────────────────────────────────────────
try {
    $logger = getLogger();
    $logger->log(
        'upload',
        $dest === 'jadwal_petugas' ? 'jadwal_petugas_gambar' : 'dokumen_paroki',
        null,
        null,
        ['nama_file' => $finalName, 'ukuran' => $file['size'], 'dest' => $dest, 'r2_key' => $r2Key]
    );
} catch (Throwable $e) {
    // Log gagal tidak fatal
}

echo json_encode([
    'success'      => true,
    'nama_file'    => $finalName,
    'url'          => $cdnUrl,
    'ukuran_bytes' => $file['size'],
    'ukuran'       => $file['size'] > 1048576
        ? round($file['size'] / 1048576, 1) . ' MB'
        : round($file['size'] / 1024, 0)    . ' KB',
]);
