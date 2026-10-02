<?php
// ================= MODUL LAPORAN ANTREAN =================
// Menampilkan laporan antrean bulanan berdasarkan poli untuk Admin.

declare(strict_types=1);

require_once __DIR__ . '/../includes/middleware_peran.php';
require_once __DIR__ . '/../konfigurasi/koneksi_database.php';

wajib_peran('admin');

/** Membentuk URL laporan sambil mempertahankan bulan, tahun, poli, dan halaman. */
function url_laporan_antrean(int $halaman, string $bulan, string $tahun, string $id_poli): string
{
    return url_aplikasi('admin/laporan-antrean.php?' . http_build_query([
        'halaman' => $halaman,
        'bulan' => $bulan,
        'tahun' => $tahun,
        'id_poli' => $id_poli,
    ]));
}

// ================= FILTER LAPORAN BULANAN =================

$nama_bulan = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
];
$bulan_filter = trim((string) ($_GET['bulan'] ?? (string) (int) date('n')));
$tahun_filter = trim((string) ($_GET['tahun'] ?? date('Y')));
$id_poli_filter = trim((string) ($_GET['id_poli'] ?? ''));
$halaman = isset($_GET['halaman']) && ctype_digit((string) $_GET['halaman'])
    ? max(1, (int) $_GET['halaman'])
    : 1;
$batas_data = 10;
$pesan_filter = null;

// Mengambil opsi poli dan tahun dari data yang sudah tersedia tanpa perubahan database.
$ambil_poli = $koneksi_database->prepare('SELECT id_poli, kode_poli, nama_poli FROM poli ORDER BY nama_poli ASC');
$ambil_poli->execute();
$daftar_poli = $ambil_poli->fetchAll();
$id_poli_tersedia = array_map(static fn (array $poli): int => (int) $poli['id_poli'], $daftar_poli);

$ambil_tahun = $koneksi_database->prepare(
    'SELECT DISTINCT YEAR(tanggal_antrean) AS tahun FROM antrean WHERE tanggal_antrean IS NOT NULL ORDER BY tahun DESC'
);
$ambil_tahun->execute();
$daftar_tahun = array_map(static fn (array $baris): int => (int) $baris['tahun'], $ambil_tahun->fetchAll());
$daftar_tahun[] = (int) date('Y');
$daftar_tahun = array_values(array_unique($daftar_tahun));
rsort($daftar_tahun, SORT_NUMERIC);

if (!ctype_digit($bulan_filter) || !isset($nama_bulan[(int) $bulan_filter])) {
    $pesan_filter = 'Bulan yang dipilih tidak valid.';
} elseif (!ctype_digit($tahun_filter) || !in_array((int) $tahun_filter, $daftar_tahun, true)) {
    $pesan_filter = 'Tahun yang dipilih tidak valid.';
} elseif ($id_poli_filter !== '' && (!ctype_digit($id_poli_filter) || !in_array((int) $id_poli_filter, $id_poli_tersedia, true))) {
    $pesan_filter = 'Pilihan poli tidak valid.';
}

$daftar_antrean = [];
$total_data = 0;
$total_halaman = 1;
$offset = 0;
$periode_laporan = 'Bulan dan tahun tidak valid';
$poli_laporan = 'Semua Poli';

if ($pesan_filter === null) {
    $bulan = (int) $bulan_filter;
    $tahun = (int) $tahun_filter;
    $tanggal_mulai = sprintf('%04d-%02d-01', $tahun, $bulan);
    $tanggal_selesai = date('Y-m-t', strtotime($tanggal_mulai));
    $periode_laporan = $nama_bulan[$bulan] . ' ' . $tahun;

    if ($id_poli_filter !== '') {
        foreach ($daftar_poli as $poli) {
            if ((int) $poli['id_poli'] === (int) $id_poli_filter) {
                $poli_laporan = $poli['nama_poli'];
                break;
            }
        }
    }

    $kondisi = ['a.tanggal_antrean BETWEEN :tanggal_mulai AND :tanggal_selesai'];
    $parameter = [
        'tanggal_mulai' => $tanggal_mulai,
        'tanggal_selesai' => $tanggal_selesai,
    ];

    if ($id_poli_filter !== '') {
        $kondisi[] = 'a.id_poli = :id_poli';
        $parameter['id_poli'] = (int) $id_poli_filter;
    }

    $where = ' WHERE ' . implode(' AND ', $kondisi);
    $dasar_query = ' FROM antrean a
        INNER JOIN pasien pa ON pa.id_pasien = a.id_pasien
        INNER JOIN poli po ON po.id_poli = a.id_poli';

    // ================= PAGINATION =================
    $hitung_laporan = $koneksi_database->prepare('SELECT COUNT(*)' . $dasar_query . $where);
    $hitung_laporan->execute($parameter);
    $total_data = (int) $hitung_laporan->fetchColumn();
    $total_halaman = max(1, (int) ceil($total_data / $batas_data));
    $halaman = min($halaman, $total_halaman);
    $offset = ($halaman - 1) * $batas_data;

    // ================= DATA LAPORAN =================
    $ambil_laporan = $koneksi_database->prepare(
        'SELECT a.nomor_antrean, a.tanggal_antrean, a.status_antrean, a.waktu_dipanggil, a.waktu_selesai,
                pa.nama_pasien, po.kode_poli, po.nama_poli'
        . $dasar_query . $where .
        ' ORDER BY a.tanggal_antrean ASC, po.nama_poli ASC, a.nomor_antrean ASC
          LIMIT :batas_data OFFSET :offset'
    );

    foreach ($parameter as $nama_parameter => $nilai_parameter) {
        $ambil_laporan->bindValue(':' . $nama_parameter, $nilai_parameter, is_int($nilai_parameter) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $ambil_laporan->bindValue(':batas_data', $batas_data, PDO::PARAM_INT);
    $ambil_laporan->bindValue(':offset', $offset, PDO::PARAM_INT);
    $ambil_laporan->execute();
    $daftar_antrean = $ambil_laporan->fetchAll();
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

$judul_halaman = 'Laporan Antrean Bulanan';
$stylesheet_tambahan = 'assets/css/gaya-dashboard.css';
$menu_aktif = 'laporan-antrean';
require_once __DIR__ . '/../includes/kepala.php';
require_once __DIR__ . '/../includes/navbar_admin.php';
?>
<style>
    @page {
        size: A4 landscape;
        margin: 12mm;
    }

    @media print {
        body { background: #fff !important; color: #000 !important; font-family: Arial, sans-serif !important; }
        .navbar, .sidebar-admin, footer, .no-print { display: none !important; }
        .dashboard-layout { display: block !important; }
        .halaman-dashboard main { padding: 0 !important; overflow: visible !important; width: 100% !important; }
        .kartu-dashboard { border: 0 !important; box-shadow: none !important; }
        .halaman-dashboard main > .d-flex { margin-bottom: 5mm !important; }
        .halaman-dashboard h1 { font-size: 18pt !important; letter-spacing: .3pt; }
        .halaman-dashboard main > .d-flex .text-secondary { color: #333 !important; font-size: 10pt !important; }
        .table-responsive { overflow: visible !important; }
        .table { width: 100% !important; table-layout: fixed !important; border-collapse: collapse !important; font-size: 10pt !important; }
        .table th, .table td { border: 0.25mm solid #555 !important; padding: 1.6mm 1.8mm !important; vertical-align: middle !important; }
        .table thead th { background: #d1e7dd !important; color: #000 !important; font-weight: 700 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .table th:nth-child(1), .table td:nth-child(1) { width: 4%; text-align: center; }
        .table th:nth-child(2), .table td:nth-child(2) { width: 12%; white-space: nowrap; }
        .table th:nth-child(3), .table td:nth-child(3) { width: 19%; }
        .table th:nth-child(4), .table td:nth-child(4) { width: 17%; }
        .table th:nth-child(5), .table td:nth-child(5) { width: 12%; white-space: nowrap; }
        .table th:nth-child(6), .table td:nth-child(6) { width: 10%; white-space: nowrap; }
        .table th:nth-child(7), .table td:nth-child(7), .table th:nth-child(8), .table td:nth-child(8) { width: 13%; white-space: nowrap; }
        .table .badge { border: 0.25mm solid #555 !important; color: #000 !important; background: transparent !important; font-size: 9pt !important; font-weight: 600 !important; }
        .table tr { break-inside: avoid; page-break-inside: avoid; }
    }
</style>
<div class="d-flex dashboard-layout halaman-dashboard">
    <?php require_once __DIR__ . '/../includes/sidebar_admin.php'; ?>
    <main class="flex-grow-1 p-3 p-md-4 p-xl-5 overflow-hidden">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <p class="teks-label mb-1">Rekap Pelayanan</p>
                <h1 class="h3 mb-2">LAPORAN ANTREAN PUSKESMAS</h1>
                <p class="text-secondary mb-0">Periode: <?= aman_html($periode_laporan); ?><br>Poli: <?= aman_html($poli_laporan); ?></p>
            </div>
            <button class="btn btn-outline-success align-self-start no-print" type="button" onclick="window.print()">Cetak Laporan</button>
        </div>

        <?php require_once __DIR__ . '/../includes/notifikasi.php'; ?>
        <?php if ($pesan_filter !== null): ?>
            <div class="alert alert-danger alert-dismissible fade show no-print" role="alert">
                <?= aman_html($pesan_filter); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
            </div>
        <?php endif; ?>

        <section class="card kartu-dashboard mb-4 no-print">
            <div class="card-body p-3 p-md-4">
                <form class="row g-3" method="get" novalidate>
                    <div class="col-md-4"><label class="form-label" for="bulan">Bulan</label><select class="form-select" id="bulan" name="bulan"><?php foreach ($nama_bulan as $nomor_bulan => $label_bulan): ?><option value="<?= $nomor_bulan; ?>" <?= $bulan_filter === (string) $nomor_bulan ? 'selected' : ''; ?>><?= aman_html($label_bulan); ?></option><?php endforeach; ?></select></div>
                    <div class="col-md-4"><label class="form-label" for="tahun">Tahun</label><select class="form-select" id="tahun" name="tahun"><?php foreach ($daftar_tahun as $tahun): ?><option value="<?= $tahun; ?>" <?= $tahun_filter === (string) $tahun ? 'selected' : ''; ?>><?= $tahun; ?></option><?php endforeach; ?></select></div>
                    <div class="col-md-4"><label class="form-label" for="id_poli">Poli</label><select class="form-select" id="id_poli" name="id_poli"><option value="">Semua Poli</option><?php foreach ($daftar_poli as $poli): ?><option value="<?= (int) $poli['id_poli']; ?>" <?= $id_poli_filter === (string) $poli['id_poli'] ? 'selected' : ''; ?>><?= aman_html($poli['kode_poli'] . ' - ' . $poli['nama_poli']); ?></option><?php endforeach; ?></select></div>
                    <div class="col-12 d-flex gap-2"><button class="btn btn-success" type="submit">Tampilkan Laporan</button><a class="btn btn-outline-secondary" href="<?= aman_html(url_aplikasi('admin/laporan-antrean.php')); ?>">Reset</a></div>
                </form>
            </div>
        </section>

        <?php if ($pesan_filter === null): ?>
            <?php if ($daftar_antrean === []): ?>
                <section class="card kartu-dashboard"><div class="card-body p-4 text-center text-secondary">Tidak ada data antrean pada periode dan poli yang dipilih.</div></section>
            <?php else: ?>
                <section class="card kartu-dashboard">
                    <div class="card-body p-0">
                        <div class="d-flex justify-content-between align-items-center p-3 p-md-4 border-bottom no-print"><h2 class="judul-kartu mb-0">Data Antrean</h2><span class="small text-secondary"><?= $total_data; ?> data</span></div>
                        <div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead class="table-success"><tr><th class="ps-3">No.</th><th>Nomor Antrean</th><th>Nama Pasien</th><th>Nama Poli</th><th>Tanggal Antrean</th><th>Status</th><th>Waktu Dipanggil</th><th class="pe-3">Waktu Selesai</th></tr></thead><tbody><?php foreach ($daftar_antrean as $urutan => $antrean): ?><tr><td class="ps-3"><?= $offset + $urutan + 1; ?></td><td class="fw-semibold"><?= aman_html($antrean['kode_poli'] . '-' . str_pad((string) $antrean['nomor_antrean'], 3, '0', STR_PAD_LEFT)); ?></td><td><?= aman_html($antrean['nama_pasien']); ?></td><td><?= aman_html($antrean['nama_poli']); ?></td><td><?= aman_html(date('d/m/Y', strtotime($antrean['tanggal_antrean']))); ?></td><td><span class="badge text-bg-<?= aman_html($kelas_status[$antrean['status_antrean']] ?? 'secondary'); ?>"><?= aman_html($label_status[$antrean['status_antrean']] ?? 'Tidak diketahui'); ?></span></td><td><?= $antrean['waktu_dipanggil'] !== null ? aman_html(date('d/m/Y H:i', strtotime($antrean['waktu_dipanggil']))) : '-'; ?></td><td class="pe-3"><?= $antrean['waktu_selesai'] !== null ? aman_html(date('d/m/Y H:i', strtotime($antrean['waktu_selesai']))) : '-'; ?></td></tr><?php endforeach; ?></tbody></table></div>
                        <?php if ($total_halaman > 1): ?><nav class="p-3 p-md-4 no-print" aria-label="Navigasi halaman laporan"><ul class="pagination justify-content-end mb-0"><li class="page-item <?= $halaman <= 1 ? 'disabled' : ''; ?>"><a class="page-link" href="<?= aman_html(url_laporan_antrean(max(1, $halaman - 1), $bulan_filter, $tahun_filter, $id_poli_filter)); ?>">Sebelumnya</a></li><?php for ($nomor_halaman = 1; $nomor_halaman <= $total_halaman; $nomor_halaman++): ?><li class="page-item <?= $nomor_halaman === $halaman ? 'active' : ''; ?>"><a class="page-link" href="<?= aman_html(url_laporan_antrean($nomor_halaman, $bulan_filter, $tahun_filter, $id_poli_filter)); ?>"><?= $nomor_halaman; ?></a></li><?php endfor; ?><li class="page-item <?= $halaman >= $total_halaman ? 'disabled' : ''; ?>"><a class="page-link" href="<?= aman_html(url_laporan_antrean(min($total_halaman, $halaman + 1), $bulan_filter, $tahun_filter, $id_poli_filter)); ?>">Berikutnya</a></li></ul></nav><?php endif; ?>
                    </div>
                </section>
            <?php endif; ?>
        <?php endif; ?>
    </main>
</div>
<?php require_once __DIR__ . '/../includes/kaki.php'; ?>
