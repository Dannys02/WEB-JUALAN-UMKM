<?php
include '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $produk_id = $_POST['produk_id'];
  $nama = mysqli_real_escape_string($koneksi, $_POST['nama_pembeli']);
  $wa = mysqli_real_escape_string($koneksi, $_POST['whatsapp']);
  $harga = mysqli_real_escape_string($koneksi, $_POST['harga']);
  $alamat = mysqli_real_escape_string($koneksi, $_POST['alamat']);

  $query = "INSERT INTO pesanan (nama_pembeli, whatsapp, harga, alamat, produk_id) VALUES ('$nama', '$wa', '$harga', '$alamat', '$produk_id')";

  if (mysqli_query($koneksi, $query)) {
    echo "<script>alert('Pesanan dikirim! Admin akan menghubungi via WA.'); window.location.href='../index.php';</script>";
  } else {
    echo "Error: " . mysqli_error($koneksi);
  }
}
?>