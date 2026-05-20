<?php
/**
 * admin/api/download_galeri.php
 * Download foto dari URL eksternal (Google Photos, dll),
 * kompres otomatis, simpan ke /public/galeri/,
 * kembalikan nama file yang tersimpan.
 */
ob_start(); // Tangkap output PHP error agar JSON tetap valid
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
adminBoot();

header('Content-Type: application/json; charset=utf-8');

$currentUser = apiRequireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean();
    apiJson(['error' => 'Method not allowed'], 405);
}

if (!function_exists('imagecreatefromjpeg')) {
    ob_end_clean();
    apiJson(['error' => 'GD Library tidak tersedia di server.'], 500);
}

$body   = jsonBody();
$action = $body['action'] ?? 'download_single';

// ── Helper: buat nama file slug ───────────────────────────────────────
function makeSlugFromUrl(string $url, string $judul = ''): string
{
    // Coba ambil nama dari judul dulu
    $base = $judul ?: '';
    if (!$base) {
        // Coba ambil dari akhir URL
        $path = parse_url($url, PHP_URL_PATH) ?: '';
        $base = pathinfo($path, PATHINFO_FILENAME) ?: 'galeri';
    }
    $map = [
        'à'=>'a','á'=>'a','â'=>'a','ã'=>'a','ä'=>'a',
        'è'=>'e','é'=>'e','ê'=>'e','ë'=>'e',
        'ì'=>'i','í'=>'i','î'=>'i','ï'=>'i',
        'ò'=>'o','ó'=>'o','ô'=>'o','õ'=>'o','ö'=>'o',
        'ù'=>'u','ú'=>'u','û'=>'u','ü'=>'u',
        'ý'=>'y','ñ'=>'n','ç'=>'c',
    ];
    $slug = strtolower(strtr($base, $map));
    $slug = preg_replace('/[^a-z0-9\s\-]/', '', $slug);
    $slug = preg_replace('/[\s\-]+/', '-', trim($slug));
    $slug = substr($slug, 0, 50) ?: 'galeri';
    return $slug;
}

function makeUniqueFilename(string $slug, string $ext, string $uploadDir): string
{
    $suffix   = date('Ymd');
    $filename = $slug . '-' . $suffix . '.' . $ext;
    $counter  = 1;
    while (file_exists($uploadDir . $filename)) {
        $filename = $slug . '-' . $suffix . '-' . $counter . '.' . $ext;
        $counter++;
    }
    return $filename;
}

// ── Helper: download URL → image resource ────────────────────────────
function isUrlSafe(string $url): bool
{
    if (!preg_match('#^https://#i', $url)) return false;
    $host = parse_url($url, PHP_URL_HOST);
    if (!$host) return false;
    $ip = gethostbyname($host);
    return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
}

function downloadImageFromUrl(string $url): array
{
    if (!isUrlSafe($url)) {
        return ['ok' => false, 'error' => 'URL tidak diizinkan. Hanya URL publik HTTPS yang dapat diunduh.'];
    }

    // Coba cURL dulu
    if (!function_exists('curl_init')) {
        return ['error' => 'cURL tidak tersedia di server.'];
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 5,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; ParokiSMDTBA/2.0)',
        CURLOPT_HTTPHEADER     => ['Accept: image/*,*/*'],
    ]);
    $data     = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr || $data === false) {
        return ['error' => 'Gagal download: ' . $curlErr];
    }
    if ($httpCode === 403) {
        return ['error' => 'Link tidak bisa diakses (403 Forbidden). Link mungkin sudah expired atau dibatasi.'];
    }
    if ($httpCode === 404) {
        return ['error' => 'File tidak ditemukan di URL tersebut (404).'];
    }
    if ($httpCode < 200 || $httpCode >= 300) {
        return ['error' => "Server mengembalikan HTTP {$httpCode}."];
    }
    if (strlen($data) < 100) {
        return ['error' => 'Data yang didownload terlalu kecil, bukan gambar valid.'];
    }

    // Deteksi mime type dari binary data
    $header   = substr($data, 0, 12);
    $mimeType = '';
    if (substr($header, 0, 2) === "\xFF\xD8")                               $mimeType = 'image/jpeg';
    elseif (substr($header, 0, 4) === "\x89PNG")                            $mimeType = 'image/png';
    elseif (substr($header, 0, 4) === 'RIFF' && substr($header, 8, 4) === 'WEBP') $mimeType = 'image/webp';
    elseif (substr($header, 0, 3) === 'GIF')                                $mimeType = 'image/gif';

    if (!in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp', 'image/gif'])) {
        // Cek apakah response adalah HTML (link expired Google)
        if (stripos(substr($data, 0, 200), '<html') !== false) {
            return ['error' => 'Link mengarah ke halaman HTML, bukan file gambar. Link mungkin sudah expired.'];
        }
        return ['error' => 'Data yang didownload bukan gambar yang dikenali (JPEG/PNG/WebP).'];
    }

    // Tulis ke file sementara
    $tmpFile = tempnam(sys_get_temp_dir(), 'galeri_');
    file_put_contents($tmpFile, $data);

    return [
        'tmp'      => $tmpFile,
        'mime'     => $mimeType,
        'size'     => strlen($data),
    ];
}

// ── Helper: proses kompres dan simpan ────────────────────────────────
function processAndSave(string $tmpFile, string $mimeType, string $slug, string $uploadDir): array
{
    $webpAvailable = function_exists('imagewebp') && function_exists('imagecreatefromwebp');
    $origKb        = round(filesize($tmpFile) / 1024, 1);

    $src = null;
    if ($mimeType === 'image/jpeg')                          $src = @imagecreatefromjpeg($tmpFile);
    elseif ($mimeType === 'image/png')                       $src = @imagecreatefrompng($tmpFile);
    elseif ($mimeType === 'image/webp' && $webpAvailable)   $src = @imagecreatefromwebp($tmpFile);
    elseif ($mimeType === 'image/gif')                       $src = @imagecreatefromgif($tmpFile);

    // GD gagal → simpan file mentah sebagai fallback
    if (!$src) {
        $ext      = ($mimeType === 'image/png') ? 'png' : 'jpg';
        $filename = makeUniqueFilename($slug, $ext, $uploadDir);
        if (!copy($tmpFile, $uploadDir . $filename)) {
            @unlink($tmpFile);
            return ['error' => 'Gagal menyimpan file gambar.'];
        }
        @unlink($tmpFile);
        return [
            'filename'   => $filename,
            'size_kb'    => $origKb,
            'orig_kb'    => $origKb,
            'compressed' => false,
            'format'     => strtoupper($ext),
        ];
    }

    $origW = imagesx($src);
    $origH = imagesy($src);

    // Target dimensi galeri thumbnail: maks 800×500
    $maxW = 800; $maxH = 500; $quality = 78;
    if ($origW > $maxW || $origH > $maxH) {
        $scale = min($maxW / $origW, $maxH / $origH);
        $newW  = max(1, (int)($origW * $scale));
        $newH  = max(1, (int)($origH * $scale));
    } else {
        $newW = $origW; $newH = $origH;
    }

    $dst   = imagecreatetruecolor($newW, $newH);
    $white = imagecolorallocate($dst, 255, 255, 255);
    imagefilledrectangle($dst, 0, 0, $newW, $newH, $white);
    if ($mimeType === 'image/png') imagealphablending($dst, true);
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
    imagedestroy($src);

    if ($webpAvailable) {
        $filename = makeUniqueFilename($slug, 'webp', $uploadDir);
        $savePath = $uploadDir . $filename;
        $saved    = imagewebp($dst, $savePath, $quality);
        $format   = 'WebP';
    } else {
        $filename = makeUniqueFilename($slug, 'jpg', $uploadDir);
        $savePath = $uploadDir . $filename;
        $saved    = imagejpeg($dst, $savePath, $quality);
        $format   = 'JPEG';
    }
    imagedestroy($dst);
    @unlink($tmpFile);

    if (!$saved || !file_exists($savePath)) {
        return ['error' => 'Gagal menyimpan gambar hasil kompresi.'];
    }

    $finalKb  = round(filesize($savePath) / 1024, 1);
    $savedPct = $origKb > 0 ? max(0, round((($origKb - $finalKb) / $origKb) * 100)) : 0;

    return [
        'filename'   => $filename,
        'size_kb'    => $finalKb,
        'orig_kb'    => $origKb,
        'compressed' => true,
        'saved_pct'  => $savedPct,
        'format'     => $format,
        'dimensions' => $newW . '×' . $newH . 'px',
    ];
}

// ── Siapkan folder ────────────────────────────────────────────────────
$uploadDir = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/public/galeri/';
if (!is_dir($uploadDir) && !@mkdir($uploadDir, 0755, true)) {
    ob_end_clean();
    apiJson(['error' => 'Folder /public/galeri/ tidak bisa dibuat.'], 500);
}
if (!is_writable($uploadDir)) {
    ob_end_clean();
    apiJson(['error' => 'Folder /public/galeri/ tidak bisa ditulis. Set permission 755.'], 500);
}

try {

// ════════════════════════════════════════════════════════════════════
// ACTION: download_single
// ════════════════════════════════════════════════════════════════════
if ($action === 'download_single') {
    $url   = trim($body['url']   ?? '');
    $judul = trim($body['judul'] ?? '');

    if (!$url) { ob_end_clean(); apiJson(['error' => 'URL tidak boleh kosong.'], 400); }
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        ob_end_clean();
        apiJson(['error' => 'Format URL tidak valid.'], 400);
    }

    $dl = downloadImageFromUrl($url);
    if (isset($dl['error'])) {
        ob_end_clean();
        apiJson(['error' => $dl['error']], 422);
    }

    $slug   = makeSlugFromUrl($url, $judul);
    $result = processAndSave($dl['tmp'], $dl['mime'], $slug, $uploadDir);

    if (isset($result['error'])) {
        ob_end_clean();
        apiJson(['error' => $result['error']], 500);
    }

    getLogger()->log($currentUser, 'CREATE', 'galeri',
        'Download thumbnail dari URL: ' . substr($url, 0, 80));

    ob_end_clean();
    apiJson([
        'success'    => true,
        'filename'   => $result['filename'],
        'url'        => '/public/galeri/' . $result['filename'],
        'size_kb'    => $result['size_kb'],
        'orig_kb'    => $result['orig_kb'],
        'saved_pct'  => $result['saved_pct'] ?? 0,
        'format'     => $result['format'],
        'dimensions' => $result['dimensions'] ?? '',
    ]);
}

// ════════════════════════════════════════════════════════════════════
// ACTION: batch_convert
// ════════════════════════════════════════════════════════════════════
if ($action === 'batch_convert') {
    $isSuperadmin = $currentUser['role'] === ROLE_SUPERADMIN;
    if (!$isSuperadmin) {
        $permsMap      = getPermissionsMap($currentUser);
        $galeriActions = $permsMap['galeri'] ?? [];
        if (!in_array('edit', $galeriActions)) {
            ob_end_clean();
            apiJson(['error' => 'Akses ditolak. Butuh izin edit galeri.'], 403);
        }
    }

    $db   = getDB();
    $rows = $db->read(TABLE_GALERI, [], 'id.asc', 'id,Judul,Gambar');

    $results = [];
    foreach ($rows as $row) {
        $id     = $row['id']     ?? null;
        $gambar = trim($row['Gambar'] ?? '');
        $judul  = trim($row['Judul']  ?? '');

        if (!$id || !$gambar) continue;

        // Skip jika sudah berupa nama file (bukan URL)
        if (!str_starts_with($gambar, 'http://') && !str_starts_with($gambar, 'https://')) {
            $results[] = ['id' => $id, 'judul' => $judul, 'status' => 'skip', 'reason' => 'sudah nama file'];
            continue;
        }

        // Download & proses
        $dl = downloadImageFromUrl($gambar);
        if (isset($dl['error'])) {
            $results[] = ['id' => $id, 'judul' => $judul, 'status' => 'error', 'reason' => $dl['error']];
            continue;
        }

        $slug   = makeSlugFromUrl($gambar, $judul);
        $result = processAndSave($dl['tmp'], $dl['mime'], $slug, $uploadDir);

        if (isset($result['error'])) {
            $results[] = ['id' => $id, 'judul' => $judul, 'status' => 'error', 'reason' => $result['error']];
            continue;
        }

        // Update Supabase: ganti link dengan nama file
        try {
            $db->update(TABLE_GALERI, 'id', $id, ['Gambar' => $result['filename']]);
            $results[] = [
                'id'        => $id,
                'judul'     => $judul,
                'status'    => 'ok',
                'filename'  => $result['filename'],
                'size_kb'   => $result['size_kb'],
                'orig_kb'   => $result['orig_kb'],
                'saved_pct' => $result['saved_pct'] ?? 0,
            ];
        } catch (Throwable $e) {
            $results[] = ['id' => $id, 'judul' => $judul, 'status' => 'error', 'reason' => 'Gagal update DB: ' . $e->getMessage()];
        }
    }

    $ok   = count(array_filter($results, fn($r) => $r['status'] === 'ok'));
    $skip = count(array_filter($results, fn($r) => $r['status'] === 'skip'));
    $err  = count(array_filter($results, fn($r) => $r['status'] === 'error'));

    getLogger()->log($currentUser, 'UPDATE', 'galeri',
        "Batch convert: {$ok} berhasil, {$skip} skip, {$err} gagal");

    ob_end_clean();
    apiJson([
        'success' => true,
        'summary' => ['ok' => $ok, 'skip' => $skip, 'error' => $err, 'total' => count($results)],
        'results' => $results,
    ]);
}

ob_end_clean();
apiJson(['error' => 'Action tidak dikenal: ' . $action], 400);

} catch (Throwable $e) {
    error_log('[download_galeri] ' . $e->getMessage());
    ob_end_clean();
    apiJson(['error' => 'Server error: ' . $e->getMessage()], 500);
}