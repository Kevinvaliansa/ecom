<?php
session_start();
require_once 'backend/config/database.php';

// Jika sudah login, lempar ke index
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // Verifikasi email dan password hash
    if ($user && password_verify($password, $user['password'])) {
        // Buat Session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_nama'] = $user['nama'];
        
        header("Location: index.php"); // Arahkan ke halaman utama
        exit;
    } else {
        $error = "Email atau Password salah!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login - XrivaStore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="frontend/css/style.css">
</head>
<body class="bg-light d-flex align-items-center" style="height: 100vh;">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-sage-primary text-white text-center fw-bold py-3">
                    Login ke XrivaStore
                </div>
                <div class="card-body p-4">
                    <?php if(isset($error)): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label>Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <button type="submit" name="login" class="btn btn-sage w-100 fw-bold">Login</button>
                        <div class="text-center mt-3">
                            Belum punya akun? <a href="register.php" class="text-sage-dark text-decoration-none fw-bold">Daftar</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>