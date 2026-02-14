<?php
session_start();

// 1. CEK PERANGKAP BOT (Honeypot)
if (!empty($_POST['perangkap'])) {
  die("Bot detected!"); // Jika terisi, langsung matikan script
}

// 2. CEK JEDA WAKTU (Rate Limit)
if (isset($_SESSION['last_submit']) && (time() - $_SESSION['last_submit'] < 10)) {
  echo "<script>alert('Mohon tunggu sebentar.'); history.back();</script>";
  exit;
}
$_SESSION['last_submit'] = time();

include '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  // 1. Sanitasi & Validasi Input (WAJIB)
  $produk_id = mysqli_real_escape_string($koneksi, $_POST['produk_id']);
  $nama = htmlspecialchars(mysqli_real_escape_string($koneksi, $_POST['nama_pembeli']));
  $alamat = htmlspecialchars(mysqli_real_escape_string($koneksi, $_POST['alamat']));
  $stok = (int)$_POST['stok'];
  $harga = (int)$_POST['harga'];

  // Bersihkan nomor WA agar hanya angka (menghindari error link WA)
  $wa_pembeli = preg_replace('/[^0-9]/', '', $_POST['whatsapp']);

  // Validasi jika stok diisi 0 atau negatif
  if ($stok < 1) {
    echo "<script>alert('Jumlah pesanan tidak valid!'); history.back();</script>";
    exit;
  }

  $total = $harga * $stok;

  // 2. Simpan ke Database
  $query = "INSERT INTO pesanan (nama_pembeli, whatsapp, stok, harga, alamat, produk_id)
            VALUES ('$nama', '$wa_pembeli', '$stok', '$harga', '$alamat', '$produk_id')";

  if (mysqli_query($koneksi, $query)) {
    // 3. Ambil Nama Produk (Agar Admin tidak bingung ID berapa)
    $res = mysqli_query($koneksi, "SELECT nama FROM produk WHERE id='$produk_id' LIMIT 1");
    $p = mysqli_fetch_assoc($res);
    $nama_produk = ($p) ? $p['nama'] : "Produk tidak ditemukan";

    // 4. Setup Pesan WhatsApp yang Rapi
    $nomor_admin = "6285645837298";

    $pesan = "*PESANAN BARU DARI WEB*\n"
    . "--------------------------\n"
    . "📦 *Produk:* " . $nama_produk . "\n"
    . "🔢 *Jumlah:* " . $stok . " pcs\n"
    . "💰 *Total:* Rp " . number_format($total, 0, ',', '.') . "\n"
    . "--------------------------\n"
    . "*Data Pembeli:*\n"
    . "👤 *Nama:* " . $nama . "\n"
    . "📱 *WA:* " . $wa_pembeli . "\n"
    . "📍 *Alamat:* " . $alamat . "\n\n"
    . "Mohon segera diproses ya Min!";

    $url_wa = "https://api.whatsapp.com/send?phone=" . $nomor_admin . "&text=" . urlencode($pesan);

    // 5. Redirect dengan Alert
    echo "<script>
            alert('Pesanan Terkirim! Lanjutkan konfirmasi ke WhatsApp Admin.');
            window.location.href='$url_wa';
          </script>";
  } else {
    // Error handling yang lebih user-friendly
    echo "Terjadi kesalahan sistem. Silakan coba lagi.";
  }
}
?>