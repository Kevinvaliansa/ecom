<?php
session_start();
require_once '../backend/config/database.php';

// CEK LOGIN DAN ROLE ADMIN
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php"); 
    exit;
}

// ==========================================
// LOGIKA TAMBAH PRODUK
// ==========================================
if (isset($_POST['tambah_produk'])) {
    $nama = $_POST['nama_produk'];
    $kategori = $_POST['kategori'];
    $harga = $_POST['harga'];
    $stok = $_POST['stok'];
    $deskripsi = $_POST['deskripsi'];
    
    // Default gambar jika tidak upload
    $gambar_baru = 'default.png';
    
    // Proses Upload Gambar
    if (!empty($_FILES['gambar']['name'])) {
        $gambar_baru = time() . '_' . $_FILES['gambar']['name']; 
        move_uploaded_file($_FILES['gambar']['tmp_name'], '../frontend/images/produk/' . $gambar_baru);
    }
    
    // Simpan ke database
    $insert = $conn->prepare("INSERT INTO produk (nama_produk, kategori, harga, stok, deskripsi, gambar) VALUES (?, ?, ?, ?, ?, ?)");
    $insert->execute([$nama, $kategori, $harga, $stok, $deskripsi, $gambar_baru]);
    
    // ==============================================================
    // POP-UP SWEETALERT2 UNTUK TAMBAH PRODUK BERHASIL
    // ==============================================================
    echo "<!DOCTYPE html><html><head><meta name='viewport' content='width=device-width, initial-scale=1.0'><script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script></head><body style='background-color: #f4f7f6;'>";
    echo "<script>
        Swal.fire({
            title: 'Berhasil!',
            text: 'Produk kacamata baru berhasil ditambahkan ke katalog.',
            icon: 'success',
            confirmButtonColor: '#4a7c6b',
            confirmButtonText: 'Oke'
        }).then((result) => {
            window.location.href = 'produk.php';
        });
    </script></body></html>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Produk - Admin Xriva Eyewear</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --sage: #4a7c6b; --sage-light: #7cb3a1; --bg-light: #f4f7f6; }
        body { background-color: var(--bg-light); font-family: 'Segoe UI', sans-serif; }
        
        /* Sidebar Styling */
        .sidebar { min-height: 100vh; background-color: var(--sage); color: white; border-top-right-radius: 20px; border-bottom-right-radius: 20px; box-shadow: 4px 0 15px rgba(0,0,0,0.05); }
        .sidebar .nav-link { color: rgba(255,255,255,0.8); margin-bottom: 8px; border-radius: 10px; padding: 12px 20px; transition: 0.3s; font-weight: 500; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background-color: rgba(255,255,255,0.2); color: white; }
        
        /* Card & Button */
        .card-custom { border-radius: 16px; border: none; box-shadow: 0 5px 20px rgba(0,0,0,0.05); background: white; }
        .btn-sage { background-color: var(--sage-light); color: white; border: none; border-radius: 10px; font-weight: bold; transition: 0.3s; padding: 12px; }
        .btn-sage:hover { background-color: var(--sage); color: white; }
        
        /* Form Styling */
        .form-control, .form-select { border-radius: 10px; border: 1px solid #ddd; padding: 10px 15px; }
        .form-control:focus, .form-select:focus { border-color: var(--sage-light); box-shadow: 0 0 0 0.2rem rgba(124, 179, 161, 0.15); }
        .form-label { font-weight: 600; color: #444; font-size: 0.9rem; }
        
        .upload-box { border: 2px dashed #ddd; border-radius: 15px; padding: 30px; text-align: center; cursor: pointer; transition: 0.3s; background: #fafafa; }
        .upload-box:hover { border-color: var(--sage-light); background: #f0f7f4; }
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
        <a href="produk.php" class="text-muted text-decoration-none mb-3 d-inline-block"><i class="fas fa-arrow-left me-2"></i>Batal dan Kembali</a>
        <h2 class="fw-bold text-dark mb-4">Tambah Produk Baru</h2>

        <div class="card card-custom p-4 p-lg-5">
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="row g-5">
                    
                    <div class="col-lg-7">
                        <div class="mb-4">
                            <label class="form-label">Nama Kacamata / Produk</label>
                            <input type="text" name="nama_produk" class="form-control" placeholder="Cth: Kacamata Minus Aviator Gold" required>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Kategori</label>
                                <select name="kategori" class="form-select" required>
                                    <option value="" disabled selected>Pilih Kategori...</option>
                                    <option value="Kacamata Gaya">Kacamata Gaya</option>
                                    <option value="Kacamata Minus">Kacamata Minus</option>
                                    <option value="Aksesoris">Aksesoris & Kotak</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Harga Jual (Rp)</label>
                                <input type="number" name="harga" class="form-control" placeholder="Cth: 150000" required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Jumlah Stok Awal</label>
                            <input type="number" name="stok" class="form-control" value="10" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Deskripsi Produk</label>
                            <textarea name="deskripsi" class="form-control" rows="5" placeholder="Tuliskan detail bahan frame, ukuran lensa, dan kelengkapan lainnya..." required></textarea>
                        </div>
                    </div>

                    <div class="col-lg-5 border-start-lg ps-lg-4">
                        <label class="form-label mb-3">Foto Produk</label>
                        <div class="upload-box mb-3" onclick="document.getElementById('fileInput').click()">
                            <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                            <h6 class="fw-bold text-dark">Klik untuk upload gambar</h6>
                            <p class="small text-muted">Format: JPG, PNG (Maks 2MB)</p>
                            <input type="file" name="gambar" id="fileInput" class="d-none" accept="image/*" required onchange="previewImage(this)">
                        </div>
                        
                        <div id="preview-container" class="mb-4 d-none">
                            <label class="small text-muted fw-bold mb-2">Preview:</label>
                            <img id="imagePreview" src="#" class="img-fluid rounded border shadow-sm" style="max-height: 200px; width: 100%; object-fit: cover;">
                        </div>

                        <button type="submit" name="tambah_produk" class="btn btn-sage w-100 shadow-sm">
                            <i class="fas fa-plus-circle me-2"></i> Tambahkan ke Katalog
                        </button>
                    </div>

                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function previewImage(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('imagePreview').src = e.target.result;
                document.getElementById('preview-container').classList.remove('d-none');
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>