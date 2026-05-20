<?php
/**
 * chatbot.php — API endpoint utama chatbot hybrid v2.0
 * POST /chatbot/api/chatbot.php
 *
 * Alur:
 * 1. Validasi & anti-spam
 * 2. Klasifikasi intent: apakah pertanyaan umum atau spesifik paroki?
 * 3. Jika spesifik paroki → cari di KB (faq.json)
 * 4. Jika tidak ketemu di KB → Gemini AI (dengan memory & konteks)
 * 5. Pertanyaan umum pengetahuan → langsung ke Gemini tanpa KB
 * 6. Log & simpan memory
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/memory.php';
require_once __DIR__ . '/logger.php';
require_once __DIR__ . '/gemini.php';
require_once __DIR__ . '/articles.php';

// ─── CORS ────────────────────────────────────────────────
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: ' . ALLOWED_ORIGIN);
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Session-ID');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonOut(405, ['error' => 'Method not allowed']);
}

// ─── Parse input ─────────────────────────────────────────
$input     = json_decode(file_get_contents('php://input'), true) ?? [];
$message   = trim($input['message']    ?? '');
$sessionId = trim($input['session_id'] ?? $_SERVER['HTTP_X_SESSION_ID'] ?? '');
$pageUrl   = trim($input['page_url']   ?? '');
$pageTitle = trim($input['page_title'] ?? '');

// ─── Validasi ────────────────────────────────────────────
if (empty($message)) {
    jsonOut(400, ['error' => 'Message kosong']);
}
if (mb_strlen($message) > MAX_MSG_LENGTH) {
    jsonOut(400, ['error' => 'Pesan terlalu panjang']);
}
if (empty($sessionId)) {
    $sessionId = bin2hex(random_bytes(12));
}

// ─── Anti-spam ───────────────────────────────────────────
if (!checkRateLimit($sessionId)) {
    jsonOut(429, [
        'answer'     => '⚠️ Terlalu banyak pesan dalam waktu singkat. Mohon tunggu sebentar. 🙏',
        'source'     => 'rate_limit',
        'session_id' => $sessionId
    ]);
}

// ─── Memory ──────────────────────────────────────────────
$memory = new ChatMemory($sessionId);
if ($pageUrl) $memory->setPageContext($pageUrl, $pageTitle);

// ─── ROUTING CERDAS ──────────────────────────────────────
//
// Prioritas:
//   0. Topik di luar konteks gereja/rohani  → tolak elegan (tanpa AI)
//   A. Pertanyaan umum rohani/netral        → langsung Gemini
//   B. Pertanyaan spesifik paroki           → cari KB dulu
//   C. KB miss                              → Gemini dengan konteks paroki
//

// ─── 0. Filter topik di luar konteks ─────────────────
$offTopicMessage = getOffTopicMessage($message);
if ($offTopicMessage !== null) {
    $memory->addTurn($message, $offTopicMessage, 'off_topic');
    ChatLogger::log($sessionId, $message, $offTopicMessage, 'off_topic', 0);
    jsonOut(200, [
        'answer'        => $offTopicMessage,
        'source'        => 'off_topic',
        'session_id'    => $sessionId,
        'links'         => [],
        'quick_replies' => defaultQuickReplies(),
    ]);
}

$isGeneralQuestion = isGeneralKnowledgeQuestion($message);

if ($isGeneralQuestion) {
    // ─── A. Pertanyaan umum → langsung Gemini ────────────
    $source = 'gemini';
    $extra  = [
        'links'         => [],
        'quick_replies' => defaultQuickReplies(),
    ];

    $articleText = '';
    if ($pageUrl) {
        $articleText = ArticleReader::fetch($pageUrl) ?? '';
    }

    $geminiResult = GeminiAI::ask(
        userMessage: $message,
        history:     $memory->getGeminiHistory(),
        pageContext: $memory->getPageContext(),
        articleText: $articleText
    );

    $answer = $geminiResult['answer'];
    if ($geminiResult['error']) $source = 'fallback';
    $extra['latency_ms'] = round($geminiResult['latency_ms'] ?? 0);

} else {
    // ─── B. Pertanyaan spesifik → cari KB dulu ───────────
    $kbResult = searchKnowledgeBase($message);

    if ($kbResult !== null) {
        $answer = $kbResult['answer'];
        $source = 'kb';
        $extra  = [
            'links'         => $kbResult['links']        ?? [],
            'quick_replies' => $kbResult['quickReplies'] ?? [],
        ];
    } else {
        // ─── C. KB miss → Gemini ─────────────────────────
        $articleText = '';
        if ($pageUrl) {
            $articleText = ArticleReader::fetch($pageUrl) ?? '';
        }

        $geminiResult = GeminiAI::ask(
            userMessage: $message,
            history:     $memory->getGeminiHistory(),
            pageContext: $memory->getPageContext(),
            articleText: $articleText
        );

        $answer = $geminiResult['answer'];
        $source = $geminiResult['error'] ? 'fallback' : 'gemini';
        $extra  = [
            'links'         => [],
            'quick_replies' => defaultQuickReplies(),
            'latency_ms'    => round($geminiResult['latency_ms'] ?? 0)
        ];
    }
}

// ─── Memory & Log ─────────────────────────────────────────
$memory->addTurn($message, $answer, $source);
ChatLogger::log($sessionId, $message, $answer, $source, $extra['latency_ms'] ?? 0);

// ─── Response ─────────────────────────────────────────────
jsonOut(200, array_merge([
    'answer'     => $answer,
    'source'     => $source,
    'session_id' => $sessionId,
], $extra));


/* ══════════════════════════════════════════════
   HELPERS
══════════════════════════════════════════════ */

/**
 * Deteksi apakah pertanyaan ini adalah pengetahuan umum
 * yang sebaiknya dijawab langsung oleh Gemini tanpa perlu cari KB paroki.
 *
 * Prinsip: jika pertanyaan TIDAK mengandung kata kunci spesifik paroki,
 * dan termasuk kategori umum → langsung ke Gemini.
 */
/**
 * Deteksi apakah pertanyaan berada di luar konteks gereja/rohani/paroki.
 * Jika iya, kembalikan pesan tolak yang elegan. Jika tidak, return null.
 */
function getOffTopicMessage(string $input): ?string {
    $n = mb_strtolower(trim($input));

    // ── Topik yang jelas di luar konteks ──────────────────
    $offTopicPatterns = [
        // Perjudian & taruhan
        '/(judi|taruhan|togel|slot|kasino|casino|poker|bet|betting|jackpot|scatter)/',
        // Matematika & perhitungan murni
        '/^[\d\s\+\-\*\/\(\)\^\.,%=]+$/',
        '/(hitung|kalkulator|kalkulus|integral|turunan|matriks|aljabar|trigonometri|statistik)/',
        '/(berapa hasil|hasil dari|nilai dari)\s+[\d]/',
        // Pembuatan gambar & video
        '/(buatkan gambar|generate gambar|buat foto|generate foto|buat video|generate video|buat logo|buat desain)/',
        '/(image generation|text to image|dall-e|midjourney|stable diffusion)/',
        // Konten dewasa & berbahaya
        '/(pornografi|bokep|xxx|konten dewasa|18\+|seks bebas)/',
        '/(narkoba|sabu|ganja|kokain|heroin|narkotika)/',
        '/(cara membunuh|cara merakit bom|cara meretas|cara hack)/',
        // Game & hiburan tidak relevan
        '/(cheat game|kode cheat|hack game|mod apk|free fire|mobile legend|pubg|valorant|minecraft)/',
        // Keuangan & investasi spekulatif
        '/(crypto|bitcoin|saham|forex|trading|investasi bodong|airdrop|nft)/',
        // Coding & pemrograman
        '/(source code|debug|error code|fungsi php|javascript|python|html css|api key|database query)/',
        // Konten tidak relevan lainnya
        '/(download film|streaming film|nonton film|download lagu|lirik lagu)/',
        '/(resep (minuman keras|alkohol|bir|wine|cocktail))/',
    ];

    foreach ($offTopicPatterns as $pattern) {
        if (preg_match($pattern, $n)) {
            return offTopicReply();
        }
    }

    return null;
}

/**
 * Pesan penolakan elegan & profesional untuk topik di luar konteks.
 */
function offTopicReply(): string {
    return
        'Terima kasih telah menghubungi kami. 🙏<br><br>'
        . 'Sebagai asisten resmi Paroki SMDTBA, kami hadir khusus untuk membantu hal-hal yang berkaitan dengan <b>kehidupan menggereja, iman Katolik, dan informasi seputar paroki</b>.<br><br>'
        . 'Pertanyaan Anda tampaknya berada di luar cakupan layanan kami. Kami mohon maaf tidak dapat membantu untuk hal tersebut.<br><br>'
        . 'Apabila Anda memiliki pertanyaan terkait paroki, jadwal misa, sakramen, atau kegiatan gereja, kami siap membantu sepenuh hati. '
        . 'Untuk keperluan lain, silakan kunjungi <a href="https://www.parokitulungagung.org/kontak" target="_blank"><b>halaman Kontak</b></a> kami.';
}

function isGeneralKnowledgeQuestion(string $input): bool {
    $n = mb_strtolower(trim($input));

    // ── Kata kunci spesifik paroki/gereja lokal → BUKAN umum ──
    $parokiKeywords = [
        'jadwal misa', 'jam misa', 'misa hari', 'misa minggu', 'misa sabtu',
        'smdtba', 'paroki', 'stasi', 'romo', 'pastor', 'sekretariat',
        'galeri', 'album', 'foto', 'gambar', 'dokumentasi',
        'agenda', 'kegiatan paroki', 'acara paroki', 'pengumuman',
        'baptis bayi', 'daftar baptis', 'permohonan baptis',
        'pernikahan di gereja', 'nikah di gereja', 'pemberkatan pernikahan',
        'komuni pertama', 'krisma', 'sakramen penguatan',
        'kontak', 'telepon paroki', 'whatsapp paroki', 'nomor paroki',
        'wilayah', 'lingkungan', 'dpp', 'bgkp',
        'legio maria', 'wkri', 'omk', 'mudika', 'pdkk', 'gim',
        'e-lonceng', 'elonceng', 'buletin',
        'umkm', 'pasar umat', 'komedi',
        'griya pastoral', 'aula paroki', 'gedung paroki',
        'komsos paroki', 'tim komsos',
        'kronik', 'historia', 'artikel paroki',
        'edaran', 'pengumuman paroki',
        'donor darah paroki', 'retret paroki', 'rekoleksi paroki',
        // Sapaan & ucapan yang sudah ada di KB — jangan ke Gemini
        'tuhan memberkati', 'berkah dalem', 'terima kasih', 'makasih',
        'halo', 'hai', 'hello', 'selamat pagi', 'selamat siang',
        'selamat sore', 'selamat malam', 'apa kabar', 'good morning',
    ];

    foreach ($parokiKeywords as $kw) {
        if (str_contains($n, $kw)) return false;
    }

    // ── Pola pertanyaan umum → langsung Gemini ──
    // CATATAN: Sapaan (halo, hai, dll) dan terima kasih TIDAK di sini —
    // biarkan KB yang menjawab lebih hemat token.
    $generalPatterns = [
        // Pertanyaan "apa itu", "siapa itu", "bagaimana", "mengapa"
        '/^(apa itu|apa arti|apa yang dimaksud|apakah|apa beda|apa perbedaan)/',
        '/^(siapa itu|siapa yang|siapa dia|siapakah)/',
        '/^(bagaimana cara|bagaimana agar|bagaimana supaya|bagaimana bisa|bagaimana)/',
        '/^(mengapa|kenapa|knp|knpa|why)/',
        '/^(kapan|sejak kapan|sudah berapa lama)(?!.*(paroki|misa jadwal|stasi))/',
        '/^(di mana|dimana)(?!.*(paroki|gereja|sekretariat|stasi))/',
        '/^(berapa)(?!.*(biaya sakramen|harga tiket smdtba))/',
        '/^(tolong|mohon|bisa|boleh|bantu|jelaskan|ceritakan|terangkan)/',
        '/^(cerita|kisah|sejarah|asal usul)(?!.*(paroki|smdtba))/',
        // Topik keagamaan umum
        '/\b(sakramen|sacrament)\b/',
        '/\b(kitab suci|alkitab|bible|injil|perjanjian (lama|baru))\b/',
        '/\b(doa|prayer|novena|rosario|rosary)\b(?!.*(jadwal|kapan|paroki))/',
        '/\b(puasa|pantang|lent|adven|advent)\b(?!.*(jadwal|kapan))/',
        '/\b(surga|neraka|purgatori|keselamatan|dosa|pengampunan)\b/',
        '/\b(yesus|kristus|allah|tuhan|roh kudus|trinitas|tritunggal)\b/',
        '/\b(bunda maria|maria|santa|santo|orang kudus|heilige)\b/',
        '/\b(paus|vatikan|konsili|gereja universal|ekumene)\b/',
        '/\b(protestanisme|islam|buddha|agama|iman|kepercayaan)\b/',
        '/\b(saat teduh|renungan|refleksi|meditasi|kontemplasi)\b/',
        // Topik umum non-agama
        '/\b(cuaca|weather|iklim|suhu|hujan|panas)\b/',
        '/\b(berita|news|internasional|nasional|dunia)\b(?!.*(paroki|gereja lokal))/',
        '/\b(teknologi|internet|hp|komputer|ai|kecerdasan buatan)\b/',
        '/\b(sejarah (indonesia|dunia|indonesia|jawa|romawi|yunani))\b/',
        '/\b(resep|masakan|makanan|minuman|kuliner)\b/',
        '/\b(olahraga|sport|sepakbola|basket|renang)\b/',
        '/\b(musik|lagu|film|buku|novel|seni)\b/',
        '/\b(matematika|fisika|kimia|biologi|ilmu)\b/',
        '/\b(kesehatan|dokter|obat|penyakit|gejala)\b(?!.*(pengurapan|minyak suci))/',
        '/\b(psikologi|emosi|mental|stress|depresi|cemas)\b/',
        '/\b(keuangan|investasi|nabung|tabungan|ekonomi)\b/',
        '/\b(perjalanan|wisata|travel|liburan|destinasi)\b(?!.*(ziarah paroki|stasi))/',
        '/\b(tips|cara|tutorial|panduan|langkah)\b(?!.*(baptis|nikah|surat paroki))/',
        '/\b(motivasi|inspirasi|kata bijak|quote)\b/',
        '/\b(arti|makna|filosofi|definisi)\b/',
    ];

    foreach ($generalPatterns as $pattern) {
        if (preg_match($pattern, $n)) return true;
    }

    // ── Pertanyaan sangat pendek (1-2 kata) tanpa konteks paroki → Gemini ──
    $words = explode(' ', trim($n));
    if (count($words) <= 2 && !in_array($n, [
        'misa', 'jadwal', 'baptis', 'krisma', 'romo', 'paroki',
        'galeri', 'foto', 'agenda', 'stasi', 'kontak', 'sekretariat',
        'dpp', 'bgkp', 'kronik', 'historia', 'edaran', 'lonceng'
    ])) {
        // Kalau kata tunggal/pendek bukan keyword paroki → Gemini
        // Tapi kita tetap biarkan KB juga mencoba (return false)
        // sehingga kalau KB miss, Gemini yang jawab
        return false;
    }

    return false;
}

/** Knowledge Base search */
function searchKnowledgeBase(string $input): ?array {
    $faqResult = searchFaqJson($input);
    if ($faqResult) return $faqResult;
    return null;
}

/** Cari di faq.json — Smart Matching dengan scoring */
function searchFaqJson(string $input): ?array {
    if (!file_exists(FAQ_FILE)) return null;

    static $faq = null;
    if ($faq === null) {
        $faq = json_decode(file_get_contents(FAQ_FILE), true) ?? [];
    }

    $normalized = normalizeText($input);
    $inputWords = array_filter(explode(' ', $normalized));
    $inputLen   = mb_strlen($normalized);

    $bestEntry = null;
    $bestScore = -1;

    foreach ($faq as $entry) {
        if (empty($entry['patterns'])) continue;
        if ($entry['patterns'][0] === '__fallback__') continue;

        foreach ($entry['patterns'] as $pattern) {

            // ── Regex pattern ──
            if (str_starts_with($pattern, '/')) {
                if (@preg_match($pattern . 'i', $normalized)) {
                    return $entry;
                }
                continue;
            }

            $p    = normalizeText($pattern);
            $pLen = mb_strlen($p);
            if ($pLen === 0) continue;

            $score = matchScore($normalized, $p, $inputWords, $inputLen, $pLen);

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestEntry = $entry;
            }
        }
    }

    // Threshold 60 — hanya return jika cukup yakin
    return ($bestScore >= 60) ? $bestEntry : null;
}

/**
 * Hitung skor kecocokan antara input dan pattern.
 */
function matchScore(
    string $normalized,
    string $p,
    array  $inputWords,
    int    $inputLen,
    int    $pLen
): int {

    if ($normalized === $p) return 100;

    if (!str_contains($normalized, $p)) return 0;

    if ($pLen <= 4) {
        if (!preg_match('/(?<![a-z0-9])' . preg_quote($p, '/') . '(?![a-z0-9])/', $normalized)) {
            return 0;
        }
        if ($pLen <= 2) return ($normalized === $p) ? 100 : 0;
    }

    $pWords = explode(' ', $p);
    if (count($pWords) >= 2) {
        foreach ($pWords as $pw) {
            if (!str_contains($normalized, $pw)) return 0;
        }
    }

    $coverage = $pLen / max($inputLen, 1);
    $score    = 60;
    $score   += (int)($coverage * 30);

    if (str_starts_with($normalized, $p)) $score += 10;
    if ($normalized === $p)               $score  = 100;

    if ($pLen < ($inputLen * 0.4) && $pLen <= 8) $score -= 20;

    return min(100, max(0, $score));
}

/** Normalkan teks */
function normalizeText(string $str): string {
    $str = mb_strtolower($str);
    $str = strtr($str, [
        'à'=>'a','á'=>'a','â'=>'a','ã'=>'a','ä'=>'a','å'=>'a',
        'è'=>'e','é'=>'e','ê'=>'e','ë'=>'e',
        'ì'=>'i','í'=>'i','î'=>'i','ï'=>'i',
        'ò'=>'o','ó'=>'o','ô'=>'o','õ'=>'o','ö'=>'o',
        'ù'=>'u','ú'=>'u','û'=>'u','ü'=>'u',
    ]);
    $str = preg_replace('/[^a-z0-9\s]/u', ' ', $str);
    return trim(preg_replace('/\s+/', ' ', $str));
}

/** Rate limiter berbasis file */
function checkRateLimit(string $sessionId): bool {
    $file = CACHE_DIR . 'rl_' . md5($sessionId) . '.json';
    $now  = time();
    $data = ['count' => 0, 'window_start' => $now];

    if (file_exists($file)) {
        $data = json_decode(file_get_contents($file), true) ?? $data;
        if (($now - $data['window_start']) > RATE_LIMIT_WINDOW) {
            $data = ['count' => 0, 'window_start' => $now];
        }
    }

    $data['count']++;
    file_put_contents($file, json_encode($data), LOCK_EX);
    return $data['count'] <= RATE_LIMIT_MAX;
}

/** Quick replies default */
function defaultQuickReplies(): array {
    return ['Jadwal Misa', 'Kontak Paroki', 'Agenda Paroki', 'Galeri Foto'];
}

/** Output JSON dan exit */
function jsonOut(int $code, array $data): never {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
