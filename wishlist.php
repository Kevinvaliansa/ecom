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

    <?php include 'frontend/includes/navbar.php'; ?>
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

                        <div style="overflow: hidden; border-top-left-radius: 16px; border-top-right-radius: 16px; height: 200px; background: #f8f9fa; display:flex; align-items:center; justify-content:center;">
                            <img src="frontend/images/produk/<?= htmlspecialchars($item['gambar']) ?>" class="card-img-top" style="max-height: 200px; width: auto; max-width: 100%; object-fit: contain; <?= $item['stok'] <= 0 ? 'filter: grayscale(100%); opacity: 0.6;' : '' ?>">
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
                        <i class="far fa-heart fa-4x text-sage mb-3 opacity-50"></i>
                        <h5 class="text-muted fw-bold">Wishlist kamu masih kosong.</h5>
                        <p class="text-muted">Yuk cari barang impianmu dan simpan di sini!</p>
                        <a href="index.php" class="btn btn-sage mt-2 px-4 rounded-pill fw-bold">Mulai Belanja</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>