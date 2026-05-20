<?php
/**
 * articles.php — Pembaca & cache konten halaman/artikel website
 */

require_once __DIR__ . '/config.php';

class ArticleReader {

    /**
     * Ambil konten halaman dari URL (dengan cache).
     * Dipanggil saat user bertanya tentang halaman tertentu.
     */
    public static function fetch(string $url): ?string {
        // Normalkan URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            // URL relatif → tambah base
            $url = rtrim(SITE_URL, '/') . '/' . ltrim($url, '/');
        }

        $cacheKey  = md5($url);
        $cacheFile = CACHE_DIR . $cacheKey . '.txt';

        // Kembalikan dari cache jika masih fresh
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < ARTICLE_CACHE_TTL) {
            return file_get_contents($cacheFile);
        }

        // Fetch halaman
        $content = self::curlFetch($url);
        if (!$content) return null;

        // Ekstrak teks bersih dari HTML
        $text = self::extractText($content);
        if (empty($text)) return null;

        // Potong agar tidak terlalu panjang (max ~2000 kata)
        $text = mb_substr($text, 0, 8000);

        // Simpan ke cache
        file_put_contents($cacheFile, $text, LOCK_EX);

        return $text;
    }

    /** Fetch via cURL */
    private static function curlFetch(string $url): ?string {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 3,
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_USERAGENT      => 'ChatbotParoki/2.0 (internal reader)',
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER     => ['Accept-Language: id,en'],
        ]);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ($code === 200 && $body) ? $body : null;
    }

    /** Ekstrak teks bersih dari HTML */
    private static function extractText(string $html): string {
        // Hapus script, style, nav, footer
        $html = preg_replace('/<(script|style|nav|footer|header)[^>]*>.*?<\/\1>/si', '', $html);

        // Ambil konten utama (article, main, .content, dll)
        if (preg_match('/<(article|main)[^>]*>(.*?)<\/\1>/si', $html, $m)) {
            $html = $m[2];
        }

        // Strip HTML tags, decode entities
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES, 'UTF-8');

        // Bersihkan whitespace berlebih
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        return $text;
    }

    /** Cari artikel berdasarkan keyword di sitemap/API lokal */
    public static function search(string $keyword): array {
        // Coba hit endpoint pencarian lokal jika ada
        $searchUrl = SITE_URL . '/api/search?q=' . urlencode($keyword) . '&limit=3';
        $result    = self::curlFetch($searchUrl);

        if ($result) {
            $data = json_decode($result, true);
            if (isset($data['results'])) return $data['results'];
        }

        return [];
    }

    /** Hapus cache lama */
    public static function clearOldCache(): int {
        $deleted = 0;
        foreach (glob(CACHE_DIR . '*.txt') as $file) {
            if ((time() - filemtime($file)) > ARTICLE_CACHE_TTL * 24) {
                unlink($file);
                $deleted++;
            }
        }
        return $deleted;
    }

    /** Invalidasi cache untuk URL tertentu */
    public static function invalidate(string $url): void {
        $cacheKey  = md5($url);
        $cacheFile = CACHE_DIR . $cacheKey . '.txt';
        if (file_exists($cacheFile)) unlink($cacheFile);
    }
}
