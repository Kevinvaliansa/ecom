<?php
session_start();
require_once 'backend/config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$id_user = $_SESSION['user_id'];

// Ambil data user
$stmt_user = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt_user->execute([$id_user]);
$user = $stmt_user->fetch();

$cart_items  = [];
$total_bayar = 0;
$ids_selected = [];

// ==============================================================
// PROSES BUAT PESANAN
// ==============================================================
if (isset($_POST['buat_pesanan'])) {
    $nama_penerima = htmlspecialchars(trim($_POST['nama_penerima']));
    $no_hp         = htmlspecialchars(trim($_POST['no_hp']));
    $alamat        = htmlspecialchars(trim($_POST['alamat_pengiriman']));
    $metode        = htmlspecialchars(trim($_POST['metode_pembayaran']));
    $status        = ($metode == 'COD') ? 'diproses' : 'pending';

    try {
        if (empty($_POST['id_carts'])) {
            throw new Exception("Keranjang kosong atau data tidak valid.");
        }

        $carts = array_filter(array_map('intval', explode(',', $_POST['id_carts'])));
        if (empty($carts)) throw new Exception("Data cart tidak valid.");

        $in = str_repeat('?,', count($carts) - 1) . '?';

        // ✅ Hitung ulang total dari DATABASE (tidak percaya POST)
        $stmt_items = $conn->prepare(
            "SELECT c.qty, p.id as id_produk, p.nama_produk, p.harga, p.gambar, p.stok
             FROM cart c JOIN produk p ON c.id_produk = p.id
             WHERE c.id IN ($in) AND c.id_user = ?"
        );
        $params = array_merge($carts, [$id_user]);
        $stmt_items->execute($params);
        $items_to_process = $stmt_items->fetchAll();

        if (empty($items_to_process)) {
            throw new Exception("Item tidak ditemukan atau bukan milik Anda.");
        }

        // Validasi stok & hitung total dari DB
        $total_server = 0;
        foreach ($items_to_process as $it) {
            if ($it['qty'] > $it['stok']) {
                throw new Exception("Stok produk '{$it['nama_produk']}' tidak mencukupi.");
            }
            $total_server += $it['harga'] * $it['qty'];
        }

        $conn->beginTransaction();

        // 1. Simpan transaksi utama (gunakan total dari server)
        $insert_trx = $conn->prepare(
            "INSERT INTO transaksi (id_user, total_harga, metode_pembayaran, status_pesanan, tanggal_transaksi)
             VALUES (?, ?, ?, ?, NOW())"
        );
        $insert_trx->execute([$id_user, $total_server, $metode, $status]);
        $id_transaksi = $conn->lastInsertId();

        foreach ($items_to_process as $it) {
            // 2. Simpan detail transaksi
            $conn->prepare(
                "INSERT INTO detail_transaksi (id_transaksi, id_produk, nama_produk, harga, jumlah, gambar)
                 VALUES (?, ?, ?, ?, ?, ?)"
            )->execute([$id_transaksi, $it['id_produk'], $it['nama_produk'], $it['harga'], $it['qty'], $it['gambar']]);

            // 3. Kurangi stok
            $conn->prepare("UPDATE produk SET stok = stok - ? WHERE id = ?")->execute([$it['qty'], $it['id_produk']]);
        }

        // 4. Hapus dari keranjang
        $conn->prepare("DELETE FROM cart WHERE id IN ($in)")->execute($carts);

        $conn->commit();

        $redirect = ($metode == 'COD') ? 'history.php' : 'upload_after_checkout.php?id=' . $id_transaksi;

        echo "<!DOCTYPE html><html><head>
                <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
              </head>
              <body style='background-color:#f4f7f6;'>
              <script>
                Swal.fire({
                    title: 'Yeay! Pesanan Berhasil 🎉',
                    text: 'Pesanan kamu telah dibuat. Total: Rp " . number_format($total_server, 0, ',', '.') . "',
                    icon: 'success',
                    confirmButtonColor: '#4a7c6b',
                    confirmButtonText: 'Lanjut'
                }).then(() => { window.location.href = '{$redirect}'; });
              </script></body></html>";
        exit;

    } catch (Exception $e) {
        if ($conn->inTransaction()) $conn->rollBack();
        $error_msg = addslashes($e->getMessage());
        echo "<!DOCTYPE html><html><head>
                <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
              </head>
              <body style='background-color:#f4f7f6;'>
              <script>
                Swal.fire({
                    title: 'Oops, Gagal Checkout!',
                    text: '{$error_msg}',
                    icon: 'error',
                    confirmButtonColor: '#d33',
                    confirmButtonText: 'Kembali'
                }).then(() => { window.location.href = 'cart.php'; });
              </script></body></html>";
        exit;
    }

} else {
    // Tampilkan halaman checkout
    if (!empty($_POST['selected_items'])) {
        $selected = array_map('intval', $_POST['selected_items']);
        $in       = str_repeat('?,', count($selected) - 1) . '?';
        $params   = array_merge($selected, [$id_user]);

        $stmt = $conn->prepare(
            "SELECT c.id as id_cart, c.qty, p.id as id_produk, p.nama_produk, p.harga, p.gambar
             FROM cart c JOIN produk p ON c.id_produk = p.id
             WHERE c.id IN ($in) AND c.id_user = ?"
        );
        $stmt->execute($params);
        $cart_items = $stmt->fetchAll();

        foreach ($cart_items as $item) {
            $total_bayar  += ($item['harga'] * $item['qty']);
            $ids_selected[] = $item['id_cart'];
        }
    } else {
        header("Location: cart.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proses Pembayaran - XrivaStore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="frontend/css/style.css?v=<?= time() ?>">
    <style>
        .card-custom { border-radius: 16px; border: none; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
    </style>
</head>
<body class="bg-light">

<?php include 'frontend/includes/navbar.php'; ?>

<div class="container my-5 pb-5">
    <a href="cart.php" class="text-decoration-none text-muted mb-4 d-inline-block">
        <i class="fas fa-arrow-left me-2"></i>Kembali ke Keranjang
    </a>
    <h3 class="mb-4 text-dark fw-bold">
        <i class="fas fa-clipboard-check text-sage-dark me-2"></i> Proses Pembayaran
    </h3>

    <form method="POST">
        <div class="row g-4">
            <div class="col-lg-7">
                <!-- Info Pengiriman -->
                <div class="card card-custom p-4 mb-4">
                    <h5 class="fw-bold mb-4 border-bottom pb-2">1. Informasi Pengiriman</h5>
                    <div class="mb-3">
                        <label class="form-label small text-muted fw-bold">Nama Penerima</label>
                        <input type="text" name="nama_penerima" class="form-control" value="<?= htmlspecialchars($user['nama']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-muted fw-bold">No. HP</label>
                        <input type="text" name="no_hp" class="form-control" value="<?= htmlspecialchars($user['no_hp'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-muted fw-bold">Alamat Lengkap</label>
                        <textarea name="alamat_pengiriman" class="form-control" rows="3" required><?= htmlspecialchars($user['alamat'] ?? '') ?></textarea>
                    </div>
                </div>

                <!-- Daftar Barang -->
                <div class="card card-custom p-4">
                    <h5 class="fw-bold mb-4 border-bottom pb-2">2. Daftar Barang</h5>
                    <?php foreach ($cart_items as $c): ?>
                    <div class="d-flex align-items-center mb-3 border-bottom pb-3">
                        <img src="frontend/images/produk/<?= htmlspecialchars($c['gambar']) ?>"
                             width="60" height="60" class="rounded border me-3"
                             style="object-fit: contain; background:#f8f9fa;">
                        <div class="flex-grow-1">
                            <h6 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($c['nama_produk']) ?></h6>
                            <small class="text-muted"><?= $c['qty'] ?> x Rp <?= number_format($c['harga'], 0, ',', '.') ?></small>
                        </div>
                        <div class="fw-bold text-sage-dark">
                            Rp <?= number_format($c['harga'] * $c['qty'], 0, ',', '.') ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Ringkasan Bayar -->
            <div class="col-lg-5">
                <div class="card card-custom p-4 bg-white sticky-top" style="top: 80px;">
                    <h5 class="fw-bold mb-4 border-bottom pb-2">3. Pembayaran</h5>

                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Total Tagihan</span>
                    </div>
                    <div class="mb-4">
                        <span class="fw-bold fs-3 text-sage-dark">Rp <?= number_format($total_bayar, 0, ',', '.') ?></span>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold small text-muted">Pilih Metode Pembayaran</label>
                        <select name="metode_pembayaran" class="form-select" style="border-radius: 12px; height: 50px;" required>
                            <option value="Transfer Bank (BCA)">🏦 Transfer Bank (BCA) – 1234-5678-90</option>
                            <option value="Transfer Bank (Mandiri)">🏦 Transfer Bank (Mandiri) – 0987-6543-21</option>
                            <option value="E-Wallet (Dana/OVO)">📱 E-Wallet (Dana/OVO)</option>
                            <option value="COD">🏠 Bayar di Tempat (COD)</option>
                        </select>
                    </div>

                    <div class="alert bg-light border-0 rounded-3 small text-muted">
                        <i class="fas fa-shield-alt me-1 text-success"></i>
                        Total dihitung ulang di server untuk keamanan transaksi.
                    </div>

                    <input type="hidden" name="id_carts" value="<?= implode(',', $ids_selected) ?>">

                    <button type="submit" name="buat_pesanan"
                            class="btn btn-sage w-100 fw-bold py-3 shadow-sm"
                            style="border-radius: 12px; font-size: 1.1rem;">
                        Buat Pesanan Sekarang <i class="fas fa-check-circle ms-1"></i>
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>