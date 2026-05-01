<?php
session_start();
require_once '../backend/config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php"); exit;
}

if (isset($_POST['tambah_produk'])) {
    $nama     = trim($_POST['nama_produk']);
    $kategori = $_POST['kategori'];
    $harga    = (int)$_POST['harga'];
    $harga_coret = (int)($_POST['harga_coret'] ?? 0);
    $stok     = (int)$_POST['stok'];
    $deskripsi= trim($_POST['deskripsi']);
    $pilihan_varian = trim($_POST['pilihan_varian'] ?? '');
    $gambar   = 'default.png';

    $merek = trim($_POST['merek'] ?? '');
    $asal = trim($_POST['asal'] ?? '');
    $bahan = trim($_POST['bahan'] ?? '');
    $bentuk = trim($_POST['bentuk'] ?? '');
    $jenis_lensa = trim($_POST['jenis_lensa'] ?? '');
    $jenis_kelamin = trim($_POST['jenis_kelamin'] ?? '-');
    $spesifikasi_lain = trim($_POST['spesifikasi_lain'] ?? '');

    if (!empty($_FILES['gambar']['name'])) {
        $gambar = time() . '_' . basename($_FILES['gambar']['name']);
        move_uploaded_file($_FILES['gambar']['tmp_name'], '../frontend/images/produk/' . $gambar);
    }
    
    $gambar2 = null; $gambar3 = null; $gambar4 = null;
    if (!empty($_FILES['gambar2']['name'])) {
        $gambar2 = time() . '_2_' . basename($_FILES['gambar2']['name']);
        move_uploaded_file($_FILES['gambar2']['tmp_name'], '../frontend/images/produk/' . $gambar2);
    }
    if (!empty($_FILES['gambar3']['name'])) {
        $gambar3 = time() . '_3_' . basename($_FILES['gambar3']['name']);
        move_uploaded_file($_FILES['gambar3']['tmp_name'], '../frontend/images/produk/' . $gambar3);
    }
    if (!empty($_FILES['gambar4']['name'])) {
        $gambar4 = time() . '_4_' . basename($_FILES['gambar4']['name']);
        move_uploaded_file($_FILES['gambar4']['tmp_name'], '../frontend/images/produk/' . $gambar4);
    }

    $conn->prepare("INSERT INTO produk (nama_produk, kategori, harga, harga_coret, stok, deskripsi, gambar, pilihan_varian, gambar2, gambar3, gambar4, merek, asal, bahan, bentuk, jenis_lensa, jenis_kelamin, spesifikasi_lain) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)")
         ->execute([$nama, $kategori, $harga, $harga_coret, $stok, $deskripsi, $gambar, $pilihan_varian, $gambar2, $gambar3, $gambar4, $merek, $asal, $bahan, $bentuk, $jenis_lensa, $jenis_kelamin, $spesifikasi_lain]);
    header("Location: produk.php?added=1"); exit;
}

$pending_count = $conn->query("SELECT COUNT(*) FROM transaksi WHERE status_pesanan='pending'")->fetchColumn();
$active_page = 'produk';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Produk - XrivaStore Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="includes/admin.css">
    <style>
        .upload-drop { border:2px dashed #dee2e6; border-radius:14px; padding:40px; text-align:center; cursor:pointer; transition:.2s; background:#fafafa; }
        .upload-drop:hover, .upload-drop.drag { border-color:var(--sage-light); background:#f0f6f4; }
        .upload-drop i { color:#adb5bd; }
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
                <div class="topbar-title">Tambah Produk Baru</div>
                <div class="topbar-sub">Isi detail kacamata yang akan ditambahkan ke katalog</div>
            </div>
        </div>
        <div class="topbar-avatar"><?= strtoupper(substr($_SESSION['user_nama'],0,1)) ?></div>
    </div>

    <div class="page-content">
        <form action="" method="POST" enctype="multipart/form-data">
            <div class="row g-4">
                <!-- Left: Form Fields -->
                <div class="col-lg-7">
                    <div class="admin-card">
                        <div class="admin-card-header">
                            <h6><i class="fas fa-info-circle me-2" style="color:var(--sage)"></i>Informasi Produk</h6>
                        </div>
                        <div class="admin-card-body">
                            <div class="mb-3">
                                <label class="form-label">Nama Produk <span class="text-danger">*</span></label>
                                <input type="text" name="nama_produk" class="form-control"
                                       placeholder="Cth: Kacamata Minus Aviator Gold" required>
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <label class="form-label">Kategori <span class="text-danger">*</span></label>
                                    <select name="kategori" class="form-select" required>
                                        <option value="" disabled selected>Pilih Kategori...</option>
                                        <option value="Kacamata Gaya">Kacamata Gaya</option>
                                        <option value="Kacamata Minus">Kacamata Minus</option>
                                        <option value="Kacamata Plus">Kacamata Plus</option>
                                        <option value="Kacamata Hitam">Kacamata Hitam</option>
                                        <option value="Aksesoris">Aksesoris & Kotak</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Harga Jual <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light">Rp</span>
                                        <input type="number" name="harga" class="form-control" placeholder="150000" min="0" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Harga Coret (Opsional)</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light">Rp</span>
                                        <input type="number" name="harga_coret" class="form-control" placeholder="200000" min="0" value="0">
                                    </div>
                                </div>
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-md-8">
                                    <label class="form-label">Pilihan Varian (Pisahkan dengan koma)</label>
                                    <input type="text" name="pilihan_varian" class="form-control" placeholder="Cth: Hitam, Putih atau -1.0, -1.5, -2.0">
                                    <small class="text-muted">Kosongkan jika tidak ada pilihan warna/minus.</small>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Stok Awal <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="number" name="stok" class="form-control" value="10" min="0" required>
                                        <span class="input-group-text bg-light">pcs</span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Spesifikasi -->
                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <label class="form-label small">Merek</label>
                                    <input type="text" name="merek" class="form-control form-control-sm" placeholder="Kosongkan jika tidak ada" value="">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small">Negara Asal</label>
                                    <input type="text" name="asal" class="form-control form-control-sm" placeholder="Cth: Indonesia" value="">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small">Bahan Bingkai</label>
                                    <input type="text" name="bahan" class="form-control form-control-sm" placeholder="Kosongkan jika bukan kacamata" value="">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small">Bentuk Bingkai</label>
                                    <input type="text" name="bentuk" class="form-control form-control-sm" placeholder="Kosongkan jika bukan kacamata" value="">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small">Jenis Lensa</label>
                                    <input type="text" name="jenis_lensa" class="form-control form-control-sm" placeholder="Kosongkan jika bukan kacamata" value="">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small">Jenis Kelamin</label>
                                    <select name="jenis_kelamin" class="form-select form-select-sm">
                                        <option value="-" selected>Pilih... (Abaikan jika Aksesoris)</option>
                                        <option value="Unisex">Unisex</option>
                                        <option value="Pria">Pria</option>
                                        <option value="Wanita">Wanita</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Spesifikasi Tambahan (Opsional)</label>
                                <textarea name="spesifikasi_lain" class="form-control form-control-sm" rows="3" placeholder="Contoh:&#10;Kapasitas : 50ml&#10;Aroma : Mint"></textarea>
                                <small class="text-muted" style="font-size: 0.75rem;">Pisahkan nama dan nilai dengan titik dua (:). Satu spesifikasi per baris.</small>
                            </div>

                            <div class="mb-0">
                                <label class="form-label d-flex justify-content-between">
                                    Deskripsi Produk <span class="char-count" id="charCount">0 / 500</span>
                                </label>
                                <textarea name="deskripsi" id="deskripsiField" class="form-control" rows="5" maxlength="500"
                                          placeholder="Tuliskan detail: bahan frame, ukuran lensa, kelengkapan, dll..." required></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: Upload + Submit -->
                <div class="col-lg-5">
                    <div class="admin-card mb-4">
                        <div class="admin-card-header">
                            <h6><i class="fas fa-image me-2" style="color:var(--sage)"></i>Foto Produk</h6>
                        </div>
                        <div class="admin-card-body">
                            <div class="upload-drop mb-3" id="dropZone" onclick="document.getElementById('fileInput').click()">
                                <i class="fas fa-cloud-upload-alt fa-3x mb-3"></i>
                                <div class="fw-bold text-dark">Klik atau drag & drop gambar</div>
                                <div class="text-muted small mt-1">Format: JPG, PNG, WEBP (Maks 2MB)</div>
                                <input type="file" name="gambar" id="fileInput" class="d-none" accept="image/*" onchange="previewImage(this)">
                            </div>
                            <div id="previewWrap" class="d-none text-center mb-3">
                                <img id="imgPreview" src="#" class="rounded w-100 shadow-sm" style="max-height:220px;object-fit:contain;border:1px solid #eee;">
                                <button type="button" class="btn btn-sm btn-outline-secondary mt-2 rounded-pill" onclick="clearImage()">
                                    <i class="fas fa-times me-1"></i> Ganti Gambar
                                </button>
                            </div>
                            
                            <hr>
                            <div class="mb-3 mt-3">
                                <label class="form-label small fw-bold">Gambar Tambahan 1 (Opsional)</label>
                                <input type="file" name="gambar2" class="form-control form-control-sm" accept="image/*">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Gambar Tambahan 2 (Opsional)</label>
                                <input type="file" name="gambar3" class="form-control form-control-sm" accept="image/*">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Gambar Tambahan 3 (Opsional)</label>
                                <input type="file" name="gambar4" class="form-control form-control-sm" accept="image/*">
                            </div>
                        </div>
                    </div>

                    <div class="admin-card">
                        <div class="admin-card-body">
                            <button type="submit" name="tambah_produk" class="btn btn-sage rounded-pill w-100 fw-bold py-2 mb-2">
                                <i class="fas fa-plus-circle me-2"></i> Tambahkan ke Katalog
                            </button>
                            <a href="produk.php" class="btn btn-outline-secondary rounded-pill w-100 fw-bold py-2">
                                Batal
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('imgPreview').src = e.target.result;
            document.getElementById('previewWrap').classList.remove('d-none');
            document.getElementById('dropZone').classList.add('d-none');
        };
        reader.readAsDataURL(input.files[0]);
    }
}
function clearImage() {
    document.getElementById('fileInput').value = '';
    document.getElementById('imgPreview').src = '#';
    document.getElementById('previewWrap').classList.add('d-none');
    document.getElementById('dropZone').classList.remove('d-none');
}

// Char counter
document.getElementById('deskripsiField').addEventListener('input', function() {
    document.getElementById('charCount').textContent = this.value.length + ' / 500';
});

// Drag drop
const dropZone = document.getElementById('dropZone');
dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('drag'); });
dropZone.addEventListener('dragleave', () => dropZone.classList.remove('drag'));
dropZone.addEventListener('drop', e => {
    e.preventDefault(); dropZone.classList.remove('drag');
    const file = e.dataTransfer.files[0];
    if (file) {
        const dt = new DataTransfer(); dt.items.add(file);
        document.getElementById('fileInput').files = dt.files;
        previewImage(document.getElementById('fileInput'));
    }
});
</script>
</body>
</html>