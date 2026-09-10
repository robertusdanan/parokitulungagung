<?php
/**
 * admin/api/upload_image.php
 * Upload gambar ke folder tertentu dengan kompresi otomatis via GD.
 *
 * POST params (multipart/form-data):
 *   file   — file gambar (jpg/png/gif/webp)
 *   folder — 'banner' | 'icon' | 'person'  (whitelist ketat)
 *
 * Folder 'person' akan ditulis ke Cloudflare R2 (prefix R2_PERSON_PREFIX),
 * bukan ke disk lokal. Responsnya menggunakan URL absolut CDN R2.
 *
 * Respons JSON:
 *   { success: true, url: 'https://img.parokitulungagung.org/person/nama.webp' }
 *   { error: '...' }
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../../includes/R2WriteClient.php';
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
    'assets'         => '/assets',               // R2 assets
    'banner'         => '/assets/banner',        // R2 assets/banner
    'icon'           => '/icon',                 // R2 root icon
    'icon_kategorial'=> '/icon/kategorial',      // R2 icon kategorial
    'person'         => '/person',               // R2 person
    'artikel'        => '/artikel',              // R2 artikel
    'ogpreview'      => '/ogpreview',            // R2 ogpreview
];
if (!array_key_exists($folderParam, $ALLOWED_FOLDERS)) {
    apiJson(['error' => 'Folder tidak valid'], 400);
}
$relFolder = $ALLOWED_FOLDERS[$folderParam];

// Semua folder gambar sekarang disimpan di Cloudflare R2
$isR2Folder = true;
$absFolder  = $isR2Folder ? '' : rtrim($_SERVER['DOCUMENT_ROOT'], '/') . $relFolder;
if (!$isR2Folder) {
    if (!is_dir($absFolder)) {
        if (!mkdir($absFolder, 0755, true)) {
            apiJson(['error' => 'Gagal membuat folder tujuan'], 500);
        }
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

// Untuk folder R2 (person, icon/kategorial), tulis ke temp lalu push ke R2.
if ($isR2Folder) {
    if (!defined('SECRET_R2_ACCESS_KEY_WRITE') || !defined('SECRET_R2_SECRET_KEY_WRITE')
        || !defined('SECRET_R2_ENDPOINT') || !defined('SECRET_R2_BUCKET')) {
        imagedestroy($dst);
        apiJson(['error' => 'Konfigurasi R2 write belum lengkap di private/secrets.php.'], 500);
    }
    // Tentukan prefix R2 sesuai folder
    [$r2prefix, $logTag] = match ($folderParam) {
        'assets'         => [(defined('R2_ASSETS_PREFIX')   ? R2_ASSETS_PREFIX   : 'assets/'),
                             'image-assets'],
        'banner'         => [(defined('R2_ASSETS_PREFIX')   ? R2_ASSETS_PREFIX . 'banner/' : 'assets/banner/'),
                             'image-banner'],
        'icon'           => [(defined('R2_ICON_PREFIX')     ? R2_ICON_PREFIX     : 'icon/'),
                             'icon'],
        'icon_kategorial'=> [(defined('R2_ICON_KAT_PREFIX') ? R2_ICON_KAT_PREFIX : 'icon/kategorial/'),
                             'icon-kategorial'],
        'person'         => [(defined('R2_PERSON_PREFIX')   ? R2_PERSON_PREFIX   : 'person/'),
                             'image-person'],
        'artikel'        => [(defined('R2_ARTIKEL_PREFIX')  ? R2_ARTIKEL_PREFIX  : 'artikel/'),
                             'image-artikel'],
        'ogpreview'      => [(defined('R2_OG_PREFIX')       ? R2_OG_PREFIX       : 'ogpreview/'),
                             'image-ogpreview'],
        default          => ['assets/', 'image-assets'],
    };
    $r2key    = rtrim($r2prefix, '/') . '/' . $fileName;
    $tmpOut   = tempnam(sys_get_temp_dir(), 'pi_') . '.webp';
    if (!imagewebp($dst, $tmpOut, $quality)) {
        imagedestroy($dst);
        @unlink($tmpOut);
        apiJson(['error' => 'Gagal mengompres gambar'], 500);
    }
    imagedestroy($dst);
    $newSize = filesize($tmpOut);
    $r2 = new R2WriteClient(
        SECRET_R2_ACCESS_KEY_WRITE, SECRET_R2_SECRET_KEY_WRITE,
        SECRET_R2_ENDPOINT, SECRET_R2_BUCKET
    );
    $uploadErr = null;
    try { $r2->putObjectFromFile($r2key, $tmpOut, 'image/webp', ['uploaded-via' => 'admin-' . $logTag]); }
    catch (Throwable $e) { $uploadErr = $e->getMessage(); }
    @unlink($tmpOut);
    if ($uploadErr !== null) {
        apiJson(['error' => 'Gagal upload ke R2: ' . $uploadErr], 500);
    }
    $savedBytes = $origSize - $newSize;
    $savedPct   = $origSize > 0 ? round($savedBytes / $origSize * 100) : 0;
    $cdnUrl     = rtrim(defined('R2_CDN_URL') ? R2_CDN_URL : '', '/') . '/' . $r2key;
    getLogger()->log($user, 'UPLOAD', 'kategorial', 'Upload ' . $logTag . ' (R2): ' . $r2key);
    apiJson([
        'success'     => true,
        'url'         => $cdnUrl,
        'filename'    => $fileName,
        'r2_key'      => $r2key,
        'width'       => $newW,
        'height'      => $newH,
        'size_bytes'  => $newSize,
        'saved_pct'   => $savedPct,
        'saved_bytes' => $savedBytes,
    ]);
}

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