-- ================= MIGRASI JENIS KELAMIN PASIEN =================
-- Menambahkan data jenis kelamin wajib pada tabel pasien.

ALTER TABLE `pasien`
    ADD COLUMN `jenis_kelamin` ENUM('Laki-laki', 'Perempuan') NOT NULL AFTER `tanggal_lahir`;
