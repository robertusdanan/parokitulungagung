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

        if (DEBUG_MODE) {
            error_log('[9Router] Gagal atau key kosong, beralih ke Gemini...');
        }

        // Fallback ke Gemini (yang nantinya fallback ke Groq jika Gemini gagal)
        return GeminiAI::ask(
            userMessage: $userMessage,
            history:     $history,
            pageContext: $pageContext,
            articleText: $articleText
        );
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
            ],
            CURLOPT_TIMEOUT        => ROUTER_TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $rawBody  = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err      = curl_error($ch);
        curl_close($ch);

        if ($err || !$rawBody || $httpCode !== 200) {
            if (DEBUG_MODE) {
                error_log("[9Router] HTTP $httpCode, Curl Error: $err");
            }
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
        if (preg_match('/```|<?php|function\s*\(|SELECT\s+.*FROM|sk-[a-zA-Z0-9]|Terima input|Kirim detail masalah/i', $text)) {
            return 'Berkah Dalem. 🙏 Silakan sampaikan pertanyaan Anda seputar jadwal misa, pelayanan sakramen, atau kegiatan Paroki SMDTBA Tulungagung. Kami siap membantu!';
        }

        // 3. Redaksi otomatis token/key jika ada
        $text = preg_replace('/(sk-[a-zA-Z0-9_-]{10,}|sb_[a-zA-Z0-9_-]{10,}|AIza[a-zA-Z0-9_-]{10,}|gsk_[a-zA-Z0-9_-]{10,})/', '[REDACTED_KEY]', $text);

        // 4. Format Markdown ke HTML
        $text = preg_replace('/^#{1,4}\s*(.*?)$/m', '<b>$1</b>', $text);
        $text = preg_replace('/\*\*(.*?)\*\*/s', '<b>$1</b>', $text);
        $text = preg_replace('/(?<!\*)\*(?!\*)(.*?)(?<!\*)\*(?!\*)/s', '<i>$1</i>', $text);
        $text = preg_replace('/\[(.*?)\]\((.*?)\)/', '<a href="$2">$1</a>', $text);
        $text = nl2br(trim($text));
        return $text;
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
