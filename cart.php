<?php
session_start();
require_once 'backend/config/database.php';

// Kalau belum login, lempar ke login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$id_user = $_SESSION['user_id'];

// LOGIKA UPDATE QTY OTOMATIS
if (isset($_POST['update_qty'])) {
    $cart_id = $_POST['cart_id'];
    $new_qty = $_POST['update_qty'];
    if($new_qty >= 1) {
        $update = $conn->prepare("UPDATE cart SET qty = ? WHERE id = ? AND id_user = ?");
        $update->execute([$new_qty, $cart_id, $id_user]);
    }
    header("Location: cart.php");
    exit;
}

// LOGIKA HAPUS KERANJANG
if (isset($_GET['hapus'])) {
    $id_hapus = $_GET['hapus'];
    $hapus = $conn->prepare("DELETE FROM cart WHERE id = ? AND id_user = ?");
    $hapus->execute([$id_hapus, $id_user]);
    header("Location: cart.php");
    exit;
}

// Ambil data keranjang
$stmt = $conn->prepare("SELECT c.id as id_cart, c.qty, p.id as id_produk, p.nama_produk, p.harga, p.gambar, p.stok 
                        FROM cart c JOIN produk p ON c.id_produk = p.id 
                        WHERE c.id_user = ? ORDER BY c.id DESC");
$stmt->execute([$id_user]);
$cart_list = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Keranjang Belanja - XrivaStore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --xriva-dark: #4a7c6b; --xriva-primary: #7cb3a1; --xriva-light: #e8f0ed; }
        body { background-color: #f4f7f6; font-family: 'Segoe UI', sans-serif; }
        .navbar-sage { background-color: var(--xriva-dark); }
        .text-sage-dark { color: var(--xriva-dark); }
        .btn-sage { background-color: var(--xriva-primary); color: white; border: none; }
        .btn-sage:hover { background-color: var(--xriva-dark); color: white; }
        
        .cart-header { font-size: 14px; font-weight: 600; color: #888; margin-bottom: 15px; padding: 0 20px; }
        .cart-card { background: #fff; border-radius: 12px; padding: 20px; margin-bottom: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.03); transition: 0.2s; }
        .cart-card:hover { box-shadow: 0 5px 15px rgba(0,0,0,0.06); }
        .custom-checkbox { width: 20px; height: 20px; cursor: pointer; accent-color: var(--xriva-primary); }
        .qty-group { display: flex; align-items: center; border: 1px solid #ddd; border-radius: 6px; overflow: hidden; width: max-content; }
        .qty-btn { background: #f8f9fa; border: none; color: #555; width: 32px; height: 32px; font-weight: bold; cursor: pointer; display: flex; align-items: center; justify-content: center; }
        .qty-btn:hover { background: #e9ecef; }
        .qty-input { width: 45px; height: 32px; text-align: center; border: none; border-left: 1px solid #ddd; border-right: 1px solid #ddd; font-weight: 600; font-size: 14px; }
        .qty-input:focus { outline: none; }
        input[type="number"]::-webkit-inner-spin-button, 
        input[type="number"]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
        .sticky-summary { position: sticky; top: 90px; }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark navbar-sage sticky-top shadow-sm py-2">
        <div class="container">
            <a class="navbar-brand fw-bold fs-4" href="index.php"><i class="fas fa-leaf"></i> XrivaStore</a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center gap-3 mt-3 mt-lg-0">
                    <li class="nav-item"><a class="nav-link d-flex align-items-center" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link d-flex align-items-center" href="wishlist.php"><i class="fas fa-heart me-1"></i> Wishlist</a></li>
                    <li class="nav-item"><a class="nav-link active fw-bold d-flex align-items-center" href="cart.php"><i class="fas fa-shopping-cart me-1"></i> Keranjang</a></li>
                    <li class="nav-item"><a class="nav-link d-flex align-items-center" href="history.php"><i class="fas fa-history me-1"></i> Pesanan</a></li>
                    
                    <li class="nav-item dropdown ms-lg-2 d-flex align-items-center border-start-lg ps-lg-3 mt-2 mt-lg-0">
                        <div class="rounded-circle d-flex justify-content-center align-items-center bg-white text-sage-dark fw-bold me-2 shadow-sm" style="width: 35px; height: 35px; font-size: 1rem;">
                            <?= strtoupper(substr($_SESSION['user_nama'], 0, 1)) ?>
                        </div>
                        <a class="nav-link dropdown-toggle fw-bold text-white p-0" href="#" data-bs-toggle="dropdown" id="dropdownUser" aria-expanded="false">
                            <?= htmlspecialchars($_SESSION['user_nama']) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end mt-3 shadow border-0" aria-labelledby="dropdownUser" style="border-radius: 12px;">
                            <?php if(isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
                                <li><a class="dropdown-item py-2 fw-bold text-sage-dark" href="admin/produk.php"><i class="fas fa-user-shield me-2"></i> Dashboard Admin</a></li>
                                <li><hr class="dropdown-divider"></li>
                            <?php endif; ?>
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
        <div class="d-flex align-items-center mb-4">
            <h3 class="fw-bold text-dark m-0">Keranjang Belanja Anda</h3>
        </div>

        <form action="checkout.php" method="POST" id="form-checkout">
            <div class="row g-4">
                
                <div class="col-lg-8">
                    
                    <div class="cart-header d-none d-md-flex align-items-center">
                        <div style="width: 5%;"><input type="checkbox" id="checkAll" class="custom-checkbox"></div>
                        <div style="width: 45%;">Produk</div>
                        <div style="width: 15%; text-align: center;">Harga Satuan</div>
                        <div style="width: 15%; text-align: center;">Kuantitas</div>
                        <div style="width: 15%; text-align: center;">Total Harga</div>
                        <div style="width: 5%; text-align: center;">Aksi</div>
                    </div>

                    <?php foreach($cart_list as $c): ?>
                    <div class="cart-card d-flex align-items-center flex-wrap flex-md-nowrap">
                        
                        <div style="width: 5%;" class="mb-3 mb-md-0">
                            <input type="checkbox" name="selected_items[]" value="<?= $c['id_cart'] ?>" class="custom-checkbox item-check" data-harga="<?= $c['harga'] ?>" data-qty="<?= $c['qty'] ?>">
                        </div>
                        
                        <div style="width: 45%;" class="d-flex align-items-center mb-3 mb-md-0 pe-3">
                            <img src="frontend/images/produk/<?= htmlspecialchars($c['gambar']) ?>" width="80" height="80" class="rounded border me-3" style="object-fit:cover;">
                            <div>
                                <h6 class="fw-bold mb-1 text-dark" style="line-height: 1.4;"><?= htmlspecialchars($c['nama_produk']) ?></h6>
                                <span class="badge bg-light text-muted border fw-normal mb-1">Stok: <?= $c['stok'] ?></span>
                            </div>
                        </div>
                        
                        <div style="width: 15%;" class="text-center fw-bold text-muted d-none d-md-block">
                            Rp <?= number_format($c['harga'],0,',','.') ?>
                        </div>
                        
                        <div style="width: 15%;" class="d-flex justify-content-center mb-3 mb-md-0">
                            <div class="qty-group">
                                <button type="button" class="qty-btn btn-minus" data-id="<?= $c['id_cart'] ?>"><i class="fas fa-minus fa-xs"></i></button>
                                <input type="number" name="qty_<?= $c['id_cart'] ?>" id="qty_<?= $c['id_cart'] ?>" value="<?= $c['qty'] ?>" class="qty-input" readonly>
                                <button type="button" class="qty-btn btn-plus" data-id="<?= $c['id_cart'] ?>" data-stok="<?= $c['stok'] ?>"><i class="fas fa-plus fa-xs"></i></button>
                            </div>
                            <button type="submit" name="update_qty" id="submit_qty_<?= $c['id_cart'] ?>" class="d-none"></button>
                            <input type="hidden" name="cart_id" value="<?= $c['id_cart'] ?>" disabled>
                        </div>
                        
                        <div style="width: 15%;" class="text-center fw-bold text-sage-dark fs-6 mb-3 mb-md-0">
                            Rp <?= number_format($c['harga'] * $c['qty'],0,',','.') ?>
                        </div>
                        
                        <div style="width: 5%;" class="text-end text-md-center">
                            <a href="cart.php?hapus=<?= $c['id_cart'] ?>" class="text-muted border-0 bg-transparent" onclick="return confirm('Hapus produk ini dari keranjang?')" title="Hapus"><i class="fas fa-trash-alt fs-5 text-danger opacity-75 hover-opacity-100"></i></a>
                        </div>

                    </div>
                    <?php endforeach; ?>

                    <?php if(count($cart_list) == 0): ?>
                    <div class="text-center py-5 bg-white rounded-3 shadow-sm border">
                        <i class="fas fa-shopping-cart fa-4x text-muted mb-3 opacity-25"></i>
                        <h5 class="fw-bold text-dark">Keranjang belanja Anda kosong</h5>
                        <a href="index.php" class="btn btn-sage px-4 py-2 mt-2 rounded-pill fw-bold">Belanja Sekarang</a>
                    </div>
                    <?php endif; ?>

                </div>

                <div class="col-lg-4">
                    <div class="card shadow-sm border-0 sticky-summary p-4" style="border-radius: 16px; background-color: #fff;">
                        <h5 class="fw-bold text-dark mb-4 border-bottom pb-3">Ringkasan Belanja</h5>
                        
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-muted">Total Barang (<span id="totalItems">0</span>)</span>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <span class="fw-bold text-dark fs-5">Total Harga</span>
                            <span class="fw-bold fs-3 text-sage-dark" id="totalHarga">Rp 0</span>
                        </div>
                        
                        <button type="submit" id="btnCheckout" class="btn btn-sage w-100 fw-bold py-3 rounded-pill fs-5" disabled>
                            Checkout
                        </button>
                    </div>
                </div>

            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    document.addEventListener("DOMContentLoaded", function() {
        // Logika Hitung Total Checkbox
        const checkAll = document.getElementById('checkAll');
        const itemChecks = document.querySelectorAll('.item-check');
        const totalHargaEl = document.getElementById('totalHarga');
        const totalItemsEl = document.getElementById('totalItems');
        const btnCheckout = document.getElementById('btnCheckout');

        function hitungTotal() {
            let total = 0; let count = 0;
            itemChecks.forEach(cb => {
                if (cb.checked) {
                    total += (parseInt(cb.dataset.harga) * parseInt(cb.dataset.qty));
                    count++;
                }
            });
            totalHargaEl.innerText = 'Rp ' + total.toLocaleString('id-ID');
            totalItemsEl.innerText = count;
            btnCheckout.disabled = (count === 0);
        }

        if(checkAll) { checkAll.addEventListener('change', function() { itemChecks.forEach(cb => cb.checked = this.checked); hitungTotal(); }); }
        itemChecks.forEach(cb => { cb.addEventListener('change', function() { hitungTotal(); if(checkAll) checkAll.checked = Array.from(itemChecks).every(c => c.checked); }); });

        // Logika Tombol Plus Minus
        document.querySelectorAll('.btn-minus').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault(); let id = this.dataset.id; let input = document.getElementById('qty_' + id); let val = parseInt(input.value);
                if (val > 1) { input.value = val - 1; submitUpdate(id, val - 1); }
            });
        });

        document.querySelectorAll('.btn-plus').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault(); let id = this.dataset.id; let stok = parseInt(this.dataset.stok); let input = document.getElementById('qty_' + id); let val = parseInt(input.value);
                if (val < stok) { input.value = val + 1; submitUpdate(id, val + 1); } else { alert("Maksimal pembelian sesuai stok."); }
            });
        });

        // Submit Form Diam-diam
        function submitUpdate(idCart, newQty) {
            let form = document.createElement('form');
            form.method = 'POST'; form.action = 'cart.php'; form.style.display = 'none';
            let inputId = document.createElement('input'); inputId.name = 'cart_id'; inputId.value = idCart;
            let inputQty = document.createElement('input'); inputQty.name = 'update_qty'; inputQty.value = newQty;
            form.appendChild(inputId); form.appendChild(inputQty); document.body.appendChild(form); form.submit();
        }
    });
    </script>
</body>
</html>