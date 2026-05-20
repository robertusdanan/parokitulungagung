<?php

ob_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/seo-keywords.php';
require_once __DIR__ . '/../includes/ImageSeoGenerator.php';
require_once __DIR__ . '/../../includes/SupabaseArticleManager.php'; // tetap di root includes
adminBoot();

header('Content-Type: application/json; charset=utf-8');

// ── Auth: superadmin untuk semua aksi, kecuali 'process' yang boleh semua admin ──
$user = apiRequireLogin();
$body   = jsonBody();
$action = $body['action'] ?? ($_GET['action'] ?? '');

if ($action !== 'process' && $user['role'] !== ROLE_SUPERADMIN) {
    ob_end_clean();
    apiJson(['error' => 'Akses ditolak. Hanya superadmin yang bisa mengakses SEO generator.'], 403);
}

// ── Helper: fetch existing artikel_ids dari image_seo ─────────────────────
function fetchExistingArtikelIds(): array
{
    $url = rtrim(SUPABASE_URL, '/') . '/rest/v1/image_seo'
         . '?select=artikel_id&artikel_id=not.is.null';

    $ctx = stream_context_create([
        'http' => [
            'header'        => "apikey: " . SUPABASE_SERVICE_KEY . "\r\n"
                             . "Authorization: Bearer " . SUPABASE_SERVICE_KEY . "\r\n"
                             . "Accept: application/json\r\n",
            'timeout'       => 10,
            'ignore_errors' => true,
        ],
        'ssl' => ['verify_peer' => false],
    ]);

    $resp = @file_get_contents($url, false, $ctx);
    if (!$resp) return [];

    $rows = json_decode($resp, true);
    if (!is_array($rows)) return [];

    $ids = [];
    foreach ($rows as $r) {
        if (!empty($r['artikel_id'])) {
            $ids[$r['artikel_id']] = true;
        }
    }
    return $ids;
}

// ── Helper: hitung jumlah <img> dalam HTML ────────────────────────────────
function countImgTags(string $html): int
{
    return substr_count(strtolower($html), '<img ');
}

// ── Helper: ekstrak semua src dari <img> tags ─────────────────────────────
function extractImgSrcs(string $html): array
{
    preg_match_all('/<img[^>]+src\s*=\s*["\']([^"\']+)["\'][^>]*>/i', $html, $m);
    return array_values(array_unique(array_filter($m[1] ?? [])));
}

// ── Helper: hitung existing image records untuk satu artikel ─────────────
function countExistingImages(string $artikelId): int
{
    $url = rtrim(SUPABASE_URL, '/') . '/rest/v1/image_seo'
         . '?artikel_id=eq.' . urlencode($artikelId)
         . '&select=image_url';

    $ctx = stream_context_create([
        'http' => [
            'header'        => "apikey: " . SUPABASE_SERVICE_KEY . "\r\n"
                             . "Authorization: Bearer " . SUPABASE_SERVICE_KEY . "\r\n"
                             . "Accept: application/json\r\n",
            'timeout'       => 6,
            'ignore_errors' => true,
        ],
        'ssl' => ['verify_peer' => false],
    ]);

    $resp = @file_get_contents($url, false, $ctx);
    if (!$resp) return 0;
    $rows = json_decode($resp, true);
    return is_array($rows) ? count($rows) : 0;
}

try {
    $am = new SupabaseArticleManager(getDB());

    switch ($action) {

        // ════════════════════════════════════════════════════════════════════
        // LIST — semua artikel dengan status SEO
        // ════════════════════════════════════════════════════════════════════
        case 'list': {
            $menus = SupabaseArticleManager::MENUS;

            // Ambil semua artikel dari semua menu (published & draft)
            $allArts = [];
            foreach ($menus as $menu) {
                $arts = $am->getAll($menu);
                foreach ($arts as $art) {
                    $art['_menu']      = $menu;
                    $art['_img_count'] = countImgTags($art['konten'] ?? '');
                    $allArts[]         = $art;
                }
            }

            // Batch: ambil semua artikel_id yang sudah ada di image_seo
            $doneIds = fetchExistingArtikelIds();

            // Susun output
            $result = [];
            foreach ($allArts as $art) {
                $isDone = isset($doneIds[$art['id']]);
                $result[] = [
                    'id'         => $art['id'],
                    'judul'      => $art['judul'] ?? '(tanpa judul)',
                    'menu'       => $art['_menu'],
                    'tags'       => $art['tags'] ?? '',
                    'status'     => $art['status'] ?? 'draft',
                    'img_count'  => $art['_img_count'],
                    'seo_done'   => $isDone,
                    'created_at' => $art['created_at'] ?? '',
                ];
            }

            ob_end_clean();
            apiJson(['success' => true, 'data' => $result, 'total' => count($result)]);
        }

        // ════════════════════════════════════════════════════════════════════
        // STATS — ringkasan agregat
        // ════════════════════════════════════════════════════════════════════
        case 'stats': {
            $menus   = SupabaseArticleManager::MENUS;
            $totalArt = 0;
            $totalImg = 0;

            foreach ($menus as $menu) {
                $arts = $am->getAll($menu);
                foreach ($arts as $art) {
                    $totalArt++;
                    $totalImg += countImgTags($art['konten'] ?? '');
                }
            }

            $doneIds = fetchExistingArtikelIds();

            ob_end_clean();
            apiJson([
                'success'      => true,
                'total_art'    => $totalArt,
                'done_art'     => count($doneIds),
                'pending_art'  => max(0, $totalArt - count($doneIds)),
                'total_img'    => $totalImg,
            ]);
        }

        // ════════════════════════════════════════════════════════════════════
        // PROCESS — generate SEO untuk satu artikel
        // ════════════════════════════════════════════════════════════════════
        case 'process': {
            $artikelId    = trim($body['artikel_id']    ?? '');
            $artikelMenu  = trim($body['artikel_menu']  ?? '');
            $artikelJudul = trim($body['artikel_judul'] ?? '');
            $artikelTags  = trim($body['artikel_tags']  ?? '');
            $konten       = trim($body['konten']         ?? '');
            $forceRegen   = !empty($body['force']);

            if (!$artikelId) {
                ob_end_clean();
                apiJson(['error' => 'artikel_id wajib diisi'], 400);
            }

            // Jika konten belum dikirim, ambil dari DB
            if (!$konten && $artikelMenu) {
                $art    = $am->getById($artikelMenu, $artikelId);
                $konten = $art['konten'] ?? '';
                if (!$artikelJudul && $art) $artikelJudul = $art['judul'] ?? '';
                if (!$artikelTags  && $art) $artikelTags  = is_array($art['tags']) ? implode(', ', $art['tags']) : ($art['tags'] ?? '');
            }

            if (!$konten) {
                ob_end_clean();
                apiJson(['ok' => true, 'msg' => 'Tidak ada konten/gambar', 'generated' => 0]);
            }

            // Ekstrak semua img src
            $srcs = extractImgSrcs($konten);
            if (empty($srcs)) {
                ob_end_clean();
                apiJson(['ok' => true, 'msg' => 'Tidak ada gambar dalam artikel', 'generated' => 0, 'skipped' => 0]);
            }

            // Jika bukan force, cek gambar yang sudah ada di Supabase
            $existingCount = 0;
            $existingSrcs  = [];
            if (!$forceRegen) {
                $url = rtrim(SUPABASE_URL, '/') . '/rest/v1/image_seo'
                     . '?artikel_id=eq.' . urlencode($artikelId)
                     . '&select=image_url';
                $ctx2 = stream_context_create([
                    'http' => [
                        'header'        => "apikey: " . SUPABASE_SERVICE_KEY . "\r\n"
                                         . "Authorization: Bearer " . SUPABASE_SERVICE_KEY . "\r\n"
                                         . "Accept: application/json\r\n",
                        'timeout'       => 6,
                        'ignore_errors' => true,
                    ],
                    'ssl' => ['verify_peer' => false],
                ]);
                $resp2 = @file_get_contents($url, false, $ctx2);
                if ($resp2) {
                    $rows2 = json_decode($resp2, true);
                    if (is_array($rows2)) {
                        foreach ($rows2 as $r) {
                            if (!empty($r['image_url'])) {
                                $existingSrcs[$r['image_url']] = true;
                            }
                        }
                    }
                }
            }

            // Tentukan label kategori
            $menuLabel = match ($artikelMenu) {
                'berita'   => 'Liputan Berita',
                'kronik'   => 'Kronik SMDTBA',
                'historia' => 'Historia Gereja',
                default    => 'Paroki SMDTBA Tulungagung',
            };

            $artData = [
                'id'       => $artikelId,
                'judul'    => $artikelJudul,
                'tags'     => $artikelTags,
                'kategori' => $menuLabel,
                'menu'     => $artikelMenu,
            ];

            $generated = 0;
            $skipped   = 0;
            $errors    = [];

            foreach ($srcs as $src) {
                if (!$forceRegen && isset($existingSrcs[$src])) {
                    $skipped++;
                    continue;
                }
                try {
                    $ok = ImageSeoGenerator::forceGenerateToSupabase($src, $artData);
                    if ($ok) $generated++;
                    else $errors[] = basename($src) . ': generate returned false';
                } catch (Throwable $e) {
                    $errors[] = basename($src) . ': ' . $e->getMessage();
                }
            }

            // Log aktivitas
            getLogger()->log($user, 'CREATE', 'seo-artikel',
                "SEO generate: {$generated} gambar diproses dari \"{$artikelJudul}\"");

            ob_end_clean();
            apiJson([
                'ok'        => true,
                'generated' => $generated,
                'skipped'   => $skipped,
                'total_img' => count($srcs),
                'errors'    => $errors,
                'msg'       => "✓ {$generated} gambar digenerate, {$skipped} sudah ada",
            ]);
        }

        // ════════════════════════════════════════════════════════════════════
        // CLEAR_CACHE — hapus file cache lokal
        // ════════════════════════════════════════════════════════════════════
        case 'clear_cache': {
            $deleted = ImageSeoGenerator::clearFileCache();
            getLogger()->log($user, 'UPDATE', 'seo-artikel',
                "Clear file cache ImageSeoGenerator: {$deleted} file dihapus");
            ob_end_clean();
            apiJson(['success' => true, 'deleted' => $deleted,
                     'msg' => "{$deleted} file cache lokal dihapus"]);
        }

        default:
            ob_end_clean();
            apiJson(['error' => 'Action tidak dikenal: ' . $action], 400);
    }

} catch (Throwable $e) {
    error_log('[seo-artikel.php] ' . $e->getMessage());
    ob_end_clean();
    apiJson(['error' => $e->getMessage()], 500);
}