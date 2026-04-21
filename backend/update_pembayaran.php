<?php
session_start();
require_once 'config/database.php';

if (isset($_GET['id']) && isset($_GET['status'])) {
    $id_transaksi = $_GET['id'];
    $status = $_GET['status'];

    if ($status == 'sukses') {
        // Ubah status jadi diproses dan isi bukti bayar otomatis sebagai 'Midtrans_Success'
        $update = $conn->prepare("UPDATE transaksi SET status_pesanan = 'diproses', bukti_bayar = 'Midtrans_Success' WHERE id = ?");
        $update->execute([$id_transaksi]);
    }
}

// Redirect ke halaman riwayat
header("Location: ../history.php");
exit;
?>