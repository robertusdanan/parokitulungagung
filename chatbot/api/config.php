<?php
date_default_timezone_set('Asia/Jakarta');
/**
 * chatbot/api/config.php — Konfigurasi Chatbot Hybrid Paroki SMDTBA
 */

// ── Load secrets dari luar public_html ──────────────────────
// __DIR__ = /home/ejtkecoh/public_html/chatbot/api
// dirname(__DIR__, 3) = /home/ejtkecoh
require_once dirname(__DIR__, 3) . '/private/secrets.php';

// ─── GEMINI API ───────────────────────────────────────────
define('GEMINI_API_KEY', SECRET_GEMINI_API_KEY);

// ─── GROQ API (Backup jika Gemini habis token / error) ────
define('GROQ_API_KEY',  SECRET_GROQ_API_KEY);
define('GROQ_ENDPOINT', 'https://api.groq.com/openai/v1/chat/completions');

define('GROQ_MODELS', [
    'llama-3.1-8b-instant',
    'llama-3.3-70b-versatile',
    'mixtral-8x7b-32768',
]);

define('GROQ_MAX_OUTPUT_TOKENS', 250);
define('GROQ_TEMPERATURE',       0.6);
define('GROQ_TIMEOUT',           15);

define('GEMINI_MODELS', [
    'gemini-3.1-flash-lite',
    'gemini-3-flash',
    'gemini-2.5-flash-lite',
    'gemini-2.5-flash',
]);

/*
|--------------------------------------------------------------------------
| GEMINI SETTINGS
|--------------------------------------------------------------------------
*/
define('GEMINI_MAX_OUTPUT_TOKENS', 250);
define('GEMINI_TEMPERATURE', 0.6);
define('GEMINI_TIMEOUT', 15);

/*
|--------------------------------------------------------------------------
| BUILD ENDPOINT
|--------------------------------------------------------------------------
*/
function gemini_endpoint(string $model): string
{
    return
        'https://generativelanguage.googleapis.com/v1beta/models/'
        . $model
        . ':generateContent?key='
        . GEMINI_API_KEY;
}

// ──────────────────────────────────────────────────────────
// PATH DATA
// ──────────────────────────────────────────────────────────
define('DATA_DIR',  __DIR__ . '/../data/');
define('FAQ_FILE',  DATA_DIR . 'faq.json');
define('CONV_DIR',  DATA_DIR . 'conversations/');
define('LOG_DIR',   DATA_DIR . 'logs/');
define('CACHE_DIR', DATA_DIR . 'cache/');

// ──────────────────────────────────────────────────────────
// ANTI-SPAM
// ──────────────────────────────────────────────────────────
define('RATE_LIMIT_MAX',    15);
define('RATE_LIMIT_WINDOW', 60);
define('MAX_MSG_LENGTH',    500);

// ──────────────────────────────────────────────────────────
// MEMORY
// ──────────────────────────────────────────────────────────
define('MAX_HISTORY',    5);
define('SESSION_TIMEOUT', 1800);

// ──────────────────────────────────────────────────────────
// AUTO LEARNING
// ──────────────────────────────────────────────────────────
define('AUTO_LEARN_ENABLED',   true);
define('AUTO_LEARN_THRESHOLD', 3);

// ──────────────────────────────────────────────────────────
// WEBSITE INFO
// ──────────────────────────────────────────────────────────
if (!defined('SITE_BASE')) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
        $scheme = $_SERVER['HTTP_X_FORWARDED_PROTO'];
    }
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    define('SITE_BASE', $scheme . '://' . $host);
}

define('SITE_NAME', 'Paroki Santa Maria Dengan Tidak Bernoda Asal (SMDTBA) Tulungagung');
define('SITE_URL',  rtrim(SITE_BASE, '/'));
define('SITE_LANG', 'id');

// ──────────────────────────────────────────────────────────
// CACHE
// ──────────────────────────────────────────────────────────
define('CACHE_TTL',         300);
define('ARTICLE_CACHE_TTL', 3600);

// ──────────────────────────────────────────────────────────
// DEBUG
// ──────────────────────────────────────────────────────────
define('DEBUG_MODE', false);

// ──────────────────────────────────────────────────────────
// CORS
// ──────────────────────────────────────────────────────────
define('ALLOWED_ORIGIN', '*');

// ──────────────────────────────────────────────────────────
// CREATE DIRECTORY
// ──────────────────────────────────────────────────────────
foreach ([CONV_DIR, LOG_DIR, CACHE_DIR] as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0750, true);
    }
}
