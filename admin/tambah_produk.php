<?php
session_start();
require_once '../backend/config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php"); exit;
}

if (isset($_POST['tambah_produk'])) {
    $nama = $_POST['nama_produk'];
    $harga = $_POST['harga'];
    $stok = $_POST['stok'];
    $kota = $_POST['kota_asal'];
    $lat = $_POST['lat'];
    $lng = $_POST['lng'];
    $desk = $_POST['deskripsi'];
    $gbr = 'default.png';

    if (!empty($_FILES['gambar']['name'])) {
        $gbr = $_FILES['gambar']['name'];
        move_uploaded_file($_FILES['gambar']['tmp_name'], '../frontend/images/produk/' . $gbr);
    }
    
    $ins = $conn->prepare("INSERT INTO produk (nama_produk, harga, stok, kota_asal, lat, lng, deskripsi, gambar) VALUES (?,?,?,?,?,?,?,?)");
    $ins->execute([$nama, $harga, $stok, $kota, $lat, $lng, $desk, $gbr]);
    header("Location: produk.php"); exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Produk - XrivaStore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --xriva-dark: #4a7c6b; --xriva-primary: #7cb3a1; }
        body { background-color: #f4f7f6; font-family: 'Segoe UI', sans-serif; }
        .sidebar { min-height: 100vh; background-color: var(--xriva-dark); color: white; border-top-right-radius: 20px; border-bottom-right-radius: 20px; }
        .sidebar .nav-link { color: rgba(255,255,255,0.8); margin-bottom: 10px; border-radius: 10px; padding: 12px 20px; }
        .sidebar .nav-link.active { background-color: rgba(255,255,255,0.15); color: white; font-weight: bold; }
        .btn-sage { background-color: var(--xriva-primary); color: white; border: none; }
        .btn-sage:hover { background-color: var(--xriva-dark); color: white; }
    </style>
</head>
<body>

<div class="d-flex">
    <div class="sidebar p-4" style="width: 280px; flex-shrink: 0;">
        <h4 class="fw-bold mb-5 text-center"><i class="fas fa-leaf"></i> AdminPanel</h4>
        <ul class="nav flex-column">
            <li class="nav-item"><a class="nav-link active" href="produk.php"><i class="fas fa-box me-3"></i> Kelola Produk</a></li>
            <li class="nav-item"><a class="nav-link" href="transaksi.php"><i class="fas fa-shopping-cart me-3"></i> Pesanan</a></li>
            <li class="nav-item"><a class="nav-link" href="laporan.php"><i class="fas fa-chart-line me-3"></i> Laporan</a></li>
            <li class="nav-item mt-5"><a class="nav-link text-danger" href="../logout.php"><i class="fas fa-sign-out-alt me-3"></i> Keluar</a></li>
        </ul>
    </div>

    <div class="p-5 w-100">
        <a href="produk.php" class="text-muted text-decoration-none mb-3 d-inline-block"><i class="fas fa-arrow-left me-2"></i>Kembali</a>
        <h2 class="fw-bold mb-4">Tambah Produk Baru</h2>

        <div class="card border-0 shadow-sm p-4" style="border-radius: 16px;">
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nama Produk</label>
                            <input type="text" name="nama_produk" class="form-control" placeholder="Cth: Meja Kayu" required>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Harga</label>
                                <input type="number" name="harga" class="form-control" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-bold">Stok</label>
                                <input type="number" name="stok" class="form-control" value="10" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Kota Asal</label>
                                <input type="text" id="kota_input" name="kota_asal" class="form-control" value="Bekasi">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="button" onclick="cariLokasi()" class="btn btn-info text-white w-100"><i class="fas fa-search-location"></i> Cari</button>
                            </div>
                        </div>
                        
                        <div class="row mb-3 bg-light p-2 rounded">
                            <div class="col-md-6"><label class="small text-muted">Lat</label><input type="text" name="lat" id="lat_input" class="form-control form-control-sm" value="-6.238270" readonly></div>
                            <div class="col-md-6"><label class="small text-muted">Lng</label><input type="text" name="lng" id="lng_input" class="form-control form-control-sm" value="107.045650" readonly></div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Deskripsi</label>
                            <textarea name="deskripsi" class="form-control" rows="5" required></textarea>
                        </div>
                    </div>

                    <div class="col-md-4 border-start ps-4">
                        <label class="form-label fw-bold">Foto Produk</label>
                        <input type="file" name="gambar" class="form-control mb-4" required>
                        <button type="submit" name="tambah_produk" class="btn btn-sage w-100 py-3 rounded-pill fw-bold">Tambahkan Produk</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function cariLokasi() {
    let kota = document.getElementById('kota_input').value;
    fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${kota}`)
    .then(r => r.json()).then(data => {
        if(data.length > 0) {
            document.getElementById('lat_input').value = data[0].lat;
            document.getElementById('lng_input').value = data[0].lon;
            alert("Lokasi " + kota + " terkunci!");
        } else { alert("Lokasi tidak ditemukan."); }
    });
}
</script>
</body>
</html>