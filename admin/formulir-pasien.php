<?php
// ================= FORMULIR DATA PASIEN =================
// Form tambah atau edit identitas dan akun login pasien oleh Admin.

declare(strict_types=1);

require_once __DIR__ . '/../includes/middleware_peran.php';
require_once __DIR__ . '/../konfigurasi/koneksi_database.php';

wajib_peran('admin');

$id_pasien = isset($_GET['id_pasien']) && ctype_digit((string) $_GET['id_pasien'])
    ? (int) $_GET['id_pasien']
    : null;
$mode_edit = $id_pasien !== null;
$data_pasien = [
    'id_pasien' => '',
    'nik' => '',
    'nama_pasien' => '',
    'email' => '',
    'tanggal_lahir' => '',
    'jenis_kelamin' => '',
    'alamat' => '',
    'nomor_telepon' => '',
    'jenis_pembayaran' => '',
];

if ($mode_edit) {
    $ambil_pasien = $koneksi_database->prepare(
        'SELECT p.*, u.email
         FROM pasien p
         INNER JOIN pengguna u ON u.id_pengguna = p.id_pengguna
         WHERE p.id_pasien = :id_pasien'
    );
    $ambil_pasien->execute(['id_pasien' => $id_pasien]);
    $hasil_pasien = $ambil_pasien->fetch();

    if ($hasil_pasien === false) {
        simpan_notifikasi('danger', 'Data pasien tidak ditemukan.');
        alihkan('admin/data-pasien.php');
    }

    $data_pasien = $hasil_pasien;
}

$judul_halaman = $mode_edit ? 'Edit Data Pasien' : 'Tambah Data Pasien';
$stylesheet_tambahan = 'assets/css/gaya-dashboard.css';
$menu_aktif = 'pasien';
require_once __DIR__ . '/../includes/kepala.php';
require_once __DIR__ . '/../includes/navbar_admin.php';
?>
<div class="d-flex dashboard-layout halaman-dashboard">
    <?php require_once __DIR__ . '/../includes/sidebar_admin.php'; ?>
    <main class="flex-grow-1 p-3 p-md-4 p-xl-5">
        <h1 class="h3 mb-1"><?= aman_html($judul_halaman); ?></h1>
        <p class="text-secondary mb-4">Lengkapi data pasien dengan benar.</p>
        <?php require_once __DIR__ . '/../includes/notifikasi.php'; ?>
        <section class="card kartu-dashboard" style="max-width: 760px;">
            <div class="card-body p-4">
                <form method="post" action="<?= aman_html(url_aplikasi('proses/proses_pasien.php')); ?>">
                    <input type="hidden" name="token_csrf" value="<?= aman_html(token_csrf()); ?>">
                    <input type="hidden" name="aksi" value="<?= $mode_edit ? 'ubah' : 'tambah'; ?>">
                    <?php if ($mode_edit): ?>
                        <input type="hidden" name="id_pasien" value="<?= (int) $data_pasien['id_pasien']; ?>">
                    <?php endif; ?>
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label" for="nama_pasien">Nama Pasien</label><input class="form-control" id="nama_pasien" name="nama_pasien" value="<?= aman_html($data_pasien['nama_pasien']); ?>" maxlength="100" required></div>
                        <div class="col-md-6"><label class="form-label" for="email">Email</label><input class="form-control" id="email" type="email" name="email" value="<?= aman_html($data_pasien['email']); ?>" maxlength="100" required></div>
                        <div class="col-md-6"><label class="form-label" for="nik">NIK</label><input class="form-control" id="nik" name="nik" type="text" value="<?= aman_html($data_pasien['nik']); ?>" inputmode="numeric" pattern="[0-9]{16}" minlength="16" maxlength="16" autocomplete="off" placeholder="16 digit NIK" oninvalid="this.setCustomValidity('NIK harus terdiri dari 16 digit angka.');" oninput="this.setCustomValidity('');" required><div class="form-text">Masukkan 16 digit angka sesuai NIK.</div></div>
                        <div class="col-md-6"><label class="form-label" for="jenis_pasien">Jenis Pasien</label><select class="form-select" id="jenis_pasien" name="jenis_pasien" required><option value="">Pilih jenis pasien</option><?php foreach (['umum' => 'Umum', 'bpjs' => 'BPJS', 'asuransi_lain' => 'Asuransi Lain'] as $nilai_jenis_pasien => $label_jenis_pasien): ?><option value="<?= aman_html($nilai_jenis_pasien); ?>" <?= $data_pasien['jenis_pembayaran'] === $nilai_jenis_pasien ? 'selected' : ''; ?>><?= aman_html($label_jenis_pasien); ?></option><?php endforeach; ?></select></div>
                        <div class="col-md-6"><label class="form-label" for="tanggal_lahir">Tanggal Lahir</label><input class="form-control" id="tanggal_lahir" type="date" name="tanggal_lahir" value="<?= aman_html($data_pasien['tanggal_lahir']); ?>" max="<?= date('Y-m-d'); ?>" required></div>
                        <div class="col-md-6"><label class="form-label" for="jenis_kelamin">Jenis Kelamin</label><select class="form-select" id="jenis_kelamin" name="jenis_kelamin" required><option value="">Pilih jenis kelamin</option><?php foreach (['Laki-laki', 'Perempuan'] as $jenis_kelamin): ?><option value="<?= aman_html($jenis_kelamin); ?>" <?= $data_pasien['jenis_kelamin'] === $jenis_kelamin ? 'selected' : ''; ?>><?= aman_html($jenis_kelamin); ?></option><?php endforeach; ?></select></div>
                        <div class="col-md-6"><label class="form-label" for="kata_sandi"><?= $mode_edit ? 'Kata Sandi Baru' : 'Kata Sandi Awal'; ?></label><div class="input-group form-password-pasien-admin"><input class="form-control border-end-0" id="kata_sandi" type="password" name="kata_sandi" minlength="8" autocomplete="new-password" oninvalid="this.setCustomValidity('Kata sandi harus memiliki minimal 8 karakter.');" oninput="this.setCustomValidity('');" <?= $mode_edit ? '' : 'required'; ?>><button class="btn btn-outline-secondary border-start-0" type="button" data-toggle-password="kata_sandi" aria-label="Tampilkan kata sandi" aria-pressed="false" title="Tampilkan kata sandi"><span data-ikon-password aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/><path d="M3 3l18 18"/></svg></span></button></div><div class="form-text"><?= $mode_edit ? 'Kosongkan jika tidak diubah. Jika diisi, minimal 8 karakter.' : 'Minimal 8 karakter.'; ?></div></div>
                        <div class="col-md-6"><label class="form-label" for="nomor_telepon">Nomor Telepon</label><input class="form-control" id="nomor_telepon" name="nomor_telepon" value="<?= aman_html($data_pasien['nomor_telepon']); ?>" maxlength="20" required></div>
                        <div class="col-12"><label class="form-label" for="alamat">Alamat</label><textarea class="form-control" id="alamat" name="alamat" rows="3" maxlength="1000" required><?= aman_html($data_pasien['alamat']); ?></textarea></div>
                    </div>
                    <button class="btn btn-success mt-4"><?= $mode_edit ? 'Simpan Perubahan' : 'Simpan Pasien'; ?></button>
                    <a class="btn btn-outline-secondary mt-4" href="<?= aman_html(url_aplikasi('admin/data-pasien.php')); ?>">Batal</a>
                </form>
            </div>
        </section>
    </main>
</div>
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
