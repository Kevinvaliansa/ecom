<?php
session_start();
require_once '../backend/config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php"); exit;
}
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// UPDATE STATUS
if (isset($_POST['update_status'])) {
    $id_trx = (int)$_POST['id_transaksi'];
    $status_baru = $_POST['status_pesanan'];
    $conn->prepare("UPDATE transaksi SET status_pesanan=? WHERE id=?")->execute([$status_baru, $id_trx]);
    $_SESSION['admin_msg'] = ['type'=>'success','text'=>"Pesanan #$id_trx diperbarui menjadi ".strtoupper($status_baru)."."];
    header("Location: transaksi.php"); exit;
}

// FILTER
$filter_status = $_GET['status'] ?? 'semua';
$search_trx    = trim($_GET['search'] ?? '');

$where_parts = ["1=1"];
$params      = [];
if ($filter_status !== 'semua') { $where_parts[] = "t.status_pesanan = ?"; $params[] = $filter_status; }
if ($search_trx) { $where_parts[] = "(u.nama LIKE ? OR u.no_hp LIKE ? OR t.id LIKE ?)"; $params[] = "%$search_trx%"; $params[] = "%$search_trx%"; $params[] = "%$search_trx%"; }

$where_sql = implode(' AND ', $where_parts);
$stmt = $conn->prepare("SELECT t.*, u.nama, u.email, u.no_hp, u.alamat, a.nama AS approved_by_name FROM transaksi t JOIN users u ON t.id_user=u.id LEFT JOIN users a ON t.approved_by=a.id WHERE $where_sql ORDER BY t.tanggal_transaksi DESC");
$stmt->execute($params);
$transaksi = $stmt->fetchAll();

// Counts per status
$counts_raw = $conn->query("SELECT status_pesanan, COUNT(*) FROM transaksi GROUP BY status_pesanan")->fetchAll(PDO::FETCH_KEY_PAIR);
$counts = ['semua' => array_sum($counts_raw), 'pending'=>$counts_raw['pending']??0, 'diproses'=>$counts_raw['diproses']??0, 'dikirim'=>$counts_raw['dikirim']??0, 'selesai'=>$counts_raw['selesai']??0, 'batal'=>$counts_raw['batal']??0];
$pending_count = $counts['pending'];

$active_page = 'transaksi';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan Masuk - XrivaStore Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="includes/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<?php include 'includes/sidebar.php'; ?>

<div class="main-content">
    <!-- Topbar -->
    <div class="topbar">
        <div>
            <div class="topbar-title"><i class="fas fa-shopping-bag me-2" style="color:var(--sage)"></i>Pesanan Masuk</div>
            <div class="topbar-sub">Kelola dan update status pengiriman</div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <?php if ($pending_count > 0): ?>
            <span class="badge rounded-pill" style="background:#fff3cd;color:#856404;font-size:.82rem;padding:6px 14px;">
                <i class="fas fa-clock me-1"></i> <?= $pending_count ?> menunggu
            </span>
            <?php endif; ?>
            <div class="topbar-avatar"><?= strtoupper(substr($_SESSION['user_nama'],0,1)) ?></div>
        </div>
    </div>

    <div class="page-content">

        <!-- Filter Bar -->
        <div class="admin-card mb-4">
            <div class="admin-card-body">
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <?php
                    $status_tabs = [
                        'semua'   => ['label'=>'Semua',     'icon'=>'fa-list',         'color'=>'#6c757d'],
                        'pending' => ['label'=>'Menunggu',  'icon'=>'fa-clock',        'color'=>'#856404'],
                        'diproses'=> ['label'=>'Diproses',  'icon'=>'fa-cog',          'color'=>'#055160'],
                        'dikirim' => ['label'=>'Dikirim',   'icon'=>'fa-truck',        'color'=>'#084298'],
                        'selesai' => ['label'=>'Selesai',   'icon'=>'fa-check-circle', 'color'=>'#0a3622'],
                        'batal'   => ['label'=>'Dibatalkan','icon'=>'fa-times-circle', 'color'=>'#842029'],
                    ];
                    foreach ($status_tabs as $key => $tab): ?>
                    <a href="?status=<?= $key ?>&search=<?= urlencode($search_trx) ?>"
                       class="filter-pill <?= $filter_status==$key ? 'active':'' ?>">
                        <i class="fas <?= $tab['icon'] ?>"></i>
                        <?= $tab['label'] ?>
                        <span class="badge rounded-pill ms-1" style="background:rgba(0,0,0,.12);font-size:.68rem;"><?= $counts[$key] ?></span>
                    </a>
                    <?php endforeach; ?>
                </div>
                <!-- Search -->
                <form method="GET" class="d-flex gap-2">
                    <input type="hidden" name="status" value="<?= htmlspecialchars($filter_status) ?>">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-start-0"
                               placeholder="Cari nama pelanggan, HP, atau ID..."
                               value="<?= htmlspecialchars($search_trx) ?>">
                    </div>
                    <button class="btn btn-sage rounded-pill px-4">Cari</button>
                    <?php if ($search_trx): ?>
                    <a href="?status=<?= $filter_status ?>" class="btn btn-outline-secondary rounded-pill px-3">Reset</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- Tabel Transaksi -->
        <div class="admin-card mb-4">
            <div class="admin-card-header">
                <h6><i class="fas fa-receipt me-2" style="color:var(--sage)"></i>
                    <?= $status_tabs[$filter_status]['label'] ?? 'Semua' ?> Pesanan
                </h6>
                <span class="text-muted" style="font-size:.8rem;"><?= count($transaksi) ?> pesanan</span>
            </div>
            <div class="table-responsive">
                <table class="table admin-table mb-0">
                    <thead>
                        <tr>
                            <th>#ID</th>
                            <th>Pelanggan</th>
                            <th>Total</th>
                            <th>Metode</th>
                            <th>Waktu</th>
                            <th>Status</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transaksi as $t): ?>
                        <?php
                            $badgeClass = match($t['status_pesanan']) {
                                'pending'=>'badge-pending','diproses'=>'badge-diproses',
                                'dikirim'=>'badge-dikirim','selesai'=>'badge-selesai',
                                default=>'badge-batal'
                            };
                            $labelMap = ['pending'=>'Menunggu Bayar','diproses'=>'Diproses','dikirim'=>'Dikirim','selesai'=>'Selesai','batal'=>'Dibatalkan'];
                        ?>
                        <tr>
                            <td><span class="fw-bold text-muted">#<?= $t['id'] ?></span></td>
                            <td>
                                <div class="fw-semibold text-dark"><?= htmlspecialchars($t['nama']) ?></div>
                                <div class="text-muted" style="font-size:.76rem;"><i class="fas fa-phone me-1"></i><?= htmlspecialchars($t['no_hp']) ?></div>
                            </td>
                            <td class="fw-bold" style="color:var(--sage)">Rp <?= number_format($t['total_harga'],0,',','.') ?></td>
                            <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($t['metode_pembayaran']) ?></span></td>
                            <td class="text-muted" style="font-size:.78rem;"><?= date('d M Y<\b\r>H:i', strtotime($t['tanggal_transaksi'])) ?></td>
                            <td><span class="<?= $badgeClass ?>"><?= $labelMap[$t['status_pesanan']] ?? $t['status_pesanan'] ?></span></td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-secondary rounded-pill px-3 fw-semibold"
                                        data-bs-toggle="modal" data-bs-target="#modal<?= $t['id'] ?>">
                                    Detail
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($transaksi)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="fas fa-inbox fa-3x mb-3 d-block opacity-25"></i>
                                Tidak ada pesanan <?= $filter_status !== 'semua' ? "dengan status ini" : "" ?>.
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<!-- ── MODALS DETAIL PER TRANSAKSI ─────────────────────── -->
<?php foreach ($transaksi as $t): ?>
<?php $s = $t['status_pesanan']; ?>
<div class="modal fade" id="modal<?= $t['id'] ?>" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0" style="border-radius:18px;overflow:hidden;">
            <div class="modal-header text-white border-0" style="background:var(--sage);padding:20px 24px;">
                <h5 class="modal-title fw-bold m-0"><i class="fas fa-receipt me-2"></i>Pesanan #<?= $t['id'] ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="row g-0">
                    <!-- Kiri: Info & Status -->
                    <div class="col-md-6 border-end p-4">
                        <h6 class="fw-bold text-muted mb-3 small text-uppercase"><i class="fas fa-map-marker-alt me-2"></i>Info Pengiriman</h6>
                        <p class="fw-bold fs-6 mb-1 text-dark"><?= htmlspecialchars($t['nama']) ?></p>
                        <p class="text-muted small mb-1"><i class="fas fa-phone me-2"></i><?= htmlspecialchars($t['no_hp']) ?></p>
                        <p class="text-muted small mb-3"><i class="fas fa-map-pin me-2"></i><?= nl2br(htmlspecialchars($t['alamat'])) ?></p>
                        <hr>
                        <h6 class="fw-bold text-muted mb-3 small text-uppercase"><i class="fas fa-cog me-2"></i>Update Status</h6>
                        <form method="POST">
                            <input type="hidden" name="id_transaksi" value="<?= $t['id'] ?>">
                            <select name="status_pesanan" class="form-select mb-3" <?= in_array($s,['batal','selesai']) ? 'disabled':'' ?>>
                                <option value="pending"  <?= $s=='pending'  ? 'selected':'' ?>>⏳ Menunggu Pembayaran</option>
                                <option value="diproses" <?= $s=='diproses' ? 'selected':'' ?>>📦 Sedang Diproses</option>
                                <option value="dikirim"  <?= $s=='dikirim'  ? 'selected':'' ?>>🚚 Sedang Dikirim</option>
                                <option value="selesai"  <?= $s=='selesai'  ? 'selected':'' ?>>✅ Selesai</option>
                                <option value="batal"    <?= $s=='batal'    ? 'selected':'' ?>>❌ Dibatalkan</option>
                            </select>
                            <?php if (!in_array($s,['batal','selesai'])): ?>
                            <button type="submit" name="update_status" class="btn btn-sage rounded-pill w-100 fw-bold">Simpan Perubahan</button>
                            <?php else: ?>
                            <div class="alert alert-secondary py-2 text-center small mb-0">Pesanan sudah dikunci.</div>
                            <?php endif; ?>
                        </form>

                        <!-- Approve / Reject bukti -->
                        <?php if ($s === 'pending' && !empty($t['bukti_bayar'])): ?>
                        <hr>
                        <div class="d-grid gap-2 mt-2">
                            <form id="formApprove<?= $t['id'] ?>" action="../backend/admin_verifikasi.php" method="POST">
                                <input type="hidden" name="id_transaksi" value="<?= $t['id'] ?>">
                                <input type="hidden" name="action" value="approve">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <button type="button" onclick="confirmApprove(<?= $t['id'] ?>)" class="btn btn-success rounded-pill w-100 fw-bold">
                                    <i class="fas fa-check me-2"></i>Setujui Pembayaran
                                </button>
                            </form>
                            <form id="formReject<?= $t['id'] ?>" action="../backend/admin_verifikasi.php" method="POST">
                                <input type="hidden" name="id_transaksi" value="<?= $t['id'] ?>">
                                <input type="hidden" name="action" value="reject">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <input type="hidden" name="reason" id="reason<?= $t['id'] ?>" value="">
                                <button type="button" onclick="confirmReject(<?= $t['id'] ?>)" class="btn btn-outline-danger rounded-pill w-100 fw-bold">
                                    <i class="fas fa-times me-2"></i>Tolak Bukti
                                </button>
                            </form>
                        </div>
                        <?php endif; ?>
                    </div>
                    <!-- Kanan: Barang & Bukti -->
                    <div class="col-md-6 p-4" style="background:#fafafa;">
                        <h6 class="fw-bold text-muted mb-3 small text-uppercase"><i class="fas fa-box-open me-2"></i>Rincian Barang</h6>
                        <?php
                        $items = $conn->prepare("SELECT * FROM detail_transaksi WHERE id_transaksi=?");
                        $items->execute([$t['id']]);
                        $items = $items->fetchAll();
                        ?>
                        <?php foreach ($items as $item): ?>
                        <div class="d-flex gap-3 mb-3 pb-3 border-bottom align-items-center">
                            <img src="../frontend/images/produk/<?= htmlspecialchars($item['gambar']) ?>" style="width:50px;height:50px;border-radius:10px;object-fit:cover;border:1px solid #eee;">
                            <div class="flex-grow-1">
                                <div class="fw-semibold text-dark small"><?= htmlspecialchars($item['nama_produk'] ?? '-') ?></div>
                                <div class="text-muted" style="font-size:.75rem;"><?= $item['jumlah'] ?> pcs × Rp <?= number_format($item['harga'],0,',','.') ?></div>
                            </div>
                            <div class="fw-bold text-dark small">Rp <?= number_format($item['harga']*$item['jumlah'],0,',','.') ?></div>
                        </div>
                        <?php endforeach; ?>
                        <?php if (empty($items)): ?>
                        <p class="text-muted small text-center py-3">Rincian tidak tersedia.</p>
                        <?php endif; ?>
                        <div class="d-flex justify-content-between pt-2 fw-bold">
                            <span>Total Tagihan</span>
                            <span style="color:var(--sage)">Rp <?= number_format($t['total_harga'],0,',','.') ?></span>
                        </div>
                        <!-- Bukti bayar -->
                        <?php if (!empty($t['bukti_bayar'])): ?>
                        <hr>
                        <h6 class="fw-bold text-muted small text-uppercase mb-2"><i class="fas fa-image me-2"></i>Bukti Pembayaran</h6>
                        <img src="../frontend/images/bukti/<?= htmlspecialchars($t['bukti_bayar']) ?>" class="rounded w-100" style="max-height:200px;object-fit:contain;border:1px solid #eee;">
                        <?php elseif ($s == 'pending'): ?>
                        <hr>
                        <div class="text-center text-muted small py-2"><i class="fas fa-hourglass me-1"></i>Menunggu upload bukti dari pembeli.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php if (isset($_SESSION['admin_msg'])): $am = $_SESSION['admin_msg']; unset($_SESSION['admin_msg']); ?>
<script>
Swal.fire({ icon: '<?= $am["type"] ?>', title: '<?= $am["type"]==="success" ? "Sukses" : "Gagal" ?>', text: '<?= addslashes($am["text"]) ?>', confirmButtonColor: '#52796f' });
</script>
<?php endif; ?>
<script>
function confirmApprove(id) {
    Swal.fire({ title:'Setujui pembayaran?', text:'Status akan diubah menjadi Diproses.', icon:'question', showCancelButton:true, confirmButtonText:'Ya, Setujui', confirmButtonColor:'#198754', cancelButtonColor:'#6c757d' })
    .then(r => { if(r.isConfirmed) document.getElementById('formApprove'+id).submit(); });
}
function confirmReject(id) {
    Swal.fire({ title:'Tolak bukti?', input:'textarea', inputLabel:'Alasan (opsional)', showCancelButton:true, confirmButtonText:'Tolak', confirmButtonColor:'#dc3545', cancelButtonColor:'#6c757d' })
    .then(r => { if(r.isConfirmed) { document.getElementById('reason'+id).value=r.value||''; document.getElementById('formReject'+id).submit(); } });
}
</script>
</body>
</html>
