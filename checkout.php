<?php
session_start();
require_once 'backend/config/database.php';

// 1. CEK LOGIN
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$id_user = $_SESSION['user_id'];
$order_success = false;

// 2. AMBIL DATA USER UNTUK ALAMAT PENGIRIMAN
$stmt_user = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt_user->execute([$id_user]);
$user_data = $stmt_user->fetch();

// 3. AMBIL DATA KERANJANG
$stmt_cart = $conn->prepare("SELECT c.id_produk, c.qty, p.nama_produk, p.harga 
                             FROM cart c JOIN produk p ON c.id_produk = p.id 
                             WHERE c.id_user = ?");
$stmt_cart->execute([$id_user]);
$cart_items = $stmt_cart->fetchAll();

// Jika keranjang kosong dan belum ada proses checkout sukses, lempar balik ke cart
if (count($cart_items) == 0 && !isset($_POST['checkout'])) {
    header("Location: cart.php");
    exit;
}

// Hitung total harga
$total_belanja = 0;
foreach ($cart_items as $item) {
    $total_belanja += $item['harga'] * $item['qty'];
}

// 4. PROSES CHECKOUT (SIMPAN KE DATABASE)
if (isset($_POST['checkout'])) {
    $alamat_pengiriman = $_POST['alamat_pengiriman'];
    $metode_pembayaran = $_POST['metode_pembayaran'];
    
    try {
        // Mulai Transaksi Database (Biar aman, kalau 1 gagal, gagal semua)
        $conn->beginTransaction();

        // A. Masukkan ke tabel `transaksi`
        $stmt_transaksi = $conn->prepare("INSERT INTO transaksi (id_user, total_harga, metode_pembayaran, status_pesanan) VALUES (?, ?, ?, 'diproses')");
        $stmt_transaksi->execute([$id_user, $total_belanja, $metode_pembayaran]);
        
        // Dapatkan ID transaksi yang baru saja dibuat
        $id_transaksi = $conn->lastInsertId();

        // B. Masukkan barang-barang ke `detail_transaksi`
        $stmt_detail = $conn->prepare("INSERT INTO detail_transaksi (id_transaksi, id_produk, qty, harga_satuan) VALUES (?, ?, ?, ?)");
        foreach ($cart_items as $item) {
            $stmt_detail->execute([$id_transaksi, $item['id_produk'], $item['qty'], $item['harga']]);
            
            // Opsional: Kurangi stok produk di database
            $update_stok = $conn->prepare("UPDATE produk SET stok = stok - ? WHERE id = ?");
            $update_stok->execute([$item['qty'], $item['id_produk']]);
        }

        // C. Kosongkan keranjang belanja user tersebut
        $hapus_cart = $conn->prepare("DELETE FROM cart WHERE id_user = ?");
        $hapus_cart->execute([$id_user]);

        // Selesaikan transaksi
        $conn->commit();
        $order_success = true;

    } catch (Exception $e) {
        $conn->rollBack();
        $error_msg = "Gagal memproses pesanan: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Checkout - XrivaStore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="frontend/css/style.css">
</head>
<body class="bg-light">

    <nav class="navbar navbar-expand-lg navbar-dark navbar-sage sticky-top mb-4">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php"><i class="fas fa-leaf"></i> XrivaStore</a>
            <div class="ms-auto text-white fw-bold">
                Pengiriman Aman & Terpercaya
            </div>
        </div>
    </nav>

    <div class="container">
        <?php if ($order_success): ?>
            <div class="row justify-content-center mt-5">
                <div class="col-md-6 text-center">
                    <div class="card border-0 shadow-sm p-5">
                        <i class="fas fa-check-circle text-success mb-3" style="font-size: 5rem;"></i>
                        <h2 class="text-sage-dark fw-bold">Pesanan Berhasil!</h2>
                        <p class="text-muted">Terima kasih, <b><?= htmlspecialchars($user_data['nama']) ?></b>. Pesanan Anda sedang kami proses.</p>
                        <hr>
                        <p class="mb-1">Metode Pembayaran: <b><?= htmlspecialchars($_POST['metode_pembayaran']) ?></b></p>
                        <p>Total yang harus dibayar: <b class="text-sage-dark fs-5">Rp <?= number_format($total_belanja, 0, ',', '.') ?></b></p>
                        
                        <?php if($_POST['metode_pembayaran'] == 'Transfer'): ?>
                            <div class="alert alert-info mt-3">
                                Silakan transfer ke Rekening BCA: <b>1234567890</b> a.n XrivaStore.
                            </div>
                        <?php endif; ?>
                        
                        <a href="index.php" class="btn btn-sage mt-4">Kembali ke Beranda</a>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <h3 class="mb-4 text-sage-dark fw-bold"><i class="fas fa-clipboard-check"></i> Proses Pembayaran</h3>
            
            <?php if(isset($error_msg)): ?>
                <div class="alert alert-danger"><?= $error_msg ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="row">
                    <div class="col-md-7 mb-4">
                        <div class="card shadow-sm border-0">
                            <div class="card-header bg-sage-light text-sage-dark fw-bold">1. Informasi Pengiriman</div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Nama Penerima</label>
                                    <input type="text" class="form-control" value="<?= htmlspecialchars($user_data['nama']) ?>" readonly>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">No. HP</label>
                                    <input type="text" class="form-control" value="<?= htmlspecialchars($user_data['no_hp']) ?>" readonly>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Alamat Lengkap Pengiriman</label>
                                    <textarea name="alamat_pengiriman" class="form-control" rows="3" required><?= htmlspecialchars($user_data['alamat']) ?></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="card shadow-sm border-0 mt-4">
                            <div class="card-header bg-sage-light text-sage-dark fw-bold">2. Metode Pembayaran</div>
                            <div class="card-body">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="metode_pembayaran" value="Transfer" id="transfer" required checked>
                                    <label class="form-check-label fw-bold" for="transfer">
                                        Transfer Bank (BCA / Mandiri / BNI)
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="metode_pembayaran" value="COD" id="cod" required>
                                    <label class="form-check-label fw-bold" for="cod">
                                        Bayar di Tempat (COD)
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-5">
                        <div class="card shadow-sm border-0">
                            <div class="card-header bg-sage-light text-sage-dark fw-bold">Ringkasan Pesanan</div>
                            <div class="card-body">
                                <?php foreach($cart_items as $item): ?>
                                    <div class="d-flex justify-content-between mb-2 pb-2 border-bottom">
                                        <div>
                                            <h6 class="mb-0"><?= htmlspecialchars($item['nama_produk']) ?></h6>
                                            <small class="text-muted"><?= $item['qty'] ?> x Rp <?= number_format($item['harga'], 0, ',', '.') ?></small>
                                        </div>
                                        <div class="fw-bold text-end">
                                            Rp <?= number_format($item['harga'] * $item['qty'], 0, ',', '.') ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                
                                <div class="d-flex justify-content-between mt-3 mb-3">
                                    <span class="fs-5 fw-bold">Total Pembayaran</span>
                                    <span class="fs-5 fw-bold text-sage-dark">Rp <?= number_format($total_belanja, 0, ',', '.') ?></span>
                                </div>

                                <button type="submit" name="checkout" class="btn btn-sage w-100 fw-bold py-2 shadow-sm">
                                    Selesaikan Pesanan <i class="fas fa-check"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>