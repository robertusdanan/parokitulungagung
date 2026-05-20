<?php
/**
 * includes/SupabaseArticleManager.php
 * Manajemen artikel via Supabase REST API
 */

if (!class_exists('SupabaseArticleManager')) {

class SupabaseArticleManager
{
    private ?object $db;
    private string  $table = 'articles';

    const MENUS = ['berita', 'kronik', 'historia'];
    const MENU_LABELS = [
        'berita'   => 'Artikel',
        'kronik'   => 'Kronik SMDTBA',
        'historia' => 'Historia Gereja',
    ];

    public function __construct(?object $db = null)
    {
        $this->db = $db;
    }

    // ── HTTP helper (publik — tanpa SupabaseClient) ───────────────────────

    private function baseUrl(): string
    {
        return rtrim(defined('SUPABASE_URL') ? SUPABASE_URL : '', '/') . '/rest/v1/' . $this->table;
    }

    private function headers(): array
    {
        $key = defined('SUPABASE_ANON_KEY') ? SUPABASE_ANON_KEY : '';
        return [
            'apikey: ' . $key,
            'Authorization: Bearer ' . $key,
            'Accept: application/json',
            'Content-Type: application/json',
            'Prefer: return=representation',
        ];
    }

    private function httpGet(string $url): ?array
    {
        $hdrs = $this->headers();
        $ctx  = stream_context_create([
            'http' => ['header' => implode("\r\n", $hdrs) . "\r\n", 'timeout' => 30, 'ignore_errors' => true],
            'ssl'  => ['verify_peer' => true, 'verify_peer_name' => true],
        ]);
        $json = @file_get_contents($url, false, $ctx);
        if ($json === false && function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => $hdrs, CURLOPT_SSL_VERIFYPEER => true, CURLOPT_TIMEOUT => 30]);
            $json = curl_exec($ch); curl_close($ch);
        }
        if (!$json) return null;
        $d = json_decode($json, true);
        return is_array($d) ? $d : null;
    }

    private function httpSend(string $url, array $data, string $method): void
    {
        $hdrs = $this->headers();
        $body = json_encode($data, JSON_UNESCAPED_UNICODE);
        $ctx  = stream_context_create([
            'http' => ['method' => $method, 'header' => implode("\r\n", $hdrs) . "\r\n", 'content' => $body, 'timeout' => 12, 'ignore_errors' => true],
            'ssl'  => ['verify_peer' => true],
        ]);
        $result = @file_get_contents($url, false, $ctx);
        if ($result === false && function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_CUSTOMREQUEST => $method, CURLOPT_POSTFIELDS => $body, CURLOPT_HTTPHEADER => $hdrs, CURLOPT_SSL_VERIFYPEER => true, CURLOPT_TIMEOUT => 12]);
            curl_exec($ch); curl_close($ch);
        }
    }

    // ── ID & Slug ─────────────────────────────────────────────────────────

    public function generateId(): string
    {
        return 'art' . base_convert(time(), 10, 36) . substr(str_replace('.', '', uniqid('', true)), -4);
    }

    public function generateSlug(string $title): string
    {
        $map  = ['à'=>'a','á'=>'a','è'=>'e','é'=>'e','ì'=>'i','í'=>'i','ò'=>'o','ó'=>'o','ù'=>'u','ú'=>'u','ý'=>'y','ñ'=>'n','ç'=>'c'];
        $slug = strtolower(strtr($title, $map));
        $slug = preg_replace('/[^a-z0-9\s\-]/', '', $slug);
        $slug = preg_replace('/[\s\-]+/', '-', trim($slug));
        return substr($slug, 0, 80) ?: 'artikel';
    }

    private function uniqueSlug(string $menu, string $title, ?string $excludeId = null): string
    {
        $base = $this->generateSlug($title); $slug = $base; $i = 1;
        while (true) {
            $found = $this->getBySlug($menu, $slug);
            if (!$found || $found['id'] === $excludeId) break;
            $slug = $base . '-' . $i++;
            if ($i > 100) { $slug = $base . '-' . time(); break; }
        }
        return $slug;
    }

    // ── Tags helper ───────────────────────────────────────────────────────

    /**
     * Normalisasi tags sebelum disimpan:
     * "Liturgi,  misa , MISA, paroki" → "liturgi, misa, paroki"
     */
    private function normalizeTags(string $raw): string
    {
        if (trim($raw) === '') return '';
        $tags = array_map(
            fn($t) => mb_strtolower(trim($t), 'UTF-8'),
            explode(',', $raw)
        );
        // Buang kosong dan duplikat, pertahankan urutan
        $seen = [];
        $out  = [];
        foreach ($tags as $t) {
            if ($t !== '' && !in_array($t, $seen, true)) {
                $seen[] = $t;
                $out[]  = $t;
            }
        }
        return implode(', ', $out);
    }

    /**
     * Ubah string tags ke array PHP.
     * Berguna di template frontend untuk <meta keywords> dll.
     * "liturgi, misa, paroki" → ['liturgi', 'misa', 'paroki']
     */
    public static function tagsToArray(string $tags): array
    {
        if (trim($tags) === '') return [];
        return array_values(array_filter(
            array_map('trim', explode(',', $tags))
        ));
    }

    /**
     * Hasilkan string meta keywords dari tags + keyword default.
     * Contoh di template:
     *   <meta name="keywords" content="<?= SupabaseArticleManager::metaKeywords($art['tags'], 'paroki smdtba') ?>">
     */
    public static function metaKeywords(string $tags, string $extra = ''): string
    {
        $arr = self::tagsToArray($tags);
        if ($extra) {
            foreach (explode(',', $extra) as $e) {
                $t = trim($e);
                if ($t && !in_array($t, $arr, true)) $arr[] = $t;
            }
        }
        return htmlspecialchars(implode(', ', $arr), ENT_QUOTES, 'UTF-8');
    }

    // ── READ ──────────────────────────────────────────────────────────────

public function getAll(string $menu, bool $publishedOnly = false, array $select = []): array {
        // Admin selalu fetch langsung dari Supabase — tidak ada cache
        if ($this->db) {
            $f = ['menu=eq.' . $menu];
            if ($publishedOnly) $f[] = 'status=eq.published';
            $rows = $this->db->readWhere($this->table, $f, 'updated_at.desc', '*');
            return is_array($rows) ? $rows : [];
        }

        // ── Cache publik — hindari fetch Supabase setiap request ──────
        $cacheKey = 'getAll_' . $menu . '_' . ($publishedOnly ? '1' : '0');
        if (function_exists('cache_get')) {
            $cached = cache_get($cacheKey);
            if (is_array($cached) && !empty($cached)) return $cached;
        }

        $url  = $this->baseUrl()
             . '?menu=eq.' . urlencode($menu)
             . ($publishedOnly ? '&status=eq.published' : '')
             . '&order=updated_at.desc&select=*&limit=1000';
        $result = $this->httpGet($url) ?? [];

        if (!empty($result) && function_exists('cache_set')) {
            cache_set($cacheKey, $result, 300); // 5 menit
        }
        return $result;
    }

    public function getById(string $menu, string $id): ?array
    {
        $id = preg_replace('/[^a-zA-Z0-9_\-.]/', '', $id);
        if ($this->db) {
            // Cari dengan menu dulu; jika tidak ketemu, cari tanpa filter menu
            // (untuk mendukung pindah menu/rubrik)
            $rows = $this->db->readWhere($this->table, ['id=eq.' . $id, 'menu=eq.' . $menu], '', '*');
            if (!empty($rows)) return $rows[0];
            $rows = $this->db->readWhere($this->table, ['id=eq.' . $id], '', '*');
            return !empty($rows) ? $rows[0] : null;
        }
        $url  = $this->baseUrl() . '?id=eq.' . urlencode($id) . '&menu=eq.' . urlencode($menu) . '&select=*&limit=1';
        $rows = $this->httpGet($url);
        if (!empty($rows)) return $rows[0];
        // Fallback: cari tanpa filter menu
        $url2 = $this->baseUrl() . '?id=eq.' . urlencode($id) . '&select=*&limit=1';
        $rows2 = $this->httpGet($url2);
        return !empty($rows2) ? $rows2[0] : null;
    }

    public function getBySlug(string $menu, string $slug): ?array
    {
        if ($this->db) {
            $rows = $this->db->readWhere($this->table, ['menu=eq.' . $menu, 'slug=eq.' . $slug], '', '*');
            return !empty($rows) ? $rows[0] : null;
        }
        $url  = $this->baseUrl() . '?menu=eq.' . urlencode($menu) . '&slug=eq.' . urlencode($slug) . '&select=*&limit=1';
        $rows = $this->httpGet($url);
        return !empty($rows) ? $rows[0] : null;
    }

    /**
     * Ambil semua artikel published milik satu penulis berdasarkan author-slug.
     * FIX: Fetch hanya field minimal dan filter di PHP — jauh lebih efisien
     * daripada getAll() yang mengambil select=* ribuan artikel lalu filter di PHP.
     */
    public function getByAuthorSlug(string $menu, string $authorSlug): array
    {
        // Coba pakai cache getAll published yang mungkin sudah ada
        $cacheKey = 'getAll_' . $menu . '_1';
        $cached   = function_exists('cache_get') ? cache_get($cacheKey) : null;

        if (is_array($cached) && !empty($cached)) {
            $all = $cached;
        } elseif ($this->db) {
            $all = $this->db->readWhere(
                $this->table,
                ['menu=eq.' . $menu, 'status=eq.published'],
                'published_at.desc',
                'judul,slug,penulis,thumbnail,published_at'
            );
            $all = is_array($all) ? $all : [];
        } else {
            $url = $this->baseUrl()
                 . '?menu=eq.' . urlencode($menu)
                 . '&status=eq.published'
                 . '&order=published_at.desc'
                 . '&select=judul,slug,penulis,thumbnail,published_at'
                 . '&limit=500';
            $all = $this->httpGet($url) ?? [];
        }

        // FIX #5: normalisasi slug konsisten — trim, lowercase, ganti non-alnum jadi dash
        $result = [];
        foreach ($all as $art) {
            $name = trim(strip_tags($art['penulis'] ?? ''));
            if (!$name) continue;
            $artSlug = preg_replace('/[^a-z0-9]+/', '-', strtolower($name));
            $artSlug = trim($artSlug, '-');
            if ($artSlug === $authorSlug) {
                $result[] = $art;
            }
        }
        return $result;
    }

        public function stats(string $menu): array
    {
        $all = $this->getAll($menu);
        return [
            'total'     => count($all),
            'published' => count(array_filter($all, fn($a) => ($a['status']??'') === 'published')),
            'draft'     => count(array_filter($all, fn($a) => ($a['status']??'') !== 'published')),
        ];
    }

    // ── WRITE ─────────────────────────────────────────────────────────────

    public function save(array $data): array
    {
        $menu = $data['menu'] ?? '';
        if (!in_array($menu, self::MENUS)) throw new RuntimeException('Menu tidak valid: ' . $menu);
        $isNew = empty($data['id']);
        $now   = date('Y-m-d H:i:s');

        // ── Normalisasi tags sebelum simpan ──────────────────────────
        if (isset($data['tags'])) {
            $data['tags'] = $this->normalizeTags($data['tags']);
        } else {
            $data['tags'] = '';
        }

        // ── Slug ─────────────────────────────────────────────────────
        if ($isNew) {
            $data['id']         = $this->generateId();
            $data['created_at'] = $now;
            $data['slug']       = $this->uniqueSlug($menu, $data['judul'] ?? 'artikel');
        } else {
            $ex = $this->getById($menu, $data['id']);
            if ($ex && ($ex['judul']??'') !== ($data['judul']??'')) {
                $data['slug'] = $this->uniqueSlug($menu, $data['judul']??'artikel', $data['id']);
            } elseif ($ex && !empty($ex['slug'])) {
                $data['slug'] = $ex['slug'];
            } else {
                $data['slug'] = $this->uniqueSlug($menu, $data['judul']??'artikel', $data['id']);
            }
            if ($ex) {
                $data['created_at'] = $ex['created_at'] ?? $now;
                if (empty($data['penulis'])) $data['penulis'] = $ex['penulis'] ?? '';
                // Pertahankan tags lama jika tidak dikirim
                if ($data['tags'] === '' && !empty($ex['tags'])) {
                    $data['tags'] = $ex['tags'];
                }
            }
        }

        $data['updated_at'] = $now;

        // ── Published at ──────────────────────────────────────────────
        if (($data['status']??'') === 'published') {
            $ex2 = $isNew ? null : $this->getById($menu, $data['id']);
            if ($ex2 && ($ex2['status']??'') === 'published' && !empty($ex2['published_at'])) {
                $data['published_at'] = $ex2['published_at'];
            } elseif (empty($data['published_at'])) {
                $data['published_at'] = $now;
            }
        }

        // ── Auto-generate ringkasan dari konten jika kosong ───────────
        if (empty(trim($data['ringkasan']??'')) && !empty($data['konten'])) {
            $plain = preg_replace('/\s+/', ' ', trim(strip_tags($data['konten'])));
            $data['ringkasan'] = mb_substr($plain, 0, 200);
        }

        // ── Whitelist kolom yang dikirim ke Supabase ─────────────────
        // PENTING: 'tags' ada di sini agar tersimpan ke DB
        $cols = [
            'id', 'menu', 'judul', 'ringkasan', 'konten',
            'thumbnail', 'tags',                          // ← tags disimpan
            'status', 'penulis', 'slug',
            'created_at', 'updated_at', 'published_at',
        ];

        $row = [];
        foreach ($cols as $col) {
            if (array_key_exists($col, $data)) {
                $row[$col] = $data[$col];
            }
        }

        // ── Simpan ke Supabase ────────────────────────────────────────
        if ($this->db) {
            if ($isNew) $this->db->insert($this->table, $row);
            else        $this->db->update($this->table, 'id', $data['id'], $row);
        } else {
            if ($isNew) $this->httpSend($this->baseUrl(), $row, 'POST');
            else        $this->httpSend($this->baseUrl() . '?id=eq.' . urlencode($data['id']), $row, 'PATCH');
        }

        // Cache tidak digunakan di admin — tidak perlu invalidasi
        return array_merge($data, $row);
    }

    public function publish(string $menu, string $id): bool
    {
        $art = $this->getById($menu, $id); if (!$art) return false;
        $now = date('Y-m-d H:i:s');
        $upd = ['status' => 'published', 'published_at' => $art['published_at'] ?? $now, 'updated_at' => $now];
        if ($this->db) $this->db->update($this->table, 'id', $id, $upd);
        else           $this->httpSend($this->baseUrl() . '?id=eq.' . urlencode($id), $upd, 'PATCH');
        return true;
    }

    public function unpublish(string $menu, string $id): bool
    {
        $art = $this->getById($menu, $id); if (!$art) return false;
        $upd = ['status' => 'draft', 'updated_at' => date('Y-m-d H:i:s')];
        if ($this->db) $this->db->update($this->table, 'id', $id, $upd);
        else           $this->httpSend($this->baseUrl() . '?id=eq.' . urlencode($id), $upd, 'PATCH');
        return true;
    }

    public function delete(string $menu, string $id): bool
    {
        $art = $this->getById($menu, $id); if (!$art) return false;
        if (!empty($art['thumbnail'])) {
            $p = rtrim($_SERVER['DOCUMENT_ROOT']??'', '/') . $art['thumbnail'];
            if (file_exists($p) && str_contains(realpath($p)?:'', '/img/artikel/')) @unlink($p);
        }
        if ($this->db) {
            $this->db->delete($this->table, 'id', $id);
        } else {
            $url  = $this->baseUrl() . '?id=eq.' . urlencode($id);
            $hdrs = $this->headers();
            $ctx  = stream_context_create(['http' => ['method' => 'DELETE', 'header' => implode("\r\n", $hdrs) . "\r\n", 'ignore_errors' => true], 'ssl' => ['verify_peer' => true]]);
            @file_get_contents($url, false, $ctx);
        }
        return true;
    }

    // ── Utilities ─────────────────────────────────────────────────────────

    public static function formatTanggal(string $dt): string
    {
        if (!$dt) return '';
        $bulan = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
        $ts = strtotime(str_replace(' ', 'T', $dt));
        if (!$ts) return $dt;
        return date('j', $ts) . ' ' . ($bulan[(int)date('n', $ts)]) . ' ' . date('Y', $ts);
    }
}

} // end class_exists guard