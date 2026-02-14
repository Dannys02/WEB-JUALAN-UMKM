<?php
session_start();
require_once '../config/db.php';

/**
* PROTEKSI AKSES
*/
if (!isset($_SESSION['admin_logged_in'])) {
  header("Location: login.php");
  exit;
}

/**
* LOGIKA PEMROSESAN (CONTROLLER)
*/
$action = $_GET['action'] ?? null;
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($action && $id) {
  if ($action === 'setuju') {
    // 1. Mulai Transaksi Database
    $koneksi->begin_transaction();

    try {
      // 2. Ambil data stok yang dipesan dan produk_id terlebih dahulu
      $stmt_info = $koneksi->prepare("SELECT produk_id, stok FROM pesanan WHERE id = ?");
      $stmt_info->bind_param("i", $id);
      $stmt_info->execute();
      $result_info = $stmt_info->get_result();
      $order_data = $result_info->fetch_assoc();

      if ($order_data) {
        $produk_id = $order_data['produk_id'];
        $jumlah_beli = $order_data['stok'];

        // 3. Update status pesanan jadi 'setuju'
        $update_order = $koneksi->prepare("UPDATE pesanan SET status = 'setuju' WHERE id = ?");
        $update_order->bind_param("i", $id);
        $update_order->execute();

        // 4. Kurangi stok di tabel produk
        // Query ini langsung mengurangi nilai stok yang ada di database
        $update_stock = $koneksi->prepare("UPDATE produk SET stok = stok - ? WHERE id = ?");
        $update_stock->bind_param("ii", $jumlah_beli, $produk_id);
        $update_stock->execute();

        // 5. Jika semua berhasil, simpan perubahan (commit)
        $koneksi->commit();

        header("Location: orders.php?status=success");
        exit;
      }
    } catch (Exception $e) {
      // 6. Jika ada error, batalkan semua perubahan
      $koneksi->rollback();
      header("Location: orders.php?status=error");
      exit;
    }

  } elseif ($action === 'tolak') {
    // ... kode tolak Anda tetap sama ...
    $update = $koneksi->prepare("UPDATE pesanan SET status = 'tolak' WHERE id = ?");
    $update->bind_param("i", $id);
    $update->execute();
    header("Location: orders.php?status=rejected");
    exit;
  }
}

/**
* PENGAMBILAN DATA
*/
$query = "SELECT p.*, pr.nama as nama_produk FROM pesanan p JOIN produk pr ON p.produk_id = pr.id ORDER BY p.id DESC";
$all_orders = mysqli_query($koneksi, $query);
$count = mysqli_num_rows($all_orders);
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.tailwindcss.com"></script>
  <title>Admin Dashboard - Kelola Pesanan</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
  <script>
    // FUNGSI HANDLE KONFIRMASI & WA
    function handleSetuju(url, waLink) {
      if (confirm('Apakah Anda yakin ingin MENYETUJUI pesanan ini?')) {
        // 1. Buka WhatsApp di tab baru
        window.open(waLink, '_blank');
        // 2. Redirect halaman utama untuk proses database
        window.location.href = url;
      }
    }
  </script>
</head>
<body class="bg-slate-50 min-h-screen p-4 md:p-8">

  <div class="max-w-6xl mx-auto">
    <header class="flex justify-between items-center mb-8">
      <div>
        <h1 class="text-3xl font-extrabold text-slate-800">Daftar Pesanan</h1>
        <p class="text-slate-500">
          Kelola konfirmasi pembayaran dan pengiriman
        </p>
      </div>
      <a href="logout.php" class="text-red-600 hover:underline font-medium">Keluar</a>
    </header>

    <?php if (isset($_GET['status'])): ?>
    <div id="status_sukses" class="mb-4 p-4 rounded-lg bg-green-100 text-green-700 border border-green-200">
      Aksi berhasil diproses!
    </div>
    <?php endif; ?>

    <div class="overflow-hidden">
      <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead class="bg-slate-100 border-b border-slate-200">
            <tr>
              <th class="p-4 font-semibold text-slate-700">Pembeli</th>
              <th class="p-4 font-semibold text-slate-700">Produk</th>
              <th class="p-4 font-semibold text-slate-700">Stok</th>
              <th class="p-4 font-semibold text-slate-700">Harga</th>
              <th class="p-4 font-semibold text-slate-700">Status</th>
              <th class="p-4 font-semibold text-slate-700 text-center">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <?php if ($count > 0): ?>
            <?php while ($row = mysqli_fetch_assoc($all_orders)): ?>
            <tr class="hover:bg-slate-50 transition-colors">
              <td class="p-4">
                <div class="font-medium text-slate-900">
                  <?= htmlspecialchars($row['nama_pembeli']) ?>
                </div>
                <div class="text-sm text-slate-500">
                  <?= htmlspecialchars($row['whatsapp']) ?>
                </div>
              </td>
              <td class="p-4 text-slate-700"><?= htmlspecialchars($row['nama_produk']) ?></td>
              <td class="p-4 text-slate-700"><?= htmlspecialchars($row['stok']) ?></td>
              <td class="p-4 text-slate-700">Rp <?= number_format($row['harga']) ?></td>
              <td class="p-4">
                <?php
                $statusStyle = [
                  'pending' => 'bg-amber-100 text-amber-700',
                  'setuju' => 'bg-emerald-100 text-emerald-700',
                  'tolak' => 'bg-rose-100 text-rose-700'
                ];
                $currentStatus = $row['status'] ?? 'pending';
                ?>
                <span class="px-2.5 py-1 rounded-full text-xs font-bold uppercase tracking-wider <?= $statusStyle[$currentStatus] ?>">
                  <?= htmlspecialchars($currentStatus) ?>
                </span>
              </td>
              <td class="p-4 text-center">
                <?php if ($currentStatus === 'pending'): ?>
                <?php
                // Persiapan Link WhatsApp
                $clean_phone = preg_replace('/[^0-9]/', '', $row['whatsapp']);
                if (substr($clean_phone, 0, 1) === '0') {
                  $clean_phone = '62' . substr($clean_phone, 1);
                }

                $pesan = "*KONFIRMASI PESANAN - " . strtoupper($row['nama_toko'] ?? 'TOKO KAMI') . "*\n"
                . "--------------------------\n"
                . "Halo *" . $row['nama_pembeli'] . "*, pesanan Anda telah kami *SETUJUI*.\n\n"
                . "📦 *Detail Produk:*\n"
                . "Nama: " . $row['nama_produk'] . "\n"
                . "Jumlah: " . $row['stok'] . " pcs\n"
                . "Total: Rp " . number_format($row['harga'] * $row['stok'], 0, ',', '.') . "\n"
                . "--------------------------\n"
                . "✅ *Status:* Siap Dikirim\n\n"
                . "Mohon tunggu informasi selanjutnya. Terima kasih sudah berbelanja!";

                $wa_link = "https://api.whatsapp.com/send?phone=" . $clean_phone . "&text=" . urlencode($pesan);
                $db_url = "?action=setuju&id=" . (int)$row['id'];
                ?>
                <div class="flex justify-center gap-2">
                  <button type="button"
                    onclick="handleSetuju('<?= $db_url ?>', '<?= $wa_link ?>')"
                    class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-1.5 rounded-lg text-sm font-medium transition-all">
                    Setujui & WA
                  </button>

                  <a href="?action=tolak&id=<?= (int)$row['id'] ?>"
                    onclick="return confirm('Tolak pesanan ini?')"
                    class="bg-white border border-rose-200 text-rose-600 hover:bg-rose-50 px-4 py-1.5 rounded-lg text-sm font-medium transition-all">
                    Tolak
                  </a>
                </div>
                <?php else : ?>
                <span class="text-slate-400 italic text-sm">Selesai diproses</span>
                <?php endif; ?>
              </td>
            </tr>
            <?php endwhile; ?>
            <?php else : ?>
            <tr><td colspan="6" class="p-12 text-center text-slate-400">Tidak ada pesanan masuk</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
  <script src="../assets/js/orders.js"></script>
</body>
</html>