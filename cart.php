<?php
session_start();
require_once 'backend/config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); exit;
}
$id_user = $_SESSION['user_id'];

// ==============================================================
// LOGIKA TAMBAH KERANJANG (dari index.php via GET, atau form via POST)
// ==============================================================
$is_add_cart = isset($_POST['add_to_cart']) || isset($_GET['add_to_cart']) || isset($_POST['buy_now']) || isset($_GET['buy_now']);
if ($is_add_cart) {
    $id_produk = (int)($_POST['id_produk'] ?? $_GET['add_to_cart'] ?? $_GET['buy_now'] ?? 0);
    $qty       = (int)($_POST['qty']       ?? $_GET['qty']       ?? 1);
    $varian    = trim($_POST['varian']     ?? '');
    if ($qty < 1) $qty = 1;

    if ($id_produk > 0) {
        $cek = $conn->prepare("SELECT * FROM cart WHERE id_user = ? AND id_produk = ? AND varian = ?");
        $cek->execute([$id_user, $id_produk, $varian]);

        if ($cek->rowCount() > 0) {
            $conn->prepare("UPDATE cart SET qty = qty + ? WHERE id_user = ? AND id_produk = ? AND varian = ?")->execute([$qty, $id_user, $id_produk, $varian]);
            if (!isset($_POST['buy_now']) && !isset($_GET['buy_now'])) {
                $_SESSION['toast'] = ['type' => 'success', 'message' => 'Jumlah produk di keranjang diperbarui!'];
            }
        } else {
            $conn->prepare("INSERT INTO cart (id_user, id_produk, qty, varian) VALUES (?, ?, ?, ?)")->execute([$id_user, $id_produk, $qty, $varian]);
            if (!isset($_POST['buy_now']) && !isset($_GET['buy_now'])) {
                $_SESSION['toast'] = ['type' => 'success', 'message' => 'Produk berhasil ditambahkan ke keranjang!'];
            }
        }

        // Jika Beli Langsung, ambil ID cart-nya dan langsung arahkan ke checkout
        if (isset($_POST['buy_now']) || isset($_GET['buy_now'])) {
            $stmt_cid = $conn->prepare("SELECT id FROM cart WHERE id_user = ? AND id_produk = ? AND varian = ?");
            $stmt_cid->execute([$id_user, $id_produk, $varian]);
            $cart_id = $stmt_cid->fetchColumn();

            echo "
            <form id='buyNowForm' action='checkout.php' method='POST'>
                <input type='hidden' name='selected_items[]' value='$cart_id'>
            </form>
            <script>document.getElementById('buyNowForm').submit();</script>";
            exit;
        }
    }
    
    // Kembali ke halaman sebelumnya jika via GET, atau ke cart.php
    $redirect = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'cart.php';
    header("Location: $redirect"); exit;
}

// ==============================================================
// LOGIKA UPDATE JUMLAH BARANG (QTY)
// ==============================================================
if (isset($_POST['update_qty'])) {
    $cart_id = (int)$_POST['cart_id'];
    $new_qty = (int)$_POST['update_qty'];
    if ($new_qty >= 1) {
        $conn->prepare("UPDATE cart SET qty = ? WHERE id = ? AND id_user = ?")->execute([$new_qty, $cart_id, $id_user]);
    }
    header("Location: cart.php"); exit;
}

// ==============================================================
// LOGIKA HAPUS BARANG DARI KERANJANG
// ==============================================================
if (isset($_GET['hapus'])) {
    $id_hapus = (int)$_GET['hapus'];
    $conn->prepare("DELETE FROM cart WHERE id = ? AND id_user = ?")->execute([$id_hapus, $id_user]);
    $_SESSION['toast'] = ['type' => 'warning', 'message' => 'Produk dihapus dari keranjang.'];
    header("Location: cart.php"); exit;
}

// Ambil data keranjang
$stmt = $conn->prepare("SELECT c.id as id_cart, c.qty, c.varian, p.id as id_produk, p.nama_produk, p.harga, p.gambar, p.stok
                        FROM cart c JOIN produk p ON c.id_produk = p.id
                        WHERE c.id_user = ? ORDER BY c.id DESC");
$stmt->execute([$id_user]);
$cart_list = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang Belanja - XrivaStore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="frontend/css/style.css?v=<?= time() ?>">
    <style>
        .cart-card {
            background: #fff;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.04);
            transition: 0.2s;
            border: 1px solid #f0f0f0;
        }
        .cart-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.08); }
        .custom-checkbox { width: 20px; height: 20px; cursor: pointer; accent-color: var(--xriva-primary); }
        .qty-input { width: 45px; height: 32px; text-align: center; border: none; font-weight: 600; font-size: 14px; background: transparent; }
        input[type="number"]::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
        .total-price { color: var(--xriva-dark); }
        .btn-checkout {
            background-color: var(--xriva-primary);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            transition: 0.3s;
        }
        .btn-checkout:hover:not(:disabled) {
            background-color: var(--xriva-dark);
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 5px 12px rgba(82,121,111,0.3);
        }
        .btn-checkout:disabled { opacity: 0.5; cursor: not-allowed; }
    </style>
</head>
<body class="bg-light">

<?php include 'frontend/includes/navbar.php'; ?>

    <div class="container my-5 pb-5">
        <h3 class="fw-bold text-dark mb-4"><i class="fas fa-shopping-cart me-2" style="color: var(--xriva-primary);"></i> Keranjang Belanja</h3>

        <form action="checkout.php" method="POST" id="form-checkout">
            <div class="mb-3 d-flex align-items-center gap-3">
                <input type="checkbox" id="selectAll" class="custom-checkbox">
                <label for="selectAll" class="mb-0 small text-muted fw-semibold">Pilih Semua</label>
            </div>

            <div class="row g-4">
                <div class="col-lg-8">
                    <?php if (count($cart_list) > 0): ?>
                        <?php foreach ($cart_list as $c): ?>
                        <?php $exceeds_stock = $c['qty'] > $c['stok']; ?>
                        <div class="cart-card d-flex align-items-center flex-wrap flex-md-nowrap gap-3 <?= $exceeds_stock ? 'border border-danger border-2 bg-danger bg-opacity-10' : '' ?>">
                            <!-- Checkbox -->
                            <div style="flex: 0 0 30px;">
                                <input type="checkbox" name="selected_items[]" value="<?= $c['id_cart'] ?>"
                                       class="custom-checkbox item-check"
                                       data-harga="<?= $c['harga'] ?>"
                                       data-qty="<?= $c['qty'] ?>"
                                       <?= $exceeds_stock ? 'data-exceeds="true"' : '' ?>>
                            </div>
                            <!-- Gambar + Nama -->
                            <div class="d-flex align-items-center flex-grow-1 pe-3">
                                <img src="frontend/images/produk/<?= htmlspecialchars($c['gambar']) ?>"
                                     width="80" height="80" class="rounded border me-3"
                                     style="object-fit: contain; background:#f8f9fa;">
                                <div>
                                    <h6 class="fw-bold mb-1 text-dark"><?= htmlspecialchars($c['nama_produk']) ?></h6>
                                    <?php if (!empty($c['varian'])): ?>
                                        <div class="small text-muted mb-1"><i class="fas fa-tag me-1"></i>Varian: <?= htmlspecialchars($c['varian']) ?></div>
                                    <?php endif; ?>
                                    <span class="badge bg-light text-muted border fw-normal">Stok: <?= $c['stok'] ?></span>
                                    <?php if ($exceeds_stock): ?>
                                    <div class="text-danger fw-bold mt-1" style="font-size: 0.75rem;"><i class="fas fa-exclamation-triangle me-1"></i>Jumlah melebihi stok!</div>
                                    <?php endif; ?>
                                    <div class="text-muted small mt-1 d-md-none">Rp <?= number_format($c['harga'], 0, ',', '.') ?></div>
                                </div>
                            </div>
                            <!-- Harga (desktop) -->
                            <div class="text-center fw-semibold text-muted d-none d-md-block" style="min-width: 110px;">
                                Rp <?= number_format($c['harga'], 0, ',', '.') ?>
                            </div>
                            <!-- Qty control -->
                            <div class="d-flex justify-content-center" style="min-width: 110px;">
                                <div class="d-flex align-items-center border rounded px-1 bg-light">
                                    <button type="button" class="btn btn-sm btn-light btn-minus" data-id="<?= $c['id_cart'] ?>">
                                        <i class="fas fa-minus fa-xs"></i>
                                    </button>
                                    <input type="number" id="qty_<?= $c['id_cart'] ?>" value="<?= $c['qty'] ?>" class="qty-input" readonly>
                                    <button type="button" class="btn btn-sm btn-light btn-plus" data-id="<?= $c['id_cart'] ?>" data-stok="<?= $c['stok'] ?>">
                                        <i class="fas fa-plus fa-xs"></i>
                                    </button>
                                </div>
                            </div>
                            <!-- Total per item -->
                            <div class="text-center fw-bold fs-6 total-price" style="min-width: 110px;">
                                Rp <?= number_format($c['harga'] * $c['qty'], 0, ',', '.') ?>
                            </div>
                            <!-- Hapus -->
                            <div class="text-end" style="flex: 0 0 30px;">
                                <a href="cart.php?hapus=<?= $c['id_cart'] ?>"
                                   class="text-danger opacity-75"
                                   onclick="return confirm('Hapus produk ini dari keranjang?')"
                                   title="Hapus">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-5 bg-white shadow-sm" style="border-radius: 16px;">
                            <i class="fas fa-shopping-cart fa-4x text-muted mb-3 opacity-25"></i>
                            <h5 class="fw-bold text-dark">Keranjang belanja kosong</h5>
                            <a href="index.php" class="btn btn-sage px-4 py-2 mt-2 rounded-pill fw-bold">Mulai Belanja</a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Ringkasan -->
                <div class="col-lg-4">
                    <div class="card shadow-sm border-0 sticky-top p-4" style="border-radius: 16px; top: 80px;">
                        <h5 class="fw-bold text-dark mb-4 border-bottom pb-3">Ringkasan Belanja</h5>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted">Total Barang</span>
                            <span class="fw-bold" id="totalItems">0</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-4 border-top pt-3">
                            <span class="fw-bold text-dark fs-6">Total Harga</span>
                            <span class="fw-bold fs-4 total-price" id="totalHarga">Rp 0</span>
                        </div>
                        <button type="submit" id="btnCheckout" class="btn-checkout w-100 py-3" disabled>
                            <i class="fas fa-lock me-2"></i> Checkout Sekarang
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener("DOMContentLoaded", function () {
        const itemChecks   = document.querySelectorAll('.item-check');
        const selectAll    = document.getElementById('selectAll');
        const totalHargaEl = document.getElementById('totalHarga');
        const totalItemsEl = document.getElementById('totalItems');
        const btnCheckout  = document.getElementById('btnCheckout');

        function hitungTotal() {
            let total = 0, count = 0;
            let hasExceed = false;
            itemChecks.forEach(cb => {
                if (cb.checked) {
                    total += parseInt(cb.dataset.harga) * parseInt(cb.dataset.qty);
                    count++;
                    if (cb.dataset.exceeds === "true") hasExceed = true;
                }
            });
            totalHargaEl.innerText = 'Rp ' + total.toLocaleString('id-ID');
            totalItemsEl.innerText = count;
            
            if (hasExceed) {
                btnCheckout.disabled = true;
                btnCheckout.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i> Kurangi Stok';
                btnCheckout.style.backgroundColor = '#dc3545';
            } else {
                btnCheckout.disabled = (count === 0);
                btnCheckout.innerHTML = '<i class="fas fa-lock me-2"></i> Checkout Sekarang';
                btnCheckout.style.backgroundColor = 'var(--xriva-primary)';
            }
        }

        itemChecks.forEach(cb => cb.addEventListener('change', hitungTotal));

        if (selectAll) {
            selectAll.addEventListener('change', function () {
                itemChecks.forEach(cb => { cb.checked = selectAll.checked; });
                hitungTotal();
            });
            // Sinkronkan state selectAll bila semua item dicentang manual
            itemChecks.forEach(cb => {
                cb.addEventListener('change', function() {
                    selectAll.checked = [...itemChecks].every(c => c.checked);
                });
            });
        }

        function submitUpdate(idCart, newQty) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'cart.php';
            form.style.display = 'none';
            const inputId  = document.createElement('input'); inputId.name  = 'cart_id';    inputId.value  = idCart;
            const inputQty = document.createElement('input'); inputQty.name = 'update_qty'; inputQty.value = newQty;
            form.appendChild(inputId);
            form.appendChild(inputQty);
            document.body.appendChild(form);
            form.submit();
        }

        document.querySelectorAll('.btn-minus').forEach(btn => {
            btn.addEventListener('click', function () {
                const id    = this.dataset.id;
                const input = document.getElementById('qty_' + id);
                const val   = parseInt(input.value);
                if (val > 1) submitUpdate(id, val - 1);
            });
        });

        document.querySelectorAll('.btn-plus').forEach(btn => {
            btn.addEventListener('click', function () {
                const id    = this.dataset.id;
                const stok  = parseInt(this.dataset.stok);
                const input = document.getElementById('qty_' + id);
                const val   = parseInt(input.value);
                if (val < stok) {
                    submitUpdate(id, val + 1);
                } else {
                    alert('Maksimal pembelian sesuai stok (' + stok + ' pcs).');
                }
            });
        });
    });
    </script>
</body>
</html>