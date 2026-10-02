<?php
// ================= MODUL DATA PASIEN =================
// Menampilkan daftar pasien dengan pencarian, pagination, dan akses CRUD Admin.

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
    $kondisi = ' WHERE p.nama_pasien LIKE :nama_pasien OR p.nomor_telepon LIKE :nomor_telepon';
    $parameter = [
        'nama_pasien' => '%' . $cari . '%',
        'nomor_telepon' => '%' . $cari . '%',
    ];
}

$hitung_pasien = $koneksi_database->prepare('SELECT COUNT(*) FROM pasien p' . $kondisi);
$hitung_pasien->execute($parameter);
$total_data = (int) $hitung_pasien->fetchColumn();
$total_halaman = max(1, (int) ceil($total_data / $batas_data));
$halaman = min($halaman, $total_halaman);
$offset = ($halaman - 1) * $batas_data;

$ambil_pasien = $koneksi_database->prepare(
    'SELECT p.id_pasien, p.nama_pasien, p.tanggal_lahir, p.jenis_kelamin, p.alamat, p.nomor_telepon, u.email
     FROM pasien p
     INNER JOIN pengguna u ON u.id_pengguna = p.id_pengguna'
    . $kondisi .
    ' ORDER BY p.nama_pasien ASC LIMIT :batas_data OFFSET :offset'
);

foreach ($parameter as $nama_parameter => $nilai_parameter) {
    $ambil_pasien->bindValue(':' . $nama_parameter, $nilai_parameter, PDO::PARAM_STR);
}
$ambil_pasien->bindValue(':batas_data', $batas_data, PDO::PARAM_INT);
$ambil_pasien->bindValue(':offset', $offset, PDO::PARAM_INT);
$ambil_pasien->execute();
$daftar_pasien = $ambil_pasien->fetchAll();

/** Membentuk URL daftar pasien sambil mempertahankan kata kunci pencarian. */
function url_data_pasien(int $halaman, string $cari): string
{
    $parameter_url = ['halaman' => $halaman];

    if ($cari !== '') {
        $parameter_url['cari'] = $cari;
    }

    return url_aplikasi('admin/data-pasien.php?' . http_build_query($parameter_url));
}

$judul_halaman = 'Data Pasien';
$stylesheet_tambahan = 'assets/css/gaya-dashboard.css';
$menu_aktif = 'pasien';
require_once __DIR__ . '/../includes/kepala.php';
require_once __DIR__ . '/../includes/navbar_admin.php';
?>
<div class="d-flex dashboard-layout halaman-dashboard">
    <?php require_once __DIR__ . '/../includes/sidebar_admin.php'; ?>
    <main class="flex-grow-1 p-3 p-md-4 p-xl-5 overflow-hidden">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div><p class="teks-label mb-1">Manajemen Data</p><h1 class="h3 mb-1">Data Pasien</h1><p class="text-secondary mb-0">Kelola data pasien dan akun login pasien.</p></div>
            <a class="btn btn-success align-self-start align-self-md-center" href="<?= aman_html(url_aplikasi('admin/formulir-pasien.php')); ?>">+ Tambah Pasien</a>
        </div>
        <?php require_once __DIR__ . '/../includes/notifikasi.php'; ?>

        <section class="card kartu-dashboard mb-4"><div class="card-body p-3"><form class="row g-2" method="get"><div class="col-md"><label class="visually-hidden" for="cari">Cari pasien</label><input class="form-control" id="cari" type="search" name="cari" value="<?= aman_html($cari); ?>" placeholder="Cari nama atau nomor telepon"></div><div class="col-md-auto d-flex gap-2"><button class="btn btn-success">Cari</button><a class="btn btn-outline-secondary" href="<?= aman_html(url_aplikasi('admin/data-pasien.php')); ?>">Reset</a></div></form></div></section>

        <section class="card kartu-dashboard"><div class="card-body p-0"><div class="d-flex justify-content-between p-3 border-bottom"><h2 class="judul-kartu mb-0">Daftar Pasien</h2><span class="small text-secondary"><?= $total_data; ?> data</span></div><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead class="table-success"><tr><th class="ps-3">No.</th><th>Nama / Email</th><th>Tanggal Lahir</th><th>Jenis Kelamin</th><th>Alamat</th><th>Telepon</th><th class="text-end pe-3">Aksi</th></tr></thead><tbody><?php if ($daftar_pasien === []): ?><tr><td colspan="7" class="text-center text-secondary py-5">Data pasien belum tersedia.</td></tr><?php else: foreach ($daftar_pasien as $urutan => $pasien): ?><tr><td class="ps-3"><?= $offset + $urutan + 1; ?></td><td><span class="fw-semibold d-block"><?= aman_html($pasien['nama_pasien']); ?></span><small class="text-secondary"><?= aman_html($pasien['email']); ?></small></td><td><?= aman_html(date('d/m/Y', strtotime($pasien['tanggal_lahir']))); ?></td><td><?= aman_html($pasien['jenis_kelamin']); ?></td><td class="text-secondary"><?= aman_html($pasien['alamat']); ?></td><td><?= aman_html($pasien['nomor_telepon']); ?></td><td class="text-end pe-3"><div class="d-inline-flex align-items-center gap-2"><a class="btn btn-sm btn-outline-success" href="<?= aman_html(url_aplikasi('admin/formulir-pasien.php?id_pasien=' . (int) $pasien['id_pasien'])); ?>">Edit</a><form id="formulir-hapus-pasien-<?= (int) $pasien['id_pasien']; ?>" class="mb-0" method="post" action="<?= aman_html(url_aplikasi('proses/proses_pasien.php')); ?>"><input type="hidden" name="token_csrf" value="<?= aman_html(token_csrf()); ?>"><input type="hidden" name="aksi" value="hapus"><input type="hidden" name="id_pasien" value="<?= (int) $pasien['id_pasien']; ?>"><button class="btn btn-sm btn-outline-danger tombol-hapus-pasien" type="button" data-bs-toggle="modal" data-bs-target="#modalHapusPasien" data-id-formulir="formulir-hapus-pasien-<?= (int) $pasien['id_pasien']; ?>">Hapus</button></form></div></td></tr><?php endforeach; endif; ?></tbody></table></div><?php if ($total_halaman > 1): ?><nav class="p-3"><ul class="pagination justify-content-end mb-0"><li class="page-item <?= $halaman <= 1 ? 'disabled' : ''; ?>"><a class="page-link" href="<?= aman_html(url_data_pasien(max(1, $halaman - 1), $cari)); ?>">Sebelumnya</a></li><?php for ($nomor_halaman = 1; $nomor_halaman <= $total_halaman; $nomor_halaman++): ?><li class="page-item <?= $nomor_halaman === $halaman ? 'active' : ''; ?>"><a class="page-link" href="<?= aman_html(url_data_pasien($nomor_halaman, $cari)); ?>"><?= $nomor_halaman; ?></a></li><?php endfor; ?><li class="page-item <?= $halaman >= $total_halaman ? 'disabled' : ''; ?>"><a class="page-link" href="<?= aman_html(url_data_pasien(min($total_halaman, $halaman + 1), $cari)); ?>">Berikutnya</a></li></ul></nav><?php endif; ?></div></section>
    </main>
</div>

<!-- ================= MODAL KONFIRMASI HAPUS PASIEN ================= -->
<div class="modal fade" id="modalHapusPasien" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content border-0"><div class="modal-header bg-success text-white"><h2 class="fs-5 modal-title">Konfirmasi Hapus Pasien</h2><button class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div><div class="modal-body">Apakah Anda yakin ingin menghapus data pasien ini?</div><div class="modal-footer"><button class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button><button class="btn btn-success" id="konfirmasiHapusPasien">Ya, Hapus</button></div></div></div></div>
<script>document.addEventListener('DOMContentLoaded',function(){let id='';document.querySelectorAll('.tombol-hapus-pasien').forEach(function(t){t.addEventListener('click',function(){id=this.dataset.idFormulir;});});document.getElementById('konfirmasiHapusPasien').addEventListener('click',function(){const f=document.getElementById(id);if(f){f.submit();}});});</script>
<?php require_once __DIR__ . '/../includes/kaki.php'; ?>
