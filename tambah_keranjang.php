<?php
session_start();
require_once 'backend/config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_produk'])) {
    $id_user = $_SESSION['user_id'];
    $id_produk = $_POST['id_produk'];
    $qty = (int)$_POST['qty'];
    $varian = trim($_POST['varian'] ?? '');

    // Cek apakah produk sudah ada di keranjang user ini dengan varian yang sama
    $cek = $conn->prepare("SELECT * FROM cart WHERE id_user = ? AND id_produk = ? AND varian = ?");
    $cek->execute([$id_user, $id_produk, $varian]);
    
    if ($cek->rowCount() > 0) {
        // Jika sudah ada, tambahkan QTY-nya
        $update = $conn->prepare("UPDATE cart SET qty = qty + ? WHERE id_user = ? AND id_produk = ? AND varian = ?");
        $update->execute([$qty, $id_user, $id_produk, $varian]);
    } else {
        // Jika belum ada, masukkan sebagai item baru
        $insert = $conn->prepare("INSERT INTO cart (id_user, id_produk, qty, varian) VALUES (?, ?, ?, ?)");
        $insert->execute([$id_user, $id_produk, $qty, $varian]);
    }
}

// Kembalikan ke halaman keranjang
header("Location: cart.php");
exit;
?>