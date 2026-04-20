<?php
session_start();
require_once 'backend/config/database.php';

// AMBIL ID PRODUK DARI URL
if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id_produk = $_GET['id'];

// AMBIL DATA PRODUK
$stmt = $conn->prepare("SELECT * FROM produk WHERE id = ?");
$stmt->execute([$id_produk]);
$p = $stmt->fetch();

if (!$p) {
    echo "<script>alert('Produk tidak ditemukan!'); window.location.href='index.php';</script>";
    exit;
}

$inisial = isset($_SESSION['user_nama']) ? strtoupper(substr($_SESSION['user_nama'], 0, 1)) : '';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($p['nama_produk']) ?> - XrivaStore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="frontend/css/style.css">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark navbar-sage sticky-top shadow-sm py-2">
    <div class="container">
        <a class="navbar-brand fw-bold fs-4" href="index.php"><i class="fas fa-leaf"></i> XrivaStore</a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav ms-auto align-items-center gap-3">
                <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="wishlist.php"><i class="fas fa-heart me-1"></i> Wishlist</a></li>
                <li class="nav-item"><a class="nav-link" href="cart.php"><i class="fas fa-shopping-cart me-1"></i> Keranjang</a></li>
                <li class="nav-item"><a class="nav-link" href="history.php"><i class="fas fa-history me-1"></i> Pesanan</a></li>
                
                <?php if(isset($_SESSION['user_id'])): ?>
                <li class="nav-item dropdown ms-2 d-flex align-items-center border-start ps-3">
                    <div class="rounded-circle d-flex justify-content-center align-items-center bg-white text-sage-dark fw-bold me-2 shadow-sm" style="width: 35px; height: 35px; font-size: 1rem;">
                        <?= $inisial ?>
                    </div>
                    <a class="nav-link dropdown-toggle fw-bold text-white p-0" href="#" data-bs-toggle="dropdown"><?= htmlspecialchars($_SESSION['user_nama']) ?></a>
                    <ul class="dropdown-menu dropdown-menu-end mt-3 shadow border-0" style="border-radius: 12px;">
                        <li><a class="dropdown-item py-2" href="profile.php"><i class="fas fa-user-circle text-muted me-2"></i> Profil Saya</a></li>
                        <li><a class="dropdown-item py-2" href="history.php"><i class="fas fa-clipboard-list text-muted me-2"></i> Pesanan Saya</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger py-2" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                    </ul>
                </li>
                <?php else: ?>
                    <li class="nav-item ms-2"><a class="btn btn-outline-light btn-sm px-3 fw-bold" href="login.php">Login</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<div class="container my-5 pb-5">
    <nav aria-label="breadcrumb" class="mb-4">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none text-muted">Home</a></li>
        <li class="breadcrumb-item active text-sage-dark fw-bold" aria-current="page"><?= htmlspecialchars($p['nama_produk']) ?></li>
      </ol>
    </nav>

    <div class="row g-5">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm p-3 bg-white" style="border-radius: 20px;">
                <img src="frontend/images/produk/<?= htmlspecialchars($p['gambar']) ?>" class="img-fluid" style="border-radius: 15px; width: 100%; height: 500px; object-fit: cover;">
            </div>
        </div>

        <div class="col-md-6">
            <div class="ps-md-4">
                <h2 class="fw-bold text-dark mb-2"><?= htmlspecialchars($p['nama_produk']) ?></h2>
                <h3 class="text-sage-dark fw-bold mb-4">Rp <?= number_format($p['harga'], 0, ',', '.') ?></h3>
                
                <hr class="my-4 opacity-50">

                <div class="mb-4">
                    <h6 class="fw-bold text-muted text-uppercase small mb-2">Deskripsi Produk</h6>
                    <p class="text-secondary" style="line-height: 1.8;">
                        <?= nl2br(htmlspecialchars($p['deskripsi'] ?? 'Belum ada deskripsi untuk produk ini.')) ?>
                    </p>
                </div>

                <div class="mb-4 d-flex align-items-center">
                    <span class="me-3 fw-bold">Stok:</span>
                    <span class="badge bg-sage-light text-sage-dark px-3 py-2 rounded-pill shadow-sm">
                        <i class="fas fa-box-open me-1"></i> Tersisa <?= $p['stok'] ?> unit
                    </span>
                </div>

                <form action="cart.php" method="POST" class="mt-5">
                    <input type="hidden" name="id_produk" value="<?= $p['id'] ?>">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">Jumlah</label>
                            <input type="number" name="qty" class="form-control form-control-lg text-center fw-bold" value="1" min="1" max="<?= $p['stok'] ?>" <?= ($p['stok'] <= 0) ? 'disabled' : '' ?>>
                        </div>
                        <div class="col-md-8 d-flex align-items-end">
                            <button type="submit" name="add_to_cart" class="btn btn-sage btn-lg w-100 fw-bold py-3 shadow-sm" <?= ($p['stok'] <= 0) ? 'disabled' : '' ?>>
                                <i class="fas fa-cart-plus me-2"></i> Tambah ke Keranjang
                            </button>
                        </div>
                    </div>
                </form>

                <div class="mt-4">
                    <a href="index.php?add_wishlist=<?= $p['id'] ?>" class="text-decoration-none text-danger fw-bold">
                        <i class="far fa-heart"></i> Tambah ke Wishlist
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>