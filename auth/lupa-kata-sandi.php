<?php
// ================= HALAMAN LUPA KATA SANDI =================
// Meminta data identitas pasien sebelum memberi otorisasi reset sementara.

declare(strict_types=1);

require_once __DIR__ . '/../includes/pembantu.php';

$judul_halaman = 'Lupa Kata Sandi';
require_once __DIR__ . '/../includes/kepala.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<main class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-5">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4 p-md-5">
                    <h1 class="h3 text-success mb-2">Lupa Kata Sandi</h1>
                    <p class="text-secondary mb-4">Masukkan data identitas sesuai akun pasien untuk melanjutkan pemulihan kata sandi.</p>
                    <?php require_once __DIR__ . '/../includes/notifikasi.php'; ?>
                    <form action="<?= aman_html(url_aplikasi('proses/proses_lupa_kata_sandi.php')); ?>" method="post">
                        <input type="hidden" name="token_csrf" value="<?= aman_html(token_csrf()); ?>">
                        <div class="mb-4">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" maxlength="100" autocomplete="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="nik" class="form-label">NIK</label>
                            <input type="text" class="form-control" id="nik" name="nik" inputmode="numeric" pattern="[0-9]{16}" minlength="16" maxlength="16" autocomplete="off" placeholder="16 digit NIK" required>
                        </div>
                        <div class="mb-4">
                            <label for="tanggal_lahir" class="form-label">Tanggal Lahir</label>
                            <input type="date" class="form-control" id="tanggal_lahir" name="tanggal_lahir" max="<?= date('Y-m-d'); ?>" required>
                        </div>
                        <button type="submit" class="btn btn-success w-100">Verifikasi Identitas</button>
                    </form>
                    <p class="text-center small mt-3 mb-0"><a href="<?= aman_html(url_aplikasi('auth/login.php')); ?>">Kembali ke Login</a></p>
                </div>
            </div>
        </div>
    </div>
</main>
<?php require_once __DIR__ . '/../includes/kaki.php'; ?>
