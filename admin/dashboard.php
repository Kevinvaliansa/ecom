<?php
session_start();
require_once '../backend/config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php"); exit;
}

// =============================================
// AMBIL SEMUA DATA UNTUK STATISTIK
// =============================================

// Total pendapatan bulan ini
$stmt = $conn->query("SELECT COALESCE(SUM(total_harga),0) FROM transaksi WHERE status_pesanan='selesai' AND MONTH(tanggal_transaksi)=MONTH(NOW()) AND YEAR(tanggal_transaksi)=YEAR(NOW())");
$pendapatan_bulan = $stmt->fetchColumn();

// Total pendapatan hari ini
$stmt = $conn->query("SELECT COALESCE(SUM(total_harga),0) FROM transaksi WHERE status_pesanan='selesai' AND DATE(tanggal_transaksi)=CURDATE()");
$pendapatan_hari = $stmt->fetchColumn();

// Jumlah pesanan per status
$stmt = $conn->query("SELECT status_pesanan, COUNT(*) as total FROM transaksi GROUP BY status_pesanan");
$status_counts_raw = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$status_counts = [
    'pending'  => $status_counts_raw['pending']  ?? 0,
    'diproses' => $status_counts_raw['diproses'] ?? 0,
    'dikirim'  => $status_counts_raw['dikirim']  ?? 0,
    'selesai'  => $status_counts_raw['selesai']  ?? 0,
    'batal'    => $status_counts_raw['batal']    ?? 0,
];
$total_pesanan = array_sum($status_counts);

// Total produk & total user
$total_produk = $conn->query("SELECT COUNT(*) FROM produk")->fetchColumn();
$total_user   = $conn->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn();

// Produk hampir habis (stok <= 5)
$stmt = $conn->query("SELECT id, nama_produk, stok, gambar FROM produk WHERE stok <= 5 AND stok > 0 ORDER BY stok ASC LIMIT 5");
$low_stock = $stmt->fetchAll();

// Produk habis total
$total_habis = $conn->query("SELECT COUNT(*) FROM produk WHERE stok = 0")->fetchColumn();

// Pesanan terbaru (10)
$stmt = $conn->query("SELECT t.*, u.nama FROM transaksi t JOIN users u ON t.id_user = u.id ORDER BY t.tanggal_transaksi DESC LIMIT 8");
$pesanan_terbaru = $stmt->fetchAll();

// Data grafik pendapatan 7 hari terakhir
$stmt = $conn->query("
    SELECT DATE(tanggal_transaksi) as tgl, COALESCE(SUM(total_harga),0) as total
    FROM transaksi
    WHERE status_pesanan='selesai' AND tanggal_transaksi >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    GROUP BY DATE(tanggal_transaksi)
    ORDER BY tgl ASC
");
$chart_raw = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$chart_labels = [];
$chart_data   = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $chart_labels[] = date('d M', strtotime($d));
    $chart_data[]   = $chart_raw[$d] ?? 0;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - XrivaStore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --sage:        #52796f;
            --sage-light:  #84a98c;
            --sage-pale:   #cad2c5;
            --bg:          #f4f7f6;
            --sidebar-w:   260px;
        }
        * { box-sizing: border-box; }
        body { background: var(--bg); font-family: 'Inter', sans-serif; margin: 0; }

        /* ─── SIDEBAR ─── */
        .sidebar {
            position: fixed; top: 0; left: 0; height: 100vh; width: var(--sidebar-w);
            background: var(--sage); color: #fff;
            display: flex; flex-direction: column;
            box-shadow: 4px 0 20px rgba(0,0,0,0.12);
            z-index: 100;
        }
        .sidebar-brand {
            padding: 28px 24px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.12);
        }
        .sidebar-brand h4 { font-weight: 800; font-size: 1.1rem; margin: 0; letter-spacing: 0.5px; }
        .sidebar-brand small { opacity: .65; font-size: .75rem; }
        .sidebar-nav { flex: 1; padding: 16px 12px; overflow-y: auto; }
        .nav-label { font-size: .65rem; text-transform: uppercase; letter-spacing: 1.5px; opacity: .5; padding: 12px 12px 6px; font-weight: 600; }
        .sidebar-nav .nav-link {
            color: rgba(255,255,255,.75); border-radius: 10px; padding: 11px 14px;
            margin-bottom: 3px; font-weight: 500; font-size: .88rem;
            display: flex; align-items: center; gap: 10px; transition: all .2s;
        }
        .sidebar-nav .nav-link:hover, .sidebar-nav .nav-link.active {
            background: rgba(255,255,255,.18); color: #fff;
        }
        .sidebar-nav .nav-link.active { font-weight: 700; }
        .sidebar-nav .nav-link i { width: 18px; text-align: center; font-size: .9rem; }
        .sidebar-footer {
            padding: 14px 12px;
            border-top: 1px solid rgba(255,255,255,.12);
        }
        .sidebar-footer .nav-link { color: rgba(255,255,255,.7); }
        .sidebar-footer .nav-link:hover { background: rgba(220,53,69,.25); color: #ff7f8a; }

        /* ─── MAIN CONTENT ─── */
        .main-content { margin-left: var(--sidebar-w); min-height: 100vh; }

        /* ─── TOPBAR ─── */
        .topbar {
            background: #fff; padding: 16px 32px;
            display: flex; align-items: center; justify-content: space-between;
            box-shadow: 0 1px 0 #e9ecef; position: sticky; top: 0; z-index: 99;
        }
        .topbar-greeting { font-weight: 700; font-size: 1.05rem; color: #2f3e46; }
        .topbar-sub { font-size: .8rem; color: #adb5bd; margin-top: 2px; }
        .topbar-avatar {
            width: 40px; height: 40px; border-radius: 50%;
            background: var(--sage); color: #fff;
            display: flex; align-items: center; justify-content: center;
            font-weight: 800; font-size: 1rem;
        }

        /* ─── STAT CARDS ─── */
        .stat-card {
            background: #fff; border-radius: 18px; padding: 22px 24px;
            box-shadow: 0 2px 10px rgba(0,0,0,.05);
            display: flex; align-items: center; gap: 18px;
            transition: transform .2s, box-shadow .2s;
        }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(0,0,0,.09); }
        .stat-icon {
            width: 56px; height: 56px; border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.4rem; flex-shrink: 0;
        }
        .stat-label { font-size: .78rem; font-weight: 600; text-transform: uppercase; letter-spacing: .8px; color: #adb5bd; }
        .stat-value { font-size: 1.5rem; font-weight: 800; color: #2f3e46; line-height: 1.2; }
        .stat-sub { font-size: .78rem; color: #6c757d; margin-top: 2px; }

        /* ─── SECTION CARD ─── */
        .section-card {
            background: #fff; border-radius: 18px;
            box-shadow: 0 2px 10px rgba(0,0,0,.05); overflow: hidden;
        }
        .section-card .card-header {
            background: #fff; padding: 18px 24px;
            border-bottom: 1px solid #f1f3f5;
            display: flex; align-items: center; justify-content: space-between;
        }
        .section-card .card-header h6 { font-weight: 700; margin: 0; font-size: .95rem; }

        /* ─── TABLE ─── */
        .table th { font-size: .75rem; text-transform: uppercase; letter-spacing: .8px; color: #adb5bd; font-weight: 600; border: none; padding: 12px 20px; background: #f8f9fa; }
        .table td { vertical-align: middle; padding: 13px 20px; border-color: #f8f9fa; font-size: .88rem; }

        /* ─── STATUS BADGE ─── */
        .badge-status { padding: 5px 12px; border-radius: 20px; font-size: .73rem; font-weight: 600; }
        .badge-pending  { background:#fff3cd; color:#856404; }
        .badge-diproses { background:#cff4fc; color:#055160; }
        .badge-dikirim  { background:#cfe2ff; color:#084298; }
        .badge-selesai  { background:#d1e7dd; color:#0a3622; }
        .badge-batal    { background:#f8d7da; color:#842029; }

        /* ─── LOW STOCK ─── */
        .low-stock-item { display: flex; align-items: center; gap: 14px; padding: 12px 0; border-bottom: 1px solid #f8f9fa; }
        .low-stock-item:last-child { border-bottom: none; }
        .low-stock-img { width: 44px; height: 44px; border-radius: 10px; object-fit: cover; background: #f8f9fa; }
        .stok-bar { height: 6px; border-radius: 3px; background: #e9ecef; }
        .stok-fill { height: 100%; border-radius: 3px; }
    </style>
</head>
<body>

<!-- ===== SIDEBAR ===== -->
<div class="sidebar">
    <div class="sidebar-brand">
        <h4><i class="fas fa-glasses me-2" style="color:var(--sage-pale)"></i>XrivaStore</h4>
        <small>Admin Panel</small>
    </div>
    <nav class="sidebar-nav">
        <div class="nav-label">Menu Utama</div>
        <a class="nav-link active" href="dashboard.php"><i class="fas fa-th-large"></i> Dashboard</a>
        <a class="nav-link" href="produk.php"><i class="fas fa-box-open"></i> Kelola Produk</a>
        <a class="nav-link" href="transaksi.php"><i class="fas fa-shopping-bag"></i> Pesanan Masuk
            <?php if ($status_counts['pending'] > 0): ?>
            <span class="badge rounded-pill ms-auto" style="background:rgba(255,193,7,.9);color:#333;font-size:.7rem;"><?= $status_counts['pending'] ?></span>
            <?php endif; ?>
        </a>
        <div class="nav-label">Laporan</div>
        <a class="nav-link" href="laporan.php"><i class="fas fa-chart-line"></i> Laporan Penjualan</a>
    </nav>
    <div class="sidebar-footer">
        <a class="nav-link" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Keluar</a>
    </div>
</div>

<!-- ===== MAIN CONTENT ===== -->
<div class="main-content">

    <!-- Topbar -->
    <div class="topbar">
        <div>
            <div class="topbar-greeting">Selamat Datang, <?= htmlspecialchars($_SESSION['user_nama']) ?>! 👋</div>
            <div class="topbar-sub"><?= date('l, d F Y') ?></div>
        </div>
        <div class="d-flex align-items-center gap-3">
            <a href="../index.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3" target="_blank">
                <i class="fas fa-store me-1"></i> Lihat Toko
            </a>
            <div class="topbar-avatar"><?= strtoupper(substr($_SESSION['user_nama'], 0, 1)) ?></div>
        </div>
    </div>

    <!-- Page Content -->
    <div class="p-4" style="max-width: 1400px;">

        <!-- ── Stat Cards ── -->
        <div class="row g-3 mb-4">
            <!-- Pendapatan Bulan Ini -->
            <div class="col-lg-3 col-sm-6">
                <div class="stat-card">
                    <div class="stat-icon" style="background:#d1e7dd">
                        <i class="fas fa-wallet" style="color:#198754"></i>
                    </div>
                    <div>
                        <div class="stat-label">Pendapatan Bulan Ini</div>
                        <div class="stat-value">Rp <?= number_format((float)$pendapatan_bulan, 0, ',', '.') ?></div>
                        <div class="stat-sub">Hari ini: Rp <?= number_format((float)$pendapatan_hari, 0, ',', '.') ?></div>
                    </div>
                </div>
            </div>
            <!-- Total Pesanan -->
            <div class="col-lg-3 col-sm-6">
                <div class="stat-card">
                    <div class="stat-icon" style="background:#cfe2ff">
                        <i class="fas fa-shopping-bag" style="color:#0d6efd"></i>
                    </div>
                    <div>
                        <div class="stat-label">Total Pesanan</div>
                        <div class="stat-value"><?= $total_pesanan ?></div>
                        <div class="stat-sub"><?= $status_counts['pending'] ?> menunggu pembayaran</div>
                    </div>
                </div>
            </div>
            <!-- Total Produk -->
            <div class="col-lg-3 col-sm-6">
                <div class="stat-card">
                    <div class="stat-icon" style="background:#fff3cd">
                        <i class="fas fa-glasses" style="color:#ffc107"></i>
                    </div>
                    <div>
                        <div class="stat-label">Total Produk</div>
                        <div class="stat-value"><?= $total_produk ?></div>
                        <div class="stat-sub <?= $total_habis > 0 ? 'text-danger fw-semibold' : '' ?>">
                            <?= $total_habis > 0 ? "$total_habis produk habis" : "Semua tersedia" ?>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Total Pelanggan -->
            <div class="col-lg-3 col-sm-6">
                <div class="stat-card">
                    <div class="stat-icon" style="background:#f3d9fa">
                        <i class="fas fa-users" style="color:#9c27b0"></i>
                    </div>
                    <div>
                        <div class="stat-label">Total Pelanggan</div>
                        <div class="stat-value"><?= $total_user ?></div>
                        <div class="stat-sub">Pengguna terdaftar</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Status Pesanan Row ── -->
        <div class="row g-3 mb-4">
            <?php
            $s_list = [
                ['label'=>'Menunggu Bayar', 'key'=>'pending',  'bg'=>'#fff3cd','color'=>'#856404','icon'=>'fa-clock'],
                ['label'=>'Diproses',       'key'=>'diproses', 'bg'=>'#cff4fc','color'=>'#055160','icon'=>'fa-cog'],
                ['label'=>'Dikirim',        'key'=>'dikirim',  'bg'=>'#cfe2ff','color'=>'#084298','icon'=>'fa-truck'],
                ['label'=>'Selesai',        'key'=>'selesai',  'bg'=>'#d1e7dd','color'=>'#0a3622','icon'=>'fa-check-circle'],
                ['label'=>'Dibatalkan',     'key'=>'batal',    'bg'=>'#f8d7da','color'=>'#842029','icon'=>'fa-times-circle'],
            ];
            foreach ($s_list as $s): ?>
            <div class="col">
                <div class="section-card p-3 text-center">
                    <div class="fw-800" style="font-size:1.6rem;font-weight:800;color:<?= $s['color'] ?>"><?= $status_counts[$s['key']] ?></div>
                    <div style="font-size:.75rem;font-weight:600;color:<?= $s['color'] ?>;background:<?= $s['bg'] ?>;border-radius:20px;padding:2px 10px;display:inline-block;margin-top:4px;">
                        <i class="fas <?= $s['icon'] ?> me-1"></i><?= $s['label'] ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- ── Grafik + Low Stock ── -->
        <div class="row g-4 mb-4">
            <!-- Grafik Pendapatan -->
            <div class="col-lg-8">
                <div class="section-card">
                    <div class="card-header">
                        <h6><i class="fas fa-chart-area me-2 text-success"></i>Pendapatan 7 Hari Terakhir</h6>
                        <span class="badge" style="background:#d1e7dd;color:#0a3622;font-size:.75rem;">Pesanan Selesai</span>
                    </div>
                    <div class="p-4">
                        <canvas id="revenueChart" height="110"></canvas>
                    </div>
                </div>
            </div>

            <!-- Low Stock -->
            <div class="col-lg-4">
                <div class="section-card h-100">
                    <div class="card-header">
                        <h6><i class="fas fa-exclamation-triangle me-2 text-warning"></i>Stok Hampir Habis</h6>
                        <a href="produk.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3" style="font-size:.75rem;">Kelola</a>
                    </div>
                    <div class="p-3">
                        <?php if (count($low_stock) > 0): ?>
                        <?php foreach ($low_stock as $ls): ?>
                        <div class="low-stock-item">
                            <img src="../frontend/images/produk/<?= htmlspecialchars($ls['gambar']) ?>"
                                 class="low-stock-img" alt="<?= htmlspecialchars($ls['nama_produk']) ?>">
                            <div class="flex-grow-1 overflow-hidden">
                                <div class="fw-semibold text-dark small text-truncate"><?= htmlspecialchars($ls['nama_produk']) ?></div>
                                <div class="stok-bar mt-1">
                                    <?php $pct = min(100, ($ls['stok'] / 5) * 100); $col = $ls['stok'] <= 2 ? '#dc3545' : '#ffc107'; ?>
                                    <div class="stok-fill" style="width:<?= $pct ?>%;background:<?= $col ?>;"></div>
                                </div>
                            </div>
                            <span class="badge rounded-pill" style="background:<?= $ls['stok'] <= 2 ? '#f8d7da' : '#fff3cd' ?>;color:<?= $ls['stok'] <= 2 ? '#842029' : '#856404' ?>;font-size:.78rem;">
                                <?= $ls['stok'] ?> pcs
                            </span>
                        </div>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <div class="text-center py-4 text-muted small">
                            <i class="fas fa-check-circle fa-2x text-success mb-2 d-block opacity-50"></i>
                            Semua produk stok aman!
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Pesanan Terbaru ── -->
        <div class="section-card mb-5">
            <div class="card-header">
                <h6><i class="fas fa-shopping-bag me-2" style="color:var(--sage)"></i>Pesanan Terbaru</h6>
                <a href="transaksi.php" class="btn btn-sm rounded-pill px-3" style="background:var(--sage);color:#fff;font-size:.78rem;">Lihat Semua</a>
            </div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Pelanggan</th>
                            <th>Total</th>
                            <th>Metode</th>
                            <th>Tanggal</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pesanan_terbaru as $p): ?>
                        <?php
                            $badgeClass = match($p['status_pesanan']) {
                                'pending'  => 'badge-pending',
                                'diproses' => 'badge-diproses',
                                'dikirim'  => 'badge-dikirim',
                                'selesai'  => 'badge-selesai',
                                default    => 'badge-batal',
                            };
                            $labelMap = ['pending'=>'Menunggu Bayar','diproses'=>'Diproses','dikirim'=>'Dikirim','selesai'=>'Selesai','batal'=>'Dibatalkan'];
                        ?>
                        <tr>
                            <td><span class="fw-bold text-muted">#<?= $p['id'] ?></span></td>
                            <td class="fw-semibold text-dark"><?= htmlspecialchars($p['nama']) ?></td>
                            <td class="fw-bold" style="color:var(--sage)">Rp <?= number_format($p['total_harga'], 0, ',', '.') ?></td>
                            <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($p['metode_pembayaran']) ?></span></td>
                            <td class="text-muted" style="font-size:.82rem;"><?= date('d M Y, H:i', strtotime($p['tanggal_transaksi'])) ?></td>
                            <td><span class="badge-status <?= $badgeClass ?>"><?= $labelMap[$p['status_pesanan']] ?? $p['status_pesanan'] ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (count($pesanan_terbaru) == 0): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4 small">Belum ada pesanan sama sekali.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div><!-- /p-4 -->
</div><!-- /main-content -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const ctx = document.getElementById('revenueChart').getContext('2d');

// Premium Gradient
const gradient = ctx.createLinearGradient(0, 0, 0, 300);
gradient.addColorStop(0, 'rgba(82, 121, 111, 0.4)');
gradient.addColorStop(0.5, 'rgba(82, 121, 111, 0.1)');
gradient.addColorStop(1, 'rgba(82, 121, 111, 0)');

new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?= json_encode($chart_labels) ?>,
        datasets: [{
            label: 'Pendapatan',
            data: <?= json_encode($chart_data) ?>,
            borderColor: '#52796f',
            borderWidth: 3,
            backgroundColor: gradient,
            fill: true,
            tension: 0.45, 
            pointBackgroundColor: '#ffffff',
            pointBorderColor: '#52796f',
            pointBorderWidth: 2,
            pointRadius: <?= count($chart_data) > 30 ? 0 : 4 ?>,
            pointHoverRadius: 6,
            pointHoverBackgroundColor: '#52796f',
            pointHoverBorderColor: '#fff',
            pointHoverBorderWidth: 3,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: 'rgba(255, 255, 255, 0.95)',
                titleColor: '#2f3e46',
                bodyColor: '#2f3e46',
                bodyFont: { weight: 'bold', size: 13 },
                padding: 10,
                borderColor: '#e9ecef',
                borderWidth: 1,
                displayColors: false,
                callbacks: {
                    label: function(context) {
                        return 'Rp ' + context.parsed.y.toLocaleString('id-ID');
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    color: '#f8f9fa',
                    drawBorder: false,
                },
                ticks: {
                    callback: function(value) {
                        if (value >= 1000000) return (value / 1000000) + 'jt';
                        return value.toLocaleString('id-ID');
                    },
                    font: { size: 10 },
                    color: '#adb5bd',
                    maxTicksLimit: 5
                }
            },
            x: {
                grid: { display: false },
                ticks: {
                    font: { size: 10 },
                    color: '#adb5bd'
                }
            }
        }
    }
});
</script>
</body>
</html>
