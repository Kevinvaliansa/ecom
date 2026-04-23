<?php
session_start();
require_once '../backend/config/database.php';

// CEK LOGIN DAN ROLE ADMIN
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php"); 
    exit;
}

// ==========================================
// LOGIKA HAPUS PRODUK
// ==========================================
if (isset($_GET['hapus'])) {
    $id_hapus = $_GET['hapus'];
    
    // Ambil nama gambar dulu untuk dihapus dari folder
    $stmt_img = $conn->prepare("SELECT gambar FROM produk WHERE id = ?");
    $stmt_img->execute([$id_hapus]);
    $img = $stmt_img->fetch();
    
    if ($img && $img['gambar'] != 'default.png') {
        $file_path = '../frontend/images/produk/' . $img['gambar'];
        if(file_exists($file_path)) {
            unlink($file_path);
        }
    }

    $delete = $conn->prepare("DELETE FROM produk WHERE id = ?");
    $delete->execute([$id_hapus]);
    
    // Redirect dengan parameter sukses untuk memicu SweetAlert
    header("Location: produk.php?pesan=hapus_sukses");
    exit;
}

// Ambil semua data produk
$stmt = $conn->query("SELECT * FROM produk ORDER BY id DESC");
$produk = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Produk - Admin Xriva Eyewear</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root { --sage: #4a7c6b; --sage-light: #7cb3a1; --bg-light: #f4f7f6; }
        body { background-color: var(--bg-light); font-family: 'Segoe UI', sans-serif; }
        
        .sidebar { min-height: 100vh; background-color: var(--sage); color: white; border-top-right-radius: 20px; border-bottom-right-radius: 20px; box-shadow: 4px 0 15px rgba(0,0,0,0.05); }
        .sidebar .nav-link { color: rgba(255,255,255,0.8); margin-bottom: 8px; border-radius: 10px; padding: 12px 20px; transition: 0.3s; font-weight: 500; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background-color: rgba(255,255,255,0.2); color: white; }
        
        .card-custom { border-radius: 16px; border: none; box-shadow: 0 5px 20px rgba(0,0,0,0.05); background: white; }
        .table thead { background-color: #f8f9fa; }
        .table th { border: none; padding: 15px; color: #666; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px; }
        .table td { vertical-align: middle; padding: 15px; border-color: #f1f1f1; }
        
        .btn-sage { background-color: var(--sage-light); color: white; border: none; border-radius: 10px; font-weight: bold; }
        .btn-sage:hover { background-color: var(--sage); color: white; }
        
        .badge-kat { background-color: #e8f0ed; color: var(--sage); font-weight: 600; padding: 6px 12px; border-radius: 8px; font-size: 0.75rem; }
        .stok-habis { color: #dc3545; font-weight: bold; }
    </style>
</head>
<body>

<div class="d-flex">
    <div class="sidebar p-4" style="width: 280px; flex-shrink: 0;">
        <h4 class="fw-bold mb-5 text-center"><i class="fas fa-glasses me-2"></i> AdminPanel</h4>
        <ul class="nav flex-column">
            <li class="nav-item"><a class="nav-link active" href="produk.php"><i class="fas fa-box-open me-3"></i> Kelola Produk</a></li>
            <li class="nav-item"><a class="nav-link" href="transaksi.php"><i class="fas fa-shopping-cart me-3"></i> Pesanan Masuk</a></li>
            <li class="nav-item"><a class="nav-link" href="laporan.php"><i class="fas fa-chart-line me-3"></i> Laporan Penjualan</a></li>
            <li class="nav-item mt-5"><a class="nav-link text-danger" href="../logout.php"><i class="fas fa-sign-out-alt me-3"></i> Keluar</a></li>
        </ul>
    </div>

    <div class="p-5 w-100">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold text-dark m-0">Daftar Produk</h2>
                <p class="text-muted">Total kacamata di katalog: <b><?= count($produk) ?> item</b></p>
            </div>
            <a href="tambah_produk.php" class="btn btn-sage px-4 py-2 shadow-sm">
                <i class="fas fa-plus me-2"></i> Tambah Produk Baru
            </a>
        </div>

        <div class="card card-custom p-0 overflow-hidden">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Gambar</th>
                            <th>Detail Produk</th>
                            <th>Kategori</th>
                            <th>Harga</th>
                            <th>Stok</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($produk as $p): ?>
                        <tr>
                            <td style="width: 100px;">
                                <img src="../frontend/images/produk/<?= htmlspecialchars($p['gambar']) ?>" 
                                     class="rounded shadow-sm" 
                                     style="width: 70px; height: 70px; object-fit: cover; <?= ($p['stok'] <= 0) ? 'filter: grayscale(100%); opacity: 0.6;' : '' ?>">
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?= htmlspecialchars($p['nama_produk']) ?></div>
                                <small class="text-muted text-truncate d-inline-block" style="max-width: 200px;"><?= htmlspecialchars(substr($p['deskripsi'], 0, 50)) ?>...</small>
                            </td>
                            <td>
                                <span class="badge-kat"><?= htmlspecialchars($p['kategori'] ?? 'Kacamata') ?></span>
                            </td>
                            <td class="fw-bold text-dark">
                                Rp <?= number_format($p['harga'], 0, ',', '.') ?>
                            </td>
                            <td>
                                <span class="<?= ($p['stok'] <= 0) ? 'stok-habis' : 'text-dark' ?>">
                                    <?= $p['stok'] ?> Pcs
                                </span>
                                <?php if($p['stok'] <= 0): ?>
                                    <br><small class="text-danger">Stok Habis</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="d-flex justify-content-center gap-2">
                                    <a href="edit_produk.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button class="btn btn-sm btn-outline-danger" onclick="confirmDelete(<?= $p['id'] ?>)" title="Hapus">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if(count($produk) == 0): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <i class="fas fa-box-open fa-3x text-muted mb-3 opacity-25"></i>
                                <h5 class="text-muted">Katalog masih kosong.</h5>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Fungsi konfirmasi hapus
    function confirmDelete(id) {
        Swal.fire({
            title: 'Hapus Kacamata?',
            text: "Produk ini akan dihapus permanen dari katalog!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = "produk.php?hapus=" + id;
            }
        })
    }
</script>

<?php if(isset($_GET['pesan']) && $_GET['pesan'] == 'hapus_sukses'): ?>
<script>
    // Munculkan notifikasi sukses jika parameter pesan=hapus_sukses ada di URL
    Swal.fire({
        title: 'Berhasil Dihapus!',
        text: 'Produk telah dihapus dari katalog.',
        icon: 'success',
        confirmButtonColor: '#4a7c6b'
    }).then(() => {
        // Bersihkan URL dari parameter pesan
        window.history.replaceState(null, null, window.location.pathname);
    });
</script>
<?php endif; ?>

</body>
</html>