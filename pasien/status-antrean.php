<?php
// ================= HALAMAN STATUS ANTREAN =================
// Menampilkan seluruh antrean aktif milik pasien yang sedang login.

declare(strict_types=1);

require_once __DIR__ . '/../includes/middleware_peran.php';
require_once __DIR__ . '/../konfigurasi/koneksi_database.php';

wajib_peran('pasien');

// ================= PENGAMBILAN ANTREAN AKTIF =================

$ambil_antrean_aktif = $koneksi_database->prepare(
    "SELECT a.nomor_antrean, a.tanggal_antrean, a.status_antrean, a.waktu_dipanggil, po.kode_poli, po.nama_poli
     FROM antrean a
     INNER JOIN pasien p ON p.id_pasien = a.id_pasien
     INNER JOIN poli po ON po.id_poli = a.id_poli
     WHERE p.id_pengguna = :id_pengguna
       AND a.status_antrean IN ('menunggu', 'dipanggil')
     ORDER BY a.tanggal_antrean DESC, a.nomor_antrean ASC"
);
$ambil_antrean_aktif->execute([
    'id_pengguna' => (int) $_SESSION['pengguna']['id_pengguna'],
]);
$daftar_antrean_aktif = $ambil_antrean_aktif->fetchAll();

$label_status = [
    'menunggu' => 'Menunggu',
    'dipanggil' => 'Dipanggil',
];
$kelas_status = [
    'menunggu' => 'warning',
    'dipanggil' => 'primary',
];

$judul_halaman = 'Status Antrean';
$stylesheet_tambahan = 'assets/css/gaya-dashboard.css';
require_once __DIR__ . '/../includes/kepala.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<div class="d-flex dashboard-layout halaman-dashboard">
    <?php require_once __DIR__ . '/../includes/sidebar_pasien.php'; ?>
    <main class="flex-grow-1 p-3 p-md-4 p-xl-5">
        <div class="mb-4">
            <p class="teks-label mb-1">Layanan Pasien</p>
            <h1 class="h3 mb-1">Status Antrean</h1>
            <p class="text-secondary mb-0">Pantau status antrean Anda yang masih aktif.</p>
        </div>

        <?php require_once __DIR__ . '/../includes/notifikasi.php'; ?>

        <?php if ($daftar_antrean_aktif === []): ?>
            <section class="card kartu-dashboard" style="max-width: 680px;">
                <div class="card-body p-4 p-md-5">
                    <h2 class="judul-kartu mb-2">Tidak Ada Antrean Aktif</h2>
                    <p class="text-secondary mb-0">Anda belum memiliki antrean dengan status menunggu atau dipanggil.</p>
                </div>
            </section>
        <?php else: ?>
            <section class="card kartu-dashboard">
                <div class="card-body p-0">
                    <div class="p-3 p-md-4 border-bottom">
                        <h2 class="judul-kartu mb-0">Daftar Antrean Aktif</h2>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-success">
                                <tr>
                                    <th class="ps-3">Nomor Antrean</th>
                                    <th>Nama Poli</th>
                                    <th>Tanggal Antrean</th>
                                    <th>Status</th>
                                    <th class="pe-3">Waktu Dipanggil</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($daftar_antrean_aktif as $antrean): ?>
                                    <tr>
                                        <td class="ps-3 fw-semibold"><?= aman_html($antrean['kode_poli'] . '-' . str_pad((string) $antrean['nomor_antrean'], 3, '0', STR_PAD_LEFT)); ?></td>
                                        <td><?= aman_html($antrean['nama_poli']); ?></td>
                                        <td><?= aman_html(date('d/m/Y', strtotime($antrean['tanggal_antrean']))); ?></td>
                                        <td><span class="badge text-bg-<?= aman_html($kelas_status[$antrean['status_antrean']] ?? 'secondary'); ?>"><?= aman_html($label_status[$antrean['status_antrean']] ?? 'Tidak diketahui'); ?></span></td>
                                        <td class="pe-3"><?= $antrean['waktu_dipanggil'] !== null ? aman_html(date('d/m/Y H:i', strtotime($antrean['waktu_dipanggil']))) : '-'; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        <?php endif; ?>
    </main>
</div>
<?php require_once __DIR__ . '/../includes/kaki.php'; ?>
