<?php
// ============================================================
// UPLOAD.PHP — Endpoint upload bukti transfer ke server lokal
// Menggantikan: Supabase Storage upload
// Simpan gambar ke: /uploads/payment-proofs/
// ============================================================

require_once __DIR__ . '/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method tidak diizinkan.']);
    exit;
}

// ── Validasi file ──────────────────────────────────────────
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    $uploadErrors = [
        UPLOAD_ERR_INI_SIZE   => 'File terlalu besar (melebihi batas server).',
        UPLOAD_ERR_FORM_SIZE  => 'File terlalu besar (melebihi batas form).',
        UPLOAD_ERR_PARTIAL    => 'File hanya terupload sebagian.',
        UPLOAD_ERR_NO_FILE    => 'Tidak ada file yang diupload.',
        UPLOAD_ERR_NO_TMP_DIR => 'Folder temporary tidak ditemukan.',
        UPLOAD_ERR_CANT_WRITE => 'Gagal menulis file ke disk.',
        UPLOAD_ERR_EXTENSION  => 'Upload dihentikan oleh ekstensi PHP.',
    ];
    $errCode = $_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE;
    $errMsg  = $uploadErrors[$errCode] ?? 'Upload gagal.';
    http_response_code(400);
    echo json_encode(['error' => $errMsg]);
    exit;
}

$file     = $_FILES['image'];

// Cek tipe MIME nyata (bukan dari client)
$finfo    = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($file['tmp_name']);
$allowed  = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/heic', 'image/heif'];

if (!in_array($mimeType, $allowed)) {
    http_response_code(400);
    echo json_encode(['error' => 'Format file tidak didukung. Gunakan JPG, PNG, atau WEBP.']);
    exit;
}

// ── Tentukan ekstensi ──────────────────────────────────────
$extMap = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
    'image/gif'  => 'gif',
    'image/heic' => 'jpg',
    'image/heif' => 'jpg',
];
$ext = $extMap[$mimeType] ?? 'jpg';

// ── Nama file dari ticket_number ───────────────────────────
$ticketNumber = preg_replace('/[^A-Za-z0-9\-_]/', '', $_POST['ticket_number'] ?? '');
if (!$ticketNumber) {
    $ticketNumber = 'TKT-' . time() . '-' . bin2hex(random_bytes(4));
}
// Selalu simpan sebagai .jpg karena hasil kompresi GD
$fileName = $ticketNumber . '.jpg';

// ── Buat folder jika belum ada ─────────────────────────────
$uploadDir = __DIR__ . '/' . UPLOAD_DIR . '/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// ── Kompresi gambar via GD (otomatis, berapapun ukuran asli) ─
$destination = $uploadDir . $fileName;

try {
    // Buat image resource dari tmp file
    switch ($mimeType) {
        case 'image/png':
            $srcImg = imagecreatefrompng($file['tmp_name']);
            break;
        case 'image/webp':
            $srcImg = imagecreatefromwebp($file['tmp_name']);
            break;
        case 'image/gif':
            $srcImg = imagecreatefromgif($file['tmp_name']);
            break;
        default: // jpeg, heic, heif
            $srcImg = @imagecreatefromjpeg($file['tmp_name']);
            if (!$srcImg) {
                // Fallback: coba imagecreatefromstring
                $srcImg = imagecreatefromstring(file_get_contents($file['tmp_name']));
            }
    }

    if (!$srcImg) {
        throw new Exception('Gagal membaca gambar.');
    }

    // Perbaiki orientasi EXIF (khusus JPEG dari kamera)
    if (function_exists('exif_read_data') && in_array($mimeType, ['image/jpeg', 'image/heic', 'image/heif'])) {
        $exif = @exif_read_data($file['tmp_name']);
        $orientation = $exif['Orientation'] ?? 1;
        switch ($orientation) {
            case 3: $srcImg = imagerotate($srcImg, 180, 0); break;
            case 6: $srcImg = imagerotate($srcImg, -90, 0); break;
            case 8: $srcImg = imagerotate($srcImg, 90, 0);  break;
        }
    }

    // Resize jika lebar > 1200px
    $origW = imagesx($srcImg);
    $origH = imagesy($srcImg);
    $maxW  = 1200;

    if ($origW > $maxW) {
        $newH   = (int) round(($origH * $maxW) / $origW);
        $dstImg = imagecreatetruecolor($maxW, $newH);

        // Pertahankan transparansi (PNG/GIF)
        imagealphablending($dstImg, false);
        imagesavealpha($dstImg, true);
        $transparent = imagecolorallocatealpha($dstImg, 255, 255, 255, 127);
        imagefilledrectangle($dstImg, 0, 0, $maxW, $newH, $transparent);

        imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, $maxW, $newH, $origW, $origH);
        imagedestroy($srcImg);
    } else {
        $dstImg = $srcImg;
    }

    // Simpan sebagai JPEG kualitas 75 (seimbang antara kualitas & ukuran)
    $quality = 75;
    imagejpeg($dstImg, $destination, $quality);
    imagedestroy($dstImg);

} catch (Exception $e) {
    // Fallback: simpan file asli tanpa kompresi
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        http_response_code(500);
        echo json_encode(['error' => 'Gagal menyimpan file ke server.']);
        exit;
    }
}

// ── Kembalikan URL publik ──────────────────────────────────
// URL relatif dari root project, bisa diakses browser
$publicUrl = '/' . trim(UPLOAD_DIR, '/') . '/' . $fileName;

echo json_encode([
    'success'  => true,
    'url'      => $publicUrl,
    'fileName' => $fileName,
]);
