<?php

declare(strict_types=1);

function render_header(string $title, array $user): void
{
    $flash = get_flash();
    $roleLabel = $user['role'] === 'admin' ? 'Admin' : 'Customer';
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= e($title); ?> | SparePrud</title>
        <link rel="stylesheet" href="assets/css/style.css">
    </head>
    <body>
    <div class="app-shell">
        <aside class="sidebar">
            <div class="brand-block">
                <div class="brand-logo">SP</div>
                <div>
                    <h1>SparePrud</h1>
                    <p>E-Commerce Sparepart Motor</p>
                </div>
            </div>

            <nav class="sidebar-nav">
                <a class="<?= nav_active(['dashboard.php']); ?>" href="dashboard.php">Dashboard</a>
                <a class="<?= nav_active(['produk.php']); ?>" href="produk.php"><?= $user['role'] === 'admin' ? 'Kelola Produk' : 'Daftar Produk'; ?></a>
                <a class="<?= nav_active(['pembelian.php', 'beli.php']); ?>" href="pembelian.php"><?= $user['role'] === 'admin' ? 'Konfirmasi Pembelian' : 'Riwayat Pembelian'; ?></a>
            </nav>
        </aside>

        <main class="main-panel">
            <header class="topbar">
                <div>
                    <p class="eyebrow">Panel <?= e($roleLabel); ?></p>
                    <h2><?= e($title); ?></h2>
                </div>
                <div class="topbar-actions">
                    <div class="user-chip">
                        <span class="avatar"><?= e(first_letter($user['username'])); ?></span>
                        <div>
                            <strong><?= e($user['username']); ?></strong>
                            <small><?= e($user['email']); ?></small>
                        </div>
                    </div>
                    <a class="btn btn-outline" href="logout.php">Logout</a>
                </div>
            </header>

            <?php if ($flash): ?>
                <div class="flash flash-<?= e($flash['type']); ?>" data-flash>
                    <span><?= e($flash['message']); ?></span>
                    <button type="button" class="flash-close" data-dismiss-flash>&times;</button>
                </div>
            <?php endif; ?>

            <section class="page-content">
    <?php
}

function render_footer(): void
{
    ?>
            </section>
        </main>
    </div>
    <script src="assets/js/app.js"></script>
    </body>
    </html>
    <?php
}
