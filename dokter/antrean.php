<?php
// ================= MODUL ANTREAN DOKTER =================
// Menampilkan antrean aktif hanya dari poli yang ditugaskan kepada Dokter login.

declare(strict_types=1);

require_once __DIR__ . '/../includes/middleware_peran.php';
require_once __DIR__ . '/../konfigurasi/koneksi_database.php';

wajib_peran('dokter');

// ================= VALIDASI PENCARIAN DAN FILTER =================

$id_dokter = (int) $_SESSION['pengguna']['id_pengguna'];
$cari = trim((string) ($_GET['cari'] ?? ''));
$filter_status = (string) ($_GET['status'] ?? 'semua');
$filter_poli = isset($_GET['id_poli']) && ctype_digit((string) $_GET['id_poli'])
    ? (int) $_GET['id_poli']
    : 0;
$halaman_hasil = isset($_GET['hasil_page']) && ctype_digit((string) $_GET['hasil_page'])
    ? max(1, (int) $_GET['hasil_page'])
    : 1;
$batas_hasil = 10;
$tanggal_hari_ini = date('Y-m-d');

if (strlen($cari) > 100) {
    $cari = '';
}
if (!in_array($filter_status, ['semua', 'menunggu', 'dipanggil', 'selesai', 'dibatalkan'], true)) {
    $filter_status = 'semua';
}

// Mengambil pilihan poli yang terbatas pada penugasan Dokter yang sedang login.
$ambil_poli_dokter = $koneksi_database->prepare(
    'SELECT poli.id_poli, poli.kode_poli, poli.nama_poli
     FROM dokter_poli
     INNER JOIN poli ON poli.id_poli = dokter_poli.id_poli
     WHERE dokter_poli.id_pengguna = :id_dokter
     ORDER BY poli.nama_poli ASC'
);
$ambil_poli_dokter->execute(['id_dokter' => $id_dokter]);
$daftar_poli_dokter = $ambil_poli_dokter->fetchAll();
$id_poli_dokter = array_map(static fn (array $poli): int => (int) $poli['id_poli'], $daftar_poli_dokter);

if ($filter_poli > 0 && !in_array($filter_poli, $id_poli_dokter, true)) {
    $filter_poli = 0;
}

// ================= PENGAMBILAN ANTREAN HARI INI =================

$daftar_antrean = [];
$daftar_antrean_selesai = [];
$total_antrean_selesai = 0;
$total_halaman_hasil = 1;
$offset_hasil = 0;
$nomor_awal_hasil = 0;
$nomor_akhir_hasil = 0;
if ($daftar_poli_dokter !== []) {
    // Daftar utama hanya memuat antrean yang masih berada dalam proses pelayanan.
    // Filter status menjadi penyaring tambahan dan tidak dapat memasukkan antrean selesai/dibatalkan kembali.
    $kondisi_status = $filter_status === 'semua' ? '' : ' AND a.status_antrean = :status_antrean';
    $kondisi_poli = $filter_poli > 0 ? ' AND a.id_poli = :id_poli' : '';
    $kondisi_cari = $cari === ''
        ? ''
        : " AND (pasien.nama_pasien LIKE :cari_nama
                 OR CONCAT(poli.kode_poli, '-', LPAD(a.nomor_antrean, 3, '0')) LIKE :cari_nomor)";

    // Relasi dokter_poli, tanggal hari ini, pencarian, dan status seluruhnya dibatasi langsung pada query.
    $ambil_antrean = $koneksi_database->prepare(
        'SELECT a.id_antrean, a.nomor_antrean, a.tanggal_antrean, a.status_antrean, a.keluhan,
                pasien.nama_pasien, poli.kode_poli, poli.nama_poli
         FROM antrean a
         INNER JOIN dokter_poli ON dokter_poli.id_poli = a.id_poli
             AND dokter_poli.id_pengguna = :id_dokter
         INNER JOIN pasien ON pasien.id_pasien = a.id_pasien
         INNER JOIN poli ON poli.id_poli = a.id_poli
         WHERE a.tanggal_antrean = :tanggal_antrean
           AND a.status_antrean IN (\'menunggu\', \'dipanggil\')
           ' . $kondisi_status . $kondisi_poli . $kondisi_cari . '
         ORDER BY poli.nama_poli ASC, a.nomor_antrean ASC'
    );
    $parameter_antrean = [
        'id_dokter' => $id_dokter,
        'tanggal_antrean' => $tanggal_hari_ini,
    ];
    if ($filter_status !== 'semua') {
        $parameter_antrean['status_antrean'] = $filter_status;
    }
    if ($filter_poli > 0) {
        $parameter_antrean['id_poli'] = $filter_poli;
    }
    if ($cari !== '') {
        $parameter_antrean['cari_nama'] = '%' . $cari . '%';
        $parameter_antrean['cari_nomor'] = '%' . $cari . '%';
    }
    $ambil_antrean->execute($parameter_antrean);
    $daftar_antrean = $ambil_antrean->fetchAll();

    // Antrean selesai hari ini diprioritaskan berdasarkan hasil yang belum diisi dan dibatasi sepuluh data per halaman.
    $dari_antrean_selesai = ' FROM antrean a
        INNER JOIN dokter_poli ON dokter_poli.id_poli = a.id_poli
            AND dokter_poli.id_pengguna = :id_dokter
        INNER JOIN pasien ON pasien.id_pasien = a.id_pasien
        INNER JOIN poli ON poli.id_poli = a.id_poli
        WHERE a.tanggal_antrean = :tanggal_antrean
          AND a.status_antrean = \'selesai\'' . $kondisi_poli;
    $parameter_antrean_selesai = [
        'id_dokter' => $id_dokter,
        'tanggal_antrean' => $tanggal_hari_ini,
    ];
    if ($filter_poli > 0) {
        $parameter_antrean_selesai['id_poli'] = $filter_poli;
    }

    $hitung_antrean_selesai = $koneksi_database->prepare('SELECT COUNT(*)' . $dari_antrean_selesai);
    $hitung_antrean_selesai->execute($parameter_antrean_selesai);
    $total_antrean_selesai = (int) $hitung_antrean_selesai->fetchColumn();
    $total_halaman_hasil = max(1, (int) ceil($total_antrean_selesai / $batas_hasil));
    $halaman_hasil = min($halaman_hasil, $total_halaman_hasil);
    $offset_hasil = ($halaman_hasil - 1) * $batas_hasil;
    if ($total_antrean_selesai > 0) {
        $nomor_awal_hasil = $offset_hasil + 1;
        $nomor_akhir_hasil = min($offset_hasil + $batas_hasil, $total_antrean_selesai);
    }

    $ambil_antrean_selesai = $koneksi_database->prepare(
        'SELECT a.id_antrean, a.nomor_antrean, a.tanggal_antrean, a.hasil_pelayanan, a.keterangan_pelayanan,
                pasien.nama_pasien, poli.kode_poli, poli.nama_poli'
        . $dari_antrean_selesai . "
         ORDER BY CASE WHEN a.hasil_pelayanan IS NULL OR a.hasil_pelayanan = '' THEN 0 ELSE 1 END ASC,
                  poli.nama_poli ASC, a.nomor_antrean ASC
         LIMIT :batas_hasil OFFSET :offset_hasil"
    );
    foreach ($parameter_antrean_selesai as $nama_parameter => $nilai_parameter) {
        $ambil_antrean_selesai->bindValue(':' . $nama_parameter, $nilai_parameter);
    }
    $ambil_antrean_selesai->bindValue(':batas_hasil', $batas_hasil, PDO::PARAM_INT);
    $ambil_antrean_selesai->bindValue(':offset_hasil', $offset_hasil, PDO::PARAM_INT);
    $ambil_antrean_selesai->execute();
    $daftar_antrean_selesai = $ambil_antrean_selesai->fetchAll();
}

/** Membentuk URL pagination hasil pelayanan sambil mempertahankan pencarian dan filter antrean. */
function url_halaman_hasil(int $halaman_hasil, string $cari, string $filter_status, int $filter_poli): string
{
    $parameter_url = ['hasil_page' => $halaman_hasil];
    if ($cari !== '') {
        $parameter_url['cari'] = $cari;
    }
    if ($filter_status !== 'semua') {
        $parameter_url['status'] = $filter_status;
    }
    if ($filter_poli > 0) {
        $parameter_url['id_poli'] = $filter_poli;
    }

    return url_aplikasi('dokter/antrean.php?' . http_build_query($parameter_url));
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

$judul_halaman = 'Antrean Dokter';
$stylesheet_tambahan = 'assets/css/gaya-dashboard.css';
$menu_aktif = 'antrean';
require_once __DIR__ . '/../includes/kepala.php';
require_once __DIR__ . '/../includes/navbar_dokter.php';
?>
<div class="d-flex dashboard-layout halaman-dashboard">
    <?php require_once __DIR__ . '/../includes/sidebar_dokter.php'; ?>
    <main class="flex-grow-1 p-3 p-md-4 p-xl-5 overflow-hidden">
        <div class="mb-4">
            <p class="teks-label mb-1">Antrean Pelayanan</p>
            <h1 class="h3 mb-1">Daftar Antrean Hari Ini</h1>
            <p class="text-secondary mb-0">Cari antrean dari poli yang menjadi penugasan Anda.</p>
        </div>
        <?php require_once __DIR__ . '/../includes/notifikasi.php'; ?>

        <?php if ($daftar_poli_dokter === []): ?>
            <section class="card kartu-dashboard" style="max-width: 680px;"><div class="card-body p-4 p-md-5">
                <h2 class="judul-kartu mb-2">Belum Ada Penugasan Poli</h2>
                <p class="text-secondary mb-0">Anda belum ditugaskan ke poli.</p>
            </div></section>
        <?php else: ?>
            <section class="card kartu-dashboard mb-4">
                <div class="card-body p-3 p-md-4">
                    <form class="row g-3 align-items-end" method="get">
                        <div class="col-md-5">
                            <label class="form-label" for="cari">Cari Antrean</label>
                            <input class="form-control" id="cari" name="cari" type="search" value="<?= aman_html($cari); ?>" maxlength="100" placeholder="Cari nama pasien atau nomor antrean...">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="status">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="semua" <?= $filter_status === 'semua' ? 'selected' : ''; ?>>Semua</option>
                                <option value="menunggu" <?= $filter_status === 'menunggu' ? 'selected' : ''; ?>>Menunggu</option>
                                <option value="dipanggil" <?= $filter_status === 'dipanggil' ? 'selected' : ''; ?>>Dipanggil</option>
                                <option value="selesai" <?= $filter_status === 'selesai' ? 'selected' : ''; ?>>Selesai</option>
                                <option value="dibatalkan" <?= $filter_status === 'dibatalkan' ? 'selected' : ''; ?>>Dibatalkan</option>
                            </select>
                        </div>
                        <?php if (count($daftar_poli_dokter) > 1): ?>
                            <div class="col-md-4">
                                <label class="form-label" for="id_poli">Poli</label>
                                <select class="form-select" id="id_poli" name="id_poli">
                                    <option value="0">Semua Poli Saya</option>
                                    <?php foreach ($daftar_poli_dokter as $poli): ?>
                                        <option value="<?= (int) $poli['id_poli']; ?>" <?= $filter_poli === (int) $poli['id_poli'] ? 'selected' : ''; ?>><?= aman_html($poli['kode_poli'] . ' - ' . $poli['nama_poli']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php else: ?>
                            <div class="col-md-4"><p class="form-label mb-1">Poli</p><p class="form-control-plaintext mb-0"><?= aman_html($daftar_poli_dokter[0]['kode_poli'] . ' - ' . $daftar_poli_dokter[0]['nama_poli']); ?></p></div>
                        <?php endif; ?>
                        <div class="col-12 d-flex gap-2"><button class="btn btn-success" type="submit">Cari</button><a class="btn btn-outline-secondary" href="<?= aman_html(url_aplikasi('dokter/antrean.php')); ?>">Reset</a></div>
                    </form>
                </div>
            </section>

            <section class="card kartu-dashboard">
                <div class="card-body p-0">
                    <div class="d-flex justify-content-between align-items-center p-3 p-md-4 border-bottom">
                        <h2 class="judul-kartu mb-0">Antrean Hari Ini</h2>
                        <span class="small text-secondary"><?= count($daftar_antrean); ?> antrean</span>
                    </div>
                    <?php if ($daftar_antrean === []): ?>
                        <div class="text-center text-secondary py-5">Data antrean tidak ditemukan.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-success"><tr><th class="ps-3">No. Antrean</th><th>Nama Pasien</th><th>Poli</th><th>Tanggal</th><th>Status</th><th>Keluhan</th><th class="text-end pe-3">Aksi</th></tr></thead>
                                <tbody>
                                    <?php foreach ($daftar_antrean as $antrean): ?>
                                        <?php $keluhan_tersedia = trim((string) $antrean['keluhan']) !== ''; ?>
                                        <tr>
                                            <td class="ps-3 fw-semibold"><?= aman_html($antrean['kode_poli'] . '-' . str_pad((string) $antrean['nomor_antrean'], 3, '0', STR_PAD_LEFT)); ?></td>
                                            <td><?= aman_html($antrean['nama_pasien']); ?></td>
                                            <td><?= aman_html($antrean['nama_poli']); ?></td>
                                            <td><?= aman_html(date('d/m/Y', strtotime($antrean['tanggal_antrean']))); ?></td>
                                            <td><span class="badge text-bg-<?= aman_html($kelas_status[$antrean['status_antrean']] ?? 'secondary'); ?>"><?= aman_html($label_status[$antrean['status_antrean']] ?? 'Tidak diketahui'); ?></span></td>
                                            <td><button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="modal" data-bs-target="#modalKeluhan<?= (int) $antrean['id_antrean']; ?>"><?= $keluhan_tersedia ? 'Lihat Keluhan' : 'Tidak Ada Keluhan'; ?></button></td>
                                            <td class="text-end pe-3">
                                                <?php if ($antrean['status_antrean'] === 'menunggu'): ?>
                                                    <form method="post" action="<?= aman_html(url_aplikasi('proses/proses_panggil_antrean_dokter.php')); ?>" class="d-inline">
                                                        <input type="hidden" name="token_csrf" value="<?= aman_html(token_csrf()); ?>">
                                                        <input type="hidden" name="id_antrean" value="<?= (int) $antrean['id_antrean']; ?>">
                                                        <button class="btn btn-sm btn-success" type="submit">Panggil</button>
                                                    </form>
                                                <?php elseif ($antrean['status_antrean'] === 'dipanggil'): ?>
                                                    <form method="post" action="<?= aman_html(url_aplikasi('proses/proses_selesai_antrean_dokter.php')); ?>" class="d-inline">
                                                        <input type="hidden" name="token_csrf" value="<?= aman_html(token_csrf()); ?>">
                                                        <input type="hidden" name="id_antrean" value="<?= (int) $antrean['id_antrean']; ?>">
                                                        <button class="btn btn-sm btn-outline-success" type="submit">Selesai</button>
                                                    </form>
                                                <?php elseif ($antrean['status_antrean'] === 'selesai'): ?>
                                                    <a class="btn btn-sm btn-outline-success" href="<?= aman_html(url_aplikasi('dokter/formulir-hasil-pelayanan.php?id_antrean=' . (int) $antrean['id_antrean'])); ?>">Hasil Pelayanan</a>
                                                <?php else: ?>
                                                    <span class="small text-secondary">Tidak dapat dipanggil</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <!-- ================= MODAL KELUHAN PASIEN ================= -->
            <?php foreach ($daftar_antrean as $antrean): ?>
                <?php $keluhan_tersedia = trim((string) $antrean['keluhan']) !== ''; ?>
                <div class="modal fade" id="modalKeluhan<?= (int) $antrean['id_antrean']; ?>" tabindex="-1" aria-labelledby="judulKeluhan<?= (int) $antrean['id_antrean']; ?>" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered"><div class="modal-content border-0">
                        <div class="modal-header bg-success text-white"><h2 class="fs-5 modal-title" id="judulKeluhan<?= (int) $antrean['id_antrean']; ?>">Keluhan Pasien</h2><button class="btn-close btn-close-white" type="button" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
                        <div class="modal-body">
                            <dl class="row mb-3"><dt class="col-sm-5">Nama Pasien</dt><dd class="col-sm-7"><?= aman_html($antrean['nama_pasien']); ?></dd><dt class="col-sm-5">Nomor Antrean</dt><dd class="col-sm-7"><?= aman_html($antrean['kode_poli'] . '-' . str_pad((string) $antrean['nomor_antrean'], 3, '0', STR_PAD_LEFT)); ?></dd></dl>
                            <p class="fw-semibold mb-1">Keluhan</p>
                            <p class="mb-0 text-break"><?= $keluhan_tersedia ? nl2br(aman_html($antrean['keluhan'])) : 'Pasien tidak memberikan keluhan.'; ?></p>
                        </div>
                        <div class="modal-footer"><button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Tutup</button></div>
                    </div></div>
                </div>
            <?php endforeach; ?>

            <section class="card kartu-dashboard mt-4">
                <div class="card-body p-0">
                    <div class="d-flex justify-content-between align-items-center p-3 p-md-4 border-bottom">
                        <div><h2 class="judul-kartu mb-1">Hasil Pelayanan Antrean Selesai</h2><p class="small text-secondary mb-0">Menampilkan antrean selesai hari ini.</p></div>
                        <span class="small text-secondary"><?= $total_antrean_selesai; ?> antrean</span>
                    </div>
                    <?php if ($daftar_antrean_selesai === []): ?>
                        <p class="text-center text-secondary py-5 mb-0">Belum ada antrean selesai hari ini.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-success"><tr><th class="ps-3">No. Antrean</th><th>Nama Pasien</th><th>Poli</th><th>Tanggal</th><th>Status Hasil</th><th class="text-end pe-3">Aksi</th></tr></thead>
                                <tbody>
                                    <?php foreach ($daftar_antrean_selesai as $antrean_selesai): ?>
                                        <?php $hasil_sudah_diisi = $antrean_selesai['hasil_pelayanan'] !== null && $antrean_selesai['hasil_pelayanan'] !== ''; ?>
                                        <tr>
                                            <td class="ps-3 fw-semibold"><?= aman_html($antrean_selesai['kode_poli'] . '-' . str_pad((string) $antrean_selesai['nomor_antrean'], 3, '0', STR_PAD_LEFT)); ?></td>
                                            <td><?= aman_html($antrean_selesai['nama_pasien']); ?></td>
                                            <td><?= aman_html($antrean_selesai['nama_poli']); ?></td>
                                            <td><?= aman_html(date('d/m/Y', strtotime($antrean_selesai['tanggal_antrean']))); ?></td>
                                            <td><span class="badge text-bg-<?= $hasil_sudah_diisi ? 'success' : 'secondary'; ?>"><?= $hasil_sudah_diisi ? 'Sudah Diisi' : 'Belum Diisi'; ?></span></td>
                                            <td class="text-end pe-3"><a class="btn btn-sm btn-outline-success" href="<?= aman_html(url_aplikasi('dokter/formulir-hasil-pelayanan.php?id_antrean=' . (int) $antrean_selesai['id_antrean'])); ?>"><?= $hasil_sudah_diisi ? 'Lihat Hasil' : 'Hasil Pelayanan'; ?></a></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 p-3 p-md-4 border-top">
                            <p class="small text-secondary mb-0">Menampilkan <?= $nomor_awal_hasil; ?>–<?= $nomor_akhir_hasil; ?> dari <?= $total_antrean_selesai; ?> data</p>
                            <?php if ($total_halaman_hasil > 1): ?>
                                <nav aria-label="Navigasi halaman hasil pelayanan">
                                    <ul class="pagination justify-content-md-end mb-0">
                                        <li class="page-item <?= $halaman_hasil <= 1 ? 'disabled' : ''; ?>"><a class="page-link" href="<?= aman_html(url_halaman_hasil(max(1, $halaman_hasil - 1), $cari, $filter_status, $filter_poli)); ?>">Previous</a></li>
                                        <?php for ($nomor_halaman = 1; $nomor_halaman <= $total_halaman_hasil; $nomor_halaman++): ?>
                                            <li class="page-item <?= $nomor_halaman === $halaman_hasil ? 'active' : ''; ?>"><a class="page-link" href="<?= aman_html(url_halaman_hasil($nomor_halaman, $cari, $filter_status, $filter_poli)); ?>"><?= $nomor_halaman; ?></a></li>
                                        <?php endfor; ?>
                                        <li class="page-item <?= $halaman_hasil >= $total_halaman_hasil ? 'disabled' : ''; ?>"><a class="page-link" href="<?= aman_html(url_halaman_hasil(min($total_halaman_hasil, $halaman_hasil + 1), $cari, $filter_status, $filter_poli)); ?>">Next</a></li>
                                    </ul>
                                </nav>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        <?php endif; ?>
    </main>
</div>
<?php require_once __DIR__ . '/../includes/kaki.php'; ?>
