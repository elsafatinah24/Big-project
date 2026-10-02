-- ================= MIGRASI PENGUMUMAN =================
-- Membuat tabel pengumuman untuk database aplikasi yang sudah ada.

CREATE TABLE IF NOT EXISTS `pengumuman` (
    `id_pengumuman` INT UNSIGNED AUTO_INCREMENT,
    `judul` VARCHAR(200) NOT NULL,
    `isi_pengumuman` TEXT NOT NULL,
    `tanggal_publikasi` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `status_aktif` ENUM('aktif', 'tidak_aktif') NOT NULL DEFAULT 'aktif',
    `dibuat_pada` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `diperbarui_pada` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_pengumuman`),
    KEY `indeks_pengumuman_status_tanggal` (`status_aktif`, `tanggal_publikasi`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Pengumuman aktif dan nonaktif Puskesmas';
