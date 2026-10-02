<?php
// ================= FORMULIR PENGUMUMAN =================
// Menampilkan form tambah atau edit pengumuman untuk Admin.

declare(strict_types=1);

require_once __DIR__ . '/../includes/middleware_peran.php';
require_once __DIR__ . '/../konfigurasi/koneksi_database.php';

wajib_peran('admin');

$id_pengumuman = isset($_GET['id_pengumuman']) && ctype_digit((string) $_GET['id_pengumuman'])
    ? (int) $_GET['id_pengumuman']
    : null;
$mode_edit = $id_pengumuman !== null;
$data_pengumuman = [
    'id_pengumuman' => '',
    'judul' => '',
    'isi_pengumuman' => '',
    'gambar' => null,
    'tanggal_publikasi' => date('Y-m-d H:i:s'),
    'status_aktif' => 'aktif',
];

if ($mode_edit) {
    $ambil_pengumuman = $koneksi_database->prepare(
        'SELECT id_pengumuman, judul, isi_pengumuman, gambar, tanggal_publikasi, status_aktif
         FROM pengumuman
         WHERE id_pengumuman = :id_pengumuman
         LIMIT 1'
    );
    $ambil_pengumuman->execute(['id_pengumuman' => $id_pengumuman]);
    $hasil_pengumuman = $ambil_pengumuman->fetch();

    if ($hasil_pengumuman === false) {
        simpan_notifikasi('danger', 'Data pengumuman tidak ditemukan.');
        alihkan('admin/data-pengumuman.php');
    }

    $data_pengumuman = $hasil_pengumuman;
}

$judul_halaman = $mode_edit ? 'Edit Pengumuman' : 'Tambah Pengumuman';
$stylesheet_tambahan = 'assets/css/gaya-dashboard.css';
$menu_aktif = 'pengumuman';
require_once __DIR__ . '/../includes/kepala.php';
require_once __DIR__ . '/../includes/navbar_admin.php';
?>
<div class="d-flex dashboard-layout halaman-dashboard">
    <?php require_once __DIR__ . '/../includes/sidebar_admin.php'; ?>
    <main class="flex-grow-1 p-3 p-md-4 p-xl-5">
        <div class="mb-4"><p class="teks-label mb-1">Manajemen Informasi</p><h1 class="h3 mb-1"><?= $mode_edit ? 'Edit Pengumuman' : 'Tambah Pengumuman'; ?></h1><p class="text-secondary mb-0">Lengkapi informasi pengumuman dengan benar.</p></div>
        <?php require_once __DIR__ . '/../includes/notifikasi.php'; ?>
        <section class="card kartu-dashboard" style="max-width: 860px;"><div class="card-body p-4"><form method="post" enctype="multipart/form-data" action="<?= aman_html(url_aplikasi('proses/proses_pengumuman.php')); ?>"><input type="hidden" name="token_csrf" value="<?= aman_html(token_csrf()); ?>"><input type="hidden" name="aksi" value="<?= $mode_edit ? 'ubah' : 'tambah'; ?>"><?php if ($mode_edit): ?><input type="hidden" name="id_pengumuman" value="<?= (int) $data_pengumuman['id_pengumuman']; ?>"><?php endif; ?><div class="row g-3"><div class="col-12"><label class="form-label" for="judul">Judul</label><input class="form-control" id="judul" name="judul" value="<?= aman_html($data_pengumuman['judul']); ?>" maxlength="200" required></div><div class="col-12"><label class="form-label" for="isi_pengumuman">Isi Pengumuman</label><textarea class="form-control" id="isi_pengumuman" name="isi_pengumuman" rows="7" maxlength="10000" required><?= aman_html($data_pengumuman['isi_pengumuman']); ?></textarea></div><div class="col-md-6"><label class="form-label" for="tanggal_publikasi">Tanggal Publikasi</label><input class="form-control" id="tanggal_publikasi" type="datetime-local" name="tanggal_publikasi" value="<?= aman_html(date('Y-m-d\TH:i', strtotime($data_pengumuman['tanggal_publikasi']))); ?>" required></div><div class="col-md-6"><label class="form-label" for="status_aktif">Status</label><select class="form-select" id="status_aktif" name="status_aktif" required><option value="aktif" <?= $data_pengumuman['status_aktif'] === 'aktif' ? 'selected' : ''; ?>>Aktif</option><option value="tidak_aktif" <?= $data_pengumuman['status_aktif'] === 'tidak_aktif' ? 'selected' : ''; ?>>Nonaktif</option></select></div><div class="col-12"><label class="form-label" for="gambar">Gambar</label><?php if ($mode_edit && is_string($data_pengumuman['gambar']) && $data_pengumuman['gambar'] !== '' && is_file(__DIR__ . '/../uploads/pengumuman/' . basename($data_pengumuman['gambar']))): ?><div class="mb-2"><img class="img-thumbnail" src="<?= aman_html(url_aplikasi('uploads/pengumuman/' . rawurlencode(basename($data_pengumuman['gambar'])))); ?>" alt="Preview gambar pengumuman" style="max-width: 240px; max-height: 180px;"></div><?php endif; ?><input class="form-control" id="gambar" type="file" name="gambar" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"><div class="form-text"><?= $mode_edit ? 'Kosongkan jika tidak ingin mengganti gambar.' : 'Opsional. Format JPG, JPEG, PNG, atau WEBP; maksimal 2 MB.'; ?></div></div></div><button class="btn btn-success mt-4" type="submit"><?= $mode_edit ? 'Simpan Perubahan' : 'Simpan Pengumuman'; ?></button> <a class="btn btn-outline-secondary mt-4" href="<?= aman_html(url_aplikasi('admin/data-pengumuman.php')); ?>">Batal</a></form></div></section>
    </main>
</div>
<?php require_once __DIR__ . '/../includes/kaki.php'; ?>
