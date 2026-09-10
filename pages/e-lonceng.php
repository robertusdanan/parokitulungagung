<?php
require_once __DIR__ . '/../includes/functions.php';

$seo = [
    'title'       => 'E-Lonceng Warta Digital – Paroki Tulungagung',
    'description' => 'Baca warta dan majalah digital E-Lonceng Paroki Santa Maria Dengan Tidak Bernoda Asal (SMDTBA) Tulungagung secara online. Terbit rutin setiap minggu berisi pengumuman, jadwal misa, renungan, dan kegiatan umat.',
    'canonical'   => 'https://www.parokitulungagung.org/e-lonceng',
    'keywords'    => 'e-lonceng, warta paroki, majalah digital paroki smdtba tulungagung, warta minggu, bulletin gereja',
    'type'        => 'website',
];
$breadcrumbs = [
    ['name' => 'Beranda', 'url' => 'https://www.parokitulungagung.org'],
    ['name' => 'E-Lonceng', 'url' => 'https://www.parokitulungagung.org/e-lonceng'],
];
$extraCss = ['/css/content.css'];
?>
<!doctype html>
<html lang="id">
<head>
  <?php include __DIR__ . '/../components/seo_head.php'; ?>
  <script type="application/ld+json">
  <?= json_encode([
    '@context'    => 'https://schema.org',
    '@type'       => 'WebPage',
    'name'        => 'E-Lonceng Warta Digital Paroki Tulungagung',
    'description' => 'Warta dan majalah digital Paroki Santa Maria Dengan Tidak Bernoda Asal Tulungagung. Terbit rutin berisi pengumuman, jadwal misa, renungan, dan kegiatan umat.',
    'url'         => 'https://www.parokitulungagung.org/e-lonceng',
    'inLanguage'  => 'id',
    'isPartOf'    => ['@id' => 'https://www.parokitulungagung.org/#website'],
    'about'       => [
      '@type'       => 'Periodical',
      'name'        => 'E-Lonceng',
      'description' => 'Warta digital mingguan Paroki Santa Maria Dengan Tidak Bernoda Asal Tulungagung',
      'publisher'   => [
        '@type' => 'Organization',
        'name'  => 'Paroki Santa Maria Dengan Tidak Bernoda Asal Tulungagung',
        'url'   => 'https://www.parokitulungagung.org',
      ],
      'inLanguage'  => 'id',
    ],
  ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT); ?>
  </script>
  <style>
  .elonceng-wrap { max-width: 900px; margin: 0 auto; padding: 0 12px 2rem; }

  /* ── Intro section — diindeks Google ── */
  .elonceng-intro {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
    margin: 1.4rem 0 1.8rem;
  }
  @media (max-width: 580px) { .elonceng-intro { grid-template-columns: 1fr; gap: 1rem; } }

  .elonceng-about {
    background: #fff;
    border: 1px solid #e8e0d4;
    border-radius: 12px;
    padding: 1.2rem 1.4rem;
  }
  .elonceng-about h2 {
    font-family: 'Cormorant Garamond', Georgia, serif;
    font-size: 1.15rem; font-weight: 700;
    color: #2c1a0e; margin: 0 0 .7rem;
  }
  .elonceng-about p {
    font-size: .85rem; line-height: 1.8;
    color: #4a3c2e; margin: 0 0 .55rem;
    font-family: 'Archivo Narrow', Arial, sans-serif;
  }
  .elonceng-about p:last-child { margin-bottom: 0; }

  .elonceng-rubriks {
    background: #faf7f2;
    border: 1px solid #e8e0d4;
    border-radius: 12px;
    padding: 1.2rem 1.4rem;
  }
  .elonceng-rubriks h2 {
    font-family: 'Cormorant Garamond', Georgia, serif;
    font-size: 1.15rem; font-weight: 700;
    color: #2c1a0e; margin: 0 0 .8rem;
  }
  .elonceng-rubrik-list { list-style: none; margin: 0; padding: 0; }
  .elonceng-rubrik-list li {
    display: flex; align-items: center; gap: .6rem;
    font-size: .83rem; color: #4a3c2e; padding: .35rem 0;
    border-bottom: 1px dashed #e4d9cc;
    font-family: 'Archivo Narrow', Arial, sans-serif;
  }
  .elonceng-rubrik-list li:last-child { border-bottom: 0; }
  .elonceng-rubrik-dot {
    width: 7px; height: 7px; border-radius: 50%;
    background: #b8963e; flex-shrink: 0;
  }

  /* ── Iframe reader ── */
  .elonceng-reader-wrap {
    background: #fff;
    border: 1px solid #e8e0d4;
    border-radius: 12px;
    overflow: hidden;
    margin-bottom: 1.5rem;
  }
  .elonceng-reader-head {
    display: flex; align-items: center; gap: .7rem;
    padding: .8rem 1.2rem;
    background: linear-gradient(135deg, #faf7f2, #f2ebe0);
    border-bottom: 1px solid #e8e0d4;
  }
  .elonceng-reader-head-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1rem; font-weight: 700; color: #2c1a0e;
  }
  .elonceng-reader-head-sub {
    font-size: .72rem; color: #8a7460;
    font-family: 'Montserrat', sans-serif;
    margin-left: auto;
  }
  .elonceng-iframe-wrap {
    position: relative;
    height: 75vh; min-height: 480px;
  }
  #iframe-loader {
    position: absolute; inset: 0;
    display: flex; flex-direction: column;
    align-items: center; justify-content: center; gap: 10px;
    background: #faf7f2;
    transition: opacity .35s ease;
  }
  #iframe-loader.hidden { opacity: 0; pointer-events: none; }
  .skel-block { height: 52px; width: 55%; border-radius: 6px; background: #e0d5c8; }
  .skel-line  { height: 12px; border-radius: 4px; background: #e0d5c8; }
  #framecontent {
    position: absolute; inset: 0;
    width: 100%; height: 100%; border: 0;
  }

  /* ── Cara baca section ── */
  .elonceng-howto {
    background: #fff;
    border: 1px solid #e8e0d4;
    border-radius: 12px;
    padding: 1.2rem 1.4rem;
    margin-bottom: 1rem;
  }
  .elonceng-howto h2 {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.1rem; font-weight: 700;
    color: #2c1a0e; margin: 0 0 .8rem;
  }
  .elonceng-steps {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: .8rem;
  }
  .elonceng-step {
    background: #faf7f2; border-radius: 8px;
    padding: .9rem 1rem;
    font-family: 'Archivo Narrow', Arial, sans-serif;
  }
  .elonceng-step-num {
    font-size: .65rem; font-weight: 700;
    color: #b8963e; letter-spacing: 1px;
    text-transform: uppercase; margin-bottom: .3rem;
  }
  .elonceng-step-text { font-size: .82rem; color: #4a3c2e; line-height: 1.6; }
  </style>
</head>
<body>
<?php $headerTitle = 'E-Lonceng'; include __DIR__ . '/../components/page_header.php'; ?>

<main id="main-content">
<div class="elonceng-wrap">

  <section class="elonceng-intro" aria-label="Tentang E-Lonceng">

    <div class="elonceng-about">
      <h2>Apa itu E-Lonceng?</h2>
      <p>
        <strong>E-Lonceng</strong> adalah warta digital resmi Paroki Santa Maria Dengan Tidak Bernoda Asal
        (SMDTBA) Tulungagung. E-Lonceng hadir sebagai pengganti buletin
        cetak yang dapat diakses kapan saja dan di mana saja melalui perangkat apapun.
      </p>
      <p>
        Nama <em>Lonceng</em> diambil dari tradisi gereja — bunyi lonceng yang mengundang umat untuk
        berkumpul dan bersatu dalam iman. Kini panggilan itu hadir dalam format digital.
      </p>
      <p>
        E-Lonceng diterbitkan oleh Komisi Komunikasi Sosial (Komsos) Paroki Tulungagung
        dan dapat diakses secara gratis oleh seluruh umat.
      </p>
    </div>

    <div class="elonceng-rubriks">
      <h2>Isi Setiap Edisi</h2>
      <ul class="elonceng-rubrik-list">
        <li><span class="elonceng-rubrik-dot"></span> Pengumuman & agenda paroki pekan ini</li>
        <li><span class="elonceng-rubrik-dot"></span> Jadwal misa dan petugas liturgi</li>
        <li><span class="elonceng-rubrik-dot"></span> Renungan & refleksi iman mingguan</li>
        <li><span class="elonceng-rubrik-dot"></span> Liputan kegiatan kategorial & wilayah</li>
        <li><span class="elonceng-rubrik-dot"></span> Info pastoral dan pelayanan umat</li>
        <li><span class="elonceng-rubrik-dot"></span> Kolom khusus: keluarga, kaum muda, lansia</li>
        <li><span class="elonceng-rubrik-dot"></span> Daftar kelahiran, baptis, dan pernikahan</li>
      </ul>
    </div>

  </section>

  <!-- Iframe reader -->
  <div class="elonceng-reader-wrap">
    <div class="elonceng-reader-head">
      <svg viewBox="0 0 24 24" fill="none" stroke="#b8963e" stroke-width="2"
           width="18" height="18" aria-hidden="true">
        <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/>
        <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>
      </svg>
      <span class="elonceng-reader-head-title">Baca E-Lonceng</span>
      <span class="elonceng-reader-head-sub">Semua edisi tersedia</span>
    </div>

    <div class="elonceng-iframe-wrap">
      <div id="iframe-loader" aria-hidden="true">
        <div class="skel-block"></div>
        <div class="skel-line" style="width:68%"></div>
        <div class="skel-line" style="width:48%"></div>
      </div>
      <iframe id="framecontent"
              src="https://anyflip.com/bookcase/dxiqc"
              title="E-Lonceng Warta Digital Paroki Tulungagung — Semua Edisi"
              allow="fullscreen"
              loading="lazy"></iframe>
    </div>
  </div>

  <!-- Cara baca — konten tambahan untuk SEO & UX -->
  <div class="elonceng-howto">
    <h2>Cara Membaca E-Lonceng</h2>
    <div class="elonceng-steps">
      <div class="elonceng-step">
        <div class="elonceng-step-num">Langkah 1</div>
        <div class="elonceng-step-text">Pilih edisi yang ingin dibaca dari rak buku di atas.</div>
      </div>
      <div class="elonceng-step">
        <div class="elonceng-step-num">Langkah 2</div>
        <div class="elonceng-step-text">Klik cover edisi untuk membuka dan membaca secara lengkap.</div>
      </div>
      <div class="elonceng-step">
        <div class="elonceng-step-num">Langkah 3</div>
        <div class="elonceng-step-text">Gunakan ikon fullscreen untuk pengalaman membaca lebih nyaman.</div>
      </div>
      <div class="elonceng-step">
        <div class="elonceng-step-num">Akses Gratis</div>
        <div class="elonceng-step-text">Setiap edisi dapat dibaca dengan gratis</div>
      </div>
    </div>
  </div>

</div>
</main>

<?php include __DIR__ . '/../components/footer.php'; ?>
<script src="/js/app.js?v=<?= substr(md5_file(__DIR__ . '/../js/app.js'), 0, 8) ?>"></script>
<script>
(function () {
  var frame  = document.getElementById('framecontent');
  var loader = document.getElementById('iframe-loader');
  if (!frame || !loader) return;

  function onReady() {
    loader.classList.add('hidden');
    // Hapus dari DOM setelah transisi selesai agar tidak block klik
    setTimeout(function () { loader.style.display = 'none'; }, 400);
  }

  frame.addEventListener('load', onReady);

  // Fallback: jika iframe sudah complete saat script jalan (cache browser)
  if (frame.contentDocument && frame.contentDocument.readyState === 'complete') {
    onReady();
  }

  // Safety timeout: sembunyikan loader setelah 8 detik walau iframe belum load
  setTimeout(onReady, 8000);
}());
</script>
</body>
</html>