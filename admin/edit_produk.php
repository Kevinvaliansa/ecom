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
    $pilihan_varian = trim($_POST['pilihan_varian'] ?? '');

    $merek = trim($_POST['merek'] ?? '');
    $asal = trim($_POST['asal'] ?? '');
    $bahan = trim($_POST['bahan'] ?? '');
    $bentuk = trim($_POST['bentuk'] ?? '');
    $jenis_lensa = trim($_POST['jenis_lensa'] ?? '');
    $jenis_kelamin = trim($_POST['jenis_kelamin'] ?? '-');
    $spesifikasi_lain = trim($_POST['spesifikasi_lain'] ?? '');

    $sql = "UPDATE produk SET nama_produk=?, kategori=?, harga=?, harga_coret=?, stok=?, deskripsi=?, pilihan_varian=?, merek=?, asal=?, bahan=?, bentuk=?, jenis_lensa=?, jenis_kelamin=?, spesifikasi_lain=?";
    $params = [$nama, $kategori, $harga, $harga_coret, $stok, $deskripsi, $pilihan_varian, $merek, $asal, $bahan, $bentuk, $jenis_lensa, $jenis_kelamin, $spesifikasi_lain];

    if (!empty($_FILES['gambar']['name'])) {
        $gambar_baru = time() . '_' . basename($_FILES['gambar']['name']);
        move_uploaded_file($_FILES['gambar']['tmp_name'], '../frontend/images/produk/' . $gambar_baru);
        $sql .= ", gambar=?";
        $params[] = $gambar_baru;
    }
    if (!empty($_FILES['gambar2']['name'])) {
        $g2 = time() . '_2_' . basename($_FILES['gambar2']['name']);
        move_uploaded_file($_FILES['gambar2']['tmp_name'], '../frontend/images/produk/' . $g2);
        $sql .= ", gambar2=?";
        $params[] = $g2;
    }
    if (!empty($_FILES['gambar3']['name'])) {
        $g3 = time() . '_3_' . basename($_FILES['gambar3']['name']);
        move_uploaded_file($_FILES['gambar3']['tmp_name'], '../frontend/images/produk/' . $g3);
        $sql .= ", gambar3=?";
        $params[] = $g3;
    }
    if (!empty($_FILES['gambar4']['name'])) {
        $g4 = time() . '_4_' . basename($_FILES['gambar4']['name']);
        move_uploaded_file($_FILES['gambar4']['tmp_name'], '../frontend/images/produk/' . $g4);
        $sql .= ", gambar4=?";
        $params[] = $g4;
    }

    $sql .= " WHERE id=?";
    $params[] = $id_edit;
    
    $conn->prepare($sql)->execute($params);
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
                            <div class="row g-3 mb-3">
                                <div class="col-md-8">
                                    <label class="form-label">Pilihan Varian (Pisahkan dengan koma)</label>
                                    <input type="text" name="pilihan_varian" class="form-control" 
                                           placeholder="Cth: Hitam, Putih atau -1.0, -1.5, -2.0"
                                           value="<?= htmlspecialchars($p['pilihan_varian'] ?? '') ?>">
                                    <small class="text-muted">Kosongkan jika tidak ada pilihan warna/minus.</small>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Stok <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="number" name="stok" class="form-control" value="<?= $p['stok'] ?>" min="0" required>
                                        <span class="input-group-text bg-light">pcs</span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Spesifikasi -->
                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <label class="form-label small">Merek</label>
                                    <input type="text" name="merek" class="form-control form-control-sm" placeholder="Kosongkan jika tidak ada" value="<?= htmlspecialchars($p['merek'] ?? '') ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small">Negara Asal</label>
                                    <input type="text" name="asal" class="form-control form-control-sm" placeholder="Cth: Indonesia" value="<?= htmlspecialchars($p['asal'] ?? '') ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small">Bahan Bingkai</label>
                                    <input type="text" name="bahan" class="form-control form-control-sm" placeholder="Kosongkan jika bukan kacamata" value="<?= htmlspecialchars($p['bahan'] ?? '') ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small">Bentuk Bingkai</label>
                                    <input type="text" name="bentuk" class="form-control form-control-sm" placeholder="Kosongkan jika bukan kacamata" value="<?= htmlspecialchars($p['bentuk'] ?? '') ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small">Jenis Lensa</label>
                                    <input type="text" name="jenis_lensa" class="form-control form-control-sm" placeholder="Kosongkan jika bukan kacamata" value="<?= htmlspecialchars($p['jenis_lensa'] ?? '') ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small">Jenis Kelamin</label>
                                    <select name="jenis_kelamin" class="form-select form-select-sm">
                                        <option value="-" <?= ($p['jenis_kelamin'] ?? '-') == '-' ? 'selected' : '' ?>>Pilih... (Abaikan jika Aksesoris)</option>
                                        <option value="Unisex" <?= ($p['jenis_kelamin'] ?? '') == 'Unisex' ? 'selected' : '' ?>>Unisex</option>
                                        <option value="Pria" <?= ($p['jenis_kelamin'] ?? '') == 'Pria' ? 'selected' : '' ?>>Pria</option>
                                        <option value="Wanita" <?= ($p['jenis_kelamin'] ?? '') == 'Wanita' ? 'selected' : '' ?>>Wanita</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-bold">Spesifikasi Tambahan (Opsional)</label>
                                <textarea name="spesifikasi_lain" class="form-control form-control-sm" rows="3" placeholder="Contoh:&#10;Kapasitas : 50ml&#10;Aroma : Mint"><?= htmlspecialchars($p['spesifikasi_lain'] ?? '') ?></textarea>
                                <small class="text-muted" style="font-size: 0.75rem;">Pisahkan nama dan nilai dengan titik dua (:). Satu spesifikasi per baris.</small>
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

                            <hr>
                            <p class="small text-muted mb-2 mt-3">Gambar Tambahan:</p>
                            
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Gambar Tambahan 1 (Opsional)</label>
                                <?php if(!empty($p['gambar2'])): ?>
                                    <div class="mb-2"><img src="../frontend/images/produk/<?= htmlspecialchars($p['gambar2']) ?>" style="height:60px; border-radius:5px; object-fit:cover;"></div>
                                <?php endif; ?>
                                <input type="file" name="gambar2" class="form-control form-control-sm" accept="image/*">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Gambar Tambahan 2 (Opsional)</label>
                                <?php if(!empty($p['gambar3'])): ?>
                                    <div class="mb-2"><img src="../frontend/images/produk/<?= htmlspecialchars($p['gambar3']) ?>" style="height:60px; border-radius:5px; object-fit:cover;"></div>
                                <?php endif; ?>
                                <input type="file" name="gambar3" class="form-control form-control-sm" accept="image/*">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Gambar Tambahan 3 (Opsional)</label>
                                <?php if(!empty($p['gambar4'])): ?>
                                    <div class="mb-2"><img src="../frontend/images/produk/<?= htmlspecialchars($p['gambar4']) ?>" style="height:60px; border-radius:5px; object-fit:cover;"></div>
                                <?php endif; ?>
                                <input type="file" name="gambar4" class="form-control form-control-sm" accept="image/*">
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