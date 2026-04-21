<?php
session_start();
require_once '../backend/config/database.php';

// CEK LOGIN DAN ROLE ADMIN
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    echo "<script>alert('Akses Ditolak! Anda bukan Admin.'); window.location.href='../login.php';</script>";
    exit;
}

// LOGIKA UPDATE STATUS PESANAN
if (isset($_GET['id']) && isset($_GET['status'])) {
    $id_transaksi = $_GET['id'];
    $status_baru = $_GET['status'];
    
    $update = $conn->prepare("UPDATE transaksi SET status_pesanan = ? WHERE id = ?");
    $update->execute([$status_baru, $id_transaksi]);
    
    header("Location: transaksi.php");
    exit;
}

// AMBIL DATA TRANSAKSI BESERTA NAMA PEMBELI
$stmt = $conn->query("
    SELECT t.*, u.nama 
    FROM transaksi t 
    JOIN users u ON t.id_user = u.id 
    ORDER BY t.tanggal_transaksi DESC
");
$transaksi_list = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Pesanan - Admin XrivaStore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-bootstrap-4/bootstrap-4.css" rel="stylesheet">
    <style>
        :root { --xriva-dark: #4a7c6b; --xriva-primary: #7cb3a1; }
        body { background-color: #f4f7f6; font-family: 'Segoe UI', sans-serif; }
        .sidebar { min-height: 100vh; background-color: var(--xriva-dark); color: white; border-top-right-radius: 20px; border-bottom-right-radius: 20px; box-shadow: 4px 0 15px rgba(0,0,0,0.1); }
        .sidebar .nav-link { color: rgba(255,255,255,0.8); margin-bottom: 10px; border-radius: 10px; padding: 12px 20px; transition: 0.3s; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background-color: rgba(255,255,255,0.15); color: white; font-weight: bold; }
        .content-area { padding: 30px; }
        .card { border-radius: 16px; border: none; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .table th { background-color: #f8f9fa; color: #555; font-weight: 600; border-bottom: 2px solid #eee; }
        .table td { vertical-align: middle; border-bottom: 1px solid #eee; }
        .btn-sage { background-color: var(--xriva-primary); color: white; border: none; }
        .btn-sage:hover { background-color: var(--xriva-dark); color: white; }
    </style>
</head>
<body>

<div class="d-flex">
    <div class="sidebar p-4" style="width: 280px;">
        <h4 class="fw-bold mb-5 text-center"><i class="fas fa-leaf"></i> AdminPanel</h4>
        <ul class="nav flex-column">
            <li class="nav-item"><a class="nav-link" href="produk.php"><i class="fas fa-box me-3"></i> Kelola Produk</a></li>
            <li class="nav-item"><a class="nav-link active" href="transaksi.php"><i class="fas fa-shopping-cart me-3"></i> Pesanan Masuk</a></li>
            <li class="nav-item"><a class="nav-link" href="laporan.php"><i class="fas fa-chart-line me-3"></i> Laporan Penjualan</a></li>
            <li class="nav-item mt-5"><a class="nav-link text-danger" href="../logout.php"><i class="fas fa-sign-out-alt me-3"></i> Keluar</a></li>
            <li class="nav-item"><a class="nav-link text-info" href="../index.php" target="_blank"><i class="fas fa-external-link-alt me-3"></i> Lihat Website</a></li>
        </ul>
    </div>

    <div class="content-area flex-grow-1">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold text-dark m-0">Pesanan Masuk</h2>
            <span class="text-muted"><i class="fas fa-user-circle"></i> Halo, <?= htmlspecialchars($_SESSION['user_nama']) ?></span>
        </div>

        <div class="card bg-white p-4">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tanggal</th>
                            <th>Pembeli</th>
                            <th>Total Tagihan</th>
                            <th>Metode</th>
                            <th>Status</th>
                            <th>Aksi Admin</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($transaksi_list as $t): ?>
                        <tr>
                            <td class="fw-bold text-muted">#<?= $t['id'] ?></td>
                            <td><?= date('d M Y, H:i', strtotime($t['tanggal_transaksi'])) ?></td>
                            <td class="fw-bold"><?= htmlspecialchars($t['nama']) ?></td>
                            <td class="text-success fw-bold">Rp <?= number_format($t['total_harga'], 0, ',', '.') ?></td>
                            <td>
                                <?php if($t['metode_pembayaran'] == 'Midtrans'): ?>
                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary"><i class="fas fa-credit-card"></i> Midtrans</span>
                                <?php else: ?>
                                    <span class="badge bg-warning bg-opacity-10 text-warning border border-warning"><i class="fas fa-truck"></i> COD</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                    if ($t['status_pesanan'] == 'pending') echo '<span class="badge bg-warning text-dark px-2 py-1 rounded-pill">Pending</span>';
                                    elseif ($t['status_pesanan'] == 'diproses') echo '<span class="badge bg-info text-dark px-2 py-1 rounded-pill">Diproses</span>';
                                    elseif ($t['status_pesanan'] == 'dikirim') echo '<span class="badge bg-primary px-2 py-1 rounded-pill">Dikirim</span>';
                                    elseif ($t['status_pesanan'] == 'selesai') echo '<span class="badge bg-success px-2 py-1 rounded-pill">Selesai</span>';
                                    else echo '<span class="badge bg-danger px-2 py-1 rounded-pill">Batal</span>';
                                ?>
                            </td>
                            <td>
                                <?php if ($t['status_pesanan'] == 'pending'): ?>
                                    <button onclick="updateStatus(<?= $t['id'] ?>, 'diproses', 'Konfirmasi Pembayaran?', 'Pesanan akan ditandai lunas dan mulai diproses.')" class="btn btn-sm btn-success rounded-pill px-3 shadow-sm"><i class="fas fa-check-circle"></i> Tandai Lunas</button>
                                <?php elseif ($t['status_pesanan'] == 'diproses'): ?>
                                    <button onclick="updateStatus(<?= $t['id'] ?>, 'dikirim', 'Kirim Pesanan?', 'Pesanan akan ditandai sedang dalam pengiriman.')" class="btn btn-sm btn-info text-white rounded-pill px-3 shadow-sm"><i class="fas fa-paper-plane"></i> Kirim</button>
                                <?php elseif ($t['status_pesanan'] == 'dikirim'): ?>
                                    <button onclick="updateStatus(<?= $t['id'] ?>, 'selesai', 'Pesanan Selesai?', 'Tandai pesanan ini telah diterima oleh pembeli.')" class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm"><i class="fas fa-flag-checkered"></i> Selesai</button>
                                <?php else: ?>
                                    <span class="text-muted small"><i class="fas fa-lock"></i> Tidak ada aksi</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if(count($transaksi_list) == 0): ?>
                        <tr><td colspan="7" class="text-center py-4 text-muted">Belum ada pesanan masuk.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function updateStatus(id, statusBaru, judul, deskripsi) {
    Swal.fire({
        title: judul,
        text: deskripsi,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#7cb3a1',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Lanjutkan!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = "transaksi.php?id=" + id + "&status=" + statusBaru;
        }
    })
}
</script>

</body>
</html>