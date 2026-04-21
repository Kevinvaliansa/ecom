<?php
require_once '../config/database.php';
header('Content-Type: application/json');

// Menerima pesan dari frontend (Format JSON)
$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data['message'])) {
    echo json_encode(["reply" => "Maaf, pesan tidak terbaca."]);
    exit;
}

$pesan = strtolower(trim($data['message']));
$response = "";

// ==========================================
// LOGIKA "OTAK" CHATBOT (Rule-Based AI)
// ==========================================

// Fitur 1: Sapaan
if (strpos($pesan, 'halo') !== false || strpos($pesan, 'hai') !== false || strpos($pesan, 'pagi') !== false) {
    $response = "Halo! Saya Asisten Belanja AI XrivaStore. Ada yang bisa saya bantu? Kamu bisa tanya soal harga, stok, promo, atau cara belanja.";
} 
// Fitur 2: Lokasi Toko
elseif (strpos($pesan, 'lokasi') !== false || strpos($pesan, 'alamat') !== false || strpos($pesan, 'dimana') !== false) {
    $response = "Toko fisik kami berlokasi di <b>Jl. Margonda Raya No. 100, Depok</b>. Tapi kamu bisa belanja lebih mudah langsung lewat website ini!";
}
// Fitur 3: Jam Operasional
elseif (strpos($pesan, 'jam berapa') !== false || strpos($pesan, 'buka') !== false || strpos($pesan, 'tutup') !== false) {
    $response = "Kami melayani pesanan website 24 jam! Untuk proses pengiriman dilakukan pada jam operasional: <b>Senin-Sabtu (09.00 - 17.00 WIB)</b>.";
}
// Fitur 4: Garansi/Retur
elseif (strpos($pesan, 'garansi') !== false || strpos($pesan, 'retur') !== false || strpos($pesan, 'rusak') !== false) {
    $response = "Jangan khawatir! Semua produk kami bergaransi 7 hari setelah barang diterima. Pastikan kamu merekam video unboxing ya untuk proses klaim.";
}
// Fitur 5: Promo/Diskon
elseif (strpos($pesan, 'promo') !== false || strpos($pesan, 'diskon') !== false || strpos($pesan, 'murah') !== false) {
    $response = "Saat ini kami sedang ada promo <b>Gratis Ongkir</b> untuk wilayah Jabodetabek dengan minimal belanja Rp 100.000! Yuk buruan checkout.";
}
// Fitur 6: Info Restock
elseif (strpos($pesan, 'kapan') !== false || strpos($pesan, 'restock') !== false || strpos($pesan, 'lama') !== false) {
    $response = "Untuk produk yang stoknya sedang kosong, biasanya kami akan melakukan restock dalam <b>3-5 hari kerja</b>. Pantau terus katalog kami ya!";
}
// Fitur 7: Cara Beli / Checkout
elseif (strpos($pesan, 'cara beli') !== false || strpos($pesan, 'checkout') !== false || strpos($pesan, 'bayar') !== false) {
    $response = "Gampang banget! <br>1. Pastikan kamu sudah Login. <br>2. Klik tombol 'Tambah' pada produk di Beranda. <br>3. Buka menu Keranjang di atas. <br>4. Klik 'Lanjut Pembayaran' dan pilih metode transfer atau COD.";
} 
// Fitur 8: Rekomendasi Produk
elseif (strpos($pesan, 'rekomendasi') !== false || strpos($pesan, 'terbaru') !== false) {
    // Ambil 2 produk terbaru dari database
    $stmt = $conn->query("SELECT nama_produk, harga FROM produk ORDER BY id DESC LIMIT 2");
    $produk = $stmt->fetchAll();
    
    if (count($produk) > 0) {
        $response = "Ini dia beberapa rekomendasi produk terbaik kami saat ini:<br>";
        foreach ($produk as $p) {
            $response .= "- <b>" . $p['nama_produk'] . "</b> (Rp " . number_format($p['harga'], 0, ',', '.') . ")<br>";
        }
        $response .= "Tertarik? Silakan cek langsung di katalog beranda ya!";
    } else {
        $response = "Maaf, saat ini belum ada produk baru di toko kami.";
    }
}
// Fitur 9: Cek Harga & Stok (Dinamis dari Database)
elseif (strpos($pesan, 'harga') !== false || strpos($pesan, 'stok') !== false || strpos($pesan, 'ada') !== false) {
    // Mencari apakah user menyebutkan nama produk di database
    $stmt = $conn->query("SELECT nama_produk, harga, stok FROM produk");
    $semua_produk = $stmt->fetchAll();
    $ditemukan = false;

    foreach ($semua_produk as $p) {
        $nama_produk_kecil = strtolower($p['nama_produk']);
        // Cek jika nama produk ada di dalam kalimat yang diketik user
        if (strpos($pesan, $nama_produk_kecil) !== false) {
            $status_stok = ($p['stok'] > 0) ? "stoknya sisa <b>" . $p['stok'] . " pcs</b>" : "<b>sedang kosong (Habis)</b>";
            $response = "Untuk produk <b>" . $p['nama_produk'] . "</b>, harganya <b>Rp " . number_format($p['harga'], 0, ',', '.') . "</b> dan saat ini " . $status_stok . ".";
            $ditemukan = true;
            break; // Hentikan pencarian jika produk sudah ketemu
        }
    }

    if (!$ditemukan) {
        $response = "Maaf, saya tidak menemukan produk yang kamu maksud. Bisa sebutkan nama produknya secara spesifik? (Contoh: 'Berapa harga kursi?')";
    }
} 
// Fitur 10: Fallback (Jika bot tidak mengerti)
else {
    $response = "Maaf, saya belum mengerti maksudmu. Coba tanyakan hal lain seperti: <br>- <i>'Rekomendasi produk'</i> <br>- <i>'Dimana lokasi toko?'</i> <br>- <i>'Berapa harga kursi?'</i>";
}

if (strpos($pesan, 'rekomendasi') !== false) {
    $stmt = $conn->query("SELECT nama_produk, harga FROM produk ORDER BY harga ASC LIMIT 2");
    $items = $stmt->fetchAll();
    $reply = "Berikut rekomendasi produk terbaik buat kamu: <br>";
    foreach($items as $it) {
        $reply .= "- <b>" . $it['nama_produk'] . "</b> (Rp " . number_format($it['harga'],0,',','.') . ")<br>";
    }
    $response = $reply . " Mau saya bantu masukkan ke keranjang?";
}

// Mengembalikan jawaban ke frontend dalam format JSON
echo json_encode(["reply" => $response]);
?>