<?php
require_once '../backend/config/database.php';

// PROSES TAMBAH PRODUK
if (isset($_POST['tambah'])) {
    $nama = $_POST['nama_produk'];
    $kategori = $_POST['id_kategori'];
    $harga = $_POST['harga'];
    $stok = $_POST['stok'];
    $deskripsi = $_POST['deskripsi'];
    
    $gambar = $_FILES['gambar']['name'];
    $tmp = $_FILES['gambar']['tmp_name'];
    $path = "../frontend/images/produk/" . $gambar;
    
    if (move_uploaded_file($tmp, $path)) {
        $stmt = $conn->prepare("INSERT INTO produk (id_kategori, nama_produk, harga, stok, deskripsi, gambar) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$kategori, $nama, $harga, $stok, $deskripsi, $gambar]);
        $pesan_sukses = "Produk berhasil ditambahkan!";
    } else {
        $pesan_error = "Gagal upload gambar!";
    }
}

// PROSES EDIT PRODUK (Stok & Harga)
if (isset($_POST['edit_produk'])) {
    $id_produk = $_POST['id_produk'];
    $harga_baru = $_POST['harga'];
    $stok_baru = $_POST['stok'];
    
    $update = $conn->prepare("UPDATE produk SET harga = ?, stok = ? WHERE id = ?");
    if ($update->execute([$harga_baru, $stok_baru, $id_produk])) {
        $pesan_sukses = "Data produk berhasil diperbarui!";
    } else {
        $pesan_error = "Gagal memperbarui data produk.";
    }
}

// AMBIL DATA KATEGORI UNTUK DROPDOWN
$kategori_stmt = $conn->query("SELECT * FROM kategori");
$kategori_data = $kategori_stmt->fetchAll();

// AMBIL DATA PRODUK UNTUK TABEL
$produk_stmt = $conn->query("SELECT p.*, k.nama_kategori FROM produk p JOIN kategori k ON p.id_kategori = k.id ORDER BY p.id DESC");
$produk_data = $produk_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Kelola Produk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../frontend/css/style.css">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark navbar-sage mb-4">
    <div class="container-fluid">
        <span class="navbar-brand mb-0 h1 fw-bold">Dashboard Admin</span>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="adminNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link active fw-bold" href="produk.php">Kelola Produk</a></li>
                <li class="nav-item"><a class="nav-link" href="transaksi.php">Kelola Transaksi</a></li>
                <li class="nav-item"><a class="nav-link" href="laporan.php">Laporan Penjualan</a></li>
            </ul>
            <a href="../index.php" class="btn btn-outline-light btn-sm" target="_blank">Lihat Website</a>
        </div>
    </div>
</nav>

<div class="container-fluid px-4">
    
    <?php if(isset($pesan_sukses)): ?>
        <div class="alert alert-success alert-dismissible fade show"><?= $pesan_sukses ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>
    <?php if(isset($pesan_error)): ?>
        <div class="alert alert-danger alert-dismissible fade show"><?= $pesan_error ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-3 mb-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-sage-light text-sage-dark fw-bold">Tambah Produk Baru</div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-2"><label class="form-label">Nama</label><input type="text" name="nama_produk" class="form-control form-control-sm" required></div>
                        <div class="mb-2"><label class="form-label">Kategori</label>
                            <select name="id_kategori" class="form-control form-control-sm" required>
                                <option value="">Pilih Kategori...</option>
                                <?php foreach($kategori_data as $k): ?><option value="<?= $k['id'] ?>"><?= $k['nama_kategori'] ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-2"><label class="form-label">Harga (Rp)</label><input type="number" name="harga" class="form-control form-control-sm" required></div>
                        <div class="mb-2"><label class="form-label">Stok</label><input type="number" name="stok" class="form-control form-control-sm" required></div>
                        <div class="mb-2"><label class="form-label">Deskripsi</label><textarea name="deskripsi" class="form-control form-control-sm" rows="2" required></textarea></div>
                        <div class="mb-3"><label class="form-label">Gambar</label><input type="file" name="gambar" class="form-control form-control-sm" accept="image/*" required></div>
                        <button type="submit" name="tambah" class="btn btn-sage btn-sm w-100 fw-bold">Simpan Produk</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-9">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-sage-light text-sage-dark fw-bold">Daftar Produk di Database</div>
                <div class="card-body table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Gambar</th>
                                <th>Nama Produk</th>
                                <th>Kategori</th>
                                <th>Harga</th>
                                <th>Stok</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($produk_data as $p): ?>
                            <tr>
                                <td><img src="../frontend/images/produk/<?= htmlspecialchars($p['gambar']) ?>" width="50" height="50" style="object-fit:cover; border-radius:5px; border: 1px solid #ddd;"></td>
                                <td class="fw-bold"><?= htmlspecialchars($p['nama_produk']) ?></td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($p['nama_kategori']) ?></span></td>
                                <td>Rp <?= number_format($p['harga'], 0, ',', '.') ?></td>
                                <td>
                                    <?php if($p['stok'] <= 0): ?>
                                        <span class="text-danger fw-bold"><?= $p['stok'] ?> Pcs (Habis)</span>
                                    <?php else: ?>
                                        <?= $p['stok'] ?> Pcs
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-sage" data-bs-toggle="modal" data-bs-target="#editModal<?= $p['id'] ?>">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                </td>
                            </tr>

                            <div class="modal fade" id="editModal<?= $p['id'] ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-sm modal-dialog-centered">
                                    <div class="modal-content">
                                        <div class="modal-header bg-sage-primary text-white">
                                            <h6 class="modal-title fw-bold">Edit: <?= htmlspecialchars($p['nama_produk']) ?></h6>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <form method="POST" action="">
                                            <div class="modal-body">
                                                <input type="hidden" name="id_produk" value="<?= $p['id'] ?>">
                                                
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">Harga Baru (Rp)</label>
                                                    <input type="number" name="harga" class="form-control" value="<?= $p['harga'] ?>" required>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">Update Stok</label>
                                                    <input type="number" name="stok" class="form-control" value="<?= $p['stok'] ?>" required>
                                                    <small class="text-muted d-block mt-1">*Jika stok minus (-1), ubah jadi angka positif.</small>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" name="edit_produk" class="btn btn-sage btn-sm fw-bold">Simpan</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>