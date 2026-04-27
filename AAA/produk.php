<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout.php';

$user = require_login($conn);
$isAdmin = $user['role'] === 'admin';
$errors = [];

if ($isAdmin && isset($_GET['delete'])) {
    $kodeProduk = trim($_GET['delete']);

    $stmt = mysqli_prepare($conn, 'SELECT COUNT(*) FROM pembelian WHERE kode_produk = ?');
    mysqli_stmt_bind_param($stmt, 's', $kodeProduk);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $purchaseCount);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    if ((int) $purchaseCount > 0) {
        set_flash('error', 'Produk tidak bisa dihapus karena sudah dipakai di transaksi.');
    } else {
        $stmt = mysqli_prepare($conn, 'DELETE FROM produk WHERE kode_produk = ?');
        mysqli_stmt_bind_param($stmt, 's', $kodeProduk);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        set_flash('success', 'Produk berhasil dihapus.');
    }

    redirect('produk.php');
}

$editProduct = null;

if ($isAdmin && isset($_GET['edit'])) {
    $kodeProduk = trim($_GET['edit']);
    $stmt = mysqli_prepare($conn, 'SELECT kode_produk, nama_produk, tipe_kendaraan, kategori_id, harga, stok, gambar, brand_id FROM produk WHERE kode_produk = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 's', $kodeProduk);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $editProduct = mysqli_fetch_assoc($result) ?: null;
    mysqli_stmt_close($stmt);
}

if ($isAdmin && is_post()) {
    $formMode = $_POST['form_mode'] ?? 'create';
    $kodeProduk = trim($_POST['kode_produk'] ?? '');
    $namaProduk = trim($_POST['nama_produk'] ?? '');
    $tipeKendaraan = trim($_POST['tipe_kendaraan'] ?? '');
    $kategoriId = trim($_POST['kategori_id'] ?? '');
    $harga = trim($_POST['harga'] ?? '');
    $stok = trim($_POST['stok'] ?? '');
    $gambar = trim($_POST['gambar'] ?? '');
    $brandId = trim($_POST['brand_id'] ?? '');
    $originalKode = trim($_POST['original_kode'] ?? '');

    if ($kodeProduk === '' || $namaProduk === '' || $tipeKendaraan === '' || $kategoriId === '' || $harga === '' || $stok === '' || $brandId === '') {
        $errors[] = 'Semua field wajib diisi kecuali gambar.';
    }

    if (!ctype_digit($harga) || !ctype_digit($stok)) {
        $errors[] = 'Harga dan stok harus berupa angka bulat positif.';
    }

    if (!$errors) {
        if ($formMode === 'edit') {
            $stmt = mysqli_prepare(
                $conn,
                'UPDATE produk SET kode_produk = ?, nama_produk = ?, tipe_kendaraan = ?, kategori_id = ?, harga = ?, stok = ?, gambar = ?, brand_id = ? WHERE kode_produk = ?'
            );
            $hargaInt = (int) $harga;
            $stokInt = (int) $stok;
            mysqli_stmt_bind_param($stmt, 'ssssiisss', $kodeProduk, $namaProduk, $tipeKendaraan, $kategoriId, $hargaInt, $stokInt, $gambar, $brandId, $originalKode);
            $success = mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            if ($success) {
                set_flash('success', 'Produk berhasil diperbarui.');
                redirect('produk.php');
            }

            $errors[] = 'Gagal memperbarui produk. Pastikan kode produk unik.';
        } else {
            $stmt = mysqli_prepare(
                $conn,
                'INSERT INTO produk (kode_produk, nama_produk, tipe_kendaraan, kategori_id, harga, stok, gambar, brand_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $hargaInt = (int) $harga;
            $stokInt = (int) $stok;
            mysqli_stmt_bind_param($stmt, 'ssssiiss', $kodeProduk, $namaProduk, $tipeKendaraan, $kategoriId, $hargaInt, $stokInt, $gambar, $brandId);
            $success = mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            if ($success) {
                set_flash('success', 'Produk berhasil ditambahkan.');
                redirect('produk.php');
            }

            $errors[] = 'Gagal menambahkan produk. Pastikan kode produk unik.';
        }

        $editProduct = [
            'kode_produk' => $kodeProduk,
            'nama_produk' => $namaProduk,
            'tipe_kendaraan' => $tipeKendaraan,
            'kategori_id' => $kategoriId,
            'harga' => $harga,
            'stok' => $stok,
            'gambar' => $gambar,
            'brand_id' => $brandId,
        ];
    }
}

$kategoriResult = mysqli_query($conn, 'SELECT id, nama_kategori FROM kategori ORDER BY nama_kategori ASC');
$brandResult = mysqli_query($conn, 'SELECT id, nama_brand FROM brand ORDER BY nama_brand ASC');
$products = mysqli_query(
    $conn,
    "SELECT p.kode_produk, p.nama_produk, p.tipe_kendaraan, p.harga, p.stok, p.gambar, b.nama_brand, k.nama_kategori
     FROM produk p
     JOIN brand b ON b.id = p.brand_id
     JOIN kategori k ON k.id = p.kategori_id
     ORDER BY p.nama_produk ASC"
);

render_header($isAdmin ? 'Kelola Produk' : 'Daftar Produk', $user);
?>
<?php if ($isAdmin): ?>
    <div class="content-grid admin-grid">
        <section class="panel">
            <div class="panel-head">
                <div>
                    <p class="eyebrow">Form Produk</p>
                    <h3><?= $editProduct ? 'Edit sparepart' : 'Tambah sparepart'; ?></h3>
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
                <input type="hidden" name="form_mode" value="<?= $editProduct ? 'edit' : 'create'; ?>">
                <input type="hidden" name="original_kode" value="<?= e($editProduct['kode_produk'] ?? ''); ?>">

                <label>
                    <span>Kode Produk</span>
                    <input type="text" name="kode_produk" value="<?= e($editProduct['kode_produk'] ?? ''); ?>" placeholder="Contoh: PRD007">
                </label>

                <label>
                    <span>Nama Produk</span>
                    <input type="text" name="nama_produk" value="<?= e($editProduct['nama_produk'] ?? ''); ?>" placeholder="Nama sparepart">
                </label>

                <label>
                    <span>Tipe Kendaraan</span>
                    <input type="text" name="tipe_kendaraan" value="<?= e($editProduct['tipe_kendaraan'] ?? ''); ?>" placeholder="Contoh: Honda Vario">
                </label>

                <label>
                    <span>Kategori</span>
                    <select name="kategori_id">
                        <option value="">Pilih kategori</option>
                        <?php mysqli_data_seek($kategoriResult, 0); ?>
                        <?php while ($kategori = mysqli_fetch_assoc($kategoriResult)): ?>
                            <option value="<?= e($kategori['id']); ?>" <?= ($editProduct['kategori_id'] ?? '') === $kategori['id'] ? 'selected' : ''; ?>>
                                <?= e($kategori['nama_kategori']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </label>

                <label>
                    <span>Brand</span>
                    <select name="brand_id">
                        <option value="">Pilih brand</option>
                        <?php mysqli_data_seek($brandResult, 0); ?>
                        <?php while ($brand = mysqli_fetch_assoc($brandResult)): ?>
                            <option value="<?= e($brand['id']); ?>" <?= ($editProduct['brand_id'] ?? '') === $brand['id'] ? 'selected' : ''; ?>>
                                <?= e($brand['nama_brand']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </label>

                <label>
                    <span>Harga</span>
                    <input type="number" name="harga" value="<?= e((string) ($editProduct['harga'] ?? '')); ?>" placeholder="65000" min="0">
                </label>

                <label>
                    <span>Stok</span>
                    <input type="number" name="stok" value="<?= e((string) ($editProduct['stok'] ?? '')); ?>" placeholder="10" min="0">
                </label>

                <label class="full-width">
                    <span>Nama File Gambar</span>
                    <input type="text" name="gambar" value="<?= e($editProduct['gambar'] ?? ''); ?>" placeholder="contoh.jpg">
                </label>

                <div class="form-actions full-width">
                    <button type="submit" class="btn btn-primary"><?= $editProduct ? 'Simpan Perubahan' : 'Tambah Produk'; ?></button>
                    <?php if ($editProduct): ?>
                        <a href="produk.php" class="btn btn-outline">Batal Edit</a>
                    <?php endif; ?>
                </div>
            </form>
        </section>

        <section class="panel">
            <div class="panel-head">
                <div>
                    <p class="eyebrow">Data Produk</p>
                    <h3>Daftar sparepart</h3>
                </div>
            </div>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Produk</th>
                        <th>Kategori</th>
                        <th>Brand</th>
                        <th>Harga</th>
                        <th>Stok</th>
                        <th>Aksi</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php while ($product = mysqli_fetch_assoc($products)): ?>
                        <tr>
                            <td><?= e($product['kode_produk']); ?></td>
                            <td>
                                <strong><?= e($product['nama_produk']); ?></strong>
                                <div class="muted"><?= e($product['tipe_kendaraan']); ?></div>
                            </td>
                            <td><?= e($product['nama_kategori']); ?></td>
                            <td><?= e($product['nama_brand']); ?></td>
                            <td><?= e(format_rupiah($product['harga'])); ?></td>
                            <td><?= e((string) $product['stok']); ?></td>
                            <td class="action-cell">
                                <a class="btn btn-small btn-outline" href="produk.php?edit=<?= e($product['kode_produk']); ?>">Edit</a>
                                <a class="btn btn-small btn-danger" href="produk.php?delete=<?= e($product['kode_produk']); ?>" data-confirm="Hapus produk ini?">Hapus</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
<?php else: ?>
    <section class="panel">
        <div class="panel-head">
            <div>
                <p class="eyebrow">Katalog</p>
                <h3>Pilih sparepart yang dibutuhkan</h3>
            </div>
        </div>
        <div class="product-grid">
            <?php while ($product = mysqli_fetch_assoc($products)): ?>
                <article class="product-card product-card-customer">
                    <div class="product-card-top">
                        <span class="tag"><?= e($product['nama_kategori']); ?></span>
                        <span class="muted"><?= e($product['nama_brand']); ?></span>
                    </div>
                    <h4><?= e($product['nama_produk']); ?></h4>
                    <p><?= e($product['tipe_kendaraan']); ?></p>
                    <div class="product-card-bottom">
                        <strong><?= e(format_rupiah($product['harga'])); ?></strong>
                        <span>Stok: <?= e((string) $product['stok']); ?></span>
                    </div>
                    <a class="btn btn-primary btn-full" href="beli.php?kode=<?= e($product['kode_produk']); ?>">Beli Sekarang</a>
                </article>
            <?php endwhile; ?>
        </div>
    </section>
<?php endif; ?>
<?php render_footer(); ?>
