<?php
// ================= MODUL AKUN DOKTER =================
// Menampilkan serta menyediakan form pembuatan akun Dokter untuk Admin.

declare(strict_types=1);

require_once __DIR__ . '/../includes/middleware_peran.php';
require_once __DIR__ . '/../konfigurasi/koneksi_database.php';

wajib_peran('admin');

// ================= PENGAMBILAN DATA AKUN DOKTER =================

$cari = trim((string) ($_GET['cari'] ?? ''));
if (strlen($cari) > 100) {
    $cari = substr($cari, 0, 100);
}

$sql_dokter =
    "SELECT id_pengguna, nama_pengguna, email, dibuat_pada
     FROM pengguna
     WHERE peran = 'dokter'";
$parameter_dokter = [];

if ($cari !== '') {
    $sql_dokter .=
        ' AND (LOWER(nama_pengguna) LIKE LOWER(:cari_nama)
               OR LOWER(email) LIKE LOWER(:cari_email))';
    $nilai_cari = '%' . $cari . '%';
    $parameter_dokter['cari_nama'] = $nilai_cari;
    $parameter_dokter['cari_email'] = $nilai_cari;
}

$sql_dokter .= ' ORDER BY nama_pengguna ASC';
$ambil_dokter = $koneksi_database->prepare(
    $sql_dokter
);
$ambil_dokter->execute($parameter_dokter);
$daftar_dokter = $ambil_dokter->fetchAll();

$judul_halaman = 'Kelola Akun Dokter';
$stylesheet_tambahan = 'assets/css/gaya-dashboard.css?v=' . filemtime(__DIR__ . '/../assets/css/gaya-dashboard.css');
$menu_aktif = 'akun-dokter';
require_once __DIR__ . '/../includes/kepala.php';
require_once __DIR__ . '/../includes/navbar_admin.php';
?>
<div class="d-flex dashboard-layout halaman-dashboard">
    <?php require_once __DIR__ . '/../includes/sidebar_admin.php'; ?>
    <main class="flex-grow-1 p-3 p-md-4 p-xl-5 overflow-hidden">
        <div class="mb-4">
            <p class="teks-label mb-1">Manajemen Dokter</p>
            <h1 class="h3 mb-1">Kelola Akun Dokter</h1>
            <p class="text-secondary mb-0">Buat akun dokter yang dapat login ke Dashboard Dokter.</p>
        </div>

        <?php require_once __DIR__ . '/../includes/notifikasi.php'; ?>

        <section class="card kartu-dashboard mb-4">
            <div class="card-body p-3 p-md-4">
                <h2 class="judul-kartu mb-3">Tambah Akun Dokter</h2>
                <form class="row g-3" method="post" action="<?= aman_html(url_aplikasi('proses/proses_akun_dokter.php')); ?>">
                    <input type="hidden" name="token_csrf" value="<?= aman_html(token_csrf()); ?>">
                    <input type="hidden" name="aksi" value="tambah">
                    <div class="col-md-6">
                        <label class="form-label" for="nama_pengguna">Nama Dokter</label>
                        <input class="form-control" id="nama_pengguna" name="nama_pengguna" type="text" maxlength="100" autocomplete="name" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="email">Email</label>
                        <input class="form-control" id="email" name="email" type="email" maxlength="100" autocomplete="email" oninvalid="this.setCustomValidity(this.validity.typeMismatch ? 'Masukkan alamat email yang valid.' : '');" oninput="this.setCustomValidity('');" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="kata_sandi">Kata Sandi Awal</label>
                        <div class="input-group form-password-dokter">
                            <input class="form-control border-end-0" id="kata_sandi" name="kata_sandi" type="password" minlength="8" autocomplete="new-password" oninvalid="this.setCustomValidity(this.validity.tooShort ? 'Kata sandi harus memiliki minimal 8 karakter.' : '');" oninput="this.setCustomValidity('');" required>
                            <button class="btn btn-outline-secondary border-start-0" id="tombol_lihat_kata_sandi" type="button" aria-label="Tampilkan kata sandi" aria-pressed="false" title="Tampilkan kata sandi">
                                <span data-ikon-password aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/><path d="M3 3l18 18"/></svg></span>
                            </button>
                        </div>
                        <div class="form-text">Minimal 8 karakter.</div>
                    </div>
                    <div class="col-12"><button class="btn btn-success" type="submit">Simpan Akun Dokter</button></div>
                </form>
            </div>
        </section>

        <section class="card kartu-dashboard">
            <div class="card-body p-0">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 p-3 p-md-4 border-bottom">
                    <div>
                        <h2 class="judul-kartu mb-1">Daftar Akun Dokter</h2>
                        <span class="small text-secondary"><?= count($daftar_dokter); ?> data</span>
                    </div>
                    <form class="w-100 formulir-cari-akun-dokter" method="get" style="max-width: 360px;">
                        <label class="visually-hidden" for="cari">Cari akun dokter</label>
                        <div class="input-group">
                            <input class="form-control" id="cari" name="cari" type="search" value="<?= aman_html($cari); ?>" maxlength="100" placeholder="Cari nama atau email dokter...">
                            <button class="btn btn-outline-success" type="submit">Cari</button>
                        </div>
                    </form>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-success">
                            <tr><th class="ps-3">Nama Dokter</th><th>Email</th><th>Role</th><th>Dibuat Pada</th><th class="text-end pe-3">Aksi</th></tr>
                        </thead>
                        <tbody>
                            <?php if ($daftar_dokter === []): ?>
                                <tr><td colspan="5" class="text-center text-secondary py-5"><?= $cari !== '' ? 'Data dokter tidak ditemukan.' : 'Belum ada akun dokter.'; ?></td></tr>
                            <?php else: ?>
                                <?php foreach ($daftar_dokter as $dokter): ?>
                                    <tr>
                                        <td class="ps-3 fw-semibold"><?= aman_html($dokter['nama_pengguna']); ?></td>
                                        <td><?= aman_html($dokter['email']); ?></td>
                                        <td><span class="badge text-bg-success">Dokter</span></td>
                                        <td><?= aman_html(date('d-m-Y H:i', strtotime($dokter['dibuat_pada']))); ?></td>
                                        <td class="text-end pe-3">
                                            <div class="d-inline-flex align-items-center gap-2">
                                                <a class="btn btn-sm btn-outline-success" href="<?= aman_html(url_aplikasi('admin/formulir-akun-dokter.php?id_pengguna=' . (int) $dokter['id_pengguna'])); ?>">Edit</a>
                                                <form id="formulir-hapus-dokter-<?= (int) $dokter['id_pengguna']; ?>" class="mb-0" method="post" action="<?= aman_html(url_aplikasi('proses/proses_akun_dokter.php')); ?>">
                                                    <input type="hidden" name="token_csrf" value="<?= aman_html(token_csrf()); ?>">
                                                    <input type="hidden" name="aksi" value="hapus">
                                                    <input type="hidden" name="id_pengguna" value="<?= (int) $dokter['id_pengguna']; ?>">
                                                    <button class="btn btn-sm btn-outline-danger tombol-hapus-dokter" type="button" data-bs-toggle="modal" data-bs-target="#modalHapusDokter" data-id-formulir="formulir-hapus-dokter-<?= (int) $dokter['id_pengguna']; ?>">Hapus</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </main>
</div>

<!-- ================= MODAL KONFIRMASI HAPUS AKUN DOKTER ================= -->
<div class="modal fade" id="modalHapusDokter" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"><div class="modal-content border-0">
        <div class="modal-header bg-success text-white"><h2 class="fs-5 modal-title">Konfirmasi Hapus Akun Dokter</h2><button class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
        <div class="modal-body">Akun hanya dapat dihapus jika belum memiliki penugasan poli dan belum terkait antrean.</div>
        <div class="modal-footer"><button class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button><button class="btn btn-success" id="konfirmasiHapusDokter">Ya, Hapus</button></div>
    </div></div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Mengubah tampilan kata sandi tanpa mengubah nilai maupun data yang dikirim formulir.
    const inputKataSandi = document.getElementById('kata_sandi');
    const tombolLihatKataSandi = document.getElementById('tombol_lihat_kata_sandi');
    const ikonKataSandi = tombolLihatKataSandi.querySelector('[data-ikon-password]');
    const ikonMataTerbuka = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>';
    const ikonMataTertutup = ikonMataTerbuka.replace('</svg>', '<path d="M3 3l18 18"/></svg>');

    tombolLihatKataSandi.addEventListener('click', function () {
        const kataSandiTerlihat = inputKataSandi.type === 'password';

        inputKataSandi.type = kataSandiTerlihat ? 'text' : 'password';
        tombolLihatKataSandi.setAttribute('aria-pressed', String(kataSandiTerlihat));
        tombolLihatKataSandi.setAttribute('aria-label', kataSandiTerlihat ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
        tombolLihatKataSandi.title = kataSandiTerlihat ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi';
        ikonKataSandi.innerHTML = kataSandiTerlihat ? ikonMataTerbuka : ikonMataTertutup;
    });

    let idFormulir = '';
    document.querySelectorAll('.tombol-hapus-dokter').forEach(function (tombol) {
        tombol.addEventListener('click', function () { idFormulir = this.dataset.idFormulir; });
    });
    document.getElementById('konfirmasiHapusDokter').addEventListener('click', function () {
        const formulir = document.getElementById(idFormulir);
        if (formulir) { formulir.submit(); }
    });
});
</script>
<?php require_once __DIR__ . '/../includes/kaki.php'; ?>
