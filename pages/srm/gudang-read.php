<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

define('SPREADSHEET_ID', '1zQtkYOkwWjPPll_D0u7pJNTYebWGTMl3ilPwnEIWVKQ');
define('CRED_FILE', __DIR__ . '/credentials.json');

// =====================
// PARAM
// =====================
$sheet = $_GET['sheet'] ?? 'non_ppn';

// =====================
// LOAD CREDENTIAL
// =====================
if (!file_exists(CRED_FILE)) {
    echo json_encode(["error" => "credentials.json tidak ditemukan"]);
    exit;
}

$creds = json_decode(file_get_contents(CRED_FILE), true);

if (!isset($creds['client_email'], $creds['private_key'])) {
    echo json_encode(["error" => "credentials.json tidak valid"]);
    exit;
}

// =====================
// HELPER
// =====================
function b64url(string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

// =====================
// GET TOKEN — pakai curl seperti submit.php
// =====================
function getAccessToken(array $creds): string {
    $now     = time();
    $header  = b64url(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
    $payload = b64url(json_encode([
        'iss'   => $creds['client_email'],
        'scope' => 'https://www.googleapis.com/auth/spreadsheets',
        'aud'   => 'https://oauth2.googleapis.com/token',
        'iat'   => $now,
        'exp'   => $now + 3600,
    ]));

    $input = $header . '.' . $payload;

    $pk = openssl_pkey_get_private($creds['private_key']);
    if (!$pk) throw new Exception('Private key tidak valid.');

    openssl_sign($input, $sig, $pk, OPENSSL_ALGO_SHA256);

    $jwt = $input . '.' . b64url($sig);

    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_POSTFIELDS     => http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion'  => $jwt,
        ]),
    ]);
    $res = curl_exec($ch);
    curl_close($ch);

    if (!$res) throw new Exception('Tidak dapat terhubung ke Google OAuth.');

    $token = json_decode($res, true);
    if (!isset($token['access_token'])) {
        throw new Exception('OAuth error: ' . ($token['error_description'] ?? $token['error'] ?? $res));
    }

    return $token['access_token'];
}

// =====================
// GET TOKEN
// =====================
try {
    $token = getAccessToken($creds);
} catch (Exception $e) {
    echo json_encode(["error" => "Gagal autentikasi: " . $e->getMessage()]);
    exit;
}

// =====================
// FETCH DATA — pakai curl seperti submit.php
// =====================
$url = "https://sheets.googleapis.com/v4/spreadsheets/" . SPREADSHEET_ID . "/values/" . urlencode($sheet);

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_HTTPHEADER     => ["Authorization: Bearer $token"],
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if (!$response || $httpCode < 200 || $httpCode >= 300) {
    echo json_encode(["error" => "Gagal mengambil data dari Sheets API (HTTP $httpCode)"]);
    exit;
}

$data = json_decode($response, true);

if (!isset($data['values'])) {
    echo json_encode([]);
    exit;
}

// =====================
// FORMAT JSON
// =====================
$rows    = $data['values'];
$headers = array_shift($rows);

$result = [];

foreach ($rows as $row) {
    $obj = [];
    foreach ($headers as $i => $h) {
        $obj[$h] = $row[$i] ?? '';
    }
    $result[] = $obj;
}

echo json_encode($result);