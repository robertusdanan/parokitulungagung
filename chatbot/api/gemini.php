<?php
/**
 * gemini.php — Hybrid Gemini AI with Auto Model Fallback
 * Versi: 2.0 — Lebih cerdas, konteks lebih luas, routing lebih tepat
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/articles.php';
require_once __DIR__ . '/groq.php';

class GeminiAI {

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

        $payload = self::buildPayload($systemPrompt, $history, $userMessage);

        $response = self::callWithFallback($payload);

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
                    'provider'   => 'gemini'
                ];
            }
        }

        // ── Gemini gagal → coba Groq sebagai backup ──
        if (DEBUG_MODE) {
            error_log('Gemini gagal, beralih ke Groq backup...');
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

        // ── Semua AI gagal → pesan fallback ──
        return [
            'answer'     => self::fallbackMessage(),
            'latency_ms' => round((microtime(true) - $start) * 1000, 2),
            'error'      => true,
            'model'      => null,
            'provider'   => 'none'
        ];
    }

    /**
     * SYSTEM PROMPT — diperluas dan diperjelas
     */
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
   → Contoh yang harus dijawab langsung: "apa itu sakramen?", "siapa Bunda Maria?",
     "apa arti Paskah?", "bagaimana cara doa rosario?", "apa beda Katolik dan Protestan?",
     "mengapa Yesus disalib?", "apa itu Adven?", "siapa Santo Petrus?",
     "bagaimana cara membaca Kitab Suci?", "cuaca hari ini di mana saja", pertanyaan
     tentang Indonesia, sejarah, sains, teknologi, dll.

2. PERTANYAAN SPESIFIK PAROKI (jadwal, kontak, kegiatan, nama orang):
   → Gunakan data yang sudah ada di KB (knowledge base).
   → Jika tidak tahu jadwal misa persis: katakan jujur dan arahkan ke /jadwal-misa.
   → Jika tidak tahu kegiatan/petugas: arahkan ke /agenda.

3. PERTANYAAN GALERI/FOTO (tanpa spesifik album):
   → Arahkan ke /galeri dan sarankan kata kunci pencarian.
   → JANGAN tanya balik berulang kali jika sudah jelas konteksnya.

4. TIDAK TAHU / TIDAK YAKIN:
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

HALAMAN PENTING — PENTING, PAHAMI PERBEDAANNYA:
• Jadwal Misa: /jadwal-misa
  → KHUSUS untuk jadwal misa harian & mingguan di gereja pusat dan stasi.
  → Arahkan ke sini jika ditanya: "jam misa", "jadwal misa", "misa hari apa".

• Agenda: /agenda
  → BUKAN halaman jadwal misa.
  → Berisi: jadwal petugas liturgi, jadwal kegiatan/hari besar khusus, dan download dokumen.
  → Arahkan ke sini jika ditanya: "jadwal petugas", "kegiatan bulan ini", "download dokumen paroki".
  → JANGAN arahkan ke /agenda untuk pertanyaan tentang jam atau jadwal misa.

• Galeri foto: /galeri
• Kontak: /kontak
• Artikel berita: /artikel/berita
• Kronik: /artikel/kronik
• Pasar Umat (UMKM): /pasar-umat

Waktu sekarang: {$now}

═══════════════════════════════════════
BATAS TOPIK — WAJIB DIPATUHI
═══════════════════════════════════════
Kamu HANYA boleh membantu topik berikut:
• Iman Katolik, Kitab Suci, sakramen, doa, liturgi, spiritualitas
• Informasi paroki, jadwal misa, kegiatan gereja, pengumuman
• Topik umum yang netral dan relevan dengan kehidupan umat (kesehatan umum, keluarga, dll)

Kamu TIDAK BOLEH merespons topik berikut. Jika ditanya, tolak dengan sopan:
• Perjudian, taruhan, togel, kasino
• Perhitungan matematika murni, kalkulator, coding/pemrograman
• Pembuatan gambar, video, atau konten multimedia
• Konten dewasa, kekerasan, narkoba
• Game, hiburan tidak relevan, download film/lagu
• Crypto, saham, forex, investasi spekulatif
• Permintaan apapun yang tidak berkaitan dengan iman, gereja, atau kehidupan rohani

Saat menolak, gunakan kalimat yang hangat, profesional, dan arahkan ke halaman Kontak:
<a href="https://www.parokitulungagung.org/kontak" target="_blank"><b>halaman Kontak</b></a>

═══════════════════════════════════════
ATURAN KONTAK — WAJIB DIPATUHI
═══════════════════════════════════════
JANGAN PERNAH menampilkan nomor WhatsApp atau nomor telepon secara langsung dalam jawaban.
Nomor-nomor tersebut hanya untuk referensi internal kamu, BUKAN untuk ditampilkan ke pengguna.

Jika perlu mengarahkan pengguna untuk menghubungi paroki, SELALU gunakan format ini:
"Silakan kunjungi <a href="https://www.parokitulungagung.org/kontak" target="_blank"><b>halaman Kontak</b></a> kami untuk informasi lebih lanjut."

JANGAN gunakan kalimat seperti:
• "hubungi via WhatsApp di +62..."
• "sekretariat dapat dihubungi di 0356..."
• "staf sekretariat", "petugas sekretariat", atau jabatan internal lainnya — gunakan "Pengurus Gereja"
• "Staf Sekretariat" → gunakan "Pengurus Gereja"

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

    /**
     * BUILD PAYLOAD
     */
    private static function buildPayload(
        string $systemPrompt,
        array  $history,
        string $userMessage
    ): array {

        $contents = [];

        // Ambil 3 turn terakhir untuk konteks percakapan yang lebih baik
        $recentHistory = array_slice($history, -3);

        foreach ($recentHistory as $turn) {
            $contents[] = $turn;
        }

        $contents[] = [
            'role'  => 'user',
            'parts' => [['text' => $userMessage]]
        ];

        return [
            'system_instruction' => [
                'parts' => [['text' => $systemPrompt]]
            ],
            'contents' => $contents,
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
            ]
        ];
    }

    /**
     * AUTO FALLBACK MODEL
     */
    private static function callWithFallback(array $payload): ?array {
        foreach (GEMINI_MODELS as $model) {
            $endpoint = gemini_endpoint($model);
            $response = self::callApi($endpoint, $payload);
            if ($response && !empty($response['candidates'][0]['content']['parts'][0]['text'])) {
                return ['response' => $response, 'model' => $model];
            }
        }
        return null;
    }

    /**
     * CALL GEMINI API
     */
    private static function callApi(string $endpoint, array $payload): ?array {
        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_TIMEOUT        => GEMINI_TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'User-Agent: SMDTBA-Chatbot/2.0'
            ],
            CURLOPT_SSL_VERIFYPEER => false
        ]);
        $raw  = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if (DEBUG_MODE) {
            error_log('Gemini HTTP=' . $code . ' ERR=' . $err);
        }

        if ($code == 429 || $code !== 200 || !$raw) return null;

        return json_decode($raw, true);
    }

    /**
     * EXTRACT TEXT
     */
    private static function extractText(array $response): string {
        return trim($response['candidates'][0]['content']['parts'][0]['text'] ?? '');
    }

    /**
     * FALLBACK MESSAGE — saat Gemini tidak bisa dihubungi
     */
    public static function fallbackMessage(): string {
        return
            'Mohon maaf, asisten kami sedang tidak dapat merespons saat ini. 🙏<br><br>'
            . 'Untuk informasi lebih lanjut, silakan kunjungi '
            . '<a href="https://www.parokitulungagung.org/kontak" target="_blank"><b>halaman Kontak</b></a> '
            . 'kami — tim Sekretariat Paroki siap membantu Anda.';
    }
}
