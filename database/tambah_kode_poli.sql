-- ================= MIGRASI KODE POLI =================
-- Jalankan satu kali pada database yang telah dibuat sebelum kolom kode_poli tersedia.
ALTER TABLE `poli` ADD COLUMN `kode_poli` VARCHAR(10) NULL AFTER `id_poli`;
-- Isi kode_poli secara manual untuk data lama sebelum menjalankan dua perintah berikutnya.
-- Contoh: UPDATE poli SET kode_poli = 'UM' WHERE id_poli = 1;
ALTER TABLE `poli` MODIFY COLUMN `kode_poli` VARCHAR(10) NOT NULL;
ALTER TABLE `poli` ADD CONSTRAINT `unik_kode_poli` UNIQUE (`kode_poli`);
