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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Bukti Pembayaran - XrivaStore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="frontend/css/style.css?v=<?= time() ?>">
    <style>
        .card-custom { border-radius: 20px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.08); }
        .upload-area {
            border: 2px dashed #dee2e6;
            border-radius: 16px;
            padding: 40px 20px;
            text-align: center;
            cursor: pointer;
            transition: 0.3s;
            background: #f8f9fa;
        }
        .upload-area:hover, .upload-area.dragover {
            border-color: var(--xriva-primary);
            background: rgba(74, 124, 107, 0.05);
        }
        .step-indicator { display: flex; justify-content: center; margin-bottom: 30px; }
        .step { display: flex; flex-direction: column; align-items: center; width: 100px; position: relative; }
        .step-circle { width: 35px; height: 35px; border-radius: 50%; background: #e9ecef; display: flex; align-items: center; justify-content: center; font-weight: bold; margin-bottom: 5px; z-index: 1; }
        .step.completed .step-circle { background: #198754; color: white; }
        .step.active .step-circle { background: var(--xriva-primary); color: white; }
        .step:not(:last-child)::after { content: ''; position: absolute; top: 17px; left: 50px; width: 100%; height: 2px; background: #e9ecef; z-index: 0; }
        .step.completed:not(:last-child)::after { background: #198754; }
        .step-label { font-size: 0.75rem; font-weight: bold; color: #6c757d; }
    </style>
</head>
<body class="bg-light">

<?php include 'frontend/includes/navbar.php'; ?>

<div class="container my-5 pb-5">
    <!-- Progress Stepper -->
    <div class="step-indicator">
        <div class="step completed">
            <div class="step-circle"><i class="fas fa-check"></i></div>
            <div class="step-label">Pengiriman</div>
        </div>
        <div class="step active">
            <div class="step-circle">2</div>
            <div class="step-label">Pembayaran</div>
        </div>
        <div class="step">
            <div class="step-circle">3</div>
            <div class="step-label">Selesai</div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="row g-4">
                <!-- Left: Bank Info -->
                <div class="col-md-5">
                    <div class="card card-custom p-4 mb-4">
                        <h5 class="fw-bold mb-4">💳 Intruksi Bayar</h5>
                        <div class="alert bg-light border-0 rounded-4 p-3 mb-4">
                            <div class="small fw-bold text-muted mb-2">Silakan transfer ke:</div>
                            <div class="mb-3">
                                <div class="small text-muted">Metode:</div>
                                <div class="fw-bold text-dark"><?= htmlspecialchars($trx['metode_pembayaran']) ?></div>
                            </div>
                            <?php 
                            $metode = $trx['metode_pembayaran'];
                            if (strpos($metode, 'BCA') !== false) {
                                $bank_display = 'BCA - Xriva Store';
                                $acc_display = '1234-5678-90';
                            } elseif (strpos($metode, 'Mandiri') !== false) {
                                $bank_display = 'Mandiri - Xriva Store';
                                $acc_display = '0987-6543-21';
                            } else {
                                $bank_display = 'DANA / OVO';
                                $acc_display = '0812-3456-7890';
                            }
                            ?>
                            <div class="d-flex justify-content-between align-items-center bg-white p-2 rounded-3 border">
                                <div>
                                    <div class="small text-muted" style="font-size: 0.7rem; text-uppercase;"><?= $bank_display ?></div>
                                    <div class="fw-bold text-sage-dark" id="numRek"><?= $acc_display ?></div>
                                </div>
                                <button class="btn btn-sm btn-sage rounded-pill px-3" onclick="copyRek()">Salin</button>
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="small text-muted mb-1">Total yang harus dibayar:</div>
                            <h3 class="fw-bold text-sage-dark">Rp <?= number_format($trx['total_harga'], 0, ',', '.') ?></h3>
                            <div class="small text-danger"><i class="fas fa-info-circle me-1"></i> Mohon transfer sesuai nominal di atas.</div>
                        </div>

                        <div class="small text-muted">
                            <p class="mb-1 fw-bold text-dark">Langkah Pembayaran:</p>
                            <ol class="ps-3">
                                <li>Lakukan transfer sesuai bank terpilih.</li>
                                <li>Screenshot atau foto bukti transfer.</li>
                                <li>Upload foto bukti pada panel di samping.</li>
                                <li>Admin akan memverifikasi dalam 1x24 jam.</li>
                            </ol>
                        </div>
                    </div>
                </div>

                <!-- Right: Upload Area -->
                <div class="col-md-7">
                    <div class="card card-custom p-4">
                        <h5 class="fw-bold mb-4">📸 Upload Bukti Transfer</h5>
                        
                        <?php if(!empty($trx['bukti_bayar'])): ?>
                            <div class="text-center py-4">
                                <div class="alert alert-success rounded-4 mb-4">
                                    <i class="fas fa-check-circle me-2"></i> Bukti pembayaran telah diunggah!
                                </div>
                                <img src="frontend/images/bukti/<?= htmlspecialchars($trx['bukti_bayar']) ?>" 
                                     class="rounded-4 shadow-sm img-fluid mb-4" 
                                     style="max-height: 350px; object-fit: contain;">
                                <div class="d-grid gap-2">
                                    <a href="history.php" class="btn btn-sage rounded-pill py-3 fw-bold">Lihat Riwayat Pesanan</a>
                                </div>
                            </div>
                        <?php else: ?>
                            <form id="uploadForm" action="backend/upload_bukti.php" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="id_transaksi" value="<?= $trx['id'] ?>">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                
                                <div class="upload-area mb-4" id="dropArea" onclick="document.getElementById('buktiInput').click()">
                                    <div id="uploadPlaceholder">
                                        <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                                        <h6 class="fw-bold text-dark mb-1">Klik atau seret file di sini</h6>
                                        <p class="small text-muted mb-0">JPG, PNG, atau WEBP (Maks 3MB)</p>
                                    </div>
                                    <div id="previewWrap" style="display:none;">
                                        <img id="buktiPreview" src="#" class="img-fluid rounded-3 mb-3" style="max-height: 300px;">
                                        <p class="small text-success fw-bold"><i class="fas fa-check-circle me-1"></i> File terpilih, siap upload!</p>
                                    </div>
                                    <input type="file" id="buktiInput" name="bukti" accept="image/*" class="d-none" required>
                                </div>

                                <div id="buktiError" class="alert alert-danger small mb-4" style="display:none;"></div>

                                <div class="d-grid gap-2">
                                    <button type="submit" id="btnUpload" class="btn btn-sage rounded-pill py-3 fw-bold shadow-sm">
                                        <i class="fas fa-upload me-2"></i> Kirim Bukti Pembayaran
                                    </button>
                                    <a href="history.php" class="btn btn-link text-muted text-decoration-none small">Nanti Saja</a>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function copyRek() {
        const num = document.getElementById('numRek').innerText;
        navigator.clipboard.writeText(num).then(() => {
            alert('Nomor rekening berhasil disalin!');
        });
    }

    const dropArea = document.getElementById('dropArea');
    const input = document.getElementById('buktiInput');
    const previewWrap = document.getElementById('previewWrap');
    const placeholder = document.getElementById('uploadPlaceholder');
    const previewImg = document.getElementById('buktiPreview');
    const errorEl = document.getElementById('buktiError');
    const btn = document.getElementById('btnUpload');
    const MAX = 3 * 1024 * 1024;

    if (dropArea) {
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropArea.addEventListener(eventName, e => { e.preventDefault(); e.stopPropagation(); }, false);
        });

        ['dragenter', 'dragover'].forEach(eventName => {
            dropArea.addEventListener(eventName, () => dropArea.classList.add('dragover'), false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropArea.addEventListener(eventName, () => dropArea.classList.remove('dragover'), false);
        });

        dropArea.addEventListener('drop', e => {
            const dt = e.dataTransfer;
            input.files = dt.files;
            handleFiles(dt.files[0]);
        }, false);

        input.addEventListener('change', function() {
            handleFiles(this.files[0]);
        });
    }

    function handleFiles(file) {
        if (!file) return;
        if (file.size > MAX) {
            errorEl.innerText = 'Ukuran file terlalu besar (maks 3MB).';
            errorEl.style.display = 'block';
            previewWrap.style.display = 'none';
            placeholder.style.display = 'block';
            btn.disabled = true;
            return;
        }

        const reader = new FileReader();
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            previewWrap.style.display = 'block';
            placeholder.style.display = 'none';
            errorEl.style.display = 'none';
            btn.disabled = false;
        };
        reader.readAsDataURL(file);
    }
</script>
</body>
</html>
