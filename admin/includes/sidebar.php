<?php
/**
 * Shared Admin Sidebar
 * Set $active_page before including: 'dashboard' | 'produk' | 'transaksi' | 'laporan'
 * Set $pending_count for order badge (optional)
 */
$pending_count = $pending_count ?? 0;
$active_page   = $active_page   ?? '';
$admin_name    = $_SESSION['user_nama'] ?? 'Admin';
$admin_initial = strtoupper(substr($admin_name, 0, 1));
?>
<!-- ===== SHARED ADMIN SIDEBAR ===== -->
<div class="sidebar">
    <div class="sidebar-brand">
        <h4><i class="fas fa-leaf me-2" style="color:#cad2c5"></i>XrivaStore</h4>
        <small>Admin Panel</small>
    </div>
    <nav class="sidebar-nav">
        <div class="nav-label">Menu Utama</div>
        <a class="nav-link <?= $active_page=='dashboard' ? 'active' : '' ?>" href="dashboard.php">
            <i class="fas fa-th-large"></i> Dashboard
        </a>
        <a class="nav-link <?= $active_page=='produk' ? 'active' : '' ?>" href="produk.php">
            <i class="fas fa-box-open"></i> Kelola Produk
        </a>
        <a class="nav-link <?= $active_page=='transaksi' ? 'active' : '' ?>" href="transaksi.php">
            <i class="fas fa-shopping-bag"></i> Pesanan Masuk
            <?php if ($pending_count > 0): ?>
            <span class="badge rounded-pill ms-auto" style="background:rgba(255,193,7,.9);color:#333;font-size:.7rem;"><?= $pending_count ?></span>
            <?php endif; ?>
        </a>
        <div class="nav-label">Laporan</div>
        <a class="nav-link <?= $active_page=='laporan' ? 'active' : '' ?>" href="laporan.php">
            <i class="fas fa-chart-line"></i> Laporan Penjualan
        </a>
    </nav>
    <div class="sidebar-footer">
        <a class="nav-link logout-link" href="../logout.php">
            <i class="fas fa-sign-out-alt"></i> Keluar
        </a>
    </div>
</div>

<!-- Sidebar Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inject toggle button into topbar
    const topbar = document.querySelector('.topbar');
    if (topbar) {
        const toggleBtn = document.createElement('button');
        toggleBtn.className = 'menu-toggle';
        toggleBtn.innerHTML = '<i class="fas fa-bars"></i>';
        
        // Find the first child of topbar (usually the title container)
        const firstChild = topbar.firstElementChild;
        if (firstChild) {
            // Make first child flex to align toggle and title
            firstChild.style.display = 'flex';
            firstChild.style.alignItems = 'center';
            firstChild.insertBefore(toggleBtn, firstChild.firstChild);
        }
    }

    // Toggle logic
    const toggle = document.querySelector('.menu-toggle');
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.getElementById('sidebarOverlay');

    if (toggle && sidebar && overlay) {
        function toggleSidebar() {
            sidebar.classList.toggle('show');
            overlay.classList.toggle('show');
        }

        toggle.addEventListener('click', toggleSidebar);
        overlay.addEventListener('click', toggleSidebar);
    }
});
</script>
