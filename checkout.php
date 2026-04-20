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

// 2. CEK APAKAH ADA BARANG YANG DIPILIH DARI KERANJANG
// Jika user langsung akses url checkout.php tanpa centang barang, lempar balik
if (!isset($_POST['selected_items']) && !isset($_POST['checkout'])) {
    echo "<script>alert('Pilih minimal satu barang untuk di-checkout!'); window.location.href='cart.php';</script>";
    exit;
}

// Jika post dari halaman cart, simpan ID barang yang dicentang ke Session sementara
if (isset($_POST['selected_items'])) {
    $_SESSION['checkout_items'] = $_POST['selected_items'];
}

// Pastikan ada barang yang akan di-checkout
if (empty($_SESSION['checkout_items'])) {
    header("Location: cart.php");
    exit;
}

// Ambil array ID cart yang dipilih
$selected_cart_ids = $_SESSION['checkout_items'];

// Buat query dinamis untuk IN (...)
$inQuery = implode(',', array_fill(0, count($selected_cart_ids), '?'));
$params = array_merge([$id_user], $selected_cart_ids);

// 3. AMBIL DATA KERANJANG (HANYA YANG DIPILIH)
$stmt_cart = $conn->prepare("SELECT c.id as id_cart, c.id_produk, c.qty, p.nama_produk, p.harga 
                             FROM cart c JOIN produk p ON c.id_produk = p.id 
                             WHERE c.id_user = ? AND c.id IN ($inQuery)");
$stmt_cart->execute($params);
$cart_items = $stmt_cart->fetchAll();

// Hitung total harga
$total_belanja = 0;
foreach ($cart_items as $item) {
    $total_belanja += $item['harga'] * $item['qty'];
}

// 4. AMBIL DATA USER UNTUK ALAMAT
$stmt_user = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt_user->execute([$id_user]);
$user_data = $stmt_user->fetch();


// 5. PROSES KLIK TOMBOL "SELESAIKAN PESANAN"
if (isset($_POST['checkout'])) {
    $alamat_pengiriman = $_POST['alamat_pengiriman'];
    $metode_pembayaran = $_POST['metode_pembayaran'];
    
    try {
        $conn->beginTransaction();

        // A. Masukkan ke tabel `transaksi`
        $stmt_transaksi = $conn->prepare("INSERT INTO transaksi (id_user, total_harga, metode_pembayaran, status_pesanan) VALUES (?, ?, ?, 'diproses')");
        $stmt_transaksi->execute([$id_user, $total_belanja, $metode_pembayaran]);
        $id_transaksi = $conn->lastInsertId();

        // B. Masukkan barang-barang ke `detail_transaksi`
        $stmt_detail = $conn->prepare("INSERT INTO detail_transaksi (id_transaksi, id_produk, qty, harga_satuan) VALUES (?, ?, ?, ?)");
        $update_stok = $conn->prepare("UPDATE produk SET stok = stok - ? WHERE id = ?");

        foreach ($cart_items as $item) {
            $stmt_detail->execute([$id_transaksi, $item['id_produk'], $item['qty'], $item['harga']]);
            // Kurangi stok
            $update_stok->execute([$item['qty'], $item['id_produk']]);
        }

        // C. Kosongkan HANYA barang yang di-checkout dari keranjang
        $hapus_cart = $conn->prepare("DELETE FROM cart WHERE id_user = ? AND id IN ($inQuery)");
        $hapus_cart->execute($params);

        $conn->commit();
        $order_success = true;
        
        // Hapus session sementara karena sudah selesai
        unset($_SESSION['checkout_items']);

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

    <nav class="navbar navbar-expand-lg navbar-dark navbar-sage sticky-top shadow-sm py-2">
        <div class="container">
            <a class="navbar-brand fw-bold fs-4" href="index.php">
                <i class="fas fa-leaf"></i> XrivaStore
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center gap-3">
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active fw-bold' : '' ?>" href="index.php">
                            Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center <?= basename($_SERVER['PHP_SELF']) == 'wishlist.php' ? 'active fw-bold' : '' ?>" href="wishlist.php">
                            <i class="fas fa-heart me-1"></i> Wishlist
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center <?= basename($_SERVER['PHP_SELF']) == 'cart.php' ? 'active fw-bold' : '' ?>" href="cart.php">
                            <i class="fas fa-shopping-cart me-1"></i> Keranjang
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center <?= basename($_SERVER['PHP_SELF']) == 'history.php' ? 'active fw-bold' : '' ?>" href="history.php">
                            <i class="fas fa-history me-1"></i> Pesanan
                        </a>
                    </li>
                    
                    <li class="nav-item dropdown ms-2 d-flex align-items-center border-start ps-3">
                        <div class="rounded-circle d-flex justify-content-center align-items-center bg-white text-sage-dark fw-bold me-2 shadow-sm" style="width: 35px; height: 35px; font-size: 1rem;">
                            <?= isset($inisial) ? $inisial : strtoupper(substr($_SESSION['user_nama'], 0, 1)) ?>
                        </div>
                        <a class="nav-link dropdown-toggle fw-bold text-white p-0" href="#" data-bs-toggle="dropdown">
                            <?= htmlspecialchars($_SESSION['user_nama']) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end mt-3 shadow border-0" style="border-radius: 12px;">
                            <li><a class="dropdown-item py-2" href="profile.php"><i class="fas fa-user-circle text-muted me-2"></i> Profil Saya</a></li>
                            <li><a class="dropdown-item py-2" href="history.php"><i class="fas fa-clipboard-list text-muted me-2"></i> Pesanan Saya</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger py-2" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
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
                        <p class="text-muted">Terima kasih, <b><?= htmlspecialchars($user_data['nama']) ?></b>. Pesanan Anda sedang diproses.</p>
                        <hr>
                        <p class="mb-1">Metode Pembayaran: <b><?= htmlspecialchars($_POST['metode_pembayaran']) ?></b></p>
                        <p>Total yang harus dibayar: <b class="text-sage-dark fs-5">Rp <?= number_format($total_belanja, 0, ',', '.') ?></b></p>
                        
                        <?php if($_POST['metode_pembayaran'] == 'Transfer'): ?>
                            <div class="alert alert-info mt-3">
                                Silakan transfer ke Rekening BCA: <b>1234567890</b> a.n XrivaStore.<br>
                                Lalu upload bukti bayarnya di menu Pesanan Saya.
                            </div>
                        <?php endif; ?>
                        
                        <a href="history.php" class="btn btn-sage mt-4">Lihat Pesanan Saya</a>
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
                                    <label class="form-check-label fw-bold" for="transfer">Transfer Bank (BCA / Mandiri / BNI)</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="metode_pembayaran" value="COD" id="cod" required>
                                    <label class="form-check-label fw-bold" for="cod">Bayar di Tempat (COD)</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-5">
                        <div class="card shadow-sm border-0 sticky-top" style="top: 20px;">
                            <div class="card-header bg-sage-light text-sage-dark fw-bold">Ringkasan Pesanan (Yang Dipilih)</div>
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
                                <a href="cart.php" class="btn btn-outline-secondary w-100 mt-2 btn-sm">Kembali ke Keranjang</a>
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