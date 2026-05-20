<?php
/**
 * analytics-proxy.php
 * ─────────────────────────────────────────────────────────────────────
 * Proxy gratis untuk Google Analytics Data API v1 (GA4)
 * 
 * CARA PAKAI:
 * 1. Taruh file ini + ga-credentials.json di server kamu
 * 2. Ganti ALLOWED_ORIGINS dengan domain kamu
 * ─────────────────────────────────────────────────────────────────────
 */

// ── Security: hanya izinkan domain kamu ───────────────────────────────
define('ALLOWED_ORIGINS', [
    'https://www.parokitulungagung.org',
    'https://parokitulungagung.org',
    'http://localhost',        // untuk testing lokal
    'http://127.0.0.1',
]);

// ── Path ke Service Account key JSON ─────────────────────────────────
define('CREDENTIALS_FILE', __DIR__ . 'SECRET_GA4_CREDENTIALS_PATH');

// ── Cache duration (detik) ────────────────────────────────────────────
define('CACHE_TTL_REPORT',   1800);  // 30 menit untuk laporan
define('CACHE_TTL_REALTIME',   30);  // 30 detik untuk realtime

// ═════════════════════════════════════════════════════════════════════
// CORS + Headers
// ═════════════════════════════════════════════════════════════════════
header('Content-Type: application/json; charset=utf-8');

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, ALLOWED_ORIGINS)) {
    header("Access-Control-Allow-Origin: $origin");
} else {
    // Jika diakses dari file HTML lokal (file://) — izinkan untuk dev
    if (empty($origin)) header("Access-Control-Allow-Origin: *");
}
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

// ═════════════════════════════════════════════════════════════════════
// INPUT VALIDATION
// ═════════════════════════════════════════════════════════════════════
$propertyId = preg_replace('/[^0-9]/', '', $_GET['property'] ?? '');
$isRealtime = !empty($_GET['realtime']);
$days       = max(7, min(365, (int)($_GET['days'] ?? 28)));

if (!$propertyId) {
    http_response_code(400);
    echo json_encode(['error' => 'Parameter property (GA4 Property ID) wajib diisi.']);
    exit;
}

if (!file_exists(CREDENTIALS_FILE)) {
    http_response_code(500);
    echo json_encode(['error' => 'File ga-credentials.json tidak ditemukan di server.']);
    exit;
}

// ═════════════════════════════════════════════════════════════════════
// CACHE
// ═════════════════════════════════════════════════════════════════════
$cacheDir  = __DIR__ . '/cache/analytics/';
if (!is_dir($cacheDir)) mkdir($cacheDir, 0755, true);

$cacheKey  = md5($propertyId . ($isRealtime ? '_rt' : '_' . $days));
$cacheFile = $cacheDir . $cacheKey . '.json';
$cacheTtl  = $isRealtime ? CACHE_TTL_REALTIME : CACHE_TTL_REPORT;

if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTtl) {
    echo file_get_contents($cacheFile);
    exit;
}

// ═════════════════════════════════════════════════════════════════════
// JWT + ACCESS TOKEN (tanpa library eksternal)
// ═════════════════════════════════════════════════════════════════════
function getAccessToken(): string {
    $creds = json_decode(file_get_contents(CREDENTIALS_FILE), true);
    if (!$creds || $creds['type'] !== 'service_account') {
        throw new Exception('ga-credentials.json tidak valid atau bukan Service Account.');
    }

    $now    = time();
    $header  = base64url_encode(json_encode(['alg'=>'RS256','typ'=>'JWT']));
    $payload = base64url_encode(json_encode([
        'iss'   => $creds['client_email'],
        'scope' => 'https://www.googleapis.com/auth/analytics.readonly',
        'aud'   => 'https://oauth2.googleapis.com/token',
        'iat'   => $now,
        'exp'   => $now + 3600,
    ]));

    $signing = $header . '.' . $payload;
    $key     = openssl_pkey_get_private($creds['private_key']);
    if (!$key) throw new Exception('Gagal memuat private key dari credentials.');

    openssl_sign($signing, $sig, $key, 'SHA256');
    $jwt = $signing . '.' . base64url_encode($sig);

    $res = ga4_http('https://oauth2.googleapis.com/token', [
        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        'assertion'  => $jwt,
    ], 'POST_FORM');

    if (!isset($res['access_token'])) {
        throw new Exception('Gagal mendapatkan access token: ' . json_encode($res));
    }
    return $res['access_token'];
}

function base64url_encode(string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

// ═════════════════════════════════════════════════════════════════════
// HTTP HELPER
// ═════════════════════════════════════════════════════════════════════
function ga4_http(string $url, array $body, string $method = 'POST', string $token = ''): array {
    $headers = ['Content-Type: application/json'];
    if ($token) $headers[] = "Authorization: Bearer $token";

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_HTTPHEADER     => $headers,
    ]);

    if ($method === 'POST_FORM') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($body));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    } elseif ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }

    $response = curl_exec($ch);
    $code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (!$response) throw new Exception('cURL gagal: ' . curl_error($ch));

    $data = json_decode($response, true);
    if ($code >= 400) {
        throw new Exception('GA4 API error ' . $code . ': ' . ($data['error']['message'] ?? $response));
    }
    return $data ?? [];
}

// ═════════════════════════════════════════════════════════════════════
// GA4 REPORT HELPER
// ═════════════════════════════════════════════════════════════════════
function runReport(string $pid, string $token, array $body): array {
    return ga4_http(
        "https://analyticsdata.googleapis.com/v1beta/properties/{$pid}:runReport",
        $body, 'POST', $token
    );
}

function parseRows(array $report, array $dimKeys, array $metKeys): array {
    $rows = $report['rows'] ?? [];
    $result = [];
    foreach ($rows as $row) {
        $item = [];
        foreach ($dimKeys as $i => $k)  $item[$k] = $row['dimensionValues'][$i]['value'] ?? '';
        foreach ($metKeys  as $i => $k) $item[$k] = $row['metricValues'][$i]['value'] ?? 0;
        $result[] = $item;
    }
    return $result;
}

// ═════════════════════════════════════════════════════════════════════
// MAIN
// ═════════════════════════════════════════════════════════════════════
try {
    $token = getAccessToken();

    // ── REALTIME ────────────────────────────────────────────────────
    if ($isRealtime) {
        $rt = ga4_http(
            "https://analyticsdata.googleapis.com/v1beta/properties/{$propertyId}:runRealtimeReport",
            [
                'dimensions' => [['name'=>'country']],
                'metrics'    => [['name'=>'activeUsers']],
            ],
            'POST', $token
        );

        $activeUsers = array_sum(array_column($rt['rows'] ?? [], null));
        $totalUsers  = 0;
        $countries   = [];
        foreach ($rt['rows'] ?? [] as $row) {
            $cnt = (int)($row['metricValues'][0]['value'] ?? 0);
            $totalUsers += $cnt;
            $countries[] = $row['dimensionValues'][0]['value'] ?? '';
        }

        $output = ['activeUsers' => $totalUsers, 'countries' => array_slice($countries, 0, 5)];
        file_put_contents($cacheFile, json_encode($output));
        echo json_encode($output);
        exit;
    }

    // ── DATE RANGES ─────────────────────────────────────────────────
    $endDate   = 'today';
    $startDate = $days . 'daysAgo';
    // Periode sebelumnya untuk perbandingan
    $prevEnd   = ($days + 1) . 'daysAgo';
    $prevStart = ($days * 2) . 'daysAgo';

    $mainRange = [['startDate'=>$startDate,'endDate'=>$endDate]];
    $twoRange  = [
        ['startDate'=>$startDate,'endDate'=>$endDate],
        ['startDate'=>$prevStart,'endDate'=>$prevEnd],
    ];

    // ── 1. Summary (current + prev) ──────────────────────────────────
    $summaryReport = runReport($propertyId, $token, [
        'dateRanges' => $twoRange,
        'metrics'    => [
            ['name'=>'totalUsers'],['name'=>'sessions'],
            ['name'=>'screenPageViews'],['name'=>'averageSessionDuration'],
            ['name'=>'bounceRate'],['name'=>'newUsers'],
        ],
    ]);

    $buildSummary = function(int $rangeIdx) use ($summaryReport): array {
        $vals = [];
        foreach ($summaryReport['rows'][0]['metricValues'] ?? [] as $i => $mv) {
            // Each metric alternates by range in date_range_0 / date_range_1
            // GA4 returns separate row per dateRange when 2 ranges used
        }
        // When 2 dateRanges, GA4 returns rows per range (dimensionValues contains dateRange)
        return [];
    };

    // Simpler: use single range for each call
    $r1 = runReport($propertyId, $token, [
        'dateRanges' => $mainRange,
        'metrics' => [
            ['name'=>'totalUsers'],['name'=>'sessions'],
            ['name'=>'screenPageViews'],['name'=>'averageSessionDuration'],
            ['name'=>'bounceRate'],['name'=>'newUsers'],
        ],
    ]);
    $r2 = runReport($propertyId, $token, [
        'dateRanges' => [['startDate'=>$prevStart,'endDate'=>$prevEnd]],
        'metrics' => [
            ['name'=>'totalUsers'],['name'=>'sessions'],
            ['name'=>'screenPageViews'],['name'=>'averageSessionDuration'],
            ['name'=>'bounceRate'],['name'=>'newUsers'],
        ],
    ]);

    $extractSummary = function(array $r): array {
        $mv = $r['rows'][0]['metricValues'] ?? [];
        return [
            'totalUsers'          => (int)   ($mv[0]['value'] ?? 0),
            'sessions'            => (int)   ($mv[1]['value'] ?? 0),
            'screenPageViews'     => (int)   ($mv[2]['value'] ?? 0),
            'avgSessionDuration'  => (float) ($mv[3]['value'] ?? 0),
            'bounceRate'          => (float) ($mv[4]['value'] ?? 0),
            'newUsers'            => (int)   ($mv[5]['value'] ?? 0),
        ];
    };

    $summary = $extractSummary($r1);
    $prev    = $extractSummary($r2);

    // ── 2. Daily trend ───────────────────────────────────────────────
    $dailyReport = runReport($propertyId, $token, [
        'dateRanges' => $mainRange,
        'dimensions' => [['name'=>'date']],
        'metrics'    => [['name'=>'totalUsers']],
        'orderBys'   => [['dimension'=>['dimensionName'=>'date'],'desc'=>false]],
    ]);
    $daily = parseRows($dailyReport, ['date'], ['totalUsers']);

    // ── 3. Channel grouping ──────────────────────────────────────────
    $chanReport = runReport($propertyId, $token, [
        'dateRanges' => $mainRange,
        'dimensions' => [['name'=>'sessionDefaultChannelGroup']],
        'metrics'    => [['name'=>'totalUsers']],
        'orderBys'   => [['metric'=>['metricName'=>'totalUsers'],'desc'=>true]],
        'limit'      => 6,
    ]);
    $channels = parseRows($chanReport, ['sessionDefaultChannelGroup'], ['totalUsers']);

    // ── 4. Top pages ─────────────────────────────────────────────────
    $pagesReport = runReport($propertyId, $token, [
        'dateRanges' => $mainRange,
        'dimensions' => [['name'=>'pagePath']],
        'metrics'    => [['name'=>'screenPageViews']],
        'orderBys'   => [['metric'=>['metricName'=>'screenPageViews'],'desc'=>true]],
        'limit'      => 100,
    ]);
    $pages = parseRows($pagesReport, ['pagePath'], ['screenPageViews']);

    // ── 5. Sources ───────────────────────────────────────────────────
    $srcReport = runReport($propertyId, $token, [
        'dateRanges' => $mainRange,
        'dimensions' => [['name'=>'sessionSource']],
        'metrics'    => [['name'=>'totalUsers']],
        'orderBys'   => [['metric'=>['metricName'=>'totalUsers'],'desc'=>true]],
        'limit'      => 8,
    ]);
    $sources = parseRows($srcReport, ['sessionSource'], ['totalUsers']);

    // ── 6. Countries ─────────────────────────────────────────────────
    $ctrReport = runReport($propertyId, $token, [
        'dateRanges' => $mainRange,
        'dimensions' => [['name'=>'country']],
        'metrics'    => [['name'=>'totalUsers']],
        'orderBys'   => [['metric'=>['metricName'=>'totalUsers'],'desc'=>true]],
        'limit'      => 5,
    ]);
    $countries = parseRows($ctrReport, ['country'], ['totalUsers']);

    // ── 7. Devices ───────────────────────────────────────────────────
    $devReport = runReport($propertyId, $token, [
        'dateRanges' => $mainRange,
        'dimensions' => [['name'=>'deviceCategory']],
        'metrics'    => [['name'=>'totalUsers']],
        'orderBys'   => [['metric'=>['metricName'=>'totalUsers'],'desc'=>true]],
    ]);
    $devices = parseRows($devReport, ['deviceCategory'], ['totalUsers']);

    // ── Output ───────────────────────────────────────────────────────
    $output = compact('summary','prev','daily','channels','pages','sources','countries','devices');
    $json   = json_encode($output, JSON_UNESCAPED_UNICODE);
    file_put_contents($cacheFile, $json);
    echo $json;

} catch (Throwable $e) {
    http_response_code(500);
    $msg = $e->getMessage();
    // Sanitize — jangan tampilkan path internal
    $msg = preg_replace('#/[^\s:]+\.php#', '[file]', $msg);
    echo json_encode(['error' => $msg]);
}