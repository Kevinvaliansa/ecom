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
    <title>Daftar Akun - Xriva Eyewear</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background-color: #f4f7f6; font-family: 'Segoe UI', sans-serif;}
    </style>
</head>
<body class="d-flex align-items-center vh-100">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow-lg border-0" style="border-radius: 20px; overflow: hidden;">
                <div class="card-header text-white text-center fw-bold py-3" style="background-color: #4a7c6b;">
                    <i class="fas fa-glasses me-2"></i> Buat Akun Xriva Eyewear
                </div>
                <div class="card-body p-4">
                    <?php if(isset($error)): ?>
                        <div class="alert alert-danger rounded-3"><?= $error ?></div>
                    <?php endif; ?>
                    <?php if(isset($sukses)): ?>
                        <div class="alert alert-success rounded-3"><?= $sukses ?> <a href="login.php" class="fw-bold">Login di sini</a></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="small fw-bold text-muted">Nama Lengkap</label>
                            <input type="text" name="nama" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="small fw-bold text-muted">Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="small fw-bold text-muted">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="small fw-bold text-muted">No. HP (Angka Saja)</label>
                            <input type="number" name="no_hp" class="form-control" placeholder="Contoh: 08123456789" required>
                        </div>
                        <div class="mb-4">
                            <label class="small fw-bold text-muted">Alamat Lengkap</label>
                            <textarea name="alamat" class="form-control" rows="2" required></textarea>
                        </div>
                        <button type="submit" name="register" class="btn text-white w-100 fw-bold py-2 mb-3" style="background-color: #7cb3a1; border-radius: 10px;">Daftar Sekarang</button>
                        
                        <div class="text-center small text-muted">
                            Sudah punya akun? <a href="login.php" class="text-decoration-none fw-bold" style="color: #4a7c6b;">Login</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>