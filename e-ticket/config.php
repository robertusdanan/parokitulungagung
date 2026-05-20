<?php
// ========================================
// CONFIG CENTRAL — e-ticket
// Edit via Admin Panel > Pengaturan Tiket
// ========================================

// ── Load secrets dari luar public_html ──────────────────────
// __DIR__ = /home/ejtkecoh/public_html/e-ticket
// dirname(__DIR__, 2) = /home/ejtkecoh
require_once dirname(__DIR__, 2) . '/private/secrets.php';

// ── SUPABASE ─────────────────────────────────────────────────
define('SUPABASE_URL',      SECRET_SUPABASE_URL);
define('SUPABASE_ANON_KEY', SECRET_SUPABASE_ANON_KEY);

// ── PENYIMPANAN GAMBAR LOKAL ──────────────────────────────────────────
define('UPLOAD_DIR', 'uploads/payment-proofs');

// ── EVENT CONFIG ──────────────────────────────────────────────
define('TOTAL_QUOTA',    1200);
define('WA_GROUP_LINK',      'https://chat.whatsapp.com/DphJqo2kL3W30OMR3Gdsr2?mode=gi_t');
define('TICKET_PAGE_BASE',   'https://www.parokitulungagung.org/e-ticket/pages/ticket/');
define('BANK_NAME',          'BCA');
define('BANK_NUMBER',    '0489898430');
define('BANK_HOLDER',    'Gereja Katolik Paroki Santa Maria');

// ── LAYOUT KURSI ──────────────────────────────────────────────
define('ANGLED_ROWS', serialize([
    1 => 13,
    2 => 17,
    3 => 20,
]));
define('NORMAL_COLS', 25);
define('TOTAL_ROWS',  25);

// ── HARGA TIKET ───────────────────────────────────────────────
$ticketPrices = [
    'vvip'    => 1000000,
    'vip'     => 500000,
    'kelas1'  => 250000,
    'reguler' => 150000,
];

// ── KONFIGURASI TIKET (4 kategori) ────────────────────────────
$ticketConfig = [
    [
        'type'     => 'vvip',
        'name'     => 'VVIP',
        'category' => 'Ultimate Premium',
        'quota'    => 100,
        'color'    => '#ffcf32',
        'rowStart' => 1,
        'rowEnd'   => 3,
        'features' => [
            'Kursi menyerong eksklusif paling depan',
            'Meet & greet eksklusif',
            'Merchandise premium',
            'Sertifikat kehadiran',
        ],
    ],
    [
        'type'     => 'vip',
        'name'     => 'VIP',
        'category' => 'Premium Experience',
        'quota'    => 250,
        'color'    => '#da0424',
        'rowStart' => 4,
        'rowEnd'   => 8,
        'features' => [
            'Kursi premium, view terbaik',
            'Akses prioritas masuk',
            'Merchandise eksklusif',
            'Snack & minuman premium',
        ],
    ],
    [
        'type'     => 'kelas1',
        'name'     => 'Kelas 1',
        'category' => 'Excellent View',
        'quota'    => 350,
        'color'    => '#6B8E23',
        'rowStart' => 9,
        'rowEnd'   => 15,
        'features' => [
            'Posisi strategis dan nyaman',
            'Merchandise',
            'Snack & minuman',
            'Doorprize',
        ],
    ],
    [
        'type'     => 'reguler',
        'name'     => 'Reguler',
        'category' => 'Standard Access',
        'quota'    => 500,
        'color'    => '#4682B4',
        'rowStart' => 16,
        'rowEnd'   => 25,
        'features' => [
            'Akses penuh acara',
            'Kursi standar nyaman',
            'Doorprize',
            'Suasana meriah',
        ],
    ],
];

// ── HELPERS ───────────────────────────────────────────────────

function generateSeatConfigPhp(): array {
    $config     = [];
    $angledRows = unserialize(ANGLED_ROWS);
    $normalCols = NORMAL_COLS;
    $totalRows  = TOTAL_ROWS;

    global $ticketConfig;
    $rowToType = [];
    foreach ($ticketConfig as $tc) {
        for ($r = $tc['rowStart']; $r <= $tc['rowEnd']; $r++) {
            $rowToType[$r] = $tc['type'];
        }
    }

    for ($row = 1; $row <= $totalRows; $row++) {
        $type = $rowToType[$row] ?? 'reguler';
        $cols = $angledRows[$row] ?? $normalCols;
        for ($seat = 1; $seat <= $cols; $seat++) {
            $config["L-{$row}-{$seat}"] = $type;
            $config["R-{$row}-{$seat}"] = $type;
        }
    }
    return $config;
}

function jsConfig(array $ticketPrices, array $ticketConfig): string {
    $prices      = json_encode($ticketPrices);
    $config      = json_encode($ticketConfig);
    $supabaseUrl = SUPABASE_URL;
    $anonKey     = SUPABASE_ANON_KEY;
    $waLink      = WA_GROUP_LINK;
    $ticketBase  = TICKET_PAGE_BASE;
    $bankName    = BANK_NAME;
    $bankNumber  = BANK_NUMBER;
    $bankHolder  = BANK_HOLDER;
    $totalQuota  = TOTAL_QUOTA;
    $angledRows  = json_encode(unserialize(ANGLED_ROWS));
    $normalCols  = NORMAL_COLS;
    $totalRows   = TOTAL_ROWS;

    return <<<JS
<script>
// === CONFIG dari PHP ===
const SUPABASE_URL      = "{$supabaseUrl}";
const SUPABASE_ANON     = "{$anonKey}";
const TICKET_PAGE_BASE  = "{$ticketBase}";
const WA_GROUP_LINK     = "{$waLink}";
const BANK_NAME     = "{$bankName}";
const BANK_NUMBER   = "{$bankNumber}";
const BANK_HOLDER   = "{$bankHolder}";
const TOTAL_QUOTA   = {$totalQuota};
const ANGLED_ROWS   = {$angledRows};
const NORMAL_COLS   = {$normalCols};
const TOTAL_ROWS    = {$totalRows};
window.ticketPrices = {$prices};
window.ticketConfig = {$config};
</script>
JS;
}

function jsSeatConfig(): string {
    $seatConfig = generateSeatConfigPhp();
    $json       = json_encode($seatConfig);
    return <<<JS
<script>
const seatConfig = {$json};
</script>
JS;
}

function asset(string $path): string {
    $fullPath = __DIR__ . '/' . ltrim($path, '/');
    $v = file_exists($fullPath) ? filemtime($fullPath) : time();
    return $path . '?v=' . $v;
}

function getSeatStatus(): array {
    global $ticketConfig;
    $file = __DIR__ . '/data/seat-config.json';
    $saved = [];
    if (file_exists($file)) {
        $decoded = json_decode(file_get_contents($file), true);
        if (is_array($decoded)) $saved = $decoded;
    }
    $result = [];
    foreach ($ticketConfig as $tc) {
        $type  = $tc['type'];
        $quota = (int)($saved[$type]['quota'] ?? $tc['quota']);
        $result[$type] = [
            'quota'      => $quota,
            'open_quota' => (int)($saved[$type]['open_quota'] ?? $quota),
            'closed'     => (bool)($saved[$type]['closed'] ?? false),
        ];
    }
    return $result;
}

function jsSeatStatus(array $seatStatus): string {
    $json = json_encode($seatStatus);
    return "<script>\nwindow.seatStatus = {$json};\n</script>";
}

function base_url($path = '') {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    return $protocol . $host . '/' . ltrim($path, '/');
}
