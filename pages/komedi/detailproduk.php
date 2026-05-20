<?php
  $kode = isset($_GET['kode']) ? $_GET['kode'] : null;

  if (!$kode) {
    echo "<p class='text-red-600'>Kode produk tidak ditemukan.</p>";
    exit;
  }

  $url = "https://opensheet.elk.sh/17wqbq_SCdfePCk_3firXNuKt_4r_kzMDmOJbGdN6Bbk/detail-katalog";
  $response = file_get_contents($url);
  $data = json_decode($response, true);

  $product = null;
  foreach ($data as $item) {
    if ($item['Code'] === $kode) {
      $product = $item;
      break;
    }
  }

  if (!$product) {
    echo "<p class='text-red-600'>Produk tidak ditemukan.</p>";
    exit;
  }

  $basePrice = intval($product['Price']);
  $discount = floatval($product['Discount']) ?: 0;
  $discountNote = $product['DiscountNote'] ?: '';
  $discountEnd = $product['DiscountEndDate'] ? new DateTime($product['DiscountEndDate']) : null;
  $now = new DateTime();

  $hasDiscount = $discount > 0 && !is_nan($basePrice) && (!$discountEnd || $discountEnd > $now);
  $discountedPrice = $hasDiscount ? round($basePrice - ($basePrice * $discount / 100)) : $basePrice;

  $priceFormatted = "Rp " . number_format($discountedPrice, 0, ',', '.');
  $originalPriceFormatted = "Rp " . number_format($basePrice, 0, ',', '.');

  // FIX: gunakan www + path baru /komedi/
  $imageSrc = isset($product['Image']) && $product['Image']
    ? "https://www.parokitulungagung.org/komedi/katalog/show.php?file=" . $product["Image"]
    : "https://via.placeholder.com/400x300?text=No+Image";
?>

<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1" name="viewport"/>
    <link rel="icon" href="/favicon.ico" type="image/x-icon" />
    <title>Detail Baju</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&amp;display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="style.css?v=<?= filemtime(__DIR__ . '/style.css'  ) ?>">
  </head>
  <body class="bg-gray-50 min-h-screen flex flex-col">
    <?php include('headerkatalog.php'); ?>

    <main class="container mx-auto px-4 py-10 flex-grow max-w-4xl">
      <!-- FIX: link kembali ke URL bersih /komedi -->
      <a class="inline-flex items-center text-indigo-600 hover:text-indigo-800 mb-6" href="/komedi">
        <i class="fas fa-arrow-left mr-2"></i> Kembali ke Katalog
      </a>

      <div class="relative bg-white rounded-lg shadow-md overflow-hidden flex flex-col md:flex-row">
        <div class="relative md:w-1/2 flex items-center justify-center p-4">
          <canvas id="productCanvas" class="mx-auto border max-w-full h-auto"></canvas>
          <?php if ($hasDiscount): ?>
            <div class="absolute top-2 left-2 bg-red-600 text-white text-xs px-2 py-1 rounded shadow">Diskon</div>
          <?php endif; ?>
        </div>

        <div class="p-6 flex flex-col justify-between md:w-1/2">
          <div>
            <h2 class="text-3xl font-bold text-gray-900 mb-4"><?php echo htmlspecialchars($product['Title'] ?? '-'); ?></h2>
            <p class="text-gray-700 mb-6 leading-relaxed"><?php echo nl2br(htmlspecialchars($product['Description'] ?? '-')); ?></p>
            <?php if ($hasDiscount): ?>
              <p class="text-sm text-red-600 mb-1"><?php echo htmlspecialchars($discountNote); ?></p>
            <?php endif; ?>
          </div>

          <div>
            <?php if ($hasDiscount): ?>
              <p class="text-gray-500 line-through text-base"><?php echo $originalPriceFormatted; ?></p>
              <p class="text-2xl font-extrabold text-indigo-600"><?php echo $priceFormatted; ?></p>
            <?php else: ?>
              <p class="text-2xl font-extrabold text-indigo-600"><?php echo $priceFormatted; ?></p>
            <?php endif; ?>

            <a href="<?php echo htmlspecialchars($product['Whatsapp']); ?>" target="_blank" rel="noopener noreferrer"
              class="w-full block text-center bg-green-600 hover:bg-indigo-700 text-white font-semibold py-3 rounded-md transition-colors duration-300 mt-4">
              Whatsapp
            </a>
            <a href="<?php echo !empty($product['Shopee']) ? htmlspecialchars($product['Shopee']) : '#'; ?>"
              <?php echo !empty($product['Shopee']) ? 'target="_blank"' : 'onclick="showShopeeModal()"'; ?>
              class="w-full block text-center <?php echo !empty($product['Shopee']) ? 'bg-orange-600 hover:bg-indigo-700' : 'bg-gray-400 hover:bg-gray-500'; ?> text-white font-semibold py-3 rounded-md transition-colors duration-300 mt-2">
              Shopee
            </a>
          </div>
        </div>
      </div>

      <!-- FIX: link size guide ke path baru /komedi/ -->
      <a href="/komedi/sizeguidekomedi.php" class="inline-block bg-indigo-100 text-red font-medium px-5 py-2 rounded-lg hover:bg-indigo-700 transition-all duration-200 shadow-sm">
        Lihat Panduan Ukuran
      </a>
    </main>

    <footer class="bg-white border-t mt-12">
      <div class="container mx-auto px-4 py-6 text-center text-gray-600 text-sm">
        Copyright © KoMeDi (Komsos Merchandise Division)
      </div>
    </footer>

    <script>
      const canvas = document.getElementById('productCanvas');
      const ctx = canvas.getContext('2d');
      const img = new Image();
      img.crossOrigin = 'anonymous';
      // FIX: gunakan www + path baru, nilai sudah disiapkan di PHP
      img.src = '<?php echo htmlspecialchars($imageSrc, ENT_QUOTES); ?>';

      img.onload = function() {
        canvas.width = img.width;
        canvas.height = img.height;
        ctx.drawImage(img, 0, 0, img.width, img.height);
        ctx.save();
        ctx.translate(img.width / 2, img.height / 2);
        ctx.rotate(-Math.PI / 4);
        ctx.font = `${img.width / 20}px sans-serif`;
        ctx.fillStyle = 'rgba(128,128,128,0.3)';
        ctx.textAlign = 'center';
        ctx.fillText('parokitulungagung.org', 0, 0);
        ctx.restore();
      };

      function showShopeeModal() {
        document.getElementById('shopeeModal').classList.remove('hidden');
      }

      function closeShopeeModal() {
        document.getElementById('shopeeModal').classList.add('hidden');
      }
    </script>

    <div id="shopeeModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
      <div class="bg-white p-6 rounded-lg shadow-lg max-w-sm text-center">
        <h2 class="text-xl font-semibold mb-4 text-gray-800">Produk belum tersedia di Shopee</h2>
        <p class="text-gray-600 mb-6">Silakan hubungi admin langsung melalui WhatsApp untuk informasi lebih lanjut.</p>
        <a href="https://wa.me/6285183068895" target="_blank" class="bg-green-600 hover:bg-green-700 text-white px-5 py-2 rounded-md font-semibold transition">Chat WhatsApp</a>
        <button onclick="closeShopeeModal()" class="mt-4 block mx-auto text-gray-500 hover:text-gray-700">Tutup</button>
      </div>
    </div>
  </body>
</html>