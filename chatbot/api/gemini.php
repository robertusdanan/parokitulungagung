<?php
/**
 * gemini.php — Hybrid Gemini AI with Multi-Key Rotation + Auto Model Fallback
 * Versi: 3.0 — Rotasi API key otomatis saat rate limit / quota habis
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/articles.php';
require_once __DIR__ . '/groq.php';

class GeminiAI
{
    /**
     * Kirim pertanyaan ke Gemini
     */
    public static function ask(
        string $userMessage,
        array  $history     = [],
        string $pageContext = '',
        string $articleText = ''
    ): array {

        $start = microtime(true);

        $systemPrompt = self::buildSystemPrompt($pageContext, $articleText);
        $payload      = self::buildPayload($systemPrompt, $history, $userMessage);
        $response     = self::callWithKeyAndModelFallback($payload);

        $latencyMs = (microtime(true) - $start) * 1000;

        // ── Gemini berhasil ──
        if ($response && !empty($response['response'])) {
            $text = self::extractText($response['response']);
            if ($text) {
                return [
                    'answer'     => $text,
                    'latency_ms' => round($latencyMs, 2),
                    'error'      => false,
                    'model'      => $response['model'],
                    'key_index'  => $response['key_index'],
                    'provider'   => 'gemini',
                ];
            }
        }

        // ── Gemini gagal → backup ke Groq ──
        if (DEBUG_MODE) {
            error_log('[Gemini] Semua key & model gagal, beralih ke Groq...');
        }

        $groqResult = GroqAI::ask(
            userMessage: $userMessage,
            history:     $history,
            pageContext: $pageContext,
            articleText: $articleText
        );

        if (!$groqResult['error']) {
            return $groqResult;
        }

        // ── Semua AI gagal ──
        return [
            'answer'     => self::fallbackMessage(),
            'latency_ms' => round((microtime(true) - $start) * 1000, 2),
            'error'      => true,
            'model'      => null,
            'provider'   => 'none',
        ];
    }

    // ════════════════════════════════════════════════════════
    // ROTASI KEY + MODEL
    // Urutan: key[0]/model[0] → key[0]/model[1] → ... →
    //         key[1]/model[0] → key[1]/model[1] → ...
    // ════════════════════════════════════════════════════════
    private static function callWithKeyAndModelFallback(array $payload): ?array
    {
        $keys   = array_values(GEMINI_API_KEYS);
        $models = GEMINI_MODELS;

        if (empty($keys)) {
            if (DEBUG_MODE) error_log('[Gemini] Tidak ada API key tersedia.');
            return null;
        }

        // Baca indeks key terakhir yang berhasil dari cache
        $startKeyIdx = self::readKeyIndex(count($keys));

        for ($ki = 0; $ki < count($keys); $ki++) {
            $keyIdx = ($startKeyIdx + $ki) % count($keys);
            $key    = $keys[$keyIdx];

            foreach ($models as $model) {
                $endpoint = gemini_endpoint($model, $key);
                $response = self::callApi($endpoint, $payload);

                if ($response === 'RATE_LIMIT') {
                    // Key ini kena rate limit — langsung pindah key berikutnya
                    if (DEBUG_MODE) {
                        error_log("[Gemini] Key #{$keyIdx} kena rate limit, pindah key.");
                    }
                    break; // keluar dari loop model, lanjut key berikutnya
                }

                if ($response && !empty($response['candidates'][0]['content']['parts'][0]['text'])) {
                    // Berhasil — simpan indeks key ini
                    self::writeKeyIndex($keyIdx);
                    return [
                        'response'  => $response,
                        'model'     => $model,
                        'key_index' => $keyIdx,
                    ];
                }

                // Model ini gagal (bukan rate limit) — coba model berikutnya dengan key sama
                if (DEBUG_MODE) {
                    error_log("[Gemini] Key #{$keyIdx} model={$model} gagal, coba model berikutnya.");
                }
            }
        }

        return null; // semua key & model habis
    }

    // ════════════════════════════════════════════════════════
    // CALL API — return 'RATE_LIMIT' | array | null
    // ════════════════════════════════════════════════════════
    private static function callApi(string $endpoint, array $payload): mixed
    {
        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_TIMEOUT        => GEMINI_TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'User-Agent: SMDTBA-Chatbot/3.0',
            ],
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $raw  = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if (DEBUG_MODE) {
            error_log("[Gemini] HTTP={$code} ERR={$err}");
        }

        if ($code === 429) return 'RATE_LIMIT'; // tandai khusus
        if ($code !== 200 || !$raw) return null;

        return json_decode($raw, true);
    }

    // ════════════════════════════════════════════════════════
    // CACHE INDEKS KEY
    // ════════════════════════════════════════════════════════
    private static function cacheFile(): string
    {
        return (defined('CACHE_DIR') ? CACHE_DIR : sys_get_temp_dir() . '/')
            . 'gemini_key_idx.json';
    }

    private static function readKeyIndex(int $total): int
    {
        $file = self::cacheFile();
        if (!file_exists($file)) return 0;
        $data = json_decode(@file_get_contents($file), true);
        $idx  = (int)($data['idx'] ?? 0);
        return ($idx < $total) ? $idx : 0;
    }

    private static function writeKeyIndex(int $idx): void
    {
        @file_put_contents(self::cacheFile(), json_encode(['idx' => $idx]), LOCK_EX);
    }

    // ════════════════════════════════════════════════════════
    // BUILD PAYLOAD
    // ════════════════════════════════════════════════════════
    private static function buildPayload(
        string $systemPrompt,
        array  $history,
        string $userMessage
    ): array {

        $contents = [];

        $recentHistory = array_slice($history, -3);
        foreach ($recentHistory as $turn) {
            $contents[] = $turn;
        }

        $contents[] = [
            'role'  => 'user',
            'parts' => [['text' => $userMessage]],
        ];

        return [
            'system_instruction' => [
                'parts' => [['text' => $systemPrompt]],
            ],
            'contents'       => $contents,
            'generationConfig' => [
                'temperature'     => GEMINI_TEMPERATURE,
                'topP'            => 0.85,
                'maxOutputTokens' => GEMINI_MAX_OUTPUT_TOKENS,
            ],
            'safetySettings' => [
                ['category' => 'HARM_CATEGORY_HARASSMENT',        'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
                ['category' => 'HARM_CATEGORY_HATE_SPEECH',       'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
                ['category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
                ['category' => 'HARM_CATEGORY_DANGEROUS_CONTENT', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
            ],
        ];
    }

    // ════════════════════════════════════════════════════════
    // SYSTEM PROMPT
    // ════════════════════════════════════════════════════════
    private static function buildSystemPrompt(
        string $pageContext,
        string $articleText
    ): string {

        $now  = date('l, d F Y, H:i') . ' WIB';
        $site = SITE_NAME;

        $prompt = <<<PROMPT
Kamu adalah **Asisten Paroki SMDTBA** — asisten virtual cerdas dan ramah dari Gereja Katolik {$site}, Tulungagung, Jawa Timur.

═══════════════════════════════════════
KEPRIBADIAN
═══════════════════════════════════════
• Hangat, sopan, natural — seperti berbicara dengan teman yang berpengetahuan
• Bahasa Indonesia yang baik dan mudah dipahami
• Empati dan penuh perhatian, terutama untuk pertanyaan spiritual

═══════════════════════════════════════
FORMAT JAWABAN
═══════════════════════════════════════
• Gunakan HTML sederhana: <b>, <br>, <a href="...">
• Bullet dengan karakter • (bukan markdown)
• JANGAN gunakan markdown: **, ##, __, *, dll
• Maksimal 120 kata — padat, jelas, tidak bertele-tele
• Untuk pertanyaan singkat/sapaan: jawab singkat 1-2 kalimat

═══════════════════════════════════════
CARA MENJAWAB — PRIORITAS
═══════════════════════════════════════

1. PERTANYAAN UMUM PENGETAHUAN (agama, sains, sejarah, budaya, dll):
   → Jawab langsung dengan pengetahuanmu. Kamu BOLEH dan HARUS menjawab.

2. PERTANYAAN SPESIFIK PAROKI (jadwal, kontak, kegiatan, nama orang):
   → Gunakan data yang sudah ada di KB (knowledge base).
   → Jika tidak tahu jadwal misa persis: katakan jujur dan arahkan ke /jadwal-misa.
   → Jika tidak tahu kegiatan/petugas: arahkan ke /agenda.

3. TIDAK TAHU / TIDAK YAKIN:
   → Jujur katakan tidak tahu, tapi tetap bantu dengan saran.
   → Arahkan ke halaman <a href="https://www.parokitulungagung.org/kontak" target="_blank"><b>Kontak</b></a>.

═══════════════════════════════════════
INFO PAROKI SMDTBA
═══════════════════════════════════════
Nama Lengkap: {$site}
Alamat: Jl. Ahmad Yani Tim. Gg. IV No.1, Bago, Tulungagung 66224
Telepon Kantor: (0355) 321727
WhatsApp Sekretariat: +62 856-3678-844
WhatsApp Komsos: +62 851-8306-8895
Website: paroki-smdtba.or.id

Romo Paroki: RD Thomas Aquino Djoko Noegroho
Romo Rekan: RD Yohanes "Jose" Setyawan
Keuskupan: Keuskupan Surabaya

JADWAL MISA HARIAN (Gereja Pusat):
• Senin–Sabtu: 05.30 WIB
• Senin & Jumat: 17.00 WIB
• Sabtu Sore: 17.00 WIB (Minggu)
• Minggu: 06.00, 08.00, 17.00 WIB

JAM SEKRETARIAT:
• Senin, Selasa, Kamis, Jumat: 08.00–14.00 WIB
• Rabu: Libur

HALAMAN PENTING:
• Jadwal Misa: /jadwal-misa
• Agenda: /agenda
• Galeri foto: /galeri
• Kontak: /kontak
• Artikel berita: /artikel/berita
• Kronik: /artikel/kronik
• Pasar Umat (UMKM): /pasar-umat
• TV Digital Indonesia (live streaming): /tvdigital
• Baby Keyboard (mainan edukatif anak): /babykeyboard


═══════════════════════════════════════
HALAMAN KHUSUS — FITUR WEBSITE
═══════════════════════════════════════
Website Paroki SMDTBA juga memiliki halaman fitur interaktif berikut:

1. TV DIGITAL INDONESIA (/tvdigital)
   Nonton siaran langsung TV digital Indonesia gratis tanpa aplikasi.
   Channel tersedia: RCTI, MNCTV, GTV, Trans7, Trans TV, Indosiar, SCTV,
   iNews, CNN Indonesia, CNBC Indonesia.
   Cocok untuk: umat yang ingin menonton siaran langsung dari gereja atau rumah.
   Kata kunci pemicu: "tv", "nonton", "live streaming", "siaran langsung",
   "rcti", "sctv", "trans7", "transtv", "mnctv", "gtv", "indosiar",
   "inews", "cnn indonesia", "cnbc", "tv digital".
   Respons yang benar: Berikan pengantar singkat bahwa website punya halaman
   nonton TV digital gratis, lalu arahkan ke
   <a href="/tvdigital"><b>halaman TV Digital</b></a>.

2. BABY KEYBOARD (/babykeyboard)
   Mainan keyboard interaktif berbasis web untuk bayi dan anak kecil.
   Setiap tombol keyboard menghasilkan suara, warna, dan animasi menyenangkan.
   Cocok untuk: orang tua yang mencari hiburan edukatif untuk bayi/balita.
   Kata kunci pemicu: "baby keyboard", "mainan bayi", "mainan anak",
   "keyboard anak", "hiburan bayi", "permainan bayi", "balita", "anak kecil".
   Respons yang benar: Berikan pengantar bahwa ada halaman mainan keyboard
   interaktif untuk bayi, lalu arahkan ke
   <a href="/babykeyboard"><b>halaman Baby Keyboard</b></a>.

ATURAN UNTUK HALAMAN FITUR:
• Jika pengguna bertanya tentang nonton TV / siaran langsung → arahkan ke /tvdigital
• Jika pengguna bertanya tentang mainan anak / baby keyboard → arahkan ke /babykeyboard
• Selalu beri pengantar 1-2 kalimat sebelum link, jangan langsung lempar link


Waktu sekarang: {$now}

═══════════════════════════════════════
BATAS TOPIK — WAJIB DIPATUHI
═══════════════════════════════════════
Kamu HANYA boleh membantu topik berikut:
• Iman Katolik, Kitab Suci, sakramen, doa, liturgi, spiritualitas
• Informasi paroki, jadwal misa, kegiatan gereja, pengumuman
• Topik umum yang netral dan relevan dengan kehidupan umat

Kamu TIDAK BOLEH merespons: perjudian, crypto, konten dewasa, game tidak relevan, dll.

═══════════════════════════════════════
ATURAN KONTAK — WAJIB DIPATUHI
═══════════════════════════════════════
JANGAN PERNAH menampilkan nomor WhatsApp atau telepon secara langsung.
Selalu gunakan: "Silakan kunjungi <a href="https://www.parokitulungagung.org/kontak" target="_blank"><b>halaman Kontak</b></a> kami."

PROMPT;

        if ($pageContext) {
            $prompt .= "\n\n═══════════════════════════════════════\nHALAMAN AKTIF USER\n═══════════════════════════════════════\n" . $pageContext;
        }

        if ($articleText) {
            $excerpt = mb_substr($articleText, 0, 500);
            $prompt .= "\n\n═══════════════════════════════════════\nKONTEKS ARTIKEL\n═══════════════════════════════════════\n" . $excerpt;
        }

        return $prompt;
    }

    // ════════════════════════════════════════════════════════
    // HELPERS
    // ════════════════════════════════════════════════════════
    private static function extractText(array $response): string
    {
        return trim($response['candidates'][0]['content']['parts'][0]['text'] ?? '');
    }

    public static function fallbackMessage(): string
    {
        return
            'Mohon maaf, asisten kami sedang tidak dapat merespons saat ini. 🙏<br><br>'
            . 'Untuk informasi lebih lanjut, silakan kunjungi '
            . '<a href="https://www.parokitulungagung.org/kontak" target="_blank"><b>halaman Kontak</b></a> '
            . 'kami — Pengurus Gereja siap membantu Anda.';
    }
}