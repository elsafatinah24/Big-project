<?php
// ================= MODUL DASHBOARD ADMIN =================
// Menampilkan ringkasan statistik, grafik, antrean aktif, dan informasi terbaru sesuai PRD.

declare(strict_types=1);

require_once __DIR__ . '/../includes/middleware_peran.php';
require_once __DIR__ . '/../konfigurasi/koneksi_database.php';

wajib_peran('admin');

// ================= PENGAMBILAN STATISTIK =================
// Semua statistik antrean dihitung berdasarkan tanggal hari ini.
$total_pasien = (int) $koneksi_database->query('SELECT COUNT(*) FROM pasien')->fetchColumn();
$total_antrean_hari_ini = (int) $koneksi_database->query(
    'SELECT COUNT(*) FROM antrean WHERE tanggal_antrean = CURDATE()'
)->fetchColumn();

$ambil_jumlah_status = $koneksi_database->prepare(
    'SELECT COUNT(*) FROM antrean WHERE tanggal_antrean = CURDATE() AND status_antrean = :status_antrean'
);
$jumlah_status = [];
foreach (['menunggu', 'dipanggil', 'selesai'] as $status_antrean) {
    $ambil_jumlah_status->execute(['status_antrean' => $status_antrean]);
    $jumlah_status[$status_antrean] = (int) $ambil_jumlah_status->fetchColumn();
}

// ================= DATA GRAFIK ANTREAN HARIAN =================
// Menyiapkan tujuh hari terakhir; tanggal tanpa antrean tetap bernilai nol.
$hasil_antrean_harian = $koneksi_database->query(
    'SELECT tanggal_antrean, COUNT(*) AS jumlah_antrean
     FROM antrean
     WHERE tanggal_antrean BETWEEN DATE_SUB(CURDATE(), INTERVAL 6 DAY) AND CURDATE()
     GROUP BY tanggal_antrean'
)->fetchAll();

$antrean_per_tanggal = [];
foreach ($hasil_antrean_harian as $baris_antrean) {
    $antrean_per_tanggal[$baris_antrean['tanggal_antrean']] = (int) $baris_antrean['jumlah_antrean'];
}

$label_antrean_harian = [];
$data_antrean_harian = [];
for ($urutan_hari = 6; $urutan_hari >= 0; $urutan_hari--) {
    $tanggal = new DateTimeImmutable("-{$urutan_hari} days");
    $tanggal_database = $tanggal->format('Y-m-d');
    $label_antrean_harian[] = $tanggal->format('d/m');
    $data_antrean_harian[] = $antrean_per_tanggal[$tanggal_database] ?? 0;
}

// ================= DATA GRAFIK PASIEN PER POLI =================
// Menghitung pasien unik yang pernah mengambil antrean pada setiap poli.
$hasil_pasien_per_poli = $koneksi_database->query(
    'SELECT p.nama_poli, COUNT(DISTINCT a.id_pasien) AS jumlah_pasien
     FROM poli AS p
     LEFT JOIN antrean AS a ON a.id_poli = p.id_poli
     GROUP BY p.id_poli, p.nama_poli
     ORDER BY p.nama_poli ASC'
)->fetchAll();

$label_pasien_per_poli = [];
$data_pasien_per_poli = [];
foreach ($hasil_pasien_per_poli as $baris_poli) {
    $label_pasien_per_poli[] = $baris_poli['nama_poli'];
    $data_pasien_per_poli[] = (int) $baris_poli['jumlah_pasien'];
}

if ($label_pasien_per_poli === []) {
    $label_pasien_per_poli = ['Belum ada poli'];
    $data_pasien_per_poli = [0];
}

// ================= DAFTAR ANTREAN BERLANGSUNG =================
// Menampilkan antrean hari ini dengan status menunggu atau dipanggil.
$daftar_antrean_berlangsung = $koneksi_database->query(
    "SELECT a.nomor_antrean, a.status_antrean, p.nama_pasien, po.nama_poli
     FROM antrean AS a
     INNER JOIN pasien AS p ON p.id_pasien = a.id_pasien
     INNER JOIN poli AS po ON po.id_poli = a.id_poli
     WHERE a.tanggal_antrean = CURDATE()
       AND a.status_antrean IN ('menunggu', 'dipanggil')
     ORDER BY CASE WHEN a.status_antrean = 'dipanggil' THEN 0 ELSE 1 END, a.nomor_antrean ASC
     LIMIT 8"
)->fetchAll();

// ================= ANTREAN SEDANG DIPANGGIL =================
// Satu atau lebih antrean dapat dipanggil pada poli yang berbeda.
$antrean_dipanggil = $koneksi_database->query(
    "SELECT a.nomor_antrean, p.nama_pasien, po.nama_poli
     FROM antrean AS a
     INNER JOIN pasien AS p ON p.id_pasien = a.id_pasien
     INNER JOIN poli AS po ON po.id_poli = a.id_poli
     WHERE a.tanggal_antrean = CURDATE() AND a.status_antrean = 'dipanggil'
     ORDER BY a.waktu_dipanggil DESC, a.nomor_antrean ASC"
)->fetchAll();

// ================= BERITA TERBARU =================
// Mengambil tiga berita atau pengumuman terakhir untuk ringkasan dashboard.
$berita_terbaru = $koneksi_database->query(
    'SELECT judul, tanggal_terbit
     FROM berita_pengumuman
     ORDER BY tanggal_terbit DESC
     LIMIT 3'
)->fetchAll();

$data_grafik = [
    'antreanHarian' => ['label' => $label_antrean_harian, 'data' => $data_antrean_harian],
    'pasienPerPoli' => ['label' => $label_pasien_per_poli, 'data' => $data_pasien_per_poli],
];

$judul_halaman = 'Dashboard Admin';
$stylesheet_tambahan = 'assets/css/gaya-dashboard.css';
$menu_aktif = 'dashboard';
require_once __DIR__ . '/../includes/kepala.php';
require_once __DIR__ . '/../includes/navbar_admin.php';
?>
<div class="d-flex dashboard-layout halaman-dashboard">
    <?php require_once __DIR__ . '/../includes/sidebar_admin.php'; ?>
    <main class="flex-grow-1 p-3 p-md-4 p-xl-5 overflow-hidden">
        <?php require_once __DIR__ . '/../includes/notifikasi.php'; ?>
        <div class="mb-4">
            <p class="teks-label mb-1">Ringkasan Pelayanan</p>
            <h1 class="h3 mb-1">Dashboard Admin</h1>
            <p class="text-secondary mb-0">Pantau kondisi antrean Puskesmas hari ini.</p>
        </div>

        <!-- ================= KARTU STATISTIK ================= -->
        <section class="row g-3 g-xl-4 mb-4" aria-label="Ringkasan statistik antrean">
            <div class="col-6 col-xl">
                <article class="card kartu-statistik"><div class="card-body p-3"><div class="d-flex justify-content-between gap-2"><div><p class="label-statistik mb-1">Total Pasien</p><p class="angka-statistik mb-0"><?= $total_pasien; ?></p></div><span class="ikon-statistik">PSN</span></div></div></article>
            </div>
            <div class="col-6 col-xl">
                <article class="card kartu-statistik"><div class="card-body p-3"><div class="d-flex justify-content-between gap-2"><div><p class="label-statistik mb-1">Antrean Hari Ini</p><p class="angka-statistik mb-0"><?= $total_antrean_hari_ini; ?></p></div><span class="ikon-statistik">AHR</span></div></div></article>
            </div>
            <div class="col-6 col-xl">
                <article class="card kartu-statistik"><div class="card-body p-3"><div class="d-flex justify-content-between gap-2"><div><p class="label-statistik mb-1">Menunggu</p><p class="angka-statistik mb-0"><?= $jumlah_status['menunggu']; ?></p></div><span class="ikon-statistik ikon-menunggu">TGG</span></div></div></article>
            </div>
            <div class="col-6 col-xl">
                <article class="card kartu-statistik"><div class="card-body p-3"><div class="d-flex justify-content-between gap-2"><div><p class="label-statistik mb-1">Dipanggil</p><p class="angka-statistik mb-0"><?= $jumlah_status['dipanggil']; ?></p></div><span class="ikon-statistik ikon-dipanggil">PGG</span></div></div></article>
            </div>
            <div class="col-12 col-xl">
                <article class="card kartu-statistik"><div class="card-body p-3"><div class="d-flex justify-content-between gap-2"><div><p class="label-statistik mb-1">Selesai</p><p class="angka-statistik mb-0"><?= $jumlah_status['selesai']; ?></p></div><span class="ikon-statistik ikon-selesai">SLS</span></div></div></article>
            </div>
        </section>

        <!-- ================= GRAFIK DASHBOARD ================= -->
        <section class="row g-4 mb-4" aria-label="Grafik statistik">
            <div class="col-xl-7"><article class="card kartu-dashboard h-100"><div class="card-body p-4"><h2 class="judul-kartu mb-4">Grafik Antrean Harian</h2><div class="wadah-grafik"><canvas id="grafikAntreanHarian"></canvas></div></div></article></div>
            <div class="col-xl-5"><article class="card kartu-dashboard h-100"><div class="card-body p-4"><h2 class="judul-kartu mb-4">Grafik Jumlah Pasien per Poli</h2><div class="wadah-grafik"><canvas id="grafikPasienPerPoli"></canvas></div></div></article></div>
        </section>

        <section class="row g-4">
            <!-- ================= ANTREAN BERLANGSUNG ================= -->
            <div class="col-xl-7"><article class="card kartu-dashboard h-100"><div class="card-body p-4"><h2 class="judul-kartu mb-3">Antrean yang Sedang Berlangsung</h2><div class="daftar-antrean">
                <?php if ($daftar_antrean_berlangsung === []): ?><p class="status-kosong mb-0">Belum ada antrean yang sedang berlangsung hari ini.</p>
                <?php else: foreach ($daftar_antrean_berlangsung as $baris_antrean): ?>
                    <div class="item-antrean d-flex align-items-center justify-content-between gap-3 py-3 border-bottom"><div><span class="fw-bold text-success">#<?= (int) $baris_antrean['nomor_antrean']; ?></span><span class="ms-2 fw-semibold"><?= aman_html($baris_antrean['nama_pasien']); ?></span><small class="d-block text-secondary mt-1"><?= aman_html($baris_antrean['nama_poli']); ?></small></div><span class="badge text-bg-<?= $baris_antrean['status_antrean'] === 'dipanggil' ? 'primary' : 'warning'; ?> rounded-pill"><?= aman_html(ucfirst($baris_antrean['status_antrean'])); ?></span></div>
                <?php endforeach; endif; ?>
            </div></div></article></div>

            <div class="col-xl-5 d-flex flex-column gap-4">
                <!-- ================= ANTREAN DIPANGGIL ================= -->
                <article class="card kartu-dashboard kartu-panggilan"><div class="card-body p-4"><h2 class="judul-kartu mb-3">Nomor Sedang Dipanggil</h2>
                    <?php if ($antrean_dipanggil === []): ?><p class="status-kosong mb-0">Belum ada nomor antrean yang dipanggil.</p>
                    <?php else: foreach ($antrean_dipanggil as $baris_dipanggil): ?><div class="border-bottom pb-2 mb-2"><div class="nomor-dipanggil">#<?= (int) $baris_dipanggil['nomor_antrean']; ?></div><div class="small text-secondary"><?= aman_html($baris_dipanggil['nama_poli']); ?> · <?= aman_html($baris_dipanggil['nama_pasien']); ?></div></div><?php endforeach; endif; ?>
                </div></article>

                <!-- ================= JADWAL PELAYANAN ================= -->
                <article class="card kartu-dashboard"><div class="card-body p-4"><h2 class="judul-kartu mb-2">Jadwal Pelayanan Aktif</h2><p class="status-kosong mb-0">Jadwal pelayanan belum tersedia dalam database.</p></div></article>

                <!-- ================= BERITA TERBARU ================= -->
                <article class="card kartu-dashboard"><div class="card-body p-4"><h2 class="judul-kartu mb-2">Berita/Pengumuman Terbaru</h2><div class="daftar-berita">
                    <?php if ($berita_terbaru === []): ?><p class="status-kosong mb-0">Belum ada berita atau pengumuman.</p>
                    <?php else: foreach ($berita_terbaru as $baris_berita): ?><div class="item-berita py-2 border-bottom"><p class="mb-1 fw-semibold small"><?= aman_html($baris_berita['judul']); ?></p><small class="text-secondary"><?= aman_html(date('d/m/Y H:i', strtotime($baris_berita['tanggal_terbit']))); ?></small></div><?php endforeach; endif; ?>
                </div></div></article>
            </div>
        </section>
    </main>
</div>
<script>
    window.dataDashboard = <?= json_encode($data_grafik, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script src="<?= aman_html(url_aplikasi('assets/js/grafik-dashboard.js')); ?>"></script>
<?php require_once __DIR__ . '/../includes/kaki.php'; ?>
