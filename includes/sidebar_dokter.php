<?php
// ================= TEMPLATE SIDEBAR DOKTER =================
// Menu dasar area Dokter; fitur layanan klinis ditambahkan pada tahap berikutnya.

declare(strict_types=1);

$menu_aktif = $menu_aktif ?? '';
?>
<aside class="sidebar-admin bg-white border-end p-3" id="sidebarDokter" tabindex="-1" aria-labelledby="judulSidebarDokter">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <p class="text-success fw-bold mb-0" id="judulSidebarDokter">Menu Dokter</p>
        <button class="btn-close d-md-none" type="button" data-sidebar-close aria-label="Tutup menu Dokter"></button>
    </div>
    <nav class="nav flex-column gap-1">
        <a class="nav-link <?= $menu_aktif === 'dashboard' ? 'aktif' : 'text-dark'; ?>" href="<?= aman_html(url_aplikasi('dokter/dashboard.php')); ?>">Dashboard</a>
        <a class="nav-link <?= $menu_aktif === 'antrean' ? 'aktif' : 'text-dark'; ?>" href="<?= aman_html(url_aplikasi('dokter/antrean.php')); ?>">Antrean</a>
    </nav>
</aside>
