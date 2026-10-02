<?php
// ================= DASHBOARD DOKTER =================
// Menampilkan ringkasan pelayanan hari ini dari poli yang ditugaskan kepada Dokter login.

declare(strict_types=1);

require_once __DIR__ . '/../includes/middleware_peran.php';
require_once __DIR__ . '/../konfigurasi/koneksi_database.php';

wajib_peran('dokter');

// ================= IDENTITAS DAN PENUGASAN DOKTER =================

$id_dokter = (int) $_SESSION['pengguna']['id_pengguna'];
$tanggal_hari_ini = date('Y-m-d');

$ambil_poli_dokter = $koneksi_database->prepare(
    'SELECT poli.kode_poli, poli.nama_poli
     FROM dokter_poli
     INNER JOIN poli ON poli.id_poli = dokter_poli.id_poli
     WHERE dokter_poli.id_pengguna = :id_dokter
     ORDER BY poli.nama_poli ASC'
);
$ambil_poli_dokter->execute(['id_dokter' => $id_dokter]);
$daftar_poli_dokter = $ambil_poli_dokter->fetchAll();

// ================= RINGKASAN ANTREAN HARI INI =================

$ringkasan_antrean = [
    'total' => 0,
    'menunggu' => 0,
    'dipanggil' => 0,
    'selesai' => 0,
    'hasil_sudah_diisi' => 0,
    'hasil_belum_diisi' => 0,
];
$daftar_antrean_hari_ini = [];

if ($daftar_poli_dokter !== []) {
    // Semua agregasi dibatasi langsung oleh relasi penugasan Dokter dan tanggal hari ini.
    $ambil_ringkasan = $koneksi_database->prepare(
        "SELECT
            COUNT(CASE WHEN antrean.status_antrean IN ('menunggu', 'dipanggil', 'selesai') THEN 1 END) AS total,
            COUNT(CASE WHEN antrean.status_antrean = 'menunggu' THEN 1 END) AS menunggu,
            COUNT(CASE WHEN antrean.status_antrean = 'dipanggil' THEN 1 END) AS dipanggil,
            COUNT(CASE WHEN antrean.status_antrean = 'selesai' THEN 1 END) AS selesai,
            COUNT(CASE WHEN antrean.status_antrean = 'selesai'
                            AND antrean.hasil_pelayanan IS NOT NULL
                            AND antrean.keterangan_pelayanan IS NOT NULL THEN 1 END) AS hasil_sudah_diisi,
            COUNT(CASE WHEN antrean.status_antrean = 'selesai'
                            AND (antrean.hasil_pelayanan IS NULL OR antrean.keterangan_pelayanan IS NULL) THEN 1 END) AS hasil_belum_diisi
         FROM antrean
         INNER JOIN dokter_poli
             ON dokter_poli.id_poli = antrean.id_poli
             AND dokter_poli.id_pengguna = :id_dokter
         WHERE antrean.tanggal_antrean = :tanggal_antrean"
    );
    $ambil_ringkasan->execute([
        'id_dokter' => $id_dokter,
        'tanggal_antrean' => $tanggal_hari_ini,
    ]);
    $hasil_ringkasan = $ambil_ringkasan->fetch();
    if ($hasil_ringkasan !== false) {
        foreach ($ringkasan_antrean as $nama_ringkasan => $nilai_awal) {
            $ringkasan_antrean[$nama_ringkasan] = (int) ($hasil_ringkasan[$nama_ringkasan] ?? $nilai_awal);
        }
    }

    // Menampilkan lima antrean pelayanan terbaru tanpa aksi perubahan status dari Dashboard.
    $ambil_antrean_hari_ini = $koneksi_database->prepare(
        "SELECT antrean.nomor_antrean, antrean.status_antrean, pasien.nama_pasien,
                poli.kode_poli, poli.nama_poli
         FROM antrean
         INNER JOIN dokter_poli
             ON dokter_poli.id_poli = antrean.id_poli
             AND dokter_poli.id_pengguna = :id_dokter
         INNER JOIN pasien ON pasien.id_pasien = antrean.id_pasien
         INNER JOIN poli ON poli.id_poli = antrean.id_poli
         WHERE antrean.tanggal_antrean = :tanggal_antrean
           AND antrean.status_antrean IN ('menunggu', 'dipanggil', 'selesai')
         ORDER BY CASE WHEN antrean.status_antrean = 'dipanggil' THEN 0
                       WHEN antrean.status_antrean = 'menunggu' THEN 1 ELSE 2 END,
                  antrean.nomor_antrean ASC
         LIMIT 5"
    );
    $ambil_antrean_hari_ini->execute([
        'id_dokter' => $id_dokter,
        'tanggal_antrean' => $tanggal_hari_ini,
    ]);
    $daftar_antrean_hari_ini = $ambil_antrean_hari_ini->fetchAll();
}

$label_status = [
    'menunggu' => 'Menunggu',
    'dipanggil' => 'Sedang Dipanggil',
    'selesai' => 'Selesai',
];
$kelas_status = [
    'menunggu' => 'warning',
    'dipanggil' => 'primary',
    'selesai' => 'success',
];

$judul_halaman = 'Dashboard Dokter';
$stylesheet_tambahan = 'assets/css/gaya-dashboard.css?v=' . filemtime(__DIR__ . '/../assets/css/gaya-dashboard.css');
$menu_aktif = 'dashboard';
require_once __DIR__ . '/../includes/kepala.php';
require_once __DIR__ . '/../includes/navbar_dokter.php';
?>
<div class="d-flex dashboard-layout halaman-dashboard">
    <?php require_once __DIR__ . '/../includes/sidebar_dokter.php'; ?>
    <main class="flex-grow-1 p-3 p-md-4 p-xl-5 overflow-hidden">
        <?php require_once __DIR__ . '/../includes/notifikasi.php'; ?>
        <div class="mb-4">
            <p class="teks-label mb-1">Area Dokter</p>
            <h1 class="h3 mb-1">Selamat datang, <?= aman_html($_SESSION['pengguna']['nama_pengguna']); ?></h1>
            <?php if ($daftar_poli_dokter === []): ?>
                <p class="text-secondary mb-0">Belum ada poli yang ditugaskan kepada Anda.</p>
            <?php else: ?>
                <p class="text-secondary mb-0">Poli: <?= aman_html(implode(', ', array_map(static fn (array $poli): string => $poli['nama_poli'], $daftar_poli_dokter))); ?></p>
            <?php endif; ?>
        </div>

        <section class="row g-3 g-xl-4 mb-4" aria-label="Ringkasan antrean hari ini">
            <div class="col-6 col-xl-3"><article class="card kartu-statistik"><div class="card-body p-3"><div class="d-flex justify-content-between gap-2"><div><p class="label-statistik mb-1">Total Antrean</p><p class="angka-statistik mb-0"><?= $ringkasan_antrean['total']; ?></p></div><span class="ikon-statistik">AHR</span></div></div></article></div>
            <div class="col-6 col-xl-3"><article class="card kartu-statistik"><div class="card-body p-3"><div class="d-flex justify-content-between gap-2"><div><p class="label-statistik mb-1">Menunggu</p><p class="angka-statistik mb-0"><?= $ringkasan_antrean['menunggu']; ?></p></div><span class="ikon-statistik ikon-menunggu">TGG</span></div></div></article></div>
            <div class="col-6 col-xl-3"><article class="card kartu-statistik"><div class="card-body p-3"><div class="d-flex justify-content-between gap-2"><div><p class="label-statistik mb-1">Sedang Dipanggil</p><p class="angka-statistik mb-0"><?= $ringkasan_antrean['dipanggil']; ?></p></div><span class="ikon-statistik ikon-dipanggil">PGG</span></div></div></article></div>
            <div class="col-6 col-xl-3"><article class="card kartu-statistik"><div class="card-body p-3"><div class="d-flex justify-content-between gap-2"><div><p class="label-statistik mb-1">Selesai</p><p class="angka-statistik mb-0"><?= $ringkasan_antrean['selesai']; ?></p></div><span class="ikon-statistik ikon-selesai">SLS</span></div></div></article></div>
        </section>

        <section class="card kartu-dashboard mb-4">
            <div class="card-body p-0">
                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 p-3 p-md-4 border-bottom">
                    <div><h2 class="judul-kartu mb-1">Antrean Hari Ini</h2><p class="small text-secondary mb-0"><?= aman_html(date('d/m/Y', strtotime($tanggal_hari_ini))); ?></p></div>
                    <a class="btn btn-sm btn-outline-success tombol-semua-antrean" href="<?= aman_html(url_aplikasi('dokter/antrean.php')); ?>">Lihat Semua Antrean</a>
                </div>
                <?php if ($daftar_antrean_hari_ini === []): ?>
                    <p class="text-center text-secondary py-5 mb-0">Belum ada antrean hari ini.</p>
                <?php else: ?>
                    <div class="table-responsive"><table class="table table-hover align-middle mb-0">
                        <thead class="table-success"><tr><th class="ps-3">No. Antrean</th><th>Nama Pasien</th><th>Poli</th><th>Status</th><th class="text-end pe-3">Aksi</th></tr></thead>
                        <tbody><?php foreach ($daftar_antrean_hari_ini as $antrean): ?><tr>
                            <td class="ps-3 fw-semibold"><?= aman_html($antrean['kode_poli'] . '-' . str_pad((string) $antrean['nomor_antrean'], 3, '0', STR_PAD_LEFT)); ?></td>
                            <td><?= aman_html($antrean['nama_pasien']); ?></td><td><?= aman_html($antrean['nama_poli']); ?></td>
                            <td><span class="badge text-bg-<?= aman_html($kelas_status[$antrean['status_antrean']] ?? 'secondary'); ?>"><?= aman_html($label_status[$antrean['status_antrean']] ?? 'Tidak diketahui'); ?></span></td>
                            <td class="text-end pe-3"><a class="btn btn-sm btn-outline-success" href="<?= aman_html(url_aplikasi('dokter/antrean.php')); ?>">Lihat Antrean</a></td>
                        </tr><?php endforeach; ?></tbody>
                    </table></div>
                <?php endif; ?>
            </div>
        </section>

        <section class="card kartu-dashboard" style="max-width: 680px;">
            <div class="card-body p-3 p-md-4">
                <h2 class="judul-kartu mb-3">Hasil Pelayanan Hari Ini</h2>
                <div class="row g-3">
                    <div class="col-sm-4"><p class="label-statistik mb-1">Selesai</p><p class="h3 mb-0 text-success"><?= $ringkasan_antrean['selesai']; ?></p></div>
                    <div class="col-sm-4"><p class="label-statistik mb-1">Sudah Diisi</p><p class="h3 mb-0 text-success"><?= $ringkasan_antrean['hasil_sudah_diisi']; ?></p></div>
                    <div class="col-sm-4"><p class="label-statistik mb-1">Belum Diisi</p><p class="h3 mb-0 text-secondary"><?= $ringkasan_antrean['hasil_belum_diisi']; ?></p></div>
                </div>
            </div>
        </section>
    </main>
</div>
<?php require_once __DIR__ . '/../includes/kaki.php'; ?>
