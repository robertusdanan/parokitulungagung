<?php
/**
 * ImageSeoGenerator.php — AI-Powered SEO Generator
 * Menggunakan Gemini API (primary) + Groq API (fallback)
 * API keys diambil dari secrets.php (SECRET_GEMINI_API_KEYS, SECRET_GROQ_API_KEYS)
 * Versi: v7-ai
 */

// Pastikan helper privatePath() tersedia (dipakai di bawah untuk load secrets)
if (!function_exists('privatePath')) {
    require_once dirname(__DIR__, 2) . '/includes/functions.php';
}

class ImageSeoGenerator
{
    private const TABLE     = 'image_seo';
    private const CACHE_TTL = 86400;      // 1 hari file-cache lokal
    private const VERSION   = 'v7-ai';   // tandai di kolom model_used

    // Timeout & model settings untuk SEO (bisa lebih tinggi dari chatbot)
    private const AI_TIMEOUT  = 20;
    private const GEMINI_MODELS = [
    'gemini-3.1-flash-lite',
    'gemini-3-flash',
    'gemini-2.5-flash-lite',
    'gemini-2.5-flash',
    ];
    private const GROQ_MODELS = [
        'llama-3.1-8b-instant',
        'llama-3.3-70b-versatile',
    ];
    private const GROQ_ENDPOINT = 'https://api.groq.com/openai/v1/chat/completions';

    /**
     * In-memory cache agar tidak ada N+1 query ke Supabase.
     */
    private static array $memoryCache = [];

    // ══════════════════════════════════════════════════════════════════════════
    //  PUBLIC API
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * Batch pre-load semua data image_seo untuk satu artikel dari Supabase.
     * Panggil SEKALI sebelum loop gambar di artikel-detail.php.
     */
    public static function preloadForArticle($artikelId): void
    {
        if (!$artikelId || !self::sbUrl() || !self::sbKey()) return;

        $url = self::sbUrl() . '/rest/v1/' . self::TABLE
             . '?artikel_id=eq.' . urlencode((string)$artikelId)
             . '&select=image_url,alt_text,caption,title_attr,schema_description,image_keywords,model_used';

        $rows = self::sbGet($url);
        if (!is_array($rows)) return;

        foreach ($rows as $row) {
            $imgUrl = trim($row['image_url'] ?? '');
            if (!$imgUrl) continue;

            $kw = $row['image_keywords'] ?? '[]';
            if (is_string($kw)) $kw = json_decode($kw, true);
            if (!is_array($kw)) $kw = [];

            $seoData = [
                'alt'         => $row['alt_text']          ?? '',
                'caption'     => $row['caption']            ?? '',
                'title'       => $row['title_attr']         ?? '',
                'description' => $row['schema_description'] ?? '',
                'keywords'    => $kw,
                '_model'      => 'preloaded',
            ];

            $cacheKey = 'imgseo_v7_' . md5($imgUrl);
            self::$memoryCache[$cacheKey] = $seoData;
            self::cacheSet($cacheKey, $seoData);
        }
    }

    /**
     * Titik masuk utama. Return array SEO untuk satu URL gambar.
     *
     * @param string $src     URL gambar
     * @param array  $artData ['id','judul','tags','kategori','menu']
     */
    public static function generate(string $src, array $artData): array
    {
        $src = trim($src);
        if (!$src) return self::emptyResult($artData);

        $cacheKey = 'imgseo_v7_' . md5($src);

        if (isset(self::$memoryCache[$cacheKey]) && !empty(self::$memoryCache[$cacheKey]['alt'])) {
            return self::$memoryCache[$cacheKey];
        }

        $cached = self::cacheGet($cacheKey);
        if ($cached && !empty($cached['alt'])) {
            self::$memoryCache[$cacheKey] = $cached;
            return $cached;
        }

        $fromDb = self::fetchFromSupabase($src);
        if ($fromDb && !empty($fromDb['alt'])) {
            self::$memoryCache[$cacheKey] = $fromDb;
            self::cacheSet($cacheKey, $fromDb);
            return $fromDb;
        }

        $result = self::generateWithAI($src, $artData);

        self::saveToSupabase($src, $result, $artData);
        self::$memoryCache[$cacheKey] = $result;
        self::cacheSet($cacheKey, $result);

        return $result;
    }

    /** Force-regenerate: hapus cache + Supabase record, lalu generate ulang. */
    public static function forceRegenerate(string $src, array $artData): array
    {
        $cacheKey = 'imgseo_v7_' . md5($src);
        @unlink(self::cacheFile($cacheKey));
        unset(self::$memoryCache[$cacheKey]);
        self::deleteFromSupabase($src);
        return self::generate($src, $artData);
    }

    /** Dipakai oleh admin/api/seo-artikel.php. */
    public static function forceGenerateToSupabase(string $src, array $artData): bool
    {
        $src = trim($src);
        if (!$src) return false;

        $result = self::generateWithAI($src, $artData);
        self::saveToSupabase($src, $result, $artData);

        $cacheKey = 'imgseo_v7_' . md5($src);
        self::$memoryCache[$cacheKey] = $result;

        return true;
    }

    /**
     * Dipakai oleh admin/api/seo-images.php untuk sumber NON-artikel.
     * $sourceData: ['source_type'=>string, 'judul'=>string, 'kategori'=>string,
     *               'tags'=>string, 'extra'=>array]
     */
    public static function forceGenerateForSource(string $src, array $sourceData): bool
    {
        $src = trim($src);
        if (!$src) return false;

        $artData = [
            'id'           => null,
            'judul'        => $sourceData['judul']       ?? '',
            'display_name' => $sourceData['judul']       ?? '',
            'kategori'     => $sourceData['kategori']    ?? 'Gereja Katolik Tulungagung',
            'tags'         => $sourceData['tags']        ?? '',
            'menu'         => $sourceData['source_type'] ?? '',
            'source_type'  => $sourceData['source_type'] ?? '',
            'extra'        => $sourceData['extra']       ?? [],
            'konten'       => '',
        ];

        $result = self::generateWithAI($src, $artData);
        self::saveToSupabase($src, $result, $artData);

        $cacheKey = 'imgseo_v7_' . md5($src);
        self::$memoryCache[$cacheKey] = $result;

        return true;
    }

    /** Hapus semua file cache lokal. */
    public static function clearFileCache(): int
    {
        $deleted = 0;
        foreach (glob(self::cacheDir() . '/imgseo_*.cache') ?: [] as $file) {
            if (@unlink($file)) $deleted++;
        }
        self::$memoryCache = [];
        return $deleted;
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  AI GENERATOR — INTI
    //  Primary: Gemini (multi-key rotation)
    //  Fallback: Groq (multi-key rotation)
    //  Last resort: emptyResult
    // ══════════════════════════════════════════════════════════════════════════

    private static function generateWithAI(string $src, array $artData): array
    {
        $prompt = self::buildSeoPrompt($src, $artData);

        // 1. Coba Gemini
        $geminiResult = self::callGemini($prompt);
        if ($geminiResult !== null) {
            $parsed = self::parseAIResponse($geminiResult['text']);
            if ($parsed) {
                $parsed['_model'] = self::VERSION . ':gemini/' . $geminiResult['model'];
                return $parsed;
            }
        }

        // 2. Fallback ke Groq
        $groqResult = self::callGroq($prompt);
        if ($groqResult !== null) {
            $parsed = self::parseAIResponse($groqResult['text']);
            if ($parsed) {
                $parsed['_model'] = self::VERSION . ':groq/' . $groqResult['model'];
                return $parsed;
            }
        }

        // 3. Last resort
        error_log('[ImageSeoGenerator] Semua AI gagal untuk: ' . basename($src));
        return self::emptyResult($artData);
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  PROMPT BUILDER
    // ══════════════════════════════════════════════════════════════════════════

    private static function buildSeoPrompt(string $src, array $artData): string
    {
        // Non-artikel sources (galeri, umkm, dpp_bgkp, asisten_imam, wilayah)
        // gunakan prompt builder yang lebih spesifik
        $sourceType = $artData['source_type'] ?? $artData['menu'] ?? '';
        $nonArtikelSources = ['galeri', 'umkm', 'dpp_bgkp', 'asisten_imam', 'wilayah'];
        if (in_array($sourceType, $nonArtikelSources)) {
            return self::buildSourcePrompt($src, $artData);
        }

        $judul    = trim($artData['judul']    ?? '');
        $kategori = trim($artData['kategori'] ?? 'Gereja Katolik Tulungagung');
        $menu     = trim($artData['menu']     ?? '');
        $tags     = $artData['tags'] ?? [];
        $konten   = $artData['konten'] ?? '';
        if (is_string($tags)) {
            $tags = array_filter(array_map('trim', explode(',', $tags)));
        }
        $tagsStr   = implode(', ', array_slice($tags, 0, 8));
        $menuLabel = match ($menu) {
            'berita'   => 'Berita Paroki',
            'kronik'   => 'Kronik Kegiatan',
            'historia' => 'Historia Gereja',
            default    => $kategori,
        };

        // 1. Nama file — bersihkan jadi teks bermakna
        $filename     = pathinfo($src, PATHINFO_FILENAME);
        $filenameText = self::cleanFilenameToText($filename);

        // 2. Konteks paragraf di sekitar foto dalam HTML artikel
        $surroundingText = $konten ? self::extractContextAroundImage($konten, $src) : '';

        // 3. Ringkasan teks artikel (tanpa HTML, dipotong wajar)
        $articleSnippet = '';
        if ($konten) {
            $plain = trim(preg_replace('/\s+/', ' ', strip_tags($konten)));
            $articleSnippet = mb_strlen($plain) > 600
                ? mb_substr($plain, 0, 600) . '…'
                : $plain;
        }

        // Susun bagian konteks untuk prompt
        $contextParts = [];
        if ($filenameText) {
            $contextParts[] = "Nama file foto: \"{$filenameText}\"";
        }
        if ($surroundingText) {
            $contextParts[] = "Teks di sekitar foto dalam artikel:\n\"\"\"\n{$surroundingText}\n\"\"\"";
        }
        if ($articleSnippet) {
            $contextParts[] = "Isi artikel (ringkasan):\n\"\"\"\n{$articleSnippet}\n\"\"\"";
        }
        $contextBlock = implode("\n\n", $contextParts);

        return <<<PROMPT
Kamu adalah SEO expert level senior yang spesialis membantu website lokal (gereja, komunitas, UMKM) ranking tinggi di Google Search.

## TUGAS
Generate metadata SEO untuk SATU FOTO dari artikel paroki Gereja Katolik Tulungagung, Keuskupan Surabaya, Jawa Timur.
Output harus mengoptimalkan: SEO on-page gambar, rich results (schema.org), dan Local SEO Indonesia.

## DATA FOTO
- URL: {$src}
- Nama file bersih: "{$filenameText}"

## KONTEKS ARTIKEL
- Judul artikel: "{$judul}"
- Menu/kategori: {$menuLabel}
- Tags: {$tagsStr}

{$contextBlock}

## PRIORITAS SUMBER INFORMASI (urutan tertinggi → terendah)
1. Nama file foto — paling spesifik, biasanya memuat isi foto
2. Teks di sekitar foto — paragraf tepat di atas/bawah foto
3. Isi artikel — konteks tambahan
4. Judul artikel — latar belakang umum

## ATURAN OUTPUT (Wajib dipatuhi, ikuti panjang kata dengan ketat)

### 1. alt_text (10-15 kata, WAJIB)
Deskripsi konkret isi foto. Mulai dengan kata kerja aktif atau noun spesifik.
- WAJIB menyertakan: [nama kegiatan/orang/tempat spesifik dari konteks] + "Gereja Katolik Tulungagung" atau "Paroki SMDTBA Tulungagung"
- Sertakan kata lokasi: "Tulungagung" minimal 1x
- JANGAN pakai: "foto ini", "gambar ini", "terlihat", "dalam gambar"
- WAJIB berbeda dari title_attr (jangan copy)
- Contoh bagus: "Umat Katolik mengikuti Misa Kudus di Gereja SMDTBA Tulungagung bersama Romo Paroki"

### 2. title_attr (8-14 kata)
Tooltip singkat, format: "[Subjek + Aksi] di [Tempat] — Gereja Katolik Tulungagung"
- Harus spesifik, tidak generik
- Contoh: "Perayaan Ekaristi Hari Minggu di Gereja SMDTBA — Tulungagung"

### 3. caption (1-2 kalimat, 15-35 kata)
Naratif hangat, manusiawi, seperti caption jurnalis lokal.
- Sertakan: siapa (subjek), apa (aktivitas), di mana (lokasi spesifik)
- WAJIB menyebut "Tulungagung" minimal 1x
- Nada: hangat, informatif, tidak berlebihan
- Contoh: "Umat Paroki SMDTBA Tulungagung beribadah bersama dalam Misa Kudus Pagi Hari Minggu di halaman gereja."

### 4. schema_description (40-60 kata)
Deskripsi formal untuk schema.org ImageObject.
- Struktur: [Deskripsi isi foto] + [Konteks kegiatan/kegiatan paroki] + [Makna/signifikansi bagi komunitas]
- WAJIB menyebut: "Gereja Katolik Tulungagung", "Keuskupan Surabaya" (jika relevan), dan 1-2 kata kunci lokal
- Hindari kata "foto", "gambar", "image" di awal kalimat

### 5. keywords (8-12 kata, array JSON)
Kombinasikan 3 tipe keyword:
- 3-5 keyword spesifik dari isi foto (nama orang/kegiatan/tempat)
- 3-4 keyword lokal (Tulungagung, Paroki SMDTBA, Keuskupan Surabaya, dll)
- 1-2 long-tail keyword (contoh: "misa harian Gereja Katolik Tulungagung", "kegiatan paroki SMDTBA 2024")
- Semua dalam Bahasa Indonesia, lowercase, tanpa titik koma

## LARANGAN
- Tidak boleh ada teks kosong
- Tidak boleh "gambar ini", "foto ini", "terlihat", "diambil dari"
- Tidak boleh copy judul artikel ke alt_text
- Tidak boleh keyword duplikat
- Bahasa Indonesia formal (bukan slang)

## FORMAT RESPON
Balas HANYA dengan JSON valid, tanpa markdown, tanpa penjelasan, tanpa backtick:
{
  "alt_text": "...",
  "title_attr": "...",
  "caption": "...",
  "schema_description": "...",
  "keywords": ["...", "..."]
}
PROMPT;
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  CONTEXT EXTRACTION — ambil teks di sekitar posisi foto dalam HTML
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * Prompt builder untuk sumber NON-artikel:
     * galeri, umkm, dpp_bgkp, asisten_imam, wilayah
     */
    private static function buildSourcePrompt(string $src, array $artData): string
    {
        $sourceType   = $artData['source_type'] ?? $artData['menu'] ?? '';
        $judul        = trim($artData['judul']    ?? '');
        $kategori     = trim($artData['kategori'] ?? 'Gereja Katolik Tulungagung');
        $tags         = $artData['tags']  ?? '';
        $extra        = $artData['extra'] ?? [];

        $filename     = pathinfo($src, PATHINFO_FILENAME);
        $filenameText = self::cleanFilenameToText($filename);

        // Susun konteks spesifik per source type
        $konteksSpesifik = '';
        $tugasLabel      = '';

        switch ($sourceType) {
            case 'galeri':
                $tugasLabel = 'foto galeri paroki';
                $konteksSpesifik  = "Foto ini adalah foto galeri kegiatan/dokumentasi Gereja Katolik Tulungagung.\n";
                if ($judul) $konteksSpesifik .= "Judul/keterangan foto: \"{$judul}\"\n";
                if ($tags)  $konteksSpesifik .= "Info tambahan: \"{$tags}\"\n";
                break;

            case 'umkm':
                $tugasLabel = 'foto produk/usaha UMKM umat';
                $namaUsaha  = $extra['nama_usaha'] ?? '';
                $deskripsi  = $extra['deskripsi']  ?? '';
                $konteksSpesifik  = "Foto ini adalah foto produk atau profil usaha UMKM milik umat Gereja Katolik Tulungagung.\n";
                if ($namaUsaha) $konteksSpesifik .= "Nama usaha: \"{$namaUsaha}\"\n";
                if ($judul)     $konteksSpesifik .= "Judul UMKM: \"{$judul}\"\n";
                if ($deskripsi) $konteksSpesifik .= "Deskripsi usaha: \"{$deskripsi}\"\n";
                break;

            case 'dpp_bgkp':
                $tugasLabel = 'foto profil pengurus DPP/BGKP';
                $nama       = $extra['Nama']    ?? $judul;
                $bidang     = $extra['Bidang']  ?? '';
                $posisi     = $extra['Posisi']  ?? '';
                $tipe       = $extra['Tipe']    ?? 'DPP';
                $periode    = $extra['Periode'] ?? '';
                $konteksSpesifik  = "Foto ini adalah foto profil resmi pengurus {$tipe} (Dewan Paroki/Badan Gerejawi) Gereja Katolik Tulungagung.\n";
                if ($nama)    $konteksSpesifik .= "Nama pengurus: \"{$nama}\"\n";
                if ($bidang)  $konteksSpesifik .= "Bidang: \"{$bidang}\"\n";
                if ($posisi)  $konteksSpesifik .= "Posisi/jabatan: \"{$posisi}\"\n";
                if ($periode) $konteksSpesifik .= "Periode kepengurusan: {$periode}\n";
                break;

            case 'asisten_imam':
                $tugasLabel = 'foto profil asisten imam';
                $nama       = $extra['Nama']               ?? $judul;
                $asal       = $extra['Asal Lingk / Stasi'] ?? $extra['asal'] ?? '';
                $periode    = $extra['Periode']            ?? '';
                $konteksSpesifik  = "Foto ini adalah foto profil resmi Asisten Imam Gereja Katolik Tulungagung.\n";
                if ($nama)    $konteksSpesifik .= "Nama: \"{$nama}\"\n";
                if ($asal)    $konteksSpesifik .= "Asal lingkungan/stasi: \"{$asal}\"\n";
                if ($periode) $konteksSpesifik .= "Periode: {$periode}\n";
                break;

            case 'wilayah':
                $tugasLabel = 'foto profil ketua/koordinator wilayah';
                $nama       = $extra['nama']       ?? $judul;
                $role       = $extra['role']       ?? 'Ketua';
                $wilayah    = $extra['Wilayah']    ?? '';
                $lingkungan = $extra['Lingkungan'] ?? '';
                $konteksSpesifik  = "Foto ini adalah foto profil {$role} ";
                if ($lingkungan) $konteksSpesifik .= "Lingkungan {$lingkungan} ";
                if ($wilayah)    $konteksSpesifik .= "Wilayah {$wilayah} ";
                $konteksSpesifik .= "Gereja Katolik Tulungagung.\n";
                if ($nama) $konteksSpesifik .= "Nama: \"{$nama}\"\n";
                break;
        }

        return <<<PROMPT
Kamu adalah SEO expert level senior yang spesialis membantu website lokal (gereja, komunitas, UMKM) ranking tinggi di Google Search.

## TUGAS
Generate metadata SEO untuk SATU foto {$tugasLabel} dari website Gereja Katolik Tulungagung, Keuskupan Surabaya, Jawa Timur.
Output harus mengoptimalkan: SEO on-page gambar, rich results (schema.org), dan Local SEO Indonesia.

## DATA FOTO
- URL: {$src}
- Nama file bersih: "{$filenameText}"

## KONTEKS FOTO
{$konteksSpesifik}
Kategori halaman: {$kategori}

## PRIORITAS SUMBER INFORMASI (urutan tertinggi → terendah)
1. Data identitas (nama orang/usaha, jabatan, bidang, lingkungan/stasi) — paling spesifik
2. Nama file foto — mencerminkan isi foto secara langsung
3. Kategori halaman — konteks umum

## ATURAN OUTPUT (Wajib dipatuhi)

### 1. alt_text (10-15 kata, WAJIB)
Deskripsi konkret isi foto. Mulai dengan nama subjek atau kata kerja aktif.
- WAJIB menyertakan: [nama orang/usaha spesifik jika ada] + "Gereja Katolik Tulungagung" atau "Paroki SMDTBA Tulungagung"
- Sertakan kata lokasi: "Tulungagung" minimal 1x
- JANGAN pakai: "foto ini", "gambar ini", "terlihat", "dalam gambar"
- WAJIB berbeda dari title_attr

### 2. title_attr (8-14 kata)
Tooltip singkat, format: "[Nama Subjek/Usaha] — [Jabatan/Kategori] Gereja Katolik Tulungagung"

### 3. caption (1-2 kalimat, 15-35 kata)
Naratif hangat, manusiawi, informatif.
- Jelaskan peran, bidang karya, atau produk usaha ini bagi paroki
- WAJIB menyebut "Tulungagung" minimal 1x

### 4. schema_description (40-60 kata)
Deskripsi formal untuk schema.org ImageObject / Person / LocalBusiness.
- Struktur: [Identitas subjek/usaha] + [Peran/layanan di komunitas paroki] + [Lokasi: Tulungagung, Keuskupan Surabaya, Jawa Timur]

### 5. keywords (8-12 kata, array JSON)
Kombinasi keyword nama spesifik, peran/kategori, dan Local SEO Tulungagung.

## FORMAT RESPON
Balas HANYA dengan JSON valid, tanpa markdown, tanpa penjelasan, tanpa backtick:
{
  "alt_text": "...",
  "title_attr": "...",
  "caption": "...",
  "schema_description": "...",
  "keywords": ["...", "..."]
}
PROMPT;
    }

    /**
     * Ambil teks paragraf yang ada di sekitar <img src="..."> dalam HTML artikel.
     * Mencari paragraf/kalimat di atas dan di bawah posisi foto.
     */
    private static function extractContextAroundImage(string $html, string $imgSrc): string
    {
        if (!$html || !$imgSrc) return '';

        // Encode src untuk matching (bisa ada & di URL)
        $srcEscaped  = htmlspecialchars($imgSrc, ENT_QUOTES);
        $srcBasename = basename(parse_url($imgSrc, PHP_URL_PATH) ?: $imgSrc);

        // Cari posisi tag <img> yang matching
        $imgPos = false;
        foreach ([$imgSrc, $srcEscaped, $srcBasename] as $needle) {
            $pos = mb_stripos($html, $needle);
            if ($pos !== false) {
                $imgPos = $pos;
                break;
            }
        }
        if ($imgPos === false) return '';

        // Ambil HTML sebelum dan sesudah posisi foto (window ~800 char masing-masing)
        $before = mb_substr($html, max(0, $imgPos - 800), min($imgPos, 800));
        $after  = mb_substr($html, $imgPos, 800);

        // Bersihkan HTML → teks biasa
        $textBefore = trim(preg_replace('/\s+/', ' ', strip_tags($before)));
        $textAfter  = trim(preg_replace('/\s+/', ' ', strip_tags($after)));

        // Ambil ~200 karakter terakhir sebelum foto (kalimat/paragraf terdekat)
        $contextBefore = '';
        if ($textBefore) {
            $cut = mb_substr($textBefore, -250);
            // Mulai dari awal kalimat jika bisa
            $sentStart = mb_strpos($cut, '. ');
            if ($sentStart !== false && $sentStart < 80) {
                $cut = mb_substr($cut, $sentStart + 2);
            }
            $contextBefore = trim($cut);
        }

        // Ambil ~200 karakter pertama setelah foto
        $contextAfter = '';
        if ($textAfter) {
            // Hapus nama file sendiri dari teks after jika ada
            $cut = preg_replace('/\b' . preg_quote($srcBasename, '/') . '\b/i', '', $textAfter);
            $cut = trim(mb_substr(trim($cut), 0, 250));
            // Potong di akhir kalimat jika bisa
            $sentEnd = mb_strrpos($cut, '. ');
            if ($sentEnd !== false && $sentEnd > 80) {
                $cut = mb_substr($cut, 0, $sentEnd + 1);
            }
            $contextAfter = trim($cut);
        }

        $parts = array_filter([$contextBefore, $contextAfter]);
        return implode(' … ', $parts);
    }

    /**
     * Bersihkan nama file menjadi teks bermakna untuk prompt AI.
     * Hapus: hash, timestamp, UUID, angka acak, prefix/suffix teknis.
     * Pertahankan: kata-kata deskriptif, nama orang/tempat/kegiatan.
     */
    private static function cleanFilenameToText(string $filename): string
    {
        if (!$filename) return '';

        $f = $filename;

        // Hapus ekstensi jika masih ada
        $f = preg_replace('/\.(jpg|jpeg|png|gif|webp|bmp|svg)$/i', '', $f);

        // Pisahkan dengan spasi: ganti - _ . dengan spasi
        $f = str_replace(['-', '_', '.'], ' ', $f);

        // Hapus hash hex panjang (md5/sha, biasanya >8 char hex murni)
        $f = preg_replace('/\b[0-9a-f]{8,}\b/i', '', $f);

        // Hapus timestamp (13 digit epoch, atau format YYYYMMDD, YYYY-MM-DD dll)
        $f = preg_replace('/\b\d{10,13}\b/', '', $f);       // unix timestamp
        $f = preg_replace('/\b(20|19)\d{6,8}\b/', '', $f);  // YYYYMMDD / YYYYMMDDHHII

        // Hapus UUID-like patterns
        $f = preg_replace('/\b[0-9a-f]{4,}-[0-9a-f]{4,}[-0-9a-f]*\b/i', '', $f);

        // Hapus angka murni yang panjang (>3 digit) tapi bukan tahun
        $f = preg_replace('/\b\d{4,}\b(?!\s*(an|an\b))/', '', $f);

        // Hapus kata-kata teknis yang tidak bermakna
        $stopTech = [
            'img', 'image', 'photo', 'pic', 'pict', 'foto', 'dsc', 'dscf',
            'tmp', 'temp', 'file', 'new', 'copy', 'final', 'rev', 'edit',
            'web', 'upload', 'uploaded', 'asset', 'bg', 'thumb', 'thumbnail',
            'resized', 'compressed', 'small', 'large', 'medium', 'banner',
            'cover', 'header', 'backup', 'ori', 'original', 'resize',
            'whatsapp', 'wa', 'received', 'sent', 'screenshot', 'screen',
        ];
        foreach ($stopTech as $stop) {
            $f = preg_replace('/\b' . preg_quote($stop, '/') . '\b/i', '', $f);
        }

        // Bersihkan whitespace berlebih
        $f = trim(preg_replace('/\s+/', ' ', $f));

        // Jika hasil terlalu pendek (< 3 char), kembalikan kosong
        return mb_strlen($f) >= 3 ? $f : '';
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  GEMINI CALLER — multi-key rotation
    // ══════════════════════════════════════════════════════════════════════════

    private static function callGemini(string $prompt): ?array
    {
        $keys = self::getGeminiKeys();
        if (empty($keys)) return null;

        $payload = [
            'contents' => [
                ['role' => 'user', 'parts' => [['text' => $prompt]]],
            ],
            'generationConfig' => [
                'temperature'     => 0.7,
                'maxOutputTokens' => 600,
            ],
        ];

        foreach ($keys as $key) {
            foreach (self::GEMINI_MODELS as $model) {
                $endpoint = 'https://generativelanguage.googleapis.com/v1beta/models/'
                          . $model . ':generateContent?key=' . $key;

                $result = self::httpPost($endpoint, $payload, [
                    'Content-Type: application/json',
                    'User-Agent: SMDTBA-SEO/7.0',
                ]);

                if ($result === 'RATE_LIMIT') {
                    break; // pindah key
                }
                if ($result !== null) {
                    $text = self::extractGeminiText($result);
                    if ($text) {
                        return ['text' => $text, 'model' => $model];
                    }
                }
            }
        }

        return null;
    }

    private static function extractGeminiText(array $response): string
    {
        $parts = $response['candidates'][0]['content']['parts'] ?? [];
        foreach ($parts as $part) {
            if (!empty($part['text'])) return trim($part['text']);
        }
        return '';
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  GROQ CALLER — multi-key rotation
    // ══════════════════════════════════════════════════════════════════════════

    private static function callGroq(string $prompt): ?array
    {
        $keys = self::getGroqKeys();
        if (empty($keys)) return null;

        $messages = [
            [
                'role'    => 'system',
                'content' => 'Kamu adalah SEO specialist. Balas HANYA dengan JSON valid sesuai format yang diminta, tanpa markdown code block, tanpa penjelasan tambahan.',
            ],
            [
                'role'    => 'user',
                'content' => $prompt,
            ],
        ];

        foreach ($keys as $key) {
            foreach (self::GROQ_MODELS as $model) {
                $payload = [
                    'model'       => $model,
                    'messages'    => $messages,
                    'temperature' => 0.7,
                    'max_tokens'  => 600,
                ];

                $result = self::httpPost(self::GROQ_ENDPOINT, $payload, [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $key,
                    'User-Agent: SMDTBA-SEO/7.0',
                ]);

                if ($result === 'RATE_LIMIT') {
                    break; // pindah key
                }
                if ($result !== null) {
                    $text = trim($result['choices'][0]['message']['content'] ?? '');
                    if ($text) {
                        return ['text' => $text, 'model' => $model];
                    }
                }
            }
        }

        return null;
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  HTTP POST HELPER — cURL dengan fallback stream
    //  Return: array (parsed JSON) | 'RATE_LIMIT' | null
    // ══════════════════════════════════════════════════════════════════════════

    private static function httpPost(string $url, array $payload, array $headers): mixed
    {
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE);

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $json,
                CURLOPT_TIMEOUT        => self::AI_TIMEOUT,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_HTTPHEADER     => $headers,
                CURLOPT_SSL_VERIFYPEER => false,
            ]);
            $raw  = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($code === 429) return 'RATE_LIMIT';
            if ($code < 200 || $code >= 300 || !$raw) return null;

            $data = json_decode($raw, true);
            return is_array($data) ? $data : null;
        }

        // Fallback: stream_context
        $headerStr = implode("\r\n", $headers);
        $ctx = stream_context_create([
            'http' => [
                'method'        => 'POST',
                'header'        => $headerStr . "\r\n",
                'content'       => $json,
                'timeout'       => self::AI_TIMEOUT,
                'ignore_errors' => true,
            ],
            'ssl' => ['verify_peer' => false],
        ]);
        $raw    = @file_get_contents($url, false, $ctx);
        $status = $http_response_header[0] ?? '';

        if (str_contains($status, '429')) return 'RATE_LIMIT';
        if (!$raw || !str_contains($status, '200')) return null;

        $data = json_decode($raw, true);
        return is_array($data) ? $data : null;
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  PARSE AI RESPONSE — ekstrak JSON dari teks AI
    // ══════════════════════════════════════════════════════════════════════════

    private static function parseAIResponse(string $text): ?array
    {
        // Bersihkan markdown code block jika ada
        $text = preg_replace('/```json\s*/i', '', $text);
        $text = preg_replace('/```\s*/i', '', $text);
        $text = trim($text);

        // Cari JSON object di dalam teks
        $start = strpos($text, '{');
        $end   = strrpos($text, '}');
        if ($start === false || $end === false || $end <= $start) return null;

        $jsonStr = substr($text, $start, $end - $start + 1);
        $data    = json_decode($jsonStr, true);

        if (!is_array($data)) return null;

        // Validasi field wajib
        if (empty($data['alt_text'])) return null;

        $kw = $data['keywords'] ?? [];
        if (is_string($kw)) {
            $kw = json_decode($kw, true) ?: array_filter(array_map('trim', explode(',', $kw)));
        }
        if (!is_array($kw)) $kw = [];
        // Normalisasi: lowercase, hapus duplikat (case-insensitive), max 12
        $seenKw = [];
        $kwNorm = [];
        foreach (array_map(fn($k) => mb_strtolower(trim((string)$k)), $kw) as $k) {
            if ($k === '' || isset($seenKw[$k])) continue;
            $seenKw[$k] = true;
            $kwNorm[] = $k;
            if (count($kwNorm) >= 12) break;
        }

        $alt = self::limit(trim($data['alt_text'] ?? ''), 130);
        $title = self::limit(trim($data['title_attr'] ?? ''), 110);

        return [
            'alt'         => $alt,
            'caption'     => self::limit(trim($data['caption'] ?? ''), 260),
            'title'       => $title !== $alt ? $title : ($alt ?: ''),
            'description' => self::limit(trim($data['schema_description'] ?? ''), 400),
            'keywords'    => $kwNorm,
        ];
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  API KEY HELPERS — baca dari secrets.php via konstanta
    // ══════════════════════════════════════════════════════════════════════════

    private static function getGeminiKeys(): array
    {
        // Prioritas: konstanta GEMINI_API_KEYS (dari chatbot config) atau SECRET_GEMINI_API_KEYS
        if (defined('GEMINI_API_KEYS') && is_array(GEMINI_API_KEYS)) {
            return array_values(array_filter(GEMINI_API_KEYS));
        }
        if (defined('SECRET_GEMINI_API_KEYS') && is_array(SECRET_GEMINI_API_KEYS)) {
            return array_values(array_filter(SECRET_GEMINI_API_KEYS));
        }
        // Load secrets jika belum di-load
        $secretsPath = privatePath('secrets.php');
        if (file_exists($secretsPath) && !defined('SECRET_GEMINI_API_KEYS')) {
            require_once $secretsPath;
        }
        if (defined('SECRET_GEMINI_API_KEYS') && is_array(SECRET_GEMINI_API_KEYS)) {
            return array_values(array_filter(SECRET_GEMINI_API_KEYS));
        }
        return [];
    }

    private static function getGroqKeys(): array
    {
        if (defined('GROQ_API_KEYS') && is_array(GROQ_API_KEYS)) {
            return array_values(array_filter(GROQ_API_KEYS));
        }
        if (defined('SECRET_GROQ_API_KEYS') && is_array(SECRET_GROQ_API_KEYS)) {
            return array_values(array_filter(SECRET_GROQ_API_KEYS));
        }
        $secretsPath = privatePath('secrets.php');
        if (file_exists($secretsPath) && !defined('SECRET_GROQ_API_KEYS')) {
            require_once $secretsPath;
        }
        if (defined('SECRET_GROQ_API_KEYS') && is_array(SECRET_GROQ_API_KEYS)) {
            return array_values(array_filter(SECRET_GROQ_API_KEYS));
        }
        return [];
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  UTILITIES
    // ══════════════════════════════════════════════════════════════════════════

    private static function limit(string $text, int $max): string
    {
        $text = trim(preg_replace('/\s+/', ' ', strip_tags($text)));
        return mb_strlen($text) > $max ? mb_substr($text, 0, $max - 1) . '…' : $text;
    }

    private static function emptyResult(array $artData = []): array
    {
        $kategori = $artData['kategori'] ?? 'Paroki';
        $judul    = $artData['judul']    ?? 'Gereja Katolik Tulungagung';
        return [
            'alt'         => "Dokumentasi kegiatan {$kategori} Gereja Katolik Tulungagung",
            'caption'     => "Kegiatan {$kategori} umat Gereja Katolik Tulungagung, Keuskupan Surabaya.",
            'title'       => "Gereja Katolik Tulungagung",
            'description' => "Foto kegiatan {$judul} — Gereja Katolik Tulungagung, Jawa Timur. Keuskupan Surabaya.",
            'keywords'    => ['paroki tulungagung', 'gereja katolik tulungagung', 'smdtba', 'keuskupan surabaya'],
            '_model'      => self::VERSION . ':empty',
        ];
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  SUPABASE OPERATIONS
    // ══════════════════════════════════════════════════════════════════════════

    private static function fetchFromSupabase(string $src): ?array
    {
        if (!self::sbUrl() || !self::sbKey()) return null;

        $url = self::sbUrl() . '/rest/v1/' . self::TABLE
             . '?image_url=eq.' . urlencode($src)
             . '&select=alt_text,caption,title_attr,schema_description,image_keywords,model_used'
             . '&limit=1';

        $resp = self::sbGet($url);
        if (!$resp || empty($resp[0])) return null;

        $row = $resp[0];
        $kw  = $row['image_keywords'] ?? '[]';
        if (is_string($kw)) $kw = json_decode($kw, true);
        if (!is_array($kw)) $kw = [];

        return [
            'alt'         => $row['alt_text']          ?? '',
            'caption'     => $row['caption']            ?? '',
            'title'       => $row['title_attr']         ?? '',
            'description' => $row['schema_description'] ?? '',
            'keywords'    => $kw,
            '_model'      => 'supabase-cache',
        ];
    }

    private static function saveToSupabase(string $src, array $data, array $artData): void
    {
        if (!self::sbUrl() || !self::sbKey()) return;

        $url       = self::sbUrl() . '/rest/v1/' . self::TABLE . '?on_conflict=image_url';
        $artikelId = !empty($artData['id']) ? (string)$artData['id'] : null;

        $payload = [
            'image_url'          => $src,
            'artikel_judul'      => mb_substr($artData['display_name'] ?? $artData['judul'] ?? '', 0, 255),
            'alt_text'           => $data['alt']         ?? '',
            'caption'            => $data['caption']     ?? '',
            'title_attr'         => $data['title']       ?? '',
            'schema_description' => $data['description'] ?? '',
            'image_keywords'     => json_encode(
                is_array($data['keywords']) ? $data['keywords'] : [],
                JSON_UNESCAPED_UNICODE
            ),
            'model_used' => $data['_model'] ?? self::VERSION,
            'updated_at' => date('c'),
        ];
        if ($artikelId !== null) $payload['artikel_id'] = $artikelId;

        $sbHeaders = [
            'apikey: '               . self::sbKey(),
            'Authorization: Bearer ' . self::sbKey(),
            'Content-Type: application/json',
            'Prefer: return=minimal,resolution=merge-duplicates',
        ];

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $sent = false;

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 8,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $json,
                CURLOPT_HTTPHEADER     => $sbHeaders,
                CURLOPT_SSL_VERIFYPEER => false,
            ]);
            $resp = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $cerr = curl_error($ch);
            curl_close($ch);
            $sent = in_array($code, [200, 201, 204]);
            if (!$sent) {
                error_log("[ImageSeoGenerator] Supabase upsert failed. HTTP=$code curl_err=$cerr resp=" . substr((string)$resp, 0, 300));
            }
        }

        if (!$sent) {
            $ctx2 = stream_context_create([
                'http' => [
                    'method'        => 'POST',
                    'header'        => implode("\r\n", $sbHeaders) . "\r\n",
                    'content'       => $json,
                    'timeout'       => 8,
                    'ignore_errors' => true,
                ],
                'ssl' => ['verify_peer' => false],
            ]);
            $resp2  = @file_get_contents($url, false, $ctx2);
            $status = $http_response_header[0] ?? '';
            if (!str_contains($status, '20')) {
                error_log("[ImageSeoGenerator] Supabase fallback failed. status=$status resp=" . substr((string)$resp2, 0, 300));
            }
        }
    }

    private static function deleteFromSupabase(string $src): void
    {
        if (!self::sbUrl() || !self::sbKey()) return;
        $url = self::sbUrl() . '/rest/v1/' . self::TABLE
             . '?image_url=eq.' . urlencode($src);
        $ctx = stream_context_create([
            'http' => [
                'method'        => 'DELETE',
                'header'        => implode("\r\n", [
                    'apikey: '               . self::sbKey(),
                    'Authorization: Bearer ' . self::sbKey(),
                    'Content-Type: application/json',
                ]) . "\r\n",
                'timeout'       => 5,
                'ignore_errors' => true,
            ],
            'ssl' => ['verify_peer' => false],
        ]);
        @file_get_contents($url, false, $ctx);
    }

    private static function sbGet(string $url): ?array
    {
        $ctx = stream_context_create([
            'http' => [
                'header'        => implode("\r\n", [
                    'apikey: '               . self::sbKey(),
                    'Authorization: Bearer ' . self::sbKey(),
                    'Accept: application/json',
                ]) . "\r\n",
                'timeout'       => 4,
                'ignore_errors' => true,
            ],
            'ssl' => ['verify_peer' => false],
        ]);
        $resp = @file_get_contents($url, false, $ctx);
        if (!$resp) return null;
        $data = json_decode($resp, true);
        return is_array($data) ? $data : null;
    }

    private static function sbUrl(): string
    {
        $v = defined('SUPABASE_URL') ? SUPABASE_URL : (getenv('SUPABASE_URL') ?: '');
        return rtrim($v, '/');
    }

    private static function sbKey(): string
    {
        if (defined('SUPABASE_SERVICE_KEY') && SUPABASE_SERVICE_KEY) return SUPABASE_SERVICE_KEY;
        return getenv('SUPABASE_SERVICE_KEY') ?: '';
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  FILE CACHE
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * Direktori cache lokal — dipusatkan di /cache/imgseo/ (bukan lagi
     * sys_get_temp_dir()) supaya persisten antar-request, tidak ikut
     * terhapus oleh housekeeping OS/hosting, dan bisa dikelola lewat
     * halaman admin Sistem → Cache seperti cache lainnya.
     */
    private static function cacheDir(): string
    {
        $root = $_SERVER['DOCUMENT_ROOT'] ?? dirname(__DIR__, 2);
        $dir  = rtrim($root, '/') . '/cache/imgseo';
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        return $dir;
    }

    private static function cacheFile(string $key): string
    {
        return self::cacheDir() . '/imgseo_' . md5($key) . '.cache';
    }

    private static function cacheGet(string $key): ?array
    {
        $file = self::cacheFile($key);
        if (!file_exists($file)) return null;
        if ((time() - filemtime($file)) > self::CACHE_TTL) return null;
        $raw  = @file_get_contents($file);
        if (!$raw) return null;
        $data = @unserialize($raw);
        return is_array($data) ? $data : null;
    }

    private static function cacheSet(string $key, array $data): void
    {
        $file = self::cacheFile($key);
        $tmp  = $file . '.tmp.' . getmypid();
        if (@file_put_contents($tmp, serialize($data), LOCK_EX) !== false) {
            @rename($tmp, $file);
        } else {
            @unlink($tmp);
        }
    }
}