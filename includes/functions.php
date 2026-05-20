<?php





// Path ke error.php yang benar (di root public_html)
define('ERROR_PAGE_PATH', dirname(__DIR__) . '/error.php');

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

    http_response_code(500);
    error_log("PHP ERROR [$errno] $errstr in $errfile:$errline");

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

    $cacheExists = file_exists($cacheFile);
    $cacheAge    = $cacheExists ? (time() - filemtime($cacheFile)) : PHP_INT_MAX;
    $cached      = $cacheExists ? @json_decode(file_get_contents($cacheFile), true) : null;

    // ── Cache segar — langsung pakai ─────────────────────────────────
    if ($cacheExists && $cacheAge < $ttl && is_array($cached)) {
        return $cached;
    }

    // ── Stale-while-revalidate ────────────────────────────────────────
    // Cache expired tapi ada: kembalikan data lama, refresh di background
    if ($cacheExists && is_array($cached)) {
        $revalidating = file_exists($staleFile) && (time() - filemtime($staleFile)) < 30;
        if (!$revalidating) {
            @file_put_contents($staleFile, time(), LOCK_EX);
            register_shutdown_function(function()
                use ($table, $filters, $order, $select, $cacheFile, $staleFile) {
                    $fresh = fetchSupabase($table, $filters, $order, $select);
                    if (is_array($fresh) && !empty($fresh)) {
                        @file_put_contents($cacheFile, json_encode($fresh), LOCK_EX);
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
        @file_put_contents($cacheFile, json_encode($data), LOCK_EX);
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
 * Escape HTML untuk output aman
 */
function e(?string $str): string
{
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * File-based cache helper — persisten di /cache/runtime/ (bukan /tmp)
 * Dipakai oleh artikel-detail, homepage, dan halaman lain yang butuh cache ringan.
 */
function cache_get(string $key) {
    $dir  = rtrim($_SERVER['DOCUMENT_ROOT'] ?? __DIR__, '/') . '/cache/runtime';
    $file = $dir . '/p_' . md5($key) . '.cache';
    if (!file_exists($file)) return null;
    $data = @unserialize(file_get_contents($file));
    if (!$data || $data['exp'] < time()) { @unlink($file); return null; }
    return $data['val'];
}

function cache_set(string $key, $value, int $ttl = 600): void {
    $dir  = rtrim($_SERVER['DOCUMENT_ROOT'] ?? __DIR__, '/') . '/cache/runtime';
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    $file = $dir . '/p_' . md5($key) . '.cache';
    @file_put_contents($file, serialize(['exp' => time() + $ttl, 'val' => $value]), LOCK_EX);
}

/**
 * Buat path foto dari nama orang
 */
function fotoFromNama(string $nama): string
{
    $nama = str_replace(['.', ' '], ['', '-'], trim($nama));
    return '/img/person/' . $nama . '.webp';
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