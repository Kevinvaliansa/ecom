<?php
session_start();
require_once 'backend/config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$id_user = $_SESSION['user_id'];

// PROSES UPLOAD BUKTI BAYAR
if (isset($_POST['upload_bukti'])) {
    $id_transaksi = $_POST['id_transaksi'];
    $gambar = $_FILES['bukti_bayar']['name'];
    $tmp = $_FILES['bukti_bayar']['tmp_name'];
    
    // Beri nama unik pada file agar tidak tertukar (id_transaksi + nama file)
    $nama_file_baru = $id_transaksi . "_" . $gambar;
    $path = "frontend/images/bukti/" . $nama_file_baru;

    if (move_uploaded_file($tmp, $path)) {
        $update = $conn->prepare("UPDATE transaksi SET bukti_bayar = ? WHERE id = ? AND id_user = ?");
        $update->execute([$nama_file_baru, $id_transaksi, $id_user]);
        $sukses = "Bukti pembayaran berhasil diunggah! Mohon tunggu verifikasi admin.";
    } else {
        $error = "Gagal mengunggah gambar.";
    }
}

// AMBIL DATA RIWAYAT TRANSAKSI USER
$stmt = $conn->prepare("SELECT * FROM transaksi WHERE id_user = ? ORDER BY tanggal_transaksi DESC");
$stmt->execute([$id_user]);
$riwayat = $stmt->fetchAll();
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

    <nav class="navbar navbar-expand-lg navbar-dark navbar-sage sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php"><i class="fas fa-leaf"></i> XrivaStore</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="cart.php">Keranjang</a></li>
                    <li class="nav-item"><a class="nav-link active fw-bold" href="history.php">Pesanan Saya</a></li>
                    <li class="nav-item"><a class="nav-link text-white" href="logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <h3 class="text-sage-dark fw-bold mb-4"><i class="fas fa-history"></i> Riwayat Pesanan Anda</h3>

        <?php if(isset($sukses)): ?>
            <div class="alert alert-success alert-dismissible fade show"><?= $sukses ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>

        <?php if(count($riwayat) > 0): ?>
            <?php foreach($riwayat as $r): ?>
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                        <div>
                            <span class="text-muted small">ID Pesanan:</span> <b>#<?= $r['id'] ?></b>
                            <span class="ms-3 text-muted small">Tanggal:</span> <?= date('d M Y', strtotime($r['tanggal_transaksi'])) ?>
                        </div>
                        <div>
                            <?php 
                                if($r['status_pesanan'] == 'diproses') echo '<span class="badge bg-warning text-dark px-3">Diproses</span>';
                                elseif($r['status_pesanan'] == 'dikirim') echo '<span class="badge bg-primary px-3">Dikirim</span>';
                                else echo '<span class="badge bg-success px-3">Selesai</span>';
                            ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-4">
                                <p class="mb-1 text-muted">Total Pembayaran:</p>
                                <h4 class="text-sage-dark fw-bold">Rp <?= number_format($r['total_harga'], 0, ',', '.') ?></h4>
                                <small class="text-muted">Metode: <?= $r['metode_pembayaran'] ?></small>
                            </div>
                            
                            <div class="col-md-4 border-start">
                                <?php if($r['metode_pembayaran'] == 'Transfer'): ?>
                                    <p class="mb-2 fw-bold">Bukti Pembayaran:</p>
                                    <?php if(empty($r['bukti_bayar'])): ?>
                                        <form method="POST" enctype="multipart/form-data" class="d-flex gap-2">
                                            <input type="hidden" name="id_transaksi" value="<?= $r['id'] ?>">
                                            <input type="file" name="bukti_bayar" class="form-control form-control-sm" accept="image/*" required>
                                            <button type="submit" name="upload_bukti" class="btn btn-sm btn-sage">Upload</button>
                                        </form>
                                        <small class="text-danger">*Segera upload bukti transfer</small>
                                    <?php else: ?>
                                        <div class="text-success"><i class="fas fa-check-circle"></i> Sudah diunggah</div>
                                        <a href="frontend/images/bukti/<?= $r['bukti_bayar'] ?>" target="_blank" class="small text-decoration-none">Lihat Bukti</a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <p class="text-muted">Bayar saat barang sampai (COD)</p>
                                <?php endif; ?>
                            </div>

                            <div class="col-md-4 text-end">
                                <a href="#" class="btn btn-outline-secondary btn-sm">Detail Pesanan</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-shopping-bag fa-3x text-sage-light mb-3"></i>
                <p class="text-muted">Kamu belum pernah melakukan pemesanan.</p>
                <a href="index.php" class="btn btn-sage">Mulai Belanja Sekarang</a>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>