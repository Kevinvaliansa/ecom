<?php
session_start();
require_once 'backend/config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Silakan login terlebih dahulu!'); window.location.href = 'login.php';</script>";
    exit;
}

$id_user = $_SESSION['user_id'];

if (isset($_POST['add_to_cart'])) {
    $id_produk = $_POST['id_produk'];
    $qty = (int)$_POST['qty'];
    $cek_cart = $conn->prepare("SELECT * FROM cart WHERE id_user = ? AND id_produk = ?");
    $cek_cart->execute([$id_user, $id_produk]);
    $cart_exist = $cek_cart->fetch();
    if ($cart_exist) {
        $new_qty = $cart_exist['qty'] + $qty;
        $update_cart = $conn->prepare("UPDATE cart SET qty = ? WHERE id = ?");
        $update_cart->execute([$new_qty, $cart_exist['id']]);
    } else {
        $insert_cart = $conn->prepare("INSERT INTO cart (id_user, id_produk, qty) VALUES (?, ?, ?)");
        $insert_cart->execute([$id_user, $id_produk, $qty]);
    }
    header("Location: cart.php"); exit;
}

if (isset($_GET['hapus'])) {
    $id_cart = $_GET['hapus'];
    $hapus = $conn->prepare("DELETE FROM cart WHERE id = ? AND id_user = ?");
    $hapus->execute([$id_cart, $id_user]);
    header("Location: cart.php"); exit;
}

$stmt = $conn->prepare("SELECT c.id as id_cart, c.qty, p.nama_produk, p.harga, p.gambar 
                        FROM cart c JOIN produk p ON c.id_produk = p.id WHERE c.id_user = ?");
$stmt->execute([$id_user]);
$cart_items = $stmt->fetchAll();

$inisial = strtoupper(substr($_SESSION['user_nama'], 0, 1));
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Keranjang - XrivaStore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="frontend/css/style.css">
    <style>
        .cart-checkbox { transform: scale(1.3); cursor: pointer; accent-color: var(--xriva-dark); }
        .table-cart td { vertical-align: middle; border-bottom: 1px solid #f0f0f0; }
        .table-cart th { border-bottom: 2px solid #e0e0e0; font-weight: 600; color: var(--xriva-text); }
        .table-cart tr:last-child td { border-bottom: none; }
    </style>
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

    <div class="container my-5 pb-5">
        <h3 class="mb-4 text-sage-dark fw-bold"><i class="fas fa-shopping-basket text-primary" style="color: var(--xriva-primary)!important;"></i> Keranjang Belanja Anda</h3>
        
        <form action="checkout.php" method="POST" id="cartForm">
            <div class="row">
                <div class="col-lg-8 mb-4">
                    <div class="card shadow-sm border-0 bg-white" style="border-radius: 16px;">
                        <div class="card-body table-responsive p-4">
                            <table class="table table-borderless table-cart mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 5%;"><input type="checkbox" id="selectAll" class="cart-checkbox"></th>
                                        <th>Produk</th>
                                        <th>Harga</th>
                                        <th class="text-center">Jumlah</th>
                                        <th class="text-end">Subtotal</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(count($cart_items) > 0): ?>
                                        <?php foreach($cart_items as $item): ?>
                                        <?php $subtotal = $item['harga'] * $item['qty']; ?>
                                        <tr>
                                            <td>
                                                <input type="checkbox" name="selected_items[]" value="<?= $item['id_cart'] ?>" class="cart-checkbox item-check" data-price="<?= $subtotal ?>">
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="frontend/images/produk/<?= $item['gambar'] ?>" width="60" height="60" style="object-fit:cover; border-radius:10px; margin-right: 15px; border: 1px solid #f0f0f0;">
                                                    <span class="fw-bold text-dark"><?= htmlspecialchars($item['nama_produk']) ?></span>
                                                </div>
                                            </td>
                                            <td class="text-muted">Rp <?= number_format($item['harga'], 0, ',', '.') ?></td>
                                            <td class="text-center fw-bold"><?= $item['qty'] ?></td>
                                            <td class="text-end fw-bold text-sage-dark">Rp <?= number_format($subtotal, 0, ',', '.') ?></td>
                                            <td class="text-center">
                                                <a href="cart.php?hapus=<?= $item['id_cart'] ?>" class="btn btn-sm btn-light text-danger rounded-circle shadow-sm" style="width: 32px; height: 32px; padding: 4px;" onclick="return confirm('Hapus produk ini?')"><i class="fas fa-trash"></i></a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-5 text-muted">
                                                <i class="fas fa-shopping-cart fa-3x mb-3" style="color: #e0e0e0;"></i><br>
                                                Keranjang belanja Anda masih kosong. <br>
                                                <a href="index.php" class="btn btn-sage btn-sm mt-3 px-4 rounded-pill">Mulai Belanja</a>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card shadow-sm border-0 bg-sage-light sticky-top" style="top: 90px; border-radius: 16px;">
                        <div class="card-body p-4">
                            <h5 class="card-title text-sage-dark fw-bold mb-4">Ringkasan Belanja</h5>
                            <div class="d-flex justify-content-between mb-3 align-items-center">
                                <span class="text-muted">Total Harga:</span>
                                <span class="fw-bold fs-4 text-sage-dark" id="displayTotal">Rp 0</span>
                            </div>
                            <hr style="border-color: rgba(0,0,0,0.1);">
                            <button type="submit" id="btnCheckout" class="btn btn-sage w-100 fw-bold py-2 rounded-pill disabled shadow-sm">
                                Lanjut Checkout (<span id="countItems">0</span>) <i class="fas fa-arrow-right ms-1"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const selectAll = document.getElementById('selectAll');
        const itemChecks = document.querySelectorAll('.item-check');
        const displayTotal = document.getElementById('displayTotal');
        const countItems = document.getElementById('countItems');
        const btnCheckout = document.getElementById('btnCheckout');

        function hitungTotal() {
            let total = 0; let checkedCount = 0;
            itemChecks.forEach(cb => {
                if (cb.checked) { total += parseInt(cb.getAttribute('data-price')); checkedCount++; }
            });
            displayTotal.innerText = 'Rp ' + total.toLocaleString('id-ID');
            countItems.innerText = checkedCount;

            if (checkedCount > 0) { btnCheckout.classList.remove('disabled'); } 
            else { btnCheckout.classList.add('disabled'); }

            if (itemChecks.length > 0) { selectAll.checked = (checkedCount === itemChecks.length); }
        }

        itemChecks.forEach(cb => { cb.addEventListener('change', hitungTotal); });
        if (selectAll) {
            selectAll.addEventListener('change', function() {
                itemChecks.forEach(cb => { cb.checked = selectAll.checked; });
                hitungTotal();
            });
        }
    </script>
</body>
</html>