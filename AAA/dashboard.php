<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout.php';

$user = require_login($conn);

$summary = [
    'produk' => 0,
    'pembelian' => 0,
    'menunggu' => 0,
];

if ($user['role'] === 'admin') {
    $summary['produk'] = (int) mysqli_fetch_row(mysqli_query($conn, 'SELECT COUNT(*) FROM produk'))[0];
    $summary['pembelian'] = (int) mysqli_fetch_row(mysqli_query($conn, 'SELECT COUNT(*) FROM pembelian'))[0];
    $summary['menunggu'] = (int) mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM pembelian WHERE status = 'menunggu'"))[0];

    $recentQuery = "
        SELECT p.id_pembelian, u.username, pr.nama_produk, p.jumlah, p.total_bayar, p.status, p.tanggal_transaksi
        FROM pembelian p
        JOIN users u ON u.id = p.user_id
        JOIN produk pr ON pr.kode_produk = p.kode_produk
        ORDER BY p.tanggal_transaksi DESC
        LIMIT 5
    ";
} else {
    $summary['produk'] = (int) mysqli_fetch_row(mysqli_query($conn, 'SELECT COUNT(*) FROM produk'))[0];

    $stmt = mysqli_prepare($conn, 'SELECT COUNT(*) FROM pembelian WHERE user_id = ?');
    mysqli_stmt_bind_param($stmt, 's', $user['id']);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $summary['pembelian']);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) FROM pembelian WHERE user_id = ? AND status = 'menunggu'");
    mysqli_stmt_bind_param($stmt, 's', $user['id']);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $summary['menunggu']);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    $recentQuery = "
        SELECT p.id_pembelian, pr.nama_produk, p.jumlah, p.total_bayar, p.status, p.tanggal_transaksi
        FROM pembelian p
        JOIN produk pr ON pr.kode_produk = p.kode_produk
        WHERE p.user_id = '" . mysqli_real_escape_string($conn, $user['id']) . "'
        ORDER BY p.tanggal_transaksi DESC
        LIMIT 5
    ";
}

$recentTransactions = mysqli_query($conn, $recentQuery);

$productPreview = mysqli_query(
    $conn,
    "SELECT p.kode_produk, p.nama_produk, p.harga, p.stok, b.nama_brand, k.nama_kategori
     FROM produk p
     JOIN brand b ON b.id = p.brand_id
     JOIN kategori k ON k.id = p.kategori_id
     ORDER BY p.nama_produk ASC
     LIMIT 4"
);

render_header('Dashboard', $user);
?>
<div class="stats-grid">
    <article class="stat-card">
        <span class="stat-label">Total Produk</span>
        <strong><?= e((string) $summary['produk']); ?></strong>
        <p>Data sparepart aktif di katalog.</p>
    </article>
    <article class="stat-card">
        <span class="stat-label"><?= $user['role'] === 'admin' ? 'Total Pembelian' : 'Pembelian Saya'; ?></span>
        <strong><?= e((string) $summary['pembelian']); ?></strong>
        <p><?= $user['role'] === 'admin' ? 'Seluruh transaksi yang masuk.' : 'Total transaksi yang pernah dibuat.'; ?></p>
    </article>
    <article class="stat-card">
        <span class="stat-label">Status Menunggu</span>
        <strong><?= e((string) $summary['menunggu']); ?></strong>
        <p><?= $user['role'] === 'admin' ? 'Perlu tindakan konfirmasi.' : 'Sedang menunggu konfirmasi admin.'; ?></p>
    </article>
</div>

<div class="content-grid">
    <section class="panel">
        <div class="panel-head">
            <div>
                <p class="eyebrow">Produk</p>
                <h3>Ringkasan katalog</h3>
            </div>
            <a class="btn btn-outline" href="produk.php"><?= $user['role'] === 'admin' ? 'Kelola Produk' : 'Lihat Semua'; ?></a>
        </div>
        <div class="product-grid">
            <?php while ($product = mysqli_fetch_assoc($productPreview)): ?>
                <article class="product-card">
                    <div class="product-card-top">
                        <span class="tag"><?= e($product['nama_kategori']); ?></span>
                        <span class="muted"><?= e($product['nama_brand']); ?></span>
                    </div>
                    <h4><?= e($product['nama_produk']); ?></h4>
                    <p class="muted">Kode: <?= e($product['kode_produk']); ?></p>
                    <div class="product-card-bottom">
                        <strong><?= e(format_rupiah($product['harga'])); ?></strong>
                        <span>Stok: <?= e((string) $product['stok']); ?></span>
                    </div>
                </article>
            <?php endwhile; ?>
        </div>
    </section>

    <section class="panel">
        <div class="panel-head">
            <div>
                <p class="eyebrow">Transaksi</p>
                <h3><?= $user['role'] === 'admin' ? 'Pembelian terbaru' : 'Riwayat terbaru'; ?></h3>
            </div>
            <a class="btn btn-outline" href="pembelian.php"><?= $user['role'] === 'admin' ? 'Proses Pembelian' : 'Lihat Riwayat'; ?></a>
        </div>

        <div class="table-wrap">
            <table class="data-table">
                <thead>
                <tr>
                    <th>ID</th>
                    <?php if ($user['role'] === 'admin'): ?>
                        <th>User</th>
                    <?php endif; ?>
                    <th>Produk</th>
                    <th>Jumlah</th>
                    <th>Total</th>
                    <th>Status</th>
                </tr>
                </thead>
                <tbody>
                <?php if (mysqli_num_rows($recentTransactions) === 0): ?>
                    <tr>
                        <td colspan="<?= $user['role'] === 'admin' ? '6' : '5'; ?>" class="empty-state">Belum ada data transaksi.</td>
                    </tr>
                <?php else: ?>
                    <?php while ($transaction = mysqli_fetch_assoc($recentTransactions)): ?>
                        <tr>
                            <td><?= e($transaction['id_pembelian']); ?></td>
                            <?php if ($user['role'] === 'admin'): ?>
                                <td><?= e($transaction['username']); ?></td>
                            <?php endif; ?>
                            <td><?= e($transaction['nama_produk']); ?></td>
                            <td><?= e((string) $transaction['jumlah']); ?></td>
                            <td><?= e(format_rupiah($transaction['total_bayar'])); ?></td>
                            <td><span class="status-badge status-<?= e($transaction['status']); ?>"><?= e(ucfirst($transaction['status'])); ?></span></td>
                        </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
<?php render_footer(); ?>
