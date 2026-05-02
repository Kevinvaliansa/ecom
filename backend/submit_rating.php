<?php
session_start();
require_once __DIR__ . '/config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php'); exit;
}

$user_id = $_SESSION['user_id'];
$id_produk = isset($_POST['id_produk']) ? (int) $_POST['id_produk'] : 0;
$id_transaksi = isset($_POST['id_transaksi']) ? (int) $_POST['id_transaksi'] : 0;
$rating = isset($_POST['rating']) ? (int) $_POST['rating'] : 0;
$review = isset($_POST['review']) ? trim($_POST['review']) : null;

// CSRF check
$redirect = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '../index.php';

if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $_SESSION['rating_msg'] = ['type' => 'error', 'text' => 'Token keamanan tidak valid.'];
    header('Location: ' . $redirect); exit;
}

if ($id_produk <= 0 || $id_transaksi <= 0 || $rating < 1 || $rating > 5) {
    $_SESSION['rating_msg'] = ['type' => 'error', 'text' => 'Data rating tidak valid.'];
    header('Location: ' . $redirect); exit;
}

// Pastikan user pernah membeli produk dan status transaksi 'selesai'
$stmt = $conn->prepare("SELECT COUNT(1) FROM detail_transaksi dt
    JOIN transaksi t ON dt.id_transaksi = t.id
    WHERE t.id_user = ? AND dt.id_produk = ? AND t.status_pesanan = 'selesai'");
$stmt->execute([$user_id, $id_produk]);
$can = $stmt->fetchColumn();

if (!$can) {
    $_SESSION['rating_msg'] = ['type' => 'error', 'text' => 'Hanya pembeli yang sudah selesai transaksi yang dapat memberikan rating.'];
    header('Location: ' . $redirect); exit;
}

try {
    // Insert atau update berdasarkan UNIQUE (id_user, id_produk, id_transaksi)
    $sql = "INSERT INTO ratings (id_user, id_produk, id_transaksi, rating, review, created_at, updated_at)
        VALUES (:id_user, :id_produk, :id_transaksi, :rating, :review, NOW(), NOW())
        ON DUPLICATE KEY UPDATE rating = VALUES(rating), review = VALUES(review), updated_at = NOW()";
    $s = $conn->prepare($sql);
    $s->execute([
        ':id_user' => $user_id,
        ':id_produk' => $id_produk,
        ':id_transaksi' => $id_transaksi,
        ':rating' => $rating,
        ':review' => $review
    ]);

    $_SESSION['rating_msg'] = ['type' => 'success', 'text' => 'Terima kasih! Rating Anda telah disimpan.'];
} catch (PDOException $e) {
    $_SESSION['rating_msg'] = ['type' => 'error', 'text' => 'Terjadi kesalahan saat menyimpan rating.'];
}

header('Location: ' . $redirect);
exit;
?>
