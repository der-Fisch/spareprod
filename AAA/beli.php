<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout.php';

$user = require_role($conn, 'customer');
$errors = [];
$kodeProduk = trim($_GET['kode'] ?? $_POST['kode_produk'] ?? '');

if ($kodeProduk === '') {
    set_flash('error', 'Produk tidak ditemukan.');
    redirect('produk.php');
}

$stmt = mysqli_prepare(
    $conn,
    "SELECT p.kode_produk, p.nama_produk, p.harga, p.stok, p.tipe_kendaraan, b.nama_brand, k.nama_kategori
     FROM produk p
     JOIN brand b ON b.id = p.brand_id
     JOIN kategori k ON k.id = p.kategori_id
     WHERE p.kode_produk = ?
     LIMIT 1"
);
mysqli_stmt_bind_param($stmt, 's', $kodeProduk);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$product = mysqli_fetch_assoc($result) ?: null;
mysqli_stmt_close($stmt);

if (!$product) {
    set_flash('error', 'Produk tidak ditemukan.');
    redirect('produk.php');
}

$jumlah = 1;

if (is_post()) {
    $jumlah = (int) ($_POST['jumlah'] ?? 1);

    if ($jumlah < 1) {
        $errors[] = 'Jumlah pembelian minimal 1.';
    }

    if ($jumlah > (int) $product['stok']) {
        $errors[] = 'Jumlah melebihi stok yang tersedia.';
    }

    if (!$errors) {
        $purchaseId = generate_id('PBL');
        $totalBayar = $jumlah * (int) $product['harga'];
        $status = 'menunggu';

        $stmt = mysqli_prepare(
            $conn,
            'INSERT INTO pembelian (id_pembelian, user_id, kode_produk, jumlah, total_bayar, status) VALUES (?, ?, ?, ?, ?, ?)'
        );
        mysqli_stmt_bind_param($stmt, 'sssiis', $purchaseId, $user['id'], $product['kode_produk'], $jumlah, $totalBayar, $status);
        $success = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        if ($success) {
            set_flash('success', 'Pembelian berhasil dibuat dan sedang menunggu konfirmasi admin.');
            redirect('pembelian.php');
        }

        $errors[] = 'Terjadi kesalahan saat menyimpan pembelian.';
    }
}

render_header('Buat Pembelian', $user);
?>
<div class="content-grid buy-grid">
    <section class="panel">
        <div class="panel-head">
            <div>
                <p class="eyebrow">Detail Produk</p>
                <h3><?= e($product['nama_produk']); ?></h3>
            </div>
        </div>
        <div class="detail-list">
            <div><span>Kode</span><strong><?= e($product['kode_produk']); ?></strong></div>
            <div><span>Brand</span><strong><?= e($product['nama_brand']); ?></strong></div>
            <div><span>Kategori</span><strong><?= e($product['nama_kategori']); ?></strong></div>
            <div><span>Tipe Kendaraan</span><strong><?= e($product['tipe_kendaraan']); ?></strong></div>
            <div><span>Harga</span><strong><?= e(format_rupiah($product['harga'])); ?></strong></div>
            <div><span>Stok Tersedia</span><strong><?= e((string) $product['stok']); ?></strong></div>
        </div>
    </section>

    <section class="panel">
        <div class="panel-head">
            <div>
                <p class="eyebrow">Form Pembelian</p>
                <h3>Masukkan jumlah pembelian</h3>
            </div>
        </div>

        <?php if ($errors): ?>
            <div class="flash flash-error">
                <?php foreach ($errors as $error): ?>
                    <span><?= e($error); ?></span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="post" class="form-grid">
            <input type="hidden" name="kode_produk" value="<?= e($product['kode_produk']); ?>">

            <label>
                <span>Jumlah</span>
                <input type="number" name="jumlah" min="1" max="<?= e((string) $product['stok']); ?>" value="<?= e((string) $jumlah); ?>">
            </label>

            <label>
                <span>Estimasi Total</span>
                <input type="text" value="<?= e(format_rupiah((int) $product['harga'] * $jumlah)); ?>" readonly>
            </label>

            <div class="form-actions full-width">
                <button type="submit" class="btn btn-primary">Simpan Pembelian</button>
                <a class="btn btn-outline" href="produk.php">Kembali ke Produk</a>
            </div>
        </form>
    </section>
</div>
<?php render_footer(); ?>
