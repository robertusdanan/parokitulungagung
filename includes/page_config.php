<?php
/**
 * includes/page_config.php — Supabase Edition
 * sheetUrl (opensheet) diganti supabaseTable (nama tabel Supabase)
 *
 * Di pages/content.php, ganti:
 *   fetchSheet($config['sheetUrl'])
 * menjadi:
 *   fetchSupabase($config['supabaseTable'])
 */

$pageConfig = [
    'agenda' => [
        'title'            => 'Info Paroki',
        'menuTitle'        => 'Info Paroki',
        'icon'             => '/img/icon/icon_square_agenda.png',
        'headlineTitle'    => 'INFO PAROKI',
        'headlineSubtitle' => 'PAROKI SANTA MARIA DENGAN TIDAK BERNODA ASAL',
        'supabaseTable'    => 'info_paroki',       // ← ganti sheetUrl
        'supabaseOrder'    => 'tanggal.asc',
        'type'             => 'agenda',
        'showBottomBar'    => false,
    ],
    'berita' => [
        'title'         => 'Artikel',
        'menuTitle'     => 'Artikel',
        'menu'          => 'berita',
        'showBottomBar' => true,
        'type'          => 'artikel',
    ],
    'historia' => [
        'title'         => 'Historia Gereja',
        'menuTitle'     => 'Historia Gereja',
        'menu'          => 'historia',
        'showBottomBar' => false,
        'type'          => 'artikel',
    ],
    'kronik' => [
        'title'         => 'Kronik SMDTBA Tulungagung',
        'menuTitle'     => 'Kronik SMDTBA',
        'menu'          => 'kronik',
        'showBottomBar' => true,
        'type'          => 'artikel',
    ],
    'e-lonceng' => [
        'title'         => 'E-Lonceng',
        'menuTitle'     => 'E-Lonceng',
        'iframe'        => 'https://anyflip.com/bookcase/dxiqc',
        'showBottomBar' => false,
        'type'          => 'iframe',
    ],
    'umkmumat' => [
        'title'            => 'UMKM Umat',
        'menuTitle'        => 'UMKM Umat',
        'icon'             => '/img/icon/umkm.png',
        'headlineTitle'    => 'UMKM',
        'headlineSubtitle' => 'Umat Paroki SMDTBA Tulungagung',
        'showBottomBar'    => false,
        'type'             => 'canva',
    ],
    'galeri' => [
        'title'            => 'Galeri Foto',
        'menuTitle'        => 'Galeri Foto',
        'icon'             => '/img/icon/icon_square_foto.png',
        'headlineTitle'    => 'GALERI FOTO',
        'headlineSubtitle' => 'PAROKI SANTA MARIA DENGAN TIDAK BERNODA ASAL',
        'supabaseTable'    => 'galeri_foto',
        'supabaseOrder'    => 'Tanggal.desc',
        'showBottomBar'    => false,
        'type'             => 'galeri',
    ],
    'petugas' => [
        'title'            => 'Jadwal Petugas Liturgi',
        'menuTitle'        => 'Jadwal Petugas',
        'icon'             => '/img/icon/icon_square_petugas.png',
        'headlineTitle'    => 'JADWAL PETUGAS LITURGI',
        'headlineSubtitle' => 'TAHUN {year} - GEREJA SMDTBA',
        'supabaseTable'    => 'petugas',
        'supabaseOrder'    => 'id.asc',
        'showBottomBar'    => false,
        'type'             => 'petugas',
    ],
    'profil_lingkungan' => [
        'title'            => 'Wilayah',
        'menuTitle'        => 'Wilayah',
        'icon'             => '/img/icon/icon_square_lingkungan.png',
        'headlineTitle'    => 'PROFIL WILAYAH',
        'headlineSubtitle' => 'PAROKI SANTA MARIA DENGAN TIDAK BERNODA ASAL',
        'supabaseTable'    => 'daftar_wilayah',
        'supabaseOrder'    => 'Wilayah.asc',
        'showBottomBar'    => false,
        'type'             => 'profil_wilayah',
    ],
    'profil_ai' => [
        'title'            => 'Asisten Imam',
        'menuTitle'        => 'Asisten Imam',
        'icon'             => '/img/icon/icon_square_ai.png',
        'headlineTitle'    => 'ASISTEN IMAM',
        'headlineSubtitle' => 'PAROKI SANTA MARIA DENGAN TIDAK BERNODA ASAL',
        'supabaseTable'    => 'daftar_asisten_imam',
        'supabaseOrder'    => 'Nama.asc',
        'showBottomBar'    => false,
        'type'             => 'profil_ai',
    ],
    'profil_dpp' => [
        'title'            => 'DPP & BGKP',
        'menuTitle'        => 'DPP & BGKP',
        'icon'             => '/img/icon/icon_square_dpp.png',
        'headlineTitle'    => 'KEPENGURUSAN DPP & BGKP',
        'headlineSubtitle' => 'PAROKI SANTA MARIA DENGAN TIDAK BERNODA ASAL',
        'supabaseTable'    => 'kepengurusan_dpp_bgkp',
        'supabaseOrder'    => 'id.asc',
        'showBottomBar'    => false,
        'type'             => 'profil_dpp',
    ],
    'kategorial' => [
        'title'            => 'Kegiatan Kategorial',
        'menuTitle'        => 'Kategorial',
        'icon'             => '/img/icon/icon_square_kategorial3.png',
        'headlineTitle'    => 'KEGIATAN KELOMPOK KATEGORIAL',
        'headlineSubtitle' => 'DAN KEGIATAN LAINNYA',
        'showBottomBar'    => false,
        'type'             => 'kategorial',
    ],
];