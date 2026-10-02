<?php
// ================= HALAMAN RESET KATA SANDI =================
// Menampilkan form kata sandi baru hanya untuk otorisasi reset pasien yang masih berlaku.

declare(strict_types=1);

require_once __DIR__ . '/../includes/pembantu.php';

const MASA_BERLAKU_RESET_PASSWORD = 600;

/** Mengambil otorisasi reset yang masih berlaku tanpa memakai ID dari URL. */
function otorisasi_reset_password_valid(): bool
{
    $otorisasi = $_SESSION['otorisasi_reset_password'] ?? null;
    $dibuat_pada = is_array($otorisasi) && isset($otorisasi['dibuat_pada']) ? (int) $otorisasi['dibuat_pada'] : 0;
    if (
        !is_array($otorisasi)
        || !isset($otorisasi['id_pengguna'], $otorisasi['peran'], $otorisasi['dibuat_pada'], $otorisasi['nonce'])
        || (int) $otorisasi['id_pengguna'] < 1
        || $otorisasi['peran'] !== 'pasien'
        || !is_string($otorisasi['nonce'])
        || strlen($otorisasi['nonce']) !== 64
        || !ctype_xdigit($otorisasi['nonce'])
        || $dibuat_pada < 1
        || $dibuat_pada > time()
        || time() - $dibuat_pada > MASA_BERLAKU_RESET_PASSWORD
    ) {
        unset($_SESSION['otorisasi_reset_password']);
        return false;
    }

    return true;
}

if (!otorisasi_reset_password_valid()) {
    simpan_notifikasi('warning', 'Verifikasi pemulihan tidak valid atau telah kedaluwarsa. Silakan verifikasi kembali.');
    alihkan('auth/lupa-kata-sandi.php');
}

$judul_halaman = 'Reset Kata Sandi';
require_once __DIR__ . '/../includes/kepala.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<main class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-5">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4 p-md-5">
                    <h1 class="h3 text-success mb-2">Buat Kata Sandi Baru</h1>
                    <p class="text-secondary mb-4">Masukkan kata sandi baru untuk akun pasien Anda.</p>
                    <?php require_once __DIR__ . '/../includes/notifikasi.php'; ?>
                    <form action="<?= aman_html(url_aplikasi('proses/proses_reset_kata_sandi.php')); ?>" method="post">
                        <input type="hidden" name="token_csrf" value="<?= aman_html(token_csrf()); ?>">
                        <div class="mb-3">
                            <label for="kata_sandi_baru" class="form-label">Kata Sandi Baru</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="kata_sandi_baru" name="kata_sandi_baru" minlength="8" autocomplete="new-password" required>
                                <button class="btn btn-outline-secondary" type="button" data-toggle-password="kata_sandi_baru" aria-label="Tampilkan kata sandi baru" aria-pressed="false" title="Tampilkan kata sandi baru">
                                    <span data-ikon-password aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/><path d="M3 3l18 18"/></svg></span>
                                </button>
                            </div>
                            <div class="form-text">Minimal 8 karakter.</div>
                        </div>
                        <div class="mb-4">
                            <label for="konfirmasi_kata_sandi" class="form-label">Konfirmasi Kata Sandi Baru</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="konfirmasi_kata_sandi" name="konfirmasi_kata_sandi" minlength="8" autocomplete="new-password" required>
                                <button class="btn btn-outline-secondary" type="button" data-toggle-password="konfirmasi_kata_sandi" aria-label="Tampilkan konfirmasi kata sandi" aria-pressed="false" title="Tampilkan konfirmasi kata sandi">
                                    <span data-ikon-password aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/><path d="M3 3l18 18"/></svg></span>
                                </button>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-success w-100">Simpan Kata Sandi Baru</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const ikonMataTerbuka = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>';
    const ikonMataTertutup = ikonMataTerbuka.replace('</svg>', '<path d="M3 3l18 18"/></svg>');

    document.querySelectorAll('[data-toggle-password]').forEach(function (tombol) {
        const inputKataSandi = document.getElementById(tombol.dataset.togglePassword);
        const ikonKataSandi = tombol.querySelector('[data-ikon-password]');

        tombol.addEventListener('click', function () {
            const kataSandiTerlihat = inputKataSandi.type === 'password';
            inputKataSandi.type = kataSandiTerlihat ? 'text' : 'password';
            tombol.setAttribute('aria-pressed', String(kataSandiTerlihat));
            tombol.setAttribute('aria-label', kataSandiTerlihat ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
            tombol.title = kataSandiTerlihat ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi';
            ikonKataSandi.innerHTML = kataSandiTerlihat ? ikonMataTerbuka : ikonMataTertutup;
        });
    });
});
</script>
<?php require_once __DIR__ . '/../includes/kaki.php'; ?>
