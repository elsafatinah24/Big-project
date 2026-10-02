<?php
// ================= TEMPLATE NAVBAR ADMIN =================
// Navigasi atas khusus untuk area Admin yang telah terautentikasi.

declare(strict_types=1);
?>
<nav class="navbar navbar-admin fixed-top navbar-expand-md bg-success navbar-dark shadow-sm">
    <div class="container-fluid px-3 px-lg-4">
        <button class="navbar-toggler d-md-none" type="button" data-sidebar-toggle="sidebarAdmin" aria-controls="sidebarAdmin" aria-expanded="false" aria-label="Buka menu Admin"><span class="navbar-toggler-icon"></span></button>
        <a class="navbar-brand d-inline-flex align-items-center gap-2 fw-semibold" href="<?= aman_html(url_aplikasi('admin/dashboard.php')); ?>"><img class="logo-navbar" src="<?= aman_html(url_aplikasi('assets/gambar/logo-antrean-puskesmas.png')); ?>" alt="" width="29" height="32" style="display: block; width: auto; height: 32px; max-width: 40px; max-height: 32px; object-fit: contain;"><span>Antrean Puskesmas Rembang</span></a>
        <div class="d-flex align-items-center gap-3 text-white ms-auto">
            <span class="d-none d-sm-inline small">Admin: <?= aman_html($_SESSION['pengguna']['nama_pengguna']); ?></span>
            <button class="btn btn-sm btn-outline-light" type="button" data-bs-toggle="modal" data-bs-target="#modalKonfirmasiLogoutAdmin">Logout</button>
        </div>
    </div>
</nav>
<div class="kompensasi-navbar-dashboard" style="height: 56px;" aria-hidden="true"></div>

<div class="modal fade" id="modalKonfirmasiLogoutAdmin" tabindex="-1" aria-labelledby="judulModalKonfirmasiLogoutAdmin" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"><div class="modal-content border-0">
        <div class="modal-header bg-success text-white"><h2 class="fs-5 modal-title" id="judulModalKonfirmasiLogoutAdmin">Konfirmasi Logout</h2><button class="btn-close btn-close-white" type="button" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
        <div class="modal-body">Apakah Anda yakin ingin keluar?</div>
        <div class="modal-footer"><button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Tidak</button><a class="btn btn-success" href="<?= aman_html(url_aplikasi('auth/logout.php')); ?>">Ya, Keluar</a></div>
    </div></div>
</div>
