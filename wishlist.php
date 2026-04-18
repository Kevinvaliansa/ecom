<?php
session_start();
require_once 'backend/config/database.php';

// PASTIKAN USER SUDAH LOGIN
if (!isset($_SESSION['user_id'])) {
    echo "<script>
        alert('Silakan login terlebih dahulu untuk menyimpan ke Wishlist!');
        window.location.href = 'login.php';
    </script>";
    exit;
}

$id_user = $_SESSION['user_id'];

// PROSES TAMBAH KE WISHLIST (Dari index.php)
if (isset($_GET['add'])) {
    $id_produk = $_GET['add'];
    
    // Cek dulu, jangan sampai barang yang sama dimasukkan 2 kali
    $cek_wishlist = $conn->prepare("SELECT * FROM wishlist WHERE id_user = ? AND id_produk = ?");
    $cek_wishlist->execute([$id_user, $id_produk]);
    
    if ($cek_wishlist->rowCount() == 0) {
        $insert = $conn->prepare("INSERT INTO wishlist (id_user, id_produk) VALUES (?, ?)");
        $insert->execute([$id_user, $id_produk]);
    }
    
    header("Location: wishlist.php");
    exit;
}

// PROSES HAPUS DARI WISHLIST
if (isset($_GET['hapus'])) {
    $id_wishlist = $_GET['hapus'];
    $hapus = $conn->prepare("DELETE FROM wishlist WHERE id = ? AND id_user = ?");
    $hapus->execute([$id_wishlist, $id_user]);
    header("Location: wishlist.php");
    exit;
}

// AMBIL DATA WISHLIST USER
$stmt = $conn->prepare("SELECT w.id as id_wishlist, p.id as id_produk, p.nama_produk, p.harga, p.gambar, p.stok 
                        FROM wishlist w 
                        JOIN produk p ON w.id_produk = p.id 
                        WHERE w.id_user = ?");
$stmt->execute([$id_user]);
$wishlist_items = $stmt->fetchAll();
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

    <nav class="navbar navbar-expand-lg navbar-dark navbar-sage sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php"><i class="fas fa-leaf"></i> XrivaStore</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link active fw-bold" href="wishlist.php"><i class="fas fa-heart"></i> Wishlist</a></li>
                    <li class="nav-item"><a class="nav-link" href="cart.php">Keranjang</a></li>
                    <li class="nav-item"><a class="nav-link" href="history.php">Pesanan Saya</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <h3 class="mb-4 text-sage-dark fw-bold"><i class="fas fa-heart text-danger"></i> Wishlist Saya</h3>

        <div class="row">
            <?php if(count($wishlist_items) > 0): ?>
                <?php foreach($wishlist_items as $item): ?>
                <div class="col-md-3 mb-4">
                    <div class="card h-100 shadow-sm border-0 position-relative">
                        
                        <a href="wishlist.php?hapus=<?= $item['id_wishlist'] ?>" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-2" style="border-radius: 50%;" title="Hapus dari Wishlist">
                            <i class="fas fa-times"></i>
                        </a>

                        <img src="frontend/images/produk/<?= htmlspecialchars($item['gambar']) ?>" class="card-img-top" style="height: 200px; object-fit: cover;">
                        
                        <div class="card-body text-center d-flex flex-column">
                            <h6 class="card-title fw-bold text-truncate"><?= htmlspecialchars($item['nama_produk']) ?></h6>
                            <p class="card-text text-sage-dark fw-bold mb-2">Rp <?= number_format($item['harga'], 0, ',', '.') ?></p>
                            <small class="text-muted mb-3">Stok: <?= $item['stok'] > 0 ? $item['stok'] . ' pcs' : '<span class="text-danger">Habis</span>' ?></small>
                            
                            <form action="cart.php" method="POST" class="mt-auto">
                                <input type="hidden" name="id_produk" value="<?= $item['id_produk'] ?>">
                                <input type="hidden" name="qty" value="1">
                                <button type="submit" name="add_to_cart" class="btn btn-sage btn-sm w-100" <?= $item['stok'] == 0 ? 'disabled' : '' ?>>
                                    <i class="fas fa-cart-plus"></i> Pindah ke Keranjang
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5">
                    <i class="far fa-heart fa-4x text-sage-light mb-3"></i>
                    <h5 class="text-muted">Wishlist kamu masih kosong.</h5>
                    <p class="text-muted">Yuk cari barang impianmu dan simpan di sini!</p>
                    <a href="index.php" class="btn btn-sage mt-2">Mulai Belanja</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>