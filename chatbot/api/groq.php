<?php
/**
 * groq.php — Groq AI Backup
 * Digunakan otomatis saat Gemini tidak tersedia (token habis / rate limit / error)
 * Versi: 1.0
 */

require_once __DIR__ . '/config.php';

class GroqAI {

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

        $messages = self::buildMessages($systemPrompt, $history, $userMessage);

        $response = self::callWithFallback($messages);

        $latencyMs = (microtime(true) - $start) * 1000;

        if (!$response || empty($response['text'])) {
            return [
                'answer'     => GeminiAI::fallbackMessage(),
                'latency_ms' => round($latencyMs, 2),
                'error'      => true,
                'model'      => null,
                'provider'   => 'groq'
            ];
        }

        return [
            'answer'     => $response['text'],
            'latency_ms' => round($latencyMs, 2),
            'error'      => false,
            'model'      => $response['model'],
            'provider'   => 'groq'
        ];
    }

    /**
     * SYSTEM PROMPT — sama persis dengan Gemini agar konsisten
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
     * Build messages dalam format OpenAI-compatible (yang digunakan Groq)
     */
    private static function buildMessages(
        string $systemPrompt,
        array  $history,
        string $userMessage
    ): array {

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt]
        ];

        // Konversi history dari format Gemini ke format OpenAI
        $recentHistory = array_slice($history, -3);
        foreach ($recentHistory as $turn) {
            // History Gemini: ['role' => 'user'/'model', 'parts' => [['text' => '...']]]
            $role    = ($turn['role'] === 'model') ? 'assistant' : 'user';
            $content = $turn['parts'][0]['text'] ?? '';
            if ($content) {
                $messages[] = ['role' => $role, 'content' => $content];
            }
        }

        $messages[] = ['role' => 'user', 'content' => $userMessage];

        return $messages;
    }

    /**
     * Coba model Groq satu per satu (auto fallback)
     */
    private static function callWithFallback(array $messages): ?array {
        foreach (GROQ_MODELS as $model) {
            $result = self::callApi($model, $messages);
            if ($result !== null) {
                return $result;
            }
        }
        return null;
    }

    /**
     * Panggil Groq API
     */
    private static function callApi(string $model, array $messages): ?array {
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
                'Authorization: Bearer ' . GROQ_API_KEY,
                'User-Agent: SMDTBA-Chatbot/2.0'
            ],
            CURLOPT_SSL_VERIFYPEER => false
        ]);

        $raw  = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if (DEBUG_MODE) {
            error_log('Groq model=' . $model . ' HTTP=' . $code . ' ERR=' . $err);
        }

        if ($code !== 200 || !$raw) return null;

        $data = json_decode($raw, true);
        $text = trim($data['choices'][0]['message']['content'] ?? '');

        if (empty($text)) return null;

        return [
            'text'  => $text,
            'model' => $model
        ];
    }
}
