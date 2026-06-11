<?php
/**
 * admin/api/seo-images.php
 * SEO Generator API — Multi-Sumber (Galeri, UMKM, DPP, Asisten Imam, Wilayah)
 *
 * Actions : list | stats | process | clear_cache
 * Param   : section = galeri | umkm | dpp_bgkp | asisten_imam | wilayah
 */
ob_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/ImageSeoGenerator.php';

adminBoot();

if (!headers_sent()) {
    @set_time_limit(120);
    @ini_set('max_execution_time', '120');
}

header('Content-Type: application/json; charset=utf-8');

$user    = apiRequireLogin();
$body    = jsonBody();
$action  = $body['action']  ?? ($_GET['action']  ?? '');
$section = $body['section'] ?? ($_GET['section'] ?? 'galeri');

// Hanya superadmin kecuali action 'process'
if ($action !== 'process' && $user['role'] !== ROLE_SUPERADMIN) {
    ob_end_clean();
    apiJson(['error' => 'Akses ditolak. Hanya superadmin.'], 403);
}

// ══════════════════════════════════════════════════════════════════════════════
//  HELPER: Supabase HTTP GET
// ══════════════════════════════════════════════════════════════════════════════
function imgSeoSbGet(string $url): ?array
{
    $ctx = stream_context_create([
        'http' => [
            'header'        => "apikey: "        . SUPABASE_SERVICE_KEY . "\r\n"
                             . "Authorization: Bearer " . SUPABASE_SERVICE_KEY . "\r\n"
                             . "Accept: application/json\r\n"
                             . "Prefer: count=none\r\n",
            'timeout'       => 12,
            'ignore_errors' => true,
        ],
        'ssl' => ['verify_peer' => false],
    ]);
    $resp = @file_get_contents($url, false, $ctx);
    if (!$resp) return null;
    $data = json_decode($resp, true);
    return is_array($data) ? $data : null;
}

// ══════════════════════════════════════════════════════════════════════════════
//  HELPER: Ambil URL gambar yang sudah punya SEO (query by prefix path)
// ══════════════════════════════════════════════════════════════════════════════
function getExistingSeoByPrefix(string $prefix): array
{
    $pattern = rawurlencode($prefix . '%');
    $url = rtrim(SUPABASE_URL, '/') . '/rest/v1/image_seo'
         . '?image_url=like.' . $pattern
         . '&select=image_url';
    $rows = imgSeoSbGet($url);
    if (!is_array($rows)) return [];
    $result = [];
    foreach ($rows as $r) {
        $imgUrl = $r['image_url'] ?? '';
        if ($imgUrl) $result[$imgUrl] = true;
    }
    return $result;
}

// ══════════════════════════════════════════════════════════════════════════════
//  HELPER: List file gambar dari folder lokal
// ══════════════════════════════════════════════════════════════════════════════
function listImagesFromFolder(string $relPath): array
{
    $absPath = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/') . $relPath;
    if (!is_dir($absPath)) return [];

    $exts  = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    $files = [];
    $dh    = opendir($absPath);
    while (($f = readdir($dh)) !== false) {
        if ($f === '.' || $f === '..') continue;
        $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
        if (!in_array($ext, $exts)) continue;
        $files[] = [
            'image_url' => $relPath . '/' . $f,
            'filename'  => $f,
            'name'      => pathinfo($f, PATHINFO_FILENAME),
        ];
    }
    closedir($dh);
    usort($files, fn($a, $b) => strcmp($b['filename'], $a['filename']));
    return $files;
}

// ══════════════════════════════════════════════════════════════════════════════
//  HELPER: Normalize foto URL ke full path
// ══════════════════════════════════════════════════════════════════════════════
function normalizeImgUrl(string $foto, string $prefix): string
{
    if (!$foto) return '';
    if (str_starts_with($foto, '/') || str_starts_with($foto, 'http')) return $foto;
    return $prefix . '/' . $foto;
}

// ══════════════════════════════════════════════════════════════════════════════
//  HELPER: Ambil semua gambar dari Supabase table
// ══════════════════════════════════════════════════════════════════════════════
function fetchSectionImages(string $section): array
{
    $baseUrl = rtrim(SUPABASE_URL, '/');

    switch ($section) {

        case 'galeri':
            // Coba dari filesystem dulu (paling lengkap)
            $fsImages = listImagesFromFolder('/public/galeri');
            // Merge dengan data Supabase untuk mendapat metadata judul/keterangan
            $sbRows = imgSeoSbGet($baseUrl . '/rest/v1/' . TABLE_GALERI
                . '?select=id,Judul,Keterangan,Gambar,Foto&order=id.desc') ?? [];
            // Build lookup table dari supabase berdasarkan filename
            $sbMeta = [];
            foreach ($sbRows as $row) {
                foreach (['Gambar', 'Foto'] as $col) {
                    $f = $row[$col] ?? '';
                    if ($f) {
                        $bn = basename($f);
                        $sbMeta[$bn] = [
                            'judul'       => $row['Judul']       ?? '',
                            'keterangan'  => $row['Keterangan']  ?? '',
                        ];
                    }
                }
            }
            // Gabungkan
            $result = [];
            foreach ($fsImages as $img) {
                $meta   = $sbMeta[$img['filename']] ?? [];
                $result[] = [
                    'image_url' => $img['image_url'],
                    'filename'  => $img['filename'],
                    'name'      => $meta['judul']       ?: $img['name'],
                    'meta'      => $meta['keterangan']  ?? '',
                    'source'    => 'galeri',
                    '_artData'  => [
                        'source_type' => 'galeri',
                        'judul'       => $meta['judul'] ?: $img['name'],
                        'kategori'    => 'Galeri Foto Gereja Katolik Tulungagung',
                        'tags'        => $meta['keterangan'] ?? '',
                        'extra'       => $meta,
                    ],
                ];
            }
            return $result;

        case 'umkm':
            $rows = imgSeoSbGet($baseUrl . '/rest/v1/' . TABLE_UMKM
                . '?select=id,judul,nama_usaha,gambar,deskripsi'
                . '&gambar=not.is.null&order=urutan.asc,id.desc') ?? [];
            $result = [];
            foreach ($rows as $row) {
                $foto = $row['gambar'] ?? '';
                if (!$foto) continue;
                $imgUrl = normalizeImgUrl($foto, '/public/umkm');
                $displayName = ($row['judul'] ?? '') ?: ($row['nama_usaha'] ?? '');
                $result[] = [
                    'image_url' => $imgUrl,
                    'filename'  => basename($imgUrl),
                    'name'      => $displayName,
                    'meta'      => $row['nama_usaha'] ?? '',
                    'source'    => 'umkm',
                    '_artData'  => [
                        'source_type' => 'umkm',
                        'judul'       => $displayName,
                        'display_name'=> '[UMKM] ' . $displayName,
                        'kategori'    => 'UMKM Umat Gereja Katolik Tulungagung',
                        'tags'        => $row['deskripsi'] ?? '',
                        'extra'       => [
                            'nama_usaha' => $row['nama_usaha'] ?? '',
                            'deskripsi'  => $row['deskripsi']  ?? '',
                        ],
                    ],
                ];
            }
            return $result;

        case 'dpp_bgkp':
            $rows = imgSeoSbGet($baseUrl . '/rest/v1/' . TABLE_DPP
                . '?select=id,Nama,Bidang,Posisi,Tipe,Periode,Foto'
                . '&Foto=not.is.null&order=Periode.desc,Tipe.asc,Bidang.asc') ?? [];
            $result = [];
            $seen   = [];
            foreach ($rows as $row) {
                $foto = $row['Foto'] ?? '';
                if (!$foto) continue;
                $imgUrl = normalizeImgUrl($foto, '/img/person');
                if (isset($seen[$imgUrl])) continue; // skip duplikat foto
                $seen[$imgUrl] = true;
                $nama    = $row['Nama']    ?? '';
                $bidang  = $row['Bidang']  ?? '';
                $posisi  = $row['Posisi']  ?? '';
                $tipe    = $row['Tipe']    ?? 'DPP';
                $periode = $row['Periode'] ?? '';
                $result[] = [
                    'image_url' => $imgUrl,
                    'filename'  => basename($imgUrl),
                    'name'      => $nama,
                    'meta'      => implode(' · ', array_filter([$tipe, $bidang, $posisi, $periode])),
                    'source'    => 'dpp_bgkp',
                    '_artData'  => [
                        'source_type'  => 'dpp_bgkp',
                        'judul'        => $nama,
                        'display_name' => "[{$tipe}] {$nama} – {$posisi}",
                        'kategori'     => "Kepengurusan {$tipe} Gereja Katolik Tulungagung",
                        'tags'         => implode(', ', array_filter([$bidang, $posisi, $tipe, $periode])),
                        'extra'        => $row,
                    ],
                ];
            }
            return $result;

        case 'asisten_imam':
            $rows = imgSeoSbGet($baseUrl . '/rest/v1/' . TABLE_AI
                . '?select=id,Nama,Periode,Foto&Foto=not.is.null'
                . '&select=id,Nama,Periode,Foto,Alamat') ?? [];
            // Also try with Asal column
            $rowsFull = imgSeoSbGet($baseUrl . '/rest/v1/' . TABLE_AI
                . '?select=id,Nama,Periode,Foto,Alamat'
                . '&Foto=not.is.null&order=Periode.desc,Nama.asc') ?? $rows;
            $result = [];
            $seen   = [];
            foreach ($rowsFull as $row) {
                $foto = $row['Foto'] ?? '';
                if (!$foto) continue;
                $imgUrl = normalizeImgUrl($foto, '/img/person');
                if (isset($seen[$imgUrl])) continue;
                $seen[$imgUrl] = true;
                $nama    = $row['Nama']    ?? '';
                $periode = $row['Periode'] ?? '';
                $result[] = [
                    'image_url' => $imgUrl,
                    'filename'  => basename($imgUrl),
                    'name'      => $nama,
                    'meta'      => $periode,
                    'source'    => 'asisten_imam',
                    '_artData'  => [
                        'source_type'  => 'asisten_imam',
                        'judul'        => $nama,
                        'display_name' => "[Asisten Imam] {$nama}" . ($periode ? " ({$periode})" : ''),
                        'kategori'     => 'Asisten Imam Gereja Katolik Tulungagung',
                        'tags'         => $periode,
                        'extra'        => $row,
                    ],
                ];
            }
            return $result;

        case 'wilayah':
            $rows = imgSeoSbGet($baseUrl . '/rest/v1/' . TABLE_WILAYAH
                . '?select=id,Wilayah,Lingkungan,Ketua,Foto,Koordinator,KoordinatorNama,KoordinatorFoto'
                . '&order=Wilayah.asc,Lingkungan.asc') ?? [];
            $result = [];
            $seen   = [];
            foreach ($rows as $row) {
                $wilayah    = $row['Wilayah']    ?? '';
                $lingkungan = $row['Lingkungan'] ?? '';

                // Foto Ketua Lingkungan
                if (!empty($row['Foto'])) {
                    $imgUrl = normalizeImgUrl($row['Foto'], '/img/person');
                    if (!isset($seen[$imgUrl])) {
                        $seen[$imgUrl] = true;
                        $nama   = $row['Ketua'] ?? '';
                        $result[] = [
                            'image_url' => $imgUrl,
                            'filename'  => basename($imgUrl),
                            'name'      => $nama ?: "Ketua {$lingkungan}",
                            'meta'      => "Ketua · {$lingkungan} · Wil. {$wilayah}",
                            'source'    => 'wilayah',
                            '_artData'  => [
                                'source_type'  => 'wilayah',
                                'judul'        => $nama,
                                'display_name' => "[Ketua] {$nama} – Lingk. {$lingkungan}",
                                'kategori'     => 'Profil Wilayah & Lingkungan Gereja Katolik Tulungagung',
                                'tags'         => "ketua lingkungan, {$lingkungan}, wilayah {$wilayah}",
                                'extra'        => array_merge($row, ['role'=>'Ketua','nama'=>$nama]),
                            ],
                        ];
                    }
                }

                // Foto Koordinator Wilayah
                if (!empty($row['KoordinatorFoto'])) {
                    $imgUrl = normalizeImgUrl($row['KoordinatorFoto'], '/img/person');
                    if (!isset($seen[$imgUrl])) {
                        $seen[$imgUrl] = true;
                        $nama   = $row['KoordinatorNama'] ?? ($row['Koordinator'] ?? '');
                        $result[] = [
                            'image_url' => $imgUrl,
                            'filename'  => basename($imgUrl),
                            'name'      => $nama ?: "Koordinator Wil. {$wilayah}",
                            'meta'      => "Koordinator · Wil. {$wilayah}",
                            'source'    => 'wilayah',
                            '_artData'  => [
                                'source_type'  => 'wilayah',
                                'judul'        => $nama,
                                'display_name' => "[Koordinator] {$nama} – Wil. {$wilayah}",
                                'kategori'     => 'Profil Wilayah & Lingkungan Gereja Katolik Tulungagung',
                                'tags'         => "koordinator wilayah, {$wilayah}",
                                'extra'        => array_merge($row, ['role'=>'Koordinator','nama'=>$nama]),
                            ],
                        ];
                    }
                }
            }
            return $result;
    }

    return [];
}

// ══════════════════════════════════════════════════════════════════════════════
//  FOLDER PREFIX per section — untuk query image_seo
// ══════════════════════════════════════════════════════════════════════════════
function sectionPrefix(string $section): string
{
    return match ($section) {
        'galeri'       => '/public/galeri/',
        'umkm'         => '/public/umkm/',
        'dpp_bgkp'     => '/img/person/',
        'asisten_imam' => '/img/person/',
        'wilayah'      => '/img/person/',
        default        => '/',
    };
}

// ══════════════════════════════════════════════════════════════════════════════
//  ACTIONS
// ══════════════════════════════════════════════════════════════════════════════
try {
    switch ($action) {

        // ─── LIST ─────────────────────────────────────────────────────────────
        case 'list': {
            $images = fetchSectionImages($section);

            // Get all existing SEO URLs for this section's prefix
            $prefix    = sectionPrefix($section);
            $existingSeo = getExistingSeoByPrefix($prefix);

            $result = [];
            foreach ($images as $img) {
                $url = $img['image_url'];
                $result[] = [
                    'image_url' => $url,
                    'filename'  => $img['filename'],
                    'name'      => $img['name']   ?? '',
                    'meta'      => $img['meta']   ?? '',
                    'source'    => $img['source'] ?? $section,
                    'seo_done'  => isset($existingSeo[$url]),
                    '_artData'  => $img['_artData'] ?? [],
                ];
            }

            $total    = count($result);
            $seoDone  = count(array_filter($result, fn($r) => $r['seo_done']));

            ob_end_clean();
            apiJson([
                'success' => true,
                'section' => $section,
                'data'    => $result,
                'total'   => $total,
                'done'    => $seoDone,
                'pending' => $total - $seoDone,
            ]);
        }

        // ─── STATS ────────────────────────────────────────────────────────────
        case 'stats': {
            $sections  = ['galeri', 'umkm', 'dpp_bgkp', 'asisten_imam', 'wilayah'];
            $statsAll  = [];
            $grandTotal = 0;
            $grandDone  = 0;

            foreach ($sections as $sec) {
                $imgs   = fetchSectionImages($sec);
                $prefix = sectionPrefix($sec);
                $exists = getExistingSeoByPrefix($prefix);
                $total  = count($imgs);
                $done   = 0;
                foreach ($imgs as $img) {
                    if (isset($exists[$img['image_url']])) $done++;
                }
                $statsAll[$sec] = [
                    'total'   => $total,
                    'done'    => $done,
                    'pending' => $total - $done,
                ];
                $grandTotal += $total;
                $grandDone  += $done;
            }

            ob_end_clean();
            apiJson([
                'success'     => true,
                'grand_total' => $grandTotal,
                'grand_done'  => $grandDone,
                'grand_pending' => $grandTotal - $grandDone,
                'sections'    => $statsAll,
            ]);
        }

        // ─── STATS SINGLE ─────────────────────────────────────────────────────
        case 'stats_section': {
            $imgs    = fetchSectionImages($section);
            $prefix  = sectionPrefix($section);
            $exists  = getExistingSeoByPrefix($prefix);
            $total   = count($imgs);
            $done    = 0;
            foreach ($imgs as $img) {
                if (isset($exists[$img['image_url']])) $done++;
            }
            ob_end_clean();
            apiJson([
                'success' => true,
                'section' => $section,
                'total'   => $total,
                'done'    => $done,
                'pending' => $total - $done,
            ]);
        }

        // ─── PROCESS — generate SEO untuk satu gambar ─────────────────────────
        case 'process': {
            $imageUrl  = trim($body['image_url']  ?? '');
            $forceRegen = !empty($body['force']);
            $artData   = $body['art_data'] ?? [];

            if (!$imageUrl) {
                ob_end_clean();
                apiJson(['error' => 'image_url wajib diisi'], 400);
            }

            // Jika bukan force, cek apakah sudah ada di Supabase
            if (!$forceRegen) {
                $baseUrl = rtrim(SUPABASE_URL, '/');
                $checkUrl = $baseUrl . '/rest/v1/image_seo'
                          . '?image_url=eq.' . rawurlencode($imageUrl)
                          . '&select=image_url';
                $existing = imgSeoSbGet($checkUrl);
                if (!empty($existing)) {
                    ob_end_clean();
                    apiJson([
                        'ok'      => true,
                        'msg'     => 'Sudah ada (skip)',
                        'skipped' => 1,
                        'generated' => 0,
                    ]);
                }
            }

            // Build sourceData dari art_data yang dikirim frontend
            $sourceData = [
                'source_type'  => $artData['source_type']  ?? $section,
                'judul'        => $artData['judul']         ?? '',
                'display_name' => $artData['display_name']  ?? ($artData['judul'] ?? ''),
                'kategori'     => $artData['kategori']      ?? 'Gereja Katolik Tulungagung',
                'tags'         => $artData['tags']          ?? '',
                'extra'        => $artData['extra']         ?? [],
            ];

            try {
                $ok = ImageSeoGenerator::forceGenerateForSource($imageUrl, $sourceData);
                getLogger()->log($user, 'CREATE', 'seo-images',
                    "SEO AI generate [{$section}]: {$imageUrl}");
                ob_end_clean();
                apiJson([
                    'ok'        => $ok,
                    'generated' => $ok ? 1 : 0,
                    'msg'       => $ok ? '✓ SEO di-generate via AI' : 'Generate gagal',
                ]);
            } catch (Throwable $e) {
                ob_end_clean();
                apiJson(['ok' => false, 'error' => $e->getMessage()], 500);
            }
        }

        // ─── CLEAR CACHE ──────────────────────────────────────────────────────
        case 'clear_cache': {
            $deleted = ImageSeoGenerator::clearFileCache();
            getLogger()->log($user, 'UPDATE', 'seo-images',
                "Clear file cache ImageSeoGenerator: {$deleted} file dihapus");
            ob_end_clean();
            apiJson([
                'success' => true,
                'deleted' => $deleted,
                'msg'     => "{$deleted} file cache lokal dihapus",
            ]);
        }

        default:
            ob_end_clean();
            apiJson(['error' => 'Action tidak dikenal: ' . $action], 400);
    }

} catch (Throwable $e) {
    error_log('[seo-images.php] ' . $e->getMessage());
    ob_end_clean();
    apiJson(['error' => $e->getMessage()], 500);
}
