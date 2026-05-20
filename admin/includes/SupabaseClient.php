<?php
/**
 * admin/includes/SupabaseClient.php
 * Client Supabase REST API — pengganti SheetsClient.php
 *
 * Semua operasi CRUD ke Supabase menggunakan PostgREST API.
 * Kompatibel dengan InfinityFree (cURL).
 */

class SupabaseClient
{
    private string $url;
    private string $key;

    public function __construct()
    {
        $this->url = rtrim(SUPABASE_URL, '/');
        $this->key = SUPABASE_SERVICE_KEY;

        if (!function_exists('curl_init')) {
            throw new RuntimeException('cURL tidak tersedia di server ini.');
        }
    }

    // ── HTTP Helper ──────────────────────────────────────────────────────

    /**
     * Kirim HTTP request ke Supabase PostgREST.
     *
     * @param string $method   GET | POST | PATCH | DELETE
     * @param string $endpoint Misal: /rest/v1/users
     * @param array  $query    Query string params (filter, order, dll)
     * @param array  $body     Body data (untuk POST/PATCH)
     * @param array  $extra    Header tambahan (misal Prefer: return=representation)
     * @return array           Decoded JSON response
     */
    public function request(
        string $method,
        string $endpoint,
        array  $query  = [],
        array  $body   = [],
        array  $extra  = []
    ): array {
        $url = $this->url . $endpoint;
        if ($query) {
            $url .= '?' . http_build_query($query);
        }

        $headers = array_merge([
            'apikey: '        . $this->key,
            'Authorization: Bearer ' . $this->key,
            'Content-Type: application/json',
            'Accept: application/json',
        ], $extra);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT      => 'ParokiSMDTBA/2.0 PHP/' . PHP_VERSION,
        ]);

        $method = strtoupper($method);
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        } elseif (in_array($method, ['PATCH', 'PUT', 'DELETE'])) {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            if ($body) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
            }
        }

        $result = curl_exec($ch);
        $errno  = curl_errno($ch);
        $err    = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // SSL fallback (umum di shared hosting)
        if ($errno === CURLE_SSL_CONNECT_ERROR || $errno === 60) {
            $ch2 = curl_init($url);
            curl_setopt_array($ch2, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER     => $headers,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_TIMEOUT        => 20,
                CURLOPT_CUSTOMREQUEST  => $method,
                CURLOPT_USERAGENT      => 'ParokiSMDTBA/2.0 PHP/' . PHP_VERSION,
            ]);
            if (in_array($method, ['POST', 'PATCH', 'PUT', 'DELETE']) && $body) {
                curl_setopt($ch2, CURLOPT_POSTFIELDS, json_encode($body));
            }
            $result   = curl_exec($ch2);
            $httpCode = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
            $err2     = curl_error($ch2);
            curl_close($ch2);
            if ($result === false) {
                throw new RuntimeException("cURL error (SSL fallback): $err2");
            }
        } elseif ($result === false) {
            throw new RuntimeException("cURL error ($errno): $err");
        }

        $decoded = json_decode($result, true);

        // Supabase mengembalikan array kosong [] untuk DELETE/PATCH tanpa Prefer: return
        if ($httpCode >= 400) {
            $msg = $decoded['message'] ?? $decoded['error'] ?? $result;
            throw new RuntimeException("Supabase error [{$httpCode}]: $msg");
        }

        return is_array($decoded) ? $decoded : [];
    }

    // ── Public CRUD API ──────────────────────────────────────────────────

    /**
     * SELECT — baca semua baris dari tabel.
     *
     * @param string $table   Nama tabel Supabase
     * @param array  $filters Misal: ['kolom' => 'nilai'] → eq filter
     * @param string $order   Misal: 'id.asc' atau 'created_at.desc'
     * @param string $select  Kolom yang di-select (default '*')
     * @return array
     */
    public function read(
        string $table,
        array  $filters = [],
        string $order   = '',
        string $select  = '*',
        int    $limit   = 0
    ): array {
        $query = ['select' => $select];
        foreach ($filters as $col => $val) {
            $query[$col] = 'eq.' . $val;
        }
        if ($order) $query['order'] = $order;
        if ($limit > 0) $query['limit'] = $limit;
        return $this->request('GET', '/rest/v1/' . $table, $query);
    }

    /**
     * SELECT dengan filter bebas (raw query params).
     * Misal: readWhere('users', ['is_active=eq.1', 'role=eq.admin'])
     */
    public function readWhere(string $table, array $rawFilters = [], string $order = '', string $select = '*'): array
    {
        $url = $this->url . '/rest/v1/' . $table;
        $params = ['select' => $select];
        if ($order) $params['order'] = $order;

        $queryStr = http_build_query($params);
        if ($rawFilters) {
            $queryStr .= '&' . implode('&', $rawFilters);
        }

        $headers = [
            'apikey: '        . $this->key,
            'Authorization: Bearer ' . $this->key,
            'Accept: application/json',
        ];

        $ch = curl_init($url . '?' . $queryStr);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT        => 20,
        ]);
        $result = curl_exec($ch);
        curl_close($ch);
        $decoded = json_decode($result, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * INSERT — tambah baris baru.
     *
     * @param string $table Nama tabel
     * @param array  $data  Associative array kolom → nilai
     * @return array        Data yang baru diinsert (dengan id, dll)
     */
    public function insert(string $table, array $data): array
    {
        $result = $this->request(
            'POST',
            '/rest/v1/' . $table,
            [],
            $data,
            ['Prefer: return=representation']
        );
        return $result[0] ?? $data;
    }

    /**
     * UPDATE — update baris berdasarkan filter.
     *
     * @param string $table   Nama tabel
     * @param string $col     Kolom filter (misal 'id')
     * @param mixed  $val     Nilai filter
     * @param array  $data    Data yang di-update
     * @return array          Data setelah update
     */
    public function update(string $table, string $col, $val, array $data): array
    {
        $result = $this->request(
            'PATCH',
            '/rest/v1/' . $table,
            [$col => 'eq.' . $val],
            $data,
            ['Prefer: return=representation']
        );
        return $result[0] ?? $data;
    }

    /**
     * DELETE — hapus baris berdasarkan filter.
     *
     * @param string $table Nama tabel
     * @param string $col   Kolom filter (misal 'id')
     * @param mixed  $val   Nilai filter
     */
    public function delete(string $table, string $col, $val): void
    {
        $this->request(
            'DELETE',
            '/rest/v1/' . $table,
            [$col => 'eq.' . $val]
        );
    }

    /**
     * COUNT — hitung jumlah baris (gunakan Prefer: count=exact).
     */
    public function count(string $table, array $filters = []): int
    {
        $query = ['select' => 'id'];
        foreach ($filters as $col => $val) {
            $query[$col] = 'eq.' . $val;
        }
        $url = $this->url . '/rest/v1/' . $table . '?' . http_build_query($query);
        $headers = [
            'apikey: '        . $this->key,
            'Authorization: Bearer ' . $this->key,
            'Prefer: count=exact',
            'Accept: application/json',
        ];
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_HEADER         => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT        => 10,
        ]);
        $response  = curl_exec($ch);
        curl_close($ch);
        if (preg_match('/Content-Range:\s*\*\/(\d+)/i', $response, $m)) {
            return (int) $m[1];
        }
        return 0;
    }
}
