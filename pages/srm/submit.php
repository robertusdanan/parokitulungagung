<?php
/**
 * StokPro - submit.php
 * action=append → tambah baris baru
 * action=update  → update baris existing (row_index = 0-based index dari data array)
 *
 * Kolom spreadsheet:
 * A=kode_barang, B=kode_internal, C=merk, D=nama_produk, E=nama_mobil,
 * F=nama_lain, G=lokasi_rak, H=stok, I=supplier,
 * J=harga_beli (pricelist asli), K=harga_jual, L=tanggal_beli,
 * M=(kosong/reserved), N=cetak_label,
 * O=diskon (%), P=harga_final (setelah diskon)
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

define('SPREADSHEET_ID', '1zQtkYOkwWjPPll_D0u7pJNTYebWGTMl3ilPwnEIWVKQ');
define('ALLOWED_SHEETS', ['non_ppn', 'ppn']);
define('CRED_FILE',      __DIR__ . '/credentials.json');

function respond(bool $success, string $message = '', array $data = []): void {
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $data));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Method tidak diizinkan.');
}

$body = file_get_contents('php://input');
$data = json_decode($body, true);
if (!$data) respond(false, 'Data tidak valid.');

$sheetName = trim($data['sheet_name'] ?? 'non_ppn');
if (!in_array($sheetName, ALLOWED_SHEETS, true)) respond(false, "Sheet tidak valid.");

$action = trim($data['action'] ?? 'append');

// Validasi field produk hanya untuk append/update
if ($action !== 'mark_cetak') {
    $required = ['kode_barang', 'nama_produk'];
    foreach ($required as $field) {
        if (empty(trim($data[$field] ?? ''))) respond(false, "Field '$field' wajib diisi.");
    }
}

// ── Logika diskon & kode_internal ──────────────────────────────────
// kode_internal sudah digenerate lengkap oleh JS (termasuk kode KENARI diskon)
// PHP hanya menghitung harga_final dan menyimpan diskon ke kolom O & P
$diskonRaw    = trim($data['diskon'] ?? '');           // misal "20", "12.5", atau ""
$diskonAngka  = ($diskonRaw !== '') ? floatval($diskonRaw) : 0;
$hargaBeli    = intval($data['harga_beli'] ?? 0);      // J: pricelist asli
$kodeInternal = trim($data['kode_internal'] ?? '');    // sudah final dari JS, tidak diubah

if ($diskonAngka > 0) {
    // Hitung harga final (P)
    $hargaFinal = intval(round($hargaBeli * (1 - $diskonAngka / 100)));

    // Nilai yang disimpan ke O dan P
    $diskonSimpan     = $diskonRaw;
    $hargaFinalSimpan = $hargaFinal;
} else {
    // Tidak ada diskon — O dan P kosong
    $diskonSimpan     = '';
    $hargaFinalSimpan = '';
}

// ── Susun baris: A–L lalu M (kosong), N (kosong/cetak_label diurus terpisah),
//                O=diskon, P=harga_final ─────────────────────────────
// A=kode_barang, B=kode_internal, C=merk, D=nama_produk, E=nama_mobil,
// F=nama_lain, G=lokasi_rak, H=stok, I=supplier,
// J=harga_beli (pricelist), K=harga_jual, L=tanggal_beli,
// M='', N='', O=diskon, P=harga_final
$row = [
    trim($data['kode_barang']  ?? ''),  // A
    $kodeInternal,                       // B (sudah dimodifikasi jika ada diskon)
    trim($data['merk']         ?? ''),  // C
    trim($data['nama_produk']  ?? ''),  // D
    trim($data['nama_mobil']   ?? ''),  // E
    trim($data['nama_lain']     ?? ''),  // F
    trim($data['lokasi_rak']   ?? ''),  // G
    intval($data['stok']       ?? 0),   // H
    trim($data['supplier']     ?? ''),  // I
    $hargaBeli,                          // J — pricelist asli
    intval($data['harga_jual'] ?? 0),   // K
    trim($data['tanggal_beli'] ?? ''),  // L
    '',                                  // M — kosong (reserved)
    '',                                  // N — cetak_label (diurus oleh mark_cetak)
    $diskonSimpan,                       // O — persentase diskon
    $hargaFinalSimpan,                   // P — harga setelah diskon
];

if (!file_exists(CRED_FILE)) respond(false, 'File credentials.json tidak ditemukan.');
$creds = json_decode(file_get_contents(CRED_FILE), true);
if (!isset($creds['client_email'], $creds['private_key'])) respond(false, 'credentials.json tidak valid.');

try { $accessToken = getAccessToken($creds); }
catch (Exception $e) { respond(false, 'Gagal autentikasi: ' . $e->getMessage()); }

$action = trim($data['action'] ?? 'append');

try {
    if ($action === 'update') {
        $rowIndex = intval($data['row_index'] ?? -1);
        if ($rowIndex < 0) respond(false, 'row_index tidak valid.');
        $sheetRow = $rowIndex + 2; // +1 for 1-indexed, +1 for header row
        updateRow($accessToken, SPREADSHEET_ID, $sheetName, $sheetRow, $row);
        respond(true, 'Data berhasil diperbarui.');
    } elseif ($action === 'mark_cetak') {
        // Batch-update kolom N (cetak_label) untuk beberapa baris sekaligus
        $rows = $data['rows'] ?? [];
        if (empty($rows)) respond(false, 'Tidak ada baris yang dikirim.');
        $batchData = [];
        foreach ($rows as $r) {
            $sheetRow = intval($r['sheet_row'] ?? 0);
            $value    = trim($r['value'] ?? '');
            if ($sheetRow < 2) continue;
            $batchData[] = [
                'range'  => "{$sheetName}!N{$sheetRow}",
                'values' => [[$value]],
            ];
        }
        if (empty($batchData)) respond(false, 'Tidak ada baris valid.');
        $url  = "https://sheets.googleapis.com/v4/spreadsheets/" . SPREADSHEET_ID . "/values:batchUpdate";
        $body = json_encode(['valueInputOption' => 'RAW', 'data' => $batchData]);
        apiReq($url, 'POST', $accessToken, $body);
        respond(true, count($batchData) . ' baris ditandai.');
    } else {
        appendRow($accessToken, SPREADSHEET_ID, $sheetName, $row);
        respond(true, 'Data berhasil disimpan.');
    }
} catch (Exception $e) {
    respond(false, 'Gagal: ' . $e->getMessage());
}

// ═══════════════════════════════════════════════════════════════════
function b64url(string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function getAccessToken(array $creds): string {
    $now     = time();
    $header  = b64url(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
    $payload = b64url(json_encode([
        'iss'   => $creds['client_email'],
        'scope' => 'https://www.googleapis.com/auth/spreadsheets',
        'aud'   => 'https://oauth2.googleapis.com/token',
        'iat'   => $now, 'exp' => $now + 3600,
    ]));
    $input = $header . '.' . $payload;
    $pk = openssl_pkey_get_private($creds['private_key']);
    if (!$pk) throw new Exception('Private key tidak valid.');
    openssl_sign($input, $sig, $pk, OPENSSL_ALGO_SHA256);
    $jwt = $input . '.' . b64url($sig);

    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt_array($ch, [CURLOPT_POST=>true, CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>15,
        CURLOPT_POSTFIELDS=>http_build_query(['grant_type'=>'urn:ietf:params:oauth:grant-type:jwt-bearer','assertion'=>$jwt])]);
    $res = curl_exec($ch); curl_close($ch);
    if (!$res) throw new Exception('Tidak dapat terhubung ke Google OAuth.');
    $token = json_decode($res, true);
    if (!isset($token['access_token'])) throw new Exception('OAuth error: '.($token['error_description']??$token['error']??$res));
    return $token['access_token'];
}

function appendRow(string $token, string $sid, string $sheet, array $row): void {
    $range = urlencode($sheet . '!A:P');
    apiReq("https://sheets.googleapis.com/v4/spreadsheets/{$sid}/values/{$range}:append?valueInputOption=RAW&insertDataOption=INSERT_ROWS",
        'POST', $token, json_encode(['values'=>[$row]]));
}

function updateRow(string $token, string $sid, string $sheet, int $sheetRow, array $row): void {
    $range = "{$sheet}!A{$sheetRow}:P{$sheetRow}";
    apiReq("https://sheets.googleapis.com/v4/spreadsheets/{$sid}/values/".urlencode($range)."?valueInputOption=RAW",
        'PUT', $token, json_encode(['range'=>$range,'majorDimension'=>'ROWS','values'=>[$row]]));
}

function apiReq(string $url, string $method, string $token, string $body): void {
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_CUSTOMREQUEST=>$method, CURLOPT_POSTFIELDS=>$body,
        CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>15,
        CURLOPT_HTTPHEADER=>['Authorization: Bearer '.$token,'Content-Type: application/json','Content-Length: '.strlen($body)]]);
    $res  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code < 200 || $code >= 300) {
        $err = json_decode($res, true);
        throw new Exception("Sheets API error ($code): ".($err['error']['message']??$res));
    }
}