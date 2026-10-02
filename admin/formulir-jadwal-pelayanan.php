<?php
// ================= FORMULIR JADWAL PELAYANAN =================
// Menampilkan form tambah atau edit jadwal pelayanan untuk Admin.

declare(strict_types=1);

require_once __DIR__ . '/../includes/middleware_peran.php';
require_once __DIR__ . '/../konfigurasi/koneksi_database.php';

wajib_peran('admin');

$id_jadwal_pelayanan = isset($_GET['id_jadwal_pelayanan']) && ctype_digit((string) $_GET['id_jadwal_pelayanan'])
    ? (int) $_GET['id_jadwal_pelayanan']
    : null;
$mode_edit = $id_jadwal_pelayanan !== null;
$data_jadwal = [
    'id_jadwal_pelayanan' => '',
    'hari' => '',
    'jam_buka' => '',
    'jam_tutup' => '',
    'keterangan' => '',
    'status_aktif' => 'aktif',
];

if ($mode_edit) {
    $ambil_jadwal = $koneksi_database->prepare(
        'SELECT id_jadwal_pelayanan, hari, jam_buka, jam_tutup, keterangan, status_aktif
         FROM jadwal_pelayanan
         WHERE id_jadwal_pelayanan = :id_jadwal_pelayanan
         LIMIT 1'
    );
    $ambil_jadwal->execute(['id_jadwal_pelayanan' => $id_jadwal_pelayanan]);
    $hasil_jadwal = $ambil_jadwal->fetch();

    if ($hasil_jadwal === false) {
        simpan_notifikasi('danger', 'Data jadwal pelayanan tidak ditemukan.');
        alihkan('admin/data-jadwal-pelayanan.php');
    }

    $data_jadwal = $hasil_jadwal;
}

$judul_halaman = $mode_edit ? 'Edit Jadwal Pelayanan' : 'Tambah Jadwal Pelayanan';
$stylesheet_tambahan = 'assets/css/gaya-dashboard.css';
$menu_aktif = 'jadwal-pelayanan';
require_once __DIR__ . '/../includes/kepala.php';
require_once __DIR__ . '/../includes/navbar_admin.php';
?>
<div class="d-flex dashboard-layout halaman-dashboard">
    <?php require_once __DIR__ . '/../includes/sidebar_admin.php'; ?>
    <main class="flex-grow-1 p-3 p-md-4 p-xl-5">
        <div class="mb-4"><p class="teks-label mb-1">Manajemen Layanan</p><h1 class="h3 mb-1"><?= $mode_edit ? 'Edit Jadwal Pelayanan' : 'Tambah Jadwal Pelayanan'; ?></h1><p class="text-secondary mb-0">Lengkapi jadwal pelayanan dengan benar.</p></div>
        <?php require_once __DIR__ . '/../includes/notifikasi.php'; ?>
        <section class="card kartu-dashboard" style="max-width: 760px;"><div class="card-body p-4"><form method="post" action="<?= aman_html(url_aplikasi('proses/proses_jadwal_pelayanan.php')); ?>"><input type="hidden" name="token_csrf" value="<?= aman_html(token_csrf()); ?>"><input type="hidden" name="aksi" value="<?= $mode_edit ? 'ubah' : 'tambah'; ?>"><?php if ($mode_edit): ?><input type="hidden" name="id_jadwal_pelayanan" value="<?= (int) $data_jadwal['id_jadwal_pelayanan']; ?>"><?php endif; ?><div class="row g-3"><div class="col-md-6"><label class="form-label" for="hari">Hari</label><select class="form-select" id="hari" name="hari" required><option value="">Pilih hari</option><?php foreach (['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'] as $hari): ?><option value="<?= aman_html($hari); ?>" <?= $data_jadwal['hari'] === $hari ? 'selected' : ''; ?>><?= aman_html($hari); ?></option><?php endforeach; ?></select></div><div class="col-md-3"><label class="form-label" for="jam_buka">Jam Buka</label><input class="form-control" id="jam_buka" type="time" name="jam_buka" value="<?= aman_html(substr($data_jadwal['jam_buka'], 0, 5)); ?>" required></div><div class="col-md-3"><label class="form-label" for="jam_tutup">Jam Tutup</label><input class="form-control" id="jam_tutup" type="time" name="jam_tutup" value="<?= aman_html(substr($data_jadwal['jam_tutup'], 0, 5)); ?>" required></div><div class="col-12"><label class="form-label" for="keterangan">Keterangan</label><textarea class="form-control" id="keterangan" name="keterangan" rows="4" maxlength="1000" placeholder="Keterangan jadwal (opsional)"><?= aman_html($data_jadwal['keterangan']); ?></textarea></div><div class="col-md-6"><label class="form-label" for="status_aktif">Status</label><select class="form-select" id="status_aktif" name="status_aktif" required><option value="aktif" <?= $data_jadwal['status_aktif'] === 'aktif' ? 'selected' : ''; ?>>Aktif</option><option value="tidak_aktif" <?= $data_jadwal['status_aktif'] === 'tidak_aktif' ? 'selected' : ''; ?>>Nonaktif</option></select></div></div><button class="btn btn-success mt-4" type="submit"><?= $mode_edit ? 'Simpan Perubahan' : 'Simpan Jadwal'; ?></button> <a class="btn btn-outline-secondary mt-4" href="<?= aman_html(url_aplikasi('admin/data-jadwal-pelayanan.php')); ?>">Batal</a></form></div></section>
    </main>
</div>
<?php require_once __DIR__ . '/../includes/kaki.php'; ?>
