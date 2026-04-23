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
// FUNGSI DATABASE
// ===================================================================
function getProdukByKategori($conn, $kategori) {
    $stmt = $conn->prepare("SELECT nama_produk, harga FROM produk WHERE kategori LIKE ? AND stok > 0 LIMIT 5");
    $stmt->execute(["%$kategori%"]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($items) > 0) {
        $list = "Untuk kategori <b>$kategori</b>, Xriva punya koleksi ini:<br><ul>";
        foreach ($items as $item) {
            $list .= "<li>" . $item['nama_produk'] . " (Rp " . number_format($item['harga'], 0, ',', '.') . ")</li>";
        }
        $list .= "</ul>Cek selengkapnya di menu filter ya! 😊";
        return $list;
    }
    return "Maaf kak, stok untuk kategori <b>$kategori</b> lagi kosong.";
}

// ===================================================================
// KNOWLEDGE BASE & FUZZY LOGIC
// ===================================================================
$intents = [
    "sapaan" => ["halo", "hai", "pagi", "siang", "sore", "malam", "p", "permisi", "oi"],
    "kacamata_minus" => ["minus", "rabun", "lensa", "silinder", "baca", "penglihatan"],
    "kacamata_gaya" => ["gaya", "fashion", "hitam", "jalan", "pantai", "sunglasses", "kece", "keren"],
    "pembayaran" => ["bayar", "transfer", "cod", "dana", "ovo", "gopay", "rekening", "harga"],
    "apresiasi" => ["mantap", "oke", "ok", "sip", "thanks", "terima", "kasih", "bagus", "keren", "siap"],
    "random_tanya" => ["siapa", "kamu", "apa", "nama", "pencipta", "buat"]
];

$best_intent = "unknown";
$highest_score = 0;
$threshold = 2; // Batas toleransi typo (jumlah karakter yang salah)

foreach ($words as $uWord) {
    // Abaikan kata yang terlalu pendek (seperti 'di', 'ke', 'ya')
    if (strlen($uWord) < 2) continue; 

    foreach ($intents as $intent_name => $keywords) {
        foreach ($keywords as $key) {
            // Cek kecocokan persis
            if ($uWord == $key) {
                $score = 10; 
            } else {
                // Cek Levenshtein Distance untuk menangani TYPO
                $dist = levenshtein($uWord, $key);
                // Jika jarak typo kecil, anggap cocok
                $score = ($dist <= $threshold) ? (5 - $dist) : 0;
            }

            if ($score > $highest_score) {
                $highest_score = $score;
                $best_intent = $intent_name;
            }
        }
    }
}

// ===================================================================
// RESPONSE ENGINE
// ===================================================================
$reply = "";

if ($highest_score >= 3) { // Minimal skor untuk dianggap valid
    switch ($best_intent) {
        case "sapaan":
            $reply = "Halo kak! Ada yang bisa Asisten Xriva bantu hari ini? Ingin cari kacamata gaya atau minus?";
            break;
        case "kacamata_minus":
            $reply = getProdukByKategori($conn, "Kacamata Minus");
            break;
        case "kacamata_gaya":
            $reply = getProdukByKategori($conn, "Kacamata Gaya");
            break;
        case "pembayaran":
            $reply = "Pembayaran di Xriva bisa via <b>Transfer (BCA/Mandiri)</b>, <b>Dana/OVO</b>, atau <b>COD</b> biar aman kak! 💳";
            break;
        case "apresiasi":
            $reply = "Sama-sama kak! 😊 Senang bisa membantu. Ada lagi yang ingin ditanyakan?";
            break;
        case "random_tanya":
            $reply = "Aku adalah AI Asisten Xriva Eyewear. Tugasku membantumu mencari kacamata terbaik! 😎";
            break;
    }
} else {
    $reply = "Waduh, aku kurang paham maksudnya kak. 😅 Coba pakai kata kunci seperti 'kacamata gaya', 'kacamata minus', atau 'cara bayar'.";
}

echo json_encode(["reply" => $reply]);
?>