<?php
require_once 'config/database.php';

// Menangkap kata kunci yang diketik user
$keyword = isset($_GET['keyword']) ? $_GET['keyword'] : '';

// Jika ada ketikan, cari berdasarkan nama produk
if ($keyword != '') {
    $stmt = $conn->prepare("SELECT * FROM produk WHERE nama_produk LIKE ? ORDER BY id DESC");
    $stmt->execute(["%$keyword%"]);
} else {
    // Jika kotak pencarian kosong, tampilkan 4 produk terbaru lagi
    $stmt = $conn->query("SELECT * FROM produk ORDER BY id DESC LIMIT 4");
}

$produk_list = $stmt->fetchAll();

// Cetak hasilnya dalam bentuk HTML Card
if (count($produk_list) > 0) {
    foreach ($produk_list as $p) {
        $stok_label = ($p['stok'] > 0) ? '<small class="text-muted mb-3">Tersisa: ' . $p['stok'] . ' Pcs</small>' : '<small class="text-danger fw-bold mb-3">Stok Habis</small>';
        $btn_disabled = ($p['stok'] <= 0) ? 'disabled' : '';
        
        echo '
        <div class="col-md-3 mb-4">
            <div class="card h-100 shadow-sm border-0 position-relative">
                <img src="frontend/images/produk/' . htmlspecialchars($p['gambar']) . '" class="card-img-top" style="height: 200px; object-fit: cover;">
                <div class="card-body text-center d-flex flex-column">
                    <h6 class="card-title fw-bold text-truncate">' . htmlspecialchars($p['nama_produk']) . '</h6>
                    <p class="card-text text-sage-dark fw-bold mb-1">Rp ' . number_format($p['harga'], 0, ',', '.') . '</p>
                    ' . $stok_label . '
                    <div class="d-flex justify-content-between mt-auto gap-2">
                        <form action="cart.php" method="POST" class="w-100">
                            <input type="hidden" name="id_produk" value="' . $p['id'] . '">
                            <input type="hidden" name="qty" value="1">
                            <button type="submit" name="add_to_cart" class="btn btn-sage btn-sm w-100" ' . $btn_disabled . '>
                                <i class="fas fa-cart-plus"></i> Tambah
                            </button>
                        </form>
                        <a href="index.php?add_wishlist=' . $p['id'] . '" class="btn btn-outline-danger btn-sm flex-shrink-0" title="Simpan ke Wishlist">
                            <i class="far fa-heart"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>';
    }
} else {
    echo '
    <div class="col-12 text-center text-muted py-5">
        <i class="fas fa-search fa-3x mb-3 text-sage-light"></i>
        <h5>Yahh, produk "' . htmlspecialchars($keyword) . '" tidak ditemukan.</h5>
        <p>Coba cari dengan kata kunci lain ya!</p>
    </div>';
}
?>