<?php
// ================= FORMULIR AKUN DOKTER =================
// Menampilkan formulir edit data akun Dokter oleh Admin.

declare(strict_types=1);

require_once __DIR__ . '/../includes/middleware_peran.php';
require_once __DIR__ . '/../konfigurasi/koneksi_database.php';

wajib_peran('admin');

$id_pengguna = isset($_GET['id_pengguna']) && ctype_digit((string) $_GET['id_pengguna'])
    ? (int) $_GET['id_pengguna']
    : 0;

if ($id_pengguna < 1) {
    simpan_notifikasi('danger', 'Identitas akun dokter tidak valid.');
    alihkan('admin/data-akun-dokter.php');
}

$ambil_dokter = $koneksi_database->prepare(
    "SELECT id_pengguna, nama_pengguna, email
     FROM pengguna
     WHERE id_pengguna = :id_pengguna AND peran = 'dokter'
     LIMIT 1"
);
$ambil_dokter->execute(['id_pengguna' => $id_pengguna]);
$data_dokter = $ambil_dokter->fetch();

if ($data_dokter === false) {
    simpan_notifikasi('danger', 'Akun dokter tidak ditemukan.');
    alihkan('admin/data-akun-dokter.php');
}

$judul_halaman = 'Edit Akun Dokter';
$stylesheet_tambahan = 'assets/css/gaya-dashboard.css';
$menu_aktif = 'akun-dokter';
require_once __DIR__ . '/../includes/kepala.php';
require_once __DIR__ . '/../includes/navbar_admin.php';
?>
<div class="d-flex dashboard-layout halaman-dashboard">
    <?php require_once __DIR__ . '/../includes/sidebar_admin.php'; ?>
    <main class="flex-grow-1 p-3 p-md-4 p-xl-5">
        <div class="mb-4">
            <p class="teks-label mb-1">Manajemen Dokter</p>
            <h1 class="h3 mb-1">Edit Akun Dokter</h1>
            <p class="text-secondary mb-0">Perbarui nama, email, atau kata sandi akun dokter.</p>
        </div>
        <?php require_once __DIR__ . '/../includes/notifikasi.php'; ?>

        <section class="card kartu-dashboard" style="max-width: 760px;">
            <div class="card-body p-4">
                <form method="post" action="<?= aman_html(url_aplikasi('proses/proses_akun_dokter.php')); ?>">
                    <input type="hidden" name="token_csrf" value="<?= aman_html(token_csrf()); ?>">
                    <input type="hidden" name="aksi" value="ubah">
                    <input type="hidden" name="id_pengguna" value="<?= (int) $data_dokter['id_pengguna']; ?>">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="nama_pengguna">Nama Dokter</label>
                            <input class="form-control" id="nama_pengguna" name="nama_pengguna" type="text" value="<?= aman_html($data_dokter['nama_pengguna']); ?>" maxlength="100" autocomplete="name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="email">Email</label>
                            <input class="form-control" id="email" name="email" type="email" value="<?= aman_html($data_dokter['email']); ?>" maxlength="100" autocomplete="email" oninvalid="this.setCustomValidity(this.validity.typeMismatch ? 'Masukkan alamat email yang valid.' : '');" oninput="this.setCustomValidity('');" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="kata_sandi">Kata Sandi Baru</label>
                            <input class="form-control" id="kata_sandi" name="kata_sandi" type="password" minlength="8" autocomplete="new-password" oninvalid="this.setCustomValidity(this.validity.tooShort ? 'Kata sandi harus memiliki minimal 8 karakter.' : '');" oninput="this.setCustomValidity('');">
                            <div class="form-text">Kosongkan jika kata sandi tidak diubah. Jika diisi, minimal 8 karakter.</div>
                        </div>
                    </div>
                    <button class="btn btn-success mt-4" type="submit">Simpan Perubahan</button>
                    <a class="btn btn-outline-secondary mt-4" href="<?= aman_html(url_aplikasi('admin/data-akun-dokter.php')); ?>">Batal</a>
                </form>
            </div>
        </section>
    </main>
</div>
<?php require_once __DIR__ . '/../includes/kaki.php'; ?>
