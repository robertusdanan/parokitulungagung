<?php
/**
 * chatbot/api/chatbot.php — Endpoint Utama Chatbot Customer Service Paroki SMDTBA
 */

header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonOut(405, ['error' => 'Method not allowed. Gunakan POST.']);
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/knowledge.php';
require_once __DIR__ . '/articles.php';
require_once __DIR__ . '/router.php';
require_once __DIR__ . '/gemini.php';
require_once __DIR__ . '/groq.php';
require_once __DIR__ . '/memory.php';
require_once __DIR__ . '/logger.php';

// ─── Baca Input JSON ───────────────────────────────────────
$rawInput = file_get_contents('php://input');
if (empty($rawInput)) {
    jsonOut(400, ['error' => 'Payload JSON kosong.']);
}

$input = json_decode($rawInput, true);
if (json_last_error() !== JSON_ERROR_NONE || !is_array($input)) {
    jsonOut(400, ['error' => 'Format JSON tidak valid.']);
}

$message   = trim($input['message'] ?? '');
$sessionId = trim($input['session_id'] ?? '');
$pageUrl   = trim($input['page_url'] ?? '');

if ($message === '') {
    jsonOut(400, ['error' => 'Pesan tidak boleh kosong.']);
}

if (mb_strlen($message) > MAX_MSG_LENGTH) {
    jsonOut(400, ['error' => 'Pesan terlalu panjang (maksimal ' . MAX_MSG_LENGTH . ' karakter).']);
}

if ($sessionId === '') {
    $sessionId = bin2hex(random_bytes(16));
}

// ─── Rate Limiter ──────────────────────────────────────────
if (!checkRateLimit($sessionId)) {
    jsonOut(429, [
        'answer'        => 'Mohon maaf, Anda mengirim pesan terlalu cepat. Silakan tunggu beberapa detik sebelum bertanya kembali. 🙏',
        'source'        => 'rate_limit',
        'session_id'    => $sessionId,
        'quick_replies' => defaultQuickReplies(),
    ]);
}

// ─── Filter Topik Berbahaya / Ilegal ───────────────────────
$offTopic = checkSevereOffTopic($message);
if ($offTopic !== null) {
    jsonOut(200, [
        'answer'        => $offTopic,
        'source'        => 'off_topic',
        'session_id'    => $sessionId,
        'quick_replies' => defaultQuickReplies(),
    ]);
}

// ─── Inisialisasi Session & Memory ─────────────────────────
$memory = new ChatMemory($sessionId);
if ($pageUrl) {
    $memory->setPageContext($pageUrl);
}

// ─── Dynamic RAG & Knowledge Retrieval ─────────────────────
// Cari secara komprehensif di seluruh database website
$ragContext = ArticleReader::buildRAGContextString($message);
$pageText   = '';
if ($pageUrl) {
    $pageText = ArticleReader::fetch($pageUrl) ?? '';
    if ($pageText !== '') {
        $pageText = "Konten halaman aktif yang sedang dilihat user:\n" . mb_substr($pageText, 0, 3000);
    }
}

// ─── Panggil AI (9Router dengan fallback ke Gemini & Groq) ─
$aiResult = RouterAI::ask(
    userMessage: $message,
    history:     $memory->getGeminiHistory(),
    pageContext: $pageText,
    articleText: $ragContext
);

$answer = $aiResult['answer'];
$source = $aiResult['provider'] ?? '9router';
if ($aiResult['error']) {
    $source = 'fallback';
}

$latencyMs = round($aiResult['latency_ms'] ?? 0);

// ─── Simpan Memory & Log ───────────────────────────────────
$memory->addTurn($message, $answer, $source);
ChatLogger::log($sessionId, $message, $answer, $source, $latencyMs);

// ─── Siapkan Quick Replies Cerdas ──────────────────────────
$quickReplies = buildContextualQuickReplies($message);

// ─── Response JSON ─────────────────────────────────────────
jsonOut(200, [
    'answer'        => $answer,
    'source'        => $source,
    'model'         => $aiResult['model'] ?? null,
    'session_id'    => $sessionId,
    'latency_ms'    => $latencyMs,
    'quick_replies' => $quickReplies,
]);


/* ══════════════════════════════════════════════════════════
   HELPER FUNCTIONS
══════════════════════════════════════════════════════════ */

/**
 * Filter topik yang dilarang keras (pornografi, kekerasan ekstrem, narkotika)
 * dan filter teknis / coding / jailbreak / data rahasia
 */
function checkSevereOffTopic(string $input): ?string
{
    $n = ' ' . mb_strtolower(trim($input)) . ' ';

    // 1. Filter Konten Berbahaya & Ilegal
    $severePatterns = [
        '/\b(pornografi|bokep|xxx|video dewasa|open bo|judi online|slot gacor|judol)\b/',
        '/\b(narkoba|sabu-sabu|ganja|kokain|heroin|ekstasi)\b/',
        '/\b(cara merakit bom|cara membunuh orang|senjata api ilegal)\b/',
        '/\b(cheat game|hack game|mod apk|diamond gratis)\b/',
    ];

    foreach ($severePatterns as $pattern) {
        if (preg_match($pattern, $n)) {
            return 'Terima kasih telah menghubungi kami. 🙏<br><br>'
                . 'Sebagai asisten Customer Service resmi Paroki SMDTBA Tulungagung, kami hadir khusus untuk membantu hal-hal yang berkaitan dengan <b>kehidupan menggereja, iman Katolik, dan pelayanan informasi paroki</b>.<br><br>'
                . 'Pertanyaan Anda berada di luar cakupan layanan kami. Untuk informasi pelayanan paroki, silakan kunjungi <a href="/kontak"><b>Halaman Kontak</b></a> atau tanyakan seputar jadwal misa dan kegiatan gereja.';
        }
    }

    // 2. Filter Jailbreak, Permintaan Kode/Sistem, API Key, Database, & Non-Church Technical
    $techJailbreakPatterns = [
        '/\b(ignore\s+all\s+previous\s+instructions|system\s+prompt|show\s+prompt|print\s+prompt|reveal\s+prompt)\b/i',
        '/\b(dan\s+mode|developer\s+mode|jailbreak|root\s+access|override\s+rules)\b/i',
        '/\b(api[_\s]?key|secret[_\s]?key|database[_\s]?password|db[_\s]?pass|supabase[_\s]?key|groq[_\s]?key|gemini[_\s]?key)\b/i',
        '/\b(select\s+\*\s+from|drop\s+table|insert\s+into|union\s+select|sql\s+injection)\b/i',
        '/\b(write\s+a\s+python|write\s+a\s+php|write\s+code|bikin\s+kode|buatkan\s+program|buat\s+script|kodingan)\b/i',
        '/\b(function\s*\(|class\s+\w+|import\s+os|system\(|exec\()\b/i',
    ];

    foreach ($techJailbreakPatterns as $pattern) {
        if (preg_match($pattern, $n)) {
            return 'Berkah Dalem. 🙏<br><br>'
                . 'Mohon maaf, sebagai Asisten CS Paroki, kami <b>tidak memiliki akses atau wewenang terhadap hal teknis, koding, maupun konfigurasi sistem</b>.<br><br>'
                . 'Layanan ini khusus diperuntukkan untuk membantu informasi seputar <b>jadwal misa, pelayanan sakramen, kegiatan paroki, dan iman Katolik</b>. Silakan tanyakan hal-hal yang berkaitan dengan pelayanan paroki kami. Terima kasih!';
        }
    }

    return null;
}

/**
 * Rate limiter berbasis session cache
 */
function checkRateLimit(string $sessionId): bool
{
    $file = CACHE_DIR . 'rl_' . md5($sessionId) . '.json';
    $now  = time();
    $data = ['count' => 0, 'window_start' => $now];

    if (file_exists($file)) {
        $data = json_decode(@file_get_contents($file), true) ?? $data;
        if (($now - $data['window_start']) > RATE_LIMIT_WINDOW) {
            $data = ['count' => 0, 'window_start' => $now];
        }
    }

    $data['count']++;
    @file_put_contents($file, json_encode($data), LOCK_EX);
    return $data['count'] <= RATE_LIMIT_MAX;
}

/**
 * Quick replies dinamis sesuai konteks pertanyaan
 */
function buildContextualQuickReplies(string $message): array
{
    $m = mb_strtolower($message);

    if (str_contains($m, 'misa') || str_contains($m, 'jadwal') || str_contains($m, 'jam')) {
        return ['Misa Stasi', 'Misa Hari Raya', 'Pelayanan Sakramen', 'Kontak Sekretariat'];
    }

    if (str_contains($m, 'baptis') || str_contains($m, 'krisma') || str_contains($m, 'nikah') || str_contains($m, 'sakramen')) {
        return ['Syarat Baptis Bayi', 'Persiapan Menikah', 'Komuni Pertama', 'Hubungi Sekretariat'];
    }

    if (str_contains($m, 'romo') || str_contains($m, 'pastor') || str_contains($m, 'pengurus') || str_contains($m, 'dpp')) {
        return ['Profil Romo', 'Pengurus DPP', 'Asisten Imam', 'Wilayah & Lingkungan'];
    }

    if (str_contains($m, 'kategorial') || str_contains($m, 'omk') || str_contains($m, 'legio') || str_contains($m, 'wkri')) {
        return ['Kegiatan OMK', 'Legio Maria', 'WKRI Tulungagung', 'PDKK Santa Maria'];
    }

    return defaultQuickReplies();
}

/**
 * Default Quick Replies
 */
function defaultQuickReplies(): array
{
    return ['Jadwal Misa', 'Pelayanan Sakramen', 'Kegiatan Kategorial', 'Kontak Paroki'];
}

/**
 * Output JSON dan terminasi request
 */
function jsonOut(int $code, array $data): never
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
