<?php
/**
 * admin/api/upload_image.php
 * Upload gambar ke folder tertentu dengan kompresi otomatis via GD.
 *
 * POST params (multipart/form-data):
 *   file   — file gambar (jpg/png/gif/webp)
 *   folder — 'banner' atau 'icon'  (whitelist ketat)
 *
 * Respons JSON:
 *   { success: true, url: '/img/banner/namafile.webp' }
 *   { error: '...' }
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
adminBoot();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
apiRequireLogin();

$user = requireLogin();

// ── Permission ────────────────────────────────────────────────────────
$isSA     = $user['role'] === ROLE_SUPERADMIN;
$permsMap = $isSA ? [] : getPermissionsMap($user);
$canEdit  = $isSA || in_array('edit', $permsMap['kategorial'] ?? []);
if (!$canEdit) {
    apiJson(['error' => 'Akses ditolak'], 403);
}

// ── Validasi folder ───────────────────────────────────────────────────
$folderParam = $_POST['folder'] ?? '';
$ALLOWED_FOLDERS = [
    'banner' => '/img/banner',
    'icon'   => '/img/icon/kategorial',
    'person' => '/img/person',
];
if (!array_key_exists($folderParam, $ALLOWED_FOLDERS)) {
    apiJson(['error' => 'Folder tidak valid'], 400);
}
$relFolder  = $ALLOWED_FOLDERS[$folderParam];
$absFolder  = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . $relFolder;

// Buat folder jika belum ada
if (!is_dir($absFolder)) {
    if (!mkdir($absFolder, 0755, true)) {
        apiJson(['error' => 'Gagal membuat folder tujuan'], 500);
    }
}

// ── Validasi file upload ──────────────────────────────────────────────
if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    $uploadErrors = [
        UPLOAD_ERR_INI_SIZE   => 'File terlalu besar (php.ini)',
        UPLOAD_ERR_FORM_SIZE  => 'File terlalu besar (form)',
        UPLOAD_ERR_PARTIAL    => 'Upload tidak lengkap',
        UPLOAD_ERR_NO_FILE    => 'Tidak ada file yang dipilih',
        UPLOAD_ERR_NO_TMP_DIR => 'Folder temp tidak tersedia',
        UPLOAD_ERR_CANT_WRITE => 'Gagal menulis ke disk',
        UPLOAD_ERR_EXTENSION  => 'Upload diblokir ekstensi',
    ];
    $code = $_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE;
    apiJson(['error' => $uploadErrors[$code] ?? 'Upload gagal'], 400);
}

$tmpPath  = $_FILES['file']['tmp_name'];
$origName = $_FILES['file']['name'];
$origSize = $_FILES['file']['size'];

// Max 20 MB sebelum kompresi (gambar dikompres otomatis)
if ($origSize > 20 * 1024 * 1024) {
    apiJson(['error' => 'Ukuran file maksimal 20 MB. Gambar dikompres otomatis setelah upload.'], 400);
}

// Deteksi tipe MIME yang nyata (bukan dari ekstensi)
$finfo    = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $tmpPath);
finfo_close($finfo);

$ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
if (!in_array($mimeType, $ALLOWED_MIMES)) {
    apiJson(['error' => 'Format gambar tidak didukung. Gunakan JPG, PNG, GIF, atau WebP.'], 400);
}

// ── Kompresi & konversi ke WebP ───────────────────────────────────────
// Semua gambar disimpan sebagai .webp untuk efisiensi ukuran
if (!function_exists('imagecreatefromjpeg')) {
    apiJson(['error' => 'GD library tidak tersedia di server ini'], 500);
}

// Load ke GD berdasarkan MIME
$src = match ($mimeType) {
    'image/jpeg' => @imagecreatefromjpeg($tmpPath),
    'image/png'  => @imagecreatefrompng($tmpPath),
    'image/gif'  => @imagecreatefromgif($tmpPath),
    'image/webp' => @imagecreatefromwebp($tmpPath),
    default      => false,
};
if (!$src) {
    apiJson(['error' => 'Gagal memproses gambar. File mungkin rusak.'], 400);
}

$origW = imagesx($src);
$origH = imagesy($src);

// ── Aturan resize per folder ──────────────────────────────────────────
//   banner : max 1600×600 px  (hero background, landscape)
//   icon   : max 200×200 px   (ikon kecil, square)
if ($folderParam === 'banner') {
    $maxW = 1600; $maxH = 600;
    $quality = 82;
} elseif ($folderParam === 'person') {
    $maxW = 400; $maxH = 400;  // foto profil pengurus, square
    $quality = 85;
} else {
    $maxW = 200; $maxH = 200;
    $quality = 88;
}

// Hitung dimensi baru (proporsional, tidak memperbesar)
[$newW, $newH] = calcResize($origW, $origH, $maxW, $maxH);

// Buat canvas baru + preserve alpha (untuk PNG transparan)
$dst = imagecreatetruecolor($newW, $newH);
if ($mimeType === 'image/png' || $mimeType === 'image/gif') {
    imagealphablending($dst, false);
    imagesavealpha($dst, true);
    $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
    imagefilledrectangle($dst, 0, 0, $newW, $newH, $transparent);
    imagealphablending($dst, true);
}

imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
imagedestroy($src);

// ── Simpan sebagai WebP ───────────────────────────────────────────────
// Nama file: {slug-aman}_{timestamp}.webp
$baseName  = pathinfo($origName, PATHINFO_FILENAME);
$safeName  = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $baseName);
$safeName  = substr($safeName, 0, 60); // max 60 karakter
$fileName  = $safeName . '_' . time() . '.webp';
$destPath  = $absFolder . '/' . $fileName;

if (!imagewebp($dst, $destPath, $quality)) {
    imagedestroy($dst);
    apiJson(['error' => 'Gagal menyimpan gambar ke server'], 500);
}
imagedestroy($dst);

// ── Hitung penghematan ukuran ─────────────────────────────────────────
$newSize    = filesize($destPath);
$savedBytes = $origSize - $newSize;
$savedPct   = $origSize > 0 ? round($savedBytes / $origSize * 100) : 0;

$url = $relFolder . '/' . $fileName;

getLogger()->log($user, 'UPLOAD', 'kategorial', 'Upload gambar: ' . $url);

apiJson([
    'success'     => true,
    'url'         => $url,
    'filename'    => $fileName,
    'width'       => $newW,
    'height'      => $newH,
    'size_bytes'  => $newSize,
    'saved_pct'   => $savedPct,
    'saved_bytes' => $savedBytes,
]);

// ── Helper ────────────────────────────────────────────────────────────
function calcResize(int $w, int $h, int $maxW, int $maxH): array
{
    if ($w <= $maxW && $h <= $maxH) return [$w, $h]; // tidak perlu diperkecil
    $ratio = min($maxW / $w, $maxH / $h);
    return [max(1, (int)round($w * $ratio)), max(1, (int)round($h * $ratio))];
}