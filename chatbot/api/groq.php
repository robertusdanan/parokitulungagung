<?php
/**
 * groq.php — Groq AI Backup dengan Multi-Key Rotation
 * Versi: 2.0 — Rotasi API key otomatis saat rate limit / quota habis
 */

require_once __DIR__ . '/config.php';

class GroqAI
{
    /**
     * Kirim pertanyaan ke Groq (format OpenAI-compatible)
     */
    public static function ask(
        string $userMessage,
        array  $history     = [],
        string $pageContext = '',
        string $articleText = ''
    ): array {

        $start = microtime(true);

        $systemPrompt = self::buildSystemPrompt($pageContext, $articleText);
        $messages     = self::buildMessages($systemPrompt, $history, $userMessage);
        $response     = self::callWithKeyAndModelFallback($messages);

        $latencyMs = (microtime(true) - $start) * 1000;

        if (!$response || empty($response['text'])) {
            return [
                'answer'     => self::fallbackMessage(),
                'latency_ms' => round($latencyMs, 2),
                'error'      => true,
                'model'      => null,
                'provider'   => 'groq',
            ];
        }

        return [
            'answer'     => $response['text'],
            'latency_ms' => round($latencyMs, 2),
            'error'      => false,
            'model'      => $response['model'],
            'key_index'  => $response['key_index'],
            'provider'   => 'groq',
        ];
    }

    // ════════════════════════════════════════════════════════
    // ROTASI KEY + MODEL
    // Urutan: key[0]/model[0] → key[0]/model[1] → ... →
    //         key[1]/model[0] → key[1]/model[1] → ...
    // ════════════════════════════════════════════════════════
    private static function callWithKeyAndModelFallback(array $messages): ?array
    {
        $keys   = array_values(GROQ_API_KEYS);
        $models = GROQ_MODELS;

        if (empty($keys)) {
            if (DEBUG_MODE) error_log('[Groq] Tidak ada API key tersedia.');
            return null;
        }

        $startKeyIdx = self::readKeyIndex(count($keys));

        for ($ki = 0; $ki < count($keys); $ki++) {
            $keyIdx = ($startKeyIdx + $ki) % count($keys);
            $key    = $keys[$keyIdx];

            foreach ($models as $model) {
                $result = self::callApi($model, $key, $messages);

                if ($result === 'RATE_LIMIT') {
                    // Key ini kena rate limit — pindah key berikutnya
                    if (DEBUG_MODE) {
                        error_log("[Groq] Key #{$keyIdx} kena rate limit, pindah key.");
                    }
                    break; // keluar dari loop model
                }

                if ($result !== null) {
                    // Berhasil — simpan indeks key ini
                    self::writeKeyIndex($keyIdx);
                    return array_merge($result, ['key_index' => $keyIdx]);
                }

                if (DEBUG_MODE) {
                    error_log("[Groq] Key #{$keyIdx} model={$model} gagal, coba model berikutnya.");
                }
            }
        }

        return null; // semua key & model habis
    }

    // ════════════════════════════════════════════════════════
    // CALL API — return 'RATE_LIMIT' | array | null
    // ════════════════════════════════════════════════════════
    private static function callApi(string $model, string $apiKey, array $messages): mixed
    {
        $payload = [
            'model'       => $model,
            'messages'    => $messages,
            'temperature' => GROQ_TEMPERATURE,
            'max_tokens'  => GROQ_MAX_OUTPUT_TOKENS,
        ];

        $ch = curl_init(GROQ_ENDPOINT);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_TIMEOUT        => GROQ_TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
                'User-Agent: SMDTBA-Chatbot/3.0',
            ],
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $raw  = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if (DEBUG_MODE) {
            error_log("[Groq] model={$model} HTTP={$code} ERR={$err}");
        }

        if ($code === 429) return 'RATE_LIMIT'; // tandai khusus
        if ($code !== 200 || !$raw) return null;

        $data = json_decode($raw, true);
        $text = trim($data['choices'][0]['message']['content'] ?? '');

        if (empty($text)) return null;

        return ['text' => $text, 'model' => $model];
    }

    // ════════════════════════════════════════════════════════
    // CACHE INDEKS KEY
    // ════════════════════════════════════════════════════════
    private static function cacheFile(): string
    {
        return (defined('CACHE_DIR') ? CACHE_DIR : sys_get_temp_dir() . '/')
            . 'groq_key_idx.json';
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
    // BUILD MESSAGES
    // ════════════════════════════════════════════════════════
    private static function buildMessages(
        string $systemPrompt,
        array  $history,
        string $userMessage
    ): array {

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
        ];

        // Konversi history dari format Gemini ke format OpenAI
        $recentHistory = array_slice($history, -3);
        foreach ($recentHistory as $turn) {
            $role    = ($turn['role'] === 'model') ? 'assistant' : 'user';
            $content = $turn['parts'][0]['text'] ?? '';
            if ($content) {
                $messages[] = ['role' => $role, 'content' => $content];
            }
        }

        $messages[] = ['role' => 'user', 'content' => $userMessage];

        return $messages;
    }

    // ════════════════════════════════════════════════════════
    // SYSTEM PROMPT — sama dengan Gemini agar konsisten
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
   → Gunakan data paroki yang ada di bawah.
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
    private static function fallbackMessage(): string
    {
        return
            'Mohon maaf, asisten kami sedang tidak dapat merespons saat ini. 🙏<br><br>'
            . 'Untuk informasi lebih lanjut, silakan kunjungi '
            . '<a href="https://www.parokitulungagung.org/kontak" target="_blank"><b>halaman Kontak</b></a> '
            . 'kami — Pengurus Gereja siap membantu Anda.';
    }
}