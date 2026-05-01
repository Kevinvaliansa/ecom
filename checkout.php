<?php
session_start();
require_once 'backend/config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$id_user = $_SESSION['user_id'];

// Ambil data user
$stmt_user = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt_user->execute([$id_user]);
$user = $stmt_user->fetch();

$cart_items  = [];
$subtotal = 0;
$ids_selected = [];
$is_direct = isset($_POST['is_direct']) && $_POST['is_direct'] == '1';

// ==============================================================
// PROSES BUAT PESANAN
// ==============================================================
if (isset($_POST['buat_pesanan'])) {
    $nama_penerima = htmlspecialchars(trim($_POST['nama_penerima']));
    $no_hp         = htmlspecialchars(trim($_POST['no_hp']));
    $alamat        = htmlspecialchars(trim($_POST['alamat_pengiriman']));
    $metode        = htmlspecialchars(trim($_POST['metode_pembayaran']));
    $ekspedisi     = htmlspecialchars(trim($_POST['ekspedisi_pilih']));
    $ongkir        = (float)$_POST['ongkir_pilih'];
    $status        = ($metode == 'COD') ? 'diproses' : 'pending';

    try {
        $items_to_process = [];

        if ($is_direct) {
            $pid = (int)$_POST['direct_pid'];
            $pqty = (int)$_POST['direct_qty'];
            $pvarian = $_POST['direct_varian'];
            
            $stmt_p = $conn->prepare("SELECT id as id_produk, nama_produk, harga, gambar, stok FROM produk WHERE id = ?");
            $stmt_p->execute([$pid]);
            $prod = $stmt_p->fetch();
            
            if (!$prod) throw new Exception("Produk tidak ditemukan.");
            $prod['qty'] = $pqty;
            $prod['varian'] = $pvarian;
            $items_to_process[] = $prod;
        } else {
            if (empty($_POST['id_carts'])) {
                throw new Exception("Keranjang kosong atau data tidak valid.");
            }
            $carts = array_filter(array_map('intval', explode(',', $_POST['id_carts'])));
            if (empty($carts)) throw new Exception("Data cart tidak valid.");

            $in = str_repeat('?,', count($carts) - 1) . '?';
            $stmt_items = $conn->prepare(
                "SELECT c.qty, c.varian, p.id as id_produk, p.nama_produk, p.harga, p.gambar, p.stok
                 FROM cart c JOIN produk p ON c.id_produk = p.id
                 WHERE c.id IN ($in) AND c.id_user = ?"
            );
            $params = array_merge($carts, [$id_user]);
            $stmt_items->execute($params);
            $items_to_process = $stmt_items->fetchAll();
        }

        if (empty($items_to_process)) {
            throw new Exception("Item tidak ditemukan atau bukan milik Anda.");
        }

        // Validasi stok & hitung total dari DB
        $total_items_price = 0;
        foreach ($items_to_process as $it) {
            if ($it['qty'] > $it['stok']) {
                throw new Exception("Stok produk '{$it['nama_produk']}' tidak mencukupi.");
            }
            $total_items_price += $it['harga'] * $it['qty'];
        }

        $final_total = $total_items_price + $ongkir;

        $conn->beginTransaction();

        // 1. Simpan transaksi utama
        $insert_trx = $conn->prepare(
            "INSERT INTO transaksi (id_user, total_harga, biaya_ongkir, ekspedisi, metode_pembayaran, status_pesanan, tanggal_transaksi)
             VALUES (?, ?, ?, ?, ?, ?, NOW())"
        );
        $insert_trx->execute([$id_user, $final_total, $ongkir, $ekspedisi, $metode, $status]);
        $id_transaksi = $conn->lastInsertId();

        foreach ($items_to_process as $it) {
            // 2. Simpan detail transaksi
            $conn->prepare(
                "INSERT INTO detail_transaksi (id_transaksi, id_produk, nama_produk, harga, jumlah, gambar, varian)
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            )->execute([$id_transaksi, $it['id_produk'], $it['nama_produk'], $it['harga'], $it['qty'], $it['gambar'], $it['varian']]);

            // 3. Kurangi stok
            $conn->prepare("UPDATE produk SET stok = stok - ? WHERE id = ?")->execute([$it['qty'], $it['id_produk']]);
        }

        // 4. Hapus dari keranjang
        if (!$is_direct && !empty($carts)) {
            $in_del = str_repeat('?,', count($carts) - 1) . '?';
            $conn->prepare("DELETE FROM cart WHERE id IN ($in_del)")->execute($carts);
        }

        $conn->commit();

        if ($metode == 'COD') {
            $_SESSION['upload_msg'] = ['type' => 'success', 'text' => 'Pesanan kamu telah berhasil dibuat.'];
            header("Location: checkout_success.php?id=" . $id_transaksi);
            exit;
        } else {
            header("Location: upload_after_checkout.php?id=" . $id_transaksi);
            exit;
        }

    } catch (Exception $e) {
        if ($conn->inTransaction()) $conn->rollBack();
        $error_msg = addslashes($e->getMessage());
        echo "<!DOCTYPE html><html><head>
                <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
              </head>
              <body style='background-color:#f4f7f6;'>
              <script>
                Swal.fire({
                    title: 'Oops, Gagal Checkout!',
                    text: '{$error_msg}',
                    icon: 'error',
                    confirmButtonColor: '#d33',
                    confirmButtonText: 'Kembali'
                }).then(() => { window.location.href = 'cart.php'; });
              </script></body></html>";
        exit;
    }

} else {
    // Tampilkan halaman checkout
    if (!empty($_POST['selected_items'])) {
        $selected = array_map('intval', $_POST['selected_items']);
        $in       = str_repeat('?,', count($selected) - 1) . '?';
        $params   = array_merge($selected, [$id_user]);

        $stmt = $conn->prepare(
            "SELECT c.id as id_cart, c.qty, c.varian, p.id as id_produk, p.nama_produk, p.harga, p.gambar
             FROM cart c JOIN produk p ON c.id_produk = p.id
             WHERE c.id IN ($in) AND c.id_user = ?"
        );
        $stmt->execute($params);
        $cart_items = $stmt->fetchAll();

        foreach ($cart_items as $item) {
            $subtotal  += ($item['harga'] * $item['qty']);
            $ids_selected[] = $item['id_cart'];
        }
    } elseif (isset($_POST['direct_buy_id'])) {
        $pid = (int)$_POST['direct_buy_id'];
        $pqty = (int)$_POST['direct_buy_qty'];
        $pvarian = $_POST['varian'] ?? '';
        
        $stmt_p = $conn->prepare("SELECT id as id_produk, nama_produk, harga, gambar, stok FROM produk WHERE id = ?");
        $stmt_p->execute([$pid]);
        $prod = $stmt_p->fetch();
        
        if ($prod) {
            $prod['qty'] = $pqty;
            $prod['varian'] = $pvarian;
            $cart_items[] = $prod;
            $subtotal = $prod['harga'] * $pqty;
            $is_direct = true;
        } else {
            header("Location: index.php"); exit;
        }
    } else {
        header("Location: cart.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - XrivaStore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="frontend/css/style.css?v=<?= time() ?>">
    <style>
        .card-custom { border-radius: 16px; border: none; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .address-option { cursor: pointer; transition: 0.3s; border: 2px solid transparent; }
        .address-option.active { border-color: var(--xriva-primary); background-color: rgba(74, 124, 107, 0.05); }
        .step-indicator { display: flex; justify-content: center; margin-bottom: 30px; }
        .step { display: flex; flex-direction: column; align-items: center; width: 100px; position: relative; }
        .step-circle { width: 35px; height: 35px; border-radius: 50%; background: #e9ecef; display: flex; align-items: center; justify-content: center; font-weight: bold; margin-bottom: 5px; z-index: 1; }
        .step.active .step-circle { background: var(--xriva-primary); color: white; }
        .step:not(:last-child)::after { content: ''; position: absolute; top: 17px; left: 50px; width: 100%; height: 2px; background: #e9ecef; z-index: 0; }
        .step-label { font-size: 0.75rem; font-weight: bold; color: #6c757d; }
    </style>
</head>
<body class="bg-light">

<?php include 'frontend/includes/navbar.php'; ?>

<div class="container my-5 pb-5">
    <div class="step-indicator">
        <div class="step active">
            <div class="step-circle">1</div>
            <div class="step-label">Pengiriman</div>
        </div>
        <div class="step">
            <div class="step-circle">2</div>
            <div class="step-label">Pembayaran</div>
        </div>
        <div class="step">
            <div class="step-circle">3</div>
            <div class="step-label">Selesai</div>
        </div>
    </div>

    <form method="POST" id="checkoutForm">
        <div class="row g-4">
            <div class="col-lg-7">
                <!-- 1. Alamat Pengiriman -->
                <div class="card card-custom p-4 mb-4">
                    <h5 class="fw-bold mb-4 border-bottom pb-2">
                        <i class="fas fa-map-marker-alt me-2 text-sage-dark"></i> Alamat Pengiriman
                    </h5>
                    
                    <div class="row g-3 mb-4">
                        <div class="col-6">
                            <div class="card p-3 address-option active" id="btnUseCurrent">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-home fa-lg me-3 text-sage-dark"></i>
                                    <div>
                                        <div class="fw-bold small">Alamat Profil</div>
                                        <div class="text-muted small">Kirim ke alamat utama</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="card p-3 address-option" id="btnUseNew">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-map-pin fa-lg me-3 text-secondary"></i>
                                    <div>
                                        <div class="fw-bold small">Alamat Baru</div>
                                        <div class="text-muted small">Gunakan alamat lain</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="addressInputs">
                        <div class="mb-3">
                            <label class="form-label small text-muted fw-bold">Nama Penerima</label>
                            <input type="text" name="nama_penerima" id="inpNama" class="form-control" value="<?= htmlspecialchars($user['nama']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small text-muted fw-bold">No. HP</label>
                            <input type="text" name="no_hp" id="inpHp" class="form-control" value="<?= htmlspecialchars($user['no_hp'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small text-muted fw-bold">Alamat Lengkap</label>
                            <textarea name="alamat_pengiriman" id="inpAlamat" class="form-control" rows="3" required><?= htmlspecialchars($user['alamat'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- 2. Pilih Pengiriman -->
                <div class="card card-custom p-4 mb-4">
                    <h5 class="fw-bold mb-4 border-bottom pb-2">
                        <i class="fas fa-truck me-2 text-sage-dark"></i> Opsi Pengiriman
                    </h5>
                    <div class="row g-3" id="shippingCards">
                        <div class="col-md-4">
                            <div class="card p-3 shipping-card h-100" onclick="selectShipping(this, 'JNE Reguler', 15000)">
                                <div class="text-center">
                                    <i class="fas fa-box fa-2x mb-2 text-muted"></i>
                                    <div class="fw-bold small">JNE Reguler</div>
                                    <div class="text-muted" style="font-size: 0.7rem;">3-5 Hari Kerja</div>
                                    <div class="fw-bold text-sage-dark mt-2">Rp 15.000</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card p-3 shipping-card h-100" onclick="selectShipping(this, 'JNE Express', 30000)">
                                <div class="text-center">
                                    <i class="fas fa-bolt fa-2x mb-2 text-muted"></i>
                                    <div class="fw-bold small">JNE Express</div>
                                    <div class="text-muted" style="font-size: 0.7rem;">1-2 Hari Kerja</div>
                                    <div class="fw-bold text-sage-dark mt-2">Rp 30.000</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card p-3 shipping-card h-100" onclick="selectShipping(this, 'Ambil di Toko', 0)">
                                <div class="text-center">
                                    <i class="fas fa-store fa-2x mb-2 text-muted"></i>
                                    <div class="fw-bold small">Ambil di Toko</div>
                                    <div class="text-muted" style="font-size: 0.7rem;">Gratis</div>
                                    <div class="fw-bold text-sage-dark mt-2">Rp 0</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="ekspedisi_pilih" id="ekspedisiValue" required>
                    <input type="hidden" name="ongkir_pilih" id="ongkirValue" value="0">
                </div>

                <style>
                    .shipping-card { cursor: pointer; transition: 0.3s; border: 2px solid #eee; border-radius: 12px; }
                    .shipping-card:hover { border-color: var(--xriva-primary); transform: translateY(-3px); }
                    .shipping-card.active { border-color: var(--xriva-primary); background: rgba(74, 124, 107, 0.05); }
                    .shipping-card.active i { color: var(--xriva-primary) !important; }
                </style>

                <!-- 3. Ringkasan Pesanan -->
                <div class="card card-custom p-4">
                    <h5 class="fw-bold mb-4 border-bottom pb-2">
                        <i class="fas fa-shopping-bag me-2 text-sage-dark"></i> Ringkasan Pesanan
                    </h5>
                    <?php foreach ($cart_items as $c): ?>
                    <div class="d-flex align-items-center mb-3 pb-3 border-bottom">
                        <img src="frontend/images/produk/<?= htmlspecialchars($c['gambar']) ?>"
                             width="65" height="65" class="rounded border me-3"
                             style="object-fit: contain; background:#f8f9fa;">
                        <div class="flex-grow-1">
                            <h6 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($c['nama_produk']) ?></h6>
                            <?php if (!empty($c['varian'])): ?>
                                <span class="badge bg-light text-muted border fw-normal mb-1">Varian: <?= htmlspecialchars($c['varian']) ?></span><br>
                            <?php endif; ?>
                            <small class="text-muted"><?= $c['qty'] ?> x Rp <?= number_format($c['harga'], 0, ',', '.') ?></small>
                        </div>
                        <div class="fw-bold text-dark">
                            Rp <?= number_format($c['harga'] * $c['qty'], 0, ',', '.') ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Ringkasan Bayar -->
            <div class="col-lg-5">
                <div class="card card-custom p-4 bg-white sticky-top" style="top: 80px;">
                    <h5 class="fw-bold mb-4 border-bottom pb-2">
                        <i class="fas fa-credit-card me-2 text-sage-dark"></i> Pembayaran
                    </h5>

                    <div class="mb-4">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Total Harga (<?= count($cart_items) ?> Barang)</span>
                            <span class="fw-bold">Rp <?= number_format($subtotal, 0, ',', '.') ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <span class="text-muted">Total Ongkos Kirim</span>
                            <span class="fw-bold" id="txtOngkir">Rp 0</span>
                        </div>
                        <div class="d-flex justify-content-between pt-3 border-top">
                            <span class="fw-bold fs-5">Total Tagihan</span>
                            <span class="fw-bold fs-3 text-sage-dark" id="totalAkhir">Rp <?= number_format($subtotal, 0, ',', '.') ?></span>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold small text-muted">Metode Pembayaran</label>
                        <select name="metode_pembayaran" id="selectMetode" class="form-select" style="border-radius: 12px; height: 50px;" required>
                            <option value="Transfer Bank (BCA)">🏦 Transfer Bank (BCA)</option>
                            <option value="Transfer Bank (Mandiri)">🏦 Transfer Bank (Mandiri)</option>
                            <option value="E-Wallet (Dana/OVO)">📱 E-Wallet (Dana/OVO)</option>
                            <option value="COD">🏠 Bayar di Tempat (COD)</option>
                        </select>
                    </div>

                    <div id="bankInfo" class="alert bg-light border-0 rounded-4 p-3 mb-4">
                        <div class="small fw-bold text-muted mb-2">Transfer ke Rekening:</div>
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-bold text-dark" id="txtBankName">BCA - Xriva Store</div>
                                <div class="fs-5 fw-bold text-sage-dark" id="txtAccountNum">1234-5678-90</div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-sage rounded-pill" onclick="copyAccount()">Salin</button>
                        </div>
                    </div>

                    <?php if($is_direct): ?>
                        <input type="hidden" name="is_direct" value="1">
                        <input type="hidden" name="direct_pid" value="<?= $cart_items[0]['id_produk'] ?>">
                        <input type="hidden" name="direct_qty" value="<?= $cart_items[0]['qty'] ?>">
                        <input type="hidden" name="direct_varian" value="<?= htmlspecialchars($cart_items[0]['varian']) ?>">
                        <input type="hidden" name="id_carts" value="0">
                    <?php else: ?>
                        <input type="hidden" name="id_carts" value="<?= implode(',', $ids_selected) ?>">
                    <?php endif; ?>

                    <button type="submit" name="buat_pesanan" id="btnSubmit"
                            class="btn btn-sage w-100 fw-bold py-3 shadow-sm rounded-4"
                            style="font-size: 1.1rem;">
                        Buat Pesanan Sekarang <i class="fas fa-chevron-right ms-1"></i>
                    </button>
                    
                    <div class="text-center mt-3 text-muted small">
                        <i class="fas fa-shield-alt me-1 text-success"></i> Transaksi Aman & Terenkripsi
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const subtotal = <?= $subtotal ?>;
    const userProfile = {
        nama: "<?= addslashes($user['nama']) ?>",
        hp: "<?= addslashes($user['no_hp'] ?? '') ?>",
        alamat: "<?= addslashes(str_replace("\n", " ", $user['alamat'] ?? '')) ?>"
    };

    const txtOngkir = document.getElementById('txtOngkir');
    const totalAkhir = document.getElementById('totalAkhir');
    const ongkirValue = document.getElementById('ongkirValue');
    const ekspedisiValue = document.getElementById('ekspedisiValue');

    function selectShipping(card, label, harga) {
        // Remove active class
        document.querySelectorAll('.shipping-card').forEach(c => c.classList.remove('active'));
        // Add active class
        card.classList.add('active');
        // Update values
        ekspedisiValue.value = label;
        ongkirValue.value = harga;
        txtOngkir.innerText = 'Rp ' + harga.toLocaleString('id-ID');
        totalAkhir.innerText = 'Rp ' + (subtotal + harga).toLocaleString('id-ID');
    }

    // Toggle Alamat
    document.getElementById('btnUseCurrent').addEventListener('click', function() {
        this.classList.add('active');
        document.getElementById('btnUseNew').classList.remove('active');
        document.getElementById('inpNama').value = userProfile.nama;
        document.getElementById('inpHp').value = userProfile.hp;
        document.getElementById('inpAlamat').value = userProfile.alamat;
    });

    document.getElementById('btnUseNew').addEventListener('click', function() {
        this.classList.add('active');
        document.getElementById('btnUseCurrent').classList.remove('active');
        document.getElementById('inpNama').value = '';
        document.getElementById('inpHp').value = '';
        document.getElementById('inpAlamat').value = '';
        document.getElementById('inpNama').focus();
    });

    // Bank Info Update
    const selectMetode = document.getElementById('selectMetode');
    const bankInfo = document.getElementById('bankInfo');
    const txtBankName = document.getElementById('txtBankName');
    const txtAccountNum = document.getElementById('txtAccountNum');

    selectMetode.addEventListener('change', function() {
        if (this.value === 'COD') {
            bankInfo.style.display = 'none';
        } else {
            bankInfo.style.display = 'block';
            if (this.value.includes('BCA')) {
                txtBankName.innerText = 'BCA - Xriva Store';
                txtAccountNum.innerText = '1234-5678-90';
            } else if (this.value.includes('Mandiri')) {
                txtBankName.innerText = 'Mandiri - Xriva Store';
                txtAccountNum.innerText = '0987-6543-21';
            } else {
                txtBankName.innerText = 'DANA / OVO';
                txtAccountNum.innerText = '0812-3456-7890';
            }
        }
    });

    function copyAccount() {
        const num = document.getElementById('txtAccountNum').innerText;
        navigator.clipboard.writeText(num).then(() => {
            alert('Nomor rekening berhasil disalin!');
        });
    }
</script>
</body>
</html>