<?php
session_start();
require_once '../backend/config/database.php';

// CEK LOGIN DAN ROLE ADMIN
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php"); 
    exit;
}

$id_produk = $_GET['id'] ?? 0;

// ==========================================
// LOGIKA EDIT PRODUK
// ==========================================
if (isset($_POST['edit_produk'])) {
    $id_edit = $_POST['id'];
    $nama = $_POST['nama_produk'];
    $kategori = $_POST['kategori']; // Menangkap input kategori baru
    $harga = $_POST['harga'];
    $stok = $_POST['stok'];
    $deskripsi = $_POST['deskripsi'];
    
    // Jika admin mengupload gambar baru
    if (!empty($_FILES['gambar']['name'])) {
        $gambar_baru = time() . '_' . $_FILES['gambar']['name']; // Tambah time() agar nama file unik
        move_uploaded_file($_FILES['gambar']['tmp_name'], '../frontend/images/produk/' . $gambar_baru);
        
        $update = $conn->prepare("UPDATE produk SET nama_produk=?, kategori=?, harga=?, stok=?, deskripsi=?, gambar=? WHERE id=?");
        $update->execute([$nama, $kategori, $harga, $stok, $deskripsi, $gambar_baru, $id_edit]);
    } else {
        // Jika gambar tidak diganti
        $update = $conn->prepare("UPDATE produk SET nama_produk=?, kategori=?, harga=?, stok=?, deskripsi=? WHERE id=?");
        $update->execute([$nama, $kategori, $harga, $stok, $deskripsi, $id_edit]);
    }
    
    // ==============================================================
    // POP-UP SWEETALERT2 UNTUK EDIT PRODUK BERHASIL
    // ==============================================================
    echo "<!DOCTYPE html><html><head><meta name='viewport' content='width=device-width, initial-scale=1.0'><script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script></head><body style='background-color: #f4f7f6;'>";
    echo "<script>
        Swal.fire({
            title: 'Update Berhasil!',
            text: 'Data kacamata berhasil diperbarui.',
            icon: 'success',
            confirmButtonColor: '#4a7c6b',
            confirmButtonText: 'Oke'
        }).then((result) => {
            window.location.href = 'produk.php';
        });
    </script></body></html>";
    exit;
}

// Ambil data produk saat ini
$stmt = $conn->prepare("SELECT * FROM produk WHERE id = ?");
$stmt->execute([$id_produk]);
$p = $stmt->fetch();

if (!$p) { 
    header("Location: produk.php"); 
    exit; 
}

// Set default kategori jika di database masih kosong
$kat_saat_ini = $p['kategori'] ?? 'Kacamata Gaya';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Produk - Admin Xriva Eyewear</title>
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
        .btn-sage { background-color: var(--sage-light); color: white; border: none; border-radius: 10px; font-weight: bold; transition: 0.3s; }
        .btn-sage:hover { background-color: var(--sage); color: white; }
        
        /* Form Styling */
        .form-control, .form-select { border-radius: 10px; border: 1px solid #ddd; padding: 10px 15px; }
        .form-control:focus, .form-select:focus { border-color: var(--sage-light); box-shadow: 0 0 0 0.2rem rgba(124, 179, 161, 0.25); }
        .form-label { font-weight: 600; color: #444; font-size: 0.9rem; }
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
        <a href="produk.php" class="text-muted text-decoration-none mb-3 d-inline-block"><i class="fas fa-arrow-left me-2"></i>Kembali ke Daftar Produk</a>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold text-dark m-0">Edit Produk</h2>
        </div>

        <div class="card card-custom p-4 p-lg-5">
            <form action="" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                
                <div class="row g-5">
                    <div class="col-lg-8">
                        <div class="row g-3 mb-3">
                            <div class="col-md-7">
                                <label class="form-label">Nama Produk</label>
                                <input type="text" name="nama_produk" class="form-control" value="<?= htmlspecialchars($p['nama_produk']) ?>" required>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label text-sage">Kategori Produk</label>
                                <select name="kategori" class="form-select" required>
                                    <option value="Kacamata Gaya" <?= $kat_saat_ini == 'Kacamata Gaya' ? 'selected' : '' ?>>Kacamata Gaya</option>
                                    <option value="Kacamata Minus" <?= $kat_saat_ini == 'Kacamata Minus' ? 'selected' : '' ?>>Kacamata Minus</option>
                                    <option value="Aksesoris" <?= $kat_saat_ini == 'Aksesoris' ? 'selected' : '' ?>>Aksesoris & Kotak</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Harga Jual (Rp)</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0">Rp</span>
                                    <input type="number" name="harga" class="form-control border-start-0 ps-0" value="<?= $p['harga'] ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Jumlah Stok</label>
                                <input type="number" name="stok" class="form-control" value="<?= $p['stok'] ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Deskripsi Lengkap</label>
                            <textarea name="deskripsi" class="form-control" rows="6" required><?= htmlspecialchars($p['deskripsi']) ?></textarea>
                            <div class="form-text mt-2"><i class="fas fa-info-circle"></i> Tuliskan keunggulan produk, dimensi kacamata, dan bahan frame di sini.</div>
                        </div>
                    </div>

                    <div class="col-lg-4 border-start-lg ps-lg-4">
                        <div class="bg-light rounded-3 p-3 text-center mb-4 border">
                            <label class="form-label d-block text-start mb-3">Foto Produk Saat Ini</label>
                            <img src="../frontend/images/produk/<?= htmlspecialchars($p['gambar']) ?>" class="img-fluid rounded shadow-sm" style="max-height: 220px; object-fit: cover;">
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label text-sage"><i class="fas fa-camera me-1"></i> Ganti Foto (Opsional)</label>
                            <input type="file" name="gambar" class="form-control" accept="image/*" onchange="previewImage(this)">
                            <small class="text-muted d-block mt-2" style="font-size: 0.8rem;">Biarkan kosong jika tidak ingin mengubah gambar.</small>
                        </div>
                        
                        <div id="preview-container" class="mb-4 d-none">
                            <label class="small text-muted fw-bold mb-2">Preview Foto Baru:</label>
                            <img id="imagePreview" src="#" class="img-fluid rounded border shadow-sm" style="max-height: 200px; width: 100%; object-fit: cover;">
                        </div>

                        <button type="submit" name="edit_produk" class="btn btn-sage w-100 py-3 mt-2 shadow-sm">
                            <i class="fas fa-save me-2"></i> Simpan Perubahan
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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
</body>
</html>