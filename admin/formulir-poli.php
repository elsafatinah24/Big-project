<?php
// ================= FORMULIR DATA POLI =================
// Menampilkan formulir tambah atau edit data poli untuk Admin.
declare(strict_types=1);
require_once __DIR__ . '/../includes/middleware_peran.php';
require_once __DIR__ . '/../konfigurasi/koneksi_database.php';
wajib_peran('admin');
$id_poli = isset($_GET['id_poli']) && ctype_digit((string) $_GET['id_poli']) ? (int) $_GET['id_poli'] : null;
$data_poli = ['id_poli' => '', 'kode_poli' => '', 'nama_poli' => '', 'keterangan' => '', 'status_aktif' => 'aktif'];
$mode_edit = $id_poli !== null;
if ($mode_edit) {
    $ambil_poli = $koneksi_database->prepare('SELECT id_poli, kode_poli, nama_poli, keterangan, status_aktif FROM poli WHERE id_poli = :id_poli LIMIT 1');
    $ambil_poli->execute(['id_poli' => $id_poli]);
    $hasil_poli = $ambil_poli->fetch();
    if ($hasil_poli === false) { simpan_notifikasi('danger', 'Data poli tidak ditemukan.'); alihkan('admin/data-poli.php'); }
    $data_poli = $hasil_poli;
}
$judul_halaman = $mode_edit ? 'Edit Data Poli' : 'Tambah Data Poli';
$stylesheet_tambahan = 'assets/css/gaya-dashboard.css?v=' . filemtime(__DIR__ . '/../assets/css/gaya-dashboard.css');
$menu_aktif = 'poli';
require_once __DIR__ . '/../includes/kepala.php';
require_once __DIR__ . '/../includes/navbar_admin.php';
?>
<div class="d-flex dashboard-layout halaman-dashboard">
<?php require_once __DIR__ . '/../includes/sidebar_admin.php'; ?>
<main class="flex-grow-1 p-3 p-md-4 p-xl-5"><div class="mb-4"><p class="teks-label mb-1">Manajemen Data</p><h1 class="h3 mb-1"><?= $mode_edit ? 'Edit Data Poli' : 'Tambah Data Poli'; ?></h1><p class="text-secondary mb-0">Lengkapi data poli pelayanan dengan benar.</p></div><?php require_once __DIR__ . '/../includes/notifikasi.php'; ?>
<section class="card kartu-dashboard" style="max-width:760px"><div class="card-body p-4"><form method="post" action="<?= aman_html(url_aplikasi('proses/proses_poli.php')); ?>"><input type="hidden" name="token_csrf" value="<?= aman_html(token_csrf()); ?>"><input type="hidden" name="aksi" value="<?= $mode_edit ? 'ubah' : 'tambah'; ?>"><?php if ($mode_edit): ?><input type="hidden" name="id_poli" value="<?= (int) $data_poli['id_poli']; ?>"><?php endif; ?><div class="mb-3"><label class="form-label" for="nama_poli">Nama Poli</label><input class="form-control" id="nama_poli" name="nama_poli" value="<?= aman_html($data_poli['nama_poli']); ?>" maxlength="100" required></div><div class="mb-3"><label class="form-label" for="keterangan">Keterangan</label><textarea class="form-control" id="keterangan" name="keterangan" rows="4" maxlength="1000" placeholder="Keterangan singkat poli (opsional)"><?= aman_html($data_poli['keterangan']); ?></textarea></div><div class="mb-4"><label class="form-label" for="status_aktif">Status</label><select class="form-select" id="status_aktif" name="status_aktif"><option value="aktif" <?= $data_poli['status_aktif'] === 'aktif' ? 'selected' : ''; ?>>Aktif</option><option value="tidak_aktif" <?= $data_poli['status_aktif'] === 'tidak_aktif' ? 'selected' : ''; ?>>Tidak Aktif</option></select></div><div class="form-actions-poli"><button class="btn btn-success" type="submit"><?= $mode_edit ? 'Simpan Perubahan' : 'Simpan Data Poli'; ?></button><a class="btn btn-outline-secondary" href="<?= aman_html(url_aplikasi('admin/data-poli.php')); ?>">Batal</a></div></form></div></section>
</main></div>
<script>
// ================= INPUT KODE POLI =================
// Menyisipkan field kode poli sebelum field nama poli untuk form tambah dan edit.
document.addEventListener('DOMContentLoaded', function () {
    const namaPoli = document.getElementById('nama_poli');
    if (!namaPoli) return;
    const wadah = document.createElement('div');
    wadah.className = 'mb-3';
    wadah.innerHTML = '<label class="form-label" for="kode_poli">Kode Poli</label><input class="form-control text-uppercase" id="kode_poli" name="kode_poli" maxlength="10" required placeholder="Contoh: UM, GG, KIA, LS">';
    namaPoli.closest('.mb-3').before(wadah);
    document.getElementById('kode_poli').value = <?= json_encode($data_poli['kode_poli']); ?>;
});
</script>
<?php require_once __DIR__ . '/../includes/kaki.php'; ?>
