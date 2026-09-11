<?php
/**
 * admin/api/media.php
 * API manajemen file media: list, rename, delete, compress, bulk-compress, stats
 * + OG Preview: convert_og, bulk_convert_og, delete_og, list_og
 */
ob_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../../includes/R2Client.php';
require_once __DIR__ . '/../../includes/R2WriteClient.php';
adminBoot();
header('Content-Type: application/json; charset=utf-8');
$currentUser = apiRequireLogin();
$isSuperadmin = $currentUser['role'] === ROLE_SUPERADMIN;
if (!$isSuperadmin) {
    $permsMap = getPermissionsMap($currentUser);
    if (!array_key_exists('media', $permsMap)) {
        ob_end_clean(); apiJson(['error' => 'Akses ditolak'], 403);
    }
}
$body   = jsonBody();
$action = $body['action'] ?? '';
$root   = rtrim($_SERVER['DOCUMENT_ROOT'], '/');

// ── Folder config ─────────────────────────────────────────────────────────
// NB: folder lokal 'galeri' (/public/galeri) sudah tidak dipakai — thumbnail
// galeri sekarang disimpan di R2 (_thumbnails/galeri/), dilayani oleh
// admin/api/list_galeri_media.php dan action 'delete_file' di r2_manager.php.
//
// Folder 'person' SEKARANG disimpan di Cloudflare R2 (prefix R2_PERSON_PREFIX).
// Media Manager untuk person membaca daftar file langsung dari R2 (bukan
// dari disk lokal). Aksi 'delete' di folder person memakai R2 DeleteObject.
$FOLDERS = [
    'umkm'           => ['path' => '/umkm',           'label' => 'UMKM Umat',     'quality' => 78, 'skip' => false, 'storage' => 'r2'],
    'jadwal_petugas' => ['path' => '/jadwal_petugas', 'label' => 'Jadwal Petugas','quality' => 85, 'skip' => false, 'storage' => 'r2'],
    'downloads'      => ['path' => '/downloads',      'label' => 'Dokumen Unduhan','quality' => 0, 'skip' => true,  'storage' => 'r2'],
    'artikel'        => ['path' => '/artikel',         'label' => 'Artikel',       'quality' => 75, 'skip' => false, 'storage' => 'r2'],
    'ogpreview'      => ['path' => '/ogpreview',       'label' => 'OG Preview',    'quality' => 85, 'skip' => true,  'storage' => 'r2'],
    'gereja'         => ['path' => 'assets/gereja',    'label' => 'Foto Gereja',   'quality' => 75, 'skip' => false, 'storage' => 'r2'],
    'assets'         => ['path' => 'assets',           'label' => 'Assets',        'quality' => 78, 'skip' => false, 'storage' => 'r2', 'shallow' => true],
    'root_img'       => ['path' => 'assets',           'label' => 'Assets',        'quality' => 78, 'skip' => false, 'storage' => 'r2', 'shallow' => true],
    'icon'           => ['path' => '/icon',            'label' => 'Icon',          'quality' => 0,  'skip' => true,  'storage' => 'r2'],
    'icon_kategorial'=> ['path' => '/icon/kategorial', 'label' => 'Icon Kategorial','quality' => 0, 'skip' => true,  'storage' => 'r2'],
    'person'         => ['path' => '/person',          'label' => 'Foto Person',   'quality' => 82, 'skip' => false, 'storage' => 'r2'],
];
$CACHE_FILE   = $root . '/cache/media/media_compressed.json';
$OG_DIR_PATH  = '/ogpreview';                              // URL path
$OG_DIR_FULL  = rtrim(sys_get_temp_dir(), '/') . '/ogpreview'; // Filesystem path (fallback)
$OG_CACHE     = $root . '/cache/media/media_og.json';     // Cache file OG
$IMG_EXTS     = ['jpg','jpeg','png','webp','gif'];

// ── Helper: bangun URL publik R2 (CDN) untuk key R2 ─────────────────────
$r2CdnUrl = function (string $key) {
    $k = ltrim($key, '/');
    $cdn = defined('R2_CDN_URL') ? rtrim(R2_CDN_URL, '/') : '';
    return $cdn !== '' ? ($cdn . '/' . $k) : ('/' . $k);
};

// ── Helpers ───────────────────────────────────────────────────────────────
function mLoadCache(string $f): array {
    if (!file_exists($f)) return [];
    $d = json_decode(@file_get_contents($f), true);
    return is_array($d) ? $d : [];
}
function mSaveCache(string $f, array $c): void {
    $d = dirname($f);
    if (!is_dir($d)) @mkdir($d, 0755, true);
    @file_put_contents($f, json_encode($c, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}
function mIsImg(string $n, array $exts): bool {
    return in_array(strtolower(pathinfo($n, PATHINFO_EXTENSION)), $exts);
}

/**
 * Apakah file ini adalah thumbnail artikel?
 * Thumbnail selalu diawali "thumb-" — konvensi dari upload_artikel.php.
 * Gambar konten artikel TIDAK diawali "thumb-", sehingga tidak masuk OG.
 */
function isThumbnail(string $filename): bool {
    return str_starts_with($filename, 'thumb-');
}

// ── List object di prefix R2 (untuk folder 'person' / 'icon' / dll) ─────
function r2ListPrefix(string $prefix, bool $shallow = false): array
{
    $items = [];
    if (!defined('R2_ACCESS_KEY') || !defined('R2_SECRET_KEY')
        || !defined('R2_ENDPOINT') || !defined('R2_BUCKET')) {
        return $items;
    }
    try {
        $r2  = new R2Client(R2_ACCESS_KEY, R2_SECRET_KEY, R2_ENDPOINT, R2_BUCKET);
        $cleanPrefix = trim($prefix, '/');
        $objects = $r2->listObjects($cleanPrefix);
        $prefixWithSlash = $cleanPrefix !== '' ? ($cleanPrefix . '/') : '';
        $prefixLen = strlen($prefixWithSlash);

        foreach ($objects as $o) {
            $key = $o['key'] ?? '';
            if (str_ends_with($key, '/')) continue;

            if ($shallow && $prefixWithSlash !== '') {
                if (str_starts_with($key, $prefixWithSlash)) {
                    $remainder = substr($key, $prefixLen);
                    if ($remainder === '' || str_contains($remainder, '/')) {
                        continue;
                    }
                }
            }

            $nm  = basename($key);
            if ($nm === '' || $nm[0] === '.') continue;
            $items[] = [
                'name'      => $nm,
                'ext'       => strtolower(pathinfo($nm, PATHINFO_EXTENSION)),
                'is_image'  => in_array(strtolower(pathinfo($nm, PATHINFO_EXTENSION)), ['jpg','jpeg','png','webp','gif']),
                'size_kb'   => isset($o['size']) ? round($o['size'] / 1024, 1) : 0,
                'url'       => rtrim(defined('R2_CDN_URL') ? R2_CDN_URL : '', '/') . '/' . ltrim($key, '/'),
                'modified'  => isset($o['lastModified']) ? substr($o['lastModified'], 0, 16) : '',
                'compressed'=> false,
                'compress_info' => null,
                'r2_key'    => $key,
            ];
        }
    } catch (Throwable $e) {
        // diam — kembalikan list kosong
    }
    return $items;
}

// ── Hapus object R2 (khusus folder 'person') ─────────────────────────────
function r2DeleteObject(string $key): bool
{
    if (!defined('SECRET_R2_ACCESS_KEY_WRITE') || !defined('SECRET_R2_SECRET_KEY_WRITE')
        || !defined('SECRET_R2_ENDPOINT') || !defined('SECRET_R2_BUCKET')) {
        return false;
    }
    try {
        $r2 = new R2WriteClient(
            SECRET_R2_ACCESS_KEY_WRITE, SECRET_R2_SECRET_KEY_WRITE,
            SECRET_R2_ENDPOINT, SECRET_R2_BUCKET
        );
        $r2->deleteObject($key);
        return true;
    } catch (Throwable $e) {
        return false;
    }
}

// ── Compress ke WebP (media biasa) ───────────────────────────────────────
function mCompress(string $src, string $dst, int $q): bool {
    if (!function_exists('imagecreatefromjpeg') || !function_exists('imagewebp')) return false;
    $mime = function_exists('finfo_open') ? finfo_file(finfo_open(FILEINFO_MIME_TYPE), $src) : (@mime_content_type($src) ?: '');
    $im = null;
    if (str_contains($mime,'jpeg')||str_contains($mime,'jpg')) $im = @imagecreatefromjpeg($src);
    elseif (str_contains($mime,'png'))  $im = @imagecreatefrompng($src);
    elseif (str_contains($mime,'webp')) $im = @imagecreatefromwebp($src);
    elseif (str_contains($mime,'gif'))  $im = @imagecreatefromgif($src);
    if (!$im) return false;
    $ok = imagewebp($im, $dst, $q);
    imagedestroy($im);
    return $ok && file_exists($dst);
}

// ── Konversi ke JPG 1200×630 crop tengah (untuk OG WhatsApp) ────────────
function mConvertOG(string $src, string $dst, int $quality = 85): bool {
    if (!function_exists('imagecreatefromjpeg')) return false;

    $mime = @mime_content_type($src) ?: '';
    $im   = null;
    if (str_contains($mime,'jpeg')||str_contains($mime,'jpg')) $im = @imagecreatefromjpeg($src);
    elseif (str_contains($mime,'png'))  $im = @imagecreatefrompng($src);
    elseif (str_contains($mime,'webp')) $im = @imagecreatefromwebp($src);
    elseif (str_contains($mime,'gif'))  $im = @imagecreatefromgif($src);
    if (!$im) return false;

    $srcW = imagesx($im);
    $srcH = imagesy($im);
    $dstW = 1200;
    $dstH = 630;

    // Crop center
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

    $dir = dirname($dst);
    if (!is_dir($dir)) @mkdir($dir, 0755, true);

    $ok = imagejpeg($out, $dst, $quality);
    imagedestroy($im);
    imagedestroy($out);
    return $ok && file_exists($dst);
}

// ── Nama file OG dari nama file sumber ───────────────────────────────────
function ogFileName(string $srcName): string {
    $base = pathinfo($srcName, PATHINFO_FILENAME);
    return $base . '.jpg';
}

try {
    switch ($action) {

    // ────────────────────────────────────────────────────────────────────
    case 'list':
        $fkey = $body['folder'] ?? '';
        if (!isset($FOLDERS[$fkey])) { ob_end_clean(); apiJson(['error'=>'Folder tidak valid'],400); }
        $cfg   = $FOLDERS[$fkey];
        $files = [];
        $cache = mLoadCache($CACHE_FILE);

        if (($cfg['storage'] ?? 'local') === 'r2') {
            // ── R2-backed folder ──────────────────────────────────────────
            $prefix  = ltrim($cfg['path'], '/');
            $shallow = !empty($cfg['shallow']);
            $files   = r2ListPrefix($prefix, $shallow);
        } else {
            // ── Local-disk folder (umkm/artikel/gereja/icon/root_img) ─────
            $dir = $root . $cfg['path'];
            if (is_dir($dir)) {
                foreach (scandir($dir) as $nm) {
                    if ($nm[0]==='.') continue;
                    $fp = $dir.'/'.$nm;
                    if (!is_file($fp)) continue;
                    $rel  = $cfg['path'].'/'.$nm;
                    $kb   = round(filesize($fp)/1024,1);
                    $comp = $cache[$rel] ?? null;
                    $files[] = [
                        'name'          => $nm,
                        'ext'           => strtolower(pathinfo($nm,PATHINFO_EXTENSION)),
                        'is_image'      => mIsImg($nm,$IMG_EXTS),
                        'size_kb'       => $kb,
                        'url'           => $rel,
                        'modified'      => date('Y-m-d H:i',filemtime($fp)),
                        'compressed'    => $comp!==null,
                        'compress_info' => $comp,
                    ];
                }
            }
        }
        usort($files, fn($a,$b)=>strcmp($b['modified']??'',$a['modified']??''));
        ob_end_clean(); apiJson(['success'=>true,'data'=>$files,'folder'=>$cfg,'folder_key'=>$fkey]);
        break;

    // ────────────────────────────────────────────────────────────────────
    case 'rename':
        $fkey = $body['folder']??''; $old=basename($body['old_name']??''); $new=basename($body['new_name']??'');
        $cfg  = $FOLDERS[$fkey] ?? null;
        if (!$cfg||!$old||!$new) { ob_end_clean(); apiJson(['error'=>'Parameter tidak lengkap'],400); }
        if (($cfg['storage'] ?? 'local') === 'r2') {
            ob_end_clean(); apiJson(['error'=>'Rename tidak didukung untuk folder R2 (person). Hapus & upload ulang jika perlu.'], 400);
            break;
        }
        $oldP = $root.$cfg['path'].'/'.$old; $newP = $root.$cfg['path'].'/'.$new;
        if (!file_exists($oldP)) { ob_end_clean(); apiJson(['error'=>'File tidak ditemukan'],404); }
        if (file_exists($newP))  { ob_end_clean(); apiJson(['error'=>'Nama sudah dipakai'],409); }
        if (!rename($oldP,$newP)){ ob_end_clean(); apiJson(['error'=>'Gagal rename'],500); }
        $cache=$cache=mLoadCache($CACHE_FILE);$oldR=$cfg['path'].'/'.$old;$newR=$cfg['path'].'/'.$new;
        if(isset($cache[$oldR])){$cache[$newR]=$cache[$oldR];unset($cache[$oldR]);mSaveCache($CACHE_FILE,$cache);}
        getLogger()->log($currentUser,'UPDATE','media',"Rename: $old → $new");
        ob_end_clean(); apiJson(['success'=>true,'new_name'=>$new,'new_url'=>$cfg['path'].'/'.$new]);
        break;

    // ────────────────────────────────────────────────────────────────────
    case 'delete':
        $fkey = $body['folder']??''; $nm=basename($body['name']??''); $cfg=$FOLDERS[$fkey]??null;
        if (!$cfg||!$nm){ ob_end_clean(); apiJson(['error'=>'Parameter tidak lengkap'],400); }
        if (($cfg['storage'] ?? 'local') === 'r2') {
            $prefix = ltrim($cfg['path'], '/');
            $r2key  = rtrim($prefix, '/') . '/' . $nm;
            if (!r2DeleteObject($r2key)) { ob_end_clean(); apiJson(['error'=>'Gagal hapus object R2'],500); }
            getLogger()->log($currentUser,'DELETE','media',"Hapus R2: $r2key ({$cfg['label']})");
            ob_end_clean(); apiJson(['success'=>true]);
            break;
        }
        $fp = $root.$cfg['path'].'/'.$nm;
        if (!file_exists($fp)) { ob_end_clean(); apiJson(['error'=>'File tidak ditemukan'],404); }
        if (!unlink($fp))      { ob_end_clean(); apiJson(['error'=>'Gagal hapus'],500); }
        $cache=mLoadCache($CACHE_FILE); $rel=$cfg['path'].'/'.$nm;
        if(isset($cache[$rel])){unset($cache[$rel]);mSaveCache($CACHE_FILE,$cache);}
        getLogger()->log($currentUser,'DELETE','media',"Hapus: $nm ({$cfg['label']})");
        ob_end_clean(); apiJson(['success'=>true]);
        break;

    // ────────────────────────────────────────────────────────────────────
    case 'compress':
        $fkey=$body['folder']??''; $nm=basename($body['name']??''); $cfg=$FOLDERS[$fkey]??null;
        if (!$cfg||!$nm){ ob_end_clean(); apiJson(['error'=>'Parameter tidak lengkap'],400); }
        if ($cfg['skip']){ ob_end_clean(); apiJson(['error'=>'Folder icon tidak dikompress'],400); }
        if (($cfg['storage'] ?? 'local') === 'r2') {
            ob_end_clean(); apiJson(['error'=>'Compress tidak berlaku untuk folder R2. Foto person sudah otomatis dikompres saat upload.'], 400);
            break;
        }
        $srcP=$root.$cfg['path'].'/'.$nm;
        if (!file_exists($srcP)||!mIsImg($nm,$IMG_EXTS)){ ob_end_clean(); apiJson(['error'=>'File tidak ditemukan/bukan gambar'],404); }
        $origKb=round(filesize($srcP)/1024,1);
        $newNm=pathinfo($nm,PATHINFO_FILENAME).'.webp'; $dstP=$root.$cfg['path'].'/'.$newNm; $tmpP=$dstP.'.tmp';
        if (!mCompress($srcP,$tmpP,$cfg['quality'])){ @unlink($tmpP); ob_end_clean(); apiJson(['error'=>'Gagal kompress (GD/WebP tidak tersedia)'],500); }
        $newKb=round(filesize($tmpP)/1024,1);
        @unlink($srcP); rename($tmpP,$dstP);
        $cache=mLoadCache($CACHE_FILE); $rel=$cfg['path'].'/'.$newNm;
        $cache[$rel]=['at'=>date('Y-m-d H:i:s'),'orig_kb'=>(int)$origKb,'new_kb'=>(int)$newKb];
        $oldR=$cfg['path'].'/'.$nm; if($oldR!==$rel&&isset($cache[$oldR]))unset($cache[$oldR]);
        mSaveCache($CACHE_FILE,$cache);
        $saved=round(($origKb-$newKb)/max($origKb,1)*100);
        getLogger()->log($currentUser,'UPDATE','media',"Kompress: $nm → $newNm ({$origKb}KB→{$newKb}KB -$saved%)");
        ob_end_clean(); apiJson(['success'=>true,'new_name'=>$newNm,'orig_kb'=>$origKb,'new_kb'=>$newKb,'saved_pct'=>$saved]);
        break;

    // ────────────────────────────────────────────────────────────────────
    case 'bulk_compress':
        $fkey=$body['folder']??''; $cfg=$FOLDERS[$fkey]??null;
        if (!$cfg){ ob_end_clean(); apiJson(['error'=>'Folder tidak valid'],400); }
        if ($cfg['skip']){ ob_end_clean(); apiJson(['success'=>true,'skipped'=>true,'message'=>'Folder icon tidak dikompress']); }
        if (($cfg['storage'] ?? 'local') === 'r2') {
            ob_end_clean(); apiJson(['success'=>true,'skipped'=>true,'message'=>'Folder R2 tidak perlu bulk-compress (foto person sudah otomatis dikompres saat upload).']);
            break;
        }
        $dir=$root.$cfg['path'];
        if (!is_dir($dir)){ ob_end_clean(); apiJson(['error'=>'Folder tidak ditemukan'],404); }
        @set_time_limit(180);
        $cache=mLoadCache($CACHE_FILE);
        $r=['compressed'=>0,'skipped'=>0,'failed'=>0,'saved_kb'=>0,'details'=>[]];
        foreach(scandir($dir) as $nm){
            if($nm[0]==='.') continue;
            $srcP=$dir.'/'.$nm; if(!is_file($srcP)||!mIsImg($nm,$IMG_EXTS)) continue;
            $rel=$cfg['path'].'/'.$nm;
            if(isset($cache[$rel])){ $r['skipped']++; continue; }
            $ext=strtolower(pathinfo($nm,PATHINFO_EXTENSION));
            if($ext==='webp'&&filesize($srcP)<51200){ $cache[$rel]=['at'=>date('Y-m-d H:i:s'),'orig_kb'=>round(filesize($srcP)/1024),'new_kb'=>round(filesize($srcP)/1024)]; $r['skipped']++; continue; }
            $origKb=round(filesize($srcP)/1024,1);
            $newNm=pathinfo($nm,PATHINFO_FILENAME).'.webp'; $dstP=$dir.'/'.$newNm; $tmpP=$dstP.'.tmp';
            if(!mCompress($srcP,$tmpP,$cfg['quality'])){ @unlink($tmpP); $r['failed']++; continue; }
            $newKb=round(filesize($tmpP)/1024,1);
            @unlink($srcP); rename($tmpP,$dstP);
            $newR=$cfg['path'].'/'.$newNm;
            $cache[$newR]=['at'=>date('Y-m-d H:i:s'),'orig_kb'=>(int)$origKb,'new_kb'=>(int)$newKb];
            if($newR!==$rel&&isset($cache[$rel]))unset($cache[$rel]);
            $r['compressed']++; $r['saved_kb']+=$origKb-$newKb;
            $r['details'][]=['from'=>$nm,'to'=>$newNm,'orig_kb'=>$origKb,'new_kb'=>$newKb];
        }
        mSaveCache($CACHE_FILE,$cache); $r['saved_kb']=round($r['saved_kb'],1);
        getLogger()->log($currentUser,'UPDATE','media',"Bulk compress {$cfg['label']}: {$r['compressed']} file, hemat {$r['saved_kb']}KB");
        ob_end_clean(); apiJson(['success'=>true,'results'=>$r]);
        break;

    // ────────────────────────────────────────────────────────────────────
    case 'stats':
        $cache=$mC=mLoadCache($CACHE_FILE); $result=[];
        foreach($FOLDERS as $k=>$cfg){
            $cnt=0; $kb=0; $cmp=0;
            if (($cfg['storage'] ?? 'local') === 'r2') {
                $shallow = !empty($cfg['shallow']);
                $items = r2ListPrefix(ltrim($cfg['path'], '/'), $shallow);
                $cnt = count($items);
                foreach ($items as $it) { $kb += (float)($it['size_kb'] ?? 0); }
            } else {
                $dir=$root.$cfg['path'];
                if(is_dir($dir)) foreach(scandir($dir) as $nm){ if($nm[0]==='.') continue; $fp=$dir.'/'.$nm; if(!is_file($fp)) continue; $cnt++; $kb+=filesize($fp)/1024; if(isset($cache[$cfg['path'].'/'.$nm]))$cmp++; }
            }
            $result[$k]=['label'=>$cfg['label'],'path'=>$cfg['path'],'count'=>$cnt,'total_kb'=>round($kb,1),'compressed_count'=>$cmp,'skip'=>$cfg['skip']];
        }
        ob_end_clean(); apiJson(['success'=>true,'data'=>$result]);
        break;

    // ════════════════════════════════════════════════════════════════════
    // OG PREVIEW ACTIONS
    // ════════════════════════════════════════════════════════════════════

    // ── List artikel images + status OG ──────────────────────────────
    case 'list_og':
        $ogCache = mLoadCache($OG_CACHE);
        $items   = [];
        $cdn     = defined('R2_CDN_URL') ? rtrim(R2_CDN_URL, '/') : 'https://img.parokitulungagung.org';

        // Index file OG yang sudah ada di R2 ogpreview/
        $ogExisting  = [];
        $r2OgObjects = r2ListPrefix('ogpreview/');
        foreach ($r2OgObjects as $ogo) {
            $ogExisting[$ogo['name']] = $ogo;
        }

        // Ambil daftar artikel dari R2
        $r2ArtObjects = r2ListPrefix('artikel/');
        foreach ($r2ArtObjects as $art) {
            $nm = $art['name'];
            if (!isThumbnail($nm)) continue;

            $ogName = ogFileName($nm);
            $hasOG  = isset($ogExisting[$ogName]);
            $ogObj  = $ogExisting[$ogName] ?? null;
            $ogInfo = $ogCache[$ogName] ?? null;

            $items[] = [
                'src_name'    => $nm,
                'src_url'     => $art['url'],
                'src_kb'      => $art['size_kb'],
                'og_name'     => $ogName,
                'og_url'      => $hasOG ? $ogObj['url'] : null,
                'og_full_url' => $hasOG ? $ogObj['url'] : null,
                'og_kb'       => $hasOG ? ($ogObj['size_kb'] ?? null) : null,
                'has_og'      => $hasOG,
                'og_info'     => $ogInfo,
                'modified'    => $art['modified'] ?? '',
            ];
        }

        // Fallback jika R2 artikel belum ada/kosong, cek folder lokal
        if (empty($items)) {
            $artikelDir = $root . '/img/artikel';
            if (is_dir($artikelDir)) {
                foreach (scandir($artikelDir) as $nm) {
                    if ($nm[0] === '.') continue;
                    $fp = $artikelDir . '/' . $nm;
                    if (!is_file($fp) || !mIsImg($nm, $IMG_EXTS) || !isThumbnail($nm)) continue;
                    $ogName = ogFileName($nm);
                    $hasOG  = isset($ogExisting[$ogName]) || file_exists($OG_DIR_FULL . '/' . $ogName);
                    $items[] = [
                        'src_name'    => $nm,
                        'src_url'     => '/img/artikel/' . $nm,
                        'src_kb'      => round(filesize($fp) / 1024, 1),
                        'og_name'     => $ogName,
                        'og_url'      => $hasOG ? ($cdn . '/ogpreview/' . $ogName) : null,
                        'og_full_url' => $hasOG ? ($cdn . '/ogpreview/' . $ogName) : null,
                        'og_kb'       => $hasOG ? round(@filesize($OG_DIR_FULL . '/' . $ogName) / 1024, 1) : null,
                        'has_og'      => $hasOG,
                        'og_info'     => $ogCache[$ogName] ?? null,
                        'modified'    => date('Y-m-d H:i', filemtime($fp)),
                    ];
                }
            }
        }

        usort($items, fn($a,$b) => $a['has_og'] <=> $b['has_og'] ?: strcmp($b['modified'], $a['modified']));

        ob_end_clean();
        apiJson([
            'success'    => true,
            'data'       => $items,
            'og_dir_url' => $cdn . '/ogpreview',
            'total'      => count($items),
            'has_og'     => count(array_filter($items, fn($i) => $i['has_og'])),
        ]);
        break;

    // ── Konversi satu file ke OG JPG 1200×630 ────────────────────────
    case 'convert_og':
        $nm = basename($body['name'] ?? '');
        if (!$nm) { ob_end_clean(); apiJson(['error' => 'Nama file wajib diisi'], 400); }

        // Hanya thumbnail yang boleh dikonversi ke OG
        if (!isThumbnail($nm)) {
            ob_end_clean(); apiJson(['error' => 'Hanya thumbnail artikel (thumb-*) yang dapat dikonversi ke OG Preview'], 400);
        }

        $cdn = defined('R2_CDN_URL') ? rtrim(R2_CDN_URL, '/') : 'https://img.parokitulungagung.org';
        $srcBytes = @file_get_contents($cdn . '/artikel/' . $nm);
        if (!$srcBytes) {
            $srcP = $root . '/img/artikel/' . $nm;
            if (file_exists($srcP)) $srcBytes = @file_get_contents($srcP);
        }
        if (!$srcBytes) {
            ob_end_clean(); apiJson(['error' => 'File gambar tidak dapat dibaca dari R2 atau lokal'], 404);
        }

        $ogName = ogFileName($nm);
        $tmpSrc = tempnam(sys_get_temp_dir(), 'arts_') . '.' . pathinfo($nm, PATHINFO_EXTENSION);
        file_put_contents($tmpSrc, $srcBytes);

        $tmpOut = tempnam(sys_get_temp_dir(), 'og_') . '.jpg';
        $origKb = round(strlen($srcBytes) / 1024, 1);

        if (!mConvertOG($tmpSrc, $tmpOut)) {
            @unlink($tmpSrc);
            @unlink($tmpOut);
            ob_end_clean(); apiJson(['error' => 'Gagal konversi (GD tidak tersedia atau file rusak)'], 500);
        }
        @unlink($tmpSrc);

        $newKb = round(filesize($tmpOut) / 1024, 1);
        $ogKey = 'ogpreview/' . $ogName;

        // Upload ke R2
        require_once __DIR__ . '/../../includes/R2WriteClient.php';
        $ak = defined('SECRET_R2_ACCESS_KEY_WRITE') ? SECRET_R2_ACCESS_KEY_WRITE : (defined('R2_ACCESS_KEY') ? R2_ACCESS_KEY : null);
        $sk = defined('SECRET_R2_SECRET_KEY_WRITE') ? SECRET_R2_SECRET_KEY_WRITE : (defined('R2_SECRET_KEY') ? R2_SECRET_KEY : null);
        $ep = defined('SECRET_R2_ENDPOINT') ? SECRET_R2_ENDPOINT : (defined('R2_ENDPOINT') ? R2_ENDPOINT : null);
        $bk = defined('SECRET_R2_BUCKET') ? SECRET_R2_BUCKET : (defined('R2_BUCKET') ? R2_BUCKET : null);

        if ($ak && $sk && $ep && $bk) {
            try {
                $r2 = new R2WriteClient($ak, $sk, $ep, $bk);
                $r2->putObjectFromFile($ogKey, $tmpOut, 'image/jpeg');
            } catch (Throwable $e) {
                @unlink($tmpOut);
                ob_end_clean(); apiJson(['error' => 'Gagal upload ke R2: ' . $e->getMessage()], 500);
            }
        }
        @unlink($tmpOut);

        $ogUrl = $cdn . '/' . $ogKey;
        $ogCache = mLoadCache($OG_CACHE);
        $ogCache[$ogName] = [
            'at'      => date('Y-m-d H:i:s'),
            'src'     => $nm,
            'orig_kb' => (int)$origKb,
            'og_kb'   => (int)$newKb,
            'size'    => '1200x630',
        ];
        mSaveCache($OG_CACHE, $ogCache);

        getLogger()->log($currentUser, 'CREATE', 'media', "OG Convert: $nm → $ogName ({$origKb}KB → {$newKb}KB)");

        ob_end_clean();
        apiJson([
            'success'     => true,
            'og_name'     => $ogName,
            'og_url'      => $ogUrl,
            'og_full_url' => $ogUrl,
            'orig_kb'     => $origKb,
            'og_kb'       => $newKb,
        ]);
        break;

    // ── Konversi semua file artikel ke OG sekaligus ───────────────────
    case 'bulk_convert_og':
        $names = $body['names'] ?? [];
        $force = (bool)($body['force'] ?? false);

        @set_time_limit(300);
        $cdn     = defined('R2_CDN_URL') ? rtrim(R2_CDN_URL, '/') : 'https://img.parokitulungagung.org';
        $ogCache = mLoadCache($OG_CACHE);
        $r = ['converted' => 0, 'skipped' => 0, 'failed' => 0, 'details' => []];

        $ogExisting = [];
        foreach (r2ListPrefix('ogpreview/') as $ogo) {
            $ogExisting[$ogo['name']] = true;
        }

        if (!empty($names)) {
            $targets = array_filter(array_map('basename', $names), 'isThumbnail');
        } else {
            $targets = [];
            foreach (r2ListPrefix('artikel/') as $art) {
                $nm = $art['name'];
                if (!isThumbnail($nm)) continue;
                $targets[] = $nm;
            }
        }

        require_once __DIR__ . '/../../includes/R2WriteClient.php';
        $ak = defined('SECRET_R2_ACCESS_KEY_WRITE') ? SECRET_R2_ACCESS_KEY_WRITE : (defined('R2_ACCESS_KEY') ? R2_ACCESS_KEY : null);
        $sk = defined('SECRET_R2_SECRET_KEY_WRITE') ? SECRET_R2_SECRET_KEY_WRITE : (defined('R2_SECRET_KEY') ? R2_SECRET_KEY : null);
        $ep = defined('SECRET_R2_ENDPOINT') ? SECRET_R2_ENDPOINT : (defined('R2_ENDPOINT') ? R2_ENDPOINT : null);
        $bk = defined('SECRET_R2_BUCKET') ? SECRET_R2_BUCKET : (defined('R2_BUCKET') ? R2_BUCKET : null);
        $r2 = ($ak && $sk && $ep && $bk) ? new R2WriteClient($ak, $sk, $ep, $bk) : null;

        foreach ($targets as $nm) {
            $ogName = ogFileName($nm);
            if (!$force && isset($ogExisting[$ogName])) {
                $r['skipped']++;
                continue;
            }

            $srcBytes = @file_get_contents($cdn . '/artikel/' . $nm);
            if (!$srcBytes) {
                $srcP = $root . '/img/artikel/' . $nm;
                if (file_exists($srcP)) $srcBytes = @file_get_contents($srcP);
            }
            if (!$srcBytes) { $r['skipped']++; continue; }

            $tmpSrc = tempnam(sys_get_temp_dir(), 'arts_') . '.' . pathinfo($nm, PATHINFO_EXTENSION);
            file_put_contents($tmpSrc, $srcBytes);
            $tmpOut = tempnam(sys_get_temp_dir(), 'og_') . '.jpg';

            if (!mConvertOG($tmpSrc, $tmpOut)) {
                @unlink($tmpSrc);
                @unlink($tmpOut);
                $r['failed']++;
                continue;
            }
            @unlink($tmpSrc);

            if ($r2) {
                try {
                    $r2->putObjectFromFile('ogpreview/' . $ogName, $tmpOut, 'image/jpeg');
                } catch (Throwable $e) {
                    @unlink($tmpOut);
                    $r['failed']++;
                    continue;
                }
            }
            $newKb = round(filesize($tmpOut) / 1024, 1);
            @unlink($tmpOut);

            $origKb = round(strlen($srcBytes) / 1024, 1);
            $ogCache[$ogName] = [
                'at'      => date('Y-m-d H:i:s'),
                'src'     => $nm,
                'orig_kb' => (int)$origKb,
                'og_kb'   => (int)$newKb,
                'size'    => '1200x630',
            ];
            $r['converted']++;
            $r['details'][] = [
                'src'     => $nm,
                'og'      => $ogName,
                'orig_kb' => $origKb,
                'og_kb'   => $newKb,
            ];
        }

        mSaveCache($OG_CACHE, $ogCache);
        getLogger()->log($currentUser, 'CREATE', 'media', "Bulk OG Convert: {$r['converted']} file dikonversi ke R2");

        ob_end_clean();
        apiJson(['success' => true, 'results' => $r]);
        break;

    // ── Hapus file OG ─────────────────────────────────────────────────
    case 'delete_og':
        $ogName = basename($body['og_name'] ?? '');
        if (!$ogName) { ob_end_clean(); apiJson(['error' => 'og_name wajib diisi'], 400); }

        $ogPath = $OG_DIR_FULL . '/' . $ogName;
        if (!file_exists($ogPath)) { ob_end_clean(); apiJson(['error' => 'File OG tidak ditemukan'], 404); }
        if (!unlink($ogPath))      { ob_end_clean(); apiJson(['error' => 'Gagal hapus'], 500); }

        $ogCache = mLoadCache($OG_CACHE);
        if (isset($ogCache[$ogName])) {
            unset($ogCache[$ogName]);
            mSaveCache($OG_CACHE, $ogCache);
        }

        getLogger()->log($currentUser, 'DELETE', 'media', "Hapus OG: $ogName");

        ob_end_clean();
        apiJson(['success' => true]);
        break;

    // ── Migrasi: hapus prefix "og-" dari file lama di /img/ogpreview/ ─
    case 'migrate_og_prefix':
        if (!is_dir($OG_DIR_FULL)) {
            ob_end_clean(); apiJson(['error' => 'Folder ogpreview tidak ditemukan'], 404);
        }

        $ogCache  = mLoadCache($OG_CACHE);
        $r = ['renamed' => 0, 'skipped' => 0, 'failed' => 0, 'details' => []];

        foreach (scandir($OG_DIR_FULL) as $nm) {
            if ($nm[0] === '.') continue;
            if (!str_starts_with($nm, 'og-')) { $r['skipped']++; continue; }

            $newNm  = substr($nm, 3);
            $oldP   = $OG_DIR_FULL . '/' . $nm;
            $newP   = $OG_DIR_FULL . '/' . $newNm;

            if (!is_file($oldP)) { $r['skipped']++; continue; }
            if (file_exists($newP)) {
                @unlink($oldP);
                if (isset($ogCache[$nm])) { unset($ogCache[$nm]); }
                $r['skipped']++;
                continue;
            }

            if (!rename($oldP, $newP)) { $r['failed']++; continue; }

            if (isset($ogCache[$nm])) {
                $ogCache[$newNm] = $ogCache[$nm];
                unset($ogCache[$nm]);
            }

            $r['renamed']++;
            $r['details'][] = ['from' => $nm, 'to' => $newNm];
        }

        mSaveCache($OG_CACHE, $ogCache);
        getLogger()->log($currentUser, 'UPDATE', 'media', "Migrasi prefix OG: {$r['renamed']} file di-rename");

        ob_end_clean();
        apiJson(['success' => true, 'results' => $r]);
        break;

    // ────────────────────────────────────────────────────────────────────
    default:
        ob_end_clean(); apiJson(['error'=>'Action tidak dikenal'],400);
    }

} catch (Throwable $e) {
    error_log('[media.php] '.$e->getMessage());
    ob_end_clean(); apiJson(['error'=>$e->getMessage()],500);
}