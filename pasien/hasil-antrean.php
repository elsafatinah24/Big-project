<?php
// ================= HALAMAN HASIL ANTREAN =================
// Menampilkan tiket antrean yang baru dibuat milik pasien yang sedang login.

declare(strict_types=1);

require_once __DIR__ . '/../includes/middleware_peran.php';
require_once __DIR__ . '/../konfigurasi/koneksi_database.php';

wajib_peran('pasien');

// ================= PENGAMBILAN TIKET PASIEN =================

$id_antrean = isset($_GET['id_antrean']) && ctype_digit((string) $_GET['id_antrean'])
    ? (int) $_GET['id_antrean']
    : 0;

if ($id_antrean < 1) {
    simpan_notifikasi('danger', 'Tiket antrean tidak valid.');
    alihkan('pasien/ambil-antrean.php');
}

$ambil_antrean = $koneksi_database->prepare(
    'SELECT a.nomor_antrean, a.tanggal_antrean, a.status_antrean, a.metode_pembayaran, a.keluhan,
            p.nama_pasien, po.kode_poli, po.nama_poli
     FROM antrean a
     INNER JOIN pasien p ON p.id_pasien = a.id_pasien
     INNER JOIN poli po ON po.id_poli = a.id_poli
     WHERE a.id_antrean = :id_antrean AND p.id_pengguna = :id_pengguna
     LIMIT 1'
);
$ambil_antrean->execute([
    'id_antrean' => $id_antrean,
    'id_pengguna' => (int) $_SESSION['pengguna']['id_pengguna'],
]);
$antrean = $ambil_antrean->fetch();

if ($antrean === false) {
    simpan_notifikasi('danger', 'Tiket antrean tidak ditemukan.');
    alihkan('pasien/ambil-antrean.php');
}

$label_status = [
    'menunggu' => 'Menunggu',
    'dipanggil' => 'Dipanggil',
    'selesai' => 'Selesai',
    'dibatalkan' => 'Dibatalkan',
];
$kelas_status = [
    'menunggu' => 'warning',
    'dipanggil' => 'primary',
    'selesai' => 'success',
    'dibatalkan' => 'secondary',
];
$label_pembayaran = [
    'umum' => 'Umum',
    'bpjs' => 'BPJS',
    'asuransi_lain' => 'Asuransi Lain',
];

$judul_halaman = 'Hasil Antrean';
$stylesheet_tambahan = 'assets/css/gaya-dashboard.css';
$menu_aktif = 'ambil-antrean';
require_once __DIR__ . '/../includes/kepala.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<div class="d-flex dashboard-layout halaman-dashboard">
    <?php require_once __DIR__ . '/../includes/sidebar_pasien.php'; ?>
    <main class="flex-grow-1 p-3 p-md-4">
        <div class="container-fluid px-0" style="max-width: 760px;">
            <?php require_once __DIR__ . '/../includes/notifikasi.php'; ?>
            <p class="teks-label mb-1">Layanan Pasien</p>
            <h1 class="h3 text-success mb-4">Antrean Berhasil Dibuat</h1>

            <section class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-body p-4 p-md-5">
                    <p class="text-secondary mb-1">Nomor Antrean</p>
                    <p class="display-5 fw-bold text-success mb-4">
                        <?= aman_html($antrean['kode_poli'] . '-' . str_pad((string) $antrean['nomor_antrean'], 3, '0', STR_PAD_LEFT)); ?>
                    </p>
                    <dl class="row mb-0">
                        <dt class="col-sm-4 text-secondary fw-normal">Nama Pasien</dt>
                        <dd class="col-sm-8 fw-semibold"><?= aman_html($antrean['nama_pasien']); ?></dd>
                        <dt class="col-sm-4 text-secondary fw-normal">Nama Poli</dt>
                        <dd class="col-sm-8"><?= aman_html($antrean['nama_poli']); ?></dd>
                        <dt class="col-sm-4 text-secondary fw-normal">Tanggal Antrean</dt>
                        <dd class="col-sm-8"><?= aman_html(date('d/m/Y', strtotime($antrean['tanggal_antrean']))); ?></dd>
                        <dt class="col-sm-4 text-secondary fw-normal">Metode Pembayaran</dt>
                        <dd class="col-sm-8"><?= aman_html($label_pembayaran[$antrean['metode_pembayaran']] ?? '-'); ?></dd>
                        <?php if ($antrean['keluhan'] !== null && $antrean['keluhan'] !== ''): ?>
                            <dt class="col-sm-4 text-secondary fw-normal">Keluhan</dt>
                            <dd class="col-sm-8 text-break"><?= aman_html($antrean['keluhan']); ?></dd>
                        <?php endif; ?>
                        <dt class="col-sm-4 text-secondary fw-normal">Status Antrean</dt>
                        <dd class="col-sm-8"><span class="badge text-bg-<?= aman_html($kelas_status[$antrean['status_antrean']] ?? 'secondary'); ?>"><?= aman_html($label_status[$antrean['status_antrean']] ?? 'Tidak diketahui'); ?></span></dd>
                    </dl>
                </div>
                <div class="card-footer bg-white border-0 px-4 px-md-5 pb-4 pb-md-5">
                    <a class="btn btn-success" href="<?= aman_html(url_aplikasi('pasien/ambil-antrean.php')); ?>">Ambil Antrean Lain</a>
                </div>
            </section>
        </div>
    </main>
</div>
<?php require_once __DIR__ . '/../includes/kaki.php'; ?>
