<?php
// ── Load shared helpers (untuk privatePath() / requireSecrets()) ───
require_once __DIR__ . '/functions.php';

// ── Load secrets dari luar public_html (path dinamis) ───────
// public_html = parent dari folder includes/ ini.
// private/    = parent dari public_html.
require_once privatePath('secrets.php');

// ── Supabase Config ─────────────────────────────────────────
if (!defined('SUPABASE_URL'))      define('SUPABASE_URL',      SECRET_SUPABASE_URL);
if (!defined('SUPABASE_ANON_KEY')) define('SUPABASE_ANON_KEY', SECRET_SUPABASE_ANON_KEY);

// ── Cloudflare R2 Config (galeri foto) ────────────────────────
// Tambahkan konstanta berikut di private/secrets.php (di luar public_html):
//   define('SECRET_R2_ACCESS_KEY', '...');   // pakai key READ-ONLY, bukan Write&Read
//   define('SECRET_R2_SECRET_KEY', '...');
//   define('SECRET_R2_ENDPOINT',   'https://<ACCOUNT_ID>.r2.cloudflarestorage.com');
//   define('SECRET_R2_BUCKET',     'parokitulungagung');
// Dibungkus defined()-check supaya situs tidak fatal error kalau belum diisi.
if (defined('SECRET_R2_ACCESS_KEY')) {
    if (!defined('R2_ACCESS_KEY')) define('R2_ACCESS_KEY', SECRET_R2_ACCESS_KEY);
    if (!defined('R2_SECRET_KEY')) define('R2_SECRET_KEY', SECRET_R2_SECRET_KEY);
    if (!defined('R2_ENDPOINT'))   define('R2_ENDPOINT',   SECRET_R2_ENDPOINT);
    if (!defined('R2_BUCKET'))     define('R2_BUCKET',     SECRET_R2_BUCKET);
}

// ── Cloudflare CDN Custom Domain untuk R2 (akses publik LANGSUNG) ────
// PENTING: domain ini HARUS dihubungkan lewat R2 Dashboard →
//   Bucket → Settings → Public Access → Custom Domains → Connect Domain
// BUKAN sekadar bikin CNAME manual di tab DNS ke <account>.r2.cloudflarestorage.com
// (endpoint itu API S3 yang butuh signature, request publik akan ditolak 403).
// Alur "Connect Domain" di atas yang membuat binding khusus supaya bucket
// terbaca publik & otomatis kena Cloudflare Cache (CDN).
//
// Foto yang lewat domain ini TIDAK diberi watermark (beda dari r2-image.php/
// galeri-cover.php yang masih dipakai untuk cover album). Override lewat
// private/secrets.php bila perlu:
//   define('SECRET_R2_CDN_URL', 'https://img.parokitulungagung.org');
if (!defined('R2_CDN_URL')) {
    if (defined('SECRET_R2_CDN_URL')) {
        define('R2_CDN_URL', SECRET_R2_CDN_URL);
    } else {
        define('R2_CDN_URL', 'https://img.parokitulungagung.org');
    }
}

// ── Cloudflare Zone & API Token (Cache Purge) ───────────────────────────
if (!defined('CF_ZONE_ID'))   define('CF_ZONE_ID',   defined('SECRET_CF_ZONE_ID') ? SECRET_CF_ZONE_ID : (defined('CLOUDFLARE_ZONE_ID') ? CLOUDFLARE_ZONE_ID : ''));
if (!defined('CF_API_TOKEN')) define('CF_API_TOKEN', defined('SECRET_CF_API_TOKEN') ? SECRET_CF_API_TOKEN : (defined('CLOUDFLARE_API_TOKEN') ? CLOUDFLARE_API_TOKEN : ''));

// ── Prefix folder untuk album foto galeri di dalam bucket R2 ─────────────
// Semua folder album (mis. "Misa Natal 2026") disimpan DI BAWAH prefix ini,
// supaya rapi dan terpisah dari folder lain di root bucket (_thumbnails/,
// icon/, umkm/, artikel/, gereja/, ogpreview/, person/, jadwal_petugas/,
// downloads/, dst). Key lengkap 1 foto = "galerifoto/<nama-album>/<file>".
if (!defined('R2_ALBUM_PREFIX')) define('R2_ALBUM_PREFIX', 'galerifoto/');

// ── Prefix folder "person" (foto Romo, DPP/BGKP, AI, Wilayah, Koordinator) ─
// Folder ini di dalam bucket R2 tempat foto person diunggah.
// Foto person TIDAK lagi disimpan di hosting lokal — semua upload person
// dari admin akan ditulis ke bucket R2 dengan key "<R2_PERSON_PREFIX><file>".
// URL publik diambil dari R2_CDN_URL (custom domain img.parokitulungagung.org
// yg sudah di-bind ke bucket).
if (!defined('R2_PERSON_PREFIX'))   define('R2_PERSON_PREFIX',   'person/');
if (!defined('R2_ICON_PREFIX'))     define('R2_ICON_PREFIX',     'icon/');
if (!defined('R2_ICON_KAT_PREFIX')) define('R2_ICON_KAT_PREFIX', 'icon/kategorial/');
if (!defined('R2_ARTIKEL_PREFIX'))  define('R2_ARTIKEL_PREFIX',  'artikel/');
if (!defined('R2_OG_PREFIX'))       define('R2_OG_PREFIX',       'ogpreview/');
if (!defined('R2_DOWNLOADS_PREFIX'))      define('R2_DOWNLOADS_PREFIX',      'downloads/');
if (!defined('R2_JADWAL_PETUGAS_PREFIX')) define('R2_JADWAL_PETUGAS_PREFIX', 'jadwal_petugas/');
if (!defined('R2_UMKM_PREFIX'))           define('R2_UMKM_PREFIX',           'umkm/');
if (!defined('R2_ASSETS_PREFIX'))         define('R2_ASSETS_PREFIX',         'assets/');

// ── Prefix folder untuk thumbnail cover album galeri di dalam bucket R2 ──
// Dipakai oleh galeri-cover.php (proxy watermark utk cover album). Berbeda
// dari R2_ALBUM_PREFIX (folder foto asli per-album) — thumbnail cover
// disimpan terpisah di root bucket, diunggah lewat admin/api/upload_galeri.php
// (lihat komentar di file itu: "Folder tujuan di R2: _thumbnails/galeri/").
if (!defined('R2_GALERI_THUMB_PREFIX')) define('R2_GALERI_THUMB_PREFIX', '_thumbnails/galeri/');

// Resize thumbnail di edge lewat Cloudflare Image Resizing (fitur tambahan
// BERBAYAR, harus diaktifkan dulu di dashboard: Speed → Optimization →
// Image Resizing). Selama masih false, grid galeri memuat foto ukuran ASLI
// (tidak error, hanya lebih berat di jaringan lambat/foto besar).
define('R2_CDN_IMAGE_RESIZING', false);

// ── Cache TTL ───────────────────────────────────────────────
define('CACHE_TTL_DEFAULT',  300);
define('CACHE_TTL_STATIC',   600);
define('CACHE_TTL_DYNAMIC',  180);