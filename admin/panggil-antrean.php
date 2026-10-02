<?php
// ================= MODUL PANGGIL ANTREAN =================
// Menampilkan antrean hari ini per poli dan menyediakan aksi perubahan status untuk Admin.

declare(strict_types=1);

require_once __DIR__ . '/../includes/middleware_peran.php';
require_once __DIR__ . '/../konfigurasi/koneksi_database.php';

wajib_peran('admin');

// ================= PENGAMBILAN ANTREAN HARI INI =================

$tanggal_hari_ini = date('Y-m-d');
$ambil_antrean = $koneksi_database->prepare(
    'SELECT a.id_antrean, a.nomor_antrean, a.status_antrean, a.waktu_dipanggil,
            pa.nama_pasien, po.id_poli, po.kode_poli, po.nama_poli
     FROM antrean a
     INNER JOIN pasien pa ON pa.id_pasien = a.id_pasien
     INNER JOIN poli po ON po.id_poli = a.id_poli
     WHERE a.tanggal_antrean = :tanggal_antrean
     ORDER BY po.nama_poli ASC, a.nomor_antrean ASC'
);
$ambil_antrean->execute(['tanggal_antrean' => $tanggal_hari_ini]);
$daftar_antrean = $ambil_antrean->fetchAll();

// ================= PENGELOMPOKAN BERDASARKAN POLI =================

$antrean_per_poli = [];
foreach ($daftar_antrean as $antrean) {
    $id_poli = (int) $antrean['id_poli'];

    if (!isset($antrean_per_poli[$id_poli])) {
        $antrean_per_poli[$id_poli] = [
            'kode_poli' => $antrean['kode_poli'],
            'nama_poli' => $antrean['nama_poli'],
            'antrean' => [],
        ];
    }

    $antrean_per_poli[$id_poli]['antrean'][] = $antrean;
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

$judul_halaman = 'Panggil Antrean';
$stylesheet_tambahan = 'assets/css/gaya-dashboard.css';
$menu_aktif = 'panggil-antrean';
require_once __DIR__ . '/../includes/kepala.php';
require_once __DIR__ . '/../includes/navbar_admin.php';
?>
<div class="d-flex dashboard-layout halaman-dashboard">
    <?php require_once __DIR__ . '/../includes/sidebar_admin.php'; ?>
    <main class="flex-grow-1 p-3 p-md-4 p-xl-5 overflow-hidden">
        <div class="mb-4">
            <p class="teks-label mb-1">Operasional Pelayanan</p>
            <h1 class="h3 mb-1">Panggil Antrean</h1>
            <p class="text-secondary mb-0">Kelola antrean pasien untuk tanggal <?= aman_html(date('d/m/Y')); ?>.</p>
        </div>

        <?php require_once __DIR__ . '/../includes/notifikasi.php'; ?>

        <?php if ($antrean_per_poli === []): ?>
            <section class="card kartu-dashboard" style="max-width: 680px;">
                <div class="card-body p-4 p-md-5">
                    <h2 class="judul-kartu mb-2">Belum Ada Antrean Hari Ini</h2>
                    <p class="text-secondary mb-0">Antrean untuk tanggal hari ini belum tersedia.</p>
                </div>
            </section>
        <?php else: ?>
            <div class="vstack gap-4">
                <?php foreach ($antrean_per_poli as $poli): ?>
                    <section class="card kartu-dashboard">
                        <div class="card-body p-0">
                            <div class="d-flex justify-content-between align-items-center p-3 p-md-4 border-bottom">
                                <div>
                                    <p class="teks-label mb-1"><?= aman_html($poli['kode_poli']); ?></p>
                                    <h2 class="judul-kartu mb-0"><?= aman_html($poli['nama_poli']); ?></h2>
                                </div>
                                <span class="small text-secondary"><?= count($poli['antrean']); ?> antrean</span>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-success">
                                        <tr>
                                            <th class="ps-3">Nomor Antrean</th>
                                            <th>Nama Pasien</th>
                                            <th>Nama Poli</th>
                                            <th>Status</th>
                                            <th>Waktu Dipanggil</th>
                                            <th class="text-end pe-3">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($poli['antrean'] as $antrean): ?>
                                            <tr>
                                                <td class="ps-3 fw-semibold"><?= aman_html($antrean['kode_poli'] . '-' . str_pad((string) $antrean['nomor_antrean'], 3, '0', STR_PAD_LEFT)); ?></td>
                                                <td><?= aman_html($antrean['nama_pasien']); ?></td>
                                                <td><?= aman_html($antrean['nama_poli']); ?></td>
                                                <td><span class="badge text-bg-<?= aman_html($kelas_status[$antrean['status_antrean']] ?? 'secondary'); ?>"><?= aman_html($label_status[$antrean['status_antrean']] ?? 'Tidak diketahui'); ?></span></td>
                                                <td><?= $antrean['waktu_dipanggil'] !== null ? aman_html(date('d/m/Y H:i', strtotime($antrean['waktu_dipanggil']))) : '-'; ?></td>
                                                <td class="text-end pe-3">
                                                    <?php if (in_array($antrean['status_antrean'], ['menunggu', 'dipanggil'], true)): ?>
                                                        <form method="post" action="<?= aman_html(url_aplikasi('proses/proses_panggil_antrean.php')); ?>" class="d-inline-flex gap-2">
                                                            <input type="hidden" name="token_csrf" value="<?= aman_html(token_csrf()); ?>">
                                                            <input type="hidden" name="id_antrean" value="<?= (int) $antrean['id_antrean']; ?>">
                                                            <?php if ($antrean['status_antrean'] === 'menunggu'): ?>
                                                                <button class="btn btn-sm btn-success" type="submit" name="status_tujuan" value="dipanggil">Panggil</button>
                                                            <?php else: ?>
                                                                <button class="btn btn-sm btn-success" type="submit" name="status_tujuan" value="selesai">Selesai</button>
                                                            <?php endif; ?>
                                                            <button class="btn btn-sm btn-outline-danger" type="submit" name="status_tujuan" value="dibatalkan">Batalkan</button>
                                                        </form>
                                                    <?php else: ?>
                                                        <span class="small text-secondary">Tidak ada aksi</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </section>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</div>
<?php require_once __DIR__ . '/../includes/kaki.php'; ?>
