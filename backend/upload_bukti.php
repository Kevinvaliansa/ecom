<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_transaksi'])) {
    $id = intval($_POST['id_transaksi']);
    $user_id = $_SESSION['user_id'];

    // CSRF check
    if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['upload_msg'] = ['type'=>'error','text'=>'Token keamanan tidak valid. Silakan coba lagi.'];
        header('Location: ../history.php'); exit;
    }

    // Cek kepemilikan transaksi
    $stmt = $conn->prepare("SELECT id_user, bukti_bayar FROM transaksi WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row || $row['id_user'] != $user_id) {
        $_SESSION['upload_msg'] = ['type'=>'error','text'=>'Transaksi tidak ditemukan atau bukan milik Anda.'];
        header('Location: ../history.php'); exit;
    }

    if (!isset($_FILES['bukti']) || $_FILES['bukti']['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['upload_msg'] = ['type'=>'error','text'=>'File tidak ditemukan atau terjadi kesalahan saat upload.'];
        header('Location: ../history.php'); exit;
    }

    // Validasi ukuran (max 3MB)
    $maxBytes = 3 * 1024 * 1024;
    if ($_FILES['bukti']['size'] > $maxBytes) {
        $_SESSION['upload_msg'] = ['type'=>'error','text'=>'Ukuran file terlalu besar. Maksimal 3MB.'];
        header('Location: ../history.php'); exit;
    }

    // Validasi tipe MIME dan gambar sebenarnya
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $_FILES['bukti']['tmp_name']);
    finfo_close($finfo);
    $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/jpg'=>'jpg'];
    if (!array_key_exists($mime, $allowed)) {
        $_SESSION['upload_msg'] = ['type'=>'error','text'=>'Tipe file tidak didukung. Gunakan JPG atau PNG.'];
        header('Location: ../history.php'); exit;
    }

    // Periksa dimensi gambar (maks 4000x4000)
    $size = getimagesize($_FILES['bukti']['tmp_name']);
    if ($size === false) {
        $_SESSION['upload_msg'] = ['type'=>'error','text'=>'File bukan gambar yang valid.'];
        header('Location: ../history.php'); exit;
    }
    if ($size[0] > 4000 || $size[1] > 4000) {
        $_SESSION['upload_msg'] = ['type'=>'error','text'=>'Dimensi gambar terlalu besar (maks 4000x4000).'];
        header('Location: ../history.php'); exit;
    }

    // Sanitasi dan buat nama file aman
    $ext = $allowed[$mime];
    $safe_fname = 'bukti_' . $id . '_' . time() . '.' . $ext;

    $destDir = __DIR__ . '/../frontend/images/bukti/';
    if (!is_dir($destDir)) mkdir($destDir, 0755, true);

    $dest = $destDir . $safe_fname;
    // Lakukan resize/compress menggunakan GD sebelum menyimpan final
    $maxW = 1200; $maxH = 1200;
    $srcPath = $_FILES['bukti']['tmp_name'];
    $srcImg = null;
    if ($ext === 'jpg') $srcImg = imagecreatefromjpeg($srcPath);
    elseif ($ext === 'png') $srcImg = imagecreatefrompng($srcPath);

    if ($srcImg !== null) {
        $w = imagesx($srcImg);
        $h = imagesy($srcImg);
        $scale = min(1, $maxW / $w, $maxH / $h);
        $newW = (int)($w * $scale);
        $newH = (int)($h * $scale);
        $dstImg = imagecreatetruecolor($newW, $newH);
        // preserve transparency for PNG
        if ($ext === 'png') {
            imagealphablending($dstImg, false);
            imagesavealpha($dstImg, true);
            $transparent = imagecolorallocatealpha($dstImg, 255, 255, 255, 127);
            imagefilledrectangle($dstImg, 0, 0, $newW, $newH, $transparent);
        }
        imagecopyresampled($dstImg, $srcImg, 0,0,0,0, $newW, $newH, $w, $h);
        // simpan dengan kualitas terkompresi
        if ($ext === 'jpg') {
            $saved = imagejpeg($dstImg, $dest, 85);
        } else {
            $saved = imagepng($dstImg, $dest, 6);
        }
        imagedestroy($srcImg);
        imagedestroy($dstImg);
        if (!$saved) {
            $_SESSION['upload_msg'] = ['type'=>'error','text'=>'Gagal menyimpan file gambar.'];
            header('Location: ../history.php'); exit;
        }
    } else {
        // fallback ke move_uploaded_file
        if (!move_uploaded_file($srcPath, $dest)) {
            $_SESSION['upload_msg'] = ['type'=>'error','text'=>'Gagal menyimpan file.'];
            header('Location: ../history.php'); exit;
        }
    }

    // Jika ada bukti sebelumnya, hapus file lama (opsional)
    if (!empty($row['bukti_bayar'])) {
        $old = $destDir . $row['bukti_bayar'];
        if (is_file($old)) @unlink($old);
    }

    // Simpan nama file dan set status pending (menunggu verifikasi)
    $update = $conn->prepare("UPDATE transaksi SET bukti_bayar = ?, status_pesanan = 'pending' WHERE id = ?");
    $ok = $update->execute([$safe_fname, $id]);
    if ($ok) {
        $_SESSION['upload_msg'] = ['type'=>'success','text'=>'Bukti berhasil di-upload. Menunggu verifikasi admin.'];
    } else {
        // rollback file jika DB gagal
        if (is_file($dest)) @unlink($dest);
        $_SESSION['upload_msg'] = ['type'=>'error','text'=>'Gagal menyimpan informasi ke database.'];
    }

}

header('Location: ../history.php');
exit;
?>
