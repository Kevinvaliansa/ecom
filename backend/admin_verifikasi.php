<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../login.php'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_transaksi']) && isset($_POST['action'])) {
    $id = intval($_POST['id_transaksi']);
    $action = $_POST['action'];

    // CSRF check
    if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['admin_msg'] = ['type'=>'error','text'=>'Token keamanan tidak valid.'];
        header('Location: ../admin/transaksi.php'); exit;
    }

    $stmt = $conn->prepare("SELECT bukti_bayar FROM transaksi WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) {
        $_SESSION['admin_msg'] = ['type'=>'error','text'=>'Transaksi tidak ditemukan.'];
        header('Location: ../admin/transaksi.php'); exit;
    }

    $bukti = $row['bukti_bayar'];

    if ($action === 'approve') {
        // Set approved_by and approved_at for audit trail
        $adminId = $_SESSION['user_id'];
        $update = $conn->prepare("UPDATE transaksi SET status_pesanan = 'diproses', approved_by = ?, approved_at = NOW() WHERE id = ?");
        $ok = $update->execute([$adminId, $id]);
        if ($ok) $_SESSION['admin_msg'] = ['type'=>'success','text'=>'Pembayaran disetujui. Status diubah menjadi Diproses.'];
        else $_SESSION['admin_msg'] = ['type'=>'error','text'=>'Gagal memperbarui status.'];

    } elseif ($action === 'reject') {
        // Hapus file bukti jika ada
        if (!empty($bukti)) {
            $path = __DIR__ . '/../frontend/images/bukti/' . $bukti;
            if (is_file($path)) @unlink($path);
        }

        $reason = isset($_POST['reason']) ? trim($_POST['reason']) : null;
        // Clear approval audit when rejecting; attempt to store reason if kolom tersedia
        try {
            $update = $conn->prepare("UPDATE transaksi SET bukti_bayar = NULL, status_pesanan = 'pending', approved_by = NULL, approved_at = NULL, reject_reason = ? WHERE id = ?");
            $ok = $update->execute([$reason, $id]);
        } catch (PDOException $e) {
            $update = $conn->prepare("UPDATE transaksi SET bukti_bayar = NULL, status_pesanan = 'pending', approved_by = NULL, approved_at = NULL WHERE id = ?");
            $ok = $update->execute([$id]);
        }
        if ($ok) $_SESSION['admin_msg'] = ['type'=>'success','text'=>'Bukti ditolak. Pembeli dapat meng-upload ulang.'];
        else $_SESSION['admin_msg'] = ['type'=>'error','text'=>'Gagal menolak bukti.'];

    } else {
        $_SESSION['admin_msg'] = ['type'=>'error','text'=>'Aksi tidak valid.'];
    }
}

header('Location: ../admin/transaksi.php');
exit;
?>
