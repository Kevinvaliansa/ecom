<?php
session_start();
require_once 'backend/config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Silakan login terlebih dahulu!'); window.location.href = 'login.php';</script>";
    exit;
}

$id_user = $_SESSION['user_id'];

if (isset($_GET['add'])) {
    $id_produk = $_GET['add'];
    $cek_wishlist = $conn->prepare("SELECT * FROM wishlist WHERE id_user = ? AND id_produk = ?");
    $cek_wishlist->execute([$id_user, $id_produk]);
    if ($cek_wishlist->rowCount() == 0) {
        $insert = $conn->prepare("INSERT INTO wishlist (id_user, id_produk) VALUES (?, ?)");
        $insert->execute([$id_user, $id_produk]);
    }
    header("Location: wishlist.php"); exit;
}

if (isset($_GET['hapus'])) {
    $id_wishlist = $_GET['hapus'];
    $hapus = $conn->prepare("DELETE FROM wishlist WHERE id = ? AND id_user = ?");
    $hapus->execute([$id_wishlist, $id_user]);
    $_SESSION['toast'] = ['type' => 'warning', 'message' => 'Produk dihapus dari Wishlist.'];
    header("Location: wishlist.php"); exit;
}

$stmt = $conn->prepare("SELECT w.id as id_wishlist, p.id as id_produk, p.nama_produk, p.harga, p.harga_coret, p.gambar, p.stok, p.kategori
                        FROM wishlist w JOIN produk p ON w.id_produk = p.id WHERE w.id_user = ?");
$stmt->execute([$id_user]);
$wishlist_items = $stmt->fetchAll();

$inisial = strtoupper(substr($_SESSION['user_nama'], 0, 1));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wishlist - XrivaStore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="frontend/css/style.css?v=<?= time() ?>">
</head>
<body class="bg-light">

    <?php include 'frontend/includes/navbar.php'; ?>

    <div class="container my-5 pb-5">

        <!-- Page Header -->
        <div class="d-flex align-items-center justify-content-between mb-4">
            <h3 class="fw-bold text-dark mb-0">
                <i class="fas fa-heart text-danger me-2"></i> Wishlist Saya
                <?php if (count($wishlist_items) > 0): ?>
                    <span class="badge rounded-pill ms-2" style="background: var(--xriva-light); color: var(--xriva-dark); font-size: 0.85rem; font-weight: 600;">
                        <?= count($wishlist_items) ?> produk
                    </span>
                <?php endif; ?>
            </h3>
            <a href="index.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                <i class="fas fa-arrow-left me-1"></i> Lanjut Belanja
            </a>
        </div>

        <div class="row" id="daftar-produk">
            <?php if (count($wishlist_items) > 0): ?>
                <?php foreach ($wishlist_items as $item): ?>
                <?php
                    $has_diskon = isset($item['harga_coret']) && $item['harga_coret'] > $item['harga'];
                    $persen = $has_diskon ? round((($item['harga_coret'] - $item['harga']) / $item['harga_coret']) * 100) : 0;
                ?>
                <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                    <div class="product-card position-relative">

                        <!-- Tombol hapus wishlist -->
                        <a href="wishlist.php?hapus=<?= $item['id_wishlist'] ?>"
                           class="btn btn-light text-danger position-absolute rounded-circle shadow-sm"
                           style="top: 10px; right: 10px; width: 35px; height: 35px; padding: 0; z-index: 10; display: flex; align-items: center; justify-content: center;"
                           title="Hapus dari Wishlist"
                           onclick="return confirm('Hapus dari Wishlist?')">
                            <i class="fas fa-heart"></i>
                        </a>

                        <div class="img-wrap">
                            <?php if ($has_diskon): ?>
                                <div class="discount-badge">
                                    <i class="fas fa-bolt me-1"></i> <?= $persen ?>% OFF
                                </div>
                            <?php endif; ?>
                            <a href="detail.php?id=<?= $item['id_produk'] ?>">
                                <img src="frontend/images/produk/<?= htmlspecialchars($item['gambar']) ?>"
                                     class="<?= $item['stok'] <= 0 ? 'img-out-of-stock' : '' ?>"
                                     alt="<?= htmlspecialchars($item['nama_produk']) ?>">
                            </a>
                        </div>

                        <div class="product-info">
                            <span class="category"><?= htmlspecialchars($item['kategori'] ?? 'Kacamata') ?></span>
                            <a href="detail.php?id=<?= $item['id_produk'] ?>" class="text-decoration-none">
                                <h6 class="product-name"><?= htmlspecialchars($item['nama_produk']) ?></h6>
                            </a>

                            <div class="d-flex justify-content-between align-items-center mt-auto">
                                <div class="product-price">
                                    <?php if ($has_diskon): ?>
                                        <div class="text-muted text-decoration-line-through" style="font-size: 0.75rem;">Rp <?= number_format($item['harga_coret'], 0, ',', '.') ?></div>
                                    <?php endif; ?>
                                    Rp <?= number_format($item['harga'], 0, ',', '.') ?>
                                </div>

                                <?php if ($item['stok'] > 0): ?>
                                    <form action="cart.php" method="POST" style="margin: 0;">
                                        <input type="hidden" name="id_produk" value="<?= $item['id_produk'] ?>">
                                        <input type="hidden" name="qty" value="1">
                                        <button type="submit" name="add_to_cart"
                                                class="btn-buy-now px-3 py-1 text-decoration-none small border-0"
                                                title="Pindah ke Keranjang">
                                            <i class="fas fa-cart-arrow-down"></i>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="badge bg-danger">Habis</span>
                                <?php endif; ?>
                            </div>
                        </div>

                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5">
                    <div class="bg-white p-5 rounded-4 shadow-sm">
                        <i class="far fa-heart fa-4x text-sage mb-3 opacity-50"></i>
                        <h5 class="text-muted fw-bold">Wishlist kamu masih kosong.</h5>
                        <p class="text-muted">Yuk cari barang impianmu dan simpan di sini!</p>
                        <a href="index.php" class="btn btn-sage mt-2 px-4 rounded-pill fw-bold">Mulai Belanja</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <footer class="text-white text-center py-4 mt-5" style="background-color: var(--xriva-dark);">
        <div class="container">
            <h5 class="fw-bold mb-2"><i class="fas fa-glasses"></i> Xriva Eyewear</h5>
            <p class="mb-2 small">&copy; <?= date('Y') ?> Xriva Eyewear. All Rights Reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>