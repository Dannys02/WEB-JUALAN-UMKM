<?php
session_start();
include '../config/db.php';

if (isset($_POST['login'])) {
  $username = $_POST['username'];
  $password = $_POST['password'];

  // Contoh username & password statis (untuk belajar)
  // Di dunia nyata, ini harusnya mengambil dari tabel 'users' di database
  if ($username == "Admin" && $password == "admin123") {
    $_SESSION['admin_logged_in'] = true;
    header("Location: orders.php");
    exit;
  } else {
    $error = "Username atau Password salah!";
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.tailwindcss.com"></script>
  <title>Login Admin</title>
</head>
<body class="bg-gray-200 flex items-center justify-center h-screen">
  <div class="bg-white p-8 rounded shadow-md w-96">
    <h2 class="text-2xl font-bold mb-4 text-center">Login Admin</h2>
    <?php if (isset($error)) echo "<p class='text-red-500 text-sm mb-4'>$error</p>"; ?>

    <form method="POST">
      <div class="mb-4">
        <label class="block text-sm font-semibold">Username</label>
        <input type="text" name="username" class="w-full border p-2 rounded focus:outline-blue-500" required>
      </div>
      <div class="mb-6">
        <label class="block text-sm font-semibold">Password</label>
        <input type="password" name="password" class="w-full border p-2 rounded focus:outline-blue-500" required>
      </div>
      <button type="submit" name="login" class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700">Masuk</button>
    </form>
  </div>
</body>
</html>