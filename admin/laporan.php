<?php
session_start();
require_once '../backend/config/database.php';

// CEK LOGIN DAN ROLE ADMIN
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    echo "<script>alert('Akses Ditolak! Anda bukan Admin.'); window.location.href='../login.php';</script>";
    exit;
}

// Menghitung Total Pendapatan (Hanya dari pesanan yang sudah 'selesai')
$stmt_pendapatan = $conn->query("SELECT SUM(total_harga) as omzet FROM transaksi WHERE status_pesanan = 'selesai'");
$data_pendapatan = $stmt_pendapatan->fetch();
$total_omzet = $data_pendapatan['omzet'] ? $data_pendapatan['omzet'] : 0;

// Menghitung Total Pesanan yang berhasil
$stmt_pesanan = $conn->query("SELECT COUNT(id) as jml_pesanan FROM transaksi WHERE status_pesanan = 'selesai'");
$data_pesanan = $stmt_pesanan->fetch();
$total_pesanan = $data_pesanan['jml_pesanan'];

// Mengambil rincian data transaksi yang sudah selesai
$stmt_detail = $conn->query("SELECT t.*, u.nama as nama_pembeli 
                             FROM transaksi t 
                             JOIN users u ON t.id_user = u.id 
                             WHERE t.status_pesanan = 'selesai'
                             ORDER BY t.tanggal_transaksi DESC");
$laporan_data = $stmt_detail->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Admin - Laporan Penjualan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --xriva-dark: #4a7c6b; --xriva-primary: #7cb3a1; }
        body { background-color: #f4f7f6; font-family: 'Segoe UI', sans-serif; }
        
        /* Sidebar Styling */
        .sidebar { min-height: 100vh; background-color: var(--xriva-dark); color: white; border-top-right-radius: 20px; border-bottom-right-radius: 20px; box-shadow: 4px 0 15px rgba(0,0,0,0.1); }
        .sidebar .nav-link { color: rgba(255,255,255,0.8); margin-bottom: 10px; border-radius: 10px; padding: 12px 20px; transition: 0.3s; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background-color: rgba(255,255,255,0.15); color: white; font-weight: bold; }
        
        /* Content Styling */
        .content-area { padding: 30px; }
        .card { border-radius: 16px; border: none; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .table th { background-color: #f8f9fa; color: #555; font-weight: 600; border-bottom: 2px solid #eee; }
        .table td { vertical-align: middle; border-bottom: 1px solid #eee; }
        
        .btn-sage { background-color: var(--xriva-primary); color: white; border: none; }
        .btn-sage:hover { background-color: var(--xriva-dark); color: white; }

        @media print {
            .sidebar, .btn-print, .header-top { display: none !important; }
            .content-area { padding: 0; width: 100%; }
            body { background-color: white; }
            .card { box-shadow: none !important; border: 1px solid #ddd !important; }
        }
    </style>
</head>
<body>

<div class="d-flex">
    <div class="sidebar p-4" style="width: 280px;">
        <h4 class="fw-bold mb-5 text-center"><i class="fas fa-leaf"></i> AdminPanel</h4>
        <ul class="nav flex-column">
            <li class="nav-item"><a class="nav-link" href="produk.php"><i class="fas fa-box me-3"></i> Kelola Produk</a></li>
            <li class="nav-item"><a class="nav-link" href="transaksi.php"><i class="fas fa-shopping-cart me-3"></i> Pesanan Masuk</a></li>
            <li class="nav-item"><a class="nav-link active" href="laporan.php"><i class="fas fa-chart-line me-3"></i> Laporan Penjualan</a></li>
            <li class="nav-item mt-5"><a class="nav-link text-danger" href="../logout.php"><i class="fas fa-sign-out-alt me-3"></i> Keluar</a></li>
            <li class="nav-item"><a class="nav-link text-info" href="../index.php" target="_blank"><i class="fas fa-external-link-alt me-3"></i> Lihat Website</a></li>
        </ul>
    </div>

    <div class="content-area flex-grow-1">
        <div class="d-flex justify-content-between align-items-center mb-4 header-top">
            <h2 class="fw-bold text-dark m-0">Rekapitulasi Penjualan</h2>
            <div class="d-flex align-items-center gap-3">
                <span class="text-muted"><i class="fas fa-user-circle"></i> Halo, <?= htmlspecialchars($_SESSION['user_nama']) ?></span>
                <button onclick="window.print()" class="btn btn-sage rounded-pill px-4 shadow-sm btn-print">
                    <i class="fas fa-print me-1"></i> Cetak Laporan
                </button>
            </div>
        </div>

        <div class="row mb-4 g-4">
            <div class="col-md-6">
                <div class="card bg-success text-white shadow h-100 p-4 d-flex justify-content-center align-items-center">
                    <h5 class="opacity-75 mb-2"><i class="fas fa-wallet me-2"></i> Total Pendapatan Kotor</h5>
                    <h1 class="display-5 fw-bold m-0">Rp <?= number_format($total_omzet, 0, ',', '.') ?></h1>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card bg-white text-dark shadow h-100 p-4 d-flex justify-content-center align-items-center" style="border: 2px solid var(--xriva-primary);">
                    <h5 class="text-muted mb-2"><i class="fas fa-check-circle me-2 text-success"></i> Pesanan Selesai</h5>
                    <h1 class="display-5 fw-bold text-sage-dark m-0"><?= $total_pesanan ?> <span class="fs-4 text-muted">Transaksi</span></h1>
                </div>
            </div>
        </div>

        <div class="card bg-white p-4">
            <h5 class="fw-bold text-dark mb-4">Rincian Transaksi Sukses</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID Pesanan</th>
                            <th>Tanggal Selesai</th>
                            <th>Nama Pelanggan</th>
                            <th>Metode Bayar</th>
                            <th class="text-end">Total Belanja</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($laporan_data) > 0): ?>
                            <?php foreach($laporan_data as $l): ?>
                            <tr>
                                <td class="fw-bold text-sage-dark">#<?= $l['id'] ?></td>
                                <td class="text-muted"><?= date('d F Y, H:i', strtotime($l['tanggal_transaksi'])) ?></td>
                                <td class="fw-bold"><?= htmlspecialchars($l['nama_pembeli']) ?></td>
                                <td><span class="badge bg-light text-dark border"><?= $l['metode_pembayaran'] ?></span></td>
                                <td class="text-end fw-bold text-success">Rp <?= number_format($l['total_harga'], 0, ',', '.') ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <tr class="table-light">
                                <td colspan="4" class="text-end fw-bold pt-4">GRAND TOTAL PENDAPATAN :</td>
                                <td class="text-end text-success fw-bold fs-5 pt-4">Rp <?= number_format($total_omzet, 0, ',', '.') ?></td>
                            </tr>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center py-4 text-muted">Belum ada pesanan yang berstatus selesai.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>