<?php
// ================= MODUL BERITA DAN PENGUMUMAN =================
// Menampilkan pengumuman dengan pencarian, pagination, dan tindakan CRUD Admin.

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
    $kondisi = ' WHERE judul LIKE :judul OR isi_pengumuman LIKE :isi_pengumuman';
    $parameter = [
        'judul' => '%' . $cari . '%',
        'isi_pengumuman' => '%' . $cari . '%',
    ];
}

$hitung_pengumuman = $koneksi_database->prepare('SELECT COUNT(*) FROM pengumuman' . $kondisi);
$hitung_pengumuman->execute($parameter);
$total_data = (int) $hitung_pengumuman->fetchColumn();
$total_halaman = max(1, (int) ceil($total_data / $batas_data));
$halaman = min($halaman, $total_halaman);
$offset = ($halaman - 1) * $batas_data;

$ambil_pengumuman = $koneksi_database->prepare(
    'SELECT id_pengumuman, judul, isi_pengumuman, tanggal_publikasi, status_aktif
     FROM pengumuman'
    . $kondisi .
    ' ORDER BY tanggal_publikasi DESC, id_pengumuman DESC
      LIMIT :batas_data OFFSET :offset'
);

foreach ($parameter as $nama_parameter => $nilai_parameter) {
    $ambil_pengumuman->bindValue(':' . $nama_parameter, $nilai_parameter, PDO::PARAM_STR);
}
$ambil_pengumuman->bindValue(':batas_data', $batas_data, PDO::PARAM_INT);
$ambil_pengumuman->bindValue(':offset', $offset, PDO::PARAM_INT);
$ambil_pengumuman->execute();
$daftar_pengumuman = $ambil_pengumuman->fetchAll();

/** Membentuk URL daftar pengumuman sambil mempertahankan kata kunci pencarian. */
function url_data_pengumuman(int $halaman, string $cari): string
{
    $parameter_url = ['halaman' => $halaman];

    if ($cari !== '') {
        $parameter_url['cari'] = $cari;
    }

    return url_aplikasi('admin/data-pengumuman.php?' . http_build_query($parameter_url));
}

$judul_halaman = 'Berita & Pengumuman';
$stylesheet_tambahan = 'assets/css/gaya-dashboard.css';
$menu_aktif = 'pengumuman';
require_once __DIR__ . '/../includes/kepala.php';
require_once __DIR__ . '/../includes/navbar_admin.php';
?>
<div class="d-flex dashboard-layout halaman-dashboard">
    <?php require_once __DIR__ . '/../includes/sidebar_admin.php'; ?>
    <main class="flex-grow-1 p-3 p-md-4 p-xl-5 overflow-hidden">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4"><div><p class="teks-label mb-1">Manajemen Informasi</p><h1 class="h3 mb-1">Berita &amp; Pengumuman</h1><p class="text-secondary mb-0">Kelola informasi pengumuman Puskesmas.</p></div><a class="btn btn-success align-self-start align-self-md-center" href="<?= aman_html(url_aplikasi('admin/formulir-pengumuman.php')); ?>">+ Tambah Pengumuman</a></div>
        <?php require_once __DIR__ . '/../includes/notifikasi.php'; ?>

        <section class="card kartu-dashboard mb-4"><div class="card-body p-3 p-md-4"><form class="row g-2" method="get"><div class="col-md"><label class="visually-hidden" for="cari">Cari pengumuman</label><input class="form-control" id="cari" type="search" name="cari" value="<?= aman_html($cari); ?>" placeholder="Cari judul atau isi pengumuman"></div><div class="col-md-auto d-flex gap-2"><button class="btn btn-success">Cari</button><a class="btn btn-outline-secondary" href="<?= aman_html(url_aplikasi('admin/data-pengumuman.php')); ?>">Reset</a></div></form></div></section>

        <section class="card kartu-dashboard"><div class="card-body p-0"><div class="d-flex justify-content-between p-3 p-md-4 border-bottom"><h2 class="judul-kartu mb-0">Daftar Pengumuman</h2><span class="small text-secondary"><?= $total_data; ?> data</span></div><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead class="table-success"><tr><th class="ps-3">No.</th><th>Judul / Isi</th><th>Tanggal Publikasi</th><th>Status</th><th class="text-end pe-3">Aksi</th></tr></thead><tbody><?php if ($daftar_pengumuman === []): ?><tr><td colspan="5" class="text-center text-secondary py-5">Pengumuman belum tersedia.</td></tr><?php else: foreach ($daftar_pengumuman as $urutan => $pengumuman): ?><tr><td class="ps-3"><?= $offset + $urutan + 1; ?></td><td><span class="fw-semibold d-block"><?= aman_html($pengumuman['judul']); ?></span><small class="text-secondary d-block text-truncate" style="max-width: 420px;"><?= aman_html($pengumuman['isi_pengumuman']); ?></small></td><td><?= aman_html(date('d/m/Y H:i', strtotime($pengumuman['tanggal_publikasi']))); ?></td><td><span class="badge text-bg-<?= $pengumuman['status_aktif'] === 'aktif' ? 'success' : 'secondary'; ?>"><?= $pengumuman['status_aktif'] === 'aktif' ? 'Aktif' : 'Nonaktif'; ?></span></td><td class="text-end pe-3"><div class="d-inline-flex align-items-center gap-2"><a class="btn btn-sm btn-outline-success" href="<?= aman_html(url_aplikasi('admin/formulir-pengumuman.php?id_pengumuman=' . (int) $pengumuman['id_pengumuman'])); ?>">Edit</a><form id="formulir-hapus-pengumuman-<?= (int) $pengumuman['id_pengumuman']; ?>" class="mb-0" method="post" action="<?= aman_html(url_aplikasi('proses/proses_pengumuman.php')); ?>"><input type="hidden" name="token_csrf" value="<?= aman_html(token_csrf()); ?>"><input type="hidden" name="aksi" value="hapus"><input type="hidden" name="id_pengumuman" value="<?= (int) $pengumuman['id_pengumuman']; ?>"><button class="btn btn-sm btn-outline-danger tombol-hapus-pengumuman" type="button" data-bs-toggle="modal" data-bs-target="#modalHapusPengumuman" data-id-formulir="formulir-hapus-pengumuman-<?= (int) $pengumuman['id_pengumuman']; ?>">Hapus</button></form></div></td></tr><?php endforeach; endif; ?></tbody></table></div><?php if ($total_halaman > 1): ?><nav class="p-3 p-md-4" aria-label="Navigasi halaman pengumuman"><ul class="pagination justify-content-end mb-0"><li class="page-item <?= $halaman <= 1 ? 'disabled' : ''; ?>"><a class="page-link" href="<?= aman_html(url_data_pengumuman(max(1, $halaman - 1), $cari)); ?>">Sebelumnya</a></li><?php for ($nomor_halaman = 1; $nomor_halaman <= $total_halaman; $nomor_halaman++): ?><li class="page-item <?= $nomor_halaman === $halaman ? 'active' : ''; ?>"><a class="page-link" href="<?= aman_html(url_data_pengumuman($nomor_halaman, $cari)); ?>"><?= $nomor_halaman; ?></a></li><?php endfor; ?><li class="page-item <?= $halaman >= $total_halaman ? 'disabled' : ''; ?>"><a class="page-link" href="<?= aman_html(url_data_pengumuman(min($total_halaman, $halaman + 1), $cari)); ?>">Berikutnya</a></li></ul></nav><?php endif; ?></div></section>
    </main>
</div>

<!-- ================= MODAL KONFIRMASI HAPUS PENGUMUMAN ================= -->
<div class="modal fade" id="modalHapusPengumuman" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content border-0"><div class="modal-header bg-success text-white"><h2 class="fs-5 modal-title">Konfirmasi Hapus Pengumuman</h2><button class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div><div class="modal-body">Apakah Anda yakin ingin menghapus pengumuman ini?</div><div class="modal-footer"><button class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button><button class="btn btn-success" id="konfirmasiHapusPengumuman">Ya, Hapus</button></div></div></div></div>
<script>document.addEventListener('DOMContentLoaded',function(){let id='';document.querySelectorAll('.tombol-hapus-pengumuman').forEach(function(t){t.addEventListener('click',function(){id=this.dataset.idFormulir;});});document.getElementById('konfirmasiHapusPengumuman').addEventListener('click',function(){const formulir=document.getElementById(id);if(formulir){formulir.submit();}});});</script>
<?php require_once __DIR__ . '/../includes/kaki.php'; ?>
