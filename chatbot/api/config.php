<?php
date_default_timezone_set('Asia/Jakarta');
/**
 * chatbot/api/config.php — Konfigurasi Chatbot Hybrid Paroki SMDTBA
 */

// ── Load functions & secrets ───────────────────────────────
require_once dirname(__DIR__, 2) . '/includes/functions.php';
$secretsFile = privatePath('secrets.php');
if (file_exists($secretsFile)) {
    require_once $secretsFile;
}
require_once dirname(__DIR__, 2) . '/includes/config.php';

// ─── 9ROUTER / OPENAI ROUTER CONFIG ─────────────────────────────
if (!defined('ROUTER_API_KEY')) {
    define('ROUTER_API_KEY', defined('SECRET_ROUTER_API_KEY') ? SECRET_ROUTER_API_KEY : '');
}
if (!defined('ROUTER_ENDPOINT')) {
    define('ROUTER_ENDPOINT', defined('SECRET_ROUTER_ENDPOINT') ? SECRET_ROUTER_ENDPOINT : 'https://9router.kaventara.id/v1/chat/completions');
}
if (!defined('ROUTER_MODEL')) {
    define('ROUTER_MODEL', defined('SECRET_ROUTER_MODEL') ? SECRET_ROUTER_MODEL : 'chatbot-paroki');
}
define('ROUTER_MAX_OUTPUT_TOKENS', 800);
define('ROUTER_TEMPERATURE',       0.65);
define('ROUTER_TIMEOUT',           25);

// ─── GEMINI API KEYS & MODELS ─────────────────────────────
if (!defined('GEMINI_API_KEYS')) {
    define('GEMINI_API_KEYS', defined('SECRET_GEMINI_API_KEYS') ? SECRET_GEMINI_API_KEYS : []);
}

define('GEMINI_MODELS', [
    'gemini-2.5-flash-lite',
    'gemini-flash-latest',
    'gemini-3.6-flash',
]);

define('GEMINI_MAX_OUTPUT_TOKENS', 1000);
define('GEMINI_TEMPERATURE', 0.65);
define('GEMINI_TIMEOUT', 4);

function gemini_endpoint(string $model, string $key): string
{
    return 'https://generativelanguage.googleapis.com/v1beta/models/'
        . $model
        . ':generateContent?key='
        . $key;
}

// ─── GROQ API KEYS & MODELS ───────────────────────────────
if (!defined('GROQ_API_KEYS')) {
    define('GROQ_API_KEYS', defined('SECRET_GROQ_API_KEYS') ? SECRET_GROQ_API_KEYS : []);
}
define('GROQ_ENDPOINT', 'https://api.groq.com/openai/v1/chat/completions');

define('GROQ_MODELS', [
    'qwen/qwen3.8-27b',
    'qwen/qwen3.6-27b',
    'openai/gpt-oss-120b',
    'groq/compound',
]);

define('GROQ_MAX_OUTPUT_TOKENS', 700);
define('GROQ_TEMPERATURE',       0.65);
define('GROQ_TIMEOUT',           15);

// ──────────────────────────────────────────────────────────
// PATH DATA
// ──────────────────────────────────────────────────────────
define('DATA_DIR',  __DIR__ . '/../data/');
define('FAQ_FILE',  DATA_DIR . 'faq.json');
define('CONV_DIR',  DATA_DIR . 'conversations/');
define('LOG_DIR',   DATA_DIR . 'logs/');
define('CACHE_DIR', DATA_DIR . 'cache/');

// ──────────────────────────────────────────────────────────
// ANTI-SPAM & LIMITS
// ──────────────────────────────────────────────────────────
define('RATE_LIMIT_MAX',    30);
define('RATE_LIMIT_WINDOW', 60);
define('MAX_MSG_LENGTH',    1000);

// ──────────────────────────────────────────────────────────
// MEMORY & LEARNING
// ──────────────────────────────────────────────────────────
define('MAX_HISTORY',          6);
define('SESSION_TIMEOUT',      1800);
define('AUTO_LEARN_ENABLED',   false);
define('AUTO_LEARN_THRESHOLD', 3);

// ──────────────────────────────────────────────────────────
// WEBSITE INFO
// ──────────────────────────────────────────────────────────
if (!defined('SITE_BASE')) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
        $scheme = $_SERVER['HTTP_X_FORWARDED_PROTO'];
    }
    $host = $_SERVER['HTTP_HOST'] ?? 'www.parokitulungagung.org';
    define('SITE_BASE', $scheme . '://' . $host);
}

if (!defined('SITE_NAME')) {
    define('SITE_NAME', 'Paroki Santa Maria Dengan Tidak Bernoda Asal (SMDTBA) Tulungagung');
}
if (!defined('SITE_URL')) {
    define('SITE_URL',  'https://www.parokitulungagung.org');
}
if (!defined('SITE_LANG')) {
    define('SITE_LANG', 'id');
}

// ──────────────────────────────────────────────────────────
// CACHE & DEBUG
// ──────────────────────────────────────────────────────────
define('CACHE_TTL',         300);
define('ARTICLE_CACHE_TTL', 1800);
define('DEBUG_MODE', false);
define('ALLOWED_ORIGIN', '*');

// ──────────────────────────────────────────────────────────
// CREATE DIRECTORIES
// ──────────────────────────────────────────────────────────
foreach ([CONV_DIR, LOG_DIR, CACHE_DIR] as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
}
