-- ================= MIGRASI STATUS ANTREAN =================
ALTER TABLE `antrean` MODIFY `status_antrean` ENUM('menunggu', 'dipanggil', 'selesai', 'dibatalkan') NOT NULL DEFAULT 'menunggu';
