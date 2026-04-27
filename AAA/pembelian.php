<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout.php';

$user = require_login($conn);
$isAdmin = $user['role'] === 'admin';

if ($isAdmin && isset($_GET['action'], $_GET['id'])) {
    $action = $_GET['action'];
    $purchaseId = trim($_GET['id']);

    if (in_array($action, ['confirm', 'reject'], true)) {
        $stmt = mysqli_prepare(
            $conn,
            'SELECT p.id_pembelian, p.kode_produk, p.jumlah, p.status, pr.stok FROM pembelian p JOIN produk pr ON pr.kode_produk = p.kode_produk WHERE p.id_pembelian = ? LIMIT 1'
        );
        mysqli_stmt_bind_param($stmt, 's', $purchaseId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $purchase = mysqli_fetch_assoc($result) ?: null;
        mysqli_stmt_close($stmt);

        if (!$purchase) {
            set_flash('error', 'Data pembelian tidak ditemukan.');
            redirect('pembelian.php');
        }

        if ($purchase['status'] !== 'menunggu') {
            set_flash('error', 'Pembelian ini sudah diproses sebelumnya.');
            redirect('pembelian.php');
        }

        if ($action === 'confirm') {
            if ((int) $purchase['jumlah'] > (int) $purchase['stok']) {
                set_flash('error', 'Stok tidak mencukupi untuk konfirmasi pembelian.');
                redirect('pembelian.php');
            }

            mysqli_begin_transaction($conn);

            try {
                $newStatus = 'dikonfirmasi';
                $stmt = mysqli_prepare($conn, 'UPDATE pembelian SET status = ? WHERE id_pembelian = ?');
                mysqli_stmt_bind_param($stmt, 'ss', $newStatus, $purchaseId);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);

                $stmt = mysqli_prepare($conn, 'UPDATE produk SET stok = stok - ? WHERE kode_produk = ?');
                $jumlah = (int) $purchase['jumlah'];
                mysqli_stmt_bind_param($stmt, 'is', $jumlah, $purchase['kode_produk']);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);

                mysqli_commit($conn);
                set_flash('success', 'Pembelian berhasil dikonfirmasi.');
            } catch (Throwable $exception) {
                mysqli_rollback($conn);
                set_flash('error', 'Konfirmasi gagal diproses.');
            }
        } else {
            $newStatus = 'ditolak';
            $stmt = mysqli_prepare($conn, 'UPDATE pembelian SET status = ? WHERE id_pembelian = ?');
            mysqli_stmt_bind_param($stmt, 'ss', $newStatus, $purchaseId);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            set_flash('success', 'Pembelian berhasil ditolak.');
        }
    }

    redirect('pembelian.php');
}

if ($isAdmin) {
    $query = "
        SELECT p.id_pembelian, p.jumlah, p.total_bayar, p.tanggal_transaksi, p.status,
               u.username, pr.nama_produk, pr.kode_produk, pr.stok
        FROM pembelian p
        JOIN users u ON u.id = p.user_id
        JOIN produk pr ON pr.kode_produk = p.kode_produk
        ORDER BY
            CASE p.status
                WHEN 'menunggu' THEN 1
                WHEN 'dikonfirmasi' THEN 2
                ELSE 3
            END,
            p.tanggal_transaksi DESC
    ";
    $transactions = mysqli_query($conn, $query);
} else {
    $stmt = mysqli_prepare(
        $conn,
        "SELECT p.id_pembelian, p.jumlah, p.total_bayar, p.tanggal_transaksi, p.status, pr.nama_produk, pr.kode_produk
         FROM pembelian p
         JOIN produk pr ON pr.kode_produk = p.kode_produk
         WHERE p.user_id = ?
         ORDER BY p.tanggal_transaksi DESC"
    );
    mysqli_stmt_bind_param($stmt, 's', $user['id']);
    mysqli_stmt_execute($stmt);
    $transactions = mysqli_stmt_get_result($stmt);
}

render_header($isAdmin ? 'Konfirmasi Pembelian' : 'Riwayat Pembelian', $user);
?>
<section class="panel">
    <div class="panel-head">
        <div>
            <p class="eyebrow">Transaksi</p>
            <h3><?= $isAdmin ? 'Daftar pembelian customer' : 'Daftar pembelian saya'; ?></h3>
        </div>
    </div>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
            <tr>
                <th>ID</th>
                <?php if ($isAdmin): ?>
                    <th>Customer</th>
                <?php endif; ?>
                <th>Produk</th>
                <th>Jumlah</th>
                <th>Total</th>
                <th>Tanggal</th>
                <th>Status</th>
                <?php if ($isAdmin): ?>
                    <th>Aksi</th>
                <?php endif; ?>
            </tr>
            </thead>
            <tbody>
            <?php if (mysqli_num_rows($transactions) === 0): ?>
                <tr>
                    <td colspan="<?= $isAdmin ? '8' : '6'; ?>" class="empty-state">Belum ada data pembelian.</td>
                </tr>
            <?php else: ?>
                <?php while ($transaction = mysqli_fetch_assoc($transactions)): ?>
                    <tr>
                        <td><?= e($transaction['id_pembelian']); ?></td>
                        <?php if ($isAdmin): ?>
                            <td><?= e($transaction['username']); ?></td>
                        <?php endif; ?>
                        <td>
                            <strong><?= e($transaction['nama_produk']); ?></strong>
                            <div class="muted"><?= e($transaction['kode_produk']); ?></div>
                        </td>
                        <td><?= e((string) $transaction['jumlah']); ?></td>
                        <td><?= e(format_rupiah($transaction['total_bayar'])); ?></td>
                        <td><?= e(date('d-m-Y H:i', strtotime($transaction['tanggal_transaksi']))); ?></td>
                        <td><span class="status-badge status-<?= e($transaction['status']); ?>"><?= e(ucfirst($transaction['status'])); ?></span></td>
                        <?php if ($isAdmin): ?>
                            <td class="action-cell">
                                <?php if ($transaction['status'] === 'menunggu'): ?>
                                    <a class="btn btn-small btn-primary" href="pembelian.php?action=confirm&id=<?= e($transaction['id_pembelian']); ?>" data-confirm="Konfirmasi pembelian ini?">Konfirmasi</a>
                                    <a class="btn btn-small btn-danger" href="pembelian.php?action=reject&id=<?= e($transaction['id_pembelian']); ?>" data-confirm="Tolak pembelian ini?">Tolak</a>
                                <?php else: ?>
                                    <span class="muted">Sudah diproses</span>
                                <?php endif; ?>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endwhile; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
if (!$isAdmin) {
    mysqli_stmt_close($stmt);
}
render_footer();
?>
