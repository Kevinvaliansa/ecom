<?php
require_once '../backend/config/database.php';

// PROSES UPDATE STATUS PESANAN
if (isset($_POST['update_status'])) {
    $id_transaksi = $_POST['id_transaksi'];
    $status_baru = $_POST['status_pesanan'];
    
    $update_stmt = $conn->prepare("UPDATE transaksi SET status_pesanan = ? WHERE id = ?");
    if ($update_stmt->execute([$status_baru, $id_transaksi])) {
        $pesan_sukses = "Status pesanan #$id_transaksi berhasil diperbarui menjadi '$status_baru'!";
    } else {
        $pesan_error = "Gagal memperbarui status pesanan.";
    }
}

// AMBIL SEMUA DATA TRANSAKSI BESERTA NAMA USER
$stmt = $conn->query("SELECT t.*, u.nama as nama_pembeli 
                      FROM transaksi t 
                      JOIN users u ON t.id_user = u.id 
                      ORDER BY t.tanggal_transaksi DESC");
$transaksi_data = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Kelola Transaksi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../frontend/css/style.css">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark navbar-sage mb-4">
    <div class="container-fluid">
        <span class="navbar-brand mb-0 h1 fw-bold">Dashboard Admin</span>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="adminNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="produk.php">Kelola Produk</a></li>
                <li class="nav-item"><a class="nav-link active fw-bold" href="transaksi.php">Kelola Transaksi</a></li>
                <li class="nav-item"><a class="nav-link" href="laporan.php">Laporan Penjualan</a></li>
            </ul>
            <a href="../index.php" class="btn btn-outline-light btn-sm" target="_blank">Lihat Website</a>
        </div>
    </div>
</nav>

<div class="container-fluid px-4">
    <h3 class="text-sage-dark fw-bold mb-3"><i class="fas fa-clipboard-list"></i> Kelola Transaksi Pesanan</h3>

    <?php if(isset($pesan_sukses)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= $pesan_sukses ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-sage-light text-sage-dark fw-bold">
            Daftar Pesanan Masuk
        </div>
        <div class="card-body table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ID / Tanggal</th>
                        <th>Nama Pembeli</th>
                        <th>Total Harga</th>
                        <th>Metode</th>
                        <th>Status</th>
                        <th>Bukti Bayar</th>
                        <th>Aksi / Update Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($transaksi_data) > 0): ?>
                        <?php foreach($transaksi_data as $t): ?>
                        <tr>
                            <td>
                                <b>#<?= $t['id'] ?></b><br>
                                <small class="text-muted"><?= date('d M Y, H:i', strtotime($t['tanggal_transaksi'])) ?></small>
                            </td>
                            <td><?= htmlspecialchars($t['nama_pembeli']) ?></td>
                            <td class="fw-bold">Rp <?= number_format($t['total_harga'], 0, ',', '.') ?></td>
                            <td>
                                <?php if($t['metode_pembayaran'] == 'COD'): ?>
                                    <span class="badge bg-secondary">COD</span>
                                <?php else: ?>
                                    <span class="badge bg-info text-dark">Transfer</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                    if($t['status_pesanan'] == 'diproses') echo '<span class="badge bg-warning text-dark">Diproses</span>';
                                    elseif($t['status_pesanan'] == 'dikirim') echo '<span class="badge bg-primary">Dikirim</span>';
                                    else echo '<span class="badge bg-success">Selesai</span>';
                                ?>
                            </td>
                            <td>
                                <?php if($t['metode_pembayaran'] == 'Transfer'): ?>
                                    <?php if(!empty($t['bukti_bayar'])): ?>
                                        <a href="../frontend/images/bukti/<?= htmlspecialchars($t['bukti_bayar']) ?>" target="_blank" class="btn btn-sm btn-outline-info">Lihat Bukti</a>
                                    <?php else: ?>
                                        <small class="text-danger">Belum Upload</small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <small class="text-muted">-</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="POST" action="" class="d-flex align-items-center">
                                    <input type="hidden" name="id_transaksi" value="<?= $t['id'] ?>">
                                    <select name="status_pesanan" class="form-select form-select-sm me-2" style="width: 130px;">
                                        <option value="diproses" <?= ($t['status_pesanan'] == 'diproses') ? 'selected' : '' ?>>Diproses</option>
                                        <option value="dikirim" <?= ($t['status_pesanan'] == 'dikirim') ? 'selected' : '' ?>>Dikirim</option>
                                        <option value="selesai" <?= ($t['status_pesanan'] == 'selesai') ? 'selected' : '' ?>>Selesai</option>
                                    </select>
                                    <button type="submit" name="update_status" class="btn btn-sm btn-sage"><i class="fas fa-save"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">Belum ada transaksi masuk.</td>
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