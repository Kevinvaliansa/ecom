<?php
require_once '../backend/config/database.php';

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
    <link rel="stylesheet" href="../frontend/css/style.css">
    <style>
        /* CSS untuk menghilangkan elemen tertentu saat halaman diprint */
        @media print {
            .navbar, .btn-print { display: none !important; }
            body { background-color: white; }
            .card { border: none !important; box-shadow: none !important; }
        }
    </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark navbar-sage mb-4">
    <div class="container-fluid">
        <span class="navbar-brand mb-0 h1 fw-bold">Dashboard Admin</span>
        <div class="collapse navbar-collapse" id="adminNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="produk.php">Kelola Produk</a></li>
                <li class="nav-item"><a class="nav-link" href="transaksi.php">Kelola Transaksi</a></li>
                <li class="nav-item"><a class="nav-link active fw-bold" href="laporan.php">Laporan Penjualan</a></li>
            </ul>
            <a href="../index.php" class="btn btn-outline-light btn-sm" target="_blank">Lihat Website</a>
        </div>
    </div>
</nav>

<div class="container my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="text-sage-dark fw-bold m-0"><i class="fas fa-chart-line"></i> Rekapitulasi Laporan Penjualan</h3>
        <button onclick="window.print()" class="btn btn-sage btn-print"><i class="fas fa-print"></i> Cetak Laporan</button>
    </div>

    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card text-white bg-sage-primary shadow-sm h-100 border-0">
                <div class="card-body text-center">
                    <h5 class="card-title">Total Pendapatan Kotor</h5>
                    <h2 class="display-5 fw-bold">Rp <?= number_format($total_omzet, 0, ',', '.') ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm h-100 border-0" style="background-color: #E3E7D3;">
                <div class="card-body text-center text-sage-dark">
                    <h5 class="card-title">Barang Terjual (Pesanan Selesai)</h5>
                    <h2 class="display-5 fw-bold"><?= $total_pesanan ?> Transaksi</h2>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white fw-bold border-bottom">
            Rincian Transaksi Sukses
        </div>
        <div class="card-body">
            <table class="table table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ID Pesanan</th>
                        <th>Tanggal Selesai</th>
                        <th>Nama Pelanggan</th>
                        <th>Metode Bayar</th>
                        <th>Total Belanja</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($laporan_data) > 0): ?>
                        <?php foreach($laporan_data as $l): ?>
                        <tr>
                            <td><b>#<?= $l['id'] ?></b></td>
                            <td><?= date('d F Y, H:i', strtotime($l['tanggal_transaksi'])) ?></td>
                            <td><?= htmlspecialchars($l['nama_pembeli']) ?></td>
                            <td><?= $l['metode_pembayaran'] ?></td>
                            <td class="text-end fw-bold">Rp <?= number_format($l['total_harga'], 0, ',', '.') ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="table-light fw-bold">
                            <td colspan="4" class="text-end">GRAND TOTAL PENDAPATAN :</td>
                            <td class="text-end text-success">Rp <?= number_format($total_omzet, 0, ',', '.') ?></td>
                        </tr>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-4">Belum ada pesanan yang berstatus selesai.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>