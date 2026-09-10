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

    /**
     * Cari artikel (Berita/Kronik/Historia) berdasarkan pesan user —
     * TIDAK bergantung pada halaman yang sedang dibuka user, jadi tetap
     * bisa menemukan hasil dari Kronik/Historia meskipun user sedang
     * membuka halaman lain (mis. Beranda atau /artikel/berita).
     */
    public static function search(string $message, int $limit = 5): array {
        $message = trim($message);
        if ($message === '') return [];

        require_once dirname(__DIR__, 2) . '/includes/functions.php';
        require_once dirname(__DIR__, 2) . '/includes/SupabaseArticleManager.php';

        // Ambil kata kunci penting dari pesan (buang stopword & kata pendek)
        $stopwords = ['apa','apakah','siapa','kapan','dimana','yang','untuk','dengan',
            'dari','atau','dan','itu','ini','ada','adalah','bagaimana','tolong','minta',
            'coba','cari','tentang','soal','mengenai','saya','aku','kamu','kita','mau',
            'ingin','bisa','boleh','gimana','dong','pada','oleh','saja','juga','lagi',
            'sudah','belum','tidak','tolong','gereja','paroki'];
        $words = preg_split('/[^a-z0-9]+/i', mb_strtolower($message));
        $keywords = array_values(array_filter($words, function($w) use ($stopwords) {
            return mb_strlen($w) >= 4 && !in_array($w, $stopwords, true);
        }));
        if (empty($keywords)) return [];

        $am         = new SupabaseArticleManager();
        $candidates = [];

        foreach (SupabaseArticleManager::MENUS as $menu) {
            $cacheKey = 'chatbot_artikel_search_' . $menu . '_v1';
            $list = function_exists('cache_get') ? cache_get($cacheKey) : null;
            if (!is_array($list) || empty($list)) {
                $list = $am->getAll($menu, publishedOnly: true);
                if (!empty($list) && function_exists('cache_set')) {
                    cache_set($cacheKey, $list, 300);
                }
            }

            foreach ($list as $art) {
                $judul    = $art['judul']     ?? '';
                $ringkas  = strip_tags($art['ringkasan'] ?? '');
                $tags     = $art['tags']      ?? '';
                $haystack = mb_strtolower($judul . ' ' . $ringkas . ' ' . $tags);

                $score = 0;
                foreach ($keywords as $kw) {
                    if (mb_strpos($haystack, $kw) !== false) $score++;
                }
                if ($score === 0) continue;

                $candidates[] = [
                    'score'   => $score,
                    'menu'    => $menu,
                    'label'   => SupabaseArticleManager::MENU_LABELS[$menu],
                    'judul'   => html_entity_decode($judul, ENT_QUOTES|ENT_HTML5, 'UTF-8'),
                    'ringkas' => mb_substr(html_entity_decode($ringkas, ENT_QUOTES|ENT_HTML5, 'UTF-8'), 0, 160, 'UTF-8'),
                    'url'     => (defined('SITE_URL') ? SITE_URL : '') . '/artikel/' . $menu . '/' . rawurlencode($art['slug'] ?? $art['id']),
                ];
            }
        }

        usort($candidates, fn($a, $b) => $b['score'] <=> $a['score']);
        return array_slice($candidates, 0, $limit);
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