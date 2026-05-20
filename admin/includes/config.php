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

// ── Load secrets dari luar public_html ──────────────────────
// __DIR__ = /home/ejtkecoh/public_html/admin/includes
// dirname(__DIR__, 3) = /home/ejtkecoh
require_once dirname(__DIR__, 3) . '/private/secrets.php';

define('ADMIN_VERSION', '2.0.0');
define('ADMIN_TITLE',   'Admin Panel · SMDTBA');
define('ADMIN_ROOT',    dirname(__DIR__));

// ── Supabase ──────────────────────────────────────────────────────────
define('SUPABASE_URL',         SECRET_SUPABASE_URL);
define('SUPABASE_SERVICE_KEY', SECRET_SUPABASE_SERVICE_KEY);

// ── Nama tabel Supabase ───────────────────────────────────────────────
define('TABLE_GALERI',          'galeri_foto');
define('TABLE_PETUGAS',         'petugas');
define('TABLE_WILAYAH',         'daftar_wilayah');
define('TABLE_AI',              'daftar_asisten_imam');
define('TABLE_DPP',             'kepengurusan_dpp_bgkp');
define('TABLE_AGENDA',          'info_paroki');
define('TABLE_USERS',           'users');
define('TABLE_ACTLOG',          'activity_log');
define('TABLE_MASTER_LINGK',    'master_lingkungan');
define('TABLE_MASTER_BIDANG',   'master_bidang');
define('TABLE_MASTER_KOOR',     'master_koordinator');
define('TABLE_UMKM',            'umkm_umat');
define('TABLE_KELOMPOK_PROFIL', 'kelompok_profil');
define('TABLE_DOKUMEN_PAROKI',  'dokumen_paroki');

// ── Session ────────────────────────────────────────────────────────────
define('SESSION_LIFETIME', 8 * 3600);
define('SESSION_NAME',     'smdtba_admin');

// ── Email (Gmail SMTP) ─────────────────────────────────────────────────
define('MAIL_FROM',      SECRET_MAIL_USERNAME);
define('MAIL_FROM_NAME', 'DEV parokitulungagung.org');
define('MAIL_USERNAME',  SECRET_MAIL_USERNAME);
define('MAIL_PASSWORD',  SECRET_MAIL_PASSWORD);

// ── Halaman data ───────────────────────────────────────────────────────
define('ALL_PAGES', [
    'galeri','petugas','wilayah','asisten_imam','dpp_bgkp',
    'agenda','umkm','media','kategorial',
    'master_lingkungan','master_bidang','master_koordinator','dokumen_paroki',
    'jadwal_petugas_gambar',
]);

// ── Halaman artikel ───────────────────────────────────────────────────
define('ARTIKEL_PAGES', ['berita', 'kronik', 'historia']);

define('PAGE_LABELS', [
    'agenda'       => 'Info / Agenda',
    'galeri'       => 'Galeri Foto',
    'wilayah'      => 'Profil Wilayah',
    'asisten_imam' => 'Asisten Imam',
    'dpp_bgkp'     => 'DPP & BGKP',
    'umkm'         => 'UMKM Umat',
    'media'        => 'Media Manager',
    'kategorial'   => 'Profil Kategorial',
    'berita'       => 'Liputan Berita',
    'kronik'       => 'Kronik SMDTBA',
    'historia'     => 'Historia Gereja',
    'master'       => 'Master Data',
]);

define('ROLE_SUPERADMIN', 'superadmin');
define('ROLE_ADMIN',      'admin');

define('PAGE_ACTIONS',       ['view', 'create', 'edit', 'delete']);
define('ARTIKEL_ACTIONS',    ['view', 'create', 'edit', 'delete', 'publish']);
define('UMKM_ACTIONS',       ['view', 'create', 'edit', 'delete', 'publish']);
define('KATEGORIAL_ACTIONS', ['view', 'edit']);

define('PAGE_AVAILABLE_ACTIONS', [
    'galeri'       => ['view', 'create', 'edit', 'delete'],
    'petugas'      => ['view', 'create', 'edit', 'delete'],
    'wilayah'      => ['view', 'create', 'edit', 'delete'],
    'asisten_imam' => ['view', 'create', 'edit', 'delete'],
    'dpp_bgkp'     => ['view', 'create', 'edit', 'delete'],
    'agenda'       => ['view', 'create', 'edit', 'delete'],
    'umkm'         => ['view', 'create', 'edit', 'delete', 'publish'],
    'media'        => ['view', 'create', 'edit', 'delete'],
    'kategorial'   => ['view', 'edit'],
    'berita'       => ['view', 'create', 'edit', 'delete', 'publish'],
    'kronik'       => ['view', 'create', 'edit', 'delete', 'publish'],
    'historia'     => ['view', 'create', 'edit', 'delete', 'publish'],
    'master'       => ['view', 'create', 'edit', 'delete'],
]);

define('PAGE_ACTION_LABELS', [
    'view'    => 'Lihat Saja',
    'create'  => 'Tambah Data',
    'edit'    => 'Edit Data',
    'delete'  => 'Hapus Data',
    'publish' => 'Publish',
]);
