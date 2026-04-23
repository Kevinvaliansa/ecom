<?php
session_start();
require_once '../backend/config/database.php';

// CEK LOGIN DAN ROLE ADMIN
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php"); 
    exit;
}

// ==========================================
// LOGIKA UPDATE STATUS PESANAN
// ==========================================
if (isset($_POST['update_status'])) {
    $id_transaksi = $_POST['id_transaksi'];
    $status_baru = $_POST['status_pesanan'];
    
    $update = $conn->prepare("UPDATE transaksi SET status_pesanan = ? WHERE id = ?");
    $update->execute([$status_baru, $id_transaksi]);
    
    // NAMA STATUS UNTUK POP-UP
    $nama_status = strtoupper($status_baru);
    
    // ==============================================================
    // POP-UP SWEETALERT2 UNTUK UPDATE STATUS BERHASIL
    // ==============================================================
    echo "<!DOCTYPE html><html><head><meta name='viewport' content='width=device-width, initial-scale=1.0'><script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script></head><body style='background-color: #f4f7f6;'>";
    echo "<script>
        Swal.fire({
            title: 'Status Diperbarui!',
            text: 'Pesanan #$id_transaksi sekarang berstatus $nama_status.',
            icon: 'success',
            confirmButtonColor: '#4a7c6b',
            confirmButtonText: 'Oke'
        }).then((result) => {
            window.location.href = 'transaksi.php';
        });
    </script></body></html>";
    exit;
}

// AMBIL SEMUA DATA TRANSAKSI BESERTA DATA USER PEMBELI
$stmt = $conn->query("
    SELECT t.*, u.nama, u.email, u.no_hp, u.alamat 
    FROM transaksi t 
    JOIN users u ON t.id_user = u.id 
    ORDER BY t.tanggal_transaksi DESC
");
$transaksi = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Pesanan - Admin Xriva Eyewear</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
        
        .btn-sage { background-color: var(--sage-light); color: white; border: none; border-radius: 8px; font-weight: bold; }
        .btn-sage:hover { background-color: var(--sage); color: white; }
        
        .badge-status { padding: 8px 12px; border-radius: 8px; font-weight: bold; font-size: 0.8rem; }
    </style>
</head>
<body>

<div class="d-flex">
    <div class="sidebar p-4" style="width: 280px; flex-shrink: 0;">
        <h4 class="fw-bold mb-5 text-center"><i class="fas fa-glasses me-2"></i> AdminPanel</h4>
        <ul class="nav flex-column">
            <li class="nav-item"><a class="nav-link" href="produk.php"><i class="fas fa-box-open me-3"></i> Kelola Produk</a></li>
            <li class="nav-item"><a class="nav-link active" href="transaksi.php"><i class="fas fa-shopping-cart me-3"></i> Pesanan Masuk</a></li>
            <li class="nav-item"><a class="nav-link" href="laporan.php"><i class="fas fa-chart-line me-3"></i> Laporan Penjualan</a></li>
            <li class="nav-item mt-5"><a class="nav-link text-danger" href="../logout.php"><i class="fas fa-sign-out-alt me-3"></i> Keluar</a></li>
        </ul>
    </div>

    <div class="p-5 w-100">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold text-dark m-0">Pesanan Masuk</h2>
                <p class="text-muted">Kelola dan update status pengiriman pesanan pelanggan Anda.</p>
            </div>
        </div>

        <div class="card card-custom p-0 overflow-hidden">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>ID & Waktu</th>
                            <th>Info Pembeli</th>
                            <th>Total Tagihan</th>
                            <th>Metode Pembayaran</th>
                            <th>Status</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($transaksi as $t): ?>
                        <tr>
                            <td>
                                <span class="fw-bold text-dark fs-6">#<?= $t['id'] ?></span><br>
                                <small class="text-muted"><?= date('d M Y, H:i', strtotime($t['tanggal_transaksi'])) ?></small>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?= htmlspecialchars($t['nama']) ?></div>
                                <small class="text-muted"><i class="fas fa-phone-alt me-1"></i> <?= htmlspecialchars($t['no_hp']) ?></small>
                            </td>
                            <td class="fw-bold" style="color: #4a7c6b;">
                                Rp <?= number_format($t['total_harga'], 0, ',', '.') ?>
                            </td>
                            <td>
                                <span class="badge bg-light text-secondary border px-3 py-2"><?= htmlspecialchars($t['metode_pembayaran']) ?></span>
                            </td>
                            <td>
                                <?php 
                                    $s = $t['status_pesanan'];
                                    if($s == 'pending') echo '<span class="badge-status bg-warning text-dark"><i class="fas fa-clock me-1"></i> Pending</span>';
                                    elseif($s == 'diproses') echo '<span class="badge-status bg-info text-dark"><i class="fas fa-box me-1"></i> Diproses</span>';
                                    elseif($s == 'dikirim') echo '<span class="badge-status bg-primary text-white"><i class="fas fa-truck me-1"></i> Dikirim</span>';
                                    elseif($s == 'selesai') echo '<span class="badge-status bg-success text-white"><i class="fas fa-check-circle me-1"></i> Selesai</span>';
                                    else echo '<span class="badge-status bg-danger text-white"><i class="fas fa-times-circle me-1"></i> Batal</span>';
                                ?>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-dark fw-bold rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modalDetail<?= $t['id'] ?>">
                                    Cek Detail
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>

                        <?php if(count($transaksi) == 0): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <i class="fas fa-inbox fa-3x text-muted mb-3 opacity-25"></i>
                                <h5 class="text-muted">Belum ada pesanan masuk.</h5>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php foreach($transaksi as $t): 
    $s = $t['status_pesanan'];
?>
<div class="modal fade" id="modalDetail<?= $t['id'] ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow-lg" style="border-radius: 16px; border: none;">
            <div class="modal-header text-white" style="background-color: #4a7c6b; border-radius: 16px 16px 0 0; padding: 20px;">
                <h5 class="modal-title fw-bold m-0"><i class="fas fa-receipt me-2"></i> Detail Pesanan #<?= $t['id'] ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            
            <div class="modal-body p-4 p-md-5">
                <div class="row g-4 mb-5">
                    <div class="col-md-6 border-end-md">
                        <div class="bg-light p-4 rounded-3 h-100 border">
                            <h6 class="fw-bold text-muted mb-3 small text-uppercase"><i class="fas fa-map-marker-alt me-2"></i>Tujuan Pengiriman</h6>
                            <p class="mb-1 fw-bold fs-5 text-dark"><?= htmlspecialchars($t['nama']) ?></p>
                            <p class="mb-2 text-primary fw-bold"><i class="fas fa-phone me-2"></i><?= htmlspecialchars($t['no_hp']) ?></p>
                            <p class="mb-0 text-muted small lh-lg"><?= nl2br(htmlspecialchars($t['alamat'])) ?></p>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="p-4 h-100">
                            <h6 class="fw-bold text-muted mb-3 small text-uppercase"><i class="fas fa-sliders-h me-2"></i>Update Status</h6>
                            
                            <form action="" method="POST" class="d-flex flex-column gap-3">
                                <input type="hidden" name="id_transaksi" value="<?= $t['id'] ?>">
                                <select name="status_pesanan" class="form-select form-select-lg fw-bold text-dark bg-light" <?= ($s == 'batal' || $s == 'selesai') ? 'disabled' : '' ?>>
                                    <option value="pending" <?= $s == 'pending' ? 'selected' : '' ?>>⏳ Menunggu Pembayaran</option>
                                    <option value="diproses" <?= $s == 'diproses' ? 'selected' : '' ?>>📦 Sedang Diproses</option>
                                    <option value="dikirim" <?= $s == 'dikirim' ? 'selected' : '' ?>>🚚 Sedang Dikirim</option>
                                    <option value="selesai" <?= $s == 'selesai' ? 'selected' : '' ?>>✅ Selesai</option>
                                    <option value="batal" <?= $s == 'batal' ? 'selected' : '' ?>>❌ Dibatalkan</option>
                                </select>
                                <button type="submit" name="update_status" class="btn btn-sage w-100 py-2" <?= ($s == 'batal' || $s == 'selesai') ? 'disabled' : '' ?>>Simpan Perubahan</button>
                            </form>

                            <?php if($s == 'selesai' || $s == 'batal'): ?>
                                <div class="alert alert-secondary mt-3 mb-0 small text-center p-2 border-0">
                                    Pesanan ini sudah dikunci permanen.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <h6 class="fw-bold text-dark mb-3"><i class="fas fa-box-open me-2 text-sage"></i>Rincian Barang yang Dibeli</h6>
                <div class="card border-0 bg-light rounded-3 p-3">
                    <?php
                    $stmt_dtl = $conn->prepare("SELECT * FROM detail_transaksi WHERE id_transaksi = ?");
                    $stmt_dtl->execute([$t['id']]);
                    $items = $stmt_dtl->fetchAll();
                    
                    if(count($items) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-borderless mb-0">
                                <tbody>
                                    <?php foreach($items as $item): ?>
                                    <tr class="border-bottom">
                                        <td style="width: 70px; padding: 10px 0;">
                                            <img src="../frontend/images/produk/<?= htmlspecialchars($item['gambar']) ?>" class="rounded border bg-white" style="width: 55px; height: 55px; object-fit: cover;">
                                        </td>
                                        <td class="align-middle">
                                            <h6 class="mb-1 fw-bold text-dark"><?= htmlspecialchars($item['nama_produk'] ?? 'Produk') ?></h6>
                                            <small class="text-muted"><?= $item['jumlah'] ?> pcs &times; Rp <?= number_format($item['harga'], 0, ',', '.') ?></small>
                                        </td>
                                        <td class="text-end fw-bold align-middle text-dark">
                                            Rp <?= number_format($item['harga'] * $item['jumlah'], 0, ',', '.') ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-exclamation-circle fa-2x mb-2 opacity-50"></i><br>
                            <span class="small">Rincian barang tidak tersedia (Pesanan Lama / Checkout Error)</span>
                        </div>
                    <?php endif; ?>
                    
                    <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top border-2">
                        <span class="fw-bold text-muted text-uppercase small">Total Tagihan Pembeli:</span>
                        <span class="fw-bold fs-4" style="color: #4a7c6b;">Rp <?= number_format($t['total_harga'], 0, ',', '.') ?></span>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>