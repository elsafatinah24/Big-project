<?php
// ================= HALAMAN AMBIL ANTREAN =================
// Menampilkan poli aktif yang dapat dipilih pasien untuk mengambil nomor antrean hari ini.

declare(strict_types=1);

require_once __DIR__ . '/../includes/middleware_peran.php';
require_once __DIR__ . '/../konfigurasi/koneksi_database.php';

wajib_peran('pasien');

// ================= DATA POLI AKTIF =================

$ambil_poli = $koneksi_database->query(
    "SELECT id_poli, kode_poli, nama_poli, keterangan
     FROM poli
     WHERE status_aktif = 'aktif'
     ORDER BY nama_poli ASC"
);
$daftar_poli = $ambil_poli->fetchAll();

// Menggunakan jenis pembayaran profil sebagai pilihan awal tanpa mengunci pilihan pasien.
$metode_pembayaran_default = 'umum';
$ambil_pembayaran_default = $koneksi_database->prepare(
    'SELECT jenis_pembayaran FROM pasien WHERE id_pengguna = :id_pengguna LIMIT 1'
);
$ambil_pembayaran_default->execute(['id_pengguna' => (int) $_SESSION['pengguna']['id_pengguna']]);
$jenis_pembayaran_pasien = $ambil_pembayaran_default->fetchColumn();

if (in_array($jenis_pembayaran_pasien, ['umum', 'bpjs', 'asuransi_lain'], true)) {
    $metode_pembayaran_default = $jenis_pembayaran_pasien;
}

$tanggal_hari_ini = date('Y-m-d');
$tanggal_besok = (new DateTimeImmutable('tomorrow'))->format('Y-m-d');

$judul_halaman = 'Ambil Antrean';
$stylesheet_tambahan = 'assets/css/gaya-dashboard.css';
$menu_aktif = 'ambil-antrean';
require_once __DIR__ . '/../includes/kepala.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<div class="d-flex dashboard-layout halaman-dashboard">
    <?php require_once __DIR__ . '/../includes/sidebar_pasien.php'; ?>
    <main class="flex-grow-1 p-3 p-md-4">
        <div class="container-fluid px-0" style="max-width: 760px;">
            <?php require_once __DIR__ . '/../includes/notifikasi.php'; ?>
            <p class="teks-label mb-1">Layanan Pasien</p>
            <h1 class="h3 text-success mb-2">Ambil Antrean</h1>
            <p class="text-secondary mb-4">Lengkapi data kunjungan untuk mendapatkan nomor antrean.</p>

            <section class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <?php if ($daftar_poli === []): ?>
                        <p class="text-secondary mb-0">Belum ada poli aktif yang tersedia untuk dipilih.</p>
                    <?php else: ?>
                        <form method="post" action="<?= aman_html(url_aplikasi('proses/proses_ambil_antrean.php')); ?>">
                            <input type="hidden" name="token_csrf" value="<?= aman_html(token_csrf()); ?>">
                            <div class="mb-3">
                                <label for="id_poli" class="form-label">Poli Tujuan</label>
                                <select class="form-select" id="id_poli" name="id_poli" required>
                                    <option value="">Pilih poli</option>
                                    <?php foreach ($daftar_poli as $poli): ?>
                                        <option value="<?= (int) $poli['id_poli']; ?>">
                                            <?= aman_html($poli['kode_poli'] . ' - ' . $poli['nama_poli']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="metode_pembayaran" class="form-label">Metode Pembayaran / Jaminan</label>
                                <select class="form-select" id="metode_pembayaran" name="metode_pembayaran" required>
                                    <option value="umum" <?= $metode_pembayaran_default === 'umum' ? 'selected' : ''; ?>>Umum</option>
                                    <option value="bpjs" <?= $metode_pembayaran_default === 'bpjs' ? 'selected' : ''; ?>>BPJS</option>
                                    <option value="asuransi_lain" <?= $metode_pembayaran_default === 'asuransi_lain' ? 'selected' : ''; ?>>Asuransi Lain</option>
                                </select>
                                <div class="form-text">Anda dapat mengubah pilihan ini untuk kunjungan saat ini.</div>
                            </div>
                            <fieldset class="mb-3">
                                <legend class="form-label mb-2">Tanggal Antrean</legend>
                                <div class="vstack gap-2">
                                    <div class="form-check border rounded-3 p-3">
                                        <input class="form-check-input ms-0 me-2" id="tanggal_hari_ini" type="radio" name="tanggal_antrean" value="<?= aman_html($tanggal_hari_ini); ?>" checked required>
                                        <label class="form-check-label" for="tanggal_hari_ini"><strong>Hari Ini</strong> — <?= aman_html(date('d/m/Y', strtotime($tanggal_hari_ini))); ?></label>
                                    </div>
                                    <div class="form-check border rounded-3 p-3">
                                        <input class="form-check-input ms-0 me-2" id="tanggal_besok" type="radio" name="tanggal_antrean" value="<?= aman_html($tanggal_besok); ?>" required>
                                        <label class="form-check-label" for="tanggal_besok"><strong>Besok</strong> — <?= aman_html(date('d/m/Y', strtotime($tanggal_besok))); ?></label>
                                    </div>
                                </div>
                            </fieldset>
                            <div class="mb-4">
                                <label for="keluhan" class="form-label">Keluhan</label>
                                <textarea class="form-control" id="keluhan" name="keluhan" rows="4" maxlength="1000" placeholder="Tuliskan keluhan Anda (opsional)"></textarea>
                                <div class="form-text">Maksimal 1.000 karakter. Jangan memasukkan data pribadi yang tidak diperlukan.</div>
                            </div>
                            <button type="submit" class="btn btn-success">Ambil Antrean</button>
                        </form>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </main>
</div>
<?php require_once __DIR__ . '/../includes/kaki.php'; ?>
