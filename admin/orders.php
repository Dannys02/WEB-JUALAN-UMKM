<?php
session_start();
require_once '../config/db.php'; // Menggunakan require_once lebih aman

/**
* PROTEKSI AKSES
* Mengecek session admin. Gunakan session_regenerate_id
* saat login untuk mencegah session fixation.
*/
if (!isset($_SESSION['admin_logged_in'])) {
  header("Location: login.php");
  exit;
}

/**
* LOGIKA PEMROSESAN (CONTROLLER)
* Memisahkan logika update status agar tidak bercampur dengan HTML.
*/
$action = $_GET['action'] ?? null;
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($action && $id) {
  if ($action === 'setuju') {
    // Ambil data detail pesanan dengan Prepared Statement
    $stmt = $koneksi->prepare("SELECT p.*, pr.nama as nama_produk FROM pesanan p
                                   JOIN produk pr ON p.produk_id = pr.id WHERE p.id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();

    if ($order) {
      // Update status ke database
      $update = $koneksi->prepare("UPDATE pesanan SET status = 'setuju' WHERE id = ?");
      $update->bind_param("i", $id);

      if ($update->execute()) {
        // Tambahkan spasi yang benar agar pesan tidak dempet
        $pesan = "Halo " . $order['nama_pembeli'] . ",\n\n" .
        "Pesanan Anda untuk *" . $order['nama_produk'] . "* dengan harga *Rp " . number_format($order['harga']) . "* telah kami *SETUJUI*.\n\n" .
        "Mohon tunggu informasi pengiriman selanjutnya. Terima kasih!";

        $wa_link = "https://api.whatsapp.com/send?phone=" . preg_replace('/[^0-9]/', '', $order['whatsapp']) .
        "&text=" . urlencode($pesan);

        // Simpan link di session untuk dipicu di halaman berikutnya
        $_SESSION['trigger_wa'] = $wa_link;

        header("Location: orders.php?status=success");
        exit;
      }
    }
  } elseif ($action === 'tolak') {
    $update = $koneksi->prepare("UPDATE pesanan SET status = 'tolak' WHERE id = ?");
    $update->bind_param("i", $id);
    $update->execute();
    header("Location: orders.php?status=rejected");
    exit;
  }
}

/**
* PENGAMBILAN DATA UNTUK VIEW
*/
$query = "SELECT p.*, pr.nama as nama_produk
          FROM pesanan p
          JOIN produk pr ON p.produk_id = pr.id
          ORDER BY p.id DESC";
$all_orders = mysqli_query($koneksi, $query);
$count = mysqli_num_rows($all_orders); // Hitung jumlah baris
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.tailwindcss.com"></script>
  <title>Admin Dashboard - Kelola Pesanan</title>
</head>
<body class="bg-slate-50 min-h-screen p-4 md:p-8">

  <div class="max-w-6xl mx-auto">
    <header class="flex justify-between items-center mb-8">
      <div>
        <h1 class="text-3xl font-extrabold text-slate-800">Daftar Pesanan</h1>
        <p class="text-slate-500 max-w-xs">
          Kelola konfirmasi pembayaran dan pengiriman
        </p>
      </div>
      <a href="logout.php" class="text-red-600 hover:underline font-medium">Keluar</a>
    </header>

    <?php if (isset($_GET['status'])): ?>
    <div class="mb-4 p-4 rounded-lg bg-green-100 text-green-700 border border-green-200">
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
              <td class="p-4 text-slate-700">
                <?= htmlspecialchars($row['nama_produk']) ?>
              </td>
              <td class="p-4 text-slate-700">
                Rp <?= htmlspecialchars(number_format($row['harga']) ?? 'Tidak terdeteksi') ?>
              </td>
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
                <div class="flex justify-center gap-2">
                  <a href="?action=setuju&id=<?= (int)$row['id'] ?>"
                    onclick="return confirm('Setujui pesanan ini?')"
                    class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-1.5 rounded-lg text-sm font-medium transition-all">
                    Setujui & WA
                  </a>
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
            <?php endwhile; // Penutup while diletakkan SETELAH kode baris tabel ?>
            <?php else : ?>
            <tr>
              <td colspan="5" class="p-12 text-center">
                <div class="text-slate-400">
                  <svg class="mx-auto h-12 w-12 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                  </svg>
                  <p class="text-lg font-semibold text-slate-500">
                    Tidak ada pesanan masuk
                  </p>
                  <p class="text-sm">
                    Semua data pesanan akan muncul di sini secara otomatis.
                  </p>
                </div>
              </td>
            </tr>
            <?php endif; ?>
          </tbody>


        </table>
      </div>
    </div>
  </div>
  <script>
    <?php if (isset($_SESSION['trigger_wa'])): ?>
    // Buka WhatsApp di tab baru
    const waWindow = window.open('<?= $_SESSION['trigger_wa'] ?>', '_blank');

    // Hapus session agar tidak terbuka terus-menerus saat refresh
    <?php unset($_SESSION['trigger_wa']); ?>

    // Jika diblokir browser, beri peringatan kecil
    if (!waWindow || waWindow.closed || typeof waWindow.closed == 'undefined') {
      alert('Mohon izinkan pop-up untuk membuka WhatsApp secara otomatis.');
    }
    <?php endif; ?>
  </script>

</body>
</html>