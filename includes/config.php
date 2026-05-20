<?php
// ── Load secrets dari luar public_html ──────────────────────
// __DIR__ = /home/ejtkecoh/public_html/includes
// dirname(__DIR__, 2) = /home/ejtkecoh
require_once dirname(__DIR__, 2) . '/private/secrets.php';

// ── Supabase Config ─────────────────────────────────────────
define('SUPABASE_URL',      SECRET_SUPABASE_URL);
define('SUPABASE_ANON_KEY', SECRET_SUPABASE_ANON_KEY);

// ── Cache TTL ───────────────────────────────────────────────
define('CACHE_TTL_DEFAULT',  300);
define('CACHE_TTL_STATIC',   600);
define('CACHE_TTL_DYNAMIC',  180);
