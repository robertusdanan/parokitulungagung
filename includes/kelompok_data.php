<?php
/**
 * includes/kelompok_data.php — Supabase Edition
 * Menggantikan fetchSheet dari opensheet ke fetchSupabase
 *
 * Setiap kelompok memiliki 'supabaseTable' sebagai nama tabel Supabase,
 * menggantikan 'sheetName' yang dipakai untuk opensheet.
 */

$kelompokData = [
    'adorasi' => [
        'nama'           => 'Adorasi',
        'subtitle'       => '(SMDTBA)',
        'icon'           => '/img/icon/kategorial/adorasi.png',
        'supabaseTable'  => 'adorasi',      // ← ganti sheetName
        'deskripsi'      => 'Santa Maria Dengan Tidak Bernoda Asal',
    ],
    'pdkk' => [
        'nama'           => 'PDKK',
        'subtitle'       => '(SMDTBA) St. Christophorus',
        'icon'           => '/img/icon/kategorial/pdkk.png',
        'supabaseTable'  => 'pdkk',
        'deskripsi'      => 'Persekutuan Doa Karismatik Katolik',
    ],
    'wanita-katolik' => [
        'nama'           => 'WKRI',
        'subtitle'       => '(SMDTBA)',
        'icon'           => '/img/icon/kategorial/wanita-katolik.png',
        'supabaseTable'  => 'wanita_katolik',
        'deskripsi'      => 'Wanita Katolik Republik Indonesia',
    ],
    'gim' => [
        'nama'           => 'Gerakan Iman Maria',
        'subtitle'       => '(SMDTBA)',
        'icon'           => '/img/icon/kategorial/gim.png',
        'supabaseTable'  => 'gim',
        'deskripsi'      => 'Doa Snakel',
    ],
    'legiomaria' => [
        'nama'           => 'Legio Maria',
        'subtitle'       => '(SMDTBA)',
        'icon'           => '/img/icon/kategorial/legiomaria.png',
        'supabaseTable'  => 'legiomaria',
        'deskripsi'      => 'Santa Maria Dengan Tidak Bernoda Asal',
    ],
    'me' => [
        'nama'           => 'ME',
        'subtitle'       => '(SMDTBA)',
        'icon'           => '/img/icon/kategorial/me.png',
        'supabaseTable'  => 'me',
        'deskripsi'      => 'Marriage Encounter',
    ],
    'pk' => [
        'nama'           => 'Pemuda Katolik',
        'subtitle'       => '(SMDTBA)',
        'icon'           => '/img/icon/kategorial/pk.png',
        'supabaseTable'  => 'pk',
        'deskripsi'      => 'Santa Maria Dengan Tidak Bernoda Asal',
    ],
    'rosariohidup' => [
        'nama'           => 'Rosario Hidup',
        'subtitle'       => '(SMDTBA)',
        'icon'           => '/img/icon/kategorial/rosariohidup.png',
        'supabaseTable'  => 'rosariohidup',
        'deskripsi'      => 'Santa Maria Dengan Tidak Bernoda Asal',
    ],
    'ktm' => [
        'nama'           => 'KTM',
        'subtitle'       => '(SMDTBA)',
        'icon'           => '/img/icon/kategorial/ktm.png',
        'supabaseTable'  => 'ktm',
        'deskripsi'      => 'Komunitas Tritunggal Mahakudus',
    ],
    'ssvmaria' => [
        'nama'           => 'SSV St. Maria',
        'subtitle'       => '(SMDTBA)',
        'icon'           => '/img/icon/kategorial/ssvmaria.png',
        'supabaseTable'  => 'ssvmaria',
        'deskripsi'      => 'Serikat Sosial Vinsensius',
    ],
    'ssvrosali' => [
        'nama'           => 'SSV Rosali',
        'subtitle'       => '(SMDTBA)',
        'icon'           => '/img/icon/kategorial/ssvrosali.png',
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
