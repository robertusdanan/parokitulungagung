<?php
/**
 * includes/R2Client.php
 * ─────────────────────────────────────────────────────────────────────────
 * Klien ringan (pure PHP, tanpa SDK/composer) untuk Cloudflare R2 lewat
 * S3-compatible API. Hanya diimplementasikan operasi READ-ONLY yang
 * dibutuhkan halaman galeri:
 *   - listObjects($prefix)  → daftar foto di dalam sebuah "folder" album
 *   - getObjectRaw($key)    → ambil isi 1 foto (dipakai oleh proxy gambar)
 *
 * Kredensial TIDAK di-hardcode di sini — selalu di-inject dari konstanta
 * yang didefinisikan di includes/config.php (yang mengambilnya dari
 * private/secrets.php, di luar public_html).
 *
 * Menggunakan AWS Signature Version 4 (SigV4), region "auto",
 * service "s3" — sesuai dokumentasi S3 API Cloudflare R2.
 * ─────────────────────────────────────────────────────────────────────────
 */

final class R2Client
{
    private string $accessKey;
    private string $secretKey;
    private string $endpoint;  // contoh: https://<account_id>.r2.cloudflarestorage.com
    private string $bucket;
    private string $region  = 'auto';
    private string $service = 's3';

    public function __construct(string $accessKey, string $secretKey, string $endpoint, string $bucket)
    {
        $this->accessKey = $accessKey;
        $this->secretKey = $secretKey;
        $this->endpoint  = rtrim($endpoint, '/');
        $this->bucket    = $bucket;
    }

    /**
     * List semua object di bawah sebuah prefix ("folder album"), auto-paginate
     * (ListObjectsV2 max 1000 key/halaman).
     *
     * @param string $prefix  Nama folder album, mis. "Rekoleksi Keluarga"
     * @return array<int,array{key:string,size:int,lastModified:string,etag:string}>
     */
    public function listObjects(string $prefix): array
    {
        $prefix = ltrim($prefix, '/');
        if ($prefix !== '' && !str_ends_with($prefix, '/')) $prefix .= '/';

        $all   = [];
        $token = null;

        do {
            $query = [
                'list-type' => '2',
                'prefix'    => $prefix,
                'max-keys'  => '1000',
            ];
            if ($token) $query['continuation-token'] = $token;

            $xmlBody = $this->signedRequest('GET', '/' . $this->bucket . '/', $query);
            if ($xmlBody === null) break;

            $doc = @simplexml_load_string($xmlBody);
            if ($doc === false) break;

            foreach ($doc->Contents ?? [] as $c) {
                $key = (string)$c->Key;
                // Lewati "folder placeholder" (key yang berakhir '/' tanpa nama file)
                if (str_ends_with($key, '/')) continue;
                $all[] = [
                    'key'          => $key,
                    'size'         => (int)$c->Size,
                    'lastModified' => (string)$c->LastModified,
                    'etag'         => trim((string)$c->ETag, '"'),
                ];
            }

            $isTruncated = strtolower((string)($doc->IsTruncated ?? 'false')) === 'true';
            $token = $isTruncated ? (string)$doc->NextContinuationToken : null;
        } while ($token);

        return $all;
    }

    /**
     * Ambil 1 object mentah dari R2 (dipakai oleh proxy streaming gambar).
     * Mengembalikan null kalau gagal / 404.
     *
     * @return array{status:int,body:string,contentType:string,etag:?string}|null
     */
    public function getObjectRaw(string $key): ?array
    {
        $path = '/' . $this->bucket . '/' . ltrim($key, '/');
        $host = parse_url($this->endpoint, PHP_URL_HOST);

        [$headers, $canonicalPath] = $this->buildSignedHeaders('GET', $path, []);
        $url = $this->endpoint . $canonicalPath;

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => true,
            CURLOPT_TIMEOUT        => 25,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $resp = curl_exec($ch);
        if ($resp === false) { curl_close($ch); return null; }

        $status     = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);

        $rawHeaders = substr($resp, 0, $headerSize);
        $body       = substr($resp, $headerSize);

        $contentType = 'application/octet-stream';
        if (preg_match('/^Content-Type:\s*(.+)$/mi', $rawHeaders, $m)) $contentType = trim($m[1]);
        $etag = null;
        if (preg_match('/^ETag:\s*"?([a-f0-9]+)"?/mi', $rawHeaders, $m)) $etag = $m[1];

        return ['status' => $status, 'body' => $body, 'contentType' => $contentType, 'etag' => $etag];
    }

    /**
     * Buat presigned URL (SigV4, query-string signing) untuk 1 object.
     * Dipakai supaya browser bisa download gambar UKURAN ASLI langsung dari
     * R2 — TIDAK lewat server hosting sama sekali (hemat storage & bandwidth
     * hosting). Kredensial tidak pernah terekspos; hanya signature sementara
     * yang expired otomatis setelah $expirySeconds.
     */
    public function getPresignedUrl(string $key, int $expirySeconds = 3600): string
    {
        $host             = parse_url($this->endpoint, PHP_URL_HOST);
        $now              = gmdate('Ymd\THis\Z');
        $dateStamp        = gmdate('Ymd');
        $credentialScope  = "{$dateStamp}/{$this->region}/{$this->service}/aws4_request";

        $path          = '/' . $this->bucket . '/' . ltrim($key, '/');
        $canonicalPath = $this->encodeUriPath($path);

        $query = [
            'X-Amz-Algorithm'     => 'AWS4-HMAC-SHA256',
            'X-Amz-Credential'    => $this->accessKey . '/' . $credentialScope,
            'X-Amz-Date'          => $now,
            'X-Amz-Expires'       => (string)$expirySeconds,
            'X-Amz-SignedHeaders' => 'host',
        ];
        ksort($query);
        $canonicalQuery = http_build_query($query, '', '&', PHP_QUERY_RFC3986);

        $canonicalHeaders = "host:{$host}\n";
        $signedHeaders    = 'host';
        // Presigned URL (query-string signing) memakai placeholder ini,
        // bukan hash body sungguhan — sesuai spesifikasi SigV4.
        $payloadHash = 'UNSIGNED-PAYLOAD';

        $canonicalRequest = implode("\n", [
            'GET', $canonicalPath, $canonicalQuery, $canonicalHeaders, $signedHeaders, $payloadHash,
        ]);

        $stringToSign = implode("\n", [
            'AWS4-HMAC-SHA256', $now, $credentialScope, hash('sha256', $canonicalRequest),
        ]);

        $kSecret   = 'AWS4' . $this->secretKey;
        $kDate     = hash_hmac('sha256', $dateStamp, $kSecret, true);
        $kRegion   = hash_hmac('sha256', $this->region, $kDate, true);
        $kService  = hash_hmac('sha256', $this->service, $kRegion, true);
        $kSigning  = hash_hmac('sha256', 'aws4_request', $kService, true);
        $signature = hash_hmac('sha256', $stringToSign, $kSigning);

        return $this->endpoint . $canonicalPath . '?' . $canonicalQuery . '&X-Amz-Signature=' . $signature;
    }

    /** GET request yang sudah ditandatangani → kembalikan body mentah (string) atau null. */
    private function signedRequest(string $method, string $path, array $query): ?string
    {
        [$headers, $canonicalPath, $canonicalQuery] = $this->buildSignedHeaders($method, $path, $query);
        $url = $this->endpoint . $canonicalPath . ($canonicalQuery ? '?' . $canonicalQuery : '');

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $resp   = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($resp === false || $status >= 400) return null;
        return $resp;
    }

    /**
     * Bangun header ter-signed (SigV4) + path/query yang sudah di-encode dengan benar.
     * @return array{0:array<int,string>,1:string,2:string} [headers, canonicalPath, canonicalQuery]
     */
    private function buildSignedHeaders(string $method, string $rawPath, array $query): array
    {
        $host        = parse_url($this->endpoint, PHP_URL_HOST);
        $now         = gmdate('Ymd\THis\Z');
        $dateStamp   = gmdate('Ymd');
        $payloadHash = hash('sha256', '');

        $canonicalPath = $this->encodeUriPath($rawPath);

        ksort($query);
        $canonicalQuery = http_build_query($query, '', '&', PHP_QUERY_RFC3986);

        $canonicalHeaders = "host:{$host}\nx-amz-content-sha256:{$payloadHash}\nx-amz-date:{$now}\n";
        $signedHeaders    = 'host;x-amz-content-sha256;x-amz-date';

        $canonicalRequest = implode("\n", [
            $method, $canonicalPath, $canonicalQuery, $canonicalHeaders, $signedHeaders, $payloadHash,
        ]);

        $credentialScope = "{$dateStamp}/{$this->region}/{$this->service}/aws4_request";
        $stringToSign = implode("\n", [
            'AWS4-HMAC-SHA256', $now, $credentialScope, hash('sha256', $canonicalRequest),
        ]);

        $kSecret   = 'AWS4' . $this->secretKey;
        $kDate     = hash_hmac('sha256', $dateStamp, $kSecret, true);
        $kRegion   = hash_hmac('sha256', $this->region, $kDate, true);
        $kService  = hash_hmac('sha256', $this->service, $kRegion, true);
        $kSigning  = hash_hmac('sha256', 'aws4_request', $kService, true);
        $signature = hash_hmac('sha256', $stringToSign, $kSigning);

        $authHeader = "AWS4-HMAC-SHA256 Credential={$this->accessKey}/{$credentialScope}, "
                    . "SignedHeaders={$signedHeaders}, Signature={$signature}";

        $headers = [
            "Host: {$host}",
            "x-amz-content-sha256: {$payloadHash}",
            "x-amz-date: {$now}",
            "Authorization: {$authHeader}",
        ];

        return [$headers, $canonicalPath, $canonicalQuery];
    }

    /** Encode tiap segmen path (bukan '/'-nya) sesuai aturan URI AWS SigV4. */
    private function encodeUriPath(string $path): string
    {
        $segments = explode('/', $path);
        foreach ($segments as &$seg) {
            // rawurlencode lalu perbaiki karakter yang tidak boleh di-encode AWS ('~')
            $seg = str_replace('%7E', '~', rawurlencode($seg));
        }
        return implode('/', $segments);
    }
}
