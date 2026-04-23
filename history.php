<?php
session_start();
require_once 'backend/config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); exit;
}
$id_user = $_SESSION['user_id'];

if (isset($_GET['cancel'])) {
    $id_cancel = $_GET['cancel'];
    $conn->prepare("UPDATE transaksi SET status_pesanan = 'batal' WHERE id = ? AND id_user = ? AND status_pesanan = 'pending'")->execute([$id_cancel, $id_user]);
    header("Location: history.php"); exit;
}

$stmt = $conn->prepare("SELECT * FROM transaksi WHERE id_user = ? ORDER BY tanggal_transaksi DESC");
$stmt->execute([$id_user]);
$riwayat = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Riwayat Pesanan - Xriva Eyewear</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-bootstrap-4/bootstrap-4.css" rel="stylesheet">
    <style>
        body { background-color: #f4f7f6; font-family: 'Segoe UI', sans-serif; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark py-2 shadow-sm sticky-top" style="background-color: #4a7c6b;">
    <div class="container">
        <a class="navbar-brand fw-bold fs-4" href="index.php"><i class="fas fa-glasses me-2"></i> Xriva Eyewear</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"><span class="navbar-toggler-icon"></span></button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center gap-3">
                <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="cart.php">Keranjang</a></li>
                <li class="nav-item"><a class="nav-link active fw-bold" href="history.php">Pesanan</a></li>
                <li class="nav-item dropdown ms-lg-2">
                    <div class="d-flex align-items-center cursor-pointer" data-bs-toggle="dropdown">
                        <div class="rounded-circle d-flex justify-content-center align-items-center bg-white text-dark fw-bold me-2 shadow-sm" style="width: 35px; height: 35px;">
                            <?= strtoupper(substr($_SESSION['user_nama'], 0, 1)) ?>
                        </div>
                        <a class="nav-link dropdown-toggle fw-bold text-white p-0" href="#"><?= htmlspecialchars($_SESSION['user_nama']) ?></a>
                    </div>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-3" style="border-radius: 12px;">
                        <li><a class="dropdown-item" href="history.php">Pesanan Saya</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="logout.php">Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container my-5 pb-5">
    <h3 class="fw-bold mb-4 text-dark"><i class="fas fa-history me-2" style="color: #4a7c6b;"></i>Riwayat Pesanan Saya</h3>

    <?php if(count($riwayat) == 0): ?>
        <div class="text-center py-5 bg-white shadow-sm" style="border-radius: 16px;">
            <i class="fas fa-box-open fa-3x text-muted mb-3 opacity-50"></i>
            <h5 class="text-muted">Belum ada pesanan nih.</h5>
            <a href="index.php" class="btn text-white px-4 mt-2 rounded-pill fw-bold" style="background-color: #7cb3a1;">Mulai Belanja</a>
        </div>
    <?php endif; ?>

    <?php foreach($riwayat as $r): ?>
    <div class="card shadow-sm border-0 mb-4" style="border-radius: 16px;">
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
            <div class="text-muted small">
                <i class="fas fa-shopping-bag me-1"></i> <?= date('d M Y, H:i', strtotime($r['tanggal_transaksi'])) ?> | ID: <span class="fw-bold">#<?= $r['id'] ?></span>
            </div>
            <div>
                <?php 
                    $s = $r['status_pesanan'];
                    if($s == 'pending') echo '<span class="badge bg-warning text-dark px-3 py-2 rounded-pill">Menunggu Pembayaran</span>';
                    elseif($s == 'diproses') echo '<span class="badge bg-info text-dark px-3 py-2 rounded-pill">Sedang Diproses</span>';
                    elseif($s == 'dikirim') echo '<span class="badge bg-primary px-3 py-2 rounded-pill">Sedang Dikirim</span>';
                    elseif($s == 'selesai') echo '<span class="badge bg-success px-3 py-2 rounded-pill">Selesai</span>';
                    else echo '<span class="badge bg-danger px-3 py-2 rounded-pill">Dibatalkan</span>';
                ?>
            </div>
        </div>

        <div class="card-body p-4">
            <div class="row align-items-center">
                <div class="col-md-5 border-end">
                    <p class="text-muted small mb-1">Total Pembayaran</p>
                    <h4 class="fw-bold mb-2" style="color: #4a7c6b;">Rp <?= number_format($r['total_harga'], 0, ',', '.') ?></h4>
                    <span class="badge bg-light text-dark border"><i class="fas fa-wallet me-1"></i> <?= htmlspecialchars($r['metode_pembayaran']) ?></span>
                </div>
                
                <div class="col-md-7 text-end d-flex gap-2 justify-content-end mt-3 mt-md-0">
                    <button class="btn btn-outline-secondary rounded-pill px-4 fw-bold" data-bs-toggle="modal" data-bs-target="#modalDetail<?= $r['id'] ?>">Detail Pesanan</button>
                    <?php if($r['status_pesanan'] == 'pending'): ?>
                        <button class="btn btn-outline-danger rounded-pill px-4 fw-bold" onclick="confirmCancel(<?= $r['id'] ?>)">Batalkan</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalDetail<?= $r['id'] ?>" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0" style="border-radius: 16px;">
                <div class="modal-header" style="background-color: #4a7c6b; color: white; border-radius: 16px 16px 0 0;">
                    <h5 class="modal-title fw-bold"><i class="fas fa-box-open me-2"></i> Isi Paket Pesanan #<?= $r['id'] ?></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    
                    <?php
                    // Ambil detail barang dari database
                    $stmt_dtl = $conn->prepare("SELECT * FROM detail_transaksi WHERE id_transaksi = ?");
                    $stmt_dtl->execute([$r['id']]);
                    $items = $stmt_dtl->fetchAll();
                    
                    if(count($items) > 0):
                        foreach($items as $item): ?>
                        <div class="d-flex align-items-center mb-3 border-bottom pb-3">
                            <img src="frontend/images/produk/<?= htmlspecialchars($item['gambar']) ?>" width="70" height="70" class="rounded border me-3" style="object-fit: cover;">
                            <div class="flex-grow-1">
                                <h6 class="m-0 fw-bold text-dark"><?= htmlspecialchars($item['nama_produk']) ?></h6>
                                <p class="m-0 text-muted small"><?= $item['jumlah'] ?> pcs x Rp <?= number_format($item['harga'], 0, ',', '.') ?></p>
                            </div>
                            <div class="fw-bold text-dark">
                                Rp <?= number_format($item['harga'] * $item['jumlah'], 0, ',', '.') ?>
                            </div>
                        </div>
                        <?php endforeach; 
                    else: ?>
                        <div class="alert alert-warning text-center small rounded-3">
                            <i class="fas fa-exclamation-triangle me-1"></i> Data detail barang untuk transaksi lama ini tidak tersedia.
                        </div>
                    <?php endif; ?>

                    <div class="d-flex justify-content-between mt-4 pt-2">
                        <span class="fw-bold fs-5 text-muted">Total Tagihan</span>
                        <span class="fw-bold fs-5" style="color: #4a7c6b;">Rp <?= number_format($r['total_harga'], 0, ',', '.') ?></span>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light w-100 rounded-pill fw-bold" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function confirmCancel(id) {
        Swal.fire({
            title: 'Batalkan Pesanan?',
            text: "Pesanan ini akan langsung dibatalkan.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Batalkan!'
        }).then((result) => {
            if (result.isConfirmed) { window.location.href = "history.php?cancel=" + id; }
        })
    }
</script>
</body>
</html>