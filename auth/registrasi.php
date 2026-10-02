<?php
// ================= HALAMAN REGISTRASI PASIEN =================
// Form pembuatan akun dan data identitas awal bagi pasien.

declare(strict_types=1);

require_once __DIR__ . '/../includes/pembantu.php';

if (sudah_login()) {
    $halaman_tujuan = $_SESSION['pengguna']['peran'] === 'admin'
        ? 'admin/dashboard.php'
        : 'pasien/beranda.php';
    alihkan($halaman_tujuan);
}

$judul_halaman = 'Registrasi Pasien';
require_once __DIR__ . '/../includes/kepala.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<main class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4 p-md-5">
                    <h1 class="h3 text-success mb-2">Registrasi Pasien</h1>
                    <p class="text-secondary mb-4">Lengkapi data berikut untuk membuat akun pasien.</p>
                    <?php require_once __DIR__ . '/../includes/notifikasi.php'; ?>
                    <form action="<?= aman_html(url_aplikasi('proses/proses_registrasi.php')); ?>" method="post">
                        <input type="hidden" name="token_csrf" value="<?= aman_html(token_csrf()); ?>">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="nama_pasien" class="form-label">Nama Lengkap</label>
                                <input type="text" class="form-control" id="nama_pasien" name="nama_pasien" maxlength="100" autocomplete="name" required>
                            </div>
                            <div class="col-md-6">
                                <label for="tanggal_lahir" class="form-label">Tanggal Lahir</label>
                                <input type="date" class="form-control" id="tanggal_lahir" name="tanggal_lahir" max="<?= date('Y-m-d'); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="nik" class="form-label">NIK</label>
                                <input type="text" class="form-control" id="nik" name="nik" inputmode="numeric" pattern="[0-9]{16}" minlength="16" maxlength="16" autocomplete="off" placeholder="16 digit NIK" oninvalid="this.setCustomValidity('NIK harus terdiri dari 16 digit angka.');" oninput="this.setCustomValidity('');" required>
                                <div class="form-text">Masukkan 16 digit angka sesuai NIK.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="jenis_kelamin" class="form-label">Jenis Kelamin</label>
                                <select class="form-select" id="jenis_kelamin" name="jenis_kelamin" required>
                                    <option value="">Pilih jenis kelamin</option>
                                    <option value="Laki-laki">Laki-laki</option>
                                    <option value="Perempuan">Perempuan</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="jenis_pasien" class="form-label">Jenis Pasien</label>
                                <select class="form-select" id="jenis_pasien" name="jenis_pasien" required>
                                    <option value="">Pilih jenis pasien</option>
                                    <option value="umum">Umum</option>
                                    <option value="bpjs">BPJS</option>
                                    <option value="asuransi_lain">Asuransi Lain</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" maxlength="100" autocomplete="email" required>
                            </div>
                            <div class="col-12">
                                <label for="nomor_telepon" class="form-label">Nomor Telepon</label>
                                <input type="tel" class="form-control" id="nomor_telepon" name="nomor_telepon" maxlength="20" autocomplete="tel" required>
                            </div>
                            <div class="col-12">
                                <label for="alamat" class="form-label">Alamat</label>
                                <textarea class="form-control" id="alamat" name="alamat" rows="3" maxlength="1000" required></textarea>
                            </div>
                            <div class="col-md-6">
                                <label for="kata_sandi" class="form-label">Kata Sandi</label>
                                <div class="input-group form-password-registrasi"><input type="password" class="form-control border-end-0" id="kata_sandi" name="kata_sandi" minlength="8" autocomplete="new-password" oninvalid="this.setCustomValidity('Kata sandi harus memiliki minimal 8 karakter.');" oninput="this.setCustomValidity('');" required><button class="btn btn-outline-secondary border-start-0" type="button" data-toggle-password="kata_sandi" aria-label="Tampilkan kata sandi" aria-pressed="false" title="Tampilkan kata sandi"><span data-ikon-password aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 12 12Z"/><circle cx="12" cy="12" r="3"/><path d="M3 3l18 18"/></svg></span></button></div>
                                <div class="form-text">Minimal 8 karakter.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="konfirmasi_kata_sandi" class="form-label">Konfirmasi Kata Sandi</label>
                                <div class="input-group form-password-registrasi"><input type="password" class="form-control border-end-0" id="konfirmasi_kata_sandi" name="konfirmasi_kata_sandi" minlength="8" autocomplete="new-password" oninvalid="this.setCustomValidity('Kata sandi harus memiliki minimal 8 karakter.');" oninput="this.setCustomValidity('');" required><button class="btn btn-outline-secondary border-start-0" type="button" data-toggle-password="konfirmasi_kata_sandi" aria-label="Tampilkan konfirmasi kata sandi" aria-pressed="false" title="Tampilkan konfirmasi kata sandi"><span data-ikon-password aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 12 12Z"/><circle cx="12" cy="12" r="3"/><path d="M3 3l18 18"/></svg></span></button></div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-success w-100 mt-4">Daftar sebagai Pasien</button>
                    </form>
                    <p class="text-center small mt-3 mb-0">Sudah memiliki akun? <a href="<?= aman_html(url_aplikasi('auth/login.php')); ?>">Login</a></p>
                </div>
            </div>
        </div>
    </div>
</main>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const ikonMataTerbuka = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 12 12Z"/><circle cx="12" cy="12" r="3"/></svg>';
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
