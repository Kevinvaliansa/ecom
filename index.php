<?php
session_start();
require_once 'backend/config/database.php';

// Logika Tambah ke Wishlist
if (isset($_GET['add_wishlist'])) {
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['toast'] = ['type' => 'error', 'message' => 'Silakan login dulu untuk menyimpan ke Wishlist!'];
        header("Location: login.php");
        exit;
    }
    $id_produk = (int)$_GET['add_wishlist'];
    $id_user = $_SESSION['user_id'];
    $cek_wishlist = $conn->prepare("SELECT * FROM wishlist WHERE id_user = ? AND id_produk = ?");
    $cek_wishlist->execute([$id_user, $id_produk]);
    
    if ($cek_wishlist->rowCount() == 0) {
        $insert = $conn->prepare("INSERT INTO wishlist (id_user, id_produk) VALUES (?, ?)");
        $insert->execute([$id_user, $id_produk]);
        $_SESSION['toast'] = ['type' => 'success', 'message' => 'Produk ditambahkan ke Wishlist!'];
    } else {
        $_SESSION['toast'] = ['type' => 'warning', 'message' => 'Produk sudah ada di Wishlist.'];
    }
    $redirect = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php';
    header("Location: $redirect");
    exit;
}

// Logika Filter Kategori dan Pencarian
$kategori_filter = $_GET['kategori'] ?? '';
$cari = $_GET['cari'] ?? '';

$query = "SELECT * FROM produk WHERE 1=1";
$params = [];

if ($kategori_filter) {
    $query .= " AND kategori = ?";
    $params[] = $kategori_filter;
}
if ($cari) {
    $query .= " AND nama_produk LIKE ?";
    $params[] = "%$cari%";
}
$query .= " ORDER BY id DESC";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$produk_list = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xriva Eyewear - Asisten Belanja AI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="frontend/css/style.css?v=<?= time() ?>">
    <style>
        /* small compatibility utilities kept inline */
        .img-out-of-stock { filter: grayscale(100%); opacity: 0.6; }
        .category-pill { border-radius: 20px; padding: 6px 22px; text-decoration: none; color: #555; background: white; border: 1px solid #ddd; transition: 0.3s; font-weight: 500; white-space: nowrap; }
        .category-pill.active, .category-pill:hover { background: var(--xriva-primary); color: white; border-color: var(--xriva-primary); }
        .kategori-scroll::-webkit-scrollbar { height: 6px; }
        .kategori-scroll::-webkit-scrollbar-thumb { background: #ccc; border-radius: 10px; }
    </style>
</head>
<body class="bg-light">
    <?php include 'frontend/includes/navbar.php'; ?>

    <header class="py-5 mb-5" style="background: linear-gradient(135deg, #f8f9fa 0%, #cad2c5 100%); border-bottom: 1px solid #dee2e6;">
        <div class="container py-4 text-center">
            <h1 class="fw-bold text-dark mb-3" style="letter-spacing: -0.5px;">Koleksi Kacamata Terbaikmu</h1>
            <p class="lead text-secondary mb-4" style="max-width: 600px; margin: 0 auto;">Desain trendi, perlindungan UV maksimal, dan harga yang pas di kantong.</p>

            <!-- Search Bar -->
            <form action="index.php" method="GET" class="d-flex justify-content-center" id="form-search">
                <?php if ($kategori_filter): ?>
                    <input type="hidden" name="kategori" value="<?= htmlspecialchars($kategori_filter) ?>">
                <?php endif; ?>
                <div class="input-group shadow-sm" style="max-width: 520px; border-radius: 50px; overflow: hidden;">
                    <span class="input-group-text bg-white border-0 ps-4">
                        <i class="fas fa-search text-muted"></i>
                    </span>
                    <input type="text" name="cari" id="searchInput"
                           class="form-control border-0 py-3"
                           placeholder="Cari produk... (e.g. kacamata minus)"
                           value="<?= htmlspecialchars($cari) ?>"
                           autocomplete="off">
                    <?php if ($cari): ?>
                    <a href="index.php<?= $kategori_filter ? '?kategori=' . urlencode($kategori_filter) : '' ?>" 
                       class="input-group-text bg-white border-0 text-muted pe-3" title="Hapus pencarian">
                        <i class="fas fa-times"></i>
                    </a>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-sage px-4 fw-bold" style="border-radius: 0 50px 50px 0;">
                        Cari
                    </button>
                </div>
            </form>
            <p class="text-muted small mt-3 mb-0" style="opacity:0.7;">
                <i class="fas fa-tag me-1"></i> Temukan dari <strong><?= count($produk_list) ?>+</strong> koleksi kacamata pilihan
            </p>
        </div>
    </header>

    <section id="produk-terbaru" class="container my-5 pb-5">
        
        <!-- Info hasil pencarian / filter -->
        <?php if ($cari || $kategori_filter): ?>
        <div class="d-flex align-items-center justify-content-between mb-3 px-1">
            <span class="text-muted small">
                <?php if ($cari): ?>
                    Hasil pencarian untuk <strong class="text-dark">"<?= htmlspecialchars($cari) ?>"</strong>
                    <?php if ($kategori_filter): ?> di kategori <strong class="text-dark"><?= htmlspecialchars($kategori_filter) ?></strong><?php endif; ?>
                <?php else: ?>
                    Kategori: <strong class="text-dark"><?= htmlspecialchars($kategori_filter) ?></strong>
                <?php endif; ?>
                &nbsp;(<?= count($produk_list) ?> produk)
            </span>
            <a href="index.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                <i class="fas fa-times me-1"></i> Reset
            </a>
        </div>
        <?php endif; ?>

        <div class="d-flex justify-content-center gap-3 overflow-auto pb-3 mb-4 kategori-scroll">
            <a href="index.php" class="category-pill <?= !$kategori_filter ? 'active' : '' ?>">Semua Koleksi</a>
            <a href="index.php?kategori=Kacamata Gaya" class="category-pill <?= $kategori_filter == 'Kacamata Gaya' ? 'active' : '' ?>">Kacamata Gaya</a>
            <a href="index.php?kategori=Kacamata Minus" class="category-pill <?= $kategori_filter == 'Kacamata Minus' ? 'active' : '' ?>">Kacamata Minus</a>
            <a href="index.php?kategori=Aksesoris" class="category-pill <?= $kategori_filter == 'Aksesoris' ? 'active' : '' ?>">Aksesoris & Kotak</a>
        </div>
        
        <div class="row" id="daftar-produk">
            <?php if(count($produk_list) > 0): ?>
                <?php foreach($produk_list as $p): ?>
                <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                    <div class="product-card">
                        <a href="index.php?add_wishlist=<?= $p['id'] ?>" class="btn btn-light text-danger position-absolute rounded-circle shadow-sm" style="top: 10px; right: 10px; width: 35px; height: 35px; padding: 0; z-index: 10; display: flex; align-items: center; justify-content: center;" title="Simpan ke Wishlist">
                            <i class="far fa-heart"></i>
                        </a>

                        <div class="img-wrap">
                            <a href="detail.php?id=<?= $p['id'] ?>">
                                <img src="frontend/images/produk/<?= htmlspecialchars($p['gambar']) ?>" 
                                    class="<?= ($p['stok'] <= 0) ? 'img-out-of-stock' : '' ?>" 
                                    alt="<?= htmlspecialchars($p['nama_produk']) ?>">
                            </a>
                        </div>

                        <div class="product-info">
                            <span class="category"><?= htmlspecialchars($p['kategori'] ?? 'Kacamata') ?></span>
                            <a href="detail.php?id=<?= $p['id'] ?>" class="text-decoration-none">
                                <h6 class="product-name"><?= htmlspecialchars($p['nama_produk']) ?></h6>
                            </a>
                            
                            <div class="d-flex justify-content-between align-items-center mt-auto">
                                <div class="product-price">Rp <?= number_format($p['harga'], 0, ',', '.') ?></div>
                                <?php if($p['stok'] > 0): ?>
                                    <a href="cart.php?add_to_cart=<?= $p['id'] ?>&qty=1" class="btn-tambah">
                                        <i class="fas fa-plus me-1"></i> Tambah
                                    </a>
                                <?php else: ?>
                                    <span class="badge bg-danger">Habis</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center text-muted py-5">
                    <i class="fas fa-glasses fa-4x mb-3" style="color: #e8f0ed;"></i>
                    <p>Produk tidak ditemukan.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <footer class="text-white text-center py-4 mt-5" style="background-color: var(--xriva-dark);">
        <div class="container">
            <h5 class="fw-bold mb-2"><i class="fas fa-glasses"></i> Xriva Eyewear</h5>
            <p class="mb-2 small">&copy; <?= date('Y') ?> Xriva Eyewear. All Rights Reserved.</p>
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
        #chatbot-toggle { position: fixed !important; bottom: 30px !important; right: 30px !important; width: 60px !important; height: 60px !important; border-radius: 50% !important; border: none !important; background-color: var(--xriva-dark) !important; color: white !important; box-shadow: 0 5px 15px rgba(0,0,0,0.2) !important; cursor: pointer !important; z-index: 99999 !important; display: flex !important; align-items: center !important; justify-content: center !important; transition: 0.3s; }
        #chatbot-toggle:hover { transform: scale(1.1); }
    </style>

    <div id="chatbot-container">
        <div class="chatbot-header">
            <span><i class="fas fa-robot me-2"></i> Xriva Assistant</span>
            <button id="chatbot-close" class="btn btn-sm text-white p-0 border-0 bg-transparent"><i class="fas fa-times fs-5"></i></button>
        </div>
        <div class="chat-body" id="chat-body">
            <div class="bot-message">
                Halo! Saya Asisten Belanja Xriva Eyewear. Ada yang bisa saya bantu mencari kacamata hari ini?
                <div class="chat-suggestions">
                    <button class="btn-suggestion" onclick="sendQuickReply('Rekomendasi kacamata minus')">Kacamata Minus</button>
                    <button class="btn-suggestion" onclick="sendQuickReply('Kacamata gaya untuk jalan-jalan')">Kacamata Gaya</button>
                    <button class="btn-suggestion" onclick="sendQuickReply('Cara pembayaran')">Cara Pembayaran</button>
                </div>
            </div>
        </div>
        <div class="chat-input-area">
            <input type="text" id="chat-input-field" placeholder="Tulis pesan...">
            <button id="chat-send-btn" class="btn btn-sage rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; padding: 0;"><i class="fas fa-paper-plane"></i></button>
        </div>
    </div>
    <button id="chatbot-toggle"><i class="fas fa-comment-dots fa-2x"></i></button>

    <script>
        function sendQuickReply(pesan) { 
            document.getElementById('chat-input-field').value = pesan; 
            sendMessage(); 
        }
        
        function sendMessage() {
            const inputField = document.getElementById('chat-input-field');
            const pesanUser = inputField.value.trim();
            if (pesanUser === "") return;

            const chatBody = document.getElementById('chat-body');
            chatBody.innerHTML += `<div class="user-message">${pesanUser}</div>`;
            inputField.value = ''; 
            chatBody.scrollTop = chatBody.scrollHeight;

            const loadingId = "loading-" + Date.now();
            chatBody.innerHTML += `<div class="bot-message" id="${loadingId}"><i>Mengetik...</i></div>`;
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
            })
            .catch(error => {
                document.getElementById(loadingId).remove();
                chatBody.innerHTML += `<div class="bot-message text-danger">Maaf, koneksi ke asisten terputus.</div>`;
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
                    setTimeout(type, 15); 
                    document.getElementById('chat-body').scrollTop = document.getElementById('chat-body').scrollHeight;
                }
            }
            type();
        }

        document.getElementById('chatbot-toggle').onclick = () => { 
            document.getElementById('chatbot-container').style.display = 'flex'; 
            document.getElementById('chatbot-toggle').style.display = 'none'; 
        };
        document.getElementById('chatbot-close').onclick = () => { 
            document.getElementById('chatbot-container').style.display = 'none'; 
            document.getElementById('chatbot-toggle').style.display = 'flex'; 
        };
        document.getElementById('chat-input-field').onkeypress = (e) => { 
            if(e.key === 'Enter') sendMessage(); 
        };
        document.getElementById('chatbot-send-btn') && (document.getElementById('chat-send-btn').onclick = sendMessage);
    </script>

    <script>
        // ===== ANIMATED SEARCH PLACEHOLDER =====
        const searchInput = document.getElementById('searchInput');
        if (searchInput && !searchInput.value) {
            const hints = [
                'Cari kacamata minus...',
                'Cari kacamata gaya...',
                'Cari aksesoris & kotak...',
                'Cari kacamata baca...'
            ];
            let hi = 0;
            setInterval(() => {
                hi = (hi + 1) % hints.length;
                searchInput.setAttribute('placeholder', hints[hi]);
            }, 2200);
        }
    </script>
</body>
</html>