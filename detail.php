<?php
session_start();
require_once 'backend/config/database.php';

// Cek login
if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Silakan login terlebih dahulu untuk melihat detail produk!'); window.location.href='login.php';</script>";
    exit;
}

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

// ambil rating rata-rata dan jumlah
$stmtR = $conn->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as cnt FROM ratings WHERE id_produk = ?");
$stmtR->execute([$id_produk]);
$rinfo = $stmtR->fetch();

// ambil beberapa review terbaru
$stmtReviews = $conn->prepare("SELECT r.*, u.nama as user_nama FROM ratings r LEFT JOIN users u ON u.id = r.id_user WHERE r.id_produk = ? ORDER BY r.created_at DESC LIMIT 10");
$stmtReviews->execute([$id_produk]);
$reviews = $stmtReviews->fetchAll();

// Ambil produk terkait (same kategori, exclude produk ini, max 4)
$stmtRelated = $conn->prepare("SELECT * FROM produk WHERE kategori = ? AND id != ? AND stok > 0 ORDER BY RAND() LIMIT 4");
$stmtRelated->execute([$p['kategori'], $id_produk]);
$related_products = $stmtRelated->fetchAll();

$inisial = isset($_SESSION['user_nama']) ? strtoupper(substr($_SESSION['user_nama'], 0, 1)) : '';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($p['nama_produk']) ?> - XrivaStore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="frontend/css/style.css?v=<?= time() ?>">
</head>
<body class="bg-light">

<?php include 'frontend/includes/navbar.php'; ?>

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
                <img src="frontend/images/produk/<?= htmlspecialchars($p['gambar']) ?>" class="img-fluid" style="border-radius: 15px; width: 100%; height: auto; max-height: 500px; object-fit: cover;">
            </div>
        </div>

        <div class="col-md-6">
            <div class="ps-md-4">
                <h2 class="fw-bold text-dark mb-2"><?= htmlspecialchars($p['nama_produk']) ?></h2>
                <h3 class="text-sage-dark fw-bold mb-4">Rp <?= number_format($p['harga'], 0, ',', '.') ?></h3>
                
                <hr class="my-4 opacity-50">

                <div class="mb-3 d-flex align-items-center gap-3">
                    <div>
                        <span class="fw-bold">Rating:</span>
                        <span class="text-warning">
                            <?php if(!empty($rinfo['avg_rating'])): ?>
                                <?= number_format($rinfo['avg_rating'], 1) ?> <i class="fas fa-star"></i>
                            <?php else: ?>
                                Belum ada rating
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="text-muted small">(<?= $rinfo['cnt'] ?? 0 ?> ulasan)</div>
                </div>
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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php if (!empty($related_products)): ?>
<div class="container pb-5">
    <h5 class="fw-bold text-dark mb-4">
        <i class="fas fa-glasses me-2 text-sage-dark"></i> Produk Serupa
    </h5>
    <div class="row g-3">
        <?php foreach ($related_products as $rp): ?>
        <div class="col-6 col-md-3">
            <a href="detail.php?id=<?= $rp['id'] ?>" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100" style="border-radius:14px; overflow:hidden; transition:0.25s;" onmouseover="this.style.transform='translateY(-4px)';this.style.boxShadow='0 8px 20px rgba(0,0,0,0.1)'" onmouseout="this.style.transform='';this.style.boxShadow=''">
                    <div style="height:150px; overflow:hidden; background:#f8f9fa; display:flex; align-items:center; justify-content:center;">
                        <img src="frontend/images/produk/<?= htmlspecialchars($rp['gambar']) ?>"
                             style="max-height:150px; width:auto; max-width:100%; object-fit:contain;" alt="<?= htmlspecialchars($rp['nama_produk']) ?>">
                    </div>
                    <div class="card-body p-3">
                        <p class="mb-1 fw-semibold text-dark small" style="line-height:1.3;"><?= htmlspecialchars($rp['nama_produk']) ?></p>
                        <span class="fw-bold text-sage-dark">Rp <?= number_format($rp['harga'], 0, ',', '.') ?></span>
                    </div>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>
<?php if(isset($_SESSION['rating_msg'])): $rm = $_SESSION['rating_msg']; unset($_SESSION['rating_msg']); ?>
<script>
    Swal.fire({
        icon: '<?= $rm['type'] === 'success' ? 'success' : 'error' ?>',
        title: '<?= $rm['type'] === 'success' ? 'Sukses' : 'Gagal' ?>',
        text: '<?= addslashes($rm['text']) ?>',
        confirmButtonColor: '<?= $rm['type'] === 'success' ? '#4a7c6b' : '#d33' ?>'
    });
</script>
<?php endif; ?>
</body>
</html>