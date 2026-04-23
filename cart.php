<?php
session_start();
require_once 'backend/config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); exit;
}
$id_user = $_SESSION['user_id'];

// ==============================================================
// LOGIKA TAMBAH KERANJANG (DARI HALAMAN INDEX)
// ==============================================================
if (isset($_POST['add_to_cart'])) {
    $id_produk = $_POST['id_produk'];
    $qty = (int)$_POST['qty'];
    
    $cek = $conn->prepare("SELECT * FROM cart WHERE id_user = ? AND id_produk = ?");
    $cek->execute([$id_user, $id_produk]);
    
    if ($cek->rowCount() > 0) {
        $conn->prepare("UPDATE cart SET qty = qty + ? WHERE id_user = ? AND id_produk = ?")->execute([$qty, $id_user, $id_produk]);
    } else {
        $conn->prepare("INSERT INTO cart (id_user, id_produk, qty) VALUES (?, ?, ?)")->execute([$id_user, $id_produk, $qty]);
    }
    header("Location: cart.php"); exit;
}

// ==============================================================
// LOGIKA UPDATE JUMLAH BARANG (QTY)
// ==============================================================
if (isset($_POST['update_qty'])) {
    $cart_id = $_POST['cart_id'];
    $new_qty = $_POST['update_qty'];
    if($new_qty >= 1) {
        $update = $conn->prepare("UPDATE cart SET qty = ? WHERE id = ? AND id_user = ?");
        $update->execute([$new_qty, $cart_id, $id_user]);
    }
    header("Location: cart.php"); exit;
}

// ==============================================================
// LOGIKA HAPUS BARANG DARI KERANJANG
// ==============================================================
if (isset($_GET['hapus'])) {
    $id_hapus = $_GET['hapus'];
    $hapus = $conn->prepare("DELETE FROM cart WHERE id = ? AND id_user = ?");
    $hapus->execute([$id_hapus, $id_user]);
    header("Location: cart.php"); exit;
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
    <title>Keranjang Belanja - Xriva Eyewear</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background-color: #f4f7f6; font-family: 'Segoe UI', sans-serif; }
        .cart-card { background: #fff; border-radius: 16px; padding: 20px; margin-bottom: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.03); transition: 0.2s; border: none; }
        .custom-checkbox { width: 20px; height: 20px; cursor: pointer; accent-color: #7cb3a1; }
        .qty-input { width: 45px; height: 32px; text-align: center; border: none; font-weight: 600; font-size: 14px; background: transparent;}
        input[type="number"]::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark sticky-top shadow-sm py-2" style="background-color: #4a7c6b;">
        <div class="container">
            <a class="navbar-brand fw-bold fs-4" href="index.php"><i class="fas fa-glasses me-2"></i> Xriva Eyewear</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"><span class="navbar-toggler-icon"></span></button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center gap-3">
                    <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link active fw-bold" href="cart.php">Keranjang</a></li>
                    <li class="nav-item"><a class="nav-link" href="history.php">Pesanan</a></li>
                    <li class="nav-item dropdown ms-lg-2">
                        <div class="d-flex align-items-center cursor-pointer" data-bs-toggle="dropdown">
                            <div class="rounded-circle d-flex justify-content-center align-items-center bg-white text-dark fw-bold me-2" style="width: 35px; height: 35px;">
                                <?= strtoupper(substr($_SESSION['user_nama'], 0, 1)) ?>
                            </div>
                            <a class="nav-link dropdown-toggle fw-bold text-white p-0" href="#"><?= htmlspecialchars($_SESSION['user_nama']) ?></a>
                        </div>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-3" style="border-radius: 12px;">
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
        <h3 class="fw-bold text-dark mb-4">Keranjang Belanja Anda</h3>

        <form action="checkout.php" method="POST" id="form-checkout">
            <div class="row g-4">
                
                <div class="col-lg-8">
                    <?php foreach($cart_list as $c): ?>
                    <div class="cart-card d-flex align-items-center flex-wrap flex-md-nowrap">
                        <div style="width: 5%;" class="mb-3 mb-md-0">
                            <input type="checkbox" name="selected_items[]" value="<?= $c['id_cart'] ?>" class="custom-checkbox item-check" data-harga="<?= $c['harga'] ?>" data-qty="<?= $c['qty'] ?>">
                        </div>
                        <div style="width: 45%;" class="d-flex align-items-center pe-3">
                            <img src="frontend/images/produk/<?= htmlspecialchars($c['gambar']) ?>" width="80" height="80" class="rounded border me-3" style="object-fit:cover;">
                            <div>
                                <h6 class="fw-bold mb-1 text-dark"><?= htmlspecialchars($c['nama_produk']) ?></h6>
                                <span class="badge bg-light text-muted border fw-normal mb-1">Stok: <?= $c['stok'] ?></span>
                            </div>
                        </div>
                        <div style="width: 15%;" class="text-center fw-bold text-muted d-none d-md-block">
                            Rp <?= number_format($c['harga'],0,',','.') ?>
                        </div>
                        <div style="width: 15%;" class="d-flex justify-content-center">
                            <div class="d-flex align-items-center border rounded px-1">
                                <button type="button" class="btn btn-sm btn-light btn-minus" data-id="<?= $c['id_cart'] ?>"><i class="fas fa-minus"></i></button>
                                <input type="number" id="qty_<?= $c['id_cart'] ?>" value="<?= $c['qty'] ?>" class="qty-input" readonly>
                                <button type="button" class="btn btn-sm btn-light btn-plus" data-id="<?= $c['id_cart'] ?>" data-stok="<?= $c['stok'] ?>"><i class="fas fa-plus"></i></button>
                            </div>
                        </div>
                        <div style="width: 15%;" class="text-center fw-bold fs-6" style="color: #4a7c6b;">
                            Rp <?= number_format($c['harga'] * $c['qty'],0,',','.') ?>
                        </div>
                        <div style="width: 5%;" class="text-end text-md-center">
                            <a href="cart.php?hapus=<?= $c['id_cart'] ?>" class="text-muted" onclick="return confirm('Hapus produk ini dari keranjang?')" title="Hapus"><i class="fas fa-trash-alt fs-5 text-danger opacity-75"></i></a>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <?php if(count($cart_list) == 0): ?>
                    <div class="text-center py-5 bg-white shadow-sm border-0" style="border-radius: 16px;">
                        <i class="fas fa-shopping-cart fa-4x text-muted mb-3 opacity-25"></i>
                        <h5 class="fw-bold text-dark">Keranjang belanja kosong</h5>
                        <a href="index.php" class="btn text-white px-4 py-2 mt-2 rounded-pill fw-bold" style="background-color: #7cb3a1;">Mulai Belanja</a>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="col-lg-4">
                    <div class="card shadow-sm border-0 sticky-top p-4" style="border-radius: 16px; top: 20px;">
                        <h5 class="fw-bold text-dark mb-4 border-bottom pb-3">Ringkasan Belanja</h5>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-muted">Total Barang (<span id="totalItems">0</span>)</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <span class="fw-bold text-dark fs-5">Total Harga</span>
                            <span class="fw-bold fs-3" style="color: #4a7c6b;" id="totalHarga">Rp 0</span>
                        </div>
                        <button type="submit" id="btnCheckout" class="btn w-100 fw-bold py-3 text-white" style="border-radius: 12px; background-color: #7cb3a1;" disabled>
                            Checkout Sekarang
                        </button>
                    </div>
                </div>

            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        const itemChecks = document.querySelectorAll('.item-check');
        const totalHargaEl = document.getElementById('totalHarga');
        const totalItemsEl = document.getElementById('totalItems');
        const btnCheckout = document.getElementById('btnCheckout');

        function hitungTotal() {
            let total = 0; let count = 0;
            itemChecks.forEach(cb => {
                if (cb.checked) { total += (parseInt(cb.dataset.harga) * parseInt(cb.dataset.qty)); count++; }
            });
            totalHargaEl.innerText = 'Rp ' + total.toLocaleString('id-ID');
            totalItemsEl.innerText = count;
            btnCheckout.disabled = (count === 0);
        }
        itemChecks.forEach(cb => { cb.addEventListener('change', hitungTotal); });

        function submitUpdate(idCart, newQty) {
            let form = document.createElement('form');
            form.method = 'POST'; form.action = 'cart.php'; form.style.display = 'none';
            let inputId = document.createElement('input'); inputId.name = 'cart_id'; inputId.value = idCart;
            let inputQty = document.createElement('input'); inputQty.name = 'update_qty'; inputQty.value = newQty;
            form.appendChild(inputId); form.appendChild(inputQty); document.body.appendChild(form); form.submit();
        }

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
    });
    </script>
</body>
</html>