<?php
// ================= MODUL DATA POLI =================
// Menampilkan daftar poli dengan pencarian dan pagination untuk Admin.
declare(strict_types=1);
require_once __DIR__ . '/../includes/middleware_peran.php';
require_once __DIR__ . '/../konfigurasi/koneksi_database.php';
wajib_peran('admin');

// ================= PENCARIAN DAN PAGINATION =================
$kata_kunci = trim((string) ($_GET['cari'] ?? ''));
$nomor_halaman = isset($_GET['halaman']) && ctype_digit((string) $_GET['halaman']) ? max(1, (int) $_GET['halaman']) : 1;
$batas_data = 10;
$kondisi_cari = '';
$parameter_cari = [];
if ($kata_kunci !== '') {
    // PDO native prepare memerlukan placeholder berbeda untuk setiap kondisi LIKE.
    $kondisi_cari = ' WHERE kode_poli LIKE :kata_kunci_kode OR nama_poli LIKE :kata_kunci_nama OR keterangan LIKE :kata_kunci_keterangan';
    $parameter_cari['kata_kunci_kode'] = '%' . $kata_kunci . '%';
    $parameter_cari['kata_kunci_nama'] = '%' . $kata_kunci . '%';
    $parameter_cari['kata_kunci_keterangan'] = '%' . $kata_kunci . '%';
}
$hitung_data = $koneksi_database->prepare('SELECT COUNT(*) FROM poli' . $kondisi_cari);
$hitung_data->execute($parameter_cari);
$total_data = (int) $hitung_data->fetchColumn();
$total_halaman = max(1, (int) ceil($total_data / $batas_data));
$nomor_halaman = min($nomor_halaman, $total_halaman);
$data_awal = ($nomor_halaman - 1) * $batas_data;
$ambil_poli = $koneksi_database->prepare('SELECT id_poli, kode_poli, nama_poli, keterangan, status_aktif FROM poli' . $kondisi_cari . ' ORDER BY nama_poli ASC LIMIT :batas_data OFFSET :data_awal');
foreach ($parameter_cari as $nama_parameter => $nilai_parameter) { $ambil_poli->bindValue(':' . $nama_parameter, $nilai_parameter, PDO::PARAM_STR); }
$ambil_poli->bindValue(':batas_data', $batas_data, PDO::PARAM_INT);
$ambil_poli->bindValue(':data_awal', $data_awal, PDO::PARAM_INT);
$ambil_poli->execute();
$daftar_poli = $ambil_poli->fetchAll();

function url_data_poli(int $halaman, string $kata_kunci): string
{
    $parameter_url = ['halaman' => $halaman];
    if ($kata_kunci !== '') { $parameter_url['cari'] = $kata_kunci; }
    return url_aplikasi('admin/data-poli.php?' . http_build_query($parameter_url));
}
$judul_halaman = 'Data Poli';
$stylesheet_tambahan = 'assets/css/gaya-dashboard.css';
$menu_aktif = 'poli';
require_once __DIR__ . '/../includes/kepala.php';
require_once __DIR__ . '/../includes/navbar_admin.php';
?>
<div class="d-flex dashboard-layout halaman-dashboard">
<?php require_once __DIR__ . '/../includes/sidebar_admin.php'; ?>
<main class="flex-grow-1 p-3 p-md-4 p-xl-5 overflow-hidden">
 <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4"><div><p class="teks-label mb-1">Manajemen Data</p><h1 class="h3 mb-1">Data Poli</h1><p class="text-secondary mb-0">Kelola daftar poli pelayanan Puskesmas.</p></div><a class="btn btn-success" href="<?= aman_html(url_aplikasi('admin/formulir-poli.php')); ?>">+ Tambah Poli</a></div>
 <?php require_once __DIR__ . '/../includes/notifikasi.php'; ?>
 <section class="card kartu-dashboard mb-4"><div class="card-body p-3 p-md-4"><form class="row g-2" method="get"><div class="col-md"><label class="visually-hidden" for="cari">Cari poli</label><input class="form-control" type="search" id="cari" name="cari" value="<?= aman_html($kata_kunci); ?>" placeholder="Cari kode, nama, atau keterangan poli"></div><div class="col-md-auto d-flex gap-2"><button class="btn btn-success">Cari</button><a class="btn btn-outline-secondary" href="<?= aman_html(url_aplikasi('admin/data-poli.php')); ?>">Reset</a></div></form></div></section>
 <section class="card kartu-dashboard"><div class="card-body p-0"><div class="d-flex justify-content-between p-3 p-md-4 border-bottom"><h2 class="judul-kartu mb-0">Daftar Poli</h2><span class="small text-secondary"><?= $total_data; ?> data</span></div><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead class="table-success"><tr><th class="ps-3">No.</th><th>Kode Poli</th><th>Nama Poli</th><th>Keterangan</th><th>Status</th><th class="text-end pe-3">Aksi</th></tr></thead><tbody>
 <?php if ($daftar_poli === []): ?><tr><td colspan="6" class="text-center text-secondary py-5">Data poli belum tersedia.</td></tr><?php else: foreach ($daftar_poli as $urutan => $poli): ?><tr><td class="ps-3"><?= $data_awal + $urutan + 1; ?></td><td><span class="badge text-bg-light border text-dark"><?= aman_html($poli['kode_poli']); ?></span></td><td class="fw-semibold"><?= aman_html($poli['nama_poli']); ?></td><td class="text-secondary"><?= $poli['keterangan'] ? aman_html($poli['keterangan']) : '-'; ?></td><td><span class="badge text-bg-<?= $poli['status_aktif'] === 'aktif' ? 'success' : 'secondary'; ?>"><?= $poli['status_aktif'] === 'aktif' ? 'Aktif' : 'Tidak Aktif'; ?></span></td><td class="text-end pe-3"><div class="d-inline-flex align-items-center gap-2"><a class="btn btn-sm btn-outline-success" href="<?= aman_html(url_aplikasi('admin/formulir-poli.php?id_poli=' . (int) $poli['id_poli'])); ?>">Edit</a><form id="formulir-hapus-poli-<?= (int) $poli['id_poli']; ?>" class="mb-0" method="post" action="<?= aman_html(url_aplikasi('proses/proses_poli.php')); ?>"><input type="hidden" name="token_csrf" value="<?= aman_html(token_csrf()); ?>"><input type="hidden" name="aksi" value="hapus"><input type="hidden" name="id_poli" value="<?= (int) $poli['id_poli']; ?>"><button class="btn btn-sm btn-outline-danger tombol-hapus-poli" type="button" data-bs-toggle="modal" data-bs-target="#modalKonfirmasiHapusPoli" data-id-formulir="formulir-hapus-poli-<?= (int) $poli['id_poli']; ?>">Hapus</button></form></div></td></tr><?php endforeach; endif; ?>
 </tbody></table></div>
 <?php if ($total_halaman > 1): ?><nav class="p-3" aria-label="Navigasi halaman data poli"><ul class="pagination justify-content-end mb-0"><li class="page-item <?= $nomor_halaman <= 1 ? 'disabled' : ''; ?>"><a class="page-link" href="<?= aman_html(url_data_poli(max(1, $nomor_halaman - 1), $kata_kunci)); ?>">Sebelumnya</a></li><?php for ($halaman = 1; $halaman <= $total_halaman; $halaman++): ?><li class="page-item <?= $halaman === $nomor_halaman ? 'active' : ''; ?>"><a class="page-link" href="<?= aman_html(url_data_poli($halaman, $kata_kunci)); ?>"><?= $halaman; ?></a></li><?php endfor; ?><li class="page-item <?= $nomor_halaman >= $total_halaman ? 'disabled' : ''; ?>"><a class="page-link" href="<?= aman_html(url_data_poli(min($total_halaman, $nomor_halaman + 1), $kata_kunci)); ?>">Berikutnya</a></li></ul></nav><?php endif; ?>
 </div></section>
</main></div>

<!-- ================= MODAL KONFIRMASI HAPUS POLI ================= -->
<div class="modal fade" id="modalKonfirmasiHapusPoli" tabindex="-1" aria-labelledby="judulModalHapusPoli" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"><div class="modal-content border-0 shadow">
        <div class="modal-header bg-success text-white"><h2 class="modal-title fs-5" id="judulModalHapusPoli">Konfirmasi Hapus Data Poli</h2><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
        <div class="modal-body">Apakah Anda yakin ingin menghapus data poli ini?</div>
        <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button><button type="button" class="btn btn-success" id="tombolKonfirmasiHapusPoli">Ya, Hapus</button></div>
    </div></div>
</div>

<script>
// ================= KONFIRMASI HAPUS POLI =================
// Modal hanya mengirim form hapus yang dipilih setelah Admin memberikan konfirmasi.
document.addEventListener('DOMContentLoaded', function () {
    let idFormulirHapus = '';
    document.querySelectorAll('.tombol-hapus-poli').forEach(function (tombol) {
        tombol.addEventListener('click', function () { idFormulirHapus = this.dataset.idFormulir; });
    });
    document.getElementById('tombolKonfirmasiHapusPoli').addEventListener('click', function () {
        const formulir = document.getElementById(idFormulirHapus);
        if (formulir) { formulir.submit(); }
    });
});
</script>
<?php require_once __DIR__ . '/../includes/kaki.php'; ?>
