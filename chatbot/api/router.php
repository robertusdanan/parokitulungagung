<?php
/**
 * router.php — Primary 9Router AI Client with Auto Fallback to Gemini & Groq
 * Versi: 4.0 — Unified Knowledge Base & Dynamic RAG
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/knowledge.php';
require_once __DIR__ . '/gemini.php';

class RouterAI
{
    /**
     * Kirim pertanyaan ke 9router dengan fallback ke Gemini -> Groq
     */
    public static function ask(
        string $userMessage,
        array  $history     = [],
        string $pageContext = '',
        string $articleText = ''
    ): array {
        $start = microtime(true);

        if (!empty(ROUTER_API_KEY)) {
            $systemPrompt = ParokiKnowledge::getSystemPrompt($pageContext, $articleText);
            $payload      = self::buildPayload($systemPrompt, $history, $userMessage);
            $response     = self::callApi($payload);

            $latencyMs = (microtime(true) - $start) * 1000;

            if ($response && is_array($response)) {
                $text = self::extractText($response);
                if ($text) {
                    return [
                        'answer'     => $text,
                        'latency_ms' => round($latencyMs, 2),
                        'error'      => false,
                        'model'      => $response['model'] ?? ROUTER_MODEL,
                        'provider'   => '9router',
                    ];
                }
            }
        }

        return [
            'answer'     => self::fallbackMessage(),
            'latency_ms' => round((microtime(true) - $start) * 1000, 2),
            'error'      => true,
            'model'      => null,
            'provider'   => 'none',
        ];
    }

    private static function fallbackMessage(): string
    {
        return 'Berkah Dalem. 🙏 Layanan asisten cerdas saat ini sedang sibuk. Silakan tanyakan kembali nanti atau hubungi <a href="https://wa.me/628563678844" target="_blank"><b>WhatsApp Sekretariat (+62 856-3678-844)</b></a> dan kunjungi <a href="/kontak"><b>Halaman Kontak</b></a> kami.';
    }

    private static function callApi(array $payload): ?array
    {
        $ch = curl_init(ROUTER_ENDPOINT);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . ROUTER_API_KEY,
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            CURLOPT_TIMEOUT        => ROUTER_TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => 6,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) ParokiChatbot/1.0',
        ]);

        $rawBody  = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err      = curl_error($ch);
        curl_close($ch);

        if ($err || !$rawBody || $httpCode !== 200) {
            error_log("[9Router] HTTP $httpCode, Curl Error: $err | Endpoint: " . ROUTER_ENDPOINT);
            return null;
        }

        $decoded = json_decode($rawBody, true);
        return is_array($decoded) ? $decoded : null;
    }

    private static function extractText(array $response): ?string
    {
        $text = $response['choices'][0]['message']['content'] ?? null;
        if (!$text) return null;

        $text = trim($text);
        if ($text === '') return null;

        return self::formatToSafeHtml($text);
    }

            private static function formatToSafeHtml(string $text): string
    {
        // 1. Hapus tag reasoning / thought jika ada
        $text = preg_replace('/<(thought|think|reasoning)[^>]*>.*?<\/\1>/si', '', $text);

        // 2. Deteksi kebocoran kode atau bahasa teknis / dev / caveman
        if (preg_match('/```|<\?php|function\s*\(|SELECT\s+.*FROM|sk-[a-zA-Z0-9]|Terima input|Kirim detail masalah/i', $text)) {
            return 'Berkah Dalem. 🙏 Silakan sampaikan pertanyaan Anda seputar jadwal misa, pelayanan sakramen, atau kegiatan Paroki SMDTBA Tulungagung. Kami siap membantu!';
        }

        // 3. Redaksi otomatis token/key jika ada
        $text = preg_replace('/(sk-[a-zA-Z0-9_-]{10,}|sb_[a-zA-Z0-9_-]{10,}|AIza[a-zA-Z0-9_-]{10,}|gsk_[a-zA-Z0-9_-]{10,})/', '[REDACTED_KEY]', $text);

        // 4. Strip common prompt leak artifacts
        $text = preg_replace('/^```[a-z]*\s*/i', '', $text);
        $text = preg_replace('/\s*```$/', '', $text);
        $text = preg_replace('/^\(HTML.*?\):?\s*/i', '', $text);
        $text = preg_replace('/^Here is.*?:/i', '', $text);

        // 5. Normalisasi newline & batasi enter beruntun maksimal 2
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        // 6. Format Markdown ke HTML
        $text = preg_replace('/^#{1,4}\s*(.*?)$/m', '<b>$1</b>', $text);
        $text = preg_replace('/\*\*(.*?)\*\*/s', '<b>$1</b>', $text);
        $text = preg_replace('/^\s*[\*\-]\s+/m', '• ', $text);
        $text = preg_replace('/(?<!\*)\*(?!\*)(.*?)(?<!\*)\*(?!\*)/s', '<i>$1</i>', $text);
        $text = preg_replace('/\[(.*?)\]\((.*?)\)/', '<a href="$2">$1</a>', $text);

        // 7. Bentuk paragraf rapi & proporsional
        $paragraphs = explode("\n\n", $text);
        $cleanParas = [];
        foreach ($paragraphs as $p) {
            $p = trim($p);
            if ($p === '') continue;
            $lines = array_filter(array_map('trim', explode("\n", $p)), fn($l) => $l !== '');
            $cleanParas[] = implode('<br>', $lines);
        }

        return implode('<br><br>', $cleanParas);
    }

    private static function buildPayload(string $systemPrompt, array $history, string $userMessage): array
    {
        $messages = [];
        $messages[] = [
            'role'    => 'system',
            'content' => $systemPrompt,
        ];

        $recentHistory = array_slice($history, -4);
        foreach ($recentHistory as $turn) {
            $role    = ($turn['role'] === 'model') ? 'assistant' : 'user';
            $content = $turn['parts'][0]['text'] ?? '';
            if ($content !== '') {
                $messages[] = ['role' => $role, 'content' => $content];
            }
        }

        $messages[] = [
            'role'    => 'user',
            'content' => $userMessage,
        ];

        return [
            'model'       => ROUTER_MODEL,
            'messages'    => $messages,
            'temperature' => ROUTER_TEMPERATURE,
            'max_tokens'  => ROUTER_MAX_OUTPUT_TOKENS,
        ];
    }
}
