<?php
session_start();

// Jika admin sudah login, langsung lempar ke halaman produk
if (isset($_SESSION['admin_logged_in'])) {
    header("Location: produk.php");
    exit;
}

if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // KUNCI RAHASIA ADMIN (Bisa kamu ganti sesuka hati)
    if ($username === 'admin' && $password === 'admin123') {
        $_SESSION['admin_logged_in'] = true;
        header("Location: produk.php");
        exit;
    } else {
        $error = "Username atau Password salah!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login Admin - XrivaShop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../frontend/css/style.css">
</head>
<body class="bg-light d-flex align-items-center" style="height: 100vh;">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-4">
            <div class="card shadow border-0" style="border-radius: 15px;">
                <div class="card-header bg-sage-primary text-white text-center fw-bold py-4" style="border-radius: 15px 15px 0 0;">
                    <i class="fas fa-user-shield fa-2x mb-2"></i><br>
                    XrivaShop Admin Panel
                </div>
                <div class="card-body p-4">
                    <?php if(isset($error)): ?>
                        <div class="alert alert-danger text-center"><?= $error ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="fw-bold text-sage-dark">Username</label>
                            <input type="text" name="username" class="form-control" placeholder="Masukkan username" required>
                        </div>
                        <div class="mb-4">
                            <label class="fw-bold text-sage-dark">Password</label>
                            <input type="password" name="password" class="form-control" placeholder="Masukkan password" required>
                        </div>
                        <button type="submit" name="login" class="btn btn-sage w-100 fw-bold py-2">Masuk ke Dashboard</button>
                    </form>
                    
                    <div class="text-center mt-4">
                        <a href="../index.php" class="text-muted text-decoration-none small"><i class="fas fa-arrow-left"></i> Kembali ke Website</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>