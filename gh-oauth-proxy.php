<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'method_not_allowed']);
    exit;
}

$allowedTargets = [
    'device_code'  => 'https://github.com/login/device/code',
    'access_token' => 'https://github.com/login/oauth/access_token',
];

$action = $_GET['action'] ?? '';

if (!isset($allowedTargets[$action])) {
    http_response_code(400);
    echo json_encode(['error' => 'invalid_action', 'error_description' => 'action harus device_code atau access_token']);
    exit;
}

$targetUrl = $allowedTargets[$action];
$body = file_get_contents('php://input');

$ch = curl_init($targetUrl);
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $body,
    CURLOPT_HTTPHEADER     => [
        'Accept: application/json',
        'Content-Type: application/json',
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_SSL_VERIFYPEER => true,
]);

$response = curl_exec($ch);
$curlErr  = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response === false) {
    http_response_code(502);
    echo json_encode(['error' => 'proxy_failed', 'error_description' => $curlErr]);
    exit;
}

http_response_code($httpCode ?: 502);
header('Content-Type: application/json');
echo $response;
