<?php
session_start();
require_once 'backend/config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$id_user = $_SESSION['user_id'];

// ==============================================================
// TRIK SAKTI SUPER: Otomatis tambahkan SEMUA kolom yang kurang!
// ==============================================================
$conn->exec("CREATE TABLE IF NOT EXISTS detail_transaksi (
    id INT PRIMARY KEY AUTO_INCREMENT,
    id_transaksi INT,
    id_produk INT
)");
try { $conn->exec("ALTER TABLE detail_transaksi ADD COLUMN nama_produk VARCHAR(255)"); } catch(Exception $e) {}
try { $conn->exec("ALTER TABLE detail_transaksi ADD COLUMN harga INT"); } catch(Exception $e) {}
try { $conn->exec("ALTER TABLE detail_transaksi ADD COLUMN jumlah INT"); } catch(Exception $e) {}
try { $conn->exec("ALTER TABLE detail_transaksi ADD COLUMN gambar VARCHAR(255)"); } catch(Exception $e) {}
// ==============================================================

// Ambil data user
$stmt_user = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt_user->execute([$id_user]);
$user = $stmt_user->fetch();

$cart_items = [];
$total_bayar = 0;
$ids_selected = [];

// LOGIKA PEMBAYARAN INTERNAL
if (isset($_POST['buat_pesanan'])) {
    $nama_penerima = $_POST['nama_penerima'];
    $no_hp = $_POST['no_hp'];
    $alamat = $_POST['alamat_pengiriman'];
    $metode = $_POST['metode_pembayaran']; 
    $total = $_POST['total_bayar'];
    
    $status = ($metode == 'COD') ? 'diproses' : 'pending';
    
    try {
        if (empty($_POST['id_carts'])) {
            throw new Exception("Keranjang kosong atau data tidak valid.");
        }

        $conn->beginTransaction();

        // 1. Simpan Transaksi Utama
        $insert_trx = $conn->prepare("INSERT INTO transaksi (id_user, total_harga, metode_pembayaran, status_pesanan, tanggal_transaksi) VALUES (?, ?, ?, ?, NOW())");
        $insert_trx->execute([$id_user, $total, $metode, $status]);
        $id_transaksi = $conn->lastInsertId();
        
        $carts = explode(',', $_POST['id_carts']);
        $in  = str_repeat('?,', count($carts) - 1) . '?';
        
        // Ambil barang yang dibeli
        $stmt_items = $conn->prepare("SELECT c.qty, p.id as id_produk, p.nama_produk, p.harga, p.gambar FROM cart c JOIN produk p ON c.id_produk = p.id WHERE c.id IN ($in)");
        $stmt_items->execute($carts);
        $items_to_process = $stmt_items->fetchAll();

        foreach($items_to_process as $it) {
            // 2. Simpan ke detail_transaksi
            $insert_detail = $conn->prepare("INSERT INTO detail_transaksi (id_transaksi, id_produk, nama_produk, harga, jumlah, gambar) VALUES (?, ?, ?, ?, ?, ?)");
            $insert_detail->execute([$id_transaksi, $it['id_produk'], $it['nama_produk'], $it['harga'], $it['qty'], $it['gambar']]);

            // 3. Kurangi stok produk
            $update_stok = $conn->prepare("UPDATE produk SET stok = stok - ? WHERE id = ?");
            $update_stok->execute([$it['qty'], $it['id_produk']]);
        }

        // 4. Hapus dari keranjang
        $hapus_cart = $conn->prepare("DELETE FROM cart WHERE id IN ($in)");
        $hapus_cart->execute($carts);

        $conn->commit();
        
        // ==============================================================
        // POP-UP SWEETALERT2 UNTUK CHECKOUT BERHASIL
        // ==============================================================
        echo "<!DOCTYPE html><html><head><meta name='viewport' content='width=device-width, initial-scale=1.0'><script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script></head><body style='background-color: #f4f7f6;'>";
        echo "<script>
            Swal.fire({
                title: 'Yeay! Pesanan Berhasil 🎉',
                text: 'Pesanan kacamata kamu sudah kami terima dan akan segera diproses.',
                icon: 'success',
                confirmButtonColor: '#4a7c6b',
                confirmButtonText: 'Lihat Pesanan Saya'
            }).then((result) => {
                window.location.href = 'history.php';
            });
        </script></body></html>";
        exit;

    } catch (Exception $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        $error_msg = addslashes($e->getMessage());
        
        // ==============================================================
        // POP-UP SWEETALERT2 UNTUK CHECKOUT GAGAL
        // ==============================================================
        echo "<!DOCTYPE html><html><head><meta name='viewport' content='width=device-width, initial-scale=1.0'><script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script></head><body style='background-color: #f4f7f6;'>";
        echo "<script>
            Swal.fire({
                title: 'Oops, Gagal Checkout!',
                text: '$error_msg',
                icon: 'error',
                confirmButtonColor: '#d33',
                confirmButtonText: 'Kembali'
            }).then((result) => {
                window.location.href = 'cart.php';
            });
        </script></body></html>";
        exit;
    }
} else {
    // Tampilkan halaman checkout
    if (isset($_POST['selected_items'])) {
        $in  = str_repeat('?,', count($_POST['selected_items']) - 1) . '?';
        $sql = "SELECT c.id as id_cart, c.qty, p.id as id_produk, p.nama_produk, p.harga, p.gambar 
                FROM cart c JOIN produk p ON c.id_produk = p.id 
                WHERE c.id IN ($in) AND c.id_user = ?";
        $params = $_POST['selected_items'];
        $params[] = $id_user;
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $cart_items = $stmt->fetchAll();
        
        foreach ($cart_items as $item) {
            $total_bayar += ($item['harga'] * $item['qty']);
            $ids_selected[] = $item['id_cart'];
        }
    } else {
        header("Location: cart.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Proses Pembayaran - Xriva Eyewear</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background-color: #f4f7f6; font-family: 'Segoe UI', sans-serif; }
        .card-custom { border-radius: 16px; border: none; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark sticky-top shadow-sm py-2" style="background-color: #4a7c6b;">
    <div class="container">
        <a class="navbar-brand fw-bold fs-4" href="index.php"><i class="fas fa-glasses me-2"></i> Xriva Eyewear</a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav ms-auto align-items-center gap-3">
                <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="cart.php">Keranjang</a></li>
                <li class="nav-item"><a class="nav-link" href="history.php">Pesanan</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container my-5 pb-5">
    <a href="cart.php" class="text-decoration-none text-muted mb-4 d-inline-block"><i class="fas fa-arrow-left me-2"></i>Kembali ke Keranjang</a>
    <h3 class="mb-4 text-dark fw-bold"><i class="fas fa-clipboard-check" style="color: #7cb3a1;"></i> Proses Pembayaran</h3>

    <form method="POST">
        <div class="row g-4">
            
            <div class="col-lg-7">
                <div class="card card-custom p-4 mb-4">
                    <h5 class="fw-bold mb-4 border-bottom pb-2">1. Informasi Pengiriman</h5>
                    <div class="mb-3">
                        <label class="form-label small text-muted fw-bold">Nama Penerima</label>
                        <input type="text" name="nama_penerima" class="form-control" value="<?= htmlspecialchars($user['nama']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-muted fw-bold">No. HP</label>
                        <input type="text" name="no_hp" class="form-control" value="<?= htmlspecialchars($user['no_hp'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-muted fw-bold">Alamat Lengkap</label>
                        <textarea name="alamat_pengiriman" class="form-control" rows="3" required><?= htmlspecialchars($user['alamat'] ?? '') ?></textarea>
                    </div>
                </div>

                <div class="card card-custom p-4">
                    <h5 class="fw-bold mb-4 border-bottom pb-2">2. Daftar Barang</h5>
                    <?php foreach($cart_items as $c): ?>
                    <div class="d-flex align-items-center mb-3 border-bottom pb-2">
                        <img src="frontend/images/produk/<?= htmlspecialchars($c['gambar']) ?>" width="60" class="rounded border me-3" style="object-fit: cover;">
                        <div class="flex-grow-1">
                            <h6 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($c['nama_produk']) ?></h6>
                            <small class="text-muted"><?= $c['qty'] ?> x Rp <?= number_format($c['harga'], 0, ',', '.') ?></small>
                        </div>
                        <div class="fw-bold text-dark">
                            Rp <?= number_format($c['harga'] * $c['qty'], 0, ',', '.') ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card card-custom p-4 bg-white sticky-top" style="top: 20px;">
                    <h5 class="fw-bold mb-4 border-bottom pb-2">3. Pembayaran</h5>
                    
                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted">Total Tagihan</span>
                        <span class="fw-bold fs-4" style="color: #4a7c6b;">Rp <?= number_format($total_bayar, 0, ',', '.') ?></span>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold small text-muted">Pilih Metode Pembayaran</label>
                        <select name="metode_pembayaran" class="form-select" style="border-radius: 12px; height: 50px;" required>
                            <option value="Transfer Bank (BCA)">Transfer Bank (BCA) - 123456789</option>
                            <option value="Transfer Bank (Mandiri)">Transfer Bank (Mandiri) - 098765432</option>
                            <option value="E-Wallet (Dana/OVO)">E-Wallet (Dana/OVO)</option>
                            <option value="COD">Bayar di Tempat (COD)</option>
                        </select>
                    </div>

                    <div class="alert bg-light border-0 rounded-3 small text-muted">
                        <i class="fas fa-info-circle me-1"></i> Pesanan akan langsung diteruskan ke penjual setelah kamu klik Buat Pesanan.
                    </div>

                    <input type="hidden" name="total_bayar" value="<?= $total_bayar ?>">
                    <input type="hidden" name="id_carts" value="<?= implode(',', $ids_selected) ?>">
                    
                    <button type="submit" name="buat_pesanan" class="btn w-100 fw-bold py-3 text-white" style="background-color: #4a7c6b; border-radius: 12px; font-size: 1.1rem;">
                        Buat Pesanan Sekarang <i class="fas fa-check-circle ms-1"></i>
                    </button>
                </div>
            </div>

        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>