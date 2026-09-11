<?php
/**
 * gemini.php — Hybrid Gemini AI with Multi-Key Rotation + Auto Model Fallback
 * Versi: 4.0 — Unified Knowledge Base & Dynamic RAG
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/knowledge.php';
require_once __DIR__ . '/articles.php';
require_once __DIR__ . '/router.php';

class GeminiAI
{
    /**
     * Kirim pertanyaan ke Gemini dengan fallback ke Groq jika seluruh key/model gagal
     */
    public static function ask(
        string $userMessage,
        array  $history     = [],
        string $pageContext = '',
        string $articleText = ''
    ): array {
        $start = microtime(true);

        $systemPrompt = ParokiKnowledge::getSystemPrompt($pageContext, $articleText);
        $payload      = self::buildPayload($systemPrompt, $history, $userMessage);
        $response     = self::callWithKeyAndModelFallback($payload);

        $latencyMs = (microtime(true) - $start) * 1000;

        // Gemini berhasil
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

        // Gemini gagal → fallback ke 9Router
        if (DEBUG_MODE) {
            error_log('[Gemini] Semua key & model gagal, beralih ke 9Router...');
        }

        return RouterAI::ask(
            userMessage: $userMessage,
            history:     $history,
            pageContext: $pageContext,
            articleText: $articleText
        );
    }

    private static function callWithKeyAndModelFallback(array $payload): ?array
    {
        $keys   = array_values(GEMINI_API_KEYS);
        $models = GEMINI_MODELS;

        if (empty($keys)) return null;

        $startKeyIdx = self::readKeyIndex(count($keys));

        for ($ki = 0; $ki < count($keys); $ki++) {
            $keyIdx = ($startKeyIdx + $ki) % count($keys);
            $key    = $keys[$keyIdx];

            foreach ($models as $model) {
                $endpoint = gemini_endpoint($model, $key);
                $response = self::callApi($endpoint, $payload);

                if ($response === 'RATE_LIMIT') {
                    break;
                }

                if ($response !== null && is_array($response)) {
                    self::saveKeyIndex($keyIdx);
                    return [
                        'response'  => $response,
                        'model'     => $model,
                        'key_index' => $keyIdx,
                    ];
                }
            }
        }

        return null;
    }

    private static function callApi(string $endpoint, array $payload): array|string|null
    {
        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT        => GEMINI_TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $rawBody  = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err      = curl_error($ch);
        curl_close($ch);

        if ($err || !$rawBody) return null;

        if ($httpCode === 429) return 'RATE_LIMIT';

        if ($httpCode !== 200) {
            $data = json_decode($rawBody, true);
            $status = $data['error']['status'] ?? '';
            if ($status === 'RESOURCE_EXHAUSTED') return 'RATE_LIMIT';
            return null;
        }

        $decoded = json_decode($rawBody, true);
        return is_array($decoded) ? $decoded : null;
    }

    private static function extractText(array $response): ?string
    {
        $parts = $response['candidates'][0]['content']['parts'] ?? [];
        if (empty($parts)) return null;

        $text = '';
        foreach ($parts as $part) {
            if (!empty($part['text'])) {
                $text .= $part['text'];
            }
        }

        $text = trim($text);
        if ($text === '') return null;

        // Bersihkan formatting jika model mengeluarkan markdown
        return self::formatToSafeHtml($text);
    }

    private static function formatToSafeHtml(string $text): string
    {
        // 1. Hapus tag reasoning / thought jika ada
        $text = preg_replace('/<(thought|think|reasoning)[^>]*>.*?<\/\1>/si', '', $text);

        // 2. Deteksi kebocoran kode atau bahasa teknis / dev / caveman
        if (preg_match('/```|<?php|function\s*\(|SELECT\s+.*FROM|sk-[a-zA-Z0-9]|Terima input|Kirim detail masalah/i', $text)) {
            return 'Berkah Dalem. 🙏 Silakan sampaikan pertanyaan Anda seputar jadwal misa, pelayanan sakramen, atau kegiatan Paroki SMDTBA Tulungagung. Kami siap membantu!';
        }

        // 3. Redaksi otomatis token/key jika ada
        $text = preg_replace('/(sk-[a-zA-Z0-9_-]{10,}|sb_[a-zA-Z0-9_-]{10,}|AIza[a-zA-Z0-9_-]{10,}|gsk_[a-zA-Z0-9_-]{10,})/', '[REDACTED_KEY]', $text);

        // Strip common prompt leak artifacts like "(HTML format):" or "```html"
        $text = preg_replace('/^```[a-z]*\s*/i', '', $text);
        $text = preg_replace('/\s*```$/', '', $text);
        $text = preg_replace('/^\(HTML.*?\):?\s*/i', '', $text);
        $text = preg_replace('/^Here is.*?:/i', '', $text);

        // Normalisasi spasi & newline ganda berlebihan
        $text = str_replace(["
\n", "
"], "\n", $text);
        $text = preg_replace("/\n{3,}/", "\n\n", $text);

        // Ganti markdown header ### -> <b>...</b>
        $text = preg_replace('/^#{1,4}\s*(.*?)$/m', '<b>$1</b>', $text);
        // Ganti **bold** -> <b>bold</b>
        $text = preg_replace('/\*\*(.*?)\*\*/s', '<b>$1</b>', $text);
        // Ganti * bullet point di awal baris menjadi •
        $text = preg_replace('/^\s*[\*\-]\s+/m', '• ', $text);
        // Ganti *italic* -> <i>italic</i>
        $text = preg_replace('/(?<!\*)\*(?!\*)(.*?)(?<!\*)\*(?!\*)/s', '<i>$1</i>', $text);
        // Normalisasi link markdown [title](url) -> <a href="url">title</a> jika ada
        $text = preg_replace('/\[(.*?)\]\((.*?)\)/', '<a href="$2">$1</a>', $text);
        // Ubah newline jadi <br>
        $text = nl2br(trim($text));

        // Bersihkan penumpukan tag <br> yang membuat spasi berlebihan
        $text = preg_replace('/(<br\s*\/?>\s*){3,}/i', '<br><br>', $text);
        $text = preg_replace('/<br\s*\/?>\s*(<\/?(?:ul|ol|li|blockquote|div|p)>)/i', '$1', $text);
        $text = preg_replace('/(<\/(?:ul|ol|li|blockquote|div|p)>)\s*<br\s*\/?>/i', '$1', $text);

        return trim($text);
    }

    private static function buildPayload(string $systemPrompt, array $history, string $userMessage): array
    {
        $contents = [];
        $recentHistory = array_slice($history, -4);
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
            'contents'         => $contents,
            'generationConfig' => [
                'temperature'     => GEMINI_TEMPERATURE,
                'topP'            => 0.9,
                'maxOutputTokens' => GEMINI_MAX_OUTPUT_TOKENS,
            ],
        ];
    }

    private static function cacheFile(): string
    {
        return CACHE_DIR . 'gemini_active_key.json';
    }

    private static function readKeyIndex(int $totalKeys): int
    {
        $file = self::cacheFile();
        if (!file_exists($file)) return 0;
        $data = json_decode(@file_get_contents($file), true);
        $idx  = (int)($data['idx'] ?? 0);
        return ($idx < $totalKeys) ? $idx : 0;
    }

    private static function saveKeyIndex(int $idx): void
    {
        @file_put_contents(self::cacheFile(), json_encode(['idx' => $idx]), LOCK_EX);
    }

    private static function fallbackMessage(): string
    {
        return 'Berkah Dalem. 🙏 Layanan asisten cerdas saat ini sedang sibuk. Silakan ajukan pertanyaan kembali dalam beberapa saat, atau hubungi <a href="https://wa.me/628563678844" target="_blank"><b>WhatsApp Sekretariat (+62 856-3678-844)</b></a> dan kunjungi <a href="/kontak"><b>Halaman Kontak</b></a> kami.';
    }
}
