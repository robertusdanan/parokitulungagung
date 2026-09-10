<?php
  // Memulai sesi PHP jika diperlukan
  // session_start();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Portfolio Album Foto Produk Merchandise</title>
  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="/style.css?v=<?= filemtime(__DIR__ . '/style.css'  ) ?>" />
  <style>
    /* Reset and base */
    *, *::before, *::after {
      margin: 0; padding: 0; box-sizing: border-box;
    }
    body {
      font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen,
        Ubuntu, Cantarell, 'Helvetica Neue', sans-serif;
      color: #222;
      background: #f7f8fa;
      line-height: 1.6;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }
    a {
      color: #0d47a1;
      text-decoration: none;
    }
    a:hover, a:focus {
      text-decoration: underline;
    }

    /* Main content */
    main {
      flex-grow: 1;
      padding: 24px 16px;
      max-width: 1200px;
      margin: 0 auto;
      width: 100%;
    }
    .heading-logo-container {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 16px;
      margin-bottom: 24px;
      flex-wrap: wrap;
    }
    .heading-logo-container img {
      height: 150px;
      width: 150px;
      object-fit: contain;
      user-select: none;
    }
    main h1 {
      font-size: 2.5rem;
      font-weight: 900;
      color: #0d47a1;
      white-space: nowrap;
    }
    /* Search bar */
    .search-container {
      max-width: 600px;
      margin: 0 auto 32px auto;
      display: flex;
      align-items: center;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 4px 8px rgb(13 71 161 / 0.15);
    }
    .search-container input[type="search"] {
      flex-grow: 1;
      border: none;
      padding: 12px 16px;
      font-size: 1rem;
      outline-offset: 2px;
    }
    .search-container input[type="search"]:focus {
      outline: 2px solid #0d47a1;
    }
    .search-container button {
      background: #0d47a1;
      border: none;
      color: white;
      padding: 0 16px;
      height: 44px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: background-color 0.3s ease;
    }
    .search-container button:hover, .search-container button:focus {
      background-color: #083d8b;
    }
    /* Gallery grid with modern card style */
    .gallery {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
      gap: 32px;
    }
    .card {
      background: white;
      border-radius: 16px;
      box-shadow: 0 6px 15px rgb(0 0 0 / 0.1);
      overflow: hidden;
      display: flex;
      flex-direction: column;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .card:hover, .card:focus-within {
      transform: translateY(-6px);
      box-shadow: 0 20px 40px rgb(0 0 0 / 0.15);
    }
    .card img {
      width: 100%;
      display: block;
      aspect-ratio: 4 / 3;
      object-fit: cover;
    }
    .card-content {
      padding: 16px 20px;
      flex-grow: 1;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }
    .card-title {
      font-weight: 700;
      font-size: 1.1rem;
      color: #0d47a1;
      margin-bottom: 8px;
      user-select: text;
    }
    .card-desc {
      color: #444;
      font-size: 0.9rem;
      line-height: 1.4;
      user-select: text;
    }
    /* Responsive breakpoints */
    @media (min-width: 768px) {
      main {
        padding: 48px 32px;
      }
    }
    @media (min-width: 1024px) {
      .heading-logo-container {
        flex-wrap: nowrap;
      }
      main h1 {
        font-size: 3rem;
      }
      .gallery {
        gap: 40px;
      }
    }
    .modal {
      position: fixed;
      top: 0;
      left: 0;
      width: 100vw;
      height: 100vh;
      background-color: rgba(0, 0, 0, 0.8);
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 1000;
    }

    .modal.is-open {
      display: flex;
    }

    .modal img {
      max-width: 90vw;
      max-height: 90vh;
      object-fit: contain;
      border-radius: 8px;
      box-shadow: 0 0 20px rgba(0,0,0,0.5);
    }

    .modal-close {
      position: absolute;
      top: 20px;
      right: 30px;
      font-size: 2rem;
      color: white;
      cursor: pointer;
      z-index: 1001;
    }
    .pagination {
      display: flex;
      justify-content: center;
      gap: 0.5rem;
      margin-top: 2rem;
      flex-wrap: wrap;
    }
    .page-btn {
      background: #f0f0f0;
      border: none;
      padding: 8px 12px;
      font-size: 1rem;
      cursor: pointer;
      border-radius: 6px;
      transition: background 0.2s ease;
    }
    .page-btn:hover {
      background: #ddd;
    }
    .page-btn.active {
      background: #333;
      color: #fff;
      font-weight: bold;
    }
    .image-wrapper {
      position: relative;
      overflow: hidden;
    }

    .image-wrapper{
      position:relative;
      width:100%;
      aspect-ratio:4/3; /* menjaga rasio grid */
      background:#eee;
    }

    .spinner{
      position:absolute;
      top:50%;left:50%;
      width:40px;height:40px;
      margin:-20px 0 0 -20px;
      border:4px solid rgba(0,0,0,.15);
      border-top-color:#0d47a1;
      border-radius:50%;
      animation:spin 0.8s linear infinite;
    }

    @keyframes spin{to{transform:rotate(360deg)}}

    .card img{transition:opacity .4s ease;opacity:0;}

    /* Material Icons setup */
    @import url('https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined');

    .material-symbols-outlined {
      font-variation-settings:
        'FILL' 0,
        'wght' 400,
        'GRAD' 0,
        'opsz' 48;
      font-family: 'Material Symbols Outlined';
      font-size: 24px;
      user-select: none;
    }
  </style>

</head>
<body>
 <body class="bg-gray-50 min-h-screen flex flex-col">

<div id="header-katalog"></div>
  <main>
    <div class="heading-logo-container">
      <img src="/pages/komedi/icon_komedi.png" alt="KoMeDi" />
      <h1>Album Foto Produk</h1>
    </div>

    <section class="search-container" role="search" aria-label="Cari album foto">
      <input
        id="search-input"
        type="search"
        placeholder="Cari album berdasarkan nama produk..."
        aria-label="Cari album"
        autocomplete="off"
        spellcheck="false"
      />
      <button id="search-button" aria-label="Mulai pencarian">
        <span class="material-symbols-outlined">search</span>
      </button>
    </section>

    <section class="gallery" id="gallery" aria-live="polite" aria-atomic="true" tabindex="0">
      <!-- Cards inserted by JavaScript -->
    </section>

    <!-- Perbaikan: pagination dipindah ke dalam <main> -->
    <div id="pagination" class="pagination"></div>
  </main>

  <!-- Modal ditaruh di luar <main> tapi tetap dalam <body> -->
<div id="image-modal" class="modal">
  <span class="modal-close">&times;</span>
  <img id="modal-image" alt="" />
</div>

  <!-- JS diletakkan di akhir body -->
  <script src="script.js?v=<?= file_exists(__DIR__ . '/script.js') ? filemtime(__DIR__ . '/script.js') : 1 ?>"></script>
  <script>
    // Menggunakan PHP untuk memuat header
    <?php
      include('headerkatalog.php');
    ?>
  </script>
   <footer class="bg-white border-t mt-12">
   <div class="container mx-auto px-4 py-6 text-center text-gray-600 text-sm">
   Copyright © KoMeDi (Komsos Merchandise Division)
   </div>
  </footer>
</body>

</html>
