<?php
// ================= TEMPLATE NAVBAR =================
// Navigasi atas untuk landing page dan pengguna yang telah login.

declare(strict_types=1);
?>
<nav class="navbar navbar-utama navbar-expand-lg navbar-dark bg-success shadow-sm">
    <div class="container-fluid px-3 px-lg-4">
        <a class="navbar-brand d-inline-flex align-items-center gap-2 fw-semibold" href="<?= aman_html(url_aplikasi('index.php')); ?>">
            <img class="logo-navbar" src="<?= aman_html(url_aplikasi('assets/gambar/logo-antrean-puskesmas.png')); ?>" alt="" width="29" height="32" style="display: block; width: auto; height: 32px; max-width: 40px; max-height: 32px; object-fit: contain;">
            <span>Antrean Puskesmas Rembang</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navigasiUtama" aria-controls="navigasiUtama" aria-expanded="false" aria-label="Buka navigasi">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navigasiUtama">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">
                <?php if (!sudah_login()): ?>
                    <li class="nav-item"><a class="nav-link" href="<?= aman_html(url_aplikasi('index.php')); ?>">Beranda</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= aman_html(url_aplikasi('#profil')); ?>">Profil</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= aman_html(url_aplikasi('#layanan')); ?>">Layanan</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= aman_html(url_aplikasi('#jadwal')); ?>">Jadwal</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= aman_html(url_aplikasi('#berita')); ?>">Berita</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= aman_html(url_aplikasi('antrean-aktif.php')); ?>">Antrean Aktif</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= aman_html(url_aplikasi('#faq')); ?>">FAQ</a></li>
                    <li class="nav-item ms-lg-2"><a class="nav-link" href="<?= aman_html(url_aplikasi('auth/login.php')); ?>">Login</a></li>
                    <li class="nav-item ms-lg-2"><a class="btn btn-light btn-sm btn-registrasi" href="<?= aman_html(url_aplikasi('auth/registrasi.php')); ?>">Registrasi</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="<?= aman_html(url_aplikasi('antrean-aktif.php')); ?>">Antrean Aktif</a></li>
                    <li class="nav-item"><span class="navbar-text me-lg-3">Halo, <?= aman_html($_SESSION['pengguna']['nama_pengguna']); ?></span></li>
                    <li class="nav-item"><button class="nav-link btn btn-link border-0 p-0" type="button" data-bs-toggle="modal" data-bs-target="#modalKonfirmasiLogoutPasien">Logout</button></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
<div class="kompensasi-navbar-utama" aria-hidden="true"></div>

<?php if (sudah_login()): ?>
    <div class="modal fade" id="modalKonfirmasiLogoutPasien" tabindex="-1" aria-labelledby="judulModalKonfirmasiLogoutPasien" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered"><div class="modal-content border-0">
            <div class="modal-header bg-success text-white"><h2 class="fs-5 modal-title" id="judulModalKonfirmasiLogoutPasien">Konfirmasi Logout</h2><button class="btn-close btn-close-white" type="button" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
            <div class="modal-body">Apakah Anda yakin ingin keluar?</div>
            <div class="modal-footer"><button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Tidak</button><a class="btn btn-success" href="<?= aman_html(url_aplikasi('auth/logout.php')); ?>">Ya, Keluar</a></div>
        </div></div>
    </div>
<?php endif; ?>
