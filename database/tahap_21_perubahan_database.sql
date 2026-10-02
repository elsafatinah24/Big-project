-- ================= MIGRASI TAHAP 21 =================
-- Menyiapkan data untuk role dokter, identitas/jaminan pasien, kunjungan antrean,
-- serta penugasan dokter ke poli. Jalankan SATU KALI setelah backup database.
-- Migrasi ini ditujukan untuk schema aplikasi sebelum Tahap 21.

-- ================= ROLE DOKTER =================
-- Mempertahankan seluruh akun serta nilai peran admin dan pasien yang telah ada.
ALTER TABLE `pengguna`
    MODIFY COLUMN `peran` ENUM('admin', 'pasien', 'dokter') NOT NULL;

-- ================= DATA TAMBAHAN PASIEN =================
-- NULL dipakai sementara agar data pasien lama tidak gagal saat migrasi.
-- Aplikasi tahap berikutnya wajib memvalidasi NIK dan jenis pembayaran untuk data baru.
ALTER TABLE `pasien`
    ADD COLUMN `nik` CHAR(16) CHARACTER SET ascii COLLATE ascii_bin NULL AFTER `id_pengguna`,
    ADD COLUMN `jenis_pembayaran` ENUM('umum', 'bpjs', 'asuransi_lain') NULL AFTER `nomor_telepon`,
    ADD CONSTRAINT `cek_nik_pasien_16_digit`
        CHECK (`nik` IS NULL OR `nik` REGEXP '^[0-9]{16}$'),
    ADD CONSTRAINT `unik_nik_pasien` UNIQUE (`nik`);

-- ================= PENUGASAN DOKTER KE POLI =================
-- Validasi bahwa pengguna berperan dokter dilakukan pada modul aplikasi tahap berikutnya.
-- Foreign key memastikan pengguna dan poli yang dirujuk benar-benar tersedia.
CREATE TABLE `dokter_poli` (
    `id_dokter_poli` INT UNSIGNED AUTO_INCREMENT,
    `id_pengguna` INT UNSIGNED NOT NULL,
    `id_poli` INT UNSIGNED NOT NULL,
    `dibuat_pada` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `diperbarui_pada` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_dokter_poli`),
    UNIQUE KEY `unik_dokter_poli` (`id_pengguna`, `id_poli`),
    KEY `indeks_dokter_poli_poli` (`id_poli`),
    CONSTRAINT `fk_dokter_poli_pengguna`
        FOREIGN KEY (`id_pengguna`) REFERENCES `pengguna` (`id_pengguna`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT `fk_dokter_poli_poli`
        FOREIGN KEY (`id_poli`) REFERENCES `poli` (`id_poli`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Penugasan pengguna berperan dokter pada poli pelayanan';

-- ================= DATA KUNJUNGAN ANTREAN =================
-- Semua kolom baru nullable untuk menjaga antrean lama tetap dapat dibaca.
-- Kolom waktu_selesai yang telah ada tetap digunakan dan tidak dibuat ulang.
ALTER TABLE `antrean`
    ADD COLUMN `metode_pembayaran` ENUM('umum', 'bpjs', 'asuransi_lain') NULL AFTER `status_antrean`,
    ADD COLUMN `keluhan` TEXT NULL AFTER `metode_pembayaran`,
    ADD COLUMN `id_dokter` INT UNSIGNED NULL AFTER `id_poli`,
    ADD COLUMN `hasil_pelayanan` ENUM('pengobatan', 'rujuk') NULL AFTER `id_dokter`,
    ADD COLUMN `keterangan_pelayanan` TEXT NULL AFTER `hasil_pelayanan`,
    ADD KEY `indeks_antrean_dokter` (`id_dokter`),
    ADD CONSTRAINT `fk_antrean_dokter`
        FOREIGN KEY (`id_dokter`) REFERENCES `pengguna` (`id_pengguna`)
        ON UPDATE CASCADE
        ON DELETE SET NULL;

-- CATATAN VALIDASI PASCA-MIGRASI (read-only):
-- SHOW CREATE TABLE pengguna;
-- SHOW CREATE TABLE pasien;
-- SHOW CREATE TABLE dokter_poli;
-- SHOW CREATE TABLE antrean;
-- SELECT peran, COUNT(*) FROM pengguna GROUP BY peran;
-- SELECT COUNT(*) AS pasien_tanpa_nik FROM pasien WHERE nik IS NULL;
-- SELECT COUNT(*) AS antrean_lama FROM antrean;
