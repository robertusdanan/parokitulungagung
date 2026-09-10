<?php





// Path ke error.php yang benar (di root public_html)
define('ERROR_PAGE_PATH', dirname(__DIR__) . '/error.php');

/**
 * Resolver path absolut ke folder private/ di luar public_html.
 *
 * folder private/ SELALU berada 1 tingkat di atas public_html,
 * sehingga path-nya relatif terhadap lokasi root website,
 * tidak tergantung /home/ejtkecoh atau user hosting tertentu.
 *
 * Logika pencarian (berurutan):
 *   1. PARENT dari DOCUMENT_ROOT/public_html
 *   2. PARENT dari SCRIPT_FILENAME (file PHP yang sedang dieksekusi)
 *   3. PARENT dari __FILE__ (file functions.php ini)
 *
 * @param string $file  nama file di dalam folder private (default 'secrets.php')
 * @return string       path absolut ke file
 */
function privatePath(string $file = 'secrets.php'): string
{
    $candidates = [];

    // 1) Parent dari DOCUMENT_ROOT (paling reliable di web server)
    if (!empty($_SERVER['DOCUMENT_ROOT'])) {
        $candidates[] = dirname(rtrim($_SERVER['DOCUMENT_ROOT'], '/\\')) . DIRECTORY_SEPARATOR . 'private' . DIRECTORY_SEPARATOR . $file;
    }

    // 2) Turun dari SCRIPT_FILENAME ke public_html, lalu naik 1.
    //    SCRIPT_FILENAME absolut, DOCUMENT_ROOT absolut. public_html = DOC_ROOT.
    //    Contoh: /home/<user>/public_html/index.php atau /pages/agenda.php
    //      → ambil parent SCRIPT_FILENAME = folder file
    //      → jika parent == DOCUMENT_ROOT, parent dari DOCUMENT_ROOT = /home/<user>
    //      → jika parent != DOCUMENT_ROOT, naik lagi
    if (!empty($_SERVER['SCRIPT_FILENAME']) && !empty($_SERVER['DOCUMENT_ROOT'])) {
        $docRoot = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\');
        $script  = $_SERVER['SCRIPT_FILENAME'];
        $parent  = dirname($script);
        // Naik sampai parent == DOCUMENT_ROOT (root public_html)
        while ($parent !== $docRoot && $parent !== '' && $parent !== '/' && $parent !== '.') {
            $newParent = dirname($parent);
            if ($newParent === $parent) break; // sudah di root filesystem
            $parent = $newParent;
        }
        // Sekarang $parent = DOCUMENT_ROOT → naik 1 ke /home/<user>
        if ($parent === $docRoot) {
            $candidates[] = dirname($parent) . DIRECTORY_SEPARATOR . 'private' . DIRECTORY_SEPARATOR . $file;
        }
    }

    // 3) Fallback dari __FILE__ (file functions.php di includes/)
    //    dirname(__FILE__, 2) = root project (mis. /workspace/parokitulungagung atau /home/user/public_html)
    //    dirname(__FILE__, 3) = parent root  (mis. /workspace atau /home/user)
    $candidates[] = dirname(__FILE__, 3) . DIRECTORY_SEPARATOR . 'private' . DIRECTORY_SEPARATOR . $file;
    $candidates[] = dirname(__FILE__, 2) . DIRECTORY_SEPARATOR . 'private' . DIRECTORY_SEPARATOR . $file;

    foreach ($candidates as $c) {
        if (is_file($c)) {
            return $c;
        }
    }
    // Tidak ditemukan — kembalikan kandidat pertama agar error PHP jelas
    return $candidates[0] ?? (dirname(__FILE__, 2) . DIRECTORY_SEPARATOR . 'private' . DIRECTORY_SEPARATOR . $file);
}

/**
 * Memuat private/secrets.php dari lokasi di luar public_html.
 * Dipanggil di awal file config / endpoint yang butuh kredensial.
 * Sekali dimuat, file di-skip (aman dipanggil berulang).
 */
function requireSecrets(): void
{
    static $loaded = false;
    if ($loaded) {
        return;
    }
    $path = privatePath('secrets.php');
    if (is_file($path)) {
        require_once $path;
        $loaded = true;
    }
}

// Matikan tampilan error mentah ke user
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Buffer output — ob_start() berlapis untuk memastikan semua output tertangkap.
// Ini bekerja bersama output_buffering=4096 di .user.ini (wajib ada di server).
// Tanpa .user.ini, fatal error yang terjadi setelah HTML terkirim tidak bisa di-intercept.
if (ob_get_level() === 0) {
    ob_start();
}

// ── Error biasa (warning, notice, user error, dll) ────────────────────────
set_error_handler(function(int $errno, string $errstr, string $errfile, int $errline): bool {
    // Hanya tangkap jika error_reporting aktif untuk tipe ini
    if (!(error_reporting() & $errno)) return false;

    error_log("PHP ERROR [$errno] $errstr in $errfile:$errline");

    // Jangan matikan skrip jika hanya Notice, Warning, Deprecated
    if (!in_array($errno, [E_USER_ERROR, E_RECOVERABLE_ERROR], true)) {
        return true; // Lanjutkan eksekusi skrip normal
    }

    http_response_code(500);

    // FIX 2: bersihkan buffer dengan benar
    while (ob_get_level() > 0) ob_end_clean();
    ob_start();

    // FIX 1: path benar ke error.php di root
    include ERROR_PAGE_PATH;
    exit;
});

// ── Exception tidak tertangkap ────────────────────────────────────────────
set_exception_handler(function(Throwable $e): void {
    http_response_code(500);
    error_log("EXCEPTION: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());

    while (ob_get_level() > 0) ob_end_clean();
    ob_start();

    // FIX 1: path benar
    include ERROR_PAGE_PATH;
    exit;
});

// ── Fatal error / Parse error (register_shutdown_function) ───────────────
// FIX 3: Ini adalah cara yang benar untuk menangkap Fatal Error di PHP.
// ob_clean() saja tidak cukup karena saat fatal error, PHP sudah output
// sebagian header & body. Solusinya: header() dikirim ulang + ob_end_clean()
// + ob_start() baru, lalu include error page.
register_shutdown_function(function(): void {
    $error = error_get_last();

    if (!$error || !in_array($error['type'], [
        E_ERROR,
        E_PARSE,
        E_CORE_ERROR,
        E_COMPILE_ERROR,
        E_USER_ERROR,
    ])) {
        return;
    }

    error_log("FATAL ERROR: {$error['message']} in {$error['file']}:{$error['line']}");

    // Bersihkan SEMUA output buffer yang ada
    while (ob_get_level() > 0) ob_end_clean();

    // Kirim header HTTP 500 (kalau belum terlanjur terkirim)
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=UTF-8');
    }

    // FIX 1: path benar
    include ERROR_PAGE_PATH;
});




/**
 * Auto-versioning CSS/JS/gambar pakai filemtime()
 *
 * Cara kerja:
 * - Fungsi ini menambah ?v=<timestamp_modifikasi_file> ke URL
 * - Setiap kali file diupdate, filemtime() berubah → URL berubah → browser wajib download ulang
 * - Di .htaccess, file dengan ?v=xxx diberi Cache-Control: immutable (cache 1 tahun penuh)
 * - Jadi: cache maksimal saat tidak ada perubahan, dan otomatis bust saat ada perubahan
 *
 * @param string $path Path relatif dari root, misal '/css/style.css'
 * @return string Path dengan ?v=timestamp, misal '/css/style.css?v=1714000000'
 */
function versioned(string $path): string
{
    // Buang query string lama jika ada (misalnya ?v=lama dari hardcode)
    $cleanPath = strtok($path, '?');
    $file = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/' . ltrim($cleanPath, '/');
    if (file_exists($file)) {
        return $cleanPath . '?v=' . filemtime($file);
    }
    // Fallback: pakai path asli agar tidak broken
    return $path;
}
require_once __DIR__ . '/config.php';

/**
 * Deteksi skema (https/http) dengan dukungan Cloudflare proxy.
 * Cloudflare mengirim request ke server via HTTP internal, sehingga
 * $_SERVER["HTTPS"] selalu "off". Gunakan header CF-Connecting-IP / X-Forwarded-Proto.
 */
function is_https(): bool
{
    if (!empty($_SERVER["HTTP_X_FORWARDED_PROTO"]) && $_SERVER["HTTP_X_FORWARDED_PROTO"] === "https") return true;
    if (!empty($_SERVER["HTTP_CF_VISITOR"])) {
        $cfVisitor = json_decode($_SERVER["HTTP_CF_VISITOR"], true);
        if (isset($cfVisitor["scheme"]) && $cfVisitor["scheme"] === "https") return true;
    }
    // cPanel / LiteSpeed / Plesk hosting
    if (!empty($_SERVER["HTTP_X_FORWARDED_SSL"]) && $_SERVER["HTTP_X_FORWARDED_SSL"] === "on") return true;
    if (!empty($_SERVER["HTTP_FRONT_END_HTTPS"]) && strtolower($_SERVER["HTTP_FRONT_END_HTTPS"]) === "on") return true;
    // Native Apache
    if (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") return true;
    if (!empty($_SERVER["SERVER_PORT"]) && (int)$_SERVER["SERVER_PORT"] === 443) return true;
    return false;
}

/**
 * Ambil IP asli pengunjung, dengan dukungan Cloudflare proxy.
 */
function get_real_ip(): string
{
    return $_SERVER["HTTP_CF_CONNECTING_IP"]
        ?? $_SERVER["HTTP_X_FORWARDED_FOR"]
        ?? $_SERVER["REMOTE_ADDR"]
        ?? "0.0.0.0";
}

/**
 * Bangun base URL yang benar (https://www.parokitulungagung.org).
 */
function base_url(string $path = ""): string
{
    $scheme = is_https() ? "https" : "http";
    $host   = $_SERVER["HTTP_HOST"] ?? "www.parokitulungagung.org";
    return $scheme . "://" . $host . ($path ? "/" . ltrim($path, "/") : "");
}

/**
 * Fetch data dari tabel Supabase via REST API.
 * Menggantikan fetchSheet() yang sebelumnya mengambil dari opensheet.elk.sh.
 *
 * @param string $table   Nama tabel Supabase
 * @param array  $filters Misal: ['Periode' => '2024-2027'] → filter eq
 * @param string $order   Misal: 'tanggal.asc'
 * @param string $select  Kolom yang di-select (default '*')
 * @return array|null     Array of rows, atau null jika gagal
 */
function fetchSupabase(
    string $table,
    array  $filters = [],
    string $order   = '',
    string $select  = '*'
): ?array {
    $url    = rtrim(SUPABASE_URL, '/') . '/rest/v1/' . $table;
    $params = ['select' => $select];
    foreach ($filters as $col => $val) {
        $params[$col] = 'eq.' . $val;
    }
    if ($order) $params['order'] = $order;
    $url .= '?' . http_build_query($params);

    $headers = [
        'apikey: '        . SUPABASE_ANON_KEY,
        'Authorization: Bearer ' . SUPABASE_ANON_KEY,
        'Accept: application/json',
    ];

    $json = false;

    // Coba file_get_contents dulu
    $ctx = stream_context_create([
        'http' => [
            'header'        => implode("\r\n", $headers) . "\r\n",
            'timeout'       => 15,
            'ignore_errors' => true,
        ],
        'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
    ]);
    $json = @file_get_contents($url, false, $ctx);

    // Fallback cURL
    if ($json === false && function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $json = curl_exec($ch);
        curl_close($ch);
    }

    if (!$json) return null;
    $decoded = json_decode($json, true);
    return is_array($decoded) ? $decoded : null;
}

/**
 * fetchSupabaseCached() — wrapper fetchSupabase() dengan file cache
 * TTL default 300 detik (5 menit). Cache disimpan di /cache/supabase/
 *
 * Strategi anti-spam:
 * - Setiap tabel/query punya tepat 1 file cache (.json) + 1 lock (.stale).
 *   File lama langsung di-replace (atomic via rename), tidak pernah bertambah.
 * - GC probabilistik (1% request): hapus .stale orphan > 5 menit dan
 *   .json yang sudah expired > 3× TTL (tidak aktif / tidak pernah di-hit lagi).
 */
function fetchSupabaseCached(
    string $table,
    array  $filters = [],
    string $order   = '',
    string $select  = '*',
    int    $ttl     = 300
): ?array {
    $cacheDir  = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/cache/supabase';
    $cacheKey  = $table . '_' . md5($table . json_encode($filters) . $order . $select);
    $cacheFile = $cacheDir . '/' . $cacheKey . '.json';
    $staleFile = $cacheFile . '.stale';

    if (!is_dir($cacheDir)) @mkdir($cacheDir, 0755, true);

    // ── Garbage collection probabilistik (1% request) ────────────────
    // Berjalan di background (shutdown), tidak memperlambat response.
    if (mt_rand(1, 100) === 1) {
        register_shutdown_function(function() use ($cacheDir, $ttl) {
            $now = time();
            foreach (glob($cacheDir . '/*.stale') ?: [] as $f) {
                // Hapus .stale orphan yang stuck > 5 menit
                if ($now - @filemtime($f) > 300) @unlink($f);
            }
            foreach (glob($cacheDir . '/*.json') ?: [] as $f) {
                // Hapus .json yang expired > 3× TTL (sudah tidak aktif)
                if ($now - @filemtime($f) > $ttl * 3) @unlink($f);
            }
        });
    }

    $cacheExists = file_exists($cacheFile);
    $cacheAge    = $cacheExists ? (time() - filemtime($cacheFile)) : PHP_INT_MAX;
    $cached      = $cacheExists ? @json_decode(file_get_contents($cacheFile), true) : null;

    // ── Cache segar — langsung pakai ─────────────────────────────────
    if ($cacheExists && $cacheAge < $ttl && is_array($cached)) {
        return $cached;
    }

    // ── Stale-while-revalidate ────────────────────────────────────────
    // Cache expired tapi ada: kembalikan data lama, refresh di background.
    // File lama di-replace atomik (tmp → rename), bukan append/duplikat baru.
    if ($cacheExists && is_array($cached)) {
        $revalidating = file_exists($staleFile) && (time() - filemtime($staleFile)) < 30;
        if (!$revalidating) {
            @file_put_contents($staleFile, time(), LOCK_EX);
            register_shutdown_function(function()
                use ($table, $filters, $order, $select, $cacheFile, $staleFile) {
                    $fresh = fetchSupabase($table, $filters, $order, $select);
                    if (is_array($fresh) && !empty($fresh)) {
                        // Tulis ke tmp dulu, lalu rename — atomic, tidak corrupt
                        $tmp = $cacheFile . '.tmp.' . getmypid();
                        if (@file_put_contents($tmp, json_encode($fresh), LOCK_EX) !== false) {
                            @rename($tmp, $cacheFile);
                        } else {
                            @unlink($tmp);
                        }
                    }
                    @unlink($staleFile);
                }
            );
        }
        return $cached; // pengunjung dapat data lama, tidak menunggu
    }

    // ── Cache miss — fetch sinkron (hanya request pertama) ───────────
    $data = fetchSupabase($table, $filters, $order, $select);
    if (is_array($data) && !empty($data)) {
        $tmp = $cacheFile . '.tmp.' . getmypid();
        if (@file_put_contents($tmp, json_encode($data), LOCK_EX) !== false) {
            @rename($tmp, $cacheFile);
        } else {
            @unlink($tmp);
        }
    }
    return $data;
}

/**
 * TTL per tabel — data jarang berubah dapat cache lebih lama
 */
function _cacheTTL(string $table): int {
    $static  = ['galeri_foto','daftar_wilayah','daftar_asisten_imam',
                'kepengurusan_dpp_bgkp','petugas','kelompok_profil',
                'master_lingkungan','master_bidang','master_koordinator'];
    $dynamic = ['info_paroki','umkm_umat'];
    if (in_array($table, $static))  return 600;
    if (in_array($table, $dynamic)) return 180;
    return 300;
}



function fetchSheet(string $url): ?array
{
    // Ekstrak nama tabel dari URL opensheet lama jika masih ada
    if (preg_match('#/([^/]+)$#', $url, $m)) {
        return fetchSupabase($m[1]);
    }
    return null;
}

/**
 * Format teks dengan sintaks markdown sederhana
 * **bold**, >center, - list item
 */
function formatStyledText(?string $text): string
{
    if (!$text || trim($text) === '-' || trim($text) === '') return '-';

    $lines    = explode("\n", str_replace("\r\n", "\n", $text));
    $html     = '';
    $listOpen = false;

    foreach ($lines as $rawLine) {
        $line = trim($rawLine);
        if ($line === '') {
            if ($listOpen) { $html .= '</ul>'; $listOpen = false; }
            $html .= '<br>';
            continue;
        }
        $line = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $line);
        if (strpos($line, '>') === 0) {
            if ($listOpen) { $html .= '</ul>'; $listOpen = false; }
            $html .= '<div style="text-align:center">' . trim(substr($line, 1)) . '</div>';
        } elseif (strpos($line, '- ') === 0) {
            if (!$listOpen) { $html .= '<ul>'; $listOpen = true; }
            $html .= '<li>' . substr($line, 2) . '</li>';
        } else {
            if ($listOpen) { $html .= '</ul>'; $listOpen = false; }
            $html .= '<p>' . $line . '</p>';
        }
    }
    if ($listOpen) $html .= '</ul>';
    return $html;
}

/**
 * Format tanggal YYYY-MM-DD ke format Indonesia
 */
function formatTanggalIndo(string $dateStr): string
{
    $bulan = [
        1  => 'Januari', 2  => 'Februari', 3  => 'Maret',    4  => 'April',
        5  => 'Mei',     6  => 'Juni',      7  => 'Juli',     8  => 'Agustus',
        9  => 'September',10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];
    $parts = explode('-', $dateStr);
    if (count($parts) !== 3) return $dateStr;
    [$y, $m, $d] = $parts;
    return (int)$d . ' ' . ($bulan[(int)$m] ?? '') . ' ' . $y;
}

/**
 * Encode key R2 (bisa mengandung '/', spasi, tanda kurung) supaya aman
 * dipakai sebagai query string, tapi tetap terbaca & konsisten sehingga
 * bisa dicocokkan sebagai prefix (LIKE) di tabel image_seo.
 */
function encodeR2Key(string $key): string
{
    return implode('/', array_map('rawurlencode', explode('/', $key)));
}

/**
 * URL publik langsung ke foto di R2 lewat Cloudflare CDN custom domain
 * (mis. https://img.parokitulungagung.org/<key>) — TANPA watermark,
 * TANPA lewat server hosting sama sekali. Dipakai khusus untuk foto isi
 * album galeri (pages/galeri-album.php). Cover album masih pakai jalur
 * lama (galeri-cover.php, dengan watermark).
 */
function r2CdnUrl(string $key): string
{
    return rtrim(R2_CDN_URL, '/') . '/' . encodeR2Key($key);
}

/**
 * URL thumbnail. Kalau R2_CDN_IMAGE_RESIZING aktif (fitur berbayar
 * Cloudflare, harus dinyalakan dulu di dashboard), pakai resize di edge
 * lewat /cdn-cgi/image/. Kalau tidak, kembalikan foto ukuran ASLI apa
 * adanya (tidak ada resize server-side lagi setelah lepas dari proxy).
 */
function r2CdnThumbUrl(string $key, int $width = 480): string
{
    $full = r2CdnUrl($key);
    if (defined('R2_CDN_IMAGE_RESIZING') && R2_CDN_IMAGE_RESIZING) {
        $host = parse_url($full, PHP_URL_HOST);
        $path = parse_url($full, PHP_URL_PATH);
        return 'https://' . $host . '/cdn-cgi/image/width=' . $width . ',quality=70,format=webp' . $path;
    }
    return $full;
}

/**
 * Deteksi kasar apakah request datang dari perangkat mobile, berdasarkan
 * User-Agent. Dipakai untuk hal non-kritis seperti ukuran halaman
 * pagination (mis. galeri foto) — BUKAN untuk keputusan keamanan/akses,
 * karena UA bisa dipalsukan.
 */
function isMobileUserAgent(): bool
{
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    return (bool) preg_match('/android|iphone|ipad|ipod|windows phone|mobile|blackberry|opera mini|iemobile/i', $ua);
}

/**
 * Ubah teks judul menjadi slug URL (huruf kecil, spasi/simbol → strip)
 */
function slugify(string $text): string
{
    $text = mb_strtolower(trim($text), 'UTF-8');
    $text = preg_replace('/[^a-z0-9]+/u', '-', $text) ?? '';
    return trim($text, '-') ?: 'album';
}

/**
 * Escape HTML untuk output aman
 */
if (!function_exists('e')) {
    function e(?string $str): string
    {
        return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
    }
}

/**
 * File-based cache helper — persisten di /cache/runtime/ (bukan /tmp)
 * Dipakai oleh artikel-detail, homepage, dan halaman lain yang butuh cache ringan.
 *
 * Strategi anti-spam:
 * - Setiap key punya tepat 1 file. cache_set() memakai tmp→rename (atomic),
 *   jadi file lama langsung terganti, tidak pernah duplikat.
 * - GC probabilistik (2% saat cache_get): scan folder, hapus semua file
 *   yang sudah expired. Berjalan di background, tidak memperlambat response.
 */
function cache_get(string $key) {
    $dir  = rtrim($_SERVER['DOCUMENT_ROOT'] ?? __DIR__, '/') . '/cache/runtime';
    $file = $dir . '/p_' . md5($key) . '.cache';
    if (!file_exists($file)) return null;
    $data = @unserialize(file_get_contents($file));
    if (!$data || $data['exp'] < time()) { @unlink($file); return null; }

    // GC probabilistik: 2% dari request, berjalan setelah response dikirim
    if (mt_rand(1, 50) === 1) {
        register_shutdown_function(function() use ($dir) {
            $now = time();
            foreach (glob($dir . '/p_*.cache') ?: [] as $f) {
                $d = @unserialize(@file_get_contents($f));
                if (!$d || $d['exp'] < $now) @unlink($f);
            }
        });
    }

    return $data['val'];
}

function cache_set(string $key, $value, int $ttl = 600): void {
    $dir  = rtrim($_SERVER['DOCUMENT_ROOT'] ?? __DIR__, '/') . '/cache/runtime';
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    $file = $dir . '/p_' . md5($key) . '.cache';
    // Tulis ke tmp dulu, lalu rename — atomic (tidak corrupt jika ada race condition)
    $tmp = $file . '.tmp.' . getmypid();
    if (@file_put_contents($tmp, serialize(['exp' => time() + $ttl, 'val' => $value]), LOCK_EX) !== false) {
        @rename($tmp, $file);
    } else {
        @unlink($tmp);
    }
}

/**
 * Hapus satu key dari cache runtime file-based
 */
function cache_del(string $key): void
{
    $dir  = rtrim($_SERVER['DOCUMENT_ROOT'] ?? __DIR__, '/') . '/cache/runtime';
    $file = $dir . '/p_' . md5($key) . '.cache';
    if (file_exists($file)) @unlink($file);
}

/**
 * Invalidate cache Supabase untuk satu tabel.
 * Hapus semua file cache/supabase/ yang key-nya diawali nama tabel tersebut.
 * Dipanggil otomatis setelah admin create/update/delete data.
 */
function invalidateSupabaseCache(string $table): int
{
    $dir = rtrim($_SERVER['DOCUMENT_ROOT'] ?? dirname(__DIR__), '/') . '/cache/supabase';
    if (!is_dir($dir)) return 0;
    $n = 0;
    foreach (glob($dir . '/' . $table . '_*.json') ?: [] as $f) {
        if (@unlink($f)) $n++;
    }
    // Hapus juga .stale files
    foreach (glob($dir . '/' . $table . '_*.stale') ?: [] as $f) {
        @unlink($f);
    }
    return $n;
}

/**
 * Purge Cloudflare Edge / CDN Cache via API
 * Mendukung purge spesifik URL atau purge everything.
 */
function purgeCloudflareCache(array $urls = [], bool $purgeEverything = false): bool
{
    $zoneId = defined('CF_ZONE_ID') ? CF_ZONE_ID : (defined('SECRET_CF_ZONE_ID') ? SECRET_CF_ZONE_ID : '');
    $token  = defined('CF_API_TOKEN') ? CF_API_TOKEN : (defined('SECRET_CF_API_TOKEN') ? SECRET_CF_API_TOKEN : '');

    if (empty($zoneId) || empty($token)) {
        return false; // Cloudflare API credentials belum diset
    }

    $payload = [];
    if ($purgeEverything) {
        $payload = ['purge_everything' => true];
    } elseif (!empty($urls)) {
        $payload = ['files' => array_values(array_unique($urls))];
    } else {
        return false;
    }

    $ch = curl_init("https://api.cloudflare.com/client/v4/zones/{$zoneId}/purge_cache");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => [
            "Authorization: Bearer {$token}",
            "Content-Type: application/json",
        ],
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_TIMEOUT        => 8,
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ($code >= 200 && $code < 300);
}

/**
 * Kirim HTTP Cache-Control header yang ramah Cloudflare CDN & Browser.
 * Jika admin sedang login, header dipaksa private/no-cache agar admin
 * selalu melihat perubahan data secara live tanpa tertunda cache.
 */
function sendPublicCacheHeaders(int $sMaxAge = 300, int $maxAge = 60, int $swr = 86400): void
{
    if (headers_sent()) return;

    $sessName = defined('SESSION_NAME') ? SESSION_NAME : 'paroki_admin_sess';
    $isAdmin  = !empty($_COOKIE[$sessName]);

    if ($isAdmin) {
        // Admin terautentikasi: bypass cache sepenuhnya
        header('Cache-Control: private, no-cache, no-store, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('Vary: Accept-Encoding, Cookie');
        return;
    }

    // Pengunjung umum: Edge CDN Cloudflare cache s-maxage detik, browser max-age detik, SWR
    header("Cache-Control: public, max-age={$maxAge}, s-maxage={$sMaxAge}, stale-while-revalidate={$swr}");
    header('Vary: Accept-Encoding, Cookie');
}

/**
 * Invalidate semua cache terkait sebuah "page" admin.
 * Menghapus:
 * 1. Cache Supabase (query JSON)
 * 2. Cache runtime (getAll artikel, sitemap, dll)
 * 3. Cloudflare CDN & Edge cache (jika API token terpasang)
 *
 * $page = nama halaman admin (agenda, galeri, petugas, berita, kronik, dll)
 * $table = nama tabel Supabase (jika berbeda dari $page)
 */
function invalidateAllRelatedCaches(string $page, string $table = ''): void
{
    // Mapping page admin -> nama tabel Supabase sebenarnya
    $pageToTables = [
        'agenda'                => ['info_paroki', 'agenda', 'dokumen_paroki'],
        'petugas'               => ['petugas', 'jadwal_petugas', 'jadwal_petugas_gambar'],
        'jadwal_petugas_gambar' => ['jadwal_petugas_gambar', 'petugas'],
        'galeri'                => ['galeri_foto', 'galeri'],
        'wilayah'               => ['daftar_wilayah', 'wilayah'],
        'asisten_imam'          => ['daftar_asisten_imam', 'asisten_imam'],
        'dpp_bgkp'              => ['kepengurusan_dpp_bgkp', 'dpp_bgkp'],
        'romo_paroki'           => ['romo_paroki'],
        'dokumen_paroki'        => ['dokumen_paroki'],
        'umkm'                  => ['umkm_umat', 'umkm'],
        'kategorial'            => ['kelompok_profil', 'kategorial'],
        'master_lingkungan'     => ['master_lingkungan', 'daftar_wilayah'],
        'master_bidang'         => ['master_bidang', 'kepengurusan_dpp_bgkp'],
        'master_koordinator'    => ['master_koordinator', 'daftar_wilayah'],
        'berita'                => ['articles'],
        'kronik'                => ['articles'],
        'historia'              => ['articles'],
        'articles'              => ['articles'],
    ];

    $tablesToInvalidate = [];
    if (!empty($table)) $tablesToInvalidate[] = $table;
    if (isset($pageToTables[$page])) {
        $tablesToInvalidate = array_merge($tablesToInvalidate, $pageToTables[$page]);
    } else {
        $tablesToInvalidate[] = $page;
    }

    // 1. Invalidate cache Supabase query untuk semua tabel terkait
    foreach (array_unique($tablesToInvalidate) as $t) {
        invalidateSupabaseCache($t);
    }

    // 2. Cache runtime (artikel getAll, kontributor, dll)
    $runtimeKeys = [];

    if (in_array($page, ['berita', 'kronik', 'historia', 'articles'])) {
        foreach (['berita', 'kronik', 'historia'] as $m) {
            $runtimeKeys[] = 'getAll_' . $m . '_1';
            $runtimeKeys[] = 'getAll_' . $m . '_0';
        }
    }

    $runtimeKeys[] = 'all_contributors_v1';

    foreach ($runtimeKeys as $k) {
        cache_del($k);
    }

    // 3. Cloudflare Edge Purge (URL publik terkait)
    $siteUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : 'https://parokitulungagung.org';
    $cdnUrl  = defined('R2_CDN_URL') ? rtrim(R2_CDN_URL, '/') : 'https://img.parokitulungagung.org';

    $cfUrls = [
        $siteUrl . '/',
        $siteUrl . '/index.php',
    ];

    switch ($page) {
        case 'agenda':
        case 'petugas':
        case 'jadwal_petugas_gambar':
        case 'dokumen_paroki':
            $cfUrls[] = $siteUrl . '/pages/agenda.php';
            $cfUrls[] = $siteUrl . '/agenda';
            break;
        case 'umkm':
            $cfUrls[] = $siteUrl . '/pages/umkmumat.php';
            $cfUrls[] = $siteUrl . '/umkm';
            break;
        case 'galeri':
            $cfUrls[] = $siteUrl . '/pages/galeri.php';
            $cfUrls[] = $siteUrl . '/galeri';
            break;
        case 'berita':
        case 'kronik':
        case 'historia':
        case 'articles':
            $cfUrls[] = $siteUrl . '/pages/artikel.php';
            $cfUrls[] = $siteUrl . '/artikel';
            $cfUrls[] = $siteUrl . '/berita';
            break;
    }

    purgeCloudflareCache($cfUrls);
}

/**
 * Buat path foto dari nama orang
 * SEBELUMNYA: mengembalikan "/img/person/<nama>.webp" (lokal hosting)
 * SEKARANG  : kembalikan URL absolut R2 CDN, mis.
 *             "https://img.parokitulungagung.org/person/<nama>.webp"
 *             Kalau R2_CDN_URL belum terdefinisi, fallback ke "/person/<nama>.webp"
 *             (relatif terhadap root situs, agar tidak error fatal).
 */
function fotoFromNama(string $nama): string
{
    $nama = str_replace(['.', ' '], ['', '-'], trim($nama));
    $base = defined('R2_CDN_URL') ? rtrim(R2_CDN_URL, '/') : '';
    return $base . '/person/' . $nama . '.webp';
}

/**
 * Bangun URL publik foto person dari nama file (nama di kolom "Foto" DB).
 * - $namaFile: nama file relatif (mis. "Andreas-Andrie-Djatmiko.webp")
 * - Hasil    : URL absolut R2 CDN.
 * Kalau R2_CDN_URL tidak tersedia, fallback ke path relatif "/person/<file>".
 */
function personPhotoUrl(string $namaFile): string
{
    $namaFile = trim($namaFile);
    if ($namaFile === '') return '';
    // Kalau ternyata sudah URL absolut (mis. ada data lama), biarkan apa adanya.
    if (preg_match('~^https?://~i', $namaFile)) return $namaFile;
    // Bersihkan path lokal lama (/img/person/ atau person/)
    $cleaned = preg_replace('~^/?(img/)?(person/)?~i', '', $namaFile);
    $base = defined('R2_CDN_URL') ? rtrim(R2_CDN_URL, '/') : '';
    $pref = defined('R2_PERSON_PREFIX') ? trim(R2_PERSON_PREFIX, '/') : 'person';
    return $base . '/' . $pref . '/' . ltrim($cleaned, '/');
}

/**
 * URL publik aset root /img/ dari Cloudflare R2 / CDN (prefix assets/).
 * Khusus untuk file media yang tadinya langsung di root /img/<file>
 * (mis. /img/avatar.webp, /img/header-logo-1.webp, /img/parokitulungagung.webp).
 * TIDAK mengubah subfolder seperti /person/, /artikel/, dll.
 */
function assetUrl(string $namaFile): string
{
    $namaFile = trim($namaFile);
    if ($namaFile === '') return '';
    $base = defined('R2_CDN_URL') ? rtrim(R2_CDN_URL, '/') : 'https://img.parokitulungagung.org';
    if (str_starts_with($namaFile, $base)) return $namaFile;
    if (preg_match('~^https?://~i', $namaFile)) return $namaFile;
    $cleaned = preg_replace('~^/?(img/|assets/)?~i', '', $namaFile);
    $pref = defined('R2_ASSETS_PREFIX') ? trim(R2_ASSETS_PREFIX, '/') : 'assets';
    return $base . '/' . $pref . '/' . ltrim($cleaned, '/');
}

/**
 * URL publik icon menu/shortcut dari R2 bucket.
 * SEBELUMNYA: "/img/icon/<file>.png" (lokal)
 * SEKARANG  : "https://img.parokitulungagung.org/icon/<file>.png"
 */
function iconUrl(string $namaFile): string
{
    $namaFile = trim($namaFile);
    if ($namaFile === '') return '';
    if (preg_match('~^https?://~i', $namaFile)) return $namaFile;
    // Bersihkan path lokal lama (/img/icon/ atau icon/)
    $cleaned = preg_replace('~^/?(img/)?(icon/)?~i', '', $namaFile);
    $base = defined('R2_CDN_URL') ? rtrim(R2_CDN_URL, '/') : '';
    $pref = defined('R2_ICON_PREFIX') ? trim(R2_ICON_PREFIX, '/') : 'icon';
    return $base . '/' . $pref . '/' . ltrim($cleaned, '/');
}

/**
 * URL publik icon kategorial dari R2 bucket.
 * SEBELUMNYA: "/img/icon/kategorial/<file>.png" (lokal)
 * SEKARANG  : "https://img.parokitulungagung.org/icon/kategorial/<file>.png"
 */
function iconKategorialUrl(string $namaFile): string
{
    $namaFile = trim($namaFile);
    if ($namaFile === '') return '';
    if (preg_match('~^https?://~i', $namaFile)) return $namaFile;
    // Bersihkan path lokal lama (/img/icon/kategorial/ atau icon/kategorial/ atau icon/)
    $cleaned = preg_replace('~^/?(img/)?(icon/(kategorial/)?)?~i', '', $namaFile);
    $base = defined('R2_CDN_URL') ? rtrim(R2_CDN_URL, '/') : '';
    $pref = defined('R2_ICON_KAT_PREFIX') ? trim(R2_ICON_KAT_PREFIX, '/') : 'icon/kategorial';
    return $base . '/' . $pref . '/' . ltrim($cleaned, '/');
}

/**
 * URL publik foto profil admin / penulis dari Cloudflare R2 bucket.
 * SEBELUMNYA: "/img/admin/profil/profil-<uid>.webp" (lokal)
 * SEKARANG  : "https://img.parokitulungagung.org/assets/admin/profil-<uid>.webp"
 */
function adminFotoUrl(string $uid): string
{
    $uid = trim($uid);
    if ($uid === '') return '';
    $base = defined('R2_CDN_URL') ? rtrim(R2_CDN_URL, '/') : 'https://img.parokitulungagung.org';
    return $base . '/assets/admin/profil-' . rawurlencode($uid) . '.webp';
}

/**
 * URL publik gambar artikel dari R2 bucket.
 * SEBELUMNYA: "/img/artikel/<file>" (lokal)
 * SEKARANG  : "https://img.parokitulungagung.org/artikel/<file>"
 */
function artikelImageUrl(string $namaFile): string
{
    $namaFile = trim($namaFile);
    if ($namaFile === '') return '';
    $base = defined('R2_CDN_URL') ? rtrim(R2_CDN_URL, '/') : 'https://img.parokitulungagung.org';
    if (str_starts_with($namaFile, $base)) return $namaFile;
    $namaFile = preg_replace('~^https?://[^/]+~i', '', $namaFile);
    $cleaned = preg_replace('~^/?(img/)?artikel/~i', '', $namaFile);
    if (preg_match('~^https?://~i', $cleaned)) return $cleaned;
    $pref = defined('R2_ARTIKEL_PREFIX') ? trim(R2_ARTIKEL_PREFIX, '/') : 'artikel';
    return $base . '/' . $pref . '/' . ltrim($cleaned, '/');
}

/**
 * URL publik gambar OG Preview dari R2 bucket.
 * SEBELUMNYA: "/img/ogpreview/<file>.jpg" (lokal)
 * SEKARANG  : "https://img.parokitulungagung.org/ogpreview/<file>.jpg"
 */
function ogPreviewUrl(string $namaFile): string
{
    $namaFile = trim($namaFile);
    if ($namaFile === '') return '';
    $base = defined('R2_CDN_URL') ? rtrim(R2_CDN_URL, '/') : 'https://img.parokitulungagung.org';
    if (str_starts_with($namaFile, $base)) return $namaFile;
    $namaFile = preg_replace('~^https?://[^/]+~i', '', $namaFile);
    $cleaned = preg_replace('~^/?(img/)?ogpreview/~i', '', $namaFile);
    if (preg_match('~^https?://~i', $cleaned)) return $cleaned;
    $pref = defined('R2_OG_PREFIX') ? trim(R2_OG_PREFIX, '/') : 'ogpreview';
    return $base . '/' . $pref . '/' . ltrim($cleaned, '/');
}

/**
 * URL publik dokumen unduhan dari Cloudflare R2 bucket.
 * SEBELUMNYA: "/public/downloads/<file>" (lokal)
 * SEKARANG  : "https://img.parokitulungagung.org/downloads/<file>"
 */
function downloadUrl(string $namaFile): string
{
    $namaFile = trim($namaFile);
    if ($namaFile === '') return '';
    $base = defined('R2_CDN_URL') ? rtrim(R2_CDN_URL, '/') : 'https://img.parokitulungagung.org';
    if (str_starts_with($namaFile, $base)) return $namaFile;
    if (preg_match('~^https?://~i', $namaFile)) return $namaFile;
    $cleaned = preg_replace('~^/?(public/)?downloads/~i', '', $namaFile);
    $pref = defined('R2_DOWNLOADS_PREFIX') ? trim(R2_DOWNLOADS_PREFIX, '/') : 'downloads';
    return $base . '/' . $pref . '/' . rawurlencode(ltrim($cleaned, '/'));
}

/**
 * URL publik jadwal petugas litrugi (gambar) dari Cloudflare R2 bucket.
 * SEBELUMNYA: "/public/jadwal_petugas/<file>" (lokal)
 * SEKARANG  : "https://img.parokitulungagung.org/jadwal_petugas/<file>"
 */
function jadwalPetugasUrl(string $namaFile): string
{
    $namaFile = trim($namaFile);
    if ($namaFile === '') return '';
    $base = defined('R2_CDN_URL') ? rtrim(R2_CDN_URL, '/') : 'https://img.parokitulungagung.org';
    if (str_starts_with($namaFile, $base)) return $namaFile;
    if (preg_match('~^https?://~i', $namaFile)) return $namaFile;
    $cleaned = preg_replace('~^/?(public/)?jadwal_petugas/~i', '', $namaFile);
    $pref = defined('R2_JADWAL_PETUGAS_PREFIX') ? trim(R2_JADWAL_PETUGAS_PREFIX, '/') : 'jadwal_petugas';
    return $base . '/' . $pref . '/' . rawurlencode(ltrim($cleaned, '/'));
}

/**
 * URL publik media promosi UMKM dari Cloudflare R2 bucket.
 * SEBELUMNYA: "/public/umkm/<file>" (lokal)
 * SEKARANG  : "https://img.parokitulungagung.org/umkm/<file>"
 */
function umkmUrl(string $namaFile): string
{
    $namaFile = trim($namaFile);
    if ($namaFile === '') return '';
    $base = defined('R2_CDN_URL') ? rtrim(R2_CDN_URL, '/') : 'https://img.parokitulungagung.org';
    if (str_starts_with($namaFile, $base)) return $namaFile;
    if (preg_match('~^https?://~i', $namaFile)) return $namaFile;
    $cleaned = preg_replace('~^/?(public/)?umkm/~i', '', $namaFile);
    $pref = defined('R2_UMKM_PREFIX') ? trim(R2_UMKM_PREFIX, '/') : 'umkm';
    return $base . '/' . $pref . '/' . rawurlencode(ltrim($cleaned, '/'));
}

/**
 * Helper instance R2WriteClient untuk operasi upload/hapus di Cloudflare R2
 */
function getR2WriteClient(): R2WriteClient
{
    require_once __DIR__ . '/R2WriteClient.php';
    $ak = defined('SECRET_R2_ACCESS_KEY_WRITE') ? SECRET_R2_ACCESS_KEY_WRITE : (defined('R2_ACCESS_KEY') ? R2_ACCESS_KEY : null);
    $sk = defined('SECRET_R2_SECRET_KEY_WRITE') ? SECRET_R2_SECRET_KEY_WRITE : (defined('R2_SECRET_KEY') ? R2_SECRET_KEY : null);
    $ep = defined('SECRET_R2_ENDPOINT') ? SECRET_R2_ENDPOINT : (defined('R2_ENDPOINT') ? R2_ENDPOINT : null);
    $bk = defined('SECRET_R2_BUCKET') ? SECRET_R2_BUCKET : (defined('R2_BUCKET') ? R2_BUCKET : null);

    if (!$ak || !$sk || !$ep || !$bk) {
        throw new RuntimeException('Konfigurasi Cloudflare R2 belum lengkap di secrets.php.');
    }
    return new R2WriteClient($ak, $sk, $ep, $bk);
}

/**
 * Sampul album galeri publik (R2 CDN)
 */
if (!function_exists('galeriCoverUrls')) {
    function galeriCoverUrls(string $gambar, string $base = ''): array {
        if ($gambar === '') return ['raw' => '', 'display' => ''];
        if (str_starts_with($gambar, 'http')) return ['raw' => $gambar, 'display' => $gambar];
        $raw = '/public/galeri/' . $gambar;
        $filename = basename(str_replace('\\', '/', $gambar));
        $display  = r2CdnUrl(defined('R2_GALERI_THUMB_PREFIX') ? R2_GALERI_THUMB_PREFIX . $filename : '_thumbnails/galeri/' . $filename);
        return ['raw' => $raw, 'display' => $display];
    }
}

/**
 * Hitung mergeMap untuk rowspan tabel petugas
 */
function calcMergeMap(array $data, array $cols): array
{
    $mergeMap = [];
    $n        = count($data);
    foreach ($cols as $ci => $col) {
        $prevVal  = null;
        $startIdx = null;
        for ($i = 0; $i < $n; $i++) {
            $val = trim($data[$i][$col] ?? '');
            if ($val === '' && $prevVal !== null && $startIdx !== null) {
                if (!isset($mergeMap[$startIdx])) $mergeMap[$startIdx] = [];
                $mergeMap[$startIdx][$ci] = ($mergeMap[$startIdx][$ci] ?? 1) + 1;
            } else {
                $prevVal  = $val === '' ? null : $val;
                $startIdx = $val === '' ? null : $i;
            }
        }
    }
    return $mergeMap;
}

/**
 * Cek apakah sel sudah dicakup rowspan dari baris sebelumnya
 */
function isMergedCell(int $row, int $col, array $mergeMap): bool
{
    for ($si = 0; $si < $row; $si++) {
        if (isset($mergeMap[$si][$col]) && $row < $si + $mergeMap[$si][$col]) {
            return true;
        }
    }
    return false;
}