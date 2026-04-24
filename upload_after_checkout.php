<?php
session_start();
require_once 'backend/config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); exit;
}

if (!isset($_GET['id'])) {
    header('Location: history.php'); exit;
}

// CSRF token untuk form upload
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

$id = intval($_GET['id']);
$id_user = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT t.*, u.nama, u.no_hp, u.alamat FROM transaksi t JOIN users u ON t.id_user = u.id WHERE t.id = ? AND t.id_user = ?");
$stmt->execute([$id, $id_user]);
$trx = $stmt->fetch();
if (!$trx) { header('Location: history.php'); exit; }

// detail items
$stmt_dtl = $conn->prepare("SELECT * FROM detail_transaksi WHERE id_transaksi = ?");
$stmt_dtl->execute([$id]);
$items = $stmt_dtl->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Upload Bukti Pembayaran - Xriva Eyewear</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body style="background-color:#f4f7f6;font-family: 'Segoe UI', sans-serif;">
<nav class="navbar navbar-dark" style="background-color:#4a7c6b;">
  <div class="container"><a class="navbar-brand text-white fw-bold" href="index.php"><i class="fas fa-glasses me-2"></i>Xriva Eyewear</a></div>
</nav>

<div class="container my-5">
  <div class="row justify-content-center">
    <div class="col-md-8">
      <div class="card p-4" style="border-radius:12px;">
        <h4 class="fw-bold">Upload Bukti Pembayaran untuk Pesanan #<?= $trx['id'] ?></h4>
        <p class="text-muted small">Total: <strong>Rp <?= number_format($trx['total_harga'],0,',','.') ?></strong></p>
        <hr>
        <h6 class="fw-bold small text-muted">Rincian Barang</h6>
        <?php foreach($items as $it): ?>
          <div class="d-flex align-items-center mb-2">
            <img src="frontend/images/produk/<?= htmlspecialchars($it['gambar']) ?>" width="60" class="me-3 rounded" style="object-fit:cover;">
            <div class="flex-grow-1">
              <div class="fw-bold"><?= htmlspecialchars($it['nama_produk']) ?></div>
              <small class="text-muted"><?= $it['jumlah'] ?> x Rp <?= number_format($it['harga'],0,',','.') ?></small>
            </div>
            <div class="fw-bold">Rp <?= number_format($it['harga'] * $it['jumlah'],0,',','.') ?></div>
          </div>
        <?php endforeach; ?>

        <hr>
        <?php if(!empty($trx['bukti_bayar'])): ?>
          <p class="small text-muted">Bukti sudah di-upload:</p>
          <a href="frontend/images/bukti/<?= htmlspecialchars($trx['bukti_bayar']) ?>" target="_blank"><img src="frontend/images/bukti/<?= htmlspecialchars($trx['bukti_bayar']) ?>" style="max-width:260px; max-height:260px; object-fit:contain; border:1px solid #e9ecef;" class="rounded"></a>
          <div class="mt-3">
            <a href="history.php" class="btn btn-light">Kembali ke Pesanan Saya</a>
          </div>
        <?php else: ?>
          <form id="uploadForm" action="backend/upload_bukti.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id_transaksi" value="<?= $trx['id'] ?>">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <div class="mb-3">
              <label class="form-label small text-muted">Pilih file bukti (JPG/PNG, max 3MB)</label>
              <input type="file" id="buktiInput" name="bukti" accept="image/*" class="form-control" required>
              <div id="buktiError" class="small text-danger mt-2" style="display:none;"></div>
            </div>

            <div class="mb-3" id="previewWrap" style="display:none;">
              <p class="small text-muted mb-1">Preview:</p>
              <img id="buktiPreview" src="#" alt="preview" style="max-width:260px; max-height:260px; object-fit:contain; border:1px solid #e9ecef;" class="rounded">
            </div>

            <div class="d-flex gap-2">
              <button id="btnUpload" class="btn btn-success" type="submit">Upload Bukti Pembayaran</button>
              <a href="history.php" class="btn btn-secondary">Nanti</a>
            </div>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

</body>
</html>
<script>
document.addEventListener('DOMContentLoaded', function(){
  const input = document.getElementById('buktiInput');
  const previewWrap = document.getElementById('previewWrap');
  const previewImg = document.getElementById('buktiPreview');
  const errorEl = document.getElementById('buktiError');
  const btn = document.getElementById('btnUpload');
  const MAX = 3 * 1024 * 1024; // 3MB

  if (!input) return;
  input.addEventListener('change', function(e){
    const f = this.files[0];
    if (!f) { previewWrap.style.display='none'; errorEl.style.display='none'; btn.disabled=false; return; }
    if (f.size > MAX) {
      errorEl.innerText = 'Ukuran file terlalu besar (maks 3MB).'; errorEl.style.display='block'; previewWrap.style.display='none'; btn.disabled = true; return;
    }
    // preview
    const reader = new FileReader();
    reader.onload = function(ev){ previewImg.src = ev.target.result; previewWrap.style.display='block'; errorEl.style.display='none'; btn.disabled=false; };
    reader.readAsDataURL(f);
  });
});
</script>
