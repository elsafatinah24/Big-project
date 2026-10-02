-- ================================================================
-- DATABASE: Aplikasi Tiket Antrean Online Puskesmas Berbasis Web
-- Dibuat berdasarkan PRD proyek.
-- Berkas ini siap diimpor melalui phpMyAdmin.
-- ================================================================

CREATE DATABASE IF NOT EXISTS `antrian_online_puskesmas`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `antrian_online_puskesmas`;

-- ================= MODUL AKUN PENGGUNA =================
-- Menyimpan akun login untuk Admin, Pasien, dan Dokter.
CREATE TABLE `pengguna` (
    `id_pengguna` INT UNSIGNED AUTO_INCREMENT,
    `nama_pengguna` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL,
    `kata_sandi` VARCHAR(255) NOT NULL,
    `peran` ENUM('admin', 'pasien', 'dokter') NOT NULL,
    `dibuat_pada` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `diperbarui_pada` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_pengguna`),
    UNIQUE KEY `unik_email_pengguna` (`email`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Data akun untuk autentikasi dan pembatasan akses berdasarkan peran';

-- ================= MODUL DATA PASIEN =================
-- Menyimpan data diri pasien yang memiliki akun berperan pasien.
CREATE TABLE `pasien` (
    `id_pasien` INT UNSIGNED AUTO_INCREMENT,
    `id_pengguna` INT UNSIGNED NOT NULL,
    `nik` CHAR(16) CHARACTER SET ascii COLLATE ascii_bin NULL,
    `nama_pasien` VARCHAR(100) NOT NULL,
    `tanggal_lahir` DATE NOT NULL,
    `jenis_kelamin` ENUM('Laki-laki', 'Perempuan') NOT NULL,
    `alamat` TEXT NOT NULL,
    `nomor_telepon` VARCHAR(20) NOT NULL,
    `jenis_pembayaran` ENUM('umum', 'bpjs', 'asuransi_lain') NULL,
    `dibuat_pada` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `diperbarui_pada` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_pasien`),
    UNIQUE KEY `unik_pengguna_pasien` (`id_pengguna`),
    UNIQUE KEY `unik_nik_pasien` (`nik`),
    CONSTRAINT `cek_nik_pasien_16_digit`
        CHECK (`nik` IS NULL OR `nik` REGEXP '^[0-9]{16}$'),
    CONSTRAINT `fk_pasien_pengguna`
        FOREIGN KEY (`id_pengguna`) REFERENCES `pengguna` (`id_pengguna`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Data identitas pasien untuk pendaftaran dan antrean';

-- ================= MODUL DATA POLI =================
-- Menyimpan daftar poli yang dapat dipilih pasien.
CREATE TABLE `poli` (
    `id_poli` INT UNSIGNED AUTO_INCREMENT,
    `kode_poli` VARCHAR(10) NOT NULL,
    `nama_poli` VARCHAR(100) NOT NULL,
    `keterangan` TEXT NULL,
    `status_aktif` ENUM('aktif', 'tidak_aktif') NOT NULL DEFAULT 'aktif',
    `dibuat_pada` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `diperbarui_pada` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_poli`),
    UNIQUE KEY `unik_kode_poli` (`kode_poli`),
    UNIQUE KEY `unik_nama_poli` (`nama_poli`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Data poli pelayanan Puskesmas';

-- ================= MODUL PENUGASAN DOKTER POLI =================
-- Menyimpan poli yang menjadi tanggung jawab setiap pengguna berperan dokter.
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

-- ================= MODUL DATA ANTREAN =================
-- Menyimpan pendaftaran dan nomor antrean pasien pada poli tertentu.
CREATE TABLE `antrean` (
    `id_antrean` INT UNSIGNED AUTO_INCREMENT,
    `id_pasien` INT UNSIGNED NOT NULL,
    `id_poli` INT UNSIGNED NOT NULL,
    `id_dokter` INT UNSIGNED NULL,
    `nomor_antrean` INT UNSIGNED NOT NULL,
    `tanggal_antrean` DATE NOT NULL,
    `status_antrean` ENUM('menunggu', 'dipanggil', 'selesai', 'dibatalkan') NOT NULL DEFAULT 'menunggu',
    `metode_pembayaran` ENUM('umum', 'bpjs', 'asuransi_lain') NULL,
    `keluhan` TEXT NULL,
    `hasil_pelayanan` ENUM('pengobatan', 'rujuk') NULL,
    `keterangan_pelayanan` TEXT NULL,
    `waktu_dipanggil` DATETIME NULL,
    `waktu_selesai` DATETIME NULL,
    `dibuat_pada` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `diperbarui_pada` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_antrean`),
    UNIQUE KEY `unik_nomor_antrean_per_poli_hari` (`id_poli`, `tanggal_antrean`, `nomor_antrean`),
    KEY `indeks_antrean_pasien` (`id_pasien`),
    KEY `indeks_antrean_poli_tanggal_status` (`id_poli`, `tanggal_antrean`, `status_antrean`),
    KEY `indeks_antrean_dokter` (`id_dokter`),
    CONSTRAINT `fk_antrean_pasien`
        FOREIGN KEY (`id_pasien`) REFERENCES `pasien` (`id_pasien`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT `fk_antrean_poli`
        FOREIGN KEY (`id_poli`) REFERENCES `poli` (`id_poli`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT `fk_antrean_dokter`
        FOREIGN KEY (`id_dokter`) REFERENCES `pengguna` (`id_pengguna`)
        ON UPDATE CASCADE
        ON DELETE SET NULL,
    CONSTRAINT `cek_nomor_antrean_positif`
        CHECK (`nomor_antrean` > 0)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Data tiket, status, dan waktu proses antrean pasien';

-- ================= MODUL JADWAL PELAYANAN =================
-- Menyimpan jadwal layanan yang dikelola Admin dan ditampilkan pada landing page.
CREATE TABLE `jadwal_pelayanan` (
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

-- ================= MODUL PENGUMUMAN =================
-- Menyimpan pengumuman yang dikelola Admin dan ditampilkan pada landing page.
CREATE TABLE `pengumuman` (
    `id_pengumuman` INT UNSIGNED AUTO_INCREMENT,
    `judul` VARCHAR(200) NOT NULL,
    `isi_pengumuman` TEXT NOT NULL,
    `gambar` VARCHAR(255) NULL,
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

-- ================= MODUL BERITA DAN PENGUMUMAN =================
-- Menyimpan berita atau pengumuman yang dibuat oleh Admin.
CREATE TABLE `berita_pengumuman` (
    `id_berita_pengumuman` INT UNSIGNED AUTO_INCREMENT,
    `id_pengguna` INT UNSIGNED NOT NULL,
    `judul` VARCHAR(200) NOT NULL,
    `isi` TEXT NOT NULL,
    `tanggal_terbit` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `dibuat_pada` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `diperbarui_pada` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_berita_pengumuman`),
    KEY `indeks_berita_penulis` (`id_pengguna`),
    KEY `indeks_berita_tanggal_terbit` (`tanggal_terbit`),
    CONSTRAINT `fk_berita_pengguna`
        FOREIGN KEY (`id_pengguna`) REFERENCES `pengguna` (`id_pengguna`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Berita dan pengumuman yang diterbitkan oleh Admin';
