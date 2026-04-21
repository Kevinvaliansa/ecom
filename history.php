<?php
session_start();
require_once 'backend/config/database.php';

// Cek apakah user sudah login, jika belum lempar ke halaman login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$id_user = $_SESSION['user_id'];

// ==========================================
// LOGIKA MEMBATALKAN PESANAN (CANCEL)
// ==========================================
if (isset($_GET['cancel'])) {
    $id_cancel = $_GET['cancel'];
    $cek = $conn->prepare("SELECT status_pesanan FROM transaksi WHERE id = ? AND id_user = ?");
    $cek->execute([$id_cancel, $id_user]);
    $tr = $cek->fetch();
    
    // Hanya bisa dibatalkan jika status masih pending
    if ($tr && $tr['status_pesanan'] == 'pending') {
        $upd = $conn->prepare("UPDATE transaksi SET status_pesanan = 'batal' WHERE id = ?");
        $upd->execute([$id_cancel]);
    }
    header("Location: history.php");
    exit;
}

// Ambil riwayat pesanan user
$stmt = $conn->prepare("SELECT * FROM transaksi WHERE id_user = ? ORDER BY tanggal_transaksi DESC");
$stmt->execute([$id_user]);
$riwayat = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Riwayat Pesanan - XrivaStore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <link href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-bootstrap-4/bootstrap-4.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    
    <style>
        :root { --xriva-dark: #4a7c6b; --xriva-primary: #7cb3a1; }
        body { background-color: #f4f7f6; font-family: 'Segoe UI', sans-serif; }
        .navbar-sage { background-color: var(--xriva-dark); }
        .text-sage-dark { color: var(--xriva-dark); }
        .bg-sage { background-color: var(--xriva-primary); }
        .btn-sage { background-color: var(--xriva-primary); color: white; border: none; }
        .btn-sage:hover { background-color: var(--xriva-dark); color: white; }
        .btn-outline-sage { border-color: var(--xriva-primary); color: var(--xriva-primary); }
        .btn-outline-sage:hover { background-color: var(--xriva-primary); color: white; }
        
        /* Hilangkan background putih pada icon peta */
        .leaflet-div-icon { background: transparent; border: none; }
        .tracking-map { height: 250px; border-radius: 12px; z-index: 1; border: 2px solid #e8f0ed; }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark navbar-sage sticky-top shadow-sm py-2">
        <div class="container">
            <a class="navbar-brand fw-bold fs-4" href="index.php"><i class="fas fa-leaf"></i> XrivaStore</a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center gap-3 mt-3 mt-lg-0">
                    <li class="nav-item"><a class="nav-link d-flex align-items-center" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link d-flex align-items-center" href="wishlist.php"><i class="fas fa-heart me-1"></i> Wishlist</a></li>
                    <li class="nav-item"><a class="nav-link d-flex align-items-center" href="cart.php"><i class="fas fa-shopping-cart me-1"></i> Keranjang</a></li>
                    <li class="nav-item"><a class="nav-link active fw-bold d-flex align-items-center" href="history.php"><i class="fas fa-history me-1"></i> Pesanan</a></li>
                    
                    <li class="nav-item dropdown ms-lg-2 d-flex align-items-center border-start-lg ps-lg-3 mt-2 mt-lg-0">
                        <div class="rounded-circle d-flex justify-content-center align-items-center bg-white text-sage-dark fw-bold me-2 shadow-sm" style="width: 35px; height: 35px; font-size: 1rem;">
                            <?= strtoupper(substr($_SESSION['user_nama'], 0, 1)) ?>
                        </div>
                        <a class="nav-link dropdown-toggle fw-bold text-white p-0" href="#" data-bs-toggle="dropdown" id="dropdownUser" aria-expanded="false">
                            <?= htmlspecialchars($_SESSION['user_nama']) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end mt-3 shadow border-0" aria-labelledby="dropdownUser" style="border-radius: 12px;">
                            <?php if(isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
                                <li><a class="dropdown-item py-2 fw-bold text-sage-dark" href="admin/produk.php"><i class="fas fa-user-shield me-2"></i> Dashboard Admin</a></li>
                                <li><hr class="dropdown-divider"></li>
                            <?php endif; ?>
                            <li><a class="dropdown-item py-2" href="profile.php"><i class="fas fa-user-circle text-muted me-2"></i> Profil Saya</a></li>
                            <li><a class="dropdown-item py-2" href="history.php"><i class="fas fa-clipboard-list text-muted me-2"></i> Pesanan Saya</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger py-2" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-5 pb-5">
        <div class="d-flex align-items-center mb-4">
            <i class="fas fa-history fa-2x text-sage-dark me-3"></i>
            <h3 class="fw-bold text-sage-dark m-0">Riwayat Pesanan Anda</h3>
        </div>

        <?php if(count($riwayat) > 0): ?>
            <?php foreach($riwayat as $r): ?>
            <div class="card shadow-sm border-0 mb-4" style="border-radius: 16px;">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                    <div class="text-muted small">
                        <i class="fas fa-shopping-bag me-1"></i> <?= date('d M Y, H:i', strtotime($r['tanggal_transaksi'])) ?> | ID: <span class="fw-bold">#<?= $r['id'] ?></span>
                    </div>
                    <div>
                        <?php 
                            if ($r['status_pesanan'] == 'pending') echo '<span class="badge bg-warning text-dark px-3 py-2 rounded-pill"><i class="fas fa-clock me-1"></i> Menunggu Pembayaran</span>';
                            elseif ($r['status_pesanan'] == 'diproses') echo '<span class="badge bg-info text-dark px-3 py-2 rounded-pill"><i class="fas fa-box-open me-1"></i> Sedang Diproses</span>';
                            elseif ($r['status_pesanan'] == 'dikirim') echo '<span class="badge bg-primary px-3 py-2 rounded-pill"><i class="fas fa-truck me-1"></i> Sedang Dikirim</span>';
                            elseif ($r['status_pesanan'] == 'selesai') echo '<span class="badge bg-success px-3 py-2 rounded-pill"><i class="fas fa-check me-1"></i> Selesai</span>';
                            else echo '<span class="badge bg-danger px-3 py-2 rounded-pill"><i class="fas fa-times me-1"></i> Dibatalkan</span>';
                        ?>
                    </div>
                </div>

                <div class="card-body p-4">
                    <div class="row align-items-center">
                        <div class="col-md-4 border-end">
                            <p class="text-muted small mb-1">Total Pembayaran</p>
                            <h4 class="fw-bold text-sage-dark mb-2">Rp <?= number_format($r['total_harga'], 0, ',', '.') ?></h4>
                            <span class="badge bg-light text-dark border"><i class="fas fa-wallet me-1"></i> <?= htmlspecialchars($r['metode_pembayaran']) ?></span>
                        </div>
                        
                        <div class="col-md-5 px-4">
                            <?php if($r['status_pesanan'] == 'batal'): ?>
                                <p class="m-0 text-danger fw-bold"><i class="fas fa-times-circle me-2"></i> Pesanan ini telah dibatalkan.</p>
                            <?php elseif($r['status_pesanan'] == 'dikirim'): ?>
                                <p class="m-0 text-primary fw-bold"><i class="fas fa-motorcycle me-2"></i> Pesanan sedang dalam perjalanan!</p>
                            <?php elseif($r['status_pesanan'] == 'pending'): ?>
                                <p class="m-0 text-danger fw-bold"><i class="fas fa-exclamation-circle me-2"></i> Segera selesaikan pembayaran.</p>
                            <?php else: ?>
                                <p class="m-0 text-muted"><i class="fas fa-check-circle text-success me-2"></i> Pesanan Anda tercatat di sistem.</p>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-3 text-end d-flex flex-column gap-2">
                            <button class="btn btn-outline-sage rounded-pill px-4 fw-bold w-100" data-bs-toggle="modal" data-bs-target="#modalDetail<?= $r['id'] ?>">Detail Pesanan</button>
                            
                            <?php if($r['status_pesanan'] == 'pending'): ?>
                                <button class="btn btn-outline-danger rounded-pill px-4 fw-bold w-100" onclick="confirmCancel(<?= $r['id'] ?>)">Batalkan</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="modalDetail<?= $r['id'] ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content" style="border-radius: 16px; border: none;">
                        <div class="modal-header bg-sage text-white" style="border-radius: 16px 16px 0 0;">
                            <h5 class="modal-title fw-bold"><i class="fas fa-receipt me-2"></i> Detail Transaksi #<?= $r['id'] ?></h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body p-4">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Status</span>
                                <span class="fw-bold text-uppercase"><?= htmlspecialchars($r['status_pesanan']) ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-3 pb-3 border-bottom">
                                <span class="text-muted">Metode Pembayaran</span>
                                <span class="fw-bold"><?= htmlspecialchars($r['metode_pembayaran']) ?></span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="fw-bold fs-5">Total Belanja</span>
                                <span class="fw-bold fs-5 text-sage-dark">Rp <?= number_format($r['total_harga'], 0, ',', '.') ?></span>
                            </div>

                            <?php if($r['status_pesanan'] == 'dikirim' || $r['status_pesanan'] == 'diproses'): ?>
                                <div class="mt-4 pt-4 border-top">
                                    <h6 class="fw-bold text-sage-dark mb-3"><i class="fas fa-map-marked-alt me-2"></i>Lacak Pengiriman</h6>
                                    
                                    <div id="map-<?= $r['id'] ?>" class="tracking-map" 
                                         data-lat="<?= $r['lat'] ?? '-6.265' ?>" 
                                         data-lng="<?= $r['lng'] ?? '107.015' ?>">
                                    </div>
                                    
                                    <p class="text-muted small mt-2 text-center">
                                        <i class="fas fa-info-circle me-1"></i> 
                                        <?= ($r['status_pesanan'] == 'diproses') ? 'Kurir akan segera mengambil paket Anda.' : 'Kurir sedang dalam perjalanan ke alamat Anda.' ?>
                                    </p>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="modal-footer border-0">
                            <button type="button" class="btn btn-light w-100 rounded-pill fw-bold" data-bs-dismiss="modal">Tutup</button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="text-center py-5 mt-4 bg-white shadow-sm" style="border-radius: 16px;">
                <i class="fas fa-shopping-basket fa-4x text-muted mb-3 opacity-25"></i>
                <h5 class="fw-bold text-dark">Belum ada pesanan</h5>
                <p class="text-muted mb-4">Anda belum melakukan transaksi apapun di XrivaStore.</p>
                <a href="index.php" class="btn btn-sage rounded-pill px-5 fw-bold">Mulai Belanja</a>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
    // Fungsi Cancel
    function confirmCancel(id) {
        Swal.fire({
            title: 'Batalkan Pesanan?',
            text: "Pesanan yang dibatalkan tidak bisa dikembalikan lagi.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Batalkan!'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = "history.php?cancel=" + id;
            }
        })
    }

    // Fungsi Render Peta saat Modal Terbuka
    document.addEventListener('shown.bs.modal', function (event) {
        let modal = event.target;
        let mapContainer = modal.querySelector('.tracking-map');
        
        if (mapContainer && !mapContainer.classList.contains('leaflet-container')) {
            let mapId = mapContainer.id;
            
            // Ambil koordinat tujuan dari data attribute yang kita set dari database
            let tujuanLat = parseFloat(mapContainer.getAttribute('data-lat'));
            let tujuanLng = parseFloat(mapContainer.getAttribute('data-lng'));
            
            let map = L.map(mapId).setView([-6.238270, 107.045650], 13);
            
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap'
            }).addTo(map);

            // Titik A (XrivaStore)
            let tokoPos = [-6.238270, 107.045650];
            // Titik B (Tujuan Pengiriman dari Database)
            let pembeliPos = [tujuanLat, tujuanLng];

            let storeIconHtml = '<i class="fas fa-store fa-2x text-sage-dark" style="text-shadow: 1px 1px 2px white;"></i>';
            let userIconHtml = '<i class="fas fa-map-marker-alt fa-2x text-danger" style="text-shadow: 1px 1px 2px white;"></i>';
            
            let storeIcon = L.divIcon({html: storeIconHtml, iconSize: [30, 42], iconAnchor: [15, 42]});
            let userIcon = L.divIcon({html: userIconHtml, iconSize: [30, 42], iconAnchor: [15, 42]});

            L.marker(tokoPos, {icon: storeIcon}).addTo(map).bindPopup('<b>XrivaStore</b>');
            L.marker(pembeliPos, {icon: userIcon}).addTo(map).bindPopup('<b>Lokasi Pengiriman</b>').openPopup();

            L.polyline([tokoPos, pembeliPos], {color: '#7cb3a1', weight: 4, dashArray: '8, 8'}).addTo(map);
            
            // Fix bug abu-abu pada Leaflet di dalam Modal
            setTimeout(function(){ map.invalidateSize(); }, 100);
            map.fitBounds([tokoPos, pembeliPos], {padding: [20, 20]});
        }
    });
    </script>
</body>
</html>