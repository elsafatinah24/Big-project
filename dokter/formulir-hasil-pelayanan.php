<?php
// ================= FORMULIR HASIL PELAYANAN =================
// Menampilkan form pencatatan hasil pelayanan untuk antrean selesai milik Dokter terkait.

declare(strict_types=1);

require_once __DIR__ . '/../includes/middleware_peran.php';
require_once __DIR__ . '/../konfigurasi/koneksi_database.php';

wajib_peran('dokter');

$id_antrean = isset($_GET['id_antrean']) && ctype_digit((string) $_GET['id_antrean'])
    ? (int) $_GET['id_antrean']
    : 0;
$id_dokter = (int) $_SESSION['pengguna']['id_pengguna'];

if ($id_antrean < 1) {
    simpan_notifikasi('danger', 'Identitas antrean tidak valid.');
    alihkan('dokter/antrean.php');
}

// Akses form dibatasi oleh penugasan poli dan Dokter yang tercatat saat antrean dipanggil.
$ambil_antrean = $koneksi_database->prepare(
    'SELECT antrean.id_antrean, antrean.nomor_antrean, antrean.tanggal_antrean,
            antrean.status_antrean, antrean.hasil_pelayanan, antrean.keterangan_pelayanan,
            pasien.nama_pasien, poli.kode_poli, poli.nama_poli
     FROM antrean
     INNER JOIN dokter_poli
         ON dokter_poli.id_poli = antrean.id_poli
         AND dokter_poli.id_pengguna = :id_dokter_penugasan
     INNER JOIN pasien ON pasien.id_pasien = antrean.id_pasien
     INNER JOIN poli ON poli.id_poli = antrean.id_poli
     WHERE antrean.id_antrean = :id_antrean
       AND antrean.id_dokter = :id_dokter_antrean
       AND antrean.status_antrean = \'selesai\'
     LIMIT 1'
);
$ambil_antrean->execute([
    'id_dokter_penugasan' => $id_dokter,
    'id_dokter_antrean' => $id_dokter,
    'id_antrean' => $id_antrean,
]);
$antrean = $ambil_antrean->fetch();

if ($antrean === false) {
    simpan_notifikasi('danger', 'Antrean selesai tidak ditemukan atau bukan wewenang Anda.');
    alihkan('dokter/antrean.php');
}

$hasil_sudah_diisi = $antrean['hasil_pelayanan'] !== null || $antrean['keterangan_pelayanan'] !== null;
$judul_halaman = 'Hasil Pelayanan';
$stylesheet_tambahan = 'assets/css/gaya-dashboard.css';
$menu_aktif = 'antrean';
require_once __DIR__ . '/../includes/kepala.php';
require_once __DIR__ . '/../includes/navbar_dokter.php';
?>
<div class="d-flex dashboard-layout halaman-dashboard">
    <?php require_once __DIR__ . '/../includes/sidebar_dokter.php'; ?>
    <main class="flex-grow-1 p-3 p-md-4 p-xl-5">
        <div class="mb-4">
            <p class="teks-label mb-1">Antrean Pelayanan</p>
            <h1 class="h3 mb-1">Hasil Pelayanan</h1>
            <p class="text-secondary mb-0"><?= aman_html($antrean['kode_poli'] . '-' . str_pad((string) $antrean['nomor_antrean'], 3, '0', STR_PAD_LEFT) . ' · ' . $antrean['nama_pasien'] . ' · ' . $antrean['nama_poli']); ?></p>
        </div>
        <?php require_once __DIR__ . '/../includes/notifikasi.php'; ?>

        <section class="card kartu-dashboard" style="max-width: 760px;"><div class="card-body p-4">
            <?php if ($hasil_sudah_diisi): ?>
                <div class="alert alert-success" role="alert">Hasil pelayanan untuk antrean ini sudah diisi.</div>
                <dl class="row mb-0">
                    <dt class="col-sm-4">Hasil Pelayanan</dt><dd class="col-sm-8"><?= aman_html($antrean['hasil_pelayanan'] === 'pengobatan' ? 'Pengobatan' : 'Rujuk'); ?></dd>
                    <dt class="col-sm-4">Keterangan Pelayanan</dt><dd class="col-sm-8 text-break"><?= aman_html($antrean['keterangan_pelayanan']); ?></dd>
                </dl>
                <a class="btn btn-outline-secondary mt-3" href="<?= aman_html(url_aplikasi('dokter/antrean.php')); ?>">Kembali</a>
            <?php else: ?>
                <form method="post" action="<?= aman_html(url_aplikasi('proses/proses_hasil_pelayanan_dokter.php')); ?>">
                    <input type="hidden" name="token_csrf" value="<?= aman_html(token_csrf()); ?>">
                    <input type="hidden" name="id_antrean" value="<?= (int) $antrean['id_antrean']; ?>">
                    <div class="mb-3">
                        <label class="form-label" for="hasil_pelayanan">Hasil Pelayanan</label>
                        <select class="form-select" id="hasil_pelayanan" name="hasil_pelayanan" required>
                            <option value="">Pilih hasil pelayanan</option>
                            <option value="pengobatan">Pengobatan</option>
                            <option value="rujuk">Rujuk</option>
                        </select>
                        <div class="form-text">Pilihan mengikuti nilai yang tersedia pada struktur database.</div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label" for="keterangan_pelayanan">Keterangan Pelayanan</label>
                        <textarea class="form-control" id="keterangan_pelayanan" name="keterangan_pelayanan" rows="5" maxlength="1000" required></textarea>
                    </div>
                    <button class="btn btn-success" type="submit">Simpan Hasil Pelayanan</button>
                    <a class="btn btn-outline-secondary" href="<?= aman_html(url_aplikasi('dokter/antrean.php')); ?>">Batal</a>
                </form>
            <?php endif; ?>
        </div></section>
    </main>
</div>
<?php require_once __DIR__ . '/../includes/kaki.php'; ?>
