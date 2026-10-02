<?php
// ================= TEMPLATE SIDEBAR ADMIN =================
// Menu utama untuk halaman dengan hak akses Admin.

declare(strict_types=1);

$menu_aktif = $menu_aktif ?? '';
?>
<aside class="sidebar-admin bg-white border-end p-3" id="sidebarAdmin" tabindex="-1" aria-labelledby="judulSidebarAdmin">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <p class="text-success fw-bold mb-0" id="judulSidebarAdmin">Menu Admin</p>
        <button class="btn-close d-md-none" type="button" data-sidebar-close aria-label="Tutup menu Admin"></button>
    </div>
    <nav class="nav flex-column gap-1">
        <a class="nav-link <?= $menu_aktif === 'dashboard' ? 'aktif' : 'text-dark'; ?>" href="<?= aman_html(url_aplikasi('admin/dashboard.php')); ?>">Dashboard</a>
        <a class="nav-link <?= $menu_aktif === 'pasien' ? 'aktif' : 'text-dark'; ?>" href="<?= aman_html(url_aplikasi('admin/data-pasien.php')); ?>">Data Pasien</a>
        <a class="nav-link <?= $menu_aktif === 'poli' ? 'aktif' : 'text-dark'; ?>" href="<?= aman_html(url_aplikasi('admin/data-poli.php')); ?>">Data Poli</a>
        <a class="nav-link <?= $menu_aktif === 'akun-dokter' ? 'aktif' : 'text-dark'; ?>" href="<?= aman_html(url_aplikasi('admin/data-akun-dokter.php')); ?>">Kelola Akun Dokter</a>
        <a class="nav-link <?= $menu_aktif === 'penugasan-dokter' ? 'aktif' : 'text-dark'; ?>" href="<?= aman_html(url_aplikasi('admin/data-penugasan-dokter.php')); ?>">Penugasan Dokter</a>
        <a class="nav-link <?= $menu_aktif === 'jadwal-pelayanan' ? 'aktif' : 'text-dark'; ?>" href="<?= aman_html(url_aplikasi('admin/data-jadwal-pelayanan.php')); ?>">Jadwal Pelayanan</a>
        <a class="nav-link <?= $menu_aktif === 'pengumuman' ? 'aktif' : 'text-dark'; ?>" href="<?= aman_html(url_aplikasi('admin/data-pengumuman.php')); ?>">Berita &amp; Pengumuman</a>
        <a class="nav-link <?= $menu_aktif === 'laporan-antrean' ? 'aktif' : 'text-dark'; ?>" href="<?= aman_html(url_aplikasi('admin/laporan-antrean.php')); ?>">Laporan Antrean</a>
    </nav>
</aside>
