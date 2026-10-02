<?php
// ================= TEMPLATE SIDEBAR PASIEN =================
// Menu utama untuk halaman dengan hak akses Pasien.

declare(strict_types=1);

// ================= PENANDA MENU AKTIF =================
// Menggunakan nilai dari halaman bila tersedia, atau menentukan dari nama halaman saat ini.
$menu_aktif = $menu_aktif ?? match (basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''))) {
    'beranda.php' => 'beranda',
    'ambil-antrean.php' => 'ambil-antrean',
    'status-antrean.php' => 'status-antrean',
    'riwayat-antrean.php' => 'riwayat-antrean',
    'profil.php' => 'profil',
    default => '',
};
?>
<aside class="sidebar-pasien bg-white border-end min-vh-100 p-3" style="width: 260px;">
    <p class="text-success fw-bold mb-3">Menu Pasien</p>
    <nav class="nav flex-column gap-1">
        <a class="nav-link <?= $menu_aktif === 'beranda' ? 'active bg-success text-white' : 'text-dark'; ?>" href="<?= aman_html(url_aplikasi('pasien/beranda.php')); ?>">Beranda</a>
        <a class="nav-link <?= $menu_aktif === 'ambil-antrean' ? 'active bg-success text-white' : 'text-dark'; ?>" href="<?= aman_html(url_aplikasi('pasien/ambil-antrean.php')); ?>">Ambil Antrean</a>
        <a class="nav-link <?= $menu_aktif === 'status-antrean' ? 'active bg-success text-white' : 'text-dark'; ?>" href="<?= aman_html(url_aplikasi('pasien/status-antrean.php')); ?>">Status Antrean</a>
        <a class="nav-link <?= $menu_aktif === 'riwayat-antrean' ? 'active bg-success text-white' : 'text-dark'; ?>" href="<?= aman_html(url_aplikasi('pasien/riwayat-antrean.php')); ?>">Riwayat Antrean</a>
        <a class="nav-link <?= $menu_aktif === 'profil' ? 'active bg-success text-white' : 'text-dark'; ?>" href="<?= aman_html(url_aplikasi('pasien/profil.php')); ?>">Profil</a>
    </nav>
</aside>
