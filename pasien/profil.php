<?php
// ================= HALAMAN PROFIL PASIEN =================
// Menampilkan data akun pasien serta form pembaruan profil dan kata sandi.

declare(strict_types=1);

require_once __DIR__ . '/../includes/middleware_peran.php';
require_once __DIR__ . '/../konfigurasi/koneksi_database.php';

wajib_peran('pasien');

// ================= DATA PROFIL PASIEN =================

$ambil_profil = $koneksi_database->prepare(
    'SELECT p.nama_pasien, p.tanggal_lahir, p.jenis_kelamin, p.alamat, p.nomor_telepon, u.nama_pengguna
     FROM pasien p
     INNER JOIN pengguna u ON u.id_pengguna = p.id_pengguna
     WHERE p.id_pengguna = :id_pengguna
     LIMIT 1'
);
$ambil_profil->execute([
    'id_pengguna' => (int) $_SESSION['pengguna']['id_pengguna'],
]);
$profil = $ambil_profil->fetch();

if ($profil === false) {
    simpan_notifikasi('danger', 'Data profil pasien tidak ditemukan.');
    alihkan('pasien/beranda.php');
}

$judul_halaman = 'Profil Pasien';
$stylesheet_tambahan = 'assets/css/gaya-dashboard.css';
require_once __DIR__ . '/../includes/kepala.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<div class="d-flex dashboard-layout halaman-dashboard">
    <?php require_once __DIR__ . '/../includes/sidebar_pasien.php'; ?>
    <main class="flex-grow-1 p-3 p-md-4 p-xl-5">
        <div class="mb-4">
            <p class="teks-label mb-1">Akun Pasien</p>
            <h1 class="h3 mb-1">Profil Saya</h1>
            <p class="text-secondary mb-0">Kelola data profil dan keamanan akun Anda.</p>
        </div>

        <?php require_once __DIR__ . '/../includes/notifikasi.php'; ?>

        <div class="row g-4">
            <div class="col-xl-4">
                <section class="card kartu-dashboard h-100">
                    <div class="card-body p-4">
                        <h2 class="judul-kartu mb-4">Data Profil</h2>
                        <dl class="mb-0">
                            <dt class="small text-secondary fw-normal mb-1">Nama Lengkap</dt>
                            <dd class="fw-semibold mb-3"><?= aman_html($profil['nama_pasien']); ?></dd>
                            <dt class="small text-secondary fw-normal mb-1">Username</dt>
                            <dd class="mb-3"><?= aman_html($profil['nama_pengguna']); ?></dd>
                            <dt class="small text-secondary fw-normal mb-1">Nomor HP</dt>
                            <dd class="mb-3"><?= aman_html($profil['nomor_telepon']); ?></dd>
                            <dt class="small text-secondary fw-normal mb-1">Alamat</dt>
                            <dd class="mb-3"><?= aman_html($profil['alamat']); ?></dd>
                            <dt class="small text-secondary fw-normal mb-1">Tanggal Lahir</dt>
                            <dd class="mb-3"><?= aman_html(date('d/m/Y', strtotime($profil['tanggal_lahir']))); ?></dd>
                            <dt class="small text-secondary fw-normal mb-1">Jenis Kelamin</dt>
                            <dd class="mb-0"><?= aman_html($profil['jenis_kelamin']); ?></dd>
                        </dl>
                    </div>
                </section>
            </div>

            <div class="col-xl-8">
                <section class="card kartu-dashboard mb-4">
                    <div class="card-body p-4">
                        <h2 class="judul-kartu mb-4">Ubah Profil</h2>
                        <form method="post" action="<?= aman_html(url_aplikasi('proses/proses_profil_pasien.php')); ?>">
                            <input type="hidden" name="token_csrf" value="<?= aman_html(token_csrf()); ?>">
                            <input type="hidden" name="aksi" value="ubah_profil">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="nama_pasien">Nama Lengkap</label>
                                    <input class="form-control" id="nama_pasien" name="nama_pasien" value="<?= aman_html($profil['nama_pasien']); ?>" maxlength="100" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="nama_pengguna">Username</label>
                                    <input class="form-control" id="nama_pengguna" name="nama_pengguna" value="<?= aman_html($profil['nama_pengguna']); ?>" maxlength="100" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="nomor_telepon">Nomor HP</label>
                                    <input class="form-control" id="nomor_telepon" name="nomor_telepon" value="<?= aman_html($profil['nomor_telepon']); ?>" maxlength="20" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="tanggal_lahir">Tanggal Lahir</label>
                                    <input class="form-control" type="date" id="tanggal_lahir" name="tanggal_lahir" value="<?= aman_html($profil['tanggal_lahir']); ?>" max="<?= date('Y-m-d'); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="jenis_kelamin">Jenis Kelamin</label>
                                    <select class="form-select" id="jenis_kelamin" name="jenis_kelamin" required>
                                        <?php foreach (['Laki-laki', 'Perempuan'] as $jenis_kelamin): ?>
                                            <option value="<?= aman_html($jenis_kelamin); ?>" <?= $profil['jenis_kelamin'] === $jenis_kelamin ? 'selected' : ''; ?>><?= aman_html($jenis_kelamin); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="alamat">Alamat</label>
                                    <textarea class="form-control" id="alamat" name="alamat" rows="3" maxlength="1000" required><?= aman_html($profil['alamat']); ?></textarea>
                                </div>
                            </div>
                            <button class="btn btn-success mt-4" type="submit">Simpan Profil</button>
                        </form>
                    </div>
                </section>

                <section class="card kartu-dashboard">
                    <div class="card-body p-4">
                        <h2 class="judul-kartu mb-4">Ubah Kata Sandi</h2>
                        <form method="post" action="<?= aman_html(url_aplikasi('proses/proses_profil_pasien.php')); ?>">
                            <input type="hidden" name="token_csrf" value="<?= aman_html(token_csrf()); ?>">
                            <input type="hidden" name="aksi" value="ubah_kata_sandi">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label" for="kata_sandi_lama">Kata Sandi Lama</label>
                                    <div class="input-group form-password-profil">
                                        <input class="form-control border-end-0" type="password" id="kata_sandi_lama" name="kata_sandi_lama" autocomplete="current-password" required>
                                        <button class="btn btn-outline-secondary border-start-0" type="button" data-toggle-password="kata_sandi_lama" aria-label="Tampilkan kata sandi lama" aria-pressed="false" title="Tampilkan kata sandi lama"><span data-ikon-password aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/><path d="M3 3l18 18"/></svg></span></button>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="kata_sandi_baru">Kata Sandi Baru</label>
                                    <div class="input-group form-password-profil">
                                        <input class="form-control border-end-0" type="password" id="kata_sandi_baru" name="kata_sandi_baru" minlength="8" autocomplete="new-password" required>
                                        <button class="btn btn-outline-secondary border-start-0" type="button" data-toggle-password="kata_sandi_baru" aria-label="Tampilkan kata sandi baru" aria-pressed="false" title="Tampilkan kata sandi baru"><span data-ikon-password aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/><path d="M3 3l18 18"/></svg></span></button>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="konfirmasi_kata_sandi">Konfirmasi Kata Sandi Baru</label>
                                    <div class="input-group form-password-profil">
                                        <input class="form-control border-end-0" type="password" id="konfirmasi_kata_sandi" name="konfirmasi_kata_sandi" minlength="8" autocomplete="new-password" required>
                                        <button class="btn btn-outline-secondary border-start-0" type="button" data-toggle-password="konfirmasi_kata_sandi" aria-label="Tampilkan konfirmasi kata sandi baru" aria-pressed="false" title="Tampilkan konfirmasi kata sandi baru"><span data-ikon-password aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/><path d="M3 3l18 18"/></svg></span></button>
                                    </div>
                                </div>
                            </div>
                            <button class="btn btn-success mt-4" type="submit">Ubah Kata Sandi</button>
                        </form>
                    </div>
                </section>
            </div>
        </div>
    </main>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const ikonMataTerbuka = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>';
    const ikonMataTertutup = ikonMataTerbuka.replace('</svg>', '<path d="M3 3l18 18"/></svg>');

    document.querySelectorAll('.form-password-profil [data-toggle-password]').forEach(function (tombol) {
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
