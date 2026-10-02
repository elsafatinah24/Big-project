<?php
// ================= HALAMAN ANTREAN AKTIF =================
// Menampilkan daftar antrean aktif hari ini dengan nama pasien dan tanpa data sensitif.

declare(strict_types=1);

require_once __DIR__ . '/konfigurasi/koneksi_database.php';

/** Membentuk URL pagination sambil mempertahankan filter poli yang valid. */
function url_antrean_aktif(string $id_poli, int $halaman): string
{
    $parameter = ['page' => $halaman];
    if ($id_poli !== '') {
        $parameter['id_poli'] = $id_poli;
    }

    return url_aplikasi('antrean-aktif.php?' . http_build_query($parameter));
}

// ================= FILTER POLI =================

$id_poli_filter = trim((string) ($_GET['id_poli'] ?? ''));
$halaman = isset($_GET['page']) && ctype_digit((string) $_GET['page'])
    ? max(1, (int) $_GET['page'])
    : 1;
$batas_data = 10;
$pesan_filter = null;

$ambil_poli = $koneksi_database->prepare(
    'SELECT id_poli, kode_poli, nama_poli FROM poli ORDER BY nama_poli ASC'
);
$ambil_poli->execute();
$daftar_poli = $ambil_poli->fetchAll();
$id_poli_tersedia = array_map(static fn (array $poli): int => (int) $poli['id_poli'], $daftar_poli);

if ($id_poli_filter !== '' && (!ctype_digit($id_poli_filter) || !in_array((int) $id_poli_filter, $id_poli_tersedia, true))) {
    $pesan_filter = 'Pilihan poli tidak valid. Silakan pilih poli yang tersedia.';
    $id_poli_filter = '';
}

// ================= PENGAMBILAN ANTREAN AKTIF HARI INI =================

$kondisi = [
    'a.tanggal_antrean = :tanggal_antrean',
    "a.status_antrean IN ('menunggu', 'dipanggil')",
];
$parameter = ['tanggal_antrean' => date('Y-m-d')];

if ($id_poli_filter !== '') {
    $kondisi[] = 'a.id_poli = :id_poli';
    $parameter['id_poli'] = (int) $id_poli_filter;
}

$dari_antrean_aktif = ' FROM antrean a
     INNER JOIN pasien pa ON pa.id_pasien = a.id_pasien
     INNER JOIN poli po ON po.id_poli = a.id_poli
     WHERE ' . implode(' AND ', $kondisi);

$hitung_antrean_aktif = $koneksi_database->prepare('SELECT COUNT(*)' . $dari_antrean_aktif);
$hitung_antrean_aktif->execute($parameter);
$total_data = (int) $hitung_antrean_aktif->fetchColumn();
$total_halaman = max(1, (int) ceil($total_data / $batas_data));
$halaman = min($halaman, $total_halaman);
$offset = ($halaman - 1) * $batas_data;
$nomor_awal = $total_data > 0 ? $offset + 1 : 0;
$nomor_akhir = $total_data > 0 ? min($offset + $batas_data, $total_data) : 0;

$ambil_antrean_aktif = $koneksi_database->prepare(
    'SELECT a.nomor_antrean, a.status_antrean, pa.nama_pasien, po.kode_poli, po.nama_poli'
    . $dari_antrean_aktif . '
     ORDER BY po.nama_poli ASC, a.nomor_antrean ASC
     LIMIT :batas_data OFFSET :offset'
);

foreach ($parameter as $nama_parameter => $nilai_parameter) {
    $ambil_antrean_aktif->bindValue(
        ':' . $nama_parameter,
        $nilai_parameter,
        is_int($nilai_parameter) ? PDO::PARAM_INT : PDO::PARAM_STR
    );
}
$ambil_antrean_aktif->bindValue(':batas_data', $batas_data, PDO::PARAM_INT);
$ambil_antrean_aktif->bindValue(':offset', $offset, PDO::PARAM_INT);
$ambil_antrean_aktif->execute();
$daftar_antrean_aktif = $ambil_antrean_aktif->fetchAll();

$label_status = [
    'menunggu' => 'Menunggu',
    'dipanggil' => 'Dipanggil',
];
$kelas_status = [
    'menunggu' => 'warning',
    'dipanggil' => 'primary',
];

$judul_halaman = 'Antrean Aktif';
require_once __DIR__ . '/includes/kepala.php';
require_once __DIR__ . '/includes/navbar.php';
?>
<main class="container py-4 py-md-5">
    <div class="row justify-content-center">
        <div class="col-xl-10">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mb-4">
                <div>
                    <p class="teks-label mb-1">Informasi Pelayanan</p>
                    <h1 class="h2 text-success mb-2">Antrean Aktif</h1>
                    <p class="text-secondary mb-0">Antrean dengan status menunggu atau dipanggil untuk hari ini, <?= aman_html(date('d/m/Y')); ?>.</p>
                </div>
            </div>

            <?php if ($pesan_filter !== null): ?>
                <div class="alert alert-warning" role="alert"><?= aman_html($pesan_filter); ?></div>
            <?php endif; ?>

            <section class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-3 p-md-4">
                    <form class="row g-2 align-items-end" method="get">
                        <div class="col-md">
                            <label class="form-label" for="id_poli">Filter Poli</label>
                            <select class="form-select" id="id_poli" name="id_poli">
                                <option value="">Semua Poli</option>
                                <?php foreach ($daftar_poli as $poli): ?>
                                    <option value="<?= (int) $poli['id_poli']; ?>" <?= $id_poli_filter === (string) $poli['id_poli'] ? 'selected' : ''; ?>>
                                        <?= aman_html($poli['kode_poli'] . ' - ' . $poli['nama_poli']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-auto d-flex gap-2">
                            <button class="btn btn-success" type="submit">Tampilkan</button>
                            <a class="btn btn-outline-secondary" href="<?= aman_html(url_antrean_aktif('', 1)); ?>">Reset</a>
                        </div>
                    </form>
                </div>
            </section>

            <?php if ($daftar_antrean_aktif === []): ?>
                <section class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-4 p-md-5 text-center">
                        <h2 class="h5 text-success mb-2">Tidak Ada Antrean Aktif</h2>
                        <p class="text-secondary mb-0">Belum ada antrean menunggu atau dipanggil untuk poli yang dipilih hari ini.</p>
                    </div>
                </section>
            <?php else: ?>
                <section aria-labelledby="judulDaftarAntrean">
                    <h2 class="h5 mb-3" id="judulDaftarAntrean">Daftar Antrean Hari Ini</h2>
                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-success">
                                    <tr>
                                        <th class="ps-3">Nama Pasien</th>
                                        <th>Poli</th>
                                        <th>No. Antrean</th>
                                        <th class="pe-3">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                        <?php foreach ($daftar_antrean_aktif as $antrean): ?>
                                    <tr class="<?= $antrean['status_antrean'] === 'dipanggil' ? 'table-primary' : ''; ?>">
                                        <td class="ps-3 fw-semibold"><?= aman_html($antrean['nama_pasien']); ?></td>
                                        <td><?= aman_html($antrean['nama_poli']); ?></td>
                                        <td class="fw-bold text-success"><?= aman_html($antrean['kode_poli'] . '-' . str_pad((string) $antrean['nomor_antrean'], 3, '0', STR_PAD_LEFT)); ?></td>
                                        <td class="pe-3"><span class="badge text-bg-<?= aman_html($kelas_status[$antrean['status_antrean']]); ?>"><?= aman_html($label_status[$antrean['status_antrean']]); ?></span><?php if ($antrean['status_antrean'] === 'dipanggil'): ?><span class="small text-primary fw-semibold d-block mt-1">Sedang dipanggil</span><?php endif; ?></td>
                                    </tr>
                        <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 p-3 p-md-4 border-top">
                            <p class="small text-secondary mb-0">Menampilkan <?= $nomor_awal; ?>–<?= $nomor_akhir; ?> dari <?= $total_data; ?> data</p>
                            <?php if ($total_halaman > 1): ?>
                                <nav class="overflow-auto" aria-label="Navigasi halaman antrean aktif">
                                    <ul class="pagination justify-content-md-end mb-0">
                                        <?php if ($halaman <= 1): ?>
                                            <li class="page-item disabled" aria-disabled="true"><span class="page-link">Previous</span></li>
                                        <?php else: ?>
                                            <li class="page-item"><a class="page-link" href="<?= aman_html(url_antrean_aktif($id_poli_filter, $halaman - 1)); ?>">Previous</a></li>
                                        <?php endif; ?>
                                        <?php for ($nomor_halaman = 1; $nomor_halaman <= $total_halaman; $nomor_halaman++): ?>
                                            <li class="page-item <?= $nomor_halaman === $halaman ? 'active' : ''; ?>"><a class="page-link" href="<?= aman_html(url_antrean_aktif($id_poli_filter, $nomor_halaman)); ?>"><?= $nomor_halaman; ?></a></li>
                                        <?php endfor; ?>
                                        <?php if ($halaman >= $total_halaman): ?>
                                            <li class="page-item disabled" aria-disabled="true"><span class="page-link">Next</span></li>
                                        <?php else: ?>
                                            <li class="page-item"><a class="page-link" href="<?= aman_html(url_antrean_aktif($id_poli_filter, $halaman + 1)); ?>">Next</a></li>
                                        <?php endif; ?>
                                    </ul>
                                </nav>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>
            <?php endif; ?>
        </div>
    </div>
</main>
<?php require_once __DIR__ . '/includes/kaki.php'; ?>
