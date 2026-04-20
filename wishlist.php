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
    header("Location: wishlist.php"); exit;
}

$stmt = $conn->prepare("SELECT w.id as id_wishlist, p.id as id_produk, p.nama_produk, p.harga, p.gambar, p.stok 
                        FROM wishlist w JOIN produk p ON w.id_produk = p.id WHERE w.id_user = ?");
$stmt->execute([$id_user]);
$wishlist_items = $stmt->fetchAll();

$inisial = strtoupper(substr($_SESSION['user_nama'], 0, 1));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Wishlist - XrivaStore</title>
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
    <div class="container my-5 pb-5">
        <h3 class="mb-4 text-sage-dark fw-bold"><i class="fas fa-heart text-danger"></i> Wishlist Saya</h3>

        <div class="row">
            <?php if(count($wishlist_items) > 0): ?>
                <?php foreach($wishlist_items as $item): ?>
                <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                    <div class="card h-100 shadow-sm border-0 position-relative bg-white" style="border-radius: 16px;">
                        
                        <a href="wishlist.php?hapus=<?= $item['id_wishlist'] ?>" class="btn btn-light text-danger position-absolute rounded-circle shadow-sm" style="top: 10px; right: 10px; width: 35px; height: 35px; padding: 5px; z-index: 2;" title="Hapus dari Wishlist">
                            <i class="fas fa-times mt-1"></i>
                        </a>

                        <div style="overflow: hidden; border-top-left-radius: 16px; border-top-right-radius: 16px;">
                            <img src="frontend/images/produk/<?= htmlspecialchars($item['gambar']) ?>" class="card-img-top" style="height: 220px; object-fit: cover;">
                        </div>
                        
                        <div class="card-body d-flex flex-column p-4 text-center">
                            <h6 class="card-title fw-bold text-dark text-truncate mb-1"><?= htmlspecialchars($item['nama_produk']) ?></h6>
                            <h5 class="card-text text-sage-dark fw-bold mb-2">Rp <?= number_format($item['harga'], 0, ',', '.') ?></h5>
                            
                            <?php if($item['stok'] > 0): ?>
                                <small class="text-muted mb-4"><i class="fas fa-box-open me-1"></i> Sisa <?= $item['stok'] ?> Pcs</small>
                            <?php else: ?>
                                <small class="text-danger fw-bold mb-4"><i class="fas fa-times-circle me-1"></i> Stok Habis</small>
                            <?php endif; ?>
                            
                            <form action="cart.php" method="POST" class="mt-auto w-100">
                                <input type="hidden" name="id_produk" value="<?= $item['id_produk'] ?>">
                                <input type="hidden" name="qty" value="1">
                                <button type="submit" name="add_to_cart" class="btn btn-sage w-100 fw-bold" <?= $item['stok'] == 0 ? 'disabled' : '' ?>>
                                    <i class="fas fa-cart-arrow-down"></i> Pindah ke Keranjang
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5">
                    <div class="bg-white p-5 rounded-4 shadow-sm">
                        <i class="far fa-heart fa-4x text-sage-light mb-3"></i>
                        <h5 class="text-muted fw-bold">Wishlist kamu masih kosong.</h5>
                        <p class="text-muted">Yuk cari barang impianmu dan simpan di sini!</p>
                        <a href="index.php" class="btn btn-sage mt-2 px-4 rounded-pill">Mulai Belanja</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>