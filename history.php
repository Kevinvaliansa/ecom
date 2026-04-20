<?php
session_start();
require_once 'backend/config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); exit;
}

$id_user = $_SESSION['user_id'];

if (isset($_POST['upload_bukti'])) {
    $id_transaksi = $_POST['id_transaksi'];
    $gambar = $_FILES['bukti_bayar']['name'];
    $tmp = $_FILES['bukti_bayar']['tmp_name'];
    $nama_file_baru = $id_transaksi . "_" . time() . "_" . $gambar;
    $path = "frontend/images/bukti/" . $nama_file_baru;

    if (move_uploaded_file($tmp, $path)) {
        $update = $conn->prepare("UPDATE transaksi SET bukti_bayar = ? WHERE id = ? AND id_user = ?");
        $update->execute([$nama_file_baru, $id_transaksi, $id_user]);
        $sukses = "Bukti pembayaran berhasil diunggah! Mohon tunggu verifikasi admin.";
    } else {
        $error = "Gagal mengunggah gambar.";
    }
}

$stmt = $conn->prepare("SELECT * FROM transaksi WHERE id_user = ? ORDER BY tanggal_transaksi DESC");
$stmt->execute([$id_user]);
$riwayat = $stmt->fetchAll();

$inisial = strtoupper(substr($_SESSION['user_nama'], 0, 1));
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Riwayat Pesanan - XrivaStore</title>
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
                <form class="d-flex mx-auto my-2 my-lg-0" style="width: 40%;" action="index.php" method="GET">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 py-2"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" name="search" id="live-search" class="form-control border-start-0 shadow-none py-2" placeholder="Cari kursi, meja, piring...">
                    </div>
                </form>

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
        <h3 class="text-sage-dark fw-bold mb-4"><i class="fas fa-history text-primary" style="color: var(--xriva-primary)!important;"></i> Riwayat Pesanan Anda</h3>

        <?php if(isset($sukses)): ?>
            <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm"><?= $sukses ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>

        <?php if(count($riwayat) > 0): ?>
            <?php foreach($riwayat as $r): ?>
                <div class="card shadow-sm border-0 mb-4 bg-white" style="border-radius: 16px; overflow: hidden;">
                    <div class="card-header bg-transparent d-flex justify-content-between align-items-center py-3 border-bottom">
                        <div>
                            <span class="text-muted small me-2"><i class="fas fa-shopping-bag"></i> Belanja</span>
                            <span class="text-muted small">| Tanggal: <span class="fw-bold text-dark"><?= date('d M Y', strtotime($r['tanggal_transaksi'])) ?></span></span>
                            <span class="text-muted small ms-2">| ID Pesanan: <span class="fw-bold text-sage-dark">#<?= $r['id'] ?></span></span>
                        </div>
                        <div>
                            <?php 
                                if($r['status_pesanan'] == 'diproses') echo '<span class="badge bg-warning text-dark px-3 py-2 rounded-pill shadow-sm">Sedang Diproses</span>';
                                elseif($r['status_pesanan'] == 'dikirim') echo '<span class="badge bg-primary px-3 py-2 rounded-pill shadow-sm">Dalam Pengiriman</span>';
                                else echo '<span class="badge bg-success px-3 py-2 rounded-pill shadow-sm"><i class="fas fa-check"></i> Selesai</span>';
                            ?>
                        </div>
                    </div>
                    
                    <div class="card-body p-4">
                        <div class="row align-items-center">
                            <div class="col-md-4 mb-3 mb-md-0 border-end">
                                <p class="mb-1 text-muted small">Total Pembayaran</p>
                                <h4 class="text-sage-dark fw-bold mb-1">Rp <?= number_format($r['total_harga'], 0, ',', '.') ?></h4>
                                <span class="badge bg-light text-dark border"><i class="fas fa-wallet text-muted"></i> <?= $r['metode_pembayaran'] ?></span>
                            </div>
                            
                            <div class="col-md-5 mb-3 mb-md-0 px-md-4">
                                <?php if($r['metode_pembayaran'] == 'Transfer'): ?>
                                    <p class="mb-2 fw-bold text-dark small">Bukti Pembayaran:</p>
                                    <?php if(empty($r['bukti_bayar'])): ?>
                                        
                                        <form method="POST" enctype="multipart/form-data" class="d-flex align-items-center gap-2">
                                            <input type="hidden" name="id_transaksi" value="<?= $r['id'] ?>">
                                            
                                            <input type="file" name="bukti_bayar" id="bukti_<?= $r['id'] ?>" class="d-none" accept="image/*" required onchange="updateFileName(this, <?= $r['id'] ?>)">
                                            <label for="bukti_<?= $r['id'] ?>" class="btn btn-outline-secondary btn-sm mb-0 rounded-pill px-3 text-truncate" id="label_<?= $r['id'] ?>" style="max-width: 150px;" title="Pilih Gambar">
                                                <i class="fas fa-image"></i> Pilih Gambar
                                            </label>
                                            
                                            <button type="submit" name="upload_bukti" class="btn btn-sm btn-sage rounded-pill px-3 shadow-sm">Upload</button>
                                        </form>
                                        <small class="text-danger mt-1 d-block" style="font-size: 0.75rem;">*Harap segera unggah bukti transfer</small>
                                        
                                    <?php else: ?>
                                        <div class="d-flex align-items-center">
                                            <div class="text-success fw-bold me-3"><i class="fas fa-check-circle"></i> Berhasil Diunggah</div>
                                            <a href="frontend/images/bukti/<?= $r['bukti_bayar'] ?>" target="_blank" class="btn btn-sm btn-light border rounded-pill text-sage-dark fw-bold">Lihat Foto</a>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <p class="text-muted small mb-0"><i class="fas fa-truck text-sage-dark me-2"></i> Pembayaran dilakukan saat barang sampai (COD).</p>
                                <?php endif; ?>
                            </div>

                            <div class="col-md-3 text-md-end text-start mt-3 mt-md-0 border-start">
                                <a href="#" class="btn btn-outline-sage btn-sm rounded-pill px-4 fw-bold">Detail Pesanan</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="text-center py-5">
                <div class="bg-white p-5 rounded-4 shadow-sm">
                    <i class="fas fa-receipt fa-4x text-sage-light mb-3"></i>
                    <h5 class="text-muted fw-bold">Kamu belum pernah berbelanja.</h5>
                    <p class="text-muted">Ayo mulai penuhi keranjangmu dengan produk pilihan kami!</p>
                    <a href="index.php" class="btn btn-sage mt-2 px-4 rounded-pill">Mulai Belanja</a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Script untuk mengubah teks label tombol saat gambar dipilih
        function updateFileName(inputElement, id) {
            const labelElement = document.getElementById('label_' + id);
            if (inputElement.files && inputElement.files.length > 0) {
                labelElement.innerHTML = '<i class="fas fa-check"></i> ' + inputElement.files[0].name;
                labelElement.classList.remove('btn-outline-secondary');
                labelElement.classList.add('btn-outline-success');
            }
        }
    </script>
</body>
</html>