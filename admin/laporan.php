<?php
session_start();
require_once '../backend/config/database.php';

// CEK LOGIN DAN ROLE ADMIN
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php"); 
    exit;
}

// ==========================================
// LOGIKA FILTER TANGGAL CANGGIH
// ==========================================
$tgl_mulai = $_GET['tgl_mulai'] ?? date('Y-m-01');
$tgl_selesai = $_GET['tgl_selesai'] ?? date('Y-m-d');

// Jika tombol filter cepat diklik
if (isset($_GET['filter'])) {
    if ($_GET['filter'] == 'hari') {
        $tgl_mulai = date('Y-m-d');
        $tgl_selesai = date('Y-m-d');
    } elseif ($_GET['filter'] == 'minggu') {
        $tgl_mulai = date('Y-m-d', strtotime('monday this week'));
        $tgl_selesai = date('Y-m-d', strtotime('sunday this week'));
    } elseif ($_GET['filter'] == 'bulan') {
        $tgl_mulai = date('Y-m-01');
        $tgl_selesai = date('Y-m-t'); // 't' mengambil hari terakhir bulan ini
    }
}

// 1. Hitung Ringkasan Data
$stmt_summary = $conn->prepare("
    SELECT SUM(total_harga) as total_pendapatan, COUNT(id) as total_pesanan 
    FROM transaksi WHERE status_pesanan = 'selesai' AND DATE(tanggal_transaksi) BETWEEN ? AND ?
");
$stmt_summary->execute([$tgl_mulai, $tgl_selesai]);
$summary = $stmt_summary->fetch();

// 2. Ambil Daftar Transaksi
$stmt_trx = $conn->prepare("
    SELECT t.*, u.nama 
    FROM transaksi t JOIN users u ON t.id_user = u.id 
    WHERE t.status_pesanan = 'selesai' AND DATE(t.tanggal_transaksi) BETWEEN ? AND ?
    ORDER BY t.tanggal_transaksi DESC
");
$stmt_trx->execute([$tgl_mulai, $tgl_selesai]);
$laporan_list = $stmt_trx->fetchAll();

// 3. Ambil Kacamata Terlaris
$stmt_top = $conn->prepare("
    SELECT dt.nama_produk, SUM(dt.jumlah) as total_terjual 
    FROM detail_transaksi dt JOIN transaksi t ON dt.id_transaksi = t.id
    WHERE t.status_pesanan = 'selesai' AND DATE(t.tanggal_transaksi) BETWEEN ? AND ?
    GROUP BY dt.id_produk, dt.nama_produk
    ORDER BY total_terjual DESC LIMIT 5
");
$stmt_top->execute([$tgl_mulai, $tgl_selesai]);
$top_products = $stmt_top->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Penjualan - Admin Xriva Eyewear</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --sage: #4a7c6b; --sage-light: #7cb3a1; --bg-light: #f4f7f6; }
        body { background-color: var(--bg-light); font-family: 'Segoe UI', sans-serif; }
        .sidebar { min-height: 100vh; background-color: var(--sage); color: white; border-top-right-radius: 20px; border-bottom-right-radius: 20px; box-shadow: 4px 0 15px rgba(0,0,0,0.05); }
        .sidebar .nav-link { color: rgba(255,255,255,0.8); margin-bottom: 8px; border-radius: 10px; padding: 12px 20px; transition: 0.3s; font-weight: 500; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background-color: rgba(255,255,255,0.2); color: white; }
        .card-stats { border-radius: 16px; border: none; transition: 0.3s; box-shadow: 0 4px 12px rgba(0,0,0,0.03); }
        .table thead { background-color: #f8f9fa; }
        .table th { border: none; padding: 15px; color: #666; font-size: 0.85rem; text-transform: uppercase; }
        .table td { vertical-align: middle; padding: 15px; border-color: #f1f1f1; }
        .btn-sage { background-color: var(--sage); color: white; border: none; font-weight: bold; }
        .btn-sage:hover { background-color: #3a6355; color: white; }
        
        @media print {
            .sidebar, .filter-section, .btn-print { display: none !important; }
            .p-5 { padding: 0 !important; }
            .card { box-shadow: none !important; border: 1px solid #ddd !important; }
        }
    </style>
</head>
<body>

<div class="d-flex">
    <div class="sidebar p-4" style="width: 280px; flex-shrink: 0;">
        <h4 class="fw-bold mb-5 text-center"><i class="fas fa-glasses me-2"></i> AdminPanel</h4>
        <ul class="nav flex-column">
            <li class="nav-item"><a class="nav-link" href="produk.php"><i class="fas fa-box-open me-3"></i> Kelola Produk</a></li>
            <li class="nav-item"><a class="nav-link" href="transaksi.php"><i class="fas fa-shopping-cart me-3"></i> Pesanan Masuk</a></li>
            <li class="nav-item"><a class="nav-link active" href="laporan.php"><i class="fas fa-chart-line me-3"></i> Laporan Penjualan</a></li>
            <li class="nav-item mt-5"><a class="nav-link text-danger" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i> Keluar</a></li>
        </ul>
    </div>

    <div class="p-5 w-100">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold text-dark m-0">Laporan Penjualan</h2>
                <p class="text-muted">Pantau performa bisnis Xriva Eyewear Anda.</p>
            </div>
            <button onclick="window.print()" class="btn btn-outline-dark btn-print fw-bold rounded-pill px-4">
                <i class="fas fa-print me-2"></i> Cetak Laporan
            </button>
        </div>

        <div class="card card-stats p-4 mb-4 filter-section bg-white">
            <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                <h6 class="fw-bold m-0 text-muted"><i class="fas fa-filter me-2"></i>Filter Periode</h6>
                <div class="btn-group shadow-sm">
                    <a href="laporan.php?filter=hari" class="btn btn-sm btn-outline-secondary <?= (isset($_GET['filter']) && $_GET['filter']=='hari') ? 'active' : '' ?>">Hari Ini</a>
                    <a href="laporan.php?filter=minggu" class="btn btn-sm btn-outline-secondary <?= (isset($_GET['filter']) && $_GET['filter']=='minggu') ? 'active' : '' ?>">Minggu Ini</a>
                    <a href="laporan.php?filter=bulan" class="btn btn-sm btn-outline-secondary <?= (!isset($_GET['filter']) || $_GET['filter']=='bulan') ? 'active' : '' ?>">Bulan Ini</a>
                </div>
            </div>
            
            <form action="" method="GET" class="row align-items-end g-3">
                <div class="col-md-5">
                    <label class="form-label small fw-bold text-muted">Tanggal Mulai</label>
                    <input type="date" name="tgl_mulai" class="form-control" value="<?= $tgl_mulai ?>">
                </div>
                <div class="col-md-5">
                    <label class="form-label small fw-bold text-muted">Tanggal Selesai</label>
                    <input type="date" name="tgl_selesai" class="form-control" value="<?= $tgl_selesai ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-sage w-100 py-2 rounded-3">Cari</button>
                </div>
            </form>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="card card-stats p-4 bg-white border-start border-4 border-success">
                    <h6 class="text-muted small fw-bold text-uppercase mb-2">Total Pendapatan</h6>
                    <h3 class="fw-bold text-dark mb-0">Rp <?= number_format($summary['total_pendapatan'] ?? 0, 0, ',', '.') ?></h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-stats p-4 bg-white border-start border-4 border-primary">
                    <h6 class="text-muted small fw-bold text-uppercase mb-2">Pesanan Selesai</h6>
                    <h3 class="fw-bold text-dark mb-0"><?= $summary['total_pesanan'] ?> Transaksi</h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-stats p-4 bg-white border-start border-4 border-warning">
                    <h6 class="text-muted small fw-bold text-uppercase mb-2">Rata-rata Per Transaksi</h6>
                    <?php $rata = ($summary['total_pesanan'] > 0) ? ($summary['total_pendapatan'] / $summary['total_pesanan']) : 0; ?>
                    <h3 class="fw-bold text-dark mb-0">Rp <?= number_format($rata, 0, ',', '.') ?></h3>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card card-stats bg-white overflow-hidden">
                    <div class="card-header bg-white py-3 border-0">
                        <h6 class="m-0 fw-bold text-dark"><i class="fas fa-list me-2 text-sage"></i>Daftar Penjualan</h6>
                    </div>
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Pembeli</th>
                                    <th>Total Bayar</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($laporan_list as $l): ?>
                                <tr>
                                    <td><small class="text-muted"><?= date('d/m/Y H:i', strtotime($l['tanggal_transaksi'])) ?></small></td>
                                    <td class="fw-bold text-dark"><?= htmlspecialchars($l['nama']) ?></td>
                                    <td class="fw-bold text-sage">Rp <?= number_format($l['total_harga'], 0, ',', '.') ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if(count($laporan_list) == 0): ?>
                                    <tr><td colspan="3" class="text-center py-4 text-muted small">Tidak ada data penjualan pada periode ini.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card card-stats bg-white p-4">
                    <h6 class="fw-bold text-dark mb-4"><i class="fas fa-award me-2 text-warning"></i>Kacamata Terlaris</h6>
                    <?php foreach($top_products as $top): ?>
                    <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                        <span class="small text-dark fw-bold"><?= htmlspecialchars($top['nama_produk'] ?? 'Produk Lama (Tanpa Nama)') ?></span>
                        <span class="badge bg-light text-success border rounded-pill"><?= $top['total_terjual'] ?> terjual</span>
                    </div>
                    <?php endforeach; ?>
                    <?php if(count($top_products) == 0): ?>
                        <p class="text-center text-muted small py-3">Belum ada data produk terjual.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>