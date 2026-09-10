<?php
/**
 * includes/kelompok_data.php — Supabase Edition
 * Menggantikan fetchSheet dari opensheet ke fetchSupabase
 *
 * Setiap kelompok memiliki 'supabaseTable' sebagai nama tabel Supabase,
 * menggantikan 'sheetName' yang dipakai untuk opensheet.
 */

// Defensive: kalau file ini di-include tanpa parent yang sudah load
// functions.php (yang menyediakan iconKategorialUrl), load di sini.
require_once __DIR__ . '/functions.php';

$kelompokData = [
    'adorasi' => [
        'nama'           => 'Adorasi',
        'subtitle'       => '(SMDTBA)',
        'icon'           => iconKategorialUrl('adorasi.png'),
        'supabaseTable'  => 'adorasi',      // ← ganti sheetName
        'deskripsi'      => 'Santa Maria Dengan Tidak Bernoda Asal',
    ],
    'pdkk' => [
        'nama'           => 'PDKK',
        'subtitle'       => '(SMDTBA) St. Christophorus',
        'icon'           => iconKategorialUrl('pdkk.png'),
        'supabaseTable'  => 'pdkk',
        'deskripsi'      => 'Persekutuan Doa Karismatik Katolik',
    ],
    'wanita-katolik' => [
        'nama'           => 'WKRI',
        'subtitle'       => '(SMDTBA)',
        'icon'           => iconKategorialUrl('wanita-katolik.png'),
        'supabaseTable'  => 'wanita_katolik',
        'deskripsi'      => 'Wanita Katolik Republik Indonesia',
    ],
    'gim' => [
        'nama'           => 'Gerakan Iman Maria',
        'subtitle'       => '(SMDTBA)',
        'icon'           => iconKategorialUrl('gim.png'),
        'supabaseTable'  => 'gim',
        'deskripsi'      => 'Doa Snakel',
    ],
    'legiomaria' => [
        'nama'           => 'Legio Maria',
        'subtitle'       => '(SMDTBA)',
        'icon'           => iconKategorialUrl('legiomaria.png'),
        'supabaseTable'  => 'legiomaria',
        'deskripsi'      => 'Santa Maria Dengan Tidak Bernoda Asal',
    ],
    'me' => [
        'nama'           => 'ME',
        'subtitle'       => '(SMDTBA)',
        'icon'           => iconKategorialUrl('me.png'),
        'supabaseTable'  => 'me',
        'deskripsi'      => 'Marriage Encounter',
    ],
    'pk' => [
        'nama'           => 'Pemuda Katolik',
        'subtitle'       => '(SMDTBA)',
        'icon'           => iconKategorialUrl('pk.png'),
        'supabaseTable'  => 'pk',
        'deskripsi'      => 'Santa Maria Dengan Tidak Bernoda Asal',
    ],
    'rosariohidup' => [
        'nama'           => 'Rosario Hidup',
        'subtitle'       => '(SMDTBA)',
        'icon'           => iconKategorialUrl('rosariohidup.png'),
        'supabaseTable'  => 'rosariohidup',
        'deskripsi'      => 'Santa Maria Dengan Tidak Bernoda Asal',
    ],
    'ktm' => [
        'nama'           => 'KTM',
        'subtitle'       => '(SMDTBA)',
        'icon'           => iconKategorialUrl('ktm.png'),
        'supabaseTable'  => 'ktm',
        'deskripsi'      => 'Komunitas Tritunggal Mahakudus',
    ],
    'ssvmaria' => [
        'nama'           => 'SSV St. Maria',
        'subtitle'       => '(SMDTBA)',
        'icon'           => iconKategorialUrl('ssvmaria.png'),
        'supabaseTable'  => 'ssvmaria',
        'deskripsi'      => 'Serikat Sosial Vinsensius',
    ],
    'ssvrosali' => [
        'nama'           => 'SSV Rosali',
        'subtitle'       => '(SMDTBA)',
        'icon'           => iconKategorialUrl('ssvrosali.png'),
        'supabaseTable'  => 'ssvrosali',
        'deskripsi'      => 'Serikat Sosial Vinsensius',
    ],
];

/**
 * Ambil data kelompok berdasarkan slug
 */
function getKelompokData(string $slug): ?array
{
    global $kelompokData;
    return $kelompokData[$slug] ?? null;
}

/**
 * Ambil URL API Supabase untuk tabel kelompok tertentu.
 * Menggantikan getKelompokApiUrl() yang menggunakan opensheet.
 */
function getKelompokSupabaseTable(string $slug): ?string
{
    global $kelompokData;
    return $kelompokData[$slug]['supabaseTable'] ?? null;
}

/**
 * Alias lama — sekarang mengembalikan URL Supabase REST API
 * agar kode lama yang memanggil getKelompokApiUrl() masih berfungsi.
 *
 * @deprecated Gunakan getKelompokSupabaseTable() + fetchSupabase()
 */
function getKelompokApiUrl(string $sheetName): string
{
    // Kembalikan URL Supabase REST API langsung
    return rtrim(SUPABASE_URL, '/') . '/rest/v1/' . $sheetName . '?select=*';
}

/**
 * Ambil semua kelompok sebagai array dengan slug
 */
function getAllKelompok(): array
{
    global $kelompokData;
    $result = [];
    foreach ($kelompokData as $slug => $data) {
        $result[] = array_merge(['slug' => $slug], $data);
    }
    return $result;
}
