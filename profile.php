<?php
session_start();
require_once 'backend/config/database.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$id_user = $_SESSION['user_id'];
$pesan_sukses = "";
$pesan_error = "";

// PROSES UPDATE PROFIL & FOTO
if (isset($_POST['update_profile'])) {
    $nama = $_POST['nama'];
    $no_hp = $_POST['no_hp'];
    $alamat = $_POST['alamat'];

    $update = $conn->prepare("UPDATE users SET nama = ?, no_hp = ?, alamat = ? WHERE id = ?");
    if ($update->execute([$nama, $no_hp, $alamat, $id_user])) {
        $_SESSION['user_nama'] = $nama; 
        $pesan_sukses = "Profil berhasil diperbarui!";
    }

    if (isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] == 0) {
        $target_dir = "frontend/images/profil/";
        $file_extension = strtolower(pathinfo($_FILES["foto_profil"]["name"], PATHINFO_EXTENSION));
        $valid_ext = array("jpg", "jpeg", "png");
        
        $file_name = "user_" . $id_user . "_" . time() . "." . $file_extension;
        $target_file = $target_dir . $file_name;

        if (in_array($file_extension, $valid_ext)) {
            if (move_uploaded_file($_FILES["foto_profil"]["tmp_name"], $target_file)) {
                $stmt_foto = $conn->prepare("UPDATE users SET foto = ? WHERE id = ?");
                $stmt_foto->execute([$file_name, $id_user]);
                $pesan_sukses = "Profil dan Foto berhasil diperbarui!";
            } else {
                $pesan_error = "Gagal mengunggah foto. Pastikan folder frontend/images/profil/ sudah dibuat.";
            }
        } else {
            $pesan_error = "Format foto tidak didukung. Gunakan JPG atau PNG.";
        }
    }
}

// PROSES UPDATE ALAMAT SAJA (Modal)
if (isset($_POST['update_alamat_saja'])) {
    $alamat_baru = $_POST['alamat_baru'];
    $update_almt = $conn->prepare("UPDATE users SET alamat = ? WHERE id = ?");
    if ($update_almt->execute([$alamat_baru, $id_user])) {
        $pesan_sukses = "Alamat berhasil diperbarui!";
    }
}

// Ambil data user terbaru
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id_user]);
$user = $stmt->fetch();

$inisial = strtoupper(substr($user['nama'], 0, 1));

// Logika Avatar
$avatar_html = "";
if (!empty($user['foto']) && file_exists("frontend/images/profil/" . $user['foto'])) {
    $avatar_html = '<img src="frontend/images/profil/' . $user['foto'] . '" class="rounded-circle profile-avatar shadow-sm" style="object-fit: cover; width: 100px; height: 100px;">';
    $avatar_sidebar = '<img src="frontend/images/profil/' . $user['foto'] . '" class="rounded-circle me-3" style="object-fit: cover; width: 50px; height: 50px;">';
} else {
    $avatar_html = '<div class="rounded-circle d-flex justify-content-center align-items-center profile-avatar mb-3 shadow-sm" style="width: 100px; height: 100px; font-size: 3rem; background-color: var(--xriva-dark); color: white;">' . $inisial . '</div>';
    $avatar_sidebar = '<div class="rounded-circle d-flex justify-content-center align-items-center profile-avatar me-3" style="width: 50px; height: 50px; font-size: 1.5rem; background-color: var(--xriva-dark); color: white;">' . $inisial . '</div>';
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Profil Saya - XrivaStore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="frontend/css/style.css">
    <style>
        .menu-tab { cursor: pointer; transition: 0.2s; }
        .menu-tab:hover { color: var(--xriva-dark) !important; font-weight: bold; }
        .form-label-custom { text-align: right; color: rgba(0,0,0,.65); font-size: 0.9rem; padding-top: 5px; }
        @media (max-width: 768px) { .form-label-custom { text-align: left; } }
    </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark navbar-sage sticky-top shadow-sm py-2">
    <div class="container">
        <a class="navbar-brand fw-bold fs-4" href="index.php"><i class="fas fa-leaf"></i> XrivaStore</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center gap-3">
                <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="wishlist.php"><i class="fas fa-heart me-1"></i> Wishlist</a></li>
                <li class="nav-item"><a class="nav-link" href="cart.php"><i class="fas fa-shopping-cart me-1"></i> Keranjang</a></li>
                <li class="nav-item"><a class="nav-link" href="history.php"><i class="fas fa-history me-1"></i> Pesanan</a></li>
                
                <li class="nav-item dropdown ms-2 d-flex align-items-center border-start ps-3">
                    <div class="rounded-circle d-flex justify-content-center align-items-center bg-white text-sage-dark fw-bold me-2 shadow-sm" style="width: 35px; height: 35px;">
                        <?= $inisial ?>
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
            </ul>
        </div>
    </div>
</nav>

<div class="container my-5 pb-5">
    <div class="row">
        
        <div class="col-md-3 mb-4">
            <div class="d-flex align-items-center mb-4 pb-3 border-bottom">
                <?= $avatar_sidebar ?>
                <div>
                    <h6 class="mb-0 fw-bold"><?= htmlspecialchars($user['nama']) ?></h6>
                    <span class="text-muted small"><i class="fas fa-pencil-alt"></i> Ubah Profil</span>
                </div>
            </div>
            
            <div class="sidebar-menu" style="font-size: 0.95rem;">
                <div class="d-flex align-items-center text-sage-dark fw-bold mb-3">
                    <i class="fas fa-user text-primary me-3"></i> Akun Saya
                </div>
                <div class="ms-4 mb-4">
                    <a class="d-block text-sage-dark text-decoration-none mb-2 fw-bold menu-tab active-tab" data-target="profil">Profil</a>
                    <a class="d-block text-muted text-decoration-none mb-2 menu-tab" data-target="alamat">Alamat</a>
                </div>
                <a href="history.php" class="d-flex align-items-center text-muted text-decoration-none mb-3">
                    <i class="fas fa-clipboard-list me-3" style="color: #4a7c6b;"></i> Pesanan Saya
                </a>
            </div>
        </div>

        <div class="col-md-9">
            <div class="card shadow-sm border-0" style="border-radius: 16px;">
                <div class="card-body p-4 p-md-5">
                    
                    <?php if($pesan_sukses): ?>
                        <div class="alert alert-success alert-dismissible fade show"><?= $pesan_sukses ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                    <?php endif; ?>
                    <?php if($pesan_error): ?>
                        <div class="alert alert-danger alert-dismissible fade show"><?= $pesan_error ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                    <?php endif; ?>

                    <div id="pane-profil" class="content-pane">
                        <h5 class="fw-bold mb-1 text-sage-dark">Profil Saya</h5>
                        <p class="text-muted small mb-4 pb-3 border-bottom">Kelola informasi profil Anda</p>
                        
                        <div class="row mt-4">
                            <form method="POST" enctype="multipart/form-data" class="d-flex flex-wrap w-100">
                                <div class="col-md-8 pe-md-4 border-end">
                                    <div class="row mb-4 align-items-center">
                                        <label class="col-sm-3 form-label-custom">Nama Lengkap</label>
                                        <div class="col-sm-9"><input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($user['nama'] ?? '') ?>" required></div>
                                    </div>
                                    <div class="row mb-4 align-items-center">
                                        <label class="col-sm-3 form-label-custom">Email</label>
                                        <div class="col-sm-9"><span class="me-3"><?= htmlspecialchars($user['email']) ?></span></div>
                                    </div>
                                    <div class="row mb-4 align-items-center">
                                        <label class="col-sm-3 form-label-custom">Nomor Telepon</label>
                                        <div class="col-sm-9"><input type="text" name="no_hp" class="form-control" value="<?= htmlspecialchars($user['no_hp'] ?? '') ?>" required></div>
                                    </div>
                                    <div class="row mb-4">
                                        <label class="col-sm-3 form-label-custom">Alamat Utama</label>
                                        <div class="col-sm-9"><textarea name="alamat" class="form-control" rows="3" required><?= htmlspecialchars($user['alamat'] ?? '') ?></textarea></div>
                                    </div>
                                </div>
                                
                                <div class="col-md-4 text-center mt-4 mt-md-0 d-flex flex-column align-items-center pt-3">
                                    <div class="mb-3"><?= $avatar_html ?></div>
                                    <input type="file" name="foto_profil" id="foto_profil" class="d-none" accept="image/jpeg, image/png">
                                    <label for="foto_profil" class="btn btn-outline-secondary btn-sm px-4 mb-2" id="label_foto" style="cursor:pointer;">Pilih Gambar</label>
                                </div>

                                <div class="col-12 mt-4 pt-3 border-top">
                                    <button type="submit" name="update_profile" class="btn btn-sage px-5 py-2 fw-bold shadow-sm">Simpan Perubahan</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div id="pane-alamat" class="content-pane" style="display: none;">
                        <h5 class="fw-bold mb-1 text-sage-dark">Alamat Saya</h5>
                        <p class="text-muted small mb-4 pb-3 border-bottom">Kelola alamat pengiriman untuk pesanan Anda.</p>
                        
                        <div class="border rounded p-3 mb-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <span class="fw-bold"><?= htmlspecialchars($user['nama'] ?? '') ?></span> | <span class="text-muted"><?= htmlspecialchars($user['no_hp'] ?? '') ?></span>
                                    <p class="mb-1 mt-2 small"><?= nl2br(htmlspecialchars($user['alamat'] ?? '')) ?></p>
                                    <span class="badge bg-sage-light text-sage-dark">Utama</span>
                                </div>
                                <div>
                                    <button class="btn btn-sm btn-link text-sage-dark fw-bold text-decoration-none" data-bs-toggle="modal" data-bs-target="#modalEditAlamat">Ubah</button>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditAlamat" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-bold">Ubah Alamat Pengiriman</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
          <div class="modal-body">
              <textarea name="alamat_baru" class="form-control" rows="4" required><?= htmlspecialchars($user['alamat'] ?? '') ?></textarea>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="submit" name="update_alamat_saja" class="btn btn-sage fw-bold">Simpan</button>
          </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.querySelectorAll('.menu-tab').forEach(tab => {
        tab.addEventListener('click', function() {
            document.querySelectorAll('.menu-tab').forEach(t => {
                t.classList.remove('text-sage-dark', 'fw-bold', 'active-tab');
                t.classList.add('text-muted');
            });
            this.classList.remove('text-muted');
            this.classList.add('text-sage-dark', 'fw-bold', 'active-tab');

            document.querySelectorAll('.content-pane').forEach(pane => {
                pane.style.display = 'none';
            });
            document.getElementById('pane-' + this.getAttribute('data-target')).style.display = 'block';
        });
    });

    document.getElementById('foto_profil').addEventListener('change', function(e) {
        if (e.target.files.length > 0) {
            document.getElementById('label_foto').innerText = e.target.files[0].name;
            document.getElementById('label_foto').classList.replace('btn-outline-secondary', 'btn-success');
            document.getElementById('label_foto').classList.add('text-white');
        }
    });
</script>
</body>
</html>