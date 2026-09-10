<?php
$hppList = [
  "Kaos Dewasa" => 55000,
  "Kaos Dewasa Polos" => 45000,
  "Kaos Anak" => 50000,
  "Kaos Anak Polos" => 40000,
  "Kaos Polo" => 75000,
  "Kaos Polo Polos" => 70000,
  "Topi" => 25000,
  "Mug" => 15000,
  "Totebag" => 30000,
  "Tas Serut Polos" => 0,
  "Tas Serut Hitam" => 30000
];

$status = "";
$totalLabaHarian = 0; // Variabel untuk menyimpan total laba keseluruhan

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $tanggal = $_POST["tanggal"];
  $barangList = $_POST["namaBarang"];
  $qtyList = $_POST["qty"];
  $hargaJualList = $_POST["hargaJual"];

  $url = "https://sheetdb.io/api/v1/fkcira8hmojju";

  // Menghitung total laba keseluruhan dari semua transaksi
  foreach ($barangList as $i => $namaBarang) {
    if (!$namaBarang || !$qtyList[$i] || !$hargaJualList[$i]) continue;

    $qty = (int) $qtyList[$i];
    $hargaJual = (int) $hargaJualList[$i];
    $hargaProduksi = $hppList[$namaBarang] ?? 0;

    $totalJual = $qty * $hargaJual;
    $laba = $hargaJual - $hargaProduksi;
    $totalLaba = $qty * $laba;

    // Menambahkan total laba keseluruhan
    $totalLabaHarian += $totalLaba;

    $data = [
      "data" => [
        "tanggal" => $tanggal,
        "namaBarang" => $namaBarang,
        "qty" => $qty,
        "hargaProduksi" => $hargaProduksi,
        "hargaJual" => $hargaJual,
        "totalJual" => $totalJual,
        "laba" => $laba,
        "totalLaba" => $totalLaba,
        // Kirim hanya total laba keseluruhan yang terakhir
        "totalLabaHarian" => ($i === count($barangList) - 1) ? $totalLabaHarian : ""
      ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    $result = curl_exec($ch);
    curl_close($ch);
  }

  $status = "Semua data berhasil dikirim ke Spreadsheet (via SheetDB).";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Penjualan Harian Komedi</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <script>
    function tambahBarang() {
      const container = document.getElementById("barangContainer");
      const template = document.querySelector(".barang-row-template");
      const clone = template.cloneNode(true);
      clone.classList.remove("hidden", "barang-row-template");
      container.appendChild(clone);
    }

    function hapusBaris(el) {
      el.closest(".grid").remove();
    }

    // Fungsi untuk menghitung total laba keseluruhan
    function hitungTotalLaba() {
      let totalLaba = 0;
      const qtyInputs = document.querySelectorAll('input[name="qty[]"]');
      const hargaJualInputs = document.querySelectorAll('input[name="hargaJual[]"]');
      const hargaProduksiList = <?php echo json_encode($hppList); ?>;
      
      qtyInputs.forEach((qtyInput, index) => {
        const qty = parseInt(qtyInput.value);
        const hargaJual = parseInt(hargaJualInputs[index].value);
        const namaBarang = document.querySelectorAll('select[name="namaBarang[]"]')[index].value;
        
        if (!isNaN(qty) && !isNaN(hargaJual) && namaBarang) {
          const hargaProduksi = hargaProduksiList[namaBarang] || 0;
          const laba = hargaJual - hargaProduksi;
          totalLaba += laba * qty;
        }
      });

      document.getElementById("totalLaba").textContent = `Total Laba: Rp ${totalLaba.toLocaleString()}`;
    }

    // Menambahkan event listener untuk input qty dan harga jual
    document.addEventListener('input', hitungTotalLaba);
    window.onload = () => {
      tambahBarang();
      hitungTotalLaba();
    };
  </script>
</head>
<body class="bg-gray-100">
  <div class="max-w-3xl mx-auto mt-10 p-6 bg-white rounded shadow">
    <div class="flex justify-between items-center mb-5">
      <img src="icon_komedi.png" alt="Logo" class="h-12">
      <h1 class="text-xl font-bold">Penjualan Harian Komedi</h1>
    </div>

    <?php if ($status): ?>
      <p class="bg-green-100 text-green-800 px-4 py-2 rounded mb-4"><?php echo $status; ?></p>
    <?php endif; ?>

    <div class="text-sm text-red-500 mb-4">
      <p>Harap diingat: Jangan menambahkan titik (.) atau koma (,) dalam penulisan angka/harga.</p>
    </div>

    <form method="post" class="space-y-6">
      <div>
        <label class="block mb-1 font-medium">Tanggal Pelaporan</label>
        <input type="date" name="tanggal" required class="w-full border px-3 py-2 rounded">
      </div>

      <div id="barangContainer" class="space-y-4">
        <!-- Template baris barang -->
        <div class="grid grid-cols-3 gap-4 items-end barang-row-template hidden">
          <div>
            <label class="block mb-1 text-sm font-medium">Nama Barang</label>
            <select name="namaBarang[]" class="w-full border px-3 py-2 rounded">
              <option value="">-- Pilih --</option>
              <?php foreach ($hppList as $barang => $hpp): ?>
                <option value="<?php echo $barang; ?>"><?php echo $barang; ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div>
            <label class="block mb-1 text-sm font-medium">Qty</label>
            <input type="number" name="qty[]" class="w-full border px-3 py-2 rounded" placeholder="0">
          </div>

          <div>
            <label class="block mb-1 text-sm font-medium">Harga Jual</label>
            <div class="flex">
              <input type="number" name="hargaJual[]" class="w-full border px-3 py-2 rounded-l" placeholder="0">
              <button type="button" onclick="hapusBaris(this)" class="bg-red-600 text-white px-3 rounded-r hover:bg-red-700">✕</button>
            </div>
          </div>
        </div>
      </div>

      <button type="button" onclick="tambahBarang()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
        + Tambah Barang
      </button>

      <div id="totalLaba" class="text-xl font-bold mt-4">Total Laba: Rp 0</div>

      <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded">
        Kirim Semua Data
      </button>
    </form>
  </div>

  <script>
    window.onload = () => {
      tambahBarang();
      hitungTotalLaba();
    };
  </script>
</body>
</html>
