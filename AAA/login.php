<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if (current_user($conn)) {
    redirect('dashboard.php');
}

$flash = get_flash();
$errors = [];
$username = '';

if (is_post()) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $errors[] = 'Username dan password wajib diisi.';
    } elseif (!attempt_login($conn, $username, $password)) {
        $errors[] = 'Username atau password tidak sesuai.';
    }

    if (!$errors) {
        set_flash('success', 'Login berhasil.');
        redirect('dashboard.php');
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | SparePrud</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-body">
<div class="login-layout">
    <section class="login-card">
        <div class="login-brand">
            <div class="brand-logo">SP</div>
            <div>
                <h1>SparePrud</h1>
                <p>Aplikasi penjualan sparepart motor sederhana.</p>
            </div>
        </div>

        <div class="login-copy">
            <h2>Masuk ke sistem</h2>
            <p>Gunakan akun admin untuk kelola produk dan konfirmasi pembelian, atau akun customer untuk berbelanja.</p>
        </div>

        <?php if ($flash): ?>
            <div class="flash flash-<?= e($flash['type']); ?>">
                <span><?= e($flash['message']); ?></span>
            </div>
        <?php endif; ?>

        <?php if ($errors): ?>
            <div class="flash flash-error">
                <?php foreach ($errors as $error): ?>
                    <span><?= e($error); ?></span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="post" class="form-grid">
            <label>
                <span>Username</span>
                <input type="text" name="username" value="<?= e($username); ?>" placeholder="Masukkan username">
            </label>

            <label>
                <span>Password</span>
                <input type="password" name="password" placeholder="Masukkan password">
            </label>

            <button type="submit" class="btn btn-primary btn-full">Login</button>
        </form>

        <div class="login-demo">
            <div>
                <strong>Akun Admin</strong>
                <p>Username: admin</p>
                <p>Password: admin123</p>
            </div>
            <div>
                <strong>Akun Customer</strong>
                <p>Username: budi</p>
                <p>Password: customer123</p>
            </div>
        </div>
    </section>

    <section class="login-showcase">
        <div class="showcase-panel">
            <p class="eyebrow">Fitur Minimum</p>
            <h2>Simple, rapi, dan cukup untuk demo CRUD + transaksi.</h2>
            <ul>
                <li>Login dan logout untuk admin serta customer</li>
                <li>Admin dapat input dan mengelola data sparepart</li>
                <li>Customer dapat membuat pembelian</li>
                <li>Admin dapat mengonfirmasi pembelian</li>
            </ul>
        </div>
    </section>
</div>
</body>
</html>
