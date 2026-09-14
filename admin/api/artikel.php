<?php
ob_start();
/**
 * admin/api/artikel.php — API CRUD Artikel via Supabase
 * + Auto-konversi OG Preview saat artikel disimpan dengan thumbnail
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../../includes/SupabaseArticleManager.php';
require_once __DIR__ . '/../includes/UserManager.php';
require_once __DIR__ . '/../includes/Mailer.php';
adminBoot();

header('Content-Type: application/json; charset=utf-8');

$body   = jsonBody();
$action = $body['action'] ?? '';
$menu   = $body['menu']   ?? '';

if (!in_array($menu, SupabaseArticleManager::MENUS)) {
    apiJson(['error' => 'Menu tidak valid: ' . $menu], 400);
}

$currentUser = apiRequirePageAccess($menu, 'list');

// ═══════════════════════════════════════════════════════════════════
// HELPER: Role Checker
// ═══════════════════════════════════════════════════════════════════

function isSuperAdmin(array $user): bool {
    return $user['role'] === ROLE_SUPERADMIN;
}
function isEditor(array $user, string $menu): bool {
    if (isSuperAdmin($user)) return true;
    $perms = getPermissionsMap($user);
    return in_array('publish', $perms[$menu] ?? []);
}
function isOwnArtikel(array $user, array $art): bool {
    $penulis  = $art['penulis'] ?? '';
    $nama     = trim($user['nama'] ?? '');
    $username = $user['username'] ?? '';
    return $penulis === $username || ($nama !== '' && $penulis === $nama);
}
function canEditArtikel(array $user, string $menu, array $art): bool {
    if (isSuperAdmin($user)) return true;
    return isOwnArtikel($user, $art);
}
function canDeleteArtikel(array $user, string $menu, array $art): bool {
    return canEditArtikel($user, $menu, $art);
}
function canPublishArtikel(array $user, string $menu): bool {
    return isEditor($user, $menu);
}
function canViewArtikel(array $user, string $menu, array $art): bool {
    if (isSuperAdmin($user) || isEditor($user, $menu)) return true;
    return ($art['status'] ?? '') === 'published' || isOwnArtikel($user, $art);
}
function getDisplayName(array $user): string {
    $nama = trim($user['nama'] ?? '');
    return $nama !== '' ? $nama : ($user['username'] ?? '');
}

// ═══════════════════════════════════════════════════════════════════
// HELPER: Auto OG Preview Conversion
// ═══════════════════════════════════════════════════════════════════

/**
 * Konversi thumbnail artikel ke JPG 1200×630 untuk OG WhatsApp/Facebook.
 * Dipanggil otomatis setelah artikel disimpan.
 *
 * @param  string $thumbnailUrl  URL thumbnail, mis. /img/artikel/thumb-xxx.webp
 * @return array  ['success'=>bool, 'og_url'=>string|null, 'note'=>string]
 */
function autoConvertOG(string $thumbnailUrl): array {
    if (!$thumbnailUrl) return ['success' => false, 'note' => 'Tidak ada thumbnail'];

    $root = rtrim($_SERVER['DOCUMENT_ROOT'], '/');

    // Ambil konten gambar sumber (lokal atau remote R2 CDN)
    $thumbPath = preg_replace('#^https?://[^/]+#', '', $thumbnailUrl);
    $thumbPath = '/' . ltrim($thumbPath, '/');
    $srcFull = $root . $thumbPath;

    $imgData = null;
    if (file_exists($srcFull)) {
        $imgData = @file_get_contents($srcFull);
    } elseif (filter_var($thumbnailUrl, FILTER_VALIDATE_URL)) {
        $imgData = @file_get_contents($thumbnailUrl);
    } else {
        $cdnUrl = function_exists('artikelImageUrl') ? artikelImageUrl($thumbnailUrl) : ('https://img.parokitulungagung.org/artikel/' . basename($thumbnailUrl));
        $imgData = @file_get_contents($cdnUrl);
    }

    if (!$imgData) {
        return ['success' => false, 'note' => 'Gambar thumbnail tidak dapat diakses'];
    }

    if (!function_exists('imagecreatefromstring')) {
        return ['success' => false, 'note' => 'GD Library tidak tersedia'];
    }

    $im = @imagecreatefromstring($imgData);
    if (!$im) return ['success' => false, 'note' => 'GD gagal membaca thumbnail'];

    // Nama & path OG — TANPA prefix "og-", cukup ganti ekstensi ke .jpg
    $thumbBase  = pathinfo(basename($thumbnailUrl), PATHINFO_FILENAME);
    $ogName     = $thumbBase . '.jpg';
    $ogDir      = $root . '/img/ogpreview';
    if (!is_dir($ogDir)) @mkdir($ogDir, 0755, true);
    $ogFull     = $ogDir . '/' . $ogName;

    // Target: 1200×630 (crop center)
    $srcW = imagesx($im);
    $srcH = imagesy($im);
    $dstW = 1200;
    $dstH = 630;

    $srcRatio = $srcW / $srcH;
    $dstRatio = $dstW / $dstH;
    if ($srcRatio > $dstRatio) {
        $cropH = $srcH;
        $cropW = (int)($srcH * $dstRatio);
        $cropX = (int)(($srcW - $cropW) / 2);
        $cropY = 0;
    } else {
        $cropW = $srcW;
        $cropH = (int)($srcW / $dstRatio);
        $cropX = 0;
        $cropY = (int)(($srcH - $cropH) / 2);
    }

    $out   = imagecreatetruecolor($dstW, $dstH);
    $white = imagecolorallocate($out, 255, 255, 255);
    imagefill($out, 0, 0, $white);
    imagecopyresampled($out, $im, 0, 0, $cropX, $cropY, $dstW, $dstH, $cropW, $cropH);
    imagedestroy($im);

    $tmpFull = tempnam(sys_get_temp_dir(), 'og_') . '.jpg';
    $ok      = imagejpeg($out, $tmpFull, 85);
    imagedestroy($out);

    if (!$ok || !file_exists($tmpFull)) {
        @unlink($tmpFull);
        return ['success' => false, 'note' => 'Gagal membuat file OG'];
    }

    $ogKb = round(filesize($tmpFull) / 1024, 1);

    // Upload ke Cloudflare R2 bucket parokitulungagung/ogpreview/
    require_once __DIR__ . '/../../includes/R2WriteClient.php';
    $ak = defined('SECRET_R2_ACCESS_KEY_WRITE') ? SECRET_R2_ACCESS_KEY_WRITE : (defined('R2_ACCESS_KEY') ? R2_ACCESS_KEY : null);
    $sk = defined('SECRET_R2_SECRET_KEY_WRITE') ? SECRET_R2_SECRET_KEY_WRITE : (defined('R2_SECRET_KEY') ? R2_SECRET_KEY : null);
    $ep = defined('SECRET_R2_ENDPOINT') ? SECRET_R2_ENDPOINT : (defined('R2_ENDPOINT') ? R2_ENDPOINT : null);
    $bk = defined('SECRET_R2_BUCKET') ? SECRET_R2_BUCKET : (defined('R2_BUCKET') ? R2_BUCKET : null);

    if ($ak && $sk && $ep && $bk) {
        try {
            $r2 = new R2WriteClient($ak, $sk, $ep, $bk);
            $r2->putObjectFromFile('ogpreview/' . $ogName, $tmpFull, 'image/jpeg');
        } catch (Throwable $e) {
            error_log('[autoConvertOG] R2 upload failed: ' . $e->getMessage());
        }
    }

    if (is_dir($ogDir) && is_writable($ogDir)) {
        if (file_exists($ogFull)) @unlink($ogFull);
        @copy($tmpFull, $ogFull);
    }
    @unlink($tmpFull);

    $cdn = defined('R2_CDN_URL') ? rtrim(R2_CDN_URL, '/') : 'https://img.parokitulungagung.org';
    $ogUrl = $cdn . '/ogpreview/' . $ogName;

    // Update cache OG
    $cacheFile = $root . '/cache/media/media_og.json';
    $cacheDir  = dirname($cacheFile);
    if (!is_dir($cacheDir)) @mkdir($cacheDir, 0755, true);
    $cache = [];
    if (file_exists($cacheFile)) {
        $raw = @file_get_contents($cacheFile);
        if ($raw) $cache = json_decode($raw, true) ?: [];
    }
    $cache[$ogName] = [
        'at'      => date('Y-m-d H:i:s'),
        'src'     => basename($thumbnailUrl),
        'orig_kb' => (int)round(strlen($imgData) / 1024),
        'og_kb'   => (int)$ogKb,
        'size'    => '1200x630',
        'auto'    => true,
    ];
    @file_put_contents($cacheFile, json_encode($cache, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

    return [
        'success' => true,
        'og_url'  => $ogUrl,
        'og_name' => $ogName,
        'og_kb'   => $ogKb,
        'note'    => 'OG preview dibuat otomatis ke Cloudflare R2',
    ];
}


/**
 * Buat template email HTML bernuansa elegan untuk notifikasi catatan revisi artikel
 */
function _buildArticleRevisionEmail(string $nama, string $judul, string $menu, string $catatan, string $reviewer, string $editorUrl): string {
    $menuLabels = [
        'berita'   => 'Artikel & Berita',
        'kronik'   => 'Kronik SMDTBA',
        'historia' => 'Historia Gereja',
    ];
    $menuName = $menuLabels[$menu] ?? ucfirst($menu);
    $logoUrl = 'https://img.parokitulungagung.org/assets/smdtba.png';
    $catatanHtml = nl2br(htmlspecialchars($catatan, ENT_QUOTES, 'UTF-8'));
    $judulEsc = htmlspecialchars($judul, ENT_QUOTES, 'UTF-8');
    $reviewerEsc = htmlspecialchars($reviewer ?: 'Tim Redaksi', ENT_QUOTES, 'UTF-8');

    return <<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Catatan Revisi Artikel</title>
</head>
<body style="margin:0;padding:0;background:#f4f1eb;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;-webkit-font-smoothing:antialiased">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f1eb;padding:36px 16px">
  <tr><td align="center">
    <table width="560" cellpadding="0" cellspacing="0" style="max-width:560px;width:100%;background:#ffffff;border-radius:14px;overflow:hidden;box-shadow:0 6px 28px rgba(0,0,0,0.08);border:1px solid #e8dfd5">
      
      <!-- Header Tema Elegan -->
      <tr><td style="background:linear-gradient(135deg,#1c1815 0%,#28201a 100%);padding:30px 36px;text-align:center;border-bottom:3px solid #c9a84c">
        <img src="{$logoUrl}"
             alt="Logo Paroki SMDTBA"
             width="72" height="72"
             style="width:72px;height:72px;object-fit:contain;display:block;margin:0 auto 12px">
        <div style="font-family:'Georgia',serif;font-size:20px;color:#f5debb;font-weight:700;letter-spacing:0.02em">Paroki SMDTBA Tulungagung</div>
      </td></tr>
      
      <!-- Body Konten -->
      <tr><td style="padding:36px 36px 28px">
        <div style="display:inline-block;background:#fef3c7;border:1px solid #f59e0b;color:#b45309;font-size:11.5px;font-weight:700;padding:4px 12px;border-radius:20px;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:18px">
          ⚠️ Catatan Revisi Artikel
        </div>
        
        <p style="margin:0 0 6px;font-size:15px;color:#2e2013;line-height:1.5">Halo, <strong>{$nama}</strong></p>
        <p style="margin:0 0 20px;font-size:14px;color:#5a4a38;line-height:1.65">
          Artikel Anda pada rubrik <strong>{$menuName}</strong> telah ditinjau oleh redaksi dan memerlukan beberapa perbaikan sebelum dapat dipublikasikan.
        </p>

        <!-- Box Judul Artikel -->
        <div style="background:#faf7f2;border:1px solid #e8dfd5;border-radius:8px;padding:14px 18px;margin-bottom:20px">
          <div style="font-size:11px;color:#8c7e70;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:3px">Judul Artikel:</div>
          <div style="font-size:15px;font-weight:600;color:#1c1815;line-height:1.4">{$judulEsc}</div>
        </div>

        <!-- Box Catatan Revisi / Saran Redaksi -->
        <div style="background:#fffbeb;border:1px solid #fcd34d;border-left:4px solid #f59e0b;border-radius:8px;padding:16px 20px;margin-bottom:26px">
          <div style="font-size:12px;font-weight:700;color:#92400e;margin-bottom:8px;display:flex;align-items:center;gap:6px">
            📝 Catatan Perbaikan dari Redaksi ({$reviewerEsc}):
          </div>
          <div style="font-size:13.5px;color:#292015;line-height:1.65;font-style:normal">
            {$catatanHtml}
          </div>
        </div>

        <!-- Tombol CTA -->
        <table cellpadding="0" cellspacing="0" style="margin:0 auto 24px;width:100%">
          <tr><td align="center" style="background:#d97706;border-radius:8px;padding:14px 28px;box-shadow:0 4px 14px rgba(217,119,6,0.3)">
            <a href="{$editorUrl}" style="color:#ffffff;font-size:14.5px;font-weight:700;text-decoration:none;letter-spacing:.02em;display:inline-block">
              Edit &amp; Perbaiki Artikel Sekarang &rarr;
            </a>
          </td></tr>
        </table>

        <!-- Info Alur -->
        <div style="background:#f8f9fa;border:1px solid #e9ecef;border-radius:8px;padding:12px 16px;margin-bottom:20px">
          <p style="margin:0;font-size:12px;color:#5a6268;line-height:1.6">
            💡 <strong>Petunjuk:</strong> Setelah Anda melakukan perbaikan dan mengklik <strong>Simpan</strong>, status artikel akan otomatis kembali ke <strong>Draft</strong> untuk ditinjau ulang oleh tim redaksi.
          </p>
        </div>

        <p style="margin:0 0 6px;font-size:12px;color:#8a7a6a">
          Jika tombol di atas tidak berfungsi, salin dan buka tautan berikut di browser:
        </p>
        <div style="background:#f8f3eb;border:1px solid #e8dfc8;border-radius:6px;padding:8px 12px;margin-bottom:10px">
          <a href="{$editorUrl}" style="font-size:11px;color:#b45309;word-break:break-all">{$editorUrl}</a>
        </div>
      </td></tr>

      <!-- Footer -->
      <tr><td style="border-top:1px solid #f0ebe0;padding:18px 36px;text-align:center;background:#faf7f2">
        <p style="margin:0 0 4px;font-size:11.5px;color:#8c7e70;font-weight:500">
          Komsos &amp; Redaksi Paroki Santa Maria dengan Tidak Bernoda Asal
        </p>
        <p style="margin:0;font-size:11px;color:#b0a090">
          Jl. Jend. A. Yani No. 37, Tulungagung &middot; Email ini dikirim otomatis oleh sistem
        </p>
      </td></tr>

    </table>
  </td></tr>
</table>
</body>
</html>
HTML;
}

// ═══════════════════════════════════════════════════════════════════
// ACTION HANDLER
// ═══════════════════════════════════════════════════════════════════

if (!isset($action) || !$action) {
    apiJson(['error' => 'Action tidak boleh kosong'], 400);
}

try {
    $am     = new SupabaseArticleManager(getDB());
    $logger = getLogger();

    switch ($action) {

        // ── LIST ──────────────────────────────────────────────────────────
        case 'list':
            $all = $am->getAll($menu);
            $visible = array_values(array_filter($all, fn($a) =>
                canViewArtikel($currentUser, $menu, $a)
            ));
            $visible = array_map(function($art) use ($currentUser, $menu) {
                $art['_can_edit']    = canEditArtikel($currentUser, $menu, $art);
                $art['_can_delete']  = canDeleteArtikel($currentUser, $menu, $art);
                $art['_can_publish'] = canPublishArtikel($currentUser, $menu);
                $art['_is_own']      = isOwnArtikel($currentUser, $art);
                return $art;
            }, $visible);
            apiJson(['success' => true, 'data' => $visible]);
            break;

        // ── STATS ─────────────────────────────────────────────────────────
        case 'stats':
            $result = [];
            foreach (SupabaseArticleManager::MENUS as $m) {
                $permsMap = isSuperAdmin($currentUser) ? [] : getPermissionsMap($currentUser);
                if (isSuperAdmin($currentUser) || array_key_exists($m, $permsMap)) {
                    $allArts = $am->getAll($m);
                    if (!isSuperAdmin($currentUser) && !isEditor($currentUser, $m)) {
                        $allArts = array_filter($allArts, fn($a) => canViewArtikel($currentUser, $m, $a));
                    }
                    $allArts    = array_values($allArts);
                    $result[$m] = [
                        'total'     => count($allArts),
                        'published' => count(array_filter($allArts, fn($a) => ($a['status'] ?? '') === 'published')),
                        'draft'     => count(array_filter($allArts, fn($a) => ($a['status'] ?? '') !== 'published')),
                    ];
                }
            }
            apiJson(['success' => true, 'data' => $result]);
            break;

        // ── GET SINGLE ────────────────────────────────────────────────────
        case 'get':
            $id  = $body['id'] ?? '';
            $art = $am->getById($menu, $id);
            if (!$art) apiJson(['error' => 'Artikel tidak ditemukan'], 404);
            if (!canViewArtikel($currentUser, $menu, $art)) {
                apiJson(['error' => 'Anda tidak memiliki akses ke artikel ini'], 403);
            }
            $art['_can_edit']    = canEditArtikel($currentUser, $menu, $art);
            $art['_can_delete']  = canDeleteArtikel($currentUser, $menu, $art);
            $art['_can_publish'] = canPublishArtikel($currentUser, $menu);
            $art['_is_own']      = isOwnArtikel($currentUser, $art);
            apiJson(['success' => true, 'data' => $art]);
            break;

        // ── SAVE (CREATE / UPDATE) ─────────────────────────────────────────
        case 'save':
            $data         = $body['data'] ?? [];
            $data['menu'] = $menu;
            $isNew        = empty($data['id']);

            if (empty(trim($data['judul'] ?? ''))) {
                apiJson(['error' => 'Judul artikel wajib diisi'], 400);
            }

            if ($isNew) {
                $data['penulis'] = getDisplayName($currentUser);
                if (!canPublishArtikel($currentUser, $menu)) {
                    $data['status'] = 'draft';
                }
            } else {
                // Cari artikel lama — bisa jadi berasal dari menu berbeda (pindah rubrik)
                $existing = $am->getById($menu, $data['id']);
                if (!$existing) apiJson(['error' => 'Artikel tidak ditemukan'], 404);
                // Jika artikel ditemukan di menu lain, pastikan permission dari menu asalnya
                $originalMenu = $existing['menu'] ?? $menu;
                if (!canEditArtikel($currentUser, $originalMenu, $existing)) {
                    $owner = $existing['penulis'] ?? 'penulis lain';
                    apiJson([
                        'error' => "Anda tidak dapat mengedit artikel ini karena ditulis oleh {$owner}."
                    ], 403);
                }
                if (!canPublishArtikel($currentUser, $menu)) {
                    $data['status'] = $existing['status'] ?? 'draft';
                }
                $data['penulis'] = $existing['penulis'] ?? $currentUser['username'];
            }

            $saved = $am->save($data);
            $logger->log(
                $currentUser,
                $isNew ? 'CREATE' : 'UPDATE',
                $menu,
                ($isNew ? 'Artikel baru: ' : 'Update artikel: ') . ($data['judul'] ?? '')
            );

            // ── AUTO OG CONVERSION ─────────────────────────────────────────
            // Jalankan setelah artikel berhasil disimpan, jika ada thumbnail
            $ogResult = ['success' => false, 'note' => 'Tidak ada thumbnail'];
            $thumbnail = $data['thumbnail'] ?? $saved['thumbnail'] ?? '';
            if ($thumbnail) {
                $ogResult = autoConvertOG($thumbnail);
                if ($ogResult['success']) {
                    $logger->log(
                        $currentUser,
                        'CREATE',
                        'media',
                        'Auto OG: ' . ($ogResult['og_name'] ?? '') . ' dari artikel "' . ($data['judul'] ?? '') . '"'
                    );
                }
            }

            $saved['_can_edit']    = canEditArtikel($currentUser, $menu, $saved);
            $saved['_can_delete']  = canDeleteArtikel($currentUser, $menu, $saved);
            $saved['_can_publish'] = canPublishArtikel($currentUser, $menu);
            $saved['_is_own']      = isOwnArtikel($currentUser, $saved);
            $saved['_og']          = $ogResult; // info OG untuk frontend

            invalidateAllRelatedCaches($menu, 'articles');
            apiJson(['success' => true, 'data' => $saved]);
            break;

        // ── PUBLISH ───────────────────────────────────────────────────────
        case 'publish':
            if (!canPublishArtikel($currentUser, $menu)) {
                apiJson(['error' => 'Anda tidak memiliki izin untuk mempublish artikel.'], 403);
            }
            $id  = $body['id'] ?? '';
            $art = $am->getById($menu, $id);
            if (!$art) apiJson(['error' => 'Artikel tidak ditemukan'], 404);
            $am->publish($menu, $id);
            $logger->log($currentUser, 'UPDATE', $menu, 'Publish: ' . ($art['judul'] ?? ''));
            invalidateAllRelatedCaches($menu, 'articles');
            apiJson(['success' => true]);
            break;

        // ── UNPUBLISH ─────────────────────────────────────────────────────
        case 'unpublish':
            if (!canPublishArtikel($currentUser, $menu)) {
                apiJson(['error' => 'Anda tidak memiliki izin untuk mengubah status artikel.'], 403);
            }
            $id  = $body['id'] ?? '';
            $art = $am->getById($menu, $id);
            if (!$art) apiJson(['error' => 'Artikel tidak ditemukan'], 404);
            $am->unpublish($menu, $id);
            $logger->log($currentUser, 'UPDATE', $menu, 'Unpublish: ' . ($art['judul'] ?? ''));
            invalidateAllRelatedCaches($menu, 'articles');
            apiJson(['success' => true]);
            break;

        // ── REJECT / TOLAK (MINTA REVISI) + EMAIL NOTIFIKASI ─────────────
        case 'reject':
        case 'tolak':
            if (!canPublishArtikel($currentUser, $menu)) {
                apiJson(['error' => 'Anda tidak memiliki izin untuk menolak / meminta revisi artikel.'], 403);
            }
            $id      = $body['id'] ?? '';
            $catatan = trim($body['catatan'] ?? $body['saran'] ?? '');
            if (!$id) apiJson(['error' => 'ID artikel wajib diisi'], 400);
            if (!$catatan) apiJson(['error' => 'Catatan / alasan revisi wajib diisi.'], 400);

            $art = $am->getById($menu, $id);
            if (!$art) apiJson(['error' => 'Artikel tidak ditemukan'], 404);

            $reviewerName = getDisplayName($currentUser);
            $am->reject($menu, $id, $catatan, $reviewerName);
            $logger->log($currentUser, 'UPDATE', $menu, 'Minta Revisi: ' . ($art['judul'] ?? '') . " (Oleh: {$reviewerName})");
            invalidateAllRelatedCaches($menu, 'articles');

            // ── KIRIM NOTIFIKASI EMAIL KE PENULIS ARTIKEL ──────────────────
            $emailSent = false;
            $authorEmail = '';
            $authorName = $art['penulis'] ?? '';

            try {
                $db = getDB();
                $um = new UserManager($db);
                $authorUser = null;

                if (!empty($authorName)) {
                    // Cari berdasarkan username
                    $authorUser = $um->findByUsername($authorName);
                    // Jika belum ketemu, cari berdasarkan nama atau username di seluruh list
                    if (!$authorUser) {
                        $allUsers = $um->getAll();
                        foreach ($allUsers as $u) {
                            if (strcasecmp(trim($u['nama'] ?? ''), trim($authorName)) === 0 ||
                                strcasecmp(trim($u['username'] ?? ''), trim($authorName)) === 0) {
                                $authorUser = $u;
                                break;
                            }
                        }
                    }
                }

                if ($authorUser && !empty($authorUser['email'])) {
                    $authorEmail = $authorUser['email'];
                    $targetName  = !empty($authorUser['nama']) ? $authorUser['nama'] : ($authorUser['username'] ?? $authorName);

                    // Buat link ke editor artikel
                    $cfVisitor = $_SERVER['HTTP_CF_VISITOR'] ?? '';
                    $cfScheme  = '';
                    if ($cfVisitor) {
                        $cfJson = json_decode($cfVisitor, true);
                        $cfScheme = $cfJson['scheme'] ?? '';
                    }
                    $xForwarded = strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '');
                    $isHttps = ($cfScheme === 'https')
                            || ($xForwarded === 'https')
                            || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

                    $host = $_SERVER['HTTP_HOST'] ?? 'www.parokitulungagung.org';
                    $editorUrl = ($isHttps ? 'https' : 'http') . '://' . $host . '/admin/pages/artikel-editor.php?menu=' . urlencode($menu) . '&id=' . urlencode($id);

                    $emailBody = _buildArticleRevisionEmail(
                        $targetName,
                        $art['judul'] ?? 'Artikel',
                        $menu,
                        $catatan,
                        $reviewerName,
                        $editorUrl
                    );

                    $mailer = new Mailer();
                    $subject = '[Catatan Revisi] ' . mb_substr($art['judul'] ?? 'Artikel Anda', 0, 50);
                    $mailer->send(
                        $authorEmail,
                        $targetName,
                        $subject,
                        $emailBody
                    );
                    $emailSent = true;
                    $logger->log($currentUser, 'NOTIF', $menu, "Email revisi terkirim ke: {$authorEmail}");
                }
            } catch (Throwable $e) {
                error_log('[admin/api/artikel.php] Gagal kirim email revisi: ' . $e->getMessage());
                // Jangan gagalkan proses reject jika SMTP error
            }

            apiJson([
                'success'    => true,
                'message'    => 'Artikel ditandai perlu revisi.',
                'email_sent' => $emailSent,
                'author'     => $authorName,
                'email'      => $authorEmail,
            ]);
            break;

        // ── DELETE ────────────────────────────────────────────────────────
        case 'delete':
            $id  = $body['id'] ?? '';
            $art = $am->getById($menu, $id);
            if (!$art) apiJson(['error' => 'Artikel tidak ditemukan'], 404);
            if (!canDeleteArtikel($currentUser, $menu, $art)) {
                $owner = $art['penulis'] ?? 'penulis lain';
                apiJson([
                    'error' => "Anda tidak dapat menghapus artikel ini karena ditulis oleh {$owner}."
                ], 403);
            }
            $am->delete($menu, $id);
            $logger->log($currentUser, 'DELETE', $menu, 'Hapus: ' . ($art['judul'] ?? ''));
            invalidateAllRelatedCaches($menu, 'articles');
            apiJson(['success' => true]);
            break;

        // ── TAGS: semua unique tag dari artikel ──────────────────────────
        case 'tags':
            $allArts = $am->getAll($menu);
            $tagMap  = [];
            foreach ($allArts as $art) {
                if (empty($art['tags'])) continue;
                $raw  = $art['tags'];
                $list = is_array($raw) ? $raw : (json_decode($raw, true) ?: array_map('trim', explode(',', $raw)));
                foreach ($list as $t) {
                    $t = trim((string)$t);
                    if ($t) $tagMap[strtolower($t)] = $t;
                }
            }
            $sorted = array_values($tagMap);
            sort($sorted, SORT_NATURAL | SORT_FLAG_CASE);
            apiJson(['tags' => $sorted]);
            break;

        default:
            apiJson(['error' => 'Action tidak dikenal: ' . $action], 400);
    }

} catch (Throwable $e) {
    error_log('[api/artikel.php] ' . $e->getMessage());
    apiJson(['error' => $e->getMessage()], 500);
}