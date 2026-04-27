<?php
session_start();
require_once '../config/database.php';

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->message) || empty(trim($data->message))) {
    echo json_encode(["reply" => "Pesan tidak boleh kosong ya kak. 😊"]);
    exit;
}

$pesanUser = strtolower(trim($data->message));
$pesanUser = preg_replace('/[^\w\s]/', '', $pesanUser); 
$words = explode(" ", $pesanUser);

// ===================================================================
// FUNGSI DATABASE TINGKAT LANJUT (OPSI A)
// ===================================================================
function getProdukByKategori($conn, $kategori) {
    $stmt = $conn->prepare("SELECT id, nama_produk, harga, harga_coret, gambar FROM produk WHERE kategori LIKE ? AND stok > 0 LIMIT 4");
    $stmt->execute(["%$kategori%"]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($items) > 0) {
        $list = "Rekomendasi <b>$kategori</b> untukmu:<br><div class='mt-2 d-flex flex-column gap-2'>";
        foreach ($items as $item) {
            $harga = "Rp " . number_format($item['harga'], 0, ',', '.');
            if(isset($item['harga_coret']) && $item['harga_coret'] > 0) {
                $harga .= " <span style='font-size:0.7rem; text-decoration:line-through; color:#999;'>Rp " . number_format($item['harga_coret'], 0, ',', '.') . "</span>";
            }
            $img_path = 'frontend/images/produk/' . htmlspecialchars($item['gambar']);
            $url = 'detail.php?id=' . $item['id'];
            
            $list .= "<a href='$url' class='text-decoration-none text-dark d-flex align-items-center bg-white p-2 border rounded shadow-sm bot-product-card' style='transition:0.2s;'>";
            $list .= "<img src='$img_path' style='width:50px; height:50px; object-fit:cover; border-radius:6px; margin-right:10px; border:1px solid #eee;'>";
            $list .= "<div style='line-height:1.2;'><b style='font-size:0.85rem; color:var(--xriva-primary);'>" . htmlspecialchars($item['nama_produk']) . "</b><br><small class='fw-bold text-sage-dark'>$harga</small></div>";
            $list .= "</a>";
        }
        $list .= "</div><div class='mt-2' style='font-size:0.8rem;'>Klik gambar untuk lihat detailnya ya! 😊</div>";
        return $list;
    }
    return "Maaf kak, stok untuk kategori <b>$kategori</b> lagi kosong.";
}

function getKeranjangUser($conn, $user_id) {
    if (!$user_id) return "Kakak belum login nih. Silakan <a href='login.php'>Login</a> dulu buat ngecek keranjang ya! 😉";
    
    $stmt = $conn->prepare("SELECT p.nama_produk, c.qty, p.harga FROM cart c JOIN produk p ON c.id_produk = p.id WHERE c.id_user = ?");
    $stmt->execute([$user_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($items) > 0) {
        $total = 0;
        $list = "Ini isi keranjang kakak sekarang:<br><ul class='mb-2 ps-3'>";
        foreach ($items as $item) {
            $sub = $item['harga'] * $item['qty'];
            $total += $sub;
            $list .= "<li>" . htmlspecialchars($item['nama_produk']) . " (x".$item['qty'].")</li>";
        }
        $list .= "</ul>Total estimasi: <b>Rp " . number_format($total, 0, ',', '.') . "</b>.<br>Mau <a href='cart.php' class='btn btn-sm btn-sage mt-2 text-white'>Buka Keranjang</a>?";
        return $list;
    }
    return "Keranjang kakak masih kosong melompong nih. Yuk cari kacamata incaran di beranda! 🛒";
}

function getStatusPesanan($conn, $user_id) {
    if (!$user_id) return "Kakak belum login. Silakan <a href='login.php'>Login</a> dulu buat ngecek status pesanan ya! 😉";
    
    $stmt = $conn->prepare("SELECT id, tanggal_transaksi, total_harga, status_pesanan FROM transaksi WHERE id_user = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$user_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($order) {
        $badge = "bg-secondary";
        if($order['status_pesanan'] == 'pending') $badge = "bg-warning text-dark";
        if($order['status_pesanan'] == 'diproses') $badge = "bg-info text-dark";
        if($order['status_pesanan'] == 'dikirim') $badge = "bg-primary";
        if($order['status_pesanan'] == 'selesai') $badge = "bg-success";
        
        return "Pesanan terakhir kakak (ID: <b>#ORD-".$order['id']."</b>) saat ini berstatus: <br><span class='badge $badge mt-1 mb-2'>" . strtoupper($order['status_pesanan']) . "</span><br>Total tagihan: Rp " . number_format($order['total_harga'], 0, ',', '.') . ".<br>Detail lengkapnya bisa dicek di menu <a href='history.php'>Riwayat Belanja</a> ya kak!";
    }
    return "Kakak belum pernah membuat pesanan di Xriva. Yuk belanja kacamata pertamamu sekarang! 😎";
}

// ===================================================================
// KNOWLEDGE BASE & FUZZY LOGIC
// ===================================================================
$intents = [
    "sapaan" => ["halo", "hai", "pagi", "siang", "sore", "malam", "p", "permisi", "oi", "hei", "hello"],
    "kacamata_minus" => ["minus", "rabun", "lensa", "silinder", "baca", "penglihatan", "silindris"],
    "kacamata_gaya" => ["gaya", "fashion", "hitam", "jalan", "pantai", "sunglasses", "kece", "keren"],
    "pembayaran" => ["bayar", "transfer", "cod", "dana", "ovo", "gopay", "rekening", "harga", "rekeningnya", "cash"],
    "cek_keranjang" => ["keranjang", "cart", "belanjaan", "troli", "isi", "pesananku", "orderan", "belanja"],
    "cek_pesanan" => ["pesanan", "status", "lacak", "resi", "dikirim", "sampai", "paket", "posisi"],
    "rekomendasi" => ["rekomen", "rekomendasi", "saran", "bagus", "cocok", "bingung", "pilih", "cari", "produk"],
    "apresiasi" => ["mantap", "oke", "ok", "sip", "thanks", "terima", "kasih", "keren", "siap", "makasih", "yoi"],
    "random_tanya" => ["siapa", "kamu", "apa", "nama", "pencipta", "buat", "bot", "ngerti", "bisa", "ai"]
];

$best_intent = "unknown";
$highest_score = 0;
$intent_scores = [];

foreach ($words as $uWord) {
    if (strlen($uWord) < 2) continue; 

    foreach ($intents as $intent_name => $keywords) {
        if (!isset($intent_scores[$intent_name])) {
            $intent_scores[$intent_name] = 0;
        }

        $best_score_for_this_word = 0;

        foreach ($keywords as $key) {
            // Cek kecocokan persis
            if ($uWord === $key) {
                $best_score_for_this_word = 10;
                break; // Jika persis, tidak perlu cek keyword lain di intent yang sama untuk kata ini
            } else {
                // Hanya gunakan Levenshtein untuk kata yang cukup panjang agar tidak false-positive
                if (strlen($uWord) >= 4 && strlen($key) >= 4) {
                    $dist = levenshtein($uWord, $key);
                    if ($dist <= 2) {
                        $score = (5 - $dist);
                        if ($score > $best_score_for_this_word) {
                            $best_score_for_this_word = $score;
                        }
                    }
                }
            }
        }
        $intent_scores[$intent_name] += $best_score_for_this_word;
    }
}

// Cari intent dengan total skor tertinggi
foreach ($intent_scores as $intent_name => $score) {
    if ($score > $highest_score) {
        $highest_score = $score;
        $best_intent = $intent_name;
    }
}

// ===================================================================
// RESPONSE ENGINE
// ===================================================================
$reply = "";

if ($highest_score >= 3) { // Minimal skor diturunkan sedikit agar typo 1 huruf tetap lolos
    $user_id = $_SESSION['user_id'] ?? null;
    $nama_user = isset($_SESSION['user_nama']) ? explode(" ", $_SESSION['user_nama'])[0] : "kak";

    switch ($best_intent) {
        case "sapaan":
            $reply = "Halo $nama_user! Ada yang bisa Asisten Xriva bantu hari ini? Ingin cari kacamata gaya, kacamata minus, atau mau cek pesanan?";
            break;
        case "kacamata_minus":
            $reply = getProdukByKategori($conn, "Kacamata Minus");
            break;
        case "kacamata_gaya":
            $reply = getProdukByKategori($conn, "Kacamata Gaya");
            break;
        case "pembayaran":
            $reply = "Pembayaran di Xriva bisa via <b>Transfer (BCA/Mandiri)</b>, <b>Dana/OVO</b>, atau <b>COD (Bayar di Tempat)</b> biar aman $nama_user! 💳";
            break;
        case "cek_keranjang":
            $reply = getKeranjangUser($conn, $user_id);
            break;
        case "cek_pesanan":
            $reply = getStatusPesanan($conn, $user_id);
            break;
        case "rekomendasi":
            if (strpos($pesanUser, "minus") !== false || strpos($pesanUser, "rabun") !== false) {
                $reply = getProdukByKategori($conn, "Kacamata Minus");
            } elseif (strpos($pesanUser, "gaya") !== false || strpos($pesanUser, "hitam") !== false) {
                $reply = getProdukByKategori($conn, "Kacamata Gaya");
            } else {
                $reply = "Xriva punya koleksi <a href='index.php?kategori=Kacamata Gaya'>Kacamata Gaya</a> untuk jalan-jalan santai, dan <a href='index.php?kategori=Kacamata Minus'>Kacamata Minus</a> untuk membantu penglihatanmu. $nama_user lagi butuh yang mana nih?";
            }
            break;
        case "apresiasi":
            $reply = "Sama-sama $nama_user! 😊 Senang banget bisa ngebantu. Ada lagi yang mau ditanyain?";
            break;
        case "random_tanya":
            $reply = "Aku adalah AI Asisten Xriva Eyewear. Tugasku bantu $nama_user cari kacamata dan ngecek status order! 😎";
            break;
    }
} else {
    $reply = "Waduh, aku kurang paham maksudnya nih. 😅 Boleh pakai kata kunci yang lebih jelas? Contohnya: <b>'kacamata gaya'</b>, <b>'cek keranjang'</b>, atau <b>'status pesanan'</b>.";
}

echo json_encode(["reply" => $reply]);
?>