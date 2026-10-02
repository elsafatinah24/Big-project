-- ================= MIGRASI GAMBAR PENGUMUMAN =================
-- Menambahkan nama file gambar opsional pada tabel pengumuman.

ALTER TABLE `pengumuman`
    ADD COLUMN `gambar` VARCHAR(255) NULL AFTER `isi_pengumuman`;
