-- ================= MIGRASI JADWAL PELAYANAN =================
-- Membuat tabel jadwal pelayanan untuk database aplikasi yang sudah ada.

CREATE TABLE IF NOT EXISTS `jadwal_pelayanan` (
    `id_jadwal_pelayanan` INT UNSIGNED AUTO_INCREMENT,
    `hari` VARCHAR(20) NOT NULL,
    `jam_buka` TIME NOT NULL,
    `jam_tutup` TIME NOT NULL,
    `keterangan` TEXT NULL,
    `status_aktif` ENUM('aktif', 'tidak_aktif') NOT NULL DEFAULT 'aktif',
    `dibuat_pada` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `diperbarui_pada` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_jadwal_pelayanan`),
    KEY `indeks_jadwal_status` (`status_aktif`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Jadwal pelayanan Puskesmas';
