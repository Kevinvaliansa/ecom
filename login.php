<?php
session_start();
require_once 'backend/config/database.php';

// Jika sudah login, lempar sesuai rolenya
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'admin') {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: index.php");
    }
    exit;
}

$error = "";

if (isset($_POST['login'])) {
    $email_or_username = trim($_POST['email']);
    $password = trim($_POST['password']);

    // ==========================================
    // TRIK BYPASS USERNAME
    // ==========================================
    if (strtolower($email_or_username) == 'admin') {
        $email_or_username = 'admin@gmail.com'; 
    }

    // Ambil data user dari database berdasarkan email atau username
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? OR username = ?");
    $stmt->execute([$email_or_username, $email_or_username]);
    $user = $stmt->fetch();

    // ==========================================
    // LOGIKA PENGECEKAN PASSWORD (PENTING!)
    // ==========================================
    // password_verify() -> untuk mengecek akun lama yang daftarnya lewat register.php (password di-hash)
    // == -> untuk mengecek akun admin yang diinput manual lewat database (plain text)
    if ($user && (password_verify($password, $user['password']) || $password == $user['password'])) {
        
        // Set Session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_nama'] = $user['nama'];
        $_SESSION['role'] = $user['role']; // Simpan rolenya

        // Arahkan berdasarkan role
        if ($user['role'] == 'admin') {
            header("Location: admin/dashboard.php");
        } else {
            header("Location: index.php");
        }
        exit;
    } else {
        $error = "Email/Username atau password salah!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login - XrivaStore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="frontend/css/style.css?v=<?= time() ?>">
</head>
<body class="bg-sage-light d-flex align-items-center" style="min-height: 100vh;">

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
                    <div class="card-header bg-sage-dark text-white text-center py-4 border-0">
                        <h3 class="fw-bold mb-0"><i class="fas fa-glasses"></i> XrivaStore</h3>
                        <p class="mb-0 small">Silakan masuk ke akun Anda</p>
                    </div>
                    <div class="card-body p-5 bg-white">
                        
                        <?php if($error): ?>
                            <div class="alert alert-danger rounded-3 small fw-bold"><i class="fas fa-exclamation-circle me-1"></i> <?= $error ?></div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label fw-bold text-muted small">Email / Username</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="fas fa-user text-muted"></i></span>
                                    <input type="text" name="email" class="form-control border-start-0 bg-light" required placeholder="email@gmail.com">
                                </div>
                            </div>
                            <div class="mb-4">
                                <label class="form-label fw-bold text-muted small">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="fas fa-lock text-muted"></i></span>
                                    <input type="password" name="password" id="passwordLogin" class="form-control border-start-0 border-end-0 bg-light" required placeholder="••••••••">
                                    <button type="button" class="input-group-text bg-light border-start-0" id="togglePassword" tabindex="-1">
                                        <i class="fas fa-eye text-muted" id="eyeIcon"></i>
                                    </button>
                                </div>
                            </div>
                            <button type="submit" name="login" class="btn btn-sage w-100 fw-bold py-2 rounded-pill shadow-sm">Masuk</button>
                        </form>

                        <div class="text-center mt-4 pt-3 border-top">
                            <span class="text-muted small">Belum punya akun? <a href="register.php" class="text-sage-dark fw-bold text-decoration-none">Daftar Sekarang</a></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const toggleBtn = document.getElementById('togglePassword');
        const passInput = document.getElementById('passwordLogin');
        const eyeIcon  = document.getElementById('eyeIcon');
        if (toggleBtn) {
            toggleBtn.addEventListener('click', function() {
                const isPass = passInput.type === 'password';
                passInput.type = isPass ? 'text' : 'password';
                eyeIcon.classList.toggle('fa-eye', !isPass);
                eyeIcon.classList.toggle('fa-eye-slash', isPass);
            });
        }
    </script>
</body>
</html>