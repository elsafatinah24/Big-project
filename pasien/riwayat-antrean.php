<?php
// ================= HALAMAN RIWAYAT ANTREAN =================
// Menampilkan seluruh riwayat antrean milik pasien dengan pencarian dan pagination.

declare(strict_types=1);

require_once __DIR__ . '/../includes/middleware_peran.php';
require_once __DIR__ . '/../konfigurasi/koneksi_database.php';

wajib_peran('pasien');

// ================= PENCARIAN DAN PAGINATION =================

$cari = trim((string) ($_GET['cari'] ?? ''));
$filter_status = (string) ($_GET['status'] ?? 'semua');
$halaman = isset($_GET['halaman']) && ctype_digit((string) $_GET['halaman'])
    ? max(1, (int) $_GET['halaman'])
    : 1;
$batas_data = 10;
$id_pengguna = (int) $_SESSION['pengguna']['id_pengguna'];

$kondisi = ' WHERE p.id_pengguna = :id_pengguna';
$parameter = ['id_pengguna' => $id_pengguna];
$status_tersedia = ['semua', 'menunggu', 'dipanggil', 'selesai', 'dibatalkan'];

if (!in_array($filter_status, $status_tersedia, true)) {
    $filter_status = 'semua';
}

if ($filter_status !== 'semua') {
    $kondisi .= ' AND a.status_antrean = :status_antrean';
    $parameter['status_antrean'] = $filter_status;
}

if ($cari !== '') {
    $kondisi .= " AND (
        CONCAT(po.kode_poli, '-', LPAD(a.nomor_antrean, 3, '0')) LIKE :nomor_antrean
        OR po.nama_poli LIKE :nama_poli
    )";
    $parameter['nomor_antrean'] = '%' . $cari . '%';
    $parameter['nama_poli'] = '%' . $cari . '%';
}

// ================= TOTAL DAN DATA RIWAYAT =================

$dari = ' FROM antrean a
           INNER JOIN pasien p ON p.id_pasien = a.id_pasien
           INNER JOIN poli po ON po.id_poli = a.id_poli';

$hitung_riwayat = $koneksi_database->prepare('SELECT COUNT(*)' . $dari . $kondisi);
$hitung_riwayat->execute($parameter);
$total_data = (int) $hitung_riwayat->fetchColumn();
$total_halaman = max(1, (int) ceil($total_data / $batas_data));
$halaman = min($halaman, $total_halaman);
$offset = ($halaman - 1) * $batas_data;

$ambil_riwayat = $koneksi_database->prepare(
    'SELECT a.id_antrean, a.nomor_antrean, a.tanggal_antrean, a.status_antrean, a.waktu_dipanggil, a.waktu_selesai,
            a.hasil_pelayanan, a.keterangan_pelayanan,
            po.kode_poli, po.nama_poli'
    . $dari . $kondisi .
    ' ORDER BY a.tanggal_antrean DESC, a.dibuat_pada DESC, a.id_antrean DESC
      LIMIT :batas_data OFFSET :offset'
);

foreach ($parameter as $nama_parameter => $nilai_parameter) {
    $ambil_riwayat->bindValue(':' . $nama_parameter, $nilai_parameter, PDO::PARAM_STR);
}
$ambil_riwayat->bindValue(':batas_data', $batas_data, PDO::PARAM_INT);
$ambil_riwayat->bindValue(':offset', $offset, PDO::PARAM_INT);
$ambil_riwayat->execute();
$daftar_riwayat = $ambil_riwayat->fetchAll();

/** Membentuk URL halaman riwayat sambil mempertahankan pencarian dan filter status. */
function url_riwayat_antrean(int $halaman, string $cari, string $filter_status): string
{
    $parameter_url = ['halaman' => $halaman];

    if ($cari !== '') {
        $parameter_url['cari'] = $cari;
    }
    if ($filter_status !== 'semua') {
        $parameter_url['status'] = $filter_status;
    }

    return url_aplikasi('pasien/riwayat-antrean.php?' . http_build_query($parameter_url));
}

$label_status = [
    'menunggu' => 'Menunggu',
    'dipanggil' => 'Sedang Dipanggil',
    'selesai' => 'Selesai',
    'dibatalkan' => 'Dibatalkan',
];
$kelas_status = [
    'menunggu' => 'warning',
    'dipanggil' => 'primary',
    'selesai' => 'success',
    'dibatalkan' => 'secondary',
];

$judul_halaman = 'Riwayat Antrean';
$stylesheet_tambahan = 'assets/css/gaya-dashboard.css';
require_once __DIR__ . '/../includes/kepala.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<div class="d-flex dashboard-layout halaman-dashboard">
    <?php require_once __DIR__ . '/../includes/sidebar_pasien.php'; ?>
    <main class="flex-grow-1 p-3 p-md-4 p-xl-5 overflow-hidden">
        <div class="mb-4">
            <p class="teks-label mb-1">Layanan Pasien</p>
            <h1 class="h3 mb-1">Riwayat Antrean</h1>
            <p class="text-secondary mb-0">Lihat seluruh riwayat antrean Anda.</p>
        </div>

        <?php require_once __DIR__ . '/../includes/notifikasi.php'; ?>

        <section class="card kartu-dashboard mb-4">
            <div class="card-body p-3 p-md-4">
                <form class="row g-2" method="get">
                    <div class="col-md-5">
                        <label class="visually-hidden" for="cari">Cari riwayat antrean</label>
                        <input class="form-control" id="cari" type="search" name="cari" value="<?= aman_html($cari); ?>" placeholder="Cari nomor antrean atau nama poli">
                    </div>
                    <div class="col-md-3">
                        <label class="visually-hidden" for="status">Filter status antrean</label>
                        <select class="form-select" id="status" name="status">
                            <option value="semua" <?= $filter_status === 'semua' ? 'selected' : ''; ?>>Semua Status</option>
                            <option value="menunggu" <?= $filter_status === 'menunggu' ? 'selected' : ''; ?>>Menunggu</option>
                            <option value="dipanggil" <?= $filter_status === 'dipanggil' ? 'selected' : ''; ?>>Sedang Dipanggil</option>
                            <option value="selesai" <?= $filter_status === 'selesai' ? 'selected' : ''; ?>>Selesai</option>
                            <option value="dibatalkan" <?= $filter_status === 'dibatalkan' ? 'selected' : ''; ?>>Dibatalkan</option>
                        </select>
                    </div>
                    <div class="col-md-auto d-flex gap-2">
                        <button class="btn btn-success" type="submit">Cari</button>
                        <a class="btn btn-outline-secondary" href="<?= aman_html(url_aplikasi('pasien/riwayat-antrean.php')); ?>">Reset</a>
                    </div>
                </form>
            </div>
        </section>

        <section class="card kartu-dashboard">
            <div class="card-body p-0">
                <div class="d-flex justify-content-between align-items-center p-3 p-md-4 border-bottom">
                    <h2 class="judul-kartu mb-0">Daftar Riwayat</h2>
                    <span class="small text-secondary"><?= $total_data; ?> data</span>
                </div>
                <?php if ($daftar_riwayat === []): ?>
                    <p class="text-center text-secondary py-5 mb-0">
                        <?= $cari === '' ? 'Belum ada riwayat antrean.' : 'Riwayat antrean tidak ditemukan.'; ?>
                    </p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-success">
                                <tr>
                                    <th class="ps-3">Nomor Antrean</th>
                                    <th>Nama Poli</th>
                                    <th>Tanggal Antrean</th>
                                    <th>Status</th>
                                    <th>Waktu Dipanggil</th>
                                    <th>Waktu Selesai</th>
                                    <th>Hasil Pelayanan</th>
                                    <th class="pe-3">Keterangan Pelayanan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($daftar_riwayat as $antrean): ?>
                                    <?php
                                    $hasil_tersedia = $antrean['hasil_pelayanan'] !== null && $antrean['hasil_pelayanan'] !== '';
                                    $keterangan_tersedia = trim((string) $antrean['keterangan_pelayanan']) !== '';
                                    ?>
                                    <tr>
                                        <td class="ps-3 fw-semibold"><?= aman_html($antrean['kode_poli'] . '-' . str_pad((string) $antrean['nomor_antrean'], 3, '0', STR_PAD_LEFT)); ?></td>
                                        <td><?= aman_html($antrean['nama_poli']); ?></td>
                                        <td><?= aman_html(date('d/m/Y', strtotime($antrean['tanggal_antrean']))); ?></td>
                                        <td><span class="badge text-bg-<?= aman_html($kelas_status[$antrean['status_antrean']] ?? 'secondary'); ?>"><?= aman_html($label_status[$antrean['status_antrean']] ?? 'Tidak diketahui'); ?></span></td>
                                        <td><?= $antrean['waktu_dipanggil'] !== null ? aman_html(date('d/m/Y H:i', strtotime($antrean['waktu_dipanggil']))) : '-'; ?></td>
                                        <td><?= $antrean['waktu_selesai'] !== null ? aman_html(date('d/m/Y H:i', strtotime($antrean['waktu_selesai']))) : '-'; ?></td>
                                        <?php if ($antrean['status_antrean'] !== 'selesai'): ?>
                                            <td><span class="text-secondary small">Pelayanan belum selesai</span></td>
                                            <td class="pe-3 text-secondary small">Belum ada keterangan dari dokter.</td>
                                        <?php elseif (!$hasil_tersedia): ?>
                                            <td><span class="text-secondary small">Hasil pelayanan belum tersedia.</span></td>
                                            <td class="pe-3 text-secondary small"><?= $keterangan_tersedia ? aman_html($antrean['keterangan_pelayanan']) : 'Belum ada keterangan dari dokter.'; ?></td>
                                        <?php else: ?>
                                            <td><?= aman_html($antrean['hasil_pelayanan'] === 'pengobatan' ? 'Pengobatan' : 'Rujuk'); ?></td>
                                            <?php if ($keterangan_tersedia): ?>
                                                <td class="pe-3 text-break"><?= aman_html($antrean['keterangan_pelayanan']); ?></td>
                                            <?php else: ?>
                                                <td class="pe-3 text-secondary small">Belum ada keterangan dari dokter.</td>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

                <?php if ($total_halaman > 1): ?>
                    <nav class="p-3 p-md-4" aria-label="Navigasi halaman riwayat antrean">
                        <ul class="pagination justify-content-end mb-0">
                            <li class="page-item <?= $halaman <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?= aman_html(url_riwayat_antrean(max(1, $halaman - 1), $cari, $filter_status)); ?>">Sebelumnya</a>
                            </li>
                            <?php for ($nomor_halaman = 1; $nomor_halaman <= $total_halaman; $nomor_halaman++): ?>
                                <li class="page-item <?= $nomor_halaman === $halaman ? 'active' : ''; ?>">
                                    <a class="page-link" href="<?= aman_html(url_riwayat_antrean($nomor_halaman, $cari, $filter_status)); ?>"><?= $nomor_halaman; ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= $halaman >= $total_halaman ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?= aman_html(url_riwayat_antrean(min($total_halaman, $halaman + 1), $cari, $filter_status)); ?>">Berikutnya</a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </section>
    </main>
</div>
<?php require_once __DIR__ . '/../includes/kaki.php'; ?>
