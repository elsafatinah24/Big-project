-- ================= TOKEN RESET PASSWORD =================
-- Jalankan satu kali secara manual setelah backup database.
-- Migrasi ini hanya membuat tabel kosong untuk token reset password.
-- Token asli TIDAK disimpan; aplikasi nantinya menyimpan hash SHA-256 pada token_hash.

CREATE TABLE IF NOT EXISTS `password_reset_token` (
    `id_password_reset_token` INT UNSIGNED AUTO_INCREMENT,
    `id_pengguna` INT UNSIGNED NOT NULL,
    `token_hash` CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    `dibuat_pada` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `kedaluwarsa_pada` DATETIME NOT NULL,
    `digunakan_pada` DATETIME NULL,
    PRIMARY KEY (`id_password_reset_token`),
    UNIQUE KEY `unik_password_reset_token_hash` (`token_hash`),
    KEY `indeks_password_reset_pengguna_aktif` (`id_pengguna`, `digunakan_pada`, `kedaluwarsa_pada`),
    CONSTRAINT `fk_password_reset_token_pengguna`
        FOREIGN KEY (`id_pengguna`) REFERENCES `pengguna` (`id_pengguna`)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Token hash sementara untuk pemulihan kata sandi pengguna';
