<?php
/**
 * admin/includes/config.php
 * Konfigurasi utama Admin Panel SMDTBA — Supabase Edition
 */

// Blokir akses langsung ke file ini
if (!defined('ADMIN_VERSION') && php_sapi_name() !== 'cli') {
    $included = count(get_included_files()) > 1;
    if (!$included) {
        http_response_code(403);
        exit('403 Forbidden');
    }
}

// ── Load shared helpers (untuk privatePath() / requireSecrets()) ───
require_once dirname(__DIR__, 2) . '/includes/functions.php';

// ── Load secrets dari luar public_html (path dinamis) ───────
// admin/includes  → ../../includes  → ../../functions.php
// private/        = 1 level di atas public_html.
require_once privatePath('secrets.php');

// ── Semua define() di bawah ini dibungkus !defined() ──────────────────
// Supaya file ini AMAN dipanggil dari halaman publik (lewat header_auth.php
// untuk cek status login admin) tanpa bentrok dengan konstanta yang sudah
// didefinisikan lebih dulu oleh includes/config.php milik situs publik
// (mis. SUPABASE_URL). Tanpa guard ini, define() akan memicu PHP Warning
// "Constant already defined" — yang oleh error-handler global situs
// otomatis diubah jadi halaman 404 (root cause 404 di semua halaman).

if (!defined('ADMIN_TITLE')) define('ADMIN_TITLE', 'Admin Panel · SMDTBA');
if (!defined('ADMIN_ROOT'))  define('ADMIN_ROOT',  dirname(__DIR__));

// ── Supabase ──────────────────────────────────────────────────────────
if (!defined('SUPABASE_URL'))         define('SUPABASE_URL',         SECRET_SUPABASE_URL);
if (!defined('SUPABASE_SERVICE_KEY')) define('SUPABASE_SERVICE_KEY', SECRET_SUPABASE_SERVICE_KEY);

// ── Nama tabel Supabase ───────────────────────────────────────────────
if (!defined('TABLE_GALERI'))          define('TABLE_GALERI',          'galeri_foto');
if (!defined('TABLE_PETUGAS'))         define('TABLE_PETUGAS',         'petugas');
if (!defined('TABLE_WILAYAH'))         define('TABLE_WILAYAH',         'daftar_wilayah');
if (!defined('TABLE_AI'))              define('TABLE_AI',              'daftar_asisten_imam');
if (!defined('TABLE_DPP'))             define('TABLE_DPP',             'kepengurusan_dpp_bgkp');
if (!defined('TABLE_AGENDA'))          define('TABLE_AGENDA',          'info_paroki');
if (!defined('TABLE_USERS'))           define('TABLE_USERS',           'users');
if (!defined('TABLE_ACTLOG'))          define('TABLE_ACTLOG',          'activity_log');
if (!defined('TABLE_MASTER_LINGK'))    define('TABLE_MASTER_LINGK',    'master_lingkungan');
if (!defined('TABLE_MASTER_BIDANG'))   define('TABLE_MASTER_BIDANG',   'master_bidang');
if (!defined('TABLE_MASTER_KOOR'))     define('TABLE_MASTER_KOOR',     'master_koordinator');
if (!defined('TABLE_UMKM'))            define('TABLE_UMKM',            'umkm_umat');
if (!defined('TABLE_KELOMPOK_PROFIL')) define('TABLE_KELOMPOK_PROFIL', 'kelompok_profil');
if (!defined('TABLE_DOKUMEN_PAROKI'))  define('TABLE_DOKUMEN_PAROKI',  'dokumen_paroki');
if (!defined('TABLE_ROMO'))            define('TABLE_ROMO',            'romo_paroki');

// ── Cloudflare Turnstile (login admin) ──────────────────────────────────
// Sebelumnya secret key ini di-hardcode langsung di admin/index.php.
// Dipindah ke sini supaya ikut pola SECRET_* seperti kredensial lain
// (Supabase, Gemini, Groq, SMTP) dan disimpan di private/secrets.php,
// bukan di file publik. Tambahkan baris berikut ke private/secrets.php:
//   define('SECRET_TURNSTILE_SECRET_KEY', 'isi-secret-key-turnstile-kamu');
if (!defined('TURNSTILE_SECRET_KEY')) define('TURNSTILE_SECRET_KEY', SECRET_TURNSTILE_SECRET_KEY);

// ── Cloudflare R2 CDN URL & Kredensial (untuk admin API) ───────────────────
// R2_CDN_URL hanya didefinisikan di includes/config.php (sisi publik).
// Admin API membutuhkannya juga — definisikan di sini supaya semua admin/api/*.php
// bisa pakai R2_CDN_URL tanpa harus load public config secara terpisah.
if (!defined('R2_CDN_URL')) {
    define('R2_CDN_URL', defined('SECRET_R2_CDN_URL')
        ? SECRET_R2_CDN_URL
        : 'https://img.parokitulungagung.org');
}
if (!defined('R2_ACCESS_KEY')) {
    $r2ak = defined('SECRET_R2_ACCESS_KEY') ? SECRET_R2_ACCESS_KEY : (defined('SECRET_R2_ACCESS_KEY_WRITE') ? SECRET_R2_ACCESS_KEY_WRITE : null);
    $r2sk = defined('SECRET_R2_SECRET_KEY') ? SECRET_R2_SECRET_KEY : (defined('SECRET_R2_SECRET_KEY_WRITE') ? SECRET_R2_SECRET_KEY_WRITE : null);
    $r2ep = defined('SECRET_R2_ENDPOINT')   ? SECRET_R2_ENDPOINT   : null;
    $r2bk = defined('SECRET_R2_BUCKET')     ? SECRET_R2_BUCKET     : null;
    if ($r2ak && $r2sk && $r2ep && $r2bk) {
        define('R2_ACCESS_KEY', $r2ak);
        define('R2_SECRET_KEY', $r2sk);
        define('R2_ENDPOINT',   $r2ep);
        define('R2_BUCKET',     $r2bk);
    }
}

// ── Cloudflare Zone & API Token (Cache Purge) ───────────────────────────
if (!defined('CF_ZONE_ID'))   define('CF_ZONE_ID',   defined('SECRET_CF_ZONE_ID') ? SECRET_CF_ZONE_ID : (defined('CLOUDFLARE_ZONE_ID') ? CLOUDFLARE_ZONE_ID : ''));
if (!defined('CF_API_TOKEN')) define('CF_API_TOKEN', defined('SECRET_CF_API_TOKEN') ? SECRET_CF_API_TOKEN : (defined('CLOUDFLARE_API_TOKEN') ? CLOUDFLARE_API_TOKEN : ''));

// ── Prefix folder untuk album foto galeri di dalam bucket R2 ─────────────
// Sama seperti includes/config.php (sisi publik) — didefinisikan lagi di
// sini (dibungkus !defined()) supaya admin/api/*.php yang hanya me-require
// admin/includes/config.php (bukan includes/config.php publik) tetap punya
// konstanta ini. Kalau diubah, ubah JUGA nilainya di includes/config.php.
if (!defined('R2_ALBUM_PREFIX')) define('R2_ALBUM_PREFIX', 'galerifoto/');

// ── Prefix folder "person" di dalam bucket R2 ────────────────────────────
// Lihat includes/config.php (sisi publik) untuk penjelasan. Didefinisikan
// di sini agar admin/api/*.php yg hanya load admin/includes/config.php juga
// punya akses ke konstanta ini saat mengunggah/membangun URL foto person.
if (!defined('R2_PERSON_PREFIX'))   define('R2_PERSON_PREFIX',   'person/');
if (!defined('R2_ICON_PREFIX'))     define('R2_ICON_PREFIX',     'icon/');
if (!defined('R2_ICON_KAT_PREFIX')) define('R2_ICON_KAT_PREFIX', 'icon/kategorial/');
if (!defined('R2_ARTIKEL_PREFIX'))  define('R2_ARTIKEL_PREFIX',  'artikel/');
if (!defined('R2_OG_PREFIX'))       define('R2_OG_PREFIX',       'ogpreview/');
if (!defined('R2_DOWNLOADS_PREFIX'))      define('R2_DOWNLOADS_PREFIX',      'downloads/');
if (!defined('R2_JADWAL_PETUGAS_PREFIX')) define('R2_JADWAL_PETUGAS_PREFIX', 'jadwal_petugas/');
if (!defined('R2_UMKM_PREFIX'))           define('R2_UMKM_PREFIX',           'umkm/');
if (!defined('R2_ASSETS_PREFIX'))         define('R2_ASSETS_PREFIX',         'assets/');
if (!defined('R2_STORIES_PREFIX'))        define('R2_STORIES_PREFIX',        'stories/');

// ── Prefix staging video sementara sebelum dikompresi GitHub Actions ──
// Key di dalamnya berbentuk deterministik:
//     {R2_PENDING_VIDEO_PREFIX}{albumClean}/{relpath, ekstensi sudah final}
// (LIHAT admin/api/r2_folder_upload.php & admin/api/r2_video_batch_dispatch.php)
// supaya GitHub Actions bisa list prefix ini per-album dan menurunkan sendiri
// key final-nya, tanpa PHP perlu mengirim daftar job eksplisit.
if (!defined('R2_PENDING_VIDEO_PREFIX')) define('R2_PENDING_VIDEO_PREFIX', '_pending_video/');

// ── Offload kompresi video ke GitHub Actions (opsional) ──────────────────
// Dipakai oleh admin/api/r2_folder_upload.php ketika ffmpeg TIDAK tersedia
// di server ini. Kalau konstanta di bawah belum diisi, fitur ini otomatis
// nonaktif dan video di-upload apa adanya seperti perilaku lama (aman,
// tidak fatal error). Tambahkan di private/secrets.php:
//
//   define('SECRET_GITHUB_TOKEN', 'github_pat_xxx');
//     -> Fine-grained PAT, scope MINIMAL "Actions: Read and write" pada
//        1 repo khusus (tidak perlu akses repo source code utama).
//   define('SECRET_GITHUB_REPO', 'namauser/nama-repo-video-compressor');
//   define('SECRET_VIDEO_WEBHOOK_SECRET', 'string-acak-panjang');
//     -> Harus SAMA dengan secret "VIDEO_WEBHOOK_SECRET" yang didaftarkan
//        di repo GitHub tsb (Settings > Secrets and variables > Actions),
//        dipakai admin/api/r2_video_job_callback.php untuk memverifikasi
//        bahwa callback memang datang dari workflow GitHub Actions.
//
// Lihat juga: includes/GitHubDispatcher.php, .github/workflows/compress-video.yml
// (di repo terpisah), dan admin/api/r2_video_job_callback.php.

// ── Session ────────────────────────────────────────────────────────────
if (!defined('SESSION_LIFETIME')) define('SESSION_LIFETIME', 8 * 3600);
if (!defined('SESSION_NAME'))     define('SESSION_NAME',     'smdtba_admin');

// ── Email (Gmail SMTP) ─────────────────────────────────────────────────
if (!defined('MAIL_FROM'))      define('MAIL_FROM',      SECRET_MAIL_USERNAME);
if (!defined('MAIL_FROM_NAME')) define('MAIL_FROM_NAME', 'DEV parokitulungagung.org');
if (!defined('MAIL_USERNAME'))  define('MAIL_USERNAME',  SECRET_MAIL_USERNAME);
if (!defined('MAIL_PASSWORD'))  define('MAIL_PASSWORD',  SECRET_MAIL_PASSWORD);

// ── Halaman data ───────────────────────────────────────────────────────
if (!defined('ALL_PAGES')) define('ALL_PAGES', [
    'galeri','stories','wilayah','asisten_imam','dpp_bgkp','romo_paroki',
    'agenda','umkm','media','kategorial',
    'master_lingkungan','master_bidang','master_koordinator','dokumen_paroki',
    'jadwal_petugas_gambar',
]);

// ── Halaman artikel ───────────────────────────────────────────────────
if (!defined('ARTIKEL_PAGES')) define('ARTIKEL_PAGES', ['berita', 'kronik', 'historia']);

if (!defined('PAGE_LABELS')) define('PAGE_LABELS', [
    'agenda'                => 'Info / Agenda',
    'dokumen_paroki'        => 'Dokumen Download',
    'jadwal_petugas_gambar' => 'Jadwal Petugas',
    'galeri'                => 'Galeri Foto',
    'stories'               => 'Stories',
    'wilayah'               => 'Profil Wilayah',
    'asisten_imam'          => 'Asisten Imam',
    'dpp_bgkp'              => 'DPP & BGKP',
    'romo_paroki'           => 'Romo Paroki',
    'umkm'                  => 'UMKM Umat',
    'media'                 => 'Media Manager',
    'kategorial'            => 'Profil Kategorial',
    'berita'                => 'Liputan Berita',
    'kronik'                => 'Kronik SMDTBA',
    'historia'              => 'Historia Gereja',
    'master'                => 'Master Data',
]);

if (!defined('ROLE_SUPERADMIN')) define('ROLE_SUPERADMIN', 'superadmin');
if (!defined('ROLE_ADMIN'))      define('ROLE_ADMIN',      'admin');

if (!defined('PAGE_ACTIONS'))       define('PAGE_ACTIONS',       ['view', 'create', 'edit', 'delete']);
if (!defined('ARTIKEL_ACTIONS'))    define('ARTIKEL_ACTIONS',    ['view', 'create', 'edit', 'delete', 'publish']);
if (!defined('UMKM_ACTIONS'))       define('UMKM_ACTIONS',       ['view', 'create', 'edit', 'delete', 'publish']);
if (!defined('KATEGORIAL_ACTIONS')) define('KATEGORIAL_ACTIONS', ['view', 'edit']);

if (!defined('PAGE_AVAILABLE_ACTIONS')) define('PAGE_AVAILABLE_ACTIONS', [
    'galeri'                => ['view', 'create', 'edit', 'delete'],
    'wilayah'               => ['view', 'create', 'edit', 'delete'],
    'asisten_imam'          => ['view', 'create', 'edit', 'delete'],
    'dpp_bgkp'              => ['view', 'create', 'edit', 'delete'],
    'romo_paroki'           => ['view', 'create', 'edit', 'delete'],
    'agenda'                => ['view', 'create', 'edit', 'delete'],
    'dokumen_paroki'        => ['view', 'create', 'edit', 'delete'],
    'jadwal_petugas_gambar' => ['view', 'create', 'edit', 'delete'],
    'umkm'                  => ['view', 'create', 'edit', 'delete', 'publish'],
    'media'                 => ['view', 'create', 'edit', 'delete'],
    'kategorial'            => ['view', 'edit'],
    'berita'                => ['view', 'create', 'edit', 'delete', 'publish'],
    'kronik'                => ['view', 'create', 'edit', 'delete', 'publish'],
    'historia'              => ['view', 'create', 'edit', 'delete', 'publish'],
    'master'                => ['view', 'create', 'edit', 'delete'],
]);

if (!defined('PAGE_ACTION_LABELS')) define('PAGE_ACTION_LABELS', [
    'view'    => 'Lihat Saja',
    'create'  => 'Tambah Data',
    'edit'    => 'Edit Data',
    'delete'  => 'Hapus Data',
    'publish' => 'Publish',
]);

// ADMIN_VERSION didefinisikan PALING TERAKHIR (dipakai sebagai penanda
// "config.php sudah pernah dimuat" oleh guard blokir-akses-langsung di atas)
if (!defined('ADMIN_VERSION')) define('ADMIN_VERSION', '2.0.0');