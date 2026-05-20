<?php
/**
 * admin/api/profil.php
 * API pengaturan profil diri sendiri — Supabase Edition
 * - Ganti username, email, password, nama
 * - Upload foto profil (auto-kompres, simpan di /img/admin/profil/)
 */
ob_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
adminBoot();

header('Content-Type: application/json; charset=utf-8');

$currentUser = apiRequireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    apiJson(['error' => 'Method not allowed'], 405);
}

$body   = jsonBody();
$action = $_POST['action'] ?? ($body['action'] ?? '');

// ── Upload foto profil ────────────────────────────────────────────────
if ($action === 'upload_foto') {
    $file = $_FILES['foto'] ?? null;

    if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        $errMap = [
            UPLOAD_ERR_INI_SIZE   => 'File terlalu besar (batas server).',
            UPLOAD_ERR_FORM_SIZE  => 'File terlalu besar.',
            UPLOAD_ERR_PARTIAL    => 'Upload tidak lengkap, coba lagi.',
            UPLOAD_ERR_NO_FILE    => 'Tidak ada file yang dipilih.',
            UPLOAD_ERR_CANT_WRITE => 'Server tidak bisa menulis file.',
        ];
        apiJson(['error' => $errMap[$file['error'] ?? 0] ?? 'Upload gagal.'], 400);
    }

    if ($file['size'] > 2 * 1024 * 1024) {
        $mb = round($file['size'] / 1024 / 1024, 1);
        apiJson(['error' => "File terlalu besar ({$mb}MB). Maksimum 2MB."], 400);
    }

    $mimeType = '';
    if (function_exists('finfo_open')) {
        $fi = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($fi, $file['tmp_name']);
        finfo_close($fi);
    } else {
        $h = @fopen($file['tmp_name'], 'rb');
        if ($h) {
            $hdr = fread($h, 12); fclose($h);
            if (substr($hdr,0,2) === "\xFF\xD8")                            $mimeType = 'image/jpeg';
            elseif (substr($hdr,0,4) === "\x89PNG")                         $mimeType = 'image/png';
            elseif (substr($hdr,0,4)==='RIFF'&&substr($hdr,8,4)==='WEBP')  $mimeType = 'image/webp';
            else $mimeType = @mime_content_type($file['tmp_name']) ?: 'unknown';
        }
    }

    if (!in_array($mimeType, ['image/jpeg','image/png','image/webp'])) {
        apiJson(['error' => 'Format tidak didukung. Gunakan JPG atau PNG.'], 400);
    }
    if (!function_exists('imagecreatefromjpeg')) {
        apiJson(['error' => 'GD Library tidak tersedia di server.'], 500);
    }

    $uploadDir = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/img/admin/profil/';
    if (!is_dir($uploadDir) && !@mkdir($uploadDir, 0755, true)) {
        apiJson(['error' => 'Gagal membuat folder /img/admin/profil/ — buat manual di cPanel.'], 500);
    }
    if (!is_writable($uploadDir)) {
        apiJson(['error' => 'Folder /img/admin/profil/ tidak bisa ditulis. Set permission 755.'], 500);
    }

    $src = null;
    if ($mimeType === 'image/jpeg') $src = @imagecreatefromjpeg($file['tmp_name']);
    elseif ($mimeType === 'image/png')  $src = @imagecreatefrompng($file['tmp_name']);
    elseif ($mimeType === 'image/webp' && function_exists('imagecreatefromwebp'))
        $src = @imagecreatefromwebp($file['tmp_name']);

    if (!$src) {
        $ext      = $mimeType === 'image/png' ? 'png' : 'jpg';
        $filename = 'profil-' . $currentUser['id'] . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
            apiJson(['error' => 'Gagal menyimpan foto.'], 500);
        }
        apiJson(['success' => true, 'path' => '/img/admin/profil/' . $filename]);
    }

    $origW = imagesx($src); $origH = imagesy($src);
    $size  = min($origW, $origH);
    $srcX  = (int)(($origW - $size) / 2);
    $srcY  = (int)(($origH - $size) / 2);

    $dst   = imagecreatetruecolor(200, 200);
    $white = imagecolorallocate($dst, 255, 255, 255);
    imagefilledrectangle($dst, 0, 0, 200, 200, $white);
    imagecopyresampled($dst, $src, 0, 0, $srcX, $srcY, 200, 200, $size, $size);
    imagedestroy($src);

    $webpOk   = function_exists('imagewebp');
    $filename = 'profil-' . $currentUser['id'] . ($webpOk ? '.webp' : '.jpg');
    $savePath = $uploadDir . $filename;
    $origKb   = round($file['size'] / 1024, 1);

    if ($webpOk) imagewebp($dst, $savePath, 80);
    else         imagejpeg($dst, $savePath, 82);
    imagedestroy($dst);

    if (!file_exists($savePath)) {
        apiJson(['error' => 'Gagal menyimpan foto ke server.'], 500);
    }

    apiJson([
        'success'  => true,
        'path'     => '/img/admin/profil/' . $filename,
        'size_kb'  => round(filesize($savePath) / 1024, 1),
        'orig_kb'  => $origKb,
        'format'   => $webpOk ? 'WebP' : 'JPEG',
    ]);
}

// ── Update profil (username / email / password / nama) ────────────────
if ($action === 'update') {
    $newUsername = trim($body['username'] ?? '');
    $newNama     = trim($body['nama']     ?? '');
    $newEmail    = trim($body['email']    ?? '');
    $oldPassword = $body['old_password']  ?? '';
    $newPassword = $body['new_password']  ?? '';

    if (!$newUsername) apiJson(['error' => 'Username tidak boleh kosong.'], 400);
    if ($newNama !== '' && mb_strlen($newNama) > 60) apiJson(['error' => 'Nama tampilan maksimal 60 karakter.'], 400);
    if (strlen($newUsername) < 3 || strlen($newUsername) > 64) apiJson(['error' => 'Username minimal 3 dan maksimal 64 karakter.'], 400);
    if (preg_match('/\s/', $newUsername)) apiJson(['error' => 'Username tidak boleh mengandung spasi.'], 400);

    $um     = new UserManager(getDB());
    $logger = getLogger();

    $self = $um->findById($currentUser['id']);
    if (!$self) {
        apiJson(['error' => 'Data user tidak ditemukan. Coba logout dan login kembali.'], 404);
    }

    $usernameChanged = ($newUsername !== $self['username']);
    $warnings = [];

    if ($usernameChanged) {
        $existing = $um->findByUsername($newUsername);
        if ($existing && $existing['id'] !== $currentUser['id']) {
            apiJson(['error' => "Username \"{$newUsername}\" sudah digunakan oleh user lain."], 409);
        }
        $warnings[] = 'Username berhasil diganti. Catatan: artikel yang sudah ditulis sebelumnya masih tercatat atas nama username lama.';
    }

    if (!empty($newPassword)) {
        if (strlen($newPassword) < 8) apiJson(['error' => 'Password baru minimal 8 karakter.'], 400);
        if (empty($oldPassword))      apiJson(['error' => 'Masukkan password lama untuk mengganti password.'], 400);
        if (!password_verify($oldPassword, $self['password_hash'])) {
            apiJson(['error' => 'Password lama tidak sesuai.'], 400);
        }
    }

    $updateData = [
        'username' => $newUsername,
        'nama'     => $newNama,
        'email'    => $newEmail,
    ];
    if (!empty($newPassword)) $updateData['password'] = $newPassword;

    $ok = $um->updateSelf($currentUser['id'], $updateData);
    if (!$ok) {
        apiJson(['error' => 'User tidak ditemukan di database. Coba logout dan login kembali.'], 500);
    }

    // Update nama penulis di artikel flat-file JSON jika nama berubah
    if (!empty($newNama)) {
        $oldIdentifiers = array_unique(array_filter([
            $self['username'] ?? '',
            $self['nama']     ?? '',
        ]));
        $artBasePath = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/articles';
        foreach (['berita','kronik','historia'] as $menu) {
            $dir = $artBasePath . '/' . $menu;
            if (!is_dir($dir)) continue;
            foreach (glob($dir . '/*.json') ?: [] as $file) {
                $artJson = @file_get_contents($file);
                if (!$artJson) continue;
                $art = json_decode($artJson, true);
                if (!is_array($art)) continue;
                if (in_array($art['penulis'] ?? '', $oldIdentifiers)) {
                    $art['penulis'] = $newNama;
                    @file_put_contents($file, json_encode($art, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
                }
            }
        }
    }

    // Update session agar langsung efektif tanpa logout
    $_SESSION['admin_user']['username'] = $newUsername;
    $_SESSION['admin_user']['email']    = $newEmail;
    $_SESSION['admin_user']['nama']     = $newNama;

    $logger->log($currentUser, 'UPDATE', 'profil',
        'Update profil: ' . ($usernameChanged ? "username → {$newUsername}" : 'email/password'));

    apiJson([
        'success'  => true,
        'username' => $newUsername,
        'nama'     => $newNama,
        'email'    => $newEmail,
        'warnings' => $warnings,
    ]);
}

// ── Hapus foto profil ─────────────────────────────────────────────────
if ($action === 'hapus_foto') {
    $dir = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/img/admin/profil/';
    foreach (['webp', 'jpg', 'png'] as $ext) {
        $f = $dir . 'profil-' . $currentUser['id'] . '.' . $ext;
        if (file_exists($f)) @unlink($f);
    }
    getLogger()->log($currentUser, 'DELETE', 'profil', 'Hapus foto profil');
    apiJson(['success' => true]);
}

apiJson(['error' => 'Action tidak dikenal.'], 400);
