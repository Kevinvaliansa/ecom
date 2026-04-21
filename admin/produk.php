<?php
session_start();
require_once '../backend/config/database.php';

// CEK LOGIN DAN ROLE ADMIN
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    echo "<script>alert('Akses Ditolak! Anda bukan Admin.'); window.location.href='../login.php';</script>";
    exit;
}

// Logika Tambah Produk
if (isset($_POST['tambah_produk'])) {
    $nama = $_POST['nama_produk'];
    $harga = $_POST['harga'];
    $stok = $_POST['stok'];
    $deskripsi = $_POST['deskripsi'];
    
    $insert = $conn->prepare("INSERT INTO produk (nama_produk, harga, stok, deskripsi, gambar) VALUES (?, ?, ?, ?, 'default.png')");
    $insert->execute([$nama, $harga, $stok, $deskripsi]);
    header("Location: produk.php");
    exit;
}

// Logika Hapus Produk
if (isset($_GET['hapus'])) {
    $id_hapus = $_GET['hapus'];
    $conn->prepare("DELETE FROM produk WHERE id = ?")->execute([$id_hapus]);
    header("Location: produk.php");
    exit;
}

// Ambil Data
$stmt = $conn->query("SELECT * FROM produk ORDER BY id DESC");
$produk_list = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Produk - Admin XrivaStore</title>
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
            <li class="nav-item"><a class="nav-link active" href="produk.php"><i class="fas fa-box me-3"></i> Kelola Produk</a></li>
            <li class="nav-item"><a class="nav-link" href="transaksi.php"><i class="fas fa-shopping-cart me-3"></i> Pesanan Masuk</a></li>
            <li class="nav-item"><a class="nav-link" href="laporan.php"><i class="fas fa-chart-line me-3"></i> Laporan Penjualan</a></li>
            <li class="nav-item mt-5"><a class="nav-link text-danger" href="../logout.php"><i class="fas fa-sign-out-alt me-3"></i> Keluar</a></li>
            <li class="nav-item"><a class="nav-link text-info" href="../index.php" target="_blank"><i class="fas fa-external-link-alt me-3"></i> Lihat Website</a></li>
        </ul>
    </div>

    <div class="content-area flex-grow-1">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold text-dark m-0">Katalog Produk</h2>
            
            <div class="d-flex align-items-center gap-3">
                <span class="text-muted"><i class="fas fa-user-circle"></i> Halo, <?= htmlspecialchars($_SESSION['user_nama']) ?></span>
                <a href="tambah_produk.php" class="btn btn-sage rounded-pill px-4 shadow-sm d-flex align-items-center">
                    <i class="fas fa-plus me-1"></i> Tambah Produk Baru
                </a>
            </div>
        </div>

        <div class="card bg-white p-4">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th width="5%">No</th>
                            <th width="15%">Gambar</th>
                            <th width="25%">Nama Produk</th>
                            <th width="15%">Harga</th>
                            <th width="10%">Stok</th>
                            <th width="25%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; foreach($produk_list as $p): ?>
                        <tr>
                            <td class="fw-bold text-muted"><?= $no++ ?></td>
                            <td><img src="../frontend/images/produk/<?= htmlspecialchars($p['gambar']) ?>" width="60" height="60" style="object-fit:cover; border-radius:10px; border: 1px solid #ddd;"></td>
                            <td class="fw-bold"><?= htmlspecialchars($p['nama_produk']) ?></td>
                            <td class="text-success fw-bold">Rp <?= number_format($p['harga'], 0, ',', '.') ?></td>
                            <td>
                                <?php if($p['stok'] > 5): ?>
                                    <span class="badge bg-success bg-opacity-25 text-success px-2 py-1 rounded-pill"><?= $p['stok'] ?> Pcs</span>
                                <?php elseif($p['stok'] >= 0): ?>
                                    <span class="badge bg-warning bg-opacity-25 text-warning px-2 py-1 rounded-pill"><?= $p['stok'] ?> Pcs</span>
                                <?php else: ?>
                                    <span class="badge bg-danger bg-opacity-25 text-danger px-2 py-1 rounded-pill"><?= $p['stok'] ?> Pcs</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="edit_produk.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-light text-primary border rounded-pill px-3 shadow-sm me-1"><i class="fas fa-edit"></i> Edit</a>
                                <button type="button" class="btn btn-sm btn-light text-danger border rounded-pill px-3 shadow-sm" onclick="confirmDelete(<?= $p['id'] ?>)"><i class="fas fa-trash"></i> Hapus</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if(count($produk_list) == 0): ?>
                        <tr><td colspan="6" class="text-center py-4 text-muted">Belum ada produk.</td></tr>
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
function confirmDelete(id) {
    Swal.fire({
        title: 'Hapus Produk?',
        text: "Data yang dihapus tidak bisa dikembalikan!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#7cb3a1',
        confirmButtonText: '<i class="fas fa-trash"></i> Ya, Hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = "produk.php?hapus=" + id;
        }
    })
}
</script>

</body>
</html>