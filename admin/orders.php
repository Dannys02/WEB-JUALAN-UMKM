<?php
include '../config/db.php';

// Fitur Update Status & WA Redirect
if(isset($_GET['action'])) {
    $id = $_GET['id'];
    $action = $_GET['action'];
    
    if($action == 'setuju') {
        // Ambil data WA user dulu
        $data = mysqli_query($conn, "SELECT p.*, pr.nama as nama_produk FROM pesanan p JOIN produk pr ON p.produk_id = pr.id WHERE p.id = $id");
        $row = mysqli_fetch_assoc($data);
        
        mysqli_query($conn, "UPDATE pesanan SET status = 'setuju' WHERE id = $id");
        
        $pesan = "Halo " . $row['nama_pembeli'] . ", pesanan anda untuk *" . $row['nama_produk'] . "* telah kami **SETUJUI**. Mohon tunggu pengiriman.";
        $wa_link = "https://api.whatsapp.com/send?phone=" . $row['whatsapp'] . "&text=" . urlencode($pesan);
        
        echo "<script>window.open('$wa_link', '_blank'); window.location.href='orders.php';</script>";
    } else {
        mysqli_query($conn, "UPDATE pesanan SET status = 'tolak' WHERE id = $id");
        header("Location: orders.php");
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <script src="https://cdn.tailwindcss.com"></script>
    <title>Admin - Kelola Order</title>
</head>
<body class="bg-gray-100 p-8">
    <h1 class="text-2xl font-bold mb-6">Daftar Pesanan Masuk</h1>
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full text-left">
            <thead class="bg-gray-200">
                <tr>
                    <th class="p-4">Pembeli</th>
                    <th class="p-4">Produk</th>
                    <th class="p-4">Status</th>
                    <th class="p-4">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $res = mysqli_query($conn, "SELECT p.*, pr.nama as nama_produk FROM pesanan p JOIN produk pr ON p.produk_id = pr.id ORDER BY p.id DESC");
                while($order = mysqli_fetch_assoc($res)):
                ?>
                <tr class="border-b">
                    <td class="p-4"><?= $order['nama_pembeli'] ?> (<?= $order['whatsapp'] ?>)</td>
                    <td class="p-4"><?= $order['nama_produk'] ?></td>
                    <td class="p-4">
                        <span class="px-2 py-1 rounded text-xs <?= $order['status'] == 'pending' ? 'bg-yellow-200' : ($order['status'] == 'setuju' ? 'bg-green-200' : 'bg-red-200') ?>">
                            <?= strtoupper($order['status']) ?>
                        </span>
                    </td>
                    <td class="p-4">
                        <?php if($order['status'] == 'pending'): ?>
                            <a href="?action=setuju&id=<?= $order['id'] ?>" class="bg-green-500 text-white px-3 py-1 rounded text-sm">Setujui & WA</a>
                            <a href="?action=tolak&id=<?= $order['id'] ?>" class="bg-red-500 text-white px-3 py-1 rounded text-sm">Tolak</a>
                        <?php else: ?>
                            <span class="text-gray-400">Selesai</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
