<?php
session_start();
require_once 'backend/config/database.php';

// Logika Tambah ke Wishlist
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

// Ambil produk dari database (Dengan fitur pencarian)
if (isset($_GET['cari']) && $_GET['cari'] != '') {
    $cari = "%" . $_GET['cari'] . "%";
    $stmt = $conn->prepare("SELECT * FROM produk WHERE nama_produk LIKE ? ORDER BY id DESC");
    $stmt->execute([$cari]);
} else {
    $stmt = $conn->query("SELECT * FROM produk ORDER BY id DESC LIMIT 8");
}
$produk_list = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>XrivaStore - E-Commerce Sage & AI Assistant</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="frontend/css/style.css">
    <style>
        :root { --xriva-dark: #4a7c6b; --xriva-primary: #7cb3a1; }
        .navbar-sage { background-color: var(--xriva-dark); }
        .text-sage-dark { color: var(--xriva-dark); }
        .btn-sage { background-color: var(--xriva-primary); color: white; border: none; }
        .btn-sage:hover { background-color: var(--xriva-dark); color: white; }
    </style>
</head>
<body class="bg-light">

    <nav class="navbar navbar-expand-lg navbar-dark navbar-sage sticky-top shadow-sm py-2">
        <div class="container">
            <a class="navbar-brand fw-bold fs-4" href="index.php"><i class="fas fa-leaf"></i> XrivaStore</a>
            
            <form class="d-flex mx-auto d-none d-lg-flex" style="max-width: 450px; width: 100%;" action="index.php" method="GET">
                <div class="input-group">
                    <input class="form-control border-0 shadow-sm" type="search" name="cari" placeholder="Cari kursi, meja, piring..." aria-label="Search" style="border-radius: 20px 0 0 20px;">
                    <button class="btn bg-white border-0 shadow-sm text-muted" type="submit" style="border-radius: 0 20px 20px 0;"><i class="fas fa-search"></i></button>
                </div>
            </form>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center gap-3 mt-3 mt-lg-0">
                    <li class="nav-item"><a class="nav-link active fw-bold d-flex align-items-center" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link d-flex align-items-center" href="wishlist.php"><i class="fas fa-heart me-1"></i> Wishlist</a></li>
                    <li class="nav-item"><a class="nav-link d-flex align-items-center" href="cart.php"><i class="fas fa-shopping-cart me-1"></i> Keranjang</a></li>
                    <li class="nav-item"><a class="nav-link d-flex align-items-center" href="history.php"><i class="fas fa-history me-1"></i> Pesanan</a></li>
                    
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <li class="nav-item dropdown ms-lg-2 d-flex align-items-center border-start-lg ps-lg-3 mt-2 mt-lg-0">
                            <div class="rounded-circle d-flex justify-content-center align-items-center bg-white text-sage-dark fw-bold me-2 shadow-sm" style="width: 35px; height: 35px; font-size: 1rem;">
                                <?= strtoupper(substr($_SESSION['user_nama'], 0, 1)) ?>
                            </div>
                            <a class="nav-link dropdown-toggle fw-bold text-white p-0" href="#" data-bs-toggle="dropdown">
                                <?= htmlspecialchars($_SESSION['user_nama']) ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end mt-3 shadow border-0" style="border-radius: 12px;">
                                <?php if(isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
                                    <li><a class="dropdown-item py-2 fw-bold text-sage-dark" href="admin/produk.php"><i class="fas fa-user-shield me-2"></i> Dashboard Admin</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                <?php endif; ?>
                                <li><a class="dropdown-item py-2" href="profile.php"><i class="fas fa-user-circle text-muted me-2"></i> Profil Saya</a></li>
                                <li><a class="dropdown-item py-2" href="history.php"><i class="fas fa-clipboard-list text-muted me-2"></i> Pesanan Saya</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger py-2" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item ms-lg-2 mt-2 mt-lg-0 d-flex align-items-center">
                            <a href="login.php" class="btn btn-light text-sage-dark fw-bold rounded-pill px-4 shadow-sm">Login</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <header class="py-5 mb-5" style="background: linear-gradient(135deg, #f8f9fa 0%, #cad2c5 100%); border-bottom: 1px solid #dee2e6;">
        <div class="container py-4 text-center">
            <h1 class="fw-bold text-sage-dark mb-3" style="letter-spacing: -0.5px;">Koleksi Terbaru Telah Tiba</h1>
            <p class="lead text-secondary mb-4" style="max-width: 600px; margin: 0 auto;">Penuhi kebutuhan harianmu dengan mudah, aman, dan dapatkan panduan cerdas dari Asisten AI kami.</p>
            <a href="#produk-terbaru" class="btn btn-sage btn-lg px-5 fw-bold shadow-sm rounded-pill">Mulai Belanja</a>
        </div>
    </header>

    <section id="produk-terbaru" class="container my-5 pb-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="text-sage-dark fw-bold m-0">Katalog Produk</h3>
        </div>
        
        <div class="row" id="daftar-produk">
            <?php if(count($produk_list) > 0): ?>
                <?php foreach($produk_list as $p): ?>
                <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                    <div class="card h-100 shadow-sm border-0 position-relative bg-white">
                        <a href="index.php?add_wishlist=<?= $p['id'] ?>" class="btn btn-light text-danger position-absolute rounded-circle shadow-sm" style="top: 10px; right: 10px; width: 35px; height: 35px; padding: 5px; z-index: 2;" title="Simpan ke Wishlist">
                            <i class="far fa-heart mt-1"></i>
                        </a>
                        <div style="overflow: hidden; border-top-left-radius: 16px; border-top-right-radius: 16px;">
                            <a href="detail.php?id=<?= $p['id'] ?>">
                                <img src="frontend/images/produk/<?= htmlspecialchars($p['gambar']) ?>" class="card-img-top" style="height: 220px; object-fit: cover;">
                            </a>
                        </div>
                        <div class="card-body d-flex flex-column p-4">
                            <h6 class="card-title fw-bold text-dark text-truncate mb-1"><?= htmlspecialchars($p['nama_produk']) ?></h6>
                            <h5 class="card-text text-sage-dark fw-bold mb-2">Rp <?= number_format($p['harga'], 0, ',', '.') ?></h5>
                            
                            <p class="text-muted small mb-2"><i class="fas fa-map-marker-alt text-danger me-1"></i> Dikirim dari: <b><?= htmlspecialchars($p['kota_asal'] ?? 'Bekasi') ?></b></p>
                            
                            <?php if($p['stok'] > 0): ?>
                                <small class="text-muted mb-4"><i class="fas fa-box-open me-1"></i> Sisa <?= $p['stok'] ?> Pcs</small>
                            <?php else: ?>
                                <small class="text-danger fw-bold mb-4"><i class="fas fa-times-circle me-1"></i> Stok Habis</small>
                            <?php endif; ?>
                            <div class="mt-auto">
                                <form action="cart.php" method="POST" class="w-100">
                                    <input type="hidden" name="id_produk" value="<?= $p['id'] ?>">
                                    <div class="row g-2 align-items-center">
                                        <div class="col-4">
                                            <input type="number" name="qty" class="form-control text-center fw-bold" value="1" min="1" max="<?= $p['stok'] ?>" <?= ($p['stok'] <= 0) ? 'disabled' : '' ?>>
                                        </div>
                                        <div class="col-8">
                                            <button type="submit" name="add_to_cart" class="btn btn-sage w-100 fw-bold rounded-3" <?= ($p['stok'] <= 0) ? 'disabled' : '' ?>>+ Keranjang</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center text-muted py-5">
                    <i class="fas fa-box-open fa-3x mb-3 text-sage-light"></i>
                    <p>Produk tidak ditemukan.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <footer class="text-white text-center py-4 mt-5" style="background-color: var(--xriva-dark);">
        <div class="container">
            <h5 class="fw-bold mb-2"><i class="fas fa-leaf"></i> XrivaShop</h5>
            <p class="mb-2 small">&copy; <?= date('Y') ?> XrivaShop. All Rights Reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <style>
        #chatbot-container { position: fixed !important; bottom: 100px !important; right: 30px !important; width: 360px !important; height: 500px !important; background-color: #ffffff !important; border-radius: 16px !important; display: none; flex-direction: column !important; box-shadow: 0 15px 35px rgba(0,0,0,0.2) !important; z-index: 99999 !important; border: 1px solid #e0e0e0 !important; overflow: hidden !important; }
        .chatbot-header { background-color: var(--xriva-dark) !important; color: white !important; padding: 15px 20px !important; font-weight: bold !important; display: flex !important; justify-content: space-between !important; align-items: center !important; }
        .chat-body { flex: 1 !important; padding: 15px !important; overflow-y: auto !important; background-color: #f8f9fa !important; display: flex !important; flex-direction: column !important; gap: 12px !important; }
        .bot-message { background-color: #e9ecef !important; color: #333 !important; padding: 10px 15px !important; border-radius: 15px 15px 15px 5px !important; max-width: 85% !important; font-size: 0.9rem !important; align-self: flex-start !important; }
        .user-message { background-color: var(--xriva-primary) !important; color: white !important; padding: 10px 15px !important; border-radius: 15px 15px 5px 15px !important; max-width: 85% !important; font-size: 0.9rem !important; align-self: flex-end !important; }
        .chat-suggestions { display: flex !important; flex-wrap: wrap !important; gap: 8px !important; margin-top: 10px !important; }
        .btn-suggestion { font-size: 0.8rem !important; padding: 6px 12px !important; border-radius: 20px !important; border: 1px solid var(--xriva-primary) !important; background: white !important; color: var(--xriva-dark) !important; cursor: pointer !important; transition: 0.2s !important; }
        .btn-suggestion:hover { background: var(--xriva-primary) !important; color: white !important; }
        .chat-input-area { display: flex !important; padding: 15px !important; background: white !important; border-top: 1px solid #eee !important; gap: 10px !important; }
        .chat-input-area input { flex: 1 !important; border: 1px solid #ddd !important; border-radius: 20px !important; padding: 8px 15px !important; outline: none !important; }
        #chatbot-toggle { position: fixed !important; bottom: 30px !important; right: 30px !important; width: 60px !important; height: 60px !important; border-radius: 50% !important; border: none !important; background-color: var(--xriva-dark) !important; color: white !important; box-shadow: 0 5px 15px rgba(0,0,0,0.2) !important; cursor: pointer !important; z-index: 99999 !important; display: flex !important; align-items: center !important; justify-content: center !important; }
    </style>

    <div id="chatbot-container">
        <div class="chatbot-header">
            <span><i class="fas fa-robot me-2"></i> Xriva Assistant</span>
            <button id="chatbot-close" class="btn btn-sm text-white p-0 border-0 bg-transparent"><i class="fas fa-times fs-5"></i></button>
        </div>
        <div class="chat-body" id="chat-body">
            <div class="bot-message">
                Halo! Saya Asisten Belanja XrivaShop. Ada yang bisa saya bantu?
                <div class="chat-suggestions">
                    <button class="btn-suggestion" onclick="sendQuickReply('Cek status pesanan')">Cek Status Pesanan</button>
                    <button class="btn-suggestion" onclick="sendQuickReply('Rekomendasi produk')">Rekomendasi Produk</button>
                    <button class="btn-suggestion" onclick="sendQuickReply('Cara bayar')">Cara Bayar</button>
                </div>
            </div>
        </div>
        <div class="chat-input-area">
            <input type="text" id="chat-input-field" placeholder="Tulis pesan...">
            <button id="chat-send-btn" class="btn btn-sage rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; padding: 0;"><i class="fas fa-paper-plane"></i></button>
        </div>
    </div>
    <button id="chatbot-toggle"><i class="fas fa-comments fa-2x"></i></button>

    <script>
        function sendQuickReply(pesan) { document.getElementById('chat-input-field').value = pesan; sendMessage(); }
        function sendMessage() {
            const inputField = document.getElementById('chat-input-field');
            const pesanUser = inputField.value.trim();
            if (pesanUser === "") return;

            const chatBody = document.getElementById('chat-body');
            chatBody.innerHTML += `<div class="user-message">${pesanUser}</div>`;
            inputField.value = ''; chatBody.scrollTop = chatBody.scrollHeight;

            const loadingId = "loading-" + Date.now();
            chatBody.innerHTML += `<div class="bot-message" id="${loadingId}"><i>Sedang mengetik...</i></div>`;
            chatBody.scrollTop = chatBody.scrollHeight;

            fetch('backend/chatbot/bot_engine.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message: pesanUser })
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById(loadingId).remove();
                const botMsgId = "bot-" + Date.now();
                chatBody.innerHTML += `<div class="bot-message" id="${botMsgId}"></div>`;
                typeWriter(data.reply, botMsgId);
            });
        }
        function typeWriter(text, elementId) {
            const element = document.getElementById(elementId);
            let i = 0;
            function type() {
                if (i < text.length) {
                    if (text.charAt(i) === '<') {
                        let tag = "";
                        while (text.charAt(i) !== '>' && i < text.length) { tag += text.charAt(i); i++; }
                        tag += '>'; element.innerHTML += tag; i++;
                    } else { element.innerHTML += text.charAt(i); i++; }
                    setTimeout(type, 15); document.getElementById('chat-body').scrollTop = document.getElementById('chat-body').scrollHeight;
                }
            }
            type();
        }

        document.getElementById('chatbot-toggle').onclick = () => { document.getElementById('chatbot-container').style.display = 'flex'; document.getElementById('chatbot-toggle').style.display = 'none'; };
        document.getElementById('chatbot-close').onclick = () => { document.getElementById('chatbot-container').style.display = 'none'; document.getElementById('chatbot-toggle').style.display = 'flex'; };
        document.getElementById('chat-input-field').onkeypress = (e) => { if(e.key === 'Enter') sendMessage(); };
        document.getElementById('chat-send-btn').onclick = sendMessage;
    </script>
</body>
</html>