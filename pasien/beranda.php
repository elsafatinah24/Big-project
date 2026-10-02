<?php
// ================= DASHBOARD PASIEN =================
// Menampilkan ringkasan antrean aktif hari ini untuk pasien yang sedang login.

declare(strict_types=1);

require_once __DIR__ . '/../includes/middleware_peran.php';
require_once __DIR__ . '/../konfigurasi/koneksi_database.php';

wajib_peran('pasien');

$id_pengguna = (int) $_SESSION['pengguna']['id_pengguna'];
$tanggal_hari_ini = date('Y-m-d');

// ================= DATA PASIEN =================

$ambil_pasien = $koneksi_database->prepare(
    'SELECT nama_pasien FROM pasien WHERE id_pengguna = :id_pengguna LIMIT 1'
);
$ambil_pasien->execute(['id_pengguna' => $id_pengguna]);
$pasien = $ambil_pasien->fetch();

$nama_pasien = $pasien !== false
    ? $pasien['nama_pasien']
    : (string) $_SESSION['pengguna']['nama_pengguna'];

// ================= ANTREAN AKTIF HARI INI =================

$ambil_antrean_aktif = $koneksi_database->prepare(
    "SELECT a.id_antrean, a.nomor_antrean, a.tanggal_antrean, a.status_antrean, po.kode_poli, po.nama_poli
     FROM antrean a
     INNER JOIN pasien p ON p.id_pasien = a.id_pasien
     INNER JOIN poli po ON po.id_poli = a.id_poli
     WHERE p.id_pengguna = :id_pengguna
       AND a.tanggal_antrean = :tanggal_antrean
       AND a.status_antrean IN ('menunggu', 'dipanggil')
     ORDER BY a.nomor_antrean ASC"
);
$ambil_antrean_aktif->execute([
    'id_pengguna' => $id_pengguna,
    'tanggal_antrean' => $tanggal_hari_ini,
]);
$daftar_antrean_aktif = $ambil_antrean_aktif->fetchAll();

$label_status = [
    'menunggu' => 'Menunggu',
    'dipanggil' => 'Dipanggil',
];
$kelas_status = [
    'menunggu' => 'warning',
    'dipanggil' => 'primary',
];

$judul_halaman = 'Dashboard Pasien';
$stylesheet_tambahan = 'assets/css/gaya-dashboard.css';
require_once __DIR__ . '/../includes/kepala.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<div class="d-flex dashboard-layout halaman-dashboard">
    <?php require_once __DIR__ . '/../includes/sidebar_pasien.php'; ?>
    <main class="flex-grow-1 p-3 p-md-4 p-xl-5">
        <div class="mb-4">
            <p class="teks-label mb-1">Dashboard Pasien</p>
            <h1 class="h3 mb-1">Halo, <?= aman_html($nama_pasien); ?></h1>
            <p class="text-secondary mb-0">Berikut ringkasan antrean Anda untuk hari ini.</p>
        </div>

        <?php require_once __DIR__ . '/../includes/notifikasi.php'; ?>

        <?php if ($daftar_antrean_aktif === []): ?>
            <section class="card kartu-dashboard" style="max-width: 680px;">
                <div class="card-body p-4 p-md-5">
                    <h2 class="judul-kartu mb-2">Belum Ada Antrean Aktif</h2>
                    <p class="text-secondary mb-4">Anda belum memiliki antrean aktif untuk hari ini.</p>
                    <a class="btn btn-success" href="<?= aman_html(url_aplikasi('pasien/ambil-antrean.php')); ?>">Ambil Antrean</a>
                </div>
            </section>
        <?php else: ?>
            <section aria-labelledby="judulAntreanAktif">
                <h2 class="judul-kartu mb-3" id="judulAntreanAktif">Antrean Aktif Anda</h2>
                <div class="row g-4">
                    <?php foreach ($daftar_antrean_aktif as $antrean): ?>
                        <div class="col-md-6 col-xl-4">
                            <article class="card kartu-dashboard h-100">
                                <div class="card-body p-4 d-flex flex-column">
                                    <p class="teks-label mb-2"><?= aman_html($antrean['nama_poli']); ?></p>
                                    <p class="nomor-dipanggil mb-3">
                                        <?= aman_html($antrean['kode_poli'] . '-' . str_pad((string) $antrean['nomor_antrean'], 3, '0', STR_PAD_LEFT)); ?>
                                    </p>
                                    <dl class="row small mb-4">
                                        <dt class="col-5 text-secondary fw-normal">Poli</dt>
                                        <dd class="col-7 mb-2"><?= aman_html($antrean['nama_poli']); ?></dd>
                                        <dt class="col-5 text-secondary fw-normal">Tanggal</dt>
                                        <dd class="col-7 mb-2"><?= aman_html(date('d/m/Y', strtotime($antrean['tanggal_antrean']))); ?></dd>
                                        <dt class="col-5 text-secondary fw-normal">Status</dt>
                                        <dd class="col-7 mb-0"><span class="badge text-bg-<?= aman_html($kelas_status[$antrean['status_antrean']] ?? 'secondary'); ?>"><?= aman_html($label_status[$antrean['status_antrean']] ?? 'Tidak diketahui'); ?></span></dd>
                                    </dl>
                                    <a class="btn btn-outline-success mt-auto" href="<?= aman_html(url_aplikasi('pasien/hasil-antrean.php?id_antrean=' . (int) $antrean['id_antrean'])); ?>">Lihat Status Antrean</a>
                                </div>
                            </article>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    </main>
</div>
<?php require_once __DIR__ . '/../includes/kaki.php'; ?>
