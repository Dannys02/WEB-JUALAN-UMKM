<?php include 'config/db.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <script src="https://cdn.tailwindcss.com"></script>
    <title>Katalog UMKM</title>
</head>
<body class="bg-gray-100">
    <nav class="bg-blue-600 p-4 text-white shadow-lg">
        <h1 class="text-xl font-bold">Toko UMKM Saya</h1>
    </nav>

    <div class="container mx-auto p-6 grid grid-cols-1 md:grid-cols-3 gap-6">
        <?php
        $query = mysqli_query($koneksi, "SELECT * FROM produk");
        while($row = mysqli_fetch_assoc($query)):
        ?>
        <div class="bg-white p-4 rounded-lg shadow-md">
            <img src="assets/img/<?= $row['gambar'] ?>" class="w-full h-48 object-cover rounded">
            <h2 class="text-xl font-bold mt-2"><?= $row['nama'] ?></h2>
            <p class="text-green-600 font-semibold">Rp <?= number_format($row['harga']) ?></p>
            
            <button onclick="openModal(<?= $row['id'] ?>, '<?= $row['nama'] ?>')" class="mt-4 w-full bg-blue-500 text-white py-2 rounded hover:bg-blue-700">Pesan Sekarang</button>
        </div>
        <?php endwhile; ?>
    </div>

    <div id="orderModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
        <div class="bg-white p-6 rounded-lg w-96">
            <h3 id="modalTitle" class="text-lg font-bold mb-4">Form Order</h3>
            <form action="api/create_order.php" method="POST">
                <input type="hidden" name="produk_id" id="produk_id">
                <input type="text" name="nama_pembeli" placeholder="Nama Anda" class="w-full border p-2 mb-3 rounded" required>
                <input type="number" name="whatsapp" placeholder="Nomor WA (Contoh: 62812...)" class="w-full border p-2 mb-3 rounded" required>
                <textarea name="alamat" placeholder="Alamat Lengkap" class="w-full border p-2 mb-3 rounded" required></textarea>
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="closeModal()" class="bg-gray-400 px-4 py-2 rounded">Batal</button>
                    <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded">Kirim Pesanan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal(id, nama) {
            document.getElementById('orderModal').classList.remove('hidden');
            document.getElementById('produk_id').value = id;
            document.getElementById('modalTitle').innerText = "Pesan: " + nama;
        }
        function closeModal() {
            document.getElementById('orderModal').classList.add('hidden');
        }
    </script>
</body>
</html>
