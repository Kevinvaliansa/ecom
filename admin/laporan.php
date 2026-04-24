<?php
session_start();
require_once '../backend/config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php"); exit;
}

// ── FILTER LOGIC ──────────────────────────────────────────────
$is_custom = isset($_GET['tgl_mulai'], $_GET['tgl_selesai']) && !isset($_GET['filter']);
$active_filter = $_GET['filter'] ?? ($is_custom ? 'custom' : 'bulan');

switch ($active_filter) {
    case 'hari':   $tgl_mulai = date('Y-m-d'); $tgl_selesai = date('Y-m-d'); break;
    case 'minggu': $tgl_mulai = date('Y-m-d', strtotime('monday this week')); $tgl_selesai = date('Y-m-d', strtotime('sunday this week')); break;
    case 'bulan':  $tgl_mulai = date('Y-m-01'); $tgl_selesai = date('Y-m-t'); break;
    case 'tahun':  $tgl_mulai = date('Y-01-01'); $tgl_selesai = date('Y-12-31'); break;
    case 'semua':  $tgl_mulai = '2000-01-01'; $tgl_selesai = date('Y-m-d'); break;
    default:       $tgl_mulai = $_GET['tgl_mulai'] ?? date('Y-m-01'); $tgl_selesai = $_GET['tgl_selesai'] ?? date('Y-m-d');
}

// ── QUERIES ───────────────────────────────────────────────────

// 1. Ringkasan
$stmt = $conn->prepare("SELECT COALESCE(SUM(total_harga),0) as pendapatan, COUNT(*) as total_pesanan FROM transaksi WHERE status_pesanan='selesai' AND DATE(tanggal_transaksi) BETWEEN ? AND ?");
$stmt->execute([$tgl_mulai, $tgl_selesai]);
$summary = $stmt->fetch();
$rata = $summary['total_pesanan'] > 0 ? $summary['pendapatan'] / $summary['total_pesanan'] : 0;

// Pesanan pending (for sidebar badge)
$pending_count = $conn->query("SELECT COUNT(*) FROM transaksi WHERE status_pesanan='pending'")->fetchColumn();

// 2. Daftar transaksi selesai di periode ini
$stmt = $conn->prepare("SELECT t.*, u.nama FROM transaksi t JOIN users u ON t.id_user = u.id WHERE t.status_pesanan='selesai' AND DATE(t.tanggal_transaksi) BETWEEN ? AND ? ORDER BY t.tanggal_transaksi DESC");
$stmt->execute([$tgl_mulai, $tgl_selesai]);
$laporan_list = $stmt->fetchAll();

// 3. Produk terlaris
$stmt = $conn->prepare("SELECT dt.nama_produk, dt.gambar, SUM(dt.jumlah) as total_terjual, SUM(dt.harga * dt.jumlah) as total_omzet FROM detail_transaksi dt JOIN transaksi t ON dt.id_transaksi = t.id WHERE t.status_pesanan='selesai' AND DATE(t.tanggal_transaksi) BETWEEN ? AND ? GROUP BY dt.id_produk, dt.nama_produk, dt.gambar ORDER BY total_terjual DESC LIMIT 5");
$stmt->execute([$tgl_mulai, $tgl_selesai]);
$top_products = $stmt->fetchAll();

// 4. Grafik harian dalam periode
$stmt = $conn->prepare("SELECT DATE(tanggal_transaksi) as tgl, COALESCE(SUM(total_harga),0) as total, COUNT(*) as cnt FROM transaksi WHERE status_pesanan='selesai' AND DATE(tanggal_transaksi) BETWEEN ? AND ? GROUP BY DATE(tanggal_transaksi) ORDER BY tgl ASC");
$stmt->execute([$tgl_mulai, $tgl_selesai]);
$chart_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

$chart_labels = array_map(fn($r) => date('d M', strtotime($r['tgl'])), $chart_raw);
$chart_data   = array_map(fn($r) => (float)$r['total'], $chart_raw);
$chart_cnt    = array_map(fn($r) => (int)$r['cnt'], $chart_raw);

// Max terlaris (untuk progress bar)
$max_terlaris = !empty($top_products) ? max(array_column($top_products, 'total_terjual')) : 1;

$active_page = 'laporan';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Penjualan - XrivaStore Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="includes/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<?php include 'includes/sidebar.php'; ?>

<div class="main-content">

    <!-- Topbar -->
    <div class="topbar">
        <div>
            <div class="topbar-title"><i class="fas fa-chart-line me-2" style="color:var(--sage)"></i>Laporan Penjualan</div>
            <div class="topbar-sub">Data transaksi berstatus "Selesai"</div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button onclick="window.print()" class="btn btn-sm btn-outline-secondary rounded-pill px-3 no-print">
                <i class="fas fa-print me-1"></i> Cetak
            </button>
            <div class="topbar-avatar"><?= strtoupper(substr($_SESSION['user_nama'], 0, 1)) ?></div>
        </div>
    </div>

    <div class="page-content">

        <!-- ── FILTER PANEL ───────────────────────────────────── -->
        <div class="admin-card mb-4 no-print">
            <div class="admin-card-header">
                <h6><i class="fas fa-sliders-h me-2" style="color:var(--sage)"></i>Filter Periode</h6>
                <span class="badge rounded-pill" style="background:#e8f0ed;color:var(--sage);font-size:.78rem;">
                    <?= date('d M Y', strtotime($tgl_mulai)) ?> — <?= date('d M Y', strtotime($tgl_selesai)) ?>
                </span>
            </div>
            <div class="admin-card-body">
                <!-- Quick Filters -->
                <div class="d-flex flex-wrap gap-2 mb-4">
                    <a href="laporan.php?filter=hari"   class="filter-pill <?= $active_filter=='hari'   ? 'active':'' ?>"><i class="fas fa-sun"></i> Hari Ini</a>
                    <a href="laporan.php?filter=minggu" class="filter-pill <?= $active_filter=='minggu' ? 'active':'' ?>"><i class="fas fa-calendar-week"></i> Minggu Ini</a>
                    <a href="laporan.php?filter=bulan"  class="filter-pill <?= $active_filter=='bulan'  ? 'active':'' ?>"><i class="fas fa-calendar-alt"></i> Bulan Ini</a>
                    <a href="laporan.php?filter=tahun"  class="filter-pill <?= $active_filter=='tahun'  ? 'active':'' ?>"><i class="fas fa-calendar"></i> Tahun Ini</a>
                    <a href="laporan.php?filter=semua"  class="filter-pill <?= $active_filter=='semua'  ? 'active':'' ?>"><i class="fas fa-infinity"></i> Semua Waktu</a>
                </div>
                <!-- Custom Date Range -->
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-6">
                        <label class="form-label">Tanggal Mulai</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="far fa-calendar text-muted"></i></span>
                            <input type="date" name="tgl_mulai" class="form-control border-start-0"
                                   value="<?= in_array($active_filter, ['semua']) ? '' : $tgl_mulai ?>" onchange="this.form.submit()">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Tanggal Selesai</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="far fa-calendar-check text-muted"></i></span>
                            <input type="date" name="tgl_selesai" class="form-control border-start-0" value="<?= $tgl_selesai ?>" onchange="this.form.submit()">
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- ── STAT CARDS ─────────────────────────────────────── -->
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-lg-3">
                <div class="stat-card">
                    <div class="stat-icon" style="background:#d1e7dd"><i class="fas fa-wallet" style="color:#198754"></i></div>
                    <div>
                        <div class="stat-label">Total Pendapatan</div>
                        <div class="stat-value" style="font-size:1.15rem;">Rp <?= number_format($summary['pendapatan'], 0, ',', '.') ?></div>
                        <div class="stat-sub">Dari <?= count($laporan_list) ?> transaksi</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="stat-card">
                    <div class="stat-icon" style="background:#cfe2ff"><i class="fas fa-shopping-bag" style="color:#0d6efd"></i></div>
                    <div>
                        <div class="stat-label">Pesanan Selesai</div>
                        <div class="stat-value"><?= $summary['total_pesanan'] ?></div>
                        <div class="stat-sub">Transaksi berhasil</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="stat-card">
                    <div class="stat-icon" style="background:#fff3cd"><i class="fas fa-receipt" style="color:#ffc107"></i></div>
                    <div>
                        <div class="stat-label">Rata-rata / Transaksi</div>
                        <div class="stat-value" style="font-size:1.1rem;">Rp <?= number_format($rata, 0, ',', '.') ?></div>
                        <div class="stat-sub">Nilai rata-rata order</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="stat-card">
                    <div class="stat-icon" style="background:#f3d9fa"><i class="fas fa-award" style="color:#9c27b0"></i></div>
                    <div>
                        <div class="stat-label">Produk Terlaris</div>
                        <div class="stat-value" style="font-size:.92rem;line-height:1.4;">
                            <?= !empty($top_products) ? htmlspecialchars(substr($top_products[0]['nama_produk'] ?? '-', 0, 22)) : '-' ?>
                        </div>
                        <div class="stat-sub"><?= $top_products[0]['total_terjual'] ?? 0 ?> pcs terjual</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── CHART + TOP PRODUCTS ───────────────────────────── -->
        <div class="row g-4 mb-4">
            <div class="col-lg-8">
                <div class="admin-card h-100">
                    <div class="admin-card-header">
                        <h6><i class="fas fa-chart-bar me-2" style="color:var(--sage)"></i>Grafik Pendapatan Harian</h6>
                        <span class="badge" style="background:#d1e7dd;color:#0a3622;font-size:.75rem;">
                            <?= count($chart_data) ?> hari data
                        </span>
                    </div>
                    <div class="admin-card-body">
                        <?php if (!empty($chart_data)): ?>
                        <canvas id="revenueChart" height="140"></canvas>
                        <?php else: ?>
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-chart-bar fa-3x mb-3 opacity-25 d-block"></i>
                            Belum ada data untuk periode ini.
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="admin-card h-100">
                    <div class="admin-card-header">
                        <h6><i class="fas fa-trophy me-2 text-warning"></i>Produk Terlaris</h6>
                    </div>
                    <div class="admin-card-body">
                        <?php if (!empty($top_products)): ?>
                            <?php foreach ($top_products as $i => $top): ?>
                            <div class="mb-3">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <span class="fw-bold text-muted" style="width:18px;font-size:.8rem;">#<?= $i+1 ?></span>
                                    <?php if (!empty($top['gambar'])): ?>
                                    <img src="../frontend/images/produk/<?= htmlspecialchars($top['gambar']) ?>"
                                         style="width:32px;height:32px;border-radius:8px;object-fit:cover;border:1px solid #eee;" alt="">
                                    <?php endif; ?>
                                    <span class="small fw-semibold text-dark text-truncate flex-grow-1">
                                        <?= htmlspecialchars($top['nama_produk'] ?? 'Produk Lama') ?>
                                    </span>
                                    <span class="badge rounded-pill" style="background:#e8f0ed;color:var(--sage);font-size:.72rem;white-space:nowrap;">
                                        <?= $top['total_terjual'] ?> pcs
                                    </span>
                                </div>
                                <div style="height:6px;border-radius:3px;background:#f1f3f5;overflow:hidden;">
                                    <div style="height:100%;border-radius:3px;background:var(--sage-light);width:<?= round(($top['total_terjual']/$max_terlaris)*100) ?>%;transition:.5s;"></div>
                                </div>
                                <div class="text-end" style="font-size:.7rem;color:#adb5bd;margin-top:2px;">
                                    Omzet: Rp <?= number_format($top['total_omzet'], 0, ',', '.') ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-4 text-muted small">
                                <i class="fas fa-box-open fa-2x mb-2 d-block opacity-25"></i>
                                Belum ada data produk terjual.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── TABEL TRANSAKSI ────────────────────────────────── -->
        <div class="admin-card mb-5">
            <div class="admin-card-header">
                <h6><i class="fas fa-list me-2" style="color:var(--sage)"></i>Detail Transaksi Selesai</h6>
                <span class="text-muted" style="font-size:.8rem;"><?= count($laporan_list) ?> transaksi</span>
            </div>
            <div class="table-responsive">
                <table class="table admin-table mb-0">
                    <thead>
                        <tr>
                            <th>#ID</th>
                            <th>Tanggal</th>
                            <th>Pelanggan</th>
                            <th>Metode Bayar</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($laporan_list as $l): ?>
                        <tr>
                            <td><span class="fw-bold text-muted">#<?= $l['id'] ?></span></td>
                            <td><span class="text-muted"><?= date('d M Y, H:i', strtotime($l['tanggal_transaksi'])) ?></span></td>
                            <td class="fw-semibold text-dark"><?= htmlspecialchars($l['nama']) ?></td>
                            <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($l['metode_pembayaran']) ?></span></td>
                            <td class="text-end fw-bold" style="color:var(--sage)">Rp <?= number_format($l['total_harga'], 0, ',', '.') ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($laporan_list)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="fas fa-search fa-2x mb-2 d-block opacity-25"></i>
                                Tidak ada data penjualan untuk periode ini.
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                    <?php if (!empty($laporan_list)): ?>
                    <tfoot>
                        <tr style="background:#f8f9fa;">
                            <td colspan="4" class="text-end fw-bold py-3 px-4 text-muted">TOTAL PENDAPATAN:</td>
                            <td class="text-end fw-bold py-3 px-4" style="color:var(--sage);font-size:1rem;">
                                Rp <?= number_format($summary['pendapatan'], 0, ',', '.') ?>
                            </td>
                        </tr>
                    </tfoot>
                    <?php endif; ?>
                </table>
            </div>
        </div>

    </div><!-- /page-content -->
</div><!-- /main-content -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php if (!empty($chart_data)): ?>
<script>
const ctx = document.getElementById('revenueChart').getContext('2d');
const gradient = ctx.createLinearGradient(0, 0, 0, 300);
gradient.addColorStop(0, 'rgba(82,121,111,0.3)');
gradient.addColorStop(1, 'rgba(82,121,111,0)');

new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?= json_encode($chart_labels) ?>,
        datasets: [{
            label: 'Pendapatan',
            data: <?= json_encode($chart_data) ?>,
            borderColor: '#52796f',
            backgroundColor: gradient,
            borderWidth: 2.5,
            pointBackgroundColor: '#52796f',
            pointRadius: 5,
            pointHoverRadius: 7,
            fill: true,
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: ctx => 'Rp ' + ctx.parsed.y.toLocaleString('id-ID')
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: { color: '#f1f3f5' },
                ticks: {
                    callback: val => 'Rp ' + (val >= 1000000 ? (val/1000000).toFixed(1)+'jt' : val.toLocaleString('id-ID')),
                    font: { size: 11 }
                }
            },
            x: {
                grid: { display: false },
                ticks: { font: { size: 11 } }
            }
        }
    }
});
</script>
<?php endif; ?>
</body>
</html>