-- ============================================================
-- XRIVASTORE: Update Sistem Varian Ganda
-- Jalankan file ini di phpMyAdmin atau MySQL CLI
-- ============================================================

-- 1. Tambah kolom varian_warna dan varian_lensa ke tabel produk
--    (pilihan_varian lama dibiarkan sebagai backup, bisa dihapus nanti)
ALTER TABLE `produk`
    ADD COLUMN `varian_warna` VARCHAR(500) NULL DEFAULT NULL COMMENT 'Varian pilihan warna, pisah koma. Cth: Hitam, Putih, Gold' AFTER `pilihan_varian`,
    ADD COLUMN `varian_lensa` VARCHAR(500) NULL DEFAULT NULL COMMENT 'Varian ukuran lensa minus/plus, pisah koma. Cth: -1, -1.5, -2' AFTER `varian_warna`;

-- 2. (Opsional) Migrasi data lama: 
--    Jika produk lama punya pilihan_varian yang berisi angka minus/plus,
--    pindahkan ke varian_lensa. Jika berisi warna, pindahkan ke varian_warna.
--    Lakukan secara manual di phpMyAdmin jika diperlukan.
--    Contoh migrasi semi-otomatis (uncomment jika diperlukan):
-- UPDATE produk SET varian_lensa = pilihan_varian 
--   WHERE pilihan_varian REGEXP '^[+-]?[0-9]';
-- UPDATE produk SET varian_warna = pilihan_varian 
--   WHERE pilihan_varian NOT REGEXP '^[+-]?[0-9]' AND pilihan_varian != '';

-- 3. Kolom varian di tabel cart — sudah ada, tidak perlu diubah struktur.
--    Nilai yang disimpan sekarang berupa gabungan: "Hitam / -1.5"

-- 4. Kolom varian di tabel detail_transaksi — sudah ada, tidak perlu diubah.
--    Nilai yang disimpan sekarang berupa gabungan: "Hitam / -1.5"

-- ============================================================
-- Cek hasilnya:
-- ============================================================
-- SHOW COLUMNS FROM produk;
-- SELECT id, nama_produk, pilihan_varian, varian_warna, varian_lensa FROM produk LIMIT 10;
