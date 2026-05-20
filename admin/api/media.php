<?php
/**
 * admin/api/media.php
 * API manajemen file media: list, rename, delete, compress, bulk-compress, stats
 * + OG Preview: convert_og, bulk_convert_og, delete_og, list_og
 */
ob_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
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
$FOLDERS = [
    'galeri'   => ['path' => '/public/galeri',  'label' => 'Galeri Foto',   'quality' => 75, 'skip' => false],
    'umkm'     => ['path' => '/public/umkm',    'label' => 'UMKM Umat',     'quality' => 78, 'skip' => false],
    'artikel'  => ['path' => '/img/artikel',    'label' => 'Artikel',       'quality' => 75, 'skip' => false],
    'gereja'   => ['path' => '/img/gereja',     'label' => 'Foto Gereja',   'quality' => 75, 'skip' => false],
    'icon'     => ['path' => '/img/icon',       'label' => 'Icon',          'quality' => 0,  'skip' => true],
    'person'   => ['path' => '/img/person',     'label' => 'Foto Person',   'quality' => 82, 'skip' => false],
    'root_img' => ['path' => '/img',            'label' => 'Root /img',     'quality' => 78, 'skip' => false],
];
$CACHE_FILE   = $root . '/cache/media_compressed.json';
$OG_DIR_PATH  = '/img/ogpreview';                          // URL path
$OG_DIR_FULL  = $root . $OG_DIR_PATH;                     // Filesystem path
$OG_CACHE     = $root . '/cache/media_og.json';           // Cache file OG
$IMG_EXTS     = ['jpg','jpeg','png','webp','gif'];

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
        $dir   = $root . $cfg['path'];
        $cache = mLoadCache($CACHE_FILE);
        $files = [];
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
        usort($files, fn($a,$b)=>strcmp($b['modified'],$a['modified']));
        ob_end_clean(); apiJson(['success'=>true,'data'=>$files,'folder'=>$cfg,'folder_key'=>$fkey]);
        break;

    // ────────────────────────────────────────────────────────────────────
    case 'rename':
        $fkey = $body['folder']??''; $old=basename($body['old_name']??''); $new=basename($body['new_name']??'');
        $cfg  = $FOLDERS[$fkey] ?? null;
        if (!$cfg||!$old||!$new) { ob_end_clean(); apiJson(['error'=>'Parameter tidak lengkap'],400); }
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
            $dir=$root.$cfg['path']; $cnt=0; $kb=0; $cmp=0;
            if(is_dir($dir)) foreach(scandir($dir) as $nm){ if($nm[0]==='.') continue; $fp=$dir.'/'.$nm; if(!is_file($fp)) continue; $cnt++; $kb+=filesize($fp)/1024; if(isset($cache[$cfg['path'].'/'.$nm]))$cmp++; }
            $result[$k]=['label'=>$cfg['label'],'path'=>$cfg['path'],'count'=>$cnt,'total_kb'=>round($kb,1),'compressed_count'=>$cmp,'skip'=>$cfg['skip']];
        }
        ob_end_clean(); apiJson(['success'=>true,'data'=>$result]);
        break;

    // ════════════════════════════════════════════════════════════════════
    // OG PREVIEW ACTIONS
    // ════════════════════════════════════════════════════════════════════

    // ── List artikel images + status OG ──────────────────────────────
    case 'list_og':
        $artikelDir = $root . $FOLDERS['artikel']['path'];
        $ogCache    = mLoadCache($OG_CACHE);

        if (!is_dir($OG_DIR_FULL)) @mkdir($OG_DIR_FULL, 0755, true);

        $items = [];
        if (is_dir($artikelDir)) {
            foreach (scandir($artikelDir) as $nm) {
                if ($nm[0] === '.') continue;
                $fp = $artikelDir . '/' . $nm;
                if (!is_file($fp) || !mIsImg($nm, $IMG_EXTS)) continue;

                // ── FILTER: hanya thumbnail (diawali "thumb-") yang masuk OG ──
                // Gambar dari isi artikel tidak perlu OG Preview.
                if (!isThumbnail($nm)) continue;

                $ogName = ogFileName($nm);
                $ogPath = $OG_DIR_FULL . '/' . $ogName;
                $hasOG  = file_exists($ogPath);
                $ogInfo = $ogCache[$ogName] ?? null;

                $items[] = [
                    'src_name'    => $nm,
                    'src_url'     => $FOLDERS['artikel']['path'] . '/' . $nm,
                    'src_kb'      => round(filesize($fp) / 1024, 1),
                    'og_name'     => $ogName,
                    'og_url'      => $hasOG ? ($OG_DIR_PATH . '/' . $ogName) : null,
                    'og_full_url' => $hasOG ? ('https://www.parokitulungagung.org' . $OG_DIR_PATH . '/' . $ogName) : null,
                    'og_kb'       => $hasOG ? round(filesize($ogPath) / 1024, 1) : null,
                    'has_og'      => $hasOG,
                    'og_info'     => $ogInfo,
                    'modified'    => date('Y-m-d H:i', filemtime($fp)),
                ];
            }
        }

        usort($items, fn($a,$b) => $a['has_og'] <=> $b['has_og'] ?: strcmp($b['modified'], $a['modified']));

        ob_end_clean();
        apiJson([
            'success'    => true,
            'data'       => $items,
            'og_dir_url' => $OG_DIR_PATH,
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

        $srcP = $root . $FOLDERS['artikel']['path'] . '/' . $nm;
        if (!file_exists($srcP) || !mIsImg($nm, $IMG_EXTS)) {
            ob_end_clean(); apiJson(['error' => 'File tidak ditemukan atau bukan gambar'], 404);
        }

        if (!is_dir($OG_DIR_FULL)) @mkdir($OG_DIR_FULL, 0755, true);

        $ogName = ogFileName($nm);
        $ogPath = $OG_DIR_FULL . '/' . $ogName;
        $tmpP   = $ogPath . '.tmp';

        $origKb = round(filesize($srcP) / 1024, 1);

        if (!mConvertOG($srcP, $tmpP)) {
            @unlink($tmpP);
            ob_end_clean(); apiJson(['error' => 'Gagal konversi (GD tidak tersedia atau file rusak)'], 500);
        }

        if (file_exists($ogPath)) @unlink($ogPath);
        rename($tmpP, $ogPath);

        $newKb  = round(filesize($ogPath) / 1024, 1);
        $ogUrl  = $OG_DIR_PATH . '/' . $ogName;
        $ogFull = 'https://www.parokitulungagung.org' . $ogUrl;

        $ogCache          = mLoadCache($OG_CACHE);
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
            'og_full_url' => $ogFull,
            'orig_kb'     => $origKb,
            'og_kb'       => $newKb,
        ]);
        break;

    // ── Konversi semua file artikel ke OG sekaligus ───────────────────
    case 'bulk_convert_og':
        $names = $body['names'] ?? [];
        $force = (bool)($body['force'] ?? false);

        $artikelDir = $root . $FOLDERS['artikel']['path'];
        if (!is_dir($artikelDir)) { ob_end_clean(); apiJson(['error' => 'Folder artikel tidak ditemukan'], 404); }
        if (!is_dir($OG_DIR_FULL)) @mkdir($OG_DIR_FULL, 0755, true);

        @set_time_limit(300);
        $ogCache = mLoadCache($OG_CACHE);
        $r = ['converted' => 0, 'skipped' => 0, 'failed' => 0, 'details' => []];

        if (!empty($names)) {
            // Dari pilihan manual — tetap filter hanya thumbnail
            $targets = array_filter(array_map('basename', $names), 'isThumbnail');
        } else {
            // Otomatis: ambil semua yang belum punya OG, hanya thumbnail
            $targets = [];
            foreach (scandir($artikelDir) as $nm) {
                if ($nm[0] === '.') continue;
                $fp = $artikelDir . '/' . $nm;
                if (!is_file($fp) || !mIsImg($nm, $IMG_EXTS)) continue;
                if (!isThumbnail($nm)) continue; // ← lewati gambar konten
                $targets[] = $nm;
            }
        }

        foreach ($targets as $nm) {
            $srcP = $artikelDir . '/' . $nm;
            if (!file_exists($srcP) || !mIsImg($nm, $IMG_EXTS)) { $r['skipped']++; continue; }

            $ogName = ogFileName($nm);
            $ogPath = $OG_DIR_FULL . '/' . $ogName;

            if (!$force && file_exists($ogPath)) { $r['skipped']++; continue; }

            $origKb = round(filesize($srcP) / 1024, 1);
            $tmpP   = $ogPath . '.tmp';

            if (!mConvertOG($srcP, $tmpP)) {
                @unlink($tmpP); $r['failed']++; continue;
            }

            if (file_exists($ogPath)) @unlink($ogPath);
            rename($tmpP, $ogPath);

            $newKb            = round(filesize($ogPath) / 1024, 1);
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
        getLogger()->log($currentUser, 'CREATE', 'media', "Bulk OG Convert: {$r['converted']} file dikonversi");

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