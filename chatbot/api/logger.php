<?php
/**
 * logger.php — Logging pertanyaan & auto-learning FAQ
 *
 * Semua log disimpan dalam 1 file: chat.log
 * Entri lebih dari 30 hari otomatis dihapus setiap kali ada penulisan baru.
 */

require_once __DIR__ . '/config.php';

class ChatLogger {

    const LOG_FILE  = LOG_DIR . 'chat.log';
    const KEEP_DAYS = 30;

    /** Log setiap interaksi ke chat.log (single file) */
    public static function log(
        string $sessionId,
        string $userMsg,
        string $botMsg,
        string $source,
        float  $latencyMs = 0
    ): void {

        $entry = json_encode([
            'd'   => date('Y-m-d'),
            't'   => date('H:i:s'),
            'sid' => substr($sessionId, 0, 12),
            'q'   => mb_substr($userMsg, 0, 200),
            'src' => $source,
            'ms'  => round($latencyMs),
        ], JSON_UNESCAPED_UNICODE) . "\n";

        file_put_contents(self::LOG_FILE, $entry, FILE_APPEND | LOCK_EX);

        // Prune entri lama secara acak (1 dari 50 request) agar tidak berat
        if (mt_rand(1, 50) === 1) {
            self::pruneOldEntries();
        }

        if (AUTO_LEARN_ENABLED) {
            self::recordQuestion($userMsg, $source);
        }
    }

    /**
     * Hapus entri lebih dari 30 hari dari chat.log.
     * Baca semua baris, filter yang masih dalam range, tulis ulang.
     */
    public static function pruneOldEntries(): void {
        if (!file_exists(self::LOG_FILE)) return;

        $cutoff = date('Y-m-d', strtotime('-' . self::KEEP_DAYS . ' days'));
        $lines  = file(self::LOG_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if (empty($lines)) return;

        $kept = [];
        foreach ($lines as $line) {
            $entry = json_decode($line, true);
            // Baris tidak valid atau tanggal masih dalam range → simpan
            if (!$entry || !isset($entry['d']) || $entry['d'] >= $cutoff) {
                $kept[] = $line;
            }
        }

        // Tulis ulang hanya jika ada yang dibuang
        if (count($kept) < count($lines)) {
            file_put_contents(
                self::LOG_FILE,
                implode("\n", $kept) . (count($kept) ? "\n" : ''),
                LOCK_EX
            );
        }
    }

    /** Catat pertanyaan yang tidak terjawab KB (untuk auto-learn) */
    private static function recordQuestion(string $question, string $source): void {
        if ($source === 'kb') return;

        $file = LOG_DIR . 'unanswered.json';
        $data = [];

        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true) ?? [];
        }

        // Buang entri yang last_seen-nya lebih dari 30 hari
        $cutoff = date('Y-m-d', strtotime('-' . self::KEEP_DAYS . ' days'));
        $data   = array_filter($data, fn($v) => ($v['last_seen'] ?? '9999-99-99') >= $cutoff);

        $key = mb_strtolower(trim($question));
        if (!isset($data[$key])) {
            $data[$key] = ['count' => 0, 'first_seen' => date('Y-m-d'), 'last_seen' => date('Y-m-d')];
        }
        $data[$key]['count']++;
        $data[$key]['last_seen'] = date('Y-m-d');

        if ($data[$key]['count'] >= AUTO_LEARN_THRESHOLD) {
            $data[$key]['faq_candidate'] = true;
        }

        file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
    }

    /** Ambil daftar kandidat FAQ untuk review admin */
    public static function getFaqCandidates(): array {
        $file = LOG_DIR . 'unanswered.json';
        if (!file_exists($file)) return [];

        $data       = json_decode(file_get_contents($file), true) ?? [];
        $candidates = array_filter($data, fn($v) => !empty($v['faq_candidate']));
        arsort($candidates);
        return $candidates;
    }

    /** Statistik penggunaan hari ini (baca dari chat.log) */
    public static function getTodayStats(): array {
        $today = date('Y-m-d');
        $stats = ['total' => 0, 'kb' => 0, 'gemini' => 0, 'fallback' => 0];

        if (!file_exists(self::LOG_FILE)) return $stats;

        $lines = file(self::LOG_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $entry = json_decode($line, true);
            if (!$entry || ($entry['d'] ?? '') !== $today) continue;
            $stats['total']++;
            $src = $entry['src'] ?? 'fallback';
            if (isset($stats[$src])) $stats[$src]++;
        }

        return $stats;
    }
}
