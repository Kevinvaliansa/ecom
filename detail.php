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
            <div class="card border-0 shadow-sm p-3 bg-white position-relative" style="border-radius: 20px;">
                <?php if(isset($p['harga_coret']) && $p['harga_coret'] > $p['harga']): 
                    $persen = round((($p['harga_coret'] - $p['harga']) / $p['harga_coret']) * 100);
                ?>
                    <div class="discount-badge" style="font-size: 1rem; padding: 6px 15px;">
                        <i class="fas fa-bolt me-1"></i> <?= $persen ?>% OFF
                    </div>
                <?php endif; ?>
                <img src="frontend/images/produk/<?= htmlspecialchars($p['gambar']) ?>" class="img-fluid" style="border-radius: 15px; width: 100%; height: auto; max-height: 500px; object-fit: cover;">
            </div>
        </div>

        <div class="col-md-6">
            <div class="ps-md-4">
                <h2 class="fw-bold text-dark mb-2"><?= htmlspecialchars($p['nama_produk']) ?></h2>
                
                <?php if(isset($p['harga_coret']) && $p['harga_coret'] > 0): ?>
                    <div class="flash-sale-container">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-bolt me-2"></i>
                            <span class="fw-bold text-uppercase">Flash Sale</span>
                        </div>
                        <div class="d-flex align-items-center" id="countdown">
                            <small class="me-2">Berakhir dalam</small>
                            <span class="timer-box" id="hours">02</span>:
                            <span class="timer-box" id="minutes">45</span>:
                            <span class="timer-box" id="seconds">10</span>
                        </div>
                    </div>
                    <h5 class="text-muted text-decoration-line-through mb-1">Rp <?= number_format($p['harga_coret'], 0, ',', '.') ?></h5>
                <?php endif; ?>
                <h3 class="text-sage-dark fw-bold mb-4" style="font-size: 2.2rem;">Rp <?= number_format($p['harga'], 0, ',', '.') ?></h3>
                
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

                <div class="mb-4 d-flex align-items-center flex-wrap gap-2">
                    <span class="me-2 fw-bold">Stok:</span>
                    <span class="badge bg-sage-light text-sage-dark px-3 py-2 rounded-pill shadow-sm">
                        <i class="fas fa-box-open me-1"></i> Tersisa <?= $p['stok'] ?> unit
                    </span>
                    <?php if($p['stok'] > 0 && $p['stok'] < 5): ?>
                        <span class="stock-warning ms-md-2">
                            <i class="fas fa-exclamation-triangle me-1"></i> Sisa <?= $p['stok'] ?> lagi! Segera checkout sebelum kehabisan.
                        </span>
                    <?php endif; ?>
                </div>

                <?php if (!empty($p['pilihan_varian'])): ?>
                <div class="mb-4">
                    <h6 class="fw-bold text-muted text-uppercase small mb-3">Pilih Varian</h6>
                    <div class="d-flex flex-wrap gap-2">
                        <?php 
                        $varians = explode(',', $p['pilihan_varian']);
                        foreach ($varians as $v): $v = trim($v);
                        ?>
                            <button type="button" class="btn btn-outline-sage btn-varian rounded-pill px-4" onclick="selectVarian(this, '<?= $v ?>')">
                                <?= $v ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                    <input type="hidden" id="selectedVarian" value="">
                </div>
                <?php endif; ?>

                <div class="row g-3 mt-5">
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-muted">Jumlah</label>
                        <div class="input-group">
                            <button class="btn btn-outline-secondary" type="button" onclick="changeDetailQty(-1)">-</button>
                            <input type="number" id="detailQty" class="form-control text-center fw-bold" value="1" min="1" max="<?= $p['stok'] ?>" readonly>
                            <button class="btn btn-outline-secondary" type="button" onclick="changeDetailQty(1)">+</button>
                        </div>
                    </div>
                    <div class="col-md-8 d-flex align-items-end gap-2">
                        <!-- Form Tambah ke Keranjang -->
                        <form action="cart.php" method="POST" class="w-50" onsubmit="return validateVarian()">
                            <input type="hidden" name="id_produk" value="<?= $p['id'] ?>">
                            <input type="hidden" name="qty" id="cartQty" value="1">
                            <input type="hidden" name="varian" class="input-varian-hidden" value="">
                            <button type="submit" name="add_to_cart" class="btn btn-lg w-100 fw-bold py-3 shadow-sm" <?= ($p['stok'] <= 0) ? 'disabled' : '' ?> style="border: 2px solid var(--xriva-primary); color: var(--xriva-primary); background: white;">
                                <i class="fas fa-cart-plus me-1"></i> Keranjang
                            </button>
                        </form>
                        
                        <!-- Form Beli Langsung (Direct Checkout) -->
                        <form action="checkout.php" method="POST" class="w-50" onsubmit="return validateVarian()">
                            <input type="hidden" name="direct_buy_id" value="<?= $p['id'] ?>">
                            <input type="hidden" name="direct_buy_qty" id="checkoutQty" value="1">
                            <input type="hidden" name="varian" class="input-varian-hidden" value="">
                            <button type="submit" class="btn btn-sage btn-lg w-100 fw-bold py-3 shadow-sm" <?= ($p['stok'] <= 0) ? 'disabled' : '' ?>>
                                Beli Langsung
                            </button>
                        </form>
                    </div>
                </div>

                <style>
                    .btn-outline-sage {
                        color: var(--xriva-primary);
                        border-color: var(--xriva-primary);
                    }
                    .btn-outline-sage:hover, .btn-varian.active {
                        background-color: var(--xriva-primary);
                        color: white;
                    }
                </style>

                <script>
                    function selectVarian(btn, val) {
                        // Remove active class from all buttons
                        document.querySelectorAll('.btn-varian').forEach(b => b.classList.remove('active'));
                        // Add active class to clicked button
                        btn.classList.add('active');
                        // Update hidden inputs
                        document.getElementById('selectedVarian').value = val;
                        document.querySelectorAll('.input-varian-hidden').forEach(inp => inp.value = val);
                    }

                    function validateVarian() {
                        const hasVarian = <?= !empty($p['pilihan_varian']) ? 'true' : 'false' ?>;
                        const selected = document.getElementById('selectedVarian').value;
                        if (hasVarian && !selected) {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Pilih Varian',
                                text: 'Silakan pilih varian kacamata terlebih dahulu!',
                                confirmButtonColor: '#4a7c6b'
                            });
                            return false;
                        }
                        return true;
                    }

                    function changeDetailQty(amt) {
                        const qtyInp = document.getElementById('detailQty');
                        const cartInp = document.getElementById('cartQty');
                        const checkoutInp = document.getElementById('checkoutQty');
                        let val = parseInt(qtyInp.value) + amt;
                        if (val >= 1 && val <= <?= $p['stok'] ?>) {
                            qtyInp.value = val;
                            cartInp.value = val;
                            checkoutInp.value = val;
                        }
                    }
                </script>

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
<script>
    // Countdown Timer Logic
    function updateCountdown() {
        const now = new Date();
        const endOfDay = new Date();
        endOfDay.setHours(23, 59, 59, 999);
        
        let diff = endOfDay - now;
        
        if (diff <= 0) {
            // Reset for next day if expired
            diff = 24 * 60 * 60 * 1000;
        }
        
        const h = Math.floor(diff / (1000 * 60 * 60));
        const m = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
        const s = Math.floor((diff % (1000 * 60)) / 1000);
        
        document.getElementById('hours') && (document.getElementById('hours').innerText = h.toString().padStart(2, '0'));
        document.getElementById('minutes') && (document.getElementById('minutes').innerText = m.toString().padStart(2, '0'));
        document.getElementById('seconds') && (document.getElementById('seconds').innerText = s.toString().padStart(2, '0'));
    }
    
    if(document.getElementById('countdown')) {
        setInterval(updateCountdown, 1000);
        updateCountdown();
    }
</script>
</body>
</html>