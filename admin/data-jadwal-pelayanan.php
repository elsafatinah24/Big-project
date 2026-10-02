<?php
// ================= MODUL JADWAL PELAYANAN =================
// Menampilkan jadwal pelayanan dengan pencarian, pagination, dan tindakan CRUD Admin.

declare(strict_types=1);

require_once __DIR__ . '/../includes/middleware_peran.php';
require_once __DIR__ . '/../konfigurasi/koneksi_database.php';

wajib_peran('admin');

// ================= PENCARIAN DAN PAGINATION =================

$cari = trim((string) ($_GET['cari'] ?? ''));
$halaman = isset($_GET['halaman']) && ctype_digit((string) $_GET['halaman'])
    ? max(1, (int) $_GET['halaman'])
    : 1;
$batas_data = 10;
$kondisi = '';
$parameter = [];

if ($cari !== '') {
    $kondisi = ' WHERE hari LIKE :hari OR keterangan LIKE :keterangan';
    $parameter = [
        'hari' => '%' . $cari . '%',
        'keterangan' => '%' . $cari . '%',
    ];
}

$hitung_jadwal = $koneksi_database->prepare('SELECT COUNT(*) FROM jadwal_pelayanan' . $kondisi);
$hitung_jadwal->execute($parameter);
$total_data = (int) $hitung_jadwal->fetchColumn();
$total_halaman = max(1, (int) ceil($total_data / $batas_data));
$halaman = min($halaman, $total_halaman);
$offset = ($halaman - 1) * $batas_data;

$ambil_jadwal = $koneksi_database->prepare(
    "SELECT id_jadwal_pelayanan, hari, jam_buka, jam_tutup, keterangan, status_aktif
     FROM jadwal_pelayanan"
    . $kondisi .
    " ORDER BY FIELD(hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'), jam_buka ASC
      LIMIT :batas_data OFFSET :offset"
);

foreach ($parameter as $nama_parameter => $nilai_parameter) {
    $ambil_jadwal->bindValue(':' . $nama_parameter, $nilai_parameter, PDO::PARAM_STR);
}
$ambil_jadwal->bindValue(':batas_data', $batas_data, PDO::PARAM_INT);
$ambil_jadwal->bindValue(':offset', $offset, PDO::PARAM_INT);
$ambil_jadwal->execute();
$daftar_jadwal = $ambil_jadwal->fetchAll();

/** Membentuk URL daftar jadwal sambil mempertahankan kata kunci pencarian. */
function url_data_jadwal_pelayanan(int $halaman, string $cari): string
{
    $parameter_url = ['halaman' => $halaman];

    if ($cari !== '') {
        $parameter_url['cari'] = $cari;
    }

    return url_aplikasi('admin/data-jadwal-pelayanan.php?' . http_build_query($parameter_url));
}

$judul_halaman = 'Jadwal Pelayanan';
$stylesheet_tambahan = 'assets/css/gaya-dashboard.css';
$menu_aktif = 'jadwal-pelayanan';
require_once __DIR__ . '/../includes/kepala.php';
require_once __DIR__ . '/../includes/navbar_admin.php';
?>
<div class="d-flex dashboard-layout halaman-dashboard">
    <?php require_once __DIR__ . '/../includes/sidebar_admin.php'; ?>
    <main class="flex-grow-1 p-3 p-md-4 p-xl-5 overflow-hidden">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div><p class="teks-label mb-1">Manajemen Layanan</p><h1 class="h3 mb-1">Jadwal Pelayanan</h1><p class="text-secondary mb-0">Kelola jadwal pelayanan Puskesmas.</p></div>
            <a class="btn btn-success align-self-start align-self-md-center" href="<?= aman_html(url_aplikasi('admin/formulir-jadwal-pelayanan.php')); ?>">+ Tambah Jadwal</a>
        </div>
        <?php require_once __DIR__ . '/../includes/notifikasi.php'; ?>

        <section class="card kartu-dashboard mb-4"><div class="card-body p-3 p-md-4"><form class="row g-2" method="get"><div class="col-md"><label class="visually-hidden" for="cari">Cari jadwal</label><input class="form-control" id="cari" type="search" name="cari" value="<?= aman_html($cari); ?>" placeholder="Cari hari atau keterangan jadwal"></div><div class="col-md-auto d-flex gap-2"><button class="btn btn-success">Cari</button><a class="btn btn-outline-secondary" href="<?= aman_html(url_aplikasi('admin/data-jadwal-pelayanan.php')); ?>">Reset</a></div></form></div></section>

        <section class="card kartu-dashboard"><div class="card-body p-0"><div class="d-flex justify-content-between p-3 p-md-4 border-bottom"><h2 class="judul-kartu mb-0">Daftar Jadwal</h2><span class="small text-secondary"><?= $total_data; ?> data</span></div><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead class="table-success"><tr><th class="ps-3">No.</th><th>Hari</th><th>Jam Buka</th><th>Jam Tutup</th><th>Keterangan</th><th>Status</th><th class="text-end pe-3">Aksi</th></tr></thead><tbody><?php if ($daftar_jadwal === []): ?><tr><td colspan="7" class="text-center text-secondary py-5">Jadwal pelayanan belum tersedia.</td></tr><?php else: foreach ($daftar_jadwal as $urutan => $jadwal): ?><tr><td class="ps-3"><?= $offset + $urutan + 1; ?></td><td class="fw-semibold"><?= aman_html($jadwal['hari']); ?></td><td><?= aman_html(substr($jadwal['jam_buka'], 0, 5)); ?></td><td><?= aman_html(substr($jadwal['jam_tutup'], 0, 5)); ?></td><td class="text-secondary"><?= $jadwal['keterangan'] !== null && $jadwal['keterangan'] !== '' ? aman_html($jadwal['keterangan']) : '-'; ?></td><td><span class="badge text-bg-<?= $jadwal['status_aktif'] === 'aktif' ? 'success' : 'secondary'; ?>"><?= $jadwal['status_aktif'] === 'aktif' ? 'Aktif' : 'Nonaktif'; ?></span></td><td class="text-end pe-3"><div class="d-inline-flex align-items-center gap-2"><a class="btn btn-sm btn-outline-success" href="<?= aman_html(url_aplikasi('admin/formulir-jadwal-pelayanan.php?id_jadwal_pelayanan=' . (int) $jadwal['id_jadwal_pelayanan'])); ?>">Edit</a><form id="formulir-hapus-jadwal-<?= (int) $jadwal['id_jadwal_pelayanan']; ?>" class="mb-0" method="post" action="<?= aman_html(url_aplikasi('proses/proses_jadwal_pelayanan.php')); ?>"><input type="hidden" name="token_csrf" value="<?= aman_html(token_csrf()); ?>"><input type="hidden" name="aksi" value="hapus"><input type="hidden" name="id_jadwal_pelayanan" value="<?= (int) $jadwal['id_jadwal_pelayanan']; ?>"><button class="btn btn-sm btn-outline-danger tombol-hapus-jadwal" type="button" data-bs-toggle="modal" data-bs-target="#modalHapusJadwal" data-id-formulir="formulir-hapus-jadwal-<?= (int) $jadwal['id_jadwal_pelayanan']; ?>">Hapus</button></form></div></td></tr><?php endforeach; endif; ?></tbody></table></div><?php if ($total_halaman > 1): ?><nav class="p-3 p-md-4" aria-label="Navigasi halaman jadwal pelayanan"><ul class="pagination justify-content-end mb-0"><li class="page-item <?= $halaman <= 1 ? 'disabled' : ''; ?>"><a class="page-link" href="<?= aman_html(url_data_jadwal_pelayanan(max(1, $halaman - 1), $cari)); ?>">Sebelumnya</a></li><?php for ($nomor_halaman = 1; $nomor_halaman <= $total_halaman; $nomor_halaman++): ?><li class="page-item <?= $nomor_halaman === $halaman ? 'active' : ''; ?>"><a class="page-link" href="<?= aman_html(url_data_jadwal_pelayanan($nomor_halaman, $cari)); ?>"><?= $nomor_halaman; ?></a></li><?php endfor; ?><li class="page-item <?= $halaman >= $total_halaman ? 'disabled' : ''; ?>"><a class="page-link" href="<?= aman_html(url_data_jadwal_pelayanan(min($total_halaman, $halaman + 1), $cari)); ?>">Berikutnya</a></li></ul></nav><?php endif; ?></div></section>
    </main>
</div>

<!-- ================= MODAL KONFIRMASI HAPUS JADWAL ================= -->
<div class="modal fade" id="modalHapusJadwal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content border-0"><div class="modal-header bg-success text-white"><h2 class="fs-5 modal-title">Konfirmasi Hapus Jadwal</h2><button class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div><div class="modal-body">Apakah Anda yakin ingin menghapus jadwal pelayanan ini?</div><div class="modal-footer"><button class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button><button class="btn btn-success" id="konfirmasiHapusJadwal">Ya, Hapus</button></div></div></div></div>
<script>document.addEventListener('DOMContentLoaded',function(){let id='';document.querySelectorAll('.tombol-hapus-jadwal').forEach(function(t){t.addEventListener('click',function(){id=this.dataset.idFormulir;});});document.getElementById('konfirmasiHapusJadwal').addEventListener('click',function(){const formulir=document.getElementById(id);if(formulir){formulir.submit();}});});</script>
<?php require_once __DIR__ . '/../includes/kaki.php'; ?>
