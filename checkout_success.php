<?php
session_start();
require_once 'backend/config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$id_transaksi = (int)($_GET['id'] ?? 0);
if ($id_transaksi == 0) {
    header("Location: history.php");
    exit;
}

// Ambil info pesanan
$stmt = $conn->prepare("SELECT * FROM transaksi WHERE id = ? AND id_user = ?");
$stmt->execute([$id_transaksi, $_SESSION['user_id']]);
$trx = $stmt->fetch();

if (!$trx) {
    header("Location: history.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan Selesai - XrivaStore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="frontend/css/style.css?v=<?= time() ?>">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .step-indicator { display: flex; justify-content: center; margin-bottom: 30px; margin-top: 30px; }
        .step { display: flex; flex-direction: column; align-items: center; width: 100px; position: relative; }
        .step-circle { width: 35px; height: 35px; border-radius: 50%; background: #e9ecef; display: flex; align-items: center; justify-content: center; font-weight: bold; margin-bottom: 5px; z-index: 1; color: #6c757d; }
        .step.active .step-circle { background: var(--xriva-primary); color: white; }
        .step.done .step-circle { background: #198754; color: white; }
        .step:not(:last-child)::after { content: ''; position: absolute; top: 17px; left: 50px; width: 100%; height: 2px; background: #e9ecef; z-index: 0; }
        .step.done:not(:last-child)::after { background: #198754; }
        .step-label { font-size: 0.75rem; font-weight: bold; color: #6c757d; }
        .step.active .step-label { color: var(--xriva-primary); }
    </style>
</head>
<body class="bg-light">

<?php include 'frontend/includes/navbar.php'; ?>

<div class="container my-4 pb-5">
    <div class="step-indicator">
        <div class="step done">
            <div class="step-circle"><i class="fas fa-check"></i></div>
            <div class="step-label">Pengiriman</div>
        </div>
        <div class="step done">
            <div class="step-circle"><i class="fas fa-check"></i></div>
            <div class="step-label">Pembayaran</div>
        </div>
        <div class="step active">
            <div class="step-circle">3</div>
            <div class="step-label">Selesai</div>
        </div>
    </div>

    <div class="row justify-content-center mt-5">
        <div class="col-md-6 text-center">
            <div class="card border-0 shadow-sm rounded-4 p-5">
                <i class="fas fa-check-circle text-success mb-4" style="font-size: 5rem;"></i>
                <h3 class="fw-bold mb-3">Terima Kasih!</h3>
                
                <?php if ($trx['metode_pembayaran'] == 'COD'): ?>
                    <p class="text-muted mb-4">Pesanan Anda <strong>#<?= $trx['id'] ?></strong> telah dibuat. Silakan siapkan uang tunai saat kurir tiba di alamat Anda.</p>
                <?php else: ?>
                    <p class="text-muted mb-4">Pesanan Anda <strong>#<?= $trx['id'] ?></strong> telah dibuat dan bukti pembayaran telah kami terima. Kami akan segera memprosesnya setelah verifikasi.</p>
                <?php endif; ?>
                
                <div class="bg-light rounded-4 p-4 mb-4 text-start">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">ID Pesanan:</span>
                        <span class="fw-bold">#<?= $trx['id'] ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Total Tagihan:</span>
                        <span class="fw-bold text-sage-dark fs-5">Rp <?= number_format($trx['total_harga'], 0, ',', '.') ?></span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Metode Pembayaran:</span>
                        <span class="fw-bold"><?= htmlspecialchars($trx['metode_pembayaran']) ?></span>
                    </div>
                </div>

                <a href="history.php" class="btn btn-sage fw-bold rounded-pill px-5 py-3">Lihat Riwayat Pesanan</a>
                <div class="mt-3">
                    <a href="index.php" class="text-muted text-decoration-none small"><i class="fas fa-arrow-left me-1"></i> Kembali Belanja</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php if(isset($_SESSION['upload_msg'])): 
    $m = $_SESSION['upload_msg']; unset($_SESSION['upload_msg']);
?>
<script>
    Swal.fire({
        icon: '<?= $m['type'] === 'success' ? 'success' : 'error' ?>',
        title: '<?= $m['type'] === 'success' ? 'Berhasil' : 'Gagal' ?>',
        text: '<?= addslashes($m['text']) ?>',
        confirmButtonColor: '<?= $m['type'] === 'success' ? '#4a7c6b' : '#d33' ?>'
    });
</script>
<?php endif; ?>
</body>
</html>
