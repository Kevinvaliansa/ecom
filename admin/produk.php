<?php
session_start();
require_once '../backend/config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php"); exit;
}

// ── PENCARIAN & PAGINASI ────────────────────────────────────
$search = trim($_GET['search'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 8;
$offset = ($page - 1) * $limit;

$params = [];
$where  = $search ? "WHERE nama_produk LIKE ? OR kategori LIKE ?" : "";
if ($search) { $params[] = "%$search%"; $params[] = "%$search%"; }

$total_data  = $conn->prepare("SELECT COUNT(*) FROM produk $where");
$total_data->execute($params);
$total_data  = (int)$total_data->fetchColumn();
$total_pages = max(1, ceil($total_data / $limit));
$page        = min($page, $total_pages);

$stmt = $conn->prepare("SELECT * FROM produk $where ORDER BY id DESC LIMIT $limit OFFSET $offset");
$stmt->execute($params);
$produk = $stmt->fetchAll();

// Stats
$total_habis = $conn->query("SELECT COUNT(*) FROM produk WHERE stok=0")->fetchColumn();
$total_all   = $conn->query("SELECT COUNT(*) FROM produk")->fetchColumn();

// Pending count for sidebar
$pending_count = $conn->query("SELECT COUNT(*) FROM transaksi WHERE status_pesanan='pending'")->fetchColumn();

// HAPUS PRODUK
if (isset($_GET['hapus'])) {
    $id_hapus = (int)$_GET['hapus'];
    $stmt_img = $conn->prepare("SELECT gambar FROM produk WHERE id=?");
    $stmt_img->execute([$id_hapus]);
    $img = $stmt_img->fetch();
    if ($img && $img['gambar'] != 'default.png') {
        $fp = '../frontend/images/produk/' . $img['gambar'];
        if (file_exists($fp)) unlink($fp);
    }
    $conn->prepare("DELETE FROM produk WHERE id=?")->execute([$id_hapus]);
    header("Location: produk.php?deleted=1"); exit;
}

$active_page = 'produk';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Produk - XrivaStore Admin</title>
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
            <div class="topbar-title"><i class="fas fa-box-open me-2" style="color:var(--sage)"></i>Kelola Produk</div>
            <div class="topbar-sub">Total <?= $total_all ?> produk di katalog<?= $total_habis > 0 ? " · <span style='color:#dc3545;font-weight:600'>$total_habis habis stok</span>" : '' ?></div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="tambah_produk.php" class="btn btn-sage btn-sm rounded-pill px-3">
                <i class="fas fa-plus me-1"></i> Tambah Produk
            </a>
            <div class="topbar-avatar"><?= strtoupper(substr($_SESSION['user_nama'], 0, 1)) ?></div>
        </div>
    </div>

    <div class="page-content">

        <!-- Search Bar -->
        <div class="admin-card mb-4">
            <div class="admin-card-body">
                <form method="GET" class="d-flex gap-2">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-start-0"
                               placeholder="Cari nama produk atau kategori..."
                               value="<?= htmlspecialchars($search) ?>">
                        <?php if ($search): ?>
                        <a href="produk.php" class="input-group-text bg-light border-start-0 text-muted" title="Hapus pencarian"><i class="fas fa-times"></i></a>
                        <?php endif; ?>
                    </div>
                    <button type="submit" class="btn btn-sage rounded-pill px-4">Cari</button>
                </form>
                <?php if ($search): ?>
                <p class="mt-2 mb-0 small text-muted">
                    Ditemukan <strong><?= $total_data ?></strong> produk untuk kata kunci "<strong><?= htmlspecialchars($search) ?></strong>"
                </p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tabel Produk -->
        <div class="admin-card mb-4">
            <div class="admin-card-header">
                <h6><i class="fas fa-th-list me-2" style="color:var(--sage)"></i>Daftar Produk</h6>
                <span class="text-muted" style="font-size:.8rem;">Halaman <?= $page ?> / <?= $total_pages ?></span>
            </div>
            <div class="table-responsive">
                <table class="table admin-table mb-0">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th>Kategori</th>
                            <th>Harga</th>
                            <th>Stok</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($produk as $p): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <img src="../frontend/images/produk/<?= htmlspecialchars($p['gambar']) ?>"
                                         style="width:52px;height:52px;border-radius:12px;object-fit:cover;border:1px solid #f1f3f5;<?= $p['stok']<=0 ? 'filter:grayscale(100%);opacity:.5;' : '' ?>">
                                    <div>
                                        <div class="fw-semibold text-dark"><?= htmlspecialchars($p['nama_produk']) ?></div>
                                        <div class="text-muted" style="font-size:.76rem;max-width:200px;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;">
                                            <?= htmlspecialchars(substr($p['deskripsi'], 0, 55)) ?>...
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge rounded-pill" style="background:#e8f0ed;color:var(--sage);font-size:.78rem;padding:5px 12px;">
                                    <?= htmlspecialchars($p['kategori'] ?? 'Umum') ?>
                                </span>
                            </td>
                            <td class="fw-semibold text-dark">Rp <?= number_format($p['harga'], 0, ',', '.') ?></td>
                            <td>
                                <?php if ($p['stok'] <= 0): ?>
                                    <span class="badge-batal">Habis</span>
                                <?php elseif ($p['stok'] <= 5): ?>
                                    <span class="badge-pending"><?= $p['stok'] ?> pcs ⚠️</span>
                                <?php else: ?>
                                    <span class="fw-semibold text-dark"><?= $p['stok'] ?> pcs</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-2">
                                    <a href="edit_produk.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3" title="Edit">
                                        <i class="fas fa-edit me-1"></i> Edit
                                    </a>
                                    <button class="btn btn-sm btn-outline-danger rounded-pill px-3" onclick="confirmDelete(<?= $p['id'] ?>, '<?= addslashes($p['nama_produk']) ?>')" title="Hapus">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($produk)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="fas fa-box-open fa-3x mb-3 d-block opacity-25"></i>
                                <?= $search ? "Tidak ditemukan produk dengan kata kunci \"$search\"." : "Belum ada produk di katalog." ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="admin-card-body border-top">
                <nav>
                    <ul class="pagination mb-0 justify-content-center">
                        <li class="page-item <?= $page<=1 ? 'disabled':'' ?>">
                            <a class="page-link rounded-pill me-1" href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>">← Sebelumnya</a>
                        </li>
                        <?php for ($i=1; $i<=$total_pages; $i++): ?>
                        <li class="page-item <?= $page==$i ? 'active':'' ?>">
                            <a class="page-link rounded-circle mx-1" style="width:36px;text-align:center;" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                        </li>
                        <?php endfor; ?>
                        <li class="page-item <?= $page>=$total_pages ? 'disabled':'' ?>">
                            <a class="page-link rounded-pill ms-1" href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>">Selanjutnya →</a>
                        </li>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function confirmDelete(id, nama) {
    Swal.fire({
        title: 'Hapus Produk?',
        html: `Produk <strong>"${nama}"</strong> akan dihapus permanen dari katalog!`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
    }).then(r => { if (r.isConfirmed) window.location.href = `produk.php?hapus=${id}`; });
}

<?php if (isset($_GET['deleted'])): ?>
Swal.fire({ title: 'Berhasil Dihapus!', text: 'Produk telah dihapus dari katalog.', icon: 'success', confirmButtonColor: '#52796f' })
    .then(() => window.history.replaceState(null, '', 'produk.php'));
<?php elseif (isset($_GET['added'])): ?>
Swal.fire({ title: 'Produk Ditambahkan!', text: 'Produk baru berhasil masuk ke katalog.', icon: 'success', confirmButtonColor: '#52796f' })
    .then(() => window.history.replaceState(null, '', 'produk.php'));
<?php elseif (isset($_GET['updated'])): ?>
Swal.fire({ title: 'Produk Diperbarui!', text: 'Perubahan data produk berhasil disimpan.', icon: 'success', confirmButtonColor: '#52796f' })
    .then(() => window.history.replaceState(null, '', 'produk.php'));
<?php endif; ?>
</script>
</body>
</html>