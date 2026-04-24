<?php
/**
 * Navbar terpusat - include di semua halaman
 * Pastikan session_start() sudah dipanggil sebelum include ini
 */
$current_page = basename($_SERVER['PHP_SELF']);
$inisial_nav  = isset($_SESSION['user_nama']) ? strtoupper(substr($_SESSION['user_nama'], 0, 1)) : '';

// Hitung jumlah item di keranjang
$cart_count = 0;
if (isset($_SESSION['user_id']) && isset($conn)) {
    $stmt_cc = $conn->prepare("SELECT COALESCE(SUM(qty), 0) FROM cart WHERE id_user = ?");
    $stmt_cc->execute([$_SESSION['user_id']]);
    $cart_count = (int)$stmt_cc->fetchColumn();
}
?>
<nav class="navbar navbar-expand-lg navbar-dark navbar-sage sticky-top shadow-sm py-2">
    <div class="container">
        <a class="navbar-brand fw-bold fs-4" href="index.php">
            <i class="fas fa-leaf me-1"></i> XrivaStore
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav ms-auto align-items-center gap-3">
                <li class="nav-item">
                    <a class="nav-link <?= $current_page == 'index.php' ? 'active fw-bold' : '' ?>" href="index.php">Home</a>
                </li>

                <?php if (isset($_SESSION['user_id'])): ?>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page == 'wishlist.php' ? 'active fw-bold' : '' ?>" href="wishlist.php">
                        <i class="fas fa-heart me-1"></i> Wishlist
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link position-relative <?= $current_page == 'cart.php' ? 'active fw-bold' : '' ?>" href="cart.php">
                        <i class="fas fa-shopping-cart me-1"></i> Keranjang
                        <?php if ($cart_count > 0): ?>
                        <span class="position-absolute translate-middle badge rounded-pill"
                              style="top:4px; left:calc(100% - 10px); background:var(--xriva-primary); font-size:0.62rem; padding:3px 6px; min-width:18px;">
                            <?= $cart_count > 99 ? '99+' : $cart_count ?>
                        </span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page == 'history.php' ? 'active fw-bold' : '' ?>" href="history.php">
                        <i class="fas fa-history me-1"></i> Pesanan
                    </a>
                </li>

                <!-- User Dropdown -->
                <li class="nav-item dropdown ms-2 d-flex align-items-center border-start ps-3">
                    <div class="rounded-circle d-flex justify-content-center align-items-center bg-white text-sage-dark fw-bold me-2 shadow-sm"
                         style="width: 35px; height: 35px; font-size: 1rem;">
                        <?= $inisial_nav ?>
                    </div>
                    <a class="nav-link dropdown-toggle fw-bold text-white p-0" href="#" data-bs-toggle="dropdown" aria-expanded="false">
                        <?= htmlspecialchars($_SESSION['user_nama']) ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end mt-3 shadow border-0" style="border-radius: 12px; min-width: 200px;">
                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
                            <li><a class="dropdown-item py-2 fw-bold text-sage-dark" href="admin/dashboard.php">
                                <i class="fas fa-user-shield me-2"></i> Dashboard Admin
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                        <?php endif; ?>
                        <!-- Info akun -->
                        <li class="px-3 py-2">
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-circle d-flex justify-content-center align-items-center fw-bold text-white"
                                     style="width:36px;height:36px;font-size:1rem;background:var(--xriva-primary);flex-shrink:0;">
                                    <?= $inisial_nav ?>
                                </div>
                                <div>
                                    <div class="fw-bold text-dark small"><?= htmlspecialchars($_SESSION['user_nama']) ?></div>
                                    <a href="profile.php" class="text-muted text-decoration-none" style="font-size:0.78rem;">
                                        <i class="fas fa-pen fa-xs me-1"></i>Edit Profil
                                    </a>
                                </div>
                            </div>
                        </li>
                        <li><hr class="dropdown-divider my-1"></li>
                        <li><a class="dropdown-item text-danger py-2 fw-semibold" href="logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i> Logout
                        </a></li>
                    </ul>
                </li>
                <?php else: ?>
                <!-- Tamu: hanya tampilkan tombol Login -->
                <li class="nav-item ms-2">
                    <a class="btn btn-outline-light btn-sm px-3 fw-bold" href="login.php">Login</a>
                </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- ===== GLOBAL TOAST NOTIFICATION ===== -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 9999;">
    <div id="globalToast" class="toast align-items-center text-white border-0 shadow-lg" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body fw-semibold" id="toastMessage">
                <!-- Message goes here -->
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<?php if (isset($_SESSION['toast'])): ?>
<script>
document.addEventListener("DOMContentLoaded", function() {
    var toastEl = document.getElementById('globalToast');
    var msgEl = document.getElementById('toastMessage');
    var toastType = '<?= $_SESSION['toast']['type'] ?>';
    
    // Set color based on type
    if (toastType === 'success') {
        toastEl.classList.add('bg-sage-dark');
        msgEl.innerHTML = '<i class="fas fa-check-circle me-2"></i> <?= addslashes($_SESSION['toast']['message']) ?>';
    } else if (toastType === 'error') {
        toastEl.classList.add('bg-danger');
        msgEl.innerHTML = '<i class="fas fa-exclamation-circle me-2"></i> <?= addslashes($_SESSION['toast']['message']) ?>';
    } else if (toastType === 'warning') {
        toastEl.classList.add('bg-warning', 'text-dark');
        toastEl.classList.remove('text-white');
        msgEl.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i> <?= addslashes($_SESSION['toast']['message']) ?>';
    }
    
    var toast = new bootstrap.Toast(toastEl, { delay: 3500 });
    toast.show();
});
</script>
<?php unset($_SESSION['toast']); endif; ?>
