<?php
/**
 * StokPro - kasir-submit.php
 * action=jual  → append items ke sheet 'penjualan' + kurangi stok gudang
 * action=beli  → append ke sheet 'pembelian' + tambah/update stok gudang
 *
 * Kolom sheet penjualan (A–T):
 *   A  no_nota        B  tanggal        C  gudang
 *   D  kode_barang    E  kode_internal  F  nama_produk
 *   G  nama_mobil     H  merk           I  kategori
 *   J  qty            K  harga_jual     L  subtotal
 *   M  total          N  metode_bayar   O  keterangan
 *   P  uang_diterima  Q  diskon_pct     R  costumer
 *   S  harga_asli     T  waktu_transaksi   ← dua kolom baru
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

define('SPREADSHEET_ID', '1zQtkYOkwWjPPll_D0u7pJNTYebWGTMl3ilPwnEIWVKQ');
define('CRED_FILE', __DIR__ . '/credentials.json');

function respond(bool $ok, string $msg = '', array $extra = []): never {
    echo json_encode(array_merge(['success' => $ok, 'message' => $msg], $extra));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') respond(false, 'Method tidak diizinkan.');

$body = file_get_contents('php://input');
$data = json_decode($body, true);
if (!$data) respond(false, 'Data tidak valid.');

if (!file_exists(CRED_FILE)) respond(false, 'credentials.json tidak ditemukan.');
$creds = json_decode(file_get_contents(CRED_FILE), true);
if (!isset($creds['client_email'], $creds['private_key'])) respond(false, 'credentials.json tidak valid.');

try { $token = getAccessToken($creds); }
catch (Exception $e) { respond(false, 'Gagal autentikasi: ' . $e->getMessage()); }

$action = trim($data['action'] ?? '');

// ═══════════════════════════════════════════════════════════════
// ACTION: JUAL
// ═══════════════════════════════════════════════════════════════
if ($action === 'jual') {
    $gudang          = in_array($data['gudang'] ?? '', ['non_ppn','ppn']) ? $data['gudang'] : 'non_ppn';
    $items           = $data['items']           ?? [];
    $noNota          = trim($data['no_nota']         ?? '');
    $tanggal         = trim($data['tanggal']         ?? '');
    $waktuTransaksi  = trim($data['waktu_transaksi'] ?? ''); // ← baru (format DD/MM/YYYY HH:MM:SS)
    $metode          = trim($data['metode_bayar']    ?? 'Tunai');
    $ket             = trim($data['keterangan']      ?? 'Toko');
    $costumer        = trim($data['costumer']        ?? '');
    $total           = intval($data['total']         ?? 0);
    $uangDiterima    = intval($data['uang_diterima'] ?? 0);

    if (empty($items))  respond(false, 'Keranjang kosong.');
    if (empty($noNota)) respond(false, 'No. nota tidak valid.');

    // Fallback: jika frontend lama tidak mengirim waktu_transaksi, pakai waktu server
    if (empty($waktuTransaksi)) {
        $now = new DateTime('now', new DateTimeZone('Asia/Jakarta'));
        $waktuTransaksi = $now->format('d/m/Y H:i:s');
    }

    try {
        // 1. Baca sheet gudang untuk cari row index stok
        $gudangRows  = getSheetValues($token, SPREADSHEET_ID, $gudang);

        // 2. Siapkan baris penjualan — 1 baris per item
        //    Kolom A–T (20 kolom):
        //    A  no_nota        B  tanggal        C  gudang
        //    D  kode_barang    E  kode_internal  F  nama_produk
        //    G  nama_mobil     H  merk           I  kategori
        //    J  qty            K  harga_jual     L  subtotal
        //    M  total          N  metode_bayar   O  keterangan
        //    P  uang_diterima  Q  diskon_pct     R  costumer
        //    S  harga_asli     T  waktu_transaksi
        $jualRows    = [];
        $stokUpdates = [];
        $lastIdx     = count($items) - 1;

        foreach ($items as $idx => $item) {
            $qty       = intval($item['qty']        ?? 1);
            $hargaJual = intval($item['harga_jual'] ?? 0);   // harga efektif (sudah diskon)
            $hargaAsli = intval($item['harga_asli'] ?? $hargaJual); // harga sebelum diskon
            $diskonPct = floatval($item['diskon_pct'] ?? 0);
            $subtotal  = $qty * $hargaJual;

$jualRows[] = [
    $noNota,
    $tanggal,
    $gudang,
    trim($item['kode_barang']   ?? ''),
    trim($item['kode_internal'] ?? ''),
    trim($item['nama_produk']   ?? ''),
    trim($item['nama_mobil']    ?? ''),
    trim($item['merk']          ?? ''),
    trim($item['kategori']      ?? ''),
    $qty,
    $hargaJual,
    $subtotal,

    // ✅ SEMUA DIISI
    $total,
    $metode,
    $ket,
    ($metode === 'Tunai' && $uangDiterima > 0) ? $uangDiterima : '',
    $diskonPct > 0 ? $diskonPct . '%' : '',
    $costumer,
    $hargaAsli,
    $waktuTransaksi,
];

            // Cari baris di gudang untuk update stok
            $kodeBarang = trim($item['kode_barang'] ?? '');
            $supplier   = trim($item['supplier']   ?? ''); // FIX: ambil supplier dari item
            if ($kodeBarang) {
                $rowNum = findRowByKode($gudangRows, $kodeBarang, $supplier); // FIX: teruskan supplier
                if ($rowNum !== false) {
                    $currentStok = intval($gudangRows[$rowNum - 2][7] ?? 0);
                    $newStok     = max(0, $currentStok - $qty);
                    $stokUpdates[$rowNum] = $newStok;
                }
            }
        }

        // 3. Catat jumlah baris saat ini (sebelum append) agar tahu posisi merge
        $rowsBefore = getPenjualanRowCount($token, SPREADSHEET_ID);

        // 4. Append semua baris ke sheet penjualan (A:T — 20 kolom)
        appendRows($token, SPREADSHEET_ID, 'penjualan', $jualRows, 'A:T');

        // 5. Merge kolom M–T jika nota terdiri dari lebih dari 1 item
        if (count($jualRows) > 1) {
            $sheetId  = getSheetId($token, SPREADSHEET_ID, 'penjualan');
            // rowsBefore sudah 0-indexed (karena getPenjualanRowCount include header)
            $startRow = $rowsBefore;                        // baris pertama item (0-indexed)
            $endRow   = $rowsBefore + count($jualRows);     // baris terakhir + 1 (exclusive)
            
        }

        // 6. Batch update stok di gudang
        if (!empty($stokUpdates)) {
            batchUpdateStok($token, SPREADSHEET_ID, $gudang, $stokUpdates);
        }

        respond(true, 'Transaksi berhasil disimpan.', ['no_nota' => $noNota]);

    } catch (Exception $e) {
        respond(false, 'Gagal: ' . $e->getMessage());
    }
}

// ═══════════════════════════════════════════════════════════════
// ACTION: BELI
// ═══════════════════════════════════════════════════════════════
elseif ($action === 'beli') {
    $gudang       = in_array($data['gudang'] ?? '', ['non_ppn','ppn']) ? $data['gudang'] : 'non_ppn';
    $noPO         = trim($data['no_po']       ?? '');
    $tgl          = trim($data['tanggal']     ?? '');
    $ket          = trim($data['keterangan']  ?? '');

    $kodeBarang   = trim($data['kode_barang']   ?? '');
    $kodeInternal = trim($data['kode_internal'] ?? '');
    $merk         = trim($data['merk']          ?? '');
    $namaProduk   = trim($data['nama_produk']   ?? '');
    $namaMobil    = trim($data['nama_mobil']    ?? '');
    $kategori     = trim($data['kategori']      ?? '');
    $lokasiRak    = trim($data['lokasi_rak']    ?? '');
    $qtyBeli      = intval($data['stok']        ?? 0);
    $supplier     = trim($data['supplier']      ?? '');
    $hargaBeli    = intval($data['harga_beli']  ?? 0);
    $hargaJual    = intval($data['harga_jual']  ?? 0);
    $tanggalBeli  = trim($data['tanggal_beli']  ?? '');

    if (empty($kodeBarang)) respond(false, 'Kode barang wajib diisi.');
    if (empty($namaProduk)) respond(false, 'Nama produk wajib diisi.');

    try {
        // 1. Append ke sheet pembelian
        // Kolom: A=no_po, B=tanggal, C=gudang, D=kode_barang, E=kode_internal,
        // F=merk, G=nama_produk, H=nama_mobil, I=kategori, J=lokasi_rak,
        // K=qty_beli, L=supplier, M=harga_beli, N=harga_jual, O=tanggal_beli, P=keterangan
        $beliRow = [
            $noPO, $tgl, $gudang, $kodeBarang, $kodeInternal,
            $merk, $namaProduk, $namaMobil, $kategori, $lokasiRak,
            $qtyBeli, $supplier, $hargaBeli, $hargaJual, $tanggalBeli, $ket
        ];
        appendRows($token, SPREADSHEET_ID, 'pembelian', [$beliRow], 'A:P');

        // 2. Update stok di gudang
        $gudangRows = getSheetValues($token, SPREADSHEET_ID, $gudang);
        $rowNum     = findRowByKode($gudangRows, $kodeBarang);

        if ($rowNum !== false) {
            $currentStok = intval($gudangRows[$rowNum - 2][7] ?? 0);
            $newStok     = $currentStok + $qtyBeli;
            batchUpdateStok($token, SPREADSHEET_ID, $gudang, [$rowNum => $newStok]);

            if ($hargaBeli > 0 || $hargaJual > 0) {
                $updates = [];
                if ($hargaBeli > 0)       $updates["{$gudang}!J{$rowNum}"] = $hargaBeli;
                if ($hargaJual > 0)       $updates["{$gudang}!K{$rowNum}"] = $hargaJual;
                if (!empty($tanggalBeli)) $updates["{$gudang}!L{$rowNum}"] = $tanggalBeli;
                batchUpdateCells($token, SPREADSHEET_ID, $updates);
            }
            respond(true, 'Pembelian disimpan & stok gudang diperbarui.', ['action_gudang' => 'updated']);
        } else {
            $gudangRow = [
                $kodeBarang, $kodeInternal, $merk, $namaProduk, $namaMobil,
                $kategori, $lokasiRak, $qtyBeli, $supplier,
                $hargaBeli, $hargaJual, $tanggalBeli
            ];
            appendRows($token, SPREADSHEET_ID, $gudang, [$gudangRow], 'A:L');
            respond(true, 'Pembelian disimpan & barang baru ditambahkan ke gudang.', ['action_gudang' => 'appended']);
        }

    } catch (Exception $e) {
        respond(false, 'Gagal: ' . $e->getMessage());
    }
}

else {
    respond(false, "Action '$action' tidak dikenal.");
}

// ═══════════════════════════════════════════════════════════════
// HELPERS
// ═══════════════════════════════════════════════════════════════

/**
 * Hitung total baris di sheet penjualan (termasuk header).
 * Dipakai untuk menentukan startRow sebelum append agar merge tepat sasaran.
 */
function getPenjualanRowCount(string $token, string $sid): int {
    $url = "https://sheets.googleapis.com/v4/spreadsheets/{$sid}/values/" . urlencode('penjualan!A:A');
    $ch  = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $token],
    ]);
    $res  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code !== 200) return 1; // fallback: anggap hanya header
    $json = json_decode($res, true);
    return count($json['values'] ?? []);
}

/**
 * Ambil numeric sheetId dari nama sheet (diperlukan untuk batchUpdate formatting).
 */
function getSheetId(string $token, string $sid, string $sheetName): int {
    $url = "https://sheets.googleapis.com/v4/spreadsheets/{$sid}?fields=sheets.properties";
    $ch  = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $token],
    ]);
    $res = curl_exec($ch);
    curl_close($ch);
    $json = json_decode($res, true);
    foreach ($json['sheets'] ?? [] as $s) {
        if ($s['properties']['title'] === $sheetName) {
            return (int) $s['properties']['sheetId'];
        }
    }
    return 0; // fallback
}

/**
 * Merge kolom M–T (index 12–19) secara vertikal untuk satu nota.
 * Masing-masing kolom di-merge sendiri-sendiri (MERGE_ALL per kolom)
 * lalu alignment tengah diterapkan agar mudah dibaca.
 *
 * @param int $startRow  Baris pertama item (0-indexed, sudah include offset header)
 * @param int $endRow    Baris setelah item terakhir (exclusive, 0-indexed)
 */
function mergePenjualanColumns(string $token, string $sid, int $sheetId, int $startRow, int $endRow): void {
    $MERGE_COLS_START = 12; // kolom M
    $MERGE_COLS_END   = 20; // kolom T + 1 (exclusive)

    $requests = [];

    // 1. Merge tiap kolom M–T secara vertikal
    for ($col = $MERGE_COLS_START; $col < $MERGE_COLS_END; $col++) {
        $requests[] = [
            'mergeCells' => [
                'range' => [
                    'sheetId'          => $sheetId,
                    'startRowIndex'    => $startRow,
                    'endRowIndex'      => $endRow,
                    'startColumnIndex' => $col,
                    'endColumnIndex'   => $col + 1,
                ],
                'mergeType' => 'MERGE_ALL',
            ],
        ];
    }

    // 2. Terapkan vertical alignment MIDDLE + wrap text pada area yang di-merge
    $requests[] = [
        'repeatCell' => [
            'range' => [
                'sheetId'          => $sheetId,
                'startRowIndex'    => $startRow,
                'endRowIndex'      => $endRow,
                'startColumnIndex' => $MERGE_COLS_START,
                'endColumnIndex'   => $MERGE_COLS_END,
            ],
            'cell' => [
                'userEnteredFormat' => [
                    'verticalAlignment' => 'MIDDLE',
                    'wrapStrategy'      => 'WRAP',
                ],
            ],
            'fields' => 'userEnteredFormat.verticalAlignment,userEnteredFormat.wrapStrategy',
        ],
    ];

    $url = "https://sheets.googleapis.com/v4/spreadsheets/{$sid}:batchUpdate";
    apiReq($url, 'POST', $token, json_encode(['requests' => $requests]));
}

function getSheetValues(string $token, string $sid, string $sheet): array {
    $range = urlencode($sheet . '!A:L');
    $url   = "https://sheets.googleapis.com/v4/spreadsheets/{$sid}/values/{$range}";
    $ch    = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token],
    ]);
    $res  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code !== 200) throw new Exception("Gagal baca sheet $sheet (HTTP $code)");
    $json = json_decode($res, true);
    $rows = $json['values'] ?? [];
    return array_slice($rows, 1); // skip header row
}

function findRowByKode(array $dataRows, string $kode, string $supplier = ''): int|false {
    // A=kode_barang(0), ..., I=supplier(8)
    $firstMatch = false;
    foreach ($dataRows as $i => $row) {
        if (isset($row[0]) && (string)$row[0] === $kode) {
            $rowNum = $i + 2; // +1 for 0-based, +1 for header row
            // Jika supplier dikirim, prioritaskan baris yang supplier-nya cocok
            if ($supplier !== '' && isset($row[8]) && trim((string)$row[8]) === $supplier) {
                return $rowNum; // exact match: kode_barang + supplier
            }
            // Simpan match pertama sebagai fallback
            if ($firstMatch === false) {
                $firstMatch = $rowNum;
            }
        }
    }
    // Fallback ke baris pertama yang kode_barang-nya cocok
    return $firstMatch;
}

function appendRows(string $token, string $sid, string $sheet, array $rows, string $colRange): void {
    $range = urlencode($sheet . '!' . $colRange);
    $url   = "https://sheets.googleapis.com/v4/spreadsheets/{$sid}/values/{$range}:append?valueInputOption=RAW&insertDataOption=INSERT_ROWS";
    apiReq($url, 'POST', $token, json_encode(['values' => $rows]));
}

function batchUpdateStok(string $token, string $sid, string $sheet, array $updates): void {
    $data = [];
    foreach ($updates as $rowNum => $newStok) {
        $data[] = ['range' => "{$sheet}!H{$rowNum}", 'values' => [[$newStok]]];
    }
    $url  = "https://sheets.googleapis.com/v4/spreadsheets/{$sid}/values:batchUpdate";
    apiReq($url, 'POST', $token, json_encode(['valueInputOption' => 'RAW', 'data' => $data]));
}

function batchUpdateCells(string $token, string $sid, array $cellMap): void {
    $data = [];
    foreach ($cellMap as $range => $value) {
        $data[] = ['range' => $range, 'values' => [[$value]]];
    }
    $url  = "https://sheets.googleapis.com/v4/spreadsheets/{$sid}/values:batchUpdate";
    apiReq($url, 'POST', $token, json_encode(['valueInputOption' => 'RAW', 'data' => $data]));
}

function apiReq(string $url, string $method, string $token, string $body): string {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_POSTFIELDS     => $body,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
            'Content-Length: ' . strlen($body),
        ],
    ]);
    $res  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code < 200 || $code >= 300) {
        $err = json_decode($res, true);
        throw new Exception("Sheets API error ($code): " . ($err['error']['message'] ?? $res));
    }
    return $res;
}

function b64url(string $d): string { return rtrim(strtr(base64_encode($d), '+/', '-_'), '='); }

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
    $pk    = openssl_pkey_get_private($creds['private_key']);
    if (!$pk) throw new Exception('Private key tidak valid.');
    openssl_sign($input, $sig, $pk, OPENSSL_ALGO_SHA256);
    $jwt = $input . '.' . b64url($sig);
    $ch  = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt_array($ch, [
        CURLOPT_POST            => true,
        CURLOPT_RETURNTRANSFER  => true,
        CURLOPT_TIMEOUT         => 15,
        CURLOPT_POSTFIELDS      => http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion'  => $jwt,
        ]),
    ]);
    $res = curl_exec($ch);
    curl_close($ch);
    if (!$res) throw new Exception('Tidak dapat terhubung ke Google OAuth.');
    $t = json_decode($res, true);
    if (!isset($t['access_token'])) throw new Exception('OAuth error: ' . ($t['error_description'] ?? $t['error'] ?? $res));
    return $t['access_token'];
}