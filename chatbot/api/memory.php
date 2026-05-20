<?php
/**
 * memory.php — Manajemen memori percakapan berbasis session file
 */

require_once __DIR__ . '/config.php';

class ChatMemory {

    private string $sessionId;
    private string $filePath;
    private array  $data;

    public function __construct(string $sessionId) {
        $this->sessionId = preg_replace('/[^a-zA-Z0-9_-]/', '', $sessionId);
        $this->filePath  = CONV_DIR . $this->sessionId . '.json';
        $this->data      = $this->load();
    }

    /** Muat session dari file */
    private function load(): array {
        if (!file_exists($this->filePath)) {
            return [
                'session_id'   => $this->sessionId,
                'created_at'   => time(),
                'last_active'  => time(),
                'page_context' => '',
                'user_name'    => '',
                'history'      => [],
                'meta'         => []
            ];
        }
        $raw = json_decode(file_get_contents($this->filePath), true);
        // Cek timeout session
        if ((time() - ($raw['last_active'] ?? 0)) > SESSION_TIMEOUT) {
            return [
                'session_id'   => $this->sessionId,
                'created_at'   => time(),
                'last_active'  => time(),
                'page_context' => '',
                'user_name'    => '',
                'history'      => [],
                'meta'         => []
            ];
        }
        return $raw;
    }

    /** Simpan session ke file */
    public function save(): void {
        $this->data['last_active'] = time();
        file_put_contents(
            $this->filePath,
            json_encode($this->data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            LOCK_EX
        );
    }

    /** Tambah turn percakapan */
    public function addTurn(string $userMsg, string $botMsg, string $source = 'kb'): void {
        $this->data['history'][] = [
            'role'      => 'user',
            'content'   => $userMsg,
            'timestamp' => time()
        ];
        $this->data['history'][] = [
            'role'      => 'assistant',
            'content'   => strip_tags($botMsg),
            'source'    => $source,   // 'kb' | 'gemini' | 'fallback'
            'timestamp' => time()
        ];

        // Potong history jika terlalu panjang (simpan MAX_HISTORY turn terakhir)
        $maxEntries = MAX_HISTORY * 2;
        if (count($this->data['history']) > $maxEntries) {
            $this->data['history'] = array_slice($this->data['history'], -$maxEntries);
        }
        $this->save();
    }

    /** Ambil history dalam format untuk Gemini API */
    public function getGeminiHistory(): array {
        $result = [];
        foreach ($this->data['history'] as $turn) {
            $result[] = [
                'role'  => $turn['role'] === 'assistant' ? 'model' : 'user',
                'parts' => [['text' => $turn['content']]]
            ];
        }
        return $result;
    }

    /** Ambil ringkasan history untuk sistem prompt */
    public function getSummary(): string {
        $history = $this->data['history'];
        if (empty($history)) return '';

        $lines = [];
        $count = min(6, count($history));
        $slice = array_slice($history, -$count);
        foreach ($slice as $turn) {
            $role  = $turn['role'] === 'user' ? 'User' : 'Bot';
            $lines[] = $role . ': ' . mb_substr($turn['content'], 0, 120);
        }
        return implode("\n", $lines);
    }

    public function setPageContext(string $url, string $title = ''): void {
        $this->data['page_context'] = $url . ($title ? " ($title)" : '');
        $this->save();
    }

    public function getPageContext(): string {
        return $this->data['page_context'] ?? '';
    }

    public function getHistory(): array {
        return $this->data['history'];
    }

    public function setMeta(string $key, $value): void {
        $this->data['meta'][$key] = $value;
    }

    public function getMeta(string $key, $default = null) {
        return $this->data['meta'][$key] ?? $default;
    }

    /** Bersihkan file session lama (cron job) */
    public static function cleanup(): int {
        $deleted = 0;
        foreach (glob(CONV_DIR . '*.json') as $file) {
            $data = json_decode(file_get_contents($file), true);
            if ((time() - ($data['last_active'] ?? 0)) > SESSION_TIMEOUT * 2) {
                unlink($file);
                $deleted++;
            }
        }
        return $deleted;
    }
}
