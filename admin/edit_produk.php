<?php
session_start();
require_once '../backend/config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php"); exit;
}

$id_produk = (int)($_GET['id'] ?? 0);

if (isset($_POST['edit_produk'])) {
    $id_edit   = (int)$_POST['id'];
    $nama      = trim($_POST['nama_produk']);
    $kategori  = $_POST['kategori'];
    $harga     = (int)$_POST['harga'];
    $harga_coret = (int)($_POST['harga_coret'] ?? 0);
    $stok      = (int)$_POST['stok'];
    $deskripsi = trim($_POST['deskripsi']);

    if (!empty($_FILES['gambar']['name'])) {
        $gambar_baru = time() . '_' . basename($_FILES['gambar']['name']);
        move_uploaded_file($_FILES['gambar']['tmp_name'], '../frontend/images/produk/' . $gambar_baru);
        $conn->prepare("UPDATE produk SET nama_produk=?,kategori=?,harga=?,harga_coret=?,stok=?,deskripsi=?,gambar=? WHERE id=?")
             ->execute([$nama, $kategori, $harga, $harga_coret, $stok, $deskripsi, $gambar_baru, $id_edit]);
    } else {
        $conn->prepare("UPDATE produk SET nama_produk=?,kategori=?,harga=?,harga_coret=?,stok=?,deskripsi=? WHERE id=?")
             ->execute([$nama, $kategori, $harga, $harga_coret, $stok, $deskripsi, $id_edit]);
    }
    header("Location: produk.php?updated=1"); exit;
}

$stmt = $conn->prepare("SELECT * FROM produk WHERE id=?");
$stmt->execute([$id_produk]);
$p = $stmt->fetch();
if (!$p) { header("Location: produk.php"); exit; }

$kat_saat_ini = $p['kategori'] ?? 'Kacamata Gaya';
$pending_count = $conn->query("SELECT COUNT(*) FROM transaksi WHERE status_pesanan='pending'")->fetchColumn();
$active_page = 'produk';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Produk - XrivaStore Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="includes/admin.css">
    <style>
        .img-current { border-radius:14px; object-fit:cover; border:2px solid #e9ecef; width:100%; max-height:220px; }
        .char-count { font-size:.75rem; color:#adb5bd; }
    </style>
</head>
<body>

<?php include 'includes/sidebar.php'; ?>

<div class="main-content">
    <div class="topbar">
        <div class="d-flex align-items-center gap-3">
            <a href="produk.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                <i class="fas fa-arrow-left me-1"></i> Kembali
            </a>
            <div>
                <div class="topbar-title">Edit Produk</div>
                <div class="topbar-sub">ID #<?= $p['id'] ?> · <?= htmlspecialchars($p['nama_produk']) ?></div>
            </div>
        </div>
        <div class="topbar-avatar"><?= strtoupper(substr($_SESSION['user_nama'],0,1)) ?></div>
    </div>

    <div class="page-content">
        <form action="" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?= $p['id'] ?>">
            <div class="row g-4">

                <!-- Left: Fields -->
                <div class="col-lg-7">
                    <div class="admin-card">
                        <div class="admin-card-header">
                            <h6><i class="fas fa-pencil-alt me-2" style="color:var(--sage)"></i>Detail Produk</h6>
                            <span class="badge rounded-pill" style="background:#e8f0ed;color:var(--sage);font-size:.78rem;">ID #<?= $p['id'] ?></span>
                        </div>
                        <div class="admin-card-body">
                            <div class="mb-3">
                                <label class="form-label">Nama Produk <span class="text-danger">*</span></label>
                                <input type="text" name="nama_produk" class="form-control"
                                       value="<?= htmlspecialchars($p['nama_produk']) ?>" required>
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <label class="form-label">Kategori <span class="text-danger">*</span></label>
                                    <select name="kategori" class="form-select" required>
                                        <?php
                                        $kategori_opts = ['Kacamata Gaya','Kacamata Minus','Kacamata Plus','Kacamata Hitam','Aksesoris'];
                                        foreach ($kategori_opts as $k): ?>
                                        <option value="<?= $k ?>" <?= $kat_saat_ini == $k ? 'selected':'' ?>><?= $k ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Harga Jual <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light">Rp</span>
                                        <input type="number" name="harga" class="form-control" value="<?= $p['harga'] ?>" min="0" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Harga Coret</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light">Rp</span>
                                        <input type="number" name="harga_coret" class="form-control" value="<?= htmlspecialchars($p['harga_coret'] ?? 0) ?>" min="0">
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Stok <span class="text-danger">*</span></label>
                                <div class="input-group" style="max-width:200px;">
                                    <input type="number" name="stok" class="form-control" value="<?= $p['stok'] ?>" min="0" required>
                                    <span class="input-group-text bg-light">pcs</span>
                                </div>
                            </div>
                            <div class="mb-0">
                                <label class="form-label d-flex justify-content-between">
                                    Deskripsi <span class="char-count" id="charCount"><?= strlen($p['deskripsi']) ?> / 500</span>
                                </label>
                                <textarea name="deskripsi" id="deskripsiField" class="form-control" rows="5" maxlength="500" required><?= htmlspecialchars($p['deskripsi']) ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: Image + Save -->
                <div class="col-lg-5">
                    <div class="admin-card mb-4">
                        <div class="admin-card-header">
                            <h6><i class="fas fa-image me-2" style="color:var(--sage)"></i>Foto Produk</h6>
                        </div>
                        <div class="admin-card-body">
                            <p class="small text-muted mb-2">Foto saat ini:</p>
                            <img src="../frontend/images/produk/<?= htmlspecialchars($p['gambar']) ?>"
                                 id="currentImg" class="img-current mb-3" alt="<?= htmlspecialchars($p['nama_produk']) ?>">

                            <label class="form-label"><i class="fas fa-camera me-1"></i> Ganti Foto (Opsional)</label>
                            <input type="file" name="gambar" class="form-control" accept="image/*" id="editFileInput" onchange="previewNewImage(this)">
                            <p class="small text-muted mt-2">Biarkan kosong jika tidak ingin mengganti gambar.</p>

                            <div id="newPreviewWrap" class="d-none mt-3">
                                <p class="small fw-semibold text-muted">Preview foto baru:</p>
                                <img id="newImgPreview" src="#" class="img-current" alt="preview baru">
                            </div>
                        </div>
                    </div>

                    <div class="admin-card">
                        <div class="admin-card-body">
                            <button type="submit" name="edit_produk" class="btn btn-sage rounded-pill w-100 fw-bold py-2 mb-2">
                                <i class="fas fa-save me-2"></i> Simpan Perubahan
                            </button>
                            <a href="produk.php" class="btn btn-outline-secondary rounded-pill w-100 py-2">Batal</a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function previewNewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('newImgPreview').src = e.target.result;
            document.getElementById('newPreviewWrap').classList.remove('d-none');
        };
        reader.readAsDataURL(input.files[0]);
    }
}
document.getElementById('deskripsiField').addEventListener('input', function() {
    document.getElementById('charCount').textContent = this.value.length + ' / 500';
});
</script>
</body>
</html>