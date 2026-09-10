<?php
/**
 * includes/UserManager.php (publik)
 * Wrapper ringan untuk kebutuhan publik — Supabase Edition
 *
 * File ini TIDAK dipakai oleh admin panel (admin punya versi sendiri di
 * admin/includes/UserManager.php). File ini hanya untuk kebutuhan publik
 * seperti: cek status user, ambil data author artikel, dsb.
 *
 * Karena halaman publik tidak butuh login/manajemen user secara langsung,
 * file ini hanya menyediakan fungsi-fungsi baca (read-only) via Supabase.
 *
 * Catatan: SheetsClient dihapus sepenuhnya. Semua akses ke data user
 * sekarang melalui Supabase REST API.
 */

if (!function_exists('fetchUserByUsername')) {

    /**
     * Cari user berdasarkan username (case-insensitive) via Supabase.
     * Dipakai misalnya untuk ambil foto profil penulis di artikel-detail.php.
     *
     * @return array|null Data user (tanpa password_hash), atau null jika tidak ditemukan
     */
    function fetchUserByUsername(string $username): ?array
    {
        if (!defined('SUPABASE_URL') || !defined('SUPABASE_ANON_KEY')) return null;

        $url = rtrim(SUPABASE_URL, '/') . '/rest/v1/users'
             . '?username=ilike.' . urlencode($username)
             . '&select=id,username,nama,email,role&limit=1';

        $headers = [
            'apikey: '        . SUPABASE_ANON_KEY,
            'Authorization: Bearer ' . SUPABASE_ANON_KEY,
            'Accept: application/json',
        ];

        $json = null;

        // Coba file_get_contents dulu
        $ctx = stream_context_create([
            'http' => [
                'header'        => implode("\r\n", $headers) . "\r\n",
                'timeout'       => 10,
                'ignore_errors' => true,
            ],
            'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
        ]);
        $result = @file_get_contents($url, false, $ctx);
        if ($result !== false) $json = $result;

        // Fallback cURL
        if ($json === null && function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER     => $headers,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_TIMEOUT        => 10,
            ]);
            $json = curl_exec($ch);
            curl_close($ch);
        }

        if (!$json) return null;
        $decoded = json_decode($json, true);
        if (!is_array($decoded) || empty($decoded)) return null;

        return $decoded[0];
    }

    /**
     * Cari user berdasarkan ID via Supabase.
     *
     * @return array|null Data user (tanpa password_hash), atau null jika tidak ditemukan
     */
    function fetchUserById(string $id): ?array
    {
        if (!defined('SUPABASE_URL') || !defined('SUPABASE_ANON_KEY')) return null;

        $url = rtrim(SUPABASE_URL, '/') . '/rest/v1/users'
             . '?id=eq.' . urlencode($id)
             . '&select=id,username,nama,email,role&limit=1';

        $headers = [
            'apikey: '        . SUPABASE_ANON_KEY,
            'Authorization: Bearer ' . SUPABASE_ANON_KEY,
            'Accept: application/json',
        ];

        $json = null;

        $ctx = stream_context_create([
            'http' => [
                'header'        => implode("\r\n", $headers) . "\r\n",
                'timeout'       => 10,
                'ignore_errors' => true,
            ],
            'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
        ]);
        $result = @file_get_contents($url, false, $ctx);
        if ($result !== false) $json = $result;

        if ($json === null && function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER     => $headers,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_TIMEOUT        => 10,
            ]);
            $json = curl_exec($ch);
            curl_close($ch);
        }

        if (!$json) return null;
        $decoded = json_decode($json, true);
        if (!is_array($decoded) || empty($decoded)) return null;

        return $decoded[0];
    }

    /**
     * Ambil semua user (read-only, tanpa password_hash).
     * Dipakai jika ada kebutuhan menampilkan daftar penulis di publik.
     *
     * @return array Array of user data
     */
    function fetchAllUsers(): array
    {
        if (!defined('SUPABASE_URL') || !defined('SUPABASE_ANON_KEY')) return [];

        $url = rtrim(SUPABASE_URL, '/') . '/rest/v1/users'
             . '?select=id,username,nama,email,role&is_active=eq.true&order=created_at.asc';

        $headers = [
            'apikey: '        . SUPABASE_ANON_KEY,
            'Authorization: Bearer ' . SUPABASE_ANON_KEY,
            'Accept: application/json',
        ];

        $json = null;

        $ctx = stream_context_create([
            'http' => [
                'header'        => implode("\r\n", $headers) . "\r\n",
                'timeout'       => 10,
                'ignore_errors' => true,
            ],
            'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
        ]);
        $result = @file_get_contents($url, false, $ctx);
        if ($result !== false) $json = $result;

        if ($json === null && function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER     => $headers,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_TIMEOUT        => 10,
            ]);
            $json = curl_exec($ch);
            curl_close($ch);
        }

        if (!$json) return [];
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : [];
    }
}