<?php
session_start();
require_once 'backend/config/database.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php"); exit;
}

$error = "";
$sukses = "";

if (isset($_POST['register'])) {
    $nama    = htmlspecialchars(trim($_POST['nama']));
    $email   = trim($_POST['email']);
    $password = $_POST['password'];
    $no_hp   = htmlspecialchars(trim($_POST['no_hp']));
    $alamat  = htmlspecialchars(trim($_POST['alamat']));

    // Validasi password minimal 6 karakter
    if (strlen($password) < 6) {
        $error = "Password minimal 6 karakter.";
    } else {
        $cek_email = $conn->prepare("SELECT email FROM users WHERE email = ?");
        $cek_email->execute([$email]);

        if ($cek_email->rowCount() > 0) {
            $error = "Email sudah terdaftar, silakan gunakan email lain.";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt   = $conn->prepare("INSERT INTO users (nama, email, password, no_hp, alamat) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$nama, $email, $hashed, $no_hp, $alamat])) {
                $sukses = "Registrasi berhasil!";
            } else {
                $error = "Terjadi kesalahan saat mendaftar.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun - XrivaStore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="frontend/css/style.css?v=<?= time() ?>">
</head>
<body class="bg-sage-light d-flex align-items-center" style="min-height: 100vh;">

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
                <div class="card-header bg-sage-dark text-white text-center py-4 border-0">
                    <h3 class="fw-bold mb-0"><i class="fas fa-leaf me-2"></i> XrivaStore</h3>
                    <p class="mb-0 small opacity-75">Buat akun baru</p>
                </div>
                <div class="card-body p-4 p-md-5 bg-white">

                    <?php if ($error): ?>
                        <div class="alert alert-danger rounded-3 small fw-bold">
                            <i class="fas fa-exclamation-circle me-1"></i> <?= $error ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($sukses): ?>
                        <div class="alert alert-success rounded-3 small fw-bold">
                            <i class="fas fa-check-circle me-1"></i> <?= $sukses ?>
                            <a href="login.php" class="fw-bold ms-1 text-success">Login sekarang →</a>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <!-- Nama -->
                        <div class="mb-3">
                            <label class="form-label fw-bold text-muted small">Nama Lengkap</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="fas fa-user text-muted"></i></span>
                                <input type="text" name="nama" class="form-control border-start-0 bg-light" required placeholder="Nama Lengkap Anda"
                                       value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>">
                            </div>
                        </div>
                        <!-- Email -->
                        <div class="mb-3">
                            <label class="form-label fw-bold text-muted small">Email</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="fas fa-envelope text-muted"></i></span>
                                <input type="email" name="email" class="form-control border-start-0 bg-light" required placeholder="email@gmail.com"
                                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                            </div>
                        </div>
                        <!-- Password -->
                        <div class="mb-3">
                            <label class="form-label fw-bold text-muted small">Password <span class="text-muted fw-normal">(min. 6 karakter)</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="fas fa-lock text-muted"></i></span>
                                <input type="password" name="password" id="regPassword" class="form-control border-start-0 border-end-0 bg-light" required placeholder="••••••••">
                                <button type="button" class="input-group-text bg-light border-start-0" id="toggleRegPass" tabindex="-1">
                                    <i class="fas fa-eye text-muted" id="eyeIconReg"></i>
                                </button>
                            </div>
                        </div>
                        <!-- No HP -->
                        <div class="mb-3">
                            <label class="form-label fw-bold text-muted small">No. HP</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="fas fa-phone text-muted"></i></span>
                                <input type="tel" name="no_hp" class="form-control border-start-0 bg-light" required placeholder="08xxxxxxxxxx"
                                       value="<?= htmlspecialchars($_POST['no_hp'] ?? '') ?>">
                            </div>
                        </div>
                        <!-- Alamat -->
                        <div class="mb-4">
                            <label class="form-label fw-bold text-muted small">Alamat Lengkap</label>
                            <div class="input-group align-items-start">
                                <span class="input-group-text bg-light border-end-0 pt-2"><i class="fas fa-map-marker-alt text-muted"></i></span>
                                <textarea name="alamat" class="form-control border-start-0 bg-light" rows="2" required placeholder="Jl. Contoh No. 1, Kota..."><?= htmlspecialchars($_POST['alamat'] ?? '') ?></textarea>
                            </div>
                        </div>

                        <button type="submit" name="register" class="btn btn-sage w-100 fw-bold py-2 rounded-pill shadow-sm mb-3">
                            <i class="fas fa-user-plus me-2"></i> Daftar Sekarang
                        </button>

                        <div class="text-center small text-muted">
                            Sudah punya akun? <a href="login.php" class="text-sage-dark fw-bold text-decoration-none">Login di sini</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Toggle show/hide password
    const toggleReg = document.getElementById('toggleRegPass');
    const regPass   = document.getElementById('regPassword');
    const eyeReg    = document.getElementById('eyeIconReg');
    if (toggleReg) {
        toggleReg.addEventListener('click', function () {
            const isPass = regPass.type === 'password';
            regPass.type = isPass ? 'text' : 'password';
            eyeReg.classList.toggle('fa-eye', !isPass);
            eyeReg.classList.toggle('fa-eye-slash', isPass);
        });
    }
</script>
</body>
</html>