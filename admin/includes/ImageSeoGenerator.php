<?php


class ImageSeoGenerator
{
    private const TABLE     = 'image_seo';
    private const CACHE_TTL = 86400;       // 1 hari file-cache lokal
    private const VERSION   = 'v6-php';   // tandai di kolom model_used

    /**
     * In-memory cache agar tidak ada N+1 query ke Supabase.
     * Diisi oleh preloadForArticle() sebelum loop gambar dimulai.
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

            $cacheKey = 'imgseo_v6_' . md5($imgUrl);
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

        $cacheKey = 'imgseo_v6_' . md5($src);

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

        $result = self::generateWithTemplates($src, $artData);

        self::saveToSupabase($src, $result, $artData);
        self::$memoryCache[$cacheKey] = $result;
        self::cacheSet($cacheKey, $result);

        return $result;
    }

    /** Force-regenerate: hapus cache + Supabase record, lalu generate ulang. */
    public static function forceRegenerate(string $src, array $artData): array
    {
        $cacheKey = 'imgseo_v6_' . md5($src);
        $file     = sys_get_temp_dir() . '/paroki_' . md5($cacheKey) . '.cache';
        @unlink($file);
        unset(self::$memoryCache[$cacheKey]);
        self::deleteFromSupabase($src);
        return self::generate($src, $artData);
    }

    /** Dipakai oleh admin/seo-generator.php. */
    public static function forceGenerateToSupabase(string $src, array $artData): bool
    {
        $src = trim($src);
        if (!$src) return false;

        $result = self::generateWithTemplates($src, $artData);
        self::saveToSupabase($src, $result, $artData);

        $cacheKey = 'imgseo_v6_' . md5($src);
        self::$memoryCache[$cacheKey] = $result;

        return true;
    }

    /** Hapus semua file cache lokal. */
    public static function clearFileCache(): int
    {
        $tmpDir  = sys_get_temp_dir();
        $deleted = 0;
        foreach (glob($tmpDir . '/paroki_*.cache') ?: [] as $file) {
            if (@unlink($file)) $deleted++;
        }
        self::$memoryCache = [];
        return $deleted;
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  TEMPLATE ENGINE — INTI TANPA AI
    // ══════════════════════════════════════════════════════════════════════════

    private static function generateWithTemplates(string $src, array $artData): array
    {
        $judul    = trim($artData['judul']    ?? '');
        $kategori = trim($artData['kategori'] ?? 'Paroki Tulungagung');
        $menu     = trim($artData['menu']     ?? '');
        $tags     = $artData['tags'] ?? [];
        if (is_string($tags)) {
            $tags = array_filter(array_map('trim', explode(',', $tags)));
        }

        $filename    = self::cleanFilename($src);
        $isGeneric   = self::isGenericFilename($filename);
        $judulPendek = mb_substr($judul, 0, 55);
        $judulSingkat= self::shortTitle($judul, 35);

        $ctx  = self::detectContext($judul, $filename, $tags, $kategori, $menu);
        $seed = abs(crc32($src));

        $ph = [
            '{judul}'          => $judulPendek,
            '{judul_singkat}'  => $judulSingkat,
            '{kategori}'       => $kategori,
            '{menu_label}'     => self::menuLabel($menu, $kategori),
            '{filename}'       => $isGeneric ? $judulSingkat : $filename,
            '{paroki}'         => 'Paroki SMDTBA Tulungagung',
            '{paroki_panjang}' => 'Paroki Santa Maria Dengan Tidak Bernoda Asal Tulungagung',
            '{kota}'           => 'Tulungagung',
            '{tahun}'          => date('Y'),
        ];

        $altPool  = self::altTemplates($ctx, $isGeneric);
        $capPool  = self::captionTemplates($ctx, $isGeneric);
        $titPool  = self::titleTemplates($ctx, $isGeneric);
        $descPool = self::descTemplates($ctx, $isGeneric);

        $alt  = self::fillPlaceholders(self::pick($altPool,  $seed),     $ph);
        $cap  = self::fillPlaceholders(self::pick($capPool,  $seed + 1), $ph);
        $tit  = self::fillPlaceholders(self::pick($titPool,  $seed + 2), $ph);
        $desc = self::fillPlaceholders(self::pick($descPool, $seed + 3), $ph);

        $keywords = self::buildKeywords($ctx, $judul, $tags, $kategori, $filename, $seed);

        return [
            'alt'         => self::limit($alt,  130),
            'caption'     => self::limit($cap,  260),
            'title'       => self::limit($tit,  110),
            'description' => self::limit($desc, 400),
            'keywords'    => $keywords,
            '_model'      => self::VERSION . ':' . $ctx,
        ];
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  CONTEXT DETECTION
    //  Scoring per sinyal — konteks dengan total skor tertinggi menang.
    //  Sinyal yang LEBIH SPESIFIK daftar lebih dulu agar tidak tersapu konteks luas.
    // ══════════════════════════════════════════════════════════════════════════

    private static function detectContext(
        string $judul,
        string $filename,
        array  $tags,
        string $kategori,
        string $menu
    ): string {
        $text = mb_strtolower(
            $judul . ' ' . $filename . ' ' . implode(' ', $tags) . ' ' . $kategori . ' ' . $menu
        );

        $scores = [];

        $signals = [

            // ── Pekan Suci: sub-konteks spesifik DULU ─────────────────────────
            'vigili_paskah' => [
                'vigili paskah', 'misa vigili', 'api paskah', 'exsultet',
                'lumen christi', 'lilin paskah dinyalakan', 'alleluia pertama',
                'baptis vigili', 'malam paskah', 'malam terbesar', 'vigili suci',
                'pembaptisan vigili', 'nyanyian pujian paskah', 'perarakan paskah',
            ],
            'sabtu_suci' => [
                'sabtu suci', 'keheningan sabtu', 'kristus di dalam kubur',
                'hari terdiam', 'menunggu kebangkitan', 'tidak ada misa siang',
            ],
            'jumat_agung' => [
                'jumat agung', 'ibadat sengsara', 'penghormatan salib',
                'salib diciumi', 'bacaan sengsara menurut yohanes',
                'yesus wafat di salib', 'gereja hening jumat', 'tidak ada lonceng',
                'via crucis agung', 'kolekte tanah suci', 'gereja berkabung',
                'ibadat jumat agung', 'imam berbaring di lantai',
            ],
            'kamis_putih' => [
                'kamis putih', 'misa in caena domini', 'pembasuhan kaki',
                'mandatum', 'perjamuan malam terakhir', 'tuguran',
                'jaga malam', 'taman getsemani', 'tabernakulum dikosongkan',
                'altar dilucuti', 'lembaga ekaristi', 'malam perjamuan tuhan',
                'romi mencuci kaki', 'romo mencuci kaki',
            ],
            'minggu_palma' => [
                'minggu palma', 'prosesi palma', 'daun palma',
                'perarakan palma', 'hosana putera daud', 'yesus masuk yerusalem',
                'sengsara panjang', 'pemberkatan palma', 'pawai palma',
                'perarakan masuk gereja',
            ],
            'pekan_suci' => [
                'pekan suci', 'minggu suci', 'trihari suci', 'triduum paskah',
                'triduum', 'liturgi pekan suci',
            ],

            // ── Prapaskah ──────────────────────────────────────────────────────
            'prapaskah' => [
                'masa prapaskah', 'rabu abu', 'penerimaan abu', 'abu di dahi',
                'ingatlah engkau adalah debu', 'bertobatlah',
                'aksi puasa pembangunan', 'pantang daging', 'puasa prapaskah',
                'pertobatan prapaskah', 'kolekte app', 'jalan salib jumat',
                'masa pertobatan', 'minggu prapaskah',
            ],

            // ── Masa Paskah ───────────────────────────────────────────────────
            'paskah' => [
                'perayaan paskah', 'misa paskah', 'kristus bangkit',
                'kebangkitan kristus', 'alleluia', 'ia sungguh bangkit',
                'surrexit dominus', 'minggu paskah', 'oktaf paskah',
                'kerahiman ilahi', 'koronka', 'paskah kristus',
            ],
            'kenaikan' => [
                'kenaikan tuhan', 'kenaikan yesus', 'hari raya kenaikan',
                'kenaikan ke surga', 'misa kenaikan',
                'empat puluh hari setelah paskah',
            ],
            'pentakosta' => [
                'hari raya pentakosta', 'turunnya roh kudus', 'lahirnya gereja',
                'karunia roh kudus', 'misa pentakosta', 'lidah api',
                'warna merah pentakosta', 'roh kudus turun',
            ],

            // ── Natal & rangkaiannya ──────────────────────────────────────────
            'natal' => [
                'misa malam natal', 'misa natal', 'malam kudus',
                'perayaan natal', 'kelahiran yesus', 'kandang natal',
                'pohon natal', 'kreche natal', 'lagu natal', 'drama natal',
                'desember natal', 'misa subuh natal', 'selamat natal',
            ],
            'keluarga_kudus' => [
                'pesta keluarga kudus', 'keluarga nazaret', 'yesus maria yusuf',
                'minggu setelah natal', 'misa keluarga kudus',
                'teladan keluarga kristiani',
            ],
            'maria_bunda_allah' => [
                'hari raya maria bunda allah', 'misa 1 januari',
                'misa tahun baru', 'theotokos', 'bunda allah',
                'hari perdamaian sedunia', '1 januari',
            ],
            'epifani' => [
                'epifani', 'penampakan tuhan', 'tiga raja', 'orang majus',
                'bintang betlehem', '6 januari', 'emas kemenyan mur',
                'kaspar melkior baltasar',
            ],
            'pembaptisan_tuhan' => [
                'pembaptisan tuhan', 'yesus dibaptis', 'sungai yordan',
                'roh kudus seperti merpati', 'inilah puteraku yang terkasih',
                'yohanes pembaptis', 'pembaptisan kristus',
            ],

            // ── Adven ─────────────────────────────────────────────────────────
            'adven' => [
                'masa adven', 'lilin adven', 'lingkaran adven',
                'penantian natal', 'penantian kristus', 'minggu adven',
                'gaudete', 'lilin merah muda adven', 'kalender adven',
            ],

            // ── Hari Raya Masa Biasa ──────────────────────────────────────────
            'corpus_christi' => [
                'corpus christi', 'tubuh dan darah kristus',
                'prosesi sakramen mahakudus', 'monstrans dibawa prosesi',
                'prosesi corpus', 'umat mengiringi sakramen',
                'hari raya tubuh dan darah',
            ],
            'hati_yesus' => [
                'hati yesus mahakudus', 'devosi hati kudus', 'pertama jumat',
                'misa pertama jumat', 'novena hati kudus', 'litani hati kudus',
                'hari raya hati yesus',
            ],
            'maria_diangkat' => [
                'maria diangkat ke surga', 'assumption', '15 agustus',
                'hari raya bunda maria agustus', 'solemnitas maria agustus',
            ],
            'pesta_pelindung' => [
                'pesta pelindung', 'pesta nama paroki', '8 desember',
                'santa maria dengan tidak bernoda', 'immaculate conception',
                'bunda tanpa noda', 'patrona paroki', 'pesta besar smdtba',
                'hari jadi paroki smdtba',
            ],
            'kristus_raja' => [
                'kristus raja', 'hari raya kristus raja', 'penutup tahun liturgi',
                'minggu terakhir masa biasa', 'solemnitas kristus raja',
            ],

            // ── Sakramen ──────────────────────────────────────────────────────
            'baptis' => [
                'sakramen baptis', 'pembaptisan', 'mandi baptis',
                'baptis bayi', 'baptis dewasa', 'baptis massal',
                'calon baptis', 'katekumen', 'air baptis',
                'bejana baptis', 'rcia', 'wali baptis',
            ],
            'komuni' => [
                'komuni pertama', 'penerimaan komuni pertama',
                'meja tuhan', 'anak komuni', 'komuni anak',
                'misa komuni pertama',
            ],
            'krisma' => [
                'sakramen krisma', 'sakramen penguatan', 'misa krisma',
                'penerimaan krisma', 'minyak krisma', 'meterai roh kudus',
                'dikrisma', 'persiapan krisma',
            ],
            'pernikahan' => [
                'pernikahan katolik', 'pemberkatan nikah',
                'misa pernikahan', 'pesta perkawinan',
                'sakramen perkawinan', 'janji pernikahan',
                'cincin nikah', 'pengantin baru', 'menikah di gereja',
            ],
            'tobat' => [
                'sakramen tobat', 'pengakuan dosa', 'rekonsiliasi',
                'bilik tobat', 'rekonsiliasi massal',
                'ibadat tobat', 'tobat komunal', 'pengampunan dosa',
            ],
            'tahbisan' => [
                'tahbisan imam', 'penahbisan imam', 'misa tahbisan',
                'sakramen imamat', 'imam baru', 'penumpangan tangan',
                'imam ditahbiskan', 'proskynesis', 'imam berbaring',
            ],

            // ── Kelompok & Kategorial ─────────────────────────────────────────
            'mudika' => [
                'orang muda katolik', 'mudika paroki', 'omk paroki',
                'pemuda gereja', 'generasi muda paroki',
                'live in mudika', 'retret mudika', 'kegiatan omk',
                'mudika smdtba',
            ],
            'wkri' => [
                'wkri paroki', 'wanita katolik ri',
                'anggota wkri', 'pertemuan wkri', 'ibu-ibu paroki',
            ],
            'legio' => [
                'legio maria', 'legion of mary',
                'presidium legio', 'anggota legio',
            ],
            'karismatik' => [
                'pembaruan karismatik', 'karismatik katolik',
                'seminar hidup baru', 'doa penyembuhan karismatik',
                'pujian penyembahan', 'doa dalam roh', 'pkk paroki',
            ],
            'cursillo' => [
                'cursillo paroki', 'ultreya cursillo',
                'de colores', 'komunitas cursillo',
            ],
            'lansia' => [
                'kelompok lansia', 'lanjut usia paroki',
                'komunitas lansia', 'kelompok opa oma', 'umat lanjut usia',
            ],
            'koor' => [
                'paduan suara', 'koor liturgi', 'koor gereja',
                'dirigen gereja', 'kelompok koor', 'tim musik liturgi',
            ],
            'lektor' => [
                'lektor paroki', 'pemazmur paroki', 'prodiakon paroki',
                'misdinar paroki', 'putera altar', 'putri altar',
            ],

            // ── Liturgi harian & devosi ───────────────────────────────────────
            'misa' => [
                'misa kudus', 'perayaan ekaristi', 'ekaristi',
                'ibadat sabda', 'homili', 'konsekrasi',
                'bacaan injil', 'ritus komuni', 'imam selebran', 'ordo misa',
                'misa harian', 'misa minggu',
            ],
            'rosario' => [
                'doa rosario', 'rosario bersama', 'manik rosario',
                'misteri rosario', 'bulan rosario',
                'oktober rosario', 'lima puluh salam maria',
            ],
            'adorasi' => [
                'adorasi sakramen', 'adorasi eukaristi', 'sakramen mahakudus',
                'monstrans', 'benediksi sakramen', 'jam adorasi',
                'adorasi abadi', 'berdiam di hadapan tuhan',
            ],
            'jalan_salib' => [
                'jalan salib', 'via crucis', 'via dolorosa',
                '14 perhentian', 'sengsara kristus',
                'yesus memanggul salib', 'prosesi jalan salib',
            ],

            // ── Kegiatan & Pelayanan ──────────────────────────────────────────
            'baksos' => [
                'bakti sosial', 'baksos paroki', 'santunan',
                'pembagian sembako', 'karitatif paroki',
            ],
            'donor_darah' => [
                'donor darah', 'pendonor darah', 'kantong darah', 'aksi donor',
            ],
            'kesehatan' => [
                'baksos kesehatan', 'pemeriksaan gratis',
                'posyandu paroki', 'poliklinik paroki',
                'pelayanan medis paroki', 'pengobatan gratis',
            ],
            'pelantikan' => [
                'pelantikan pengurus', 'pelantikan dpp', 'dilantik',
                'serah terima jabatan', 'pengurus baru paroki', 'dpp baru',
            ],
            'rapat' => [
                'rapat dpp', 'sidang paroki', 'musyawarah paroki',
                'rapat pleno', 'evaluasi program paroki',
                'rapat pastoral', 'rapat kerja tahunan',
            ],
            'pastoral' => [
                'kunjungan pastoral', 'visitasi pastoral',
                'visitasi romo', 'imam mengunjungi umat',
            ],
            'sejarah' => [
                'sejarah paroki', 'historia paroki', 'kronologi paroki',
                'asal usul paroki', 'yubileum paroki', 'arsip paroki lama',
            ],
            'bazar' => [
                'bazar paroki', 'bazar tahunan', 'stand bazar',
                'penggalangan dana bazar', 'fun games paroki',
            ],
            'retret' => [
                'retret paroki', 'retret umat', 'rekoleksi paroki',
                'rekoleksi umat', 'hari hening', 'silent retreat',
            ],
            'ziarah' => [
                'ziarah paroki', 'gua maria', 'sendangsono',
                'peziarah paroki', 'wisata rohani',
            ],
            'sekolah_minggu' => [
                'sekolah minggu paroki', 'bina iman anak',
                'bina iman remaja', 'katekese anak',
                'pendidikan iman anak',
            ],
            'katekese' => [
                'katekese umat', 'pendalaman kitab suci',
                'kursus kitab suci', 'pembinaan iman dewasa',
            ],
            'lingkungan' => [
                'ibadat lingkungan', 'pertemuan lingkungan',
                'ketua lingkungan paroki', 'doa lingkungan',
            ],
            'wilayah' => [
                'pertemuan wilayah', 'koordinator wilayah paroki',
                'musyawarah wilayah', 'rapat wilayah',
            ],
            'kronik' => [
                'kronik kegiatan', 'laporan kegiatan',
                'dokumentasi kegiatan', 'liputan kegiatan',
            ],
            'berita' => [
                'berita paroki', 'kabar paroki',
                'informasi terbaru paroki', 'warta paroki terbaru',
            ],
        ];

        foreach ($signals as $ctx => $kwList) {
            $score = 0;
            foreach ($kwList as $kw) {
                if (str_contains($text, $kw)) $score++;
            }
            if ($score > 0) $scores[$ctx] = $score;
        }

        if (empty($scores)) return 'generic';

        arsort($scores);
        return array_key_first($scores);
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  ALT TEXT TEMPLATES
    //  Panjang ideal: 9–14 kata. Deskriptif, spesifik, kaya variasi.
    // ══════════════════════════════════════════════════════════════════════════

    private static function altTemplates(string $ctx, bool $isGeneric): array
    {
        $pools = [

    'misa' => [
        'Umat {paroki} berdoa khusyuk bersama dalam misa {menu_label}',
        'Suasana ekaristi yang sakral dan hening di Gereja SMDTBA {kota}',
        'Imam memimpin perayaan misa kudus bersama umat {paroki}',
        'Umat {kota} mengikuti {menu_label} dengan khidmat di {paroki}',
        'Barisan umat tertunduk khusyuk dalam liturgi misa {paroki}',
        'Ratusan umat {paroki} memenuhi Gereja SMDTBA saat {menu_label}',
        'Konsekrasi roti dan anggur dalam misa {kategori} di {paroki}',
        'Imam dan umat merayakan ekaristi bersama di Gereja SMDTBA {kota}',
        'Momen sakral saat hosti diangkat dalam misa {paroki}',
        'Komunitas {paroki} berkumpul dalam satu liturgi yang khidmat',
        'Umat Gereja Katolik {kota} merayakan misa kudus bersama',
        'Komuni suci dibagikan kepada umat dalam misa {kategori} {paroki}',
        'Salam damai antar umat dalam perayaan ekaristi Gereja SMDTBA {kota}',
        'Prosesi masuk imam dalam misa {menu_label} yang khidmat di {paroki}',
        'Lektor membacakan Sabda Tuhan dalam misa {kategori} di {paroki}',
        'Imam mengangkat piala dalam konsekrasi misa {paroki} {kota}',
        'Umat {paroki} menerima berkat di akhir misa {menu_label}',
        'Koor mengiringi liturgi misa kudus {paroki} dengan indah',
    ],

    'rosario' => [
        'Umat {paroki} berdoa rosario bersama dalam keheningan penuh devosi',
        'Manik rosario di jemari umat {paroki} dalam doa bersama',
        'Doa rosario mengalun khusyuk di Gereja SMDTBA {kota}',
        'Umat {paroki} memanjatkan devosi rosario bersama Bunda Maria',
        'Permenungan misteri rosario bersama komunitas {paroki}',
        'Umat berlutut dalam doa rosario harian di {paroki}',
        'Persekutuan doa rosario komunitas Gereja Santa Maria {kota}',
        'Novena rosario umat {paroki} yang penuh kekhusyukan',
        'Umat {kota} berdoa rosario di bulan Oktober bersama',
        'Devosi kepada Bunda Maria lewat rosario di Gereja SMDTBA',
        'Umat menggenggam manik rosario dalam doa malam bersama {paroki}',
        'Komunitas {paroki} bersatu dalam litani dan doa rosario bersama',
    ],

    'adorasi' => [
        'Umat {paroki} beradorasi di hadapan Sakramen Mahakudus dengan khidmat',
        'Monstrans bersinar di altar saat adorasi eukaristi {paroki}',
        'Umat berlutut hening dalam adorasi Sakramen Mahakudus Gereja SMDTBA',
        'Keheningan adorasi memenuhi Gereja Katolik {kota} malam itu',
        'Cahaya lilin menerangi adorasi sakramen di Gereja SMDTBA {kota}',
        'Umat {paroki} mendekat kepada Kristus dalam adorasi yang sakral',
        'Benediksi sakramen mahakudus dalam ibadat adorasi {paroki}',
        'Monstrans emas di altar menjadi pusat perhatian adorasi {paroki}',
        'Adorasi malam umat {paroki} yang penuh kedamaian dan keintiman',
        'Komunitas beradorasi khusyuk di Gereja Santa Maria {kota}',
    ],

    'jalan_salib' => [
        'Umat {paroki} menghayati jalan salib Tuhan Yesus bersama-sama',
        'Prosesi via crucis umat Paroki Santa Maria {kota}',
        'Permenungan 14 perhentian jalan salib di Gereja SMDTBA',
        'Umat {paroki} berdoa di setiap perhentian jalan salib Kristus',
        'Prosesi jalan salib komunitas {paroki} menjelang Paskah',
        'Keharuan umat dalam permenungan sengsara Kristus di {paroki}',
        'Salib dipikul bergantian oleh umat dalam prosesi via crucis {paroki}',
        'Umat membawa lilin dalam prosesi jalan salib malam {paroki}',
        'Imam memimpin prosesi jalan salib umat Gereja Santa Maria {kota}',
        'Anak muda {paroki} turut memikul salib dalam jalan salib tahunan',
    ],

    // ── NATAL ──────────────────────────────────────────────────────────────
    'natal' => [
        'Suasana sukacita perayaan Natal di Gereja Katolik {kota}',
        'Umat {paroki} merayakan malam kudus Natal bersama',
        'Kebersamaan hangat umat dalam misa Natal di {paroki}',
        'Gereja SMDTBA berhias indah dalam perayaan Natal {tahun}',
        'Drama natal anak-anak {paroki} yang penuh makna dan sukacita',
        'Misa malam Natal yang khidmat di Paroki Santa Maria {kota}',
        'Cahaya lilin natal menerangi umat {paroki} yang khusyuk berdoa',
        'Kandang natal menghiasi sudut Gereja SMDTBA dalam perayaan Natal',
        'Paduan suara {paroki} membawakan lagu natal yang menyentuh hati',
        'Pohon natal dan dekorasi menghiasi Gereja Santa Maria {kota}',
        'Imam menyampaikan homili Natal kepada umat {paroki} yang antusias',
        'Perayaan kelahiran Kristus di Gereja Katolik {kota} berlangsung khidmat',
        'Senyum bahagia umat terpancar dalam pesta Natal di Gereja SMDTBA',
        'Kreche natal sederhana menjadi daya tarik utama saat Natal {paroki}',
        'Umat {kota} berbagi sukacita kelahiran Tuhan dalam misa Natal',
    ],

    // ── PESTA KELUARGA KUDUS ───────────────────────────────────────────────
    'keluarga_kudus' => [
        'Misa Pesta Keluarga Kudus Nazaret dirayakan umat {paroki} bersama',
        'Keluarga-keluarga {paroki} hadir dalam perayaan Pesta Keluarga Kudus',
        'Umat {kota} merenungkan teladan Yesus, Maria, dan Yusuf di {paroki}',
        'Pesta Keluarga Kudus di Gereja SMDTBA mengundang seluruh keluarga hadir',
        'Homili Pesta Keluarga Kudus menginspirasi keluarga umat {paroki}',
        'Orang tua dan anak duduk berdampingan dalam misa Keluarga Kudus {paroki}',
        'Komunitas {paroki} meneladani Nazaret dalam pesta yang khidmat',
        'Keluarga muda {paroki} mengikuti misa Keluarga Kudus dengan penuh harap',
    ],

    // ── MARIA BUNDA ALLAH — 1 Januari ─────────────────────────────────────
    'maria_bunda_allah' => [
        'Umat {paroki} merayakan tahun baru dalam iman lewat misa 1 Januari',
        'Misa Hari Raya Maria Bunda Allah di Gereja SMDTBA {kota} awal tahun',
        'Komunitas {paroki} memulai tahun baru di hadapan altar bersama Maria',
        'Doa awal tahun umat Gereja Katolik {kota} bersama Bunda Allah',
        'Imam memimpin misa perdamaian sedunia di {paroki} awal tahun',
        'Umat {paroki} mempercayakan tahun baru ke tangan Bunda Maria',
        'Hari Raya Maria Bunda Allah — pembuka tahun liturgi di {paroki}',
    ],

    // ── EPIFANI ────────────────────────────────────────────────────────────
    'epifani' => [
        'Umat {paroki} merayakan Hari Raya Penampakan Tuhan bersama-sama',
        'Misa Epifani di Gereja SMDTBA {kota} dihadiri umat dengan khidmat',
        'Kisah tiga raja dari timur direnungkan umat {paroki} dalam misa',
        'Hari Raya Epifani menutup rangkaian perayaan Natal di {paroki}',
        'Imam mewartakan cahaya Kristus bagi segala bangsa dalam Epifani {paroki}',
        'Komunitas {paroki} merayakan Epifani sebagai pesta iman universal',
        'Umat Gereja Katolik {kota} mengikuti misa Penampakan Tuhan bersama',
    ],

    // ── PEMBAPTISAN TUHAN ──────────────────────────────────────────────────
    'pembaptisan_tuhan' => [
        'Umat {paroki} merayakan Hari Raya Pembaptisan Tuhan dengan khidmat',
        'Misa Pembaptisan Tuhan di Gereja SMDTBA {kota} sebagai penutup Natal',
        'Imam mewartakan misteri baptisan Kristus di sungai Yordan kepada umat',
        'Komunitas {paroki} merenungkan makna baptisan dalam misa hari raya',
        'Umat {kota} memperbarui makna baptis mereka dalam perayaan ini',
        'Hari Raya Pembaptisan Tuhan — awal Masa Biasa di {paroki}',
    ],

    // ── PRAPASKAH ──────────────────────────────────────────────────────────
    'prapaskah' => [
        'Umat {paroki} menerima abu pertobatan di awal masa Prapaskah',
        'Permenungan pertobatan umat {paroki} di hari Rabu Abu',
        'Jalan salib prapaskah yang khidmat di Gereja SMDTBA {kota}',
        'Aksi Puasa Pembangunan umat {paroki} sebagai wujud kasih nyata',
        'Tanda abu di dahi umat {paroki} sebagai simbol pertobatan',
        'Imam memberi abu kepada umat dalam misa Rabu Abu {paroki}',
        'Komunitas {paroki} menjalani puasa prapaskah dengan sepenuh hati',
        'Kolekte APP mengalir dari tangan umat peduli di {paroki}',
        'Umat {paroki} merenungkan sengsara Kristus di masa prapaskah',
        'Suasana pertobatan yang dalam di Gereja SMDTBA awal prapaskah',
        'Aksi karitatif prapaskah umat {paroki} untuk sesama yang membutuhkan',
    ],

    // ── MINGGU PALMA ──────────────────────────────────────────────────────
    'minggu_palma' => [
        'Prosesi palma umat {paroki} menyambut Kristus Raja dengan daun palma',
        'Umat membawa dan melambaikan daun palma dalam prosesi Minggu Palma',
        'Pemberkatan palma di luar Gereja SMDTBA sebelum perarakan masuk',
        'Perarakan Minggu Palma komunitas {paroki} menyusuri jalan dengan khidmat',
        'Umat {kota} memasuki Gereja SMDTBA dalam prosesi palma yang meriah',
        'Imam memberkati daun palma umat dalam pembukaan Pekan Suci {paroki}',
        'Anak-anak {paroki} turut serta dalam prosesi palma yang menyenangkan',
        'Hosana bergema dari mulut umat dalam perarakan palma di {paroki}',
        'Pekan Suci dibuka dengan prosesi palma yang agung di {paroki}',
        'Umat {paroki} berdiri tegak menyimak bacaan Sengsara panjang setelah palma',
        'Daun palma melambai di tangan ratusan umat {kota} dalam prosesi',
        'Minggu Palma di {paroki}: sukacita hari ini, salib menanti di depan',
    ],

    // ── KAMIS PUTIH ────────────────────────────────────────────────────────
    'kamis_putih' => [
        'Imam berlutut membasuh kaki umat dalam misa Kamis Putih {paroki}',
        'Momen pembasuhan kaki yang mengharukan dalam liturgi Kamis Putih',
        'Perjamuan malam terakhir dikenang kembali dalam misa Kamis Putih {paroki}',
        'Umat {paroki} mengikuti misa In Caena Domini dengan penuh kekhusyukan',
        'Altar Gereja SMDTBA dilucuti setelah misa Kamis Putih yang sakral',
        'Komunitas {paroki} berjaga dalam tuguran Kamis Putih malam itu',
        'Sakramen Mahakudus dipindahkan dalam prosesi tuguran di {paroki}',
        'Umat duduk berjaga bersama di depan tabernakulum malam Kamis Putih',
        'Imam {paroki} mencuci kaki dua belas wakil umat sebagai simbol pelayanan',
        'Lembaga ekaristi dan imamat dikenang komunitas {paroki} di Kamis Putih',
        'Gereja SMDTBA hening setelah liturgi — tabernakulum sudah dikosongkan',
        'Komunitas {paroki} merenungkan malam perjamuan terakhir bersama Kristus',
    ],

    // ── JUMAT AGUNG ────────────────────────────────────────────────────────
    'jumat_agung' => [
        'Umat {paroki} mengikuti ibadat Jumat Agung dengan keharuan yang dalam',
        'Penghormatan salib dalam ibadat Jumat Agung di Gereja SMDTBA {kota}',
        'Salib diciumi satu per satu oleh umat {paroki} dalam hening yang sakral',
        'Imam berbaring di lantai altar dalam prosesi ibadat Jumat Agung {paroki}',
        'Umat {kota} berbaris panjang menghormati salib di Gereja SMDTBA',
        'Keheningan Jumat Agung menyelimuti Gereja SMDTBA — hari tanpa misa',
        'Bacaan Sengsara menurut Yohanes dibawakan khidmat di {paroki}',
        'Via crucis agung komunitas {paroki} mengenang perjalanan Kristus ke Golgota',
        'Umat menunduk dalam permenungan wafat Kristus di Jumat Agung {paroki}',
        'Gereja SMDTBA hening tanpa lonceng dalam peringatan Jumat Agung',
        'Kolekte Tanah Suci dikumpulkan dalam ibadat Jumat Agung di {paroki}',
        'Tirai altar terurai, lilin padam — suasana berkabung Jumat Agung {paroki}',
        'Umat {paroki} mencium salib sebagai ungkapan cinta kepada Kristus',
    ],

    // ── SABTU SUCI & VIGILI PASKAH ────────────────────────────────────────
    'sabtu_suci' => [
        'Api Paskah dinyalakan di halaman Gereja SMDTBA memulai vigili malam',
        'Lilin Paskah umat {paroki} menyala dari api yang sama dalam vigili',
        'Exsultet bergema untuk pertama kali di Gereja SMDTBA — malam paling agung',
        'Alleluia yang ditahan 40 hari akhirnya bergema di {paroki} malam ini',
        'Umat berlutut dalam pembaruan janji baptis di vigili Paskah {paroki}',
        'Baptis baru diterima dalam komunitas {paroki} di vigili Paskah',
        'Cahaya lilin berpendar dalam kegelapan malam vigili Paskah {paroki}',
        'Imam mengumandangkan Kristus Cahaya Dunia dalam vigili {paroki}',
        'Umat {paroki} mendengar tujuh bacaan vigili dalam keheningan yang dalam',
        'Gereja SMDTBA dari hening sabtu siang menuju ledakan alleluia malam vigili',
        'Api kecil memulai malam terbesar iman Kristiani di {paroki}',
        'Kegelapan dikalahkan cahaya satu demi satu dalam vigili Paskah {paroki}',
        'Komunitas {paroki} bersama memasuki malam terang kebangkitan Kristus',
    ],

    // ── PASKAH ────────────────────────────────────────────────────────────
    'paskah' => [
        'Kegembiraan Paskah menyambut kebangkitan Kristus di {paroki}',
        'Umat {paroki} merayakan kemenangan Kristus dalam liturgi Paskah',
        'Alleluia bergema dalam perayaan Paskah bersama umat {paroki}',
        'Umat {kota} bersorak sukacita dalam misa Paskah yang meriah',
        'Kristus bangkit dirayakan dengan penuh keyakinan oleh {paroki}',
        'Misa Paskah yang penuh sukacita di Paroki Santa Maria {kota}',
        'Perayaan kebangkitan yang khidmat di Gereja Katolik {kota}',
        'Umat {paroki} berdiri tegak dalam alleluia Paskah yang meriah',
        'Prosesi agung Paskah umat Paroki Santa Maria {kota} {tahun}',
        'Ekspresi sukacita kebangkitan terpancar dari wajah umat {paroki}',
        'Seluruh umat {kota} hadir menyaksikan kebangkitan Kristus di {paroki}',
    ],

    // ── ADVEN ─────────────────────────────────────────────────────────────
    'adven' => [
        'Lilin Adven menyala di {paroki} menandai penantian akan Kristus',
        'Suasana penuh harap masa Adven di Paroki Santa Maria {kota}',
        'Lingkaran Adven yang bermakna di Gereja SMDTBA {kota}',
        'Umat {paroki} mempersiapkan hati menyambut Natal dalam masa Adven',
        'Empat lilin Adven memancarkan harapan di Gereja SMDTBA {kota}',
        'Doa dan permenungan Adven umat Gereja Katolik {kota}',
        'Lilin pertama Adven dinyalakan dalam misa pembukaan di {paroki}',
        'Umat {paroki} merayakan minggu Gaudete penuh sukacita penantian',
        'Lingkaran Adven dan doa bersama umat Gereja SMDTBA {tahun}',
    ],

    // ── CORPUS CHRISTI ────────────────────────────────────────────────────
    'corpus_christi' => [
        'Prosesi Sakramen Mahakudus dalam Hari Raya Corpus Christi di {paroki}',
        'Monstrans dibawa dalam prosesi agung Corpus Christi di {kota}',
        'Umat {paroki} mengiringi Sakramen Mahakudus dalam prosesi Corpus Christi',
        'Adorasi agung dalam Hari Raya Tubuh dan Darah Kristus di {paroki}',
        'Komunitas {kota} berjalan dalam prosesi Corpus Christi yang khidmat',
        'Imam membawa monstrans dalam perarakan Corpus Christi {paroki}',
        'Umat berlutut mengiringi Sakramen Mahakudus dalam prosesi suci',
        'Perayaan Corpus Christi di Gereja SMDTBA sebagai pesta iman ekaristi',
    ],

    // ── HATI YESUS ────────────────────────────────────────────────────────
    'hati_yesus' => [
        'Umat {paroki} merayakan Hari Raya Hati Yesus Mahakudus bersama',
        'Devosi misa Pertama Jumat di Gereja SMDTBA {kota} penuh pengikut setia',
        'Komunitas {paroki} berdoa novena Hati Kudus dengan penuh devosi',
        'Umat {kota} mengikuti misa devosi Hati Yesus yang khidmat di {paroki}',
        'Litani Hati Kudus didoakan bersama komunitas Gereja SMDTBA {kota}',
        'Imam memimpin devosi bulanan Hati Kudus bersama umat {paroki}',
    ],

    // ── MARIA DIANGKAT KE SURGA ───────────────────────────────────────────
    'maria_diangkat' => [
        'Umat {paroki} merayakan Hari Raya Maria Diangkat ke Surga bersama',
        'Misa 15 Agustus di Gereja SMDTBA {kota} dihadiri ratusan umat',
        'Devosi kepada Bunda Maria dalam perayaan Assumption di {paroki}',
        'Komunitas {paroki} menghormati Bunda Maria dalam pesta terbesarnya',
        'Imam memimpin misa Hari Raya Maria Diangkat ke Surga di {paroki}',
        'Bunga-bunga menghiasi altar Gereja SMDTBA dalam perayaan 15 Agustus',
        'Umat {kota} bersatu memuliakan Bunda Maria dalam pesta agung {paroki}',
    ],

    // ── PESTA PELINDUNG ───────────────────────────────────────────────────
    'pesta_pelindung' => [
        'Umat {paroki} merayakan Pesta Pelindung Santa Maria Dengan Tidak Bernoda Asal',
        'Perayaan 8 Desember di Gereja SMDTBA — hari paling istimewa bagi paroki ini',
        'Komunitas {paroki} bersukacita dalam Pesta Nama Paroki yang selalu dinantikan',
        'Misa Pesta Pelindung di Gereja SMDTBA {kota} dipadati umat dengan bangga',
        'Hari Raya Santa Maria Tidak Bernoda menutup tahun paroki dengan indah',
        'Seluruh komunitas {kota} berkumpul merayakan patrona tercinta di {paroki}',
        'Imam memimpin misa agung Pesta Pelindung Paroki SMDTBA Tulungagung',
        'Festival Pesta Pelindung {paroki} — puncak kebanggaan umat setiap tahunnya',
        'Umat {paroki} merayakan dies natalis paroki dengan sukacita penuh',
    ],

    // ── KRISTUS RAJA ──────────────────────────────────────────────────────
    'kristus_raja' => [
        'Umat {paroki} merayakan Hari Raya Kristus Raja Semesta Alam bersama',
        'Misa Kristus Raja menutup tahun liturgi di Gereja SMDTBA {kota}',
        'Komunitas {paroki} menyerahkan diri kepada Kristus Raja dalam misa penutup',
        'Imam mewartakan kerajaan Kristus kepada umat {paroki} dalam misa agung',
        'Akhir tahun liturgi dirayakan penuh syukur oleh umat {kota} di {paroki}',
        'Homili Kristus Raja meneguhkan iman komunitas {paroki} menutup tahun',
    ],

    // ── SAKRAMEN ──────────────────────────────────────────────────────────
    'baptis' => [
        'Momen pembaptisan yang mengharukan di Paroki SMDTBA {kota}',
        'Umat baru diterima lewat sakramen baptis di {paroki}',
        'Air baptis mengalir sebagai tanda kelahiran baru iman umat {paroki}',
        'Prosesi baptis yang penuh keharuan di Gereja Katolik {kota}',
        'Imam menuangkan air baptis kepada calon baptis di Gereja SMDTBA',
        'Katekumen resmi menjadi anggota Gereja Katolik {kota} lewat baptis',
        'Wali baptis mendampingi calon baptis dalam sakramen di {paroki}',
        'Lilin baptis dinyalakan dari lilin Paskah dalam misa baptis {paroki}',
        'Komunitas {paroki} menyambut saudara-saudara baru dalam iman',
    ],

    'komuni' => [
        'Momen sakral penerimaan Komuni Pertama umat {paroki}',
        'Anak-anak menyambut Tubuh Kristus untuk pertama kali di {paroki}',
        'Anak-anak berpakaian putih menuju meja Tuhan di {paroki}',
        'Orang tua haru menyaksikan putra-putrinya komuni pertama di {paroki}',
        'Imam menyerahkan hosti kepada anak-anak komuni pertama {paroki}',
        'Tangan kecil anak-anak menyambut Tubuh Kristus di altar {paroki}',
        'Misa Komuni Pertama yang penuh sukacita di Paroki Santa Maria {kota}',
    ],

    'krisma' => [
        'Pemuda {paroki} menerima sakramen Krisma dengan penuh sukacita',
        'Uskup mengkrisma umat muda dalam misa penguatan di {paroki} {kota}',
        'Tangan Uskup mengurapi dahi penerima krisma di {paroki}',
        'Umat muda {paroki} menerima meterai Roh Kudus dalam krisma',
        'Sponsor mendampingi penerima krisma dalam misa penguatan {paroki}',
        'Minyak krisma mengurapi dahi generasi penerus {paroki} {kota}',
    ],

    'tahbisan' => [
        'Imam baru berbaring di lantai dalam prosesi tahbisan yang mengharukan',
        'Komunitas {paroki} menyaksikan penahbisan imam dengan sukacita',
        'Tangan Uskup ditumpangkan pada calon imam dalam misa tahbisan',
        'Imam baru menerima stola tanda tugas pelayanannya di {paroki}',
        'Uskup meletakkan tangan pada kepala calon imam dalam tahbisan',
        'Komunitas {paroki} bangga menyaksikan putra terbaiknya ditahbiskan',
    ],

    'pernikahan' => [
        'Pasangan baru menerima berkat pernikahan di Gereja Katolik {kota}',
        'Janji pernikahan kudus diucapkan di hadapan altar {paroki}',
        'Dua hati bersatu dalam sakramen perkawinan di {paroki} {kota}',
        'Senyum pengantin baru yang diberkati di Paroki Santa Maria {kota}',
        'Imam memberkati cincin dan mempertemukan tangan pengantin {paroki}',
        'Lorong gereja SMDTBA dipenuhi bunga dalam prosesi pernikahan',
    ],

    'tobat' => [
        'Umat mengantri bilik tobat dalam persiapan sakramen pengampunan',
        'Rekonsiliasi massal umat {paroki} menjelang {menu_label}',
        'Umat {paroki} memohon pengampunan dalam sakramen tobat yang hening',
        'Wajah damai terpancar dari umat {paroki} usai menerima absolusi',
        'Antrian panjang umat {kota} di depan bilik tobat Gereja SMDTBA',
        'Komunitas {paroki} memilih berdamai dengan Tuhan dalam sakramen tobat',
    ],

    // ── KELOMPOK ──────────────────────────────────────────────────────────
    'mudika' => [
        'Orang Muda Katolik {paroki} bersemangat dalam kegiatan {kategori}',
        'Generasi muda paroki berkumpul penuh semangat dalam {kategori}',
        'OMK {paroki} {kota} antusias dalam kegiatan rohani bersama',
        'Kaum muda Gereja SMDTBA berbagi iman dan kebersamaan',
        'OMK {paroki} membuktikan bahwa iman muda itu aktif dan berkarya',
        'Mudika {paroki} bersatu dalam misi melayani dan bertumbuh dalam iman',
        'Generasi penerus Gereja Katolik {kota} tampil penuh kreativitas',
    ],

    'wkri' => [
        'Anggota WKRI {paroki} berkumpul dalam kebersamaan yang hangat',
        'Wanita Katolik {paroki} aktif melayani dan berbagi dalam komunitas',
        'Pertemuan rutin WKRI {paroki} yang selalu hangat dan produktif',
        'WKRI {paroki}: kekuatan tersembunyi di balik setiap pelayanan komunitas',
        'Para ibu {kota} bersama dalam persaudaraan WKRI yang teguh beriman',
    ],

    'legio' => [
        'Anggota Legio Maria {paroki} setia dalam devosi dan pelayanan',
        'Presidium Legio Maria {paroki} berkumpul dalam kesetiaan dan doa',
        'Devosi kepada Bunda Maria menjadi kekuatan pelayanan Legio {paroki}',
        'Legio Maria {paroki} menjalankan tugas kunjungan pastoral dengan setia',
        'Pertemuan mingguan Legio Maria umat {paroki} yang tak pernah absen',
    ],

    'karismatik' => [
        'Umat {paroki} memuji Tuhan dengan sukacita dalam pertemuan karismatik',
        'Tangan terangkat dalam pujian karismatik penuh semangat di {paroki}',
        'Roh Kudus terasa nyata dalam pertemuan PKK {paroki} yang hidup',
        'PKK {paroki} berdoa dan memuji Tuhan dalam semangat yang membara',
        'Seminar Hidup Baru dalam Roh Kudus di {paroki} yang penuh semangat',
    ],

    'cursillo' => [
        'Peserta Cursillo {paroki} berbagi pengalaman iman dalam ultreya bersama',
        'Cursillista {paroki} bersatu dalam salam De Colores yang menghangatkan',
        'Ultreya Cursillo {paroki} membarakan kembali api iman yang pernah menyala',
        'Komunitas Cursillo {kota} setia berkumpul dalam ultreya bersama',
    ],

    'lansia' => [
        'Para lansia {paroki} berkumpul dalam keakraban yang menghangatkan hati',
        'Kelompok opa-oma {paroki} menunjukkan iman yang tak pernah menua',
        'Senyum bijaksana para lansia {paroki} memancarkan kedalaman iman',
        'Kearifan dan kesetiaan iman para lansia {paroki} menjadi teladan',
        'Komunitas lansia Gereja Katolik {kota} aktif dan penuh semangat',
    ],

    'koor' => [
        'Paduan suara {paroki} memperindah perayaan liturgi dengan nyanyian merdu',
        'Dirigen memimpin koor {paroki} dalam melayani liturgi sepenuh hati',
        'Nyanyian koor {paroki} mengalun indah mengiringi setiap bagian liturgi',
        'Paduan suara Gereja SMDTBA berlatih serius sebelum misa dimulai',
        'Anggota koor {paroki} melayani Tuhan dengan karunia suara mereka',
    ],

    'lektor' => [
        'Lektor {paroki} mewartakan Sabda Tuhan di hadapan umat dengan jelas',
        'Pemazmur {paroki} membawakan mazmur tanggapan dengan penuh penghayatan',
        'Prodiakon {paroki} membantu pembagian komuni dalam perayaan ekaristi',
        'Misdinar {paroki} melayani di altar dengan khidmat dan penuh tanggung jawab',
        'Para putera altar Gereja SMDTBA melayani dengan seragam yang rapi',
    ],

    // ── KEGIATAN ──────────────────────────────────────────────────────────
    'retret' => [
        'Peserta retret {paroki} menikmati keheningan dan kedekatan dengan Tuhan',
        'Umat {paroki} merenung dan memperbarui diri dalam retret rohani',
        'Suasana hening dan kontemplatif dalam rekoleksi {paroki} {kota}',
        'Umat {paroki} berdiam sejenak bersama Tuhan dalam retret tahunan',
        'Sesi sharing retret {paroki} berlangsung terbuka dan penuh ketulusan',
    ],

    'ziarah' => [
        'Umat {paroki} berziarah bersama mendekatkan diri kepada Tuhan',
        'Peziarah {paroki} berdoa di hadapan gua Maria dengan penuh harap',
        'Umat {paroki} berziarah ke gua Maria dengan devosi yang dalam',
        'Bus ziarah {paroki} membawa umat {kota} menuju tempat suci',
        'Doa rosario mengiringi perjalanan ziarah komunitas {paroki}',
    ],

    'bazar' => [
        'Stand bazar yang ramai dalam kegiatan tahunan {paroki}',
        'Kemeriahan bazar {paroki} yang melibatkan seluruh umat',
        'Kuliner dan kerajinan umat memenuhi halaman Gereja SMDTBA di bazar',
        'Anak-anak berlarian ceria dalam pesta bazar tahunan {paroki}',
        'Penggalangan dana bazar {paroki} untuk program gereja dan sosial',
    ],

    'baksos' => [
        'Umat {paroki} berbagi kasih lewat bakti sosial untuk sesama',
        'Santunan diberikan kepada yang membutuhkan oleh komunitas {paroki}',
        'Komunitas {paroki} hadir di tengah masyarakat lewat aksi karitatif',
        'Pelayanan sosial {paroki} menjangkau yang lemah di sekitar {kota}',
    ],

    'donor_darah' => [
        'Umat {paroki} bergantian mendonorkan darah secara sukarela',
        'Aksi donor darah {paroki} sebagai wujud kasih nyata kepada sesama',
        'Komunitas {kota} berpartisipasi dalam donor darah sukarela {paroki}',
    ],

    'kesehatan' => [
        'Tim medis {paroki} melayani umat dalam baksos kesehatan gratis',
        'Antrian pemeriksaan gratis dalam baksos kesehatan {paroki} {kota}',
        'Pelayanan kesehatan murah meriah dari komunitas {paroki} untuk sesama',
    ],

    'pelantikan' => [
        'Pelantikan pengurus DPP {paroki} periode baru berlangsung khidmat',
        'Serah terima jabatan pengurus {paroki} berjalan tertib dan berkesan',
        'Komunitas {paroki} menyaksikan pelantikan pemimpin baru mereka',
    ],

    'rapat' => [
        'Rapat DPP {paroki} membahas program pastoral tahun ini',
        'Sidang paroki {kota} berlangsung tertib dan produktif',
        'Musyawarah {paroki} merumuskan langkah pelayanan bersama',
    ],

    'pastoral' => [
        'Romo {paroki} mengunjungi umat di lingkungan secara langsung',
        'Visitasi pastoral imam {paroki} mempererat gembala dan kawanannya',
        'Kehadiran romo di tengah umat {kota} menguatkan ikatan komunitas',
    ],

    'sejarah' => [
        'Arsip bersejarah yang mendokumentasikan perjalanan panjang {paroki}',
        'Foto lama {paroki} menjadi warisan rohani yang tak ternilai',
        'Momen bersejarah perjalanan Paroki Santa Maria {kota} dari masa ke masa',
    ],

    'lingkungan' => [
        'Ibadat lingkungan di {paroki}: tidak butuh gedung besar, cukup hati yang hadir',
        'Pertemuan lingkungan {paroki} mempererat persaudaraan umat setempat',
        'Komunitas kecil {paroki} berkumpul dalam ibadat lingkungan yang hangat',
    ],

    'wilayah' => [
        'Ratusan umat dari berbagai lingkungan {paroki} berhimpun dalam pertemuan wilayah',
        'Koordinasi wilayah {paroki} mempererat persaudaraan antar lingkungan',
        'Musyawarah wilayah {paroki} berlangsung hidup dan produktif',
    ],

    'sekolah_minggu' => [
        'Anak-anak {paroki} gembira dalam kegiatan Sekolah Minggu',
        'Pendamping BIA mendampingi anak {paroki} dengan penuh kesabaran',
        'Generasi masa depan {paroki} tumbuh dalam iman lewat Sekolah Minggu',
        'BIA {paroki} membentuk karakter anak Kristiani sejak dini',
    ],

    'katekese' => [
        'Katekese yang memperdalam iman umat {paroki} bersama pembimbing',
        'Umat {paroki} antusias belajar dan mendalami iman bersama katekis',
        'Sharing iman yang terbuka dalam sesi katekese umat {paroki}',
        'Umat duduk melingkar dalam pendalaman iman yang antusias',
    ],

    'kronik' => [
        'Dokumentasi perjalanan {menu_label} umat {paroki} yang tak terlupakan',
        'Rekam jejak kegiatan {kategori} di {paroki} {kota}',
        'Arsip foto kegiatan {kategori} {paroki} yang berharga bagi komunitas',
    ],

    'berita' => [
        'Informasi terkini kegiatan {paroki} {kota} untuk umat',
        'Warta terbaru dari {paroki} untuk komunitas umat {kota}',
        'Liputan kegiatan terkini {paroki} sebagai warta seluruh umat',
    ],

    'pekan_suci' => [
        'Umat {paroki} menghayati sengsara Kristus dalam Pekan Suci yang khidmat',
        'Trihari suci {paroki}: tiga hari paling sakral dalam kalender liturgi Kristiani',
        'Komunitas {paroki} menjalani Triduum Paskah bersama dalam iman',
    ],

    'generic' => [
        'Umat {paroki} bersatu dalam semangat kebersamaan dan iman',
        'Momen kebersamaan umat dalam kegiatan {kategori} di {paroki}',
        'Komunitas umat {paroki} {kota} dalam momen yang bermakna',
        'Kegiatan {kategori} umat Gereja Katolik {kota} penuh semangat',
        'Wajah kebersamaan umat {paroki} yang mencerminkan kasih dan iman',
        'Foto kegiatan {judul_singkat} komunitas Gereja Santa Maria {kota}',
        'Iman dan kasih persaudaraan {paroki} tampak nyata dalam kegiatan ini',
    ],

        ];

        return $pools[$ctx] ?? $pools['generic'];
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  CAPTION TEMPLATES
    //  Pendek, manusiawi, tidak kaku. 1–2 kalimat max.
    //  Jangan panjang-panjang — kesan > deskripsi.
    // ══════════════════════════════════════════════════════════════════════════

    private static function captionTemplates(string $ctx, bool $isGeneric): array
    {
        $pools = [

    'misa' => [
        'Misa {kategori} di {paroki} — bukan rutinitas, melainkan perjumpaan yang selalu dinantikan.',
        'Di sinilah, minggu demi minggu, umat {kota} menemukan rumah — di meja Tuhan.',
        'Tidak ada kursi kosong, namun tidak ada yang berdesak-desakan. Begitulah misa di {paroki}.',
        'Saat konsekrasi tiba, Gereja SMDTBA sunyi. Ribuan hati mendekat kepada Kristus.',
        'Ekaristi {menu_label} {paroki} — sumber dan puncak iman yang dirayakan bersama.',
        'Cahaya matahari menerobos jendela, menerangi barisan umat yang khusyuk di {paroki}.',
        '"Komunitas" bukan sekadar kata di {paroki}. Ia terdefinisi setiap kali ratusan orang duduk bersama dalam liturgi.',
        'Pukul tujuh pagi, dan Gereja SMDTBA sudah penuh. Umat {kota} tidak perlu diingatkan dua kali.',
        'Satu jam misa. Satu Tuhan. Sederhana tapi cukup — itulah ekaristi {paroki}.',
        'Di luar dunia bergerak cepat. Di dalam SMDTBA, waktu seolah berhenti saat misa dimulai.',
    ],

    'rosario' => [
        'Lima puluh salam Maria, satu kerinduan. Di {paroki}, rosario bukan hafalan — ia adalah doa hati.',
        'Oktober di {paroki} identik dengan satu suara: doa rosario yang mengalun setiap malam.',
        'Manik-manik itu sudah usang dipakai. Artinya devosi rosario di {paroki} sudah sangat lama hidup.',
        'Keheningan yang aneh tapi indah — itu yang selalu hadir saat rosario bersama di {paroki} dimulai.',
        'Selesai rosario, mereka tidak langsung pulang. Ada sesuatu yang tertinggal di {paroki}.',
        'Seorang nenek memimpin, dan puluhan jemaat mengikuti. Rosario {paroki} malam ini milik semua usia.',
    ],

    'adorasi' => [
        'Di hadapan monstrans yang bercahaya, umat {paroki} memilih diam — dan dalam diam itu, Tuhan berbicara.',
        'Adorasi di Gereja SMDTBA: tidak ada agenda padat. Hanya hadirat Tuhan dan hati yang siap mendengar.',
        'Tidak semua doa butuh kata-kata. Di hadapan Sakramen Mahakudus, kehadiran itu sudah cukup.',
        'Ia masuk dengan resah. Satu jam beradorasi di {paroki}, ia keluar dengan damai yang sulit dijelaskan.',
        'Di hadapan Sakramen Mahakudus {paroki}, semua jabatan luruh. Yang tersisa hanya jiwa yang haus.',
    ],

    'jalan_salib' => [
        'Empat belas perhentian, empat belas undangan untuk berhenti dan merasakan kasih Tuhan.',
        'Via crucis {paroki}: perjalanan fisik yang pendek, tapi rohani yang membawa umat jauh ke dalam misteri.',
        'Salib dipikul bergantian. Fisiknya ringan, maknanya jauh lebih berat dari itu — di {paroki}.',
        'Di perhentian terakhir, komunitas {paroki} berdiri dalam keheningan panjang. Tidak ada yang buru-buru pulang.',
        'Via dolorosa itu hanya dua kilometer di Yerusalem. Tapi di {paroki}, perjalanannya melintas jauh ke dalam hati.',
    ],

    // ── NATAL ──────────────────────────────────────────────────────────────
    'natal' => [
        'Malam kudus itu kembali hadir di {paroki} — dan seperti biasa, tidak ada yang melewatkannya.',
        'Natal di {paroki}: bukan tentang dekorasi paling indah, melainkan hati yang paling terbuka.',
        'Satu bintang, satu kandang, satu bayi — dan Gereja SMDTBA kembali penuh sesak menyambut-Nya.',
        'Desember di {kota}: panas di luar, hangat di dalam. SMDTBA punya caranya sendiri.',
        'Anak kecil itu tertidur di pelukan ayahnya sepanjang misa Natal. Tapi senyumnya tetap ada.',
        'Sudah bertahun-tahun {paroki} merayakan Natal bersama. Kehangatan itu tidak pernah berkurang.',
        '"Damai di bumi, damai di hati" — kalimat yang selalu terdengar di homili Natal, dan selalu terasa baru.',
        'Natal di {paroki} bukan puncak musim belanja. Ia adalah puncak penantian.',
    ],

    // ── PESTA KELUARGA KUDUS ───────────────────────────────────────────────
    'keluarga_kudus' => [
        'Nazaret bukan sekadar tempat di peta — ia adalah cara hidup. Dan {paroki} merayakannya hari ini.',
        'Pesta Keluarga Kudus di {paroki}: rumah tangga katolik diundang merenungkan teladan yang paling nyata.',
        'Yesus, Maria, Yusuf — keluarga paling sederhana yang mengubah dunia. Dirayakan di {paroki} {tahun}.',
        'Gereja SMDTBA penuh keluarga hari ini. Pesta Keluarga Kudus adalah undangan untuk semua.',
    ],

    // ── MARIA BUNDA ALLAH ─────────────────────────────────────────────────
    'maria_bunda_allah' => [
        '1 Januari di {paroki}: bukan sekadar tahun baru, tapi doa awal tahun bersama Bunda Allah.',
        'Tahun dimulai di altar Gereja SMDTBA — komunitas {paroki} memilih memulainya bersama Maria.',
        'Maria, Bunda Allah, menjaga langkah tahun baru umat {paroki} dalam doanya.',
        'Misa perdamaian sedunia di {paroki}: doa agar tahun ini membawa lebih banyak damai.',
    ],

    // ── EPIFANI ────────────────────────────────────────────────────────────
    'epifani' => [
        'Tiga raja dari timur itu melakukan perjalanan jauh. Umat {paroki} pun mengikuti bintangnya.',
        'Epifani di {paroki}: Kristus bukan hanya milik satu bangsa — Ia adalah cahaya bagi semua.',
        'Emas, kemenyan, mur — hadiah tiga raja. Iman, doa, dan pelayanan — hadiah umat {paroki}.',
        'Penutup Natal yang indah di {paroki}: Gereja SMDTBA merayakan Penampakan Tuhan bersama.',
    ],

    // ── PEMBAPTISAN TUHAN ──────────────────────────────────────────────────
    'pembaptisan_tuhan' => [
        'Di sungai Yordan, Yesus dibaptis. Di {paroki}, umat merenungkan makna baptisannya sendiri.',
        'Inilah Putera-Ku yang terkasih — suara yang bergema di sungai Yordan, dan juga di hati kita.',
        'Pembaptisan Tuhan menutup Masa Natal di {paroki}. Kini Masa Biasa dimulai — dengan iman yang diperbarui.',
    ],

    // ── PRAPASKAH ──────────────────────────────────────────────────────────
    'prapaskah' => [
        'Abu di dahi umat {paroki} bukan dekorasi. Ia adalah pengakuan: kita debu, dan kepada Tuhan kita kembali.',
        'Rabu Abu di Gereja SMDTBA — awal perjalanan 40 hari yang tidak mudah, tapi sangat perlu.',
        'Puasa dan pantang bukan hukuman. Bagi umat {kota}, itu adalah cara memberi ruang bagi Tuhan.',
        'Kolekte APP mengalir dari tangan umat {paroki}. Pertobatan yang sesungguhnya berbuah kasih.',
        'Abu ditorehkan di dahi, bukan di kepala orang lain. Prapaskah di {paroki} dimulai dari diri sendiri.',
        'Misa Rabu Abu {paroki} dipadati umat — bahkan mereka yang jarang datang. Ada yang menarik dari pertobatan.',
    ],

    // ── MINGGU PALMA ──────────────────────────────────────────────────────
    'minggu_palma' => [
        'Daun palma di tangan, tapi jalan salib sudah menunggu. Begitulah Minggu Palma selalu hadir.',
        'Hosana bergema di {paroki} — raja yang disambut rakyat, yang segera akan ditolak.',
        'Prosesi palma {paroki}: pawai kecil yang menyimpan ironi besar dalam iman Kristiani.',
        'Umat {paroki} melangkah masuk gereja dengan palma — pembukaan pekan paling sakral sepanjang tahun.',
        'Hari ini ramai, Jumat gelap. Di antara keduanya, iman umat {paroki} diuji dan dikuatkan.',
    ],

    // ── KAMIS PUTIH ────────────────────────────────────────────────────────
    'kamis_putih' => [
        'Malam perjamuan terakhir itu dikenang kembali di {paroki}. Lakukan ini untuk mengenang Aku.',
        'Romo berlutut mencuci kaki umat di {paroki} — inilah pelayanan yang sesungguhnya.',
        'Gereja SMDTBA hening setelah misa — altar dilucuti, tabernakulum kosong. Tuguran dimulai.',
        'Tangan yang mencuci kaki malam ini adalah tangan yang sama yang akan terpaku di salib besok.',
        'Komunitas {paroki} berjaga malam ini. Getsemani terasa dekat. Mereka memilih untuk tidak tidur.',
    ],

    // ── JUMAT AGUNG ────────────────────────────────────────────────────────
    'jumat_agung' => [
        'Gereja hening, salib diciumi — Jumat Agung di {paroki} berbicara tanpa banyak kata.',
        'Tak ada lonceng hari ini. Tak ada misa. Yang ada hanya doa, salib, dan kasih yang tak terbatas.',
        'Imam berbaring di lantai altar {paroki}. Itu bukan kelemahan — itu penyerahan total.',
        'Satu per satu umat mencium salib. Di {paroki}, gerakan kecil itu adalah ungkapan iman yang paling jujur.',
        'Hari ini {paroki} berkabung — bukan putus asa. Karena besok malam, api Paskah akan menyala.',
        'Via crucis agung {paroki}: perjalanan yang pendek, luka yang nyata, kasih yang tak terpahami.',
    ],

    // ── SABTU SUCI & VIGILI PASKAH ────────────────────────────────────────
    'sabtu_suci' => [
        'Api kecil itu memulai malam terbesar iman Kristiani di {paroki}. Lumen Christi.',
        'Alleluia yang tertahan 40 hari akhirnya meledak bergema di Gereja SMDTBA malam ini.',
        'Dari kegelapan ke terang — malam vigili {paroki} selalu menjadi momen yang tidak bisa dilupakan.',
        'Exsultet bergema, lilin menyala satu per satu. Paskah dimulai di {paroki} malam ini.',
        'Sabtu siang: hening total. Sabtu malam: Gereja SMDTBA penuh dan bercahaya. Itulah iman.',
        'Bayi dibaptis malam ini di {paroki}. Kebangkitan Kristus dan kelahiran baru — dalam satu vigili.',
    ],

    // ── PASKAH ────────────────────────────────────────────────────────────
    'paskah' => [
        'Alleluia bergema di {paroki} — Kristus bangkit, dan tidak ada sukacita yang sebanding.',
        'Setelah 40 hari Prapaskah, momen ini terasa seperti napas lega yang sudah lama ditunggu.',
        'Paskah {tahun} di {paroki}: momen komunitas merayakan bahwa kematian bukan akhir dari cerita.',
        'Di ujung malam vigili, imam berseru Alleluia — dan Gereja SMDTBA seperti meledak.',
        'Lilin Paskah itu akan menyala 50 hari ke depan. Seperti iman umat {paroki} yang tidak padam.',
        'Jawaban umat {paroki} tidak berubah: Ya, kami percaya. Dan kepercayaan itulah yang mengubah segalanya.',
    ],

    // ── ADVEN ─────────────────────────────────────────────────────────────
    'adven' => [
        'Lilin pertama Adven menyala. Di {paroki}, waktu menunggu itu bukan kosong — itu penuh harapan.',
        'Empat minggu, empat lilin. Komunitas {paroki} menghitung hari menuju kelahiran yang mengubah segalanya.',
        'Adven di {paroki}: bukan hanya persiapan Natal, melainkan pembaruan kerinduan akan Kristus.',
        'Lilin merah muda Minggu Gaudete menyala di {paroki}. Sukacita itu dekat — Ia sudah di depan pintu.',
    ],

    // ── CORPUS CHRISTI ────────────────────────────────────────────────────
    'corpus_christi' => [
        'Monstrans itu berjalan di antara umat {paroki}. Kristus hadir nyata — dan kami mengiringi-Nya.',
        'Prosesi Corpus Christi {paroki}: pesta ekaristi yang paling meriah dan penuh devosi.',
        'Di hari raya ini, Gereja SMDTBA keluar dari dinding gereja — Kristus dibawa melewati {kota}.',
        'Kehadiran nyata Kristus dalam ekaristi dirayakan bersama di {paroki} dengan penuh sukacita.',
    ],

    // ── HATI YESUS ────────────────────────────────────────────────────────
    'hati_yesus' => [
        'Pertama Jumat di {paroki}: devosi yang tidak mengenal absen bagi mereka yang mengasihi Kristus.',
        'Hati Yesus yang terbuka itu tidak memilih-milih siapa yang datang. Di {paroki}, semua disambut.',
        'Litani Hati Kudus didoakan komunitas {paroki} — doa lama yang selalu terasa baru di setiap bulannya.',
    ],

    // ── MARIA DIANGKAT ────────────────────────────────────────────────────
    'maria_diangkat' => [
        '15 Agustus di {paroki}: pesta Maria yang paling agung — dan Gereja SMDTBA pun ikut bersukacita.',
        'Bunda Maria sudah sampai lebih dulu. Dan hari ini, komunitas {paroki} bersukacita atas kepergiannya.',
        'Bunga di altar Gereja SMDTBA hari ini lebih banyak dari biasanya — karena ini pestanya Bunda.',
    ],

    // ── PESTA PELINDUNG ───────────────────────────────────────────────────
    'pesta_pelindung' => [
        '8 Desember di Gereja SMDTBA: bukan hanya hari raya, tapi ulang tahun paroki yang paling bermakna.',
        'Pesta Pelindung {paroki} — hari di mana komunitas bersyukur atas Santa Maria Tidak Bernoda.',
        'Setahun penuh bersama, dan pada 8 Desember ini umat {paroki} berhenti sejenak untuk bersyukur.',
        'Santa Maria Dengan Tidak Bernoda Asal — nama yang dibanggakan umat {kota} selama bertahun-tahun.',
        'Pesta paling istimewa dalam kalender {paroki}: ulang tahun patrona yang selalu ditunggu-tunggu.',
    ],

    // ── KRISTUS RAJA ──────────────────────────────────────────────────────
    'kristus_raja' => [
        'Penutup tahun liturgi di {paroki}: mahkota diserahkan kepada Kristus Raja yang menguasai segalanya.',
        'Minggu terakhir Masa Biasa. Di {paroki}, tahun gerejawi ditutup dengan sebuah penegasan: Ia adalah Raja.',
        'Kristus Raja Semesta Alam — penegasan akhir tahun liturgi yang selalu menguatkan iman {paroki}.',
    ],

    // ── SAKRAMEN ──────────────────────────────────────────────────────────
    'baptis' => [
        'Air mengalir di dahi, nama baru disebut — seseorang resmi masuk dalam komunitas {paroki}.',
        'Tangis bayi yang baru dibaptis itu sebenarnya tanda kehidupan baru di {paroki}.',
        'Katekumen itu sudah menunggu lama. Kini air baptis menjawab penantian mereka di Gereja SMDTBA.',
        'Lilin baptis dinyalakan — dan seseorang mulai berjalan sebagai anak Allah di {paroki}.',
    ],

    'komuni' => [
        'Tangan kecil itu terbuka — dan Kristus hadir di dalamnya untuk pertama kali di {paroki}.',
        'Orang tua menahan tangis di barisan belakang. Di depan, putra-putrinya maju komuni pertama.',
        'Hari paling kudus dalam iman anak-anak {paroki}: meja Tuhan menyambut mereka.',
        'Misa Komuni Pertama {paroki} — hari yang tidak akan pernah lupa oleh mereka yang ada di sana.',
    ],

    'krisma' => [
        'Minyak krisma mengurapi dahi mereka — dan mereka bukan lagi yang sama setelah itu di {paroki}.',
        'Para pemuda {paroki} maju satu per satu. Satu usapan krisma, satu komitmen seumur hidup.',
        'Uskup mengurapi, Roh Kudus memeterai — generasi penerus {paroki} resmi dewasa dalam iman.',
    ],

    'tahbisan' => [
        'Ia berbaring di lantai altar dalam keheningan total. Momen itu tidak akan pernah terlupakan di {paroki}.',
        'Tangan Uskup ditumpangkan — dan seseorang menjadi imam untuk selamanya bagi umat {paroki}.',
        'Imam baru itu warganya sendiri. Dan komunitas {paroki} tidak mampu menyembunyikan kebanggaannya.',
    ],

    'pernikahan' => [
        'Dua orang berdiri di altar {paroki} dan berjanji — atas nama Tuhan, untuk selama-lamanya.',
        'Lorong Gereja SMDTBA selalu terasa lebih panjang saat dilewati pasangan pengantin yang menggenggam tangan.',
        'Janji setia itu diucapkan pelan, tapi seluruh komunitas {paroki} mendengarnya.',
    ],

    'tobat' => [
        'Antrian panjang di depan bilik tobat {paroki} — tanda bahwa rindu akan pengampunan itu nyata.',
        'Mereka masuk dengan beban. Mereka keluar dengan wajah yang berbeda. Sakramen tobat di {paroki}.',
        'Tidak semua yang datang ke Gereja SMDTBA malam ini mencari khotbah. Sebagian mencari absolusi.',
    ],

    // ── KELOMPOK ──────────────────────────────────────────────────────────
    'mudika' => [
        'Mereka muda, berenergi, dan beriman. OMK {paroki} membuktikan bahwa generasi penerus itu ada.',
        'Kegiatan {kategori} ini bukan sekadar acara. Bagi Mudika {paroki}, ini adalah cara menghidupi iman.',
        'Wajah-wajah muda {paroki} dalam kegiatan ini adalah gambar masa depan Gereja Katolik {kota}.',
    ],

    'wkri' => [
        'Ibu-ibu {paroki} hadir, setia, dan melayani — kekuatan WKRI yang tidak pernah habis.',
        'Pertemuan WKRI {paroki}: selalu hangat, selalu produktif, selalu ada yang bisa dibagi.',
        'Di balik setiap kegiatan {paroki} yang sukses, ada tangan-tangan ibu WKRI yang bekerja.',
    ],

    'legio' => [
        'Setia dari pintu ke pintu — itulah Legio Maria {paroki}, tanpa fanfare, tanpa lelah.',
        'Pertemuan Legio Maria {paroki}: doa, laporan, dan semangat pelayanan yang tidak pernah turun.',
        'Bendera Legio berkibar, dan komunitas {paroki} tahu — pelayanan yang sunyi sedang terjadi.',
    ],

    'karismatik' => [
        'Tangan terangkat, suara bergema — Roh Kudus terasa nyata dalam pertemuan PKK {paroki}.',
        'Di {paroki}, ada komunitas yang memuji Tuhan dengan cara yang sangat hidup dan bersemangat.',
        'Seminar Hidup Baru di {paroki}: masuk dengan keraguan, keluar dengan iman yang terbakar.',
    ],

    'cursillo' => [
        'De Colores! — dua kata yang menggetarkan hati setiap Cursillista yang berkumpul di {paroki}.',
        'Ultreya {paroki}: pertemuan yang selalu menghangatkan dan membarakan iman yang pernah menyala.',
    ],

    'lansia' => [
        'Mereka datang paling awal, duduk paling depan. Para lansia {paroki} adalah teladan kesetiaan.',
        'Iman tidak menua. Komunitas lansia {paroki} membuktikannya setiap kali mereka hadir bersama.',
        'Di pertemuan opa-oma {paroki}, kearifan dan sukacita selalu hadir dalam porsi yang sama.',
    ],

    'koor' => [
        'Tanpa koor, misa di {paroki} masih berlangsung. Tapi dengan mereka, jiwa terasa lebih terangkat.',
        'Mereka berlatih sebelum misa. Mereka melayani saat misa. Koor {paroki} tidak kenal setengah-setengah.',
        'Setiap nada koor {paroki} adalah doa — dan saat mereka bersatu, seluruh gereja ikut merasakan.',
    ],

    'lektor' => [
        'Lektor itu berdiri di ambo — dan untuk beberapa menit, seluruh {paroki} mendengarkan Sabda.',
        'Misdinar {paroki} melayani dengan khidmat. Mereka masih muda, tapi tanggung jawabnya nyata.',
        'Tim pelayan liturgi {paroki}: bekerja di belakang layar agar misa berjalan indah.',
    ],

    // ── KEGIATAN ──────────────────────────────────────────────────────────
    'retret' => [
        'Dua hari hening di luar rutinitas. Peserta retret {paroki} kembali dengan hati yang diperbaharui.',
        'Rekoleksi {paroki}: bukan liburan, bukan seminar. Ini waktu bersama Tuhan yang tidak bisa digantikan.',
        'Dari retret {paroki}, yang dibawa pulang bukan sertifikat — tapi ketenangan yang bertahan lama.',
    ],

    'ziarah' => [
        'Bus ziarah itu sudah berjalan sejak subuh. Umat {paroki} tidak mengeluh soal jauhnya jalan.',
        'Di depan gua Maria, umat {paroki} berdoa. Beberapa menangis. Semua pulang dengan lebih ringan.',
        'Ziarah {paroki}: perjalanan rohani yang mengubah sesuatu yang sulit dijelaskan tapi nyata dirasakan.',
    ],

    'bazar' => [
        'Halaman Gereja SMDTBA berubah meriah — bazar tahunan {paroki} selalu seperti reuni komunitas.',
        'Stand makanan, lomba anak, dan kebersamaan hangat — itulah bazar {paroki} dalam satu kalimat.',
        'Bazar {paroki}: penggalangan dana yang menyenangkan karena semua orang mau terlibat.',
    ],

    'baksos' => [
        'Komunitas {paroki} hadir bukan untuk dipotret. Mereka hadir karena ada yang membutuhkan.',
        'Baksos {paroki}: iman yang terwujud dalam tangan yang memberi dan kaki yang menjangkau.',
        'Santunan itu kecil secara angka. Tapi bagi penerimanya di {kota}, itu sangat besar maknanya.',
    ],

    'donor_darah' => [
        'Satu kantong darah dari umat {paroki} bisa menyelamatkan nyawa yang tidak mereka kenal.',
        'Donor darah {paroki}: aksi paling konkret bahwa kasih sesama itu bukan slogan.',
    ],

    'kesehatan' => [
        'Antrian pemeriksaan panjang di halaman {paroki} — bukti bahwa pelayanan kasih tidak pernah sepi.',
        'Baksos kesehatan {paroki}: dokter, perawat, dan umat bergotong royong untuk sesama.',
    ],

    'pelantikan' => [
        'Pengurus baru {paroki} dilantik — dengan janji pelayanan yang diucapkan di hadapan komunitas.',
        'Serah terima jabatan di {paroki}: estafet kepemimpinan yang berlangsung dengan hormat dan syukur.',
    ],

    'rapat' => [
        'Rapat DPP {paroki}: forum di mana komunitas merencanakan langkah berikutnya bersama-sama.',
        'Sidang paroki {kota} berjalan tertib dan terbuka — karena setiap suara berhak didengar.',
    ],

    'pastoral' => [
        'Romo datang ke lingkungan. Bagi umat {paroki}, kunjungan itu selalu terasa seperti Kristus yang hadir.',
        'Visitasi pastoral {paroki}: gembala menyapa kawanannya satu per satu — dan komunitas merasa dikenal.',
    ],

    'sejarah' => [
        'Foto lama itu menyimpan cerita yang tidak ada di buku manapun. Warisan {paroki} yang tak ternilai.',
        'Setiap retak di dinding tua Gereja SMDTBA menyimpan doa generasi yang mendahului kita.',
        'Historia {paroki}: bukan hanya catatan tahun, tapi perjalanan iman dari generasi ke generasi.',
    ],

    'lingkungan' => [
        'Ibadat lingkungan {paroki}: tidak butuh gedung besar. Cukup ruang tamu dan hati yang mau hadir.',
        'Di sini, di lingkaran kecil ini, iman {paroki} benar-benar tumbuh dari bawah.',
    ],

    'wilayah' => [
        'Ratusan umat dari berbagai lingkungan {paroki} berhimpun. Pertemuan wilayah ini selalu menghadirkan semangat baru.',
        'Pertemuan wilayah {paroki}: simpel tapi konsisten. Dan konsistensi itulah yang membangun kepercayaan.',
    ],

    'sekolah_minggu' => [
        'Di ruangan itu ada gelak tawa dan lagu. Di situlah iman generasi {paroki} mulai dibentuk.',
        'Pendamping BIA {paroki} tidak digaji. Tapi mereka hadir tiap minggu — karena tahu betapa pentingnya ini.',
        'Anak-anak itu belum mengerti teologi. Tapi mereka sudah tahu: Tuhan mengasihi mereka.',
    ],

    'katekese' => [
        'Sesi katekese {paroki} selalu hidup. Tidak ada yang tidur — pertanyaan jujur disambut jawaban tulus.',
        '"Saya tidak pernah tahu itu artinya seperti itu" — kalimat yang sering terdengar setelah katekese {paroki}.',
        'Umat tua dan muda duduk bersama dalam katekese {paroki}. Pengalaman dan semangat bertemu.',
    ],

    'kronik' => [
        'Kronik {paroki}: catatan harian sebuah komunitas yang hidup, bergerak, jatuh, bangkit, dan terus berjalan.',
        'Setiap foto dalam kronik ini menyimpan cerita — bukan sekadar acara, tapi manusia di dalamnya.',
        'Laporan {menu_label} {paroki}: dokumen sederhana yang suatu hari akan menjadi sejarah berharga.',
    ],

    'berita' => [
        'Kabar terbaru dari {paroki}: komunitas terus bergerak, melayani, dan bertumbuh dalam iman.',
        'Warta {paroki}: setiap berita adalah bukti bahwa Gereja Katolik {kota} tidak pernah berhenti.',
        'Berita ini kecil di mata dunia. Tapi bagi umat {kota}, ini cerita yang paling relevan.',
    ],

    'pekan_suci' => [
        'Tiga hari — satu perjalanan iman terbesar. Triduum Paskah {paroki} dijalani bersama komunitas.',
        'Pekan Suci di {paroki}: dari hosana hingga alleluia, jarak itu diisi dengan salib dan keheningan.',
        'Komunitas {paroki} melewati Pekan Suci bersama. Yang berat terasa ringan ketika ditanggung bersama.',
    ],

    'generic' => [
        '{judul_singkat} di {paroki} — bukti bahwa iman yang hidup selalu menemukan caranya untuk berbuah.',
        'Komunitas {paroki} {kota} hadir dalam satu momen yang sama. Itu sudah cukup untuk disebut bermakna.',
        'Tidak selalu harus besar dan meriah. {judul_singkat} di {paroki} sudah cukup menguatkan banyak orang.',
        'Begitulah {paroki}: selalu ada yang berkumpul, selalu ada yang berbagi, selalu ada yang pulang dengan hati penuh.',
        'Di {paroki}, tidak ada momen yang terlalu kecil untuk dirayakan. Karena setiap pertemuan adalah karunia.',
        'Apa yang membuat umat {paroki} selalu kembali? Momen seperti {judul_singkat} inilah jawabannya.',
        'Satu kegiatan {kategori}. Satu komunitas {paroki}. Satu iman yang terus mengikat semuanya.',
    ],

        ];

        return $pools[$ctx] ?? $pools['generic'];
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  TITLE TEMPLATES (untuk title attr HTML & SEO)
    // ══════════════════════════════════════════════════════════════════════════

    private static function titleTemplates(string $ctx, bool $isGeneric): array
    {
        $pools = [
            'misa'              => ['Misa {menu_label} — {paroki}', 'Perayaan Ekaristi Umat {kota}', 'Misa Kudus {kategori} Gereja SMDTBA', 'Ekaristi Bersama Umat {paroki}', 'Liturgi {menu_label} Paroki Santa Maria {kota}'],
            'rosario'           => ['Doa Rosario Bersama {paroki}', 'Devosi Rosario Umat {kota}', 'Rosario Bersama di Gereja SMDTBA', 'Bulan Rosario {paroki}'],
            'adorasi'           => ['Adorasi Sakramen Mahakudus {paroki}', 'Doa Adorasi Umat {kota}', 'Adorasi Ekaristi Gereja SMDTBA', 'Keheningan Adorasi {paroki}'],
            'jalan_salib'       => ['Jalan Salib Agung {paroki}', 'Via Crucis Umat {kota}', 'Permenungan Jalan Salib {paroki}', 'Prosesi Jalan Salib Gereja SMDTBA'],
            // Natal & rangkaian
            'natal'             => ['Perayaan Natal {paroki} {tahun}', 'Misa Natal Gereja SMDTBA {kota}', 'Sukacita Natal Umat {paroki}', 'Malam Kudus Natal {paroki} {tahun}'],
            'keluarga_kudus'    => ['Pesta Keluarga Kudus {paroki} {tahun}', 'Nazaret di {paroki} — Misa Keluarga Kudus', 'Keluarga Kudus Teladan Umat {kota}'],
            'maria_bunda_allah' => ['Hari Raya Maria Bunda Allah {paroki}', 'Misa 1 Januari — Bersama Maria {kota}', 'Tahun Baru Bersama Bunda Allah {paroki}'],
            'epifani'           => ['Hari Raya Penampakan Tuhan {paroki}', 'Epifani — Cahaya bagi Segala Bangsa {kota}', 'Misa Epifani Gereja SMDTBA {tahun}'],
            'pembaptisan_tuhan' => ['Hari Raya Pembaptisan Tuhan {paroki}', 'Penutup Masa Natal di {paroki}', 'Pembaptisan Kristus — Misa {paroki} {tahun}'],
            // Prapaskah & Pekan Suci
            'prapaskah'         => ['Masa Prapaskah — Pertobatan {paroki}', 'Rabu Abu Awal Prapaskah {kota}', 'APP Prapaskah {paroki} {tahun}', 'Pertobatan Menuju Paskah {paroki}'],
            'minggu_palma'      => ['Minggu Palma {paroki} {tahun}', 'Prosesi Palma Gereja SMDTBA {kota}', 'Perarakan Palma Pembukaan Pekan Suci {paroki}'],
            'kamis_putih'       => ['Kamis Putih — In Caena Domini {paroki}', 'Perjamuan Malam Terakhir {paroki} {tahun}', 'Misa Kamis Putih dan Tuguran {kota}'],
            'jumat_agung'       => ['Jumat Agung — Ibadat Sengsara {paroki}', 'Penghormatan Salib Gereja SMDTBA {kota}', 'Jumat Agung {paroki} {tahun}'],
            'sabtu_suci'        => ['Vigili Paskah {paroki} {tahun}', 'Malam Paskah — Api dan Alleluia {kota}', 'Vigili Suci Gereja SMDTBA {tahun}'],
            'pekan_suci'        => ['Pekan Suci {paroki} {kota} {tahun}', 'Triduum Paskah di Paroki Santa Maria {kota}', 'Trihari Suci {paroki} — Sengsara, Wafat, Kebangkitan'],
            // Masa Paskah
            'paskah'            => ['Paskah Kristus Bangkit — {paroki}', 'Alleluia! Paskah {paroki} {kota}', 'Kebangkitan Kristus Dirayakan Umat {kota}', 'Paskah {tahun} Paroki Santa Maria {kota}'],
            'kenaikan'          => ['Hari Raya Kenaikan Tuhan {paroki}', 'Kenaikan Kristus ke Surga {kota}', 'Perayaan Kenaikan {paroki} {tahun}'],
            'pentakosta'        => ['Pentakosta — Turunnya Roh Kudus {paroki}', 'Hari Raya Pentakosta Gereja SMDTBA', 'Api Roh Kudus {paroki} {kota}'],
            // Hari Raya Masa Biasa
            'corpus_christi'    => ['Corpus Christi — Prosesi Sakramen {paroki}', 'Hari Raya Tubuh dan Darah Kristus {kota}', 'Prosesi Corpus Christi Gereja SMDTBA {tahun}'],
            'hati_yesus'        => ['Hari Raya Hati Yesus Mahakudus {paroki}', 'Devosi Hati Kudus Yesus {kota}', 'Misa Pertama Jumat Gereja SMDTBA'],
            'maria_diangkat'    => ['Maria Diangkat ke Surga — {paroki} {tahun}', '15 Agustus di Gereja SMDTBA {kota}', 'Pesta Bunda Maria Agustus {paroki}'],
            'pesta_pelindung'   => ['Pesta Pelindung SMDTBA — 8 Desember {tahun}', 'Santa Maria Tidak Bernoda — Patrona {paroki}', 'Dies Natalis {paroki} {tahun}'],
            'kristus_raja'      => ['Kristus Raja Semesta Alam — {paroki}', 'Penutup Tahun Liturgi {paroki} {tahun}', 'Hari Raya Kristus Raja Gereja SMDTBA'],
            // Adven
            'adven'             => ['Masa Adven — Penantian di {paroki}', 'Lilin Adven Gereja SMDTBA {kota}', 'Penantian Kristus dalam Adven {paroki}'],
            // Sakramen
            'baptis'            => ['Sakramen Baptis — {paroki} {kota}', 'Pembaptisan Umat Gereja SMDTBA', 'Kelahiran Baru dalam Kristus {paroki}'],
            'komuni'            => ['Komuni Pertama — {paroki} {kota}', 'Momen Komuni Pertama {paroki}', 'Tubuh Kristus Disambut Anak {paroki}'],
            'krisma'            => ['Sakramen Krisma — {paroki} {kota}', 'Penguatan Iman Umat Muda {paroki}', 'Meterai Roh Kudus {paroki}'],
            'tahbisan'          => ['Tahbisan Imam Baru {paroki}', 'Imamat Kudus {paroki} {tahun}', 'Imam Baru untuk {paroki} {kota}'],
            'pernikahan'        => ['Pernikahan Kudus di {paroki} {kota}', 'Pemberkatan Nikah Gereja SMDTBA', 'Dua Hati Bersatu di {paroki}'],
            'tobat'             => ['Rekonsiliasi Massal {paroki}', 'Sakramen Tobat Umat {kota}', 'Pengampunan Dosa di {paroki}'],
            // Kelompok
            'mudika'            => ['Mudika {paroki} — Generasi Iman {kota}', 'OMK {paroki} Aktif Berkarya', 'Pemuda Katolik {kota} Bersemangat'],
            'wkri'              => ['WKRI {paroki} — Wanita Beriman', 'Wanita Katolik {paroki} Melayani'],
            'legio'             => ['Legio Maria {paroki} — Setia Melayani', 'Presidium Legio Maria {kota}'],
            'karismatik'        => ['PKK {paroki} — Pujian dan Penyembahan', 'Karismatik Katolik {kota}', 'Pembaruan Karismatik {paroki} {tahun}'],
            'cursillo'          => ['Cursillo {paroki} — De Colores!', 'Ultreya Komunitas Cursillo {kota}'],
            'lansia'            => ['Lansia {paroki} — Iman Tanpa Batas Usia', 'Komunitas Opa-Oma {kota}'],
            'koor'              => ['Paduan Suara {paroki} — Melayani Liturgi', 'Koor Gereja SMDTBA {kota}'],
            'lektor'            => ['Pelayan Liturgi {paroki}', 'Lektor dan Prodiakon {kota}', 'Misdinar Gereja SMDTBA'],
            // Kegiatan
            'retret'            => ['Retret Rohani {paroki} {tahun}', 'Rekoleksi Umat {paroki} {kota}', 'Berdiam Bersama Tuhan {paroki}'],
            'ziarah'            => ['Ziarah Umat {paroki} {kota}', 'Perjalanan Rohani {paroki}', 'Devosi Ziarah Umat {kota}'],
            'bazar'             => ['Bazar Tahunan {paroki} {kota}', 'Pesta Bazar Gereja SMDTBA {tahun}'],
            'baksos'            => ['Bakti Sosial {paroki} — Berbagi Kasih', 'Pelayanan Sosial {paroki}'],
            'donor_darah'       => ['Donor Darah {paroki} — Berbagi Nyawa', 'Aksi Donor Sukarela Gereja SMDTBA'],
            'kesehatan'         => ['Baksos Kesehatan {paroki}', 'Pemeriksaan Gratis Umat {kota}'],
            'rapat'             => ['Musyawarah DPP {paroki}', 'Sidang Paroki {kota}', 'Rapat Pastoral {paroki} {tahun}'],
            'pelantikan'        => ['Pelantikan DPP {paroki} {tahun}', 'Kepemimpinan Baru {paroki}', 'Serah Terima Jabatan {paroki}'],
            'pastoral'          => ['Kunjungan Pastoral {paroki}', 'Visitasi Romo {kota}'],
            'sejarah'           => ['Sejarah {paroki} dari Masa ke Masa', 'Historia Paroki Santa Maria {kota}', 'Warisan Iman {paroki}'],
            'lingkungan'        => ['Kebersamaan Lingkungan {paroki}', 'Ibadat Lingkungan {paroki}'],
            'wilayah'           => ['Pertemuan Wilayah {paroki}', 'Koordinasi Wilayah {kota}'],
            'sekolah_minggu'    => ['Sekolah Minggu {paroki}', 'BIA Gereja SMDTBA {kota}', 'Generasi Beriman {paroki} {kota}'],
            'katekese'          => ['Katekese Umat {paroki}', 'Pendalaman Iman {kota}', 'Pembinaan Iman {paroki}'],
            'kronik'            => ['Kronik {menu_label} {paroki}', 'Dokumentasi {kategori} Paroki Santa Maria {kota}'],
            'berita'            => ['Berita Terkini {paroki}', 'Warta {paroki} {tahun}', 'Liputan Kegiatan {paroki}'],
            'generic'           => ['{judul_singkat} — {paroki}', 'Kegiatan {kategori} {paroki} {kota}', 'Dokumentasi {kategori} Gereja SMDTBA', '{paroki} — {kota} {tahun}', 'Komunitas Iman {paroki} {kota}'],
        ];

        return $pools[$ctx] ?? $pools['generic'];
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  DESCRIPTION TEMPLATES (schema.org ImageObject, 30–55 kata)
    // ══════════════════════════════════════════════════════════════════════════

    private static function descTemplates(string $ctx, bool $isGeneric): array
    {
        $pools = [
            'misa' => [
                'Foto dokumentasi perayaan misa {menu_label} yang diselenggarakan oleh {paroki}. Momen ekaristi ini merekam kekhusyukan umat Gereja Katolik {kota} dalam berdoa dan merayakan kehadiran Kristus di tengah komunitas.',
                'Gambar suasana misa kudus {kategori} di {paroki}. Perayaan ekaristi adalah jantung kehidupan rohani komunitas Paroki Santa Maria {kota}, Jawa Timur — dirayakan setiap hari dengan iman yang sama.',
                'Dokumentasi misa {menu_label} di Gereja SMDTBA {kota} yang dihadiri ratusan umat dari berbagai lingkungan. Setiap ekaristi menjadi perjumpaan nyata dengan Kristus yang hadir dalam roti dan anggur yang dikuduskan.',
            ],
            'rosario' => [
                'Dokumentasi doa rosario bersama umat {paroki}. Devosi kepada Bunda Maria ini menjadi bagian penting spiritualitas komunitas Gereja Katolik {kota} yang dijalankan dengan tekun dan penuh keheningan.',
                'Foto kegiatan rosario bersama di {paroki} yang mempertemukan umat dalam satu barisan doa. Melalui setiap misteri, umat {kota} semakin mendekat kepada Kristus lewat syafaat Bunda Maria.',
            ],
            'adorasi' => [
                'Gambar adorasi Sakramen Mahakudus di {paroki} yang menampilkan kekhusyukan umat dalam keheningan. Momen adorasi adalah saat paling intim di mana umat {kota} berdiam dan mendengar suara Tuhan.',
                'Dokumentasi adorasi ekaristi yang berlangsung hening dan penuh makna di Gereja SMDTBA {kota}. Dalam keheningan itu, umat {paroki} menemukan kedalaman iman yang sulit diungkapkan dengan kata-kata.',
            ],
            'jalan_salib' => [
                'Foto prosesi jalan salib umat {paroki} yang berlangsung khidmat dan penuh devosi. Setiap perhentian via crucis menjadi undangan mendalam untuk merenungkan kasih Kristus yang rela menderita demi umat manusia.',
                'Dokumentasi kegiatan jalan salib di {paroki} yang melibatkan seluruh komunitas umat {kota}. Permenungan 14 perhentian ini menjadi sarana penguatan iman dan pendalaman makna sengsara Kristus.',
            ],
            'natal' => [
                'Dokumentasi perayaan Natal di {paroki} yang menampilkan sukacita dan kebersamaan umat Gereja Katolik {kota}. Natal menjadi puncak perayaan iman yang menyatukan seluruh komunitas dalam kasih Kristus.',
                'Foto momen Natal yang meriah dan bermakna bagi komunitas {paroki}. Kebersamaan umat dalam merayakan kelahiran Yesus mencerminkan semangat kasih dan persaudaraan Gereja Katolik {kota}.',
                'Dokumentasi misa natal dan perayaan hari raya kelahiran Kristus di {paroki}. Suasana penuh cahaya lilin dan nyanyian natal memperkuat makna Natal sebagai peristiwa kasih yang mengubah dunia selamanya.',
            ],
            'keluarga_kudus' => [
                'Foto Pesta Keluarga Kudus di {paroki} yang mengundang seluruh keluarga umat Gereja Katolik {kota}. Keluarga Nazaret direnungkan sebagai teladan nyata bagi setiap rumah tangga Kristiani yang beriman.',
                'Dokumentasi misa Pesta Keluarga Kudus di Gereja SMDTBA {kota}. Yesus, Maria, dan Yusuf menjadi inspirasi bagi keluarga-keluarga {paroki} dalam membangun rumah tangga yang berpusat pada Tuhan.',
            ],
            'maria_bunda_allah' => [
                'Foto misa Hari Raya Maria Bunda Allah pada 1 Januari di {paroki}. Komunitas Gereja Katolik {kota} memulai tahun baru dalam doa dan iman bersama Bunda Allah yang menjaga perjalanan mereka.',
                'Dokumentasi perayaan Hari Raya Maria Bunda Allah sekaligus Hari Perdamaian Sedunia di Gereja SMDTBA {kota}. Awal tahun disambut dengan doa bersama yang dipersembahkan kepada Maria, Bunda Kristus.',
            ],
            'epifani' => [
                'Foto misa Hari Raya Penampakan Tuhan (Epifani) di {paroki}. Kedatangan orang-orang majus dari timur dirayakan sebagai tanda bahwa Kristus adalah cahaya bagi seluruh bangsa di Gereja SMDTBA {kota}.',
                'Dokumentasi Hari Raya Epifani yang menutup rangkaian Masa Natal di {paroki}. Tiga raja menginspirasi komunitas {kota} untuk terus mengikuti bintang iman menuju Kristus yang selalu menanti.',
            ],
            'pembaptisan_tuhan' => [
                'Foto misa Hari Raya Pembaptisan Tuhan di {paroki} yang menandai penutupan resmi Masa Natal. Pembaptisan Yesus di sungai Yordan dirayakan komunitas Gereja Katolik {kota} sebagai peristiwa iman yang mendasar.',
                'Dokumentasi perayaan Hari Raya Pembaptisan Tuhan di Gereja SMDTBA {kota}. Suara Bapa dari surga mengingatkan umat {paroki} akan identitas mereka sendiri sebagai anak-anak Allah yang terkasih.',
            ],
            'prapaskah' => [
                'Foto kegiatan Prapaskah di {paroki} yang meliputi penerimaan abu, jalan salib, dan aksi karitatif APP. Masa 40 hari pertobatan ini menjadi sarana pendalaman iman komunitas Gereja Katolik {kota} menuju Paskah.',
                'Dokumentasi masa Prapaskah di {paroki}, termasuk misa Rabu Abu dan kegiatan pertobatan umat {kota}. Setiap kegiatan Prapaskah menjadi langkah nyata komunitas mendekati Kristus yang menderita demi kasih.',
            ],
            'minggu_palma' => [
                'Foto prosesi palma dalam Minggu Palma di {paroki} yang membuka Pekan Suci {tahun}. Umat Gereja Katolik {kota} merayakan masuknya Kristus ke Yerusalem dengan mengarak daun palma yang telah diberkati.',
                'Dokumentasi Minggu Palma di Gereja SMDTBA {kota}, pembukaan liturgi Pekan Suci yang paling sakral. Prosesi daun palma dan bacaan Sengsara panjang mengantar komunitas {paroki} memasuki Triduum Paskah.',
            ],
            'kamis_putih' => [
                'Foto misa Kamis Putih (In Caena Domini) di {paroki} yang menampilkan upacara pembasuhan kaki dan tuguran. Komunitas Gereja Katolik {kota} mengenang perjamuan malam terakhir dan lembaga ekaristi bersama.',
                'Dokumentasi liturgi Kamis Putih di Gereja SMDTBA {kota}. Misa pembasuhan kaki, pemindahan Sakramen Mahakudus, dan tuguran malam menjadi pengalaman iman paling mendalam bagi umat {paroki}.',
            ],
            'jumat_agung' => [
                'Foto ibadat Jumat Agung di {paroki} yang berlangsung hening dan penuh permenungan. Sengsara Kristus dihayati bersama oleh seluruh komunitas Gereja Katolik {kota} dalam kekhidmatan yang mendalam.',
                'Dokumentasi ibadat Sengsara dan penghormatan salib pada Jumat Agung di Gereja SMDTBA {kota}. Hari tanpa misa ini menjadi puncak permenungan komunitas {paroki} atas wafat Kristus demi keselamatan manusia.',
            ],
            'sabtu_suci' => [
                'Foto vigili Paskah di {paroki} yang menampilkan api Paskah, exsultet, dan alleluia pertama. Malam terbesar dalam tahun liturgi ini dirayakan penuh sukacita oleh komunitas Gereja Katolik {kota}.',
                'Dokumentasi misa vigili Paskah di Gereja SMDTBA {kota} dengan liturgi api, tujuh bacaan, liturgi baptis, dan ekaristi Paskah. Komunitas {paroki} menyambut kebangkitan Kristus dalam malam yang paling agung.',
            ],
            'paskah' => [
                'Gambar perayaan Paskah di {paroki} yang menggambarkan sukacita kebangkitan Kristus. Liturgi Paskah menjadi puncak tahun liturgi yang dirayakan penuh kekhidmatan oleh seluruh umat {kota}.',
                'Foto vigili Paskah dan perayaan kebangkitan Tuhan di {paroki}. Alleluia mencerminkan sukacita iman komunitas Gereja Katolik {kota} yang percaya pada Kristus yang bangkit dan hidup.',
                'Dokumentasi misa Paskah {tahun} di Gereja SMDTBA {kota} yang dihadiri ratusan umat dengan penuh sukacita. Kebangkitan Kristus dirayakan sebagai puncak iman Kristiani yang mendasari seluruh kehidupan komunitas.',
            ],
            'kenaikan' => [
                'Foto perayaan hari raya kenaikan Tuhan Yesus Kristus di {paroki}. Liturgi kenaikan ini mengundang umat {kota} untuk merenungkan pengharapan surgawi yang menjadi tujuan akhir perjalanan iman mereka.',
                'Dokumentasi misa kenaikan di Gereja SMDTBA {kota} yang berlangsung khidmat. Kenaikan Kristus ke surga sekaligus menegaskan misi Gereja untuk mewartakan Injil sampai ke ujung bumi.',
            ],
            'pentakosta' => [
                'Foto perayaan hari raya Pentakosta di {paroki}. Turunnya Roh Kudus dirayakan dengan penuh sukacita oleh komunitas Gereja Katolik {kota} sebagai sumber kekuatan dan pelayanan Gereja sepanjang masa.',
                'Dokumentasi misa Pentakosta di Gereja SMDTBA {kota} yang berlangsung meriah dan penuh semangat. Roh Kudus diimani sebagai pendamping komunitas {paroki} dalam setiap langkah misi pelayanannya.',
            ],
            'corpus_christi' => [
                'Foto prosesi Sakramen Mahakudus dalam perayaan Corpus Christi di {paroki}. Monstrans dibawa melewati komunitas Gereja Katolik {kota} sebagai perayaan kehadiran nyata Kristus dalam ekaristi.',
                'Dokumentasi Hari Raya Tubuh dan Darah Kristus di Gereja SMDTBA {kota}. Prosesi agung yang melibatkan seluruh umat {paroki} ini menjadi penegasan iman akan kehadiran Kristus yang nyata dalam ekaristi.',
            ],
            'hati_yesus' => [
                'Foto perayaan Hari Raya Hati Yesus Mahakudus dan devosi misa Pertama Jumat di {paroki}. Kasih Kristus yang tak terbatas direnungkan komunitas Gereja Katolik {kota} dalam devosi yang penuh kesetiaan.',
                'Dokumentasi devosi Hati Kudus Yesus di Gereja SMDTBA {kota}. Umat {paroki} yang setia hadir dalam misa bulanan ini meneguhkan komitmen mereka untuk mencintai Kristus seutuhnya.',
            ],
            'maria_diangkat' => [
                'Foto misa Hari Raya Maria Diangkat ke Surga pada 15 Agustus di {paroki}. Pesta terbesar Bunda Maria ini dirayakan dengan sukacita dan devosi oleh seluruh komunitas Gereja Katolik {kota}.',
                'Dokumentasi perayaan Assumption of Mary di Gereja SMDTBA {kota}. Maria yang diangkat seutuhnya ke surga menjadi inspirasi dan harapan bagi umat {paroki} dalam perjalanan iman mereka.',
            ],
            'pesta_pelindung' => [
                'Foto perayaan Pesta Pelindung Paroki SMDTBA pada 8 Desember — Hari Raya Santa Maria Dengan Tidak Bernoda Asal. Komunitas Gereja Katolik {kota} bersukacita merayakan patrona tercinta mereka setiap tahunnya.',
                'Dokumentasi dies natalis {paroki} dalam Pesta Pelindung 8 Desember {tahun}. Hari Raya Santa Maria Tidak Bernoda menjadi puncak perayaan tahunan komunitas {kota} bagi patrona yang selalu dijunjung tinggi.',
            ],
            'kristus_raja' => [
                'Foto misa Hari Raya Kristus Raja Semesta Alam di {paroki} yang menutup tahun liturgi. Komunitas Gereja Katolik {kota} menyerahkan seluruh kehidupan dan perjalanan setahun penuh ke tangan Kristus Raja.',
                'Dokumentasi perayaan Kristus Raja di Gereja SMDTBA {kota} sebagai penutup tahun gerejawi. Penegasan tentang kerajaan Allah yang hadir dan berkembang menjadi pesan akhir tahun liturgi {paroki}.',
            ],
            'adven' => [
                'Gambar masa Adven di {paroki} yang mencerminkan semangat penantian umat Gereja Katolik {kota}. Lilin Adven yang menyala satu per satu menjadi simbol harapan komunitas yang menantikan kedatangan Kristus.',
                'Dokumentasi kegiatan Adven di {paroki} yang mempersiapkan hati umat menyambut Natal. Dalam penantian dan doa, komunitas {kota} memperbarui kerinduan akan Kristus yang terus hadir dalam kehidupan mereka.',
            ],
            'baptis' => [
                'Dokumentasi sakramen pembaptisan di {paroki}. Momen bersejarah ini merekam diterimanya anggota baru ke dalam keluarga besar Gereja Katolik {kota} melalui air baptis yang menyucikan.',
                'Foto sakramen baptis di {paroki} yang menampilkan keharuan dan sukacita keluarga serta komunitas. Pembaptisan adalah pintu masuk kehidupan Kristiani yang ditandai dengan air suci dan nama baru dalam Kristus.',
            ],
            'komuni' => [
                'Foto perayaan Komuni Pertama di {paroki} yang menampilkan kesakralan momen paling berkesan dalam perjalanan iman anak-anak umat. Meja Tuhan menyambut mereka untuk pertama kali dengan kasih yang tak terhingga.',
                'Dokumentasi Komuni Pertama di Gereja SMDTBA {kota} yang menjadi hari paling berkesan bagi keluarga Katolik. Anak-anak berpakaian putih itu menerima Tubuh Kristus dan seluruh komunitas bersukacita bersama.',
            ],
            'krisma' => [
                'Foto sakramen Krisma di {paroki} yang menampilkan momen penguatan iman kaum muda Gereja Katolik {kota}. Roh Kudus dimeteraikan dalam diri penerimanya sebagai bekal untuk menjadi saksi Kristus yang aktif.',
                'Dokumentasi misa penguatan (Krisma) di Gereja SMDTBA {kota} yang berlangsung khidmat dan penuh sukacita. Para penerima krisma berkomitmen menjadi bagian aktif dari misi Gereja Katolik {kota}.',
            ],
            'tahbisan' => [
                'Dokumentasi perayaan tahbisan imam di {paroki} yang menjadi momen bersejarah bagi komunitas Gereja Katolik {kota}. Imam baru dipercayakan untuk melanjutkan misi Kristus dalam pelayanan kepada umat.',
                'Foto misa tahbisan yang khidmat di Gereja SMDTBA {kota}. Dalam prosesi sakral yang mengharukan ini, seorang jiwa dipersembahkan sepenuhnya kepada Tuhan dan Gereja-Nya untuk selamanya.',
            ],
            'pernikahan' => [
                'Foto pemberkatan pernikahan kudus di {paroki}. Sakramen perkawinan yang dirayakan di Gereja SMDTBA {kota} ini menjadi momen paling berkesan bagi pasangan dan keluarga yang menyaksikannya.',
                'Dokumentasi misa pernikahan di {paroki} yang mencerminkan keindahan cinta yang dikuduskan Tuhan. Janji setia yang diucapkan di hadapan altar menjadi meterai kasih yang mengikat dua jiwa seumur hidup.',
            ],
            'tobat' => [
                'Dokumentasi rekonsiliasi massal dan sakramen tobat di {paroki}. Pengakuan dosa yang dilakukan umat {kota} ini adalah ungkapan kepercayaan bahwa tidak ada luka yang terlalu dalam untuk disembuhkan kasih Tuhan.',
                'Foto umat {paroki} yang dengan tekun menunggu giliran mengaku dosa. Sakramen tobat bukan hanya ritual — ia adalah pengalaman nyata pengampunan yang mengubah dan membarukan hidup seseorang.',
            ],
            'mudika' => [
                'Dokumentasi kegiatan {kategori} Orang Muda Katolik (OMK) {paroki}. Generasi muda Paroki Santa Maria {kota} aktif berkarya, melayani, dan bertumbuh dalam iman sebagai generasi penerus gereja.',
                'Foto aktivitas Mudika {paroki} dalam kegiatan yang mencerminkan semangat dan kreativitas kaum muda Gereja Katolik {kota}. OMK membuktikan bahwa iman yang hidup itu aktif, kreatif, dan penuh semangat.',
            ],
            'wkri' => [
                'Dokumentasi kegiatan WKRI {paroki}. Para wanita Katolik {kota} ini aktif dalam pelayanan rohani dan sosial sebagai cerminan iman yang hidup dan mengakar dalam kehidupan sehari-hari komunitas.',
            ],
            'legio' => [
                'Dokumentasi kegiatan Legio Maria {paroki}. Anggota Legio yang setia ini menjalankan tugas pelayanan dan devosi dengan tekun, meneladani semangat Bunda Maria sebagai hamba Tuhan yang rendah hati.',
            ],
            'karismatik' => [
                'Foto kegiatan Pembaruan Karismatik Katolik (PKK) {paroki}. Umat {kota} memuji dan menyembah Tuhan dengan penuh semangat, merasakan nyata kehadiran Roh Kudus yang membarakan hati komunitas.',
                'Dokumentasi Seminar Hidup Baru dalam Roh Kudus di {paroki}. Peserta mengalami sentuhan Roh Kudus yang membarakan iman dan komunitas {kota} menyaksikan kuasa-Nya yang nyata dalam kehidupan.',
            ],
            'cursillo' => [
                'Dokumentasi kegiatan Cursillo {paroki}. Komunitas Cursillo Gereja Katolik {kota} bertemu dalam ultreya yang membarakan semangat iman dan persaudaraan sesama pengikut Kristus.',
            ],
            'lansia' => [
                'Dokumentasi kegiatan kelompok lanjut usia {paroki}. Para opa dan oma ini membuktikan bahwa iman dan semangat pelayanan tidak mengenal batasan usia dalam komunitas Gereja Katolik {kota}.',
            ],
            'koor' => [
                'Dokumentasi paduan suara {paroki} dalam pelayanan liturgi. Koor Gereja SMDTBA {kota} memberikan kontribusi nyata dalam memperindah perayaan ekaristi dan mengantar umat kepada hadirat Tuhan melalui musik.',
            ],
            'retret' => [
                'Foto kegiatan retret dan rekoleksi umat {paroki}. Permenungan rohani ini menjadi sarana pembaruan iman dan semangat pelayanan bagi komunitas Gereja Katolik {kota}.',
                'Dokumentasi retret tahunan {paroki} yang menjadi oasis rohani bagi umat {kota}. Dalam keheningan dan permenungan bersama, komunitas memperbarui komitmen iman dan semangat pelayanannya.',
            ],
            'ziarah' => [
                'Foto perjalanan ziarah rohani umat {paroki}. Devosi dan doa mengiringi setiap langkah peziarah {kota} yang mencari perjumpaan lebih dalam dengan Tuhan dan pengalaman iman yang memperkuat.',
                'Dokumentasi ziarah umat {paroki} ke tempat-tempat suci. Perjalanan rohani ini memperdalam iman komunitas Gereja Katolik {kota} dan mempererat persaudaraan dalam satu tujuan mencari wajah Tuhan.',
            ],
            'bazar' => [
                'Dokumentasi bazar tahunan {paroki} yang menghadirkan kebersamaan hangat dan semangat komunitas Gereja Katolik {kota}. Penggalangan dana yang berlangsung meriah ini juga memperkuat persaudaraan umat.',
            ],
            'baksos' => [
                'Foto bakti sosial {paroki} yang menjangkau masyarakat sekitar dengan kasih nyata. Komunitas Gereja Katolik {kota} hadir melayani sesama sebagai perwujudan iman yang berbuah dalam kepedulian sosial.',
            ],
            'donor_darah' => [
                'Dokumentasi aksi donor darah sukarela {paroki}. Komunitas Gereja Katolik {kota} berbagi kasih lewat donasi darah yang menjadi penyelamat nyawa bagi sesama yang membutuhkan.',
            ],
            'kesehatan' => [
                'Foto baksos kesehatan {paroki} yang memberikan layanan pemeriksaan gratis kepada masyarakat. Komunitas Gereja Katolik {kota} hadir dengan dokter dan tim medis sukarela demi kesehatan sesama.',
            ],
            'pelantikan' => [
                'Foto pelantikan pengurus DPP {paroki} periode baru yang berlangsung khidmat. Pergantian kepemimpinan ini membawa semangat dan visi segar bagi perjalanan pastoral komunitas {kota}.',
            ],
            'rapat' => [
                'Foto sidang paroki {kota} yang berlangsung tertib dan produktif. Pengurus {paroki} berdiskusi secara terbuka demi merumuskan program pastoral yang semakin baik bagi seluruh umat.',
            ],
            'pastoral' => [
                'Dokumentasi kunjungan pastoral romo {paroki} ke lingkungan-lingkungan umat {kota}. Kehadiran gembala di tengah umatnya memperkuat ikatan komunitas dan menghidupkan iman dalam keseharian.',
            ],
            'sejarah' => [
                'Arsip foto bersejarah yang mendokumentasikan perjalanan panjang {paroki} dari masa ke masa. Historia ini menjadi warisan rohani yang berharga bagi generasi umat Paroki Santa Maria {kota}.',
                'Dokumentasi sejarah {paroki} yang merekam perjalanan iman dari generasi ke generasi. Foto-foto bersejarah ini mengingatkan bahwa Gereja Katolik {kota} dibangun oleh orang-orang yang percaya sepenuh hati.',
            ],
            'lingkungan' => [
                'Dokumentasi pertemuan dan ibadat lingkungan umat {paroki}. Lingkaran kecil komunitas ini adalah tempat iman dipupuk, persaudaraan dirawat, dan pelayanan dimulai dari yang paling dekat.',
            ],
            'wilayah' => [
                'Dokumentasi pertemuan wilayah umat {paroki}. Koordinasi antarlingkungan ini menjadi sarana penguatan komunitas yang lebih besar dalam menjalankan misi pastoral Gereja Katolik {kota}.',
            ],
            'sekolah_minggu' => [
                'Dokumentasi kegiatan Sekolah Minggu {paroki} yang berlangsung ceria dan penuh semangat. Bina Iman Anak adalah investasi masa depan Gereja Katolik {kota} yang dijalankan dengan kasih dan kreativitas.',
            ],
            'katekese' => [
                'Dokumentasi katekese dan pendalaman iman umat {paroki}. Pembinaan iman ini memperkuat pemahaman dan penghayatan umat Gereja Katolik {kota} dalam menjalani hidup berdasarkan ajaran Kristus.',
            ],
            'kronik' => [
                'Dokumentasi kegiatan {kategori} yang tercatat dalam kronik {paroki}. Catatan perjalanan ini merekam momen-momen berharga komunitas Gereja Katolik {kota} yang menjadi bagian dari sejarah hidup bersama.',
            ],
            'berita' => [
                'Foto dokumentasi berita dan informasi terkini dari {paroki}. Liputan ini memastikan seluruh umat Gereja Katolik {kota} selalu terhubung dan terinformasi tentang kehidupan komunitas yang terus bergerak.',
            ],
            'pekan_suci' => [
                'Dokumentasi Pekan Suci di {paroki} yang merekam momen-momen paling sakral dalam kalender liturgi Kristiani. Dari Minggu Palma hingga Sabtu Suci, umat {kota} menjalani Triduum Paskah bersama dalam iman.',
                'Foto prosesi Pekan Suci di Gereja SMDTBA {kota}. Trihari suci ini menjadi puncak perjalanan iman tahunan komunitas {paroki} yang menghayati sengsara, wafat, dan menantikan kebangkitan Kristus.',
            ],
            'generic' => [
                'Foto kegiatan {judul_singkat} yang diselenggarakan oleh {paroki}. Dokumentasi ini merekam momen berharga komunitas Gereja Katolik {kota} dalam menghidupi iman, persaudaraan, dan pelayanan bersama.',
                'Gambar dokumentasi {kategori} {paroki} yang merekam kehidupan nyata komunitas iman Gereja Katolik {kota}. Setiap kegiatan adalah ungkapan nyata iman yang hidup dan berbuah dalam kasih.',
                'Dokumentasi {judul_singkat} oleh {paroki}. Foto ini menjadi bagian dari perjalanan iman yang terus tumbuh dalam komunitas Paroki Santa Maria Dengan Tidak Bernoda Asal {kota}, Jawa Timur.',
                'Foto yang merekam salah satu momen bermakna dari perjalanan komunitas {paroki}. Bersama dalam sukacita, tantangan, dan pelayanan, umat Gereja Katolik {kota} terus bertumbuh sebagai komunitas yang mencintai Tuhan.',
            ],
        ];

        return $pools[$ctx] ?? $pools['generic'];
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  KEYWORD BUILDER
    // ══════════════════════════════════════════════════════════════════════════

    private static function buildKeywords(
        string $ctx,
        string $judul,
        array  $tags,
        string $kategori,
        string $filename,
        int    $seed
    ): array {
        if (function_exists('get_keywords_for_context')) {
            $base = get_keywords_for_context($ctx, 8);
        } else {
            $base = [
                'paroki smdtba tulungagung',
                'gereja katolik tulungagung',
                'paroki tulungagung',
                'keuskupan surabaya',
                'tulungagung',
            ];
        }

        $extra = [];
        foreach ($tags as $tag) {
            if (mb_strlen(trim($tag)) > 2) $extra[] = mb_strtolower(trim($tag));
        }

        $stopWords = ['yang','dari','dan','atau','untuk','dalam','pada','dengan','oleh',
                      'akan','saat','kita','para','bagi','atas','bisa','juga','sudah',
                      'telah','lebih','masih','baru','kami','anda','ini','itu','ada',
                      'satu','dua','tiga'];
        $judulWords = array_filter(
            preg_split('/[\s,.\-\/\(\)\[\]]+/', mb_strtolower($judul)),
            fn($w) => mb_strlen(trim($w)) > 3 && !in_array(trim($w), $stopWords)
        );

        $fnWords = [];
        if (!self::isGenericFilename($filename)) {
            $fnWords = array_filter(
                explode(' ', mb_strtolower($filename)),
                fn($w) => mb_strlen($w) > 3
            );
        }

        $y = date('Y');
        $longTail = match ($ctx) {
            'misa'              => ['foto misa ' . mb_strtolower($kategori) . ' paroki tulungagung', 'misa paroki smdtba ' . $y, 'ekaristi gereja smdtba tulungagung'],
            'rosario'           => ['foto doa rosario paroki tulungagung', 'rosario bersama gereja smdtba', 'devosi rosario umat smdtba'],
            'adorasi'           => ['foto adorasi sakramen mahakudus smdtba', 'adorasi eukaristi paroki tulungagung'],
            'jalan_salib'       => ['foto jalan salib paroki tulungagung', 'via crucis gereja smdtba', 'jalan salib prapaskah smdtba'],
            'natal'             => ['foto natal paroki tulungagung', 'perayaan natal gereja smdtba ' . $y, 'misa natal smdtba tulungagung'],
            'keluarga_kudus'    => ['pesta keluarga kudus paroki smdtba', 'misa keluarga kudus tulungagung'],
            'maria_bunda_allah' => ['misa 1 januari paroki smdtba', 'hari raya maria bunda allah tulungagung'],
            'epifani'           => ['misa epifani paroki tulungagung', 'penampakan tuhan gereja smdtba'],
            'pembaptisan_tuhan' => ['hari raya pembaptisan tuhan smdtba', 'misa pembaptisan tuhan tulungagung'],
            'prapaskah'         => ['foto prapaskah paroki smdtba', 'rabu abu tulungagung', 'app aksi puasa pembangunan smdtba'],
            'minggu_palma'      => ['foto minggu palma paroki tulungagung', 'prosesi palma smdtba ' . $y],
            'kamis_putih'       => ['foto kamis putih paroki smdtba', 'tuguran kamis putih tulungagung', 'pembasuhan kaki smdtba'],
            'jumat_agung'       => ['foto jumat agung paroki tulungagung', 'ibadat sengsara smdtba ' . $y, 'penghormatan salib gereja smdtba'],
            'sabtu_suci'        => ['foto vigili paskah paroki tulungagung', 'malam paskah smdtba ' . $y, 'api paskah gereja smdtba'],
            'pekan_suci'        => ['pekan suci gereja smdtba tulungagung', 'triduum paskah paroki smdtba', 'trihari suci tulungagung'],
            'paskah'            => ['foto paskah paroki tulungagung', 'alleluia paskah smdtba ' . $y, 'kebangkitan kristus tulungagung'],
            'kenaikan'          => ['foto kenaikan tuhan paroki tulungagung', 'hari raya kenaikan smdtba'],
            'pentakosta'        => ['foto pentakosta paroki tulungagung', 'roh kudus gereja smdtba ' . $y],
            'corpus_christi'    => ['foto corpus christi paroki tulungagung', 'prosesi sakramen mahakudus smdtba'],
            'hati_yesus'        => ['foto hati yesus paroki smdtba', 'misa pertama jumat tulungagung'],
            'maria_diangkat'    => ['foto maria diangkat smdtba', 'misa 15 agustus paroki tulungagung'],
            'pesta_pelindung'   => ['foto pesta pelindung smdtba 8 desember', 'hari raya santa maria tidak bernoda tulungagung', 'dies natalis paroki smdtba'],
            'kristus_raja'      => ['foto kristus raja paroki tulungagung', 'penutup tahun liturgi smdtba'],
            'adven'             => ['foto adven paroki tulungagung', 'lilin adven gereja smdtba', 'masa penantian natal smdtba'],
            'baptis'            => ['foto pembaptisan paroki smdtba', 'sakramen baptis tulungagung', 'umat baru gereja smdtba'],
            'komuni'            => ['foto komuni pertama paroki tulungagung', 'komuni pertama smdtba'],
            'krisma'            => ['foto krisma paroki tulungagung', 'sakramen penguatan smdtba'],
            'tahbisan'          => ['foto tahbisan imam paroki tulungagung', 'penahbisan klerus smdtba'],
            'pernikahan'        => ['foto pernikahan gereja katolik tulungagung', 'pemberkatan nikah smdtba'],
            'tobat'             => ['foto rekonsiliasi massal smdtba', 'pengakuan dosa paroki tulungagung'],
            'mudika'            => ['foto omk paroki tulungagung', 'orang muda katolik smdtba ' . $y],
            'wkri'              => ['foto wkri paroki tulungagung', 'wanita katolik smdtba'],
            'legio'             => ['foto legio maria paroki tulungagung', 'legio maria smdtba'],
            'koor'              => ['foto paduan suara paroki smdtba', 'koor liturgi gereja smdtba tulungagung'],
            'karismatik'        => ['foto pkk paroki tulungagung', 'karismatik katolik smdtba ' . $y],
            'cursillo'          => ['foto cursillo paroki tulungagung', 'ultreya cursillo smdtba'],
            'lansia'            => ['foto lansia paroki tulungagung', 'komunitas opa oma smdtba'],
            'retret'            => ['foto retret paroki tulungagung', 'rekoleksi umat smdtba'],
            'ziarah'            => ['foto ziarah paroki tulungagung', 'ziarah gua maria dari tulungagung'],
            'bazar'             => ['foto bazar paroki tulungagung', 'pesta bazar gereja smdtba ' . $y],
            'baksos'            => ['foto baksos paroki tulungagung', 'pelayanan sosial gereja smdtba'],
            'donor_darah'       => ['foto donor darah paroki smdtba', 'donor sukarela gereja tulungagung'],
            'kesehatan'         => ['foto baksos kesehatan smdtba', 'pemeriksaan gratis paroki tulungagung'],
            'pelantikan'        => ['foto pelantikan dpp paroki tulungagung', 'pengurus baru smdtba ' . $y],
            'rapat'             => ['sidang paroki smdtba tulungagung', 'rapat dpp gereja smdtba'],
            'pastoral'          => ['foto kunjungan pastoral smdtba', 'visitasi romo paroki tulungagung'],
            'sejarah'           => ['foto sejarah paroki tulungagung', 'historia smdtba tulungagung'],
            'lingkungan'        => ['foto pertemuan lingkungan smdtba', 'ibadat lingkungan paroki tulungagung'],
            'wilayah'           => ['foto pertemuan wilayah smdtba', 'koordinasi wilayah paroki tulungagung'],
            'sekolah_minggu'    => ['foto sekolah minggu paroki smdtba', 'bia gereja smdtba tulungagung'],
            'katekese'          => ['foto katekese paroki tulungagung', 'pendalaman iman smdtba'],
            'kronik'            => ['kronik kegiatan paroki smdtba', 'dokumentasi paroki smdtba ' . $y],
            default             => ['foto kegiatan paroki smdtba tulungagung', 'dokumentasi paroki smdtba ' . $y, 'komunitas gereja smdtba tulungagung'],
        };

        $all = array_unique(array_merge(
            $base,
            $longTail,
            $extra,
            array_values($judulWords),
            array_values($fnWords)
        ));

        // Shuffle deterministik
        $arr = array_values(array_slice($all, 0, 30));
        $r   = $seed;
        for ($i = count($arr) - 1; $i > 0; $i--) {
            $r   = ($r * 1103515245 + 12345) & 0x7fffffff;
            $j   = $r % ($i + 1);
            [$arr[$i], $arr[$j]] = [$arr[$j], $arr[$i]];
        }

        return array_values(array_slice($arr, 0, 12));
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  UTILITIES
    // ══════════════════════════════════════════════════════════════════════════

    private static function cleanFilename(string $src): string
    {
        $f = pathinfo($src, PATHINFO_FILENAME);
        $f = mb_strtolower($f);
        $f = str_replace(['-', '_', '.'], ' ', $f);
        $f = preg_replace('/\b\d{4,}\b/', '', $f);
        $f = preg_replace('/\b\d{1,3}\b/', '', $f);

        $stopWords = ['img','image','foto','photo','pic','pict','dsc','dscf','tmp','temp',
                      'file','new','copy','final','rev','v1','v2','v3','banner','thumb',
                      'resized','compressed','small','large','medium','web','upload',
                      'asset','bg','background','header','cover'];
        $words = array_filter(
            explode(' ', $f),
            fn($w) => mb_strlen(trim($w)) > 1 && !in_array(trim($w), $stopWords)
        );
        $f = trim(preg_replace('/\s+/', ' ', implode(' ', $words)));
        return mb_strlen($f) > 2 ? ucwords($f) : '';
    }

    private static function isGenericFilename(string $filename): bool
    {
        $generic = ['','img','image','foto','photo','pic','dsc','screenshot',
                    'p','pict','file','asset','upload','tmp'];
        return mb_strlen($filename) < 4 || in_array(mb_strtolower($filename), $generic, true);
    }

    private static function shortTitle(string $judul, int $maxChars = 35): string
    {
        if (mb_strlen($judul) <= $maxChars) return $judul;
        $cut       = mb_substr($judul, 0, $maxChars);
        $lastSpace = mb_strrpos($cut, ' ');
        return $lastSpace > 10 ? mb_substr($cut, 0, $lastSpace) : $cut;
    }

    private static function menuLabel(string $menu, string $fallback): string
    {
        return match ($menu) {
            'berita'   => 'Berita Paroki',
            'kronik'   => 'Kronik Kegiatan',
            'historia' => 'Historia Gereja',
            default    => $fallback,
        };
    }

    private static function pick(array $pool, int $seed): string
    {
        if (empty($pool)) return '';
        return $pool[$seed % count($pool)];
    }

    private static function fillPlaceholders(string $tpl, array $ph): string
    {
        return strtr($tpl, $ph);
    }

    private static function limit(string $text, int $max): string
    {
        $text = trim(preg_replace('/\s+/', ' ', strip_tags($text)));
        return mb_strlen($text) > $max ? mb_substr($text, 0, $max - 1) . '…' : $text;
    }

    private static function emptyResult(array $artData = []): array
    {
        $kategori = $artData['kategori'] ?? 'Paroki';
        $judul    = $artData['judul']    ?? 'Paroki SMDTBA Tulungagung';
        return [
            'alt'         => "Dokumentasi kegiatan {$kategori} Paroki SMDTBA Tulungagung",
            'caption'     => "Kegiatan {$kategori} umat Paroki Santa Maria Dengan Tidak Bernoda Asal Tulungagung, Keuskupan Surabaya.",
            'title'       => "Paroki SMDTBA Tulungagung",
            'description' => "Foto kegiatan {$judul} — Paroki Santa Maria Dengan Tidak Bernoda Asal (SMDTBA) Tulungagung, Jawa Timur. Keuskupan Surabaya.",
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
            'artikel_judul'      => mb_substr($artData['judul'] ?? '', 0, 255),
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

    private static function cacheGet(string $key): ?array
    {
        $file = sys_get_temp_dir() . '/paroki_' . md5($key) . '.cache';
        if (!file_exists($file)) return null;
        if ((time() - filemtime($file)) > self::CACHE_TTL) return null;
        $raw  = @file_get_contents($file);
        if (!$raw) return null;
        $data = @unserialize($raw);
        return is_array($data) ? $data : null;
    }

    private static function cacheSet(string $key, array $data): void
    {
        $file = sys_get_temp_dir() . '/paroki_' . md5($key) . '.cache';
        @file_put_contents($file, serialize($data), LOCK_EX);
    }
}