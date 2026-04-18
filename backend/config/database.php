<?php
$host = "localhost";
$dbname = "ecommerce_db";
$username = "root"; // Username default database Laragon
$password = "";     // Password default Laragon biasanya kosong

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    // Setting error mode PDO ke exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Buka komentar di bawah ini kalau kamu mau ngetest koneksinya berhasil atau nggak
    // echo "Koneksi Berhasil!"; 
} catch(PDOException $e) {
    echo "Koneksi Gagal: " . $e->getMessage();
}
?>