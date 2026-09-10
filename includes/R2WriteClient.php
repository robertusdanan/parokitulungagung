<?php
/**
 * includes/R2WriteClient.php
 * ─────────────────────────────────────────────────────────────────────────
 * Klien Cloudflare R2 (S3-compatible API) dengan kemampuan TULIS (PUT,
 * multipart upload, DELETE, batch delete) + LIST, ditulis manual pakai
 * cURL + AWS Signature V4 — sama seperti includes/R2Client.php (read-only)
 * tapi versi ini dipakai KHUSUS oleh admin panel (Media Manager > Cloudflare
 * R2), butuh API Token R2 dengan izin "Object Read & Write".
 *
 * JANGAN dipakai di halaman publik — kredensial yang dipakai di sini WAJIB
 * kredensial write-capable (beda dari R2_ACCESS_KEY read-only yang dipakai
 * untuk galeri publik).
 *
 * Payload SELALU di-stream langsung dari disk (CURLOPT_UPLOAD + CURLOPT_INFILE)
 * supaya upload video besar tetap aman untuk memory PHP-FPM di shared hosting.
 * ─────────────────────────────────────────────────────────────────────────
 */

final class R2WriteClient
{
    private const MULTIPART_THRESHOLD = 4 * 1024 * 1024 * 1024; // 4 GB
    private const MULTIPART_PART_SIZE = 100 * 1024 * 1024;      // 100 MB per part

    private string $accessKey;
    private string $secretKey;
    private string $endpoint;
    private string $bucket;
    private string $region  = 'auto';
    private string $service = 's3';
    private string $host;

    public function __construct(string $accessKey, string $secretKey, string $endpoint, string $bucket)
    {
        $this->accessKey = $accessKey;
        $this->secretKey = $secretKey;
        $this->endpoint  = rtrim($endpoint, '/');
        $this->bucket    = $bucket;
        $parsed = parse_url($this->endpoint);
        $this->host = ($parsed['host'] ?? '') . (isset($parsed['port']) ? ':' . $parsed['port'] : '');
    }

    // ═══════════════════════════ PUBLIC API ═══════════════════════════

    /** Cek apakah object sudah ada. Return ukurannya (bytes) atau null kalau tidak ada. */
    public function headObjectSize(string $key): ?int
    {
        $res = $this->request('HEAD', $key, [], null, null);
        if ($res['code'] === 200) {
            foreach ($res['responseHeaders'] as $name => $value) {
                if (strtolower($name) === 'content-length') return (int)$value;
            }
        }
        return null;
    }

    /**
     * Upload 1 file lokal ke key tertentu (auto pilih PUT biasa / multipart
     * tergantung ukuran). $metadata → header x-amz-meta-*.
     * @throws R2WriteException
     */
    public function putObjectFromFile(string $key, string $localPath, string $contentType, array $metadata = []): void
    {
        $size = filesize($localPath);
        if ($size === false) throw new R2WriteException("Tidak dapat membaca ukuran file: $localPath");

        $metaHeaders = [];
        $standardHeaders = ['cache-control', 'content-disposition', 'content-encoding', 'content-language', 'expires'];
        foreach ($metadata as $name => $value) {
            if ($value === null || $value === '') continue;
            $lowerName = strtolower($name);
            if (in_array($lowerName, $standardHeaders, true)) {
                $headerName = match($lowerName) {
                    'cache-control'       => 'Cache-Control',
                    'content-disposition' => 'Content-Disposition',
                    'content-encoding'    => 'Content-Encoding',
                    'content-language'    => 'Content-Language',
                    'expires'             => 'Expires',
                };
                $metaHeaders[$headerName] = (string)$value;
            } else {
                $metaHeaders['x-amz-meta-' . $name] = rawurlencode((string)$value);
            }
        }

        // Suntikkan default Cache-Control: 1 tahun penuh, immutable (tidak pernah berubah)
        if (!isset($metaHeaders['Cache-Control'])) {
            $metaHeaders['Cache-Control'] = 'public, max-age=31536000, immutable';
        }

        if ($size > self::MULTIPART_THRESHOLD) {
            $this->multipartUpload($key, $localPath, $contentType, $size, $metaHeaders);
            return;
        }
        $this->putSmall($key, $localPath, $contentType, $size, $metaHeaders);
    }

    /**
     * Copy 1 object DI DALAM bucket yang sama, dari $sourceKey ke $destKey,
     * server-side (lewat CopyObject S3 API) — TIDAK men-download+upload ulang
     * bytes-nya lewat PHP, jadi cepat & hemat bandwidth hosting. Dipakai untuk
     * migrasi struktur folder (mis. pindahkan album dari root bucket ke bawah
     * prefix baru) tanpa perlu menyentuh isi file sama sekali.
     *
     * Object tujuan akan menimpa object lain dengan key yang sama kalau sudah
     * ada — cek dulu pakai headObjectSize() di sisi pemanggil kalau perlu
     * skip-if-exists.
     * @throws R2WriteException
     */
    public function copyObject(string $sourceKey, string $destKey): void
    {
        $copySource = self::canonicalUriFor($this->bucket, $sourceKey);

        $attempt = 0;
        while (true) {
            $attempt++;
            $res = $this->request('PUT', $destKey, [], null, null, [
                'x-amz-copy-source' => $copySource,
            ]);

            if ($res['ok'] && $res['code'] === 200) return;
            if ($this->shouldRetry($res, $attempt)) { sleep(min(2, $attempt)); continue; }

            throw new R2WriteException("Copy gagal '$sourceKey' -> '$destKey' (HTTP {$res['code']}): " . ($res['error'] ?: $res['body']));
        }
    }

    /** Hapus 1 object. Return true kalau sukses (204/200) atau memang sudah tidak ada (404 dianggap sukses). */
    public function deleteObject(string $key): bool
    {
        $res = $this->request('DELETE', $key, [], null, null);
        return $res['ok'] && in_array($res['code'], [200, 204, 404], true);
    }

    /** Hapus banyak object sekaligus (maks 1000/batch, dipecah otomatis). */
    public function deleteObjectsBatch(array $keys): array
    {
        $deleted = [];
        $failed  = [];
        foreach (array_chunk($keys, 1000) as $chunk) {
            $xml = '<Delete>';
            foreach ($chunk as $k) {
                $xml .= '<Object><Key>' . htmlspecialchars($k, ENT_XML1) . '</Key></Object>';
            }
            $xml .= '</Delete>';
            $bodyHash = hash('sha256', $xml);
            $res = $this->request('POST', '', ['delete' => ''], $xml, null, ['Content-MD5' => base64_encode(md5($xml, true))], $bodyHash);
            if ($res['ok'] && $res['code'] === 200) {
                $deleted = array_merge($deleted, $chunk);
            } else {
                $failed = array_merge($failed, $chunk);
            }
        }
        return ['deleted' => $deleted, 'failed' => $failed];
    }

    /**
     * List HANYA nama folder tingkat atas (mis. nama album) di bawah prefix
     * tertentu, pakai delimiter '/' (ListObjectsV2 CommonPrefixes) — TIDAK
     * meng-enumerasi objek di dalam tiap folder, jadi jauh lebih murah
     * dibanding listAllObjects() kalau yang dibutuhkan cuma daftar nama
     * (dipakai untuk lazy-load: tampilkan semua nama album dulu, baru hitung
     * jumlah file & ukuran per album belakangan, HANYA untuk halaman yang
     * sedang aktif — lihat R2FolderCompressor... eh, lihat r2_manager.php).
     * @return array<int,string>
     */
    public function listFolderNames(string $prefix = ''): array
    {
        $names = [];
        $token = null;
        do {
            $query = ['list-type' => '2', 'max-keys' => '1000', 'delimiter' => '/'];
            if ($prefix !== '') $query['prefix'] = $prefix;
            if ($token) $query['continuation-token'] = $token;

            $res = $this->request('GET', '', $query, null, null);
            if (!$res['ok'] || $res['code'] !== 200) break;

            $doc = @simplexml_load_string($res['body']);
            if ($doc === false) break;

            foreach ($doc->CommonPrefixes ?? [] as $cp) {
                $p = rtrim((string)$cp->Prefix, '/');
                $slash = strrpos($p, '/');
                $names[] = $slash === false ? $p : substr($p, $slash + 1);
            }

            $isTruncated = strtolower((string)($doc->IsTruncated ?? 'false')) === 'true';
            $token = $isTruncated ? (string)$doc->NextContinuationToken : null;
        } while ($token);

        return $names;
    }

    /**
     * List SEMUA object di bucket (auto-paginate ListObjectsV2). Untuk bucket
     * besar ini bisa beberapa request (max-keys 1000/halaman) tapi tetap
     * jauh lebih murah dibanding GetObject satu-satu.
     * @return array<int,array{key:string,size:int,lastModified:string}>
     */
    public function listAllObjects(string $prefix = ''): array
    {
        $all = [];
        $token = null;
        do {
            $query = ['list-type' => '2', 'max-keys' => '1000'];
            if ($prefix !== '') $query['prefix'] = $prefix;
            if ($token) $query['continuation-token'] = $token;

            $res = $this->request('GET', '', $query, null, null);
            if (!$res['ok'] || $res['code'] !== 200) break;

            $doc = @simplexml_load_string($res['body']);
            if ($doc === false) break;

            foreach ($doc->Contents ?? [] as $c) {
                $key = (string)$c->Key;
                if ($key === '' || str_ends_with($key, '/')) continue; // skip folder placeholder
                $all[] = ['key' => $key, 'size' => (int)$c->Size, 'lastModified' => (string)$c->LastModified];
            }

            $isTruncated = strtolower((string)($doc->IsTruncated ?? 'false')) === 'true';
            $token = $isTruncated ? (string)$doc->NextContinuationToken : null;
        } while ($token);

        return $all;
    }

    /** Buat presigned URL sementara (dipakai untuk thumbnail preview di panel admin). */
    public function getPresignedUrl(string $key, int $expirySeconds = 3600): string
    {
        $now             = gmdate('Ymd\THis\Z');
        $dateStamp       = gmdate('Ymd');
        $credentialScope = "{$dateStamp}/{$this->region}/{$this->service}/aws4_request";
        $canonicalPath   = self::canonicalUriFor($this->bucket, $key);

        $query = [
            'X-Amz-Algorithm'     => 'AWS4-HMAC-SHA256',
            'X-Amz-Credential'    => $this->accessKey . '/' . $credentialScope,
            'X-Amz-Date'          => $now,
            'X-Amz-Expires'       => (string)$expirySeconds,
            'X-Amz-SignedHeaders' => 'host',
        ];
        ksort($query);
        $canonicalQuery = http_build_query($query, '', '&', PHP_QUERY_RFC3986);

        $canonicalHeaders = "host:{$this->host}\n";
        $payloadHash = 'UNSIGNED-PAYLOAD';
        $canonicalRequest = implode("\n", ['GET', $canonicalPath, $canonicalQuery, $canonicalHeaders, 'host', $payloadHash]);
        $stringToSign = implode("\n", ['AWS4-HMAC-SHA256', $now, $credentialScope, hash('sha256', $canonicalRequest)]);
        $signature = hash_hmac('sha256', $stringToSign, $this->signingKey($dateStamp));

        return $this->endpoint . $canonicalPath . '?' . $canonicalQuery . '&X-Amz-Signature=' . $signature;
    }

    /** Buat presigned PUT URL sementara untuk upload direct dari browser ke R2. */
    public function getPresignedPutUrl(string $key, int $expirySeconds = 3600): string
    {
        $now             = gmdate('Ymd\THis\Z');
        $dateStamp       = gmdate('Ymd');
        $credentialScope = "{$dateStamp}/{$this->region}/{$this->service}/aws4_request";
        $canonicalPath   = self::canonicalUriFor($this->bucket, $key);

        $query = [
            'X-Amz-Algorithm'     => 'AWS4-HMAC-SHA256',
            'X-Amz-Credential'    => $this->accessKey . '/' . $credentialScope,
            'X-Amz-Date'          => $now,
            'X-Amz-Expires'       => (string)$expirySeconds,
            'X-Amz-SignedHeaders' => 'host',
        ];
        ksort($query);
        $canonicalQuery = http_build_query($query, '', '&', PHP_QUERY_RFC3986);

        $canonicalHeaders = "host:{$this->host}\n";
        $payloadHash = 'UNSIGNED-PAYLOAD';
        $canonicalRequest = implode("\n", ['PUT', $canonicalPath, $canonicalQuery, $canonicalHeaders, 'host', $payloadHash]);
        $stringToSign = implode("\n", ['AWS4-HMAC-SHA256', $now, $credentialScope, hash('sha256', $canonicalRequest)]);
        $signature = hash_hmac('sha256', $stringToSign, $this->signingKey($dateStamp));

        return $this->endpoint . $canonicalPath . '?' . $canonicalQuery . '&X-Amz-Signature=' . $signature;
    }

    // ═══════════════════════════ INTERNAL ═══════════════════════════

    private function putSmall(string $key, string $localPath, string $contentType, int $size, array $metaHeaders): void
    {
        $attempt = 0;
        while (true) {
            $attempt++;
            $fp = fopen($localPath, 'rb');
            if ($fp === false) throw new R2WriteException("Tidak dapat membuka file: $localPath");

            $res = $this->request('PUT', $key, [], $fp, $size, ['Content-Type' => $contentType] + $metaHeaders);
            if (is_resource($fp)) fclose($fp);

            if ($res['ok'] && $res['code'] === 200) return;
            if ($this->shouldRetry($res, $attempt)) { sleep(min(2, $attempt)); continue; }

            throw new R2WriteException("Upload gagal '$key' (HTTP {$res['code']}): " . ($res['error'] ?: $res['body']));
        }
    }

    private function multipartUpload(string $key, string $localPath, string $contentType, int $size, array $metaHeaders): void
    {
        $res = $this->request('POST', $key, ['uploads' => ''], null, null, ['Content-Type' => $contentType] + $metaHeaders);
        if (!$res['ok'] || $res['code'] !== 200 || preg_match('/<UploadId>(.*?)<\/UploadId>/s', $res['body'], $m) !== 1) {
            throw new R2WriteException("Gagal memulai multipart upload '$key'");
        }
        $uploadId = $m[1];
        $parts = [];
        $partNumber = 0;
        $offset = 0;

        try {
            $fp = fopen($localPath, 'rb');
            if ($fp === false) throw new R2WriteException("Tidak dapat membuka file: $localPath");

            while ($offset < $size) {
                $partNumber++;
                $partSize = min(self::MULTIPART_PART_SIZE, $size - $offset);
                fseek($fp, $offset);

                $attempt = 0;
                while (true) {
                    $attempt++;
                    fseek($fp, $offset);
                    $r = $this->request('PUT', $key, ['partNumber' => (string)$partNumber, 'uploadId' => $uploadId], $fp, $partSize);
                    if ($r['ok'] && $r['code'] === 200) {
                        $etag = null;
                        foreach ($r['responseHeaders'] as $n => $v) if (strtolower($n) === 'etag') $etag = trim($v, " \t\"");
                        if ($etag === null) throw new R2WriteException("Part $partNumber '$key' sukses tapi ETag tidak ditemukan.");
                        $parts[] = ['PartNumber' => $partNumber, 'ETag' => $etag];
                        break;
                    }
                    if ($this->shouldRetry($r, $attempt)) { sleep(min(2, $attempt)); continue; }
                    throw new R2WriteException("Upload part $partNumber gagal '$key' (HTTP {$r['code']})");
                }
                $offset += $partSize;
            }
            fclose($fp);

            $xml = '<CompleteMultipartUpload>';
            foreach ($parts as $p) $xml .= '<Part><PartNumber>' . $p['PartNumber'] . '</PartNumber><ETag>' . htmlspecialchars($p['ETag'], ENT_XML1) . '</ETag></Part>';
            $xml .= '</CompleteMultipartUpload>';

            $c = $this->request('POST', $key, ['uploadId' => $uploadId], $xml, null);
            if (!$c['ok'] || $c['code'] !== 200) throw new R2WriteException("Gagal menyelesaikan multipart upload '$key'");
        } catch (\Throwable $e) {
            try { $this->request('DELETE', $key, ['uploadId' => $uploadId], null, null); } catch (\Throwable) {}
            throw $e;
        }
    }

    private function shouldRetry(array $res, int $attempt): bool
    {
        if ($attempt >= 3) return false;
        if ($res['error'] !== '') return true;
        return $res['code'] === 429 || $res['code'] >= 500;
    }

    /**
     * @param resource|string|null $body
     */
    private function request(string $method, string $key, array $query, $body, ?int $bodySize, array $extraHeaders = [], ?string $bodyHashOverride = null): array
    {
        $canonicalUri   = $key === '' ? '/' . $this->bucket . '/' : self::canonicalUriFor($this->bucket, $key);
        $canonicalQuery = self::canonicalQueryString($query);

        $isStream = is_resource($body);
        $payloadHash = $bodyHashOverride ?? ($body === null || $isStream ? 'UNSIGNED-PAYLOAD' : hash('sha256', (string)$body));

        $now = gmdate('Ymd\THis\Z');
        $dateStamp = gmdate('Ymd');
        $credentialScope = "{$dateStamp}/{$this->region}/{$this->service}/aws4_request";

        $headersToSign = ['host' => $this->host, 'x-amz-content-sha256' => $payloadHash];
        ksort($headersToSign);
        $canonicalHeaders = '';
        foreach ($headersToSign as $n => $v) $canonicalHeaders .= "$n:$v\n";
        $signedHeaders = implode(';', array_keys($headersToSign));

        $canonicalRequest = implode("\n", [$method, $canonicalUri, $canonicalQuery, $canonicalHeaders, $signedHeaders, $payloadHash]);
        $stringToSign = implode("\n", ['AWS4-HMAC-SHA256', $now, $credentialScope, hash('sha256', $canonicalRequest)]);
        $signature = hash_hmac('sha256', $stringToSign, $this->signingKey($dateStamp));

        $authHeader = "AWS4-HMAC-SHA256 Credential={$this->accessKey}/{$credentialScope}, SignedHeaders={$signedHeaders}, Signature={$signature}";

        $httpHeaders = [
            'Host: ' . $this->host,
            'x-amz-content-sha256: ' . $payloadHash,
            'x-amz-date: ' . $now,
            'Authorization: ' . $authHeader,
        ];
        foreach ($extraHeaders as $n => $v) $httpHeaders[] = "$n: $v";

        $url = 'https://' . $this->host . $canonicalUri . ($canonicalQuery !== '' ? '?' . $canonicalQuery : '');

        $ch = curl_init($url);
        $opts = [
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => false,
            CURLOPT_TIMEOUT        => 0,
            CURLOPT_CONNECTTIMEOUT => 20,
            CURLOPT_SSL_VERIFYPEER => true,
        ];
        $responseHeaders = [];
        $opts[CURLOPT_HEADERFUNCTION] = static function ($ch, string $line) use (&$responseHeaders): int {
            $parts = explode(':', $line, 2);
            if (count($parts) === 2) $responseHeaders[trim($parts[0])] = trim($parts[1]);
            return strlen($line);
        };

        if ($method === 'HEAD') $opts[CURLOPT_NOBODY] = true;

        if ($isStream) {
            $httpHeaders[] = 'Content-Length: ' . $bodySize;
            $opts[CURLOPT_UPLOAD]     = true;
            $opts[CURLOPT_INFILE]     = $body;
            $opts[CURLOPT_INFILESIZE] = $bodySize;
        } elseif (is_string($body)) {
            $httpHeaders[] = 'Content-Length: ' . strlen($body);
            $opts[CURLOPT_POSTFIELDS] = $body;
        } elseif ($method === 'PUT' || $method === 'POST') {
            $httpHeaders[] = 'Content-Length: 0';
        }

        $opts[CURLOPT_HTTPHEADER] = $httpHeaders;
        curl_setopt_array($ch, $opts);

        $res  = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        return ['ok' => $err === '', 'code' => $code, 'body' => is_string($res) ? $res : '', 'error' => $err, 'responseHeaders' => $responseHeaders];
    }

    private function signingKey(string $dateStamp): string
    {
        $kDate    = hash_hmac('sha256', $dateStamp, 'AWS4' . $this->secretKey, true);
        $kRegion  = hash_hmac('sha256', $this->region, $kDate, true);
        $kService = hash_hmac('sha256', $this->service, $kRegion, true);
        return hash_hmac('sha256', 'aws4_request', $kService, true);
    }

    private static function uriEncode(string $value, bool $encodeSlash): string
    {
        $result = '';
        for ($i = 0, $len = strlen($value); $i < $len; $i++) {
            $c = $value[$i];
            if (($c >= 'A' && $c <= 'Z') || ($c >= 'a' && $c <= 'z') || ($c >= '0' && $c <= '9') || $c === '-' || $c === '_' || $c === '.' || $c === '~') {
                $result .= $c;
            } elseif ($c === '/') {
                $result .= $encodeSlash ? '%2F' : '/';
            } else {
                $result .= '%' . strtoupper(bin2hex($c));
            }
        }
        return $result;
    }

    private static function canonicalUriFor(string $bucket, string $key): string
    {
        $segments = array_merge([$bucket], explode('/', $key));
        $encoded  = array_map(static fn(string $s) => self::uriEncode($s, false), $segments);
        return '/' . implode('/', $encoded);
    }

    private static function canonicalQueryString(array $params): string
    {
        if (empty($params)) return '';
        $pairs = [];
        foreach ($params as $k => $v) $pairs[] = self::uriEncode((string)$k, true) . '=' . self::uriEncode((string)$v, true);
        sort($pairs);
        return implode('&', $pairs);
    }
}

final class R2WriteException extends \RuntimeException {}
