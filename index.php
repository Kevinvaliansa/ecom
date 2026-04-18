<?php
session_start();
require_once 'backend/config/database.php';

if (isset($_GET['add_wishlist'])) {
    if (!isset($_SESSION['user_id'])) {
        echo "<script>alert('Silakan login dulu untuk menyimpan ke Wishlist!'); window.location.href='login.php';</script>";
        exit;
    }
    $id_produk = $_GET['add_wishlist'];
    $id_user = $_SESSION['user_id'];
    $cek_wishlist = $conn->prepare("SELECT * FROM wishlist WHERE id_user = ? AND id_produk = ?");
    $cek_wishlist->execute([$id_user, $id_produk]);
    
    if ($cek_wishlist->rowCount() == 0) {
        $insert = $conn->prepare("INSERT INTO wishlist (id_user, id_produk) VALUES (?, ?)");
        $insert->execute([$id_user, $id_produk]);
    }
    header("Location: index.php#produk-terbaru");
    exit;
}

// Ambil 4 produk terbaru dari database (Default awal)
$stmt = $conn->query("SELECT * FROM produk ORDER BY id DESC LIMIT 4");
$produk_list = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Commerce Sage & AI Assistant</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="frontend/css/style.css">
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark navbar-sage sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php"><i class="fas fa-leaf"></i> XrivaStore</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <form class="d-flex mx-auto my-2 my-lg-0" style="width: 40%;" onsubmit="return false;">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" id="live-search" class="form-control border-start-0" placeholder="Cari kursi, meja, piring...">
                    </div>
                </form>

                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item"><a class="nav-link active" href="index.php">Home</a></li>
                    
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <li class="nav-item"><a class="nav-link" href="wishlist.php"><i class="fas fa-heart"></i> Wishlist</a></li>
                        <li class="nav-item"><a class="nav-link" href="cart.php"><i class="fas fa-shopping-cart"></i> Keranjang</a></li>
                        <li class="nav-item"><a class="nav-link" href="history.php"><i class="fas fa-history"></i> Pesanan</a></li>
                        <li class="nav-item dropdown ms-2">
                            <a class="nav-link dropdown-toggle fw-bold text-white" href="#" data-bs-toggle="dropdown">
                                Halo, <?= htmlspecialchars($_SESSION['user_nama']) ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="cart.php"><i class="fas fa-shopping-cart"></i> Keranjang</a></li>
                        <li class="nav-item ms-2"><a class="btn btn-outline-light btn-sm fw-bold px-3" href="login.php">Login</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <header class="bg-sage-light py-5 text-center">
        <div class="container">
            <h1 class="fw-bold text-sage-dark">Koleksi Terbaru Telah Tiba</h1>
            <p class="lead text-secondary">Belanja lebih mudah dengan panduan Asisten AI kami.</p>
            <a href="#produk-terbaru" class="btn btn-sage btn-lg mt-3 shadow-sm">Mulai Belanja</a>
        </div>
    </header>

    <section id="produk-terbaru" class="container my-5">
        <h3 class="text-center mb-4 text-sage-dark fw-bold">Katalog Produk</h3>
        
        <div class="row" id="daftar-produk">
            <?php if(count($produk_list) > 0): ?>
                <?php foreach($produk_list as $p): ?>
                <div class="col-md-3 mb-4">
                    <div class="card h-100 shadow-sm border-0 position-relative">
                        <img src="frontend/images/produk/<?= htmlspecialchars($p['gambar']) ?>" class="card-img-top" style="height: 200px; object-fit: cover;">
                        <div class="card-body text-center d-flex flex-column">
                            <h6 class="card-title fw-bold text-truncate"><?= htmlspecialchars($p['nama_produk']) ?></h6>
                            <p class="card-text text-sage-dark fw-bold mb-1">Rp <?= number_format($p['harga'], 0, ',', '.') ?></p>
                            
                            <?php if($p['stok'] > 0): ?>
                                <small class="text-muted mb-3">Tersisa: <?= $p['stok'] ?> Pcs</small>
                            <?php else: ?>
                                <small class="text-danger fw-bold mb-3">Stok Habis</small>
                            <?php endif; ?>
                            
                            <div class="d-flex justify-content-between mt-auto gap-2">
                                <form action="cart.php" method="POST" class="w-100">
                                    <input type="hidden" name="id_produk" value="<?= $p['id'] ?>">
                                    <input type="hidden" name="qty" value="1">
                                    <button type="submit" name="add_to_cart" class="btn btn-sage btn-sm w-100" <?= ($p['stok'] <= 0) ? 'disabled' : '' ?>>
                                        <i class="fas fa-cart-plus"></i> Tambah
                                    </button>
                                </form>
                                <a href="index.php?add_wishlist=<?= $p['id'] ?>" class="btn btn-outline-danger btn-sm flex-shrink-0" title="Simpan ke Wishlist">
                                    <i class="far fa-heart"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center text-muted py-5">
                    <i class="fas fa-box-open fa-3x mb-3 text-sage-light"></i>
                    <p>Belum ada produk. Silakan tambahkan dari dashboard admin.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <footer class="text-white text-center py-4 mt-5" style="background-color: var(--xriva-dark);">
        <div class="container">
            <h5 class="fw-bold mb-2"><i class="fas fa-leaf"></i> XrivaShop</h5>
            <p class="mb-1 small">Belanja mudah, aman, dan didukung oleh Asisten AI Berkembang.</p>
            <p class="mb-2 small">&copy; <?= date('Y') ?> XrivaShop. All Rights Reserved.</p>
        </div>
    </footer>
    
    <div id="chatbot-container">
        <div class="chatbot-header d-flex justify-content-between align-items-center">
            <span><i class="fas fa-robot"></i> AI Assistant</span>
            <button id="chatbot-close" class="btn btn-sm text-white border-0 bg-transparent"><i class="fas fa-times"></i></button>
        </div>
        <div class="chat-body" id="chat-body">
            <div class="bot-message">Halo! Ada yang bisa saya bantu terkait produk atau keranjang belanja hari ini?</div>
        </div>
        <div class="chat-input">
            <input type="text" id="chat-input-field" placeholder="Ketik pesan...">
            <button id="chat-send-btn"><i class="fas fa-paper-plane"></i></button>
        </div>
    </div>
    <button id="chatbot-toggle" class="btn-sage shadow"><i class="fas fa-comments fa-2x"></i></button>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // --- 1. SCRIPT LIVE SEARCH PRODUCT ---
        const searchInput = document.getElementById('live-search');
        const daftarProduk = document.getElementById('daftar-produk');

        searchInput.addEventListener('input', function() {
            const keyword = searchInput.value;
            
            // Lakukan Fetch ke file pencari di backend
            fetch('backend/search_produk.php?keyword=' + keyword)
            .then(response => response.text())
            .then(data => {
                // Ganti isi daftar produk secara otomatis (tanpa refresh web)
                daftarProduk.innerHTML = data;
            });
        });

        // --- 2. SCRIPT AI CHATBOT (Sama seperti sebelumnya) ---
        const chatContainer = document.getElementById('chatbot-container');
        const btnToggle = document.getElementById('chatbot-toggle');
        const btnClose = document.getElementById('chatbot-close');
        const chatBody = document.getElementById('chat-body');
        const chatInput = document.getElementById('chat-input-field');
        const btnSend = document.getElementById('chat-send-btn');

        btnToggle.addEventListener('click', () => { chatContainer.style.display = 'flex'; btnToggle.style.display = 'none'; });
        btnClose.addEventListener('click', () => { chatContainer.style.display = 'none'; btnToggle.style.display = 'flex'; });

        function typeWriter(text, elementId) {
            const element = document.getElementById(elementId);
            let i = 0; let speed = 15;
            function type() {
                if (i < text.length) {
                    if (text.charAt(i) === '<') {
                        let tag = "";
                        while (text.charAt(i) !== '>' && i < text.length) { tag += text.charAt(i); i++; }
                        tag += '>'; element.innerHTML += tag;
                    } else { element.innerHTML += text.charAt(i); i++; }
                    setTimeout(type, speed); chatBody.scrollTop = chatBody.scrollHeight;
                }
            }
            type();
        }

        function sendMessage() {
            const pesanUser = chatInput.value.trim();
            if (pesanUser === "") return;

            chatBody.innerHTML += `<div style="text-align: right; margin-bottom: 10px;">
                                      <span style="background-color: var(--sage-primary); color: white; padding: 8px 12px; border-radius: 10px; display: inline-block; max-width: 80%; font-size: 0.9em;">${pesanUser}</span>
                                   </div>`;
            chatInput.value = ''; chatBody.scrollTop = chatBody.scrollHeight;

            const tempId = 'bot-' + Date.now();
            chatBody.innerHTML += `<div id="loading" style="text-align: left; margin-bottom: 10px;">
                                      <span style="background-color: #e9ecef; color: #555; padding: 8px 12px; border-radius: 10px; display: inline-block; font-size: 0.9em; font-style: italic;">Sedang mengetik...</span>
                                   </div>`;
            chatBody.scrollTop = chatBody.scrollHeight;

            fetch('backend/chatbot/bot_engine.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message: pesanUser })
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('loading').remove();
                chatBody.innerHTML += `<div style="text-align: left; margin-bottom: 10px;">
                                          <span id="${tempId}" style="background-color: var(--sage-light); color: var(--sage-dark); padding: 8px 12px; border-radius: 10px; display: inline-block; max-width: 80%; font-size: 0.9em;"></span>
                                       </div>`;
                typeWriter(data.reply, tempId);
            });
        }
        chatInput.addEventListener('keypress', (e) => { if (e.key === 'Enter') sendMessage(); });
        btnSend.addEventListener('click', sendMessage);
    </script>
</body>
</html>