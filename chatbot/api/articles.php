<?php
/**
 * articles.php — Unified Multi-Domain RAG & Content Retriever for Paroki Website
 * Mencari artikel, kategorial, wilayah/lingkungan, asisten imam, DPP, agenda, dan UMKM.
 */

require_once __DIR__ . '/config.php';
require_once dirname(__DIR__, 2) . '/includes/functions.php';

class ArticleReader
{
    /**
     * Ambil konten halaman dari URL (dengan cache)
     */
    public static function fetch(string $url): ?string
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $url = rtrim(SITE_URL, '/') . '/' . ltrim($url, '/');
        }

        $cacheKey  = md5($url);
        $cacheFile = CACHE_DIR . $cacheKey . '.txt';

        if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < ARTICLE_CACHE_TTL) {
            return file_get_contents($cacheFile);
        }

        $content = self::curlFetch($url);
        if (!$content) return null;

        $text = self::extractText($content);
        if (empty($text)) return null;

        $text = mb_substr($text, 0, 8000);
        file_put_contents($cacheFile, $text, LOCK_EX);

        return $text;
    }

    private static function curlFetch(string $url): ?string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 3,
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_USERAGENT      => 'ChatbotParoki/3.0 (Smart RAG Agent)',
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER     => ['Accept-Language: id,en'],
        ]);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ($code === 200 && $body) ? $body : null;
    }

    private static function extractText(string $html): string
    {
        $html = preg_replace('/<(script|style|nav|footer|header)[^>]*>.*?<\/\1>/si', '', $html);
        if (preg_match('/<(article|main)[^>]*>(.*?)<\/\1>/si', $html, $m)) {
            $html = $m[2];
        }
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES, 'UTF-8');
        $text = preg_replace('/\s+/', ' ', $text);
        return trim($text);
    }

    /**
     * Multi-Domain RAG Search: Mengumpulkan konteks relevan dari seluruh data website
     */
    public static function searchAll(string $message, int $maxResults = 6): array
    {
        $message = trim($message);
        if ($message === '') return [];

        $keywords = self::extractKeywords($message);
        if (empty($keywords)) return [];

        $results = [
            'articles'   => self::searchArticles($keywords),
            'kategorial' => self::searchKategorial($keywords),
            'wilayah'    => self::searchWilayah($keywords),
            'ai'         => self::searchAsistenImam($keywords),
            'dpp'        => self::searchDPP($keywords),
            'umkm'       => self::searchUMKM($keywords),
            'agenda'     => self::searchAgenda($keywords),
        ];

        return $results;
    }

    /**
     * Backward-compatible article search
     */
    public static function search(string $message, int $limit = 5): array
    {
        $keywords = self::extractKeywords($message);
        if (empty($keywords)) return [];
        return self::searchArticles($keywords, $limit);
    }

    /**
     * Bangun string konteks RAG siap injeksi ke Prompt AI
     */
    public static function buildRAGContextString(string $message): string
    {
        $data = self::searchAll($message);
        $sections = [];

        // 1. Artikel (Berita / Kronik / Historia)
        if (!empty($data['articles'])) {
            $lines = ["【ARTIKEL & WARTA TERKAIT】:"];
            foreach (array_slice($data['articles'], 0, 4) as $art) {
                $lines[] = "• [{$art['label']}] {$art['judul']} — {$art['ringkas']} (Tautan: {$art['url']})";
            }
            $sections[] = implode("\n", $lines);
        }

        // 2. Kategorial
        if (!empty($data['kategorial'])) {
            $lines = ["【KELOMPOK KATEGORIAL TERKAIT】:"];
            foreach ($data['kategorial'] as $kat) {
                $lines[] = "• {$kat['nama']} (Slug: /kategorial/{$kat['slug']}) — Deskripsi: {$kat['deskripsi']}";
            }
            $sections[] = implode("\n", $lines);
        }

        // 3. Wilayah & Lingkungan
        if (!empty($data['wilayah'])) {
            $lines = ["【WILAYAH & LINGKUNGAN TERKAIT】:"];
            foreach ($data['wilayah'] as $wil) {
                $lines[] = "• {$wil['nama_wilayah']} ({$wil['nama_lingkungan']}) — Wilayah ke-{$wil['no_wilayah']}";
            }
            $sections[] = implode("\n", $lines);
        }

        // 4. Asisten Imam / Prodiakon
        if (!empty($data['ai'])) {
            $lines = ["【ASISTEN IMAM / PRODIAKON TERKAIT】:"];
            foreach ($data['ai'] as $ai) {
                $lines[] = "• {$ai['nama']} (Lingkungan/Stasi: {$ai['lingkungan']})";
            }
            $sections[] = implode("\n", $lines);
        }

        // 5. DPP & BGKP
        if (!empty($data['dpp'])) {
            $lines = ["【PENGURUS DPP / BGKP TERKAIT】:"];
            foreach ($data['dpp'] as $dpp) {
                $lines[] = "• {$dpp['jabatan']}: {$dpp['nama']} ({$dpp['bidang']})";
            }
            $sections[] = implode("\n", $lines);
        }

        // 6. UMKM Umat
        if (!empty($data['umkm'])) {
            $lines = ["【PRODUK / USAHA UMKM UMAT TERKAIT】:"];
            foreach ($data['umkm'] as $u) {
                $lines[] = "• {$u['nama_usaha']} ({$u['kategori']}) — {$u['deskripsi']} (Owner: {$u['pemilik']}, WA: {$u['kontak']}) [Tautan: /umkmumat]";
            }
            $sections[] = implode("\n", $lines);
        }

        // 7. Agenda
        if (!empty($data['agenda'])) {
            $lines = ["【AGENDA & INFO PAROKI TERKAIT】:"];
            foreach ($data['agenda'] as $ag) {
                $lines[] = "• {$ag['judul']} — {$ag['keterangan']} (Tautan: /agenda)";
            }
            $sections[] = implode("\n", $lines);
        }

        return empty($sections) ? "Tidak ada rekaman spesifik database yang cocok; jawab dengan pengetahuan umum gereja atau panduan paroki." : implode("\n\n", $sections);
    }

    private static function extractKeywords(string $message): array
    {
        $stopwords = [
            'apa','apakah','siapa','kapan','dimana','yang','untuk','dengan',
            'dari','atau','dan','itu','ini','ada','adalah','bagaimana','tolong','minta',
            'coba','cari','tentang','soal','mengenai','saya','aku','kamu','kita','mau',
            'ingin','bisa','boleh','gimana','dong','pada','oleh','saja','juga','lagi',
            'sudah','belum','tidak','tolong','mohon','kenapa','mengapa','halo','hai'
        ];
        $words = preg_split('/[^a-z0-9]+/i', mb_strtolower($message));
        return array_values(array_filter($words, function($w) use ($stopwords) {
            return mb_strlen($w) >= 3 && !in_array($w, $stopwords, true);
        }));
    }

    private static function searchArticles(array $keywords, int $limit = 5): array
    {
        require_once dirname(__DIR__, 2) . '/includes/SupabaseArticleManager.php';
        $am = new SupabaseArticleManager();
        $candidates = [];

        foreach (SupabaseArticleManager::MENUS as $menu) {
            $cacheKey = 'chatbot_art_' . $menu;
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
                    if (mb_strpos($haystack, $kw) !== false) $score += 2;
                }
                if ($score === 0) continue;

                $candidates[] = [
                    'score'   => $score,
                    'menu'    => $menu,
                    'label'   => SupabaseArticleManager::MENU_LABELS[$menu] ?? ucfirst($menu),
                    'judul'   => html_entity_decode($judul, ENT_QUOTES|ENT_HTML5, 'UTF-8'),
                    'ringkas' => mb_substr(html_entity_decode($ringkas, ENT_QUOTES|ENT_HTML5, 'UTF-8'), 0, 160, 'UTF-8'),
                    'url'     => SITE_URL . '/artikel/' . $menu . '/' . rawurlencode($art['slug'] ?? $art['id']),
                ];
            }
        }

        usort($candidates, fn($a, $b) => $b['score'] <=> $a['score']);
        return array_slice($candidates, 0, $limit);
    }

    private static function searchKategorial(array $keywords): array
    {
        $builtIn = [
            ['nama' => 'Adorasi Sakramen Mahakudus', 'slug' => 'adorasi', 'deskripsi' => 'Kelompok doa devosi dan penyembahan di hadapan Sakramen Mahakudus.'],
            ['nama' => 'PDKK Santa Maria', 'slug' => 'pdkk', 'deskripsi' => 'Persekutuan Doa Karismatik Katolik Paroki SMDTBA.'],
            ['nama' => 'WKRI Cabang SMDTBA', 'slug' => 'wkri', 'deskripsi' => 'Wanita Katolik Republik Indonesia — wadah karya sosial kemasyarakatan ibu-ibu Katolik.'],
            ['nama' => 'Gerakan Imam Maria (GIM)', 'slug' => 'gim', 'deskripsi' => 'Gerakan doa senakel rosario bagi para imam dan persatuan dengan Hati Tersuci Maria.'],
            ['nama' => 'Legio Maria', 'slug' => 'legio-maria', 'deskripsi' => 'Presidium tentara Maria bertugas karya kerasulan doa dan kunjungan umat/orang sakit.'],
            ['nama' => 'Marriage Encounter (ME)', 'slug' => 'me', 'deskripsi' => 'Gerakan pembinaan dan pembaharuan relasi pasutri (suami-istri) Katolik.'],
            ['nama' => 'Pemuda Katolik & OMK', 'slug' => 'omk', 'deskripsi' => 'Orang Muda Katolik St. Aloysius Gonzaga dan Pemuda Katolik Komcab Tulungagung.'],
            ['nama' => 'Putra Putri Altar (Misdinar)', 'slug' => 'misdinar', 'deskripsi' => 'PPA St. Tarsisius yang melayani di altar pada perayaan ekaristi.'],
            ['nama' => 'Rosario Hidup', 'slug' => 'rosario-hidup', 'deskripsi' => 'Komunitas pendoa satu peristiwa rosario harian terhubung.'],
            ['nama' => 'Komunitas Tritunggal Mahakudus (KTM)', 'slug' => 'ktm', 'deskripsi' => 'Komunitas doa kontemplatif dan penginjilan.'],
            ['nama' => 'Serikat Sosial Vinsensius (SSV)', 'slug' => 'ssv', 'deskripsi' => 'Pelayanan karitatif langsung kepada warga miskin dan terlantar.'],
        ];

        $matched = [];
        foreach ($builtIn as $item) {
            $haystack = mb_strtolower($item['nama'] . ' ' . $item['slug'] . ' ' . $item['deskripsi']);
            foreach ($keywords as $kw) {
                if (mb_strpos($haystack, $kw) !== false) {
                    $matched[] = $item;
                    break;
                }
            }
        }
        return array_slice($matched, 0, 3);
    }

    private static function searchWilayah(array $keywords): array
    {
        $wilayahData = [
            ['no_wilayah' => 1, 'nama_wilayah' => 'Wilayah 1 - Pusat', 'nama_lingkungan' => 'Lingkungan St. Petrus, St. Paulus, St. Yohanes, St. Antonius'],
            ['no_wilayah' => 2, 'nama_wilayah' => 'Wilayah 2', 'nama_lingkungan' => 'Lingkungan St. Monika, St. Maria Goretti, St. Theresia'],
            ['no_wilayah' => 3, 'nama_wilayah' => 'Wilayah 3', 'nama_lingkungan' => 'Lingkungan St. Thomas Rasul, St. Fransiskus Asisi, St. Ignasius'],
            ['no_wilayah' => 4, 'nama_wilayah' => 'Wilayah 4', 'nama_lingkungan' => 'Lingkungan St. Vincentius, St. Yusuf, St. Agnes'],
            ['no_wilayah' => 5, 'nama_wilayah' => 'Wilayah 5 - Kauman', 'nama_lingkungan' => 'Lingkungan St. Matias, St. Markus, St. Lukas'],
            ['no_wilayah' => 6, 'nama_wilayah' => 'Wilayah 6 - Ngunut & Rejotangan', 'nama_lingkungan' => 'Lingkungan St. Stefanus, St. Bartolomeus, Stasi Ngunut, Stasi Rejotangan'],
            ['no_wilayah' => 7, 'nama_wilayah' => 'Wilayah 7 - Trenggalek & Pegunungan', 'nama_lingkungan' => 'Stasi Trenggalek, Stasi Dongko, Stasi Sendang, Stasi Panggul'],
        ];

        $matched = [];
        foreach ($wilayahData as $item) {
            $haystack = mb_strtolower($item['nama_wilayah'] . ' ' . $item['nama_lingkungan']);
            foreach ($keywords as $kw) {
                if (mb_strpos($haystack, $kw) !== false) {
                    $matched[] = $item;
                    break;
                }
            }
        }
        return array_slice($matched, 0, 3);
    }

    private static function searchAsistenImam(array $keywords): array
    {
        // Keyword check for prodiakon / asisten imam
        $hasAiKw = false;
        foreach ($keywords as $kw) {
            if (in_array($kw, ['prodiakon', 'asisten', 'imam', 'komuni', 'pelayan'], true)) {
                $hasAiKw = true;
                break;
            }
        }
        if (!$hasAiKw) return [];

        return [
            ['nama' => 'Asisten Imam / Prodiakon Paroki SMDTBA', 'lingkungan' => 'Melayani seluruh wilayah/lingkungan dan pembagian komuni orang sakit. Info lengkap di /profil-ai']
        ];
    }

    private static function searchDPP(array $keywords): array
    {
        $hasDppKw = false;
        foreach ($keywords as $kw) {
            if (in_array($kw, ['dpp', 'bgkp', 'pengurus', 'dewan', 'pastoral', 'ketua'], true)) {
                $hasDppKw = true;
                break;
            }
        }
        if (!$hasDppKw) return [];

        return [
            ['jabatan' => 'Ketua Umum DPP', 'nama' => 'RD. Thomas Aquino Djoko Noegroho', 'bidang' => 'Dewan Pastoral Paroki'],
            ['jabatan' => 'Sekretariat & BGKP', 'nama' => 'Pengurus DPP & Pengelola Aset Gereja', 'bidang' => 'Info lengkap di /profil-dpp']
        ];
    }

    private static function searchUMKM(array $keywords): array
    {
        $hasUmkmKw = false;
        foreach ($keywords as $kw) {
            if (in_array($kw, ['umkm', 'jualan', 'usaha', 'produk', 'beli', 'katering', 'pasar', 'umat'], true)) {
                $hasUmkmKw = true;
                break;
            }
        }
        if (!$hasUmkmKw) return [];

        return [
            ['nama_usaha' => 'Direktori Pasar UMKM Umat Paroki', 'kategori' => 'Kuliner, Fashion, Jasa, Kerajinan', 'deskripsi' => 'Wadah promosi usaha warga paroki untuk saling mendukung ekonomi umat.', 'pemilik' => 'Umat Paroki SMDTBA', 'kontak' => '+62 856-3678-844']
        ];
    }

    private static function searchAgenda(array $keywords): array
    {
        $hasAgendaKw = false;
        foreach ($keywords as $kw) {
            if (in_array($kw, ['agenda', 'acara', 'kegiatan', 'pengumuman', 'warta', 'jadwal', 'retret', 'rekoleksi'], true)) {
                $hasAgendaKw = true;
                break;
            }
        }
        if (!$hasAgendaKw) return [];

        return [
            ['judul' => 'Agenda & Warta Kegiatan Paroki', 'keterangan' => 'Jadwal kegiatan rutin, misa hari raya, dan pengumuman paroki terupdate dapat dilihat di halaman /agenda']
        ];
    }

    public static function clearOldCache(): int
    {
        $deleted = 0;
        foreach (glob(CACHE_DIR . '*.txt') as $file) {
            if ((time() - filemtime($file)) > ARTICLE_CACHE_TTL * 24) {
                @unlink($file);
                $deleted++;
            }
        }
        return $deleted;
    }
}
