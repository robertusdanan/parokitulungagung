<?php

/**
 * ═══════════════════════════════════════════════════════════════════════════════
 * seo-keywords.php
 * ───────────────────────────────────────────────────────────────────────────────
 * Master keyword pool LENGKAP untuk Paroki Santa Maria Dengan Tidak Bernoda Asal
 * (SMDTBA), Tulungagung, Jawa Timur — Keuskupan Surabaya.
 *
 * Tidak ada AI. Tidak ada API eksternal. Semua keyword dikurasi secara manual
 * dengan mempertimbangkan: volume pencarian lokal, relevansi topik, variasi
 * long-tail, pertanyaan pengguna, dan kata-kata yang disukai Google.
 *
 * Digunakan oleh:
 *   - build_page_keywords()       → meta keywords halaman artikel
 *   - get_image_keyword_context() → konteks keyword untuk ImageSeoGenerator
 *   - get_keywords_for_context()  → keyword per konteks spesifik
 *   - get_extended_keywords()     → keyword extended per konteks
 * ═══════════════════════════════════════════════════════════════════════════════
 */

// ──────────────────────────────────────────────────────────────────────────────
// POOL UTAMA
// ──────────────────────────────────────────────────────────────────────────────

function get_seo_keywords_pool(): array
{
    static $pool = null;
    if ($pool !== null) return $pool;

    $pool = [

        // ── 1. IDENTITAS INTI PAROKI ──────────────────────────────────────────
        'identitas' => [
            'gereja katolik tulungagung',
            'paroki smdtba',
            'paroki tulungagung',
            'paroki kabupaten tulungagung',
            'santa maria dengan tidak bernoda asal tulungagung',
            'smdtba tulungagung',
            'gereja paroki tulungagung',
            'umat katolik tulungagung',
            'katolik tulungagung',
            'gereja smdtba',
            'paroki santa maria tulungagung',
            'paroki smdtba tulungagung',
            'gereja santa maria tulungagung',
            'parokitulungagung',
            'parokitulungagung.org',
            'gereja tulungagung',
            'kristiani tulungagung',
            'komunitas katolik tulungagung',
            'umat kristiani tulungagung',
            'gereja katolik di kabupaten tulungagung',
            'paroki lokal tulungagung',
            'kehidupan iman tulungagung',
            'iman katolik tulungagung',
            'website paroki smdtba',
            'informasi paroki tulungagung',
            'identitas paroki smdtba',
            'gereja tua tulungagung',
            'komunitas iman tulungagung',
            'gereja dengan tidak bernoda',
            'paroki aktif tulungagung',
            'umat gereja smdtba',
            'sejarah paroki smdtba',
            'paroki jawa timur',
            'keuskupan surabaya',
            'paroki keuskupan surabaya',
            'gereja keuskupan surabaya tulungagung',
        ],

        // ── 2. LOKASI & GEOGRAFI ──────────────────────────────────────────────
        'lokasi' => [
            'tulungagung',
            'kabupaten tulungagung',
            'jawa timur',
            'jatim',
            'kota tulungagung',
            'gereja di tulungagung',
            'katolik jawa timur',
            'gereja jawa timur',
            'indonesia',
            'jawa',
            'keuskupan surabaya',
            'dioses surabaya',
            'wilayah tulungagung',
            'kecamatan tulungagung',
            'lingkungan paroki',
            'umat setempat',
            'masyarakat tulungagung',
            'kota marmer',
            'tulungagung kota',
            'gereja lokal',
            'komunitas lokal',
            'paroki di jawa timur',
            'kegiatan gereja tulungagung',
        ],

        // ── 3. LITURGI & PERAYAAN EKARISTI ────────────────────────────────────
        'liturgi' => [
            'misa',
            'misa harian',
            'misa minggu',
            'misa kudus',
            'misa pagi',
            'misa sore',
            'misa malam',
            'misa hari biasa',
            'perayaan ekaristi',
            'ekaristi',
            'liturgi',
            'liturgi gereja',
            'liturgi katolik',
            'ibadat',
            'ibadat sabda',
            'ibadat lingkungan',
            'ibadat wilayah',
            'ibadat tobat',
            'doa rosario',
            'novena',
            'novena tiga hari',
            'adorasi sakramen mahakudus',
            'adorasi',
            'benediksi',
            'jalan salib',
            'via crucis',
            'litani',
            'meditasi',
            'meditasi kristiani',
            'doa malam',
            'doa bersama',
            'koor',
            'paduan suara gereja',
            'paduan suara liturgi',
            'lagu gereja',
            'lagu liturgi',
            'lagu pujian',
            'buku misa',
            'injil',
            'bacaan injil',
            'bacaan pertama',
            'bacaan kedua',
            'homili',
            'khotbah',
            'renungan harian',
            'renungan minggu',
            'refleksi harian',
            'refleksi rohani',
            'ayat kitab suci',
            'mazmur',
            'katekese',
            'pembinaan iman',
            'pendalaman iman',
            'iman katolik',
            'ajaran gereja',
            'katekismus',
            'sabda tuhan',
            'firman tuhan',
            'peringatan orang kudus',
            'misa peringatan',
            'misa arwah',
            'misa requiem',
            'imam selebran',
            'diakon',
            'lektor',
            'pemazmur',
            'prodiakon',
            'akolit',
            'misdinar',
            'putera altar',
            'tata perayaan ekaristi',
            'ritus komuni',
            'doa umat',
            'syahadat',
            'prefasi',
            'konsekrasi',
            'bapa kami',
            'salam damai',
            'roti dan anggur',
            'hosti',
            'piala kudus',
            'altar',
            'sakristi',
            'kalender liturgi',
            'tahun liturgi',
            'bacaan liturgi',
            'leksionari',
            'ordo misa',
            'tata liturgi',
            'tim liturgi',
            'koordinator liturgi',
            'musik gereja',
            'organis gereja',
            'lagu paskah',
            'lagu natal',
            'nyanyian gereja',
            'antifon',
            'responsori',
        ],

        // ── 4. SAKRAMEN ───────────────────────────────────────────────────────
        'sakramen' => [
            'baptis',
            'pembaptisan',
            'baptis bayi',
            'baptis dewasa',
            'baptis anak',
            'mandi baptis',
            'komuni pertama',
            'komuni kudus',
            'penerimaan komuni pertama',
            'komuni suci',
            'krisma',
            'sakramen penguatan',
            'penguatan',
            'krisma dewasa',
            'tobat',
            'pengakuan dosa',
            'rekonsiliasi',
            'pengampunan dosa',
            'bilik tobat',
            'perkawinan',
            'pernikahan gereja',
            'pemberkatan pernikahan',
            'pernikahan kudus',
            'misa pernikahan',
            'pernikahan sakramental',
            'pengurapan orang sakit',
            'sakramen orang sakit',
            'viatikum',
            'tahbisan',
            'sakramen imamat',
            'tahbisan imam',
            'tahbisan diakon',
            'sakramen gereja',
            'penerimaan sakramen',
            'RCIA',
            'calon baptis',
            'katekumen',
            'baptisan kudus',
            'tujuh sakramen',
            'persiapan baptis',
            'persiapan komuni pertama',
            'persiapan krisma',
            'kelas pernikahan',
            'bimbingan pranikah',
            'perayaan sakramen',
        ],

        // ── 5. KALENDER LITURGI & HARI RAYA ──────────────────────────────────
        // Pool umum — tiap hari raya punya pool spesifiknya sendiri di bawah.
        'kalender' => [
            // Adven
            'masa adven',
            'adven',
            'penantian kristus',
            'persiapan natal',
            // Natal & sesudahnya
            'natal',
            'perayaan natal',
            'malam natal',
            'kelahiran yesus',
            'pesta keluarga kudus',
            'maria bunda allah',
            'epifani',
            'penampakan tuhan',
            'pembaptisan tuhan',
            // Prapaskah
            'prapaskah',
            'masa prapaskah',
            'rabu abu',
            'puasa dan pantang',
            // Pekan Suci
            'pekan suci',
            'minggu palma',
            'kamis putih',
            'jumat agung',
            'sabtu suci',
            'vigili paskah',
            'triduum paskah',
            // Masa Paskah
            'paskah',
            'hari raya paskah',
            'kebangkitan kristus',
            'masa paskah',
            'kenaikan tuhan',
            'pentakosta',
            // Masa Biasa
            'corpus christi',
            'tubuh dan darah kristus',
            'hati yesus mahakudus',
            'kristus raja semesta alam',
            // Maria
            'maria diangkat ke surga',
            'santa maria dengan tidak bernoda asal',
            // Umum
            'hari orang kudus',
            'hari arwah',
            'bulan rosario',
            'bulan november arwah',
            'hari raya gereja',
            'solemnitas',
            'masa biasa',
            'tahun liturgi',
            'tahun A',
            'tahun B',
            'tahun C',
            'hari jadi paroki',
            'ulang tahun paroki',
            'pesta pelindung paroki',
            'pesta nama paroki',
            'peringatan wajib',
            'pesta',
            'hari kaum muda sedunia',
            'hari misi sedunia',
        ],

        // ── 6. KEPENGURUSAN & STRUKTUR PAROKI ────────────────────────────────
        'kepengurusan' => [
            'dewan pastoral paroki',
            'dpp paroki',
            'dpp',
            'pengurus paroki',
            'pastor',
            'romo',
            'pastor paroki',
            'pastor kepala',
            'pastor pembantu',
            'imam',
            'diakon',
            'diakon tetap',
            'bruder',
            'suster',
            'frater',
            'klerus',
            'pemimpin umat',
            'koordinator',
            'koordinator wilayah',
            'panitia',
            'sekretaris paroki',
            'bendahara paroki',
            'ketua lingkungan',
            'ketua wilayah',
            'perwakilan umat',
            'dewan paroki',
            'komisi paroki',
            'bidang pastoral',
            'pengurus lingkungan',
            'staf pastoral',
            'pengurus dpp',
            'tim pastoral',
            'tim liturgi',
            'tim musik',
            'tim katekese',
            'tim sosial',
            'seksi',
            'ketua dewan paroki',
            'ketua bidang',
            'keluarga besar smdtba',
        ],

        // ── 7. KATEGORIAL & KELOMPOK UMAT ─────────────────────────────────────
        'kategorial' => [
            'kategorial',
            'kelompok kategorial',
            'mudika',
            'orang muda katolik',
            'omk',
            'pemuda katolik',
            'wanita katolik',
            'wkri',
            'wanita katolik ri',
            'pria katolik',
            'kelompok pria',
            'lansia katolik',
            'lanjut usia',
            'kelompok lansia',
            'keluarga katolik',
            'karismatik',
            'pembaruan karismatik katolik',
            'pkk',
            'cursillo',
            'legio maria',
            'legio',
            'kelompok doa',
            'persekutuan doa',
            'bible sharing',
            'sharing kitab suci',
            'bina iman anak',
            'bia',
            'bina iman remaja',
            'bir',
            'anak remaja paroki',
            'pramuka katolik',
            'kelompok lektor',
            'prodiakon sukarela',
            'tim pemazmur',
            'choir',
            'kelompok koor',
            'kelompok misdinar',
            'putera putri altar',
        ],

        // ── 8. KEGIATAN PAROKI ─────────────────────────────────────────────────
        'kegiatan' => [
            'retret',
            'retret paroki',
            'retret umat',
            'retret tahunan',
            'rekoleksi',
            'rekoleksi umat',
            'rekoleksi wilayah',
            'rekoleksi lingkungan',
            'ziarah',
            'ziarah umat',
            'ziarah rohani',
            'ziarah ke sendangsono',
            'ziarah ke gua maria',
            'perjalanan rohani',
            'live in',
            'live in mudika',
            'gathering',
            'gathering umat',
            'gathering paroki',
            'pesta paroki',
            'perayaan paroki',
            'bazar',
            'bazar paroki',
            'lomba',
            'festival',
            'pentas seni',
            'pawai',
            'karnaval',
            'jalan sehat',
            'bakti sosial',
            'baksos',
            'donor darah',
            'donor darah paroki',
            'kunjungan sosial',
            'santunan',
            'santunan anak yatim',
            'pembagian sembako',
            'kerja bakti',
            'gotong royong',
            'renovasi gereja',
            'kegiatan lingkungan',
            'kegiatan wilayah',
            'agenda paroki',
            'jadwal kegiatan paroki',
            'pelantikan',
            'pelantikan pengurus',
            'pelantikan dpp',
            'serah terima jabatan',
            'rapat paroki',
            'sidang paroki',
            'evaluasi kegiatan',
            'program tahunan paroki',
            'malam keakraban',
        ],

        // ── 9. SOSIAL & KEMASYARAKATAN ────────────────────────────────────────
        'sosial' => [
            'umat',
            'jemaat',
            'komunitas',
            'lingkungan',
            'wilayah',
            'stasi',
            'kapel',
            'pos pelayanan',
            'masyarakat',
            'toleransi',
            'dialog antar agama',
            'ekumene',
            'kerukunan',
            'kerukunan umat beragama',
            'kebersamaan',
            'gotong royong',
            'solidaritas',
            'persaudaraan',
            'cinta kasih',
            'pelayanan sosial',
            'kepedulian',
            'orang miskin',
            'orang sakit',
            'lansia',
            'anak yatim',
            'duafa',
            'pendampingan',
            'pelayanan pastoral',
            'keadilan',
            'perdamaian',
            'kemanusiaan',
            'keadilan sosial',
            'bantuan bencana',
            'caritas',
            'karitatif',
            'pelayanan karitatif',
        ],

        // ── 10. SEJARAH & WARISAN ─────────────────────────────────────────────
        'sejarah' => [
            'sejarah paroki',
            'historia gereja',
            'historia paroki',
            'asal usul paroki',
            'berdirinya paroki',
            'pendiri paroki',
            'misionaris',
            'misionaris serikat yesus',
            'zaman kolonial',
            'gereja tua',
            'arsitektur gereja',
            'bangunan bersejarah',
            'cagar budaya',
            'warisan budaya',
            'tradisi',
            'inkulturasi',
            'gereja lokal',
            'kronologi paroki',
            'jejak paroki',
            'perjalanan paroki',
            'arsip paroki',
            'foto lama paroki',
            'tahun berdiri',
            'yubileum',
            'yubileum perak',
            'yubileum emas',
            'yubileum berlian',
            'perjalanan iman',
            'iman turun temurun',
            'generasi iman',
        ],

        // ── 11. PENDIDIKAN & PEMBINAAN ────────────────────────────────────────
        'pendidikan' => [
            'sekolah minggu',
            'sekolah minggu paroki',
            'pendidikan iman anak',
            'pembinaan iman anak',
            'bina iman',
            'katekese anak',
            'katekese orang dewasa',
            'persiapan sakramen',
            'pelajaran agama',
            'pelajaran agama katolik',
            'pendidikan agama katolik',
            'sekolah katolik',
            'yayasan pendidikan',
            'beasiswa paroki',
            'bantuan pendidikan',
            'kelas persiapan',
            'seminar iman',
            'webinar paroki',
            'pelatihan kader',
            'formasi rohani',
            'kursus kitab suci',
            'kursus evangelisasi',
            'evangelisasi',
            'pewartaan',
        ],

        // ── 12. DIGITAL & MEDIA ───────────────────────────────────────────────
        'digital' => [
            'website paroki',
            'web paroki smdtba',
            'informasi online paroki',
            'berita online paroki',
            'media paroki',
            'media sosial paroki',
            'instagram paroki',
            'youtube paroki',
            'live streaming misa',
            'misa online',
            'warta paroki online',
            'jadwal misa online',
            'informasi terkini paroki',
            'pengumuman digital',
            'konten rohani',
            'podcast paroki',
            'newsletter paroki',
            'grup whatsapp paroki',
        ],

        // ── 13. KATEGORI MENU ARTIKEL ─────────────────────────────────────────
        'berita' => [
            'berita paroki terbaru',
            'kabar paroki',
            'liputan paroki',
            'informasi terkini paroki',
            'update paroki',
            'warta terbaru',
            'pengumuman terbaru',
            'kabar terkini gereja',
            'berita gereja tulungagung',
            'info paroki hari ini',
            'perkembangan paroki',
            'kilas paroki',
            'berita umat',
            'liputan kegiatan paroki',
            'reportase paroki',
            'warta gereja',
        ],

        'kronik' => [
            'kronik kegiatan',
            'catatan perjalanan',
            'laporan kegiatan paroki',
            'dokumentasi kegiatan paroki',
            'peristiwa paroki',
            'momen paroki',
            'kenangan paroki',
            'rekam jejak',
            'ulasan kegiatan paroki',
            'reportase paroki',
            'kronik smdtba',
            'album kegiatan',
            'jejak kegiatan',
            'catatan tahunan',
            'catatan bulanan paroki',
        ],

        'historia' => [
            'historia gereja katolik tulungagung',
            'sejarah lengkap paroki',
            'asal usul gereja',
            'kronologi gereja',
            'arsip paroki',
            'dokumen sejarah paroki',
            'heritage gereja',
            'warisan iman',
            'sejarah smdtba',
            'historia smdtba tulungagung',
            'kisah pendirian paroki',
            'tanggal berdiri paroki',
            'misi awal paroki',
            'misionaris pertama',
        ],

        // ── 14. LITURGI SPESIFIK: NATAL ───────────────────────────────────────
        'natal' => [
            'perayaan natal',
            'misa malam natal',
            'misa natal',
            'misa subuh natal',
            'misa siang natal',
            'dekorasi natal gereja',
            'natal di paroki',
            'natal paroki smdtba',
            'pesta natal',
            'natal bersama umat',
            'lagu natal',
            'nyanyian natal',
            'drama natal',
            'puisi natal',
            'kreche natal',
            'kandang natal',
            'pohon natal',
            'selamat natal',
            'natal paroki tulungagung',
            'sukacita natal',
            'momen natal paroki',
            'natalan paroki',
            'kebersamaan natal',
            'berbagi di natal',
            'kelahiran yesus kristus',
            'malam kudus',
            'silent night',
            'sukacita kelahiran tuhan',
            'noel',
            'desember',
        ],

        // ── 14b. PESTA KELUARGA KUDUS ─────────────────────────────────────────
        // Minggu setelah Natal (atau 30 Desember jika Natal hari Minggu)
        'keluarga_kudus' => [
            'pesta keluarga kudus',
            'pesta keluarga kudus nazaret',
            'yesus maria yusuf',
            'keluarga nazaret',
            'teladan keluarga kristiani',
            'minggu setelah natal',
            'hari raya keluarga kudus',
            'misa keluarga kudus',
            'pastoral keluarga natal',
            'keluarga sebagai gereja kecil',
            'keluarga beriman',
            'keluarga katolik tulungagung',
            'rumah tangga beriman',
            'iman dalam keluarga',
            'pesta keluarga paroki smdtba',
        ],

        // ── 14c. MARIA BUNDA ALLAH — 1 Januari ───────────────────────────────
        'maria_bunda_allah' => [
            'hari raya maria bunda allah',
            'santa perawan maria bunda allah',
            'solemnitas maria',
            '1 januari',
            'tahun baru dalam iman',
            'perayaan tahun baru gereja',
            'misa 1 januari',
            'misa tahun baru katolik',
            'theotokos',
            'bunda allah',
            'maria bunda kristus',
            'doa awal tahun',
            'hari perdamaian sedunia',
            'hari raya awal tahun',
            'tahun baru bersama maria',
            'paroki smdtba 1 januari',
        ],

        // ── 14d. EPIFANI — 6 Januari ──────────────────────────────────────────
        'epifani' => [
            'hari raya penampakan tuhan',
            'epifani',
            'tiga raja',
            'orang majus dari timur',
            'bintang betlehem',
            '6 januari',
            'misa epifani',
            'misa penampakan tuhan',
            'emas kemenyan mur',
            'mengikuti bintang',
            'raja raja dari timur',
            'kaspar melkior baltasar',
            'penutup masa natal liturgi',
            'epifani paroki smdtba',
            'penampakan kristus kepada bangsa',
            'cahaya bagi segala bangsa',
        ],

        // ── 14e. PEMBAPTISAN TUHAN ────────────────────────────────────────────
        // Minggu setelah Epifani — penutup resmi Masa Natal
        'pembaptisan_tuhan' => [
            'hari raya pembaptisan tuhan',
            'yesus dibaptis di sungai yordan',
            'pembaptisan yesus',
            'roh kudus seperti merpati',
            'inilah puteraku yang terkasih',
            'yohanes pembaptis',
            'sungai yordan',
            'penutup masa natal',
            'misa pembaptisan tuhan',
            'awal masa biasa',
            'pembaptisan kristus',
            'air baptis sungai yordan',
            'suara bapa dari surga',
            'pembaptisan tuhan paroki smdtba',
        ],

        // ── 15. PRAPASKAH ─────────────────────────────────────────────────────
        // Rabu Abu hingga Sabtu sebelum Minggu Palma (tidak termasuk Pekan Suci)
        'prapaskah' => [
            'masa prapaskah',
            'rabu abu',
            'penerimaan abu',
            'abu di dahi',
            'ingatlah engkau adalah debu',
            'bertobatlah dan percayalah pada injil',
            'pertobatan',
            'puasa',
            'pantang',
            'pantang daging',
            'puasa prapaskah',
            'aksi puasa pembangunan',
            'app',
            'kolekte app',
            'aksi karitatif prapaskah',
            'pertobatan bersama',
            'pengakuan massal prapaskah',
            'jalan salib jumat prapaskah',
            'jalan salib rutin',
            'masa pertobatan',
            'merenungkan sengsara',
            'persiapan paskah',
            'minggu I prapaskah',
            'minggu II prapaskah',
            'minggu III prapaskah',
            'minggu IV prapaskah',
            'minggu V prapaskah',
            'warna ungu liturgi',
            'berpuasa berpantang',
            'tobat pribadi dan komunal',
            'pertobatan hati',
            'doa puasa amal',
            'prapaskah paroki smdtba',
            'perjalanan menuju paskah',
        ],

        // ── 15b. MINGGU PALMA ─────────────────────────────────────────────────
        // Hari pertama Pekan Suci
        'minggu_palma' => [
            'minggu palma',
            'minggu suci',
            'prosesi palma',
            'daun palma',
            'umat membawa daun palma',
            'perarakan masuk yerusalem',
            'hosana putera daud',
            'yesus masuk yerusalem',
            'pembukaan pekan suci',
            'bacaan sengsara panjang',
            'sengsara menurut matius',
            'sengsara menurut markus',
            'sengsara menurut lukas',
            'minggu palma paroki smdtba',
            'pemberkatan palma',
            'prosesi luar gereja',
            'keledai putih',
            'pakaian dihamparkan',
            'pawai palma',
            'perarakan masuk gereja',
            'minggu palma tulungagung',
        ],

        // ── 15c. KAMIS PUTIH ──────────────────────────────────────────────────
        // Misa In Caena Domini — Perjamuan Terakhir
        'kamis_putih' => [
            'kamis putih',
            'misa in caena domini',
            'perjamuan malam terakhir',
            'pembasuhan kaki',
            'mandatum',
            'romo mencuci kaki umat',
            'yesus membasuh kaki para rasul',
            'tuguran',
            'tuguran paskah',
            'jaga malam',
            'taman getsemani',
            'doa di taman getsemani',
            'tabernakulum dikosongkan',
            'altar dilucuti',
            'altar tanpa hiasan',
            'pemindahan sakramen mahakudus',
            'misa krisma keuskupan',
            'kamis putih paroki smdtba',
            'perjamuan ekaristi pertama',
            'lembaga ekaristi',
            'lembaga imamat',
            'malam perjamuan tuhan',
            'roti tubuhku',
            'anggur darahku',
            'lakukan ini untuk mengenang aku',
            'komunitas berjaga bersama tuhan',
        ],

        // ── 15d. JUMAT AGUNG ──────────────────────────────────────────────────
        // Ibadat Sengsara — bukan misa
        'jumat_agung' => [
            'jumat agung',
            'ibadat sengsara tuhan',
            'ibadat jumat agung',
            'bukan misa jumat agung',
            'penghormatan salib',
            'salib diciumi umat',
            'prosesi salib',
            'bacaan sengsara menurut yohanes',
            'yesus wafat di salib',
            'gereja hening jumat agung',
            'tidak ada lonceng',
            'via crucis agung',
            'jalan salib jumat agung',
            'warna merah liturgi',
            'komuni dari tabernakulum',
            'tidak ada konsekrasi',
            'kolekte tanah suci',
            'gereja berkabung',
            'tirai altar terurai',
            'keheningan jumat agung',
            'sengsara dan wafat kristus',
            'yesus memanggul salib',
            'jumat agung paroki smdtba',
            'jumat agung tulungagung',
            'berjaga dalam wafat kristus',
            'salib lambang cinta tuhan',
        ],

        // ── 15e. SABTU SUCI & VIGILI PASKAH ──────────────────────────────────
        // Sabtu siang = hening total, malam = Vigili Paskah
        'sabtu_suci' => [
            'sabtu suci',
            'keheningan sabtu suci',
            'kristus di dalam kubur',
            'menunggu kebangkitan',
            'tidak ada misa siang sabtu suci',
            'hari terdiam gereja',
            'hening antara wafat dan kebangkitan',
            'vigili paskah',
            'misa vigili paskah',
            'malam paskah',
            'misa terpanjang tahun',
            'api paskah',
            'exsultet',
            'nyanyian pujian paskah',
            'lilin paskah dinyalakan',
            'cahaya kristus',
            'lumen christi',
            'liturgi sabda vigili',
            'tujuh bacaan vigili paskah',
            'liturgi baptis vigili',
            'pembaruan janji baptis',
            'baptis baru vigili',
            'percikan air baptis',
            'liturgi ekaristi paskah',
            'alleluia pertama setelah prapaskah',
            'alleluia bergema kembali',
            'sabtu suci paroki smdtba',
            'vigili paskah tulungagung',
            'malam terbesar iman kristiani',
            'kegelapan menjadi terang',
            'api kecil membelah malam',
        ],

        // ── 16. PASKAH (Masa Paskah) ──────────────────────────────────────────
        // Minggu Paskah hingga sebelum Kenaikan
        'paskah' => [
            'perayaan paskah',
            'minggu paskah',
            'hari raya paskah',
            'kristus bangkit',
            'kebangkitan kristus',
            'surrexit dominus vere',
            'ia sungguh bangkit',
            'alleluia',
            'misa paskah',
            'paskah paroki smdtba',
            'pekan paskah',
            'oktaf paskah',
            'minggu kerahiman ilahi',
            'kerahiman ilahi',
            'novena kerahiman ilahi',
            'koronka',
            'paskah II sampai VII',
            'masa paskah lima puluh hari',
            'sukacita paskah',
            'api paskah',
            'lilin paskah',
            'paskah bersama',
            'paskah tulungagung',
            'paskah umat paroki',
            'paskah di gereja smdtba',
            'perayaan kebangkitan',
            'gereja merayakan paskah',
            'telur paskah',
            'anak paskah',
            'prosesi paskah',
        ],

        // ── 17. ADVEN ────────────────────────────────────────────────────────
        'adven' => [
            'masa adven',
            'lilin adven',
            'lingkaran adven',
            'persiapan natal',
            'penantian',
            'penantian kristus',
            'adven paroki',
            'adven keluarga',
            'retret adven',
            'perayaan adven',
            'doa adven',
            'kalender adven',
            'permenungan adven',
            'minggu adven pertama',
            'minggu adven kedua',
            'minggu adven ketiga',
            'minggu gaudete',
            'lilin merah muda adven',
            'minggu adven keempat',
            'empat lilin adven',
            'nantikanlah tuhan',
            'siapkanlah jalan tuhan',
            'adven paroki smdtba',
            'kerinduan akan kristus',
            'harapan adven',
        ],

        // ── 18. KENAIKAN TUHAN ────────────────────────────────────────────────
        'kenaikan' => [
            'kenaikan tuhan',
            'hari raya kenaikan',
            'kenaikan yesus kristus',
            'kenaikan ke surga',
            'perayaan kenaikan',
            'misa kenaikan',
            'liturgi kenaikan',
            'kenaikan tulungagung',
            'hari raya kenaikan smdtba',
            'yesus naik ke surga',
            'pergi untuk menyiapkan tempat',
            'aku menyertai kamu sampai akhir zaman',
            'murid menyaksikan kenaikan',
            'pewartaan injil ke seluruh dunia',
            'kenaikan dan misi gereja',
            'paroki smdtba merayakan kenaikan',
            'empat puluh hari setelah paskah',
        ],

        // ── 19. PENTAKOSTA ────────────────────────────────────────────────────
        'pentakosta' => [
            'pentakosta',
            'hari raya pentakosta',
            'turunnya roh kudus',
            'roh kudus',
            'api roh kudus',
            'lahirnya gereja',
            'pentakosta paroki',
            'misa pentakosta',
            'warna merah pentakosta',
            'karunia roh kudus',
            'lima puluh hari paskah',
            'penutup masa paskah',
            'lidah api',
            'berbicara dalam berbagai bahasa',
            'petrus berkhotbah',
            'kelahiran gereja perdana',
            'roh penghibur',
            'roh kebenaran',
            'pentakosta paroki smdtba',
            'pentakosta tulungagung',
        ],

        // ── 20. CORPUS CHRISTI — Tubuh dan Darah Kristus ─────────────────────
        // Kamis setelah Minggu Tritunggal / Minggu setelah Tritunggal (sering di Indonesia)
        'corpus_christi' => [
            'corpus christi',
            'hari raya tubuh dan darah kristus',
            'tubuh dan darah kristus',
            'solemnitas corpus christi',
            'prosesi sakramen mahakudus',
            'prosesi corpus christi',
            'monstrans dibawa dalam prosesi',
            'adorasi agung',
            'pesta ekaristi',
            'misa corpus christi',
            'prosesi di luar gereja',
            'umat mengiringi sakramen',
            'corpus christi paroki smdtba',
            'corpus christi tulungagung',
            'ekaristi pusat iman',
            'kehadiran nyata kristus',
            'misa hari raya ekaristi',
        ],

        // ── 21. HATI YESUS MAHAKUDUS ─────────────────────────────────────────
        // Jumat ketiga setelah Pentakosta
        'hati_yesus' => [
            'hari raya hati yesus mahakudus',
            'devosi hati kudus yesus',
            'hati yesus terbuka',
            'pertama jumat',
            'misa pertama jumat',
            'devosi pertama jumat',
            'novena hati kudus',
            'cinta kasih yang tak terbatas',
            'hati yesus sumber kehidupan',
            'litani hati kudus',
            'misa jumat pertama paroki',
            'hati yesus paroki smdtba',
            'devosi bulanan hati kudus',
            'konsekrasi kepada hati yesus',
        ],

        // ── 22. MARIA DIANGKAT KE SURGA — 15 Agustus ─────────────────────────
        'maria_diangkat' => [
            'maria diangkat ke surga',
            'hari raya maria diangkat ke surga',
            'assumption of mary',
            '15 agustus',
            'misa maria diangkat ke surga',
            'perayaan maria agustus',
            'bunda maria ke surga',
            'hari raya bunda maria',
            'solemnitas maria agustus',
            'devosi maria agustus',
            'pesta maria terbesar',
            'maria diangkat seutuhnya',
            'maria paroki smdtba',
            'agustus bulan maria',
            'perayaan maria tulungagung',
        ],

        // ── 23. PESTA PELINDUNG PAROKI — 8 Desember ──────────────────────────
        // Santa Maria Dengan Tidak Bernoda Asal — Pelindung Utama Paroki SMDTBA
        'pesta_pelindung' => [
            'pesta pelindung paroki smdtba',
            'pesta nama paroki',
            'santa maria dengan tidak bernoda asal',
            'immaculate conception',
            '8 desember',
            'hari raya santa maria tidak bernoda',
            'hari raya pelindung paroki',
            'hari jadi paroki smdtba',
            'perayaan santa maria',
            'patrona paroki tulungagung',
            'misa pesta pelindung',
            'festival pesta pelindung',
            'dies natalis paroki',
            'ulang tahun pelindung',
            'bunda tanpa noda',
            'tak bernoda sejak asal',
            'dogma tidak bernoda',
            'devosi immaculate conception',
            'pesta besar paroki smdtba tulungagung',
        ],

        // ── 24. KRISTUS RAJA SEMESTA ALAM ────────────────────────────────────
        // Minggu terakhir Masa Biasa — penutup Tahun Liturgi
        'kristus_raja' => [
            'hari raya kristus raja semesta alam',
            'kristus raja',
            'minggu terakhir masa biasa',
            'penutup tahun liturgi',
            'solemnitas kristus raja',
            'misa kristus raja',
            'kerajaan allah',
            'raja segala raja',
            'yesus raja alam semesta',
            'kristus raja paroki smdtba',
            'kristus raja tulungagung',
            'menyerahkan kerajaan kepada bapa',
            'tahun liturgi berakhir',
            'akhir tahun gerejawi',
        ],

        // ── 25. SAKRAMEN SPESIFIK: BAPTIS ─────────────────────────────────────
        'baptis' => [
            'pembaptisan umat baru',
            'misa baptis',
            'baptis massal',
            'baptis di paroki tulungagung',
            'baptis bayi smdtba',
            'pembaptisan kristiani',
            'air baptis',
            'kolam baptis',
            'sakramen baptis',
            'nama baptis',
            'sponsor baptis',
            'wali baptis',
            'calon baptis dewasa',
            'katekumen baptis',
            'pembekalan baptis',
            'kelas calon baptis',
            'momen pembaptisan',
            'baptis pertama',
            'baptis paskah',
            'masuk gereja',
        ],

        // ── 26. SAKRAMEN SPESIFIK: PERNIKAHAN ─────────────────────────────────
        'pernikahan' => [
            'pernikahan katolik',
            'misa pemberkatan nikah',
            'pesta perkawinan',
            'cincin nikah',
            'janji pernikahan',
            'prosesi pernikahan',
            'foto pernikahan gereja',
            'pengantin baru',
            'pasangan baru',
            'pasangan katolik',
            'bimbingan pranikah',
            'kursus pranikah',
            'kpn paroki',
            'penyerahan bunga',
            'penyerahan lilin',
            'dispensasi pernikahan',
            'menikah di gereja smdtba',
            'menikah di paroki tulungagung',
            'pernikahan di gereja tulungagung',
        ],

        // ── 27. KEGIATAN MUDA-MUDI (MUDIKA/OMK) ──────────────────────────────
        'mudika' => [
            'mudika paroki',
            'omk tulungagung',
            'orang muda katolik',
            'pemuda gereja',
            'generasi muda paroki',
            'kaum muda paroki',
            'kegiatan mudika',
            'kegiatan omk paroki',
            'lomba mudika',
            'gathering pemuda',
            'live in mudika',
            'retret mudika',
            'rekoleksi omk',
            'seminar pemuda',
            'kajian kitab suci muda',
            'sharing iman muda',
            'volunter mudika',
            'volunteer paroki',
            'pelayanan muda',
            'mudika smdtba',
            'omk smdtba tulungagung',
            'kepemimpinan muda',
            'aksi muda paroki',
        ],

        // ── 28. ZIARAH ────────────────────────────────────────────────────────
        'ziarah' => [
            'ziarah umat paroki',
            'ziarah rohani',
            'ziarah ke sendangsono',
            'ziarah ke gua maria sempu',
            'ziarah ke gua maria',
            'gua maria tulungagung',
            'gua maria boro',
            'gua maria lourdes',
            'tempat ziarah jawa timur',
            'devosi maria',
            'devosi kepada maria',
            'bunda maria',
            'perjalanan rohani',
            'wisata rohani',
            'bukit doa',
            'kapel ziarah',
            'tempat suci',
            'berdoa di gua maria',
            'komunitas peziarah',
        ],

        // ── 29. RETRET & REKOLEKSI ────────────────────────────────────────────
        'retret' => [
            'retret paroki',
            'retret umat',
            'retret tahunan paroki',
            'retret spiritual',
            'retret rohani',
            'retret keluarga',
            'retret lingkungan',
            'rekoleksi',
            'rekoleksi umat',
            'rekoleksi lingkungan',
            'rekoleksi wilayah',
            'rekoleksi paroki',
            'permenungan bersama',
            'berdiam dalam tuhan',
            'silent retreat',
            'pembimbing retret',
            'rumah retret',
            'keheningan rohani',
            'hari bersama tuhan',
            'pembaruan rohani',
            'mendekat kepada tuhan',
        ],

        // ── 30. KARISMATIK ────────────────────────────────────────────────────
        'karismatik' => [
            'pembaruan karismatik',
            'karismatik katolik',
            'seminar hidup baru',
            'shn',
            'doa penyembuhan',
            'penyembuhan rohani',
            'majelis karismatik',
            'misa karismatik',
            'pujian penyembahan',
            'doa dalam roh',
            'kharisma',
            'kelompok karismatik paroki',
            'pkk tulungagung',
            'pertemuan karismatik',
            'kebangunan rohani',
            'sharing karismatik',
        ],

        // ── 31. BAZAR & PENGGALANGAN DANA ─────────────────────────────────────
        'bazar' => [
            'bazar paroki',
            'bazar tahunan',
            'bazar natal',
            'bazar paskah',
            'pameran paroki',
            'penggalangan dana paroki',
            'sumbangan paroki',
            'kolekte',
            'kolekte khusus',
            'dana paroki',
            'iuran paroki',
            'penggalangan dana sosial',
            'lelang amal',
            'fun games paroki',
            'hiburan paroki',
            'stand bazar',
            'kuliner paroki',
            'makanan khas tulungagung',
            'umkm umat',
        ],

        // ── 32. DONOR DARAH & KESEHATAN ───────────────────────────────────────
        'kesehatan' => [
            'donor darah paroki',
            'donor darah sukarela',
            'bakti sosial kesehatan',
            'pemeriksaan gratis',
            'posyandu paroki',
            'kesehatan umat',
            'layanan kesehatan gratis',
            'pelayanan medis',
            'tim medis paroki',
            'pendampingan orang sakit',
            'pengurapan orang sakit',
            'doa kesembuhan',
            'kunjungan orang sakit',
        ],

        // ── 33. FOTO/GAMBAR SPESIFIK ──────────────────────────────────────────
        'gambar' => [
            'foto kegiatan paroki',
            'dokumentasi paroki',
            'gambar gereja katolik',
            'foto misa',
            'foto liturgi',
            'foto umat paroki',
            'foto paroki tulungagung',
            'gambar paroki smdtba',
            'foto gereja smdtba',
            'gambar gereja tulungagung',
            'foto sakramen',
            'foto baptis paroki',
            'foto komuni pertama',
            'foto pernikahan gereja',
            'foto kategorial paroki',
            'foto kegiatan sosial paroki',
            'foto natal paroki tulungagung',
            'foto paskah paroki',
            'foto retret paroki',
            'foto ziarah umat',
            'dokumentasi misa paroki',
            'foto perayaan ekaristi',
            'foto lingkungan paroki',
            'foto wilayah paroki',
            'foto mudika omk paroki',
            'foto wkri paroki',
            'foto legio maria',
            'foto bazar paroki',
            'foto donor darah paroki',
            'foto romo pastor paroki',
            'foto dpp paroki',
            'foto umat berdoa',
            'foto kebersamaan umat',
            'foto komunitas paroki',
            'gambar kegiatan rohani',
            'foto pelantikan pengurus paroki',
            'foto pertemuan lingkungan',
            'foto pertemuan wilayah',
            'foto kunjungan pastoral',
            'foto adorasi paroki',
            'foto jalan salib paroki',
            'foto rosario bersama',
            'foto vigili paskah',
            'foto malam natal',
            'foto tahbisan imam',
            'foto krisma paroki',
            'foto baksos paroki',
            'foto sekolah minggu paroki',
            'foto katekese umat',
            'foto retret rohani',
            'foto rekoleksi paroki',
            'foto ziarah gua maria',
            'foto koor paduan suara',
            'foto karismatik paroki',
            'foto cursillo paroki',
            'foto lansia paroki',
            'foto misdinar altar',
            'foto prodiakon paroki',
            'foto rabu abu',
            'foto minggu palma paroki',
            'foto kamis putih paroki',
            'foto jumat agung paroki',
            'foto sabtu suci paroki',
            'foto vigili paskah paroki',
            'foto adven paroki',
            'foto corpus christi paroki',
            'foto pesta pelindung smdtba',
            'foto kristus raja paroki',
            'foto sidang paroki',
            'foto pelantikan dpp',
            'foto kronik paroki',
            'foto berita paroki',
            'foto historia paroki',
            'foto arsip gereja smdtba',
            'dokumentasi kegiatan smdtba',
            'galeri foto paroki tulungagung',
            'arsip foto paroki smdtba',
            'album foto gereja smdtba',
        ],

        // ── 34. PERTANYAAN LONG-TAIL ──────────────────────────────────────────
        'longTail' => [
            // Umum
            'kapan jadwal misa di paroki smdtba',
            'di mana gereja katolik tulungagung',
            'apa itu paroki smdtba tulungagung',
            'bagaimana mendaftar baptis di paroki smdtba',
            'siapa pastor paroki tulungagung',
            'kegiatan apa saja di paroki smdtba',
            'cara daftar krisma di tulungagung',
            'misa natal di gereja smdtba jam berapa',
            'retret paroki smdtba kapan',
            'ziarah ke gua maria dari tulungagung',
            'bazar paroki smdtba tulungagung',
            'cara jadi anggota legio maria paroki',
            'jadwal pengakuan dosa di smdtba',
            'pendaftaran komuni pertama paroki',
            'berapa biaya nikah di gereja smdtba',
            'sejarah gereja smdtba tulungagung',
            'foto kegiatan paroki smdtba tulungagung',
            'berita terbaru paroki smdtba',
            'kronik paroki smdtba terbaru',
            'bagaimana cara mendaftar katekumen di smdtba',
            'jadwal misa hari minggu paroki tulungagung',
            'di mana lokasi gereja smdtba tulungagung',
            'apakah ada retret mudika di paroki tulungagung',
            'nama romo di paroki smdtba tulungagung',
            'kapan jadwal donor darah di gereja smdtba',
            'cara ikut rekoleksi paroki smdtba',
            'kegiatan wkri di paroki tulungagung',
            'apakah ada adorasi di paroki smdtba',
            'jadwal jalan salib di gereja smdtba',
            'informasi pernikahan di gereja smdtba',
            'sekolah minggu di paroki smdtba tulungagung',
            // Pekan Suci
            'kapan minggu palma di paroki smdtba',
            'prosesi palma gereja smdtba tulungagung',
            'misa kamis putih paroki tulungagung jam berapa',
            'ada tuguran di paroki smdtba tidak',
            'ibadat jumat agung gereja smdtba',
            'jadwal jalan salib jumat agung di smdtba',
            'vigili paskah gereja smdtba jam berapa mulai',
            'suasana vigili paskah di paroki tulungagung',
            // Hari Raya Khusus
            'misa pesta pelindung paroki smdtba 8 desember',
            'kapan pesta nama paroki smdtba',
            'misa corpus christi di paroki tulungagung',
            'prosesi corpus christi gereja smdtba',
            'misa 1 januari gereja smdtba',
            'kapan pesta keluarga kudus di paroki smdtba',
            'perayaan epifani di gereja smdtba',
            'misa kristus raja paroki smdtba',
            'kapan hari raya maria diangkat ke surga paroki smdtba',
        ],

        // ── 35. WAKTU & FRESHNESS SIGNALS ────────────────────────────────────
        'waktu' => [
            '2024',
            '2025',
            '2026',
            '2027',
            'terbaru',
            'terkini',
            'hari ini',
            'minggu ini',
            'bulan ini',
            'tahun ini',
            'agenda terbaru',
            'kegiatan terbaru',
            'info terbaru paroki',
            'berita terbaru paroki smdtba',
            'kronik terbaru',
            'liputan terkini',
            'momen terbaru',
            'dokumentasi terbaru',
            'galeri terbaru paroki',
        ],

        // ── 36. ROSARIO & DEVOSI ─────────────────────────────────────────────
        'rosario' => [
            'doa rosario',
            'rosario bersama',
            'rosario harian',
            'manik rosario',
            'bunda maria',
            'devosi maria',
            'bulan rosario',
            'oktober bulan rosario',
            'misteri rosario',
            'misteri gembira',
            'misteri sedih',
            'misteri mulia',
            'misteri terang',
            'novena rosario',
            'doa kepada maria',
            'syafaat maria',
            'rosario keluarga',
            'rosario lingkungan',
            'ibadat sabda rosario',
            'lima puluh salam maria',
        ],

        // ── 37. ADORASI & DOA KHUSUS ─────────────────────────────────────────
        'adorasi' => [
            'adorasi sakramen mahakudus',
            'adorasi eukaristi',
            'benediksi',
            'monstrans',
            'tabernakulum',
            'keheningan adorasi',
            'berdiam di hadapan tuhan',
            'doa di hadapan sakramen mahakudus',
            'jam adorasi',
            'malam adorasi',
            'adorasi abadi',
            'hening bersama tuhan',
            'kontemplasi',
            'doa kontemplatif',
        ],

        // ── 38. JALAN SALIB ──────────────────────────────────────────────────
        'jalan_salib' => [
            'jalan salib',
            'via crucis',
            'via dolorosa',
            '14 perhentian',
            'jalan salib agung',
            'prosesi jalan salib',
            'jalan salib prapaskah',
            'jalan salib jumat',
            'permenungan sengsara',
            'sengsara kristus',
            'yesus memanggul salib',
            'jalan salib outdoor',
            'jalan salib komunitas',
            'perhentian jalan salib',
            'devosi jalan salib',
        ],

        // ── 39. KENAIKAN & PENTAKOSTA (sudah di atas, ini alias) ─────────────
        // (pool kenaikan dan pentakosta sudah ada di atas)

        // ── 40. TOBAT & REKONSILIASI ─────────────────────────────────────────
        'tobat' => [
            'sakramen tobat',
            'pengakuan dosa',
            'rekonsiliasi',
            'pengampunan dosa',
            'bilik tobat',
            'rekonsiliasi massal',
            'misa tobat',
            'pertobatan',
            'absolusi',
            'penitensi',
            'pengakuan massal',
            'tobat komunal',
            'ibadat tobat',
        ],

        // ── 41. TAHBISAN & IMAMAT ─────────────────────────────────────────────
        'tahbisan' => [
            'tahbisan imam',
            'penahbisan imam',
            'misa tahbisan',
            'sakramen imamat',
            'imam baru',
            'tahbisan diakon',
            'prosesi tahbisan',
            'penumpangan tangan',
            'konsekrasi uskup',
            'imam serikat yesus',
            'imam keuskupan surabaya',
            'formasi seminari',
            'panggilan imamat',
        ],

        // ── 42. KOOR & MUSIK LITURGI ─────────────────────────────────────────
        'koor' => [
            'paduan suara paroki',
            'koor gereja',
            'koor liturgi',
            'musik gereja',
            'dirigen gereja',
            'organis gereja',
            'kelompok koor',
            'tim musik liturgi',
            'lagu misa',
            'lagu liturgi',
            'nyanyian gereja',
            'pujian liturgi',
            'schola cantorum',
            'paduan suara misa',
            'pelayanan musik',
        ],

        // ── 43. LEKTOR & PELAYAN ALTAR ───────────────────────────────────────
        'lektor' => [
            'lektor',
            'pemazmur',
            'prodiakon',
            'prodiakon sukarela',
            'misdinar',
            'putera altar',
            'putri altar',
            'akolit',
            'pembawa kolekte',
            'tim pelayan liturgi',
            'pelayan altar',
            'latihan lektor',
            'pelatihan misdinar',
            'formasi prodiakon',
        ],

        // ── 44. PASTORAL KHUSUS ──────────────────────────────────────────────
        'pastoral' => [
            'kunjungan pastoral',
            'visitasi pastoral',
            'visitasi romo',
            'pastoral care',
            'pendampingan pastoral',
            'kunjungan romo ke lingkungan',
            'gembala dan umat',
            'pastoral keluarga',
            'pastoral remaja',
            'pastoral lansia',
            'pastoral mudika',
            'pastoral orang sakit',
            'tim pastoral',
        ],

        // ── 45. WILAYAH & STASI ──────────────────────────────────────────────
        'wilayah' => [
            'wilayah paroki',
            'pertemuan wilayah',
            'koordinator wilayah',
            'rapat wilayah',
            'stasi',
            'kapel stasi',
            'wilayah smdtba',
            'pembagian wilayah paroki',
            'ibadat wilayah',
            'lingkungan dan wilayah',
            'koordinasi umat wilayah',
        ],

        // ── 46. SEKOLAH MINGGU & BINA IMAN ──────────────────────────────────
        'sekolah_minggu' => [
            'sekolah minggu',
            'sekolah minggu paroki',
            'bina iman anak',
            'bia',
            'bina iman remaja',
            'bir',
            'pendidikan iman anak',
            'pendamping bia',
            'katekese anak',
            'cerita kitab suci anak',
            'lagu anak gereja',
            'aktivitas anak gereja',
            'permainan rohani anak',
            'generasi muda beriman',
        ],

        // ── 47. RAPAT & SIDANG ───────────────────────────────────────────────
        'rapat' => [
            'rapat dpp',
            'sidang paroki',
            'musyawarah paroki',
            'rapat pleno',
            'evaluasi program',
            'rapat pastoral',
            'koordinasi pengurus',
            'rapat kerja tahunan',
            'sidang tahunan paroki',
            'laporan pertanggungjawaban',
            'program kerja paroki',
            'rencana pastoral',
            'rapat komisi',
        ],

        // ── 48. PELAYANAN SOSIAL ─────────────────────────────────────────────
        'pelayanan_sosial' => [
            'pelayanan karitatif',
            'caritas paroki',
            'caritas indonesia',
            'lembaga sosial gereja',
            'bantuan bencana',
            'pendampingan orang berduka',
            'pelayanan orang sakit',
            'bantuan beasiswa',
            'konseling pastoral',
        ],

        // ── 49. KEHIDUPAN ROHANI SEHARI-HARI ─────────────────────────────────
        'kehidupan_rohani' => [
            'doa harian',
            'doa pagi',
            'doa malam',
            'doa keluarga',
            'renungan harian',
            'refleksi rohani',
            'meditasi harian',
            'iman dalam keseharian',
            'menghidupi iman',
            'iman yang nyata',
            'iman yang berbuah',
            'iman yang hidup',
            'spiritualitas sehari-hari',
            'kehidupan beriman',
            'perjalanan rohani',
            'pertumbuhan rohani',
            'pembaruan rohani',
            'mendekat kepada Tuhan',
            'hidup dalam Kristus',
            'panggilan hidup',
            'kesaksian hidup',
            'saksi Kristus',
        ],

        // ── 50. ARSITEKTUR & BANGUNAN GEREJA ─────────────────────────────────
        'arsitektur_gereja' => [
            'bangunan gereja',
            'gedung gereja',
            'arsitektur gereja',
            'gereja tua',
            'gereja bersejarah',
            'bangunan bersejarah',
            'cagar budaya',
            'heritage bangunan',
            'menara gereja',
            'lonceng gereja',
            'altar gereja',
            'mimbar gereja',
            'sakristi',
            'aula paroki',
            'halaman gereja',
            'kompleks gereja',
            'kapel adorasi',
            'bejana baptis',
            'gua maria',
            'taman doa',
            'stasi salib outdoor',
        ],

        // ── 51. PEKAN SUCI (umbrella — sub-pool ada di atas) ─────────────────
        'pekan_suci' => [
            'pekan suci',
            'minggu suci',
            'trihari suci',
            'triduum paskah',
            'minggu palma',
            'kamis putih',
            'jumat agung',
            'sabtu suci',
            'vigili paskah',
            'prosesi palma',
            'perjamuan malam terakhir',
            'pembasuhan kaki',
            'ibadat sengsara',
            'penghormatan salib',
            'api paskah',
            'exsultet',
            'alleluia',
            'liturgi pekan suci',
        ],

        // ── 52. DEVOSI KHUSUS ────────────────────────────────────────────────
        'devosi_khusus' => [
            'devosi hati kudus yesus',
            'devosi kerahiman ilahi',
            'devosi kepada yesus',
            'angelus',
            'doa angelus',
            'completorium',
            'ibadat harian',
            'novena kerahiman',
            'novena santo',
            'novena santa',
            'koronka',
            'doa untuk orang mati',
            'misa arwah',
            'november bulan arwah',
            'doa perdamaian',
        ],

        // ── 53. KATEGORIAL TAMBAHAN ──────────────────────────────────────────
        'kategorial_tambahan' => [
            'kelompok doa pria',
            'pria kristiani',
            'keluarga muda',
            'pasutri muda',
            'kelompok keluarga',
            'persekutuan doa siswa',
            'persekutuan doa mahasiswa',
            'katolik kampus',
            'guru katolik',
            'dokter katolik',
            'perawat kristiani',
            'pelayan kesehatan beriman',
        ],

        // ── 54. MEDIA GEREJA ─────────────────────────────────────────────────
        'media_gereja' => [
            'warta paroki',
            'buletin paroki',
            'majalah paroki',
            'newsletter paroki',
            'pengumuman paroki',
            'siaran radio paroki',
            'podcast rohani',
            'video rohani',
            'youtube paroki',
            'instagram paroki',
            'konten rohani',
            'live streaming ibadah',
            'misa online',
            'website paroki',
            'berita gereja online',
            'komsos',
        ],

        // ── 55. PANGGILAN & HIDUP BAKTI ──────────────────────────────────────
        'panggilan' => [
            'panggilan imamat',
            'seminaris',
            'seminari',
            'calon imam',
            'formasi imam',
            'biarawan',
            'biarawati',
            'bruder',
            'suster',
            'frater',
            'kongregasi',
            'serikat yesus',
            'panggilan religius',
            'hidup bakti',
            'kaul kemiskinan',
            'kaul kemurnian',
            'kaul ketaatan',
            'awam beriman',
            'diakonat permanen',
            'hari doa panggilan',
        ],

        // ── 56. YUBILEUM & PERINGATAN ────────────────────────────────────────
        'yubileum' => [
            'yubileum paroki',
            'ulang tahun paroki',
            'hari jadi paroki',
            'yubileum perak',
            'yubileum emas',
            'yubileum berlian',
            'pesta nama paroki',
            'pesta pelindung',
            'dies natalis paroki',
            'anniversary paroki',
        ],

        // ── 57. KELUARGA ─────────────────────────────────────────────────────
        'keluarga' => [
            'pastoral keluarga',
            'keluarga katolik',
            'keluarga beriman',
            'keluarga kristiani',
            'rumah tangga katolik',
            'bimbingan keluarga',
            'konseling perkawinan',
            'persiapan pernikahan',
            'kelas pernikahan',
            'marriage encounter',
            'keluarga kudus',
            'teladan nazaret',
        ],

        // ── 58. ANAK & REMAJA ────────────────────────────────────────────────
        'anak_remaja' => [
            'kegiatan anak gereja',
            'anak-anak paroki',
            'remaja gereja',
            'kegiatan remaja',
            'camp anak',
            'kemah rohani',
            'lomba anak',
            'festival anak',
            'karya anak gereja',
            'drama alkitab',
            'pertunjukan anak',
            'paduan suara anak',
            'koor anak',
            'pramuka katolik',
        ],

        // ── 59. KATA WAKTU & RITME ────────────────────────────────────────────
        'kata_waktu' => [
            'setiap minggu',
            'minggu demi minggu',
            'tahun demi tahun',
            'dari generasi ke generasi',
            'sejak lama',
            'kembali hadir',
            'selalu hadir',
            'rutin dilaksanakan',
            'setiap tahun digelar',
            'selalu dinantikan',
            'terus berlangsung',
            'tetap hidup',
        ],

        // ── 60. KONTEN JURNALISTIK ───────────────────────────────────────────
        'konten_jurnalistik' => [
            'liputan eksklusif',
            'laporan langsung',
            'reportase mendalam',
            'catatan lapangan',
            'kisah nyata',
            'berlangsung khidmat',
            'berlangsung meriah',
            'disambut antusias',
            'dihadiri ratusan',
            'menjadi momen bersejarah',
            'tak terlupakan',
            'foto eksklusif',
            'dokumentasi berharga',
            'komunitas yang solid',
            'komunitas yang hangat',
        ],

        // ── 61. LEGIO MARIA ──────────────────────────────────────────────────
        'legio' => [
            'legio maria',
            'legion of mary',
            'presidium legio',
            'anggota legio',
            'pertemuan legio',
            'praesidium',
            'akies legio',
            'devosi legio maria',
        ],

        // ── 62. WKRI ─────────────────────────────────────────────────────────
        'wkri' => [
            'wkri paroki',
            'wanita katolik ri',
            'kelompok wkri',
            'ibu-ibu paroki',
            'anggota wkri',
            'pertemuan wkri',
            'wkri tulungagung',
        ],

        // ── 63. CURSILLO ─────────────────────────────────────────────────────
        'cursillo' => [
            'cursillo paroki',
            'ultreya cursillo',
            'de colores',
            'palanca cursillo',
            'komunitas cursillo',
            'fin de semana',
            'cursillo smdtba',
        ],

        // ── 64. LANSIA ───────────────────────────────────────────────────────
        'lansia' => [
            'kelompok lansia',
            'lanjut usia paroki',
            'komunitas lansia',
            'kelompok opa oma',
            'umat lanjut usia',
            'pertemuan lansia',
            'pastoral lansia',
        ],

        // ── 65. KATEKESE ─────────────────────────────────────────────────────
        'katekese' => [
            'katekese umat',
            'pendalaman kitab suci',
            'kursus kitab suci',
            'pembinaan iman dewasa',
            'katekese lingkungan',
            'katekese wilayah',
            'kelas iman',
            'bible sharing',
        ],

        // ── 66. EKUMENISME ───────────────────────────────────────────────────
        'ekumenisme' => [
            'ekumenis',
            'dialog antaragama',
            'kerukunan umat',
            'toleransi beragama',
            'persatuan gereja',
            'pekan doa untuk persatuan',
            'lintas agama',
            'kerukunan nasional',
        ],

    ];

    return $pool;
}

// ──────────────────────────────────────────────────────────────────────────────
// HELPER FUNCTIONS
// ──────────────────────────────────────────────────────────────────────────────

/**
 * Flatten seluruh pool jadi satu array keyword unik.
 */
function get_seo_keywords_flat(): array
{
    $pool = get_seo_keywords_pool();
    $flat = [];
    foreach ($pool as $keywords) {
        foreach ($keywords as $kw) {
            $flat[] = $kw;
        }
    }
    return array_values(array_unique($flat));
}

/**
 * Build keyword string untuk meta tag halaman artikel.
 *
 * @param string       $judul    Judul artikel
 * @param array|string $tags     Tags artikel
 * @param string       $kategori Kategori artikel (menu label / slug)
 * @param int          $max      Maksimum jumlah keyword output
 */
function build_page_keywords(string $judul, $tags, string $kategori, int $max = 30): string
{
    $pool = get_seo_keywords_pool();

    $base = array_merge(
        $pool['identitas'],
        array_slice($pool['lokasi'], 0, 5)
    );

    $catKey = strtolower(trim($kategori));
    $catKw  = [];

    if (isset($pool[$catKey])) {
        $catKw = array_slice($pool[$catKey], 0, 8);
    } else {
        $menuMap = [
            // Liturgi harian
            'misa'              => 'liturgi',
            'ekaristi'          => 'liturgi',
            'liturgi'           => 'liturgi',
            // Hari raya besar
            'natal'             => 'natal',
            'keluarga kudus'    => 'keluarga_kudus',
            'maria bunda allah' => 'maria_bunda_allah',
            'epifani'           => 'epifani',
            'penampakan tuhan'  => 'epifani',
            'pembaptisan tuhan' => 'pembaptisan_tuhan',
            // Prapaskah & Pekan Suci — urutkan spesifik ke umum
            'vigili paskah'     => 'sabtu_suci',
            'sabtu suci'        => 'sabtu_suci',
            'jumat agung'       => 'jumat_agung',
            'kamis putih'       => 'kamis_putih',
            'minggu palma'      => 'minggu_palma',
            'pekan suci'        => 'pekan_suci',
            'rabu abu'          => 'prapaskah',
            'prapaskah'         => 'prapaskah',
            // Masa Paskah
            'paskah'            => 'paskah',
            'kenaikan'          => 'kenaikan',
            'pentakosta'        => 'pentakosta',
            // Hari raya lain
            'corpus christi'    => 'corpus_christi',
            'tubuh dan darah'   => 'corpus_christi',
            'hati yesus'        => 'hati_yesus',
            'maria diangkat'    => 'maria_diangkat',
            'pesta pelindung'   => 'pesta_pelindung',
            'tidak bernoda'     => 'pesta_pelindung',
            'kristus raja'      => 'kristus_raja',
            // Adven
            'adven'             => 'adven',
            // Sakramen
            'baptis'            => 'baptis',
            'komuni pertama'    => 'sakramen',
            'krisma'            => 'sakramen',
            'pernikahan'        => 'pernikahan',
            'nikah'             => 'pernikahan',
            'tobat'             => 'tobat',
            'tahbisan'          => 'tahbisan',
            'imamat'            => 'tahbisan',
            // Kelompok
            'mudika'            => 'mudika',
            'omk'               => 'mudika',
            'wkri'              => 'wkri',
            'legio maria'       => 'legio',
            'cursillo'          => 'cursillo',
            'karismatik'        => 'karismatik',
            'lansia'            => 'lansia',
            // Devosi
            'rosario'           => 'rosario',
            'adorasi'           => 'adorasi',
            'jalan salib'       => 'jalan_salib',
            'via crucis'        => 'jalan_salib',
            // Pelayanan & kegiatan
            'retret'            => 'retret',
            'rekoleksi'         => 'retret',
            'ziarah'            => 'ziarah',
            'bazar'             => 'bazar',
            'baksos'            => 'sosial',
            'donor darah'       => 'kesehatan',
            'kesehatan'         => 'kesehatan',
            // Kelembagaan
            'pelantikan'        => 'kepengurusan',
            'dpp'               => 'kepengurusan',
            'pastoral'          => 'pastoral',
            'rapat'             => 'rapat',
            'wilayah'           => 'wilayah',
            // Musik & pelayanan altar
            'koor'              => 'koor',
            'paduan suara'      => 'koor',
            'lektor'            => 'lektor',
            'misdinar'          => 'lektor',
            'prodiakon'         => 'lektor',
            // Pembinaan
            'sekolah minggu'    => 'sekolah_minggu',
            'bina iman anak'    => 'sekolah_minggu',
            'katekese'          => 'katekese',
            // Artikel
            'berita'            => 'berita',
            'kronik'            => 'kronik',
            'historia'          => 'historia',
            'sejarah'           => 'sejarah',
        ];

        foreach ($menuMap as $needle => $poolKey) {
            if (str_contains($catKey, $needle) && isset($pool[$poolKey])) {
                $catKw = array_slice($pool[$poolKey], 0, 8);
                break;
            }
        }

        if (empty($catKw)) {
            $catKw = array_slice($pool['digital'], 0, 5);
        }
    }

    $tagsArr = is_array($tags)
        ? $tags
        : array_filter(array_map('trim', explode(',', (string)$tags)));

    $stopWords = ['yang','dari','dan','atau','untuk','dalam','pada','dengan',
                  'oleh','akan','saat','kita','para','bagi','atas','bisa',
                  'juga','sudah','telah','lebih','masih','baru','kami','anda'];
    $judulWords = array_filter(
        preg_split('/[\s,.\-\/\(\)\[\]]+/', mb_strtolower($judul)),
        fn($w) => mb_strlen(trim($w)) > 3 && !in_array(trim($w), $stopWords)
    );

    $waktuKw = array_slice($pool['waktu'], 0, 3);

    $all = array_unique(array_merge(
        $base,
        $catKw,
        array_values($judulWords),
        $tagsArr,
        $waktuKw
    ));

    return implode(', ', array_slice(array_values($all), 0, $max));
}

/**
 * Ambil keyword yang relevan untuk sebuah gambar.
 * Dipakai oleh ImageSeoGenerator sebagai konteks untuk membangun keywords field.
 *
 * @param string       $judul    Judul artikel
 * @param array|string $tags     Tags artikel
 * @param string       $kategori Kategori artikel
 * @param string       $filename Nama file gambar (sudah bersih)
 * @param int          $max      Maks keyword yang dikembalikan
 */
function get_image_keyword_context(
    string $judul,
    $tags,
    string $kategori,
    string $filename = '',
    int    $max = 15
): array {
    $pool = get_seo_keywords_pool();

    $base = array_merge(
        array_slice($pool['identitas'], 0, 5),
        array_slice($pool['lokasi'],    0, 3),
        array_slice($pool['gambar'],    0, 6)
    );

    $textToScan = mb_strtolower($judul . ' ' . $filename . ' ' . $kategori);
    $extraPools = [];

    // Signal maps — urutan dari PALING SPESIFIK ke PALING UMUM
    // agar tidak ada konteks yang "ditelan" oleh konteks yang lebih luas
    $signals = [
        // ── Pekan Suci — sub-konteks spesifik dulu ──────────────────────────
        'vigili_paskah' => [
            'vigili paskah','misa vigili','api paskah','exsultet','lumen christi',
            'lilin paskah dinyalakan','alleluia pertama','baptis vigili',
            'malam paskah','malam terbesar','api kecil membelah malam',
        ],
        'sabtu_suci' => [
            'sabtu suci','keheningan sabtu','kristus di dalam kubur',
            'tidak ada misa siang','hari terdiam',
        ],
        'jumat_agung' => [
            'jumat agung','ibadat sengsara','penghormatan salib','salib diciumi',
            'bacaan sengsara menurut yohanes','yesus wafat di salib',
            'gereja hening','tidak ada lonceng','via crucis agung',
            'kolekte tanah suci','gereja berkabung','wafat kristus',
        ],
        'kamis_putih' => [
            'kamis putih','misa in caena domini','pembasuhan kaki','mandatum',
            'perjamuan malam terakhir','tuguran','jaga malam','taman getsemani',
            'tabernakulum dikosongkan','altar dilucuti','lembaga ekaristi',
            'malam perjamuan tuhan',
        ],
        'minggu_palma' => [
            'minggu palma','prosesi palma','daun palma','perarakan palma',
            'hosana putera daud','yesus masuk yerusalem','sengsara panjang',
            'pemberkatan palma','pawai palma',
        ],
        // ── Masa Prapaskah ───────────────────────────────────────────────────
        'prapaskah' => [
            'masa prapaskah','rabu abu','penerimaan abu','abu di dahi',
            'ingatlah engkau adalah debu','bertobatlah','aksi puasa pembangunan',
            'pantang daging','puasa prapaskah','pertobatan prapaskah',
            'kolekte app','jalan salib jumat','masa pertobatan',
        ],
        // ── Masa Paskah ──────────────────────────────────────────────────────
        'paskah' => [
            'perayaan paskah','misa paskah','kristus bangkit','kebangkitan kristus',
            'alleluia','ia sungguh bangkit','surrexit dominus','minggu paskah',
            'oktaf paskah','kerahiman ilahi','koronka',
        ],
        'kenaikan' => [
            'kenaikan tuhan','kenaikan yesus','hari raya kenaikan',
            'kenaikan ke surga','misa kenaikan','empat puluh hari setelah paskah',
        ],
        'pentakosta' => [
            'hari raya pentakosta','turunnya roh kudus','lahirnya gereja',
            'karunia roh kudus','misa pentakosta','lidah api','warna merah pentakosta',
        ],
        // ── Natal & rangkaiannya ─────────────────────────────────────────────
        'natal' => [
            'misa malam natal','misa natal','malam kudus','perayaan natal',
            'kelahiran yesus','kandang natal','pohon natal','kreche natal',
            'lagu natal','drama natal','malam kudus','desember',
        ],
        'keluarga_kudus' => [
            'pesta keluarga kudus','keluarga nazaret','yesus maria yusuf',
            'minggu setelah natal','misa keluarga kudus',
        ],
        'maria_bunda_allah' => [
            '1 januari','hari raya maria bunda allah','misa tahun baru',
            'theotokos','bunda allah','hari perdamaian sedunia',
        ],
        'epifani' => [
            'epifani','penampakan tuhan','tiga raja','orang majus',
            'bintang betlehem','6 januari','emas kemenyan mur',
        ],
        'pembaptisan_tuhan' => [
            'pembaptisan tuhan','yesus dibaptis','sungai yordan',
            'roh kudus seperti merpati','inilah puteraku','yohanes pembaptis',
        ],
        // ── Adven ────────────────────────────────────────────────────────────
        'adven' => [
            'masa adven','lilin adven','lingkaran adven','penantian natal',
            'penantian kristus','minggu adven','gaudete','lilin merah muda',
        ],
        // ── Hari Raya Masa Biasa ─────────────────────────────────────────────
        'corpus_christi' => [
            'corpus christi','tubuh dan darah kristus','prosesi sakramen mahakudus',
            'monstrans dibawa','prosesi corpus','umat mengiringi sakramen',
        ],
        'hati_yesus' => [
            'hati yesus mahakudus','devosi hati kudus','pertama jumat',
            'misa pertama jumat','novena hati kudus','litani hati kudus',
        ],
        'maria_diangkat' => [
            'maria diangkat ke surga','assumption','15 agustus',
            'hari raya bunda maria agustus',
        ],
        'pesta_pelindung' => [
            'pesta pelindung','pesta nama paroki','santa maria dengan tidak bernoda',
            '8 desember','immaculate conception','bunda tanpa noda',
            'patrona paroki','pesta besar smdtba',
        ],
        'kristus_raja' => [
            'kristus raja','hari raya kristus raja','penutup tahun liturgi',
            'minggu terakhir masa biasa','kerajaan allah',
        ],
        // ── Sakramen ─────────────────────────────────────────────────────────
        'baptis' => [
            'sakramen baptis','pembaptisan','mandi baptis','baptis bayi',
            'baptis dewasa','baptis massal','calon baptis','katekumen',
            'air baptis','bejana baptis','rcia',
        ],
        'komuni' => [
            'komuni pertama','penerimaan komuni pertama','meja tuhan',
            'anak komuni','komuni anak',
        ],
        'krisma' => [
            'sakramen krisma','sakramen penguatan','misa krisma',
            'penerimaan krisma','minyak krisma','meterai roh kudus',
        ],
        'pernikahan' => [
            'pernikahan katolik','pemberkatan nikah','misa pernikahan',
            'sakramen perkawinan','janji pernikahan','cincin nikah','pengantin baru',
        ],
        'tobat' => [
            'sakramen tobat','pengakuan dosa','rekonsiliasi',
            'bilik tobat','rekonsiliasi massal','ibadat tobat',
        ],
        'tahbisan' => [
            'tahbisan imam','penahbisan imam','misa tahbisan',
            'imam baru','penumpangan tangan','imam ditahbiskan',
        ],
        // ── Kelompok & Kategorial ────────────────────────────────────────────
        'mudika' => [
            'orang muda katolik','mudika paroki','omk paroki','live in mudika',
            'retret mudika','generasi muda paroki',
        ],
        'wkri' => [
            'wkri paroki','wanita katolik ri','anggota wkri','pertemuan wkri',
        ],
        'legio' => [
            'legio maria','legion of mary','presidium legio','anggota legio',
        ],
        'karismatik' => [
            'pembaruan karismatik','karismatik katolik','seminar hidup baru',
            'doa penyembuhan karismatik','pujian penyembahan','kelompok karismatik',
        ],
        'cursillo' => [
            'cursillo paroki','ultreya cursillo','de colores','komunitas cursillo',
        ],
        'lansia' => [
            'kelompok lansia','lanjut usia paroki','komunitas lansia','opa oma paroki',
        ],
        'koor' => [
            'paduan suara','koor liturgi','koor gereja','dirigen gereja','kelompok koor',
        ],
        'lektor' => [
            'lektor paroki','pemazmur paroki','prodiakon paroki','misdinar paroki',
            'putera altar','putri altar',
        ],
        // ── Liturgi harian & devosi ──────────────────────────────────────────
        'misa' => [
            'misa kudus','perayaan ekaristi','ekaristi','ibadat sabda','homili',
            'konsekrasi','bacaan injil','ritus komuni','imam selebran','ordo misa',
        ],
        'rosario' => [
            'doa rosario','rosario bersama','manik rosario','misteri rosario',
            'bulan rosario','oktober rosario','lima puluh salam maria',
        ],
        'adorasi' => [
            'adorasi sakramen','adorasi eukaristi','sakramen mahakudus',
            'monstrans','benediksi sakramen','jam adorasi',
        ],
        'jalan_salib' => [
            'jalan salib','via crucis','via dolorosa','14 perhentian',
            'sengsara kristus','yesus memanggul salib','prosesi jalan salib',
        ],
        // ── Kegiatan & Pelayanan ─────────────────────────────────────────────
        'sosial' => [
            'baksos paroki','bakti sosial','santunan paroki','karitatif paroki',
            'pembagian sembako',
        ],
        'kesehatan' => [
            'donor darah paroki','baksos kesehatan','pemeriksaan gratis',
            'posyandu paroki','pelayanan medis paroki','pengobatan gratis',
        ],
        'kepengurusan' => [
            'dpp paroki','pelantikan pengurus','serah terima jabatan',
            'pengurus baru paroki','pastor paroki',
        ],
        'sejarah' => [
            'sejarah paroki','historia paroki','kronologi paroki',
            'yubileum paroki','arsip paroki',
        ],
        'pastoral' => [
            'kunjungan pastoral','visitasi pastoral','visitasi romo',
        ],
        'bazar' => [
            'bazar paroki','bazar tahunan','stand bazar','penggalangan dana bazar',
        ],
        'retret' => [
            'retret paroki','rekoleksi paroki','hari hening','silent retreat',
        ],
        'ziarah' => [
            'ziarah paroki','gua maria','sendangsono','peziarah paroki',
        ],
        'sekolah_minggu' => [
            'sekolah minggu paroki','bina iman anak','bina iman remaja',
            'pendamping sekolah minggu','katekese anak',
        ],
        'katekese' => [
            'katekese umat','pendalaman kitab suci','kursus kitab suci',
            'pembinaan iman dewasa',
        ],
        'lingkungan' => [
            'ibadat lingkungan','pertemuan lingkungan','ketua lingkungan paroki',
        ],
        'wilayah' => [
            'pertemuan wilayah','koordinator wilayah paroki','musyawarah wilayah',
        ],
        'kronik' => [
            'kronik kegiatan','laporan kegiatan paroki',
            'dokumentasi kegiatan paroki','liputan kegiatan',
        ],
        'berita' => [
            'berita paroki','warta paroki','informasi terbaru paroki',
            'pengumuman paroki',
        ],
    ];

    foreach ($signals as $poolKey => $kwList) {
        foreach ($kwList as $kw) {
            if (str_contains($textToScan, $kw) && isset($pool[$poolKey])) {
                $extraPools[] = array_slice($pool[$poolKey], 0, 5);
                break; // satu hit per pool sudah cukup
            }
        }
    }

    $tagsArr = is_array($tags)
        ? $tags
        : array_filter(array_map('trim', explode(',', (string)$tags)));

    $waktuKw = array_slice($pool['waktu'], 0, 2);

    $all = array_unique(array_merge(
        $base,
        $tagsArr,
        $waktuKw,
        ...($extraPools ?: [array_slice($pool['digital'], 0, 4)])
    ));

    return array_values(array_slice($all, 0, $max));
}

/**
 * Ambil keyword untuk konteks spesifik — dipanggil dari ImageSeoGenerator.
 *
 * @param string $context  Konteks yang sudah terdeteksi
 * @param int    $max      Maksimum keyword
 */
function get_keywords_for_context(string $context, int $max = 10): array
{
    $pool   = get_seo_keywords_pool();
    $merged = array_merge(
        array_slice($pool['identitas'], 0, 4),
        array_slice($pool['lokasi'],    0, 2),
        $pool[$context] ?? array_slice($pool['kegiatan'], 0, 6)
    );
    return array_values(array_slice(array_unique($merged), 0, $max));
}

/**
 * Build keyword set untuk konteks gambar dengan cakupan yang sangat luas.
 *
 * @param string $ctx      Konteks yang sudah terdeteksi
 * @param string $judul    Judul artikel
 * @param string $kategori Kategori artikel
 * @param array  $tags     Tags artikel
 * @param int    $max      Maksimum keyword
 */
function get_extended_keywords(string $ctx, string $judul, string $kategori, array $tags = [], int $max = 15): array
{
    $pool = get_seo_keywords_pool();

    $base = array_merge(
        array_slice($pool['identitas'], 0, 4),
        array_slice($pool['lokasi'],    0, 2),
        array_slice($pool['gambar'],    0, 4)
    );

    // Mapping konteks → pool yang relevan
    // Pekan Suci: sub-konteks dulu, baru umbrella
    $ctx_map = [
        // Pekan Suci
        'vigili_paskah'    => ['sabtu_suci', 'paskah', 'pekan_suci'],
        'sabtu_suci'       => ['sabtu_suci', 'pekan_suci', 'paskah'],
        'jumat_agung'      => ['jumat_agung', 'jalan_salib', 'pekan_suci'],
        'kamis_putih'      => ['kamis_putih', 'pekan_suci', 'liturgi'],
        'minggu_palma'     => ['minggu_palma', 'pekan_suci', 'prapaskah'],
        'pekan_suci'       => ['pekan_suci', 'paskah', 'jalan_salib'],
        'prapaskah'        => ['prapaskah', 'jalan_salib', 'tobat'],
        // Masa Paskah
        'paskah'           => ['paskah', 'kalender', 'liturgi'],
        'kenaikan'         => ['kenaikan', 'kalender'],
        'pentakosta'       => ['pentakosta', 'karismatik', 'kalender'],
        // Natal & rangkaian
        'natal'            => ['natal', 'kalender', 'kegiatan'],
        'keluarga_kudus'   => ['keluarga_kudus', 'keluarga', 'kalender'],
        'maria_bunda_allah'=> ['maria_bunda_allah', 'rosario', 'kalender'],
        'epifani'          => ['epifani', 'kalender', 'liturgi'],
        'pembaptisan_tuhan'=> ['pembaptisan_tuhan', 'baptis', 'kalender'],
        // Adven
        'adven'            => ['adven', 'kalender', 'kehidupan_rohani'],
        // Hari Raya Masa Biasa
        'corpus_christi'   => ['corpus_christi', 'adorasi', 'liturgi'],
        'hati_yesus'       => ['hati_yesus', 'devosi_khusus', 'adorasi'],
        'maria_diangkat'   => ['maria_diangkat', 'rosario', 'kalender'],
        'pesta_pelindung'  => ['pesta_pelindung', 'yubileum', 'kegiatan'],
        'kristus_raja'     => ['kristus_raja', 'kalender', 'liturgi'],
        // Sakramen
        'baptis'           => ['baptis', 'sakramen'],
        'komuni'           => ['sakramen', 'kalender'],
        'krisma'           => ['sakramen', 'mudika'],
        'tahbisan'         => ['tahbisan', 'panggilan', 'kepengurusan'],
        'pernikahan'       => ['pernikahan', 'keluarga', 'sakramen'],
        'tobat'            => ['tobat', 'sakramen', 'kehidupan_rohani'],
        // Kelompok
        'mudika'           => ['mudika', 'anak_remaja', 'kategorial'],
        'wkri'             => ['wkri', 'kategorial_tambahan'],
        'legio'            => ['legio', 'devosi_khusus', 'kategorial'],
        'karismatik'       => ['karismatik', 'devosi_khusus'],
        'cursillo'         => ['cursillo', 'kategorial', 'kehidupan_rohani'],
        'lansia'           => ['lansia', 'kategorial_tambahan'],
        // Liturgi & devosi
        'misa'             => ['liturgi', 'kalender'],
        'rosario'          => ['rosario', 'devosi_khusus', 'liturgi'],
        'adorasi'          => ['adorasi', 'devosi_khusus', 'liturgi'],
        'jalan_salib'      => ['jalan_salib', 'prapaskah', 'liturgi'],
        'koor'             => ['koor', 'liturgi'],
        'lektor'           => ['lektor', 'liturgi'],
        // Kegiatan & pelayanan
        'retret'           => ['retret', 'kehidupan_rohani'],
        'ziarah'           => ['ziarah', 'devosi_khusus'],
        'bazar'            => ['bazar', 'kegiatan'],
        'baksos'           => ['sosial', 'pelayanan_sosial'],
        'donor_darah'      => ['kesehatan', 'pelayanan_sosial'],
        'kesehatan'        => ['kesehatan', 'pelayanan_sosial'],
        'rapat'            => ['rapat', 'kepengurusan'],
        'pelantikan'       => ['kepengurusan', 'kegiatan'],
        'pastoral'         => ['pastoral', 'kepengurusan', 'kehidupan_rohani'],
        'sejarah'          => ['sejarah', 'historia', 'arsitektur_gereja'],
        'lingkungan'       => ['sosial', 'kehidupan_rohani'],
        'wilayah'          => ['wilayah', 'kepengurusan'],
        'sekolah_minggu'   => ['sekolah_minggu', 'pendidikan', 'anak_remaja'],
        'katekese'         => ['katekese', 'pendidikan', 'kehidupan_rohani'],
        'kronik'           => ['kronik', 'media_gereja', 'konten_jurnalistik'],
        'berita'           => ['berita', 'media_gereja', 'konten_jurnalistik'],
    ];

    $extra = [];
    $pools_to_use = $ctx_map[$ctx] ?? ['kegiatan', 'kehidupan_rohani'];

    foreach ($pools_to_use as $pool_key) {
        if (isset($pool[$pool_key])) {
            $extra = array_merge($extra, array_slice($pool[$pool_key], 0, 5));
        }
    }

    $tagsArr = array_filter(array_map('trim', $tags));

    $waktu = array_slice($pool['waktu'], 0, 2);

    $stopWords = ['yang','dari','dan','atau','untuk','dalam','pada','dengan','oleh',
                  'akan','saat','kita','para','bagi','atas','bisa','juga','sudah',
                  'telah','lebih','masih','baru','kami','anda','ini','itu','ada'];
    $judulWords = array_filter(
        preg_split('/[\s,.\-\/\(\)\[\]]+/', mb_strtolower($judul)),
        fn($w) => mb_strlen(trim($w)) > 3 && !in_array(trim($w), $stopWords)
    );

    $all = array_unique(array_merge($base, $extra, $tagsArr, $waktu, array_values($judulWords)));

    return array_values(array_slice($all, 0, $max));
}

// ──────────────────────────────────────────────────────────────────────────────
// POOL EKSPANSI — Bank Kata Profesional & Kaya Konteks
// ──────────────────────────────────────────────────────────────────────────────

/**
 * Bank kata emosional & deskriptif untuk menambah kedalaman konten.
 */
function get_vocabulary_bank(): array
{
    return [

        // ── KATA SIFAT ROHANI ──────────────────────────────────────────────────
        'kata_sifat_rohani' => [
            'khidmat', 'sakral', 'agung', 'suci', 'kudus', 'hening', 'syahdu',
            'mengharukan', 'menggetarkan', 'menyentuh', 'mendalam', 'bermakna',
            'berkesan', 'penuh kasih', 'penuh harapan', 'penuh syukur',
            'tulus', 'ikhlas', 'khusyuk', 'hikmat', 'sejuk', 'meneduhkan',
            'menyejukkan', 'menenangkan', 'menguatkan', 'meneguhkan',
            'mengangkat', 'memurnikan', 'memperbarui',
            'kontemplatif', 'meditatif', 'reflektif',
        ],

        // ── KATA KERJA KOMUNITAS ───────────────────────────────────────────────
        'kata_kerja_komunitas' => [
            'berhimpun', 'berkumpul', 'bersatu', 'menyatu',
            'bersama-sama', 'bergandengan', 'berpadu', 'bergabung', 'bersekutu',
            'merayakan', 'menyambut', 'menerima', 'memeluk', 'mempererat',
            'memperkuat', 'memperbarui', 'membangun', 'menguatkan', 'menopang',
            'mendampingi', 'mengikuti', 'mengiringi', 'menyertai',
            'berbagi', 'memberi', 'melayani', 'membantu', 'menjangkau',
            'merangkul', 'mempertemukan',
        ],

        // ── FRASA JURNALISTIK ──────────────────────────────────────────────────
        'frasa_jurnalistik' => [
            'tidak ada kursi kosong',
            'penuh sesak namun tertib',
            'berlangsung khidmat dan bermakna',
            'dihadiri ratusan umat',
            'disambut antusias komunitas',
            'berjalan lancar dan penuh sukacita',
            'menorehkan kesan mendalam',
            'menjadi momen yang tak terlupakan',
            'diakhiri dengan doa dan berkat',
            'meninggalkan jejak yang dalam',
            'mengundang banyak air mata haru',
        ],

        // ── KATA KOMUNITAS ─────────────────────────────────────────────────────
        'kata_komunitas' => [
            'komunitas', 'jemaat', 'umat', 'keluarga Allah', 'kawanan domba',
            'saudara seiman', 'komunitas beriman', 'keluarga paroki',
            'warga paroki', 'umat setia', 'umat beriman', 'keluarga SMDTBA',
            'komunitas Kristiani', 'saudara-saudari', 'sesama beriman',
            'keluarga iman', 'persekutuan umat',
        ],

        // ── FRASA IMAN ─────────────────────────────────────────────────────────
        'frasa_iman' => [
            'iman yang hidup',
            'iman yang berbuah',
            'iman yang nyata',
            'iman yang tidak goyah',
            'perjalanan iman',
            'pertumbuhan rohani',
            'pembaruan iman',
            'kehidupan rohani',
            'keteguhan iman',
            'buah iman',
            'saksi iman',
            'gereja yang hidup',
            'gereja yang melayani',
        ],

        // ── KATA WAKTU & RITME ────────────────────────────────────────────────
        'kata_waktu' => [
            'setiap minggu', 'minggu demi minggu', 'tahun demi tahun',
            'dari generasi ke generasi', 'sejak lama', 'kembali hadir',
            'selalu hadir', 'rutin dilaksanakan', 'setiap tahun digelar',
            'selalu dinantikan', 'terus berlangsung', 'tetap hidup',
        ],
    ];
}

/**
 * Pool kata untuk alt text yang lebih deskriptif dan bervariasi.
 */
function get_alt_text_vocabulary(): array
{
    return [
        'awalan' => [
            'Suasana', 'Momen', 'Foto', 'Gambar', 'Dokumentasi', 'Potret',
            'Pemandangan', 'Tampak', 'Terlihat', 'Kilasan', 'Cuplikan', 'Rekaman',
        ],
        'kata_kerja' => [
            'bersatu', 'berkumpul', 'berhimpun', 'merayakan', 'menjalani',
            'mengikuti', 'menghadiri', 'berpartisipasi', 'menghayati',
            'merasakan', 'berdoa', 'beribadah', 'melayani', 'berbagi',
        ],
        'kata_sifat' => [
            'khidmat', 'sakral', 'agung', 'mengharukan', 'bermakna', 'berkesan',
            'tulus', 'hangat', 'akrab', 'hidup', 'bersemangat',
            'antusias', 'gembira', 'sukacita', 'penuh harap', 'penuh kasih',
            'syahdu', 'hening', 'kontemplatif', 'meditatif', 'penuh devosi',
        ],
    ];
}

/**
 * Ambil bank kata untuk variasi penulisan konten.
 *
 * @param string $category Kategori bank kata
 * @param int    $count    Jumlah item yang diambil (0 = semua)
 */
function get_writing_vocabulary(string $category = 'all', int $count = 0): array
{
    $bank = get_vocabulary_bank();

    if ($category === 'all') {
        $merged = [];
        foreach ($bank as $items) {
            $merged = array_merge($merged, $items);
        }
        $result = array_unique($merged);
    } else {
        $result = $bank[$category] ?? [];
    }

    return $count > 0 ? array_slice($result, 0, $count) : $result;
}