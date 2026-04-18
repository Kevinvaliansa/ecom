<?php
session_start();
require_once 'backend/config/database.php';

if (isset($_POST['register'])) {
    $nama = $_POST['nama'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Enkripsi password
    $no_hp = $_POST['no_hp'];
    $alamat = $_POST['alamat'];

    // Cek apakah email sudah terdaftar
    $cek_email = $conn->prepare("SELECT email FROM users WHERE email = ?");
    $cek_email->execute([$email]);
    
    if ($cek_email->rowCount() > 0) {
        $error = "Email sudah terdaftar, silakan gunakan email lain.";
    } else {
        // Masukkan data ke database
        $stmt = $conn->prepare("INSERT INTO users (nama, email, password, no_hp, alamat) VALUES (?, ?, ?, ?, ?)");
        if ($stmt->execute([$nama, $email, $password, $no_hp, $alamat])) {
            $sukses = "Registrasi berhasil! Silakan login.";
        } else {
            $error = "Terjadi kesalahan saat mendaftar.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Akun - XrivaStore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="frontend/css/style.css">
</head>
<body class="bg-light d-flex align-items-center" style="height: 100vh;">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-sage-primary text-white text-center fw-bold py-3">
                    Buat Akun Baru
                </div>
                <div class="card-body p-4">
                    <?php if(isset($error)): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>
                    <?php if(isset($sukses)): ?>
                        <div class="alert alert-success"><?= $sukses ?> <a href="login.php">Login di sini</a></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label>Nama Lengkap</label>
                            <input type="text" name="nama" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>No. HP</label>
                            <input type="text" name="no_hp" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Alamat Lengkap</label>
                            <textarea name="alamat" class="form-control" rows="2" required></textarea>
                        </div>
                        <button type="submit" name="register" class="btn btn-sage w-100 fw-bold">Daftar Sekarang</button>
                        <div class="text-center mt-3">
                            Sudah punya akun? <a href="login.php" class="text-sage-dark text-decoration-none fw-bold">Login</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>