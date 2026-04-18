<?php
session_start();
require_once 'backend/config/database.php';

// 1. PASTIKAN USER SUDAH LOGIN
if (!isset($_SESSION['user_id'])) {
    echo "<script>
        alert('Silakan login terlebih dahulu untuk memasukkan barang ke keranjang!');
        window.location.href = 'login.php';
    </script>";
    exit;
}

$id_user = $_SESSION['user_id'];

// 2. PROSES TAMBAH KE KERANJANG (Dari Halaman index.php)
if (isset($_POST['add_to_cart'])) {
    $id_produk = $_POST['id_produk'];
    $qty = (int)$_POST['qty'];

    // Cek apakah produk ini sudah ada di keranjang user
    $cek_cart = $conn->prepare("SELECT * FROM cart WHERE id_user = ? AND id_produk = ?");
    $cek_cart->execute([$id_user, $id_produk]);
    $cart_exist = $cek_cart->fetch();

    if ($cart_exist) {
        // Jika sudah ada, tambahkan jumlah (qty)-nya saja
        $new_qty = $cart_exist['qty'] + $qty;
        $update_cart = $conn->prepare("UPDATE cart SET qty = ? WHERE id = ?");
        $update_cart->execute([$new_qty, $cart_exist['id']]);
    } else {
        // Jika belum ada, masukkan sebagai baris baru
        $insert_cart = $conn->prepare("INSERT INTO cart (id_user, id_produk, qty) VALUES (?, ?, ?)");
        $insert_cart->execute([$id_user, $id_produk, $qty]);
    }
    
    // Refresh halaman agar form resubmission tidak terjadi
    header("Location: cart.php");
    exit;
}

// 3. PROSES HAPUS PRODUK DARI KERANJANG
if (isset($_GET['hapus'])) {
    $id_cart = $_GET['hapus'];
    $hapus = $conn->prepare("DELETE FROM cart WHERE id = ? AND id_user = ?");
    $hapus->execute([$id_cart, $id_user]);
    header("Location: cart.php");
    exit;
}

// 4. AMBIL DATA KERANJANG UNTUK DITAMPILKAN
$stmt = $conn->prepare("SELECT c.id as id_cart, c.qty, p.nama_produk, p.harga, p.gambar 
                        FROM cart c 
                        JOIN produk p ON c.id_produk = p.id 
                        WHERE c.id_user = ?");
$stmt->execute([$id_user]);
$cart_items = $stmt->fetchAll();

$total_belanja = 0; // Variabel untuk menghitung total harga
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Keranjang Belanja - XrivaStore</title>
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
                    <li class="nav-item"><a class="nav-link active" href="cart.php"><i class="fas fa-shopping-cart"></i> Keranjang</a></li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle fw-bold text-white" href="#" data-bs-toggle="dropdown">
                            Halo, <?= htmlspecialchars($_SESSION['user_nama']) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item text-danger" href="logout.php">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <h3 class="mb-4 text-sage-dark fw-bold"><i class="fas fa-shopping-basket"></i> Keranjang Belanja Anda</h3>
        
        <div class="row">
            <div class="col-md-8">
                <div class="card shadow-sm border-0">
                    <div class="card-body table-responsive">
                        <table class="table align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Produk</th>
                                    <th>Harga</th>
                                    <th>Jumlah</th>
                                    <th>Subtotal</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(count($cart_items) > 0): ?>
                                    <?php foreach($cart_items as $item): ?>
                                    <?php 
                                        $subtotal = $item['harga'] * $item['qty'];
                                        $total_belanja += $subtotal; // Tambahkan ke total keseluruhan
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="frontend/images/produk/<?= $item['gambar'] ?>" alt="" width="50" height="50" style="object-fit:cover; border-radius:5px; margin-right: 15px;">
                                                <span class="fw-bold"><?= htmlspecialchars($item['nama_produk']) ?></span>
                                            </div>
                                        </td>
                                        <td>Rp <?= number_format($item['harga'], 0, ',', '.') ?></td>
                                        <td><?= $item['qty'] ?></td>
                                        <td class="fw-bold text-sage-dark">Rp <?= number_format($subtotal, 0, ',', '.') ?></td>
                                        <td>
                                            <a href="cart.php?hapus=<?= $item['id_cart'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus produk ini dari keranjang?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">
                                            Keranjang belanja Anda masih kosong. <br>
                                            <a href="index.php" class="btn btn-sage btn-sm mt-2">Mulai Belanja</a>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card shadow-sm border-0 bg-sage-light">
                    <div class="card-body">
                        <h5 class="card-title text-sage-dark fw-bold mb-3">Ringkasan Belanja</h5>
                        <div class="d-flex justify-content-between mb-3">
                            <span>Total Harga:</span>
                            <span class="fw-bold fs-5">Rp <?= number_format($total_belanja, 0, ',', '.') ?></span>
                        </div>
                        <hr>
                        <a href="checkout.php" class="btn btn-sage w-100 fw-bold <?php echo ($total_belanja == 0) ? 'disabled' : ''; ?>">
                            Lanjut Pembayaran <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>