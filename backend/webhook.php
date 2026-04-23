<?php
require_once 'config/database.php';

// --- PENGATURAN KEAMANAN ---
// Gunakan Server Key Sandbox untuk tahap pengembangan (PI)
// JANGAN masukkan key asli di sini jika ingin di-upload ke GitHub
$serverKey = 'ISI_SERVER_KEY_SANDBOX_ANDA'; 

// Set konfigurasi Midtrans
// Pastikan kamu sudah menginstal library Midtrans lewat composer
// require_once __DIR__ . '/../vendor/autoload.php'; 

// Jika tidak pakai composer, pastikan path-nya benar
// \Midtrans\Config::$serverKey = $serverKey;
// \Midtrans\Config::$isProduction = false; // Set false untuk Sandbox
// \Midtrans\Config::$isSanitized = true;
// \Midtrans\Config::$is3ds = true;

$notification = json_decode(file_get_contents('php://input'), true);

if ($notification) {
    $transaction = $notification['transaction_status'];
    $type = $notification['payment_type'];
    $order_id = $notification['order_id'];
    $fraud = $notification['fraud_status'];

    // Ambil ID Transaksi asli dari order_id (Format: TRX-timestamp-ID)
    $parts = explode('-', $order_id);
    $id_transaksi = end($parts);

    if ($transaction == 'capture') {
        if ($type == 'credit_card') {
            if ($fraud == 'challenge') {
                $status = 'pending';
            } else {
                $status = 'diproses';
            }
        }
    } else if ($transaction == 'settlement') {
        $status = 'diproses';
    } else if ($transaction == 'pending') {
        $status = 'pending';
    } else if ($transaction == 'deny' || $transaction == 'expire' || $transaction == 'cancel') {
        $status = 'batal';
    }

    // Update status di database kamu
    if (isset($status)) {
        $stmt = $conn->prepare("UPDATE transaksi SET status_pesanan = ? WHERE id = ?");
        $stmt->execute([$status, $id_transaksi]);
    }
}

// Balas dengan HTTP 200 agar Midtrans tahu pesan sudah sampai
http_response_code(200);
echo "OK";