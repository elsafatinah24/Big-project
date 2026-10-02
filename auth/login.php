<?php
// ================= HALAMAN LOGIN =================
// Form autentikasi dasar untuk Admin dan Pasien.

declare(strict_types=1);

require_once __DIR__ . '/../includes/pembantu.php';

if (sudah_login()) {
    $halaman_tujuan = match ($_SESSION['pengguna']['peran']) {
        'admin' => 'admin/dashboard.php',
        'dokter' => 'dokter/dashboard.php',
        default => 'pasien/beranda.php',
    };
    alihkan($halaman_tujuan);
}

$judul_halaman = 'Login';
require_once __DIR__ . '/../includes/kepala.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<main class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-5">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4 p-md-5">
                    <h1 class="h3 text-success mb-2">Login</h1>
                    <p class="text-secondary mb-4">Masuk untuk melanjutkan ke aplikasi.</p>
                    <?php require_once __DIR__ . '/../includes/notifikasi.php'; ?>
                    <form action="<?= aman_html(url_aplikasi('proses/proses_login.php')); ?>" method="post">
                        <input type="hidden" name="token_csrf" value="<?= aman_html(token_csrf()); ?>">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" autocomplete="email" required>
                        </div>
                        <div class="mb-4">
                            <label for="kata_sandi" class="form-label">Kata Sandi</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="kata_sandi" name="kata_sandi" autocomplete="current-password" required>
                                <button class="btn btn-outline-secondary" id="tombol_lihat_kata_sandi" type="button" aria-label="Lihat kata sandi" aria-pressed="false" title="Lihat kata sandi" style="width: auto;">
                                    <span data-ikon-password aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/><path d="M3 3l18 18"/></svg></span>
                                </button>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-success w-100">Login</button>
                    </form>
                    <p class="text-center small mt-3 mb-0"><a href="<?= aman_html(url_aplikasi('auth/lupa-kata-sandi.php')); ?>">Lupa Kata Sandi?</a></p>
                    <p class="text-center small mt-3 mb-0">Belum memiliki akun? <a href="<?= aman_html(url_aplikasi('auth/registrasi.php')); ?>">Registrasi sebagai pasien</a></p>
                </div>
            </div>
        </div>
    </div>
</main>
<script>
// ================= TOGGLE KATA SANDI =================
// Mengubah tampilan kata sandi tanpa mengubah nilai maupun data yang dikirim formulir.
document.addEventListener('DOMContentLoaded', function () {
    const inputKataSandi = document.getElementById('kata_sandi');
    const tombolLihatKataSandi = document.getElementById('tombol_lihat_kata_sandi');
    const ikonKataSandi = tombolLihatKataSandi.querySelector('[data-ikon-password]');
    const ikonMataTerbuka = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>';
    const ikonMataTertutup = ikonMataTerbuka.replace('</svg>', '<path d="M3 3l18 18"/></svg>');

    tombolLihatKataSandi.addEventListener('click', function () {
        const kataSandiTerlihat = inputKataSandi.type === 'password';

        inputKataSandi.type = kataSandiTerlihat ? 'text' : 'password';
        tombolLihatKataSandi.setAttribute('aria-pressed', String(kataSandiTerlihat));
        tombolLihatKataSandi.setAttribute('aria-label', kataSandiTerlihat ? 'Sembunyikan kata sandi' : 'Lihat kata sandi');
        tombolLihatKataSandi.title = kataSandiTerlihat ? 'Sembunyikan kata sandi' : 'Lihat kata sandi';
        ikonKataSandi.innerHTML = kataSandiTerlihat ? ikonMataTerbuka : ikonMataTertutup;
    });
});
</script>
<?php require_once __DIR__ . '/../includes/kaki.php'; ?>
