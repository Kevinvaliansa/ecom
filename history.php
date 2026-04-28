<?php
session_start();
require_once 'backend/config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); exit;
}
$id_user = $_SESSION['user_id'];

// CSRF token untuk form upload
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

if (isset($_GET['cancel'])) {
    $id_cancel = $_GET['cancel'];
    $conn->prepare("UPDATE transaksi SET status_pesanan = 'batal' WHERE id = ? AND id_user = ? AND status_pesanan = 'pending'")->execute([$id_cancel, $id_user]);
    $_SESSION['toast'] = ['type' => 'success', 'message' => 'Pesanan berhasil dibatalkan.'];
    header("Location: history.php"); exit;
}

$status_filter = $_GET['status'] ?? 'semua';

if ($status_filter !== 'semua') {
    $stmt = $conn->prepare("SELECT * FROM transaksi WHERE id_user = ? AND status_pesanan = ? ORDER BY tanggal_transaksi DESC");
    $stmt->execute([$id_user, $status_filter]);
} else {
    $stmt = $conn->prepare("SELECT * FROM transaksi WHERE id_user = ? ORDER BY tanggal_transaksi DESC");
    $stmt->execute([$id_user]);
}
$riwayat = $stmt->fetchAll();

$counts_raw = $conn->prepare("SELECT status_pesanan, COUNT(*) FROM transaksi WHERE id_user = ? GROUP BY status_pesanan");
$counts_raw->execute([$id_user]);
$counts_raw = $counts_raw->fetchAll(PDO::FETCH_KEY_PAIR);
$counts = [
    'semua' => array_sum($counts_raw),
    'pending' => $counts_raw['pending'] ?? 0,
    'diproses' => $counts_raw['diproses'] ?? 0,
    'dikirim' => $counts_raw['dikirim'] ?? 0,
    'selesai' => $counts_raw['selesai'] ?? 0,
    'batal' => $counts_raw['batal'] ?? 0
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Pesanan - XrivaStore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="frontend/css/style.css?v=<?= time() ?>">
    <style>
        body { background-color: #f4f7f6; font-family: 'Segoe UI', sans-serif; }
    </style>
</head>
<body>

<?php include 'frontend/includes/navbar.php'; ?>

<div class="container my-5 pb-5">
    <h3 class="fw-bold mb-4 text-dark"><i class="fas fa-history me-2" style="color: #4a7c6b;"></i>Riwayat Pesanan Saya</h3>

    <div class="d-flex flex-wrap gap-2 mb-4 pb-2 border-bottom">
        <?php
        $status_tabs = [
            'semua'   => ['label'=>'Semua',     'icon'=>'fa-list'],
            'pending' => ['label'=>'Menunggu',  'icon'=>'fa-clock'],
            'diproses'=> ['label'=>'Diproses',  'icon'=>'fa-cog'],
            'dikirim' => ['label'=>'Dikirim',   'icon'=>'fa-truck'],
            'selesai' => ['label'=>'Selesai',   'icon'=>'fa-check-circle'],
            'batal'   => ['label'=>'Dibatalkan','icon'=>'fa-times-circle'],
        ];
        foreach ($status_tabs as $key => $tab): ?>
        <a href="?status=<?= $key ?>" 
           class="btn btn-sm rounded-pill px-3 py-2 fw-bold <?= $status_filter == $key ? 'btn-sage' : 'btn-outline-secondary' ?>">
            <i class="fas <?= $tab['icon'] ?> me-1"></i> <?= $tab['label'] ?>
            <span class="badge ms-1 rounded-pill <?= $status_filter == $key ? 'bg-white text-sage-dark' : 'bg-secondary text-white' ?>"><?= $counts[$key] ?></span>
        </a>
        <?php endforeach; ?>
    </div>

    <?php if(count($riwayat) == 0): ?>
        <div class="text-center py-5 bg-white shadow-sm" style="border-radius: 16px;">
            <i class="fas fa-box-open fa-4x text-sage mb-3 opacity-50"></i>
            <h5 class="text-muted fw-bold">Belum ada pesanan <?= $status_filter !== 'semua' ? 'di status ini' : 'nih' ?>.</h5>
            <p class="text-muted"><?= $status_filter !== 'semua' ? 'Coba cek di tab status lainnya.' : 'Yuk mulai belanja kacamata favoritmu sekarang!' ?></p>
            <a href="index.php" class="btn btn-sage px-4 mt-2 rounded-pill fw-bold">Mulai Belanja</a>
        </div>
    <?php endif; ?>

    <?php foreach($riwayat as $r): ?>
    <div class="card shadow-sm border-0 mb-4" style="border-radius: 16px;">
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
            <div class="text-muted small">
                <i class="fas fa-shopping-bag me-1"></i> <?= date('d M Y, H:i', strtotime($r['tanggal_transaksi'])) ?> | ID: <span class="fw-bold">#<?= $r['id'] ?></span>
            </div>
            <div>
                <?php 
                    $s = $r['status_pesanan'];
                    if($s == 'pending') echo '<span class="badge bg-warning text-dark px-3 py-2 rounded-pill">Menunggu Pembayaran</span>';
                    elseif($s == 'diproses') echo '<span class="badge bg-info text-dark px-3 py-2 rounded-pill">Sedang Diproses</span>';
                    elseif($s == 'dikirim') echo '<span class="badge bg-primary px-3 py-2 rounded-pill">Sedang Dikirim</span>';
                    elseif($s == 'selesai') echo '<span class="badge bg-success px-3 py-2 rounded-pill">Selesai</span>';
                    else echo '<span class="badge bg-danger px-3 py-2 rounded-pill">Dibatalkan</span>';
                ?>
            </div>
        </div>

        <div class="card-body p-4">
            <div class="row align-items-center">
                <div class="col-md-5 border-end">
                    <p class="text-muted small mb-1">Total Pembayaran</p>
                    <h4 class="fw-bold mb-2 text-sage-dark">Rp <?= number_format($r['total_harga'], 0, ',', '.') ?></h4>
                    <span class="badge bg-light text-dark border"><i class="fas fa-wallet me-1"></i> <?= htmlspecialchars($r['metode_pembayaran']) ?></span>
                </div>
                
                <div class="col-md-7 text-end d-flex gap-2 justify-content-end mt-3 mt-md-0">
                    <button class="btn btn-outline-secondary rounded-pill px-4 fw-bold" data-bs-toggle="modal" data-bs-target="#modalDetail<?= $r['id'] ?>">Detail Pesanan</button>
                    <?php if($r['status_pesanan'] == 'pending'): ?>
                        <button class="btn btn-outline-danger rounded-pill px-4 fw-bold" onclick="confirmCancel(<?= $r['id'] ?>)">Batalkan</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalDetail<?= $r['id'] ?>" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0" style="border-radius: 16px;">
                <div class="modal-header border-0 pb-0" style="background: linear-gradient(135deg, #4a7c6b 0%, #3a6356 100%); border-radius: 18px 18px 0 0;">
                    <h5 class="modal-title text-white fw-bold" id="detailModalLabel<?= $r['id'] ?>">
                        <i class="fas fa-box-open me-2"></i> Detail Pesanan #<?= $r['id'] ?>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
                        <div>
                            <div class="small text-muted mb-1">Tanggal Transaksi</div>
                            <div class="fw-bold text-dark small"><?= date('d F Y, H:i', strtotime($r['tanggal_transaksi'])) ?></div>
                        </div>
                        <div class="text-end">
                            <div class="small text-muted mb-1">Status</div>
                            <span class="badge rounded-pill <?= $badgeClass ?>"><?= $labelMap[$r['status_pesanan']] ?? $r['status_pesanan'] ?></span>
                        </div>
                    </div>

                    <h6 class="fw-bold mb-3 small text-muted text-uppercase tracking-wider"><i class="fas fa-list me-1"></i> Daftar Barang</h6>
                    
                    <?php
                    $stmt_dtl = $conn->prepare("SELECT * FROM detail_transaksi WHERE id_transaksi = ?");
                    $stmt_dtl->execute([$r['id']]);
                    $items = $stmt_dtl->fetchAll();
                    
                    if(count($items) > 0):
                        foreach($items as $item): ?>
                        <div class="bg-light p-3 rounded-4 mb-3 border-0">
                            <div class="d-flex align-items-center">
                                <img src="frontend/images/produk/<?= htmlspecialchars($item['gambar']) ?>" 
                                     width="60" height="60" class="rounded-3 border me-3" style="object-fit: contain; background:white;">
                                <div class="flex-grow-1">
                                    <div class="fw-bold text-dark small mb-0"><?= htmlspecialchars($item['nama_produk']) ?></div>
                                    <?php if(!empty($item['varian'])): ?>
                                        <div class="badge bg-white text-muted border fw-normal" style="font-size:0.65rem;"><?= htmlspecialchars($item['varian']) ?></div>
                                    <?php endif; ?>
                                    <div class="text-muted small"><?= $item['jumlah'] ?> pcs x Rp <?= number_format($item['harga'], 0, ',', '.') ?></div>
                                </div>
                                <div class="fw-bold text-dark small">
                                    Rp <?= number_format($item['harga'] * $item['jumlah'], 0, ',', '.') ?>
                                </div>
                            </div>
                            
                            <?php if($r['status_pesanan'] === 'selesai'): 
                                $stmtRating = $conn->prepare("SELECT * FROM ratings WHERE id_user = ? AND id_produk = ? LIMIT 1");
                                $stmtRating->execute([$id_user, $item['id_produk']]);
                                $userRating = $stmtRating->fetch();
                            ?>
                            <div class="mt-3 pt-3 border-top">
                                <form action="backend/submit_rating.php" method="POST" class="row g-2">
                                    <input type="hidden" name="id_produk" value="<?= $item['id_produk'] ?>">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <div class="col-4">
                                        <select name="rating" class="form-select form-select-sm rounded-pill">
                                            <?php for($i=5;$i>=1;$i--): ?>
                                                <option value="<?= $i ?>" <?= (!empty($userRating) && $userRating['rating'] == $i) ? 'selected' : '' ?>><?= $i ?> ★</option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                    <div class="col-6">
                                        <input type="text" name="review" class="form-control form-control-sm rounded-pill" placeholder="Ulasan..." value="<?= htmlspecialchars($userRating['review'] ?? '') ?>">
                                    </div>
                                    <div class="col-2">
                                        <button type="submit" class="btn btn-sm btn-sage w-100 rounded-circle"><i class="fas fa-paper-plane"></i></button>
                                    </div>
                                </form>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; 
                    else: ?>
                        <div class="alert alert-warning text-center small rounded-4">Data tidak tersedia.</div>
                    <?php endif; ?>

                    <div class="mt-4 p-3 rounded-4" style="background-color: #f0f4f2;">
                        <div class="d-flex justify-content-between small text-muted mb-1">
                            <span>Subtotal Barang</span>
                            <span>Rp <?= number_format($r['total_harga'] - $r['biaya_ongkir'], 0, ',', '.') ?></span>
                        </div>
                        <div class="d-flex justify-content-between small text-muted mb-2">
                            <span>Ongkos Kirim (<?= htmlspecialchars($r['ekspedisi'] ?? '-') ?>)</span>
                            <span>Rp <?= number_format($r['biaya_ongkir'], 0, ',', '.') ?></span>
                        </div>
                        <div class="d-flex justify-content-between border-top pt-2 mt-2">
                            <span class="fw-bold text-dark">Total Tagihan</span>
                            <span class="fw-bold fs-4 text-sage-dark">Rp <?= number_format($r['total_harga'], 0, ',', '.') ?></span>
                        </div>
                    </div>

                    <?php if(!empty($r['bukti_bayar'])): ?>
                        <div class="mt-4 text-center">
                            <div class="small fw-bold text-muted mb-2 text-uppercase tracking-wider">Bukti Pembayaran</div>
                            <img src="frontend/images/bukti/<?= htmlspecialchars($r['bukti_bayar']) ?>" class="rounded-4 shadow-sm" style="max-width:100%; max-height:250px; object-fit:contain; border:1px solid #e9ecef;">
                        </div>
                    <?php elseif($r['status_pesanan'] == 'pending'): ?>
                        <div class="mt-4 pt-3 border-top">
                            <h6 class="fw-bold mb-3 small text-muted text-uppercase tracking-wider">Upload Bukti Transfer</h6>
                            <form action="backend/upload_bukti.php" method="POST" enctype="multipart/form-data" class="bg-white p-3 rounded-4 border shadow-sm">
                                <input type="hidden" name="id_transaksi" value="<?= $r['id'] ?>">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <div class="mb-3">
                                    <input type="file" name="bukti" class="form-control form-control-sm rounded-pill" required>
                                </div>
                                <button type="submit" class="btn btn-sage w-100 rounded-pill fw-bold py-2 shadow-sm">
                                    <i class="fas fa-upload me-2"></i> Kirim Bukti Sekarang
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light w-100 rounded-pill fw-bold py-2" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php if(isset($_SESSION['upload_msg'])): 
    $m = $_SESSION['upload_msg']; unset($_SESSION['upload_msg']);
?>
<script>
    Swal.fire({
        icon: '<?= $m['type'] === 'success' ? 'success' : 'error' ?>',
        title: '<?= $m['type'] === 'success' ? 'Sukses' : 'Gagal' ?>',
        text: '<?= addslashes($m['text']) ?>',
        confirmButtonColor: '<?= $m['type'] === 'success' ? '#4a7c6b' : '#d33' ?>'
    });
</script>
<?php endif; ?>
<script>
    function confirmCancel(id) {
        Swal.fire({
            title: 'Batalkan Pesanan?',
            text: "Pesanan ini akan langsung dibatalkan.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Batalkan!'
        }).then((result) => {
            if (result.isConfirmed) { window.location.href = "history.php?cancel=" + id; }
        })
    }
</script>
<script>
document.addEventListener('DOMContentLoaded', function(){
    const MAX = 3 * 1024 * 1024; // 3MB
    document.querySelectorAll('.bukti-input').forEach(function(inp){
        const trx = inp.dataset.trx;
        const err = document.getElementById('buktiError'+trx);
        const previewWrap = document.getElementById('previewWrap'+trx);
        const preview = document.getElementById('buktiPreview'+trx);
        const btn = document.getElementById('btnUpload'+trx);

        inp.addEventListener('change', function(){
            const f = this.files[0];
            if(!f){ if(err) err.style.display='none'; if(previewWrap) previewWrap.style.display='none'; if(btn) btn.disabled=false; return; }
            if (f.size > MAX) {
                if(err){ err.innerText = 'Ukuran file terlalu besar (maks 3MB).'; err.style.display='block'; }
                if(previewWrap) previewWrap.style.display='none'; if(btn) btn.disabled = true; return;
            }
            // preview
            const reader = new FileReader();
            reader.onload = function(e){ if(preview){ preview.src = e.target.result; previewWrap.style.display='block'; } if(err) err.style.display='none'; if(btn) btn.disabled=false; };
            reader.readAsDataURL(f);
        });
    });
});
</script>
</body>
</html>